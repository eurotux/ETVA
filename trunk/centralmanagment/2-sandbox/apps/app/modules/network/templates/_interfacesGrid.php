<?php
// called in view/networks
// used in the Main>Networks tabs
// display grid containing server and networks info


$js_grid = js_grid_info($tableMap);


/*
 * Default data to be inserted in DB
 */

$default_model_values = array('default'=>'name','items'=>
    array('name'=>'Change me...',
        //   'server_id'=>$server_id,
                                      'ip'=>'000.000.000.000',
                                      'mask'=>'000.000.000.000'
    )
);



?>
<script>
    
/*
 * Partial networkGrid
 */
// shorthand alias
Ext.namespace('Network');
Network.InterfacesGrid = Ext.extend(Ext.grid.GridPanel, {            
        border:false,
        autoScroll:true,
        stripeRows: true
        ,loadMask: {msg: <?php echo json_encode(__('Retrieving data...')) ?>}
        ,initComponent:function(){

<?php

$url = json_encode(url_for('network/jsonGridNoPager',false));
$store_id = json_encode($js_grid['pk']);
?>

            // var gridUrl = ;
            var store_id = <?php echo $store_id ?>;
            var sort_field = store_id;
            var url = <?php echo $url ?>;

            this.cm = new Ext.grid.ColumnModel([
                {header:"Id",width:20,dataIndex:"Id",sortable:true},
                {header:"ServerId",dataIndex:"ServerId",sortable:true},
                {header:"ServerName",width:80,dataIndex:"ServerName",sortable:true},
                {header:"NodeName",width:80,dataIndex:"NodeName",sortable:true},
                {header:"Port",width:60,dataIndex:"Port",sortable:true},
                {header:"Ip",width:120,dataIndex:"Ip",sortable:true},
                {header:"Mask",width:120,dataIndex:"Mask",sortable:true},
                {header:"Mac",width:120,dataIndex:"Mac",sortable:true},
                {id:'Network',header:"Network",width:60,dataIndex:"Network",sortable:true}]);

            this.autoExpandColumn = 'Network';

            this.view = new Ext.grid.GroupingView({
                autoFill:true,
                emptyText: __('Empty!'),  //  emptyText Message
                forceFit:true,
                groupTextTpl: '{text} ({[values.rs.length]} {[values.rs.length > 1 ? __("Items") : "Item"]})'
            });

            this.store = new Ext.data.GroupingStore({
                url: url,
                method:'POST',
                baseParams: {'cid': this.cluster_id},
                reader: new Ext.data.JsonReader({

                    totalProperty: 'total',
                    root: 'data',
                    id: store_id,
                    fields: [
                        {name:"Id", mapping:'Id'},
                        {name:"ServerId", mapping:'ServerId'},
                        {name:"VmType", mapping:'VmType'},
                        {name:"ServerName",mapping:'ServerName'},
                        {name:"NodeId",mapping:'NodeId'},
                        {name:"NodeName",mapping:'NodeName'},
                        {name:"Port",mapping:'Port'},
                        {name:"Ip",mapping:'Ip'},
                        {name:"Mask",mapping:'Mask'},
                        {name:"Mac",mapping:'Mac'},
                        {name:"Network",mapping:'Vlan'},
                        {name:"Vm_state", mapping:'Vm_state'}]
                }),

                sortInfo:{field: 'NodeName', direction: "DESC"},
                remoteSort: false,
                groupField:'NodeName'
            });


            Network.InterfacesGrid.superclass.initComponent.call(this);



            this.on({rowcontextmenu:this.onRowContextMenu,
                        // load the store at the latest possible moment
                        afterlayout:{scope:this, single:true, fn:function() {
                        this.store.load();
                    }}
            });



        //    grid.on({show:function(){store.load();}});


        }//Fim init
        ,onRowContextMenu : function(grid,rowIndex, e){


            if(this.ctxRow){
                // this.ctxNode.ui.removeClass('x-node-ctx');
                this.ctxRow = null;
            }
            grid.getSelectionModel().selectRow(rowIndex);
            // e.stopEvent();
            //               // if(node.isLeaf()){ //open context menu only if node is a leaf
            this.ctxRow = grid.getView().getRow(rowIndex);
            this.ctxRecord = grid.getSelectionModel().getSelected();
            //alert("aki");

           // if(!this.menu){ // create context menu on first right click
                this.menu = new Ext.menu.Menu({
                    items: [
                        {
                            text:<?php echo json_encode(__('Manage network interfaces')) ?>
                            ,iconCls:'go-action'
                            ,url:<?php echo json_encode(url_for('network/Network_ManageInterfacesGrid')); ?>
                            ,call:'Network.ManageInterfacesGrid'
                            ,ref:'manageNet'
                            ,callback:function(item){
                                
                                var rec = grid.getSelectionModel().getSelected();
                                var server_id = rec.get('ServerId');
                                var server_name = rec.get('ServerName');
                                var server_type = rec.get('VmType');
                                
                                var managegrid = new Network.ManageInterfacesGrid({vm_type:server_type,server_name:server_name,server_id:server_id,loadMask:true,border:false});
                                managegrid.on('render',function(){this.store.load.defer(200,this.store);});


                                var win = new Ext.Window({
                                                title: String.format(<?php echo json_encode(__('Attach/detach network interfaces for server {0}')) ?>,server_name),
                                                width:700,
                                                height:300,
                                                iconCls: 'icon-window',
                                                //bodyStyle: 'padding:10px;',
                                                shim:false,
                                                border:true
                                                ,tools: [{
                                                    id:'help',
                                                    qtip: __('Help'),
                                                    handler:function(){
                                                        View.showHelp({
                                                            anchorid:'help-network-main',
                                                            autoLoad:{ params:'mod=network'},
                                                            title: <?php echo json_encode(__('Manage network interfaces Help')) ?>
                                                        });
                                                    }
                                                }]
                                                //resizable:false,
                                                //draggable:false,
                                                ,constrainHeader:true,
                                                layout: 'fit',
                                                modal:true,
                                                items:managegrid,
                                                buttons: [{
                                                        text: __('Save'),
                                                        handler: function(){

                                                            if(managegrid.isValid()) managegrid.save();
                                                            else
                                                                Ext.Msg.show({title: <?php echo json_encode(__('Error!')) ?>,
                                                                    buttons: Ext.MessageBox.OK,
                                                                    msg: <?php echo json_encode(__('Missing network interface data!')) ?>,
                                                                    icon: Ext.MessageBox.INFO});
                                                        }
                                                    },
                                                    {
                                                        text: __('Cancel'),
                                                        handler: function(){win.close();}
                                                    }
                                                ]// end buttons
                                                ,listeners:{
                                                    'onManageInterfacesSuccess':function(){
                                                        win.close();
                                                        grid.getStore().reload();
                                                    }}
                                            });
                                            win.show();                                
                            }
                            ,handler:View.loadComponent                            
                        },
                        {
                            iconCls:'go-action',
                            text:<?php echo json_encode(__('Remove network interface')) ?>,
                            scope: this,
                            ref:'rmIf',
                            handler:this.detachInterface
                        }
                    ]
                });

                this.menu.on('hide', this.onContextHide, this);
            if(this.ctxRecord.get('VmType')!='pv' && this.ctxRecord.get('Vm_state')=='running'){
                this.menu.manageNet.setDisabled(true);
                this.menu.manageNet.setTooltip({text: <?php echo json_encode(__('Server need to be stop to edit!')) ?>});
                this.menu.rmIf.setDisabled(true);
                this.menu.rmIf.setTooltip({text: <?php echo json_encode(__('Server need to be stop to edit!')) ?>});
            }

            // Stops the browser context menu from showing.
            e.stopEvent();
            this.menu.showAt(e.getXY());


        },
        onContextHide : function(){
            // prevent browser default context menu
            //          e.stopEvent();
            if(this.ctxRow){
                //    this.ctxNode.ui.removeClass('x-node-ctx');
                this.ctxRow = null;
            }
        }
        ,load:function(rec){
            var vlanName = __('All');

            if(rec){
                var query = {'vlan_id':rec.get('id')};
                vlanName = rec.get('name');
                this.store.baseParams = {'query': Ext.encode(query), 'cid': this.cluster_id};
                this.ownerCt.setTitle(String.format(<?php echo json_encode(__('List of {0} network interfaces')) ?>,vlanName));

            }else{
                this.store.baseParams = null;
                this.ownerCt.setTitle(<?php echo json_encode(__('List all network interfaces')) ?>);
            }
            this.store.load();
            

        },
        reload:function(){
            this.store.reload();
        },
        detachInterface:function(){

            var server_id = this.ctxRecord.get('ServerId');
            var server_name = this.ctxRecord.get('ServerName');
            var macaddr = this.ctxRecord.get('Mac');
            
            Ext.Msg.show({
                title: <?php echo json_encode(__('Remove network interface')) ?>,
                buttons: Ext.MessageBox.YESNOCANCEL,
                icon: Ext.MessageBox.QUESTION,
                msg: String.format(<?php echo json_encode(__('Remove network interface with mac {0} ?')) ?>,macaddr),
                scope:this,
                fn: function(btn){

                    if (btn == 'yes'){

                        var conn = new Ext.data.Connection();
                        conn.request({
                            url: <?php echo json_encode(url_for('network/jsonRemove'))?>,
                            params: {'sid': server_id,'macaddr':macaddr},
                            scope:this,
                            success: function(resp,opt) {
                                var response = Ext.util.JSON.decode(resp.responseText);
                                Ext.ux.Logger.info(response['agent'],response['response']);
                                this.store.reload();

                            },
                            failure: function(resp,opt) {
                                var response = Ext.util.JSON.decode(resp.responseText);

                                Ext.ux.Logger.error(response['agent'],response['error']);

                                Ext.Msg.show({
                                    title: String.format(<?php echo json_encode(__('Error {0}')) ?>,response['agent']),
                                    buttons: Ext.MessageBox.OK,
                                    msg: String.format(<?php echo json_encode(__('Unable to detach network interface {0} from {1}!')) ?>+'<br> {2}',macaddr,server_name,response['info']),
                                    icon: Ext.MessageBox.ERROR});
                            }
                        });//END Ajax request

                    }//END button==yes
                }// END fn
            }); //END Msg.show

        }

});

    // Ext.onReady(Network.Grid.init, Network.Grid);
</script>
