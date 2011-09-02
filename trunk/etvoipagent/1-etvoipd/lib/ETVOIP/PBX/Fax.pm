#!/usr/bin/perl

=pod

=head1 NAME

ETVOIP::PBX::Fax

=head1 SYNOPSIS

...

=head1 DESCRIPTION

...
TODO: fax_extensions_add ?????

=cut

package ETVOIP::PBX::Fax;
use strict;
use Data::Dumper;
use ETVA::Utils;

BEGIN {
    use vars qw($VERSION @ISA @EXPORT @EXPORT_OK %EXPORT_TAGS $CRLF $AUTOLOAD);
    @ISA = ('ETVOIP::PBX');
};

use constant MODULE_PRIORITY => 1;

sub new{
    my $class = shift;
    my $self = {@_};
    bless $self, $class;
    return $self;
}

=item
    called on edit extension
=cut
sub fax_extensions_edit {
    my ($self,$p) = @_;
    my $extension = trim($p->{'extension'});
    my $faxenabled = trim($p->{'faxenabled'});
    my $faxemail = trim($p->{'faxemail'});

    my $update = $self->fax_save_user($extension,$faxenabled,$faxemail);
    if($update && !isError($update)) {
        return retOk("_OK_ADD_EXTENSION_FAX_","Updated fax successfully.");
    }
    else{
        return wantarray() ? %$update : $update;
    }       
}

=item
    called on delete extension
=cut
sub fax_extensions_del {
    my ($self,$p) = @_;
    my $extension = trim($p->{'extension'});    

    my $deleted = $self->fax_delete_user($extension);
    if($deleted && !isError($deleted)) {
        return retOk("_OK_ADD_EXTENSION_FAX_","Updated fax successfully.");
    }
    else{
        return wantarray() ? %$deleted : $deleted;
    }
}


sub fax_save_user {
    my ($self,$faxext,$faxenabled,$faxemail) = @_;
    DB::db_sql('REPLACE INTO fax_users (user, faxenabled, faxemail) VALUES ('.DB::db_quote($faxext).','.DB::db_quote($faxenabled).','.DB::db_quote($faxemail).')');
    return 1;
}


sub fax_delete_user {
    my ($self,$faxext) = @_;
    DB::db_sql('DELETE FROM fax_users where user = '.DB::db_quote($faxext));
    return 1;
}


=item
    called on del inboud route
=cut
sub fax_did_process_del{
    my ($self,$p) = @_;
    my $extdisplay = $p->{'extdisplay'};    
    return $self->fax_delete_incoming($extdisplay);
}


sub fax_delete_incoming {
    my ($self,$extdisplay) = @_;        
    my @extarray = split(/\//,$extdisplay,2);

    my $sql = "DELETE FROM fax_incoming WHERE cidnum = ".$extarray[1]." AND extension =".$extarray[0];
    DB::db_sql($sql);
    return retOk("_OK_DEL_FAXINCOMING_","Deleted successfully.");
}
1;