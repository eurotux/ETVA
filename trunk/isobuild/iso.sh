#!/bin/bash -eu
# -e: Exit immediately if a command exits with a non-zero status.
# -u: Treat unset variables as an error when substituting.

CENTOSVER=`cat /etc/redhat-release |sed -e 's/.*release //' -e 's/\..*//'`
DIR=`dirname $0`
if [ "$DIR" == "." ]; then
        DIR=`ls -la /proc/$$/cwd | awk {' print $11 '}`
fi
[ "$DIR" != "" ] || exit

MOCKCONF=$DIR/etc/mock
DIR=$DIR/../../
ISOFILE=$DIR/etva.iso

if [ "$CENTOSVER" == "5" ]; then
        sudo /usr/sbin/revisor --cli --model=c5-x86_64 --yes --kickstart-default --kickstart-include --install-dvd --kickstart=${PWD}/etva-ks.cfg --config=etc/revisor/revisor.conf
	./isobuild.sh /srv/revisor/c5-x86_64/iso/CentOS-5-x86_64-DVD.iso
	sudo rm -rf /srv/revisor/c5-*/iso/*.iso 2> /dev/null
elif [ "$CENTOSVER" == "6" ]; then
        mock --configdir=$MOCKCONF -r etva-6-x86_64 init
	mock --configdir=$MOCKCONF -r etva-6-x86_64 install revisor-cli sudo
	mock --configdir=$MOCKCONF -r etva-6-x86_64 --copyin etva-ks6.cfg /tmp/etva-ks.cfg
	mock --configdir=$MOCKCONF -r etva-6-x86_64 --copyin isolinux /tmp/isolinux/
	mock --configdir=$MOCKCONF -r etva-6-x86_64 --copyin comps/comps6.xml /tmp/comps.xml
	mock --configdir=$MOCKCONF -r etva-6-x86_64 --copyin etc/revisor/revisor.conf /etc/revisor/revisor.conf
	mock --configdir=$MOCKCONF -r etva-6-x86_64 --copyin etc/revisor/conf.d/revisor-el6-x86_64-updates.conf /etc/revisor/conf.d/revisor-el6-x86_64-updates.conf
	echo "mkdir -p /home/mock" | mock --configdir=$MOCKCONF -r etva-6-x86_64 shell
	mock --configdir=$MOCKCONF -r etva-6-x86_64 --copyin ../../repositorio-etva /home/mock/repositorio-etva/
	echo "revisor --cli --model=el6-x86_64-updates --yes --kickstart-default --kickstart-include --install-dvd --kickstart=/tmp/etva-ks.cfg" | mock --configdir=$MOCKCONF -r etva-6-x86_64 shell || (
		echo "Error running revisor"
		exit -1
	)

	mock --configdir=$MOCKCONF -r etva-6-x86_64 --copyin isobuild6.sh /tmp/isobuild6.sh
	(
		for var in `seq 0 8`; do echo "mknod /dev/loop$var b 7 $var"; done 
		echo "/tmp/isobuild6.sh /srv/revisor/el6-x86_64-updates/iso/etvm-6-x86_64-DVD.iso;"
	) | mock --configdir=$MOCKCONF -r etva-6-x86_64 shell || (
		echo "Error remastering iso"
		exit -1
	)
	mock --configdir=$MOCKCONF -r etva-6-x86_64 --copyout /etva.iso $ISOFILE
	mock --configdir=$MOCKCONF -r etva-6-x86_64 clean
fi
