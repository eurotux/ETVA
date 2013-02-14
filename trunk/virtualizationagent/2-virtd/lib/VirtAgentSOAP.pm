#!/usr/bin/perl

use strict;

package VirtAgentSOAP;

use strict;

use ETVA::Utils;

use ETVA::Client::SOAP::HTTP;

use Data::Dumper;

my @CHILD_PIDS = ();

BEGIN {
    # this is the worst damned warning ever, so SHUT UP ALREADY!
    $SIG{__WARN__} = sub { warn @_ unless $_[0] =~ /Use of uninitialized value/ };

    require Exporter;
    require ETVA::Agent::SOAPFork;
    require VirtAgentInterface;
    use vars qw($VERSION @ISA @EXPORT @EXPORT_OK %EXPORT_TAGS $CRLF $AUTOLOAD);
    $VERSION = '0.0.1';
    @ISA = qw( ETVA::Agent::SOAPFork VirtAgentInterface );
    @EXPORT = qw( );
}

# rewrite new method
sub new {
    my $self = shift;
    my (%p) = @_;

    unless( ref($self) ){
        my $class = ref( $self ) || $self;

        # force dispatcher 
        $p{'_dispatcher'} = "$self";

            # new implementation of ETVA::Agent::SOAP
        $self = new ETVA::Agent::SOAPFork( %p );

        $self = bless $self => $class;
    }
    return $self;
}

sub do_need_update {
    # CMAR 14/08/2012
    # #509 - dont (re)load syste info... this call is heavy
    #VirtAgentInterface->loadsysinfo(1);
}


sub disconnect {
    my $self = shift;
    plog "VirtAgentSOAP disconnect" if( &debug_level() > 7 );
    $self->vmDisconnect();
}

my ($last_sec,$last_min,$last_hour,$last_day,$last_time);
sub _idle_ {
    my $self = shift;

    $self->SUPER::_idle_();
    
    my $now = time();
    my ($sec,$min,$hour,$mday,$mon,$year,$wday,$yday,$isdst) = localtime($now);

    if (($sec != $last_sec) || ($min != $last_min) || ($hour != $last_hour) || ($yday != $last_day)) {
        if( $sec % 5 == 0 ){    # TODO add config this
            plog "_idle_ 5secs" if(&debug_level() > 7);
            my $func = $self->can("opPeriodic5Secs");
            if( $func ){
                &$func($self);
            }
        } 
    } 

    $last_sec = $sec;                       # store time values
    $last_min = $min;
    $last_hour = $hour;
    $last_day = $yday;
    $last_time = $now;
}

sub opPeriodic5Secs {
    my $self = shift;

    plog "opPeriodic5secs " if(&debug_level() > 7);

    my $restart = 0;

    if( $self->get_registerok() ){
        my $vms = $self->domains_stats();
        my $R = new ETVA::Client::SOAP::HTTP( uri => $self->{'cm_uri'}, 'debug'=>&debug_level )
                -> call( $self->{'cm_namespace'},
                            'updateVirtAgentServersStats',
                            uuid=>$self->{'uuid'},
                            'vms'=>$vms
                        );
        plog("updateVirtAgentServersStats ",Dumper($R)) if( &debug_level > 7 );

        unless( $R && !$R->{'_error_'} && ref($R->{'return'}) && $R->{'return'}{'success'} && $R->{'return'}{'success'} ne 'false' ){
            if( $R->{'return'}{'reason'} eq '_servers_inconsistency_' ){    # if we get servers inconsistency
                $restart = 1;   # do restart
                plog("something wrong with update domains stats... servers info is inconsistent... is going restart...");
            } else {
                plog("something wrong with update domains stats...");
            }
        }
    }

    my $max_mem = $self->{'proc_maxmem'} || 512 * 1024 * 1024; # process max memory 512Mb
    my $cur_mem = ETVA::Utils::process_mem_size($$);
    if( $cur_mem > $max_mem ){
        plog("process max memory exceeded $cur_mem > $max_mem.");
        $restart = 1;
    }

    my $max_childs = ETVA::Utils::process_max_childs();
    if( $max_childs ){
        my $num_childs = $self->get_number_childs();
        plog("process number of childs: $num_childs") if( &debug_level() > 7 );
        if( $num_childs > $max_childs ){
            plog("number of childs process exceeded $num_childs > $max_childs.");
        }
    }

    if( $restart ){
        plog("process going restart. Killing process...");
        $self->set_runout();   # set to run out
        $self->terminate_agent(); # go to the end
    }
}

sub terminate_agent {
    my $self = shift;
    plog("VirtAgentSOAP terminate_agent") if( &debug_level() > 7 );
    ETVA::Agent::SOAPFork->terminate_agent();
    VirtAgentInterface->exit_handler();
}

sub set_imparent {
    my $self = shift;
    plog("VirtAgentSOAP set_imparent") if( &debug_level() > 7 );
    ETVA::Agent::SOAPFork->set_imparent();
    VirtAgentInterface->set_imparent();
}

sub get_imchild {
    my $self = shift;
    plog("VirtAgentSOAP get_imchild") if( &debug_level() > 7 );
    ETVA::Agent::SOAPFork->get_imchild();
    VirtAgentInterface->get_imchild();
}

sub set_imchild {
    my $self = shift;
    plog("VirtAgentSOAP set_imchild") if( &debug_level() > 7 );
    ETVA::Agent::SOAPFork->set_imchild();
    VirtAgentInterface->set_imchild();
}

1;
