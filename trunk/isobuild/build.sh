#!/bin/bash -eu
# -e: Exit immediately if a command exits with a non-zero status.
# -u: Treat unset variables as an error when substituting.

DIR=`dirname $0`
if [ "$DIR" == "." ]; then
	DIR=`ls -la /proc/$$/cwd | awk {' print $11 '}`
fi
[ "$DIR" != "" ] || exit

DIR=$DIR/../../
cd $DIR

[ -d $DIR/SRPMS ] || ( mkdir $DIR/SRPMS
mkdir -p $DIR/._RPM_/{RPMS/i386,RPMS/x86_64,SPECS}
)

rm -rf $DIR/SRPMS/*rpm 2> /dev/null

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
cd trunk/virtualizationagent/3-requires
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
		_SOURCE=`head -20 *-virtd/*.spec|egrep -i "^Source:"|awk {' print $2'}|sed -e 's/-.*//'`
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

# build do lcdagent
cd trunk/virtualizationagent/*-tools
	if [ -d lcdagent ]; then
		_VERSION=`head lcdagent/*.spec|egrep -i "^Version:"|awk {' print $2'}`
		_SOURCE=`head lcdagent/*.spec|egrep -i "^Source:"|awk {' print $2'}|sed -e 's/-.*//'`
		_SOURCE=$_SOURCE-${_VERSION}-$VERSION
		cat lcdagent/*.spec | sed -e "s/^Release:.*/Release: $VERSION/g" > _tmp
		mv _tmp lcdagent/*.spec
		mv lcdagent $_SOURCE
	else
		_VERSION=`head *$VERSION/*.spec|egrep -i "^Version:"|awk {' print $2'}`
		_SOURCE=`head *$VERSION/*.spec|egrep -i "^Source:"|awk {' print $2'}|sed -e 's/-.*//'`
		_SOURCE=$_SOURCE-$_VERSION-$VERSION
	fi
	rm -f $_SOURCE.tar.gz
	tar zcf $_SOURCE.tar.gz $_SOURCE
	rpmbuild --nodeps -ts --define "_topdir $DIR/._RPM_" --define "_srcrpmdir $DIR/SRPMS" $_SOURCE.tar.gz > /dev/null
cd - > /dev/null


# build dos pacotes do etvoipagent
cd trunk/etvoipagent/*-required
	for spec in SPECS/*; do
		rpmbuild --nodeps -bs --define "_topdir $PWD" $spec > /dev/null
	done
	mv SRPMS/* $DIR/SRPMS/
cd - > /dev/null

# build do etvoipagent
cd trunk/etvoipagent/
	if [ -d *-etvoipd ]; then
		_VERSION=`head *-etvoipd/*.spec|egrep -i "^Version:"|awk {' print $2'}`
		_SOURCE=`head *-etvoipd/*.spec|egrep -i "^Source:"|awk {' print $2'}|sed -e 's/-%.*//'`
		_SOURCE=$_SOURCE-${_VERSION}-$VERSION
		cat *-etvoipd/*.spec | sed -e "s/^Release:.*/Release: $VERSION/g" > _tmp
		mv _tmp *-etvoipd/*.spec
		mv *-etvoipd $_SOURCE
	else
		_VERSION=`head *$VERSION/*.spec|egrep -i "^Version:"|awk {' print $2'}`
		_SOURCE=`head *$VERSION/*.spec|egrep -i "^Source:"|awk {' print $2'}|sed -e 's/-%.*//'`
		_SOURCE=$_SOURCE-$_VERSION-$VERSION
	fi
	rm -f $_SOURCE.tar.gz
	tar zcf $_SOURCE.tar.gz $_SOURCE
	rpmbuild --nodeps -ts --define "_topdir $DIR/._RPM_" --define "_srcrpmdir $DIR/SRPMS" $_SOURCE.tar.gz > /dev/null
cd - > /dev/null

# build dos pacotes do etfwagent
cd trunk/etfwagent/*-requires || exit -1
	for spec in SPECS/*; do
		rpmbuild --nodeps -bs --define "_topdir $PWD" $spec > /dev/null
	done
	mv SRPMS/* $DIR/SRPMS/
cd - > /dev/null

# build do etfwagent
cd trunk/etfwagent/
	if [ -d *-etfwd ]; then
		_VERSION=`head *-etfwd/*.spec|egrep -i "^Version:"|awk {' print $2'}`
		_SOURCE=`head *-etfwd/*.spec|egrep -i "^Source:"|awk {' print $2'}|sed -e 's/-%.*//'`
		_SOURCE=$_SOURCE-${_VERSION}-$VERSION
		cat *-etfwd/*.spec | sed -e "s/^Release:.*/Release: $VERSION/g" > _tmp
		mv _tmp *-etfwd/*.spec
		mv *-etfwd $_SOURCE
	else
		_VERSION=`head *$VERSION/*.spec|egrep -i "^Version:"|awk {' print $2'}`
		_SOURCE=`head *$VERSION/*.spec|egrep -i "^Source:"|awk {' print $2'}|sed -e 's/-%.*//'`
		_SOURCE=$_SOURCE-$_VERSION-$VERSION
	fi
	rm -f $_SOURCE.tar.gz
	tar zcf $_SOURCE.tar.gz $_SOURCE
	rpmbuild --nodeps -ts --define "_topdir $DIR/._RPM_" --define "_srcrpmdir $DIR/SRPMS" $_SOURCE.tar.gz > /dev/null
cd - > /dev/null

# build dos pacotes extra necessarios
cd trunk/extrapackages/SOURCES && make && cd - > /dev/null
cd trunk/extrapackages/
	for spec in SPECS/*; do
		rpmbuild --nodeps -bs --define "_topdir $PWD" $spec > /dev/null
	done
	mv SRPMS/* $DIR/SRPMS/
cd - > /dev/null
cd trunk/extrapackages/SOURCES && make distclean && cd - > /dev/null

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
		_SOURCE=`head -20 *-sandbox/*.spec|egrep -i "^Source:"|awk {' print $2'}|sed -e 's/-%.*//'`
		_SOURCE=$_SOURCE-${_VERSION}-$VERSION
		cat *-sandbox/*.spec | sed -e "s/^Release:.*/Release: $VERSION/g" > _tmp
		mv _tmp *-sandbox/*.spec
		mv *-sandbox $_SOURCE
	else
		_VERSION=`head *$VERSION/*.spec|egrep -i "^Version:"|awk {' print $2'}`
		_SOURCE=`head -20 *$VERSION/*.spec|egrep -i "^Source:"|awk {' print $2'}|sed -e 's/-%.*//'`
		_SOURCE=$_SOURCE-$_VERSION-$VERSION
	fi
	rm -f $_SOURCE.tar.gz
	tar zcf $_SOURCE.tar.gz $_SOURCE
	rpmbuild --nodeps -ts --define "_topdir $DIR/._RPM_" --define "_srcrpmdir $DIR/SRPMS" $_SOURCE.tar.gz > /dev/null
cd - > /dev/null

# revert das alteracoes
rm -f trunk/centralmanagment/etva-centralmanagement*tar.gz trunk/virtualizationagent/virtagent-*.tar.gz trunk/etvoipagent/etva-etvoip-*tar.gz trunk/etfwagent/etva-etfw-*tar.gz && \
mv trunk/centralmanagment/etva-centralmanagement-* trunk/centralmanagment/2-sandbox && \
mv trunk/virtualizationagent/virtagent-* trunk/virtualizationagent/2-virtd && \
mv trunk/etvoipagent/etva-etvoip-* trunk/etvoipagent/1-etvoipd && \
mv trunk/etfwagent/etva-etfw-* trunk/etfwagent/1-etfwd && \
(cd trunk && svn revert centralmanagment/2-sandbox/etva-centralmanagement.spec virtualizationagent/2-virtd/virtagent.spec)

# Lista RPMS gerados
ls -la $DIR/SRPMS/
