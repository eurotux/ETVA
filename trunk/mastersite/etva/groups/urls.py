from django.conf.urls.defaults import *

# Uncomment the next two lines to enable the admin:
# from django.contrib import admin
# admin.autodiscover()

urlpatterns = patterns('',
    # Example:
	(r'^new', 'etva.groups.views.new'),
	(r'^(?P<group>[\w]+)/?', 'etva.groups.views.view'),
	(r'', 'etva.groups.views.show'),

)
