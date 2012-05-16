#!/usr/bin/perl

=pod

=head1 NAME

VirtAgent::Network -

=head1 SYNOPSIS

...

=head1 DESCRIPTION

...

=head1 METHODS

=over 4

=cut

package VirtAgent::Network;

use strict;

BEGIN {
    # this is the worst damned warning ever, so SHUT UP ALREADY!
    $SIG{__WARN__} = sub { warn @_ unless $_[0] =~ /Use of uninitialized value/ };

    require VirtAgent;
    use vars qw($VERSION @ISA @EXPORT @EXPORT_OK %EXPORT_TAGS $CRLF);
    $VERSION = '0.0.1';
    @ISA = qw( VirtAgent );
    @EXPORT = qw( );
}

use File::Path qw( mkpath );

use ETVA::Utils;

my %BRInfo = ();
my %NetDevices;
my %VLANDevices;
my $NETCOUNT = 0;
my $net_scripts_dir = "/etc/sysconfig/network-scripts";
my $devices_dir = "/etc/sysconfig/networking/devices";
my $etva_config_dir = $ENV{'etva_conf_dir'} || "/etc/sysconfig/etva-vdaemon/config";

sub loadnetdev {
    my $self = shift;
    my ($force) = @_;

    if( $force || !%NetDevices ){ $self->findnetdev($force); }

    # count physical devices
    $NETCOUNT = grep { $_->{'phy'} && !$_->{'dummy'} } values %NetDevices;

    # load bridges created
#    if( $force || !%BRInfo ){ $self->loadbridges($force); }

}

sub findnetdev {
    my $self = shift;

    %NetDevices = ();

    # network physical devices
    my $dir_devices = "/sys/devices";
    open(F,"/usr/bin/find $dir_devices -name 'net:*'|");
    while(<F>){
        chomp;
        my $netdev_dir = $_;
        my $netdev_address_file = "$netdev_dir/address";
        open(N,$netdev_address_file);
        my $netdev_address = <N>;
        chomp($netdev_address);
        close(N);
        my ($netdev_name) = ($_ =~ m/net:(\w+\d+)/);
        $NetDevices{$netdev_name} = {
                                        "address" => $netdev_address,
                                        "devdir" => $netdev_dir,
                                        "name" => $netdev_name,
                                        "device" => "$netdev_name",
                                        "phy" => 1
                                    };
    }
    close(F);

    # other devices
    open(E,"/proc/net/dev");
    my $dum = <E>;
    $dum = <E>;
    while(<E>){
        chomp;
        my ($d,$ol) = split(/:/,$_);
        $d = trim($d);
        $NetDevices{"$d"}{'device'} = "$d";
        if( -e "/sys/class/net/$d/bridge" ){
            $NetDevices{"$d"}{"isbridge"} = 1;
        }
        if( -e "/sys/class/net/$d/bonding" ){
            $NetDevices{"$d"}{"bonding"} = 1;
            my $fslaves = "/sys/class/net/$d/bonding/slaves";
            open(FS,$fslaves);
            while(<FS>){
                chomp;
                my @lif = split(/ /,$_);
                for my $i (@lif){
                    $NetDevices{"$i"}{"slave"} = 1;
                    $NetDevices{"$i"}{"bond"} = $d;
                }
            }
            close(FS);
        }
        if( -e "/sys/class/net/$d/brport" ){

            my $pbr = readlink("/sys/class/net/$d/brport/bridge");
            my @pbr = split(/\//,$pbr);
            
            $NetDevices{"$d"}{"bridge"} = pop @pbr;
        }
        if( ( !-e "/sys/class/net/$d/device" ) &&
            ( -e "/sys/class/net/p$d" ) ){
            my $pd = "p$d";
            $NetDevices{"$pd"}{'dummy'} = 1;    # mark as dummy
            $NetDevices{"$d"}{'devdir'} = $NetDevices{"$pd"}{'devdir'};
            $NetDevices{"$d"}{'phy'} = 1;
            $NetDevices{"$d"}{'pdev'} = $pd;
        }
    }
    close(E);

    # default route
    my $dev = defaultroute();
    if( $dev ){
        $NetDevices{"$dev"}{'defaultroute'} = 1;
    }

    # load virtual lan
    $self->loadvlans();

    return wantarray ? %NetDevices : \%NetDevices;
}
sub loadvlans {
    my $self = shift;

    %VLANDevices = ();

    my $vdir = "/proc/net/vlan";
    opendir(D,$vdir);
    my @veths = grep { !/^\.+$/ } readdir(D);
    for my $veth (@veths){
        next if( $veth eq 'config' );
        open(F,"$vdir/$veth");
        my $fl = <F>;
        chomp $fl;
        my $pd;
        my ($dd,undef,$vid) = split(/\s+/,$fl);
        $vid = trim($vid);
        
        while(<F>){
            chomp;
            if( /^Device:/ ){
                (undef,$pd) = split(/:/,$_);
                $pd = trim($pd);
            }
        }
        close(F);
        $VLANDevices{"$veth"}{'device'} = "$veth";
        $VLANDevices{"$veth"}{'vid'} = $vid;
        $NetDevices{"$veth"}{'vlan'} = 1;
        $NetDevices{"$veth"}{'phydevice'} = $VLANDevices{"$veth"}{'phydevice'} = $pd;
        $NetDevices{"$veth"}{'vid'} = $VLANDevices{"$veth"}{'vid'} = $vid;
        $NetDevices{"$pd"}{'lvid'} = $vid;
    }
    closedir(D);

    return wantarray ? %VLANDevices : \%VLANDevices;
}
sub getnetdev {
    my $self = shift;

    if( !%NetDevices ){
        $self->loadnetinfo(1);
    }

    return wantarray ? %NetDevices : \%NetDevices;
}

sub vlan_name_type {
    # * name-type:  VLAN_PLUS_VID (vlan0005), VLAN_PLUS_VID_NO_PAD (vlan5),
    #               DEV_PLUS_VID (eth0.0005), DEV_PLUS_VID_NO_PAD (eth0.5)

    #               VLAN_NAME_TYPE_PLUS_VID, VLAN_NAME_TYPE_PLUS_VID_NO_PAD
    #               VLAN_NAME_TYPE_RAW_PLUS_VID, VLAN_NAME_TYPE_RAW_PLUS_VID_NO_PAD

    my $self = shift;

    my $type = "VLAN_NAME_TYPE_RAW_PLUS_VID";
    open(F,"/proc/net/vlan/config");
    while(<F>){
        chomp;
        if( /Name-Type:\s+(\S+)/ ){
            $type = $1;
            last;
        }
    }
    close(F);

    return $type;
}

sub vlan_todevice {
    my $self = shift;
    my (%p) = @_;

    my $type = $self->vlan_name_type();
    if( defined($p{'name'}) && defined($p{'vlanid'}) ){
        return ($type eq "VLAN_NAME_TYPE_PLUS_VID_NO_PAD") ? "vlan$p{'vlanid'}" : "$p{'name'}.$p{'vlanid'}";
    } else {
        return ($type eq "VLAN_NAME_TYPE_PLUS_VID_NO_PAD") ? "vlan$p{'vid'}" : "$p{'phydevice'}.$p{'vid'}";
    }
}

sub boot_vlanadd {
    my $self = shift;
    my (%p) = @_;

    my $device = $p{'device'};
    if( !$device ){
        $device = $self->vlan_todevice( %p );
    }

    my $cfgfile = "$net_scripts_dir/ifcfg-$device";
    if( !$VLANDevices{"$device"} && ! -e "$cfgfile" ){

        $p{'VLAN'} = 'yes';
        $p{'ONBOOT'} = 'yes';
        $p{'BOOTPROTO'} = 'static';
        $p{'PHYSDEV'} = $p{'name'} || $p{'phydevice'};
        $p{'DEVICE'} = $p{'device'} = $device;
        
        open(F,">$cfgfile");
        for my $k (qw(DEVICE VLAN PHYSDEV ONBOOT BOOTPROTO)){
            my $lc_k = lc($k);
            if( $p{"$lc_k"} || $p{"$k"} ){
                my $v = $p{"$lc_k"} || $p{"$k"};
                my $s_v = ($v =~ /\s/)? "\"$v\"" : $v;  # if have spaces
                print F $k,"=",$s_v,"\n";
            }
        }
        close(F);

        my ($s,$msg) = cmd_exec("/sbin/ifup",$device);
        unless( $s == 0 || $s == -1 ){
            unlink "$cfgfile" if( -e "$cfgfile" );  # remove file if fail!
            return retErr("_ERR_VLANADD_","Error add new virtual-lan: $msg");
        }
        return retOk("_OK_VLANADD_","VLan created successfully.");

    } else {
        return retErr("_ERR_VLANADD_","Error vlan already exists");
    }
}

sub boot_vlanrem {
    my $self = shift;
    my (%p) = @_;

    my $device = $p{'device'};
    if( !$device ){
        $device = $self->vlan_todevice( %p );
    }

    my $cfgfile = "$net_scripts_dir/ifcfg-$device";
    if( $VLANDevices{"$device"} || -e "$cfgfile" ){
        my ($s,$msg) = cmd_exec("/sbin/ifdown",$device);
        unless( $s == 0 || $s == -1 ){
            return retErr("_ERR_VLANREM_","Error remove new virtual-lan: $msg");
        }

        unlink($cfgfile);

        return retOk("_OK_VLANREM_","Remove vlan successfully.");
    } else {
        return retErr("_ERR_VLANREM_","Error vlan doesnt exists");
    }
}

sub vlancreate {
    my $self = shift;
    my ($iname,$vlanid) = my %p = @_;
    $iname = $p{'iname'} if( $p{'iname'} );
    $vlanid = $p{'vlanid'} if( $p{'vlanid'} );

    if( !%NetDevices ){
        $self->loadnetinfo(1);
    }

    if( $NetDevices{$iname} ){
        my %E = $self->boot_vlanadd( 'name'=>$iname, 'vlanid'=>$vlanid );
        unless( !isError(%E) ){
            return wantarray() ? %E : \%E;
        }
        $self->loadnetinfo(1);
        return retOk("_OK_VLANCREATE_","VLan created successfully.");
    } else {
        return retErr("_INVALID_NETDEVICE","Invalid network interface device");
    }
}

sub vlanremove {
    my $self = shift;
    my ($vlan) = my %p = @_;
    if( $p{'vlan'} ){
        $vlan = $p{'vlan'};
    }

    if( !%VLANDevices ){
        $self->loadnetinfo(1);
    }

    if( $VLANDevices{$vlan} ){
        my %E = $self->boot_vlanrem( 'device'=>$vlan );
        unless( !isError(%E) ){
            return wantarray() ? %E : \%E;
        }
        $self->loadnetinfo(1);
        return retOk("_OK_VLANREMOVE_","Remove vlan interace successfully.");
    } else {
        return retErr("_INVALID_VLAN","Invalid vlan interface device");
    }
}

sub nextvlanid {
    my $self = shift;
    my ($if) = my %p = @_;
    if( $p{'if'} ){
        $if = $p{'if'};
    }
    if( !%VLANDevices ){
        $self->loadnetinfo(1);
    }

    my $defvlanid = 2;

    my $maxid;
    for my $I (values %VLANDevices){
        if( $I->{'phydevice'} eq $if ){
            $maxid = $I->{'vid'} if( $maxid < $I->{'vid'} );
        }
    }
    return ( defined $maxid )? $maxid+1 : $defvlanid;
}

sub getvlanif {
    my $self = shift;
    my ($vl,$if,$id) = my %p = @_;
    if( $p{'vlan'} || $p{'if'} || $p{'id'} ){
        $vl = $p{'vlan'};
        $if = $p{'if'};
        $id = $p{'id'};
    }
    if( !%VLANDevices ){
        $self->loadnetinfo(1);
    }

    if( $vl && $VLANDevices{"$vl"} ){
        return $VLANDevices{"$vl"};
    } else {
        for my $I (values %VLANDevices){
            if( $I->{'phydevice'} eq $if ){
                if( $I->{'vid'} eq $id ){
                    return $I;
                }
            }
        }
    }
    return retErr("_ERR_VLAN_NOTFOUND_","Error vlan not found");
}

=item defaultroute

get default route

    my ($route) = VirtAgent::Network->defaultroute();

=cut

sub defaultroute {
    open(F,"/proc/net/route");
    my $fstl = <F>;             # first line
    my @topf = split(/\s+/,$fstl);
    while(<F>){
        chomp;
        next if( !$_ );
        my $il = $_;
        my ($if,$d) = split(/\s+/,$il);
        my $dr = join(".",reverse map {hex} ($d =~ m/(..)/g));
        if( $dr =~ m/0.0.0.0/ ){
            return $if;
        }
    }
    close(F);
    return;
}

=item defaultnetwork

get default network and bridge

    my ($network,$bridge) = VirtAgent::Network->defaultnetwork();

=cut

sub defaultnetwork {
    my $dev = defaultroute();
    my @r = ( "network", "default" );
    if( $dev ){
        if( -e "/sys/class/net/$dev/bridge" ){
            @r = ( "bridge", $dev );
            return wantarray() ? @r : \@r;
        }
        my ($d) = ($dev =~ m/(\d+)/);
        if( -e "/sys/class/net/peth$d/brport" &&
            -e "/sys/class/net/xenbr$d/bridge" ){
            @r = ( "bridge", "xenbr$d" );
            return wantarray() ? @r : \@r;
        }
    }
    return wantarray() ? @r : \@r;
}

=item defaultbridge

get defaultbridge

    my $bridge = VirtAgent::Network->defaultbridge();

=cut

sub defaultbridge {
    my $self = shift;
    my $dev = defaultroute();
    my $bridge = "virbr0";
    if( $dev ){
        my ($d) = ($dev =~ m/(\d+)/);
        $bridge = "virbr$d";
    } else {
        $bridge = "virbr0";
    }

    # create default bridge if not created
    if( !$BRInfo{"$bridge"} ){
        $self->brcreate( 'name'=>$bridge );
    }

    return $bridge;
}
sub loadbridges {
    my $self = shift;
    my ($force) = @_;

    if( $force || !%BRInfo ){

        %BRInfo = ();
        my $bdir = "/sys/class/net";
        opendir(D,"$bdir");
        my @bif = grep { !/^\.+$/ } readdir(D);
        closedir(D);
        for my $if (@bif){
            if( -e "$bdir/$if/bridge" ){
                my %H = ();
                $H{"name"} = $if;
                open(FB,"$bdir/$if/bridge/bridge_id");
                my $bid = <FB>;
                close(FB);
                chomp($bid);
                $H{"id"} = $bid; 

                open(FS,"$bdir/$if/bridge/stp_state");
                my $stp = <FS>;
                close(FS);
                chomp($stp);
                $H{"stp"} = $stp ? "yes":"no"; 

                opendir(B,"$bdir/$if/brif");
                my @ifs = grep { !/^\.+$/ } readdir(B);
                close(B);
                $H{"interfaces"} = join(";",@ifs);
                
                $BRInfo{"$if"} = \%H;
            }
        }
    }
    return wantarray() ? %BRInfo : \%BRInfo;
}
sub brid {
    my $self = shift;
    my ($pref) = my %p = @_;
    $pref = $p{'prefix'} if( $p{'prefix'} );

    my $maxn = 0;
    for my $br (keys %BRInfo){
        if( my ($n) = ($br =~ m/^${pref}(\d+)/) ){
            if( $n > $maxn ){
                $maxn = $n;
            }
        }
    }
    return $maxn;
}
sub ifavailable {
    my $self = shift;
    my ($if) = my %p = @_;
    if( $p{'if'} ){
        $if = $p{'if'};
    }
    my $ef = ( -e "$net_scripts_dir/ifcfg-$if" );
    return ( !$NetDevices{"$if"} && !$ef )? 1: 0;
}
sub bravailable {
    my $self = shift;
    my ($br) = my %p = @_;
    $br = $p{'br'} if( $p{'br'} );

    # testing if bridge exist
    #return 0 if( $self->brexist($br) );

    if( !%NetDevices ){
        $self->loadnetinfo(1);
    }

    # testing if is other interface type
    return $self->ifavailable( $br );
}
sub brexist {
    my $self = shift;
    my ($br) = my %p = @_;
    $br = $p{'br'} if( $p{'br'} );

    return $BRInfo{"$br"} ? 1 : 0;
}

sub brcreate_prefix {
    my $self = shift;
    my ($pref,$n) = my %p = @_;
    if( $p{'prefix'} || $p{'n'} ){
        $pref = $p{'prefix'};
        $n = $p{'n'};
    }

    # load bridges created
    if( !%BRInfo ){
        $self->loadnetinfo(1);
    }

    $pref = "virbr" if( !$pref );
    my $nx = $self->brid($pref);
    $n = $nx if( $nx > $n );
    $n++;

    return wantarray() ? ('pref'=>$pref, 'n'=>$n, 'br'=>"${pref}${n}") : "${pref}${n}";
}

=item brcreate

create bridge with name or prefix

    my $BR = VirtAgent::Network->brcreate( name=>$name );

    my $BR = VirtAgent::Network->brcreate( prefix=>$prefix );

=cut

sub boot_addbr {
    my $self = shift;
    my ($name) = my %p = @_;
    if( $p{'name'} ){
        $name = $p{'name'};
    }

    my $cfgfile = "$net_scripts_dir/ifcfg-$name";
    if( ! -e "$cfgfile" ){

        $p{'ONBOOT'} = 'yes';
        $p{'TYPE'} = 'Bridge';
        $p{'BOOTPROTO'} = 'static';
        $p{'DEVICE'} = $p{'device'} = $name;
        
        open(F,">$cfgfile");
        for my $k (qw(DEVICE TYPE ONBOOT BOOTPROTO)){
            my $lc_k = lc($k);
            if( $p{"$lc_k"} || $p{"$k"} ){
                my $v = $p{"$lc_k"} || $p{"$k"};
                my $s_v = ($v =~ /\s/)? "\"$v\"" : $v;  # if have spaces
                print F $k,"=",$s_v,"\n";
            }
        }
        close(F);

        my ($s,$msg) = cmd_exec("/sbin/ifup",$name);
        unless( $s == 0 || $s == -1 ){
            return retErr("_ERR_ADDBR_","Error add new bridge: $msg");
        }
        return retOk("_OK_ADDBR_","Bridge created successfully.");

    } else {
        return retErr("_ERR_ADDBR_","Error bridge already exists");
    }
}

sub brcreate {
    my $self = shift;
    my ($pref,$name) = my %p = @_;
    if( $p{'prefix'} || $p{'name'} ){
        $pref = $p{'prefix'};
        $name = $p{'name'};
    }

    # create name by prefix
    $name = $self->brcreate_prefix($pref) if( !$name );

    # load bridges created
    if( !%BRInfo ){
        $self->loadnetinfo(1);
    }

    # only if not alread created
    if( !$BRInfo{"$name"} ){
        # TODO
        #   add interfaces to bridge

        my %E = $self->boot_addbr($name);
        unless( !isError(%E) ){
            return wantarray() ? %E : \%E;
        }
        $self->loadnetinfo(1);
        return retOk("_OK_BRCREATE_","Bridge created successfully.");
    } else {
        return retErr("_ERR_BRCREATE_","Error bridge already exists");
    }
}

=item braddif

add interface to bridge

    my $BR = VirtAgent::Network->braddif( br=>$br, if=>$if );

=cut

sub boot_braddif {
    my $self = shift;
    my ($br,$if) = my %p = @_;
    if( $p{'br'} || $p{'if'} ){
        $br = $p{'br'};
        $if = $p{'if'}
    }

    my $cf_if = "$net_scripts_dir/ifcfg-$if";
    if( -e "$cf_if" ){
        open(R,"$cf_if");
        my @l = <R>;
        close(R);

        # replace or add new line
        if( !grep { s/^BRIDGE\s*=.*/BRIDGE=$br/ } @l ){
            push(@l, "BRIDGE=$br\n");
        }

        open(W,">$cf_if");
        print W @l;
        close(W)
    }

    my $s = cmd_exec("/usr/sbin/brctl","addif",$br,$if);
    unless( $s == 0 || $s == -1 ){
        return retErr("_ERR_BRIDGE_ADDIF_","Error add interface to bridge");
    }
    return retOk("_OK_BRIDGE_ADDIF_","Add interface to bridge ok.");
}

sub braddif {
    my $self = shift;
    my ($br,$if) = my %p = @_;
    if( $p{'br'} || $p{'if'} ){
        $br = $p{'br'};
        $if= $p{'if'};
    }

    # load bridges created
    if( !%BRInfo ){
        $self->loadnetinfo(1);
    }

    # if bridge doenst exists!
    if( $BRInfo{"$br"} ){
        my %E = $self->boot_braddif( $br, $if );
        unless( !isError(%E) ){
            return wantarray() ? %E : \%E;
        }
        $self->loadnetinfo(1);
        return retOk("_OK_BRIDGE_ADDIF_","Add interface to bridge ok.","_RET_OBJ_",$BRInfo{"$br"});
    } else {
        return retErr("_ERR_BRIDGE_ADDIF_","Bridge doenst exists!");
    }
}

=item brdelif

del interface to bridge

    my $BR = VirtAgent::Network->brdelif( br=>$br, if=>$if );

=cut

sub boot_brdelif {
    my $self = shift;
    my ($br,$if) = my %p = @_;
    if( $p{'br'} || $p{'if'} ){
        $br = $p{'br'};
        $if = $p{'if'}
    }

    my $cf_if = "$net_scripts_dir/ifcfg-$if";
    if( -e "$cf_if" ){
        open(R,"$cf_if");
        my @l = <R>;
        close(R);

        # replace or add new line
        @l = grep { !/^BRIDGE\s*=.*/ } @l;

        open(W,">$cf_if");
        print W @l;
        close(W)
    }

    my $s = cmd_exec("/usr/sbin/brctl","delif",$br,$if);
    unless( $s == 0 || $s == -1 ){
        return retErr("_ERR_BRIDGE_DELIF_","Error del interface to this bridge");
    }
    return retOk("_OK_BRIDGE_DELIF_","Del interface to this bridge ok.");
}

sub brdelif {
    my $self = shift;
    my ($br,$if) = my %p = @_;
    if( $p{'br'} || $p{'if'} ){
        $br = $p{'br'};
        $if= $p{'if'};
    }

    # load bridges created
    if( !%BRInfo ){
        $self->loadnetinfo(1);
    }

    # if bridge doenst exists!
    if( $BRInfo{"$br"} ){
        if( grep { $_ eq $if } split(/;/,$BRInfo{"$br"}{'interfaces'}) ){
            my %E = $self->boot_brdelif( $br, $if );
            unless( !isError(%E) ){
                return wantarray() ? %E : \%E;
            }
            $self->loadnetinfo(1);
            return retOk("_OK_BRIDGE_DELIF_","Del interface to this bridge ok.","_RET_OBJ_",$BRInfo{"$br"});
        } else {
            return retErr("_ERR_BRIDGE_DELIF_","Interface not attached to this bridge!");
        }
    } else {
        return retErr("_ERR_BRIDGE_DELIF_","Bridge doenst exists!");
    }
}

=item list_bridges

list of bridges

    my $List = VirtAgent::Network->list_bridges( );

=cut

# list_bridges
#   list of bridges
sub list_bridges {
    my $self = shift;
    
    return wantarray() ? %BRInfo: \%BRInfo;
}

=item getnetinfo

    get network info: Network Devices, VLAN Devices, Bridge info

=cut

sub loadnetinfo {
    my $self = shift;
    my $force = shift || 0;

    if( $force || !%BRInfo ){ $self->loadbridges($force); }
    if( $force || !%NetDevices ){ $self->loadnetdev($force); }
    if( $force || !%VLANDevices ){ $self->loadvlans(); }

    return;
}

# getnetinfo
sub getnetinfo {
    my $self = shift;
    my $force = shift || 0;

    $self->loadnetinfo( $force );

    my %res = ( 'netcount'=>$NETCOUNT, 'netdevices'=>\%NetDevices, 'vlandevices'=>\%VLANDevices, 'brinfo'=>\%BRInfo );

    return wantarray() ? %res : \%res;
}

# testing if have bonding
sub have_bonding {
    return ( -e "/proc/net/bonding" ) ? 1 : 0;
}

sub get_ipaddr {
    my $self = shift;
    my ($if) = my %p = @_;

    if( $p{'if'} ){
        $if = $p{'if'};
    }

    my $gateway = '';

    my ($D) = grep { $_->{'default'} && ( $_->{'iface'} eq $if ) } $self->list_routes();
    if( $D ){
        $gateway = $D->{'gateway'};
    }

    open(F,"/sbin/ip addr show $if scope global  | ");
    while(<F>){
        if( /inet (\S+)/ ){
            my $ipaddr = $1;

            my ($ip,$netmask) = &get_netmask($ipaddr);

            my $network = &make_netaddr( $ip,$netmask );
        
            return ($ip,$netmask,$network,$gateway);
        } 
    }

    close(F);
    return;
}

sub get_netmask {
    my ($ipaddr) = @_;
    my ($ip,$n) = split(/\//,$ipaddr);

    my @lmask = ();

    my $c = $n;
    while( $c > 0 ){
        if( $c >= 8 ){
            push(@lmask,255);   # 
        } else {
            # fill $c left bits
            push(@lmask, ((0xff00>>$c)&0xff) );
        }
        $c -= 8;
    }
    while( scalar(@lmask) < 4 ){
        push(@lmask,0); # put 0 in others
    }
    my $netmask = join('.',@lmask);
    return ($ip,$netmask);
}
sub make_netaddr {
    my ($addr,$mask) = @_;

    my ($ip1, $ip2, $ip3, $ip4) = split(/\./, $addr);
    my ($nm1, $nm2, $nm3, $nm4) = split(/\./, $mask);
    return sprintf "%d.%d.%d.%d",
                                    ($ip1 & int($nm1))&0xff,
                                    ($ip2 & int($nm2))&0xff,
                                    ($ip3 & int($nm3))&0xff,
                                    ($ip4 & int($nm4))&0xff;
}

sub boot_chgipaddr {
    my $self = shift;
    my %p = @_;
    my $if = $p{'if'};

    # down interface
    my $s = cmd_exec("/sbin/ifdown",$if);
    unless( $s == 0 || $s == -1 ){
        return retErr("_ERR_CHANGE_IPADDR_","Error change ip address");
    }

    my $cf_if = "$net_scripts_dir/ifcfg-$if";
    if( -e "$cf_if" ){
        open(R,"$cf_if");
        my @l = <R>;
        close(R);

        for my $k_uc (qw(IPADDR NETMASK NETWORK BOOTPROTO USERCTL BRIDGE GATEWAY)){
            my $k = lc($k_uc);
            my $v = $p{"$k"};

            if( defined($v) ){
                # replace or add new line
                if( !grep { s/^$k_uc\s*=.*/$k_uc=$v/ } @l ){
                    push(@l, "$k_uc=$v\n");
                }
            }
        }

        open(W,">$cf_if");
        print W @l;
        close(W)
    }

    $s = cmd_exec("/sbin/ifup",$if);
    unless( $s == 0 || $s == -1 ){
        return retErr("_ERR_CHANGE_IPADDR_","Error change ip address");
    }
    return retOk("_OK_CHANGE_IPADDR_","Change ip address ok.");
}

=item addgateway

add gateway to interface

    my $OK = VirtAgent::Network->addgateway( if=>$if, gateway=>$gw );

=cut

sub boot_addgateway {
    my $self = shift;
    my ($if,$gw) = my %p = @_;
    if( $p{'gw'} || $p{'if'} ){
        $if = $p{'if'};
        $gw = $p{'gw'};
    }

    my $cf_if = "$net_scripts_dir/ifcfg-$if";
    if( -e "$cf_if" ){
        open(R,"$cf_if");
        my @l = <R>;
        close(R);

        # replace or add new line
        if( !grep { s/^GATEWAY\s*=.*/GATEWAY=$gw/ } @l ){
            push(@l, "GATEWAY=$gw\n");
        }

        open(W,">$cf_if");
        print W @l;
        close(W)
    }

    my $s = cmd_exec("/sbin/route","add","default","gw",$gw,"dev",$if);
    unless( $s == 0 || $s == -1 ){
        return retErr("_ERR_ADDGATEWAY_","Error add gateway to interface");
    }
    return retOk("_OK_ADDGATEWAY_","Add gateway to interface ok.");
}

sub addgateway {
    my $self = shift;
    my ($if,$gw) = my %p = @_;
    if( $p{'gw'} || $p{'if'} ){
        $gw = $p{'gw'};
        $if= $p{'if'};
    }

    # load devices created
    if( !%NetDevices ){
        $self->loadnetinfo(1);
    }

    # if interface doenst exists!
    if( $NetDevices{"$if"} ){
        my %E = $self->boot_addgateway( $if, $gw );
        unless( !isError(%E) ){
            return wantarray() ? %E : \%E;
        }
        $self->loadnetinfo(1);
        return retOk("_OK_ADDGATEWAY_","Add gateway to interface ok.","_RET_OBJ_",$NetDevices{"$if"});
    } else {
        return retErr("_ERR_ADDGATEWAY_","Interface doenst exists!");
    }
}

=item delgateway

del gateway from intrface

    my $OK = VirtAgent::Network->delgateway( if=>$if, gw=>$gw );

=cut

sub boot_delgateway {
    my $self = shift;
    my ($if,$gw) = my %p = @_;
    if( $p{'gw'} || $p{'if'} ){
        $gw = $p{'gw'};
        $if = $p{'if'}
    }

    my $cf_if = "$net_scripts_dir/ifcfg-$if";
    if( -e "$cf_if" ){
        open(R,"$cf_if");
        my @l = <R>;
        close(R);

        # replace or add new line
        @l = grep { !/^GATEWAY\s*=.*/ } @l;

        open(W,">$cf_if");
        print W @l;
        close(W)
    }

    my $s = cmd_exec("/sbin/route","del","default","gw",$gw,"dev",$if);
    unless( $s == 0 || $s == -1 ){
        return retErr("_ERR_DELGATEWAY_","Error de gateway from interface.");
    }
    return retOk("_OK_DELGATEWAY_","Del gateway from this interface ok.");
}

sub delgateway {
    my $self = shift;
    my ($if,$gw) = my %p = @_;
    if( $p{'gw'} || $p{'if'} ){
        $gw = $p{'gw'};
        $if= $p{'if'};
    }

    # load devices created
    if( !%NetDevices ){
        $self->loadnetinfo(1);
    }

    # if interface doenst exists!
    if( $NetDevices{"$if"} ){
        my %E = $self->boot_delgateway( $if, $gw );
        unless( !isError(%E) ){
            return wantarray() ? %E : \%E;
        }
        $self->loadnetinfo(1);
        return retOk("_OK_DELGATEWAY_","Del gateway from tnterface ok.","_RET_OBJ_",$NetDevices{"$if"});
    } else {
        return retErr("_ERR_DELGATEWAY_","Interface doenst exists!");
    }
}

sub save_boot_interface {
    my $self = shift;
    my (%p) = @_;

    my $name = $p{'name'};
    my $fn = "ifcfg-$name";
    if( my $v = $p{'virtual'} ){
        $fn .= ":$v";
    }

    my %conf = ();
    %conf = loadconfigfile("$net_scripts_dir/$fn",\%conf,1);

    if( $p{'up'} ){
        if( $p{'virtual'} ){
            $conf{'ONPARENT'} = 'yes';
        } else {
        }
    }                        
    $conf{'TYPE'} = $p{'type'} if( defined $p{'type'} );
    $conf{'ONBOOT'} = $p{'up'} ? 'yes' : 'no';
    $conf{'ONPARENT'} = $p{'up'} ? 'yes' : 'no' if( $p{'virtual'} );
    $conf{'DEVICE'} = $name if( !$p{'nodevice'} );
    $conf{'IPADDR'} = $p{'address'} if( defined $p{'address'} );
    $conf{'NETMASK'} = $p{'netmask'} if( defined $p{'netmask'} );
    if( $p{'address'} && $p{'netmask'} ){
        $conf{'NETWORK'} = make_netaddr($p{'address'},$p{'netmask'});
    }
    $conf{'BROADCAST'} = $p{'broadcast'} if( defined $p{'broadcast'}  );
    $conf{'GATEWAY'} = $p{'gateway'} if( defined $p{'gateway'} );
    $conf{'MTU'} = $p{'mtu'} if( defined $p{'mtu'} );
    $conf{'BOOTPROTO'} = $p{'bootp'} ? 'bootp':
                            $p{'dhcp'} ? 'dhcp' : 'none';
    $conf{'NAME'} = $p{'desc'} if( defined $p{'desc'} );

    saveconfigfile("$net_scripts_dir/$fn",\%conf,0,1,1,1);
}

sub del_boot_interface {
    my $self = shift;
    my (%p) = @_;

    my $name = $p{'name'};
    my $fn = "ifcfg-$name";
    if( my $v = $p{'virtual'} ){
        $fn .= ":$v";
    }

    if( -e "$net_scripts_dir/$fn" ){
        unlink("$net_scripts_dir/$fn");
    }

    if( -e "$devices_dir/$fn" ){
        unlink("$devices_dir/$fn");
    }
}

sub list_routes {
    my $self = shift;

    my @list = ();
    open(R,"/bin/netstat -rn 2>/dev/null |");
    while(<R>){
        if( /^([0-9\.]+)\s+([0-9\.]+)\s+([0-9\.]+)\s+\S+\s+\S+\s+\S+\s+\S+\s+(\S+)/ ){
            push @list, { 'dest'=>$1,
                            'gateway'=>$2,
                            'netmask'=>$3,
                            'iface'=>$4,
                            'default'=> ($1 eq '0.0.0.0')? 1:0
                        }; 
        } 
    }
    close(R);

    # TODO IPv6

    return wantarray() ? @list : \@list;
}

sub ifstart {
    my $self = shift;
    my ($if) = my %p = @_;
    if( $p{'if'} ){
        $if= $p{'if'};
    }

    # up interface
    my $s = cmd_exec("/sbin/ifup",$if);
    unless( $s == 0 || $s == -1 ){
        return retErr("_ERR_IFSTART_","Error start interface '$if'");
    }
    return retOk("_OK_IFSTART_","Interface '$if' start ok.");
}
sub ifstop {
    my $self = shift;
    my ($if) = my %p = @_;
    if( $p{'if'} ){
        $if= $p{'if'};
    }

    # down interface
    my $s = cmd_exec("/sbin/ifdown",$if);
    unless( $s == 0 || $s == -1 ){
        return retErr("_ERR_IFSTOP_","Error stop interface '$if'");
    }

    return retOk("_OK_IFSTOP_","Interface '$if' stop ok.");
}
sub ifrestart {
    my $self = shift;
    my ($if) = my %p = @_;
    if( $p{'if'} ){
        $if= $p{'if'};
    }

    # down interface
    my $e = $self->ifstop( @_ );
    unless( !isError($e) ){
        return retErr("_ERR_IFRESTART_","Error stop interface '$if'");
    }

    $e = $self->ifstart( @_ );
    unless( !isError($e) ){
        return retErr("_ERR_IFRESTART_","Error start interface '$if'");
    }
    return retOk("_OK_IFRESTART_","Interface '$if' restart ok.");
}

sub del_if_toscript {
    my $self = shift;
    my ($if) = my %p = @_;
    if( $p{'if'} ){
        $if = $p{'if'};
    }
    opendir(D,"$etva_config_dir");
    my @lc = grep { /(up|down)-$if$/ } readdir(D);
    closedir(D);
    for my $f (@lc){
        my $fpath = "$etva_config_dir/$f";
        unlink($fpath);
    }
}
sub add_conf_toscript {
    my $self = shift;
    my ($if) = my %p = @_;
    if( $p{'if'} ){
        $if = $p{'if'};
    }
    if( -d "$etva_config_dir" ){
        mkpath("$etva_config_dir");
    }
    opendir(D,"$etva_config_dir");
    my @lc = readdir(D);
    closedir(D);
    my $c = 0;
    for my $f (@lc){
        if( $f =~ /(\d+)-up-/ ){
            my $m = $1;
            $c = $m if( $m > $c ); 
        }
    }
    $c++;
    my $fup = sprintf("$etva_config_dir/%.3d-up-$if",$c);

    my $mc = $c < 100 ? (100 - $c) : ($c - 100);
    my $fdown = sprintf("$etva_config_dir/%.3d-down-$if",$mc);
    
    open(FU,">$fup");
    print FU $p{'upconfig'};
    close(FU);

    open(FD,">$fdown");
    print FD $p{'downconfig'};
    close(FD);
}
sub add_if_toscript {
    my $self = shift;
    my ($if) = my %p = @_;
    if( $p{'if'} ){
        $if = $p{'if'};
    }
    
    my $upconfig = <<__UPCONFIG__;
#!/bin/sh

/sbin/ifup $if >/dev/null 2>&1
__UPCONFIG__

    my $downconfig = <<_DOWNCONFIG__;
#!/bin/sh

_DOWNCONFIG__

    if( $p{'ipaddr'} && $p{'netmask'} ){
        $upconfig .= <<__UPCONFIG__;
/sbin/ifconfig $if $p{'ipaddr'} netmask $p{'netmask'} up >/dev/null 2>&1
__UPCONFIG__
    } else {
        $upconfig .= <<__UPCONFIG__;
/sbin/ifconfig $if $p{'ipaddr'} up >/dev/null 2>&1
__UPCONFIG__
    }

    if( $p{'bridge'} ){
        $upconfig .= <<__UPCONFIG__;
/usr/sbin/brctl addif $p{'bridge'} $if >/dev/null 2>&1
__UPCONFIG__

        $downconfig .= <<_DOWNCONFIG__;
/usr/sbin/brctl delif $p{'bridge'} $if >/dev/null 2>&1
_DOWNCONFIG__
    }

    if( $p{'dhcp'} ){
        $upconfig .= <<__UPCONFIG__;
/sbin/dhclient $if >/dev/null 2>&1
__UPCONFIG__

        $downconfig .= <<_DOWNCONFIG__;
killall /sbin/dhclient >/dev/null 2>&1
_DOWNCONFIG__
    }

    if( $p{'gateway'} ){
        $upconfig .= <<__UPCONFIG__;
/sbin/route add default gw $p{'gateway'} dev $if >/dev/null 2>&1
__UPCONFIG__

        $downconfig .= <<_DOWNCONFIG__;
/sbin/route del default gw $p{'gateway'} dev $if >/dev/null 2>&1
_DOWNCONFIG__
    }

    $downconfig .= <<_DOWNCONFIG__;
/sbin/ifdown $if >/dev/null 2>&1
/sbin/ifconfig $if down >/dev/null 2>&1
_DOWNCONFIG__

    $self->add_conf_toscript( 'if'=>$if,
                            'upconfig'=>$upconfig,
                            'downconfig'=>$downconfig );
}
sub add_br_toscript {
    my $self = shift;
    my ($br) = my %p = @_;
    if( $p{'br'} ){
        $br = $p{'br'};
    }
    
    my $upconfig = <<__UPCONFIG__;
#!/bin/sh

/usr/sbin/brctl addbr $br >/dev/null 2>&1
__UPCONFIG__

    my $downconfig = <<_DOWNCONFIG__;
#!/bin/sh

_DOWNCONFIG__

    if( defined($p{'stp'}) ){
        # stp is on then on
        # stp is 1 then on
        # stp is off then off
        # stp is 0 then off
        
        my $stp = ( $p{'stp'} && ( $p{'stp'} ne 'off' ) )? "on": "off";
        $upconfig .= <<__UPCONFIG__;
/usr/sbin/brctl stp $br $stp >/dev/null 2>&1
__UPCONFIG__
    }

    if( defined($p{'fd'}) || defined($p{'delay'}) ){
        my $fd = $p{'fd'} || $p{'delay'}; 
        $upconfig .= <<__UPCONFIG__;
/usr/sbin/brctl setfd $br $fd >/dev/null 2>&1
__UPCONFIG__
    }

    if( $p{'ipaddr'} && $p{'netmask'} ){
        $upconfig .= <<__UPCONFIG__;
/sbin/ifconfig $br $p{'ipaddr'} netmask $p{'netmask'} up >/dev/null 2>&1
__UPCONFIG__
    } elsif( $p{'ipaddr'} ){
        $upconfig .= <<__UPCONFIG__;
/sbin/ifconfig $br $p{'ipaddr'} up >/dev/null 2>&1
__UPCONFIG__
    } else {
        $upconfig .= <<__UPCONFIG__;
/sbin/ifconfig $br up >/dev/null 2>&1
__UPCONFIG__
    }

    if( $p{'dhcp'} ){
        $upconfig .= <<__UPCONFIG__;
/sbin/dhclient $br >/dev/null 2>&1
__UPCONFIG__

        $downconfig .= <<_DOWNCONFIG__;
killall /sbin/dhclient >/dev/null 2>&1
_DOWNCONFIG__
    }

    if( $p{'gateway'} ){
        $upconfig .= <<__UPCONFIG__;
/sbin/route add default gw $p{'gateway'} dev $br >/dev/null 2>&1
__UPCONFIG__

        $downconfig .= <<_DOWNCONFIG__;
/sbin/route del default gw $p{'gateway'} dev $br >/dev/null 2>&1
_DOWNCONFIG__
    }

    $downconfig .= <<_DOWNCONFIG__;
/sbin/ifdown $br >/dev/null 2>&1
/sbin/ifconfig $br down >/dev/null 2>&1
/usr/sbin/brctl delbr $br >/dev/null 2>&1
_DOWNCONFIG__

    $self->add_conf_toscript( 'if'=>$br,
                            'upconfig'=>$upconfig,
                            'downconfig'=>$downconfig );
}
sub add_vlan_toscript {
    my $self = shift;
    my ($if,$id) = my %p = @_;
    if( $p{'if'} || $p{'vlanid'} ){
        $if = $p{'if'};
        $id = $p{'vlanid'};
    }
    my $vlan = $self->vlan_todevice( 'name'=>$if, 'vlanid'=>$id, 'phydevice'=>$if, 'vid'=>$id, %p );
    
    my $upconfig = <<__UPCONFIG__;
#!/bin/sh

/sbin/vconfig add $if $id >/dev/null 2>&1
__UPCONFIG__

    my $downconfig = <<_DOWNCONFIG__;
#!/bin/sh

_DOWNCONFIG__

    if( $p{'ipaddr'} && $p{'netmask'} ){
        $upconfig .= <<__UPCONFIG__;
/sbin/ifconfig $vlan $p{'ipaddr'} netmask $p{'netmask'} up >/dev/null 2>&1
__UPCONFIG__
    } elsif( $p{'ipaddr'} ){
        $upconfig .= <<__UPCONFIG__;
/sbin/ifconfig $vlan $p{'ipaddr'} up >/dev/null 2>&1
__UPCONFIG__
    } else {
        $upconfig .= <<__UPCONFIG__;
/sbin/ifconfig $vlan up >/dev/null 2>&1
__UPCONFIG__
    }

    if( $p{'bridge'} ){
        $upconfig .= <<__UPCONFIG__;
/usr/sbin/brctl addif $p{'bridge'} $vlan >/dev/null 2>&1
__UPCONFIG__

        $downconfig .= <<_DOWNCONFIG__;
/usr/sbin/brctl delif $p{'bridge'} $vlan >/dev/null 2>&1
_DOWNCONFIG__
    }

    if( $p{'dhcp'} ){
        $upconfig .= <<__UPCONFIG__;
/sbin/dhclient $vlan >/dev/null 2>&1
__UPCONFIG__

        $downconfig .= <<_DOWNCONFIG__;
killall /sbin/dhclient >/dev/null 2>&1
_DOWNCONFIG__
    }

    if( $p{'gateway'} ){
        $upconfig .= <<__UPCONFIG__;
/sbin/route add default gw $p{'gateway'} dev $vlan >/dev/null 2>&1
__UPCONFIG__

        $downconfig .= <<_DOWNCONFIG__;
/sbin/route del default gw $p{'gateway'} dev $vlan >/dev/null 2>&1
_DOWNCONFIG__
    }

    $downconfig .= <<_DOWNCONFIG__;
/sbin/ifdown $vlan >/dev/null 2>&1
/sbin/ifconfig $vlan down >/dev/null 2>&1
/sbin/vconfig rem $vlan >/dev/null 2>&1
_DOWNCONFIG__

    $self->add_conf_toscript( 'if'=>$vlan,
                            'upconfig'=>$upconfig,
                            'downconfig'=>$downconfig );
}

1;

=back

=pod

=head1 BUGS

...

=head1 AUTHORS

...

=head1 COPYRIGHT

...

=head1 LICENSE

...

=head1 SEE ALSO

L<VirtAgentInterface>, L<VirtAgent::Disk>, L<VirtAgent::Network>,
L<VirtMachine>
C<http://libvirt.org>


=cut

=pod
