#!/usr/bin/perl

use strict;
use warnings;
use locale;
use utf8;

use lib '/usr/local/sap2elastix';

use utilcommon;
use utilsql;
use utilallocnumber;

use Asterisk::AGI;

use Data::Dumper;

my $debug = 0;
my $debugfile = '/var/tmp/allocnumber.log';

sub initDebug {
    open(DEBUGFH,">>$debugfile");
}
sub endDebug {
    close(DEBUGFH);
}
sub printDebug {
    print DEBUGFH utilcommon::nowStr()," ",@_,"\n";
}

sub allocNumber {
    my ($ext) = @_;

    # init db
    utilallocnumber::initdb();

    # list alloc numbers
    my %allocnumbers = ();
    if( my @listallocnumbers = utilallocnumber::listAllocNumbers() ){

        foreach my $A (@listallocnumbers){
            $allocnumbers{"$A->{'phone'}"} = $A;
        }
    }

    # load external numbers
    my @externalnumbers = utilallocnumber::listExternalNumbers();

    # get free number
    my ($allocnumber) = grep { !$allocnumbers{"$_"} } @externalnumbers;

    &printDebug( "DEBUG allocNumber'$ext' (3) " );
    if( $allocnumber ){

        my $ttl = 1*60; # by 1 hour
        utilallocnumber::allocNumber($allocnumber,$ext,$ttl);

        my $limit = utilcommon::nowStr( $ttl*60 );    # by 1 hour
        &printDebug( "DEBUG number '$allocnumber' allocated to '$ext' until '$limit' " );
    }

    # close db
    utilallocnumber::closedb();

    return $allocnumber;
}
sub main {

    &initDebug;

    # set up communications w/ Asterisk
    my $agi = new Asterisk::AGI;
    my %input = $agi->ReadParse();

    my $callerid = $input{'callerid'};
    my $dnid = $input{'dnid'};

    &printDebug( "Call from '$callerid' to '$dnid'" );
    &printDebug( "Dump=",Dumper(\%input) );

    if( my $allocnumber = &allocNumber( $callerid ) ){
        &printDebug( "DEBUG Number '$allocnumber' allocated to '$callerid'" );
        $agi->set_variable('allocnumber',$allocnumber); 
    }

    &endDebug;
}
&main(@ARGV);

1;

