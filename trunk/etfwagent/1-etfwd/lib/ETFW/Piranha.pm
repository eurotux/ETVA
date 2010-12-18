#!/usr/bin/perl

=pod

=head1 NAME

ETFW::Piranha

=head1 SYNOPSIS

...

=head1 DESCRIPTION

...

=head1 METHODS

=over 4

=cut

package ETFW::Piranha;

BEGIN {
    use vars qw($VERSION @ISA @EXPORT @EXPORT_OK %EXPORT_TAGS $CRLF $AUTOLOAD);
};

use strict;

use FileFuncs;

my %CONF = ( 'conf_file'=>'/etc/sysconfig/ha/lvs.cf', 'conf_dir'=>'/etc/sysconfig/ha',
                'service_cmd'=>'/etc/init.d/pulse' );

=item get_config

    get configuration

=cut

sub get_config {
    my $self = shift;

    my $lnum = 0;
    my %conf = ();
    my @parents = ();
    my $P = \%conf;
    unshift(@parents, $P);
    my $S = \%conf;
    open(F,$CONF{"conf_file"});
    while(<F>){
        chomp;
        s/#.*//g;
        if( /^\s*(\S+)\s*=\s*(.*)$/ ){
            $S->{"$1"} = $2;
        } elsif( /^\s*virtual\s+(\S+)\s*{/ ){
            $P = $S;
            unshift(@parents, $P);
            $S = { name=>$1, line=>$lnum, servers=>[] };
            push(@{$P->{"virtual"}},$S);
        } elsif( /^\s*server\s+(\S+)\s*{/ ){
            $P = $S;
            unshift(@parents, $P);
            $S = { name=>$1, line=>$lnum };
            push(@{$P->{"servers"}},$S);
        } elsif( /^\s*failover\s+(\S+)\s*{/ ){
            $P = $S;
            unshift(@parents, $P);
            $S = { name=>$1, line=>$lnum };
            push(@{$P->{"failover"}},$S);
        } elsif( /^\s*}/ ){
            $S->{"eline"} = $lnum;
            $S = shift(@parents);
            $P = $parents[0] || \%conf;
        }
        $lnum++;
    }
    close(F);
    return wantarray() ? %conf : \%conf;
}

=item set_config

    change configuration parameters

    ARGS: %p - parameters

=cut

sub set_config {
    my $self = shift;
    my (%p) = @_;
    
    my $cfref = read_file_lines($CONF{"conf_file"});
    for my $k (keys %p){
        my $v = $p{"$k"};
        my $gc = grep { s/^\s*($k)\s*=\s*(.*)$/$k = $v/ } @$cfref;
        if( !$gc ){
            push(@$cfref, "$k = $v");
        }
    }
    flush_file_lines($CONF{"conf_file"});
}

=item del_config

    delete configuration parameters

    ARGS: %p - parameters

=cut

sub del_config {
    my $self = shift;
    my (%p) = @_;

    my $lnum = 0;
    my $cfref = read_file_lines($CONF{"conf_file"});
    for my $l (@$cfref){
        if( /^\s*(\S+)\s*=\s*(.*)$/ && $p{"$1"} ){
            splice(@$cfref,$lnum,1);
        }
        $lnum++;
    }
    flush_file_lines($CONF{"conf_file"});
}

my %NoQuoteField = ( "nat_router"=>1, "address"=>1 );

sub hashtofilelines {
    my (%p) = @_;

    my $type = delete $p{"type"};
    my $name = delete $p{"name"};
    my $tn = delete $p{"t"} || 0;
    
    my $c = 4;
    my $tab = " ";
    my $tab1 = $tab x ( $c * $tn );
    my $tab2 = $tab x ( $c * ( $tn + 1 ) );

    my @ref = ();
    push(@ref, $tab1 . "$type $name {" );
    for my $k ( keys %p ){
        my $v = $p{"$k"};
        $v = '"' . $v . '"' if( $v =~ /\s/ && $v !~ /^".*"$/ && !$NoQuoteField{"$k"} );
        push(@ref, $tab2 . $k . " = " . $v );
    }
    push(@ref, $tab1 . "}" );

    return wantarray() ? @ref : \@ref;
}

sub add_hashconf {
    my $self = shift;
    my (%p) = @_;

    if( my $type = $p{"type"} ){
        if( my $name = $p{"name"} ){
            push_file_lines($CONF{"conf_file"}, hashtofilelines( %p ) );
        }
    }
}
sub del_hashconf {
    my $self = shift;
    my (%p) = @_;

    if( my $type = $p{"type"} ){
        if( my $name = $p{"name"} ){
            my %C = $self->get_config();
            if( my $lc = $C{"$type"} ){
                if( my ($S) = grep { $_->{"name"} eq $name } @$lc ){
                    splice_file_lines($CONF{"conf_file"},$S->{"line"},$S->{"eline"} - $S->{"line"} + 1 );
                }
            }
        }
    }
}
sub set_hashconf {
    my $self = shift;
    my (%p) = @_;

    if( my $type = $p{"type"} ){
        if( my $name = $p{"name"} ){
            my %C = $self->get_config();
            if( my $lc = $C{"$type"} ){
                if( my ($S) = grep { $_->{"name"} eq $name } @$lc ){
                    splice_file_lines($CONF{"conf_file"},$S->{"line"},$S->{"eline"} - $S->{"line"} + 1, hashtofilelines( %$S, %p ) );
                }
            }
        }
    }
}

=item add_failover / del_failover / set_failover

    add / remove / change failover

    ARGS: name - name failover service
            
          %p - other parameters

=cut

sub AUTOLOAD {
    my ($package,$method) = ( $AUTOLOAD =~ m/(.+)::([^:]+)$/ );

    if( my ($op,$type) = ($method =~ m/(add|del|set)_(\S+)/) ){
        my $func = "${op}_hashconf";
        my $tfunc = "conf_${type}";
        $AUTOLOAD = sub {
                        my $self = shift;
                        my (%p) = @_;
                        # make config for specific type
                        eval {
                            %p = $self->$tfunc( %p );
                        };
                        # make operation base config
                        return $self->$func( type=>$type, %p );
                    };
    }
    if( $AUTOLOAD ){
        &$AUTOLOAD;
    }
}

=item add_virtual / del_virtual / set_virtual

    add / remove / change virtual server

    ARGS: name - name of virtual server
          address - virtual address
          port - application port
          protocol - protocol
          %p - other parameters

=cut

sub conf_virtual {
    my $self = shift;
    my (%p) = @_;
    if( my $sendcmd = delete $p{"sendcmdtype"} ){
        $p{"protocol"} = "tcp";
        if( $sendcmd eq "http" ){
            $p{"port"} = "http";
            $p{"send"} = '"GET / HTTP/1.1\r\nHost: lvs\r\n\r\n"';
            $p{"expect"} = '"HTTP"';
        } elsif( $sendcmd eq "allok" ){
            $p{"port"} = "http";
            $p{"send_program"} = '"/usr/local/bin/all_ok.sh"';
            $p{"expect"} = '"OK"';
        } elsif( $sendcmd eq "pop3" ){
            $p{"port"} = "pop3";
            $p{"send_program"} = '"/usr/local/bin/pop3_test %h"';
            $p{"expect"} = '"OK"';
        } elsif( $sendcmd eq "smtp" ){
            $p{"port"} = "smtp";
            $p{"send_program"} = '"/usr/local/bin/smtp_test %h"';
            $p{"expect"} = '"OK"';
        }
    }
    return wantarray() ? %p : \%p;
}

=item add_server

    add real server to virtual server configuration

    ARGS: virtual - virtual server that belongs
          name - name of server
          address - server address
          port - port of server
          protocol - server protocol
          weight - weight of configuration

=cut

sub add_server {
    my $self = shift;
    my (%p) = @_;

    if( my $virtual = delete $p{"virtual"} ){
        if( my $name = $p{"name"} ){
            my %C = $self->get_config();
            if( my $lv = $C{"virtual"} ){
                if( my ($V) = grep { $_->{"name"} eq $virtual } @$lv ){
                    splice_file_lines($CONF{"conf_file"},$V->{"eline"},0, hashtofilelines( type=>"server", t=>1, %p ) );
                }
            }
        }
    }
}

=item del_server

    remove real server to virtual server configuration

    ARGS: virtual - virtual server that belongs
          name - name of server

=cut

sub del_server {
    my $self = shift;
    my (%p) = @_;

    if( my $virtual = delete $p{"virtual"} ){
        if( my $name = $p{"name"} ){
            my %C = $self->get_config();
            if( my $lv = $C{"virtual"} ){
                if( my ($V) = grep { $_->{"name"} eq $virtual } @$lv ){
                    if( my $lc = $V->{"servers"} ){
                        if( my ($S) = grep { $_->{"name"} eq $name } @$lc ){
                            splice_file_lines($CONF{"conf_file"},$S->{"line"},$S->{"eline"} - $S->{"line"} + 1 );
                        }
                    }
                }
            }
        }
    }
}

=item set_server

    change values of real server

    ARGS: virtual - virtual server that belongs
          name - name of server
          address - server address
          port - port of server
          protocol - server protocol
          weight - weight of configuration

=cut

sub set_server {
    my $self = shift;
    my (%p) = @_;

    if( my $virtual = delete $p{"virtual"} ){
        if( my $name = $p{"name"} ){
            my %C = $self->get_config();
            if( my $lv = $C{"virtual"} ){
                if( my ($V) = grep { $_->{"name"} eq $virtual } @$lv ){
                    if( my $lc = $V->{"servers"} ){
                        if( my ($S) = grep { $_->{"name"} eq $name } @$lc ){
                            splice_file_lines($CONF{"conf_file"},$S->{"line"},$S->{"eline"} - $S->{"line"} + 1, hashtofilelines( type=>"server", t=>1, %$S, %p ) );
                        }
                    }
                }
            }
        }
    }
}

=item view_routingtable

    show LVS routing table

=cut

sub view_routingtable {
    my $self = shift;

    my %R = ();
    my $lnum = 0;
    open(R,"/proc/net/ip_vs");
    while(<R>){
        chomp;
        if( $lnum > 2 ){ # ignore first 3 lines
            if( /^(\S+)\s+(\w+):(\w+)\s+(\S+)\s*(\s+(\S+))?$/ ){
                %R = ( prot=>$1,
                        localaddr=> $2,
                        localport=> $3,
                        scheduler=>$4, flags=>$6||"" ); 
                $R{"localaddr"} = join(".",map { hex } ($R{"localaddr"} =~ m/\w{2}/g));
                $R{"localport"} = hex($R{"localport"});
            } elsif( /^\s+->\s+(\w+):(\w+)\s+(\S+)\s+(\S+)\s+(\S+)\s+(\S+)$/ ){
                $R{"remoteaddr"} = $1;
                $R{"remoteport"} = $2;
                $R{"forward"} = $3;
                $R{"weight"} = $4;
                $R{"activeconn"} = $5;
                $R{"inactconn"} = $6;
                $R{"remoteaddr"} = join(".",map { hex } ($R{"remoteaddr"} =~ m/\w{2}/g));
                $R{"remoteport"} = hex($R{"remoteport"});
            }
        }
        $lnum++;
    }
    close(R);

    return wantarray() ? %R : \%R;
}

=item start

=cut

sub start {
    my $self = shift;
    if( -x $CONF{"service_cmd"} ){
        cmd_exec($CONF{"service_cmd"},"start");
    }
}

=item stop

=cut

sub stop {
    my $self = shift;
    if( -x $CONF{"service_cmd"} ){
        cmd_exec($CONF{"service_cmd"},"stop");
    }
}

=item restart

=cut

sub restart {
    my $self = shift;

    if( -x $CONF{"service_cmd"} ){
        cmd_exec($CONF{"service_cmd"},"restart");
    } else {
        $self->stop();
        $self->start();
    } 
}

=item status

=cut

sub status {
    my $self = shift;

    my %status = ();
    if( -x $CONF{"service_cmd"} ){
        my ($e,$m) = cmd_exec($CONF{"service_cmd"},"status");

        %status = ( status=>$e, message=>$m );
    }

    return wantarray() ? %status : \%status;
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

