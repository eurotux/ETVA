<script>
Ext.ns('Server.Snapshots');

Server.Snapshots.Panel = Ext.extend(Ext.Panel, {
    border:true
    ,monitorValid:true
    ,autoScroll:true
    ,layout:'fit'
    ,width:370
    ,labelWidth:140
    ,initComponent:function() {
        var myparams = {'id':this.server_id, 'level': this.level};
        var myurl = <?php echo json_encode(url_for('server/jsonListSnapshots')) ?>;

        this.gridListSnapshots = new Ext.grid.GridPanel({
                                        id: 'grid-list-snapshots',
                                        autoScroll:true,
                                        layout:'fit',
                                        border: false,
                                        sm: new Ext.grid.RowSelectionModel({singleSelect:true}),
                                        viewConfig: {
                                            forceFit: true,
                                        },
                                        colModel: new Ext.grid.ColumnModel({
                                                        columns: [
                                                            {header: __('Name'), dataIndex: 'name'},
                                                            {header: __('Create Time'), dataIndex: 'createtime'},
                                                            {header: __('State'), dataIndex: 'state' }
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
                                                        ,{name: 'name', mapping: 'name', type: 'string'}
                                                        ,{name: 'createtime', mapping: 'createtime', type:'string'}
                                                        ,{name: 'state', mapping: 'state', type:'string'}
                                                    ]
                                                })
                                        ,tbar: [
                                                {
                                                    text: <?php echo json_encode(__('Create snapshot')) ?>,
                                                    ref:'btn_create_snapshot',
                                                    iconCls: 'go-action',
                                                    url:<?php echo(json_encode(url_for('server/createsnapshot')))?>,
                                                    call:'Server.CreateSnapshot',
                                                    callback:function(btn,e,obj){
                                                        var window = new Server.CreateSnapshot.Window({
                                                                                                title: String.format(<?php echo json_encode(__('Create snapshot for {0}')) ?>,obj.server_name), 'server_id': obj.server_id, 'server_name': obj.server_name});
                                                        window.on({//'show':function(){window.loadData({id:this.server_id});},
                                                                    'onCancel':function(){window.close();}
                                                                    ,'onSave':function(){window.close();
                                                                        Ext.getCmp('grid-list-snapshots').getStore().reload();
                                                                    }
                                                                    ,scope:this
                                                        });
                                                        window.show();

                                                    }
                                                    ,handler: function(btn,e){View.loadComponent(btn,e, {server_id:this.server_id, server_name:this.server_name});}
                                                    ,scope: this
                                                }
                                                ]
                                        ,tools: [{
                                                    id:'refresh',
                                                    handler: function(){
                                                        this.gridListSnapshots.getStore().reload();
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
                                                            id:'snapshot-revert',
                                                            iconCls:'go-action',
                                                            text: <?php echo json_encode(__('Revert to snapshot')) ?>,
                                                            scope: this,
                                                            handler:function(b,e){

                                                                var snapshot = this.ctxRecord.get('name');
                                                                var state = this.ctxRecord.get('state');
                                                                var msg = String.format(<?php echo json_encode(__('Do you want revert server state to snapshot {0}?')) ?>,snapshot);

                                                                if( state == 'shutoff' ){
                                                                    msg = String.format(<?php echo json_encode(__('Do you want revert server state to snapshot {0}? Attention this will shutoff the server.')) ?>,snapshot);
                                                                }

                                                                Ext.MessageBox.show({
                                                                        title: <?php echo json_encode(__('Revert to snapshot')) ?>,
                                                                        msg: msg,
                                                                        buttons: Ext.MessageBox.YESNOCANCEL,
                                                                        fn: function(btn){

                                                                            if(btn=='yes'){
                                                                                var myparams = { 'id':this.server_id, 'snapshot':snapshot };

                                                                                var conn = new Ext.data.Connection({
                                                                                    listeners:{
                                                                                        beforerequest:function(){
                                                                                            Ext.MessageBox.show({
                                                                                                title: <?php echo json_encode(__('Please wait...')) ?>,
                                                                                                msg: <?php echo json_encode(__('Revert snapshot...')) ?>,
                                                                                                width:300,
                                                                                                wait:true
                                                                                            });

                                                                                        },
                                                                                        requestcomplete:function(){Ext.MessageBox.hide();}
                                                                                        ,requestexception:function(c,r,o){
                                                                                                            Ext.MessageBox.hide();
                                                                                                            Ext.Ajax.fireEvent('requestexception',c,r,o);}

                                                                                    }
                                                                                });

                                                                                conn.request({
                                                                                    url: <?php echo json_encode(url_for('server/jsonRevertSnapshot'))?>,
                                                                                    params: myparams,
                                                                                    scope:this,
                                                                                    success: function(resp,opt){

                                                                                        var response = Ext.util.JSON.decode(resp.responseText);
                                                                                        var okmsg = String.format(<?php echo json_encode(__('Virtual machine snapshot reverted successfully.')) ?>);
                                                                                        Ext.ux.Logger.info(response['agent'],okmsg);
                                                                                        Ext.getCmp('grid-list-snapshots').getStore().reload();
                                                                                    },
                                                                                    failure: function(resp,opt) {
                                                                                        var response = Ext.util.JSON.decode(resp.responseText);
                                                                                        Ext.Msg.show({
                                                                                            title: String.format(<?php echo json_encode(__('Error {0}')) ?>,response['agent']),
                                                                                            buttons: Ext.MessageBox.OK,
                                                                                            msg: String.format(<?php echo json_encode(__('Unable to revert snapshot')) ?>)+'<br>'+response['info'],
                                                                                            icon: Ext.MessageBox.ERROR});
                                                                                    }
                                                                                });// END Ajax request

                                                                            }
                                                                        },
                                                                        scope:this,
                                                                        icon: Ext.MessageBox.QUESTION
                                                                });
                                                            }
                                                        },{
                                                            id:'snapshot-remove',
                                                            iconCls:'go-action',
                                                            text: <?php echo json_encode(__('Remove snapshot')) ?>,
                                                            scope: this,
                                                            handler:function(b,e){

                                                                var snapshot = this.ctxRecord.get('name');

                                                                Ext.MessageBox.show({
                                                                        title: <?php echo json_encode(__('Remove snapshot')) ?>,
                                                                        msg: String.format(<?php echo json_encode(__('Do you want remove {0} snapshot?')) ?>,snapshot),
                                                                        buttons: Ext.MessageBox.YESNOCANCEL,
                                                                        fn: function(btn){

                                                                            if(btn=='yes'){
                                                                                var myparams = { 'id':this.server_id, 'snapshot':snapshot };

                                                                                var conn = new Ext.data.Connection({
                                                                                    listeners:{
                                                                                        beforerequest:function(){
                                                                                            Ext.MessageBox.show({
                                                                                                title: <?php echo json_encode(__('Please wait...')) ?>,
                                                                                                msg: <?php echo json_encode(__('Remove snapshot...')) ?>,
                                                                                                width:300,
                                                                                                wait:true
                                                                                            });

                                                                                        },
                                                                                        requestcomplete:function(){Ext.MessageBox.hide();}
                                                                                        ,requestexception:function(c,r,o){
                                                                                                            Ext.MessageBox.hide();
                                                                                                            Ext.Ajax.fireEvent('requestexception',c,r,o);}

                                                                                    }
                                                                                });

                                                                                conn.request({
                                                                                    url: <?php echo json_encode(url_for('server/jsonRemoveSnapshot'))?>,
                                                                                    params: myparams,
                                                                                    scope:this,
                                                                                    success: function(resp,opt){

                                                                                        var response = Ext.util.JSON.decode(resp.responseText);
                                                                                        var okmsg = String.format(<?php echo json_encode(__('Virtual machine snapshot removed successfully.')) ?>);
                                                                                        Ext.ux.Logger.info(response['agent'],okmsg);
                                                                                        Ext.getCmp('grid-list-snapshots').getStore().reload();
                                                                                    },
                                                                                    failure: function(resp,opt) {
                                                                                        var response = Ext.util.JSON.decode(resp.responseText);
                                                                                        Ext.Msg.show({
                                                                                            title: String.format(<?php echo json_encode(__('Error {0}')) ?>,response['agent']),
                                                                                            buttons: Ext.MessageBox.OK,
                                                                                            msg: String.format(<?php echo json_encode(__('Unable to remove snapshot')) ?>)+'<br>'+response['info'],
                                                                                            icon: Ext.MessageBox.ERROR});
                                                                                    }
                                                                                });// END Ajax request

                                                                            }
                                                                        },
                                                                        scope:this,
                                                                        icon: Ext.MessageBox.QUESTION
                                                                });
                                                            }
                                                        },{
                                                            id:'snapshot-download',
                                                            iconCls:'go-action',
                                                            text: <?php echo json_encode(__('Download backup of snapshot')) ?>,
                                                            scope: this,
                                                            handler:function(b,e){

                                                                var snapshot = this.ctxRecord.get('name');
                                                                var msg = String.format(<?php echo json_encode(__('Do you want download the backup of snapshot {0} of server {1}?')) ?>,snapshot,this.server_name);

                                                                Ext.MessageBox.show({
                                                                        title: <?php echo json_encode(__('Download backup of snapshot')) ?>,
                                                                        msg: msg,
                                                                        buttons: Ext.MessageBox.YESNOCANCEL,
                                                                        fn: function(btn){

                                                                            if(btn=='yes'){
                                                                                var myparams = { 'id':this.server_id, 'snapshot':snapshot };
                                                                                var send_data = new Object();

                                                                                send_data['sid'] = this.server_id;
                                                                                send_data['snapshot'] = snapshot;

                                                                                var url = <?php echo json_encode(url_for('ovf/OvfDownload'))?> + '?sid=' + send_data['sid'] + '&snapshot='+send_data['snapshot'];

                                                                                var export_iframe = Ext.getCmp('ovf-export-frame');

                                                                                var server_name = this.server_name;

                                                                                if(!export_iframe){
                                                                                    export_iframe = new Ext.ux.ManagedIFrame.Window({id:'ovf-export-frame',defaultSrc:url,
                                                                                                        listeners:{
                                                                                                            'domready':function(frameEl){
                                                                                                                var doc = frameEl.getFrameDocument();
                                                                                                                var response = doc.body.innerHTML;

                                                                                                                Ext.MessageBox.hide();
                                                                                                                if(response){
                                                                                                                    Ext.Msg.show({
                                                                                                                        title: String.format(<?php echo json_encode(__('Error!')) ?>),
                                                                                                                        buttons: Ext.MessageBox.OK,
                                                                                                                        msg: doc.body.innerHTML,
                                                                                                                        icon: Ext.MessageBox.ERROR});            
                                                                                                                } else {
                                                                                                                    Ext.Msg.show({
                                                                                                                        title: <?php echo json_encode(__('Download backup of snapshot')) ?>,
                                                                                                                        buttons: Ext.MessageBox.OK,
                                                                                                                        msg: String.format(<?php echo json_encode(__('Backup of snapshot {0} of server {1} downloaded with success.')) ?>,snapshot,server_name),
                                                                                                                        icon: Ext.MessageBox.INFO});
                                                                                                                }
                                                                                                            },
                                                                                                            'hide':function(){
                                                                                                                //Ext.getCmp('ovf-export-window').fireEvent('onSave');
                                                                                                            }
                                                                                                        }
                                                                                    });
                                                                                }        
                                                                                        
                                                                                export_iframe.setSrc(url);

                                                                                export_iframe.show();
                                                                                export_iframe.hide();

                                                                                Ext.MessageBox.show({
                                                                                    title: <?php echo json_encode(__('Please wait...')) ?>,
                                                                                    msg: <?php echo json_encode(__('Downloading backup of snapshot...')) ?>,
                                                                                    width:300,
                                                                                    wait:true
                                                                                });
                                                                            }

                                                                        },
                                                                        scope:this,
                                                                        icon: Ext.MessageBox.QUESTION
                                                                    });
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
                this.gridListSnapshots.on('rowcontextmenu', this.gridListSnapshots.onRowContextMenu,this);

                this.items = [
                    /*{xtype:'hidden',name:'id'}
                    ,{xtype:'textfield', fieldLabel: <?php echo json_encode(__('Virtual server name')) ?>,readOnly:true, allowBlank:false, name:'name'},*/
                    this.gridListSnapshots
                ];
                this.buttons = [
                            {
                                text:__('Cancel'),
                                scope:this,
                                handler:function(){(this.ownerCt).close()}
                            }];
                Server.Snapshots.Panel.superclass.initComponent.call(this);

            }
            ,loadRecord:function(rec){
                this.gridListSnapshots.getStore().reload();
            }
        });


        Server.Snapshots.Window = function(config) {

            Ext.apply(this,config);

            Server.Snapshots.Window.superclass.constructor.call(this, { 
                width:430,
                height: 320, 
                modal:true,
                bodyStyle:'padding:3px;',
                layout:'fit',autoScroll:true,
                items:[ new Server.Snapshots.Panel({server_id:this.server_id, server_name:this.server_name})]
            });
        };


        Ext.extend(Server.Snapshots.Window, Ext.Window,{
            tools:[{id:'help', qtip: __('Help'),handler:function(){View.showHelp({anchorid:'help-vmachine-snapshots',autoLoad:{ params:'mod=server'},title: <?php echo json_encode(__('Snapshots Server Help')) ?>});}}],
            loadData:function(src){
                this.items.get(0).loadRecord(src);        
            }        
        });

        </script>
