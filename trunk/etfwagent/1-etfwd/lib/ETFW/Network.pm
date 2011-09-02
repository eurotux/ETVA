#!/usr/bin/perl

=pod

=head1 NAME

ETFW::Network

=head1 SYNOPSIS

...

=head1 DESCRIPTION

...

=head1 METHODS

=over 4

=cut

package ETFW::Network;

use strict;

use ETVA::Utils;
use FileFuncs;

BEGIN {
    require Exporter;
    use vars qw($VERSION @ISA @EXPORT @EXPORT_OK %EXPORT_TAGS $CRLF $AUTOLOAD);
    @ISA = qw( Exporter );
    @EXPORT = qw( make_netaddr );
}

my $network_config = "/etc/sysconfig/network";
my $devices_dir = "/etc/sysconfig/networking/devices";
my $sysctl_config = "/etc/sysctl.conf";
my $net_scripts_dir = "/etc/sysconfig/network-scripts";

=item active_interfaces

    list all active interfaces

=cut

sub active_interfaces {
    my %ifs = all_interfaces();
    my %a_ifs = (); 
    for my $if (keys %ifs){
        $a_ifs{"$if"} = $ifs{"$if"} if( $ifs{"$if"}{"active"} );
    }
    return wantarray() ? %a_ifs : \%a_ifs;
}

=item all_interfaces

    list all interfaces

=cut

sub all_interfaces {
    open(F,"/sbin/ifconfig -a |");
    my $if;
    my %IFaces = ();
    while(<F>){
        if( /^(\S+)/ ){
            $if = $1;
        }
        if( $if ){
            if( /^([^:\s]+)/ ){
                $IFaces{"$if"}{"name"} = $1;
            }
            if( /^(\S+)/ ){
                $IFaces{"$if"}{"fullname"} = $1;
            }
            if( /^(\S+):(\d+)/ ){
                $IFaces{"$if"}{"virtual"} = $2;
            }
            if( /HWaddr (\S+)/ ){
                $IFaces{"$if"}{'macaddress'} = $1
            }
            if( /inet addr:(\S+)/ ){
                $IFaces{"$if"}{'address'} = $1;
                $IFaces{"$if"}{'active'} = 1;
            }
            if( /Mask:(\S+)/ ){
                $IFaces{"$if"}{'netmask'} = $1;
            }
            if( /Bcast:(\S+)/ ){
                $IFaces{"$if"}{'broadcast'} = $1;
            }
            if( /MTU:(\d+)/ ){
                $IFaces{"$if"}{'mtu'} = $1;
            }
            if( /P-t-P:(\S+)/ ){
                $IFaces{"$if"}{'ptp'} = $1;
            }

            if( /\sUP\s/ ){
                $IFaces{"$if"}{'up'} = 1;
            }
            $IFaces{"$if"}{'up'} ||= 0;

            if( /\sPROMISC\s/ ){
                $IFaces{"$if"}{'promisc'} = 1;
            }
            $IFaces{"$if"}{'edit'} = ($if !~ /^ppp/)? 1:0;

            $IFaces{"$if"}{'type'} = iface_type($if);

            # TODO inet6
        }
    }
    close(F);
    return wantarray() ? %IFaces : \%IFaces;
}

=item boot_interfaces

    list boot interfaces

=cut

sub boot_interfaces {
    my %ifs = ();
    opendir(NET_DIR,"$net_scripts_dir");
    for my $nf ( readdir(NET_DIR) ){
        if( $nf =~ /^ifcfg-([a-z0-9:\.]+)$/ && $nf !~ /\.(bak|old)$/i ){
            my $n = $1;
            $ifs{"$n"}{"fullname"} = $n;
            $ifs{"$n"}{"name"} = $n;
            if( $n =~ /(\S+):(\d+)/ ){
                $ifs{"$n"}{"name"} = $1;
                $ifs{"$n"}{"virtual"} = $2;
            }
            my %conf = ();
            %conf = loadconfigfile("$net_scripts_dir/$nf",\%conf,1);

            $ifs{"$n"}{"file"} = "$net_scripts_dir/$nf";

            $ifs{"$n"}{'up'} = (defined($conf{'ONPARENT'}) && $ifs{"$n"}{'virtual'}) ?
                                ($conf{'ONPARENT'} eq 'yes') :
                                ($conf{'ONBOOT'} eq 'yes');
            $ifs{"$n"}{'up'} ||= 0;
            $ifs{"$n"}{'address'} = $conf{'IPADDR'} if( $conf{'IPADDR'} );
            $ifs{"$n"}{'netmask'} = $conf{'NETMASK'} if( $conf{'NETMASK'} );
            $ifs{"$n"}{'broadcast'} = $conf{'BROADCAST'} if( $conf{'BROADCAST'} );
            $ifs{"$n"}{'gateway'} = $conf{'GATEWAY'} if( $conf{'GATEWAY'} );
            $ifs{"$n"}{'mtu'} = $conf{'MTU'} if( $conf{'MTU'} );
            $ifs{"$n"}{'dhcp'} = ($conf{'BOOTPROTO'} eq 'dhcp') ? 1:0;
            $ifs{"$n"}{'bootp'} = ($conf{'BOOTPROTO'} eq 'bootp') ? 1:0;
            $ifs{"$n"}{'edit'} = ($ifs{"$n"}{'name'} !~ /^ppp|irlan/) ? 1:0;
            $ifs{"$n"}{'desc'} = $conf{'NAME'} if( $conf{'NAME'} );

            $ifs{"$n"}{'type'} = iface_type($n);
        }
    }
    closedir(NET_DIR);

    return wantarray() ? %ifs : \%ifs;
}

=item save_boot_interface

    save boot interface

    ARGS: name - interface name

          up - up/down (1/0, 0 is default) 

          virtual - virtual id (optional)

          address - ip address

          netmask - net mask

          broadcast - broadcast address

          gateway - gateway address

          mtu -

          dhcp - dhcp on/off

          bootp - bootp on/off

          desc - description

=cut

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
    $conf{'ONBOOT'} = $p{'up'} ? 'yes' : 'no';
    $conf{'ONPARENT'} = $p{'up'} ? 'yes' : 'no' if( $p{'virtual'} );
    $conf{'DEVICE'} = $name;
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

=item save_n_apply_boot_interfaces

    save and apply boot interfaces

    ARGS: same as save_boot_interfaces

=cut

sub save_n_apply_boot_interfaces {
    my $self = shift;
    my (%p) = @_;

    $self->save_boot_interface( %p );
    $self->apply_interface( %p );

}

=item del_boot_interface

    delete boot interface

    ARGS: name - interface name
          virtual - virtual id (optional)

=cut

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

=item del_boot_interfaces

    delete one or more boot interfaces

    ARGS: interfaces - list of interfaces
              name - interface name
              virtual - virtual id (optional)

=cut

sub del_boot_interfaces {
    my $self = shift;
    my (%p) = @_;

    my $ifs = $p{'interfaces'};

    if( $ifs ){
        my @lifs = ref($ifs) eq 'ARRAY' ? @$ifs : values %$ifs;
        for my $if (@lifs){
            $self->del_boot_interface( %$if );
        }
    }
}

=item activate_interface

    activate interface

    ARGS: name - interface name

          up - up/down (1/0, 0 is default) 

          address - ip address

          netmask - net mask

          broadcast - broadcast address

          ether - mac address

          virtual - virtual id (optional)

          vlan - is vlan (1/0)

          physical - physical interface (optional)

          vlanid - vlan id (optional)

    RETURN: Ok || Error

=cut

sub activate_interface {
    my $self = shift;
    my (%p) = @_;

    my $cmd = "";
    if( $p{'vlan'} ){
        my $vconfig_cmd = "/sbin/vconfig add ".$p{'physical'}." ".$p{'vlanid'};

        my ($e,$m) = cmd_exec($vconfig_cmd);
        unless( $e == 0 ){
            return retErr("_ERR_AI_VLANADD_","Error creatae vlan: $m");
        }

        $cmd .= "/sbin/ifconfig ".$p{'physical'}.".".$p{'vlanid'};
    } else {
        $cmd .= "/sbin/ifconfig ".$p{'name'};
        if( $p{'virtual'} ){ $cmd .= ":".$p{'virtual'}; }
        # TODO IPv6
    }
    $cmd .= " ".$p{'address'};
    if( $p{'netmask'} ){ $cmd .= " netmask ".$p{'netmask'}; }
    if( $p{'broadcast'} ){ $cmd .= " broadcast ".$p{'broadcast'}; }
    if( $p{'mtu'} && !$p{'virtual'} ){ $cmd .= " mtu ".$p{'mtu'}; }
    if( $p{'up'} ){ $cmd .= " up"; }
    else{ $cmd .= " down"; }

    my ($e, $m) = cmd_exec($cmd);

    unless( $e == 0 ){
        return retErr("_ERR_AI_IFUP_","Error activate interface: $m");
    }

    if( $p{'ether'} ){
        ($e, $m) = cmd_exec("/sbin/ifconfig ".$p{'name'}." hw ether ".$p{'ether'});
        unless( $e == 0 ){
            return retErr("_ERR_AI_HWUP_","Error activate ethernet address: $m");
        }
    }

    return retOk("_OK_AI_","Interface activated successfully.");
}

=item deactivate_interface

    deactivate interface

    ARGS: name - interface name

          virtual - virtual id (optional)

    RETURN: Ok || Error

=cut

sub deactivate_interface {
    my $self = shift;
    my (%p) = @_;

    my $name = $p{'name'};
    $name .= $p{'virtual'} if( $p{'virtual'} );

    if( $p{'virtual'} ){
        cmd_exec("/sbin/ifconfig $name 0");
    }
    # TODO IPv6
    
    my %aifs = active_interfaces();
    my ($still) = grep { $_->{'fullname'} eq $name } values %aifs;
    if( $still ){
        cmd_exec("/sbin/ifconfig $name down");

        # virtual interface type
        if( iface_type($name) =~ / VLAN$/ ){
            cmd_exec("vconfig rem $name");
        }
        %aifs = active_interfaces();
        ($still) = grep { $_->{'fullname'} eq $name } values %aifs;
        if( $still ){
            return retErr("_ERR_DEAIF_STILL_","Error deactivate interface.");
            
        }
    } else {
        return retErr("_ERR_DEAIF_NOIF_","Interface is not active.");
    }
    return retOk("_OK_DEAIF_","Interface deactivated successfully.");
}

=item deactivate_interfaces

    deactivate one or more interfaces

    ARGS: interfaces - list of interfaces
              name - interface name
              virtual - virtual id (optional)

=cut

sub deactivate_interfaces {
    my $self = shift;
    my (%p) = @_;

    my $ifs = $p{'interfaces'};

    if( $ifs ){
        my @lifs = ref($ifs) eq 'ARRAY' ? @$ifs : values %$ifs;
        for my $if (@lifs){
            $self->deactivate_interface( %$if );
        }
    }
}

=item apply_interface

    apply interface

=cut

sub apply_interface {
    my $self = shift;
    my (%p) = @_;

    my $dev = $p{'name'} || $p{'dev'};
    cmd_exec("cd / ; ifdown '$dev' >/dev/null 2>&1 </dev/null ; ifup '$dev' 2>&1 </dev/null");
}

# iface_type(name)
# Returns a human-readable interface type name
sub iface_type {
    my ($name) = @_;
    if ($name =~ /^(.*)\.(\d+)$/) {
        return iface_type("$1") . " VLAN";
    }
    if ($name =~ /^(.*):(\d+)$/) {
        return iface_type("$1") . " (Virtual)";
    }
    return "PPP" if ($name =~ /^ppp/);
    return "SLIP" if ($name =~ /^sl/);
    return "PLIP" if ($name =~ /^plip/);
    return "Ethernet" if ($name =~ /^eth/);
    return "Wireless Ethernet" if ($name =~ /^(wlan|ath)/);
    return "Arcnet" if ($name =~ /^arc/);
    return "Token Ring" if ($name =~ /^tr/);
    return "Pocket/ATP" if ($name =~ /^atp/);
    return "Loopback" if ($name =~ /^lo/);
    return "ISDN rawIP" if ($name =~ /^isdn/);
    return "ISDN syncPPP" if ($name =~ /^ippp/);
    return "CIPE" if ($name =~ /^cip/);
    return "VmWare" if ($name =~ /^vmnet/);
    return "Wireless" if ($name =~ /^wlan/);
    return "Bonded" if ($name =~ /^bond/);
    return "Unknown";
}

=item list_routes

=cut

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

=item default_route

    return default route

=cut

sub default_route {
    my $self = shift;

    if( my ($DR) = grep { $_->{'default'} || ( $_->{'dest'} eq '0.0.0.0' ) } $self->list_routes() ){
        return wantarray() ? %$DR : $DR;
    }
    return;
}

=item default_interface

    return default interface

=cut

sub default_interface {
    my $self = shift;

    if( my $DR = $self->default_route() ){
        my $if = $DR->{"iface"};
        my %IFs = $self->all_interfaces();
        if( my $IF = $IFs{"$if"} ){
            return wantarray() ? %$IF : $IF;
        }
    }
    return;
}

=item create_route

    ARGS: dest - destination
          netmask - network mask
          gateway - gateway
          iface - network interface

=cut

sub create_route {
    my $self = shift;
    my (%p) = @_;

    my $cmd = "/sbin/route ";
    # TODO IPv6
    $cmd .= "-A inet ";
    $cmd .= " add ";
    if( !$p{'dest'} || $p{'dest'} eq '0.0.0.0' ){
        $cmd .= " default";
    } elsif( $p{'netmask'} eq '255.255.255.255' ){
        $cmd .= "-host ".$p{'dest'};
    } else { 
        $cmd .= " -net ".$p{'dest'};
        if( $p{'netmask'} && $p{'netmask'} ne '0.0.0.0' ){
            $cmd .=  " netmask ".$p{'netmask'};
        }
    }

    if( $p{'gateway'} ){
        $cmd .= " gw ".$p{'gateway'};
    } elsif( $p{'iface'} ){
        $cmd .= " dev ".$p{'iface'};
    }

    my ($e,$m) = cmd_exec($cmd);
    unless( $e == 0 ){
        return retErr("_ERR_CROUTE_","Cant create route: $m");
    }
    return retOk("_OK_CROUTE_","Route created successfully.");
}

=item delete_route

    ARGS: dest - destination
          netmask - network mask
          gateway - gateway
          iface - network interface

=cut

sub delete_route {
    my $self = shift;
    my (%p) = @_;

    my $cmd = "/sbin/route ";
    # TODO IPv6
    $cmd .= "-A inet ";
    $cmd .= "del ";
    
    if( !$p{'dest'} || $p{'dest'} eq '0.0.0.0' ){
        $cmd .= " default";
    } elsif( $p{'netmask'} eq '255.255.255.255' ){
        $cmd .= "-host ".$p{'dest'};
    } else { 
        $cmd .= " -net ".$p{'dest'};
        if( $p{'netmask'} && $p{'netmask'} ne '0.0.0.0' ){
            $cmd .=  " netmask ".$p{'netmask'};
        }
    }

    if( $p{'gateway'} ){
        $cmd .= " gw ".$p{'gateway'};
    } elsif( $p{'iface'} ){
        $cmd .= " dev ".$p{'iface'};
    }

    my ($e,$m) = cmd_exec($cmd);
    unless( $e == 0 ){
        return retErr("_ERR_DROUTE_","Cant delete route: $m");
    }
    return retOk("_OK_DROUTE_","Route deleted successfully.");
}

=item delete_routes

    ARGS: routes - list of route to delete
              dest - destination
              netmask - network mask
              gateway - gateway
              iface - network interface

=cut

sub delete_routes {
    my $self = shift;
    my (%p) = @_;

    my $lr = $p{'routes'};

    if( $lr ){
        my @lroutes = ref($lr) eq 'ARRAY' ? @$lr : values %$lr;
        for my $r (@lroutes){
            $self->delete_route( %$r );
        }
    }
}

=item load_dns

=cut

sub load_dns {
    my $self = shift;

    my %dns = ( "nameserver"=>[], "domain"=>[], "order"=>[] );
    open(R,"/etc/resolv.conf");
    while(<R>){
        if( /nameserver\s+(.*)/ ){
            push(@{$dns{"nameserver"}}, split(/\s+/,$1));
        } elsif( /domain\s+(\S+)/ ){
            $dns{"domain"} = [ $1 ];
        } elsif( /search\s+(.*)/ ){
            $dns{"domain"} = [ split(/\s+/,$1) ];
        }
    }
    close(R);
    open(N,"/etc/nsswitch.conf");
    while(<N>){
        s/\r|\n//g;
        if( /^\s*hosts:\s+(.*)/ ){
            push(@{$dns{"order"}}, split(/\s+/,$1));
        }
    }
    close(N);

    return wantarray() ? %dns : \%dns;
}

=item save_dns
    save DNS config

    nameserver - name server
    domain - domain name
    order - resolution order

=cut

sub save_dns {
    my $self = shift;
    my (%p) = @_;

    open(B,"/etc/resolv.conf");
    my @bkpdns=<F>;
    close(B);

    open(S,">/etc/resolv.conf");
    my @ns = ref($p{"nameserver"}) ? @{$p{"nameserver"}} : ($p{"nameserver"});
    for my $n (@ns){
        print S "nameserver $n\n";
    }
    my $ld = $p{"domain"};
    my @ds = ref($ld) ? @$ld : ($ld);
    if( scalar(@ds) > 1 ){
        print S "search ",join(" ",@ds),"\n";
    } else {
        print S "domain $ds[0]";
    }

    for my $l (@bkpdns){
        print S $l if( $l !~ /^\s*(nameserver|domain|search)\s+/ );
    }
    close(S);

    # Update resolution order in nsswitch.conf
    open(SWITCH, "/etc/nsswitch.conf");
    my @switch = <SWITCH>;
    close(SWITCH);
    open(SWITCH, ">/etc/nsswitch.conf");

    my $lo = $p{"order"};
    my @do = ref($lo) ? @$lo : ($lo);
    my $order = join(" ",@do);
    foreach (@switch) {
        if (/^\s*hosts:\s+/) {
                print SWITCH "hosts:\t$order\n";
        } else {
                print SWITCH $_;
        }
    }
    close(SWITCH);
}

=item get_hostname

    get hostname

=cut

sub get_hostname {
    my $self = shift;

    my %h = ();

    my %nc = ();
    read_env_file("$network_config",\%nc);

    if( not $h{"hostname"} = $nc{"HOSTNAME"} ){
        my $cfref = read_file_lines("/etc/HOSTNAME");
        $h{"hostname"} = $cfref->[0];
    }
    
    return wantarray() ? %h : \%h;
}

=item set_hostname

    change hostname

    ARGS: hostname

=cut

sub set_hostname {
    my $self = shift;
    my (%p) = @_;

    my $hostname = $p{"hostname"};

    my $cfref = read_file_lines("/etc/HOSTNAME");
    $cfref->[0] = $hostname;
    flush_file_lines("/etc/HOSTNAME");

    my $cfref2 = read_file_lines("$network_config");
    grep { $_ =~ s/^\s*HOSTNAME\s*=\s*(.*)/HOSTNAME=$hostname/ } @$cfref2;

}

=item get_hostname_dns

    get hostname and DNS info

=cut

sub get_hostname_dns {
    my $self = shift;

    my %dns = $self->load_dns();

    my %hn = $self->get_hostname();
    
    my %res = (%dns,%hn);

    return wantarray() ? %res : \%res;
}

=item set_hostname_dns

    set hostname and DNS config

    ARGS: hostname
        nameserver - name server
        domain - domain name
        order - resolution order

=cut

sub set_hostname_dns {
    my $self = shift;
    my (%p) = @_;

    $self->save_dns(%p);

    $self->set_hostname(%p);
}

=item list_hosts

=cut

sub list_hosts {
    my $self = shift;
    my @lhosts = ();
    open(H,"/etc/hosts");
    my $i = 0;
    while(<H>){
        chomp;
        s/#.*$//g;
        s/\s+$//g;

        my @h = split(/\s+/,$_);
        if( my $ipaddr = shift(@h) ){
            push(@lhosts, { 'address'=>$ipaddr, 'hosts'=>\@h, 'index'=>$i });
            $i++;
        }
        
    }
    close(H);
    return wantarray() ? @lhosts: \@lhosts;
}

=item create_host

    ARGS: address - host IP address
          hosts - host names

=cut

sub create_host {
    my $self = shift;
    my (%p) = @_;
    open(H,">>/etc/hosts");
    my @hs = ref($p{'hosts'}) ? @{$p{'hosts'}} : ($p{'hosts'});
    print H $p{"address"},"\t",join(" ",@hs),"\n";
    close(H);
}

=item delete_host

    ARGS: address - host IP address
          index - host index

=cut

sub delete_host {
    my $self = shift;
    my (%p) = @_;
    my $addr = $p{"address"};
    if( !$addr && defined $p{'index'} ){
        my $i = $p{'index'};
        my @lh = $self->list_hosts();
        $addr = $lh[$i]->{"address"};
    }
    if( $addr ){
        open(B,"/etc/hosts");
        my @bkp=<B>;
        close(B);
        open(H,">/etc/hosts");
        for my $l (@bkp){
            if( $l !~ m/^\s*$addr/ ){
                print H $l;
            }
        }
        close(H);
    }
}

=item modify_host

    ARGS: address - host IP address
          hosts - host names
          oldaddress - old address to modify
          index - host index

=cut

sub modify_host {
    my $self = shift;
    my (%p) = @_;
    my $addr = $p{"oldaddress"};
    if( !$addr && defined $p{'index'} ){
        my $i = $p{'index'};
        my @lh = $self->list_hosts();
        $addr = $lh[$i]->{"address"};
    }
    if( $addr ){
        open(B,"/etc/hosts");
        my @bkp=<B>;
        close(B);
        open(H,">/etc/hosts");
        for my $l (@bkp){
            if( $l =~ m/^\s*$addr/ ){
                my @hs = ref($p{'hosts'}) ? @{$p{'hosts'}} : ($p{'hosts'});
                print H $p{"address"},"\t",join(" ",@hs),"\n";
            } else {
                print H $l;
            }
        }
        close(H);
    }
}

=item get_boot_routing

    get routing boot time configuration

=cut

sub get_boot_routing {
    my $self = shift;

    my %conf = ();
    read_env_file($network_config,\%conf);

    # Default routes
    my %ifs = $self->boot_interfaces();
    my @lroutes = ();

    if( $conf{"GATEWAY"} ){
        push(@lroutes, { dev=>"any", gateway=>$conf{"GATEWAY"} });
    }

    for my $if (keys %ifs){
        if( my $gw = $ifs{"$if"}{"gateway"} ){
            push(@lroutes,{ dev=>$if, gateway=>$gw });
        }
    }

    # Act as router ?
    my %sysctl;
    read_env_file($sysctl_config,\%sysctl);
    my $act_router = $sysctl{'net.ipv4.ip_forward'} ? 1 : 0;

    # Static routes
    #  and Local routes

    # get static routes from per-interface files
    my $f;
    my @st;
    my @hr;
    opendir(DIR, $devices_dir);
    while($f = readdir(DIR)) {
        if ($f =~ /^([a-z]+\d*(\.\d+)?(:\d+)?)\.route$/ ||
            $f =~ /^route\-([a-z]+\d*(\.\d+)?(:\d+)?)$/) {
            my $dev = $1;
            my (%rfile, $i);
            read_env_file("$devices_dir/$f", \%rfile);
            for( $i=0; defined($rfile{"ADDRESS$i"}); $i++ ){
                if( $rfile{"GATEWAY$i"} =~ /\d+.\d+.\d+.\d+/ ){
                    push(@st, { device=>$dev, address=>$rfile{"ADDRESS$i"},
                                netmask=>$rfile{"NETMASK$i"},
                                gateway=>$rfile{"GATEWAY$i"},
                                'index'=>$i });
                } else {
                    push(@hr, { device=>$dev, address=>$rfile{"ADDRESS$i"},
                                netmask=>$rfile{"NETMASK$i"} || "255.255.255.255",
                                'index'=>$i });
                }
            }
        }
    }
    closedir(DIR);

    my %conf = ( DefaultRoutes=>\@lroutes, IsRouter=>$act_router, StaticRoutes=>\@st, LocalRoutes=>\@hr );

    return wantarray() ? %conf : \%conf;
}

sub set_ip_forward {
    my $self = shift;
    my ($v) = @_;
    my $cl = read_file_lines($sysctl_config);
    if( ! grep { s/net.ipv4.ip_forward\s*=.*$/net.ipv4.ip_forward=$v/ } @$cl ){
        push(@$cl, "net.ipv4.ip_forward=$v");
    }
    flush_file_lines($sysctl_config);
}

=item setIsRouter

    set as router

=cut

sub setIsRouter {
    my $self = shift;
    $self->set_ip_forward(1);
}

=item setIsNotRouter

    set not as router

=cut

sub setIsNotRouter {
    my $self = shift;
    $self->set_ip_forward(0);
}

=item add_boot_routing

    DefaultRoutes - default routes
        * dev - device
        * gateway - gateway

    StaticRoutes - static routes
        * dev - device
        * address - network address
        * netmask - network mask
        * gateway - network gateway

    LocalRoutes - local routes
        * dev - device
        * address - network address
        * netmask - network mask

=cut

sub add_boot_routing {
    my $self = shift;
    my (%p) = @_;

    if( my $ld = $p{"DefaultRoutes"} ){
        my @l = ( ref($ld) eq 'ARRAY' ) ? @$ld : ($ld);

        my %conf = ();
        read_env_file($network_config,\%conf);

        for my $L (@l){
            my $dev = $L->{"dev"};
            my $gw = $L->{"gateway"};
            if( $dev eq "any" || $dev eq "*" ){
                $conf{"GATEWAY"} = $gw;
            } else {
                my $lc = read_file_lines("$net_scripts_dir/ifcfg-$dev");
                if( ! grep { s/GATEWAY.*=.*/GATEWAY=$gw/ } @$lc ){
                    push(@$lc, "GATEWAY=$gw");
                }
            }
        }
        flush_file_lines();
        write_env_file($network_config,\%conf);
    } 

    my @lr = ();
    if( my $sr = $p{"StaticRoutes"} ){
        push(@lr, ( ref($sr) eq 'ARRAY' ) ? @$sr : ($sr) );
    }
    if( my $lr = $p{"LocalRoutes"} ){
        push(@lr, ( ref($lr) eq 'ARRAY' ) ? @$lr : ($lr) );
    }

    if( @lr ){
        for my $L (@lr){
            my $dev = $L->{"device"};
            my $addr = $L->{"address"} || "0.0.0.0";

            next if( !$dev );

            my $file = "$devices_dir/$dev.route";
            $file = "$devices_dir/route-$dev" if( ! -e "$file" );
            my $lc = read_file_lines($file);
            my $max = 0;
            for (@$lc){
                if( /ADDRESS(\d+)/ ){
                    my $i = $1;
                    if( $i >= $max ){
                        $max = $i + 1;
                    }
                } 
            }
            my $i = $max;
            my $nm = $L->{"netmask"} || "0.0.0.0";

            push(@$lc, "ADDRESS$i=$addr");
            push(@$lc, "NETMASK$i=$nm");
            if( $L->{'gateway'} ){
                push(@$lc, "GATEWAY$i=$L->{gateway}");
            } else {
                push(@$lc, "GATEWAY$i=\"\"");
            }
        }
        flush_file_lines();
    }

}

=item del_boot_routing

    DefaultRoutes - default routes
        * dev - device

    StaticRoutes - static routes
        * dev - device
        * address - network address
        * index - or index

    LocalRoutes - local routes
        * dev - device
        * address - network address
        * index - or index

=cut

sub del_boot_routing {
    my $self = shift;
    my (%p) = @_;

    if( my $ld = $p{"DefaultRoutes"} ){
        my @l = ( ref($ld) eq 'ARRAY' ) ? @$ld : ($ld);

        my %conf = ();
        read_env_file($network_config,\%conf);
        for my $L (@l){
            my $dev = $L->{"dev"};
            if( $dev eq "any" || $dev eq "*" ){
                delete $conf{"GATEWAY"};
            } else {
                my $lc = read_file_lines("$net_scripts_dir/ifcfg-$dev");
                grep { s/GATEWAY.*=.*// } @$lc;
            }
        }
        flush_file_lines();
        write_env_file($network_config,\%conf);
    } 

    my @lr = ();
    if( my $sr = $p{"StaticRoutes"} ){
        push(@lr, ( ref($sr) eq 'ARRAY' ) ? @$sr : ($sr) );
    }
    if( my $lr = $p{"LocalRoutes"} ){
        push(@lr, ( ref($lr) eq 'ARRAY' ) ? @$lr : ($lr) );
    }

    if( @lr ){
        for my $L (@lr){
            my $dev = $L->{"device"};

            next if( !$dev );

            my $file = "$devices_dir/$dev.route";
            $file = "$devices_dir/route-$dev" if( ! -e "$file" );
            if( !$L->{"index"} && !$L->{"address"} ){
                # delete all rules
                unlink($file);
            } else {
                my $lc = read_file_lines($file);
                my $i = $L->{"index"};
                if( ! defined $i ){ 
                    my $addr = $L->{"address"};
                    for (@$lc){
                        if( $addr && /ADDRESS(\d+)\s*=\s*$addr/ ){
                            $i = $1;
                            last;
                        } 
                    }
                }
                if( $i ){
                    my @nlc = grep { !/ADDRESS$i/ ||
                                     !/NETMASK$i/ ||
                                     !/GATEWAY$i/
                                        } @$lc;
                    $lc = \@nlc;
                }
            }
        }
        flush_file_lines();
    }
}

=item set_boot_routing

    DefaultRoutes - default routes
        * dev - device
        * gateway - gateway

    IsRouter - is router (1:0)

    StaticRoutes - static routes
        * dev - device
        * address - network address
        * netmask - network mask
        * gateway - network gateway
        * index - or index

    LocalRoutes - local routes
        * dev - device
        * address - network address
        * netmask - network mask
        * index - or index

=cut

sub set_boot_routing {
    my $self = shift;
    my (%p) = @_;

    # Default routes
    my %conf = ();
    read_env_file($network_config,\%conf);
    delete $conf{"GATEWAY"};    # clean default gateway

    my %ifs = $self->boot_interfaces();
    for my $if (keys %ifs){
        my $gw = '""';  # clean all gateway
        my $lc = read_file_lines("$net_scripts_dir/ifcfg-$if");
        if( ! grep { s/GATEWAY.*=.*/GATEWAY=$gw/ } @$lc ){
            push(@$lc, "GATEWAY=$gw");
        }
    }
    if( my $ld = $p{"DefaultRoutes"} ){
        my @l = ( ref($ld) eq 'ARRAY' ) ? @$ld : ($ld);

        for my $L (@l){
            my $if = $L->{"dev"};
            if( $if eq "any" || $if eq "*" ){
                if( $L->{"gateway"} ){
                    $conf{"GATEWAY"} = $L->{"gateway"};
                }
            } else {
                my $gw = $ifs{"$if"}{"gateway"} = $L->{"gateway"} || '""';
                my $lc = read_file_lines("$net_scripts_dir/ifcfg-$if");
                if( ! grep { s/GATEWAY.*=.*/GATEWAY=$gw/ } @$lc ){
                    push(@$lc, "GATEWAY=$gw");
                }
            }
        }
    } 

    # set as router
    $self->set_ip_forward( $p{"IsRouter"} ? 1 : 0 );

    # Static routes
    #  and Local routes

    # delete all static routes from per-interface files
    my $f;
    opendir(DIR, $devices_dir);
    while($f = readdir(DIR)) {
        if ($f =~ /^([a-z]+\d*(\.\d+)?(:\d+)?)\.route$/ ||
            $f =~ /^route\-([a-z]+\d*(\.\d+)?(:\d+)?)$/) {
            my $file = "$devices_dir/$f";
            my $lc = read_file_lines($file);
            splice(@$lc,0,scalar(@$lc));   # clean all routes
        }
    }
    closedir(DIR);

    my @lr = ();
    if( my $sr = $p{"StaticRoutes"} ){
        push(@lr, ( ref($sr) eq 'ARRAY' ) ? @$sr : ($sr) );
    }
    if( my $lr = $p{"LocalRoutes"} ){
        push(@lr, ( ref($lr) eq 'ARRAY' ) ? @$lr : ($lr) );
    }

    if( @lr ){
        for my $L (@lr){
            my $dev = $L->{"device"};
            my $addr = $L->{"address"} || "0.0.0.0";

            next if( !$dev );

            my $file = "$devices_dir/$dev.route";
            $file = "$devices_dir/route-$dev" if( ! -e "$file" );
            my $lc = read_file_lines($file);

            my $i = $L->{"index"};
            if( !defined $i ){
                my $max = 0;
                for (@$lc){
                    if( /ADDRESS(\d+)/ ){
                        my $c = $1;
                        if( $c >= $max ){
                            $max = $c + 1;
                        }
                    } 
                }
                $i = $max;
            }

            my $gw = $L->{'gateway'} || '""';
            my $nm = $L->{'netmask'} || '0.0.0.0';

            push(@$lc, "ADDRESS$i=$addr");
            push(@$lc, "NETMASK$i=$nm");
            push(@$lc, "GATEWAY$i=$gw");
        }
    }
    flush_file_lines();
    write_env_file($network_config,\%conf);
}

=item apply_config

=cut

sub apply_config {
    my $self = shift;

    if( -x "/etc/init.d/network" ){
        cmd_exec("/etc/init.d/network stop");
        cmd_exec("/etc/init.d/network start");
    }
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


=cut

