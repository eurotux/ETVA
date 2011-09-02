#!/usr/bin/perl

=pod

=head1 NAME

ETVOIP::PBX::Ivr

=head1 SYNOPSIS

...

=head1 DESCRIPTION

...


=cut

package ETVOIP::PBX::Ivr;
use strict;
use Data::Dumper;
use ETVA::Utils;

BEGIN {
    use vars qw($VERSION @ISA @EXPORT @EXPORT_OK %EXPORT_TAGS $CRLF $AUTOLOAD);
    @ISA = ('ETVOIP::PBX');
};

use constant MODULE_PRIORITY => 8;

sub new{
    my $class = shift;
    my $self = {@_};
    bless $self, $class;
    return $self;
}


# The destinations this module provides
# returns a associative arrays with keys 'destination' and 'description'

sub ivr_destinations {
    my $self = shift;
    my @extens = ();
    
    #get the list of IVR's
	my $results = $self->ivr_list();

    foreach my $result (@$results){
        push(@extens, {'destination' => 'ivr-'.$result->{'ivr_id'}.',s,1', 'description' => $result->{'displayname'}});
    }

    return wantarray() ? @extens : \@extens;
}

sub ivr_list {
    my $self = shift;
    my ($sth, $result) = DB::db_sql('SELECT * FROM ivr where displayname <> \'__install_done\' ORDER BY displayname');
    my $res = $sth->fetchall_arrayref({});

    wantarray() ? %$res : $res;
}

1;