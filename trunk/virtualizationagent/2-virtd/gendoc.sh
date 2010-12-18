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

/usr/bin/pod2wiki --style moinmoin Agent.pm > $POD_WIKI/Agent.wiki;

if [ ! -e "$POD_WIKI/Agent" ]; then
    mkdir -p $POD_WIKI/Agent;
fi

/usr/bin/pod2wiki --style moinmoin Agent/JSON.pm > $POD_WIKI/Agent/JSON.wiki;

/usr/bin/pod2wiki --style moinmoin Agent/SOAP.pm > $POD_WIKI/Agent/SOAP.wiki;

/usr/bin/pod2wiki --style moinmoin Client.pm > $POD_WIKI/Client.wiki;

if [ ! -e "$POD_WIKI/Client" ]; then
    mkdir -p $POD_WIKI/Client;
fi

/usr/bin/pod2wiki --style moinmoin Client/SOAP.pm > $POD_WIKI/Client/SOAP.wiki;

if [ ! -e "$POD_WIKI/Client/SOAP" ]; then
    mkdir -p $POD_WIKI/Client/SOAP;
fi

/usr/bin/pod2wiki --style moinmoin Client/SOAP/HTTP.pm > $POD_WIKI/Client/SOAP/HTTP.wiki;

/usr/bin/pod2wiki --style moinmoin VirtAgent.pm > $POD_WIKI/VirtAgent.wiki;

if [ ! -e "$POD_WIKI/VirtAgent" ]; then
    mkdir -p $POD_WIKI/VirtAgent;
fi

/usr/bin/pod2wiki --style moinmoin VirtAgent/Disk.pm > $POD_WIKI/VirtAgent/Disk.wiki;

/usr/bin/pod2wiki --style moinmoin VirtAgent/Network.pm > $POD_WIKI/VirtAgent/Network.wiki;

/usr/bin/pod2wiki --style moinmoin VirtAgentInterface.pm > $POD_WIKI/VirtAgentInterface.wiki;

/usr/bin/pod2wiki --style moinmoin VirtMachine.pm > $POD_WIKI/VirtMachine.wiki;

/usr/bin/pod2wiki --style moinmoin virtClient.pl > $POD_WIKI/virtClient.wiki;

/usr/bin/pod2wiki --style moinmoin virtd > $POD_WIKI/virtd.wiki;


# Gen man
echo "generating man doc";

if [ ! -e "$POD_MAN" ]; then
    mkdir -p $POD_MAN;
fi

if [ ! -e "$POD_MAN/man3" ]; then
    mkdir -p $POD_MAN/man3;
fi

/usr/bin/pod2man Agent.pm > $POD_MAN/man3/Agent.3pm;

/usr/bin/pod2man Agent/JSON.pm > $POD_MAN/man3/Agent::JSON.3pm;

/usr/bin/pod2man Agent/SOAP.pm > $POD_MAN/man3/Agent::SOAP.3pm;

/usr/bin/pod2man Client.pm > $POD_MAN/man3/Client.3pm;

/usr/bin/pod2man Client/SOAP.pm > $POD_MAN/man3/Client::SOAP.3pm;

/usr/bin/pod2man Client/SOAP/HTTP.pm > $POD_MAN/man3/Client::SOAP::HTTP.3pm;

/usr/bin/pod2man VirtAgent.pm > $POD_MAN/man3/VirtAgent.3pm;

/usr/bin/pod2man VirtAgent/Disk.pm > $POD_MAN/man3/VirtAgent::Disk.3pm;

/usr/bin/pod2man VirtAgent/Network.pm > $POD_MAN/man3/VirtAgent::Network.3pm;

/usr/bin/pod2man VirtAgentInterface.pm > $POD_MAN/man3/VirtAgentInterface.3pm;

/usr/bin/pod2man VirtMachine.pm > $POD_MAN/man3/VirtMachine.3pm;

/usr/bin/pod2man virtClient.pl > $POD_MAN/man3/virtClient.3pm;

/usr/bin/pod2man virtd > $POD_MAN/man3/virtd.3pm;
