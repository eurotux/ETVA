# $Id$
%define php_extdir %(php-config --extension-dir 2>/dev/null || echo %{_libdir}/php4)

Summary: PECL package for APC
Name: php-pecl-apc
Version: 3.1.9
Release: 1
Epoch: 1
License: PHP
Group: Development/Languages
URL: http://pecl.php.net/package/apc
Source: http://pecl.php.net/get/APC-%{version}.tgz
BuildRoot: %{_tmppath}/%{name}-%{version}-%{release}-root
Requires: php
BuildRequires: php, php-devel, pcre-devel
# Required by phpize
#BuildRequires: autoconf213, automake, libtool, gcc-c++

%description
APC is a free, open, and robust framework for caching and 
optimizing PHP intermediate code.

%prep
%setup -n APC-%{version}

%build
# Workaround for broken old phpize on 64 bits
%{__cat} %{_bindir}/phpize | sed 's|/lib/|/%{_lib}/|g' > phpize && sh phpize
%configure
%{__make} %{?_smp_mflags}

%install
%{__rm} -rf %{buildroot}
%{__make} install INSTALL_ROOT=%{buildroot}

# Drop in the bit of configuration
%{__mkdir_p} %{buildroot}%{_sysconfdir}/php.d
%{__mkdir_p} %{buildroot}%{_sysconfdir}/php.mod.d/extensions
%{__mkdir_p} %{buildroot}%{_sysconfdir}/php.cgi.d/extensions
%{__mkdir_p} %{buildroot}%{_sysconfdir}/php.cli.d/extensions

%{__cat} > %{buildroot}%{_sysconfdir}/php.mod.d/extensions/apc.ini << 'EOF'
; Enable apc extension
extension=apc.so
EOF
%{__cat} > %{buildroot}%{_sysconfdir}/php.cli.d/extensions/apc.ini << 'EOF'
; Enable apc extension
extension=apc.so
EOF
%{__cat} > %{buildroot}%{_sysconfdir}/php.cgi.d/extensions/apc.ini << 'EOF'
; Enable apc extension
extension=apc.so
EOF
%{__cat} > %{buildroot}%{_sysconfdir}/php.d/apc.ini << 'EOF'
; Enable apc extension
extension=apc.so
EOF

%clean
%{__rm} -rf %{buildroot}

%files
%defattr(-, root, root, 0755)
/usr/include/php/ext/apc/apc_serializer.h
%doc LICENSE CHANGELOG INSTALL NOTICE TECHNOTES.txt TODO
%config(noreplace) %{_sysconfdir}/php.mod.d/extensions/apc.ini
%config(noreplace) %{_sysconfdir}/php.cli.d/extensions/apc.ini
%config(noreplace) %{_sysconfdir}/php.cgi.d/extensions/apc.ini
%config(noreplace) %{_sysconfdir}/php.d/apc.ini
%{php_extdir}/apc.so

%changelog
* Thu Jul  9 2009 Clay Loveless <clay@killersoft.com>
- Initial release 3.1.2


