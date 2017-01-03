#!/usr/bin/perl

use strict;
use warnings;
use locale;
use utf8;

use lib '/usr/local/sap2elastix';

use utilcommon;
use utilsql;

use Asterisk::AGI;

use Data::Dumper;

my $debug = 0;
my $debugfile = '/var/tmp/ext2int.log';

my $DBPREFIX = 'etux_';     # database tables prefix

my %ext2int = ( '71511' => '1511',
                '71515' => '1515' );

sub initDebug {
    open(DEBUGFH,">>$debugfile");
}
sub endDebug {
    close(DEBUGFH);
}
sub printDebug {
    print DEBUGFH utilcommon::nowStr()," ",@_,"\n";
}

=com    # get ext2int from database
        #   table etux_ext2int

CREATE TABLE `etux_ext2int` (
  `phone` varchar(80) NOT NULL,
  `extension` varchar(80) NOT NULL,
  `start` datetime NOT NULL,
  `end` datetime NOT NULL
);
ALTER TABLE etux_ext2int ADD INDEX (phone,start,end);

=cut

sub getExt2Int {
    my ($ext) = @_;

    my %conf = utilcommon::load_conf('/usr/local/sap2elastix/config.conf');
    my ($dbhost,$dbuser,$dbpass) = ($conf{'mysql'}{'host'} || "127.0.0.1", $conf{'mysql'}{'user'} || "sap", $conf{'mysql'}{'pass'} || "123456");
    $DBPREFIX = $conf{'mysql'}{'prefix'} || 'etux_';

    my $aDBH = utilsql::sqlConnect("DBI:mysql:database=asterisk;host=$dbhost", $dbuser, $dbpass);

    if( my $c = utilsql::sqlSelect($aDBH, "extension,descr", "${DBPREFIX}ext2int", "phone='$ext' AND start<now() AND end>now()", "ORDER BY start ASC") ){
        if( my ($int,$descr) = $c->fetchrow() ){
            &printDebug( "DEBUG getExt2Int  phone='$ext' and int='$int'" );
            return ($int,$descr);
        }
        $c->finish();
    }
    utilsql::sqlDisconnect($aDBH);
    #return $ext2int{"$ext"};

}
sub main {

    &initDebug;

    # set up communications w/ Asterisk
    my $agi = new Asterisk::AGI;
    my %input = $agi->ReadParse();

    my $callerid = $input{'callerid'};
    my $dnid = $input{'dnid'};

    #&printDebug( "Call from '$callerid' to '$dnid'" );
    #&printDebug( "Dump=",Dumper(\%input) );

    my ($int,$descr) = &getExt2Int("$dnid");
    if( $int ){
        &printDebug( "Call from '$callerid' to '$dnid' will be delivery to '$int'" );
        $agi->set_variable('intnumber',$int); 
        $agi->set_variable('intdescr',$descr) if( $descr );
    } else {
        &printDebug( "Internal number not available for number '$dnid' (call from '$callerid')" );
    }

    &endDebug;
}
&main(@ARGV);

1;
