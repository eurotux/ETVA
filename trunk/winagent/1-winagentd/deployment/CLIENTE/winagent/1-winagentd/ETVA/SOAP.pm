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
    @EXPORT = qw( soap_request soap_response parse_request response_soap_fault response_soap );
}

use ETVA::Utils;

use SOAP::Lite;

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

    my $data_xml;
    # clear no xml lines
    for my $line (split(/\r|\n/,$data)){
        if( $line =~ /<\?xml/ || $data_xml ){
            $data_xml .= $line . "\n";
        }
    }
    plog "soap_response = $data_xml\n" if( &debug_level > 3 );

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

# parse soap request
sub parse_request {
    my ($request) = @_;

    my $request_xml;
    # clear no xml lines
    for my $line (split(/\r|\n/,$request)){
        if( $line =~ /<\?xml/ || $request_xml ){
            $request_xml .= $line . "\n";
        }
    }
    my ($headers,$body,$typeuri,$method);

    eval {
        my $som = SOAP::Deserializer->deserialize($request_xml);

        $som->match((ref $som)->method);
        ($typeuri, $method) = ($som->namespaceuriof || '', $som->dataof->name);

        my $st_body = $som->body();
        $body = $st_body->{"$method"};
    };

plog  "header Dump=",Dumper($headers),"\n" if( &debug_level > 3 );
plog  "body Dump=",Dumper($body),"\n" if( &debug_level > 3 );

    return ($headers,$body,$typeuri,$method);
}

sub response_soap_fault {
    my ($typeuri, $faultcode, $faultstring, $result_desc) = @_;
    $faultcode = '' if( not defined $faultcode );
    $faultstring = '' if( not defined $faultstring );
    $result_desc = '' if( not defined $result_desc );

    plog("SOAP_FAULT: faultcode: $faultcode, faultstring: $faultstring, detail: $result_desc") if( &debug_level );

    my %a = ( faultcode => encode_content($faultcode,1,1),
                faultstring => encode_content($faultstring,1,1),
                detail => encode_content($result_desc,1,1) );

    my $response = SOAP::Lite
                                ->uri($typeuri)
                                ->serializer()
                                ->fault($a{"faultcode"},$a{"faultstring"},$a{"detail"} );

    plog("response_soap_fault: $response") if(&debug_level);

    return $response;
}

sub response_soap {
    my ($typeuri, $method, $res) = @_;
    
    # CMAR 04/02/2010
    # force to dont enconde entities
    my %a = ( result=>encode_content($res,1,1) );

    my $serializer = SOAP::Lite
                                ->uri($typeuri)
                                ->serializer();
    my $soap_response = $serializer->envelope( response=>"${method}Response", make_soap($serializer,\%a) );

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


