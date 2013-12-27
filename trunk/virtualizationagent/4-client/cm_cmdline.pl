#!/usr/bin/perl

use strict;

use Cmdline::Shell;

use Data::Dumper;
use JSON;

my $uri = $ENV{'cm_uri'} || "http://localhost/soapcliapi.php";
my $ns = "urn:soapCliController";

if( $ENV{'cm_host'} ) {
    my $host = $ENV{'cm_host'};
    $uri = "http://${host}/soapcliapi.php";
}

sub main {
    my $shell = Cmdline::Shell->new();
    if( @ARGV ){
        $shell->remove_command("exit");
        $shell->cmd("@ARGV");
    } else {
        $shell->cmdloop();
    }
}

main();

1;

