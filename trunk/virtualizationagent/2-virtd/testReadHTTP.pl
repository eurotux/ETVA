#!/usr/bin/perl
#

use strict;

use IO::Socket;
use POSIX;

# nonblock($socket) puts socket into nonblocking mode
# Perl Cookbook
sub nonblock {
     my $socket = shift;
     my $flags;

     $flags = fcntl($socket, F_GETFL, 0)
        or die "Can't get flags for socket: $!\n";
     fcntl($socket, F_SETFL, $flags | O_NONBLOCK)
        or die "Can't make socket nonblocking: $!\n";
}
sub receive {
    my ($fh) = @_;

    &nonblock($fh); # non block

    my $ready = 0;              # all data received
    my $data = "";              #  data
    my $rd = "";                # read result
    my $part;                   # partial read

    while (!$ready && ($rd = read($fh,$part,4096)) >= 0) {  # read data from socket
        if (defined $rd) {      # undef means failure
            if( $rd > 0 ){
                $data .= $part;  # join parts
                $ready = 1;
            }
        }
    }
    return $data;
}
sub main {

    #my $peer = "/var/tmp/virtagent-guestmngt-sockets-dir/filesrv";
    #my $sock = new IO::Socket::UNIX (
    #    Peer     => $peer,
    #    Type     => SOCK_STREAM
    #) or die "couldn't open socket: $!";

    my $sock = new IO::Socket::INET (
                    PeerAddr => '10.10.4.234',
                    PeerPort => '9009',
                    Proto    => 'tcp'
                ) or die "couldn't open socket: $!";


    my $ttl = 10;
    my $dt;
    my $b_size = 0;

    my $rd = "";                # read result
    my $part;                   # partial read

    while (($rd = read($sock,$part,4096)) >= 0) {  # read data from socket
        if (defined $rd) {      # undef means failure
            if( $rd > 0 ){
                if( !$dt || ((time() - $dt) == $ttl) ){
                    my $secs = $ttl;
                    my $speed = $secs ? ($b_size / $secs) : 0;
                    print STDERR "b_size=$b_size speed=$speed b/s","\n";
                    sleep(1);
                    $b_size = 0;
                    $dt = time();
                }
                $b_size += length($part);
            }
        }
    }
}

&main();

1;
