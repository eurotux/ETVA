package ETVA::GuestAgent::SerialPort::SOAP;

use strict;

BEGIN {
    # this is the worst damned warning ever, so SHUT UP ALREADY!
    $SIG{__WARN__} = sub { warn @_ unless $_[0] =~ /Use of uninitialized value/ };

    require Exporter;
    require ETVA::GuestAgent::SerialPort;
    require ETVA::GuestAgent::SOAP;
    use vars qw($VERSION @ISA @EXPORT @EXPORT_OK %EXPORT_TAGS $CRLF);
    $VERSION = '0.0.1';
    @ISA = qw( ETVA::GuestAgent::SerialPort ETVA::GuestAgent::SOAP );
    @EXPORT = qw(  );
}

use ETVA::SOAP;
use ETVA::Utils;

use Time::HiRes qw(gettimeofday);

my @QueueMessages = ();     # queue not processed messages
my $buffer_message_soap = '';   # buffer of read messages

my $timeout_receive_message = 3 * 60;	# timeout for receive message

# new method
sub new {
    my $self = shift;
    
    unless( ref $self ){
        my $class = ref( $self ) || $self;

        my %params = @_;
        
        $self = $self->SUPER::new( %params );

        $self = bless $self => $class;
    }
	
	return $self;
}

# call required for CM connection
sub call {
    my $self = shift;
    my ($uri,$method,@params) = @_;

    # default uri
    $uri = "urn:#$method" if( !$uri );

    my $request = &soap_request($uri,$method,@params);

    plogNow "SOAP call request = $request" if( &debug_level > 9);

    my $t0 = Time::HiRes::gettimeofday();

    my $response = ETVA::Utils::timeout_call($timeout_receive_message, \&send_receive, $self,$request);

    my $t1 = Time::HiRes::gettimeofday();
    my $secs = $t1 - $t0;

    plogNow("[DEBUG] ",__PACKAGE__," call to CM in $secs secs") if( &debug_level > 3 );

    plogNow "SOAP call response = $response" if( &debug_level > 9);

    return &soap_response($response);
}

# send and receive messages from serial port
sub send_receive {
    my $self = shift;
    my ($request) = @_;
 
    plogNow(" send_receive: send...") if( &debug_level > 9 );
 
    $self->{'PortObj'}->write( "$request" );
 
    plogNow(" send_receive: receive...") if( &debug_level > 9 );
 
    # wait for response
    #return $self->waitForMessage(\&isSoapResponseOrFault );
    return ETVA::Utils::timeout_call($timeout_receive_message,\&waitForMessage,$self,\&isSoapResponseOrFault );
}

# receive message SOAP
sub receiveSOAP {
    my $self = shift;
    my $ready = 0;
    my $message = '';
    while(!$ready){
        # not in buffer message
        if( !$buffer_message_soap ){
            # read from serial port
            $buffer_message_soap = $self->{'PortObj'}->read(9600);
        }
        if( length($buffer_message_soap) ){
            # get first message
            my (undef,$head,$tail) = split(/<\?xml/,$buffer_message_soap,3);
            $message = "<?xml$head";
            $buffer_message_soap = ($tail) ? "<?xml$tail" : "";

            # check if message is SOAP valid
            $ready = &isSOAPValid($message);
            plogNow("DEBUG receiveSOAP m=$message ready=$ready err=$@") if( &debug_level > 9);
        }
    }
    return $message;
}

# wait for message and put on queue 
sub processQueueMessages {
    my ($handle) = @_;
    my @aux = ();
    my $message;
    for my $m (@QueueMessages){
        if( $handle->($m) ){    # use handle to validate type of message
            $message = $m;
        } else {
            push(@aux,$m);
        }
    }
    @QueueMessages = @aux;  # change queue
    return $message;
}
sub waitForMessage {
    my $self = shift;
    my ($handle) = @_;
    my $ready = 0;
    my $message = '';
    if( !($message = &processQueueMessages($handle)) ){
        while(!$ready){
            $message = $self->receiveSOAP();
            if( !($ready = $handle->($message)) ){  # use handle to validate type of message
                # if is not desirable of type, enqueue the message
                push(@QueueMessages,$message);
            }
            plogNow("DEBUG waitForMessage message=$message ready=$ready") if( &debug_level > 9 );
        }
    }
    return $message;
}

sub receive {
    my $self = shift;

    plogNow("receive ... ");
    # wait for request
    return ETVA::Utils::timeout_call($timeout_receive_message,\&waitForMessage,$self,\&isSoapRequestOrFault);
    #return $self->waitForMessage(\&isSoapRequestOrFault );
}
1;
