#!/bin/bash

DIR=`dirname $0`
if [ "$DIR" == "." ]; then
	DIR=`ls -la /proc/$$/cwd | awk {' print $11 '}`
fi
[ "$DIR" != "" ] || exit

cd $HOME
DIR=$HOME

[ -d $DIR/SRPMS ] || ( mkdir $DIR/SRPMS
mkdir -p $DIR/._RPM_/{RPMS/i386,RPMS/x86_64,SPECS}
)

if [ ! -d $DIR/trunk ]; then
	VERSION=`svn export  https://srcmaster.eurotux.com/repos/etva/trunk $DIR/trunk| tail -1 | awk {' print $3'}| sed -e 's/.$//'`
else
	VERSION=`svn info $DIR/trunk |grep Revision|awk {' print $2 '}`
fi

# build dos pacotes de virtualizacao
cd trunk/virtualizationagent/*libvirt
	for spec in SPECS/*; do
		rpmbuild --nodeps -bs --define "_topdir $PWD" $spec > /dev/null
	done
	mv SRPMS/* $DIR/SRPMS/
cd - > /dev/null
cd trunk/virtualizationagent/*perlmodules
	for spec in SPECS/*; do
		rpmbuild --nodeps -bs --define "_topdir $PWD" $spec > /dev/null
	done
	mv SRPMS/* $DIR/SRPMS/
cd - > /dev/null
cd trunk/centralmanagment/*-required
	for spec in SPECS/*; do
		rpmbuild --nodeps -bs --define "_topdir $PWD" $spec > /dev/null
	done
	mv SRPMS/* $DIR/SRPMS/
cd - > /dev/null

# build do vagent
cd trunk/virtualizationagent/
	if [ -d *-virtd ]; then
		_VERSION=`head *-virtd/*.spec|egrep -i "^Version:"|awk {' print $2'}`
		_SOURCE=`head *-virtd/*.spec|egrep -i "^Source:"|awk {' print $2'}|sed -e 's/-.*//'`
		_SOURCE=$_SOURCE-${_VERSION}-$VERSION
		cat *-virtd/*.spec | sed -e "s/^Release:.*/Release: $VERSION/g" > _tmp
		mv _tmp *-virtd/*.spec
		mv *-virtd $_SOURCE
	else
		_VERSION=`head *$VERSION/*.spec|egrep -i "^Version:"|awk {' print $2'}`
		_SOURCE=`head *$VERSION/*.spec|egrep -i "^Source:"|awk {' print $2'}|sed -e 's/-.*//'`
		_SOURCE=$_SOURCE-$_VERSION-$VERSION
	fi
	rm -f $_SOURCE.tar.gz
	tar zcf $_SOURCE.tar.gz $_SOURCE
	rpmbuild --nodeps -ts --define "_topdir $DIR/._RPM_" --define "_srcrpmdir $DIR/SRPMS" $_SOURCE.tar.gz > /dev/null
cd - > /dev/null

# build do symfony
cd trunk/centralmanagment/*-symfony
	for spec in SPECS/*; do
		rpmbuild --nodeps -bs --define "_topdir $PWD" $spec > /dev/null
	done
	mv SRPMS/* $DIR/SRPMS/
cd - > /dev/null

# build do centralmanagement
cd trunk/centralmanagment/
	if [ -d *-sandbox ]; then
		_VERSION=`head *-sandbox/*.spec|egrep -i "^Version:"|awk {' print $2'}`
		_SOURCE=`head *-sandbox/*.spec|egrep -i "^Source:"|awk {' print $2'}|sed -e 's/-%.*//'`
		_SOURCE=$_SOURCE-${_VERSION}-$VERSION
		cat *-sandbox/*.spec | sed -e "s/^Release:.*/Release: $VERSION/g" > _tmp
		mv _tmp *-sandbox/*.spec
		mv *-sandbox $_SOURCE
	else
		_VERSION=`head *$VERSION/*.spec|egrep -i "^Version:"|awk {' print $2'}`
		_SOURCE=`head *$VERSION/*.spec|egrep -i "^Source:"|awk {' print $2'}|sed -e 's/-%.*//'`
		_SOURCE=$_SOURCE-$_VERSION-$VERSION
	fi
	rm -f $_SOURCE.tar.gz
	tar zcf $_SOURCE.tar.gz $_SOURCE
	rpmbuild -ts --define "_topdir $DIR/._RPM_" --define "_srcrpmdir $DIR/SRPMS" $_SOURCE.tar.gz > /dev/null
cd - > /dev/null

# Lista RPMS gerados
ls -la $DIR/SRPMS/
