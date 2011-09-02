#!/bin/bash
# Script to add a user to Linux system
# -------------------------------------------------------------------------
# Copyright (c) 2007 nixCraft project <http://bash.cyberciti.biz/>
# This script is licensed under GNU GPL version 2.0 or above
# Comment/suggestion: <vivek at nixCraft DOT com>
# -------------------------------------------------------------------------
# See url for more info:
# http://www.cyberciti.biz/tips/howto-write-shell-script-to-add-user.html
# -------------------------------------------------------------------------
if [ $(id -u) -eq 0 ]; then
    if [ -z "$1" ]; then
        read -p "Enter username : " username
        read -p "Enter password : " password
        read -p "Enter homedir : " homedir

    else
        username=$1
        password=$2
        homedir=$3
    fi

    execute="useradd"
    message="User has been added to system!";
    pass=$(perl -e 'print crypt($ARGV[0], "password")' $password)

    egrep "^$username" /etc/passwd >/dev/null
    #
    # if user exists then change password to new one
    #
    if [ $? -eq 0 ]; then
        message="User $username has been changed!";
        execute="usermod"
    fi
		
        if [ -z $homedir ]; then
            /usr/sbin/$execute -s /sbin/nologin -p $pass $username
        else            
            /usr/sbin/$execute -s /sbin/nologin -p $pass -d $homedir $username
        fi

		if [ $? -eq 0 ]; then
            echo $message
        else
            echo "Failed to add a user!"
        fi

else
	echo "Only root may add a user to the system"
	exit 2
fi
