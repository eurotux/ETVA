Name:           parted-swig
Version:        0.1.20020731
Release:        1%{?dist}
Summary:        bindings for libparted
License:        GPL
Group:          Development/Libraries
URL:            http://packages.ubuntu.com/source/dapper/parted-swig
Source:         parted-swig-%{version}.tar.gz
BuildRoot:      %{_tmppath}/%{name}-%{version}-%{release}-root-%(%{__id_u} -n)
BuildRequires:  parted >= 1.6.2
BuildRequires:  parted-devel >= 1.6.2
BuildRequires:  swig >= 1.3.13
BuildRequires:  gcc

%description
This is a SWIG source for creating libparted bindings for several languages.

%prep
%setup -q -n parted-swig-%{version}

%build
make

%install
rm -rf $RPM_BUILD_ROOT

#make pure_install PERL_INSTALL_ROOT=$RPM_BUILD_ROOT
make -C perl5 pure_install PERL_INSTALL_ROOT=$RPM_BUILD_ROOT

chmod -R u+rwX,go+rX,go-w $RPM_BUILD_ROOT/*

#%check
#make test

%clean
rm -rf $RPM_BUILD_ROOT

%files
#%defattr(-,root,root,-)
#%doc AUTHORS CHANGES LICENSE README examples/
%{perl_vendorarch}/auto/*
%{perl_vendorarch}/parted*
#%{_mandir}/man3/*

%changelog
* Tue May 5 2009 Carlos Rodrigues <cmar@eurotux.com> 0.1.20020731-2build2
- Create by me
