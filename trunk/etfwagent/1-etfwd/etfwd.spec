Name:           etva-etfw
Version: 2.2
Release:        beta
Summary:        ETFW Agent
License:        GPL
BuildArch:      noarch
Group:          Daemons
URL:            http://www.eurotux.com
Source:         etva-etfw-%{version}-%{release}.tar.gz
BuildRoot:      %{_tmppath}/%{name}-%{version}-%{release}

Requires:  virtagent-libs >= %{version}-%{release}

Requires: perl >= 5.6
Requires: perl-Config-IniFiles
Requires: webmin
Requires: iptables
Requires: net-tools
Requires: vconfig
Requires: initscripts
Requires: daemontools
Requires: perl-libwww-perl
Requires: logrotate

%description
ETFW Agent

%prep
%setup -q -n etva-etfw-%{version}-%{release}

#%build

%install
rm -rf $RPM_BUILD_ROOT

mkdir -p $RPM_BUILD_ROOT%{_sysconfdir}/sysconfig/etva-etfw
mkdir -p $RPM_BUILD_ROOT/srv/etva-etfw

mkdir -p $RPM_BUILD_ROOT/service/etva-etfw/supervise

cp etfwd $RPM_BUILD_ROOT/srv/etva-etfw/etfwd
cp pkg_match.conf $RPM_BUILD_ROOT/srv/etva-etfw/pkg_match.conf
cp etfwd.conf $RPM_BUILD_ROOT%{_sysconfdir}/sysconfig/etva-etfw/etfwd.conf
cp -rf lib $RPM_BUILD_ROOT/srv/etva-etfw/

cp service-run $RPM_BUILD_ROOT/service/etva-etfw/run
chmod 755 $RPM_BUILD_ROOT/service/etva-etfw/run
mkdir -p $RPM_BUILD_ROOT/service/etva-etfw/log
echo -e '#!/bin/bash\nexec multilog t ./main' > $RPM_BUILD_ROOT/service/etva-etfw/log/run
chmod 755 $RPM_BUILD_ROOT/service/etva-etfw/log/run

mkdir -p $RPM_BUILD_ROOT%{_sysconfdir}/logrotate.d/
cp -rf logrotate-etva-etfw $RPM_BUILD_ROOT%{_sysconfdir}/logrotate.d/etva-etfw

mkdir -p $RPM_BUILD_ROOT/var/log/etva-etfw
touch $RPM_BUILD_ROOT/var/log/etva-etfw/etfwd.log

#/bin/sh gendoc.sh
#mkdir -p $RPM_BUILD_ROOT/usr/share/man/
#cp -rf doc/man/ $RPM_BUILD_ROOT/usr/share/man/

find $RPM_BUILD_ROOT -name "\.svn" -depth -type d -exec rm -rf {} 2>/dev/null \;

%clean
rm -rf $RPM_BUILD_ROOT

%files
%defattr(-,root,root)
#%doc AUTHORS CHANGES LICENSE README
/srv/etva-etfw
/service/etva-etfw
/var/log/etva-etfw/etfwd.log
%{_sysconfdir}/logrotate.d/etva-etfw
%config(noreplace) %{_sysconfdir}/sysconfig/etva-etfw/etfwd.conf
#%{_mandir}
#%doc doc/html doc/wiki

%changelog
* Wed Sep 16 2009 Carlos Rodrigues <cmar@eurotux.com> 0.1-beta
- Created by me

