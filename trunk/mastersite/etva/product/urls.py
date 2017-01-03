from django.conf.urls.defaults import *

# Uncomment the next two lines to enable the admin:
# from django.contrib import admin
# admin.autodiscover()

urlpatterns = patterns('',
    # Example:
        
	(r'^(?P<userId>[0-9]+)', 'etva.product.views.viewProduct'),
	(r'^new/(?P<userId>[0-9]+)', 'etva.product.views.newProduct'),
	(r'^delete/(?P<productId>[0-9]+)', 'etva.product.views.deleteProduct'),
	(r'', 'etva.product.views.showProduct'),

)
