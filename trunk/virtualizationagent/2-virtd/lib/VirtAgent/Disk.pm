#!/usr/bin/perl

=pod

=head1 NAME

VirtAgent::Disk - ...

=head1 SYNOPSIS

...

=head1 DESCRIPTION

...

=head1 METHODS

=over 4

=cut

package VirtAgent::Disk;

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

use ETVA::Utils;

use Filesys::DiskFree;
use File::PathConvert qw(realpath);
use Data::Dumper;

my $CONF;

use constant MINOR_MASK     => 037774000377;
use constant MINOR_SHIFT    => 0000000;
use constant MAJOR_MASK     => 03777400;
use constant MAJOR_SHIFT    => 0000010;

my $MIN_SIZE_DISK = 1024 * 1024;    # min disk size 1M

my $LIMIT_SIZE_DISK_PERC = 0.999;    # limit size disk percentage of disk free

# Disk Devices
#   name => { info }
my %DiskDevices = ();
# Physical Disks
#   name => { info }
my %PhyDisk = ();
# Physical Devices
#   name => { info }
my %PhyDevices = ();
# Physical Volumes
#   pvname => { info }
my %PVInfo = ();
# Volume Groups
#   vg => { info }
my %VGInfo = ();
# Logical Volumes
#   lv => { info }
my %LVInfo = ();
# Multipath maps
#   uuid => { info }
my %PathMaps = ();
# Mounted Devices
#   device => { info }
my %MountDev = ();

# All Disk Devices info
my %AllDiskDevices = ();

# Multipath support flag
my $HAVEMULTIPATH;

=item ...

=cut

# getdiskdev
#   get disk device info
#
#   args: empty
#   res: Hash { name => { info } }
sub getdiskdev {
    my $self = shift;
    
    $self->loaddiskdev();

    return wantarray() ? %DiskDevices : \%DiskDevices;
}

=item getphydisk

get physical disk devices info

    my %D = VirtAgent::Disk->getphydisk( );

    return: %D = ( 'local'=>{ 'sda'=>{ ... }, ... }, 'SAN'=>{ ... } )

=cut

# getphydisk
#   get physical disks info
#
#   args: empty
#   res: Hash { type => { name => { info } } }
sub getphydisk {
    my $self = shift;
    
    $self->loaddiskdev();

    # groupping by type
    my %TPD = ();
    for my $pd (keys %PhyDisk){
        my $type = $PhyDisk{$pd}{'type'} || "unknown";
        $TPD{$type}{$pd} = $PhyDisk{$pd};
    }
    return wantarray() ? %TPD : \%TPD;
}
# hash_phydisks
#   get physical disks info
#
#   args: empty
#   res: Hash { name => { info } }
sub hash_phydisks {
    my $self = shift;
    my (%p) = @_;
    
    my $force = $p{'force'} ? 1 : 0;
    $self->loaddiskdev($force);

    return wantarray() ? %PhyDisk : \%PhyDisk;
}

sub hash_phydevices {
    my $self = shift;
    my (%p) = @_;
    
    my $force = $p{'force'} ? 1 : 0;
    $self->loaddiskdev($force);

    return wantarray() ? %PhyDevices : \%PhyDevices;
}

=item getpvs

get physical volumes

=cut

# getpvs
#   get physical volumes info
#
#   args: empty
#   res: Hash { name => { info } }
sub getpvs {
    my $self = shift;
    my (%p) = @_;
    
    my $force = $p{'force'} ? 1 : 0;
    $self->loaddiskdev($force);

    return wantarray() ? %PVInfo : \%PVInfo;
}

sub getphydev {
    my $self = shift;
    my (%p) = @_;
    my $PD;
    if( my $uuid = $p{'uuid'} ){
        ($PD,my @more) = grep { ( $_->{'uuid'} eq "$uuid" ) } values %PhyDevices;
        if( @more ){
            ($PD) = grep { ( $_->{'uuid'} eq "$uuid" ) } values %PhyDisk;
        }
    } elsif( my $dev = $p{'device'} ){
        ($PD) = grep { ( $_->{'device'} eq "$dev" ) || ( $_->{'aliasdevice'} eq "$dev" ) } values %PhyDevices;
    } elsif( my $lf = $p{'loopfile'} ){
        ($PD) = grep { $_->{'loopfile'} eq "$lf" } values %PhyDevices;
    } elsif( my $name = $p{'name'} ){
        $PD = $PhyDevices{"$name"};
    }
    return $PD;
}
sub getpv {
    my $self = shift;
    my (%p) = @_;
    my $PV;
    if( my $uuid = $p{'pv_uuid'} || $p{'uuid'} ){
        ($PV) = grep { ( $_->{'pv_uuid'} eq "$uuid" ) || ( $_->{'uuid'} eq "$uuid" ) } values %PVInfo;
    } elsif( my $dev = $p{'device'} ){
        ($PV) = grep { ( $_->{'device'} eq "$dev" ) || ( $_->{'aliasdevice'} eq "$dev" ) } values %PVInfo;
    } elsif( my $name = $p{'name'} ){
        $PV = $PVInfo{"$name"};
    }
    return $PV;
}
sub getvg {
    my $self = shift;
    my (%p) = @_;
    my $VG;
    if( my $uuid = $p{'vg_uuid'} || $p{'uuid'} ){
        ($VG) = grep { ( $_->{'vg_uuid'} eq "$uuid" ) || ( $_->{'uuid'} eq "$uuid" ) } values %VGInfo;
    } elsif( my $name = $p{'name'} ){
        $VG = $VGInfo{"$name"};
    }
    return $VG;
}
sub getlv {
    my $self = shift;
    my (%p) = @_;
    my $LV;
    if( my $uuid = $p{'lv_uuid'} || $p{'uuid'} ){
        ($LV) = grep { ( $_->{'lv_uuid'} eq "$uuid" ) || ( $_->{'uuid'} eq "$uuid" ) } values %LVInfo;
    } elsif( my $dev = $p{'device'} ){
        ($LV) = grep { ( $_->{'device'} eq "$dev" ) || ( $_->{'aliasdevice'} eq "$dev" ) } values %LVInfo;
    } elsif( my $name = $p{'name'} ){
        $LV = $LVInfo{"$name"};
    }
    return $LV;
}

=item getvgs

get volume groups

=cut

# getvgs
#   get volume groups info
#
#   args: empty
#   res: Hash { name => { info } }
sub getvgs {
    my $self = shift;
    my (%p) = @_;
    
    my $force = $p{'force'} ? 1 : 0;
    $self->loaddiskdev($force);

    return wantarray() ? %VGInfo : \%VGInfo;
}

=item getlvs

get logical volumes

=cut

# getlvs
#   get logical volumes info
#
#   args: empty
#   res: Hash { name => { info } }
sub getlvs {
    my $self = shift;
    my (%p) = @_;
    
    my $force = $p{'force'} ? 1 : 0;
    $self->loaddiskdev($force);

    return wantarray() ? %LVInfo : \%LVInfo;
}

# load disk device
#   function to initialize disk device info
sub loaddiskdev {
    my $self = shift;
    my ($force) = @_;

    $CONF = ETVA::Utils::get_conf();
    if( $CONF->{'MIN_SIZE_DISK'} ){
        $MIN_SIZE_DISK = str2size($CONF->{'MIN_SIZE_DISK'});
    }
    if( $CONF->{'LIMIT_SIZE_DISK_PERC'} ){
        $LIMIT_SIZE_DISK_PERC = $CONF->{'LIMIT_SIZE_DISK_PERC'};
    }

    # get physical devices
    if( $force || !%PhyDevices ){ phydev(); }

    # get physical volumes 
    if( $force || !%PVInfo ){ pvinfo(); }

    # get volume groups
    if( $force || !%VGInfo ){ vginfo(); }

    # get logical volumes 
    if( $force || !%LVInfo ){
        lvinfo();
        # activate inactive logical volumes
        activate_lvs();
        # update LV info
        lvinfo();
    }

    # multipath map info
    if( $force || !%PathMaps ){ pathmapsinfo(); }

    if( $force || !%MountDev ){ mountdev(); }

    %PhyDisk = () if( $force );
    %DiskDevices = () if( $force );

    # update devices with aditional info
    if( $force || !%DiskDevices ){ update_devices(); }

    my %res = ( devices => \%PhyDevices, PV => \%PVInfo, VG => \%VGInfo, LV => \%LVInfo, MultiPath => \%PathMaps );
    return wantarray ? %res : \%res;
}

# Physical devices
sub phydev {

    %PhyDevices = ();

    # get it from /prc/partitions
    open(F,"/proc/partitions");
    my $fstl = <F>;             # first line
    my @topf = split(/\s+/,$fstl);
    my $devnull = <F>;          # drop next line
    while(<F>){
        chomp;
        my $devline = trim($_);
        my @devf = split(/\s+/,$devline);
        my %PhyDev = ();
        for(my $i=0; $i<scalar(@topf); $i++){
            my $f = tokey($topf[$i]);
            my $v = $devf[$i];
            $PhyDev{"$f"} = $v;
        }
        my $name = $PhyDev{"dname"} = $PhyDev{"name"};
        my $device = "/dev/$name";
        if( -e "$device" ){
            $PhyDev{"device"} = $device;
        }
        $PhyDev{"size"} = $PhyDev{"blocks"} * 1024; # in bytes

        # loop device
        if( $name =~ m/loop/ ){

            # mark as loop device
            $PhyDev{"loopdevice"} = 1;

            open(L,"/sbin/losetup $device |");
            while(<L>){
                if( /\S+\s+\S+\s+\((\S+)\)/ ){
                    $PhyDev{"loopfile"} = $1;
                    last;
                }
            }
            close(L);
        }

        my $blockdev = "/block/$name";
        # get blockid (uuid)
        open(S,"/sbin/scsi_id -p 0x83 -g -s $blockdev |"); 
        my $uuid = <S>;    # read one single line
        chomp($uuid);
        close(S);

        # only if have universal uniq id
        if( $uuid ){
            $PhyDev{"uuid"} = $uuid if( $uuid);
        }

        for my $k (qw(size freesize)){
            # pretty string for size field
            $PhyDev{"pretty_${k}"} = prettysize($PhyDev{"$k"});
        }
        $PhyDevices{"$name"} = \%PhyDev;
    }
    
    close(F);

    # get info from libparted
    libparted_phydevinfo();
    
    return wantarray() ? %PhyDevices: \%PhyDevices;
}

sub libparted_phydevinfo {

    # libparted required
    require parted;

    if( -e "/proc/partitions" &&
        %PhyDevices ){
        # probe all device from /proc/partitions
        for my $dn (keys %PhyDevices){
            my $D = $PhyDevices{"$dn"};
            if( $D->{'device'} ){
                # get device
                my $dev = parted::device_get( $D->{'device'} );
                libparted_updatedevinfo( $dev );
            }
        }
    } else {
        # probe all devices
        parted::device_probe_all();

        # get first device
        my $dev = parted::device_get_first();

        # update %PhyDevices with more info
        libparted_updatedevinfo( $dev );

        # and for others devices
        while( $dev = $dev->get_next() ){
            libparted_updatedevinfo( $dev );
        }
    }
}
sub libparted_updatedevinfo {
    my ($dev) = @_;
    if( $dev ){
        if( $dev->open() ){
            my $path = $dev->{'path'};
            my @ad = split(/\//,$path);
            my $ndev = pop(@ad);    # device name

            if( my $disktype = $dev->disk_probe() ){
                if( my $disk = $dev->disk_new() ){
                    # last partition index
                    my $lpi = $disk->get_last_partition_num();
                    
                    # get all partitions
                    for( my $i=1; $i<=$lpi; $i++ ){
                        # get i-partition
                        my $part = $disk->get_partition($i);
                        if( $part ){    # is valid one
                            my $pndev = "$ndev" . "$i";
                            if( $PhyDevices{$pndev} ){

                                # file system type for this partition
                                my $fs_type = "";
                                if( $part->{'fs_type'} ){
                                    $fs_type = $part->{'fs_type'}->{'name'};
                                }
                                $PhyDevices{$pndev}{'fs_type'} = $fs_type if( $fs_type );

                                # get partition type: logical, extended, primary, etc...
                                my $type = parted::partition_type_get_name($part->{'type'});
                                $type = "unknown" if( !$type ); # other unknown
                                $PhyDevices{$pndev}{'dtype'} = $type;
                                
                                # is LVM partition
                                my $is_lvm = $part->get_flag(parted::partition_flag_get_by_name('lvm'));
                                $PhyDevices{$pndev}{'lvm'} = 1 if( $is_lvm );

                                # is swap partition
                                my $is_swap = $part->get_flag(parted::partition_flag_get_by_name('swap'));
                                $PhyDevices{$pndev}{'swap'} = 1 if( $is_swap );
                                $PhyDevices{$pndev}{'swap'} = 1 if( $fs_type eq 'linux-swap' );

                                # is RAID partition
                                my $is_raid = $part->get_flag(parted::partition_flag_get_by_name('raid'));
                                $PhyDevices{$pndev}{'raid'} = 1 if( $is_raid );

                            } 
                        }
                    }
                    if( $PhyDevices{$ndev} ){
                        # no partitions flag
                        $PhyDevices{$ndev}{'nopartitions'} = 1 if( $lpi>0 );
                        # disk device flag
                        $PhyDevices{$ndev}{'diskdevice'} = 1;
                    }
                }
            }
            $dev->close();
        }
    }
}

# multipath maps info
sub pathmapsinfo {

    # testing multipath
    if( &havemultipath() ){

        %PathMaps = ();

        # get info from multipathd
        # TODO must be active
        open(M,'echo "show maps" | /sbin/multipathd -k |');
        my $hmap = <M>;
        chomp($hmap);
        my @hf = split(/\s+/,trim($hmap));
        shift(@hf); # drop first
        while(<M>){
            chomp;
            my $lmap = $_;
            my @lf = split(/\s+/,$lmap);
            if( scalar(@lf) == scalar(@hf) ){
                my %DMap = ();
                for(my $i=0; $i<scalar(@hf); $i++){
                    my $f = tokey($hf[$i]);
                    my $v = $lf[$i];
                    $DMap{"$f"} = $v;
                }
                my $uuid = $DMap{"uuid"};
                my $name = $DMap{"name"};
                $DMap{"device"} = "/dev/mapper/$name";
                $PathMaps{"$uuid"} = \%DMap;
            }
        }
        close(M);

        # get devices from topology
        open(T,'echo "show maps topology" | /sbin/multipathd -k |');
#multipathd> mpath0 (3600a0b800050817400000bab4cc017ec) dm-2  IBM,1814      FAStT
#[size=100G][features=0       ][hwhandler=1 rdac   ][rw        ]
#\_ round-robin 0 [prio=100][enabled]
# \_ 6:0:0:1 sdd 8:48  [active][ready] 
#\_ round-robin 0 [prio=0][enabled]
# \_ 5:0:0:1 sdb 8:16  [active][faulty]
# \_ 5:0:1:1 sdc 8:32  [active][ghost] 
# \_ 6:0:1:1 sde 8:64  [active][ghost] 
#multipathd> 
        my $p_uuid;
        while(<T>){
            chomp;
            s/multipathd> //;   # clean multipathd
            if( /^(\w+)\s+\((\w+)\)\s+(\S+)\s+(\S+)\s+(\S+)$/ ){
                #mpath0 (3600a0b800050817400000bab4cc017ec) dm-2  IBM,1814      FAStT
                my ($n,$i,$d,$v,$m) = ($1,$2,$3,$4,$5);
                $p_uuid = $i;   # mark process
                if( !$PathMaps{"$p_uuid"} ){
                    $PathMaps{"$p_uuid"} = { 'uuid'=>"$p_uuid", 'name'=>$n, 'device'=>"/dev/mapper/$n" };
                }
                $PathMaps{"$p_uuid"}{'vendor'} = $v;
                $PathMaps{"$p_uuid"}{'model'} = $m;
            } elsif( /^\[([^\]]+)\[([^\]]+)\]\[([^\]]+)\]\[([^\]]+)\]$/ ){
                #[size=100G][features=0       ][hwhandler=1 rdac   ][rw        ]
                if( $p_uuid && $PathMaps{"$p_uuid"} ){
                    ( $PathMaps{"$p_uuid"}{'size'}, $PathMaps{"$p_uuid"}{'features'},
                       $PathMaps{"$p_uuid"}{'hwhandler'},$PathMaps{"$p_uuid"}{'permissions'})
                                = ($1,$2,$3,$4);
                }
            } elsif( /^\\_\s+(\S+)\s+(\d+)\s+\[(\S+)\]\[(\w+)\]$/ ){
                #\_ round-robin 0 [prio=100][enabled]
                #my ($sp,$si,$pp,$ps) = ($1,$2,$3,$4);
                if( $p_uuid && $PathMaps{"$p_uuid"} ){
                    my %G = ( 'scheduling_policy'=>$1, 'sp_unknown_digit'=>$2,
                                'path_group_priority_if_known'=>$3,
                                'path_group_status_if_known'=>$4 );
                    if( !$PathMaps{"$p_uuid"}{'group'} ){
                        $PathMaps{"$p_uuid"}{'group'} = [ \%G ];
                    } else {
                        push( @{$PathMaps{"$p_uuid"}{'group'}}, \%G );
                    }
                }
            } elsif( /^\s+\\_\s+(\d+):(\d+):(\d+):(\d+)\s+(\w+)\s+(\d+):(\d+)\s+\[(\w+)\]\[(\w+)\]\s*$/ ){
                # \_ 6:0:0:1 sdd 8:48  [active][ready] 
                #my ($h,$c,$i,$l, $d, $ma,$mi, $ds,$ps) = ($1,$2,$3,$4, $5, $6,$7, $8,$9);
                my %D = ( 'host'=>$1, 'channel'=>$2, 'id'=>$3, 'lun'=>$4,
                            'devnode'=>$5, 'major'=>$6, 'minor'=>$7,
                            'dm_status_if_known'=>$8, 'path_status'=>$9 );
                if( $p_uuid && $PathMaps{"$p_uuid"} ){
                    if( !$PathMaps{"$p_uuid"}{'group'}[-1]{'devices'} ){
                        $PathMaps{"$p_uuid"}{'group'}[-1]{'devices'} = [ \%D ];
                    } else {
                        push( @{$PathMaps{"$p_uuid"}{'group'}[-1]{'devices'}}, \%D );
                    }
                    if( !$PathMaps{"$p_uuid"}{'devices'} ){
                        $PathMaps{"$p_uuid"}{'devices'} = [ \%D ];
                    } else {
                        push( @{$PathMaps{"$p_uuid"}{'devices'}}, \%D );
                    }
                }
            }
        }
        close(T);
    }
    return wantarray() ? %PathMaps : \%PathMaps;
}
# Physical volumes info
sub pvinfo {

    %PVInfo = ( );

    my $opts = "pv_fmt,pv_uuid,pv_size,dev_size,pv_free,pv_used,pv_name,pv_attr,pv_pe_count,pv_pe_alloc_count,pv_tags,vg_name";
    open(I,"/usr/sbin/pvs --separator=';' --units=b --noheadings --options=$opts 2>/dev/null|");

    my @hf = split(/,/,$opts);
    while(<I>){
        chomp;
        my $sline = $_;

        my %H = ( 'type'=>'local' );

        my @hv = split(/;/,$sline);
        # process field by first line
        for(my $i=0; $i<scalar(@hf); $i++){
            my $f = lc tokey($hf[$i]);
            my $v = defined $hv[$i] ? $hv[$i] : "";
            $H{"$f"} = trim($v);
        }

        # redundant field
        $H{"name"} = $H{"pv"} ||= $H{"pv_name"};
        $H{"vg"} ||= $H{"vg_name"};
        my $device = $H{"pv"};

        next if( $device eq "unknown device" );

        my @p = split(/\//,$device);
        my $pv = $H{'name'} = pop @p;   # write right name
        $H{"device"} = $device;

        # grant this fields
        $H{"psize"} ||= $H{"pv_size"};
        $H{"pfree"} ||= $H{"pv_free"};
        $H{'attr'} ||= $H{'pv_attr'};
        $H{'uuid'} ||= $H{'pv_uuid'};

        # size from string to int
        $H{'pvsize'} = $H{"size"} = str2size($H{"psize"});
        $H{'pvfreesize'} = $H{"freesize"} = str2size($H{"pfree"});

        # Attr info
        my ($a,$e) = ( $H{'attr'} =~ m/(.)(.)/ );
        $H{'allocatable'} = 1 if( $a eq 'a' );
		$H{'exported'} = 1 if( $e eq 'x' );
        
        for my $k (qw(size freesize)){
            # pretty string for size field
            $H{"pretty_${k}"} = prettysize($H{"$k"});
        }
        $PVInfo{$pv} = \%H;
    }
    close(I);

    return wantarray() ? %PVInfo : \%PVInfo;
}
# Volume groups info
sub vginfo {
    %VGInfo = ();

    my $opts = "vg_fmt,vg_uuid,vg_name,vg_attr,vg_size,vg_free,vg_sysid,vg_extent_size,vg_extent_count,vg_free_count,max_lv,max_pv,pv_count,lv_count,snap_count,vg_seqno,vg_tags";
    open(I,"/usr/sbin/vgs --separator=';' --units=b --noheadings --options=$opts 2>/dev/null|");

    my @hf = split(/,/,$opts);
    while(<I>){
        chomp;
        my $sline = $_;

        my %H = ( 'type'=>'local' );

        my @hv = split(/;/,$sline);
        # process field by first line
        for(my $i=0; $i<scalar(@hf); $i++){
            my $f = lc tokey($hf[$i]);
            my $v = defined $hv[$i] ? $hv[$i] : "";
            $H{"$f"} = trim($v);
        }

        # redundant field
        my $vg = $H{"name"} = $H{"vg"} ||= $H{"vg_name"};

        # grant this fields
        $H{"vsize"} ||= $H{"vg_size"};
        $H{"vfree"} ||= $H{"vg_free"};
        $H{'attr'} ||= $H{'vg_attr'};
        $H{'uuid'} ||= $H{'vg_uuid'};

        # size to int
        $H{"vgsize"} = $H{"size"} = str2size($H{"vsize"});
        $H{"vgfreesize"} = $H{"freesize"} = str2size($H{"vfree"});

        # Attr info
        my ($pe,$r,$e,$p,$ap,$c) = ( $H{'attr'} =~ m/(.)(.)(.)(.)(.)(.)/ );
        $H{'readonly'} = 1 if( $pe eq 'r' );
        $H{'writeable'} = 1 if( $pe eq 'w' );
        $H{'resizeable'} = 1 if( $r eq 'z' );
        $H{'exported'} = 1 if( $e eq 'x' );
        $H{'partial'} = 1 if( $p eq 'p' );
        $H{'policy'} = 'contiguous' if( $ap eq 'c' );
		$H{'policy'} = 'cling' if( $ap eq 'l' );
		$H{'policy'} = 'normal' if( $ap eq 'n' );
        $H{'policy'} = 'anywhere' if( $ap eq 'a' );
		$H{'policy'} = 'inherited' if( $ap eq 'i' );
        $H{'clustered'} = 1 if( $p eq 'c' );
        
        for my $k (qw(size freesize)){
            # pretty string for size field
            $H{"pretty_${k}"} = prettysize($H{"$k"});
        }
        $VGInfo{"$vg"} = \%H;
    }
    close(I);

    if( $CONF->{'storagedir'} ){
        my %H = ( 'type'=>'local' );
        my $vg = $H{"name"} = $H{"vg"} = $H{"vg_name"} = '__DISK__';

        $VGInfo{"$vg"} = \%H;   # set info on hash to prevent the recursive in loop

        my ($vgsize,$vgfreesize) = get_size_sparsefiles( $CONF->{'storagedir'} );

        $H{"lsize"} = $H{"size"} = $vgsize;
        $H{"lfree"} = $H{"freesize"} = $vgfreesize;
        for my $k (qw(size freesize)){
            # pretty string for size field
            $H{"pretty_${k}"} = prettysize($H{"$k"});
        }
    }

    return wantarray() ? %VGInfo : \%VGInfo;
}
# Logical volumes info
sub lvinfo {
    %LVInfo = ( );

    my $opts = "lv_uuid,lv_name,lv_attr,lv_major,lv_minor,lv_kernel_major,lv_kernel_minor,lv_size,seg_count,origin,snap_percent,copy_percent,move_pv,lv_tags,segtype,stripes,stripesize,chunksize,seg_start,seg_size,seg_tags,devices,regionsize,mirror_log,modules,convert_lv,vg_name";
    open(I,"/usr/sbin/lvs --separator=';' --units=b --noheadings --options=$opts 2>/dev/null|");

    my @hf = split(/,/,$opts);
    while(<I>){
        chomp;
        my $sline = $_;

        my %H = ( 'type'=>'local' );

        my @hv = split(/;/,$sline);
        # process field by first line
        for(my $i=0; $i<scalar(@hf); $i++){
            my $f = lc tokey($hf[$i]);
            my $v = defined $hv[$i] ? $hv[$i] : "";
            $H{"$f"} = trim($v);
        }

        my $lv = $H{"name"} = $H{"lv"} ||= $H{"lv_name"};
        my $vg = $H{"vg"} ||= $H{"vg_name"};
        $H{"lvdevice"} = $H{"aliasdevice"} = $H{"device"} = "/dev/$vg/$lv";
        # for symbolic links resolve them
        $H{"device"} = readlink($H{"device"}) if( -l $H{"device"} );

        # grant this fields
        $H{"lsize"} ||= $H{"lv_size"} || 0;
        $H{"lfree"} ||= $H{"lv_free"} || 0;
        $H{'attr'} ||= $H{'lv_attr'};
        $H{'uuid'} ||= $H{'lv_uuid'};

        $H{"size"} = str2size($H{"lsize"});
        $H{"freesize"} = str2size($H{"lfree"});

        # Attr info
        my ($v,$pe,$ap,$f,$s,$d) = ( $H{'attr'} =~ m/(.)(.)(.)(.)(.)(.)/ );
		$H{'volumetype'} = 'mirrored' if( $v eq 'm' );
		$H{'volumetype'} = 'mirrored  without  initial  sync' if( $v eq 'M' );
		$H{'volumetype'} = 'origin' if( $v eq 'o' );
		$H{'volumetype'} = 'pvmove' if( $v eq 'p' );
		$H{'volumetype'} = 'snapshot' if( $v eq 's' );
		$H{'volumetype'} = 'invalid   snapshot' if( $v eq 'S' );
		$H{'volumetype'} = 'virtual' if( $v eq 'v' );
		$H{'volumetype'} = 'mirror image' if( $v eq 'i' );
		$H{'volumetype'} = 'mirror image out-of-sync' if( $v eq 'I' );
		$H{'volumetype'} = 'under conversion' if( $v eq 'c' );
        $H{'readonly'} = 1 if( $pe eq 'r' );
        $H{'writeable'} = 1 if( $pe eq 'w' );
        $H{'policy'} = 'contiguous' if( $ap eq 'c' );
		$H{'policy'} = 'cling' if( $ap eq 'l' );
		$H{'policy'} = 'normal' if( $ap eq 'n' );
        $H{'policy'} = 'anywhere' if( $ap eq 'a' );
		$H{'policy'} = 'inherited' if( $ap eq 'i' );
        $H{'fixedminor'} = 1 if( $f eq 'm' );
        $H{'state'} = $s;
		$H{'state_dscr'} = 'active' if( $s eq 'a' );
		$H{'state_dscr'} = 'suspended' if( $s eq 's' );
		$H{'state_dscr'} = 'Invalid  snapshot' if( $s eq 'I' );
		$H{'state_dscr'} = 'invalid Suspended snapshot' if( $s eq 'S' );
		$H{'state_dscr'} = 'mapped device present without tables' if( $s eq 'd' );
		$H{'state_dscr'} = 'mapped device present with inactive table' if( $s eq 'i' );

        $H{'deviceopen'} = 1 if( $d eq 'o' );

        $H{"logical"} = $H{"logicalvolume"} = 1;          # mark as logical volume

        for my $k (qw(size freesize)){
            # pretty string for size field
            $H{"pretty_${k}"} = prettysize($H{"$k"});
        }
        $LVInfo{"$lv"} = \%H;
    }
    close(I);

    if( $CONF->{'storagedir'} ){
        opendir(D,$CONF->{'storagedir'});
        my @l = readdir(D);
        for my $f (@l){
            next if( $f =~ m/^\./ );

            my $path = "$CONF->{'storagedir'}/$f";

            # links or regular files only
            if( -f "$path" || -l "$path" ){

                my %H = ( 'filedisk'=>1, 'type'=>'local' );

                my $vg = $H{"vg"} = $H{"vg_name"} = '__DISK__';
                my $lv = $H{"name"} = $H{"lv"} = $H{"lv_name"} = $f;
                my $vg = $H{"vg"} ||= $H{"vg_name"};
                $H{"lvdevice"} = $H{"aliasdevice"} = $H{"device"} = $path;
                # for symbolic links resolve them
                $H{"device"} = readlink($H{"device"}) if( -l $H{"device"} );
                $H{'writeable'} = 1;

                $H{"logical"} = $H{"logicalvolume"} = 1;          # mark as logical volume
                my ($PD) = grep { $_->{'loopdevice'} && ($_->{'loopfile'} eq $path) } values %PhyDevices;
                if( $PD ){
                    $H{'deviceopen'} = 1;
                    $H{'loopdevice'} = $PD->{'device'};
                    $H{'devicename'} = $PD->{'name'};
                }

                my $usize = get_sparsefiles_usagesize( $path );
                my $asize = get_sparsefiles_apparentsize( $path );
                $H{"lsize"} = $H{"size"} = $asize;
                $H{"lfree"} = $H{"freesize"} = $asize - $usize;
                for my $k (qw(size freesize)){
                    # pretty string for size field
                    $H{"pretty_${k}"} = prettysize($H{"$k"});
                }
                $LVInfo{"$lv"} = \%H;
            }
        }
        closedir(D);
    }

    return wantarray() ? %LVInfo: \%LVInfo;
}

sub activate_lvs {
    # force update lvs info
    cmd_exec("/usr/sbin/lvscan");

    # activate all lvs
    for my $L (values %LVInfo){
        if( $L->{'state'} eq '-' ){
            # if not active then activate it
            my ($e,$m) = cmd_exec("/usr/sbin/lvchange","-ay",$L->{'device'});
        }
    }
}

sub mountdev {
    %MountDev = ();

    # mount points
    my @topf = qw( device mountpoint fs options dfreq pnum );
    open(M,"/etc/mtab");
    while(<M>){
        chomp;
        my $line = $_;
        my @al = split(/\s+/,$line);
        my %M = ();
        for(my $i=0; $i<scalar(@topf); $i++){
            my $f = $topf[$i];
            my $v = $al[$i];
            $M{"$f"} = $v;
        }
        my $k = $M{"device"};
        $MountDev{"$k"} = \%M;
    }
    close(M);

    # swap partitions
    open(S,"/proc/swaps");
    my $tline = <S>;    # ignore
    while(<S>){
        chomp;
        my $sline = $_;
        my @sl = split(/\s+/,$sline);
        my %M = ( device => $sl[0],
                    fs => 'swap' );
        my $k = $M{"device"};
        $MountDev{"$k"} = \%M;
    }
    close(S);

    return wantarray() ? %MountDev: \%MountDev;
}
sub chk_path_mount {
    my ($path) = @_;
    if( ! %MountDev ){
        mountdev();
    }
    my $realpath = realpath($path);
    my $M;
    my @lpath = split(/\//,$realpath);
    while(@lpath){
        my $tpath = join("/",@lpath);
        ($M) = grep { $_->{'mountpoint'} && ( $_->{'mountpoint'} eq $tpath ) } values %MountDev;
        if( $M ){
            return $M;
        }
        pop(@lpath);
    }
    my $tpath = '/';
    ($M) = grep { $_->{'mountpoint'} && ( $_->{'mountpoint'} eq $tpath ) } values %MountDev;
    return $M;
}

sub size_blockdev {
    my ($dev) = @_;
    if( -l "$dev" ){
        $dev = readlink $dev;
    }
    if( -b "$dev" ){
        my ($rdev) = (lstat $dev)[6];
        my $major = ( $rdev & MAJOR_MASK ) >> MAJOR_SHIFT;
        my $minor = ( $rdev & MINOR_MASK ) >> MINOR_SHIFT;
        if( -r "/proc/partitions" ){
            open(F,"/proc/partitions");
            while(<F>){
                if( /^\s+$major\s+$minor\s+(\d+)\s+.+$/ ){
                    my $blockn = $1;
                    return ($blockn * 1024); # in bytes
                }
            }
            close(F);
        }
    }
    return 0;
}

sub get_size_path {
    my ($path,$force) = @_;
    if( $force || ! %AllDiskDevices ){
        __PACKAGE__->loaddiskdev($force);
    }

    my ($size,$freesize) = (0,0);
    if( my $M = chk_path_mount($path) ){
        my ($D) = grep { $_->{'mountpoint'} eq $M->{'mountpoint'} } values %AllDiskDevices;
        $size = $D->{'size'};
        $freesize = $D->{'freesize'};
    }
    return wantarray() ? ($size,$freesize) : $size;
}

sub get_size_sparsefiles {
    my ($lv, $force) = @_;

    # To fix SPARSE-FILES free space size misleading:
    # calc sparse-files disk usage
    # sparse-files size
    my $sp_size = get_disk_usage_sparsefiles($lv);
    # real size of files
    my $nsp_size = get_disk_usage($lv);
    my $diff_size = $sp_size - $nsp_size;
    # END

    my ($vgsize,$vgfreesize) = get_size_path( $lv, $force );
    # CMAR 22/03/2010
    # re-calculate vgfreesize based on diff of sparse-files space usage
    $vgfreesize = $vgfreesize - $diff_size;

    return wantarray() ? ($vgsize,$vgfreesize) : $vgfreesize;
}

sub get_sparsefiles_usagesize {
    my ($path) = @_;
    my $size = 0;

    open(P,"/usr/bin/du -s -B1 $path 2>/dev/null |");
    while(<P>){
        chomp;
        my ($s,$p) = split(/\s+/,$_);
        if( $p eq $path ){
            $size = $s;
            last;
        }
    }
    close(P);

    return $size;
}
sub get_sparsefiles_apparentsize {
    my ($path) = @_;
    my $size = 0;

    open(P,"/usr/bin/du -s -B1 --apparent-size $path 2>/dev/null |");
    while(<P>){
        chomp;
        my ($s,$p) = split(/\s+/,$_);
        if( $p eq $path ){
            $size = $s;
            last;
        }
    }
    close(P);

    return $size;
}
sub get_disk_usage {
    my ($path) = @_;
    my $dir = "$path";
    if( ! -d "$path" ){
        ($dir) = ( $path =~ m/((\/[^\/]+)+)\/([^\/]+)/ );
    }

    return get_sparsefiles_usagesize( $dir );
}
sub get_disk_usage_sparsefiles {
    my ($path) = @_;
    my $dir = "$path";
    if( ! -d "$path" ){
        ($dir) = ( $path =~ m/((\/[^\/]+)+)\/([^\/]+)/ );
    }

    return get_sparsefiles_apparentsize( $dir );
}

sub isDevicePathFromMultipath {
    my ($D) = @_;
    my $uuid = $D->{'uuid'};
    if( $uuid && $PathMaps{"$uuid"} ){
        return $uuid;
    } else {
        # try by major and minor
        my $major = $D->{'major'};
        my $minor = $D->{'minor'};
        for my $MP (values %PathMaps){
            if( my $ld = $MP->{'devices'} ){
                if( grep { (($_->{'major'} == $major) && ($_->{'minor'} == $minor)) } @$ld ){
                    return $MP->{'uuid'};
                }
            }
        }
    }
    return;
}

sub update_devices {
    my %BDPhy = ();
    %AllDiskDevices = ();   # reset;
    %AllDiskDevices = %PhyDevices;
    for my $dn (keys %AllDiskDevices){
        my $major = $AllDiskDevices{"$dn"}{"major"};
        my $minor = $AllDiskDevices{"$dn"}{"minor"};
        $BDPhy{"${major}:${minor}"} = $dn;
        if( my $uuid = &isDevicePathFromMultipath($AllDiskDevices{"$dn"}) ){
            $AllDiskDevices{"$dn"}{"multipath"} = 1;    # mark as using multipath
            $AllDiskDevices{"$dn"}{"mpathdevice"} = 1;
            $AllDiskDevices{"$dn"}{"devmapper"} = $PathMaps{"$uuid"}{"device"};
            $AllDiskDevices{"$dn"}{"multipathname"} = $PathMaps{"$uuid"}{"name"};
            push(@{$PathMaps{"$uuid"}{"phydevices"}},$AllDiskDevices{"$dn"});
        }
        if( $PVInfo{"$dn"} ){
            $PVInfo{"$dn"}{"pvinit"} = 1;    # initialized
            $PhyDevices{"$dn"}{"pvinit"} = 1;    # initialized
            $AllDiskDevices{"$dn"}{"pvinit"} = 1;    # initialized
            $AllDiskDevices{"$dn"}{"allocatable"} = $PVInfo{"$dn"}{"allocatable"} if( defined $PVInfo{"$dn"}{"allocatable"} );
            $AllDiskDevices{"$dn"}{"exported"} = $PVInfo{"$dn"}{"exported"} if( defined $PVInfo{"$dn"}{"exported"} );
            $AllDiskDevices{"$dn"}{"pv_uuid"} = $PVInfo{"$dn"}{'uuid'};
            $AllDiskDevices{"$dn"}{"pv"} = $PVInfo{"$dn"}{'pv'};
            $AllDiskDevices{"$dn"}{"vg"} = $PVInfo{"$dn"}{'vg'};
            $AllDiskDevices{"$dn"}{"pvsize"} = $PVInfo{"$dn"}{'size'};
            $AllDiskDevices{"$dn"}{"pvfreesize"} = $PVInfo{"$dn"}{'freesize'};
        }
        my $dev = $AllDiskDevices{"$dn"}{'device'};
        if( $MountDev{"$dev"} ){
            $AllDiskDevices{"$dn"}{"mounted"} = 1;
            $AllDiskDevices{"$dn"}{"mountpoint"} = $MountDev{"$dev"}{'mountpoint'};
        }

        my $blockdir = "/sys/block/$dn";
        if( -d "$blockdir" ){
            opendir(D,$blockdir);
            my @dparts = grep { /$dn/ } readdir(D);
            close(D);
            if( scalar(@dparts) ){
                # have partitions
                $AllDiskDevices{"$dn"}{'partitioned'} = 1;
                my $alsize = 0;
                for my $par (@dparts){
                    $AllDiskDevices{"$par"}{"partition"} = 1;
                    $alsize += $AllDiskDevices{"$par"}{'size'};
                }
                $AllDiskDevices{"$dn"}{'freesize'} = $AllDiskDevices{"$dn"}{'size'} - $alsize;
                $AllDiskDevices{"$dn"}{'freesize'} = 0 if( $AllDiskDevices{"$dn"}{'freesize'} < 0 );
            }
        }

    }

    for my $muid (keys %PathMaps){
        my $name = $PathMaps{"$muid"}{"name"};
        my $device = $PathMaps{"$muid"}{"device"};
        my $phydevm = $PathMaps{"$muid"}{"sysfs"};

        $AllDiskDevices{"$phydevm"}{"multipath"} = 1;       # mark as multipath device
        $AllDiskDevices{"$phydevm"}{"mpathsysdev"} = 1; # mark as multipath sys device
        $AllDiskDevices{"$phydevm"}{"type"} = "SAN";    # type SAN
        $AllDiskDevices{"$phydevm"}{"mmapper"} = $PathMaps{"$muid"};
        $AllDiskDevices{"$phydevm"}{"devmapper"} = $PathMaps{"$muid"}{"device"};

        $AllDiskDevices{"$name"}{"device"} = $PathMaps{"$muid"}{"device"};
        $AllDiskDevices{"$phydevm"}{'mp_uuid'} = $AllDiskDevices{"$name"}{"mp_uuid"} = $muid;
        $AllDiskDevices{"$phydevm"}{'uuid'} = $AllDiskDevices{"$name"}{"uuid"} = $muid;

        $AllDiskDevices{"$name"} = { %{$AllDiskDevices{"$phydevm"}}, %{$AllDiskDevices{"$name"}} };

        if( $PVInfo{"$name"} ){
            $AllDiskDevices{"$phydevm"}{'isalias'} = 1;
            $PVInfo{"$name"}{'aliasdevice'} = $AllDiskDevices{"$name"}{'aliasdevice'} = $AllDiskDevices{"$phydevm"}{"device"};

            $PVInfo{"$name"}{"pvinit"} = 1;    # initialized

            $PVInfo{"$name"}{"type"} = "SAN";
            if( my $vg = $PVInfo{"$name"}{'vg_name'} ){
                $VGInfo{"$vg"}{'type'} = "SAN";
            }

            $PhyDevices{"$name"}{"pvinit"} = 1;    # initialized
            $AllDiskDevices{"$name"}{"pvinit"} = 1;    # initialized
            $AllDiskDevices{"$name"}{"allocatable"} = $PVInfo{"$name"}{"allocatable"} if( defined $PVInfo{"$name"}{"allocatable"} );
            $AllDiskDevices{"$name"}{"exported"} = $PVInfo{"$name"}{"exported"} if( defined $PVInfo{"$name"}{"exported"} );
            $AllDiskDevices{"$name"}{"pv_uuid"} = $PVInfo{"$name"}{'pv_uuid'};
            $AllDiskDevices{"$name"}{"pv"} = $PVInfo{"$name"}{'pv'};
            $AllDiskDevices{"$name"}{"vg"} = $PVInfo{"$name"}{'vg'};
            $AllDiskDevices{"$name"}{"pvsize"} = $PVInfo{"$name"}{'size'};
            $AllDiskDevices{"$name"}{"pvfreesize"} = $PVInfo{"$name"}{'freesize'};

            $AllDiskDevices{"$name"}{"uuid"} = $PVInfo{"$name"}{'uuid'} = $muid;
        } else {
            $AllDiskDevices{"$name"}{'isalias'} = 1;
            $AllDiskDevices{"$phydevm"}{'aliasdevice'} = $AllDiskDevices{"$name"}{"device"};
            if( $PVInfo{"$phydevm"} ){
                $PVInfo{"$phydevm"}{"type"} = "SAN";
                if( my $vg = $PVInfo{"$phydevm"}{'vg_name'} ){
                    $VGInfo{"$vg"}{'type'} = "SAN";
                }
                $AllDiskDevices{"$phydevm"}{"uuid"} = $PVInfo{"$phydevm"}{'uuid'} = $muid;
                $PVInfo{"$phydevm"}{'aliasdevice'} = $AllDiskDevices{"$phydevm"}{'aliasdevice'} = $AllDiskDevices{"$name"}{"device"};
            }
        }
    }

    for my $lv (keys %LVInfo){
        my $major = $LVInfo{"$lv"}{"lv_kernel_major"};
        my $minor = $LVInfo{"$lv"}{"lv_kernel_minor"};
        my $pd = $LVInfo{"$lv"}{"phydev"} = $BDPhy{"${major}:${minor}"}; 
         
        if( $pd ){
            $PhyDevices{"$pd"}{"device"} = $LVInfo{"$lv"}{"device"};
            $PhyDevices{"$pd"}{"logicaldevice"} = 1;
        }

        my $vg = $LVInfo{"$lv"}{"vg"};
        $LVInfo{"$lv"}{"vgsize"} = $VGInfo{"$vg"}{"size"} || 0;
        $LVInfo{"$lv"}{"vgfreesize"} = $VGInfo{"$vg"}{"freesize"} || 0;

        # reference to volume group
        $LVInfo{"$lv"}{"volumegroup"} = $VGInfo{"$vg"};

        $LVInfo{"$lv"}{'type'} = $VGInfo{"$vg"}{'type'};

        $AllDiskDevices{"$lv"} = $LVInfo{"$lv"};

        my $dev = $AllDiskDevices{"$lv"}{'device'};
        my $adev = $AllDiskDevices{"$lv"}{'aliasdevice'};
        if( $MountDev{"$dev"} ||
             $MountDev{"$adev"} ){
            $AllDiskDevices{"$lv"}{'mounted'} = 1;
            
            $AllDiskDevices{"$lv"}{'mountpoint'} = $MountDev{"$dev"} ? $MountDev{"$dev"}{'mountpoint'} : $MountDev{"$adev"}{'mountpoint'};
        }
    }

    for my $pv (keys %PVInfo){
        if( my $vg = $PVInfo{"$pv"}{"vg"} ){
            $VGInfo{"$vg"}{'physicalvolumes'}{"$pv"} = $PVInfo{"$pv"};
        }
    }

    my $hdf = new Filesys::DiskFree;
    $hdf->df();

    for my $name (keys %AllDiskDevices){
        my $kn = $name;
        my $D = $AllDiskDevices{"$kn"};

        my $dev = $D->{'device'};
        my $adev = $D->{'aliasdevice'};
        if( $MountDev{"$dev"} ||
                $MountDev{"$adev"} ){
            $D->{"mounted"} = 1;
            $D->{"mountpoint"} = $MountDev{"$dev"} ? $MountDev{"$dev"}{'mountpoint'} : $MountDev{"$adev"}{'mountpoint'};
        }

        if( $D->{'mpathsysdev'} ){

            my $Dev_PathMap = $D->{"mmapper"};
            my $devs = $Dev_PathMap->{"phydevices"};
            $devs = [] if( !$devs );
            $D->{'multipath'} = 1;
            $D->{'paths'} = scalar(@$devs);

            $D->{"type"} = "SAN";       # just in case...
        } 

        $D->{"type"} = "local" if( !$D->{'type'} ); # type as local by default

        if( $D->{'mounted'} ){
            $D->{'size'} = $hdf->total($D->{'device'});
            $D->{'freesize'} = $hdf->avail($D->{'device'});
            for my $k (qw(size freesize)){
                # pretty string for size field
                $D->{"pretty_${k}"} = prettysize($D->{"$k"});
            }
        }

        if( $PVInfo{"$kn"} ){
            # set type on pv 
            $PVInfo{"$kn"}{'type'} = $D->{'type'};
            if( my $vg = $PVInfo{"$kn"}{'vg_name'} ){
                $VGInfo{"$vg"}{'type'} = $D->{'type'};
            }
        }

        if( !$D->{'uuid'} ){
            # fix uuid
            $D->{'uuid'} = $D->{'mp_uuid'} || $D->{'pv_uuid'};
        }
        insert_disk($kn,$D);
        insert_physical($kn,$D);
    }
}

sub insert_physical {
    my ($key,$D) = @_;
    my $phy = 1;
 
    $phy = 0 if( ignore_diskdevice($D) );
    $phy = 0 if( $D->{'logical'} );
    # TODO
    #   partition LVM only ? not so sure
    $phy = 0 if( $D->{'partition'} && !$D->{'lvm'} );

    #   ignore logical and loop devices
    $phy = 0 if( $D->{"loopdevice"} );
    $phy = 0 if( $D->{"logicaldevice"} );

    # ignore file disks
    $phy = 0 if( $D->{"filedisk"} );

    if( $phy ){
        $PhyDisk{$key} = cp_devicehash($D);
        for my $k (keys %$D){
            if( $k =~ m/size/ && $k !~ m/^pretty/){
                # pretty string for size field
                $PhyDisk{"$key"}{"pretty_${k}"} = prettysize($D->{"$k"});
            }
        }
    }
}
sub insert_disk {
    my ($key,$D) = @_;
    my $disk = 1;

    $disk = 0 if( ignore_diskdevice($D) );

    if( $disk ){
        $DiskDevices{$key} = cp_devicehash($D);
        for my $k (keys %$D){
            if( $k =~ m/size/ && $k !~ m/^pretty/){
                # pretty string for size field
                $DiskDevices{"$key"}{"pretty_${k}"} = prettysize($D->{"$k"});
            }
        }
    }
}
sub cp_devicehash {
    my ($D,@fields) = @_;
    if( !@fields ){
        @fields = qw( pvinit size freesize logical device allocatable exported mounted mountpoint partition partitioned uuid pv_uuid mp_uuid pv pvsize pvfreesize vg vgsize vgfreesize lv type lvm swap nopartitions fs_type dtype type diskdevice );  
    }
    my %H = ();
    for my $k (@fields){
        next if( $H{"$k"} );
        if( defined $D->{"$k"} ){
            $H{"$k"} = $D->{"$k"};
        }
    }
    return wantarray() ? %H : \%H;
}
sub ignore_diskdevice {
    my ($D) = @_;
    my $ignore = 0;
    
#    if( $D->{'mpathsysdev'} ){
#        $ignore = 1;
#    }
    if( $D->{'mpathdevice'} ){
        $ignore = 1;
    }
    if( !-e "$D->{device}" ){
        $ignore = 2;
    }

    # ignore swap
    if( $D->{'swap'} ){
        $ignore = 3;
    }

    # ignore disk devices with partitions
    if( $D->{'partitioned'} ){
        $ignore = 4;
    }

    # ignore partitions extended
    if( $D->{'dtype'} eq 'extended' ){
        $ignore = 5;
    }

    # ignore mounted partitions
    if( $D->{'mounted'} ){
        $ignore = 6;
    }

    # ignore partitions with file system
    if( $D->{'fs_type'} ){
        $ignore = 7;
    }

    # ignore loop devices
    if( $D->{'loopdevice'} ){
        $ignore = 8;
    }

    # ignore alias device
    if( $D->{'isalias'} ){
        $ignore = 1;
    }

    return $ignore;
}

=item pvcreate

create physical volume

    my $OK = VirtAgent::Disk->pvcreate( device=>$dev );

=cut

# pvcreate
#   physical volume creating function
#
#   args: device
#   res: ok
sub pvcreate {
    my $self = shift;
    my ($device) = my %p = @_;
    $device = $p{"device"} if( $p{"device"} );
    my @pd = split(/\//,$device);
    my $dn = pop(@pd);

    $self->loaddiskdev();

    if( my $PD = $self->getphydev(%p, 'name'=>$dn) ){ 
        if( !$self->getpv(%p, 'name'=>$dn) ){
            $device = $PD->{'device'};

            my ($e,$m) = cmd_exec("/usr/sbin/pvcreate $device");

            $self->loaddiskdev(1);  # update disk device info
            unless( $e == 0 ){
                return retErr("_ERR_DISK_PVCREATE_","Error creating physical volume.");
            }
            my $PV = $self->getpv(%p, 'name'=>$dn);
            return retOk("_OK_PVCREATE_","Physical volume successfully created.","_RET_OBJ_",$PV);
        } else {
            return retErr("_ERR_DISK_DEVICE_ALREADY_INIT_","Disk device already initialized");
        }
    } else {
        return retErr("_INVALID_DISK_DEVICE_","Invalid disk device");
    }
}

=item pvremove

remove physical volume

    my $OK = VirtAgent::Diks->pvremove( device=>$dev );

=cut

# pvremove
#   physical volume remove function
#
#   args: device
#   res: ok
sub pvremove {

    my $self = shift;
    my ($device) = my %p = @_;
    $device = $p{"device"} if( $p{"device"} );
    my @pd = split(/\//,$device);
    my $dn = pop(@pd);

    $self->loaddiskdev();

    if( my $PV = $self->getpv(%p, 'name'=>"$dn") ){
        $device = $PV->{'device'};
        my ($e,$m) = cmd_exec("/usr/sbin/pvremove",$device);
        $self->loaddiskdev(1);  # update disk device info
        unless( $e == 0 ){
            return retErr("_ERR_DISK_PVREMOVE_","Error remove physical volume: $m");
        }
        if( my $PD = $self->getphydev( %p, 'name'=>$dn ) ){
            # update new device and alias device
            $PV->{'device'} = $PD->{'device'};
            $PV->{'aliasdevice'} = $PD->{'aliasdevice'};
        }
        return retOk("_OK_PVREMOVE_","Physical volume successfully removed.","_RET_OBJ_",$PV);
    } else {
        return retErr("_INVALID_PV_","Invalid physical volume.");
    }
}

=item pvresize

resize physical volume

    my $OK = VirtAgent::Disk->pvresize( device=>$dev, size=>$size );

    size: 40, 512M, 1G

=cut

# pvresize
#   physical volume resize function
#
#   args: device, size
#   res: ok || Error
sub pvresize {
    my $self = shift;
    my ($device,$size) = my %p = @_;
    $device = $p{"device"} if( $p{"device"} );
    $size = $p{"size"} if( $p{"size"} );
    my @pd = split(/\//,$device);
    my $dn = pop(@pd);

    $self->loaddiskdev();

    if( my $PV = $self->getpv(%p, 'name'=>"$dn") ){
        $device = $PV->{'device'};
        my ($e,$m) = cmd_exec("/usr/sbin/pvresize","--setphysicalvolumesize",$size,$device);
        $self->loaddiskdev(1);  # update disk device info
        unless( $e == 0 ){
            return retErr("_ERR_DISK_PVRESIZE_","Error resizing physical volume.");
        }
        $PV = $self->getpv(%p, 'name'=>"$dn");
        return retOk("_OK_PVRESIZE_","Physical volume successfully resized.","_RET_OBJ_",$PV);
    } else {
        return retErr("_INVALID_PV_","Invalid physical volume.");
    }
}

=item vgcreate

create volume group

    my $OK = VirtAgent::Disk->vgcreate( vgname=>$vg, pv1=>$pv1, ..., pvn=>$pvn );

=cut

# vgcreate
#   volume group creating function
#
#   args: vgname, pv1, pv2, ..., pvn
#   res: ok || Error
sub vgcreate {
    my $self = shift;

    my ($vgname,@pv) = my %p = @_;
    $vgname = delete $p{"vgname"} if( $p{"vgname"} );
    my @vs = values %p;
    @pv = @vs if( scalar(@pv) > scalar(@vs) );

    $self->loaddiskdev();

    my @lpv = ();
    for my $pd (@pv){
        my $uuid;
        $uuid = $pd if( $pd !~ m/^\/dev/ );
        if( my $PV = $self->getpv( 'device'=>$pd, 'uuid'=>$uuid ) ){
            push(@lpv,$PV->{'device'});
        }
    }
    if( !$self->getvg( %p, 'name'=>$vgname ) ){
        my ($e,$m) = cmd_exec("/usr/sbin/vgcreate",$vgname,@lpv);

        $self->loaddiskdev(1);  # update disk device info
		unless( $e == 0 ){
            return retErr("_ERR_DISK_VGCREATE_","Error creating volume group.");
        }
        my $VG = $self->getvg( %p, 'name'=>$vgname );
        return retOk("_OK_VGCREATE_","Volume group successfully created.","_RET_OBJ_",$VG);
    } else {
        return retErr("_ERR_VGCREATE_DUP_","Error volume group already exists.");
    }
}

=item vgremove

remove volume group

    my $OK = VirtAgent::Disk->vgremove( vgname=>$vg );

=cut

# vgremove
#   volume group remove function
#
#   args: vgname
#   res: ok || Error
sub vgremove {
    my $self = shift;

    my ($vgname,@pv) = my %p = @_;
    $vgname = delete $p{"vgname"} if( $p{"vgname"} );

    $self->loaddiskdev();

    if( my $VG = $self->getvg( %p, 'name'=>$vgname ) ){
        $vgname = $VG->{'vg_name'};
        my ($e,$m) = cmd_exec("/usr/sbin/vgremove",$vgname);

        $self->loaddiskdev(1);  # update disk device info
        unless( $e == 0 ){
            return retErr("_ERR_DISK_VGREMOVE_","Error removing volume group.");
        }

        # reference to updated physicalvolumes
        my %PVS = ();
        my $oldPV = $VG->{"physicalvolumes"};
        if( $oldPV ){
            for my $PV ( values %$oldPV ){
                my $nPV = $self->getpv( 'uuid'=>$PV->{'uuid'}, 'device'=>$PV->{'device'} );
                my @p = split(/\//,$nPV->{'name'});
                my $pv = pop @p;   # get right name
                $PVS{"$pv"} = $nPV;
            }
        }
        $VG->{"physicalvolumes"} = \%PVS;
        return retOk("_OK_VGREMOVE_","Volume group successfully removed.","_RET_OBJ_",$VG);
    } else {
        return retErr("_INVALID_VG_","Invalid volume group.");
    }
}

=item vgextend

add physical volumes to volume group

    my $OK = VirtAgent::Disk->vgextend( vgname=>$vg, pv1=>$pv1, ..., pvn=>$pvn );

=cut

# vgextend
#   volume group extend function
#   
#   args: vgname, pv1, pv2, ..., pvn
#   res: ok || Error
sub vgextend {
    my $self = shift;

    my ($vgname,@pv) = my %p = @_;
    $vgname = delete $p{"vgname"} if( $p{"vgname"} );
    my @vs = values %p;
    @pv = @vs if( scalar(@pv) > scalar(@vs) );

    $self->loaddiskdev();

    my @lpv = ();
    for my $pd (@pv){
        my $uuid;
        $uuid = $pd if( $pd !~ m/^\/dev/ );
        if( my $PV = $self->getpv( 'device'=>$pd, 'uuid'=>$uuid ) ){
            push(@lpv,$PV->{'device'});
        }
    }
    if( my $VG = $self->getvg( %p, 'name'=>$vgname ) ){
        $vgname = $VG->{'vg_name'};
        my ($e,$m) = cmd_exec("/usr/sbin/vgextend",$vgname,@lpv);

        $self->loaddiskdev(1);  # update disk device info
        unless( $e == 0 ){
            return retErr("_ERR_DISK_VGEXTEND_","Error extend volume group.");
        }

        $VG = $self->getvg( %p, 'name'=>$vgname );
        return retOk("_OK_VGEXTEND_","Volume group successfully extended.","_RET_OBJ_",$VG);
    } else {
        return retErr("_INVALID_VG_","Invalid volume group.");
    }
}

=item vgreduce

remove physical volumes from volume group

    my $OK = VirtAgent::Disk->vgreduce( vgname=>$vn, pv1=>$pv1, ..., pvn=>$pvn );

=cut

# vgreduce
#   allow remove physical volumes from volume group
#   
#   args: vgname, pv1, pv2, ..., pvn
#   res: ok || Error 
sub vgreduce {
    my $self = shift;

    my ($vgname,@pv) = my %p = @_;
    $vgname = delete $p{"vgname"} if( $p{"vgname"} );
    my @vs = values %p;
    @pv = @vs if( scalar(@pv) > scalar(@vs) );

    $self->loaddiskdev();

    my @lpv = ();
    for my $pd (@pv){
        my $uuid;
        $uuid = $pd if( $pd !~ m/^\/dev/ );
        if( my $PV = $self->getpv( 'device'=>$pd, 'uuid'=>$uuid ) ){
            push(@lpv,$PV->{'device'});
        }
    }
    if( my $VG = $self->getvg( %p, 'name'=>$vgname ) ){
        $vgname = $VG->{'vg_name'};
        my ($e,$m) = cmd_exec("/usr/sbin/vgreduce",$vgname,@lpv);

        $self->loaddiskdev(1);  # update disk device info
        unless( $e == 0 ){
            return retErr("_ERR_DISK_VGREDUCE_","Error reduce volume group.");
        }

        $VG = $self->getvg( %p, 'name'=>$vgname );
        return retOk("_OK_VGREDUCE_","Volume group successfully reduced.","_RET_OBJ_",$VG);
    } else {
        return retErr("_INVALID_VG_","Invalid volume group.");
    }
}


=item vgpvremove

remove one physical volume from volume group

    my $OK = VirtAgent::Disk->vgpvremove( vgname=>$vg, pv=>$pv );

=cut

# vgpvremove
#   drop physical volume from volume group
#   
#   args: vgname, pv
#   res: ok || Error 
sub vgpvremove {
    my $self = shift;

    my ($vgname,$pv) = my %p = @_;
    $vgname = delete $p{"vgname"} if( $p{"vgname"} );
    $pv = delete $p{"pv"} if( $p{"pv"} );

    $self->loaddiskdev();

    # get physical volume name
    my $pv_uuid;
    $pv_uuid = $pv if( $pv !~ m/^\/dev/ );
    my $PV = $self->getpv( %p, 'device'=>$pv, 'uuid'=>$pv_uuid );

    if( my $VG = $self->getvg( %p, 'name'=>$vgname ) ){
        $vgname = $VG->{'vg_name'};

        if( $PV ){
            $pv = $PV->{'device'};
            my ($e,$m) = cmd_exec("/usr/sbin/vgreduce",$vgname,$pv);

            $self->loaddiskdev(1);  # update disk device info
            unless( $e == 0 ){
                return retErr("_ERR_DISK_VGPVREMOVE_","Error remove physical volume from volume group.");
            }

            return retOk("_OK_VGPVREMOVE_","Physical volume successfully removed from volume group.","_RET_OBJ_",$PV);
        } else {
            return retErr("_INVALID_PV_","Invalid physical volume.");
        }
    } else {
        return retErr("_INVALID_VG_","Invalid volume group.");
    }
}

# getvgpvs
#   get volume group physical volumes
#   args: empty
#   res: Hash { vg => { info, pv => { info }  } }
sub getvgpvs {
    my $self = shift;
    
    # an alias
    my %vgs = $self->getvgs(@_);
    my @lvgs = values %vgs;
    return wantarray() ? @lvgs : \@lvgs;
}

=item lvcreate

create logical volume

    my $OK = VirtAgent::Disk->lvcreate( lv=>$lv, vg=>$vg, size=>$size );

    size: 400, 512M, 2G

=cut

# lvcreate
#   logical volume creating function
#
#   args: lv,vg,size
#   res: ok || Error

sub ddcalc_bs_bc {
    my ($size) = @_;
    # determine block-size and block-count
    my $bs = 1;
    my $c = $size;
    while(int($c/1024)){
        $c = int($c / 1024);
        $bs = $bs * 1024;
    }
    return ($bs,$c);
}

sub ddcreate {
    my $self = shift;
    my (%p) = @_;

    if( ! -e "$p{'path'}" ){
        my $size = str2size($p{'size'});
        if( $size ){
            # determine block-size and block-count
            #my ($bs,$c) = ddcalc_bs_bc($size);

            # create disk file with zeros
            my ($e,$m) = cmd_exec("/bin/dd if=/dev/zero of=$p{'path'} bs=1 count=0 seek=$size");
            # TODO testing error cmd
            unless( $e == 0 || $e == -1 ){
                return retErr('_ERR_DDCREATE_', " Error create file disk: " . $m);
            }
            return retOk('_OK_DDCREATE_',"Disk created successfully.");
        } else {
            return retErr('_ERROR_DDCREATE_',"No valid size.");
        }
    } else {
        return retErr('_ERROR_DDCREATE_',"Disk already exists.");
    }
}
sub ddresize {
    my $self = shift;
    my (%p) = @_;

    if( -e "$p{'path'}" ){
        $self->loaddiskdev(1);
        # testing if file is in use
        if( $self->getphydev( 'loopfile'=>$p{'path'} ) ){
            return retErr('_ERROR_DDRESIZE_',"Disk is in use.");
        } else {
            my $size = str2size($p{'size'});
            my $min_size = 4 * 1024; # min is 4k

            if( $size > $min_size ){
                # resize for both cases: increase and decrease
                #   resize is set zero-block in seek position
                my ($e,$m) = cmd_exec_errh("/bin/dd if=/dev/zero of=$p{'path'} bs=1 count=0 seek=$size");    

                return retOk('_OK_DDRESIZE_',"Disk resized successfully.");
            } else {
                return retErr('_ERROR_DDRESIZE_',"Resize size is too short.");
            }
        }
    } else {
        return retErr('_ERROR_DDRESIZE_',"Disk does not exists.");
    }
}
sub ddremove {
    my $self = shift;
    my (%p) = @_;

    if( -e "$p{'path'}" ){
        $self->loaddiskdev(1);
        # testing if file is in use
        if( $self->getphydev( 'loopfile'=>$p{'path'} ) ){
            return retErr('_ERROR_DDREMOVE_',"Disk is in use.");
        } else {
            # delete it
            unlink("$p{'path'}");
            return retOk('_OK_DDREMOVE_',"Disk deleted successfully.");
        }
    } else {
        return retErr('_ERROR_DDREMOVE_',"Disk does not exists.");
    }
}

sub lvcreate {
    my $self = shift;
    my ($lv,$vg,$size) = my %p = @_;
    if( $p{'lv'} || $p{"vg"} || $p{"size"} ){
        $lv = $p{"lv"};
        $vg = $p{"vg"};
        $size = $p{"size"};
    }
    my @lvp = split(/\//,$lv);
    my $lvn = pop @lvp;

    $self->loaddiskdev();

    if( $vg eq '__DISK__' ){
        if( !$self->getlv('device'=>$lv, 'name'=>$lvn) ){

            # check freesize
            my $VG = $self->getvg(%p, 'name'=>$vg);

            # for security stuff use percentage limit for freesize
            my $b_vg_size = $VG->{'freesize'};
            my $b_vg_size_limit = $b_vg_size * $LIMIT_SIZE_DISK_PERC;
            my $b_lv_size = str2size($size);

            # min size disk test
            if( $b_lv_size < $MIN_SIZE_DISK ){
                return retErr("_ERR_DISK_LVCREATE_","Logical volume size is too small.");
            }
            if( $b_lv_size >= $b_vg_size_limit ){
                return retErr("_ERR_DISK_LVCREATE_","Volume group dont have free size available.");
            }

            # CMAR 02/03/2010
            #   special case for create by dd 
            my $E = $self->ddcreate( 'path'=>$lv, 'size'=>$size );

            $self->loaddiskdev(1);  # update disk device info

            # Get last logical volume created
            my $LV = $self->getlv('device'=>$lv, 'name'=>$lvn);

            if( isError($E) ){
                return retErr("_ERR_DISK_LVCREATE_","Error creating logical volume: ".$E->{'_errordetail_'});
            }
            return retOk("_OK_LVCREATE_","Special logical volume successfully created.","_RET_OBJ_",$LV);
        } else {
            return retErr("_ERR_DISK_LVCREATE_","Logical volume already exists.");
        }
    } elsif( my $VG = $self->getvg( %p, 'name'=>$vg ) ){
        $vg = $VG->{'vg_name'};
        my ($e,$m) = cmd_exec("/usr/sbin/lvcreate","-L",$size,"-n",$lv,$vg);

        $self->loaddiskdev(1);  # update disk device info
        unless( $e == 0 ){
            # return error if not created
            if( !$self->getlv('name'=>$lv) ){
                return retErr("_ERR_DISK_LVCREATE_","Error creating logical volume.");
            }
        }
        # Get last logical volume created
        my $LV = $self->getlv('name'=>$lv);
        return retOk("_OK_LVCREATE_","Logical volume successfully created.","_RET_OBJ_",$LV);
    } else {
        return retErr("_INVALID_VG_","Invalid volume group");
    }
}

=item lvremove

remove logical volume

    my $OK = VirtAgent::Disk->lvremove( lv=>$lv, vg=>$vg );

=cut

# lvremove
#   logical volume remove function
#
#   args: lv
#   res: ok || Error
sub lvremove {
    my $self = shift;
    my ($lv,$vg) = my %p = @_;
    if( $p{"lv"} || $p{"vg"} ){
        $lv = $p{"lv"};
        $vg = $p{"vg"};
    }
    my @lvp = split(/\//,$lv);
    my $lvn = pop @lvp;

    $self->loaddiskdev();

    if( my $LV = $self->getlv(%p, 'name'=>"$lvn" ) ){
        if( $LV->{'lv_name'} ne "$lvn" ){   # when name not equal... something goes wrong
            $lvn = $LV->{'lv_name'};        # ... fix it...
            $vg = $LV->{'vg_name'};
            $lv = $LV->{'device'};
        }
        my $VG = $self->getvg(%p, 'name'=>$vg);
        $vg = $VG->{'vg_name'};

        if( $vg eq '__DISK__' ){
            # CMAR 04/03/2010
            #   special case for remove file disks
            my $E = $self->ddremove( 'path'=>$lv );

            $self->loaddiskdev(1);  # update disk device info

            $VG = $self->getvg(%p, 'name'=>$vg);
            $vg = $VG->{'vg_name'};

            # send update vg freesize
            $LV->{"vgfreesize"} = $VG->{"freesize"} || 0;

            # reference to updated volume group
            $LV->{"volumegroup"} = $VG;

            if( isError($E) ){
                return retErr("_ERR_DISK_LVREMOVE_","Error remove logical volume: ".$E->{'_errordetail_'});
            }
            return retOk("_OK_LVREMOVE_","Special logical volume successfully removed.","_RET_OBJ_",$LV);
        } else {
            my $tlv = $lv;
            if( $vg ){
                $tlv = "${vg}/${lvn}";
            } else {
                $vg = $LV->{"vg_name"};  # set vg
            }
            if( !$tlv ){
                $lv = $tlv = $LV->{'device'};
                $lvn = $LV->{'lv_name'};
                $vg = $LV->{'vg_name'};
            }
            my ($e,$m) = cmd_exec("/usr/sbin/lvremove","-f",$tlv);

            $self->loaddiskdev(1);  # update disk device info
            unless( $e == 0 ){
                # send error if LV remove not successful
                unless( !$self->getlv( 'device'=>$tlv, %p ) ){
                    return retErr("_ERR_DISK_LVREMOVE_","Error remove logical volume.");
                }
            }

            $VG = $self->getvg(%p, 'name'=>$vg);
            $vg = $VG->{'vg_name'};

            # send update vg freesize
            $LV->{"vgfreesize"} = $VG->{"freesize"} || 0;

            # reference to updated volume group
            $LV->{"volumegroup"} = $VG;

            return retOk("_OK_LVREMOVE_","Logical volume successfully removed.","_RET_OBJ_",$LV);
        }
    } else {
        return retErr("_INVALID_LV_","Invalid logical volume");
    }
}

=item lvresize

resize logical volume

    my $OK = VirtAgent::Disk->lvresize( lv=>$lv, size=>$size );

=cut

# lvresize
#   logical volume resize function
#
#   args: lv,size
#   res: ok || Error
sub lvresize {
    my $self = shift;
    my ($lv,$size) = my %p = @_;
    if( $p{"lv"} || $p{"size"} ){
        $lv = $p{"lv"};
        $size = $p{"size"};
    }

    $self->loaddiskdev();

    if( my $LV = $self->getlv( 'device'=>$lv, %p ) ){
        my $vg = $LV->{'vg_name'};
        if( $vg eq '__DISK__' ){

            # get Volume group
            my $VG = $self->getvg(%p, 'name'=>$vg);

            # check freesize
            my $b_vg_size = $VG->{'freesize'};
            my $b_vg_size_limit = $b_vg_size * $LIMIT_SIZE_DISK_PERC;
            my $b_lv_size = str2size($size);
            if( $b_lv_size < $MIN_SIZE_DISK ){
                return retErr("_ERR_DISK_LVRESIZE_","Logical volume size is too small.");
            }
            my $d_size = $b_lv_size - $LV->{'size'};
            if( $d_size > 0 && ( $d_size >= $b_vg_size_limit ) ){
                return retErr("_ERR_DISK_LVRESIZE_","Volume group dont have free size available.");
            }

            # CMAR 04/03/2010
            #   special case for resize file disks
            my $E = $self->ddresize( 'path'=>$lv, 'size'=>$size );

            $self->loaddiskdev(1);  # update disk device info

            # Get updated logical volume
            $LV = $self->getlv( 'device'=>$lv, %p );
            if( isError($E) ){
                return retErr("_ERR_DISK_LVRESIZE_","Error reisze logical volume: ".$E->{'_errordetail_'});
            }
            return retOk("_OK_LVRESIZE_","Special logical volume successfully resized.","_RET_OBJ_",$LV);
        } else {
            $lv = $LV->{'device'} if( !$lv );
            my ($e,$m) = cmd_exec("/usr/sbin/lvresize","-f","-L",$size,$lv);

            $self->loaddiskdev(1);  # update disk device info
            unless( $e == 0 ){
                return retErr("_ERR_DISK_LVRESIZE_","Error resize logical volume.");
            }
        }
    } else {
        return retErr("_INVALID_LV_","Invalid logical volume");
    }
    my $LV = $self->getlv( 'device'=>$lv, %p );
    return retOk("_OK_LVRESIZE_","Logical volume successfully resized.","_RET_OBJ_",$LV);
}

=item createsnapshot

create snapshot of logical volume

    my $OK = VirtAgent::Disk->createsnapshot( olv=>$lv, slv=>$snapshot, size=>$size );

    size: 512M, 1G

=cut

# createsnapshot
#   create a snapshot from logical volume
#
#   args: olv,slv,size
#   res: ok || Error
# e.g. lvcreate --size 100m --snapshot --name snap /dev/vg00/lvol1
sub createsnapshot {
    my $self = shift;
    my ($olv,$slv,$size) = my %p = @_;

    if( $p{'olv'} || $p{'slv'} || $p{'size'} ){
        $olv = $p{'olv'};
        $slv = $p{'slv'};
        $size = $p{'size'};
    }

    $self->loaddiskdev();

    if( my $LV = $self->getlv( 'device'=>$olv, %p ) ){
        $olv = $LV->{'device'};
        # TODO
        #   this can block process...
        my ($e,$m);
        unless( ( ($e,$m) = cmd_exec("/usr/sbin/lvcreate","--size",$size,"--snapshot","--name",$slv,$olv) ) && ( $e == 0 ) ){
            return retErr("_ERR_CREATE_SNAPSHOT_","Error creating snapshot: $m");
        }

        $self->loaddiskdev(1);  # update disk device info
    } else {
        return retErr('_INVALID_LOG_VOL_',"Invalid logical volume: $olv");
    }
    # TODO change this
    return retOk("_OK_","ok");
}

=item convertsnapshot

convert to a snapshot

    my $OK = VirtAgent::Disk->convertsnapshot( olv=>$lv, sl=>$snapshot );

=cut

# convertsnapshot
#   convert logical volume to snapshot of another logical volume
#
#   args: olv,slv
#   res: ok || Error
# e.g. lvconvert -s vg00/lvol1 vg00/lvol2
sub convertsnapshot {
    my $self = shift;
    my ($olv,$slv) = my %p = @_;

    if( $p{'olv'} || $p{'slv'} ){
        $olv = $p{'olv'};
        $slv = $p{'slv'};
    }

    $self->loaddiskdev();

    if( my $LV = $self->getlv( 'device'=>$olv, %p ) ){
        $olv = $LV->{'device'};
        # TODO
        #   this can block process...
        my ($e,$m);
        unless( ( ($e,$m) = cmd_exec("/usr/sbin/lvconvert --snapshot",$olv,$slv) ) && ( $e == 0 ) ){
            return retErr("_ERR_CONVERT_SNAPSHOT_","Error convert snapshot: $m");
        }

        $self->loaddiskdev(1);  # update disk device info
    } else {
        return retErr('_INVALID_LOG_VOL_',"Invalid logical volume: $olv");
    }
    # TODO change this
    return retOk("_OK_","ok");
}

# havemultipath
#   testing multipath support
#
sub havemultipath {
    if( not defined $HAVEMULTIPATH ){
        my ($e,$m) = cmd_exec("echo 'show maps' | /sbin/multipathd -k");
        if( $e == 0 && ( $m ne 'multipathd> multipathd> ' ) ){
            $HAVEMULTIPATH = 1;
        } else {
            $HAVEMULTIPATH = 0;
        }
    }
    return $HAVEMULTIPATH;
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

