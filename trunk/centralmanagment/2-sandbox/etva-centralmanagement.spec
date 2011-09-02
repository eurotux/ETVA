Name: etva-centralmanagement
Version: 0.2
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
Requires: system-config-network
Requires: sudo
Requires: mysql-server
Requires: mysql
Requires: apr-util-mysql
Requires: php-pear-SOAP >= 0.12
Requires: php-mysql
Requires: avahi
Requires: logrotate
Requires(post): chkconfig
BuildRequires: symfony

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
%{__mkdir_p} $RPM_BUILD_ROOT%{_sysconfdir}/httpd/conf.d/;
%{__mkdir_p} $RPM_BUILD_ROOT%{_sysconfdir}/php.d/;
%{__mkdir_p} $RPM_BUILD_ROOT%{_sysconfdir}/cron.d/
%{__mkdir_p} $RPM_BUILD_ROOT%{_sysconfdir}/sysconfig/
%{__mkdir_p} $RPM_BUILD_ROOT%{_sysconfdir}/avahi/services/
%{__mkdir_p} $RPM_BUILD_ROOT/usr/share/etva-isos;
%{__mkdir_p} $RPM_BUILD_ROOT%{_sysconfdir}/logrotate.d/

%{__mv} httpd_etvacm.conf $RPM_BUILD_ROOT%{_sysconfdir}/httpd/conf.d/httpd_etvacm.conf;
%{__mv} php_etva.ini $RPM_BUILD_ROOT%{_sysconfdir}/php.d/php_etva.ini; #set php timezone

%{__mv} etva-model.conf $RPM_BUILD_ROOT%{_sysconfdir}/sysconfig/etva-model.conf;
%{__mv} utils/cronjobs.conf $RPM_BUILD_ROOT%{_sysconfdir}/cron.d/etva
%{__mv} etva.service $RPM_BUILD_ROOT%{_sysconfdir}/avahi/services/

%{__cp} -rf * $RPM_BUILD_ROOT/srv/etva-centralmanagement/;

%{__rm} -f $RPM_BUILD_ROOT/srv/etva-centralmanagement/*.spec

find $RPM_BUILD_ROOT -name "\.svn" -depth -type d -exec rm -rf {} 2>/dev/null \;

%clean
rm -rf $RPM_BUILD_ROOT

#Comandos para executar depois da instalacao contem instrucoes diversas que sao executadas
#apos a instalacao do pacote no sistema (copia dos arquivos).
%post
cd /srv/etva-centralmanagement
/sbin/chkconfig httpd on
/sbin/chkconfig mysqld on
/sbin/chkconfig vsftpd on

#clear project cache
symfony cc;

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

%post ent
cd /srv/etva-centralmanagement
if [ "$1" == "1" ]; then
	# insert nfs share
	echo "/usr/share/etva-isos       *(ro,no_root_squash,async)" > /etc/exports
	/sbin/chkconfig nfs on
	echo -e "model=enterprise\nmastersite=http://10.10.10.53:7000/services\n\n[networks]\nbond0=Management" > /etc/sysconfig/etva-model.conf
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
%config(noreplace) %{_sysconfdir}/httpd/conf.d/httpd_etvacm.conf
%{_sysconfdir}/php.d/php_etva.ini
%config(noreplace) %{_sysconfdir}/cron.d/etva
%{_sysconfdir}/avahi/services/etva.service

%config(noreplace) /srv/etva-centralmanagement/config
%config(noreplace) /srv/etva-centralmanagement/apps/app/config/config.yml

%files ent
%config(noreplace) %{_sysconfdir}/sysconfig/etva-model.conf

%files smb
%config(noreplace) %{_sysconfdir}/sysconfig/etva-model.conf

%changelog
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

