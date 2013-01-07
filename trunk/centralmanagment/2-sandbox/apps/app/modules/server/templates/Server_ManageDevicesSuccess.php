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
                dataIndex: 'bus'
            }
            ,{
                header   : <?php echo json_encode(__('Slot')) ?>,
                width    : 10,
                sortable : true,
                dataIndex: 'slot'
            }
            ,{
                header   : <?php echo json_encode(__('Function')) ?>,
                width    : 15,
                sortable : true,
                dataIndex: 'function'
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

//        var mac_vlan_record = Ext.data.Record.create([{name: 'mac', type: 'string'},
//                                                      {name: 'vlan'},{name:'intf_model'}]);
//
//        if(!this.level){
//            this.level = 'server';
//            this.treenode_id = this.server_id;
//        }else if(this.level == 'node'){
//            this.treenode_id = this.node_id;
//        }else if(this.level == 'server'){
//            this.treenode_id = this.server_id;
//
//
//        }else if(this.level == 'cluster'){      //changed from this.cluster
//            this.treenode_id = this.cluster_id;
//        }
//
//
//        var storeVlansCombo = new Ext.data.JsonStore({
//                                root:'data'
//                                ,totalProperty:'total'
//                                ,baseParams:{id:this.treenode_id, level:this.level}
//                                ,fields:[
//                                    {name:'id', type:'string'}
//                                    ,{name:'name', type:'string'}]
//                                ,url:<?php echo json_encode(url_for('vlan/jsonList'))?>});
//
//
//        var queryServer = {'server_id':this.server_id};
//        // create the data store to retrieve network data
//        var store_networks = new Ext.data.JsonStore({
//                        proxy: new Ext.data.HttpProxy({url:<?php echo json_encode(url_for('network/jsonGridNoPager')); ?>}),
//                        baseParams: {'query': Ext.encode(queryServer), 'sort':'port', 'dir':'asc'},
//                        totalProperty: 'total',
//                        root: 'data',
//                        fields: [
//                                {name:'vlan',mapping:'Vlan'},
//                                {name:'vlan_id',mapping:'VlanId'},
//                                {name:'mac',mapping:'Mac'},
//                                {name:'intf_model',mapping:'IntfModel'},
//                                {name:'id',mapping:'Id'}],
//                        remoteSort: false});
//
//        var model_cb = new Ext.form.ComboBox({                                        
//                    triggerAction: 'all',
//                    clearFilterOnReset:false,
//                    lastQuery:'',
//                    store: new Ext.data.ArrayStore({
//                            fields: ['type','value', 'name'],
//                            data : <?php
//                                        /*
//                                         * build interfaces model dynamic
//                                         */
//                                        $interfaces_drivers = sfConfig::get('app_interfaces');
//                                        $interfaces_elem = array();
//
//                                        foreach($interfaces_drivers as $hyper =>$models)
//                                            foreach($models as $model)
//                                                $interfaces_elem[] = '['.json_encode($hyper).','.json_encode($model).','.json_encode($model).']';
//                                                echo '['.implode(',',$interfaces_elem).']'."\n";
//                                    ?>
//                            }),
//                    displayField:'name',
//                    mode:'local',                    
//                    valueField: 'value',
//                    forceSelection: true
//                });
//                
//        model_cb.getStore().filter('type',this.vm_type);
//                
//        var mac_vlan_cm = new Ext.grid.ColumnModel([
//            new Ext.grid.RowNumberer(),
//            {
//                id:'mac',
//                header: "MAC Address",
//                dataIndex: 'mac',
//                fixed:true,
//                allowBlank: false,
//                width: 120,
//                renderer: function(val){return '<span ext:qtip="'+__('Drag and Drop to reorder')+'">' + val + '</span>';}
//            },
//            {
//                header: "Network",
//                dataIndex: 'vlan',
//                width: 130,
//                renderer:function(value,meta,rec){
//                    if(!value){ return String.format('<b>{0}</b>',<?php echo json_encode(__('Select network...')) ?>);}
//                    else{ rec.commit(true); return value;}
//                },
//                editor: new Ext.form.ComboBox({
//                    typeAhead: true,
//                    editable:false,
//                    triggerAction: 'all',
//                    store:storeVlansCombo,
//                    displayField:'name',
//                    lazyRender:true,
//                    listClass: 'x-combo-list-small',
//                    bbar:new Ext.ux.grid.TotalCountBar({
//                            store:storeVlansCombo
//                            ,displayInfo:true
//                    }),
//                    //scope:this,
//                    listeners: {
//                        select:{scope:this,fn:function(combo,record,index){
//                            var record_ = this.getSelectionModel().getSelected();                            
//                            record_.set('vlan', record.data['name']);
//                            record_.set('vlan_id', record.data['id']);
//
//                        }}
//                    }// end listeners
//                    //,scope:this
//                })
//                //,scope:this
//            },
//            {                
//                header: "Model",
//                dataIndex: 'intf_model',
//                fixed:true,
//                hidden:this.vm_type=='pv',
//                allowBlank: false,
//                width: 160,
//                editable:this.vm_type!='pv',
//                editor: this.vm_type=='pv' ? '' : model_cb,
//                renderer:function(value,meta,rec){                    
//                    if(!value && this.editable){ return String.format('<b>{0}</b>',<?php echo json_encode(__('Select model...')) ?>);}
//                    else{ rec.commit(true); return value;}
//                }                
//            }
//        ]);// end mac_vlan columnmodel
//
//        // hard coded - cannot be changed from outsid
        var config = {
                scope:this
                ,items:[
//                    iconCls:'go-action',
//                    text: <?php echo json_encode(__('Type')) ?>,
//                    ref:'lvadd',
//                    scope: this
//                    //,handler:this.devtype
//                },{
//                    iconCls:'go-action',
//                    text: <?php echo json_encode(__('Type')) ?>,
//                    ref:'lvadd',
//                    scope: this
//                    //,handler:this.devlist
//                    
//                }
//                ,{
                    serverDevicesGrid
                
                ]
//                store:store_networks,
//              //  autoScroll: true,
//               // layout:'fit',
//                ddGroup: 'testDDGroup',
//                enableDragDrop: true,
//                cm: mac_vlan_cm,
//             //   width:440,
//             //   height:200,
//                autoExpandColumn:'mac',
//                viewConfig:{
//                    forceFit:true,
//                    emptyText: __('Empty!'),  //  emptyText Message
//                    deferEmptyText:false
//                },
//                clicksToEdit:2,
//                sm: new Ext.grid.RowSelectionModel({
//                    singleSelect: true,
//                    moveEditorOnEnter:false
//                }),
//                tbar: [
//                {
//                    //adds new mac from poll
//                    text: <?php echo json_encode(__('Add interface')) ?>,
//                    ref:'../addBtn',
//                    iconCls:'add',
//                    scope:this,
//                    handler : function(button,event){
//
//                        button.setDisabled(true);
//
//                        var conn = new Ext.data.Connection();
//                        conn.request({
//                            url: 'mac/jsonGetUnused',
//                            scope: this,
//                            success:function(resp,options) {
//                                var response = Ext.util.JSON.decode(resp.responseText);
//                                var new_mac = response['Mac'];
//                                var new_record = new mac_vlan_record({mac: new_mac,vlan: '',intf_model:''});
//
//                                this.getStore().insert(0, new_record);
//                                this.getView().refresh();
//                                this.getSelectionModel().selectRow(0, true);
//                                button.setDisabled(false);
//
//                            },
//                            failure: function(resp,opt) {
//
//                                button.setDisabled(false);
//                                var response = Ext.util.JSON.decode(resp.responseText);
//
//                                Ext.ux.Logger.error(response['agent'], response['error']);
//
//                                Ext.Msg.show({title: <?php echo json_encode(__('Error!')) ?>,
//                                    buttons: Ext.MessageBox.OK,
//                                    msg: response['error'],
//                                    icon: Ext.MessageBox.ERROR});
//                            }
//                        });// end ajax request
//                    }// end handler
//                },// end button
//                {
//                    text: <?php echo json_encode(__('Edit interface')) ?>,
//                    iconCls:'icon-edit-record',
//                    tooltip:this.selectItem_msg,
//                    ref:'.../editBtn',
//                    disabled:true,
//                    scope:this,
//                    handler : function(){
//                        var record = this.getSelectionModel().getSelected();
//                        if (!record) {return;}
//                        this.stopEditing();
//                        var index = this.store.indexOf(record);                        
//                        this.startEditing(index,2);                        
//                    }
//                },
//                {
//                    text: <?php echo json_encode(__('Remove interface')) ?>,
//                    iconCls:'remove',
//                    tooltip:this.selectItem_msg,
//                    ref:'.../removeBtn',
//                    disabled:true,
//                    scope:this,
//                    handler : function(){
//                        var record = this.getSelectionModel().getSelected();
//
//                        if (!record) {return;}
//                        this.getStore().remove(record);
//                        this.getView().refresh();
//                    }
//                },'-',
//                {
//                    text: __('Move up'),
//                    iconCls:'icon-up',
//                    tooltip:this.selectItem_msg,
//                    ref:'.../upBtn',
//                    disabled:true,
//                    scope:this,
//                    handler : function(){
//                        new Grid.util.RowMoveSelected(this,-1);}
//                },
//                {
//                    text: __('Move down'),
//                    iconCls:'icon-down',
//                    tooltip:this.selectItem_msg,
//                    ref:'.../downBtn',
//                    disabled:true,
//                    scope:this,
//                    handler : function(){
//                        new Grid.util.RowMoveSelected(this,1);}
//                }
//                ]
//                ,bbar:['->',
//                    {text: <?php echo json_encode(__('MAC Pool Management')) ?>,
//                        url:'mac/createwin?sid='+this.server_id,
//                        handler: View.clickHandler
//                        ,scope:this
////                        params: {'cid': this.treenode_id}
//                    }
//                    <?php if($sf_user->getAttribute('etvamodel')!='standard'): ?>
//                    ,'-',
//                    {
//                        text: <?php echo json_encode(__('Add network')) ?>,
//                        iconCls: 'icon-add',
//                        url: <?php echo json_encode(url_for('vlan/Vlan_CreateForm')); ?>,
//                        call:'Vlan.Create',
//                        callback:function(item){
//                            var grid = (item.ownerCt).ownerCt;
////                            ,baseParams:{id:this.server_id, level:'server'}
//
//                            // get cluster id to manage networks
//                            var send_data = {'level':'server', 'id':grid.server_id};
//                            var conn = new Ext.data.Connection();// end conn
//
//                            conn.request({
//                                    url: <?php echo json_encode(url_for('cluster/jsonGetId'))?>,
//                                    params: send_data,
//                                    scope:this,
//                                    success: function(resp,opt) {
//
//                                        var response = Ext.util.JSON.decode(resp.responseText);
//                                        var txt = response['cluster_id'];
//
//                                        var win = new Vlan.Create({
//                                                title:item.text
//                                                ,cluster_id:txt
//                                                ,listeners:{
//                                                    onVlanCancel:function(){
//                                                        win.close();
//                                                    }
//                                                    ,onVlanSuccess:function(){
//                                                        win.close();
//                                                    },
//                                                    onVlanFailure:function(){
//                                                        win.close();
//                                                    }
//                                                }});
//                                        win.show();
//
//                                    },
//                                    failure: function(resp,opt) {
//                                    }
//                                });// END Ajax request
//
//                        },
//                        handler:View.loadComponent
//                    }// END Add button
//                    <?php endif; ?>
//                  ]
////                ,listeners:{
////                    beforerender:function(){
////                        Ext.getBody().mask(<?php //echo json_encode(__('Loading network interfaces...')) ?>);
////                    }
////                    ,render:{delay:100,fn:function(){
////                        Ext.getBody().unmask();
////                }}
////        }
//
        }; // eo config object

        // apply config
        Ext.apply(this, Ext.apply(this.initialConfig, config));

        // call parent
        Server.ManageDevices.superclass.initComponent.apply(this, arguments);


//        this.getSelectionModel().on({
//            selectionchange:{
//                scope:this,
//                fn:function(sm){
//                    var btnState = sm.getCount() < 1 ? true :false;
//                    var selected = sm.getSelected();
//        
//                    this.editBtn.setTooltip(btnState ? this.selectItem_msg : '');
//                    this.editBtn.setDisabled(btnState);
//        
//                    this.removeBtn.setTooltip(btnState ? this.selectItem_msg : '');
//                    this.removeBtn.setDisabled(btnState);
//        
//                    this.upBtn.setTooltip(btnState ? this.selectItem_msg : '');
//                    this.upBtn.setDisabled(btnState);
//        
//                    this.downBtn.setTooltip(btnState ? this.selectItem_msg : '');
//                    this.downBtn.setDisabled(btnState);
//        
//                }
//            }
//        });



//        this.on({
//            render: function(g) {
//                
//                // Best to create the drop target after render, so we don't need to worry about whether grid.el is null
//
//                // constructor parameters:
//                //    grid (required): GridPanel or EditorGridPanel (with enableDragDrop set to true and optionally a value specified for ddGroup, which defaults to 'GridDD')
//                //    config (optional): config object
//                // valid config params:
//                //    anything accepted by DropTarget
//                //    listeners: listeners object. There are 4 valid listeners, all listed in the example below
//                //    copy: boolean. Determines whether to move (false) or copy (true) the row(s) (defaults to false for move)
//                this.ddrow = new Ext.ux.dd.GridReorderDropTarget(g, {
//                    copy: false
//                    ,listeners: {
//                        beforerowmove: function(objThis, oldIndex, newIndex, records) {
//                            // code goes here
//                            // return false to cancel the move
//                        }
//                        ,afterrowmove: function(objThis, oldIndex, newIndex, records) {
//                            g.getView().refresh();
//                            // code goes here
//                        }
//                        ,beforerowcopy: function(objThis, oldIndex, newIndex, records) {
//                            // code goes here
//                            // return false to cancel the copy
//                        }
//                        ,afterrowcopy: function(objThis, oldIndex, newIndex, records) {
//                            // code goes here
//                        }
//                    }
//                });
//
//                // if you need scrolling, register the grid view's scroller with the scroll manager
//                Ext.dd.ScrollManager.register(g.getView().getEditorParent());
//            }
//            ,beforedestroy: function(g) {
//                
//                this.ddrow.target.destroy();                
//                // if you previously registered with the scroll manager, unregister it (if you don't it will lead to problems in IE)
//                Ext.dd.ScrollManager.unregister(g.getView().getEditorParent());
//            }});        
//
    } // eo function initComponent
//    ,isCellValid:function(col, row) {
//
//        var record = this.store.getAt(row);
//        if(!record) {
//            return true;
//        }
//
//        var field = this.colModel.getDataIndex(col);                
//        if(this.vm_type == 'pv' && field == 'intf_model') return true;
//        
//        if(!record.data[field]) return false;
//        return true;
//    },
//    isValid:function(editInvalid) {
//        var cols = this.colModel.getColumnCount();
//        var rows = this.store.getCount();
//        if(rows==0) return false;
//
//        var r, c;
//        var valid = true;
//        for(r = 0; r < rows; r++) {
//            for(c = 1; c < cols; c++) {
//                valid = this.isCellValid(c, r);
//                if(!valid) {
//                    break;
//                }
//            }
//            if(!valid) {
//                break;
//            }
//        }
//        return valid;
//    }
//    ,onRender:function() {
//
//        // call parent
//        Server.ManageDevices.superclass.onRender.apply(this, arguments);
//
//        //this.store.load.defer(200,this.store);
//
//    } // eo function onRender
//    ,save:function(){
//
//        var networks=[];
//        var nets_store = this.getStore();
//        var i = 0;
//
//        nets_store.each(function(f){
//
//                var data = f.data;
//                var insert = {
//                    'port':i,
//                    'vlan':data['vlan'],
//                    'mac':data['mac']};
//
//                networks.push(insert);
//                i++;
//        });
//
//
//        var conn = new Ext.data.Connection({
//            listeners:{
//                // wait message.....
//                beforerequest:function(){
//                    Ext.MessageBox.show({
//                        title: <?php echo json_encode(__('Please wait...')) ?>,
//                        msg: <?php echo json_encode(__('Processing interfaces...')) ?>,
//                        width:300,
//                        wait:true,
//                        modal: true
//                    });
//                },// on request complete hide message
//                requestcomplete:function(){Ext.MessageBox.hide();}
//                ,requestexception:function(c,r,o){
//                                    Ext.MessageBox.hide();
//                                    Ext.Ajax.fireEvent('requestexception',c,r,o);}
//            }
//        });// end conn
//        conn.request({
//            url: <?php echo json_encode(url_for('network/jsonReplace')) ?>,
//            params:{'sid':this.server_id,'networks': Ext.encode(networks)},
//            scope: this,
//            success: function(resp,options) {
//
//                var response = Ext.util.JSON.decode(resp.responseText);
//
//                Ext.ux.Logger.info(response['agent'], response['response']);
//
//                if(this.ownerCt) this.ownerCt.fireEvent('onManageInterfacesSuccess');
//            }
//            ,
//            failure: function(resp,opt) {
//
//                var response = Ext.util.JSON.decode(resp.responseText);
//                Ext.ux.Logger.error(response['agent'], response['error']);
//
//                Ext.Msg.show({
//                        title: String.format(<?php echo json_encode(__('Error {0}')) ?>,response['agent']),
//                        buttons: Ext.MessageBox.OK,
//                        msg: String.format(<?php echo json_encode(__('Unable to attach/detach interfaces for server {0}!')) ?>+'<br> {1}',this.server_name,response['info']),
//                        icon: Ext.MessageBox.ERROR});
//
//            }
//        }); // END Ajax request
//    }

});
// register component
Ext.reg('server_managedevices', Server.ManageDevices);    

//});
</script>
