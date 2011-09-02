#!/usr/bin/perl

=pod

=head1 NAME

ETVOIP::PBX::Languages

=head1 SYNOPSIS

...

=head1 DESCRIPTION

...

=head1 METHODS

=over 4

=cut

package ETVOIP::PBX::Languages;
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
    called on add extension
=cut
sub languages_extensions_add {
    my ($self,$p) = @_;
    my $extension = trim($p->{'extension'});
    my $langcode = $p->{'langcode'};

    print STDERR "\n\nULTIMO\n\n";

    my $update = $self->languages_user_update($extension, $langcode);
    if($update && !isError($update)) {
        return retOk("_OK_ADD_EXTENSION_","Updated language successfully.");
    }
    else{
        return wantarray() ? %$update : $update;
    }    
}


sub languages_extensions_edit {
    my ($self,$p) = @_;
    my $extension = trim($p->{'extension'});
    my $langcode = $p->{'langcode'};

    print STDERR "\n\nULTIMO\n\n";

    my $update = $self->languages_user_update($extension, $langcode);
    if($update && !isError($update)) {
        return retOk("_OK_EDIT_EXTENSION_LANGUAGE","Updated language successfully.");
    }
    else{
        return wantarray() ? %$update : $update;
    }
}


sub languages_extensions_del {
    my ($self,$p) = @_;
    my $extension = trim($p->{'extension'});    

    my $deleted = $self->languages_user_del($extension);
    if($deleted && !isError($deleted)) {
        return retOk("_OK_DEL_EXTENSION_LANGUAGE","Deleted language successfully.");
    }
    else{
        return wantarray() ? %$deleted : $deleted;
    }
}

sub languages_user_del {
    my ($self, $extension) = @_;
    my $astman = $self->{'astman'};
    
	if ($astman) {
        # Clean up the tree when the user is deleted
        $astman->db_deltree("AMPUSER",$extension."/language");
        return 1;
    }
    return 0;
}

sub languages_user_get {
    my ($self, $extension) = @_;

    my $astman = $self->{'astman'};
    # Retrieve the language configuraiton from this user from ASTDB
	my $langcode = $astman->db_get("AMPUSER",$extension."/language");
    return $langcode;
}


sub languages_user_update {
    my ($self, $extension, $langcode) = @_;
    my $astman = $self->{'astman'};
    
    #write to astdb
	if ($astman) {
        # Update the settings in ASTDB
        $astman->db_put("AMPUSER",$extension."/language",$langcode);
        return 1;
    }
    return 0;
}
1;