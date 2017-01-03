#!/bin/bash -eu
# -e: Exit immediately if a command exits with a non-zero status.
# -u: Treat unset variables as an error when substituting.

CENTOSVER=`cat /etc/redhat-release |sed -e 's/.*release //' -e 's/\..*//'`

if [ "$CENTOSVER" == "5" ]; then
	. ./packages5
fi
if [ "$CENTOSVER" == "6" ]; then
	. ./packages6
fi

if [ "$PACKAGES" == "" ]; then
	echo "Error loading packages"
	exit -1
fi

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
	mock --configdir=$MOCKCONF/ -r etva-$CENTOSVER-$arch.first init
	sleep 1
	# Increase php memory limit
	echo "mkdir -p /etc/php.d; echo memory_limit = 128M > /etc/php.d/memory.ini; echo 127.0.0.1 localhost localhost.localdomain `hostname` > /etc/hosts" | mock --configdir=$MOCKCONF/ -r etva-$CENTOSVER-$arch shell
done

for var in `echo $PACKAGES|xargs`; do
	for arch in x86_64; do
		# Build packages
		echo "Building $var for $arch..."
		echo "Executing mock --configdir=$MOCKCONF/ -r etva-$CENTOSVER-$arch --no-clean rebuild SRPMS/$var"
		if [ "$arch" == "i386" ]; then
			SETARCH="setarch i386"
		else
			SETARCH=""
		fi
		if [ "echo $var | grep -i libvirt" == "" ]; then #libvirt hack
			$SETARCH mock --configdir=$MOCKCONF/ -r etva-$CENTOSVER-$arch --no-clean installdeps --define "rhel $CENTOSVER" SRPMS/$var > logs/$var.$arch.log 2>&1
		fi
		echo "perl -pi -e 's/memory_limit=16M/memory_limit=128M/' /usr/bin/pear" | mock --configdir=$MOCKCONF/ -r etva-$CENTOSVER-$arch shell 2> /dev/null
		$SETARCH mock --configdir=$MOCKCONF/ -r etva-$CENTOSVER-$arch --no-clean --define "rhel $CENTOSVER" rebuild SRPMS/$var > logs/$var.$arch.log 2>&1
		# Move results to repodir and rebuild repodir
		if [ "$?" == "0" ]; then
			echo "Done."
			echo "rm /builddir/build/SRPMS/*.src.rpm" | mock --configdir=$MOCKCONF/ -r etva-$CENTOSVER-$arch shell 2> /dev/null
			rm -f /var/lib/mock/etva-$CENTOSVER-$arch/result/*.src.rpm
			mv /var/lib/mock/etva-$CENTOSVER-$arch/result/*rpm $REPODIRTARGET/$arch/RPMS
			cd $REPODIRTARGET/$arch
				createrepo .
			cd - > /dev/null
		else
			sleep 5
			echo "perl -pi -e 's/memory_limit=16M/memory_limit=128M/' /usr/bin/pear" | mock --configdir=$MOCKCONF/ -r etva-$CENTOSVER-$arch shell 2> /dev/null
			mock --configdir=$MOCKCONF/ -r etva-$CENTOSVER-$arch --no-clean --define "rhel $CENTOSVER" rebuild SRPMS/$var > logs/$var.$arch.log 2>&1
			if [ "$?" == "0" ]; then
				echo "Done."
				echo "rm /builddir/build/SRPMS/*.src.rpm" | mock --configdir=$MOCKCONF/ -r etva-$CENTOSVER-$arch shell 2> /dev/null
				rm -f /var/lib/mock/etva-$CENTOSVER-$arch/result/*.src.rpm
				mv /var/lib/mock/etva-$CENTOSVER-$arch/result/*rpm $REPODIRTARGET/$arch/RPMS
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
