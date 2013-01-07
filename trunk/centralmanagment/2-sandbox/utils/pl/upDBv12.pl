#!/usr/bin/perl

use strict;

package main;

use Data::Dumper;

my $filename = $ARGV[0];
my $outfilename = $filename.".v1";

print "$filename\n";

my %hashRelNodesCluster = &getRelNodesCluster();
#print STDERR Dumper(\%hashRelNodesCluster);

open FILE, "<$filename" or die $!;
open V1FILE, ">$outfilename" or die $!;

#EtvaServer:
#  EtvaServer_1:
#...
#    node_id: EtvaNode_1

my @read_lines = <FILE>;
print V1FILE &proc_lines(\@read_lines);

close FILE;
close V1FILE;

# copy changes
unlink $filename;
rename $outfilename, $filename;

sub getRelNodesCluster {
    my %HashNodesCluster = ();
    open FILE, "<$filename" or die $!;
    while(<FILE>){
        if(/^EtvaNode:$/){
            while(<FILE>){
                last if( /^\S/ );
                if(/^\s{2}EtvaNode_(\d+):$/){
                    my $node_id = $1;
                    while(<FILE>){
                        last if( /^\s{2}\S/ );
                        if(/^\s{4}cluster_id:\sEtvaCluster_(\d+)/ ){
                            my $cluster_id = $1;
                            $HashNodesCluster{"$node_id"} = $cluster_id;
                            last;
                        }
                    }
                }
            }
        }
    }
    close(FILE);
    return wantarray() ? %HashNodesCluster : \%HashNodesCluster;
}

sub proc_lines {
    my ($lines) = @_;

    my @new_Server_assign_lines = ();

    for(my $c=0; $c < scalar(@$lines); $c++){
        my $l = $lines->[$c];
        if( $l =~ /^EtvaServer:$/){
            for($c++; $c < scalar(@$lines); $c++){
                $l = $lines->[$c];
                if( $l =~ /^\s{2}EtvaServer_(\d+):$/){
                    my $server_id = $1;

                    my $assign;

                    for( $c++, $l = $lines->[$c];
                            $l && ($l !~ /^\s{4}node_id:\sEtvaNode_(\d+)/ ) && ($c < scalar(@$lines));
                            $c++, $l = $lines->[$c]){

                        last if( $l =~ /^\s{2}?\S/ );
                        if( $l =~ s/^\s{4}unassigned:\s'(\d+)'// ){
                            $assign = $1 ? 0 : 1;
                        }
                    }
                    
                    if( my ($node_id) = ( $l =~ /^\s{4}node_id:\sEtvaNode_(\d+)/ ) ){
                        my $cluster_id = $hashRelNodesCluster{"$node_id"};

                        $l = $lines->[$c] = "    cluster_id: EtvaCluster_${cluster_id}\n";

                        if( !defined($assign) ){
                            for( $c++, $l = $lines->[$c];
                                    $l && ($l !~ /^\s{4}unassigned:\s'(\d+)'/ ) && ($c < scalar(@$lines));
                                    $c++, $l = $lines->[$c]){
                                last if( $l =~ /^\s{2}?\S/ );
                            }
                            if( $l =~ s/^\s{4}unassigned:\s'(\d+)'// ){
                                $assign = $1 ? 0 : 1;
                            } else {
                                $c--; # roll back
                            }
                        }

                        push(@new_Server_assign_lines,"  EtvaServerAssign_${server_id}_${node_id}:\n    server_id: EtvaServer_$server_id\n    node_id: EtvaNode_$node_id\n") if( $assign || !defined($assign) );
                    } else {
                        $c--;   # roll back
                    }
                }
            }
        }
    }
    if( @new_Server_assign_lines ){
        push(@$lines, "EtvaServerAssign:","\n",@new_Server_assign_lines );
    }
    return wantarray() ? @$lines : $lines;
}

