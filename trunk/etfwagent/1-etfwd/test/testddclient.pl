#!/usr/bin/perl

sub main {
    use Data::Dumper;

    require ETFW::DDClient;

    my $C = ETFW::DDClient->get_config();

    print Dumper($C),"\n";

    ETFW::DDClient->set_config( server=>{ hosts=>["bla.dyndns.com","zbr.dyndns.com"], protocol=>"web" } );
}
main();

1;
