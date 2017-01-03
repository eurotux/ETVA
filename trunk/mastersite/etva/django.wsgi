import os
import sys
import django


os.environ['DJANGO_SETTINGS_MODULE'] = 'settings'


sys.path.append('/var/www/vhosts/etva/etva')
sys.path.append('/var/www/vhosts/etva')
import django.core.handlers.wsgi
application = django.core.handlers.wsgi.WSGIHandler()

