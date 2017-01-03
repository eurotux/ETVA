#!/usr/bin/perl
# module with function for asterisk

package utilasterisk;

use strict;

use IO::Socket;

# constants
use constant {
    ChannelStateDown => 0,
    ChannelStateRing => 4,
    ChannelStateRinging => 5,
    ChannelStateUp => 6
};

# common functions

#  connect to asterisk
sub connect {
    my ($host, $port) = @_;

    my $sock = new IO::Socket::INET (
        PeerAddr => $host,
        PeerPort => $port,
        Proto => 'tcp',
        );

    die "Could not create socket: $!\n" unless $sock;

    return $sock;
}

# send request
my $action_id = 1;
sub send_request {
    my $sock = shift;
    my ($action, %params) = @_;

    my %result = ( 'action'=>$action, 'action_id'=>$action_id );
    print $sock "Action: $action\r\n";
    print $sock "ActionID: $action_id","\r\n";
    for my $k (keys %params){
        my $v = $params{"$k"};
        print $sock "$k: $v\r\n";
    }
    print $sock "\r\n";

    $action_id++;   # inc action_id

    return wantarray() ? %result : \%result;
}

# read packet from socket
sub read_packet {
    my ($sock) = @_;

    local $/ = "\r\n\r\n";

    my $line = <$sock>;

    my %packet = ();
    foreach my $el (split(/\r\n/,$line)){
        my ($key,$value) = split(/:\s+/, $el,2);
        $packet{"$key"} = $value;
    }
    return wantarray() ? %packet : \%packet;
}

#  read next packet from buffer or from socket
my $inbuffer_packet = [];
sub read_next_packet {
    my ($sock) = @_;
    my $packet;
    if( @$inbuffer_packet ){
        $packet = shift(@$inbuffer_packet)
    } else {
        $packet = &read_packet($sock)
    }
    return wantarray() ? %$packet : $packet;
}
#  wait to response for action_id
sub wait_response {
    my ($sock,$action_id) = @_;

    my $response;
    my $events = [];

    my @tmpbuffer = ();
    while( my %packet = &read_next_packet($sock) ){
        if( !$action_id || ($packet{'ActionID'} && ($packet{'ActionID'} eq $action_id)) ){
            if( $packet{'Event'} ) {
                push(@$events, { %packet });
                last if( $packet{'EventList'} && ($packet{'EventList'} eq 'Complete') );
            } elsif( $packet{'Response'} ){
                $response = { %packet };
                last if( !$packet{'EventList'} );
            }
        } else {
            push(@tmpbuffer, { %packet });
        }
    }
    $inbuffer_packet = [ @tmpbuffer ];

    $response->{'_Events_'} = $events if( @$events );

    return wantarray() ? %$response : $response;
}

#  send request and receive response
sub request_response {
    my $sock = shift;
    my $action = shift;

    my $A = &send_request($sock, $action, @_);
    return &wait_response($sock, $A->{'action_id'});
}

# supported methods

sub failed {
    my ($sock, $desc, $action_id) = @_;

    my $R = &wait_response($sock, $action_id);

    return 0 if ( $R->{'Response'} eq "Success" );

    print "Dial failed: $desc\n";
    return 1;
}

sub login {
    my ($sock, $user, $pass) = @_;
    my $A = &send_request($sock, 'Login', ('UserName'=>$user, 'Secret'=>$pass));

    exit 1 if failed($sock, "authentication", $A->{'action_id'});
    return 0;
}
sub logoff {
    my ($sock) = @_;
    return &request_response($sock, 'Logoff');
}

sub is_extension_available
{
    my ($sock, $extension) = @_;

    my $R = &request_response($sock, 'ExtensionState', ('Context'=>'from-internal', 'Exten'=>$extension));

    if ( $R->{'Response'} eq "Success" ){
        my $s = $R->{'Status'};
        return 1 if( $s == 0 );

        our %status = (
            -1, "Extension not found",
            0, "Idle",
            1, "In Use",
            2, "Busy",
            4, "Unavailable",
            8, "Ringing",
            16, "On Hold ",
        );
        print "Skipping extension $extension: ", $status{$s}, "\n";
    } else {
        print "Error getting status of extension $extension\n";
    }
    return 0;
}

sub get_channels {
    my ($sock) = @_;

    my $R = &request_response($sock, 'CoreShowChannels');
    if( $R->{'Response'} eq 'Success' ){
        if( my $events = $R->{'_Events_'} ){
            return wantarray() ? @$events : $events;
        }
    }
    return;
}
sub get_extension_channels {
    my ($sock, $extension) = @_;

    if( my $channels = &get_channels($sock) ){
        my @ext_channels = grep { ($_->{'CallerIDnum'} eq $extension) || $_->{'Channel'} =~ m/\/${extension}-/ } @$channels;

        return wantarray() ? @ext_channels : \@ext_channels;
    }
    return;
}
sub get_extension_channel_for_number {
    my ($sock, $extension, $number) = @_;

    if( my $channels = &get_extension_channels($sock,$extension) ){
        if( my ($C) = grep { ($_->{'ConnectedLineNum'} eq $number) } @$channels ){
            #print STDERR "[DEBUG] hangup channel Channel=$C->{'Channel'} CallerIDnum=$C->{'CallerIDnum'} ConnectedLineNum=$C->{'ConnectedLineNum'} ApplicationData=$C->{'ApplicationData'} ChannelState=$C->{'ChannelStateDesc'}","\n";
            return $C;     # Case CallerIDnum=601 to ConnectedLineNum=266
        } elsif( my ($C) = grep { ($_->{'ApplicationData'} =~ m/\/\+?\d*${number}\D/) } @$channels ){
            #print STDERR "[DEBUG] hangup channel Channel=$C->{'Channel'} CallerIDnum=$C->{'CallerIDnum'} ConnectedLineNum=$C->{'ConnectedLineNum'} ApplicationData=$C->{'ApplicationData'} ChannelState=$C->{'ChannelStateDesc'}","\n";
            return $C;     # Case Channel=SIP/409-00000269 to ApplicationData=SIP/VoipBuster/00351265623651,300,
        } elsif( my ($C) = grep { ($_->{'ChannelState'} == ChannelStateRinging) } @$channels ){
            #print STDERR "[DEBUG] hangup channel Channel=$C->{'Channel'} CallerIDnum=$C->{'CallerIDnum'} ConnectedLineNum=$C->{'ConnectedLineNum'} ApplicationData=$C->{'ApplicationData'} ChannelState=$C->{'ChannelStateDesc'}","\n";
            return $C;     # Case CallerIDnum=601 ChannelState=5 (Ringing)
        }
    }
    return;
}

sub hangup {
    my ($sock, $extension, $number) = @_;

    if( my $C = &get_extension_channel_for_number( $sock, $extension, $number) ){
        return &request_response($sock, 'Hangup', 'Channel'=>$C->{'Channel'} );
    }
}

sub dial {
    my ($sock, $extension, $number) = @_;

    return &request_response($sock, 'Originate',
                                        'Channel' => "SIP/$extension",
                                        'Context' => 'from-internal',
                                        'Exten'   => $number,
                                        'Priority'=> 1,
                                        'Callerid'=> $extension );
}

sub modemdial {
    my ($sock, $app, $number, $caller) = @_;

    $caller = "Modem <$app>" if( !$caller );

    return &request_response($sock, 'Originate',
                                        'Channel' => "Local/$number\@from-internal/n",
                                        'Context' => 'from-internal',
                                        'Exten'   => $app,
                                        'Priority'=> 1,
                                        'Callerid'=> $caller );
}

sub get_host_for_extension {
    my ($sock, $extension) = @_;

    my $R = &request_response($sock, 'SIPpeers');
    if( $R->{'Response'} eq 'Success' ){
        if( my $events = $R->{'_Events_'} ){
            if( my ($A) = grep { ($_->{'ObjectName'} eq $extension) } @$events ){
                if( $A->{'IPaddress'} =~ m/\d+\.\d+\.\d+.\d+/ ){
                    return $A->{'IPaddress'};
                }
            }
        }
    }
    return;
}

1;
