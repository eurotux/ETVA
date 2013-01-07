#!/usr/bin/perl

package ETVA::NetworkTools;

use strict;

use ETVA::DebugLog;

use File::Copy;

# etva conf dir
my $etva_config_dir = $ENV{'etva_conf_dir'} || "/etc/sysconfig/etva-vdaemon/config";

my $SYSTEM_CONFIG_NETWORK_CMD;

# read_pipe_lines(cmd)
sub read_pipe_lines {
    my ($cmd) = @_;
    my @lines = ();
    open(READ_PIPE,"$cmd |");
    while(<READ_PIPE>){
        chomp;
        push(@lines,$_);
    }
    close(READ_PIPE);
    return wantarray() ? @lines : \@lines;
}
# flush_pipe_lines(cmd,lines, [eol])
sub flush_pipe_lines {
    my ($cmd,$lines,$eol) = @_;
    if( $lines ){
        $eol ||= "\n";
        my $tmpfile = ETVA::DebugLog::rand_logfile("/var/tmp/tmpfile_flush_pipe_lines","txt");
        open(TMPFILE_FLUSH_PIPE_LINES,">$tmpfile");
        for my $line (@$lines){
            print TMPFILE_FLUSH_PIPE_LINES $line,$eol;
        }
        close(TMPFILE_FLUSH_PIPE_LINES);
        my $tmplog = ETVA::DebugLog::rand_logfile("/var/tmp/flush_pipe_lines","log");
        my $e = system("$cmd <$tmpfile 2>$tmplog");
        unless( $e == 0 ){
            my $m = ETVA::DebugLog::dumplogfile($tmplog);
            ETVA::DebugLog::pdebuglog "ERROR flush_pipe_lines - error flush lines: $e - $m","\n";
        }
        unlink($tmpfile);
        unlink($tmplog);
    }
}

sub read_system_config_network_cmd {
    if( ! -x "/usr/sbin/system-config-network-cmd" ){
        ETVA::DebugLog::pdebuglog "system-config-network-cmd tool doest exists!\n";
    } else {
        if( !$SYSTEM_CONFIG_NETWORK_CMD ){
            $SYSTEM_CONFIG_NETWORK_CMD = &read_pipe_lines('/usr/sbin/system-config-network-cmd -e');
        }
        return wantarray() ? @$SYSTEM_CONFIG_NETWORK_CMD : $SYSTEM_CONFIG_NETWORK_CMD;
    }
}
sub flush_system_config_network_cmd {
    if( ! -x "/usr/sbin/system-config-network-cmd" ){
        ETVA::DebugLog::pdebuglog "system-config-network-cmd tool doest exists!\n";
    } elsif( $SYSTEM_CONFIG_NETWORK_CMD ){
        &flush_pipe_lines('/usr/sbin/system-config-network-cmd -ci',$SYSTEM_CONFIG_NETWORK_CMD);
        $SYSTEM_CONFIG_NETWORK_CMD = 0;
    }
}

sub replace_system_config_network_cmd {
    my (@lines) = @_;
    if( my $read_lines = &read_system_config_network_cmd() ){
        for my $line (@lines){
            my ($o,$nv) = ref($line) ? @$line : $line;
            $nv = $nv ? "${o}${nv}" : "${o}";
            my $re = $o; $re =~ s/\./\\./g;
            if( !grep { s/^${re}.*$/$nv/ } @$read_lines ){
                push(@$read_lines,"${nv}");
            }
        }
        &flush_system_config_network_cmd();
    }
}

# write DNS config
sub change_dns {
    my (%p) = @_;
    if( ! -x "/usr/sbin/system-config-network-cmd" ){
        #die "system-config-network-cmd tool doest exists!\n";
        ETVA::DebugLog::pdebuglog "system-config-network-cmd tool doest exists!\n";
    } else {
        my @lines = ();

        push(@lines, ['ProfileList.default.DNS.Hostname=',$p{'hostname'}]) if( defined $p{'hostname'} );
        push(@lines, ['ProfileList.default.DNS.Domainname=',$p{'domainname'}]) if( defined $p{'domainname'} );
        push(@lines, ['ProfileList.default.DNS.PrimaryDNS=',$p{'primarydns'}]) if( defined $p{'primarydns'} );
        push(@lines, ['ProfileList.default.DNS.SecondaryDNS=',$p{'secondarydns'}]) if( defined $p{'secondarydns'} );
        push(@lines, ['ProfileList.default.DNS.TertiaryDNS=',$p{'tertiarydns'}]) if( defined $p{'tertiarydns'} );

        my @searchlist = ();
        if( my $sl = $p{'searchlist'} ){
            @searchlist = ref($sl) ? @$sl : split(/,/,$sl);
        } elsif( grep { /searchlist/ } keys %p ){
            my @lk = grep { /searchlist\.\d+/ } keys %p;
            for my $i ( sort map { /searchlist\.(\d+)/ } @lk ){
                push(@searchlist, $p{"searchlist.$i"});
            }
        }
        if( @searchlist ){
            for(my $i=0; $i<scalar(@searchlist); $i++){
                push @lines, ["ProfileList.default.DNS.SearchList.$i=", $searchlist[$i]];
            }
        }
        &replace_system_config_network_cmd( @lines );
    }
}

sub change_hostname {
    my ($hostname) = @_;
    &change_dns( 'hostname'=>$hostname );
}

# apply interface configuration
sub active_ip_conf {
    my (%p) = @_;

    if( $p{'if'} ){
        if( $p{'dhcp'} ){
            if( ! -x "/sbin/dhclient" ){
                ETVA::DebugLog::pdebuglog "dhclient command not found!\n";
                return 0;
            } else {
                system("/usr/bin/killall dhclient >/dev/null 2>&1");
                sleep(1);
                system("/sbin/dhclient -nw $p{'if'} >/dev/null 2>&1");
            }
        } else {
            # stop dhclient process
            if( -x "/sbin/dhclient" ){
                system("/usr/bin/killall dhclient >/dev/null 2>&1");
                sleep(1);
            }

            if( ! -x "/sbin/ifconfig" ){
                ETVA::DebugLog::pdebuglog "ifconfig command not found!\n";
                return 0;
            } else {
                if( $p{'ip'} && $p{'netmask'} ){
                    #ETVA::DebugLog::pdebuglog "/sbin/ifconfig $p{'if'} $p{'ip'} netmask $p{'netmask'} up","\n";
                    system("/sbin/ifconfig $p{'if'} $p{'ip'} netmask $p{'netmask'} up >/dev/null 2>&1");
                } else {
                    ETVA::DebugLog::pdebuglog "ip or netmask not defined!\n";
                    return 0;
                }
            }
        }

        if( $p{'gateway'} ){
            if( ! -x "/sbin/route" ){
                ETVA::DebugLog::pdebuglog "route command not found!\n";
            } else {
                #ETVA::DebugLog::pdebuglog "/sbin/route add default gw $p{'gateway'} dev $p{'if'}","\n";
                system("/sbin/route add default gw $p{'gateway'} dev $p{'if'} >/dev/null 2>&1");
            }
        }
        return 1;
    }
}

# write interface ip config
sub change_if_conf {
    my (%p) = @_;

    if( ! -x "/usr/sbin/system-config-network-cmd" ){
        ETVA::DebugLog::pdebuglog "system-config-network-cmd tool doest exists!\n";
    } else {
        if( $p{'if'} ){
            if( !$p{'type'} ){
                if( -e "/sys/class/net/$p{'if'}/bridge" ){
                    $p{'type'} = 'bridge';
                } elsif( -e "/sys/class/net/$p{'if'}/bonding" ){
                    $p{'type'} = 'BOND';
                } else {
                    $p{'type'} = 'Ethernet';
                }
            }
            my $ok = 0;
            my @lines = ();
            if( defined($p{'ip'}) && defined($p{'netmask'}) ){
                push(@lines, ["DeviceList.$p{'type'}.$p{'if'}.IP=",$p{'ip'}]);
                push(@lines, ["DeviceList.$p{'type'}.$p{'if'}.Netmask=",$p{'netmask'}]);
                push(@lines, ["DeviceList.$p{'type'}.$p{'if'}.Gateway=",$p{'gateway'}]) if( defined $p{'gateway'} );
                $ok = 1;
            }
            if( defined $p{'bootproto'} ){
                push(@lines, ["DeviceList.$p{'type'}.$p{'if'}.BootProto=",$p{'bootproto'}]);
                $ok = 1;
            }
            &replace_system_config_network_cmd( @lines );

            return $ok ? 1 : 0;
        } else {
            ETVA::DebugLog::pdebuglog "interface not defined!\n";
        }
    }
    return 0;
}

sub change_ip_etva_conf {
    my (%p) = @_;

    opendir(D,"$etva_config_dir");
    my @lc = grep { /(up|down)-$p{'if'}$/ } readdir(D);
    closedir(D);
    for my $f (@lc){
        my $fpath = "$etva_config_dir/$f";
        my $bkp_fpath = "$etva_config_dir/$f.bkp";

        open(F,"$fpath");
        open(B,">$bkp_fpath");

        # delete old lines
        while(<F>){
            print B if( !s#/sbin/dhclient $p{'if'}## && 
                        !s#/sbin/ifconfig $p{'if'} \d+\.\d+\.\d+\.\d+ netmask \d+\.\d+\.\d+\.\d+ up## &&
                        !s#/sbin/route add default gw \d+\.\d+\.\d+\.\d+ dev $p{'if'}## &&
                        !s#/sbin/route del default gw \d+\.\d+\.\d+\.\d+ dev $p{'if'}## );
        }

        # add new lines
        if( $p{'dhcp'} ){
            if( $f =~ m/up-$p{'if'}$/ ){
                print B "/sbin/dhclient $p{'if'}","\n";
            }
        } else {
            if( $f =~ m/up-$p{'if'}$/ ){
                print B "/sbin/ifconfig $p{'if'} $p{'ip'} netmask $p{'netmask'} up","\n";
                print B "/sbin/route add default gw $p{'gateway'} dev $p{'if'}","\n" if( $p{'gateway'} );
            } elsif( $f =~ m/down-$p{'if'}$/ ){
                print B "/sbin/route del default gw $p{'gateway'} dev $p{'if'}","\n" if( $p{'gateway'} );
            }
        }
        close(F);
        close(B);

        move($bkp_fpath,$fpath);
    }
}

# aux func validate ip
sub valid_ipaddr {
    my ($ip) = @_;
    return ( ( $ip =~ m/(\d+)\.(\d+)\.(\d+)\.(\d+)/ ) && 
                ($1 > 0 && $1 < 255 ) &&
                ($2 >= 0 && $2 < 255 ) &&
                ($3 >= 0 && $3 < 255 ) &&
                ($4 > 0 && $4 < 255 ) ) ? 1 : 0;
}

# aux func validate netmask
sub valid_netmask {
    my ($ip) = @_;
    return ( ( $ip =~ m/(\d+)\.(\d+)\.(\d+)\.(\d+)/ ) && 
                ( ($1 == 255 && $2 == 0 && $3 == 0 && $4 == 0) ||
                    ($1 == 255 && $2 == 255 && $3 == 0 && $4 == 0) ||
                    ($1 == 255 && $2 == 255 && $3 == 255 && $4 == 0) ||
                    ($1 == 255 && $2 == 255 && $3 == 255 && $4 == 255) ) ) ? 1 : 0;
}

# get ip configuration info
sub get_ip_conf {
    my ($if,$ip) = @_;

    if( ! -x "/usr/sbin/system-config-network-cmd" ){
        ETVA::DebugLog::pdebuglog "system-config-network-cmd tool doest exists!\n";
    } else {
        my $re_if;
        my $re_ip = $ip ? $ip : '\d+.\d+.\d+.\d+';
        $re_ip =~ s/\./\\\./g;

        my $lre_if = '\w+';
        $lre_if = $if if( $if );

        my @l = ();
        my %p = ();
        open(F,"/usr/sbin/system-config-network-cmd -e |");
        while(<F>){
            if( /([\w\.]+\.(${lre_if}))\.IP=($re_ip)/ ){
                $re_if=$1;
                $if=$2;
                $ip=$3;
                $re_if =~ s/\./\\\./g;
                $p{'IP'} = $ip;
                $p{'IF'} = $if;
                for my $e (@l){
                    if( $e =~ m/$re_if\.(\w+)=(.+)/ ){
                        $p{"$1"} = $2;
                    }
                }
            } elsif( $re_if && ( $_ =~ /$re_if\.(\w+)=(.+)/ ) ){
                $p{"$1"} = $2;
            } elsif( /ProfileList\.default\.DNS\.([^=]+)=(.+)/ ){
                $p{"$1"} = $2;
            } else {
                push(@l,$_);
            }
        }
        close(F);

        return wantarray() ? %p : \%p;
    }
}

# get hosts list
sub get_hosts_list {

    my @l = ();
    if( ! -x "/usr/sbin/system-config-network-cmd" ){
        ETVA::DebugLog::pdebuglog "system-config-network-cmd tool doest exists!\n";
    } else {
        my %HostsList = ();
        open(F,"/usr/sbin/system-config-network-cmd -e |");
        while(<F>){
            if( /ProfileList\.default\.HostsList\.(\d+)\.([^=]+)=(.+)/ ){
                my ($n,$k,$v) = ($1,$2,$3);
                $HostsList{"$n"} = { 'id'=>$n } if( !$HostsList{"$n"} );
                $HostsList{"$n"}{"$k"} = $v;
            }
        }
        close(F);

        @l = sort { $a->{'id'} <=> $b->{'id'} } values %HostsList;
    }

    return wantarray() ? @l : \@l;
}
# set hosts list
sub set_hosts_list {
    my @l = @_;

    if( ! -x "/usr/sbin/system-config-network-cmd" ){
        ETVA::DebugLog::pdebuglog "system-config-network-cmd tool doest exists!\n";
    } else {
        my $c = 1;
        my @lines = ();
        for my $H (@l){
            for my $k (keys %$H){
                next if( $k eq 'id' );  # ignore id key
                my $v = $H->{"$k"};
                push(@lines, ["ProfileList.default.HostsList.$c.$k=",$v]);
            }
            $c++;
        }
        &replace_system_config_network_cmd( @lines );
    }
}
# add host to hosts list
sub add_hosts_list {
    my ($name,$ip,$comment,@alias) = @_;

    my @l = &get_hosts_list();
    my %Host = ( 'Hostname'=>$name, 'IP'=>$ip, 'Comment'=>$comment );
    my $c = 1;
    for my $a (@alias){
        $Host{"AliasList.$c"} = $a;
    }
    push(@l, \%Host );

    &set_hosts_list(@l);
}
# change host of hosts list
sub change_hosts_list {
    my ($name,$ip,$comment,@alias) = @_;

    my @l = &get_hosts_list();

    my %Host = ( 'Hostname'=>$name, 'IP'=>$ip, 'Comment'=>$comment );
    my $c = 1;
    for my $a (@alias){
        $Host{"AliasList.$c"} = $a;
    }
    for(my $i=0; $i<scalar(@l); $i++){
        if( $l[$i]{'Hostname'} eq $Host{'Hostname'} ){
            $l[$i] = \%Host;
        }
    }
    &set_hosts_list(@l);
}

sub fix_hostname_resolution {
    my ($hostname,$ip) = @_;
    my @H = grep { $_->{'Hostname'} eq $hostname } &get_hosts_list();
    if( !@H ){
        &add_hosts_list($hostname,$ip);
    } else {
        if( ! grep  { $_->{'IP'} eq $ip } @H ){    # change it
            &change_hosts_list($hostname,$ip);
        }
    }
}

1;
