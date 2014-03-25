Name: etva-etvoip
Version: 2.1.1
Release: beta
Summary: ETVOIP Agent
License: GPL
Group: Daemons
URL: http://www.eurotux.com
Source: etva-etvoip-%{version}-%{release}.tar.gz
#BuildRoot: %{_tmppath}/%{name}-%{version}-%{release}
BuildRoot: %{_tmppath}/%{name}
BuildArch: noarch

Requires:  virtagent-libs >= %{version}-%{release}

Requires: perl
Requires: daemontools
Requires: freePBX = 2.7.0
Requires: elastix >= 2.0
Requires: perl-Asterisk-AMI
Requires: perl-PHP-Serialization
Requires: perl-version >= 0.88
Requires: logrotate
Conflicts: perl-version >= 0.7203

%description
ETVOIP Agent

%prep
%setup -q -n etva-etvoip-%{version}-%{release}

#%build

%install
rm -rf $RPM_BUILD_ROOT

mkdir -p $RPM_BUILD_ROOT%{_sysconfdir}/sysconfig/etva-etvoip
mkdir -p $RPM_BUILD_ROOT/srv/etva-etvoip

mkdir -p $RPM_BUILD_ROOT/service/etva-etvoip/supervise

cp etvoipd $RPM_BUILD_ROOT/srv/etva-etvoip/etvoipd
cp pkg_match.conf $RPM_BUILD_ROOT/srv/etva-etvoip/pkg_match.conf
cp etvoipd.conf $RPM_BUILD_ROOT%{_sysconfdir}/sysconfig/etva-etvoip/etvoipd.conf
cp -rf lib $RPM_BUILD_ROOT/srv/etva-etvoip/

cp service-run $RPM_BUILD_ROOT/service/etva-etvoip/run
chmod 755 $RPM_BUILD_ROOT/service/etva-etvoip/run
mkdir -p $RPM_BUILD_ROOT/service/etva-etvoip/log
echo -e '#!/bin/bash\nexec multilog t ./main' > $RPM_BUILD_ROOT/service/etva-etvoip/log/run
chmod 755 $RPM_BUILD_ROOT/service/etva-etvoip/log/run

mkdir -p $RPM_BUILD_ROOT%{_sysconfdir}/logrotate.d/
cp -rf logrotate-etva-etvoip $RPM_BUILD_ROOT%{_sysconfdir}/logrotate.d/etva-etvoip

mkdir -p $RPM_BUILD_ROOT/var/log/etva-etvoip
touch $RPM_BUILD_ROOT/var/log/etva-etvoip/etvoipd.log

find $RPM_BUILD_ROOT -name "\.svn" -depth -type d -exec rm -rf {} 2>/dev/null \;

%clean
rm -rf $RPM_BUILD_ROOT

%files
%defattr(-,root,root)
#%doc AUTHORS CHANGES LICENSE README
/srv/etva-etvoip
/service/etva-etvoip
/var/log/etva-etvoip/etvoipd.log
%{_sysconfdir}/logrotate.d/etva-etvoip
%config(noreplace) %{_sysconfdir}/sysconfig/etva-etvoip/etvoipd.conf

%changelog
* Thu May 12 2011 Ricardo Gomes <rjg@eurotux.com> 0.1beta
- Specfile created
