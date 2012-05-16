#!/bin/bash
# This script generates a tarball with the ETVA/ETMV configuration.
#####


BACKUPFILE='/tmp/backup_'$(date +%Y_%m_%d)'.yml';
SERVERDIR='/srv/etva-centralmanagement';
ALRTLOGS='/var/log/etva_etvm/*.log';
ALRTALRT='/var/run/etva_etvm';
CMLOGDIR=$SERVERDIR'/log/*.log';
APPCONFIG=$SERVERDIR'/apps/app/config';
PROJECTCONFIG=$SERVERDIR'/config';
MODELCONFIG='/etc/sysconfig/etva-model.conf';
PHPINI='/etc/php.ini';

#####
## AUTO VARIABLES
AGENTSDIR=$SERVERDIR'/data/backup'
CMDB=$SERVERDIR'/data/fixtures/backup'
INFOFILE='/tmp/cm_adicional_info.txt'

#####
## SCRIPT CONFIG VARIABLES
SSHTIMEOUT=2    # seconds
PORTTIMEOUT=2   

#BACKUPFILEMODIFIED='/tmp/backup_'$(date +%Y_%m_%d)'_modified.yml';

cminfo(){
    echo "[INFO] cminfo called";
    echo "==== df -h ====" > $INFOFILE;
    df -h >> $INFOFILE;

    echo "" >> $INFOFILE;
    echo "==== uptime ====" >> $INFOFILE;
    uptime >> $INFOFILE;
    
    # PING test
    echo "" >> $INFOFILE;
    echo "==== AGENTS ====" >> $INFOFILE;
    echo "    PING    |    SSH    | AGENT PORT |  AGENT ALIVE  |      IP      |  NAME" >> $INFOFILE;
    echo "-----------------------------------------------------------------------------" >> $INFOFILE;

    for (( pidx=1; pidx<${#args[@]}; pidx++ ))
    do
        NAME=${args[$pidx]};
        pidx=$(($pidx+1));
        IP=${args[$pidx]};
        pidx=$(($pidx+1));
        AGENTPORT=${args[$pidx]};
        PINGSTATE="NOK";
        SSHSTATE="NOK";
        AGENTPORTRSP="CLOSED";

        ping -c 1 -w 5 $IP &>/dev/null;
        if [ $? -eq 0 ] ; then
            PINGSTATE="OK ";

            #test agent port
            echo $IP;
            nc -zv -w$PORTTIMEOUT $IP $AGENTPORT;
            if [ $? -eq 0 ] ; then
                AGENTPORTRSP="OPEN  ";
            fi

            #now test ssh connection
            ssh -q -q -o "BatchMode=yes" -o "ConnectTimeout $SSHTIMEOUT" root@$IP echo 2>&1
            if [ $? -eq 0 ] ; then
                SSHSTATE="OK ";
            fi
        fi
        echo "    $PINGSTATE     |    $SSHSTATE    |   $AGENTPORTRSP   |     $AGENTPORT     | $IP  |  $NAME" >> $INFOFILE;
    done
}


args=("$@");
       
if [ $# -lt 1 ]; then
    echo "usage:
./diagnostic_ball.sh /path/to/file/filename [[ip][port]] [[ip][port]]

Where:
    ip   - Is the Virtualization Agent to test 
    port - Virtualization Agent port
    "
    echo "Argument 1: =>  /path/to/file/filename.ext";
    exit -1;
fi

cd $SERVERDIR;
BALL=$1;

SOSFILE='/tmp/sosreport*';

/usr/sbin/sosreport --batch --name=eurotux --no-progressbar;
if [ $? -ne 0 ] ; then
    echo "[ERROR] sosreport exit code != 0";
    exit 2;  
fi

if [ $ETVADIAGNOSTIC != 'symfony' ]; then
#if [ ${args[(($#-1))]} != 'symfony' ]; then

    # from the command line (does not include info from the agents)
    cminfo;
    ./symfony propel:data-dump $BACKUPFILE
    tar -czvf $BALL $ALRTLOGS $ALRTALRT $CMLOGDIR $APPCONFIG $PROJECTCONFIG $MODELCONFIG $BACKUPFILE $PHPINI $INFOFILE $SOSFILE;
else
    echo 'symfony';
    # from the central management interface
    cminfo;
    ./symfony propel:data-dump $BACKUPFILE
    tar -czvf $BALL $ALRTLOGS $ALRTALRT $CMLOGDIR $APPCONFIG $PROJECTCONFIG $MODELCONFIG $BACKUPFILE $PHPINI $INFOFILE $AGENTSDIR $SOSFILE;
    rm -rf $AGENTSDIR/*_down;
fi
rm -rf $SOSFILE;
exit $?;

