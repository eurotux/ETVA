#!/usr/bin/perl

# Copywrite Eurotux 2009
# 
# CMAR 2009/04/28 (cmar@eurotux.com)

=pod

=head1 NAME

Client::SOAP::HTTP - Module for soap client functions with HTTP protocol

=head1 SYNOPSIS

    my $Client = Client::SOAP::HTTP->new( uri=>$uri );

    my $SOAP_Response = $Client->call( $uri, $method, @params );

=head1 DESCRIPTION

...

=head1 METHODS

=over 4

=cut

package Client::SOAP::HTTP;

use strict;

BEGIN {
    # this is the worst damned warning ever, so SHUT UP ALREADY!
    $SIG{__WARN__} = sub { warn @_ unless $_[0] =~ /Use of uninitialized value/ };

    require Exporter;
    require Client::SOAP;
    use vars qw($VERSION @ISA @EXPORT @EXPORT_OK %EXPORT_TAGS  $CRLF);
    $VERSION = '0.0.1';
    @ISA = qw( Client::SOAP );
    @EXPORT = qw( );
}

use Utils;

use LWP::UserAgent;
use HTTP::Request;

# debug flag
my $debug = 0;

=item new

    my $Client = Client::SOAP::HTTP->new( uri=>$uri );

    my $Client = Client::SOAP::HTTP->new( method=>$method, proto=>$proto, address=>$addr, port=>$port );

    uri - complete SOAP url

    method - POST or GET

    proto - http, ftp, ...

    address - the address of SOAP service

    port - the port of SOAP service

=cut

sub new {
    my $self = shift;
    
    unless( ref $self ){
        my $class = ref( $self ) || $self;

        my (%params) = @_;
        
        # active debug
        $debug = 1 if( $params{'debug'} );

        $self = bless {%params} => $class;

        my $uri = $params{'uri'};
        my $method = $params{'method'} || 'POST';
        if( !$uri ){
            my $proto = $params{'proto'} || 'http';
            $uri = $proto."://".$params{'address'}.":".$params{'port'};
        }
        my $ua = $self->{'_ua'} = new LWP::UserAgent(); 
        my $request = $self->{'_request'} = new HTTP::Request( $method => $uri );
#        die "Could not create socket: $!\n" unless $sock;
    }
	
	return $self;
}

sub send_receive {
    my $self = shift;
    my ($data) = @_;

plog "Client::SOAP::HTTP - send_receive data=$data\n" if( $debug || $self->{'debug'} );
    $self->{'_request'}->content($data);
    my $response = $self->{'_ua'}->request( $self->{'_request'} );

    if( $response->is_success() ){
        my $res = $response->content();
plog "Client::SOAP::HTTP - send_receive res=$res\n" if( $debug || $self->{'debug'} );
        return $res;
    } else {
        my $err = $response->status_line();
plog "Client::SOAP::HTTP - send_receive err=$err\n" if( $debug || $self->{'debug'} );
        return { _error_ => 1, detail => $err };
    }
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

L<Client>, L<Client::SOAP>, L<Agent>
L<LWP::UserAgent>, L<HTTP::Request>

=cut

