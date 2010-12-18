<?php

$js_grid = js_grid_info($agent_tableMap);

/*
 * Default data to be inserted in DB
 */

$default_model_values = array('default'=>'name','items'=>
                                array('name'=>'Change me...',
                                      'server_id'=>$server_id,
                                      'ip'=>'000.000.000.000',
                                      'mask'=>'000.000.000.000'
                                      )
                        );

$insert_model = js_insert_model($agent_form,$default_model_values);

?>
<script>
/*
 * Partial agentGrid
 */
// shorthand alias
Ext.namespace('Agent');
  Agent.Grid = function(){
    var agentGrid;
    return{
      init:function(){
          Ext.QuickTips.init();

      //  var ds_insert = <?php // echo $ds_insert ?>;

        var fm = Ext.form;

        var cm = new Ext.grid.ColumnModel([<?php echo $js_grid['cm'] ?>]);


        <?php
        $url = json_encode(url_for('agent/jsonGrid?sid='.$server_id,false));
        $store_id = json_encode($js_grid['pk']);
        ?>

        var gridUrl = <?php echo $url ?>;
        var store_id = <?php echo $store_id ?>;
        var sort_field = store_id;
        var httpProxy = new Ext.data.HttpProxy({url: gridUrl});

        // var ds_model = Ext.data.Record.create([<?php // echo $insert_model['ds'] ?>]);

        // create the Data Store
        var store = new Ext.data.JsonStore({
            proxy: httpProxy,
            id: store_id,
            totalProperty: 'total',
            root: 'data',
            fields: [<?php echo $js_grid['ds'] ?>],
            sortInfo: { field: sort_field,
            direction: 'DESC' },
            remoteSort: true
        });        

        // create the editor grid
        agentGrid = new Ext.grid.EditorGridPanel({
            store: store,
            cm: cm,
            border: false,
            loadMask: {msg: 'Retrieving info...'},
            viewConfig:{forceFit:true},
            id:'agent-grid',
            autoScroll:true,
            title: 'Agents',
            stripeRows:true,
            clicksToEdit:1,
            tbar: [{
                    text: 'Add Agent',
                    iconCls: 'icon-add',
                    handler: function() {
       
                        var insert_model = <?php echo json_encode($insert_model['db']); ?>;


                        var conn = new Ext.data.Connection();
                        conn.request({
                            url: 'agent/jsonCreate',
                            params: insert_model,
                            success: function(resp,opt) {
                                var insert_id = Ext.util.JSON.decode(resp.responseText).insert_id;
                 //               var updated_at = Ext.util.JSON.decode(resp.responseText).updated_at;

                 //                ds_insert[store_id] = insert_id;
                 //                ds_insert['CreatedAt'] = 'insert_id';
                 //                ds_insert['UpdatedAt'] = updated_at;
                                 store.setDefaultSort(store_id,'DESC');
                                 store.reload({                                         
                                        callback:function(){

                                         //setTimeout(Agent.Grid.startEditRow, 200);
                                         
                                         var row = store.find(store_id,insert_id);
                                         var sm = agentGrid.getSelectionModel();

                                         sm.selectRow(row);
                                         agentGrid.startEditing(row,2);
                                        
                                        // alert(insert_id);
                                        // var sel = store.getById(insert_id);
                                        // alert(sel);
                                        //.getSelected();
                                         }});
                                 //agentGrid.startEditing(0,2);
                           //     agentGrid.getStore().insert(0,
                             //       new ds_model(ds_insert,insert_id));

        
                              //  agentGrid.startEditing(0,2);
                            },
                            failure: function(resp,opt) {
                                Ext.Msg.alert('Error','Unable to add agent');
                            }
                        }); // END Ajax request
                    }// END Add handler
                   }// END Add button
                   ,
                   {
                    text: 'Remove Agent',
                    iconCls: 'icon-remove',
                    handler: function() {
                        var sm = agentGrid.getSelectionModel();
                        var sel = sm.getSelected();
                        if (sm.hasSelection()){
                            Ext.Msg.show({
                                title: 'Remove Agent',
                                buttons: Ext.MessageBox.YESNOCANCEL,
                                msg: 'Remove '+sel.data.Name+'?',
                                fn: function(btn){
                                    if (btn == 'yes'){
                                        var conn = new Ext.data.Connection();
                                        conn.request({
                                            url: 'agent/jsonDelete',
                                            params: {
                                                'sf_method':'delete',
                                                id: sel.id
                                            },
                                            success: function(resp,opt) {
                                                agentGrid.getStore().remove(sel);

                                            },
                                            failure: function(resp,opt) {
                                                Ext.Msg.alert('Error',
                                                'Unable to delete agent');
                                            }
                                        });// END Ajax request
                                    }//END button==yes
                                }// END fn
                            }); //END Msg.show
                        };//END if
                    }//END handler Remove
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

            }),
            listeners: {
                    afteredit: function(e){
                        var conn = new Ext.data.Connection();
                        conn.request({
                            url: 'agent/jsonUpdate',
                            params: {
                                action: 'update',
                                id: e.record.id,
                                field: e.field,
                                value: e.value
                            },
                            success: function(resp,opt) {
                                var colCount = agentGrid.colModel.getColumnCount();
                                e.record.commit();
                                if(e.column < (colCount - 1))
                                    agentGrid.startEditing(e.row,e.column+1);
                            },
                            failure: function(resp,opt) {
                               Ext.Msg.alert('Error','Could not save changes');
                                //e.record.reject();
                            }
                        });//END Ajax request
                    }//END afteredit



            }
        });//END agentGrid
 agentGrid.on({
 afterlayout:{scope:this, single:true, fn:function() {
 store.load({params:{start:0, limit:10}});
 }}
 });
 
 return agentGrid;
     }//Fim init
     


}
  }();
  
 // Ext.onReady(Network.Grid.init, Network.Grid);
</script>