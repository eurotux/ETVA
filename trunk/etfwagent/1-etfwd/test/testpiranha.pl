#!/usr/bin/perl

use strict;

sub main {
    use Data::Dumper;

    require ETFW::Piranha;

    my $C = ETFW::Piranha->get_config();

    print Dumper($C),"\n";

    ETFW::Piranha->add_virtual( name=>"vs1", address=>192.168.122.1 ,port=>80, protocol=>"tcp" );

    ETFW::Piranha->add_server( virtual=>"vs1", name=>"bla",port=>80, address=>10.10.20.106 protocol=>"tcp" );

    my $C = ETFW::Piranha->get_config();

    print Dumper($C),"\n";

    ETFW::Piranha->set_server( virtual=>"vs1", name=>"bla",port=>8080 );
    my $C = ETFW::Piranha->get_config();

    print Dumper($C),"\n";

    my $R = ETFW::Piranha->view_routingtable();

    print Dumper($R),"\n";

    ETFW::Piranha->del_server( virtual=>"vs1", name=>"bla" );

}
main();
1;
