<script>

Ext.ns('Primavera.NewUser');

Primavera.NewUser.Form = new Ext.extend( Ext.form.FormPanel, {

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
                                    { fieldLabel:'User',
                                      name: 'cod',
                                      xtype:'textfield' },
                                    { fieldLabel:'Name',
                                      name: 'name',
                                      xtype:'textfield' },
                                    { fieldLabel:'Email',
                                      name: 'email',
                                      xtype:'textfield' },
                                    { fieldLabel:'Password',
                                      name: 'password',
                                      inputType: 'password',
                                      xtype:'textfield' },
                                    { fieldLabel:'Verify Password',
                                      name: 'verpassword',
                                      inputType: 'password',
                                      xtype:'textfield' },
                                    { fieldLabel:'Super Admin',
                                      name: 'suadmin',
                                      xtype:'checkbox', listeners: { 
                                                                'check':{scope:this,fn:function(cbox,ck){
                                                                                                        if(ck){
                                                                                                            this.form.findField('admin').setValue(true);
                                                                                                            this.form.findField('admin').disable();
                                                                                                        } else {
                                                                                                            this.form.findField('admin').enable();
                                                                                                        }
                                                                                                }}}},
                                    { fieldLabel:'Admin',
                                      name: 'admin',
                                      xtype:'checkbox' },
                                    { fieldLabel:'Tecnico',
                                      name: 'tecnico',
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

        Primavera.NewUser.Form.superclass.initComponent.call(this);
    }
    ,onSave: function(){
        var form_values = this.getForm().getValues();
        //alert("alert " + " cod:" + form_values['cod'] + " name:" + form_values['name'] );
        if( !form_values['cod'] ){
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
            send_data['u_cod'] = form_values['cod'];
            send_data['u_name'] = form_values['name'];
            send_data['u_email'] = form_values['email'];
            send_data['u_password'] = form_values['password'];
            send_data['u_suadmin'] = form_values['suadmin'];
            send_data['u_admin'] = form_values['admin'];
            send_data['u_tecnico'] = form_values['tecnico'];
 
            // process change ip
            var conn = new Ext.data.Connection({
                listeners:{
                    // wait message.....
                    beforerequest:function(){
                        Ext.MessageBox.show({
                            title: <?php echo json_encode(__('Please wait...')) ?>,
                            msg: <?php echo json_encode(__('New User...')) ?>,
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

            conn.request({
                url: <?php echo json_encode(url_for('primavera/json')) ?>,
                params: {
                    id: this.service_id,
                    method: 'primavera_insertuser',
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
                        msg: String.format(<?php echo json_encode(__('Error create new user!')) ?>)+'<br>'+response['info'],
                        icon: Ext.MessageBox.ERROR});

                }
            });// END Ajax request
        }
    }
    ,loadRecord: function(){
        this.form.findField('id').setValue(this.service_id);
        /*this.load({url:<?php echo json_encode(url_for('primavera/json'))?>,params:{id:this.service_id,method:'primavera_listusers'} ,waitMsg:'Loading...'
                        ,success:function(f,a){
                            if( a.result['data']['users'].length > 0 ){
                                var users = a.result['data']['users'];
                                var data = new Array();
                                for(var i=0; i<users.length; i++){
                                    var e = new Array(users[i]['cod'],users[i]['name']);
                                    data.push(e);
                                }
                                
                                Ext.getCmp('grid-users').store.loadData(data);
                            }
                        }
                        ,scope: this
                    });*/
    }
});

Primavera.NewUser.Window = function(config) {

    Ext.apply(this,config);

    Primavera.NewUser.Window.superclass.constructor.call(this, {
        width:360
        ,height:320
        ,border:false
        ,modal:true
        ,layout:'fit'
        ,items:[ new Primavera.NewUser.Form({service_id:this.service_id})]
    });
};


Ext.extend(Primavera.NewUser.Window, Ext.Window,{
    loadData:function(data){
        this.items.get(0).loadRecord(data);
    }
});

</script>

