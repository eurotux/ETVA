#!/usr/bin/perl

=pod

=head1 NAME

ETVOIP::PBX::Paging

=head1 SYNOPSIS

...

=head1 DESCRIPTION

...

=cut

package ETVOIP::PBX::Paging;
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


sub paging_check_extension {
    my ($self, $extension) = @_;
    my $results = {};

    my $sql = "SELECT page_group, description FROM paging_config ORDER BY page_group";
    my ($sth, $result) = DB::db_sql($sql);

    my $get_res = $sth->fetchall_arrayref({});
    foreach my $item (@$get_res){
        $results->{$item->{'page_group'}} = {'description' => $item->{'description'}};
    }

    return wantarray() ? %$results : $results;

}


=item
    called on del extension
=cut
sub paging_extensions_del {
    my ($self,$p) = @_;
    my $extension = trim($p->{'extension'});

    my $sql = "DELETE FROM paging_groups WHERE ext = '$extension'";
    DB::db_sql($sql);

    return retOk("_OK_DEL_EXTENSION_PAGING_","Deleted paging successfully.");        
}
1;