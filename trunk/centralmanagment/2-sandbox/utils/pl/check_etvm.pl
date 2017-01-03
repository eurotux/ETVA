#!/usr/bin/perl

package Utils;

use strict;
use constant {
    OK      => 0,
    WARNING => 1,
    CRITICAL=> 2,
    UNKNOWN => 3,    
};

# procedure log
sub plog { print @_,"\n"; }

if( !(chdir '/srv/etva-centralmanagement') ){
    plog "CRITICAL: Unable to open directory '/srv/etva-centralmanagement': $!.";
    exit CRITICAL;
}

# etva conf dir
#my $etva_config_dir = $ENV{'etva_conf_dir'} || "/etc/sysconfig/etva-vdaemon/config";

&check_etva;

sub check_etva {
    
    my $path = &getLogsPath;
    unless(-d $path){ 
        plog "UNKNOWN: Path '$path' doesn't exists.";
        exit UNKNOWN; 
    }

    my @files = <$path/*.alert>;
    if( scalar(@files) ){

        #foreach (@files){
        #    &readFile($_);
        #}

        plog "CRITICAL: There are several critical alerts for this task: ".join(" | ",@files);
        exit CRITICAL;
    } else {
        plog "OK: No alerts found.";
        exit OK;
    }
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
