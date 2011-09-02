<script>

Ext.ns('Primavera.EditUser');

Primavera.EditUser.Form = new Ext.extend( Ext.form.FormPanel, {

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
                                    {xtype:'hidden',name:'operation'},
                                    { fieldLabel:__('Name'),
                                      name: 'Nome',
                                      xtype:'textfield' },
                                    { fieldLabel:__('Email'),
                                      name: 'Email',
                                      xtype:'textfield' },
                                    { fieldLabel:__('Password'),
                                      name: 'password',
                                      inputType: 'password',
                                      xtype:'textfield' },
                                    { fieldLabel:__('Verify Password'),
                                      name: 'verpassword',
                                      inputType: 'password',
                                      xtype:'textfield' },
                                    { fieldLabel:__('Super Admin'),
                                      name: 'SuperAdministrador',
                                      xtype:'checkbox', listeners: { 
                                                                'check':{scope:this,fn:function(cbox,ck){
                                                                                                        if(ck){
                                                                                                            this.form.findField('Administrador').setValue(true);
                                                                                                            this.form.findField('Administrador').disable();
                                                                                                        } else {
                                                                                                            this.form.findField('Administrador').enable();
                                                                                                        }
                                                                                                }}}},
                                    { fieldLabel:__('Admin'),
                                      name: 'Administrador',
                                      xtype:'checkbox' },
                                    { fieldLabel:__('Tecnico'),
                                      name: 'Tecnico',
                                      xtype:'checkbox' },
                            ]
                        }
                    ]
                    ,buttons: [{
                           text: __('Save'),
                           formBind:true,
                           handler: this.onSave,
                           scope: this
                       },
                       {
                           text:__('Cancel'),
                           scope:this,
                           handler:function(){(this.ownerCt).close()}
                       }]

                }
            ];

        Primavera.EditUser.Form.superclass.initComponent.call(this);
    }
    ,onSave: function(){
        var form_values = this.getForm().getValues();
        var cod = this.getForm().findField("cod").getValue();
        //alert("alert " + " cod:" + cod + " name:" + form_values['Nome'] );
        if( !cod ){
            Ext.Msg.show({
                title: String.format(<?php echo json_encode(__('Error invalid username')) ?>),
                buttons: Ext.MessageBox.OK,
                msg: String.format(<?php echo json_encode(__('Invalid username!')) ?>),
                icon: Ext.MessageBox.ERROR});
        } else if( form_values['password'] != form_values['verpassword'] ){
            Ext.Msg.show({
                title: String.format(<?php echo json_encode(__('Error invalid password')) ?>),
                buttons: Ext.MessageBox.OK,
                msg: String.format(<?php echo json_encode(__('Passwords mismatch!')) ?>),
                icon: Ext.MessageBox.ERROR});
        } else {
            var send_data = {};
            send_data['u_cod'] = cod;
            send_data['u_name'] = form_values['Nome'];
            send_data['u_email'] = form_values['Email'];
            send_data['u_password'] = form_values['password'];
            send_data['u_suadmin'] = form_values['SuperAdministrador'];
            send_data['u_admin'] = this.getForm().findField("Administrador").getValue();
            send_data['u_tecnico'] = form_values['Tecnico'];
 
            // process change ip
            var conn = new Ext.data.Connection({
                listeners:{
                    // wait message.....
                    beforerequest:function(){
                        Ext.MessageBox.show({
                            title: <?php echo json_encode(__('Please wait...')) ?>,
                            msg: <?php echo json_encode(__('Saving User...')) ?>,
                            width:300,
                            wait: true,
                            modal: false
                        });
                    },// on request complete hide message
                    requestcomplete:function(){Ext.MessageBox.hide();}
                    ,requestexception:function(c,r,o){
                                Ext.MessageBox.hide();
                                Ext.Ajax.fireEvent('requestexception',c,r,o);}
                }
            });// end conn

            var method = 'primavera_updateuser';
            var operation = this.getForm().findField("operation").getValue();
            if( operation == 'new' ){
                method = 'primavera_insertuser';
            }
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
            this.items.get(1).items.get(0).insert(0, { fieldLabel:__('User'), name: 'cod', xtype:'textfield' });
        } else {
            this.form.findField('operation').setValue("update");
            this.items.get(1).items.get(0).insert(0, { fieldLabel:__('User'), name: 'cod', xtype:'displayfield' });

            if( data['user'] ){

                this.form.findField("cod").setValue(data['user']['cod']);

                this.load({url:<?php echo json_encode(url_for('primavera/json'))?>
                                ,params:{id:this.service_id,method:'primavera_viewuser', params: Ext.encode({cod:data['user']['cod']}) }
                                ,waitMsg:'Loading...'
                                /*,success:function(f,a){
                                    if( a.result['data']['users'].length > 0 ){
                                        var users = a.result['data']['users'];
                                        for(var i=0; i<users.length; i++){
                                            if( users[i]['cod'] == data['user']['cod'] ){
                                                return;
                                            }
                                        }
                                    }
                                }*/
                                ,scope: this
                            });
            }
        }
    }
});

Primavera.EditUser.Window = function(config) {

    Ext.apply(this,config);

    Primavera.EditUser.Window.superclass.constructor.call(this, {
        width:360
        ,height:320
        ,border:false
        ,modal:true
        ,layout:'fit'
        ,items:[ new Primavera.EditUser.Form({service_id:this.service_id})]
    });
};


Ext.extend(Primavera.EditUser.Window, Ext.Window,{
    loadData:function(data){
        this.items.get(0).loadRecord(data);
    }
});

</script>

