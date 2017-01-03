#!/bin/sh

django-admin makemessages -a && django-admin compilemessages

poedit conf/locale/pt/LC_MESSAGES/django.po &&>/dev/null
django-admin makemessages -a && django-admin compilemessages
