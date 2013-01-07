#!/usr/bin/perl

use strict;

package ETVA::Agent::SOAPFork;

use strict;

use ETVA::Utils;

use POSIX qw/:sys_wait_h SIGHUP SIGTERM SIGKILL/;

use CGI qw(:cgi);
use HTTP::Request;
use HTTP::Status;

use Data::Dumper;

my @CHILD_PIDS = ();

BEGIN {
    # this is the worst damned warning ever, so SHUT UP ALREADY!
    $SIG{__WARN__} = sub { warn @_ unless $_[0] =~ /Use of uninitialized value/ };

    require Exporter;
    require ETVA::Agent::SOAP;
    use vars qw($VERSION @ISA @EXPORT @EXPORT_OK %EXPORT_TAGS $CRLF $AUTOLOAD);
    $VERSION = '0.0.1';
    @ISA = qw( ETVA::Agent::SOAP );
    @EXPORT = qw( );
}

my $DISPATCHER = "";

sub AUTOLOAD {
    my $method = $AUTOLOAD;
    my $self = shift;
    my %p = @_;

    plog "ETVA::Agent::SOAPFork method=$method" if( &debug_level > 5 );

    # implements _may_fork methods types
    #       drop _may_fork
    $method =~ s/_may_fork//;

    if( my ($request_class,$m1) = ($method =~ m/(.*)::(.+)/) ){
        if( $self->can($m1) ){
            # just call method as it is
            my $R = $self->$m1(%p);
            return $R;
        } else {
            # call from dispatcher
            my $dispatcher = ref($self) ? $self->{'_dispatcher'} : "";

            if( $dispatcher ){
                eval "require $dispatcher";
                if( !$@ ){
                    my $R;
                    eval {
                        $R = $dispatcher->$m1(%p);
                    };
                    if( $@ ){
                        die "error call method '$m1'.($@)\n";
                    }
                    return $R;
                } else {
                    die "error use dispatcher '$dispatcher'.\n";
                }
            } else {
                die "error no valid dispatcher.\n";
            }
        }
    } else {
        die "method $method not found\n";
    }
    return;
}

sub sigchld_handler {

    plog "ETVA::Agent::SOAPFork sigchld_handler..." if(&debug_level() > 5);

    # wait for die pid
    my $dead_pid = waitpid(-1,&WNOHANG);

    my @aux_CHILD_PIDS = grep { $_ != $dead_pid } @CHILD_PIDS;
    
    my $need_update = (scalar(@CHILD_PIDS) != scalar(@aux_CHILD_PIDS)) ? 1 : 0;
    
    @CHILD_PIDS = @aux_CHILD_PIDS;

    if( $need_update ){
        if( $DISPATCHER ){
            eval "require $DISPATCHER";
            if( !$@ ){
                eval {
                    $DISPATCHER->do_need_update();
                };
            }
        }
    }
}

sub terminate_agent {
    my $self = shift;

    plog "$self terminate_agent: You want me to stop, eh!?n" if(&debug_level);

    for my $cpid (@CHILD_PIDS){
        kill SIGHUP, $cpid;
        sleep(2);
        waitpid(-1,&WNOHANG);
    }
}

# rewrite new method
sub new {
    my $self = shift;
    my (%p) = @_;

    unless( ref($self) ){
        my $class = ref( $self ) || $self;

        # force dispatcher 
        $DISPATCHER = $p{'_dispatcher'} if( $p{'_dispatcher'} );

        # call both chld handlers
        my $chldhandler = $p{'_chldhandler_'};
        $p{'_chldhandler_'} = $chldhandler ? sub { &$chldhandler(); &sigchld_handler; }
                                    : \&sigchld_handler;

        $self = $self->SUPER::new( %p );

        $self = bless $self => $class;
    }
    return $self;
}

# testing if fork func
sub isForkable {
    my $self = shift;
    my ($method) = @_;

    # TODO maybe use other tests...
    return ( $method =~ m/_may_fork$/ ) ? 1 : 0;
}

sub disconnect { plog "ETVA::Agent::SOAPFork disconnect" if( &debug_level > 3 ); }
sub set_imchild { plog "ETVA::Agent::SOAPFork set_imchild" if( &debug_level > 3 ); }
sub set_imparent { plog "ETVA::Agent::SOAPFork set_imparent" if( &debug_level > 3 ); }

sub processdata {
    my $self = shift;
    my ($fh) = @_;

    my $bef_cur_mem = ETVA::Utils::process_mem_size($$);

    plog("processing data") if( &debug_level > 3 );

    # Get data
    $fh->blocking(0);
    my $request = $self->receive($fh);

    plog("processdata: $request") if( &debug_level > 3 );

    my ($headers,$body,$typeuri,$method,$rtype) = $self->parse_request( $request );

    my $runproc = 0;    # flag to run process

    # if method is forkable...
    my $isfork = $self->isForkable($method);
    if( $isfork ){

        $self->disconnect();

        $runproc = fork();
        if( defined($runproc) && ( $runproc == 0 ) ){
            $self->set_imchild();   # mark as child
        } else {
            $self->set_imparent();  # mark as parent
            plog "fork...\n" if(&debug_level > 5);
            push(@CHILD_PIDS,$runproc);  # put in queue
        }
    }

    if( $runproc == 0 ){    # always run process
        # treat soap request
        my $response = $self->treatRequest($request,$fh);

        if( $response ){    # send if have something to respond
            $fh->blocking(1);
            # send response
            $self->send($fh,$response);
        }

        if( $isfork ){
            # if fork exit at end
            POSIX::_exit(0);
        }
    }

    my $aft_cur_mem = ETVA::Utils::process_mem_size($$);

    my $diff_cur_mem = $aft_cur_mem - $bef_cur_mem;
    my $diff_cur_mem_ps = ETVA::Utils::prettysize($diff_cur_mem);
    my $aft_cur_mem_ps = ETVA::Utils::prettysize($aft_cur_mem);

    plog(sprintf('%s: MEMORY_LEAK ETVA::Agent::SOAPFork::processdata method=%s memory detect cur_mem=%s (diff=%s)',ETVA::Utils::nowStr(0),$method,$aft_cur_mem_ps,$diff_cur_mem_ps)) if(&debug_level > 9);
}

sub parse_request {
    my $self = shift;
    my ($request) = @_;

    my ($headers,$body,$typeuri,$method,$rtype);

    if( $self->isSOAP($request) ){
        $rtype = 'SOAP';
        ($headers,$body,$typeuri,$method) = ETVA::Agent::SOAP->parse_request( $request );
        $body = ETVA::Agent::SOAP::get_params($body);

    } else {

        eval {
            my $r = HTTP::Request->parse( $request );

            die "need POST request" if( uc($r->method()) ne 'POST' );
            
            $headers = $r->headers();
            $typeuri = $r->uri()->as_string();

            $method = $headers->header( 'Method' );
            if( !$method ){
                (undef,$method) = ($typeuri =~ m/\/([^\/]+\/)*(.+)$/);
            }

            # init body
            $body = {} if( !$body );

            my $content = $r->content();
            for my $a (split(/&/,$content)){
                my ($k,$v) = split(/=/,$a,2);
                if( defined($body->{"$k"}) ){
                    my $ov = ref($body->{"$k"}) ? $body->{"$k"} : [ $body->{"$k"} ];
                    $body->{"$k"} = [ @$ov, $v ];
                } else {
                    $body->{"$k"} = $v;
                }
            }
        };
        # TODO make this module
        #($headers,$body,$typeuri,$method) = ETVA::Agent::HTTP->parse_request( $request );
    }

    plog "headers=$headers, body=$body, typeuri=$typeuri method=$method" if(&debug_level > 5);

    return ($headers,$body,$typeuri,$method,$rtype);
}

sub make_response {
    my $self = shift;
    my ($rtype,$typeuri, $method, $res, @extra) = @_;

    if( $rtype eq 'SOAP' ){
        return $self->response_soap( $typeuri, $method, $res, @extra);
    } else {
        my $content = $res;
        $content = Dumper($res) if( ref($res) );
        return $self->http_response(RC_OK, undef, $content, @extra );
    }
}

sub make_response_fault {
    my $self = shift;
    my ($rtype,$typeuri,$faultcode,$faultstring,$detail) = @_;
    if( $rtype eq 'SOAP' ){
        return $self->response_soap_fault($typeuri,$faultcode,$faultstring,$detail);
    } else {
        # TODO optimize this
        my $content = join("\n",@_);
        return $self->http_response(RC_INTERNAL_SERVER_ERROR, undef, $content );
    }
}

sub http_response {
    my $self = shift;
    my ($status,$headers,$content,@extra) = @_;
    $headers = {} if( !$headers );

    my $response = "";
    $response .= header( -type=>"text/plain", -nph=>1, -status=>$status, %$headers, @extra );
    $response .= $content;
    plog "http_response = $response" if(&debug_level > 5);
    return $response;
}

sub treatRequest {
    my $self = shift;
    my ($request,$fh) = @_;

    my ($headers,$body,$typeuri,$method,$rtype) = $self->parse_request( $request );

    plog( &nowStr(), " [info] Receive call to treatRequest '$method'");

    if( $@ ){
plog "Failed while unmarshaling the request: $@" if( &debug_level );
        return $self->make_response_fault( $rtype,$typeuri, "Server",
                                                'Application Faulted',
                                                "Failed while unmarshaling the request: $@");
    }

    my %params = %$body;

    # share with method client socket
    $params{'_socket'} = $fh;

    # handlers to write response
    $params{'_make_response'} = sub { my ($res,@extra) = @_; return $self->make_response($rtype,$typeuri,$method, $res, @extra); };
    $params{'_make_response_fault'} = sub { my ($code,$string,$detail) = @_; return $self->make_response_fault($rtype,$typeuri,$code,$string,$detail); };

    my $response;

plog  "paramas Dump=",Dumper(\%params),"\n" if( &debug_level > 3 );
    eval {
        my $res = $self->$method(%params);
        if( defined $res ){ # response only if return somethin
            #$res = {} if( not defined $res );
plog  "result Dumper=",Dumper($res),"\n" if( &debug_level > 3 );
            if( ref($res) eq 'HASH' && $res->{'_error_'} ){
                $response = $self->make_response_fault( $rtype,$typeuri,$res->{'_errorcode_'},
                                                        $res->{'_errorstring_'},
                                                        $res->{'_errordetail_'});
            } else {
                $response = $self->make_response($rtype,$typeuri, $method, $res);
            }
        }
    };
    if( $@ ){
        return $self->make_response_fault( $rtype, $typeuri, "Server",
                                            'Application Faulted',
                                            "An exception fired while processing the request: $@");
    }

    if( my ($faultcode,$faultstring,$detail) = ETVA::Agent::SOAP::is_error($response) ){
        return $self->make_response_fault( $rtype,$typeuri,$faultcode,$faultstring,$detail);
    }

    return $response;
}

sub reinitialize {
    my $self = shift;
    plog "register ... restarting for register..." if(&debug_level);
    $self->set_runout();
    return retOk("_OK_","ok");
}

sub get_number_childs {
    return int(@CHILD_PIDS);
}

1;
