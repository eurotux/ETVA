#!/usr/bin/perl

=pod

=head1 NAME

ETVOIP::PBX::Dictate

=head1 SYNOPSIS

...

=head1 DESCRIPTION

...

=head1 METHODS

=over 4

=cut

package ETVOIP::PBX::Dictate;
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
sub dictate_extensions_add {
    my ($self,$p) = @_;
    my $extension = trim($p->{'extension'});
    my $dictenabled = trim($p->{'dictenabled'});
    my $dictformat = trim($p->{'dictformat'});
    my $dictemail = trim($p->{'dictemail'});    

    my $update = $self->dictate_update($extension, $dictenabled, $dictformat, $dictemail);
    if($update && !isError($update)) {
        return retOk("_OK_ADD_EXTENSION_DICTATE_","Updated dictate successfully.");
    }
    else{
        return wantarray() ? %$update : $update;
    }

}


=item
    called on edit extension
=cut
sub dictate_extensions_edit {
    my ($self,$p) = @_;
    my $extension = trim($p->{'extension'});
    my $dictenabled = trim($p->{'dictenabled'});
    my $dictformat = trim($p->{'dictformat'});
    my $dictemail = trim($p->{'dictemail'});

    my $update = $self->dictate_update($extension, $dictenabled, $dictformat, $dictemail);
    if($update && !isError($update)) {
        return retOk("_OK_EDIT_EXTENSION_DICTATE_","Updated dictate successfully.");
    }
    else{
        return wantarray() ? %$update : $update;
    }

}


sub dictate_update{    
    my ($self, $extension, $dictenabled, $dictformat, $dictemail) = @_;
    my $astman = $self->{'astman'};
    
    if ($dictenabled eq 'disabled') {
		$self->dictate_del($extension);
        return 1;
	} else {
		# Update the settings in ASTDB
        if ($astman) {
            $astman->db_put("AMPUSER",$extension."/dictate/enabled",$dictenabled);
            $astman->db_put("AMPUSER",$extension."/dictate/format",$dictformat);
            $astman->db_put("AMPUSER",$extension."/dictate/email",$dictemail);
            return 1;
        }
        return 0;
	}
}


sub dictate_get {
    my ($self, $extension) = @_;
    my $astman = $self->{'astman'};

    # Clean up the tree when the user is deleted
    if ($astman) {
        my $ena = $astman->db_get("AMPUSER",$extension."/dictate/enabled");
        my $format = $astman->db_get("AMPUSER",$extension."/dictate/format");
        my $email = $astman->db_get("AMPUSER",$extension."/dictate/email");
        
        return {'enabled' => $ena, 'format' => $format, 'email' => $email};
	}
    else {
		plog("Cannot connect to Asterisk Manager with ".$self->{'amp_conf'}{"AMPMGRUSER"}."/".$self->{'amp_conf'}{"AMPMGRPASS"});
        return retErr("_ERR_DICTATE_GET_","Cannot connect to Asterisk Manager");
	}    
}

=item
    called on del extension
=cut
sub dictate_extensions_del {
    my ($self,$p) = @_;
    my $extension = trim($p->{'extension'});
    return $self->dictate_del($extension);
}

sub dictate_del{
    my ($self, $extension) = @_;
    my $astman = $self->{'astman'};

    # Clean up the tree when the user is deleted
    if ($astman) {
        $astman->db_deltree("AMPUSER/$extension/dictate");
        return retOk("_OK_DEL_EXTENSION_","Deleted dictate refs.");
	}
    else {
		plog("Cannot connect to Asterisk Manager with ".$self->{'amp_conf'}{"AMPMGRUSER"}."/".$self->{'amp_conf'}{"AMPMGRPASS"});
        return retErr("_ERR_ADD_EXTENSION_","Cannot connect to Asterisk Manager");
	}
}
1;