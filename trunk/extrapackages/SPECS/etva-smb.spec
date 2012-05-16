Name: etva-smb
Summary: ETVA smb related files
Version: 1.0.1
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
install -m 644 %{SOURCE0} $RPM_BUILD_ROOT%{_sysconfdir}/sysconfig/lcdproc/
install -m 644 %{SOURCE1} $RPM_BUILD_ROOT%{_sysconfdir}/sysconfig/lcdproc/

%files
%{_sysconfdir}/sysconfig/lcdproc/*

%post
/sbin/chkconfig LCDd on
/sbin/chkconfig lcdproc on

if [ "$1" == "1" ]; then
    # Alterar o shutdown dos guest e timeout
	%{__perl} -pi -e 's/#ON_SHUTDOWN=suspend/ON_SHUTDOWN=shutdown/' %{_sysconfdir}/sysconfig/libvirt-guests
	%{__perl} -pi -e 's/#SHUTDOWN_TIMEOUT=0/SHUTDOWN_TIMEOUT=120/' %{_sysconfdir}/sysconfig/libvirt-guests
fi

