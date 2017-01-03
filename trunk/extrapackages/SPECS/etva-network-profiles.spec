Summary:    etva-network-profiles
Name:       etva-network-profiles
Version: 2.2
Release:    3%{?dist}
License:    GPL
Group:      Applications/System
Source0:    smb.tgz
Source1:    ent.tgz
URL:        http://www.eurotux.com/
Packager:   Nuno Fernandes <npf@eurotux.com>
BuildRoot:  %{_tmppath}/%{name}-%{version}-buildroot
BuildArch:  noarch
Requires:   initscripts rsync

%description
ETVA network files

%prep
tar zxf %{SOURCE0}
tar zxf %{SOURCE1}

%build

%install
rm -rf ${RPM_BUILD_ROOT}
mkdir -p ${RPM_BUILD_ROOT}/tmp
%{__mv} etc.smb ${RPM_BUILD_ROOT}/tmp/
%{__mv} etc.ent ${RPM_BUILD_ROOT}/tmp/

%clean
rm -rf ${RPM_BUILD_ROOT}

%files
%attr (-,root,root)
/tmp/*
