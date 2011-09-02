#!/usr/bin/perl
# CMAR 19/03/2010
#   test_requires.pl: this script should be test requires for virtd

use strict;

use Data::Dumper;

sub main {
    my $xen_support = 0;
    if( -e "/proc/xen" ){
        $xen_support = 1;

        print "XEN support ok!\n";
    }

    my $kvm_support = 0;
    if( -e "/dev/kvm" ){
        $kvm_support = 1;

        print "KVM support ok!\n";
    }

    my $hvm_support = 0;
    open(F,"/sys/hypervisor/properties/capabilities");
    while(<F>){
        if( /hvm/ ){
            $hvm_support = 1;
            print "HVM support ok!\n";
            last;
        }
    }
    close(F);
#    /sys/hypervisor/properties/capabilities

    my $bonding_support = 0;
    if( -e "/proc/net/bonding" ){
        $bonding_support = 1;

        print "BONDING support ok!\n";

        my %SlavesByBonding = ();
        my $have_bonding_slaves = 0;

        opendir(D,"/proc/net/bonding");
        my @lifs = readdir(D);
        for my $if (@lifs){
            next if( $if =~ m/^./ );    # ignore hidden files

            open(F,"/proc/net/bonding/$if");
            while(<F>){
                if( /Slave Interface:\s+(\S+)/ ){
                    my $sif = $1;
                    $have_bonding_slaves++;
                    push(@{$SlavesByBonding{"$if"}{'slaves'}}, $sif);
                }
            }
            close(F);
        }
        closedir(D);

        print "SlavesByBonding = ",Dumper(\%SlavesByBonding),"\n";
    }

    my $vlan_support = 0;
    if( -e "/proc/net/vlan" ){
        $vlan_support = 1;

        print "VLAN support ok!\n";

        my $vlan_name_type = "";

        my @vlans = ();
        open(F,"/proc/net/vlan/config");
        while(<F>){
            if( /VLAN Dev name    | VLAN ID/ ){
                next;   # ignore
            } elsif( /Name-Type: (\S+)/ ){
                $vlan_name_type = $1;
            } else {
                my ($name,$id,$if) = ( /(\S+)\s+\|\s+(\d+)\s+\|\s+(\S+)/ );
                push(@vlans, { 'name'=>$name, 'id'=>$id, 'if'=>$if });
            }
            
        }
        close(F);

        print "VLANS = ",Dumper(\@vlans),"\n";
    }
    
}

main();

1;
