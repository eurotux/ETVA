from django.template import RequestContext
from django.http import HttpResponseRedirect
from django.shortcuts import render_to_response, get_object_or_404

from django.shortcuts import render_to_response
from django.contrib.auth.decorators import login_required
from django.contrib.auth.models import User, Group
from django.utils.translation import ugettext as _
from django.contrib.auth.decorators import user_passes_test
from django.core.paginator import Paginator, InvalidPage, EmptyPage

from etva.lib.funcs import SECTION_USERS
from etva.settings import AGENTS_GROUP, CLIENTS_GROUP
from settings import PAGING_RESULTS

from forms import userForm, userFormEdit
# Create your views here.
@login_required
@user_passes_test(lambda u: u.has_perm('auth.add_group'),login_url='/noauth/')
def show(request):

    myGroups = request.user.groups.exclude(name=AGENTS_GROUP)

    users = User.objects.select_related(depth=3).filter(groups__in=myGroups).exclude(username=request.user.username).distinct().exclude(username='admin')
    #users = User.objects.all()
    
    paginator = Paginator(users, PAGING_RESULTS) # Show PAGING_RESULTS per page

    # Make sure page request is an int. If not, deliver first page.
    try:
        page = int(request.GET.get('page', '1'))
    except ValueError:
        page = 1

    # If page request (9999) is out of range, deliver last page of results.
    try:
        users = paginator.page(page)
    except (EmptyPage, InvalidPage):
        users = paginator.page(paginator.num_pages)

    

    return render_to_response('users/show.html',
            {
                'users':users,
                'CLIENTS_GROUP':CLIENTS_GROUP,
                'AGENTS_GROUP':AGENTS_GROUP,
                'section':SECTION_USERS,
                },context_instance=RequestContext(request))

@login_required
@user_passes_test(lambda u: u.has_perm('auth.add_group'),login_url='/noauth/')
def new(request,group=None):

    myGroups = request.user.groups.exclude(name='agents') #user is agent
    errorMessage = None
    if (request.POST):
        form = userForm(request.POST)
       
        if form.is_valid():
            if (User.objects.filter(username=form.cleaned_data['user_name']).count()>0):
                errorMessage=_("User already exists")
            else:
                u = User.objects.create_user(form.cleaned_data['user_name'], form.cleaned_data['email'], form.cleaned_data['user_password'])
                u.groups.add(request.POST['group'])
                userGrp = Group.objects.get(name=CLIENTS_GROUP)
                u.groups.add(userGrp)
                u.save()
                return HttpResponseRedirect('/users/'+u.username) # Redirect after POST




    else:
	if (group):
	        form = userForm(initial={'group':group})
		group=int(group)
	else:
	        form = userForm()

    selGroup = request.user.groups.exclude(name__in=[AGENTS_GROUP,CLIENTS_GROUP])
    #import rpdb2
    #rpdb2.start_embedded_debugger("1")
    
    return render_to_response('users/new.html',
            {
                'myGroups':myGroups,
                'form':form,
                'group':group,
                'section':SECTION_USERS,
                'selGroup':selGroup,
                'errorMessage':errorMessage,
                },context_instance=RequestContext(request))

@login_required
@user_passes_test(lambda u: u.has_perm('auth.add_group'),login_url='/noauth/')
def view(request,userId):

    allowGroups = request.user.groups.all()
    #allowGroups = request.user.groups.exclude(name=CLIENTS_GROUP).exclude(name=AGENTS_GROUP).all()

    #u = User.objects.get(id=userId,groups__in=allowGroups)
    if request.user.is_superuser:
        u = get_object_or_404(User,id=userId)
    else:
        u = get_object_or_404(User,id=userId,groups__in=allowGroups)


    errorMessage = ""
    users=None
    
    usableGroup = u.groups.exclude(name=CLIENTS_GROUP)[0] #shoul ony be 1
    #form = userForm(initial={'user_name':u.username,'email':u.email,'group':usableGroup.id})
    form = userFormEdit(initial={'email':u.email})
    form.is_valid()

    selGroup = request.user.groups.exclude(name__in=[AGENTS_GROUP,CLIENTS_GROUP])
    
    if (request.POST):
        form = userFormEdit(request.POST)
        
        if form.is_valid():
            #u.user_name=form.cleaned_data['user_name']
            u.email = form.cleaned_data['email']
            #import rpdb2
            #rpdb2.start_embedded_debugger("1")

            if (form.cleaned_data['user_password']):
                if (form.cleaned_data['user_password'] == form.cleaned_data['confirm_password']):
                    u.set_password(form.cleaned_data['user_password'])
                else:
                    errorMessage=_("Password mismatch")

            u.save()
            return HttpResponseRedirect('/users/')#+u.username) # Redirect after POST/*}}}*/

    showButtons=True
    if (u.groups.filter(name=AGENTS_GROUP).count()>0):
        showButtons = False
    


    return render_to_response('users/view.html',
            {
                'showButtons':showButtons,
                'selUser':u,
                'group':usableGroup.id,
                'form':form,
                'section':SECTION_USERS,
                'selGroup':selGroup,
                'errorMessage':errorMessage,
                },context_instance=RequestContext(request))
