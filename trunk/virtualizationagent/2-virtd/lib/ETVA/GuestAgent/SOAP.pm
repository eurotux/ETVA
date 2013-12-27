#!/usr/bin/perl
#

package ETVA::GuestAgent::SOAP;

use strict;

use ETVA::Utils;

use ETVA::SOAP;

# process request
sub handlerequest {
    my $self = shift;
    my ($client,$request) = @_;

    plogNow __PACKAGE__,"handlerequest client=$client request=$request" if( &debug_level > 3 );
    # parse request
    my ($headers,$body,$typeuri,$method, $id) = $self->parse_request( $request );
    if( $@ ){
        plogNow __PACKAGE__," Failed while unmarshaling the request: $@" if( &debug_level > 3 );
        return $self->make_response_fault( $typeuri, "Server",
                                                'Application Faulted',
                                                "Failed while unmarshaling the request: $@", {'soap_msg_id'=> $id});
    }
    plogNow __PACKAGE__," handlerequest client=$client method=$method id=$id" if( &debug_level > 3 );

    return $self->exec_request($client, $headers,$body,$typeuri,$method, {'soap_msg_id'=> $id});
}

sub exec_request {
    my $self = shift;
    my ($client, $headers,$body,$typeuri,$method, @extra) = @_;

    my $response = "";
    my $R = $self->call_method($client, $headers,$body,$typeuri,$method, @extra);
    if( defined $R ){ # response only if return somethin
        if( isError($R) ){
            $response = $self->make_response_fault($typeuri,$R->{'_errorcode_'},
                                                    $R->{'_errorstring_'},
                                                    $R->{'_errordetail_'}, @extra);
        } else {
            $response = $self->make_response($typeuri, $method, $R, @extra);
        }
    }
    return $response;
}

sub call_method {
    my $self = shift;
    my ($client, $headers,$body,$typeuri,$method, @extra) = @_;

    # dispatcher class
    my $request_class = $self->{'_dispatcher'};

    # check is can't load the class
    eval "require $request_class";
    if( $@ ){
        return retErr('Application Faulted',
                            "Failed to load Perl module $request_class: $@", "Server");
    }

    # treat params of request
    my %params = &get_params($body);

    # share with method client socket
    $params{'_socket'} = $client;

    # handlers to write response
    $params{'_make_response'} = sub { my ($res,@aux) = @_; return $self->make_response($typeuri, $method, $res, @extra, @aux); };
    $params{'_make_response_fault'} = sub { my ($code,$string,$detail) = @_; return $self->make_response_fault($typeuri,$code,$string,$detail, @extra ); };

    my $R;
 
    # call the method
    plogNow(__PACKAGE__," call_method method=$method");
    plogNow "params Dump=",Dumper(\%params),"\n" if( &debug_level > 9 );
    eval {
        $R = $request_class->$method(%params);
    };
    if( $@ ){
        return retErr('Application Faulted',
                            "An exception fired while processing the request: $@", "Server");
    }
    plogNow  "result Dumper=",Dumper($R),"\n" if( &debug_level > 9 );
    return $R;
}

sub make_response {
    my $self = shift;
    my ($typeuri, $method, $res, @extra) = @_;

    return ETVA::SOAP::response_soap( $typeuri, $method, $res, @extra);
}

sub make_response_fault {
    my $self = shift;
    my ($typeuri,$faultcode,$faultstring,$detail,@extra) = @_;
    return ETVA::SOAP::response_soap_fault($typeuri,$faultcode,$faultstring,$detail,@extra);

}

sub parse_request {
    my $self = shift;
    my ($request) = @_;
    return ETVA::SOAP::parse_soap_request( $request );
}

1;
