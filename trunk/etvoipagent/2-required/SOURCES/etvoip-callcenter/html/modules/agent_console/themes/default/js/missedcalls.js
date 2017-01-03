var extensionId = null;
function initialize_client_missed_calls(extension)
{
    extensionId = extension;

    // Start check missed calls callback
    setTimeout(do_check_missedcalls, 1);
}

function formatTimeSince(iSecs)
{
    var iHour = iMinutes = iSeconds = 0;
    iSeconds = Math.round(iSecs % 60); iSecs = (iSecs - iSeconds) / 60;
    iMinutes = Math.round(iSecs % 60); iSecs = (iSecs - iMinutes) / 60;
    iHour = Math.round(iSecs);
    //return sprintf('%02d:%02d:%02d', iHour, iMinutes, iSeconds);
    if( iHour > 0 )
        return iHour + ' hour(s) ' + iMinutes + ' min(s) ' + iSeconds + ' sec(s)';
    else if( iMinutes > 0 )
        return iMinutes + ' min(s) ' + iSeconds + ' sec(s)';
    return iSeconds + ' sec(s)';
}
function calcTimeSince(strDate)
{
    var dTimeSecs = (new Date() - new Date(strDate)) / 1000;
    return formatTimeSince(dTimeSecs);
}

function do_check_missedcalls()
{
	var startdate = $.datepicker.formatDate('dd+M+yy', new Date(new Date().getTime()-(60*60*24*1000)));
	var enddate = $.datepicker.formatDate('dd+M+yy', new Date());
    $.post('/cgi-bin/missedcalls?op=queue',
            function (response) {
                manageResponseMissedCalls(response);

                // call back at each 10secs
                setTimeout(do_check_missedcalls, 10000);
            }
        ).fail(function() {
                // try again 30 secs later
                setTimeout(do_check_missedcalls, 30000);
        });
}
function manageResponseMissedCalls(response)
{
    $('#llamadas_perdidas_count').text(0);
    $('#elastix-callcenter-llamadas-perdidas-queue')
        .empty();
	if( response.total > 0 ){

        $('#llamadas_perdidas_count').text(response.total);

        var at48hours = (new Date((((new Date()).getTime()/1000) - 48*60*60)*1000)).getTime();

        var data = response.result;

        var ul = $('<ul class="llamada-perdida-list"/>');
		$('#elastix-callcenter-llamadas-perdidas-queue').append(ul);
		for (var i in data) {
                var fullTextClient = '&lt;' + data[i]['phone'] + '&gt;';
                var textClient = '&lt;' + data[i]['phone'] + '&gt;';
                if( data[i]['name'] ){
                    var cname = data[i]['name'];
                    fullTextClient = cname + ' ' + fullTextClient;
                    if( cname.length > 20 ){
                        var arrname = cname.split(" ");
                        var tmpname = '';
                        for(var n in arrname){
                            var aux = tmpname + ' ' + arrname[n];
                            if( aux.length < 20 ){
                                if( tmpname != '' ) tmpname += " "; 
                                tmpname += arrname[n];
                            }
                        }
                        textClient = tmpname + ' ' + textClient;
                    } else {
                        textClient = cname + ' ' + textClient;
                    }
                    //textClient = cname + ' ' + textClient;
                }
                var histWarn = false;
                if( data[i]['_history_'] ){
                    var hist = data[i]['_history_'];
                    for(k=0; k<hist.length; k++){
                        var strDate = hist[k].replace(" ","T");
                        if( (new Date(strDate)).getTime() > at48hours ){
                            fullTextClient += "\n" + hist[k];
                            if( k>0 ) histWarn = true;
                        }
                    }
                } else if( data[i]['start_date'] ){
                    fullTextClient += "\n" + data[i]['start_date'];
                }
                var li = $('<li class="llamada-perdida-line" onmouseover="this.style.backgroundColor=\'#f2f2f2\';" onmouseout="this.style.backgroundColor=\'#ffffff\';" style="background-color: rgb(255, 255, 255);" />')
                            .append('<a href="#" onclick="callnumber(\''+data[i]['phone']+'\')" title="'+fullTextClient+'">'+textClient+'</a>')
                            //.append(' at ' + calcTimeSince(data[i]['start_date']))
                            //.append(' wait ' + formatTimeSince(data[i]['duration_wait']) )
                            //.append(' <a href="/cgi-bin/redirectsapclient?phone='+data[i]['phone']+'" target="_blank" title="Informa&ccedil;&atilde;o"><img src="modules/agent_console/images/agent.png" border="0" alt="Informa&ccedil;&atilde;o"/></a>')
                            .append(' <a href="#" onclick="ignorecall(\''+data[i]['phone']+'\')" title="Ignorar"><img src="modules/agent_console/images/ignore.png" border="0" alt="Ignorar"/></a>');
                if( histWarn ){
                    li.append(' <a href="#" onclick="callnumber(\''+data[i]['phone']+'\')" title="Chamar"><img src="modules/agent_console/images/warning.png" border="0" alt="Chamar"/></a>');
                } else {
                    li.append(' <a href="#" onclick="callnumber(\''+data[i]['phone']+'\')" title="Chamar"><img src="modules/agent_console/images/call.png" border="0" alt="Chamar"/></a>');
                }
                ul.append(li);
		}
	}
}
function callnumber(phone) {
    $.post('/cgi-bin/sapcall', 'extension='+extensionId+'&number='+phone, 
            function (response) {
                //console.log(response);
                // check missed calls now 
                setTimeout(do_check_missedcalls, 1);
            },'text');
}
function ignorecall(phone) {
    $.post('/cgi-bin/sapcall', 'op=ignore&extension='+extensionId+'&number='+phone, 
            function (response) {
                //console.log(response);
                // check missed calls now 
                setTimeout(do_check_missedcalls, 1);
            },'text');
}
