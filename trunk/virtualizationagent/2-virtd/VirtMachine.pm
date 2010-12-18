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

use Utils;

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
    my $disk = VirtDisk->new(%p);
    push @{$self->{'Disks'}}, $disk;
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

=item get_network

    my $Network = $VM->get_network( i=>$i ); 

=cut

sub get_network {
    my $self = shift;
    my %p = @_;
    my $i = $p{'i'};
    my $macaddr = $p{'macaddr'};
    if( defined $i ){
        if( $i < scalar(@{$self->{'Network'}}) ){
            return $self->{'Network'}->[$i];
        }
    } elsif( defined $macaddr ){
        if( my $networks = $self->{'Network'} ){
            for my $VN (@$networks){
                if( $VN->get_macaddr() eq $macaddr ){
                    return $VN;
                }
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
    for my $f (qw( name uuid memory vcpu cpuset )){
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

    $D{'os'}{'pxe'} = $self->{'pxe'} if( $self->{'pxe'} );
    $D{'os'}{'install'} = $self->{'install'} if( $self->{'install'} );
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

    return $vm;
}
sub xml_domain_parser {
    use XML::DOM;

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
            $nname eq 'bootloader' || 
            $nname eq 'on_poweroff' || 
            $nname eq 'on_reboot' || 
            $nname eq 'on_crash' 
            ){
            $D{"$nname"} = $ch->getFirstChild->toString();
        } elsif( $nname eq 'memory' || 
                    $nname eq 'currentMemory' ){
            $D{"$nname"} = $ch->getFirstChild->toString() * 1024;   # to bytes
        } elsif( $nname eq 'vcpu' ){
            $D{"vcpu"} = $ch->getFirstChild->toString();
            if( my %A = $self->xml_domain_parser_get_attr($ch) ){
                if( defined $A{'cpuset'} ){
                    $D{'cpuset'} = $A{'cpuset'};
                }
            } 
        } elsif( $nname eq 'clock' ){
            $D{'clock'} = $self->xml_domain_parser_get_attr($ch)->{'offset'};
        } elsif( $nname eq 'devices' ){
            for my $cdev ($ch->getChildNodes()){
                my $tn = $cdev->getNodeName();
                if( $tn eq "graphics" ){
                    my %A = $self->xml_domain_parser_get_attr($cdev);
                    if( $A{'type'} eq "vnc" ){
                        $D{"vnc_port"} = $A{'port'};
                        $D{"vnc_listen"} = $A{'listen'} if ( $A{'listen'} );
                    }
                    # TODO support others
                } elsif( $tn eq "disk" ){
                    my %A = $self->xml_domain_parser_get_attr($cdev);
                    for my $cdd ($cdev->getChildNodes()){
                        my $td = $cdd->getNodeName();
                        if( $td eq "source" ){
                            my %S = $self->xml_domain_parser_get_attr($cdd);
                            $A{'path'} = $S{'dev'} || $S{'file'};
                        } elsif( $td eq "target" ){
                            my %T = $self->xml_domain_parser_get_attr($cdd);
                            $A{'target'} = $T{'dev'};
                            $A{'bus'} = $T{'bus'} if( $T{'bus'} );
                        } elsif( $td eq "driver" ){
                            my %C = $self->xml_domain_parser_get_attr($cdd);
                            $A{'drivername'} = $C{'name'};
                            $A{'drivertype'} = $C{'type'} if( $C{'type'} );
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

use Utils;
use VirtAgent;

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
        $D{'size'} = $p{'size'};
        $D{'device'} = $p{'device'} || "disk";
        $D{'drivername'} = $p{'drivername'};
        $D{'drivertype'} = $p{'drivertype'};
        $D{'target'} = $p{'target'};
        $D{'readonly'} = $p{'readonly'} ? 1:0;

        $D{'type'} = ( -b "$path" )? "block": "file";
        
        $self = bless {%D} => $class;

        if( !$self->get_target() ){
            $self->set_target( $self->defaulttarget($p{'i'}) );
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
    $D{'driver'} = { 'name' => $self->{'drivername'},
                        'type' => $self->{'drivertype'}
                    } if( $self->{'drivername'} );
    if( $self->{'target'} ){
        $D{'target'} = { 'dev' => $self->{'target'} };
        $D{'target'}{'bus'} = $self->{'bus'} if( $self->{'bus'} );
    }
    return wantarray() ? %D : \%D;
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

use Utils;

use VirtAgent::Network;

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
            $N{'bridge'} = $p{'bridge'} || VirtAgent::Network->defaultbridge();
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

    $N{'mac'} = { 'address' => $self->{'macaddr'} } if( $self->{'macaddr'} );

    return wantarray() ? %N : \%N;
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

    if( my ($a) = ($method =~ m/get_(\w+)/) ){
        $AUTOLOAD = sub {
                        my $self = shift;
                        return $self->{"$a"};
                    };
    } elsif( my ($a) = ($method =~ m/set_(\w+)/) ){
        $AUTOLOAD = sub {
                        my $self = shift;
                        return $self->{"$a"} = shift;
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

