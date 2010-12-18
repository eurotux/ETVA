#!/usr/bin/perl

# Copywrite Eurotux 2009
# 
# CMAR 2009/04/17 (cmar@eurotux.com)

=pod

=head1 NAME

Client::SOAP - Module for soap client functions

=head1 SYNOPSIS

    my $Client = Client::SOAP->new( PeerAddr=>$addr, PeerPort=>$port, Proto=>$porto );

    my $SOAP_Response = $Client->call( $uri, $method, @params );

=head1 DESCRIPTION

...

=head1 METHODS

=over 4

=cut

package Client::SOAP;

use strict;

BEGIN {
    # this is the worst damned warning ever, so SHUT UP ALREADY!
    $SIG{__WARN__} = sub { warn @_ unless $_[0] =~ /Use of uninitialized value/ };

    require Exporter;
    require Client;
    use vars qw($VERSION @ISA @EXPORT @EXPORT_OK %EXPORT_TAGS  $CRLF);
    $VERSION = '0.0.1';
    @ISA = qw( Client );
    @EXPORT = qw( );
}

use Utils;

use SOAP::Lite;

my $debug = 0;

=item new

    my $Client = Client::SOAP->new( PeerAddr=>$addr, PeerPort=>$port, Proto=>$porto );

=cut

sub new {
    my $self = shift;
    my (%params) = @_;

    # active debug
    $debug = 1 if( $params{'debug'} );

    return $self->SUPER::new(@_);
}

# soap_request
#  make soap request
#  args: uri, method, parameteres
sub soap_request {
    my $self = shift;
    my ($uri, $method, @params) = @_;

    my $soap_request = '';

    # HACK: force to be something in case is empty
    push(@params,'nil','true') if( !scalar(@params) );

    for my $p (@params){
        $p = encode_content( $p, 1 );
    }
    
    my $serializer = SOAP::Lite
                                ->uri($uri)
                                ->serializer();
    $soap_request = $serializer->envelope( method=>$method, make_soap_args($serializer, @params) );

    plog "soap_request = $soap_request" if( $debug || $self->{'debug'} );

    return $soap_request;
}

# soap_response
#  parsing soap response
#  args: data message
sub soap_response {
    my $self = shift;
    my ($data) = @_;

    # data is not xml
    if( ref( $data ) ){
        my %r = ( _error_ => 1, detail => $data->{'detail'} );
        return wantarray() ? %r : \%r;
    }

    my $data_xml;
    # clear no xml lines
    for my $line (split(/\r|\n/,$data)){
        if( $line =~ /<\?xml/ || $data_xml ){
            $data_xml .= $line . "\n";
        }
    }
    plog "soap_response = $data_xml\n" if( $debug || $self->{'debug'} );

    my ($headers,$body,$typeuri,$method);

    eval {
        my $som = SOAP::Deserializer->deserialize($data_xml);

        $som->match((ref $som)->method);
        ($typeuri, $method) = ($som->namespaceuriof || '', $som->dataof->name);

        my $st_body = $som->body();
        $body = $st_body->{"$method"};
    };
    if( $@ ){
        # TODO handle error
        my %r = ( _error_ => 1, detail=> $@ );
        return wantarray() ? %r : \%r;
    }

    return wantarray() ?  %$body : $body;
}

=item call

    my $SOAP_Response = $Client->call( $uri, $method, @params );

=cut

# call
#  soap call
#  args: uri, method, parameters
sub call {
    my $self = shift;
    my ($uri,$method,@params) = @_;

    # default uri
    $uri = "urn:#$method" if( !$uri );

    my $request = $self->soap_request($uri,$method,@params);

    plog "Client::SOAP call request = $request" if( $debug || $self->{'debug'} );

    my $response = $self->send_receive($request);

    plog "Client::SOAP call response = $response" if( $debug || $self->{'debug'} );

    return $self->soap_response($response);
}

sub set_debug {
    $debug = 1;
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

L<Client>, L<Agent>

=cut

