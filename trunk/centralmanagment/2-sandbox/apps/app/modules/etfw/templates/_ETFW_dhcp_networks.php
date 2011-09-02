<script>

Ext.ns('ETFW.DHCP.Networks');
//
// create pre-configured grid class
ETFW.DHCP.Networks.Data_Grid = Ext.extend(Ext.grid.GridPanel, {

    initComponent:function() {

        var selectBoxModel= new Ext.grid.CheckboxSelectionModel({keepSelections:true});

        var group_field = '';
        var sort_info = '';
        var grid_columns = [];
        switch(this.type){
            case 'subnet':
                group_field = 'netmask';
                sort_info = {field:'address', direction:'ASC'};
                grid_columns = [
                    selectBoxModel,
                    {header: "Address", width: 40, sortable: true, dataIndex: 'address',renderer: function (value, metadata, record, rowIndex, colIndex, store) {
                            metadata.attr = 'ext:qtip="Double-click to filter address pool(s) for this subnet"';
                            return value;
                        }}
                    ,{header: "Netmask", width: 20, sortable: true, dataIndex: 'netmask'}
                    ,{header: "Shared network", width: 20, sortable: true, dataIndex: 'parent-name'}
                ];

                this.tbar = [{
                        text:'Add subnet',
                        tooltip:'Add a New Subnet',
                        ref:'../addBtn',
                        iconCls:'add',
                        handler: function(){
                            Ext.getBody().mask('Preparing data...');
                            this.addSubnet.defer(150,this);
                        },
                        scope:this
                    },
                    '-',
                    {
                        text:'Edit subnet',
                        ref: '../editBtn',
                        tooltip:'Edit selected subnet',
                        disabled:true,
                        handler: function(){
                            Ext.getBody().mask('Preparing data...');
                            this.editSubnet.defer(150,this);
                        },
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
                            Ext.getBody().mask('Preparing data...');
                            this.addShared.defer(150,this);
                        },
                        scope:this
                    },
                    '-',
                    {
                        text:'Edit shared network',
                        ref: '../editBtn',
                        tooltip:'Edit selected network',
                        disabled:true,
                        handler: function(){
                            Ext.getBody().mask('Preparing data...');
                            this.editShared.defer(150,this);
                        },
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
                    fields:[]
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

            var poolgrid = Ext.getCmp(this.service_id+'-pool_grid');

            var filterUUID = poolgrid.filters.getFilter('parent-uuid');
            filterUUID.setValue(selected.data['uuid']);
            filterUUID.setActive(true);

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
        ETFW.DHCP.Networks.Data_Grid.superclass.initComponent.apply(this, arguments);

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
                        handler: function(){
                            Ext.getBody().mask('Preparing data...');
                            this.editSubnet.defer(150,this);
                        },
                        scope:this
                    },{
                        text:'Delete subnet',
                        tooltip:'Delete this subnet',
                        iconCls:'remove',
                        handler:function(){
                            Ext.MessageBox.show({
                                title:'Delete pool',
                                msg: 'You are about to delete this subnet. <br />Are you sure you want to delete?',
                                buttons: Ext.MessageBox.YESNOCANCEL,
                                fn: function(btn){

                                    if(btn=='yes'){
                                        var selected = this.getSelectionModel().getSelected();
                                        var uuid = selected.data['uuid'];
                                        if(uuid) this.onDeleteSubnet(uuid);
                                    }

                                },
                                scope:this,
                                icon: Ext.MessageBox.QUESTION
                            });
                        },
                        scope:this
                    }
                    ]
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
                        handler: function(){
                            Ext.getBody().mask('Preparing data...');
                            this.editShared.defer(150,this);
                        },
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
    ,addSubnet:function(){

        var subnet_title = 'Add Subnet ';

        var subnet_form = new ETFW.DHCP.Subnet_Form({title:subnet_title,service_id:this.service_id});

        subnet_form.on({
                createdSubnet:function(){
                                win.close();
                                this.reload();
                            },
                scope:this
        });


        var viewerSize = Ext.getBody().getViewSize();
        var windowHeight = viewerSize.height * 0.95;
        windowHeight = Ext.util.Format.round(windowHeight,0);
        windowHeight = (windowHeight > 650) ? 650 : windowHeight;

        // create and show window
        var win = new Ext.Window({
            title:'Subnet information'
            ,width:920
            ,layout:'fit'
            ,modal:true
            ,height:windowHeight
            ,closable:true
            ,border:false
            ,items:subnet_form
            ,buttons:[{text:'Close',handler:function(){win.close()}}]
            ,listeners:{
                show:{fn:function(){
                        subnet_form.reset();
                      },delay:10}
            }

        });

        win.show();

    }
    ,editSubnet:function(){

        var selected = this.getSelectionModel().getSelected();
        var subnet_title = 'Edit Subnet '+selected.data['address'];

        var subnet_form = new ETFW.DHCP.Subnet_Form({title:subnet_title,service_id:this.service_id});
        subnet_form.on({
                updatedSubnet:function(){
                                win.close();

                                var poolgrid = Ext.getCmp(this.service_id+'-pool_grid');
                                var filter = poolgrid.filters.getFilter('parent-uuid');

                                if(selected.data['uuid']==filter.getValue() && filter.active){
                                    poolgrid.reload(true);
                                }

                                this.reload();
                            },
                deleteSubnet:function(uuid){
                                win.close();
                                this.onDeleteSubnet(uuid);},
                scope:this
        });

        var client_title = 'Client Options for subnet '+ selected.data['address'];
        var client_options = new ETFW.DHCP.ClientOptions_Form({title:client_title,service_id:this.service_id});
        client_options.on({
                updatedClientOptions:function(){
                                win.close();
                                this.reload();
                            },
                scope:this
        });

        var record_option= new Object();
        record_option.data = selected.data['option'];
        record_option.data['uuid'] = selected.data['uuid'];

        client_options.on('afterLayout',function(){client_options.loadRecord(record_option);},this,{single:true});

        var leases_params = {'all':1,'netaddr':selected.data['address'],'netmask':selected.data['netmask']};
        var list_leases = new ETFW.DHCP.ListLeases_Grid({
                title:'DHCP leases in network '+ selected.data['args'],
                url:this.url,
                service_id:this.service_id,
                baseParams:{id:this.service_id,method:'list_leases',params:Ext.encode(leases_params)},
                xtype:'leasesgrid'});

        var tabs = new Ext.TabPanel({
            activeTab:0,
            border:false,
            defaults:{border:false},
            items:[subnet_form
                ,client_options
                ,list_leases
            ]
        });

        var viewerSize = Ext.getBody().getViewSize();
        var windowHeight = viewerSize.height * 0.95;
        windowHeight = Ext.util.Format.round(windowHeight,0);
        windowHeight = (windowHeight > 650) ? 650 : windowHeight;

        // create and show window
        var win = new Ext.Window({
            title:'Subnet information'
            ,width:920
            ,layout:'fit'
            ,modal:true
            ,height:windowHeight
            ,closable:true
            ,border:false
            ,items:tabs
            ,buttons:[{text:'Close',handler:function(){win.close()}}]
            ,listeners:{
                show:{fn:function(){
                        var record= new Object();
                        record.data = selected.data['params'];
                        record.data['uuid'] = selected.data['uuid'];
                        subnet_form.loadSubnetRecord(record);
                      },delay:10}
            }
        });

        win.show();

    }
    ,addShared:function(){

        var shared_title = 'Add Shared network ';

        var shared_form = new ETFW.DHCP.Sharednetwork_Form({title:shared_title,service_id:this.service_id});

        shared_form.on({
                createdShared:function(){
                                win.close();
                                this.reload();
                            },
                scope:this
        });


        var viewerSize = Ext.getBody().getViewSize();
        var windowHeight = viewerSize.height * 0.95;
        windowHeight = Ext.util.Format.round(windowHeight,0);
        windowHeight = (windowHeight > 650) ? 650 : windowHeight;

        // create and show window
        var win = new Ext.Window({
            title:'Shared network information'
            ,width:920
            ,layout:'fit'
            ,modal:true
            ,height:windowHeight
            ,closable:true
            ,border:false
            ,items:shared_form
            ,buttons:[{text:'Close',handler:function(){win.close()}}]
            ,listeners:{
                show:{fn:function(){
                        shared_form.reset();
                      },delay:10}
            }

        });

        win.show();

    }
    ,editShared:function(){


        var selected = this.getSelectionModel().getSelected();
        var shared_title = 'Edit Shared network '+selected.data['name'];

        var shared_form = new ETFW.DHCP.Sharednetwork_Form({title:shared_title,service_id:this.service_id});
        shared_form.on({
                updatedShared:function(){
                                win.close();

                                var poolgrid = Ext.getCmp(this.service_id+'-pool_grid');
                                var filter = poolgrid.filters.getFilter('parent-uuid');

                                if(selected.data['uuid']==filter.getValue() && filter.active){
                                    poolgrid.reload(true);
                                }

                                this.reload();
                            },
                deleteShared:function(uuid){
                                win.close();
                                this.onDeleteShared(uuid);},
                scope:this
        });

        var client_title = 'Client Options for shared network '+ selected.data['name'];
        var client_options = new ETFW.DHCP.ClientOptions_Form({title:client_title,service_id:this.service_id});
        client_options.on({
                updatedClientOptions:function(){
                                win.close();
                                this.reload();
                            },
                scope:this
        });

        var record_option= new Object();
        record_option.data = selected.data['option'];
        record_option.data['uuid'] = selected.data['uuid'];

        client_options.on('afterLayout',function(){client_options.loadRecord(record_option);},this,{single:true});



        var tabs = new Ext.TabPanel({
            activeTab:0,
            border:false,
            defaults:{border:false},
            items:[shared_form
                ,client_options
            ]
        });

        var viewerSize = Ext.getBody().getViewSize();
        var windowHeight = viewerSize.height * 0.95;
        windowHeight = Ext.util.Format.round(windowHeight,0);
        windowHeight = (windowHeight > 650) ? 650 : windowHeight;

        // create and show window
        var win = new Ext.Window({
            title:'Shared network information'
            ,width:920
            ,layout:'fit'
            ,modal:true
            ,height:windowHeight
            ,closable:true
            ,border:false
            ,items:tabs
            ,buttons:[{text:'Close',handler:function(){win.close()}}]
            ,listeners:{
                show:{fn:function(){
                        var record= new Object();
                        record.data = selected.data['params'];
                        record.data['uuid'] = selected.data['uuid'];
                        shared_form.loadSharedRecord(record);
                      },delay:10}
            }
        });

        win.show();


    }
    ,onRender:function() {

        // call parent
        ETFW.DHCP.Networks.Data_Grid.superclass.onRender.apply(this, arguments);

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
            params:{id:this.service_id,method:'del_declarations',params:Ext.encode([subnets])},
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

                var poolgrid = Ext.getCmp(this.service_id+'-pool_grid');


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
    ,onDeleteSubnet:function(uuid){


        var send_data = {"decls":[{"uuid":uuid}]};

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
                ,requestexception:function(c,r,o){Ext.Ajax.fireEvent('requestexception',c,r,o);}
            }
        });// end conn

        conn.request({
            url: this.url,
            params:{id:this.service_id,method:'del_declarations',params:Ext.encode(send_data)},
            // everything ok...
            success: function(resp,opt){
                var msg = 'Subnet removed successfully';
                Ext.ux.Logger.info(msg);
                View.notify({html:msg});
                this.reload();

                var poolgrid = Ext.getCmp(this.service_id+'-pool_grid');
                var filter = poolgrid.filters.getFilter('parent-uuid');

                if(uuid==filter.getValue() && filter.active) poolgrid.reload(true);

            },scope:this
        });// END Ajax request


    }
    ,onDeleteShared:function(uuid){


        var send_data = {"decls":[{"uuid":uuid}]};

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
                ,requestexception:function(c,r,o){Ext.Ajax.fireEvent('requestexception',c,r,o);}
            }
        });// end conn

        conn.request({
            url: this.url,
            params:{id:this.service_id,method:'del_declarations',params:Ext.encode(send_data)},
            // everything ok...
            success: function(resp,opt){
                var msg = 'Shared network removed successfully';
                Ext.ux.Logger.info(msg);
                View.notify({html:msg});
                this.reload();

                var poolgrid = Ext.getCmp(this.service_id+'-pool_grid');
                var filter = poolgrid.filters.getFilter('parent-uuid');

                if(uuid==filter.getValue() && filter.active) poolgrid.reload(true);

            },scope:this
        });// END Ajax request

    }
    // }}}

}); // eo extend

// register component
Ext.reg('etfw_dhcp_networkgrid', ETFW.DHCP.Networks.Data_Grid);



/*
*
* POOL GRID
*
*/

// create pre-configured grid class
ETFW.DHCP.Networks.Pool_Grid = Ext.extend(Ext.grid.GridPanel, {

    initComponent:function() {

        var selectBoxModel= new Ext.grid.CheckboxSelectionModel({keepSelections:true});
        var filters = new Ext.ux.grid.GridFilters({
            // encode and local configuration options defined previously for easier reuse
            //encode: false, // json encode the filter query
            local: true,   // defaults to false (remote filtering)
            filters: [
                {
                    type: 'string',
                    like:'false',
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
                            Ext.getBody().mask('Preparing data...');
                            this.createPool.defer(150,this);
                        },scope:this
                    },
                    '-',
                    {
                        text:'Edit pool',
                        ref: '../editBtn',
                        tooltip:'Edit selected pool',
                        disabled:true,
                        handler:function(){
                            Ext.getBody().mask('Preparing data...');
                            this.editPool.defer(150,this);
                        },
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
                    fields:[]
                })
                ,proxy:new Ext.data.HttpProxy({url:this.url})
                ,baseParams:this.baseParams
                ,groupField:'parent-name'
                ,sortInfo:{field:'parent-name', direction:'ASC'}
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
                ,{header: "Parent", width: 40, sortable: true, dataIndex: 'parent-name'}
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
                handler: this.clearFilters,
                scope:this
            }];

        // call parent
        ETFW.DHCP.Networks.Pool_Grid.superclass.initComponent.apply(this, arguments);

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
                            handler: function(){
                                Ext.getBody().mask('Preparing data...');
                                this.editPool.defer(150,this);
                            },
                            scope:this
                        },{
                            text:'Delete pool',
                            tooltip:'Delete this item',
                            iconCls:'remove',
                            handler:function(){
                                Ext.MessageBox.show({
                                    title:'Delete pool',
                                    msg: 'You are about to delete this pool. <br />Are you sure you want to delete?',
                                    buttons: Ext.MessageBox.YESNOCANCEL,
                                    fn: function(btn){

                                        if(btn=='yes'){
                                            var selected = this.getSelectionModel().getSelected();
                                            var uuid = selected.data['uuid'];
                                            if(uuid) this.onDeletePool(uuid);
                                        }

                                    },
                                    scope:this,
                                    icon: Ext.MessageBox.QUESTION
                                });
                            },
                            scope:this
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
        ETFW.DHCP.Networks.Pool_Grid.superclass.onRender.apply(this, arguments);

        // start w/o grouping
        //		this.store.clearGrouping();
        //var store = grid.getStore();
        // store.load.defer(20,store);
        // load the store
        //this.store.load({params:{start:0, limit:10}});
        this.store.load.defer(20,this.store);

    } // eo function onRender
    ,clearFilters:function(){
        this.filters.clearFilters();
        this.setTitle('All networks');

    }
    ,reload:function(clr){
        if(clr===true) this.clearFilters();
        this.store.reload();}
    ,createPool:function(){

        var title = 'Add Address Pool in '+this.title;
        var poolParentUUID = this.filters.getFilter('parent-uuid').getValue();

        var form = new ETFW.DHCP.Pool_Form({title:title,service_id:this.service_id});
        form.on({
                updatedPool:function(){
                                win.close();
                                this.reload();},
                scope:this
        });

        form.setPoolParent(poolParentUUID);



        var viewerSize = Ext.getBody().getViewSize();
        var windowHeight = viewerSize.height * 0.95;
        windowHeight = Ext.util.Format.round(windowHeight,0);
        windowHeight = (windowHeight > 650) ? 650 : windowHeight;

        // create and show window
        var win = new Ext.Window({
            title:'Address Pool information'
            ,width:920
            ,layout:'fit'
            ,modal:true
            ,height:windowHeight
            ,closable:true
            ,border:false
            ,items:form
            ,buttons:[{text:'Close',handler:function(){win.close()}}]
        });
        win.show();

    }
    ,editPool:function(){

        var selected = this.getSelectionModel().getSelected();
        var title = '';

        switch(selected.data['parent-type']){
            case 'subnet':
                            title = 'Edit Address Pool in Subnet '+selected.data['parent-args'].replace(' netmask ','/');
                            break;
            case 'shared-network':
                            title = 'Edit Address Pool in Shared network '+selected.data['parent-args'].replace(' netmask ','/');
                            break;
            default:
                            break;
        }

        var form = new ETFW.DHCP.Pool_Form({title:title,service_id:this.service_id});
        form.on({
                updatedPool:function(){
                                win.close();
                                this.reload();},
                deletePool:function(uuid){
                                win.close();
                                this.onDeletePool(uuid);},
                scope:this
        });

        var viewerSize = Ext.getBody().getViewSize();
        var windowHeight = viewerSize.height * 0.95;
        windowHeight = Ext.util.Format.round(windowHeight,0);
        windowHeight = (windowHeight > 650) ? 650 : windowHeight;

        // create and show window
        var win = new Ext.Window({
            title:'Address pool information'
            ,width:920
            ,layout:'fit'
            ,modal:true
            ,height:windowHeight
            ,closable:true
            ,border:false
            ,items:form
            ,buttons:[{text:'Close',handler:function(){win.close()}}]
            ,listeners:{
                show:{fn:function(){
                        var record= new Object();
                        record.data = selected.data['params'];
                        record.data['uuid'] = selected.data['uuid'];
                        form.loadPoolRecord(record);
                      },delay:10}
            }
        });

        win.show();


    }
    ,onDeletePool:function(uuid){

        var send_data = {"decls":[{"uuid":uuid}]};

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
                ,requestexception:function(c,r,o){Ext.Ajax.fireEvent('requestexception',c,r,o);}
            }
        });// end conn

        conn.request({
            url: this.url,
            params:{id:this.service_id,method:'del_declarations',params:Ext.encode(send_data)},
            // everything ok...
            success: function(resp,opt){
                var msg = 'Pool removed successfully';
                Ext.ux.Logger.info(msg);
                View.notify({html:msg});
                this.reload();

            },scope:this
        });// END Ajax request


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
                ,requestexception:function(c,r,o){Ext.Ajax.fireEvent('requestexception',c,r,o);}
            }
        });// end conn
        var pools = [];

        for(var i=0,len = items.length;i<len;i++){
            pools[i] = {"uuid":items[i].data.uuid};

        }

        var send_data = {'decls':pools};

        conn.request({
            url: this.url,
            params:{id:this.service_id,method:'del_declarations',params:Ext.encode(send_data)},
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
Ext.reg('etfw_dhcp_poolgrid', ETFW.DHCP.Networks.Pool_Grid);





ETFW.DHCP.Networks.Main = function(service_id) {

    this.service_id = service_id;

    ETFW.DHCP.Networks.Main.superclass.constructor.call(this, {
        title: 'Subnets / Shared Networks',
        layout:'border',
        border:false,
        items:[{
                xtype: 'tabpanel',
                region: 'center',
                margins: '3 3 3 3',
                tabPosition: 'bottom',                
                activeTab: 0,
                defaults:{border:false},
                items: [{

                        title: 'Subnets info',
                        id:this.service_id+'-subnets_grid',
                        url:<?php echo json_encode(url_for('etfw/json'))?>,
                        type:'subnet',
                        service_id:this.service_id,
                        baseParams:{id:this.service_id,method:'list_subnet'},xtype:'etfw_dhcp_networkgrid'
                    },{

                        title: 'Shared networks info',
                        id:this.service_id+'-shared_grid',
                        //containerId:this.containerId,
                        url:<?php echo json_encode(url_for('etfw/json'))?>,
                        service_id:this.service_id,
                        type:'shared-network',
                        baseParams:{id:this.service_id,method:'list_sharednetwork'},
                        xtype:'etfw_dhcp_networkgrid'
                    }]
            },
            {region:'east',layout:'fit',width:350,collapsible:true,
                title:'Address Pools',
                defaults:{border:false},
                margins: '3 3 3 0',cmargins:'3 3 3 0',
                items:[{title:'All networks',url:<?php echo json_encode(url_for('etfw/json'))?>,border:false,id:this.service_id+'-pool_grid',service_id:this.service_id,baseParams:{id:this.service_id,method:'list_pool'},xtype:'etfw_dhcp_poolgrid'}]
            }
        ]
    });


}//eof

// define public methods
Ext.extend(ETFW.DHCP.Networks.Main, Ext.Panel,{
    reload:function(){
        var tabPanel = this.get(0);

        var subnetsPanel = tabPanel.get(0);
        var sharednetPanel = tabPanel.get(1);

        if(subnetsPanel.rendered)
            subnetsPanel.reload();

        if(sharednetPanel.rendered)
            sharednetPanel.reload();

    }

});

</script>