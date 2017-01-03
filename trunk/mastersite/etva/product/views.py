from django.shortcuts import render_to_response
from django.http import HttpResponseRedirect
from django.contrib.auth.decorators import login_required
from django.contrib.auth.models import User, Group
from django.template import RequestContext
from django.contrib.auth.decorators import user_passes_test
from django.utils.translation import ugettext as _
from django.core.paginator import Paginator, InvalidPage, EmptyPage

import uuid

from etva.product.models import Product
from forms import productForm

from etva.lib.funcs import SECTION_USERS, SECTION_PRODUCTS
from etva.settings import AGENTS_GROUP, CLIENTS_GROUP
from settings import PAGING_RESULTS

from datetime import datetime

# Create your views here.
@login_required
#@user_passes_test(lambda u: u.has_perm('auth.add_group'),login_url='/noauth/')
def showProduct(request):

    #get serials from this user:
    isAgent=False   
    if (AGENTS_GROUP in request.user.groups.values_list('name',flat=True)):

        myGroups = request.user.groups.exclude(name=AGENTS_GROUP)

        users = User.objects.select_related(depth=2).filter(groups__in=myGroups).exclude(username=request.user.username).distinct().exclude(username='admin')

        products = Product.objects.filter(client__in=users)
        isAgent = True
    else:
        products = Product.objects.filter(client=request.user)

    #if super user
    if (request.user.is_superuser):
        products = Product.objects.all()


    paginator = Paginator(products, PAGING_RESULTS) # Show PAGING_RESULTS per page

    # Make sure page request is an int. If not, deliver first page.
    try:
        page = int(request.GET.get('page', '1'))
    except ValueError:
        page = 1

    # If page request (9999) is out of range, deliver last page of results.
    try:
        products = paginator.page(page)
    except (EmptyPage, InvalidPage):
        products = paginator.page(paginator.num_pages)

    #import rpdb2
    #rpdb2.start_embedded_debugger("1")
    canSeeDelete = False
    if request.user.has_perm('auth.add_group'):
        canSeeDelete = True

    return render_to_response('product/show.html',
            {
                'canSeeDelete':canSeeDelete,
                'products':products,
                'section':SECTION_PRODUCTS,
                'isAgent':isAgent
                }
            ,context_instance=RequestContext(request))


@login_required
@user_passes_test(lambda u: u.has_perm('auth.add_group'),login_url='/noauth/')
def viewProduct(request,userId):

    user = User.objects.get(pk=userId)

    #get serials from this user:
    products = Product.objects.filter(client=user)
    paginator = Paginator(products, PAGING_RESULTS) # Show PAGING_RESULTS per page

    # Make sure page request is an int. If not, deliver first page.
    try:
        page = int(request.GET.get('page', '1'))
    except ValueError:
        page = 1

    # If page request (9999) is out of range, deliver last page of results.
    try:
        products = paginator.page(page)
    except (EmptyPage, InvalidPage):
        products = paginator.page(paginator.num_pages)
    
    canSeeDelete = False
    if request.user.has_perm('auth.add_group'):
        canSeeDelete = True

    return render_to_response('product/view.html',
            {
                'canSeeDelete':canSeeDelete,
                'useri':user,
                'products':products,
                'section':SECTION_USERS,
                }
            ,context_instance=RequestContext(request))


@login_required
@user_passes_test(lambda u: u.has_perm('auth.add_group'),login_url='/noauth/')
def newProduct(request,userId):
    
    user = User.objects.get(pk=userId)

    
    uui = uuid.uuid4()
    
    while (Product.objects.filter(serial=uui)).count()>0:
        uui = uuid.uuid4()





    errorMessage = None
    if (request.POST):
        form = productForm(request.POST)
       
        if form.is_valid():
            if (Product.objects.filter(serial=form.cleaned_data['serial']).count()>0):
                errorMessage=_("Serial already exists")
            else:
                p = Product()
                p.agent = request.user
                p.client = user
                p.serial=form.cleaned_data['serial']
                p.notes=form.cleaned_data['notes']
                p.add_date= datetime.now()
                p.save()
                return HttpResponseRedirect('/product/'+str(user.id)) # Redirect after POST




    else:
        form = productForm(initial={'serial':uui})

    return render_to_response('product/new.html',
            {
                'uuid':uui,
                'user':user,
                'form':form,
                'errorMessage':errorMessage,
                'section':SECTION_USERS,
                }
            ,context_instance=RequestContext(request))

@login_required
@user_passes_test(lambda u: u.has_perm('auth.add_group'),login_url='/noauth/')
def deleteProduct(request,productId):

    allowGroups = request.user.groups.exclude(name=CLIENTS_GROUP).exclude(name=AGENTS_GROUP).all()

    p = Product.objects.get(id=productId,groups__in=allowGroups)
    user = p.client
    p.delete()
    return HttpResponseRedirect('/product/'+str(user.id)) # Redirect after POST

