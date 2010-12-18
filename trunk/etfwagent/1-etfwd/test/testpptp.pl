#!/usr/bin/perl

sub main {
    use Data::Dumper;

    use ETFW::PPTP;

#    my $server = new ETFW::PPTP::Server();

    my $C = ETFW::PPTP::Server->get_config();

    print STDERR Dumper($C),"\n";

    my $L = ETFW::PPTP::Server->list_connections();

    print STDERR Dumper($L),"\n";

#    my $client = new ETFW::PPTP::Client();

    my $list = ETFW::PPTP::Client->list_tunnels();

    print Dumper($list),"\n";

    ETFW::PPTP::Client->add_tunnel( tunnel=>"teste", server=>"10.10.10.1" );

    my $list = ETFW::PPTP::Client->list_tunnels();

    print Dumper($list),"\n";

    ETFW::PPTP::Client->del_tunnel( tunnel=>"teste" );

}

main();

1;
