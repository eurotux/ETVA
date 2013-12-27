#!/usr/bin/perl

# Copywrite Eurotux 2009
# 
# CMAR 2009/04/17 (cmar@eurotux.com)

=pod

=head1 NAME

ETVA::Client::SOAP - Module for soap client functions

=head1 SYNOPSIS

    my $Client = ETVA::Client::SOAP->new( PeerAddr=>$addr, PeerPort=>$port, Proto=>$porto );

    my $SOAP_Response = $Client->call( $uri, $method, @params );

=head1 DESCRIPTION

...

=head1 METHODS

=over 4

=cut

package ETVA::Client::SOAP;

use strict;

BEGIN {
    # this is the worst damned warning ever, so SHUT UP ALREADY!
    $SIG{__WARN__} = sub { warn @_ unless $_[0] =~ /Use of uninitialized value/ };

    require Exporter;
    require ETVA::Client;
    use vars qw($VERSION @ISA @EXPORT @EXPORT_OK %EXPORT_TAGS  $CRLF);
    $VERSION = '0.0.1';
    @ISA = qw( ETVA::Client );
    @EXPORT = qw( );
}

use ETVA::Utils;

use SOAP::Lite;

use ETVA::SOAP;

use POSIX;

my $debug = 0;

=item new

    my $Client = ETVA::Client::SOAP->new( PeerAddr=>$addr, PeerPort=>$port, Proto=>$porto );

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

    # CMAR 04/02/2010
    # force to dont enconde entities
    for my $p (@params){
        $p = encode_content( $p, 1, 1 );
    }
    
    my $serializer = SOAP::Lite
                                ->uri($uri)
                                ->serializer();
    $soap_request = $serializer->envelope( method=>$method, make_soap_args($serializer, @params) );

    plog "soap_request = $soap_request" if( ($debug || $self->{'debug'}) > 3 );

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
    plog "soap_response = $data_xml\n" if( ($debug || $self->{'debug'}) > 3 );

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

    plog "ETVA::Client::SOAP call request = $request" if( ($debug || $self->{'debug'}) > 3 );

    my $response = $self->send_receive($request);

    plog "ETVA::Client::SOAP call response = $response" if( ($debug || $self->{'debug'}) > 3 );

    return $self->soap_response($response);
}

sub set_debug {
    $debug = 1;
}

# nonblock($socket) puts socket into nonblocking mode
# Perl Cookbook
sub nonblock {
     my $socket = shift;
     my $flags;

     $flags = fcntl($socket, F_GETFL, 0)
        or die "Can't get flags for socket: $!\n";
     fcntl($socket, F_SETFL, $flags | O_NONBLOCK)
        or die "Can't make socket nonblocking: $!\n";
}

sub receive {
    my $self = shift;

    my $fh = $self->{'_sock'};  # file handle

    &nonblock($fh);

    my $ready = 0;              # all data received
    my $data = "";              #  data
    my $rd = "";                # read result
    my $part;                   # partial read

    while (!$ready && ($rd = read($fh,$part,4096)) >= 0) {  # read data from socket
        if (defined $rd) {      # undef means failure
            if( $rd > 0 ){
                $data .= $part;  # join parts
                $ready = &isSOAPValid($data);
            }
        }
    }
    return $data;
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

L<ETVA::Client>, L<ETVA::Agent>

=cut

