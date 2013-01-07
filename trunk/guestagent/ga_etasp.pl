#!/usr/bin/perl

use strict;
use warnings;
use FindBin;
use lib $FindBin::Bin;
use vars qw($PROGNAME $REVISION);
use Getopt::Long;
use vars qw($opt_V $opt_H $opt_h $opt_w $opt_c $opt_m $opt_p);
use Data::Dumper;
use LWP::UserAgent;
use JSON;

use constant{
    APINAME => 'etaspapi',
    ERROR   => 'nok',
    SUCCESS => 'ok',
};

# parse commandline options
Getopt::Long::Configure('bundling');
GetOptions (
	"h"   => \$opt_h, "help"       => \$opt_h,
	"H=s" => \$opt_H, "host=s"     => \$opt_H,
    "m=s" => \$opt_m, "method=s"   => \$opt_m,
    "p=i" => \$opt_p, "port=i"     => \$opt_p
);

# check some options
if(defined($opt_h) || ((!defined($opt_H) && !defined($opt_m)))) {
	print_usage();
	exit -1;
}

my $res = &_dispatcher;

print encode_json $res;

sub _dispatcher{
    my $port = $opt_p || 80;
    my $url = "http://$opt_H:$port/".APINAME."/$opt_m";
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
		$var = $json->allow_nonref->utf8->relaxed->escape_slash->loose->allow_singlequote->allow_barekey->decode($response->decoded_content());
	    $res{'success'} = SUCCESS;
        $res{'msg'} = $var;	
	};
	if($@) {
        $res{'success'} = ERROR;
        $res{'msg'} = $@;
	}

   return wantarray() ? %res : \%res;
}


sub print_usage {
    print "Usage: COMMAND -H <host> -m <method> [-p <port>] [-h]\n";
}
				
