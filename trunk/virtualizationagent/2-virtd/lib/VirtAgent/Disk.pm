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

use Cwd qw(abs_path);

# libparted required for get partitions info
require parted;

my $CONF;

use constant MINOR_MASK     => 037774000377;
use constant MINOR_SHIFT    => 0000000;
use constant MAJOR_MASK     => 03777400;
use constant MAJOR_SHIFT    => 0000010;

my $MIN_SIZE_DISK = 1024 * 1024;    # min disk size 1M

my $LIMIT_SIZE_DISK_PERC = 0.999;    # limit size disk percentage of disk free

my $san_config_file = $ENV{'san_conf_file'} || "/etc/sysconfig/etva-vdaemon/san_file.conf";

my $DEFAULT_TIMEOUT_SECONDS = 300;      # default value of timeout for timeout command
my $BULK_QEMU_IMG_INFO_TIMEOUT = 10;    # timeout value for bulk qemu_img command
my $SCSI_ID_TIMEOUT = 10;               # timeout value for scsi_id command

my $sysblock_path = "/sys/block";

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

# SAN Devices information
my %SANDev = ();

# All Disk Devices info
my %AllDiskDevices = ();

# Multipath support flag
my $HAVEMULTIPATH;

# Revert snapshots support
my $HAVEREVERTSNAPSHOTSUPPORT;

# flag mark libparted updated
my $LIBPARTED_UPDATED = 0;

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

sub getpvs_arr {
    my $self = shift;

    my @pvs = values %{$self->getpvs(@_)};
    return wantarray() ? @pvs : \@pvs;
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
    } elsif( defined($p{'major'}) && defined($p{'minor'}) ){
        ($PD) = grep { ( $_->{'major'} == $p{'major'} ) && ( $_->{'minor'} == $p{'minor'} ) } values %PhyDevices;
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

sub getvgs_arr {
    my $self = shift;

    my @vgs = values %{$self->getvgs(@_)};
    return wantarray() ? @vgs : \@vgs;
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

sub getlvs_arr {
    my $self = shift;

    my @lvs = values %{$self->getlvs(@_)};
    return wantarray() ? @lvs : \@lvs;
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
        #lvinfo();
        # activate inactive logical volumes
        activate_lvs();
        # update LV info
        lvinfo();
    }

    # multipath map info
    if( $force || !%PathMaps ){ pathmapsinfo(@_); }

    if( $force || !%SANDev ){ sandevconf(); }

    if( $force || !%MountDev ){ mountdev(); }

    # get info from libparted
    if( $force || !$LIBPARTED_UPDATED ){ libparted_phydevinfo(); }
    

    %PhyDisk = () if( $force );
    %DiskDevices = () if( $force );

    # update devices with aditional info
    if( $force || !%DiskDevices ){ update_devices(); }

    my %res = ( devices => \%PhyDevices, PV => \%PVInfo, VG => \%VGInfo, LV => \%LVInfo, MultiPath => \%PathMaps );
    return wantarray ? %res : \%res;
}

# get uuid from scsi
sub scsi_id_uuid{
    my ($name) = @_;

    my $blockdev = "/block/$name";
    my $cmd = "/sbin/scsi_id -p 0x83 -g -s $blockdev"; 
    my ($release,$version) = &get_os_release();
    if( $release eq 'Fedora' ){
        my $device = "/dev/$name";
        $cmd = "/usr/lib/udev/scsi_id --page=0x83 --whitelisted --device=$device"; 
    } elsif( (($release eq 'CentOS') && (int($version) >= 6)) ||
            (($release =~ m/Red Hat/) && (int($version) >= 6)) ||
            (($release eq 'ETVA') && (int($version) >= 6)) ||
            (($release eq 'Nuxis') && (int($version) >= 6)) ){
        my $device = "/dev/$name";
        $cmd = "/lib/udev/scsi_id --page=0x83 --whitelisted --device=$device"; 
    }

    my $timeout_cmd = &timeout_cmd($SCSI_ID_TIMEOUT,1) . " " . $cmd;

    plog(" DEBUG scsi_id_uuid cmd=$timeout_cmd release=$release version=$version") if( &debug_level > 7 );

    # get blockid (uuid)
    open(SCSIID_FH,"$timeout_cmd |"); 
    my $uuid = <SCSIID_FH>;    # read one single line
    chomp($uuid);
    close(SCSIID_FH);

    return $uuid;
}
sub get_device_uuid {
    return &scsi_id_uuid(@_);
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

        # get blockid (uuid)
        my $uuid = &get_device_uuid($name);

        # only if have universal uniq id
        if( $uuid ){
            $PhyDev{"uuid"} = $uuid if( $uuid);
        }

        for my $k (qw(size freesize)){
            # pretty string for size field
            $PhyDev{"pretty_${k}"} = prettysize($PhyDev{"$k"});
        }

        if( -e "$sysblock_path/$name/dm/name" ){
            open(DMFH,"$sysblock_path/$name/dm/name");
            my ($aliasname) = <DMFH>;
            chomp($aliasname);
            close(DMFH);
            $PhyDev{'aliasname'} = $aliasname;
        }

        $PhyDevices{"$name"} = \%PhyDev;
    }
    
    close(F);

    
    return wantarray() ? %PhyDevices: \%PhyDevices;
}

# check if device is a multipath
sub check_device_is_mpath {
    my ($D) = @_;

    if( my $uuid = &isDevicePathFromMultipath($D) ){
        return 1;
    }
    return 0;
}
# check if device is a ghost device from multipath
sub check_device_is_ghost_device {
    my ($D) = @_;

    if( my $uuid = &isDevicePathFromMultipath($D) ){
        if($PathMaps{"$uuid"}{sysfs} ne $D->{'name'}){
            if( my ($MPD) = grep { $_->{'devnode'} eq $D->{'name'} } @{$PathMaps{"$uuid"}{'devices'}} ){
                if( $MPD->{"path_status"} eq 'ghost' ){
                    plogNow( "[DEBUG] check_device_is_ghost_device device=$D->{'device'} ($uuid) ignored." );
                    return 1;
                }
            }
        }
    }
    return 0;
}
sub check_device_is_media_type {
    my ($D) = @_;
    my $cdn = $D->{'name'};
    $cdn =~ s/\//!/g;
    my $blockdir = "/sys/block/$cdn";

    # detect media is readonly devices
    if( -e "$blockdir/ro" ){
        open(FRO,"$blockdir/ro");
        my $ro = <FRO>;
        close(FRO);
        if( int($ro) ){
            plogNow( "[DEBUG] check_device_is_media_type device=$D->{'device'} ignored: (cause is ro)." );
            return 1;
        }
    }

    # detect media changeable/ejectable devices like CD-ROM
    if( -e "$blockdir/events" ){
        open(BDE,"$blockdir/events");
        my $events = <BDE>;
        close(BDE);
        if( $events =~ m/media_change/gs ){
            plogNow( "[DEBUG] check_device_is_media_type device=$D->{'device'} ignored: (cause is media changeable)." );
            return 1;
        }
        if( $events =~ m/eject/gs ){
            plogNow( "[DEBUG] check_device_is_media_type device=$D->{'device'} ignored: (cause is ejectable)." );
            return 1;
        }
    }
    return 0;
}
sub libparted_ignoredevice {
    if( &check_device_is_ghost_device(@_) ){	# ignore multipath devices
        return 1;
    }
    if( &check_device_is_media_type(@_) ){
        return 1;
    }
    return 0;
}
sub libparted_phydevinfo {

    plogNow( "[info] libparted_phydevinfo go update..." );

    if( -e "/proc/partitions" &&
        %PhyDevices ){
        # probe all device from /proc/partitions
        for my $dn (keys %PhyDevices){
            my $D = $PhyDevices{"$dn"};
            if( $D->{'device'} ){

                next if( &libparted_ignoredevice($D) );	# ignore some devices

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
        parted::device_free_all();
    }

    $LIBPARTED_UPDATED = 1;	# mark libparted as updated
}

# lookup_partition_phydevice - get phydevice for partition
sub lookup_partition_phydevice {
    my ($Dev,$ndev,$i) = @_;
    my $pndev = (($ndev =~ m/^dm-/) || ($ndev =~ m/\d+$/)) ? "$ndev" ."p". "$i" : "$ndev" . "$i";

    my $PDev = $PhyDevices{"$pndev"};   # get direct from device
    if( !$PDev ){
        if( $Dev->{'aliasname'} ){      # get from alias name
            my $pnaliasname = (($ndev =~ m/^dm-/) || ($ndev =~ m/\d+$/)) ? "$Dev->{'aliasname'}" ."p". "$i" : "$Dev->{'aliasname'}" . "$i";
            ($PDev) = grep { ( $_->{'name'} eq "$pnaliasname" ) || ( $_->{'aliasname'} eq "$pnaliasname" ) } values %PhyDevices;
        }
    }

    return $PDev;
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
                            if( my $PDev = &lookup_partition_phydevice($PhyDevices{$ndev},$ndev,$i) ){
                                my $pndev = $PDev->{'name'};

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
                                $PhyDevices{$pndev}{'swap'} = 1 if( $fs_type =~ m/linux-swap/ );

                                # is RAID partition
                                my $is_raid = $part->get_flag(parted::partition_flag_get_by_name('raid'));
                                $PhyDevices{$pndev}{'raid'} = 1 if( $is_raid );

                            } 
                        }
                    }
                    if( $PhyDevices{$ndev} ){
                        if( $lpi > 0 ){
                            # partitioned flag
                            $PhyDevices{$ndev}{'partitioned'} = 1;
                        } else {
                            # no partitions flag
                            $PhyDevices{$ndev}{'nopartitions'} = 1;
                        }
                        # disk device flag
                        $PhyDevices{$ndev}{'diskdevice'} = 1;
                    }
                }
            }
            $dev->close();
        }
    }
}

# read out put of multipath
sub read_multipathd {
    my ($cmd) = @_;
    my @lines = ();
    open(READ_MULTIPATHD,"echo \"$cmd\" | /sbin/multipathd -k |");
    while(<READ_MULTIPATHD>){
        chomp;
        s/multipathd> //;   # clean multipathd
        s/$cmd//;   # clean command
        push(@lines,$_) if( $_ );
    }
    close(READ_MULTIPATHD);
    return wantarray() ? @lines : \@lines;
}

# multipath maps info
sub pathmapsinfo {

    # testing multipath
    if( &havemultipath(@_) ){

        %PathMaps = ();

        # get info from multipathd
        # TODO must be active
        my @mlines = &read_multipathd("show maps");
        my $hmap = shift(@mlines);
        my @hf = split(/\s+/,trim($hmap));

        if( scalar(@hf) < 3 ){ # 2nd line
            $hmap = shift(@mlines);
            @hf = split(/\s+/,trim($hmap));
        }

        foreach(@mlines){
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

        # get devices from topology
        my @mlines_t = &read_multipathd("show maps topology");
#multipathd> mpath0 (3600a0b800050817400000bab4cc017ec) dm-2  IBM,1814      FAStT
#[size=100G][features=0       ][hwhandler=1 rdac   ][rw        ]
#\_ round-robin 0 [prio=100][enabled]
# \_ 6:0:0:1 sdd 8:48  [active][ready] 
#\_ round-robin 0 [prio=0][enabled]
# \_ 5:0:0:1 sdb 8:16  [active][faulty]
# \_ 5:0:1:1 sdc 8:32  [active][ghost] 
# \_ 6:0:1:1 sde 8:64  [active][ghost] 
#multipathd> 


#multipathd> mpath3 (3600a0b8000320d5e00001a834f7bdc49) dm-0 IBM,1814      FAStT
#size=169G features='1 queue_if_no_path' hwhandler='1 rdac' wp=rw
#|-+- policy='round-robin 0' prio=6 status=active
#| |- 5:0:0:2 sdf 8:80  active ready running
#| `- 4:0:0:2 sdc 8:32  active ready running
#`-+- policy='round-robin 0' prio=1 status=enabled
#  |- 5:0:1:2 sdi 8:128 active ghost running
#  `- 4:0:1:2 sdh 8:112 active ghost running
#mpath2 (3600a0b8000320d5e00001a804f7bdc31) dm-1 IBM,1814      FAStT
#size=110G features='1 queue_if_no_path' hwhandler='1 rdac' wp=rw
#|-+- policy='round-robin 0' prio=6 status=active
#| |- 5:0:0:1 sde 8:64  active ready running
#| `- 4:0:0:1 sdb 8:16  active ready running
#`-+- policy='round-robin 0' prio=1 status=enabled
#  |- 5:0:1:1 sdg 8:96  active ghost running
#  `- 4:0:1:1 sdd 8:48  active ghost running
#multipathd>

        my $p_uuid;
        foreach(@mlines_t){
            if( /(\w+)\s+\((\w+)\)\s+(\S+)\s+(\S+)(\s+(\S+))?/ ){
                #mpath0 (3600a0b800050817400000bab4cc017ec) dm-2  IBM,1814      FAStT
                #create: mpath0 (36000eb3b55860573000000000000002d) dm-2  LEFTHAND,iSCSIDisk
                my ($n,$i,$d,$v,$m) = ($1,$2,$3,$4,$6);
                $p_uuid = $i;   # mark process
                if( !$PathMaps{"$p_uuid"} ){
                    $PathMaps{"$p_uuid"} = { 'uuid'=>"$p_uuid", 'name'=>$n, 'device'=>"/dev/mapper/$n" };
                }
                $PathMaps{"$p_uuid"}{'vendor'} = $v;
                $PathMaps{"$p_uuid"}{'model'} = $m;
            } elsif( /^\[size=([^\]]+)\[features=([^\]]+)\]\[hwhandler=([^\]]+)\]\[([^\]]+)\]$/ ||
                        /^size=(\S+)\s+features='?([^']+)'\s+hwhandler='?([^']+)'\s+wp=(\S+)$/ ){
                #[size=100G][features=0       ][hwhandler=1 rdac   ][rw        ]
                #size=110G features='1 queue_if_no_path' hwhandler='1 rdac' wp=rw
                if( $p_uuid && $PathMaps{"$p_uuid"} ){
                    ( $PathMaps{"$p_uuid"}{'size'}, $PathMaps{"$p_uuid"}{'features'},
                       $PathMaps{"$p_uuid"}{'hwhandler'},$PathMaps{"$p_uuid"}{'permissions'})
                                = ($1,$2,$3,$4);
                }
            } elsif( /^\\_\s+(\S+)\s+(\d+)\s+\[(\S+)\]\[(\w+)\]$/ ||
                        /^\|-\+-\s+policy='(\S+)\s+(\d+)'\s+(\S+)\s+status=(\w+)$/ ||
                        /^\`-\+-\s+policy='(\S+)\s+(\d+)'\s+(\S+)\s+status=(\w+)$/ ){
                #\_ round-robin 0 [prio=100][enabled]
                #|-+- policy='round-robin 0' prio=6 status=active
                #`-+- policy='round-robin 0' prio=1 status=enabled
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
            } elsif( /^\s+\\_\s+(\d+):(\d+):(\d+):(\d+)\s+(\w+)\s+(\d+):(\d+)\s+\[(\w+)\]\[(\w+)\]\s*$/ ||
                        /^\s+\\|-\s+(\d+):(\d+):(\d+):(\d+)\s+(\w+)\s+(\d+):(\d+)\s+(\w+)\s+(\w+)/ ||
                        /^\s+\\`-\s+(\d+):(\d+):(\d+):(\d+)\s+(\w+)\s+(\d+):(\d+)\s+(\w+)\s+(\w+)/ ){
                # \_ 6:0:0:1 sdd 8:48  [active][ready] 
                #  |- 5:0:1:1 sdg 8:96  active ghost running
                #  `- 4:0:1:1 sdd 8:48  active ghost running
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
    }
    return wantarray() ? %PathMaps : \%PathMaps;
}

# aux func for get block device info
sub info_majorminor {
    my ($major,$minor) = @_;

    if( -r "/proc/partitions" ){
        open(PF,"/proc/partitions");
        while(<PF>){
            if( /^\s+$major\s+$minor\s+(\d+)\s+(\S+)$/ ){
                my ($blockn,$name) = ($1,$2);
                my $size = $blockn * 1024; # in bytes
                return ($major,$minor,$blockn,$name,$size);
            }
        }
        close(PF);
    }
    return;
}
sub info_blockdev {
    my ($dev) = @_;
    if( -l "$dev" ){
        $dev = abs_path($dev);
    }
    if( -b "$dev" ){
        my ($rdev) = (lstat $dev)[6];
        my $major = ( $rdev & MAJOR_MASK ) >> MAJOR_SHIFT;
        my $minor = ( $rdev & MINOR_MASK ) >> MINOR_SHIFT;
        if( my @info = &info_majorminor($major,$minor) ){
            return ($dev,@info);
        }
    }
    return;
}

sub sandevconf {
    %SANDev = ();

    if( -e "$san_config_file" ){
        open(SCF,"$san_config_file");
        while(<SCF>){
            chomp;
            if( /^(\S+)/ ){
                my ($dev) = ($1);
                if( my @dc = &info_blockdev($dev) ){   # get device info from /proc/partitions
                    my %D = ( 'device'=>$dc[0], 'major'=>$dc[1], 'minor'=>$dc[2], 'blocks'=>$dc[3], 'name'=>$dc[4], 'size'=>$dc[5], 'type'=>'SAN' );
                    $D{'alias'} = $dev if( $dev ne $D{'device'} );
                    $SANDev{"$D{'name'}"} = \%D;
                }
            }
        }
        close(SCF);
    }

    return wantarray() ? %SANDev : \%SANDev;
}

# Physical volumes info
sub pvinfo {
    my ($force) = @_;

    if( $force || !%PVInfo ){
        my $debug_opt = ""; # debug option
        $debug_opt = " -v" if( &debug_level > 9 );

        # force update pvs info
        cmd_exec("pvscan $debug_opt");
    }

    %PVInfo = ( );

    my $opts = "pv_fmt,pv_uuid,pv_size,dev_size,pv_free,pv_used,pv_name,pv_attr,pv_pe_count,pv_pe_alloc_count,pv_tags,vg_name";
    open(I,"pvs --separator=';' --units=b --noheadings --options=$opts 2>/dev/null|");

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
        $H{"aliasdevice"} = $H{"device"} = $device;

        next if( $device eq "unknown device" );

        # for symbolic links resolve them
        $device = abs_path($device) if( -l $device );

        my @p = ( $device =~ m/\/mapper\// ) ? split(/\//,$device)
						:split(/\//,$device,3);
        my $pv = $H{'name'} = pop @p;   # write right name
        $H{"device"} = $device;

        # get blockid (uuid)
        my $uuid = &get_device_uuid($pv);

        # grant this fields
        $H{"psize"} ||= $H{"pv_size"};
        $H{"pfree"} ||= $H{"pv_free"};
        $H{'attr'} ||= $H{'pv_attr'};
        $H{'uuid'} ||= $uuid || $H{'pv_uuid'};

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
    my ($force) = @_;

    if( $force || !%VGInfo ){
        my $debug_opt = ""; # debug option
        $debug_opt = " -v" if( &debug_level > 9 );

        # force update vgs info
        cmd_exec("vgscan $debug_opt");
    }

    %VGInfo = ();

    my $opts = "vg_fmt,vg_uuid,vg_name,vg_attr,vg_size,vg_free,vg_sysid,vg_extent_size,vg_extent_count,vg_free_count,max_lv,max_pv,pv_count,lv_count,snap_count,vg_seqno,vg_tags";
    open(I,"vgs --separator=';' --units=b --noheadings --options=$opts 2>/dev/null|");

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

        # get size and freesize from path of local storage
        my ($vgsize,$vgfreesize) = get_size_path( $CONF->{'storagedir'} );

        my $DISKDevices = &local_storagedir_info($CONF->{'storagedir'});

        # calc total size of device on local storage dir
        my $totalsize = 0;
        for my $H (@$DISKDevices){
            $totalsize += $H->{'size'};
        }

        # calc free size
        my $tmp_vgfreesize = ( $vgfreesize > $totalsize ) ? $vgsize - $totalsize : $vgfreesize;
        $vgfreesize = ( $vgfreesize > $tmp_vgfreesize ) ? $tmp_vgfreesize : $vgfreesize;

        $H{"lsize"} = $H{"size"} = $vgsize;
        $H{"lfree"} = $H{"freesize"} = $vgfreesize;
        for my $k (qw(size freesize)){
            # pretty string for size field
            $H{"pretty_${k}"} = prettysize($H{"$k"});
        }

=cmt # bug on vgfreesize
        # fix freesize
        if( %LVInfo ){
            my $new_vgfreesize = $vgfreesize;
            my @storageDir_lvs = grep { $_->{'vg'} eq $vg } values %LVInfo;
            for my $LV (@storageDir_lvs){
                $new_vgfreesize -= $LV->{'size'};
            }
            $H{"lfree"} = $H{"freesize"} = $new_vgfreesize;
            $H{"pretty_freesize"} = prettysize($new_vgfreesize);
        }
=cut
    }

    return wantarray() ? %VGInfo : \%VGInfo;
}
# Logical volumes info
sub lvinfo {
    my ($force) = @_;

    if( $force || !%LVInfo ){
        my $debug_opt = ""; # debug option
        $debug_opt = " -v" if( &debug_level > 3 );

        # force update lvs info
        cmd_exec("lvscan $debug_opt");
    }

    my $LVDevices = [];

    %LVInfo = ( );

    #my $opts = "lv_uuid,lv_name,lv_attr,lv_major,lv_minor,lv_kernel_major,lv_kernel_minor,lv_size,seg_count,origin,snap_percent,copy_percent,move_pv,lv_tags,segtype,stripes,stripesize,chunksize,seg_start,seg_size,seg_tags,devices,regionsize,mirror_log,modules,convert_lv,vg_name";
    my $opts = "lv_uuid,lv_name,vg_name,lv_attr,lv_major,lv_minor,lv_kernel_major,lv_kernel_minor,lv_size,seg_count,origin";
    open(I,"lvs --separator=';' --units=b --noheadings --options=$opts 2>/dev/null|");

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
        $H{"device"} = abs_path($H{"device"}) if( -l $H{"device"} );

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
		$H{'volumetype'} = 'invalid   snapshot' if( $v eq 'S' );
		$H{'volumetype'} = 'virtual' if( $v eq 'v' );
		$H{'volumetype'} = 'mirror image' if( $v eq 'i' );
		$H{'volumetype'} = 'mirror image out-of-sync' if( $v eq 'I' );
		$H{'volumetype'} = 'under conversion' if( $v eq 'c' );
		$H{'volumetype'} = 'snapshot' if( $v eq 's' );
		$H{'snapshot'} = 1 if( $v eq 's' );
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
        $H{'format'} = 'raw';   # by default format is raw

        $H{'deviceopen'} = 1 if( $d eq 'o' );

        $H{"logical"} = $H{"logicalvolume"} = 1;          # mark as logical volume

        for my $k (qw(size freesize)){
            # pretty string for size field
            $H{"pretty_${k}"} = prettysize($H{"$k"});
        }
        my $rH = \%H;
        push(@$LVDevices,$rH);
        $LVInfo{"$lv"} = $rH;
    }
    close(I);

    $LVDevices = &qemu_img_info_bulk($LVDevices);

    if( $CONF->{'storagedir'} ){

        my $DISKDevices = &local_storagedir_info($CONF->{'storagedir'});

        for my $H (@$DISKDevices){
            my $lv = $H->{"lv"};
            $LVInfo{"$lv"} = $H;
        }
    }

    return wantarray() ? %LVInfo: \%LVInfo;
}

sub local_storagedir_info {
    my ($storagedir) = @_;

    my $DISKDevices = [];

    if( -d "$storagedir" ){
        opendir(D,$storagedir);
        my @l = readdir(D);
        for my $f (@l){
            next if( $f =~ m/^\./ );

            my $path = "${storagedir}/$f";

            # links or regular files only
            if( -f "$path" || -l "$path" ){

                my %H = ( 'filedisk'=>1, 'type'=>'local' );

                my $vg = $H{"vg"} = $H{"vg_name"} = '__DISK__';
                my $lv = $H{"name"} = $H{"lv"} = $H{"lv_name"} = $f;

                $H{"lvdevice"} = $H{"aliasdevice"} = $H{"device"} = $path;
                # for symbolic links resolve them
                $H{"device"} = abs_path($H{"device"}) if( -l $H{"device"} );
                $H{'writeable'} = 1;

                $H{"logical"} = $H{"logicalvolume"} = 1;          # mark as logical volume
                my ($PD) = grep { $_->{'loopdevice'} && ($_->{'loopfile'} eq $path) } values %PhyDevices;
                if( $PD ){
                    $H{'deviceopen'} = 1;
                    $H{'loopdevice'} = $PD->{'device'};
                    $H{'devicename'} = $PD->{'name'};
                }

                my $rH = \%H;
                push(@$DISKDevices,$rH);
                # TODO list snapshots
            }
        }
        closedir(D);

        $DISKDevices = &lvinfo_files_size_bulk($DISKDevices);
        $DISKDevices = &qemu_img_info_bulk($DISKDevices);
    }

    return wantarray() ? @$DISKDevices : $DISKDevices;
}

# timeout_cmd
sub timeout_cmd {
    my ($ts,$force) = @_;

    my $args = "";
    $ts = $DEFAULT_TIMEOUT_SECONDS if( !$ts );
    $args = "--signal=SIGKILL" if( $force );
    
    return "timeout $args $ts";
}

# qemu_img_cmd : return qemu-img command path
sub qemu_img_cmd {
    return "/usr/bin/qemu-img";
}
# have_qemu_img : check if qemu-img command available
sub have_qemu_img {
    return ( -x &qemu_img_cmd() ) ? 1 : 0;
}
# have_qemu_img_resize_support : check if qemu-img support resize disks
my $have_qemu_img_resize_support;
sub have_qemu_img_resize_support {
    my ($force) = @_;
    if( $force || !defined($have_qemu_img_resize_support) ){
        my $qemu_img_cmd = &qemu_img_cmd();
        my ($e,$m) = cmd_exec("$qemu_img_cmd");
        $have_qemu_img_resize_support = ( $m =~ m/resize/gs ) ? 1 : 0;
    }
    return $have_qemu_img_resize_support;
}

# qemu_img_info : get disk info from qemu-img
sub qemu_img_info {
    my ($D) = @_;
    ($D) = &qemu_img_info_bulk([ $D ]);
    return $D;
}

sub qemu_img_info_parseline {
    my ($l,$D) = @_;

    my ($k,$v) = split(/:/,$l,2);
    my $nk = $k;
    my $nv = trim($v);
    $nk =~ s/\s/_/g;
    if( $nk eq 'file_format' ){
        if( !$D->{'format'} || (($nv ne 'host_device') && ($nv ne 'raw'))){ # ignore host_device and raw formats
            $D->{"format"} = $nv;
        }
    } elsif( $nk eq 'virtual_size' ){
        my ($ps,$s,$ts) = ( $nv =~ m/(\S+) \((\S+) (\w+)\)/ );
        # fix size calc for all disk formats
        $D->{'pretty_virtual_size'} = $ps;
        $D->{'virtual_size'} = str2size("$s$ts");
        if( !-b "$D->{'device'}" ){
            if( 1 || !$D->{'size'} ){   # force to update size
                $D->{'pretty_size'} = $D->{'pretty_virtual_size'};
                $D->{'lsize'} = $D->{'size'} = $D->{'virtual_size'};
            }
        }
    } elsif( $nk eq 'disk_size' ){
        $D->{'pretty_disk_size'} = $nv;
        $D->{'disk_size'} = str2size($nv);

        if( !-b "$D->{'device'}" ){
            if( $D->{'disk_size'} > $D->{'size'} ){ # if disk_size is great of size
                $D->{'pretty_size'} = $D->{'pretty_disk_size'};
                $D->{'lsize'} = $D->{'size'} = $D->{'disk_size'};
            }
        }
    }
    $D->{"$nk"} ||= $nv;

    return $D;
}
sub qemu_img_info_formatsize {
    my ($D) = @_;
    if( !-b "$D->{'device'}" ){
        if( $D->{'disk_size'} && $D->{'virtual_size'} ){
            if( !$D->{'freesize'} ){
                $D->{'lfree'} = $D->{'freesize'} = ($D->{'virtual_size'} - $D->{'disk_size'});
                $D->{"pretty_freesize"} = prettysize($D->{'freesize'});
            }
        }
    }
    return $D;
}

sub qemu_img_info_bulk_cmd {
    my ($L) = @_;
    my $blk_cmd = "";
    for my $D (@$L){
        # ignore none active volumes
        next if( defined($D->{'state'}) && ($D->{'state'} ne 'a') );

        # call qemu-img info with timeout
        $blk_cmd .= &timeout_cmd($BULK_QEMU_IMG_INFO_TIMEOUT,1) . " " . &qemu_img_cmd() . " info $D->{'device'};" . "\n";
    }
    plog( " qemu_img_info_bulk_cmd=$blk_cmd ") if( &debug_level > 7 );
    my @lines = ();
    if( $blk_cmd ){
        open(P,"( $blk_cmd )|");
        @lines = <P>;
        close(P);
    }

    return wantarray() ? @lines : \@lines;
}

sub qemu_img_info_bulk {
    my ($L) = @_;
    if( &have_qemu_img() ){
        my @lines = &qemu_img_info_bulk_cmd($L);
        for my $D (@$L){
            my $go_process = 0;
            for( my $i=0; $i<scalar(@lines); $i++ ){
                my $l = $lines[$i];
                if( $l =~ m#^image: $D->{'device'}$# ){
                    $go_process = 1;
                    next;
                }
                if( $go_process ){
                    last if( $l =~ m/image:/ );             # is next image
                    plog($l) if( &debug_level > 9 );
                    if( $l =~ m/Snapshot list:/ ){     # go to snapshot list
                        # TODO process snapshots list
                        $D->{'has_snapshots'} = 1;
                        my $c = $i;
                        $c++; # ignore next row
                        # 1         win2k3-teste1          447M 2012-12-14 12:30:47  381:22:43.296
                        for( $c++; $c<scalar(@lines); $c++ ){
                            $l = $lines[$c];
                            if( $l =~ m/^\d+\s+.+\s+(\d+(.\d+)?\w?)\s+\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\s+\d+:\d+:\d+\.\d+$/ ){
                                my $s_size = $1;
                                $D->{'size_of_snapshots'} += str2size($s_size);
                            } else {
                                last;
                            }
                        }
                        plog( "qemu_img_info_bulk device=$D->{device} has_snapshots=$D->{has_snapshots} size_of_snapshots=$D->{size_of_snapshots}" );
                        last;
                    }
                    $D = &qemu_img_info_parseline($l,$D);
                }
            }
            $D = &qemu_img_info_formatsize($D);
        }
    }
    return wantarray() ? @$L : $L;
}

# lvinfo_files_size_bulk: get size info for list of lv files
sub lvinfo_files_size_bulk_cmd {
    my ($L) = @_;

    my $blk_cmd = "";
    for my $D (@$L){
        $blk_cmd .= "echo -n \"usagesize=\"; /usr/bin/du -s -B1 $D->{'device'} 2>/dev/null;" . "\n";
        $blk_cmd .= "echo -n \"apparentsize=\"; /usr/bin/du -s -B1 --apparent-size $D->{'device'} 2>/dev/null;"."\n";
    }
    plog( "lvinfo_files_size_bulk_cmd=$blk_cmd" ) if( &debug_level > 7 );
    my %devs = ();
    if( $blk_cmd ){
        open(P,"( $blk_cmd )|");
        while(<P>){
            chomp;
            my ($ts,$p) = split(/\s+/,$_);
            my ($t,$s) = split(/=/,$ts);
            $devs{"$p"}{'device'} = $p;
            $devs{"$p"}{"$t"} = $s;
        }
        close(P);
    }

    my @lines = values %devs;
    return wantarray() ? @lines : \@lines;
}

sub lvinfo_files_size_bulk {
    my ($L) = @_;

    my @lines = &lvinfo_files_size_bulk_cmd($L);
    for my $D (@$L){
        my ($SD) = grep { $_->{'device'} eq $D->{'device'} } @lines;
        if( $SD ){
            my $usize = $SD->{'usagesize'};
            my $asize = $SD->{'apparentsize'};
            $D->{"lsize"} = $D->{"size"} = $asize;
            $D->{"lfree"} = $D->{"freesize"} = ($asize > $usize) ? $asize - $usize : 0;
            for my $k (qw(size freesize)){
                # pretty string for size field
                $D->{"pretty_${k}"} = prettysize($D->{"$k"});
            }
        }
    }

    return wantarray() ? @$L : $L;
}

sub activate_lvs {

    # update LV info
    lvinfo(1);

    # activate all lvs
    for my $L (values %LVInfo){
        if( $L->{'state'} eq '-' ){
            # if not active then activate it
            my $lvdevice = $L->{'lvdevice'} || $L->{'device'};
            my ($e,$m) = cmd_exec("lvchange","-ay",$lvdevice);
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
        my $k = my $device = $M{"device"};
        $device = abs_path($device) if( -l $device );
        $MountDev{"$k"} = $MountDev{"$device"} = \%M;
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
        my $k = my $device = $M{"device"};
        $device = abs_path($device) if( -l $device );
        $MountDev{"$k"} = $MountDev{"$device"} = \%M;
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

    if( my @dc = &info_blockdev($dev) ){   # get device info from /proc/partitions
        return $dc[5];
    }
    return 0;
}

# check if mountpoint is mount as bind
sub is_mount_bind {
    my ($M) = @_;
    if( $M->{'fs'} eq 'none' ){
        if( grep { $_ eq 'bind' } split(/,/,$M->{'options'}) ){
            return 1;
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
        if( &is_mount_bind($M) ){    # if mount as bind
            # get mountpoint of device
            if( my $A = chk_path_mount($M->{'device'}) ){
                $M = $A;
            }
        }
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

# get disk size and usage size
sub get_file_disk_size {
    my ($path) = @_;
    $path = abs_path($path) if( -l "$path" );   # get real path if link 
    my $D = qemu_img_info( { 'device'=>"$path" } );
    return ($D->{'virtual_size'},$D->{'disk_size'});
}
sub get_disk_size {
    my ($path) = @_;
    $path = abs_path($path) if( -l "$path" );   # get real path if link 
    if( -b "$path" ){
        if( my $LV = __PACKAGE__->getlv( 'device'=> $path ) ){
            return $LV->{'size'}
        } else {
            return &size_blockdev($path);
        }
    } else {
        my ($vsize,$usize) = &get_file_disk_size($path);

        return $usize if( $usize > $vsize );    # for small disks when have snapshots usize could be great then vsize
        return $vsize;
    }
}
sub get_disk_usagesize {
    my ($path) = @_;
    $path = abs_path($path) if( -l "$path" );   # get real path if link 
    if( -b "$path" ){
        return &get_disk_size($path);
    } else {
        my ($vsize,$usize) = &get_file_disk_size($path);
        return $usize;
    }
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
            $AllDiskDevices{"$dn"}{"type"} = "SAN";    # type SAN
            $AllDiskDevices{"$dn"}{"multipath"} = 1;    # mark as using multipath
            $AllDiskDevices{"$dn"}{"devmapper"} = $PathMaps{"$uuid"}{"device"};
            $AllDiskDevices{"$dn"}{"multipathname"} = $PathMaps{"$uuid"}{"name"};
            if($PathMaps{"$uuid"}{sysfs} ne $AllDiskDevices{"$dn"}{'name'}){
                
                $AllDiskDevices{"$dn"}{"mpathdevice"} = 1;
                if( grep { $_->{'devnode'} eq $AllDiskDevices{"$dn"}{'name'} } @{$PathMaps{"$uuid"}{'devices'}} ){
                    push(@{$PathMaps{"$uuid"}{"phydevices"}},$AllDiskDevices{"$dn"});
                }
            }
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
            $AllDiskDevices{"$dn"}{"lvm"} = 1;
        }
        my $dev = $AllDiskDevices{"$dn"}{'device'};
        if( $MountDev{"$dev"} ){
            $AllDiskDevices{"$dn"}{"mounted"} = 1;
            $AllDiskDevices{"$dn"}{"mountpoint"} = $MountDev{"$dev"}{'mountpoint'};
            $AllDiskDevices{"$dn"}{"fs"} = $MountDev{"$dev"}{'fs'};
        }

        my $cdn = $dn;
        $cdn =~ s/\//!/g;
        my $blockdir = "/sys/block/$cdn";
        if( -d "$blockdir" ){
            opendir(D,$blockdir);
            my @dparts = grep { /$cdn/ } readdir(D);
            close(D);
            if( scalar(@dparts) ){
                # have partitions
                $AllDiskDevices{"$dn"}{'partitioned'} = 1;
                my $alsize = 0;
                for my $par (@dparts){
                    my $cpar = $par;
                    $cpar =~ s/!/\//g;
                    $AllDiskDevices{"$cpar"}{"partition"} = 1;
                    $alsize += $AllDiskDevices{"$cpar"}{'size'};
                }
                $AllDiskDevices{"$dn"}{'freesize'} = $AllDiskDevices{"$dn"}{'size'} - $alsize;
                $AllDiskDevices{"$dn"}{'freesize'} = 0 if( $AllDiskDevices{"$dn"}{'freesize'} < 0 );
            }

            if( -e "$blockdir/ro" ){
                open(FRO,"$blockdir/ro");
                my $ro = <FRO>;
                close(FRO);
                $AllDiskDevices{"$dn"}{'readonly'} = int($ro) ? 1 : 0;
            }

            # detect media changeable/ejectable devices like CD-ROM
            if( -e "$blockdir/events" ){
                open(BDE,"$blockdir/events");
                my $events = <BDE>;
                close(BDE);
                $AllDiskDevices{"$dn"}{'media_changeable'} = ( $events =~ m/media_change/gs )? 1 : 0;
                $AllDiskDevices{"$dn"}{'ejectable'} = ( $events =~ m/eject/gs )? 1 : 0;
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
            $AllDiskDevices{"$name"}{"lvm"} = 1;
        } else {
            $AllDiskDevices{"$name"}{'isalias'} = 1;
            $AllDiskDevices{"$phydevm"}{'aliasdevice'} = $AllDiskDevices{"$name"}{"device"};
            if( $PVInfo{"$phydevm"} ){
                $PVInfo{"$phydevm"}{"type"} = "SAN";
                if( my $vg = $PVInfo{"$phydevm"}{'vg_name'} ){
                    $VGInfo{"$vg"}{'type'} = "SAN";
                }
                $AllDiskDevices{"$phydevm"}{"uuid"} = $PVInfo{"$phydevm"}{'uuid'} = $muid;
                $AllDiskDevices{"$phydevm"}{"lvm"} = 1;
                $PVInfo{"$phydevm"}{'aliasdevice'} = $AllDiskDevices{"$phydevm"}{'aliasdevice'} = $AllDiskDevices{"$name"}{"device"};
            }
        }
    }

    # mark devices with type SAN
    for my $sd (keys %SANDev){
        my $name = $SANDev{"$sd"}{'name'};
        my $alias = $SANDev{"$sd"}{'alias'};

        $AllDiskDevices{"$name"}{"type"} = "SAN";    # type SAN

        my $sandevice = $AllDiskDevices{"$name"}{"device"} = $SANDev{"$sd"}{'device'};

        if( $alias ){
            $AllDiskDevices{"$alias"}{"type"} = "SAN";    # type SAN
            $AllDiskDevices{"$alias"}{'uuid'} = $AllDiskDevices{"$name"}{"uuid"};

            $AllDiskDevices{"$name"} = { %{$AllDiskDevices{"$alias"}}, %{$AllDiskDevices{"$name"}} };
        }

        my $PV = $PVInfo{"$name"};
        ($PV) = grep { $_->{'device'} eq $sandevice } values %PVInfo;
        if( $PV ){
            if( $alias ){
                $AllDiskDevices{"$alias"}{'isalias'} = 1;
                $PV->{'aliasdevice'} = $AllDiskDevices{"$name"}{'aliasdevice'} = $AllDiskDevices{"$alias"}{"device"};
            }

            $PV->{"pvinit"} = 1;    # initialized

            $PV->{"type"} = "SAN";
            if( my $vg = $PV->{'vg_name'} ){
                $VGInfo{"$vg"}{'type'} = "SAN";
            }

            $PhyDevices{"$name"}{"pvinit"} = 1;    # initialized
            $AllDiskDevices{"$name"}{"pvinit"} = 1;    # initialized
            $AllDiskDevices{"$name"}{"allocatable"} = $PV->{"allocatable"} if( defined $PV->{"allocatable"} );
            $AllDiskDevices{"$name"}{"exported"} = $PV->{"exported"} if( defined $PV->{"exported"} );
            $AllDiskDevices{"$name"}{"pv_uuid"} = $PV->{'pv_uuid'};
            $AllDiskDevices{"$name"}{"pv"} = $PV->{'pv'};
            $AllDiskDevices{"$name"}{"vg"} = $PV->{'vg'};
            $AllDiskDevices{"$name"}{"pvsize"} = $PV->{'size'};
            $AllDiskDevices{"$name"}{"pvfreesize"} = $PV->{'freesize'};

            $AllDiskDevices{"$name"}{"uuid"} = $PV->{'uuid'};
        } elsif( $alias ){
            $AllDiskDevices{"$name"}{'isalias'} = 1;
            $AllDiskDevices{"$alias"}{'aliasdevice'} = $AllDiskDevices{"$name"}{"device"};
            if( $PVInfo{"$alias"} ){
                $PVInfo{"$alias"}{"type"} = "SAN";
                if( my $vg = $PVInfo{"$alias"}{'vg_name'} ){
                    $VGInfo{"$vg"}{'type'} = "SAN";
                }
                $AllDiskDevices{"$alias"}{"uuid"} = $PVInfo{"$alias"}{'uuid'};
                $PVInfo{"$alias"}{'aliasdevice'} = $AllDiskDevices{"$alias"}{'aliasdevice'} = $AllDiskDevices{"$name"}{"device"};
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
        my $cdev = "/dev/$vg/$lv";
        my $slv = $lv;
        $slv =~ s/-/--/gs;
        my $svg = $vg;
        $svg =~ s/-/--/gs;
        my $cadev = "/dev/mapper/$svg-$slv";
        if( ($dev && $MountDev{"$dev"}) ||
             ($adev && $MountDev{"$adev"}) ||
             $MountDev{"$cdev"} ||
             $MountDev{"$cadev"} ){

            plog("mounted lv=$lv($dev,$adev,$cdev,$cadev) $MountDev{$dev} || $MountDev{$adev} || $MountDev{$cdev} || $MountDev{$cadev}") if( &debug_level > 3 );

            $AllDiskDevices{"$lv"}{'mounted'} = 1;
            
            $AllDiskDevices{"$lv"}{'mountpoint'} = $MountDev{"$dev"}{'mountpoint'} ||
                                                     $MountDev{"$adev"}{'mountpoint'} ||
                                                     $MountDev{"$cdev"}{'mountpoint'} ||
                                                     $MountDev{"$cadev"}{'mountpoint'};
            $AllDiskDevices{"$lv"}{'fs'} = $MountDev{"$dev"}{'fs'} ||
                                                     $MountDev{"$adev"}{'fs'} ||
                                                     $MountDev{"$cdev"}{'fs'} ||
                                                     $MountDev{"$cadev"}{'fs'};
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
        if( ($dev && $MountDev{"$dev"} ) ||
             ($adev && $MountDev{"$adev"} ) ){

            plog("mounted device=$dev($adev) $MountDev{$dev} || $MountDev{$adev} ") if( &debug_level > 3 );

            $D->{"mounted"} = 1;
            $D->{"mountpoint"} = $MountDev{"$dev"}{'mountpoint'} || $MountDev{"$adev"}{'mountpoint'};
            $D->{"fs"} = $MountDev{"$dev"}{'fs'} || $MountDev{"$adev"}{'fs'};
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

        if( $D->{'mounted'} && ( $D->{'fs'} ne 'swap' ) ){
            $D->{'size'} = $D->{'mountpoint'} ? $hdf->total($D->{'mountpoint'})
						    : $hdf->total($D->{'device'});
            $D->{'freesize'} = $D->{'mountpoint'} ? $hdf->avail($D->{'mountpoint'})
						    : $hdf->avail($D->{'device'});
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

            $D->{'lvm'} = 1;
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
        @fields = qw( pvinit size freesize logical device allocatable exported mounted mountpoint partition partitioned uuid pv_uuid mp_uuid pv pvsize pvfreesize vg vgsize vgfreesize lv type lvm swap nopartitions fs_type dtype type diskdevice major minor multipath aliasdevice devmapper);  
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
    if( $D->{'mpathdevice'} && !$D->{'mpathsysdev'} ){
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

    # ignore readonly device
    if( $D->{'readonly'} ){
        $ignore = 9;
    }

    # ignore cdrom
    if( $D->{'cdrom'} ){
        $ignore = 9;
    }

    # ignore media changeable device
    if( $D->{'media_changeable'} ){
        $ignore = 9;
    }

    # ignore ejectable device
    if( $D->{'ejectable'} ){
        $ignore = 9;
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

sub get_valid_physicaldevice {
    my ($PD,@fields) = @_;

    push(@fields,('device','devmapper','aliasdevice')); # add default fields

    my $device = '';

    # TODO get from /etc/lvm/lvm.conf
    my @filter_pattern = ( "[hs][a-z][0-9]?", "cciss/c0d0", "mapper/mpath" );

    for my $n (@fields){
        $device = $PD->{"$n"};
        last if( grep { $device =~ m#$_# } @filter_pattern );
    }
    return $device;
}
sub pvcreate {
    my $self = shift;
    my ($device) = my %p = @_;
    $device = $p{"device"} if( $p{"device"} );
    my @pd = split(/\//,$device);
    my $dn = pop(@pd);

    $self->loaddiskdev();

    if( my $PD = $self->getphydev(%p, 'name'=>$dn) ){ 
        if( !$self->getpv(%p, 'name'=>$dn) ){
            if( $device = &get_valid_physicaldevice($PD) ){

                my ($e,$m) = cmd_exec("pvcreate $device");

                $self->loaddiskdev(1);  # update disk device info
                unless( $e == 0 ){
                    return retErr("_ERR_DISK_PVCREATE_","Error creating physical volume.");
                }
                my $PV = $self->getpv(%p, 'name'=>$dn);
                return retOk("_OK_PVCREATE_","Physical volume successfully created.","_RET_OBJ_",$PV);
            } else {
                return retErr("_INVALID_DISK_DEVICE_","Invalid disk device");
            }
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
        if( $device = &get_valid_physicaldevice($PV, 'pv') ){
            my ($e,$m) = cmd_exec("pvremove",$device);
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
            return retErr("_INVALID_PV_","Invalid physical volume '$dn'.");
        }
    } else {
        return retErr("_INVALID_PV_","Invalid physical volume '$dn'.");
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
    if( $p{'device'} || $p{'size'} ){
        $device = $p{"device"};
        $size = $p{"size"};
    }
    my @pd = split(/\//,$device);
    my $dn = pop(@pd);

    $self->loaddiskdev();

    if( my $PV = $self->getpv(%p, 'name'=>"$dn") ){
        if( $device = &get_valid_physicaldevice($PV, 'pv') ){
            my @a_size = ();
            push( @a_size, "--setphysicalvolumesize", $size ) if( $size );
            my ($e,$m) = cmd_exec("pvresize",@a_size,$device);
            $self->loaddiskdev(1);  # update disk device info
            unless( $e == 0 ){
                return retErr("_ERR_DISK_PVRESIZE_","Error resizing physical volume.");
            }
            $PV = $self->getpv(%p, 'name'=>"$dn");
            return retOk("_OK_PVRESIZE_","Physical volume successfully resized.","_RET_OBJ_",$PV);
        } else {
            return retErr("_INVALID_PV_","Invalid physical volume '$dn'.");
        }
    } else {
        return retErr("_INVALID_PV_","Invalid physical volume '$dn'.");
    }
}

sub get_mp_disks {
	my $dev = shift();
	my @res;

	# get mp info
	open(PROC, "/sbin/multipath -ll $dev|") or die "Can't execute multipath: $!\n";
	while(my $line = <PROC>) {
		if($line =~ /\d+:\d+:\d+:\d+\s+(\w+)\s+\d+:\d+\s+[\[\]\w]+/) {
			push(@res, $1) if(-b "/dev/$1");
		}
	}
	close(PROC);

	# test if there are 4 paths
	unless(scalar(@res) == 4) {
		plog "Can't find 4 paths for $dev:\n";
		plog Dumper(\@res);
        return;
	}

	return(@res);
}

sub expand_mp_dev {
	my ($dev, @disks) = @_;

	plog `/sbin/multipath -ll $dev | grep size`;

	for my $disk (@disks) {
		my $file = "/sys/block/$disk/device/rescan";
		plog "Expanding device $disk...\n";
		
		open(DISK, ">", $file) or die "Can't open $file: $!\n";
		print DISK "1\n";
		close(DISK);
		
		plog "done\n";
	}
	plog "Expanding mp $dev...\n";
	cmd_exec("/sbin/multipathd -k'resize map $dev'");
	plog `/sbin/multipath -ll $dev | grep size`;
	plog "done\n";
}

sub deviceresize {
    my $self = shift;
    my ($device) = my %p = @_;
    $device = $p{"device"} if( $p{"device"} );
    my @pd = split(/\//,$device);
    my $dn = pop(@pd);

    $self->loaddiskdev();

    if( my $PD = $self->getphydev(%p, 'name'=>$dn) ){ 
        $device = $PD->{'device'};
        if( $PD->{'multipath'} ){
            if( my @mp_disks = &get_mp_disks( $device ) ){
                &expand_mp_dev($device, @mp_disks);
            } else {
                return retErr("_ERR_DEVICERESIZE_","Invalid number of disks.");
            }
        }

        if( my $PV = $self->getpv(%p, 'name'=>"$dn") ){
            my $E = $self->pvresize(%p);
            if( isError($E) ){
                return retErr("_ERR_DEVICERESIZE_","Error resize physical volume.");
            }
        }

        # update disk device info
        $self->loaddiskdev(1);
        $PD = $self->getphydev( %p, 'name'=>$dn );

        return retOk("_OK_DEVICERESIZE_","Physical volume successfully resized.","_RET_OBJ_",$PD);

    } else {
        return retErr("_INVALID_PV_","Invalid physical volume.");
    }
}

=item deviceremove

remove device

    my $OK = VirtAgent::Diks->deviceremove( device=>$dev );

=cut

# deviceremove
#   device remove function
#
#   args: device
#   res: ok
sub deviceremove {
    my $self = shift;
    my ($device) = my %p = @_;
    $device = $p{"device"} if( $p{"device"} );
    my @pd = split(/\//,$device);
    my $dn = pop(@pd);

    $self->loaddiskdev();

    if( my $PD = $self->getphydev(%p, 'name'=>$dn) ){ 
        $device = $PD->{'device'};
        if( $PD->{'multipath'} ){

            my ($e,$m) = cmd_exec("/sbin/multipathd -k'remove map $device'");

            $self->loaddiskdev(1);  # update disk device info
            unless( $e == 0 ){
                return retErr("_ERR_DISK_DEVICEREMOVE_","Error remove device: $m");
            }
            return retOk("_OK_DEVICEREMOVE_","Device successfully removed.","_RET_OBJ_",$PD);
        } else {
            return retErr("_INVALID_DEVICE_","Invalid device '$device'.");
        }
    } else {
        return retErr("_INVALID_DEVICE_","Invalid device '$dn'.");
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
        next if( ref($pd) );    # ignore no string cases
        my $uuid;
        $uuid = $pd if( $pd !~ m/^\/dev/ );
        if( my $PV = $self->getpv( 'device'=>$pd, 'uuid'=>$uuid ) ){
            if( my $pvdevice = &get_valid_physicaldevice($PV, 'pv') ){
                push(@lpv,$pvdevice);
            } else {
                return retErr("_ERR_VGCREATE_INVALID_PV_","Error creating volume group: invalid physical volume '$pd'.");
            }
        } else {
            return retErr("_ERR_VGCREATE_INVALID_PV_","Error creating volume group: invalid physical volume '$pd'.");
        }
    }
    if( !$self->getvg( %p, 'name'=>$vgname ) ){
        my ($e,$m) = cmd_exec("vgcreate",$vgname,@lpv);

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
        my ($e,$m) = cmd_exec("vgremove",$vgname);

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
        next if( ref($pd) );    # ignore no string cases
        my $uuid;
        $uuid = $pd if( $pd !~ m/^\/dev/ );
        if( my $PV = $self->getpv( 'device'=>$pd, 'uuid'=>$uuid ) ){
            if( my $pvdevice = &get_valid_physicaldevice($PV, 'pv') ){
                push(@lpv,$pvdevice);
            } else {
                return retErr("_ERR_VGEXTEND_INVALID_PV_","Error extend volume group: invalid physical volume '$pd'.");
            }
        } else {
            return retErr("_ERR_VGEXTEND_INVALID_PV_","Error extend volume group: invalid physical volume '$pd'.");
        }
    }
    if( my $VG = $self->getvg( %p, 'name'=>$vgname ) ){
        $vgname = $VG->{'vg_name'};
        my ($e,$m) = cmd_exec("vgextend",$vgname,@lpv);

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
        next if( ref($pd) );    # ignore no string cases
        my $uuid;
        $uuid = $pd if( $pd !~ m/^\/dev/ );
        if( my $PV = $self->getpv( 'device'=>$pd, 'uuid'=>$uuid ) ){
            if( my $pvdevice = &get_valid_physicaldevice($PV, 'pv') ){
                push(@lpv,$pvdevice);
            } else {
                return retErr("_ERR_VGREDUCE_INVALID_PV_","Error reduce volume group: invalid physical volume '$pd'.");
            }
        } else {
            return retErr("_ERR_VGREDUCE_INVALID_PV_","Error reduce volume group: invalid physical volume '$pd'.");
        }
    }
    if( my $VG = $self->getvg( %p, 'name'=>$vgname ) ){
        $vgname = $VG->{'vg_name'};
        my ($e,$m) = cmd_exec("vgreduce",$vgname,@lpv);

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
            if( $pv = &get_valid_physicaldevice($PV, 'pv') ){
                my ($e,$m) = cmd_exec("vgreduce",$vgname,$pv);

                $self->loaddiskdev(1);  # update disk device info
                unless( $e == 0 ){
                    return retErr("_ERR_DISK_VGPVREMOVE_","Error remove physical volume from volume group.");
                }

                return retOk("_OK_VGPVREMOVE_","Physical volume successfully removed from volume group.","_RET_OBJ_",$PV);
            } else {
                return retErr("_INVALID_PV_","Invalid physical volume '$pv'.");
            }
        } else {
            return retErr("_INVALID_PV_","Invalid physical volume '$pv'.");
        }
    } else {
        return retErr("_INVALID_VG_","Invalid volume group '$vgname'.");
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

# qemu_img_create : create disk from qemu-img
sub qemu_img_create {
    my $self = shift;
    my (%p) = @_;

    if( $p{'force'} || (! -e "$p{'path'}") ){
        my $size = ETVA::Utils::roundedsize($p{'size'});   # get rounded size
        if( $size ){
            my $fmt = $p{'format'} || "raw";
            my $qemu_img_cmd = &qemu_img_cmd();

            # create disk 
            my ($e,$m) = cmd_exec("$qemu_img_cmd create -f $fmt $p{'path'} $size");
            # TODO testing error cmd
            unless( $e == 0 || $e == -1 ){
                return retErr('_ERR_QEMU_IMG_CREATE_', " Error create file disk: " . $m);
            }
            return retOk('_OK_QEMU_IMG_CREATE_',"Disk created successfully.");
        } else {
            return retErr('_ERROR_QEMU_IMG_CREATE_',"No valid size.");
        }
    } else {
        return retErr('_ERROR_QEMU_IMG_CREATE_',"Disk already exists.");
    }
}
# qemu_img_resize : resize disk with qemu-img
sub qemu_img_resize {
    my $self = shift;
    my (%p) = @_;

    if( -e "$p{'path'}" ){
        $self->loaddiskdev(1);
        # testing if file is in use
        if( $self->getphydev( 'loopfile'=>$p{'path'} ) ){
            return retErr('_ERROR_QEMU_IMG_RESIZE_',"Disk is in use.");
        } else {
            my $size = ETVA::Utils::roundedsize($p{'size'});   # get rounded size
            if( $size ){
                my $qemu_img_cmd = &qemu_img_cmd();
                # resize for both cases: increase and decrease (WARN)
                if( &have_qemu_img_resize_support() ){
                    my ($e,$m) = cmd_exec_errh("$qemu_img_cmd resize $p{'path'} $size");
                    # TODO testing error cmd
                    unless( $e == 0 ){
                        return retErr('_ERR_QEMU_IMG_RESIZE_', " Error resizing file disk: " . $m);
                    }
                } else {
                    my $fmt = $p{'format'};
                    my $opath = my $rpath = $p{'path'};

                    if( $fmt && $fmt ne 'raw' ){    # if not in raw format we need to convert
                        $rpath = "${opath}.raw";
                        # convert to raw
                        my ($e1,$m1) = cmd_exec_errh("$qemu_img_cmd convert $opath -O raw $rpath");
                        unless( $e1 == 0 ){
                            unlink("$rpath") if( -e "$rpath" );
                            return retErr('_ERR_QEMU_IMG_RESIZE_', " Error resizing file disk: cant convert to raw format - " . $m1);
                        }
                    }
                    # resize raw
                    my ($e2,$m2) = cmd_exec_errh("/bin/dd if=/dev/zero of=$rpath bs=1 count=0 seek=$size");

                    if( $fmt && $fmt ne 'raw' ){    # if not in raw format
                        # convert to original
                        my ($e3,$m3) = cmd_exec_errh("$qemu_img_cmd convert $rpath -O $fmt $opath");
                        unlink("$rpath");
                        unless( $e3 == 0 ){
                            return retErr('_ERR_QEMU_IMG_RESIZE_', " Error resizing file disk: cant convert to original format ('$fmt') - " . $m3);
                        }
                    }
                }
                return retOk('_OK_QEMU_IMG_RESIZE_',"Disk resized successfully.");
            } else {
                return retErr('_ERROR_QEMU_IMG_RESIZE_',"No valid size.");
            }
        }
    } else {
        return retErr('_ERROR_QEMU_IMG_RESIZE_',"Disk does not exists.");
    }
}

sub lvcreate {
    my $self = shift;
    my ($lv,$vg,$size,$format,$usagesize) = my %p = @_;
    if( $p{'lv'} || $p{"vg"} || $p{"size"} || $p{'format'} || $p{'usagesize'} ){
        $lv = $p{"lv"};
        $vg = $p{"vg"};
        $size = $p{"size"};
        $format = $p{"format"};
        $usagesize = $p{"usagesize"};
    }

    $size = "${size}M" if( $size =~ m/^[0-9.]+$/ ); # size in Mb by default

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

            my $E;
            if( &have_qemu_img() ){
                $E = $self->qemu_img_create( 'path'=>$lv, 'size'=>$size, 'format'=>$format );
            } elsif( $format && ( $format ne 'raw' ) ){
                return retErr("_ERR_DISK_LVCREATE_","Dont have support for disk file format: '$format'");
            } else {
                # CMAR 02/03/2010
                #   special case for create by dd 
                $E = $self->ddcreate( 'path'=>$lv, 'size'=>$size );
            }

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
        my ($e,$m) = cmd_exec("lvcreate","-L",$size,"-n",$lv,$vg);

        $self->loaddiskdev(1);  # update disk device info
        unless( $e == 0 ){
            # return error if not created
            if( !$self->getlv('name'=>$lv) ){
                return retErr("_ERR_DISK_LVCREATE_","Error creating logical volume.");
            }
        }
        
        # Get last logical volume created
        my $LV = $self->getlv('name'=>$lv);

        if( $format && ( $format ne 'raw' ) && ( $format ne 'lvm' ) ){
            if( &have_qemu_img() ){
                $usagesize ||= $LV->{'size'};
                my $usagesize_k = convertsize($usagesize,'K');
                my $E = $self->qemu_img_create( 'path'=>$LV->{'device'}, 'size'=>"${usagesize_k}K", 'format'=>$format, 'force'=>1 );
                #if( isError($E) ){
                #    return retErr("_ERR_DISK_LVCREATE_","Error creating logical volume: ".$E->{'_errordetail_'});
                #}

                $self->loaddiskdev(1);  # update disk device info
                # Get last logical volume created
                $LV = $self->getlv('name'=>$lv);

            #} else {
            #    return retErr("_ERR_DISK_LVCREATE_","Dont have support for disk file format: '$format'");
            }
        }

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
        $lvn = $LV->{'lv_name'};        # ... fix it...
        $vg = $LV->{'vg_name'};
        $lv = $LV->{'device'};

        my $VG = $self->getvg(%p, 'name'=>$vg);

        my $SNAPSHOTS;
        if( $LV->{'volumetype'} eq 'origin' ){
            $SNAPSHOTS = [ grep { $_->{'snapshot'} && ( $_->{'vg'} eq $LV->{'vg'} ) && ( $_->{'origin'} eq $LV->{'lv'} ) } values %LVInfo ];
        }

        my $_ok_msg_;

        if( $vg eq '__DISK__' ){
            # CMAR 04/03/2010
            #   special case for remove file disks
            my $E = $self->ddremove( 'path'=>$lv );

            $self->loaddiskdev(1);  # update disk device info

            if( isError($E) ){
                return retErr("_ERR_DISK_LVREMOVE_","Error remove logical volume: ".$E->{'_errordetail_'});
            }

            $_ok_msg_ ||= "Special logical volume successfully removed.";
        } else {
            my $lvdevice = $LV->{'lvdevice'} || $LV->{'device'};
            my ($e,$m) = cmd_exec("lvremove","-f",$lvdevice);

            $self->loaddiskdev(1);  # update disk device info
            unless( $e == 0 ){
                # send error if LV remove not successful
                unless( !$self->getlv( 'device'=>$lvdevice, %p ) ){
                    return retErr("_ERR_DISK_LVREMOVE_","Error remove logical volume.");
                }
            }
        }

        $VG = $self->getvg(%p, 'name'=>$vg);

        # send update vg freesize
        $LV->{"vgfreesize"} = $VG->{"freesize"} || 0;

        # reference to updated volume group
        $LV->{"volumegroup"} = $VG;

        my %_RET_OBJ_ = ( %$LV );
        if( $SNAPSHOTS && @$SNAPSHOTS ){
            $_RET_OBJ_{'SNAPSHOTS'} = $SNAPSHOTS;
        }

        $_ok_msg_ ||= "Logical volume successfully removed.";
        return retOk("_OK_LVREMOVE_","$_ok_msg_","_RET_OBJ_",\%_RET_OBJ_);

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
    my ($lv,$size,$usagesize) = my %p = @_;
    if( $p{"lv"} || $p{"size"} || $p{'usagesize'} ){
        $lv = $p{"lv"};
        $size = $p{"size"};
        $usagesize = $p{'usagesize'};
    }
    $size = "${size}M" if( $size =~ m/^[0-9.]+$/ ); # size in Mb by default

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

            my $format = $LV->{'format'};

            my $E;
            if( &have_qemu_img() ){
                $E = $self->qemu_img_resize( 'path'=>$lv, 'size'=>$size, 'format'=>$format );
            } elsif( $format && ( $format ne 'raw' ) ){
                return retErr("_ERR_DISK_LVRESIZE_","Dont have support for resize disk file format: '$format'");
            } else {
                # CMAR 04/03/2010
                #   special case for resize file disks
                my $E = $self->ddresize( 'path'=>$lv, 'size'=>$size );
            }

            $self->loaddiskdev(1);  # update disk device info

            # Get updated logical volume
            $LV = $self->getlv( 'device'=>$lv, %p );
            if( isError($E) ){
                return retErr("_ERR_DISK_LVRESIZE_","Error reisze logical volume: ".$E->{'_errordetail_'});
            }
            return retOk("_OK_LVRESIZE_","Special logical volume successfully resized.","_RET_OBJ_",$LV);
        } else {
            $lv = $LV->{'lvdevice'} || $LV->{'device'} if( !$lv );
            my ($e,$m) = cmd_exec("lvresize","-f","-L",$size,$lv);

            $self->loaddiskdev(1);  # update disk device info
            unless( $e == 0 ){
                return retErr("_ERR_DISK_LVRESIZE_","Error resize logical volume.");
            }

            $LV = $self->getlv( 'device'=>$lv, %p );
            if( $usagesize ){
                my $format = $LV->{'format'};
                if( $format && ( $format ne 'raw' ) && ( $format ne 'lvm' ) ){
                    if( &have_qemu_img() ){
                        my $usagesize_k = convertsize($usagesize,'K');  # resize for usage size
                        my $E = $self->qemu_img_resize( 'path'=>$LV->{'device'}, 'size'=>"${usagesize_k}K", 'format'=>$format);
                        if( isError($E) ){
                            return retErr("_ERR_DISK_LVRESIZE_","Error reisze logical volume: ".$E->{'_errordetail_'});
                        }
                        $self->loaddiskdev(1);  # update disk device info
                    #} else {
                    #    return retErr("_ERR_DISK_LVRESIZE_","Dont have support for resize disk file format: '$format'");
                    }
                }
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
# e.g. lvcreate --size 100m --name snap --snapshot /dev/vg00/lvol1
sub createsnapshot {
    my $self = shift;
    my ($olv,$slv,$size,$extents,$name,$tag) = my %p = @_;

    if( $p{'olv'} || $p{'slv'} || $p{'size'} || $p{'extents'} || $p{'tag'} || $p{'name'} ){
        $olv = $p{'olv'};
        $slv = $p{'slv'};
        $size = $p{'size'};
        $extents = $p{'extents'};
        $tag = $p{'tag'};
        $name = $p{'name'};
    }
    $size = "${size}M" if( $size =~ m/^[0-9.]+$/ ); # size in Mb by default

    $self->loaddiskdev();

    if( my $LV = $self->getlv( 'device'=>$olv, %p ) ){
        $olv = $LV->{'device'};
        if( $p{'use_qemu'} || $LV->{'vg'} eq '__DISK__' ){
            # do this usign qemu-img if available
            if( &have_qemu_img() ){
                if( $LV->{'format'} eq 'qcow2' ){   # only qcow2 format support snapshots
                    if( !$slv ){
                        $slv = $name || $tag;
                    }
                    my $qemu_img_cmd = &qemu_img_cmd();
                    my ($e,$m) = cmd_exec("$qemu_img_cmd snapshot -c $slv $olv");
                    unless( $e == 0 ){
                        return retErr("_ERR_CREATE_SNAPSHOT_","Error creating snapshot: $m");
                    }
                } else {
                    return retErr('_ERR_CREATE_SNAPSHOT_',"Cant create snapshot: the volume format does not support snapshots.");
                }
            } else {
                return retErr('_ERR_CREATE_SNAPSHOT_',"Cant create snapshot: not qemu-img available.");
            }
        } else {
            # TODO
            #   this can block process...
            my ($e,$m);
            my @extra = ();
            if( $extents ){
                push(@extra,"--extents",$extents),
            } else {
                push(@extra,"--size",$size),
            }
            if( !$slv ){
                my $tok = $name || $tag;
                $slv = "$LV->{'name'}-$tok";
            }
            unless( ( ($e,$m) = cmd_exec("lvcreate",@extra,"--snapshot",$LV->{'lvdevice'},"--name",$slv) ) && ( $e == 0 ) ){
                return retErr("_ERR_CREATE_SNAPSHOT_","Error creating snapshot: $m");
            }
        }
    } else {
        return retErr('_INVALID_LOG_VOL_',"Invalid logical volume: $olv");
    }

    $self->loaddiskdev(1);  # update disk device info

    # Get last snapshot created
    my $SLV = $self->getlv('name'=>$slv);
    return retOk("_OK_CREATESNAPSHOT_","Snapshot successfully created.","_RET_OBJ_",$SLV);
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
    my ($olv,$slv,$name,$tag) = my %p = @_;

    if( $p{'olv'} || $p{'slv'} || $p{'tag'} || $p{'name'} ){
        $olv = $p{'olv'};
        $slv = $p{'slv'};
        $tag = $p{'tag'};
        $name = $p{'name'};
    }

    $self->loaddiskdev();

    if( my $LV = $self->getlv( 'device'=>$olv, %p ) ){
        $olv = $LV->{'device'};
        if( $p{'use_qemu'} || $LV->{'vg'} eq '__DISK__' ){
            # do this usign qemu-img if available
            if( &have_qemu_img() ){
                if( $LV->{'format'} eq 'qcow2' ){   # only qcow2 format support snapshots
                    if( !$slv ){
                        $slv = $name || $tag;
                    }
                    my $qemu_img_cmd = &qemu_img_cmd();
                    my ($e,$m) = cmd_exec("$qemu_img_cmd snapshot -a $slv $olv");
                    unless( $e == 0 ){
                        return retErr("_ERR_CONVERT_SNAPSHOT_","Error converting snapshot: $m");
                    }
                } else {
                    return retErr('_ERR_CONVERT_SNAPSHOT_',"Cant convert snapshot: the volume format does not support snapshots.");
                }
            } else {
                return retErr('_ERR_CONVERT_SNAPSHOT_',"Cant convert snapshot: not qemu-img available.");
            }
        } else {
            if( !$slv ){
                my $tok = $name || $tag;
                #$slv = "$LV->{'name'}-$tok";
                $slv = "$LV->{'device'}-$tok";
            }
            # TODO
            #   this can block process...
            my ($e,$m);
            unless( ( ($e,$m) = cmd_exec("lvconvert --snapshot",$LV->{'lvdevice'},$slv) ) && ( $e == 0 ) ){
                return retErr("_ERR_CONVERT_SNAPSHOT_","Error convert snapshot: $m");
            }
        }
    } else {
        return retErr('_INVALID_LOG_VOL_',"Invalid logical volume: $olv");
    }

    $self->loaddiskdev(1);  # update disk device info

    # TODO change this
    return retOk("_OK_","ok");
}

=item revertsnapshot

    revert a snapshot into its origin logical volume

    my $OK = VirtAgent::Disk->revertsnapshot( olv=>$lv, slv=>$snapshot );

=cut

# revertsnapshot
#   revert a snapshot into its origin logical volume
#
#   args: olv,slv
#   res: ok || Error
# e.g. lvconvert --merge vg00/lvol1-snap
# haveRevertSnapshotSupport - check revert snapshots support
sub haveRevertSnapshotSupport {
    if( not defined $HAVEREVERTSNAPSHOTSUPPORT ){
        my ($e,$m) = cmd_exec("dmsetup targets");
        if( $e == 0 && ( $m =~ m/snapshot-merge/gs ) ){
            $HAVEREVERTSNAPSHOTSUPPORT = 1;
        } else {
            $HAVEREVERTSNAPSHOTSUPPORT = 0;
        }
    }
    return $HAVEREVERTSNAPSHOTSUPPORT;
}

sub revertsnapshot {
    my $self = shift;
    my ($olv,$slv,$name,$tag) = my %p = @_;

    if( $p{'olv'} || $p{'slv'} || $p{'tag'} || $p{'name'} ){
        $olv = $p{'olv'};
        $slv = $p{'slv'};
        $tag = $p{'tag'};
        $name = $p{'name'};
    }

    $self->loaddiskdev();

    if( my $LV = $self->getlv( 'device'=>$olv, %p ) ){
        $olv = $LV->{'device'};
        if( $p{'use_qemu'} || ($LV->{'vg'} eq '__DISK__') ){
            # do this usign qemu-img if available
            if( &have_qemu_img() ){
                if( $LV->{'format'} eq 'qcow2' ){   # only qcow2 format support snapshots
                    if( !$slv ){
                        $slv = $name || $tag;
                    }
                    my $qemu_img_cmd = &qemu_img_cmd();
                    my ($e,$m) = cmd_exec("$qemu_img_cmd snapshot -a $slv $olv");
                    unless( $e == 0 ){
                        return retErr("_ERR_REVERT_SNAPSHOT_","Error revert snapshot: $m");
                    }
                } else {
                    return retErr('_ERR_REVERT_SNAPSHOT_',"Cant revert snapshot: the volume format does not support snapshots.");
                }
            } else {
                return retErr('_ERR_REVERT_SNAPSHOT_',"Cant revert snapshot: not qemu-img available.");
            }
        } else {
            if( &haveRevertSnapshotSupport ){
                if( !$slv ){
                    if( $name ){
                        $slv = "$LV->{'device'}-$name";
                    } elsif( $tag ){
                        $slv = '@'."$tag";
                    }
                }
                # TODO
                #   this can block process...
                my ($e,$m);
                unless( ( ($e,$m) = cmd_exec("lvconvert --merge",$slv) ) && ( $e == 0 ) ){
                    return retErr("_ERR_REVERT_SNAPSHOT_","Error revert snapshot: $m");
                }
            } else {
                return retErr("_ERR_REVERT_SNAPSHOT_","Don't have support to revert snapshots.");
            }
        }
    } else {
        return retErr('_INVALID_LOG_VOL_',"Invalid logical volume: $olv");
    }

    $self->loaddiskdev(1);  # update disk device info

    # TODO change this
    return retOk("_OK_","ok");
}

=item listsnapshots

list snapshots of logical volume

    my $LIST = VirtAgent::Disk->listsnapshots( lv=>$lv );

=cut

sub listsnapshots {
    my $self = shift;
    my ($lv) = my %p = @_;

    if( $p{'lv'} ){
        $lv = $p{'lv'};
    }

    $self->loaddiskdev();

    my @list_snapshots = ();

    if( my $LV = $self->getlv( 'device'=>$lv, %p ) ){
        $lv = $LV->{'device'};
        if( $p{'use_qemu'} || $LV->{'vg'} eq '__DISK__' ){
            # do this usign qemu-img if available
            if( &have_qemu_img() ){
                if( $LV->{'format'} eq 'qcow2' ){   # only qcow2 format support snapshots
                    my $qemu_img_cmd = &qemu_img_cmd();
                    my ($e,$m) = cmd_exec("$qemu_img_cmd info -f $LV->{'format'} $lv");
                    unless( $e == 0 ){
                        return retErr("_ERR_LIST_SNAPSHOTS_","Error list snapshots: $m");
                    }
                    my $go_snapshots = 0;
                    for my $l (split(/\r?\n/,$m)){
                        if( $l =~ m/Snapshot list:/ ){     # go to snapshot list
                            $go_snapshots = 1;
                        }
                        if( $go_snapshots ){
                            if( $l =~ m/^(\d+)\s+(.+)\s+(\d+)\s+(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\s+(\d{2}:\d{2}:\d{2}.\d{3})$/ ){
                                my ($id,$snapshot,$size,$date,$vmclock) = ($1,$2,$3,$3,$4);
                                push(@list_snapshots, { 'id'=>$id, 'name'=>trim($snapshot), 'size'=>$size, 'date'=>$date, 'vmclock'=>$vmclock });
                            }
                        }
                    }
                } else {
                    return retErr('_ERR_LIST_SNAPSHOTS_',"Cant list snapshots: the volume format does not support snapshots.");
                }
            } else {
                return retErr('_ERR_LIST_SNAPSHOTS_',"Cant list snapshots: not qemu-img available.");
            }
        } else {
            # TODO
            #if( $LV->{'volumetype'} eq 'origin' ){
            #    @list_snapshots = ( grep { $_->{'snapshot'} && ( $_->{'vg'} eq $LV->{'vg'} ) && ( $_->{'origin'} eq $LV->{'lv'} ) } values %LVInfo );
            #}
            return retErr('_ERR_LIST_SNAPSHOTS_',"Cant list snapshots: this logical volume $lv does not support snapshots.");
        }
    } else {
        return retErr('_INVALID_LOG_VOL_',"Invalid logical volume: $lv");
    }

    return wantarray() ? @list_snapshots : \@list_snapshots;
}

=item convertformat

convert volume to specific format

    my $OK = VirtAgent::Disk->convertformat( olv=>$lv, dlv=>$newlv, format=>$format );

=cut

# convertformat
#   convert volume to specific format
#
#   args: olv,dlv,format
#   res: ok || Error
#
sub convertformat {
    my $self = shift;
    my ($olv,$dlv,$format) = my %p = @_;

    if( $p{'olv'} || $p{'dlv'} ){
        $olv = $p{'olv'};
        $dlv = $p{'dlv'};
        $format = $p{'format'};
    }

    $self->loaddiskdev();

    if( my $LV = $self->getlv( 'device'=>$olv, %p ) ){
        $olv = $LV->{'device'};

        if( $LV->{'format'} eq $format ){
            return retErr('_ERR_LVCONVERT_',"Volume already in this format: $format.");
        }

        if( 1 || $LV->{'vg'} eq '__DISK__' ){
            # do this usign qemu-img if available
            if( &have_qemu_img() ){
                my $qemu_img_cmd = &qemu_img_cmd();

                if( ($format ne 'host_device') && ($format ne 'raw') ){
                    # force to check format with qemu-img
                    my ($e0,$m0) = cmd_exec("$qemu_img_cmd info -f $format $olv");
                    if( ($e0 == 0) && ($m0 =~ m/file format: $format/gs) ){
                        return retErr('_ERR_LVCONVERT_',"Volume already in this format: $format.");
                    }
                }

                my ($e,$m) = cmd_exec("$qemu_img_cmd convert -O $format $olv $dlv");
                unless( $e == 0 ){
                    return retErr("_ERR_LVCONVERT_","Error converting volume: $m");
                }
            } else {
                return retErr('_ERR_LVCONVERT_',"Cant convert volume: not qemu-img available.");
            }
        } else {
            # TODO convert LVM to other formats
            return retErr('_ERR_LVCONVERT_',"Cant convert LVM volume.");
        }
    } else {
        return retErr('_INVALID_LOG_VOL_',"Invalid logical volume: $olv");
    }
    $self->loaddiskdev(1);  # update disk device info
    # TODO change this
    return retOk("_OK_","ok");
}

=item backupsnapshot

    backup snapshot to standalone file

    my $OK = VirtAgent::Disk->backupsnapshot( olv=>$lv, slv=>$snapshot, backup=>$backupfile );

=cut

sub backupsnapshot {
    my $self = shift;
    my ($olv,$slv,$name,$tag,$backup) = my %p = @_;

    if( $p{'olv'} || $p{'slv'} || $p{'tag'} || $p{'name'} || $p{'backup'} ){
        $olv = $p{'olv'};
        $slv = $p{'slv'};
        $tag = $p{'tag'};
        $name = $p{'name'};
        $backup = $p{'backup'};
    }

    $self->loaddiskdev();

    if( my $LV = $self->getlv( 'device'=>$olv, %p ) ){
        $olv = $LV->{'device'};
        if( $p{'use_qemu'} || 
                    ( $LV->{'format'} eq 'qcow2' ) ||   # only qcow2 format support snapshots
                    ( $LV->{'vg'} eq '__DISK__' ) ){

            # do this usign qemu-img if available
            if( &have_qemu_img() ){
                if( $LV->{'format'} eq 'qcow2' ){   # only qcow2 format support snapshots
                    if( !$slv ){
                        $slv = $name || $tag;
                    }
                    my $qemu_img_cmd = &qemu_img_cmd();
                    #qemu-img2 convert -f qcow2 -O qcow2 -s $date $filename.qcow2 $filename-$date.qcow2
                    my ($e,$m) = cmd_exec("$qemu_img_cmd convert -f qcow2 -O qcow2 -s $slv $olv $backup");
                    unless( $e == 0 ){
                        return retErr("_ERR_BACKUP_SNAPSHOT_","Error backup snapshot: $m");
                    }
                } else {
                    return retErr('_ERR_BACKUP_SNAPSHOT_',"Cant backup snapshot: the volume format does not support snapshots.");
                }
            } else {
                return retErr('_ERR_BACKUP_SNAPSHOT_',"Cant backup snapshot: not qemu-img available.");
            }
        } else {

            if( !$slv ){
                if( $name ){
                    $slv = "$LV->{'device'}-$name";
                } elsif( $tag ){
                    $slv = "$LV->{'device'}-$tag";
                }
            }

            my $bs = "512";
            $bs = "10M" if( $LV->{'size'} > (10 * 1024 * 1024) ); # if greater then 10Mb
            my $ionice_c = 3;
            $ionice_c = $p{'ionice_c'} if( defined($p{'ionice_c'}) );
            my ($e,$m) = cmd_exec("ionice -c $ionice_c dd if=$slv of=$backup bs=$bs");

            # TODO testing error cmd
            unless( $e == 0 || $e == -1 ){
                return retErr("_ERR_BACKUP_SNAPSHOT_","Error backup snapshot: $m");
            }
        }
    } else {
        return retErr('_INVALID_LOG_VOL_',"Invalid logical volume: $olv");
    }

    $self->loaddiskdev(1);  # update disk device info

    # TODO change this
    return retOk("_OK_","ok");
}

=item backupdisk

    backup disk to standalone file

    my $OK = VirtAgent::Disk->backupdisk( path=>$path, backup=>$backupfile );

=cut

sub backupdisk {
    my $self = shift;
    my ($path,$backup) = my %p = @_;

    if( $p{'path'} || $p{'backup'} ){
        $path = $p{'path'};
        $backup = $p{'backup'};
    }

    $self->loaddiskdev();

    if( my $LV = $self->getlv( 'device'=>$path, %p ) ){
        $path = $LV->{'device'};

        my $bs = "512";
        $bs = "10M" if( $LV->{'size'} > (10 * 1024 * 1024) ); # if greater then 10Mb
        my $ionice_c = 3;
        $ionice_c = $p{'ionice_c'} if( defined($p{'ionice_c'}) );
        my ($e,$m) = cmd_exec("ionice -c $ionice_c dd if=$path of=$backup bs=$bs");

        # TODO testing error cmd
        unless( $e == 0 || $e == -1 ){
            return retErr("_ERR_BACKUP_DISK_","Error backup disk: $m");
        }
    } else {
        return retErr('_INVALID_LOG_VOL_',"Invalid logical volume: $path");
    }

    $self->loaddiskdev(1);  # update disk device info

    # TODO change this
    return retOk("_OK_BACKUP_DISK_","Backup disk with success");
}

# havemultipath
#   testing multipath support
#
sub havemultipath {
    my ($force) = @_;
    if( $force || (not defined $HAVEMULTIPATH) ){
        my ($e,$m) = cmd_exec("echo 'show maps' | /sbin/multipathd -k");
        if( $e == 0 && ( $m ne 'multipathd> multipathd> ' ) ){
            $HAVEMULTIPATH = 1;
        } else {
            $HAVEMULTIPATH = 0;
        }
    }
    return $HAVEMULTIPATH;
}

# lvclone
#   clone logical volumes
#
#   args: olv,clv
#   res: ok || Error
# e.g. 
sub lvclone {
    my $self = shift;
    my ($olv,$clv) = my %p = @_;

    if( $p{'olv'} || $p{'clv'} ){
        $olv = $p{'olv'};
        $clv = $p{'clv'};
    }

    $self->loaddiskdev();

    # TODO testing clone device too
    if( my $LV = $self->getlv( 'device'=>$olv, %p ) ){
        $olv = $LV->{'device'};

        my $bs = "512";
        $bs = "10M" if( $LV->{'size'} > (10 * 1024 * 1024) ); # if greater then 10Mb

        my $ionice_c = 3;   # use io nice
        $ionice_c = $p{'ionice_c'} if( defined($p{'ionice_c'}) );

        my ($e,$m) = cmd_exec("ionice -c $ionice_c dd if=$olv of=$clv bs=$bs");

        # TODO testing error cmd
        unless( $e == 0 || $e == -1 ){
            return retErr('_ERR_LVCLONE_', " Error clone logical volume: " . $m);
        }
    } else {
        return retErr('_INVALID_LOG_VOL_',"Invalid logical volume: $olv");
    }

    $self->loaddiskdev(1);  # update disk device info

    # Get cloned logical volume
    my $CLV = $self->getlv('device'=>$clv);
    return retOk("_OK_LVCLONE_","Clone of logical volume successfully created.","_RET_OBJ_",$CLV);
}

sub clonedisk {
    my $self = shift;
    my %p = @_;

    # create new logical volume to be a clone
    my $E = $self->lvcreate(@_);

    if( !isError($E) ){
        my $CLV = $E->{'_obj_'};
        # clone volumes
        $E = $self->lvclone( 'olv'=>$p{'original_lv'}, 'clv'=>$CLV->{'device'} );
    }

    if( isError($E) ){  # if error roll-back
        #$self->lvremove(@_);
    }
    return wantarray() ? %$E : $E;
}

# dmsetup_cmd : return dmsetup command path
sub dmsetup_cmd {
    return "/sbin/dmsetup";
}
# have_dmsetup : check if dmsetup command available
sub have_dmsetup {
    return ( -x &dmsetup_cmd() ) ? 1 : 0;
}

# device_table: output table for device
sub device_table {
    my $self = shift;
    my %p = @_;

    my $device = $p{'device'} || "";

    my $op_target = "";
    $op_target = "--target $p{'target'}" if( $p{'target'} );

    my $dmsetup_cmd = &dmsetup_cmd();

    open(DMSETUP_TABLE,"$dmsetup_cmd table $op_target $device 2>/dev/null|");
    my @table = ();
    while(<DMSETUP_TABLE>){
        my $nl = $_;
        chomp($nl);
        if( !$p{'nouuid'} ){
            # resolve major:minor to device/uuid
            while( my ($major,$minor) = ( $nl =~ m/(\d+):(\d+)/ ) ){
                if( my $PD = $self->getphydev( 'major'=>$major, 'minor'=>$minor ) ){ 
                    my $tokid = $PD->{'uuid'} ? "$PD->{'uuid'}" : "$PD->{'device'}";
                    $nl =~ s/${major}:${minor}/{$tokid}/;
                }
            }
        }
        push(@table,$nl);
    }
    close(DMSETUP_TABLE);

    return wantarray() ? @table : \@table;
}

sub device_table_w_device_trans {
    my $self = shift;
    my @table = $self->device_table(@_, 'nouuid'=>1 );

    my @n_table = ();
    for my $l (@table){
        my $nl = $l;
        chomp($nl);
        while( my ($major,$minor) = ( $nl =~ m/(\d+):(\d+)/ ) ){ # resolve major:minor to device/uuid
            if( my $PD = $self->getphydev( 'major'=>$major, 'minor'=>$minor ) ){ 
                my $tokid = $PD->{'uuid'} ? "$PD->{'uuid'}" : "$PD->{'device'}";
                $nl =~ s/${major}:${minor}/{$tokid}/;
            }
        }
        my ($id_device) = ( $nl =~ m/^(\S+):/ );
        if( my @lAD = grep { ($_->{'device'} =~ m#/${id_device}$#) || ($_->{'aliasdevice'} =~ m#/${id_device}$#) } values %AllDiskDevices ){
            # we have duplicated devices with diferent fields/values

            my $tokid;
            if( my ($AD) = grep { $_->{'uuid'} } @lAD ){    # get device with uuid 
                $tokid = $AD->{'uuid'};
            } else {                                        # or else get device
                my ($AD) = @lAD;
                $tokid = $AD->{'device'};
            }

            $nl =~ s#^${id_device}:#${tokid}:#; # replace by uuid/device
        }
        
        push(@n_table, [ $nl, $l ] );
    }
    return wantarray() ? @n_table : \@n_table;
}

sub get_lvs_devicetable {
    my $self = shift;
    my %lvs = $self->getlvs(@_);
    my @table = $self->device_table(@_, 'nouuid'=>1 );

    my @n_lvs = ();
    for my $LV (values %lvs){
        my ($lv,$vg) = ($LV->{'lv'},$LV->{'vg'});
        my $alv = $lv;
        $alv =~ s/-/--/gs;
        my $re_vglv = "${vg}-${alv}";
        my @lv_tbl = ();
        for my $el (@table){
            chomp($el);
            if( $el =~ m/^${re_vglv}(-real)?:\s+(\d+)\s+(\d+)\s+linear\s+(\d+):(\d+)\s+(\d+)$/ ){
                my ($snapshot,$start,$end,$major,$minor,$size) = ($1,$2,$3,$4,$5,$6);
                my %L = ( 'snapshot'=>$snapshot, 'start'=>$start, 'end'=>$end, 'major'=>$major, 'minor'=>$minor, 'size'=>$size );
                if( my $PD = $self->getphydev( 'major'=>$major, 'minor'=>$minor ) ){ 
                    $L{'physicaldevice'} = { 'uuid'=>$PD->{'uuid'}, 'device'=>$PD->{'device'} };
                }
                push(@lv_tbl, \%L);
            }
        }
        my %lv = ( 'lv'=>$LV->{'lv'}, 'uuid'=>$LV->{'uuid'}, 'device'=>$LV->{'device'}
                        ,'vg'=>$LV->{'vg'}, 'vg_uuid'=>$LV->{'volumegroup'}{'vg_uuid'}
                        ,'table'=>\@lv_tbl );
        push(@n_lvs, \%lv);
    }

    return wantarray() ? @n_lvs : \@n_lvs;
}

sub device_loadtable {
    my $self = shift;
    my %p = @_;

    my $device = $p{'device'};
    if( !$device ){
        return retErr("_ERR_DEVICE_LOADTABLE_","Error load device table: no device specified");
    }

    my $table = [];
    if( $table = $p{'table'} ){
        my $dmsetup_cmd = &dmsetup_cmd();
        open(DMSETUP_LOAD,"| $dmsetup_cmd load $device 2>/dev/null");
        for my $l (@$table){
            while( my ($tok) = ( $l =~ m/\{(.+)\}/ ) ){ # resolve major:minor to device/uuid
                my %q = ( $tok =~ m/\/dev\// ) ? ('device'=>$tok) : ('uuid'=>$tok);
                if( my $PD = $self->getphydev(%q) ){ 
                    my ($major,$minor) = ($PD->{'major'},$PD->{'minor'});
                    $l =~ s/\{$tok\}/${major}:${minor}/;
                }
            }
            plog("device_loadtable: $l") if( &debug_level > 3 );
            print DMSETUP_LOAD $l,$/;
        }
        close(DMSETUP_LOAD);
    } else {
        return retErr("_ERR_DEVICE_LOADTABLE_","Error load device table: no table specified");
    }
}

# device_resume: device resume
sub device_resume {
    my $self = shift;
    my %p = @_;

    my $device = $p{'device'};
    if( !$device ){
        return retErr("_ERR_DEVICE_RESUME_","Error resume device: no device specified");
    }

    my $dmsetup_cmd = &dmsetup_cmd();
    my ($e,$m) = cmd_exec("$dmsetup_cmd resume $device");
    unless( $e == 0 ){
        return retErr("_ERR_DEVICE_RESUME_","Error resume device: $m");
    }

    return retOk("_OK_DEVICE_RESUME_","Device successfully resumed.");
}

# device_suspend: device suspend
sub device_suspend {
    my $self = shift;
    my %p = @_;

    my $device = $p{'device'};
    if( !$device ){
        return retErr("_ERR_DEVICE_SUSPEND_","Error suspend device: no device specified");
    }

    my $dmsetup_cmd = &dmsetup_cmd();
    my ($e,$m) = cmd_exec("$dmsetup_cmd suspend $device");
    unless( $e == 0 ){
        return retErr("_ERR_DEVICE_SUSPEND_","Error suspend device: $m");
    }

    return retOk("_OK_DEVICE_SUSPEND_","Device successfully suspended.");
}

# device_remove: device remove
sub device_remove {
    my $self = shift;
    my %p = @_;

    my $device = $p{'device'};
    if( !$device ){
        return retErr("_ERR_DEVICE_REMOVE_","Error remove device: no device specified");
    }

    my $dmsetup_cmd = &dmsetup_cmd();
    my ($e,$m) = cmd_exec("$dmsetup_cmd remove $device");
    unless( $e == 0 ){
        return retErr("_ERR_DEVICE_REMOVE_","Error remove device: $m");
    }

    # remove symbolic links
    if( -l "$device" ){
        unlink $device;
    } elsif( $device =~ m/(^\/dev\/mapper\/)?(.+)$/ ){
        (undef, my $dn) = ($1,$2);
        $dn =~ s#-#/#;
        my $ndn = "/dev/$dn";
        if( -l "$ndn" ){
            unlink $ndn;
        }
    }

    return retOk("_OK_DEVICE_REMOVE_","Device successfully removed.");
}

# wrapper for device_remove
sub device_remove_wrap {
    my $self = shift;

    my $E = $self->device_suspend( @_ );
    unless( isError($E) ){
        $E = $self->device_remove( @_ );

        $E = $self->device_resume( @_ );
    }
    return wantarray() ? %$E : $E;
}

sub device_loadtable_wrap {
    my $self = shift;

    my $E = $self->device_suspend( @_ );
    unless( isError($E) ){
        $E = $self->device_loadtable( @_ );

        $E = $self->device_resume( @_ );
    }
    return wantarray() ? %$E : $E;
}

sub lookup_fc_devices {
    my $self = shift;

    my $fc_bdir = '/sys/class/scsi_host';

    my $pbefore = &get_fc_partitions();

    for my $fc (&detect_fcs($fc_bdir)) {
        &scan_fc($fc);
    }

    my $pafter = &get_fc_partitions();

    my %NewFcDevices = ();
    for my $uid (keys %$pafter) {
        next if(exists $pbefore->{"$uid"});  # ignore old ones

        $NewFcDevices{"$uid"} = $pafter->{"$uid"};

        plog("Found new volume '$uid' with device(s): " . join(' ', @{$pafter->{$uid}}) ) if( &debug_level > 3 );
        my $log = <<EOF;
        multipath {
            wwid			$uid
            alias			<nome>
        }
EOF
        plog("$log") if( &debug_level > 3 );
    }

    # load disk dev info
    $self->loaddiskdev(1);

    return retOk("_OK_LOOKUP_FC_DEVICES_","Lookup FC devices with success.");
}

####################################################################

sub detect_fcs {
	my $dir = shift();
	my @res;

	if( opendir(DIR, $dir) ){
        while(my $d = readdir(DIR)) {
            # skip rubish
            next unless($d =~ /^host\d+$/);

            # skip hosts without description
            next unless(-r "$dir/$d/model_desc");

            # test model_desc
            open(FILE, "<", "$dir/$d/model_desc");
            my $str = <FILE>;
            close(FILE);

            # TODO improve this
            next unless($str =~ /qlogic/i || $str =~ /FC Expansion Card/i || $str =~ /QLE220/ || $str =~ /Fibre Channel .*Mezzanine HBA/);

            push(@res, $d);
        }
        closedir(DIR);
    } else {
        plog "Can't open $dir: $!";
    }

	return(@res);
}

sub scan_fc {
	my $fc = shift();

	my $file = "/sys/class/scsi_host/$fc/scan";

	if( open(FILE, ">", $file) ){
        plog("Scanning $fc...") if( &debug_level > 3 );
        print FILE "- - -\n";
        close(FILE);

        sleep 3;
        plog("Scanning $fc... done.") if( &debug_level > 3 );
    } else {
        plog("Can't open $file: $!");
    }
}

sub get_fc_partitions {
	my $p = {};
	if( open my $f, '<', '/proc/partitions' ){
        while(<$f>) {
            chomp;
            if(my ($disk) = /^\s*\d+\s+\d+\s+\d+\s+(sd[a-z]+)$/) {
                my $uid = &get_device_uuid($disk);
                if(exists $p->{$uid}) {
                    push(@{$p->{$uid}}, $disk);
                } else {
                    $p->{$uid} = [$disk];
                }
            }
        }
        close $f;
    } else {
        plog("Can't read from /proc/partitions.");
    }
	return $p;
}

# check raid /proc/mdstat
sub check_mdstat {
    my $self = shift;

	my ($l);
	my ($s,$n,$f);
    my ($status,$message);

	if( open MDSTAT,"</proc/mdstat" ){
        while( $l = <MDSTAT> ) {
            if( $l =~ /^(\S+)\s+:/ ) { $n = $1; $f = ''; next; }
            if( $l =~ /(\S+)\[\d+\]\(F\)/ ) { $f = $1; next; }
            if( $l =~ /\s*.*\[([U_]+)\]/ ) {
                $s = $1;
                #next if(!valid($n));
                if($s =~ /_/ ) {
                    $status = 2;    # CRITICAL
                    $message .= "md:$n:$f:$s ";
                } else {
                    $message .= "md:$n:$s ";
                }
            }
        }
        close MDSTAT;

        unless( $status == 0 ){
            return retErr("_ERR_CRITICAL_CHECK_MDSTAT_","Status of mdstat is critical: $message")
        }
        return retOk("_OK_CHECK_MDSTAT_","Status of mdstat is normal.");
    } else {
        return retErr("_ERR_CHECK_MDSTAT_","Couldn't open /proc/mdstat.");
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

L<VirtAgentInterface>, L<VirtAgent::Disk>, L<VirtAgent::Network>,
L<VirtMachine>
C<http://libvirt.org>


=cut

=pod

