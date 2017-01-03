from django.template import RequestContext
from django.http import HttpResponseRedirect

from django.shortcuts import render_to_response
from django.contrib.auth.decorators import login_required
from django.contrib.auth.models import User, Group
from django.utils.translation import ugettext as _
from django.contrib.auth.decorators import user_passes_test
from django import forms

from etva.lib.funcs import SECTION_GROUPS
from etva.settings import AGENTS_GROUP, CLIENTS_GROUP

from forms import groupForm
# Create your views here.

@login_required
@user_passes_test(lambda u: u.has_perm('auth.add_group'),login_url='/noauth/')
def show(request):

    #if request.user.is_superuser:
    #    myGroups = request.user.groups.exclude(name=AGENTS_GROUP).order_by('name')
    #else:
    #    myGroups = request.user.groups.filter(group in (request.user.groups)).exclude(name=AGENTS_GROUP).order_by('name')
    myGroups = request.user.groups.exclude(name=AGENTS_GROUP).order_by('name')

    #remove some?

    return render_to_response('groups/show.html',
            {
                'myGroups':myGroups,
                'section':SECTION_GROUPS,
                },context_instance=RequestContext(request))

@login_required
@user_passes_test(lambda u: u.has_perm('auth.add_group'),login_url='/noauth/')
def new(request):

    myGroups = request.user.groups.exclude(name='agents') #user is agent
    errorMessage=""

    agents = User.objects.filter(groups__name=AGENTS_GROUP)
    
    if (request.user.groups.filter(name=AGENTS_GROUP).count()>0):
        agents = User.objects.filter(id=request.user.id)

    if (request.POST):
        form = groupForm(request.POST)
        form.fields['agent'] = forms.ModelChoiceField(required=True,queryset=agents,empty_label=None)
       
        if form.is_valid():
            otherGroups = Group.objects.filter(name=form.cleaned_data["name"]) #user is agent
            if otherGroups.count()>0:
                errorMessage=_("Group already exists")
            else:
                group = Group()
                group.name=form.cleaned_data["name"]
                group.save()
                
                #add targgueted agent too
                ag = form.cleaned_data["agent"]
                ag.groups.add(group)
                ag.save()

                #add admin
                amin = User.objects.get(username='admin')
                amin.groups.add(group)
                amin.save()
               
                return HttpResponseRedirect('/groups/') # Redirect after POST
    else:
        form = groupForm()
        form.fields['agent'] = forms.ModelChoiceField(required=True,queryset=agents,empty_label=None)



    return render_to_response('groups/new.html',
            {
                'agents':agents,
                'myGroups':myGroups,
                'form':form,
                'section':SECTION_GROUPS,
                'errorMsg':errorMessage,
                },context_instance=RequestContext(request))

@login_required
@user_passes_test(lambda u: u.has_perm('auth.add_group'),login_url='/noauth/')
def view(request,group):


    myGroups = request.user.groups.exclude(name=CLIENTS_GROUP).exclude(name=AGENTS_GROUP)

    selGroup = Group.objects.filter(id=group) 

    errorMessage = ""
    users=None
    if (selGroup.count()==0):
        errorMessage = _("Cant't find group")
    else:
        #import rpdb2
        #rpdb2.start_embedded_debugger("1")
        if selGroup[0] not in myGroups:
            errorMessage = _("You don't this own group")
        else:
            users = User.objects.filter(groups__id=group).exclude(username=request.user.username).exclude(username='admin')





    #remove some?

    return render_to_response('groups/view.html',
            {
                'myGroups':myGroups,
                'groupName':selGroup[0].name,
                'group':group,
                'users':users,
                'section':SECTION_GROUPS,
                'errorMessage':errorMessage,
                },context_instance=RequestContext(request))
