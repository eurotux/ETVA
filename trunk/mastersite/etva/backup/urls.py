from django.conf.urls.defaults import *

# Uncomment the next two lines to enable the admin:
# from django.contrib import admin
# admin.autodiscover()

urlpatterns = patterns('',
    # Example:
        (r'^get/(?P<file>.*)/?', 'etva.backup.views.view'),
        (r'^new/?', 'etva.backup.views.new'),
	(r'^delete/(?P<backupId>[0-9]+)', 'etva.backup.views.delete'),
        (r'^(?P<backupId>[0-9]*)/?', 'etva.backup.views.show'),
        (r'^/?', 'etva.backup.views.show'),

)
