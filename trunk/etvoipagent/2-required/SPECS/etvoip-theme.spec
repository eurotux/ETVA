# npf@eurotux.com
Summary:	Eurotux Voip Theme
Name:		etvoip-theme
Version:	1.0
Release:	1%{?dist}
License:	GPL
Group:		Applications/System
Source0:	etvoip-theme.tgz
URL:		http://eurotux.com
Requires:	elastix-framework >= 2.4.0
BuildArch:	noarch
BuildRoot:	%{_tmppath}/%{name}-%{version}-root-%(id -u -n)

%description
Theme for elastix.

%prep

%build

%install
rm -rf $RPM_BUILD_ROOT
mkdir -p $RPM_BUILD_ROOT/var/www/html/themes
tar zxf %{SOURCE0} -C $RPM_BUILD_ROOT/var/www/html/themes

%clean
rm -rf $RPM_BUILD_ROOT

%files
%defattr(644,root,root,755)
/var/www/html/themes/*

%changelog
* Tue Jan 28 2014 Carlos Rodrigues <cmar@eurotux.com> 1.0-1
Theme updated for Elastix 2.4.0
* Fri Dec 23 2011 Nuno Fernandes <npf@eurotux.com> 1.0-0
Release 1
