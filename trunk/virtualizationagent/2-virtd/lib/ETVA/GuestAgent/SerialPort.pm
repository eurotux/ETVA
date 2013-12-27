package ETVA::GuestAgent::SerialPort;

use strict;

BEGIN {
    # this is the worst damned warning ever, so SHUT UP ALREADY!
    $SIG{__WARN__} = sub { warn @_ unless $_[0] =~ /Use of uninitialized value/ };

    require Exporter;
    require ETVA::GuestAgent;
    use vars qw($VERSION @ISA @EXPORT @EXPORT_OK %EXPORT_TAGS $CRLF);
    $VERSION = '0.0.1';
    @ISA = qw( ETVA::GuestAgent );
    @EXPORT = qw(  );
}

use ETVA::Utils;

use Device::SerialPort qw( :PARAM :STAT 0.07 );

use POSIX;

my @list_serial_ports = qw(/dev/ttyS0  /dev/ttyS1  /dev/ttyS2  /dev/ttyS3);

# new method
sub new {
    my $self = shift;
    
    unless( ref $self ){
        my $class = ref( $self ) || $self;

        my %params = @_;
        
	if( $params{'serial_ports'} ){
            @list_serial_ports = @{ $params{'serial_ports'} };	    
	}
	if( $params{'exclude_serial_ports'} ){
            my %ex_serial_ports = map { $_ => 1 } @{ $params{'exclude_serial_ports'} };
            @list_serial_ports = grep { !$ex_serial_ports{$_} } @list_serial_ports;
	} 
        $self = $self->SUPER::new( %params );

        $self = bless $self => $class;

        ($self->{'PortObj'},$self->{'server'}) = $self->_init(%params);
        if( !$self->{'PortObj'} ){
            die "Couldn't initiate serial port!";
            return;
        }

        $self->{'select'}->add( $self->{'server'} );
    }
	
	return $self;
}

# idle
sub idle {
    my $self = shift;
}

# open serial port
sub _open_serial_port {
    my $self = shift;
    my %p = @_;

    my ($portObj,$sockPort);
    if( $p{'serial_port'} ){
        #$portObj = new Device::SerialPort("$p{'serial_port'}");
        $portObj = tie (*COM, 'Device::SerialPort', $p{'serial_port'});
        if( !$portObj ){
            $sockPort = *COM;
            plogNow("ETVA::GuestManagementAgentSerialPort _open_serial_port: couldn't open serial port '$p{'serial_port'}': $!");
        }
    } else {
        for my $sport (@list_serial_ports){
            #$portObj = new Device::SerialPort("$sport");
            $portObj = tie (*COM, 'Device::SerialPort', $sport);
            if( $portObj ){
                $sockPort = *COM;
                last;
            } else {
                plogNow("ETVA::GuestManagementAgentSerialPort _open_serial_port: couldn't open serial port '$sport': $!");
            }
        }
    }
    if( $portObj ){
        # initialize port
        $portObj->user_msg(1);
        $portObj->baudrate($p{'baudrate'} || 9600);
        $portObj->parity("none");
        $portObj->databits(8);
        $portObj->stopbits(1);
        $portObj->handshake("xoff");
        $portObj->write_settings;
        $portObj->lookclear;

        $portObj->read_char_time(0);
        $portObj->read_const_time(100);

        $portObj->stty_echo;
        $portObj->stty_echoe;
        $portObj->stty_echok;
    }
    return wantarray() ? ($portObj,$sockPort) : $portObj;
}

# initialize
sub _init {
    my $self = shift;
    my %p = @_;

    # init serial port
    return $self->_open_serial_port(%p);
}

# end agent and close serial port
sub terminate_agent {
    my $self = shift;

    close($self->{'server'});
    close(COM);
    $self->{'PortObj'}->close;
    undef $self->{'PortObj'};
    untie($self->{'server'});
    untie *COM;
    undef $self->{'server'};
}

# idle
sub idle {
    my $self = shift;
    $self->SUPER::idle();
}

1;

