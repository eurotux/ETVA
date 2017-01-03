Name: etva-enterprise
Summary: ETVA enterprise related files
Version: 2.2
Release: 14%{?dist}
Group: System Environment/Base
Source0: lvm.conf
Source1: multipath.conf
Source2: libvirt.cert.sh
Source3: libvirt_certs.tar.gz
Source4: check-libvirt-certs.sh
License: Copyright © 2010-2011 Eurotux Informaca S.A.  All rights reserved.
BuildRoot: %{_tmppath}/%{name}-root
BuildArch: noarch
Requires: lvm2 device-mapper-multipath
Requires: gnutls libvirt
Requires(post): chkconfig perl

%description
Files to the enterprise version of etva.

%prep

%build

%install
rm -rf $RPM_BUILD_ROOT
mkdir -p $RPM_BUILD_ROOT/tmp
mkdir -p $RPM_BUILD_ROOT/%{_sysconfdir}/scripts/

install -m 644 %{SOURCE0} $RPM_BUILD_ROOT/tmp/
install -m 644 %{SOURCE1} $RPM_BUILD_ROOT/tmp/
install -m 755 %{SOURCE2} $RPM_BUILD_ROOT/%{_sysconfdir}/scripts/
install -m 644 %{SOURCE3} $RPM_BUILD_ROOT/tmp/
install -m 755 %{SOURCE4} $RPM_BUILD_ROOT/%{_sysconfdir}/scripts/

%post
if [ "$1" == "1" ]; then
    #echo "depois de instalar pela 1a vez (-i)..."

	mv /tmp/lvm.conf /etc/lvm/
	mv /tmp/multipath.conf /etc/

	#%{_sysconfdir}/scripts/libvirt.cert.sh
    ( cd /tmp ; tar xvzf libvirt_certs.tar.gz libvirt_certs/; cd libvirt_certs; /bin/sh ./libvirt_cp_certs.sh; cd ..; rm -rf libvirt_certs; rm -f libvirt_certs.tar.gz ) >/dev/null 2>&1
	echo "tls_no_verify_certificate = 1" >> %{_sysconfdir}/libvirt/libvirtd.conf
	# Modifica o libvirt para usar tls nas ligacoes
	%{__perl} -pi -e "s/^#listen_tls = 0/listen_tls = 1/" %{_sysconfdir}/libvirt/libvirtd.conf
	# Coloca o libvirt em listening mode
	%{__perl} -pi -e 's/#LIBVIRTD_ARGS="--listen"/LIBVIRTD_ARGS="--listen"/' %{_sysconfdir}/sysconfig/libvirtd

	# Modifica user e group do qemu
	%{__perl} -pi -e "s/^#?user = .*/user = \"root\"/" %{_sysconfdir}/libvirt/qemu.conf
	%{__perl} -pi -e "s/^#?group = .*/group = \"root\"/" %{_sysconfdir}/libvirt/qemu.conf

    # Alterar o shutdown dos guest e timeout
	%{__perl} -pi -e 's/#ON_BOOT=start/ON_BOOT=ignore/' %{_sysconfdir}/sysconfig/libvirt-guests
	%{__perl} -pi -e 's/#ON_SHUTDOWN=suspend/ON_SHUTDOWN=shutdown/' %{_sysconfdir}/sysconfig/libvirt-guests
	%{__perl} -pi -e 's/#SHUTDOWN_TIMEOUT=0/SHUTDOWN_TIMEOUT=120/' %{_sysconfdir}/sysconfig/libvirt-guests
fi

%{_sysconfdir}/scripts/check-libvirt-certs.sh

%files
%defattr(-, root, root)
/tmp/*
%{_sysconfdir}/scripts/libvirt.cert.sh
%{_sysconfdir}/scripts/check-libvirt-certs.sh

%changelog
* Mon Oct 20 2014 Carlos Rodrigues <cmar@eurotux.com> 2.1.1-14
- Check certificates scripts

* Fri Dec 20 2013 Carlos Rodrigues <cmar@eurotux.com> 2.0.0-13
- Overwrite lvm.conf and multipath.conf and ignore ATA vendors

* Fri Dec 20 2013 Carlos Rodrigues <cmar@eurotux.com> 2.0.0-12
- Don't overwrite lvm.conf and multipath.conf

