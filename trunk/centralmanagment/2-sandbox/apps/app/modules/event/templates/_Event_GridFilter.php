<script>
Ext.ns("Event");
Event.GridFilter = function(app) {
    
    var level_store = [['3','Error'],['6', 'Info']];
    var filterCmb_store = Ext.ux.util.clone(level_store);
    filterCmb_store.push(['3,6','Debug']);
    
    var filterLevelCmb = new Ext.form.ComboBox({
	        emptyText: 'Select level type',
	        resizable: true,	     
	        store: filterCmb_store,triggerAction:'all',
            forceSelection:true,
            value:'3,6',
            mode:'local',width:150});

    var filters = new Ext.ux.grid.GridFilters({
            encode:true,
            filters:[
                {type: 'string',  dataIndex: 'message'},
                {
					type: 'list',
					dataIndex: 'level',
                    options: level_store,
					phpMode: true
                }
            ]
    });

    var store = new Ext.data.JsonStore({
            proxy: new Ext.data.HttpProxy({url: <?php echo json_encode(url_for('event/jsonGrid')); ?>}),
            id: 'Id',
            totalProperty: 'total',
            root: 'data',
            fields: [{name:'id',type:'int',mapping:'Id'},
                     {name:'level',mapping:'Level'},
                     {name:'priority',mapping:'Priority'},
                     {name:'message',mapping:'Message'},
                     {name:'createdAt',mapping:'CreatedAt'}
                 ],
            sortInfo: { field: 'id',
            direction: 'DESC' },
            remoteSort: true
        });

    var grid = new Ext.grid.GridPanel({
            border:false,
            ref:'../grid',
            cls:'gridWrap',
            autoScroll:true,
            stripeRows: true,
            viewConfig:{
                //forceFit:true,
                emptyText: 'Empty!',  //  emptyText Message
                deferEmptyText:false
            },
            loadMask: {msg: 'Retrieving info...'},
            cm:new Ext.grid.ColumnModel([                    
                    {header: "Id", dataIndex: 'id',sortable:true,width:40},
                    {header: "Level", align:'center',dataIndex: 'level',sortable:true,width:80,renderer: function (value, metadata, record, rowIndex, colIndex, store) {
                                switch(record.data.level){
                                    case 6:
                                        metadata.css = 'log-info';
                                        break;
                                    case 3:
                                        metadata.css = 'log-error';
                                        break;
                                    default:
                                        break;
                                }

                                metadata.attr = 'ext:qtip="'+record.data.priority+'"';
                                return '';                                      
                    }},
                    {header: "Message", id:'message', dataIndex: 'message',width:40},
                    {header: "Created At", dataIndex: 'createdAt', width: 140, sortable: true}               
            ])
            ,autoExpandColumn : 'message'
            ,store:store
            ,bbar:[new Ext.PagingToolbar({
                    store: store,
                    displayInfo:true,
                    pageSize:10,
                    plugins: [new Ext.ux.Andrie.pPageSize({comboCfg: {width: 50}}), filters]
            })]
            ,tbar:[filterLevelCmb,{xtype:'button',
                             text: <?php echo json_encode(__('Filter by log level')) ?>,
                             handler:function(){
                                var filter = filters.getFilter('level');
                                var selLevel = filterLevelCmb.getValue().split(',');

                                if(selLevel){
                                    filter.setValue(selLevel);
                                    filter.setActive(true);
                                }else{
                                    filter.setActive(false);
                                }
                            }
                            }]
            ,plugins:filters
    });

    grid.on('filterupdate',function(gfilter, filter){        
            if(filter.dataIndex=='level'){
                if(filter.active){
                    var val = filter.getValue();                    
                    filterLevelCmb.setValue(val);
                }
                else{                  
                  filterLevelCmb.clearValue();
                }
            }
    });
        

    Event.GridFilter.superclass.constructor.call(this, {
        layout: 'border',
        border:false,     
        items:[
            {region:'center',
             margins:'3 3 3 3',
             layout:'fit',            
             items:grid}
        ]
    });
    
};

Ext.extend(Event.GridFilter, Ext.Panel, {
     reload:function(){                  
         this.grid.getStore().reload();
     }
});
</script>