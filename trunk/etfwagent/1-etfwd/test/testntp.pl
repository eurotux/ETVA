#!/usr/bin/perl

use strict;

sub main {
    use Data::Dumper;

    require ETFW::NTP;

    my $C = ETFW::NTP->get_config();
    
    print STDERR "C=",Dumper($C),"\n";

    ETFW::NTP->add_server( server=>"10.10.10.1" );

}
main();
