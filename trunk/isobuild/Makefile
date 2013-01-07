all: prepare build compile iso repopush

prepare:
	./prepare.sh

build:
	./build.sh

compile:
	./compile.sh

iso:
	./iso.sh

repopush:
	./repopush.sh

clean:
	sudo rm -rf /var/lib/mock/etva* 2> /dev/null
	rm -rf /tmp/BUILD ${HOME}/logs ${HOME}/repositorio-etva ${HOME}/SRPMS ${HOME}/trunk 2> /dev/null

distclean: clean
	rm -f ${HOME}/etva.*.iso
