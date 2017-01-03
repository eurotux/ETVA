<script>

Ext.QuickTips.init();
Ext.ns('Network');

Network.ManageInterfacesGrid = Ext.extend(Ext.grid.EditorGridPanel,{
    selectItem_msg:<?php echo json_encode(__('Network interface from grid must be selected!')) ?>,
    initComponent:function(){

        var mac_vlan_record = Ext.data.Record.create([{name: 'mac', type: 'string'},
                                                      {name: 'vlan'},{name:'intf_model'}]);

        if(!this.level){
            this.level = 'server';
            this.treenode_id = this.server_id;
        }else if(this.level == 'node'){
            this.treenode_id = this.node_id;
        }else if(this.level == 'server'){
            this.treenode_id = this.server_id;


        }else if(this.level == 'cluster'){      //changed from this.cluster
            this.treenode_id = this.cluster_id;
        }


        var storeVlansCombo = new Ext.data.JsonStore({
                                root:'data'
                                ,totalProperty:'total'
                                ,baseParams:{id:this.treenode_id, level:this.level}
                                ,fields:[
                                    {name:'id', type:'string'}
                                    ,{name:'name', type:'string'}]
                                ,url:<?php echo json_encode(url_for('vlan/jsonList'))?>});


        var queryServer = {'server_id':this.server_id};
        // create the data store to retrieve network data
        var store_networks = new Ext.data.JsonStore({
                        proxy: new Ext.data.HttpProxy({url:<?php echo json_encode(url_for('network/jsonGridNoPager')); ?>}),
                        baseParams: {'query': Ext.encode(queryServer), 'sort':'port', 'dir':'asc'},
                        totalProperty: 'total',
                        root: 'data',
                        fields: [
                                {name:'vlan',mapping:'Vlan'},
                                {name:'vlan_id',mapping:'VlanId'},
                                {name:'mac',mapping:'Mac'},
                                {name:'intf_model',mapping:'IntfModel'},
                                {name:'id',mapping:'Id'}],
                        remoteSort: false});

        var model_cb = new Ext.form.ComboBox({                                        
                    triggerAction: 'all',
                    clearFilterOnReset:false,
                    lastQuery:'',
                    store: new Ext.data.ArrayStore({
                            fields: ['type','value', 'name'],
                            data : <?php
                                        /*
                                         * build interfaces model dynamic
                                         */
                                        $interfaces_drivers = sfConfig::get('app_interfaces');
                                        $interfaces_elem = array();

                                        foreach($interfaces_drivers as $hyper =>$models)
                                            foreach($models as $model)
                                                $interfaces_elem[] = '['.json_encode($hyper).','.json_encode($model).','.json_encode($model).']';
                                                echo '['.implode(',',$interfaces_elem).']'."\n";
                                    ?>
                            }),
                    displayField:'name',
                    mode:'local',                    
                    valueField: 'value',
                    forceSelection: true
                });
                
        model_cb.getStore().filter('type',this.vm_type);
                
        var queryMacNotInUse = {'in_use':0};

        var storeMacNoInUse = new Ext.data.JsonStore({
                                    url:<?php echo json_encode(url_for('mac/jsonGridQueryAll'))?>,
                                    baseParams: {'query': Ext.encode(queryMacNotInUse)},
                                    totalProperty: 'total',
                                    root: 'data',
                                    fields: [{name:'mac',mapping:'Mac'},{name:'in_use',mapping:'InUse'}],
                                    remoteSort: false
                                });
        var mac_cb = new Ext.form.ComboBox({
                            //editable:true,
                            typeAhead: true,
                            selectOnFocus: true,
                            triggerAction:'all',
                            forceSelection:true,
                            enableKeyEvents:true,
                            displayField:'mac',
                            lazyRender:true,
                            listClass: 'x-combo-list-small',
                            //allQuery: Ext.encode(queryMacNotInUse),     // default all query
                            queryParam: 'mac',
                            listeners: {
                                select:{scope:this,fn:function(combo,record,index){
                                    var record_ = this.getSelectionModel().getSelected();                            
                                    console.log(record);
                                    record_.set('mac', record.data['mac']);

                                }}
                            },// end listeners
                            displayField:'mac',
                            valueField: 'mac',
                            store: storeMacNoInUse
                });
        //mac_cb.getStore().filter('in_use','0');
        //mac_cb.getStore().query('in_use','0');
        /*mac_cb.getStore().filterBy(function(record){
                                        console.log(record);
                                        if( record.data['in_use'] == 0 ){
                                            return true;
                                        } else {
                                            return false;
                                        }
                                    });*/

        var mac_vlan_cm = new Ext.grid.ColumnModel([
            new Ext.grid.RowNumberer(),
            {
                id:'mac',
                header: "MAC Address",
                dataIndex: 'mac',
                fixed:true,
                editable: true,
                renderer:function(value,meta,rec){
                    if(!value){ return String.format('<b>{0}</b>',<?php echo json_encode(__('Select network...')) ?>);}
                    else{ rec.commit(true); return value;}
                },
                editor: mac_cb,
                allowBlank: false,
                width: 120,
                renderer: function(val){return '<span ext:qtip="'+__('Drag and Drop to reorder')+'">' + val + '</span>';}
            },
            {
                header: "Network",
                dataIndex: 'vlan',
                width: 130,
                allowBlank: false,
                renderer:function(value,meta,rec){
                    if(!value){ return String.format('<b>{0}</b>',<?php echo json_encode(__('Select network...')) ?>);}
                    else{ rec.commit(true); return value;}
                },
                editor: new Ext.form.ComboBox({
                    typeAhead: true,
                    editable:false,
                    triggerAction: 'all',
                    store:storeVlansCombo,
                    displayField:'name',
                    lazyRender:true,
                    listClass: 'x-combo-list-small',
                    bbar:new Ext.ux.grid.TotalCountBar({
                            store:storeVlansCombo
                            ,displayInfo:true
                    }),
                    //scope:this,
                    listeners: {
                        select:{scope:this,fn:function(combo,record,index){
                            var record_ = this.getSelectionModel().getSelected();                            
                            record_.set('vlan', record.data['name']);
                            record_.set('vlan_id', record.data['id']);

                        }}
                    }// end listeners
                    //,scope:this
                })
                //,scope:this
            },
            {                
                header: "Model",
                dataIndex: 'intf_model',
                fixed:true,
                hidden:this.vm_type=='pv',
                allowBlank: false,
                width: 160,
                editable:this.vm_type!='pv',
                editor: this.vm_type=='pv' ? '' : model_cb,
                renderer:function(value,meta,rec){                    
                    if(!value && this.editable){ return String.format('<b>{0}</b>',<?php echo json_encode(__('Select model...')) ?>);}
                    else{ rec.commit(true); return value;}
                }                
            }
        ]);// end mac_vlan columnmodel

        var url_macCreateWin = 'mac/createwin';
        if( this.server_id ){
            url_macCreateWin += '?sid='+this.server_id;
        } else {
            url_macCreateWin += '?cid='+this.treenode_id;
        }
        // hard coded - cannot be changed from outsid
        var config = {
                scope:this,
                store:store_networks,
              //  autoScroll: true,
               // layout:'fit',
                ddGroup: 'testDDGroup',
                enableDragDrop: true,
                cm: mac_vlan_cm,
             //   width:440,
             //   height:200,
                autoExpandColumn:'mac',
                viewConfig:{
                    forceFit:true,
                    emptyText: __('Empty!'),  //  emptyText Message
                    deferEmptyText:false
                },
                clicksToEdit:2,
                sm: new Ext.grid.RowSelectionModel({
                    singleSelect: true,
                    moveEditorOnEnter:false
                }),
                tbar: [
                {
                    //adds new mac from poll
                    text: <?php echo json_encode(__('Add interface')) ?>,
                    ref:'../addBtn',
                    iconCls:'add',
                    scope:this,
                    handler : function(button,event){

                        button.setDisabled(true);

                        var conn = new Ext.data.Connection();
                        conn.request({
                            url: 'mac/jsonGetUnused',
                            scope: this,
                            success:function(resp,options) {
                                var response = Ext.util.JSON.decode(resp.responseText);
                                var new_mac = response['Mac'];
                                var new_record = new mac_vlan_record({mac: new_mac,vlan: '',intf_model:''});

                                var n = this.getStore().getTotalCount();
                                this.getStore().insert(n, new_record);
                                this.getView().refresh();
                                this.getSelectionModel().selectRow(n, true);
                                button.setDisabled(false);

                                // enable boot from network

                                var bootlocation = Ext.getCmp('server-edit-config-boot-locationurl');
                                if(bootlocation && !bootlocation.hidden){
                                    bootlocation.setDisabled(false);
                                    if(bootlocation.getValue() == true){
                                        var bootlocationurl = Ext.getCmp('server-edit-config-boot-locationurl-text');
                                        bootlocationurl.setDisabled(false);
				    }
                                }else{
                                    var bootpxe = Ext.getCmp('server-edit-config-boot-pxe');
                                    if(bootpxe && !bootpxe.hidden)
                                        bootpxe.setDisabled(false);
                                }
                            },
                            failure: function(resp,opt) {

                                button.setDisabled(false);
                                var response = Ext.util.JSON.decode(resp.responseText);

                                Ext.ux.Logger.error(response['agent'], response['error']);

                                Ext.Msg.show({title: <?php echo json_encode(__('Error!')) ?>,
                                    buttons: Ext.MessageBox.OK,
                                    msg: response['error'],
                                    icon: Ext.MessageBox.ERROR});
                            }
                        });// end ajax request
                    }// end handler
                },// end button
                {
                    text: <?php echo json_encode(__('Edit interface')) ?>,
                    iconCls:'icon-edit-record',
                    tooltip:this.selectItem_msg,
                    ref:'.../editBtn',
                    disabled:true,
                    scope:this,
                    handler : function(){
                        var record = this.getSelectionModel().getSelected();
                        if (!record) {return;}
                        this.stopEditing();
                        var index = this.store.indexOf(record);                        
                        this.startEditing(index,2);                        
                    }
                },
                {
                    text: <?php echo json_encode(__('Remove interface')) ?>,
                    iconCls:'remove',
                    tooltip:this.selectItem_msg,
                    ref:'.../removeBtn',
                    disabled:true,
                    scope:this,
                    handler : function(){
                        var record = this.getSelectionModel().getSelected();

                        if (!record) {return;}
                        this.getStore().remove(record);
                        this.getView().refresh();

                        // if there are no interfaces disable boot from pxe/network
                        if(this.getStore().getCount() == 0){
                            var showmsg = false;
                            var bootlocation = Ext.getCmp('server-edit-config-boot-locationurl');
                            if(bootlocation && !bootlocation.hidden){
                                var bootlocationurl = Ext.getCmp('server-edit-config-boot-locationurl-text');
                                bootlocationurl.setDisabled(true);
                                bootlocation.setDisabled(true);
                                if(bootlocation.getValue() == true){
                                    showmsg = true;
                                    bootlocation.setValue(false);
                                    var bootfs = Ext.getCmp('server-edit-config-boot-vmfilesystem');
                                    bootfs.setValue(true);
                                }
                            }else{
                                var bootpxe = Ext.getCmp('server-edit-config-boot-pxe');
                                if(bootpxe && !bootpxe.hidden){
                                    bootpxe.disable();
                                    if(bootpxe.getValue() == true){
					bootpxe.setValue(false);
                                        var bootfs = Ext.getCmp('server-edit-config-boot-vmfilesystem');
                                        bootfs.setValue(true);
                                        showmsg = true;
                                    }    
                                }
                            }
                           
                            if(showmsg){
                                var tabpanel = Ext.getCmp('server-edit-tabpanel');
                                tabpanel.setActiveTab(1);
            
                                Ext.Msg.show({
                                    title: this.text,
                                    buttons: Ext.MessageBox.OK,
                                    icon: Ext.MessageBox.INFO,
                                    msg: <?php echo json_encode(__('Boot from the network was disabled. </br> Please confirm if the boot settings are correct.')) ?>
                                });
                            }
                        }
                        
                    }
                },'-',
                {
                    text: __('Move up'),
                    iconCls:'icon-up',
                    tooltip:this.selectItem_msg,
                    ref:'.../upBtn',
                    disabled:true,
                    scope:this,
                    handler : function(){
                        new Grid.util.RowMoveSelected(this,-1);}
                },
                {
                    text: __('Move down'),
                    iconCls:'icon-down',
                    tooltip:this.selectItem_msg,
                    ref:'.../downBtn',
                    disabled:true,
                    scope:this,
                    handler : function(){
                        new Grid.util.RowMoveSelected(this,1);}
                }
                ]
                ,bbar:['->',
                    {text: <?php echo json_encode(__('MAC Pool Management')) ?>,
                        url: url_macCreateWin,
                        handler: View.clickHandler
                        ,scope:this
                    }
                    <?php if($sf_user->getAttribute('etvamodel')!='standard'): ?>
                    ,'-',
                    {
                        text: <?php echo json_encode(__('Add network')) ?>,
                        iconCls: 'icon-add',
                        url: <?php echo json_encode(url_for('vlan/Vlan_CreateForm')); ?>,
                        call:'Vlan.Create',
                        callback:function(item){
                            var grid = (item.ownerCt).ownerCt;
//                            ,baseParams:{id:this.server_id, level:'server'}

                            // get cluster id to manage networks
                            var send_data = {'level':'server', 'id':grid.server_id};
                            var conn = new Ext.data.Connection();// end conn

                            conn.request({
                                    url: <?php echo json_encode(url_for('cluster/jsonGetId'))?>,
                                    params: send_data,
                                    scope:this,
                                    success: function(resp,opt) {

                                        var response = Ext.util.JSON.decode(resp.responseText);
                                        var txt = response['cluster_id'];

                                        var win = new Vlan.Create({
                                                title:item.text
                                                ,cluster_id:txt
                                                ,listeners:{
                                                    onVlanCancel:function(){
                                                        win.close();
                                                    }
                                                    ,onVlanSuccess:function(){
                                                        win.close();
                                                    },
                                                    onVlanFailure:function(){
                                                        win.close();
                                                    }
                                                }});
                                        win.show();

                                    },
                                    failure: function(resp,opt) {
                                    }
                                });// END Ajax request

                        },
                        handler:View.loadComponent
                    }// END Add button
                    <?php endif; ?>
                  ]
//                ,listeners:{
//                    beforerender:function(){
//                        Ext.getBody().mask(<?php //echo json_encode(__('Loading network interfaces...')) ?>);
//                    }
//                    ,render:{delay:100,fn:function(){
//                        Ext.getBody().unmask();
//                }}
//        }

        }; // eo config object

        // apply config
        Ext.apply(this, Ext.apply(this.initialConfig, config));

        // call parent
        Network.ManageInterfacesGrid.superclass.initComponent.apply(this, arguments);


        this.getSelectionModel().on({selectionchange:{scope:this,fn:function(sm){
            var btnState = sm.getCount() < 1 ? true :false;
            var selected = sm.getSelected();

            this.editBtn.setTooltip(btnState ? this.selectItem_msg : '');
            this.editBtn.setDisabled(btnState);

            this.removeBtn.setTooltip(btnState ? this.selectItem_msg : '');
            this.removeBtn.setDisabled(btnState);

            this.upBtn.setTooltip(btnState ? this.selectItem_msg : '');
            this.upBtn.setDisabled(btnState);

            this.downBtn.setTooltip(btnState ? this.selectItem_msg : '');
            this.downBtn.setDisabled(btnState);

        }}});



        this.on({
            render: function(g) {
                
                // Best to create the drop target after render, so we don't need to worry about whether grid.el is null

                // constructor parameters:
                //    grid (required): GridPanel or EditorGridPanel (with enableDragDrop set to true and optionally a value specified for ddGroup, which defaults to 'GridDD')
                //    config (optional): config object
                // valid config params:
                //    anything accepted by DropTarget
                //    listeners: listeners object. There are 4 valid listeners, all listed in the example below
                //    copy: boolean. Determines whether to move (false) or copy (true) the row(s) (defaults to false for move)
                this.ddrow = new Ext.ux.dd.GridReorderDropTarget(g, {
                    copy: false
                    ,listeners: {
                        beforerowmove: function(objThis, oldIndex, newIndex, records) {
                            // code goes here
                            // return false to cancel the move
                        }
                        ,afterrowmove: function(objThis, oldIndex, newIndex, records) {
                            g.getView().refresh();
                            // code goes here
                        }
                        ,beforerowcopy: function(objThis, oldIndex, newIndex, records) {
                            // code goes here
                            // return false to cancel the copy
                        }
                        ,afterrowcopy: function(objThis, oldIndex, newIndex, records) {
                            // code goes here
                        }
                    }
                });

                // if you need scrolling, register the grid view's scroller with the scroll manager
                Ext.dd.ScrollManager.register(g.getView().getEditorParent());
            }
            ,beforedestroy: function(g) {
                
                this.ddrow.target.destroy();                
                // if you previously registered with the scroll manager, unregister it (if you don't it will lead to problems in IE)
                Ext.dd.ScrollManager.unregister(g.getView().getEditorParent());
            }});        

    } // eo function initComponent
    ,isCellValid:function(col, row) {

        var record = this.store.getAt(row);
        if(!record) {
            return true;
        }

        var field = this.colModel.getDataIndex(col);                
        if(this.vm_type == 'pv' && field == 'intf_model') return true;
        
        if(!record.data[field]) return false;
        return true;
    },
    isValid:function(editInvalid) {
        var cols = this.colModel.getColumnCount();
        var rows = this.store.getCount();
        if(rows==0) return true;

        var r, c;
        var valid = true;
        for(r = 0; r < rows; r++) {
            for(c = 1; c < cols; c++) {
                valid = this.isCellValid(c, r);
                if(!valid) {
                    break;
                }
            }
            if(!valid) {
                break;
            }
        }
        return valid;
    }
    ,onRender:function() {

        // call parent
        Network.ManageInterfacesGrid.superclass.onRender.apply(this, arguments);

        //this.store.load.defer(200,this.store);

    } // eo function onRender
    ,save:function(){

        var networks=[];
        var nets_store = this.getStore();
        var i = 0;

        nets_store.each(function(f){

                var data = f.data;
                var insert = {
                    'port':i,
                    'vlan':data['vlan'],
                    'mac':data['mac']};

                networks.push(insert);
                i++;
        });


        var conn = new Ext.data.Connection({
            listeners:{
                // wait message.....
                beforerequest:function(){
                    Ext.MessageBox.show({
                        title: <?php echo json_encode(__('Please wait...')) ?>,
                        msg: <?php echo json_encode(__('Processing interfaces...')) ?>,
                        width:300,
                        wait:true,
                        modal: true
                    });
                },// on request complete hide message
                requestcomplete:function(){Ext.MessageBox.hide();}
                ,requestexception:function(c,r,o){
                                    Ext.MessageBox.hide();
                                    Ext.Ajax.fireEvent('requestexception',c,r,o);}
            }
        });// end conn
        conn.request({
            url: <?php echo json_encode(url_for('network/jsonReplace')) ?>,
            params:{'sid':this.server_id,'networks': Ext.encode(networks)},
            scope: this,
            success: function(resp,options) {

                var response = Ext.util.JSON.decode(resp.responseText);

                Ext.ux.Logger.info(response['agent'], response['response']);

                if(this.ownerCt) this.ownerCt.fireEvent('onManageInterfacesSuccess');
            }
            ,
            failure: function(resp,opt) {

                var response = Ext.util.JSON.decode(resp.responseText);
                Ext.ux.Logger.error(response['agent'], response['error']);

                Ext.Msg.show({
                        title: String.format(<?php echo json_encode(__('Error {0}')) ?>,response['agent']),
                        buttons: Ext.MessageBox.OK,
                        msg: String.format(<?php echo json_encode(__('Unable to attach/detach interfaces for server {0}!')) ?>+'<br> {1}',this.server_name,response['info']),
                        icon: Ext.MessageBox.ERROR});

            }
        }); // END Ajax request


    }
});
// register component
Ext.reg('network_manageinterfacesgrid', Network.ManageInterfacesGrid);    

//});
</script>
