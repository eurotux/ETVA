#!/usr/bin/perl

package ETVOIPDispatcher;

use strict;

use ETVOIP;
use ETVA::Utils;

BEGIN {
    use vars qw($VERSION @ISA @EXPORT @EXPORT_OK %EXPORT_TAGS $CRLF $AUTOLOAD);
};

my %AllETVOIPMods = ();
my %ActiveETVOIPMods = ();

sub AUTOLOAD {
    my ($package,$method) = ( $AUTOLOAD =~ m/(.+)::([^:]+)$/ );
    my $self = shift;
    my (%p) = @_;

    # get module dispatcher
    my $dispatcher = delete $p{"dispatcher"} || 'ETVOIP';

    # load active modules
    if( !%ActiveETVOIPMods ){
        force_loadmodules();
    }
    
    my $pmod = ( $dispatcher =~ m/^ETVOIP/ )? $dispatcher : $ActiveETVOIPMods{"$dispatcher"};
    if( $pmod ){
        # only ETFVOIP::* is valid
        eval "require $pmod";
        if( !$@ ){
            $AUTOLOAD = sub {
                            return $pmod->$method( %p );
                        };
        } else {
            die "Module '$pmod' is not available";
        }
    } elsif( $AllETVOIPMods{"$dispatcher"} ){
        die "Module '$dispatcher' is not available";
    } else {
        die "Module '$dispatcher' is not valid";
    }
        
    if( $AUTOLOAD ){
        &$AUTOLOAD;
    }
}


# force_loadmodules
#   force to load ETFW active modules
sub force_loadmodules {
    %AllETVOIPMods = ETVOIP->get_allmodules();
    %ActiveETVOIPMods = ETVOIP->get_activemodules();
}

sub getstate {
    my $self = shift;
    return retOk("_OK_STATE_","I'm alive.");
}

1;
