<?php

include_partial('pool/createwin');
?>
<script>

Ext.ns("poolwin.GridForm");

poolwin.GridForm.Main = function(node_id, level) {

    this.level = level;
    this.node_id = node_id;    

    var myparams = {};
    var myurl = <?php echo json_encode(url_for('pool/jsonList'))?>;

    if(this.level == 'cluster'){
        myparams = {'cid':this.node_id, 'level': this.level};
    }else if(this.level == 'node'){
        myparams = {'nid':this.node_id, 'level': this.level};
    }

    this.gridPool = new Ext.grid.GridPanel({
                                    id: 'grid-pool-list',
                                    layout:'fit',
                                    border: false,
                                    sm: new Ext.grid.RowSelectionModel({singleSelect:true}),
                                    viewConfig: {
                                        forceFit: true,
                                        getRowClass: function(record,index){
                                                        if( record.get('inconsistent') == 'true' ){
                                                            return 'list-pool-inconsistent';
                                                        }
                                        }
                                    },
                                    colModel: new Ext.grid.ColumnModel({
                                                    columns: [
                                                        {header: __('Uuid'), dataIndex: 'uuid'},
                                                        {header: __('Name'), dataIndex: 'name'},
                                                        {header: __('Type'), dataIndex: 'type', hidden: true},
                                                        {header: __('Host'), dataIndex: 'source_host'},
                                                        {header: __('Shared'), dataIndex: 'shared', hidden: true},
                                                        {header: __('Target IQN'), dataIndex: 'source_device'},
                                                        {header: __('Capacity'), dataIndex: 'capacity', renderer: function(v){ return Ext.util.Format.fileSize(v); } }
                                                    ]
                                                }),
                                   store: new Ext.data.JsonStore({
                                                root: 'data'
                                                ,totalProperty: 'total'
                                                ,baseParams: myparams
                                                ,listeners:{
                                                    'beforeload':function(){                                    
                                                        this.ownerCt.body.mask(<?php echo json_encode(__('Please wait...')) ?>, 'x-mask-loading');
                                                    },
                                                    'load':function(store,records,options){
                                                        this.ownerCt.body.unmask();                
                                                    },
                                                    'loadexception':function(store,options,resp,error){                                        
                                                        this.ownerCt.body.unmask();                    
                                                    }
                                                    ,
                                                    scope:this
                                                }
                                                ,url: myurl
                                                ,fields:[
                                                    {name: 'id', mapping: 'id', id: 'int'}
                                                    ,{name: 'uuid', mapping: 'uuid', uuid: 'string'}
                                                    ,{name: 'name', mapping: 'name', type:'string'}
                                                    ,{name: 'type', mapping: 'type', type: 'string'}
                                                    ,{name: 'source_host', mapping: 'source_host', source_host: 'string'}
                                                    ,{name: 'source_device', mapping: 'source_device', source_device: 'string'}
                                                    ,{name: 'shared', mapping: 'shared', type:'string'}
                                                    ,{name: 'capacity', mapping: 'capacity', type:'int'}
                                                    ,{name: 'inconsistent', mapping: 'inconsistent', type:'string'}
                                                ]
                                            })
                                    ,tbar: [
                                            {
                                                text: <?php echo json_encode(__('New') . ' ' . __(sfConfig::get('app_storage_pool_title'))) ?>,
                                                iconCls: 'go-action',
                                                handler: function(){

                                                    var win = Ext.getCmp('pool-create-win');

                                                    if(!win){
                                                        var centerPanel;

                                                        if(this.level == 'cluster' || this.level == 'node'){
                                                            centerPanel = new poolwin.createForm.Main(this.node_id, this.level);
                                                        }else{
                                                            centerPanel = new poolwin.createForm.Main(this.node_id);
                                                        }
                                                        
                                                        centerPanel.on('updated',function(){
                                                            win.close();
                                                            this.gridPool.getStore().reload();
                                                        },this);

                                                        win = new Ext.Window({
                                                            id: 'pool-create-win',
                                                            title: <?php echo json_encode(__(sfConfig::get('app_storage_pool_title'))) ?>,
                                                            width:430,
                                                            height:320,
                                                            modal:true,
                                                            iconCls: 'icon-window',
                                                            bodyStyle: 'padding:10px;',
                                                            border:true,
                                                            layout: 'fit',
                                                            items: [centerPanel]
                                                            ,tools: [{
                                                                id:'help',
                                                                qtip: __('Help'),
                                                                handler:function(){
                                                                    View.showHelp({
                                                                        anchorid:'help-pool-add',
                                                                        autoLoad:{ params:'mod=pool'},
                                                                        title: <?php echo json_encode(__(sfConfig::get('app_storage_pool_title') . ' Help')) ?>
                                                                    });
                                                                }
                                                            }]
                                                        });

                                                    }

                                                    win.show();
                                                    //gridPool.store.reload();
                                                    centerPanel.load();
                                                }
                                                ,scope: this
                                            }
                                            ]
                                    ,tools: [{
                                                id:'refresh',
                                                handler: function(){
                                                    this.gridPool.getStore().reload();
                                                }
                                                ,scope: this
                                            }]
                                    ,onRowContextMenu: function(grid,rowIndex,e){
                                        if(this.ctxRow){
                                            // this.ctxNode.ui.removeClass('x-node-ctx');
                                            this.ctxRow = null;
                                        }
                                        grid.getSelectionModel().selectRow(rowIndex);
                                        this.ctxRow = grid.getView().getRow(rowIndex);
                                        this.ctxRecord = grid.getSelectionModel().getSelected();

                                        if(!this.menu){ // create context menu on first right click
                                            this.menu = new Ext.menu.Menu({
                                                items: [
                                                    {
                                                        id:'pool-reload',
                                                        iconCls:'go-action',
                                                        text: <?php echo json_encode(__('Reload') . ' ' . __(sfConfig::get('app_storage_pool_title'))) ?>,
                                                        scope: this,
                                                        handler:function(){
                                                            var conn = new Ext.data.Connection({
                                                                            listeners:{
                                                                                // wait message.....
                                                                                beforerequest:function(){

                                                                                    Ext.MessageBox.show({
                                                                                        title: <?php echo json_encode(__('Please wait...')) ?>,
                                                                                        msg: <?php echo json_encode(__('Reload') . ' ' . __(sfConfig::get('app_storage_pool_title')) . '...') ?>,
                                                                                        width:300,
                                                                                        wait:true
                                                                                    });

                                                                                },// on request complete hide message
                                                                                requestcomplete:function(){Ext.MessageBox.hide();}
                                                                                ,requestexception:function(c,r,o){
                                                                                        Ext.MessageBox.hide();
                                                                                        Ext.Ajax.fireEvent('requestexception',c,r,o);}
                                                                            }
                                                            });// end conn

                                                            //console.log(this.ctxRecord);

                                                            var params = myparams;
                                                            params['id'] = this.ctxRecord.get('id');
                                                            params['uuid'] = this.ctxRecord.get('uuid');
                                                            params['name'] = this.ctxRecord.get('name');
                                                            
                                                            conn.request({
                                                                url: <?php echo json_encode(url_for('pool/jsonReload'))?>,
                                                                params: params,
                                                                scope:this,
                                                                success: function(resp,opt){
                                                                    var response = Ext.util.JSON.decode(resp.responseText);
                                                                    Ext.ux.Logger.info(response['agent'],response['response']);                    

                                                                    if( response['errors'] ){
                                                                        var errors = response['errors'];
                                                                        if( errors.length > 0 ){
                                                                            for(var i=0; i<errors.length; i++){
                                                                                var err = errors[i];
                                                                                Ext.ux.Logger.error(err['agent'],err['response']);
                                                                            }
                                                                            Ext.Msg.show({
                                                                            title: <?php echo json_encode(__('Reload') . ' ' . __(sfConfig::get('app_storage_pool_title'))) ?>,
                                                                            buttons: Ext.MessageBox.OK,
                                                                            msg: String.format(<?php echo json_encode(__(sfConfig::get('app_storage_pool_title')) . ' ' . __('{0} reloaded with some errors. See info panel.')) ?>,params['name']),
                                                                            icon: Ext.MessageBox.WARNING});
                                                                        }
                                                                    }

                                                                    this.gridPool.getStore().reload();
                                                                },
                                                                failure: function(resp,opt) {
                                                                    var response = Ext.util.JSON.decode(resp.responseText);
                                                                    
                                                                    if(response)
                                                                    {
                                                                        Ext.Msg.show({
                                                                            title: String.format(<?php echo json_encode(__('Error {0}')) ?>,response['agent']),
                                                                            buttons: Ext.MessageBox.OK,
                                                                            msg: String.format(<?php echo json_encode(__('Unable to reload') . ' ' . __(sfConfig::get('app_storage_pool_title')) . ' {0}!') ?>,params['name'])+'<br>'+response['info'],
                                                                            icon: Ext.MessageBox.ERROR});
                                                                    }
                                                                    this.gridPool.getStore().reload();
                                                                }
                                                            });// END Ajax request
                                                        }
                                                    },
                                                    {
                                                        id:'pool-remove',
                                                        iconCls:'go-action',
                                                        text: <?php echo json_encode(__('Remove') .' ' . __(sfConfig::get('app_storage_pool_title'))) ?>,
                                                        scope: this,
                                                        handler:function(){
                                                            var conn = new Ext.data.Connection({
                                                                            listeners:{
                                                                                // wait message.....
                                                                                beforerequest:function(){

                                                                                    Ext.MessageBox.show({
                                                                                        title: <?php echo json_encode(__('Please wait...')) ?>,
                                                                                        msg: <?php echo json_encode(__('Remove') . ' ' . __(sfConfig::get('app_storage_pool_title')) . '...') ?>,
                                                                                        width:300,
                                                                                        wait:true
                                                                                    });

                                                                                },// on request complete hide message
                                                                                requestcomplete:function(){Ext.MessageBox.hide();}
                                                                                ,requestexception:function(c,r,o){
                                                                                        Ext.MessageBox.hide();
                                                                                        Ext.Ajax.fireEvent('requestexception',c,r,o);}
                                                                            }
                                                            });// end conn

                                                            //console.log(this.ctxRecord);

                                                            var params = myparams;
                                                            params['id'] = this.ctxRecord.get('id');
                                                            params['uuid'] = this.ctxRecord.get('uuid');
                                                            params['name'] = this.ctxRecord.get('name');
                                                            
                                                            conn.request({
                                                                url: <?php echo json_encode(url_for('pool/jsonRemove'))?>,
                                                                params: params,
                                                                scope:this,
                                                                success: function(resp,opt){
                                                                    var response = Ext.util.JSON.decode(resp.responseText);
                                                                    Ext.ux.Logger.info(response['agent'],response['response']);                    

                                                                    if( response['errors'] ){
                                                                        var errors = response['errors'];
                                                                        if( errors.length > 0 ){
                                                                            for(var i=0; i<errors.length; i++){
                                                                                var err = errors[i];
                                                                                Ext.ux.Logger.error(err['agent'],err['response']);
                                                                            }
                                                                            Ext.Msg.show({
                                                                            title: <?php echo json_encode(__('Remove') .' ' . __(sfConfig::get('app_storage_pool_title'))) ?>,
                                                                            buttons: Ext.MessageBox.OK,
                                                                            msg: String.format(<?php echo json_encode(__(sfConfig::get('app_storage_pool_title')) . ' {0} ' . __('removed with some errors. See info panel.')) ?>,params['name']),
                                                                            icon: Ext.MessageBox.WARNING});
                                                                        }
                                                                    }
 
                                                                    this.gridPool.getStore().reload();
                                                                },
                                                                failure: function(resp,opt) {
                                                                    var response = Ext.util.JSON.decode(resp.responseText);
                                                                    
                                                                    if(response)
                                                                    {
                                                                        Ext.Msg.show({
                                                                            title: String.format(<?php echo json_encode(__('Error {0}')) ?>,response['agent']),
                                                                            buttons: Ext.MessageBox.OK,
                                                                            msg: String.format(<?php echo json_encode(__('Unable to remove') . ' ' . __(sfConfig::get('app_storage_pool_title')) . ' {0}!') ?>,params['name'])+'<br>'+response['info'],
                                                                            icon: Ext.MessageBox.ERROR});
                                                                    }
                                                                    this.gridPool.getStore().reload();
                                                                }
                                                            });// END Ajax request
                                                        }
                                                    }]
                                            });// end menu

                                            //this.menu.on('hide', this.onRowContextHide, this);
                                        }

                                        // Stops the browser context menu from showing.
                                        //e.stopEvent();
                                        this.menu.showAt(e.getXY());
                                        e.preventDefault();
                                    }
                            });
    // on context click call onRowContextMenu
    this.gridPool.on('rowcontextmenu', this.gridPool.onRowContextMenu,this);


    // define window and pop-up - render formPanel
    //var centerPanel = new Ext.form.FormPanel({
    poolwin.GridForm.Main.superclass.constructor.call(this, {        
        layout:'fit',autoScroll:true,
        scope:this,
        monitorValid:true,
        items: [this.gridPool],
        buttons: [{
                text: __('Ok'),
                formBind:true,
                scope: this,
                handler:function(){this.fireEvent('updated');}
            },
            {
                text: __('Cancel'),
                scope:this,
                //handler:function(){this.ownerCt.close();}
                handler:function(){this.fireEvent('updated');}
            }]// end buttons
    });
    
};// end poolwin.GridForm.Main function

// define public methods
Ext.extend(poolwin.GridForm.Main, Ext.form.FormPanel, {
    // load data
    load: function(node) {
        this.gridPool.getStore().reload();
    }
});

</script>
