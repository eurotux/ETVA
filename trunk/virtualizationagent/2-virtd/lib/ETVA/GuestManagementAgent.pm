#!/usr/bin/perl

package ETVA::GuestManagementAgent;

use strict;

BEGIN {
    # this is the worst damned warning ever, so SHUT UP ALREADY!
    $SIG{__WARN__} = sub { warn @_ unless $_[0] =~ /Use of uninitialized value/ };

    require Exporter;
    require ETVA::GuestAgent::Socket::SOAP;
    use vars qw($VERSION @ISA @EXPORT @EXPORT_OK %EXPORT_TAGS $CRLF);
    $VERSION = '0.0.1';
    @ISA = qw( ETVA::GuestAgent::Socket::SOAP );
    @EXPORT = qw(  );
}

1;
