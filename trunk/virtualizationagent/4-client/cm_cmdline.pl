#!/usr/bin/perl

use strict;

use Cmdline::Shell;

use Data::Dumper;
use JSON;

my $uri = "http://10.10.20.116:8004/soapcliapi.php";
my $ns = "urn:soapCliController";

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

