<script>

Ext.ns('ETFS.EditUser');

ETFS.EditUser.Form = new Ext.extend( Ext.form.FormPanel, {

    monitorValid:true,
    border: false,
    labelWidth: 90,
    defaults: { border:false },
    initComponent:function(){
        var service_id = this.service_id;
        var user = this.user;

        var edituser_fieldset = {
                                    id: 'etfs-edit-file-user-fieldset',
                                    items: [
                                            /*{ fieldLabel:__('User name'),
                                              name: 'name',
                                              allowBlank: false,
                                              xtype:'textfield' },*/
                                            { fieldLabel:__('Unix UID'),
                                              name: 'uid',
                                              //allowBlank: false,
                                              xtype:'textfield' },
                                            { fieldLabel:__('Password'),
                                                ref: 'passwordfieldset',
                                                border: false,
                                                layout: 'column',
                                                items: []
                                            },
                                            { fieldLabel:__('User options'),
                                                id: 'cbg-user-options', 
                                                xtype: 'checkboxgroup',
                                                border: false,
                                                //xtype: 'fieldset',
                                                defaultType: 'checkbox', // each item will be a checkbox
                                                columns: 1,
                                                items: [
                                                    { xtype: 'hidden', name: 'opts', inputValue: 'U' },
                                                    /*{ boxLabel:__('Normal user'),
                                                        fieldLabel: '',
                                                        labelSeparator: '',
                                                        name: 'opts',
                                                        checked: true,
                                                        inputValue: 'U' },*/
                                                    { boxLabel:__('No password required'),
                                                        fieldLabel: '',
                                                        labelSeparator: '',
                                                        name: 'opts',
                                                        inputValue: 'N' },
                                                    { boxLabel:__('Account disabled'),
                                                        fieldLabel: '',
                                                        labelSeparator: '',
                                                        name: 'opts',
                                                        inputValue: 'D' },
                                                    { boxLabel:__('Account is locked'),
                                                        fieldLabel: '',
                                                        labelSeparator: '',
                                                        name: 'opts',
                                                        inputValue: 'L' },
                                                    { boxLabel:__('Password never expires'),
                                                        fieldLabel: '',
                                                        labelSeparator: '',
                                                        name: 'opts',
                                                        inputValue: 'X' }
                                                    /*,{ boxLabel:__('Workstation trust account'),
                                                        fieldLabel: '',
                                                        labelSeparator: '',
                                                        name: 'opts',
                                                        inputValue: 'W' }*/
                                                ]
                                            }
                                    ]
        };

        this.items = [
                        {xtype:'hidden',name:'id'},
                        {xtype:'hidden',name:'operation'},
                        {
                            border: false,
                            anchor: '100% 100%',
                            layout: {
                                type: 'vbox',
                                align: 'stretch'  // Child items are stretched to full width
                            }
                            ,defaults: { flex: 1, layout:'form', autoScroll:true, bodyStyle:'padding:10px;', border:false}
                            ,items:[ edituser_fieldset ]
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
        ETFS.EditUser.Form.superclass.initComponent.call(this);
    }
    ,onSave: function(){
        var form_values = this.getForm().getValues();
        var name = this.getForm().findField("name").getValue();
        var operation = this.getForm().findField("operation").getValue();

        if( !name ){
            Ext.Msg.show({
                title: String.format(<?php echo json_encode(__('Error invalid username')) ?>),
                buttons: Ext.MessageBox.OK,
                msg: String.format(<?php echo json_encode(__('Invalid username!')) ?>),
                icon: Ext.MessageBox.ERROR});
        } else {
            var send_data = {};
            send_data['name'] = name;
            send_data['sync_to_samba'] = true;
            if( operation == 'new' ){
                send_data['password'] = form_values['password'];
            } else {
                if( form_values['passwd_opt']=='new' ){
                    send_data['change_password'] = true;
                    send_data['newpassword'] = form_values['newpassword'];
                }
            }
            send_data['uid'] = form_values['uid'];
            send_data['opts'] = form_values['opts'];
            
 
            // process update user
            var conn = new Ext.data.Connection({
                listeners:{
                    // wait message.....
                    beforerequest:function(){
                        Ext.MessageBox.show({
                            title: <?php echo json_encode(__('Please wait...')) ?>,
                            msg: <?php echo json_encode(__('Saving user info...')) ?>,
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

            var method = 'update_user';
            if( operation == 'new' ){
                method = 'create_user';
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
                    (this.ownerCt).fireEvent('onSave');
                },
                failure: function(resp,opt) {
                    
                    var response = Ext.util.JSON.decode(resp.responseText);
                    Ext.ux.Logger.error(response['agent'],response['error']);

                    Ext.Msg.show({
                        title: String.format(<?php echo json_encode(__('Error {0}')) ?>,response['agent']),
                        buttons: Ext.MessageBox.OK,
                        msg: String.format(<?php echo json_encode(__('Error saving user!')) ?>)+'<br>'+response['info'],
                        icon: Ext.MessageBox.ERROR});

                }
            });// END Ajax request
        }
    }
    ,loadRecord: function(data){
        this.form.findField('id').setValue(this.service_id);
        if( data['new'] ){
            this.form.findField('operation').setValue("new");
            Ext.getCmp('etfs-edit-file-user-fieldset').insert(0, { fieldLabel:__('User name'), name: 'name', allowBlank: false, xtype:'textfield' });
            console.log(Ext.getCmp('etfs-edit-file-user-fieldset'));
            Ext.getCmp('etfs-edit-file-user-fieldset').passwordfieldset.add({ name: 'password',
                                                                        xtype: 'textfield', inputType: 'password', allowBlank: true });
            Ext.getCmp('etfs-edit-file-user-fieldset').passwordfieldset.doLayout();
        } else {
            this.form.findField('operation').setValue("update");
            Ext.getCmp('etfs-edit-file-user-fieldset').insert(0, { fieldLabel:__('User name'), name: 'name', xtype:'displayfield' });
            Ext.getCmp('etfs-edit-file-user-fieldset').passwordfieldset.add([
                                                    { boxLabel:__('Current password'),
                                                        xtype: 'radio',
                                                        name: 'passwd_opt',
                                                        checked: true,
                                                        inputValue: 'curr' },
                                                    { boxLabel:__('New password'),
                                                        xtype: 'radio',
                                                        name: 'passwd_opt',
                                                        inputValue: 'new' },
                                                    { name: 'newpassword',
                                                        xtype: 'textfield', inputType: 'password' }
                                                ]);
            Ext.getCmp('etfs-edit-file-user-fieldset').passwordfieldset.doLayout();
            if( data['user'] ){
                var user = data['user'];
                this.form.loadRecord(new Ext.data.Record(user));
                var opts = user['opts'];
                if( typeof opts == "object" ){
                    this.form.findField("cbg-user-options").setValue(opts.join(','));
                }
            }
        }
    }
});

ETFS.EditUser.Window = function(config) {

    console.log('ETFS.EditUser.Window');
    console.log(config);
    Ext.apply(this,config);

    ETFS.EditUser.Window.superclass.constructor.call(this, {
        width:460
        ,height:360
        ,border:false
        ,modal:true
        ,layout:'fit'
        ,items:[ new ETFS.EditUser.Form({service_id:this.service_id, user: this.user})]
    });
};


Ext.extend(ETFS.EditUser.Window, Ext.Window,{
    loadData:function(data){
        this.items.get(0).loadRecord(data);
    }
});

</script>

