#!/usr/bin/perl

package Cmdline::Shell;

use strict;

use Cmdline::CM;

use Text::Reform;
use Pod::XPath;

BEGIN {
    require Term::Shell;
    use vars qw($VERSION @ISA @EXPORT @EXPORT_OK %EXPORT_TAGS $CRLF $AUTOLOAD);
    use base qw(Term::Shell);
};

my $NODE;
my $Node_name;

my %AliasFuncs = ();
my %AliasCmds = ();

sub new {
    my $self = shift;

    my $pkg = ref($self) || $self;

    {
        no strict 'refs';

        my %smry_handlers = ();
        my %help_handlers = ();
        my %run_handlers = ();
        for (keys %{ $pkg . "::" }){
            if( /^smry_/ ){
                $smry_handlers{"$_"} = 1;
            } elsif( /^help_/ ){
                $help_handlers{"$_"} = 1;
            } elsif( /^run_/ ){
                $run_handlers{"$_"} = 1;
            }
        }

        for my $rh (keys %run_handlers){
            my ($cmd) = ( $rh =~ m/run_(\S+)/ );
            my $sh = "smry_$cmd";
            my $hh = "help_$cmd";
            if( !$smry_handlers{$sh} ){
                *$sh = sub {
                        return get_textsmry($cmd);
                        };
            }
            if( !$help_handlers{$hh} ){
                *$hh = sub {
                        my $str_help = get_texthelp($cmd);
                        return <<END;
Help on '$cmd', $str_help
END
                        };
            }
        }
    }

    $self = $self->SUPER::new(@_);

    return $self;
}

# create alias func
sub mkalias {
    my ($fo,$fn) = @_;
    my ($co) = ( $fo =~ m/run_(\S+)/ );
    my ($cn) = ( $fn =~ m/run_(\S+)/ );
    $AliasFuncs{"$fn"} = $fo;
    $AliasCmds{"$cn"} = $co;
    {
        no strict 'refs';
        *$fn = \&$fo;
    }
}

=item get_textsmry

get summary text from POD of Cmdline::CM module

=cut

my %SmryText = ();

sub get_textsmry {
    my ($method) = @_;
    my $txt = $SmryText{"$method"};
    if( !$txt){
        $txt = Pod::XPath->new("Cmdline::CM")->find('/pod/sect1[title/text() = "METHODS"]/list/item[itemtext/text() = "'.$method.'"]/*[2]/text()');
        if( !$txt && $AliasCmds{"$method"} ){
            $txt = get_textsmry($AliasCmds{"$method"});
            $txt .= " ( " . $AliasCmds{"$method"} . " alias )" if ( $txt );
        }
        $txt =~ s/[\r\n]//gs;
        $SmryText{"$method"} = $txt;
    }
    return $txt;
}

=item get_texthelp

get help text from POD of Cmdline::CM module

=cut

my %HelpText = ();

sub get_texthelp {
    my ($method) = @_;
    my $txt = $HelpText{"$method"};
    if( !$txt ){
        $txt = "";
        my $item_nodeset = Pod::XPath->new("Cmdline::CM")->find('/pod/sect1[title/text() = "METHODS"]/list/item[itemtext/text() = "'.$method.'"]/*');
        my $c=0;
        for my $item ($item_nodeset->get_nodelist()){
            if( $c>0 ){
                $txt .= $item->find("text()");
            }
            $c++;
        }
        if( !$txt && $AliasCmds{"$method"} ){
            $txt = get_texthelp($AliasCmds{"$method"});
        }
        $HelpText{"$method"} = $txt;
    }
    
    return $txt;
}

# print functions
sub print_header {
    sub print_header_el {
        my ($E) = @_;

        my $dl = $E->{"maxlength"} > $E->{"length"} ? $E->{"maxlength"} - $E->{"length"} : 0;
        my $hdl1 = int($dl / 2);
        my $hdl2 = $dl - $hdl1;
        my $str = ( " " x $hdl1 ) . $_->{"title"} . ( " " x $hdl2 );

        return $str;
    }

    my @list = @_;

    return " " . join("   ",map { print_header_el($_) } @list ) . " ";
}

sub print_format {
    my @list = @_;
    
    return " " . join("   ",map { ( "[" x $_->{"maxlength"} ) } @list ) . " ";
}

sub print_table {
    my ($title,$H,$L) = @_;

    my %V = ();
    my @data = ();
    my @keys = ();
    while(@$H){
        my $k = shift(@$H);
        my $t = shift(@$H);
        $V{"$k"}{"title"} = $t;
        $V{"$k"}{"length"} = length($t);
        push(@keys,$k);
    }

    for my $E (@$L){
        my @values = ();
        for my $k (@keys){
            my $v = $E->{"$k"};
            my $lv = length($v);
            $V{"$k"}{"maxlength"} = $lv if( $lv > $V{"$k"}{"maxlength"} );
            $V{"$k"}{"maxlength"} = $V{"$k"}{"length"} if( $V{"$k"}{"length"} > $V{"$k"}{"maxlength"} );
            push(@values,$v);
        }
        push(@data,\@values);
    }

    my @lv = map { $V{"$_"} } @keys;
    my @cols = map { $V{"$_"}{"title"} } @keys;
    my $header = print_header( @lv ); 
    my $line = "-" x length($header); 
    my $format = print_format( @lv );

    print $title,"\n";
    print "\n\n";
    print form
                $header,
                $line,
                $format,
                { cols => [ 0 .. scalar(@keys) ],
                    from => \@data };
    print "\n\n";
}

# get from input node args
sub mkargs_node {
    my $self = shift;

    my %args = Cmdline->splitOps(@_);

    # node
    my $node = $args{"node"} || $_[0] || $NODE;
    if( !$node ||
            !Cmdline::CM::get_node($node) ){
        my $ok_node = 0;
        while( !$ok_node ){
            if( $node && !Cmdline::CM::get_node($node) ){
                print "Invalid node!\n";
            } elsif( $node ){
                last;
            }
            $node = $self->prompt("Type node or (l)ist: ");
            if( $node eq "l" ){
                $node = "";
                $self->run("list");
            }
        }
    }
    $args{"node"} = $node;

    return %args;
}

# get from input server args
sub mkargs_server {
    my $self = shift;

    my %args = Cmdline->splitOps(@_);

    # node
    my $node = $args{"node"} || $_[0] || $NODE;
    if( !$node ||
            !Cmdline::CM::get_node($node) ){
        my $ok_node = 0;
        while( !$ok_node ){
            if( $node && !Cmdline::CM::get_node($node) ){
                print "Invalid node!\n";
            } elsif( $node ){
                last;
            }
            $node = $self->prompt("Type node or (l)ist: ");
            if( $node eq "l" ){
                $node = "";
                $self->run("list");
            }
        }
    }
    $args{"node"} = $node;
    
    # server name
    my $name = $args{"name"} || $_[1];
    if( !$name ||
        !Cmdline::CM::get_server($node,$name) ){
        my $ok_name = 0;
        while( !$ok_name ){
            if( $name && !Cmdline::CM::get_server($node,$name) ){
                print "Invalid server!\n";
            } elsif( $name ){
                last;
            }
            $name = $self->prompt("Type server name or (s)erver to list them: ");
            if( $name eq "s" ){
                $name = "";
                $self->run("servers",$node);
            }
        }
    }
    $args{"name"} = $name;

    return %args;
}

# choose node
sub run_choose {
    my $self = shift;
    $NODE = "";
    my %args = $self->mkargs_node(@_);
    my $N = Cmdline::CM::get_node($args{"node"});
    $NODE = $N->{"Uid"};
    $Node_name = $N->{"Name"};

    print "Node '$Node_name' choosed.","\n";

    # redifine prompt_str
    *prompt_str = sub { "$Node_name> "; };
}
# summary choose
sub smry_choose { "choose node" }
# help choose
sub help_choose { 
        return <<END;
Help on 'choose', choose node
END
}

# invoke list of nodes
sub run_list {
    my $self = shift;

    my @list = Cmdline::CM::list(@_);

    print_table( "nodes:",
                    [ 'Id'=>"Id",
                    'Name'=>"Name",
                    'Ip'=>"Ip",
                    'Port'=>"Port",
                    'Uid'=>"UUID"
                    ],
                    \@list );
}
# invoke list of servers
sub run_servers {
    my $self = shift;

    my %args = $self->mkargs_node(@_);

    my @list = Cmdline::CM::servers(%args);

    print_table( "servers:",
                    [ 'Id'=>"Id",
                    'Name'=>"Name",
                    'Ip'=>"Ip",
                    'Uid'=>"UUID"
                    ],
                    \@list );
}
# get from input args for create server
sub mkargs_servercreate {
    my $self = shift;

    my %args = Cmdline->splitOps(@_);

    # node
    my $node = $args{"node"} || $_[0] || $NODE;
    if( !$node ||
            !Cmdline::CM::get_node($node) ){
        my $ok_node = 0;
        while( !$ok_node ){
            if( $node && !Cmdline::CM::get_node($node) ){
                print "Invalid node!\n";
            } elsif( $node ){
                last;
            }
            $node = $self->prompt("Type node or (l)ist: ");
            if( $node eq "l" ){
                $node = "";
                $self->run("list");
            }
        }
    }
    $args{"node"} = $node;
    
    # server name
    my $name = $args{"name"} || $_[1];
    if( !$name ||
        Cmdline::CM::get_server($node,$name) ){
        my $ok_name = 0;
        while( !$ok_name ){
            if( $name && Cmdline::CM::get_server($node,$name) ){
                print "Server name already exists! Type (s)ervers to list them.\n";
            } elsif( $name ){
                last;
            }
            $name = $self->prompt("Type server name: ");
            if( $name eq "s" ){
                $name = "";
                $self->run("servers",$node);
            }
        }
    }
    $args{"name"} = $name;
    
    my $networks = $args{"networks"};

    if( !$networks ){
        my $res = $self->prompt("Want specify networks? (y)es/(n)o ");
        if( $res eq "y" ){
            my @Networks = ();
            my $ok_net = 0;
            print "Please specify parameteres for each network or (e)xit","\n";
            while( !$ok_net ){
                my %Network = ();
                if( my $vlan = $self->prompt("Vlan name or (l)ist available: ") ){
                    last if( $vlan eq "e" );
                    if( $vlan eq "l" ){
                        $self->run("vlanlist");
                        next;
                    }
                    $Network{"vlan"} = $vlan;
                }
                if( my $mac = $self->prompt("MAC address: ") ){
                    last if( $mac eq "e" );
                    # TODO ask for mac address availables
                    $Network{"mac"} = $mac;
                }
                if( my $port = $self->prompt("VNC Port: ") ){
                    last if( $port eq "e" );
                    $Network{"port"} = $port;
                }

                if( %Network ){
                    push(@Networks,\%Network);
                }
            }
            $networks = $args{"networks"} = \@Networks;
        }
    } elsif( ! ref($networks) ){
        my $str_networks = delete($args{"networks"});
        my @Networks = ();
        for my $snet ( split(/;/,$str_networks) ){
            my %Network = ();
            for my $sparm ( split(/,/,$snet) ){
                my ($p,$v) = split(/=/,$sparm,2);
                $Network{"$p"} = $v;
            }
            push(@Networks,\%Network);
        } 
        $networks = $args{"networks"} = \@Networks;
    }
    
    my $lv = $args{"lv"};
    if( !$lv ||
        !grep { $_->{'lv'} eq $lv } Cmdline::CM::lvlist( $node ) ){
        my $ok_lv = 0;
        while( !$ok_lv ){
            if( $lv && !grep { $_->{'lv'} eq $lv } Cmdline::CM::lvlist( $node ) ){
                print "Invalid logical volume\n";
            } elsif( $lv ){
                last;
            }
            $lv = $self->prompt("Type lv or (l)vs to list them: ");
            if( $lv eq "l" ){
                $lv = "";
                $self->run("lvlist", $node);
            }
        }
    }
    $args{"lv"} = $lv;

    if( !$args{"ip"} || !$args{"mem"} || ! defined($args{"cpuset"}) || !$args{"location"} ){
        my $res = $self->prompt("Want specify anything else? (y)es/(n)o ");
        if( $res eq "y" ){
            
            if( !$args{"ip"} ){
                $args{"ip"} = $self->prompt("Ip: ");
            }
            
            if( !$args{"mem"} ){
                $args{"mem"} = $self->prompt("Memory: ");
            }

            if( ! defined($args{"cpuset"}) ){
                $args{"cpuset"} = $self->prompt("CPU set: ");
            }

            if( !$args{"location"} ){
                $args{"location"} = $self->prompt("Location: ");
            }
        }
    }

    return %args;
}
# invoke server creation method
sub run_servercreate {
    my $self = shift;

    my %args = $self->mkargs_servercreate( @_ );

    my $ok = Cmdline::CM::servercreate(%args);
    if( $ok ){
        print "ok\n";
    } else {
        print "nok\n";
    }
}
# invoke server start
sub run_serverstart {
    my $self = shift;

    my %args = $self->mkargs_server( @_ );

    my $ok = Cmdline::CM::serverstart(%args);
    if( $ok ){
        print "ok\n";
    } else {
        print "nok\n";
    }
}
# invoke server stop
sub run_serverstop {
    my $self = shift;

    my %args = $self->mkargs_server( @_ );

    my $ok = Cmdline::CM::serverstop(%args);
    if( $ok ){
        print "ok\n";
    } else {
        print "nok\n";
    }
}
# invoke server remove
sub run_serverremove {
    my $self = shift;

    my %args = $self->mkargs_server( @_ );

    my $ok = Cmdline::CM::serverremove(%args);
    if( $ok ){
        print "ok\n";
    } else {
        print "nok\n";
    }
}

# get from input network params to replace
sub mkargs_networkreplace {
    my $self = shift;

    my %args = Cmdline->splitOps(@_);

    # node
    my $node = $args{"node"} || $_[0] || $NODE;
    if( !$node ||
            !Cmdline::CM::get_node($node) ){
        my $ok_node = 0;
        while( !$ok_node ){
            if( $node && !Cmdline::CM::get_node($node) ){
                print "Invalid node!\n";
            } elsif( $node ){
                last;
            }
            $node = $self->prompt("Type node or (l)ist: ");
            if( $node eq "l" ){
                $node = "";
                $self->run("list");
            }
        }
    }
    $args{"node"} = $node;
    
    # server name
    my $name = $args{"name"} || $_[1];
    if( !$name ||
        !Cmdline::CM::get_server($node,$name) ){
        my $ok_name = 0;
        while( !$ok_name ){
            if( $name && !Cmdline::CM::get_server($node,$name) ){
                print "Invalid server!\n";
            } elsif( $name ){
                last;
            }
            $name = $self->prompt("Type server name or (s)erver to list them: ");
            if( $name eq "s" ){
                $name = "";
                $self->run("servers",$node);
            }
        }
    }
    $args{"name"} = $name;
    
    # get network params
    my $networks = $args{"networks"};
    #  nothing defined... ask
    if( !$networks ){
        my $res = $self->prompt("Want specify networks? (y)es/(n)o ");
        if( $res eq "y" ){
            my @Networks = ();
            my $ok_net = 0;
            print "Please specify parameteres for each network or (e)xit","\n";
            while( !$ok_net ){
                my %Network = ();
                if( my $vlan = $self->prompt("Vlan name: ") ){
                    last if( $vlan eq "e" );
                    $Network{"vlan"} = $vlan;
                }
                if( my $mac = $self->prompt("MAC address: ") ){
                    last if( $mac eq "e" );
                    $Network{"mac"} = $mac;
                }
                if( my $port = $self->prompt("Port: ") ){
                    last if( $port eq "e" );
                    $Network{"port"} = $port;
                }

                if( %Network ){
                    push(@Networks,\%Network);
                }
            }
            $networks = $args{"networks"} = \@Networks;
        }
    } elsif( ! ref($networks) ){
        # defined as string ... process it
        my $str_networks = delete($args{"networks"});
        my @Networks = ();
        for my $snet ( split(/;/,$str_networks) ){
            my %Network = ();
            for my $sparm ( split(/,/,$snet) ){
                my ($p,$v) = split(/=/,$sparm,2);
                $Network{"$p"} = $v;
            }
            push(@Networks,\%Network);
        } 
        $networks = $args{"networks"} = \@Networks;
    }
    
    return %args;
}
# invoke network replace
sub run_networkreplace {
    my $self = shift;

    my %args = $self->mkargs_networkreplace( @_ );

    my $ok = Cmdline::CM::networkreplace(%args);
    if( $ok ){
        print "ok\n";
    } else {
        print "nok\n";
    }
}
# get from input params of network to detach
sub mkargs_networkdetach {
    my $self = shift;

    my %args = Cmdline->splitOps(@_);

    # node
    my $node = $args{"node"} || $_[0] || $NODE;
    if( !$node ||
            !Cmdline::CM::get_node($node) ){
        my $ok_node = 0;
        while( !$ok_node ){
            if( $node && !Cmdline::CM::get_node($node) ){
                print "Invalid node!\n";
            } elsif( $node ){
                last;
            }
            $node = $self->prompt("Type node or (l)ist: ");
            if( $node eq "l" ){
                $node = "";
                $self->run("list");
            }
        }
    }
    $args{"node"} = $node;
    
    # server name
    my $name = $args{"name"} || $_[1];
    if( !$name ||
        !Cmdline::CM::get_server($node,$name) ){
        my $ok_name = 0;
        while( !$ok_name ){
            if( $name && !Cmdline::CM::get_server($node,$name) ){
                print "Invalid server!\n";
            } elsif( $name ){
                last;
            }
            $name = $self->prompt("Type server name or (s)erver to list them: ");
            if( $name eq "s" ){
                $name = "";
                $self->run("servers",$node);
            }
        }
    }
    $args{"name"} = $name;
    
    # mac address
    if( !$args{"macaddr"} ){
        $args{"macaddr"} = $self->prompt("MAC Address: ");
    }
    return %args;
}
# invoke network detach
sub run_networkdetach {
    my $self = shift;

    my %args = $self->mkargs_networkdetach( @_ );

    my $ok = Cmdline::CM::networkdetach(%args);
    if( $ok ){
        print "ok\n";
    } else {
        print "nok\n";
    }
}
# run physical volumes list
mkalias("run_pvlist","run_pvs");
sub run_pvlist {
    my $self = shift;

    my %args = $self->mkargs_node(@_);

    my @list = Cmdline::CM::pvlist(%args);

    print_table( "pvs:",
                    [ 'id'=>"Id",
                    'pv'=>"PVolume"
                    ],
                    \@list );
}
# get from input params to create physical volume
sub mkargs_pvcreate {
    my $self = shift;

    my %args = Cmdline->splitOps(@_);

    # node
    my $node = $args{"node"} || $_[0] || $NODE;
    if( !$node ||
            !Cmdline::CM::get_node($node) ){
        my $ok_node = 0;
        while( !$ok_node ){
            if( $node && !Cmdline::CM::get_node($node) ){
                print "Invalid node!\n";
            } elsif( $node ){
                last;
            }
            $node = $self->prompt("Type node or (l)ist: ");
            if( $node eq "l" ){
                $node = "";
                $self->run("list");
            }
        }
    }
    $args{"node"} = $node;

    # get device
    # TODO validate device
    if( !$args{"device"} ){
        $args{"device"} = $self->prompt("Device: ");
    }

    return %args;
}
# invoke physical volume create method
sub run_pvcreate {
    my $self = shift;

    my %args = $self->mkargs_choosepv( @_ );

    my $ok = Cmdline::CM::pvcreate(%args);
    if( $ok ){
        print "ok\n";
    } else {
        print "nok\n";
    }
}
# get from input params of physical volume to remove
sub mkargs_pvremove {
    my $self = shift;

    my %args = Cmdline->splitOps(@_);

    # node
    my $node = $args{"node"} || $_[0] || $NODE;
    if( !$node ||
            !Cmdline::CM::get_node($node) ){
        my $ok_node = 0;
        while( !$ok_node ){
            if( $node && !Cmdline::CM::get_node($node) ){
                print "Invalid node!\n";
            } elsif( $node ){
                last;
            }
            $node = $self->prompt("Type node or (l)ist: ");
            if( $node eq "l" ){
                $node = "";
                $self->run("list");
            }
        }
    }
    $args{"node"} = $node;

    # device name
    my $device = $args{"device"} || $_[1];
    if( !$device ||
        !grep { $_->{'pv'} eq $device } Cmdline::CM::pvfree( $node ) ){
        my $ok_device = 0;
        while( !$ok_device ){
            if( $device && !grep { $_->{'pv'} eq $device } Cmdline::CM::pvfree( $node ) ){
                print "Invalid physical volume to remove!\n";
            } elsif( $device ){
                last;
            }
            $device = $self->prompt("Type device or (p)vs to list them: ");
            if( $device eq "p" ){
                $device = "";
                $self->run("pvfree",$node);
            }
        }
    }
    $args{"device"} = $device;

    return %args;
}
# invoke physical volume remove
sub run_pvremove {
    my $self = shift;

    my %args = $self->mkargs_choosepv( @_ );

    my $ok = Cmdline::CM::pvremove(%args);
    if( $ok ){
        print "ok\n";
    } else {
        print "nok\n";
    }
}
# invoke method to show physical volume available to alocate
sub run_pvfree {
    my $self = shift;

    my %args = $self->mkargs_node(@_);

    my @list = Cmdline::CM::pvfree(%args);

    print_table( "pvs:",
                    [ 'id'=>"Id",
                    'pv'=>"PVolume"
                    ],
                    \@list );
}
# invoke list of volume groups
mkalias("run_vglist","run_vgs");
sub run_vglist {
    my $self = shift;

    my %args = $self->mkargs_node(@_);

    my @list = Cmdline::CM::vglist(%args);

    print_table( "vgs:",
                    [ 'id'=>"Id",
                    'vg'=>"VGroup"
                    ],
                    \@list );
}
# get from input params for volume group update/create
sub mkargs_vgupdate {
    my $self = shift;

    my %args = Cmdline->splitOps(@_);

    # node
    my $node = $args{"node"} || $_[0] || $NODE;
    if( !$node ||
            !Cmdline::CM::get_node($node) ){
        my $ok_node = 0;
        while( !$ok_node ){
            if( $node && !Cmdline::CM::get_node($node) ){
                print "Invalid node!\n";
            } elsif( $node ){
                last;
            }
            $node = $self->prompt("Type node or (l)ist: ");
            if( $node eq "l" ){
                $node = "";
                $self->run("list");
            }
        }
    }
    $args{"node"} = $node;

    # vg name
    my $vg = $args{"vgname"} || $_[1];
    while( !$vg ){
        $vg = $self->prompt("Type vg or (v)gs to list them: ");
        if( $vg eq "v" ){
            $vg = "";
            $self->run("vglist",$node);
        }
    }
    $args{"vgname"} = $vg;

    # get physical volumes
    my $pvs = $args{"pvs"};
    if( !$pvs ){
        my @pvs = ();
        my $ok_net = 0;
        print "Please specify physical volumes or (e)xit or (l) to list available","\n";
        while( !$ok_net ){
            my $pv;
            if( $pv = $self->prompt("Physical volume: ") ){
                if( $pv eq "e" ){
                    last;
                } elsif( $pv eq "l" ){
                    $self->run("pvlist", $node);
                } else {
                    push(@pvs,$pv);
                }
            }
        }
        $pvs = $args{"pvs"} = \@pvs;
    } elsif( ! ref($pvs) ){
        # in string case...
        my $str_pvs = delete($args{"pvs"});
        my @pvs = split(/,/,$str_pvs);
        $pvs = $args{"pvs"} = \@pvs;
    }
    return %args;
}
# invoke volume group update/create
sub run_vgupdate {
    my $self = shift;

    my %args = $self->mkargs_vgupdate( @_ );

    my $ok = Cmdline::CM::vgupdate(%args);
    if( $ok ){
        print "ok\n";
    } else {
        print "nok\n";
    }
}
# get from input params to reduce volume group
sub mkargs_vgreduce {
    my $self = shift;

    my %args = Cmdline->splitOps(@_);

    # node
    my $node = $args{"node"} || $_[0] || $NODE;
    if( !$node ||
            !Cmdline::CM::get_node($node) ){
        my $ok_node = 0;
        while( !$ok_node ){
            if( $node && !Cmdline::CM::get_node($node) ){
                print "Invalid node!\n";
            } elsif( $node ){
                last;
            }
            $node = $self->prompt("Type node or (l)ist: ");
            if( $node eq "l" ){
                $node = "";
                $self->run("list");
            }
        }
    }
    $args{"node"} = $node;

    # vg name
    my $vg = $args{"vgname"} || $_[1];
    while( !$vg ){
        $vg = $self->prompt("Type vg or (v)gs to list them: ");
        if( $vg eq "v" ){
            $vg = "";
            $self->run("vglist",$node);
        }
    }
    $args{"vgname"} = $vg;

    # get physical volumes
    my $pvs = $args{"pvs"};
    if( !$pvs ){
        my @pvs = ();
        my $ok_net = 0;
        print "Please specify physical volumes or (e)xit or (l) to list available","\n";
        while( !$ok_net ){
            my $pv;
            if( $pv = $self->prompt("Physical volume: ") ){
                if( $pv eq "e" ){
                    last;
                } elsif( $pv eq "l" ){
                    $self->run("pvlist", $node);
                } else {
                    push(@pvs,$pv);
                }
            }
        }
        $pvs = $args{"pvs"} = \@pvs;
    } elsif( ! ref($pvs) ){
        my $str_pvs = delete($args{"pvs"});
        my @pvs = split(/,/,$str_pvs);
        $pvs = $args{"pvs"} = \@pvs;
    }
    return %args;
}
# invoke volume group reduce
sub run_vgreduce {
    my $self = shift;

    my %args = $self->mkargs_vgreduce( @_ );

    my $ok = Cmdline::CM::vgreduce(%args);
    if( $ok ){
        print "ok\n";
    } else {
        print "nok\n";
    }
}
# get input params to volume group remove
sub mkargs_vgremove {
    my $self = shift;

    my %args = Cmdline->splitOps(@_);

    # node
    my $node = $args{"node"} || $_[0] || $NODE;
    if( !$node ||
            !Cmdline::CM::get_node($node) ){
        my $ok_node = 0;
        while( !$ok_node ){
            if( $node && !Cmdline::CM::get_node($node) ){
                print "Invalid node!\n";
            } elsif( $node ){
                last;
            }
            $node = $self->prompt("Type node or (l)ist: ");
            if( $node eq "l" ){
                $node = "";
                $self->run("list");
            }
        }
    }
    $args{"node"} = $node;

    # volume group name
    my $vg = $args{"vgname"} || $_[1];
    if( !$vg ||
        !grep { $_->{'vg'} eq $vg } Cmdline::CM::vglist( $node ) ){
        my $ok_vg = 0;
        while( !$ok_vg ){
            if( $vg && !grep { $_->{'vg'} eq $vg } Cmdline::CM::vglist( $node ) ){
                print "Volume group invalid!\n";
            } elsif( $vg ){
                last;
            }
            $vg = $self->prompt("Type vg or (v)gs to list them: ");
            if( $vg eq "v" ){
                $vg = "";
                $self->run("vglist", $node);
            }
        }
    }
    $args{"vgname"} = $vg;

    return %args;
}
# invoke volume group remove
sub run_vgremove {
    my $self = shift;

    my %args = $self->mkargs_vgremove( @_ );

    my $ok = Cmdline::CM::vgremove(%args);
    if( $ok ){
        print "ok\n";
    } else {
        print "nok\n";
    }
}
# invoke method to show logical volumes
mkalias("run_lvlist","run_lvs");
sub run_lvlist {
    my $self = shift;

    my %args = $self->mkargs_node(@_);

    my @list = Cmdline::CM::lvlist(%args);

    print_table( "lvs:",
                    [ 'id'=>"Id",
                    'lv'=>"LVolume"
                    ],
                    \@list );
}
# get from input params of logical volume to create
sub mkargs_lvcreate {
    my $self = shift;

    my %args = Cmdline->splitOps(@_);

    # node
    my $node = $args{"node"} || $_[0] || $NODE;
    if( !$node ||
            !Cmdline::CM::get_node($node) ){
        my $ok_node = 0;
        while( !$ok_node ){
            if( $node && !Cmdline::CM::get_node($node) ){
                print "Invalid node!\n";
            } elsif( $node ){
                last;
            }
            $node = $self->prompt("Type node or (l)ist: ");
            if( $node eq "l" ){
                $node = "";
                $self->run("list");
            }
        }
    }
    $args{"node"} = $node;

    # logical volume name
    my $lv = $args{"lvname"} || $_[1];
    if( !$lv ||
        grep { $_->{'lv'} eq $lv } Cmdline::CM::lvlist( $node ) ){
        my $ok_lv = 0;
        while( !$ok_lv ){
            if( $lv && grep { $_->{'lv'} eq $lv } Cmdline::CM::lvlist( $node ) ){
                print "Logical volume already exists! Type (l)vs to list them.\n";
            } elsif( $lv ){
                last;
            }
            $lv = $self->prompt("Type lv: ");
            if( $lv eq "l" ){
                $lv = "";
                $self->run("lvlist", $node);
            }
        }
    }
    $args{"lvname"} = $lv;

    # volume group name
    my $vg = $args{"vgname"} || $_[2];
    if( !$vg ||
        !grep { $_->{'vg'} eq $vg } Cmdline::CM::vglist( $node ) ){
        my $ok_vg = 0;
        while( !$ok_vg ){
            if( $vg && !grep { $_->{'vg'} eq $vg } Cmdline::CM::vglist( $node ) ){
                print "Volume group invalid!\n";
            } elsif( $vg ){
                last;
            }
            $vg = $self->prompt("Type vg or (v)gs to list them: ");
            if( $vg eq "v" ){
                $vg = "";
                $self->run("vglist", $node);
            }
        }
    }
    $args{"vgname"} = $vg;

    # logical volume size
    if( !$args{"size"} ){
        $args{"size"} = $self->prompt("Size: ");
    }

    return %args;
}
# invoke logical volume create
sub run_lvcreate {
    my $self = shift;

    my %args = $self->mkargs_lvcreate( @_ );

    my $ok = Cmdline::CM::lvcreate(%args);
    if( $ok ){
        print "ok\n";
    } else {
        print "nok\n";
    }
}
# get from input parameters for logical volume resize
sub mkargs_lvresize {
    my $self = shift;

    my %args = Cmdline->splitOps(@_);

    # node
    my $node = $args{"node"} || $_[0] || $NODE;
    if( !$node ||
            !Cmdline::CM::get_node($node) ){
        my $ok_node = 0;
        while( !$ok_node ){
            if( $node && !Cmdline::CM::get_node($node) ){
                print "Invalid node!\n";
            } elsif( $node ){
                last;
            }
            $node = $self->prompt("Type node or (l)ist: ");
            if( $node eq "l" ){
                $node = "";
                $self->run("list");
            }
        }
    }
    $args{"node"} = $node;

    # logical volume name
    my $lv = $args{"lvname"} || $_[1];
    if( !$lv ||
        !grep { $_->{'lv'} eq $lv } Cmdline::CM::lvlist( $node ) ){
        my $ok_lv = 0;
        while( !$ok_lv ){
            if( $lv && !grep { $_->{'lv'} eq $lv } Cmdline::CM::lvlist( $node ) ){
                print "Invalid logical volume\n";
            } elsif( $lv ){
                last;
            }
            $lv = $self->prompt("Type lv or (l)vs to list them: ");
            if( $lv eq "l" ){
                $lv = "";
                $self->run("lvlist", $node);
            }
        }
    }
    $args{"lvname"} = $lv;

    # size
    if( !$args{"size"} ){
        $args{"size"} = $self->prompt("Size: ");
    }

    return %args;
}
# invoke logical volume resize
sub run_lvresize {
    my $self = shift;

    my %args = $self->mkargs_lvresize( @_ );

    my $ok = Cmdline::CM::lvresize(%args);
    if( $ok ){
        print "ok\n";
    } else {
        print "nok\n";
    }
}
# get from input params to remove logical volume
sub mkargs_lvremove {
    my $self = shift;

    my %args = Cmdline->splitOps(@_);

    # node
    my $node = $args{"node"} || $_[0] || $NODE;
    if( !$node ||
            !Cmdline::CM::get_node($node) ){
        my $ok_node = 0;
        while( !$ok_node ){
            if( $node && !Cmdline::CM::get_node($node) ){
                print "Invalid node!\n";
            } elsif( $node ){
                last;
            }
            $node = $self->prompt("Type node or (l)ist: ");
            if( $node eq "l" ){
                $node = "";
                $self->run("list");
            }
        }
    }
    $args{"node"} = $node;

    # lv name
    my $lv = $args{"lvname"} || $_[1];
    if( !$lv ||
        !grep { $_->{'lv'} eq $lv } Cmdline::CM::lvlist( $node ) ){
        my $ok_lv = 0;
        while( !$ok_lv ){
            if( $lv && !grep { $_->{'lv'} eq $lv } Cmdline::CM::lvlist( $node ) ){
                print "Invalid logical volume\n";
            } elsif( $lv ){
                last;
            }
            $lv = $self->prompt("Type lv or (l)vs to list them: ");
            if( $lv eq "l" ){
                $lv = "";
                $self->run("lvlist", $node);
            }
        }
    }
    $args{"lvname"} = $lv;

    return %args;
}
# invoke logical volume remove
sub run_lvremove {
    my $self = shift;

    my %args = $self->mkargs_lvremove( @_ );

    my $ok = Cmdline::CM::lvremove(%args);
    if( $ok ){
        print "ok\n";
    } else {
        print "nok\n";
    }

}
# invoke method to show virtual networks
sub run_vlanlist {
    my $self = shift;

    my @list = Cmdline::CM::vlanlist();

    print_table( "VLans:",
                    [ 'Id'=>"Id",
                    'Name'=>"Name"
                    ],
                    \@list );

}
# get from input params to create virtual network
sub mkargs_vlancreate {
    my $self = shift;

    my %args = Cmdline->splitOps(@_);

    # vlan name
    my $vlan = $args{"vlan"} || $_[0];
    if( !$vlan ||
        grep { $_->{'vlan'} eq $vlan } Cmdline::CM::vlanlist() ){
        my $ok_vlan = 0;
        while( !$ok_vlan ){
            if( $vlan && grep { $_->{'vlan'} eq $vlan } Cmdline::CM::vlanlist() ){
                print "Vlan already exists! Type (v)lans to list them.\n";
            } elsif( $vlan ){
                last;
            }
            $vlan = $self->prompt("Type vlan: ");
            if( $vlan eq "l" ){
                $vlan = "";
                $self->run("vlanlist");
            }
        }
    }
    $args{"vlan"} = $vlan;
    
    return %args;
}
# invoke virtual network create
sub run_vlancreate {
    my $self = shift;

    my %args = $self->mkargs_vlancreate( @_ );

    my $ok = Cmdline::CM::vlancreate(%args);
    if( $ok ){
        print "ok\n";
    } else {
        print "nok\n";
    }
}
# get from input params to remove virtual network
sub mkargs_vlanremove {
    my $self = shift;

    my %args = Cmdline->splitOps(@_);

    # vlan name
    my $vlan = $args{"vlan"} || $_[0];
    if( !$vlan ||
        !grep { $_->{'vlan'} eq $vlan } Cmdline::CM::vlanlist() ){
        my $ok_vlan = 0;
        while( !$ok_vlan ){
            if( $vlan && !grep { $_->{'vlan'} eq $vlan } Cmdline::CM::vlanlist() ){
                print "Invalid vlan!\n";
            } elsif( $vlan ){
                last;
            }
            $vlan = $self->prompt("Type Vlan or (v)lans to list them: ");
            if( $vlan eq "l" ){
                $vlan = "";
                $self->run("vlanlist");
            }
        }
    }
    $args{"vlan"} = $vlan;
    
    return %args;
}
# invoke remove virtual network method
sub run_vlanremove {
    my $self = shift;

    my %args = $self->mkargs_vlanremove( @_ );

    my $ok = Cmdline::CM::vlanremove(%args);
    if( $ok ){
        print "ok\n";
    } else {
        print "nok\n";
    }
}

1;

