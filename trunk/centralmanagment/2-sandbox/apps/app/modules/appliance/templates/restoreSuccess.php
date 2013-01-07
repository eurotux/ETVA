<script>
Ext.ns('Appliance.Restore');



Appliance.Restore.BackupGrid = Ext.extend(Ext.grid.GridPanel, {
	// override
	initComponent : function() {

        var gridStore = new Ext.data.JsonStore({
                autoLoad:true,
                url: <?php echo json_encode(url_for('appliance/jsonListBackup'))?>,
                totalProperty: 'total',
                root: 'data',
                scope:this,
                fields: ['id','url','product_id','file_name','size','date_created','notes','description','mime'],
                listeners:{exception:{scope:this,fn:function(prox, type, action, opt, resp, args){

                        var resp = Ext.decode(resp.responseText);

                        if(resp.action && resp.action=='need_register'){
                            Ext.MessageBox.show({
                                title: <?php echo json_encode(__('Appliance not registered')) ?>,
                                width:300,
                                msg: <?php echo json_encode(__('Need to register Appliance first before restoring backup.<br><br>Register now?')) ?>,
                                buttons: Ext.MessageBox.YESNOCANCEL,
                                fn: function(btn){
                                    if(btn=='yes'){
                                        this.fireEvent('needRegister');                                        
                                    }
                                },
                                scope:this,
                                icon: Ext.MessageBox.WARNING
                            });
                        }
                    }}
                    ,load:{scope:this,fn:function(st,rcs,opts){

                        this.serial_number = st.reader.jsonData.sn;
                        
                    }}
                }

            });
            
		Ext.apply(this, {
			// Pass in a column model definition
			// Note that the DetailPageURL was defined in the record definition but is not used
			// here. That is okay.
	        columns: [	            
	            {header: "Filename", dataIndex: 'file_name', sortable: true},
                {header: "Description", dataIndex: 'description', sortable: true},
                {header: "Size", sortable: true, dataIndex: 'size', align:'right', renderer:function(v){
                    return Ext.util.Format.fileSize(v);
                }},
	            {header: "Date", width:120,dataIndex: 'date_created', sortable: true}
	        ],
			sm: new Ext.grid.RowSelectionModel({singleSelect: true}),
            bbar: new Ext.ux.grid.TotalCountBar({
                                    store: gridStore,
                                    displayInfo:true
            }),
			// Note the use of a storeId, this will register thisStore
			// with the StoreMgr and allow us to retrieve it very easily.
			store: gridStore,
			// force the grid to fit the space which is available
            viewConfig:{
                forceFit:true,
                deferEmptyText:false,
                emptyText: __('Empty!')  //  emptyText Message
            }
		});
		// finally call the superclasses implementation
		Appliance.Restore.BackupGrid.superclass.initComponent.call(this);

        this.addListener("rowcontextmenu", this.onRowContextMenu);
	}
    ,onRowContextMenu:function(grid,index,event){
        event.stopEvent();
        grid.getSelectionModel().selectRow(index);

        if (!this.contextMenu) {
		this.contextMenu = new Ext.ux.TooltipMenu({
			items: [
                    {
                        text: <?php echo json_encode(__('Restore backup')) ?>,
                        iconCls:'icon-restore-go',
                        tooltip: <?php echo json_encode(__('Restore selected backup')) ?>,
                        handler: function(){
                            var selected = grid.getSelectionModel().getSelected();
                            if(selected){

                                Ext.MessageBox.show({
                                    title: <?php echo json_encode(__('Restore backup')) ?>,
                                    msg: String.format(<?php echo json_encode(__('You are about to restore {0}. <br />Are you sure you want to restore?')) ?>,selected.data['file_name']),
                                    buttons: Ext.MessageBox.YESNOCANCEL,
                                    fn: function(btn){

                                        if(btn=='yes')
                                            this.restoreBackup(selected);

                                    },
                                    scope:this,
                                    icon: Ext.MessageBox.NOTICE
                                });


                            }
                        },scope:this
                    },
                    {
                        text: <?php echo json_encode(__('Delete backup')) ?>,
                        tooltip: <?php echo json_encode(__('Delete selected backup')) ?>,
                        iconCls:'remove',                        
                        handler: function(){
                            var selected = grid.getSelectionModel().getSelected();
                            if(selected){

                                Ext.MessageBox.show({
                                    title: <?php echo json_encode(__('Delete backup')) ?>,
                                    msg: String.format(<?php echo json_encode(__('You are about to delete {0}. <br />Are you sure you want to delete?')) ?>,selected.data['file_name']),
                                    buttons: Ext.MessageBox.YESNOCANCEL,
                                    fn: function(btn){

                                        if(btn=='yes')
                                            this.deleteBackup(selected);

                                    },
                                    scope:this,
                                    icon: Ext.MessageBox.QUESTION
                                });


                            } 
                        },scope:this
                    }]
		});
	}
	
        this.contextMenu.showAt(event.getXY());
    }
    ,restoreBackup:function(item){        

        var progressbar = new Ext.ProgressBar({text: <?php echo json_encode(__('Initializing...')) ?>});
        var progress_msg = <?php echo json_encode(__('% completed...')) ?>;

        var serial_number = this.serial_number;        

        
        

        /*
         * run task ever 5 seconds...check upload progress
         */
        var task = {run:function(){                            
                            Ext.getCmp('appliance-restore-notify').toFront();
                            Ext.Ajax.request({
                                url:<?php echo json_encode(url_for('/comm.php/appliance/jsonRestoreProgress'))?>,
                                params:{sn:serial_number},
                                scope:this,                                
                                success: function(r) {
                                            var resp = Ext.decode(r.responseText);
                                            var action = resp.action;                                            

                                            
                                            
                                            if(resp.action == <?php echo json_encode(Appliance::GET_RESTORE) ?>){
                                                var i = resp.percent;
                                                progressbar.updateProgress(i, Math.round(100*i)+progress_msg);
                                                
                                            }
                                            else{
                                                var txt = resp.txt;
                                                if(txt) progressbar.updateText(txt);
                                            }

                                            if(resp.action == <?php echo json_encode(Appliance::VA_COMPLETED) ?>){
                                                Ext.MessageBox.hide();
                                                Ext.TaskMgr.stop(task);
                                            }
                                           
                                }
                            });

            },interval: 5000
        };


        var delay_notification =
                new Ext.util.DelayedTask(function(){
                                        //Ext.MessageBox.hide();
                                        View.notify({
                                            border:false,
                                            id:'appliance-restore-notify',
                                            items:[
                                                {bodyStyle:'background:transparent;',html: <?php echo json_encode(__('Appliance Restore')) ?>},
                                                progressbar
                                            ],
                                            pinState: 'pin',
                                            task:task
                                        });                                        
                                     
                                        Ext.TaskMgr.start(task);
        });
        delay_notification.delay(1000); // start notiy progress task in 1 seconds...


        var send_data = {'method':'restore','backup':item.data['id'],'backup_size':item.data['size']};
        
        var conn = new Ext.data.Connection({
            listeners:{
                // wait message.....
                beforerequest:function(){
                    Ext.MessageBox.show({
                        title: <?php echo json_encode(__('Please wait...')) ?>,
                        msg: <?php echo json_encode(__('Preparing restore configuration...')) ?>,
                        width:300,
                        wait:true,
                        modal: true
                    });                    
                },// on request complete hide message
                requestcomplete:{scope:this,fn:function(){
                    //    Ext.MessageBox.hide();
                    }}
                ,requestexception:function(c,r,o){
                    Ext.Ajax.fireEvent('requestexception',c,r,o);}
            }
        });// end conn

        conn.request({
            url: <?php echo json_encode(url_for('appliance/jsonRestore'))?>,
            params:send_data,
            // everything ok...
            failure:function(response){
                var resp = Ext.decode(response.responseText);
                
                delay_notification.cancel();
                Ext.TaskMgr.stop(task);

                if(resp['action']=='check_nodes')
                {

                    Ext.MessageBox.show({
                        title: <?php echo json_encode(__('Node restore')) ?>,
                        msg: String.format('{0}<br><br>{1}<br>'
                                ,<?php echo json_encode(__('Node reported down. Cannot proceed. Remember to verify node connectivity to Central Management')) ?>
                                ,resp['info']
                                ),
                        buttons: Ext.MessageBox.OK,                        
                        scope:this,
                        icon: Ext.MessageBox.WARNING
                    });

                }else{

                    if(resp['txt']){
                        progressbar.updateText(resp['txt']);
                    }

                    Ext.MessageBox.show({
                            title: String.format(<?php echo json_encode(__('Error {0}')) ?>,resp['agent']),
                            msg: String.format(<?php echo json_encode(__('Could not perform {0} RESTORE.<br> {1}')) ?>,'<?php echo sfConfig::get('config_acronym'); ?>',resp['info']),
                            buttons: Ext.MessageBox.OK,
                            icon: Ext.MessageBox.ERROR
                    });
                }

            }
            ,success: function(resp,opt){
                //this.disableBackup(false);


                
                
//
//                //progressbar.updateProgress(1, Math.round(100)+progress_msg);
//                var response = Ext.util.JSON.decode(resp.responseText);
//                var msg = String.format('Appliance (<b>SN: {0}</b>)<br>Backup success!',response['serial_number']);
//                View.notify({html:msg});
//                this.fireEvent('onSave');


            },scope:this
        });// END Ajax request

    }
    ,deleteBackup:function(item){

        
        var conn = new Ext.data.Connection({
            listeners:{
                // wait message.....
                beforerequest:function(){
                    Ext.MessageBox.show({
                        title: <?php echo json_encode(__('Please wait...')) ?>,
                        msg: <?php echo json_encode(__('Removing backup...')) ?>,                        
                        width:300,
                        wait:true,
                        modal: false
                    });
                },// on request complete hide message
                requestcomplete:function(){Ext.MessageBox.hide();}
            }
        });// end conn

        conn.request({
            url: <?php echo json_encode(url_for('appliance/jsonDelBackup'))?>,
            params:{backup:item.data['id']},
            failure: function(resp,opt){
                var response = Ext.util.JSON.decode(resp.responseText);
//                Ext.MessageBox.alert('Error Message', response['info']);
                Ext.ux.Logger.error(response['error']);

            },
            // everything ok...
            success: function(resp,opt){

                var msg = String.format(<?php echo json_encode(__('Backup {0} deleted')) ?>,item.data['file_name']);
                Ext.ux.Logger.info(msg);
                View.notify({html:msg});

                this.getStore().reload();

            },scope:this

        });// END Ajax request

    }
});
// This will associate an string representation of a class
// (called an xtype) with the Component Manager
// It allows you to support lazy instantiation of your components
Ext.reg('appliance_restore_backupGrid', Appliance.Restore.BackupGrid);





Appliance.Restore.BackupDetail = Ext.extend(Ext.Panel, {
	// add tplMarkup as a new property
	tplMarkup: [		
		'Filename: <a href="{url}">{file_name}</a><br/>',
        'Description: {size}<br/>',        
        'Serial Number: {product_id}</br>',
		'Size: {[Ext.util.Format.fileSize(values.size)]}<br/>',
		'Notes: {notes}<br/>',
        'Content Type: {mime}<br/>'
	],
	// startingMarup as a new property
	startingMarkup: <?php echo json_encode(__('Select a backup to see additional details')) ?>,
	// override initComponent to create and compile the template
	// apply styles to the body of the panel and initialize
	// html to startingMarkup
	initComponent: function() {
		this.tpl = new Ext.XTemplate(this.tplMarkup);
		Ext.apply(this, {
			bodyStyle: {
				background: '#ffffff',
				padding: '7px'
			},
			html: this.startingMarkup
		});
		// call the superclass's initComponent implementation
		Appliance.Restore.BackupDetail.superclass.initComponent.call(this);
	},
	// add a method which updates the details
	updateDetail: function(data) {
		this.tpl.overwrite(this.body, data);
	}
});
// register the Appliance.Restore.BackupDetail class with an xtype of appliance_restore_backupDetail
Ext.reg('appliance_restore_backupDetail', Appliance.Restore.BackupDetail);


Appliance.Restore.Panel = Ext.extend(Ext.Panel, {
	// override initComponent
	initComponent: function() {
		// used applyIf rather than apply so user could
		// override the defaults
		Ext.applyIf(this, {
			frame: true,
			title: <?php echo json_encode(__('Backups List')) ?>,			
            anchor: '100% 100%',
            layout: {
                type: 'vbox',
                align: 'stretch'  // Child items are stretched to full width
            },
            defaults:{layout:'fit'},
			items: [
                {
                    flex:1,
                    items:[{
                            xtype: 'appliance_restore_backupGrid',
                            itemId: 'gridPanel'
                            }]
                },
                {
                    //flex:1,
                    items:[{
                            xtype: 'appliance_restore_backupDetail',
                            itemId: 'detailPanel',
                            height:100}]
                }]
		})
		// call the superclass's initComponent implementation
		Appliance.Restore.Panel.superclass.initComponent.call(this);
	},
	// override initEvents
	initEvents: function() {
		// call the superclass's initEvents implementation
		Appliance.Restore.Panel.superclass.initEvents.call(this);

		// now add application specific events
		// notice we use the selectionmodel's rowselect event rather
		// than a click event from the grid to provide key navigation
		// as well as mouse navigation
        var backupGrid = (this.get(0)).getComponent('gridPanel');
		var backupGridSm = backupGrid.getSelectionModel();
		backupGridSm.on('rowselect', this.onRowSelect, this);

        backupGrid.on({
            'needRegister':{scope:this,fn:function(){this.fireEvent('showRegister');}}
        });

	},
	// add a method called onRowSelect
	// This matches the method signature as defined by the 'rowselect'
	// event defined in Ext.grid.RowSelectionModel
	onRowSelect: function(sm, rowIdx, r) {
		// getComponent will retrieve itemId's or id's. Note that itemId's
		// are scoped locally to this instance of a component to avoid
		// conflicts with the ComponentMgr
		var detailPanel = (this.get(1)).getComponent('detailPanel');
		detailPanel.updateDetail(r.data);
	}
});



Appliance.Restore.Main = function(config) {

    Ext.apply(this, config);

    var p = new Appliance.Restore.Panel();
    p.on('showRegister',function(){this.fireEvent('showRegister');},this);
    
    this.items = p;
        

    Appliance.Restore.Main.superclass.constructor.call(this, {
        layout: 'fit',
        iconCls: 'icon-etva',
        maxW:700,
        maxH:400,
        modal:true,
        border:false
    });

    this.on({
        'show':function(){
                this.resizeFunc();
                //this.items.get(0).loadData();
        },
        'close':function(){
                Ext.EventManager.removeResizeListener(this.resizeFunc,this);
        }
    });

    //on browser resize, resize window
    Ext.EventManager.onWindowResize(this.resizeFunc,this);

};
Ext.extend(Appliance.Restore.Main, Ext.Window,{
    tools:[{id:'help', qtip: __('Help'),handler:function(){View.showHelp({anchorid:'help-appliance-restore',autoLoad:{ params:'mod=appliance'},title: <?php echo json_encode(__('Appliance Restore Help')) ?>});}}]
});

</script>
