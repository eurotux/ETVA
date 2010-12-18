<?php
/*
 * Use Extjs helper to dynamic create data store and column model javascript
 */
// use_helper('Extjs');
$extraDSfields = array('SfGuardGroupName');

$extraCMAttrs = array(
                        'SfGuardGroupId'=> array('editor'=>"new Ext.form.ComboBox({
                                valueField: 'Id',displayField: 'Name',
                                forceSelection: true,store: sfGuardGroup_ds,
                                mode: 'remote',lazyRender: true,triggerAction: 'all',
                                listClass: 'x-combo-list-small'})",
                                 'renderer'=>"rendersfGuardGroupName")
);


$js_grid = js_grid_info($server_tableMap,true,$extraDSfields,$extraCMAttrs);

$js_sfGuard = js_grid_info($sfGuardGroup_tableMap);





?>
<script>



    Ext.namespace('Server');
    Server.Grid = function(){
        var node_id = <?php echo $node_id; ?>;
        //  var networkGrid;
        return{
            // var networkGrid;
            init:function(){
                Ext.QuickTips.init();

                /*
                 * Partial networkGrid
                 */




                // the column model has information about grid columns
                // dataIndex maps the column to the specific data field in
                // the data store (created below)
                // TODO: get fields dynamicaly


                /*
                 * Data Source model. Used when creating new network object
                 */
                //var ds_model = Ext.data.Record.create([
                //              'Id','Name']);

<?php

if(isset($server_id)){
    $title = json_encode('Server info');
    $url = json_encode(url_for('server/jsonGridInfo?id='.$server_id,false));
    $id = $server_id;
}else{
    $title = json_encode('Servers');
    $url = json_encode(url_for('server/jsonGrid?nid='.$node_id,false));
    $id = $node_id;
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

                // In case of http exception code show error box
                //  httpProxy.getConnection().on('requestexception', View.requestFailed);


                sfGuardGroup_ds = new Ext.data.JsonStore({
                    url: 'sfGuardGroup/json',
                    id: store_id,
                    totalProperty: 'total',
                    root: 'data',
                    fields: [<?php echo $js_sfGuard['ds'] ?>],
                    sortInfo: { field: sort_field,
                        direction: 'DESC' },
                    remoteSort: true
                });



                // Define related renderer-helpers to show preloaded data; prevent loading all related data stores
                function preloadsfGuardGroupNameFromIndex(index, value) {
                    var Record = Ext.data.Record.create([<?php echo $js_sfGuard['ds'] ?>]);
                    var record_data = Array();
                    record_data['Id'] = value;
                    record_data['Name'] = store.getAt(index).get('SfGuardGroupName');
                    var r = new Record(record_data);

                    r.id = value;

                    sfGuardGroup_ds.add(r);
                }

                function rendersfGuardGroupName(value) {
                    if (value) {
                        //  alert(store.find('SfGuardGroupId', value));
                        // if value in foreign datastore, return this value
                        if (sfGuardGroup_ds.getById(value)) {
                            return sfGuardGroup_ds.getById(value).get('Name');
                            // if not in foreign datastore, test if it is preloaded (this can be out-dated therefor do it after checking datastore)
                        } else if ((index = store.find('SfGuardGroupId', value)) != -1) {
                            // foreign-ID not known in foreign-store yet, add preloaded data from the main-store
                            preloadsfGuardGroupNameFromIndex(index, value);
                            return store.getAt(index).get('SfGuardGroupName');
                        }
                    }
                }

                var cm = new Ext.grid.ColumnModel([<?php echo $js_grid['cm'] ?>]);



                var bpaging = new Ext.PagingToolbar({
                    store: store,
                    displayInfo:true,
                    pageSize:10,
                    plugins:new Ext.ux.Andrie.pPageSize({comboCfg: {width: 50}})
                });


                // create the editor grid
                var serverGrid = new Ext.grid.EditorGridPanel({
                    store: store,
                    cm: cm,
                    border: false,
                    loadMask: {msg: 'Retrieving info...'},
                    viewConfig:{forceFit:true},
                    autoScroll:true,
                    title: grid_title,
                    stripeRows:true,
                    clicksToEdit:1,
                    tbar: [
                        {text:'Add Server Wizard',
                            iconCls: 'icon-add',
                            url:<?php echo(json_encode(url_for('server/createwin?id=')))?>+<?php echo($node_id);?>,
                            handler: View.clickHandler
                        },
                        {text: 'Remove Server',
                            iconCls: 'icon-remove',
                            handler: function() {
                                var sm = serverGrid.getSelectionModel();
                                var sel = sm.getSelected();
                                if (sm.hasSelection()){
                                    Ext.Msg.show({
                                        title: 'Remove Server',
                                        buttons: Ext.MessageBox.YESNOCANCEL,
                                        msg: 'Delete '+sel.data.Name+'?',
                                        icon: Ext.MessageBox.QUESTION,
                                        fn: function(btn){
                                            if (btn == 'yes'){
                                                // process delete
                                                var params = {'name':sel.data.Name};
                                                var conn = new Ext.data.Connection({
                                                    listeners:{
                                                        // wait message.....
                                                        beforerequest:function(){
                                                            Ext.MessageBox.show({
                                                                title: 'Please wait',
                                                                msg: 'Deleting virtual server...',
                                                                width:300,
                                                                wait:true,
                                                                modal: false
                                                            });
                                                        },// on request complete hide message
                                                        requestcomplete:function(){Ext.MessageBox.hide();}}
                                                });// end conn
                                                //send soap vmDestroy
                                                // on success delete from DB
                                                conn.request({
                                                    url: <?php echo json_encode(url_for('server/jsonRemove'))?>,
                                                    // url: <?php // echo json_encode(url_for('node/soap?method=vmDestroy'))?>,
                                                    params: {'nid':node_id,'server':sel.data.Name},
                                                    scope:this,
                                                    success: function(resp,opt) {

                                                        var response = Ext.util.JSON.decode(resp.responseText);
                                                        Ext.ux.Logger.info(response['response']);
                                                        serverGrid.getStore().remove(sel);
                                                        node_id = 's'+sel.id;
                                                        nodesPanel.removeNode(node_id);

                                                    },
                                                    failure: function(resp,opt) {
                                                        var response = Ext.util.JSON.decode(resp.responseText);
                                                        Ext.ux.Logger.error(response['error']);

                                                        Ext.Msg.show({title: 'Error',
                                                            buttons: Ext.MessageBox.OK,
                                                            msg: 'Unable to delete server '+sel.data.Name,
                                                            icon: Ext.MessageBox.ERROR});

                                                    }
                                                });// END Ajax request
                                            }//END button==yes
                                        }// END fn
                                    }); //END Msg.show
                                };//END if
                            }//END handler Remove
                        },
                        '-',
                        {text:'Open console',
                            handler:function(){

                                var sm = serverGrid.getSelectionModel();
                                var sel = sm.getSelected();
                                if (sm.hasSelection()){

                                    var url = '<?php echo url_for('view/vncviewer?id=');?>'+sel.data.Id


                                    this.win = new Ext.Window ({
                                        height: 510,
                                        resizable:false,
                                        layout:'fit',
                                        shim:false,
                                        width: 655,
                                        title: sel.data.Name + ' console',
                                        html: '<iframe width="640" height="480" frameborder=0 src="'+url+'"></iframe>'
                                    });
                                    //


                                    this.win.show();


                                }


                            }
                        },
                        '-',
                        {text:'Start Server',
                            handler: function() {
                                var sm = serverGrid.getSelectionModel();
                                var sel = sm.getSelected();
                                if (sm.hasSelection()){
                                    Ext.Msg.show({
                                        title: 'Start Server',
                                        buttons: Ext.MessageBox.YESNOCANCEL,
                                        msg: 'Start '+sel.data.Name+'?',
                                        fn: function(btn){
                                            if (btn == 'yes'){
                                                var params = {'name':sel.data.Name};
                                                var conn = new Ext.data.Connection({
                                                    listeners:{
                                                        // wait message.....
                                                        beforerequest:function(){
                                                            Ext.MessageBox.show({
                                                                title: 'Please wait',
                                                                msg: 'Starting virtual server...',
                                                                width:300,
                                                                wait:true,
                                                                modal: false
                                                            });
                                                        },// on request complete hide message
                                                        requestcomplete:function(){Ext.MessageBox.hide();}}
                                                });// end conn
                                                conn.request({
                                                    url: <?php echo json_encode('server/jsonStart')?>,
                                                    params: {'nid':node_id,'server': sel.data.Name},
                                                    scope:this,
                                                    success: function(resp,opt) {
                                                        var response = Ext.util.JSON.decode(resp.responseText);
                                                        Ext.ux.Logger.info(response['response']);

                                                        store.reload();


                                                    },
                                                    failure: function(resp,opt) {
                                                        var response = Ext.util.JSON.decode(resp.responseText);

                                                        Ext.ux.Logger.error(response['error']);

                                                        Ext.Msg.show({title: 'Error',
                                                            buttons: Ext.MessageBox.OK,
                                                            msg: 'Unable to start virtual server ' + sel.data.Name,
                                                            icon: Ext.MessageBox.ERROR});
                                                    }
                                                });// END Ajax request
                                            }//END button==yes
                                        }// END fn
                                    }); //END Msg.show
                                };//END if
                            }//END handler
                        },
                        {text:'Stop Server',
                            handler: function() {
                                var sm = serverGrid.getSelectionModel();
                                var sel = sm.getSelected();
                                if (sm.hasSelection()){
                                    Ext.Msg.show({
                                        title: 'Stop Server',
                                        buttons: Ext.MessageBox.YESNOCANCEL,
                                        msg: 'Stop '+sel.data.Name+'?',
                                        icon: Ext.MessageBox.QUESTION,
                                        fn: function(btn){
                                            if (btn == 'yes'){
                                                var params = {'name':sel.data.Name};
                                                var conn = new Ext.data.Connection({
                                                    listeners:{
                                                        // wait message.....
                                                        beforerequest:function(){
                                                            Ext.MessageBox.show({
                                                                title: 'Please wait',
                                                                msg: 'Stoping virtual server...',
                                                                width:300,
                                                                wait:true,
                                                                modal: false
                                                            });
                                                        },// on request complete hide message
                                                        requestcomplete:function(){Ext.MessageBox.hide();}}
                                                });// end conn
                                                conn.request({
                                                    url: <?php echo json_encode('server/jsonStop')?>,
                                                    params: {'nid':node_id,'server': sel.data.Name},
                                                    scope:this,
                                                    success: function(resp,opt) {
                                                        var response = Ext.util.JSON.decode(resp.responseText);
                                                        Ext.ux.Logger.info(response['response']);

                                                        store.reload();

                                                    },
                                                    failure: function(resp,opt) {
                                                        var response = Ext.util.JSON.decode(resp.responseText);

                                                        Ext.ux.Logger.error(response['error']);

                                                        Ext.Msg.show({title: 'Error',
                                                            buttons: Ext.MessageBox.OK,
                                                            msg: 'Unable to stop virtual server ' + sel.data.Name,
                                                            icon: Ext.MessageBox.ERROR});
                                                    }
                                                });// END Ajax request
                                            }//END button==yes
                                        }// END fn
                                    }); //END Msg.show
                                };//END if
                            }//END handler Remove
                        }
                        ,
                        '-',
                        {text: 'Refresh',
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
                        }

                    ],// END tbar
                    bbar : bpaging,
                    sm: new Ext.grid.RowSelectionModel({
                        singleSelect: true,
                        moveEditorOnEnter:false

                    }),
                    listeners: {
                        afteredit: function(e){
                            var conn = new Ext.data.Connection();
                            conn.request({
                                url: 'server/jsonUpdateField',
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
                                        updateNodeId = 's'+e.record.id;

                                        //                            if(currentNode.id==0){ //root Node
                                        //                                updateNodeId = e.record.id;
                                        //                            }else if(currentNode.isLeaf()){
                                        //                                updateNodeId = 's'+e.record.id;
                                        //                            }


                                        nodesPanel.updateNode({
                                            id: updateNodeId,
                                            text: e.value});
                                    }

                                    var colCount = serverGrid.colModel.getColumnCount();
                                    e.record.commit();
                                    if(e.column < (colCount - 1))
                                        serverGrid.startEditing(e.row,e.column+1);
                                },
                                failure: function(resp,opt) {
                                    Ext.Msg.alert('Error','Could not save changes');
                                    //e.record.reject();
                                }
                            });//END Ajax request
                        }//END afteredit

                    }
                });//END serverGrid


serverGrid.on('render', function(){

<?php if(isset($node_id)):?>
                store.load({params:{start:0, limit:10}});
<?php else: ?>
                store.load();
<?php endif; ?>
				
				        	});
				                   
                return serverGrid;
            }//Fim init


            //     ,afterlayout:{scope:this, single:true, fn:function() {
            // store.load({params:{start:0, limit:10}});
            // }}

        }
    }();

</script>