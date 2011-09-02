#!/bin/bash

if [ ! -d "/etc/pki/CA" ]; then
	mkdir -p /etc/pki/CA;
fi

/usr/bin/certtool --generate-privkey > /etc/pki/CA/cakey.pem

echo "organization = Eurotux
ca
cert_signing_key" >/var/tmp/ca.info

/usr/bin/certtool --generate-self-signed --load-privkey /etc/pki/CA/cakey.pem --template /var/tmp/ca.info --outfile /etc/pki/CA/cacert.pem

if [ ! -d "/etc/pki/libvirt" ]; then
	mkdir -p /etc/pki/libvirt/private;
fi

/usr/bin/certtool --generate-privkey >/etc/pki/libvirt/private/serverkey.pem

echo "organization = Eurotux
cn = `hostname`
tls_www_server
encryption_key
signing_key" > /var/tmp/server.info

/usr/bin/certtool --generate-certificate --load-ca-privkey /etc/pki/CA/cakey.pem --load-ca-certificate /etc/pki/CA/cacert.pem --load-privkey /etc/pki/libvirt/private/serverkey.pem --template /var/tmp/server.info --outfile /etc/pki/libvirt/servercert.pem

ln -s /etc/pki/libvirt/private/serverkey.pem /etc/pki/libvirt/private/clientkey.pem
ln -s /etc/pki/libvirt/servercert.pem /etc/pki/libvirt/clientcert.pem

