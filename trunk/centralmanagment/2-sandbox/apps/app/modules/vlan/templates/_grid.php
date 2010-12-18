<?php

$js_grid = js_grid_info($tableMap);


?>
<script>
    /*
     * Partial vlanGrid
     */
    // shorthand alias
    Ext.namespace('Vlan');
    Vlan.Grid = function(){
        var grid;
        var store;
        return{
            init:function(){
                Ext.QuickTips.init();
                                

                var cm = new Ext.grid.ColumnModel([
                    {header: "Vlan Id", width: 15, sortable: true, dataIndex: 'Id'},
                    {header: "Vlan Name", width: 155, sortable: true, dataIndex: 'Name'}]);


<?php
$url = json_encode(url_for('vlan/jsonList'));
$store_id = json_encode($js_grid['pk']);
?>

                var gridUrl = <?php echo $url ?>;
                var store_id = <?php echo $store_id ?>;
                var sort_field = store_id;
                var httpProxy = new Ext.data.HttpProxy({url: gridUrl});               

                // create the Data Store
                store = new Ext.data.JsonStore({
                    proxy: httpProxy,
                    id: store_id,
                    totalProperty: 'total',
                    root: 'data',
                    fields: [<?php echo $js_grid['ds'] ?>],
                    sortInfo: { field: sort_field,
                        direction: 'DESC' },
                    remoteSort: false
                });

                store.load({params:{start:0, limit:10}});


                // create the grid
                grid = new Ext.grid.GridPanel({
                    store: store,
                    cm: cm,
                    border: false,
                    //  height:200,
                    loadMask: {msg: 'Retrieving info...'},
                    viewConfig:{forceFit:true},
                    //  id:'vlan-grid',
                    autoScroll:true,
                    autoExpandColumn: 'Name',
                    // title: 'Vlans',
                    stripeRows:true,
                    clicksToEdit:1,
                    bbar:['->',{
                            text: 'Refresh',
                            xtype: 'button',
                            tooltip: 'refresh',
                            iconCls: 'x-tbar-loading',
                            handler: function(button,event)
                            {
                                button.addClass('x-item-disabled');
                                store.reload({
                                    callback:function(){button.removeClass('x-item-disabled');}});
                            }
                        }],
                    tbar: [
                            {
                                text: 'Add Vlan',
                                iconCls: 'icon-add',
                                handler:this.vlanCreate
                            }// END Add button
                            ,
                            {
                                text: 'Remove Vlan',
                                iconCls: 'icon-remove',
                                handler:this.vlanRemove
                            }// END Remove button
                            ,
                            '->'
                            ,
                            {text:'MAC Management',
                                url:'mac/createwin',
                                handler: View.clickHandler
                            }
                    ],// END tbar
                    //     bbar :pagingToolbar,
                    sm: new Ext.grid.RowSelectionModel({
                        singleSelect: true,
                        moveEditorOnEnter:false
                        //                    listeners:{
                        //                        beforerowselect:function(sm,rowIndex,keepExist,record){
                        //                            if(sm.isSelected(record)){
                        //                                alert('false');
                        //
                        //                            sm.clearSelections();
                        //                            return false;
                        //                        }
                        //                        return true;
                        //
                        //                        },
                        //                        rowselect:function(sm,rowIndex,record){
                        //                       // var rec = grid.store.getAt(rowIndex);
                        //                        alert(record.get('Name'));
                        //                      //  Network.Grid.load(rec.get('Name'));
                        //
                        //
                        //                    }
                        //                    }

                    }),
                    listeners: {
                        rowmousedown:function(grid,rowIndex,e){
                            var rec = grid.store.getAt(rowIndex);
                            if(grid.getSelectionModel().isSelected(rowIndex)){
                                grid.getSelectionModel().clearSelections();
                                Network.InterfacesGrid.load(null);
                                return false;
                            }else{
                                Network.InterfacesGrid.load(rec.get('Name'));
                                return true;
                            }

                        }
                        

                    }
                });//END grid



                return grid;
            }//Fim init
            ,
            vlanCreate:function(){

                Ext.MessageBox.prompt('New vlan','Please enter new vlan name:', function(btn,text){

                    if (btn == 'ok'){

                        var conn = new Ext.data.Connection({
                            listeners:{
                                // wait message.....
                                beforerequest:function(){
                                    Ext.MessageBox.show({
                                        title: 'Please wait',
                                        msg: 'Initializing network...',
                                        width:300,
                                        wait:true,
                                        modal: false
                                    });
                                },// on request complete hide message
                                requestcomplete:function(){Ext.MessageBox.hide();}}
                        });// end conn

                        conn.request({
                            timeout:60000,
                            url: <?php echo json_encode(url_for('vlan/jsonCreate'))?>,
                            params: {'name': text},
                            scope:this,
                            success: function(resp,opt) {

                                var response = Ext.util.JSON.decode(resp.responseText);
                                var txt = response['response'];
                                
                                var length = txt.length;

                                for(var i=0;i<length;i++){
                                    Ext.ux.Logger.info(txt[i]);
                                }

                                store.reload();

                            },
                            failure: function(resp,opt) {

                                var response = Ext.util.JSON.decode(resp.responseText);
                                var txt = response['error'];

                                var length = txt.length;

                                for(var i=0;i<length;i++){
                                    Ext.ux.Logger.error(txt[i]);
                                }                                                                
                                

                                Ext.Msg.show({title: 'Error',
                                    buttons: Ext.MessageBox.OK,
                                    msg: 'Unable to intialize network',
                                    icon: Ext.MessageBox.ERROR});
                            }
                        });// END Ajax request

                    }// end button ok

                });

            },vlanRemove:function(){
                var sm = grid.getSelectionModel();
                var sel = sm.getSelected();                
                if (sm.hasSelection()){
                    Ext.Msg.show({
                        title: 'Remove Vlan',
                        buttons: Ext.MessageBox.YESNOCANCEL,
                        msg: 'Remove '+sel.data.Name+'?',
                        fn: function(btn){
                            if (btn == 'yes'){

                                var conn = new Ext.data.Connection();
                                conn.request({
                                    url: <?php echo json_encode(url_for('vlan/jsonRemove'))?>,
                                    params: {'name': sel.data.Name},
                                    scope:this,
                                    success: function(resp,opt) {

                                        var response = Ext.util.JSON.decode(resp.responseText);
                                        var txt = response['response'];

                                        var length = txt.length;

                                        for(var i=0;i<length;i++){
                                            Ext.ux.Logger.info(txt[i]);
                                        }

                                        store.reload();
                                        Network.InterfacesGrid.reload();
                                        
                                    },
                                    failure: function(resp,opt) {
                                        var response = Ext.util.JSON.decode(resp.responseText);
                                        var txt = response['error'];

                                        var length = txt.length;

                                        for(var i=0;i<length;i++){
                                            Ext.ux.Logger.error(txt[i]);
                                        }

                                        Ext.Msg.show({title: 'Error',
                                            buttons: Ext.MessageBox.OK,
                                            msg: 'Unable to remove network',
                                            icon: Ext.MessageBox.ERROR});
                                    }
                                });// END Ajax request

                            }//END button==yes
                        }// END fn
                    }); //END Msg.show
                }//END if



            }            


        }
    }();
    // END Vlan.Grid

    // Ext.onReady(Network.Grid.init, Network.Grid);
</script>