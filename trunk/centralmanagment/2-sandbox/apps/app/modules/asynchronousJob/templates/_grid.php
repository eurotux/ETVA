<?php
include_partial('asynchronousJob/functions');
?>
<script type='text/javascript'>

Ext.namespace('AsynchronousJob.TaskPanel');

AsynchronousJob.TaskGrid = function(config){
    Ext.apply(this,config);

    Ext.TaskMgr.start({
            run: function(){
                this.getStore().reload();
            },
            interval: 5*1000, // each 5 seconds
            scope: this
    });

    AsynchronousJob.TaskGrid.superclass.constructor.call(this, {
        id: 'running-task-grid-panel',
        ref: 'runningTasksGridPanel',
        store: new Ext.data.JsonStore({
            root:'data',
            autoLoad: true,
            proxy: new Ext.data.HttpProxy({
                    url:<?php echo json_encode(url_for('asynchronousJob/list'))?>
                }),
            baseParams: { 'query': Ext.encode([{ 'field': 'updated_at', 'min': (Math.round((new Date()).getTime()/1000) - 5 * 60) }])},
            fields: [
               {name: 'Id' },
               {name: 'type' },
               {name: 'CreatedAt' },
               {name: 'UpdatedAt' },
               {name: 'Tasknamespace'},
               {name: 'Taskname'},
               {name: 'Status'},
               {name: 'Result'}
            ]
        }),
        autoScroll:true,
        stripeRows:true,    
        border: false,
        sm: new Ext.grid.RowSelectionModel({singleSelect:true}),
        flex: 1,
        viewConfig: {
            forceFit: true,
        },
        colModel: new Ext.grid.ColumnModel({
                        columns: [
                            {header: __('Type'), dataIndex: 'type',
                                renderer: function (value, metadata){
                                            if( value=='success' ){
                                                metadata.attr = 'style="background-color: green;color:white;"';
                                            } else if( value=='pending' || value=='waiting' || value=='leased' || value=='queued' ){
                                                metadata.attr = 'style="background-color: yellow;color:black;"';
                                            } else {
                                                metadata.attr = 'style="background-color: red;color:white;"';
                                            }
                                            return __(value);
                                        }
                            },
                            { header:__('Created'), dataIndex: 'CreatedAt', renderer: Ext.util.Format.dateRenderer('M j H:i:s')},
                            { header:__('Updated'), dataIndex: 'UpdatedAt', renderer: Ext.util.Format.dateRenderer('M j H:i:s')},
                            { header:__('Namespace'), dataIndex: 'Tasknamespace'},
                            { header:__('Task'), dataIndex: 'Taskname'},
                            { header:__('Status'), dataIndex: 'Status', renderer: function (value){ return __(value); }},
                            { header:__('Message'), dataIndex: 'Result'}
                        ],
                    })
        ,listeners: {
            'rowcontextmenu': function(grid, rowIndex, e){
                grid.getSelectionModel().selectRow(rowIndex);

                if(!this.menu){ // create context menu on first right click
                    this.menu = new Ext.ux.TooltipMenu({
                        items: [{
                                iconCls:'go-action',
                                ref:'abortTaskBtn',
                                text: __('Abort'),
                                scope: this,
                                handler: function(){
                                    var selRowTask = Ext.getCmp('running-task-grid-panel').getSelectionModel().getSelected();
                                    console.log(selRowTask);
                                    if(typeof(selRowTask) != 'undefined'){
                                        Ext.MessageBox.show({
                                            title: __('Warning'),
                                            msg: String.format('{0}'
                                                    ,<?php echo json_encode(__('Do you pretend abort this task?')) ?>),
                                            buttons: Ext.MessageBox.YESNO,
                                            fn: function(btn){
                                                if(btn=='yes'){ 
                                                    AsynchronousJob.Functions.Abort( { 'id': selRowTask.data.Id } );
                                                }
                                            },
                                            scope:this,
                                            icon: Ext.MessageBox.WARNING
                                        });
                                    }
                            }
                        }]
                    });
                }
                this.menu.showAt(e.getXY());

                var sm = grid.getSelectionModel();
                var sel = sm.getSelected();
    
                //console.log(sel.data);
                
                if(sel.data['Status']=='finished') this.menu.abortTaskBtn.setDisabled(true);
                else if(sel.data['Status']=='aborted' ) this.menu.abortTaskBtn.setDisabled(true);
                else if(sel.data['Status']=='invalid' ) this.menu.abortTaskBtn.setDisabled(true);
                else this.menu.abortTaskBtn.setDisabled(false);


            }
        }
        ,tools:[
                {
                    id: 'refresh',
                    handler: function(e,t,p,tc) {
                        p.getStore().reload();
                    }
                /*},{
                    id: 'plus',
                    handler: function(e,t,p,tc) {
                        AsynchronousJob.Functions.Create( 'node', 'check', {'id':1}, {} );
                    }*/
                },{
                    id:'help',
                    qtip: __('Help'),
                    handler:function(){
                        View.showHelp({ anchorid:'help-bottom-panel-main',
                                        autoLoad:{ params:'mod=view'},
                                        title: <?php echo json_encode(__('Running tasks Help')) ?>});
                    }
                }
        ]
    })
}

Ext.extend(AsynchronousJob.TaskGrid, Ext.grid.GridPanel,{});

Ext.namespace('AsynchronousJob.TaskPanel');

AsynchronousJob.TaskPanel = function(config){

    Ext.apply(this,config);

    AsynchronousJob.TaskPanel.superclass.constructor.call(this, {
        layout:'fit',
        height: 90,
        title: <?php echo json_encode(__('Running tasks')) ?>,
        id:'view-running-task-panel',
        defaults:{border:false},
        closeAction: 'hide',
        collapsible: true,
        autoScroll:true,
        items:[ new AsynchronousJob.TaskGrid() ]
    });
};

Ext.extend(AsynchronousJob.TaskPanel, Ext.Panel,{});

</script>
