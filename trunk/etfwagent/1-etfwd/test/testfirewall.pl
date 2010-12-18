#!/usr/bin/perl

sub main {

    use Data::Dumper;

    require ETFW::Firewall;

    my $lrules = ETFW::Firewall->load_config( direct=>1 );
    
    print STDERR Dumper($lrules),"\n";

    ETFW::Firewall->add_rule( table=>"nat", chain=>"POSTROUTING", "s"=>"192.168.1.0/255.255.255.0","d"=>"! 192.168.1.0/255.255.255.0","j"=>"MASQUERADE" );

    my $lrules = ETFW::Firewall->load_config( direct=>1 );
    
    print STDERR Dumper($lrules),"\n";

    ETFW::Firewall->del_rule( table=>"nat", chain=>"POSTROUTING", "s"=>"192.168.1.0/255.255.255.0","d"=>"! 192.168.1.0/255.255.255.0","j"=>"MASQUERADE" );

    my $lrules = ETFW::Firewall->load_config( direct=>1 );
    
    print STDERR Dumper($lrules),"\n";

    ETFW::Firewall->open_port( direct=>1, dport=>80, dest=>'10.10.10.1' );

    my $lrules = ETFW::Firewall->load_config( direct=>1 );
    
    print STDERR Dumper($lrules),"\n";

    ETFW::Firewall->forward_port( direct=>1, dport=>8080, dest=>'10.10.10.2', sport=>80 );
}
main();
1;
