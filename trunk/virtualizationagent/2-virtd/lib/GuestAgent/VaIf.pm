#!/usr/bin/perl -w 

package GuestAgent::VaIf;

use strict;

#use Event::Lib;
use Data::Dumper;
use JSON;
use IO::Socket::INET;

use ETVA::Utils;
use GuestAgent::Message;


# $guestif = VaIf->new()
sub new{
    my $self = shift;
    my %p = @_;

    unless( ref $self ){
        my $class = ref($self) || $self;
        my %M = %p;

        $M{'queue'} = [];
        $M{'socket'} = $p{'socket'};
#        $M{'socket'} = IO::Socket::INET->new(
#            Listen    => 5,
#            LocalAddr => $p{'addr'},
#            LocalPort => $p{'port'},
#            Proto     => $p{'proto'},
#            ReuseAddr => 1 || SO_REUSEADDR,
##           Type      => SOCK_STREAM,
#        ) or die $!;

        $self = bless {%M} => $class;
    }

    return $self;
}

sub enqueue{
    my $self = shift;
    my %p = @_;

    push(@{ $self->{'queue'} },$p{'msg'}); 
}

sub dequeue{
    my $self = shift;

    if($self->queueSize > 0){
        my $msg = shift(@{ $self->{'queue'} });
        plog "dequeue succeeded" if( &debug_level > 9 );
        return $msg;
    }    
}

sub queueSize{
    my $self = shift;
    return scalar @{ $self->{'queue'} };
}

sub printSktState{
    my $self = shift;
    my $skt = $self->{'socket'};
    my $str = 'Write connected? ';
    $str .= $skt->connected() ? 'yes' : 'no';
    $str .= "\nWrite opened? ";
    $str .= $skt->opened() ? 'yes' : 'no';
    $str .= "\nWrite errored? ";
    $str .= $skt->error() ? 'yes' : 'no';
    $str .=  "\nProtocol: ";
    $str .= $skt->protocol();
    $str .= "\n";
    plog($str) if( &debug_level > 3 );
}

sub sendCmd{
    my $self = shift;
    my %p = @_;

    plog "SEND CMD CALLED"  if( &debug_level > 9 );
    

    my $msgObj = $p{'msg'};
    my $s = $self->{'socket'};
    $s->autoflush(1);
    if($msgObj->isValid()){
        plog "{sendCmd} ",$msgObj->toJson();
        #print $s $msgObj->toJson() or die "$!";
        $s->send($msgObj->toJson()) or return 0;
        $s->flush;
        return 1;
    }
    return 0;
}

sub getName{
    my $self = shift;
    return $self->{'name'};
}

sub getSocket{
    my $self = shift;
    return $self->{'socket'};
}   

#sub decodeMsg{
#    my $self = shift;
#    my %p = @_;
#
#    my $obj = decode_json $p{'msg'};
#    $obj->{'timestamp'} = time;
#    $self->{'state'}{$obj->{'__name__'}} = $obj;
#
#    return $obj;
#}


#sub _socketReader{
#    my $self = shift;
#
#    my $s = $self->{'socket'};
#    while (my $line = <$s>){
#        print $line;
##        $self->_updateState('response' => $line);
##        $self->_writeState();
#    }
#    print "child exiting\n";
#    exit 0;
#}

1;
