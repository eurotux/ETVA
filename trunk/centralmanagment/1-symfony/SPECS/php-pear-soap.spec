%define peardir %(pear config-get php_dir 2> /dev/null)
%define pear_xmldir  /var/lib/pear

Name:           php-pear-soap
Version:        0.12.0
Release:        1
Summary:        SOAP Client/Server for PHP
Group:          Development/Languages
License:        PHP License 
URL:            http://pear.php.net/package/SOAP
#Source0:        http://download.pear.php.net/package/SOAP-0.12.0.tgz
BuildRoot:      %{_tmppath}/%{name}-%{version}-build
BuildArch:      noarch
BuildRequires:  php-pear
PreReq:         php php-pear

%description
Implementation of SOAP protocol and services

%prep
#%setup -q -c -T


%build
# Empty build section, nothing to build


%install

%{__mkdir_p} %{buildroot}%{pear_xmldir}

#%{__install} -pm 644 %{SOURCE0} %{buildroot}%{pear_xmldir}/pear.symfony-project.com.xml


%clean

%{__rm} -rf %{buildroot}


%post
#if [ $1 -eq  1 ] ; then
#   pear channel-add %{pear_xmldir}/pear.symfony-project.com.xml > /dev/null || :
#else
#   pear channel-update %{pear_xmldir}/pear.symfony-project.com.xml > /dev/null ||:
#fi
pear install SOAP-%{version}

%postun
pear uninstall SOAP-%{version}

#if [ $1 -eq 0 ] ; then
#   pear channel-delete pear.symfony-project.com > /dev/null || :
#fi

%files
%defattr(-,root,root,-)
#%{pear_xmldir}/*

