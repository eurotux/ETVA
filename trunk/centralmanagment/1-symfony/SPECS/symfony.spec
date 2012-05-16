#norootforbuild

#
# spec file for package symfony (Version 1.0.6)
#
# Copyright (c) 2007 SuSE Linux AG, Nuernberg, Germany.
# This file and all modifications and additions to the pristine
# package are under the same license as the package itself.
#
# Please submit bug fixes or comments via http://bugs.opensuse.org/
# NOTE
# If memory limit error occur increase php memory limit or check /usr/bin/pear for memory limit param
#

%define peardir %(pear config-get php_dir 2> /dev/null)
%define xmldir  /var/lib/pear

Summary: Symfony is a complete framework designed to optimize the development of web applications
Name: symfony
Version: 1.4.4
Release: 1.4
License: MIT license
Group: Development/Libraries/Other
BuildRoot: %{_tmppath}/%{name}-%{version}-build
URL: http://pear.symfony-project.com/package/symfony
BuildRequires: php-pear-channel-symfony
PreReq: php-pear-channel-symfony
Requires: php-gettext php-xsl php-tidy php-posix php-mbstring php-ctype php-tokenizer php-iconv php-mysql
%if 0%{?suse_version} > 1000
Recommends: php-xcache php-syck php-json
%endif
BuildArch: noarch

%description
Symfony is a complete framework designed to optimize the development of web
applications by way of several key features.
   For starters, it separates a web application's business rules, server
logic, and presentation views.
   It contains numerous tools and classes aimed at shortening the
development time of a complex web application.
   Additionally, it automates common tasks so that the developer can focus
entirely on the specifics of an application.
   The end result of these advantages means there is no need to reinvent
the wheel every time a new web application is built!

%prep

%setup -q -c -T
pear -v -c pearrc \
        -d php_dir=%{peardir} \
        -d doc_dir=/docs \
        -d bin_dir=%{_bindir} \
        -d data_dir=%{peardir}/data \
        -d test_dir=%{peardir}/tests \
        -s
pear -c pearrc download symfony/symfony-%{version}

%build
mkdir -p %{buildroot}/usr/share/pear/.channels/

%install
pear -c pearrc install --nodeps --packagingroot %{buildroot} symfony-%{version}.tgz

# Clean up unnecessary files
%{__rm} pearrc
%{__rm} -f %{buildroot}/%{peardir}/.filemap
%{__rm} -f %{buildroot}/%{peardir}/.lock
%{__rm} -rf %{buildroot}/%{peardir}/.registry
%{__rm} -rf %{buildroot}%{peardir}/.channels
%{__rm} %{buildroot}%{peardir}/.depdb
%{__rm} %{buildroot}%{peardir}/.depdblock
%{__mv} %{buildroot}/docs .

# Create apache conf file
%{__mkdir_p} %{buildroot}%{_sysconfdir}/httpd/conf.d/
echo "Alias /sf %{peardir}/data/symfony/web/sf" > %{buildroot}%{_sysconfdir}/httpd/conf.d/symfony.conf

# Install XML package description
%{__mkdir_p} %{buildroot}%{xmldir}
%{__tar} -xzf symfony-%{version}.tgz package.xml
%{__cp} -p package.xml %{buildroot}%{xmldir}/symfony.xml

%clean
%{__rm} -rf %{buildroot}

%post
pear install --nodeps --soft --force --register-only %{xmldir}/symfony.xml

%postun
if [ "$1" -eq "0" ]; then
    pear uninstall --nodeps --ignore-errors --register-only pear.symfony-project.com/symfony
fi

%files

%defattr(-,root,root)
%{_bindir}/symfony
%{_sysconfdir}/httpd/conf.d/symfony.conf
%doc docs/symfony/*
%{peardir}/*
%{xmldir}/symfony.xml
%changelog
* Mon Dec 24 2007 crrodriguez@suse.de
- update to version 1.0.10 mostly correcting some wrong assumptions
  in the code that caused breakeage on newer PHP versions.
* Tue Oct 23 2007 crrodriguez@suse.de
- update to version 1.0.8
* Fri Sep 14 2007 crrodriguez@suse.de
- version 1.0.7
- 4980: updated pake to 1.1.5 (-2125)
- r4956: fixed magic_quotes checks from the symfony command line closes (-2155)
- r4941: fixed sfPropelData doesn't use connection passed on data load (-2149)
- r4904: fixed dumping Propel data to multiple files
- r4891: fixed a typo in sfPostgreSQLSessionStorage::sessionGC()
- r4883: fixed fillInFormFilter can't find form with content_type = xml (-1687)
- r4834: fixed sfPropelData::dump() filenames when dumping to a directory
- r4831: fixed propel-dump-data does not preserve data loading order (-1575)
- r4829: fixed typo in sfPropelData
- r4827: fixed propel-dump-data & sfGuard (-2019)
- r4824: fixed propel-dump-data outputs model name when table is empty (-1577)
