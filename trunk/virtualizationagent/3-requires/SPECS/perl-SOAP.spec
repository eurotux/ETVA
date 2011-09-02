Summary: Library for SOAP clients and servers in Perl
Name: perl-SOAP
Version: 0.28
Release: 1%{?dist}
License: GPL
Group: Development/Libraries
Source: SOAP-%{version}.tar.gz
BuildRoot: %{_tmppath}/%{name}-%{version}-%{release}-root
URL: http://search.cpan.org/~kbrown/SOAP-0.28/lib/SOAP.pm

BuildRequires: perl >= 5.6
BuildRequires: perl-XML-Parser
Requires: perl-libwww-perl
Requires: perl-XML-Parser

Conflicts: perl-SOAP-Lite

provides: perl(SOAP)
provides: perl(SOAP::Defs)
provides: perl(SOAP::Envelope)
provides: perl(SOAP::EnvelopeMaker)
provides: perl(SOAP::GenericHashSerializer)
provides: perl(SOAP::GenericInputStream)
provides: perl(SOAP::GenericScalarSerializer)
provides: perl(SOAP::OutputStream)
provides: perl(SOAP::Packager)
provides: perl(SOAP::Parser)
provides: perl(SOAP::Serializer)
provides: perl(SOAP::SimpleTypeWrapper)
provides: perl(SOAP::Struct)
provides: perl(SOAP::StructSerializer)
provides: perl(SOAP::Transport::HTTP::Apache)
provides: perl(SOAP::Transport::HTTP::CGI)
provides: perl(SOAP::Transport::HTTP::Client)
provides: perl(SOAP::Transport::HTTP::Server)
provides: perl(SOAP::TypeMapper)
provides: perl(SOAP::TypedPrimitive)
provides: perl(SOAP::TypedPrimitiveSerialize)

%description
SOAP/Perl is a collection of Perl modules which provides a simple
and consistent application programming interface (API) to the 
Simple Object Access Protocl (SOAP).

To learn more about SOAP, see
<URL:http://www.w3.org/TR/SOAP>

This library provides tools for you to build SOAP clients and servers.

The library contains modules for high-level use of SOAP, but also modules
for lower-level use in case you need something a bit more customized.

SOAP/Perl uses Perl's object oriented features exclusively. There are
no subroutines exported directly by these modules.

This version of SOAP/Perl supports a subset of the SOAP 1.1 specification,
which is an IETF internet draft. See <URL:http://www.ietf.org>
for details. See below for SOAP/Perl's major limitations.

%prep
%setup -q -n SOAP-%{version}

%build
%{__perl} Makefile.PL INSTALLDIRS=vendor OPTIMIZE="$RPM_OPT_FLAGS"
make %{?_smp_mflags}

%install
rm -fr %{buildroot}

make pure_install PERL_INSTALL_ROOT=$RPM_BUILD_ROOT

%check
echo "yes" | make test

%clean
rm -fr %{buildroot}

%files
%defattr(-, root, root)
%{perl_vendorarch}/auto/*
%{perl_vendorlib}/SOAP*
%{_mandir}/man3/*

%changelog
* Thu May 7 2009 Carlos Rodrigues <cmar@eurotux.com> - 0.28-1
- release 0.28
