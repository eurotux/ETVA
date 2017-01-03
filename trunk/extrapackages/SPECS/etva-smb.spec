Name: etva-smb
Summary: ETVA smb related files
Version: 2.2
Release: 11%{?dist}
Group: System Environment/Base
License: Copyright © 2010-2011 Eurotux Informatica S.A.  All rights reserved.
BuildRoot: %{_tmppath}/%{name}-root
Requires: lcdproc
Requires: lcdagent
BuildArch: noarch
Source0: LCDd.conf
Source1: lcdproc.conf
Requires(post): chkconfig

%description
ETVA smb related files and metapackage

%install
rm -rf $RPM_BUILD_ROOT
mkdir -p $RPM_BUILD_ROOT%{_sysconfdir}/sysconfig/lcdproc/
install -m 644 %{SOURCE0} $RPM_BUILD_ROOT%{_sysconfdir}/sysconfig/lcdproc/LCDd.conf.new
install -m 644 %{SOURCE1} $RPM_BUILD_ROOT%{_sysconfdir}/sysconfig/lcdproc/lcdproc.conf.new

%files
%{_sysconfdir}/sysconfig/lcdproc/*

%post
/sbin/chkconfig LCDd on
/sbin/chkconfig lcdproc on

if [ "$1" == "1" ]; then

    if [ ! -e "%{_sysconfdir}/sysconfig/lcdproc/LCDd.conf" ]; then
        cp -f %{_sysconfdir}/sysconfig/lcdproc/LCDd.conf.new %{_sysconfdir}/sysconfig/lcdproc/LCDd.conf
    fi

    if [ ! -e "%{_sysconfdir}/sysconfig/lcdproc/lcdproc.conf" ]; then
        cp -f %{_sysconfdir}/sysconfig/lcdproc/lcdproc.conf.new %{_sysconfdir}/sysconfig/lcdproc/lcdproc.conf
    fi

	# Modifica user e group do qemu
	%{__perl} -pi -e "s/^#?user = .*/user = \"root\"/" %{_sysconfdir}/libvirt/qemu.conf
	%{__perl} -pi -e "s/^#?group = .*/group = \"root\"/" %{_sysconfdir}/libvirt/qemu.conf

    # Alterar o shutdown dos guest e timeout
	%{__perl} -pi -e 's/#ON_BOOT=start/ON_BOOT=ignore/' %{_sysconfdir}/sysconfig/libvirt-guests
	%{__perl} -pi -e 's/#ON_SHUTDOWN=suspend/ON_SHUTDOWN=shutdown/' %{_sysconfdir}/sysconfig/libvirt-guests
	%{__perl} -pi -e 's/#SHUTDOWN_TIMEOUT=0/SHUTDOWN_TIMEOUT=120/' %{_sysconfdir}/sysconfig/libvirt-guests
fi

