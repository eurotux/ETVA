#!/usr/bin/perl

use strict;

use ETVA::Utils;
use ETVA::SOAP;

use IO::Select;
use IO::Socket;

use IO::Handle;

use LWP::UserAgent;
use HTTP::Request;

use POSIX;

#use JSON;

use Data::Dumper;

my $dir_guest_management_sockets = "/var/tmp/virtagent-guestmngt-sockets-dir";

my %handled_sockets = ();

# Invok each T_ALARM seconds
# Alarm ttl
my $T_ALARM = 5 * 60;

my $RUNNING;
my $EXITSIGNAL;
my $_alarmhandler_;
my $lasttime_alarm = time();

my %clients;			# client socket descriptor hash

my %guests;				# guest socket descriptor hash

my $queueClients;       # queue for handle clients

# Invoked when the client's socket becomes readable

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
    my $fh = shift;

    my $data = '';
    while(<$fh>){ $data .= $_; };

    return $data;
}

sub receiveGA {
    my ($fh) = @_;

    &nonblock($fh);

    my $ready = 0;              # all data received
    my $data = "";              #  data
    my $rd = "";                # read result
    my $part;                   # partial read

    while (!$ready && ($rd = read($fh,$part,4096)) >= 0) {  # read data from socket
        if (defined $rd) {      # undef means failure
            if( $rd > 0 ){
                $data .= $part;  # join parts
                #plogNow("receive data=$data");
                $ready = &isSOAPValid($data);
            }
        }
    }
    return $data;
}

sub sendGA {
    my ($sock,$dmp) = @_;

    $sock->autoflush(1);
    send($sock,$dmp,0);
    #$sock->send($dmp);
    #print $sock $dmp;
    $sock->flush();
}

sub send {
    my ($fh,$data) = @_;
    
    $fh->send($data);
}

sub getMASocket {
    my (%p) = @_;
    if( $p{'server_name'} ){
        my $fsock = "$p{'server_name'}-guestmngr";
        my $fsock_path = "${dir_guest_management_sockets}/$fsock";
        if( -e "$fsock_path" ){
            if( my $masock = $handled_sockets{"$fsock"} ){
                return wantarray() ? ($masock,$fsock) : $masock;
            }
        }
    }
    return;
}

sub parseVAMessage {
    my ($message) = @_;
    my $R = {};
    my ($headers,$body,$typeuri,$method) = &parse_request( $message );
    if( !$@ ){
        $R->{'message'} = $message;
        $R->{'headers'} = $headers;
        $R->{'body'} = $body;
        $R->{'typeuri'} = $typeuri;
        $R->{'method'} = $method;
        ($R->{'masock'},$R->{'fsock'}) = &getMASocket(%$body);
        #plogNow("parseVAMessage ",Dumper($R));
    }
    return wantarray() ? %$R : $R;
}

sub checkData {
    my ($request) = @_;

    return -1 if( $request !~ m/<\?xml/gsi );
    return -1 if( $request !~ m/<\/\S+Envelope>/gsi );

    return &isSOAPValid($request);
}

sub _handleRequestGA {
    my ($sock, $message ) = @_;
    # TODO check is valid message
    my $ua = new LWP::UserAgent();
    my $request = new HTTP::Request( 'POST' => 'http://10.10.4.9/soapapi.php' );

    plogNow("DEBUG _handleRequestGA message=$message");

    $request->content($message);
    my $response = $ua->request( $request );
    if( $response->is_success() ){
        my $res = $response->content();
        plogNow("DEBUG _handleRequestGA res=$res");
        &sendGA($sock, $res);
    } else {
        plogNow("DEBUG _handleRequestGA error=",Dumper($response));
        my $typeuri = 'urn:soapController';
        my $res = &response_soap_fault( $typeuri, "Server",
                                            'Application Faulted',
                                            $response->content());
        plogNow("DEBUG _handleRequestGA res=$res");
        &sendGA($sock, $res);
    }
}

sub waitForResponseGA {
    my ($masock) = @_;
    my $responseGA;
    while(!$responseGA){
        my $message = &receiveGA($masock);
        plogNow("DEBUG waitForResponseGA message=$message");
        if( $message =~ m/<\S+Response/gsi ){   # if is a Response then foward to VA
            $responseGA = $message;
        } else {
            &_handleRequestGA($masock,$message);
        }
    }
    return $responseGA;
}

sub treatRequest {
    my ($client,$request) = @_;

    plogNow("treatRequest request=$request");
    # enqueue to handle by work
    # $queueClients->enqueue({'client'=>$client,'request'=>$request});

    my $R = &parseVAMessage($request);
    my $masock = $R->{'masock'};
    my $mamsg = $R->{'message'};
    if( $masock && $mamsg ){
        plogNow "receive send = $mamsg";
        &sendGA($masock, $mamsg);
        plogNow "receive wait for receive";
        my $response = &waitForResponseGA($masock);
        plogNow "receive message = $response";
        &send($client,$response);
    }
}

sub treatRequestGA {
    my ($guest,$message) = @_;

    plogNow("treatRequestGA message=$message");

=com
    my $ua = new LWP::UserAgent();
    my $request = new HTTP::Request( 'POST' => 'https://10.10.4.225/soapapi.php' );

    $request->content($message);
    my $response = $ua->request( $request );
    if( $response->is_success() ){
        my $res = $response->content();
        &send($guest,$res);
    }
=cut
}

my $select_timeout = 0.020; # value for select timeout reads

my $SelectVA;   # Select IO for VA messages
my $SelectGA;   # Select IO for GA messages

sub mainLoop {
    my (%p) = @_;
	
	$RUNNING = 1;
	
    my $laddr = $p{'LocalAddr'};
    my $lport = $p{'LocalPort'} = $p{'LocalPort'} || 7000;
    my $proto = $p{'Proto'} = $p{'Proto'} || 'tcp';

	my $server = new IO::Socket::INET( Listen => 1,
                                        LocalAddr => $laddr,
                                        LocalPort => $lport,
                                        Proto => $proto,
                                        ReuseAddr => 1
                                        );
    if( $server ){
        # register agent
        #&register();

        $server->sockopt(SO_REUSEADDR,1) or die("can't sockopt!");
        
        $SelectVA = new IO::Select( $server ) if( !$SelectVA );
        
        #plogNow("activate alarm"); 
        #alarm($T_ALARM);

        my %inbuffer  = ();					# input buffers
        my %inbufferGA  = ();					# input buffers

        # run
        plog("Running...");
        while($RUNNING){
            my $rv;
            my $data;

            my @ready_va = $SelectVA->can_read($select_timeout);
            foreach my $client (@ready_va) {
                if($client == $server) {
                    # Create a new socket
                    plogNow("accept"); 
                    my $new = $server->accept();
                    # Set REUSEADDR flag
                    $new->sockopt(SO_REUSEADDR,1) or die("can't sockop!");
                    $SelectVA->add($new);
                } else {

                    # read data
                    $data = '';
                    $rv   = $client->recv($data, POSIX::BUFSIZ, 0);

                    $inbuffer{$client} .= $data;	# add to buffer
                    my $status = &checkData($inbuffer{$client});	# check completion/integrity
                    if ($status < 0) {				# invalid data
                        delete $inbuffer{$client};
                        $SelectVA->remove($client);
                        delete $clients{$client};
                        close $client;
                        next;
                    } elsif ($status > 0) {			# valid and complete data
                        my $udata = $inbuffer{$client};
                        plogNow("DEBUG receive udata=$udata");
                        delete $inbuffer{$client};
                        $SelectVA->remove($client);
                        &treatRequest($client,$udata);

                        delete $clients{$client};
                        #close $client;
                    } else { # status == 0 means incomplete, continue in select
                        # unless we are at the end-of-file
                        unless (defined($rv) && length $data) {
                            # This would be the end of file, so close the client
                            delete $inbuffer{$client};
                            $SelectVA->remove($client);
                            delete $clients{$client};
                            close $client;
                        }
                    }
                }
            }

            #&check_past_alarm_events();

            &_idle_();

            # Sleep
            sleep(1);
        }
        # close socket
        $server->close();

        #&terminate_agent();
	} else {
        plogNow("Can't create the socket");
    }

    plogNow("MainLoop exit RUNNING=$RUNNING");
    return $EXITSIGNAL;
}

sub _idle_ {
    &getGASockets();
}

sub open_io_socket {
    my $path = shift;
    my $my_addr = sockaddr_un($path);

    my $sock;
    socket($sock,PF_UNIX,SOCK_STREAM,0);

    my $s = connect($sock, $my_addr);

    return $sock;

=com
    return new IO::Socket::UNIX (
            Peer     => $path,
            Type     => SOCK_STREAM,
            Blocking    => 0
        );
=cut
}

sub getGASockets {
    my $dirsockets;

    $SelectGA = new IO::Select( ) if( !$SelectGA );

    opendir($dirsockets,"$dir_guest_management_sockets");
    my @list_sockets = readdir($dirsockets);
    foreach my $fsock (@list_sockets){
        my $fp_sock = "$dir_guest_management_sockets/$fsock";
        if( (-S "$fp_sock") && !$handled_sockets{"$fsock"} ){
            plog "new socket $fsock";
            my $unix_socket = &open_io_socket($fp_sock);
            $handled_sockets{"$fsock"} = $unix_socket;
            $SelectGA->add($unix_socket);
        }
    }
    close($dirsockets);
}

sub _handle_client {
    # Thread will loop until no more work
    while (defined(my $item = $queueClients->dequeue())) {
        # Do work on $item
    }
}
sub main {

    &getGASockets();

    # A new empty queue
    #$queueClients = Thread::Queue->new() if( !$queueClients );

    # Worker thread
    #my $thr = threads->create( \&_handle_client );

    &mainLoop( 
        LocalAddr   => '127.0.0.1',
        LocalPort   => 9009,
        Proto       => 'tcp'
    );

    # Signal that there is no more work to be sent
    #$queueClients->end();
    # Join up with the thread when it finishes
    #$thr->join();
}

&main();

1;
