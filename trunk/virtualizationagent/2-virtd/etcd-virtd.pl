#!/usr/bin/perl
#

use strict;
use warnings;
use utf8;

use Etcd;
use JSON;

my $RUNNING = 1;
my $UUID = "node-1";

use VirtAgent;

my $Dispatcher = 'VirtAgent';

sub main {

    my $dispatcher = $Dispatcher;

    eval "require $dispatcher;";
    if( $@ ){
        die "etcd-virtd: Cant launch agent with this dispatcher: $dispatcher\n";
    }

    my $etcd = Etcd->new;

    while($RUNNING){
        print "Go next...","\n";
        my $task;
        eval {
            $task = $etcd->get("/queues/nodes/$UUID", wait=>'true',recursive=>'true');
        };
        if( $task ){
            print "response (key=",$task->node->key," value=",$task->node->value,")","\n";

            my $request = decode_json($task->node->value);
            if( my $method = $request->{'method'} ){
                eval {
                    $request->{'response'} = $dispatcher->$method(%$request);
                };
                if( $@ ){
                    #$request->{'response'} = 
                }
            }
            
            sleep(5);
            $etcd->set($task->node->key, encode_json($request));
        }

        sleep(1);
    }
}
&main;

1;
