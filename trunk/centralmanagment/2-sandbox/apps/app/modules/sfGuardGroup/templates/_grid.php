<?php
/*
 * Use Extjs helper to dynamic create data store and column model javascript
 */

$js_grid = js_grid_info($sfGuardGroup_tableMap);
// $js_grid = js_grid_info($sfGuardGroup_tableMap);

/*
 * Default data to be inserted in DB
 */
$default_model_values = array('default'=>'name','items'=>
                                array('name'=>'Change me...'
                                      )
                        );

$insert_model = js_insert_model($sfGuardGroup_form,$default_model_values);

?>
<script>

  sfGuardGroupGrid = function(){
      
  var store;

  return{

    init:function(){
    Ext.QuickTips.init();

    var fm = Ext.form;



    // the column model has information about grid columns
    // dataIndex maps the column to the specific data field in
    // the data store (created below)


//    var cm = new Ext.grid.ColumnModel([
//        {header: "Name", width: 120, dataIndex: 'Name'},
//        {header: "CPU Time", width: 100, dataIndex: 'CPUTime'},
//		{header: "Max VCPU", width: 80, dataIndex: 'MaxVCPU'},
//        {header: "State", width: 100, dataIndex: 'State'}
//
//	//	{header: "Manufacturer", width: 115, dataIndex: 'Manufacturer'},
//	//	{header: "Product Group", width: 100, dataIndex: 'ProductGroup'}
//	]);
//    cm.defaultSortable = true;



    var cm = new Ext.grid.ColumnModel([<?php echo $js_grid['cm'] ?>]);

    /*
    *
    *  Data Source model. Used when creating new network object
    *
    */

   <?php
        $url = json_encode(url_for('sfGuardGroup/jsonGrid',false));
        $store_id = json_encode($js_grid['pk']);
   ?>

        var gridUrl = <?php echo $url ?>;
        var store_id = <?php echo $store_id ?>;
        var sort_field = store_id;
        var httpProxy = new Ext.data.HttpProxy({url: gridUrl});

    // var ds_model = Ext.data.Record.create([<?php // echo $js_grid['ds'] ?>]);
    // var ds_model_insert = <?php // echo $ds_model ?>;

     store = new Ext.data.JsonStore({
            proxy: httpProxy,
            id: store_id,
            totalProperty: 'total',
            root: 'data',
            fields: [<?php echo $js_grid['ds'] ?>],
            sortInfo: { field: sort_field,
            direction: 'DESC' },
            remoteSort: true
        });


//var ds = new Ext.data.Store({
//        // load using HTTP
//        proxy: new Ext.data.HttpProxy({url: <?php //echo json_encode('soapapi/getNodeServers?method=')?>}),
//
//        // the return will be XML, so lets set up a reader
//        reader: new Ext.data.XmlReader({
//               // records will have an "Item" tag
//               record: 'domain',
//               id: 'name',
//               totalRecords: '@total'
//           }, [
//               // set up the fields mapping into the xml doc
//               // The first needs mapping, the others are very basic
//               {name: 'Name', mapping: 'name'},
//               {name: 'CPUTime', mapping: "info@cpuTime"},
//               {name: 'MaxVCPU', mapping: "info@maxvcpus"},
//               {name: 'State', mapping: 'state'}
//           ])
//    });




   // create the grid
//    var nodeGrid = new Ext.grid.GridPanel({
//        store: store,
//        cm:cm,
//        title:'teste',
//        loadMask:true,
//    //    renderTo:'example-grid',
//        width:540,
//        height:200,
//        listeners:{
//    render: function(grid){   //load the store when the grid is rendered
//           grid.loadMask.show();
//         var store = grid.getStore();
//           store.load.defer(20,store);  //give the mask a chance to render
//    },
//    delay : 100, //also give the loadMask time to init (afterRender).
//    single : true
//}
//
//    });



    // create the editor grid
      var grid = new Ext.grid.EditorGridPanel({
            store: store,
            cm: cm,
            border: false,
            loadMask: {msg: 'Retrieving info...'},
        
            //,

       //     getRowClass : function(r){
         //       var d = r.data;
           //     if(d.completed){
            //        return 'task-completed';
             //   }
             //   if(d.dueDate && d.dueDate.getTime() < new Date().clearTime().getTime()){
               //     return 'task-overdue';
               // }
             //   return '';
           // }
        
            
            viewConfig:{forceFit:true},            
            autoScroll:true,
            title: 'Permission Groups',
            stripeRows:true,
            clicksToEdit:1,           
            tbar: [{
                    text: 'Add Group',
                    tooltip:'Add a new permission group',
                    iconCls: 'icon-add',
                    handler: function() {
                        var insert_model = <?php echo json_encode($insert_model['db']); ?>;

                        var conn = new Ext.data.Connection();
                        conn.request({
                            url: 'sfGuardGroup/jsonCreate',
                            params: insert_model,
                            success: function(resp,opt) {
                                var insert_id = Ext.util.JSON.decode(resp.responseText).insert_id;

                                 store.setDefaultSort(store_id,'DESC');
                                 store.reload({
                                        callback:function(){

                                         var row = store.find(store_id,insert_id);
                                         var sm = sfGuardGroupGrid.getSelectionModel();

                                         sm.selectRow(row);
                                         sfGuardGroupGrid.startEditing(row,1);

                                         }});
                            },
                            failure: function(resp,opt) {
                                var stringRes = resp.responseText;
                                try {
                                    var jsonErrors = Ext.util.JSON.decode(stringRes).errors;
                                    var str = '';
                                    for(prop in jsonErrors)
                                        str += jsonErrors[prop]+'\n';//Concate prop and its value from object

                                    Ext.MessageBox.show({
                                        title: 'Permission Group',
                                        msg: str,
                                        buttons: Ext.MessageBox.OK,
                                        icon: Ext.MessageBox.WARNING
                                    });
                                    // Ext.Msg.alert('Error','Unable to add user group <br>'+ str);
                                }
                                catch (err) {
                                    Ext.MessageBox.show({
                                        title: 'User Groups',
                                        msg: 'Unable to add user group',
                                        buttons: Ext.MessageBox.OK,
                                        icon: Ext.MessageBox.ERROR
                                    });

                                }

                            }
                        }); // END Ajax request
                    }// END Add handler
                   }// END Add button
                   ,
                   {
                    text: 'Remove Group',
                    tooltip:'Remove the selected group',
                    iconCls: 'icon-remove',
                    handler: function() {
                        var sm = sfGuardGroupGrid.getSelectionModel();
                        var sel = sm.getSelected();
                        if (sm.hasSelection()){
                            Ext.Msg.show({
                                title: 'Remove Permission Group',
                                buttons: Ext.MessageBox.YESNOCANCEL,
                                msg: 'Remove '+sel.data.Name+'?',
                                fn: function(btn){
                                    if (btn == 'yes'){
                                        var conn = new Ext.data.Connection();
                                        conn.request({
                                            url: 'sfGuardGroup/jsonDelete',
                                            params: {
                                                'sf_method':'delete',
                                                id: sel.id
                                            },
                                            success: function(resp,opt) {
                                                sfGuardGroupGrid.getStore().reload();
                                               // sfGuardGroupGrid.getStore().remove(sel);

                                            },
                                            failure: function(resp,opt) {
                                                Ext.Msg.alert('Error',
                                                'Unable to delete group');
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
                            url: 'sfGuardGroup/jsonUpdate',
                            params: {
                                action: 'update',
                                id: e.record.id,
                                field: e.field,
                                value: e.value
                            },
                            success: function(resp,opt) {
                                var colCount = sfGuardGroupGrid.colModel.getColumnCount();
                                e.record.commit();
                                if(e.column < (colCount - 1))
                                    sfGuardGroupGrid.startEditing(e.row,e.column+1);
                            },
                            failure: function(resp,opt) {
                                Ext.Msg.alert('Error','Could not save changes');
                                //e.record.reject();
                            }
                        });//END Ajax request
                    }//END afteredit



            }
        });//END networkGrid



  
      // view = grid.getView();
     // view.setEmptyText("Press the Load button to reload Payoff from Database.");
    //  grid.setView(view);
 

 store.load.defer(20,store,[{params:{start:0,limit:10}}]);
 return grid;
     }//Fim init

}
  }();

sfGuardGroupGrid = sfGuardGroupGrid.init();

      // The fields in the grid's header
  

// store.load.defer(20,store);  //give the mask a chance to render
</script>