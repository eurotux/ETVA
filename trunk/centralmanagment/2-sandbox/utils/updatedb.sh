#!/bin/bash
echo $PATH;

CONFIGFILE='/etc/sysconfig/etva-model.conf';
SERVERDIR='/srv/etva-centralmanagement';

BACKUPFILE='/tmp/backup_'$(date +%Y_%m_%d)'.yml';
BACKUPFILEMODIFIED='/tmp/backup_'$(date +%Y_%m_%d)'_modified.yml';

BACKUPCONFIGDIR=$SERVERDIR'/data/schemas/curr';
CONFIGDIR=$SERVERDIR'/config';

cd $SERVERDIR;


applyschema(){
    # copy updated config
    local CONFIG=$SERVERDIR'/apps/app/config/app.yml';
    REQUIRED=`grep 'dbrequired' $CONFIG | tr -d '  dbrequired: '`;
    DIRECTORY=$SERVERDIR'/data/schemas/'$REQUIRED;
    if [ -d "$DIRECTORY" ]; then
        `cd data/schemas/1.0; find * | grep -v '/\.' | cpio -dump ../../../config/.; cd ../../../;`;
    else
        #rollback
        echo '[ERROR] Configuration copy failed. Please check dbrequired entry on app.yml!';
        exit 2;
    fi
    
    
    # apply required schema
    symfony propel:build-all --no-confirmation   
    if [ $? -ne 0 ]; then
        echo '[ERROR] Build all failed!';
        return 1;
    fi
   
    # restore database
    symfony propel:data-load $BACKUPFILEMODIFIED
    if [ $? -ne 0 ]; then
        echo '[ERROR] Data restore failed!';
        return 1;
    fi

    perl -pi -e "s/dbversion=.*/dbversion=$REQUIRED/" $CONFIGFILE;
    perl -pi -e "s#mastersite=.*#mastersite=http://etva-reg.eurotux.com/services#" $CONFIGFILE;
    return 0;
}

rollback(){
    # do rollback
    echo 'rollback';
    exit 0;
}

# stop centralmanagement
symfony project:disable prod
status=$?

if [ $status -ne 0 ]; then
    echo '[WARNING] Cannot stop symfony project';
fi
symfony cc


# backup current configuration (for rollback purposes)
TMP=$CONFIGDIR'/*';
cp -r $TMP $BACKUPCONFIGDIR;
status=$?

if [ $status -eq 0 ]; then
    echo '[INFO] Current configuration was successfully backed up.';
else
    echo '[ERROR] Cannot backup configuration. Process aborting!';
    exit 1;
fi


# backup database data
symfony propel:data-dump $BACKUPFILE
BKPRES=$?
cp $BACKUPFILE $BACKUPFILEMODIFIED;
CPRES=$?
if [ $BKPRES -ne 0 -o $CPRES -ne 0 ]; then
    echo '[ERROR] Cannot backup database! Process aborting.';
    exit 1;
else
    echo '[INFO] Backup successfully done: '$BACKUPFILE;
fi

if [[ -s $BACKUPFILE ]] ; then      # nel caso in cui 
    echo "[INFO] $BACKUPFILE has data.";
else
    echo "[ERROR] $BACKUPFILE is empty.";
    exit 1;
fi

# run updatedb command
symfony etva:updatedb $BACKUPFILEMODIFIED

if [ $? -eq 0 ]; then
    applyschema;
    if [ $? -ne 0 ]; then
        rollback;    
    fi
fi

# restart apache server
/etc/init.d/httpd restart

symfony project:enable prod
exit $?;

