Summary: Xen Windows PVDrivers
Name: etva-xen-win
Version: 0.11.0
Release: 356
License: GPLv2
URL: http://wiki.univention.de/
Packager: Nuno Fernandes <npf@eurotux.com>
Group: Applications/System
Source0: gplpv_2000_signed_%{version}.%{release}.msi
Source1: gplpv_2003x64_signed_%{version}.%{release}.msi
Source2: gplpv_Vista2008x64_signed_%{version}.%{release}.msi
Source3: gplpv_2003x32_signed_%{version}.%{release}.msi
Source4: gplpv_Vista2008x32_signed_%{version}.%{release}.msi
Source5: gplpv_XP_signed_%{version}.%{release}.msi
Source6: EnvVarUpdate.nsh
Source7: nuxis-xen-version.nsh
Source8: nuxis-xen.nsi
BuildRoot: %{_tmppath}/%{name}-%{version}-%{release}-root
Requires: etva-centralmanagement
BuildRequires: mkisofs mingw32-nsis
BuildArch: noarch

%description
Windows Xen Drivers

%prep
%{__mkdir_p} cd/

%build
install %{SOURCE0} cd/
install %{SOURCE1} cd/
install %{SOURCE2} cd/
install %{SOURCE3} cd/
install %{SOURCE4} cd/
install %{SOURCE5} cd/
install %{SOURCE6} cd/
install %{SOURCE7} cd/
install %{SOURCE8} cd/
pushd cd
	makensis nuxis-xen.nsi
	rm -f gplpv*msi *nsh *nsi
popd
mkisofs -r -R -J -T -v -p "Eurotux Informatica S.A." -A "XEN GPLPv %{version}-%{release}" -o %{name}-%{version}-%{release}.iso cd/*
%{__rm} -rf cd/

%install
%{__mkdir_p} %{buildroot}/usr/share/etva-isos
%{__cp} %{name}-%{version}-%{release}.iso %{buildroot}/usr/share/etva-isos/

%clean
%{__rm} -rf $RPM_BUILD_DIR

%files
%defattr(-, root, root, 0755)
/usr/share/etva-isos/*

%changelog
* Tue Dec 20 2011 Nuno Fernandes <npf@eurotux.com> - 0.1-15-1
- Initial package.
