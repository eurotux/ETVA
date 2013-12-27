#!/usr/bin/perl

package ETVA::GuestAgent::SOAP::Fork;

use strict;

BEGIN {
    # this is the worst damned warning ever, so SHUT UP ALREADY!
    $SIG{__WARN__} = sub { warn @_ unless $_[0] =~ /Use of uninitialized value/ };

    require Exporter;
    require ETVA::GuestAgent::SOAP;
    use vars qw($VERSION @ISA @EXPORT @EXPORT_OK %EXPORT_TAGS $CRLF $AUTOLOAD);
    $VERSION = '0.0.1';
    @ISA = qw( ETVA::GuestAgent::SOAP );
    @EXPORT = qw(  );
}

use ETVA::Utils;

use ETVA::SOAP;

use POSIX;

use CGI qw(:cgi);
use HTTP::Request;
use HTTP::Status;

use Data::Dumper;

my @CHILD_PIDS = ();        # child processes

sub handlerequest {
    my $self = shift;
    my ($client,$request) = @_;

    my $bef_cur_mem = ETVA::Utils::process_mem_size($$);

    plogNow(__PACKAGE__," handlerequest  client=$client request=$request") if(&debug_level > 9 );

    my ($headers,$body,$typeuri,$method,$id,$rtype) = $self->parse_request( $request );

    my $runproc = 0;    # flag to run process

    # if method is forkable...
    my $isfork = $self->isForkable($method);
    if( $isfork ){

        $self->disconnect();

        $runproc = fork();
        if( defined($runproc) && ( $runproc == 0 ) ){
            $self->set_imchild();   # mark as child

            # rename process
            $0 .= " ($method)";

        } else {
            $self->set_imparent();  # mark as parent
            plogNow "fork... pid=$runproc \n" if(&debug_level > 5);
            push(@CHILD_PIDS,$runproc);  # put in queue
        }
    }

    my $response;

    if( $runproc == 0 ){    # always run process

        # treat soap request
        $response = $self->exec_request($client, $headers,$body,$typeuri,$method, {'soap_msg_id'=> $id}, $rtype);

        plogNow(__PACKAGE__," handleresponse  client=$client response=$response") if( &debug_level > 9 );

        if( $isfork ){
            if( $response ){    # send if have something to respond
                # send response
                $self->send($client,$response);
            }
            # if fork exit at end
            POSIX::_exit(0);
        }

    } else {
        $self->endclient($client) if( $self->can('endclient') );
    }

    my $aft_cur_mem = ETVA::Utils::process_mem_size($$);

    my $diff_cur_mem = $aft_cur_mem - $bef_cur_mem;
    my $diff_cur_mem_ps = ETVA::Utils::prettysize($diff_cur_mem);
    my $aft_cur_mem_ps = ETVA::Utils::prettysize($aft_cur_mem);

    plog(sprintf('%s: MEMORY_LEAK ETVA::GuestAgent::SOAP::Fork::processdata method=%s memory detect cur_mem=%s (diff=%s)',ETVA::Utils::nowStr(0),$method,$aft_cur_mem_ps,$diff_cur_mem_ps)) if(&debug_level > 9);

    return $response;
}

sub parse_request {
    my $self = shift;
    my ($request) = @_;

    my ($headers,$body,$typeuri,$method,$id,$rtype);

    if( ETVA::SOAP::isSOAP($request) ){
        $rtype = 'SOAP';
        ($headers,$body,$typeuri,$method,$id) = $self->SUPER::parse_request( $request );
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

    return ($headers,$body,$typeuri,$method,$id,$rtype);
}

sub make_response {
    my $self = shift;
    my ($typeuri, $method, $res, $header, $rtype, @extra) = @_;

    if( $rtype eq 'SOAP' ){
        return $self->SUPER::make_response( $typeuri, $method, $res, $header, @extra);
    } else {
        my $content = $res;
        $content = Dumper($res) if( ref($res) );
        return $self->http_response(RC_OK, undef, $content, @extra );
    }
}

sub make_response_fault {
    my $self = shift;
    my ($typeuri,$faultcode,$faultstring,$detail,$header, $rtype, @extra) = @_;
    if( $rtype eq 'SOAP' ){
        return $self->SUPER::make_response_fault($typeuri,$faultcode,$faultstring,$detail,$header,@extra);
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
    plogNow __PACKAGE__," http_response = $response" if(&debug_level > 5);
    return $response;
}
# testing if fork func
sub isForkable {
    my $self = shift;
    my ($method) = @_;

    plogNow(__PACKAGE__," isForkable method=$method") if( &debug_level > 3 );

    if( $self->{'_dispatcher'}->can('isForkable') ){
        return $self->{'_dispatcher'}->isForkable($method);
    }

    # TODO maybe use other tests...
    return ( $method =~ m/_may_fork$/ ) ? 1 : 0;
}

sub disconnect { plogNow __PACKAGE__," disconnect" if( &debug_level > 3 ); }
sub set_imchild { plogNow __PACKAGE__," set_imchild" if( &debug_level > 3 ); }
sub set_imparent { plogNow __PACKAGE__," set_imparent" if( &debug_level > 3 ); }

sub chld_exists {
    my ($dead_pid) = @_;
    return grep { $_ == $dead_pid } @CHILD_PIDS;
}

sub chld_dies_handler {
    my $self = shift;
    my ($dead_pid) = @_;

    plogNow(__PACKAGE__," chld_dies_handler dead pid=$dead_pid") if(&debug_level() > 5);

    my @aux_CHILD_PIDS = grep { $_ != $dead_pid } @CHILD_PIDS;
    
    my $need_update = (scalar(@CHILD_PIDS) != scalar(@aux_CHILD_PIDS)) ? 1 : 0;
    
    @CHILD_PIDS = @aux_CHILD_PIDS;

    return $need_update;
}

sub terminate_agent {
    my $self = shift;

    plogNow __PACKAGE__," terminate_agent: You want me to stop, eh!?";

    ETVA::Utils::timeout_call(30, sub { 
	    for my $cpid (@CHILD_PIDS){
            plog "$self killing pid $cpid... ";
            kill SIGHUP, $cpid;
            sleep(2);
            if( waitpid(-1,&WNOHANG)  < 0 ){    # wait until timed out or no more pids
                plog "$self no more pids to kill...";
                last;
            }
            plog "$self killing pid $cpid... done";
	    }
	} );    # wait until timed out 

}

sub term_handler {
    my $self = shift;

    $self->terminate_agent();

    $self->SUPER::term_handler();
}

1;

