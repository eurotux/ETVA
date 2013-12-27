package ETVA::GuestAgent::Management;

use strict;

BEGIN {
    # this is the worst damned warning ever, so SHUT UP ALREADY!
    $SIG{__WARN__} = sub { warn @_ unless $_[0] =~ /Use of uninitialized value/ };

    use vars qw($VERSION @ISA @EXPORT @EXPORT_OK %EXPORT_TAGS $CRLF $AUTOLOAD);
    $VERSION = '0.0.1';
    @ISA = qw( );
    @EXPORT = qw( );
}

use ETVA::Utils;

use ETVA::GuestAgent::Socket::SOAPFork;
use ETVA::GuestAgent::SerialPort::SOAP;

use POSIX qw(:signal_h :errno_h :sys_wait_h);

my $RUNNING;
my $EXITSIGNAL;

sub new {
    my $self = shift;
    unless( ref $self ){
        my $class = ref( $self ) || $self;

        my %params = @_;
        
        $SIG{'HUP'} = $SIG{'TERM'} = $params{'_inthandler_'} = $params{'_huphandler_'} = $params{'_termhandler_'} = sub { $self->term_handler(); };

        $self = bless {%params} => $class;

        $self->{'AgentSerialPort'} = new ETVA::GuestAgent::SerialPort::SOAP(%params);
        $self->{'AgentSocket'} = new ETVA::GuestAgent::Socket::SOAPFork(%params);
    }
	
	return $self;
}

# loop to receive messages
sub mainLoop {
    my $self = shift;
	
    # register func handlers
    if( $self->register() ){
        $RUNNING = 1;
        
        # run
        plogNow("[INFO] ETVA::GuestAgent::Management mainLoop: Running...");
        while( $RUNNING ){

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

    plogNow("ETVA::GuestAgent::Management mainLoop exit RUNNING=$RUNNING");
    return $EXITSIGNAL;
}
sub register {
    my $self = shift;
    my $v1 = $self->{'AgentSerialPort'}->register();
    my $v2 = $self->{'AgentSocket'}->register();
    return ($v1 || $v2);
}

sub AUTOLOAD {
    my $method = $AUTOLOAD;
    my $self = shift;
    my %p = @_;

    plog "ETVA::GuestAgent::Management method=$method" if( &debug_level > 5 );

    if( my ($request_class,$m1) = ($method =~ m/(.*)::(.+)/) ){
        if( $self->{'AgentSerialPort'}->can($m1) && $self->{'AgentSocket'}->can($m1) ){
            # just call method as it is for both agents
            $self->{'AgentSerialPort'}->$m1(%p) if( $self->{'AgentSerialPort'}{'registered'} );
            $self->{'AgentSocket'}->$m1(%p) if( $self->{'AgentSocket'}{'registered'} );
        }
    } else {
        die "method $method not found\n";
    }
    return;
}

sub set_runout {
	$RUNNING = 0;
}
sub term_handler {
    my $self = shift;

    $self->{'AgentSocket'}->term_handler();
    $self->{'AgentSerialPort'}->term_handler();

    &set_runout();
    $EXITSIGNAL = SIGTERM;
	plogNow(__PACKAGE__," Receive TERM Signal... Agent terminate!");
}

1;
