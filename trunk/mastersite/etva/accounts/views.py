from django.shortcuts import render_to_response
from django.contrib.auth.decorators import login_required
from django.template import RequestContext


@login_required
def profile(request):
    #import rpdb2
    #rpdb2.start_embedded_debugger("1")

    var = request.user
	
    return render_to_response('accounts/profile.html',context_instance=RequestContext(request))
