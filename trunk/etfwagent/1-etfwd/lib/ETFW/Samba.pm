#!/usr/bin/perl

=pod

=head1 NAME

ETFW::Samba

=head1 SYNOPSIS

...

=head1 DESCRIPTION

...

=head1 METHODS

=over 4

=cut

package ETFW::Samba;

use strict;

use Utils;

use Config::IniFiles;

my %CONF = ( "conf_path"=>"/etc/samba",
                "conf_file"=>"",
                "dom_conf_file"=>"/etc/samba/joindom.ini" );

my $DEBUG = 0;

my $joindomconfig;
my $joindomconf;

=item get_dom_conf

    get Samba domain config

=cut

sub get_dom_conf {
    my $self = shift;

    my %conf = ();
    return loadconfigfile($CONF{"dom_conf_file"},\%conf); 
}

=item set_dom_conf

    set samba domain config

=cut

sub set_dom_conf {

    my $self = shift;
    my (%p) = @_;

    my %C = ();
    %C = loadconfigfile($CONF{"dom_conf_file"},\%C); 

    # TODO
    #   make this recursive
    for my $k (keys %p){
        if( my $rf = ref($p{"$k"}) ){
            if( $rf eq "ARRAY" ){
                $C{"$k"} = (ref($C{"$k"}) eq "ARRAY")?([@{$C{"$k"}},@{$p{"$k"}}]):([$C{"$k"},@{$p{"$k"}}]);
            } elsif( $rf eq "HASH" ){
                $C{"$k"} = (ref($C{"$k"}) eq "HASH")?({%{$C{"$k"}},%{$p{"$k"}}}):({%{$p{"$k"}}});
            }
        } else {
            $C{"$k"} = $p{"$k"};
        }
    }

    saveconfigfile($CONF{"dom_conf_file"},\%C,0,1);
}

=item joindomain

    join ETFW machine to Samba domain

=cut

sub joindomain {
    my $self = shift;
	my (@params) = @_;

	$joindomconfig = new Config::IniFiles( -file => $CONF{"dom_conf_file"});
	if ($#params >= 0 && $params[0] eq "test") {
		system("/usr/bin/net ads testjoin");

        return retOk("_OK_TEST_","Testing join ok.");
	} else {
        print "JOINDOM:\tI'm in joindom module!\n" if $DEBUG;

        # let's go
        $joindomconf = $joindomconfig->{'v'};
        my $DOMAIN=uc($joindomconf->{'DOMINIO'}->{'realm'});
        my $USER=$joindomconf->{'DOMINIO'}->{'domainadmin'};
        my $PASS=$joindomconf->{'DOMINIO'}->{'domainpasswd'};

        open FH, ">/etc/krb5.conf" or die "can't open: $!";
        print FH printkrb5();
        close FH;

        open FH, ">/etc/samba/smb.conf" or die "can't open: $!";
        print FH printsmb();
        close FH;

        my $hosts = printhosts();
        unlink("/etc/hosts");
        open FH, ">/etc/hosts" or die "can't open: $!";
        print FH $hosts;
        close FH;

        open FH, ">/etc/samba/lmhosts" or die "can't open: $!";
        print FH "127.0.0.1 localhost\n";
        print FH "$joindomconf->{'DOMINIO'}->{'dcipaddr'} ", uc($joindomconf->{'DOMINIO'}->{'workgroup'}), "\n";
        print FH "$joindomconf->{'DOMINIO'}->{'dcipaddr'} *SMBSERVER\n";
        close FH;

        system("/usr/kerberos/bin/kdestroy 2> /dev/null");
        system("/bin/mkdir -p /mnt/harddisk/var/cache/samba/ 2> /dev/null");
        system("/sbin/service smb restart > /dev/null");
        system("/sbin/service winbind restart > /dev/null");

        retErr "_error_", "Nao consigo acertar a hora pelo servidor de dominio" if (system("/usr/bin/net time set > /dev/null"));
        retErr "_error_", "Nao consigo obter token de administrador" if (system("echo '$PASS' | /usr/kerberos/bin/kinit $USER\@$DOMAIN > /dev/null"));
        retErr "_error_", "Ja existe uma maquina com o mesmo nome no dominio" if (not system("/usr/bin/net ads status > /dev/null"));
        retErr "_error_", "Nao consigo fazer o join" if (system("/usr/bin/net ads join"));
        retErr "_error_", "Nao consigo mudar as permissoes do winbind" if (system("/bin/chgrp squid /mnt/harddisk/var/cache/samba/winbindd_privileged"));

        system("/sbin/hwclock --systohc");
        system("/sbin/service smb restart > /dev/null");
        system("/sbin/service winbind restart > /dev/null");
    }

    retOk("_ok_","Ok");
}

#########################################################################
# SUB FUNCTIONS
#########################################################################
# Imprime o hostname do domain controler para o hosts
# FROM: etfw.pl
sub printhosts {
        my $hosts = `cat /etc/hosts`;
        $hosts =~  s/\n.*$joindomconf->{'DOMINIO'}->{'dchostname'}//g;
        $hosts .= "$joindomconf->{'DOMINIO'}->{'dcipaddr'}\t$joindomconf->{'DOMINIO'}->{'dchostname'}\n";
        return $hosts;
}

sub printsmb {
my $DOMAIN=uc($joindomconf->{'DOMINIO'}->{'realm'});
my $WORKGROUP=uc($joindomconf->{'DOMINIO'}->{'workgroup'});
my $HOSTNAME=uc($joindomconf->{'PROXY'}->{'hostname'});
my $DCIP=$joindomconf->{'DOMINIO'}->{'dcipaddr'};

my $res;
$res=<<"EOF";
[global]
        workgroup = $WORKGROUP
        realm = $DOMAIN
        netbios name = $HOSTNAME
        password server = $DCIP
        hosts allow = 127.0.0.
        interfaces = 127.0.0.1/24
        bind interfaces only = Yes

        preferred master = no
        domain master = no
        disable spoolss = yes
        server string = Eurotux Firewall
        security = ADS
        obey pam restrictions = Yes
        log file = /var/log/samba/%m.log
        max log size = 50
        socket options = TCP_NODELAY SO_RCVBUF=8192 SO_SNDBUF=8192
        load printers = No
        printcap name = /etc/printcap
        local master = No
        dns proxy = No
        idmap uid = 10000-20000
        idmap gid = 10000-20000

        winbind use default domain = Yes
        winbind nested groups = Yes
        winbind enum users= Yes
        winbind enum groups = Yes
        use spnego = yes
        winbind separator = +
        winbind cache time = 600
EOF
    return $res;
}

sub printkrb5 {
my $DOMAIN=uc($joindomconf->{'DOMINIO'}->{'realm'});

my $res;
$res=<<"EOF";
[logging]
 default = FILE:/var/log/krb5libs.log
 kdc = FILE:/var/log/krb5kdc.log
 admin_server = FILE:/var/log/kadmind.log

[libdefaults]
 ticket_lifetime = 24000
 default_realm = $DOMAIN
 dns_lookup_realm = false
 dns_lookup_kdc = false

[realms]
 $DOMAIN = {
  kdc = $joindomconf->{'DOMINIO'}->{'dchostname'}:88
  admin_server = $joindomconf->{'DOMINIO'}->{'dchostname'}:749
  default_domain = $joindomconf->{'DOMINIO'}->{'realm'}
 }

[domain_realm]
 .$joindomconf->{'DOMINIO'}->{'realm'} = $DOMAIN
 $joindomconf->{'DOMINIO'}->{'realm'} = $DOMAIN

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
    return $res;
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

