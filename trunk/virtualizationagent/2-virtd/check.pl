#!/usr/bin/perl

use strict;

use ETVA::Utils;
use ETVA::Client::SOAP;

use Data::Dumper;

# Config load from config file
my $CONF = { "CFG_FILE"=>"/etc/sysconfig/etva-vdaemon/virtd.conf" };

my $service_dir = "/service/etva-vdaemon/";

my $port;
my $addr;
my $uri;
my $debug;
my $blocking;

sub check {
    my $ok = 1;
    eval {
        my $client = new ETVA::Client::SOAP( address => $addr,
                                        port => $port,
                                        proto=>'tcp',
                                        blocking=>$blocking );

        $client->set_debug() if( $debug );
        
        my $result = $client->call($uri,"getstate");

        plog(Dumper($result));
    };
    if( $@ ){
        $ok = 0;
        plog("Could possible connect to service... $@");
    }
    return $ok;
}

sub check_va_status {
    my ($e,$m) = cmd_exec("svstat $service_dir");
    if( $m =~ m/ up / ){
        return 1;
    }
    return 0;
}

sub restart_va {
    cmd_exec("svc -dk $service_dir");
    sleep(5);
    cmd_exec("svc -du $service_dir");
}

sub check_loop {
    my $cr = 0;
    while(!&check){
        die "could not check virt agent!\n" if( $cr > 5 );
        &restart_va();
        sleep(10);
        my $cs = 0;
        while(!&check_va_status){
            die "could not start virt agent!\n" if( $cs > 10 );
            sleep(5);
            $cs++;
        }
        $cr++;
    }
}

sub loadconf {
    $CONF->{"CFG_FILE"} = $ENV->{'CFG_FILE'} if( $ENV{'CFG_FILE'} );
    $CONF = ETVA::Utils::get_conf(1,$CONF->{"CFG_FILE"});

    $port = $ENV{'va_port'} || $CONF->{"Port"} || 7000;
    $addr = $ENV{'va_addr'} || $CONF->{"IP"} || $CONF->{"LocalIP"} || "localhost";
    $uri = $ENV{'va_uri'} || $CONF->{'va_uri'} || 'http://www.eurotux.com/VirtAgent';
    $debug = ($ENV{'va_debug'} || $ENV{'DEBUG'} || $CONF->{'debug'} ) ? 1:0;
    $blocking = $ENV{'BLOCKING'} || $CONF->{'blocking'} || 0;
}

sub main {

    &loadconf();

    &check_loop;
}
&main();

1;
