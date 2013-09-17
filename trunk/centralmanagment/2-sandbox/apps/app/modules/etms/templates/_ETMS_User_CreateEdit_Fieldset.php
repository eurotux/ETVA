<script>
Ext.ns("ETMS.User.CreateEdit.Fieldset");

ETMS.User.CreateEdit.onSave = function(alldata){

    var service_id = alldata['service_id'];
    var url = <?php echo json_encode(url_for('etms/json'))?>;
    var send_data = new Object();
    send_data['user_name']      = alldata['user_name'];
    send_data['domain']         = alldata['domain'];
    send_data['real_name']      = alldata['real_name'];
    send_data['isActive']       = alldata['isActive'];
    if(send_data['isActive']){
        send_data['isActive'] = 'active';
    }else{
        send_data['isActive'] = 'noaccess';
    }

    var dlv_type = alldata['delivery_type'];
    if(dlv_type == ""){
        send_data['delivery_type']  = "noforward";
    }else{
        send_data['delivery_type']  = dlv_type;
    }
    
    if(alldata['allowExternalSend']){
        send_data['allowExternalSend'] = 1;
    }else{
        send_data['allowExternalSend'] = 0;
    }

    send_data['password'] = alldata['password'];

    var quota = parseInt(alldata['mailbox_quota']);

    send_data['mailbox_quota'] = 0;
    if(quota > 0){
        send_data['mailbox_quota'] = quota;
    }

    send_data['automatic_answer']   = alldata['automatic_answer'];

    var method = 'create_user';
    var ok_msg = <?php echo json_encode(__('Client options created successfully')) ?>;
    if( alldata['action'] == 'edit' ){

        var username_arr = new Array();
        username_arr.push( alldata['user_name'] );
        username_arr.push( alldata['domain'] );
        send_data['user_name'] = username_arr.join('@');

        method = 'edit_user';
        ok_msg = <?php echo json_encode(__('Client options edited successfully')) ?>;
    }

  //============= ENVIAR O PEDIDO ==============
    var conn = new Ext.data.Connection({
        listeners:{
        // wait message.....
            beforerequest:function(){
                Ext.MessageBox.show({
                    title: <?php echo json_encode(__('Please wait')) ?>,
                    msg: <?php echo json_encode(__('Updating Client options info...')) ?>,
                    width:300,
                    wait:true,
                    modal: true
                });
            },// on request complete hide message
            requestcomplete:function(){Ext.MessageBox.hide();}
        }
    });// end conn

    //create
    conn.request({
        url: url,
        params:{id:service_id,method: method,params:Ext.encode(send_data)},
        failure: function(resp,opt){

            if(!resp.responseText){
                Ext.ux.Logger.error(resp.statusText);
                return;
            }

            var response = Ext.util.JSON.decode(resp.responseText);

            var err_msg = '';
            if( response['error'] && response['info'] )
                err_msg = response['error'] + ': ' + response['info'];
            else if( response['error'] )
                err_msg = response['error'];
            else if( response['info'] )
                err_msg = response['info'];

            Ext.MessageBox.alert(<?php echo json_encode(__('Error Message'))?>, err_msg);
            Ext.ux.Logger.error(response['agent'],err_msg);

        },
        success: function(resp,opt){
            var response = Ext.util.JSON.decode(resp.responseText);
            Ext.ux.Logger.info(response['agent'],ok_msg);

            var extra = { 'user_name': alldata['user_name'], 'domain': alldata['domain'] };
            call_save_sfGuardUser_UpdateUserService({ id: alldata['user_id'], service_id: service_id, extra: Ext.encode(extra) });

        }
    });
};

ETMS.User.CreateEdit.Fieldset = function(config){
    var Obj = {
                border: false,
                bodyStyle: 'padding: 10px; background:transparent;',
                defaults: { border: false },
                items: [ { xtype: 'fieldset',
                            id:'user_list-createedit-tab-etms',
                            checkboxToggle: true,
                            collapsed: true,
                            title: <?php echo json_encode(__('Enable ETMailServer user information?')) ?>,
                            items: [
                                    {
                                        xtype: 'hidden',
                                        name: 'service_id',
                                        ref: 'service_id',
                                        value: parseInt('<?php echo $modulesConf['ETMS']['mailbox']['service_id'] ?>'),
                                    },{
                                        xtype: 'hidden',
                                        name: 'domain_quota_limit',
                                        ref: 'domain_quota_limit'
                                    },{ xtype:'radio',
                                        name: 'etms-action',
                                        inputValue: 'add',
                                        fieldLabel:__('New account'),
                                        checked: true,
                                        listeners: {
                                            'check': function(e,check){
                                                e.ownerCt.account_new.setDisabled(!check);
                                                e.ownerCt.txtuser_name.setDisabled(!check);
                                                e.ownerCt.txtdomain.setDisabled(!check);
                                                e.ownerCt.account_new.setVisible(check);
                                                e.ownerCt.txtuser_name.setVisible(check);
                                                e.ownerCt.txtdomain.setVisible(check);
                                                if( check )
                                                    e.ownerCt.reset({ 'action': 'add' });
                                            }
                                        }
                                    },{ xtype:'radio',
                                        name: 'etms-action',
                                        inputValue: 'edit',
                                        checked: false,
                                        fieldLabel:__('Edit account'),
                                        listeners: {
                                            'check': function(e,check){
                                                e.ownerCt.account_edit.setDisabled(!check);
                                                e.ownerCt.account_edit.setVisible(check);
                                                if( check )
                                                    e.ownerCt.reset({ 'action': 'edit' });
                                            }
                                        }
                                    },{
                                        xtype: 'combo',
                                        width: 180,
                                        typeAhead: true,
                                        triggerAction: 'all',
                                        ref: 'domain',
                                        name: 'domain',
                                        mode: 'local',
                                        store: new Ext.data.JsonStore({
                                            proxy:new Ext.data.HttpProxy({url:<?php echo json_encode(url_for('etms/json'))?>}),
                                            totalProperty: 'total',
                                            baseParams:{ id:parseInt('<?php echo $modulesConf['ETMS']['domain']['service_id'] ?>'),method:'list_domains'},
                                            root: 'value',
                                            fields: [            
                                               {name: 'name'},
                                               {name: 'user'},
                                               {name: 'password'},
                                               {name: 'description'},
                                               {name: 'server_quota'},
                                               {name: 'max_mailboxes'},
                                               {name: 'mailbox'},
                                               {name: 'isActive'}
                                            ],
                                            autoLoad: true
                                        }),
                                        fieldLabel: __('Domain'),
                                        editable: false,
                                        valueField: 'name',
                                        displayField: 'name',
                                        allowBlank: false,
                                        listeners: {
                                            select : function(cb,r,ix){
                                                cb.ownerCt.account_edit.getStore().baseParams.params = Ext.encode({'domain': r.data.name});

                                                if( cb.ownerCt.txtdomain )
                                                    cb.ownerCt.txtdomain.setValue('@'+r.data.name);
                                                var server_quota = parseInt(r.data.server_quota);
                                                if( server_quota > 0 ){
                                                    this.ownerCt.server_quota_limit.setValue(server_quota + ' (Bytes)');
                                                    this.ownerCt.domain_quota_limit.setValue(server_quota);
                                                }
                                            }
                                        }

                                },{
                                    fieldLabel: __('Account'),
                                    border:false,
                                    bodyStyle:'background:transparent;',
                                    items:[ 
                                    new Ext.form.ComboBox({
                                        fieldLabel: __('Account'),
                                        hidden: true,
                                        ref: '../account_edit',
                                        selectOnFocus: false,
                                        editable: false,        
                                        //mode: 'local',
                                        triggerAction: 'all',
                                        allowBlank: false,
                                        store: new Ext.data.JsonStore({
                                            proxy:new Ext.data.HttpProxy({url:<?php echo json_encode(url_for('etms/json'))?>}),
                                            totalProperty: 'total',
                                            baseParams:{ id:parseInt('<?php echo $modulesConf['ETMS']['mailbox']['service_id'] ?>'),method:'get_users', params:Ext.encode({})},
                                            root: 'value',
                                            fields: [            
                                               {name: 'user_name'},
                                               {name: 'real_name'},
                                               {name: 'isActive'},
                                               {name: 'allowExternalSend'},
                                               {name: 'mailbox_quota'},
                                               {name: 'mailbox_quota'},
                                               {name: 'delivery_type'},
                                               {name: 'automatic_answer'}
                                            ],
                                            autoLoad: false
                                        }),
                                        valueField: 'user_name',
                                        displayField: 'user_name',
                                        name: 'user_name',
                                        xtype:'combo', 	    
                                        width: 180,
                                        listeners: {
                                            'select': function(cbx, r, i){
                                                cbx.ownerCt.ownerCt.loadRecord(r.data);
                                            }
                                        } }),
                                        {
                                            layout:'column',
                                            fieldLabel: __('Account'),
                                            bodyStyle:'background:transparent;',
                                            border: false,
                                            ref: '../account_new',
                                            items: [{
                                                xtype: 'textfield',
                                                name: 'user_name',
                                                required: true,
                                                ref:'../../txtuser_name',
                                                style:'text-align:right;',
                                                allowBlank: false
                                            },{
                                                xtype: 'displayfield',
                                                ref:'../../txtdomain',
                                                value: '@',
                                                style:'padding-top:5px;'
                                            }]
                                        }
                                    ]
                                },{
                                        xtype: 'textfield',
                                        fieldLabel: __('Real Name'),
                                        name: 'real_name',
                                        ref:'real_name',
                                        allowBlank: false
                                },{
                                        fieldLabel: __('Change Password'),
                                        border: false,
                                        bodyStyle:'background:transparent;',
                                        items: [{
                                            xtype: 'checkbox', 	//pode ser feito como os radio buttons
                                            name: 'change_pwd',
                                            ref: '../change_pwd',
                                            checked: false,
                                            hidden: true,
                                            handler: function(checkbox, checked){
                                                this.ownerCt.find('name','password')[0].setDisabled(!checked);
                                            }
                                        },{
                                            xtype: 'textfield',
                                            name: 'password',
                                            ref: '../password',
                                            inputType: 'password',
                                            minLength: 6,
                                            minLengthText: <?php echo json_encode(__('Password must be at least 6 characters long.')) ?>,
                                            allowBlank: false
                                        }]
                                },{
                                        xtype: 'checkbox',
                                        columns: 1,
                                        fieldLabel: __('Active'),
                                        name: 'isActive',
                                        ref: 'isActive',
                                        checked: true
                                },{
                                        xtype: 'checkbox',
                                        columns: 1,
                                        fieldLabel: __('Allow External Send'),
                                        name: 'allowExternalSend',
                                        checked: true,
                                        ref: 'allowExternalSend'
                                },{
                                        scope:this,
                                        layout:'column',
                                        fieldLabel: __('Mail Quota'),
                                        border: false,
                                        bodyStyle:'background:transparent;',
                                        items: [{
                                            xtype:'radio',
                                            name: 'radio_maxquota', //nota: o mesmo nome em todos os radio buttons
                                            boxLabel: __('Unlimited'),
                                            inputValue: 'unlimited',
                                            checked: true,
                                            ref: '../mail_quota_unlimited',
                                            columnWidth: .25
                                         },{
                                            xtype:'radio',
                                            name: 'radio_maxquota', //nota: o mesmo nome em todos os radio buttons
                                            boxLabel: __('Limited'),
                                            inputValue: 'limited',
                                            ref: '../mail_quota_limited',
                                            columnWidth: .25,
                                            listeners: {
                                                check : function(checkbox, checked){
                                                    this.ownerCt.find('name','mailbox_quota')[0].setDisabled(!checked);
                                                }
                                            }
                                        },{
                                            xtype: 'textfield',
                                            fieldLabel: '',
                                            name: 'mailbox_quota',
                                            scope:this,
                                            //maskRe: /[0-9]/,
                                            ref: '../mailbox_quota',
                                            columnWidth: .25,
                                            disabled: true,
                                            //vtype: 'quota',
                                            validateValue: function(value){
                                                var cota = this.ownerCt.ownerCt.domain_quota_limit.getValue();
                                                if(parseInt(value) < parseInt(cota) || parseInt(cota) == 0){
                                                    return true;
                                                }else{
                                                    return false;
                                                }
                //                                if(value.length < 1 || value === this.emptyText){ // if it's blank
                //                                     if(this.allowBlank){
                //                                         this.clearInvalid();
                //                                         return true;
                //                                     }else{
                //                                         this.markInvalid(this.blankText);
                //                                         return false;
                //                                     }
                //                                }
                                                 return false;
                                            },
                                        },{
                                            xtype: 'displayfield',
                                            value: ' (Bytes)',
                                            ref: '../server_quota_limit',
                                            columnWidth: .25,
                                            style: 'color:green;padding-top:5px;'
                                        }]
                                    },{
                                        xtype: 'combo',
                                        width: 180,
                                        typeAhead: true,
                                        triggerAction: 'all',
                                        mode: 'local',
                                        store: new Ext.data.ArrayStore({
                                            fields: ['id','name'],
                                            data: [['noforward',<?php echo json_encode(__('local only')) ?>],
                                                ['forwardonly',<?php echo json_encode(__('forward only')) ?>],
                                                ['noprogram',<?php echo json_encode(__('local and forward')) ?>],
                                                ['reply',<?php echo json_encode(__('local with automatic answer')) ?>]]
                                        }),
                                        fieldLabel: __('Delivery Type'),
                                        editable: false,
                                        name: 'delivery_type',
                                        ref: 'delivery_type',
                                        valueField: 'id',
                                        displayField: 'name'
                                },{
                                        xtype: 'textarea',
                                        fieldLabel: __('Automatic Answer'),
                                        name: 'automatic_answer',
                                        width: 250,
                                        ref: 'automatic_answer'
                                }]
                            ,listeners:{
                                beforecollapse:function(panel,anim){
                                    Ext.each(this.items.items,function(ct){ // hide all items
                                        if( ct.xtype )
                                            ct.setDisabled(true);
                                        else Ext.each(ct.items.items, function(sct){ sct.setDisabled(true); });
                                    });
                                    this.txtuser_name.setDisabled(true);
                                },
                                beforeexpand:function(panel,anim){
                                    Ext.each(this.items.items,function(ct){ // hide all items
                                        if( ct.xtype )
                                            ct.setDisabled(false);
                                        else Ext.each(ct.items.items, function(sct){ sct.setDisabled(false); });

                                    });
                                    this.txtuser_name.setDisabled(false);
                                    // fix mailbox_quota disable
                                    if( !this.mail_quota_limited.getValue() )  this.mailbox_quota.setDisabled(true);
                                    // clean this user_service_list
                                    if( this.user_service_list ){
                                        this.user_service_list.setValue( '' );
                                    }
                                },
                                afterrender: function(c){
                                    if( this.find('name','user_name')[0].getValue() ){
                                        this.expand();
                                    } else {
                                        this.collapse();
                                    }
                                }
                            }
                            ,reset: function(data){
                                if( !data ) data = {};
                                this.loadRecord(data);
                                this.find('name','password')[0].setValue('');
                            }
                            ,loadRecord: function(data){
                                var action = data['action'] ? data['action'] : 'edit';
                                Ext.each(this.find('name','etms-action'),function(ct){ // set all items
                                    if( ct.inputValue == action ){
                                        ct.setValue(true);
                                    } else {
                                        ct.setValue(false);
                                    }
                                });
                                var username, domain;
                                if( data['user_name'] ){
                                    var username_fields = data['user_name'].split('@');
                                    username = username_fields[0];
                                    domain = username_fields[1];
                                }
                                Ext.each(this.find('name','user_name'),function(ct){ // set all items
                                    ct.setValue( username );
                                });
                                this.find('name','domain')[0].setValue( domain );

                                if( domain )
                                    this.account_edit.getStore().baseParams.params = Ext.encode({'domain': domain});

                                this.find('name','real_name')[0].setValue( data['real_name'] );
                                this.find('name','delivery_type')[0].setValue( data['delivery_type'] );
                                this.find('name','automatic_answer')[0].setValue( data['automatic_answer'] );

                                if( data['isActive'] == 'active' ){
                                    this.find('name','isActive')[0].setValue( 1 );
                                } else {
                                    this.find('name','isActive')[0].setValue( 0 );
                                }
                                this.find('name','allowExternalSend')[0].setValue( data['allowExternalSend'] );

                                this.find('name','mailbox_quota')[0].setValue( data['mailbox_quota'] );
                                if( data['mailbox_quota'] > 0 ){
                                    this.mail_quota_limited.setValue( true );
                                }
                                this.find('name','password')[0].allowBlank = (action == 'edit')? true : false;

                            }
                            ,load: function(data){
                                var service_id = this.service_id.getValue();
                                var services_list = data['user_service_list'];
                                var extra_str = null;
                                for(var i=0; i<services_list.length && !extra_str; i++){
                                    if( services_list[i]['service_id'] == service_id){
                                        extra_str = services_list[i]['extra'];
                                    }
                                }
                                if( extra_str ){
                                    // add to user_service_list
                                    this.add( { 'xtype':'hidden', 'name': 'user_service_list', 'ref': 'user_service_list' , 'value': service_id } );

                                    var extra = Ext.decode(extra_str);
                                    var conn = new Ext.data.Connection({
                                        listeners:{
                                            // wait message.....
                                            beforerequest:function(){
                                                Ext.MessageBox.show({
                                                    title: <?php echo json_encode(__('Please wait...')) ?>,
                                                    msg: <?php echo json_encode(__('Retrieving data...')) ?>,
                                                    width:300,
                                                    wait:true,
                                                    modal: true
                                                });
                                            },// on request complete hide message
                                            requestcomplete:function(){Ext.MessageBox.hide();}
                                            ,requestexception:function(c,r,o){
                                                                    Ext.MessageBox.hide();
                                                                    Ext.Ajax.fireEvent('requestexception',c,r,o);}
                                        }
                                    });// end conn

                                    conn.request({
                                        url: <?php echo json_encode(url_for('etms/json'))?>,
                                        params: { id:service_id, method:'get_user', params:Ext.encode(extra)},
                                        scope:this,
                                        success: function(resp,opt) {
                                            var response = Ext.decode(resp.responseText);

                                            this.loadRecord(response['value']);
                                        }
                                    });//END Ajax request
                                }
                            }
                            ,onSave: function(data){
                                var user_service_list_value;
                                if( this.user_service_list ){
                                    user_service_list_value = this.user_service_list.getValue();
                                }
                                if( data['user_list-createedit-tab-etms-checkbox'] ){
                                    var pData = new Object();
                                    pData['user_id'] = data['id'];
                                    pData['service_id'] = this.service_id.getValue();

                                    Ext.each(this.find('name','etms-action'),function(ct){ // set all items
                                        if( ct.checked ){
                                            pData['action'] = ct.getRawValue();
                                        }
                                    });

                                    pData['user_name'] = this.find('name','user_name')[0].getValue();
                                    pData['domain'] = this.find('name','domain')[0].getValue();
                                    pData['password'] = this.find('name','password')[0].getValue();
                                    pData['real_name'] = this.find('name','real_name')[0].getValue();
                                    pData['delivery_type'] = this.find('name','delivery_type')[0].getValue();
                                    pData['automatic_answer'] = this.find('name','automatic_answer')[0].getValue();

                                    pData['isActive'] = this.find('name','isActive')[0].getValue();
                                    pData['allowExternalSend'] = this.find('name','allowExternalSend')[0].getValue();

                                    pData['mailbox_quota'] = this.find('name','mailbox_quota')[0].getValue();
                                    ETMS.User.CreateEdit.onSave(pData);
                                } else if( user_service_list_value ){
                                    var extra = { 'user_name': this.find('name','user_name')[0].getValue(), 'domain': this.find('name','domain')[0].getValue() };
                                    call_save_sfGuardUser_UpdateUserService({ 'id': data['id'], 'service_id': user_service_list_value, 'extra': Ext.encode(extra) });
                                }
                            }
                        }]};
    Ext.apply(Obj,config);
    return Obj;
};
</script>
