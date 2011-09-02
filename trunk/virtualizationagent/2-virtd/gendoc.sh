#!/bin/sh
# CMAR 26/06/2009
# cmar@eurotux.com
# generate doc 

POD_HTML="doc/html";
POD_WIKI="doc/wiki";
POD_MAN="doc/man";

# Gen POD 2 HTML
echo "generating html doc";

if [ ! -e "$POD_HTML" ]; then
    mkdir -p $POD_HTML;
fi

if [ ! -e "virtd.pod" ]; then
    ln -s virtd virtd.pod;
fi

/usr/bin/perl -MPod::Simple::HTMLBatch -e 'Pod::Simple::HTMLBatch::go' . $POD_HTML >/dev/null 2>&1

# Gen wiki
echo "generating wiki doc";

if [ ! -e "$POD_WIKI" ]; then
    mkdir -p $POD_WIKI;
fi

if [ ! -e "$POD_WIKI/ETVA/Agent" ]; then
    mkdir -p $POD_WIKI/ETVA/Agent;
fi

if [ ! -e "$POD_WIKI/ETVA/Client/SOAP" ]; then
    mkdir -p $POD_WIKI/ETVA/Client/SOAP;
fi

if [ ! -e "$POD_WIKI/VirtAgent" ]; then
    mkdir -p $POD_WIKI/VirtAgent;
fi

/usr/bin/pod2wiki --style moinmoin lib/ETVA/Agent.pm > $POD_WIKI/ETVA/Agent.wiki;

/usr/bin/pod2wiki --style moinmoin lib/ETVA/Agent/JSON.pm > $POD_WIKI/ETVA/Agent/JSON.wiki;

/usr/bin/pod2wiki --style moinmoin lib/ETVA/Agent/SOAP.pm > $POD_WIKI/ETVA/Agent/SOAP.wiki;

/usr/bin/pod2wiki --style moinmoin lib/ETVA/Client.pm > $POD_WIKI/ETVA/Client.wiki;

/usr/bin/pod2wiki --style moinmoin lib/ETVA/Client/SOAP.pm > $POD_WIKI/ETVA/Client/SOAP.wiki;

/usr/bin/pod2wiki --style moinmoin lib/ETVA/Client/SOAP/HTTP.pm > $POD_WIKI/ETVA/Client/SOAP/HTTP.wiki;

/usr/bin/pod2wiki --style moinmoin lib/VirtAgent.pm > $POD_WIKI/VirtAgent.wiki;

/usr/bin/pod2wiki --style moinmoin lib/VirtAgent/Disk.pm > $POD_WIKI/VirtAgent/Disk.wiki;

/usr/bin/pod2wiki --style moinmoin lib/VirtAgent/Network.pm > $POD_WIKI/VirtAgent/Network.wiki;

/usr/bin/pod2wiki --style moinmoin lib/VirtAgentInterface.pm > $POD_WIKI/VirtAgentInterface.wiki;

/usr/bin/pod2wiki --style moinmoin lib/VirtMachine.pm > $POD_WIKI/VirtMachine.wiki;

/usr/bin/pod2wiki --style moinmoin client.pl > $POD_WIKI/client.wiki;

/usr/bin/pod2wiki --style moinmoin virtd > $POD_WIKI/virtd.wiki;


# Gen man
echo "generating man doc";

if [ ! -e "$POD_MAN" ]; then
    mkdir -p $POD_MAN;
fi

if [ ! -e "$POD_MAN/man3" ]; then
    mkdir -p $POD_MAN/man3;
fi

/usr/bin/pod2man lib/ETVA/Agent.pm > $POD_MAN/man3/ETVA::Agent.3pm;

/usr/bin/pod2man lib/ETVA/Agent/JSON.pm > $POD_MAN/man3/ETVA::Agent::JSON.3pm;

/usr/bin/pod2man lib/ETVA/Agent/SOAP.pm > $POD_MAN/man3/ETVA::Agent::SOAP.3pm;

/usr/bin/pod2man lib/ETVA/Client.pm > $POD_MAN/man3/ETVA::Client.3pm;

/usr/bin/pod2man lib/ETVA/Client/SOAP.pm > $POD_MAN/man3/ETVA::Client::SOAP.3pm;

/usr/bin/pod2man lib/ETVA/Client/SOAP/HTTP.pm > $POD_MAN/man3/ETVA::Client::SOAP::HTTP.3pm;

/usr/bin/pod2man lib/VirtAgent.pm > $POD_MAN/man3/VirtAgent.3pm;

/usr/bin/pod2man lib/VirtAgent/Disk.pm > $POD_MAN/man3/VirtAgent::Disk.3pm;

/usr/bin/pod2man lib/VirtAgent/Network.pm > $POD_MAN/man3/VirtAgent::Network.3pm;

/usr/bin/pod2man lib/VirtAgentInterface.pm > $POD_MAN/man3/VirtAgentInterface.3pm;

/usr/bin/pod2man lib/VirtMachine.pm > $POD_MAN/man3/VirtMachine.3pm;

/usr/bin/pod2man client.pl > $POD_MAN/man3/virtClient.3pm;

/usr/bin/pod2man virtd > $POD_MAN/man3/virtd.3pm;
