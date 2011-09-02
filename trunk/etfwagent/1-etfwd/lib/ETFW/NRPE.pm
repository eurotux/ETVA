#!/usr/bin/perl

=pod

=head1 NAME

ETFW::NRPE

=head1 SYNOPSIS

...

=head1 DESCRIPTION

...

=head1 METHODS

=over 4

=cut

package ETFW::NRPE;

use strict;

use ETVA::Utils;

my %CONF = ( "conf_file"=>"/etc/nagios/nrpe.cfg",
                "service_path"=>"/etc/init.d/nrpe" );

=item get_config

=cut

sub get_config {
    my $self = shift;

    my %lconf = ();
    %lconf = loadconfigfile($CONF{"conf_file"},\%lconf); 

    my %conf = ();
    for my $k (keys %lconf){
        if( $k =~ m/command\[(\w+)\]/ ){
            $conf{"commands"}{"$1"} = $lconf{"$k"};
        } else {
            $conf{"$k"} = $lconf{"$k"};
        }
    }

    return wantarray() ? %conf : \%conf; 
}

=item set_config

    ARGS: %p - set key=>value 
          commands - cmd=>"cmd line"

=cut

sub set_config {
    my $self = shift;
    my (%p) = @_;
   
    my %C = ();
    %C = loadconfigfile($CONF{"conf_file"},\%C); 

    # TODO
    #   make this recursive
    for my $k (keys %p){
        if( $k eq "commands" && 
            ( ref($p{"$k"}) eq 'HASH' ) ){

            my $cmd = $p{"$k"};
            for my $c (keys %$cmd){
                my $kcmd = "command[$c]";
                $C{"$kcmd"} = $cmd->{"$c"};
            }
        } else {
            $C{"$k"} = $p{"$k"};
        }
    }

    saveconfigfile($CONF{"conf_file"},\%C,0,1);
}

=item add_command

    ARGS: command - command name
          line - command line

=cut

sub add_command {
    my $self = shift;
    my (%p) = @_;

    if( my $cmd = $p{"command"} ){
        my $kcmd = "command[$cmd]";
        return $self->set_config( $kcmd=>$p{"line"} );
    }
}

=item del_command

    ARGS: command - command to delete

=cut

sub del_command {
    my $self = shift;
    my (%p) = @_;

    if( my $cmd = $p{"command"} ){
        my %C = ();
        %C = loadconfigfile($CONF{"conf_file"},\%C); 
        my $kcmd = "command[$cmd]";
        if( delete $C{"$kcmd"} ){
            saveconfigfile($CONF{"conf_file"},\%C,0,1);
        }
        # TODO return error
    }
}

=item start

=cut

sub start {
    my $self = shift;
    if( -x $CONF{"service_path"} ){
        cmd_exec($CONF{"service_path"},"start");
    }
}

=item stop

=cut

sub stop {
    my $self = shift;
    if( -x $CONF{"service_path"} ){
        cmd_exec($CONF{"service_path"},"stop");
    }
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

