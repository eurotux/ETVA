#!/usr/bin/perl


=pod

=head1 NAME

ETVOIP

=head1 SYNOPSIS

...

=head1 DESCRIPTION

...

=head1 METHODS

=over 4

=cut

package ETVOIP;

use strict;

use ETVA::Utils;
use LWP::Simple;
#use FileFuncs;

# Default Active modules
my %DefMod = ( 'pbx'=>'ETVOIP::PBX' );

# ETFW/Perl modules match
my %ModMatch = ();

my %CONF = ( 'pkg_list'=>"/etc/sysconfig/etvoip/pkg_list.conf",
                'pkg_match'=>'./pkg_match.conf' );

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

=item
    get backup
=cut
sub get_backupconf {
    my $self = shift;
    my (%p) = @_;    

    # create pbx backup archive (save to disk)
    # return retErr || retOk
    my $E = ETVOIP::PBX->backupconf();
        
    if( isError($E) ){        
        return wantarray() ? %$E : $E;
    }
       
    my $sock = $p{'_socket'};

    # set blocking for wait to transmission end
    $sock->blocking(1);

    if( $p{'_make_response'} ){
        print $sock $p{'_make_response'}->("",'-type'=>'application/x-compressed-tar');
    }

    if( my $c_path = ETVOIP::PBX->get_backupconf_file() ){
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
            return retErr("_ERR_GET_BACKUPCONF_","backup file '$c_path' doesnt exists.");
        }
    } else {

        plog "get_backupconf","No backup file.";
        return retErr("_ERR_GET_BACKUPCONF_","No backup file.");
    }
    return;
}


# set_backupconf - overwrite configuration file
sub set_backupconf {
    my $self = shift;
    my (%p) = @_;

    # create previous backup
    my $E = ETVOIP::PBX->backupconf();
    if( isError($E) ){
        return wantarray() ? %$E : $E;
    }


    if( my $bf = ETVOIP::PBX->get_backupconf_file() ){
        # TODO fix this name
        my $pbx_archive = ETVOIP::PBX->get_backup_archive();
        my @lbf = split(/\//,$pbx_archive);
        my $fn = pop(@lbf); # get file name

        plog("FILENAME $fn");

        if( $p{'_url'} ){
            my $rc = LWP::Simple::getstore("$p{'_url'}","$pbx_archive");
            if( is_error($rc) || !-e "$pbx_archive" ){
                return retErr('_ERR_SET_BACKUPCONF_',"Error get backup file ($pbx_archive status=$rc) ");
            }
        } else {
            my $sock = $p{'_socket'};

            # set blocking for wait to transmission end
            $sock->blocking(1);

            my $tar = ETVA::ArchiveTar->new();
            $tar->read($sock);
            plog "set_backupconf files=",$tar->list_files();
            $tar->write($pbx_archive);
        }

        my $s = ETVOIP::PBX->restoreconf($fn);        
        if( isError($s) ){
            return wantarray() ? %$s : $s;
        }

    } else {
        return retErr("_ERR_SET_BACKUPCONF_","No media to backup file.");
    }

    return retOk("_OK_SET_BACKUPCONF_","BACKUP SAVE ok.");
    
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
