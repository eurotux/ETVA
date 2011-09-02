<script>
Ext.ns("Appliance.Register");

Appliance.Register.Form = Ext.extend(Ext.form.FormPanel, {
    labelWidth:80,
    border:false
    ,autoScroll:true
    ,bodyStyle:'padding:10px;'
    ,initComponent:function() {

        var config = {
            monitorValid:true,            
            items:[               
                {
                    xtype:'displayfield',
                    hideLabel:true,
                    value: <?php echo json_encode(__('You should register ONLY ONCE! If you see a SERIAL NUMBER below it means you already register.<br> Optionally, you can also provide a brief description to better indentify this Appliance')) ?>
                }
                ,{
                    xtype:'displayfield',fieldLabel:'Serial Number',name:'sn'
                }
                ,{
                    xtype:'textfield',fieldLabel:<?php echo json_encode(__('Description')) ?>,name:'description',width:200
                }
                ,{
                    xtype: 'fieldset',
                    style:'margin-top:20px',
                    defaultType:'textfield',
                    title: <?php echo json_encode(__('Authentication')) ?>,
                    collapsible: false,
                    items: [
                        {
                            xtype:'hidden',
                            name: 'serial_number'
                        },
                        {
                            fieldLabel: <?php echo json_encode(__('Username')) ?>,
                            name: 'username',                            
                            allowBlank:false
                        },
                        {
                            fieldLabel: <?php echo json_encode(__('Password')) ?>,
                            inputType:'password',
                            name: 'password',
                            allowBlank:false
                        }

                    ]
                }
            ]
        };

        this.buttons = [{
                            text: __('Save'),
                            handler:this.save,
                            scope:this,
                            formBind:true
                        }
                        ,{
                            text: __('Cancel'),
                            scope:this,
                            handler:function(){
                                this.fireEvent('onCancel');
                            }
                        }];

        // apply config
        Ext.apply(this, Ext.apply(this.initialConfig, config));

        // call parent
        Appliance.Register.Form.superclass.initComponent.apply(this, arguments);


    } // eo function initComponent
    ,onRender:function() {
        // call parent
        Appliance.Register.Form.superclass.onRender.apply(this, arguments);

        // set wait message target
        this.getForm().waitMsgTarget = this.getEl();


    } // eo function onRender
    ,getFocusField:function(){
        return this.getForm().findField('description');
    }
    ,loadData:function(){        

        this.load({
            url:this.url,
            waitMsg: <?php echo json_encode(__('Retrieving data...')) ?>,
            params:{params:Ext.encode(['serial_number'])},
            failure:function(){
                alert('falhou');
                this.disable();
            },
            success: function ( form, action ) {
                var result = action.result;
                var data = result.data;

                this.getForm().findField('sn').setValue(data['serial_number']);
                //var rec = action.result;
                //this.getForm().loadRecord(rec);
            },scope:this
        });

    }    
    ,save:function(){


        if (this.form.isValid()) {
                        
            var alldata = this.form.getValues();
            var send_data = new Object();
            var send_data = alldata;
            send_data['method'] = 'register';

            var conn = new Ext.data.Connection({
                listeners:{
                    // wait message.....
                    beforerequest:function(){
                        Ext.MessageBox.show({
                            title: <?php echo json_encode(__('Please wait...')) ?>,
                            msg: <?php echo json_encode(__('Registering ETVA...')) ?>,
                            width:300,
                            wait:true,
                            modal: false
                        });
                    },// on request complete hide message
                    requestcomplete:function(){Ext.MessageBox.hide();}
                    ,requestexception:function(c,r,o){                        
                        Ext.Ajax.fireEvent('requestexception',c,r,o);}
                }
            });// end conn

            conn.request({
                url: this.url,
                params:send_data,                                
                // everything ok...
                failure:function(response){
                    var resp = Ext.decode(response.responseText);
                    
                    Ext.MessageBox.show({
                            title: String.format(<?php echo json_encode(__('Error {0}')) ?>,resp['agent']),
                            msg: String.format(<?php echo json_encode(__('Could not register ETVA.<br> {0}')) ?>,resp['info']),
                            buttons: Ext.MessageBox.OK,                            
                            icon: Ext.MessageBox.ERROR
                        });

                }
                ,success: function(resp,opt){

                    var response = Ext.util.JSON.decode(resp.responseText);
                    var msg = String.format(<?php echo json_encode(__('Register OK with serial number <b>{0}</b>')) ?>,response['serial_number']);
                    View.notify({html:msg});
                    this.fireEvent('onSave');


                },scope:this
            });// END Ajax request


        }else{
            //form not valid...
            var f = this.form.findInvalid()[0];
            if(f) f.ensureVisible();        

            Ext.MessageBox.show({
                title: <?php echo json_encode(__('Error!')) ?>,
                msg: <?php echo json_encode(__('Please fix the errors noted!')) ?>,
                buttons: Ext.MessageBox.OK,
                icon: Ext.MessageBox.WARNING
            });
        }

    }


}); // eo extend


Appliance.Register.Main = function(config) {

    Ext.apply(this, config);
    // main panel
    var win = Ext.getCmp('appliance-register-main');

    if(!win){        

        var register_form = new Appliance.Register.Form({url:<?php echo json_encode(url_for('appliance/jsonRegister'))?>});

        win = new Ext.Window({
                id:          'appliance-register-main',
                title:       this.title,
                modal:       true,
                iconCls:     'icon-etva',
                maxW:400,
                maxH:300,
                defaultButton: register_form.getFocusField(),
                layout:      'fit',
                border:       false,
                items:[register_form]
                ,tools:[{id:'help', qtip: __('Help'),handler:function(){View.showHelp({anchorid:'help-appliance-register',autoLoad:{ params:'mod=appliance'},title: <?php echo json_encode(__('Register Appliance Help')) ?>});}}]
             ,listeners:{
                        'close':function(){
                            Ext.EventManager.removeResizeListener(win.resizeFunc,win);
                        }
                    }
        });
        
        register_form.on({
                'onSave':{scope:this,fn:function(){win.close();}}
                ,'onCancel':function(){win.close();}
            });

        win.on({'show':function(){
                  this.items.get(0).loadData();
        }});

        

    }

    //on browser resize, resize window
    Ext.EventManager.onWindowResize(win.resizeFunc,win);

    win.resizeFunc();
    win.show();

};

</script>