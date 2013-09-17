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

#use ETVA::Agent::SOAPFork;
#use ETVA::Client::SOAP::HTTP;
use ETVA::SOAP;
use ETVA::Utils;

use Samba;

use Event::Lib;

#use IO::Socket;
#use IO::Handle;
use POSIX;

#use Device::SerialPort;

use Data::Dumper;

my %CONF = ( "CFG_FILE"=>"/etc/sysconfig/etva-fsagent/fsagentd.conf" );

# choosed serial port
my $serial_port = "/dev/ttyS0";

=item launch_agent
start agent

    create ETVA::Agent::SOAP instance with Samba dispatcher module

=cut

sub launch_agent {

    # get network interface to initialize agent with macaddress
    $CONF{'macaddr'} = ETVA::Utils::getmacaddr();

    open(COM,"+<","$serial_port");

    # dispatcher
    my $dispatcher = $CONF{'_dispatcher'} = "Samba";

    # register func handlers
    &register_handler( *COM, \%CONF );

    # loop
    &mainLoop( *COM, \%CONF );
    #my $Agent = ETVA::Agent::SOAPFork->new( %CONF );

    #$Agent->mainLoop();

    close(COM);
}

=item call

    my $SOAP_Response = $Client->call( $uri, $method, @params );

=cut

# call
#  soap call
#  args: uri, method, parameters
sub call {
    my $fh = shift;
    my ($uri,$method,@params) = @_;

    # default uri
    $uri = "urn:#$method" if( !$uri );

    my $request = &soap_request($uri,$method,@params);

    plog "ETVA::Client::SOAP call request = $request" if(1 || &debug_level > 3);

    my $response = &send_receive($fh, $request);

    plog "ETVA::Client::SOAP call response = $response" if(1 || &debug_level > 3);

    return &soap_response($response);
}

=item send_receive

send request and receive response

    my $response = $Client->send_receive( $request );

=cut

sub send_receive {
    my $fh = shift;
    &send( $fh, @_ );

    return &receive($fh);
}

# nonblock($socket) puts socket into nonblocking mode
# Perl Cookbook
sub nonblock {
     my $socket = shift;
     my $flags;

     $flags = fcntl($socket, F_GETFL, 0)
        or die "Can't get flags for socket: $!\n";
     fcntl($socket, F_SETFL, $flags | O_NONBLOCK)
        or die "Can't make socket nonblocking: $!\n";
}
sub receive {
    my $fh = shift;
    my $data = '';
    while(<$fh>){
        $data .= $_;
        last if( $data =~ m/<\/\S+Envelope>/i );
    }
    return $data;
}
sub send {
    my $fh = shift;
    my @data = @_;

    print $fh @data;
}

sub register_handler {
    my $fh = shift;
    my ($agent) = @_;

    my $dispatcher = $agent->{'_dispatcher'};

    # initialized on CentralManagement 
    if( $agent->{'cm_uri'} ){

        my $now = nowStr();
        plogNow("init Agent with macaddr=",$agent->{'macaddr'});

        my $R = &call( $fh, $agent->{'cm_namespace'},
                                 "initAgentServices",
                                    name=>'ETFS',
                                    ip=>$agent->{'LocalIP'},
                                    port=>$agent->{'LocalPort'},
                                    macaddr=>$agent->{'macaddr'},
                                    services=>[{'name'=>'main'}]
                                );
        if( !$R || $R->{'_error_'} ){
            plogNow("Cant connect to CentralManagement.\nInitialization Agent 'etfs' aborted!");
            die("Cant connect to CentralManagement.\nInitialization Agent 'etfs' aborted!");
        } else {
            if( ref($R->{'return'}) && $R->{'return'}{'success'} && ( $R->{'return'}{'success'} ne 'false' )){
                plogNow("ETFS Agent register with success on CentralManagement");

                plogNow Dumper($R);
                # Reset
                if( $R->{'return'}{'reset'} ){
                    my $response = $dispatcher->set_backupconf( '_url'=>"$R->{'return'}{'backup_url'}" );

                    # send reset/restore ok
                    if(!isError($response)){
                        my $reset_ok = $response;
                        my $RR = &call( $fh, $agent->{'cm_namespace'},
                                        'restoreManagAgent',
                                        macaddr=>$agent->{'macaddr'},
                                        ok=>{ 'oktype'=>$reset_ok->{'_oktype_'}, 'okmsg'=>$reset_ok->{'_okmsg_'} }
                                    );
                    }
                }
            }else{
                plogNow("ETFS Agent NOT registered with success on CentralManagement");
                die("ETFS Agent NOT registered with success on CentralManagement");
            }
        }      
    }
}

sub handle_request {
    my $e = shift;
    my $h = $e->fh;

    plog "handle_guestmessage";
    my $message=&receive($h);
    plog "receive message = $message";
}

sub mainLoop {
    my $fh = shift;
    my ($agent) = @_;
    
    my $eguest = event_new($fh, EV_READ|EV_PERSIST, \&handle_request, $agent);
    $eguest->add;

    event_mainloop;
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
