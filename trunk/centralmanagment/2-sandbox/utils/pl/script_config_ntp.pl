#!/usr/bin/perl
#

package NTPShell;

use strict;

use NTP;

use Data::Dumper;

sub run_listservers {
    for my $server (NTP->list_server){
        print $server,"\n";
    }
}

sub run_setservers {
    my @servers = @_;

    NTP->set_config('server'=>[@servers]);
    NTP->apply_config();
}

package main;

use strict;

use Getopt::Long;

my %op = ();
my ($help, $DEBUG, $version, $list, $set);

my $goodOptions = GetOptions(
    "help|?|h"      => \$help,          # show help
    "debug|d"       => \$DEBUG,         # show debug
    "version|v"     => \$op{'version'}, # show program version
    "list|l"        => \$op{'list'},    # list servers option
    "set|s"         => \$op{'set'},     # set servers option
);

if( $op{'list'} ){
    NTPShell::run_listservers();
} elsif( $op{'set'} ){
    NTPShell::run_setservers(@ARGV);
}

1;
