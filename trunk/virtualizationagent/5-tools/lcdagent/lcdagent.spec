Name:           lcdagent
Version:        2.1.0
Release: 4103
Summary:        LCD Agent
License:        GPL
BuildArch:		noarch
Group:          Daemons
URL:            http://www.eurotux.com
Source:         lcdagent-%{version}-%{release}.tar.gz
BuildRoot:      %{_tmppath}/%{name}-%{version}-%{release}

BuildRequires:  perl-Pod-Simple
BuildRequires:  perl-Pod-Simple-Wiki

Requires:  virtagent-libs >= %{version}-%{release}

Requires:  perl >= 5.6
Requires:  lcdproc >= 0.5.3
Requires:  perl(IO::Socket)
Requires:  perl(Event::Lib)
Requires:  perl(Filesys::Statvfs)
Requires:  perl(Config::IniFiles)
Requires:  perl(Digest::MD5)
Requires:  perl(IO::Handle)
Requires:  perl(HTML::Entities)
Requires:  perl(SOAP::Lite)
Requires:  system-config-network-tui
Requires:  dhclient
Requires:  coreutils initscripts bridge-utils util-linux net-tools
Requires:  virt-what

%description
LCD Agent

%prep
%setup -q -n lcdagent-%{version}-%{release}

#%build

%post
if [ "$1"x = "1"x ]; then
	/sbin/chkconfig --add lcdagent
	/sbin/chkconfig --level 3 lcdagent on
fi

%preun
if [ "$1"x = "0"x ]; then
	/sbin/chkconfig --del lcdagent
fi

%install
rm -rf $RPM_BUILD_ROOT

mkdir -p $RPM_BUILD_ROOT%{_sysconfdir}/sysconfig/lcdagent/
mkdir -p $RPM_BUILD_ROOT/srv/lcdagent/

mkdir -p $RPM_BUILD_ROOT/service/lcdagent/supervise

cp lcdagent_event.pl $RPM_BUILD_ROOT/srv/lcdagent/lcdagentd
#cp -rf *.pm $RPM_BUILD_ROOT/srv/lcdagent/

cp -rf lcdagent.ini $RPM_BUILD_ROOT%{_sysconfdir}/sysconfig/lcdagent/lcdagent.ini

mkdir -p $RPM_BUILD_ROOT/service/lcdagent
cp service-run $RPM_BUILD_ROOT/service/lcdagent/run
touch $RPM_BUILD_ROOT/service/lcdagent/down
mkdir -p $RPM_BUILD_ROOT/service/lcdagent/log
echo -e '#!/bin/bash\nexec multilog t ./main' > $RPM_BUILD_ROOT/service/lcdagent/log/run
chmod 755 $RPM_BUILD_ROOT/service/lcdagent/log/run

mkdir -p $RPM_BUILD_ROOT%{_sysconfdir}/logrotate.d/
cp -rf logrotate-lcdagent $RPM_BUILD_ROOT%{_sysconfdir}/logrotate.d/lcdagent

mkdir -p $RPM_BUILD_ROOT/var/log/lcdagent
touch $RPM_BUILD_ROOT/var/log/lcdagent/lcdagent.log

# Install startup scripts
mkdir -p $RPM_BUILD_ROOT%{_sysconfdir}/init.d/
cp -rf lcdagent.init $RPM_BUILD_ROOT%{_sysconfdir}/init.d/lcdagent

#/bin/sh gendoc.sh

#mkdir -p $RPM_BUILD_ROOT/usr/share/man/
#cp -rf doc/man/ $RPM_BUILD_ROOT/usr/share/man/

find $RPM_BUILD_ROOT -name "\.svn" -depth -type d -exec rm -rf {} 2>/dev/null \;

%clean
rm -rf $RPM_BUILD_ROOT

%files
%defattr(-,root,root)
#%doc AUTHORS CHANGES LICENSE README
/srv/lcdagent
/service/lcdagent
%dir /var/log/lcdagent
%ghost /var/log/lcdagent/lcdagent.log
%{_sysconfdir}/sysconfig/lcdagent/
%{_sysconfdir}/logrotate.d/lcdagent
%config(noreplace) %{_sysconfdir}/sysconfig/lcdagent/lcdagent.ini
#%{_mandir}
#%doc doc/html doc/wiki
%attr(0755,root,root) %{_sysconfdir}/init.d/lcdagent

%changelog
* Sun May 26 2013 Nuno Fernandes <npf@eurotux.com> 0.2
- ghost the log file. Add initscript. Post and pre scripts

* Thu Feb 24 2009 Carlos Rodrigues <cmar@eurotux.com> 0.1
- Created by me

