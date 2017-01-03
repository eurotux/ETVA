Name:		etvoip-endpoints-configuration
Version:	1.0
Release:	1%{?dist}
Summary:	Eurotux VoIP Endpoints configuration improvements

Group:		Applications/System
License:	GPL
URL:		http://eurotux.com
Source0:	endpoint_configuration_vendor_Linksys.patch
BuildArch:	noarch
BuildRoot:	%{_tmppath}/%{name}-%{version}-root-%(id -u -n)

Requires:	elastix-pbx >= 2.4.0-7

%description
Special configuration for some endpoints


%prep
#%setup -q


%build


%install
rm -rf $RPM_BUILD_ROOT
install -D -p -m 0644 %{SOURCE0} $RPM_BUILD_ROOT%{_tmppath}/endpoint_configuration_vendor_Linksys.patch

%clean
rm -rf $RPM_BUILD_ROOT


%post
patch -p0 --directory=/var/www/html < %{_tmppath}/endpoint_configuration_vendor_Linksys.patch 


%files
%{_tmppath}/endpoint_configuration_vendor_Linksys.patch



%changelog
* Mon Feb 17 2014 Carlos Rodrigues <cmar@eurotux.com> 1.0-1
Release 1


