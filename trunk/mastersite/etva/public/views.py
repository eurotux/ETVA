from django.shortcuts import render_to_response
from django.contrib.auth.decorators import login_required
from django.contrib.auth.views import login
from django.contrib.auth.models import User
from django.template import RequestContext
# Create your views here.

#@login_required
def login(request):
    return render_to_response('login.html')

#@user_passes_test(lambda u: u.has_perm('polls.can_vote'))
@login_required
def home(request):
    
 
    return render_to_response('home.html',context_instance=RequestContext(request))
