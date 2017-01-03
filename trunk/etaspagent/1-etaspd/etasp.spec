name:           etva-etasp
Version: 2.2
Release:        beta
Summary:        ETASP Agent
License:        GPL
BuildArch:      noarch
Group:          Daemons
URL:            http://www.eurotux.com
Source:         etva-etasp-%{version}-%{release}.tar.gz
BuildRoot:      %{_tmppath}/%{name}-%{version}-%{release}

Requires:  virtagent-libs >= %{version}-%{release}

Requires: perl >= 5.6
Requires: perl-Config-IniFiles
Requires: daemontools
Requires: perl-libwww-perl
Requires: logrotate

%description
ETASP Agent.

%prep
%setup -q -n etva-etasp-%{version}-%{release}

#%build

%install
rm -rf $RPM_BUILD_ROOT

mkdir -p $RPM_BUILD_ROOT%{_sysconfdir}/sysconfig/etva-etasp
mkdir -p $RPM_BUILD_ROOT/srv/etva-etasp
cp etaspd $RPM_BUILD_ROOT/srv/etva-etasp/etaspd
cp etaspd.conf $RPM_BUILD_ROOT/srv/etva-etasp/
cp -rf lib $RPM_BUILD_ROOT/srv/etva-etasp/
#cp pkg_match.conf $RPM_BUILD_ROOT/srv/etva-etasp/pkg_match.conf
cp etaspd.conf $RPM_BUILD_ROOT%{_sysconfdir}/sysconfig/etva-etasp/etaspd.conf

#deamon-tools
mkdir -p $RPM_BUILD_ROOT/service/etva-etasp/supervise
cp service-run $RPM_BUILD_ROOT/service/etva-etasp/run
chmod 755 $RPM_BUILD_ROOT/service/etva-etasp/run
mkdir -p $RPM_BUILD_ROOT/service/etva-etasp/log
echo -e '#!/bin/bash\nexec multilog t ./main' > $RPM_BUILD_ROOT/service/etva-etasp/log/run
chmod 755 $RPM_BUILD_ROOT/service/etva-etasp/log/run

#log rotate
mkdir -p $RPM_BUILD_ROOT%{_sysconfdir}/logrotate.d/
cp -rf logrotate-etva-etasp $RPM_BUILD_ROOT%{_sysconfdir}/logrotate.d/etva-etasp

mkdir -p $RPM_BUILD_ROOT/var/log/etva-etasp
touch $RPM_BUILD_ROOT/var/log/etva-etasp/etaspd.log

#/bin/sh gendoc.sh
#mkdir -p $RPM_BUILD_ROOT/usr/share/man/
#cp -rf doc/man/ $RPM_BUILD_ROOT/usr/share/man/

find $RPM_BUILD_ROOT -name "\.svn" -depth -type d -exec rm -rf {} 2>/dev/null \;

%clean
rm -rf $RPM_BUILD_ROOT

%files
%defattr(-,root,root)
#%doc AUTHORS CHANGES LICENSE README
/srv/etva-etasp
/service/etva-etasp
/var/log/etva-etasp/etaspd.log
%{_sysconfdir}/logrotate.d/etva-etasp
%config(noreplace) %{_sysconfdir}/sysconfig/etva-etasp/etaspd.conf
#%{_mandir}
#%doc doc/html doc/wiki

%changelog
* Tue May 15 2012 Manuel Fortuna Dias <mfd@eurotux.com> 1.0
- Created by me
