#!/usr/bin/perl -w 

package GuestAgent::MessageFactory;

use strict;
use Data::Dumper;
use JSON;

use GuestAgent::Message;
use GuestAgent::GuestIf;
use GuestAgent::VaIf;

use ETVA::Utils;

use constant {
    CONNECT     => 'connect',
    DISCONNECT  => 'disconnect',
    LOCKSCREEN  => 'lock-screen',
    RSPLOCKSCREEN => 'rsp-lock-screen',
    LOGOFF      => 'log-off',
    RSPLOGOFF   => 'rsp-log-off',
    SHUTDOWN    => 'shutdown',
    RSPSHUTDOWN => 'rspshutdown',
    LOGIN       => 'login',
    RSPLOGIN    => 'rsplogin',
    REFRESH     => 'refresh',
    ECHO        => 'echo',
    OK          => 'ok',
    NOK         => 'nok',
    ERROR       => 'error',
    GETSTATE    => 'state',
    ETASPCOMMAND=> 'etasp'
};

sub createEmptyMsg{
    my %p = @_;
    GuestAgent::Message->new(); 
}

#USAGE: GuestAgent::MessageFactory::createGuestMsgFromJson('msg' => json, 'id' => 1)
sub createGuestMsgFromJson{
    my %p = @_; 

    my $hash = decode_json $p{'msg'}; 
    my $msg = GuestAgent::MessageFactory::createGuestMsg(
        %$hash,
        'guest' => $p{'guest'},
        'id'    => $p{'id'},
        'va'    => $p{'va'}
    );

    return $msg;
}

#USAGE: GuestAgent::MessageFactory::createVaMsgFromJson('msg' => json, 'id' => 1)
sub createVaMsgFromJson{
    my %p = @_; 

    my $hash = decode_json $p{'msg'}; 
    my $msg = GuestAgent::MessageFactory::createVaMsg(
        %$hash,
        'va'    => $p{'va'},
        'id'    => $p{'id'}
    );
    return $msg;
}


#usage GuestAgent::MessageFactory::createGuestMsg('id' => 1, 'action' => 'shutdown', 'guest' => obj, 'va' => obj)
sub createGuestMsg{
    my %p = @_;

    if( defined $p{'id'} && defined $p{'action'} && 
        defined $p{'guest'} && ref($p{'guest'}) eq 'GuestAgent::GuestIf' &&
        defined $p{'va'} && ref($p{'va'}) eq 'GuestAgent::VaIf')
    {
        $p{'__name__'} = delete $p{'action'};
#        $p{'id'} = $self->_getIdAndInc();
    
        my $msg = GuestAgent::Message->new(%p);
        if( $msg->isValid() ){
            return $msg;
        }else{
            plog "malformed message", Dumper $msg;
            die;
        }
    }else{
        plog "Error: could not create Guest Message";
        die;
    }
}

#usage GuestAgent::MessageFactory::createVaMsg('id' => 1, 'action' => 'shutdown', 'va' => obj)
sub createVaMsg{
    my %p = @_;

plog "CREATING VA MSG";
   
    if( defined $p{'guest'} && ref($p{'guest'}) ne 'GuestAgent::GuestIf'){
        plog "wrong guest type: ", ref($p{'guest'});
        die;
    }

    if( defined $p{'id'} && defined $p{'action'} )
    {
        if( defined $p{'__name__'} ){
            $p{'action'} = delete $p{'__name__'};
        }

#        $p{'id'} = $self->_getIdAndInc();
    
        my $msg = GuestAgent::Message->new(%p);
        if( $msg->isValid() ){
            return $msg 
        }else{
            plog "malformed message", Dumper $msg;
            die;
        }
    }else{
        plog Dumper \%p;
        plog "Error: could not create Va Message";
        die;
    }
}

1;
