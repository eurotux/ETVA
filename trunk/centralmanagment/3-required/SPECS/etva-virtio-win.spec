Summary: Perl module that implements for Version Objects
Name: etva-virtio-win
Version: 0.1
Release: 15
License: GPLv2
URL: http://linux-kvm.com/
Packager: Nuno Fernandes <npf@eurotux.com>
Group: Applications/System
Source: http://alt.fedoraproject.org/pub/alt/virtio-win/latest/images/bin/virtio-win-%{version}-%{release}.iso
BuildRoot: %{_tmppath}/%{name}-%{version}-%{release}-root
Requires: etva-centralmanagement
BuildArch: noarch

%description
Windows VirtIO Drivers

%prep

%build

%install
%{__mkdir_p} %{buildroot}/usr/share/etva-isos
%{__cp} %{SOURCE0} %{buildroot}/usr/share/etva-isos/

%clean
%{__rm} -rf %{buildroot}

%files
%defattr(-, root, root, 0755)
/usr/share/etva-isos/*

%changelog
* Tue Dec 20 2011 Nuno Fernandes <npf@eurotux.com> - 0.1-15-1
- Initial package.
