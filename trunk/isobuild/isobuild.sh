#!/bin/bash

# first create iso using
# sudo /usr/sbin/revisor --cli --yes --kickstart-default --kickstart-include --install-dvd --kickstart=/home/mock/etva-ks.cfg
# e depois chamar este programa com
# ./isobuild.sh /srv/revisor/c5-i386/iso/CentOS-5-i386-DVD.iso

cd $HOME

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
ISOFILE=~/etva.iso

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

# Muda a imagem de boot
cp -f trunk/isobuild/isolinux/* $DIRDEST/isolinux/

# Cria o iso
mkisofs -r -R -J -T -v -no-emul-boot -boot-load-size 4 -boot-info-table \
-V "ETVA 1.0" -p "Eurotux Informatica S.A." -A "ETVA 1.0 – 2009/06/25" \
-b isolinux/isolinux.bin -c isolinux/boot.cat -x "lost+found" -o $ISOFILE $DIRDEST

# Implanta MD5
/usr/lib/anaconda-runtime/implantisomd5 $ISOFILE
