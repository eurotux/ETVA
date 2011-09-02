#!/bin/sh

file="$1";
url="$2";
bkpfile="$file.bkp";

if [ "$file" = "" ]; then
    file="/etc/sysconfig/etva-vdaemon/virtd.conf";
fi

if [ "$url" = "" ]; then
    read -e -p "Type URL of CentralManagement access: " url;
fi

if [ "$url" != "" ]; then
    /bin/cat $file | /bin/sed -e "s#^\#\?\s*cm_uri\s*=\s*.*#cm_uri = $url#" | /bin/sed -e 's/^#\?\s*\(cm_namespace\s*=\s*.\+\)/\1/' > $bkpfile;
    /bin/mv $bkpfile $file;
    echo "URL change to '$url'.";
else
    echo "URL not changed.";
fi

