#!/usr/bin/perl

package Main;

use strict;

use ETVA::Agent::SOAP;
use ETVA::Client::SOAP::HTTP;
use ETVA::Utils;

use Data::Dumper;

my %CONF = ( 'CFG_FILE'=>'winagentd.ini' );

sub main {
    
    require WinDispatcher;

    &loadconf();
    WinDispatcher->init_conf( %CONF );

    # initialization agent
    my $agent = ETVA::Agent::SOAP->new( 'LocalPort'=>'7000', '_dispatcher'=>'WinDispatcher', debug=>1, %CONF, '_register_handler'=>\&register_handler );

    if( $agent ){
        # start loop
        $agent->mainLoop();
    }
}

sub register_handler {
    my $agent = shift;
    if( $agent->{'cm_uri'} ){
        my $now = &now();
        my $R = new ETVA::Client::SOAP::HTTP( uri => $agent->{'cm_uri'} )
                    -> call( $agent->{'cm_namespace'},
                             "initAgentServices",
                                name=>$agent->{'Type'} || 'WINAGENT',
                                ip=>$agent->{'LocalIP'},
                                port=>$agent->{'LocalPort'},
                                macaddr=>$agent->{'macaddr'},
                                services=>[{'name'=>'main'}]
                            );
	ETVA::Utils::plog( "macaddr = $agent->{'macaddr'} ");
        if( !$R || $R->{'_error_'} ){
            ETVA::Utils::plog("Cant connect to CentralManagement.\nInitialization Agent aborted!");
        } else {
            if( ref($R->{'return'}) && $R->{'return'}{'success'} ne 'false' ){
                ETVA::Utils::plog("$now - Agent register with success on CentralManagement");
            }
        }
    }
}

sub loadconf {

    my $cfg_file = $ENV{'CFG_FILE'} || $CONF{'CFG_FILE'};
    plog("loadconf CFG_FILE = '$cfg_file'") if( &debug_level() > 5 );
    %CONF = ETVA::Utils::get_conf(1,$cfg_file);

    my ($I) = WinDispatcher::get_ipconfig();
    $CONF{'macaddr'} = $I->{'macaddr'};
}

&main();

1;
