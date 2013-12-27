#!/usr/bin/perl
# Copywrite Eurotux 2009
# 
# CMAR 2009/04/03 (cmar@eurotux.com)

=pod

=head1 NAME

ETVA::Agent::SOAP - Agent class for treat SOAP calls

=head1 SYNOPSIS

    my $Agent = ETVA::Agent::SOAP->new( Port=>$port, ..., debug=>1, cm_uri=>..., _register_handler=>$reghandler, _alarmhandler_=>$keepalivehandler );

    $Agent->mainLoop();

=head1 DESCRIPTION

...

=head1 METHODS

=over 4

=cut

package ETVA::Agent::SOAP;

use strict;

BEGIN {
    # this is the worst damned warning ever, so SHUT UP ALREADY!
    $SIG{__WARN__} = sub { warn @_ unless $_[0] =~ /Use of uninitialized value/ };

    require Exporter;
    require ETVA::Agent;
    use vars qw($VERSION @ISA @EXPORT @EXPORT_OK %EXPORT_TAGS $CRLF);
    $VERSION = '0.0.1';
    @ISA = qw( ETVA::Agent );
    @EXPORT = qw(  );
}

use ETVA::Utils;

use SOAP::Lite;
use HTML::Entities;
use Data::Dumper;

# Universal Uniq id
my $UUID;
# CentralManagement conection params
my $cm_uri;
my $cm_namespace;

=item new

    my $Agent = ETVA::Agent::SOAP->new( Port=>$port, ..., debug=>1, cm_uri=>..., _register_handler=> sub { ... } );

    debug - debug flag

    cm_uri - Central Management URI

    cm_namespace - Central Management Namespace

    _register_handler - register handler

    _alarmhandler_ - alarm handler

=cut

sub new {
    my $self = shift;
    my $class = ref($self) || $self;
    my (%params) = @_;

    # active debug
    &set_debug_level($params{'debug'}) if( $params{'debug'} );

    $self = $self->SUPER::new(%params);

    $cm_uri = $self->{'cm_uri'};
    $cm_namespace = $self->{'cm_namespace'};
    $UUID = $self->{'uuid'};

    return $self;
}

sub register {
    my $self = shift;

    if( $self->{'_register_handler'} ){
        $self->{'_register_handler'}->($self);
    }
}
sub processdata {
    my $self = shift;
    my ($fh) = @_;

    plog("ETVA::Agent::SOAP processing data") if( &debug_level > 3 );

    # Get data
    my $soap_request = $self->receive($fh);

    plog("processdata: $soap_request") if( &debug_level > 3 );

    # treat soap request
    my $soap_response = $self->treatRequest($soap_request);

    # send response
    $self->send($fh,$soap_response);
}

sub isSOAP {
    my $self = shift;
    my ($request) = @_;

    for (split(/\r|\n/,$request)){
        if( /<\?xml/ ){
            return 1;
        }
    }
    return 0;
}

sub parse_request {
    my $self = shift;
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
sub treatRequest {
    my $self = shift;
    my ($request) = @_;

    my ($headers,$body,$typeuri,$method) = $self->parse_request( $request );

    if( $@ ){
plog "Failed while unmarshaling the request: $@" if( &debug_level );
        return $self->response_soap_fault( $typeuri, "Server",
                                                'Application Faulted',
                                                "Failed while unmarshaling the request: $@");
    }

    my $request_class = $self->{'_dispatcher'};

    eval "require $request_class";
    if( $@ ){
        return $self->response_soap_fault( $typeuri, "Server",
                                            'Application Faulted',
                                            "Failed to load Perl module $request_class: $@");
    }

    my %params = get_params($body);

    my $response;

plog  "paramas Dump=",Dumper(\%params),"\n" if( &debug_level > 3 );
    eval {
        my $res = $request_class->$method(%params);
        $res = {} if( not defined $res );
plog  "result Dumper=",Dumper($res),"\n" if( &debug_level > 3 );
        if( ref($res) eq 'HASH' && $res->{'_error_'} ){
            $response = $self->response_soap_fault($typeuri,$res->{'_errorcode_'},
                                                    $res->{'_errorstring_'},
                                                    $res->{'_errordetail_'});
        } else {
            $response = $self->response_soap($typeuri, $method, $res);
        }
    };
    if( $@ ){
        return $self->response_soap_fault( $typeuri, "Server",
                                            'Application Faulted',
                                            "An exception fired while processing the request: $@");
    }

    if( my ($faultcode,$faultstring,$detail) = is_error($response) ){
        return $self->response_soap_fault($typeuri,$faultcode,$faultstring,$detail);
    }

    return $response;
}

sub response_soap_fault {
    my $self = shift;
    my ($typeuri, $faultcode, $faultstring, $result_desc) = @_;
    $faultcode = '' if( not defined $faultcode );
    $faultstring = '' if( not defined $faultstring );
    $result_desc = '' if( not defined $result_desc );

    plog("SOAP_FAULT: faultcode: $faultcode, faultstring: $faultstring, detail: $result_desc") if( &debug_level > 3 );

    my %a = ( faultcode => encode_content($faultcode,1,1),
                faultstring => encode_content($faultstring,1,1),
                detail => encode_content($result_desc,1,1) );

    my $response = SOAP::Lite
                                ->uri($typeuri)
                                ->serializer()
                                ->fault($a{"faultcode"},$a{"faultstring"},$a{"detail"} );

    plog("response_soap_fault: $response") if( &debug_level > 9 );

    return $response;
}

sub response_soap {
    my $self = shift;
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

L<ETVA::Agent>, L<ETVA::Client>

=cut

