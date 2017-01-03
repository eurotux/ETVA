from django import forms
from django.utils.translation import ugettext_lazy as _
from django.contrib.auth.models import User, Group
from etva.settings import AGENTS_GROUP#, CLIENTS_GROUP

class groupForm(forms.Form):
    name = forms.CharField(label=_("Group name"))
    agent = forms.ModelChoiceField(required=True,queryset=User.objects.filter(groups__name=AGENTS_GROUP),empty_label=None)

