<?php
/*
 * Use Extjs helper to dynamic create data store and column model javascript
 */
$extraDSfields = array('mem_text','mem_available','state_text');
$js_grid = js_grid_info($node_tableMap,true,$extraDSfields);

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
    init:function(config){
    Ext.QuickTips.init();

    var fm = Ext.form;    
    
    Ext.apply(this,config);


    var expander = new Ext.ux.grid.RowExpander({
        enableCaching : false,
        tpl : new Ext.XTemplate(
        '<p><b>UUID:</b> {uuid}&nbsp&nbsp <b>Port:</b> {port}<br>',
        '<b>VirtAgent status:</b>',
        '<span style="color:{[values.state === 1 ? "green" : "red"]}">',
            ' {values.state_text}',
        '</span>&nbsp&nbsp <b>Created at:</b> {created_at}<br>',
        '</p>'
    )});
  
    //var cm = new Ext.grid.ColumnModel([expander,<?php // echo $js_grid['cm'] ?>]);
    var cm = new Ext.grid.ColumnModel([expander,
                    {header:'Name', dataIndex:'name'},
                    {header:'Memory (MB)', dataIndex:'mem_text'},
                    {header:'Memory Available (MB)', dataIndex:'mem_available', width:150},
                    {header:'CPUs', dataIndex:'cputotal'},
                    {header:'Network cards', dataIndex:'network_cards'},
                    {header:'IP', dataIndex:'ip'},
                    {header:'Hypervisor', dataIndex:'hypervisor'},
                    {header: "State", dataIndex:'state_text',renderer: function (value, metadata, record, rowIndex, colIndex, store) {
                                if(value=='Down') metadata.attr = 'style="background-color: red;color:white;"';
                                else metadata.attr = 'style="background-color: green;color:white;"';
                                return value;
                    }}
    ]);


    /*
    *
    *  Data Source model. Used when creating new network object
    *
    */

    // var ds_model = Ext.data.Record.create([<?php // echo $js_grid['ds'] ?>]);
    // var ds_model_insert = <?php // echo $ds_model ?>;
<?php
    $store_id = json_encode($js_grid['pk']);
?>

    var grid_title = this.title;
    var gridUrl = this.url;
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
        sortInfo: { field: 'name',
        direction: 'ASC' },
        remoteSort: true
    });


//    this.changeAAA = function(id){
//        this.aaa = id;
//        alert(this.aaa);
//    }


    var bpaging = new Ext.PagingToolbar({
            store: store,
            displayInfo:true,
            pageSize:10,
            plugins:new Ext.ux.Andrie.pPageSize({comboCfg: {width: 50}})
        });

    // row expander
            


        // create the editor grid
        var nodeGrid = new Ext.grid.EditorGridPanel({
            store: store,
            cm: cm,
            border: false,
            loadMask: {msg: <?php echo json_encode(__('Retrieving data...')) ?>},
            viewConfig:{
                emptyText: __('Empty!'),  //  emptyText Message
                forceFit:true
            },
            layout:'fit',
            autoScroll:true,
            title: grid_title,
            stripeRows:true,
            clicksToEdit:1,
            plugins:expander,
    //        tbar: [
    //              {
    //            text: 'Add node',
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
    //           {
    //            text: <?php //echo json_encode(__('Remove Node'))?>,
    //            iconCls: 'icon-remove',
    //            hidden:true,
    //            tooltip: <?php //echo json_encode(__('Disabled in this version')) ?>,
    //            //cls: 'x-btn-text-icon',
    //            handler: function() {
    //                var sm = nodeGrid.getSelectionModel();
    //                var sel = sm.getSelected();
    //                if (sm.hasSelection()){
    //                    Ext.Msg.show({
    //                        title: <?php //echo json_encode(__('Remove Node'))?>,
    //                        buttons: Ext.MessageBox.YESNO,
    //                        msg: String.format(<?php //echo json_encode(__('Remove Node {0} ?')) ?>,sel.data.Name),
    //                        fn: function(btn){
    //                            if (btn == 'yess'){
    //                                var conn = new Ext.data.Connection();
    //                                conn.request({
    //                                    url: 'node/jsonDelete',
    //                                    params: {
    //                                        'sf_method':'delete',
    //                                        id: sel.id
    //                                    },
    //                                    success: function(resp,opt) {
    //                                        nodeGrid.getStore().remove(sel);
    //                                        nodesPanel.removeNode(sel.id);
    //                                        //nodes.fireEvent('removeNode',sel.id);
    //
    //                                    },
    //                                    failure: function(resp,opt) {
    //                                        Ext.Msg.alert(<?php //echo json_encode(__('Error!')) ?>, <?php //echo json_encode(__('Unable to delete node!')) ?>);
    //                                    }
    //                                });// END Ajax request
    //                            }//END button==yes
    //                        }// END fn
    //                    }); //END Msg.show
    //                };//END if
    //            }//END handler Remove
    //           }// END Remove button
    //    ],// END tbar
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
                        url: 'node/jsonGridUpdate',
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
                            Ext.Msg.alert(<?php echo json_encode(__('Error!')) ?>, <?php echo json_encode(__('Could not save changes!')) ?>);
                            //e.record.reject();
                        }
                    });//END Ajax request
                }//END afteredit



        }
    });//END nodeGrid
 
     nodeGrid.on({
        activate:{scope:this,fn:function() {

            if(this.type=='info') store.load.defer(100,store);
            else store.load.defer(100,store,[{params:{start:0, limit:10, id:this.aaa}}]);
        }}
     });
 
        return nodeGrid;
    }//Fim init    
}
  }();

//Ext.onReady(Node.Grid.init, Node.Grid);

</script>
