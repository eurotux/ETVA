#!/bin/bash -eu
# npf@eurotux.com
# the purpose of this script is to send all the rpms that were build to our development repository

if [ "$JOB_NAME" != "etva-build" -a "$JOB_NAME" != "etva6-build" ]; then
	# not a devel build so i'll bailout
	exit
fi

ELREPO="el5"
if [ "$JOB_NAME" == "etva6-build" ]; then
	ELREPO="el6"
fi

LOCALPATH="../../repositorio-etva/x86_64/RPMS"
REMOTEUSER="root"
REMOTESERVER="etrepos.eurotux.com"
REMOTEPATH="/var/www/html/redhat/$ELREPO/en/x86_64/etva-devel/RPMS/"
PRIVATEKEY="/etc/pki/mock/id_dsa"

if [ ! -f $PRIVATEKEY ]; then
	echo "ssh private key not found. Please put private key in $PRIVATEKEY"
	exit
fi

scp -i $PRIVATEKEY $LOCALPATH/*rpm $REMOTEUSER@$REMOTESERVER:$REMOTEPATH
