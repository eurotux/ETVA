from django.conf.urls.defaults import *

# Uncomment the next two lines to enable the admin:
from django.contrib import admin
import settings

from etva.lib.funcs import SECTION_GROUPS, SECTION_USERS, SECTION_BACKUPS

admin.autodiscover()

urlpatterns = patterns('',
    # Example:

    
    (r'^accounts/', include('etva.accounts.urls')),
    (r'^services/', include('etva.service.urls')),
    (r'^product/', include('etva.product.urls')),
    (r'^groups/', include('etva.groups.urls')),
    (r'^users/', include('etva.users.urls')),
    (r'^backups/', include('etva.backup.urls')),
# Language change
    (r'^i18n/', include('django.conf.urls.i18n')),
    (r'^noauth/?', 'django.views.generic.simple.direct_to_template', {'template': 'no_auth.html'}),
    
    (r'', include('etva.public.urls')),
    (r'^static/(?P<path>.*)$', 'django.views.static.serve',{'document_root': settings.MEDIA_ROOT}),
    # Uncomment the admin/doc line below to enable admin documentation:
    (r'^admin/doc/', include('django.contrib.admindocs.urls')),

    # Uncomment the next line to enable the admin:
    (r'^admin/', include(admin.site.urls)),
)
