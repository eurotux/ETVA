#!/usr/bin/perl

use strict;

sub main {
    use Data::Dumper;

    require ETFW::PPPClient;

    my $C = ETFW::PPPClient->get_config();

    print Dumper($C),"\n";

    ETFW::PPPClient->set_config( dialer=>"tmn", "key1"=>"username","value1"=>"tmn", "key2"=>"password", "value2"=>"ola123" );

    my $C = ETFW::PPPClient->get_config();

    print Dumper($C),"\n";

}

main();

1;
