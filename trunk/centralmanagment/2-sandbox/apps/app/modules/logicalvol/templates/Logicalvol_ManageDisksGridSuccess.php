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
            baseParams: {'nid': this.node_id},
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
                    availableGridStore.filter([ {
                                            fn: function(record){
                                                return record.get('server_id') != this.server_id;
                                            }, scope: this
                                        } ]);
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

    });

    selectedGrid.getSelectionModel().on({selectionchange:{scope:this,fn:function(sm){
        var btnState = sm.getCount() < 1 ? true :false;
        var selected = sm.getSelected();

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
                    
                    Ext.each(records,function(f){
                        var data = f.data;
                        if(f.data['pos']==0 && vm_state == 'running' ) return false;
                        ddSource.grid.store.remove(f);
                        availableGrid.store.add(f);
                     
                    });
                    ddSource.grid.getView().refresh();
                    //  Ext.each(records, ddSource.grid.store.remove, ddSource.grid.store);
                    //   availableGrid.store.add(records);
                    //   availableGrid.store.sort('lv', 'ASC');
                    return true
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
                        Ext.each(records,function(f){
                            var data = f.data;

                            if( !f.data['disk_type'] )  // set default disk type
                                f.data['disk_type'] = default_disk_type;

                            if( f.data['in_use'] )
                                Ext.Msg.show({
                                    title: String.format(<?php echo json_encode(__('Disk {0} in use')) ?>, f.data['lv']),
                                    buttons: Ext.MessageBox.YESNOCANCEL,
                                    scope:this,
                                    msg: String.format(<?php echo json_encode(__('The server {0} is using this disk.')) ?>,f.data['vm_name'])+'<br>'
                                         +String.format(<?php echo json_encode(__('Do you want add it any way?')) ?>),
                                    fn: function(btn){
                                        if (btn == 'yes'){
                                            ddSource.grid.store.remove(f);
                                            selectedGrid.store.add(f);
                                        }
                                    }
                                });
                            else {
                                ddSource.grid.store.remove(f);
                                selectedGrid.store.add(f);
                            }
                        });
                        //selectedGrid.store.add(records);
                        //selectedGrid.store.sort('lv', 'ASC');
                        return true
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
