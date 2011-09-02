#!/usr/bin/perl

=pod

=head1 NAME

ETVOIP::PBX::Findmefollow

=head1 SYNOPSIS

...

=head1 DESCRIPTION

...

=head1 METHODS

=over 4

=cut

package ETVOIP::PBX::Findmefollow;
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
    called on del extension
=cut
sub findmefollow_extensions_del {
    my ($self, $p) = @_;
    return $self->findmefollow_del($p);
}

sub findmefollow_del {
    my ($self,$p) = @_;
    my $grpnum = trim($p->{'extension'});
    my $astman = $self->{'astman'};    

    my $sql="DELETE FROM findmefollow WHERE grpnum = ".DB::db_quote($grpnum);
    my ($sth, $result) = DB::db_sql($sql);	

	if ($astman) {
		$astman->db_deltree("AMPUSER/".$grpnum."/followme");
        return retOk("_OK_DEL_EXTENSION_","Deleted followme refs.");
	}
    else {
		plog("Cannot connect to Asterisk Manager with ".$self->{'amp_conf'}{"AMPMGRUSER"}."/".$self->{'amp_conf'}{"AMPMGRPASS"});
        return retErr("_ERR_ADD_EXTENSION_","Cannot connect to Asterisk Manager");
	}

}
1;