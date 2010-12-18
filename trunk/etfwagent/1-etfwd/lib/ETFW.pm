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

use Utils;
use FileFuncs;

# Default Active modules
my %DefMod = ( 'network'=>'ETFW::Network', 'firewall'=>'ETFW::Firewall', 'webmin'=>'ETFW::Webmin' );

# ETFW/Perl modules match
my %ModMatch = ();

my %CONF = ( 'conf_dir'=>"/etc/etfw", 'pkg_list'=>"/etc/etfw/keys/pkg_list.txt",
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
        cmd_exec($CONF{"etfw_bin"},"save",$to);
    } else {
        return retErr("_ERR_NOETFWBIN_","ETFW binary file not found.");
    }
}

sub etfw_restore {
    my $self = shift;
    my (%p) = @_;

    if( -x $CONF{"etfw_bin"} ){
        my $from = $p{"from"} || "harddisk";
        cmd_exec($CONF{"etfw_bin"},"save",$from);
    } else {
        return retErr("_ERR_NOETFWBIN_","ETFW binary file not found.");
    }
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
