#!/bin/sh

while true;
do
    /srv/etva-centralmanagement/symfony etva:process-asynchronousjob
    sleep 1
done
