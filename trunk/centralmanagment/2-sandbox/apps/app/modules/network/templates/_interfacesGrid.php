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
    Network.InterfacesGrid = function(){
        var store;
        var grid;
        return{
            init:function(){
                Ext.QuickTips.init();
               
               

<?php
$url = json_encode(url_for('network/jsonGridNoPager',false));
$store_id = json_encode($js_grid['pk']);
?>

                // var gridUrl = ;
                var store_id = <?php echo $store_id ?>;
                var sort_field = store_id;
                var url = <?php echo $url ?>;               


                var cm = new Ext.grid.ColumnModel([
                    {header:"Id",width:20,dataIndex:"Id",sortable:true},
                    {header:"ServerId",dataIndex:"ServerId",sortable:true},
                    {header:"ServerName",width:80,dataIndex:"ServerName",sortable:true},
                    {header:"NodeName",width:80,dataIndex:"NodeName",sortable:true},
                    {header:"Port",width:60,dataIndex:"Port",sortable:true},
                    {header:"Ip",width:120,dataIndex:"Ip",sortable:true},
                    {header:"Mask",width:120,dataIndex:"Mask",sortable:true},
                    {header:"Mac",width:120,dataIndex:"Mac",sortable:true},
                    {id:'Vlan',header:"Vlan",width:60,dataIndex:"Vlan",sortable:true}]);


                store = new Ext.data.GroupingStore({
                    url: url,
                    reader: new Ext.data.JsonReader({

                        totalProperty: 'total',
                        root: 'data',
                        id: store_id,
                        fields: [
                            {name:"Id", mapping:'Id'},
                            {name:"ServerId", mapping:'ServerId'},
                            {name:"ServerName",mapping:'ServerName'},
                            {name:"NodeId",mapping:'NodeId'},
                            {name:"NodeName",mapping:'NodeName'},
                            {name:"Port",mapping:'Port'},
                            {name:"Ip",mapping:'Ip'},
                            {name:"Mask",mapping:'Mask'},
                            {name:"Mac",mapping:'Mac'},
                            {name:"Vlan",mapping:'Vlan'}]

                    }),
                    
                    sortInfo:{field: 'NodeName', direction: "DESC"},
                    remoteSort: false,
                    groupField:'NodeName'
                });

                
                store.load();
                

                grid = new Ext.grid.GridPanel({
                    store: store,
                    border:false,
                    cm:cm,               
                    autoScroll:true,
                    autoExpandColumn: 'Vlan',
                    view: new Ext.grid.GroupingView({
                        autoFill:true,
                        forceFit:true,
                        groupTextTpl: '{text} ({[values.rs.length]} {[values.rs.length > 1 ? "Items" : "Item"]})'
                    }),

                    stripeRows: true,
                    //    autoExpandColumn: 'Vlan',

//                    layout:'fit',
                    // items:[Network.Grid.init()],

                    loadMask: {msg: 'Retrieving info...'},
                    //  iconCls: 'icon-grid'
                    // renderTo: document.body
                    bbar:['->',{
                            text: 'Refresh',
                            xtype: 'button',
                            tooltip: 'refresh',
                            iconCls: 'x-tbar-loading',
                            scope:this,
                            handler: function(button,event)
                            {
                                button.addClass('x-item-disabled');


                                store.reload({
                                    callback:function(){button.removeClass('x-item-disabled');}});
                            }
                        }]
                });


                grid.on('rowcontextmenu', this.onRowContextMenu,this);


                return grid;
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
                
                // alert(this.ctxRecord.id);

               // if(!this.menu){ // create context menu on first right click
                    this.menu = new Ext.menu.Menu({                        
                        items: [
                            {
                                iconCls:'go-action',
                                text:'Manage interfaces',
                                url:<?php echo(json_encode(url_for('network/interfacesWin?sid=')))?>+this.ctxRecord.get('ServerId'),
                                handler:View.clickHandler
                            },
                            {                              
                                iconCls:'go-action',
                                text:'Remove interface',
                                scope: this,                                
                                handler:this.detachInterface
                            }
                        ]
                    });

                    this.menu.on('hide', this.onContextHide, this);

                

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
            ,load:function(vlanName){

                var query = {'vlan':vlanName};
                if(vlanName) store.baseParams = {'query': Ext.encode(query)};
                else store.baseParams = null;

                store.load();               

                if(!vlanName) vlanName = 'All';                
                Ext.getCmp('interfaces-grid').setTitle('Interfaces - '+vlanName);

            },
            reload:function(){
                store.reload();
            },
            detachInterface:function(){

                var ctxRecord = this.ctxRecord;
                var node_id = ctxRecord.get('NodeId');
                var serverName = ctxRecord.get('ServerName');
                var macaddr = ctxRecord.get('Mac');



                var conn = new Ext.data.Connection();
                conn.request({
                    url: <?php echo json_encode(url_for('network/jsonRemove'))?>,
                    params: {'nid': node_id,'server':serverName,'macaddr':macaddr},
                    scope:this,
                    success: function(resp,opt) {
                        var response = Ext.util.JSON.decode(resp.responseText);

                        Ext.ux.Logger.info(response['response']);
                        
                    },
                    failure: function(resp,opt) {
                        var response = Ext.util.JSON.decode(resp.responseText);

                        Ext.ux.Logger.error(response['error']);

                        Ext.Msg.show({title: 'Error',
                            buttons: Ext.MessageBox.OK,
                            msg: 'Unable to detach interface '+macaddr+' from '+serverName,
                            icon: Ext.MessageBox.ERROR});
                    }
                });//END Ajax request
                
            }            


        }
    }();

    // Ext.onReady(Network.Grid.init, Network.Grid);
</script>