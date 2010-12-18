Name:           virtagent
Version:        0.1
Release:        beta
Summary:        Virtualization Agent
License:        GPL
BuildArch:		noarch
Group:          Daemons
URL:            http://www.eurotux.com
Source:         virtagent-%{version}-%{release}.tar.gz
BuildRoot:      %{_tmppath}/%{name}-%{version}-%{release}

BuildRequires:  perl-Pod-Simple
BuildRequires:  perl-Pod-Simple-Wiki

Requires:  perl >= 5.6
Requires:  libvirt >= 0.6.1
Requires:  perl-Sys-Virt >= 0.2.0
Requires:  parted-swig
Requires:  daemontools
Requires:  perl-HTML-Parser
Requires:  perl-libwww-perl
Requires:  perl-SOAP-Lite
Requires:  perl-MIME-tools
Requires:  perl(IO::Select)
Requires:  perl(IO::Socket)
Requires:  perl(JSON)
Requires:  perl(JSON::XS)
Requires:  perl(Digest::MD5)
Requires:  perl(XML::Generator)
Requires:  perl(HTML::Entities)
Requires:  perl(LWP::UserAgent)
Requires:  perl(HTTP::Request)
Requires:  lvm2

%description
Virtualization Agent

%prep
%setup -q -n virtagent-%{version}-%{release}

#%build

%install
rm -rf $RPM_BUILD_ROOT

mkdir -p $RPM_BUILD_ROOT%{_sysconfdir}/sysconfig/etva-vdaemon
mkdir -p $RPM_BUILD_ROOT/srv/etva-vdaemon

mkdir -p $RPM_BUILD_ROOT/service/etva-vdaemon/supervise

cp virtd $RPM_BUILD_ROOT/srv/etva-vdaemon/virtd
cp virtd.sh $RPM_BUILD_ROOT/srv/etva-vdaemon/virtd.sh
cp virtClient.pl $RPM_BUILD_ROOT/srv/etva-vdaemon/virtClient.pl
cp -rf VirtAgent.conf $RPM_BUILD_ROOT%{_sysconfdir}/sysconfig/etva-vdaemon/virtd.conf
cp -rf VirtAgent $RPM_BUILD_ROOT/srv/etva-vdaemon/
cp -rf Agent $RPM_BUILD_ROOT/srv/etva-vdaemon/
cp -rf Client $RPM_BUILD_ROOT/srv/etva-vdaemon/
cp -rf *.pm $RPM_BUILD_ROOT/srv/etva-vdaemon/

cp service-run $RPM_BUILD_ROOT/service/etva-vdaemon/run
mkdir -p $RPM_BUILD_ROOT/service/etva-vdaemon/log
echo -e '#!/bin/bash\nexec multilog t ./main' > $RPM_BUILD_ROOT/service/etva-vdaemon/log/run
chmod 755 $RPM_BUILD_ROOT/service/etva-vdaemon/log/run

mkdir -p $RPM_BUILD_ROOT/var/log/etva-vdaemon

/bin/sh gendoc.sh

mkdir -p $RPM_BUILD_ROOT/usr/share/man/
cp -rf doc/man/ $RPM_BUILD_ROOT/usr/share/man/

find $RPM_BUILD_ROOT -name "\.svn" -depth -type d -exec rm -rf {} 2>/dev/null \;

%clean
rm -rf $RPM_BUILD_ROOT

%files
%defattr(-,root,root)
#%doc AUTHORS CHANGES LICENSE README
/srv/etva-vdaemon
/service/etva-vdaemon
%{_sysconfdir}/sysconfig/etva-vdaemon/virtd.conf
%{_mandir}
%doc doc/html doc/wiki

%changelog
* Thu May 22 2009 Carlos Rodrigues <cmar@eurotux.com> 0.1beta
- RPM Spec file changes
* Thu May 7 2009 Carlos Rodrigues <cmar@eurotux.com> 0.1beta
- Created by me

