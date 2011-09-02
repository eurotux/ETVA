# $Id: daemontools.spec 809 2007-06-15 20:00:31Z rpmbuild $
# repo: nonfree
# restrictions:

Summary: Tools for supervising and logging daemons
Name: daemontools
Version: 0.70
Release: 11
Epoch: 0
License: 17 USC 117
Group: System Environment/Daemons
URL: http://cr.yp.to/daemontools.html
Source0: http://cr.yp.to/daemontools/%{name}-%{version}.tar.gz
Source1: http://smarden.org/pape/djb/manpages/%{name}-%{version}-man.tar.gz
Source2: svscan.init.redhat
Source3: svscan.init.aix
Patch0: ftp://moni.csi.hu/pub/glibc-2.3.1/%{name}-%{version}.errno.patch
Patch1: %{name}-%{version}.tai64nlocal.patch
Patch2: http://www-dt.e-technik.uni-dortmund.de/~ma/djb/daemontools/%{name}-%{version}-fixlooping.patch
BuildRoot: %{_tmppath}/%{name}-%{version}-%{release}-root
NoSource: 0

%description
daemontools is a collection of tools for managing UNIX services. 

supervise monitors a service. It starts the service and restarts the
service if it dies. Setting up a new service is easy: all supervise needs
is a directory with a run script that runs the service. 

multilog saves error messages to one or more logs. It optionally timestamps
each line and, for each log, includes or excludes lines matching specified
patterns. It automatically rotates logs to limit the amount of disk space
used. If the disk fills up, it pauses and tries again, without losing any data.

%prep
%setup -q
%setup -q -D -T -a 1
%patch0
%patch1
%patch2 -p1

%build
echo "${RPM_BUILD_ROOT}%{_prefix}" > conf-home
%{__make}

%install
[ -n "$RPM_BUILD_ROOT" -a "$RPM_BUILD_ROOT" != / ] && %{__rm} -rf $RPM_BUILD_ROOT
%{__install} -m 0755 -d ${RPM_BUILD_ROOT}%{_bindir}
%{__install} -m 0755 -d ${RPM_BUILD_ROOT}%{_datadir}/doc
%{__make} setup
%{__install} -m 0755 -D %{SOURCE2} ${RPM_BUILD_ROOT}%{_initrddir}/svscan
%ifos aix4.3 aix5.1 aix5.2 aix5.3
%{__install} -m 0755 -D %{SOURCE3} ${RPM_BUILD_ROOT}%{_initrddir}/svscan
%endif
%{__install} -m 0755 -d ${RPM_BUILD_ROOT}%{_localstatedir}/services
%{__install} -m 0755 -d ${RPM_BUILD_ROOT}%{_localstatedir}/svscan
%{__install} -m 0755 -d ${RPM_BUILD_ROOT}%{_localstatedir}/log/svscan
cd %{name}-%{version}-man
%{__mv} README README.man
for X in 1 5 8; do
  for MAN in `ls *.$X`; do
    %{__install} -m 0644 -D $MAN ${RPM_BUILD_ROOT}%{_mandir}/man${X}/${MAN}
  done
done
cd ..

%clean
[ -n "$RPM_BUILD_ROOT" -a "$RPM_BUILD_ROOT" != / ] && %{__rm} -rf $RPM_BUILD_ROOT

%ifos aix4.3 aix5.1 aix5.2 aix5.3
%post
%{_sbindir}/chkconfig --add svscan

%preun
# Only if a last uninstall...
if [ "$1" = 0 ]; then
  %{_initrddir}/svscan stop > /dev/null 2>&1
  %{_sbindir}/chkconfig --del svscan
fi

%postun
# Only if we are upgrading...
if [ "$1" -ge "1" ]; then
  %{_initrddir}/svscan condrestart > /dev/null 2>&1
fi

%else

%post
/sbin/chkconfig --add svscan

%preun
# Only if a last uninstall...
if [ "$1" = 0 ]; then
  /sbin/service svscan stop > /dev/null 2>&1
  /sbin/chkconfig --del svscan
fi

%postun
# Only if we are upgrading...
if [ "$1" -ge "1" ]; then
  /sbin/service svscan condrestart > /dev/null 2>&1
fi
%endif

%files
%defattr(-,root,root)
%doc CHANGES README TODO %{name}-%{version}-man/README.man
%{_initrddir}/svscan
%{_bindir}/*
%doc %{_mandir}/*/*
%{_localstatedir}/services
%{_localstatedir}/svscan
%{_localstatedir}/log/svscan

%changelog
* Thu Jun 14 2007 RazorsEdge Packaging <rpmpackaging@razorsedge.org> 0.70-11
- Added condrestart to init scripts so package upgrades will work.

* Tue Apr 10 2007 RazorsEdge Packaging <rpmpackaging@razorsedge.org> 0.70-10
- Fixed init script error codes to be LSB compliant.

* Wed Jan 04 2006 RazorsEdge Packaging <rpmpackaging@razorsedge.org> 0.70-9
- Added AIX build support.

* Sun Dec 18 2005 RazorsEdge Packaging <rpmpackaging@razorsedge.org> 0.70-8
- Refactored.
- Rebuilt in new build system.

* Fri Apr 29 2005 RazorsEdge Packaging <rpmpackaging@razorsedge.org>
- Fixed %post to add the service, not set it to on.
- Added %preun and %postun.
- Changed Group and added Epoch.

* Wed Jun 25 2003 Mike Arnold <mike@razorsedge.org>
- Red Hat 9 now needs to #include <errno.h> in error.h

* Mon Apr 29 2002 Andy Dustman <andy@dustman.net>
- Red Hat now needs to #include <time.h> in tai64nlocal.c
- svscan init script fixes

* Thu Feb 08 2001 Andy Dustman <andy@dustman.net>
- Start svscan with /usr/local/bin in the path.
- Log svscan output to /dev/console.
- Add more Prefix: headers to fix relocation problems.

