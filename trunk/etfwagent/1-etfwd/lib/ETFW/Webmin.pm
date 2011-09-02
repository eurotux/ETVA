#!/usr/bin/perl

=pod

=head1 NAME

ETFW::Webmin

=head1 SYNOPSIS

...

=head1 DESCRIPTION

...

=head1 METHODS

=over 4

=cut

package ETFW::Webmin;

use strict;

use ETVA::Utils;

=item load_config

=cut

my %CONF = ( "conf_dir"=>"/etc/webmin" );

sub load_config {
    my $self = shift;
    my (%p) = @_;

    my $conf_dir = $CONF{"conf_dir"} = $p{"conf_dir"} ? $p{"conf_dir"} : $CONF{"conf_dir"};

    open(S,"$conf_dir/miniserv.conf");
    while(<S>){
        chomp;

        # ignore comments
        s/#\s*(.*)//;

        if( /(\S+)=(\S+)/ ){
            $CONF{"$1"} = $2;
        }
    }
    close(S);

    if( !$CONF{"host"} ){
        $CONF{"host"} = ETVA::Utils::get_ip();
    }
    if( !$CONF{"url"} ){
        my $proto = $CONF{"ssl"} ? "https" : "http";
        $CONF{"url"} = $proto . "://" . $CONF{"host"} . ":" . $CONF{"port"};
    }
    return wantarray() ? %CONF : \%CONF; 
}

=item get_config

=cut

sub get_config {
    my $self = shift;

    return $self->load_config();
}

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

