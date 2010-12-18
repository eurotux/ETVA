#!/usr/bin/perl

=pod

=head1 NAME

ETFW::TinyDNS::DNSCache

=head1 SYNOPSIS

...

=head1 DESCRIPTION

...

=head1 METHODS

=over 4

=cut

package ETFW::TinyDNS::DNSCache;

use strict;

use DNS::TinyDNS;
use DNS::TinyDNS::dnscache;

BEGIN {
    use vars qw($VERSION @ISA @EXPORT @EXPORT_OK %EXPORT_TAGS $CRLF $AUTOLOAD);
};

my %CONF = ( 'dir' => "/service/dnscache" );

my $DNSCache;

sub load_module {
    my $self = shift;
    my (%p) = @_;

    $p{"dir"} = $CONF{"dir"} if( !$p{"dir"} );

    $DNSCache = DNS::TinyDNS::dnscache->new( $p{"dir"} );
}

sub AUTOLOAD {
    my ($package,$method) = ( $AUTOLOAD =~ m/(.+)::([^:]+)$/ );

    $AUTOLOAD = sub {
                    my $self = shift;
                    return $DNSCache->$method(@_);
                };
    &$AUTOLOAD;
}

=item list_ips

=cut

=item add_ip

    ARGS: ip - ip address

=cut

sub add_ip {
    my $self = shift;
    my (%p) = @_;

    $DNSCache->add_ip( $p{"ip"} );
}

=item del_ip

    ARGS: ip - ip address

=cut

sub del_ip {
    my $self = shift;
    my (%p) = @_;

    $DNSCache->del_ip( $p{"ip"} );
}

=item list_servers

=cut

=item add_server

    ARGS: server - root server

=cut

sub add_server {
    my $self = shift;
    my (%p) = @_;

    $DNSCache->add_server( $p{"server"} );
}

=item del_server
    
    ARGS: server - root server

=cut

sub del_server {
    my $self = shift;
    my (%p) = @_;

    $DNSCache->del_server( $p{"server"} );
}

=item get_env

    ARGS: env1=>1, ..., envn=>1

=cut

sub get_env {
    my $self = shift;
    my (%p) = @_;

    return $DNSCache->get_env( keys %p );
}

=item set_env

    ARGS: env1=>"value1", ..., envn=>"valuen"

=cut

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

L<DNS::TinyDNS::dnscache>.
L<DNS::TinyDNS::dnsserver>.
L<DNS::TinyDNS>.

=cut

=pod

=head1 NAME

ETFW::TinyDNS::DNSServer

=head1 SYNOPSIS

...

=head1 DESCRIPTION

...

=head1 METHODS

=over 4

=cut

package ETFW::TinyDNS::DNSServer;

use strict;

use DNS::TinyDNS;
use DNS::TinyDNS::dnsserver;

BEGIN {
    use vars qw($VERSION @ISA @EXPORT @EXPORT_OK %EXPORT_TAGS $CRLF $AUTOLOAD);
};

my %CONF = ( 'dir' => "/service/tinydns" );

my $DNSServer;

sub load_module {
    my $self = shift;
    my (%p) = @_;

    $p{"dir"} = $CONF{"dir"} if( !$p{"dir"} );

    $DNSServer = DNS::TinyDNS::dnsserver->new( $p{'dir'} ); 
}

sub AUTOLOAD {
    my ($package,$method) = ( $AUTOLOAD =~ m/(.+)::([^:]+)$/ );

    $AUTOLOAD = sub {
                    my $self = shift;
                    return $DNSServer->$method(@_);
                };
    &$AUTOLOAD;
}

=item get_env

    ARGS: env1=>1, ..., envn=>1

=cut

sub get_env {
    my $self = shift;
    my (%p) = @_;

    return $DNSServer->get_env( keys %p );
}

=item set_env

    ARGS: env1=>"value1", ..., envn=>"valuen"

=cut


=item list_zones

=cut

=item get_zone

    RET:  type - String showing the type of the record ('ns','host','mx','alias','reverse')

          zone - zone

          ttl      - ttl of record

          ip       - ip of the host

          host     - host is only set with ns or mx type records

          priority - is only set with mx records

=cut

=item list

    ARGS: type - type of list

          zone - zone

=cut

=item add

    ARGS: type - type of record

          zone - zone

          ttl      - ttl of record

          ip       - ip of the host

          host     - host is only set with ns or mx type records

          priority - is only set with mx records

=cut 

=item del

    ARGS: type - type of record

          zone - zone

          ttl      - ttl of record

          ip       - ip of the host

          host     - host is only set with ns or mx type records

          priority - is only set with mx records

=cut

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

L<DNS::TinyDNS::dnscache>.
L<DNS::TinyDNS::dnsserver>.
L<DNS::TinyDNS>.

=cut

=pod

=head1 NAME

ETFW::TinyDNS

=head1 SYNOPSIS

...

=head1 DESCRIPTION

...

=head1 METHODS

=over 4

=cut

package ETFW::TinyDNS;

use strict;

BEGIN {
    use vars qw($VERSION @ISA @EXPORT @EXPORT_OK %EXPORT_TAGS $CRLF $AUTOLOAD);
};

sub load_module {
    my $self = shift;
    ETFW::TinyDNS::DNSCache->load_module( );
    ETFW::TinyDNS::DNSServer->load_module( ); 
}

sub AUTOLOAD {
    my ($package,$method) = ( $AUTOLOAD =~ m/(.+)::([^:]+)$/ );

    if( my ($func) = ($method =~ m/^dnscache_(\S+)/) ){
        $AUTOLOAD = sub {
                        my $self = shift;
                        return ETFW::TinyDNS::DNSCache->$func( @_ );
                    };
    } elsif( my ($func) = ($method =~ m/^dnsserver_(\S+)/) ){
        $AUTOLOAD = sub {
                        my $self = shift;
                        return ETFW::TinyDNS::DNSServer->$func( @_ );
                    };
    } else {
        $AUTOLOAD = sub {
                        my $self = shift;
                        my $wantarray = wantarray();
                        my $ref;
                        my @res;
                        # try DNS Server
                        eval {
                            $wantarray ? @res = ETFW::TinyDNS::DNSServer->$method( @_ )
                                        : $ref = ETFW::TinyDNS::DNSServer->$method( @_ );

                        };
                        if( $@ ){
                            # invoke DNS Cache method
                            return ETFW::TinyDNS::DNSCache->$method( @_ );
                        } else {
                            return $wantarray ? @res : $ref;
                        }
                    };
    }
    if( $AUTOLOAD ){ 
        &$AUTOLOAD;
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

L<DNS::TinyDNS::dnscache>.
L<DNS::TinyDNS::dnsserver>.
L<DNS::TinyDNS>.

=cut
