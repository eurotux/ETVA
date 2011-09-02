%{!?__pear: %{expand: %%global __pear %{_bindir}/pear}}
%define pear_name SOAP

Name:           php-pear-SOAP
Version:        0.12.0
Release:        3%{?dist}
Summary:        Simple Object Access Protocol (SOAP) Client/Server for PHP

Group:          Development/Libraries
License:        PHP
URL:            http://pear.php.net/package/SOAP
Source0:        http://download.pear.php.net/package/%{pear_name}-%{version}.tgz
Source2:        xml2changelog
BuildRoot:      %{_tmppath}/%{name}-%{version}-%{release}-root-%(%{__id_u} -n)

BuildArch:      noarch
BuildRequires:  php-pear(PEAR) >= 1.5.4
Requires(post): %{__pear}
Requires(postun): %{__pear}
Requires:       php-pear(PEAR), php-pear(HTTP_Request)
Requires(hint): php-pear(Mail), php-pear(Mail_Mime), php-pear(Net_DIME)
Provides:       php-pear(%{pear_name}) = %{version}

%description
Implementation of Simple Object Access Protocol (SOAP) protocol and services.
 

%prep
%setup -q -c
# Package.xml is V2
%{_bindir}/php -n %{SOURCE2} package.xml >CHANGELOG
mv package.xml %{pear_name}-%{version}/%{pear_name}.xml

cd %{pear_name}-%{version}


%build
cd %{pear_name}-%{version}
# Empty build section, most likely nothing required.


%install
rm -rf $RPM_BUILD_ROOT docdir
cd %{pear_name}-%{version}
%{__pear} -d download_dir=/tmp install --nodeps --packagingroot $RPM_BUILD_ROOT %{pear_name}.xml

# Move documentation
mv $RPM_BUILD_ROOT%{pear_docdir}/%{pear_name} ../docdir


# Clean up unnecessary files
rm -rf $RPM_BUILD_ROOT%{pear_phpdir}/.??*

# Install XML package description
mkdir -p $RPM_BUILD_ROOT%{pear_xmldir}
install -pm 644 %{pear_name}.xml $RPM_BUILD_ROOT%{pear_xmldir}


%clean
rm -rf $RPM_BUILD_ROOT


%post
%{__pear} install --nodeps --soft --force --register-only \
    %{pear_xmldir}/%{pear_name}.xml >/dev/null || :

%postun
if [ $1 -eq 0 ] ; then
    %{__pear} uninstall --nodeps --ignore-errors --register-only \
        %{pear_name} >/dev/null || :
fi


%files
%defattr(-,root,root,-)
%doc CHANGELOG docdir/*

%{pear_xmldir}/%{pear_name}.xml
%{pear_phpdir}/SOAP


%changelog
* Sun Jul 26 2009 Fedora Release Engineering <rel-eng@lists.fedoraproject.org> - 0.12.0-3
- Rebuilt for https://fedoraproject.org/wiki/Fedora_12_Mass_Rebuild

* Thu Feb 26 2009 Fedora Release Engineering <rel-eng@lists.fedoraproject.org> - 0.12.0-2
- Rebuilt for https://fedoraproject.org/wiki/Fedora_11_Mass_Rebuild

* Tue Aug 05 2008 Remi Collet <Fedora@FamilleCollet.com> 0.12.0-1
- update to 0.12.0
- fix license
- BR on pear >= 1.5.4

* Mon Jul 02 2007 Remi Collet <Fedora@FamilleCollet.com> 0.11.0-1
- update to 0.11.0

* Sat Jan 27 2007 Remi Collet <Fedora@FamilleCollet.com> 0.10.1-1
- update to 0.10.1

* Mon Jan 22 2007 Remi Collet <Fedora@FamilleCollet.com> 0.10.0-1
- update to 0.10.0

* Mon Nov  6 2006 Remi Collet <Fedora@FamilleCollet.com> 0.9.4-1
- initial RPM (generated specfile + cleanup)
- add CHANGELOG and LICENSE
