%{!?rhel:%define rhel   %(cat /etc/redhat-release |sed -e 's/.*release //' -e 's/\..*//')}

Name:           virtagent
Version:        1.2.1
Release: 5751
Summary:        Virtualization Agent
License:        GPL
BuildArch:		noarch
Group:          Daemons
URL:            http://www.eurotux.com
Source:         virtagent-%{version}-%{release}.tar.gz
BuildRoot:      %{_tmppath}/%{name}-%{version}-%{release}

BuildRequires:  perl-Pod-Simple
BuildRequires:  perl-Pod-Simple-Wiki
BuildRequires:  perl(ExtUtils::MakeMaker)

Requires:  %{name}-libs = %{version}-%{release}

Requires:  perl >= 5.6
Requires:  libvirt >= 0.7.0
Requires:  parted-swig
Requires:  daemontools

Requires:  qemu-img >= 0.14

Requires:  samba-client
Requires:  nfs-utils

Requires:  perl-Sys-Virt >= 0.2.0
Requires:  perl-libwww-perl

Requires:  perl(IPC::SysV) >= 2.01
Requires:  perl(IPC::SharedMem)

Requires:  lvm2 >= 2.02.88
Requires:  e2fsprogs
Requires:  coreutils initscripts bridge-utils util-linux net-tools
Requires:  vconfig
Requires:  device-mapper-multipath
Requires:  system-config-network-tui
Requires:  avahi-tools
Requires:  gnutls-utils
Requires:  gpg
Requires:  perl-Mail-Sender
Requires:  sos
Requires:  pciutils
Requires:  libguestfs-tools
%if 0%{?rhel} == 6
Requires: perl-Class-Inspector
%endif

Requires:  logrotate
Requires:  which
Requires: /usr/bin/timeout

%description
Virtualization Agent

%package libs
Summary:        Virtualization Agent library and utilities
Group: Development/Libraries

Requires:  perl >= 5.6

Requires:  perl-HTML-Parser
Requires:  perl-libwww-perl
Requires:  perl-SOAP-Lite
Requires:  perl-MIME-tools
Requires:  perl-Package-Constants
Requires:  perl-Archive-Tar

Requires:  perl-Crypt-SSLeay

Requires:  perl(JSON)
Requires:  perl(JSON::XS)

Requires:  coreutils initscripts bridge-utils util-linux net-tools
Requires:  vconfig
Requires:  device-mapper-multipath
Requires:  system-config-network-tui
Requires:  avahi-tools
Requires:  gnutls-utils

%description libs
Libraries shared with others ETVA Agents

%prep
%setup -q -n virtagent-%{version}-%{release}

%build
(echo | %{__perl} Makefile.PL INSTALLDIRS="vendor" PREFIX="%{buildroot}%{_prefix}") || echo "ignore warnings"
%{__make} %{?_smp_mflags}

%install
rm -rf $RPM_BUILD_ROOT
%{__make} pure_install

### Clean up buildroot
find %{buildroot} -name .packlist -exec %{__rm} {} \;

mkdir -p $RPM_BUILD_ROOT%{_sysconfdir}/sysconfig/etva-vdaemon
mkdir -p $RPM_BUILD_ROOT/srv/etva-vdaemon

mkdir -p $RPM_BUILD_ROOT/service/etva-vdaemon/supervise

cp virtd $RPM_BUILD_ROOT/srv/etva-vdaemon/virtd
cp virtd.sh $RPM_BUILD_ROOT/srv/etva-vdaemon/virtd.sh
cp client.pl $RPM_BUILD_ROOT/srv/etva-vdaemon/client.pl

cp check.pl $RPM_BUILD_ROOT/srv/etva-vdaemon/check.pl
cp restart.pl $RPM_BUILD_ROOT/srv/etva-vdaemon/restart.pl
cp poweroff.pl $RPM_BUILD_ROOT/srv/etva-vdaemon/poweroff.pl

cp -rf VirtAgent.conf $RPM_BUILD_ROOT%{_sysconfdir}/sysconfig/etva-vdaemon/virtd.conf
mkdir -p $RPM_BUILD_ROOT/srv/etva-vdaemon/storage

mkdir -p $RPM_BUILD_ROOT%{_sysconfdir}/init.d
cp -rf etva-script $RPM_BUILD_ROOT%{_sysconfdir}/init.d/etva-script
mkdir -p $RPM_BUILD_ROOT%{_sysconfdir}/sysconfig/etva-vdaemon/config

rm -rf lib/ETVA
cp -rf lib $RPM_BUILD_ROOT/srv/etva-vdaemon/

cp change_cm.sh $RPM_BUILD_ROOT/srv/etva-vdaemon/change_cm.sh
cp script-upgrade.sh $RPM_BUILD_ROOT/srv/etva-vdaemon/script-upgrade.sh

mkdir -p $RPM_BUILD_ROOT/service/etva-vdaemon
cp service-run $RPM_BUILD_ROOT/service/etva-vdaemon/run
chmod 755 $RPM_BUILD_ROOT/service/etva-vdaemon/run
mkdir -p $RPM_BUILD_ROOT/service/etva-vdaemon/log
echo -e '#!/bin/bash\nexec multilog t ./main' > $RPM_BUILD_ROOT/service/etva-vdaemon/log/run
chmod 755 $RPM_BUILD_ROOT/service/etva-vdaemon/log/run

mkdir -p $RPM_BUILD_ROOT%{_sysconfdir}/logrotate.d/
cp -rf logrotate-etva-vdaemon $RPM_BUILD_ROOT%{_sysconfdir}/logrotate.d/etva-vdaemon

mkdir -p $RPM_BUILD_ROOT/var/log/etva-vdaemon
touch $RPM_BUILD_ROOT/var/log/etva-vdaemon/virtd.log

#/bin/sh gendoc.sh

#mkdir -p $RPM_BUILD_ROOT/usr/share/man/
#cp -rf doc/man/ $RPM_BUILD_ROOT/usr/share/man/

find $RPM_BUILD_ROOT -name "\.svn" -depth -type d -exec rm -rf {} 2>/dev/null \;

# remove *.pl from package ETVA

rm -rf %{buildroot}%{_mandir}/man3/client.3pm*
rm -rf %{buildroot}%{perl_vendorlib}/*.pl

%clean
rm -rf $RPM_BUILD_ROOT

%post

if [ "$1" == "1" ]; then
    /sbin/chkconfig --add etva-script;
    /sbin/chkconfig etva-script on;

    /sbin/chkconfig --add multipathd;
    /sbin/chkconfig multipathd on;

    echo "Para alterar o endereço do CentralManagement, corra o seguinte comando:";
    echo "    /bin/sh /srv/etva-vdaemon/change_cm.sh %{_sysconfdir}/sysconfig/etva-vdaemon/virtd.conf http://cm:8008/soapapi.php";
else 
    /bin/sh /srv/etva-vdaemon/script-upgrade.sh
fi

%files
%defattr(-,root,root)
#%doc AUTHORS CHANGES LICENSE README
/srv/etva-vdaemon
/service/etva-vdaemon
/var/log/etva-vdaemon/virtd.log
%{_sysconfdir}/init.d/etva-script
%{_sysconfdir}/sysconfig/etva-vdaemon/config
%{_sysconfdir}/logrotate.d/etva-vdaemon
%config(noreplace) %{_sysconfdir}/sysconfig/etva-vdaemon/virtd.conf
#%{_mandir}
#%doc doc/html doc/wiki

%files libs
%defattr(-,root,root)
#%doc AUTHORS CHANGES LICENSE README
%doc %{_mandir}/man3/*.3pm*
%{perl_vendorlib}/ETVA/

%changelog
* Thu May 22 2009 Carlos Rodrigues <cmar@eurotux.com> 0.1beta
- RPM Spec file changes
* Thu May 7 2009 Carlos Rodrigues <cmar@eurotux.com> 0.1beta
- Created by me

