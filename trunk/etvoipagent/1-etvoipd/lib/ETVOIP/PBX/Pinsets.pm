#!/usr/bin/perl

=pod

=head1 NAME

ETVOIP::PBX::Pinsets

=head1 SYNOPSIS

...

=head1 DESCRIPTION

...


=cut

package ETVOIP::PBX::Pinsets;
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


sub pinsets_routing_editroute {
    my ($self, $p) = @_;
    my $route = $p->{'routename'};
    my $action = 'editroute';
    my $pinsets = $p->{'pinsets'};
    my $direction = '';
    my $newname = '';
    #$self->pinsets_adjustroute($route,$action,$pinsets,$direction,$newname);
    
    return retOk("_OK_EDIT_OUTBOUNDROUTE_PINSET_","Updated pinset successfully.");

}

sub pinsets_routing_delroute {
    my ($self, $route) = @_;    
    my $action = 'delroute';    
    my $pinsets = '';
    my $direction = '';
    my $newname = '';
    $self->pinsets_adjustroute($route,$action,$pinsets,$direction,$newname);

    return retOk("_OK_DEL_OUTBOUNDROUTE_PINSET_","Updated pinset successfully.");

}



#get the existing meetme extensions
sub pinsets_list {
    my $self = shift;
    my ($sth,$result) = DB::db_sql("SELECT * FROM pinsets");
    my $data = $sth->fetchall_arrayref({});

    #foreach my $item (@$data){
    #    $results->{$item->{'extension'}} = {'description' => $item->{'name'}};
    #}

    return wantarray() ?  @$data : $data;


}

#removes a pinset from a route and shifts priority for all outbound routing pinsets
sub pinsets_adjustroute {
    my ($self, $route,$action,$routepinset,$direction,$newname) = @_;
    $routepinset ||= '';
    $direction ||= '';
    $newname ||= '';

    my $priority = substr($route,0,3);
    #create a selection of available pinsets
    my @pinsets = $self->pinsets_list();
    my $pinset;
    my $usedby;
    my @arrUsedby;
plog("\n\n\n\nPINSETS\n\n\n ",Dumper(@pinsets));


    foreach (@pinsets) {
        $pinset = $_;
        
        # get the used_by field
        $usedby = $pinset->{'used_by'};

        # create an array from usedby
        @arrUsedby = split(',',$usedby);
        for(my $i=0;$i<@arrUsedby;$i++){

            if (substr($arrUsedby[$i],0,8) eq 'routing_') {
                for($action) {
                    if(/editroute/) {
                        
                        if ($arrUsedby[$i] eq "routing_$route") {
                            splice @arrUsedby, $i,1;
                        }
                    }
                    elsif(/delroute/){
                        if ($arrUsedby[$i] eq "routing_$route") {                        	
                            splice @arrUsedby, $i,1;
						}

                        my $usedbypriority = substr($arrUsedby[$i],8,3);
                        my $usedbyroute = substr($arrUsedby[$i],12);

                        if ($usedbypriority > $priority) {                            
                            
                            my $newpriority = sprintf("%03d",$usedbypriority-1);                            

                        	$arrUsedby[$i] = 'routing_'.$newpriority.'-'.$usedbyroute;                            

                        }


                    }                   
                }
            }            
        }


        # save the route in the selected pin
        if ($routepinset eq $pinset->{'pinsets_id'} && $action eq 'editroute') {
            push(@arrUsedby, 'routing_'.$route);
        }
        plog("\n\n\nREORENDER \n\n\n",Dumper(@arrUsedby));

        # remove any duplicates
        my %seen = ();
        my @uniq_arrUsedby;
        foreach my $item (@arrUsedby){
            push(@uniq_arrUsedby, $item) unless $seen{$item}++;
        }

        plog("\n\n\nUNIQU REORENDER \n\n\n",Dumper(@uniq_arrUsedby));

        # create a new string
        my $strUsedby = join(",",@uniq_arrUsedby);

        # Insure there's no leading or trailing commas
        $strUsedby = trim ($strUsedby, ',');


        # store the used_by column in the DB
        DB::db_sql("UPDATE pinsets SET used_by = \"$strUsedby\" WHERE pinsets_id = \"$pinset->{'pinsets_id'}\"");
               
    }   

}
1;