#!/usr/bin/perl

=pod

=head1 NAME

ETFW::OpenVPN

=head1 SYNOPSIS

...

=head1 DESCRIPTION

...

=head1 METHODS

=over 4

=cut

package ETFW::OpenVPN;

use strict;

use FileFuncs;
use Utils;

use Data::Dumper;

my $Conf;
my %CONF = ( 'service_cmd'=>"/etc/init.d/openvpn", 'conf_dir'=>"/etc/openvpn",
                'conf_file'=>'/etc/openvpn/server.conf',
                'openvpn_bin'=>'/usr/sbin/openvpn',
                'clientscerts_dir'=>'/etc/openvpn/certs/client',
                'serverscerts_dir'=>'/etc/openvpn/certs',
                'clientsconfig_dir'=>'/etc/openvpn/ccd',
                'openssl_bin'=>'/usr/bin/openssl',
                'cacert_file'=>'/etc/openvpn/certs/ca.crt',
                'servercert_file'=>'/etc/openvpn/certs/server.crt',
                'serverkey_file'=>'/etc/openvpn/certs/server.key' );

# get_config: internal get config func
sub get_config {

    my $lref = read_file_lines($CONF{"conf_file"},1);

    my @conf = ();
    my $line = 0;
    for (@$lref){
        my $str = $_;   # bkp it
        my %L = ();
        if( $str =~ s/#\s*(.*)// ){
            $L{"comment"} = $1;
        }
        if( $str ){
            $L{"commented"} = ( $str =~ m/(;)(\S+)/ ) ? 1 : 0;
            if( $str =~ m/(;)?(\S+)\s+(.+)/ ){
                $L{"name"} = $2;
                $L{"value"} = $3;
            }
            $L{"text"} = $str;
            $L{"line"} = $line;
            $L{"eline"} = $line;
            $L{"index"} = scalar(@conf);
            push(@conf,\%L);
        }
        $line++;
    }

    return wantarray() ? @conf : \@conf;
}

=item get_config_params

=cut

sub get_config_params {
    my $self = shift;

    if( !$Conf ){
        $Conf = get_config();
    }

    my %HC = ();

    for my $L (@$Conf){
        next if( $L->{'commented'} );

        if( my $name = $L->{'name'} ){
            my $v = $L->{'value'};
            if( $name eq 'push' ){
                my ($pn,$pv) = ( $v =~ m/"?(\S+)\s*(.*)"?/ );

                if( $HC{"push"}{"$pn"} ){
                    push(@{$HC{"push"}{"$pn"}}, $pv );
                } else {
                    $HC{"push"}{"$pn"} = [ $pv ];
                }
            } else {
                if( $HC{"$name"} ){
                    if( ref($HC{"$name"}) ){
                        push(@{$HC{"$name"}}, $v );
                    } else {
                        $HC{"$name"} = [ $HC{"$name"}, $v ];
                    }
                } else {
                    $HC{"$name"} = $v;
                }
            }
        } elsif( my $text = $L->{'text'} ){
            $HC{"$text"} = 1;
        }
    }

    return wantarray() ? %HC : \%HC;
}

sub get_config_param {
    my ($p) = @_;
    if( !$Conf ){
        $Conf = get_config();
    }
    return grep { !$_->{"commented"} && ( $_->{"name"} eq $p || $_->{"text"} eq $p ) } @$Conf;
}

=item get_server_config

=cut

sub get_server_config {
    my $self = shift;

    my %SC = $self->get_config_params();

    if( my $cacert_file = $SC{'ca'} ){
        $cacert_file = "$CONF{'conf_dir'}/$cacert_file" if( $cacert_file !~ m/^\// );
        my (%CAInfo) = get_cert_info($cacert_file);
        $SC{"CACERT"} = \%CAInfo;
    }
    if( my $servercert_file = $SC{'cert'} ){
        $servercert_file = "$CONF{'conf_dir'}/$servercert_file" if( $servercert_file !~ m/^\// );
        my (%SInfo) = get_cert_info($servercert_file);
        $SC{"SERVERCERT"} = \%SInfo;
    }

    return wantarray() ? %SC : \%SC;
}

=item set_config_params

=cut

sub set_config_params {
    my $self = shift;
    my (%p) = @_;

    if( !$Conf ){
        $Conf = get_config();
    }

    for my $k ( keys %p ){
        my $v = $p{"$k"};

        if( $k eq "push" ){
            if( ref($v) eq 'HASH' ){
                for my $k ( keys %$v){
                    my $pv = $v->{"$k"};
                    if( ref($pv) eq 'ARRAY' ){
                        my $i = 0;
                        for my $epv (@$pv){
                            $Conf = set_config_push_param( $k, $epv, $i );
                            $i++;
                        }
                    } else {
                        $Conf = set_config_push_param( $k, $pv, 0 );
                    }
                }
            } else {
                $Conf = set_config_push_param( $k, $v );
            }
        } else {
            # ignoring not value types
            if( !ref($v) ){
                # testing empty value
                my $ev = ( $v =~ m/^[01]$/ ) ? 1 : 0;
                $Conf = set_config_param( $k, $v, $ev );
            }
        }
    }

    save_config( $Conf );
}

=item set_server_config

=cut

sub set_server_config {
    my $self = shift;
    my (%p) = @_;

    if( my $CACERT = delete $p{'CACERT'} ){
        # TODO
    }
    if( my $SCERT = delete $p{'SERVERCERT'} ){
        my $rel_certfile = upload_certificate($SCERT->{'filename'},$SCERT->{'_content_'},
                                    $CONF{"serverscerts_dir"},undef,$CONF{"servercert_file"});

        # set server certificate file
        $rel_certfile =~ s#^$CONF{conf_dir}/##;
        $p{"cert"} = $rel_certfile;

        # set key certificate
        if( my $rel_keyfile = upload_key($SCERT->{'filename'},$SCERT->{'_content_'},
                                            $CONF{"serverscerts_dir"},undef,$CONF{"serverkey_file"}) ){
            $rel_keyfile =~ s#^$CONF{conf_dir}/##;
            $p{"key"} = $rel_keyfile;
        }
    }

    $self->set_config_params( %p );
}

sub set_config_param {
    my ($p,$v,$ev) = @_;
    if( !$Conf ){
        $Conf = get_config();
    }

    my ($L) = get_config_param( $p );
    if( !$L && ( ( $v ne "" ) || $ev ) ){
        my ($C) = sort { $b->{'line'} <=> $a->{'line'} }
                    grep { $_->{"name"} eq $p } @$Conf;
        $C = {} if( !$C );
        $L = { %$C, commented=>0 };
        $L->{"line"}++ if( defined $L->{"line"} );
        $L->{"index"}++ if( defined $L->{"index"} );
        my $i = $L->{"index"};
        defined $i ? splice(@$Conf,$i,0,$L) : push(@$Conf,$L);
    }
    $L->{"name"} = $p;
    $L->{"value"} = $v;
    $L->{"text"} = $v ne "" ? $L->{"name"} . " " . $L->{"value"} : undef;
    $L->{"text"} = $L->{"name"} if( $ev );

    return $Conf;
}
sub set_config_push_param {
    my ($p,$v,$i) = @_;

    if( !$Conf ){
        $Conf = get_config();
    }

    $i = defined $i ? $i : 0;

    my @L = grep { !$_->{"commented"} && $_->{"name"} eq "push" && $_->{"value"} =~ m/^\s*"?\s*$p\s+/ } @$Conf;
    my $L = $L[$i];
    if( !$L && ( $v ne "" ) ){
        my ($C) = sort { $b->{'line'} <=> $a->{'line'} }
                    grep { $_->{"name"} eq "push" && $_->{"value"} =~ m/^\s*"?\s*$p\s+/ } @$Conf;
        $C = {} if( !$C );
        $L = { %$C, commented=>0, name=>"push" };
        $L->{"line"}++ if( defined $L->{"line"} );
        $L->{"index"}++ if( defined $L->{"index"} );
        my $ix = $L->{"index"};
        defined $ix ? splice(@$Conf,$ix,0,$L) : push(@$Conf,$L);
    }
    $L->{"name"} = "push";
    $L->{"value"} = $v ? '"' . $p . " " . $v . '"' : "";
    $L->{"text"} = $v ne "" ? $L->{"name"} . " " . $L->{"value"} : undef;

    return $Conf;
}

# save_config: save configuration
sub save_config {
    $Conf = $_[0] if( @_ );

    my $lref = read_file_lines($CONF{"conf_file"});
    for my $L ( reverse @$Conf ){
        my $text = $L->{"text"};
        if( $L->{"comment"} ){
            $text .= " " if( $text =~ m/\S+$/ );
            $text .= "# " . $L->{"comment"};
        }
        if( ! defined($L->{"line"}) ){
            if( defined $L->{"text"} ){
                push(@$lref,$text);
            }
        } else {
            my @l = defined $L->{"text"} ? ($text) : ();
            splice(@$lref,$L->{"line"},$L->{"eline"} - $L->{"line"} + 1,@l);
        }
    }
    flush_file_lines($CONF{"conf_file"});

    return $Conf;
}

sub get_cert_email {
    my ($c_file) = @_;

    my ($e,$email) = cmd_exec("$CONF{openssl_bin} x509 -email -noout -in $c_file");
    if( $e == 0 ){
        chomp($email);
        return ($email);
    }
    return;
}   
sub get_cert_trusted {
    my ($c_file) = @_;

    my $ca_cert = $CONF{"cacert_file"};
    my ($e,$out) = cmd_exec("$CONF{openssl_bin} verify -CAfile $ca_cert $c_file");

    return ( $out =~  m/OK/ ) ? 1 : 0;

}
sub expired_dates {
    my ($edate) = @_;

    use Date::Parse;

    my $etime = str2time($edate);

    # local time stamp
    my $ltime = time();

    return ( $ltime > $etime ) ? 1 : 0;
}
sub valid_dates {
    my ($sdate,$edate) = @_;

    use Date::Parse;

    my $stime = str2time($sdate);
    my $etime = str2time($edate);

    # local time stamp
    my $ltime = time();

    return ( ( $ltime >= $stime ) && 
                ( $ltime <= $etime ) ) ? 1 : 0;
}
sub get_cert_info {
    my ($c_file) = @_;

    my %info = ();
    my ($e,$out) = cmd_exec("$CONF{openssl_bin} x509 -enddate -startdate -issuer -subject -noout -in $c_file");
    if( $e == 0 ){
        ($info{"startdate"}) = ( $out =~ m/notBefore=([^\n\r]+)/s );
        ($info{"enddate"}) = ( $out =~ m/notAfter=([^\n\r]+)/s );
        ($info{"issuer"}) = ( $out =~ m/issuer= ([^\n\r]+)/s );
        ($info{"subject"}) = ( $out =~ m/subject= ([^\n\r]+)/s );

        ($info{"cn"}) = ( $info{"subject"} =~ m/\/CN=([^\/]+)/ );

        ($info{"email"}) = get_cert_email($c_file);

        $info{"trusted"} = get_cert_trusted($c_file) ? "yes" : "no";

        $info{"valid"}   = ( get_cert_trusted($c_file) && valid_dates($info{"startdate"},$info{"enddate"}) ) ? "yes" : "no" ;
        $info{"expired"} = ( expired_dates($info{"enddate"}) ) ? "yes" : "no" ;
    }

    return (%info);
}

sub list_clients_certs {
    my @l = ();
    my $ccertsdir = $CONF{"clientscerts_dir"};
    opendir(D, $ccertsdir);
    my @lf = readdir(D);
    for my $f (@lf){
        my $cert_f = "$ccertsdir/$f";
        if( ( $f !~ m/^\./ ) && ( $f =~ /\.crt$/ ) && -f "$cert_f" && -s "$cert_f"){
            my %info = get_cert_info($cert_f);
            $info{"type"} = "client";
            $info{"file"} = $cert_f;
            push @l, \%info;
        }
    }
    closedir(D);

    return @l;
}

# get client config
sub get_client_config {
    my $list = [];
    if( -d $CONF{"clientsconfig_dir"} ){
        my $cconfigdir = $CONF{"clientsconfig_dir"};
        opendir(D,$cconfigdir);
        my @files = grep { !/^\./ } readdir(D);
        for my $cn (@files){
            my $fp = "$cconfigdir/$cn";
            my %C = ( cn=>$cn );
            open(F,$fp);
            while(<F>){
                # clean comments
                s/[#;]\s*(.*)//;

                if( /ifconfig-push\s+(\d+\.\d+\.\d+\.\d+)\s+(\d+\.\d+\.\d+\.\d+)/ ){
                    $C{"localip"} = $1;
                    $C{"remoteip"} = $2;

                } elsif( /push\s+"?route\s+(\d+\.\d+\.\d+\.\d+)\s+(\d+\.\d+\.\d+\.\d+)"?/ ){
                    push(@{$C{"routes"}}, { netaddr=>$1,netmask=>$2 });
                } elsif( /push\s+"?dhcp-option\s+DNS\s+(\d+\.\d+\.\d+\.\d+)"?/ ){
                    push(@{$C{"dhcp_option_dns"}}, { addr=>$1 });
                } elsif( /push\s+"?dhcp-option\s+WINS\s+(\d+\.\d+\.\d+\.\d+)"?/ ){
                    push(@{$C{"dhcp_option_wins"}}, { addr=>$1 });
                } elsif( /push\s+"([^"]+)"/ ){
                    push(@{$C{"other_config"}}, { line=>$1 });
                }
            }
            push( @$list, \%C );
            close(F);
        }
        closedir(D);
    }
    return wantarray() ? @$list : $list;
}

=item list_clients

=cut

sub list_clients {
    my $self = shift;

    my @lc = ( list_clients_certs(), get_client_config() );
    my %hc = ();
    for my $C (@lc){
        my $cn = $C->{"cn"};
        my $Info = $hc{"$cn"} || {};
        my %aux = (%$Info,%$C);
        $hc{"$cn"} = \%aux;
    }
    return wantarray() ? %hc : \%hc;
}

=item get_client

    ARGS: cn - canonical name

=cut

sub get_client {
    my $self = shift;
    my (%p) = @_;
    my $Info;
    if( my $cn = $p{'cn'} ){
        if( $cn ){
            my %hc = list_clients();
            $Info = $hc{"$cn"};
        }
    }
    return $Info;
}

sub del_client_config {
    my %ci = @_;

    my $cconfigdir = $CONF{"clientsconfig_dir"};
    my $cn = $ci{"cn"};

    my $cpath = "$cconfigdir/$cn";
    unlink($cpath);
}

=item del_clients

=cut

sub del_clients {
    my $self = shift;
    my (%p) = @_;

    if( my $ld = $p{'del'} ){
        my %hc = list_clients();

        for my $C (@$ld){
            my $cn = ref($C) ? $C->{'cn'} : $C;
            if( my $Info = $hc{"$cn"} ){
                if( $Info->{'file'} ){
                    unlink($Info->{'file'});
                }
                del_client_config( cn=>$Info->{"cn"} );
            }
        }
    }
}

sub save_client_config {
    my %ci = @_;

    my $cconfigdir = $CONF{"clientsconfig_dir"};
    if( !-d "$cconfigdir" ){
        use File::Path qw( mkpath );

        mkpath($cconfigdir);
    }

    my $cn = $ci{"cn"};

    my $cpath = "$cconfigdir/$cn";

    open(F,">$cpath");
    # ifconfig-push
    print F "ifconfig-push ",$ci{"localip"}," ",$ci{"remoteip"},"\n";
    # add other config stuff
    if( my $rl = $ci{"routes"} ){
        for my $R (@$rl){
            print F 'push "route ' . $R->{"netaddr"} . " " . $R->{"netmask"} . '"',"\n";
        }
    }
    if( my $dw = $ci{"dhcp_option_wins"} ){
        for my $D (@$dw){
            print F 'push "dhcp-option WINS ' . $D->{"addr"} . '"',"\n";
        }
    }
    if( my $dd = $ci{"dhcp_option_dns"} ){
        for my $D (@$dd){
            print F 'push "dhcp-option DNS ' . $D->{"addr"} . '"',"\n";
        }
    }
    close(F);

    # Netmask for 2 hosts only
    my $nm = "255.255.255.252";

    # Network address
    my $netaddr = compute_network($ci{"localip"},$nm);

    my @ld = split(/\//,$cconfigdir);
    my $dir = pop(@ld);

    set_config_param("client-config-dir",$dir);
    add_config_route($netaddr,$nm);

    save_config();
    # TODO Check CN and IPs
}

=item set_client_config

    ARGS: cn - canonical name
          localip - local IP address
          remoteip - remote IP address
          routes - routes list
          dhcp_option_wins - DHCP WINS options
          dhcp_option_dns - DHCP DNS options
=cut

sub set_client_config {
    my $self = shift;
    my (%p) = @_;
    if( my $cn = $p{'cn'} ){
        my %Info = ( 'cn'=>$cn );

        if( my $CERT = delete $p{'CLIENTCERT'} ){
            my $certfile = upload_certificate($CERT->{'filename'},$CERT->{'_content_'},$CONF{"clientscerts_dir"});
            %Info = get_cert_info($certfile);
            if( $Info{'cn'} ne $cn ){
                # TODO return error
                return;
            }
        } else {
            if( my $C = get_client( $Info{"cn"} ) ){
                %Info = %$C;
            }
        }

        # save it
        save_client_config(%Info, %p);
    }
    # TODO return error
    return;
}

sub add_config_route {
    my ($netaddr,$nm) = @_;
    if( ! grep { $_->{"value"} eq "$netaddr $nm" } get_config_param( "route" ) ){
        my ($C) = sort { $b->{'line'} <=> $a->{'line'} }
                    grep { $_->{"name"} eq "route" } @$Conf;
        $C = {} if( !$C );
        my $L = { %$C, commented=>0 };
        $L->{"line"}++ if( defined $L->{"line"} );
        $L->{"index"}++ if( defined $L->{"index"} );
        $L->{"value"} = "$netaddr $nm";
        $L->{"text"} = "route $netaddr $nm";
        my $i = $L->{"index"};
        defined $i ? splice(@$Conf,$i,0,$L) : push(@$Conf,$L);
    }
}

sub del_config_route {
    my ($netaddr,$nm) = @_;

    my ($L) = grep { $_->{"value"} eq "$netaddr $nm" } get_config_param( "route" );
    # mark to delection
    $L->{"text"} = undef;
}

sub upload_certificate {
    my ($orifilename,$content,$dircerts,$Info,$pathfile) = @_;

    my $tmp_file = open_tempfile(*MOD,">$orifilename");
    binmode(MOD);
    print MOD $content;
    close_tempfile(*MOD);

    if( $Info && $Info->{"file"} ){
        $pathfile = $Info->{"file"};
    } elsif( !$pathfile ){
        # using email for filename
        my ($email) = get_cert_email($tmp_file);
        my $filename = "${email}.crt";
        $pathfile = "$dircerts/$filename";
    }

    my $tomove = 1;

    if( $orifilename =~ m/\.pem/ ){
        my $e = cmd_exec("$CONF{openssl_bin} x509 -text -in $tmp_file -out $pathfile");
        # something goes wrong
        $tomove = ( $e == 0 ) ? 1 : 0;
    }

    if( $tomove ){
        move($tmp_file,$pathfile);
    }

    return $pathfile;
}

sub upload_key {
    my ($orifilename,$content,$dircerts,$Info,$pathfile) = @_;
    if( $content =~ m/(-----BEGIN .* PRIVATE KEY-----(.|\n|\r)+-----END .* PRIVATE KEY-----)/ ){
        my $key_content = $1;

        my $tmp_file = open_tempfile(*MOD,">$orifilename");
        binmode(MOD);
        print MOD $key_content,"\n";
        close_tempfile(*MOD);

        if( $Info && $Info->{"file"} ){
            $pathfile = $Info->{"file"};
        } elsif( !$pathfile ){
            # using email for filename
            my ($email) = get_cert_email($tmp_file);
            my $filename = "${email}.key";
            $pathfile = "$dircerts/$filename";
        }

        move($tmp_file,$pathfile);

        return $pathfile;
    }
    return;
}

# compute_network(ip, netmask)
# Returns a computed network address (ip & netmask)
sub compute_network {
    my $ipnum = &ip_to_integer($_[0]);
    my $nmnum = &ip_to_integer($_[1]);
    return &integer_to_ip($ipnum & $nmnum);
}

# ip_to_integer(ip)
# Given an IP address, returns a 32-bit number
sub ip_to_integer {
    my @ip = split(/\./, $_[0]);
    return ($ip[0]<<24) + ($ip[1]<<16) + ($ip[2]<<8) + ($ip[3]<<0);
}

# integer_to_ip(integer)
# Given a 32-bit number, converts it to an IP
sub integer_to_ip {
    return sprintf "%d.%d.%d.%d",
        ($_[0]>>24)&0xff,
        ($_[0]>>16)&0xff,
        ($_[0]>>8)&0xff,
        ($_[0]>>0)&0xff;
}


=item start

    start service

=cut

sub start {
    my $self = shift;
    if( -x $CONF{"service_cmd"} ){
        cmd_exec($CONF{"service_cmd"},"start");
    }
}

=item stop

    stop service

=cut

sub stop {
    my $self = shift;
    if( -x $CONF{"service_cmd"} ){
        cmd_exec($CONF{"service_cmd"},"stop");
    }
}

=item restart

    restart service

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

