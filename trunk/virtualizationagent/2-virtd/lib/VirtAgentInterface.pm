#!/usr/bin/perl
# Copywrite Eurotux 2009
# 
# CMAR 2009/04/23 (cmar@eurotux.com)
#

=pod

=head1 NAME

VirtAgentInterface - interface for VirtAgent module

=head1 SYNOPSIS

...

=head1 DESCRIPTION

...

=head1 METHODS

=over 4

=cut

package VirtAgentInterface;

use strict;

BEGIN {
    # this is the worst damned warning ever, so SHUT UP ALREADY!
    $SIG{__WARN__} = sub { warn @_ unless $_[0] =~ /Use of uninitialized value/ };

    require Exporter;
    require VirtAgent;
    require VirtAgent::Disk;
    require VirtAgent::Network;
    require VirtAgent::Storage;
    require VirtAgent::Devices;
    use vars qw($VERSION @ISA @EXPORT @EXPORT_OK %EXPORT_TAGS $CRLF $AUTOLOAD);
    $VERSION = '0.0.1';
    @ISA = qw( VirtAgent VirtAgent::Disk VirtAgent::Network VirtAgent::Storage );
    @EXPORT = qw( );
}

use ETVA::Utils;
use ETVA::NetworkTools;
use ETVA::ArchiveTar;

use GuestAgent::Client;
use GuestAgent::MessageFactory;
use JSON;

use VirtMachine;

use File::Copy;
use XML::Generator;
use LWP::Simple;
use Data::Dumper;

use File::Path qw( mkpath rmtree );

use Cwd qw(abs_path);

use POSIX qw/SIGHUP SIGTERM SIGKILL/;
use POSIX ":sys_wait_h";
# CMAR 09/11/2010 
#  disable to use all POSIX functions
#use POSIX;
use Fcntl ':flock';

use Time::localtime;
use File::stat;

use Time::HiRes qw(gettimeofday);

use constant {
    NODE_ACTIVE => 1,
    NODE_INACTIVE => 0,
    NODE_FAIL => -1,
    NODE_MAINTENANCE => -2,
    NODE_COMA => -3
};

my $hypervisor_type;    # hypervisor type

my $PARENT = 1;

my $VM_DIR = "./vmdir";
my $CONF;
my $TMP_DIR = "/var/tmp";

my $VIRTIO_CHANNELS_SOCKETS_DIR = "/var/tmp/virtagent-virtio/virtio-sockets-dir";
my $VIRTIO_CHANNELS_STATE_DIR = "/var/tmp/virtagent-virtio/virtio-state-dir";

# guest management sockets dir
my $GUEST_MANAGEMENT_SOCKETS_DIR = "/var/tmp/virtagent-guestmngt-sockets-dir";

# guest console sockets dir
my $GUEST_CONSOLE_SOCKETS_DIR = "/var/tmp/virtagent-console-sockets-dir";

sub AUTOLOAD {
    my $method = $AUTOLOAD;
    my $self = shift;

    if( my ($request_class,$m1,$m2) = ($method =~ m/(.*)::(.+)_(as_\w+)/) ){
        my $R = $self->$m1(@_);
        if( isError($R) ){
            return $R;
        } else {
            return $self->$m2($m1,$R);
        }
    } else {
        die "method $method not found\n";
    }
}

sub new {
    my $self = shift;

    unless( ref($self) ){
        my $class = ref( $self ) || $self;
        $self = bless {@_} => $class;
    }
    return $self;
}

sub as_string {
    my $self = shift;
   my $method = shift;
    my @R = @_;
    my $str = Dumper(@R);
    return $str;
}
# as_xml
#   return result as xml
#   args: maintag, list of hash
#   res: xml string
sub as_xml {

    sub as_xml_rec {
        my ($E,$tag) = @_;

        my @list = ();
        my $X = XML::Generator->new(':pretty');

        if( ref($E) eq "HASH" ){
            for my $k (keys %$E){
                if( ref( $E->{"$k"} ) eq 'ARRAY' ){
                    push @list, as_xml_rec($E->{"$k"},$k);
                } else {
                    push @list, $X->$k( as_xml_rec($E->{"$k"}) );
                }
            }
        } elsif( ref($E) eq "ARRAY" ){
            my $i=0;
            for my $e (@$E){
                # FIX: not so sure to be this
                push @list, $tag ? $X->$tag( as_xml_rec($e) ) : $X->item( { i=>$i }, as_xml_rec($e) ) ;
                $i++;
            }
            @list = ( $X->list( @list ) ) if( !$tag );
        } else {    # not ref
            push @list, $tag && $E ? $X->$tag( $E ) : $E; 
        }
        return @list;
    }

    my $self = shift;
    my $tag = shift;
    my @R = @_;

    my $X = XML::Generator->new(':pretty');

    my $xml = "";
    my @list = ();
    for my $E (@R){
        push @list, as_xml_rec($E);
    }
    $xml = sprintf( '%s', $X->xml( $X->$tag( @list ) ) );

    return $xml;
}

=item start_vm

virtual machine start function

    my $OK = VirtAgentInterface->start_vm( name=>$name );

=begin WSDL

    _INOUT start_vm @string virtual machine start function

    _RETURN @string ok or error message

=end WSDL

=cut

# start_vm
#   func wrapper to start vm
sub start_vm {
    my $self = shift;
    my %p = @_;

    return $self->vmStart( %p );
}

#   virtual machine start function
#   args: hash { name  }
#   return: ok || error
sub vmStart {
    my $self = shift;
    my %p = @_;

    $p{'name'} = $self->{'_lastdomain'} if( !$p{'name'} && ref($self) );
    $p{'uuid'} = $self->{'_lastuuid'} if( !$p{'uuid'} && ref($self) );

    my $VM = $self->getVM(%p);

    if( $VM->isrunning() || $self->vmIsRunning(%p) ){
        return retErr("_ERR_VM_IS_RUNNING_","Error virtual machine is running.");
    }

    # set location
    $VM = $self->vmSetLocation( 'VM'=>$VM, %p );
    if( isError($VM) ){
        return wantarray() ? %$VM : $VM;
    }

    # on_reboot and on_crash flag: should restart VM
    $VM->set_on_reboot('restart');
    $VM->set_on_crash('restart');

    if( $p{'boot'} eq 'filesystem' ){
        $self->set_vm_bootloader( VM=>$VM, %p );
        $VM->del_install(0);
        $VM->del_kernel();
        $VM->del_initrd();
        $VM->del_cmdline();
    } else {
        $VM->del_bootloader(0);
        $VM->set_install(1);
    }

    # set vnc options
    for my $k ( qw( nographics vnc_port vnc_listen vnc_keymap ) ){
        if( defined $p{"$k"} ){
            my $f_set = "set_$k";
            $VM->$f_set( $p{"$k"} );
        }
    }

    plog "VM(1)=",Dumper($VM) if( &debug_level > 3 );

    my %V = $self->defineDomain( $VM->todomain() );
    if( isError(%V) ){
        return wantarray() ? %V : \%V;
    }

    my $E = $self->startDomain(%p);

    if( isError($E) ){
        return wantarray() ? %$E : $E;
    }

    my $xml = $self->get_xml_domain( 'uuid'=>$VM->get_uuid(), 'name'=>$VM->get_name() );
    plog "init xml=",$xml if( &debug_level > 3 );
    if( isError($xml) ){
        return wantarray() ? %$xml : $xml;
    }

    $VM = $VM->loadfromxml( $xml );

    if( $p{'first_install'} || $p{'first_boot'} || $VM->get_firstboot() ){

        # set location
        $VM = $self->vmSetLocation( 'VM'=>$VM );
        if( isError($VM) ){
            return wantarray() ? %$VM : $VM;
        }

        # set boot from disk
        $VM->set_bootdev( 'hd' );

        $VM->set_firstboot(1);
        $self->set_vm_bootloader( VM=>$VM, %p );
        $VM->set_install(0);
        $VM->del_kernel();
        $VM->del_initrd();
        $VM->del_cmdline();

        $VM->set_on_reboot('restart');
        $VM->set_on_crash('restart');

        plog "VM(2)=",Dumper($VM) if( &debug_level > 3 );

        my @ptod = $VM->todomain();

        my $boot_xml = $self->genXMLDomain( @ptod );
        plog "boot_xml=",$boot_xml if( &debug_level > 3 );

        %V = $self->defineDomain( @ptod );
        if( isError(%V) ){
            return wantarray() ? %V : \%V;
        }
    } else {
        $VM->set_firstboot(0);
    }

    $VM->set_state("running");

    my %H = $VM->tohash();

    $self->setVM( 'uuid'=>$VM->get_uuid(), 'name'=>$VM->get_name(), 'VM'=>$VM );

    return retOk("_VM_START_OK_","Virtual machine successfully started","_RET_OBJ_",\%H);
}

=item stop_vm

virtual machine stop function

    my $OK = VirtAgentInterface->stop_vm( name=>$name );

=cut

# stop_vm
# vmStop alias func
sub stop_vm {
    my $self = shift;
    my %p = @_;

    return $self->vmStop( %p );
}

# vmStop 
# virtual machine stop function
# args: hash { name  }
# return: ok || error
sub vmStop {
    my $self = shift;
    my %p = @_;

    $p{'name'} = $self->{'_lastdomain'} if( !$p{'name'} && ref($self) );
    $p{'uuid'} = $self->{'_lastuuid'} if( !$p{'uuid'} && ref($self) );

    my $VM = $self->getVM(%p);
    if( !$VM ){
        return retErr("_ERR_VM_NOT_FOUND_","Error virtual machine not found.");
    }

    my $uuid = $VM->get_uuid();
    my $name = $VM->get_name();

    my $E = $self->stopDomain( 'uuid'=>$uuid,'name'=>$name, force=>$p{'force'}, destroy=>$p{'destroy'} );

    # update state any way
    #$VM->set_state("stop");

    $self->setVM( 'uuid'=>$VM->get_uuid(), 'name'=>$VM->get_name(), 'VM'=>$VM );

    if( isError($E) ){
        return wantarray() ? %$E : $E;
    } else {
=com
        my $dom = $self->getDomain( 'uuid'=>$uuid,'name'=>$name );
        if( isError($dom) ){
            $self->delVM( 'uuid'=>$uuid, 'name'=>$name );
        }
=cut
        my %H = $VM->tohash();
        return retOk("_VM_STOP_OK_","Virtual machine successfully stoped","_RET_OBJ_",\%H);
    }
}

=item suspend_vm

virtual machine suspend function

    my $OK = VirtAgentInterface->suspend_vm( name=>$name );

=cut

# suspend_vm
# vmSuspend alias func
sub suspend_vm {
    my $self = shift;
    my %p = @_;

    return $self->vmSuspend( %p );
}

# vmSuspend 
# virtual machine suspend function
# args: hash { name  }
# return: ok || error
sub vmSuspend {
    my $self = shift;
    my %p = @_;

    $p{'name'} = $self->{'_lastdomain'} if( !$p{'name'} && ref($self) );
    $p{'uuid'} = $self->{'_lastuuid'} if( !$p{'uuid'} && ref($self) );

    my $VM = $self->getVM(%p);
    if( !$VM ){
        return retErr("_ERR_VM_NOT_FOUND_","Error virtual machine not found.");
    }

    my $uuid = $VM->get_uuid();
    my $name = $VM->get_name();

    my $E = $self->suspendDomain( 'uuid'=>$uuid,'name'=>$name, force=>$p{'force'}, destroy=>$p{'destroy'} );

    # update state any way
    $VM->set_state("suspended");

    $self->setVM( 'uuid'=>$VM->get_uuid(), 'name'=>$VM->get_name(), 'VM'=>$VM );

    if( isError($E) ){
        return wantarray() ? %$E : $E;
    } else {
        my %H = $VM->tohash();
        return retOk("_VM_SUSPEND_OK_","Virtual machine successfully suspended","_RET_OBJ_",\%H);
    }
}

=item resume_vm

virtual machine resume function

    my $OK = VirtAgentInterface->resume_vm( name=>$name );

=cut

# resume_vm
# vmResume alias func
sub resume_vm {
    my $self = shift;
    my %p = @_;

    return $self->vmResume( %p );
}

# vmResume 
# virtual machine resume function
# args: hash { name  }
# return: ok || error
sub vmResume {
    my $self = shift;
    my %p = @_;

    $p{'name'} = $self->{'_lastdomain'} if( !$p{'name'} && ref($self) );
    $p{'uuid'} = $self->{'_lastuuid'} if( !$p{'uuid'} && ref($self) );

    my $VM = $self->getVM(%p);
    if( !$VM ){
        return retErr("_ERR_VM_NOT_FOUND_","Error virtual machine not found.");
    }

    my $uuid = $VM->get_uuid();
    my $name = $VM->get_name();

    my $E = $self->resumeDomain( 'uuid'=>$uuid,'name'=>$name, force=>$p{'force'}, destroy=>$p{'destroy'} );

    # update state any way
    $VM->set_state("running");

    $self->setVM( 'uuid'=>$VM->get_uuid(), 'name'=>$VM->get_name(), 'VM'=>$VM );

    if( isError($E) ){
        return wantarray() ? %$E : $E;
    } else {
        my %H = $VM->tohash();
        return retOk("_VM_RESUME_OK_","Virtual machine successfully resumed","_RET_OBJ_",\%H);
    }
}

sub vmSetLocation {
    my $self = shift;
    my (%p) = @_;

    # hypervisor type
    my $type = $self->get_type();

    my $VM = $p{'VM'};

    if( $VM ){

        # os type
        my $ostype = $VM->get_os_type();

        # get kernel
        if( $p{'kernel'} ){
            $VM->set_kernel(  $p{'kernel'} );
            $VM->set_initrd(  $p{'initrd'} );
            $VM->set_cmdline( $p{'cmdline'} );
        } else {
        # or boot info

            # location
            my $location = $p{'location'} || $p{'cdrom'} || $p{'bootdisk'};

            # define boot device
            if( $p{'boot'} eq 'filesystem' ){
                $VM->set_bootdev( 'hd' );
            } elsif( $p{'boot'} eq 'cdrom' ){
                $VM->set_bootdev( 'cdrom' );
            } elsif( $p{'boot'} eq 'pxe' ){
                $p{'pxe'} = 1;
                $VM->set_bootdev( 'network' );
            }

            # enable boot menu
            if( !defined($p{'bootmenu'}) || ($p{'bootmenu'} ne 'no') ){
                $VM->set_bootmenu( 'yes' );
            } else {
                $VM->set_bootmenu( 'no' );
            }

            # change location or boot from filesystem
            # otherwise no changes applied

            # if boot from cdrom... cdrom use location
            if( $p{'location'} && ( $p{'boot'} eq 'cdrom' ) ){
                $location = $p{'cdrom'} = $p{'location'};
            }

            # set as install
            $p{'install'} = 1 if( $p{'first_install'} || $p{'first_boot'} || $VM->get_firstboot() );

            $location =~ s/^\s+//; $location =~ s/\s+$//; # clean spaces
            if( $location ){
                if( $p{'cdrom'} ){          # try boot from cdrom
                    if( 1 || -e "$location" ){  # force to set empty location
                        my $prev_cdrom = $VM->get_cdrom();
                        if( !defined($prev_cdrom) ){
                            my %cdrom_attr = ( device=>'cdrom', readonly=>1, 'hypervisor_type'=>$type, 'os_type'=>$ostype );
                            if( $type =~ 'xen' ){
                                $cdrom_attr{'OLDDISKS'} = $VM->get_Disks();
                                if( my @disks = $self->get_loadvm_disks_params(%p) ){
                                    $cdrom_attr{'NEWDISKS'} = \@disks;
                                }
                            }
                            my %CD = $self->set_disk_node( %cdrom_attr );
                            $CD{'path'} = $location if( -e "$location" );
                            $VM->add_disk( %CD );
                        } elsif( $prev_cdrom ne $location ){
                            my $VD = $VM->get_disk( 'path'=> $prev_cdrom );
                            $VD->set_path( $location ) if( -e "$location" );
                        }
                        $VM->set_cdrom( $location );
                    } else {
                        return retErr("_ERR_VMLOAD_LOCATION_","Error add location '$location' to cdrom.");
                    }
                } elsif( $p{'bootdisk'} ){  # boot from bootdisk
                    my $bootdisk = VirtAgent->get_bootdisk($location,$type,$p{'arch'},$p{'distro'});
                    if( -e "$bootdisk" ){
                        my $prev_cdrom = $VM->get_cdrom();
                        if( !defined($prev_cdrom) ){
                            my %cdrom_attr = ( path=>$bootdisk, device=>'cdrom', readonly=>1, 'hypervisor_type'=>$type, 'os_type'=>$ostype );
                            if( $type =~ 'xen' ){
                                $cdrom_attr{'OLDDISKS'} = $VM->get_Disks();
                                if( my @disks = $self->get_loadvm_disks_params(%p) ){
                                    $cdrom_attr{'NEWDISKS'} = \@disks;
                                }
                            }
                            my %CD = $self->set_disk_node( %cdrom_attr );
                            $VM->add_disk( %CD );
                        } elsif( $prev_cdrom ne $bootdisk ){
                            my $VD = $VM->get_disk( 'path'=> $prev_cdrom );
                            $VD->set_path( $bootdisk );
                        }
                        $VM->set_cdrom( $bootdisk );
                    } else {            # go to boot loader
                        return retErr("_ERR_VMLOAD_BOOTDISK_","Error get bootdisk from location: '$location'.");
                    }
                } else {                    # get kernel and initrd from location
                    my $extra = $p{'extra'};
                    if( $p{'kickstart'} ){  # add kickstart
                        $extra .= " " if( $extra );
                        $extra .= "ks=$p{'kickstart'}";
                    }
                    (my $kernel,my $initrd,$extra) = VirtAgent->get_kernel($location,$type,$p{'arch'},$p{'distro'},$extra);
                    if( -e "$kernel" ){ # testing kernel ok
                        $VM->set_kernel(  $kernel );
                        $VM->set_initrd(  $initrd );
                        $VM->set_cmdline( $extra );
                    # CMAR 02/06/2011 : ignore error location when cant get kernel
                    #} else {            # go to boot loader
                    #    return retErr("_ERR_VMLOAD_LOCATION_","Error get kernel an initrd from location: '$location'.");
                    }
                }
            }
		    $VM->set_install( $p{'install'} || $p{'pxe'} );
            if( $VM->get_kernel() ){
                $VM->del_bootloader(0);
            } else {
	    	    $self->set_vm_bootloader( VM=>$VM, %p );
            }
    		$VM->set_pxe( $p{'pxe'} );
        }
    }
    return $VM
}
sub get_loadvm_disks_params {
    my $self = shift;
    my %p = @_;

    my @Disks = ();
    if( ref($p{'disk'}) eq 'ARRAY' ){
        @Disks = @{$p{'disk'}};
    } else {
        my %D = ();

        $D{'path'} = $p{'path'} || $p{'disk'}{'path'};

        if( $D{'path'} ){
            $D{'device'} = $p{'diskdevice'} || $p{'disk'}{'device'};
            $D{'drivertype'} = $p{'diskdrivertype'} || $p{'disk'}{'drivertype'};
            $D{'drivername'} = $p{'diskdrivername'} || $p{'disk'}{'drivername'};
            $D{'drivercache'} = $p{'diskdrivercache'} || $p{'disk'}{'drivercache'};
            $D{'driverio'} = $p{'diskdriverio'} || $p{'disk'}{'driverio'};
            $D{'sourceaio'} = $p{'disksourceaio'} || $p{'disk'}{'sourceaio'};
            $D{'target'} = $p{'disktarget'} || $p{'disk'}{'target'};
            $D{'readonly'} = $p{'diskreadonly'} || $p{'disk'}{'readonly'};
            $D{'node'} = $p{'disknode'} || $p{'disk'}{'node'};
            $D{'bus'} = $p{'diskbus'} || $p{'disk'}{'bus'};

            if( !$D{'drivername'} &&
                    ($p{'diskdrivercache'} || $p{'disk'}{'drivercache'} || $D{'drivertype'} || $p{'diskformat'} || $p{'disk'}{'format'}) ){

                # determinate the format
                if( $p{'diskformat'} || $p{'disk'}{'format'} ){
                    $D{'drivertype'} = $p{'diskformat'} || $p{'disk'}{'format'};
                }

                if( $self->get_hypervisor_type() eq 'kvm' ){
                    $D{'drivername'} = "qemu";

                } elsif( $self->get_hypervisor_type() =~ m/xen/ ){
                    # use tap driver name only if specify the format
                    $D{'drivername'} = "tap" if( $D{'drivertype'} );
                }
            }

            push(@Disks,\%D);
        }
    }
    plogNow("[DEBUG] Disks=",Dumper(\@Disks));
    return wantarray() ? @Disks : \@Disks;
}

# vmLoad
#   load virtual machine config
#
sub vmLoad {
    my $self = shift;
    $self = $self->new();
    my %p = @_;

    # prepare load VM params 
    %p = $self->prep_loadvm_params(%p);

    my %A = ();

    if( $p{'extend'} ){             # extend to apply to libvirt
        %A = %{ $p{'extend'} };
    }

    # get name
    my $name = $A{'name'} = $p{'name'};

    # get memory
    $A{'memory'} = $p{'memory'};    # mem in bytes

    # get uuid
    $A{'uuid'} = $p{'uuid'};

    # get description
    $A{'description'} = $p{'description'};

    # get vcpus
    $A{'vcpu'} = $p{'vcpu'};

    my $type = $self->get_type();
    # if xen
    #   get cpuset
    if( $type eq 'xen' ){
        $A{'cpuset'} = $p{'cpuset'} if( defined $p{'cpuset'} );
    }

    # graphics information
    $A{'nographics'} = $p{'nographics'} if( $p{'nographics'} );
    $A{'vnc_port'} = $p{'vnc_port'} if( $p{'vnc_port'} );
    $A{'vnc_listen'} = $p{'vnc_listen'} if( $p{'vnc_listen'} );
    $A{'vnc_keymap'} = $p{'vnc_keymap'} if( $p{'vnc_keymap'} );

    # input mouse
    if( !$p{'no_mouse'} || defined($p{'mouse_bus'}) ){
        $A{'mouse_bus'} = $p{'mouse_bus'};
        if( !$p{'mouse_bus'} ){
            $A{'mouse_bus'} = ( ($self->get_hypervisor_type() =~ m/xen/) && ($p{'vm_type'} eq 'pv')  ) ? "xen" : "ps2";
        }
    }
    if( ($self->get_hypervisor_type() ne 'xen') && ($p{'vm_type'} ne 'pv') ){
        # this doenst work for xen hypervisor type
        # input tablet
        if( !$p{'no_tablet'} || defined($p{'tablet_bus'}) ){
            $A{'tablet_bus'} = $p{'tablet_bus'} || "usb";
        }
    }
    my %features = ();
    if( $p{'features'} ){
        %features = %{$p{'features'}};
    } else {
        my @keysfeatures = map { s/feature_(\w+)/$1/ } grep { /feature_/ } keys %p; 
        foreach my $kf (@keysfeatures){
            $features{"$kf"} = $p{"$kf"};
        }
    }
    if( %features ){
        $A{'features'} = { %features };
    }

    if( $p{'acpi'} || (!defined($p{'acpi'}) && !defined($A{'features'}{'acpi'})) ){   # set ACPI by default
        $A{'features'}{'acpi'} = 1;
    }
    if( $p{'apic'} || (!defined($p{'apic'}) && !defined($A{'features'}{'apic'})) ){   # set APIC by default
        $A{'features'}{'apic'} = 1;
    }
    if( $p{'pae'} || (!defined($p{'pae'}) && !defined($A{'features'}{'pae'})) ){   # set PAE by default
        $A{'features'}{'pae'} = 1;
    }

    if( $p{'vm_os'} =~ m/windows/i ){
        # for MS Windows 
        #  add ACPI support
        $A{'features'}{'acpi'} = 1;

        #  add tablet USB bus
        if( !$p{'no_tablet'} ){
            $A{'tablet_bus'} = $p{'tablet_bus'} || "usb";
        }
    }

    # set clock as localtime
    $A{'clock'}{'offset'} = 'localtime';

    # set cpu information
    if( $p{'cpu'} ){
        $A{'cpu'} = $p{'cpu'};
    }
    if( $p{'sockets'} && $p{'cores'} && $p{'threads'} ){
        $A{'cpu'}{'topology'} = { 'sockets'=>$p{'sockets'}, 'cores'=>$p{'cores'}, 'threads'=>$p{'threads'} };
    }

    if( my $controllers = $p{'controllers'} ){
        $A{'Controllers'} = [ @$controllers ];
    }

    # for kvm add channel and controller 
    if( !$p{'nochannel'} && ($self->get_hypervisor_type() eq 'kvm') ){
        my $controller = $A{'Controllers'} || [];
        $A{'Controllers'} = [ @$controller, { 'type'=>'virtio-serial', 'index'=>'0', 'ports'=>'16' } ];

        my $channelpath = "${VIRTIO_CHANNELS_SOCKETS_DIR}/$name";
        my $channels = $A{'Channels'} || [];
        $A{'Channels'} = [ @$channels, { 'type'=>'unix', 'target'=>{ 'type'=>'virtio', 'name'=>'com.redhat.rhevm.vdsm' }, 'source'=>{ 'mode'=>'bind', 'path'=>$channelpath } } ];
    }

    # add serial ports
    # GUEST_MANAGEMENT_SOCKETS_DIR
    if( !$p{'noserial'} ){
        my $serials = $A{'Serials'} || [];
        my $i = scalar(@$serials);

        if( ($p{'name'} =~ m/etfw/i) || ($p{'description'} =~ m/etfw/i) ){      # for ETFW virtual machines
            $i++;

            # add console serial port
            my $console_serial_socketpath = "${GUEST_CONSOLE_SOCKETS_DIR}/$name";
            push(@$serials, { 'type'=>'unix', 'source'=>{ 'mode'=>'bind', 'path'=>$console_serial_socketpath }, 'target'=>{ 'port'=>$i } });
        }

        $i++;
        my $serialsocketpath = "${GUEST_MANAGEMENT_SOCKETS_DIR}/$name";
        $A{'Serials'} = [ @$serials, { 'type'=>'unix', 'source'=>{ 'mode'=>'bind', 'path'=>$serialsocketpath }, 'target'=>{ 'port'=>$i } } ];
    }

    my $VM = VirtMachine->new( %A );
    my $uuid = $A{'uuid'} = $VM->get_uuid();
    # not running
    $VM->set_state("notrunning");

    # other stuff
    # CMAR 22/03/2010: change order of make Virtual Machine parameteres

    # os params: os_type os_variant ...

    $p{'os'}{'type'} = $self->os_type(%p);  # set os_type
    $p{'os'}{'loader'} = $self->os_loader(%p);  # set os_loader

    if( my $pos = $p{'os'} ){
        for my $k ( keys %$pos ){
            $VM->set_attr("os_$k",$pos->{"$k"});
        }
    }

    $p{'arch'} = $self->get_arch(%p) || 'i686' if( !$p{'arch'} );

    # set arch
    $VM->set_arch( $p{'arch'} );

    # set location
    $VM = $self->vmSetLocation( 'VM'=>$VM, %p );
    if( isError($VM) ){
        return wantarray() ? %$VM : $VM;
    }

    my @HostDevs = ();
    
    if( ref($p{'hostdevs'}) eq 'ARRAY' ){
        @HostDevs = @{$p{'hostdevs'}};
    }

    for my $hdev (@HostDevs){

        $VM->add_hostdev(%$hdev);
    }

    # disk devices
    my @Disks = $self->get_loadvm_disks_params(%p);

    my $htype = $self->get_type();
    my $ostype = $VM->get_os_type();
    for my $D (@Disks){
        $D = $self->set_disk_node( %$D, 'hypervisor_type'=>$htype, 'os_type'=>$ostype, 'NEWDISKS'=>\@Disks );
        my $VD = $VM->add_disk(%$D);
        # initialized if not yet
# CMAR 03/03/2010
#   dont initialize the disk at this point
#   use: lvcreate( vg=>'__DISK__', lv=>'...', size=>'...' );
#    to do that
#        if( $VD->get_size() > 0 ){  # only if size great of zero
#            $VD->initialize();
#        }
        
        $A{'disk'} .= ";" if( $A{'disk'} );
        $A{'disk'} .= fieldsAsString($D);
    }

    # network information
    my @Network = ();
    if( ref($p{'network'}) eq 'ARRAY' ){
        @Network = @{$p{'network'}};
    } else {
        my %N = ();
        $N{'type'} = $p{'nettype'} || $p{'network'}{'type'};
        $N{'bridge'} = $p{'netbridge'} || $p{'network'}{'bridge'};
        $N{'name'} = $p{'netname'} || $p{'network'}{'name'};
        $N{'macaddr'} = $p{'macaddr'} || $p{'network'}{'macaddr'};
        push( @Network, \%N );
    }

    for my $N (@Network){
        my $VN = $VM->add_network(%$N);
        $N->{'macaddr'} = $VN->get_macaddr();

        $A{'network'} .= ";" if( $A{'network'} );
        $A{'network'} .= fieldsAsString($N);
    }

    # filesystem devices
    my @Filesystem = ();
    if( ref($p{'filesystem'}) eq 'ARRAY' ){
        @Filesystem = @{$p{'filesystem'}};
    } else {
        my %F = ();

        $F{'target'} = $p{'fstarget'} || $p{'fs'}{'target'};
        if( $F{'target'} ){
            $F{'type'} = $p{'fstype'} || $p{'fs'}{'type'};
            $F{'name'} = $p{'fsname'} || $p{'fs'}{'name'};
            $F{'dir'} = $p{'fsdir'} || $p{'fs'}{'dir'};
            $F{'file'} = $p{'fsfile'} || $p{'fs'}{'file'};
            $F{'dev'} = $p{'fsdev'} || $p{'fs'}{'dev'};

            push(@Filesystem,\%F);
        }
    }
    for my $F (@Filesystem){
        my $VF = $VM->add_filesystem( %$F );
        $A{'filesystem'} .= ";" if( $A{'filesystem'} );
        $A{'filesystem'} .= fieldsAsString($F);
    }

    # stay shared uuid and domain name
    $self->{'_lastuuid'} = $uuid;
    $self->{'_lastdomain'} = $self->{'_lastname'} = $name;

    #plogNow("[DEBUG] VM=",Dumper($VM));
    return $VM;
}

sub set_disk_node {
    my $self = shift;
    my %p = @_;

    my $htype = delete $p{'hypervisor_type'};
    my $ostype = delete $p{'os_type'};

    my $new_disks = delete $p{'NEWDISKS'};
    my $old_disks = delete $p{'OLDDISKS'};

    # if target present
    if( $p{'target'} ){
        # override node
        ($p{'node'}) = ( $p{'target'} =~ m/^(\w+)\w$/ );
    }

    if( $p{'bus'} && !$p{'node'} ){    # try determinate best node for bus
        if( $p{'bus'} eq 'xen' ){
            $p{'node'} = 'xvd';
        } elsif( $p{'bus'} eq 'ide' ){
            $p{'node'} = 'hd';
        } elsif( $p{'bus'} eq 'scsi' ){
            $p{'node'} = 'sd';
        } elsif( $p{'bus'} eq 'virtio' ){
            $p{'node'} = 'vd';
        } else {
            $p{'bus'} = 'ide';
            $p{'node'} = 'hd';
        }
    } elsif( !$p{'node'} ){    # try determinate best node type
        if( $p{'device'} eq 'cdrom' ){
            $p{'node'} = 'hd';  # by default
            $p{'bus'} = 'ide';
            if( $htype eq 'kvm' ){
                if( !-e "$p{'path'}" ){
                    $p{'path'} = '/dev/null';
                }
            } elsif( $htype =~ 'xen' ){
                if( $new_disks ){
                    if( grep { $_->{'bus'} eq 'xen' } @$new_disks ){
                        $p{'node'} = 'xvd';
                        $p{'bus'} = 'xen';
                    }
                }
                if( $old_disks ){
                    if( grep { $_->{'bus'} eq 'xen' } @$old_disks ){
                        $p{'node'} = 'xvd';
                        $p{'bus'} = 'xen';
                    }
                }
            }
        }  else {
            if( ( $ostype eq 'hvm')
                    && ( $htype eq 'kvm' ) ){
                $p{'node'} = 'vd';
                $p{'bus'} = 'virtio';
            } elsif( $ostype eq 'linux' ){
                if( $htype eq 'xen' ){
                    $p{'node'} = 'xvd';
                    $p{'bus'} = 'xen';
                } else {
                    $p{'node'} = 'sd';
                    $p{'bus'} = 'scsi';
                }
            } else {
                $p{'node'} = 'hd';
                $p{'bus'} = 'ide';
            }
        }
    } elsif( $p{'node'} ){
        if( ( $p{'node'} eq 'vd' )
                && ( $ostype eq 'hvm')
                && ( $htype eq 'kvm' ) ){
            $p{'bus'} = 'virtio';
        } elsif( ( $p{'node'} eq 'xvd' )
                && ( $ostype eq 'linux' ) 
                && ( $htype eq 'xen' ) ){
            $p{'bus'} = 'xen';
        } elsif( ( $p{'node'} eq 'sd' )
                && ( $ostype eq 'linux' ) ){ 
            $p{'bus'} = 'scsi';
        } else {
            $p{'node'} = 'hd';
            $p{'bus'} = 'ide';
            delete $p{'target'};    # unset target
        }
    }

    plog "set_disk_node bus=$p{'bus'} node=$p{'node'} target=$p{'target'}" if( &debug_level > 5 );

    return wantarray() ? %p : \%p;
}

=item create_vm

create virtual machine
    
    my $OK = VirtAgentInterface->create_vm( name=>$name, ram=>$ram, ncpus=>$n, location=>'...', disk=>'path=/var/tmp/disk.img,targe=hda;...', network=>'name=Management,macaddr=...'  );

=cut

# create_vm
#   func wrapper to create vm
sub create_vm {
    my $self = shift;
    my %p = @_;

    # create and init virtual machine
    return $self->create_n_init_vm( %p );
}
# vmCreate
#   create virtual machine
#   args: name
#   res: ok || error
sub vmCreate {
    my $self = shift;
    $self = $self->new();
    
    my %p = @_;

    # get name
    my $name = $p{'name'};

    if( !$name ){
        return retErr("_ERROR_VMCREATE_NONAME_","No name defined.");
    }

    if( VMSCache::getUuidFromName($name) ){
        return retErr("_ERROR_VMCREATE_EXISTS_","Name already exists.");
    }

    # save to file from default config
#    $p{'savetofile'} ||= $CONF->{'savetofile'} || 0;

    %p = $self->clean_params( %p );

    my $VM = $self->vmLoad(%p);

    if( isError($VM) ){
        return wantarray() ? %$VM : $VM;
    }

    my $uuid = $VM->get_uuid();
    $name = $VM->get_name();

    $self->setVM( 'uuid'=>$uuid, 'name'=>$name, 'VM'=>$VM );

    # TODO initialize on hypervisor

    my %H = $VM->tohash();
    # TODO change this
    return retOk("_VM_CREATED_OK_","Virtual machine successfully created","_RET_OBJ_",\%H);
}
sub prep_comma_sep_fields {
    my ($str_comma_sep,$func) = @_;

    my @list = ();
    for my $c (split(/;/,$str_comma_sep)){
        my %F = ();
        for my $field (split(/,/,$c)){
            my ($f,$v) = split(/=/,$field,2);
            $F{"$f"} = $v;
        }
        %F = $func->(%F) if( $func );
        push(@list,\%F);
    }
    return wantarray() ? @list : \@list;
}

sub prep_disk_params {
    my $self = shift;
    my (%p) = @_;

    if( defined $p{'disk'} ){
        my $disk = $p{'disk'};
        if( !ref($disk) ){
            $p{'disk'} = &prep_comma_sep_fields($disk,
                                                        sub {
                                                            my (%D) = @_;
                                                            if( $hypervisor_type eq 'kvm' ){
                                                                # CMAR 23/06/2016
                                                                #   see #1060 - add defaults for performance improvement
                                                                if( ((not defined($D{'drivercache'})) or ($D{'drivercache'} eq 'none')) and
                                                                                not defined($D{'driverio'}) and not defined($D{'sourceaio'}) ){
                                                                    if( -b "$D{'path'}" ){
                                                                        $D{'drivercache'} = 'none';
                                                                        $D{'driverio'} = 'native';
                                                                        #$D{'sourceaio'} = 'native';
                                                                    }
                                                                }
                                                            }

                                                            my $format = delete($D{'format'});
                                                            if( !$D{'drivername'} &&
                                                                ($format || $D{'drivertype'} || $D{'drivercache'}) ){

                                                                # determinate the format
                                                                $D{'drivertype'} = $format if( $format );

                                                                if( $hypervisor_type eq 'kvm' ){
                                                                    $D{'drivername'} = "qemu";
                                                                } elsif( $hypervisor_type =~ m/xen/ ){
                                                                    # use tap driver name only if specify the format
                                                                    $D{'drivername'} = "tap" if( $D{'drivertype'} );
                                                                }
                                                            }
                                                            return wantarray() ? %D : \%D;

                                                        });
        }
    }
    return wantarray() ? %p : \%p;
}
sub prep_network_params {
    my $self = shift;
    my (%p) = @_;

    if( defined $p{'network'} ){
        my $network = $p{'network'};
        if( !ref($network) ){
            my @Network = &prep_comma_sep_fields($network ,
                                                    sub { 
                                                        my (%N) = @_;
                                                        if( !$N{'type'} ){
                                                            if( $N{'name'} ){
                                                                $N{'type'} = "network";
                                                            } elsif( $N{'bridge'} ) {
                                                                $N{'type'} = "Bridge";
                                                            } else {
                                                                $N{'type'} = "user";
                                                            }
                                                        }
                                                        if( ($N{'model'} eq 'xen') && ($hypervisor_type =~ 'xen') ){
                                                            delete($N{'model'});
                                                        }
                                                        return wantarray() ? %N : \%N;
                                                    });
            $p{'network'} = \@Network;
        }
    }
    return wantarray() ? %p : \%p;
}

sub prep_hostdev_obj{
    my %p = @_;

    if($p{'type'} eq 'usb'){
        unless($p{'vendor'} =~ /0x/){
            $p{'vendor'} = '0x'.$p{'vendor'};
        }
    
        unless($p{'product'} =~ /0x/){
            $p{'product'} = '0x'.$p{'product'};
        }

        delete $p{'bus'};
        delete $p{'slot'};
        delete $p{'function'};
    }elsif($p{'type'} eq 'pci'){
        unless($p{'bus'} =~ /0x/){
            $p{'bus'} = '0x'.$p{'bus'};
        }
    
        unless($p{'slot'} =~ /0x/){
            $p{'slot'} = '0x'.$p{'slot'};
        }
    
        unless($p{'function'} =~ /0x/){
            $p{'function'} = '0x'.$p{'function'};
        }

        delete $p{'vendor'};
        delete $p{'product'};
    }

    return wantarray() ? %p : \%p;
}

sub prep_hostdevs_params {
    my $self = shift;
    my (%p) = @_;

    if( defined $p{'hostdevs'} ){
        my $devs = $p{'hostdevs'};
        if( !ref($devs) ){
            $p{'hostdevs'} = &prep_comma_sep_fields($devs, \&prep_hostdev_obj);
        }
    }

    return wantarray() ? %p : \%p;
}

sub prep_controllers_params {
    my $self = shift;
    my (%p) = @_;

    if( defined $p{'controllers'} ){
        my $controller = $p{'controllers'};
        if( !ref($controller) ){
            #plogNow("[DEBUG] prep_controllers_params controller=",$controller);
            my @aux = &prep_comma_sep_fields($controller);

            my %indexByType = ();
            my $controllers = [];
            foreach my $c (@aux){
                my $t = $c->{'type'};
                if( $t eq 'usb2' ){   # add usb 2.0
                    $t = 'usb';
                    my $i = $indexByType{"$t"}++;
                    push( @$controllers,
                                        { 'type'=>"$t", 'index'=>"$i", 'model'=>'ich9-ehci1' },
                                        { 'type'=>"$t", 'index'=>"$i", 'model'=>'ich9-uhci1' },
                                        { 'type'=>"$t", 'index'=>"$i", 'model'=>'ich9-uhci2' },
                                        { 'type'=>"$t", 'index'=>"$i", 'model'=>'ich9-uhci3' } );
                } else {
                    my $i = $indexByType{"$t"}++;
                    push( @$controllers, { %$c, 'index'=>"$i" } );
                }
            }
            #plogNow("[DEBUG] prep_controllers_params controllers=",Dumper($controllers));
            $p{'controllers'} = $controllers;
        }
    }

    return wantarray() ? %p : \%p;
}

sub prep_filesystem_params {
    my $self = shift;
    my (%p) = @_;

    if( defined $p{'filesystem'} ){
        my $filesystem = $p{'filesystem'};
        if( !ref($filesystem) ){
            my @Filesystem = ();
            for my $d (split(/;/,$filesystem)){
                my %D = ();
                for my $field (split(/,/,$d)){
                    my ($f,$v) = split(/=/,$field,2);
                    $D{"$f"} = $v;
                }
                if( !$D{'type'} ){
                    if( $D{'dev'} ){
                        $D{'type'} = "block";
                    } elsif( $D{'dir'} ){
                        $D{'type'} = "mount";
                    } elsif( $D{'file'} ){
                        $D{'type'} = "file";
                    } else {
                        $D{'type'} = "template";
                    }
                }
                push(@Filesystem,\%D);
            }
            $p{'filesystem'} = \@Filesystem;
        }
    }
    return wantarray() ? %p : \%p;
}
sub prep_loadvm_params {
    my $self = shift;
    my (%p) = @_;

    # prepare devices
    %p = $self->prep_devices_params(%p);

    # memory ram in mbytes to bytes
    $p{'memory'} = $p{'ram'} * 1024 * 1024 if( $p{'ram'} );             # ram in mbytes

    # ncpus to vcpu
    $p{'vcpu'} = $p{'ncpus'} if( $p{'ncpus'} );

    return wantarray() ? %p : \%p;
}
sub prep_devices_params {
    my $self = shift;
    my (%p) = @_;

    # prepare disk
    %p = $self->prep_disk_params(%p);
    # prepare network
    %p = $self->prep_network_params(%p);
    # prepare filesystem
    %p = $self->prep_filesystem_params(%p);
    # prepare hostdevs
    %p = $self->prep_hostdevs_params(%p);
    # prepare controllers
    %p = $self->prep_controllers_params(%p);

    # other stuff

    return wantarray() ? %p : \%p;
}

=item destroy_vm

destroy virtual machine

    my $OK = VirtAgentInterface->destroy_vm( name=>$name );

    my $OK = VirtAgentInterface->destroy_vm( uuid=>$uuid );

=cut

# destroy_vm
# vmDestroy alias func
sub destroy_vm {
    my $self = shift;
    my %p = @_;

    return $self->vmDestroy( %p );
}

# vmDestroy
#   virtual machine destroy function
#   args: hash ( uuid || name )
#   return: ok || error
sub vmDestroy {
    my $self = shift;
    $self = $self->new();
    
    my %p = @_;

    my $uuid = $p{'uuid'};
    my $name = $p{'name'};

    if( !$uuid && !$name ){
        return retErr("_ERRR_VM_DESTROY_NOID_","Error no uuid and no name specified.");
    } elsif( $uuid ){
        $name = VMSCache::getVMName($uuid);
    } elsif( $name ){
        $uuid = VMSCache::getUuidFromName("${name}");
    }

    my $VM = $self->getVM(%p);
    if( $VM ){

        # force to stop first
        my %E = $self->stopDomain( 'uuid'=>$uuid, 'name'=>$name, force=>1, destroy=>1 );

        # undefine domain
        my %U = $self->undefDomain( 'uuid'=>$uuid, 'name'=>$name );

        # keep_fs is false: destroy disks
        if( defined($p{'keep_fs'}) && !$p{'keep_fs'} ){
            my $disks = $VM->get_Disks();

            if( $disks ){
                for my $D (@$disks){
                    my $path = $D->{'path'};

                    if( $D->{'type'} eq 'file' ){
                        $self->lvremove( 'vg'=>'__DISK__', 'lv'=>$path );
                    } else {
                        $self->lvremove( 'lv'=>$path );
                    }
                }
            }
        }
    
        # delete from memory
        $self->delVM( 'uuid'=>$uuid, 'name'=>$name );

        # TODO change this
        return retOk("_OK_","ok");

    } else {
        return retErr("_ERR_VM_DESTROY_NOT_FOUND","Error virtual machine not found.");
    }
}
# vmInit
#   virtual machine initialization
#   args: hash( uuid || name )
#   return: ok || error
sub vmInit {
    my $self = shift;
    $self = $self->new();
    
    my %p = @_;

    $p{'uuid'} = $self->{'_lastuuid'} if( !$p{'uuid'} );
    $p{'name'} = $self->{'_lastname'} if( !$p{'name'} );

    my $VM = $self->getVM(%p);
    
    if( !$VM ){
        return retErr("_ERR_VM_NOT_FOUND_","Error virtual machine not found.");
    }
    if( $VM->get_initialized() ){
        return retErr("_ERR_VM_INIT_YET_","Error virtual machine already initialized.");
    }

    my %V = $self->defineDomain( $VM->todomain() );
    if( isError(%V) ){
        return wantarray() ? %V : \%V;
    }

    my $xml = $self->get_xml_domain( 'uuid'=>$VM->get_uuid(), 'name'=>$VM->get_name() );
    if( isError($xml) ){
        return wantarray() ? %$xml : $xml;
    }
    # TODO
    #   fixme - update some info
    $VM = $VM->loadfromxml( $xml );
    $VM->set_state("notrunning");

    $VM->set_initialized(1);

    $self->setVM( 'uuid'=>$VM->get_uuid(), 'name'=>$VM->get_name(), 'VM'=>$VM );

    my %H = $VM->tohash();
    return retOk("_VM_INIT_OK_","Virtual machine init successfully","_RET_OBJ_",\%H);
}
# create and init virtual machine method
sub create_n_init_vm {
    my $self = shift;

    $self = $self->new();
    
    my %p = @_;

    # get name
    my $name = $p{'name'};

    if( !$name ){
        return retErr("_ERROR_VMCREATE_NONAME_","No name defined.");
    }

    if( VMSCache::getUuidFromName($name) ){
        return retErr("_ERROR_VMCREATE_EXISTS_","Name already exists.");
    }

    %p = $self->clean_params( %p );

    my $VM = $self->vmLoad(%p);

    if( isError($VM) ){
        return wantarray() ? %$VM : $VM;
    }

    my %V = $self->defineDomain( $VM->todomain() );
    if( isError(%V) ){
        return wantarray() ? %V : \%V;
    }

    my $xml = $self->get_xml_domain( 'uuid'=>$VM->get_uuid(), 'name'=>$VM->get_name() );
    if( isError($xml) ){
        return wantarray() ? %$xml : $xml;
    }
    # TODO
    #   fixme - update some info
    $VM = $VM->loadfromxml( $xml );
    $VM->set_state("notrunning");

    $VM->set_initialized(1);

    my $uuid = $VM->get_uuid();
    $name = $VM->get_name();

    $self->setVM( 'uuid'=>$uuid, 'name'=>$name, 'VM'=>$VM );

    my %H = $VM->tohash();
    return retOk("_VM_INIT_OK_","Virtual machine init successfully","_RET_OBJ_",\%H);
}
sub vmGenxml {
    my $self = shift;
    $self = $self->new();
    
    my %p = @_;

    $p{'uuid'} = $self->{'_lastuuid'} if( !$p{'uuid'} );
    $p{'name'} = $self->{'_lastname'} if( !$p{'name'} );

    my $VM = $self->getVM(%p);
    
    if( !$VM ){
        return retErr("_ERR_VM_NOT_FOUND_","Error virtual machine not found.");
    }
    return $VM->toxml();
}

=item reload_vm

reload virtual machine

    my $OK = VirtAgentInterface->reload_vm( name=>$name );

    my $OK = VirtAgentInterface->reload_vm( uuid=>$uuid );

=cut

sub reload_vm {
    my $self = shift;
    my %p = @_;

    return $self->vmReload( %p );
}
sub vmReload {
    my $self = shift;
    my (%p) = @_;

    %p = $self->clean_params( %p );

    my $VM = $self->getVM(%p);

    if( $VM ){

        # uuid and name
        my $uuid = $VM->get_uuid();
        my $name = $VM->get_name();

        # recover uuid and/or name
        $p{'uuid'} = $uuid if( !$p{'uuid'} );
        if( !$p{'name'} ){
            $p{'name'} = $name;
        } else {
            if( $p{'name'} ne $name ){  # name changed
                if( VMSCache::getUuidFromName($p{'name'}) ){
                    return retErr("_ERR_VM_NAME_EXISTS_","Name already exists.");
                }
            }
        }

        # previous state
        my $prev_state = $VM->get_state();

        # is running
        my $isrunning = $VM->isrunning() || $self->vmIsRunning(%p);
        # live flag
        my $live = $p{'live'};
        
        # load new VM
        my $newVM = $self->vmLoad(%p);
        if( isError($newVM) ){
            return wantarray() ? %$newVM : $newVM;
        }

        if( $isrunning && $live ){

            # get running VM
            my $rxml = $self->get_xml_domain( 'uuid'=>$uuid, 'name'=>$name );
            if( isError($rxml) ){
                return wantarray() ? %$rxml : $rxml;
            }
            my $rVM = VirtMachine->loadfromxml( $rxml );

            # one live mode only change change:

            # ... memory
            if( $rVM->get_currentMemory() ne $newVM->get_memory() ){
                my $mem = $newVM->get_memory();
                $mem = $mem / 1024; # convert bytes to kbytes

                # if new memory is less then actual, need to take some 
                if( $newVM->get_memory() < $rVM->get_currentMemory() ){
                    my $Er1 = $self->setMemory( 'uuid'=>$uuid, 'name'=>$name, 'mem'=>$mem );
                    if( isError($Er1) ){
                        return wantarray() ? %$Er1 : $Er1;
                    }
                } else {
                    if( $newVM->get_memory() > $rVM->get_memory() ){
                        my $Er2 = $self->setMaxMemory( 'uuid'=>$uuid, 'name'=>$name, 'maxmem'=>$mem );
                        if( isError($Er2) ){
                            return wantarray() ? %$Er2 : $Er2;
                        }
                    }

                    my $Er1 = $self->setMemory( 'uuid'=>$uuid, 'name'=>$name, 'mem'=>$mem );
                    if( isError($Er1) ){
                        return wantarray() ? %$Er1 : $Er1;
                    }
                }
            }

            # ... n cpus
            if( $rVM->get_vcpu() ne $newVM->get_vcpu() ){
                my $Er = $self->setVCPUS( 'uuid'=>$uuid, 'name'=>$name, 'vcpus'=>$newVM->get_vcpu( ) );
                if( isError($Er) ){
                    return wantarray() ? %$Er : $Er;
                }
            }

            # ... attach/detach device 
            
            #       ... disks
            plog "vmReload process disks..." if( &debug_level > 3 );

            my $ndisks = $newVM->get_Disks();
            if( $ndisks ){
                for my $VD ( @$ndisks ){
                    plog "vmReload process disk target=",$VD->get_target()," path=",$VD->get_path() if( &debug_level > 3 );

                    my $already_attached = 0;
                    for my $disk_p ( ({ 'path'=>$VD->get_path() },{ 'target'=>$VD->get_target() }) ){
                        my ($oi,$oVD) = $rVM->get_disk_i( %$disk_p );
                        if( $oVD ){
                            plog "vmReload disk exists target=",$oVD->get_target()," path=",$oVD->get_path() if( &debug_level > 3 );
                            $already_attached = $VD->isequal( $oVD );
                            if( !$already_attached ){

                                if( $oVD->get_device() ne 'cdrom' ){
                                    plog "vmReload detach old disk target=",$oVD->get_target()," path=",$oVD->get_path() if( &debug_level > 3 );
                                    my $D = $oVD->todevice();
                                    my $Er = $self->detachDevice( 'uuid'=>$uuid, name => $name,
                                                                    devices => { disk => $D } );
                                    if( isError($Er) ){
                                        return retErr("_ERR_DETACH_DISK_","Error detach disk '".$oVD->get_target()."'.");
                                    }
                                    sleep(2);
                                } else {
                                    plog "vmReload not detach old cdrom target=",$oVD->get_target()," path=",$oVD->get_path() if( &debug_level > 3 );
                                }
                                $oVD = $rVM->del_disk( i => $oi );
                            } else {
                                # mark to already attached
                                $already_attached = 1;
                            }
                            # dont touch
                            $oVD->set_dontdetach(1);
                        }
                    }

                    if( !$already_attached ){
                        sleep(2);
                        plog "vmReload attach disk target=",$VD->get_target()," path=",$VD->get_path() if( &debug_level > 3 );
                        my $D = $VD->todevice();
                        my $Er = $self->attachDevice( 'uuid'=>$uuid, name => $name,
                                                        devices => { disk => $D } );
                        if( isError($Er) ){
                            return retErr("_ERR_ATTACH_DISK_","Error attach disk '".$VD->get_target()."'.");
                        }

                        $rVM->add_disk( $VD->tohash(), 'dontdetach'=>1 );
                    }
                    plog "vmReload end process disk target=",$VD->get_target()," path=",$VD->get_path() if( &debug_level > 3 );
                }
            }

            # delete not used
            my $oldisks = $rVM->get_Disks();
            if( $oldisks ){
                my $c = 0;
                for my $oVD (@$oldisks){
                    if( $oVD ){
                        if( !$oVD->get_dontdetach() ){
                            plog "vmReload detach old disk target=",$oVD->get_target()," path=",$oVD->get_path() if( &debug_level > 3 );

                            my $D = $oVD->todevice();
                            my $Er = $self->detachDevice( 'uuid'=>$uuid, name => $name,
                                                            devices => { disk => $D } );
                            if( isError($Er) ){
                                return retErr("_ERR_DETACH_DISK_","Error detach disk '".$oVD->get_target()."'.");
                            }

                            $rVM->del_disk( i=>$c );
                            $c--;                       # one less
                        }
                    }
                    $c++;
                }
            }

            #       ... interfaces
            plog "vmReload process interfaces..." if( &debug_level > 3 );

            my $ninterfaces = $newVM->get_Network();
            if( $ninterfaces ){
                for my $VN ( @$ninterfaces ){
                    plog "vmReload process interfaces macaddr=",$VN->get_macaddr() if( &debug_level > 3 );

                    my $already_attached = 0;
                    my ($oi,$oVN) = $rVM->get_network_i( 'macaddr'=>$VN->get_macaddr() );
                    if( $oVN ){
                        plog "vmReload interface exists macaddr=",$VN->get_macaddr() if( &debug_level > 3 );
                        $already_attached = $VN->isequal( $oVN );
                        if( !$already_attached ){
                            plog "vmReload detach old interface macaddr=",$VN->get_macaddr() if( &debug_level > 3 );

                            my $D = $oVN->todevice();
                            my $Er = $self->detachDevice( 'uuid'=>$uuid, name => $name,
                                                            devices => { interface => $D } );
                            if( isError($Er) ){
                                return retErr("_ERR_DETACH_INTERFACE_","Error detach interface '".$oVN->get_macaddr()."'.");
                            }
                            $oVN = $rVM->del_network( i => $oi );
                        } else {
                            # mark to already attached
                            $already_attached = 1;
                        }
                        # dont touch
                        $oVN->set_dontdetach(1);
                    }

                    if( !$already_attached ){
                        my $D = $VN->todevice();
                        my $Er = $self->attachDevice( 'uuid'=>$uuid, name => $name,
                                                        devices => { interface => $D } );
                        if( isError($Er) ){
                            return retErr("_ERR_ATTACH_INTERFACE_","Error attach interface '".$VN->get_macaddr()."'.");
                        }

                        $rVM->add_network( $VN->tohash(), 'dontdetach'=>1 );
                    }
                    plog "vmReload end process interfaces macaddr=",$VN->get_macaddr() if( &debug_level > 3 );
                }
            }

            # delete not used
            my $olinterfaces = $rVM->get_Network();
            if( $olinterfaces ){
                my $c = 0;
                for my $oVN (@$olinterfaces){
                    if( $oVN ){
                        if( !$oVN->get_dontdetach() ){
                            plog "vmReload detach old interface macaddr=",$oVN->get_macaddr() if( &debug_level > 3 );

                            my $D = $oVN->todevice();
                            my $Er = $self->detachDevice( 'uuid'=>$uuid, name => $name,
                                                            devices => { interface => $D } );
                            if( isError($Er) ){
                                return retErr("_ERR_DETACH_INTERFACE_","Error detach interface '".$oVN->get_macaddr()."'.");
                            }
                            $rVM->del_network( i=>$c );
                            $c--;                       # one less
                        }
                    }
                    $c++;
                }
            }

            #       ... hostdevs
            plog "vmReload process hostdevices..." if( &debug_level > 3 );

            my $nhostdevs = $newVM->get_Hostdev();
            if( $nhostdevs ){
                for my $HD ( @$nhostdevs ){
                    plog "vmReload process hostdevs type=",$HD->{'type'} if( &debug_level > 3 );

                    my $already_attached = 0;
                    my ($oi,$oHD) = $rVM->get_hostdev_i( 'type'=>$HD->{'type'}, %$HD );
                    if( $oHD ){
                        plogNow "[DEBUG] vmReload hostdev exists type=",$HD->{'type'} if( &debug_level > 3 );
                        # dont touch
                        $oHD->{'dontdetach'} = 1;
                    } else {
                        my $Er = $self->attachHostdev( 'uuid'=>$uuid, name => $name,
                                                        devices => { hostdev => {%$HD} } );
                        if( isError($Er) ){
                            return retErr("_ERR_ATTACH_HOSTDEV_","Error attach hostdev '".$HD->{'type'}."'.");
                        }

                        $rVM->add_hostdev( %$HD, 'dontdetach'=>1 );
                    }
                    plog "vmReload end process hostdevs type=",$HD->{'type'} if( &debug_level > 3 );
                }
            }

            # delete not used
            my $olhostdevs = $rVM->get_Hostdev();
            if( $olhostdevs ){
                my $c = 0;
                for my $oHD (@$olhostdevs){
                    if( $oHD ){
                        if( !$oHD->{'dontdetach'} ){
                            plog "vmReload detach old hostdev type=",$oHD->{'type'} if( &debug_level > 3 );

                            my $Er = $self->detachHostdev( 'uuid'=>$uuid, name => $name,
                                                            devices => { hostdev => {%$oHD} } );
                            if( isError($Er) ){
                                return retErr("_ERR_DETACH_HOSTDEV_","Error detach hostdev '".$oHD->{'type'}."'.");
                            }
                            $rVM->del_hostdev( i=>$c );
                            $c--;                       # one less
                        }
                    }
                    $c++;
                }
            }

            plog "vmReload live end..." if( &debug_level > 3 );

        } else {
            # undefine previous xml definition
            # #127 - 28/08/2012
            # CMAR: dont undefine domain to preserve domain snapshots
            #
            # #625 - 13/02/2013
            # CMAR - for name change should undefine domain
            if( ($name ne $p{'name'}) && $p{'force_to_change_name'} ){
                $self->undefDomain( 'uuid'=>$uuid, 'name'=>$name );
            }
        }
        $VM = $newVM;   # change object

        my %V = $self->defineDomain( $VM->todomain() );
        if( isError(%V) ){
            return wantarray() ? %V : \%V;
        }

        # update uuid and name
        my $old_uuid = $uuid;
        my $old_name = $name;

        $uuid = $VM->get_uuid();
        $name = $VM->get_name();

        my $xml = $self->get_xml_domain( 'uuid'=>$uuid, 'name'=>$name );
        if( isError($xml) ){
            return wantarray() ? %$xml : $xml;
        }

        if( !$isrunning ){  # if not running
            # update info
            $VM = $VM->loadfromxml( $xml );
        }

        # update state
        $VM->set_state( $prev_state );

        # delete old name and uuid
        $self->delVM( 'uuid'=>$old_uuid, 'name'=>$old_name );

        # update name and uuid VMS Hash
                                            # sync info
        $self->setVM( 'uuid'=>$uuid, 'name'=>$name, 'VM'=>$VM );

        my %H = $VM->tohash();
        return retOk("_VM_RELOAD_OK_","Virtual machine reload successfully","_RET_OBJ_",\%H);
    } else {
        return retErr("_ERR_VM_NOT_FOUND_","Error virtual machine not found.");
    }
}

sub apply_config_vm {
    my $self = shift;
    my (%p) = @_;

    %p = $self->clean_params( %p );

    my $VM = $self->getVM(%p);
    if( $VM ){


        # re-define Domain
        my %V = $self->defineDomain( $VM->todomain() );
        if( isError(%V) ){
            return wantarray() ? %V : \%V;
        }

        my $xml = $self->get_xml_domain( 'uuid'=>$VM->get_uuid(), 'name'=>$VM->get_name() );
        if( isError($xml) ){
            return wantarray() ? %$xml : $xml;
        }

        my $prev_state = $VM->get_state();

        # TODO
        #   fixme - update some info
        $VM = $VM->loadfromxml( $xml );
        $VM->set_state( $prev_state );

        $VM->set_initialized(1);

        my $uuid = $VM->get_uuid();
        my $name = $VM->get_name();

        $self->setVM( 'uuid'=>$uuid, 'name'=>$name, 'VM'=>$VM );

        my %H = $VM->tohash();

        return retOk("_VM_APPLY_OK_","Virtual machine configuration applied successfully","_RET_OBJ_",\%H);
    } else {
        return retErr("_ERR_VM_NOT_FOUND_","Error virtual machine not found.");
    }
}

sub apply_n_restart_vm {
    my $self = shift;
    my (%p) = @_;

    my $VM = $self->getVM(%p);
    if( $VM ){
        my $err_apply = $self->apply_config_vm( %p );
        if( isError($err_apply) ){
            return wantarray() ? %$err_apply : $err_apply;
        }


        if( $VM->isrunning() || $self->vmIsRunning(%p) ){
            my $err_stop = $self->stop_vm( %p );
            if( isError($err_stop) ){
                return wantarray() ? %$err_stop : $err_stop;
            }
        }

        my $err_start = $self->start_vm( %p );
        if( isError($err_start) ){
            return wantarray() ? %$err_start : $err_start;
        }
    
        my %H = $VM->tohash();

        return retOk("_VM_APPLY_OK_","Virtual machine configuration applied and restarted successfully","_RET_OBJ_",\%H);
    } else {
        return retErr("_ERR_VM_NOT_FOUND_","Error virtual machine not found.");
    }
}

sub reboot_vm {
    my $self = shift;
    my %p = @_;

    return $self->vmReboot( %p );
}
sub vmReboot {
    my $self = shift;
    my %p = @_;

    $p{'name'} = $self->{'_lastdomain'} if( !$p{'name'} && ref($self) );
    $p{'uuid'} = $self->{'_lastuuid'} if( !$p{'uuid'} && ref($self) );

    my $VM = $self->getVM(%p);

    if( !$VM->isrunning() && !$self->vmIsRunning(%p) ){
        return retErr("_ERR_VM_IS_NOT_RUNNING_","Error virtual machine is not running.");
    }

    my $E = $self->rebootDomain(%p);

    if( isError($E) ){
        return wantarray() ? %$E : $E;
    }

    my $xml = $self->get_xml_domain( 'uuid'=>$VM->get_uuid(), 'name'=>$VM->get_name() );
    if( isError($xml) ){
        return wantarray() ? %$xml : $xml;
    }

    $VM = $VM->loadfromxml( $xml );

    $VM->set_state("reboot");

    $self->setVM( 'uuid'=>$VM->get_uuid(), 'name'=>$VM->get_name(), 'VM'=>$VM );

    my %H = $VM->tohash();
    return retOk("_VM_REBOOT_OK_","Virtual machine reboot","_RET_OBJ_",\%H);
}

# set_vnc_options: set vnc vm options
sub set_vnc_options {
    my $self = shift;
    my (%p) = @_;

    my %o = ();
    for my $k ( qw( nographics vnc_port vnc_listen vnc_keymap ) ){
        $o{"$k"} = $p{"$k"} if( defined $p{"$k"} );
    }
    my $Er = $self->set_vm_options( %p, 'options'=>\%o );

    if( !isError($Er) ){
        return retOk("_OK_VNC_OPTIONS_","Set vnc options ok.");
    } else {
        return wantarray() ? %$Er: $Er;
    }
}

# set_vm_options: set vm options
sub set_vm_options {
    my $self = shift;
    my (%p) = @_;

    my $VM = $self->getVM(%p);
    if( $VM ){
        if( ref($p{'options'}) eq 'HASH' ){
            my $options = $p{'options'};
            my @lopts = keys %$options;
            my %o = ();
            for my $k ( @lopts ){
                if( $k eq 'ram' ){
                    $options->{'memory'} = $options->{'ram'} * 1024 * 1024; # ram in mbytes
                } elsif( $k eq 'ncpus' ){
                    $options->{'vcpu'} = $options->{'ncpus'};
                } elsif( defined $options->{"$k"} ){
                    $o{"$k"} = $options->{"$k"} ;
                }
            }
            $VM->setfields( %o );
        }

        if( $p{'apply_n_restart'} ){
            my $err = $self->apply_n_restart_vm( %p );
            if( isError($err) ){
                return wantarray() ? %$err : $err;
            }
        } else {
            my $err = $self->apply_config_vm( %p );
            if( isError($err) ){
                return wantarray() ? %$err : $err;
            }
        }

        return retOk("_OK_VM_OPTIONS_","Set vm options ok.");
    } else {
        return retErr("_ERR_VM_NOT_FOUND_","Error virtual machine not found.");
    }
}

# load sys info
sub loadsysinfo {
    my $self = shift;
    
    # load configuration
    $self->loadconf();

    # connect to virtualization management
    my $vc = $self->vmConnect(address=>$CONF->{'vm_address'},uri=>$CONF->{'vm_uri'},readonly=>$CONF->{'vm_readonly'});

    # TODO load other stuff
    VirtAgent->loadsysinfo(@_);
    VirtAgent::Disk->loaddiskdev(@_);
    VirtAgent::Network->loadnetdev(@_);

    # load hypervisor type
    $self->hypervisor_type();

    # load virtual machines info
    $self->loadvms();

    $self->load_vnets();
    
=pod

=begin comment

# this is not supported

    # register domain events callback
    $vc->domain_event_deregister();
    my $callback = sub { 
                        my ($con,$dom,$event,$detail) = @_;
                        my $VM = VirtAgentInterface->getVM( uuid=>$dom->get_uuid() );
                        $VM->set_detail( $detail );
                        print STDERR "DOMAIN CALLBACK dom=",$dom->get_name()," event=",$event, " detail=",$detail,"\n";
                        open(F,">>/tmp/bla.txt");
                        print F "DOMAIN CALLBACK dom=",$dom->get_name()," event=",$event, " detail=",$detail,"\n";
                        close(F);
                        return 0;
                    };
    $vc->domain_event_register( $callback );

=end comment

=cut

    # TODO change this
    return retOk("_OK_","ok");
}

# getsysinfo
#   return info from VirtAgent and VirtAgent::Network
#   TODO get info from VirtAgent::Disk

sub getsysinfo {
    my $self = shift;

    my %res = ( VirtAgent->getsysinfo(), VirtAgent::Network->getnetinfo(), 'hypervisor_type'=>$hypervisor_type );
    return wantarray() ? %res : \%res;
}

sub umount_isosdir {
    if( $CONF->{'isosdir'} ){
        # check if isosdir mount with nfs
        my ($e,$m) = cmd_exec("/bin/mount | grep \"$CONF->{'isosdir'}\" | grep \"nfs\"");
        if( ( $e==0 ) && $m ){
            my ($e1,$m) = cmd_exec("/bin/umount","$CONF->{'isosdir'}");
            unless( $e1 == 0 ){
                return 0;   # fail
            }
        }

        # check if exists in /etc/fstab
        my ($e2,$m2) = cmd_exec("/bin/grep \"$CONF->{'isosdir'}\" /etc/fstab | grep \"nfs\"");
        if( ( $e2==0 ) && $m2 ){
            # lock file
            my $fstab_file = "/etc/fstab";
            my $fstab_lockfile = "/var/tmp/fstab.lockfile";
            open(LF,"$fstab_lockfile");
            flock(LF,LOCK_EX);  # lock
            my $tmpfile = "$fstab_file.new.bkp";
            open(O,">$tmpfile");
            open(F,"$fstab_file");
            while(<F>){
                next if( m#$CONF->{'isosdir'}# );
                print O; 
            }
            close(F);
            close(O);
            move($tmpfile,$fstab_file); # overwride fstab with new file
            flock(LF,LOCK_UN);  # unlock
            close(LF);
        }
    }
    return 1;
}

sub mount_isosdir {
    my ($IP_CENTRAL_MANAGEMENT) = @_;
    if( $IP_CENTRAL_MANAGEMENT ){
        # get ip only
        $IP_CENTRAL_MANAGEMENT =~ s/^http:\/\/([^\/]+)\/.*$/$1/;
    }

    if( $CONF->{'isosdir'} ){
        if( !-d "$CONF->{'isosdir'}" ){
            mkpath( "$CONF->{'isosdir'}" );
        }

        # check if mounted
        my ($e,$m) = cmd_exec("/bin/mount | grep \"$CONF->{'isosdir'}\"");
        if( ( $e!=0 ) && !$m ){
            my $do_mount = 1;
            # check if exists in /etc/fstab
            my ($e2,$m2) = cmd_exec("/bin/grep \"$CONF->{'isosdir'}\" /etc/fstab");
            if( ( $e2!=0 ) && !$m2 ){
                $do_mount = 0;
                $IP_CENTRAL_MANAGEMENT ||= ETVA::Utils::get_cmip();
                if( $IP_CENTRAL_MANAGEMENT ne '127.0.0.1' ){    # not localhost
                    open(FT,">>/etc/fstab");
                    print FT "$IP_CENTRAL_MANAGEMENT:$CONF->{'isosdir'} $CONF->{'isosdir'} nfs     soft,timeo=600,retrans=2,nosharecache,_netdev        0 0","\n";
                    close(FT);
                    $do_mount = 1;
                }
            }
            if( $do_mount ){
                # mount!
                cmd_exec("/bin/mount","$CONF->{'isosdir'}");
            }
        }
    }
    return 1;
}

sub loadconf {
    # load conf
    $CONF = ETVA::Utils::get_conf();
    $VM_DIR = $CONF->{'VM_DIR'} if( $CONF->{'VM_DIR'} );
    $TMP_DIR = $CONF->{'tmpdir'} if( $CONF->{'tmpdir'} );
    $VIRTIO_CHANNELS_SOCKETS_DIR = $ENV{'ga_socket_dir'} || $CONF->{'ga_socket_dir'} || "$TMP_DIR/virtagent-virtio/virtio-sockets-dir";
    $VIRTIO_CHANNELS_STATE_DIR = $ENV{'ga_state_dir'} || $CONF->{'ga_state_dir'} || "$TMP_DIR/virtagent-virtio/virtio-state-dir";

    $GUEST_MANAGEMENT_SOCKETS_DIR = $ENV{'gm_socket_dir'} || $CONF->{'gm_socket_dir'} || "$TMP_DIR/virtagent-guestmngt-sockets-dir";
    $GUEST_CONSOLE_SOCKETS_DIR = $ENV{'guest_console_socket_dir'} || $CONF->{'guest_console_socket_dir'} || "$TMP_DIR/virtagent-console-sockets-dir";

    if( !$ENV{'ga_socket_dir'} ){
        $ENV{'ga_socket_dir'} = $VIRTIO_CHANNELS_SOCKETS_DIR;
    }
    if( ! -d "$VIRTIO_CHANNELS_SOCKETS_DIR" ){
        mkpath("$VIRTIO_CHANNELS_SOCKETS_DIR");
    }
    if( !$ENV{'ga_state_dir'} ){
        $ENV{'ga_state_dir'} = $VIRTIO_CHANNELS_STATE_DIR;
    }
    if( ! -d "$VIRTIO_CHANNELS_STATE_DIR" ){
        mkpath("$VIRTIO_CHANNELS_STATE_DIR");
    }
    if( !$ENV{'gm_socket_dir'} ){
        $ENV{'gm_socket_dir'} = $GUEST_MANAGEMENT_SOCKETS_DIR;
    }
    if( ! -d "$GUEST_MANAGEMENT_SOCKETS_DIR" ){
        mkpath("$GUEST_MANAGEMENT_SOCKETS_DIR");
    }
    if( !$ENV{'guest_console_socket_dir'} ){
        $ENV{'guest_console_socket_dir'} = $GUEST_CONSOLE_SOCKETS_DIR;
    }
    if( ! -d "$GUEST_CONSOLE_SOCKETS_DIR" ){
        mkpath("$GUEST_CONSOLE_SOCKETS_DIR");
    }
    &set_debug_level( $CONF->{'debug'} || 0 );

    # fix agent name resolution to ip
    if( $CONF->{'name'} ){
        ETVA::NetworkTools::fix_hostname_resolution($CONF->{'name'}, $CONF->{'IP'} || $CONF->{'LocalIP'} || '127.0.0.1');
    }
    
    &mount_isosdir();
}

# loadvms
#   load virtual machines
sub loadvms {
    my $self = shift;

    $self = $self->new();

    # load Domains on virtualization system
    my @l = ();
    push @l, VirtAgent->listDomains();
    push @l, VirtAgent->listDefDomains();
    for my $H (@l){
        if( my $ldoms = $H->{'domains'} ){
            for my $D ( @$ldoms ){
                my $Di = $D->{'domain'};
                my $id = $Di->{'id'};
                my $nm = $Di->{'name'};

                        # ignore id=0 or Domain-0
                next if( !$id || ( $nm eq 'Domain-0') );

                my $uuid = $Di->{'uuid'};
                $uuid ||= VMSCache::getUuidFromName($nm);
                if( $uuid ){
                    my $xml = VirtAgent->get_xml_domain('uuid'=>$uuid, "name"=>$nm);
                    if( !isError($xml) ){
                        # load as VirtMachine
                        my $VM = VirtMachine->loadfromxml($xml);
                        $VM->set_initialized(1);
                        $VM->set_state( $Di->{'state'} );

                        $self->setVM( 'uuid'=>$uuid, 'name'=>$nm, 'VM'=>$VM );
                    }
                }
            }
        }
    }

    # TODO change this
    return retOk("_OK_","ok");
}

# getVM
#   get virtual machine
sub getVM {
    my $self = shift;
    my ($uuid,$name) = my %p = @_;
    if( $p{'name'} || $p{'uuid'} ){
        $name = $p{'name'};
        $uuid = $p{'uuid'};
    }

    $uuid = VMSCache::getUuidFromName($name) if( !$uuid && $name );

    my $VM = VMSCache::getVMFromUuid($uuid);

    plog( &nowStr()," [info] Call getVM name=$name uuid=$uuid vm=$VM" );

    if( !$VM || $p{'force'} ){
        $VM = $self->loadVM(%p);
    }

    return $VM;
}

sub setVM {
    my $self = shift;
    my ($uuid,$name,$VM) = my %p = @_;
    if( $p{'name'} || $p{'uuid'} ){
        $name = $p{'name'};
        $uuid = $p{'uuid'};
        $VM = $p{'VM'};
    }

    $uuid = VMSCache::getUuidFromName($name) if( !$uuid && $name );
    $name = $VM->{"name"} if( $uuid && !$name );

    # delete previous version
    $self->delVM( 'uuid'=>"$uuid", 'name'=>"$name" );

    VMSCache::setVM( $uuid, $name, $VM );
    return $VM;
}

# loadVM
#   load VM from libvirt
sub loadVM {
    my $self = shift;
    my ($uuid,$name) = my %p = @_;
    if( $p{'name'} || $p{'uuid'} ){
        $name = $p{'name'};
        $uuid = $p{'uuid'};
    }

    $uuid = VMSCache::getUuidFromName($name) if( !$uuid && $name );

    # ask to libvirt
    my $dom = $self->getDomain( 'uuid'=>$uuid, 'name'=>$name );

    my $VM;
    if( !isError($dom) ){
        my $xml = $self->get_xml_domain('uuid'=>$uuid, "name"=>$name);
        if( !isError($xml) ){
            # load as VirtMachine
            $VM = VirtMachine->loadfromxml($xml);
            $VM->set_initialized(1);

            my $Di = VirtAgent::retDomainInfo( $dom );
            if( !isError($Di) ){
                $VM->set_state( $Di->{'state'} );
            }

            $self->setVM( 'uuid'=>$uuid, 'name'=>$name, 'VM'=>$VM );
        }
    }

    return $VM;
}

# load_vm alias of loadVM
*load_vm = \&loadVM;

# delVM
#   delete from memory
sub delVM {
    my $self = shift;
    my ($uuid,$name) = my %p = @_;
    if( $p{'name'} || $p{'uuid'} ){
        $name = $p{'name'};
        $uuid = $p{'uuid'};
    }

    $uuid = VMSCache::getUuidFromName($name) if( !$uuid && $name ); 
    $name = VMSCache::getVMName($uuid) if( $uuid && !$name );

    if( $name ne VMSCache::getVMName($uuid) ){
        my $oldname = VMSCache::getVMName($uuid);
        VMSCache::delName2Uuid($oldname);
    }
    if( VMSCache::getUuidFromName($name) ){
        VMSCache::delName2Uuid($name);
    }

    my $VM;
    if( VMSCache::getVMName($uuid) ){
        VMSCache::delVMUuid($uuid);
    }
    return $VM;
}

# delete_vm alias of delVM
*delete_vm = \&delVM;

=item list_vms

list running virtual machines

    my $List = VirtAgentInterface->list_vms( );

=cut

# list_vms
#   list running virtual machines
sub list_vms {
    my $self = shift;
    my @list = ();

    for my $VM (VMSCache::valuesVMS()){
        my %H = $VM->tohash();
#        $H{'DOMAIN'} = $VM->todomain();
        push @list, \%H;
    }
    return wantarray() ? @list : \@list;
}

=item list_vms

Returns a hash with the vms info in xml format
my $List = VirtAgentInterface->vms_xml( );

=cut
sub vms_xml {
    my $self = shift;
    my @list = ();

    for my $VM (VMSCache::valuesVMS()){
        my $xml = $self->get_xml_domain( 'uuid'=>$VM->get_uuid(), 'name'=>$VM->get_name() );
        my %vmh = ( 
            'name'  => $VM->get_name(),
            'uuid'  => $VM->get_uuid(),
            'xml'   => $xml
        );
        if( isError($xml) ){
            $vmh{'xml'} = '';
            $vmh{'xmlWithErrors'} = 1;
        }
        push @list, \%vmh;
    }
    return wantarray() ? @list : \@list;
}

sub hash_vms {
    my $self = shift;
    my %hash = ();

    for my $VM (VMSCache::valuesVMS()){
        my %H = $VM->tohash();
        my $name = $H{'name'};
        $hash{"$name"} = \%H;
    }

    return wantarray() ? %hash : \%hash;
}

=item get_vm

get virtual machine in hash format

    my $VM = VirtAgentInterface->get_vm( name=>$name );

=cut

# get_vm
#   get virtual machine in hash format
sub get_vm {
    my $self = shift;
    my %p = @_;

    my $VM = $self->getVM(%p);
    if( !$VM ){
        return retErr("_ERR_VM_NOT_FOUND_","Error virtual machine not found.");
    }

    return $VM->tohash();
}

# TODO
sub attach_device {
    my $self = shift;
    my %p = @_;

    my $VM = $self->getVM(%p);
    if( !$VM ){
        return retErr("_ERR_VM_NOT_FOUND_","Error virtual machine not found.");
    }
}


=item attach_disk

attach one or more disk devices

    my $OK = VirtAgentInterface->attach_disk( name=>$name, disk=>'path=...,target=...;path=...' );

    params for each disk: path, target, readonly, device, drivername, drivertype

=cut

# attach_disk
#   attach disk device
#   args: Hash { name, path, diskdevice, diskdrivertype, diskdrivername, disktarget, diskreadonly }
#           or Hash { disk => 'path=...,target=...;path=....,....' }
#   res: ok || err
sub attach_disk {
    my $self = shift;
    my %p = @_;

    %p = $self->clean_params( %p );

    my $VM = $self->getVM(%p);
    if( !$VM ){
        return retErr("_ERR_VM_NOT_FOUND_","Error virtual machine not found.");
    }

    # prepare disk params
    %p = $self->prep_disk_params(%p);

    # disk devices
    my @Disks = ();
    if( ref($p{'disk'}) eq 'ARRAY' ){
        @Disks = @{$p{'disk'}};
    } else {
        my %D = ();

        $D{'path'} = $p{'path'} || $p{'disk'}{'path'};

        if( $D{'path'} ){
            $D{'device'} = $p{'diskdevice'} || $p{'disk'}{'device'};
            $D{'drivertype'} = $p{'diskdrivertype'} || $p{'disk'}{'drivertype'};
            $D{'drivername'} = $p{'diskdrivername'} || $p{'disk'}{'drivername'};
            $D{'drivercache'} = $p{'diskdrivercache'} || $p{'disk'}{'drivercache'};
            $D{'driverio'} = $p{'diskdriverio'} || $p{'disk'}{'driverio'};
            $D{'sourceaio'} = $p{'disksourceaio'} || $p{'disk'}{'sourceaio'};
            $D{'target'} = $p{'disktarget'} || $p{'disk'}{'target'};
            $D{'readonly'} = $p{'diskreadonly'} || $p{'disk'}{'readonly'};
            $D{'node'} = $p{'disknode'} || $p{'disk'}{'node'};
            $D{'bus'} = $p{'diskbus'} || $p{'disk'}{'bus'};

            if( !$D{'drivername'} &&
                    ($p{'diskdrivercache'} || $p{'disk'}{'drivercache'} || $D{'drivertype'} || $p{'diskformat'} || $p{'disk'}{'format'}) ){

                # determinate the format
                if( $p{'diskformat'} || $p{'disk'}{'format'} ){
                    $D{'drivertype'} = $p{'diskformat'} || $p{'disk'}{'format'};
                }

                if( $self->get_hypervisor_type() eq 'kvm' ){
                    $D{'drivername'} = "qemu";

                } elsif( $self->get_hypervisor_type() =~ m/xen/ ){
                    # use tap driver name only if specify the format
                    $D{'drivername'} = "tap" if( $D{'drivertype'} );
                }
            }

            push(@Disks,\%D);
        }
    }
    if( @Disks ){

        # uuid and name
        my $uuid = $VM->get_uuid();
        my $name = $VM->get_name();

        # is running
        my $isrunning = $VM->isrunning() || $self->vmIsRunning(%p);
        # live flag
        my $live = defined($p{'live'}) ? $p{'live'} : 1;    # live on by default

        # hypervisor type
        my $htype = $self->get_type();
        # os type
        my $ostype = $VM->get_os_type();

        # new VM
        my $new_VM = $VM->clone();

        # get running VM
        my $xml = $self->get_xml_domain( 'uuid'=>$uuid, 'name'=>$name );
        if( isError($xml) ){
            return wantarray() ? %$xml : $xml;
        }
        my $rVM = VirtMachine->loadfromxml( $xml );

        for my $D (@Disks){
            my %TD = ( 'path' => $D->{'path'},
                            'device' => $D->{'device'},
                            'drivertype' => $D->{'drivertype'},
                            'drivername' => $D->{'drivername'},
                            'drivercache' => $D->{'drivercache'},
                            'driverio' => $D->{'driverio'},
                            'sourceaio' => $D->{'sourceaio'},
                            'target' => $D->{'target'},
                            'readonly' => $D->{'readonly'},
                            'node' => $D->{'node'},
                            'bus' => $D->{'bus'}
                            );
            %TD = $self->set_disk_node( %TD, 'hypervisor_type'=>$htype, 'os_type'=>$ostype, 'NEWDISKS'=>\@Disks, 'OLDDISKS'=>$rVM->get_Disks() );
            my $VD = $new_VM->add_disk( %TD );

            # if is running and live flag on
            if( $isrunning && $live ){
                if( !$rVM->get_disk( 'path'=>$TD{'path'}, 'target'=>$TD{'target'} ) ){
                    my $DTD = $VD->todevice();
                    my $S = $self->attachDevice( 'uuid'=>$uuid, name => $name,
                                                    devices => { disk => $DTD } );
                    if( isError($S) ){
                        return wantarray() ? %$S : $S;
                    }
                    $rVM->add_disk( %TD );
                }
            }
        }
        $VM = $new_VM;

        # redefine domain for next reboot
        my %V = $self->defineDomain( $VM->todomain() );
        if( isError(%V) ){

            return wantarray() ? %V : \%V;
        }

        # sync info
        $self->setVM( 'uuid'=>$uuid, 'name'=>$name, 'VM'=>$VM );

        return retOk("_ATTACH_DISK_OK_","Disk successfully attached.");
    } else {
        return retErr("_ERR_ATTACH_DISK_","Error no disk info defined.");
    }
}

=item detach_disk

detach disk device

    my $OK = VirtAgentInterface->detach_disk( name=>$name, i=>0 );

=cut

# detach_disk
#   detach disk device
#   args: i - disk index 
#   res: ok || err
sub detach_disk {
    my $self = shift;
    my %p = @_;

    %p = $self->clean_params( %p );

    my $VM = $self->getVM(%p);
    if( !$VM ){
        return retErr("_ERR_VM_NOT_FOUND_","Error virtual machine not found.");
    }

    # uuid and name
    my $uuid = $VM->get_uuid();
    my $name = $VM->get_name();

    # is running
    my $isrunning = $VM->isrunning() || $self->vmIsRunning(%p);
    # live flag
    my $live = defined($p{'live'}) ? $p{'live'} : 1;    # live on by default

    # hypervisor type
    my $htype = $self->get_type();
    # os type
    my $ostype = $VM->get_os_type();

    # new VM
    my $new_VM = $VM->clone();

    # get running VM
    my $xml = $self->get_xml_domain( 'uuid'=>$uuid, 'name'=>$name );
    if( isError($xml) ){
        return wantarray() ? %$xml : $xml;
    }
    my $rVM = VirtMachine->loadfromxml( $xml );

    my $VD = $new_VM->get_disk( i => $p{'i'} );
    if( $VD ){
        # if is running and live flag on
        if( $isrunning && $live ){
            my ($oi,$oVD) = $rVM->get_disk_i( 'path'=>$VD->get_path(), 'target'=>$VD->get_target() );
            if( $oVD ){
                my $D = $VD->todevice();
                my $S = $self->detachDevice( 'uuid'=>$uuid, name => $name,
                                                devices => { disk => $D } );
                if( isError($S) ){
                    return wantarray() ? %$S : $S;
                }
                $rVM->del_disk( i => $oi );
            }
        }
        $VD = $new_VM->del_disk( i => $p{'i'} );

        $VM = $new_VM;

        # redefine domain
        my %V = $self->defineDomain( $VM->todomain() );
        if( isError(%V) ){
            return wantarray() ? %V : \%V;
        }

        # sync info
        $self->setVM( 'uuid'=>$uuid, 'name'=>$name, 'VM'=>$VM );

        return retOk("_DETACH_DISK_OK_","Disk successfully detached.");
    } else {
        return retErr("_ERR_DETACH_DISK_","Error no disk to detach.");
    }
}

=item attach_interface

attach one or more network interfaces

    my $OK = VirtAgentInterface->attach_interface( name=>$name, network=>'name=...,macaddr=...;type=...' );

    types: network, bridge, user

    params for each interface: macaddr, bridge, name

=cut

# attach_interface
#   attach network interface 
#   args: Hash { nettype, netbridge, netname, macaddr }
#           or Hash { network => 'type=...,name=...,macaddr=...;type=...,bridge=...,macaddr=...' }
#   res: ok || err
sub attach_interface {
    my $self = shift;
    my %p = @_;

    %p = $self->clean_params( %p );

    my $VM = $self->getVM(%p);
    if( !$VM ){
        return retErr("_ERR_VM_NOT_FOUND_","Error virtual machine not found.");
    }

    # prepare network params
    %p = $self->prep_network_params(%p);

    # network information
    my @Network = ();
    if( ref($p{'network'}) eq 'ARRAY' ){
        @Network = @{$p{'network'}};
    } else {
        my %N = ();
        $N{'type'} = $p{'nettype'} || $p{'network'}{'type'};
        $N{'bridge'} = $p{'netbridge'} || $p{'network'}{'bridge'};
        $N{'name'} = $p{'netname'} || $p{'network'}{'name'};
        $N{'macaddr'} = $p{'macaddr'} || $p{'network'}{'macaddr'};
        push( @Network, \%N );
    }

    if( @Network ){

        # uuid and name
        my $uuid = $VM->get_uuid();
        my $name = $VM->get_name();

        # is running
        my $isrunning = $VM->isrunning() || $self->vmIsRunning(%p);
        # live flag
        my $live = defined($p{'live'}) ? $p{'live'} : 1;    # live on by default

        # hypervisor type
        my $htype = $self->get_type();
        # os type
        my $ostype = $VM->get_os_type();

        # new VM
        my $new_VM = $VM->clone();

        # get running VM
        my $xml = $self->get_xml_domain( 'uuid'=>$uuid, 'name'=>$name );
        if( isError($xml) ){
            return wantarray() ? %$xml : $xml;
        }
        my $rVM = VirtMachine->loadfromxml( $xml );

        for my $N (@Network){
            my %TN = ( 'type' => $N->{'type'},
                                        'bridge' => $N->{'bridge'},
                                        'name' => $N->{'name'},
                                        'macaddr' => $N->{'macaddr'}
                                        );
            my $VN = $new_VM->add_network( %TN );
            $N->{'macaddr'} = $TN{'macaddr'} = $VN->get_macaddr();

            # if is running and live flag on
            if( $isrunning && $live ){
                if( !$rVM->get_network( 'macaddr'=>$VN->get_macaddr() ) ){
                    my $D = $VN->todevice();
                    my $S = $self->attachDevice( 'uuid'=>$uuid, name => $name,
                                                    devices => { interface => $D } );
                    if( isError($S) ){
                        return wantarray() ? %$S : $S;
                    }
                    $rVM->add_network( %TN );
                }
            }
        }
        $VM = $new_VM;

        # redefine domain for next reboot
        my %V = $self->defineDomain( $VM->todomain() );
        if( isError(%V) ){
            return wantarray() ? %V : \%V;
        }

        # sync info
        $self->setVM( 'uuid'=>$uuid, 'name'=>$name, 'VM'=>$VM );

        return retOk("_ATTACH_INTERFACE_OK_","Interface successfully attached.");
    } else {
        return retErr("_ERR_ATTACH_INTERFACE_","Error no interface info defined.");
    }
}

=item detach_interface

detach network interface 

    my $OK = VirtAgentInterface->detach_interface( name=>$name, i=>0 );

    my $OK = VirtAgentInterface->detach_interface( name=>$name, macaddr=>0 );

=cut

# detach_interface
#   detach network interface 
#   args: i - network index
#         macaddr - mac address
#   res: ok || err
sub detach_interface {
    my $self = shift;
    my %p = @_;

    %p = $self->clean_params( %p );

    my $VM = $self->getVM(%p);
    if( !$VM ){
        return retErr("_ERR_VM_NOT_FOUND_","Error virtual machine not found.");
    }

    # uuid and name
    my $uuid = $VM->get_uuid();
    my $name = $VM->get_name();

    # is running
    my $isrunning = $VM->isrunning() || $self->vmIsRunning(%p);
    # live flag
    my $live = defined($p{'live'}) ? $p{'live'} : 1;    # live on by default

    # hypervisor type
    my $htype = $self->get_type();
    # os type
    my $ostype = $VM->get_os_type();

    # new VM
    my $new_VM = $VM->clone();

    # get running VM
    my $xml = $self->get_xml_domain( 'uuid'=>$uuid, 'name'=>$name );
    if( isError($xml) ){
        return wantarray() ? %$xml : $xml;
    }
    my $rVM = VirtMachine->loadfromxml( $xml );

    my $VN = $new_VM->get_network( i => $p{'i'}, macaddr => $p{'macaddr'} );
    if( $VN ){
        # if is running and live flag on
        if( $isrunning && $live ){
            my ($oi,$oVN) = $rVM->get_network_i( 'macaddr'=>$VN->get_macaddr() );
            if( $oVN ){
                my $D = $VN->todevice();
                my $S = $self->detachDevice( 'uuid'=>$uuid, name => $name,
                                                devices => { interface => $D } );
                if( isError($S) ){
                    return wantarray() ? %$S : $S;
                }
                $rVM->del_network( i => $oi );
            }
        }
        $VN = $new_VM->del_network( i => $VN->{'i'} );

        $VM = $new_VM;

        # redefine domain
        my %V = $self->defineDomain( $VM->todomain() );
        if( isError(%V) ){
            return wantarray() ? %V : \%V;
        }

        # sync info
        $self->setVM( 'uuid'=>$uuid, 'name'=>$name, 'VM'=>$VM );

        return retOk("_DETACH_INTERFACE_OK_","Interface successfully detached.");
    } else {
        return retErr("_ERR_DETACH_INTERFACE_","Error no interface to detach.");
    }
}

=item detachall_interfaces

detach all network interfaces

    my $OK = VirtAgentInterface->detachall_interfaces( name=>$name );

=cut

# detachall_interfaces
#   detach all network interfaces
#   args: name - vm name
#   res: OK || Error
sub detachall_interfaces {
    my $self = shift;
    my %p = @_;

    %p = $self->clean_params( %p );

    my $VM = $self->getVM(%p);
    if( !$VM ){
        return retErr("_ERR_VM_NOT_FOUND_","Error virtual machine not found.");
    }

    # uuid and name
    my $uuid = $VM->get_uuid();
    my $name = $VM->get_name();

    # is running
    my $isrunning = $VM->isrunning() || $self->vmIsRunning(%p);
    # live flag
    my $live = defined($p{'live'}) ? $p{'live'} : 1;    # live on by default

    # hypervisor type
    my $htype = $self->get_type();
    # os type
    my $ostype = $VM->get_os_type();

    # new VM
    my $new_VM = $VM->clone();

    # get running VM
    my $xml = $self->get_xml_domain( 'uuid'=>$uuid, 'name'=>$name );
    if( isError($xml) ){
        return wantarray() ? %$xml : $xml;
    }
    my $rVM = VirtMachine->loadfromxml( $xml );

    while( my $VN = $new_VM->last_network() ){
        # if is running and live flag on
        if( $isrunning && $live ){
            my ($oi,$oVN) = $rVM->get_network_i( 'macaddr'=>$VN->get_macaddr() );
            if( $oVN ){
                my $D = $VN->todevice();
                my $S = $self->detachDevice( 'uuid'=>$uuid, name => $name,
                                                devices => { interface => $D } );
                if( isError($S) ){
                    return wantarray() ? %$S : $S;
                }
                $rVM->del_network( i => $oi );
            }
        }
        $VN = $new_VM->del_network( i => $VN->{'i'} );
    }
    $VM = $new_VM;

    # redefine domain
    my %V = $self->defineDomain( $VM->todomain() );
    if( isError(%V) ){
        return wantarray() ? %V : \%V;
    }

    # sync info
    $self->setVM( 'uuid'=>$uuid, 'name'=>$name, 'VM'=>$VM );

    # sleep to make effect: 5s
    sleep(5);

    return retOk("_DETACHALL_INTERFACES_OK_","All interfaces successfully detached.");
}

=item attach_filesystem

attach filesystem device

    my $OK = VirtAgentInterface->attach_filesystem( name=>$name, filesystem=>'type=template,name...,target;type=mount,...' );

    for each filesystem we have:

        types: mount, block, file, template (default)

        params: dir (for type mount), dev (for type block), file (for type file), name (for type template). target for all types 

=cut

# attach_filesystem
#   attach filesystem device
#   args: Hash { type, name, dir, file, dev, target }
#           or Hash { filesystem => 'type=template,name=...,target=...;type=mount,....,....' }
#   res: ok || err
sub attach_filesystem {
    my $self = shift;
    my %p = @_;

    %p = $self->clean_params( %p );

    my $VM = $self->getVM(%p);
    if( !$VM ){
        return retErr("_ERR_VM_NOT_FOUND_","Error virtual machine not found.");
    }

    # prepare filesystem params
    %p = $self->prep_filesystem_params(%p);

    # filesystem devices
    my @Filesystem = ();
    if( ref($p{'filesystem'}) eq 'ARRAY' ){
        @Filesystem = @{$p{'filesystem'}};
    } else {
        my %F = ();

        $F{'target'} = $p{'fstarget'} || $p{'fs'}{'target'};
        if( $F{'target'} ){
            $F{'type'} = $p{'fstype'} || $p{'fs'}{'type'};
            $F{'name'} = $p{'fsname'} || $p{'fs'}{'name'};
            $F{'dir'} = $p{'fsdir'} || $p{'fs'}{'dir'};
            $F{'file'} = $p{'fsfile'} || $p{'fs'}{'file'};
            $F{'dev'} = $p{'fsdev'} || $p{'fs'}{'dev'};

            push(@Filesystem,\%F);
        }
    }
    if( @Filesystem ){

        # uuid and name
        my $uuid = $VM->get_uuid();
        my $name = $VM->get_name();

        # is running
        my $isrunning = $VM->isrunning() || $self->vmIsRunning(%p);
        # live flag
        my $live = defined($p{'live'}) ? $p{'live'} : 1;    # live on by default

        # hypervisor type
        my $htype = $self->get_type();
        # os type
        my $ostype = $VM->get_os_type();

        # new VM
        my $new_VM = $VM->clone();

        # get running VM
        my $xml = $self->get_xml_domain( 'uuid'=>$uuid, 'name'=>$name );
        if( isError($xml) ){
            return wantarray() ? %$xml : $xml;
        }
        my $rVM = VirtMachine->loadfromxml( $xml );

        for my $F (@Filesystem){
            # if is running and live flag on
            if( $isrunning && $live ){
                my $S = $self->attachDevice( 'uuid'=>$uuid, name => $name,
                                                devices => { filesystem => $F } );
                if( isError($S) ){
                    return wantarray() ? %$S : $S;
                }
                $rVM->add_filesystem( %$F );
            }
            # add at end if nothing goes wrong
            my $VF = $new_VM->add_filesystem( %$F );
        }
        $VM = $new_VM;

        # redefine domain for next reboot
        my %V = $self->defineDomain( $VM->todomain() );
        if( isError(%V) ){
            return wantarray() ? %V : \%V;
        }

        # sync info
        $self->setVM( 'uuid'=>$uuid, 'name'=>$name, 'VM'=>$VM );

        return retOk("_ATTACH_FS_OK_","Filesystem successfully attached.");
    } else {
        return retErr("_ERR_ATTACH_FS_","Error no filesystem info defined.");
    }
}

=item detach_filesystem

detach filesystem device

    my $OK = VirtAgentInterface->detach_filesystem( name=>$name, i=>0 );

=cut

# detach_filesystem
#   detach filesystem device
#   args: name - vm name
#            i - fs index 
#   res: ok || err
sub detach_filesystem {
    my $self = shift;
    my %p = @_;

    %p = $self->clean_params( %p );

    my $VM = $self->getVM(%p);
    if( !$VM ){
        return retErr("_ERR_VM_NOT_FOUND_","Error virtual machine not found.");
    }

    # uuid and name
    my $uuid = $VM->get_uuid();
    my $name = $VM->get_name();

    # is running
    my $isrunning = $VM->isrunning() || $self->vmIsRunning(%p);
    # live flag
    my $live = defined($p{'live'}) ? $p{'live'} : 1;    # live on by default

    # hypervisor type
    my $htype = $self->get_type();
    # os type
    my $ostype = $VM->get_os_type();

    # new VM
    my $new_VM = $VM->clone();

    # get running VM
    my $xml = $self->get_xml_domain( 'uuid'=>$uuid, 'name'=>$name );
    if( isError($xml) ){
        return wantarray() ? %$xml : $xml;
    }
    my $rVM = VirtMachine->loadfromxml( $xml );

    my $VF = $new_VM->get_filesystem( i => $p{'i'} );
    if( $VF ){
        # if is running and live flag on
        if( $isrunning && $live ){
            my $S = $self->detachDevice( 'uuid'=>$uuid, name => $name,
                                            devices => { filesystem => $VF } );
            if( isError($S) ){
                return wantarray() ? %$S : $S;
            }
            $rVM->del_filesystem( i => $p{'i'} );
        }
        $VF = $new_VM->del_filesystem( i => $p{'i'} );

        $VM = $new_VM;

        # redefine domain for next reboot
        my %V = $self->defineDomain( $VM->todomain() );
        if( isError(%V) ){
            return wantarray() ? %V : \%V;
        }

        # sync info
        $self->setVM( 'uuid'=>$uuid, 'name'=>$name, 'VM'=>$VM );

        return retOk("_DETACH_FS_OK_","Filesystem successfully detached.");
    } else {
        return retErr("_ERR_DETACH_FS_","Error no filesystem to detach.");
    }
}

sub is_bridgeavailable {
    my $self = shift;
    my ($br) = my %p = @_;
    if( $p{'br'} ){
        $br = $p{'br'};
    }
    return 0 if( !VirtAgent::Network->bravailable($br) );

    my %VL = $self->list_networks();
    
    return ( grep { $_->{'bridge'} eq $br } values %VL ) ? 0 : 1;
}

sub get_bridgeavailable {
    my $self = shift;
    my (%p) = @_;

    my %B = VirtAgent::Network->brcreate_prefix(%p);

    while( !$self->is_bridgeavailable($B{'br'}) ){
        %B = VirtAgent::Network->brcreate_prefix(%B, 'n'=>$B{'n'} );
    }

    return $B{'br'};
}

=item create_network

create network

    my $OK = VirtAgentInterface->create_network( name=>$name );

    my $OK = VirtAgentInterface->create_network( name=>$name, uuid=>$uuid, bridge=>$bridge );

=cut 

# create_network
#   create network
#   args: name 
#         uuid (optional)
#         bridge (optional)
#   return: ok || error
sub create_network {
    my $self = shift;
    my %p = @_;

    %p = $self->clean_params( %p );

    my %X = ();

    my $name = $X{'name'} = $p{'name'};

    if( !$name ){
        return retErr("_ERR_CREATE_NET_NONAME_","Error creating network: need name");
    }

    if( VNETSCache::getVNET($name) ){
        return retErr("_ERR_CREATE_NET_EXIST_","Error creating network: already exists");
    }

    my $uuid = $X{'uuid'} = $p{'uuid'} = $p{'uuid'} || random_uuid(); 

    # gen bridge name
    $X{'bridge'}{'name'} = $p{'bridge'};

    # if not requested generated bridge and if network name doenst exist as bridge, use it as bridge name
    $X{'bridge'}{'name'} ||= ( !$p{'genbr'} && $self->is_bridgeavailable($name) )?
                                                    $name : $self->get_bridgeavailable();

    # bridge stp=off delay=0 by default
    $X{'bridge'}{'stp'} = ($p{'stp'} eq 'on') ? 'on' : 'off';
    $X{'bridge'}{'delay'} = $p{'delay'} || 0;

    # forward options
    if( $p{'forwardmode'} || $p{'forwarddev'} ){
        $X{'forward'}{'mode'} = $p{'forwardmode'} || 'nat';
        $X{'forward'}{'dev'} = $p{'forwarddev'} || VirtAgent::Network->defaultroute();
    }

    # network addressing
    if( my $addr = $p{'ipaddr'} ){
        my ($ip,$netmask) = VirtAgent::Network::get_netmask($addr);

        $X{'ip'}{'address'} = $ip;
        $X{'ip'}{'netmask'} = $netmask;

        # TODO dhcp hosts
        if( $p{'dhcprange'} ){
            my ($start,$end) = split(/;/,$p{'dhcprange'});
            $X{'ip'}{'dhcp'}{'range'} = { 'start' => $start, end => $end };
        }
    }

    # generate xml
    my $xml = VirtAgent->genXMLNetwork( 'network' => \%X );
    plog "create_network xml=",$xml if( &debug_level > 3 );

    my $vm = $self->vmConnect();
    if( isError($vm) ){
        return wantarray() ? %$vm : $vm;
    }

    # create
    my $vn;
    eval {
        $vn = $vm->define_network($xml);
    };
    if( $@ ){
        return retErr("_ERR_CREATE_NETWORK_","Error creating network: $@");
    }

    # CMAR: force to be always autostart
    my $autostart = $p{'autostart'};
    $autostart = 1 if( not defined $autostart );
    if( $autostart ){
        $vn->set_autostart($autostart);
    }

    my $dxml = $vn->get_xml_description();
    plog "create_network xml=",$dxml,"\n" if( &debug_level > 3 );

    $name = $vn->get_name();
    my %N = ( 'name' => $name,
                'uuid' => $vn->get_uuid_string(),
                'bridge' => $vn->get_bridge_name(),
                'autostart' => $vn->get_autostart(), 'active' => 1 );
    
    my %IC = ();    # interface config hash
    my %BC = ();    # bridge config hash

    $BC{'br'} = $N{'bridge'};
    $BC{'stp'} = ( $dxml =~ m/<bridge .*stp='on'/gs ) ? 'on' : 'off';
    $BC{'delay'} = ( $dxml =~ m/<bridge .*delay='(\d+)'/gs ) ? $1 : 0;

    # Physical device to attach to the bridge
    if( $p{'ifout'} || $p{'defaultroute'} || $p{'vlanmake'} || $p{'vlan_untagged'} || $p{'vlan_tagged'} ){

        my $if = my $ifout = $IC{'if'} = $N{'ifout'} = $p{'ifout'} = $p{'ifout'} || VirtAgent::Network->defaultroute();

        if( $p{'vlanmake'} || $p{'vlan_tagged'} ){
            my $vlid = int($p{'vlanid'}) || VirtAgent::Network->nextvlanid( $ifout );

            $if = $ifout = $IC{'if'} = $N{'ifout'} = $p{'ifout'} = VirtAgent::Network->vlan_defaultphysdev(%p);

            # TODO if ifout is bridge add interface attached to the bridge
            my $ifname = "$ifout";
            #my %NetDevs = VirtAgent::Network->getnetdev();
            #if( $NetDevs{"$ifout"}{'isbridge'} ){
            #    my ($If) = grep { ( $_->{'bridge'} eq $ifout ) && ( $_->{'phy'} || $_->{'bonding'} ) } values %NetDevs;
            #    if( $If ){
            #        $ifname = $If->{'device'};
            #    }
            #}

            $IC{'vlan'} = 1;
            $IC{'if'} = $IC{'name'} = $ifname;
            $IC{'vlanid'} = $vlid;

            # force to create vlan like VLAN_NAME_TYPE_PLUS_VID_NO_PAD vlan type
            my %E = VirtAgent::Network->vlancreate( 'iname'=>$ifname, 'vlanid'=>$vlid, 'vlantype'=>'VLAN_NAME_TYPE_PLUS_VID_NO_PAD' );
            if( isError(%E) ){
                # something goes wrong
                $vn->undefine();
                return wantarray() ? %E : \%E;
            }

            my $I = VirtAgent::Network->getvlanif( 'if'=>$ifname, 'id'=>$vlid );

            if( isError($I) ){
                $vn->undefine();
                return wantarray() ? %$I : $I;
            }

            $if = $N{'ifout'} = $p{'ifout'} = $I->{'device'};

            # create network boot script
            my %br = ( 'name'=>$N{'bridge'}, 'type'=>'Bridge', 'bootproto'=>'none', 'up'=>1 );
            $br{'stp'} = $BC{'stp'} if( defined($BC{'stp'}) );
            $br{'delay'} = $BC{'delay'} if( defined($BC{'delay'}) );
            VirtAgent::Network->save_boot_interface( 'name'=>$N{'bridge'}, %br );

        } else {
            my ($ipaddr,$netmask,$network,$gateway) = VirtAgent::Network->get_ipaddr( 'if'=>$if );

            # network addressing
            if( $ipaddr ){

                $X{'ip'}{'address'} = $ipaddr;
                $X{'ip'}{'netmask'} = $netmask;

                # generate xml
                my $xml_2 = VirtAgent->genXMLNetwork( 'network' => \%X );
                plog "xml_2=",$xml_2 if( &debug_level > 3 );

                eval {
                    $vn = $vm->define_network($xml_2);
                };
                if( $@ ){
                    # something goes wrong
                    $self->load_vnets();
                    $self->destroy_network( %p );

                    return retErr("_ERR_CREATE_NETWORK_","Error creating network: $@");
                }

                # change ip address
                # clear bridge when set ip
                $IC{'ipaddr'} = '0.0.0.0';
                VirtAgent::Network->boot_chgipaddr( 'if'=>$if, 'ipaddr'=>'0.0.0.0', 'netmask'=>'', 'network'=>'', 'bridge'=>'', bootproto=>'static' );
                my %br = ( 'name'=>$N{'bridge'}, 'address'=>$ipaddr, 'type'=>'Bridge', 'bootproto'=>'static', 'up'=>1 );
                $br{'stp'} = $BC{'stp'} if( defined($BC{'stp'}) );
                $br{'delay'} = $BC{'delay'} if( defined($BC{'delay'}) );
                $BC{'ipaddr'} = $ipaddr;
                $BC{'netmask'} = $br{'netmask'} = $netmask if( $netmask );
                $BC{'gateway'} = $br{'gateway'} = $gateway if( $gateway );
                VirtAgent::Network->save_boot_interface( 'name'=>$N{'bridge'}, %br );
            } else {
                VirtAgent::Network->boot_chgipaddr( 'if'=>$if, 'ipaddr'=>'0.0.0.0', 'netmask'=>'', 'network'=>'', 'bridge'=>'', bootproto=>'static' );
                my %br = ( 'name'=>$N{'bridge'}, 'type'=>'Bridge', 'bootproto'=>'static', 'up'=>1 );
                VirtAgent::Network->save_boot_interface( 'name'=>$N{'bridge'}, %br );
            }
        }

        eval {
            # create it
            $vn->create();
        };
        if( $@ ){
            # something goes wrong
            $self->load_vnets();
            $self->destroy_network( %p );

            return retErr("_ERR_CREATE_NETWORK_","Error creating network: $@");
        }

        # update network info
        VirtAgent::Network->loadnetinfo(1);

        $IC{'bridge'} = $N{'bridge'};
        my %E = VirtAgent::Network->boot_braddif( 'br'=>$N{'bridge'}, 'if'=>$if );
        if( isError(%E) ){
            # something goes wrong
            $self->load_vnets();
            $self->destroy_network( %p );
            return wantarray() ? %E : \%E;
        }

        if( $BC{'gateway'} ){
            #cmd_exec("route add default gw $BC{'gateway'} dev $N{'bridge'}");
            VirtAgent::Network->addgateway($N{'bridge'},$BC{'gateway'});
        }

        # CMAR - #745 - do not use scripts
        # write to etva script
        #VirtAgent::Network->add_br_toscript( %BC );
        #if( $IC{'vlan'} ){
        #    VirtAgent::Network->add_vlan_toscript( %IC );
        #} else {
        #    VirtAgent::Network->add_if_toscript( %IC );
        #}

        # restart brigde interface
        #VirtAgent::Network->ifrestart( 'if'=>$N{'bridge'} );
    } else {
        eval {
            # create it
            $vn->create();
        };
        if( $@ ){
            # something goes wrong
            $self->load_vnets();
            $self->destroy_network( %p );

            return retErr("_ERR_CREATE_NETWORK_","Error creating network: $@");
        }
    }

    # load virtual networks
    $self->load_vnets();

    return retOk("_CREATE_NETWORK_OK_","Network created successful.","_RET_OBJ_",\%N);
}

=item active_network

activate network

    my $OK = VirtAgentInterface->active_network( name=>$name );

    my $OK = VirtAgentInterface->active_network( uuid=>$uuid );

=cut

sub active_network {
    my $self = shift;
    my ($name,$uuid) = my %p = @_;
    if( $p{'name'} || $p{'uuid'} ){
        $name = $p{'name'};
        $uuid = $p{'uuid'};
    }

    my $vn;
    my $vm = $self->vmConnect();
    eval {
        if( $uuid ){
            $vn = $vm->get_network_by_uuid($uuid);
        } elsif( $name ){
            $vn = $vm->get_network_by_name($name);
        }
    };
    if( $@ ){
        return retErr("_ERR_NET_LOOKUP_","Error lookup network: $@");
    }

    eval {
        $vn->create();
    };
    if( $@ ){
        return retErr("_ERR_NET_ACTIVATE_","Error trying activate network: $@");
    }

    # update state
    $self->load_vnets();

    retErr("_OK_NET_ACTIVATE_","Network activate successfully");
}

sub set_autostart_network {
    my $self = shift;
    my ($name,$uuid) = my %p = @_;
    if( $p{'name'} || $p{'uuid'} ){
        $name = $p{'name'};
        $uuid = $p{'uuid'};
    }

    my $vn;
    my $vm = $self->vmConnect();
    eval {
        if( $uuid ){
            $vn = $vm->get_network_by_uuid($uuid);
        } elsif( $name ){
            $vn = $vm->get_network_by_name($name);
        }
    };
    if( $@ ){
        return retErr("_ERR_NET_LOOKUP_","Error lookup network: $@");
    }

=pod

=begin comment    # only Sys::Virt 0.2.3 (requires libvirt 0.7.5)


    if( !$vn->is_active() ){
        eval {
            $vn->create();
        };
        if( $@ ){
            return retErr("_ERR_NET_ACTIVATE_","Error trying activate network: $@");
        }
    }

=end comment

=cut

    if( !$vn->get_autostart() ){
        eval {
            $vn->set_autostart(1);
        };
        if( $@ ){
            return retErr("_ERR_NET_SET_AUTOSTART_","Error trying configure network autostart: $@");
        }
    } else {
        return retErr("_ERR_NET_SET_AUTOSTART_","Error network already set to autostart: $@");
    }

    # update state
    $self->load_vnets();

    retErr("_OK_NET_SET_AUTOSTART_","Network autostart configure successfully");
}

=item list_networks

    my $Hash = VirtAgentInterface->list_networks( );

=cut

# list_networks
#   list of networks
#   args: empty
#   res: Hash { name => info }
sub list_networks {
    my $self = shift;
    my ($force) = my %p = @_;
    $force = 1 if( $p{'force'} );

    my %VNets = VNETSCache::allVNETS();
    if( $force || !%VNets ){
        %VNets = VNETSCache::resetVNETS();
        $self->load_vnets();
        %VNets = VNETSCache::allVNETS();
    }

    return wantarray() ? %VNets : \%VNets;
}

=item create_networks

create multiple networks

    my $OK = VirtAgentInterface->create_networks( network=>'name=...,uuid=....;name=...,bridge=...' );

=cut

# create_networks
#   multiple networks creation
#   args: { networks => 'name=...,uuid=...;name=...' }
#   res: ok || error
sub create_networks {
    my $self = shift;
    my %p = @_;

    my @Networks = ();
    if( my $networks = $p{'networks'} ){
        if( !ref($networks) ){
            for my $net (split(/;/,$networks)){
                my %N = ();
                for my $field (split(/,/,$net)){
                    my ($f,$v) = split(/=/,$field,2);
                    $N{"$f"} = $v;
                }
                push(@Networks,\%N);
            }
        } elsif( ref($networks) eq 'ARRAY' ){
            @Networks = @$networks;
        } elsif( ref($networks) eq 'HASH' ){
            @Networks = values %$networks;
        }
    }

    for my $N (@Networks){
        $self->create_network( %$N );
    }

    # load virtual networks
    $self->load_vnets();

    # TODO change this
    return retOk("_OK_","ok");
}

sub load_vnets {
    my $self = shift;

    my $vm = $self->vmConnect();

    # update network info
    VirtAgent::Network->loadnetinfo(1);

    my %NetDevs = VirtAgent::Network->getnetdev();

    my @lnetdevs_out = grep { ( $_->{'phy'} || $_->{'vlan'} || $_->{'bonding'} ) && $_->{'bridge'} } values %NetDevs;

    my @run_nets;
    eval {
        @run_nets = $vm->list_networks();
    };
    for my $N (@run_nets){

        my $name;
        eval{ $name = $N->get_name(); };
        if( $@ ){
            plog "load_vnets: Error network info: $@\n" if( &debug_level );
        }

        my $uuid;
        eval{ $uuid = $N->get_uuid_string(); };
        if( $@ ){
            plog "load_vnets: Error network info: $@\n" if( &debug_level );
        }
    
        my $br;
        eval{ $br = $N->get_bridge_name(); };
        if( $@ ){
            plog "load_vnets: Error network info: $@\n" if( &debug_level );
        }
    
        my $autostart;
        eval{ $autostart = $N->get_autostart(); };
        if( $@ ){
            plog "load_vnets: Error network info: $@\n" if( &debug_level );
        }

        my $VN = { 'name' => $name,
                            'uuid' => $uuid,
                            'bridge' => $br,
                            'autostart' => $autostart, 'active' => 1 };

        my ($I) = grep { $_->{'bridge'} && ($_->{'bridge'} eq $br) } @lnetdevs_out;
        if( $I ){
            $VN->{'ifout'} = $I->{'device'};
        }
        VNETSCache::setVNET( $name, $VN );
    }

    my @notrun_nets;
    eval {
        @notrun_nets = $vm->list_defined_networks();
    };
    for my $N (@notrun_nets){

        my $name;
        eval{ $name = $N->get_name(); };
        if( $@ ){
            plog "load_vnets: Error network info: $@\n" if( &debug_level );
        }

        my $uuid;
        eval{ $uuid = $N->get_uuid_string(); };
        if( $@ ){
            plog "load_vnets: Error network info: $@\n" if( &debug_level );
        }
    
        my $br;
        eval{ $br = $N->get_bridge_name(); };
        if( $@ ){
            plog "load_vnets: Error network info: $@\n" if( &debug_level );
        }
    
        my $autostart;
        eval{ $autostart = $N->get_autostart(); };
        if( $@ ){
            plog "load_vnets: Error network info: $@\n" if( &debug_level );
        }

        my $VN = { 'name' => $name,
                        'uuid' => $uuid,
                        'bridge' => $br,
                        'autostart' => $autostart, 'active' => 0 };

        my ($I) = grep { $_->{'bridge'} && ($_->{'bridge'} eq $br) } @lnetdevs_out;
        if( $I ){
            $VN->{'ifout'} = $I->{'device'};
        }
        VNETSCache::setVNET( $name, $VN );
    }

    # TODO change this
    return retOk("_OK_","ok");
}

=item destroy_network

    my $OK = VirtAgentInterface->destroy_network( name=>$name );

    my $OK = VirtAgentInterface->destroy_network( uuid=>$uuid );

=cut

# destroy_network
#   network destroy
#   args: name || uuid
#   res: ok || error
sub destroy_network {
    my $self = shift;
    my ($name,$uuid) = my %p = @_;
    if( $p{'name'} || $p{'uuid'} ){
        $name = $p{'name'};
        $uuid = $p{'uuid'};
    }

    my $vm = $self->vmConnect();

    my $VN;

    if( $uuid ){
        $VN = $vm->get_network_by_uuid($uuid);
    } elsif( $name ){
        $VN = $vm->get_network_by_name($name);
    }
    if( $VN ){

        $name = $VN->get_name();

        # Network info
        my $N = VNETSCache::getVNET($name);
        my $br = $N->{'bridge'};

        if( $N->{'ifout'} || $p{'ifout'} ){

            my $ifout = $N->{'ifout'} || $p{'ifout'};
            VirtAgent::Network->boot_brdelif( 'br'=>$br, 'if'=>$ifout );

            my ($ipaddr,$netmask,$network,$gateway) = VirtAgent::Network->get_ipaddr( 'if'=>$br );

            # remove bridge boot interface
            VirtAgent::Network->del_boot_interface( 'name'=>$br );

            # network addressing
            if( $ipaddr ){

                # take down bridge
                cmd_exec("/sbin/ifconfig",$br,"0.0.0.0","down");

                # change ip address
                VirtAgent::Network->boot_chgipaddr( 'if'=>$ifout, 'ipaddr'=>$ipaddr, 'netmask'=>$netmask, 'network'=>$network, 'gateway'=>$gateway ? $gateway : undef );
            }

            # Remove VLAN
            my %NetDevs = VirtAgent::Network->getnetdev();
            if( $NetDevs{"$ifout"}{'vlan'} ){
                VirtAgent::Network->vlanremove( $ifout );
            }

            VirtAgent::Network->del_if_toscript( 'if'=>$ifout );
        }
        VirtAgent::Network->del_if_toscript( 'if'=>$br );

        eval {
            $VN->destroy();
        };
        if( $@ ){
            # destroy network fail
            eval {
                # try undefine it
                $VN->undefine();
            };
        } else {
            eval {
                # try undefine it
                $VN->undefine();
            };
        }

        # remove frome cache
        VNETSCache::delVNET($name);

        # load virtual networks
        $self->load_vnets();

        # TODO
        my $net = $uuid || $name;
        return retOk("_OK_NET_DESTROY_","Network '$net' destroyed.");
    } else {
        return retErr("_ERR_NET_DESTROY_NONET_","Error not found network to destroy.");
    }
}

=item undefine_network 

drop network

    my $OK = VirtAgentInterface->undefine_network( name=>$name );

    my $OK = VirtAgentInterface->undefine_network( uuid=>$uuid );

=cut

sub undefine_network {
    my $self = shift;
    my ($name,$uuid) = my %p = @_;
    if( $p{'name'} || $p{'uuid'} ){
        $name = $p{'name'};
        $uuid = $p{'uuid'};
    }

    my $vm = $self->vmConnect();

    my $VN;

    if( $uuid ){
        $VN = $vm->get_network_by_uuid($uuid);
    } elsif( $name ){
        $VN = $vm->get_network_by_name($name);
    }
    if( $VN ){
        $VN->undefine();

        $name = $VN->get_name();

        if( VNETSCache::getVNETifout($name) ){
            my $ifout = VNETSCache::getVNETifout($name);
            # Remove VLAN
            my %NetDevs = VirtAgent::Network->getnetdev();
            if( $NetDevs{"$ifout"}{'vlan'} ){
                VirtAgent::Network->vlanremove( $ifout );
            }
        }

        # load virtual networks
        $self->load_vnets();

        # TODO
        my $net = $uuid || $name;
        return retOk("_OK_NET_UNDEFINE_","Network '$net' undefined.");
    } else {
        return retErr("_ERR_NET_DESTROY_NONET_","Error not found network to undefine.");
    }
}

sub getstate {
    my $self = shift;
    return retOk("_OK_STATE_","I'm alive.");
}

=item updatestate_vm

virtual machine update state function

    my $OK = VirtAgentInterface->updatestate_vm( name=>$name );

=cut

# updatestate_vm
#   func wrapper to update vm state
sub updatestate_vm {
    my $self = shift;
    my %p = @_;

    my $sync = defined($p{'sync'}) ? $p{'sync'} : 1;
    my $VM = $self->updateStateVM( %p, 'sync'=>$sync );

    my %H = $VM->tohash();

    return retOk("_VM_UPDATESTATE_OK_","Virtual machine state successfully updated","_RET_OBJ_",\%H);
}

sub updateStateVM {
    my $self = shift;
    my %p = @_;

    my $VM = $self->getVM(%p);
    if( !$VM ){
        return retErr("_ERR_VM_NOT_FOUND_","Error virtual machine not found.");
    }

    my $xml = $self->get_xml_domain( 'uuid'=>$VM->get_uuid(), 'name'=>$VM->get_name() );
    if( isError($xml) ){
        return wantarray() ? %$xml : $xml;
    }

    if( $p{'sync'} ){
        $VM = $VM->loadfromxml( $xml );
    } else {
        $VM = VirtMachine->loadfromxml( $xml );
    }

    return $VM;
}

sub get_hypervisor_type {
    my $self = shift;

    if( !$hypervisor_type ){
        $self->loadsysinfo();
    }
    return $hypervisor_type;
}

sub hypervisor_type {
    my $self = shift;

    my $type = $self->get_type();

    my $have_kvm = $self->have_kvm_support();
    my $have_hvm = $self->have_hvm_support();
    
    if( $type eq 'xen' ){
        if( $have_hvm ){
            $hypervisor_type = 'hvm+xen';
        } else {
            $hypervisor_type = 'xen';
        }
    } else {
        if( $have_kvm ){
            $hypervisor_type = 'kvm';
        } else {
            $hypervisor_type = $type;
        }
    }
    return $hypervisor_type
}

sub os_type {
    my $self = shift;
    my (%p) = @_;

    if( !$p{'os'}{'type'} ){
        if( ( ( $p{'vm_type'} eq 'linux' ) ||        # force linux
               ( $p{'vm_type'} eq 'pv' ) )
                && ( $self->get_hypervisor_type() =~ m/xen/ ) ){    # only if xen type
            $p{'os'}{'type'} = 'linux';
        } elsif( ( $p{'vm_type'} eq 'kvm' )     # force kvm
                        && $self->have_kvm_support() ){
            return $p{'os'}{'type'} = 'hvm';
        } elsif( ( $p{'vm_type'} eq 'hvm' )     # force hvm
                        && $self->have_hvm_support() ){
            return $p{'os'}{'type'} = 'hvm';
        } elsif( $self->have_kvm_support() ){   # try kvm
            return $p{'os'}{'type'} = 'hvm';
        } elsif( $self->have_hvm_support() ){   # try hvm
            return $p{'os'}{'type'} = 'hvm';
        } else {                                # otherwise linux type
            return $p{'os'}{'type'} = 'linux';
        }
    }
    return $p{'os'}{'type'};
}
sub os_loader {
    my $self = shift;
    my (%p) = @_;

    if( !$p{'os'}{'loader'} ){
        $p{'os'}{'type'} = $self->os_type(%p);

        if( ($p{'os'}{'type'} eq 'hvm') && ($self->get_hypervisor_type() =~ m/xen/) ){
            return $p{'os'}{'loader'} = '/usr/lib/xen/boot/hvmloader';
        }
    }
    return $p{'os'}{'loader'};
}
sub set_vm_bootloader {
    my $self = shift;
    my (%p) = @_;

    my $VM = $p{'VM'};

    if( $VM ){
        $p{'os'}{'loader'} = $self->os_loader(%p);
        if( $p{'os'}{'loader'} ){
            $VM->set_os_loader( $p{'os'}{'loader'} );
        } else {
            $VM->set_bootloader(1);
        }
    }
    return $VM;
}

sub domains_stats {
    my $self = shift;

    my @ls = $self->domainStats();
    my @gls = ();

    for my $d (@ls){
        if( $d->{'id'} && ( $d->{'name'} ne 'Domain-0' ) ){
            plogNow("domains_stats name=",$d->{'name'}," uuid=",$d->{'uuid'}," state=",$d->{'state'}) if( &debug_level > 5 );

            if( my $VM = $self->getVM( $d->{'uuid'} ) ){
                $VM->set_state( $d->{'state'} );    # update state
                plogNow("domains_stats name=",$d->{'name'}," uuid=",$d->{'uuid'}," state=",$d->{'state'}," ...Found") if( &debug_level > 5 );

                $self->setVM( 'uuid'=>$VM->get_uuid(), 'name'=>$VM->get_name(), 'VM'=>$VM );
                push(@gls,$d);
            } else {
                plogNow("domains_stats name=",$d->{'name'}," uuid=",$d->{'uuid'}," state=",$d->{'state'}," ...Not Found") if( &debug_level > 5 );
            }
        }
    }
    return wantarray() ? @gls : \@gls;
}

=item migrate_vm

Virtual machine host migration

    my $OK = $self->migrate_vm( duri=>$uri, name=>$name, live=>1 );    

    args:

            duri       - destination host uri

            daddr      - destination host address

            name       - source virtual machine

            uuid       - source virtual machine

            dname      - destination name virtual machine
            
            migrateuri - alternative migrate uri

            live       - migration live flag

            bandwidth  - bandwidth

            dagentname - destination agent name

    return: 

            OK - return ok message with domain info

            Error - error message

=cut

sub migrate_vm {
    my $self = shift;
    my %p = @_;

    my $ht = $p{'hypervisor'} || $self->hypervisor_type();

    my $uri = $p{'duri'} || $p{'daddr'};
    # try to determinate uri for connection
    if( $uri !~ m/\w+(\+\w+)?:\/\/[^\/]*\/\w+/ ){
        # Connect to the  "default" hypervisor running on remote host using TLS
        my $addr = $p{'address'} || $p{'daddr'};

        my $extra = ""; # extra params
        my $no_verify = $p{'verify'} ? "" : "?no_verify=1";
        $extra .= "$no_verify";

        # TODO add more extra params
        #$extra .= "&" if( $extra );

        $p{'duri'} = $uri = "remote://$addr/$extra";
        delete $p{'daddr'};
    }

    # by default: persist on destination node and undefine on source node
    if( !defined($p{'persist_dest'}) && !defined($p{'undefine_source'}) ){
        $p{'persist_dest'} = 1;
        $p{'undefine_source'} = 1;
    }

    # TODO if dagentname check hostname
    if( $p{'dagentname'} && ($p{'duri'} =~ m/:\/\/(\d+\.\d+\.\d+\.\d+)\//) ){
        my $agentip = $1;
        my $agentname = $p{'dagentname'};
        ETVA::NetworkTools::fix_hostname_resolution($agentname,$agentip);
    }
    
    my %E = $self->vmMigrate(%p);
    if( !isError(%E) ){
        plogNow("migrate_vm delete VM exists") if( &debug_level > 5 );
        $self->delVM( %p ); # delete VM
        plogNow("migrate_vm check if VM exists") if( &debug_level > 5 );
        #my $VM = $self->getVM(%p, 'force'=>1 ); # force to get actualized
        my $VM = $self->getVM(%p); # force to get actualized
        if( $VM ){  # if still there
            my $uuid = $VM->get_uuid();
            my $name = $VM->get_name();

            plogNow("migrate_vm check if domain exists") if( &debug_level > 5 );
            my $dom = $self->getDomain( 'uuid'=>$uuid, 'name'=>$name );
            if( !isError($dom) ){        # if we can get it

                plogNow("migrate_vm still there uuid=$uuid name=$name") if( &debug_level > 5 );
                                        # destroyed at source
                $self->delVM( 'uuid'=>$uuid, 'name'=>$name );
            }
        }
    }
    plogNow("migrate_vm .... end.") if( &debug_level > 5 );
    return wantarray() ? %E : \%E; 
}

sub storage_pool_to_hash {
    my $self = shift;
    my ($sp) = @_;

    my $info = $sp->get_info();
    my %SP = ( %$info );

    eval { $SP{'name'} = $sp->get_name(); };
    eval { $SP{'uuid'} = $sp->get_uuid_string(); };
    
#        eval { $SP{'is_active'} = $sp->is_active(); };
#        eval { $SP{'is_persistent'} = $sp->is_persistent(); };

    my $state = $SP{'state'};
    $SP{'state'} = 'INACTIVE' if( $state eq Sys::Virt::StoragePool::STATE_INACTIVE );
    $SP{'state'} = 'BUILDING' if( $state eq Sys::Virt::StoragePool::STATE_BUILDING );
    $SP{'state'} = 'RUNNING' if( $state eq Sys::Virt::StoragePool::STATE_RUNNING );
    $SP{'state'} = 'DEGRADED' if( $state eq Sys::Virt::StoragePool::STATE_DEGRADED );

    my $xml = $sp->get_xml_description();
    my %SPi = VirtAgent::Storage::xml_storage_pool_parser($xml);
    %SP = (%SP,%SPi);

    $SP{'volumes'} = $self->list_storage_volumes( $sp );

    return wantarray() ? %SP : \%SP;
}

=item list_storage_pools

    list storage pools

=cut

sub list_storage_pools {
    my $self = shift;

    my $vm = $self->vmConnect();

    my @l = ();

    my @pools = $vm->list_storage_pools();
    for my $sp (@pools){
        my %SP = $self->storage_pool_to_hash( $sp );
        $SP{'active'} = 1;
        push(@l,\%SP);
    }

    my @defined_pools = $vm->list_defined_storage_pools();
    for my $sp (@defined_pools){
        my %SP = $self->storage_pool_to_hash( $sp );
        $SP{'active'} = 0;
        push(@l,\%SP);
    }
    

    return wantarray() ? @l : \@l;
}

sub storage_volume_to_hash {
    my $self = shift;
    my ($sv) = @_;

    my $info = $sv->get_info();
    my %SV = ( %$info );

    eval { $SV{'name'} = $sv->get_name(); };
    eval { $SV{'key'} = $sv->get_key(); };
    eval { $SV{'path'} = $sv->get_path(); };
    
    my $type = $SV{'type'};
    $SV{'type'} = 'FILE' if( $type eq Sys::Virt::StorageVol::TYPE_FILE );
    $SV{'type'} = 'BLOCK' if( $type eq Sys::Virt::StorageVol::TYPE_BLOCK );

    my $xml = $sv->get_xml_description();
    my %SVi = VirtAgent::Storage::xml_storage_volume_parser($xml);
    %SV = (%SV,%SVi);

    return wantarray() ? %SV : \%SV;
}

=item list_storage_volumes

    list volumes of storage pool

=cut

sub list_storage_volumes {
    my $self = shift;
    my ($sp) = my %p = @_;

    my $vm = $self->vmConnect();

    if( ref($sp) ne 'Sys::Virt::StoragePool' ){
        if( $p{'pool_uuid'} || $p{'uuid'} ){
            my $uuid = $p{'pool_uuid'} || $p{'uuid'};
            eval { $sp = $vm->get_storage_pool_by_uuid($uuid); };
        } elsif( $p{'pool_name'} || $p{'name'} ){
            my $name = $p{'pool_name'} || $p{'name'};
            eval { $sp = $vm->get_storage_pool_by_name($name); };
        }
    }

    if( !$sp || ( ref($sp) ne 'Sys::Virt::StoragePool' ) ){
        return retErr("_ERR_LS_STORAGE_VOLUMES_","Error list storage volumes of unknown storage pool.");
    }

    my @l = ();

    my @vols = ();
    eval { @vols = $sp->list_volumes(); };
    for my $sv (@vols){

        my %SV = $self->storage_volume_to_hash( $sv );

        push(@l,\%SV);
    }

    return wantarray() ? @l : \@l;
}

my @SPoolTypes = qw( dir fs netfs disk iscsi logical );

=item create_storage_pool

    Create storage pool

    args:

        name - pool name

        type - pool type

        source_device - list of source devices
        
        source_host - host
        
        source_port - source port

        source_dir - source directory

        path - pool target path

=cut

sub prep_storage_pool_source_params {
    my $self = shift;
    my (%p) = @_;

    my %source = ();

    if( my $device = $p{'source_device'} ){
        my @ld = ref($device) eq 'ARRAY' ? @$device : ($device);
        for my $d (@ld){
            my %D = ( ref($d) ? %$d : ('path'=>$d) );
            push(@{$source{'device'}}, \%D);
        }
    }
    if( $p{'source_directory'} ){
        $source{'directory'}{'path'} = $p{'source_directory'};
    }
    if( $p{'source_dir'} ){
        $source{'dir'}{'path'} = $p{'source_dir'};
    }
    if( $p{'source_adapter'} ){
        $source{'adapter'}{'name'} = $p{'source_adapter'};
    }
    if( $p{'source_host'} ){
        $source{'host'}{'name'} = $p{'source_host'};
        $source{'host'}{'port'} = $p{'source_port'} if( $p{'source_port'} );
    }
    if( $p{'source_name'} ){
        $source{'name'} = $p{'source_name'};
    }
    if( $p{'source_format'} ){
        $source{'format'}{'type'} = $p{'source_format'};
    }

    return wantarray() ? %source : \%source;
}
sub prep_storage_pool_params {
    my $self = shift;
    my (%p) = @_;

    my %pool = ();

    $pool{"name"} = $p{'name'};
    if( !$pool{"name"} ){
        return retErr("_ERR_CREATE_STORAGE_POOL_","Error creating storage pool: no name defined.");
    }

    $pool{'uuid'} = $p{'uuid'} || random_uuid();

    my $type = $pool{"type"} = $p{'type'};
    if( !$type ){
        return retErr("_ERR_CREATE_STORAGE_POOL_","Error creating storage pool: no type specified.");
    }
    if( ! grep { $type eq $_ } @SPoolTypes ){
        return retErr("_ERR_CREATE_STORAGE_POOL_","Error creating storage pool: invalid type.");
    }

    $pool{'allocation'} = str2size($p{'allocation'}) if( defined $p{'allocation'} );
    $pool{'capacity'} = str2size($p{'capacity'}) if( defined $p{'capacity'} );
    $pool{'available'} = str2size($p{'available'}) if( defined $p{'available'} );

    if( my %source = $self->prep_storage_pool_source_params(%p) ){
        $pool{'source'} = { %source };
    }

    if( $p{'target_path'} || $p{'path'} ){
        my $path = $pool{'target'}{'path'} = $p{'target_path'} || $p{'path'};

        if( ! -e "$path" ){
            # create it
            mkpath( "$path" );
        }
    } else {
        return retErr("_ERR_CREATE_STORAGE_POOL_","Error creating storage pool: no target path specified.");
    }

    if( $p{'permissions_owner'} ||
        $p{'permissions_group'} ||
        $p{'permissions_mode'} ||
        $p{'permissions_label'} ){
        $pool{'target'}{'permissions'}{'owner'} = $p{'permissions_owner'} || '-1';
        $pool{'target'}{'permissions'}{'group'} = $p{'permissions_group'} || '-1';
        $pool{'target'}{'permissions'}{'mode'} = $p{'permissions_mode'} || '0700';
        $pool{'target'}{'permissions'}{'label'} = $p{'permissions_label'} if( $p{'permissions_label'} );
    }
    if( $p{'target_encryption_type'} || $p{'encryption_type'} ){
        $pool{'target'}{'encryption'}{'type'} = $p{'target_encryption_type'} || $p{'encryption_type'};
    }

    return wantarray() ? %pool : \%pool;
}

sub create_storage_pool {
    my $self = shift;
    my (%p) = @_;

    my $vm = $self->vmConnect();
    if( isError($vm) ){
        return wantarray() ? %$vm : $vm;
    }

    plog "hash=",Dumper(\%p) if( &debug_level > 3 );

    my %pool = $self->prep_storage_pool_params(%p);
    if( isError(%pool) ){
        return wantarray() ? %pool : \%pool;
    }

    my $xml = VirtAgent::Storage->gen_xml_storage_pool( %pool );

    plog "xml=",$xml if( &debug_level > 3 );

    my $SP;
    
    eval { $SP = $vm->define_storage_pool( $xml ); };
    if( $@ ){
        return retErr("_ERR_CREATE_STORAGE_POOL_","Error creating storage pool: $@");
    }

    eval { $SP->create(); };
    if( $@ ){
        return retErr("_ERR_CREATE_STORAGE_POOL_","Error creating storage pool: $@");
    }

    # CMAR: force to be always autostart
    my $autostart = $p{'autostart'};
    $autostart = 1 if( not defined $autostart );
    if( $autostart ){
        eval { $SP->set_autostart($autostart); };
        if( $@ ){
            plogNow("_ERR_CREATE_STORAGE_POOL_"," Error make storage pool autostart: $@");
        }
    }

    my %P = $self->storage_pool_to_hash( $SP );
    return retOk("_CREATE_STORAGE_POOL_OK_","Storage Pool created successful.","_RET_OBJ_",\%P);
}

=item find_storage_pool_source

    Find storage pool source

    args:

        type - pool type

        source_device - list of source devices
        
        source_host - host
        
        source_port - source port

        source_dir - source directory

=cut

sub find_storage_pool_source {
    my $self = shift;
    my (%p) = @_;

    my $vm = $self->vmConnect();
    if( isError($vm) ){
        return wantarray() ? %$vm : $vm;
    }

    plog "hash=",Dumper(\%p) if( &debug_level > 3 );

    my $type = $p{'type'};
    if( !$type ){
        return retErr("_ERR_FIND_STORAGE_POOL_SOURCE_","Error find storage pool source: no type specified.");
    }
    if( ! grep { $type eq $_ } @SPoolTypes ){
        return retErr("_ERR_FIND_STORAGE_POOL_SOURCE_","Error find storage pool source: invalid type.");
    }

    my %source = $self->prep_storage_pool_source_params(%p);
    if( isError(%source) ){
        return wantarray() ? %source : \%source;
    }

    my $xml = VirtAgent::Storage->gen_xml_storage_pool_source( 'source'=>{ %source } );

    plog "xml=",$xml if( &debug_level > 3 );

    my $xml_out;
    
    eval { $xml_out = $vm->find_storage_pool_sources( $type, $xml ); };
    if( $@ ){
        return retErr("_ERR_FIND_STORAGE_POOL_SOURCE_","Error to find storage pool source: $@");
    }

    plog "xml=",$xml if( &debug_level > 3 );

    return VirtAgent::Storage::xml_storage_pool_source_parser( $xml_out );
}

=item reload_storage_pool

    Reload storage pool and create if if doesn't exists

    args:

        force - force to create

        uuid - pool uuid

        name - pool name

=cut

sub reload_storage_pool {
    my $self = shift;
    my ($sp) = my %p = @_;

    my $vm = $self->vmConnect();
    if( isError($vm) ){
        return wantarray() ? %$vm : $vm;
    }

    if( ref($sp) ne 'Sys::Virt::StoragePool' ){
        if( $p{'uuid'} ){
            eval { $sp = $vm->get_storage_pool_by_uuid($p{'uuid'}); };
        } elsif( $p{'name'} ){
            eval { $sp = $vm->get_storage_pool_by_name($p{'name'}); };
        }
    }

    if( $p{'force'} || !$sp || ( ref($sp) ne 'Sys::Virt::StoragePool' ) ){
        return $self->create_storage_pool(%p);
    } else {
        # TODO do reload ??
        my $msg = "";
        eval { $sp->refresh(); };
        if( $@ ){
            $msg .= "$@";
            return retErr("_ERR_RELOAD_STORAGE_POOL_","Error reload storage pool: $msg");
        }
    }
    my %P = $self->storage_pool_to_hash( $sp );
    return retOk("_RELOAD_STORAGE_POOL_OK_","Storage Pool reloaded successful.","_RET_OBJ_",\%P);

}

=item destroy_storage_pool

    Destroy storage pool

    args:

        name - pool name

        uuid - pool uuid

=cut

sub destroy_storage_pool {
    my $self = shift;
    my ($sp) = my %p = @_;

    my $vm = $self->vmConnect();
    if( isError($vm) ){
        return wantarray() ? %$vm : $vm;
    }

    if( ref($sp) ne 'Sys::Virt::StoragePool' ){
        if( $p{'uuid'} ){
            eval { $sp = $vm->get_storage_pool_by_uuid($p{'uuid'}); };
        } elsif( $p{'name'} ){
            eval { $sp = $vm->get_storage_pool_by_name($p{'name'}); };
        }
    }

    if( !$sp || ( ref($sp) ne 'Sys::Virt::StoragePool' ) ){
        return retErr("_ERR_DESTROY_STORAGE_POOL_","Error destroy unknown storage pool.");
    }

    my %P = $self->storage_pool_to_hash( $sp );
    my $msg = "";
    eval { $sp->destroy(); };
    if( $@ ){
        $msg .= "$@";
    }

    eval { $sp->undefine(); };
    if( $@ ){
        $msg .= "\n" if( $msg );
        $msg .= "$@";
        return retErr("_ERR_DESTROY_STORAGE_POOL_","Error destroy storage pool: $msg");
    }

    return retOk("_DESTROY_STORAGE_POOL_OK_","Storage Pool destroyed successful.","_RET_OBJ_",\%P);
}

=item create_storage_volume

    args:

        pool_name - pool name

        pool_uuid - pool uuid

        name - volume name / alias

        capacity - volume size

        allocation - allocation size

        path - volume path

        format - volume format

=cut

sub create_storage_volume {
    my $self = shift;
    my (%p) = @_;

    my $vm = $self->vmConnect();
    if( isError($vm) ){
        return wantarray() ? %$vm : $vm;
    }

    my $sp = $p{'pool'};

    if( ref($sp) ne 'Sys::Virt::StoragePool' ){
        if( $p{'pool_uuid'} ){
            eval { $sp = $vm->get_storage_pool_by_uuid($p{'pool_uuid'}); };
        } elsif( $p{'pool_name'} ){
            eval { $sp = $vm->get_storage_pool_by_name($p{'pool_name'}); };
        }
    }

    if( !$sp || ( ref($sp) ne 'Sys::Virt::StoragePool' ) ){
        return retErr("_ERR_CREATE_STORAGE_VOLUME_","Error create storage volume of unknown storage pool.");
    }

    my %volume = ();

    $volume{"name"} = $p{'name'};
    if( !$volume{"name"} ){
        return retErr("_ERR_CREATE_STORAGE_VOLUME_","Error creating storage volume: no name defined.");
    }

    $volume{'allocation'} = $p{'allocation'} if( defined $p{'allocation'} );
    $volume{'capacity'} = $p{'capacity'} if( defined $p{'capacity'} );

    if( my $device = $p{'source_device'} ){
        my @ld = ref($device) eq 'ARRAY' ? @$device : ($device);
        for my $d (@ld){
            my %D = ( ref($d) ? %$d : ('path'=>$d) );
            push(@{$volume{'source'}{'device'}}, \%D);
        }
    }

    if( $p{'target_path'} || $p{'path'} ){
        my $path = $volume{'target'}{'path'} = $p{'target_path'} || $p{'path'};
    } else {
        return retErr("_ERR_CREATE_STORAGE_VOLUME_","Error creating storage volume: no target path specified.");
    }

    if( $p{'target_format'} || $p{'format'} ){
        $volume{'target'}{'format'}{'type'} = $p{'target_format'} || $p{'format'};
    }

    if( $p{'permissions_owner'} ||
        $p{'permissions_group'} ||
        $p{'permissions_mode'} ||
        $p{'permissions_label'} ){
        $volume{'target'}{'permissions'}{'owner'} = $p{'permissions_owner'} || '-1';
        $volume{'target'}{'permissions'}{'group'} = $p{'permissions_group'} || '-1';
        $volume{'target'}{'permissions'}{'mode'} = $p{'permissions_mode'} || '0700';
        $volume{'target'}{'permissions'}{'label'} = $p{'permissions_label'} if( $p{'permissions_label'} );
    }

    $volume{'key'} = $p{'key'} || $volume{'target'}{'path'};

    if( $p{'backingStore_path'} ){
        my $path = $volume{'backingStore'}{'path'} = $p{'backingStore_path'};
    }

    if( $p{'backingStore_format'} || $volume{'backingStore'} ){
        $volume{'backingStore'}{'format'}{'type'} = $p{'backingStore_format'} || $volume{'target'}{'format'};
    }

    if( $p{'backingStore_permissions_owner'} ||
        $p{'backingStore_permissions_group'} ||
        $p{'backingStore_permissions_mode'} ||
        $p{'backingStore_permissions_label'} || $volume{'backingStore'} ){
        $volume{'backingStore'}{'permissions'}{'owner'} = $p{'backingStore_permissions_owner'} || $p{'permissions_owner'} || '-1';
        $volume{'backingStore'}{'permissions'}{'group'} = $p{'backingStore_permissions_group'} || $p{'permissions_group'} || '-1';
        $volume{'backingStore'}{'permissions'}{'mode'} = $p{'backingStore_permissions_mode'} || $p{'permissions_mode'} || '0700';
        $volume{'backingStore'}{'permissions'}{'label'} = $p{'backingStore_permissions_label'} || $p{'permissions_label'} if( $p{'backingStore_permissions_label'} || $p{'permissions_label'} );
    }

    plog "hash=",Dumper(\%p) if( &debug_level > 3 );

    my $xml = VirtAgent::Storage->gen_xml_storage_volume( %volume );

    plog "xml=",$xml if( &debug_level > 3 );

    my $SV;
    
    eval { $SV = $sp->create_volume( $xml ); };
    if( $@ ){
        return retErr("_ERR_CREATE_STORAGE_VOLUME_","Error creating storage volume: $@");
    }

    my %V = $self->storage_volume_to_hash( $SV );
    return retOk("_CREATE_STORAGE_VOLUME_OK_","Storage Volume created successful.","_RET_OBJ_",\%V);
}

=item delete_storage_volume

    Delete storage volume

    args:
        
        name - volume name. need pool_name
        
        pool_name - pool name
    
        pool_uuid - pool uuid

        path - volume path

        key - volume key

=cut

sub delete_storage_volume {
    my $self = shift;
    my ($sv) = my %p = @_;

    my $vm = $self->vmConnect();
    if( isError($vm) ){
        return wantarray() ? %$vm : $vm;
    }

    if( ref($sv) ne 'Sys::Virt::StorageVol' ){
        if( $p{'name'} ){
            my $sp = $p{'pool'};
            if( ref($sp) ne 'Sys::Virt::StoragePool' ){
                if( $p{'pool_uuid'} ){
                    eval { $sp = $vm->get_storage_pool_by_uuid($p{'pool_uuid'}); };
                } elsif( $p{'pool_name'} ){
                    eval { $sp = $vm->get_storage_pool_by_name($p{'pool_name'}); };
                }
            }
            if( !$sp || ( ref($sp) ne 'Sys::Virt::StoragePool' ) ){
                return retErr("_ERR_DEL_STORAGE_VOLUME_","Error delete volume of unknown storage pool.");
            }

            eval { $sv = $sp->get_volume_by_name( $p{'name'} ); };
        } elsif( $p{'path'} ){
            eval { $sv = $vm->get_storage_volume_by_path( $p{'path'} ); };
        } elsif( $p{'key'} ){
            eval { $sv = $vm->get_storage_volume_by_key( $p{'key'} ); };
        }
    }

    if( !$sv || ( ref($sv) ne 'Sys::Virt::StorageVol' ) ){
        return retErr("_ERR_DEL_STORAGE_VOLUME_","Error delete storage volume: volume unknown .");
    }

    my $flags;
    $flags = Sys::Virt::StorageVol::DELETE_NORMAL if( $p{'mode'} eq 'normal' );
    $flags = Sys::Virt::StorageVol::DELETE_ZEROED if( $p{'mode'} eq 'zeroed' );

    my %V = $self->storage_volume_to_hash( $sv );

    eval { $sv->delete( $flags ) };
    if( $@ ){
        return retErr("_ERR_DEL_STORAGE_VOLUME_","Error delete storage volume: $@");
    }

    return retOk("_DELETE_STORAGE_VOLUME_OK_","Storage Volume delete successful.","_RET_OBJ_",\%V);
}

=item change_ip

    change agent and interface configuration

    args:
        
        network - network name

        if - network interface

        ip - ip address

        dhcp - DHCP

        netmask - network mask

        gateway - gateway

        hostname - DNS hostname

        domainname - DNS domain name

        primarydns - first DNS server

        secondarydns - second DNS server

        tertiarydns - third DNS server

        searchlist - domain search list (e.g. domain1,domain2,domain3)

        cm_uri - Central Management URI

=cut

sub _change_ip {
    my $self = shift;
    my (%p) = @_;

    # get if from network
    if( $p{'network'} ){
        my $N = VNETSCache::getVNET($p{'network'});
        if( !$N ){
            return retErr("_ERR_CHANGE_IP_","Error network doenst exists!");
        }
        $p{'if'} = $N->{'bridge'};
        if( !$p{'if'} ){
            return retErr("_ERR_CHANGE_IP_","Error: no interface attached to network!");
        }
    }

    # check interface is valid
    if( $p{'if'} ){
        if( -e "/sys/class/net/$p{'if'}" ){
            if( $p{'dhcp'} ){   # dhcp
                $p{'bootproto'} = 'dhcp';
            } else {            # manual
                $p{'bootproto'} = 'static';
                # check ip
                if( !ETVA::NetworkTools::valid_ipaddr($p{'ip'}) ){
                    return retErr("_ERR_CHANGE_IP_","Error: invalid IP.");
                }

                # check netmask
                if( !ETVA::NetworkTools::valid_netmask($p{'netmask'}) ){
                    return retErr("_ERR_CHANGE_IP_","Error: invalid Netmask.");
                }

                # check gateway
                if( $p{'gateway'} && !ETVA::NetworkTools::valid_ipaddr($p{'gateway'}) ){
                    return retErr("_ERR_CHANGE_IP_","Error: invalid Gateway.");
                }
            }

            # change conf
            if( ETVA::NetworkTools::change_if_conf( %p ) ){
                # apply if conf
                if( !ETVA::NetworkTools::active_ip_conf( %p ) ){
                    return retErr("_ERR_CHANGE_IP_",'error change ip');
                }
                # try change dns values
                ETVA::NetworkTools::change_dns(%p);

                # change etva script files
                ETVA::NetworkTools::change_ip_etva_conf( %p );

                my %IFS = ETVA::Utils::get_allinterfaces();

                my $IF = $IFS{"$p{'if'}"};
                my $ipaddr = $IF->{'address'} || '127.0.0.1'; 
                
                # change ip address
                $CONF->{'IP'} = $CONF->{'LocalIP'} = $ipaddr;

                # change hosts
                ETVA::NetworkTools::fix_hostname_resolution($CONF->{'name'}, $CONF->{'IP'} || $CONF->{'LocalIP'} || '127.0.0.1');

            } else {
                return retErr("_ERR_CHANGE_IP_","change ip config fail!");
            }
        } else {
            return retErr("_ERR_CHANGE_IP_","interface '$p{'if'}' not found!");
        }
    }
    

    # change CM URI
    if( $p{'cm_uri'} ){
        $CONF->{'cm_uri'} = $p{'cm_uri'};
    }

    $self->setconfig( %$CONF );

    return retOk("_OK_CHANGE_IP_","IP change successfully.");
}

sub change_ip {
    my $self = shift;
    my (%p) = @_;

    my $R = {};
    if( !&umount_isosdir() ){    # try unmount isos dir configured to previous CM
        $R = retErr("_ERR_CHANGE_IP_","error umount isosdir");
    } else {
        $R = $self->_change_ip(%p);
    }

    &mount_isosdir($CONF->{'cm_uri'});     # mount isos dir

    if( !isError($R) ){
        plog "going down.... Dump=",Dumper($CONF),"\n";
        # restart process
        #kill SIGHUP, $$;
        sleep(2);
        $self->reinitialize();
    }

    return wantarray() ? %$R : $R;
}

# change_uuid: change uuid and reinitialize
sub change_uuid {
    my $self = shift;
    my (%p) = @_;

    $self->setuuid($p{'uuid'});

    $self->reinitialize();

    return retOk("_OK_","ok");
}

sub update_ntp_config {
    my $self = shift;
    my (%p) = @_;

    my $cmip = ETVA::Utils::get_cmip();
    my $myip = ETVA::Utils::get_ip();
    if( ($cmip ne '127.0.0.1') && ($cmip ne 'localhost') && ($cmip ne $myip) ){
        ETVA::Utils::configure_ntp( $cmip );
    }

    return retOk("_OK_UPDATE_NTP_CONFIG_","NTP configuration update successfully.");
}

sub shutdown {
    my $self = shift;
    my (%p) = @_;
    my ($e,$m) = cmd_exec("poweroff");

    if($e != 0){
        return retErr("_ERR_POWEROFF_",$m);
    }else{
        return retOk("_OK_","ok");
    }
}

sub get_pci_devices{
    my $self = shift;
    my %hostdev = ();
    my @devices = VirtAgent::Devices::pci_dev_list();

#    plog(Dumper(\@devices));
    return wantarray() ? @devices : \@devices; 
}

sub get_usb_devices{
    my $self = shift;
    my @devices = VirtAgent::Devices::usb_dev_list();
    return wantarray() ? @devices : \@devices; 
}

=item get_va_ipconf

    get virtual-agent ip configuration info

    args:
        
        network - network name

        if - network interface

=cut

sub get_va_ipconf {
    my $self = shift;
    my (%p) = @_;

    # get if from network
    if( $p{'network'} ){
        my $N = VNETSCache::getVNET($p{'network'});
        if( !$N ){
            return retErr("_ERR_VA_IPCONF_","Error network doenst exists!");
        }
        $p{'if'} = $N->{'bridge'};
        if( !$p{'if'} ){
            return retErr("_ERR_VA_IPCONF_","Error: no interface attached to network!");
        }
    }

    # check interface is valid
    if( $p{'if'} ){
        if( -e "/sys/class/net/$p{'if'}" ){
            return ETVA::NetworkTools::get_ip_conf($p{'if'});
        } else {
            return retErr("_ERR_GET_VA_IPCONF_","interface '$p{'if'}' not found!");
        }
    } elsif( $CONF->{'IP'} ){
        return ETVA::NetworkTools::get_ip_conf(undef,$CONF->{'IP'});
    } else {
        return retErr("_ERR_GET_VA_IPCONF_","cant get ip config info!");
    }
}

=item change_va_name

    change virtual-agent name

    args:
        
        name - va name

=cut

sub change_va_name {
    my $self = shift;
    my (%p) = @_;
    if( $p{'name'} ){

        $self->setname($p{'name'});
        ETVA::NetworkTools::change_hostname($p{'name'});

        # generate server certificates
        ETVA::Utils::gencerts( $CONF->{'Organization'}, $p{'name'}, 1 );

        return retOk("_OK_CHANGE_VA_NAME_","Change name ok!", "_RET_OBJ_", { 'name'=>$p{'name'} } );
    } else {
        return retErr("_ERR_CHANGE_VA_NAME_","Invalid name!");
    }
}

sub sleep_10s {
    sleep(10);

    return nowStr() . ": wake up! $$\n";
}

sub vm_ovf_import {
    my $self = shift;
    my (%p) = @_;

    %p = $self->clean_params( %p );

    my $noinitialize = $p{'noinitialize'};

    my $disks_path_dir;
    if( !$p{'disks_path_dir'} ){

        # try create tmp dir in isosdir
        $disks_path_dir = ETVA::Utils::rand_tmpdir("$CONF->{'isosdir'}/.vmovfimport-tmpdir") if( $CONF->{'isosdir'} );

        # if could not create in isosdir, try it tmpdir
        $disks_path_dir = ETVA::Utils::rand_tmpdir("${TMP_DIR}/.vmovfimport-tmpdir") if( ! -e "$disks_path_dir" );

        $p{'disks_path_dir'} = $disks_path_dir;     # update parameter
    }

    if( !ref($p{'Disks'}) ){
        my $lDisks = $p{'Disks'} = &prep_comma_sep_fields($p{'Disks'});
        my @lhash = grep { $_->{'diskid'} } @$lDisks;
        if( @lhash ){
            my %Disks = map { $_->{'diskid'} => $_ } @lhash;
            $p{'Disks'} = \%Disks;
        }
    }

    if( !ref($p{'Networks'}) ){
        $p{'Networks'} = &prep_comma_sep_fields($p{'Networks'});
    }

    # os params: os_type os_variant ...

    $p{'os_type'} = $self->os_type(%p);  # set os_type
    $p{'os_loader'} = $self->os_loader(%p);  # set os_loader

    # input mouse
    if( !$p{'no_mouse'} || defined($p{'mouse_bus'}) ){
        if( !$p{'mouse_bus'} ){
            $p{'mouse_bus'} = ( ($self->get_hypervisor_type() =~ m/xen/) && ($p{'vm_type'} eq 'pv')  ) ? "xen" : "ps2";
        }
    }

    if( ($self->get_hypervisor_type() ne 'xen') && ($p{'vm_type'} ne 'pv') ){
        # this doenst work for xen hypervisor type
        # input tablet
        if( !$p{'no_tablet'} || defined($p{'tablet_bus'}) ){
            $p{'tablet_bus'} ||= "usb";
        }
    }

    my %features = ();
    if( $p{'features'} ){
        %features = %{$p{'features'}};
    } else {
        my @keysfeatures = map { s/feature_(\w+)/$1/ } grep { /feature_/ } keys %p; 
        foreach my $kf (@keysfeatures){
            $features{"$kf"} = $p{"$kf"};
        }
    }
    if( %features ){
        $p{'features'} = { %features };
    }

    if( $p{'acpi'} ){
        $p{'features'}{'acpi'} = 1;
    }

    if( $p{'vm_os'} =~ m/windows/i ){
        # for MS Windows 
        #  add ACPI support
        $p{'features'}{'acpi'} = 1;

        #  add tablet USB bus
        if( !$p{'no_tablet'} ){
            $p{'tablet_bus'} = $p{'tablet_bus'} || "usb";
        }
    }

    my ($VM,$LVS) = VirtMachine->ovf_import( %p );

    # remove tmp dir
    if( $disks_path_dir ){
        rmdir "$disks_path_dir";
    }

    if( $VM ){
        if( isError($VM) ){
            return wantarray() ? %$VM : $VM;
        } else {
            my %H = $VM->tohash();

            plog "vm_ovf_import VM=",Dumper(\%H) if( &debug_level > 5 );
            if( !$noinitialize ){
                my %V = $self->defineDomain( $VM->todomain() );
                if( isError(%V) ){
                    return wantarray() ? %V : \%V;
                }

                my $xml = $self->get_xml_domain( 'uuid'=>$VM->get_uuid(), 'name'=>$VM->get_name() );
                if( isError($xml) ){
                    return wantarray() ? %$xml : $xml;
                }

                plog "vm_ovf_import xml=$xml";

                # TODO
                #   fixme - update some info
                #$VM = $VM->loadfromxml( $xml );
                $VM->set_state("notrunning");

                plog "VM set state notrunning";

                $VM->set_initialized(1);

                plog "VM set initialized 1";

                my $uuid = $VM->get_uuid();
                my $name = $VM->get_name();

                plog "VM get uuid and name";

                $self->setVM( 'uuid'=>$uuid, 'name'=>$name, 'VM'=>$VM );

                plog "VM update VMS and Name2Uuid";
            }

            # update
            %H = $VM->tohash();

            return retOk("_VM_OVF_IMPORT_OK_","Virtual machine successfully imported by OVF.","_RET_OBJ_", { 'VM'=>\%H, 'LVS'=>$LVS } );

        }
    }
    return retErr("_ERR_VM_OVF_IMPORT_","Error import ovf!");
}

sub vm_ovf_export {
    my $self = shift;
    my (%p) = @_;

    my $sock = $p{'_socket'};

    %p = $self->clean_params( %p );

    my $VM = $self->getVM(%p);
    if( !$VM ){
        return retErr("_ERR_VM_NOT_FOUND_","Error virtual machine not found.");
    }

    my $name = $VM->get_name();

    #$p{'export_path_dir'} = $TMP_DIR if( !$p{'export_path_dir'} );
    $p{'export_ovf_file'} = $name . ".ovf" if( !$p{'export_ovf_file'} );

    my %F = $VM->ovf_export(%p);

    # set blocking for wait to transmission end
    $sock->blocking(1);

    my $fn_ova = "$F{'ovf_file'}" || $name . ".ova";
    $fn_ova =~ s/\.ovf/.ova/;
    my $fn_ovf = $F{'ovf_file'};
#    print $sock 'Content-disposition: attachment; filename="',$fn_ova,'"',"\n";
#    print $sock 'Content-Type: application/x-tar',"\n\n";

    plogNow( "vm_ovf_export fn_ova=$fn_ova fn_ovf=$fn_ovf" );

    my ($bkp_randtmpdir, $bkp_randtmpdir_ori);

    if( $p{'location'} || $p{'snapshot'} || $p{'do_not_generate_tar'} ){
        # create tmp dir
        $bkp_randtmpdir = $bkp_randtmpdir_ori = ETVA::Utils::rand_tmpdir("${TMP_DIR}/.virtd-export-backup-${name}");
    }

    my $tar = new ETVA::ArchiveTar( 'handle'=>$sock );

    my $E;  # error object

    if( $p{'location'} ){
        $E = my $bkp_randtmpdir_aux = &_pre_copy_backup_to_location( %p, 'backup_dir'=>$bkp_randtmpdir );
        if( !isError($E) ){
            $bkp_randtmpdir = $bkp_randtmpdir_aux;
            my $tar_randtmpfile = ETVA::Utils::rand_tmpfile("${bkp_randtmpdir}/ovf-export-${name}") . ".tar";
            $tar = new ETVA::ArchiveTar( 'file'=>$tar_randtmpfile );
        }
    }

    if( !isError($E) ){

        # clean old backups
        if( $p{'clean_old_backups'} && $p{'location'} ){
            # do the clean up
            &_clean_old_backups('vm_name'=>$name, 'n_days'=>$p{'n_days'}, 'backup_dir'=>$bkp_randtmpdir);
        }

        # generate backup fullpath based on date now string
        my $dtnow_str = &nowStr(0,'%Y%m%d');
        my $backup_fullpath = ETVA::Utils::rand_tmpdir("${bkp_randtmpdir}/vm-backup-${name}/${dtnow_str}");

        if( $backup_fullpath ){
            $tar->add_file( 'name'=>"$fn_ovf", 'path'=>'', 'data'=>$F{'xml'}, type=>ETVA::ArchiveTar::FILE, 'mode'=>33204, 'mtime'=>now()  );
            for my $D (@{$F{'Disks'}}){ 
                next if( $D->get_device() eq 'cdrom' ); # ignore CDROM

                my $oripath = $D->get_path();
                if( -l $oripath ){
                    $oripath = abs_path($oripath);
                }
                my $fname = $D->get_filename();

                my $bkp_path = $oripath;
                if( my $snapshot = $p{'snapshot'} ){    # if has snapshot do export from snapshot
                    $bkp_path = "${backup_fullpath}/${fname}";
                    plogNow( "[INFO] vm_ovf_export copy oripath=$oripath to bkp_path=$bkp_path ... " );
                    $E = $self->backupsnapshot('olv'=>$oripath, 'slv'=>$snapshot, 'backup'=>$bkp_path, 'use_qemu'=>$p{'use_qemu'} );
                    if( isError($E) ){
                        plogNow( "[ERROR] vm_ovf_export copy oripath=$oripath to bkp_path=$bkp_path ... ERROR!" );
                        last;
                        #return wantarray() ? %$E: $E;
                    }
                    plogNow( "[ERROR] vm_ovf_export copy oripath=$oripath to bkp_path=$bkp_path ... done" );
                } elsif( $p{'location'} ){
                    $bkp_path = "${backup_fullpath}/${fname}";
                    plogNow( "[INFO] vm_ovf_export backupdisk copy oripath=$oripath to bkp_path=$bkp_path ... " );
                    $E = $self->backupdisk('path'=>$oripath, 'backup'=>$bkp_path );
                    if( isError($E) ){
                        plogNow( "[ERROR] vm_ovf_export backupdisk copy oripath=$oripath to bkp_path=$bkp_path ... ERROR!" );
                        last;
                        #return wantarray() ? %$E: $E;
                    }
                    plogNow( "[INFO] vm_ovf_export backupdisk copy oripath=$oripath to bkp_path=$bkp_path ... done" );
                }
                plogNow("[INFO] file name=$fname oripath=$D->{'path'}($oripath) bkp_path=$bkp_path");
                $tar->add_file( 'name'=>"$fname", 'path'=>"$bkp_path", type=>ETVA::ArchiveTar::FILE, 'mode'=>33204, 'mtime'=>now() );
            }
        } else {
            $E = retErr("_ERR_VM_OVF_EXPORT_","Error create backup directory.");
        }
    
        if( !isError($E) ){
            if( !$p{'do_not_generate_tar'} ){
                $tar->write();
            } else {
                # write .ovf to tmpdir
                open(FOVF,">${backup_fullpath}/$fn_ovf");
                print FOVF $F{'xml'};
                close(FOVF);
            }
        }

        if( $p{'location'} ){
            # TODO move to location $bkp_randtmpdir
            &_pos_copy_backup_to_location( %p, 'backup_dir'=>$bkp_randtmpdir );
        }
    }

    if( $p{'location'} ){
        rmdir $bkp_randtmpdir_ori if( -d "$bkp_randtmpdir_ori" );   # if exists remove it
    } else {
        rmtree $bkp_randtmpdir if( -d "$bkp_randtmpdir" ); # remove dir recursively
    }

    # no return... must write to socket...
    if( isError($E) ){
        return wantarray() ? %$E: $E;
    }
    return retOk("_VM_OVF_EXPORT_","VM '$name' export ovf with success.");
}

# prepare location to copy backup
sub _pre_copy_backup_to_location {
    my ($location,$backupdir) = my %p = @_;
    if( $p{'backup_dir'} || $p{'location'} ){
        $location = $p{'location'};
        $backupdir = $p{'backup_dir'};
    }

    plogNow("_pre_copy_backup_to_location location=$location backupdir=$backupdir");

    if( $location =~ m/ftp:\/\// ){
        # TODO for FTP and/or HTTP
        return retErr("_ERR_COPY_BACKUP_TO_LOCATION_","Error copy backup to location: invalid location");
    } elsif( -d "$location" ){
        return $location;
    } elsif( ($location =~ m/smb:\/\//) ||
                ($location =~ m/nfs:\/\//) ||
                (-b "$location") ||
                (-e "$location" ) ){
        $backupdir ||= ETVA::Utils::rand_tmpdir("${TMP_DIR}/.virtd-copy_backup_to_location-tmdir");

        if( $location =~ m/smb:\/\// ){
            my @opts = ();
            my $ofs = ($location =~ m/smb:\/\//)? 4: 0;
            if( $location =~ s/smb:\/\/((\S+);)?(\S+):(\S+)\@/smb:\/\// ){
                my ($domain,$username,$password) = ($2,$3,$4);

                # CMAR 20/06/2016
                #   from man mount.cifs:
                #       "The default in mainline kernel versions prior to v3.8 was sec=ntlm. In v3.8, the default was changed to sec=ntlmssp."
                #
                #   so add option sec=ntlm to keep ntlm as default
                #
                my $options = "username=$username,password=$password,sec=ntlm";
                $options .= ",domain=$domain" if( $domain );    # add domain
                $options =~ s/'/'"'"'/gs;     # espace ' char
                push(@opts,"-o","'$options'");
            }
            my $nl = substr($location,$ofs);
            my ($e,$m) = cmd_exec("mount",$nl,$backupdir,@opts);
            unless( $e == 0 ){
                return retErr("_ERR_COPY_BACKUP_TO_LOCATION_","Error copy backup to location: $m");
            }
        } elsif( $location =~ m/nfs:\/\// ){
            my $ofs = ($location =~ m/nfs:\/\//)? 6: 4;
            my $nl = substr($location,$ofs);
            my ($e,$m) = cmd_exec("mount",$nl,$backupdir);
            unless( $e == 0 ){
                return retErr("_ERR_COPY_BACKUP_TO_LOCATION_","Error copy backup to location: $m");
            }
        } elsif( -b "$location" ){
            my ($e,$m) = cmd_exec("mount",$location,$backupdir);
            unless( $e == 0 ){
                return retErr("_ERR_COPY_BACKUP_TO_LOCATION_","Error copy backup to location: $m");
            }
        } elsif( -e "$location" ){
            my ($e,$m) = cmd_exec("mount","-o","loop",$location,$backupdir);
            unless( $e == 0 ){
                return retErr("_ERR_COPY_BACKUP_TO_LOCATION_","Error copy backup to location: $m");
            }
        }
    } else {
        return retErr("_ERR_COPY_BACKUP_TO_LOCATION_","Error copy backup to location: invalid location");
    }
    return $backupdir;
}
# post call for copy backup to location
sub _pos_copy_backup_to_location {
    my ($backupdir, $location) = my %p = @_;
    if( $p{'backup_dir'} || $p{'location'} ){
        $location = $p{'location'};
        $backupdir = $p{'backup_dir'};
    }

    if( $location =~ m/ftp:\/\// ){
        # TODO for FTP and/or HTTP
    } elsif( -d "$location" ){
    } elsif( ($location =~ m/smb:\/\//) ||
                ($location =~ m/nfs:\/\//) ||
                (-b "$location") ||
                (-e "$location" ) ){
        plogNow("_pos_copy_backup_to_location umount $backupdir") if( 1 || &debug_level > 3 );
        cmd_exec("umount",$backupdir);
        rmdir($backupdir);
    }
}

sub set_imchild { $PARENT = 0; }
sub set_imparent { $PARENT = 1; }
sub get_imchild { return !$PARENT; }
sub get_imparent { return $PARENT; }

sub exit_handler {
    plog "clean up all shared memory" if( &debug_level > 5 );
    SharedCache::scclean_up();
}

# clean_params - clean some params (eg socket)
sub clean_params {
    my $self = shift;
    my (%p) = @_;

    # drop socket
    delete $p{'_socket'} if( $p{'_socket'} );
    delete $p{'_make_response'} if( $p{'_make_response'} );
    delete $p{'_make_response_fault'} if( $p{'_make_response_fault'} );

    return %p;
}

# get_backupconf - get backup of configuration file
sub get_backupconf {
    my $self = shift;
    my (%p) = @_;

    my $sock = $p{'_socket'};

    # set blocking for wait to transmission end
    $sock->blocking(1);

    if( $p{'_make_response'} ){
        print $sock $p{'_make_response'}->("",'-type'=>'application/x-tar');
    }

    my $c_path = $CONF->{'CFG_FILE'};
    my $tar = new ETVA::ArchiveTar( 'handle'=>$sock );
    $tar->add_file( 'name'=>"$c_path", 'path'=>"$c_path" );


    my $agent_logs = $ENV{'agent_log_dir'} || "/var/log/etva-vdaemon/*.log";


    if($p{'diagnostic'}){
        
        # add agent logs
        while(<$agent_logs>){
            $tar->add_file( 'name'=>"$_", 'path'=>"$_" );
        }
        
        # adds agent info
        my $hostname = `hostname`;
        chomp $hostname;
        my $infofile = "/tmp/$hostname";
        $infofile .= '_info.txt';         

        $self->get_agentinfo($infofile);
        $tar->add_file( 'name'=>$infofile, 'path'=>$infofile );

        my $sos = $self->get_sosreport();
        if($sos){
            my @sosfiles = glob $sos;
            print "[INFO] @sosfiles";
            for my $f (@sosfiles){
                print "[INFO] Adding file $f\n";
                $tar->add_file( 'name'=>$f, 'path'=>$f);
            }
        }
        
        my @xml = $self->vms_xml();
        foreach my $x (@xml){
            my %xmlh = %$x;
            $tar->add_file( 'name'=>$xmlh{'name'}, 'path'=>"", 'data'=>$xmlh{'xml'}, 'type'=>ETVA::ArchiveTar::FILE, 'mode'=>33204, 'mtime'=>now() );
        }
    }

    $tar->write();

    return;
}

my $CMD_SOSREPORT;
sub cmd_sosreport {
    if( ! defined($CMD_SOSREPORT) ){
        $CMD_SOSREPORT = "/usr/sbin/sosreport --batch --name=eurotux";
        if( `/usr/sbin/sosreport --help | grep no-progressbar` ){
            $CMD_SOSREPORT = "/usr/sbin/sosreport --batch --name=eurotux --no-progressbar";
        }
    }
    return $CMD_SOSREPORT;
}

sub get_sosreport {
    my $sosfiles = '/tmp/sosreport*';

    my $cmd = &cmd_sosreport;
    my $res = `$cmd`;
    if($?){
        print STDERR "[ERROR] sosreport problem $res \n";
        return 0;       
    }
    return $sosfiles;
}

sub get_agentinfo {
    my $self = shift;
    my $filename = shift;

    open FILE, ">$filename";
    my $oldhandle = select FILE;
    print " ============ UPTIME =========== \n";
    print `uptime`;
    print "\n ========== DISK SPACE ========= \n";
    print `df -h`;
    print "\n ====== CENTRAL MANAGEMENT ====== \n";
    my $cm_uri = $CONF->{'cm_uri'};
    print "CM_URI:  $cm_uri\n";
    if($cm_uri =~ /\/\/([^(\/|:)]*)/){
        my $ip = $1;
        my $status = system "ping -c 1 -w 5 $ip &>/dev/null";
        if($status == 0){
            print "PING: OK\n";
            
            # test url
            my $urlheader =  LWP::Simple::head($cm_uri);
            print "CM_URI: ";
            if($urlheader){
                print "OK\n";
            }else{
                print "NOK\n";
            }
        }else{
            print "PING: NOK\n";
        }
    }
    #system "nc -zv -w2 10.10.4.36 ";

    select $oldhandle;
    close FILE;
}

# set_backupconf - overwrite configuration file
sub set_backupconf {
    my $self = shift;
    my (%p) = @_;

    my $tar = ETVA::ArchiveTar->new();

    # set word dir to /
    $tar->setcwd( "/" );

    my $tmpbf;
    if( $p{'_url'} ){
        $tmpbf = ETVA::Utils::rand_tmpfile("${TMP_DIR}/.virtd-setbkpconf-tmpfile");
        my $rc = LWP::Simple::getstore("$p{'_url'}","$tmpbf");
        if( is_error($rc) || !-e "$tmpbf" ){
            return retErr('_ERR_SET_BACKUPCONF_',"Error get backup file ($tmpbf status=$rc) ");
        }
        $tar->read($tmpbf);
    } else {
        my $sock = $p{'_socket'};

        # set blocking for wait to transmission end
        $sock->blocking(1);
        $tar->read($sock);
    }

    plog "set_backupconf files=",$tar->list_files();
    $tar->extract();

    if( $tmpbf ){
        # remove tmp file
        unlink "$tmpbf";
    }

    return;
}

=com # this manual way to create snapshots
    # This should be deprecated with new libvirt versions
=cut
sub vm_create_snapshot_manualway {
    my $self = shift;
    my %p = @_;

    my $VM = $self->getVM(%p);
    if( !$VM ){
        return retErr("_ERR_VM_NOT_FOUND_","Error virtual machine not found.");
    }

    my $name = $VM->get_name();
    my $tag = now();

    my $snapshots_dir = $CONF->{'SNAPSHOTS_DIR'} || "$TMP_DIR/snapshots";
    $snapshots_dir .= "/$name";
    if( !-d "$snapshots_dir" ){
        mkpath( "$snapshots_dir" );
    }

    if( !$VM->isrunning() && !$self->vmIsRunning(%p) ){ # vm is not running
        # save vm config
        # take snapshot disks
        return retErr("_ERR_VM_IS_NOT_RUNNING_","Error virtual machine is not running.");
    } else { # is running

        # save vm state to file
        my $snapshot_file = "${snapshots_dir}/${name}-snapshot-${tag}";
        $self->saveDomain(%p, 'file'=>$snapshot_file );

        # save vm config??
        my $snapshot_xmlfile = "${snapshots_dir}/${name}-snapshot-${tag}.xml";
        my $xml = $self->get_xml_domain(%p);
        if( isError($xml) ){
            return wantarray() ? %$xml : $xml;
        }
        open(F,">$snapshot_xmlfile");
        print F $xml;
        close(F);

        # take snapshot disks
        my $disks = $VM->get_Disks();
        if( $disks ){
            for my $D (@$disks){
                $self->createsnapshot( %p, 'olv'=>$D->get_path(), 'name'=>$tag, 'extents'=>'20%FREE' );
                # TODO testing errors
            }
        }
    }
    my %H = ( 'tag'=>$tag );
    return retOk("_VM_CREATE_SNAPSHOT_OK_","Virtual machine snapshot created successfully","_RET_OBJ_",\%H);
}

sub vm_revert_snapshot_manualway {
    my $self = shift;
    my %p = @_;

    my $VM = $self->getVM(%p);
    if( !$VM ){
        return retErr("_ERR_VM_NOT_FOUND_","Error virtual machine not found.");
    }

    my $name = $VM->get_name();
    my $tag = $p{'tag'};

    my $snapshots_dir = $CONF->{'SNAPSHOTS_DIR'} || "$TMP_DIR/snapshots";
    $snapshots_dir .= "/$name";
    if( !-d "$snapshots_dir" ){
        return retErr("_ERR_VM_REVERT_SNAPSHOT_","Error virtual machine dont have snapshots.");
    }

    # get vm state to file
    my $snapshot_file = "${snapshots_dir}/${name}-snapshot-${tag}";

    if( !-e "$snapshot_file" ){
        return retErr("_ERR_VM_REVERT_SNAPSHOT_","Error virtual machine snapshot does not exists.");
    }

    # revert vm config??
    my $snapshot_xmlfile = "${snapshots_dir}/${name}-snapshot-${tag}.xml";
    #my $xml = $self->get_xml_domain(%p);
    #if( isError($xml) ){
    #    return wantarray() ? %$xml : $xml;
    #}
    #open(F,">$snapshot_xmlfile");
    #print F $xml;
    #close(F);

    # revert snapshot disks
    my $disks = $VM->get_Disks();
    if( $disks ){
        for my $D (@$disks){
            $self->revertsnapshot( %p, 'olv'=>$D->get_path(), 'name'=>$tag );
            # TODO testing errors
        }
    }

    # restore Domain
    $self->restoreDomain(%p, 'file'=>$snapshot_file );

    my %H = ( 'tag'=>$tag );
    return retOk("_VM_REVERT_SNAPSHOT_OK_","Virtual machine snapshot reverted successfully","_RET_OBJ_",\%H);
}

sub vm_list_snapshots_manualway {
    my $self = shift;
    my %p = @_;

    my $VM = $self->getVM(%p);
    if( !$VM ){
        return retErr("_ERR_VM_NOT_FOUND_","Error virtual machine not found.");
    }

    my $name = $VM->get_name();

    my @list = ();

    my $snapshots_dir = $CONF->{'SNAPSHOTS_DIR'} || "$TMP_DIR/snapshots";
    $snapshots_dir .= "/$name";
    if( -d "$snapshots_dir" ){
        opendir(D,"$snapshots_dir");
        my @lfiles = readdir(D);
        my $c = 1;
        for my $f (@lfiles){
            if( $f =~ m/^${name}-snapshot-(\d+)$/ ){
                my $tag = $1;
                my $fpath = "$snapshots_dir/$f";
                my $date = ctime(stat($fpath)->ctime);
                my ($size) = (-s "$fpath" );
                push(@list, { 'id'=>$c, 'tag'=>$tag, 'date'=>$date, size=>$size } );
                $c++;
            }
        }
        closedir(D);
    }
    return wantarray() ? @list : \@list;
}
# END snapshots manual way

sub vm_create_snapshot {
    my $self = shift;
    my %p = @_;

    my $VM = $self->getVM(%p);
    if( !$VM ){
        return retErr("_ERR_VM_NOT_FOUND_","Error virtual machine not found.");
    }

    my $E = $self->create_snapshot(%p);
    if( isError($E) ){
        return wantarray() ? %$E: $E;
    }
    return retOk("_VM_CREATE_SNAPSHOT_OK_","Virtual machine snapshot created successfully.","_RET_OBJ_",$E->{'_RET_OBJ_'});
}

sub vm_revert_snapshot {
    my $self = shift;
    my %p = @_;

    my $VM = $self->getVM(%p);
    if( !$VM ){
        return retErr("_ERR_VM_NOT_FOUND_","Error virtual machine not found.");
    }

    # revert snapshot
    my $E = $self->revert_snapshot(%p);
    if( isError($E) ){
        return wantarray() ? %$E: $E;
    }
    return retOk("_VM_REVERT_SNAPSHOT_OK_","Virtual machine snapshot reverted successfully.","_RET_OBJ_",$E->{'_RET_OBJ_'});
}
sub vm_remove_snapshot {
    my $self = shift;
    my %p = @_;

    my $VM = $self->getVM(%p);
    if( !$VM ){
        return retErr("_ERR_VM_NOT_FOUND_","Error virtual machine not found.");
    }

    # remove snapshot
    my $E = $self->remove_snapshot(%p);
    if( isError($E) ){
        return wantarray() ? %$E: $E;
    }
    return retOk("_VM_REMOVE_SNAPSHOT_OK_","Virtual machine snapshot removed successfully.","_RET_OBJ_",$E->{'_RET_OBJ_'});
}

sub vm_list_snapshots {
    my $self = shift;
    my %p = @_;

    my $VM = $self->getVM(%p);
    if( !$VM ){
        return retErr("_ERR_VM_NOT_FOUND_","Error virtual machine not found.");
    }
    return $self->list_snapshots(%p);
}

=item vm_backup

    backup VM

    args:
        
        name - va name

        location - location where backup will be saved

        snapshot - if backup is based on snapshot

        shutdown - shutdown the VM before do the backup and start up again after

=cut

# vm_backup_snapshot alias of vm_backup
*vm_backup_snapshot = \&vm_backup;
# vm_backup_offsite( alias of vm_backup
*vm_backup_offsite = \&vm_backup;

sub vm_backup {
    my $self = shift;
    my %p = @_;

    my $VM = $self->getVM(%p);
    if( !$VM ){
        return retErr("_ERR_VM_NOT_FOUND_","Error virtual machine not found.");
    }

    my $name = $VM->get_name();

    if( !$p{'force'} && !defined($p{'snapshot'}) && !$p{'shutdown'} ){
        return retErr("_ERR_VM_BACKUP_","Couldn't backup VM with server running and without snapshots.");
    }

    if( defined($p{'snapshot'}) ){
        my $Snapshot = $self->get_snapshot(%p);
        if( isError($Snapshot) ){
            return wantarray() ? %$Snapshot: $Snapshot;
        }
        $p{'snapshot'} = $Snapshot->{'name'};
    }

    if( $p{'location'} ){   # do backup for location
        plogNow("[INFO] vm_backup: Backup of server '$name' to location '$p{'location'}' and do_not_generate_tar=$p{'do_not_generate_tar'}.");
    }

    my $running;
    if( $p{'shutdown'} ){   # shutdown VM after doing the backup
        $running = $self->vmIsRunning('uuid'=>$VM->get_uuid() ) ? 1 : 0;
        if( $running ){
            plogNow("[INFO] vm_backup: server '$name' is running...");
            my $E = $self->stopDomain( 'uuid'=>$VM->get_uuid() );
            plogNow("[INFO] vm_backup: server '$name' shutting down...");

            if( &waitCond( sub { return  $self->vmIsRunning('uuid'=>$VM->get_uuid()); }, 1*60) ){

                plogNow("[WARN] vm_backup: server '$name' is stil running and will be shutting down...");
                $E = $self->stopDomain( 'uuid'=>$VM->get_uuid(), 'force'=>1 );

                if( &waitCond( sub { return  $self->vmIsRunning('uuid'=>$VM->get_uuid()); }, 2*60) ){
                    #plogNow("vm_backup: server '$name' is stil running and will be force shutting down...");
                    #$E = $self->stopDomain( 'uuid'=>$VM->get_uuid(), 'force'=>1, 'destroy'=>1 );
                    plogNow("[ERROR] vm_backup: server '$name' is still running and couldn't make backup...");
                    return retErr('_ERR_VM_BACKUP_STILL_RUNNING_',"The server '$name' is still running and couldn't make backup.");
                }
            }
        }
    }

    # do the VM OVF export
    my $E = $self->vm_ovf_export(%p);
    
    if( $p{'shutdown'} && $running ){   # start VM after backup
        my $SE = $self->startDomain( 'uuid'=>$VM->get_uuid() );
        if( isError($SE) ){
            return wantarray() ? %$SE: $SE;
        }
    }

    if( isError($E) ){
        return wantarray() ? %$E: $E;
    }
    return retOk("_OK_VM_BACKUP_","Backup of VM '$name' done with success.");
}

sub _clean_old_backups {
    my ($vm_name,$backupdir,$n_days) = my %p = @_;
    if( $p{'backup_dir'} || $p{'vm_name'} || $p{'n_days'} ){
        $backupdir = $p{'backup_dir'};
        $vm_name = $p{'vm_name'};
        $n_days = $p{'n_days'};
    }

    # get VM backup fullpath
    my $backup_fullpath = "${backupdir}/vm-backup-${vm_name}/";

    # number of days of retention
    $n_days ||= $CONF->{'VM_BACKUPS_N_DAYS'} || 2;
    my $p_n_days = "+${n_days}";

    plogNow("[INFO] Clean backups of server '$vm_name' with more then '$n_days' days on '$backupdir'.");

    # remove old backups
    my ($ef,$mf) = &cmd_exec("find ${backup_fullpath} -maxdepth 2 -mindepth 1 -type f -mtime ${p_n_days} -print0 -delete");
    plogNow("[INFO] _clean_old_backups: remove the following files: $mf");

    # clean empty directories
    my ($ed,$md) &cmd_exec("find ${backup_fullpath} -maxdepth 2 -mindepth 1 -type d -empty -print0 -delete");
    plogNow("[INFO] _clean_old_backups: remove the following empty directories: $md");
}

sub vm_clean_old_backups {
    my $self = shift;
    my %p = @_;

    my $VM = $self->getVM(%p);
    if( !$VM ){
        return retErr("_ERR_VM_NOT_FOUND_","Error virtual machine not found.");
    }

    # get VM name
    my $name = $VM->get_name();

    # create tmp dir
    my $bkp_randtmpdir = ETVA::Utils::rand_tmpdir("${TMP_DIR}/.virtd-export-backup-${name}");

    if( !$p{'location'} ){
        return retErr('_ERR_VM_CLEAN_OLD_BACKUPS_',"No location specified.");
    }

    plogNow("[INFO] vm_clean_old_backups: Clean old backups of server '$name' on location '$p{'location'}'.");

    my $E = &_pre_copy_backup_to_location( 'location'=>$p{'location'}, 'backup_dir'=>$bkp_randtmpdir );

    if( !isError($E) ){

        # do the clean up
        &_clean_old_backups('vm_name'=>$name, 'n_days'=>$p{'n_days'}, 'backup_dir'=>$bkp_randtmpdir);

        # umount location
        &_pos_copy_backup_to_location( 'location'=>$p{'location'}, 'backup_dir'=>$bkp_randtmpdir );
    }

    rmdir $bkp_randtmpdir if( -d "$bkp_randtmpdir" );   # if exists remove it

    if( isError($E) ){
        return wantarray() ? %$E: $E;
    }
    return retOk("_OK_VM_CLEAN_OLD_BACKUPS_","Old backup cleanned with success.");
}

###### GUEST AGENT METHODS ######
sub refreshGAInfo{
    my $self = shift;
    my (%p) = @_;
        
    plogNow("[info] - VirtAgentInterface - refreshGAInfo called");
    
    # get plone info
    $self->plone_info(%p);

    my $client;
    eval {
        $client = GuestAgent::Client->new(
            'addr'  => 'localhost',
            'port'  => '7778',
            'proto' => 'tcp'
        );
    };
    if( $@ ){
        return retErr("_ERR_REFRESH_GA_CLIENT_CREATE_","Couldn't create guest agent client connection: $@");
    }
    
    my $res;
    eval {
        $res = $client->connect(
            'vmname'    => $p{'vmname'},
        );
    };
    if( $@ ){
        return retErr("_ERR_REFRESH_GA_CONNECT_","Couldn't connect to guest agent: $@");
    }

    my %obj = ();
    eval {
        %obj = %{ decode_json $res };
        
        if($obj{'success'} eq GuestAgent::MessageFactory::OK){
            $res = $client->refresh('vmname' => $p{'vmname'});
            $res = $client->getState('vmname' => $p{'vmname'});
            #print Dumper \%obj;
            %obj = %{ decode_json $res };
        }
    };
    if( $@ ){
        return retErr("_ERR_REFRESH_GA_REFRESH_","Couldn't refresh state of guest agent: $@");
    }

    $client->disconnect();
    return wantarray() ? %obj: \%obj;
}

sub plone_info{
    my $self = shift;
    my (%p) = @_;
        
    plog("plone_info called") if( &debug_level > 3 );

    my $client;
    eval {
        $client = GuestAgent::Client->new(
            'addr'  => 'localhost',
            'port'  => '7778',
            'proto' => 'tcp'
        );
    };
    if( $@ ){
        return retErr("_ERR_PLONE_INFO_GA_CLIENT_CREATE_","Couldn't create guest agent client connection: $@");
    }
    
    my $res;
    eval {
        $res = $client->connect(
            'vmname'    => $p{'vmname'},
        );
    };
    if( $@ ){
        return retErr("_ERR_PLONE_INFO_GA_CONNECT_","Couldn't connect to guest agent: $@");
    }

    my %obj = ();
    eval {
        %obj = %{ decode_json $res };
        
        if($obj{'success'} eq GuestAgent::MessageFactory::OK){
            $client->genericMsg(
                'vmname'    => $p{'vmname'},
                'action'    => GuestAgent::MessageFactory::ETASPCOMMAND,
                'method'    => 'getInstanceMetadata'
            );
            
            $client->genericMsg(
                'vmname'    => $p{'vmname'},
                'action'    => GuestAgent::MessageFactory::ETASPCOMMAND,
                'method'    => 'getDatabaseInfo'
            );
            
            $client->genericMsg(
                'vmname'    => $p{'vmname'},
                'action'    => GuestAgent::MessageFactory::ETASPCOMMAND,
                'method'    => 'getResourceUsage'
            );

            $res = $client->refresh('vmname' => $p{'vmname'});
            $res = $client->getState('vmname' => $p{'vmname'});
            %obj = %{ decode_json $res };
        }
    };
    if( $@ ){
        return retErr("_ERR_PLONE_INFO_GA_REFRESH_","Couldn't refresh state of guest agent: $@");
    }

    $client->disconnect();
    return wantarray() ? %obj: \%obj;
}

sub plone_pack{
    my $self = shift;
    my (%p) = @_;
        
    plog("plone_pack called") if( &debug_level > 3 );

    my $client;
    eval {
        $client = GuestAgent::Client->new(
            'addr'  => 'localhost',
            'port'  => '7778',
            'proto' => 'tcp'
        );
    };
    if( $@ ){
        return retErr("_ERR_PLONE_PACK_GA_CLIENT_CREATE_","Couldn't create guest agent client connection: $@");
    }
    
    my $res;
    eval {
        $res = $client->connect(
            'vmname'    => $p{'vmname'},
        );
    };
    if( $@ ){
        return retErr("_ERR_PLONE_PACK_GA_CONNECT_","Couldn't connect to guest agent: $@");
    }

    my %obj = ();
    eval {
        %obj = %{ decode_json $res };
        
        if($obj{'success'} eq GuestAgent::MessageFactory::OK){
#            $client->genericMsg(
            $res = $client->messageWithResponse(
                'vmname'    => $p{'vmname'},
                'action'    => GuestAgent::MessageFactory::ETASPCOMMAND,
                'method'    => 'pack'
            );

#            $res = $client->refresh('vmname' => $p{'vmname'});
#            $res = $client->getState('vmname' => $p{'vmname'});
            %obj = %{ decode_json $res };
        }
    };
    if( $@ ){
        return retErr("_ERR_PLONE_PACK_GA_REFRESH_","Couldn't refresh state of guest agent: $@");
    }

    $client->disconnect();
    return wantarray() ? %obj: \%obj;
}

sub refreshAllGAInfo{
    my $self = shift;
    my %p = @_;
    my @guestlist = ();
    @guestlist = @{ $p{'vmnames'} } if( $p{'vmnames'} );

    my %rsp;

    my @errors = ();

    foreach my $guest (@guestlist){
        my $obj = $self->refreshGAInfo('vmname' => $guest);
        if( isError($obj) ){
            push(@errors, $obj);
            next;
        }
        $rsp{$guest} = $obj;
    }

    if( !%rsp && @errors ){
        return retErr("_ERR_REFRESH_ALL_GA_INFO_","Something wrong with GA info, we only get error messages...");
    }
    return wantarray() ? %rsp: \%rsp;
}

=item systemCheck

 run system check process

=cut

sub systemCheck {
    my $self = shift;
    # TODO improve this
    return retOk("_OK_SYSTEMCHECK_","System check ok.");
}

# testing if fork func
sub isForkable {
    my $self = shift;
    my ($method) = @_;

    my $v = 0;

    # TODO maybe use other tests...
    $v = 1 if( $method =~ m/_may_fork$/ );

    $v = 1 if( $method =~ m/^vm_ovf_export/ );
    $v = 1 if( $method =~ m/^vm_backup_snapshot/ );
    $v = 1 if( $method =~ m/^vm_backup_offsite/ );
    $v = 1 if( $method =~ m/^vm_backup/ );

    $v = 1 if( $method eq 'forward_to_mngtagent' );

    plogNow("VirtAgentInterace isForkable method=$method flag=$v") if( 1 || &debug_level > 3 );

    return $v;
}

# treat Management Agent calls

sub forward_to_mngtagent {
    my $self = shift;
    my (%p) = @_;

    plogNow("forward_to_mngtagent receive call server_name=$p{'server_name'} method=$p{'method'}");
        
    my $client;
    eval {
        # TODO improve this configuration
        $client = new ETVA::Client::SOAP( address => '127.0.0.1',
                                            port   => $CONF->{'LocalPort'}+2009,
                                            proto=>'tcp',
                                            blocking=>0 );
    };
    if( $@ ){
        return retErr("_ERR_FORWARD_TO_MNGTAGENT_","Couldn't create guest agent client connection: $@");
    }
    
    my $t0 = Time::HiRes::gettimeofday();

    my $res;
    eval {
        $res = $client->call($CONF->{'cm_namespace'}, $p{'method'}, %p );
    };
    if( $@ ){
        return retErr("_ERR_FORWARD_TO_MNGTAGENT_","Couldn't connect to guest agent: $@");
    }

    my $t1 = Time::HiRes::gettimeofday();
    my $secs = $t1 - $t0;

    plogNow(__PACKAGE__," FORWARD_TO_MNGTAGENT method=$p{'method'} server_name=$p{'server_name'} in $secs secs");

    # treat return response
    if( defined $res->{faultcode} ){
        return retErr($res->{faultstring},$res->{detail},$res->{faultcode});
    } else {
        my $result = $res->{'result'};
        if( ref($result) eq 'ARRAY' ){
            return wantarray() ? @$result: $result;
        } elsif( ref($result) eq 'HASH' ){
            return wantarray() ? %$result: $result;
        }
        return $result;
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

L<VirtAgent>, L<VirtAgent::Disk>, L<VirtAgent::Network>,
L<VirtMachine>

=cut

package VMSCache;

use strict;

use Fcntl ':flock';

BEGIN {
    my $lock_file = "/var/tmp/_VMSCache_lock";        # file for locks, via flock
    if( ! -e "$lock_file" ){
        open(LF,">$lock_file") or die "Couldn't create lock($lock_file)";
    } else {
        open(LF,"$lock_file") or die "Couldn't open lock($lock_file)";
    }
}

my %Name2Uuid = ();
my %VMS = ();

sub _readCache {

    my $Name2Uuid = SharedCache::sclookup("Name2Uuid","ALL");
    if( $Name2Uuid && (ref($Name2Uuid) eq 'HASH') ){
        %Name2Uuid = %$Name2Uuid;
    }
    my $VMS = SharedCache::sclookup("VMS","ALL");
    if( $VMS && (ref($VMS) eq 'HASH') ){
        %VMS = %$VMS;
    }
}

sub _storeCache {
    SharedCache::scstore("Name2Uuid","ALL",\%Name2Uuid);
    SharedCache::scstore("VMS","ALL",\%VMS);
}

sub getUuidFromName {
    my ($name) = @_;
    _readCache();
    return $Name2Uuid{"$name"};
}

sub getVMFromUuid {
    my ($uuid) = @_;
    _readCache();
    return $VMS{"$uuid"};
}

sub getVMName {
    my ($uuid) = @_;
    _readCache();
    return $VMS{"$uuid"}{'name'};
}

sub keysVMS {
    _readCache();
    return keys %VMS;
}

sub valuesVMS {
    _readCache();
    return values %VMS;
}

sub setVM {
    my ($uuid,$name,$VM) = @_;
    flock(LF,LOCK_EX);  # lock
    _readCache();
    $Name2Uuid{"${name}"} = $uuid;
    $VMS{"$uuid"} = $VM;
    _storeCache();
    flock(LF,LOCK_UN);  # unlock
    return $VM;
}

sub delName2Uuid {
    my ($name) = @_;

    flock(LF,LOCK_EX);  # lock
    _readCache();
    my $uuid;
    if( $Name2Uuid{"${name}"} ){
        $uuid = delete $Name2Uuid{"${name}"};
    }
    _storeCache();
    flock(LF,LOCK_UN);  # unlock

    return $uuid;
}
sub delVMUuid {
    my ($uuid) = @_;

    flock(LF,LOCK_EX);  # lock
    _readCache();
    my $VM;
    if( $VMS{"$uuid"} ){
        $VM = delete $VMS{"$uuid"};
    }
    _storeCache();
    flock(LF,LOCK_UN);  # unlock

    return $VM;
}

1;

package VNETSCache;

use strict;

use Fcntl ':flock';

BEGIN {
    my $lock_file = "/var/tmp/_VNETSCache_lock";        # file for locks, via flock
    if( ! -e "$lock_file" ){
        open(LF,">$lock_file") or die "Couldn't create lock($lock_file)";
    } else {
        open(LF,"$lock_file") or die "Couldn't open lock($lock_file)";
    }
}

my %VNETS = ();

sub _readCache {

    my $VNETS = SharedCache::sclookup("VNETS","ALL");
    if( $VNETS && (ref($VNETS) eq 'HASH') ){
        %VNETS = %$VNETS;
    }
    return wantarray () ? %VNETS : \%VNETS;
}

sub _storeCache {
    return SharedCache::scstore("VNETS","ALL",\%VNETS);
}

sub getVNET {
    my ($name) = @_;
    _readCache();
    return $VNETS{"$name"};
}
sub setVNET {
    my ($name,$VN) = @_;
    flock(LF,LOCK_EX);  # lock
    _readCache();
    $VNETS{"$name"} = $VN;
    _storeCache();
    flock(LF,LOCK_UN);  # unlock
    return $VN;
}
sub delVNET {
    my ($name) = @_;
    flock(LF,LOCK_EX);  # lock
    _readCache();
    my $VN;
    if( $VNETS{"$name"} ){
        $VN = delete $VNETS{"$name"};
    }
    _storeCache();
    flock(LF,LOCK_UN);  # unlock
    return $VN;
}
sub resetVNETS {
    %VNETS = ();
    _storeCache();
    return wantarray() ? %VNETS : \%VNETS;
}
sub allVNETS {
    _readCache();
    return wantarray() ? %VNETS : \%VNETS;
}

sub getVNETifout {
    my ($name) = @_;
    my $VN = getVNET($name);
    return $VN->{'ifout'};
}

1;

package SharedCache;

use strict;

use IPC::SysV qw(IPC_PRIVATE IPC_CREAT IPC_EXCL IPC_STAT S_IRUSR S_IWUSR IPC_RMID);
use IPC::SharedMem;
use Fcntl ':flock';
use Digest::MD5 qw(md5_hex);
use Storable;

BEGIN {
    my $lock_file = "/var/tmp/_SharedCache_lock";        # file for locks, via flock
    if( ! -e "$lock_file" ){
        open(LF,">$lock_file") or die "Couldn't create lock($lock_file)";
    } else {
        open(LF,"$lock_file") or die "Couldn't open lock($lock_file)";
    }
}

my %Proc_Reg = ();

sub _shm_key {
    my ($cache,$id) = @_;
    my $hex = md5_hex($cache.$id);
    my $key = pack A6 => $hex;
    $key = unpack i => $key;
    return $key;
}

sub _stat {
    my ($cache,$id) = @_;

    my $key = _shm_key($cache,$id);
    
    my $shm = IPC::SharedMem->new($key,0,0600);
    if( $shm ){
        if( my $stat = $shm->stat() ){
            return @$stat;
        }
    }
    return undef;
}

sub sclookup {
    my ($cache,$id) = @_;

    my $key = _shm_key($cache,$id);

    _lock();

    my $shm = shmget($key,0,0600);            # create a new segment for this key
    if( defined($shm) ){
                # get data from segment
        my ($userid,$gid,$cuid,$cgid,$mode,$segsz,
            $lpid,$cpid,$nattach,$atime,$dtime,$ctime) = _stat($cache,$id);

        my $ice = '';
        shmread($shm,$ice, 0, $segsz);
        my $C;
        eval { $C = Storable::thaw($ice); };
        if( !$C ){
            shmctl($shm,IPC_RMID,0);
            delete $Proc_Reg{"$shm"};
        } else {
            if( ($C->{'id'} eq $id) && ($C->{'cache'} eq $cache) ){
                $Proc_Reg{"$shm"} = $C if( !$Proc_Reg{"$shm"} );    # sync Proc_Reg
                _unlock();
                return $C->{'_data'};
            }
        }
    }

    _unlock();
    return;
}

sub scstore {
    my ($cache,$id,$data) = @_;

    my $key = _shm_key($cache,$id);

    my $C = { 'cache'=>$cache,
                'id'=>$id,
                '_data'=>$data };

    my $ice;
    eval {
        $ice = Storable::freeze($C);
    };
    return if( $@ );

    my $size = length($ice)+1;          # length, plus 1

    _lock();

    my $shmid = shmget($key,1,0600);    # check for existing memory segment
    if( defined($shmid) ){
        shmctl($shmid,IPC_RMID,0);
        delete $Proc_Reg{"$shmid"};
    }

    my $shm = shmget($key,$size,        # create a new segment for this key
                                    IPC_CREAT|IPC_EXCL|0600);

    if( defined($shm) ){
        shmwrite($shm,$ice,0,$size);    # write data into segment
        $Proc_Reg{"$shm"} = $C;
    }
    _unlock();
    return defined($shm) ? $size : 0;
}

sub scremove {
    my ($cache,$id) = @_;

    my $key = _shm_key($cache,$id);

    _lock();

    my $shmid = shmget($key,1,0600);    # check for existing memory segment
    if( defined($shmid) ){
        shmctl($shmid,IPC_RMID,0);
        delete $Proc_Reg{"$shmid"};
    }

    _unlock();

    return defined($shmid) ? 1 : 0;
}

sub scclean_up {
    for my $C (values(%Proc_Reg)){
        scremove($C->{'cache'},$C->{'id'});
    }
}

sub _lock {
    flock(LF,LOCK_EX);
}

sub _unlock {
    flock(LF,LOCK_UN);
}


1;
