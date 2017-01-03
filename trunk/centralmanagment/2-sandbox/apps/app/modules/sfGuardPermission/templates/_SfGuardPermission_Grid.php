<script>
Ext.ns("SfGuardPermission");

SfGuardPermission.Grid = Ext.extend(Ext.grid.GridPanel,{
    border: false,
    loadMask: {msg: <?php echo json_encode(__('Retrieving data...')) ?>},
    viewConfig:{
        emptyText: __('Empty!'),  //  emptyText Message
        deferEmptyText:false
    },
    autoScroll:true,
    stripeRows:true,    
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
                    {header: "Name", width: 150, sortable: true, dataIndex: 'name',renderer: function (value, metadata, record, rowIndex, colIndex, store) {
                                metadata.attr = String.format('ext:qtip="{0}"',<?php echo json_encode(__('Right-click to open sub-menu or double-click to edit')) ?>);
                                return value;
                            }},
                    {header: "Description", id:'description', dataIndex: 'description', width: 120, sortable: true},
                    {header: "Type", id:'perm_type', dataIndex: 'perm_type', width: 90, sortable: true}
        ]);
        
        this.autoExpandColumn = 'description';

        this.store = new Ext.data.JsonStore({
            proxy: new Ext.data.HttpProxy({url: 'sfGuardPermission/JsonGridGroupsClustersVms/action' }), //<php echo json_encode(url_for('sfGuardPermission/jsonGridWithGroups')) //'sfGuardPermission/JsonGridGroupsClustersVms ?>'
            id: 'Id',
            totalProperty: 'total',
            root: 'data',
            fields: [{name:'id',type:'int',mapping:'Id'}
                ,{name:'name',mapping:'Name'},
                ,{name:'description',mapping:'Description'}
                ,{name:'permission_id',mapping:'PermissionId'}
                ,{name:'perm_type',mapping:'PermType'}
                ,'etva_permission_group_list'
                ,'etva_permission_server_list'
                ,'etva_permission_cluster_list'
                ,'etva_permission_user_list'
            ]
            ,sortInfo: { field: 'name',
            direction: 'DESC' },
            remoteSort: false
        });

        this.bbar = new Ext.PagingToolbar({
                    store: this.store,
                    displayInfo:true,
                    pageSize:10
                    ,
                    plugins:new Ext.ux.Andrie.pPageSize({comboCfg: {width: 50}})
        });

        this.tbar = [{
                    text: <?php echo json_encode(__('Add permission')) ?>,
                    tooltip: <?php echo json_encode(__('Add new permission')) ?>,
                    iconCls: 'icon-add',
                    scope:this,
                    handler: function() {
                        this.fireEvent('onAdd');
                    }// END Add handler
                   }// END Add button
                   ,
                   {
                    text: <?php echo json_encode(__('Edit permission')) ?>,
                    ref:'../editBtn',
                    disabled:true,
                    tooltip: <?php echo json_encode(__('Edit selected permission')) ?>,
                    iconCls: 'icon-edit-record',
                    scope:this,
                    handler: this.doEdit
                   },
                   {
                    text: <?php echo json_encode(__('Remove permission')) ?>,
                    tooltip: <?php echo json_encode(__('Remove selected permission')) ?>,
                    iconCls: 'icon-remove',
                    ref:'../delBtn',
                    scope:this,
                    disabled:true,
                    handler: this.doDelete
                   }// END Remove button
                   ,'->',{
                        xtype: 'panel',
                        baseCls: '',
                        tools:[{id:'help', qtip: __('Help'),handler:function(){View.showHelp({anchorid:'help-sfguard-permission-main',autoLoad:{ params:'mod=sfGuardPermission'},title: <?php echo json_encode(__('Permissions Management Help')) ?>});}}]
                    }
        ];// END tbar


        this.keys = [{
                    key: Ext.EventObject.DELETE,
                    fn: this.doDelete,
                    scope:this
        }];                  
       

        SfGuardPermission.Grid.superclass.initComponent.call(this);

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
                        text: <?php echo json_encode(__('Edit selected permission')) ?>,                        
                        iconCls:'icon-edit-record',
                        handler:function(){grid.doEdit();}
		        },{
			        text: <?php echo json_encode(__('Remove selected permission')) ?>,
                    iconCls:'icon-remove',
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
                title: <?php echo json_encode(__('Remove permission')) ?>,
                buttons: Ext.MessageBox.YESNO,
                scope:this,
                msg: String.format(<?php echo json_encode(__('Remove permission {0} ?')) ?>,sel.data.name),
                fn: function(btn){

                    if (btn == 'yes'){

                        if(sel.phantom){
                            this.store.reload();
                            return;
                        }
                        
                        var conn = new Ext.data.Connection({
                                listeners:{
                                // wait message.....
                                beforerequest:function(){
                                    Ext.MessageBox.show({
                                        title: <?php echo json_encode(__('Please wait...')) ?>,
                                        msg: <?php echo json_encode(__('Removing permission...')) ?>,
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
                            url: 'sfGuardPermission/jsonDelete',
                            scope:this,
                            params: {id: sel.id},
                            success: function(resp,opt){
                                this.fireEvent('onRemove');
                            },
                            failure: function(resp,opt) {
                                Ext.Msg.alert(<?php echo json_encode(__('Error!')) ?>,
                                <?php echo json_encode(__('Unable to remove permission')) ?>);
                            }
                        });// END Ajax request


                    }//END button==yes
                }// END fn
            }); //END Msg.show
        }//END if
    }
});

</script>