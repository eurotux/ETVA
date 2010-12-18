Name:           perl-Sys-Virt
Version:        0.2.0
Release:        1%{?dist}
Summary:        Represent and manage a libvirt hypervisor connection
License:        GPL
Group:          Development/Libraries
URL:            http://search.cpan.org/dist/Sys-Virt/
Source:        http://www.cpan.org/authors/id/D/DA/DANBERR/Sys-Virt-%{version}.tar.gz
BuildRoot:      %{_tmppath}/%{name}-%{version}-%{release}-root-%(%{__id_u} -n)
BuildRequires:  perl(Test::Pod) perl(XML::XPath)
BuildRequires:  perl(Test::Pod::Coverage)
BuildRequires:  libvirt-devel >= 0.1.1
# https://bugzilla.redhat.com/bugzilla/show_bug.cgi?id=202320
BuildRequires:  /usr/bin/pkg-config
%if %{!?fedora:0}%{?fedora} >= 6
BuildRequires:  xen-devel
# libvirt/xen are only available on these:
ExclusiveArch: i386 x86_64 ia64
%else
# libvirt/xen are only available on these:
ExclusiveArch: i386 x86_64
%endif
Requires:       perl(:MODULE_COMPAT_%(eval "`%{__perl} -V:version`"; echo $version))

%description
The Sys::Virt module provides a Perl XS binding to the libvirt virtual
machine management APIs. This allows machines running within arbitrary
virtualization containers to be managed with a consistent API.

%prep
%setup -q -n Sys-Virt-%{version}

sed -i -e '/Sys-Virt\.spec/d' Makefile.PL
sed -i -e '/\.spec\.PL$/d' MANIFEST
rm -f *.spec.PL

%build
%{__perl} Makefile.PL INSTALLDIRS=vendor OPTIMIZE="$RPM_OPT_FLAGS"
make %{?_smp_mflags}

%install
rm -rf $RPM_BUILD_ROOT

make pure_install PERL_INSTALL_ROOT=$RPM_BUILD_ROOT

find $RPM_BUILD_ROOT -type f -name .packlist -exec rm -f {} \;
find $RPM_BUILD_ROOT -type f -name '*.bs' -size 0 -exec rm -f {} \;
find $RPM_BUILD_ROOT -depth -type d -exec rmdir {} 2>/dev/null \;

chmod -R u+rwX,go+rX,go-w $RPM_BUILD_ROOT/*

%check
make test

%clean
rm -rf $RPM_BUILD_ROOT

%files
%defattr(-,root,root,-)
%doc AUTHORS CHANGES LICENSE README examples/
%{perl_vendorarch}/auto/*
%{perl_vendorarch}/Sys*
%{_mandir}/man3/*

%changelog
* Thu Apr 30 2009 Carlos Rodrigues <cmar@eurotux.com> 0.2.0-1
- Specfile from Steven Pritchard <steve@kspei.com> 0.1.1-6
- Remove Sys-Virt-Domain-doc.patch.
