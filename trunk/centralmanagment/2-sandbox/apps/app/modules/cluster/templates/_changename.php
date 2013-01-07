<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of _ChangeName
 *
 * Show a window with a field for changing clusters name
 *
 * @author mfd
 */

?>

<script>

Ext.ns("Cluster.ChangeName");
Cluster.ChangeName = Ext.extend(Ext.form.FormPanel, {
    border:false
    ,height:100
    ,labelWidth:100   
    ,initComponent:function() {
        var config = {
            monitorValid:true
            ,items:[{
                xtype:'hidden', 
                name:'id'
            },{
                xtype:'textfield',
                fieldLabel: __('Name'), 
                allowBlank:false,
                name:'name',                
                //invalidText : <?php echo json_encode(__('No spaces and only alpha-numeric characters allowed! "Default" not availalable.')) ?>,
                invalidText : <?php echo json_encode(__('No spaces and only alpha-numeric characters allowed!')) ?>,
                validator  : function(v){
                    var t = /^[a-zA-Z][a-zA-Z0-9\-\_]+$/;
                    //var d = /^Default$/;
                    //return t.test(v) && !d.test(v);
                    return t.test(v);
                }
                ,listeners:{
                    specialkey:{
                        scope:this,
                        fn:function(field,e){
                            if(e.getKey()==e.ENTER) this.onSave();
                        }
                    }
                }
            }
            ,{
                xtype: 'checkbox',
                fieldLabel: __('Default'), 
                xtype:'checkbox',
                name:'isdefault',
                inputValue:'1',
                ref:'isdefault'
            }
            ]
            ,frame:true
            ,scope:this
            ,bodyStyle:'padding:10px'
            ,buttons:[{
                            text: __('Save'),
                            formBind:true,
                            scope:this,
                            handler:function(){
                                this.onSave();}
                            },
                            {
                                text: __('Cancel'),
                                scope:this,
                                handler:function(){
                                    this.fireEvent('onCancel');
                                }
                            }
                     ]
            //,buttons:[{text:'ok'}]
        };


        // apply config
        Ext.apply(this, Ext.apply(this.initialConfig, config));

        // call parent
        Cluster.ChangeName.superclass.initComponent.apply(this, arguments);


    } // eo function initComponent
    ,onRender:function() {
        // call parent
        Cluster.ChangeName.superclass.onRender.apply(this, arguments);

        // set wait message target
        this.getForm().waitMsgTarget = this.getEl();


    } // eo function onRender
    ,loadData:function(id){


        this.load({
            url:<?php echo json_encode(url_for('cluster/jsonName')) ?>,
            waitMsg: <?php echo json_encode(__('Please wait...')) ?>,
            params:{id:id},
            success: function ( form, action ) {
                var name = form.findField('name');
                name.focus(true,20);

            },scope:this
        });

    }
    ,onSave:function(){

        if (this.form.isValid()) {

            var alldata = this.form.getValues();

            var send_data = {'method':'update','id':alldata['id'],'name':alldata['name']};

            var conn = new Ext.data.Connection({
                listeners:{
                    // wait message.....
                    beforerequest:function(){
                        Ext.MessageBox.show({
                            title: <?php echo json_encode(__('Please wait...')) ?>,
                            msg: <?php echo json_encode(__('Updating cluster name...')) ?>,
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
                url:<?php echo json_encode(url_for('cluster/jsonName')) ?>,
                params:send_data,
                // everything ok...
                success: function(resp,opt){

                    var response = Ext.util.JSON.decode(resp.responseText);
                    if(response['success']){
                        Ext.ux.Logger.info(response['agent'],response['info']);
                        this.fireEvent('onSave');
                    }else{
                        Ext.ux.Logger.error(response['agent'],response['info']);
                        //var msg = String.format(<?php echo json_encode(__('Network {0} could not be initialized: {1}')) ?>,name,'<br>'+agents);
                        var msg = response['info'];
                        Ext.Msg.show({
                            title: String.format(<?php echo json_encode(__('Error {0}')) ?>,response['agent']),
                            width:300,
                            buttons: Ext.MessageBox.OK,
                            msg: msg,
                            icon: Ext.MessageBox.ERROR}); 
                    }


                }
                ,failure: function(resp,opt) {
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
}); // eo extend

</script>
