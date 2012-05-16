#!/bin/bash -eu
# -e: Exit immediately if a command exits with a non-zero status.
# -u: Treat unset variables as an error when substituting.

# primeiro criar iso com o revisor e depois
# chamar este programa com
# ./isobuild.sh /srv/revisor/el6-x86_64-updates/iso/etvm-6-x86_64-DVD.iso

DIR=`dirname $0`
if [ "$DIR" == "." ]; then
        DIR=`ls -la /proc/$$/cwd | awk {' print $11 '}`
fi
[ "$DIR" != "" ] || exit

DIR=$DIR/../../
cd $DIR

# check if we require sudo
if [ "`id -u`" == "0" ]; then
	SUDO=""
else
	SUDO="sudo"
fi

rpm -q anaconda > /dev/null 2>&1
if [ "$?" != "0" ]; then
	$SUDO yum -y install anaconda anaconda-help anaconda-runtime
fi

if [ "$1" == "" ]; then
	echo "$0 <file.iso>"
	exit 1
fi


if [ ! -f $1 ]; then
	echo "Iso not found"
	exit 1
fi


#cria uma directoria temporaria para ser montada
DIRSOURCE=`mktemp -d`
DIRIMG=`mktemp -d`
DIRDEST=/tmp/BUILD
ISOFILE=$DIR/etva.iso

############################ FUNCAO DE TRAP
function limpa() {
	if [ -d $DIRSOURCE ]; then
		$SUDO fuser -k $DIRSOURCE 2> /dev/null || :
		$SUDO umount $DIRSOURCE 2> /dev/null || :
		rmdir $DIRSOURCE 2> /dev/null || :
	fi
	if [ -d $DIRIMG ]; then
		rm -rf $DIRIMG
	fi
}

trap limpa EXIT
############################################

rm -rf $DIRDEST
rm -f $ISOFILE 2> /dev/null
mkdir $DIRDEST 2> /dev/null

#monta directoria
$SUDO mount -t iso9660 -o loop $1 $DIRSOURCE

# Copia conteudo para disco
cd $DIRSOURCE
	tar -cf - . | ( cd $DIRDEST ; tar -xpf - )
cd - > /dev/null
$SUDO umount $DIRSOURCE

# Find all directories, and make sure they have +x permission
find $DIRDEST -type d -exec chmod -c 755 {} \;

# Muda a imagem de boot
cp -f /tmp/isolinux/* $DIRDEST/isolinux/
# Adiciona p2v
#cp -rf trunk/isobuild/live $DIRDEST/
#rm -rf $DIRDEST/live/.svn

# recria o comps.xml
chmod +w $DIRDEST/repodata -R
#grep -v  "xml:lang" repodata/comps.xml > /tmp/no-lang.comps.xml
rm -f $DIRDEST/repodata/comps.xml
# acerta o comps.xml e identa
xsltproc --novalid -o $DIRDEST/repodata/comps.xml /usr/share/revisor/comps-cleanup.xsl /tmp/comps.xml
# recria os ficheiros do repositorio
cd $DIRDEST
	createrepo -g repodata/comps.xml .
cd - > /dev/null

# remove anaconda repo
cd $DIRIMG
	unsquashfs $DIRDEST/images/install.img
	rm -f squashfs-root/etc/anaconda.repos.d/CentOS-Base.repo squashfs-root/etc/anaconda.repos.d/CentOS-Debuginfo.repo
	mksquashfs squashfs-root/ $DIRDEST/images/install.img -noappend
	$SUDO rm -rf squashfs-root
cd - > /dev/null
rm -rf $DIRIMG

# cria o ks de enterprise
cat $DIRDEST/ks.cfg | sed -e 's/^# interactive/interactive/g' |egrep -v "^(virtagent|etva-centralmanagement|etva-virtio-win|xen|kernel-xen|etva-smb|kvm|ignoredisk|clearpart|part|raid|volgroup|logvol)" > $DIRDEST/ks.ent.cfg
# cria o ks de smb
cat $DIRDEST/ks.cfg | egrep -v "xen" |egrep -v "^(etva-enterprise|etva-centralmanagement-ent|etva-centralmanagement-nrpe)"> $DIRDEST/ks.smb.kvm.cfg
cat $DIRDEST/ks.cfg | egrep -v "kvm" |egrep -v "^(etva-enterprise|etva-centralmanagement-ent|etva-centralmanagement-nrpe|etva-virtio-win)"> $DIRDEST/ks.smb.xen.cfg
# cria o ks de smb-usb
cat $DIRDEST/ks.cfg | egrep -v "xen" |egrep -v "^(etva-enterprise|etva-centralmanagement-ent|etva-centralmanagement-nrpe)" | sed -e 's/^cdrom/askmethod/g' > $DIRDEST/ks.smb.kvm.usb.cfg
cat $DIRDEST/ks.cfg | egrep -v "kvm" |egrep -v "^(etva-enterprise|etva-centralmanagement-ent|etva-centralmanagement-nrpe|etva-virtio-win)" | sed -e 's/^cdrom/askmethod/g' > $DIRDEST/ks.smb.xen.usb.cfg

# Cria o iso
mkisofs -r -R -J -T -v -no-emul-boot -boot-load-size 4 -boot-info-table \
-V "ETVM 1.0" -p "Eurotux Informatica S.A." -A "ETVM 1.0 - 2011/06/25" \
-b isolinux/isolinux.bin -c isolinux/boot.cat -x "lost+found" -o $ISOFILE $DIRDEST

# Implanta MD5
/usr/bin/implantisomd5 $ISOFILE

# Transforma o iso para poder ser bootable tambem por USB
[ -f /usr/bin/isohybrid ] && /usr/bin/isohybrid $ISOFILE || echo -e "Please install syslinux > 3.72.\nCould not do isohybrid. $ISOFILE is not bootable in USB stick"
