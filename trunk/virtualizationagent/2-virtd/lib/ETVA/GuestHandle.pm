#!/usr/bin/perl

package ETVA::GuestHandle;

use strict;

use ETVA::Utils;
use ETVA::SOAP;

use IO::Socket;
use IO::Select;

use POSIX;

use LWP::UserAgent;
use HTTP::Request;

use Data::Dumper;

my %handled_sockets = ();

# default select timeout reads
my $select_timeout = 0.020; # value for select timeout reads

# Invok each T_ALARM seconds
# Alarm ttl
my $T_ALARM = 5 * 60;

my $RUNNING;
my $EXITSIGNAL;
my $_alarmhandler_;
my $lasttime_alarm = time();

my %clients;			# client socket descriptor hash

my %inbufferVA  = ();   # input buffers for VA clients

my %queue_wait_response_ma = ();    # queue for waitting responses from MA

#my %queue_read_va = ();    # queue for the messages receive from VA (not used)
my %queue_write_va = ();    # queue for the messages to send to VA
my %queue_read_ma = ();     # queue for the messages receive from MA and need to process
my %queue_write_ma = ();    # queue for the messages to send to MA

my $ttl_messages_ids = 3*60;    # ttl of messages ids
my %va_messages_ids = ();   # messages ids

my %ignore_guest_sockets = ();  # ignore guest sockets
my $ttl_ignore_guest = 30*60;   # ttl that guest will be ignore

my $ttl_open_socket = 3;   # ttl wait for to open socket

my $min_request_data = length('<?xml');     # min size of request to validate data

# new method
sub new {
    my $self = shift;
    
    unless( ref $self ){
        my $class = ref( $self ) || $self;

        my %params = @_;
        
        $self = bless {%params} => $class;

        $self->{'LocalPort'} = $self->{'LocalPort'} || $self->{'Port'};

        $self->{'LocalAddr'} ||=  '127.0.0.1';
        $self->{'LocalPort'} ||= 9000;
        $self->{'Proto'} ||= 'tcp';

        # create socket INET
        $self->{'server'} = new IO::Socket::INET( Listen => 1,
                                                    LocalAddr => $self->{'LocalAddr'},
                                                    LocalPort => $self->{'LocalPort'},
                                                    Proto => $self->{'Proto'},
                                                    ReuseAddr => 1
                                            );
    
        if( !$self->{'server'} ){
            plogNow("[ERROR] ETVA::GuestHandle: Can't create the socket: $!");
            die("Can't create the socket: $!");
        }

        # IO select for virt agent
        $self->{'selectVA'} = new IO::Select( $self->{'server'} );

        # IO select for management agent
        $self->{'selectMA'} = new IO::Select( );

        # initialize id
        $self->{'_id'} = int(rand(time()));
    }
	
	return $self;
}

# loop to receive messages
sub mainLoop {
    my $self = shift;
    my (%p) = @_;
	
	$RUNNING = 1;
	
    $SIG{'HUP'} = $SIG{'TERM'} = \&term_handler;

    if( $self->{'server'} ){

        $self->{'server'}->sockopt(SO_REUSEADDR,1) or die("can't sockopt!");
        
        # run
        plogNow("[INFO] ETVA::GuestHandle mainLoop: Running...");
        while( $RUNNING ){
            my $rv;
            my $data;

            # read from virt agent
            $self->_read_va();

            # write to management guest agent
            $self->_write_ma();

            # read from management guest agent
            $self->_read_ma();

            # write to virt agent
            $self->_write_va();

            # _idle_
            $self->_idle_();

            # Sleep
            #sleep(1);
        }

        $self->terminate_agent();

	} else {
        plogNow("[ERROR] ETVA::GuestHandle mainLoop: Can't create the socket");
    }

    plogNow("ETVA::GuestHandle mainLoop exit RUNNING=$RUNNING");
    return $EXITSIGNAL;
}

sub terminate_agent {
    my $self = shift;

    # closing clients sockets
    foreach my $client (values %clients) {
        close($client);
    }

    # close server
    $self->close();
}
sub close {
    my $self = shift;

    # close socket
    $self->{'server'}->close();
}

# read messages from virtagent
sub _read_va {
    my $self = shift;

    my $rv;
    my $data;

    my @ready_va = $self->{'selectVA'}->can_read($select_timeout);
    foreach my $client (@ready_va) {
        plogNow("[DEBUG] read_va client=$client") if( &debug_level > 9 );

        if( $client == $self->{'server'} ){

            # accept new socket connection

            plogNow("[INFO] ETVA::GuestHandle mainLoop: accept") if( &debug_level > 3 ); 

            my $new = $self->{'server'}->accept();
            # Set REUSEADDR flag
            $new->sockopt(SO_REUSEADDR,1) or die("can't sockop!");
            $self->{'selectVA'}->add($new);

            $clients{"$new"} = $new;

        } else {

            $data = '';
            # receive data
            $rv   = $client->recv($data, POSIX::BUFSIZ, 0);

            $inbufferVA{$client} .= $data;	                # add to buffer
            my $status = &checkData($inbufferVA{$client});	# check completion/integrity

            if( $status > 0 ){			# valid and complete data
                my $udata = $inbufferVA{$client};

                plogNow("ETVA::GuestHandle mainLoop: receive treatRequest request=$udata") if( &debug_level > 9 );

                # clean buffer
                delete $inbufferVA{$client};

                # treat Request
                $self->treatRequest($client,$udata);
            }
        }
    }
}

# read messages from management agent
sub _read_ma {
    my $self = shift;

    # process messages on queue
    foreach my $sock ( keys %queue_read_ma ){
        my $qread_ma = $queue_read_ma{"$sock"};
        my @aux = ();
        foreach my $R (@$qread_ma){
            my $id = $R->{'id'};
            plogNow("[DEBUG] selectMA process old msgs id=$id ") if( &debug_level > 9 );
            if( my $W = delete($queue_wait_response_ma{"$id"}) ){
                push(@{$queue_write_va{"$W->{'vasock'}"}}, $R->{'message'});
            } else {
                push(@aux, $R);
            }
        }
        $queue_read_ma{"$sock"} = [ @aux ];
    }

    my @ready_ma = $self->{'selectMA'}->can_read($select_timeout);
    foreach my $sock (@ready_ma) {
        plogNow("[DEBUG] selectMA read sock=$sock") if( &debug_level > 9 );

        $ignore_guest_sockets{"$sock"} = time();  # reset ignore time

        if( my $message = &receiveGA($sock) ){

            if( &isSoapResponseOrFault($message) ){
                # if is a Response for request then enqueue to forward to VA

                # parsing response
                my ($H,$R,undef,undef,$id) = &parse_soap_response($message);

                plogNow("[DEBUG] selectMA read sock=$sock id=$id ") if( &debug_level > 9 );

                # if as wait for response
                if( my $W = delete($queue_wait_response_ma{"$id"}) ){
                    plogNow("[DEBUG] selectMA read sock=$sock and send to $W->{'vasock'} ") if( &debug_level > 9 );
                    push(@{$queue_write_va{"$W->{'vasock'}"}}, $message);
                } else {
                    # else enqueue
                    push(@{$queue_read_ma{"$sock"}}, { %$R, 'message'=>$message, 'id'=>$id });
                }
            } else {
                # else is a Request forward to CM
                $self->_handleRequestGA($sock,$message);
            }
        }
    }
}

# send messages to virtagent
sub _write_va {
    my $self = shift;
    my $rv;
    my $data;

    my @ready_va = $self->{'selectVA'}->can_write($select_timeout);
    foreach my $client (@ready_va) {
        plogNow("[DEBUG] selectVA write client=$client") if( &debug_level > 9 );
        if( $queue_write_va{"$client"} ){
            my $msg = shift(@{$queue_write_va{"$client"}});
            if( $msg ){
                &send($client,$msg);

                # send response remove from IO select
                $self->{'selectVA'}->remove($client);
                delete $clients{$client};
                close $client;
            }
        }
    }
}

# send messages to management agent
sub _write_ma {
    my $self = shift;
    my $rv;
    my $data;

    my @ready_ma = $self->{'selectMA'}->can_write($select_timeout);
    foreach my $sock (@ready_ma) {
        if( $queue_write_ma{"$sock"} ){
            my $msg = shift(@{$queue_write_ma{"$sock"}});
            if( $msg ){
                &sendGA($sock,$msg);
            }
        }
    }
}

# _idle_
sub _idle_ {
    my $self = shift;
    $self->checkGASockets();

    foreach my $id (keys %queue_wait_response_ma){
        if( $va_messages_ids{"$id"} < time() ){
            my $W = delete($queue_wait_response_ma{"$id"});
            my $client = $W->{'vasock'};

            $ignore_guest_sockets{"$W->{masock}"} = time()+$ttl_ignore_guest;    # ignore for ttl

            plogNow("[WARN] _idle_ message id '$id' with client '$client' expires");

            # send fault message back
            my $typeuri = $self->{'typeuri'};
            my $msg_fault = &response_soap_fault( $typeuri, __PACKAGE__,
                                                'ERR_REQUEST_TIMEOUT',
                                                "Request timeout.");
        
            # send to client
            push(@{$queue_write_va{"$client"}}, $msg_fault);
        }
    }
}

# open guest socket
sub open_io_socket {
    my $path = shift;
    my $my_addr = sockaddr_un($path);

    my $sock;
    if( socket($sock,PF_UNIX,SOCK_STREAM,0) ){
        if( my $s = connect($sock, $my_addr) ){
            return $sock;
        }
    }

    return;
=com
    return new IO::Socket::UNIX (
            Peer     => $path,
            Type     => SOCK_STREAM,
            Blocking    => 0
        );
=cut
}

# check if have new guest socket file
sub checkGASockets {
    my $self = shift;
    my $dirsockets;

    opendir($dirsockets,"$self->{'sockets_dir'}");
    my @list_sockets = readdir($dirsockets);
    foreach my $fsock (@list_sockets){
        my $fp_sock = "$self->{'sockets_dir'}/$fsock";
        #if( (-S "$fp_sock") && !$handled_sockets{"$fsock"} ){
        if( -S "$fp_sock" ){
            my $mtime_fpsocket = (stat("$fp_sock"))[9];   # modification time of file path socket
            if( !$handled_sockets{"$fsock"} || ($handled_sockets{"$fsock"}{'mtime'} < $mtime_fpsocket) ){
                if( $handled_sockets{"$fsock"}{'mtime'} < $mtime_fpsocket ){
                    close($handled_sockets{"$fsock"}{'socket'});    # close old socket
                }
                if( my $unix_socket = ETVA::Utils::timeout_call($ttl_open_socket,\&open_io_socket,$fp_sock) ){
                    plogNow("[INFO] ETVA::GuestHandle checkGASockets (re)new guest socket '$fsock'.");
                    $handled_sockets{"$fsock"}{'socket'} = $unix_socket;
                    $handled_sockets{"$fsock"}{'mtime'} = $mtime_fpsocket;
                    
                    $self->{'selectMA'}->add($unix_socket);
                } else {
                    plogNow("[WARN] ETVA::GuestHandle checkGASockets couldn't open new guest socket '$fsock'.") if( &debug_level > 3 );
                }
            }
        }
    }
    close($dirsockets);
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

# receive form guest agent socket
my %inbufferGA = ();
sub receiveGA {
    my ($fh) = @_;

    &nonblock($fh);

    my $ready = 0;              # all data received
    my $data = "";              #  data
    my $rd = "";                # read result
    my $part;                   # partial read

    my $rv   = read($fh,$data, 4096, 0);
    $inbufferGA{$fh} .= $data;                  # add to buffer

    my $status = &checkData($inbufferGA{$fh});    # check completion/integrity

    plogNow("[DEBUG] receiveGA rd=$rv part=$data status=$status err=$@") if( &debug_level > 9 );

    if( $status > 0 ){          # valid and complete data
        my $udata = $inbufferGA{$fh};

        # clean buffer
        delete $inbufferGA{$fh};
        return $udata;
    } elsif( $status < 0 ){
        delete $inbufferGA{$fh};
    }
    return;
}

# send by guest agent socket
sub sendGA {
    my ($sock,$dmp) = @_;

    $sock->autoflush(1);
    send($sock,$dmp,0);
    $sock->flush();
}

# send by client socket
sub send {
    my ($fh,$data) = @_;
    
    $fh->send($data);
}

# get socket of management agent by server name
sub getMASocket {
    my $self = shift;
    my (%p) = @_;
    if( $p{'server_name'} ){
        my $fsock = "$p{'server_name'}";
        my $fsock_path = "$self->{'sockets_dir'}/$fsock";
        if( -e "$fsock_path" ){
            if( my $masock = $handled_sockets{"$fsock"}{'socket'} ){
                return wantarray() ? ($masock,$fsock) : $masock;
            }
        }
    }
    return;
}

# parsing virt agent message
sub parseVAMessage {
    my $self = shift;
    my ($message) = @_;
    my $R = {};
    my ($headers,$body,$typeuri,$method) = &parse_soap_request( $message );
    if( !$@ ){
        $R->{'message'} = $message;
        $R->{'headers'} = $headers;
        $R->{'body'} = $body;
        $R->{'typeuri'} = $typeuri;
        $R->{'method'} = $method;
        ($R->{'masock'},$R->{'fsock'}) = $self->getMASocket(%$body);
 
        # reset ttl when force call
        $ignore_guest_sockets{"$R->{masock}"} = time() if( $body->{'force_call'} );

        # ignore message
        $R->{'ignore'} = ($ignore_guest_sockets{"$R->{masock}"} > time()) ? 1 : 0;

        if( !$R->{'ignore'} ){

            # generate id
            my $id = $R->{'id'} = ++$self->{'_id'};
            $va_messages_ids{"$id"} = time()+$ttl_messages_ids;     # ttl of message id

            # change request to send msg id
            $R->{'message'} = $message = &soap_request($typeuri, $method, %$body, 'header'=>{'soap_msg_id'=>$id} );
        }
    }
    return wantarray() ? %$R : $R;
}

# validate data
sub checkData {
    my ($request) = @_;

    return 0 if( length($request) < $min_request_data );

    return -1 if( split(/<\?xml/,$request,3) == 1 );

    return 0 if( $request !~ m/<\?xml/gsi );
    return 0 if( $request !~ m/<\/\S+Envelope>/gsi );

    return &isSOAPValid($request);
}

# handle request and foward to CM 
sub _handleRequestGA {
    my $self = shift;
    my ($sock, $message ) = @_;
    # TODO check is valid message
    my $ua = new LWP::UserAgent();
    my $request = new HTTP::Request( 'POST' => $self->{'cm_uri'} );

    plogNow(" _handleRequestGA message=$message ") if( &debug_level > 9 );

    $request->content($message);
    my $response = $ua->request( $request );

    my $res;

    if( $response->is_success() ){
        $res = $response->content();
    } else {
        my $typeuri = $self->{'cm_namespace'};
        $res = &response_soap_fault( $typeuri, __PACKAGE__,
                                            'ERR_REQUEST_CM',
                                            $response->content());
    }
    push(@{$queue_write_ma{"$sock"}}, $res);
}

# treat client request
sub treatRequest {
    my $self = shift;
    my ($client,$request) = @_;

    # parse VA new Message
    my $R = $self->parseVAMessage($request);
    my $masock = $R->{'masock'};
    my $mamsg = $R->{'message'};

    if( !$R->{'ignore'} && $masock && $mamsg ){
        # send request
        push(@{$queue_write_ma{"$masock"}}, $mamsg);

        # wait for response
        $queue_wait_response_ma{"$R->{'id'}"} = { %$R, 'vasock'=>$client };
    } else {
        # send fault message back
        my $typeuri = $self->{'typeuri'};
        my $msg_fault = &response_soap_fault( $typeuri, __PACKAGE__,
                                            'ERR_INVALID_SERVER_MESSAGE',
                                            "Invalid message or request server.");
        if( $R->{'ignore'} ){
            $msg_fault = &response_soap_fault( $typeuri, __PACKAGE__,
                                            'ERR_MESSAGE_SERVER_IGNORED',
                                            "The message should be ignored.");
            plogNow("[WARN] treatRequest message from client '$client' to '$R->{fsock}' will be ignored.");
        } else {
            plogNow("[WARN] treatRequest message id '$R->{id}' from client '$client' with invalid MA socket or message.");
        }

        # send to client
        push(@{$queue_write_va{"$client"}}, $msg_fault);
    }
}

sub term_handler {
    $RUNNING = 0;
    plogNow("[INFO] ETVA::GuestHandle term_handler");
}

1;
