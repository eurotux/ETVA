<script>

Ext.ns('Primavera.ChangeIP');

Primavera.ChangeIP.Form = new Ext.extend( Ext.form.FormPanel, {

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
                                    {
                                        boxLabel: <?php echo json_encode(__('DHCP')) ?>,
                                        //xtype:'radio', checked: (data['dhcp']==1) ? true : false,
                                        xtype:'radio',
                                        name:'dhcp',inputValue:'1',ref:'dhcp'
                                    },
                                    {
                                        boxLabel: <?php echo json_encode(__('Static')) ?>,
                                        //xtype:'radio', checked: (data['dhcp']==1) ? true : false,
                                        xtype:'radio',
                                        name:'dhcp',inputValue:'0',ref:'dhcp'
                                    },
                                    { fieldLabel:__('IP address'),
                                      name: 'ipaddr',
                                      xtype:'textfield' },
                                    { fieldLabel: __('Netmask'),
                                      name: 'netmask',
                                      xtype:'textfield' },
                                    { fieldLabel: __('Gateway'),
                                      name: 'gateway',
                                      xtype:'textfield' }
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

        Primavera.ChangeIP.Form.superclass.initComponent.call(this);
    }
    ,onSave: function(){
        //alert("save");
        var form_values = this.getForm().getValues();
        //alert("alert " + " dhcp:" + form_values['dhcp'] + " ipaddr:" + form_values['ipaddr'] );
        var send_data = {};
        if( form_values['dhcp'] == 1 ){
            send_data['dhcp'] = 1;
        } else {
            send_data['ipaddr'] = form_values['ipaddr'];
            send_data['netmask'] = form_values['netmask'];
            send_data['gateway'] = form_values['gateway'];
        }

        // process change ip
        var conn = new Ext.data.Connection({
            listeners:{
                // wait message.....
                beforerequest:function(){
                    Ext.MessageBox.show({
                        title: <?php echo json_encode(__('Please wait...')) ?>,
                        msg: <?php echo json_encode(__('Changing IP Address...')) ?>,
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
            url: <?php echo json_encode(url_for('primavera/jsonNoWait')) ?>,
            params: {
                id: this.service_id,
                method: 'change_ip',
                params:Ext.encode(send_data)                
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
                    msg: String.format(<?php echo json_encode(__('Unable to change ip address!')) ?>)+'<br>'+response['info'],
                    icon: Ext.MessageBox.ERROR});

            }
        });// END Ajax request



    }
    ,loadRecord: function(){
        this.load({url:<?php echo json_encode(url_for('primavera/json'))?>,params:{id:this.service_id,method:'primavera_info'} ,waitMsg:'Loading...'
                        /*,success:function(f,a){
                            var data = a.result['data'];
                            var dhcp = this.findField('dhcp');
                            alert(data['']);

                        }*/
                    });
    }
});

Primavera.ChangeIP.Window = function(config) {

    Ext.apply(this,config);

    Primavera.ChangeIP.Window.superclass.constructor.call(this, {
        width:320
        ,height:240
        ,border:false
        ,modal:true
        ,layout:'fit'
        ,items:[ new Primavera.ChangeIP.Form({service_id:this.service_id})]
    });

    

};


Ext.extend(Primavera.ChangeIP.Window, Ext.Window,{
    loadData:function(data){
        this.items.get(0).loadRecord(data);
    }
});

</script>
