#!/usr/bin/perl
# Copywrite Eurotux 2009
# 
# CMAR 2009/04/03 (cmar@eurotux.com)

=pod

=head1 NAME

ETVA::Agent - Class with main functions for agents

=head1 SYNOPSIS

    my $Agent = ETVA::Agent->new( Port=>$port, LocalAddr=>$addr, Proto=>$proto );

    $Agent->mainLoop();

=head1 DESCRIPTION

...

=head1 METHODS

=over 4

=cut

package ETVA::Agent;

use strict;

BEGIN {
    # this is the worst damned warning ever, so SHUT UP ALREADY!
    $SIG{__WARN__} = sub { warn @_ unless $_[0] =~ /Use of uninitialized value/ };

    require Exporter;
    use vars qw($VERSION @ISA @EXPORT @EXPORT_OK %EXPORT_TAGS $CRLF );
    $VERSION = '0.0.1';
    @ISA = qw( Exporter );
    @EXPORT = qw( );
}

use ETVA::Utils;

#use POSIX qw/SIGHUP SIGINT SIGTERM/;
use POSIX qw(:signal_h :errno_h :sys_wait_h);

use IO::Select;
use IO::Socket;
use Data::Dumper;

my $T_ALARM = 5 * 60;
my $RUNNING;
my $EXITSIGNAL;
my $_alarmhandler_;
my $lasttime_alarm = time();

my $REGISTER_OK = 0;

=item new

    my $Agent = ETVA::Agent->new( Port=>$port, LocalAddr=>$addr, Proto=>$proto, _alarmhandler_=>sub{ ... } );

    Port - Listen port

    LocalAddr - Listen address

    Proto - Protocol

    _alarmhandler_ - alarm handler

=cut

sub new {
    my $self = shift;
    
    unless( ref $self ){
        my $class = ref( $self ) || $self;

        my %params = @_;
        
        $self = bless {%params} => $class;

        $self->{'LocalPort'} = $self->{'LocalPort'} || $self->{'Port'};

        if( !$self->{'LocalPort'} ){
            die "LocalPort not defined";
            return;
        }	
        $T_ALARM = $self->{'T_ALARM'} if( $self->{'T_ALARM'} );

        # define signal handlers
        $SIG{'TERM'} = $params{'_termhandler_'} ? 
                                            sub { $params{'_termhandler_'}->(); &term_handler; }
                                            : \&term_handler;
        $SIG{'INT'} = $params{'_inthandler_'} ? 
                                            sub { $params{'_inthandler_'}->(); &int_handler; }
                                            : \&int_handler;
        $SIG{'HUP'} = $params{'_huphandler_'} ? 
                                            sub { $params{'_huphandler_'}->(); &hup_handler; }
                                            : \&hup_handler;
        my $chld_handler_ref = $params{'_chldhandler_'} ? 
                                            sub { $params{'_chldhandler_'}->(); &chld_handler(); }
                                            : \&chld_handler;
        $SIG{'CHLD'} = $chld_handler_ref;
        #my $sigset_chld = POSIX::SigSet->new( SIGCHLD );
        #my $sigaction_chld = POSIX::SigAction->new($chld_handler_ref, $sigset_chld, &POSIX::SA_NOCLDSTOP);
        #sigaction(SIGCHLD, $sigaction_chld);

        $SIG{'USR1'} = \&usr1_handler;
        $SIG{'USR2'} = \&usr2_handler;
        $_alarmhandler_ = $SIG{'ALRM'} = $params{'_alarmhandler_'} ? 
                                            sub { $params{'_alarmhandler_'}->(); &alarm_handler; }
                                            : \&alarm_handler;

    }
	
	return $self;
}

=item register

register agent method

    $Agent->register(); 

=cut

sub register {
    my $self = shift;

    plog("try register... no register");

    my $laddr = $self->{'LocalAddr'} || "*";
    my $port = $self->{'LocalPort'} = $self->{'LocalPort'} || $self->{'Port'};
    my $proto = $self->{'Proto'};

    plog("agent initialized listen in addr=$laddr port=$port proto=$proto");
}

sub set_registerok {
    my $self = shift;
    my ($v) = @_;
    return $REGISTER_OK = $v;
}

sub get_registerok {
    my $self = shift;
    return $REGISTER_OK;
}

sub terminate_agent {
plog("agent terminate");
}

sub idle {
    my $self = shift;

    plog("_idle_");
}
sub alarm_handler {

    plog("_alarm_ T_ALARM=$T_ALARM");
    
    $lasttime_alarm = time();

    alarm($T_ALARM);
}

sub _idle_ {
    my $self = shift;

    $self->check_past_alarm_events();
}

sub check_past_alarm_events {
    my $now_ttl = time();

    my $diff_ttl = $now_ttl - $lasttime_alarm;
    #plog "check_past_alarm_events if diff_ttl $diff_ttl > $T_ALARM";
    if( $diff_ttl > $T_ALARM ){
        alarm(0);
        $_alarmhandler_->();
    }
}

sub set_t_alarm {
    my $self = shift;
    my (%p) = @_;
    $self->{'T_ALARM'} = $T_ALARM = $p{'T_ALARM'};
}
=item mainLoop

Agent loop

    $Agent->mainLoop();

=cut

sub mainLoop {
	my ($self) = @_;
	
	$RUNNING = 1;
	
    my $laddr = $self->{'LocalAddr'};
    my $lport = $self->{'LocalPort'} = $self->{'LocalPort'} || 7000;
    my $proto = $self->{'Proto'} = $self->{'Proto'} || 'tcp';

	my $server = new IO::Socket::INET( Listen => 1,
                                        LocalAddr => $laddr,
                                        LocalPort => $lport,
                                        Proto => $proto,
                                        ReuseAddr => 1
                                        );
    if( $server ){
        # register agent
        $self->register();

        $server->sockopt(SO_REUSEADDR,1) or die("can't sockopt!");
        my $sel = new IO::Select( $server );
        
        plog( nowStr(), " ", "activate alarm\n"); 
        alarm($T_ALARM);

        my $select_timeout = 0.020;

        # run
        plog("Running...");
        while($RUNNING){
            my @ready = $sel->can_read($select_timeout);
            foreach my $client (@ready) {
                if($client == $server) {
                    # Create a new socket
                    plog( nowStr(), " ", "accept"); 
                    my $new = $server->accept();
                    # Set REUSEADDR flag
                    $new->sockopt(SO_REUSEADDR,1) or die("can't sockop!");
                    $sel->add($new);
                } else {
                    # Process socket
                    plog('process');

                    # disable alarm signal
                    alarm(0);
                    plog( nowStr(), " ", "deactivate alarm\n"); 


                    # ignore SIGCHLD
                    #my $sigset = POSIX::SigSet->new(SIGCHLD);    # define the signals to block
                    #my $old_sigset = POSIX::SigSet->new;        # where the old sigmask will be kept

                    #sigprocmask(SIG_BLOCK, $sigset, $old_sigset);

                    #my $bkp_chld_handler = $SIG{CHLD};
                    #$SIG{CHLD} = 'DEFAULT';

                    # Data processing
                    $self->processdata($client);

                    #$SIG{CHLD} = $bkp_chld_handler;

                    # recover SIGCHLD
                    #sigprocmask(SIG_UNBLOCK, $old_sigset);

                    # re-activate alarm signal
                    plog( nowStr(), " ", "activate alarm\n"); 
                    alarm($T_ALARM);

                    # Maybe we have finished with the socket
                    plog('close');
                    $sel->remove($client);
                    $client->close;

                    # check if have past alarm events
                    $self->check_past_alarm_events();
                }
            }

            $self->_idle_();

            # Sleep
            sleep(1);
        }
        # close socket
        $server->close();

        $self->terminate_agent();
	} else {
        plog("Can't create the socket");
    }

    return $EXITSIGNAL;
}

sub usr1_handler {
    &debug_inc;
    $SIG{'USR1'} = \&usr1_handler;
}
sub usr2_handler {
    &debug_dec;
    $SIG{'USR2'} = \&usr2_handler;
}
sub chld_handler {
    plog "ETVA::Agent receiving chld signal..." if( &debug_level() > 5 );
}
sub set_runout {
	$RUNNING = 0;
}
sub int_handler {
    &set_runout;
    $EXITSIGNAL = SIGINT;
}
sub hup_handler {
    &set_runout;
    $EXITSIGNAL = SIGHUP;
}
sub term_handler {
    &set_runout;
    $EXITSIGNAL = SIGTERM;
	plog("Receive TERM Signal... Agent terminate!");
}

=item processdata

=cut

sub processdata {
    my $self = shift;
    my ($fh) = @_;

    plog(nowStr()," ","Agent processing data");

    # treat handler
    if( my $handler = $self->{'_handler'} ){
        $handler->($fh);
    }
    
}

=item receive

=cut

sub receive {
    my $self = shift;
    my ($fh) = @_;

    my $data = '';
    while(<$fh>){ $data .= $_; };

    return $data;
}

=item send

=cut

sub send {
    my $self = shift;
    my ($fh,$data) = @_;
    
    $fh->send($data);
}

1;

=back

=pod

=head1 BUGS

...

=head1 AUTHORS

...

=head1 COPYRIGHT

...

=head1 LICENSE

...

=head1 SEE ALSO

L<ETVA::Client>

=cut

