#!/usr/bin/perl

=pod

=head1 NAME

ETVOIP::PBX::Vmblast

=head1 SYNOPSIS

...

=head1 DESCRIPTION

...

=cut

package ETVOIP::PBX::Vmblast;
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
    called on edit extension
=cut
sub vmblast_extensions_edit {
    my ($self,$p) = @_;
    my $extension = trim($p->{'extension'});
    my $vm = $p->{'vm'};

    if($vm ne 'enabled'){
        DB::db_sql("DELETE FROM vmblast_groups WHERE ext = '$extension'");
    }

    return retOk("_OK_EDIT_EXTENSION_VMBLAST_","Updated vm blast successfully.");
}

=item
    called on del extension
=cut
sub vmblast_extensions_del {
    my ($self,$p) = @_;
    my $extension = trim($p->{'extension'});   
    
    DB::db_sql("DELETE FROM vmblast_groups WHERE ext = '$extension'");

    return retOk("_OK_DEL_EXTENSION_VMBLAST_","Deleted vm blast successfully.");
}

sub vmblast_check_extension {
    my ($self, $extension) = @_;
    my $results = {};

    my $sql = "SELECT grpnum ,description FROM vmblast ORDER BY CAST(grpnum AS UNSIGNED)";
    my ($sth, $result) = DB::db_sql($sql);

    my $get_res = $sth->fetchall_arrayref({});
    foreach my $item (@$get_res){
        $results->{$item->{'grpnum'}} = {'description' => $item->{'description'}};
    }

    return wantarray() ? %$results : $results;

}
1;