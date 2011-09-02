Name: etva-logos
Summary: ETVA-related icons and pictures.
Version: 4.10.99
Release: 11%{?dist}
Group: System Environment/Base
Source0: splash.xpm.gz
Source1: COPYING.etva

License: Copyright © 2010-2011 Eurotux Informaca S.A.  All rights reserved.
BuildRoot: %{_tmppath}/%{name}-root
BuildArchitectures: noarch
Conflicts: anaconda-images <= 10
Provides: system-logos redhat-logos

%description
The redhat-logos package (the "Package") contains files created by Eurotux
 to replace the Red Hat "Shadow Man" logo and  RPM logo.
The Red Hat "Shadow Man" logo, RPM, and the RPM logo are trademarks or
registered trademarks of Red Hat, Inc.

The Package and ETVA logos (the "Marks") can only used as outlined in
the included COPYING file. Please see that file for information on copying
and redistribution of the ETVA Marks.

%prep

%build

%install
rm -rf $RPM_BUILD_ROOT
mkdir -p $RPM_BUILD_ROOT/boot/grub
install -m 644 %{SOURCE0} $RPM_BUILD_ROOT/boot/grub/splash.xpm.gz
install -m 644 %{SOURCE1} .

%files
%defattr(-, root, root)
%doc COPYING.etva
/boot/grub/splash.xpm.gz
