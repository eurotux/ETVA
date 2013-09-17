<script>
Ext.ns('Primavera.Windows.NewUser');

Primavera.Windows.NewUser.Form = new Ext.extend( Ext.form.FormPanel, {

    monitorValid:true,
    border: false,
    labelWidth: 140,
    defaults: { border:false },
    initComponent:function(){
        this.items = [
                {xtype:'hidden',name:'id'},
                {
                    anchor: '100% 100%',
                    layout: {
                        type: 'hbox',
                        align: 'stretch'  // Child items are stretched to full width
                    }
                    ,defaults: { flex: 1, layout:'form', autoScroll:true, bodyStyle:'padding:10px;', border:false}
                    ,items:[
                            {
                                items: [
                                    { fieldLabel:__('User name'),
                                      name: 'username',
                                      allowBlank: false,
                                      allowBlank: false,
                                      xtype:'textfield' },
                                    { fieldLabel:__('Password'),
                                      name: 'password',
                                      ref: 'password',
                                      inputType: 'password',
                                      allowBlank: false,
                                      xtype:'textfield' },
                                    { fieldLabel:__('Verify Password'),
                                      name: 'verpassword',
                                      allowBlank: false,
                                      inputType: 'password',
                                        validator:function(v){
                                            if(!v) return true;
                                            
                                            if(v==this.ownerCt.password.getValue()) return true;
                                            else return <?php echo json_encode(__('Passwords doesn\'t match')) ?>;
                                        },
                                      xtype:'textfield' },
                                      new Ext.form.ComboBox({
                                          selectOnFocus:true,
                                          editable: false,        
                                          mode: 'local',
                                          value: 'Users',
                                          triggerAction: 'all',
                                          name:'groups',
                                          hiddenName:'groups',
                                          fieldLabel: __('Type'),
                                          xtype:'combo', 	    
                                          allowBlank: false,
                                          store: new Ext.data.ArrayStore({
                                              fields: ['value','name'],
                                              data: [['Administrators', __('Administrator')], ['Users', __('Standard user')]]
                                          }),
                                          valueField: 'value',
                                          displayField: 'name'
                                      })
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
                           scope: this,
                           handler:function(){(this.ownerCt).close()}
                       }];

        Primavera.Windows.NewUser.Form.superclass.initComponent.call(this);
    }
    ,onSave: function(){
        var form_values = this.getForm().getValues();

        var send_data = {};
        var method = 'windows_createuser';

        send_data['username'] = form_values['username'];
        send_data['password'] = form_values['password'];
        send_data['groups'] = form_values['groups'];

        // process
        var conn = new Ext.data.Connection({
            listeners:{
                // wait message.....
                beforerequest:function(){
                    Ext.MessageBox.show({
                        title: <?php echo json_encode(__('Please wait...')) ?>,
                        msg: <?php echo json_encode(__('Creating Windows user...')) ?>,
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
            url: <?php echo json_encode(url_for('primavera/json')) ?>,
            params: {
                id: this.service_id,
                method: method,
                params: Ext.encode(send_data)                
            },            
            scope:this,
            success: function(resp,opt) {

                var response = Ext.util.JSON.decode(resp.responseText);                

                Ext.ux.Logger.info(response['agent'],response['response']);
                this.ownerCt.fireEvent('onSave');                

            },
            failure: function(resp,opt) {
                
                var response = Ext.util.JSON.decode(resp.responseText);
                Ext.ux.Logger.error(response['agent'],response['error']);

                Ext.Msg.show({
                    title: String.format(<?php echo json_encode(__('Error {0}')) ?>,response['agent']),
                    buttons: Ext.MessageBox.OK,
                    msg: String.format(<?php echo json_encode(__('Unable to create Windows User!')) ?>)+'<br>'+response['info'],
                    icon: Ext.MessageBox.ERROR});

            }
        });// END Ajax request
    }
});

Primavera.Windows.NewUser.Window = function(config) {

    Ext.apply(this,config);

    Primavera.Windows.NewUser.Window.superclass.constructor.call(this, {
        width:360
        ,height:240
        ,border:false
        ,modal:true
        ,layout:'fit'
        ,items:[ new Primavera.Windows.NewUser.Form({service_id:this.service_id})]
    });
};

Ext.extend(Primavera.Windows.NewUser.Window, Ext.Window,{});

</script>
