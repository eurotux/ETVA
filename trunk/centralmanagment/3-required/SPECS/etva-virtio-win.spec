Summary: Perl module that implements for Version Objects
Name: etva-virtio-win
Version: 0.1
Release: 65
License: GPLv2
URL: http://linux-kvm.com/
Packager: Nuno Fernandes <npf@eurotux.com>
Group: Applications/System
Source: http://alt.fedoraproject.org/pub/alt/virtio-win/latest/images/bin/virtio-win-%{version}-%{release}.iso
Source1: NuxisGuestServer.exe
BuildRoot: %{_tmppath}/%{name}-%{version}-%{release}-root
Requires: etva-centralmanagement
BuildRequires: mkisofs xorriso
BuildArch: noarch

%description
Windows VirtIO Drivers

%prep
mkdir %{name}-%{version}-%{release}
pushd %{name}-%{version}-%{release}
	xorriso -osirrox on -indev %{SOURCE0} -extract / .
	find . -iname TRANS.TBL -exec rm -f {} \;
	mkdir guestagent
	cp %{SOURCE1} guestagent/
popd
mkisofs -r -R -J -p "Eurotux Informatica S.A." -A "KVM GPLPv %{version}-%{release}" -o %{name}-%{version}-%{release}.iso %{name}-%{version}-%{release}/

%build

%install
%{__mkdir_p} %{buildroot}/usr/share/etva-isos
%{__cp} %{name}-%{version}-%{release}.iso %{buildroot}/usr/share/etva-isos/

%clean
%{__rm} -rf %{buildroot}

%files
%defattr(-, root, root, 0755)
/usr/share/etva-isos/*

%changelog
* Mon Feb 11 2013 Carlos Rodrigues <cmar@eurotux.com> - 0.1-52-1
- Update virtio drivers to 0.1-52.

* Sun Jul 29 2012 Nuno Fernandes <npf@eurotux.com> - 0.1-30-1
- Update virtio drivers to 0.1-30.

* Fri Jun 08 2012 Nuno Fernandes <npf@eurotux.com> - 0.1-22-1
- Add guest tools.

* Tue Dec 20 2011 Nuno Fernandes <npf@eurotux.com> - 0.1-15-1
- Initial package.
