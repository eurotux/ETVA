#!/bin/sh

verifycertificates(){
    echo "do verifycertificates";

    VIRTPKIVAL_CA_CERT=`virt-pki-validate | grep "The CA certificate and the server certificate do not match"`;
    VIRTPKIVAL_SRV_CERT=`virt-pki-validate | grep "The server certificate does not seem to match the host name"`;

    ORGANIZATION=`egrep "Organization =" /etc/sysconfig/etva-vdaemon/virtd.conf | awk -F= {' print $2 '} | sed -e 's/^ *//'`;
    NAME=`egrep "name =" /etc/sysconfig/etva-vdaemon/virtd.conf | awk -F= {' print $2 '} | sed -e 's/^ *//'`;

    if [ "$VIRTPKIVAL_CA_CERT" != "" ]; then
        echo "need build CA Certs with ORGANIZATION=$ORGANIZATION and NAME=$NAME";
        perl -METVA::Utils -e "ETVA::Utils::gencerts(\"$ORGANIZATION\",\"$NAME\",1,1);"
    elif [ "$VIRTPKIVAL_SRV_CERT" != "" ]; then
        echo "need build SRV Certs with ORGANIZATION=$ORGANIZATION and NAME=$NAME";
        perl -METVA::Utils -e "ETVA::Utils::gencerts(\"$ORGANIZATION\",\"$NAME\",1);"
    fi
    
}
fixlibvirtguestsconf(){
    perl -pi -e 's/#ON_BOOT=start/ON_BOOT=ignore/' /etc/sysconfig/libvirt-guests
    perl -pi -e 's/#ON_SHUTDOWN=suspend/ON_SHUTDOWN=shutdown/' /etc/sysconfig/libvirt-guests
    perl -pi -e 's/#SHUTDOWN_TIMEOUT=0/SHUTDOWN_TIMEOUT=120/' /etc/sysconfig/libvirt-guests
}

verifycertificates;
fixlibvirtguestsconf;

exit $?;

