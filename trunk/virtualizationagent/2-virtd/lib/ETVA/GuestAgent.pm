package ETVA::GuestAgent;

use strict;

use ETVA::Utils;

use IO::Select;
use POSIX qw(:signal_h :errno_h :sys_wait_h);

use Time::HiRes qw(gettimeofday);

my $RUNNING;

my $EXITSIGNAL;

# default select timeout reads
my $select_timeout = 0.020; # value for select timeout reads

my %inbuffer  = ();   # input buffers for clients

my %queue_wait_response = ();    # queue for waitting responses from MA

my %queue_read = ();     # queue for the messages receive and need to process
my %queue_write = ();    # queue for the messages to send

my $T_ALARM = 5 * 60;   # ttl alarm
my $_alarmhandler_;     # alarm handler
my $lasttime_alarm;     # last time for alarm

# new method
sub new {
    my $self = shift;
    unless( ref $self ){
        my $class = ref( $self ) || $self;

        my %params = @_;
        
        $self = bless {%params} => $class;

        $self->{'select_timeout'} = $select_timeout if( !$self->{'select_timeout'} );

        # IO select for server
        $self->{'select'} = new IO::Select( );

        $T_ALARM = $self->{'T_ALARM'} if( $self->{'T_ALARM'} );

        # define signal handlers
        $SIG{'TERM'} = $params{'_termhandler_'} ? 
                                            sub { $params{'_termhandler_'}->(); $self->term_handler(); }
                                            : sub { $self->term_handler(); };
        $SIG{'INT'} = $params{'_inthandler_'} ? 
                                            sub { $params{'_inthandler_'}->(); $self->int_handler(); }
                                            : sub { $self->int_handler(); };
        $SIG{'HUP'} = $params{'_huphandler_'} ? 
                                            sub { $params{'_huphandler_'}->(); $self->hup_handler(); }
                                            : sub { $self->hup_handler(); };
        $SIG{'CHLD'} = $params{'_chldhandler_'} ? 
                                            sub { $params{'_chldhandler_'}->(); $self->chld_handler(); }
                                            : sub { $self->chld_handler(); };

        plogNow(__PACKAGE__," new PACKAGE=",__PACKAGE__," self=$self");

        $SIG{'USR1'} = \&usr1_handler;
        $SIG{'USR2'} = \&usr2_handler;
        $_alarmhandler_ = $SIG{'ALRM'} = $params{'_alarmhandler_'} ? 
                                            sub { $params{'_alarmhandler_'}->(); &alarm_handler; }
                                            : \&alarm_handler;
    }
	
	return $self;
}

# register handle
sub register {
    my $self = shift;

    if( $self->{'_register_handler'} ){
        return $self->{'registered'} = $self->{'_register_handler'}->($self);
    }
    return $self->{'registered'} = 1;
}

# loop to receive messages
sub mainLoop {
    my $self = shift;
    my (%p) = @_;
	
    # register func handlers
    if( $self->register() ){

        $RUNNING = 1;
        
        $lasttime_alarm = time();

        # run
        plogNow(__PACKAGE__," [INFO] mainLoop: Running...");
        while( $RUNNING ){
            my $rv;
            my $data;

            # read from socket
            $self->read();

            # write to socket
            $self->write();

            # idle
            $self->idle();

            # Sleep
            #sleep(1);
        }
    }
    $self->terminate_agent();

    plogNow("ETVA::GuestAgent mainLoop exit RUNNING=$RUNNING");
    return $EXITSIGNAL;
}

# idle
sub idle {
    my $self = shift;
}

# read messages from socket
sub read {
    my $self = shift;

    my $rv;
    my $data;

    my @ready_r = $self->{'select'}->can_read($self->{'select_timeout'});
    foreach my $client (@ready_r) {
        plogNow("[DEBUG] read client=$client") if( &debug_level > 9 );

        my $t0 = Time::HiRes::gettimeofday();

        $self->processclient($client);

        my $t1 = Time::HiRes::gettimeofday();
        my $secs = $t1 - $t0;
        plogNow("[DEBUG] process client=$client in $secs secs") if( &debug_level > 3 );

    }
}
  
# process client
sub processclient {
    my $self = shift;
    my ($client) = @_;

    # Get data
    my $request = $self->receive($client);

    if( $request ){
        # treat request
        $self->treatRequest($client,$request);
    }
}

# treat client request
sub treatRequest {
    my $self = shift;
    my ($client,$request) = @_;

    plogNow("[INFO] ETVA::GuestAgent treatRequest self=$self request=$request") if( &debug_level > 3 ); 
    if( $self->can('handlerequest') ){
        plogNow("[INFO] ETVA::GuestAgent can handlerequest") if( &debug_level > 9 ); 
        my $response = $self->handlerequest($client,$request);

        plogNow("[INFO] ETVA::GuestAgent treatRequest response=$response") if( &debug_level > 9 ); 
        # send response
        push(@{$queue_write{"$client"}}, $response);
    }
}

# receive from client
sub receive {
    my $self = shift;
    my ($sock) = @_;

    my $data = '';
    # receive data
    my $rv   = read($sock,$data, POSIX::BUFSIZ, 0);

    $inbuffer{$sock} .= $data;	                # add to buffer
    my $status = $self->checkData($inbuffer{$sock});	# check completion/integrity

    if( $status > 0 ){			# valid and complete data
        my $udata = $inbuffer{$sock};

        plogNow("ETVA::GuestAgent receive request=$udata") if( &debug_level > 9 );

        # clean buffer
        delete $inbuffer{$sock};

        # treat Request
        return $udata;
    }
    return;
}

# validate data
sub checkData {
    my $self = shift;
    my ($request) = @_;

    
    return 1 if( length($request) );
    return 0;
}

# send messages to client
sub write {
    my $self = shift;
    my $rv;
    my $data;

    my @ready_w = $self->{'select'}->can_write($self->{'select_timeout'});
    foreach my $client (@ready_w) {
        if( $queue_write{"$client"} ){
            if( scalar(@{$queue_write{"$client"}}) > 0 ){   # have message
                my $msg = shift(@{$queue_write{"$client"}});
                plogNow("[DEBUG] write client=$client msg=$msg") if( &debug_level > 9 );

                # send by client socket
                $self->send($client,$msg);
            }
        }
    }
}

# send to client
sub send {
    my $self = shift;
    my ($sock,@data) = @_;
    print $sock @data;
}

# end agent
sub terminate_agent {
}

# Signal handlers

sub alarm_handler {

    plogNow(__PACKAGE__," _alarm_ T_ALARM=$T_ALARM");
    
    $lasttime_alarm = time();

    alarm($T_ALARM);
}

sub usr1_handler {
    &debug_inc;
    $SIG{'USR1'} = \&usr1_handler;
}
sub usr2_handler {
    &debug_dec;
    $SIG{'USR2'} = \&usr2_handler;
}
sub chld_handler {
    my $self = shift;

    plogNow __PACKAGE__," receiving chld signal..." if( 1 || &debug_level > 5 );

    local ($!, $?);
    # wait for die pid
    my $dead_pid = waitpid(-1,&WNOHANG);
    while($dead_pid > 0){
        #last unless WIFEXITED($?);
        #last unless &chld_exists($dead_pid);

        $self->chld_dies_handler($dead_pid) if( $self->can('chld_dies_handler') );
        $dead_pid = waitpid(-1,&WNOHANG);
    }
}

sub set_runout {
	$RUNNING = 0;
}
sub int_handler {
    plogNow(__PACKAGE__," int signal");
    my $self = shift;
    $self->term_handler();

    $EXITSIGNAL = SIGINT;
}
sub hup_handler {
    plogNow(__PACKAGE__," hup signal");
    my $self = shift;
    $self->term_handler();

    $EXITSIGNAL = SIGHUP;
}
sub term_handler {
    &set_runout;
    $EXITSIGNAL = SIGTERM;
	plogNow(__PACKAGE__," Receive TERM Signal... Agent terminate! $_");
}

1;
