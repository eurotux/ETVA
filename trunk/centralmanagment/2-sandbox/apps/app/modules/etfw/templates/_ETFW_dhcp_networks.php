<script>

    Ext.ns('ETFW_DHCP.Networks');
    //
    // create pre-configured grid class
    ETFW_DHCP.Networks.Data_Grid = Ext.extend(Ext.grid.GridPanel, {

        initComponent:function() {

            var selectBoxModel= new Ext.grid.CheckboxSelectionModel({keepSelections:true});



            var store_fields = [];
            var group_field = '';
            var sort_info = '';
            var grid_columns = [];
            switch(this.type){
                case 'subnet':
                    store_fields = ['uuid','address','netmask','args','parent','parent-type','params','option'];
                    group_field = 'netmask';
                    sort_info = {field:'address', direction:'ASC'};
                    grid_columns = [
                        selectBoxModel,
                        {header: "Address", width: 40, sortable: true, dataIndex: 'address',renderer: function (value, metadata, record, rowIndex, colIndex, store) {
                                metadata.attr = 'ext:qtip="Double-click to filter address pool(s) for this subnet"';
                                return value;
                            }}
                        ,{header: "Netmask", width: 20, sortable: true, dataIndex: 'netmask'}
                        ,{header: "Shared network", width: 20, sortable: true, dataIndex: 'parent'}
                    ];

                    this.tbar = [{
                            text:'Add subnet',
                            tooltip:'Add a New Subnet',
                            ref:'../addBtn',
                            iconCls:'add',
                            handler: function(){

                                var form = new ETFW_DHCP.Data_Form({title:'Add Subnet',parent_grid:this,serviceId:this.serviceId});
                                form.reset();
                                // create and show window
                                var win = new Ext.Window({
                                    title:'Subnet information'
                                    ,layout:'fit'
                                    ,width:800
                                    ,modal:true
                                    ,height:420
                                    ,closable:true
                                    ,border:false
                                    ,items:form
                                });
                                win.show();

                            },scope:this
                        },
                        '-',
                        {
                            text:'Edit subnet',
                            ref: '../editBtn',
                            tooltip:'Edit selected subnet',
                            disabled:true,
                            handler:this.editSubnet,
                            scope:this
                        },
                        '-',
                        {
                            text:'Delete subnet(s)',
                            ref: '../removeBtn',
                            tooltip:'Delete the selected subnet(s)',
                            iconCls:'remove',
                            disabled:true,
                            handler: function(){
                                new Grid.util.DeleteItem({panel: this.id});
                            },scope:this
                        }];


                     this.addListener("rowcontextmenu", this.onSubnetRowContextMenu, this);



                    break;
                case 'shared-network':
                    store_fields = ['uuid','name','authoritative_txt','lastcomment','params','option'];
                    group_field = 'authoritative_txt';
                    sort_info = {field:'name', direction:'ASC'};

                    grid_columns = [
                        selectBoxModel,
                        {header: "Name", width: 40, sortable: true, dataIndex: 'name',renderer: function (value, metadata, record, rowIndex, colIndex, store) {
                                metadata.attr = 'ext:qtip="Double-click to filter address pool(s) for this shared network"';
                                return value;
                            }}
                        ,{header: "Server is Authoritative", width: 20, sortable: true, dataIndex: 'authoritative_txt'}
                        ,{header: "Description", width: 20, sortable: true, dataIndex: 'lastcomment'}
                    ];


                    this.tbar = [{
                            text:'Add shared network',
                            tooltip:'Add a new shared network',
                            ref:'../addBtn',
                            iconCls:'add',
                            handler: function(){

                                var form = new ETFW_DHCP.Data_Form({title:'Add Shared Network',parent_grid:this,serviceId:this.serviceId});
                                form.reset();
                                // create and show window
                                var win = new Ext.Window({
                                    title:'Shared network information'
                                    ,layout:'fit'
                                    ,width:800
                                    ,modal:true
                                    ,height:420
                                    ,closable:true
                                    ,border:false
                                    ,items:form
                                });
                                win.show();

                            },scope:this
                        },
                        '-',
                        {
                            text:'Edit shared network',
                            ref: '../editBtn',
                            tooltip:'Edit selected network',
                            disabled:true,
                            handler:this.editShared,
                            scope:this
                        },
                        '-',
                        {
                            text:'Delete shared network',
                            ref: '../removeBtn',
                            tooltip:'Delete the selected network(s)',
                            iconCls:'remove',
                            disabled:true,
                            handler: function(){
                                new Grid.util.DeleteItem({panel: this.id});
                            },scope:this
                        }];


                        this.addListener("rowcontextmenu", this.onSharedRowContextMenu, this);


                    break;
                default:break;
            }

            // configure the grid
            Ext.apply(this, {
                store:new Ext.data.GroupingStore({
                    reader:new Ext.data.JsonReader({
                        //id:'index'
                        totalProperty:'total'
                        ,root:'data'
                        ,fields:store_fields
                    })
                    ,proxy:new Ext.data.HttpProxy({url:this.url})
                    ,baseParams:this.baseParams
                    ,groupField:group_field
                    ,sortInfo:sort_info
                })
                ,columns:grid_columns
                ,sm:selectBoxModel
                ,view: new Ext.grid.GroupingView({
                    forceFit:true
                    ,groupTextTpl:'{text} ({[values.rs.length]} {[values.rs.length > 1 ? "Items" : "Item"]})'
                })
                ,loadMask:true
                ,viewConfig:{forceFit:true}
                ,height:200
                ,autoScroll:true
            }); // eo apply

            // add paging toolbar
            this.bbar = new Ext.ux.grid.TotalCountBar({
                store:this.store
                ,displayInfo:true
            });


            this.on('rowdblclick', function(gridPanel, rowIndex, e) {
                var selected = this.store.data.items[rowIndex];

                var poolgrid = Ext.getCmp(this.containerId+'-pool_grid');


                var filter = poolgrid.filters.getFilter('parent-uuid');
                filter.setValue (selected.data['uuid']);
                filter.setActive(true);

                switch(this.type){
                    case 'subnet': poolgrid.setTitle('Subnet '+selected.data.args);
                        break;
                    case 'shared-network':
                        poolgrid.setTitle('Shared network '+selected.data['name']);
                        break;
                    default:break;
                }

            });

            // call parent
            ETFW_DHCP.Networks.Data_Grid.superclass.initComponent.apply(this, arguments);

            this.getSelectionModel().on('selectionchange', function(sm){
                // this.addBtn.setDisabled(sm.getCount() < 1);
                this.editBtn.setDisabled(sm.getCount() < 1);
                this.removeBtn.setDisabled(sm.getCount() < 1);
            },this);





        } // eo function initComponent
        ,reload:function(){this.store.reload();}
        ,onSubnetRowContextMenu:function(grid, rowIndex, e) {
            grid.getSelectionModel().selectRow(rowIndex);

            if (!this.menu) {
                this.menu = new Ext.menu.Menu({
                    items: [{
                            text:'Edit subnet',
                            tooltip:'Edit subnet information of the selected item',
                            iconCls:'editItem',
                            handler: this.editSubnet,
                            scope:this
                        },{
                            text:'Delete subnet',
                            tooltip:'Delete this subnet',
                            iconCls:'remove',
                            handler: function(){
                                Ext.MessageBox.show({
                                    title:'Delete subnet',
                                    msg: 'You are about to delete this subnet. <br />Are you sure you want to delete?',
                                    buttons: Ext.MessageBox.YESNOCANCEL,
                                    fn: this.onDeleteSubnet,
                                    scope:this,
                                    icon: Ext.MessageBox.QUESTION
                                });

                            },scope:this
                        }]
                });
            }
            this.rowctx = rowIndex;
            e.stopEvent();
            this.menu.showAt(e.getXY());
        }
        ,onSharedRowContextMenu:function(grid, rowIndex, e) {
            grid.getSelectionModel().selectRow(rowIndex);

            if (!this.menu) {
                this.menu = new Ext.menu.Menu({
                    items: [{
                            text:'Edit shared network',
                            tooltip:'Edit network information of the selected item',
                            iconCls:'editItem',
                            handler: this.editShared,
                            scope:this
                        },{
                            text:'Delete shared network',
                            tooltip:'Delete this shared network',
                            iconCls:'remove',
                            handler: function(){
                                Ext.MessageBox.show({
                                    title:'Delete shared network',
                                    msg: 'You are about to delete this shared network. <br />Are you sure you want to delete?',
                                    buttons: Ext.MessageBox.YESNOCANCEL,
                                    fn: this.onDeleteShared,
                                    scope:this,
                                    icon: Ext.MessageBox.QUESTION
                                });

                            },scope:this
                        }]
                });
            }
            this.rowctx = rowIndex;
            e.stopEvent();
            this.menu.showAt(e.getXY());
        }
        ,editSubnet:function(){


            var selected = this.getSelectionModel().getSelected();



            var extra_txt = '<br>';


            var type = '';
            switch(selected.data['parent-type']){
                case 'subnet': type = 'subnet ';
                    break;
                case 'shared-network': type = 'shared network ';
                    break;
                default:break;
            }

            if(type) extra_txt += 'in '+type+selected.data['parent'];
            else extra_txt += '&nbsp';



            var form = new ETFW_DHCP.Data_Form({title:'Edit Subnet'+extra_txt,parent_grid:this,serviceId:this.serviceId,containerId:this.containerId});

            var extra_txt_options = '<br> for subnet '+selected.data['address'];
            var client_options = new ETFW_DHCP.ClientOptions_Form({title:'Client Options'+extra_txt_options,parent_grid:this,serviceId:this.serviceId});
            var record_option= new Object();
            record_option.data = selected.data['option'];

            var leases_params = {'all':1,'netaddr':selected.data['address'],'netmask':selected.data['netmask']};

            var list_leases = new ETFW_DHCP.ListLeases_Grid({
                    title:'DHCP leases <br>in network '+ selected.data['args'],
                    url:this.url,
                    serviceId:this.serviceId,
                    baseParams:{id:this.serviceId,method:'list_leases',params:Ext.encode(leases_params)},
                    xtype:'leasesgrid'});

            var tabs = new Ext.TabPanel({
                activeTab:0,
                items:[form,client_options,list_leases]
            });
            // create and show window
            var win = new Ext.Window({
                title:'Subnet information'
                ,layout:'fit'
                ,width:800
                ,modal:true
                ,height:420
                ,closable:true
                ,border:false
                ,items:tabs
            });
            win.show();


            client_options.loadRecord(selected.data['uuid'],record_option);


            var record= new Object();
            record.data = selected.data['params'];
            form.loadSubnetRecord(selected.data['uuid'],record);



        }
        ,editShared:function(){


            var selected = this.getSelectionModel().getSelected();

            var extra_txt = '<br>&nbsp';

            var form = new ETFW_DHCP.Data_Form({title:'Edit Shared network'+extra_txt,parent_grid:this,serviceId:this.serviceId,containerId:this.containerId});

            var extra_txt_options = '<br> for shared network '+selected.data['name'];
            var client_options = new ETFW_DHCP.ClientOptions_Form({title:'Client Options'+extra_txt_options,parent_grid:this,serviceId:this.serviceId});
            var record_option= new Object();
            record_option.data = selected.data['option'];

            var tabs = new Ext.TabPanel({
                activeTab:0,
                items:[form,client_options]
            });
            // create and show window
            var win = new Ext.Window({
                title:'Shared network information'
                ,layout:'fit'
                ,width:800
                ,modal:true
                ,height:420
                ,closable:true
                ,border:false
                ,items:tabs
            });
            win.show();


            client_options.loadRecord(selected.data['uuid'],record_option);


            var record= new Object();
            record.data = selected.data['params'];
            form.loadSharedRecord(selected.data['uuid'],record);



        }
        ,onRender:function() {

            // call parent
            ETFW_DHCP.Networks.Data_Grid.superclass.onRender.apply(this, arguments);

            // start w/o grouping
            //		this.store.clearGrouping();
            //var store = grid.getStore();
            // store.load.defer(20,store);
            // load the store
            //this.store.load({params:{start:0, limit:10}});
            this.store.load.defer(20,this.store);

        } // eo function onRender
        ,deleteData:function(items){


            var conn = new Ext.data.Connection({
                listeners:{
                    // wait message.....
                    beforerequest:function(){
                        Ext.MessageBox.show({
                            title: 'Please wait',
                            msg: 'Removing subnet(s)/shared network(s)...',
                            width:300,
                            wait:true,
                            modal: false
                        });
                    },// on request complete hide message
                    requestcomplete:function(){Ext.MessageBox.hide();}
                }
            });// end conn
            var subnets = [];
            var items_subnets = [];

            for(var i=0,len = items.length;i<len;i++){
                subnets[i] = {"uuid":items[i].data.uuid};
                items_subnets[i] = items[i].data.uuid;
            }




            conn.request({
                url: this.url,
                params:{id:this.serviceId,method:'del_declarations',params:Ext.encode([subnets])},
                failure: function(resp,opt){
                    var response = Ext.util.JSON.decode(resp.responseText);
                    Ext.MessageBox.alert('Error Message', response['info']);
                    Ext.ux.Logger.error(response['error']);

                },
                // everything ok...
                success: function(resp,opt){

                    var msg = 'Subnet(s)/shared network(s) deleted';
                    Ext.ux.Logger.info(msg);
                    View.notify({html:msg});

                    this.reload();

                    var poolgrid = Ext.getCmp(this.containerId+'-pool_grid');


                    var filter = poolgrid.filters.getFilter('parent-uuid');
                    var filter_value = filter.getValue();
                    if(((items_subnets.join(",")).indexOf(filter_value)!=-1) && filter.active){
                        filter.setActive(false);
                        poolgrid.setTitle('All networks');
                        poolgrid.reload();

                    }





                },scope:this

            });// END Ajax request

        }
        ,onDeleteSubnet:function(btn){

            var selected = this.getSelectionModel().getSelected();
            if(btn=='yes' && selected){


                var conn = new Ext.data.Connection({
                    listeners:{
                        // wait message.....
                        beforerequest:function(){
                            Ext.MessageBox.show({
                                title: 'Please wait',
                                msg: 'Removing subnet info...',
                                width:300,
                                wait:true,
                                modal: false
                            });
                        },// on request complete hide message
                        requestcomplete:function(){Ext.MessageBox.hide();}
                    }
                });// end conn
                var send_data = [{"uuid":selected.data['uuid']}];

                conn.request({
                    url: this.url,
                    params:{id:this.serviceId,method:'del_declarations',params:Ext.encode([send_data])},
                    failure: function(resp,opt){

                        if(!resp.responseText){
                            Ext.ux.Logger.error(resp.statusText);
                            return;
                        }

                        var response = Ext.util.JSON.decode(resp.responseText);
                        Ext.MessageBox.alert('Error Message', response['info']);
                        Ext.ux.Logger.error(response['error']);

                    },
                    // everything ok...
                    success: function(resp,opt){

                        var msg = 'Subnet removed successfully';
                        Ext.ux.Logger.info(msg);
                        View.notify({html:msg});
                        this.reload();

                        var poolgrid = Ext.getCmp(this.containerId+'-pool_grid');

                        var filter = poolgrid.filters.getFilter('parent-uuid');
                        var filter_value = filter.getValue();
                        if(selected.data['uuid']==filter_value && filter.active){
                            filter.setActive(false);
                            poolgrid.setTitle('All networks');
                            poolgrid.reload();
                        }


                    },scope:this
                });// END Ajax request
            }

        }
        ,onDeleteShared:function(btn){

            var selected = this.getSelectionModel().getSelected();
            if(btn=='yes' && selected){


                var conn = new Ext.data.Connection({
                    listeners:{
                        // wait message.....
                        beforerequest:function(){
                            Ext.MessageBox.show({
                                title: 'Please wait',
                                msg: 'Removing shared network info...',
                                width:300,
                                wait:true,
                                modal: false
                            });
                        },// on request complete hide message
                        requestcomplete:function(){Ext.MessageBox.hide();}
                    }
                });// end conn
                var send_data = [{"uuid":selected.data['uuid']}];

                conn.request({
                    url: this.url,
                    params:{id:this.serviceId,method:'del_declarations',params:Ext.encode([send_data])},
                    failure: function(resp,opt){

                        if(!resp.responseText){
                            Ext.ux.Logger.error(resp.statusText);
                            return;
                        }

                        var response = Ext.util.JSON.decode(resp.responseText);
                        Ext.MessageBox.alert('Error Message', response['info']);
                        Ext.ux.Logger.error(response['error']);

                    },
                    // everything ok...
                    success: function(resp,opt){

                        var msg = 'Shared network removed successfully';
                        Ext.ux.Logger.info(msg);
                        View.notify({html:msg});
                        this.reload();

                        var poolgrid = Ext.getCmp(this.containerId+'-pool_grid');

                        var filter = poolgrid.filters.getFilter('parent-uuid');
                        var filter_value = filter.getValue();
                        if(selected.data['uuid']==filter_value && filter.active){
                            filter.setActive(false);
                            poolgrid.setTitle('All networks');
                            poolgrid.reload();
                        }


                    },scope:this
                });// END Ajax request
            }

        }
        // }}}

    }); // eo extend

    // register component
    Ext.reg('etfw_dhcp_networkgrid', ETFW_DHCP.Networks.Data_Grid);



    /*
    *
    * POOL GRID
    *
    */

    // create pre-configured grid class
    ETFW_DHCP.Networks.Pool_Grid = Ext.extend(Ext.grid.GridPanel, {

        initComponent:function() {

            var selectBoxModel= new Ext.grid.CheckboxSelectionModel({keepSelections:true});
            var filters = new Ext.ux.grid.GridFilters({
                // encode and local configuration options defined previously for easier reuse
                //encode: false, // json encode the filter query
                local: true,   // defaults to false (remote filtering)
                filters: [
                    {
                        type: 'string',
                        dataIndex: 'parent-uuid'
                    },
                    {   type:'string',
                        dataIndex:'parent-type'
                    }
                ]
            });

            this.tbar = [{
                    text:'Add pool',
                    tooltip:'Add a New Pool',
                    ref:'../addBtn',
                    disabled:true,
                    iconCls:'add',
                    handler: function(){

                        var form = new ETFW_DHCP.Data_Form({title:'Add Address Pool <br>in '+this.title,parent_grid:this,serviceId:this.serviceId});

                        // create and show window
                        var win = new Ext.Window({
                            title:'Address Pool information'
                            ,layout:'fit'
                            ,width:800
                            ,modal:true
                            ,height:420
                            ,closable:true
                            ,border:false
                            ,items:form
                        });
                        win.show();

                    },scope:this
                },
                '-',
                {
                            text:'Edit pool',
                            ref: '../editBtn',
                            tooltip:'Edit selected pool',
                            disabled:true,
                            handler:this.editPool,
                            scope:this
               },
                '-',
                {
                    text:'Delete pool(s)',
                    ref: '../removeBtn',
                    tooltip:'Delete the selected pool(s)',
                    iconCls:'remove',
                    disabled:true,
                    handler: function(){
                        new Grid.util.DeleteItem({panel: this.id});
                    },scope:this
                }];

            // row expander
            this.expander = new Ext.ux.grid.RowExpander({
                tpl : new Ext.XTemplate(
                '<tpl for="range_display">',       // process the data.kids node
                '<p><b>Range:</b> {address}<br>',
                '<b>Dynamic BOOTP:</b> {bootp}',
                '</p>',
                '</tpl>'

            )});


            // configure the grid
            Ext.apply(this, {
                store:new Ext.data.GroupingStore({
                    reader:new Ext.data.JsonReader({

                        totalProperty:'total'
                        ,root:'data'
                        ,fields:[
                            'uuid',
                            'name','parent','parent-uuid','parent-type','parent-args',
                            'range_display','range','failover',
                            'params'

                        ]
                    })
                    ,proxy:new Ext.data.HttpProxy({url:this.url})
                    ,baseParams:this.baseParams
                    ,groupField:'parent'
                    ,sortInfo:{field:'parent', direction:'ASC'}
                    ,listeners:{
                        load:{scope:this, fn:function() {
                                //   this.getSelectionModel().selectFirstRow();
                            }}
                        
                    }
                })
                ,columns:[

                    //selectBoxModel,
                    this.expander,
                    {header: "Name", width: 40, sortable: true, dataIndex: 'name'}
                    ,{header: "Parent", width: 40, sortable: true, dataIndex: 'parent'}
                    ,{header: "Parent type", width: 20, sortable: true, dataIndex: 'parent-type'}
                    //,this.action
                ]
                ,stateful:false
                ,plugins:[filters
                    //	,plugins:[this.action
                    ,this.expander
                ]
                ,sm:selectBoxModel
                ,view: new Ext.grid.GroupingView({
                    forceFit:true
                    ,emptyText: 'Empty!'  //  emptyText Message
                    ,deferEmptyText:false
                    ,groupTextTpl:'{text} ({[values.rs.length]} {[values.rs.length > 1 ? "Items" : "Item"]})'
                })
                ,loadMask:true
                // ,viewConfig:{forceFit:true}
                //                 ,viewConfig:{
                //                    forceFit:true,
                //                    emptyText: 'Empty!',  //  emptyText Message
                //                    deferEmptyText:false
                //                }
                ,height:200
                //    ,layout:'fit'
                //  ,loadMask:true

                ,autoScroll:true
                //			,viewConfig:{forceFit:true}
            }); // eo apply

            // add paging toolbar
            this.bbar = [new Ext.ux.grid.TotalCountBar({
                store:this.store
                ,displayInfo:true
                ,plugins:[filters]
                }),{
                    text: 'Clear Filter Data',
                    handler: function () {
                        this.filters.clearFilters();
                        this.setTitle('All networks');
                    },scope:this
                }];

            // call parent
            ETFW_DHCP.Networks.Pool_Grid.superclass.initComponent.apply(this, arguments);

            this.getSelectionModel().on('selectionchange', function(sm){
                // this.addBtn.setDisabled(sm.getCount() < 1);
                this.editBtn.setDisabled(sm.getCount() < 1);
                this.removeBtn.setDisabled(sm.getCount() < 1);
            },this);

            this.on('filterupdate', function(GridFilter,filter){
                if(filter.dataIndex=='parent-uuid')
                    if(filter.active) this.addBtn.setDisabled(false);
                else this.addBtn.setDisabled(true);
            }, this);


            /************************************************************
             * handle contextmenu event
             ************************************************************/
            this.addListener("rowcontextmenu", onContextMenu, this);
            function onContextMenu(grid, rowIndex, e) {

                grid.getSelectionModel().selectRow(rowIndex);

                if (!this.menu) {
                    this.menu = new Ext.menu.Menu({
                        // id: 'menus',
                        items: [{
                                text:'Edit pool',
                                tooltip:'Edit pool information of the selected item',
                                iconCls:'editItem',
                                handler: this.editPool,
                                scope:this
                            },{
                                text:'Delete pool',
                                tooltip:'Delete this item',
                                iconCls:'remove',
                                handler: function(){
                                    Ext.MessageBox.show({
                                        title:'Delete pool',
                                        msg: 'You are about to delete this pool. <br />Are you sure you want to delete?',
                                        buttons: Ext.MessageBox.YESNOCANCEL,
                                        fn: this.onDeletePool,
                                        scope:this,
                                        icon: Ext.MessageBox.QUESTION
                                    });

                                },scope:this
                            }]
                    });
                }
                this.rowctx = rowIndex;
                e.stopEvent();
                this.menu.showAt(e.getXY());
            }


        } // eo function initComponent
        // }}}
        // {{{
        ,onRender:function() {

            // call parent
            ETFW_DHCP.Networks.Pool_Grid.superclass.onRender.apply(this, arguments);

            // start w/o grouping
            //		this.store.clearGrouping();
            //var store = grid.getStore();
            // store.load.defer(20,store);
            // load the store
            //this.store.load({params:{start:0, limit:10}});
            this.store.load.defer(20,this.store);

        } // eo function onRender
        ,reload:function(){this.store.reload();}
        ,editPool:function(){


            var selected = this.getSelectionModel().getSelected();

            var extra_txt = '<br>';


            var type = '';
            switch(selected.data['parent-type']){
                case 'subnet': type = 'subnet ';
                    break;
                case 'shared-network': type = 'shared network ';
                    break;
                default:break;
            }

            if(type) extra_txt += 'in '+type+selected.data['parent-args'];
            else extra_txt += '&nbsp';



            var form = new ETFW_DHCP.Data_Form({title:'Edit Address Pool'+extra_txt,parent_grid:this,serviceId:this.serviceId});

            // create and show window
            var win = new Ext.Window({
                title:'Address pool information'
                ,layout:'fit'
                ,width:800
                ,modal:true
                ,height:420
                ,closable:true
                ,border:false
                ,items:form
            });
            win.show();

            var record= new Object();
            record.data = selected.data['params'];
            form.loadPoolRecord(selected.data['uuid'],record);



        }
        ,onDeletePool:function(btn){

            var selected = this.getSelectionModel().getSelected();
            if(btn=='yes' && selected){


                var conn = new Ext.data.Connection({
                    listeners:{
                        // wait message.....
                        beforerequest:function(){
                            Ext.MessageBox.show({
                                title: 'Please wait',
                                msg: 'Removing pool info...',
                                width:300,
                                wait:true,
                                modal: false
                            });
                        },// on request complete hide message
                        requestcomplete:function(){Ext.MessageBox.hide();}
                    }
                });// end conn
                var send_data = [{"uuid":selected.data['uuid']}];

                conn.request({
                    url: this.url,
                    params:{id:this.serviceId,method:'del_declarations',params:Ext.encode([send_data])},
                    failure: function(resp,opt){

                        if(!resp.responseText){
                            Ext.ux.Logger.error(resp.statusText);
                            return;
                        }

                        var response = Ext.util.JSON.decode(resp.responseText);
                        Ext.MessageBox.alert('Error Message', response['info']);
                        Ext.ux.Logger.error(response['error']);

                    },
                    // everything ok...
                    success: function(resp,opt){
                        var msg = 'Pool removed successfully';
                        Ext.ux.Logger.info(msg);
                        View.notify({html:msg});
                        this.reload();


                    },scope:this
                });// END Ajax request
            }

        }
        ,deleteData:function(items){


            var conn = new Ext.data.Connection({
                listeners:{
                    // wait message.....
                    beforerequest:function(){
                        Ext.MessageBox.show({
                            title: 'Please wait',
                            msg: 'Removing pool(s)...',
                            width:300,
                            wait:true,
                            modal: false
                        });
                    },// on request complete hide message
                    requestcomplete:function(){Ext.MessageBox.hide();}
                }
            });// end conn
            var pools = [];

            for(var i=0,len = items.length;i<len;i++){
                pools[i] = {"uuid":items[i].data.uuid};

            }

            conn.request({
                url: this.url,
                params:{id:this.serviceId,method:'del_declarations',params:Ext.encode([pools])},
                failure: function(resp,opt){
                    var response = Ext.util.JSON.decode(resp.responseText);
                    Ext.MessageBox.alert('Error Message', response['info']);
                    Ext.ux.Logger.error(response['error']);

                },
                // everything ok...
                success: function(resp,opt){

                    var msg = 'Pool(s) deleted';
                    Ext.ux.Logger.info(msg);
                    View.notify({html:msg});

                    this.reload();

                },scope:this

            });// END Ajax request

        }


    }); // eo extend

    // register component
    Ext.reg('poolgrid', ETFW_DHCP.Networks.Pool_Grid);





    ETFW_DHCP.Networks.Main = function(serviceId,containerId) {

        this.serviceId = serviceId;
        this.containerId = containerId;

        ETFW_DHCP.Networks.Main.superclass.constructor.call(this, {
            title: 'Subnets and Shared Networks',
            layout:'border',
            items:[{
                    xtype: 'tabpanel',
                    region: 'center',
                    margins: '5 5 5 0',
                    tabPosition: 'bottom',
                    activeTab: 0,
                    items: [{

                            title: 'Subnets info',
                            id:this.containerId+'-subnets_grid',
                            containerId:this.containerId,
                            url:<?php echo json_encode(url_for('etfw/json'))?>,
                            type:'subnet',
                            serviceId:this.serviceId,
                            baseParams:{id:this.serviceId,method:'list_subnet'},xtype:'etfw_dhcp_networkgrid'
                        },{

                            title: 'Shared networks info',
                            id:this.containerId+'-shared_grid',
                            containerId:this.containerId,
                            url:<?php echo json_encode(url_for('etfw/json'))?>,
                            serviceId:this.serviceId,
                            type:'shared-network',
                            baseParams:{id:this.serviceId,method:'list_sharednetwork'},
                            xtype:'etfw_dhcp_networkgrid'
                        }]
                },
                {region:'east',layout:'fit',width:350,collapsible:true,
                    title:'Address Pools',
                    items:[{title:'All networks',url:<?php echo json_encode(url_for('etfw/json'))?>,id:this.containerId+'-pool_grid',serviceId:this.serviceId,baseParams:{id:this.serviceId,method:'list_pool'},xtype:'poolgrid'}]
                }
            ]
        });


    }//eof

    // define public methods
    Ext.extend(ETFW_DHCP.Networks.Main, Ext.Panel,{});

</script>