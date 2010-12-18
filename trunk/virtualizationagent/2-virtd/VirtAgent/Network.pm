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

use Utils;

my %BRInfo = ();
my %NetDevices;
my %VLANDevices;

sub loadnetdev {
    my $self = shift;
    my ($force) = @_;

    if( $force || !%NetDevices ){ $self->findnetdev($force); }

    # load bridges created
    if( $force || !%BRInfo ){ $self->loadbridges($force); }

}
sub findnetdev {
    my $self = shift;

    %NetDevices = ();

    # network physical devices
    my $dir_devices = "/sys/devices";
    open(F,"/usr/bin/find $dir_devices -name 'net:eth*'|");
    while(<F>){
        chomp;
        my $netdev_dir = $_;
        my $netdev_address_file = "$netdev_dir/address";
        open(N,$netdev_address_file);
        my $netdev_address = <N>;
        chomp($netdev_address);
        close(N);
        my ($netdev_name) = ($_ =~ m/net:(eth\d+)/);
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
        if( -e "/sys/class/net/$d/brport" ){

            my $pbr = readlink("/sys/class/net/$d/brport/bridge");
            my @pbr = split(/\//,$pbr);
            
            $NetDevices{"$d"}{"bridge"} = pop @pbr;
        }
    }
    close(E);

    # default route
    my $dev = defaultroute();
    if( $dev ){
        $NetDevices{"$dev"}{'defaultroute'} = 1;
    }

    # load virtual lan
    loadvlans();

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

    loadnetdev();

    return wantarray ? %NetDevices : \%NetDevices;
}

sub vlancreate {
    my $self = shift;
    my ($iname,$lvanid) = my %p = @_;
    $iname = $p{'iname'} if( $p{'iname'} );
    $lvanid = $p{'lvanid'} if( $p{'lvanid'} );

    if( $NetDevices{$iname} ){
        my ($s,$msg) = cmd_exec("/sbin/vconfig","add",$iname,$lvanid);
        unless( $s == 0 ){
            return retErr("_ERR_VCONFIG_ADD_","Error add new virtual-lan: $msg");
        }
    } else {
        return retErr("_INVALID_NETDEVICE","Invalid network interface device");
    }

    # TODO change this
    return "ok";
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
                open(F,"$bdir/$if/bridge/bridge_id");
                my $bid = <F>;
                chomp($bid);
                $H{"id"} = $bid; 
                close(F);
                open(F,"$bdir/$if/bridge/stp_state");
                my $stp = <F>;
                chomp($stp);
                $H{"stp"} = $stp ? "yes":"no"; 
                close(F);
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

sub brcreate_prefix {
    my $self = shift;
    my ($pref) = my %p = @_;
    if( $p{'prefix'} ){
        $pref = $p{'prefix'};
    }

    # load bridges created
    $self->loadbridges();

    $pref = "virbr" if( !$pref );
    my $n = $self->brid($pref);
    $n++;

    return "${pref}${n}";
}

=item brcreate

create bridge with name or prefix

    my $BR = VirtAgent::Network->brcreate( name=>$name );

    my $BR = VirtAgent::Network->brcreate( prefix=>$prefix );

=cut

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
    my %BR = $self->loadbridges();

    # only if not alread created
    if( !$BR{"$name"} ){
        # TODO
        #   add interfaces to bridge

        unless( cmd_exec("/usr/sbin/brctl","addbr",$name) == 0 ){
            return retErr("_ERR_BRIDGE_CREATE_","Error creating bridge");
        }
        $self->loadbridges(1);
    }
    return $name;
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
