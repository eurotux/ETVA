<script>
Ext.ns("ETVOIP.User.CreateEdit.Fieldset");

ETVOIP.User.CreateEdit.onSave = function(alldata){
    //console.log(alldata);
    var send_data = alldata;
    send_data['extension'] = alldata['extension'];

    var url = <?php echo json_encode(url_for('etvoip/json'))?>;
    var service_id = alldata['service_id'];

    var method = 'add_extension';
    var wait_msg = <?php echo json_encode(__('Adding extension...'))?>;
    var ok_msg = <?php echo json_encode(__('Added extension {0}'))?>;

    if(alldata['action'] == 'edit'){
        method = 'edit_extension';
        wait_msg = <?php echo json_encode(__('Updating extension...'))?>;
        ok_msg = <?php echo json_encode(__('Updated extension {0}'))?>;
    }

    var conn = new Ext.data.Connection({
        listeners:{
            // wait message.....
            beforerequest:function(){
                Ext.MessageBox.show({
                    title: <?php echo json_encode(__('Please wait...')) ?>,
                    msg: wait_msg,
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
        url: url,
        params:{id:service_id,method:method,params:Ext.encode(send_data)},                
        // everything ok...
        success: function(resp,opt){
            var response = Ext.util.JSON.decode(resp.responseText);                    
            var msg = String.format(ok_msg,alldata['extension']);
            Ext.ux.Logger.info(response['agent'],msg);
            ETVOIP.User.CreateEdit.onSave_ETVOIPReload(send_data);

            var extra = { 'tech': alldata['tech'], 'extension': alldata['extension'] };
            call_save_sfGuardUser_UpdateUserService({ id: alldata['user_id'], service_id: service_id, extra: Ext.encode(extra) });
        }
    });// END Ajax request
};

ETVOIP.User.CreateEdit.onSave_ETVOIPReload = function(ext){
    var url = <?php echo json_encode(url_for('etvoip/json'))?>;
    var service_id = ext['service_id'];
    var method = 'do_reload';

    var wait_msg = <?php echo json_encode(__('Reloading Asterisk configuration...'))?>;
    var ok_msg = <?php echo json_encode(__('Reloaded Asterisk configuration')) ?>;

    var conn = new Ext.data.Connection({
        listeners:{
            // wait message.....
            beforerequest:function(){
                Ext.MessageBox.show({
                    title: <?php echo json_encode(__('Please wait...')) ?>,
                    msg: wait_msg,
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
        url: url,
        params:{id:service_id,method:method}, 
        // everything ok...
        success: function(resp,opt){
            var response = Ext.util.JSON.decode(resp.responseText);                    
            Ext.ux.Logger.info(response['agent'],ok_msg);

        },scope:this
    });// END Ajax request
};

ETVOIP.User.CreateEdit.Fieldset = function(config){
    var Obj = {
                border: false,
                bodyStyle: 'padding: 10px; background:transparent;',
                defaults: { border: false },
                items: [ { xtype: 'fieldset',
                            checkboxToggle: true,
                            collapsed: true,
                            id:'user_list-createedit-tab-etvoip',
                            title: <?php echo json_encode(__('Enable ETVOIP user information?')) ?>,
                            items: [{
                                        xtype: 'hidden',
                                        name: 'service_id',
                                        ref: 'service_id',
                                        value: parseInt('<?php echo $modulesConf['ETVOIP']['pbx']['service_id'] ?>')
                                    },{ xtype:'radio',
                                        name: 'etvoip-action',
                                        inputValue: 'add',
                                        fieldLabel:__('New extension'),
                                        checked: true,
                                        listeners: {
                                            'check': function(e,check){
                                                e.ownerCt.extension_new.setDisabled(!check);
                                                e.ownerCt.extension_new.setVisible(check);
                                                if( check )
                                                    e.ownerCt.reset({ 'action': 'add' });
                                            }
                                        }
                                    },{ xtype:'radio',
                                        name: 'etvoip-action',
                                        inputValue: 'edit',
                                        checked: false,
                                        fieldLabel:__('Edit extension'),
                                        listeners: {
                                            'check': function(e,check){
                                                e.ownerCt.extension_edit.setDisabled(!check);
                                                e.ownerCt.extension_edit.setVisible(check);
                                                if( check )
                                                    e.ownerCt.reset({ 'action': 'edit' });
                                            }
                                        }
                                    },
                                new Ext.form.ComboBox({
                                    selectOnFocus:true,
                                    editable: false, 
                                    mode: 'local',
                                    value:'sip',
                                    triggerAction: 'all',
                                    name:'tech',
                                    hiddenName:'tech',
                                    fieldLabel: __('Type'),
                                    ref: 'tech',
                                    xtype:'combo', 	    
                                    width: 180,
                                    allowBlank: false,
                                    store: new Ext.data.ArrayStore({
                                        fields: ['value','name'],
                                        data: [['sip', __('SIP')], ['iax2', __('IAX2')]]
                                    }),
                                    valueField: 'value',
                                    displayField: 'name',
                                    listeners: {
                                        select : function(cb,r,ix){
                                            if( r.data.value == 'sip' ){
                                                this.ownerCt.find('name','devinfo_secret')[0].setDisabled(false);
                                                this.ownerCt.find('name','devinfo_dtmfmode')[0].setDisabled(false);
                                            } else {
                                                this.ownerCt.find('name','devinfo_secret')[0].setDisabled(true);
                                                this.ownerCt.find('name','devinfo_dtmfmode')[0].setDisabled(true);
                                            }
                                        }
                                    }
                                }),
                                {
                                    fieldLabel : __('User Extension'),
                                    border:false,
                                    bodyStyle:'background:transparent;',
                                    items:[ 
                                    new Ext.form.ComboBox({ fieldLabel:__('User Extension'),
                                        hidden: true,
                                        ref: '../extension_edit',
                                        selectOnFocus: false,
                                        editable: false,        
                                        //mode: 'local',
                                        triggerAction: 'all',
                                        allowBlank: false,
                                        store: new Ext.data.JsonStore({
                                            proxy:new Ext.data.HttpProxy({url:<?php echo json_encode(url_for('etvoip/json'))?>}),
                                            totalProperty: 'total',
                                            baseParams:{ id:parseInt('<?php echo $modulesConf['ETVOIP']['pbx']['service_id'] ?>'),method:'get_extensions'},
                                            root: 'data',
                                            fields: [            
                                               {name: 'extension'},
                                               {name: 'tech'},
                                               {name: 'name'},
                                               {name: 'devinfo_dtmfmode'}
                                            ],
                                            autoLoad: false
                                        }),
                                        valueField: 'extension',
                                        displayField: 'extension',
                                        name: 'extension',
                                        xtype:'combo', 	    
                                        width: 180,
                                        listeners: {
                                            'select': function(cbx, r, i){
                                                cbx.ownerCt.ownerCt.loadRecord(r.data);
                                            }
                                    } }),
                                    {
                                        xtype:'numberfield',
                                        ref : '../extension_new',
                                        name       : 'extension',
                                        fieldLabel : __('User Extension'),
                                        validateOnBlur:false,
                                        validator:function(v){

                                            if(v==this.originalValue){
                                              return true;
                                            }

                                        },
                                        hint: <?php echo json_encode(__('The extension number to dial to reach this user')) ?>,
                                        allowBlank : false
                                    }
                                ]},
                                {
                                    xtype:'textfield',
                                    name       : 'name',
                                    fieldLabel : __('Display Name'),
                                    hint: <?php echo json_encode(__('The caller id name for calls from this user will be set to this name. Only enter the name, NOT the number')) ?>,
                                    allowBlank : false
                                }
                                ,{
                                    xtype:'textfield',
                                    inputType: 'password',
                                    name       : 'devinfo_secret',
                                    fieldLabel : __('Secret'),
                                    allowBlank : false
                                }
                                ,{
                                    xtype:'textfield',
                                    name       : 'devinfo_dtmfmode',
                                    fieldLabel : __('Dtmfmode'),
                                    originalValue: 'rfc2833',
                                    value: 'rfc2833'
                                }
                            ]
                            ,listeners:{
                                beforecollapse:function(panel,anim){
                                    Ext.each(this.items.items,function(ct){ // hide all items
                                        if( ct.xtype )
                                            ct.setDisabled(true);
                                        else Ext.each(ct.items.items, function(sct){ sct.setDisabled(true); });
                                    });
                                },
                                beforeexpand:function(panel,anim){
                                    var action = 'add';
                                    Ext.each(this.items.items,function(ct){ // hide all items
                                        if( ct.xtype ){
                                            ct.setDisabled(false);
                                            if( (ct.name=='etvoip-action') && (ct.inputValue=='edit') && ct.checked ){
                                                action = 'edit';
                                            }
                                            if( (ct.name=='tech') && (action=='edit') ){
                                                //ct.setDisabled(true);
                                            }
                                        } else Ext.each(ct.items.items, function(sct){ sct.setDisabled(false); });
                                    });
                                    // clean this user_service_list
                                    if( this.user_service_list ){
                                        this.user_service_list.setValue( '' );
                                    }
                                },
                                afterrender: function(c){
                                    if( this.find('name','extension')[0].getValue() ){
                                        this.expand();
                                    } else {
                                        this.collapse();
                                    }
                                }
                            }
                            ,reset: function(data){
                                if( !data ) data = {};
                                this.loadRecord(data);
                                this.find('name','devinfo_secret')[0].setValue('');
                            }
                            ,loadRecord: function(data){
                                //console.log(data);

                                var action = data['action'] ? data['action'] : 'edit';
                                Ext.each(this.find('name','etvoip-action'),function(ct){ // set all items
                                    if( ct.inputValue == action ){
                                        ct.setValue(true);
                                    } else {
                                        ct.setValue(false);
                                    }
                                });

                                var tech = data['tech'];
                                if( !tech ) tech = 'sip';
                                this.find('name','tech')[0].setValue( tech );
                                //this.find('name','tech')[0].setDisabled( (action=='edit') ? true : false );
                                this.find('name','tech')[0].setReadOnly( (action=='edit') ? true : false );

                                Ext.each(this.find('name','extension'),function(ct){ // set all items
                                    ct.setValue( data['extension'] );
                                    //ct.setDisabled( true );
                                });

                                this.find('name','name')[0].setValue( data['name'] );

                                var dtmfmode = data['devinfo_dtmfmode'];
                                if( !dtmfmode ) dtmfmode = this.find('name','devinfo_dtmfmode')[0].originalValue;
                                this.find('name','devinfo_dtmfmode')[0].setValue( dtmfmode );
                                this.find('name','devinfo_secret')[0].allowBlank = (action == 'edit')? true : false;

                                if( tech == 'sip' ){
                                    this.find('name','devinfo_secret')[0].setDisabled(false);
                                    this.find('name','devinfo_dtmfmode')[0].setDisabled(false);
                                } else {
                                    this.find('name','devinfo_secret')[0].setDisabled(true);
                                    this.find('name','devinfo_dtmfmode')[0].setDisabled(true);
                                }
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
                                    if( !extra['tech'] ) extra['tech'] = 'sip';

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
                                        url: <?php echo json_encode(url_for('etvoip/json'))?>,
                                        params: { id:service_id, method:'get_extension', params:Ext.encode(extra)},
                                        scope:this,
                                        success: function(resp,opt) {
                                            var response = Ext.decode(resp.responseText);

                                            this.loadRecord(response['data']);
                                        }
                                    });//END Ajax request
                                }
                            }
                            ,onSave: function(data){
                                var user_service_list_value;
                                if( this.user_service_list ){
                                    user_service_list_value = this.user_service_list.getValue();
                                }
                                if( data['user_list-createedit-tab-etvoip-checkbox'] ){
                                    var pData = new Object();
                                    pData['user_id'] = data['id'];
                                    pData['service_id'] = this.service_id.getValue();

                                    Ext.each(this.find('name','etvoip-action'),function(ct){ // set all items
                                        if( ct.checked ){
                                            pData['action'] = ct.getRawValue();
                                        }
                                    });

                                    pData['tech'] = this.find('name','tech')[0].getValue();
                                    pData['extension'] = this.find('name','extension')[0].getValue();
                                    pData['name'] = this.find('name','name')[0].getValue();
                                    pData['devinfo_dtmfmode'] = this.find('name','devinfo_dtmfmode')[0].getValue();
                                    pData['devinfo_secret'] = this.find('name','devinfo_secret')[0].getValue();
                                    ETVOIP.User.CreateEdit.onSave(pData);
                                } else if( user_service_list_value ){
                                    var extra = { 'tech': this.find('name','tech')[0].getValue(), 'extension': this.find('name','extension')[0].getValue() };
                                    call_save_sfGuardUser_UpdateUserService({ 'id': data['id'], 'service_id': user_service_list_value, 'extra': Ext.encode(extra) });
                                }
                            }
                        }]};
    Ext.apply(Obj,config);
    return Obj;
};
</script>

