#!/usr/bin/perl

use strict;

sub main {

    use Data::Dumper;

    require ETFW::Dansguardian;

    my $C = ETFW::Dansguardian->get_conf();

    print STDERR Dumper($C),"\n";

    ETFW::Dansguardian->add_banned_ip( value=>"10.10.10.99" );

    ETFW::Dansguardian->del_banned_ip( value=>"10.10.10.99" );

}
main();

1;
