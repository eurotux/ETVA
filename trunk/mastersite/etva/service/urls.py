from django.conf.urls.defaults import *

# Uncomment the next two lines to enable the admin:
# from django.contrib import admin
# admin.autodiscover()

urlpatterns = patterns('',
    # Example:
	(r'^login', 'etva.service.views.mylogin'),
	(r'^upload', 'etva.service.views.uploadFile'),
	(r'^download/?(?P<file_id>[0-9]*)', 'etva.service.views.view'),
	(r'^list', 'etva.service.views.showBackups'),
	(r'^delete', 'etva.service.views.delete'),
	(r'^updatestatus', 'etva.service.views.updateStatusMessage')

)
