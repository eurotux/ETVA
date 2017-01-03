<?php

$extraDSfields = array('ServerName');
$js_grid = js_grid_info($network_tableMap,false,$extraDSfields);


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

    return{
        init:function(config){
            Ext.QuickTips.init();

            Ext.apply(this,config);

            var cm = new Ext.grid.ColumnModel([<?php echo $js_grid['cm'] ?>]);


            var store_id = <?php echo json_encode($js_grid['pk']) ?>;
            var sort_field = store_id;
            var server_id = this.server_id;

            // create the Data Store
            var store = new Ext.data.JsonStore({
                proxy: new Ext.data.HttpProxy({
                    url: <?php echo json_encode(url_for('network/jsonGridPager')) ?>
                }),
                baseParams:{'sid':server_id},
                id: store_id,
                totalProperty: 'total',
                root: 'data',
                fields: [<?php echo $js_grid['ds'] ?>],
                sortInfo: { field: 'Port',
                    direction: 'ASC' },
                remoteSort: true
            });

            // create the editor grid
            var networkGrid = new Ext.grid.GridPanel({
                store: store,
                cm: cm,
                border: false,
                loadMask: {msg: <?php echo json_encode(__('Retrieving data...')) ?>},
                viewConfig:{forceFit:true},
                autoScroll:true,
                title: <?php echo json_encode(__('Network interfaces')) ?>,
                stripeRows:true,
                clicksToEdit:1,
                tbar: [
                    {
                        text: <?php echo json_encode(__('Manage network interfaces')) ?>,
                        iconCls: 'icon-add',
                        handler:function(){
                            var server_name = ((networkGrid.ownerCt).ownerCt).title;

                            Ext.getBody().mask(<?php echo json_encode(__('Retrieving data...')) ?>);

                            Ext.Ajax.request({
                                url:<?php echo(json_encode(url_for('network/Network_ManageInterfacesGrid')))?>,
                                method: 'GET',
                                success:function(response){
                                    Ext.get('dynPageContainer').update(response.responseText,true,function(){
                                        Ext.getBody().unmask();
                                        var grid = new Network.ManageInterfacesGrid({server_id:server_id});

                                        var win = new Ext.Window({
                                            title: String.format(<?php echo json_encode(__('Attach/detach network interfaces for server {0}')) ?>,server_name),
                                            width:600,
                                            height:300,
                                            iconCls: 'icon-window',
                                            bodyStyle: 'padding:10px;',
                                            shim:false,
                                            border:true,
                                            resizable:false,
                                            draggable:false,
                                            constrainHeader:true,
                                            layout: 'fit',
                                            modal:true,
                                            items:grid,
                                            buttons: [{
                                                    text: __('Save'),
                                                    handler: function(){

                                                        if(grid.isValid()) grid.save();
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
                                                    store.reload();
                                                }}
                                        });
                                        win.show();
                                    });
                                }
                            });// end request
                        }
                    }
                    ,
                    {
                        text: <?php echo json_encode(__('Remove interface'))?>,
                        iconCls: 'icon-remove',
                        handler: function() {

                            var sm = networkGrid.getSelectionModel();
                            var sel = sm.getSelected();
                            if (sm.hasSelection()){
                                Ext.Msg.show({
                                    title: <?php echo json_encode(__('Remove interface'))?>,
                                    buttons: Ext.MessageBox.YESNO,
                                    icon: Ext.MessageBox.QUESTION,
                                    msg: String.format(<?php echo json_encode(__('Remove network interface with mac {0} ?')) ?>,sel.data.Mac),
                                    fn: function(btn){
                                        if (btn == 'yes'){
                                            this.sendRemove(networkGrid,sel);
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

            networkGrid.on('refresh', function(){
                store.load({params:{start:0, limit:10}});
            });

            networkGrid.on('activate', function(){networkGrid.fireEvent('refresh');});

            return networkGrid;
        }//Fim init                
        ,
        sendRemove:function(grid,row){

            var server_id = row.data.ServerId;
            var server_name = row.data.ServerName;
            var macaddr = row.data.Mac;

            var conn = new Ext.data.Connection({
                listeners:{
                    // wait message.....
                    beforerequest:function(){
                        Ext.MessageBox.show({
                            title: <?php echo json_encode(__('Please wait...')) ?>,
                            msg: <?php echo json_encode(__('Removing network interface...')) ?>,
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
                url: <?php echo json_encode(url_for('network/jsonRemove'))?>,
                params: {'sid': server_id,'macaddr':macaddr},
                scope:this,
                success: function(resp,opt){
                    var response = Ext.util.JSON.decode(resp.responseText);
                    Ext.ux.Logger.info(response['agent'], response['response']);
                    grid.getStore().reload();
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
            });// END Ajax request


        }



    }
}();

    // Ext.onReady(Network.Grid.init, Network.Grid);
</script>