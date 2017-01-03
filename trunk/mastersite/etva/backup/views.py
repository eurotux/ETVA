from django.template import RequestContext
from django.http import HttpResponseRedirect, HttpResponse
from django.http import Http404

from django.shortcuts import render_to_response
from django.contrib.auth.decorators import login_required
from django.core.paginator import Paginator, InvalidPage, EmptyPage
from django.contrib.auth.models import User, Group

from forms import UploadFileForm
from models import Backup
import simplejson as json
from django import forms

from etva.settings import MEDIA_ROOT
from etva.settings import AGENTS_GROUP, CLIENTS_GROUP
from etva.product.models import Product
from etva.lib.funcs import SECTION_GROUPS, SECTION_USERS, SECTION_BACKUPS
from settings import PAGING_RESULTS

# Create your views here.

@login_required
def show(request,backupId):
    """docstring for show"""
    #get user backups
    userGroups = request.user.groups.all().values_list('name',flat=True)

    backupOwner=None
            
    #import rpdb2
    #rpdb2.start_embedded_debugger("1")
    
    if ( (AGENTS_GROUP in userGroups) | (request.user.is_superuser) ):
        backups = Backup.objects.filter(user__groups__in=request.user.groups.exclude(name=AGENTS_GROUP).values('id')).distinct().order_by('-date_created')
    else:
        backups = Backup.objects.filter(user=request.user).order_by('-date_created')

    if backupId:
        backupOwner = User.objects.get(pk=backupId)
        backups = backups.filter(user=backupOwner)

    paginator = Paginator(backups, PAGING_RESULTS) # Show PAGING_RESULTS per page

    # Make sure page request is an int. If not, deliver first page.
    try:
        page = int(request.GET.get('page', '1'))
    except ValueError:
        page = 1

    # If page request (9999) is out of range, deliver last page of results.
    try:
        backups = paginator.page(page)
    except (EmptyPage, InvalidPage):
        backups = paginator.page(paginator.num_pages)
    
    return render_to_response('backup/show.html',
            {
                'section':SECTION_BACKUPS,
                'backups':backups,
                'backupOwner':backupOwner,
                },context_instance=RequestContext(request))

@login_required
def new(request):
    errorMessage = None

    activatedKeys = Product.objects.filter(client=request.user,activated=True).values_list('serial','serial')

    if (request.POST):
        form = UploadFileForm(request.POST, request.FILES)
        form.fields['serial_number'] = forms.ChoiceField(choices = activatedKeys )
       
        if form.is_valid():
            #import rpdb2
            #rpdb2.start_embedded_debugger("1")
            b = Backup()
            b.user = request.user
            b.file=form.cleaned_data['file']

            b.mime = request.FILES['file'].content_type
            b.size = request.FILES['file'].size
            #b.etva_agent = form.cleaned_data['etva_agent']

            b.file_name = (form.cleaned_data['file']).name
            b.notes=form.cleaned_data['notes']
            b.product=Product.objects.get(serial=form.cleaned_data['serial_number'],client=request.user,activated=True)
            b.save()
            return HttpResponseRedirect('/backups/') # Redirect after POST
    else:
        form = UploadFileForm()
        form.fields['serial_number'] = forms.ChoiceField(choices = activatedKeys) 


    
    return render_to_response('backup/new.html',
            {
                'form':form,
                'section':SECTION_BACKUPS,
                'errorMessage':errorMessage,
                'activatedKeys':activatedKeys,
                },context_instance=RequestContext(request))

            
@login_required
def view(request,file): #file is id
    #try:

    #TODO IF NOT agente
    
    #get user backups
    userGroups = request.user.groups.all().values_list('name',flat=True)

    if AGENTS_GROUP in userGroups:
        backups = Backup.objects.get(id=file)
    else:
        backups = Backup.objects.get(user=request.user,id=file)

    f = open(MEDIA_ROOT+backups.file.name, 'r')
    #except:
        #raise Http404
    
    response = HttpResponse(f, mimetype=backups.mime)
    response['Content-Disposition'] = 'attachment; filename='+backups.file_name
    return response
#/*}}}*/

@login_required
def delete(request,backupId):
    allowGroups = request.user.groups.exclude(name=CLIENTS_GROUP).exclude(name=AGENTS_GROUP).all()

    p = Backup.objects.get(id=backupId, user__groups__in=allowGroups)
    p.delete()
    return HttpResponseRedirect('/backups/') # Redirect after POST


