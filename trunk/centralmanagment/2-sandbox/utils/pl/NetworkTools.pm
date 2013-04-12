#!/usr/bin/perl

package NetworkTools;

use strict;
use File::Copy;
use Digest::MD5 qw(md5_hex);

# etva conf dir
my $etva_config_dir = $ENV{'etva_conf_dir'} || "/etc/sysconfig/etva-vdaemon/config";

# aux funcs
sub rand_logfile {
    my ($pr,$ext) = @_;
    $ext = '.log' if( !$ext );
    my $randtok = substr(md5_hex( rand(time()) ),0,5);
    return $pr ? "$pr.$randtok.$ext" : "$randtok.$ext";
}

sub pdebuglog {
	print STDERR @_;
}
# end aux funcs

# read_file_lines(file)
sub read_file_lines {
    my ($file) = @_;
    my @lines = ();
    open(READ_FILE,"$file");
    while(<READ_FILE>){
        chomp;
        push(@lines,$_);
    }
    close(READ_FILE);
    return wantarray() ? @lines : \@lines;
}
# flush_file_lines(file,lines, [eol])
sub flush_file_lines {
    my ($file,$lines,$eol) = @_;
    if( $lines ){
        $eol ||= "\n";
        my $tmpfile = &rand_logfile("$file","tmp");
        open(FLUSH_FILE, ">$tmpfile");
        foreach my $line (@$lines) {
            (print FLUSH_FILE $line,$eol) ||
                &pdebuglog("Error file write $tmpfile: $!");
        }
        close(FLUSH_FILE);
        rename($tmpfile, $file) || &pdebuglog("Error replace file $file with $tmpfile: $!");
        unlink($tmpfile);
    }
}
# write DNS config
sub change_dns {
    my (%p) = @_;

    if( defined $p{'hostname'} ){
        my $already_there = 0;
        my @lines_network = ();
        my $bkp_network = &read_file_lines("/etc/sysconfig/network");
        for my $l (@$bkp_network){
            if( $l =~ m/^HOSTNAME=/i ){
                $already_there = 1;
                $l = "HOSTNAME=$p{'hostname'}";
            }
            push(@lines_network,$l);
        }
        push(@lines_network,"HOSTNAME=$p{'hostname'}") if( !$already_there );
        &flush_file_lines("/etc/sysconfig/network",\@lines_network);
    }

    if( defined($p{'domainname'}) ||
            defined($p{'primarydns'}) ||
            defined($p{'secondarydns'}) ||
            defined($p{'tertiarydns'}) ){
        my @lines_resolv = ();

        my $bkp_resolv = &read_file_lines("/etc/resolv.conf");

        my $has_searchlist = 0;
        my @searchlist = ();
        if( grep { /searchlist/ } keys %p ){
            $has_searchlist = 1;
            if( my $sl = $p{'searchlist'} ){
                @searchlist = ref($sl) ? @$sl : split(/,/,$sl);
            } else {
                for my $i ( sort map { /searchlist\.(\d+)/ } keys %p ){
                    $searchlist[$i-1] = $p{"searchlist.$i"};
                }
            }
        }

        my $domain_c = 0;
        my $nameserver_c = 0;
        my $searchlist_c = 0;
        for my $l (@$bkp_resolv){
            if( $l =~ m/^domain/ ){
                $domain_c++;
                if( defined $p{'domainname'} ){
                    $l = ( $p{'domainname'} ) ? "domain $p{'domainname'}" : "";
                }
            } elsif( $l =~ m/^nameserver/ ){
                $nameserver_c++;
                if( $nameserver_c==1 && defined($p{'primarydns'}) ){
                    $l = ($p{'primarydns'}) ? "nameserver $p{'primarydns'}" : "";
                }
                if( $nameserver_c==2 && defined($p{'secondarydns'}) ){
                    $l = ($p{'secondarydns'}) ? "nameserver $p{'secondarydns'}" : "";
                }
                if( $nameserver_c==3 && defined($p{'tertiarydns'}) ){
                    $l = ($p{'tertiarydns'}) ? "nameserver $p{'tertiarydns'}" : "";
                }
            } elsif( $l =~ m/^search/ ){
                $searchlist_c++;

                if( $has_searchlist ){
                    $l = "";    # clean up
                    if( @searchlist ){
                        my @sl = split(/\s+/,$l);
                        shift(@sl); # drop search
                        for(my $i=0; $i<scalar(@sl); $i++){
                            $searchlist[$i] = defined($searchlist[$i]) ? $searchlist[$i] : $sl[$i];
                        }
                        $l = "search " . join(" ",@searchlist);
                    }
                }
            }
            push(@lines_resolv,$l);
        }
        push(@lines_resolv,"domain $p{'domainname'}") if( !$domain_c && defined($p{'domainname'}) );
        push(@lines_resolv,"nameserver $p{'primarydns'}") if( $nameserver_c<1 && defined($p{'primarydns'}) );
        push(@lines_resolv,"nameserver $p{'secondarydns'}") if( $nameserver_c<2 && defined($p{'secondarydns'}) );
        push(@lines_resolv,"nameserver $p{'tertiarydns'}") if( $nameserver_c<3 && defined($p{'tertiarydns'}) );
        push(@lines_resolv,"search " . join(" ",@searchlist)) if( !$searchlist_c && @searchlist );

        &flush_file_lines("/etc/resolv.conf",\@lines_resolv);
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
                print STDERR "dhclient command not found!\n";
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
                print STDERR "ifconfig command not found!\n";
                return 0;
            } else {
                if( $p{'ip'} && $p{'netmask'} ){
                    #print STDERR "/sbin/ifconfig $p{'if'} $p{'ip'} netmask $p{'netmask'} up","\n";
                    system("/sbin/ifconfig $p{'if'} $p{'ip'} netmask $p{'netmask'} up >/dev/null 2>&1");
                } else {
                    print STDERR "ip or netmask not defined!\n";
                    return 0;
                }
            }
        }

        if( $p{'gateway'} ){
            if( ! -x "/sbin/route" ){
                print STDERR "route command not found!\n";
            } else {
                #print STDERR "/sbin/route add default gw $p{'gateway'} dev $p{'if'}","\n";
                system("/sbin/route add default gw $p{'gateway'} dev $p{'if'} >/dev/null 2>&1");
            }
        }
        return 1;
    }
}

# write interface ip config
sub change_if_conf {
    my (%p) = @_;

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

        my $if_cfg_file = "/etc/sysconfig/network-scripts/ifcfg-$p{'if'}";
        my $ok = 0;

        my @lines = &read_file_lines($if_cfg_file);
        if( !@lines ){
            push(@lines,"DEVICE=$p{'if'}");
            push(@lines,"TYPE=$p{'type'}");
        }

        if( defined($p{'ip'}) && defined($p{'netmask'}) ){
            if( !grep { s/^IPADDR=.*$/IPADDR=$p{'ip'}/ } @lines ){
                push(@lines,"IPADDR=$p{'ip'}");
            }
            if( !grep { s/^NETMASK=.*$/NETMASK=$p{'netmask'}/ } @lines ){
                push(@lines, "NETMASK=$p{'netmask'}");
            }
            if( defined $p{'gateway'} ){
                if( !grep { s/^GATEWAY=.*$/GATEWAY=$p{'gateway'}/ } @lines){
                    push(@lines, "GATEWAY=$p{'gateway'}");
                }
            }
            $ok = 1;
        }
        if( defined $p{'bootproto'} ){
            if( !grep { s/^BOOTPROTO=.*$/BOOTPROTO=$p{'bootproto'}/ } @lines ){
                push(@lines,"BOOTPROTO=$p{'bootproto'}");
            }
            $ok = 1;
        }

        &flush_file_lines($if_cfg_file,\@lines);

        return $ok ? 1 : 0;
    } else {
        pdebuglog "interface not defined!\n";
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
        print STDERR "system-config-network-cmd tool doest exists!\n";
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

1;
