#!/usr/bin/perl

# Copywrite Eurotux 2009
# 
# CMAR 2013/09/13 (cmar@eurotux.com)

=pod

=head1 NAME

ETVA::SOAP - Module for soap functions

=head1 SYNOPSIS

=head1 DESCRIPTION

...

=head1 METHODS

=over 4

=cut

package ETVA::SOAP;

use strict;

BEGIN {
    # this is the worst damned warning ever, so SHUT UP ALREADY!
    $SIG{__WARN__} = sub { warn @_ unless $_[0] =~ /Use of uninitialized value/ };

    require Exporter;
    use vars qw($VERSION @ISA @EXPORT @EXPORT_OK %EXPORT_TAGS  $CRLF);
    $VERSION = '0.0.1';
    @ISA = qw( Exporter );
    @EXPORT = qw( soap_request soap_response parse_soap_response parse_soap_request response_soap_fault response_soap get_params
                    isSOAP isSOAPValid isSoapFault isSoapResponse isSoapResponseOrFault isSoapRequestOrFault
            );
}

use ETVA::Utils;

use SOAP::Lite;

use Data::Dumper;

# soap_request
#  make soap request
#  args: uri, method, parameteres
sub soap_request {
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

    plog "soap_request = $soap_request" if( &debug_level > 3 );

    return $soap_request;
}

# parse soap response
sub parse_soap_response {
    my ($data) = @_;

    my $data_xml;
    # clear no xml lines
    for my $line (split(/\r|\n/,$data)){
        if( $line =~ /<\?xml/ || $data_xml ){
            $data_xml .= $line . "\n";
        }
    }
    plogNow "parse_soap_response = $data_xml\n" if( &debug_level > 3 );

    my ($header,$body,$typeuri,$method,$id);

    eval {
        my $som = SOAP::Deserializer->deserialize($data_xml);

        $som->match((ref $som)->method);
        ($typeuri, $method) = ($som->namespaceuriof || '', $som->dataof->name);

        my $st_body = $som->body();
        $body = $st_body->{"$method"};

        $header = $som->header();
        $id = $header->{'soap_msg_id'} if( $header->{'soap_msg_id'} );
    };
    if( $@ ){
        # handle error
        $body = { _error_ => 1, detail=> $@ };
    }

    return ($header,$body,$typeuri,$method,$id);
}

# soap_response
#  parsing soap response
#  args: data message
sub soap_response {
    my ($data) = @_;

    # data is not xml
    if( ref( $data ) ){
        my %r = ( _error_ => 1, detail => $data->{'detail'} );
        return wantarray() ? %r : \%r;
    }

    my ($header,$body,$typeuri,$method,$id) = &parse_soap_response($data);

    return wantarray() ?  %$body : $body;
}

# parse soap request
sub parse_soap_request {
    my ($request) = @_;

    my $request_xml;
    # clear no xml lines
    for my $line (split(/\r|\n/,$request)){
        if( $line =~ /<\?xml/ || $request_xml ){
            $request_xml .= $line . "\n";
        }
    }
    my ($header,$body,$typeuri,$method,$id);

    eval {
        my $som = SOAP::Deserializer->deserialize($request_xml);

        $som->match((ref $som)->method);
        ($typeuri, $method) = ($som->namespaceuriof || '', $som->dataof->name);

        my $st_body = $som->body();
        $body = $st_body->{"$method"};

        $header = $som->header();
        $id = $header->{'soap_msg_id'} if( $header->{'soap_msg_id'} );
    };

plog  "header Dump=",Dumper($header),"\n" if( &debug_level > 3 );
plog  "body Dump=",Dumper($body),"\n" if( &debug_level > 3 );

    return ($header,$body,$typeuri,$method,$id);
}

sub response_soap_fault {
    my ($typeuri, $faultcode, $faultstring, $result_desc, $header) = @_;
    $faultcode = '' if( not defined $faultcode );
    $faultstring = '' if( not defined $faultstring );
    $result_desc = '' if( not defined $result_desc );

    plog("SOAP_FAULT: faultcode: $faultcode, faultstring: $faultstring, detail: $result_desc, header: $header") if( &debug_level > 3 );

    my %a = ( faultcode => encode_content($faultcode,1,1),
                faultstring => encode_content($faultstring,1,1),
                detail => encode_content($result_desc,1,1) );

    my $serializer = SOAP::Lite
                                ->uri($typeuri)
                                ->serializer();

    my @extra = ();
    push(@extra, make_soap($serializer,$header,1)) if( $header );

    my $response = $serializer->fault($a{"faultcode"},$a{"faultstring"},$a{"detail"}, @extra );

    plog("response_soap_fault: $response") if( &debug_level > 9 );

    return $response;
}

sub response_soap {
    my ($typeuri, $method, $res, $header) = @_;
    
    # CMAR 04/02/2010
    # force to dont enconde entities
    my %a = ( result=>encode_content($res,1,1) );

    my $serializer = SOAP::Lite
                                ->uri($typeuri)
                                ->serializer();

    my @extra = ();
    push(@extra, make_soap($serializer,$header,1)) if( $header );

    my $soap_response = $serializer->envelope( response=>"${method}Response", make_soap($serializer,\%a), @extra );

    plog("response_soap: $soap_response") if(&debug_level > 3);

    return $soap_response;
}

sub is_error {
    my ($response) = @_;
    if( ref($response) eq "HASH" && $response->{'_error_'} ){
        # TODO: fix faultcode
        return ("Server",$response->{'_error_'}{'type'},$response->{'_error_'}{'message'});
    }
    return;
}
sub get_params {
    my ($cnt) = @_;

    if( ref($cnt) eq 'ARRAY' ){
        for my $e (@$cnt){
            $e = get_params($e);
        }
    } elsif( ref($cnt) ){   # Hash or other object
        my $dbody = decode_content($cnt);
        return wantarray() ? %$dbody : $dbody;
    }
    return $cnt;
}

sub isSOAP {
    my ($request) = @_;

    for (split(/\r|\n/,$request)){
        if( /<\?xml/ ){
            return 1;
        }
    }
    return 0;
}

sub isSOAPValid {
    my ($request) = @_;

    my $request_xml;
    # clear no xml lines
    for my $line (split(/\r|\n/,$request)){
        if( $line =~ /<\?xml/ || $request_xml ){
            $request_xml .= $line . "\n";
        }
    }

    eval {
        SOAP::Deserializer->deserialize($request_xml);
    };
    return $@ ? 0 : 1;
}

sub isSoapFault {
    my ($message) = @_;
    return ( $message =~ m/<\S+Fault/gsi )? 1 : 0;
}
sub isSoapResponse {
    my ($message) = @_;
    return ( $message =~ m/<\S+Response/gsi )? 1 : 0;
}

sub isSoapResponseOrFault {
    my ($message) = @_;
    return &isSoapFault($message) || &isSoapResponse($message);
}
sub isSoapRequestOrFault {
    my ($message) = @_;
    return &isSoapFault($message) || !&isSoapResponse($message);
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


