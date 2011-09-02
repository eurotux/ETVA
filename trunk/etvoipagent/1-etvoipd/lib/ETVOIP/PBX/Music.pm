#!/usr/bin/perl

=pod

=head1 NAME

ETVOIP::PBX::Music

=head1 SYNOPSIS

...

=head1 DESCRIPTION

...


=cut

package ETVOIP::PBX::Music;
use strict;
use Data::Dumper;
use ETVA::Utils;

BEGIN {
    use vars qw($VERSION @ISA @EXPORT @EXPORT_OK %EXPORT_TAGS $CRLF $AUTOLOAD);
};

use constant MODULE_PRIORITY => 8;

sub new{
    my $class = shift;
    my $self = {@_};
    bless $self, $class;
    return $self;
}


sub music_list {
    my ($self, $path) = @_;
    my $dh;
    my $dir;
    my @dirarray = ({'dir' => 'default'});

    if(!$path){
        # to get through possible upgrade gltiches, check if set
        if(!$self->{'amp_conf'}{'MOHDIR'}) {
            $self->{'amp_conf'}{'MOHDIR'} = '/mohmp3';
        }      
        $path = $self->{'amp_conf'}{'ASTVARLIBDIR'}.'/'.$self->{'amp_conf'}{'MOHDIR'};        
    }    

    opendir($dh, $path);
    while(defined ($dir = readdir($dh))){

        if ( ($dir ne ".") && ($dir ne "..") && ($dir ne "CVS") && ($dir ne ".svn") && ($dir ne ".nomusic_reserved" ) ){
            if(-d "$path/$dir") {
                push(@dirarray,{'dir'=>$dir});
            }
        }
    }    
    closedir($dh);
    
    # add a none categoy for no music
    if (!grep  { $_->{'dir'} eq 'none' } @dirarray ){
            push(@dirarray,{'dir'=>'none'});
    }

    return wantarray() ? @dirarray : \@dirarray;
}
1;