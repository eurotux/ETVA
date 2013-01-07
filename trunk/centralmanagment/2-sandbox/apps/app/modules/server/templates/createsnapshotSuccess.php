<?php
?>
<script>
Ext.ns('Server.CreateSnapshot');

Server.CreateSnapshot.Form = Ext.extend(Ext.form.FormPanel, {    
    id: 'server-createsnapshot-form',
    border:true
    ,monitorValid:true   
    ,initComponent:function() {

        var config = {
            items: [
                    {
                        xtype:'textfield',
                        fieldLabel: __('Snapshot name'), 
                        allowBlank:false,
                        name:'snapshot',
                        /*invalidText : <?php echo json_encode(__('No spaces and only alpha-numeric characters allowed!')) ?>,
                        validator  : function(v){
                            var t = /^[a-zA-Z][a-zA-Z0-9\-\_]+$/;
                            return t.test(v) && !d.test(v);
                        },*/
                        listeners:{
                            specialkey:{
                                scope:this,
                                fn:function(field,e){
                                    if(e.getKey()==e.ENTER) this.onSave();
                                }
                            }
                        }
                    }
            ]
            ,scope:this
            ,bodyStyle:'padding:10px'
            ,buttons:[{
                            text: __('Save'),
                            formBind:true,
                            scope:this,
                            handler:this.onSave
                        },
                        {
                            text: __('Cancel'),
                            scope:this,
                            handler:function(){
                                (this.ownerCt).close();
                            }
                        }
                     ]
        };

        // apply config
        Ext.apply(this, Ext.apply(this.initialConfig, config));

        Server.CreateSnapshot.Form.superclass.initComponent.call(this);
    }
    ,onSave:function(btn,e){

        if (this.form.isValid()) {

                
            var form_fieldvalues = this.getForm().getFieldValues();
            var form_values = this.getForm().getValues();

            var send_data = {'id':this.server_id,'snapshot':form_values['snapshot']};

            var conn = new Ext.data.Connection({
                listeners:{
                    // wait message.....
                    beforerequest:function(){
                        Ext.MessageBox.show({
                            title: <?php echo json_encode(__('Please wait...')) ?>,
                            msg: <?php echo json_encode(__('Create snapshot...')) ?>,
                            width:300,
                            wait:true,
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
                url:<?php echo json_encode(url_for('server/jsonCreateSnapshot')) ?>,
                params:send_data,
                // everything ok...
                success: function(resp,opt){
                    var response = Ext.util.JSON.decode(resp.responseText);
                    var okmsg =  String.format(<?php echo json_encode(__('Virtual machine snapshot created successfully.')) ?>);
                    Ext.ux.Logger.info(response['agent'],okmsg);
                    (this.ownerCt).fireEvent('onSave');
                }
                ,failure: function(resp,opt) {
                    var response = Ext.util.JSON.decode(resp.responseText);
                    var error =  String.format(<?php echo json_encode(__('Unable to create snapshot')) ?>)+'<br>'+response['info'];
                    Ext.ux.Logger.error(response['agent'],error);
                    Ext.Msg.show({
                        title: String.format(<?php echo json_encode(__('Error {0}')) ?>,response['agent']),
                        width:300,
                        buttons: Ext.MessageBox.OK,
                        msg: error,
                        icon: Ext.MessageBox.ERROR}); 

                }
                ,scope:this
                
            });// END Ajax request


        } else{
            Ext.MessageBox.show({
                title: <?php echo json_encode(__('Error!')) ?>,
                msg: <?php echo json_encode(__('Please fix the errors noted!')) ?>,
                buttons: Ext.MessageBox.OK,
                icon: Ext.MessageBox.WARNING
            });
        }
    }
});

Server.CreateSnapshot.Window = function(config) {

    Ext.apply(this,config);

    Server.CreateSnapshot.Window.superclass.constructor.call(this, {
        tools:[{id:'help', qtip: __('Help'),handler:function(){View.showHelp({anchorid:'help-vmachine-snapshot',autoLoad:{ params:'mod=server'},title: <?php echo json_encode(__('Snapshots Server Help')) ?>});}}],
        id: 'server-createsnapshot-window',
        width:290
        ,height:120
        ,border:false
        ,modal:true
        ,layout:'fit'
        ,items:[ new Server.CreateSnapshot.Form({'server_id':this.server_id,'server_name':this.server_name})]
    });
};

Ext.extend(Server.CreateSnapshot.Window, Ext.Window,{
});

</script>

