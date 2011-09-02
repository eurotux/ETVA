#!/usr/bin/perl

=pod

=head1 NAME

ETVOIP::PBX::Dundicheck

=head1 SYNOPSIS

...

=head1 DESCRIPTION

...


=cut

package ETVOIP::PBX::Dundicheck;
use strict;
use Data::Dumper;
use ETVA::Utils;

BEGIN {
    use vars qw($VERSION @ISA @EXPORT @EXPORT_OK %EXPORT_TAGS $CRLF $AUTOLOAD);
    @ISA = ('ETVOIP::PBX');
};

use constant MODULE_PRIORITY => 5;

sub new{
    my $class = shift;
    my $self = {@_};
    bless $self, $class;
    return $self;
}


=item dundicheck_check_extension

    check if extension is in use by this module
=cut
sub dundicheck_check_extension {
    my ($self, $extension) = @_;    

    my $astman = $self->{'astman'};
    my $results = {};

    if($astman){
        # Get a list of the DUNDi trunks configured, if none then we just exit
        my @dundimap = ();
        my $core_module = ETVOIP::PBX::Core->new();
        my $trunklist = $core_module->core_trunks_list(1);        

        foreach my $trunkprops (@$trunklist){

            if (trim($trunkprops->{'disabled'}) eq 'on') {					
					next;
            }

            for($trunkprops->{'tech'}) {
                if(/DUNDI/) { push(@dundimap, $trunkprops->{'name'}); }
            }            
        }

        if(!@dundimap) { return wantarray() ? %$results : $results;}        
        

        # Now look through the extensions and lookup to see if DUNDi knows about them        
        my $foundone = $self->dundicheck_lookup($extension, \@dundimap);
        if ($foundone) {
            $results->{$extension} = {'description' => $foundone};
            #push(@results,{'exten' => $extension, 'description' => $foundone});
        }                
    }

    return wantarray() ? %$results : $results;
    
}

sub dundicheck_lookup {
    my ($self, $ext, $map) = @_;    
    my $astman = $self->{'astman'};
    my $reg_exp = $ext.' (EXISTS)';
    
    if($astman){       

        foreach my $lookup (@$map){
            my $command = "dundi lookup $ext".'@'.$lookup;        
            my $actionid = $astman->send_action({Action => 'Command', Command => $command});            
            my $response = $astman->get_response($actionid);
            
            my @cmd = @{$response->{'CMD'}};            
            my $resp_line = $cmd[0];                        
            
            if($resp_line =~ m/$reg_exp/){
                my @x = split(/@/,$resp_line);
                return $x[1];
            }
        }
        
        return 0;
    }
}
1;