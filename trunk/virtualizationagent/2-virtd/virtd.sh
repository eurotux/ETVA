#!/bin/sh

VIRTD=`which virtd 2>/dev/null`;
if [ "$VIRTD" == "" ]; then
    VIRTD="./virtd"
fi

PERL_INC="./lib";
CFG_FILE="$PREFIX/etc/sysconfig/etva-vdaemon/virtd.conf" \
    /usr/bin/perl -I$PERL_INC $VIRTD
