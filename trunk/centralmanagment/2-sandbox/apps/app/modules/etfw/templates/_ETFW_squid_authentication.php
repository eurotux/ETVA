<script>    
Ext.ns('ETFW.Squid.Authentication');


ETFW.Squid.Authentication.Form_TimeCombo = function(config) {

    // call parent constructor
    ETFW.Squid.Authentication.Form_TimeCombo.superclass.constructor.call(this, config);

    var config = {
        triggerAction:'all'
        ,mode:'local'
        ,width:80
        ,forceSelection: true
        ,editable:false
        ,fieldLabel: ''
        ,labelSeparator:''
        ,editable:false
        ,store:new Ext.data.SimpleStore({
            fields:['value', 'name']
            ,data:[['hour', 'hours'],
                    ['month', 'months'],
                    ['week', 'weeks'],
                    ['second', 'seconds'],
                    ['fortnight', 'fortnights'],
                    ['minute', 'minutes'],
                    ['decade', 'decades'],
                    ['day', 'days'],
                    ['year', 'years']
                  ]
        })
        ,displayField:'name'
        ,valueField:'value'
        ,hiddenName:this.name
        ,value:'hour'
        ,validator:function(value){
                        if(value=='') this.setValue(this.originalValue);
                        return true;
                   }
        ,scope:this
        ,selectOnFocus:true
    };

    Ext.apply(this, config);

}// end constructor

// extend
Ext.extend(ETFW.Squid.Authentication.Form_TimeCombo, Ext.form.ComboBox, {}); // end of extend


ETFW.Squid.Authentication.Form = function(service_id) {

    this.service_id = service_id;

    this.params = ["authenticate_ip_ttl","auth_param"];


    this.refreshBtn = new Ext.Button({
                        text: 'Refresh',
                        tooltip: 'refresh',
                        iconCls: 'x-tbar-loading',
                        scope:this,
                        handler: function(button,event){this.loadData();}
                    });
    this.saveBtn = new Ext.Button({
                        text: 'Save',
                        tooltip: 'save',
                        scope:this,
                        handler: this.onSave
                    });

    var allFields = [
        {xtype:'fieldset',
        defaults:{border:false},
        title:'Ports and Networking options',
        items:[
            this.buildDefaultItem('Basic authentication program','None','basic_program',280),
            this.buildDefaultItem('Number of authentication programs','Default','basic_children',100),
            {
             layout:'table',
             layoutConfig: {columns: 2},
             defaults:{layout:'form',border:false},
             items: [
                     this.buildDefaultItem('Authentication cache time','Default','basic_credentialsttl',60),
                     {labelWidth:1,bodyStyle:'padding-bottom:5px;',items:[
                             new ETFW.Squid.Authentication.Form_TimeCombo({name: 'basic_credentialsttl-time'})]}]
            },
            this.buildDefaultItem('Authentication realm','Default','basic_realm',280),
            {html:'<hr/>'},
            /*
            *
            * DIGEST AUTHENTICATION PROGRAM
            *
            */
            this.buildDefaultItem('Digest authentication program','None','digest_program',280),
            this.buildDefaultItem('Number of authentication programs','Default','digest_children',100),
            this.buildDefaultItem('Authentication realm','Default','digest_realm',280),
            {html:'<hr/>'},
            /*
            *
            * NTLM AUTHENTICATION PROGRAM
            *
            */
            this.buildDefaultItem('NTLM authentication program','None','ntlm_program',280),
            this.buildDefaultItem('Number of authentication programs','Default','ntlm_children',100),
            {
                layout:'table',width:700,
                layoutConfig: {columns: 3},
                defaults:{layout:'form',border:false,height:40},
                items: [
                    {items:[{xtype:'radio', fieldLabel:'Number of times an NTLM challenge can be re-used',name:'ntlm_max_challenge_reuses-src',boxLabel:'Default',checked:true,inputValue: '0'}]},
                    {items:[{xtype:'radio', fieldLabel:'',name:'ntlm_max_challenge_reuses-src',hideLabel:true,boxLabel:'',inputValue: '1'}]},
                    {bodyStyle:'padding-bottom:5px;',items:[{xtype:'textfield',name:'ntlm_max_challenge_reuses',fieldLabel:'',hideLabel:true}]}
                ]
            },
            {
             layout:'table',
             layoutConfig: {columns: 2},
             defaults:{layout:'form',border:false},
             items: [
                     this.buildDefaultItem('Lifetime of NTLM challenges','Default','ntlm_max_challenge_lifetime',100),
                     {labelWidth:1,bodyStyle:'padding-bottom:5px;',items:[
                             new ETFW.Squid.Authentication.Form_TimeCombo({name: 'ntlm_max_challenge_lifetime-time'})]}]
            },
            {html:'<hr/>Authenticate IP TTL is required to be > 0 if you are using a "max_user_ip" ACL.\n\
                        Enter the time you wish Squid to remember the User/IP relationship.\n\
                        The user may only logon from the remembered IP until this amount of time has passed, even if they have closed their browser.\n\
                    <hr/>'},
            {
             layout:'table',
             layoutConfig: {columns: 2},
             defaults:{layout:'form',border:false},
             items: [
                     this.buildDefaultItem('Authenticate IP cache time','Default','authenticate_ip_ttl',100),
                     {labelWidth:1,bodyStyle:'padding-bottom:5px;',items:[
                             new ETFW.Squid.Authentication.Form_TimeCombo({name: 'authenticate_ip_ttl-time'})]}]
            }]
        }
        ];

    ETFW.Squid.Authentication.Form.superclass.constructor.call(this, {
        labelWidth: 190,
        bodyStyle:'padding-top:10px',
        url:<?php echo json_encode(url_for('etfw/json'))?>,
        autoScroll:true,
        border:false,
        defaults:{border:false},
        tbar:[this.refreshBtn],
        items: allFields,
        bbar: [this.saveBtn]
    });

    // on loadRecord finished bind data to form correctly....
        this.on('actioncomplete',function(form,action){

            var rec_data = action.result.data; // data
            var basic_program_src = this.find('name','basic_program-src')[0]; // get component by name...
            if(!Ext.isEmpty(rec_data['basic_program'])) basic_program_src.setValue('1');

            var basic_children_src = this.find('name','basic_children-src')[0]; // get component by name...
            if(!Ext.isEmpty(rec_data['basic_children'])) basic_children_src.setValue('1');

            var basic_credentialsttl_src = this.find('name','basic_credentialsttl-src')[0]; // get component by name...
            if(!Ext.isEmpty(rec_data['basic_credentialsttl'])) basic_credentialsttl_src.setValue('1');

            var basic_realm_src = this.find('name','basic_realm-src')[0]; // get component by name...
            if(!Ext.isEmpty(rec_data['basic_realm'])) basic_realm_src.setValue('1');

            var digest_program_src = this.find('name','digest_program-src')[0]; // get component by name...
            if(!Ext.isEmpty(rec_data['digest_program'])) digest_program_src.setValue('1');

            var digest_children_src = this.find('name','digest_children-src')[0]; // get component by name...
            if(!Ext.isEmpty(rec_data['digest_children'])) digest_children_src.setValue('1');

            var digest_realm_src = this.find('name','digest_realm-src')[0]; // get component by name...
            if(!Ext.isEmpty(rec_data['digest_realm'])) digest_realm_src.setValue('1');

            var ntlm_program_src = this.find('name','ntlm_program-src')[0]; // get component by name...
            if(!Ext.isEmpty(rec_data['ntlm_program'])) ntlm_program_src.setValue('1');

            var ntlm_children_src = this.find('name','ntlm_children-src')[0]; // get component by name...
            if(!Ext.isEmpty(rec_data['ntlm_children'])) ntlm_children_src.setValue('1');

            var ntlm_children_src = this.find('name','ntlm_children-src')[0]; // get component by name...
            if(!Ext.isEmpty(rec_data['ntlm_children'])) ntlm_children_src.setValue('1');

            var ntlm_max_challenge_reuses_src = this.find('name','ntlm_max_challenge_reuses-src')[0]; // get component by name...
            if(!Ext.isEmpty(rec_data['ntlm_max_challenge_reuses'])) ntlm_max_challenge_reuses_src.setValue('1');

            var ntlm_max_challenge_lifetime_src = this.find('name','ntlm_max_challenge_lifetime-src')[0]; // get component by name...
            if(!Ext.isEmpty(rec_data['ntlm_max_challenge_lifetime'])) ntlm_max_challenge_lifetime_src.setValue('1');

            var authenticate_ip_ttl_src = this.find('name','authenticate_ip_ttl-src')[0]; // get component by name...
            if(!Ext.isEmpty(rec_data['authenticate_ip_ttl'])) authenticate_ip_ttl_src.setValue('1');

        },this);//end actioncomplete

     this.on({
             afterlayout:{scope:this, single:true, fn:function() {this.loadData();}}
            });

};

Ext.extend(ETFW.Squid.Authentication.Form, Ext.form.FormPanel, {
    onSave:function(){


        var alldata = this.form.getValues();
        var send_data = new Object();
        send_data['auth_param'] = [];
        var auth_params = [];

        if (this.form.isValid()) {

            // BASIC
            var basic_program_src = alldata['basic_program-src'];
            if(basic_program_src==1) auth_params.push('basic program '+alldata['basic_program']);

            var basic_children_src = alldata['basic_children-src'];
            if(basic_children_src==1) auth_params.push('basic children '+alldata['basic_children']);

            var basic_credentialsttl_src = alldata['basic_credentialsttl-src'];
            var basic_credentialsttl_time = alldata['basic_credentialsttl-time'];
            if(basic_credentialsttl_src==1) auth_params.push('basic credentialsttl '+alldata['basic_credentialsttl']+' '+basic_credentialsttl_time);

            var basic_realm_src = alldata['basic_realm-src'];
            if(basic_realm_src==1) auth_params.push('basic realm '+alldata['basic_realm']);

            // DIGEST
            var digest_program_src = alldata['digest_program-src'];
            if(digest_program_src==1) auth_params.push('digest program '+alldata['digest_program']);

            var digest_children_src = alldata['digest_children-src'];
            if(digest_children_src==1) auth_params.push('digest children '+alldata['digest_children']);

            var digest_realm_src = alldata['digest_realm-src'];
            if(digest_realm_src==1) auth_params.push('digest realm '+alldata['digest_realm']);

            // NTLM
            var ntlm_program_src = alldata['ntlm_program-src'];
            if(ntlm_program_src==1) auth_params.push('ntlm program '+alldata['ntlm_program']);

            var ntlm_children_src = alldata['ntlm_children-src'];
            if(ntlm_children_src==1) auth_params.push('ntlm children '+alldata['ntlm_children']);

            var ntlm_max_challenge_reuses_src = alldata['ntlm_max_challenge_reuses-src'];
            if(ntlm_max_challenge_reuses_src==1) auth_params.push('ntlm max_challenge_reuses '+alldata['ntlm_max_challenge_reuses']);

            var ntlm_max_challenge_lifetime_src = alldata['ntlm_max_challenge_lifetime-src'];
            var ntlm_max_challenge_lifetime_time = alldata['ntlm_max_challenge_lifetime-time'];
            if(ntlm_max_challenge_lifetime_src==1) auth_params.push('ntlm max_challenge_lifetime '+alldata['ntlm_max_challenge_lifetime']+' '+ntlm_max_challenge_lifetime_time);

            var authenticate_ip_ttl_src = alldata['authenticate_ip_ttl-src'];
            var authenticate_ip_ttl_time = alldata['authenticate_ip_ttl-time'];
            if(authenticate_ip_ttl_src==1) send_data['authenticate_ip_ttl'] = alldata['authenticate_ip_ttl']+' '+authenticate_ip_ttl_time;
            else send_data['authenticate_ip_ttl'] = '';

            send_data['auth_param'] = auth_params;


            var conn = new Ext.data.Connection({
                listeners:{
                    // wait message.....
                    beforerequest:function(){
                        Ext.MessageBox.show({
                            title: 'Please wait',
                            msg: 'Updating ports...',
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
                params:{id:this.service_id,method:'set_config',
                    params:Ext.encode(send_data)
                },
                failure: function(resp,opt){
                    var response = Ext.util.JSON.decode(resp.responseText);
                    Ext.MessageBox.alert('Error Message', response['info']);
                    Ext.ux.Logger.error(response['error']);

                },
                // everything ok...
                success: function(resp,opt){
                    var msg = 'Updated authentication programs information';
                    Ext.ux.Logger.info(msg);
                    View.notify({html:msg});
                    this.loadData();


                },scope:this
            });// END Ajax request


        } else{
            Ext.MessageBox.alert('error', 'Please fix the errors noted.');
        }



    }
    ,buildDefaultItem:function(fieldlabel,boxlabel,name,width){
        var txt_field = {xtype:'textfield',name:name,fieldLabel:'',hideLabel:true};
        if(width) txt_field = {xtype:'textfield',name:name,fieldLabel:'',width:width,hideLabel:true};
        var config = {
            layout:'table',
            layoutConfig: {columns: 3},
            defaults:{layout:'form',border:false},
            items: [
                {items:[{xtype:'radio', fieldLabel:fieldlabel,name:name+'-src',boxLabel:boxlabel,checked:true,inputValue: '0'}]},
                {items:[{xtype:'radio', fieldLabel:'',hideLabel:true,name:name+'-src',boxLabel:'',inputValue: '1'}]},
                {bodyStyle:'padding-bottom:5px;',items:[txt_field]}
            ]
        };
        return config;

    }
    ,loadData:function(){
            this.refreshBtn.addClass('x-item-disabled');
            this.getForm().reset();
            var params = {"fields":this.params};
            //'get_proxy_ports'
            this.load({
                url: this.url,
                waitMsg:'Loading...',
                params:{id:this.service_id,method:'get_config_fields',mode:'get_auth_program',params:Ext.encode(params)},
                success:function(){
                    this.refreshBtn.removeClass('x-item-disabled');
                }
                ,scope:this
            });
    }
});


ETFW.Squid.Authentication.Main = function(service_id) {


    var form = new ETFW.Squid.Authentication.Form(service_id);

    form.on('beforerender',function(){
            Ext.getBody().mask('Loading ETFW squid authentication panel...');}
            ,this
    );

    form.on('render',function(){
            Ext.getBody().unmask();}
            ,this
            ,{delay:10}
    );


    ETFW.Squid.Authentication.Main.superclass.constructor.call(this, {
        border:false,
        layout:'fit',
        defaults:{border:false},
        title: 'Authentication Programs',
        items:form

    });
}

// define public methods
Ext.extend(ETFW.Squid.Authentication.Main, Ext.Panel, {
    reload:function(){
        this.get(0).loadData();
    }
});

</script>