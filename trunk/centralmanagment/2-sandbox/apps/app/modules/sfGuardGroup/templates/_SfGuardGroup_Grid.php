<script>
Ext.ns("SfGuardGroup");

SfGuardGroup.Grid = Ext.extend(Ext.grid.GridPanel, {
    border:false,
    autoScroll:true,
    stripeRows: true,
    viewConfig:{
        //forceFit:true,
        emptyText: __('Empty!'),  //  emptyText Message
        deferEmptyText:false
    },
    loadMask: {msg: <?php echo json_encode(__('Retrieving data...')) ?>},
    initComponent:function(){

        this.sm = new Ext.grid.RowSelectionModel({singleSelect:true});

        //listener to activate/deactivate buttons depending on how many rows are selected
        this.sm.on('selectionchange', function(sm){

            if(sm.getSelections().length > 0)
            {
                var selected = sm.getSelected().data['id'];                

                if(selected==this.defaultId){
                    this.delBtn.disable();
                    this.delBtn.setTooltip(String.format(<?php echo json_encode(__('CANNOT REMOVE DEFAULT GROUP ID {0}')) ?>,this.defaultId));
                }
                else{
                    this.delBtn.enable();
                    this.delBtn.setTooltip('');
                }

                this.editBtn.enable();


            }else{
                this.editBtn.disable();
                this.delBtn.disable();
            }

        }, this);

        this.cm = new Ext.grid.ColumnModel([
                    {header: "Id", dataIndex: 'id',width:40},
                    {header: "Name", width: 200, sortable: true, dataIndex: 'name',renderer: function (value, metadata, record, rowIndex, colIndex, store) {                                
                                metadata.attr = String.format('ext:qtip="{0}"',<?php echo json_encode(__('Right-click to open sub-menu or double-click to edit')) ?>);
                                return value;
                            }},
                    {header: "Description", id:'description', dataIndex: 'description', width: 160, sortable: true}
        ]);

        this.autoExpandColumn = 'description';

        this.store = new Ext.data.JsonStore({
            proxy: new Ext.data.HttpProxy({url: <?php echo json_encode(url_for('sfGuardGroup/jsonGridWithPerms')); ?>}),
            id: 'Id',
            totalProperty: 'total',
            root: 'data',
            fields: [{name:'id',type:'int',mapping:'Id'},
                     {name:'name',mapping:'Name'},
                     {name:'description',mapping:'Description'},
                     'sf_guard_group_permission_list'],
            sortInfo: { field: 'name',
            direction: 'ASC' },
            remoteSort: true
        });

        //this.store.load.defer(20,this.store,[{params:{start:0,limit:10}}]);

        this.bbar = new Ext.PagingToolbar({
                    store: this.store,
                    displayInfo:true,
                    pageSize:10
                    ,
                    plugins:new Ext.ux.Andrie.pPageSize({comboCfg: {width: 50}})
        });


        this.tbar = [{
                    text: <?php echo json_encode(__('Add group')) ?>,
                    tooltip: <?php echo json_encode(__('Add new group')) ?>,
                    iconCls: 'icon-add',
                    scope:this,
                    handler: function() {
                        this.fireEvent('onAdd');
                    }// END Add handler
                   }// END Add button
                   ,
                   {
                    text: <?php echo json_encode(__('Edit group')) ?>,
                    ref:'../editBtn',
                    disabled:true,
                    tooltip: <?php echo json_encode(__('Edit selected group')) ?>,
                    iconCls: 'icon-edit-record',
                    scope:this,
                    handler: this.doEdit
                   },
                   {
                    text: <?php echo json_encode(__('Remove group')) ?>,
                    tooltip: <?php echo json_encode(__('Remove selected group')) ?>,
                    iconCls: 'icon-remove',
                    ref:'../delBtn',
                    scope:this,
                    disabled:true,
                    handler: this.doDelete
                   }// END Remove button
                   ,'->',{
                        xtype: 'panel',
                        baseCls: '',
                        tools:[{id:'help', qtip: __('Help'),handler:function(){View.showHelp({anchorid:'help-sfguard-group-main',autoLoad:{ params:'mod=sfGuardGroup'},title: <?php echo json_encode(__('Groups Management Help')) ?>});}}]
                    }
        ];// END tbar
        

        this.keys = [{
                    key: Ext.EventObject.DELETE,
                    fn: this.doDelete,
                    scope:this
        }];

        SfGuardGroup.Grid.superclass.initComponent.call(this);

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
                        text: <?php echo json_encode(__('Edit selected group')) ?>,
                        iconCls:'icon-edit-record',
                        handler:function(){grid.doEdit();}
		        },                
                {
			        text: <?php echo json_encode(__('Remove selected group')) ?>,
                    ref:'remove_group',                                        
                    iconCls:'icon-remove',
			        handler:function(){grid.doDelete();}
			    }]
		    });
		}
		e.stopEvent();
        var gid = grid.getStore().getAt(rowIndex).data.id;
        this.menu.remove_group.setDisabled(gid==this.defaultId);
        
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
                title: <?php echo json_encode(__('Remove group')) ?>,
                buttons: Ext.MessageBox.YESNO,
                msg: String.format(<?php echo json_encode(__('Remove group {0} ?')) ?>,sel.data.name),
                scope:this,
                fn: function(btn){
                    if (btn == 'yes'){
                        var conn = new Ext.data.Connection({
                                listeners:{
                                    // wait message.....
                                    beforerequest:function(){
                                        Ext.MessageBox.show({
                                            title: <?php echo json_encode(__('Please wait...')) ?>,
                                            msg: <?php echo json_encode(__('Removing group...')) ?>,
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
                            url: 'sfGuardGroup/jsonDelete',
                            scope:this,
                            params: {id: sel.id},
                            success: function(resp,opt){
                                this.fireEvent('onRemove');

                            },
                            failure: function(resp,opt) {
                                Ext.Msg.alert(<?php echo json_encode(__('Error!')) ?>,
                                <?php echo json_encode(__('Unable to remove group')) ?>);
                            }
                        });// END Ajax request
                    }//END button==yes
                }// END fn
            }); //END Msg.show
        }
     }
});
</script>
