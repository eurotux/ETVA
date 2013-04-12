#!/usr/bin/perl
# Copywrite Eurotux 2009
# 
# CMAR 2009/04/03 (cmar@eurotux.com)

=pod

=head1 NAME

VirtAgent - Perl module for virtualization management interacting with libvirt ( Sys::Virt )

=head1 SYNOPSIS

...

=head1 DESCRIPTION

...

=head1 METHODS

=over 4

=cut

package VirtAgent;

use strict;

BEGIN {
    # this is the worst damned warning ever, so SHUT UP ALREADY!
    $SIG{__WARN__} = sub { warn @_ unless $_[0] =~ /Use of uninitialized value/ };

    require Exporter;
    use vars qw($VERSION @ISA @EXPORT @EXPORT_OK %EXPORT_TAGS  $CRLF);
    $VERSION = '0.0.1';
    @ISA = qw( Exporter );
    @EXPORT = qw( getAttrs );
}

use ETVA::Utils;

use Sys::Virt;
use XML::Generator;
use XML::DOM;

use File::Copy;
use Data::Dumper;

use POSIX qw(strftime);

use version 0.77;

my $CONF;
my $UUID;
my $MAXMEM;         # max memory in bytes
my $MAXNCPU;
my %CPUInfo = ();
my %MEMInfo = ();
my %NodeInfo = ();

my $VMConnection;

my $node_arch;      # default node arch
my $have_kqemu;
my $have_kvm;
my $have_hvm;

sub new {
    my $self = shift;
    unless( ref $self ){
        my $class = ref($self) || $self;
        $self = bless {@_} => $class;
    }
    return $self;
}

=item getuuid/setuuid

get/set virtual agent uniq id

    my $uuid = VirtAgent->getuuid();
    VirtAgent->setuuid( $uuid );

=cut

# getuuid
#   get virtual agent uniq id 
#   args: empty
#   return uuid
sub getuuid {
    my $self = shift;

    return $UUID;
}
# setuuid
#   set agent uniq id
#   args: uuid
#   return: empty
sub setuuid {
    my $self = shift;
    my ($uuid) = @_;
    $UUID = $uuid;
    $self->setconfig( %$CONF, uuid=>$uuid );
}

sub setname {
    my $self = shift;
    my ($name) = @_;
    $self->setconfig( %$CONF, name=>$name );
}

# setconfig
#   set config hash
#   args: Hash { conf }
#   return: empty
sub setconfig {
    my $self = shift;
    my ($H) = my %conf = @_;
    $CONF = ref($H) ? $H : \%conf;
    $UUID = $CONF->{'uuid'};
    ETVA::Utils::set_conf($CONF->{'CFG_FILE'},%$CONF);
}
sub loadconf {
    $CONF = ETVA::Utils::get_conf();
    $UUID = $CONF->{'uuid'};
}
# loadmeminfo
#   load node memory info
#   args: empty
#   return: empty
sub loadmeminfo {
    open(M,"/proc/meminfo");
    while(<M>){
        chomp;
        my ($f,$v) = split(/:/,$_);
        my $cv = trim($v);
        my $cf = tokey($f);
        $MEMInfo{"$cf"} = $cv * 1024; # convert to bytes
    }
    close(M);
    $MAXMEM = $MEMInfo{"MemTotal"};
}
# loadcpuinfo
#   load node cpu info
#   args: empty
#   return: empty
sub loadcpuinfo {
    open(C,"/proc/cpuinfo");
    my @lCpus = ();
    my $C = {};
    while(<C>){
        chomp;
        if( $_ ){
            my ($f,$v) = split(/:/,$_);
            my $cf = tokey($f);
            $C->{"$cf"} = trim($v);
        } else {
            push(@lCpus,$C);
            $C = {};
        }
        
    }
    close(C);
    $CPUInfo{"CPUS"} = \@lCpus;
    $MAXNCPU = $CPUInfo{"num"} = scalar(@lCpus);
}
# loadsysinfo
#   load node system info: memory, cpu, ...
#   args: empty
#   return: empty
sub loadsysinfo {
    my $self = shift;
    my $force = shift || 0;

    if( $force || !$CONF ){ loadconf(); }
    if( $force || !%MEMInfo ){ loadmeminfo(); }
    if( $force || !%CPUInfo ){ loadcpuinfo(); }

    if( $VMConnection ){
        my $NodeInfo;
        eval {
            $NodeInfo = $VMConnection->get_node_info();
        };
        if( $@ ){
            plog( "VirtAgent hypervisor does not support get_node_info" );
        } elsif( $NodeInfo ){
            %NodeInfo = %$NodeInfo;
            my $old_MemTotal = $MEMInfo{'MemTotal'};
            my $old_MemFree = $MEMInfo{'MemFree'};
            $MAXMEM = $MEMInfo{'MemTotal'} = $NodeInfo->{'memory'} * 1024;
            my $MemFree;
            eval {
                $MemFree = $VMConnection->get_node_free_memory();
            };
            if( $@ ){
                plog( "VirtAgent hypervisor does not support get_node_free_memory" );
            } else {
                $MEMInfo{'MemFree'} =  $MEMInfo{'NodeFreeMemory'} = $MemFree;
            }
        }
    }
}

=item getsysinfo

get system info

    my $Hash = VirtAgent->getsysinfo( );

=cut

# getsysinfo
#   return system info
#   args: empty
#   return: Hash ( maxmem, maxncpu, Hash ( cpuinfo ), Hash ( meminfo ) );
sub getsysinfo {
    loadsysinfo();

    my %res = ( maxmem=>$MAXMEM, maxncpu=>$MAXNCPU, cpuinfo=>\%CPUInfo, meminfo=>\%MEMInfo, 'nodeinfo'=>\%NodeInfo );
    return wantarray() ? %res : \%res;
}

=item vmConnect

Connection to virtual machine monitor

    my $VMM = VirtAgent->vmConnect( address=>$addr, uri=>$uri, readonly=>0 );

=cut

sub vmConnect {
    my $self = shift;

    my %params = @_;

    # if not connected
    if( !$VMConnection ){
        my %virtparms = ();

        $virtparms{"address"} = $params{"address"} if( $params{"address"} );
        $virtparms{"uri"} = $params{"uri"} if( $params{"uri"} );
        $virtparms{"readonly"} = $params{"readonly"} if( $params{"readonly"} );

        # try create Sys::Virt
        eval {
            $VMConnection = new Sys::Virt(%virtparms);
        };
        if( $@ ){
            return retErr('_CON_VM_',"Can't connect to virtual machine: $@");
        }
    }
    return $VMConnection;
}

=item vmDisconnect

Disconnect virtual machine monitor

    VirtAgent->vmDisconnect();

=cut

sub vmDisconnect {
    my $self = shift;
    
    # delete connection
    return undef $VMConnection;
}

=item listDomains

list domains

    my $Hash = VirtAgent->listDomains();

=cut

sub listDomains {
    my $self = shift;

    my $vm = $self->vmConnect(@_);

    my @domains = $vm->list_domains();

    # list domains as Hash
    my @list = ();
    foreach my $dom (@domains) {
        my $di = domainInfo($dom);
        push @list, { domain => $di };
    }
    return { domains => \@list };
}

=item listDefDomains

List of defined Domains

    my $Hash = VirtAgent->listDefDomains( );

=cut

sub listDefDomains {
    my $self = shift;

    my $vm = $self->vmConnect(@_);

    my @domains = $vm->list_defined_domains();

    # list domains as Hash
    my @list = ();
    foreach my $dom (@domains) {
        my $di = domainInfo($dom);
        push @list, { domain => $di };
    }
    return { domains => \@list };
}

=item domainStats

list of statistics for each domain: cputime, memory, maxmemory ...

    my $List = VirtAgent->domainStats( );

=cut

sub domainStats {
    my $self = shift;

    my $vm = $self->vmConnect(@_);

    my @domains = ();
    my @a_domains = $vm->list_domains();
    push(@domains,@a_domains);
    my @d_domains = $vm->list_defined_domains();
    push(@domains,@d_domains);

    my @stats = ();
    my $node_info = $vm->get_node_info();
    my $node_maxmem = $node_info->{'memory'} || $MAXMEM; 
    my $node_maxcpus = $node_info->{'cpus'} || $MAXNCPU;
    for my $dom (@domains){
        
        my $name = $dom->get_name();

        my $info = {};
        eval {
            $info = $dom->get_info();
        };
        if( $@ ){
            plog("domainStats error get info of dom '$name': $@");
        }
        my $maxmem = $info->{'maxMem'};
        $maxmem = $node_maxmem if( !$maxmem  );
        my $state_id = $info->{'state'};
        my $state_str = "";
        if( $state_id == Sys::Virt::Domain::STATE_NOSTATE ){
            $state_str = "STATE_NOSTATE";
		} elsif( $state_id == Sys::Virt::Domain::STATE_RUNNING ){
			$state_str = "STATE_RUNNING";
		} elsif( $state_id == Sys::Virt::Domain::STATE_BLOCKED ){
			$state_str = "STATE_BLOCKED";
		} elsif( $state_id == Sys::Virt::Domain::STATE_PAUSED ){
			$state_str = "STATE_PAUSED";
		} elsif( $state_id == Sys::Virt::Domain::STATE_SHUTDOWN ){
			$state_str = "STATE_SHUTDOWN";
		} elsif( $state_id == Sys::Virt::Domain::STATE_SHUTOFF ){
			$state_str = "STATE_SHUTOFF";
		} elsif( $state_id == Sys::Virt::Domain::STATE_CRASHED ){
			$state_str = "STATE_CRASHED";
        }
        my $state = $state_str;
        if( $state_id == Sys::Virt::Domain::STATE_RUNNING ||
                $state_id == Sys::Virt::Domain::STATE_BLOCKED ||
                $state_id == Sys::Virt::Domain::STATE_NOSTATE ){
            $state = "running";
        } elsif( $state_id == Sys::Virt::Domain::STATE_PAUSED ){
            $state = "suspended";
        } elsif( $state_id == Sys::Virt::Domain::STATE_SHUTDOWN ){
            $state_str = "shuttingdown";
        } elsif( $state_id == Sys::Virt::Domain::STATE_SHUTOFF ){
            $state = "stop";
        } else {
            $state = "notrunning";
        }

        my $has_snapshots = 0;
        if( &haveSnapshotSupport() ){
            if( my $lsnaps = $self->domainListSnapshots($dom) ){
                $has_snapshots = 1 if( !isError($lsnaps) && @$lsnaps );
            }
        }
        push @stats, {
                        "id" => $dom->get_id(),
                        "name" => $name,
                        "uuid" => $dom->get_uuid_string(),
                        "cputime" => $info->{'cpuTime'},     # cpu time in nano secs 
                        "ncpus" => $info->{'nrVirtCpu'},     # number of cpus
                        "mem" => $info->{'memory'} * 1024,  # current mem in bytes
                        "maxmem" => $maxmem * 1024,     # max mem in bytes
                        "node_maxmem" => $node_maxmem * 1024,
                        "node_maxcpus" => $node_maxcpus,
                        "state"=>$state,
                        "state_str"=>$state_str,
                        "state_id"=>$state_id,
                        "has_snapshots"=>$has_snapshots,
                        "timestamp" => time()
                        };
    }

    return (wantarray())? @stats : \@stats;
}

sub domBlockStats {
    my $self = shift;
    my %p = @_;

    my $dom = $self->getDomain( %p );

    if( isError($dom) ){
        return wantarray() ? %$dom : $dom;
    } 

    my $dev = $p{'dev'};
    if( $dev ){
        my $stats;
        eval {
            $stats = $dom->block_stats( $dev );
        };
        if( $@ ){
            return retErr('_DOM_BLOCK_STATS_ERROR_',"Error get block stats: $@");
        }
        return wantarray() ? %$stats : $stats;
    } else {
        return retErr('_DOM_BLOCK_STATS_NO_DEV_',"No device specified!");
    }
}

sub domInterfaceStats {
    my $self = shift;
    my %p = @_;

    my $dom = $self->getDomain( %p );

    if( isError($dom) ){
        return wantarray() ? %$dom : $dom;
    } 

    my $if = $p{'if'};
    if( $if ){
        my $stats;
        eval {
            $stats = $dom->interface_stats( $if );
        };
        if( $@ ){
            return retErr('_DOM_INTERFACE_STATS_ERROR_',"Error get block stats: $@");
        }
        return wantarray() ? %$stats : $stats;
    } else {
        return retErr('_DOM_INTERFACE_STATS_NO_IF_',"No interface specified!");
    }
}

sub domMemoryStats {
    my $self = shift;
    my %p = @_;

    my $dom = $self->getDomain( %p );

    if( isError($dom) ){
        return wantarray() ? %$dom : $dom;
    } 

    my $flags = $p{'flags'} || 0;
    my $stats;
    eval {
        $stats = $dom->memory_stats( $flags );
    };
    if( $@ ){
        return retErr('_DOM_MEMORY_STATS_ERROR_',"Error get block stats: $@");
    }
    return wantarray() ? %$stats : $stats;
}

=item createDomain

create domain

    my $DomInfo = VirtAgent->createDomain( name=>$name, ... );

=cut

sub createDomain {
    my $self = shift;
    
    my $vm = $self->vmConnect(@_);

    my $xml = $self->genXMLDomain(@_);

    my $dom;
    # try create domain
    eval {
        $dom = $vm->create_domain($xml);
    };
    if( $@ ){
        return retErr('_CREATE_DOMAIN_',"Can't create domain: $@");
    }
    return retDomainInfo($dom);
}

=item defineDomain

define domain

    my $DomInfo = VirtAgent->defineDomain( name=>$name, ... );

=cut

sub defineDomain {
    my $self = shift;
    
    my $vm = $self->vmConnect(@_);

    my $xml = $self->genXMLDomain(@_);

    plog "defineDomain xml=$xml" if( &debug_level > 3 );
    my $dom;
    # try create domain
    eval {
        $dom = $vm->define_domain($xml);
    };
    if( $@ ){
        return retErr('_DEFINE_DOMAIN_',"Can't define domain: $@");
    }
    return retDomainInfo($dom);
}

=item getDomain

get domain

    my $dom = VirtAgent->getDomain( name=>$name );

    my $dom = VirtAgent->getDomain( uuid=>$uuid );

=cut

sub getDomain {
    my $self = shift;
    my (%p) = @_;

    my $vm = $self->vmConnect(@_);

    my $dom;

    if( $p{'uuid'} ){
        eval {
            $dom = $vm->get_domain_by_uuid($p{'uuid'});
        };
        if( $@ ){
            return retErr('_GET_DOMAIN_UUID_',"Invalid domain uuid: $@");
        }
    } elsif( $p{'name'} ){
        eval {
            $dom = $vm->get_domain_by_name($p{'name'});
        };
        if( $@ ){
            return retErr('_GET_DOMAIN_NAME_',"Invalid domain name: $@");
        }
    } else {
        return retErr('_GET_NODOMAIN_',"No domain uuid/name specified");
    }

    if( wantarray() ){
        return ($dom,$vm);
    } else {
        return $dom;
    }
}

=item getDomainInfo

get domain info

    my $DomInfo = VirtAgent->getDomainInfo( name=>$name );

=cut

sub getDomainInfo {
    my $self = shift;
    my %params = @_;
    my $vm = $self->vmConnect(@_);
    my $name = $params{"name"};

    # get domain
    my $dom = $self->getDomain(%params);

    if( isError($dom) ){
        return wantarray() ? %$dom : $dom;
    } 

    return retDomainInfo($dom);
}
sub isRunning {
    my ($dom) = @_;

    my $info = $dom->get_info();

    if( $info->{'state'} eq Sys::Virt::Domain::STATE_RUNNING ||
        $info->{'state'} eq Sys::Virt::Domain::STATE_BLOCKED ){
        return 1;
    }
    return 0;
}
sub isSuspended {
    my ($dom) = @_;

    my $info = $dom->get_info();

    if( $info->{'state'} eq Sys::Virt::Domain::STATE_PAUSED ){
        return 1;
    }
    return 0;
}

=item startDomain

start domain

    my $DomInfo = VirtAgent->startDomain( name=>$name );

=cut

sub startDomain {
    my $self = shift;
    my %params = @_;
    my $vm = $self->vmConnect(@_);
    my $name = $params{"name"};
    my $uuid = $params{"uuid"};

    # get domain
    my $dom = $self->getDomain(%params);

    if( isError($dom) ){
        return wantarray() ? %$dom : $dom;
    } 

    if( ! isRunning($dom) ){
        eval {
            $dom->create();
        };
        if( $@ ){
            return retErr('_START_DOMAIN_',"Can't start domain: $@");
        }
    }
    return retDomainInfo($dom);
}

=item stopDomain

stop domain

    my $DomInfo = VirtAgent->stopDomain( name=>$name, force=>1, destroy=>1 );

=cut

sub stopDomain {
    my $self = shift;
    my %params = @_;
    my $vm = $self->vmConnect(@_);
    my $name = $params{"name"};
    my $force = $params{"force"} || 0;
    my $destroy = $params{"destroy"} || 0;

    # get domain
    my $dom = $self->getDomain(%params);

    if( isError($dom) ){
        return wantarray() ? %$dom : $dom;
    } 

    if( $force || isRunning($dom) ){
        if( $destroy ){
            eval {
                $dom->destroy();
            };
            if( $@ ){
                return retErr('_DESTROY_DOMAIN_',"Can't destroy domain: $@");
            }
        } else {
            eval {
                $dom->shutdown();
            };
            if( $@ ){
                return retErr('_SHUTDOWN_DOMAIN_',"Can't shutdown domain: $@");
            }
        }
    } else {
        return retErr('_NOTRUNNING_DOMAIN_',"Domain is not running.");
    }
    return retDomainInfo($dom);
}

=item undefineDomain

undefine domain

    my $OK = VirtAgent->undefineDomain( name=>$name );

=cut

sub undefDomain {
    my $self = shift;
    my %params = @_;
    my $vm = $self->vmConnect(@_);
    my $name = $params{"name"};

    # get domain
    my $dom = $self->getDomain(%params);

    if( isError($dom) ){
        return wantarray() ? %$dom : $dom;
    } 

    if( !isRunning($dom) ){
        eval {
            $dom->undefine();
        };
        if( $@ ){
            return retErr('_CANTUNDEF_DOMAIN_',"Can't undefine domain: $@");
        }
    } else {
        return retErr('_CANTUNDEF_RUNNING_DOMAIN_',"Domain is running cant undefine.");
    }
    # TODO
    return retOk("_OK_","ok");
}

=item suspendDomain

suspend domain

    my $DomInfo = VirtAgent->suspendDomain( name=>$name );

=cut

sub suspendDomain {
    my $self = shift;
    my %params = @_;
    my $vm = $self->vmConnect(@_);
    my $name = $params{"name"};

    # get domain
    my $dom = $self->getDomain(%params);

    if( isError($dom) ){
        return wantarray() ? %$dom : $dom;
    } 

    if( isRunning($dom) ){
        eval {
            $dom->suspend();
        };
        if( $@ ){
            return retErr('_SUSPEND_DOMAIN_',"Can't suspend domain: $@");
        }
    }
    return retDomainInfo($dom);
}

=item resumeDomain

resume domain

    my $DomInfo = VirtAgent->resumeDomain( name=>$name );

=cut

sub resumeDomain {
    my $self = shift;
    my %params = @_;
    my $vm = $self->vmConnect(@_);
    my $name = $params{"name"};

    # get domain
    my $dom = $self->getDomain(%params);

    if( isError($dom) ){
        return wantarray() ? %$dom : $dom;
    } 

    if( isSuspended($dom) ){
        eval {
            $dom->resume();
        };
        if( $@ ){
            return retErr('_RESUME_DOMAIN_',"Can't resume domain: $@");
        }
    }
    return retDomainInfo($dom);
}

=item rebootDomain

reboot domain

    my $DomInfo = VirtAgent->rebootDomain( name=>$name );

=cut

sub rebootDomain {
    my $self = shift;
    my %params = @_;
    my $vm = $self->vmConnect(@_);
    my $name = $params{"name"};
    my $flags = $params{"flags"} || 0;

    # get domain
    my $dom = $self->getDomain(%params);

    if( isError($dom) ){
        return wantarray() ? %$dom : $dom;
    } 

    if( isRunning($dom) ){
        eval {
            $dom->reboot($flags);
        };
        if( $@ ){
            return retErr('_REBOOT_DOMAIN_',"Can't reboot domain: $@");
        }
    }
    return retDomainInfo($dom);
}

=item setMemory

set memory for domain

    my $DomInfo = VirtAgent->setMemory( name=>$name, mem=>$size );

=cut

sub setMemory {
    my $self = shift;
    my %params = @_;
    my $vm = $self->vmConnect(@_);
    my $name = $params{"name"};
    my $mem = $params{"mem"} || 0;

    # get domain
    my $dom = $self->getDomain(%params);

    if( isError($dom) ){
        return wantarray() ? %$dom : $dom;
    } 

    if( $mem > $dom->get_max_memory() ){
        return retErr('_MEMORY_LIMIT_EXCEEDED_',"Memory limit exceeded!");
    }

    eval {
        $dom->set_memory($mem);
    };
    if( $@ ){
        return retErr('_SET_MEMORY_DOMAIN_',"Can't set memory for domain: $@");
    }
    return retDomainInfo($dom);
}

=item setMaxMemory

set max memory for domain

    my $DomInfo = VirtAgent->setMaxMemory( name=>$name, maxmem=>$size );

=cut

sub setMaxMemory {
    my $self = shift;
    my %params = @_;
    my $vm = $self->vmConnect(@_);
    my $name = $params{"name"};
    my $maxmem = $params{"maxmem"} || 0;

    # get domain
    my $dom = $self->getDomain(%params);

    if( isError($dom) ){
        return wantarray() ? %$dom : $dom;
    } 

    if( $maxmem > $MAXMEM ){
        return retErr('_MEMORY_LIMIT_EXCEEDED_',"Memory limit exceeded!");
    }

    eval {
        $dom->set_max_memory($maxmem);
    };
    if( $@ ){
        return retErr('_SET_MAX_MEMORY_DOMAIN_',"Can't set max memory for domain: $@");
    }
    return retDomainInfo($dom);
}

=item setVCPUS

set virtual cpus for domain

    my $DomInfo = VirtAgent->setVCPUS( name=>$name, vcpus=>$n );

=cut

sub setVCPUS {
    my $self = shift;
    my %params = @_;
    my $vm = $self->vmConnect(@_);
    my $name = $params{"name"};
    my $vcpus = $params{"vcpus"} || 1;

    # get domain
    my $dom = $self->getDomain(%params);

    if( isError($dom) ){
        return wantarray() ? %$dom : $dom;
    } 

    if( $vcpus <= $MAXNCPU ){
        eval {
            $dom->set_vcpus($vcpus);
        };
        if( $@ ){
            return retErr('_SET_VCPUS_DOMAIN_',"Can't set vcpus for domain: $@");
        }
        return retDomainInfo($dom);
    } else {
        return retErr('_VCPUS_LIMIT_EXCEEDED_',"Number of CPUs exceeded!");
    }
}

=item attachDevice

attach device to domain

    my $OK = VirtAgent->attachDevice( name=>$name, device=>... );

=cut

# attachDevice
#   device attach func
sub attachDevice {
    my $self = shift;
    my %params = @_;
    my $vm = $self->vmConnect(@_);
    my $name = $params{"name"};

    # get domain
    my $dom = $self->getDomain(%params);

    if( isError($dom) ){
        return wantarray() ? %$dom : $dom;
    } 

    my $dxml = $self->genXMLDevices(@_);
    plog "dxml=$dxml" if( &debug_level > 3 );

    eval {
        $dom->attach_device($dxml)
    };
    if( $@ ){
        return retErr('_ATTACH_DEVICE_DOMAIN_',"Can't attach device to domain: $@");
    }
    retOk("_OK_","ok");
}

=item detachDevice

attach device to domain

    my $OK = VirtAgent->detachDevice( name=>$name, device=>... );

=cut

sub detachDevice {
    my $self = shift;
    my %params = @_;
    my $vm = $self->vmConnect(@_);
    my $name = $params{"name"};

    # get domain
    my $dom = $self->getDomain(%params);

    if( isError($dom) ){
        return wantarray() ? %$dom : $dom;
    } 

    my $dxml = $self->genXMLDevices(@_);

    eval {
        $dom->detach_device($dxml)
    };
    if( $@ ){
        return retErr('_DETACH_DEVICE_DOMAIN_',"Can't detach device to domain: $@");
    }
    retOk("_OK_","ok");
}

=item attachHostdev

attach hostdev to domain

    my $OK = VirtAgent->attachHostdev( name=>$name, type=>... );

=cut

sub prepHostdev {
    my $self = shift;
    my %params = @_;

    if( my $hostdev = $params{'devices'}{'hostdev'} ){
        my $hostdevs = [];
        my @l_hostdev = (ref($hostdev) eq 'ARRAY') ? @$hostdev : ($hostdev);
        for my $H ( @l_hostdev ){
            my %DH = ();
            for my $k (keys %$H){
                if( $H->{'type'} eq 'usb' ){
                    $DH{'type'} = 'usb';
                    $DH{'source'}{'vendor'} = { 'id'=>$H->{'vendor'} };
                    $DH{'source'}{'product'} = { 'id'=>$H->{'product'} };
                } elsif( $H->{'type'} eq 'pci' ){
                    $DH{'type'} = 'pci';
                    $DH{'source'}{'address'} = { 'bus'=>$H->{'bus'}, 'slot'=>$H->{'slot'}, 'function'=>$H->{'function'} };
                }
            }
            push @$hostdevs, \%DH;
        }
        $params{'devices'}{'hostdev'} = $hostdevs;
    }

    return wantarray() ? %params : \%params;
}

sub attachHostdev {
    my $self = shift;
    my %params = @_;

    %params = $self->prepHostdev(%params);
    return $self->attachDevice(%params);
}

=item detachHostdev

attach hostdev to domain

    my $OK = VirtAgent->detachHostdev( name=>$name, type=>... );

=cut

sub detachHostdev {
    my $self = shift;
    my %params = @_;

    %params = $self->prepHostdev(%params);
    return $self->detachDevice(%params);
}

=item vmMigrate
Virtual machine host migration

    my $OK = $self->vmMigrate( duri=>$uri, name=>$name, live=>1 );    

    args:

            duri       - destination host uri

            daddr      - destination host address

            name      - source virtual machine

            uuid      - source virtual machine

            dname     - destination name virtual machine

            live      - migration live flag

            bandwidth - bandwidth

    return: 

            OK - return ok message with domain info

            Error - error message

=cut

# vmMigrate
#   Virtual machine host migration
#   args: Hash { duri, daddr, name, uuid, dname, bandwidth, live }
#   return: OK || Error
#   
sub vmMigrate {
    my $self = shift;

    my %p = @_;

    # get source host connection
    my $vm = $self->vmConnect();

    # get domain
    my $dom = $self->getDomain(%p);

    if( isError($dom) ){
        return wantarray() ? %$dom : $dom;
    } 

    # destination connection uri
    my $uri = $p{'duri'};

    # get remote host connection
    my $dcon;
    if( $uri || $p{'daddr'} ){
        my %cp = ();
        $cp{'uri'} = $uri if( $uri );
        $cp{'address'} = $p{'daddr'} if( $p{'daddr'} );
        plog "uri=$cp{'uri'} address=$cp{'address'}" if( &debug_level > 5 );
        eval {
            $dcon =  Sys::Virt->new( %cp );
        };
        if( $@ ){
            return retErr('_MIGRATE_DESTCONNECT_ERROR_',"Error connecting to remote host: $@");
        }

    } else {
        return retErr('_MIGRATE_NOPARMS_DESTCONNECT_',"No parameters for remote connection");
    }

    # migration offline by default
    my $flags = 0;
    # perfome migration live
    $flags |= Sys::Virt::Domain::MIGRATE_LIVE if( $p{'live'} );
    # perfome other cases
    if( version->new($Sys::Virt::VERSION) ge version->new('0.2.3') ){
        eval {
            no strict;
            $flags |= Sys::Virt::Domain::MIGRATE_PEER2PEER if( $p{'peer2peer'} );
            $flags |= Sys::Virt::Domain::MIGRATE_TUNNELLED if( $p{'tunnelled'} );
            $flags |= Sys::Virt::Domain::MIGRATE_PERSIST_DEST if( $p{'persist_dest'} );
            $flags |= Sys::Virt::Domain::MIGRATE_UNDEFINE_SOURCE if( $p{'undefine_source'} );
            $flags |= Sys::Virt::Domain::MIGRATE_PAUSED if( $p{'paused'} );
            use strict;
        };
    }
    if( version->new($Sys::Virt::VERSION) ge version->new('0.9.4') ){
        # only available on libvirt 0.9.4
        eval {
            no strict;
            $flags |= Sys::Virt::Domain::MIGRATE_NON_SHARED_DISK if( $p{'non_shared_disk'} );
            $flags |= Sys::Virt::Domain::MIGRATE_NON_SHARED_INC if( $p{'non_shared_inc'} );
            $flags |= Sys::Virt::Domain::MIGRATE_CHANGE_PROTECTION if( $p{'change_protection'} );
            use strict;
        };
    }
    if( version->new($Sys::Virt::VERSION) ge version->new('0.9.11') ){
        # only available on libvirt 0.9.11
        eval {
            no strict;
            $flags |= Sys::Virt::Domain::MIGRATE_UNSAFE if( $p{'unsafe'} );
            use strict;
        };
    }
    plog "unsafe mode on flags=$flags version=$Sys::Virt::VERSION" if( $p{'unsafe'} );

    my $dname = $p{'dname'};
    my $bw = $p{'bandwidth'};

    # migrate URI
    my $migrateuri = $p{'migrateuri'};

    my $ddom;
    eval {
        $ddom = $dom->migrate($dcon,$flags,$dname,$migrateuri,$bw);
    };
    if( $@ ){
        return retErr('_MIGRATE_ERROR_',"Something wrong with migration: $@");
    }

    # return domain info
    my $ID = retDomainInfo($ddom);
    return retOk("_OK_MIGRATE_","Domain successful migrated","_RET_OBJ_",$ID)
    
}

# saveDomain: save VM state to file
sub saveDomain {
    my $self = shift;

    my %p = @_;

    # get source host connection
    my $vm = $self->vmConnect();

    # get domain
    my $dom = $self->getDomain(%p);

    if( isError($dom) ){
        return wantarray() ? %$dom : $dom;
    } 

    my $file = $p{'file'};

    eval {
        $dom->save($file);
    };
    if( $@ ){
        return retErr('_SAVE_ERROR_',"Something wrong saving to file: $@");
    }

    # return domain info
    my $ID = retDomainInfo($dom);
    return retOk("_OK_SAVE_","Domain successful saved","_RET_OBJ_",$ID);
}

# restoreDomain: restore domain from saved file
sub restoreDomain {
    my $self = shift;

    my %p = @_;

    # get source host connection
    my $vm = $self->vmConnect();

    my $file = $p{'file'};

    eval {
        $vm->restore_domain($file);
    };
    if( $@ ){
        return retErr('_Restore_ERROR_',"Something wrong with restore domain: $@");
    }

    # return domain info
    return retOk("_OK_RESTORE_","Domain successful restored");
}

# create snapshots
my $HAVEVIRSHCREATESNAPSHOTSUPPORT;
sub haveVirshCreateSnapshotSupport {
    my ($err) = @_;
    if( $err =~ m/error: this function is not supported by the connection driver/ ){
        $HAVEVIRSHCREATESNAPSHOTSUPPORT = 0;
    }
    if( not defined $HAVEVIRSHCREATESNAPSHOTSUPPORT ){
        my ($e,$m) = cmd_exec("virsh help");
        if( $e == 0 && ( $m =~ m/snapshot-create/gs ) ){
            $HAVEVIRSHCREATESNAPSHOTSUPPORT = 1;
        } else {
            $HAVEVIRSHCREATESNAPSHOTSUPPORT = 0;
        }
    }
    return $HAVEVIRSHCREATESNAPSHOTSUPPORT;
}
sub haveSnapshotSupport {
    return &haveVirshCreateSnapshotSupport();
}
sub create_snapshot {
    my $self = shift;
    my %p = @_;

    # get source host connection
    my $vm = $self->vmConnect();

    # get domain
    my $dom = $self->getDomain(%p);

    if( isError($dom) ){
        return wantarray() ? %$dom : $dom;
    } 

    if( $dom->can("create_snapshot") ){
        # generate xml domain snapshot
        my $X = XML::Generator->new(':pretty');
        my @domainsnapshot_params = ();
        push(@domainsnapshot_params, $X->name($p{'snapshot'})) if( $p{'snapshot'} );
        my $xml = sprintf('%s', $X->domainsnapshot(@domainsnapshot_params) );

        my $flags;
        my $snapshot;
        eval {
            $snapshot = $dom->create_snapshot($xml,$flags);
        };
        if( $@ ){
            return retErr('_ERR_CREATE_SNAPSHOT_',"Something wrong creating snapshot: $@");
        }
    } elsif( &haveVirshCreateSnapshotSupport ){
        plog("Module Perl of libvirt doesn't support snapshots") if( &debug_level > 3 );
        my $name = $dom->get_name();
        my ($e,$m) = cmd_exec("virsh snapshot-create-as",$name,$p{'snapshot'});
        unless( $e == 0 ){
            return retErr('_ERR_CREATE_SNAPSHOT_',"Something wrong creating snapshot: $m");
        }
        my ($snaphost) = ($m =~ m/Domain snapshot (.+) created/);

    } else {
        return retErr('_ERR_CREATE_SNAPSHOT_',"libvirt doesn't support snapshots");
    }

    # return domain info
    my $ID = retDomainInfo($dom);
    return retOk("_OK_CREATE_SNAPSHOT_","Snapshot created successfully.","_RET_OBJ_",$ID);
}
sub revert_snapshot {
    my $self = shift;
    my %p = @_;

    # get source host connection
    my $vm = $self->vmConnect();

    # get domain
    my $dom = $self->getDomain(%p);

    if( isError($dom) ){
        return wantarray() ? %$dom : $dom;
    } 

    if( $dom->can("create_snapshot") ){
        if( $dom->has_current_snapshot() ){
            my $snapshot;
            if( $p{'snapshot'} ){
                eval {
                    $snapshot = $dom->get_snapshot_by_name($p{'snapshot'});
                };
            }
            if( !$snapshot ){
                return retErr('_ERR_REMOVE_SNAPSHOT_',"Snapshot '$p{snapshot}' not found.");
            }
            eval {
                $snapshot->revert_to()
            };
            if( $@ ){
                return retErr('_ERR_REMOVE_SNAPSHOT_',"Something wrong to revert snapshot: $@");
            }
        } else {
            return retErr('_ERR_REMOVE_SNAPSHOT_',"No snapshots available.");
        }
    } elsif( &haveVirshCreateSnapshotSupport ){
        plog("Module Perl of libvirt doesn't support snapshots") if( &debug_level > 3 );
        my $name = $dom->get_name();
        my ($e,$m) = cmd_exec("virsh snapshot-revert",$name,$p{'snapshot'});
        unless( $e == 0 ){
            return retErr('_ERR_REMOVE_SNAPSHOT_',"Something wrong to revert snapshot: $m");
        }
        # TODO
    } else {
        return retErr('_ERR_REMOVE_SNAPSHOT_',"libvirt doesn't support snapshots");
    }

    # return domain info
    my $ID = retDomainInfo($dom);
    return retOk("_OK_REMOVE_SNAPSHOT_","Snapshot reverted successfully.","_RET_OBJ_",$ID);
}
sub remove_snapshot {
    my $self = shift;
    my %p = @_;

    # get source host connection
    my $vm = $self->vmConnect();

    # get domain
    my $dom = $self->getDomain(%p);

    if( isError($dom) ){
        return wantarray() ? %$dom : $dom;
    } 

    if( $dom->can("create_snapshot") ){
        if( $dom->has_current_snapshot() ){
            my $snapshot;
            eval {
                $snapshot = ($p{'snapshot'}) ? $dom->get_snapshot_by_name($p{'snapshot'})
                                                    : $dom->current_snapshot();
            };
            if( !$snapshot ){
                return retErr('_ERR_REVERT_SNAPSHOT_',"Snapshot '$p{snapshot}' not found.");
            }
            eval {
                $snapshot->delete()
            };
            if( $@ ){
                return retErr('_ERR_REVERT_SNAPSHOT_',"Something wrong to remove snapshot: $@");
            }
        } else {
            return retErr('_ERR_REVERT_SNAPSHOT_',"No snapshots available.");
        }
    } elsif( &haveVirshCreateSnapshotSupport ){
        plog("Module Perl of libvirt doesn't support snapshots") if( &debug_level > 3 );
        my $name = $dom->get_name();
        my ($e,$m) = cmd_exec("virsh snapshot-delete",$name,$p{'snapshot'});
        unless( $e == 0 ){
            return retErr('_ERR_REVERT_SNAPSHOT_',"Something wrong to remove snapshot: $m");
        }
        # TODO
    } else {
        return retErr('_ERR_REVERT_SNAPSHOT_',"libvirt doesn't support snapshots");
    }

    # return domain info
    my $ID = retDomainInfo($dom);
    return retOk("_OK_REVERT_SNAPSHOT_","Snapshot removed successfully.","_RET_OBJ_",$ID);
}
sub domainListSnapshots {
    my $self = shift;
    my ($dom) = @_;

    my @list = ();
    if( $dom ){
        if( $dom->can("create_snapshot") ){
            my @snapshots = ();
            eval {
                @snapshots = $dom->list_snapshots();
            };
            if( $@ ){
                return retErr('_ERR_LIST_SNAPSHOTS_',"Something wrong to list snapshots: $@");
            }
            for my $S (@snapshots){
                my %H = $self->domainSnapshotToHash( $S );
                push(@list, { 'name'=>$H{'name'}, 'createdate'=>$H{'createdate'} ,'createtime'=>$H{'creationTime'}, 'state'=>$H{'state'} });
            }
        } elsif( &haveVirshCreateSnapshotSupport ){
            plog("Module Perl of libvirt doesn't support snapshots") if( &debug_level > 3 );
            my $name = $dom->get_name();
            my ($e,$m) = cmd_exec("virsh snapshot-list",$name);
            unless( $e == 0 ){
                &haveVirshCreateSnapshotSupport($m);
                return retErr('_ERR_LIST_SNAPSHOTS_',"Something wrong to list snapshots: $m");
            }
            for my $l (split(/\n/,$m)){
                # 1344498085           2012-08-09 08:41:25 +0100 shutoff
                if( $l =~ m/^\s+(.+)\s+(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2} .\d{4})\s+(\S+)$/ ){
                    my ($snapshot,$time,$state) = ($1,$2,$3);
                    push(@list, { 'name'=>trim($snapshot), 'createtime'=>$time, 'state'=>$state });
                }
            }
            # TODO
        } else {
            return retErr('_ERR_LIST_SNAPSHOTS_',"libvirt doesn't support snapshots");
        }
    }
    return wantarray() ? @list : \@list;
}
sub list_snapshots {
    my $self = shift;
    my %p = @_;

    # get source host connection
    my $vm = $self->vmConnect();

    # get domain
    my $dom = $self->getDomain(%p);

    if( isError($dom) ){
        return wantarray() ? %$dom : $dom;
    } 

    return $self->domainListSnapshots($dom);
}

sub domainSnapshotToHash {
    my $self = shift;
    my ($snapshot) = @_;

    my $xml = ref($snapshot) ? $snapshot->get_xml_description() : $snapshot;
    my %H = $self->domain_xml_parser( $xml );
    $H{'createdate'} = strftime('%Y-%m-%d %H:%M:%S %z',localtime($H{'creationTime'}));

    return wantarray() ? %H : \%H;
}

sub get_snapshot {
    my $self = shift;
    my %p = @_;

    # get source host connection
    my $vm = $self->vmConnect();

    # get domain
    my $dom = $self->getDomain(%p);

    if( isError($dom) ){
        return wantarray() ? %$dom : $dom;
    } 

    if( $dom->can("create_snapshot") ){
        if( $dom->has_current_snapshot() ){
            my $snapshot;
            eval {
                $snapshot = ($p{'snapshot'}) ? $dom->get_snapshot_by_name($p{'snapshot'})
                                                    : $dom->current_snapshot();
            };
            if( !$snapshot ){
                return retErr('_ERR_GET_SNAPSHOT_',"No Snapshot found.");
            }
            return $self->domainSnapshotToHash($snapshot);
        } else {
            return retErr('_ERR_GET_SNAPSHOT_',"No snapshots available.");
        }
    } elsif( &haveVirshCreateSnapshotSupport ){
        plog("Module Perl of libvirt doesn't support snapshots") if( &debug_level > 3 );
        my $name = $dom->get_name();
        my @cmd = ("virsh snapshot-current",$name);
        if( $p{'snapshot'} ){
            @cmd = ("virsh snapshot-dumpxml",$name,$p{'snapshot'});
        }
        my ($e,$xml) = cmd_exec(@cmd);
        unless( $e == 0 ){
            return retErr('_ERR_GET_SNAPSHOT_',"Something wrong to get snapshot: $xml");
        }
        if( $xml ){
            return $self->domainSnapshotToHash($xml);
        } else {
            return retErr('_ERR_GET_SNAPSHOT_',"Something wrong to get snapshot: not xml available.");
        }
    } else {
        return retErr('_ERR_GET_SNAPSHOT_',"libvirt doesn't support snapshots");
    }
}


sub vmIsRunning {
    my $self = shift;
    my (%p) = @_;

    # get domain
    my $dom = $self->getDomain(%p);

    if( isError($dom) ){
        return wantarray() ? %$dom : $dom;
    } 

    return isRunning($dom);
}

sub getAttrs {
    my ($H) = @_;
    return if( !$H );
    my %Attrs = ();
    for my $k (keys %$H){
        # only scalars
        if( !ref($H->{"$k"}) ){
            $Attrs{"$k"} = $H->{"$k"};
        }
    }
    return \%Attrs;
}
sub genXMLDomain {
    my $self = shift;

    my (%params) = @_;

    # return xml from param    
    return $params{'xml'} if( $params{'xml'} );

    my $X = XML::Generator->new(':pretty');

    # domain params
    my @dom_params = ();
    my %dom_attrs = ();
    $dom_attrs{'type'} = $params{'type'} = $self->get_type( %params );
    $dom_attrs{'id'} = $params{'id'} if( $params{'id'} );

    push(@dom_params, \%dom_attrs ) if( %dom_attrs );

    my $name = $params{'name'};
    push(@dom_params, $X->name( $name ) );

    my $uuid = $params{'uuid'} ? $params{'uuid'} : random_uuid();
    push(@dom_params, $X->uuid( $uuid ));

    if( defined $params{'description'} ){
        push(@dom_params, $X->description( $params{'description'} ) );
    }

    my $maxmemory = $self->get_maxmemory( %params ) / 1024; # in kbytes
    push(@dom_params, $X->memory( $maxmemory ) );
    my $memory = $self->get_memory( %params ) / 1024;   # in kbytes
    push(@dom_params, $X->currentMemory( $memory ) );

    my %cpu_attrs = ();
    my $maxcpu = $MAXNCPU;
    if( defined $params{'cpuset'} ){
        $cpu_attrs{'cpuset'} = $params{'cpuset'};

        # change max of cpus
        my @lc = split(/,/,$cpu_attrs{'cpuset'});
        $maxcpu = scalar(@lc);
    }
    my $vcpu = $params{'vcpu'} ? $params{'vcpu'} : $maxcpu;
    push(@dom_params, $X->vcpu( \%cpu_attrs, $vcpu ) );

    my @os_params = $self->get_osparams_xml( %params );

    if( @os_params ){
        push(@dom_params, $X->os(@os_params) );
    }
    if( !@os_params ||
            ( $params{'boot'}{'loader'} && ( $self->get_type(%params) eq 'xen' ) ) ){
        push(@dom_params, $X->bootloader( '/usr/bin/pygrub' ) );
    }

    my $on_poweroff = $params{'on_poweroff'} || "destroy";
    push(@dom_params, $X->on_poweroff( $on_poweroff ));
    my $on_reboot = $params{'on_reboot'} || "restart";
    push(@dom_params, $X->on_reboot( $on_reboot ));
    my $on_crash = $params{'on_crash'} || "restart";
    push(@dom_params, $X->on_crash( $on_crash ));

    # features
    if( my $F = $params{'features'} ){
        my @features_params = ();
        for my $feature (keys %$F){
            if( $F->{"$feature"} ){
                push(@features_params, $X->$feature());
            }
        }
        push(@dom_params, $X->features(@features_params) ) if( @features_params );
    }

    # cpu
    if( my $C = $params{'cpu'} ){
        my @cpu_params = ();

        if( $C->{'vendor'} ){
            my $v = delete($C->{'vendor'});
            push(@cpu_params, $X->vendor($v));
        }
        if( $C->{'model'} || $C->{'model_fallback'} ){
            my $m = delete($C->{'model'});
            my $mf = delete $C->{'model_fallback'};
            my $am = {};
            $am->{'fallback'} = $mf if( $mf );
            push(@cpu_params, $X->model($am,$m));
        }

        # get attributes
        my $cpu_attrs = getAttrs($C);
        unshift(@cpu_params,$cpu_attrs) if( $cpu_attrs );

        for my $e (keys %$C){
            my $v = $C->{"$e"};
            if( ref($v) ){
                my $vatt = {};
                my @e_params = ();
                for my $va (keys %$v){
                    my $va_v = $v->{"$va"};
                    if( !ref($va_v) ){
                        $vatt->{"$va"} = $va_v;
                    } elsif(ref($va_v) eq 'ARRAY'){     # for tags like numae
                        for my $va_ve (@$va_v){
                            push(@e_params,$X->$va($va_ve));
                        }
                    } elsif(ref($va_v) eq 'HASH'){      # for other tags
                        push(@e_params,$X->$va($va_v));
                    }
                }
                unshift(@e_params, $vatt) if( %$vatt );     # put attributes in first place
                push(@cpu_params, $X->$e(@e_params));
            }
        }
        push(@dom_params, $X->cpu(@cpu_params) ) if( @cpu_params );
    }

#    open FILE, ">>/tmp/debug.txt" or die $!;
#    print FILE Dumper \%params;
#    close FILE;

    my @devices_params = ();

    #   <disk>
    my @diskdevices_params = $self->get_diskdevices_xml(%params);
    push(@devices_params, @diskdevices_params ) if( @diskdevices_params );

    #   <hostdev>
    my @hostdevdevices_params = $self->get_hostdevdevices_xml(%params);
    push(@devices_params, @hostdevdevices_params ) if( @hostdevdevices_params );

    #   <controller>
    my @controllers_params = $self->get_controllers_xml(%params);
    push(@devices_params, @controllers_params ) if ( @controllers_params );
    
    #   <channel>
    my @channels_params = $self->get_channels_xml(%params);
    push(@devices_params, @channels_params ) if ( @channels_params );

    #   <interface>
    my @interfacedevices_params = $self->get_interfacedevices_xml(%params);
    push(@devices_params, @interfacedevices_params ) if( @interfacedevices_params );

    #   <filesystem>
    my @filesystemdevices_params = $self->get_filesystemdevices_xml(%params);
    push(@devices_params, @filesystemdevices_params ) if( @filesystemdevices_params );

    my @inputdevices_params = $self->get_inputdevices_xml(%params);
    push(@devices_params, @inputdevices_params ) if( @inputdevices_params );
    #push(@devices_params, $X->input( $params{'devices'}{'input'} )) if( $params{'devices'}{'input'} );

    push(@devices_params, $X->graphics( $params{'devices'}{'graphics'} )) if( $params{'devices'}{'graphics'} );

    if( $params{'devices'}{'console'} ){
        my @console_params = ();
        my $console_attrs = getAttrs($params{'devices'}{'console'});
        push(@console_params,$console_attrs) if( $console_attrs );
        push(@console_params,$X->source($params{'devices'}{'console'}{'source'})) if( $params{'devices'}{'console'}{'source'} );
        push(@console_params,$X->target($params{'devices'}{'console'}{'target'})) if( $params{'devices'}{'console'}{'target'} );
        push(@devices_params, $X->console(@console_params) ) if( @console_params );
    }

    push(@dom_params, $X->devices(@devices_params) ) if( @devices_params );

    # return xml as string
    return sprintf('%s', $X->domain(@dom_params) );
}
sub genXMLDevices {
    my $self = shift;
    my (%params) = @_;
    my $X = XML::Generator->new(':pretty');

    # <devices>
    my @devices_params = ();

    #   <emulator>
    push(@devices_params, $X->emulator($params{'emulator'}) ) if( $params{'emulator'} );

    
    #   <disk>
    my @diskdevices_params = $self->get_diskdevices_xml(%params);
    push(@devices_params, @diskdevices_params ) if( @diskdevices_params );

    #   <hostdev>
    my @hostdevdevices_params = $self->get_hostdevdevices_xml(%params);
    push(@devices_params, @hostdevdevices_params ) if( @hostdevdevices_params );

    #   <controller>
    my @controllers_params = $self->get_controllers_xml(%params);
    push(@devices_params, @controllers_params ) if ( @controllers_params );
    
    #   <channel>
    my @channels_params = $self->get_channels_xml(%params);
    push(@devices_params, @channels_params ) if ( @channels_params );

    # <interface>
    my @interfacedevices_params = $self->get_interfacedevices_xml(%params);
    push(@devices_params, @interfacedevices_params ) if( @interfacedevices_params );

    # <filesystem>
    my @filesystemdevices_params = $self->get_filesystemdevices_xml(%params);
    push(@devices_params, @filesystemdevices_params ) if( @filesystemdevices_params );

    my @inputdevices_params = $self->get_inputdevices_xml(%params);
    push(@devices_params, @inputdevices_params ) if( @inputdevices_params );

    push(@devices_params, $X->graphics( $params{'devices'}{'graphics'} )) if( $params{'devices'}{'graphics'} );


    # parallel
    if($params{'devices'}{'parallel'}){
        my @parallel_params = ();
        my $parallel_attrs = getAttrs($params{'devices'}{'parallel'});
        push(@parallel_params,$parallel_attrs) if( $parallel_attrs );
        push(@parallel_params,$X->source($params{'devices'}{'parallel'}{'source'})) if( $params{'devices'}{'parallel'}{'source'} );
        push(@parallel_params,$X->target($params{'devices'}{'parallel'}{'target'})) if( $params{'devices'}{'parallel'}{'target'} );
        push(@devices_params, $X->parallel(@parallel_params) ) if( @parallel_params );
    }

    # serial
    if($params{'devices'}{'serial'}){
        my @serial_params = ();
        my $serial_attrs = getAttrs($params{'devices'}{'serial'});
        push(@serial_params,$serial_attrs) if( $serial_attrs );
        push(@serial_params,$X->source($params{'devices'}{'serial'}{'source'})) if( $params{'devices'}{'serial'}{'source'} );
        push(@serial_params,$X->target($params{'devices'}{'serial'}{'target'})) if( $params{'devices'}{'serial'}{'target'} );
        push(@devices_params, $X->serial(@serial_params) ) if( @serial_params );
    }

    # console
    if( $params{'devices'}{'console'} ){
        my @console_params = ();
        my $console_attrs;
        $console_attrs = getAttrs($params{'devices'}{'console'});
        push(@console_params,$console_attrs) if( $console_attrs );
        push(@console_params,$X->source($params{'devices'}{'console'}{'source'})) if( $params{'devices'}{'console'}{'source'} );
        push(@console_params,$X->target($params{'devices'}{'console'}{'target'})) if( $params{'devices'}{'console'}{'target'} );
        push(@devices_params, $X->console(@console_params) ) if( @console_params );
    }

    # return xml string
    #return sprintf('%s', $X->devices(@devices_params) );
    return shift @devices_params;
}
sub genXMLNetwork {
    my $self = shift;
    my (%params) = @_;
    my $X = XML::Generator->new(':pretty');

    # <network>

    my @network_params = ();

    push(@network_params, $X->name($params{'network'}{'name'}) );
    push(@network_params, $X->uuid($params{'network'}{'uuid'}) ) if($params{'network'}{'uuid'});

    #   <bridge>
    if( $params{'network'}{'bridge'} ){
        my $bridge_attrs;
        $bridge_attrs = getAttrs($params{'network'}{'bridge'});
        push(@network_params, $X->bridge($bridge_attrs) ) if( $bridge_attrs );
    }

    #   <forward>
    if( $params{'network'}{'forward'} ){
        my $forward_attrs;
        $forward_attrs = getAttrs($params{'network'}{'forward'});
        push(@network_params, $X->forward($forward_attrs) ) if( $forward_attrs );
    }

    #   <ip>
    if( $params{'network'}{'ip'} ){
        my @ip_params = ();
        push(@ip_params, getAttrs($params{'network'}{'ip'}) );

        if( $params{'network'}{'ip'}{'dhcp'} ){
            my @dhcp_params = ();
            if( $params{'network'}{'ip'}{'dhcp'}{'range'} ){
                push(@dhcp_params, $X->range( $params{'network'}{'ip'}{'dhcp'}{'range'} ) );
            }
            if( my $host = $params{'network'}{'ip'}{'dhcp'}{'host'} ){
                if( ref($host) eq 'HASH' ){
                    push(@dhcp_params, $X->host( $params{'network'}{'ip'}{'dhcp'}{'host'} ) );
                } elsif( ref($host) eq 'ARRAY' ){
                    for my $H (@$host){
                        push(@dhcp_params, $X->host( $H ) );
                    }
                }
            }
            push(@ip_params, $X->dhcp( @dhcp_params ) ) if( @dhcp_params );
        }

        push(@network_params, $X->ip( @ip_params ) ) if( @ip_params );
    }

    return sprintf('%s', $X->network(@network_params) );
}

sub get_type {
    my $self = shift;
    my %params = @_;
    my $type = $params{'type'};
    
    if( !$type ){
	$self->vmConnect( @_ );
        $type = $VMConnection->get_type();
        if( $type eq "Xen" ){
            $type = "xen";
        } elsif( lc($type) eq 'qemu' ){
            $type = "qemu";
            if( $self->have_kvm_support() ){
                $type = "kvm";
            } elsif( $self->have_kqemu_support() ){
                $type = "kqemu";
            }
        } else {
            $type = lc($type);
        }
    }
    return $type;
}
sub have_kqemu_support {
    if( not defined $have_kqemu ){
        $have_kqemu = (-e "/dev/kqemu")? 1:0;
    }
    return $have_kqemu;
}
sub have_kvm_support {
    if( not defined $have_kvm ){
        $have_kvm = (-e "/dev/kvm")? 1:0;
    }
    return $have_kvm;
}
sub have_hvm_support {
    if( not defined $have_hvm ){
        open(F,"/sys/hypervisor/properties/capabilities");
        my $cnt = "";
        while(<F>){ chomp; $cnt .= $_; }
        close(F);
 
        $have_hvm = ($cnt =~ m/hvm/s) ? 1:0;
    }
    return $have_hvm;
}
sub is_hvm {
    my $self = shift;
    my (%p) = @_;
    if( $p{'hvm'} && $self->have_hvm_support() ){
        return 1;
    }
    return 0;
}
sub get_name {
    my $self = shift;
    my %params = @_;
    my $name = $params{'name'};

    my $dom;
    eval { $dom = $VMConnection->get_domain_by_name($name) };
    if( $dom ){
        return retErr("_DOMAIN_EXISTS_","Domain already exists");
    }
    return $name;
}
sub get_maxmemory {
    my $self = shift;
    my %params = @_;
    my $mem = $params{'memory'};

    if( !$mem || $mem > $MAXMEM ){
        $mem = $MAXMEM;
    }
    return $mem;
}
sub get_memory {
    my $self = shift;
    my %params = @_;
    my $mem = $params{'currentMemory'};
    
    if( !$mem || $mem > $self->get_maxmemory(%params) ){
        $mem = $self->get_maxmemory(%params);
    }
    return $mem;    
}
sub get_osparams_xml {
    my $self = shift;
    my %params = @_; 

    my @os_params = ();

    my $X = XML::Generator->new(':pretty');

    # type
    my $type = $params{'os'}{'type'};
    if( !$type ){
        if( is_hvm(%params) ){
            $type = "hvm";
        } else {
            $type = "linux";
        }
    }
    my %type_attrs = ();
    $type_attrs{'arch'} = $params{'arch'} if( $params{'arch'} );
    $type_attrs{'machine'} = $params{'machine'} if( $params{'machine'} );
    push(@os_params, $X->type( \%type_attrs, $type ) );
    
    # special type exe
    if( $type eq 'exe' ){
        my $init = $params{'os'}{'init'} || '/sbin/init';
        push(@os_params, $X->init( $init ) );
    } else {
        %params = $self->load_kernel_params( %params );

        if( $params{'os'}{'loader'} ){
            push(@os_params, $X->loader( $params{'os'}{'loader'} ) );
        } 

        if( $params{'os'}{'kernel'} ){
            # kernel
            push(@os_params, $X->kernel( $params{'os'}{'kernel'} ) );
            # initrd
            push(@os_params, $X->initrd( $params{'os'}{'initrd'} ) );
            # cmdline
            push(@os_params, $X->cmdline( $params{'os'}{'cmdline'} ) );
        }
        if( $params{'os'}{'install'} ){
            if( $params{'os'}{'pxe'} ){
                push(@os_params, $X->boot( {'dev'=>'network'} ) );
            } else {
                push(@os_params, $X->boot( {'dev'=>'cdrom'} ) );
            }
        } elsif( $params{'os'}{'bootdev'} ){
            push(@os_params, $X->boot( {'dev'=>$params{'os'}{'bootdev'}} ) );
        } else {
            push(@os_params, $X->boot( {'dev'=>'hd'} ) );
        }
    }
    
    return wantarray() ? @os_params : \@os_params;
}
sub get_diskdevice_xml {
    my $self = shift;
    my ($D) = my %p = @_; 

    $D = \%p if( !ref($D) );

    my $X = XML::Generator->new(':pretty');

    my @devices_params = ();

    my @disk_params = ();

    my $readonly = delete $D->{'readonly'};
    my $disk_attrs;
    $disk_attrs = getAttrs($D) if( $D );
    push(@disk_params,$disk_attrs) if( $disk_attrs );

    my $source_attrs;
    $source_attrs = getAttrs($D->{'source'}) if( $D->{'source'} );
    push(@disk_params,$X->source($source_attrs) ) if( $source_attrs );

    my $target_attrs;
    $target_attrs = getAttrs($D->{'target'}) if( $D->{'target'} );
    push(@disk_params,$X->target($target_attrs) ) if( $target_attrs );

    my $driver_attrs;
    $driver_attrs = getAttrs($D->{'driver'}) if( $D->{'driver'} );
    push(@disk_params,$X->driver($driver_attrs) ) if( $driver_attrs );

    push(@disk_params,$X->readonly() ) if( $readonly );

    push(@devices_params, $X->disk(@disk_params) ) if( @disk_params );

    return wantarray() ? @devices_params : \@devices_params;
}
sub get_diskdevices_xml {
    my $self = shift;
    my %params = @_; 

    my $X = XML::Generator->new(':pretty');

    my @devices_params = ();

    my $ldd = $params{'devices'}{'disk'};
    if( ref($ldd) eq 'ARRAY' ){
        for my $D (@$ldd){
            push(@devices_params, $self->get_diskdevice_xml($D) );
        }
    } elsif( ref($ldd) eq 'HASH' ){
        push(@devices_params, $self->get_diskdevice_xml($ldd) );
    }

    return wantarray() ? @devices_params : \@devices_params;
}
sub get_interfacedevice_xml {
    my $self = shift;
    my ($D) = my %p = @_; 

    $D = \%p if( !ref($D) );

    my $X = XML::Generator->new(':pretty');

    my @devices_params = ();

    my @interface_params = ();
    my $interface_attrs;
    $interface_attrs = getAttrs($D) if( $D );
    push(@interface_params,$interface_attrs) if( $interface_attrs );
    push(@interface_params,$X->source($D->{'source'})) if( $D->{'source'} );
    push(@interface_params,$X->mac($D->{'mac'})) if( $D->{'mac'} );
    push(@interface_params,$X->script($D->{'script'})) if( $D->{'script'} );
    push(@interface_params,$X->target($D->{'target'})) if( $D->{'target'} );
    push(@interface_params,$X->model($D->{'model'})) if( $D->{'model'} );

    push(@devices_params, $X->interface(@interface_params) ) if( @interface_params );

    return wantarray() ? @devices_params : \@devices_params;
}
sub get_interfacedevices_xml {
    my $self = shift;
    my %params = @_; 

    my $X = XML::Generator->new(':pretty');

    my @devices_params = ();
    my $ldi = $params{'devices'}{'interface'};
    if(ref($ldi) eq 'HASH'){
        push(@devices_params,$self->get_interfacedevice_xml($ldi));
    } elsif( ref($ldi) eq 'ARRAY'){
        for my $D (@$ldi){
            push(@devices_params,$self->get_interfacedevice_xml($D));
        }
    }

    return wantarray() ? @devices_params : \@devices_params;
}
sub get_filesystemdevice_xml {
    my $self = shift;
    my ($D) = my %p = @_; 

    $D = \%p if( !ref($D) );

    my $X = XML::Generator->new(':pretty');

    my @devices_params = ();

    my @filesystem_params = ();
    my $filesystem_attrs;
    $filesystem_attrs = getAttrs($D) if( $D );
    push(@filesystem_params,$filesystem_attrs) if( $filesystem_attrs );
    push(@filesystem_params,$X->source($D->{'source'})) if( $D->{'source'} );
    push(@filesystem_params,$X->target($D->{'target'})) if( $D->{'target'} );

    push(@devices_params, $X->filesystem(@filesystem_params) ) if( @filesystem_params );

    return wantarray() ? @devices_params : \@devices_params;
}
sub get_filesystemdevices_xml {
    my $self = shift;
    my %params = @_; 

    my $X = XML::Generator->new(':pretty');

    my @devices_params = ();
    my $ldf = $params{'devices'}{'filesystem'};
    if(ref($ldf) eq 'HASH'){
        push(@devices_params,$self->get_filesystemdevice_xml($ldf));
    } elsif( ref($ldf) eq 'ARRAY'){
        for my $D (@$ldf){
            push(@devices_params,$self->get_filesystemdevice_xml($D));
        }
    }

    return wantarray() ? @devices_params : \@devices_params;
}
sub get_inputdevice_xml {
    my $self = shift;
    my ($D) = my %p = @_; 

    $D = \%p if( !ref($D) );

    my $X = XML::Generator->new(':pretty');

    my @devices_params = ();

    my @input_params = ();
    my $input_attrs;
    $input_attrs = getAttrs($D) if( $D );
    push(@input_params,$input_attrs) if( $input_attrs );

    push(@devices_params, $X->input(@input_params) ) if( @input_params );

    return wantarray() ? @devices_params : \@devices_params;
}
sub get_inputdevices_xml {
    my $self = shift;
    my %params = @_; 

    my $X = XML::Generator->new(':pretty');

    my @devices_params = ();
    my $ldf = $params{'devices'}{'input'};
    if(ref($ldf) eq 'HASH'){
        push(@devices_params,$self->get_inputdevice_xml($ldf));
    } elsif( ref($ldf) eq 'ARRAY'){
        for my $D (@$ldf){
            push(@devices_params,$self->get_inputdevice_xml($D));
        }
    }

    return wantarray() ? @devices_params : \@devices_params;
}
sub get_hostdevdevice_xml {
    my $self = shift;
    my ($D) = my %p = @_; 

    $D = \%p if( !ref($D) );

    my $X = XML::Generator->new(':pretty');

    my @devices_params = ();

    my @hostdev_params = ();

    #   <hostdev>
    my $hostdev_attrs = getAttrs($D);
    push(@hostdev_params,$hostdev_attrs) if( $hostdev_attrs );

    #       <source>
    my @source_params = ();

    if( $D->{'type'} eq 'usb' ){
        push(@source_params,$X->vendor( $D->{'source'}{'vendor'} )) if( $D->{'source'}{'vendor'}  );
        push(@source_params,$X->product( $D->{'source'}{'product'} )) if( $D->{'source'}{'product'} );
    } else {
        push(@source_params,$X->address( $D->{'source'}{'address'} )) if( $D->{'source'}{'address'} );
    }
    
    push(@hostdev_params, $X->source( @source_params )) if( @source_params );

    push(@devices_params,$X->hostdev(@hostdev_params)) if( @hostdev_params );

    return wantarray() ? @devices_params : \@devices_params;
}

sub get_controller_xml {
    my $self = shift;
    my ($D) = my %p = @_; 

    $D = \%p if( !ref($D) );

    my $X = XML::Generator->new(':pretty');

    my @devices_params = ();

    my @controller_params = ();

    #   <controller>
    my $controller_attrs = getAttrs($D);
    push(@controller_params,$controller_attrs) if( $controller_attrs );
    push(@devices_params,$X->controller(@controller_params)) if( @controller_params );

    return wantarray() ? @devices_params : \@devices_params;
}

sub get_channel_xml {
    my $self = shift;
    my ($D) = my %p = @_; 

    $D = \%p if( !ref($D) );

    my $X = XML::Generator->new(':pretty');

    my @devices_params = ();

    my @channel_params = ();
    my @target_params = ();
    my @source_params = ();

    #   <channel>
    my $channel_attrs = getAttrs($D);
    push(@channel_params,$channel_attrs) if( $channel_attrs );

    #       <target>
    my $target_attrs = getAttrs($D->{'target'});
    push(@target_params, $target_attrs) if( $target_attrs );    

    #       <source>
    my $source_attrs = getAttrs($D->{'source'});
    push(@source_params, $source_attrs) if( $source_attrs );    
   
    push(@channel_params, $X->source( @source_params )) if( @source_params );
    push(@channel_params, $X->target( @target_params )) if( @target_params );

    push(@devices_params,$X->channel(@channel_params)) if( @channel_params );

    return wantarray() ? @devices_params : \@devices_params;
}

sub get_hostdevdevices_xml {
    my $self = shift;
    my %params = @_; 

    my $X = XML::Generator->new(':pretty');

    my @devices_params = ();
    my $ldf = $params{'devices'}{'hostdev'};
    if(ref($ldf) eq 'HASH'){
        push(@devices_params,$self->get_hostdevdevice_xml($ldf));
    } elsif( ref($ldf) eq 'ARRAY'){
        for my $D (@$ldf){
            push(@devices_params,$self->get_hostdevdevice_xml($D));
        }
    }

    return wantarray() ? @devices_params : \@devices_params;
}

sub get_controllers_xml {
    my $self = shift;
    my %params = @_; 

    my $X = XML::Generator->new(':pretty');

    my @devices_params = ();
    my $ldf = $params{'devices'}{'controller'};
    if(ref($ldf) eq 'HASH'){
        push(@devices_params,$self->get_controller_xml($ldf));
    } elsif( ref($ldf) eq 'ARRAY'){
        for my $D (@$ldf){
            push(@devices_params,$self->get_controller_xml($D));
        }
    }

    return wantarray() ? @devices_params : \@devices_params;
}

sub get_channels_xml {
    my $self = shift;
    my %params = @_; 

    my $X = XML::Generator->new(':pretty');

    my @devices_params = ();
    my $ldf = $params{'devices'}{'channel'};
    if(ref($ldf) eq 'HASH'){
        push(@devices_params,$self->get_channel_xml($ldf));
    } elsif( ref($ldf) eq 'ARRAY'){
        for my $D (@$ldf){
            push(@devices_params,$self->get_channel_xml($D));
        }
    }

    return wantarray() ? @devices_params : \@devices_params;
}

sub load_kernel_params {
    my $self = shift;
    my %params = @_;

    if( $params{'location'} ){
        $params{'arch'} = $self->get_arch( %params );
        my ( $kernelfn,
                $initrdfn,
                $args) = $self->get_kernel( $params{'location'}, $params{'type'}, $params{'arch'}, $params{'distro'} );

        $params{'os'}{'kernel'} = $kernelfn;
        $params{'os'}{'initrd'} = $initrdfn;
        if( $params{'os'}{'cmdline'} ){
            $params{'os'}{'cmdline'} .= " " . $args;
        } else {
            $params{'os'}{'cmdline'} = $args;
        }
    }
    return wantarray() ? %params : \%params;
}

=item get_kernel

get kernel from location

    my ($kernel,$initrd,$extra) = VirtAgent->get_kernel( $location, $type, $arch, $distro );

=cut

sub get_kernel {
    my $self = shift;
    my ( $location, $type, $arch, $adistro ) = @_;
    my $TMP_DIR = $self->get_tmpdir(); 

    my ($kernelfn,$initrdfn,$args);

    my $kernel_file = ETVA::Utils::rand_tmpfile("$TMP_DIR/vmlinuz");
    my $initrd_file = ETVA::Utils::rand_tmpfile("$TMP_DIR/initrd.img");
    my $extras = "";

    my @distros = ( );

    if( !$adistro || ($adistro eq 'RedHatLike') ){
        push(@distros, 'RedHatLike', 'RedHat6Like');
        if( !$adistro ){
            push(@distros, 'UbuntuLike');
        }
    } else {
        push(@distros, $adistro);
    }
    

    for my $distro (@distros){

        my ($kernelpath,$initrdpath) = $self->get_kernelpath_ditro($distro,$type,$arch);

        if( $location =~ m/^http:/ ||
            $location =~ m/^ftp:/ ){
            # http or ftp source

            my $child;
            LWP::Simple::getstore("$location/$kernelpath",$kernel_file);
            LWP::Simple::getstore("$location/$initrdpath",$initrd_file);
            $extras = "text method=$location";
        } elsif( $location =~ m/^nfs:/ ){
            # nfs source
            my $tmpdir = ETVA::Utils::rand_tmpdir("$TMP_DIR/virtagent-tmpdir");
            my $ofs = ($location =~ m/nfs:\/\//)? 6: 4;
            my $nl = substr($location,$ofs);
            cmd_exec("mount","-o","ro",$nl,$tmpdir);
            copy("$tmpdir/$kernelpath",$kernel_file);
            copy("$tmpdir/$initrdpath",$initrd_file);
            cmd_exec("umount",$tmpdir);
            rmdir($tmpdir);
            $extras = "text method=nfs:$nl";
        } elsif( -d $location ){ 
            # is dir
            copy("$location/$kernelpath",$kernel_file);
            copy("$location/$initrdpath",$initrd_file);
            $extras = "text method=$location";
        } elsif( -b $location ){
            # is block device
            my $tmpdir = ETVA::Utils::rand_tmpdir("$TMP_DIR/virtagent-tmpdir");
            cmd_exec("mount","-o","ro",$location,$tmpdir);
            copy("$tmpdir/$kernelpath",$kernel_file);
            copy("$tmpdir/$initrdpath",$initrd_file);
            cmd_exec("umount",$tmpdir);
            rmdir($tmpdir);
            $extras = "text method=$location";
        } elsif( -e $location ){ 
            # is file
            my $tmpdir = ETVA::Utils::rand_tmpdir("$TMP_DIR/virtagent-tmpdir");
            cmd_exec("mount","-o","ro,loop",$location,$tmpdir);
            copy("$tmpdir/$kernelpath",$kernel_file);
            copy("$tmpdir/$initrdpath",$initrd_file);
            cmd_exec("umount",$tmpdir);
            rmdir($tmpdir);
            $extras = "text method=$location";
        }

        # if it fails lets try other distro paths
        if( (!-e "$kernel_file") && (!-e "$initrd_file") ){
            next;
        } else {
            last;
        }
    }
    return ( $kernel_file,$initrd_file,$extras );
}

=item get_bootdisk

get bootdisk from location

    my ($bootdisk) = VirtAgent->get_bootdisk( $location, $type, $arch, $distro );

=cut

sub get_bootdisk {
    my $self = shift;
    my ( $location, $type, $arch, $distro ) = @_;
    my $TMP_DIR = $self->get_tmpdir(); 

    my ($bootpath) = $self->get_bootpath_ditro($distro,$type,$arch);

    my $bootdisk = ETVA::Utils::rand_tmpfile("$TMP_DIR/boot.iso");
    if( $location =~ m/http:/ || $location =~ m/ftp:/ ){
        my $child;
        LWP::Simple::getstore("$location/$bootpath",$bootdisk);
    } elsif( $location =~ m/nfs:/ ){
        my $tmpdir = ETVA::Utils::rand_tmpdir("$TMP_DIR/virtagent-tmpdir");
        my $ofs = ($location =~ m/nfs:\/\//)? 6: 4;
        my $nl = substr($location,$ofs);
        cmd_exec("mount","-o","ro",$nl,$tmpdir);
        copy("$tmpdir/$bootpath",$bootdisk);
        cmd_exec("umount",$tmpdir);
        rmdir($tmpdir);
    } elsif( -d "$location" ){
        copy("$location/$bootpath",$bootdisk);
    } elsif( -b "$location" ){   # block device
        my $tmpdir = ETVA::Utils::rand_tmpdir("$TMP_DIR/virtagent-tmpdir");
        cmd_exec("mount","-o","ro",$location,$tmpdir);
        copy("$tmpdir/$bootpath",$bootdisk);
        cmd_exec("umount",$tmpdir);
        rmdir($tmpdir);
    } elsif( -e "$location" ){   # file
        my $tmpdir = ETVA::Utils::rand_tmpdir("$TMP_DIR/virtagent-tmpdir");
        cmd_exec("mount","-o","ro,loop",$location,$tmpdir);
        copy("$tmpdir/$bootpath",$bootdisk);
        cmd_exec("umount",$tmpdir);
        rmdir($tmpdir);
    }
    return wantarray() ? ($bootdisk) : $bootdisk;
}
sub get_kernelpath_ditro {
    my $self = shift;
    my ($distro,$type,$arch) = @_;
    my ($kpath, $ipath);
    if( $distro eq 'RedHatLike' or !$distro ){
        if( $type ){
            $kpath = sprintf 'images/%s/vmlinuz', $type;
            $ipath = sprintf 'images/%s/initrd.img', $type;
        } else {
            $kpath = "images/pxeboot/vmlinuz";
            $ipath = "images/pxeboot/initrd.img";
        }
    } elsif( $distro eq 'RedHat6Like' ){
        if( $type ){
            $kpath = 'isolinux/vmlinuz';
            $ipath = 'isolinux/initrd.img';
        } else {
            $kpath = "images/pxeboot/vmlinuz";
            $ipath = "images/pxeboot/initrd.img";
        }
    } elsif( $distro eq 'UbuntuLike' ){
        #ftp://.../main/installer-i386/current/images/netboot/xen/vmlinuz
        #ftp://.../main/installer-i386/current/images/netboot/xen/initrd.gz
        #ftp://.../main/installer-i386/current/images/netboot/ubuntu-installer/i386/linux
        #ftp://.../main/installer-i386/current/images/netboot/ubuntu-installer/i386/initrd.gz
        my $s_arch = $arch || '*';
        $s_arch = "amd64" if( $arch eq 'x86_64' );
        if( $type eq 'xen' ){
            $kpath = sprintf 'images/netboot/xen/vmlinuz';
            $ipath = sprintf 'images/netboot/xen/initrd.gz';
        } else {
            $kpath = sprintf 'images/netboot/ubuntu-installer/%s/linux', $s_arch;
            $ipath = sprintf 'images/netboot/ubuntu-installer/%s/initrd.gz', $s_arch;
        }
     } elsif( $distro eq 'DebianLike' ){
        #ftp://.../main/installer-i386/current/images/netboot/xen/vmlinuz
        #ftp://.../main/installer-i386/current/images/netboot/xen/initrd.gz
        #ftp://.../main/installer-i386/current/images/netboot/debian-installer/i386/linux
        #ftp://.../main/installer-i386/current/images/netboot/debian-installer/i386/initrd.gz
        my $s_arch = $arch || '*';
        $s_arch = "amd64" if( $arch eq 'x86_64' );
        if( $type eq 'xen' ){
            $kpath = sprintf 'images/netboot/xen/vmlinuz';
            $ipath = sprintf 'images/netboot/xen/initrd.gz';
        } else {
            $kpath = sprintf 'images/netboot/debian-installer/%s/linux', $s_arch;
            $ipath = sprintf 'images/netboot/debian-installer/%s/initrd.gz', $s_arch;
        }
    }
    # TODO other cases
    return ($kpath, $ipath);
}
sub get_bootpath_ditro {
    my $self = shift;
    my ($distro,$type,$arch) = @_;
    my ($bpath);
    if( $distro eq 'RedHatLike' or !$distro ){
        $bpath = "images/boot.iso";
    }
    # TODO other cases
    return ($bpath);
}
sub get_tmpdir {
    my $self = shift;
    my $tmpdir = "/tmp";

    if( $CONF->{'tmpdir'} ){
        $tmpdir = $CONF->{'tmpdir'};
    } elsif( $self->get_type() eq "xen" ){
        $tmpdir = "/var/lib/xen";
    } else {
        $tmpdir = "/var/tmp";
    }

    return $tmpdir;
}
sub get_arch {
    my $self = @_;
    my %params = @_;
    my $arch = $params{'arch'};
    if( !$arch ){
        if( !$node_arch ){
            open(A,"/bin/uname -p|");
            $arch = <A>;
            chomp $arch;
            close(A);
            if( !$arch or ( $arch eq "unknown" ) ){
                open(A,"/bin/uname -i|");
                $arch = <A>;
                chomp $arch;
                close(A);
            }
            $node_arch = $arch; # sync
        } else {
            $arch = $node_arch;
        }
    }
    return $arch;
}

sub retDomainInfo {
    my $dom = shift;
    
    return domainInfo($dom);
}

sub domainInfo {
    my $dom = shift;

    my ($info,$id,$uuid,$name,$state,$maxcpus,$maxmem);
    eval {
        $id = $dom->get_id();
        $uuid = $dom->get_uuid_string();
        $name = $dom->get_name();
#        $maxmem = $dom->get_max_memory();
        $info = $dom->get_info();
        $info->{'maxvcpus'} = $dom->get_max_vcpus();
        # state as string
        $state = "STATE_RUNNING" if( $info->{"state"} eq Sys::Virt::Domain::STATE_RUNNING );
        $state = "STATE_NOSTATE" if( $info->{"state"} eq Sys::Virt::Domain::STATE_NOSTATE );
        $state = "STATE_SHUTOFF" if( $info->{"state"} eq Sys::Virt::Domain::STATE_SHUTOFF );
        $state = "STATE_SHUTDOWN" if( $info->{"state"} eq Sys::Virt::Domain::STATE_SHUTDOWN );
        $state = "STATE_BLOCKED" if( $info->{"state"} eq Sys::Virt::Domain::STATE_BLOCKED );
        $state = "STATE_PAUSED" if( $info->{"state"} eq Sys::Virt::Domain::STATE_PAUSED );
        $state = "STATE_CRASHED" if( $info->{"state"} eq Sys::Virt::Domain::STATE_CRASHED );
    };
    if( $@ ){
        return retErr("_DOMAIN_INFO_","Can't get domain info: $@");
    }

    my %Info = ();
    $Info{'id'} = $id if( $id );
    $Info{'uuid'} = $uuid if( $uuid );
    $Info{'name'} = $name if( $name );
    $Info{'info'} = $info if( $info );
    $Info{'state'} = $state if( $state );
    if( $info->{'state'} eq Sys::Virt::Domain::STATE_RUNNING ){
        $Info{'os_type'} = $dom->get_os_type();
    }
    return wantarray() ? %Info : \%Info;
}
sub getDomainConfig {
    my $self = shift;

    my $dom = $self->getDomain(@_);
    # some go wrong to get domain
    if( isError($dom) ){
        return wantarray() ? %$dom : $dom;
    }

    my $xml;
    eval {
        $xml = $dom->get_xml_description();
    };
    if( $@ ){
    }
    my %D = $self->domain_xml_parser($xml);
    return wantarray() ? %D : \%D;
}

=item get_xml_domain

get xml description for domain

    my $xml = VirtAgent->get_xml_domain( name=>$name );

=cut

sub get_xml_domain {
    my $self = shift;

    my $dom = $self->getDomain(@_);
    # some go wrong to get domain
    if( isError($dom) ){
        return wantarray() ? %$dom : $dom;
    }

    my $xml;
    eval {
        $xml = $dom->get_xml_description();
    };
    if( $@ ){
        return retErr("_ERR_DOMAIN_XML_","Error: cant return xml domain");
    }
    return $xml;
}
sub domain_xml_parser {
    sub domain_xml_parser_rec {

        my ($node) = @_;
        my %D = ();
        for my $ch ($node->getChildNodes()){
            if( ref($ch) eq "XML::DOM::Text" ){
                my $v = $ch->toString();
                $v =~ s/^\s+$//;
                if( $v ){
                    $D{'_content_'} = $v;
                }
            } else {
                my $name = $ch->getNodeName();
                $D{"$name"} = domain_xml_parser_rec($ch);

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
    my ($self,$xml) = @_;
    my $parser = new XML::DOM::Parser();
    my $doc = $parser->parse($xml);
    my $root = $doc->getDocumentElement();
    my %D = domain_xml_parser_rec($root);

    # Avoid memory leaks - cleanup circular references for garbage collection
    $doc->dispose;

    return wantarray() ? %D : \%D;
}
sub domainXML {
    my $dom = shift;

    my $X = XML::Generator->new(':pretty');
    my @list = ();

    my %Info = domainInfo($dom);
    for my $ikey (keys %Info){
        push( @list, $X->$ikey( $Info{"$ikey"} ) ) if( $Info{"$ikey"} );
    }
    
    return $X->domain( @list );
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
