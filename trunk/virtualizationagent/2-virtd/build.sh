#!/bin/sh

# build
svn export https://srcmaster/repos/etva/trunk/virtualizationagent/2-virtd virtagent-0.1-$REV
cat virtagent-0.1-$REV/virtagent.spec  | sed -e "s/Release:\s\s*[0-9][0-9]*/Release: $REV/" > virtagent-0.1-$REV/virtagent.spec.new
mv virtagent-0.1-$REV/virtagent.spec.new virtagent-0.1-$REV/virtagent.spec
tar cvzf virtagent-0.1-$REV.tar.gz virtagent-0.1-$REV/
rpmbuild -ta virtagent-0.1-$REV.tar.gz

# copy to nodes
scp /usr/src/redhat/RPMS/noarch/virtagent-0.1-$REV.noarch.rpm root@10.10.4.34:/tmp/;
scp /usr/src/redhat/RPMS/noarch/virtagent-0.1-$REV.noarch.rpm root@10.10.4.35:/tmp/;
scp /usr/src/redhat/RPMS/noarch/virtagent-0.1-$REV.noarch.rpm root@10.10.10.246:/tmp/
scp /usr/src/redhat/RPMS/noarch/virtagent-0.1-$REV.noarch.rpm  root@10.10.4.225:/tmp/;

# clean stuff
rm -rf virtagent-0.1-$REV/
rm -f virtagent-0.1-$REV.tar.gz
rm -f /usr/src/redhat/RPMS/noarch/virtagent-0.1-$REV.noarch.rpm
rm -f /usr/src/redhat/SRPMS/virtagent-0.1-$REV.src.rpm 
