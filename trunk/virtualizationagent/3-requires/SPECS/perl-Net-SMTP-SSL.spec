Name: perl-Net-SMTP-SSL
Version: 1.01
Release: 4%{?dist}
Summary: SSL support for Net::SMTP
Group: Development/Libraries
License: GPL+ or Artistic
URL: http://search.cpan.org/dist/Net-SMTP-SSL/
Source0: http://www.cpan.org/modules/by-module/Net/Net-SMTP-SSL-%{version}.tar.gz
BuildRoot: %{_tmppath}/%{name}-%{version}-%{release}-root-%(%{__id_u} -n)

BuildArch: noarch
BuildRequires: perl(ExtUtils::MakeMaker), perl(Test::More)
BuildRequires: perl(Net::SMTP)
BuildRequires: perl(IO::Socket::SSL)
Requires: perl(:MODULE_COMPAT_%(eval "`%{__perl} -V:version`"; echo $version))

%description
Implements the same API as Net::SMTP, but uses IO::Socket::SSL for its
network operations.

%prep
%setup -q -n Net-SMTP-SSL-%{version}

%build
%{__perl} Makefile.PL INSTALLDIRS=vendor
make %{?_smp_mflags}

%install
rm -rf $RPM_BUILD_ROOT
make %{?_smp_mflags} pure_install PERL_INSTALL_ROOT=$RPM_BUILD_ROOT
find $RPM_BUILD_ROOT -type f -name .packlist -exec rm -f {} ';'
find $RPM_BUILD_ROOT -depth -type d -exec rmdir {} 2>/dev/null ';'
chmod -R u+w $RPM_BUILD_ROOT

%check
make %{?_smp_mflags} test

%clean
rm -rf $RPM_BUILD_ROOT

%files
%defattr(-,root,root,-)
%doc README Changes
%dir %{perl_vendorlib}/Net/
%dir %{perl_vendorlib}/Net/SMTP/
%{perl_vendorlib}/Net/SMTP/SSL.pm
%{_mandir}/man3/Net::SMTP::SSL.3*

%changelog
* Mon Dec  7 2009 Stepan Kasal <skasal@redhat.com> - 1.01-4
- rebuild against perl 5.10.1

* Sun Jul 26 2009 Fedora Release Engineering <rel-eng@lists.fedoraproject.org> - 1.01-3
- Rebuilt for https://fedoraproject.org/wiki/Fedora_12_Mass_Rebuild

* Thu Feb 26 2009 Fedora Release Engineering <rel-eng@lists.fedoraproject.org> - 1.01-2
- Rebuilt for https://fedoraproject.org/wiki/Fedora_11_Mass_Rebuild

* Wed Oct 15 2008 Dan Nicholson <dbn.lists@gmail.com> 1.01-1
- Initial release
