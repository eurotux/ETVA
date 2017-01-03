#!/usr/bin/perl
# Copywrite Eurotux 2009
# 
# CMAR 2009/05/19 (cmar@eurotux.com)

# VirtMachine

=pod

=head1 NAME

VirtMachine - 

=head1 SYNOPSIS

...

=head1 DESCRIPTION

...

=head1 OVERVIEW OF CLASSES AND PACKAGES

=cut

=head2 VirtMachine

=over 4

=cut

package VirtMachine;

use strict;

BEGIN {
    # this is the worst damned warning ever, so SHUT UP ALREADY!
    $SIG{__WARN__} = sub { warn @_ unless $_[0] =~ /Use of uninitialized value/ };

    use vars qw($VERSION @ISA @EXPORT @EXPORT_OK %EXPORT_TAGS $CRLF $AUTOLOAD);
    $VERSION = '0.0.1';
    @ISA = qw( VirtObjects );
    @EXPORT = qw( );
}

use VirtAgentInterface;
use VirtAgent::Disk;

use ETVA::Utils;
#use Data::Dumper;

use XML::DOM;
use XML::Generator;
use LWP::Simple;

use constant {
    DEVICE_CPU => 3,
    DEVICE_MEMORY => 4,
    DEVICE_IDE_BUS => 5,
    DEVICE_SCSI_BUS => 6,
    DEVICE_ETHERNET => 10,
    DEVICE_DISK => 17,
    DEVICE_GRAPHICS => 24
};

=item new

    my $VM = VirtMachine->new( name=>$name, uuid=>$uuid, ... );

=cut

sub new {
    my $self = shift;
    my %p = @_;
    unless( ref $self ){
        my $class = ref($self) || $self;

        my %M = %p;

        $M{'name'} = $p{'name'};
        $M{'uuid'} = $p{'uuid'} || random_uuid();

        $M{'Disks'} = [];
        $M{'Network'} = [];

        $self = bless {%M} => $class;
    }
    return $self;
}

sub clone {
    my $self = shift;
    
    my $clone = $self->SUPER::clone();
    if( ref($self) ){
        for my $k (keys %$self){
            if( !ref($self->{"$k"}) ){
                $clone->{"$k"} = $self->{"$k"};
            }
        }
        if( $self->{'Disks'} ){
            my $Disks = [];
            my $disks = $self->{'Disks'};
            for my $VD (@$disks){
                my $clone_VD = $VD->clone();
                push(@$Disks, $clone_VD);
            }
            $clone->{'Disks'} = $Disks;
        }
        if( $self->{'Network'} ){
            my $Network = [];
            my $network = $self->{'Network'};
            for my $VN (@$network){
                my $clone_VN = $VN->clone();
                push(@$Network, $clone_VN);
            }
            $clone->{'Network'} = $Network;
        }
    }
    return $clone;
}

=item get_name

    my $name =  $VM->get_name();

=cut

sub get_name {
    my $self = shift;

    return $self->{'name'};
}

=item get_uuid

    my $uuid = $VM->get_uuid();

=cut

sub get_uuid {
    my $self = shift;

    return $self->{'uuid'};
}

sub isrunning {
    my $self = shift;

    if( $self->get_state() eq 'running' ||
        $self->get_state() eq 'idle' ){
        return 1;
    }
    return 0;
}
sub set_state {
    my $self = shift;
    my ($state) = @_;
    
    $state = lc($state);
    $state =~ s/state_//;

    $state = "idle" if( $state eq "blocked" );

    return $self->{'state'} = $state;
}

sub set_attr {
    my $self = shift;
    my ($a,$v) = @_;
    $self->{"$a"} = $v;
}

sub set_feature {
    my $self = shift;
    my ($f) = @_;
    $self->{"features"}{"$f"} = 1;
}

sub get_feature {
    my $self = shift;
    my ($f) = @_;
    return $self->{"features"}{"$f"} ? 1 : 0;
}

=item get_disk

    # get cdrom defined on object or get from Disks

    my $path = $VM->get_cdrom( ); 

=cut

sub get_cdrom {
    my $self = shift;
    if( defined($self->{'cdrom'}) ){
        if( my $disks = $self->{'Disks'} ){
            for my $VD (@$disks){
                if( $VD->get_device() eq 'cdrom' ){
                    $self->{'cdrom'} = $VD->get_path();
                    last;
                }
            }
        }
    }
    return $self->{'cdrom'};
}

=item get_disk

    my $Disk = $VM->get_disk( i=>$i ); 

=cut

sub get_disk {
    my $self = shift;
    my %p = @_;
    my $i = $p{'i'};
    if( defined $i ){
        if( $i < scalar(@{$self->{'Disks'}}) ){
            return $self->{'Disks'}->[$i];
        }
    } elsif( defined($p{'target'}) || defined($p{'path'}) ){
        return $self->get_disk_i( %p );
    }
    return;
}

sub get_disk_i {
    my $self = shift;
    my %p = @_;
    my $target = $p{'target'};
    my $path = $p{'path'};

    if( defined($target) || defined($path) ){
        if( my $disks = $self->{'Disks'} ){
            my $i = 0;
            for my $VD (@$disks){
                if( ( defined($target) && ( $VD->get_target() eq $target ) )
                        || ( defined($path) && ( $VD->get_path() eq $path ) ) ){
                    return wantarray() ? ($i,$VD) : $VD;
                }
                $i++;
            }
        }
    }
    return;
}

sub init_disks {
    my $self = shift;

    delete $self->{'Disks'} if( $self->{'Disks'} );
    $self->{'Disks'} = [];

}

=item add_disk

    my $Disk = $VM->add_disk( path=>... );

=cut

sub add_disk {
    my $self = shift;
    my %p = @_;
    $p{'i'} = scalar(@{$self->{'Disks'}});  # disk index
    my $node = $p{'node'} || 'xvd';
    if( !$p{'node'} && $p{'bus'} ){
        if( $p{'bus'} eq 'ide' ){
            $node = $p{'node'} = 'hd';
        } elsif( $p{'bus'} eq 'scsi' ){
            $node = $p{'node'} = 'sd';
        } elsif( $p{'bus'} eq 'virtio' ){
            $node = $p{'node'} = 'vd';
        } else {
            $node = $p{'node'} = 'xvd';
        }
    }
    $p{'ni'} = grep { $_->{'node'} eq $node } @{$self->{'Disks'}};  # disk index
    my $disk = VirtDisk->new(%p);
    push @{$self->{'Disks'}}, $disk;
    return $disk;
}

sub add_disk_ifnot_exists {
    my $self = shift;
    my %p = @_;
    my ($disk) = grep { $_->{'path'} eq $p{'path'} } @{$self->{'Disks'}};
    if( !$disk ){   # if not exists
        return $self->add_disk(%p);
    }
    return $disk;
}

=item del_disk

    my $Disk = $VM->del_disk( i=>$i ); 

=cut

sub del_disk {
    my $self = shift;
    my %p = @_;
    my $i = $p{'i'};
    if( defined $i ){
        my $Disks = $self->{'Disks'};
        my $n = scalar(@$Disks);
        for(my $c=0;$c<$n;$c++){
            if( $c > $i ){
                $Disks->[$c]->set_i($c-1);
            }
        }
        # get disk object
        my $D = $Disks->[$i];
        # delete from array
        splice(@$Disks,$i,1);
        return $D;
    }
    return;
}

# last_disk
#   return last disk
#
sub last_disk {
    my $self = shift;
    if( $self->{'Disks'} ){
        my $i = scalar(@{$self->{'Disks'}})-1;  # disk index
        if( my $VD = $self->{'Disks'}->[$i] ){
            return $VD;
        }
    }
    return;
}

sub count_disks {
    my $self = shift;
    if( $self->{'Disks'} ){
        return scalar(@{$self->{'Disks'}});
    }
    return;
}

=item get_filesystem

    my $FS = $VM->get_filesystem( i=>$i ); 

=cut

sub get_filesystem {
    my $self = shift;
    my %p = @_;
    my $i = $p{'i'};
    if( defined $i ){
        if( $i < scalar(@{$self->{'Filesystem'}}) ){
            return $self->{'Filesystem'}->[$i];
        }
    }
    return;
}

sub init_filesystem {
    my $self = shift;

    delete $self->{'Filesystem'} if( $self->{'Filesystem'} );

}

=item add_filesystem

    my $FS = $VM->add_filesystem( name=>..., target=>... );

=cut

sub add_filesystem {
    my $self = shift;
    my %p = @_;

    # initialize if not defined 
    $self->{'Filesystem'} = [] if( !$self->{'Filesystem'} );

    $p{'i'} = scalar(@{$self->{'Filesystem'}});  # fs index

    my $fs = \%p;
    push @{$self->{'Filesystem'}}, $fs;
    return $fs;
}


=item del_filesystem

    my $FS = $VM->del_filesystem( i=>$i ); 

=cut

sub del_filesystem {
    my $self = shift;
    my %p = @_;
    my $i = $p{'i'};
    if( defined $i ){
        if( my $Filesystem = $self->{'Filesystem'} ){
            my $n = scalar(@$Filesystem);
            for(my $c=0;$c<$n;$c++){
                if( $c > $i ){
                    $Filesystem->[$c]->{'i'} = $c-1;
                }
            }
            # get fs
            my $D = $Filesystem->[$i];
            # delete from array
            splice(@$Filesystem,$i,1);
            return $D;
        }
    }
    return;
}

# last_filesystem
#   return last filesystem
#
sub last_filesystem {
    my $self = shift;
    if( $self->{'Filesystem'} ){
        my $i = scalar(@{$self->{'Filesystem'}})-1;  # filesystem index
        if( my $VF = $self->{'Filesystem'}->[$i] ){
            return $VF;
        }
    }
    return;
}

sub count_filesystem {
    my $self = shift;
    if( $self->{'Filesystem'} ){
        return scalar(@{$self->{'Filesystem'}});
    }
    return;
}

=item get_hostdev

    my $HD = $VM->get_hostdev( i=>$i ); 

=cut

sub get_hostdev {
    my $self = shift;
    my %p = @_;
    my $i = $p{'i'};
    if( defined $i ){
        if( $i < scalar(@{$self->{'Hostdev'}}) ){
            return $self->{'Hostdev'}->[$i];
        }
    } elsif( defined($p{'type'})  &&
            ( (defined($p{'vendor'}) && defined($p{'product'})) || 
                (defined($p{'bus'}) && defined($p{'slot'}) && defined($p{'function'})) ) ){
        return $self->get_hostdev_i(%p);
    }
    return;
}
sub get_hostdev_i {
    my $self = shift;
    my %p = @_;

    if( defined($p{'type'})  &&
            ( (defined($p{'vendor'}) && defined($p{'product'})) || 
                (defined($p{'bus'}) && defined($p{'slot'}) && defined($p{'function'})) ) ){
        if( my $hostdevs = $self->{'Hostdev'} ){
            my $i = 0;
            for my $HD (@$hostdevs){
                if( (($p{'type'} eq 'usb') && ($HD->{'type'} eq $p{'type'}) && ($HD->{'vendor'} eq $p{'vendor'}) && ($HD->{'product'} eq $p{'product'})) ||
                    (($p{'type'} eq 'pci') && ($HD->{'type'} eq $p{'type'}) && ($HD->{'bus'} eq $p{'bus'}) && ($HD->{'slot'} eq $p{'slot'}) && ($HD->{'function'} eq $p{'function'})) ){
                    return wantarray() ? ($i,$HD) : $HD;
                }
                $i++;
            }
        }
    }
    return;
}

sub init_hostdev {
    my $self = shift;

    delete $self->{'Hostdev'} if( $self->{'Hostdev'} );

}

=item add_hostdev

    # For USB
    my $HD = $VM->add_hostdev( type=>'usb', vendor=>..., product=>... );
    # For PCI
    my $HD = $VM->add_hostdev( type=>'pci', bus=>..., slot=>..., function=>... );

=cut

sub add_hostdev {
    my $self = shift;
    my %p = @_;

    # initialize if not defined 
    $self->{'Hostdev'} = [] if( !$self->{'Hostdev'} );

    $p{'i'} = scalar(@{$self->{'Hostdev'}});  # fs index

    my $hd = \%p;
    push @{$self->{'Hostdev'}}, $hd;
    return $hd;
}


=item del_hostdev

    my $HD = $VM->del_hostdev( i=>$i ); 

=cut

sub del_hostdev {
    my $self = shift;
    my %p = @_;
    my $i = $p{'i'};
    if( defined $i ){
        if( my $Hostdev = $self->{'Hostdev'} ){
            my $n = scalar(@$Hostdev);
            for(my $c=0;$c<$n;$c++){
                if( $c > $i ){
                    $Hostdev->[$c]->{'i'} = $c-1;
                }
            }
            # get fs
            my $D = $Hostdev->[$i];
            # delete from array
            splice(@$Hostdev,$i,1);
            return $D;
        }
    }
    return;
}

# last_hostdev
#   return last hostdev
#
sub last_hostdev {
    my $self = shift;
    if( $self->{'Hostdev'} ){
        my $i = scalar(@{$self->{'Hostdev'}})-1;  # hostdev index
        if( my $HD = $self->{'Hostdev'}->[$i] ){
            return $HD;
        }
    }
    return;
}

sub count_hostdev {
    my $self = shift;
    if( $self->{'Hostdev'} ){
        return scalar(@{$self->{'Hostdev'}});
    }
    return;
}

=item get_network

    my $Network = $VM->get_network( i=>$i ); 

=cut

sub get_network {
    my $self = shift;
    my %p = @_;
    my $i = $p{'i'};
    if( defined $i ){
        if( $i < scalar(@{$self->{'Network'}}) ){
            return $self->{'Network'}->[$i];
        }
    } elsif( $p{'macaddr'} ){
        return $self->get_network_i(%p);
    }
    return;
}
sub get_network_i {
    my $self = shift;
    my %p = @_;
    my $macaddr = $p{'macaddr'};
    if( $macaddr ){
        if( my $networks = $self->{'Network'} ){
            my $i = 0;
            for my $VN (@$networks){
                if( $VN->get_macaddr() eq $macaddr ){
                    return wantarray() ? ($i,$VN) : $VN;
                }
                $i++;
            }
        }
    }
    return;
}

sub init_network {
    my $self = shift;

    delete $self->{'Network'} if( $self->{'Network'} );
    $self->{'Network'} = [];

}

=item add_network

    my $Network = $VM->add_network( type=>..., name=>... );

=cut

sub add_network {
    my $self = shift;
    my %p = @_;
    $p{'i'} = scalar(@{$self->{'Network'}});  # network index
    my $net = VirtNetwork->new(%p);
    push @{$self->{'Network'}}, $net;
    return $net;
}

=item del_network

    my $Network = $VM->del_network( i=>$i ); 

=cut

sub del_network {
    my $self = shift;
    my %p = @_;
    my $i = $p{'i'};
    if( defined $i ){
        my $Network = $self->{'Network'};
        my $n = scalar(@$Network);
        for(my $c=0;$c<$n;$c++){
            if( $c > $i ){
                $Network->[$c]->set_i($c-1);
            }
        }
        # get network object
        my $N = $Network->[$i];
        # delete from array
        splice(@$Network,$i,1);
        return $N;
    }
    return;
}

# last_network
#   return last network interface
#
sub last_network {
    my $self = shift;
    if( $self->{'Network'} ){
        my $i = scalar(@{$self->{'Network'}})-1;  # network index
        if( my $VN = $self->{'Network'}->[$i] ){
            return $VN;
        }
    }
    return;
}

sub count_network {
    my $self = shift;
    if( $self->{'Network'} ){
        return scalar(@{$self->{'Network'}});
    }
    return;
}

=item toxml

generate xml domain ( VirtAgent->genXMLDomain )

    my $xml = $VM->toxml();

=cut

sub toxml {
    my $self = shift;

    return VirtAgent->genXMLDomain( $self->todomain() );
}

=item todomain

generate hash with fields for VirtAgent->genXMLDomain

    my $Hash = $VM->todomain();

=cut

sub todomain {
    my $self = shift;

    my %D = ();
    for my $f (qw( name uuid description memory vcpu cpuset on_reboot on_poweroff on_crash features arch cpu clock )){
        $D{"$f"} = $self->{"$f"};
    }
    if( $self->{'kernel'} ){
        $D{'os'}{'kernel'} = $self->{'kernel'};
        $D{'os'}{'initrd'} = $self->{'initrd'};
        $D{'os'}{'cmdline'} = $self->{'cmdline'};
    }

    $D{'os'}{'type'} = $self->{'os_type'} if( $self->{'os_type'} );
    $D{'os'}{'variant'} = $self->{'os_variant'} if( $self->{'os_variant'} );
    $D{'os'}{'init'} = $self->{'os_init'} if( $self->{'os_init'} );

    $D{'os'}{'loader'} = $self->{'loader'} || $self->{'os_loader'} if( $self->{'loader'} ||  $self->{'os_loader'});
    $D{'os'}{'pxe'} = $self->{'pxe'} || $self->{'os_pxe'} if( $self->{'pxe'} ||  $self->{'os_pxe'});
    $D{'os'}{'install'} = $self->{'install'} || $self->{'os_install'} if( $self->{'install'} ||  $self->{'os_install'});
    $D{'os'}{'bootdev'} = $self->{'bootdev'} || $self->{'os_bootdev'} if( $self->{'bootdev'} ||  $self->{'os_bootdev'});
    $D{'os'}{'bootmenu'} = $self->{'bootmenu'} || $self->{'os_bootmenu'} if( $self->{'bootmenu'} ||  $self->{'os_bootmenu'});
    $D{'boot'}{'loader'} = 1 if( $self->{'bootloader'} );

    if( my $devices = $self->get_todevices() ){
        $D{'devices'} = $devices;
    }

    return wantarray() ? %D : \%D; 
}

=item get_todevices

get devices of VirtMachine

    my $Hash = $VM->get_todevices();

=cut

sub get_todevices {
    my $self = shift;

    my %D = ();

    $D{'graphics'}{'type'} = "vnc" if( !$self->{'nographics'} );
    # vnc port
    if( $self->{'vnc_port'} ){
        $D{'graphics'}{'type'} = "vnc";
        $D{'graphics'}{'port'} = $self->{'vnc_port'};
    }
    # vnc listen: local or any
    if( $self->{'vnc_listen'} ){
        my $listen = $self->{'vnc_listen'};
        $listen = '0.0.0.0' if( $listen eq 'any' );
        $listen = '127.0.0.1' if( $listen eq 'local' );
        $D{'graphics'}{'type'} = "vnc";
        $D{'graphics'}{'listen'} = $listen;
    }
    # vnc keymap: specifies the keymap to use
    if( $self->{'vnc_keymap'} ){
        my $keymap = $self->{'vnc_keymap'};
        $D{'graphics'}{'type'} = "vnc";
        $D{'graphics'}{'keymap'} = $keymap;
    }
    if( $D{'graphics'} &&
            (!$D{'graphics'}{'port'} || ( $D{'graphics'}{'port'} <= 0 ) ) ){
        $D{'graphics'}{'autoport'} = 'yes';
    }

    my @input = ();
    # input mouse
    if( $self->{'mouse_bus'} ){
        push( @input, { 'type'=>"mouse", 'bus'=>$self->{'mouse_bus'} } );
    }
    # input tablet
    if( $self->{'tablet_bus'} ){
        push( @input, { 'type'=>"tablet", 'bus'=>$self->{'tablet_bus'} } );
    }
    $D{'input'} = \@input if( @input );

    my @controllers = ();
    if( $self->{'Controllers'} ){
        my $arr_controllers = $self->{'Controllers'};
        push(@controllers,@$arr_controllers);
    }
    my @channels = ();
    if( $self->{'Channels'} ){
        my $arr_channels = $self->{'Channels'};
        push(@channels,@$arr_channels);
    }
    # TODO $p{'virtio-channel-path'} = $cn{'source'}{'path'};
    $D{'controller'} = \@controllers;
    $D{'channel'} = \@channels;

    my @serials = ();
    if( $self->{'Serials'} ){
        my $arr_serials = $self->{'Serials'};
        push(@serials,@$arr_serials);
    }
    $D{'serial'} = \@serials;

    for my $H ( @{$self->{'Disks'}} ){
        my $DD = $H->todevice();
        push @{$D{'disk'}}, $DD;
    }

    for my $N ( @{$self->{'Network'}} ){
        my $DI = $N->todevice();
        push @{$D{'interface'}}, $DI;
    }

    if( my $filesystem = $self->{'Filesystem'} ){
        for my $F ( @$filesystem ){
            my %DF = ();
            for my $k (keys %$F){
                next if( $k eq 'i' );
                if( $k eq 'target' ){
                    $DF{'target'} = { 'dir' => $F->{'target'} };
                } elsif( $k eq 'file' or 
                            $k eq 'dir' or 
                            $k eq 'dev' or 
                            $k eq 'name' ){
                    $DF{'source'}{"$k"} = $F->{"$k"};
                } else {
                    $DF{"$k"} = $F->{"$k"};
                }
            }
            push @{$D{'filesystem'}}, \%DF;
        }
    }
    
    if( my $hostdev = $self->{'Hostdev'} ){
        for my $H ( @$hostdev ){
            my %DH = ();
            for my $k (keys %$H){
                if( $k eq 'vendor' or 
                            $k eq 'product'){
                    $DH{'source'}{"$k"}{'id'} = $H->{"$k"};
                } elsif( $k eq 'bus' or 
                            $k eq 'device' or
                            $k eq 'slot' or
                            $k eq 'function'){
                    $DH{'source'}{'address'}{"$k"} = $H->{"$k"};
                } else {
                    $DH{"$k"} = $H->{"$k"};
                }
            }
            push @{$D{'hostdev'}}, \%DH;
        }
    }
    
    return wantarray() ? %D : \%D; 
}

=item loadfromxml

load VirtMachine from xml domain

    my $VM = $VM->loadfromxml( $xml ); 

=cut

sub loadfromxml {
    my $self = shift;
    my ($xml) = @_;

    my %p = $self->xml_domain_parser($xml);
    
    my $disks = delete $p{'_disks_'};
    my $network = delete $p{'_network_'};
    my $filesystem = delete $p{'_filesystem_'};
    my $hostdev = delete $p{'_hostdev_'};

    # test if controller and channel exists, if so add its path.    
    if( defined $p{'_controller_'} ){
        my @controllers = @{ $p{'_controller_'} };
        delete $p{'_controller_'};

        $p{'Controllers'} = [@controllers];
    }
    
    if( defined $p{'_channel_'} ){
        my @channels = @{ $p{'_channel_'} };
        delete $p{'_channel_'};

        $p{'Channels'} = [@channels];
    }

    if( defined $p{'_serial_'} ){
        my @serials = @{ $p{'_serial_'} };
        delete $p{'_serial_'};

        $p{'Serials'} = [@serials];
    }

    # virtual machine create
    my $vm = ref($self) ? $self->setfields(%p) : $self->new(%p);

    # add disks
    if( $disks ){
        $vm->{'Disks'} = [];
        for my $D (@$disks){
            $vm->add_disk(%$D);
        }
    }
    # add network
    if( $network ){
        $vm->{'Network'} = [];

        # get Virtual Networks        
        my %VN = VirtAgentInterface->list_networks();
        # Hash bridge network
        my %BrNet = map { $_->{'bridge'} => $_ } values %VN; 

        for my $N (@$network){
            if( my $br = $N->{'bridge'} ){
                # if have network associated
                if( my $vname = $BrNet{"$br"}->{'name'} ){
                    $N->{'type'} = "network";
                    $N->{'name'} = $vname;
                }
            }
            $vm->add_network(%$N);
        }
    }

    # add filesystem
    if( $filesystem ){
        $vm->{'Filesystem'} = [];
        for my $F (@$filesystem){
            $vm->add_filesystem(%$F);
        }
    }

    # add hostdev
    if( $hostdev ){
        $vm->{'Hostdev'} = [];
        for my $F (@$hostdev){
            $vm->add_hostdev(%$F);
        }
    }

    return $vm;
}
sub xml_domain_parser {
    sub xml_domain_parser_get_attr {
        my ($self,$ch) = @_;
        my %A = ();
        if( my $attr = $ch->getAttributes() ){
            for(my $i=0;$i<$attr->getLength();$i++){
                my $n = $attr->item($i)->getNodeName();
                my $v = $attr->item($i)->getValue();
                $A{"$n"} = $v;
            }
        }
        return wantarray() ? %A : \%A;
    }

    my ($self,$xml) = @_;

    my $parser = new XML::DOM::Parser();
    my $doc = $parser->parse($xml);
    my $root = $doc->getDocumentElement();

    my %D = $self->xml_domain_parser_get_attr($root);

    for my $ch ($root->getChildNodes()){
        my $nname = $ch->getNodeName();
        if( $nname eq 'name' || 
            $nname eq 'uuid' || 
            $nname eq 'description' || 
            $nname eq 'bootloader' || 
            $nname eq 'on_poweroff' || 
            $nname eq 'on_reboot' || 
            $nname eq 'on_crash' 
            ){
            eval{ $D{"$nname"} = $ch->getFirstChild->toString(); };
            if( $@ ){ $D{"$nname"} = ""; }
        } elsif( $nname eq 'memory' || 
                    $nname eq 'currentMemory' ){
            eval{ $D{"$nname"} = $ch->getFirstChild->toString() * 1024; };   # to bytes
            if( $@ ){ $D{"$nname"} = ""; }
        } elsif( $nname eq 'vcpu' ){
            $D{"vcpu"} = $ch->getFirstChild->toString();
            eval{ $D{"vcpu"} = $ch->getFirstChild->toString(); };
            if( $@ ){ $D{"vcpu"} = ""; }
            if( my %A = $self->xml_domain_parser_get_attr($ch) ){
                if( defined $A{'cpuset'} ){
                    $D{'cpuset'} = $A{'cpuset'};
                }
            } 
        } elsif( $nname eq 'os' ){
            for my $cdev ($ch->getChildNodes()){
                my $tn = $cdev->getNodeName();
                if( $tn eq "kernel" ){
                    eval{ $D{"kernel"} = $cdev->getFirstChild->toString(); };
                    if( $@ ){ $D{"kernel"} = ""; }
                } elsif( $tn eq "initrd" ){
                    eval{ $D{"initrd"} = $cdev->getFirstChild->toString(); };
                    if( $@ ){ $D{"initrd"} = ""; }
                } elsif( $tn eq "cmdline" ){
                    eval{ $D{"cmdline"} = $cdev->getFirstChild->toString(); };
                    if( $@ ){ $D{"cmdline"} = ""; }
                } elsif( $tn eq "type" ){
                    eval{ $D{"os_type"} = $cdev->getFirstChild->toString(); };
                    if( $@ ){ $D{"os_type"} = ""; }
                    my %A = $self->xml_domain_parser_get_attr($cdev);
                    $D{'arch'} = $A{'arch'} if( $A{'arch'} );
                }
            }
        } elsif( $nname eq 'clock' ){
            $D{'clock'}{'offset'} = $self->xml_domain_parser_get_attr($ch)->{'offset'};
        } elsif( $nname eq 'features' ){
            for my $cdev ($ch->getChildNodes()){
                my $tn = $cdev->getNodeName();
                if( $tn ne '#text' ){
                    $D{'features'}{"$tn"} = 1;
                }
            }
        } elsif( $nname eq 'cpu' ){
            my %A = $self->xml_domain_parser_get_attr($ch);
            for my $ccpu ($ch->getChildNodes()){
                my $tc = $ccpu->getNodeName();
                if( $tc eq 'vendor' ){
                    eval{ $A{"vendor"} = $ccpu->getFirstChild->toString(); };
                } elsif( $tc eq 'model' ){
                    eval{ $A{"model"} = $ccpu->getFirstChild->toString(); };
                    my %Am = $self->xml_domain_parser_get_attr($ccpu);
                    $A{'model_fallback'} = $Am{'fallback'} if( $Am{'fallback'} );
                } elsif( $tc eq 'numa' ){
                    my @cells = ();
                    for my $ccell ($ccpu->getChildNode()){
                        my %Ac = $self->xml_domain_parser_get_attr($ccell);
                        push(@cells,\%Ac);
                    }
                    $A{'numa'} = [@cells] if( @cells );
                } elsif( $tc ne '#text' ){
                    my %Ae = $self->xml_domain_parser_get_attr($ccpu);
                    $A{"$tc"} = { %Ae };
                }
            }
            $D{'cpu'} = \%A;
        } elsif( $nname eq 'devices' ){
            for my $cdev ($ch->getChildNodes()){
                my $tn = $cdev->getNodeName();
                if( $tn eq "graphics" ){
                    my %A = $self->xml_domain_parser_get_attr($cdev);
                    if( $A{'type'} eq "vnc" ){
                        $D{"vnc_port"} = $A{'port'};
                        $D{"vnc_listen"} = $A{'listen'} if ( $A{'listen'} );
                        $D{"vnc_keymap"} = $A{'keymap'} if ( $A{'keymap'} );
                    }
                    # TODO support others
                } elsif( $tn eq "disk" ){
                    my %A = $self->xml_domain_parser_get_attr($cdev);
                    for my $cdd ($cdev->getChildNodes()){
                        my $td = $cdd->getNodeName();
                        if( $td eq "source" ){
                            my %S = $self->xml_domain_parser_get_attr($cdd);
                            $A{'path'} = $S{'dev'} || $S{'file'};
                            $A{'sourceaio'} = $S{'sourceaio'} if( $S{'sourceaio'} );
                        } elsif( $td eq "target" ){
                            my %T = $self->xml_domain_parser_get_attr($cdd);
                            $A{'target'} = $T{'dev'};
                            $A{'bus'} = $T{'bus'} if( $T{'bus'} );
                            # node
                            if( ( $A{'bus'} eq 'xen' ) &&
                                    ( $A{'target'} =~ m/^xvd\w$/ ) ){
                                $A{'node'} = 'xvd';
                            } elsif( ( $A{'bus'} eq 'ide' ) &&
                                    ( $A{'target'} =~ m/^hd\w$/ ) ){
                                $A{'node'} = 'hd';
                            } elsif( ( $A{'bus'} eq 'scsi' ) &&
                                    ( $A{'target'} =~ m/^sd\w$/ ) ){
                                $A{'node'} = 'sd';
                            } elsif( ( $A{'bus'} eq 'virtio' ) &&
                                    ( $A{'target'} =~ m/^vd\w$/ ) ){
                                $A{'node'} = 'vd';
                            } else {
                                ($A{'node'}) = ( $A{'target'} =~ m/^(\w+)\w$/ );
                            }
                        } elsif( $td eq "driver" ){
                            my %C = $self->xml_domain_parser_get_attr($cdd);
                            $A{'drivername'} = $C{'name'};
                            $A{'drivertype'} = $C{'type'} if( $C{'type'} );
                            $A{'drivercache'} = $C{'cache'} if( $C{'cache'} );
                            $A{'driverio'} = $C{'io'} if( $C{'io'} );
                        }
                    }
                    push(@{$D{'_disks_'}},\%A);
                } elsif( $tn eq "interface" ){
                    my %I = $self->xml_domain_parser_get_attr($cdev);
                    for my $cdi ($cdev->getChildNodes()){
                        my $ti = $cdi->getNodeName();
                        if( $ti eq 'mac' ){
                            $I{'macaddr'} = $self->xml_domain_parser_get_attr($cdi)->{'address'};
                        } elsif( $ti eq 'source' ){
                            my %A = $self->xml_domain_parser_get_attr($cdi);
                            $I{'name'} = $A{'network'} if( $A{'network'} );
                            $I{'bridge'} = $A{'bridge'} if( $A{'bridge'} );
                        } elsif( $ti eq 'script' ){
                            $I{'script'} = $self->xml_domain_parser_get_attr($cdi)->{'path'};
                        } elsif( $ti eq 'target' ){
                            $I{'target'} = $self->xml_domain_parser_get_attr($cdi)->{'dev'};
                        } elsif( $ti eq 'model' ){
                            $I{'model'} = $self->xml_domain_parser_get_attr($cdi)->{'type'};
                        }
                    }
                    push(@{$D{'_network_'}},\%I);
                } elsif( $tn eq 'filesystem' ){
                    my %F = $self->xml_domain_parser_get_attr($cdev);
                    for my $cdf ($cdev->getChildNodes()){
                        my $tf = $cdf->getNodeName();
                        if( $tf eq 'source' ){
                            my %A = $self->xml_domain_parser_get_attr($cdf);
                            $F{'file'} = $A{'file'} if( $A{'file'} );
                            $F{'dir'} = $A{'dir'} if( $A{'dir'} );
                            $F{'dev'} = $A{'dev'} if( $A{'dev'} );
                            $F{'name'} = $A{'name'} if( $A{'name'} );
                        } elsif( $tf eq 'target' ){
                            $F{'target'} = $self->xml_domain_parser_get_attr($cdf)->{'dir'};
                        }
                    }
                    push(@{$D{'_filesystem_'}},\%F);
                } elsif( $tn eq 'input' ){
                    my %A = $self->xml_domain_parser_get_attr($cdev);
                    if( $A{'type'} eq "mouse" ){
                        $D{"mouse_bus"} = $A{'bus'} || 'ps2';
                    } elsif( $A{'type'} eq "tablet" ){
                        $D{"tablet_bus"} = $A{'bus'} || 'usb';
                    }
                } elsif( $tn eq "hostdev" ){
                    my %H = $self->xml_domain_parser_get_attr($cdev);
                    for my $cdi ($cdev->getChildNodes()){
                        my $ti = $cdi->getNodeName();
                        if( $ti eq 'source' ){
                            for my $cds ($cdi->getChildNodes()){
                                my $ts = $cds->getNodeName();
                                if( $ts eq 'vendor' ){
                                    my %A = $self->xml_domain_parser_get_attr($cds);
                                    $H{'vendor'} = $A{'id'} if( $A{'id'} );
                                } elsif( $ts eq 'product' ){
                                    my %A = $self->xml_domain_parser_get_attr($cds);
                                    $H{'product'} = $A{'id'} if( $A{'id'} );
                                } elsif( $ts eq 'address' ){
                                    my %A = $self->xml_domain_parser_get_attr($cds);
                                    $H{'bus'} = $A{'bus'} if( $A{'bus'} );
                                    $H{'device'} = $A{'device'} if( $A{'device'} );
                                    $H{'slot'} = $A{'slot'} if( $A{'slot'} );
                                    $H{'function'} = $A{'function'} if( $A{'function'} );
                                }
                            }
                        } elsif( $ti eq 'boot' ){
                            my %A = $self->xml_domain_parser_get_attr($cdi);
                            $H{'boot'}{'order'} = $A{'order'} if( $A{'order'} );
                        } elsif( $ti eq 'rom' ){
                            my %A = $self->xml_domain_parser_get_attr($cdi);
                            $H{'rom'}{'bar'} = $A{'bar'} if( $A{'bar'} );
                            $H{'rom'}{'file'} = $A{'file'} if( $A{'file'} );
                        }
                    }
                    push(@{$D{'_hostdev_'}},\%H);
                } elsif( $tn eq "controller" ){
                    my %C = $self->xml_domain_parser_get_attr($cdev);
                    push(@{$D{'_controller_'}},\%C);
                } elsif( $tn eq "channel" ){
                    my %C = $self->xml_domain_parser_get_attr($cdev);
                    for my $cdc ($cdev->getChildNodes()){
                        my $ts = $cdc->getNodeName();
                        if( $ts ne '#text' ){
                            my %CC = $self->xml_domain_parser_get_attr($cdc);
                            $C{"$ts"} = { %CC };
                        }
                    }
                    push(@{$D{'_channel_'}},\%C);
                } elsif( $tn eq "serial" ){
                    my %C = $self->xml_domain_parser_get_attr($cdev);
                    for my $cdc ($cdev->getChildNodes()){
                        my $ts = $cdc->getNodeName();
                        if( $ts ne '#text' ){
                            my %CC = $self->xml_domain_parser_get_attr($cdc);
                            $C{"$ts"} = { %CC };
                        }
                    }
                    push(@{$D{'_serial_'}},\%C);
                }
            }
        }
        next;
        if( ref($ch) eq "XML::DOM::Text" ){
            my $v = $ch->toString();
            $v =~ s/^\s+$//;
            if( $v ){
                $D{'_content_'} = $v;
            }
        } else {
            my $name = $ch->getNodeName();
            $D{"$name"} = xml_domain_parser_rec($ch);

            if( my $attr = $ch->getAttributes() ){
                for(my $i=0;$i<$attr->getLength();$i++){
                    my $n = $attr->item($i)->getNodeName();
                    my $v = $attr->item($i)->getValue();
                    $D{"$name"}{"$n"} = $v;
                }
            }

            if( ( scalar( keys %{$D{"$name"}} ) == 1 ) && 
                    $D{"$name"}{"_content_"} ){
                $D{"$name"} = $D{"$name"}{"_content_"};
            }
        }
    }

    # Avoid memory leaks - cleanup circular references for garbage collection
    $doc->dispose;

    return wantarray() ? %D : \%D;
}

=item tohash

show VirtMachine as hash

    my $Hash = $VM->tohash();

=cut

sub tohash {
    my $self = shift;

    my %H = ();
    for my $k (keys %$self){
        next if( not defined $self->{"$k"} );

        if( $k eq 'Disks' ){
            my $ld = $self->{"$k"};
            # ignore
            # TODO late
            for my $D (@$ld){
                my $DH = $D->tohash();
                push(@{$H{'Disks'}},$DH);
            }
        } elsif( $k eq 'Network' ){
            my $ln = $self->{"$k"};
            # ignore
            # TODO late
            for my $N (@$ln){
                my $NH = $N->tohash();
                push(@{$H{'Network'}},$NH);
            }
        } else {
            $H{"$k"} = $self->{"$k"};
        }
    }
    return wantarray() ? %H : \%H;
}

sub isequal {
    my $self = shift;
    my ($VM,@l) = @_;
    @l = qw( name uuid ) if( !@l );

    return $self->SUPER::isequal( $VM, @l );
}

sub convert_alloc_val_tobytes {
    # convert to bytes
    my ($ignore, $val) = @_;
    if( $val > 100000000 ){
        # Assume bytes
        #return int($val / 1024.0 / 1024.0);
        return int($val);
    } elsif( $val > 100000 ){
        # Assume kilobytes
        #return int($val / 1024.0);
        return int($val * 1024);
    } elsif( $val < 32 ){
        # Assume GB
        #return int($val * 1024);
        return int($val * 1024 * 1024 * 1024);
    }
    # MB to bytes
    return int($val * 1024 * 1024);
}

sub ovf_import {
    my $env_disks_path_dir;
    my $env_ovf_url;

    sub ovf_get_disk_file {
        my ($disk_url,$disk_path,$fmt) = @_;

        plog "GET $disk_url to $disk_path";
        my $rc = LWP::Simple::getstore("$disk_url","$disk_path");
        if( is_error($rc) || !-e "$disk_path" ){
            my $E = retErr('_ERR_OVF_IMPORT_',"Error get disk ($disk_url status=$rc) ");
            return $E;
        }
        if( $fmt =~ m/vmdk/ ){
        # TODO convert to raw using qemu-img
        }
        return retOk('_OK_OVF_IMPORT_',"Get disk file with success.");
    }
    sub ovf_xml_parser {

        my ($xml,$url,$dir,$lDisks,$lNetwork) = @_;

        my $parser = new XML::DOM::Parser( Namespaces=>1 );

        my ($base_url) = ( $url =~ m/(\w+:\/\/([^\/]+\/)+).+\.ovf/ );

        my $doc = $parser->parse($xml);

        my $root = $doc->getDocumentElement();

        my ($file_refs,$disk_section,$network_section); # temp vars
        my ($disk_buses);

        my $have_disks = (ref($lDisks) eq 'HASH')? scalar(%$lDisks) : scalar(@$lDisks);
        my $have_networks = (ref($lNetwork) eq 'HASH')? scalar(%$lNetwork) : scalar(@$lNetwork);

        my %VM = ( '_network_'=>[], '_disks_'=>[] );
        my @LVS = ();

        my %LVInfo = VirtAgent::Disk->getlvs();

        for my $ch ($root->getChildNodes()){
            my $n = $ch->getNodeName();
            if( $n eq 'References' ){
                for my $ref_ch ($ch->getChildNodes()){
                    if( $ref_ch->getNodeName() eq 'File' ){
                        my $file_id = $ref_ch->getAttributeNode('id')->getValue();
                        my $path = $ref_ch->getAttributeNode('href')->getValue();
                        if( $file_id && $path ){
                            $file_refs->{"$file_id"} = $path;
                        }
                    }
                }
            } elsif( $n eq 'DiskSection' ){
                for my $disk_ch ($ch->getChildNodes()){
                    if( $disk_ch->getNodeName() eq 'Disk' ){
                        my $fmt = $disk_ch->getAttributeNode('format') ? $disk_ch->getAttributeNode('format')->getValue() : "raw";
                        my $disk_id = $disk_ch->getAttributeNode('diskId')->getValue();
                        my $file_ref = $disk_ch->getAttributeNode('fileRef')->getValue();
                        $disk_section->{"$disk_id"} = [ $file_ref, $fmt ];
                    }
                }
            } elsif( $n eq 'NetworkSection' ){
                for my $net_ch ($ch->getChildNodes()){
                    if( $net_ch->getNodeName() eq 'Network' ){
                        if( $net_ch->getAttributeNode('name') ){
                            my $net_name = $net_ch->getAttributeNode('name')->getValue();
                            $network_section->{"$net_name"} = 1;
                        }
                    }
                }
            } elsif( $n eq 'VirtualSystem' ){
                for my $vs_ch ($ch->getChildNodes()){
                    if( $vs_ch->getNodeName() eq 'Name' ){
                        $VM{'name'} = $vs_ch->getFirstChild()->toString();
                    } elsif( $vs_ch->getNodeName() eq 'OperatingSystemSection' ){
                        $VM{'os_id'} = $vs_ch->getAttributeNode('id') ? $vs_ch->getAttributeNode('id')->getValue() : "";
                        $VM{'os_version'} = $vs_ch->getAttributeNode('version') ? $vs_ch->getAttributeNode('version')->getValue() : "";
                        $VM{'os_type'} = $vs_ch->getAttributeNode('osType') ? $vs_ch->getAttributeNode('osType')->getValue() : "";
                    } elsif( $vs_ch->getNodeName() eq 'VirtualHardwareSection' ){
                        for my $dev_node ($vs_ch->getChildNodes()){
                            if( $dev_node->getNodeName() eq 'Item' ){
                                my $dev_type;
                                my ($item_ResourceType_node) = grep { $_->getNodeName() eq 'ResourceType' } $dev_node->getChildNodes();
                                if( $item_ResourceType_node ){
                                    $dev_type = $item_ResourceType_node->getFirstChild()->toString();
                                }

                                 # aux vars
                                my ($mem,$alloc_str);
                                my $net_model;
                                my $disk;

                                for my $item_node ($dev_node->getChildNodes()){
                                    if( $dev_type == DEVICE_CPU ){
                                        if( $item_node->getNodeName() eq 'VirtualQuantity' ){
                                            $VM{'vcpu'} += $item_node->getFirstChild()->toString();
                                        }
                                    } elsif( $dev_type == DEVICE_MEMORY ){
                                        if( $item_node->getNodeName() eq 'VirtualQuantity' ){
                                            $mem = $item_node->getFirstChild()->toString();
                                        } elsif( $item_node->getNodeName() eq 'AllocationUnits' ){
                                            $alloc_str = $item_node->getFirstChild()->toString();
                                        }
                                    } elsif( $dev_type == DEVICE_ETHERNET ){
                                        if( $item_node->getNodeName() eq 'ResourceSubType' ){
                                            $net_model = $item_node->getFirstChild()->toString();
                                        }
                                    } elsif( $dev_type == DEVICE_IDE_BUS ){
                                        if( $item_node->getNodeName() eq 'InstanceID' ){
                                            my $instance_id = $item_node->getFirstChild()->toString();
                                            $disk_buses->{"$instance_id"} = "ide";
                                        }
                                    } elsif( $dev_type == DEVICE_SCSI_BUS ){
                                        if( $item_node->getNodeName() eq 'InstanceID' ){
                                            my $instance_id = $item_node->getFirstChild()->toString();
                                            $disk_buses->{"$instance_id"} = "scsi";
                                        }
                                    } elsif( $dev_type == DEVICE_DISK ){
                                        if( $item_node->getNodeName() eq 'Parent' ){
                                            $disk->{'bus_id'} = $item_node->getFirstChild()->toString();
                                        } elsif( $item_node->getNodeName() eq 'HostResource' ){
                                            $disk->{'path'} = $item_node->getFirstChild()->toString();
                                        } elsif( $item_node->getNodeName() eq 'AddressOnParent' ){
                                            $disk->{'dev_num'} = int($item_node->getFirstChild()->toString());
                                        }
                                    }
                                }

                                if( ( $dev_type == DEVICE_MEMORY ) && $mem ){
                                    $VM{'memory'} = convert_alloc_val_tobytes($alloc_str,$mem);
                                }
                                
                                if( $dev_type == DEVICE_ETHERNET ){
                                    # get network extra params
                                    my $i = scalar(@{$VM{"_network_"}});
                                    my $P = $lNetwork->[$i] || {};
                                    if( !$have_networks || %$P ){
                                        push(@{$VM{'_network_'}}, { 'model'=>lc($net_model), %$P });
                                    }
                                }

                                if( ( $dev_type == DEVICE_DISK ) && $disk ){
                                    my $bus = ( $disk->{'bus_id'} and $disk_buses->{"$disk->{'bus_id'}"} ) || "ide";
                                    my $fmt = "raw";
                                    if( $disk->{'path'} ){
                                        my $ref;
                                        my $disk_ref;
                                        if( $disk->{'path'} =~ m/\/disk\/(\w+)/ ){
                                            $disk_ref = $1;
                                            if( $disk_section->{"$disk_ref"} ){
                                                ($ref,$fmt) = @{$disk_section->{"$disk_ref"}};
                                            }
                                        } elsif( $disk->{'path'} =~ m/\/file\/(\w+)/ ){
                                            $ref = $1;
                                        }

                                        plog "ref=$ref path=$disk->{'path'}";
                                        if( $ref ){
                                            my $path = $file_refs->{"$ref"};
                                            if( $path ){

                                                # get disks extra params
                                                my $i = scalar(@{$VM{"_disks_"}});
                                                my $P = ( (ref($lDisks) eq 'HASH') ? $lDisks->{"$disk_ref"} : $lDisks->[$i] ) || {};

                                                plog "have_disks=$have_disks";
                                                if( !$have_disks || %$P ){

                                                    # need get disk file
                                                    my $disk_dir = delete $P->{'outdir'} || $dir;  # dir for write disk file
                                                    my $disk_url = "$base_url$path";
                                                    my $disk_path = "$disk_dir/$path";
                                                    my $nooverwrite = delete $P->{'nooverwrite'};
                                                    if( !$nooverwrite || ! -e "$disk_path" ){

                                                        # TODO add lock

                                                        if( $P->{'lv'} ){
                                                            plog( "ovf_import lv=$P->{'lv'} size=$P->{'size'}" );
                                                            my $vg = delete $P->{'vg'};
                                                            my $lv = delete $P->{'lv'};
                                                            my @lvp = split(/\//,$lv);
                                                            my $lvn = pop @lvp;

                                                            my $size = delete $P->{'size'};

                                                            my $lvdevice;
                                                            my $LV = $LVInfo{"$lvn"};
                                                            if( !$LV ){# create it

                                                                my $E = VirtAgent::Disk->lvcreate($lv,$vg,$size,$fmt);
                                                                if( !isError($E) && $E->{'_obj_'} ){
                                                                    $LV = $E->{'_obj_'};
                                                                } else {
                                                                    unlink $disk_path;  # remove disk file
                                                                    return $E;
                                                                }
                                                            }
                                                            $lvdevice = $LV->{'lvdevice'} || $LV->{'device'};

                                                            if( $lvdevice ){
                                                                push(@LVS,$LV);

                                                                unlink $disk_path;

                                                                # set disk path with lv device path
                                                                $disk_path = $lvdevice;

                                                                # get disk file from OVF directly to lv device
                                                                my $E = &ovf_get_disk_file($disk_url,$disk_path,$fmt);
                                                                if( isError($E) ){
                                                                    return $E;
                                                                }

                                                            } else {
                                                                plog "logical volume '$lv' invalid!";
                                                                my $E = retErr('_ERR_OVF_IMPORT_',"logical volume '$lv' invalid!");
                                                                return $E;
                                                                next;
                                                            }
                                                        } else {
                                                            # get disk file from OVF
                                                            my $E = &ovf_get_disk_file($disk_url,$disk_path,$fmt);
                                                            if( isError($E) ){
                                                                unlink $disk_path;  # remove disk file
                                                                return $E;
                                                            }

                                                        }
                                                    } else {
                                                        plog "$disk_path already there!";
                                                    }

                                                    plog("ovf_import _disks_ 'path'=>$disk_path, 'format'=>$fmt, 'bus'=>$bus");
                                                    push(@{$VM{"_disks_"}}, { 'path'=>$disk_path, 'format'=>$fmt, 'bus'=>$bus, %$P });
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    } elsif( $vs_ch->getNodeName() eq 'AnnotationSection' ){
                        for my $an_ch ($vs_ch->getChildNodes()){
                            if( $an_ch->getNodeName() eq 'Annotation' ){
                                $VM{'description'} = $an_ch->getFirstChild()->toString();
                            }
                        }
                    }
                }
            }   # else ignore
        }

        # Avoid memory leaks - cleanup circular references for garbage collection
        $doc->dispose;

        # get extra Network info
        if( my $nn = scalar(@$lNetwork) ){
            my $i = scalar(@{$VM{"_network_"}});
            for( ;$i<$nn; $i++ ){
                my $P = $lNetwork->[$i];
                push(@{$VM{'_network_'}}, $P );
            }
        }

        # get extra Disks
        if( ref($lDisks) eq 'ARRAY' ){
            if( my $nd = scalar(@$lDisks) ){
                my $i = scalar(@{$VM{"_disks_"}});
                for( ;$i<$nd;$i++ ){
                    my $P = $lDisks->[$i];
                    push(@{$VM{"_disks_"}}, $P );
                }
            }
        }
        return (\%VM,\@LVS);
    }
    my $self = shift;
    my (%arg) = @_;

    $env_ovf_url = delete $arg{'ovf_url'};

    # get xml 
    my $xml = LWP::Simple::get($env_ovf_url);
    if( $xml ){
        $env_disks_path_dir = delete $arg{'disks_path_dir'};

        my ($lDisks,$lNetwork) = (delete $arg{'Disks'}, delete $arg{'Networks'});
        my ($VM,$LVS) = ovf_xml_parser($xml,$env_ovf_url,$env_disks_path_dir,$lDisks || [] ,$lNetwork || []);
        if( isError($VM) ){
            return $VM;
        }

        my $disks = delete $VM->{'_disks_'};
        my $network = delete $VM->{'_network_'};
        my $filesystem = delete $VM->{'_filesystem_'};
        my $hostdev = delete $VM->{'_hostdev_'};

        # virtual machine create
        my $vm = ref($self) ? $self->setfields(%$VM,%arg) : $self->new(%$VM,%arg);

        # add disks
        if( $disks ){
            $vm->{'Disks'} = [];
            for my $D (@$disks){
                $vm->add_disk(%$D);
            }
        }
        # add network
        if( $network ){
            $vm->{'Network'} = [];

            # get Virtual Networks        
            my %VN = VirtAgentInterface->list_networks();
            # Hash bridge network
            my %BrNet = map { $_->{'bridge'} => $_ } values %VN; 

            for my $N (@$network){
                if( my $br = $N->{'bridge'} ){
                    # if have network associated
                    if( my $vname = $BrNet{"$br"}->{'name'} ){
                        $N->{'type'} = "network";
                        $N->{'name'} = $vname;
                    }
                } elsif( $N->{'network'} ){
                    $N->{'type'} = 'network';
                    $N->{'name'} = delete $N->{'network'};
                }
                $vm->add_network(%$N);
            }
        }

        # add filesystem
        if( $filesystem ){
            $vm->{'Filesystem'} = [];
            for my $F (@$filesystem){
                $vm->add_filesystem(%$F);
            }
        }

        # add hostdev
        if( $hostdev ){
            $vm->{'Hostdev'} = [];
            for my $F (@$hostdev){
                $vm->add_hostdev(%$F);
            }
        }

        return wantarray() ? ($vm,$LVS) : $vm;
    }
    my $E = retErr('_ERR_OVF_IMPORT_','Error import VM from OVF');
    return $E;
}

sub convert_alloc_val_tomegas {
    # convert to bytes
    my ($ignore, $val) = @_;
    if( $val > 100000000 ){
        # Assume bytes
        return int($val / 1024.0 / 1024.0);
    } elsif( $val > 100000 ){
        # Assume kilobytes
        return int($val / 1024.0);
    } elsif( $val < 32 ){
        # Assume GB
        return int($val * 1024);
    }
    # MB to bytes
    return int($val);
}

sub ovf_export {

    sub gen_ovf_xml {
        sub genFiles {
            my (%p) = @_;

            my $X = XML::Generator->new(':conformance');
            my @Files = ();
            
            # extract disks
            my $Disks = $p{'Disks'};
            if( $Disks ){
                my $c = 1;
                my $tokfiledisk = "filedisk";
                for my $D (@$Disks){

                    next if( $D->get_device() eq 'cdrom' ); # ignore CDROM

                    my %Attr = ();

                    my $fileid = $Attr{'ovf:id'} = "${tokfiledisk}$c";
                    $Attr{'ovf:href'} = $D->get_filename();

                    # TODO calc size
                    $Attr{'ovf:size'} = $D->get_size();

                    # TODO other attr
                    # $Attr{'ovf:compression'}
                    # $Attr{'ovf:chunkSize'}

                    push(@Files, $X->File( \%Attr ) );
                    $c++;
                }
            }

            # TODO other stuff

            return @Files;
        }
        sub genReferences {
            my (%p) = @_;

            my $X = XML::Generator->new(':conformance');
            return $X->References( genFiles( %p ) );
        }
        sub genDisks {
            my (%p) = @_;

            my $X = XML::Generator->new(':conformance');

            my @Disks = ();

            my $Disks = $p{'Disks'};
            if( $Disks ){
                my $c = 1;
                my $tokvmdisk = "vmdisk";
                my $tokfiledisk = "filedisk";
                for my $D (@$Disks){
                    next if( $D->get_device() eq 'cdrom' ); # ignore CDROM

                    my %Attr = ();

                    my $fileref = "${tokfiledisk}$c";
                    $Attr{'ovf:diskId'} = "${tokvmdisk}$c";
                    $Attr{'ovf:fileRef'} = "$fileref";

                    $Attr{'ovf:capacity'} = $D->get_size();
                    $Attr{'ovf:populateSize'} = $D->get_usagesize();
                    $Attr{'ovf:format'} = $D->get_format() || "raw";
                    $Attr{'ovf:capacityAllocationUnits'} = 'byte';

                    push(@Disks, $X->Disk( \%Attr ) );
                    $c++;
                }
            }

            return @Disks;
        }
        sub genDiskSection {
            my (%p) = @_;

            my $X = XML::Generator->new(':conformance');
            return $X->DiskSection( $X->Info('Describes the set of virtual disks'),
                                        genDisks( %p ) );
        }
        sub genNetworks {
            my (%p) = @_;

            my $X = XML::Generator->new(':conformance');

            my @Networks = ();
            my $Network = $p{'Network'};
            if( $Network ){
                my $c = 1;
                my $toknet = "vnet";
                for my $N (@$Network){
                    my %Attr = ();
                    if( $N->{'type'} eq 'bridge' ){
                        $Attr{'ovf:name'} = $N->{'bridge'};
                    } elsif( $N->{'type'} eq 'network' ){
                        $Attr{'ovf:name'} = $N->{'name'};
                    }

                    $Attr{'ovf:id'} = "${toknet}$c";
                    push(@Networks, $X->Network( \%Attr ));
                    $c++;
                }
            }
            return @Networks;
        }
        sub genNetworkSection {
            my (%p) = @_;

            my $X = XML::Generator->new(':conformance');
            return $X->NetworkSection( $X->Info('List of logical networks used in the package'),
                                        genNetworks( %p ) );
        }
        sub genVirtualHardwareSection {
            my (%p) = @_;

            my $X = XML::Generator->new(':conformance');
            my %Attr = ();

            # $Attr{'ovf:id'}
            # $Attr{'ovf:transport'}

            my @VirtualHardwareSection = ();
            
            my $rasdAllocationUnits = 'rasd:AllocationUnits';
            my $rasdCaption = 'rasd:Caption';
            my $rasdDescription = 'rasd:Description';
            my $rasdElementName = 'rasd:ElementName';
            my $rasdInstanceID = 'rasd:InstanceID';
            my $rasdResourceType = 'rasd:ResourceType';
            my $rasdVirtualQuantity = 'rasd:VirtualQuantity';

            my $id = 1;

            # CPU
            push( @VirtualHardwareSection, $X->Item( $X->$rasdCaption("$p{'vcpu'} virtual CPU"),
                                                        $X->$rasdDescription("Number of virtual CPUs"),
                                                        $X->$rasdElementName("virtual CPU"),
                                                        $X->$rasdInstanceID(1),
                                                        $X->$rasdResourceType(DEVICE_CPU),
                                                        $X->$rasdVirtualQuantity($p{'vcpu'}) ));
            $id++;

            my $alloc_str = "MegaBytes";
            my $mega_m = convert_alloc_val_tomegas($alloc_str,$p{'memory'});
            # Memory
            push( @VirtualHardwareSection, $X->Item( $X->$rasdAllocationUnits("$alloc_str"),
                                                        $X->$rasdCaption("$mega_m of memory"),
                                                        $X->$rasdDescription("Memory Size"),
                                                        $X->$rasdElementName("Memory"),
                                                        $X->$rasdInstanceID(2),
                                                        $X->$rasdResourceType(DEVICE_MEMORY),
                                                        $X->$rasdVirtualQuantity($mega_m) ));
            $id++;

            # TODO network, scsi, disk...

            # Network
            my $Network = $p{'Network'};
            if( $Network ){
                my $c = 1;
                my $toknet = "vnet";
                for my $N (@$Network){
                    my @Item = ();
                    
                    my $rasdAutomaticAllocation = 'rasd:AutomaticAllocation';
                    my $rasdCaption = 'rasd:Caption';
                    my $rasdAddress = 'rasd:Address';
                    my $rasdConnection = 'rasd:Connection';
                    my $rasdDescription = 'rasd:Description';
                    my $rasdElementName = 'rasd:ElementName';
                    my $rasdInstanceID = 'rasd:InstanceID';
                    my $rasdResourceType = 'rasd:ResourceType';
                    my $rasdResourceSubType = 'rasd:ResourceSubType';

                    push(@Item, $X->$rasdAutomaticAllocation('true'));
                    push(@Item, $X->$rasdCaption("Network adapter"));
                    push(@Item, $X->$rasdInstanceID($id));
                    push(@Item, $X->$rasdResourceType(DEVICE_ETHERNET));

                    # model network as ResourceSubType
                    if( $N->{'model'} ){
                        push(@Item, $X->$rasdResourceSubType($N->{'model'}));
                    }

                    # mac-address as Address
                    push(@Item, $X->$rasdAddress($N->{'macaddr'}));

                    my $name;
                    if( $N->{'type'} eq 'bridge' ){
                        $name = $N->{'bridge'};
                    } elsif( $N->{'type'} eq 'network' ){
                        $name = $N->{'network'} || $N->{'name'} || $N->{'bridge'};
                    }
                    push(@Item, $X->$rasdConnection("$name"));
                    push(@Item, $X->$rasdDescription("$name ?"));
                    push(@Item, $X->$rasdElementName("Ethernet adapter"));

                    push(@VirtualHardwareSection, $X->Item( @Item ));
                    $c++;
                    $id++;
                }
            }

            # disk
            my $Disks = $p{'Disks'};
            if( $Disks ){

                # get bus disk devices
                my %BusInstance = ();

                # get disks
                my $c = 1;
                my $tokvmdisk = "vmdisk";
                for my $D (@$Disks){

                    next if( $D->get_device() eq 'cdrom' ); # ignore CDROM

                    my $rasdAutomaticAllocation = 'rasd:AutomaticAllocation';
                    my $rasdCaption = 'rasd:Caption';
                    my $rasdDescription = 'rasd:Description';
                    my $rasdElementName = 'rasd:ElementName';
                    my $rasdHostResource = 'rasd:HostResource';
                    my $rasdInstanceID = 'rasd:InstanceID';
                    my $rasdResourceSubType = 'rasd:ResourceSubType';
                    my $rasdResourceType = 'rasd:ResourceType';
                    my $rasdParent = 'rasd:Parent';

                    my $bus = $D->{'bus'};

                    my $parent = $BusInstance{"$bus"};
                    if( !$parent ){
                        my @Item = ();

                        if( $bus eq 'scsi' ){
                            $bus = 'scsi';
                            push(@Item, $X->$rasdCaption("SCSI Controller"));
                            push(@Item, $X->$rasdDescription("SCSI Controller"));
                            push(@Item, $X->$rasdElementName("SCSI Controller"));
                            push(@Item, $X->$rasdInstanceID($id));
                            push(@Item, $X->$rasdResourceType(DEVICE_SCSI_BUS));
                        } else {
                            $bus = 'ide';
                            push(@Item, $X->$rasdCaption("IDE Controller"));
                            push(@Item, $X->$rasdDescription("IDE Controller"));
                            push(@Item, $X->$rasdElementName("IDE Controller"));
                            push(@Item, $X->$rasdInstanceID($id));
                            push(@Item, $X->$rasdResourceType(DEVICE_IDE_BUS));
                        }
                        # TODO other controllers
                        push(@VirtualHardwareSection, $X->Item( @Item ));
                        $parent = $BusInstance{"$bus"} = $id;
                        $id++;
                    }

                    my @Item = ();

                    push(@Item, $X->$rasdCaption("Harddisk $c"));
                    push(@Item, $X->$rasdDescription("HD"));
                    push(@Item, $X->$rasdElementName("Hard Disk"));
                    push(@Item, $X->$rasdHostResource("ovf:/disk/${tokvmdisk}$c"));
                    push(@Item, $X->$rasdInstanceID($id));
                    push(@Item, $X->$rasdParent($parent));
                    push(@Item, $X->$rasdResourceType(DEVICE_DISK));

                    push(@VirtualHardwareSection, $X->Item( @Item ));
                    $c++;
                    $id++;
                }
            }

            my $vssdElementName = 'vssd:ElementName';
            my $vssdInstanceID = 'vssd:InstanceID';
            my $vssdVirtualSystemType = 'vssd:VirtualSystemType';

            return $X->VirtualHardwareSection( %Attr, $X->Info('Virtual Hardware Requirements'),
                                                    $X->System( $X->$vssdElementName('libvirt'),
                                                                    $X->$vssdInstanceID(1),
                                                                    $X->$vssdVirtualSystemType($p{'type'}) ),
                                                    @VirtualHardwareSection );
        }
        sub genVirtualSystem {
            my (%p) = @_;

            my $X = XML::Generator->new(':conformance');
            my %Attr = ();
            return $X->VirtualSystem( %Attr, $X->Info('A virtual machine'),
                                                        $X->Name($p{'name'}),
                                                        genVirtualHardwareSection(%p) );
        }
        sub genOthersSection {
        }
        sub genEnvelope {
            my (%p) = @_;

            my $xml;
            my $X = XML::Generator->new(':conformance');
            eval {
                    $xml = $X->Envelope( {   
                                    'xmlns:xsi'=>'http://www.w3.org/2001/XMLSchema-instance',
                                    'xmlns:ovf'=>'http://schemas.dmtf.org/ovf/envelope/1',
                                    'xmlns:ovfenv'=>'http://schemas.dmtf.org/ovf/environment/1',
                                    'xmlns:ovfstr'=>'http://schema.dmtf.org/ovf/strings/1',
                                    'xmlns:vssd'=>'http://schemas.dmtf.org/wbem/wscim/1/cim-schema/2/CIM_VirtualSystemSettingData',
                                    'xmlns:rasd'=>'http://schemas.dmtf.org/wbem/wscim/1/cim-schema/2/CIM_ResourceAllocationSettingData',
                                    'xmlns:cim'=>'http://schemas.dmtf.org/wbem/wscim/1/common',
                                    'ovf:version'=>$p{'version'},
                                    'xml:lang'=>$p{'lang'},
#                                    'xsi:schemaLocation'=>'http://schemas.dmtf.org/ovf/envelope/1 ../ovf-envelope.xsd',
                                    'xmlns'=>'http://schemas.dmtf.org/ovf/envelope/1' },
                                    genReferences(%p),
                                    genDiskSection(%p),
                                    genNetworkSection(%p),
                                    genVirtualSystem(%p),
                                    genOthersSection(%p) );
            };
            if( $@ ){
                my $msg = $@;
                my $E = retErr('_ERR_OVF_EXPORT_',$msg);
                return $E;
            }
            return $xml;
        }

        my (%p) = @_;

        my $xml = sprintf('%s', genEnvelope( %p ) ); 
        return $xml;
    }

    my $self = shift;
    my (%p) = @_;

    my $name = $self->get_name();

    my $export_path_dir = $p{'export_path_dir'};
    my $ovf_file = $p{'export_ovf_file'};
    if( !$ovf_file ){
        $ovf_file = "$export_path_dir";
        $ovf_file .= "/" if( $ovf_file !~ m/\/$/ );
        $ovf_file .= "${name}.ovf";
        $p{'export_ovf_file'} = $ovf_file;
    }

    # write to file
    my $E = my $xml = gen_ovf_xml( %$self, %p );
    if( !isError($E) ){
        $xml = &xml_header . $xml;
=comt
        open(F,">$ovf_file");
        print F $xml;
        close(F);
=cut

        # return hash
        my %F = ( 'ovf_file'=>$ovf_file, 'xml'=>$xml, 'Disks'=>$self->get_Disks() );
        return wantarray() ? %F : \%F;
    }
    return wantarray() ? %$E : $E;
}

sub xml_header {
    return '<?xml version="1.0"?>'."\n";
}
1;

=back

=head2 VirtDisk

=over 4

=cut

package VirtDisk;

use strict;

BEGIN {
    # this is the worst damned warning ever, so SHUT UP ALREADY!
    $SIG{__WARN__} = sub { warn @_ unless $_[0] =~ /Use of uninitialized value/ };

    use vars qw($VERSION @ISA @EXPORT @EXPORT_OK %EXPORT_TAGS $CRLF $AUTOLOAD);
    $VERSION = '0.0.1';
    @ISA = qw( VirtObjects );
    @EXPORT = qw( );
}

use ETVA::Utils;
use VirtAgent;

use Cwd qw(abs_path);

=item new

    my $VD = VirtDisk->new( path=>$path, size=>$s, target=>$t );

=cut

sub new {
    my $self = shift;
    my %p = @_;
    unless( ref $self ){
        my $class = ref($self) || $self;

        my %D = %p;

        my $path = $D{'path'} = $p{'path'};
        $path = abs_path($path) if( -l "$path" );   # get real path if link 
        $D{'size'} = $p{'size'};
        $D{'device'} = $p{'device'} || "disk";
        $D{'drivername'} = $p{'drivername'};
        $D{'drivertype'} = $p{'drivertype'} if( $p{'drivertype'} );
        $D{'drivercache'} = $p{'drivercache'} if( $p{'drivercache'} );
        $D{'driverio'} = $p{'driverio'} if( $p{'driverio'} );
        $D{'sourceaio'} = $p{'sourceaio'} if( $p{'sourceaio'} );
        $D{'target'} = $p{'target'};
        $D{'readonly'} = $p{'readonly'} ? 1:0;

        $D{'type'} = ( -b "$path" )? "block": "file";
        
        $self = bless {%D} => $class;

        if( !$self->get_target() ){
            my $ni = defined($p{'ni'}) ? $p{'ni'} : $p{'i'};   # node index
            $self->set_target( $self->defaulttarget($ni) );
        }
    }
    return $self;
}

sub defaulttarget {
    my $self = shift;
    my ($i) = @_;

    my $disknode = $self->{'node'} || "xvd";
    my $target = sprintf('%s%c',$disknode,ord("a")+$i);

    return $target;
}

sub toxml {
    my $self = shift;

    my ($dd) = VirtAgent->get_diskdevice_xml( $self->todevice() );
    return wantarray() ? ($dd) : sprintf('%s',$dd);
}

sub todevice {
    my $self = shift;

    my $typeattr = ( $self->{'type'} eq 'block' ) ? "dev" : "file";

    my %D = ( 
                'readonly' => $self->{'readonly'},
                'type' => $self->{'type'},
                'device' => $self->{'device'},
                'source' => { "$typeattr" => $self->{'path'} },
                );

    $D{'source'}{'aio'} = $self->{'sourceaio'} if( $self->{'sourceaio'} );

    if( $self->{'drivername'} ){
        $D{'driver'} = { 'name' => $self->{'drivername'} };

        $D{'driver'}{'type'} = $self->{'drivertype'} if( $self->{'drivertype'} );
        $D{'driver'}{'cache'} = $self->{'drivercache'} if( $self->{'drivercache'} );
        $D{'driver'}{'io'} = $self->{'driverio'} if( $self->{'driverio'} );
    }
    if( $self->{'target'} ){
        $D{'target'} = { 'dev' => $self->{'target'} };
        $D{'target'}{'bus'} = $self->{'bus'} if( $self->{'bus'} );
    }
    return wantarray() ? %D : \%D;
}

sub initialize {
    my $self = shift;
    
    if( ! -e "$self->{'path'}" ){
        my $size = str2size($self->{'size'});
        if( $size ){
            # determine block-size and block-count
            my $bs = 1;
            my $c = $size;
            while(int($c/1024)){
                $c = int($c / 1024);
                $bs = $bs * 1024;
            }

            # create disk file with zeros
            my ($e,$m) = cmd_exec("/bin/dd if=/dev/zero of=$self->{'path'} bs=$bs count=$c");    
            unless( $e == 0 ){
                return retErr('_ERROR_VM_DISK_INITIALIZE_', " Error initialize disk: " . $m);
            }
            return retOk('_OK_VM_DISK_INITIALIZE_',"Disk initialized successfully.");
        }
    } else {
        return retErr('_ERROR_VM_DISK_INITIALIZE_',"Disk already initialized.");
    }
}

sub isequal {
    my $self = shift;
    my ($VD,@l) = @_;
    @l = qw( target path ) if( !@l );

    return $self->SUPER::isequal( $VD, @l );
}

sub get_filename {
    my $self = shift;

    if( !$self->{'_filename'} ){
        my $path = $self->get_path();

        my @lpath = split(/\//,$path);
        my $fname = pop(@lpath);
        $fname .= ".img" if( $fname !~ m/\.\w+$/ ); # append ext

        $self->{'_filename'} = $fname;
    }
    return $self->{'_filename'};
}

sub get_size {
    my $self = shift;
    if( !$self->{'_size'} ){
        my $path = $self->get_path();
        $self->{'_size'} = VirtAgent::Disk::get_disk_size($path);
    }
    return $self->{'_size'};
}
sub get_usagesize {
    my $self = shift;
    if( !$self->{'_usagesize'} ){
        my $path = $self->get_path();
        $self->{'_usagesize'} = VirtAgent::Disk::get_disk_usagesize($path);
    }
    return $self->{'_usagesize'};
}

1;

=back

=head2 VirtNetwork

=over 4

=cut

package VirtNetwork;

use strict;

BEGIN {
    # this is the worst damned warning ever, so SHUT UP ALREADY!
    $SIG{__WARN__} = sub { warn @_ unless $_[0] =~ /Use of uninitialized value/ };

    use vars qw($VERSION @ISA @EXPORT @EXPORT_OK %EXPORT_TAGS $CRLF $AUTOLOAD);
    $VERSION = '0.0.1';
    @ISA = qw( VirtObjects );
    @EXPORT = qw( );
}

use ETVA::Utils;

use VirtAgent::Network;
use VirtAgentInterface;

=item new 

    my $VN = VirtNetwork( name=>$name, macaddr=>$maddr );

    my $VN = VirtNetwork( type=>"bridge", bridge=$br, macaddr=>$maddr );

=cut

sub new {
    my $self = shift;
    my %p = @_;
    unless( ref $self ){
        my $class = ref($self) || $self;

        my %N = %p;

        $N{'type'} = $p{'type'};
        if( !$N{'type'} ){
            ($N{'type'}) = VirtAgent::Network->defaultnetwork();
        }

        if( $N{'type'} eq "bridge" ){
            $N{'name'} = $N{'bridge'} = $p{'bridge'} || VirtAgent::Network->defaultbridge();
        } elsif( $N{'type'} eq "network" ){
            $N{'name'} = $p{'name'} || "default";
        }

        $N{'macaddr'} = $p{'macaddr'} || random_mac();

        $self = bless {%N} => $class;
    }
    return $self;
}

sub toxml {
    my $self = shift;

    my ($id) = VirtAgent->get_interfacedevice_xml( $self->todevice() );

    return wantarray() ? ($id) : sprintf('%s',$id);
}

sub todevice {
    my $self = shift;

    my %N = ( 'type' => $self->{'type'} );
    
    $N{'source'} = { 'bridge'  => $self->{'bridge'}  } if( $self->{'type'} eq "bridge" );

    # for type network try convert to bridge
    if( $self->{'type'} eq "network" ){

        # get Virtual Networks        
        my %VN = VirtAgentInterface->list_networks();
        
        my $n = $self->{'name'};
        if( $VN{"$n"} && ( my $br = $VN{"$n"}{"bridge"} ) ){

            # set type bridge
            $N{'type'} = "bridge";
            # and set the bridge
            $N{"source"}{"bridge"} = $br;
        }
    }

    $N{'target'} = { 'dev' => $self->{'target'} } if( $self->{'target'} );
    $N{'script'} = { 'path' => $self->{'script'} } if( $self->{'script'} );
    $N{'model'} = { 'type' => $self->{'model'} } if( $self->{'model'} );
    $N{'mac'} = { 'address' => $self->{'macaddr'} } if( $self->{'macaddr'} );

    return wantarray() ? %N : \%N;
}

sub isequal {
    my $self = shift;
    my ($VN,@l) = @_;
    @l = qw( macaddr name ) if( !@l );

    return $self->SUPER::isequal( $VN, @l );
}

1;

=back

=head2 VirtObjects

=over 4

=cut

package VirtObjects;

use strict;

BEGIN {
    # this is the worst damned warning ever, so SHUT UP ALREADY!
    $SIG{__WARN__} = sub { warn @_ unless $_[0] =~ /Use of uninitialized value/ };

    require Exporter;
    use vars qw($VERSION @ISA @EXPORT @EXPORT_OK %EXPORT_TAGS $CRLF $AUTOLOAD);
    $VERSION = '0.0.1';
    @ISA = qw( Exporter );
    @EXPORT = qw( );
}

=item AUTOLOAD

    my $val = $VO->get_field();

    $val = $VO->set_field( $val );

=cut

sub AUTOLOAD {
    my ($package,$method) = ( $AUTOLOAD =~ m/(.+)::([^:]+)$/ );

    my $a;
    if( ($a) = ($method =~ m/get_(\w+)/) ){
        $AUTOLOAD = sub {
                        my $self = shift;
                        return $self->{"$a"};
                    };
    } elsif( ($a) = ($method =~ m/set_(\w+)/) ){
        $AUTOLOAD = sub {
                        my $self = shift;
                        return $self->{"$a"} = shift;
                    };
    } elsif( ($a) = ($method =~ m/del_(\w+)/) ){
        $AUTOLOAD = sub {
                        my $self = shift;
                        delete $self->{"$a"};
                    };
    }
    if( $AUTOLOAD ){
        &$AUTOLOAD;
    }
}

=item setfields

set multiply fields

    $VO = $VO->setfields( f1=>$v1, f2=>$v2, ..., fn=>$vn );

=cut

sub setfields {
    my $self = shift;
    my %p = @_;
    for my $f ( keys %p ){
        $self->{"$f"} = $p{"$f"};
    }
    return $self;
}

sub tohash {
    my $self = shift;

    my %H = ();
    for my $k (keys %$self){
        next if( not defined $self->{"$k"} );

        $H{"$k"} = $self->{"$k"};
    }
    return wantarray() ? %H : \%H;
}

sub isequal {
    my $self = shift;
    my ($VO,@l) = @_;
    @l = keys %$self if( !@l );

    for my $k (@l){
        if( $self->{"$k"} ne $VO->{"$k"} ){
            return 0;
        }
        # TODO compare not scalar
    }
    return 1;
}

sub clone {
    my $self = shift;
    
    my $class = ref($self) || $self;
    my $clone = $class->new();
    if( ref($self) ){
        for my $k (keys %$self){
            if( !ref($self->{"$k"}) ){
                $clone->{"$k"} = $self->{"$k"};
            }
        }
    }
    return $clone;
}

sub DESTROY {
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

L<VirtAgent>

=cut

