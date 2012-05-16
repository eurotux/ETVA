#!/usr/bin/perl


=pod

=head1 NAME

ETFW

=head1 SYNOPSIS

...

=head1 DESCRIPTION

...

=head1 METHODS

=over 4

=cut

package ETFW;

use strict;

use ETVA::Utils;
use ETVA::ArchiveTar;

use FileFuncs;

use LWP::Simple;

# Default Active modules
my %DefMod = ( 'network'=>'ETFW::Network', 'firewall'=>'ETFW::Firewall', 'webmin'=>'ETFW::Webmin', 'wizard'=>'ETFWWizard', 'dhcp'=>'ETFW::DHCP', 'squid'=>'ETFW::Squid' );

# ETFW/Perl modules match
my %ModMatch = ();

my %CONF = ( 'conf_dir'=>"/etc/etfw", 'pkg_list'=>"/etc/etfw/keys/pkg_list.txt",
                'conf_file'=>"/etc/etfw/etfw.ini",
                'pkg_match'=>'./pkg_match.conf', 'etfw_bin'=>'/usr/sbin/etfw' );

sub load_module {
    my $self = shift;

    open(F,$CONF{"pkg_match"});
    while(<F>){
        chomp;
        s/#.*//;
        if( /^\s*(\S+)\s*=\s*(.+)$/ ){
            $ModMatch{"$1"} = $2;
        }
    }
    close(F);
}

=item get_allmodules

    show all ETFW modules

=cut

sub get_allmodules {
    my $self = shift;

    $self->load_module();

    return wantarray() ? %ModMatch : \%ModMatch;
}

=item get_activemodules

    show active ETFW modules

=cut

sub get_activemodules {
    my $self = shift;

    my %mod = %DefMod;
    open(M,$CONF{"pkg_list"});
    while(<M>){
        chomp;
        s/#.*//;
        if( my ($emod) = ( $_ =~ m/^\s*(\S+)/ ) ){
            if( my $pmod = $ModMatch{"$emod"} ){
                $mod{"$emod"} = $pmod;
            }
        }
    }
    close(M);

    return wantarray() ? %mod : \%mod;
}

=item add_module / del_module

    add / delete module from ETFW 

=cut

sub add_module {
    my $self = shift;
    my (%p) = @_;
    if( my $mod = $p{"mod"} ){
        my $cfref = read_file_lines($CONF{"pkg_list"});
        push(@$cfref,$mod);
        flush_file_lines($CONF{"pkg_list"});
    }
}

sub del_module {
    my $self = shift;
    my (%p) = @_;
    if( my $mod = $p{"mod"} ){
        my $lnum;
        my $cfref = read_file_lines($CONF{"pkg_list"});
        for my $l (@$cfref){
            if( $l eq $mod ){ 
                $lnum ||= 0;
                last;
            }
            $lnum++;
        }
        if( defined $lnum ){    # mod found
            # delete them
            splice(@$cfref,$lnum,1);
        }
        flush_file_lines($CONF{"pkg_list"});
    }
}

=item etfw_save / etfw_restore 

    save and restore etfw methods

=cut

sub etfw_save {
    my $self = shift;
    my (%p) = @_;

    if( -x $CONF{"etfw_bin"} ){
        my $to = $p{"to"} || "harddisk";
        my ($e,$m) = cmd_exec($CONF{"etfw_bin"},"save",$to);
        unless( $e == 0 ){
            return retErr("_ERR_ETFWSAVE_","Error saving ETFW: $m");
        }
    } else {
        return retErr("_ERR_NOETFWBIN_","ETFW binary file not found.");
    }
}

sub etfw_restore {
    my $self = shift;
    my (%p) = @_;

    if( -x $CONF{"etfw_bin"} ){
        my $from = $p{"from"} || "harddisk";
        my $file = $p{"file"};
        my ($e,$m) = cmd_exec($CONF{"etfw_bin"},"restore",$from,$file);
        unless( $e == 0 ){
            return retErr("_ERR_ETFWRESTORE_","Error restore ETFW: $m");
        }
    } else {
        return retErr("_ERR_NOETFWBIN_","ETFW binary file not found.");
    }
}

sub get_etfw_backupfile {
    my $self = shift;

    my $cfg = new Config::IniFiles( -file => "$CONF{'conf_file'}");
    my $location = $cfg->val('harddisk','location');

    if( $location ){
        my (undef,$fbkp) = ( $location =~ m/^(\w+:\/\/)?(.+)$/ );
        return $fbkp;
    }
    return;
}

# get_backupconf - get backup of configuration file
sub get_backupconf {
    my $self = shift;
    my (%p) = @_;
    
    # save to harddisk to create backup
    my $E = $self->etfw_save( 'to'=>'harddisk' );
    if( isError($E) ){
        plog "get_backupconf","etfw_save";
        return wantarray() ? %$E : $E;
    }

    my $sock = $p{'_socket'};

    # set blocking for wait to transmission end
    $sock->blocking(1);
    
    if( $p{'_make_response'} ){
        print $sock $p{'_make_response'}->("",'-type'=>'application/x-compressed-tar');
    }

    if( my $c_path = $self->get_etfw_backupfile() ){
        if( -e "$c_path" ){
            plog "get_backupconf","c_path=$c_path";
            my $fh;
            open($fh,"$c_path");    # read from file
#            binmode($fh);
            my $buf;
            while (read($fh, $buf, 60*57)) {
                print $sock $buf;   # write to socket
            }
            close($fh);
        } else {
            plog "get_backupconf","backup file '$c_path' doesnt exists.";
            return retErr("_ERR_GETBACKUPCONF_","backup file '$c_path' doesnt exists.");
        }
    } else {
        plog "get_backupconf","No backup file.";
        return retErr("_ERR_GETBACKUPCONF_","No backup file.");
    }
    return;
}

# set_backupconf - overwrite configuration file
sub set_backupconf {
    my $self = shift;
    my (%p) = @_;

    # create previous backup
    my $E = $self->etfw_save( 'to'=>'harddisk' );
    if( isError($E) ){
        return wantarray() ? %$E : $E;
    }


    if( my $bf = $self->get_etfw_backupfile() ){
        # TODO fix this name
        my $oribf = "$bf.recoverbackupconf";
        my @lbf = split(/\//,$oribf);
        my $fn = pop(@lbf); # get file name

        if( $p{'_url'} ){
            my $rc = LWP::Simple::getstore("$p{'_url'}","$oribf");
            if( is_error($rc) || !-e "$oribf" ){
                return retErr('_ERR_SET_BACKUPCONF_',"Error get backup file ($oribf status=$rc) ");
            }
        } else {
            my $sock = $p{'_socket'};

            # set blocking for wait to transmission end
            $sock->blocking(1);

            my $tar = ETVA::ArchiveTar->new();
            $tar->read($sock);
            plog "set_backupconf files=",$tar->list_files();
            $tar->write($oribf);
        }

        my $S = $self->etfw_restore( 'from'=>'harddisk', 'file'=>"$fn" );
        if( isError($S) ){
            return wantarray() ? %$S : $S;
        }
        
    } else {
        return retErr("_ERR_SETBACKUPCONF_","No media to backup file.");
    }

    return;
}

load_module();
1;

=back

=pod

=head1 BUGS

...

=head1 AUTHORS

...

=head1 COPYRIGHT

...

=head1 LICENSE

...

=head1 SEE ALSO


=cut
=back

=pod

=head1 BUGS

...

=head1 AUTHORS

...

=head1 COPYRIGHT

...

=head1 LICENSE

...

=head1 SEE ALSO


=cut
