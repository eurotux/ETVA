#!/usr/bin/perl -w 

package GuestAgent::Message;

use strict;
use Data::Dumper;
use JSON;

use ETVA::Utils;
use GuestAgent::MessageFactory;


# $msg = Message->new('id' => 1, 'skt' => $skt,'__name__' => 'shutdown', ['arg1name' => 'arg1value',] ['arg2name' => 'arg2value'])
sub new{
    my $self = shift;
    my %p = @_;

    unless( ref $self ){
        my $class = ref($self) || $self;
        my %M = ();
        $M{'guest'} = delete $p{'guest'};
        $M{'va'}    = delete $p{'va'};
        $M{'id'}    = $p{'id'};
        $M{'msg'}   = \%p;
        $self = bless {%M} => $class;
    }
    return $self;
}

sub isValid{
    my $self = shift;
    my %msg = %{$self->{'msg'}};

    return 0 unless( defined $msg{'id'} );

#    return 0 unless( 
#        $msg{'__name__'} eq GuestAgent::MessageFactory::LOCKSCREEN ||
#        $msg{'__name__'} eq GuestAgent::MessageFactory::LOGOFF ||
#        $msg{'__name__'} eq GuestAgent::MessageFactory::SHUTDOWN ||
#        $msg{'__name__'} eq GuestAgent::MessageFactory::LOGIN ||
#        $msg{'__name__'} eq GuestAgent::MessageFactory::REFRESH ||
#        $msg{'__name__'} eq GuestAgent::MessageFactory::ECHO 
#    );

    #TODO: usar constante da factory
    if($msg{'__name__'} eq 'login'){ #GuestAgent::MessageFactory::LOGIN){
        return 0 unless(defined($msg{'username'}) && defined($msg{'password'}));
    }

    return 1;
}

sub hasResponse{
    my $self = shift;
    plog "HAS RESPONSE CALLED";
    plog (defined $self->{'msg'}{'withresponse'}) ? 1 : 0;

    return (defined $self->{'msg'}{'withresponse'}) ? 1 : 0;
}

sub toJson{
    my $self = shift;
    my $json = JSON::encode_json($self->{'msg'});
    $json .= "\n";
    return $json;
}

sub getGuest{
    my $self = shift;
    return $self->{'guest'};
}

sub getAction{
    my $self = shift;
    return $self->{'msg'}->{'__name__'};
}

sub getVa{
    my $self = shift;
    return $self->{'va'};
}

sub getId{
    my $self = shift;
    return $self->{'id'};
}
#my $msg = Message->new('__name__' => Message::SHUTDOWN);
#print Dumper $msg;
#print $msg->toJson();


1;
