#!/usr/bin/perl

package NetworkTools;

use strict;
use File::Copy;

# etva conf dir
my $etva_config_dir = $ENV{'etva_conf_dir'} || "/etc/sysconfig/etva-vdaemon/config";

# write DNS config
sub change_dns {
    my (%p) = @_;
    if( ! -x "/usr/sbin/system-config-network-cmd" ){
        #die "system-config-network-cmd tool doest exists!\n";
        print STDERR "system-config-network-cmd tool doest exists!\n";
    } else {
        open(P,"| /usr/sbin/system-config-network-cmd -i 2>/dev/null");
        print P 'ProfileList.default.DNS.Hostname=',$p{'hostname'},"\n" if( defined $p{'hostname'} );
        print P 'ProfileList.default.DNS.Domainname=',$p{'domainname'},"\n" if( defined $p{'domainname'} );
        print P 'ProfileList.default.DNS.PrimaryDNS=',$p{'primarydns'},"\n" if( defined $p{'primarydns'} );
        print P 'ProfileList.default.DNS.SecondaryDNS=',$p{'secondarydns'},"\n" if( defined $p{'secondarydns'} );
        print P 'ProfileList.default.DNS.TertiaryDNS=',$p{'tertiarydns'},"\n" if( defined $p{'tertiarydns'} );

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
                print P 'ProfileList.default.DNS.SearchList.',$i,'=',$searchlist[$i],"\n"; 
            }
        }
        close(P);
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
                system("/sbin/dhclient $p{'if'} >/dev/null 2>&1");
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

    if( ! -x "/usr/sbin/system-config-network-cmd" ){
        print STDERR "system-config-network-cmd tool doest exists!\n";
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
            open(P,"| /usr/sbin/system-config-network-cmd -i 2>/dev/null");
            if( defined($p{'ip'}) && defined($p{'netmask'}) ){
                print P "DeviceList.$p{'type'}.",$p{'if'},'.IP=',$p{'ip'},"\n";
                print P "DeviceList.$p{'type'}.",$p{'if'},'.Netmask=',$p{'netmask'},"\n";
                print P "DeviceList.$p{'type'}.",$p{'if'},'.Gateway=',$p{'gateway'},"\n" if( defined $p{'gateway'} );
                $ok = 1;
            }
            if( defined $p{'bootproto'} ){
                print P "DeviceList.$p{'type'}.",$p{'if'},'.BootProto=',$p{'bootproto'},"\n";
                $ok = 1;
            }
            close(P);

            return $ok ? 1 : 0;
        } else {
            print STDERR "interface not defined!\n";
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
