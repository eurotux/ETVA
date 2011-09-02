#!/usr/bin/perl

=pod

=head1 NAME

ETVOIP::PBX::Cidlookup

=head1 SYNOPSIS

...

=head1 DESCRIPTION

...

=cut

package ETVOIP::PBX::Cidlookup;
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

=item
    called on add inboud route
=cut
sub cidlookup_did_process_add{
    my ($self,$p) = @_;    
    my $extension = $p->{'extension'};
    my $cidnum = $p->{'cidnum'};
    my $cidlookup_id = $p->{'cidlookup_id'};    

    my $sql = "INSERT INTO cidlookup_incoming (cidlookup_id, extension, cidnum) VALUES (\"$cidlookup_id\", \"$extension\", \"$cidnum\")";
    DB::db_sql($sql);

    return retOk("_OK_ADD_CIDLOOKUP_","Added cid lookup successfully.");
}

=item
    called on edit inboud route
=cut
sub cidlookup_did_process_edit{
    my ($self,$p) = @_;
    my $extdisplay = $p->{'extdisplay'};
    my $extension = $p->{'extension'};
    my $cidnum = $p->{'cidnum'};
    my $cidlookup_id = $p->{'cidlookup_id'};
    my @extarray = split(/\//,$extdisplay,2);

    my $sql = "DELETE FROM cidlookup_incoming WHERE extension = ".$extarray[0]." AND cidnum =".$extarray[1];
    DB::db_sql($sql);

    $sql = "INSERT INTO cidlookup_incoming (cidlookup_id, extension, cidnum) VALUES (\"$cidlookup_id\", \"$extension\", \"$cidnum\")";
    DB::db_sql($sql);

    return retOk("_OK_EDIT_CIDLOOKUP_","Updated cid lookup successfully.");
}


=item
    called on del inboud route
=cut
sub cidlookup_did_process_del{
    my ($self,$p) = @_;
    my $extdisplay = $p->{'extdisplay'};            
    my @extarray = split(/\//,$extdisplay,2);    

    my $sql = "DELETE FROM cidlookup_incoming WHERE extension = ".$extarray[0]." AND cidnum =".$extarray[1];
    DB::db_sql($sql);
    
    return retOk("_OK_DEL_CIDLOOKUP_","Deleted cid lookup successfully.");
}


sub cidlookup_did_get{
    my ($self,$extdisplay) = @_;    
    my @extarray = split(/\//,$extdisplay,2);

    if(scalar @extarray == 2){
        my $sql = "SELECT cidlookup_id FROM cidlookup_incoming WHERE extension = ".DB::db_quote($extarray[0])." AND cidnum = ".DB::db_quote($extarray[1]);
        my ($sth, $result) = DB::db_sql($sql);
        my $res = $sth->fetchrow_hashref;
        return $res->{'cidlookup_id'};
    }
    else {
        return 0;
    }       
}
1;