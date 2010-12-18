Name: etva-centralmanagement
Version: 0.1
Release: beta
Summary: ETVA Central Management
License: GPL
Group: Applications/Web
URL: http://www.eurotux.com
Source: etva-centralmanagement-%{version}-%{release}.tar.gz
#BuildRoot: %{_tmppath}/%{name}-%{version}-%{release}
BuildRoot: %{_tmppath}/%{name}
BuildArch: noarch

Requires: php >= 5.2.9
Requires: httpd >= 2.2.11
Requires: symfony
Requires: sqlite >= 3.3.6
Requires: apr-util-sqlite
Requires: php-pear-soap >= 0.12
Requires: php-sqlite

%description
ETVA Central Management

%prep
%setup -q -n %{name}-%{version}-%{release}

#%build

%install
rm -rf $RPM_BUILD_ROOT;

mkdir -p $RPM_BUILD_ROOT/srv/etva-centralmanagement;
mkdir -p $RPM_BUILD_ROOT%{_sysconfdir}/httpd/conf.d/;

cp httpd_etvacm.conf $RPM_BUILD_ROOT%{_sysconfdir}/httpd/conf.d/httpd_etvacm.conf;

cp -rf * $RPM_BUILD_ROOT/srv/etva-centralmanagement/;

find $RPM_BUILD_ROOT -name "\.svn" -depth -type d -exec rm -rf {} 2>/dev/null \;

%clean
rm -rf $RPM_BUILD_ROOT

%post
cd /srv/etva-centralmanagement;

/usr/bin/symfony fix-perms;
/usr/bin/symfony cc;

chmod 777 /srv/etva-centralmanagement/data;
chmod 777 /srv/etva-centralmanagement/data/etva.db;

%files
%defattr(-,root,root)
#%doc AUTHORS CHANGES LICENSE README
/srv/etva-centralmanagement/*
%{_sysconfdir}/httpd/conf.d/httpd_etvacm.conf

%changelog
* Mon Jun 22 2009 Carlos Rodrigues <cmar@eurotux.com> 0.1beta
- Specfile created

