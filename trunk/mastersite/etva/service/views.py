from django.contrib.auth import authenticate, login
from django.contrib.auth import logout
from django.http import HttpResponse, HttpResponseBadRequest
from etva.backup.forms import UploadFileForm
from django.contrib.auth.decorators import login_required
from etva.settings import AGENTS_GROUP, CLIENTS_GROUP
from etva.settings import MEDIA_ROOT, BASE_URL
from django.utils.html import strip_tags

import simplejson as json
from django import forms
from etva.backup.models import Backup
from etva.product.models import Product
from datetime import datetime

# Create your views here.
def mylogin(request):
    """docstring for mylogin"""
    if request.user.is_authenticated():
        logout(request)
    
    parameters = request.GET
    if not parameters:
        parameters = request.POST

    if parameters:
        username = parameters['username']
        password = parameters['password']
        user = authenticate(username=username, password=password)
        
        if user is not None:
            if user.is_active:
                resp = dict()
                response = dict()
                

                resp['success'] = True
                response['response'] = "You logged in"
                response['user'] = user.username
                resp['last_login'] = str(user.last_login)
                login(request, user)

                serial = None

                #import rpdb2
                #rpdb2.start_embedded_debugger("1")
                if parameters.has_key('serial_number'):
                    #pick one
                    serials = Product.objects.filter(client=request.user,serial=parameters['serial_number'], activated=True)#.values_list('id','serial')

                    if serials.count()>0:
                        serial = serials[0]
                    else:
                        return HttpResponseBadRequest(json.dumps({'error':"Invalid serial number: "+parameters['serial_number']}), mimetype="application/json")
                
                #if parameters['serial_number']!='':

                else:
                    #check existence:
                    serials = Product.objects.filter(client=request.user, activated=False)#.values_list('id','serial')
                    if serials.count()>0:
                        serial = serials[0]
                        serial.activated=True
                        serial.activation_date = datetime.now()
                        serial.save()
                    else:
                        return HttpResponseBadRequest(json.dumps({'error':"No available serial numbers for this client"}), mimetype="application/json")


                
                
                

                #all ok!
                #check for description:
                if parameters.has_key('description'):
                    serial.description = parameters['description']
                    serial.save()
                    response['description'] = parameters['description']

                 
                response['serial_number'] =serial.serial #pick one of the clients
                resp['response'] = response
                
                return HttpResponse(json.dumps(resp), mimetype="application/json")
            else:
                return HttpResponseBadRequest(json.dumps({'error':"You account is deactivated"}), mimetype="application/json")


            
        response = HttpResponse()
        response.status_code = 401
        response['WWW-Authenticate'] = 'Basic realm="%s:%s' % (
        request.META["SERVER_NAME"], request.META["SERVER_PORT"])
        
        return response
    else:
        #return render_to_response('services/login.html')
        if not request.user.is_authenticated():
                response = HttpResponse()
                response.status_code = 401
                response['WWW-Authenticate'] = 'Basic realm="%s:%s' % (
                request.META["SERVER_NAME"], request.META["SERVER_PORT"])

                return response
        else:
                return HttpResponse("You are authed", mimetype="text/plain")

#@login_required
def uploadFile(request):
    #import rpdb2
    #rpdb2.start_embedded_debugger("1")
    parameters = request.GET
    if not parameters:
        parameters = request.POST

    if parameters:
        username = parameters['username']
        password = parameters['password']
        request.user = authenticate(username=username, password=password)

    if (not request.user): 
            response = HttpResponse()
            response.status_code = 401
            response['WWW-Authenticate'] = 'Basic realm="%s:%s' % (
            request.META["SERVER_NAME"], request.META["SERVER_PORT"])

            return response
    if (not request.user.is_authenticated()):
            response = HttpResponse()
            response.status_code = 401
            response['WWW-Authenticate'] = 'Basic realm="%s:%s' % (
            request.META["SERVER_NAME"], request.META["SERVER_PORT"])

            return response

    errorMessage = None
    response = dict()
    activatedKeys = Product.objects.filter(client=request.user,activated=True).values_list('serial','serial')
    if (request.POST):
        form = UploadFileForm(request.POST, request.FILES)
        form.fields['serial_number'] = forms.ChoiceField(choices = activatedKeys )
       
        if form.is_valid():
            b = Backup()
            b.user = request.user
            b.file=form.cleaned_data['file']

            b.mime = request.FILES['file'].content_type
            b.size = request.FILES['file'].size
            #b.etva_agent = form.cleaned_data['etva_agent']

            b.file_name = (form.cleaned_data['file']).name
            b.notes=form.cleaned_data['notes']
            b.product= Product.objects.get(serial=form.cleaned_data['serial_number'],client=request.user,activated=True)
            b.save()

            response['success']=True
            response['response']={'size':b.size,'mime':b.mime}
        else:
            response['success']=False
            s=""
            s2=""
            for resp in form.errors.keys():
                s += resp +":" + str(form.errors[resp])+", "
                s2+="field "+resp+": "+ strip_tags(str(form._errors[resp])) +" "

            response['response']={
                    'error':'form not valid',
                    'fields':s,
                    'errorMessage':s2}
    else:
            s = []
            serials = Product.objects.filter(client=request.user, activated=True).values_list('serial','serial')
            for ser in serials:
                s+=[str(ser)]
            form = UploadFileForm()
            form.fields['serial_number'] = forms.ChoiceField(choices = activatedKeys )
            response['success']=False
            response['response']={
                    'error':"No post parameters",
                    'username':request.user.username,
                    'required_fields':form.fields.keys(),
                    'valid client serials':s}
    
    return HttpResponse(json.dumps(response), mimetype="application/json")


#@login_required
def showBackups(request):
    if not request.user.is_authenticated():
            response = HttpResponse()
            response.status_code = 401
            response['WWW-Authenticate'] = 'Basic realm="%s:%s' % (
            request.META["SERVER_NAME"], request.META["SERVER_PORT"])

            return response
    """docstring for show"""
    #get user backups
    userGroups = request.user.groups.all().values_list('name',flat=True)
            
    #import rpdb2
    #rpdb2.start_embedded_debugger("1")
    
    if (AGENTS_GROUP in userGroups):
        backups = Backup.objects.filter(user__groups__in=request.user.groups.exclude(name=AGENTS_GROUP).values('id')).distinct().order_by('-date_created')
    else:
        backups = Backup.objects.filter(user=request.user).order_by('-date_created')

    if (request.GET):
        if (request.GET.has_key('serial_number')):
            backups= backups.filter(product__serial=request.GET['serial_number'])

   
    items = []
    for b in backups:

        items+= [{
            'id':b.id,
            'url':"http://"+BASE_URL+"/services/download/"+str(b.id),
            'file_name':b.file_name,
            'mime':b.mime,
            'size':b.size,
            'notes':b.notes,
            'date_created':str(b.date_created),
            'product_id':b.product.serial,
            'description':b.product.description

                }]
    
    return HttpResponse(json.dumps(items), mimetype="application/json")

#@login_required
def view(request,file_id=None): #file is id
    if not request.user.is_authenticated():
            response = HttpResponse()
            response.status_code = 401
            response['WWW-Authenticate'] = 'Basic realm="%s:%s' % (
            request.META["SERVER_NAME"], request.META["SERVER_PORT"])

            return response
    #try:

    #import rpdb2
    #rpdb2.start_embedded_debugger("1")
    #TODO IF NOT agente
    if not file_id:
        file_id = request.POST['id']

    
    #get user backups
    userGroups = request.user.groups.all().values_list('name',flat=True)

    if AGENTS_GROUP in userGroups:
        backups = Backup.objects.get(id=file_id)
    else:
        backups = Backup.objects.get(user=request.user,id=file_id)

    f = open(MEDIA_ROOT+backups.file.name, 'r')
    #except:
        #raise Http404
    
    response = HttpResponse(f, mimetype=backups.mime)
    response['Content-Disposition'] = 'attachment; filename='+backups.file_name
    response['Content-Length']=backups.size
    return response
#/*}}}*/

#@login_required
def delete(request):
    if not request.user.is_authenticated():
            response = HttpResponse()
            response.status_code = 401
            response['WWW-Authenticate'] = 'Basic realm="%s:%s' % (
            request.META["SERVER_NAME"], request.META["SERVER_PORT"])

            return response

    backupId = request.POST['id']
    p = Backup.objects.get(id=backupId)
    p.delete()
    return HttpResponse(json.dumps({'success':True}), mimetype="application/json")
 
def updateStatusMessage(request):
    if not request.user.is_authenticated():
            response = HttpResponse()
            response.status_code = 401
            response['WWW-Authenticate'] = 'Basic realm="%s:%s' % (
            request.META["SERVER_NAME"], request.META["SERVER_PORT"])

            return response

    serial = None
    parameters = request.GET
    if not parameters:
        parameters = request.POST

    if (parameters.has_key('serial_number')):
        serials = Product.objects.filter(client=request.user,serial=parameters['serial_number'], activated=True)

        if serials.count()>0:
            serial = serials[0]
            serial.message = parameters['message']
            serial.save()
        else:
            return HttpResponseBadRequest(json.dumps({'error':"Invalid serial number: "+parameters['serial_number']}), mimetype="application/json")
    else:
        return HttpResponseBadRequest(json.dumps({'error':"No valid serial number."}), mimetype="application/json")

    return HttpResponse(json.dumps({'success':True}), mimetype="application/json")

