<script>
    /*
     * DNS zones
     *
     */
    Ext.ns('ETFW_DHCP.Zones');

    // create pre-configured grid class
    ETFW_DHCP.Zones.Zone_Grid = Ext.extend(Ext.grid.GridPanel, {

        initComponent:function() {

            var selectBoxModel= new Ext.grid.CheckboxSelectionModel({keepSelections:true});

            var defaultEditor = new Ext.ux.grid.RowEditor({
                saveText: 'Update',
                clicksToEdit: 2
            });


            defaultEditor.on({
                scope: this,
                afteredit: function(roweditor, changes, record, rowIndex) {
                              var send_data = record.data;

                              var conn = new Ext.data.Connection({
                                        listeners:{
                                            // wait message.....
                                            beforerequest:function(){
                                                Ext.MessageBox.show({
                                                    title: 'Please wait',
                                                    msg: record.phantom ? 'Adding zone info...' : 'Updating zone info...',
                                                    width:300,
                                                    wait:true,
                                                    modal: false
                                                });
                                            },// on request complete hide message
                                            requestcomplete:function(){Ext.MessageBox.hide();}
                                        }
                                    });// end conn

                              conn.request({
                                    url: this.url,
                                    params:{id:this.serviceId,method: record.phantom ? 'add_zone' : 'set_zone',params:Ext.encode(send_data)},
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
                                        var msg = record.phantom ? 'Zone created successfully' : 'Zone edited successfully';
                                        Ext.ux.Logger.info(msg);
                                        View.notify({html:msg});
                                        this.store.reload();

                                    },scope:this
                              });// END Ajax request
                }
            });


            this.tbar = [
                {
                    iconCls: 'icon-add',
                    text: 'Add zone',
                    handler: function(){

                        var key_data = keys_store.getAt(0);
                        var init_key = key_data ? key_data.data.value : '' ;

                        var zone_Record = this.store.recordType;
                        var zone_r = new zone_Record({
                            name: 'New zone...',
                            lastcomment: '',
                            primary:'',
                            key: init_key
                        });

                        defaultEditor.stopEditing();

                        this.store.insert(0, zone_r);
                        this.getView().refresh();
                        this.getSelectionModel().selectRow(0);
                        defaultEditor.startEditing(0);

                    },scope:this
                },
                {
                    text:'Edit zone',
                    ref: '../editBtn',
                    tooltip:'Edit selected zone',
                    disabled:true,
                    handler: function(){
                        var selected = this.getSelectionModel().getSelected();
                        defaultEditor.stopEditing();
                        defaultEditor.startEditing(this.store.indexOf(selected));
                    },
                    scope:this
                },
                {
                    text:'Delete zone(s)',
                    ref: '../removeBtn',
                    tooltip:'Delete the selected zone(s)',
                    iconCls:'remove',
                    disabled:true,
                    handler: function(){
                        new Grid.util.DeleteItem({panel: this.id});
                    },scope:this
                }];


            var keys_store = new Ext.data.JsonStore({
                url: this.url,
                baseParams:{id:this.serviceId,method:'list_key'},
                //id: 'namefullname',
                remoteSort: false,
                totalProperty: 'total',
                root: 'data',
                fields: ['key'] // initialized from json metadata
            });
            keys_store.setDefaultSort('key', 'ASC');

            this.keys_combo = new Ext.form.ComboBox({
                mode: 'remote',
                reload:true,
                triggerAction: 'all',
                fieldLabel: '',
                hideLabel:true,
                emptyText:'',
                allowBlank: true,
                readOnly:true,
                editable:false,
                store:keys_store,
                valueField: 'key',
                displayField: 'key',
                width:70
            });

            // configure the grid
            Ext.apply(this, {
                store:new Ext.data.GroupingStore({
                    reader:new Ext.data.JsonReader({

                        totalProperty:'total'
                        ,idProperty:'uuid'
                        ,root:'data'
                        ,fields:[
                            'uuid','name','lastcomment','primary','key'
                        ]
                    })
                    ,proxy:new Ext.data.HttpProxy({url:this.url})
                    ,baseParams:this.baseParams
                    ,groupField:'key'
                    ,sortInfo:{field:'name', direction:'ASC'}
                    ,listeners:{
                        load:{scope:this, fn:function(){}}
                    }
                })
                ,columns:[
                    selectBoxModel,
                    {header: "Name", width: 40, sortable: true, dataIndex: 'name',
                        renderer: function (value, metadata, record, rowIndex, colIndex, store) {
                                metadata.attr = 'ext:qtip="Double-click to edit"';
                                return value;
                        },editor: {xtype: 'textfield',allowBlank: false}}
                    ,{header: "Description", width: 40, sortable: true, allowBlank:true,dataIndex: 'lastcomment',
                        editor: {xtype: 'textfield',allowBlank: true}}
                    ,{header: "IP", width: 20, sortable: true, dataIndex: 'primary',
                        editor: {xtype: 'textfield',allowBlank: true}}
                    ,{header: "TSIG key", width: 20, sortable: true, allowBlank:true,dataIndex: 'key',
                        editor: this.keys_combo}
                ]
                ,stateful:false
                ,sm:selectBoxModel
                ,view: new Ext.grid.GroupingView({
                    forceFit:true
                    ,emptyText: 'Empty!'  //  emptyText Message
                    ,deferEmptyText:false
                    ,groupTextTpl:'{text} ({[values.rs.length]} {[values.rs.length > 1 ? "Items" : "Item"]})'
                })
                ,loadMask:true
                ,plugins:[defaultEditor]
                ,height:200
                ,autoScroll:true

            }); // eo apply

            // add bottom toolbar
            this.bbar = [
                        '->',
                        {text: 'Refresh',
                            xtype: 'button',
                            tooltip: 'refresh',
                            iconCls: 'x-tbar-loading',
                            scope:this,
                            handler: function(button,event)
                            {
                                button.addClass('x-item-disabled');
                                this.store.reload({
                                    callback:function(){button.removeClass('x-item-disabled');}});
                            }
                        }
                ];


            // call parent
            ETFW_DHCP.Zones.Zone_Grid.superclass.initComponent.apply(this, arguments);

            this.getSelectionModel().on('selectionchange', function(sm){
                this.editBtn.setDisabled(sm.getCount() < 1);
                this.removeBtn.setDisabled(sm.getCount() < 1);
            },this);

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
                                text:'Delete zone',
                                tooltip:'Delete this item',
                                iconCls:'remove',
                                handler: function(){
                                            new Grid.util.DeleteItem({panel: this.id});
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
            ETFW_DHCP.Zones.Zone_Grid.superclass.onRender.apply(this, arguments);

            // start w/o grouping
            //		this.store.clearGrouping();
            //var store = grid.getStore();
            // store.load.defer(20,store);
            // load the store
            //this.store.load({params:{start:0, limit:10}});
            this.store.load.defer(20,this.store);


        } // eo function onRender
        ,reload:function(){this.store.reload();}
        ,deleteData:function(items){

            var conn = new Ext.data.Connection({
                listeners:{
                    // wait message.....
                    beforerequest:function(){
                        Ext.MessageBox.show({
                            title: 'Please wait',
                            msg: 'Removing zone(s)...',
                            width:300,
                            wait:true,
                            modal: false
                        });
                    },// on request complete hide message
                    requestcomplete:function(){Ext.MessageBox.hide();}
                }
            });// end conn
            var zones = [];

            for(var i=0,len = items.length;i<len;i++){
                zones[i] = {'uuid':items[i].data.uuid};

            }

            conn.request({
                url: this.url,
                params:{id:this.serviceId,method:'del_declarations',params:Ext.encode({'zones':zones})},
                failure: function(resp,opt){
                    var response = Ext.util.JSON.decode(resp.responseText);
                    Ext.MessageBox.alert('Error Message', response['info']);
                    Ext.ux.Logger.error(response['error']);

                },
                // everything ok...
                success: function(resp,opt){

                    var msg = 'Zone(s) removed';
                    Ext.ux.Logger.info(msg);
                    View.notify({html:msg});
                    this.reload();

                },scope:this

            });// END Ajax request
        }

    }); // eo extend

    // register component
    Ext.reg('etfw_dhcp_zonegrid', ETFW_DHCP.Zones.Zone_Grid);


    ETFW_DHCP.Zones.Key_Grid = Ext.extend(Ext.grid.GridPanel, {

        initComponent:function() {

            var selectBoxModel= new Ext.grid.CheckboxSelectionModel({keepSelections:true});

            var defaultEditor = new Ext.ux.grid.RowEditor({
                saveText: 'Update',
                clicksToEdit: 2
            });


            defaultEditor.on({
                scope: this,
                afteredit: function(roweditor, changes, record, rowIndex) {
                              var send_data = record.data;

                              var conn = new Ext.data.Connection({
                                        listeners:{
                                            // wait message.....
                                            beforerequest:function(){
                                                Ext.MessageBox.show({
                                                    title: 'Please wait',
                                                    msg: record.phantom ? 'Adding key info...' : 'Updating key info...',
                                                    width:300,
                                                    wait:true,
                                                    modal: false
                                                });
                                            },// on request complete hide message
                                            requestcomplete:function(){Ext.MessageBox.hide();}
                                        }
                                    });// end conn

                              conn.request({
                                    url: this.url,
                                    params:{id:this.serviceId,method: record.phantom ? 'add_key' : 'set_key',params:Ext.encode(send_data)},
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
                                        var msg = record.phantom ? 'Key created successfully' : 'Key edited successfully';
                                        Ext.ux.Logger.info(msg);
                                        View.notify({html:msg});
                                        this.store.reload();

                                    },scope:this
                              });// END Ajax request
                }
            });


            this.tbar = [
                {
                    iconCls: 'icon-add',
                    text: 'Add key',
                    handler: function(){
                        var init_alg = this.algorithm_combo.getStore().getAt(0).data['field1'];

                        var key_Record = this.store.recordType;
                        var key_r = new key_Record({
                            key: '',
                            algorithm: init_alg,
                            secret:''
                        });

                        defaultEditor.stopEditing();

                        this.store.insert(0, key_r);
                        this.getView().refresh();
                        this.getSelectionModel().selectRow(0);
                        defaultEditor.startEditing(0);

                    },scope:this
                },
                {
                    text:'Edit key',
                    ref: '../editBtn',
                    tooltip:'Edit selected key',
                    disabled:true,
                    handler: function(){
                        var selected = this.getSelectionModel().getSelected();
                        defaultEditor.stopEditing();
                        defaultEditor.startEditing(this.store.indexOf(selected));
                    },
                    scope:this
                },
                {
                    text:'Delete key(s)',
                    ref: '../removeBtn',
                    tooltip:'Delete the selected key(s)',
                    iconCls:'remove',
                    disabled:true,
                    handler: function(){
                        new Grid.util.DeleteItem({panel: this.id});
                    },scope:this
                }];


            this.algorithm_combo = new Ext.form.ComboBox({
                mode: 'local',
                triggerAction: 'all',
                fieldLabel: '',
                hideLabel:true,
                emptyText:'',
                allowBlank: true,
                readOnly:true,
                editable:false,
                store:['hmac-md5'],
               // valueField: 'value',
               // displayField: 'name',
                width:70
            });

            // configure the grid
            Ext.apply(this, {
                store:new Ext.data.GroupingStore({
                    reader:new Ext.data.JsonReader({

                        totalProperty:'total'
                        ,idProperty:'uuid'
                        ,root:'data'
                        ,fields:[
                            'uuid','key','algorithm','secret'
                        ]
                    })
                    ,proxy:new Ext.data.HttpProxy({url:this.url})
                    ,baseParams:this.baseParams
                    ,groupField:'key'
                    ,sortInfo:{field:'key', direction:'ASC'}
                    ,listeners:{
                        load:{scope:this, fn:function(){}}
                    }
                })
                ,columns:[
                    selectBoxModel,
                    {header: "Key ID", width: 40, sortable: true, dataIndex: 'key',
                        renderer: function (value, metadata, record, rowIndex, colIndex, store) {
                                metadata.attr = 'ext:qtip="Double-click to edit"';
                                return value;
                        },editor: {xtype: 'textfield',allowBlank: true}}
                    ,{header: "Algorithm", width: 40, sortable: true, allowBlank:true,dataIndex: 'algorithm',
                        editor: this.algorithm_combo}
                    ,{header: "Secret", width: 20, sortable: true, dataIndex: 'secret',
                        editor: {xtype: 'textfield',allowBlank: true}}
                ]
                ,stateful:false
                ,sm:selectBoxModel
                ,view: new Ext.grid.GroupingView({
                    forceFit:true
                    ,emptyText: 'Empty!'  //  emptyText Message
                    ,deferEmptyText:false
                    ,groupTextTpl:'{text} ({[values.rs.length]} {[values.rs.length > 1 ? "Items" : "Item"]})'
                })
                ,loadMask:true
                ,plugins:[defaultEditor]
                ,height:200
                ,autoScroll:true

            }); // eo apply

            // add bottom toolbar
            this.bbar = [
                        '->',
                        {text: 'Refresh',
                            xtype: 'button',
                            tooltip: 'refresh',
                            iconCls: 'x-tbar-loading',
                            scope:this,
                            handler: function(button,event)
                            {
                                button.addClass('x-item-disabled');
                                this.store.reload({
                                    callback:function(){button.removeClass('x-item-disabled');}});
                            }
                        }
                ];


            // call parent
            ETFW_DHCP.Zones.Key_Grid.superclass.initComponent.apply(this, arguments);

            this.getSelectionModel().on('selectionchange', function(sm){
                this.editBtn.setDisabled(sm.getCount() < 1);
                this.removeBtn.setDisabled(sm.getCount() < 1);
            },this);

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
                                text:'Delete key',
                                tooltip:'Delete this item',
                                iconCls:'remove',
                                handler: function(){
                                            new Grid.util.DeleteItem({panel: this.id});
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
            ETFW_DHCP.Zones.Key_Grid.superclass.onRender.apply(this, arguments);

            // start w/o grouping
            //		this.store.clearGrouping();
            //var store = grid.getStore();
            // store.load.defer(20,store);
            // load the store
            //this.store.load({params:{start:0, limit:10}});
            this.store.load.defer(20,this.store);


        } // eo function onRender
        ,reload:function(){this.store.reload();}
        ,deleteData:function(items){

            var conn = new Ext.data.Connection({
                listeners:{
                    // wait message.....
                    beforerequest:function(){
                        Ext.MessageBox.show({
                            title: 'Please wait',
                            msg: 'Removing key(s)...',
                            width:300,
                            wait:true,
                            modal: false
                        });
                    },// on request complete hide message
                    requestcomplete:function(){Ext.MessageBox.hide();}
                }
            });// end conn
            var keys = [];

            for(var i=0,len = items.length;i<len;i++){
                keys[i] = {'uuid':items[i].data.uuid};

            }

            conn.request({
                url: this.url,
                params:{id:this.serviceId,method:'del_declarations',params:Ext.encode({'keys':keys})},
                failure: function(resp,opt){
                    var response = Ext.util.JSON.decode(resp.responseText);
                    Ext.MessageBox.alert('Error Message', response['info']);
                    Ext.ux.Logger.error(response['error']);

                },
                // everything ok...
                success: function(resp,opt){

                    var msg = 'DNS Key(s) removed';
                    Ext.ux.Logger.info(msg);
                    View.notify({html:msg});
                    this.reload();

                },scope:this

            });// END Ajax request
        }

    }); // eo extend

    // register component
    Ext.reg('etfw_dhcp_keygrid', ETFW_DHCP.Zones.Key_Grid);




    ETFW_DHCP.Zones.Main = function(serviceId,containerId) {

        this.serviceId = serviceId;
        this.containerId = containerId;


        ETFW_DHCP.Zones.Main.superclass.constructor.call(this, {
            id:this.containerId+'-panel',
            title: 'DNS zones',
            layout:'border',
            items:[{
                    xtype: 'tabpanel',
                    region: 'center',
                    margins: '5 5 5 0',
                    tabPosition: 'bottom',
                    activeTab: 0,
                    items: [{

                            title: 'Zones info',
                            url:<?php echo json_encode(url_for('etfw/json'))?>,
                            serviceId:this.serviceId,
                            baseParams:{id:this.serviceId,method:'list_zone'},
                            xtype:'etfw_dhcp_zonegrid'
                        }]
                },
                {region:'east',layout:'fit',width:350,collapsible:true,
                    title:'TSIG Keys',
                    items:[{url:<?php echo json_encode(url_for('etfw/json'))?>,
                            serviceId:this.serviceId,
                            baseParams:{id:this.serviceId,method:'list_key'},
                            xtype:'etfw_dhcp_keygrid'}]
                }
            ]

        });


    }//eof

    // define public methods
    Ext.extend(ETFW_DHCP.Zones.Main, Ext.Panel,{});
</script>