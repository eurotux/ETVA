<script>

Ext.ns('ETFS.JoinToAD');

ETFS.JoinToAD.Form = new Ext.extend( Ext.form.FormPanel, {

    monitorValid:true,
    border: false,
    labelWidth: 140,
    defaults: { border:false },
    initComponent:function(){
        var service_id = this.service_id;
        var share = this.share;

        var store_list_users = new Ext.data.JsonStore({
            proxy:new Ext.data.HttpProxy({url:<?php echo json_encode(url_for('etfs/json'))?>}),
            baseParams:{ id:service_id,method:'list_users'},
            root: 'data',
            fields: [            
               {name: 'uid'},
               {name: 'name'}
            ],
            autoLoad: true
        });
        var store_list_groups = new Ext.data.JsonStore({
            proxy:new Ext.data.HttpProxy({url:<?php echo json_encode(url_for('etfs/json'))?>}),
            baseParams:{ id:service_id,method:'list_groups'},
            root: 'data',
            fields: [            
               {name: 'gid'},
               {name: 'name'}
            ],
            autoLoad: true
        });

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
                                    id: 'etfs-join-to-ad-config-fieldset',
                                    items: [
                                            { fieldLabel:__('Domain NetBIOS Name'),
                                              name: 'netbios_name',
                                              xtype:'textfield', allowBlank: false },
                                            { fieldLabel:__('AD Server Name'),
                                              name: 'password_server',
                                              xtype:'textfield', allowBlank: false },
                                            { fieldLabel:__('Domain'),
                                              name: 'realm',
                                              xtype:'textfield', allowBlank: false },
                                            { fieldLabel:__('Domain Administrator Username'),
                                              name: 'domainadmin',
                                              xtype:'textfield', allowBlank: false },
                                            { fieldLabel:__('Domain Administrator Password'),
                                              name: 'domainpasswd',
                                              inputType: 'password',
                                              xtype:'textfield', allowBlank: false },
                                            { xtype: 'hidden', name: 'workgroup' },
                                            { xtype: 'hidden', name: 'server_string' },
                                    ]
        }
                            ]
                        }
                ];

            this.buttons = [{
                               text: __('Test'),
                               formBind:true,
                               action: 'test',
                               handler: this.onJoin,
                               scope: this
                           },{
                               text: __('Join'),
                               formBind:true,
                               action: 'join',
                               handler: this.onJoin,
                               scope: this
                           },{
                               text: __('Save'),
                               formBind:true,
                               action: 'save',
                               handler: this.onJoin,
                               scope: this
                           },
                           {
                               text:__('Cancel'),
                               scope:this,
                               handler:function(){(this.ownerCt).close()}
                       }];
        ETFS.JoinToAD.Form.superclass.initComponent.call(this);
    }
    ,onJoin: function(b,e){
        var form_values = this.getForm().getValues();
        var send_data = {};
        send_data['test'] = (b.action=='test') ? true : false;
        send_data['name'] = 'global';
        send_data['configtype'] = 'ads';
        send_data['netbios_name'] = form_values['netbios_name'];
        send_data['password_server'] = form_values['password_server'];
        send_data['realm'] = form_values['realm'];
        send_data['domainadmin'] = form_values['domainadmin'];
        send_data['domainpasswd'] = form_values['domainpasswd'];

        if( form_values['workgroup'] ) send_data['workgroup'] = form_values['workgroup'];
        if( form_values['server_string'] ) send_data['server_string'] = form_values['server_string'];

        // process update share
        var conn = new Ext.data.Connection({
            listeners:{
                // wait message.....
                beforerequest:function(){
                    Ext.MessageBox.show({
                        title: <?php echo json_encode(__('Please wait...')) ?>,
                        msg: <?php echo json_encode(__('Saving configuration...')) ?>,
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

        var method = 'join_to_domain';
        if( b.action == 'save' ){
            method = 'set_global_configuration';
        }
        conn.request({
            url: <?php echo json_encode(url_for('etfs/json')) ?>,
            params: {
                id: this.service_id,
                method: method,
                params: Ext.encode(send_data)
            },            
            scope:this,
            success: function(resp,opt) {

                var response = Ext.util.JSON.decode(resp.responseText);                

                Ext.ux.Logger.info(response['agent'],response['response']);

                if( b.action == 'test' ){
                    Ext.MessageBox.show({
                        title: <?php echo json_encode(__('Test join to AD OK!')) ?>,
                        msg: String.format(<?php echo json_encode(__('{0}')) ?>,response['response']),
                        buttons: Ext.MessageBox.OK,
                        icon: Ext.MessageBox.INFO
                    });
                } else {
                    (this.ownerCt).fireEvent('onSave');
                }
            },
            failure: function(resp,opt) {
                
                var response = Ext.util.JSON.decode(resp.responseText);
                Ext.ux.Logger.error(response['agent'],response['error']);

                Ext.Msg.show({
                    title: String.format(<?php echo json_encode(__('Error {0}')) ?>,response['agent']),
                    buttons: Ext.MessageBox.OK,
                    msg: String.format(<?php echo json_encode(__('Error saving configuration!')) ?>)+'<br>'+response['info'],
                    icon: Ext.MessageBox.ERROR});

            }
        });// END Ajax request
    }
    ,loadRecord: function(data){
        this.form.findField('id').setValue(this.service_id);
        this.form.loadRecord(new Ext.data.Record(data));
    }
});

ETFS.JoinToAD.Window = function(config) {

    Ext.apply(this,config);

    ETFS.JoinToAD.Window.superclass.constructor.call(this, {
        width:360
        ,height:270
        ,border:false
        ,modal:true
        ,layout:'fit'
        ,items:[ new ETFS.JoinToAD.Form({service_id:this.service_id, share: this.share})]
    });
};


Ext.extend(ETFS.JoinToAD.Window, Ext.Window,{
    loadData:function(data){
        this.items.get(0).loadRecord(data);
    }
});

</script>

