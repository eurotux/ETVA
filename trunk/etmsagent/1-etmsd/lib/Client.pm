#!/usr/bin/perl 
# Copywrite Eurotux 2009
# 
# CMAR 2009/04/17 (cmar@eurotux.com)

=pod

=head1 NAME

Client - Module for client functions

=head1 SYNOPSIS

    my $Client = Client->new( PeerAddr=>$addr, PeerPort=>$port, Proto=>$porto );

    my $response = $Client->send_receive( $request );

=head1 DESCRIPTION

...

=head1 METHODS

=over 4

=cut

package Client;

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

use POSIX;
use IO::Socket;

my $BLOCK = 0;

=item new

    my $Client = Client->new( PeerAddr=>$addr, PeerPort=>$port, Proto=>$porto );

=cut

sub new {
    my $self = shift;
    
    unless( ref $self ){
        my $class = ref( $self ) || $self;

        my (%params) = @_;
        
        $self = bless {%params} => $class;

        $BLOCK = 1 if( $params{'blocking'} ); 

        my $sock;
        if( $params{'path'} || $params{'Peer'} ){
            my $peer = $params{'path'} || $params{'Peer'};
            unlink "$params{'path'}";
            $sock = $self->{'_sock'} =
                            new IO::Socket::UNIX (
                                Peer     => $peer,
                                Type     => SOCK_STREAM
                            );
        } else {
            $sock = $self->{'_sock'} =
                            new IO::Socket::INET (
                                PeerAddr => $params{'address'},
                                PeerPort => $params{'port'},
                                Proto    => $params{'proto'},
                            );
        }
        die "Could not create socket: $!\n" unless $sock;
    }
	
	return $self;
}

sub receive {
    my $self = shift;

    my $data = '';
    my $fh = $self->{'_sock'};  # file handle
    while(<$fh>){ $data .= $_; };

    return $data;
}
sub send {
    my $self = shift;
    my ($data) = @_;
    
    $self->{'_sock'}->send($data);
}

=item send_receive

send request and receive response

    my $response = $Client->send_receive( $request );

=cut

sub send_receive {
    my $self = shift;

    $self->{'_sock'}->autoflush(1);
    
    $self->send( @_ );

    shutdown($self->{'_sock'},1) if( !$BLOCK );   # no more write

    my $response = $self->receive( );

    shutdown($self->{'_sock'},0) if( !$BLOCK );   # no more reads

    shutdown($self->{'_sock'},2);   # bye

    return $response;
}

=item splitOps

split params to make hash

    my %Hash = $Client->splitOps( "p1=v1", ..., "pn=vn" );

=cut

# splitOps: make operation parameters
#   Ex:
#       name=val - { name => val }
#       p1_p2=val - { 'p1_p2' =>  val }
#       -name_op=val - { name => { op => val } }
#
sub splitOps {
    sub splitOpsRec {
        my ($op,$val,$OPS) = @_;
        # split by _
        my @os = ($op =~ s/^-//)?(split(/_/,$op)):($op);
        if( scalar(@os) > 1 ){
            my $o = shift(@os);

            # if op starts with _
            if( $op =~ m/^_/ ){
                $o = "_" . shift(@os);
            }

            # initialize
            $OPS->{"$o"} = {} if( !$OPS->{"$o"} );
            # go recursive
            splitOpsRec(join("_",@os),$val,$OPS->{"$o"});
        } else {
            # stop condition
            $OPS->{"$op"} = defined($val) ? $val : 1;
        }
    }

    my $self = shift;
    my (@ops) = @_;
    my %Ops = ();
    for my $o (@ops){
        if( my ($o1,$v1) = split(/=/,$o,2) ){
            splitOpsRec($o1,$v1,\%Ops);
        }
    }
    return %Ops;
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

L<Agent>

=cut

