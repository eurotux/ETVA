Name: etva-enterprise
Summary: ETVA enterprise related files
Version: 1.2.1
Release: 11%{?dist}
Group: System Environment/Base
Source0: lvm.conf
Source1: multipath.conf
Source2: libvirt.cert.sh
Source3: libvirt_certs.tar.gz
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

    # Alterar o shutdown dos guest e timeout
	%{__perl} -pi -e 's/#ON_BOOT=start/ON_BOOT=ignore/' %{_sysconfdir}/sysconfig/libvirt-guests
	%{__perl} -pi -e 's/#ON_SHUTDOWN=suspend/ON_SHUTDOWN=shutdown/' %{_sysconfdir}/sysconfig/libvirt-guests
	%{__perl} -pi -e 's/#SHUTDOWN_TIMEOUT=0/SHUTDOWN_TIMEOUT=120/' %{_sysconfdir}/sysconfig/libvirt-guests

fi

%files
%defattr(-, root, root)
/tmp/*
%{_sysconfdir}/scripts/libvirt.cert.sh
