<?php
/*
 * Use Extjs helper to dynamic create data store and column model javascript
 */

$js_grid = js_grid_info($node_tableMap);

/*
 * Default data to be inserted in DB
 */
$default_model_values = array('default'=>'name','items'=>
                                array('name'=>'Change me...'
                                      )
                        );

// $insert_model = js_insert_model($node_form,$default_model_values);

?>
<script>

Ext.namespace('Node');
  Node.Grid = function(){    
  return{
    init:function(){
    Ext.QuickTips.init();

    var fm = Ext.form;        

    // the column model has information about grid columns
    // dataIndex maps the column to the specific data field in
    // the data store (created below)
    
    
    var cm = new Ext.grid.ColumnModel([<?php echo $js_grid['cm'] ?>]);


    /*
    *
    *  Data Source model. Used when creating new network object
    *
    */
    
    // var ds_model = Ext.data.Record.create([<?php // echo $js_grid['ds'] ?>]);
    // var ds_model_insert = <?php // echo $ds_model ?>;
<?php
    if(isset($node_id)){
    $title = json_encode('Node info');
    $url = json_encode(url_for('node/jsonGridInfo?id='.$node_id,false));
    }else{
    $title = json_encode('Nodes');
    $url = json_encode(url_for('node/jsonGrid',false));
    }

    $store_id = json_encode($js_grid['pk']);
?>
              
    var grid_title = <?php echo $title ?>;
    var gridUrl = <?php echo $url ?>;
    var store_id = <?php echo $store_id ?>;
    var sort_field = store_id;
    var httpProxy = new Ext.data.HttpProxy({url: gridUrl});

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


    var bpaging = new Ext.PagingToolbar({
            store: store,
            displayInfo:true,
            pageSize:10,
            plugins:new Ext.ux.Andrie.pPageSize({comboCfg: {width: 50}})
        });


    // create the editor grid
    var nodeGrid = new Ext.grid.EditorGridPanel({
        store: store,
        cm: cm,
        border: false,
        loadMask: {msg: 'Retrieving info...'},
        viewConfig:{forceFit:true},
        layout:'fit',     
        autoScroll:true,
        title: grid_title,
        stripeRows:true,
        clicksToEdit:1,
        tbar: [
//              {
//            text: 'Add Node',
//            iconCls: 'icon-add',
//            handler: function() {
//                var insert_model = <?php // echo json_encode($insert_model['db']); ?>;
//
//                var conn = new Ext.data.Connection();
//                conn.request({
//                    url: 'node/jsonCreate',
//                    params: insert_model,
//                    title: insert_model[<?php // echo $insert_model['title']; ?>],
//                    scope: this,
//                    success: function(resp,options) {
//                        var insert_id = Ext.util.JSON.decode(resp.responseText).insert_id;
//                        var title = options.title;
//
////                        ds_model_insert[store_id] = insert_id;
////                        nodeGrid.getStore().insert(0,
////                            new ds_model(ds_model_insert,insert_id));
////                        nodeGrid.startEditing(0,1);
//                          nodesPanel.addNode({id: insert_id,text: title,url: 'node/view?id='+insert_id});
////                        nodesPanel.fireEvent('addNode', {
////                            id: insert_id,
////                            text: title,
////                            url: 'node/view?id='+insert_id});
//                    },
//                    failure: function(resp,opt) {
//                        Ext.Msg.alert('Error','Unable to add node');
//                    }
//                }); // END Ajax request
//            }// END Add handler
//           }// END Add button
//           ,
           {
            text: 'Remove Node',
            iconCls: 'icon-remove',
            //cls: 'x-btn-text-icon',
            handler: function() {
                var sm = nodeGrid.getSelectionModel();
                var sel = sm.getSelected();
                if (sm.hasSelection()){
                    Ext.Msg.show({
                        title: 'Remove Node',
                        buttons: Ext.MessageBox.YESNOCANCEL,
                        msg: 'Remove '+sel.data.Name+'?',
                        fn: function(btn){
                            if (btn == 'yes'){
                                var conn = new Ext.data.Connection();
                                conn.request({
                                    url: 'node/jsonDelete',
                                    params: {                                        
                                        'sf_method':'delete',
                                        id: sel.id
                                    },
                                    success: function(resp,opt) {
                                        nodeGrid.getStore().remove(sel);
                                        nodesPanel.removeNode(sel.id);
                                        //nodes.fireEvent('removeNode',sel.id);
                                           
                                    },
                                    failure: function(resp,opt) {
                                        Ext.Msg.alert('Error',
                                        'Unable to delete node');
                                    }
                                });// END Ajax request
                            }//END button==yes
                        }// END fn
                    }); //END Msg.show
                };//END if
            }//END handler Remove
           }// END Remove button
    ],// END tbar
    bbar : bpaging,
  //  bbar : new Ext.PagingToolbar({
    //        store: store,
      //      displayInfo:true,
        //    pageSize:10
          //  ,
         //   plugins:new Ext.ux.Andrie.pPageSize({comboCfg: {width: 50}})
   //  }),
    sm: new Ext.grid.RowSelectionModel({
            singleSelect: true,
            moveEditorOnEnter:false
          
    }),
    listeners: {
            afteredit: function(e){
                var conn = new Ext.data.Connection();
                conn.request({
                    url: 'node/jsonUpdate',
                    params: {
                        action: 'update',
                        id: e.record.id,
                        field: e.field,
                        value: e.value
                    },
                    success: function(resp,opt) {

                        if(e.field=='Name'){
                            
                            var currentNode = nodesPanel.getSelectionModel().getSelectedNode();
                            var updateNodeId = currentNode.id;

                            if(currentNode.id==0){ //root Node
                                updateNodeId = e.record.id;
                            }else if(currentNode.isLeaf()){
                                updateNodeId = 's'+e.record.id;
                            }

                            
                            nodesPanel.updateNode({
                            id: updateNodeId,
                            text: e.value});
                        }

                        var colCount = nodeGrid.colModel.getColumnCount();
                        e.record.commit();
                        if(e.column < (colCount - 1))
                            nodeGrid.startEditing(e.row,e.column+1);
                    },
                    failure: function(resp,opt) {
                        Ext.Msg.alert('Error','Could not save changes');
                        //e.record.reject();
                    }
                });//END Ajax request
            }//END afteredit
           

                                    
    }
});//END nodeGrid
 nodeGrid.on({
 afterlayout:{scope:this, single:true, fn:function() {
 <?php if(isset($node_id)):?>
 store.load({params:{start:0, limit:10}});
 <?php else: ?>
     store.load();
 <?php endif; ?>
 }}
 });
 return nodeGrid;
     }//Fim init

}
  }();

//Ext.onReady(Node.Grid.init, Node.Grid);

</script>