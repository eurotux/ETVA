<script>

Ext.ns('Server.Remove');

Server.Remove.Form = Ext.extend(Ext.form.FormPanel, {
    border:false
    ,autoScroll:true
    ,labelWidth:40
    ,bodyStyle:'padding-top:10px'
    ,url:null    
    ,initComponent:function() {

        this.items = [
            {xtype:'hidden',name:'server'},
            {xtype:'hidden',name:'server_id'},
            {name:'keep_fs',xtype:'checkbox',checked:true,
             helpText: <?php echo json_encode(__('Enable this will prevent disk file of being deleted')) ?>,
             boxLabel: <?php echo json_encode(__('Keep disk')) ?>}];

        // build form-buttons
        this.buttons = [{
            text: __('Yes'),
            ref: '../saveBtn',
            handler: this.onDelete,
            scope: this
            },
            {text:__('No'),scope:this,handler:function(){(this.ownerCt).close()}}];

        Server.Remove.Form.superclass.initComponent.call(this);

    }
    ,loadRecord:function(rec){
        this.getForm().loadRecord(rec);
    }
    ,onDelete:function(){

        var form_values = this.getForm().getValues();
        var send_data = new Object();
        var server_id = form_values['server_id'];
        var server = form_values['server'];

        send_data['id'] = server_id;
        send_data['keep_fs'] = 1;

        if(Ext.isEmpty(form_values['keep_fs'])) send_data['keep_fs'] = 0;

        // process delete
        var conn = new Ext.data.Connection({
            listeners:{
                // wait message.....
                beforerequest:function(){
                    Ext.MessageBox.show({
                        title: <?php echo json_encode(__('Please wait...')) ?>,
                        msg: <?php echo json_encode(__('Removing virtual server...')) ?>,
                        width:300,
                        wait:true,
                        modal: false
                    });
                },// on request complete hide message
                requestcomplete:function(){Ext.MessageBox.hide();}}
        });// end conn
        //send soap vmDestroy
        // on success delete from DB
        conn.request({
            url: this.url,
            params: send_data,
            scope:this,
            success: function(resp,opt) {

                var response = Ext.util.JSON.decode(resp.responseText);
                var sId = 's'+server_id;

                Ext.ux.Logger.info(response['agent'],response['response']);

                Ext.getCmp('view-main-panel').remove('view-center-panel-'+sId);
                this.ownerCt.fireEvent('onRemove');

                Ext.getCmp('view-nodes-panel').removeNode(sId);
                //Ext.getCmp('view-main-panel').remove('view-center-panel-'+sId);

            },
            failure: function(resp,opt) {
                var response = Ext.util.JSON.decode(resp.responseText);
                Ext.ux.Logger.error(response['agent'],response['error']);

                Ext.Msg.show({
                    title: String.format(<?php echo json_encode(__('Error {0}')) ?>,response['agent']),
                    buttons: Ext.MessageBox.OK,
                    msg: String.format(<?php echo json_encode(__('Unable to remove virtual server {0}!')) ?>,server)+'<br>'+response['info'],
                    icon: Ext.MessageBox.ERROR});

            }
        });// END Ajax request
    }
});


Server.Remove.Window = function(config) {

    Ext.apply(this,config);
    

    Server.Remove.Window.superclass.constructor.call(this, {
        defaults:{
            border:false,
            bodyStyle:'padding:10px;background:transparent;'
        },
        modal:true,
        width:300,
        bodyStyle:'padding:10px;',
        items:[
                {
                    bodyStyle:'font-size:12px;background:transparent;',
                    html: ''
                },
                new Server.Remove.Form({url: <?php echo json_encode(url_for('server/jsonRemove'))?>})]
    });

    this.on('onRemove',function(){    
        this.close();
        var parentCmp = Ext.getCmp(this.parent);

        if(parentCmp && parentCmp.isVisible()) parentCmp.fireEvent('refresh');
        
    });

};


Ext.extend(Server.Remove.Window, Ext.Window,{
    tools:[{id:'help', qtip: __('Help'),handler:function(){View.showHelp({anchorid:'help-vmachine-remove',autoLoad:{ params:'mod=server'},title: <?php echo json_encode(__('Remove Server Help')) ?>});}}],
    loadData:function(data){
        
        this.items.get(0).body.update(String.format(<?php echo json_encode(__('Remove server {0} ?')) ?>,data.data['server']));
        this.items.get(1).loadRecord(data);
    }
});

</script>