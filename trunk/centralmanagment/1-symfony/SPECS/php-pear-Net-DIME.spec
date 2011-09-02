%{!?__pear: %{expand: %%global __pear %{_bindir}/pear}}
%define pear_name Net_DIME

# define beta RC1

Name:           php-pear-Net-DIME
Version:        1.0.1
Release:        3%{?dist}
Summary:        Implements Direct Internet Message Encapsulation (DIME)

Group:          Development/Libraries
License:        BSD
URL:            http://pear.php.net/package/Net_DIME
Source0:        http://pear.php.net/get/%{pear_name}-%{version}%{?beta}.tgz
Source2:        xml2changelog
BuildRoot:      %{_tmppath}/%{name}-%{version}-%{release}-root-%(%{__id_u} -n)

BuildArch:      noarch
BuildRequires:  php-pear >= 1:1.4.9-1.2
Requires:       php-pear(PEAR)
Requires(post): %{__pear}
Requires(postun): %{__pear}
Provides:       php-pear(%{pear_name}) = %{version}


%description
This is the initial independent release of the Net_DIME package.
Provides an implementation of "Direct Internet Message Encapsulation" (DIME) 
as defined at http://search.ietf.org/internet-drafts/draft-nielsen-dime-02.txt

Note : this specification has been superseded by the SOAP Message Transmission
Optimization Mechanism (MTOM) specification.

%prep
%setup -q -c
# package.xml is V2
%{_bindir}/php -n %{SOURCE2} package.xml >CHANGELOG
mv package.xml %{pear_name}-%{version}%{?beta}/%{pear_name}.xml

cd %{pear_name}-%{version}%{?beta}


%build
cd %{pear_name}-%{version}%{?beta}
# Empty build section, most likely nothing required.


%install
rm -rf $RPM_BUILD_ROOT docdir
cd %{pear_name}-%{version}%{?beta}
%{__pear} install --nodeps --packagingroot $RPM_BUILD_ROOT %{pear_name}.xml

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
%doc CHANGELOG
%{pear_xmldir}/%{pear_name}.xml
%{pear_phpdir}/Net
%{pear_testdir}/%{pear_name}


%changelog
* Sun Jul 26 2009 Fedora Release Engineering <rel-eng@lists.fedoraproject.org> - 1.0.1-3
- Rebuilt for https://fedoraproject.org/wiki/Fedora_12_Mass_Rebuild

* Thu Feb 26 2009 Fedora Release Engineering <rel-eng@lists.fedoraproject.org> - 1.0.1-2
- Rebuilt for https://fedoraproject.org/wiki/Fedora_11_Mass_Rebuild

* Fri Sep 05 2008 Remi Collet <Fedora@FamilleCollet.com> 1.0.1-1
- update to 1.0.1

* Fri Aug 29 2008 Remi Collet <Fedora@FamilleCollet.com> 1.0.0-2
- fix Source0

* Fri Aug 29 2008 Remi Collet <Fedora@FamilleCollet.com> 1.0.0-1
- update to 1.0.0
- Switched license to BSD License

* Fri Aug  8 2008 Remi Collet <Fedora@FamilleCollet.com> 1.0.0-0.1.RC1
- update to 1.0.0RC1
- fix LICENSE

* Mon Nov  6 2006 Remi Collet <Fedora@FamilleCollet.com> 0.3-1
- initial RPM (generated specfile + cleanup)
- add CHANGELOG and LICENSE
