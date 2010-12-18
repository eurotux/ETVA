/*!
 * Ext JS Library 3.0.0
 * Copyright(c) 2006-2009 Ext JS, LLC
 * licensing@extjs.com
 * http://www.extjs.com/license
 */
Ext.app.REMOTING_API = {type:'polling',url:'php/poll.php'};

Ext.onReady(function(){
    Ext.Direct.addProvider(
        Ext.app.REMOTING_API
        //,
        //{
         //   type:'polling',
         //   url: 'php/poll.php'
       // }
    );



    var out = new Ext.form.DisplayField({
        cls: 'x-form-text',
        id: 'out'
    });



    

  

	var p = new Ext.Panel({
        title: 'Remote Call Log',
        //frame:true,
		width: 600,
		height: 300,
		layout:'fit',
		
		items: [out]
       
	}).render(Ext.getBody());

    Ext.Direct.on('message', function(e){
        out.append(String.format('<p><i>{0}</i></p>', e.data));
                out.el.scroll('b', 100000, true);
    });
});

//Ext.app.REMOTING_API = {"url":"php\/router.php","type":"pooling"};

// Ext.app.REMOTING_API = {"url":"php\/router.php","type":"remoting","actions":{"TestAction":[{"name":"doEcho","len":1},{"name":"multiply","len":1},{"name":"getTree","len":1}],"Profile":[{"name":"getBasicInfo","len":2},{"name":"getPhoneInfo","len":1},{"name":"getLocationInfo","len":1},{"name":"updateBasicInfo","len":2,"formHandler":true}]}};
