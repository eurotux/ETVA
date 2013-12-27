<?php
    include_partial('server/add_device_win');
?>
<script>

Ext.QuickTips.init();
Ext.ns('Server.Devices');

Server.ManageDevices = Ext.extend(Ext.Panel,{ //Ext.grid.EditorGridPanel,{
//    selectItem_msg:<?php echo json_encode(__('Network interface from grid must be selected!')) ?>,
    layout:'fit',autoScroll:true,
    initComponent:function(){      
//        alert("init managedevices: "+this.server_id);

        var devicesStore = new Ext.data.JsonStore({
            root: 'data'
            ,totalProperty: 'total'
            ,baseParams:{
                'sid': this.server_id 
            }
            ,fields:[
                {name:'id'}
                ,{name: 'type', mapping: 'type', type: 'string'}
                ,{name: 'idvendor', mapping: 'idvendor', type: 'string'}
                ,{name: 'idproduct', mapping: 'idproduct', type:'string'}
                ,{name: 'description', mapping: 'description', type: 'string'}
                ,{name:'bus', mapping:'bus', type:'string'}
                ,{name:'slot', mapping:'slot', type:'string'}
                ,{name:'function', mapping:'function', type:'string'}
                ,{name:'controller', mapping:'controller', type:'string'}
            ]
            ,url: <?php echo json_encode(url_for('server/jsonListServerDevices'))?>
        });

    var serverDevicesGrid = new Ext.grid.GridPanel({
        id: 'server-edit-devices-grid'
        ,viewConfig: {forceFit: true},
        store: devicesStore,
        url: <?php echo json_encode(url_for('server/jsonListServerDevices'))?>,
        scope:this,
        selModel: new Ext.grid.RowSelectionModel({	//deixa seleccionar apenas uma linha
//            singleSelect: true
            singleSelect: false
        }),
        columns: [
            {
                header   : <?php echo json_encode(__('Type')) ?>,
                width    : 15,
                sortable : true,
                dataIndex: 'type'
            }
            ,{
//                id       :'id',
                header   : <?php echo json_encode(__('Vendor')) ?>,
                width    : 18,
                sortable : true,
                dataIndex: 'idvendor'
            }
            ,{
                header   : <?php echo json_encode(__('Product')) ?>,
                width    : 18,
                sortable : true,
                dataIndex: 'idproduct'
            }
            ,{
                header   : <?php echo json_encode(__('Bus')) ?>,
                width    : 10,
                sortable : true,
                hidden   : true,
                dataIndex: 'bus'
            }
            ,{
                header   : <?php echo json_encode(__('Slot')) ?>,
                width    : 10,
                sortable : true,
                hidden   : true,
                dataIndex: 'slot'
            }
            ,{
                header   : <?php echo json_encode(__('Function')) ?>,
                width    : 15,
                sortable : true,
                hidden   : true,
                dataIndex: 'function'
            }
            ,{
                header   : <?php echo json_encode(__('Controller')) ?>,
                width    : 15,
                sortable : true,
                dataIndex: 'controller'
            }
            ,{
                header   : <?php echo json_encode(__('Description')) ?>,
//                width    : 160,
                sortable : true,
                dataIndex: 'description'
            }
        ],
//        stripeRows: true,
//        height: 355,
        layout:'fit',
//        width: 600,
        bbar: new Ext.ux.grid.TotalCountBar({
            store:devicesStore
            ,displayInfo:true
        }),
        //title: 'Array Grid',
        // config options for stateful behavior

        tbar: [{
                    html: "&nbsp"
                },{
                    //================= EDIÇÃO =================
                    text: __('Add'),
                    icon: 'images/table_add.png',
                    cls: 'x-btn-text-icon',
                    ref: '../addBtn',
                    scope: this,
//                    disabled: true,
                    handler: function() {
//                        console.log(Server.Devices);
                        var editor = new Server.Devices.Editor({
                            
//                        var editor = new Server.Devices.Add({
//                            title   : <?php echo json_encode(__('Create Mailbox')) ?>,
//                            domain  : grid.domain.getValue(),
//                            maxQuota: grid.domainObj.server_quota,//this.maxQuota,    //TODO: alterar isto para dar info do server
//                            service_id: grid.service_id,
//                            parent_grid: grid,
//                            changeFreeMb: grid.changeFreeMb
                            devs_tab: this
                            ,server_id: this.server_id
                            ,listeners:{
                                close: function(){
//                                    serverDevicesGrid.store.reload(); 
                                }
                                ,scope: this
                            }

                        });

                        editor.show();
                    }
                }
                ,{
                    //================= APAGAR =================
                text: <?php echo json_encode(__('Delete')) ?>,
                disabled: true,
                ref: '../removeBtn',
                icon: 'images/table_delete.png',
                cls: 'x-btn-text-icon',
                scope: this,
                handler: function() {
                    var sm = serverDevicesGrid.getSelectionModel();

                    if(sm.hasSelection()){
                        sel = sm.getSelections();
                        var store = serverDevicesGrid.getStore();
                        store.remove(sel);
                    }
            
//////////////////// DELETE NOW - do not delete, could be usefull ///////////////////                
//                    var sm = serverDevicesGrid.getSelectionModel();
//                    sel = sm.getSelected();
//
//                    if(sm.hasSelection()){
//
//                        //============ Confirmation question ===========
//                        Ext.Msg.show({
//                            title: <?php echo json_encode(__('Warning')) ?>,
//                            msg: <?php echo json_encode(__('Do you want to dettach the selected device?')) ?>, //+sel.get('user_name')
//                            buttons: {
//                                    yes: true,
//                                    no: true
//                            },
//                            icon: Ext.MessageBox.INFO,
//                            scope:this,
//                            fn: function(btn) {
//                                    switch(btn){
//                                        case 'yes':
//
//                                            //PEDIDO AJAX =====> REMOVE DOMINIO E os ALIASes
//                                            var conn = new Ext.data.Connection({
//                                                listeners:{
//                                                // wait message.....
//                                                    beforerequest:function(){
//                                                        Ext.MessageBox.show({
//                                                            title: <?php echo json_encode(__('Please wait')) ?>,
//                                                            msg: <?php echo json_encode(__('Dettaching device...')) ?>,
//                                                            width:300,
//                                                            wait:true,
//                                                            modal: false
//                                                        });
//                                                    },// on request complete hide message
//                                                    requestcomplete:function(){Ext.MessageBox.hide();}
//                                                }
//                                            });// end conn
//
//                                            var params = {
//                                                'sid'       : this.server_id,
//                                                'idvendor'  : sel.get('idvendor'),
//                                                'idproduct' : sel.get('idproduct')
//                                            }
//
//                                            conn.request({
//                                                scope:this,
//                                                url: <?php echo json_encode(url_for('server/jsonRemoveDevice'))?>,
//                                                params: params,
//                                                failure: function(resp,opt){
//                                                    if(!resp.responseText){
//                                                        Ext.ux.Logger.error(resp.statusText);
//                                                        return;
//                                                    }
//
//                                                    var response = Ext.util.JSON.decode(resp.responseText);
//                                                    Ext.MessageBox.alert(<?php echo json_encode(__('Error Message'))?>, response['info']);
//                                                    Ext.ux.Logger.error(response['error']);
//
//                                                },
//                                                success: function(resp,opt){
//                                                    var msg; 
//                                                    Ext.ux.Logger.info(msg);
//                                                    var response = Ext.util.JSON.decode(resp.responseText);
//                                                    Ext.ux.Logger.info(response['agent'], response['info']);
//                                                    serverDevicesGrid.store.reload();
//                                            },scope:this
//                                         });// END Ajax request
//                                            break;
//                                        case 'no':
//                                            Ext.Msg.alert(<?php echo json_encode(__('Warning')) ?>,<?php echo json_encode(__('Operation canceled!')) ?>);
//                                            break;
//                                    }
//                            }
//                        });
//                    }
                }
                },{
			xtype: 'tbfill'		//separa metendo os componentes à direita
		}
                ]
        });

        devicesStore.reload();

        serverDevicesGrid.getSelectionModel().on({
            selectionchange:{
                scope:this,
                fn:function(sm){
                    if(sm.getCount() < 1){
                        serverDevicesGrid.removeBtn.setDisabled(true);
                    }else{
                        serverDevicesGrid.removeBtn.setDisabled(false);
                    }
                }
            }
        });

        var config = {
                scope:this
                ,items:[
                    serverDevicesGrid
                ]
        }; // eo config object

        // apply config
        Ext.apply(this, Ext.apply(this.initialConfig, config));

        // call parent
        Server.ManageDevices.superclass.initComponent.apply(this, arguments);
    } // eo function initComponent
});
// register component
Ext.reg('server_managedevices', Server.ManageDevices);    

//});
</script>
