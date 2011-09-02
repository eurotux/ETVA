#!/usr/bin/perl

use strict;

use ETVA::NetworkTools;

package main;

# aux func for parsing options
sub parse_ops {
    my @args = @_;

    my %p = ();
    while(@args){
        my $o = shift(@args);
        if( $o =~ s/^-+// ){
            my @l = split(/=/,$o,2);
            if( scalar(@l) > 1 ){
                $p{"$l[0]"} = $l[1];
            } else {
                if( !$args[0] || $args[0] =~ m/^-+/ ){
                    $p{"$o"} = 1;
                } else {
                    my $v = shift(@args);
                    $p{"$o"} = $v;
                }
            }
        }
    }
    return wantarray() ? %p : \%p;
}

my %p = parse_ops(@ARGV);

use Data::Dumper;
#print Dumper(\%p),"\n";

# help print
if( $p{'help'} ){
    print "Help","\n";
    print " -if - network interface","\n";
    print " -ip - ip address","\n";
    print " -netmask - network mask","\n";
    print " -gateway - gateway","\n";
    print " -hostname - DNS hostname","\n";
    print " -domainname - DNS domain name","\n";
    print " -primarydns - first DNS server","\n";
    print " -secondarydns - second DNS server","\n";
    print " -tertiarydns - third DNS server","\n";
    print " -searchlist - domain search list (e.g. domain1,domain2,domain3)","\n";
    print "\n\n";
    exit(0);
}

if( $p{'dhcp'} ){
    $p{'bootproto'} = 'dhcp';
} else {
    $p{'bootproto'} = 'none';
    # check ip
    if( !ETVA::NetworkTools::valid_ipaddr($p{'ip'}) ){
        die "Error: invalid IP. ","\n";
    }

    # check netmask
    if( !ETVA::NetworkTools::valid_netmask($p{'netmask'}) ){
        die "Error: invalid Netmask. ","\n";
    }

    # check gateway
    if( $p{'gateway'} && !ETVA::NetworkTools::valid_ipaddr($p{'gateway'}) ){
        die "Error: invalid Gateway. ","\n";
    }
}

# check interface is valid
if( -e "/sys/class/net/$p{'if'}" ){
    # change conf
    if( ETVA::NetworkTools::change_if_conf( %p ) ){
        # apply if conf
        if( !ETVA::NetworkTools::active_ip_conf( %p ) ){
            die 'error change ip';
        }
        # try change dns values
        ETVA::NetworkTools::change_dns(%p);

        # change etva script files
        ETVA::NetworkTools::change_ip_etva_conf( %p );
    }
} else {
    die "interface '$p{'if'}' not found!\n";
}

1;
