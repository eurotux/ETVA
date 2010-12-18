#!/usr/bin/perl

use strict;

sub main {
    use Data::Dumper;

    require ETFW::SNMP;

    my $C = ETFW::SNMP->get_config();

    print Dumper($C),"\n";

    ETFW::SNMP->add_directive( directive=>"sysservices", value=>"0" );
    ETFW::SNMP->add_directive( directive=>"syslocation", value=>"Lisboa, Portugal" );
    ETFW::SNMP->add_directive( directive=>"syscontact", value=>"Tecnica Eurotux <tecnica@…>");

    ETFW::SNMP->add_directive( directive=>"rocommunity", value=>"r3ad0n1y");
    ETFW::SNMP->add_directive( directive=>"trapcommunity", value=>"r3ad0n1y");
    ETFW::SNMP->add_directive( directive=>"trapsink", value=>"172.16.40.11");

    ETFW::SNMP->add_directive( directive=>"disk", value=>"/");
    ETFW::SNMP->add_directive( directive=>"disk", value=>"/var");
    ETFW::SNMP->add_directive( directive=>"disk", value=>"/usr");
    ETFW::SNMP->add_directive( directive=>"disk", value=>"/tmp");
    ETFW::SNMP->add_directive( directive=>"swap", value=>"16000");
    ETFW::SNMP->add_directive( directive=>"linkUpDownNotifications", value=>"yes");
    ETFW::SNMP->add_directive( directive=>"defaultMonitors", value=>"yes");
    ETFW::SNMP->add_directive( directive=>"gentSecName", value=>"eurotux");
    ETFW::SNMP->add_directive( directive=>"rwuser", value=>"eurotux");
    ETFW::SNMP->add_directive( directive=>"master", value=>"agentx");

    ETFW::SNMP->add_directive( directive=>"proc", value=>"httpd 0 0");
    
    my $C = ETFW::SNMP->get_config();

    print Dumper($C),"\n";

    ETFW::SNMP->del_directive( directive=>"gentSecName", value=>"eurotux");

    ETFW::SNMP->add_security( secname=>'local', source=>'localhost', community=>'zbr' );
    ETFW::SNMP->add_security( secname=>'mymonhost', source=>'172.16.42.249/32', community=>'x1p2t3o4' );
    ETFW::SNMP->add_security( secname=>'mymonhost', source=>'172.16.42.224/32', community=>'x1p2t3o4' );

    ETFW::SNMP->add_group( groupname=>'MyRWGroup', securitymodel=>'v1', securityname=>'local' );
	ETFW::SNMP->add_group( groupname=>'MyRWGroup', securitymodel=>'v2c', securityname=>'local' );
	ETFW::SNMP->add_group( groupname=>'MyRWGroup', securitymodel=>'usm', securityname=>'local' );
	ETFW::SNMP->add_group( groupname=>'MyROGroup', securitymodel=>'v1', securityname=>'mymonhost' );
	ETFW::SNMP->add_group( groupname=>'MyROGroup', securitymodel=>'v2c', securityname=>'mymonhost' );
	ETFW::SNMP->add_group( groupname=>'MyROGroup', securitymodel=>'usm', securityname=>'mymonhost' );
	ETFW::SNMP->add_group( groupname=>'MyROGroup', securitymodel=>'v1', securityname=>'mymonhost2' );
	ETFW::SNMP->add_group( groupname=>'MyROGroup', securitymodel=>'v2c', securityname=>'mymonhost2' );
	ETFW::SNMP->add_group( groupname=>'MyROGroup', securitymodel=>'usm', securityname=>'mymonhost2' );

	ETFW::SNMP->add_view( name=>'all', inc_exc=>'included', subtree=>'.1', mask=>'80' );

    ETFW::SNMP->add_access( group=>'MyROGroup', context=>'""', secmodel=>'any', seclevel=>'noauth', prefix=>'exact', 'read'=>'all', 'write'=>'none', notif=>'none' );
    ETFW::SNMP->add_access( group=>'MyRWGroup', context=>'""', secmodel=>'any', seclevel=>'noauth', prefix=>'exact', 'read'=>'all', 'write'=>'all', notif=>'none' );

    my $C = ETFW::SNMP->get_config();

    print Dumper($C),"\n";
}
main();
1;
