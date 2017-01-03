from django.db import models
from django.contrib.auth.models import User
from datetime import datetime

from django.contrib import admin

# Create your models here.
class Product(models.Model):
    agent = models.ForeignKey(User,related_name='issuer')
    client = models.ForeignKey(User,related_name='claimer')
    serial = models.CharField(max_length=36)
    description = models.CharField(max_length=255)
    notes = models.CharField(max_length=255,blank=True)
    add_date = models.DateTimeField(default=datetime.now)
    activated = models.BooleanField() 
    activation_date =models.DateTimeField(null=True,blank=True)
    message = models.CharField(max_length=255,null=True,blank=True)
    
    def __unicode__(self):
        return self.serial

class ProductAdmin(admin.ModelAdmin):
    list_display = ('agent','client','serial','notes','add_date','activated','activation_date')

admin.site.register(Product,ProductAdmin)
