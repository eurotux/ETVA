#!/usr/bin/perl

package Main;

use strict;

use ETVA::Utils;
use ETVA::NetworkTools;

use Event::Lib;
use IO::Socket;
use Fcntl;
use Filesys::Statvfs;
use Data::Dumper;

use Config::IniFiles;

use POSIX qw/SIGINT SIGTERM SIGHUP/;

my $testing = 1;
my $DEBUG = 1;
my $server = "localhost";
my $port = 13666;
my $T_ALARM = 1;

my $LCD; # LCD socket
my %State = (); # agent state
my %FD = ();    # file descriptors

my $CONF; # Config from ini file

my %AllSections = ();
my @ALLOPS = qw(LOAD MEM CPU DF NET CLOCK UPTIME IP GW VIRTD);
my @OPS = @ALLOPS;
$State{'op'} = $OPS[0]; # start op

# read/send/process commands to LCD
sub read_cmd {
    my ($lcd,$nowait) = @_;

    $lcd->blocking(0) if( $nowait );
    my $r = <$lcd>;
    ETVA::Utils::plog("read_cmd: $r") if( $DEBUG );
    $lcd->blocking(1) if( $nowait );

    return $r;
}
sub send_cmd {
    my ($lcd,$m) = @_;

    ETVA::Utils::plog("send_cmd: $m") if( $DEBUG );
    print $lcd $m,"\n";
}
sub process_read {
    my ($lcd,$h,$nowait) = @_;
    my $r = read_cmd($lcd,$nowait);
    $h->($r);
}
# end

# send some commands to LCD
sub initialization {
    my $lcdconnect = send_cmd($LCD, "hello");

    send_cmd($LCD, "client_set name {lcdagent}");

    send_cmd($LCD, "screen_add lcdagent");
    send_cmd($LCD, "screen_set lcdagent -name {lcdagent} -priority foreground -heartbeat off");
#    send_cmd($LCD, "screen_set lcdagent name {lcdagent} heartbeat off");

    my $lcd_title = $CONF->val("lcdagent","Title","lcdagent");
    send_cmd($LCD, "widget_add lcdagent title title");
    send_cmd($LCD, "widget_set lcdagent title {$lcd_title}");

    send_cmd($LCD, "client_add_key shared Up");
    send_cmd($LCD, "client_add_key shared Down");

    send_cmd($LCD, "widget_add lcdagent scroller scroller");

    &createmenus();

    &fd_initialization();
}
sub createmenus {

# Rede
# Reset password
# Shutdown
# Restart virtd

    # no/yes title translation
    my $no_title = $CONF->val("Translation","No","No");
    my $yes_title = $CONF->val("Translation","Yes","Yes");

    # check if Network menu is on
    if( lc($CONF->val("Menu Network","Active","False")) eq 'true' ){
        my $net_title = $CONF->val("Menu Network","Title","Network");
        send_cmd($LCD, "menu_add_item \"\" network menu \"$net_title\"");

        my @l_ifs = ();
        my $if_i = $CONF->val("Menu Network","If");
        push(@l_ifs,$if_i) if( $if_i );
        my $i = 0;
        while( $if_i = $CONF->val("Menu Network","If$i") ){
            push(@l_ifs,$if_i);
            $i++;
        }
    
        if( !@l_ifs ){
            my %If = ETVA::Utils::get_defaultinterface();
            push(@l_ifs, $If{'name'} ) if( $If{'name'} );
        }

        for my $if (@l_ifs){
            my $If = ETVA::Utils::get_interface($if);
        
            if( $If ){  # only If exists
                $if = $If->{'name'};
                my %If_conf = ETVA::NetworkTools::get_ip_conf($if);
                my $ifbkp = $if;
                $ifbkp =~ s/[^a-zA-Z0-9:.]//g;
                my $if_mn = "if_$ifbkp";
                send_cmd($LCD, "menu_add_item \"network\" $if_mn menu \"$if\"");
                my $ck_dhcp = ( $If->{'dhcp'} || ( $If_conf{'BootProto'} eq 'dhcp' ))? "on" : "off";
                send_cmd($LCD, "menu_add_item \"$if_mn\" ${if_mn}_dhcp_cbx checkbox \"DHCP\" -value $ck_dhcp");
                send_cmd($LCD, "menu_add_item \"$if_mn\" ${if_mn}_addr ip \"IP\" -v6 false -value \"$If->{'address'}\"");
                send_cmd($LCD, "menu_add_item \"$if_mn\" ${if_mn}_netmask ip \"Netmask\" -v6 false -value \"$If->{'netmask'}\"");
                send_cmd($LCD, "menu_add_item \"$if_mn\" ${if_mn}_broadcast ip \"Broadcast\" -v6 false -value \"$If->{'broadcast'}\"");
                my $ck_active = $If->{'active'} ? "on" : "off";
                send_cmd($LCD, "menu_add_item \"$if_mn\" ${if_mn}_active_cbx checkbox \"Active\" -value $ck_active");
                send_cmd($LCD, "menu_add_item \"$if_mn\" ${if_mn}_apply menu \"Apply\"");
                send_cmd($LCD, "menu_add_item \"${if_mn}_apply\" ${if_mn}_apply_no action \"$no_title\" -next _close_");
                send_cmd($LCD, "menu_add_item \"${if_mn}_apply\" ${if_mn}_apply_yes action \"$yes_title\" -next _quit_");
            }
        }
    }

	send_cmd($LCD, "menu_add_item \"\" passwd menu \"Reset Password\"");
	send_cmd($LCD, "menu_add_item \"passwd\" passwd_no action \"$no_title\" -next _close_");
	send_cmd($LCD, "menu_add_item \"passwd\" passwd_yes action \"$yes_title\" -next _quit_");

	send_cmd($LCD, "menu_add_item \"\" shutdown menu \"Shutdown\"");
	send_cmd($LCD, "menu_add_item \"shutdown\" shutdown_no action \"$no_title\" -next _close_");
	send_cmd($LCD, "menu_add_item \"shutdown\" shutdown_yes action \"$yes_title\" -next _quit_");

	send_cmd($LCD, "menu_add_item \"\" reboot menu \"Reboot\"");
	send_cmd($LCD, "menu_add_item \"reboot\" reboot_no action \"$no_title\" -next _close_");
	send_cmd($LCD, "menu_add_item \"reboot\" reboot_yes action \"$yes_title\" -next _quit_");

    # check if Virtd menu is on
    if( lc($CONF->val("Menu Virtd","Active","False")) eq 'true' ){
        my $virtd_title = $CONF->val("Menu Virtd","Title","Restart Virtd");
        send_cmd($LCD, "menu_add_item \"\" virtd menu \"$virtd_title\"");
        send_cmd($LCD, "menu_add_item \"virtd\" virtd_no action \"$no_title\" -next _close_");
        send_cmd($LCD, "menu_add_item \"virtd\" virtd_yes action \"$yes_title\" -next _quit_");
    }

    # check if SSH menu is on
    if( lc($CONF->val("Menu SSH","Active","False")) eq 'true' ){
        my $ssh_title = $CONF->val("Menu SSH","Title","Restart SSH");
        send_cmd($LCD, "menu_add_item \"\" sshd menu \"$ssh_title\"");
        send_cmd($LCD, "menu_add_item \"sshd\" sshd_no action \"$no_title\" -next _close_");
        send_cmd($LCD, "menu_add_item \"sshd\" sshd_yes action \"$yes_title\" -next _quit_");
    }
	send_cmd($LCD, "menu_set_main \"\"");
}

sub reloadmenus {

    # no/yes title translation
    my $no_title = $CONF->val("Translation","No","No");
    my $yes_title = $CONF->val("Translation","Yes","Yes");

    # check if Network menu is on
    if( lc($CONF->val("Menu Network","Active","False")) eq 'true' ){
        my $net_title = $CONF->val("Menu Network","Title","Network");
        send_cmd($LCD, "menu_add_item \"\" network menu \"$net_title\"");

        my @l_ifs = ();
        my $if_i = $CONF->val("Menu Network","If");
        push(@l_ifs,$if_i) if( $if_i );
        my $i = 0;
        while( $if_i = $CONF->val("Menu Network","If$i") ){
            push(@l_ifs,$if_i);
            $i++;
        }
    
        if( !@l_ifs ){
            my %If = ETVA::Utils::get_defaultinterface();
            push(@l_ifs, $If{'name'} ) if( $If{'name'} );
        }

        for my $if (@l_ifs){
            my $If = ETVA::Utils::get_interface($if);
        
            if( $If ){  # only If exists
                $if = $If->{'name'};
                my %If_conf = ETVA::NetworkTools::get_ip_conf($if);
                my $ifbkp = $if;
                $ifbkp =~ s/[^a-zA-Z0-9:.]//g;
                my $if_mn = "if_$ifbkp";
                my $ck_dhcp = ( $If->{'dhcp'} || ( $If_conf{'BootProto'} eq 'dhcp' ))? "on" : "off";
                send_cmd($LCD, "menu_set_item \"$if_mn\" ${if_mn}_dhcp_cbx -value $ck_dhcp");
                send_cmd($LCD, "menu_set_item \"$if_mn\" ${if_mn}_addr -value \"$If->{'address'}\"");
                send_cmd($LCD, "menu_set_item \"$if_mn\" ${if_mn}_netmask -value \"$If->{'netmask'}\"");
                send_cmd($LCD, "menu_set_item \"$if_mn\" ${if_mn}_broadcast -value \"$If->{'broadcast'}\"");
                my $ck_active = $If->{'active'} ? "on" : "off";
                send_cmd($LCD, "menu_add_item \"$if_mn\" ${if_mn}_active_cbx -value $ck_active");
            }
        }
    }
}

sub fd_initialization {
    # open loadavg
    open($FD{'fd_loadavg'},"/proc/loadavg");

    # open uptime
    open($FD{'fd_uptime'},"/proc/uptime");

    # open stat
    open($FD{'fd_stat'},"/proc/stat");

    # open meminfo
    open($FD{'fd_meminfo'},"/proc/meminfo");

    # open stat
    open($FD{'fd_stat'},"/proc/stat");

    # open netstats
    open($FD{'fd_netstats'},"/proc/net/dev");
}
sub setwidget_title {
    my ($title) = @_;
    send_cmd($LCD, "widget_set lcdagent title {$title}");
}
sub setwidget_scroller {
    my ($msg) = @_;
    send_cmd($LCD, "widget_set lcdagent scroller 1 2 16 1 m 15 {$msg }");
}
# end

# process OPs
sub opLOAD {
    my $lbuf;
    seek($FD{'fd_loadavg'},0,0);
    read($FD{'fd_loadavg'},$lbuf,1024);
    my $load = "";
    if( $lbuf =~ m/(\d+\.\d+) (\d+\.\d+) (\d+\.\d+)/ ){
        $load = "$1 $2 $3";
    }
    return $load;
}
sub opIP {
    return ETVA::Utils::get_ip();
}
sub opGW {
    my %R = ETVA::Utils::get_defaultroute();
    return $R{'gateway'} || "none";
}
sub opCLOCK {
    my $sep = " ";
    $sep = ":" if( time() % 2 == 0);
    # TODO Set format
    my $format = $CONF->val("CLOCK","Format",'%d/%m/%Y %H:%M');
    open(FP,"date +'$format' |");
    my $clock = <FP>;
    close(FP);
    chomp($clock);
    return $clock;
}
sub opMEM {
    my $lbuf;
    seek($FD{'fd_meminfo'},0,0);
    read($FD{'fd_meminfo'},$lbuf,1024);
    my $mem = "";
#MemTotal:      7919220 kB
#MemFree:       7590672 kB
#SwapTotal:     1044208 kB
#SwapFree:      1044208 kB
    my %h = ();
    if( $lbuf =~ m/MemTotal:\s+(\d+)\s+kB/s ){
        $h{'MemTotal'} = ETVA::Utils::roundconvertsize("$1K");
    }
    if( $lbuf =~ m/MemFree:\s+(\d+)\s+kB/s ){
        $h{'MemFree'} = ETVA::Utils::roundconvertsize("$1K");
    }
    if( $lbuf =~ m/SwapTotal:\s+(\d+)\s+kB/s ){
        $h{'SwapTotal'} = ETVA::Utils::roundconvertsize("$1K");
    }
    if( $lbuf =~ m/SwapFree:\s+(\d+)\s+kB/s ){
        $h{'SwapFree'} = ETVA::Utils::roundconvertsize("$1K");
    }
    if( %h ){
        $mem = "M $h{'MemFree'}/$h{'MemTotal'}; S $h{'SwapFree'}/$h{'SwapTotal'}";
    } 
    return $mem;
}
my @CPU = ();
my $CPU_BUF_SIZE = 4;

sub get_cpu_load {
    my $lbuf;
    seek($FD{'fd_stat'},0,0);
    read($FD{'fd_stat'},$lbuf,1024);
# cpu  3711 0 2701 2684475 2881 2 311 0
    my %l = ();
    if( $lbuf =~ m/cpu\s+(\d+) (\d+) (\d+) (\d+) (\d+) (\d+) (\d+) (\d+)/ ){
        ($l{'user'},$l{'nice'},$l{'system'},$l{'idle'},$l{'iowait'},$l{'irq'},$l{'softirq'}) = ($1,$2,$3,$4,$5,$6,$7,$8);

        $l{'idle'} += $l{'iowait'} if( defined($l{'iowait'}) );
        $l{'system'} += $l{'irq'} if( defined($l{'irq'}) );
        $l{'system'} += $l{'softirq'} if( defined($l{'softirq'}) );

        $l{'total'} = $l{'user'} + $l{'nice'} + $l{'system'} + $l{'idle'};

        if( $State{'last_load'} ){
            my %cur = ();
            $cur{'user'} = $l{'user'} - $State{'last_load'}{'user'};
            $cur{'nice'} = $l{'nice'} - $State{'last_load'}{'nice'};
            $cur{'system'} = $l{'system'} - $State{'last_load'}{'system'};
            $cur{'idle'} = $l{'idle'} - $State{'last_load'}{'idle'};
            $cur{'total'} = $l{'total'} - $State{'last_load'}{'total'};

            return wantarray() ? %cur : \%cur;
        }
        $State{'last_load'} = \%l;
    }
    return;
}

sub opCPU {
    my $rcur = get_cpu_load();
    if( $rcur ){
        my %cur = %$rcur;
        # Shift values over by one
        for (my $i = 0; $i < ($CPU_BUF_SIZE - 1); $i++){
            $CPU[$i] = $CPU[$i + 1];
        }

        if( $cur{'total'} > 0){
            $CPU[$CPU_BUF_SIZE - 1]{'user'} = 100 * $cur{'user'} / $cur{'total'};
            $CPU[$CPU_BUF_SIZE - 1]{'nice'} = 100 * $cur{'nice'} / $cur{'total'};
            $CPU[$CPU_BUF_SIZE - 1]{'system'} = 100 * $cur{'system'} / $cur{'total'};
            $CPU[$CPU_BUF_SIZE - 1]{'idle'} = 100 * $cur{'idle'} / $cur{'total'};
            $CPU[$CPU_BUF_SIZE - 1]{'total'} = 100 * ( $cur{'user'} + $cur{'nice'} + $cur{'system'} ) / $cur{'total'};
        } else {
            $CPU[$CPU_BUF_SIZE - 1]{'user'}   = 0;
            $CPU[$CPU_BUF_SIZE - 1]{'nice'}   = 0;
            $CPU[$CPU_BUF_SIZE - 1]{'system'} = 0;
            $CPU[$CPU_BUF_SIZE - 1]{'idle'}   = 0;
            $CPU[$CPU_BUF_SIZE - 1]{'total'}  = 0;
        }

        # Average values for final result
        for my $k (qw(user nice system idle total)){
            my $value = 0;
            for (my $j = 0; $j < $CPU_BUF_SIZE; $j++){
                $value += $CPU[$j]{"$k"};
            }
            $value /= $CPU_BUF_SIZE;
            $CPU[$CPU_BUF_SIZE]{"$k"} = $value;
        }

        return sprintf('CPU %.1f U %.1f S %.1f N %.1f I %.1f',$CPU[$CPU_BUF_SIZE]{'total'},$CPU[$CPU_BUF_SIZE]{'user'},$CPU[$CPU_BUF_SIZE]{'system'},$CPU[$CPU_BUF_SIZE]{'nice'},$CPU[$CPU_BUF_SIZE]{'idle'});
    }
    return;
}

sub opDF {
    my ($bsize,undef,$blocks,$bfree,$bavail) = statvfs("/");
    if( $blocks ){
        my $size = $blocks * $bsize;
        my $free = $bavail * $bsize;
        my $per_used = 100 * ($blocks - $bfree) / $blocks;
        return sprintf('/ %s/%s %d%%',ETVA::Utils::roundconvertsize($free),ETVA::Utils::roundconvertsize($size),$per_used);
    }
    return;
}

sub get_if_stats {
    my ($if) = @_;

    my $lbuf;
    seek($FD{'fd_netstats'},0,0);
    read($FD{'fd_netstats'},$lbuf,1024);

    my %st = ( 'status'=>'down' );
#  eth0:177749963  370777    0    0    0     0          0      5527 84733533  517616    0    0    0     0       0          0
    if( $lbuf =~ m/\s+${if}:\s*(\d+)\s+(\d+)\s+\d+\s+\d+\s+\d+\s+\d+\s+\d+\s+\d+\s+(\d+)\s+(\d+)\s+\d+\s+\d+\s+\d+\s+\d+\s+\d+\s+\d+/s ){
        $st{'status'} = 'up';
        $st{'time'} = time();
        ( $st{'rx_bytes'}, 
            $st{'rx_packets'},
            $st{'tx_bytes'},
            $st{'tx_packets'} ) = ($1,$2,$3,$4);

        $st{'total_bytes'} = $st{'rx_bytes'} + $st{'tx_bytes'};

        if( $State{'last_ifstats'} ){
            my $dt = $st{'time'} - $State{'last_ifstats'}{'time'};
            if( $dt > 0 ){
                $st{'rx_br'} = ( $st{'rx_bytes'} - $State{'last_ifstats'}{'rx_bytes'} ) / $dt;
                $st{'tx_br'} = ( $st{'tx_bytes'} - $State{'last_ifstats'}{'tx_bytes'} ) / $dt;
                $st{'total_br'} = ( $st{'total_bytes'} - $State{'last_ifstats'}{'total_bytes'} ) / $dt;
            }
        }

        $State{'last_ifstats'} = \%st;
    }
    return wantarray() ? %st : \%st;
}

sub opNET {
    my %st = get_if_stats("eth0");
    if( %st && ($st{'status'} eq 'up') ){
        return sprintf('U: %s D: %s',ETVA::Utils::roundconvertsize($st{'tx_br'}),ETVA::Utils::roundconvertsize($st{'rx_br'}));
    } elsif( $st{'status'} eq 'down' ){
        return "Down";
    }
    return;
}

sub opUPTIME {
    my $lbuf;
    seek($FD{'fd_uptime'},0,0);
    read($FD{'fd_uptime'},$lbuf,1024);
    if( $lbuf =~ m/(\d+\.\d+) (\d+\.\d+)/ ){
        my ($uptime,$l_id) = ($1,$2);
        my $idle = ( $uptime > 0 ) ? 100 * $l_id / $uptime : 100;
        my $days = int($uptime / 86400);
        my $hour = int(($uptime % 86400) / 3600);
        my $min  = int(($uptime % 3600) / 60);
        my $sec  = int($uptime % 60);

        my $sd = $days == 1 ? "" : "s";
        return sprintf('Up %d day%s %02d:%02d:%02d %3i%% idle',$days,$sd,$hour,$min,$sec,$idle);
    }
}

sub opref {
    my ($op) = @_;
    return __PACKAGE__->can("op${op}");
}
sub opvalid {
    my ($op) = @_;
    return &opref($op) ? 1 : 0;
}

sub checkVirtd {
    my @l = ETVA::Utils::find_procname("/usr/bin/perl virtd");
    return @l ? 1 : 0;
}
sub opVIRTD {
    return &checkVirtd() ? "Running" : "Not running";
}

sub processop {
    my ($op) = @_;

    my $func = &opref($op);
    if( $func ){
        return &$func();
    } else {
        ETVA::Utils::plog("processop: invalid op($op)") if( $DEBUG );
    }
    return "";
}
# end

sub handle_term {
    my $e = shift;
    # change title
    &setwidget_title("Shutdown");
    # change message
    &setwidget_scroller("System is going shutdown...");
    # sleep by 2secs
    sleep(2);
    $e->remove;
    # exit
    POSIX::_exit(0);
}
sub handle_reload {
    my $e = shift;

    # re-load configuration from ini file
    &load_conf();
    ETVA::Utils::plog("reload configuration file");
}

my $timer = 0;
sub handle_time {
    my $e = shift;

    if( $timer == 0 ){
        # initialization
        &initialization();
    }

    if( $timer == $T_ALARM ){
        $timer = 0;
        if( $State{'listen'} && &opvalid($State{'op'})){
            my $force = 0;
            if( $State{'prev_op'} ne $State{'op'} ){
                # print title op
                my $op_title = $CONF->val($State{'op'},"Title","$State{'op'}");
                &setwidget_title($op_title);
                # clean last message
                delete $State{"prev_msg_$State{'op'}"};
                $force = 1;
            }

            my $msg = &processop($State{'op'});
            if( $force ||     # if force to render messag or...
                ( $msg &&     # if have message
                    ( $State{"prev_msg_$State{'op'}"} ne $msg ) ) ){  # and have change from last call
                &setwidget_scroller($msg);
                $State{"prev_msg_$State{'op'}"} = $msg;
            }
            $State{'prev_op'} = $State{'op'};
        }
    }

    $timer++;

    # renew event
    $e->add;
}

my %IFS = ();
sub ev_select_if {
    my ($if,$a) = @_;
    my %I = $IFS{"$if"} ? %{$IFS{"$if"}} : ();
    ETVA::Utils::plog("I=",Dumper(\%I)) if( $DEBUG );
    my %O = ETVA::Utils::get_interface($if);
    ETVA::Utils::plog("O=",Dumper(\%O)) if( $DEBUG );

    my %T = ETVA::NetworkTools::get_ip_conf($if);
    ETVA::Utils::plog("T=",Dumper(\%T)) if( $DEBUG );

    my %If = ( 'if'=>$if );
    if( $I{'active'} ){
        $If{'active'} = ( !$I{'active'} || ($I{'active'} eq 'off') ) ? 0 : 1;
    } else {
        $If{'active'} = $O{'active'} || 0;
    }
    $If{'ip'} = $I{'addr'} || $O{'address'} || $T{'IP'};
    $If{'netmask'} = $I{'netmask'} || $O{'netmask'} || $T{'Netmask'};
    $If{'gateway'} = $I{'gateway'} || $O{'gateway'} || $T{'Gateway'};
    $If{'broadcast'} = $I{'broadcast'} || $O{'broadcast'} || $T{'Broadcast'};
    if( $I{'dhcp'} ){
        $If{'bootproto'} = ( !$I{'dhcp'} || ($I{'dhcp'} eq 'off') )? "none" : "dhcp";
        $If{'dhcp'} = ( !$I{'dhcp'} || ($I{'dhcp'} eq 'off') )? 0 : 1;
    } else {
        $If{'bootproto'} = $T{'BootProto'};
        $If{'dhcp'} = ( uc($T{'BootProto'}) eq 'DHCP' )? 1 : 0;
    }

    ETVA::Utils::plog("if=",Dumper(\%If)) if( $DEBUG );
    delete $IFS{"$if"};

    if( ETVA::NetworkTools::change_if_conf(%If) ){
        ETVA::Utils::plog( "ETVA::NetworkTools::change_if_conf ok!" );
        if( $If{'active'} && !ETVA::NetworkTools::active_ip_conf(%If) ){
            ETVA::Utils::plog( "ETVA::NetworkTools::active_ip_conf nok!" );
            ETVA::Utils::plog( "interface $if activation failed!" ) if( $DEBUG );
        }
    } else {
        ETVA::Utils::plog( "interface $if change configuration failed!" ) if( $DEBUG );
    }
}

sub ev_select_virtd_yes {
    my ($p) = ETVA::Utils::find_procname("/usr/bin/perl .*virtd");
    if( $p ){
        ETVA::Utils::cmd_exec("kill $p->{'pid'}");
    }
}
sub ev_select_shutdown_yes {
    ETVA::Utils::plog( "ev_select_shutdown_yes" );
    ETVA::Utils::cmd_exec("poweroff") if( !$testing );
}
sub ev_select_passwd_yes {
    ETVA::Utils::plog( "ev_select_passwd_yes" );
    ETVA::Utils::cmd_exec("/usr/bin/passwd -d") if( !$testing );
}
sub ev_select_sshd_yes {
    ETVA::Utils::plog( "ev_select_sshd_yes" );
    ETVA::Utils::cmd_exec("/etc/init.d/sshd restart") if( !$testing );
}
sub ev_select_reboot_yes {
    ETVA::Utils::plog( "ev_select_reboot_yes" );
    ETVA::Utils::cmd_exec("reboot") if( !$testing );
}

sub ev_update {
    my ($i,$v) = @_;

    if( $i =~ m/^((if_([a-zA-Z0-9:.]+))_([a-zA-Z0-9]+))(_([a-zA-Z0-9]+))?/ ){
        my ($mn,$if_mn,$if,$f,$o) = ($1,$2,$3,$4,$6);
#        ETVA::Utils::plog( ">>>>update mn=$mn if_mn=$if_mn if=$if f=$f v=$v" );
        $IFS{"$if"}{"$f"} = $v;
        if( $o eq 'cbx' ){
        }
    }
}

sub handle_menuevent {
    my ($r) = @_;
    ETVA::Utils::plog "handle_menuevent $r" if( $DEBUG ); 
    if( $r =~ m/^menuevent\s+(\S+)\s+(\S+)(\s+(\S+))?/ ){
        my ($t,$i,$v) = ($1,$2,$4);
        if( $t eq 'select' ){
            if( $i =~ m/^if_([a-zA-Z0-9:.]+)_([a-zA-Z0-9]+)/ ){
                my ($if,$a) = ($1,$2);
                &ev_select_if($if,$a,$v);
            } else {
                my $func = __PACKAGE__->can("ev_${t}_${i}");
                if( $func ){
                    &$func($v);
                }
            }
            &reloadmenus();
        } elsif( $t eq 'update' ){
            &ev_update($i,$v);
        }
    }
}

sub handle_response {
    my ($r) = @_;
    if( $r =~ m/^key (\w+)/ ){
        my $key = $1;
        if( $key eq 'Up' ){
            $State{'i'}++;
            $State{'i'} = 0 if( $State{'i'} >= scalar(@OPS) );
        } elsif( $key eq 'Down' ){
            $State{'i'}--;
            $State{'i'} = scalar(@OPS) - 1 if( $State{'i'} < 0 );
        }
        $State{'op'} = @OPS[$State{'i'}];
    } elsif( $r =~ m/^listen/ ){
        $State{'listen'} = 1;
    } elsif( $r =~ m/^ignore/ ){
        $State{'listen'} = 0;
    } elsif( $r =~ m/^menuevent/ ){
        &handle_menuevent($r);
    }
}

sub handle_read {
    my $e = shift;
    my $lcd = $e->fh;
    process_read($lcd,\&handle_response,1);
}

sub load_conf {
    my $cfg_file = $ENV{'CFG_FILE'} || "lcdagent.ini";
    if( ! -e "$cfg_file" ){
        die "config file not found";
    }
    $CONF = Config::IniFiles->new( -file =>"$cfg_file" );
    $server = $CONF->val( "lcdagent", "Server", "localhost" );
    $port = $CONF->val( "lcdagent", "Port", "1366" );
    $T_ALARM = $CONF->val( "lcdagent", "T_ALARM", "1" );
    $testing = $CONF->val( "lcdagent", "Testing", "0" );
    $DEBUG = $CONF->val( "lcdagent", "DEBUG", "0" );

    %AllSections = map { $_ => ( ( lc($CONF->val("$_","Active","false")) eq "true" ) ? 1 : 0) } $CONF->Sections();

    my %h_OPS = map { $_ => 1 } @ALLOPS;
    my @c_ops = grep { $h_OPS{"$_"} && $AllSections{$_} } $CONF->Sections();    # use config ini file order
    if( @c_ops ){
        @OPS = @c_ops;
        $State{'op'} = $OPS[0]; # start op
    }
}
sub main {

    # load configuration from ini file
    &load_conf();

    # connect to LCD
    $LCD = new IO::Socket::INET(
                        'Proto' => 'tcp',
                        'PeerAddr' => "$server",
                        'PeerPort' => "$port"
                    ) or die "Cannot connect to LCD ($server:$port)\n";

    # Make sure our messages get there right away
    $LCD->autoflush(1);

    # create event of reads
    my $emain = event_new($LCD, EV_READ|EV_PERSIST, \&handle_read);
    # create event of time alarm
    my $etimer = timer_new(\&handle_time);

    my $esint = signal_new(SIGINT, \&handle_term);
    my $esterm = signal_new(SIGTERM, \&handle_term);
    my $ehup = signal_new(SIGHUP, \&handle_reload);

    # register events
    $_->add for $emain, $etimer, $esint, $esterm, $ehup;
    event_mainloop;
}
&main();

1;
