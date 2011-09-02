all: prepare build compile iso

prepare:
	./prepare.sh

build:
	./build.sh

compile:
	./compile.sh

iso:
	sudo /usr/sbin/revisor --cli --model=c5-x86_64 --yes --kickstart-default --kickstart-include --install-dvd --kickstart=${PWD}/etva-ks.cfg
	./isobuild.sh /srv/revisor/c5-x86_64/iso/CentOS-5-x86_64-DVD.iso
	sudo rm -rf /srv/revisor/c5-*/iso/*.iso 2> /dev/null

clean:
	sudo rm -rf /var/lib/mock/etva* 2> /dev/null
	rm -rf /tmp/BUILD ${HOME}/logs ${HOME}/repositorio-etva ${HOME}/SRPMS ${HOME}/trunk 2> /dev/null

distclean: clean
	rm -f ${HOME}/etva.*.iso
