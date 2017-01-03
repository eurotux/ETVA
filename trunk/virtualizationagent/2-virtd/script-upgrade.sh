#!/bin/sh

checkexpiredcertificates(){
    echo "do checkexpiredcertificates";

    tnow=$(date +%s);

    ca_keyfile="/etc/pki/CA/private/cakey.pem";
    ca_certfile="/etc/pki/CA/cacert.pem";
    tmpca_certfile="/etc/pki/CA/cacert.pem-new";

    ca_expdate=$(certtool -i --infile $ca_certfile  | grep "Not After:" | cut -d ":" -f 2-);
    ca_texp=$(date --date="$ca_expdate" +%s);
    ca_tnow=$(date +%s);

    if [ "$ca_tnow" -ge "$ca_texp" ];
    then
        echo "CA Certificate '$ca_certfile' expires at '$ca_expdate'. Need to update certificate manually with following command and copy for each node:";
        echo "  certtool --update-certificate \\";
        echo "           --load-ca-privkey $ca_keyfile \\";
        echo "           --load-ca-certificate $ca_certfile \\";
        echo "           --load-certificate $ca_certfile \\";
        echo "           --outfile $tmpca_certfile;";
        echo "  cp $tmpca_certfile $ca_certfile;"
        exit 1;
    fi

    tmpserver_certfile="/etc/pki/libvirt/servercert.pem-new";
    server_certfile="/etc/pki/libvirt/servercert.pem";

    server_expdate=$(certtool -i --infile $server_certfile  | grep "Not After:" | cut -d ":" -f 2-);
    server_texp=$(date --date="$server_expdate" +%s);
    server_tnow=$(date +%s);

    tmpinfo="/var/tmp/server_cert.info";
    echo "expiration_days = 730" > $tmpinfo;

    if [ "$server_tnow" -ge "$server_texp" ];
    then
        echo "Server Certificate '$server_certfile' expires at '$server_expdate' and will be update.";
        certtool --update-certificate \
                 --load-ca-privkey $ca_keyfile \
                 --load-ca-certificate $ca_certfile \
                 --load-certificate $server_certfile \
                 --template $tmpinfo \
                 --outfile $tmpserver_certfile;
        cp $tmpserver_certfile $server_certfile;
    fi

    echo "Certificates are updated."
}

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

fixhosts(){
     perl -ni -e 'print if( !$CHECK{$_}++ );' /etc/hosts
}

fixhosts;
verifycertificates;
fixlibvirtguestsconf;
checkexpiredcertificates;

exit $?;

