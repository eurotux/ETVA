%define builtin_release_name Final
%define product_family CentOS
%define base_release_version 5.8
%define builtin_release_variant Server
%define builtin_release_version %{base_release_version}
%define real_release_version %{?release_version}%{!?release_version:%{builtin_release_version}}
%define real_release_name %{?release_name}%{!?release_name:%{builtin_release_name}}
%define product_family Nuxis

%define current_arch %{_arch}
%ifarch i386
%define current_arch x86
%endif

Summary: %{product_family} release file
Name: nuxis-release
Epoch: 10
Version: 5
Release: 9.el5.nuxis
License: GPL
Group: System Environment/Base
Source: centos-release-%{builtin_release_version}.tar.gz
Source1: nuxis.repo

Obsoletes: rawhide-release redhat-release-as redhat-release-es redhat-release-ws redhat-release-de comps 
Obsoletes: rpmdb-redhat redhat-release whitebox-release fedora-release sl-release enterprise-release centos-release
Provides: centos-release redhat-release yumconf etva-release nuxis-release
Requires: centos-release-notes
Requires: epel-release eurotux-release

BuildRoot: %{_tmppath}/centos-release-root

%description
%{product_family} release files

%prep
%setup -q -n centos-release-%{builtin_release_version}

%build
python -c "import py_compile; py_compile.compile('eula.py')"

%install
rm -rf $RPM_BUILD_ROOT
mkdir -p $RPM_BUILD_ROOT/etc
echo "%{product_family} release %{base_release_version} (%{real_release_name})" > $RPM_BUILD_ROOT/etc/redhat-release
cp $RPM_BUILD_ROOT/etc/redhat-release $RPM_BUILD_ROOT/etc/issue
echo "Kernel \r on an \m" >> $RPM_BUILD_ROOT/etc/issue
cp $RPM_BUILD_ROOT/etc/issue $RPM_BUILD_ROOT/etc/issue.net
echo >> $RPM_BUILD_ROOT/etc/issue

mkdir -p $RPM_BUILD_ROOT/usr/share/firstboot/modules
cp eula.py* $RPM_BUILD_ROOT/usr/share/firstboot/modules

mkdir -p $RPM_BUILD_ROOT/usr/share/eula
cp eula.[!py]* $RPM_BUILD_ROOT/usr/share/eula

mkdir -p -m 755 $RPM_BUILD_ROOT/etc/sysconfig/rhn
install -m 644 sources $RPM_BUILD_ROOT/etc/sysconfig/rhn

mkdir -p -m 755 $RPM_BUILD_ROOT/etc/yum.repos.d
for file in CentOS*repo ; do
  install -m 644 $file $RPM_BUILD_ROOT/etc/yum.repos.d
done
install -m 644 %{SOURCE1} $RPM_BUILD_ROOT/etc/yum.repos.d

mkdir -p -m 755 $RPM_BUILD_ROOT/etc/pki/rpm-gpg
for file in RPM-GPG-KEY* ; do
        install -m 644 $file $RPM_BUILD_ROOT/etc/pki/rpm-gpg
done

%clean
rm -rf $RPM_BUILD_ROOT

%files
%defattr(-,root,root)
%attr(0644,root,root) /etc/redhat-release
%dir /etc/yum.repos.d
%config(noreplace) /etc/yum.repos.d/*
%doc EULA GPL autorun-template
%config(noreplace) %attr(0644,root,root) /etc/issue
%config(noreplace) %attr(0644,root,root) /etc/issue.net
/usr/share/firstboot/modules/eula.py*
/usr/share/eula/eula.*
%dir /etc/pki/rpm-gpg
/etc/pki/rpm-gpg/*
%dir /etc/sysconfig/rhn
%config(noreplace) /etc/sysconfig/rhn/sources

%changelog
* Wed May 16 2012 Nuno Fernandes <npf@eurotux.com> - 5-8.el5.nuxis
- Build for Nuxis

* Fri Feb 24 2012 Karanbir Singh <kbsingh@centos.org> - 5-8.el5.centos
- Build for CentOS-5.8

* Mon Aug 22 2011 Karanbir Singh <kbsingh@centos.org> - 5-7.el5.centos
- Build for CentOS-5.7

* Mon Jan 31 2011 Karanbir Singh <kbsingh@centos.org> - 5-6.el5.centos.1
- Build for CentOS-5.6
- Add Vault repos
- Add debuginfo repos

* Sun Apr 25 2010 Karanbir Singh <kbsingh@centos.org> - 5-5.el5.centos
- Build for CentOS-5.5

* Mon Sep 28 2009 Karanbir Singh <kbsingh@centos.org> - 5-4.el5.centos
- Build for CentOS-5.4

* Tue Mar 17 2009 Karanbir Singh <kbsingh@centos.org> - 5-3.el5.centos.1
- Change path to repo gpg key to point at filesystem instead of remote

* Sun Mar  1 2009 Karanbir Singh <kbsingh@centos.org> - 5-3.el5.centos
- Build for CentOS-5.3

* Fri Nov 23 2007 Karanbir Singh <kbsingh@centos.org> - 5.2.el5.centos
- add centos-release-disableContrib.patch
- add SOURCES/centos-release-Typo.patch
- Build for CentOS-5.1

* Fri Apr  6 2007 Karanbir Singh <kbsingh@centos.org> - 5.0.0.el5.centos.2
- Add Epoch to resolve (CentOS bug#1887)

* Sun Apr  1 2007 Karanbir Singh <kbsingh@centos.org> - 5.0.0.el5.centos.1
- Add /media/CentOS/ path to the CentOS-Media.repo 

* Thu Mar 29 2007 Karanbir Singh <kbsingh@centos.org> - 5-0.0.el5.centos
- Adapt for Final

* Sun Mar 11 2007 Karanbir Singh <kbsingh@centos.org> - 4.92.el5.centos.6
- Add Requires for centos-release-notes

* Fri Mar  9 2007 Karanbir Singh <kbsingh@centos.org> - 4.92.el5.centos.5
- rebuild

* Tue Mar  6 2007 Karanbir Singh <kbsingh@centos.org> - 4.92.el5.centos.4
- Patch and build for issue #1703

* Mon Feb 19 2007 Karanbir Singh <kbsingh@centos.org> - 4.92.el5.centos.3
- disable eula page
- modify repos for beta urls
- add rpm-gpg-keys

* Sat Feb 10 2007 Karanbir Singh <kbsingh@centos.org> - 4.92.el5.centos
- CentOS 5 beta  ( Release 4.92 )
