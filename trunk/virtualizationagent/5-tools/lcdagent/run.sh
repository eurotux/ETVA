#!/bin/sh

LCDAGENT=`which lcdagent_event.pl 2>/dev/null`;
if [ "$LCDAGENT" == "" ]; then
    LCDAGENT="./lcdagent_event.pl"
fi

if [ "$LOG" == "" ]; then
    LOG="/dev/null"
fi

PERL_INC="/srv/etva-vdaemon";
CFG_FILE="$PREFIX/etc/sysconfig/lcdagent.ini" \
    /usr/bin/perl -I$PERL_INC $LCDAGENT >$LOG 2>&1

