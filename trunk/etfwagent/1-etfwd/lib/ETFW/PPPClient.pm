#!/usr/bin/perl

=pod

=head1 NAME

ETFW::PPPClient

=head1 SYNOPSIS

...

=head1 DESCRIPTION

...

=head1 METHODS

=over 4

=cut

package ETFW::PPPClient;

use strict;

use Utils;
use FileFuncs;

my %CONF = ( 'conf_file'=>"/etc/wvdial.conf", "wvdial_bin"=>"/usr/bin/wvdial" );

=item get_config

=cut

sub get_config {
    my $self = shift;
   
    my %conf = ( Default=>[] );
    my $D = $conf{"Default"};
    open(C,$CONF{"conf_file"});
    my $lnum = 0;
    while(<C>){
        chomp;
        s/#.*//;
        if( /\[Dialer\s+(\S+)\]/ ){
            my $dialer = $1;
            $D = $conf{"$dialer"};
            if( !$D ){
                $D = $conf{"$dialer"} = [];
            }
        } elsif( $_ && /^\s*([^=]+)\s+?=\s+?(.*)$/ ){
            my $k = $1;
            my $v = $2;
            push(@$D, { name=>$k, value=>$v, line=>$lnum });
        }
        $lnum++;
    }
    close(C);

    return wantarray() ? %conf : \%conf;
}

sub save_config {
    my $self = shift;
    my (%C) = @_;

    my $cl = read_file_lines($CONF{"conf_file"});
    for my $d ( keys %C ){
        my @bl;
        for my $L (@{$C{"$d"}}){
            if( defined $L->{"line"} ){
                splice(@$cl,$L->{"line"},1,"$L->{name} = $L->{value}");
            } else {
                push(@bl,"$L->{name} = $L->{value}");
            }
        } 
        if( @bl ){
            my $nbl = join("\n",@bl);
            if( ! grep { s/\[Dialer $d\]/$&\n$nbl/ } @$cl ){
                push(@$cl,"[Dialer $d]");
                push(@$cl,@bl);
            }
        }
    }
    flush_file_lines($CONF{"conf_file"});
}

=item set_config

    ARGS: dialer - the dialer ( default: Default )
          key1,...,keyn - key param
          value1,...,valuen - value param

=cut

sub set_config {
    my $self = shift;
    my (%p) = @_;

    my %C = $self->get_config();
    my $dialer = $p{"dialer"} || "Default";
    my $D = $C{"$dialer"};
    if( !$D ){
        $D = $C{"$dialer"} = [];
    }

    for my $k (keys %p){
        if( $k =~ /^key(\w+)/ ){
            my $n = $p{"$k"};
            my $v = $p{"value$1"};
            if( my ($L) = grep { $_->{"name"} eq $n } @$D ){
                $L->{"value"} = $v;
            } else {
                push(@$D, { name=>$n, value=>$v });
            }
        }
    }
    $self->save_config(%C);
}

=item ppp_connect

    Connect ppp with wvdial

    ARGS: dialer - dialer

=cut

sub ppp_connect {
    my $self = shift;

    my (%p) = @_;

    # TODO fork this
    my ($e,$m) = cmd_exec($CONF{"wvdial_bin"},$p{"dialer"});

    if( $e == 0 ){
        my $connected;
        if( $m =~ /IP\s+address\s+is\s+(\d+\.\d+\.\d+\.\d+)/is ){
            # Connected OK!
            $connected = $1 eq "0.0.0.0" ? "*" : $1;
        } elsif( $m =~ /starting\s+ppp/is) {
            # Connected in stupid mod
            $connected = "*";
        } elsif( !$m ){
            return retErr("_ERR_PPPCONNECT_FAILED_","Connection to wvdial failed...");
        }
        return retOk("_OK_PPPCONNECT_","Wvdial connected to '$connected'");
    } else {
        return retErr("_ERR_PPPCONNECT_","Error connect wvdial.");
    }
}

=item ppp_disconnect

    shutdown ppp connection
    
=cut

sub ppp_disconnect {
    my $self = shift;

    if( my $pid = $self->get_wvdial_pid() ){
        cmd_exec("/bin/kill",$pid);
        return retOk("_OK_PPPDISCONNECT_","Wvdial disconnected.");
    } else {
        return retErr("_ERR_PPPDISCONNECT_","Wvdial not running.");
    }
}

sub get_wvdial_pid {
    my $self = shift;

    foreach my $p (list_processes()) {
        if( $p->{'args'} =~ /^\S*wvdial($|\s)/ && $p->{'args'} !~ /\[/ ){
            return $p->{'pid'};
        }
    }
    return;
}

=item ppp_status

    get ppp connection status

=cut

sub ppp_status {
    my $self = shift;

    my %res = ( connected=>0 ); 
    if( $self->get_wvdial_pid() ){
        $res{"connected"} = 1;
    }

    return wantarray() ? %res : \%res;
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

