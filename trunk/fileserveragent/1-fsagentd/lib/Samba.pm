#!/usr/bin/perl

=pod

=head1 NAME

ETVOIP

=head1 SYNOPSIS

...

=head1 DESCRIPTION

...

=head1 METHODS

=over 4

=cut

package Samba;

use strict;

use ETVA::Utils;
use ETVA::FileFuncs;
use ETVA::NetworkTools;
use ETVA::ArchiveTar;

use Socket;
use LWP::Simple;
use File::Path qw( mkpath );

use Data::Dumper;

my $TMP_DIR = "/var/tmp";
my %CONF = ( 'cfg_file'=>"/etc/samba/smb.conf" ,
                'pdbedit_cmd'=>'/usr/bin/pdbedit',
                'net_cmd'=>'/usr/bin/net',
                'samba_restart_cmd'=>'service smb restart',
                'samba_stop_cmd'=>'service smb stop',
                'samba_start_cmd'=>'service smb start',
                'samba_servicestatus_cmd'=>'service smb status',
                'samba_status_cmd'=>'/usr/bin/smbstatus',
                'lmhosts_file'=>'/etc/samba/lmhosts',
                'resolv_conf_file'=>'/etc/resolv.conf',
                'kdestroy_cmd'=>'/usr/bin/kdestroy',
                'kinit_cmd'=>'/usr/bin/kinit',
                'winbind_restart_cmd'=>'service winbind restart',
                'hwclock_cmd'=>'/sbin/hwclock',
                'samba_log_file'=>'/var/log/samba/%m.log',
                'samba_printcap_name'=>'/etc/printcap',
                'krb5_conf_file'=>'/etc/krb5.conf',
                'samba_password_cmd'=>'/usr/bin/smbpasswd',
                'password_cmd'=>'/usr/bin/passwd'
                 );

my %aliaskey = ( 'server_string'=>'server string',
                    'log_file'=>'log file','max_log_size'=>'max log size',
                    'passdb_backend'=>'passdb backend',
                    'load_printers'=>'load printers','cups_options'=>'cups options' );

my $DEFAULT_TIMEOUT_SECONDS = 300;      # default value of timeout for timeout command

# timeout_cmd
sub timeout_cmd {
    my ($ts,$force) = @_;

    my $args = "";
    $ts = $DEFAULT_TIMEOUT_SECONDS if( !$ts );
    $args = "--signal=SIGKILL" if( $force );
    
    return "timeout $args $ts";
}

=item new
    create object

=cut

sub new {
    return bless { @_ }, __PACKAGE__;
}

=item list_shares

    list of samba shares

=cut

sub list_shares {
    my $self = shift;
    
    my $lines = &read_file_lines("$CONF{cfg_file}");

    my @shares = ();
    my %S = ();
    foreach (@$lines){
        my $s = $_;
        chomp($s);
        $s =~ s/^\s*;.*$//g;
        $s =~ s/^\s*#.*$//g;
        if( $s =~ m/^\s*\[([^\]]+)\]\s*$/ ){
            my $share_name = $1;
            push(@shares, { %S }) if( %S );
            %S = ( 'name'=>"$share_name" );
        } elsif( $s =~ /^\s*([^=]+)\s*=\s*(.+)\s*$/ ){
            my ($k,$v) = ($1,$2);
            $k = trim($k);
            $v = trim($v);
            $S{"$k"} = "$v";
        }
    }
    push(@shares, { %S }) if( %S );

    return wantarray() ? @shares : \@shares;
}

=item create_share

    create a new samba share

=cut

sub create_share {
    my $self = shift;
    my (%S) = @_;

    my $lines = &read_file_lines("$CONF{cfg_file}");
    
    my $share_name = delete($S{'name'});
    push(@$lines, "[$share_name]");

    my %V = &getValidShare(%S);
    foreach my $k (keys %V){
        if( my $v = $S{"$k"} ){
            push(@$lines, "\t$k = $v");
        }
    }
    &flush_file_lines("$CONF{cfg_file}");

    # create path
    if( $S{'path'} && (!-e $S{'path'}) ){
        mkpath("$S{'path'}");
    }

    return retOk("_CREATE_SHARE_","Share '$share_name' successfully created.");
}

=item update_share

    modify samba share

=cut

sub modify_share {
    my $self = shift;
    my ($share_name,@new_lines) = @_;

    my $lines = &read_file_lines("$CONF{cfg_file}");

    my $i = 0;
    for(; $i<scalar(@$lines); $i++){
        my $bkp = $lines->[$i];
        my $s = $bkp;
        chomp($s);
        $s =~ s/^\s*;.*$//g;
        $s =~ s/^\s*#.*$//g;
        if( $s =~ m/^\s*\[([^\]]+)\]\s*$/ ){
            my $ename = $1;
            if( $share_name eq $ename ){
                last;
            }
        }
    }
    my $s = $i;
    my $e = 0;
    for($i++; $i<scalar(@$lines); $i++, $e++){
        my $bkp = $lines->[$i];
        my $s = $bkp;
        chomp($s);
        $s =~ s/^\s*;.*$//g;
        $s =~ s/^\s*#.*$//g;
        if( $s =~ m/^\s*\[([^\]]+)\]\s*$/ ){
            last;
        }
    }
    splice(@$lines,$s,$e+1,@new_lines);
    &flush_file_lines("$CONF{cfg_file}");
}

sub update_share {
    my $self = shift;
    my (%S) = @_;

    my $share_name = delete($S{'name'});

    my @new_lines = ();
    push(@new_lines,"[$share_name]");

    my %V = &getValidShare(%S);
    foreach my $k (keys %V){
        if( my $v = $V{"$k"} ){
            push(@new_lines,"\t$k = $v");
        }
    }
    $self->modify_share($share_name,@new_lines);

    # create path
    if( $S{'path'} && (!-e $S{'path'}) ){
        mkpath("$S{'path'}");
    }

    return retOk("_UPDATE_SHARE_","Share '$share_name' successfully updated.");
}

sub getValidShare {
    my (%S) = @_;
    my %V = ();
    foreach my $k (keys %S){
        if( my $vk = &validShareKey($k) ){
            $V{"$vk"} = $S{"$k"};
        }
    }
    return wantarray() ? %V : \%V;
}
sub validShareKey {
    my ($k) = @_;
    return 0 if( $k !~ m/^[a-zA-Z]/ );
    return 0 if( $k eq 'configtype' );
    return 0 if( $k eq 'name' );
    my $nk = $k;
    $nk =~ s/_/ /g; # TODO dont normalize for exceptions
    return $aliaskey{$k} || $nk;
}

=item delete_share

    delete samba share

=cut

sub delete_share {
    my $self = shift;
    my (%S) = @_;

    my $share_name = delete($S{'name'});

    $self->modify_share($share_name);

    return retOk("_DELETE_SHARE_","Share '$share_name' successfully deleted.");
}

# treat users samba
#   list users from samba
sub list_smb_users {
    my $self = shift;
    my (%p) = @_;

    my @list = ();
    open(SPASSFH,"$CONF{'pdbedit_cmd'} -L -w -s $CONF{'cfg_file'} |");
    while(<SPASSFH>){
        chomp;
        s/^\s*#.*$//g;
        my @f = split(/:/, $_);
        next if( scalar(@f) < 4);
        my %user = ( 'name'=>$f[0], 'uid'=>$f[1], 'pass1'=>$f[2], 'pass2'=>$f[3] );
        $f[4] =~ s/[\[\] ]//g;
        $user{'opts'} = [ split(//, $f[4]) ];
        $user{'change'} = $f[5];
        push(@list, \%user);
    }
    close(SPASSFH);
    return wantarray() ? @list : \@list;
}

#   create user on samba
sub create_smb_user {
    my $self = shift;
    my (%p) = @_;

    my @p_opts = (ref($p{'opts'}) eq 'ARRAY') ? @{$p{'opts'}} : split(/,/,$p{'opts'});
    my @opts = grep { $_ ne "U" && $_ ne "W" } @p_opts;

    my @extra = ();
    push(@extra, "-G $CONF{'sync_gid'}") if($CONF{'sync_gid'});
    push(@extra, "-c '[".join("", @opts)."]'");
    push(@extra, "-m") if(grep { /W/ } @p_opts);

    my $password = $p{'password'};
    my ($e,$out) = cmd_exec("echo \"$password\n$password\" | $CONF{'pdbedit_cmd'} -t -a -s $CONF{'cfg_file'} -u $p{'name'}",@extra);
    unless( $e == 0 ){
        return retErr("_ERR_CREATE_USER_","Could not create user '$p{name}'. $out");
    }

    return retOk("_CREATE_USER_OK_","User '$p{name}' successfully created.");
}

#   delete user from samba
sub delete_smb_user {
    my $self = shift;
    my (%p) = @_;

    my ($e,$out) = cmd_exec("$CONF{'pdbedit_cmd'} -x -s $CONF{'cfg_file'} -u $p{'name'}");
    unless( $e == 0 ){
        return retErr("_ERR_DELETE_USER_","Could not delete user '$p{name}'. $out");
    }
    return retOk("_DELETE_USER_OK_","User '$p{name}' successfully deleted.");
}

#   update user from samba
sub update_smb_user {
    my $self = shift;
    my (%p) = @_;

    if( $p{'oldname'} ){
        my %E = $self->delete_smb_user(%p, 'name'=>$p{'oldname'});
        if( isError(%E) ){
            return wantarray() ? %E : \%E;
        }
        %E = $self->create_smb_user(%p);
        if( isError(%E) ){
            return wantarray() ? %E : \%E;
        }
    } else {
        my @p_opts = (ref($p{'opts'}) eq 'ARRAY') ? @{$p{'opts'}} : split(/,/,$p{'opts'});
        #if(!grep { /W/ } @p_opts){
        #    return retErr("_ERR_UPDATE_USER_","Could not update user '$p{name}'. The 'Workstation trust account' option must be enable for existing users.");
        #}

        my @opts = grep { $_ ne "U" } @p_opts;

        my @extra = ();
        push(@extra, "-c '[".join("", @opts)."]'");

        my ($e,$out) = cmd_exec("$CONF{'pdbedit_cmd'} -r -s $CONF{'cfg_file'} -u $p{'name'}",@extra);
        unless( $e == 0 ){
            return retErr("_ERR_UPDATE_USER_","Could not update user '$p{name}'. $out");
        }
    }

    if( $p{'change_password'} ){
        my $password = $p{'newpassword'};
        cmd_exec("echo \"$password\n$password\" | $CONF{'samba_password_cmd'} -s $p{'name'}");
    }

    return retOk("_UPDATE_USER_OK_","User '$p{name}' successfully updated.");
}

=item list_users

    list users

=cut

sub list_users {
    my $self = shift;
    my (%p) = @_;

    my @list = ();
    open(PASSFH,"getent passwd |");
    while(<PASSFH>){
        chomp;
        s/^\s*#.*$//g;
        my @f = split(/:/, $_);
        next if( scalar(@f) < 4);
        my %user = ( 'name'=>$f[0], 'pass'=>$f[1], 'uid'=>$f[2], gid=>$f[3], 'desc'=>$f[4], 'home'=>$f[5], 'shell'=>$f[6] );
        push(@list, \%user);
    }
    close(PASSFH);
    return wantarray() ? @list : \@list;
}

=item create_user

    create new user

        args: sync_to_samba - to create user on samba

=cut

sub create_user {
    my $self = shift;
    my (%p) = @_;

    my @extra = ($p{'name'});
    foreach my $k (qw(gid groups home-dir shell uid expiredate inactive)){
        unshift(@extra, "--$k",$p{"$k"}) if( $p{"$k"} );
    }
    my ($e,$out) = cmd_exec("useradd",@extra);
    unless( $e==0 ){
        return retErr("_ERR_CREATE_USER_","Could not create user '$p{name}'. $out");
    }

    # set password
    if( $p{'password'} ){
        my $password = $p{'password'};
        cmd_exec("echo \"$password\n$password\" | passwd --stdin $p{'name'}");
    }

    if( $p{'sync_to_samba'} ){
        my %E = $self->create_smb_user(%p);
        if( isError(%E) ){
            return wantarray() ? %E : \%E;
        }
    }
    return retOk("_CREATE_USER_OK_","User '$p{name}' successfully created.");
}

=item update_user

    modify user options

        args: sync_to_samba - to update on samba

=cut

sub update_user {
    my $self = shift;
    my (%p) = @_;

    my @extra = ($p{'oldname'}) ? ("--login",$p{'name'},$p{'oldname'}) : ($p{'name'});

    foreach my $k (qw(gid groups home shell uid expiredate inactive)){
        unshift(@extra, "--$k",$p{"$k"}) if( $p{"$k"} );
    }
    unshift(@extra, "--move-home") if( $p{"move-home"} );
    my ($e,$out) = cmd_exec("usermod",@extra);
    unless( $e==0 ){
        return retErr("_ERR_UPDATE_USER_","Could not update user '$p{name}'. $out");
    }
    if( $p{'change_password'} ){
        my $password = $p{'newpassword'};
        cmd_exec("echo \"$password\n$password\" | passwd --stdin $p{'name'}");
    }
    if( $p{'sync_to_samba'} ){
        my %E = $self->update_smb_user(%p);
        if( isError(%E) ){
            return wantarray() ? %E : \%E;
        }
    }
    return retOk("_UPDATE_USER_OK_","User '$p{name}' successfully updated.");
}

=item delete_user

    delete user

        args: sync_to_samba - to delete user from samba too

=cut

sub delete_user {
    my $self = shift;
    my (%p) = @_;

    my @extra = ($p{'name'});
    foreach my $k (qw(force remove selinux-user)){
        unshift(@extra, "--$k") if( $p{"$k"} );
    }
    my ($e,$out) = cmd_exec("userdel",@extra);
    unless( $e==0 ){
        return retErr("_ERR_DELETE_USER_","Could not delete user '$p{name}'. $out");
    }
    if( $p{'sync_to_samba'} ){
        my %E = $self->delete_smb_user(%p);
        if( isError(%E) ){
            return wantarray() ? %E : \%E;
        }
    }
    return retOk("_DELETE_USER_OK_","User '$p{name}' successfully deleted.");
}

=item list_groups

    list groups

=cut

sub list_groups {
    my $self = shift;
    my (%p) = @_;

    my @list = ();
    open(GROUPSFH,"getent group |");
    while(<GROUPSFH>){
        chomp;
        s/^\s*#.*$//g;
        my @f = split(/:/, $_);
        next if( scalar(@f) < 4);
        my $users = $f[3];
        my %group = ( 'name'=>$f[0], 'pass'=>$f[1], 'gid'=>$f[2], 'users'=>[ split(/,/,$users) ] );
        push(@list, \%group);
    }
    close(GROUPSFH);
    return wantarray() ? @list : \@list;
}

# treat groups samba
#   list groups from samba
sub list_smb_groups {
    my $self = shift;
    my (%p) = @_;

    my $group = {};
    my @list = ();
    open(SGROUPSFH,"$CONF{'net_cmd'} -s $CONF{'cfg_file'} groupmap list verbose |");
    while(<SGROUPSFH>){
        chomp;
        s/^\s*#.*$//g;
        if (/^(\S.*)/) {
            $group = { 'name'=>$1 };
            push(@list, $group);
        } elsif (/^\s+SID\s*:\s+(.*)/i) {
            $group->{'sid'} = $1;
        } elsif (/^\s+Unix group\s*:\s*(.*)/i) {
            $group->{'unix'} = $1;
        } elsif (/^\s+Group type\s*:\s*(.*)/i) {
            $group->{'type'} = $1;
        } elsif (/^\s+Comment\s*:\s*(.*)/i) {
            $group->{'desc'} = $1;
        } elsif (/^\s+Privilege\s*:\s*(.*)/i) {
            $group->{'priv'} = $1;
        }
    }
    close(SGROUPSFH);
    return wantarray() ? @list : \@list;
}

=item start_service / stop_service / restart_service

    start, stop and restart samba service

=cut

sub start_service {
    my ($e,$out) = cmd_exec("$CONF{'samba_start_cmd'}");
    unless( $e == 0 ){
        return retErr("_ERR_START_SERVICE_","Could not start samba service. $out");
    }
    return retOk("_START_SERVICE_OK_","Samba service start successfully.");
}

sub stop_service {
    my ($e,$out) = cmd_exec("$CONF{'samba_stop_cmd'}");
    unless( $e == 0 ){
        return retErr("_ERR_STOP_SERVICE_","Could not stop samba service. $out");
    }
    return retOk("_STOP_SERVICE_OK_","Samba service stop successfully.");
}

sub restart_service {
    my ($e,$out) = cmd_exec("$CONF{'samba_restart_cmd'}");
    unless( $e == 0 ){
        return retErr("_ERR_RESTART_SERVICE_","Could not restart samba service. $out");
    }
    return retOk("_RESTART_SERVICE_OK_","Samba service restart successfully.");
}

sub status_service {
    my ($e,$out) = cmd_exec("$CONF{'samba_servicestatus_cmd'}");
    chomp($out);
    my $running = 0;
    my $status = 'stopped';
    if( $out =~ m/running/i ){
        $status = 'running';
        $running = 1;
    }
    my %res = ( 'status'=>$status, 'running'=>$running, 'out'=>$out );
    return wantarray() ? %res : \%res;
}

=item get_global_configuration

    get global configuration

=cut

sub get_global_configuration {
    my $self = shift;
    my (%p) = @_;

    my ($G) = grep { $_->{'name'} eq 'global'} $self->list_shares();
    $G = {} if( !$G );
    return wantarray() ? %$G : $G;
}

=item set_global_configuration

    override global configuration

=cut

sub set_global_configuration {
    my $self = shift;
    my (%p) = @_;

    my $configtype = delete($p{'configtype'});

    my $G = $self->get_global_configuration();

    # filter for valid share parameters
    foreach my $k (keys %p){
        if( my $vk = &validShareKey($k) ){
            $G->{"$vk"} = $p{"$k"};
        }
    }

    if( ($p{'security'} eq 'ads') ||
            ($configtype eq 'ads') ){

        my $domain = $p{'realm'} || $G->{'realm'};

        if( !$p{'dchostname'} ){
            $p{'dchostname'} = $G->{'password server'};
        }
        if( !$p{'dcipaddr'} ){
            $p{'dcipaddr'} = $p{'dchostname'};
        }
        # convert to ip
        eval {
            $p{'dcipaddr'} = inet_ntoa(inet_aton($p{'dcipaddr'})) if( $p{'dcipaddr'} !~ m/\d+\.\d+\.\d+\.\d+/ );
        };
        if( $@ ){
            return retErr("_ERR_SET_GLOBAL_CONFIGURATION_","Error join to domain: No AD server configuration.");
        }

        my $dcipaddr = $p{'dcipaddr'};
        my $dchostname = $p{'dchostname'};

        if( !$dcipaddr || !$dchostname ){
            return retErr("_ERR_SET_GLOBAL_CONFIGURATION_","Error join to domain: No AD server configuration.");
        }

        $self->configure_join_to_domain_krb5(%p);

        $self->configure_join_to_domain_samba(%p);

        # add to /etc/hosts
        if( $dchostname ne $dcipaddr ){
            ETVA::NetworkTools::fix_hostname_resolution($dchostname,$dcipaddr);
        }

        my $workgroup = $p{'workgroup'} || $G->{'workgroup'};

        # change resolv.conf
        my $resolv_conf = &read_file_lines($CONF{'resolv_conf_file'});
        @$resolv_conf = ("search $domain",
                        "nameserver $p{'dcipaddr'}");
        &flush_file_lines($CONF{'resolv_conf_file'});

        # change lmhosts
        my $lmhosts = &read_file_lines($CONF{'lmhosts_file'});
        @$lmhosts = ("127.0.0.1 localhost",
                        "$dcipaddr ".uc($workgroup),
                        "$dcipaddr *SMBSERVER");
        &flush_file_lines($CONF{'lmhosts_file'});

    } else {
        $self->update_share(%$G);
    }
    return retOk("_SET_GLOBAL_CONFIGURATION_","Global configuration successfully updated.");
}

=item join_to_domain

    join samba to Domain

=cut

sub configure_join_to_domain_krb5 {
    my $self = shift;
    my %p = @_;

    my $domain = uc($p{'realm'});
    open(KRB5_FH,">$CONF{'krb5_conf_file'}");
    print KRB5_FH <<"EOF";
[logging]
 default = FILE:/var/log/krb5libs.log
 kdc = FILE:/var/log/krb5kdc.log
 admin_server = FILE:/var/log/kadmind.log

[libdefaults]
 ticket_lifetime = 24000
 default_realm = $domain
 dns_lookup_realm = false
 dns_lookup_kdc = false

[realms]
 $domain = {
  kdc = $p{'dchostname'}:88
  admin_server = $p{'dchostname'}:749
  default_domain = $p{'realm'}
 }

[domain_realm]
 .$p{'realm'} = $domain
 $p{'realm'} = $domain

[kdc]
 profile = /var/kerberos/krb5kdc/kdc.conf

[appdefaults]
 pam = {
   debug = false
   ticket_lifetime = 36000
   renew_lifetime = 36000
   forwardable = true
   krb4_convert = false
 }
EOF
    close(KRB5_FH);

}
sub configure_join_to_domain_samba {
    my $self = shift;
    my %p = @_;

    my $G = $self->get_global_configuration();

    $G->{'workgroup'} = uc($p{'workgroup'}) if( $p{'workgroup'} );
    $G->{'realm'} = uc($p{'realm'}) if( $p{'realm'} );
    $G->{'netbios name'} = uc($p{'netbios name'}) || uc(delete($p{'netbios_name'})) || uc(delete($p{'proxy_hostname'})) if( $p{'netbios name'} || $p{'netbios_name'} || $p{'proxy_hostname'} );
    $G->{'password server'} = $p{'password server'} || delete($p{'password_server'}) || delete($p{'dcipaddr'}) if( $p{'password server'} || $p{'password_server'} || $p{'dcipaddr'} );
	$G->{'hosts allow'} = "127.0.0.";
	$G->{'interfaces'} = "127.0.0.1/24";
	$G->{'bind interfaces only'} = "Yes";

	$G->{'preferred master'} = "no";
	$G->{'domain master'} = "no";
	$G->{'disable spoolss'} = "yes";
	$G->{'server string'} = $p{'server string'} || delete($p{'server_string'}) if( $p{'server string'} || $p{'server_string'} );
	$G->{'security'} = "ads";
	$G->{'obey pam restrictions'} = "Yes";
	$G->{'log file'} = $p{'log file'} || delete($p{'log_file'}) || $CONF{'samba_log_file'} || "/var/log/samba/%m.log";
	$G->{'max log size'} = "50";
	$G->{'socket options'} = "TCP_NODELAY SO_RCVBUF=8192 SO_SNDBUF=8192";
	$G->{'load printers'} = "No";
	$G->{'printcap name'} = $p{'printcap name'} || delete($p{'printcap_name'}) || $CONF{'samba_printcap_name'} || "/etc/printcap";
	$G->{'local master'} = "No";
	$G->{'dns proxy'} = "No";
	$G->{'idmap uid'} = "10000-20000";
	$G->{'idmap gid'} = "10000-20000";

	$G->{'winbind use default domain'} = "Yes";
	$G->{'winbind nested groups'} = "Yes";
	$G->{'winbind enum users'} = "Yes";
	$G->{'winbind enum groups'} = "Yes";
	$G->{'use spnego'} = "yes";
	$G->{'winbind separator'} = "+";
	$G->{'winbind cache time'} = "600";

    $self->update_share(%$G);
}

sub join_to_domain {
    my $self = shift;
    my %p = @_;

    my $test = delete($p{'test'});

    my $domain = $p{'realm'};
    my $user = delete($p{'domainadmin'});
    my $pass = delete($p{'domainpasswd'});

    # set global configuration as ADS
    my %E = $self->set_global_configuration(%p, 'configtype'=>'ads');
    if( isError(%E) ){
        return wantarray() ? %E : \%E;
    }

    if( $test ){
        my ($e,$out) = cmd_exec(&timeout_cmd(),"$CONF{'net_cmd'} ads testjoin");
        unless( $e == 0 ){
            return retErr("_ERR_JOIN_TO_DOMAIN_","Error testing join to domain. $out");
        }
        return retOk("_JOIN_TO_DOMAIN_OK_","Test join to domain run successfully.");
    } else {

        # TODO requires krb5-workstation
        cmd_exec("$CONF{'kdestroy_cmd'}");
        cmd_exec("$CONF{'samba_restart_cmd'}");
        cmd_exec("$CONF{'winbind_restart_cmd'}");

        unless( 1 || cmd_exec("$CONF{'net_cmd'} time set") == 0 ){
            return retErr("_ERR_JOIN_TO_DOMAIN_","Could not synchronize clock with domain server.");
        }
        # TODO requires krb5-workstation
        unless( cmd_exec("echo '$pass' | $CONF{'kinit_cmd'} $user\@$domain") == 0 ){
            return retErr("_ERR_JOIN_TO_DOMAIN_","Could not get token of administrator.");
        }
        if( cmd_exec(&timeout_cmd(),"$CONF{'net_cmd'} ads status -U '$user%$pass'") == 0 ){
            return retErr("_ERR_JOIN_TO_DOMAIN_","There already exists on hosts with same name in domain.");
        }
        unless( cmd_exec(&timeout_cmd(),"$CONF{'net_cmd'} ads join -U '$user%$pass'") == 0 ){
            return retErr("_ERR_JOIN_TO_DOMAIN_","Could not join to domain.");
        }

        cmd_exec("$CONF{'hwclock_cmd'} --systohc");
        cmd_exec("$CONF{'samba_restart_cmd'}");
        cmd_exec("$CONF{'winbind_restart_cmd'}");

        return retOk("_JOIN_TO_DOMAIN_OK_","Join to domain '$domain' with successfully.");
    }
}

=item get_samba_status

    get status of samba like services and users connections

=cut

# get samba status
sub get_samba_status {
    my $self = shift;
    
    open(SMBSTATUS_FH,"$CONF{'samba_status_cmd'} -s $CONF{'cfg_file'} |");

    while(<SMBSTATUS_FH>){
        last if( /----/ );
    }
    
    my %pidusers = ();
    while(<SMBSTATUS_FH>){
        last if( /----/ );
        if( /^\s*(\d+)\s+(\S+)\s+(\S+)\s+(\S+)\s+\((\S+)\)/ ){ 
            my %user = ( 'pid'=>"$1", 'username'=>"$2", 'group'=>"$3", 'hostname'=>"$4", 'ipaddr'=>"$5" );
            $pidusers{"$user{'pid'}"} = { %user };

        }
    }
    my @services = ();
    while(<SMBSTATUS_FH>){
        if( /^\s*(\S+)\s+(\d+)\s+(\S+)\s+(.*)$/ ){
            my %service = ( 'service'=>"$1", 'pid'=>"$2", 'hostname'=>"$3", 'connected_at'=>"$4" );
            $service{'USER'} = $pidusers{"$service{'pid'}"};
            push(@services, \%service);
        }
    }
    close(SMBSTATUS_FH);
    return wantarray() ? @services : \@services;
}
sub get_samba_status_raw {
    my $self = shift;
    my ($e,$m) = cmd_exec("$CONF{'samba_status_cmd'} -s $CONF{'cfg_file'} ");

    unless( $e == 0 ){
        return retErr('_ERR_GET_SAMBA_STATUS_',"Error get samba status: $m ");
    }
    my %res = ( 'status'=>$m );
    return wantarray() ? %res : \%res;
}

# get_backupconf - get backup of configuration file
sub get_backupconf {
    my $self = shift;
    my (%p) = @_;

    my $sock = $p{'_socket'};

    # set blocking for wait to transmission end
    $sock->blocking(1);

    if( $p{'_make_response'} ){
        print $sock $p{'_make_response'}->("",'-type'=>'application/x-tar');
    }

    my $c_path = $CONF{'cfg_file'};
    my $tar = new ETVA::ArchiveTar( 'handle'=>$sock );
    $tar->add_file( 'name'=>"$c_path", 'path'=>"$c_path" );

    for my $kf (qw(lmhosts_file resolv_conf_file krb5_conf_file samba_log_file)){
        my $f = $CONF{"$kf"};
        $tar->add_file( 'name'=>$f, 'path'=>$f);
    }

    my $agent_logs = $ENV{'agent_log_dir'} || "/var/log/etva-*/*.log";

    # add agent logs
    while(<$agent_logs>){
        $tar->add_file( 'name'=>"$_", 'path'=>"$_" );
    }

    $tar->write();

    return;
}


# set_backupconf - overwrite configuration file
sub set_backupconf {
    my $self = shift;
    my (%p) = @_;

    my $tar = ETVA::ArchiveTar->new();

    # set word dir to /
    $tar->setcwd( "/" );

    my $tmpbf;
    if( $p{'_url'} ){
        $tmpbf = ETVA::Utils::rand_tmpfile("${TMP_DIR}/.samba-setbkpconf-tmpfile");
        my $rc = LWP::Simple::getstore("$p{'_url'}","$tmpbf");
        if( is_error($rc) || !-e "$tmpbf" ){
            return retErr('_ERR_SET_BACKUPCONF_',"Error get backup file ($tmpbf status=$rc) ");
        }
        $tar->read($tmpbf);
    } else {
        my $sock = $p{'_socket'};

        # set blocking for wait to transmission end
        $sock->blocking(1);
        $tar->read($sock);
    }

    plog "set_backupconf files=",$tar->list_files();
    $tar->extract();

    if( $tmpbf ){
        # remove tmp file
        unlink "$tmpbf";
    }

    return;
}

sub getstate {
    my $self = shift;
    return retOk("_OK_STATE_","I'm alive.");
}

# testing if fork func
sub isForkable {
    my $self = shift;
    my ($method) = @_;

    my $v = 0;
    $v = 1 if ( $method eq 'list_users' );
    plogNow("Samba isForkable method=$method flag=$v") if( 1 || &debug_level > 3 );
    return $v;
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

__END__
