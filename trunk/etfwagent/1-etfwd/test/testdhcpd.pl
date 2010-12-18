#!/usr/bin/perl

use strict;

sub main {
    use Data::Dumper;

    require ETFW::DHCP;

    my %Conf = ETFW::DHCP->load_config( "conf_file"=>"/etc/dhcpd.conf" );

    print Dumper(\%Conf),"\n";
    
    ETFW::DHCP->add_host( "conf_file"=>"/etc/dhcpd.conf", host=>"bla", parameters=>[ {name=>"hardware",value=>"ethernet 00:90:27:bd:86:f3;"},{name=>"fixed-address",value=>"bla.zbr.pt"} ], parent=>{ type=>"subnet",address=>"204.254.239.0",netmask=>"255.255.255.224" } );
    ETFW::DHCP->add_host( "conf_file"=>"/etc/dhcpd.conf", host=>"zbr", parameters=>[ {name=>"hardware",value=>"ethernet 00:90:27:bd:86:f3;"},{name=>"fixed-address",value=>"zbr.zbr.pt"} ], parent=>{ type=>"subnet",address=>"204.254.239.0",netmask=>"255.255.255.224" } );

    ETFW::DHCP->set_option( "conf_file"=>"/etc/dhcpd.conf", parent=>{ type=>"host", host=>"bla" }, name=>"fixed-address",value=>"bla.xpto.pt" );

    %Conf = ETFW::DHCP->load_config( "conf_file"=>"/etc/dhcpd.conf" );

    print Dumper(\%Conf),"\n";

    ETFW::DHCP->set_host( "conf_file"=>"/etc/dhcpd.conf", parent=>{ type=>"subnet",address=>"204.254.239.0",netmask=>"255.255.255.224" }, parameters=>[ {name=>"hardware",value=>"ethernet 00:90:27:bd:86:f3;"},{name=>"fixed-address",value=>"bla.xxx.pt"} ], old=>{ type=>"host", host=>"bla" } );
    
    %Conf = ETFW::DHCP->load_config( "conf_file"=>"/etc/dhcpd.conf" );

    print Dumper(\%Conf),"\n";

    my $R = ETFW::DHCP->list_subnet();
    print Dumper($R);

    my $R = ETFW::DHCP->list_host();
    print Dumper($R);

    my $R = ETFW::DHCP->list_group();
    print Dumper($R);

    my $R = ETFW::DHCP->list_zone();
    print Dumper($R);

    my $R = ETFW::DHCP->list_clientoptions();
    print Dumper($R);

}
main();
1;
