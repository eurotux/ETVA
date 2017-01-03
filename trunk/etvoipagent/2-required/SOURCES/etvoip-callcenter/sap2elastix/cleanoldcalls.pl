#!/usr/bin/perl
# cleanoldcalls
#   clean calls entries with more than x days

use strict;

use utilcommon;
use utilsql;

use Data::Dumper;

# reload asterisk
sub main {
    my ($ndays) = @_;
    my %conf = utilcommon::load_conf('/usr/local/sap2elastix/config.conf');
    my ($dbhost,$dbuser,$dbpass) = ($conf{'mysql'}{'host'} || "127.0.0.1",$conf{'mysql'}{'user'} || "sap", $conf{'mysql'}{'pass'} || "123456");

    my $DBPREFIX = $conf{'mysql'}{'prefix'} || 'etux_';     # database tables prefix

    my $DBH = utilsql::sqlConnect("DBI:mysql:database=asteriskcdrdb;host=$dbhost", $dbuser, $dbpass);

    $ndays ||= 90;   # default 90 days
    my $datelimit = time() - int($ndays)*24*60*60;   # last X days

    utilsql::sqlDelete($DBH, "${DBPREFIX}callentry", "created<FROM_UNIXTIME($datelimit)");

    utilsql::sqlDisconnect($DBH);
}

&main(@ARGV);

1;

