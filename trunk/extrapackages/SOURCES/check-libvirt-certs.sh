#!/bin/sh

checkexpiredcertificates(){
    echo "do checkexpiredcertificates";

    tnow=$(date +%s);

    ca_keyfile="/etc/pki/CA/private/cakey.pem";
    ca_certfile="/etc/pki/CA/cacert.pem";
    tmpca_certfile="/etc/pki/CA/cacert.pem-new";

    tmpinfo="/var/tmp/server_cert.info";
    echo "expiration_days = 730" > $tmpinfo;

    ca_expdate=$(certtool -i --infile $ca_certfile  | grep "Not After:" | cut -d ":" -f 2-);
    ca_texp=$(date --date="$ca_expdate" +%s);
    ca_tnow=$(date +%s);

    if [ "$ca_tnow" -ge "$ca_texp" ];
    then
        echo "CA Certificate '$ca_certfile' expires at '$ca_expdate' and will be updated.";
        certtool --update-certificate \
                 --load-ca-privkey $ca_keyfile \
                 --load-ca-certificate $ca_certfile \
                 --load-certificate $ca_certfile \
                 --template $tmpinfo \
                 --outfile $tmpca_certfile;
        cp $tmpca_certfile $ca_certfile;
    fi

    tmpserver_certfile="/etc/pki/libvirt/servercert.pem-new";
    server_certfile="/etc/pki/libvirt/servercert.pem";

    server_expdate=$(certtool -i --infile $server_certfile  | grep "Not After:" | cut -d ":" -f 2-);
    server_texp=$(date --date="$server_expdate" +%s);
    server_tnow=$(date +%s);

    if [ "$server_tnow" -ge "$server_texp" ];
    then
        echo "Server Certificate '$server_certfile' expires at '$server_expdate' and will be updated.";
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

genCAcertificates(){
    if [ ! -d "/etc/pki/CA" ]; then
        mkdir -p /etc/pki/CA;
    fi

    if [ ! -e "/etc/pki/CA/private/cakey.pem" ]; then
        /usr/bin/certtool --generate-privkey > /etc/pki/CA/private/cakey.pem
    fi

    echo "cn = Eurotux
    ca
    cert_signing_key
    expiration_days = 730" >/var/tmp/ca.info

    /usr/bin/certtool --generate-self-signed --load-privkey /etc/pki/CA/private/cakey.pem --template /var/tmp/ca.info --outfile /etc/pki/CA/cacert.pem
}
genServercertificates(){
    if [ ! -d "/etc/pki/libvirt" ]; then
        mkdir -p /etc/pki/libvirt/private;
    fi

    if [ ! -e "/etc/pki/libvirt/private/serverkey.pem" ]; then
        /usr/bin/certtool --generate-privkey >/etc/pki/libvirt/private/serverkey.pem
        ln -s /etc/pki/libvirt/private/serverkey.pem /etc/pki/libvirt/private/clientkey.pem
    fi

    echo "organization = Eurotux
    cn = `hostname`
    tls_www_server
    tls_www_client
    encryption_key
    signing_key
    expiration_days = 730" > /var/tmp/server.info

    /usr/bin/certtool --generate-certificate --load-ca-privkey /etc/pki/CA/private/cakey.pem --load-ca-certificate /etc/pki/CA/cacert.pem --load-privkey /etc/pki/libvirt/private/serverkey.pem --template /var/tmp/server.info --outfile /etc/pki/libvirt/servercert.pem

    if [ ! -e "/etc/pki/libvirt/clientcert.pem" ]; then
        ln -s /etc/pki/libvirt/servercert.pem /etc/pki/libvirt/clientcert.pem
    fi
}
verifycertificates(){
    echo "do verifycertificates";

    VIRTPKIVAL_CA_CERT=`virt-pki-validate | grep "The CA certificate and the server certificate do not match"`;
    VIRTPKIVAL_SRV_CERT=`virt-pki-validate | grep "The server certificate does not seem to match the host name"`;

    if [ "$VIRTPKIVAL_CA_CERT" != "" ]; then
        echo "need to generate CA Certificates";
        genCAcertificates;
        genServercertificates;
    elif [ "$VIRTPKIVAL_SRV_CERT" != "" ]; then
        echo "need to generate Server Certificates";
        genServercertificates;
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

#fixhosts;
verifycertificates;
#fixlibvirtguestsconf;
checkexpiredcertificates;

exit $?;

