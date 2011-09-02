#!/usr/bin/perl

package Main;

use strict;

use Agent::SOAP;

sub main {
    
    require MyDispatcher;
    # initialization agent
	my $agent = Agent::SOAP->new( 'LocalAddr'=>'localhost', 'LocalPort'=>'7000', '_dispatcher'=>'MyDispatcher' );
	
	if( $agent ){
        # start loop
		$agent->mainLoop();
	}
}

&main();

1;
