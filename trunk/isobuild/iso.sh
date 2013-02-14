#!/bin/bash -eu
# -e: Exit immediately if a command exits with a non-zero status.
# -u: Treat unset variables as an error when substituting.

CENTOSVER=`cat /etc/redhat-release |sed -e 's/.*release //' -e 's/\..*//'`
DIR=`dirname $0`
DEBUG=0

if [ "$DEBUG" == "1" ]; then
	MOCKDEBUG="-v"
else
	MOCKDEBUG=""
fi

if [ "$DIR" == "." ]; then
        DIR=`ls -la /proc/$$/cwd | awk {' print $11 '}`
fi
[ "$DIR" != "" ] || exit

MOCKCONF=$DIR/etc/mock
DIR=$DIR/../../
ISOFILE=$DIR/etva.iso
ETVMVER=`svn info . |grep URL|awk {'print $2'}|sed -e 's/.*etva\///' -e 's/\/isobuild//' -e 's/.*tags\///'`

mock --configdir=$MOCKCONF $MOCKDEBUG -r etva-$CENTOSVER-x86_64 init
if [ "$CENTOSVER" == "5" ]; then
	mock --configdir=$MOCKCONF $MOCKDEBUG -r etva-$CENTOSVER-x86_64 install revisor sudo
	mock --configdir=$MOCKCONF $MOCKDEBUG -r etva-$CENTOSVER-x86_64 --copyin stage2/rebrand.diff /tmp/
elif [ "$CENTOSVER" == "6" ]; then
	mock --configdir=$MOCKCONF $MOCKDEBUG -r etva-$CENTOSVER-x86_64 install revisor-cli sudo
fi
mock --configdir=$MOCKCONF $MOCKDEBUG -r etva-$CENTOSVER-x86_64 --copyin etva-ks$CENTOSVER.cfg /tmp/etva-ks.cfg
mock --configdir=$MOCKCONF $MOCKDEBUG -r etva-$CENTOSVER-x86_64 --copyin isolinux /tmp/isolinux/
mock --configdir=$MOCKCONF $MOCKDEBUG -r etva-$CENTOSVER-x86_64 --copyin comps/comps$CENTOSVER.xml /tmp/comps.xml
mock --configdir=$MOCKCONF $MOCKDEBUG -r etva-$CENTOSVER-x86_64 --copyin etc/revisor/revisor$CENTOSVER.conf /etc/revisor/revisor.conf
mock --configdir=$MOCKCONF $MOCKDEBUG -r etva-$CENTOSVER-x86_64 --copyin etc/revisor/conf.d/revisor-el$CENTOSVER-x86_64-updates.conf /etc/revisor/conf.d/revisor-el$CENTOSVER-x86_64-updates.conf
echo "mkdir -p /home/mock" | mock --configdir=$MOCKCONF $MOCKDEBUG -r etva-$CENTOSVER-x86_64 shell
mock --configdir=$MOCKCONF $MOCKDEBUG -r etva-$CENTOSVER-x86_64 --copyin ../../repositorio-etva /home/mock/repositorio-etva/
(
	for var in `seq 0 8`; do echo "mknod /dev/loop$var b 7 $var"; done
	echo "revisor --cli --model=el$CENTOSVER-x86_64-updates --yes --kickstart-default --kickstart-include --install-dvd --kickstart=/tmp/etva-ks.cfg"
) | mock --configdir=$MOCKCONF $MOCKDEBUG -r etva-$CENTOSVER-x86_64 shell || (
		echo "Error running revisor"
		exit -1
	)
mock --configdir=$MOCKCONF $MOCKDEBUG -r etva-$CENTOSVER-x86_64 --copyin isobuild$CENTOSVER.sh /tmp/isobuild.sh
(
	for var in `seq 0 8`; do echo "mknod /dev/loop$var b 7 $var"; done 
	echo "/tmp/isobuild.sh /srv/revisor/el$CENTOSVER-x86_64-updates/iso/*x86_64-DVD.iso $ETVMVER"
) | mock --configdir=$MOCKCONF $MOCKDEBUG -r etva-$CENTOSVER-x86_64 shell || (
	echo "Error remastering iso"
	exit -1
)
mock --configdir=$MOCKCONF $MOCKDEBUG -r etva-$CENTOSVER-x86_64 --copyout /etva.iso $ISOFILE
mock --configdir=$MOCKCONF $MOCKDEBUG -r etva-$CENTOSVER-x86_64 clean
