Name:		etvoip-language-pt
Version:	1.0
Release:	1%{?dist}
Summary:	Eurotux VoIP language PT package

Group:		Applications/System
License:	GPL
URL:		http://eurotux.com
Source0:	etvoip-language-pt.patch
BuildArch:	noarch
BuildRoot:	%{_tmppath}/%{name}-%{version}-root-%(id -u -n)

Requires:	elastix-framework >= 2.4.0

%description
Portuguese language package


%prep
#%setup -q


%build


%install
rm -rf $RPM_BUILD_ROOT
install -D -p -m 0644 %{SOURCE0} $RPM_BUILD_ROOT%{_tmppath}/etvoip-language-pt.patch

%clean
rm -rf $RPM_BUILD_ROOT


%post
patch -p1 --directory=/var/www/html < %{_tmppath}/etvoip-language-pt.patch


%files
%{_tmppath}/etvoip-language-pt.patch



%changelog
* Mon Feb 17 2014 Carlos Rodrigues <cmar@eurotux.com> 1.0-1
Release 1

