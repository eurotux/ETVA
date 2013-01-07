package GuestAgent::Client;

use IO::Socket;
use IO::Socket::INET;
use Data::Dumper;

use ETVA::Utils;
use GuestAgent::MessageFactory;

use constant {
    DEFAULT_TIMEOUT => 10,
};

#my $client = GuestAgent::Client->new(
#    'addr'  => 'localhost',
#    'port'  => '7778',
#    'proto' => 'tcp'
#);
#
#$client->refresh(
#    'vmname'    => 'centos6'
#);

#$client->shutdown(
#    'vmname'    => 'centos6'
#);


sub new{
    my $self = shift;
    my %p = @_;

    unless( ref $self ){
        my $class = ref($self) || $self;
        my %M = %p;

        $M{'socket'} = new IO::Socket::INET (
                PeerAddr => $p{'addr'},
                PeerPort => $p{'port'},
                Proto => $p{'proto'},
                'Timeout'   => $p{'timeout'} || DEFAULT_TIMEOUT
           ) 
        or die "GuestAgent::Client Could not create socket ($p{'proto'},$p{'addr'},$p{'port'}): $!\n";

        $self = bless {%M} => $class;
    }

    return $self;
}

sub disconnect{
    my $self = shift;
    shutdown($self->{'socket'},2);   # bye
    close($self->{'socket'});
}

########### PRIVATE METHODS ##########
sub _send{
    my $self = shift;
    my %p = @_;
    
    plog "Sending message to GA Manager: $p{'msg'}" if( &debug_level > 9 );

    my $s = $self->{'socket'};
    $s->autoflush(1);
    #print $s $p{'msg'};
    $s->send($p{'msg'});
    $s->flush;
}

sub _receive{
    my $self = shift;
    my %p = @_;
    
    my $s = $self->{'socket'};
    my $msg;

    eval {
        local $SIG{ALRM} = sub { die "alarm\n" }; # NB: \n required

        my $timeout = $self->{'timeout'} || DEFAULT_TIMEOUT;
        alarm $timeout;

        plog "RECEIVE CALLED" if( &debug_level > 9 );
        if(defined($msg = <$s>)) {
            plog "{_receive} VA RECEIVED MSG: ",$msg if( &debug_level > 9 );
        }

        alarm 0;
    };
    if( $@ ){
        die unless $@ eq "alarm\n";   # propagate unexpected errors
        # timed out
        plog( "{_receive} timeout..." )  if( &debug_level > 3 );
    }

    return $msg;
}

sub _createJson{
    my %p = @_;
    print Dumper \%p;
    my $str = '{';

    foreach my $k (keys %p){
        $str .= "\"$k\": \"$p{$k}\",";
    }
    chop $str;
    $str .= "}\n";
    return $str;
}

############ ACTION METHODS ##########
sub getState{
    my $self = shift;
    my %p = @_;

    $p{'action'} = GuestAgent::MessageFactory::GETSTATE;

    if( defined $p{"vmname"} ){        
        my $msg = GuestAgent::Client::_createJson(%p);
        $self->_send('msg' => $msg);
        return $self->_receive(); 
    }
    return 0;
}

sub refresh{
    my $self = shift;
    my %p = @_;
    $p{'action'} = GuestAgent::MessageFactory::REFRESH;

    if( defined $p{"vmname"} ){        
        my $msg = GuestAgent::Client::_createJson(%p);
        $self->_send('msg' => $msg);
        return $self->_receive(); 
    }
    return 0;
}

sub shutdown{
    my $self = shift;
    my %p = @_;
    $p{'action'} = GuestAgent::MessageFactory::SHUTDOWN;

    if( defined $p{"vmname"} ){
        my $msg = GuestAgent::Client::_createJson(%p);
        $self->_send('msg' => $msg);
        return $self->_receive(); 
    }
    return 0;
}

sub genericMsg{
    my $self = shift;
    my %p = @_;
   
    if( defined $p{'vmname'} && defined{'action'} ){
        my $msg = GuestAgent::Client::_createJson(%p);
        $self->_send('msg' => $msg);
        return $self->_receive();
    }
}

sub messageWithResponse{
    my $self = shift;
    my %p = @_;

    $p{'withresponse'} = 1;
    if( defined $p{'vmname'} && defined{'action'} ){
        my $msg = GuestAgent::Client::_createJson(%p);
        $self->_send('msg' => $msg);
        return $self->_receive();
    }
#    return $self->genericMsg(%p);
}

sub connect{
    my $self = shift;
    my %p = @_;

    $p{'action'} = GuestAgent::MessageFactory::CONNECT;
    if( defined $p{"vmname"} ){
        my $msg = GuestAgent::Client::_createJson(%p);
        $self->_send('msg' => $msg);
        return $self->_receive(); 
    }
    return 0;
}
######################################

1;
