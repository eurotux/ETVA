#!/usr/bin/perl

package Utils;

use strict;
use constant {
    OK      => 0,
    WARNING => 1,
    CRITICAL=> 2,
    UNKNOWN => 3,    
};

chdir '/srv/etva-centralmanagement' or &retCritical($!);

# etva conf dir
#my $etva_config_dir = $ENV{'etva_conf_dir'} || "/etc/sysconfig/etva-vdaemon/config";

&check_etva;

sub check_etva {
    
    my $path = &getLogsPath;
    unless(-d $path){ 
        exit UNKNOWN; 
    }

    my @files = <$path/*.alert>;
    return OK if(scalar @files == 0);

    foreach (@files){
        &readFile($_);
    }

    exit CRITICAL;
}

sub readFile {
    my $file = shift;
    if(-e $file){
        unless(-d $file){
            print "\n[ERROR] ======= FILE EXISTS: $file =======\n";
            open FILE, "<$file" or &retCritical($!);
            while(<FILE>){
                print;
            }
            close FILE;
        }
    }
}

sub getLogsPath {
    my $name = `grep log_dir apps/app/config/app.yml`; # | tr -d '  log_dir: '`;
    chomp $name;
    if($name =~ /log\_dir:\s'(.*)'$/){
        return $1;
    }
}

sub retCritical {
    print '[ERROR] '.(shift)."\n";
    exit CRITICAL;
}
