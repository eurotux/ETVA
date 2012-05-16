#!/usr/bin/perl

use strict;

package main;

my $filename = $ARGV[0];
my $outfilename = $filename.".v1";

print "$filename\n";


my $clusterId = &defaultClusterId();

open FILE, "<$filename" or die $!;
open V1FILE, ">$outfilename" or die $!;


OUT: while(<FILE>){
    if(/^\s\sEtvaVlan_\d+:$/){
        print V1FILE;

        my $line = <FILE>;
        unless($line =~ /^\s{4}cluster_id:/){
            print V1FILE "    cluster_id: EtvaCluster_$clusterId\n";
        }
        print V1FILE $line;
    }else{
        print V1FILE;
    }
}

close FILE;
close V1FILE;

# copy changes
unlink $filename;
rename $outfilename, $filename;


sub defaultClusterId(){
    open FILE, "<$filename" or die $!;
    while(<FILE>){
        if(/^\s\sEtvaCluster_(\d+):/){
            my $clusterId = $1;
            while(my $line = <FILE>){
                if($line =~ /^\s{4}isdefaultcluster:\s'1'/){
                    close FILE;
                    return $clusterId;
                }
            }
        }
    }
    exit(1);
}




