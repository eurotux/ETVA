#!/usr/bin/perl
# Copywrite Eurotux 2012
# 
# CMAR 2012/07/11 (cmar@eurotux.com)

# DebugLog

package ETVA::DebugLog;

use strict;

use Digest::MD5 qw(md5_hex);

BEGIN {
    # this is the worst damned warning ever, so SHUT UP ALREADY!
    $SIG{__WARN__} = sub { warn @_ unless $_[0] =~ /Use of uninitialized value/ };

    require Exporter;
    use vars qw($VERSION @ISA @EXPORT @EXPORT_OK %EXPORT_TAGS $CRLF);
    $VERSION = '0.0.1';
    @ISA = qw( Exporter );
    @EXPORT = qw();
}

my $DEBUGFILEPATH;
my $DEBUGFILE = *STDERR;

sub pdebuglog {
	print $DEBUGFILE @_;
    $DEBUGFILE->flush;
}

sub setDebugFile {
    my ($fpath) = @_;

    $DEBUGFILEPATH = $fpath if( $fpath );

    if( !$DEBUGFILEPATH || ($DEBUGFILEPATH eq 'STDERR') ){
        $DEBUGFILE = *STDERR;   # set to default
    } else {
        open(FDEBUG,">>$DEBUGFILEPATH");
        $DEBUGFILE = *FDEBUG;   # open debug file
    }
}
sub closeDebugFile {
    my $fhstderr = *STDERR;
    if( $DEBUGFILE ne $fhstderr ){
        close($DEBUGFILE);
    }
}

sub rand_logfile {
    my ($pr,$ext) = @_;
    $ext = '.log' if( !$ext );
    my $randtok = substr(md5_hex( rand(time()) ),0,5);
    return $pr ? "$pr.$randtok.$ext" : "$randtok.$ext";
}

sub dumplogfile {
    my ($file) = @_;
    my $msg = "";
    open(DUMP_LOG_FILE,"$file");
    while( <DUMP_LOG_FILE> ){
        $msg .= $_;
    }
    close(DUMP_LOG_FILE);
    return $msg;
}

1;
