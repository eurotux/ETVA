#!/usr/bin/perl
#

use strict;

use IO::Socket;

my %queue_write = ();
sub main {
    my $server = new IO::Socket::INET (
                                        Listen => 1,
                                        LocalPort => 9009,
                                        Proto => 'tcp',
                                        ReuseAddr => 1
                ) or die "couldn't open socket: $!";

    my $new = $server->accept();
    # Set REUSEADDR flag
    $new->sockopt(SO_REUSEADDR,1) or die("can't sockop!");

    print STDERR "accept...","\n";

    while(1){
        #print $sockport rand(time()) x 4096;
        print $new 0 x 4096;
    }
}

&main();

1;
