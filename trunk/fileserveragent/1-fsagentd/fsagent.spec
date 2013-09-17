Name: etva-fsagent
Version: 1.2.2
Release: beta
Summary: File Server Agent
License: GPL
Group: Daemons
URL: http://www.eurotux.com
Source: etva-fsagent-%{version}-%{release}.tar.gz
#BuildRoot: %{_tmppath}/%{name}-%{version}-%{release}
BuildRoot: %{_tmppath}/%{name}
BuildArch: noarch

Requires:  virtagent-libs >= %{version}-%{release}

Requires: perl
Requires: daemontools
Requires: samba >= 3.6.9
Requires: logrotate
Requires: krb5-workstation

%description
ETVOIP Agent

%prep
%setup -q -n etva-fsagent-%{version}-%{release}

#%build

%install
rm -rf $RPM_BUILD_ROOT

mkdir -p $RPM_BUILD_ROOT%{_sysconfdir}/sysconfig/etva-fsagent
mkdir -p $RPM_BUILD_ROOT/srv/etva-fsagent

mkdir -p $RPM_BUILD_ROOT/service/etva-fsagent/supervise

cp fsagentd $RPM_BUILD_ROOT/srv/etva-fsagent/fsagentd
cp pkg_match.conf $RPM_BUILD_ROOT/srv/etva-fsagent/pkg_match.conf
cp fsagentd.conf $RPM_BUILD_ROOT%{_sysconfdir}/sysconfig/etva-fsagent/fsagentd.conf
cp -rf lib $RPM_BUILD_ROOT/srv/etva-fsagent/

cp service-run $RPM_BUILD_ROOT/service/etva-fsagent/run
chmod 755 $RPM_BUILD_ROOT/service/etva-fsagent/run
mkdir -p $RPM_BUILD_ROOT/service/etva-fsagent/log
echo -e '#!/bin/bash\nexec multilog t ./main' > $RPM_BUILD_ROOT/service/etva-fsagent/log/run
chmod 755 $RPM_BUILD_ROOT/service/etva-fsagent/log/run

mkdir -p $RPM_BUILD_ROOT%{_sysconfdir}/logrotate.d/
cp -rf logrotate-etva-fsagent $RPM_BUILD_ROOT%{_sysconfdir}/logrotate.d/etva-fsagent

mkdir -p $RPM_BUILD_ROOT/var/log/etva-fsagent
touch $RPM_BUILD_ROOT/var/log/etva-fsagent/fsagentd.log

find $RPM_BUILD_ROOT -name "\.svn" -depth -type d -exec rm -rf {} 2>/dev/null \;

%clean
rm -rf $RPM_BUILD_ROOT

%files
%defattr(-,root,root)
#%doc AUTHORS CHANGES LICENSE README
/srv/etva-fsagent
/service/etva-fsagent
/var/log/etva-fsagent/fsagentd.log
%{_sysconfdir}/logrotate.d/etva-fsagent
%config(noreplace) %{_sysconfdir}/sysconfig/etva-fsagent/fsagentd.conf

%changelog
* Thu Aug 22 2013 Carlos Rodrigues <cmar@eurotux.com> 1.2.2-beta
- Specfile created
