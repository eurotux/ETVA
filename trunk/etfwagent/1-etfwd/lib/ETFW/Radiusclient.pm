#!/usr/bin/perl

=pod

=head1 NAME

ETFW::Radiusclient

=head1 SYNOPSIS

...

=head1 DESCRIPTION

...

=head1 METHODS

=over 4

=cut

package ETFW::Radiusclient;

use strict;

use ReadSimpleConfig;

my %CONF = ( "conf_file"=>"/etc/radiusclient/radiusclient.conf",
                "conf_path"=>"/etc/radiusclient/",
                "keyservers_file"=>"/etc/radiusclient/servers" );

=item add_keyserver

    ARGS: key - key server
          server - server

=cut

sub add_keyserver {
    my $self = shift;
    my (%p) = @_;
    my $key = $p{"key"};
    my $server = $p{"server"};
    open(F,">>",$CONF{"keyservers_file"});
    print F "$server\n$key","\n";
    close(F);
}

=item del_keyserver

    ARGS: server - server

=cut

sub del_keyserver {
    my $self = shift;
    my (%p) = @_;

    if( my $server = $p{"server"} ){
        open(F,$CONF{"keyservers_file"});
        while(<F>){
            if( !/^\s*$server/ ){
                print F $_; 
            }
        }
        close(F);
    }
}

sub read_config {
    my ($file) = @_;

    my $cfg = new ReadSimpleConfig( filename=>$file, syntax=>"simple" );

    my %C = ();
    if( my $C = $cfg->{"_DATA"} ){
        for my $k (keys %$C){
            my $l = $C->{"$k"};
            $C{"$k"} = (scalar(@$l)>1)? $l: $l->[0]||"";
        }
    }

    return wantarray() ? %C : \%C;
}

=item get_config

    get configuration values

=cut

sub get_config {
    my $self = shift;

    return read_config( $CONF{"conf_file"} );
}

=item set_config

    set configuration values

    ARGS: %p - parameteres

=cut

sub set_config {
    my $self = shift;
    my (%p) = @_;
   
    my $cfg = new ReadSimpleConfig( filename=>$CONF{"conf_file"}, syntax=>"simple" );

    for my $k (keys %p){
        $cfg->param($k,$p{"$k"});
    }
    $cfg->write();
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

=cut

