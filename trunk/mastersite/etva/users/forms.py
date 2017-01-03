from django import forms
from django.utils.translation import ugettext_lazy as _
from django.contrib.auth.models import Group



class userForm(forms.Form):
    user_name = forms.CharField(label=_("User name"))
    email = forms.EmailField(required=True)

    user_password = forms.CharField(widget=forms.PasswordInput(render_value=False),required=False)
    confirm_password = forms.CharField(widget=forms.PasswordInput(render_value=False),required=False)
    #group = forms.ModelChoiceField(required=True,queryset=Group.objects.all().exclude(name='admin').exclude(name='Agentes').exclude(name='clients'))

class userFormEdit(forms.Form):
    email = forms.EmailField(required=True)

    user_password = forms.CharField(widget=forms.PasswordInput(render_value=False),required=False)
    confirm_password = forms.CharField(widget=forms.PasswordInput(render_value=False),required=False)
    


