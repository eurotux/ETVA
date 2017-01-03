#!/usr/bin/perl
# Copywrite Eurotux 2009
# 
# CMAR 2009/04/14 (cmar@eurotux.com)

=pod

=head1 NAME

ETVA::Agent::JSON - Agent class for treat JSON calls

=head1 SYNOPSIS

    my $Agent = ETVA::Agent::JSON->new( Port=>$port, LocalAddr=>$addr, Proto=>$proto );

    $Agent->mainLoop();

=head1 DESCRIPTION

...

=head1 METHODS

=over 4

=cut

package ETVA::Agent::JSON;

use strict;

BEGIN {
    # this is the worst damned warning ever, so SHUT UP ALREADY!
    $SIG{__WARN__} = sub { warn @_ unless $_[0] =~ /Use of uninitialized value/ };

    require Exporter;
    require ETVA::Agent;
    use vars qw($VERSION @ISA @EXPORT @EXPORT_OK %EXPORT_TAGS  $CRLF);
    $VERSION = '0.0.1';
    @ISA = qw( ETVA::Agent );
    @EXPORT = qw(  );
}

use ETVA::Utils;

use POSIX;
use IO::Select;
use IO::Socket;
use Data::Dumper;
use JSON;

my ($dispatcher,$method,$args);

=item new

    my $Agent = ETVA::Agent::JSON->new( Port=>$port, LocalAddr=>$addr, Proto=>$proto );

=cut

sub new {
    my $self = shift;
    my $class = ref($self) || $self;

    $self = $self->SUPER::new(@_);
    return $self;
}

sub processdata {
    my $self = shift;
    my ($fh) = @_;

    plog("ETVA::Agent::JSON processing data");

    # Get data
    my $data = '';
    while($fh->recv($data,POSIX::BUFSIZ, 0)){ }
#    plog("Final DATA: $data");

    my $out;
    # catch error
    eval {
        $out = $self->treatCall($data);
    };
    if( $@ ){
        plog("Error invoke $dispatcher->$method($args): $@");
    }

    $fh->send($out);

}

sub treatCall {
    my $self = shift;
    my $data = shift;

    plog("debug data=$data");

    # Dispatcher Model
    $dispatcher = $self->{'_dispatcher'};

    # distiller data
    # method and args
    ($method,$args) = distiller($data);

    plog("exec dispatcher=$dispatcher method=$method args=$args");
    # exec method
    my $res = $dispatcher->$method($args);
    
    plog("debug res=".Dumper($res));
    # convert result
    my $msg = generator($res);

    return $msg;
}

sub distiller {
    my ($data) = @_;

    # decode json message
    my $H = decode_json($data);
    
    my $method = $H->{method};
    my $args = $H->{args};

    if( wantarray() ){
        return ($method,$args);
    } else {
        return { 'method' => $method, 'args' => $args };
    }
}
sub generator {
    my ($res) = @_;
    # encode to json 
    return encode_json({'res'=>$res});
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

L<ETVA::Agent>, L<ETVA::Client>

=cut

