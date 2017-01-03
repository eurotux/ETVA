#!/usr/bin/perl

use strict;
use warnings;

use Cwd 'realpath';
use File::Basename;

use YAML 'LoadFile';

## add script dir to @INC
my $lib;

BEGIN { $lib = dirname(realpath $0); }

use lib $lib;

my ($cfile) = ('conf.yaml');

sub main {

    # load configuration file
    my $Conf = LoadFile($cfile)
                    or die "couldn't load configuration from $cfile: $!\n";
    
    # get sources and targes
    my $sources = $Conf->{'sources'} ? $Conf->{'sources'} : [ $Conf->{'source'} ];
    my $targets = $Conf->{'targets'} ? $Conf->{'targets'} : [ $Conf->{'target'} ];

    my %Users = ();     # load users

    print "[DEBUG] debug=$Conf->{debug}","\n" if( $Conf->{'debug'} );

    # load users from sources
    foreach my $source (@$sources){
        my $sn = $source->{'type'};
        require "sources/$sn.pm";

        my $smfn = "$lib/map/sources/$sn.yaml";     # source config file
        my $sm = LoadFile($smfn) if( -e "$smfn" );

        my $s = "sources::$sn"->new(%$source, m => $sm, 'debug'=>$Conf->{'debug'});
        my $users = $s->users;          # load users
        %Users = (%Users, %$users);     # merge users
    }

    # import to tagers
    foreach my $target (@$targets){
        my $tn = $target->{'type'};
        require "targets/$tn.pm";

        my $tmfn = "$lib/map/targets/$tn.yaml";     # target config file
        my $tm = LoadFile($tmfn) if( -e "$tmfn" );

        my $t = "targets::$tn"->new(%$target, m => $tm, 'debug'=>$Conf->{'debug'});
        $t->sync_users(\%Users);        #  import users
    }
}
&main();
1;

