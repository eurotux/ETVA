<script>    

Ext.ns('Logicalvol');

Logicalvol.ManageDisksGrid = function(config) {
    Ext.apply(this,config);
	var fields_available = [
                {name:'id', type:'int'}
                ,{name:'lv', type:'string'}
                ,{name:'server_id', type:'int'}
                ,{name:'vm_name', type:'string'}
                ,{name:'in_use', type:'int'}
                ,{name:'size', type:'int'}];

    var fields_selected = [
                {name:'id', type:'int'}
                ,{name:'lv', type:'string'}
                ,{name:'size', type:'int'}
                ,{name:'disk_type', type:'string'}
                ,{name:'pos', type:'int'}];

    // create the data store
    var availableGridStore = new Ext.data.JsonStore({
            proxy: new Ext.data.HttpProxy({url:<?php echo json_encode(url_for('logicalvol/jsonGetAll')); ?>}),
            baseParams: {'nid': this.node_id,'sid':this.server_id},
            fields:fields_available,
            totalProperty: 'total',
            root: 'data',
            listeners:{
                beforeload:{scope:this,fn:function(){
                    var tBar = this.getTopToolbar();
                    tBar.refreshBtn.addClass('x-item-disabled');
                }}
                ,load:{scope:this,fn:function(){
                    var tBar = this.getTopToolbar();
                    tBar.refreshBtn.removeClass('x-item-disabled');

                    /*
                     * filter send on request dont need filter grid where
                     * availableGridStore.filter([ {
                                            fn: function(record){
                                                return record.get('server_id') != this.server_id;
                                            }, scope: this
                                        } ]);*/
                }}
            }
        });

	// Column Model shortcut array
	var cols_available = [
		{header: "ID", width: 40, sortable: true, dataIndex: 'id'},
		{id:'lv', header: "Name", width: 150, sortable: true, dataIndex: 'lv'},
		{header: "Server", width: 150, sortable: true, dataIndex: 'vm_name'},
		{header: "Size", width: 150, sortable: true, dataIndex: 'size',renderer:function(v){
                return Ext.util.Format.fileSize(v);
        }}
	];


    var disk_cb = new Ext.form.ComboBox({
        triggerAction: 'all',
        clearFilterOnReset:false,
        lastQuery:'',
        store: new Ext.data.ArrayStore({                
                fields: ['type','value', 'name'],
                data : <?php
                            /*
                             * build interfaces model dynamic
                             */
                            $disks_drivers = sfConfig::get('app_disks');
                            $disks_elem = array();

                            foreach($disks_drivers as $hyper =>$drivers)
                                foreach($drivers as $driver)
                                    $disks_elem[] = '['.json_encode($hyper).','.json_encode($driver).','.json_encode($driver).']';
                                    echo '['.implode(',',$disks_elem).']'."\n";
                        ?>
                }),
        displayField:'name',
        mode:'local',
        valueField: 'value',
        forceSelection: true
    });
  
    disk_cb.getStore().filter('type',this.vm_type);
    // default disk type 
    var default_disk_type = disk_cb.getStore().getAt(0).data['value'];
    
    var cols_selected = [
        new Ext.grid.RowNumberer(),
		{id:'id', header: "ID", width: 40, sortable: this.vm_state == 'running' ? false : true , dataIndex: 'id'},
        {id:'pos', header: "Pos", width: 40, sortable: this.vm_state == 'running' ? false : true, dataIndex: 'pos', hidden:true},
		{id:'lv', header: "Name", width: 150, sortable: this.vm_state == 'running' ? false : true, dataIndex: 'lv'
         ,renderer: {scope:this,fn:function (value, metadata, record, rowIndex, colIndex, store) {
                                if(this.vm_state == 'running' && record.data['pos']==0) metadata.attr = 'ext:qtip=<?php echo json_encode(__('Cannot make changes to system disk while server is running!')) ?>';
                                return value;
        }}},
		{header: "Size", width: 150, sortable: true, dataIndex: 'size',renderer:function(v){
                return Ext.util.Format.fileSize(v);
        }},
        {
            id:'disk_type',
            header: "Type",
            dataIndex: 'disk_type',
            fixed:true,            
            allowBlank: false,
            width: 150,            
            editor: disk_cb,
            renderer:function(value,meta,rec){
                if(!value){ return String.format('<b>{0}</b>',<?php echo json_encode(__('Select type...')) ?>);}
                else{ rec.commit(true); return value;}
            }
        }        
	];    

// this was to recover the user changes after a resize
//    var dragged_to_selected_id = [];
//    var dragged_to_available_id = [];
    var grids_changed = false;

	// declare the source Grid
    var availableGrid = new Ext.grid.GridPanel({
        ddGroup          : 'selectedDiskGridDDGroup',
        store            : availableGridStore,
        columns          : cols_available,
        loadMask         : true,
        border           : false,
        viewConfig       : {forceFit:true},
        enableDragDrop   : true,
        stripeRows       : true,
        autoExpandColumn : 'lv',
        title            : <?php echo json_encode(__('Available disks')) ?>
    });

    var selectedGridStore = new Ext.data.JsonStore({
            proxy: new Ext.data.HttpProxy({url:<?php echo json_encode(url_for('logicalvol/jsonList')); ?>}),
            baseParams: {'sid': this.server_id},
            fields:fields_selected,
            totalProperty: 'total',
            root: 'data',            
            listeners:{
                beforeload:{scope:this,fn:function(){
                    var tBar = this.getTopToolbar();
                    tBar.refreshBtn.addClass('x-item-disabled');
                }}
                ,load:{scope:this,fn:function(){
                    var tBar = this.getTopToolbar();
                    tBar.refreshBtn.removeClass('x-item-disabled');
                    /*
                     * on store reload make sort by pos
                     */
                    selectedGridStore.setDefaultSort('pos','ASC' );
                    selectedGridStore.sort([{ field: 'pos', direction: 'ASC' }]);

                }}
            }
        });
        

    // create the destination Grid
    var selectedGrid = new Ext.grid.EditorGridPanel({
        id               : this.id+'-selected',
        node_id          : this.node_id,
        level            : 'node',
        border           : false,
        ddGroup          : 'availableDiskGridDDGroup',
        store            : selectedGridStore,
        columns          : cols_selected,
        enableDragDrop   : true,
        loadMask         : true,
        viewConfig       : {forceFit:true},
        stripeRows       : true,
        clicksToEdit:2,
        sm: new Ext.grid.RowSelectionModel({
            singleSelect: true,
            moveEditorOnEnter:false
        }),
        autoExpandColumn : 'lv',
        title            : <?php echo json_encode(__('Attached disks')) ?>
    });

    Ext.apply(selectedGrid,{
        isCellValid:function(col, row) {

            var record = this.store.getAt(row);
            if(!record) {
                return true;
            }

            var field = this.colModel.getDataIndex(col);
            if(!record.data[field] && field!='pos') return false;
            return true;
        },
        isValid:function(editInvalid) {
            var cols = this.colModel.getColumnCount();
            var rows = this.store.getCount();
            if(rows==0) return false;

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
        ,reload: function(){
            selectedGridStore.reload();
            availableGridStore.reload();
        }
        ,lvresize:function() {
            var selModel = selectedGrid.getSelectionModel();
            var selRow = selModel.getSelected();
            if(typeof(selRow) == 'undefined'){
                return;    
            }
            
            var lvname = selRow.data.lv;
            var lvsize = selRow.data.size; 
            var lvid = selRow.data.id;
            var win = Ext.getCmp('lv-resize-win');
            
            // GET VG free space
            var conn = new Ext.data.Connection();// end conn
            
            conn.request({
                url: <?php echo json_encode(url_for('volgroup/jsonGetVg')) ?>,
                params: {lv_id: lvid},
                success: function(resp,opt) {
                    var response = Ext.util.JSON.decode(resp.responseText);
                    var vgfreesize = response['Freesize'];
            
                    var attributes = {
                       vgfreesize:vgfreesize,
                       size:""+lvsize,
                       text:lvname
                    };
            
                    var ctx = new Object();
                    ctx.attributes = attributes;
            
                    if(!win){
                        var centerPanel = new lvwin.resizeForm.Main(selectedGrid.node_id, selectedGrid.level);
                        centerPanel.load(ctx);
            
                        centerPanel.on('updated',function(){
                            win.close();
                            selectedGrid.reload();
                        },
                        this);
            
                        win = new Ext.Window({
                            id: 'lv-resize-win',
                            title: String.format(<?php echo json_encode(__('Resize logical volume {0}')) ?>,ctx.attributes.text),
                            width:360,
                            height:200,
                            iconCls: 'icon-window',
                           // shim:false,
                            animCollapse:false,
                            //     closeAction:'hide',
                            modal:true,
                            border:false,
                            defaultButton:centerPanel.items.get(0).lv_new_size,
                           // constrainHeader:true,
                            layout: 'fit',
                            items: [centerPanel]
                            ,tools: [{
                                id:'help',
                                qtip: __('Help'),
                                handler:function(){
                                    View.showHelp({
                                        anchorid:'help-lvol-rs',
                                        autoLoad:{ params:'mod=logicalvol'},
                                        title: <?php echo json_encode(__('Logical Volume Help')) ?>
                                    });
                                }
                            }]
                        });
                    }
            
                    win.show();
                },
                failure: function(resp,opt) {
                    return;
                }
            });
        }
        ,attachdisk: function(records,sourceGrid){
            Ext.each(records,function(f){

                // enable boot from filesystem option 
                Ext.getCmp('server-edit-config-boot-vmfilesystem').enable();

                var added = false;
                var data = f.data;

                if( !f.data['disk_type'] )  // set default disk type
                    f.data['disk_type'] = default_disk_type;

                if( f.data['in_use'] )
                    Ext.Msg.show({
                        title: String.format(<?php echo json_encode(__('Disk {0} in use')) ?>, f.data['lv']),
                        buttons: Ext.MessageBox.YESNO,
                        scope:this,
                        msg: String.format(<?php echo json_encode(__('The server {0} is using this disk.')) ?>,f.data['vm_name'])+'<br>'
                             +String.format(<?php echo json_encode(__('Do you want add it any way?')) ?>),
                        fn: function(btn){
                            if (btn == 'yes'){
                                sourceGrid.store.remove(f);
                                selectedGrid.store.add(f);
                                added = true;
                            }
                        }
                    });
                else {
                    sourceGrid.store.remove(f);
                    selectedGrid.store.add(f);
                    added = true;
                }

                // this was to recover the user changes after a resize
                if(added){
                    grids_changed = true;
                }
            });
            return true
        }
        ,detachdisk: function(records,sourceGrid,vm_state){
            Ext.each(records,function(f){
                
                // check if there are any disks
                if(sourceGrid.store.getCount() == 1){
                    var showmsg = false;
                    var bootdisk = Ext.getCmp('server-edit-config-boot-vmfilesystem');
                    if(bootdisk){
                        var bootpxe = Ext.getCmp('server-edit-config-boot-pxe');

                        if(bootpxe){
                            if(bootdisk.getValue() == true){
                                bootdisk.setValue(true);
                                var bootcdrom = Ext.getCmp('server-edit-config-boot-cdrom');
                                bootcdrom.setValue(true);
                                showmsg = true;
                            }
                        }else{
                            showmsg = true;
                        }
                        bootdisk.disable();
                    }

                    if(showmsg){
                        Ext.Msg.show({
                            title: this.text,
                            buttons: Ext.MessageBox.OK,
                            icon: Ext.MessageBox.INFO,
                            msg: <?php echo json_encode(__('Boot from filesystem was disabled. </br> Please confirm if the boot options are correct.')) ?>
                        });

                        var tabpanel = Ext.getCmp('server-edit-tabpanel');
                        tabpanel.setActiveTab(0);
                    }
                }else{
                    Ext.getCmp('server-edit-config-boot-vmfilesystem').enable();
                }

                var data = f.data;
                if(f.data['pos']==0 && vm_state == 'running' ){
                    Ext.Msg.show({
                        title: this.text,
                        buttons: Ext.MessageBox.OK,
                        icon: Ext.MessageBox.ERROR,
                        msg: <?php echo json_encode(__('Cannot detach the first disk on running server!')) ?>
                    });
                    return false;
                }
                if(f.data['disk_type']=='ide' && vm_state == 'running' ){
                    Ext.Msg.show({
                        title: this.text,
                        buttons: Ext.MessageBox.OK,
                        icon: Ext.MessageBox.ERROR,
                        msg: <?php echo json_encode(__('Cannot detach the IDE disk on running server!')) ?>
                    });
                    return false;
                }
                sourceGrid.store.remove(f);
                availableGrid.store.add(f);
                grids_changed = true;
            });
            sourceGrid.getView().refresh();
            return true;
        }

    });

    selectedGrid.on('rowcontextmenu', 
        function(grid, rowIndex, e){
            var selModel = this.getSelectionModel();
            selModel.selectRow(rowIndex);

            if(!this.menu){ // create context menu on first right click
                this.menu = new Ext.ux.TooltipMenu({
                    items: [{
                            iconCls:'go-action',
                            ref:'lvresize',
                            text: <?php echo json_encode(__('Resize logical volume')) ?>,
                            scope: this,
                            handler: function(){
                                if(grids_changed){
                                    Ext.MessageBox.show({
                                        title: __('Warning'),
                                        msg: String.format('{0}<br>{1}<br>{2}<br>{3}'
                                                ,<?php echo json_encode(__('You have made changes on the virtual server.')) ?>
                                                ,<?php echo json_encode(__('This changes will be lost during the logical volume resize.')) ?>
                                                ,<?php echo json_encode(__('We recomend to save the changes before proceed.')) ?>
                                                ,<?php echo json_encode(__('Do you still want to proceed?')) ?>)
                                                ,
                                        buttons: Ext.MessageBox.YESNO,
                                        fn: function(btn){
                    
                                            if(btn=='yes'){ 
                                                this.lvresize();
                                            }
                                        },
                                        scope:this,
                                        icon: Ext.MessageBox.WARNING
                                    });
                                }else{
                                    this.lvresize();
                                }
                        }
                    }]
                });
            }
            this.menu.showAt(e.getXY());
        }
        ,selectedGrid);
    selectedGrid.on('beforeedit', function(e){
        if( this.vm_state=='running' ){
            if( e.row==0 ){
                e.cancel=true;
                Ext.Msg.show({
                    title: this.text,
                    buttons: Ext.MessageBox.OK,
                    icon: Ext.MessageBox.ERROR,
                    msg: <?php echo json_encode(__('Cannot edit the first disk on running server!')) ?>
                });
            } else if( (e.field=='disk_type') && (e.record.data.disk_type=='ide') ){
                e.cancel=true;
                Ext.Msg.show({
                    title: this.text,
                    buttons: Ext.MessageBox.OK,
                    icon: Ext.MessageBox.ERROR,
                    msg: <?php echo json_encode(__('Cannot edit the IDE disk on running server!')) ?>
                });
            }
        }
    },this);
    selectedGrid.on('validateedit', function(e){
        if( this.vm_state=='running' ){
            if( e.row==0 ){
                e.cancel=true;
                Ext.Msg.show({
                    title: this.text,
                    buttons: Ext.MessageBox.OK,
                    icon: Ext.MessageBox.ERROR,
                    msg: <?php echo json_encode(__('Cannot edit the first disk on running server')) ?>
                });
            } else if( (e.field=='disk_type') && (e.originalValue=='ide') ){
                e.cancel=true;
                Ext.Msg.show({
                    title: this.text,
                    buttons: Ext.MessageBox.OK,
                    icon: Ext.MessageBox.ERROR,
                    msg: <?php echo json_encode(__('Cannot edit the IDE disk on running server!')) ?>
                });
            } else if( (e.field=='disk_type') && (e.value=='ide') ){
                e.cancel=true;
                Ext.Msg.show({
                    title: this.text,
                    buttons: Ext.MessageBox.OK,
                    icon: Ext.MessageBox.ERROR,
                    msg: <?php echo json_encode(__('Cannot edit disk to set IDE disk type on running server!')) ?>
                });
            }
        }
    },this);

    availableGrid.getSelectionModel().on({selectionchange:{scope:this,fn:function(sm){
        var btnState = sm.getCount() < 1 ? true :false;
        var selected = sm.getSelected();

        this.attachBtn.setTooltip(btnState ? this.selectItem_msg : '');
        this.attachBtn.setDisabled(btnState);

    }}});

    selectedGrid.getSelectionModel().on({selectionchange:{scope:this,fn:function(sm){
        var btnState = sm.getCount() < 1 ? true :false;
        var selected = sm.getSelected();

        this.detachBtn.setTooltip(btnState ? this.selectItem_msg : '');
        this.detachBtn.setDisabled(btnState);

        this.editBtn.setTooltip(btnState ? this.selectItem_msg : '');
        this.editBtn.setDisabled(btnState);

        this.upBtn.setTooltip(btnState ? this.selectItem_msg : '');
        this.upBtn.setDisabled(btnState);

        this.downBtn.setTooltip(btnState ? this.selectItem_msg : '');
        this.downBtn.setDisabled(btnState);

    }}});

    Logicalvol.ManageDisksGrid.superclass.constructor.call(this, {
		layout       : 'column',
        selectItem_msg:<?php echo json_encode(__('Disk from grid must be selected!')) ?>,
        border:false,        
        defaults:{layout:'fit',border:false},
        layoutConfig: {fitHeight: true,split: true},
        items: [{columnWidth:.5,items:[availableGrid]}
                ,{columnWidth:.5,items:[selectedGrid]}],		
		tbar    : [
            {
                text: <?php echo json_encode(__('Attach disk')) ?>,
                iconCls:'icon-drive-add',
                tooltip:this.selectItem_msg,
                ref:'.../attachBtn',
                disabled:true,
                scope:this,
                handler : function(){

                    var records = availableGrid.getSelectionModel().getSelections();
                    if (!records) {return;}

                    return selectedGrid.attachdisk(records,availableGrid);
                }
            },
            {
                text: <?php echo json_encode(__('Detach disk')) ?>,
                iconCls:'icon-drive-delete',
                tooltip:this.selectItem_msg,
                ref:'.../detachBtn',
                disabled:true,
                scope:this,
                handler : function(){

                    var records = selectedGrid.getSelectionModel().getSelections();
                    if (!records) {return;}

                    return selectedGrid.detachdisk(records,selectedGrid,this.vm_state);
                }
            },
			'->', // Fill
            {
                text: <?php echo json_encode(__('Edit disk type')) ?>,
                iconCls:'icon-edit-record',
                tooltip:this.selectItem_msg,
                ref:'.../editBtn',
                disabled:true,
                scope:this,
                handler : function(){
                    var record = selectedGrid.getSelectionModel().getSelected();
                    if (!record) {return;}
                    selectedGrid.stopEditing();
                    var index = selectedGrid.store.indexOf(record);                    
                    selectedGrid.startEditing(index,5);
                }
            },
            {
                text: __('Move up'),
                iconCls:'icon-up',
                tooltip:this.selectItem_msg,
                ref:'.../upBtn',
                disabled:true,
                scope:this,
                handler : function(){

                    var sm = selectedGrid.getSelectionModel();
                    if (sm.hasSelection()){
                        var selected = sm.getSelected();
                        var index = selectedGrid.getStore().indexOf(selected);
                        index--;

                        if (index == 0 && this.vm_state == 'running') return false;
                        else new Grid.util.RowMoveSelected(selectedGrid,-1);
                    }
                }
                
            },
            {
                text: __('Move down'),
                iconCls:'icon-down',
                tooltip:this.selectItem_msg,
                ref:'.../downBtn',
                disabled:true,
                scope:this,
                handler : function(){

                    var sm = selectedGrid.getSelectionModel();
                    if (sm.hasSelection()){
                        var selected = sm.getSelected();
                        if(this.vm_state == 'running' && selected.data['pos']==0) return false;
                        else new Grid.util.RowMoveSelected(selectedGrid,1);
                    }
                }
            },
            {
                text: __('Refresh'),
                xtype: 'button',
                tooltip: 'refresh',
                iconCls: 'x-tbar-loading',
                ref:'refreshBtn',
                //scope:this,
                handler: function(button,event)
                {
                    selectedGridStore.reload();
                    availableGridStore.reload();
                    grids_changed = false;
                }
            }
		]
        ,listeners:{
                beforerender:function(){
                    Ext.getBody().mask(<?php echo json_encode(__('Loading disks...')) ?>);
                }
                ,render:{delay:100,fn:function(){
                    Ext.getBody().unmask();
                }}
            }
    });



        // used to add records to the destination stores
	//var blankRecord =  Ext.data.Record.create(fields);

    /****
    * Setup Drop Targets
    ***/
    // This will make sure we only drop to the  view scroller element

    availableGrid.on({render:{scope:this,fn:function(g){

        g.store.load.defer(100,g.store);

        var vm_state = this.vm_state;
        var availableGridDropTargetEl =  availableGrid.getView().scroller.dom;
        var availableGridDropTarget = new Ext.dd.DropTarget(availableGridDropTargetEl, {
                ddGroup    : 'availableDiskGridDDGroup',
                notifyDrop : function(ddSource, e, data){
                    var records =  ddSource.dragData.selections;
                    return ddSource.grid.detachdisk(records,ddSource.grid,vm_state);
                }                                
        });
        
    }}});

    selectedGrid.on({render:function(g){

        g.store.load.defer(100,this.store);


        // This will make sure we only drop to the view scroller element
        var selectedGridDropTargetEl = selectedGrid.getView().scroller.dom;
        var selectedGridDropTarget = new Ext.dd.DropTarget(selectedGridDropTargetEl, {
                ddGroup    : 'selectedDiskGridDDGroup',
                notifyDrop : function(ddSource, e, data){
                        var records =  ddSource.dragData.selections;
                        //Ext.each(records, ddSource.grid.store.remove, ddSource.grid.store);
                        return selectedGrid.attachdisk(records,ddSource.grid);
                }
        });
    }});

}//eof

// define public methods
Ext.extend(Logicalvol.ManageDisksGrid, Ext.Panel,{
    /* get attached grid */
    getSelected:function(){
       var grid = this.findById(this.id+'-selected');
       return grid;

    }
});


</script>
