#pp -vvv -a primaveraconsole.exe -a /bin/sh -l /usr/bin/cygperl5_10.dll -l /usr/bin/cygwin1.dll -l /usr/bin/cyggcc_s-1.dll -l /usr/bin/cygssp-0.dll -l /usr/bin/cygcrypt-0.dll -z -M SOAP::Lite::Deserializer::XMLSchema2001 -o primaveraagentd.exe -c primaveraagentd.pl

#cp /usr/bin/cygperl5_10.dll .
#cp /usr/bin/cygwin1.dll .
#cp /usr/bin/cyggcc_s-1.dll .
#cp /usr/bin/cygssp-0.dll .
#cp /usr/bin/cygcrypt-0.dll .

/cygdrive/c/Program\ Files/NSIS/makensis.exe primaveraagentd.nsi
