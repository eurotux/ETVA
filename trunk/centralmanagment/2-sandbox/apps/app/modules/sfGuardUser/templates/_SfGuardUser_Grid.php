<script>
Ext.ns("SfGuardUser");

SfGuardUser.Grid = Ext.extend(Ext.grid.GridPanel, {
    border:false,
    autoScroll:true,
    stripeRows: true,    
    viewConfig:{
        //forceFit:true,
        emptyText: __('Empty!'),  //  emptyText Message
        deferEmptyText:false
    },
    loadMask: {msg: <?php echo json_encode(__('Retrieving data...')) ?>},
    renderDate:function(value){

        if(value)
            return Date.parseDate(value,"Y-m-d h:i:s").format('dS M Y, H:i:s');
    },
    initComponent:function(){
        
        this.sm = new Ext.grid.RowSelectionModel({singleSelect:true});

        //listener to activate/deactivate buttons depending on how many rows are selected
        this.sm.on('selectionchange', function(sm){

            if(sm.getSelections().length > 0)
            {
                this.editBtn.enable();
                this.delBtn.enable();

            }else{
                this.editBtn.disable();
                this.delBtn.disable();
            }
            
        }, this);

        this.cm = new Ext.grid.ColumnModel([                    
                    {header: "Id", dataIndex: 'id',width:40},
                    {header: "User Name", id:'username', width: 200, sortable: true, dataIndex: 'username',renderer: function (value, metadata, record, rowIndex, colIndex, store) {
                                metadata.attr = String.format('ext:qtip="{0}"',<?php echo json_encode(__('Right-click to open sub-menu or double-click to edit')) ?>);
                                return value;
                            }},
                    {header: "Created At", dataIndex: 'createdAt', width: 140, sortable: true,renderer: this.renderDate},
                    {header: "Last Login", dataIndex: 'lastLogin', width: 140, sortable: true,renderer: this.renderDate},
                    {header: "Active", dataIndex: 'isActive', width: 60, sortable: true},
                    {header: "Super Admin", dataIndex: 'isSuperAdmin', width: 80, sortable: true}
        ]);
        
        this.autoExpandColumn = 'username';
                
        this.store = new Ext.data.JsonStore({
            proxy: new Ext.data.HttpProxy({url: <?php echo json_encode(url_for('sfGuardUser/jsonGrid')); ?>}),
            id: 'Id',
            totalProperty: 'total',
            root: 'data',
            fields: [{name:'id',type:'int',mapping:'Id'},
                     {name:'username',mapping:'Username'},
                     {name:'createdAt',mapping:'CreatedAt'},
                     {name:'lastLogin',mapping:'LastLogin'},
                     {name:'isActive',mapping:'IsActive'},
                     {name:'isSuperAdmin',mapping:'IsSuperAdmin'}
                 ],
            sortInfo: { field: 'username',
            direction: 'ASC' },
            remoteSort: true            
        });        

        // for filter
        var filters = new Ext.ux.grid.GridFilters({
            filters:[
                {type: 'string',  dataIndex: 'username'}
            ]});


        this.bbar = new Ext.PagingToolbar({
                    store: this.store,
                    displayInfo:true,
                    pageSize:10,
                    plugins: [new Ext.ux.Andrie.pPageSize({comboCfg: {width: 50}}), filters]
        });
        
        this.plugins = filters;
        
        this.tbar = [{
                        text: <?php echo json_encode(__('Add user')) ?>,
                        tooltip: <?php echo json_encode(__('Add user account')) ?>,
                        scope:this,
                        iconCls:'icon-user-add',
                        handler: function() {
                            this.fireEvent('onAdd');
                        }// END Add handler
                    },'-',
                    {                       
                        text: <?php echo json_encode(__('Edit user')) ?>,
                        tooltip: <?php echo json_encode(__('Edit selected user')) ?>,
                        iconCls:'icon-user-edit',
                        ref:'../editBtn',
                        disabled:true,
                        scope:this,
                        handler: this.doEdit
                    },'-',
                    {                        
                        text: <?php echo json_encode(__('Remove user')) ?>,
                        tooltip: <?php echo json_encode(__('Remove selected user')) ?>,
                        iconCls:'icon-user-delete',
                        disabled:true,
                        scope:this,
                        ref:'../delBtn',
                        handler: this.doDelete
                    },'->',{
                        xtype: 'panel',
                        baseCls: '',
                        tools:[{id:'help', qtip: __('Help'),handler:function(){View.showHelp({anchorid:'help-sfguard-user-main',autoLoad:{ params:'mod=sfGuardUser'},title: <?php echo json_encode(__('User Management Help')) ?>});}}]
                    }];

        this.keys = [{
                    key: Ext.EventObject.DELETE,
                    fn: this.doDelete,
                    scope:this
        }];

        SfGuardUser.Grid.superclass.initComponent.call(this);

        /************************************************************
        * handle contextmenu event
        ************************************************************/

        this.addListener("rowcontextmenu", this.doContextMenu, this);        


        
     }//Fim init
     ,doContextMenu:function(grid,rowIndex,e){
        grid.getSelectionModel().selectRow(rowIndex);
        if(!this.menu){
                this.menu = new Ext.menu.Menu({                    
                    items: [{
                        text: <?php echo json_encode(__('Edit selected user')) ?>,
                        iconCls:'icon-user-edit',
                        handler:function(){grid.doEdit();}
		        },{			        
                    text: <?php echo json_encode(__('Remove selected user')) ?>,
                    iconCls:'icon-user-delete',
			        handler:function(){grid.doDelete();}
			    }]
		    });
		}
		e.stopEvent();
        this.menu.showAt(e.getXY());
         
     }
     ,doEdit:function(){
        var sm = this.getSelectionModel();
        var sel = sm.getSelected();        
        var index = this.getStore().indexOf(sel);        
        this.fireEvent('rowdblclick',this,index);
     }
     ,doDelete:function(){
        var sm = this.getSelectionModel();
        var sel = sm.getSelected();
        if (sm.hasSelection()){
            Ext.Msg.show({
                title: <?php echo json_encode(__('Remove user')) ?>,
                buttons: Ext.MessageBox.YESNOCANCEL,
                msg: String.format(<?php echo json_encode(__('Remove user {0} ?')) ?>,sel.data.username),
                scope:this,
                fn: function(btn){
                    if (btn == 'yes'){
                        var conn = new Ext.data.Connection({
                                listeners:{
                                    // wait message.....
                                    beforerequest:function(){
                                        Ext.MessageBox.show({
                                            title: <?php echo json_encode(__('Please wait...')) ?>,
                                            msg: <?php echo json_encode(__('Removing user...')) ?>,
                                            width:300,
                                            wait:true,
                                            modal: false
                                        });
                                    },// on request complete hide message
                                    requestcomplete:function(){Ext.MessageBox.hide();}
                                    ,requestexception:function(c,r,o){
                                        Ext.MessageBox.hide();
                                        Ext.Ajax.fireEvent('requestexception',c,r,o);}
                                }
                        });// end conn

                        conn.request({
                            url: 'sfGuardUser/jsonDelete',
                            scope:this,
                            params: {                                
                                id: sel.id
                            },
                            success: function(resp,opt){
                                this.fireEvent('onRemove');

                            },
                            failure: function(resp,opt) {
                                Ext.Msg.alert(<?php echo json_encode(__('Error!')) ?>,
                                <?php echo json_encode(__('Unable to remove user')) ?>);
                            }
                        });// END Ajax request
                    }//END button==yes
                    else{
                        this.getView().focusEl.focus();                        
                    }
                }// END fn
            }); //END Msg.show
        }
     }
});

</script>