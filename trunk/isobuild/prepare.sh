#!/bin/bash

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

sudo /sbin/service iptables stop > /dev/null 2>&1
sudo su - -c "/usr/bin/system-config-securitylevel-tui -q --disabled --selinux='disabled'"
