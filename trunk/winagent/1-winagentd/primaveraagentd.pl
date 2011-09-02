#!/usr/bin/perl

package Main;

use strict;

use POSIX qw/SIGHUP/;

#use ETVA::Agent::SOAP;
use ETVA::Agent::SOAPFork;
use ETVA::Client::SOAP::HTTP;
use ETVA::Utils;

use Data::Dumper;

my %CONF = ( 'CFG_FILE'=>'primaveraagentd.ini' );

sub main {
    
    require PrimaveraDispatcher;

    &loadconf();
    PrimaveraDispatcher->init_conf( %CONF );

    # initialization agent
    my $agent = ETVA::Agent::SOAPFork->new( 'LocalPort'=>'7000', '_dispatcher'=>'PrimaveraDispatcher', debug=>1, %CONF, '_register_handler'=>\&register_handler );

    # allways fork
    *ETVA::Agent::SOAPFork::isForkable = sub { shift; return ( shift =~ /change_ip/ ) ? 0 : 1; };

    if( $agent ){
        # start loop
    return $agent->mainLoop(); 
    }
    return;
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
        ETVA::Utils::plog( "macaddr = $agent->{'macaddr'} ip=$agent->{'LocalIP'}");
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

    # get first ethernet adapter
    my ($I) = sort { $a->{'description'} cmp $b->{'description'} } WinDispatcher::get_ipconfig();

    if( !$CONF{'macaddr'} || !$CONF{'LocalIP'} ||
            ( $CONF{'LocalIP'} eq '127.0.0.1' ) ||
            ( $CONF{'LocalIP'} ne "$I->{'ipaddr'}" ) ){

        plog( "IP address changed '$I->{'ipaddr'}'" ) if( $CONF{'LocalIP'} ne "$I->{'ipaddr'}" );

        $CONF{'macaddr'} = $I->{'macaddr'};
        $CONF{'LocalIP'} = $CONF{'IP'} = $I->{'ipaddr'};

        # write to config file
        ETVA::Utils::set_conf($CONF{'CFG_FILE'},%CONF);
    }
}

while( &main() == SIGHUP ){
    sleep 10;
}

1;
