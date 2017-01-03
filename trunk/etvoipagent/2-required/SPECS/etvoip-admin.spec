Name:		etvoip-admin
Version:	1.0
Release:	3%{?dist}
Summary:	Eurotux VoIP admin modifications

Group:		Applications/System
License:	GPL
URL:		http://eurotux.com
Source0:	etvoip-admin.patch
Source1:    etvoip-admin-disa-messages.patch
Source2:    fix-admin-callerid-and-encryption-transport.patch
Source3:    fix-queues-retry-field-range.patch
BuildArch:	noarch
BuildRoot:	%{_tmppath}/%{name}-%{version}-root-%(id -u -n)

Requires:	freePBX >= 2.8.1-17

%description
Modifications of FreePBX admin module


%prep
#%setup -q


%build


%install
rm -rf $RPM_BUILD_ROOT

%{__mkdir_p} $RPM_BUILD_ROOT%{_tmppath}

install -D -p -m 0644 %{SOURCE0} $RPM_BUILD_ROOT%{_tmppath}/%{SOURCE0}
install -D -p -m 0644 %{SOURCE1} $RPM_BUILD_ROOT%{_tmppath}/%{SOURCE1}
install -D -p -m 0644 %{SOURCE2} $RPM_BUILD_ROOT%{_tmppath}/%{SOURCE2}
install -D -p -m 0644 %{SOURCE3} $RPM_BUILD_ROOT%{_tmppath}/%{SOURCE3}

%clean
rm -rf $RPM_BUILD_ROOT


%post
patch -p1 --directory=/var/www/html/admin < %{_tmppath}/%{SOURCE0}
patch -p1 --directory=/var/www/html/admin < %{_tmppath}/%{SOURCE1}
patch -p1 --directory=/var/www/html/admin < %{_tmppath}/%{SOURCE2}
patch -p1 --directory=/var/www/html/admin < %{_tmppath}/%{SOURCE3}

%files
%{_tmppath}/%{SOURCE0}
%{_tmppath}/%{SOURCE1}
%{_tmppath}/%{SOURCE2}
%{_tmppath}/%{SOURCE3}



%changelog
* Mon Oct 06 2014 Carlos Rodrigues <cmar@eurotux.com> 1.0-3
 add fix-queues-retry-field-range.patch

* Thu Jun 26 2014 Carlos Rodrigues <cmar@eurotux.com> 1.0-2
 add fix-admin-callerid-and-encryption-transport.patch modification

* Tue Feb 25 2014 Carlos Rodrigues <cmar@eurotux.com> 1.0-1
 Release 1


