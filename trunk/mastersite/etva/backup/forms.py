from django import forms
from django.utils.translation import ugettext_lazy as _
from django.contrib.auth.models import Group

from models import EtvaAgent
from etva.product.models import Product

class UploadFileForm(forms.Form):
    serial_number = forms.ChoiceField(label=_("Serial number"),required=True)
    #etva_agent = forms.ModelChoiceField(required=True,queryset=EtvaAgent.objects.all(),empty_label=None)
    file  = forms.FileField()
    notes = forms.CharField(widget=forms.Textarea(),required=False)

