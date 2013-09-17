#!/usr/bin/perl
# Copywrite Eurotux 2013
# 
# CMAR 2013/08/09 (cmar@eurotux.com)

=pod

=head1 NAME

fsagentd - File Server daemon

=head1 SYNOPSIS

    ./fsagentd

=head1 DESCRIPTION

    main

        load configuration

        start agent

            create new agent ETVA::Agent::SOAP using ETFS as dispatcher

            $Agent->mainLoop()

                create Socket to Listen

                register on Central Management

                register alarm at T_ALARM seconds to send keep alive alert to Central Management

                accept connections from clients

=head1 METHODS

=over 4

=cut

use strict;

use ETVA::Agent::SOAPFork;
use ETVA::Client::SOAP::HTTP;
use ETVA::Utils;

use ETFS;

use Data::Dumper;

my %CONF = ( "CFG_FILE"=>"/etc/sysconfig/etva-fsagent/fsagentd.conf" );


=item launch_agent
start agent

    create ETVA::Agent::SOAP instance with ETFS dispatcher module

=cut

sub launch_agent {

    # get network interface to initialize agent with macaddress
    $CONF{'macaddr'} = ETVA::Utils::getmacaddr();

    # register func handlers
    $CONF{'_register_handler'} = \&register_handler;

    require ETFSDispatcher;

    my $dispatcher = $CONF{'_dispatcher'} = "ETFSDispatcher";

    my $Agent = ETVA::Agent::SOAPFork->new( %CONF );

    $Agent->mainLoop();
}
sub register_handler {
    my ($agent) = @_;

    # initialized on CentralManagement 
    if( $agent->{'cm_uri'} ){

        # get ETFS active modules
        my %Mod = ETFS->get_activemodules();
        my @services = ();
        for my $m (keys %Mod){
            my $pmod = $Mod{"$m"};            

            eval "require $pmod";
            if( !$@ ){
                my %params = ( "dispatcher"=>$pmod );
                eval { 
                    # trie run load config of module
                    my %C = $pmod->get_config();                    
                    if( %C ){                        
                        # send parameters from config of module
                        %params = (%params,%C);
                    }
                };
                push(@services, { name=>$m, description=>"$m ($pmod)",
                                    params=>\%params } );
            } else{
                plog "etfs - Perl module '$pmod' required!";
            }
        }
        my $now = nowStr();
        plog("$now - init Agent with macaddr=",$agent->{'macaddr'});
        plog(" services = ",Dumper(\@services));

        my $R = new ETVA::Client::SOAP::HTTP( uri => $agent->{'cm_uri'} )
                        -> call( $agent->{'cm_namespace'},
                                 "initAgentServices",
                                    name=>'ETFS',
                                    ip=>$agent->{'LocalIP'},
                                    port=>$agent->{'LocalPort'},
                                    macaddr=>$agent->{'macaddr'},
                                    services=>\@services
                                );
        if( !$R || $R->{'_error_'} ){
                plog("Cant connect to CentralManagement.\nInitialization Agent 'etfs' aborted!");
        } else {
            plog($R->{'return'}{'success'});
            if( ref($R->{'return'}) && $R->{'return'}{'success'} && ( $R->{'return'}{'success'} ne 'false' )){
                plog("$now - ETFS Agent register with success on CentralManagement");


                # Reset
                if( $R->{'return'}{'reset'} ){
                    my $response = ETFS->set_backupconf( '_url'=>"$R->{'return'}{'backup_url'}" );

                    plog("RESPONSE ",isOk($response),Dumper($response));

                    # send reset/restore ok
                    if(!isError($response)){
                        plog("RESPONSE OKKKK",Dumper($response));
                        my $reset_ok = $response;
                        my $RR = new ETVA::Client::SOAP::HTTP( uri => $agent->{'cm_uri'} )
                            -> call( $agent->{'cm_namespace'},
                                        'restoreManagAgent',
                                        macaddr=>$agent->{'macaddr'},
                                        ok=>{ 'oktype'=>$reset_ok->{'_oktype_'}, 'okmsg'=>$reset_ok->{'_okmsg_'} }
                                    );
                    }
                    
                }

                

            }else{
                plog("$now - ETFS Agent NOT registered with success on CentralManagement");
            }
        }      
    }
}

=item main

simple startup

=over

=item *

load configuration stuff

=item *

launch agent

=back

=cut

# simple startup
sub main {
    $CONF{"CFG_FILE"} = $ENV{'CFG_FILE'} if( $ENV{'CFG_FILE'} );
    loadfunc();
    launch_agent();
}

sub loadfunc {
    %CONF = ETVA::Utils::get_conf(1,$CONF{"CFG_FILE"});
}

main();
1;

=back

=pod

=head1 BUGS

...

=head1 AUTHORS

...

=head1 COPYRIGHT

...

=head1 LICENSE

...

=head1 SEE ALSO

C<http://libvirt.org>
L<ETVA::Agent>, L<ETVA::Agent::SOAP>, L<ETVA::Agent::JSON>
L<ETVA::Client>, L<ETVA::Client::SOAP>, L<ETVA::Client::SOAP::HTTP>

=cut
