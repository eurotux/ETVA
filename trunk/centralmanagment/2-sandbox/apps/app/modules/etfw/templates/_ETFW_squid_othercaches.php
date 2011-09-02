<script>    
Ext.ns('ETFW.Squid.Othercaches');

/*
 *
 * Cache hosts template form
 *
 */
ETFW.Squid.Othercaches.CacheTpl = Ext.extend(Ext.form.FormPanel, {

    // defaults - can be changed from outside
    border:false
    ,labelWidth:150
    ,url:<?php echo json_encode(url_for('etfw/json'))?>

    ,initComponent:function() {

        this.saveBtn = new Ext.Button({text:'Save'
            ,scope:this
            ,handler:this.onSave
        });

        var allFields = [{xtype:'fieldset',
                        title:'Cache Host Options',border:false,
                        defaultType:'textfield',
                        defaults:{border:false},
                        items:[
                              {xtype:'hidden',name:'index'}
                              ,{xtype:'hidden',name:'cache_peer_domain_index'}
                              ,{
                                layout:'table',
                                xtype:'panel',
                                layoutConfig: {columns: 2},
                                defaults:{layout:'form',border:false},
                                items: [
                                    //1st col
                                    {width:350,defaults:{border:false},items:[
                                        {xtype:'textfield',fieldLabel:'Hostname', allowBlank:false,vtype:'no_spaces',name:'hostname'}
                                        ,{xtype:'numberfield',fieldLabel:'Proxy port', width:60,allowBlank:false,name:'http-port'}
                                        ,{xtype:'radiogroup',
                                            name:'option_proxy-only',
                                            fieldLabel:'Proxy only',
                                            labelSeparator:'?',
                                            height:15,
                                            width:90,
                                            items:[
                                                {xtype:'radio', name:'option_proxy-only',inputValue:'1', boxLabel:'Yes'},
                                                {xtype:'radio', name:'option_proxy-only',checked:true,inputValue:'0', boxLabel:'No'}]
                                         }
                                         ,{xtype:'radiogroup',
                                            name:'option_default',
                                            fieldLabel:'Default cache',
                                            labelSeparator:'?',
                                            height:15,
                                            width:90,
                                            items:[
                                                {xtype:'radio', name:'option_default', inputValue:'1', boxLabel:'Yes'},
                                                {xtype:'radio', name:'option_default',checked:true, inputValue:'0', boxLabel:'No'}]
                                         }
                                        ,{
                                            layout:'table',
                                            xtype:'panel',
                                            layoutConfig: {columns: 3},
                                            defaults:{layout:'form',border:false,bodyStyle:'padding-left:5px;'},
                                            items: [
                                                {bodyStyle:'padding:0px;',items:[{xtype:'radio', fieldLabel:'ICP time-to-live',name:'option_ttl-radio',boxLabel:'Default',checked:true,inputValue: 0}]},
                                                {items:[{xtype:'radio', fieldLabel:'',hideLabel:true,name:'option_ttl-radio',boxLabel:'',inputValue: 1}]},
                                                {items:[{xtype:'numberfield',fieldLabel:'',hideLabel:true,width:60,name:'option_ttl'}]}]
                                        }
                                        ,{xtype:'radiogroup',
                                            name:'option_closest-only',
                                            fieldLabel:'Closest only',
                                            labelSeparator:'?',
                                            height:15,
                                            width:90,
                                            items:[
                                                {xtype:'radio', name:'option_closest-only', inputValue:'1', boxLabel:'Yes'},
                                                {xtype:'radio', name:'option_closest-only',checked:true,inputValue:'0', boxLabel:'No'}]
                                         }
                                         ,{xtype:'radiogroup',
                                            name:'option_no-netdb-exchange',
                                            fieldLabel:'No NetDB exchange',
                                            labelSeparator:'?',
                                            height:15,
                                            width:90,
                                            items:[
                                                {xtype:'radio', name:'option_no-netdb-exchange', inputValue:'1', boxLabel:'Yes'},
                                                {xtype:'radio', name:'option_no-netdb-exchange',checked:true,inputValue:'0', boxLabel:'No'}]
                                         }
                                    ]},
                                    //2nd col
                                    {bodyStyle:'padding-left:20px;',defaults:{border:false},items:[
                                        {xtype:'combo',fieldLabel: 'Type',width:80,name: 'type',
                                                store: ['parent','sibling','multicast'],
                                                forceSelection: true,
                                                value:'parent',
                                                triggerAction: 'all',
                                                editable:false,
                                                validator:function(value){
                                                            if(value=='') this.setValue(this.originalValue);
                                                            return true;},
                                                scope:this,
                                                selectOnFocus:true}
                                        ,{xtype:'numberfield',fieldLabel:'ICP port',width:60,allowBlank:false,name:'icp-port'}
                                        ,{xtype:'radiogroup',
                                            name:'option_no-query',
                                            fieldLabel:'Send ICP queries',
                                            labelSeparator:'?',
                                            height:15,
                                            width:80,
                                            items:[
                                                {xtype:'radio', name:'option_no-query', checked:true, inputValue:'1', boxLabel:'Yes'},
                                                {xtype:'radio', name:'option_no-query',inputValue:'0', boxLabel:'No'}]
                                         }
                                         ,{xtype:'radiogroup',
                                            name:'option_round-robin',
                                            fieldLabel:'Round-robin cache',
                                            labelSeparator:'?',
                                            height:15,
                                            width:80,
                                            items:[
                                                {xtype:'radio', name:'option_round-robin',inputValue:'1', boxLabel:'Yes'},
                                                {xtype:'radio', name:'option_round-robin',checked:true, inputValue:'0', boxLabel:'No'}]
                                         }
                                        ,{
                                            layout:'table',
                                            xtype:'panel',
                                            layoutConfig: {columns: 3},
                                            defaults:{layout:'form',border:false,bodyStyle:'padding-left:5px;'},
                                            items: [
                                                {bodyStyle:'padding:0px;',items:[{xtype:'radio', fieldLabel:'Cache weighting',name:'option_weight-radio',boxLabel:'Default',checked:true,inputValue: 0}]},
                                                {items:[{xtype:'radio', fieldLabel:'',hideLabel:true,name:'option_weight-radio',boxLabel:'',inputValue: 1}]},
                                                {items:[{xtype:'numberfield',fieldLabel:'',hideLabel:true,width:60,name:'option_weight'}]}]
                                        }
                                        ,{xtype:'radiogroup',
                                            name:'option_no-digest',
                                            fieldLabel:'No digest',
                                            labelSeparator:'?',
                                            height:15,
                                            width:80,
                                            items:[
                                                {xtype:'radio', name:'option_no-digest', inputValue:'1', boxLabel:'Yes'},
                                                {xtype:'radio', name:'option_no-digest',checked:true, inputValue:'0', boxLabel:'No'}]
                                         }
                                         ,{xtype:'radiogroup',
                                            name:'option_no-delay',
                                            fieldLabel:'No delay',
                                            labelSeparator:'?',
                                            height:15,
                                            width:80,
                                            items:[
                                                {xtype:'radio', name:'option_no-delay', inputValue:'1', boxLabel:'Yes'},
                                                {xtype:'radio', name:'option_no-delay',checked:true, inputValue:'0', boxLabel:'No'}]
                                         }
                                    ]}// end 2nd col
                                ]}//end table
                             ,{xtype:'radio', name:'option_login-radio',fieldLabel:'Login to proxy',checked:true,inputValue:0, boxLabel:'No login'}
                             ,{
                                layout:'table',
                                xtype:'panel',
                                layoutConfig: {columns: 3},
                                defaults:{layout:'form',border:false},
                                items: [
                                    {items:[{xtype:'radio', fieldLabel:'',name:'option_login-radio',boxLabel:'User:',inputValue: 1}]},
                                    {items:[{xtype:'textfield',fieldLabel:'',hideLabel:true,name:'option_login_user'}]},
                                    {bodyStyle:'padding-left:5px;',labelWidth:25,items:[{xtype:'textfield',fieldLabel:'Pass',name:'option_login_pwd'}]}]
                             }
                             ,{xtype:'radio', name:'option_login-radio',fieldLabel:'',inputValue:2, boxLabel:'Pass on client authentication to this cache'}
                             ,{
                                layout:'table',
                                xtype:'panel',
                                layoutConfig: {columns: 2},
                                defaults:{layout:'form',border:false},
                                items: [
                                    {items:[{xtype:'radio', fieldLabel:'',name:'option_login-radio',boxLabel:'Pass on client login with password:',inputValue: 3}]},
                                    {items:[{xtype:'textfield',fieldLabel:'',hideLabel:true,name:'option_login_pass_pwd'}]}]
                              }
                             ,{
                                layout:'table',
                                xtype:'panel',
                                layoutConfig: {columns: 2},
                                defaults:{layout:'form',border:false},
                                items: [
                                    //1st col
                                    {width:350,defaults:{border:false},items:[
                                        {
                                         layout:'table',
                                         xtype:'panel',
                                         layoutConfig: {columns: 3},
                                         defaults:{layout:'form',border:false,bodyStyle:'padding-left:5px;'},
                                         items: [
                                            {bodyStyle:'padding:0px;',items:[{xtype:'radio', fieldLabel:'Connection timeout for host',name:'option_connect-timeout-radio',checked:true,boxLabel:'Default',inputValue: 0}]},
                                            {items:[{xtype:'radio', fieldLabel:'',hideLabel:true,name:'option_connect-timeout-radio',boxLabel:'',inputValue: 1}]},
                                            {items:[{xtype:'numberfield',fieldLabel:'',hideLabel:true,width:60,name:'option_connect-timeout'}]}]
                                        }
                                        ,{xtype:'radiogroup',
                                            name:'option_allow-miss',
                                            fieldLabel:'Allow miss requests',
                                            labelSeparator:'?',
                                            height:15,
                                            width:90,
                                            items:[
                                                {xtype:'radio', name:'option_allow-miss',inputValue:'1', boxLabel:'Yes'},
                                                {xtype:'radio', name:'option_allow-miss',checked:true,inputValue:'0', boxLabel:'No'}]
                                         }
                                         ,{xtype:'radiogroup',
                                            name:'option_htcp',
                                            fieldLabel:'Use HTCP instead of ICP',
                                            labelSeparator:'?',
                                            height:15,
                                            width:90,
                                            items:[
                                                {xtype:'radio', name:'option_htcp',inputValue:'1', boxLabel:'Yes'},
                                                {xtype:'radio', name:'option_htcp',checked:true, inputValue:'0', boxLabel:'No'}]
                                         }
                                        ,{xtype:'radiogroup',
                                            name:'option_originserver',
                                            fieldLabel:'Treat host as origin server',
                                            labelSeparator:'?',
                                            height:15,
                                            width:90,
                                            items:[
                                                {xtype:'radio', name:'option_originserver',inputValue:'1', boxLabel:'Yes'},
                                                {xtype:'radio', name:'option_originserver',checked:true, inputValue:'0', boxLabel:'No'}]
                                         }
                                         ,{xtype:'radiogroup',
                                            name:'option_multicast-responder',
                                            fieldLabel:'Multicast responder',
                                            labelSeparator:'?',
                                            height:15,
                                            width:90,
                                            items:[
                                                {xtype:'radio', name:'option_multicast-responder',inputValue:'1', boxLabel:'Yes'},
                                                {xtype:'radio', name:'option_multicast-responder',checked:true,inputValue:'0', boxLabel:'No'}]
                                         }
                                         ,{xtype:'textarea',width:150,name:'cache_peer_domain_query',fieldLabel:'Query host for domains'}
                                    ]},
                                    //2nd col
                                    {bodyStyle:'padding-left:10px;',defaults:{border:false},width:430,items:[
                                        {
                                         layout:'table',
                                         xtype:'panel',
                                         layoutConfig: {columns: 3},
                                         defaults:{layout:'form',border:false,bodyStyle:'padding-left:5px;'},
                                         items: [
                                            {bodyStyle:'padding:0px;',items:[{xtype:'radio', fieldLabel:'Host\'s cache digest URL',name:'option_digest-url-radio',boxLabel:'Default',checked:true,inputValue: 0}]},
                                            {items:[{xtype:'radio', fieldLabel:'',hideLabel:true,name:'option_digest-url-radio',boxLabel:'',inputValue: 1}]},
                                            {items:[{xtype:'textfield',fieldLabel:'',hideLabel:true,name:'option_digest-url'}]}]
                                        }
                                        ,{
                                         layout:'table',
                                         xtype:'panel',
                                         layoutConfig: {columns: 3},
                                         defaults:{layout:'form',border:false,height:40,bodyStyle:'padding-left:5px;'},
                                         items: [
                                            {bodyStyle:'padding:0px;',items:[{xtype:'radio', fieldLabel:'Maximum concurrent connections',name:'option_max-conn-radio',boxLabel:'Default',checked:true,inputValue: 0}]},
                                            {items:[{xtype:'radio', fieldLabel:'',hideLabel:true,name:'option_max-conn-radio',boxLabel:'',inputValue: 1}]},
                                            {items:[{xtype:'numberfield',fieldLabel:'',hideLabel:true,width:60,name:'option_max-conn'}]}]
                                        }
                                        ,{
                                         layout:'table',
                                         xtype:'panel',
                                         layoutConfig: {columns: 3},
                                         defaults:{layout:'form',border:false,bodyStyle:'padding-left:5px;'},
                                         items: [
                                            {bodyStyle:'padding:0px;',items:[{xtype:'radio', fieldLabel:'Force Host: header to',name:'option_forceddomain-radio',boxLabel:'Unchanged',checked:true,inputValue: 0}]},
                                            {items:[{xtype:'radio', fieldLabel:'',hideLabel:true,name:'option_forceddomain-radio',boxLabel:'',inputValue: 1}]},
                                            {items:[{xtype:'textfield',fieldLabel:'',hideLabel:true,name:'option_forceddomain'}]}]
                                        }
                                        ,{xtype:'radiogroup',
                                            name:'option_ssl',
                                            fieldLabel:'Connect using SSL',
                                            labelSeparator:'?',
                                            height:15,
                                            width:90,
                                            items:[
                                                {xtype:'radio', name:'option_ssl', inputValue:'1', boxLabel:'Yes'},
                                                {xtype:'radio', name:'option_ssl',checked:true, inputValue:'0', boxLabel:'No'}]
                                         }
                                         ,{xtype:'textarea',width:150,name:'cache_peer_domain_dontquery',fieldLabel:'Don\'t query for domains'}
                                    ]}//end 2nd col
                              ]}//end table
                        ]//end fieldset items
                        }];



        var config = {
            defaultType:'textfield'
            ,monitorValid:true
            ,autoScroll:true
            ,buttonAlign:'left'
            ,items:allFields
            ,border:true
            ,frame:true
            ,bodyStyle:'padding-top:10px'
            ,buttons:[this.saveBtn]
        };


        // apply config
        Ext.apply(this, Ext.apply(this.initialConfig, config));


        this.on('beforerender',function(){
            Ext.getBody().mask('Loading form data...');

        },this);

        this.on('render',function(){
            Ext.getBody().unmask();}
        ,this
        ,{delay:100}
        );



        // call parent
        ETFW.Squid.Othercaches.CacheTpl.superclass.initComponent.apply(this, arguments);


    } // eo function initComponent
    ,onRender:function() {
        // call parent
        ETFW.Squid.Othercaches.CacheTpl.superclass.onRender.apply(this, arguments);

        // set wait message target
        this.getForm().waitMsgTarget = this.getEl();

    } // eo function onRender
    ,loadRecord:function(rec){

        this.getForm().reset();
        this.saveBtn.setText('Save');

        if(typeof(rec.data['options']['proxy-only'])=='undefined') rec.data['option_proxy-only'] = 0;
        else rec.data['option_proxy-only'] = 1;

        if(typeof(rec.data['options']['default'])=='undefined') rec.data['option_default'] = 0;
        else rec.data['option_default'] = 1;

        if(Ext.isEmpty(rec.data['options']['ttl'])){
            rec.data['option_ttl-radio'] = 0;
            rec.data['option_ttl'] = '';
        }else{
            rec.data['option_ttl-radio'] = 1;
            rec.data['option_ttl'] = rec.data['options']['ttl'];
        }

        if(typeof(rec.data['options']['closest-only'])=='undefined') rec.data['option_closest-only'] = 0;
        else rec.data['option_closest-only'] = 1;

        if(typeof(rec.data['options']['no-netdb-exchange'])=='undefined') rec.data['option_no-netdb-exchange'] = 0;
        else rec.data['option_no-netdb-exchange'] = 1;

        if(typeof(rec.data['options']['no-query'])=='undefined') rec.data['option_no-query'] = 1;
        else rec.data['option_no-query'] = 0;

        if(typeof(rec.data['options']['round-robin'])=='undefined') rec.data['option_round-robin'] = 0;
        else rec.data['option_round-robin'] = 1;

        if(Ext.isEmpty(rec.data['options']['weight'])){
            rec.data['option_weight-radio'] = 0;
            rec.data['option_weight'] = '';
        }else{
            rec.data['option_weight-radio'] = 1;
            rec.data['option_weight'] = rec.data['options']['weight'];
        }

        if(typeof(rec.data['options']['no-digest'])=='undefined') rec.data['option_no-digest'] = 0;
        else rec.data['option_no-digest'] = 1;

        if(typeof(rec.data['options']['no-delay'])=='undefined') rec.data['option_no-delay'] = 0;
        else rec.data['option_no-delay'] = 1;

        if(Ext.isEmpty(rec.data['options']['login'])){
            rec.data['option_login-radio'] = 0;
        }else{

            switch(rec.data['options']['login']){
                case 'PASS':
                            rec.data['option_login-radio'] = 2;
                            break;
                    default:
                            var pieces = rec.data['options']['login'].split(':');
                            if(pieces.length>1){
                                var user = pieces[0];
                                if(user=='*'){
                                    rec.data['option_login-radio'] = 3;
                                    rec.data['option_login_pass_pwd'] = pieces[1];
                                }else{
                                    rec.data['option_login-radio'] = 1;
                                    rec.data['option_login_user'] = user;
                                    rec.data['option_login_pwd'] = pieces[1];
                                }
                            }
                            break;
            }
        }

        if(Ext.isEmpty(rec.data['options']['connect-timeout'])){
            rec.data['option_connect-timeout-radio'] = 0;
            rec.data['option_connect-timeout'] = '';
        }else{
            rec.data['option_connect-timeout-radio'] = 1;
            rec.data['option_connect-timeout'] = rec.data['options']['connect-timeout'];
        }

        if(typeof(rec.data['options']['allow-miss'])=='undefined') rec.data['option_allow-miss'] = 0;
        else rec.data['option_allow-miss'] = 1;

        if(typeof(rec.data['options']['htcp'])=='undefined') rec.data['option_htcp'] = 0;
        else rec.data['option_htcp'] = 1;

        if(typeof(rec.data['options']['originserver'])=='undefined') rec.data['option_originserver'] = 0;
        else rec.data['option_originserver'] = 1;

        if(typeof(rec.data['options']['multicast-responder'])=='undefined') rec.data['option_multicast-responder'] = 0;
        else rec.data['option_multicast-responder'] = 1;

        if(Ext.isEmpty(rec.data['options']['digest-url'])){
            rec.data['option_digest-url-radio'] = 0;
            rec.data['option_digest-url'] = '';
        }else{
            rec.data['option_digest-url-radio'] = 1;
            rec.data['option_digest-url'] = rec.data['options']['digest-url'];
        }

        if(Ext.isEmpty(rec.data['options']['max-conn'])){
            rec.data['option_max-conn-radio'] = 0;
            rec.data['option_max-conn'] = '';
        }else{
            rec.data['option_max-conn-radio'] = 1;
            rec.data['option_max-conn'] = rec.data['options']['max-conn'];
        }

        if(Ext.isEmpty(rec.data['options']['forceddomain'])){
            rec.data['option_forceddomain-radio'] = 0;
            rec.data['option_forceddomain'] = '';
        }else{
            rec.data['option_forceddomain-radio'] = 1;
            rec.data['option_forceddomain'] = rec.data['options']['forceddomain'];
        }

        if(typeof(rec.data['options']['ssl'])=='undefined') rec.data['option_ssl'] = 0;
        else rec.data['option_ssl'] = 1;


        if(Ext.isEmpty(rec.data['cache_peer_domain'])){
            rec.data['cache_peer_domain_dontquery'] = '';
            rec.data['cache_peer_domain_query'] = '';
        }else{
            rec.data['cache_peer_domain_index'] = rec.data['cache_peer_domain'][0]['index'];

            rec.data['cache_peer_domain_dontquery'] = rec.data['cache_peer_domain'][0]['dontquery'].toString();
            rec.data['cache_peer_domain_dontquery'] = rec.data['cache_peer_domain_dontquery'].replace(/,/g,'\n');

            rec.data['cache_peer_domain_query'] = rec.data['cache_peer_domain'][0]['query'].toString();
            rec.data['cache_peer_domain_query'] = rec.data['cache_peer_domain_query'].replace(/,/g,'\n');
        }



        this.body.dom.scrollTop = 0;

        this.getForm().getEl().mask('Loading data...');
        (function(){
            this.getForm().loadRecord(rec);
            this.getForm().getEl().unmask();
        }).defer(100,this);

    }
    ,onSave:function(){

        if (this.form.isValid()) {

            var alldata = this.form.getValues();
            var send_data = new Object();
            var options = new Object();

            var method = 'set_cache_peer';

            if(!Ext.isEmpty(alldata['index'])){
                send_data['index'] = alldata['index'];
            }else{
                method = method.replace('set_','add_');
            }

            send_data['hostname'] = alldata['hostname'];
            send_data['icp-port'] = alldata['icp-port'];
            send_data['http-port'] = alldata['http-port'];
            send_data['type'] = alldata['type'];

            var proxy_only = alldata['option_proxy-only'];
            if(proxy_only==1) options['proxy-only'] = '';

            var default_ = alldata['option_default'];
            if(default_==1) options['default'] = '';

            var ttl_radio = alldata['option_ttl-radio'];
            if(ttl_radio==1) options['ttl'] = alldata['option_ttl'];


            var closest_only = alldata['option_closest-only'];
            if(closest_only==1) options['closest-only'] = '';

            var no_netdb_exchange = alldata['option_no-netdb-exchange'];
            if(no_netdb_exchange==1) options['no-netdb-exchange'] = '';

            var no_query = alldata['option_no-query'];
            if(no_query==0) options['no-query'] = '';

            var round_robin = alldata['option_round-robin'];
            if(round_robin==1) options['round-robin'] = '';

            var weight_radio = alldata['option_weight-radio'];
            if(weight_radio==1) options['weight'] = alldata['option_weight'];

            var no_digest = alldata['option_no-digest'];
            if(no_digest==1) options['no-digest'] = '';

            var no_delay = alldata['option_no-delay'];
            if(no_delay==1) options['no-delay'] = '';

            var login_radio = alldata['option_login-radio'];
            switch(login_radio){
                case '3' :
                            options['login'] = '*:'+alldata['option_login_pass_pwd'];
                            break;
                case '2' :
                            options['login'] = 'PASS';
                            break;
                case '1' :
                            options['login'] = alldata['option_login_user']+':'+alldata['option_login_pwd'];
                            break;
                default:
                            break;
            }

            var connect_timeout_radio = alldata['option_connect-timeout-radio'];
            if(connect_timeout_radio==1) options['connect-timeout'] = alldata['option_connect-timeout'];

            var allow_miss = alldata['option_allow-miss'];
            if(allow_miss==1) options['allow-miss'] = '';

            var htcp = alldata['option_htcp'];
            if(htcp==1) options['htcp'] = '';

            var originserver = alldata['option_originserver'];
            if(originserver==1) options['originserver'] = '';

            var multicast_responder = alldata['option_multicast-responder'];
            if(multicast_responder==1) options['multicast-responder'] = '';

            var digest_url_radio = alldata['option_digest-url-radio'];
            if(digest_url_radio==1) options['digest-url'] = alldata['option_digest-url'];

            var max_conn_radio = alldata['option_max-conn-radio'];
            if(max_conn_radio==1) options['max-conn'] = alldata['option_max-conn'];

            var forceddomain_radio = alldata['option_forceddomain-radio'];
            if(forceddomain_radio==1) options['forceddomain'] = alldata['option_forceddomain'];

            var ssl = alldata['option_ssl'];
            if(ssl==1) options['ssl'] = '';

            send_data['options'] = options;

            var domain_index = alldata['cache_peer_domain_index'];
            var dontquery = alldata['cache_peer_domain_dontquery'];
            if(Ext.isEmpty(dontquery)) dontquery = [];
            else{
                var joined_dontquery = dontquery.replace(/[\n\r\s]+/g, ' ');
                dontquery = joined_dontquery.split(' ');
            }

            var query = alldata['cache_peer_domain_query'];
            if(Ext.isEmpty(query)) query = [];
            else{
                var joined_query = query.replace(/[\n\r\s]+/g, ' ');
                query = joined_query.split(' ');
            }

            if(Ext.isEmpty(domain_index)) send_data['cache_peer_domain'] = [{'dontquery':dontquery,'query':query}];
            else send_data['cache_peer_domain'] = [{'index':domain_index,'dontquery':dontquery,'query':query}];


            var conn = new Ext.data.Connection({
                listeners:{
                    // wait message.....
                    beforerequest:function(){
                        Ext.MessageBox.show({
                            title: 'Please wait',
                            msg: 'Updating cache host information...',
                            width:300,
                            wait:true,
                            modal: false
                        });
                    },// on request complete hide message
                    requestcomplete:function(){Ext.MessageBox.hide();}
                }
            });// end conn

            conn.request({
                url: this.url,
                params:{id:this.service_id,method:method,
                    params:Ext.encode(send_data)
                },
                failure: function(resp,opt){
                    var response = Ext.util.JSON.decode(resp.responseText);
                    Ext.MessageBox.alert('Error Message', response['info']);
                    Ext.ux.Logger.error(response['error']);

                },
                // everything ok...
                success: function(resp,opt){
                 //   (this.ownerCt).close();
                 (this.ownerCt).fireEvent('reloadParentContent');
                    var msg = 'Updated cache host';
                    Ext.ux.Logger.info(msg);
                    View.notify({html:msg});

                },scope:this
            });// END Ajax request


        } else{
            Ext.MessageBox.show({
                title: 'Error',
                msg: 'Please fix the errors noted.',
                buttons: Ext.MessageBox.OK,
                icon: Ext.MessageBox.WARNING
            });
        }
    }
    ,reset:function(){
        this.saveBtn.setText('Add');
        this.getForm().reset();
    }


}); // eo extend
// end cache hosts form template



/*
 * Other caches hosts grid
 *
 */

ETFW.Squid.Othercaches.Cache_Grid = Ext.extend(Ext.grid.GridPanel, {
    initComponent:function() {

        // show check boxes
        var selectBoxModel= new Ext.grid.CheckboxSelectionModel({keepSelections:true});

        var dataStore = new Ext.data.GroupingStore({
                reader:new Ext.data.JsonReader({
                    totalProperty: 'total',
                    root: 'data',
                    fields: ['index',{name:'hostname', sortType:'asUCString',type:'string'},'type','http-port','icp-port','options','cache_peer_domain']
                })
                ,proxy:new Ext.data.HttpProxy({url:this.url})
                ,baseParams:{id:this.service_id,method:'get_cache_peer'}
                ,groupField:'type'
                ,sortInfo:{field:'hostname',direction:'ASC'}
            });

        // column model
        var cm = new Ext.grid.ColumnModel([
            selectBoxModel,
            {header: "Hostname", sortable: true, dataIndex: 'hostname', width:120, renderer: function (value, metadata, record, rowIndex, colIndex, store) {
                            metadata.attr = 'ext:qtip="Double-click to edit"';
                            return value;
                        }},
            {header: "Type", dataIndex: 'type', width:120, sortable: true},
            {header: "Proxy port", dataIndex: 'http-port', width:120, sortable: true},
            {header: "ICP port", dataIndex: 'icp-port', width:120, sortable: true}
        ]);

        var config = {
            store:dataStore
            ,view: new Ext.grid.GroupingView({
                forceFit:true
                ,groupTextTpl:'{text} ({[values.rs.length]} {[values.rs.length > 1 ? "Items" : "Item"]})'
            })
            ,cls:'gridWrap'
            ,cm:cm
            ,sm:selectBoxModel
            ,viewConfig:{forceFit:true}
            ,loadMask:true
        }; // eo config object

        // apply config
        Ext.apply(this, Ext.apply(this.initialConfig, config));

        this.bbar = new Ext.ux.grid.TotalCountBar({
            store:this.store
            ,displayInfo:true
        });

        this.tbar = [
                {
                    tooltip:'Click here to add another cache',
                    text:'Create new cache',
                    iconCls:'add',
                    handler:this.clearCacheTpl,
                    scope:this

                }
                ,'-'
                ,{
                    ref: '../editBtn',
                    text:'Edit',
                    tooltip:'Edit the selected item',
                    disabled:true,
                    handler: function(item){
                        var selected = item.ownerCt.ownerCt.getSelectionModel().getSelected();
                        this.ownerCt.fireEvent('loadOthercacheTplRecord',selected);
                    },scope:this
                }
                ,'-'
                ,{
                    ref: '../removeBtn',
                    text:'Delete',
                    tooltip:'Delete the selected item(s)',
                    iconCls:'remove',
                    disabled:true,
                    handler: function(){
                        new Grid.util.DeleteItem({panel: this.id});
                    },scope:this
                }];

        // call parent
        ETFW.Squid.Othercaches.Cache_Grid.superclass.initComponent.apply(this, arguments);

        this.getSelectionModel().on('selectionchange', function(sm){
            this.editBtn.setDisabled(sm.getCount() < 1);
            this.removeBtn.setDisabled(sm.getCount() < 1);
        },this);

        this.on('rowdblclick', function(gridPanel, rowIndex, e) {
            var selected = this.store.data.items[rowIndex];
            this.ownerCt.fireEvent('loadOthercacheTplRecord',selected);
        });

        /************************************************************
         * handle contextmenu event
         ************************************************************/
        this.addListener("rowcontextmenu", onContextMenu, this);
        function onContextMenu(grid, rowIndex, e) {
            this.rowctx = rowIndex;
            if (!this.menu) {
                this.menu = new Ext.menu.Menu({
                    // id: 'menus',
                    items: [
                            {
                                text:'Edit subnet',
                                tooltip:'Edit subnet information of the selected item',
                                iconCls:'editItem',
                                handler: function(){
                                    var selected = this.store.data.items[this.rowctx];
                                    this.ownerCt.fireEvent('loadOthercacheTplRecord',selected);
                                },
                                scope:this
                            }
                            ,{
                            text:'Delete',
                            tooltip:'Delete the selected item(s)',
                            iconCls:'remove',
                            handler: function(){
                                new Grid.util.DeleteItem({panel: grid.id});
                            }
                        }]
                });
            }
            e.stopEvent();
            this.menu.showAt(e.getXY());
        }


        // load the store at the latest possible moment
        this.on({
            afterlayout:{scope:this, single:true, fn:function() {
                    this.store.load();
                }}
        });

    } // eo function initComponent
    ,reload : function() {
        this.store.load();
    }
    ,// call delete stuff now
    // Server side will receive delData throught parameter
    deleteData : function (items) {

        var conn = new Ext.data.Connection({
            listeners:{
                // wait message.....
                beforerequest:function(){
                    Ext.MessageBox.show({
                        title: 'Please wait',
                        msg: 'Deleting other host cache(s)...',
                        width:300,
                        wait:true,
                        modal: false
                    });
                },// on request complete hide message
                requestcomplete:function(){Ext.MessageBox.hide();}
            }
        });// end conn

        var caches = [];

        for(var i=0,len = items.length;i<len;i++){
            caches[i] = items[i].data.index;
        }

        var send_data = {'indexes':caches};

        conn.request({
            url: this.url,
            params:{id:this.service_id,method:'del_cache_peer',params:Ext.encode(send_data)},
            failure: function(resp,opt){
                var response = Ext.util.JSON.decode(resp.responseText);
                Ext.MessageBox.alert('Error Message', response['info']);
                Ext.ux.Logger.error(response['error']);

            },
            // everything ok...
            success: function(resp,opt){
                var msg = 'Deleted other host(s) cache';
                Ext.ux.Logger.info(msg);
                View.notify({html:msg});
                this.reload();

            },scope:this
        });// END Ajax request

    }
    ,clearCacheTpl:function(){
        this.ownerCt.fireEvent('clearOthercacheTpl');
    }

});
Ext.reg('etfw_squid_othercaches_cachegrid', ETFW.Squid.Othercaches.Cache_Grid);



ETFW.Squid.Othercaches.OptionsTpl = function(config) {

    Ext.apply(this,config);

    this.params = ["hierarchy_stoplist","icp_query_timeout","dead_peer_timeout","mcast_icp_query_timeout"];


    var allFields = [{xtype:'fieldset',
                        title:'Cache Selection Options',
                        defaultType:'textfield',
                        defaults:{border:false},
                        items:[
                             {
                                layout:'table',
                                xtype:'panel',
                                layoutConfig: {columns: 3},
                                defaults:{layout:'form',bodyStyle:'padding-left:5px;',border:false,height:40},
                                items: [
                                    {items:[{xtype:'radio', fieldLabel:'Directly fetch URLs containing',name:'hierarchy_stoplist-radio',boxLabel:'Default',checked:true,inputValue: 0}]},
                                    {items:[{xtype:'radio', fieldLabel:'',hideLabel:true,name:'hierarchy_stoplist-radio',boxLabel:'',inputValue: 1}]},
                                    {items:[{xtype:'textfield',fieldLabel:'',hideLabel:true,name:'hierarchy_stoplist'}]}
                                ]
                              }
                            ,{
                                layout:'table',
                                xtype:'panel',
                                layoutConfig: {columns: 2},
                                defaults:{layout:'form',border:false},
                                items: [
                                    {items:[
                                            {
                                            layout:'table',
                                            width:300,
                                            border:false,
                                            xtype:'panel',
                                            layoutConfig: {columns: 4},
                                            defaults:{layout:'form',bodyStyle:'padding-left:5px;',border:false,height:40},
                                            items: [
                                                {items:[{xtype:'radio', fieldLabel:'ICP query timeout',name:'icp_query_timeout-radio',boxLabel:'Default',checked:true,inputValue: 0}]},
                                                {items:[{xtype:'radio', fieldLabel:'',hideLabel:true,name:'icp_query_timeout-radio',boxLabel:'',inputValue: 1}]},
                                                {items:[{xtype:'numberfield',fieldLabel:'',hideLabel:true,width:60,name:'icp_query_timeout'}]},
                                                {items:[{xtype:'displayfield',hideLabel:true,value:'ms'}]}
                                            ]
                                            }]},
                                    //2nd col
                                    {bodyStyle:'padding-left:15px;',items:[
                                            {
                                            layout:'table',
                                            border:false,
                                            width:300,
                                            xtype:'panel',
                                            layoutConfig: {columns: 4},
                                            defaults:{layout:'form',bodyStyle:'padding-left:5px;',border:false,height:40},
                                            items: [
                                                {items:[{xtype:'radio', fieldLabel:'Multicase ICP timeout',name:'mcast_icp_query_timeout-radio',boxLabel:'Default',checked:true,inputValue: 0}]},
                                                {items:[{xtype:'radio', fieldLabel:'',hideLabel:true,name:'mcast_icp_query_timeout-radio',boxLabel:'',inputValue: 1}]},
                                                {items:[{xtype:'numberfield',fieldLabel:'',hideLabel:true,width:60,name:'mcast_icp_query_timeout'}]},
                                                {items:[{xtype:'displayfield',hideLabel:true,value:'ms'}]}
                                            ]
                                            }]}
                                ]
                             }
                             ,{
                                layout:'table',
                                xtype:'panel',
                                layoutConfig: {columns: 4},
                                defaults:{layout:'form',bodyStyle:'padding-left:5px;',height:40,border:false},
                                items: [
                                    {items:[{xtype:'radio', fieldLabel:'Dead peer timeout',name:'dead_peer_timeout-radio',boxLabel:'Default',checked:true,inputValue: 0}]},
                                    {items:[{xtype:'radio', fieldLabel:'',hideLabel:true,name:'dead_peer_timeout-radio',boxLabel:'',inputValue: 1}]},
                                    {items:[{xtype:'numberfield',fieldLabel:'',hideLabel:true,width:60,name:'dead_peer_timeout'}]},
                                    {items:[{xtype:'displayfield',hideLabel:true,value:'secs'}]}
                                ]
                              }
                        ]
                    }];




    this.refreshBtn = new Ext.Button({
        text: 'Refresh',
        tooltip: 'refresh',
        iconCls: 'x-tbar-loading',
        scope:this,
        handler: function(button,event){this.loadData();}
    });

    this.savebtn = new Ext.Button({text: 'Save',iconCls:'page-save',handler:this.onSave,scope:this});


    ETFW.Squid.Othercaches.OptionsTpl.superclass.constructor.call(this, {
        // baseCls: 'x-plain',
        labelWidth: 120,
        bodyStyle:'padding-top:10px',
        url:<?php echo json_encode(url_for('etfw/json'))?>,
        defaultType: 'textfield',
        buttonAlign:'left',
        autoScroll:true,
        border:false,
        defaults:{border:false},
        items: [allFields],
        tbar: [this.refreshBtn],
        bbar: [this.savebtn]
    });



    //on loadRecord finished bind data to form correctly....
    this.on('actioncomplete',function(form,action){
        var rec_data = action.result.data; // data

        for(var i=0,len=this.params.length;i<len;i++){
            var radio_field = form.findField(this.params[i]+'-radio');
            if(!Ext.isEmpty(rec_data[this.params[i]])) radio_field.setValue(1);
            else radio_field.setValue(0);
        }

    });

};

Ext.extend(ETFW.Squid.Othercaches.OptionsTpl, Ext.form.FormPanel, {
    reset:function(){
        this.savebtn.setText('Add');
        this.getForm().reset();
    }
    ,onRender:function() {
        // call parent
        ETFW.Squid.Othercaches.OptionsTpl.superclass.onRender.apply(this, arguments);

        // set wait message target
        this.getForm().waitMsgTarget = (this.ownerCt).getEl();

        // loads form after initial layout
        this.on({
             afterlayout:{scope:this, single:true, fn:function() {this.loadData();}}
            });

    } // eo function onRender
    ,loadData:function(){
            this.refreshBtn.addClass('x-item-disabled');
            this.getForm().reset();
            var params = {"fields":this.params};
            //'get_proxy_ports'
            this.load({
                url: this.url,
                waitMsg:'Loading...',
                params:{id:this.service_id,method:'get_config_fields',mode:'get_othercaches_options',params:Ext.encode(params)},
                success:function(){
                    this.refreshBtn.removeClass('x-item-disabled');
                }
                ,scope:this
            });
    }
    ,onSave:function(){

        if (this.form.isValid()) {

            var alldata = this.form.getValues();
            var send_data = new Object();

            for(var i=0,len=this.params.length;i<len;i++){
                var radio_value = alldata[this.params[i]+'-radio'];
                if(radio_value==1) send_data[this.params[i]] = alldata[this.params[i]];
                else send_data[this.params[i]] = '';
            }

            var conn = new Ext.data.Connection({
                listeners:{
                    // wait message.....
                    beforerequest:function(){
                        Ext.MessageBox.show({
                            title: 'Please wait',
                            msg: 'Updating cache options information...',
                            width:300,
                            wait:true,
                            modal: false
                        });
                    },// on request complete hide message
                    requestcomplete:function(){Ext.MessageBox.hide();}
                }
            });// end conn

            conn.request({
                url: this.url,
                params:{id:this.service_id,method:'set_config',mode:'set_cache_options',
                    params:Ext.encode(send_data)
                },
                failure: function(resp,opt){
                    var response = Ext.util.JSON.decode(resp.responseText);
                    Ext.MessageBox.alert('Error Message', response['info']);
                    Ext.ux.Logger.error(response['error']);

                },
                // everything ok...
                success: function(resp,opt){
                    var msg = 'Updated cache options';
                    Ext.ux.Logger.info(msg);
                    View.notify({html:msg});
                    this.loadData();


                },scope:this
            });// END Ajax request


        } else{
            Ext.MessageBox.alert('error', 'Please fix the errors noted.');
        }



    }
});

ETFW.Squid.Othercaches.Main = function(service_id) {

    var cache_hosts_panel = new Ext.Panel({
        title:'Cache Hosts List',
        layout:'border',
        border:false,
        defaults:{border:false},
        items:[
            {
                url:<?php echo json_encode(url_for('etfw/json'))?>
                ,region:'center'
                ,collapsible: false
                ,margins:'3 3 3 3'
                ,service_id:service_id
                ,xtype:'etfw_squid_othercaches_cachegrid'
                ,layout:'fit'
            }
        ]
        ,listeners:{
            loadOthercacheTplRecord:function(record){
                Ext.getBody().mask('Preparing data...');
                host_cache_win.setTitle('Edit Cache Host');
                (function(){
                    host_cache_win.show();
                    host_cache_form.loadRecord(record);
                }).defer(100);
            }
            ,clearOthercacheTpl:function(){
                Ext.getBody().mask('Preparing data...');
                host_cache_win.setTitle('Create Cache Host');
                (function(){
                        host_cache_win.show();
                        host_cache_form.reset();
                }).defer(100);
            }
        }
    });



    var host_cache_form = new ETFW.Squid.Othercaches.CacheTpl({service_id:service_id});

    var viewerSize = Ext.getBody().getViewSize();
    var windowHeight = viewerSize.height * 0.95;
    windowHeight = Ext.util.Format.round(windowHeight,0);

     // create and show window
     var host_cache_win = new Ext.Window({
        title:'Create Cache Host'
        ,layout:'fit'
        ,width:850
        ,modal:true
        ,height:windowHeight
        ,closable:true
        ,closeAction:'hide'
        ,border:false
        ,items:host_cache_form
        ,listeners:{
            show:function(){
                Ext.getBody().unmask();
            }
            ,reloadParentContent:function(){
                host_cache_win.hide();
                cache_hosts_panel.get(0).reload();
            }
        }
    });



    /*
    *
    * indirect fetch acl panel
    *
    */

    var indirect_fetch_form = new ETFW.Squid.Acl.RestrictionTpl({fieldsetTitle:'Never directly fetch requests matching ACLs',method:'set_never_direct',service_id:service_id});

    var indirect_fetch_panel = new Ext.Panel({
        title:'ACLs never to fetch directly',
        layout:'border',
        border:false,
        defaults: {
                collapsible: true,
                split: true,
                useSplitTips:true,
                border:false
        },
        items:[
            {
                region:'center'
                ,url:<?php echo json_encode(url_for('etfw/json'))?>
                ,collapsible: false
                ,margins:'3 0 3 3'
                ,service_id:service_id
                ,xtype:'etfw_squid_restriction_grid'
                ,restrictionTitle: 'ACLs never to fetch directly'
                ,method:'get_never_direct'
                ,layout:'fit'
            },
            {region:'east',
                margins: '3 3 3 0',
                cmargins: '3 3 3 3',
                border:true,
                collapsed:true,
                autoScroll:true,
                title:'Create Indirect Fetch',
                width:350,
                items:[indirect_fetch_form],
                listeners:{
                    beforeexpand:function(){
                        Ext.getBody().mask('Expanding panel...');
                    }
                    ,expand:{fn:function(){
                        Ext.getBody().unmask();
                    },delay:100}
                    ,beforecollapse:function(){
                        Ext.getBody().mask('Collapsing panel...');
                    }
                    ,collapse:{fn:function(){
                        Ext.getBody().unmask();
                    },delay:100}
                    ,reloadRestriction:function(){
                            indirect_fetch_panel.get(0).reload();
                    }
                }
            }
        ]
        ,listeners:{
            loadRestrictionTplRecord:function(record){
                var right_region = indirect_fetch_panel.get(1);
                right_region.setTitle('Edit Indirect Fetch');

                if(!right_region.isVisible()){
                    Ext.getBody().mask('Loading panel...');

                    (function(){
                        right_region.expand(false);
                        indirect_fetch_form.loadRecord(record);
                    }).defer(100);
                }
                else indirect_fetch_form.loadRecord(record);
            }
            ,clearRestrictionTpl:function(mode){
                var right_region = indirect_fetch_panel.get(1);
                right_region.setTitle('Create Indirect Fetch');

                if(mode == 'show' && !right_region.isVisible()){
                    Ext.getBody().mask('Loading panel...');

                    (function(){
                        right_region.expand(false);
                        indirect_fetch_form.reset();
                    }).defer(100);
                }
                else indirect_fetch_form.reset();
            }
        }
    });

    indirect_fetch_panel.on('beforerender',function(){
        Ext.getBody().mask('Loading ETFW squid indirect ACL panel...');}
        ,this
    );

    indirect_fetch_panel.on('render',function(){
        Ext.getBody().unmask();}
        ,this
    ,{delay:100}
    );




   /*
    *
    * direct fetch acl panel
    *
    */
    var direct_fetch_form = new ETFW.Squid.Acl.RestrictionTpl({fieldsetTitle:'Directly fetch requests matching ACLs',method:'set_always_direct',service_id:service_id});

    var direct_fetch_panel = new Ext.Panel({
        title:'ACLs to fetch directly',
        layout:'border',
        border:false,
        defaults: {
                collapsible: true,
                split: true,
                useSplitTips:true,
                border:false
        },
        items:[
            {
                region:'center'
                ,url:<?php echo json_encode(url_for('etfw/json'))?>
                ,collapsible: false
                ,service_id:service_id
                ,margins:'3 0 3 3'
                ,xtype:'etfw_squid_restriction_grid'
                ,restrictionTitle: 'ACLs to fetch directly'
                ,method:'get_always_direct'
                ,layout:'fit'
            },
            {
                region:'east',
                margins: '3 3 3 0',
                cmargins: '3 3 3 3',
                border:true,
                collapsed:true,
                autoScroll:true,
                title:'Create Direct Fetch',
                width:350,
                items:[direct_fetch_form],
                listeners:{
                    beforeexpand:function(){
                        Ext.getBody().mask('Expanding panel...');
                    }
                    ,expand:{fn:function(){
                        Ext.getBody().unmask();
                    },delay:100}
                    ,beforecollapse:function(){
                        Ext.getBody().mask('Collapsing panel...');
                    }
                    ,collapse:{fn:function(){
                        Ext.getBody().unmask();
                    },delay:100}
                    ,reloadRestriction:function(){
                        direct_fetch_panel.get(0).reload();
                    }
                }
            }
        ]
        ,listeners:{
            loadRestrictionTplRecord:function(record){
                var right_region = direct_fetch_panel.get(1);
                right_region.setTitle('Edit Direct Fetch');
                if(!right_region.isVisible()){
                    Ext.getBody().mask('Loading panel...');

                    (function(){
                        right_region.expand(false);
                        direct_fetch_form.loadRecord(record);
                    }).defer(100);
                }
                else direct_fetch_form.loadRecord(record);

            }
            ,clearRestrictionTpl:function(mode){
                var right_region = direct_fetch_panel.get(1);
                right_region.setTitle('Create Direct Fetch');

                if(mode == 'show' && !right_region.isVisible()){
                    Ext.getBody().mask('Loading panel...');

                    (function(){
                        right_region.expand(false);
                        direct_fetch_form.reset();
                    }).defer(100);
                }
                else direct_fetch_form.reset();
            }
        }
    });

    direct_fetch_panel.on('beforerender',function(){
        Ext.getBody().mask('Loading ETFW squid direct ACL panel...');}
        ,this
    );

    direct_fetch_panel.on('render',function(){
        Ext.getBody().unmask();}
        ,this
    ,{delay:100}
    );



    var cache_options_form = new ETFW.Squid.Othercaches.OptionsTpl({service_id:service_id});

    var cache_options_panel = new Ext.Panel({
        title:'Cache Selection Options',
        layout:'border',
        border:false,
        defaults:{border:false},
        items:[
            {
                region:'center'
                ,defaults:{border:false}
                ,collapsible: false
                ,xtype:'tabpanel'
                ,activeTab:0
                ,tabPosition: 'bottom'
                ,items:[{title:'Main options',
                        layout:'fit',autoScroll:true,
                        items:cache_options_form}
                        ,direct_fetch_panel
                        ,indirect_fetch_panel]
            }]
    });


    ETFW.Squid.Othercaches.Main.superclass.constructor.call(this, {
        border:false,
        defaults:{border:false},
        layout:'fit',
        title: 'Other Caches',
        items: [{
                xtype:'tabpanel',
                activeTab:0,
                items:[cache_options_panel,cache_hosts_panel]
            }]
    });

}

// define public methods
Ext.extend(ETFW.Squid.Othercaches.Main, Ext.Panel, {
    reload:function(){
        var tabPanel = this.get(0);
        var cacheOptionsPanel = tabPanel.get(0);
        var cacheHosts = tabPanel.get(1);
        if(cacheHosts.rendered) cacheHosts.get(0).reload();

        if(cacheOptionsPanel.rendered){
            var cacheOptions = cacheOptionsPanel.get(0);
            var mainOptions = cacheOptions.get(0);
            var fetchDirect = cacheOptions.get(1);
            var fetchIndirect = cacheOptions.get(2);

            if(mainOptions.rendered) (mainOptions.get(0)).loadData();

            if(fetchDirect.rendered){
                var grid_direct = fetchDirect.get(0);
                var formPanel_direct = fetchDirect.get(1);
                grid_direct.reload();
                (formPanel_direct.get(0)).reload();
            }

            if(fetchIndirect.rendered){
                var grid_indirect = fetchIndirect.get(0);
                var formPanel_indirect = fetchIndirect.get(1);
                grid_indirect.reload();
                (formPanel_indirect.get(0)).reload();
            }



        }

    }
});

</script>
