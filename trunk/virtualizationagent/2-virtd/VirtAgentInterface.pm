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
    use vars qw($VERSION @ISA @EXPORT @EXPORT_OK %EXPORT_TAGS $CRLF $AUTOLOAD);
    $VERSION = '0.0.1';
    @ISA = qw( VirtAgent VirtAgent::Disk VirtAgent::Network );
    @EXPORT = qw( );
}

use Utils;

use VirtMachine;

use File::Copy;
use XML::Generator;
use LWP::Simple;
use Data::Dumper;

my %Name2Uuid;
my %VMS;
my %VNETS;
my $VM_DIR = "./vmdir";
my $CONF;
my $TMP_DIR = "/var/tmp";

sub AUTOLOAD {
    my $method = $AUTOLOAD;
    my $self = shift;

    if( my ($request_class,$m1,$m2) = ($method =~ m/(.*)::(.+)_(as_\w+)/) ){
        my $R = $request_class->$m1(@_);
        if( isError($R) ){
            return $R;
        } else {
            return $request_class->$m2($m1,$R);
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

    $p{'name'} = $self->{'_lastdomain'} if( !$p{'name'} );

    my $VM = $self->getVM(%p);

    if( $VM->isrunning() || $self->vmIsRunning(%p) ){
        return retErr("_ERR_VM_IS_RUNNING_","Error virtual machine is running.");
    }

    my $E = $self->startDomain(%p);

    if( isError($E) ){
        return wantarray() ? %$E : $E;
    }

    my $xml = $self->get_xml_domain( 'name'=>$VM->get_name() );

    $VM = $VM->loadfromxml( $xml );

    $VM->set_state("running");

    my %H = $VM->tohash();
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

    $p{'name'} = $self->{'_lastdomain'} if( !$p{'name'} );

    my $VM = $self->getVM(%p);
    if( !$VM ){
        return retErr("_ERR_VM_NOT_FOUND_","Error virtual machine not found.");
    }

    my $E = $self->stopDomain(%p);

    if( isError($E) ){
        return wantarray() ? %$E : $E;
    } else {
        $VM->set_state("stop");
        my %H = $VM->tohash();
        return retOk("_VM_STOP_OK_","Virtual machine successfully stoped","_RET_OBJ_",\%H);
    }
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

    # get name
    my $name = $A{'name'} = $p{'name'};

    # get memory
    $A{'memory'} = $p{'memory'};    # mem in bytes
    $A{'memory'} = $p{'ram'} * 1024 * 1024 if( $p{'ram'} ); # ram in mbytes

    # get uuid
    $A{'uuid'} = $p{'uuid'};

    # get vcpus
    $A{'vcpu'} = $p{'vcpu'};
    $A{'vcpu'} = $p{'ncpus'} if( $p{'ncpus'} );

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

    my $VM = VirtMachine->new( %A );
    my $uuid = $A{'uuid'} = $VM->get_uuid();
    # not running
    $VM->set_state("notrunning");

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
            $D{'target'} = $p{'disktarget'} || $p{'disk'}{'target'};
            $D{'readonly'} = $p{'diskreadonly'} || $p{'disk'}{'readonly'};

            push(@Disks,\%D);
        }
    }
    for my $D (@Disks){
        my $VD = $VM->add_disk(%$D);
        # TODO initialized if not yet
        #$X{'disk'}{'size'} = $p{'size'} if( $p{'size'} );
        #$X{'disk'}{'create'} = 1;

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

    # other stuff

    # os params: os_type os_variant ...
    if( my $pos = $p{'os'} ){
        for my $k ( keys %$pos ){
            $VM->set_attr("os_$k",$pos->{"$k"});
        }
    }

    if( $p{'kernel'} ){
        $VM->set_kernel( $A{'kernel'} = $p{'kernel'} );
        $VM->set_initrd( $A{'initrd'} = $p{'initrd'} );
        $VM->set_cmdline( $A{'cmdline'} = $p{'cmdline'} );
    } else {
        # location
        if( my $location = $A{'location'} = $p{'location'} ){
            if( $p{'bootdisk'} || $p{'cdrom'} ){
                my $bootdisk = VirtAgent->get_bootdisk($location,$type,$p{'arch'},$p{'distro'});
                $VM->add_disk( path=>$bootdisk, device=>'cdrom', node=>'hd', bus=>'ide', readonly=>1 );
                $VM->set_install( 1 );
            } elsif( $p{'cdrom'} ){
                $VM->add_disk( path=>$location, device=>'cdrom', node=>'hd', bus=>'ide', readonly=>1 );
                $VM->set_bootloader(1);
            } else {
                my ($kernel,$initrd,$extras) = VirtAgent->get_kernel($location,$type,$p{'arch'},$p{'distro'});
                $VM->set_kernel( $A{'kernel'} = $kernel );
                $VM->set_initrd( $A{'initrd'} = $initrd );
                $VM->set_cmdline( $A{'cmdline'} = $extras );
            }
        } else {
            $VM->set_install( $p{'install'} ) if( $p{'install'} );
            $VM->set_pxe( $A{'pxe'} = $p{'pxe'} ) if( $p{'pxe'} );
            $VM->set_bootloader(1);
        }
    }

=pod

=begin comment

# 04/06/2009 disable save to file

    # save to file

    if( $p{'savetofile'} ){
        my $file = $p{'savetofile'};
        $file = "${name}.conf" if( $file !~ m/[a-z][a-zA-Z0-9\.]+/ );
        if( $file ){
            my $fp = "$VM_DIR/$file";
            saveconfigfile($fp,\%A);
        }
    }

=end comment

=cut

    $self->{'_lastuuid'} = $uuid;
    $self->{'_lastdomain'} = $self->{'_lastname'} = $name;

    return $VM;
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

    if( $Name2Uuid{"$name"} ){
        return retErr("_ERROR_VMCREATE_EXISTS_","Name already exists.");
    }

    # save to file from default config
#    $p{'savetofile'} ||= $CONF->{'savetofile'} || 0;

    my $VM = $self->vmLoad(%p);

    my $uuid = $VM->get_uuid();
    my $name = $VM->get_name();

    $VMS{"$uuid"} = $VM;
    $Name2Uuid{"${name}"} = $uuid;

    # TODO initialize on hypervisor

    my %H = $VM->tohash();
    # TODO change this
    return retOk("_VM_CREATED_OK_","Virtual machine successfully created","_RET_OBJ_",\%H);
}
sub prep_disk_params {
    my $self = shift;
    my (%p) = @_;

    if( defined $p{'disk'} ){
        my $disk = $p{'disk'};
        if( !ref($disk) ){
            my @Disks = ();
            for my $d (split(/;/,$disk)){
                my %D = ();
                for my $field (split(/,/,$d)){
                    my ($f,$v) = split(/=/,$field,2);
                    $D{"$f"} = $v;
                }
                push(@Disks,\%D);
            }
            $p{'disk'} = \@Disks;
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
            my @Network = ();
            for my $net (split(/;/,$network)){
                my %N = ();
                for my $field (split(/,/,$net)){
                    my ($f,$v) = split(/=/,$field,2);
                    $N{"$f"} = $v;
                }
                if( !$N{'type'} ){
                    if( $N{'name'} ){
                        $N{'type'} = "network";
                    } elsif( $N{'bridge'} ) {
                        $N{'type'} = "bridge";
                    } else {
                        $N{'type'} = "user";
                    }
                }
                push(@Network,\%N);
            }
            $p{'network'} = \@Network;
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
        $name = $VMS{"$uuid"}{'name'};
    } elsif( $name ){
        $uuid = $Name2Uuid{"${name}"};
    }

    # force to stop
    my %E = $self->stopDomain( 'name'=>$name, force=>1, destroy=>1 );

    # undefine domain
    my %U = $self->undefDomain( 'name'=>$name );

    delete $VMS{"$uuid"};
    delete $Name2Uuid{"${name}"};
    # TODO change this
    return retOk("_OK_","ok");
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

    my $xml = $self->get_xml_domain( 'name'=>$VM->get_name() );
    # TODO
    #   fixme - update some info
    $VM = $VM->loadfromxml( $xml );
    $VM->set_state("notrunning");

    $VM->set_initialized(1);

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

    if( $Name2Uuid{"$name"} ){
        return retErr("_ERROR_VMCREATE_EXISTS_","Name already exists.");
    }

    my $VM = $self->vmLoad(%p);

    my %V = $self->defineDomain( $VM->todomain() );
    if( isError(%V) ){
        return wantarray() ? %V : \%V;
    }

    my $xml = $self->get_xml_domain( 'name'=>$VM->get_name() );
    # TODO
    #   fixme - update some info
    $VM = $VM->loadfromxml( $xml );
    $VM->set_state("notrunning");

    $VM->set_initialized(1);

    my $uuid = $VM->get_uuid();
    my $name = $VM->get_name();

    $VMS{"$uuid"} = $VM;
    $Name2Uuid{"${name}"} = $uuid;

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

    my $VM = $self->getVM(%p);
    if( $VM && !$VM->isrunning() && !$self->vmIsRunning(%p) ){

        # undefine domain
        $self->undefDomain( 'name'=>$VM->get_name() );

        my $uuid = $VM->get_uuid();
        my $name = $VM->get_name();

        delete $VMS{"$uuid"};
        delete $Name2Uuid{"${name}"};

        $self->create_vm( %p );
        
    } else {
        return retErr("_ERR_VM_IS_RUNNING_","Error virtual machine is running.");
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
sub loadconf {
    # load conf
    $CONF = Utils::get_conf();
    $VM_DIR = $CONF->{'VM_DIR'} if( $CONF->{'VM_DIR'} );
    $TMP_DIR = $CONF->{'tmpdir'} if( $CONF->{'tmpdir'} );
}
# loadvms
#   load virtual machines
sub loadvms {
    my $self = shift;

    $self = $self->new();

    %VMS = ();

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
                $uuid ||= $Name2Uuid{"$nm"};
                if( $uuid ){
                    my $xml = VirtAgent->get_xml_domain("name"=>$nm);
                    if( !isError($xml) ){
                        # load as VirtMachine
                        my $VM = $VMS{"$uuid"} = VirtMachine->loadfromxml($xml);
                    
                        $VM->set_initialized(1);
                        $VM->set_state( $Di->{'state'} );
                        $Name2Uuid{"$nm"} = $uuid;
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
    $name = $p{'name'} if( $p{'name'} );
    $uuid = $p{'uuid'} if( $p{'uuid'} );

    $uuid = $Name2Uuid{"$name"} if( $name );

    my $VM = $VMS{"$uuid"};
    return $VM;
}

=item list_vms

list running virtual machines

    my $List = VirtAgentInterface->list_vms( );

=cut

# list_vms
#   list running virtual machines
sub list_vms {
    my $self = shift;
    my @list = ();

    for my $VM (values %VMS){
        my %H = $VM->tohash();
        push @list, \%H;
    }
    return wantarray() ? @list : \@list;
}
sub hash_vms {
    my $self = shift;
    my %hash = ();

    for my $VM (values %VMS){
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
            $D{'target'} = $p{'disktarget'} || $p{'disk'}{'target'};
            $D{'readonly'} = $p{'diskreadonly'} || $p{'disk'}{'readonly'};

            push(@Disks,\%D);
        }
    }
    if( @Disks ){
        for my $D (@Disks){
            my %TD = ( 'path' => $D->{'path'},
                            'device' => $D->{'device'},
                            'drivertype' => $D->{'drivertype'},
                            'drivername' => $D->{'drivername'},
                            'target' => $D->{'target'},
                            'readonly' => $D->{'readonly'}
                            );
            my $VD = $VM->add_disk( %TD );

            # initialize on hypervisor if vm is defined
            if( $VM->get_initialized() ){
                my $DTD = $VD->todevice();
                my $S = $self->attachDevice( name => $VM->get_name(),
                                                devices => { disk => $DTD } );
                if( isError($S) ){
                    # something goes wrong remove disk
                    $VM->del_disk( i=>$VD->get_i() );

                    return wantarray() ? %$S : $S;
                }
            }

            # TODO initialized if not yet
            #$X{'disk'}{'size'} = $p{'size'} if( $p{'size'} );
            #$X{'disk'}{'create'} = 1;
        }

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

    my $VM = $self->getVM(%p);
    if( !$VM ){
        return retErr("_ERR_VM_NOT_FOUND_","Error virtual machine not found.");
    }

    my $VD = $VM->get_disk( i => $p{'i'} );
    if( $VD ){
        # detach from hypervisor if vm is defined
        if( $VM->isrunning() ){
            my $D = $VD->todevice();
            my $S = $self->detachDevice( name => $VM->get_name(),
                                            devices => { disk => $D } );
            if( isError($S) ){
                return wantarray() ? %$S : $S;
            }
        }
        $VD = $VM->del_disk( i => $p{'i'} );

        # redefine domain
        my %V = $self->defineDomain( $VM->todomain() );
        if( isError(%V) ){
            return wantarray() ? %V : \%V;
        }

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
        for my $N (@Network){
            my %TN = ( 'type' => $N->{'type'},
                                        'bridge' => $N->{'bridge'},
                                        'name' => $N->{'name'},
                                        'macaddr' => $N->{'macaddr'}
                                        );
            my $VN = VirtNetwork->new( %TN );
            $N->{'macaddr'} = $TN{'macaddr'} = $VN->get_macaddr();

            # initialize on hypervisor if vm is defined
            if( $VM->get_initialized() ){
                my $D = $VN->todevice();
                my $S = $self->attachDevice( name => $VM->get_name(),
                                                devices => { interface => $D } );
                if( isError($S) ){
                    return wantarray() ? %$S : $S;
                }
            }
            $VN = $VM->add_network( %TN );
        }
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

    my $VM = $self->getVM(%p);
    if( !$VM ){
        return retErr("_ERR_VM_NOT_FOUND_","Error virtual machine not found.");
    }

    my $VN = $VM->get_network( i => $p{'i'}, macaddr => $p{'macaddr'} );
    if( $VN ){
        # detach from hypervisor if vm is defined
        if( $VM->isrunning() ){
            my $D = $VN->todevice();
            my $S = $self->detachDevice( name => $VM->get_name(),
                                            devices => { interface => $D } );
            if( isError($S) ){
                return wantarray() ? %$S : $S;
            }
        }
        $VN = $VM->del_network( i => $VN->{'i'} );

        # redefine domain
        my %V = $self->defineDomain( $VM->todomain() );
        if( isError(%V) ){
            return wantarray() ? %V : \%V;
        }

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

    my $VM = $self->getVM(%p);
    if( !$VM ){
        return retErr("_ERR_VM_NOT_FOUND_","Error virtual machine not found.");
    }

    my $name = $VM->get_name();
    my $isrunning = $VM->isrunning();

    while( my $VN = $VM->last_network() ){
        # VM is running
        if( $isrunning ){
            my $D = $VN->todevice();
            my $S = $self->detachDevice( name => $name,
                                            devices => { interface => $D } );
            if( isError($S) ){
                return wantarray() ? %$S : $S;
            }
        }
        $VN = $VM->del_network( i => $VN->{'i'} );
    }

    # redefine domain
    my %V = $self->defineDomain( $VM->todomain() );
    if( isError(%V) ){
        return wantarray() ? %V : \%V;
    }

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
        for my $F (@Filesystem){
            # initialize on hypervisor if vm is defined
            if( $VM->get_initialized() ){
                my $S = $self->attachDevice( name => $VM->get_name(),
                                                devices => { filesystem => $F } );
                if( isError($S) ){
                    return wantarray() ? %$S : $S;
                }
            }
            # add at end if nothing goes wrong
            my $VF = $VM->add_filesystem( %$F );
        }
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

    my $VM = $self->getVM(%p);
    if( !$VM ){
        return retErr("_ERR_VM_NOT_FOUND_","Error virtual machine not found.");
    }

    my $VF = $VM->get_filesystem( i => $p{'i'} );
    if( $VF ){
        # detach from hypervisor if vm is defined
        if( $VM->get_initialized() ){
            my $S = $self->detachDevice( name => $VM->get_name(),
                                            devices => { filesystem => $VF } );
            if( isError($S) ){
                return wantarray() ? %$S : $S;
            }
        }
        $VF = $VM->del_filesystem( i => $p{'i'} );
        return retOk("_DETACH_FS_OK_","Filesystem successfully detached.");
    } else {
        return retErr("_ERR_DETACH_FS_","Error no filesystem to detach.");
    }
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

    my %X = ();

    my $name = $X{'name'} = $p{'name'};

    if( !$name ){
        return retErr("_ERR_CREATE_NET_NONAME_","Error creating network: need name");
    }

    if( $VNETS{"$name"} ){
        return retErr("_ERR_CREATE_NET_EXIST_","Error creating network: already exists");
    }

    my $uuid = $X{'uuid'} = $p{'uuid'} || random_uuid(); 

    # gen bridge name
    $X{'bridge'}{'name'} = $p{'bridge'} || VirtAgent::Network->brcreate_prefix();

    # forward options
    if( $p{'forwardmode'} || $p{'forwarddev'} ){
        $X{'forward'}{'mode'} = $p{'forwardmode'} || 'nat';
        $X{'forward'}{'dev'} = $p{'forwarddev'} || VirtAgent::Network->defaultroute();
    }

    # network addressing
    if( my $addr = $p{'ipaddr'} ){
        my ($ip,$n) = split(/\//,$addr);
        my $netmask = '255.255.255.255';
        $netmask = '255.0.0.0'     if( $n == 8 );
        $netmask = '255.255.0.0'   if( $n == 16 );
        $netmask = '255.255.255.0' if( $n == 24 );

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
    plog "xml=",$xml;

    my $vm = $self->vmConnect();
    if( isError($vm) ){
        return wantarray() ? %$vm : $vm;
    }

    # create
    my $vn;
    eval {
        $vn = $vm->create_network($xml);
    };
    if( $@ ){
        return retErr("_ERR_CREATE_NETWORK_","Error creating network: $@");
    }

    if( my $autostart = $p{'autostart'} ){
        $vn->set_autostart($autostart);
    }

    # update bridges info
    VirtAgent::Network->loadbridges(1);

    my $dxml = $vn->get_xml_description();
    plog "xml=",$dxml,"\n";

    my $name = $vn->get_name();
    my %N = ( 'name' => $name,
                'uuid' => $vn->get_uuid_string(),
                'bridge' => $vn->get_bridge_name(),
                'autostart' => $vn->get_autostart(), 'active' => 1 );
    
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

=item list_networks

    my $Hash = VirtAgentInterface->list_networks( );

=cut

# list_networks
#   list of networks
#   args: empty
#   res: Hash { name => info }
sub list_networks {
    my $self = shift;

    if( !%VNETS ){
        $self->load_vnets();
    }

    return wantarray() ? %VNETS : \%VNETS;
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

    %VNETS = ();

    my $vm = $self->vmConnect();

    my @run_nets = $vm->list_networks();
    for my $N (@run_nets){
        my $name = $N->get_name();
        $VNETS{"$name"} = { 'name' => $name,
                            'uuid' => $N->get_uuid_string(),
                            'bridge' => $N->get_bridge_name(),
                            'autostart' => $N->get_autostart(), 'active' => 1 };
    }

    my @notrun_nets = $vm->list_defined_networks();
    for my $N (@notrun_nets){
        my $name = $N->get_name();
        $VNETS{"$name"} = { 'name' => $name,
                        'uuid' => $N->get_uuid_string(),
                        'bridge' => $N->get_bridge_name(),
                        'autostart' => $N->get_autostart(), 'active' => 0 };
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

    my $N;

    if( $uuid ){
        $N = $vm->get_network_by_uuid($uuid);
    } elsif( $name ){
        $N = $vm->get_network_by_name($name);
    }
    if( $N ){
        $N->destroy();

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

    my $N;

    if( $uuid ){
        $N = $vm->get_network_by_uuid($uuid);
    } elsif( $name ){
        $N = $vm->get_network_by_name($name);
    }
    if( $N ){
        $N->undefine();

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

    my $VM = $self->updateStateVM( %p );

    my %H = $VM->tohash();

    return retOk("_VM_UPDATESTATE_OK_","Virtual machine state successfully updated","_RET_OBJ_",\%H);
}

sub updateStateVM {
    my $self = shift;
    my %p = @_;

    $p{'name'} = $self->{'_lastdomain'} if( !$p{'name'} );

    my $VM = $self->getVM(%p);
    if( !$VM ){
        return retErr("_ERR_VM_NOT_FOUND_","Error virtual machine not found.");
    }

    my $xml = $self->get_xml_domain( 'name'=>$VM->get_name() );

    $VM = $VM->loadfromxml( $xml );

    return $VM;
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

