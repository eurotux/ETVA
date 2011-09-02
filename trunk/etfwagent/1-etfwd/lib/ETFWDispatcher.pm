#!/usr/bin/perl

package ETFWDispatcher;

use strict;

use ETFW;
use ETVA::Utils;

BEGIN {
    use vars qw($VERSION @ISA @EXPORT @EXPORT_OK %EXPORT_TAGS $CRLF $AUTOLOAD);
};

my %AllETFWMods = ();
my %ActiveETFWMods = ();

sub AUTOLOAD {
    my ($package,$method) = ( $AUTOLOAD =~ m/(.+)::([^:]+)$/ );
    my $self = shift;
    my (%p) = @_;

    # get module dispatcher
    my $dispatcher = delete $p{"dispatcher"} || 'ETFW';

    # load active modules
    if( !%ActiveETFWMods ){
        force_loadmodules();
    }
    my $pmod = ( $dispatcher =~ m/^ETFW/ )? $dispatcher : $ActiveETFWMods{"$dispatcher"};
    if( $pmod ){
        # only ETFW::* is valid
        eval "require $pmod";
        if( !$@ ){
            $AUTOLOAD = sub {
                            return $pmod->$method( %p );
                        };
        } else {
            die "Module '$pmod' is not available";
        }
    } elsif( $AllETFWMods{"$dispatcher"} ){
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
    %AllETFWMods = ETFW->get_allmodules();
    %ActiveETFWMods = ETFW->get_activemodules();
}

sub getstate {
    my $self = shift;
    return retOk("_OK_STATE_","I'm alive.");
}

1;
