#!/usr/bin/perl

package ReadSimpleConfig;

use strict;
#use diagnostics;

use Carp;
use Fcntl qw(:DEFAULT :flock);
use Text::ParseWords 'parse_line';

BEGIN {
    require Config::Simple;

    use vars qw($VERSION @ISA @EXPORT @EXPORT_OK %EXPORT_TAGS $CRLF $DEFAULTNS $LC $USEQQ $errstr);

    $VERSION = '0.0.1';
    @ISA = qw( Config::Simple );
    @EXPORT = qw( );
};

# parse_cfg_file
#   rewrite Config::Simple parse_cfg_file func
sub parse_cfg_file {
  my ($class, $file) = @_;

  my ($fh, $close_fh) = $class->_get_fh($file, O_RDONLY) or return;
    
  unless ( flock($fh, LOCK_SH) ) {
    $errstr = "couldn't get shared lock on $fh: $!";
    return undef;
  }

  unless ( seek($fh, 0, 0) ) {
    $errstr = "couldn't seek to the start of the file: :$!";
  }

  my %data = ();
  my $line;
  while ( defined($line=<$fh>) ) {
    # skipping comments and empty lines:
    $line =~ /^(\n|\#)/  and next;
    $line =~ /\S/        or  next;    
    chomp $line;
    $line =~ s/^\s+//g;
    $line =~ s/\s+$//g;
    # parsing key/value pairs
    $line =~ /^\s*([\w-]+)\s+(.*)\s*$/ and $data{lc($1)}=[parse_line($class->SUPER::READ_DELIM, 0, $2)], next;
    $line =~ /^\s*([\w-]+)\s*$/ and $data{lc($1)}=[ ], next;
    # if we came this far, the syntax couldn't be validated:
    $errstr = "syntax error on line $.: '$line'";
    return undef;
  }
  unless ( flock($fh, LOCK_UN) ) {
    $errstr = "couldn't unlock the file: $!";
    return undef;
  }
  
  if ( $close_fh ) {
    CORE::close($fh);
  }
  return \%data;
}

1;
