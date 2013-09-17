#!/usr/bin/perl

use strict;

use ETVA::Utils;

use IO::Select;
use IO::Socket;

use IO::Handle;

use POSIX;

use Event::Lib;

use Data::Dumper;

my $dir_guest_management_sockets = "/var/tmp/virtagent-guestmngt-sockets-dir/";

my %handled_sockets = ();

# Invoked when the client's socket becomes readable

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
    my $fh = shift;

    &nonblock($fh);

    my $data = "";              #  data
    my $rd = "";                # read result
    my $part;                   # partial read
    while (($rd = read($fh,$part,POSIX::BUFSIZ,)) >= 0) {  # read data from socket
        if (defined $rd) {      # undef means failure
            $data .= $part if ($rd>0);  # join parts
        } else {
            last;
        }
    }

    return $data;
}
sub handle_guestmessage {
    my $e = shift;
    my $h = $e->fh;

    plog "handle_guestmessage";
    my $message=&receive($h);
    plog "receive message = $message";
}

sub open_io_socket {
    my $path = shift;
    my $my_addr = sockaddr_un($path);

    my $sock;
    socket($sock,PF_UNIX,SOCK_STREAM,0);

    my $s = connect($sock, $my_addr);

    return $sock;

=com
    return new IO::Socket::UNIX (
            Peer     => $path,
            Type     => SOCK_STREAM,
            Blocking    => 0
        );
=cut
}

# Invok each T_ALARM seconds
# Alarm ttl
my $T_ALARM = 5 * 60;
sub handle_time_newsockets {
    my $e = shift;

    plog "new time tick t=$T_ALARM";

    my $dirsockets;
    opendir($dirsockets,"$dir_guest_management_sockets");
    my @list_sockets = readdir($dirsockets);
    foreach my $fsock (@list_sockets){
        my $fp_sock = "$dir_guest_management_sockets/$fsock";
        if( (-S "$fp_sock") && !$handled_sockets{"$fsock"} ){
            plog "new socket $fsock";
            $handled_sockets{"$fsock"} = 1;

            my $unix_socket = &open_io_socket($fp_sock);
            my $eguest = event_new($unix_socket, EV_READ|EV_PERSIST, \&handle_guestmessage);
            $eguest->add;
        }
    }
    close($dirsockets);

    $e->add($T_ALARM);
}

sub main {

    my $etimer = timer_new(\&handle_time_newsockets);

    $_->add for $etimer;

    event_mainloop;
}

&main();

1;
