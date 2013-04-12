#!/bin/bash
# This script generates a tarball with the ETVA/ETMV configuration.
#####


BACKUPFILE='/tmp/backup_'$(date +%Y_%m_%d)'.yml';
SERVERDIR='/srv/etva-centralmanagement';
ALRTLOGS='/var/log/etva_etvm/*.log';
ALRTALRT='/var/run/etva_etvm';
INSTALLLOG='/var/log/post_install.log /root/install.log /var/log/messages*'
CMLOGDIR=$SERVERDIR'/log/*.log';
APPCONFIG=$SERVERDIR'/apps/app/config';
PROJECTCONFIG=$SERVERDIR'/config';
MODELCONFIG='/etc/sysconfig/etva-model.conf';
PHPINI='/etc/php.ini';
SSHKEY='/srv/etva-centralmanagement/.ssh/id_dsa';

#####
## AUTO VARIABLES
AGENTSDIR=$SERVERDIR'/data/backup'
CMDB=$SERVERDIR'/data/fixtures/backup'
INFOFILE='/tmp/cm_adicional_info.txt'

#####
## SCRIPT CONFIG VARIABLES
SSHTIMEOUT=5    # seconds
PORTTIMEOUT=2   

#####
## SCRIPT VARIABLES
PIDS=""
SOSFILE='/tmp/sosreport*';


#BACKUPFILEMODIFIED='/tmp/backup_'$(date +%Y_%m_%d)'_modified.yml';
getAgentDiagnosticBySsh(){
    echo "[INFO] Triying to diagnose $2 by ssh connection ($1)."
    ssh -o ConnectTimeout=$SSHTIMEOUT $1 -i $SSHKEY 'perl -I/srv/etva-vdaemon/lib /srv/etva-vdaemon/diagnostic.pl;' 
    if [ $? -ne 0 ] ; then
        echo "[ERROR] Could'nt run diagnostic.pl on: $2";
        exit 5;  
    fi

    scp -i $SSHKEY root@$1:/tmp/diagnostic_ball.tar $AGENTSDIR/$2.dignostic.tar;
    if [ $? -ne 0 ] ; then
        echo "[ERROR] Could'nt copy the agent diagnostic tarball";
        exit 6;  
    fi
    echo "[INFO] Diagnose from $2 successfully fetched.";
}

wait_for_pids(){
    for pid in $pids ; do
        wait $pid
        echo "[INFO] PID $pid exited";
    done
}

sosreport(){
    /usr/sbin/sosreport --batch --name=eurotux --no-progressbar;
    if [ $? -ne 0 ] ; then
        touch /tmp/sosreport_cm_failed;
    fi
}

db_backup(){
    ./symfony propel:data-dump $BACKUPFILE
}

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

            #test ssh connection
#            echo "ssh -q -q -o "BatchMode=yes" -o "ConnectTimeout $SSHTIMEOUT" root@$IP echo 2>&1";
            ssh -q -q -o "BatchMode=yes" -o "ConnectTimeout $SSHTIMEOUT" root@$IP -i $SSHKEY "echo 2>&1";
            if [ $? -eq 0 ] ; then
                SSHSTATE="OK ";
                echo "$NAME SSH OK";
            fi

            #test agent port
            echo $IP;
            nc -zv -w$PORTTIMEOUT $IP $AGENTPORT;
            if [ $? -eq 0 ] ; then
                AGENTPORTRSP="OPEN  ";
            fi

        fi
        echo "[INFO] $NAME($IP) SSH = $SSHSTATE ; AGENT = $AGENTPORTRSP";
        if [ $SSHSTATE != "NOK" -a $AGENTPORTRSP = "CLOSED" ]; then
#            getAgentDiagnosticBySsh $IP $NAME;
            bash $0 getAgentDiagnosticBySsh $IP $NAME&
            pids="$pids $!";
        fi
        echo "    $PINGSTATE     |    $SSHSTATE    |   $AGENTPORTRSP   |     $AGENTPORT     | $IP  |  $NAME" >> $INFOFILE;
    done
    sosreport;
    db_backup;
    wait_for_pids;
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

if [ $# -eq 3 -a $1 = "getAgentDiagnosticBySsh" ]; then
  getAgentDiagnosticBySsh $2 $3;
  exit 0;
fi

#echo "ETVA DIAGNOSTIC $ETVADIAGNOSTIC";
#exit 0;
cd $SERVERDIR;
BALL=$1;

echo "[INFO] CMINFO ";
cminfo;
echo "[INFO] CMINFO EXITED";

#if [ $ETVADIAGNOSTIC -a $ETVADIAGNOSTIC != 'symfony' ]; then
#    tar -czvf $BALL $ALRTLOGS $ALRTALRT $CMLOGDIR $APPCONFIG $PROJECTCONFIG $MODELCONFIG $BACKUPFILE $PHPINI $INFOFILE $SOSFILE;
#else
tar -czvf $BALL $ALRTLOGS $ALRTALRT $CMLOGDIR $APPCONFIG $PROJECTCONFIG $MODELCONFIG $BACKUPFILE $PHPINI $INFOFILE $AGENTSDIR $SOSFILE $INSTALLLOG;
rm -rf $AGENTSDIR/*;
#rm -rf $AGENTSDIR/*_down;
#fi

rm -rf $SOSFILE;
exit $?;

