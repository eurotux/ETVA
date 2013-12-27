package ETVA::GuestAgent::Socket::SOAPFork;

use strict;

BEGIN {
    # this is the worst damned warning ever, so SHUT UP ALREADY!
    $SIG{__WARN__} = sub { warn @_ unless $_[0] =~ /Use of uninitialized value/ };

    require Exporter;
    require ETVA::GuestAgent::Socket;
    require ETVA::GuestAgent::SOAP::Fork;
    use vars qw($VERSION @ISA @EXPORT @EXPORT_OK %EXPORT_TAGS $CRLF);
    $VERSION = '0.0.1';
    @ISA = qw( ETVA::GuestAgent::Socket ETVA::GuestAgent::SOAP::Fork );
    @EXPORT = qw(  );
}

use ETVA::Client::SOAP::HTTP;

# new method
sub new {
    my $self = shift;
    
    unless( ref $self ){
        my $class = ref( $self ) || $self;

        my %params = @_;
        
        $self = $self->SUPER::new( %params );

        $self = bless $self => $class;
    }
	
	return $self;
}


# call required for to CM connection
sub call {
    my $self = shift;

    return new ETVA::Client::SOAP::HTTP( uri => $self->{'cm_uri'} )
                    -> call( @_ );
}

1;

