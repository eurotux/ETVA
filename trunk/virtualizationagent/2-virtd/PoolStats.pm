#!/usr/bin/perl

package PoolStats;

use strict;

use VirtAgent::Disk;
use Utils;

# pooling load stats
sub pool_loadstats {
    my %stats = ();
    my $now = $stats{"timestamp"} = now();

    open(L,"/proc/loadavg");
    while(<L>){
        if( /(\S+)\s+(\S+)\s+(\S+)/ ){
            $stats{"onemin"} = $1;
            $stats{"fivemin"} = $2;
            $stats{"fifteenmin"} = $3;
        }
        last;
    }
    close(L);
    return wantarray() ? %stats : \%stats;
}
# old cpu time stats for compare
my %old_cpustats;
# pooling cpu time stats
sub pool_cpustats {
    my %stats = ();
    my $now = $stats{"timestamp"} = now();
    open(S,"/proc/stat");
    while(<S>){
        if( /(cpu(\d+)?)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)/ ){
            my $t = $1;
            my $tc = 0;
            my %diff = ();
            $stats{"$t"}{"timestamp"} = $now;
            $stats{"$t"}{"us"} = $3;
            $stats{"$t"}{"ni"} = $4;
            $stats{"$t"}{"sy"} = $5;
            $stats{"$t"}{"id"} = $6;
            $stats{"$t"}{"wa"} = $7;
            $stats{"$t"}{"hi"} = $8;
            $stats{"$t"}{"si"} = $9;
            $stats{"$t"}{"st"} = $10;

            for my $k (keys %{$stats{"$t"}}){
                $tc += $diff{"$k"} = $stats{"$t"}{"$k"} - $old_cpustats{"$t"}{"$k"};
            }

            $tc = 1 if( $tc == 0 );

            # percentage calc for each value
            for my $k (keys %{$stats{"$t"}}){
                if( $diff{"$k"} > 0 ){
                    $stats{"$t"}{"${k}_per"} = ( ( $diff{"$k"} * 100 ) / $tc );
                } else {
                    $stats{"$t"}{"${k}_per"} = 0;
                }
            }
            # total of cpu usage percentage
            $stats{"$t"}{"total_per"} = 100 - $stats{"$t"}{"id_per"};
        }
    }
    close(S);
    %old_cpustats = %stats;
    return wantarray() ? %stats : \%stats;
}
# pooling memory stats
sub pool_memstats {
    my %stats = ();
    my $now = $stats{"timestamp"} = now();
    open(M,"/proc/meminfo");
    while(<M>){
        if( /(\S+):\s+(\d+)\s+(\S+)/ ){
            $stats{"$1"} = str2size("$2$3");
        }
    }
    close(M);
    return wantarray() ? %stats : \%stats;
}
# old net stats
my %old_netstats;
# pooling network interface stats
sub pool_netstats {
    my %stats;
    open(N,"/proc/net/dev");
    while(<N>){
        if( /\s*?(\S+):\s*?(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+/ ){
            my $if = $1;
            $stats{"$if"}{"rx_bytes"} = $2;         # received bytes
            $stats{"$if"}{"rx_packets"} = $3;       # received packets
=comment    # not need
            $stats{"$if"}{"rx_errs"} = $4;
            $stats{"$if"}{"rx_drop"} = $5;
            $stats{"$if"}{"rx_fifo"} = $6;
            $stats{"$if"}{"rx_frame"} = $7;
            $stats{"$if"}{"rx_compressed"} = $8;
            $stats{"$if"}{"rx_multicast"} = $9;
=cut
            $stats{"$if"}{"tx_bytes"} = $10;        # transmitted bytes
            $stats{"$if"}{"tx_packets"} = $11;      # transmitted packets
=comment    # not need
            $stats{"$if"}{"tx_errs"} = $12;
            $stats{"$if"}{"tx_drop"} = $13;
            $stats{"$if"}{"tx_fifo"} = $14;
            $stats{"$if"}{"tx_colls"} = $15;
            $stats{"$if"}{"tx_carrier"} = $16;
            $stats{"$if"}{"tx_compressed"} = $17;
=cut

            $stats{"$if"}{"total_bytes"} = $stats{"$if"}{"rx_bytes"} + $stats{"$if"}{"tx_bytes"};

            my $now = $stats{"$if"}{"timestamp"} = now();
            if( %old_netstats ){
                my $dt = $stats{"$if"}{"deltatime"} = $now - $old_netstats{"$if"}{"timestamp"};

                if( $dt > 0 ){
                    # received bitrate
                    $stats{"$if"}{"rx_br"} = ( ( $stats{"$if"}{"rx_bytes"} - $old_netstats{"$if"}{"rx_bytes"} ) / $dt );
                    # transmitted bitrate
                    $stats{"$if"}{"tx_br"} = ( ( $stats{"$if"}{"tx_bytes"} - $old_netstats{"$if"}{"tx_bytes"} ) / $dt );
                    # total bitrate ( received and transmitted )
                    $stats{"$if"}{"total_br"} = ( ( $stats{"$if"}{"total_bytes"} - $old_netstats{"$if"}{"total_bytes"} ) / $dt );
                }
            }
        }
    }
    close(N);
    %old_netstats = %stats;
    return wantarray() ? %stats : \%stats;
}
my %old_diskstats;
# pooling disk stats: access read, write, io...
sub pool_diskstats {
    my %stats = ();
    open(D,"/proc/diskstats");
    while(<D>){
        if( /\s+(\d+)\s+(\d+)\s+(\S+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)/ ){
            my $d = $3;
            $stats{"$d"}{"major"} = $1;         # major block device
            $stats{"$d"}{"minor"} = $2;         # minor block device
            $stats{"$d"}{"r_n"} = $4;           # n of reads completed
            $stats{"$d"}{"r_m"} = $5;           # n of reads merged 
            $stats{"$d"}{"r_sectors"} = $6;     # n of sectors read
            $stats{"$d"}{"r_spent"} = $7;       # n of milliseconds spent reading
            $stats{"$d"}{"w_n"} = $8;           # n of writes completed
            $stats{"$d"}{"w_m"} = $9;           # n of writes merged
            $stats{"$d"}{"w_sectors"} = $10;    # n of sectors written
            $stats{"$d"}{"w_spent"} = $11;      # n of milliseconds spent writing
            $stats{"$d"}{"io_run"} = $12;       # n of I/Os currently in progress
            $stats{"$d"}{"io_spent"} = $13;     # n of milliseconds spent doing I/Os
            $stats{"$d"}{"io_weighted"} = $14;  # n of milliseconds spent doing I/Os


            my $now = $stats{"$d"}{"timestamp"} = now();

            if( %old_diskstats ){
                my $dt = $stats{"$d"}{"deltatime"} = $now - $old_diskstats{"$d"}{"timestamp"};

                $stats{"$d"}{"r_dn"} = $stats{"$d"}{"r_n"} - $old_diskstats{"$d"}{"r_n"};
                $stats{"$d"}{"r_dsectors"} = $stats{"$d"}{"r_sectors"} - $old_diskstats{"$d"}{"r_sectors"};
                $stats{"$d"}{"r_dspent"} = $stats{"$d"}{"r_spent"} - $old_diskstats{"$d"}{"r_spent"};
                $stats{"$d"}{"w_dn"} = $stats{"$d"}{"w_n"} - $old_diskstats{"$d"}{"w_n"};
                $stats{"$d"}{"w_dsectors"} = $stats{"$d"}{"w_sectors"} - $old_diskstats{"$d"}{"w_sectors"};
                $stats{"$d"}{"w_dspent"} = $stats{"$d"}{"w_spent"} - $old_diskstats{"$d"}{"w_spent"};
                $stats{"$d"}{"io_dspent"} = $stats{"$d"}{"io_spent"} - $old_diskstats{"$d"}{"io_spent"};
            }
        }
    }
    close(D);
    %old_diskstats = %stats;
    return wantarray() ? %stats : \%stats;
}

# pooling disks info: size, freesize, number of read, number of writes, ...
sub pool_disks {
    my $slef = shift;
    my ($kn) = @_;
    $kn = "dname" if( !$kn );

    # load disk devices info
    VirtAgent::Disk::loaddiskdev();

    # get physical devices info
    my %PHY = VirtAgent::Disk::phydev();
    # get logical volumes info
    my %LV = VirtAgent::Disk::lvinfo();
    # get mounted devices info
    my %MP = VirtAgent::Disk::mountdev();

    my %BI = ();
    for my $p (keys %PHY){
        my $major = $PHY{"$p"}{"major"};
        my $minor = $PHY{"$p"}{"minor"};
        $BI{"$major:$minor"} = $PHY{"$p"};
    }

    for my $l (keys %LV){
        my $major = $LV{"$l"}{"lv_kernel_major"};
        my $minor = $LV{"$l"}{"lv_kernel_minor"};
        my %B = ();
        if( $BI{"$major:$minor"} ){
            %B = %{$BI{"$major:$minor"}};
        }
        %B = (%B,%{$LV{"$l"}});
        $BI{"$major:$minor"} = \%B;
    }

    my %disk_stats = pool_diskstats();

    my %stats = ();
    for my $s (keys %disk_stats){
        my $major = $disk_stats{"$s"}{"major"};
        my $minor = $disk_stats{"$s"}{"minor"};
        if( $BI{"$major:$minor"} ){
            my $key = $BI{"$major:$minor"}{"$kn"};
            my $device = $BI{"$major:$minor"}{"device"};

            if( $BI{"$major:$minor"}{"loopdevice"} ){
                $stats{"$key"}{"path"} = $BI{"$major:$minor"}{"loopfile"};
            } elsif( $BI{"$major:$minor"}{"aliasdevice"} ){
                $stats{"$key"}{"path"} = $BI{"$major:$minor"}{"aliasdevice"};
            } else {
                $stats{"$key"}{"path"} = $BI{"$major:$minor"}{"device"};
            }
            $stats{"$key"}{"device"} = $BI{"$major:$minor"}{"device"};
            $stats{"$key"}{"mounted"} = $MP{"$device"}{"mountpoint"} if( $MP{"$device"} );
            $stats{"$key"}{"name"} = $BI{"$major:$minor"}{"name"};
            $stats{"$key"}{"dname"} = $BI{"$major:$minor"}{"dname"};
            $stats{"$key"}{"size"} = $BI{"$major:$minor"}{"size"} || 0;
            $stats{"$key"}{"freesize"} = $BI{"$major:$minor"}{"freesize"} || 0;
            $stats{"$key"}{"r_n"} = $disk_stats{"$s"}{"r_n"} || 0;
            $stats{"$key"}{"r_sectors"} = $disk_stats{"$s"}{"r_sectors"} || 0;
            $stats{"$key"}{"r_spent"} = $disk_stats{"$s"}{"r_spent"} || 1;
            $stats{"$key"}{"r_dn"} = $disk_stats{"$s"}{"r_dn"} || 0;
            $stats{"$key"}{"r_dsectors"} = $disk_stats{"$s"}{"r_dsectors"} || 0;
            $stats{"$key"}{"r_dspent"} = $disk_stats{"$s"}{"r_dspent"} || 0;
            $stats{"$key"}{"w_n"} = $disk_stats{"$s"}{"w_n"} || 0;
            $stats{"$key"}{"w_spent"} = $disk_stats{"$s"}{"w_spent"} || 0;
            $stats{"$key"}{"w_sectors"} = $disk_stats{"$s"}{"w_sectors"} || 0;
            $stats{"$key"}{"w_dn"} = $disk_stats{"$s"}{"w_dn"} || 0;
            $stats{"$key"}{"w_dspent"} = $disk_stats{"$s"}{"w_dspent"} || 0;
            $stats{"$key"}{"w_dsectors"} = $disk_stats{"$s"}{"w_dsectors"} || 0;

            $stats{"$key"}{"timestamp"} = $disk_stats{"$s"}{"timestamp"};
        }
    }

    return wantarray() ? %stats : \%stats;
}

# all stats
sub pool_stats {
    my %load_stats = pool_loadstats();
    my %cpu_stats = pool_cpustats();
    my %mem_stats = pool_memstats();
    my %net_stats = pool_netstats();
    my %disk_stats = pool_disks();
    
    my %info = ( 'load'=>\%load_stats,
                    'cpu'=>\%cpu_stats,
                    'mem'=>\%mem_stats,
                    'net'=>\%net_stats,
                    'disk'=>\%disk_stats
                );

    return wantarray() ? %info : \%info;
    
}

1;
