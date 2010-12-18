#!/usr/bin/perl
# Copywrite Eurotux 2009
# 
# CMAR 2009/04/03 (cmar@eurotux.com)

=pod

=head1 NAME

Agent - Class with main functions for agents

=head1 SYNOPSIS

    my $Agent = Agent->new( Port=>$port, LocalAddr=>$addr, Proto=>$proto );

    $Agent->mainLoop();

=head1 DESCRIPTION

...

=head1 METHODS

=over 4

=cut

package Agent;

use strict;

BEGIN {
    # this is the worst damned warning ever, so SHUT UP ALREADY!
    $SIG{__WARN__} = sub { warn @_ unless $_[0] =~ /Use of uninitialized value/ };

    require Exporter;
    use vars qw($VERSION @ISA @EXPORT @EXPORT_OK %EXPORT_TAGS  $CRLF);
    $VERSION = '0.0.1';
    @ISA = qw( Exporter );
    @EXPORT = qw( );
}

use Utils;

use POSIX;
use IO::Select;
use IO::Socket;
use Data::Dumper;

my $T_ALARM = 5 * 60;
my $RUNNING;

=item new

    my $Agent = Agent->new( Port=>$port, LocalAddr=>$addr, Proto=>$proto, _alarmhandler_=>sub{ ... } );

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
        $SIG{'TERM'} = \&term_handler;
        $SIG{'ALRM'} = $params{'_alarmhandler_'} ? 
                        sub { $params{'_alarmhandler_'}->(); alarm_handler(); }
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
sub idle {
    my $self = shift;

    plog("_idle_");
}
sub alarm_handler {

    plog("_alarm_");
    
    alarm($T_ALARM);
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

        # run
        while($RUNNING){
            plog("Running...");
            my @ready = $sel->can_read();
            foreach my $client (@ready) {
                if($client == $server) {
                    # Create a new socket
                    plog('accept');
                    my $new = $server->accept();
                    # Set REUSEADDR flag
#                    setsockopt($new,SOL_SOCKET,SO_REUSEADDR,1);
                    $new->sockopt(SO_REUSEADDR,1) or die("can't sockop!");
                    $sel->add($new);
                } else {
                    # Process socket
                    plog('process');

                    # disable alarm signal
                    alarm(0);
                    plog( nowStr(), " ", "deactivate alarm\n"); 

                    # Data processing
                    $self->processdata($client);

                    # re-activate alarm signal
                    plog( nowStr(), " ", "activate alarm\n"); 
                    alarm($T_ALARM);

                    # Maybe we have finished with the socket
                    plog('close');
                    $sel->remove($client);
                    $client->close;
                }
            }
            # Sleep
            sleep(1);
        }
        # close socket
        $server->close();
	} else {
        plog("Can't create the socket");
    }
}

sub term_handler {
	$RUNNING = 0;
	plog("Agent terminate!");
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

L<Client>

=cut

