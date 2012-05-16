<script>
Ext.ns('Server.Migrate');

Server.Migrate.Form = Ext.extend(Ext.form.FormPanel, {
    border:true
    ,monitorValid:true
    ,autoScroll:true
    ,width:370
    ,labelWidth:140
    ,bodyStyle:'padding:10px;'
    ,initComponent:function() {
        
        this.items = [
            {xtype:'hidden',name:'id'}
//            ,{xtype:'box',height:40
//            ,autoEl:{
//                tag:'div',
//                children:[{
//                            tag:'div'
//                            ,style:'float:left;width:31px;height:32px;'
//                            ,cls:'icon-warning'
//                        },
//                        {
//                            tag:'div'
//                            ,style:'margin-left:35px'
//                            ,html: <?php //echo json_encode(__('Server must be running!')) ?>
//                        }]
//                }
//            },
            ,{xtype:'textfield', fieldLabel: <?php echo json_encode(__('Virtual server name')) ?>,readOnly:true, allowBlank:false, name:'name'}
            ,{xtype:'combo',emptyText: __('Select...'),fieldLabel: <?php echo json_encode(__('Destination node')) ?>,triggerAction: 'all',
                            selectOnFocus:true,forceSelection:true,editable:false,allowBlank:false,name:'nodes_cb',hiddenName:'nodes_cb',valueField:'Id',displayField:'name',store:new Ext.data.Store({
                            proxy:new Ext.data.HttpProxy({url:'node/JsonListCluster'}),
                            reader: new Ext.data.JsonReader({
                                        root:'data',
                                        fields:['Id',{name:'name',mapping:'Name'}]
                            })
            })}
        ];

        // build form-buttons
        this.buttons = [{
                            text: __('Ok'),
                            formBind:true,
                            handler: this.onSave,
                            scope: this
                        },
                        {
                            text:__('Cancel'),
                            scope:this,
                            handler:function(){(this.ownerCt).close()}
                        }];

        Server.Migrate.Form.superclass.initComponent.call(this);

    }
    ,loadRecord:function(rec){

        var cb = this.getForm().findField('nodes_cb');
        cb.store.baseParams = {sid:rec.data['id']};

        this.getForm().loadRecord(rec);

        cb.setValue( rec.data['target_id'] );
        cb.setRawValue( rec.data['target_name'], false );

    }
    ,onSave:function(){
              
        var form_values = this.getForm().getValues();
        var send_data = new Object();

        send_data['id'] = form_values['id'];
        send_data['nid'] = form_values['nodes_cb'];

        switch(this.type){
            case 'migrate' :
                            var url = <?php echo json_encode(url_for('server/jsonMigrate')) ?>;
                            var wait_msg = <?php echo json_encode(__('Migrating virtual server...')) ?>;
                            var err_msg = <?php echo json_encode(__('Unable to migrate virtual server {0}!')) ?>;
                            break;
               case 'move' :
                            var url = <?php echo json_encode(url_for('server/jsonMove')) ?>;
                            var wait_msg = <?php echo json_encode(__('Moving virtual server...')) ?>;
                            var err_msg = <?php echo json_encode(__('Unable to move virtual server {0}!')) ?>;
                            break;


        }

        // process delete
        var conn = new Ext.data.Connection({
            listeners:{
                // wait message.....
                beforerequest:function(){
                    Ext.MessageBox.show({
                        title: <?php echo json_encode(__('Please wait...')) ?>,
                        msg: wait_msg,
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
            url: url,
            params: send_data,
            scope:this,
            success: function(resp,opt) {

                var response = Ext.util.JSON.decode(resp.responseText);
                var sId = 's'+form_values['id'];

                Ext.ux.Logger.info(response['agent'],response['response']);

                this.ownerCt.fireEvent('onSave',sId);
                this.ownerCt.close();

            },
            failure: function(resp,opt) {
            
                var response = Ext.util.JSON.decode(resp.responseText);                

                Ext.Msg.show({
                    title: String.format(<?php echo json_encode(__('Error {0}')) ?>,response['agent']),
                    buttons: Ext.MessageBox.OK,
                    msg: String.format(err_msg,form_values['name'])+'<br>'+response['info'],
                    icon: Ext.MessageBox.ERROR});

            }
        });// END Ajax request
    }
});


Server.Migrate.Window = function(config) {

    Ext.apply(this,config);

    Server.Migrate.Window.superclass.constructor.call(this, {        
        width:390,
        modal:true,
        bodyStyle:'padding:3px;',
        items:[ new Server.Migrate.Form({type:this.type})]
    });

    this.on('onSave',function(sId){        

        Ext.getCmp('view-nodes-panel').removeNode(sId);
        Ext.getCmp('view-main-panel').remove('view-center-panel-'+sId);

//        if(Ext.getCmp(this.parent) && Ext.getCmp(this.parent).isVisible())
//            Ext.getCmp(this.parent).fireEvent('refresh');
        
    });

};


Ext.extend(Server.Migrate.Window, Ext.Window,{
    tools:[{id:'help', qtip: __('Help'),handler:function(){View.showHelp({anchorid:'help-vmachine-migrate',autoLoad:{ params:'mod=server'},title: <?php echo json_encode(__('Migrate Server Help')) ?>});}}],
    loadData:function(src){
        this.items.get(0).loadRecord(src);        
        
    }        
});

</script>
