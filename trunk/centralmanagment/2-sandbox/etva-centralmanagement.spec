%{!?rhel:%define rhel   %(cat /etc/redhat-release |sed -e 's/.*release //' -e 's/\..*//')}
%define nagios_plugins /usr/lib64/nagios/plugins  

Name: etva-centralmanagement
Version: 2.1.0
Release: 2536
Summary: ETVA Central Management
License: GPL
Group: Applications/Web
URL: http://www.eurotux.com
Source: etva-centralmanagement-%{version}-%{release}.tar.gz
#BuildRoot: %{_tmppath}/%{name}-%{version}-%{release}
BuildRoot: %{_tmppath}/%{name}
BuildArch: noarch

Requires: php >= 5.2.9
Requires: php-pecl-apc
Requires: httpd >= 2.2.11
Requires: vsftpd
Requires: rrdtool >= 1.4.2
Requires: symfony
%if 0%{?fedora} >= 12 || 0%{?rhel} >=6
Requires: system-config-network-tui
%else
Requires: system-config-network
%endif
Requires: sudo
Requires: mysql-server
Requires: mysql
Requires: apr-util-mysql
Requires: php-pear-SOAP >= 0.12
Requires: php-mysql
Requires: avahi
Requires: logrotate
Requires: gpg
Requires: perl-Email-Sender
Requires: perl-MIME-tools
Requires: perl-Net-SMTP-SSL
Requires: sos
Requires(post): chkconfig perl
BuildRequires: symfony
Requires: /usr/bin/timeout
Requires: cman selinux-policy

%description
ETVA Central Management

%package    ent
Summary:    Enterprise Version of ETVA
Group:      Applications/Web
Requires:   %{name}

%description ent
Enterprise files of ETVA

%package    smb
Summary:    SMB Version of ETVA
Group:      Applications/Web
Requires:   %{name}

%description smb
SMB files of ETVA

%package    https
Summary:    HTTPS configuration Version of ETVA
Group:      Applications/Web
Requires:   %{name}
Requires:   openssl
Requires:   mod_ssl
Requires:   httpd >= 2.2.11-9

%description https
HTTPS configuration files of ETVA

%package nrpe
Summary:    NRPE Nagios checks
Group:      Applications/Web
Requires:   %{name}
Requires:   etux-nrpe

%description nrpe
NRPE Nagios checks

%prep
%setup -q -n %{name}-%{version}-%{release}

%build
#
#rebuild all data and create db from doc/database db4.xml
#
# Correct permissions
/usr/bin/symfony project:permissions;
# clean cache
/usr/bin/symfony cc;

%install
rm -rf $RPM_BUILD_ROOT;

%{__mkdir_p} $RPM_BUILD_ROOT/srv/etva-centralmanagement;
%{__mkdir_p} $RPM_BUILD_ROOT/srv/etva-centralmanagement/.ssh;
%{__mkdir_p} $RPM_BUILD_ROOT%{_sysconfdir}/httpd/conf.d/;
%{__mkdir_p} $RPM_BUILD_ROOT%{_sysconfdir}/php.d/;
%{__mkdir_p} $RPM_BUILD_ROOT%{_sysconfdir}/cron.d/
%{__mkdir_p} $RPM_BUILD_ROOT%{_sysconfdir}/sysconfig/
%{__mkdir_p} $RPM_BUILD_ROOT%{nagios_plugins}/
%{__mkdir_p} $RPM_BUILD_ROOT%{_sysconfdir}/avahi/services/
%{__mkdir_p} $RPM_BUILD_ROOT/usr/share/etva-isos;
%{__mkdir_p} $RPM_BUILD_ROOT%{_sysconfdir}/logrotate.d/
%{__mkdir_p} $RPM_BUILD_ROOT/var/log/etva_etvm
%{__mkdir_p} $RPM_BUILD_ROOT/var/run/etva_etvm

%{__mv} httpd_etvacm.conf $RPM_BUILD_ROOT%{_sysconfdir}/httpd/conf.d/httpd_etvacm.conf;
%{__mv} https_etvacm.conf $RPM_BUILD_ROOT%{_sysconfdir}/httpd/conf.d/https_etvacm.conf.disabled;

%{__mv} php_etva.ini $RPM_BUILD_ROOT%{_sysconfdir}/php.d/php_etva.ini; #set php timezone

%{__mv} etva-model.conf.smb $RPM_BUILD_ROOT%{_sysconfdir}/sysconfig/etva-model.conf;
%{__mv} etva-model.conf.ent $RPM_BUILD_ROOT%{_sysconfdir}/sysconfig/etva-model.conf.ent;
%{__mv} utils/cronjobs.conf $RPM_BUILD_ROOT%{_sysconfdir}/cron.d/etva
%{__mv} utils/cronjobs-ent.conf $RPM_BUILD_ROOT%{_sysconfdir}/cron.d/etva-ent
%{__mv} utils/cronjobs-smb.conf $RPM_BUILD_ROOT%{_sysconfdir}/cron.d/etva-smb
%{__mv} etva.service $RPM_BUILD_ROOT%{_sysconfdir}/avahi/services/
%{__mv} utils/pl/check_etvm.pl $RPM_BUILD_ROOT%{nagios_plugins}

%{__cp} -rf * $RPM_BUILD_ROOT/srv/etva-centralmanagement/;

install -D -p -m 0755 etva-queue-cm $RPM_BUILD_ROOT%{_sysconfdir}/init.d/etva-queue-cm

%{__rm} -f $RPM_BUILD_ROOT/srv/etva-centralmanagement/*.spec

find $RPM_BUILD_ROOT -name "\.svn" -depth -type d -exec rm -rf {} 2>/dev/null \;

%clean
rm -rf $RPM_BUILD_ROOT

#Comandos para executar depois da instalacao contem instrucoes diversas que sao executadas
#apos a instalacao do pacote no sistema (copia dos arquivos).
%post
# disable selinux
if [ -f /etc/selinux/config ]; then
    perl -npe 's/^SELINUX=.*/SELINUX=disabled/' -i /etc/selinux/config
fi

# fix date.timezone forced
if [ -f /etc/sysconfig/clock ]; then
    _TIMEZONE=`grep "ZONE" /etc/sysconfig/clock | sed -e 's/ZONE="\([^"]\+\)"/\1/'`;
    sed -i -e "s#^;\?date.timezone = .*#date.timezone = \"$_TIMEZONE\"#" /etc/php.d/php_etva.ini
fi

cd /srv/etva-centralmanagement

#Incluir no ficheiro de configuracao a versao do cm
_VERSION=`grep 'version:' apps/app/config/config.yml`;
_RELEASE=`grep 'release:' apps/app/config/config.yml`;

if [ "$_VERSION" == "" ]; then
    echo "  version: %{version}" >> apps/app/config/config.yml
else
    %{__perl} -pi -e 's/version:.*/version: %{version}/' apps/app/config/config.yml
fi

if [ "$_RELEASE" == "" ]; then
    echo "  release: %{release}" >> apps/app/config/config.yml 
else
    %{__perl} -pi -e 's/release:.*/release: %{release}/' apps/app/config/config.yml
fi

#####
# Generate key pair to access nodes via ssh
if [ ! -f '.ssh/id_dsa' ]; then
    ssh-keygen -t dsa -f .ssh/id_dsa -P "";
fi

/sbin/chkconfig httpd on
/sbin/chkconfig mysqld on
/sbin/chkconfig vsftpd on

#clear project cache
symfony cc;

# add Queue CM service
if [ ! -f "/var/lock/subsys/etva-queue-cm" ]; then
    /sbin/chkconfig --add etva-queue-cm
    /sbin/chkconfig etva-queue-cm on
    /sbin/service etva-queue-cm start
fi

if [ "$1" == "1" ]; then
	#echo "depois de instalar pela 1a vez (-i)..."
    # inicializa ligacao mysql e insere dados        
    if ! pidof mysqld > /dev/null; then
        echo "Starting MySQL"
        /sbin/service mysqld start
        mysql_run=0
    else
        echo "MySQL is running"
        mysql_run=1
    fi
    
    /usr/bin/php utils/init_db.php;
	# parsing do xml (fabforce)  e cria o schema da DB
	/usr/bin/symfony propel:db4-to-propel app
	# cria a bd
	/usr/bin/symfony propel:build-all --no-confirmation
	# compila os dados de teste
	/usr/bin/symfony propel:data-load data/fixtures/clean_data.yml
    # para mysql
    if [ $mysql_run == 0 ]; then
        echo "Stopping MySQL"
        /sbin/service mysqld stop
    fi
	#add etva ftp user to system script
	/usr/bin/php utils/add_user.php;
    #clear project cache after adding configuration parameters to etva
    symfony cc;

    # cria link simbolico para logrotate
    ln -sf /srv/etva-centralmanagement/config/logrotate/etva-cm.logrotate.conf /etc/logrotate.d/
    
    #adiciona a lista de sudoers o ficheiro php
    echo "apache  ALL=(ALL) NOPASSWD: /usr/bin/php -f /srv/etva-centralmanagement/utils/sudoexec.php" >> /etc/sudoers
    # apache does not require tty
    echo Defaults:apache \!requiretty >> /etc/sudoers
	%{__chmod} 777 /usr/share/etva-isos
	%{__rm} -rf /usr/share/etva-isos/.{bash,mozilla}*    
fi

%post smb
cd /srv/etva-centralmanagement
_ACRONYM=`grep 'acronym' apps/app/config/config.yml`;

if [ "$_ACRONYM" == "" ]; then
    echo "  acronym: UNITBOX" >> apps/app/config/config.yml 
else
    %{__perl} -pi -e 's/acronym:.*/acronym: UNITBOX/' apps/app/config/config.yml
fi

# change title
%{__perl} -pi -e 's/^\s+title:.*/    title: UnitBox - Eurotux/' apps/app/config/view.yml

if [ "$1" == "1" ]; then
	# inicializa ligacao mysql e insere dados
    if ! pidof mysqld > /dev/null; then
        echo "Starting MySQL"
        /sbin/service mysqld start
        mysql_run=0
    else
        echo "MySQL is running"
        mysql_run=1
    fi
	#insert etva data model info (file etva-model.conf) in DB
	/usr/bin/symfony etva:loadConf;
	# para mysql
    if [ $mysql_run == 0 ]; then
        echo "Stopping MySQL"
        /sbin/service mysqld stop
    fi
fi

# modify the etva-model.conf
echo -n " " >> /etc/sysconfig/etva-model.conf

%post ent
cd /srv/etva-centralmanagement

_ACRONYM=`grep 'acronym' apps/app/config/config.yml`;

if [ "$_ACRONYM" == "" ]; then
    echo "  acronym: NUXIS" >> apps/app/config/config.yml 
else
    %{__perl} -pi -e 's/acronym:.*/acronym: NUXIS/' apps/app/config/config.yml
fi

# change title
%{__perl} -pi -e 's/^\s+title:.*/    title: NUXIS/' apps/app/config/view.yml

if [ "$1" == "1" ]; then
	# insert nfs share
	echo "/usr/share/etva-isos       *(ro,no_root_squash,async)" > /etc/exports
	/sbin/chkconfig nfs on

    %{__cp} /etc/sysconfig/etva-model.conf.ent /etc/sysconfig/etva-model.conf

	# inicializa ligacao mysql e insere dados
    if ! pidof mysqld > /dev/null; then
        echo "Starting MySQL"
        /sbin/service mysqld start
        mysql_run=0
    else
        echo "MySQL is running"
        mysql_run=1
    fi	
	#insert etva data model info (file etva-model.conf) in DB
	/usr/bin/symfony etva:loadConf;
	# para mysql
    if [ $mysql_run == 0 ]; then
        echo "Stopping MySQL"
        /sbin/service mysqld stop
    fi
fi

# modify the etva-model.conf
echo -n " " >> /etc/sysconfig/etva-model.conf

%post https
if [ "$1" == "1" ]; then

    echo "[ req ]
prompt=no
distinguished_name     = req_distinguished_name
x509_extensions        = v3_server

[ req_distinguished_name ]
CN=$HOSTNAME
OU=Eurotux
emailAddress=tec@eurotux.com

[ v3_server ]
keyUsage=critical, digitalSignature, keyEncipherment
extendedKeyUsage=serverAuth
subjectKeyIdentifier=hash
authorityKeyIdentifier=keyid,issuer
" > /var/tmp/sslserver.conf;

    /usr/bin/openssl genrsa -out /etc/pki/tls/private/etva.key 1024;

    /usr/bin/openssl req -new -x509 -nodes -sha1 -days 365 -key /etc/pki/tls/private/etva.key -out /etc/pki/tls/certs/etva.crt -config /var/tmp/sslserver.conf -batch;

    %{__rm} -f /var/tmp/sslserver.conf;

    %{__mv} /etc/httpd/conf.d/https_etvacm.conf.disabled /etc/httpd/conf.d/https_etvacm.conf;

    cd /srv/etva-centralmanagement

    %{__mv} apps/app/config/security.yml apps/app/config/security.yml.http;
    %{__mv} apps/app/config/security.yml.https apps/app/config/security.yml;

    %{__mv} apps/app/config/filters.yml apps/app/config/filters.yml.http;
    %{__mv} apps/app/config/filters.yml.https apps/app/config/filters.yml;

    symfony cc

fi

#Comandos para executar antes da desinstalacao
%preun
if [ "$1" == "1" ]; then
   echo "antes de remover a versao antiga (-U)..."
elif [ "$1" == "0" ]; then
   echo "antes de remover definitivamente (-e)..."
    rm -rf /srv/etva-centralmanagement/plugins
    rm -rf /srv/etva-centralmanagement/lib
    rm -rf /srv/etva-centralmanagement/cache
    rm -rf /srv/etva-centralmanagement/log
fi

%files
%defattr(-,root,root)
#%doc AUTHORS CHANGES LICENSE README
/srv/etva-centralmanagement/
%attr(0777,apache,apache) /srv/etva-centralmanagement/web/uploads
%attr(0777,apache,apache) /srv/etva-centralmanagement/cache
%attr(0750,apache,apache) /srv/etva-centralmanagement/data/rra
%attr(0750,apache,apache) /srv/etva-centralmanagement/data
%attr(0777,apache,apache) /srv/etva-centralmanagement/log
%attr(0777,apache,apache) /srv/etva-centralmanagement/symfony
%attr(0777,apache,apache) /srv/etva-centralmanagement/web/uploads/assets
%attr(0755,apache,apache) /var/log/etva_etvm
%attr(0755,apache,apache) /var/run/etva_etvm

%config(noreplace) %{_sysconfdir}/httpd/conf.d/httpd_etvacm.conf
%config(noreplace) %{_sysconfdir}/php.d/php_etva.ini
%{_sysconfdir}/cron.d/etva
%{_sysconfdir}/avahi/services/etva.service
%{_sysconfdir}/init.d/etva-queue-cm

%config(noreplace) /srv/etva-centralmanagement/config/databases.yml
%config(noreplace) /srv/etva-centralmanagement/apps/app/config/config.yml

%files ent
%config(noreplace) %{_sysconfdir}/sysconfig/etva-model.conf
%config(noreplace) %{_sysconfdir}/sysconfig/etva-model.conf.ent
%{_sysconfdir}/cron.d/etva-ent

%files smb
%config(noreplace) %{_sysconfdir}/sysconfig/etva-model.conf
%{_sysconfdir}/cron.d/etva-smb

%files https
%config(noreplace) %{_sysconfdir}/httpd/conf.d/https_etvacm.conf.disabled

%files nrpe
%attr(0777,apache,apache) %{nagios_plugins}/check_etvm.pl

%changelog
* Tue Jan 10 2012 Manuel Dias <mfd@eurotux.com> 1.0
- Propel upgrade to 1.6. Database engine set to InnoDB.

* Tue Sep 27 2011 Carlos Rodrigues <cmar@eurotux.com> 0.7beta
- add HTTPS package

* Wed Dec 29 2010 Ricardo Gomes <rjg@eurotux.com> 0.6beta
- Changed from sqlite to mysql DB

* Wed Dec 22 2010 Ricardo Gomes <rjg@eurotux.com> 0.5beta
- Added php_etva.ini for php timezone

* Tue Oct 19 2010 Nuno Fernandes <npf@eurotux.com> 0.4beta
- Added etva.service to avahi

* Thu Aug 26 2010 Ricardo Gomes <rjg@eurotux.com> 0.3beta
- Added etva-model.conf to sysconfig
- Load etva-model.conf data to DB on build
- Added cronjobs

* Thu Jun 17 2010 Nuno Fernandes <npf@eurotux.com> 0.2beta
- Spec corrections

* Mon Jun 22 2009 Carlos Rodrigues <cmar@eurotux.com> 0.1beta
- Specfile created

