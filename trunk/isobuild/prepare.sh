#!/bin/bash -eu
# -e: Exit immediately if a command exits with a non-zero status.
# -u: Treat unset variables as an error when substituting.

perl -pi -e "s/etva-build/$JOB_NAME/" etc/mock/etva-* etc/revisor/conf.d/revisor-*
perl -pi -e "s/etva6-build/$JOB_NAME/" etc/mock/etva-* etc/revisor/conf.d/revisor-*

# use devel repository in devel builds. See #419
if [ "$JOB_NAME" == "etva-build" ]; then
	echo "
[etva-devel]
name=ETVA Repository - devel
baseurl=http://etrepos.eurotux.com/redhat/el5/en/x86_64/etva-devel/
enabled=1
gpgcheck=0
gpgkey=
" >> etc/revisor/conf.d/revisor-el5-x86_64-updates.conf
	cat etc/mock/etva-5-x86_64.cfg | sed -e 's#\[groups\]#[etva-devel]\nname=ETVA Repository - devel branch\nbaseurl=http://etrepos.eurotux.com/redhat/el5/en/x86_64/etva-devel/\n\n\[groups\]#' > etc/mock/etva-5-x86_64.cfg.new && mv etc/mock/etva-5-x86_64.cfg.new etc/mock/etva-5-x86_64.cfg
	cat etc/mock/etva-5-x86_64.first.cfg | sed -e 's#\[groups\]#[etva-devel]\nname=ETVA Repository - devel branch\nbaseurl=http://etrepos.eurotux.com/redhat/el5/en/x86_64/etva-devel/\n\n\[groups\]#' > etc/mock/etva-5-x86_64.first.cfg.new && mv etc/mock/etva-5-x86_64.first.cfg.new etc/mock/etva-5-x86_64.first.cfg
elif [ "$JOB_NAME" == "etva6-build" ]; then
	echo "
[etva-devel]
name=ETVA Repository - devel
baseurl=http://etrepos.eurotux.com/redhat/el6/en/x86_64/etva-devel/
enabled=1
gpgcheck=0
gpgkey=
" >> etc/revisor/conf.d/revisor-el6-x86_64-updates.conf
	cat etc/mock/etva-6-x86_64.cfg | sed -e 's#\[local\]#[etva-devel]\nname=ETVA Repository - devel branch\nbaseurl=http://etrepos.eurotux.com/redhat/el6/en/x86_64/etva-devel/\n\n\[local\]#' > etc/mock/etva-6-x86_64.cfg.new && mv etc/mock/etva-6-x86_64.cfg.new etc/mock/etva-6-x86_64.cfg
	cat etc/mock/etva-6-x86_64.first.cfg | sed -e 's#\[local\]#[etva-devel]\nname=ETVA Repository - devel branch\nbaseurl=http://etrepos.eurotux.com/redhat/el6/en/x86_64/etva-devel/\n\n\[local\]#' > etc/mock/etva-6-x86_64.first.cfg.new && mv etc/mock/etva-6-x86_64.first.cfg.new etc/mock/etva-6-x86_64.first.cfg
fi

CENTOSVER=`cat /etc/redhat-release |sed -e 's/.*release //' -e 's/\..*//'`
if [ "$CENTOSVER" == "6" ]; then
	exit 0
fi

if [ "`whoami`" != "mock" ]; then
		echo "You must run the program with user mock"
		if [ ! -d /home/mock ]; then
			rpm -q mock > /dev/null 2>&1
			if [ "$?" != "0" ]; then
				yum -y install mock
			fi
			useradd mock -g mock
			echo "mock    ALL=(ALL)       NOPASSWD: ALL" >> /etc/sudoers
			echo Defaults:mock \!requiretty >> /etc/sudoers
		fi
		exit 1
fi

sudo -S id </dev/null > /dev/null 2> /dev/null
if [ "$?" != "0" ]; then
	echo "please add    \"mock    ALL=(ALL)       NOPASSWD: ALL\" to /etc/sudoers"
	exit 1
fi

rpm -q createrepo > /dev/null 2>&1
if [ "$?" != "0" ]; then
	sudo yum -y install createrepo
fi

if [ "$CENTOSVER" == "5" ]; then
	rpm -q squashfs-tools > /dev/null 2>&1
	if [ "$?" != "0" ]; then
		sudo yum -y install squashfs-tools
	fi
	rpm -q revisor > /dev/null 2>&1
	if [ "$?" != "0" ]; then
			sudo yum --enablerepo=epel -y install revisor mock rpm-build
	fi
	rpm -q popt.i386 > /dev/null 2>&1
	if [ "$?" != "0" ]; then
			sudo yum -y install popt.i386 popt
	fi
	rpm -q bzip2-libs.i386 > /dev/null 2>&1
	if [ "$?" != "0" ]; then
			sudo yum -y install bzip2-libs.i386 bzip2-libs
	fi
	sudo cp etc/revisor/conf.d/* /etc/revisor/conf.d/
	sudo su - -c "/usr/bin/system-config-securitylevel-tui -q --disabled --selinux='disabled'"
fi

sudo /sbin/service iptables stop > /dev/null 2>&1
