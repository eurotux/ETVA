#!/usr/bin/perl
# Copywrite Eurotux 2009
# 
# CMAR 2009/04/14 (cmar@eurotux.com)

# Utils
#   util functions

package ETVA::Utils;

use strict;

use Socket;
use POSIX;
use Digest::MD5 qw(md5_hex md5_base64);
use IO::Handle;
use HTML::Entities;
use SOAP::Lite;
use File::Path qw( mkpath );

BEGIN {
    # this is the worst damned warning ever, so SHUT UP ALREADY!
    $SIG{__WARN__} = sub { warn @_ unless $_[0] =~ /Use of uninitialized value/ };

    require Exporter;
    use vars qw($VERSION @ISA @EXPORT @EXPORT_OK %EXPORT_TAGS $CRLF);
    $VERSION = '0.0.1';
    @ISA = qw( Exporter );
    @EXPORT = qw( trim tokey enckey
                    plog now nowStr retErr retOk isError isOk
                    cmd_exec cmd_exec_errh backquote_exec
                    loadconfigfile saveconfigfile random_uuid
                    get_conf set_conf random_mac tmpfile tmpdir
                    fieldsAsString
                    decode_content encode_content
                    make_soap make_soap_args
                    str2size prettysize
                    list_processes find_procname
                    debug_level debug_inc debug_dec set_debug_level
                     );
}

my $debug = 0;

my %CONF;

sub trim {
    my ($str) = @_;
    $str =~ s/^\s+//;
    $str =~ s/\s+$//;
    return $str;
}

sub tokey {
    my ($str) = @_;
    my $key = trim($str);
    $key =~ s/\s/_/g;
    $key =~ s/\W//g;
    return $key;
}

sub enckey {
    my ($str) = @_;
    my $key = trim($str);
    $key =~ s/\s/_/g;
    $key =~ s/[^a-zA-Z0-9_.-]/_/g;
    return $key;
}

sub plog {
	print STDERR @_,$/;
}

sub now {
    my ($secs) = @_;
    $secs = 0 if( !$secs );
    
    return time()+$secs;
}
sub nowStr {
    my ($secs,$fmt) = @_;
    $fmt ||= '%Y-%m-%d %H:%M:%S';
    return  strftime($fmt,localtime(now($secs)));    
}

sub retErr {
    my ($type,$msg,$code) = @_;
    
    # TODO: 
    plog "ERROR ($type): $msg" if( &debug_level > 0 );
    my %E = ( '_error_'=>1, '_errorcode_'=>$code, '_errorstring_'=>$type, '_errordetail_'=>$msg );
    return wantarray() ? %E : \%E;
}
sub retOk {
    my ($type,$msg,$code,$obj) = @_;

    plog "RETOK ($type)[$code]: $msg" if( &debug_level > 1 );
    my %O = ( '_ok_'=>1, '_oktype_'=>$type, '_okmsg_'=>$msg );
    $O{'_okcode_'} = $code if( $code );
    $O{'_obj_'} = $obj if( $obj );
    return wantarray() ? %O : \%O;
}
sub isError {
    my ($R) = my %E = @_;
    if( ( ref($R) ) eq 'HASH' && $R->{'_error_'} ){
        return 1;
    }
    if( %E && $E{'_error_'} ){
        return 1;
    }
    return 0;
}
sub isOk {
    my ($R) = my %E = @_;
    if( ( ref($R) ) eq 'HASH' && $R->{'_ok_'} ){
        return 1;
    }
    if( %E && $E{'_ok_'} ){
        return 1;
    }

    return 0;
}

sub cmd_exec {
    my @cmds = @_;
    
    IO::Handle->autoflush(1);

    my $tmpdir = $CONF{'tmpdir'} || "/tmp";
    my $fptmpfile = tmpfile("${tmpdir}/cmd_exec") . ".log";
    push(@cmds,">$fptmpfile","2>&1");
    my $cmd_str = join(" ",@cmds);

    plog("system exec: $cmd_str ") if( &debug_level > 2 );

    my $e = system($cmd_str);

    my $msg = "";
    open(M,"$fptmpfile");
    while(<M>){ $msg .= $_; }
    close(M);
    unlink($fptmpfile);

    plog("  error: $e ") if( &debug_level > 2 );
    plog("  message: $msg ") if( &debug_level > 2);

    return wantarray() ? ($e,$msg) : $e;
}

sub cmd_exec_errh {
    my @cmds = @_;
    
    IO::Handle->autoflush(1);

    my $tmpdir = $CONF{'tmpdir'} || "/tmp";
    my $fptmpfile = tmpfile("${tmpdir}/cmd_exec") . ".log";
    push(@cmds,"2>$fptmpfile");
    my $cmd_str = join(" ",@cmds);

    plog("system exec: $cmd_str ") if( &debug_level > 2 );

    my $e = system($cmd_str);

    my $msg = "";
    open(M,"$fptmpfile");
    while(<M>){ $msg .= $_; }
    close(M);
    unlink($fptmpfile);

    plog("  error: $e ") if( &debug_level > 2 );
    plog("  message: $msg ") if( &debug_level > 2 );

    return wantarray() ? ($e,$msg) : $e;
}

sub backquote_exec {
    my @cmds = @_;
    
    IO::Handle->autoflush(1);

    my $cmd_str = join(" ",@cmds);

    plog("backquote exec: $cmd_str ") if( &debug_level > 2 );

    my $msg = `$cmd_str`;
    my $e = $?;

    plog("  error: $e ") if( &debug_level > 2 );
    plog("  message: $msg ") if( &debug_level > 2);

    return wantarray() ? ($e,$msg) : $e;
}

# load config file
sub loadconfigfile {
    sub confrefsect {
        my $F = shift;
        my $sect = shift;
        my @as = @_;

        my $A;
        $F->{"$sect"} = {} if( !$F->{"$sect"} );
        if( scalar(@as) ){
            $A = confrefsect($F->{"$sect"},@as);
        } else {
            $A = $F->{"$sect"};
        }
        return $A;
    }

    my ($FILE,$CONF,$pq) = @_;
    $pq ||= 0;  # parse quotes

    open(C,$FILE);
    my $C = $CONF;
    while(<C>){
        chomp;
        my $line = $_;
        next if( !$line );

        next if( $line =~ m/^(\s+)?(#|--|;)/ );

        # no spaces line
        my $sline = $line;
        $sline =~ s/\s//g;
        if( my ($mline) = ($sline =~ m/^\s*\[((\w+:?)+)\]/) ){
            my ($sect) = my @asect = split(/:/,$mline);
            if( ( scalar(@asect) == 1 )  && ( lc($sect) eq "geral" ) ){
                $C = $CONF;
            } else {
                $C = confrefsect($CONF,@asect); 
            }
        } else {
            my ($f,$v) = split(/=/,$line,2);
            my $cf = trim($f);
            my $cv = trim($v);
            if( $pq ){  # remove quotes
                $cv =~ s/^\s*["']//;
                $cv =~ s/["']\s*$//;
            }
            $C->{"$cf"} = $cv;
        }
    }
    close(C);
    return wantarray() ? %$CONF : $CONF;
}
# save config file
sub saveconfigfile {
    sub saveconfigfile_rec {
        my ($fh,$C,$s,$pq,$ns) = @_;
        # Only hash implemented
        if( ref($C) eq 'HASH' ){
            for my $k ( sort {(!ref($C->{$b})) <=> (!ref($C->{$a}))} keys %$C ){
                my $v = $C->{"$k"};
                if( ref($v) ){
                    my $sec = $s;
                    $sec .= ":" if( $sec );
                    $sec .= $k;
                    print $fh "[$sec]",$/;
                    saveconfigfile_rec($fh,$v,$sec,$pq);
                } else {
                    if( $pq ){ # save value with quotes
                        $v = '"'.$v.'"' if( $v =~ /\s/ && $v =~ /^["']/);
                    }
                    my $str = $ns ? "$k=$v" : "$k = $v";
                    print $fh $str,$/;
                }
            }
        }
    }
    my ($FILE,$CONF,$append,$nocom,$pq,$ns) = @_;
    my $com = !$nocom;
    $pq ||= 0; # using quotes
    $ns ||= 0; # no spaces

    my $mode = ">"; # mode write
    $mode = ">>" if( $append ); # mode append

    my $FH;
    open($FH,$mode,"$FILE");
    print $FH "# -- This part was generated --",$/ if( $com );
    saveconfigfile_rec($FH,$CONF,"",$pq,$ns);
    print $FH "# -- End --",$/ if( $com );
    close($FH);
}

sub replaceconfigfile {
    my ($FILE,$CONF) = @_;

    # read all lines
    open(FH,"$FILE");
    my @readlines = <FH>;
    close(FH);

    my $cr = $/;    # carriage return
    my %Pf = ();
    my $S = ( grep { /\[geral\]/ } @readlines )? {} : $CONF;
    open(FH,'>',"$FILE");
    for my $l (@readlines){
        if( $l =~ m/\[((\w+:?)+)\]/ ){
            my $lsec = $1;
            my ($sect, @asect) = split(/:/,$lsec);

            if( %$S ){  # have config params to write
                for my $f (keys %$S){
                    if( !$Pf{"$f"} ){
                        my $cv = $S->{"$f"};
                        my $pt = "";
                        $pt = ($cv =~ m/"/)? "'" : '"' if( $cv =~ m/\s/ );
                        print FH "$f = $pt$cv$pt"."$cr";
                        $Pf{"$f"} = 1;
                    }
                }
            }

            %Pf = ();   # clean processed fields
            $S = ( $sect eq 'geral' ) ? $CONF : $CONF->{"$sect"};
            for my $s (@asect){
                last if( !$S );
                $S = $S->{"$s"};
            }
            $S ||= {};
        } elsif( $l =~ m/^\s*(\w+)\s*=\s*(.+)$/){
            my ($f,$v) = ($1,$2);
            if( $S->{"$f"} ){
                my $cv = $S->{"$f"};
                my $pt = "";
                $pt = ($cv =~ m/"/)? "'" : '"' if( $cv =~ m/\s/ );
                $l = "$f = $pt$cv$pt"."$cr";
                $Pf{"$f"} = 1;
            }
        }
        print FH $l;
    }

    if( %$S ){  # have config params to write
        for my $f (keys %$S){
            if( !$Pf{"$f"} ){
                my $cv = $S->{"$f"};
                my $pt = "";
                $pt = ($cv =~ m/"/)? "'" : '"' if( $cv =~ m/\s/ );
                print FH "$f = $pt$cv$pt"."$cr";
                $Pf{"$f"} = 1;
            }
        }
    }

    close(FH);
}

# random_uuid
#   generate random uuid in hexadecimal
sub random_uuid {
    return random_uuid_hex();
}
# random_uuid_base64
#   generate random uuid in base64
sub random_uuid_base64 {
    my $uuid = "";
    
    $uuid = join('-',
                        substr(md5_base64(rand(time())),0,8),
                        substr(md5_base64(rand(time())),0,4),
                        substr(md5_base64(rand(time())),0,4),
                        substr(md5_base64(rand(time())),0,4),
                        substr(md5_base64(rand(time())),0,12),
                        ); 
    return $uuid;
}
# random UUID
#   Version 4 (random)
#   from http://en.wikipedia.org/wiki/UUID
#       xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx
#           with hexadecimal digits x and hexadecimal digits 8, 9, A, or B for y.
sub random_uuid_hex {
    my $uuid = "";
    
    my @y = qw( 8 9 a b );
    my $i = int(rand(scalar(@y)));
    $uuid = join("-",
                        substr(md5_hex(rand(time())),0,8),
                        substr(md5_hex(rand(time())),0,4),
                        "4".substr(md5_hex(rand(time())),0,3),
                        $y[$i].substr(md5_hex(rand(time())),0,3),
                        substr(md5_hex(rand(time())),0,12),
                        ); 
    return $uuid;
}

# random_mac
#  Generate a random MAC address.
#    http://standards.ieee.org/regauth/oui/oui.txt.
#
#    return: MAC address string
sub random_mac {
    my $mac = join(":",
                        sprintf('%02x',0x00),
                        sprintf('%02x',0x16),
                        sprintf('%02x',0x3e),
                        sprintf('%02x',rand(127)),
                        sprintf('%02x',rand(255)),
                        sprintf('%02x',rand(255))
                    );
    return $mac;
}

# get configuration hash
sub get_conf {
    my ($force,$cfg_file) = @_;
    if( $force || !%CONF ){

        $cfg_file = $cfg_file || $ENV{'CFG_FILE'};

        if( -e "$cfg_file" ){
            plog "load config file from '$cfg_file'",$/ if( &debug_level > 1 ); 
            %CONF = loadconfigfile($cfg_file,\%CONF);
            # configuration file
            $CONF{'CFG_FILE'} = $cfg_file;

            my %UC = ();   # conf to update

            # uuid define
            $UC{'uuid'} = $CONF{'uuid'} = random_uuid() if( !$CONF{'uuid'} );

            # cm_uri
            $UC{'cm_uri'} = $CONF{'cm_uri'} = get_cmuri() if( !$CONF{'cm_uri'} || $CONF{'cm_uri'} =~ m/http:\/\/localhost/ );

            # IP
            $UC{'IP'} = $CONF{'IP'} = $CONF{'LocalIP'} = get_ip(%CONF) if( !$CONF{'IP'} && !$CONF{'LocalIP'} );
            $UC{'IP'} = $CONF{'IP'} = $CONF{'LocalIP'} if( !$CONF{'IP'} );
            $CONF{'LocalIP'} = $CONF{'IP'} if( !$CONF{'LocalIP'} );

            # Port
            $UC{'Port'} = $CONF{'Port'} = $CONF{'LocalPort'} = get_port() if( !$CONF{'Port'} && !$CONF{'LocalPort'} );
            $UC{'Port'} = $CONF{'Port'} = $CONF{'LocalPort'} if( !$CONF{'Port'} );
            $CONF{'LocalPort'} = $CONF{'Port'} if( !$CONF{'LocalPort'} );
            if( !$CONF{'Port'} ){
                die "ERROR: no Port defined $/";
            }

            # name
            $UC{'name'} = $CONF{'name'} = get_name(%CONF) if( !$CONF{'name'} );
            if( !isNameValid($CONF{'name'}) ){
                die "ERROR: name is not valid$/";
            }

            if( %UC ){
                # update config
                #saveconfigfile($CONF{'CFG_FILE'},\%UC,1);
                replaceconfigfile($CONF{'CFG_FILE'},\%UC);
            }
        } else {
            die "ERROR: Config file not defined$/";
        }
    }
    return wantarray() ? %CONF : \%CONF;
}

# set configuration hash
sub set_conf {
    my ($cfg_file,%conf) = @_;

    %CONF = (%CONF,%conf);

    $cfg_file = $cfg_file || $ENV{'CFG_FILE'};

    plog "set config file from '$cfg_file'",$/ if( &debug_level > 1 );

    # configuration file
    $CONF{'CFG_FILE'} = $cfg_file;

    # uuid define
    $CONF{'uuid'} = random_uuid() if( !$CONF{'uuid'} );

    # cm_uri
    $CONF{'cm_uri'} = get_cmuri() if( !$CONF{'cm_uri'} || $CONF{'cm_uri'} =~ m/http:\/\/localhost/ );

    # IP
    $CONF{'IP'} = $CONF{'LocalIP'} = get_ip(%CONF) if( !$CONF{'IP'} && !$CONF{'LocalIP'} );
    $CONF{'IP'} = $CONF{'LocalIP'} if( !$CONF{'IP'} );
    $CONF{'LocalIP'} = $CONF{'IP'} if( !$CONF{'LocalIP'} );

    # Port
    $CONF{'Port'} = $CONF{'LocalPort'} = get_port() if( !$CONF{'Port'} && !$CONF{'LocalPort'} );
    $CONF{'Port'} = $CONF{'LocalPort'} if( !$CONF{'Port'} );
    $CONF{'LocalPort'} = $CONF{'Port'} if( !$CONF{'LocalPort'} );
    if( !$CONF{'Port'} ){
        die "ERROR: no Port defined $/";
    }

    # name
    $CONF{'name'} = get_name(%CONF) if( !$CONF{'name'} );
    if( !isNameValid($CONF{'name'}) ){
        die "ERROR: name is not valid$/";
    }

    # upade config
    saveconfigfile($CONF{'CFG_FILE'},\%CONF);
}
sub get_name {
    my %H = @_;
    my $name;

    if( my $ip = $H{'ip'} ){
        $name = scalar gethostbyaddr(inet_aton("$ip"), AF_INET); 
    }
    if( !$name ){
        open(N,"/bin/uname -n |");
        my $sl = <N>;
        chomp $sl;
        # only first field
        ($name) = split(/\./,$sl);
        close(N);
    }
    return $name;
}
sub get_port {
    my $port = 7000;

    while( !( new IO::Socket::INET( Listen => 1, LocalPort => $port ) ) ){
        # Just to prevent
        die "No more ports available." if( $port >= 65535 );
        $port++;
    }
    return $port;
}
sub get_ip {
    my %H = @_;

    # try get from previous call
    my $ip = $H{'IP'} || $H{'LocalIP'} || $CONF{'IP'} || $CONF{'LocalIP'} || "";

    # return ip if set and dont have force to reload flag
    if( !$H{'force'} && $ip ){
        return $ip;
    }

    my $cm_uri = $H{'cm_uri'} || $CONF{'cm_uri'};

    # get ip from route to cm_uri
    if( $cm_uri && ($cm_uri !~ m/localhost/) &&
        ($cm_uri !~ m/127\.0\.0\.1/) &&
        ( $cm_uri =~ m/^http:\/\/([^\/]+)\// ) ){
        my ($cm_ip) = ($1);
        # convert to ip
        $cm_ip = inet_ntoa(inet_aton($cm_ip)) if( $cm_ip !~ m/\d+\.\d+\.\d+\.\d+/ );
        my ($e,$m) = cmd_exec("ip route get $cm_ip");
        my ($if) = ( $m =~ m/^\S+\s+\S+\s+(\S+)/gs );

        if( $if ){
            my %If = get_interface($if);
            if( $ip = $If{"address"} ){
                return $ip;
            }
        }
    }

    my $name = $H{'name'};
    if( !$name ){ $name = get_name(); }

    if( $name && ( $name ne 'localhost' ) ){
        my $iad = inet_aton($name);
        if( $iad ){
            if( $ip = inet_ntoa($iad) ){
                return $ip;
            }
        }
    }

    # get IP from default interface
    my %if = get_defaultinterface();
    if( $ip = $if{"address"} ){
        return $ip;
    }

    # return localhost by default
    return "127.0.0.1";
}
sub get_cmip {

    my $ip = "127.0.0.1";

    my $fc = $CONF{'CFG_FILE'} || $ENV{'CFG_FILE'} || "/etc/sysconfig/etva-vdaemon/noavahi";
    $fc =~ s/^(\.?\.?\/([^\/]+\/)*)?[^\/]+$/$1noavahi/;
    if( !-e "$fc" ){
        # avahi-browse -l -f -t _etva._tcp -r|grep address|awk {'print $3'}|sed -e 's/\[//g' -e 's/\]//g'
        # x.x.x.x
        if( -x "/usr/bin/avahi-browse" ){
            open(PH,q#/usr/bin/avahi-browse -l -f -t _etva._tcp -r|grep address|awk {'print $3'}|sed -e 's/\[//g' -e 's/\]//g' |#);
            while(<PH>){
                chomp;
                if( /(\d+\.\d+\.\d+\.\d+)/ ){
                    $ip = $1;
                    last;
                }
            }
            close(PH);
        }
    }

    return $ip;
}
sub get_cmuri {

    my $cmuri = "http://localhost/soapapi.php";
    my $ip = &get_cmip();
    if( $ip && $ip ne '127.0.0.1' ){
        $cmuri = "http://$ip/soapapi.php";
    }
    return $cmuri;
}
sub get_listroutes {
    my @list = ();
    open(R,"/bin/netstat -rn 2>/dev/null |");
    while(<R>){
        if( /^([0-9\.]+)\s+([0-9\.]+)\s+([0-9\.]+)\s+\S+\s+\S+\s+\S+\s+\S+\s+(\S+)/ ){
            push @list, { 'dest'=>$1,
                            'gateway'=>$2,
                            'netmask'=>$3,
                            'iface'=>$4,
                            'default'=> ($1 eq '0.0.0.0')? 1:0
                        };
        }
    }
    close(R);

    return wantarray() ? @list : \@list;
}
sub get_defaultroute {
    if( my ($DR) = grep { $_->{'default'} || ( $_->{'dest'} eq '0.0.0.0' ) } get_listroutes() ){
        return wantarray() ? %$DR : $DR;
    }
    return;
}
sub get_allinterfaces {
    open(F,"/sbin/ifconfig -a |");
    my $if;
    my %IFaces = ();
    while(<F>){ 
        if( /^(\S+)/ ){
            $if = $1;
        }
        if( $if ){
            if( /^([^:\s]+)/ ){
                $IFaces{"$if"}{"name"} = $1;
            }
            if( /^(\S+)/ ){
                $IFaces{"$if"}{"fullname"} = $1;
            }
            if( /^(\S+):(\d+)/ ){
                $IFaces{"$if"}{"virtual"} = $2;
            }
            if( /HWaddr (\S+)/ ){
                $IFaces{"$if"}{'macaddress'} = $1
            }
            if( /inet addr:(\S+)/ ){
                $IFaces{"$if"}{'address'} = $1;
                $IFaces{"$if"}{'active'} = 1;
            }
            if( /Mask:(\S+)/ ){
                $IFaces{"$if"}{'netmask'} = $1;
            }
            if( /Bcast:(\S+)/ ){
                $IFaces{"$if"}{'broadcast'} = $1;
            }
            if( /MTU:(\d+)/ ){
                $IFaces{"$if"}{'mtu'} = $1;
            }
            if( /P-t-P:(\S+)/ ){
                $IFaces{"$if"}{'ptp'} = $1;
            }

            if( /\sUP\s/ ){
                $IFaces{"$if"}{'up'} = 1;
            }
            if( /\sPROMISC\s/ ){
                $IFaces{"$if"}{'promisc'} = 1;
            }
            $IFaces{"$if"}{'edit'} = ($if !~ /^ppp/)? 1:0;

            $IFaces{"$if"}{'type'} = iface_type($if);

            # TODO inet6
        }
    }
    close(F);
    return wantarray() ? %IFaces : \%IFaces;
}

# iface_type(name)
# Returns a human-readable interface type name
sub iface_type { 
    my ($name) = @_;
    if ($name =~ /^(.*)\.(\d+)$/) {
        return iface_type("$1") . " VLAN";
    }
    return "PPP" if ($name =~ /^ppp/);
    return "SLIP" if ($name =~ /^sl/);
    return "PLIP" if ($name =~ /^plip/);
    return "Ethernet" if ($name =~ /^eth/);
    return "Wireless Ethernet" if ($name =~ /^(wlan|ath)/);
    return "Arcnet" if ($name =~ /^arc/);
    return "Token Ring" if ($name =~ /^tr/);
    return "Pocket/ATP" if ($name =~ /^atp/);
    return "Loopback" if ($name =~ /^lo/);
    return "ISDN rawIP" if ($name =~ /^isdn/);
    return "ISDN syncPPP" if ($name =~ /^ippp/);
    return "CIPE" if ($name =~ /^cip/);
    return "VmWare" if ($name =~ /^vmnet/);
    return "Wireless" if ($name =~ /^wlan/);
    return "Bonded" if ($name =~ /^bond/);
    return "Unknown";
}

sub get_interface {
    my ($if) = @_;
    my %IFs = get_allinterfaces();
    if( my $IF = $IFs{"$if"} ){
        return wantarray() ? %$IF : $IF;
    }
    return;
}
sub get_defaultinterface {
    if( my $DR = get_defaultroute() ){
        if( my $IF = get_interface($DR->{"iface"}) ){
            return wantarray() ? %$IF : $IF;
        }
    }
    return;
}
sub isNameValid {
    my ($name) = @_;
    my $v = 1;
    if( !$name ){ $v = 0; }
    if( $name eq 'localhost' ){ $v = 0; }
    if( $name eq 'localhost.localdomain' ){ $v = 0; }
    return $v;
}
sub tmpfile {
    my ($pr) = @_;
    my $randtok = substr(md5_hex( rand(time()) ),0,5);
    return $pr ? "$pr.$randtok" : $randtok ;
}
sub tmpdir {
    my ($pr) = @_;
    my $randtok = substr(md5_hex( rand(time()) ),0,5);
    my $dir = $pr ? "$pr.$randtok" : $randtok ;
    mkdir $dir;
    return $dir;
}

# fieldsAsString
#   convert fields to string
#   f1=v1,f2=v2,...,fn=vn
#
#   args: hash, list of fields
#   return: string
sub fieldsAsString {
    my ($N,$list) = @_;
    my @keys = $list ? @$list : keys %$N;
    my $str = "";
    for my $f (@keys){
        $str .= "," if( $str );
        $str .= "$f=$N->{$f}" if( $N->{"$f"} );
    }
    return $str;
}

sub encode_content {
    my ($cnt,$nashash,$noentities) = @_;
    $nashash ||= 0;     # flag to disable encode as hash
    $noentities ||= 0;  # dont convert entities

    my $res = {};
    if( ref($cnt) eq 'ARRAY' ){
        # SOAP doenst work well with array
        #   convert it intro hash
        my $cnt_as_hash = $nashash ? [] : {};
        for(my $i=0; $i<scalar(@$cnt); $i++){
            $nashash ? 
                $cnt_as_hash->[$i] = encode_content($cnt->[$i],$nashash,$noentities)
                : $cnt_as_hash->{"arrayi-$i"} = encode_content($cnt->[$i],$nashash,$noentities);

        }
        $res = $cnt_as_hash;
    } elsif( ref($cnt) eq 'HASH' ){
        for my $k ( keys %$cnt ){
           my $ek = enckey($k);
           $res->{"$ek"} = encode_content($cnt->{"$k"},$nashash,$noentities);
        }
    } else {
        $cnt = '' if( not defined $cnt );
        $res = $noentities ? $cnt : encode_entities($cnt);
    } 
    return $res;
}

sub decode_content {
    my ($cnt) = @_;

    if( ref($cnt) eq "ARRAY" ){
        for(my $i=0; $i<scalar(@$cnt); $i++){
            $cnt->[$i] = decode_content($cnt->[$i]);
        }
    } elsif( ref($cnt) ){   # Hash or object
        my $is_array = 0;
        for my $key (keys %$cnt){
            if( $key =~ m/soap_/ ){
                delete $cnt->{$key};
            } else {
                if( $key =~ m/arrayi-/ ){
                    $is_array = 1;
                }
                $cnt->{"$key"} = decode_content($cnt->{"$key"});
            }
        }
        if( $is_array ){
            sub tonum {
                my ($x) = @_;
                my ($d) = ($x =~ m/arrayi-(\d+)/);
                return $d;
            }
            my @as_array = ();
            for my $k (sort {tonum($a) <=> tonum($b)} keys %$cnt){
                push(@as_array,$cnt->{"$k"});
            }
            $cnt = \@as_array;
        }
    } else {
        decode_entities($cnt);
    }
    return $cnt;
}

sub make_soap_args {
    my ($serializer,@args) = @_;

    my $soapenc = $serializer->find_prefix($SOAP::Constants::NS_ENC);

    my @rargs = ();
    while(@args){
        my $k = shift(@args);
        my $v = shift(@args);
        if( ref($v) eq 'HASH' ){
            push(@rargs, SOAP::Data->name( $k => \SOAP::Data->value( make_soap($serializer,$v) )) );
        } elsif( ref($v) eq 'ARRAY' ){
            my $c = scalar(@$v);
            my %attr = ( "$soapenc:arrayType"=>"xsd:anyType[$c]", "xsi:type"=>"$soapenc:Array" );
#            $attr{"xsi:nil"} = "true" if( !$c );
            push(@rargs, SOAP::Data->name( $k => \SOAP::Data->value( make_soap($serializer,$v) ))->attr( \%attr ) );
        } else {
            push(@rargs, SOAP::Data->name( $k => $v )); 
        }
    }

    return @rargs;
}
sub make_soap {
    my ($serializer,$st) = @_;

    my $soapenc = $serializer->find_prefix($SOAP::Constants::NS_ENC);

    my @res = ();
    if( ref($st) eq 'HASH' ){
        my @sres = ();
        for my $k ( keys %$st ){
            my $v = $st->{"$k"};
            if( ref($v) eq 'HASH' ){
                push(@sres, SOAP::Data->name( $k => \SOAP::Data->value( make_soap($serializer,$v) )) );
            } elsif( ref($v) eq 'ARRAY' ){
                my $c = scalar(@$v);
                my %attr = ( "$soapenc:arrayType"=>"xsd:anyType[$c]", "xsi:type"=>"$soapenc:Array" );
#                $attr{"xsi:nil"} = "true" if( !$c );
                push(@sres, SOAP::Data->name( $k => \SOAP::Data->value( make_soap($serializer,$v) ))->attr( \%attr ) );
            } else {
                push(@sres, SOAP::Data->name( $k => $v )); 
            }
        }
        push(@res, @sres);
    } elsif( ref($st) eq 'ARRAY' ){
        if( @$st ){
            my @sres = ();
            for my $e (@$st){
                if( ref($e) eq 'HASH' ){
                    push(@sres, SOAP::Data->name('item' => \SOAP::Data->value( make_soap($serializer,$e) ) ));
                } elsif( ref($e) eq 'ARRAY' ){
                    my $c = scalar(@$e);
                    my %attr = ( "$soapenc:arrayType"=>"xsd:anyType[$c]", "xsi:type"=>"$soapenc:Array" );
#                    $attr{"xsi:nil"} = "true" if( !$c );
                    push(@sres, SOAP::Data->name('item' => \SOAP::Data->value( make_soap($serializer,$e) ) )->attr( \%attr ));
                } else {
                    push(@sres, SOAP::Data->name('item' => $e ));
                }
            }
            push(@res, SOAP::Data->name("arrayOfItems" => @sres ) );
        }
    } else {
        push(@res,$st);
    }

    return @res;
}

sub str2size {
    my ($str) = @_;
    my ($size,$t) = ($str =~ m/([0-9.]+)([bBKkMmGg])?/);
    $size = 0 if( !$size );

    $size = $size * 1024 if( $t =~ /K/i );              # convert kbytes to bytes
    $size = $size * 1024 * 1024 if( $t =~ /M/i );       # convert mbytes to bytes
    $size = $size * 1024 * 1024 * 1024 if( $t =~ /G/i );# convert Gbytes to bytes

    my $int_size = int($size);              # convert to int
    $int_size ++ if( $int_size < $size );   # increase one if less then original

    return $int_size;
}
sub prettysize {
    my ($size) = @_;
    
    my $psize = $size || 0;
    my $spsize = "${psize}B";

    my $mod_psize = $psize;
    $mod_psize = -1 * $mod_psize if( $mod_psize < 0 );
    
    if( $mod_psize > 1000 ){
        $psize = $psize / 1024;
        $spsize = sprintf("%.2fKb",$psize);
    }
    $mod_psize = $psize;
    $mod_psize = -1 * $mod_psize if( $mod_psize < 0 );
    if( $mod_psize > 1000 ){
        $psize = $psize / 1024;
        $spsize = sprintf("%.2fMb",$psize);
    }
    $mod_psize = $psize;
    $mod_psize = -1 * $mod_psize if( $mod_psize < 0 );
    if( $mod_psize > 1000 ){
        $psize = $psize / 1024;
        $spsize = sprintf("%.2fGb",$psize);
    }
    return $spsize;
}

sub roundsize {
    my ($s) = @_;
    return prettysize(str2size($s));
}

# list_processes
#   list processes with ps command
sub list_processes {
    
    my @list = ();
    open(P,"/bin/ps fax |");
    my $fst=<P>;
    while(<P>){
        chomp;
        if( /^\s*(\S+)\s+(\S+)\s+(\S+)\s+(\S+)\s+(.+)$/ ){
            
            push(@list, { pid=>$1,
                            tty=>$2,
                            'stat'=>$3,
                            'time'=>$4,
                            args=>$5 });
        }
    }
    close(P);

    return wantarray() ? @list : \@list;
}

# find_procname
#   find process on list_processes by name
sub find_procname {
    my ($name) = @_;
    my @l = grep { $_->{"args"} =~ m#$name# } list_processes();
    return wantarray() ? @l : \@l;
}

sub debug_level {
    return $debug;
}
sub set_debug_level {
    return $debug = shift;
}
sub debug_inc {
    plog "debug_inc ",++$debug;
    return $debug;
}
sub debug_dec {
    plog "debug_dec ",($debug = ($debug > 0 ? --$debug : 0));
    return $debug;
}

# gencerts
#   generate server certificates
sub gencerts {
    my ($organization,$cn,$force) = @_;

    if( !-x "/usr/bin/certtool" ){
        return 0;
    }

    my $ca_topdir = "/etc/pki/CA";
    if( !-d "$ca_topdir" ){
        mkpath( "$ca_topdir" );
    }
    my $topdir = "/etc/pki/libvirt";
    if( !-d "$topdir/private" ){
        mkpath( "$topdir/private" );
    }

    my $ca_keyfile = "$ca_topdir/cakey.pem";
    my $ca_certfile = "$ca_topdir/cacert.pem";
    my $srv_keyfile = "$topdir/private/serverkey.pem";
    my $srv_certfile = "$topdir/servercert.pem";
    my $cli_keyfile = "$topdir/private/clientkey.pem";
    my $cli_certfile = "$topdir/clientcert.pem";

    my $srv_tmpinfo = '/var/tmp/server.info';
    my $ca_tmpinfo = '/var/tmp/ca.info';

    # generate CA certificate
    if( $force || !-e "$ca_keyfile" ){
        cmd_exec_errh("/usr/bin/certtool","--generate-privkey",">$ca_keyfile");
        open(F,">$ca_tmpinfo");
        print F <<__SRVINFO__;
organization = $organization
ca
cert_signing_key
__SRVINFO__
        close(F);
        cmd_exec("/usr/bin/certtool","--generate-self-signed",
                            "--load-privkey","$ca_keyfile",
                            "--template","$ca_tmpinfo",
                            "--outfile","$ca_certfile");
        unlink("$ca_tmpinfo");
    }

    # generate server certificate
    if( $force || !-e "$srv_keyfile" ){
        #certtool --generate-privkey > cakey.pem
        cmd_exec_errh("/usr/bin/certtool","--generate-privkey",">$srv_keyfile");
        open(F,">$srv_tmpinfo");
        print F <<__SRVINFO__;
organization = $organization
cn = $cn
tls_www_server
encryption_key
signing_key
__SRVINFO__
        close(F);
        #certtool --generate-self-signed --load-privkey cakey.pem \
        #  --template ca.info --outfile cacert.pem
#        cmd_exec("/usr/bin/certtool","--generate-self-signed",
        cmd_exec("/usr/bin/certtool","--generate-certificate",
                            "--load-ca-privkey","$ca_keyfile",
                            "--load-ca-certificate","$ca_certfile",
                            "--load-privkey","$srv_keyfile",
                            "--template","$srv_tmpinfo",
                            "--outfile","$srv_certfile");

        symlink("$srv_keyfile","$cli_keyfile");
        symlink("$srv_certfile","$cli_certfile");
        unlink("$srv_tmpinfo");
    }
    return 1;
}

1;
