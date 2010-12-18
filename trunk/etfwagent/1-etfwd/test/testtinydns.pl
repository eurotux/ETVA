#!/usr/bin/perl

sub main {
    use Data::Dumper;

    require ETFW::TinyDNS;

   my @lips = ETFW::TinyDNS::DNSCache->list_ips();
    
    print STDERR "ips=",Dumper(\@lips),"\n";

	my @lservers = ETFW::TinyDNS::DNSCache->list_servers();

	print STDERR "servers=",Dumper(\@lservers),"\n";

	ETFW::TinyDNS::DNSCache->add_server( server=>"10.10.10.1" );

	my @lservers = ETFW::TinyDNS::DNSCache->list_servers();

	print STDERR "servers=",Dumper(\@lservers),"\n";

	ETFW::TinyDNS::DNSCache->del_server( server=>"10.10.10.1" );

	my @lzones = ETFW::TinyDNS::DNSServer->list_zones();

	print STDERR "zones=",Dumper(\@lzones),"\n";

	my @lhosts = ETFW::TinyDNS::DNSServer->list( type=>'host',zone=>'eurotux.com' );

	print STDERR "hosts=",Dumper(\@lhosts),"\n";

}
main();
