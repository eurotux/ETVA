<script>

Ext.ns('ETFS.EditGlobal');

ETFS.EditGlobal.Form = new Ext.extend( Ext.form.FormPanel, {

    monitorValid:true,
    border: false,
    labelWidth: 140,
    defaults: { border:false },
    initComponent:function(){
        var service_id = this.service_id;

        this.items = [
                        {xtype:'hidden',name:'id'},
                        {
                            border: false,
                            anchor: '100% 100%',
                            layout: {
                                type: 'vbox',
                                align: 'stretch'  // Child items are stretched to full width
                            }
                            ,defaults: { flex: 1, layout:'form', autoScroll:true, bodyStyle:'padding:10px;', border:false}
                            ,items:[
                                {
                                    id: 'etfs-edit-global-config-fieldset',
                                    items: [
                                            { fieldLabel:__('Workgroup'),
                                              name: 'workgroup',
                                              allowBlank: false,
                                              xtype:'textfield' },
                                            { fieldLabel:__('Server description'),
                                              name: 'server_string',
                                              allowBlank: true,
                                              xtype:'textfield' },
                                            { //boxLabel:__('Standalone server'),
                                                fieldLabel:__('Standalone server'),
                                                xtype: 'radio',
                                                name: 'security',
                                                inputValue: 'user',
                                                listeners: {
                                                    'check': function(f,checked){
                                                        if( checked ){
                                                            this.form.findField('realm').setValue("");
                                                            this.form.findField('netbios_name').setValue("");
                                                            this.form.findField('password_server').setValue("");
                                                        }
                                                    },scope: this
                                                }
                                             },
                                            { fieldLabel:__('AD domain authentication'),
                                                border: false,
                                                layout: 'column',
                                                items: [
                                                    { //boxLabel:__('AD domain authentication'),
                                                        xtype: 'radio',
                                                        name: 'security',
                                                        inputValue: 'ads',
                                                        listeners: {
                                                            'check': function(f,checked){
                                                                f.ownerCt.etfs_join_to_ad_btn.setDisabled(!checked);
                                                            }
                                                        }
                                                    },
                                                    { xtype: 'button',
                                                        disabled: true,
                                                        iconCls:'icon-user-add',
                                                        ref: 'etfs_join_to_ad_btn',
                                                        text: '',
                                                        url: <?php echo(json_encode(url_for('etfs/ETFS_JoinToAD')))?>,
                                                        call:'ETFS.JoinToAD',
                                                        scope:this,
                                                        callback: function(item) {

                                                            var parentCmp = Ext.getCmp((item.scope).id);
                                                            var form_values_data = parentCmp.getForm().getValues();
                                                            form_values_data['service_id'] = service_id;

                                                            var window = new ETFS.JoinToAD.Window({
                                                                                title: String.format(<?php echo json_encode(__('AD domain autentication')) ?>),
                                                                                service_id:service_id });
                                                            window.on({
                                                                show:{fn:function(){window.loadData(form_values_data);}},
                                                                onSave:{fn:function(){
                                                                        this.close();
                                                                        var parentCmp = Ext.getCmp((item.scope).id);
                                                                        parentCmp.fireEvent('refresh',parentCmp);
                                                                }}
                                                            });
                                                            window.show();
                                                        },
                                                        handler: function(btn){View.loadComponent(btn);}
                                                    }
                                                ]
                                            },
                                            { xtype: 'hidden', name: 'netbios_name' },
                                            { xtype: 'hidden', name: 'password_server' },
                                            { xtype: 'hidden', name: 'realm' }
                                    ]
                                }
                            ]
                        }
                ];

            this.buttons = [{
                               text: __('Save'),
                               formBind:true,
                               handler: this.onSave,
                               scope: this
                           },
                           {
                               text:__('Cancel'),
                           scope:this,
                           handler:function(){(this.ownerCt).close()}
                       }];
        ETFS.EditGlobal.Form.superclass.initComponent.call(this);

        this.on({
            refresh:{ scope:this, fn:function(){                                    
                        this.loadRecord();
                    }
            }
        });
    }
    ,onSave: function(){
        var form_values = this.getForm().getValues();
        var send_data = {};
        send_data['name'] = 'global';
        send_data['workgroup'] = form_values['workgroup'];
        send_data['server_string'] = form_values['server_string'];
        send_data['security'] = form_values['security'];
        send_data['netbios_name'] = form_values['netbios_name'];
        send_data['password_server'] = form_values['password_server'];
        send_data['realm'] = form_values['realm'];

        // process update share
        var conn = new Ext.data.Connection({
            listeners:{
                // wait message.....
                beforerequest:function(){
                    Ext.MessageBox.show({
                        title: <?php echo json_encode(__('Please wait...')) ?>,
                        msg: <?php echo json_encode(__('Saving global configuration...')) ?>,
                        width:300,
                        wait: true,
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
            url: <?php echo json_encode(url_for('etfs/json')) ?>,
            params: {
                id: this.service_id,
                method: 'set_global_configuration',
                params: Ext.encode(send_data)
            },            
            scope:this,
            success: function(resp,opt) {

                var response = Ext.util.JSON.decode(resp.responseText);                

                Ext.ux.Logger.info(response['agent'],response['response']);

                (this.ownerCt).fireEvent('onSave');
            },
            failure: function(resp,opt) {
                
                var response = Ext.util.JSON.decode(resp.responseText);
                Ext.ux.Logger.error(response['agent'],response['error']);

                Ext.Msg.show({
                    title: String.format(<?php echo json_encode(__('Error {0}')) ?>,response['agent']),
                    buttons: Ext.MessageBox.OK,
                    msg: String.format(<?php echo json_encode(__('Error saving global configuration!')) ?>)+'<br>'+response['info'],
                    icon: Ext.MessageBox.ERROR});

            }
        });// END Ajax request
    }
    ,loadRecord: function(data){
        this.form.findField('id').setValue(this.service_id);
        this.load({url:<?php echo json_encode(url_for('etfs/json'))?>
                        ,params:{id:this.service_id,method:'get_global_configuration'}
                        ,waitMsg: <?php echo json_encode(__('Loading...')) ?>
                        ,success:function(f,a){
                            var data = a.result['data']
                            this.form.loadRecord(new Ext.data.Record(data));
                        }
                        ,scope: this
                    });
    }
});

ETFS.EditGlobal.Window = function(config) {

    Ext.apply(this,config);

    ETFS.EditGlobal.Window.superclass.constructor.call(this, {
        width:360
        ,height:270
        ,border:false
        ,modal:true
        ,layout:'fit'
        ,items:[ new ETFS.EditGlobal.Form({service_id:this.service_id})]
    });
};


Ext.extend(ETFS.EditGlobal.Window, Ext.Window,{
    loadData:function(data){
        this.items.get(0).loadRecord(data);
    }
});

</script>

