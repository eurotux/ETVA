#!/usr/bin/perl

use strict;

sub main {

    use Data::Dumper;

    require ETFW::Radiusclient;

    my $C = ETFW::Radiusclient->get_config();

    print STDERR Dumper($C),"\n";

    my $bkp_login_tries = $C->{"login_tries"};
    ETFW::Radiusclient->set_config( login_tries=>3 ); 

    my $C = ETFW::Radiusclient->get_config();

    print STDERR Dumper($C),"\n";

    ETFW::Radiusclient->set_config( login_tries=>$bkp_login_tries ); 

}
main();

1;
