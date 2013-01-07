Name:		timeout
Version:	0.2
Release:	1%{?dist}
Summary:	Command timout
Buildroot:  %{_tmppath}/%{name}-root
Group:		System Environment/Base
License:	GPLv2
URL:		http://www.gnu.org/software/coreutils/
Source0:	timeout

BuildArch: x86_64

%description
This is package for command timeout

%prep
%build
%install
[ $RPM_BUILD_ROOT != / ] && rm -rf $RPM_BUILD_ROOT
%{__mkdir_p} $RPM_BUILD_ROOT/usr/bin
install -m 0755 %{SOURCE0} $RPM_BUILD_ROOT/usr/bin/

%clean
%{__rm} -rf $RPM_BUILD_DIR

%files
%defattr(-, root, root, 0755)
/usr/bin/timeout

%changelog
* Thu Jul 27 2012 Nuno Fernandes <npf@eurotux.com> - 0.2-1
- Correct package build.

* Fri Jul 20 2012 Carlos Rodrigues <cmar@eurotux.com> - 0.1-1
- Initial package.

