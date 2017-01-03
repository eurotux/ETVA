#!/usr/bin/perl
#

use strict;
use warnings;
use utf8;

use ETVA::Client;

use Etcd;
use JSON;

sub main {
    my %args = ETVA::Client->splitOps(@_);

    my $etcd = Etcd->new;

    my $request = $etcd->create_in_order("/queues/nodes/$args{'node'}",encode_json({ %args}));

    #my $response = $etcd->get($request->node->key,wait=>'true');
    my $response = $etcd->get($request->node->key);

    print "response (key=",$response->node->key," value=",$response->node->value,")","\n";
 
}

&main(@ARGV);
1;
