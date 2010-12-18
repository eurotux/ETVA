#!/usr/bin/perl

sub main {
    use Data::Dumper;

    require ETFW::Squid;

    my $C = ETFW::Squid->get_enabled_config();

    print Dumper($C),"\n";

    ETFW::Squid->set_config( "http_port"=>[ "3128" ]);

    my $C = ETFW::Squid->get_enabled_config();

    print Dumper($C),"\n";

    ETFW::Squid->add_http_port( port=>"1234" );
    ETFW::Squid->add_http_port( port=>"7890" );

    my $C = ETFW::Squid->get_enabled_config();

    print Dumper($C),"\n";

    ETFW::Squid->del_http_port( port=>"7890" );

    my $C = ETFW::Squid->get_enabled_config();

    print Dumper($C),"\n";

    ETFW::Squid->set_http_port( port=>"3128" );

}

main();
1;
