package ETVA::GuestAgent::Socket;

use strict;

BEGIN {
    # this is the worst damned warning ever, so SHUT UP ALREADY!
    $SIG{__WARN__} = sub { warn @_ unless $_[0] =~ /Use of uninitialized value/ };

    require Exporter;
    require ETVA::GuestAgent;
    use vars qw($VERSION @ISA @EXPORT @EXPORT_OK %EXPORT_TAGS $CRLF);
    $VERSION = '0.0.1';
    @ISA = qw( ETVA::GuestAgent );
    @EXPORT = qw(  );
}

use ETVA::Utils;

use IO::Socket;
use POSIX;

my %clients;			# client socket descriptor hash

# new method
sub new {
    my $self = shift;
    
    unless( ref $self ){
        my $class = ref( $self ) || $self;

        my %params = @_;
        
        $self = $self->SUPER::new( %params );

        #$self = bless {%params} => $class;
        $self = bless $self => $class;

        $self->{'LocalPort'} = $self->{'LocalPort'} || $self->{'Port'};

        $self->{'LocalAddr'};
        $self->{'LocalPort'} ||= 7000;
        $self->{'Proto'} ||= 'tcp';

        # create socket INET
        $self->{'server'} = new IO::Socket::INET( Listen => 1,
                                                    LocalAddr => $self->{'LocalAddr'},
                                                    LocalPort => $self->{'LocalPort'},
                                                    Proto => $self->{'Proto'},
                                                    ReuseAddr => 1
                                            );
    
        if( !$self->{'server'} ){
            plogNow("[ERROR] ETVA::GuestAgent::Socket: Can't create the socket: $!");
            die("Can't create the socket: $!");
        }

        $self->{'select'}->add($self->{'server'});
    }
	
	return $self;
}

# process client
sub processclient {
    my $self = shift;
    my ($client) = @_;

    if( $client == $self->{'server'} ){

        plogNow("[INFO] ETVA::GuestAgent::Socket mainLoop: accept") if( &debug_level > 3 ); 

        # accept new socket connection
        my $new = $self->{'server'}->accept();

        # Set REUSEADDR flag
        $new->sockopt(SO_REUSEADDR,1) or die("can't sockop!");
        $self->{'select'}->add($new);

        $clients{"$new"} = $new;

    } else {

        # no block
        $client->blocking(0);

        # Get data
        my $request = $self->receive($client);

        if( $request ){

            plogNow("[INFO] ETVA::GuestAgent::Socket request=$request") if( &debug_level > 3 ); 

            # treat request
            $self->treatRequest($client,$request);
        }
    }
}

# send to client
sub send {
    my $self = shift;
    my ($client,@data) = @_;

    $self->SUPER::send($client,@data);

    $self->endclient($client);
}

sub endclient {
    my $self = shift;
    my ($client) = @_;

    # send response remove from IO select
    $self->{'select'}->remove($client);
    delete $clients{$client};
    close $client;
}

# idle
sub idle {
    my $self = shift;
    $self->SUPER::idle();
}

# end agent and close socket
sub terminate_agent {
    my $self = shift;

    $self->{'server'}->close;
}

1;
