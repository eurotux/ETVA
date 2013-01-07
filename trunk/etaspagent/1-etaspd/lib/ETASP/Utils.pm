#!/usr/bin/perl

package ETASP::Utils;

use strict;
use Data::Dumper;
use LWP::UserAgent;
use JSON -support_by_pp;

use constant{
    APINAME => 'etaspapi',
    ERROR   => 'nok',
    SUCCESS => 'ok',
};

# usage: dispatcher{ host => '', method => '', port => '' }
sub call{
    my %p = @_;
print "call called\n";
    my $port = $p{'port'} || 80;
    my $url = "http://$p{'host'}:$port/".APINAME."/$p{'method'}";
    my %res;    

    # contact WS
	my $ua = new LWP::UserAgent();
	my $response = $ua->get($url);

	unless($response->is_success()) {
        $res{'success'} = ERROR;
        $res{'msg'} = $response->status_line();
        return wantarray() ? %res : \%res;
	}

	# parse output
	my $var;
	eval {
		my $json = new JSON();
		my $var = $json->allow_nonref->utf8->relaxed->escape_slash->loose->allow_singlequote->allow_barekey->decode($response->decoded_content());

        print $var;    
        for my $value ( values %$var ) {
            print $value;
            next unless 'JSON::PP::Boolean' eq ref $value;
            $value = ( $value ? 'yes' : 'no' );
        }

	    $res{'success'} = SUCCESS;
        $res{'msg'} = $var;	
	};
	if($@) {
        $res{'success'} = ERROR;
        $res{'msg'} = $@;
	}

   return wantarray() ? %res : \%res;
}

1;		
