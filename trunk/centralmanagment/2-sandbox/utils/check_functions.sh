#!/bin/sh

#####
## SCRIPT CONFIG VARIABLES
if [ "$SSHTIMEOUT" == "" ]; then
    SSHTIMEOUT=5; # seconds
fi

if [ "$PINGTIMEOUT" == "" ]; then
    PINGTIMEOUT=5; # seconds
fi

if [ "$PORTTIMEOUT" == "" ]; then
    PORTTIMEOUT=5; # seconds
fi

if [ "$CHECKTIMEOUT" == "" ]; then
    CHECKTIMEOUT=180; # seconds
fi

if [ "$RESTARTTIMEOUT" == "" ]; then
    RESTARTTIMEOUT=30; # seconds
fi

if [ "$POWEROFFTIMEOUT" == "" ]; then
    POWEROFFTIMEOUT=30; # seconds
fi

if [ "$FENCETIMEOUT" == "" ]; then
    FENCETIMEOUT=30; # seconds
fi

## SCRIPT AUX VARIABLES
PIDS=""

## CONFIG VARS
SSHKEY='/srv/etva-centralmanagement/.ssh/id_dsa';
if [ ! -f "$SSHKEY" ]; then
    SSHKEY='/srv/etva-centralmanagement/.ssh/id_rsa';
fi

if [ "$DEBUGFILE" == "" ]; then
    DEBUGFILE="/var/log/etva_etvm/debug.log";
fi

checknode(){
    IP=$1
    AGENTPORT=$2
    NAME=$3
    PINGSTATE="NOK";
    SSHSTATE="NOK";
    AGENTPORTRSP="CLOSED";
    CHECKSTATE="NOK";

    ping -c 1 -w $PINGTIMEOUT $IP &>/dev/null;
    if [ $? -eq 0 ] ; then
        PINGSTATE="OK ";

        #test ssh connection
        timeout $SSHTIMEOUT ssh -q -o "StrictHostKeyChecking no" -o "BatchMode=yes" -o "ConnectTimeout $SSHTIMEOUT" -l root $IP -i $SSHKEY "echo 2>&1;";
        if [ $? -eq 0 ] ; then
            SSHSTATE="OK ";
        fi

        #test agent port
        #echo $IP;
        nc -zv -w$PORTTIMEOUT $IP $AGENTPORT;
        if [ $? -eq 0 ] ; then
            AGENTPORTRSP="OPEN  ";
        fi

        timeout $CHECKTIMEOUT ssh -q -o "StrictHostKeyChecking no" -o "BatchMode=yes" -o "ConnectTimeout $SSHTIMEOUT" -l root $IP -i $SSHKEY "cd /srv/etva-vdaemon; perl check.pl 2>&1; ";
        if [ $? -eq 0 ] ; then
            CHECKSTATE="OK ";
        fi
    fi
    echo "[INFO] $NAME($IP) SSH = $SSHSTATE ; AGENT = $AGENTPORTRSP CHECK = $CHECKSTATE";
}
waitrestartnode(){
    IP=$1
    AGENTPORT=$2
    NAME=$3

    RESTARTSTATE="OK ";
    sleep 120; # wait 2min

    CHECK_1=`checknode $1 $2 $3`;

    TEST_1=`echo "$CHECK_1" | grep "= NOK"`;

    echo "[DEBUG] after 120s CHECK = $CHECK_1" >>$DEBUGFILE;

    if [ "$TEST_1" != "" ]; then
        sleep 240; # wait 4min

        CHECK_2=`checknode $1 $2 $3`;

        TEST_2=`echo "$CHECK_2" | grep "= NOK"`;

        echo "[DEBUG] after 240s CHECK = $CHECK_2" >>$DEBUGFILE;

        if [ "$TEST_2" != "" ]; then
            sleep 360; # wait 6min

            CHECK_3=`checknode $1 $2 $3`;

            TEST_3=`echo "$CHECK_3" | grep "= NOK"`;

            echo "[DEBUG] after 360s CHECK = $CHECK_3" >>$DEBUGFILE;

            if [ "$TEST_3" != "" ]; then
                RESTARTSTATE="NOK ";
            fi
        fi
    fi
    echo "[INFO] $NAME($IP) RESTART = $RESTARTSTATE";
}
restartnode(){
    IP=$1
    AGENTPORT=$2
    NAME=$3

    RESTARTSTATE="OK ";

    timeout $RESTARTTIMEOUT ssh -q -o "StrictHostKeyChecking no" -o "BatchMode=yes" -o "ConnectTimeout $SSHTIMEOUT" -l root $IP -i $SSHKEY "cd /srv/etva-vdaemon; perl restart.pl 2>&1";
    
    if [ $? -eq 0 ] ; then
        CHECK_1=`waitrestartnode $1 $2 $3`;

        echo " [DEBUG] restartnode CHECK_1= $CHECK_1" >>$DEBUGFILE;

        TEST_1=`echo "$CHECK_1" | grep "RESTART = NOK"`;

        if [ "$TEST_1" != "" ]; then
            RESTARTSTATE="NOK ";
        fi
    else
        CHECK_1=`exec_fence 4 $@`;

        echo " [DEBUG] restartnode CHECK_1= $CHECK_1" >>$DEBUGFILE;

        TEST_1=`echo "$CHECK_1" | grep "Success"`;

        if [ "$TEST_1" != "" ]; then
            CHECK_2=`waitrestartnode $1 $2 $3`;

            TEST_2=`echo "$CHECK_2" | grep "RESTART = NOK"`;

            echo " [DEBUG] restartnode CHECK_2= $CHECK_2" >>$DEBUGFILE;

            if [ "$TEST_2" != "" ]; then
                RESTARTSTATE="NOK ";
            fi
        else 
            RESTARTSTATE="NOK ";
        fi
    fi

    echo "[INFO] $NAME($IP) RESTART = $RESTARTSTATE";
}

poweroffnode(){
    IP=$1
    AGENTPORT=$2
    NAME=$3

    POWEROFFSTATE="NOK ";

    timeout $POWEROFFTIMEOUT ssh -q -o "StrictHostKeyChecking no" -o "BatchMode=yes" -o "ConnectTimeout $SSHTIMEOUT" -l root $IP -i $SSHKEY "cd /srv/etva-vdaemon; perl poweroff.pl 2>&1";
    if [ $? -eq 0 ] ; then
        POWEROFFSTATE="OK ";
    else
        CHECK_1=`exec_fence 4 $@`;

        echo " [DEBUG] poweroffnode CHECK_1= $CHECK_1" >>$DEBUGFILE;

        TEST_1=`echo "$CHECK_1" | grep "Success"`;

        if [ "$TEST_1" != "" ]; then
            POWEROFFSTATE="OK ";
        fi
    fi

    echo "[INFO] $NAME($IP) POWEROFF = $POWEROFFSTATE";
}

wait_for_pids(){
    for pid in $pids ; do
        wait $pid
        echo "[INFO] PID $pid exited";
    done
}
strip_args(){
    i=$1;
    args=("$@");
    elems=${#args[@]};

    cmd="";
    for ((;i<$elems;i++)); do
     cmd="$cmd ${args[${i}]}";
    done 

    echo $cmd;
}
exec_fence() {
    cmd=`strip_args $@`;
    if [ "$cmd" != "" ]; then
        echo "[DEBUG] exec_fence: $cmd" >>$DEBUGFILE;
        eval "timeout $FENCETIMEOUT $cmd";
    fi
}
