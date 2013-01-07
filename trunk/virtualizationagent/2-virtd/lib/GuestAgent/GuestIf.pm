#!/usr/bin/perl -w 

package GuestAgent::GuestIf;

use strict;

#BEGIN {
#    # this is the worst damned warning ever, so SHUT UP ALREADY!
#    $SIG{__WARN__} = sub { warn @_ unless $_[0] =~ /Use of uninitialized value/ };
#
#    require IO::Socket::UNIX;
#    use vars qw($VERSION @ISA @EXPORT @EXPORT_OK %EXPORT_TAGS $CRLF $AUTOLOAD);
#    $VERSION = '0.0.1';
#    @ISA = qw( IO::Socket::UNIX );
#    @EXPORT = qw( );
#}

use POSIX qw/SIGHUP SIGTERM SIGKILL/;
use POSIX ":sys_wait_h";
use POSIX;
use Data::Dumper;
#use IO::Socket;
use JSON;
use Data::Dumper;

use GuestAgent::Message;
use ETVA::Utils;

use IO::Socket::UNIX;
use IO::Socket::UNIX qw( SOCK_STREAM );

use Fcntl;



use constant {
    DEFAULT_TIMEOUT => 10,
};

#sub AUTOLOAD {
#     my $method = $AUTOLOAD;
#     my $self = shift;
#
#     if( my ($request_class,$m) = ($method =~ m/(.*)::(.+)/) ){
#             $self->{'sock'}->$m(@_);
#     }
#}

# $guestif = GuestIf->new('name' => 'centos6', 
#    'path' => '/tmp/foo/pipe', 'state_file' = "vm state filename", ['timeout' => 10])
sub new{
    my $self = shift;
    my %p = @_;

    unless( ref $self ){
        my $class = ref($self) || $self;
        my %M = %p;

        $M{'queue'}     = [];
        $M{'vmname'}    = $p{'vmname'};
        $M{'msgid'}     = 0;
        $M{'waitingqueue'} = {};

        $M{'socket'} = IO::Socket::UNIX->new(
            'Type'      => SOCK_STREAM,
            'Peer'      => $p{'path'},
            'Timeout'   => $p{'timeout'} || DEFAULT_TIMEOUT,
            #'Blocking'  => ($p{'blocking'}||0)
        ) or die("Can't connect to server: $!\n");


        $self = bless {%M} => $class;

    }

    return $self;
}
sub getWaitingForResp(){
    my $self = shift;
    my %p = @_;
    
    return unless defined $p{'res'};

    my $obj = decode_json $p{'res'};

    my $va = $self->{'waitingqueue'}{$obj->{'id'}};
    if($va){
        delete $self->{'waitingqueue'}{$obj->{'id'}};
    }
    return $va;
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

sub getIdAndInc{
    my $self = shift;
    my $id = $self->{'msgid'};
    $self->{'msgid'}++;
    return $id;
}


sub printSktState{
    my $self = shift;
    my $skt = $self->{'socket'};
    print 'Write connected? ', $skt->connected() ? 'yes' : 'no', "\n";
    print 'Write opened? '   , $skt->opened() ? 'yes' : 'no', "\n";
    print 'Write errored? '  , $skt->error() ? 'yes' : 'no', "\n";
    print 'Write peerpath: ' , $skt->peerpath(), "\n";
    print 'inode : '          , (stat($skt->peerpath()))[1], "\n";
}

sub _openStateFile{
    my $self = shift;
    my $filename = $self->{'state_file'};
#    print "opening file: $filename\n";
    open FILE, ">$filename" or die "could not open the file: $!";
#    open FILE, ">/tmp/foo/$filename" or die "could not open the file: $!";
    FILE->autoflush(1);
    return \*FILE;
}

sub read{
    my $self = shift;
    my $s = $self->{'socket'};

    if (my $line = <$s>){
        $self->_updateState('msg' => $line);
#        if($ENV{'DEBUG'}){
            $self->_writeState();
#        }
        return $line;
    }else{
        return 0;
    }
}


sub _updateState{
    my $self = shift;
    my %p = @_;

    my $obj = decode_json $p{'msg'};
    $obj->{'timestamp'} = time;
#    $self->{'state'}{$obj->{'__name__'}} = $obj;
    if(defined $self->{'state'}{$obj->{'__name__'}}){
        my %old = %{ $self->{'state'}{$obj->{'__name__'}} };

        @old{keys %$obj} = values %$obj;
        $self->{'state'}{$obj->{'__name__'}} = \%old;
    }else{
        $self->{'state'}{$obj->{'__name__'}} = $obj;
    }

#    if($obj->{'__name__'} == 'etasp'){
#        my %heartbeat = (
#            'free-ram'  => $obj->{'free-ram'},
#            'timestamp' => time,
#        );
##        print Dumper \%heartbeat;
#        $self->{'state'}{'heartbeat'} = \%heartbeat;
#    }

#    if($obj->{'__name__'} == 'heartbeat'){
#        my %heartbeat = (
#            'free-ram'  => $obj->{'free-ram'},
#            'timestamp' => time,
#        );
##        print Dumper \%heartbeat;
#        $self->{'state'}{'heartbeat'} = \%heartbeat;
#    }
    return $obj; 
}

sub _writeState{
    my $self = shift;
    my $fh = $self->_openStateFile('filename' => $self->{'state_file'});

    my $state_json;
#    if($ENV{'DEBUG'}){
        $state_json = to_json($self->{'state'}, {utf8 => 1, pretty => 1});
#    }else{
#        $state_json = encode_json($self->{'state'});
#    }
    
    print $fh $state_json;
    close $fh;
}

sub getState{
    my $self = shift;

#    my $state_json;
#    if($ENV{'DEBUG'}){
#        $state_json = to_json($self->{'state'}, {utf8 => 1, pretty => 1});
#    }else{
#        $state_json = encode_json($self->{'state'});
#    }
#    return $state_json;    
    return $self->{'state'};
}

sub sendCmd{
    my $self = shift;
    my %p = @_;
    
    my $msgObj = $p{'msg'};
    my $s = $self->{'socket'};
    if($msgObj->isValid()){
        #$s->autoflush(1);
        #print $s $msgObj->toJson() or die "$!";    
        eval {
            my $strj = $msgObj->toJson();
            plog("GuestAgent::GuestIf sendCmd json",$strj) if( &debug_level > 9 );
            #$s->send($strj) or die "GuestAgent::GuestIf sendCmd: send - $!";
            socketPrint($s,$strj);
        };
        if( $@ ){
            plog("GuestAgent::GuestIf sendCmd : cannot send commad");
            return 0;
        }
        $s->flush;
        return 1;
    }
    return 0;
}

sub getVmName{
    my $self = shift;
    return $self->{'vmname'};
}

sub getSocket{
    my $self = shift;
    return $self->{'socket'};
}

sub socketPrint {
    my ($fh,$dmp) = @_;

    # quick fix: put it in blocking mode
    my $flags = fcntl($fh, F_GETFL, 0)
        or die "Can't get flags for socket: $!\n";

    my $nflags = ($flags & (0xffffffff & ~O_NONBLOCK));
    fcntl($fh, F_SETFL, $nflags)
        or die "Can't make socket nonblocking: $!\n";

    my $sc_errno = 0;
    if (! print $fh $dmp) {             # try to print
        $sc_errno = $!;                 # set error code if print failed
    }

    # revert to original flags
    fcntl($fh, F_SETFL, $flags)
        or die "Can't make socket nonblocking: $!\n";
}

1;
