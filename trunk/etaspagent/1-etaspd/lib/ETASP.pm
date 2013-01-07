#!/usr/bin/perl -w
use strict;

package ETASP;
use ETVA::Utils;
use ETVA::ArchiveTar;
use ETASP::Utils;
use Data::Dumper;
use JSON;

use constant{

    # ETASP REST SERVER
    HOST => 'localhost'
};

sub pack{
    my $self;

    my %obj = ETASP::Utils::call( 
        host => HOST, 
        method => 'pack', 
    );
    return wantarray() ? %obj: \%obj;        
}

sub getInstanceMetadata{
    my $self;

    my %obj = ETASP::Utils::call( 
        host => HOST, 
        method => 'getInstanceMetadata', 
    );
    return wantarray() ? %obj: \%obj;        
}

sub getDatabaseInfo{
    my $self;

    my %obj = ETASP::Utils::call( 
        host => HOST, 
        method => 'getDatabaseInfo', 
    );
    return wantarray() ? %obj: \%obj;        
}

sub getResourceUsage{
    my $self;

    my %obj = ETASP::Utils::call( 
        host => HOST, 
        method => 'getResourceUsage', 
    );
    return wantarray() ? %obj: \%obj;        

}

1;
