#!/usr/bin/perl

sub main {

    use Data::Dumper;

    require ETFW::Network;

    my %H = ETFW::Network->get_hostname();
    print STDERR Dumper(\%H),"\n";

    ETFW::Network->set_hostname(%H);

    my %if = ETFW::Network->default_interface();
    print STDERR Dumper(\%if),"\n";

    my %IF = ETFW::Network::all_interfaces();

    print STDERR Dumper(\%IF),"\n";
    my %AIF = ETFW::Network::active_interfaces();

    use Data::Dumper;

    print STDERR Dumper(\%AIF),"\n";

    my %BIF = ETFW::Network::boot_interfaces();

    use Data::Dumper;

    print STDERR Dumper(\%BIF),"\n";
    my $lr = ETFW::Network::list_routes();

    print STDERR Dumper($lr),"\n";

    ETFW::Network->create_route( dest=>'192.168.0.0', netmask=>'255.255.255.0', gateway=>'10.10.20.1' );

    my $lr = ETFW::Network::list_routes();

    print STDERR Dumper($lr),"\n";

    ETFW::Network->delete_route( dest=>'192.168.0.0', netmask=>'255.255.255.0', gateway=>'10.10.20.1' );

    my $lr = ETFW::Network::list_routes();

    print STDERR Dumper($lr),"\n";

    my $bkpldns = ETFW::Network::load_dns();

    print STDERR Dumper($bkpldns),"\n";

    ETFW::Network->save_dns( 'domain'=>[ 'udp.eurotux.com','office.eurotux.com' ], nameserver=>[ '10.10.20.2','10.10.10.1' ]  );

    my $ldns = ETFW::Network::load_dns();

    print STDERR Dumper($ldns),"\n";

    ETFW::Network->save_dns( %$bkpldns );

    my $ldns = ETFW::Network::load_dns();

    print STDERR Dumper($ldns),"\n";

    my $lhosts = ETFW::Network::list_hosts();

    print STDERR Dumper($lhosts),"\n";

    ETFW::Network->create_host( address=>'10.10.20.79', hosts=>['cmartux'] );

    my $lhosts = ETFW::Network::list_hosts();

    print STDERR Dumper($lhosts),"\n";

    ETFW::Network->modify_host( address=>'10.10.20.79', hosts=>['cmardesk'] );

    my $lhosts = ETFW::Network::list_hosts();

    print STDERR Dumper($lhosts),"\n";

    ETFW::Network->delete_host( address=>'10.10.20.79' );

    my $lhosts = ETFW::Network::list_hosts();

    print STDERR Dumper($lhosts),"\n";

}
main();
1;
