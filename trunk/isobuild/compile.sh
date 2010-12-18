#!/bin/bash

REPODIRTARGET=$HOME/repositorio-etva
PACKAGES="apr-1.3.3-4.src.rpm
apr-util-1.3.4-3.src.rpm
httpd-2.2.11-8.src.rpm
php-5.2.10-5.src.rpm
php-pear-channel-symfony-1.0-4.2.src.rpm
php-pear-soap-0.12.0-1.src.rpm
libvirt-0.6.*-1.src.rpm
perl-Sys-Virt-0.2.0-1.src.rpm
virtagent-*.src.rpm
parted-swig-0.1.20020731-1.src.rpm
perl-SOAP-0.28-1.src.rpm
symfony-1.2.4-1.2.src.rpm
etva-centralmanagement-*.src.rpm"

cd $HOME

[ -d $REPODIRTARGET ] || (
	mkdir -p $REPODIRTARGET/{i386,x86_64}/RPMS
	for arch in i386 x86_64; do
		cd $REPODIRTARGET/$arch
			createrepo .
		cd - > /dev/null
	done
)

[ -d logs ] || mkdir logs

# Initialize chroot
for arch in i386 x86_64; do
	mock -r etva-5-$arch.first init
	sleep 1
	# Increase php memory limit
	echo "mkdir -p /etc/php.d; echo memory_limit = 32M > /etc/php.d/memory.ini; echo 127.0.0.1 localhost localhost.localdomain `hostname` > /etc/hosts" | mock -r etva-5-$arch shell
done

for var in `echo $PACKAGES|xargs`; do
	for arch in i386 x86_64; do
		# Build packages
		echo "Building $var for $arch..."
		echo "Executing mock -r etva-5-$arch --no-clean rebuild SRPMS/$var"
		mock -r etva-5-$arch --no-clean rebuild SRPMS/$var > logs/$var.$arch.log 2>&1
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
			mock -r etva-5-$arch --no-clean rebuild SRPMS/$var > logs/$var.$arch.log 2>&1
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
