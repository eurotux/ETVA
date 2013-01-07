#!/usr/bin/perl -w 

package GuestAgent::Manager;

use strict;
use JSON;
use Data::Dumper;
use IO::Socket;
use IO::Select;
use IO::Socket::UNIX;

use GuestAgent::VaIf;
use GuestAgent::GuestIf;
use GuestAgent::Message;
use GuestAgent::MessageFactory;

use ETVA::Utils;


BEGIN {
    # this is the worst damned warning ever, so SHUT UP ALREADY!
    $SIG{__WARN__} = sub { warn @_ unless $_[0] =~ /Use of uninitialized value/ };

    use vars qw($VERSION @ISA @EXPORT @EXPORT_OK %EXPORT_TAGS $CRLF $AUTOLOAD);
    $VERSION = '0.0.1';
    @ISA = qw( );
    @EXPORT = qw( );
}

use IO::Socket::UNIX qw( SOCK_STREAM );

use constant {
    DEFAULT_TIMEOUT => 1,
};


sub new{
    my $self = shift;
    my %p = @_;

    unless( ref $self ){
        my $class = ref($self) || $self;
        my %M = %p;
        $M{'gqueue'} = [];
        $M{'gselect'} = new IO::Select();
        $M{'vaselect'} = new IO::Select();
        $M{'vaqueue'} = [];
        $M{'valist'} = [];
        $M{'guestlist'} = [];
        $M{'msgid'} = 0;

        # create va tcp server
        my $srv = IO::Socket::INET->new(
            Listen    => 5,
            LocalAddr => $p{'addr'},
            LocalPort => $p{'port'},
            Proto     => $p{'proto'},
            ReuseAddr => 1 || SO_REUSEADDR,
#           Type      => SOCK_STREAM,
            Timeout   => 3
        ) or die "GuestAgent::Manager couldn't create IO::Socket::INET $!";

        $M{'mainSkt'} = $srv;
        $M{'vaselect'}->add($srv);

        # ensure some dirs    
        $M{'state_dir'} = $ENV{'ga_state_dir'} || '/tmp/foo/state';
        `mkdir -p $M{'state_dir'}`;
        $M{'socket_dir'} = $ENV{'ga_socket_dir'} || '/tmp/foo';

        $self = bless {%M} => $class;
    }

    return $self;
}


sub addVaIf{
    my $self = shift;
    my %p = @_;

    my $va = GuestAgent::VaIf->new('socket' => $p{'socket'});

#    $self->{'mainSkt'} = $va_skt->getSocket();
    push(@{ $self->{'valist'} }, $va);
    $self->{'vaselect'}->add($p{'socket'});
    
    #if debug
    $va->printSktState();
}
sub rmVaIf{
    my $self = shift;
    my %p = @_;

    my $sock = $p{'socket'};
    $self->{'vaselect'}->remove($sock);
    $self->{'valist'} = [ grep { ($_->getSocket() != $sock) } @{ $self->{'valist'} } ];

}

#usage addSkt('skt' => '/tmp/foo/pipe', 'vmname' => 'centos5')
sub addSkt{
    my $self = shift;
    my %p = @_;
    
    my $state_file = $self->{'state_dir'};
    $state_file .= "/$p{'vmname'}";

    my $guest = GuestAgent::GuestIf->new(
        'path'      => $p{'socketpath'}, 
        'state_file'=> $state_file, 
        'vmname'    => $p{'vmname'}, 
    );
    push(@{ $self->{'guestlist'} }, $guest);
    
    $guest->printSktState() if($ENV{'DEBUG'});
    $self->{'gselect'}->add($guest->getSocket());
}

sub rmSkt{
    my $self = shift;
    my %p = @_;

    my $sock = $p{'socket'};
    $self->{'gselect'}->remove($sock);
    $self->{'guestlist'} = [ grep { ($_->getSocket() != $sock) } @{ $self->{'guestlist'} } ];

}

#USAGE: m->getSkt('name' => 'centos6')
sub getSkt{
    my $self = shift;
    my %p = @_;

    # search for the skt
    my @skts = $self->{'guest_list'};
    foreach my $skt(@skts){
        if($skt->getName() eq $p{'name'}){
            return $skt;
        }
    }
}

sub writeToSkt{
    my $self = shift;
    my %p = @_;

    my $skt = $p{'skt'};

#TODO: to implement    
#    $skt->sendCmd('cmd' => MsgObj);
}

sub writeToAllSkt{
    my $self = shift;
    my @skts = $self->{'guest_list'};

    #for each socket call write to socket
    foreach my $skt (@skts){
        writeToSkt($skt);
    }
}

sub _readVa{
    my $self = shift;
    my @new_readable = $self->{'vaselect'}->can_read(DEFAULT_TIMEOUT); #timeout 1 sec

    #We now have at least one readable handle.
    SOCK: foreach my $sock (@new_readable){

        if($sock == $self->{'mainSkt'}){
            my $new_sock = $sock->accept() or die "GuestAgent::Manager socket accept Should not happen";
            $new_sock->sockopt(SO_REUSEADDR,1) or die("GuestAgent::Manager can't sockopt!");
            $self->addVaIf('socket'=>$new_sock);
#            $new_sock->blocking(0);
#            $self->{'vaselect'}->add($new_sock);            
        }else{
            my $buf = <$sock>;
            if($buf) {

                plog "{_readVa} Received from VA: ", $buf if( &debug_level > 3 ); 
                my $va = $self->getVaBySocket('socket' => $sock);
                unless($va){
                    plog "{_readVa} va socket not found $sock";
                    die "GuestAgent::Manager va soucket not found $sock";
                    #TODO: review
                    next SOCK;
                }   

                #Do stuff with $buf
                #check the action
                my %hash = %{ decode_json($buf) };
                
                #check if we are connected with the vm (except for state and connect msgs)
                my $guest = $self->getGuestByVMName( 'vmname' => $hash{'vmname'} );
                unless( 
                    (defined $guest && ref($guest) eq 'GuestAgent::GuestIf') 
                    || $hash{'action'} eq GuestAgent::MessageFactory::CONNECT
                    || $hash{'action'} eq GuestAgent::MessageFactory::GETSTATE)
                    {

                    # TODO: disconnected guest
                    plog("{_readVa} $hash{'vmname'} read va else") if( &debug_level > 9 );
                    my $vamsg = GuestAgent::MessageFactory::createVaMsg(
                        'id'        => $self->_getIdAndInc(),
                        'action'    => $hash{'action'},
                        'success'   => GuestAgent::MessageFactory::NOK,
                        'errormsg'  => "disconnected agent $hash{'vmname'}",
                    );

                    if( defined $vamsg){
                        $va->enqueue('msg' => $vamsg);
                    }

                    plog "{_readVa} $hash{'vmname'} disconnected agent" if( &debug_level > 9 );

                    next SOCK;
                }
        
                if( $hash{'action'} eq GuestAgent::MessageFactory::CONNECT ){

                    # connect to guest
                    my $retry = 3;
                    my $error = 0;
                    until( $guest ){

                        plog("{_readVa} Trying to connect to $hash{'vmname'}. Remaining tries: $retry") if( &debug_level > 3 );
                        if($retry < 0 || $error){

                            plog("{_readVa} Error connect to $hash{'vmname'} (retry=$retry): $error") if( &debug_level > 3 );
                            plog("readva  if") if( &debug_level > 9 );
                            my $vamsg = GuestAgent::MessageFactory::createVaMsg(
                                'id'        => $self->_getIdAndInc(),
                                'action'    => $hash{'action'},
                                'success'   => GuestAgent::MessageFactory::NOK,
                                'errormsg'  => "could not connect to guest $hash{'vmname'}",
                            );

                            if( defined $vamsg){
                                $va->enqueue('msg' => $vamsg);
                            }

                            next SOCK;
                        }


                        my $socket_path = $self->{'socket_dir'};
                        $socket_path .= "/$hash{'vmname'}";
                    

                        #TODO: socketpath in config file or env
                        eval{
                            $self->addSkt(
                                'socketpath'=> $socket_path, 
                                'vmname'    => $hash{'vmname'}, 
                            );
                        };

                        if($@){
                            $error = $@;
                            plog( "{_readVa} $hash{'vmname'}  error addSkt: $error" ) if( &debug_level > 9 );
                            next;    
                        }

                        $retry -= 1;
   
                        # TODO: test to connect to an unknown vm                    
                        $guest = $self->getGuestByVMName('vmname' => $hash{'vmname'});
                    }
    
                    # send success msg
                    plog("{_readVa} $hash{'vmname'}  connect message") if( &debug_level > 9 );
                    my $vamsg = GuestAgent::MessageFactory::createVaMsg(
                        'id'        => $self->_getIdAndInc(),
                        'action'    => $hash{'action'},
                        'success'   => GuestAgent::MessageFactory::OK,
                        'msg'  => "connected to guest $hash{'vmname'}",
                    );

                    if( defined $vamsg){
                        $va->enqueue('msg' => $vamsg);
                    }
                    next SOCK;
                }elsif($hash{'action'} eq GuestAgent::MessageFactory::GETSTATE){
                    my $state = $guest->getState();
#                    plog "printing state: ", Dumper $state;
                   
                    plog("{_readVa} $hash{'vmname'} get state msg") if( &debug_level > 9 );
                    my $vamsg = GuestAgent::MessageFactory::createVaMsg(
                        'id'        => $self->_getIdAndInc(),
                        'action'    => $hash{'action'},
                        'success'   => GuestAgent::MessageFactory::OK,
                        'msg'       => $state,
                    );

                    $va->enqueue('msg' => $vamsg);
                }else{
                    my $msg_id = $self->_getIdAndInc();

                    my $msg = GuestAgent::MessageFactory::createGuestMsgFromJson(
                        'msg'   => $buf, 
                        'id'    => $msg_id,
                        'guest' => $guest,
                        'va'    => $va
                    );
        
                    # generic msgs
                    my $obj = decode_json $buf;
                    if($obj->{'withresponse'}){
                        $guest->{'waitingqueue'}{$msg_id} = $va;   
                    }

                    if($msg){
                        $guest->enqueue('msg' => $msg);
                    }
                }
            } else {
                #socket was closed
                $self->rmVaIf('socket'=>$sock);
                close($sock);
            }
        }
    }
}

sub getGuestByVMName{
    my $self = shift;
    my %p = @_;
    
    foreach my $g (@{ $self->{'guestlist'} }){
        return $g if($g->getVmName() eq $p{'vmname'});
    }
}

sub getGuestBySock{
    my $self = shift;
    my %p = @_;
    
    foreach my $g (@{ $self->{'guestlist'} }){
        if( $g->getSocket == $p{'socket'} ){
            return $g;
        }
    }
}

sub getVaBySocket{
    my $self = shift;
    my %p = @_;
    
    foreach my $va (@{ $self->{'valist'} }){
        if( $va->getSocket == $p{'socket'} ){
            return $va;
        }
    }
}


sub _readGuests{
    my $self = shift;
    my @new_readable = $self->{'gselect'}->can_read(DEFAULT_TIMEOUT); #timeout 1 sec

    my @return_read = ();
    # We now have at least one readable handle.
    SOCK: foreach my $sock (@new_readable){

        my $guest = $self->getGuestBySock('socket' => $sock);
        next SOCK unless( $guest && ref($guest) eq 'GuestAgent::GuestIf');

        # construct vm state
        my $buf = $guest->read();
    
        if($buf) {
#           $guest->{'waiting'}{$msg_id}

            # Do other stuff with buffer

            # TODO: Remove
            #`echo "$buf" >> /tmp/rec`;

            plog "RESPOSTA RECEBIDA AKII de $guest->{'vmname'}" if( &debug_level > 9 );
            plog $buf if( &debug_level > 9 );

            my $obj = decode_json $buf;
            my $msg_id = $obj->{'id'};

            plog "WAITING QUEUE" if( &debug_level > 9 );
            my $va = $guest->{'waitingqueue'}{$msg_id};

            if($va){
                plog "Removing from waiting queue" if( &debug_level > 9 );
                delete $guest->{'waitingqueue'}{$msg_id};

                plog "resposta certa" if( &debug_level > 9 );
                my $vamsg = GuestAgent::MessageFactory::createVaMsg(
                    'action'    => $obj->{'__name__'},
                    'id'        => $obj->{'id'},
                    'success'   => GuestAgent::MessageFactory::OK, 
                    'guest'     => $guest,
                    'msg'       => $obj
                );
                
                plog "ENQUEUING MSG TO VA" if( &debug_level > 9 );

                if($vamsg){
                    $va->enqueue('msg' => $vamsg);
                }
            }


            push(@return_read, { 'guest'=>$guest, 'msg'=>$buf });

#            my $va = $guest->getWaitingForResp($obj);


        } else {
            plog("_readGuests socket '$sock' was closed") if( &debug_level > 3 );
            #socket was closed
            $self->rmSkt('socket'=>$sock);
            close($sock);
        }
    }

    return wantarray() ? @return_read : \@return_read;
}

sub _getIdAndInc{
    my $self = shift;
    my $id = $self->{'msgid'};
    $self->{'msgid'}++;
    return $id;
}

# gets and incs a msg id
# USAGE: $self->createMsg('action' => 'shutdown')
sub createMsg{
    my $self = shift;
    my %p = @_;

    $p{'__name__'} = delete $p{'action'};
    $p{'id'} = $self->_getIdAndInc();

    my $msg = Message->new(%p);
    return $msg if( $msg->isValid() );
}

sub _writeGuests{
    my $self = shift;

    my @new_writeable = $self->{'gselect'}->can_write(DEFAULT_TIMEOUT); #timeout 1 sec

    # We now have at least one writeable handle.
    SOCK: foreach my $sock (@new_writeable){
        
        # get guest obj
        my $guest = $self->getGuestBySock('socket' => $sock);

        # check if there are any message to be written
        my $msg = $guest->dequeue();

        # write the first message 
        if( defined $msg && ref($msg) eq 'GuestAgent::Message'){
            plog "writing to Guest Agent" if( &debug_level > 9 );
            $guest->sendCmd('msg' => $msg); 

            # auto answer. if in ask resp mode do not send auto msg
            # send response to va
            unless( $msg->hasResponse() ){
                plog "NAO TEM RESPOSTA" if( &debug_level > 9 );
                my $va = $msg->getVa();
                plog("write guests") if( &debug_level > 9 );
                my $vamsg = GuestAgent::MessageFactory::createVaMsg(
                    'id'        => $msg->getId(),
                    'action'    => $msg->getAction(),
                    'success'   => GuestAgent::MessageFactory::OK, 
                    'guest'     => $guest,
                );
    
                if($vamsg){
                    $va->enqueue('msg' => $vamsg);
                }
            }
        }
    }
}

sub _writeVa{
    my $self = shift;

    my @new_writeable = $self->{'vaselect'}->can_write(DEFAULT_TIMEOUT); #timeout 1 sec

    # We now have at least one writeable handle.
    SOCK: foreach my $sock (@new_writeable){

        # get va obj 
        my $va = $self->getVaBySocket('socket' => $sock);

        # check if there are any message to be written
        my $msg = $va->dequeue();

        if( defined $msg && ref($msg) eq 'GuestAgent::Message'){
            plog "SENDING MSG TO VA" if( &debug_level > 9 );
            $va->sendCmd('msg' => $msg); 
        }
    }

}

#sub readFromVA{
#    my $self = shift;
#
#    # enqueue
#    my $msg = $self->createMsg('action' => 'refresh');
#    unshift(@{ $self->{'gqueue'} },$msg);
#}


#my $gm = Manager->new();
#
#$gm->addVASkt('addr' => '127.0.0.1', 'port' => 7777, 'protocol' => 'tcp');
#$gm->addSkt('skt' => '/tmp/foo/pipe', 'name' => 'centos6', );
#
##my $read = VaIf->new('port' => 7777, 'guestmanager' => $gm);
#
#while(1){
#    $gm->_readVa();
#    $gm->_writeGuests();
#
#    $gm->_readGuests();
#    $gm->_writeVa();
#    sleep 1;
#}

1;
