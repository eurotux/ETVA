<script>

Ext.ns("pvwin.scanForm");

pvwin.scanForm.Main = function(node_id, level) {

    this.level = level;
    this.node_id = node_id;    

    var myparams = {};
    //var myurl = <?php echo json_encode(url_for('physicalvol/jsonListSyncDiskDevices'))?>;
    var myurl = <?php echo json_encode(url_for('physicalvol/jsonScanDiskDevices'))?>;

    if(this.level == 'cluster'){
        myparams = {'cid':this.node_id, 'level': this.level};
    }else if(this.level == 'node'){
        myparams = {'nid':this.node_id, 'level': this.level};
    }
    myparams['notregistered'] = true;
    this.gridDiskDevices = new Ext.grid.GridPanel({
                                    id: 'grid-pv-scan',
                                    layout:'fit',
                                    border: false,
                                    sm: new Ext.grid.RowSelectionModel({singleSelect:true}),
                                    viewConfig: {
                                        forceFit: true,
                                        getRowClass: function(record,index){
                                                        if( record.get('inconsistent') == 'true' ){
                                                            return 'scan-pv-inconsistent';
                                                        } else if( record.get('registered') == 'true' ){
                                                            return 'scan-pv-registered';
                                                        }
                                        }
                                    },
                                    colModel: new Ext.grid.ColumnModel({
                                                    columns: [
                                                        {id: 'uuid', header: __('Uuid'), dataIndex: 'uuid'},
                                                        {header: __('Type'), dataIndex: 'type'},
                                                        {header: __('Device'), dataIndex: 'device'},
                                                        {header: __('Size'), dataIndex: 'size', renderer: Ext.util.Format.fileSize }
                                                        /*,{header: __('Registered'), dataIndex: 'registered'},
                                                        {header: __('Inconsistent'), dataIndex: 'inconsistent'}*/
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
                                                    ,{name: 'uuid', mapping: 'uuid', uuid: 'string'}
                                                    ,{name: 'type', mapping: 'type', type: 'string'}
                                                    ,{name: 'device', mapping: 'device', type:'string'}
                                                    ,{name: 'size', mapping: 'size', type:'int'}
                                                    ,{name: 'registered', mapping: 'registered', type:'string'}
                                                    ,{name: 'inconsistent', mapping: 'inconsistent', type:'string'}
                                                ]
                                            })
                                    ,tbar: [
                                            {
                                                text: <?php echo json_encode(__('Scan physical devices')) ?>,
                                                iconCls: 'go-action',
                                                handler: function(){
                                                    var conn = new Ext.data.Connection({
                                                                    listeners:{
                                                                        // wait message.....
                                                                        beforerequest:function(){

                                                                            Ext.MessageBox.show({
                                                                                title: <?php echo json_encode(__('Please wait...')) ?>,
                                                                                msg: <?php echo json_encode(__('Scan physical devices...')) ?>,
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

                                                    var scanparams = myparams;
                                                    //scanparams['scan'] = true;
                                                    scanparams['force'] = true;
                                                    conn.request({
                                                        url: <?php echo json_encode(url_for('physicalvol/jsonScanDiskDevices'))?>,
                                                        params: scanparams,
                                                        scope:this,
                                                        success: function(resp,opt){
                                                            this.gridDiskDevices.getStore().reload();
                                                        },
                                                        failure: function(resp,opt) {
                                                            var response = Ext.util.JSON.decode(resp.responseText);
                                                            
                                                            if(response)
                                                            {
                                                                Ext.Msg.show({
                                                                    title: String.format(<?php echo json_encode(__('Error {0}')) ?>,response['agent']),
                                                                    buttons: Ext.MessageBox.OK,
                                                                    msg: String.format(<?php echo json_encode(__('Unable to scan physical devices {0}!')) ?>)+'<br>'+response['info'],
                                                                    icon: Ext.MessageBox.ERROR});
                                                            }
                                                        }
                                                    });// END Ajax request
                                                }
                                                ,scope: this
                                            }
                                            ]
                                    ,tools: [{
                                                id:'refresh',
                                                handler: function(){
                                                    this.gridDiskDevices.getStore().reload();
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
                                                        id:'pv-add',
                                                        iconCls:'go-action',
                                                        text: <?php echo json_encode(__('Add physical device')) ?>,
                                                        scope: this,
                                                        handler:function(b,e){
                                                            var conn = new Ext.data.Connection({
                                                                            listeners:{
                                                                                // wait message.....
                                                                                beforerequest:function(){

                                                                                    Ext.MessageBox.show({
                                                                                        title: <?php echo json_encode(__('Please wait...')) ?>,
                                                                                        msg: <?php echo json_encode(__('Register physical device...')) ?>,
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

                                                            var params = myparams;
                                                            physicalvolume = this.ctxRecord.data;
                                                            params['physicalvolume'] = Ext.encode(physicalvolume);
                                                            if( this.ctxRecord.get('uuid') )
                                                                params['uuid'] = this.ctxRecord.get('uuid');
                                                            if( this.ctxRecord.get('device') )
                                                                params['device'] = this.ctxRecord.get('device');
                                                            
                                                            conn.request({
                                                                url: <?php echo json_encode(url_for('physicalvol/jsonRegister'))?>,
                                                                params: params,
                                                                scope:this,
                                                                success: function(resp,opt){
                                                                    var response = Ext.util.JSON.decode(resp.responseText);
                                                                    Ext.ux.Logger.info(response['agent'],response['response']);                    
                                                                    //this.fireEvent('updated');
                                                                    this.gridDiskDevices.getStore().reload();
                                                                },
                                                                failure: function(resp,opt) {
                                                                    var response = Ext.util.JSON.decode(resp.responseText);
                                                                    
                                                                    if(response)
                                                                    {
                                                                        Ext.Msg.show({
                                                                            title: String.format(<?php echo json_encode(__('Error {0}')) ?>,response['agent']),
                                                                            buttons: Ext.MessageBox.OK,
                                                                            msg: String.format(<?php echo json_encode(__('Unable to registe physical device {0}!')) ?>,params['device'])+'<br>'+response['info'],
                                                                            icon: Ext.MessageBox.ERROR});
                                                                    }
                                                                    this.gridDiskDevices.getStore().reload();
                                                                }
                                                            });// END Ajax request
                                                        }
                                                    },{
                                                        id:'pv-remove',
                                                        iconCls:'go-action',
                                                        text: <?php echo json_encode(__('Remove physical device')) ?>,
                                                        scope: this,
                                                        //handler:this.pvremove
                                                        handler:function(){
                                                            var conn = new Ext.data.Connection({
                                                                            listeners:{
                                                                                // wait message.....
                                                                                beforerequest:function(){

                                                                                    Ext.MessageBox.show({
                                                                                        title: <?php echo json_encode(__('Please wait...')) ?>,
                                                                                        msg: <?php echo json_encode(__('Unregister physical device...')) ?>,
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

                                                            var params = myparams;
                                                            if( this.ctxRecord.get('uuid') )
                                                                params['uuid'] = this.ctxRecord.get('uuid');
                                                            if( this.ctxRecord.get('device') )
                                                                params['device'] = this.ctxRecord.get('device');
                                                            
                                                            conn.request({
                                                                url: <?php echo json_encode(url_for('physicalvol/jsonUnregister'))?>,
                                                                params: params,
                                                                scope:this,
                                                                success: function(resp,opt){
                                                                    var response = Ext.util.JSON.decode(resp.responseText);
                                                                    Ext.ux.Logger.info(response['agent'],response['response']);                    
                                                                    //this.fireEvent('updated');
                                                                    this.gridDiskDevices.getStore().reload();
                                                                },
                                                                failure: function(resp,opt) {
                                                                    var response = Ext.util.JSON.decode(resp.responseText);
                                                                    
                                                                    if(response)
                                                                    {
                                                                        Ext.Msg.show({
                                                                            title: String.format(<?php echo json_encode(__('Error {0}')) ?>,response['agent']),
                                                                            buttons: Ext.MessageBox.OK,
                                                                            msg: String.format(<?php echo json_encode(__('Unable to unregiste physical device {0}!')) ?>,params['device'])+'<br>'+response['info'],
                                                                            icon: Ext.MessageBox.ERROR});
                                                                    }
                                                                    this.gridDiskDevices.getStore().reload();
                                                                }
                                                            });// END Ajax request
                                                        }
                                                    }]
                                            });// end menu

                                            //this.menu.on('hide', this.onRowContextHide, this);
                                        }

                                        if(this.ctxRecord.get('registered')=='true'){
                                            this.menu.items.get('pv-add').setDisabled(true);
                                            this.menu.items.get('pv-remove').setDisabled(false);
                                            //this.menu.items.get('pv-add').setTooltip({text: <?php echo json_encode(__('Server need to be stop to edit!')) ?>});
                                        } else {
                                            this.menu.items.get('pv-add').setDisabled(false);
                                            this.menu.items.get('pv-remove').setDisabled(true);
                                        }

                                        // Stops the browser context menu from showing.
                                        //e.stopEvent();
                                        this.menu.showAt(e.getXY());
                                        e.preventDefault();
                                    }
                                    /*,onRowContextHide : function(){
                                        // prevent browser default context menu
                                        //          e.stopEvent();
                                        if(this.ctxRow){
                                            //    this.ctxNode.ui.removeClass('x-node-ctx');
                                            this.ctxRow = null;
                                        }
                                    }*/
                            });
    // on context click call onRowContextMenu
    this.gridDiskDevices.on('rowcontextmenu', this.gridDiskDevices.onRowContextMenu,this);


    // define window and pop-up - render formPanel
    //var centerPanel = new Ext.form.FormPanel({
    pvwin.scanForm.Main.superclass.constructor.call(this, {        
        layout:'fit',autoScroll:true,
        scope:this,
        monitorValid:true,
        items: [this.gridDiskDevices],
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
    
};// end pvwin.scanForm.Main function

// define public methods
Ext.extend(pvwin.scanForm.Main, Ext.form.FormPanel, {
    // load data
    load: function(node) {
        this.gridDiskDevices.getStore().reload();
    }
});

</script>
