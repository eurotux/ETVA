#!/usr/bin/perl

=pod

=head1 NAME

ETVOIP::PBX::Featurecodeadmin

=head1 SYNOPSIS

...

=head1 DESCRIPTION

...

=cut

package ETVOIP::PBX::Featurecodeadmin;
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

sub featurecodes_getAllFeaturesDetailed{
    my $self = shift;
    my $fd = $self->{'amp_conf'}{'ASTETCDIR'}.'/freepbx_featurecodes.conf';
    my %overridecodes;
    %overridecodes = ETVA::Utils::loadconfigfile($fd,\%overridecodes);
    
    my $modules = $self->active_modules();  

    my $sql = "SELECT featurecodes.modulename, featurecodes.featurename, featurecodes.description AS featuredescription, featurecodes.enabled AS featureenabled, featurecodes.defaultcode, featurecodes.customcode, ";
	$sql .= "modules.enabled AS moduleenabled ";
	$sql .= "FROM featurecodes ";
	$sql .= "INNER JOIN modules ON modules.modulename = featurecodes.modulename ";
	$sql .= "ORDER BY featurecodes.modulename, featurecodes.description ";
    my ($sth, $result) = DB::db_sql($sql);

    my $get_res = $sth->fetchall_arrayref({});
    foreach my $item (@$get_res){

        # get the module display name
        my $module_name = $modules->{$item->{'modulename'} }{'name'};
        $item->{'moduledescription'} = $module_name || ucfirst($item->{'modulename'});
        
        if($overridecodes{$item->{'modulename'}}{$item->{'featurename'}} && trim($overridecodes{$item->{'modulename'}}{$item->{'featurename'}}) ne '' ){
            $item->{'defaultcode'} = $overridecodes{$item->{'modulename'}}{$item->{'featurename'}};
        }               
    }

    return wantarray() ? @$get_res : $get_res;
    
}

sub featurecodeadmin_check_extension {
    my ($self, $extension) = @_;
    my $results = {};

    my $featurecodes = $self->featurecodes_getAllFeaturesDetailed();

    foreach my $item (@$featurecodes) {
		my $thisexten = ($item->{'customcode'} != '') ? $item->{'customcode'}:$item->{'defaultcode'};
       
		# Ignore disabled codes, and modules, and any exten not being requested unless all (true)
		#
		if (($item->{'featureenabled'} == 1) && ($item->{'moduleenabled'} == 1)) {
            $results->{$thisexten} = {'description' => $item->{'featuredescription'}};	
		}
	}

    return wantarray() ? %$results : $results;
}
1;