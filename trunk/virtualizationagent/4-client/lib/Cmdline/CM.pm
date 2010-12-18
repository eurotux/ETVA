#!/usr/bin/perl

=pod

=head1 NAME

Cmdline::CM

=head1 SYNOPSIS

...

=head1 DESCRIPTION

...

=head1 METHODS

=over 4

=cut

package Cmdline::CM;

use strict;

use Utils;
use Cmdline;

use Data::Dumper;
use JSON;

my $CONF_FILE = "/etc/sysconfig/etva-cmdline/cmdline.conf";

my %CONF;

sub _call {
    my ($method,@args) = @_;
    if( !%CONF ){
        _cmparms();
    } 

    my $client = new Cmdline( uri => $CONF{'cm_uri'}, debug=>$CONF{'debug'} );

    return $client-> call( $CONF{'cm_namespace'},
                            $method,@args );
}
sub _cmparms {
    my $conf_file = $ENV{"CFG_FILE"} || $CONF_FILE;

    %CONF = ();
    %CONF = loadconfigfile($conf_file,\%CONF);
}

sub _isSuccess {
    my (%r) = @_;

    if( $r{'return'} && ref($r{'return'}) && $r{'return'}{'success'} ){
        return 1;
    } else {
        return 0;
    }
}

=pod 

=begin comment

    my $l = [
          {
            'NetworkCards' => undef,
            'Memtotal' => 1582301184,
            'Id' => 1,
            'Port' => 7001,
            'Cputotal' => 2,
            'State' => 0,
            'Ip' => '10.10.20.79',
            'UpdatedAt' => '2009-09-22 15:47:18',
            'CreatedAt' => '2009-08-06 19:14:19',
            'Memfree' => 127422464,
            'servers' => [
                           {
                             'NetworkCards' => 13,
                             'Mem' => '512',
                             'Vcpu' => 0,
                             'State' => 'running',
                             'SfGuardGroupId' => 1,
                             'CreatedAt' => '2008-12-12 00:00:00',
                             'Cpuset' => '1',
                             'MacAddresses' => '15',
                             'Location' => 'f',
                             'AgentTmpl' => 'ETFW',
                             'Uid' => '8f1f6992-95f9-4ffc-b3ad-c2d80d07aab0',
                             'LogicalvolumeId' => 8,
                             'Id' => 1,
                             'AgentPort' => 7007,
                             'Ip' => '10.10.20.106',
                             'VncPort' => 5900,
                             'NodeId' => 1,
                             'UpdatedAt' => '2009-09-14 15:53:26',
                             'Description' => '5',
                             'Name' => 'bla'
                           }
                         ],
            'Name' => 'VirtAgent01',
            'Uid' => '030d25e8-ac6b-48c0-dae8-6cb94fd49abc'
          }
        ];
    my @list = @$l; 

=end comment

=cut

=item list

list of nodes on Central Management

=cut

sub list {

    my %r = _call("cli_nodeList");

    my $list = [];
    if( _isSuccess(%r) ){
        if( $r{'return'}{'response'} ){
            $list = $r{'return'}{'response'};
        }
    }
    return wantarray() ? @$list : $list;
}

sub get_node {
    my ($node) = @_;

    if( my @list = list() ){
        my ($Node) = grep { $_->{"Id"} eq $node ||
                            $_->{"Uid"} eq $node ||
                            $_->{"Name"} eq $node ||
                            $_->{"Ip"} eq $node } @list;
        return $Node;
    }
    return;
}

sub get_server {
    my ($node,$server) = @_;

    my @servers = servers($node);

    my ($Server) = grep { $_->{"Name"} eq $server ||
                            $_->{"Uid"} eq $server ||
                            $_->{"Id"} eq $server
                            } @servers;

    return $Server;
}

=item servers

list of virtual servers on node

    args: node - identification of node

=cut

sub servers {
    my ($node) = my (%p) = @_;
    $node = $p{"node"} if( $p{"node"} );

    my $servers = [];
    if( my $Node = get_node($node) ){
        if( $Node->{'servers'} ){
            $servers = $Node->{'servers'};
        }
    }
    return wantarray() ? @$servers : $servers;
}

=item servercreate

create virtual server on Central Management

    args: node - identification of node
          name - name of server
          network - network specification
          cpuset - 
          mem

=cut

sub servercreate {
    my ($node,$server) = my (%p) = @_;
    if( $p{"node"} || $p{"name"} || $p{"server"} ){
        $node = $p{"node"};
        $server = $p{"name"} || $p{"server"};
    }

    if( my $Node = get_node($node) ){
        my $agent_ip = $Node->{"Ip"};

        if( get_server($node,$p{"name"}) ){
            # TODO return error
            return;
        }
        if( !$p{"nettype"} ){
            $p{"nettype"} = "network";
        }
        if( ! defined $p{"cpuset"} ){
            $p{"cpuset"} = "0"; # first one by default
        }
        if( ! $p{"mem"} ){
            $p{"mem"} = "512";  # TODO return error
        }

        my %R = _call("cli_servercreate", agentIP=>$agent_ip,
                                            server=>{ lv=>$p{"lv"},
                                                        networks=>$p{"networks"},
                                                        nettype=>$p{"nettype"},
                                                        name=>$p{"name"},
                                                        ip=>$p{"ip"},
                                                        mem=>$p{"mem"},
                                                        cpuset=>$p{"cpuset"},
                                                        location=>$p{"location"}
                                                        } );
        if( _isSuccess(%R) ){
            # TODO return ok message
            return "ok";
        }
    }
    return;
}

=item serverstart

start server

    args: node - identification of node
          name - name of server

=cut

sub serverstart {
    my ($node,$server) = my (%p) = @_;
    if( $p{"node"} || $p{"name"} || $p{"server"} ){
        $node = $p{"node"};
        $server = $p{"name"} || $p{"server"};
    } else {
        ($node,$server) = (shift,shift);
        (%p) = @_;
    }

    if( my $Node = get_node($node) ){
        my $agent_ip = $Node->{"Ip"};

        if( my $Server = get_server($node,$server) ){
            my %R = _call("cli_serverstart", agentIP=>$agent_ip, server=>$Server->{"Name"} );
            if( _isSuccess(%R) ){
                # TODO return ok message
                return "ok";
            }
        }
    }
    return;
}

=item serverstop

stop server

    args: node - identification of node
          name - name of server

=cut

sub serverstop {
    my ($node,$server) = my (%p) = @_;
    if( $p{"node"} || $p{"name"} || $p{"server"} ){
        $node = $p{"node"};
        $server = $p{"name"} || $p{"server"};
    }

    if( my $Node = get_node($node) ){
        my $agent_ip = $Node->{"Ip"};

        if( my $Server = get_server($node,$server) ){
            my %R = _call("cli_serverstop", agentIP=>$agent_ip, server=>$Server->{"Name"} );
            if( _isSuccess(%R) ){
                # TODO return ok message
                return "ok";
            }
        }
    }
    return;
}

=item serverremove

remove server

    args: node - identification of node
          name - name of server

=cut

sub serverremove {
    my ($node,$server) = my (%p) = @_;
    if( $p{"node"} || $p{"name"} || $p{"server"} ){
        $node = $p{"node"};
        $server = $p{"name"} || $p{"server"};
    }

    if( my $Node = get_node($node) ){
        my $agent_ip = $Node->{"Ip"};

        if( my $Server = get_server($node,$server) ){
            my %R = _call("cli_serverremove", agentIP=>$agent_ip, server=>$Server->{"Name"} );
            if( _isSuccess(%R) ){
                # TODO return ok message
                return "ok";
            }
        }
    }
    return;
}

=item networkreplace

network replace

    args: node - identification of node
          name - name of server
          networks - networks to replace

=cut

sub networkreplace {
    my ($node,$server) = my (%p) = @_;
    if( $p{"node"} || $p{"name"} || $p{"server"} ){
        $node = $p{"node"};
        $server = $p{"name"} || $p{"server"};
    }

    if( my $Node = get_node($node) ){
        my $agent_ip = $Node->{"Ip"};

        if( my $Server = get_server($node,$p{"name"}) ){

            my %R = _call("cli_networkreplace", agentIP=>$agent_ip,
                                                server=>$Server->{"name"},
                                                networks=>$p{"networks"},
                                            );
            if( _isSuccess(%R) ){
                # TODO return ok message
                return "ok";
            }
        }
    }
    return;
}

=item networkdetach

detach network

    args: node - identification of node
          name - name of server
          macaddr - mac address of network to detach

=cut

sub networkdetach {
    my ($node,$server) = my (%p) = @_;
    if( $p{"node"} || $p{"name"} || $p{"server"} ){
        $node = $p{"node"};
        $server = $p{"name"} || $p{"server"};
    }

    if( my $Node = get_node($node) ){
        my $agent_ip = $Node->{"Ip"};

        if( my $Server = get_server($node,$p{"name"}) ){

            my %R = _call("cli_networkdetach", agentIP=>$agent_ip,
                                                server=>$Server->{"name"},
                                                macaddr=>$p{"macaddr"},
                                            );
            if( _isSuccess(%R) ){
                # TODO return ok message
                return "ok";
            }
        }
    }
    return;
}

=item pvlist

list physical volumes

    args: node - identification of node

=cut

sub pvlist {
    my ($node) = my (%p) = @_;
    $node = $p{"node"} if( $p{"node"} );

    my $list = [];
    if( my $Node = get_node($node) ){
        
        my %r = _call("cli_pvList", 'agentIP'=>$Node->{'Ip'} );

        if( _isSuccess(%r) ){
            if( my $hash = $r{'return'}{'response'} ){
                my @values = values %$hash;
                $list = \@values; 
            }
        }
    }
    return wantarray() ? @$list : $list;
}

=item pvcreate

create physical volume

    args: node - identification of node
          device - physical device

=cut

sub pvcreate {
    my ($node) = my (%p) = @_;
    $node = $p{"node"} if( $p{"node"} );

    if( my $Node = get_node($node) ){
        
        my $device = $p{"device"};
        if( -b "$device" ){
            my %r = _call("cli_pvcreate", 'agentIP'=>$Node->{'Ip'}, 'device'=>$device );

            if( _isSuccess(%r) ){
                return "ok";
            }
        }
    }
    return;
}

=item pvremove

remove physical volume

    args: node - identification of node
          device - physical device

=cut

sub pvremove {
    my ($node) = my (%p) = @_;
    $node = $p{"node"} if( $p{"node"} );

    if( my $Node = get_node($node) ){
        
        my $device = $p{"device"};
        if( -b "$device" ){
            my %r = _call("cli_pvremove", 'agentIP'=>$Node->{'Ip'}, 'device'=>$device );

            if( _isSuccess(%r) ){
                return "ok";
            }
        }
    }
    return;
}

=item pvfree

list free to allocatable physical volumes

    args: node - identification of node

=cut

sub pvfree {
    my ($node) = my (%p) = @_;
    $node = $p{"node"} if( $p{"node"} );

    my $list = [];
    if( my $Node = get_node($node) ){
        
        my %r = _call("cli_pvListAllocatable", 'agentIP'=>$Node->{'Ip'} );

        if( _isSuccess(%r) ){
            if( my $hash = $r{'return'}{'response'} ){
                my @values = values %$hash;
                $list = \@values; 
            }
        }
    }
    return wantarray() ? @$list : $list;
}

=item vglist

list volumes group for node

    args: node - identification of node

=cut

sub vglist {
    my ($node) = my (%p) = @_;
    $node = $p{"node"} if( $p{"node"} );

    my $list = [];
    if( my $Node = get_node($node) ){
        
        my %r = _call("cli_vgList", 'agentIP'=>$Node->{'Ip'} );

        if( _isSuccess(%r) ){
            if( my $hash = $r{'return'}{'response'} ){
                my @values = values %$hash;
                $list = \@values; 
            }
        }
    }
    return wantarray() ? @$list : $list;
}

=item vgupdate

update or create volume group with list of physical volumes

    args: node - identification of node
          vgname - name of volume group
          pvs - list of physical volumes

=cut

sub vgupdate {
    my ($node) = my (%p) = @_;
    $node = $p{"node"} if( $p{"node"} );

    if( my $Node = get_node($node) ){
        
        my $vgname = $p{"vgname"};
        my $pvs = $p{"pvs"};
        if( $vgname && $pvs && scalar(@$pvs) ){
            my %r = _call("cli_vgupdate", 'agentIP'=>$Node->{'Ip'}, 'volume_group'=>$vgname, 'physicalvols'=>$pvs );

            if( _isSuccess(%r) ){
                return "ok";
            }
        }
    }
    return;
}

=item vgreduce

remove one or more physical volumes from volume group

    args: node - identification of node
          vgname - name of volume group
          pvs - list of physical volumes

=cut

sub vgreduce {
    my ($node) = my (%p) = @_;
    $node = $p{"node"} if( $p{"node"} );

    if( my $Node = get_node($node) ){
        
        my $vgname = $p{"vgname"};
        my $pvs = $p{"pvs"};
        if( $vgname && $pvs && scalar(@$pvs) ){
            my %r = _call("cli_vgreduce", 'agentIP'=>$Node->{'Ip'}, 'volume_group'=>$vgname, 'physicalvols'=>$pvs );

            if( _isSuccess(%r) ){
                return "ok";
            }
        }
    }
    return;
}

=item vgremove

delete volume group

    args: node - identification of node
          vgname - name of volume group

=cut

sub vgremove {
    my ($node) = my (%p) = @_;
    $node = $p{"node"} if( $p{"node"} );

    if( my $Node = get_node($node) ){
        
        my $vgname = $p{"vgname"};
        if( $vgname ){
            if( ! grep { $_->{'vg'} eq $vgname } vglist()){
                return;
            }
            my %r = _call("cli_vgremove", 'agentIP'=>$Node->{'Ip'}, 'volume_group'=>$vgname );

            if( _isSuccess(%r) ){
                return "ok";
            }
        }
    }
    return;
}

=item lvlist

list logical volumes

    args: node - identification of node

=cut

sub lvlist {
    my ($node) = my (%p) = @_;
    $node = $p{"node"} if( $p{"node"} );

    my $list = [];
    if( my $Node = get_node($node) ){
        
        my %r = _call("cli_lvList", 'agentIP'=>$Node->{'Ip'} );

        if( _isSuccess(%r) ){
            if( my $hash = $r{'return'}{'response'} ){
                my @values = values %$hash;
                $list = \@values; 
            }
        }
    }
    return wantarray() ? @$list : $list;
}

=item lvcreate

create logical volume

    args: node - identification of node
          lvname - name of logical volume
          vgname - name of volume group
          size - size of volume

=cut

sub lvcreate {
    my ($node) = my (%p) = @_;
    $node = $p{"node"} if( $p{"node"} );

    if( my $Node = get_node($node) ){
        
        my $lvname = $p{"lvname"};
        my $vgname = $p{"vgname"};
        my $size = $p{"size"};
        if( $vgname && $lvname ){
            if( ! grep { $_->{'vg'} eq $vgname } vglist( $node ) ){
                return;
            }
            if( grep { $_->{'lv'} eq $lvname } lvlist( $node ) ){
                return;
            }
            my %r = _call("cli_lvcreate", 'agentIP'=>$Node->{'Ip'}, 'logical_volume'=>$lvname, 'volume_group'=>$vgname, size=>$size );

            if( _isSuccess(%r) ){
                return "ok";
            }
        }
    }
    return;
}

=item lvresize

resize logical volume

    args: node - identification of node
          lvname - name of logical volume
          size - size of volume

=cut

sub lvresize {
    my ($node) = my (%p) = @_;
    $node = $p{"node"} if( $p{"node"} );

    if( my $Node = get_node($node) ){
        
        my $lvname = $p{"lvname"};
        my $size = $p{"size"};
        if( $lvname ){
            if( !grep { $_->{'lv'} eq $lvname } lvlist( $node ) ){
                return;
            }
            my %r = _call("cli_lvresize", 'agentIP'=>$Node->{'Ip'}, 'logical_volume'=>$lvname, size=>$size );

            if( _isSuccess(%r) ){
                return "ok";
            }
        }
    }
    return;
}

=item lvremove

remove logical volume 

    args: node - identification of node
          lvname - name of logical volume

=cut

sub lvremove {
    my ($node) = my (%p) = @_;
    $node = $p{"node"} if( $p{"node"} );

    if( my $Node = get_node($node) ){
        
        my $lvname = $p{"lvname"};
        if( $lvname ){
            if( !grep { $_->{'lv'} eq $lvname } lvlist( $node ) ){
                return;
            }
            my %r = _call("cli_lvremove", 'agentIP'=>$Node->{'Ip'}, 'logical_volume'=>$lvname );

            if( _isSuccess(%r) ){
                return "ok";
            }
        }
    }
    return;
}

=item vlanlist

list virtual networks

=cut

sub vlanlist {
    my (%p) = @_;

    my $list = [];

    my %r = _call("cli_vlanList");
    if( _isSuccess(%r) ){
        if( $r{'return'}{'response'} ){
            $list = $r{'return'}{'response'}; 
        }
    }
    return wantarray() ? @$list : $list;
}

=item vlancreate

create virtual network

    args: vlan - name of virtual network

=cut

sub vlancreate {
    my (%p) = @_;

    my $vlan = $p{"vlan"};
    if( $vlan ){
        if( grep { $_->{'vlan'} eq $vlan } vlanlist() ){
            return;
        }
        my %r = _call("cli_vlancreate", 'vlan'=>$vlan );

        if( _isSuccess(%r) ){
            return "ok";
        }
    }
    return;
}

=item vlanremove

remove virtual network

    args: vlan - name of virtual network

=cut

sub vlanremove {
    my (%p) = @_;

    my $vlan = $p{"vlan"};
    if( $vlan ){
        if( ! grep { $_->{'vlan'} eq $vlan } vlanlist() ){
            return;
        }
        my %r = _call("cli_vlanremove", 'vlan'=>$vlan );

        if( _isSuccess(%r) ){
            return "ok";
        }
    }
    return;
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


