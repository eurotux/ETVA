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

use Utils;

use Data::Dumper;

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
    
    loaddiskdev();

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
    
    loaddiskdev();

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
    
    loaddiskdev();

    return wantarray() ? %PhyDisk : \%PhyDisk;
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
    
    loaddiskdev();

    return wantarray() ? %PVInfo : \%PVInfo;
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
    
    loaddiskdev();

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
    
    loaddiskdev();

    return wantarray() ? %LVInfo : \%LVInfo;
}

# load disk device
#   function to initialize disk device info
sub loaddiskdev {
    my $self = shift;
    my ($force) = @_;

    # get physical devices
    if( $force || !%PhyDevices ){ phydev(); }

    # get physical volumes 
    if( $force || !%PVInfo ){ pvinfo(); }

    # get volume groups
    if( $force || !%VGInfo ){ vginfo(); }

    # get logical volumes 
    if( $force || !%LVInfo ){ lvinfo(); }

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
    
    # get info from libparted
    libparted_phydevinfo();
    
    close(F);

    return wantarray() ? %PhyDevices: \%PhyDevices;
}

sub libparted_phydevinfo {

    # libparted required
    require parted;

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
        }
    }
}

# multipath maps info
sub pathmapsinfo {

    # testing multipath
    if( havemultipath() ){

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
    }
    return wantarray() ? %PathMaps : \%PathMaps;
}
# Physical volumes info
sub pvinfo {

    %PVInfo = ();

    my $opts = "pv_fmt,pv_uuid,pv_size,dev_size,pv_free,pv_used,pv_name,pv_attr,pv_pe_count,pv_pe_alloc_count,pv_tags,vg_name";
    open(I,"/usr/sbin/pvs --separator=';' --units=b --noheadings --options=$opts 2>/dev/null|");

    my @hf = split(/,/,$opts);
    while(<I>){
        chomp;
        my $sline = $_;

        my %H = ();

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
        my $pv = pop @p;
        $H{"device"} = $device;

        # grant this fields
        $H{"psize"} ||= $H{"pv_size"};
        $H{"pfree"} ||= $H{"pv_free"};
        $H{'attr'} ||= $H{'pv_attr'};

        # size from string to int
        $H{"size"} = str2size($H{"psize"});
        $H{"freesize"} = str2size($H{"pfree"});

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

        my %H = ();

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

        # size to int
        $H{"size"} = str2size($H{"vsize"});
        $H{"freesize"} = str2size($H{"vfree"});

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

    return wantarray() ? %VGInfo : \%VGInfo;
}
# Logical volumes info
sub lvinfo {
    %LVInfo = ();

    my $opts = "lv_uuid,lv_name,lv_attr,lv_major,lv_minor,lv_kernel_major,lv_kernel_minor,lv_size,seg_count,origin,snap_percent,copy_percent,move_pv,lv_tags,segtype,stripes,stripesize,chunksize,seg_start,seg_size,seg_tags,devices,regionsize,mirror_log,modules,convert_lv,vg_name";
    open(I,"/usr/sbin/lvs --separator=';' --units=b --noheadings --options=$opts 2>/dev/null|");

    my @hf = split(/,/,$opts);
    while(<I>){
        chomp;
        my $sline = $_;

        my %H = ();

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

    return wantarray() ? %LVInfo: \%LVInfo;
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
sub update_devices {
    my %BDPhy = ();
    my %AllDiskDevices = %PhyDevices;
    for my $dn (keys %AllDiskDevices){
        my $major = $AllDiskDevices{"$dn"}{"major"};
        my $minor = $AllDiskDevices{"$dn"}{"minor"};
        $BDPhy{"${major}:${minor}"} = $dn;
        if( my $uuid = $AllDiskDevices{"$dn"}{"uuid"} ){
            if( $PathMaps{"$uuid"} ){
                $AllDiskDevices{"$dn"}{"multipath"} = 1;    # mark as using multipath
                $AllDiskDevices{"$dn"}{"devmapper"} = $PathMaps{"$uuid"}{"device"};
                $AllDiskDevices{"$dn"}{"multipathname"} = $PathMaps{"$uuid"}{"name"};
                push(@{$PathMaps{"$uuid"}{"phydevices"}},$AllDiskDevices{"$dn"});
            }
        }
        if( $PVInfo{"$dn"} ){
            $PVInfo{"$dn"}{"pvinit"} = 1;    # initialized
            $PhyDevices{"$dn"}{"pvinit"} = 1;    # initialized
            $AllDiskDevices{"$dn"}{"pvinit"} = 1;    # initialized
            $AllDiskDevices{"$dn"}{"allocatable"} = $PVInfo{"$dn"}{"allocatable"} if( defined $PVInfo{"$dn"}{"allocatable"} );
            $AllDiskDevices{"$dn"}{"exported"} = $PVInfo{"$dn"}{"exported"} if( defined $PVInfo{"$dn"}{"exported"} );
            $AllDiskDevices{"$dn"}{"pv"} = $PVInfo{"$dn"}{'pv'};
            $AllDiskDevices{"$dn"}{"vg"} = $PVInfo{"$dn"}{'vg'};
            $AllDiskDevices{"$dn"}{"pvsize"} = $PVInfo{"$dn"}{'size'};
            $AllDiskDevices{"$dn"}{"pvfreesize"} = $PVInfo{"$dn"}{'freesize'};
        }
        my $dev = $AllDiskDevices{"$dn"}{"device"};
        if( $MountDev{"$dev"} ){
            $AllDiskDevices{"$dn"}{"mounted"} = 1;
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
        my $device = $PathMaps{"$muid"}{"device"};
        $AllDiskDevices{"$muid"} = $PathMaps{"$muid"};
        $AllDiskDevices{"$muid"}{"multipath"} = 1;       # mark as multipath device
        my $phydevm = $PathMaps{"$muid"}{"sysfs"};
        $AllDiskDevices{"$phydevm"}{"mpathsysdev"} = 1; # mark as multipath sys device
        $AllDiskDevices{"$phydevm"}{"devmapper"} = $PathMaps{"$muid"}{"device"};
        $AllDiskDevices{"$phydevm"}{"mmapper"} = $PathMaps{"$muid"};

        $PathMaps{"$muid"}{"pvinit"} = 0;        # not initialized by default

        my $name = $PathMaps{"$muid"}{"name"};
        if( $PVInfo{"$name"} ){
            $PathMaps{"$muid"}{"pvinit"} = 0;    # initialized
        }
    }

    for my $lv (keys %LVInfo){
        my $major = $LVInfo{"$lv"}{"lv_kernel_major"};
        my $minor = $LVInfo{"$lv"}{"lv_kernel_minor"};
        my $pd = $LVInfo{"$lv"}{"phydev"} = $BDPhy{"${major}:${minor}"}; 
         
        $PhyDevices{"$pd"}{"device"} = $LVInfo{"$lv"}{"device"};
        $PhyDevices{"$pd"}{"logicaldevice"} = 1;

        my $vg = $LVInfo{"$lv"}{"vg"};
        $LVInfo{"$lv"}{"vgsize"} = $VGInfo{"$vg"}{"size"} || 0;
        $LVInfo{"$lv"}{"vgfreesize"} = $VGInfo{"$vg"}{"freesize"} || 0;

        # reference to volume group
        $LVInfo{"$lv"}{"volumegroup"} = $VGInfo{"$vg"};

        $AllDiskDevices{"$lv"} = $LVInfo{"$lv"};

        my $dev = $AllDiskDevices{"$lv"}{'device'};
        if( $MountDev{"$dev"} ||
                $MountDev{"/dev/$lv"} ){
            $AllDiskDevices{"$lv"}{'mounted'} = 1;
        }
    }

    for my $pv (keys %PVInfo){
        if( my $vg = $PVInfo{"$pv"}{"vg"} ){
            $VGInfo{"$vg"}{'physicalvolumes'}{"$pv"} = $PVInfo{"$pv"};
        }
    }

    for my $name (keys %AllDiskDevices){
        my $kn = $name;
        my $D = $AllDiskDevices{"$kn"};

        if( $D->{'multipath'} ){
            my $uuid = $D->{'uuid'};
            my $devs = $PathMaps{"$uuid"}{"phydevices"};
            $devs = [] if( !$devs );
            $kn = $PathMaps{"$uuid"}{'name'};
            $D->{'multipath'} = 1;
            $D->{'device'} = $PathMaps{"$uuid"}{'device'};
            $D->{'paths'} = scalar(@$devs);

            # TODO type 
            $D->{"type"} = "SAN";
        } else {
            $D->{"type"} = "local";
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
        @fields = qw( pvinit size freesize logical device allocatable exported mounted partition partitioned uuid pv pvsize pvfreesize vg vgsize vgfreesize lv type lvm swap nopartitions fs_type dtype type diskdevice );  
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
    
    if( $D->{'mpathsysdev'} ){
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

    loaddiskdev();

    if( $PhyDevices{"$dn"} && ( $PhyDevices{"$dn"}{"device"} eq $device ) ){ 
        if( !$PVInfo{"$dn"} ){

            my ($e,$m) = cmd_exec("/usr/sbin/pvcreate $device");

            $self->loaddiskdev(1);  # update disk device info
            unless( $e == 0 ){
                return retErr("_ERR_DISK_PVCREATE_","Error creating physical volume.");
            }
            my $PV = $PVInfo{"$dn"};
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

    loaddiskdev();

    if( my $PV = $PVInfo{$dn} ){
        my ($e,$m) = cmd_exec("/usr/sbin/pvremove",$device);
        $self->loaddiskdev(1);  # update disk device info
        unless( $e == 0 ){
            return retErr("_ERR_DISK_PVREMOVE_","Error remove physical volume.");
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

    loaddiskdev();

    if( $PVInfo{$dn} ){
        my ($e,$m) = cmd_exec("/usr/sbin/pvresize","--setphysicalvolumesize",$size,$device);
        $self->loaddiskdev(1);  # update disk device info
        unless( $e == 0 ){
            return retErr("_ERR_DISK_PVRESIZE_","Error resizing physical volume.");
        }
        my $PV = $PVInfo{"$dn"};
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

    loaddiskdev();

    my @lpv = ();
    for my $pd (@pv){
        my @pp = split(/\//,$pd);
        my $pn = pop(@pp);
        if( $PVInfo{"$pn"} ){
            push(@lpv,$pd);
        }
    }
    if( !$VGInfo{$vgname} ){
        my ($e,$m) = cmd_exec("/usr/sbin/vgcreate",$vgname,@lpv);

        $self->loaddiskdev(1);  # update disk device info
		unless( $e == 0 ){
            return retErr("_ERR_DISK_VGCREATE_","Error creating volume group.");
        }
        my $VG = $VGInfo{$vgname};
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

    loaddiskdev();

    if( my $VG = $VGInfo{$vgname} ){
        my ($e,$m) = cmd_exec("/usr/sbin/vgremove",$vgname);

        $self->loaddiskdev(1);  # update disk device info
        unless( $e == 0 ){
            return retErr("_ERR_DISK_VGREMOVE_","Error removing volume group.");
        }
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

    loaddiskdev();

    my @lpv = ();
    for my $pd (@pv){
        my @pp = split(/\//,$pd);
        my $pn = pop(@pp);
        if( $PVInfo{"$pn"} ){
            push(@lpv,$pd);
        }
    }
    if( $VGInfo{$vgname} ){
        my ($e,$m) = cmd_exec("/usr/sbin/vgextend",$vgname,@lpv);

        $self->loaddiskdev(1);  # update disk device info
        unless( $e == 0 ){
            return retErr("_ERR_DISK_VGEXTEND_","Error extend volume group.");
        }

        my $VG = $VGInfo{$vgname};
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

    loaddiskdev();

    my @lpv = ();
    for my $pd (@pv){
        my @pp = split(/\//,$pd);
        my $pn = pop(@pp);
        if( $PVInfo{"$pn"} ){
            push(@lpv,$pd);
        }
    }
    if( $VGInfo{$vgname} ){
        my ($e,$m) = cmd_exec("/usr/sbin/vgreduce",$vgname,@lpv);

        $self->loaddiskdev(1);  # update disk device info
        unless( $e == 0 ){
            return retErr("_ERR_DISK_VGREDUCE_","Error reduce volume group.");
        }

        my $VG = $VGInfo{$vgname};
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

    # get physical volume name
    my @pd = split(/\//,$pv);
    my $dn = pop(@pd);

    loaddiskdev();

    if( $VGInfo{$vgname} ){

        if( my $PV = $PVInfo{"$dn"} ){
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
    return $self->getvgs();
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
sub lvcreate {
    my $self = shift;
    my ($lv,$vg,$size) = my %p = @_;
    $lv = $p{"lv"} if( $p{"lv"} );
    $vg = $p{"vg"} if( $p{"vg"} );
    $size = $p{"size"} if( $p{"size"} );

    loaddiskdev();

    if( $VGInfo{$vg} ){
        my ($e,$m) = cmd_exec("/usr/sbin/lvcreate","-L",$size,"-n",$lv,$vg);

        $self->loaddiskdev(1);  # update disk device info
        unless( $e == 0 ){
            # return error if not created
            if( !$LVInfo{$lv} ){
                return retErr("_ERR_DISK_LVCREATE_","Error creating logical volume.");
            }
        }
    } else {
        return retErr("_INVALID_VG_","Invalid volume group");
    }
    # Get last logical volume created
    my $LV = $LVInfo{$lv};
    return retOk("_OK_LVCREATE_","Logical volume successfully created.","_RET_OBJ_",$LV);
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

    loaddiskdev();

    if( my $LV = $LVInfo{"$lvn"} ){
        my $tlv = $lv;
        if( $vg ){
            $tlv = "${vg}/${lvn}";
        } else {
            $vg = $LV->{"vg"};  # set vg
        }
        my ($e,$m) = cmd_exec("/usr/sbin/lvremove","-f",$tlv);

        $self->loaddiskdev(1);  # update disk device info
        unless( $e == 0 ){
            # send error if LV remove not successful
            unless( !$LVInfo{"$lvn"} ){
                return retErr("_ERR_DISK_LVREMOVE_","Error remove logical volume.");
            }
        }

        # send update vg freesize
        $LV->{"vgfreesize"} = $VGInfo{"$vg"}{"freesize"} || 0;

        # reference to updated volume group
        $LV->{"volumegroup"} = $VGInfo{"$vg"};

        return retOk("_OK_LVREMOVE_","Logical volume successfully removed.","_RET_OBJ_",$LV);
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
    $lv = $p{"lv"} if( $p{"lv"} );
    $size = $p{"size"} if( $p{"size"} );

    my @lvp = split(/\//,$lv);
    my $lvn = pop @lvp;

    loaddiskdev();

    if( $LVInfo{$lvn} ){
        my ($e,$m) = cmd_exec("/usr/sbin/lvresize","-f","-L",$size,$lv);

        $self->loaddiskdev(1);  # update disk device info
        unless( $e == 0 ){
            return retErr("_ERR_DISK_LVRESIZE_","Error resize logical volume.");
        }

    } else {
        return retErr("_INVALID_LV_","Invalid logical volume");
    }
    my $LV = $LVInfo{$lvn};
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

    my @olvp = split(/\//,$olv);
    my $olvn = pop @olvp;

    loaddiskdev();

    if( $LVInfo{$olvn} ){
        # TODO
        #   this can block process...
        my ($e,$m);
        unless( ( ($e,$m) = cmd_exec("/usr/sbin/lvcreate","--size",$size,"--snapshot","--name",$slv,$olv) ) && ( $e == 0 ) ){
            return retErr("_ERR_CREATE_SNAPSHOT_","Error creating snapshot: $m");
        }

        $self->loaddiskdev(1);  # update disk device info
    } else {
        return retErr('_INVALID_LOG_VOL_',"Invalid logical volume: $olvn");
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

    my @olvp = split(/\//,$olv);
    my $olvn = pop @olvp;

    loaddiskdev();

    if( $LVInfo{$olvn} ){
        # TODO
        #   this can block process...
        my ($e,$m);
        unless( ( ($e,$m) = cmd_exec("/usr/sbin/lvconvert --snapshot",$olv,$slv) ) && ( $e == 0 ) ){
            return retErr("_ERR_CONVERT_SNAPSHOT_","Error convert snapshot: $m");
        }

        $self->loaddiskdev(1);  # update disk device info
    } else {
        return retErr('_INVALID_LOG_VOL_',"Invalid logical volume: $olvn");
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
        }
        $HAVEMULTIPATH = 0;
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

