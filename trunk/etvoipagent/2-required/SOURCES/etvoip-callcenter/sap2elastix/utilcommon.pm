#!/usr/bin/perl

package utilcommon;

use strict;
use warnings;
use locale;
use utf8;

use POSIX;
use Mail::Sender;

# clean white spaces
sub trim {
    my ($e) = @_;
    $e =~ s/^\s+//;
    $e =~ s/\s+$//;
    return $e;
}
sub toInt {
    my ($num) = @_;
    if( $num =~ m/(\d+)/ ){
        $num = $1;
        return $num;
    }
    return;
}
sub toWord {
    my ($word) = @_;
    if( $word =~ m/(\w+)/ ){
        $word = $1;
        return $word;
    }
    return;
}
sub toPhone {
    my ($phone) = @_;
    if( $phone =~ m/^\s*(\+?[\s\d]+)$/ ){
        $phone = $1;
        $phone =~ s/\+/00/;
        $phone =~ s/\s+//g;

        return $phone;
    }
    return;
}
my %defaultformats = ( 'int'=>\&toInt, 'phone'=>\&toPhone, 'word'=>\&toWord );

# read args from ARGV, QUERY_STRING or from post-data
sub read_args {
    sub read_args_stdin {
        my $qstr = $ENV{'QUERY_STRING'} || <STDIN>;
        chomp($qstr);
        return split(/&/,$qstr);
    }
    my $format = {};
    $format = shift if( ref($_[0]) eq 'HASH' );

    my @l_args = @_;
    @l_args = @ARGV if( !@l_args );
    @l_args = &read_args_stdin() if( !@l_args );

    my %args = ();
    foreach my $arg (@l_args){
        my $carg = &trim($arg);
        my ($k,$v) = split(/=/,$carg,2);
        if( my $func = $format->{"$k"} ){
            $func = $defaultformats{"$func"} if( ref($func) ne 'CODE' );
            $v = &$func($v);
        }
        $args{"$k"} = $v;
    }
    return wantarray() ? %args : \%args;
}
# load configuration for Host, User and Password
sub load_conf {
    my ($file_conf) = shift || "config.conf";
    my %conf = ();
    if( -e "$file_conf" ){
        my $sec = "__general__";
        open(CFH,$file_conf);
        while(<CFH>){
            s/;.*//;
            if( /\[(\w+)\]/ ){
                $sec = $1;
            } elsif( /(\w+)\s*=\s*(.*)/ ){
                my ($p,$v) = ($1,$2);
		$conf{"$sec"}{"$p"} = $v;
            }
        }
        close(CFH);
    }
    return wantarray () ? %conf : \%conf;
}
# exec function call with timeout
sub timeout_call {
    my ($time,$call,@args) = @_;
    my $res;
    eval {
        local $SIG{ALRM} = sub { die "Timeout!\n"; };
        alarm($time);
        $res = &$call(@args);
        alarm(0);
    };
    print STDERR "timeout_call timed out! $@" if( $@ && $@ =~ m/Timeout/ );

    return $res;
}

sub now {
    my ($secs) = @_;
    $secs = 0 if( !$secs );

    return time()+$secs;
}
sub nowStr {
    my ($secs,$fmt) = @_;
    $fmt ||= '%Y-%m-%d %H:%M:%S';
    my $strtime = strftime($fmt,localtime(&now($secs)));
    return $strtime;
}

sub sendEmail {
    my %a = @_;
    if( $a{'to'} && $a{'subject'} && $a{'msg'} ){
        $a{'from'} ||= 'etvoip@eurotux.com';
        $a{'smtp'} ||= 'localhost';

        print STDERR "DEBUG ", "new Mail::Sender ( 'smtp'=>$a{'smtp'}, 'from'=>$a{'from'} )","\n" if( $a{'debug'} );
        print STDERR "DEBUG ", "MailMsg( 'to'=>$a{'to'}, 'msg'=>$a{'msg'}, 'subject'=>$a{'subject'} )","\n" if( $a{'debug'} );

        unless( $a{'debug'} ){
            my $sender = new Mail::Sender ( { 'smtp'=>$a{'smtp'}, 'from'=>$a{'from'} } );
            $sender->MailMsg( { 'to'=>$a{'to'}, 'msg'=>$a{'msg'}, 'subject'=>$a{'subject'} } );
            $sender->Close();
        }
    }
}

1;
