#!/usr/bin/perl

package Main;

use strict;

use utf8;

use POSIX;

#use ETVA::Agent::SOAP;
#use ETVA::Agent::SOAPFork;
#use ETVA::Client::SOAP::HTTP;

use ETVA::GuestAgent::Management;

use ETVA::Utils;

use Data::Dumper;

my $AgentClass = 'ETVA::Agent::SOAPFork';

my %CONF = ( 'CFG_FILE'=>'primaveraagentd.ini' );

sub main {
    
    require PrimaveraDispatcher;

    &loadconf();

    PrimaveraDispatcher->init_conf( %CONF );

    my %C = ();
    $C{'_chldhandler_'} = \&chld_handler;

    my $agent = new ETVA::GuestAgent::Management( 'LocalPort'=>'7000', '_dispatcher'=>'PrimaveraDispatcher', debug=>1, %CONF, '_register_handler'=>\&register_handler, %C );
    return $agent->mainLoop(); 
}

sub chld_handler {

    # don't change $! and $? outside handler
    local ($!, $?);

    # wait for die pid
    my $dead_pid = waitpid(-1,&WNOHANG);

    while($dead_pid > 0){

        #last unless WIFEXITED($?);
        #last unless &chld_exists($dead_pid);
        &chld_dies_handler($dead_pid);

        $dead_pid = waitpid(-1,&WNOHANG);
    }
}

sub chld_dies_handler {
    my ($dead_pid) = @_;

    my $ch = $AgentClass->can('chld_dies_handler');
    if( $ch ){
        &$ch($dead_pid);
    }
}

sub register_handler {
    my $self = shift;
    if( $self->{'cm_uri'} ){
        my $now = &now();
        my $R = $self->call( $self->{'cm_namespace'},
                             "initAgentServices",
                                name=>$self->{'Type'} || 'WINAGENT',
                                ip=>$self->{'LocalIP'},
                                port=>$self->{'LocalPort'},
                                macaddr=>$self->{'macaddr'},
                                services=>[{'name'=>'main'}]
                            );
        ETVA::Utils::plogNow(__PACKAGE__," macaddr = $self->{'macaddr'} ip=$self->{'LocalIP'}");
        if( !$R || $R->{'_error_'} ){
            ETVA::Utils::plogNow(__PACKAGE__," Cant connect to CentralManagement.\nInitialization Agent aborted!");
        } else {
            if( ref($R->{'return'}) && $R->{'return'}{'success'} ne 'false' ){
                ETVA::Utils::plogNow(__PACKAGE__," - Agent register with success on CentralManagement");
            }
        }
    }
}

sub loadconf {

    my $cfg_file = $ENV{'CFG_FILE'} || $CONF{'CFG_FILE'};
    plog("loadconf CFG_FILE = '$cfg_file'") if( &debug_level() > 5 );
    %CONF = ETVA::Utils::get_conf(1,$cfg_file);

    # get first ethernet adapter
    my ($I) = sort { $a->{'description'} cmp $b->{'description'} } grep { $_->{'gateway'}} WinDispatcher::get_ipconfig();

    if( !$CONF{'macaddr'} || !$CONF{'LocalIP'} ||
            ( $CONF{'LocalIP'} eq '127.0.0.1' ) ||
            ( $CONF{'macaddr'} ne "$I->{'macaddr'}" ) ||
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
