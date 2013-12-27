name:           etva-etms
Version:        2.1.0
Release:        beta
Summary:        ETMS Agent
License:        GPL
BuildArch:      noarch
Group:          Daemons
URL:            http://www.eurotux.com
Source:         etva-etms-%{version}-%{release}.tar.gz
BuildRoot:      %{_tmppath}/%{name}-%{version}-%{release}

Requires:  virtagent-libs >= %{version}-%{release}

Requires: perl >= 5.6
Requires: perl-Config-IniFiles
Requires: webmin
Requires: daemontools
Requires: perl-libwww-perl
Requires: logrotate
Requires: etmailserver

%description
ETMS - Eurotux Mail Server Agent.

%prep
%setup -q -n etva-etms-%{version}-%{release}

#%build

%install
rm -rf $RPM_BUILD_ROOT

mkdir -p $RPM_BUILD_ROOT%{_sysconfdir}/sysconfig/etva-etms
mkdir -p $RPM_BUILD_ROOT/srv/etva-etms
cp etmsd $RPM_BUILD_ROOT/srv/etva-etms/etmsd
cp etmsd.conf $RPM_BUILD_ROOT/srv/etva-etms/
cp -rf lib $RPM_BUILD_ROOT/srv/etva-etms/
#cp pkg_match.conf $RPM_BUILD_ROOT/srv/etva-etms/pkg_match.conf
cp etmsd.conf $RPM_BUILD_ROOT%{_sysconfdir}/sysconfig/etva-etms/etmsd.conf

#deamon-tools
mkdir -p $RPM_BUILD_ROOT/service/etva-etms/supervise
cp service-run $RPM_BUILD_ROOT/service/etva-etms/run
chmod 755 $RPM_BUILD_ROOT/service/etva-etms/run
mkdir -p $RPM_BUILD_ROOT/service/etva-etms/log
echo -e '#!/bin/bash\nexec multilog t ./main' > $RPM_BUILD_ROOT/service/etva-etms/log/run
chmod 755 $RPM_BUILD_ROOT/service/etva-etms/log/run

#log rotate
mkdir -p $RPM_BUILD_ROOT%{_sysconfdir}/logrotate.d/
cp -rf logrotate-etva-etms $RPM_BUILD_ROOT%{_sysconfdir}/logrotate.d/etva-etms

mkdir -p $RPM_BUILD_ROOT/var/log/etva-etms
touch $RPM_BUILD_ROOT/var/log/etva-etms/etmsd.log

#/bin/sh gendoc.sh
#mkdir -p $RPM_BUILD_ROOT/usr/share/man/
#cp -rf doc/man/ $RPM_BUILD_ROOT/usr/share/man/

find $RPM_BUILD_ROOT -name "\.svn" -depth -type d -exec rm -rf {} 2>/dev/null \;

%clean
rm -rf $RPM_BUILD_ROOT

%files
%defattr(-,root,root)
#%doc AUTHORS CHANGES LICENSE README
/srv/etva-etms
/service/etva-etms
/var/log/etva-etms/etmsd.log
%{_sysconfdir}/logrotate.d/etva-etms
%config(noreplace) %{_sysconfdir}/sysconfig/etva-etms/etmsd.conf
#%{_mandir}
#%doc doc/html doc/wiki

%changelog
* Tue May 31 2011 Manuel Fortuna Dias <mfd@eurotux.com> 0.1-beta
- Created by me
