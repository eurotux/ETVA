#!/usr/bin/perl

use strict;

use ETVA::Utils;

use ETVA::GuestHandle;

use POSIX;

my $dir_guest_management_sockets = "/var/tmp/virtagent-guestmngt-sockets-dir/";

sub main {

    my $guestHandle = new ETVA::GuestHandle(
                                        LocalAddr   => '127.0.0.1',
                                        LocalPort   => 9009,
                                        Proto       => 'tcp',
                                        'sockets_dir' => $dir_guest_management_sockets,
                                        'cm_uri' => 'http://127.0.0.1/soapapi.php'
                        );

    $guestHandle->mainLoop();
}

&main();

1;
