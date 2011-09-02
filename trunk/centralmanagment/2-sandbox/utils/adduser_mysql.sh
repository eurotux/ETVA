#!/bin/bash
# Script to add default root password for mysql and add a user to Linux system

_rootpwd="$1"
_db="$2"
_user="$3"
_pass="$4"

# make sure we get at least 3 args, else die
[[ $# -le 3 ]] && { echo "Usage: $0 'ROOT_PWD' 'DB_Name' 'DB_USER' 'DB_PASSORD'"; exit 1; }

/usr/bin/mysqladmin -u root password $_rootpwd
if [ $? -eq 0 ]; then
    echo "Password changed!"
    echo -e "[Client]\nhost=localhost\nuser=root\npassword=$_rootpwd" > /root/.my.cnf
else
    echo "Failed to change mysql password!"    
fi

_rootpwd=`cat /root/.my.cnf | grep password | awk -F'=' '{print $2}'`

#check if db exists first
echo "Creating a database for $_db"
DBS=`mysql -u root -p$_rootpwd -Bse 'show databases'| egrep -v 'information_schema|mysql'`
for db in $DBS; do
if [ "$db" = "$_db" ]
then
echo "This database already exists : exiting now"
  fi
done
mysqladmin -u root -p$_rootpwd create $_db;
mysql -u root -p$_rootpwd -h localhost -e "GRANT ALL ON ${_db}.* TO ${_user}@localhost IDENTIFIED BY '${_pass}';"

# stores db connection in apache 
# echo -e "DBDParams \"host=localhost dbname=etva user=$_user pass=$_pass\"" >> /etc/httpd/conf.d/httpd_etvacm.conf
sed -i "s/^DBDParams.*/DBDParams \"host=localhost dbname=etva user=$_user pass=$_pass\"/g" /etc/httpd/conf.d/httpd_etvacm.conf
echo "Database $_db created"
