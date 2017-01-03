#!/usr/bin/perl

###############################################################################
# Eurotux Activation AGI script
# Notifies Expresso.pt for user activation
#
# Copyright (C) 2011, Eurotux, SA
# lma@eurotux.com
#
# This program is free software; you can redistribute it and/or
# modify it under the terms of the GNU General Public License
# as published by the Free Software Foundation; either version 2
# of the License, or (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
#
#
#  $Id
###############################################################################

use DBI;
use DBD::mysql;
use Data::Dumper;
use LWP::UserAgent;
use Asterisk::AGI;
use Data::Dumper;

use strict;
use warnings;

# Only lookup for entries in activations table for the last 5 minutes
our $FILTER_MINUTES_INTERVAL = 5;
our $FILTERSQL               = "AND confirmed = 0 AND date >= SUBDATE( NOW(), INTERVAL $FILTER_MINUTES_INTERVAL MINUTE )";
our $LOGFILE                 = '/home/ex/expresso-agi.log';
our $LOGFH;

sub open_logfh {
    open( $LOGFH, '>>', $LOGFILE ) or die "Coudln't open logfile '$LOGFILE': $!";
}

sub log_message {
    my $now_string = localtime;
    print $LOGFH $now_string, "  ", @_, "\n" if $LOGFH;
    # print STDERR $now_string,"  ",@_, "\n";
}

sub close_logfh {
    close($LOGFH);
}

sub get_callerid_last_four_digits {
    my $callerid = shift;
    my ($last_four_digits) = $callerid =~ m/(\d{4})$/;
    return $last_four_digits;
}

sub find_callback_on_db_for_phone {
    my $callerid = shift;

    my $last_four_digits = get_callerid_last_four_digits($callerid);

    # connect to the database
    my $dbh = DBI->connect( 'DBI:mysql:ex', 'ex', 'ex-activate' );
    my @result;

    if ($dbh) {

        # find the first record with the same phone number
        my $sth = $dbh->prepare("SELECT * FROM activations WHERE phone_lastdigits = ? $FILTERSQL");

        $sth->execute($last_four_digits);
        my $entry;
        while ( $entry = $sth->fetchrow_hashref() ) {
            push @result, $entry;
        }

        $sth->finish;

        $dbh->disconnect;

        if ( scalar(@result) > 0 ) {
            log_message( "Found callback for $callerid: ", Dumper( \@result ) );
        }
        else {
            log_message("INFO: Didn't found active callbacks for $callerid in the last $FILTER_MINUTES_INTERVAL minutes.");
        }
    }

    return @result;
}

sub notify_callback {
    my $callback = shift;

    my $ua = LWP::UserAgent->new();
	$ua->agent("Eurotux Activation Gateway/1.0 ");
    $ua->env_proxy;

    my $response = $ua->get( $callback->{'callback_url'} );
	
    if ( $response->is_success ) {
        log_message( "SUCCESS: Connecting to $callback->{'callback_url'}: " . $response->decoded_content );
        my $success = confirm_callback_on_db($callback);
        log_message("SUCCESS: Confirming callback state on DB");
    }
    else {
        log_message( "ERROR: Connecting to $callback->{'callback_url'}: " . $response->status_line );
    }
}

sub confirm_callback_on_db {
    my $callback = shift;

    # connect to the database
    my $dbh = DBI->connect( 'DBI:mysql:ex', 'ex', 'ex-activate' );

    if ($dbh) {

        # find the first record with the same phone number
        my $sth = $dbh->prepare("UPDATE activations set confirmed = 1, updated = NOW() WHERE phone_lastdigits = ? $FILTERSQL");

        my $success = $sth->execute( $callback->{'phone_lastdigits'} );

        $sth->finish;

        $dbh->disconnect;

        return $success;
    }

    return 0;
}

sub notify_expresso {
    my $phonenumber = shift;
    
	open_logfh();

    log_message("INFO: received call for number $phonenumber and trying to activate user on expresso.");

    my @callbackurls = find_callback_on_db_for_phone($phonenumber);
    if ( scalar(@callbackurls) ) {
        for my $callback (@callbackurls) {
            notify_callback($callback);
        }
    }

    close_logfh();
}

sub notify_teste {
    my $AGI = shift;
    $AGI->verbose( "AGI notify_teste");
}

######################################################################
# Main
######################################################################

my $AGI   = new Asterisk::AGI;
my %input = $AGI->ReadParse();

my $callerid = $input{'callerid'};

$AGI->verbose( "AGI CallerID: " . $callerid );

notify_expresso($callerid);

$AGI->verbose( "Runned notify_expresso");

$AGI->hangup();

