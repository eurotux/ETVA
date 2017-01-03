from django.conf.urls.defaults import *

# Uncomment the next two lines to enable the admin:
# from django.contrib import admin
# admin.autodiscover()

urlpatterns = patterns('',
    # Example:
	(r'^new/(?P<group>[0-9]+)/?', 'etva.users.views.new'),
	(r'^new/?', 'etva.users.views.new'),
	(r'^(?P<userId>[0-9]+)/?', 'etva.users.views.view'),
	(r'', 'etva.users.views.show'),

)
