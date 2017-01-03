#!/bin/bash

# TODO tentar recuperar a execução

# Require free size of 4G
REQUIREFREESIZE=4194304

ETVAISOSDIR="/usr/share/etva-isos"
BKPDIR="/bkp/bkp-unitbox-1-x-root"

GOLDIMAGEDIR="/bkp/unitbox-2-x-root"

GOLDIMAGEFILE="$ETVAISOSDIR/unitbox-2-x-root.tar.gz";

RUNLEVEL=`runlevel | sed -n 's/.*\([[:digit:]]\+\).*/\1/p'`
BACKUPFILE="/srv/etva-centralmanagement/data/backup/unitbox-cm-bkp-`date +'%Y%m%d'`.yml"

LOGFILE="/unitbox-upgrade-1x-to-2x.log"

# redirect logs
exec > >(tee -a $LOGFILE)
exec 2> >(tee -a $LOGFILE >&2)

RETVAL=0

getgoldimage(){
    #  get gold image
    echo "  get gold image"
    scp -v 10.172.4.1:$GOLDIMAGEFILE $GOLDIMAGEFILE
    scp -v 10.172.4.1:$GOLDIMAGEFILE.sha1sum $GOLDIMAGEFILE.sha1sum
    RETVAL=0
    return $RETVAL
}
checkgoldimage(){
    # validate sha1sum goldimage
    cd $ETVAISOSDIR
    echo "Validate gold image!"
    sha1sum --status -c $GOLDIMAGEFILE.sha1sum
    RETVAL=$?
    if [[ $RETVAL -ne 0 ]]; then
        echo "Gold image invalid!"
    fi
    cd - >/dev/null
    return $RETVAL
}

if [[ "$RUNLEVEL" -ne 1 ]]; then

    if [ ! -e "$BACKUPFILE" ]; then
        #  backup symfony database
        echo "  backup symfony database"
        cd /srv/etva-centralmanagement/
        symfony propel:data-dump $BACKUPFILE
    fi

    # Require 4G of free space
    FREESPACESIZE=`df -P | grep "$ETVAISOSDIR" | awk '{ print $4 }'`

    if [[ "$FREESPACESIZE" -lt "$REQUIREFREESIZE" ]]; then
        echo "No free space available. You need '$REQUIREFREESIZE' free space and you have '$FREESPACESIZE'"
        exit 1
    else
        echo "Free space ok."
    fi

    if [ ! -e "$GOLDIMAGEFILE" ]; then
        #  get gold image
        getgoldimage
    fi

    # validate sha1sum goldimage
    checkgoldimage
    if [[ $RETVAL -ne 0 ]]; then

        #  try to get gold image
        getgoldimage

        checkgoldimage
        if [[ $RETVAL -ne 0 ]]; then
            echo "Could get valid gold image!"
            exit $RETVAL
        fi
    fi

    echo "  go to run-level 1 to proceed to the upgrade"
    echo "   do 'telinit 1'"
    exit 1
fi

#  stop services
echo "  stop services"

service nfslock stop
service rpcidmapd stop
service dnsmasq stop
service iscsid stop
killall udevd

# umount rpc_pipefs
umount /var/lib/nfs/rpc_pipefs

#  uncompress gold image
echo "  uncompress gold image"

# create /bkp
if [ ! -d "/bkp" ]; then
    mkdir /bkp
fi

if [[ `grep "$ETVAISOSDIR" /proc/mounts` != "" ]]; then
    umount $ETVAISOSDIR
fi

if [[ `grep "/bkp" /proc/mounts` == "" ]]; then
    mount /dev/vg_etva_local/etva-isos /bkp
fi

if [ ! -d "$GOLDIMAGEDIR" ]; then
    mkdir -p $GOLDIMAGEDIR
fi
if [ ! -d "$GOLDIMAGEDIR/bin" ]; then
    cd $GOLDIMAGEDIR
    tar xvzf ../unitbox-2-x-root.tar.gz
fi

#  create backup
echo "  create backup"

if [ ! -d "$BKPDIR" ]; then
    mkdir -p $BKPDIR
fi

rsync -Ptav --exclude="/bkp" --exclude="/dev" --exclude="/proc" --exclude="/sys" --exclude="/selinux" --exclude="/lost+found" --exclude="/srv/etva-vdaemon/storage" / $BKPDIR

#echo "stop here"
#exit 1

#  update OS files
echo "  update OS files"

echo "   remove old files"
cd /
rm -rf boot/*
rm -rf service tmp var srv opt mnt media home
rm -rf bin lib* sbin* usr root etc
# remove Nuxis dirs. Preserve /srv/etva-vdaemon/storage
rm -rf /srv/etva-centralmanagement /srv/lcdagent

echo "   copy new files"
export LD_LIBRARY_PATH=$GOLDIMAGEDIR/lib64
$GOLDIMAGEDIR/sbin/ldconfig -r $GOLDIMAGEDIR
$GOLDIMAGEDIR/lib64/ld-linux-x86-64.so.2 $GOLDIMAGEDIR/usr/bin/rsync -ax --numeric-ids $GOLDIMAGEDIR/ /

unset LD_LIBRARY_PATH

#  recover configurations
echo "  recover configurations"

cp -varf $BKPDIR/etc/sysconfig/network /etc/sysconfig/network

rm -rf /etc/sysconfig/networking/*
cp -varf $BKPDIR/etc/sysconfig/networking/* /etc/sysconfig/networking/

cp -varf $BKPDIR/etc/ntp.conf /etc/ntp.conf

cp -varf $BKPDIR/etc/resolv.conf /etc/resolv.conf

if [ -e "$BKPDIR/etc/scripts" ]; then
    cp -varf $BKPDIR/etc/scripts /etc/
fi

cp -varf $BKPDIR/etc/cron.d/etva-backups /etc/cron.d/etva-backups

cp -varf $BKPDIR/etc/hosts* /etc/

cp -varf $BKPDIR/etc/lvm/* /etc/lvm/

cp -varf $BKPDIR/etc/shadow /etc/shadow

# repor configuracao do iscsi
cp -varf $BKPDIR/var/lib/iscsi/* /var/lib/iscsi/

# repor configuracao do libvirt
cp -varf $BKPDIR/etc/libvirt/qemu /etc/libvirt/
cp -varf $BKPDIR/etc/libvirt/storage /etc/libvirt/

# update network-scripts
echo "    update network-scripts"

echo "     remove old network-scripts"
# remove old network-scripts
rm -rf /etc/sysconfig/network-scripts/ifcfg-*

echo "     copy network-scripts from backup"
#  replace NM_CONTROLLED=no
NETWORK_SCRIPTS_DIR="/etc/sysconfig/network-scripts"
cp $BKPDIR/$NETWORK_SCRIPTS_DIR/ifcfg-* $NETWORK_SCRIPTS_DIR

echo "     set NM_CONTROLLED=no"
for f in `ls $NETWORK_SCRIPTS_DIR/ifcfg-*`;
do
    sed -i -e "/NM_CONTROLLED=.*/ d" -e "$ a NM_CONTROLLED=no" $f
done

#   disable NetworkManager
chkconfig NetworkManager off

echo "    copy mdamd and fstab"
cp -v $BKPDIR/etc/mdadm.conf /etc/mdadm.conf
cp -v $BKPDIR/etc/fstab /etc/fstab
 
echo "    update grub.conf"
ROOTDEVICE=`cat $BKPDIR/boot/grub/grub.conf | sed -n 's/^[^#]\+root=\(\S\+\).*/\1/p'`
BOOTHD=`cat $BKPDIR/boot/grub/grub.conf | sed -n 's/^[^#]\+root \((hd[[:digit:]]\+,[[:digit:]]\+)\).*/\1/p'`
MDUUID=`cat /etc/mdadm.conf | sed -n -e "s|.\+$ROOTDEVICE.\+uuid=\(\S\+\)|\1|ip"`

#vim /boot/grub/grub.conf
cp /boot/grub/grub.conf /boot/grub/grub.conf.bkp
sed -i -e "s|root=\S\+|root=$ROOTDEVICE|" -e "s|(hd[[:digit:]]\+,[[:digit:]]\+)|$BOOTHD|" -e "s/rd_MD_UUID=\S\+/rd_MD_UUID=$MDUUID/" /boot/grub/grub.conf
#vim /boot/grub/device.map
cp /boot/grub/device.map /boot/grub/device.map.bkp
cp $BKPDIR/boot/grub/device.map /boot/grub/device.map

for d in `cat /boot/grub/device.map | sed -n 's/(hd[[:digit:]]\+)[[:blank:]]\+\(\S\+\)/\1/p'`;
do
    echo "grub_install $d";
    grub-install $d
done

INITRD=`cat /boot/grub/grub.conf | sed -n 's/^[^#]\+initrd \/\([[:alpha:]]\+-\S\+\.img\)/\1/p'`;
KERNEL_VERSION=`cat /boot/grub/grub.conf | sed -n 's/^[^#]\+initrd \/[[:alpha:]]\+-\(\S\+\)\.img/\1/p'`

mv /boot/$INITRD /boot/$INITRD.bkp
echo " run dracut /boot/$INITRD $KERNEL_VERSION"
dracut /boot/$INITRD $KERNEL_VERSION

# recover database backup
echo " recover database backup"

#cp $BKPDIR/etc/sysconfig/etva-model.conf /etc/sysconfig/etva-model.conf 

rsync -Ptav $BKPDIR/etc/sysconfig/etva-vdaemon/ /etc/sysconfig/etva-vdaemon/

# TODO change MySQL password

service mysqld start

echo "   load backup $BACKUPFILE"
cp $BKPDIR/$BACKUPFILE $BACKUPFILE

cd /srv/etva-centralmanagement/
symfony propel:build-all --no-confirmation
symfony propel:data-load $BACKUPFILE

umount /bkp
rmdir /bkp
mkdir $ETVAISOSDIR

service mysqld stop

sync

echo " Now reboot your system."

#reboot

