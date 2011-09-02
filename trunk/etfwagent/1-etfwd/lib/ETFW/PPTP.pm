#!/usr/bin/perl

=pod

=head1 NAME

ETFW::PPTP

=head1 SYNOPSIS

...

=head1 DESCRIPTION

...

=head1 METHODS

=over 4

=cut

package ETFW::PPTP;

use strict;

use ETVA::Utils;
use FileFuncs;

BEGIN {
    require Exporter;

    use vars qw($VERSION @ISA @EXPORT @EXPORT_OK %EXPORT_TAGS $CRLF $AUTOLOAD);
    $VERSION = '0.0.1';
    @ISA = qw( Exporter );
    @EXPORT = qw( parse_ppp_options );
};

my %CONF = ( 'binary_file'=>'/usr/sbin/pppd', 'peers_dir'=>'/etc/ppp/peers',
                'pap_file'=>'/etc/ppp/chap-secrets',
                'options_file'=>'/etc/ppp/options.pptp' );

sub new {
    my $self = shift;

    unless( ref($self) ){
        $self = bless { %CONF, @_ }, $self;
    }
    return $self;
}

=item get_global_options

    get global options using options_file config file
    
    /etc/ppp/options.pptp

=cut

sub get_global_options {
    my $self = shift;

    $self = $self->new() if( ! ref($self) );

    my $file = $self->{"options_file"} || $CONF{"options_file"};

    my @opts = parse_ppp_options($file);

    return wantarray() ? @opts : \@opts;
}

=item set_global_options

    set global options using options_file config file
    
    /etc/ppp/options.pptp

    ARGS: %p - parameters

=cut

sub set_global_options {
    my $self = shift;
    my (%p) = @_;

    $self = $self->new() if( ! ref($self) );

    my $file = $self->{"options_file"} || $CONF{"options_file"};
    my $cfref = read_file_lines($file);
    foreach my $opt (keys %p) {
        my $val = $p{"$opt"};

        my $grep_count = s/^(\s*$opt\s+)([^#]*)(.*)$/$1$val$3/;
        if( !$grep_count ){
            push @$cfref, "$opt $val";
        }
    }
    flush_file_lines($file);
}

# parse_ppp_options(file)
sub parse_ppp_options {

    my ($file) = @_;

    my @rv;
    my $lnum = 1;

    open(OPTS, $file);
    while(<OPTS>) {
        s/\r|\n//g;
        if (/^#\s*(.*)/) {
            # A comment, used to store meta-information
            push(@rv, { 'comment' => $1,
                        'file' => $file,
                        'line' => $lnum,
                        'index' => scalar(@rv) });
        } elsif (/^([0-9\.]+):([0-9\.]+)/) {
            # A local/remote IP specification
            push(@rv, { 'local' => $1,
                        'remote' => $2,
                        'file' => $file,
                        'line' => $lnum,
                        'index' => scalar(@rv) });
        } elsif (/^([^# ]*)\s*([^#]*)/) {
            # A PPP options directive
            push(@rv, { 'name' => $1,
                        'value' => $2,
                        'file' => $file,
                        'line' => $lnum,
                        'index' => scalar(@rv) });
        }
        $lnum++;
    }
    close(OPTS);
    return @rv;
}

=item add_username

    add username to config file pap_file

    /etc/ppp/chap-secrets

    ARGS: username - user name
          secret - the secret
          server - server type ( default: pptp )
          ipaddress - IP address ( defaull: * - any )
    
=cut

sub add_username {
    my $self = shift;
    my (%p) = @_;

    $self = $self->new() if( ! ref($self) );

    my $file = $self->{"pap_file"} || $CONF{"pap_file"};

    if( my $username = $p{"username"} ){

        if( my $secret = $p{"secret"} ){

            my $cfref = read_file_lines($file);

            my $server = $p{"server"} || "pptp";
            my $ipaddr = $p{"ipaddress"} || "*";
            
            my $val = "$server $secret $ipaddr";

            my $grep_count = s/^(\s*$username\s+)([^#]*)(.*)$/$1$val$3/;
            if( !$grep_count ){
                push @$cfref, "$username $val";
            }

            flush_file_lines($file);
        }
    }
}

=item del_username

    remove user authentication

    ARGS: username - user name
          server - server type (default: pptp)
          ipaddress - IP address (default: * - any)

=cut

sub del_username {
    my $self = shift;
    my (%p) = @_;

    $self = $self->new() if( ! ref($self) );

    my $file = $self->{"pap_file"} || $CONF{"pap_file"};

    if( my $username = $p{"username"} ){

        my $cfref = read_file_lines($file);

        my $server = $p{"server"} || "pptp";
        my $ipaddr = $p{"ipaddress"} || "*";
        
        my @toremove = grep { /^(\s*$username\s+)/ } @$cfref;
        if( scalar(@toremove) ){
            for my $L (@toremove){
                splice(@$cfref,$L->{"index"},1);
            }
            flush_file_lines($file);
        }
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

=pod

=head1 NAME

ETFW::PPTP::Client

=head1 SYNOPSIS

...

=head1 DESCRIPTION

...

=head1 METHODS

=over 4

=cut

package ETFW::PPTP::Client;

use strict;

use FileFuncs;
use ETFW::PPTP;
use ETFW::Network;
use ETVA::Utils;

BEGIN {
    use vars qw($VERSION @ISA @EXPORT @EXPORT_OK %EXPORT_TAGS $CRLF $AUTOLOAD);
    $VERSION = '0.0.1';
    @ISA = qw( ETFW::PPTP );
    @EXPORT = qw( );
};

my %CONF = ( 'binary_file'=>'/usr/sbin/pppd', 'peers_dir'=>'/etc/ppp/peers',
                'pap_file'=>'/etc/ppp/chap-secrets',
                'options_file'=>'/etc/ppp/options.pptp', timeout=>30 );

sub new {
    my $self = shift;

    return $self->SUPER::new( %CONF, @_ );
}

=item add_tunnel

    create tunnel

    ARGS: tunnel - tunnel name
          server - server address

=cut

sub add_tunnel {
    my $self = shift;
    my (%p) = @_;

    $self = $self->new() if( ! ref($self) );

    if( my $tun = delete($p{"tunnel"}) ){
        my $peers_dir = $self->{"peers_dir"} || $CONF{"peers_dir"};
        my $file = "$peers_dir/$tun";
        if( ! -f "$file" ){
            my $cfref = read_file_lines($file);
            my $server = delete $p{"server"};
            push(@$cfref, "# PPTP Tunnel configuration for tunnel $tun" );
            push(@$cfref, "# Server IP: $server");
            for my $k (keys %p){
                my $v = $p{"$k"};
                push(@$cfref, "$k $v" );
            }
            flush_file_lines($file);
        } else {
            return retErr("_err_","Tunnel exists");
        }
    }
}

=item del_tunnel

    remove tunnel

    ARGS: tunnel - tunnel name

=cut

sub del_tunnel {
    my $self = shift;
    my (%p) = @_;

    $self = $self->new() if( ! ref($self) );

    if( my $tun = delete($p{"tunnel"}) ){
        my $peers_dir = $self->{"peers_dir"} || $CONF{"peers_dir"};
        my $file = "$peers_dir/$tun";
        if( -f "$file" ){
            my @opts = parse_ppp_options($file);
            if( grep { $_->{"comment"} =~ "PPTP" } @opts ){
                unlink($file);
            } else {
                return retErr("_err_","The tunnel is not valid");
            }
        }
    }
}

=item list_tunnels

    list tunnels created

=cut

sub list_tunnels {
    my $self = shift;

    $self = $self->new() if( ! ref($self) );

    my $dir = $self->{"peers_dir"} || $CONF{"peers_dir"};

    my @tunnels = ();

    opendir(D,"$dir");
    for my $f ( readdir(D) ){
        next if( $f =~ /^\./ );

        my $file = "$dir/$f";
        my @opts = parse_ppp_options($file);
        if( grep { $_->{"comment"} =~ "PPTP" } @opts ){
            push(@tunnels, { 'name'=>$f, 'file'=>$file, 'opts'=>\@opts });
        }
    }
    closedir(D);

    return wantarray() ? @tunnels : \@tunnels;
}

=item list_connected

    list tunnels connected

=cut

sub list_connected {
    my $self = shift;

    my @lproc = list_processes();

    my @lcon = ();

    for my $P (@lproc){
        if( $P->{"args"} =~ m/pppd\s.*call\s+(.*\S+)/ ){
            push( @lcon, { pid=>$P->{"pid"}, tunnel=>$1 } );
        }
    }

    return wantarray() ? @lcon : \@lcon;
}

=item connect_tunnel

    connect to tunnel

    ARGS: tunnel - tunnel name

=cut

sub connect_tunnel {
    my $self = shift;
    my (%p) = @_;

    $self = $self->new() if( ! ref($self) );

    my $binary_file = $self->{"binary_file"} || $CONF{"binary_file"};

    if( my $tun = $p{"tunnel"} ){
        if( my ($tunnel) = grep { $_->{'name'} eq $tun } $self->list_tunnels() ){
            if( ! grep { $_->{"tunnel"} eq $tun } list_connected() ){
                cmd_exec("/sbin/modprobe","ip_gre");
                cmd_exec($binary_file,quotemeta($tunnel->{'server'}),"call",quotemeta($tunnel->{'name'}));

                my %sifaces = map { $_->{'fullname'}, $_->{'address'} } get_ppp_ifaces();

                my $newiface;

                my $start = now();
                my $timeout = $self->{"timeout"} || $CONF{"timeout"};
                WHILE: while( now() - $start < $timeout ){
                    my @lifaces = get_ppp_ifaces();
                    for my $i (@lifaces){
                        if( !$sifaces{$i->{'fullname'}} ){
                            $newiface = $i;
                            last WHILE;
                        }
                    }
                }

                if( $newiface ){
                    if( my @lroutes = @{$tunnel->{'routes'}} ){
                        my @oldroutes = ETFW::Network::list_routes();
                        my ($defroute) = grep { $_->{'dest'} eq '0.0.0.0' } @oldroutes;
                        my $oldgw;
                        $oldgw = $defroute->{'gateway'} if ($defroute);
                        for my $r ( @lroutes ){
                            my $cmd = "route $r";
                            $cmd =~ s/TUNNEL_DEV/$newiface->{'fullname'}/g;
                            $cmd =~ s/DEF_GW/$oldgw/g;
                            $cmd =~ s/GW/$newiface->{'ptp'}/g;
                            cmd_exec("$cmd");
                        }
                    }
                }
            }
        }
    } 
}

=item get_ppp_ifaces

    show ppp interfaces

=cut

sub get_ppp_ifaces {
    return grep { $_->{'fullname'} =~ /^ppp(\d+)$/ &&
                    $_->{'up'} && $_->{'address'} } ETFW::Network::active_interfaces();
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

=pod

=head1 NAME

ETFW::PPTP::Server

=head1 SYNOPSIS

...

=head1 DESCRIPTION

...

=head1 METHODS

=over 4

=cut

package ETFW::PPTP::Server;

use strict;

use ETVA::Utils;
use FileFuncs;
use ETFW::PPTP;

BEGIN {
    use vars qw($VERSION @ISA @EXPORT @EXPORT_OK %EXPORT_TAGS $CRLF $AUTOLOAD);
    $VERSION = '0.0.1';
    @ISA = qw( ETFW::PPTP );
    @EXPORT = qw( );
};

my %CONF = ( 'conf_file'=>'/etc/pptpd.conf','pid_file'=>'/var/run/pptpd.pid',
                'pap_file'=>'/etc/ppp/chap-secrets','ppp_options'=>'/etc/ppp/options',
                'log_file'=>'/var/log/messages','binary_file'=>'/usr/sbin/pptpd' );

sub new {
    my $self = shift;

    return $self->SUPER::new( %CONF, @_ );
}

=item get_config

    get configuration server

=cut

sub get_config {
    my $self = shift;

    my $lnum = 1;
    my @lc = ();
    my %conf = ();

    $self = $self->new() if( ! ref($self) );

    my $conf_file = $self->{"conf_file"} || $CONF{"conf_file"};
    open(F,$conf_file);
    while(<F>){
        chomp;
        if( $_ && /^\s*(#?)\s*(\S+)?\s*(\S*)\s*$/ ){
            push(@lc,{ 'name' => $2,
                        'value' => $3,
                        'enabled' => (!$1),
                        'line' => $lnum,
                        'index' => scalar(@lc) }) if( !$1 );
        }
        $lnum++;
    }
    close(F);

    return wantarray() ? @lc : \@lc;
}

=item set_config

    change configuration server

    ARGS: %p - parameters

=cut

sub set_config {
    my $self = shift;
    my (%p) = @_;

    $self = $self->new() if( ! ref($self) );

    my $conf_file = $self->{"conf_file"} || $CONF{"conf_file"};
    my $cfref = read_file_lines($conf_file);
    foreach my $opt (keys %p) {
        my $val = $p{"$opt"};

        my $grep_count = ( $val ) ?
                            ( s/^(\s*$opt\s+)([^#]*)(.*)$/$1\t$val$3/ )
                            :( s/^(\s*$opt\s+)([^#]*)(.*)$/# $1\t$val$3/ );
        if( !$grep_count ){
            if( $val ){
                push @$cfref, "$opt\t$val";
            }
        }
    }
    flush_file_lines($conf_file);

}

=item list_connections

    Returns a list of active PPTP connections by checking the process list.
    Each element of the list is an array containing the PPP PID, PPTP PID,
    client IP, interface, local IP and remote IP, start time and username

=cut

sub list_connections {
    my $self = shift;

    $self = $self->new() if( ! ref($self) );

    my $logfile = $self->{"log_file"} || $CONF{"log_file"};

    # Look in the log file for connection messages
    my (%pppuser, %localip, %remoteip);

    open(L,$logfile);
    while(<L>){
        if (/pppd\[(\d+)\].*authentication\s+succeeded\s+for\s+(\S+)/i) {
            $pppuser{$1} = $2;
        } elsif (/pppd\[(\d+)\].*local\s+IP\s+address\s+(\S+)/) {
            $localip{$1} = $2;
        } elsif (/pppd\[(\d+)\].*remote\s+IP\s+address\s+(\S+)/) {
            $remoteip{$1} = $2;
        }
    }
    close(L);

    # Check for running pptpd and pppd processes
    my @procs = list_processes();
    my @ifaces = ETFW::Network::active_interfaces();

    my @rv = ();
    for my $p (@procs){
        if ($p->{'args'} =~ /pptpd\s*\[([0-9\.]+)/) {
            # Found a PPTP connection process .. get the child PPP proc
            my $rip = $1;
            my ($ppp) = grep { $_->{'ppid'} == $p->{'pid'} } @procs;
            my $user = $ppp ? $pppuser{$ppp->{'pid'}} : "";
            my $lip; 
            if( $ppp && ( $lip = $localip{$ppp->{'pid'}} ) ){
                # We got the local and remote IPs from the log file
                my $rip2 = $remoteip{$ppp->{'pid'}};
                my ($iface) = grep { $_->{'address'} eq $lip &&
                                        $_->{'ptp'} eq $rip } @ifaces; 
                push(@rv, { pppid=>$ppp->{'pid'}, pid=>$p->{'pid'},
                            remoteip=>$rip, 'if'=>($iface ? $iface->{'fullname'} : ""),
                            localip=>$lip, remoteip2=>$rip2,
                            stime=>$ppp->{'_stime'}, user=>$user } );
            } elsif( $ppp && ( $ppp->{'args'} =~ /([0-9\.]+):([0-9\.]+)/ ) ){
                # Find the matching interface
                my ($iface) = grep { $_->{'address'} eq $1 &&
                                        $_->{'ptp'} eq $2 } @ifaces;
                if( $iface ){
                    push(@rv, { pppid=>$ppp->{'pid'}, pid=>$p->{'pid'},
                                remoteip=>$rip, 'if'=>$iface->{'fullname'},
                                localip=>$1, remoteip2=>($iface->{'ptp'} || $2),
                                stime=>$ppp->{'_stime'}, user=>$user } );
                } else {
                    push(@rv, { pppid=>$ppp->{'pid'}, pid=>$p->{'pid'},
                                remoteip=>$rip, 'if'=>"", localip=>$1, remoteip2=>$2,
                                stime=>$ppp->{'_stime'}, user=>$user } );
                }
            } elsif( $ppp ){
                # PPP process doesn't include IPs
                push(@rv, { pppid=>$ppp->{'pid'}, pid=>$p->{'pid'},
                            remoteip=>$rip, 'if'=>"", localip=>"", remoteip2=>"",
                            stime=>$ppp->{'_stime'}, user=>$user } );
            }
        }
    }

    return wantarray() ? @rv : \@rv;
}

=item start

    start server

=cut

sub start {
    my $self = shift;

    $self = $self->new() if( ! ref($self) );

    if( my $service_cmd = $self->{"service_cmd"} ){
        cmd_exec($service_cmd);
    } elsif( my $pptpd = $self->{"binary_file"} || $CONF{"binary_file"} ){
        my $pidfile = $self->{"pid_file"} || $CONF{"pid_file"};
        cmd_exec($pptpd,"--pidfile",$pidfile);
    }
}

=item stop

    stop server

=cut

sub stop {
    my $self = shift;

    $self = $self->new() if( ! ref($self) );

    if( my $service_cmd = $self->{"service_cmd"} ){
        cmd_exec("killall $service_cmd");
    } elsif( my $pptpd = $self->{"binary_file"} || $CONF{"binary_file"} ){
        my $pidfile = $self->{"pid_file"} || $CONF{"pid_file"};
        if( -f "$pidfile" ){
            open(F,"$pidfile");
            my ($pid) = <F>; chomp($pid);
            close(F);
            cmd_exec("/bin/kill",$pid);
        }
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

