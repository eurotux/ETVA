#!/bin/bash

# first create iso using
# sudo /usr/sbin/revisor --cli --yes --kickstart-default --kickstart-include --install-dvd --kickstart=/home/mock/etva-ks.cfg
# e depois chamar este programa com
# ./isobuild.sh /srv/revisor/c5-i386/iso/CentOS-5-i386-DVD.iso

DIR=`dirname $0`
if [ "$DIR" == "." ]; then
        DIR=`ls -la /proc/$$/cwd | awk {' print $11 '}`
fi
[ "$DIR" != "" ] || exit

DIR=$DIR/../../
cd $DIR

rpm -q anaconda anaconda-runtime > /dev/null 2>&1
if [ "$?" != "0" ]; then
	sudo yum -y install anaconda anaconda-help anaconda-runtime
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
DIRDEST=/tmp/BUILD
ISOFILE=$DIR/etva.iso

############################ FUNCAO DE TRAP
function limpa() {
	if [ -d $DIRSOURCE ]; then
		sudo fuser -k $DIRSOURCE 2> /dev/null
		sudo umount $DIRSOURCE 2> /dev/null
		rmdir $DIRSOURCE 2> /dev/null
	fi
}

trap limpa EXIT
############################################

rm -rf $DIRDEST
rm -f $ISOFILE 2> /dev/null
mkdir $DIRDEST 2> /dev/null

#monta directoria
sudo mount -t iso9660 -o loop $1 $DIRSOURCE

# Copia conteudo para disco
cd $DIRSOURCE
	tar -cf - . | ( cd $DIRDEST ; tar -xpf - )
	sudo umount $DIRSOURCE
cd - > /dev/null

#ADICIONAR PACOTES
# nao e' necessa'rio uma vez que ja' foi feito pelo revisor
# cp mypackage-x.y-z.i386.rpm $DIRDEST/CentOS

# Find all directories, and make sure they have +x permission
find $DIRDEST -type d -exec chmod -c 755 {} \;

# Muda a imagem de boot
cp -f trunk/isobuild/isolinux/* $DIRDEST/isolinux/
# Adiciona p2v
#cp -rf trunk/isobuild/live $DIRDEST/
#rm -rf $DIRDEST/live/.svn

# recria o comps.xml
chmod +w $DIRDEST/repodata -R
#grep -v  "xml:lang" repodata/comps.xml > /tmp/no-lang.comps.xml
rm -f $DIRDEST/repodata/comps.xml
# acerta o comps.xml e identa
xsltproc --novalid -o $DIRDEST/repodata/comps.xml /usr/share/revisor/comps-cleanup.xsl trunk/isobuild/comps/comps.xml
# recria os ficheiros do repositorio
cd $DIRDEST
	createrepo -g repodata/comps.xml .
cd - > /dev/null

# modifica o stage2 para mudar algumas strings
sudo /usr/sbin/unsquashfs $DIRDEST/images/stage2.img
cd squashfs-root
	sudo patch -p1 < ../trunk/isobuild/stage2/rebrand.diff
cd - > /dev/null
sudo /sbin/mksquashfs squashfs-root/ $DIRDEST/images/stage2.img -noappend
sudo rm -rf squashfs-root

# cria o ks de enterprise
cat $DIRDEST/ks.cfg | sed -e 's/^# interactive/interactive/g' |egrep -v "^(virtagent|etva-centralmanagement|xen|kernel-xen|etva-smb|kvm|ignoredisk|clearpart|part|raid|volgroup|logvol)" > $DIRDEST/ks.ent.cfg
# cria o ks de smb
cat $DIRDEST/ks.cfg | egrep -v "xen" |egrep -v "^(etva-enterprise|etva-centralmanagement-ent)"> $DIRDEST/ks.smb.kvm.cfg
cat $DIRDEST/ks.cfg | egrep -v "kvm" |egrep -v "^(etva-enterprise|etva-centralmanagement-ent)"> $DIRDEST/ks.smb.xen.cfg
# cria o ks de smb-usb
cat $DIRDEST/ks.cfg | egrep -v "xen" |egrep -v "^(etva-enterprise|etva-centralmanagement-ent)" | sed -e 's/^cdrom/askmethod/g' > $DIRDEST/ks.smb.kvm.usb.cfg
cat $DIRDEST/ks.cfg | egrep -v "kvm" |egrep -v "^(etva-enterprise|etva-centralmanagement-ent)" | sed -e 's/^cdrom/askmethod/g' > $DIRDEST/ks.smb.xen.usb.cfg

# Cria o iso
mkisofs -r -R -J -T -v -no-emul-boot -boot-load-size 4 -boot-info-table \
-V "ETVA 1.0" -p "Eurotux Informatica S.A." -A "ETVA 1.0 - 2010/06/25" \
-b isolinux/isolinux.bin -c isolinux/boot.cat -x "lost+found" -o $ISOFILE $DIRDEST

# Implanta MD5
/usr/lib/anaconda-runtime/implantisomd5 $ISOFILE

# Transforma o iso para poder ser bootable tambem por USB
[ -f /usr/bin/isohybrid ] && /usr/bin/isohybrid $ISOFILE || echo -e "Please install syslinux > 3.72.\nCould not do isohybrid. $ISOFILE is not bootable in USB stick"
