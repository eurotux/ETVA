from django import forms
from django.utils.translation import ugettext_lazy as _
from django.contrib.auth.models import Group



class productForm(forms.Form):
    serial = forms.CharField(label=_("Product serial"),widget=forms.HiddenInput(attrs={'size':'36'}))
    notes = forms.CharField(widget=forms.Textarea())
    


