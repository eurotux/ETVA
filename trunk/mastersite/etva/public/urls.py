from django.conf.urls.defaults import *

# Uncomment the next two lines to enable the admin:
# from django.contrib import admin
# admin.autodiscover()

urlpatterns = patterns('',
    # Example:
	(r'^logout/?', 'django.contrib.auth.views.logout',{'template_name':'logout.html'}),
	(r'^login/?', 'django.contrib.auth.views.login',{'template_name': 'login.html'}),
	(r'^/?$', 'etva.public.views.home'),

)
