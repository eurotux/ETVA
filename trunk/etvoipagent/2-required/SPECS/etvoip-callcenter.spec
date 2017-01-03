Name:       etvoip-callcenter
Version:    1.0
Release:    5%{?dist}
Summary:    Eurotux VoIP Callcenter modifications

Group:      Applications/System
License:    GPL
URL:        http://eurotux.com
Source0:    etvoip-callcenter.tar.gz
Source1:    etvoip-callcenter.patch
Source2:    etvoip-callcenter.cron
Source3:    remove_break_cumulative_time.patch

BuildArch:  noarch
BuildRoot:  %{_tmppath}/%{name}-%{version}-root-%(id -u -n)

Requires:   elastix-callcenter >= 2.2.0-7

%description
Module Callcenter modifications of Panike costumer


%prep
%setup -q -n %{name}


%build


%install
rm -rf $RPM_BUILD_ROOT

%{__mkdir_p} $RPM_BUILD_ROOT%{_tmppath}

%{__mkdir_p} $RPM_BUILD_ROOT/etc/cron.d/

%{__mkdir_p} $RPM_BUILD_ROOT/var/www/cgi-bin/
%{__mkdir_p} $RPM_BUILD_ROOT/var/www/html/modules/agent_console/
%{__mkdir_p} $RPM_BUILD_ROOT/usr/local/sap2elastix/
%{__mkdir_p} $RPM_BUILD_ROOT/usr/local/asterisk-ldap-sync/

%{__cp} -rf cgi-bin/* $RPM_BUILD_ROOT/var/www/cgi-bin/
%{__cp} -rf html/modules/agent_console/* $RPM_BUILD_ROOT/var/www/html/modules/agent_console/
%{__cp} -rf sap2elastix/* $RPM_BUILD_ROOT/usr/local/sap2elastix/
%{__cp} -rf asterisk-ldap-sync/* $RPM_BUILD_ROOT/usr/local/asterisk-ldap-sync/

install -D -p -m 0644 %{SOURCE1} $RPM_BUILD_ROOT%{_tmppath}/etvoip-callcenter.patch
install -D -p -m 0644 %{SOURCE2} $RPM_BUILD_ROOT/etc/cron.d/etvoip-callcenter.cron
install -D -p -m 0644 %{SOURCE3} $RPM_BUILD_ROOT%{_tmppath}/remove_break_cumulative_time.patch

%clean
rm -rf $RPM_BUILD_ROOT

%post
patch -p1 -t --directory=/var/www/html < %{_tmppath}/etvoip-callcenter.patch
patch -p0 -t --directory=/var/www/html < %{_tmppath}/remove_break_cumulative_time.patch

if [ "$1" == "1" ]; then
    SAPUSER="sap"
    SAPPASS=`tr -dc A-Za-z0-9_ < /dev/urandom | head -c 8`

    mysql -u root /usr/local/sap2elastix/sap2elastix.sql
    if [ $? -ne 0 ]; then
        echo "Unable to access to MySQL for db sap2elastix creation"
        echo " and grant access to '$SAPUSER' db user on databases: sap2elastix, asterisk, asteriskcdrdb and call_center."
        echo " Please, access to MySQL with user root and run SQL script '/usr/local/sap2elastix/sap2elastix.sql'. "
        echo " After that, create '$SAPUSER' with access to the databases sap2elastix, asterisk, asteriskcdrdb and call_center and complete configuration file '/usr/local/sap2elastix/config.conf' with user name and password."
        exit(-1)
    fi

    mysql -u root -e "GRANT ALL ON sap2elastix.* TO $SAPUSER@localhost IDENTIFIED BY '$SAPPASS';"
    mysql -u root -e "GRANT ALL ON asterisk.* TO $SAPUSER@localhost IDENTIFIED BY '$SAPPASS';"
    mysql -u root -e "GRANT ALL ON asteriskcdrdb.* TO $SAPUSER@localhost IDENTIFIED BY '$SAPPASS';"
    mysql -u root -e "GRANT ALL ON call_center.* TO $SAPUSER@localhost IDENTIFIED BY '$SAPPASS';"

    sed -i "s/user: .*/user: $SAPUSER/; s/pass: .*/pass: $SAPPASS/" /usr/local/asterisk-ldap-sync/conf.yaml
fi

%files
%{_tmppath}/etvoip-callcenter.patch
%{_tmppath}/remove_break_cumulative_time.patch
/var/www/cgi-bin/
/var/www/html/modules/agent_console/images/agent.png
/var/www/html/modules/agent_console/images/call.png
/var/www/html/modules/agent_console/lang/pt.lang
/var/www/html/modules/agent_console/themes/default/css/elastix-callcenter-missedcalls.css
/var/www/html/modules/agent_console/themes/default/js/missedcalls.js
/var/www/html/modules/agent_console/themes/eurotux/
/usr/local/sap2elastix
/usr/local/asterisk-ldap-sync/
/etc/cron.d/etvoip-callcenter.cron

%changelog
* Tue Oct 14 2014 Carlos Rodrigues <cmar@eurotux.com> 1.0-5
 Fix patches updates

* Tue Oct 14 2014 Carlos Rodrigues <cmar@eurotux.com> 1.0-5
 Fix patch remove_break_cumulative_time.patch

* Tue Oct 14 2014 Carlos Rodrigues <cmar@eurotux.com> 1.0-3
 Update for elastix-callcenter-2.2.0-7

* Thu Jun 26 2014 Carlos Rodrigues <cmar@eurotux.com> 1.0-1
 Version Eurotux call center
    * add modification for don't use cumulative break time

* Mon Feb 17 2014 Carlos Rodrigues <cmar@eurotux.com> 1.0-1
 Release 1


