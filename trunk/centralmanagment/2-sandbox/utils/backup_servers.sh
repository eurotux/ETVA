#!/bin/sh

if [ "$BACKUP_LOCATION" == "" ]; then
    BACKUP_LOCATION="/var/tmp";
fi

echo "Backup servers to location $BACKUP_LOCATION";

/srv/etva-centralmanagement/symfony node:backup-servers $BACKUP_LOCATION
