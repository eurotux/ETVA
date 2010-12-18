%define peardir %(pear config-get php_dir 2> /dev/null)
%define pear_xmldir  /var/lib/pear

Name:           php-pear-channel-symfony
Version:        1.0
Release:        4.2
Summary:        Adds pear.symfony-project.com channel to PEAR
Group:          Development/Languages
License:        MIT
URL:            http://pear.symfony-project.com/
Source0:        http://pear.symfony-project.com/channel.xml
BuildRoot:      %{_tmppath}/%{name}-%{version}-build
BuildArch:      noarch
BuildRequires:  php-pear
PreReq:         php php-pear

%description
This package adds the pear.symfony-project.com channel which allows PEAR packages
from this channel to be installed.


%prep
%setup -q -c -T


%build
# Empty build section, nothing to build


%install

%{__mkdir_p} %{buildroot}%{pear_xmldir}

%{__install} -pm 644 %{SOURCE0} %{buildroot}%{pear_xmldir}/pear.symfony-project.com.xml


%clean

%{__rm} -rf %{buildroot}


%post
if [ $1 -eq  1 ] ; then
   pear channel-add %{pear_xmldir}/pear.symfony-project.com.xml > /dev/null || :
else
   pear channel-update %{pear_xmldir}/pear.symfony-project.com.xml > /dev/null ||:
fi


%postun

if [ $1 -eq 0 ] ; then
   pear channel-delete pear.symfony-project.com > /dev/null || :
fi


%files
%defattr(-,root,root,-)
%{pear_xmldir}/*

