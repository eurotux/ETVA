#!/usr/bin/perl
# module with function for asterisk

package utilallocnumber;

use strict;

use utilcommon;
use utilsql;

my $DBPREFIX = 'etux_';     # database tables prefix
my $DBH;

my $default_ttl = 60; # default ttl

sub initdb {
    my %conf = utilcommon::load_conf('/usr/local/sap2elastix/config.conf');
    my ($dbhost,$dbuser,$dbpass) = ($conf{'mysql'}{'host'} || "127.0.0.1", $conf{'mysql'}{'user'} || "sap", $conf{'mysql'}{'pass'} || "123456");
    $DBPREFIX = $conf{'mysql'}{'prefix'} || 'etux_';

    $DBH = utilsql::sqlConnect("DBI:mysql:database=asterisk;host=$dbhost", $dbuser, $dbpass);
    return $DBH;
}
sub closedb {
    utilsql::sqlDisconnect($DBH);
}
sub listAllocNumbers {
    my @list = ();
    if( my $c = utilsql::sqlSelect($DBH, "*", "${DBPREFIX}ext2int", "end>now()", "ORDER BY start ASC") ){
        while( my $A = $c->fetchrow_hashref() ){
            push(@list, $A);
        }
        $c->finish();
    }
    return wantarray() ? @list : \@list;
}
sub listExternalNumbers {
    my @list = ();
    if( my $c = utilsql::sqlSelect($DBH, "numbers.phone,MAX(TIMESTAMPDIFF(SECOND,now(),log.created)) AS weight",
                                            "etux_extnumbers AS numbers LEFT JOIN etux_ext2intlog AS log ON numbers.phone=log.phone AND log.operation='insert'",
                                            "numbers.active=1",
                                            "GROUP BY numbers.phone ORDER BY weight ASC, numbers.phone ASC" ) ){
        while( my $N = $c->fetchrow_hashref() ){
            push(@list, $N->{'phone'});
        }
        $c->finish();
    }
    return wantarray() ? @list : \@list;
}
sub allocNumber {
    my ($allocnumber, $ext, $ttl, %extra ) = @_;
    $ttl ||= $default_ttl*60;    # default 1 hour

    # calc dates
    my $now = utilcommon::nowStr();
    my $limit = utilcommon::nowStr( $ttl );

    return &allocNumberRange($allocnumber, $ext, $now, $limit, %extra);
}
sub allocNumberRange {
    my ($allocnumber, $ext, $start, $end, %extra ) = @_;

    my $res = utilsql::sqlInsert($DBH,"${DBPREFIX}ext2int", { 'phone'=>$allocnumber, 'extension'=>$ext, 'start'=>$start, 'end'=>$end, %extra } );

    # log
    my $now = utilcommon::nowStr();
    utilsql::sqlInsert($DBH,"${DBPREFIX}ext2intlog", { 'phone'=>$allocnumber, 'extension'=>$ext, 'start'=>$start, 'end'=>$end, 'created'=>$now, 'operation'=>'insert', 'user'=>$ext } );

    return $res;
}
sub deleteAllocNumber {
    my ($allocnumber, $ext, %extra) = @_;
    my $res = utilsql::sqlDelete($DBH,"${DBPREFIX}ext2int", { 'phone'=>$allocnumber, 'extension'=>$ext } );
    
    # log
    my $now = utilcommon::nowStr();
    utilsql::sqlInsert($DBH,"${DBPREFIX}ext2intlog", { 'phone'=>$allocnumber, 'extension'=>$ext, 'created'=>$now, 'operation'=>'delete', 'user'=>$ext } );

    return $res;
}

1;
