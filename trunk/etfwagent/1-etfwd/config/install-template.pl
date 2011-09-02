#!/usr/bin/perl

use strict;

use Utils;
use FileFuncs;

use File::Copy;

my %conf = ( 'base_dir'=>'.' );

sub include_file_squid {
    my ($sconf_i) = @_;

    my $sconf = "/etc/squid/squid.conf";

    my $lr = &read_file_lines($sconf);

    my $lr_i = &read_file_lines($sconf_i);
    my $str_li = join("\n",@$lr_i);
    
    if( grep { s#INCLUDE#$str_li# } @$lr ){
        my ($ie,$IP) = cmd_exec('/sbin/ip add show |grep "inet "|awk {\' print $2 \'}|grep -v 127.0.0|head -1');
        chomp($IP);
        my ($ne,$NETWORK) = cmd_exec("/bin/ipcalc -n $IP");
        chomp($NETWORK);
        $NETWORK =~ s/NETWORK=//;

        for(@$lr){
            s/__NETWORK__/$NETWORK/;
            s/etproxyserver/squid/g;
            s/etproxy/squid/g;
        }
    }

    &flush_file_lines();
}

sub install_ad {
    &include_file_squid("$conf{'base_dir'}/etproxyserver-ad/etc/etproxy/etproxy.ad.conf");
}

sub install_ldap {
    &include_file_squid("$conf{'base_dir'}/etproxyserver-ldap/etc/etproxy/etproxy.ldap.conf");
}

sub install_transparent {
    my $sconf = "/etc/squid/squid.conf";
    my $fconf = "$conf{'base_dir'}/etproxyserver-transparent/etc/etproxy/etproxy.transparent.conf";

    if( -e "$sconf" ){
        move($sconf,"$sconf.etfw.bkp");
    }
    copy($fconf,$sconf);

    my $lr = &read_file_lines($sconf);

    my ($ie,$IP) = cmd_exec('/sbin/ip add show |grep "inet "|awk {\' print $2 \'}|grep -v 127.0.0|head -1');
    chomp($IP);
    my ($ne,$NETWORK) = cmd_exec("/bin/ipcalc -n $IP");
    chomp($NETWORK);
    $NETWORK =~ s/NETWORK=//;

    for(@$lr){
        s/__NETWORK__/$NETWORK/;
        s/etproxyserver/squid/g;
        s/etproxy/squid/g;
    }

    &flush_file_lines();
}

sub install_base {

    my $toetc = "/etc/squid";
    my $detc = "$conf{'base_dir'}/etproxyserver/etc/etproxy";

    if( ! -d "$toetc" ){
        mkdir "$toetc";
    }

    opendir(D,"$detc");
    my @lf = readdir(D);
    for my $f (@lf){
        next if( $f =~ m/^\./ );

        my $fpath = "$detc/$f";

        # replace etproxy by squid
        $f =~ s/etproxy/squid/;

        my $topath = "$toetc/$f";

        if( -e "$topath" ){
            move($topath,"$topath.etfw.bkp");
        }
        copy($fpath,$topath);
        
        if( $f =~ m/\.conf$/ ){
            my $lr = &read_file_lines($topath);
            for(@$lr){
                s#etproxyserver#squid#g;
                s#etproxy#squid#g;
            }
        }
    }
    closedir(D);

    my $fdansguardian_f1 = "/etc/dansguardian/dansguardianf1.conf";
    if( -e "$fdansguardian_f1" ){
        my $lr_f1 = &read_file_lines("$fdansguardian_f1");
        for(@$lr_f1){
            s#naughtynesslimit = 50#naughtynesslimit = 160#;
        }
    }
    
    my $fdansguardian = "/etc/dansguardian/dansguardian.conf";
    if( -e "$fdansguardian" ){
        my $lr_d = &read_file_lines("$fdansguardian");
        for(@$lr_d){
            s#language = 'ukenglish'#language = 'portuguese'#;
            s#filterip =#filterip = 127.0.0.1#;
            s#proxyport = 3128#proxyport = 8081#;
            s#YOURSERVER.YOURDOMAIN#`hostname`#;
            s#usexforwardedfor = off#usexforwardedfor = on#;
        }
    }
    
    # configure squid out
    my $f_sysconfig = "$conf{'base_dir'}/etproxyserver/etc/sysconfig/etproxyout";
    my $to_sysconfig = "/etc/sysconfig/squidout";
    if( -e "$to_sysconfig" ){
        move($to_sysconfig,"$to_sysconfig.etfw.bkp");
    }
    copy($f_sysconfig,$to_sysconfig);

    my $lr_sys = &read_file_lines($to_sysconfig);
    for(@$lr_sys){
        s#etproxyserver#squid#g;
        s#etproxy#squid#g;
    }

    my $f_initd = "$conf{'base_dir'}/etproxyserver/etc/init.d/etproxyout";
    my $to_initd = "/etc/init.d/squidout";
    if( -e "$to_initd" ){
        move($to_initd,"$to_initd.etfw.bkp");
    }
    copy($f_initd,$to_initd);

    my $lr_initd = &read_file_lines($to_initd);
    for(@$lr_initd){
        s#etproxyserver#squid#g;
        s#etproxy#squid#g;
    }

    &flush_file_lines();
}

sub main {
    my ($template) = @ARGV;

    &install_base();

    if( $template eq 'proxy_ad' ){
        &install_ad();
    } elsif( $template eq 'proxy_ldap' ){
        &install_ldap();
    } else {
#    } elsif( $template eq 'transparent' ){
        &install_transparent();
    }

    # TODO start squid and squidout
}

main();
1;
