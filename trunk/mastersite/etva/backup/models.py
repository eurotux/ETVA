from django.db import models
from django.contrib.auth.models import User

from django.contrib import admin
from etva.product.models import Product
from datetime import datetime

class EtvaAgent(models.Model):
    agent = models.CharField(max_length=128)
    name = models.CharField(max_length=255)

    def __unicode__(self):
        return self.name

class EtvaAgentAdmin(admin.ModelAdmin):
    list_display = ('agent','name')

admin.site.register(EtvaAgent,EtvaAgentAdmin)

#Create your models here.
class Backup(models.Model):
    user = models.ForeignKey(User)
    file = models.FileField(upload_to='backup')
    file_name = models.CharField(max_length=255)
    mime = models.CharField(max_length=255)
    size = models.IntegerField()
    notes = models.CharField(max_length=255,null=True,blank=True)
    date_created=models.DateTimeField(default=datetime.now)
    file = models.FileField(upload_to='backup')
    
    #extra
    #uuid = models.CharField(max_length=255)
    #etva_agent = models.ForeignKey(EtvaAgent)
    product = models.ForeignKey(Product)
    
    #serial_number = models.CharField(max_length=255)


class BackupAdmin(admin.ModelAdmin):
    list_display = ('user','file','file_name','notes','date_created')

admin.site.register(Backup,BackupAdmin)

