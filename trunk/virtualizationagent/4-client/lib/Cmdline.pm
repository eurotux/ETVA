#!/usr/bin/perl
# Copywrite Eurotux 2009
# 
# CMAR 2009/07/07 (cmar@eurotux.com)

=pod

=head1 NAME

Cmdline

=head1 SYNOPSIS

...

=head1 DESCRIPTION

...

=head1 METHODS

=over 4

=cut

package Cmdline;

use strict;

BEGIN {
    # this is the worst damned warning ever, so SHUT UP ALREADY!
    $SIG{__WARN__} = sub { warn @_ unless $_[0] =~ /Use of uninitialized value/ };

    require Client::SOAP::HTTP;

    use vars qw($VERSION @ISA @EXPORT @EXPORT_OK %EXPORT_TAGS  $CRLF);
    $VERSION = '0.0.1';
    @ISA = qw( Client::SOAP::HTTP );
    @EXPORT = qw( );
}

use JSON;

=item new

=cut

sub new {
    my $self = shift;
    
    $self = $self->SUPER::new( @_ );
	
	return $self;
}

sub call {
    my $self = shift;

    my %r = $self->SUPER::call( @_ );

    my %h = $self->distiller( %r );
    return wantarray() ? %h : \%h;
}

sub distiller {
    my $self = shift;

    my @l = ();
    for my $a ( @_ ){
        if( ref($a) eq 'HASH' ){
            my %h = ();
            for my $k (keys %$a){
                $h{"$k"} = decode_json($a->{"$k"});
            }
            $a = \%h;
        } elsif( ref($a) eq 'ARRAY' ){
            my @l = ();
            for my $e (@$a){
                push(@l,decode_json($e));
            }
            $a = \@l;
        } else {
            my $bkp_a = $a;
            eval {
                $a = decode_json($a);
            };
            if( $@ ){
                $a = $bkp_a;
            }
        }
        push(@l,$a);
    }
    return @l;
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

