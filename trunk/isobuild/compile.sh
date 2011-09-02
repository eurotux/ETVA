#!/bin/bash

PACKAGES="apr-1.3.3-4.src.rpm
apr-util-1.3.4-3.src.rpm
httpd-2.2.11-8.src.rpm
php-5.2.10-5.src.rpm
php-pear-channel-symfony-1.0-4.2.src.rpm
php-pear-1.*.rpm
php-pear-Net-DIME-*.rpm
php-pear-SOAP-*.rpm
cmake-*.src.rpm
xmlrpc-c-*.src.rpm
etva-smb*.src.rpm
etva-enterprise*.src.rpm
etva-network-profiles*.src.rpm
etva-release*.src.rpm
anaconda-*.src.rpm
libxml2-*.src.rpm
libvirt-*.src.rpm
perl-Sys-Virt-*.src.rpm
perl-IPC-SysV-*.src.rpm
virtagent-*.src.rpm
lcdagent-*.src.rpm
parted-swig-0.1.20020731-1.src.rpm
perl-SOAP-Lite-*.src.rpm
symfony-1.*.src.rpm
etva-centralmanagement-*.src.rpm"

DIR=`dirname $0`
if [ "$DIR" == "." ]; then
        DIR=`ls -la /proc/$$/cwd | awk {' print $11 '}`
fi
[ "$DIR" != "" ] || exit

MOCKCONF=$DIR/etc/mock
DIR=$DIR/../../
REPODIRTARGET=$DIR/repositorio-etva
cd $DIR

mkdir -p $REPODIRTARGET/x86_64/RPMS 2> /dev/null
for arch in x86_64; do
	cd $REPODIRTARGET/$arch
		rm -rf RPMS/*
		createrepo .
	cd - > /dev/null
done

[ -d logs ] || mkdir logs

# Initialize chroot
for arch in x86_64; do
	mock --configdir=$MOCKCONF/ -r etva-5-$arch.first init
	sleep 1
	# Increase php memory limit
	echo "mkdir -p /etc/php.d; echo memory_limit = 64M > /etc/php.d/memory.ini; echo 127.0.0.1 localhost localhost.localdomain `hostname` > /etc/hosts" | mock --configdir=$MOCKCONF/ -r etva-5-$arch shell
done

for var in `echo $PACKAGES|xargs`; do
	for arch in x86_64; do
		# Build packages
		echo "Building $var for $arch..."
		echo "Executing mock --configdir=$MOCKCONF/ -r etva-5-$arch --no-clean rebuild SRPMS/$var"
		if [ "$arch" == "i386" ]; then
			SETARCH="setarch i386"
		else
			SETARCH=""
		fi
		$SETARCH mock --configdir=$MOCKCONF/ -r etva-5-$arch --no-clean installdeps SRPMS/$var > logs/$var.$arch.log 2>&1
		echo "perl -pi -e 's/memory_limit=16M/memory_limit=64M/' /usr/bin/pear" | mock --configdir=$MOCKCONF/ -r etva-5-$arch shell 2> /dev/null
		$SETARCH mock --configdir=$MOCKCONF/ -r etva-5-$arch --no-clean rebuild SRPMS/$var > logs/$var.$arch.log 2>&1
		# Move results to repodir and rebuild repodir
		if [ "$?" == "0" ]; then
			echo "Done."
			rm -f /var/lib/mock/etva-5-$arch/result/*.src.rpm
			mv /var/lib/mock/etva-5-$arch/result/*rpm $REPODIRTARGET/$arch/RPMS
			cd $REPODIRTARGET/$arch
				createrepo .
			cd - > /dev/null
		else
			sleep 5
			echo "perl -pi -e 's/memory_limit=16M/memory_limit=64M/' /usr/bin/pear" | mock --configdir=$MOCKCONF/ -r etva-5-$arch shell 2> /dev/null
			mock --configdir=$MOCKCONF/ -r etva-5-$arch --no-clean rebuild SRPMS/$var > logs/$var.$arch.log 2>&1
			if [ "$?" == "0" ]; then
				echo "Done."
				rm -f /var/lib/mock/etva-5-$arch/result/*.src.rpm
				mv /var/lib/mock/etva-5-$arch/result/*rpm $REPODIRTARGET/$arch/RPMS
				cd $REPODIRTARGET/$arch
					createrepo .
				cd - > /dev/null
			else
				echo "[ERR] Error building file. Please check logs/$var.$arch.log"
				exit -1
			fi
		fi
		sleep 5
	done
done
