<?php


$js_grid = js_grid_info($network_tableMap);


/*
 * Default data to be inserted in DB
 */

//$default_model_values = array('default'=>'name','items'=>
//    array('name'=>'Change me...',
//                                      'server_id'=>$server_id,
//                                      'ip'=>'000.000.000.000',
//                                      'mask'=>'000.000.000.000'
//    )
//);

// $insert_model = js_insert_model($network_form,$default_model_values);


?>
<script>
    /*
     * Partial networkGrid
     */
    // shorthand alias
    Ext.namespace('Network');
    Network.Grid = function(){

        var store;

        return{
            init:function(){
                Ext.QuickTips.init();


                var cm = new Ext.grid.ColumnModel([<?php echo $js_grid['cm'] ?>]);

                
                var store_id = <?php echo json_encode($js_grid['pk']) ?>;
                var sort_field = store_id;
                var server_id = <?php echo $server_id ?>;               

                // create the Data Store
                store = new Ext.data.JsonStore({
                    proxy: new Ext.data.HttpProxy({
                        url: <?php echo json_encode(url_for('network/jsonGridPager')) ?>
                    }),
                    baseParams:{'sid':server_id},
                    id: store_id,
                    totalProperty: 'total',
                    root: 'data',
                    fields: [<?php echo $js_grid['ds'] ?>],
                    sortInfo: { field: sort_field,
                        direction: 'DESC' },
                    remoteSort: true
                });

                // create the editor grid
                var networkGrid = new Ext.grid.GridPanel({
                    store: store,
                    cm: cm,
                    border: false,
                    loadMask: {msg: 'Retrieving info...'},
                    viewConfig:{forceFit:true},                    
                    autoScroll:true,
                    title: 'Networks',
                    stripeRows:true,
                    clicksToEdit:1,
                    tbar: [{
                            text: 'Add NIC',
                            iconCls: 'icon-add',
                            url:<?php echo(json_encode(url_for('network/interfacesWin?sid=')))?>+server_id,
                            handler:View.clickHandler,
                            //handler:View.clickHandler,
                            scope:this
                        }// END Add button
                        ,
                        {
                            text: 'Remove NIC',
                            iconCls: 'icon-remove',
                            handler: function() {                                

                                var sm = networkGrid.getSelectionModel();
                                var sel = sm.getSelected();
                                if (sm.hasSelection()){
                                    Ext.Msg.show({
                                        title: 'Remove NIC',
                                        buttons: Ext.MessageBox.YESNOCANCEL,
                                        msg: 'Remove interface '+sel.data.Port+'?',
                                        fn: function(btn){
                                            if (btn == 'yes'){
                                                this.sendRemove(sel);
                                            }//END button==yes
                                        }// END fn
                                        ,scope:this
                                    }); //END Msg.show
                                };//END if
                            }//END handler Remove
                            ,scope:this
                        }// END Remove button
                    ],// END tbar
                    bbar : new Ext.PagingToolbar({
                        store: store,
                        displayInfo:true,
                        pageSize:10
                        ,
                        plugins:new Ext.ux.Andrie.pPageSize({comboCfg: {width: 50}})
                    }),
                    sm: new Ext.grid.RowSelectionModel({
                        singleSelect: true,
                        moveEditorOnEnter:false

                    })                    
                });//END networkGrid



                networkGrid.on({
                    afterlayout:{scope:this, single:true, fn:function() {
                            store.load({params:{start:0, limit:10}});
                        }}
                });

                return networkGrid;
            }//Fim init
            ,
            reload:function(){
                store.reload();
            },
            sendCreate:function(){
                var node_id = <?php echo $node_id ?>;
                var currentServer = nodesPanel.getSelectionModel().getSelectedNode();
                var serverName = currentServer.text;
                var network = {
                        'port':i,
                        'vlan':data['vlan'],
                        'mac':data['mac']};
                
                var conn = new Ext.data.Connection({
                listeners:{
                    // wait message.....
                    beforerequest:function(){
                        Ext.MessageBox.show({
                            title: 'Please wait',
                            msg: 'Processing interfaces...',
                            width:300,
                            wait:true,
                            modal: false
                        });
                    },// on request complete hide message
                    requestcomplete:function(){Ext.MessageBox.hide();}}
            });// end conn
            conn.request({
                  url: <?php echo json_encode(url_for('network/jsonCreate')) ?>,                
                params:{'nid':node_id,'server':serverName,'network': Ext.encode(network)},
                // params:{'networks': Ext.encode(network),'sid':server_id},
                scope: this,
                success: function(resp,options) {

                    var response = Ext.util.JSON.decode(resp.responseText);

                    Ext.ux.Logger.info(response['response']);

                    Network.InterfacesGrid.reload();

                    //close interfaces window
                    win.close();


                }
                ,
                failure: function(resp,opt) {

                    var response = Ext.util.JSON.decode(resp.responseText);
                    Ext.ux.Logger.error(response['error']);

                    Ext.Msg.show({title: 'Error',
                            buttons: Ext.MessageBox.OK,
                            msg: 'Unable to attach/detach interfaces ',
                            icon: Ext.MessageBox.ERROR});

                }
            }); // END Ajax request

            }
            ,
            sendRemove:function(row){                               

                var currentServer = nodesPanel.getSelectionModel().getSelectedNode();
                var serverName = currentServer.text;
                var node_id = <?php echo $node_id ?>;                
                var macaddr = row.data.Mac;               

                var conn = new Ext.data.Connection({
                    listeners:{
                        // wait message.....
                        beforerequest:function(){
                            Ext.MessageBox.show({
                                title: 'Please wait',
                                msg: 'Detaching interface...',
                                width:300,
                                wait:true,
                                modal: false
                            });
                        },// on request complete hide message
                        requestcomplete:function(){Ext.MessageBox.hide();}
                    }
                });// end conn

                conn.request({
                    url: <?php echo json_encode(url_for('network/jsonRemove'))?>,
                    params: {'nid': node_id,'server':serverName,'macaddr':macaddr},                
                    scope:this,
                    success: function(resp,opt){
                        var response = Ext.util.JSON.decode(resp.responseText);
                        Ext.ux.Logger.info(response['response']);
                        networkGrid.getStore().remove(row);
                    },
                    failure: function(resp,opt) {
                        var response = Ext.util.JSON.decode(resp.responseText);
                        Ext.ux.Logger.error(response['error']);

                        Ext.Msg.show({title: 'Error',
                            buttons: Ext.MessageBox.OK,
                            msg: 'Unable to detach interface with mac '+macaddr,
                            icon: Ext.MessageBox.ERROR});
                    }
                });// END Ajax request
                

            }
           


        }
    }();

    // Ext.onReady(Network.Grid.init, Network.Grid);
</script>