#!/bin/bash -eu
# npf@eurotux.com
# the purpose of this script is to send all the rpms that were build to our development repository

ELREPO="el5"
PROJECTREPO="etva-devel"
if [ "$JOB_NAME" == "etva6-build" -o "$JOB_NAME" == "etva6-stable" ]; then
	ELREPO="el6"
fi
if [ "$JOB_NAME" == "etva-stable" -o "$JOB_NAME" == "etva6-stable" ]; then
	PROJECTREPO="etva-cr"
fi

LOCALPATH="../../repositorio-etva/x86_64/RPMS"
REMOTEUSER="root"
REMOTESERVER="etrepos.eurotux.com"
REMOTEPATH="/var/www/html/redhat/$ELREPO/en/x86_64/$PROJECTREPO/RPMS/"
PRIVATEKEY="/etc/pki/mock/id_dsa"

if [ ! -f $PRIVATEKEY ]; then
	echo "ssh private key not found. Please put private key in $PRIVATEKEY"
	exit
fi

scp -i $PRIVATEKEY $LOCALPATH/*rpm $REMOTEUSER@$REMOTESERVER:$REMOTEPATH
