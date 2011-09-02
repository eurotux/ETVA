#!/usr/bin/perl

=pod

=head1 NAME

ETVOIP::PBX::Ringgroups

=head1 SYNOPSIS

...

=head1 DESCRIPTION

...

=head1 METHODS



=cut

package ETVOIP::PBX::Ringgroups;
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



sub ringgroups_check_extension {
    my ($self, $extension) = @_;
    my $results = {};

    my $sql = "SELECT grpnum ,description FROM ringgroups ORDER BY CAST(grpnum AS UNSIGNED)";
    my ($sth, $result) = DB::db_sql($sql);

    my $get_res = $sth->fetchall_arrayref({});
    foreach my $item (@$get_res){
        $results->{$item->{'grpnum'}} = {'description' => $item->{'description'}};
    }

    return wantarray() ? %$results : $results;

}

# The destinations this module provides
# returns a associative arrays with keys 'destination' and 'description'
sub ringgroups_destinations {
    my $self = shift;
    #get the list of ringgroups
	my $results = $self->ringgroups_list();
    my @extens = ();
    my $thisgrp;


    foreach my $result (@$results){
        
        print STDERR "RR".$result->{'grpnum'};
        
        $thisgrp = $self->ringgroups_get($result->{'grpnum'});
        if(isError($thisgrp)) { return retErr("_ERR_GET_RIGGROUPS_DESTINATIONS_","Could not get info."); }
        push(@extens, {'destination' => 'ext-group,'.trim($result->{'grpnum'}).',1', 'description' => $thisgrp->{'description'}.' <'.trim($result->{'grpnum'}).'>'});
    }

    return wantarray() ? @extens : \@extens;
}


sub ringgroups_list {

    my ($sth, $result) = DB::db_sql("SELECT grpnum, description FROM ringgroups ORDER BY CAST(grpnum as UNSIGNED)");
    my $res = $sth->fetchall_arrayref({});
    my @grps;
    
    foreach my $item (@$res){
        if($item->{'grpnum'}) {
            push(@grps,{'grpnum' => $item->{'grpnum'}, 'description' => $item->{'description'}})
        }
    }

    return wantarray() ? @grps : \@grps;   
}

sub ringgroups_get {
    my ($self, $grpnum) = @_;

    my $astman = $self->{'astman'};

    my ($sth, $result) = DB::db_sql('SELECT grpnum, strategy, grptime, grppre, grplist, annmsg_id, postdest, description, alertinfo, needsconf, remotealert_id, toolate_id, ringing, cwignore, cfignore FROM ringgroups WHERE grpnum = '.DB::db_quote($grpnum));
    my $results = $sth->fetchrow_hashref;    

    if($astman) {
        my $astdb_changecid = lc($astman->db_get("RINGGROUP",$grpnum."/changecid"));

        if($astdb_changecid ne 'default' &&
           $astdb_changecid ne 'did' &&
           $astdb_changecid ne 'forcedid' &&
           $astdb_changecid ne 'fixed' &&
           $astdb_changecid ne 'extern') { $astdb_changecid = 'default'; }


        $results->{'changecid'} = $astdb_changecid;
        my $fixedcid = trim($astman->db_get("RINGGROUP",$grpnum."/fixedcid"));
        $fixedcid =~ s/[^0-9\+]//;
        $results->{'fixedcid'} = $fixedcid;
        
	} else {

        plog("Cannot connect to Asterisk Manager with ".$self->{'amp_conf'}{"AMPMGRUSER"}."/".$self->{'amp_conf'}{"AMPMGRPASS"});
        return retErr("_ERR_GET_RINGGROUPS_","Cannot connect to Asterisk Manager");
	}
    return wantarray() ? %$results : $results;        
}
1;