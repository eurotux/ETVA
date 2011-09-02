<script>

/*
 *
 * ACTIVE CONFIGURATION TAB
 *
 */
/*
 * active now grid
 *
 */
Ext.ns('ETFW.Network.Routing.ActiveRoutes');
ETFW.Network.Routing.ActiveRoutes.Grid = Ext.extend(Ext.grid.GridPanel, {
    formCmp:null,
    initComponent:function() {

        // show check boxes
        var selectBoxModel= new Ext.grid.CheckboxSelectionModel({keepSelections:true});

        // column model
        var cm = new Ext.grid.ColumnModel([
            selectBoxModel,
            {header: "Destination", dataIndex: 'dest', width:120,  sortable: true},
            {header: "Gateway", dataIndex: 'gateway', width:120, sortable: true},
            {header: "Netmask", dataIndex: 'netmask', width:120, sortable: true},
            {header: "Interface", dataIndex: 'iface', width:120, sortable: true}
        ]);

        var dataStore = new Ext.data.JsonStore({
            url: this.url,
            baseParams:{id:this.service_id,method:'list_routes'},
            remoteSort: false,
            totalProperty: 'total',
            root: 'data',
            fields: [{name:'dest'},{name:'gateway'},
                {name:'netmask'},{name:'iface'},{name:'default'}] // initialized from json metadata
        });
        dataStore.setDefaultSort('dest', 'ASC');

        var config = {
            store:dataStore
            ,cm:cm
            ,sm:selectBoxModel
            ,viewConfig:{forceFit:true}
            ,loadMask:true
        }; // eo config object

        // apply config
        Ext.apply(this, Ext.apply(this.initialConfig, config));        

        this.bbar = new Ext.ux.grid.TotalCountBar({
            store:this.store
            ,displayInfo:true
        });

        this.tbar = [{
                text:'Add',
                tooltip:'Add a New Route',
                iconCls:'add',
                handler: function(){
                    this.formCmp.clean();
                },scope:this
            },
            '-',{
                ref: '../removeBtn',
                text:'Delete',
                tooltip:'Delete the selected item(s)',
                iconCls:'remove',
                disabled:true,
                handler: function(){
                    new Grid.util.DeleteItem({panel: this.id});
                },scope:this
            }];

        // call parent
        ETFW.Network.Routing.ActiveRoutes.Grid.superclass.initComponent.apply(this, arguments);

        this.getSelectionModel().on('selectionchange', function(sm){
            this.removeBtn.setDisabled(sm.getCount() < 1);
        },this);

        /************************************************************
         * handle contextmenu event
         ************************************************************/
        this.addListener("rowcontextmenu", onContextMenu, this);
        function onContextMenu(grid, rowIndex, e) {
            if (!this.menu) {
                this.menu = new Ext.menu.Menu({
                    // id: 'menus',
                    items: [{
                            text:'Delete',
                            tooltip:'Delete the selected item(s)',
                            iconCls:'remove',
                            handler: function(){
                                new Grid.util.DeleteItem({panel: grid.id});
                            }
                        }]
                });
            }
            e.stopEvent();
            this.menu.showAt(e.getXY());
        }


        // load the store at the latest possible moment
        this.on({
            afterlayout:{scope:this, single:true, fn:function() {
                    this.store.load();
                }}
        });

    } // eo function initComponent
    ,reload : function() {
        this.store.load();
    }
    ,// call delete stuff now
    // Server side will receive delData throught parameter
    deleteData : function (items) {

        var conn = new Ext.data.Connection({
            listeners:{
                // wait message.....
                beforerequest:function(){
                    Ext.MessageBox.show({
                        title: 'Please wait',
                        msg: 'Deleting route(s)...',
                        width:300,
                        wait:true,
                        modal: false
                    });
                },// on request complete hide message
                requestcomplete:function(){Ext.MessageBox.hide();}
            }
        });// end conn

        var routes = [];

        for(var i=0,len = items.length;i<len;i++){
            routes[i] = {"dest":items[i].data.dest,
                        "netmask":items[i].data.netmask,
                        "gateway":items[i].data.gateway,
                        "iface":items[i].data.iface
                        };
        }

        var send_data = {'routes':routes};

        conn.request({
            url: this.url,
            params:{id:this.service_id,method:'delete_routes',params:Ext.encode(send_data)},
            failure: function(resp,opt){
                var response = Ext.util.JSON.decode(resp.responseText);
                Ext.MessageBox.alert('Error Message', response['info']);
                Ext.ux.Logger.error(response['error']);

            },
            // everything ok...
            success: function(resp,opt){
                var msg = 'Deleted route(s)';
                Ext.ux.Logger.info(msg);
                View.notify({html:msg});
                this.reload();

            },scope:this
        });// END Ajax request

    }

});
Ext.reg('etfw_network_routing_activegrid', ETFW.Network.Routing.ActiveRoutes.Grid);

// bottom panel
ETFW.Network.Routing.CreateActiveForm = function(config) {    
    

    Ext.apply(this,config);

    this.dest = new Ext.form.TextField({
        fieldLabel:'',
        hideLabel:true,
        name:'dest',
        maxLength: 15,
        width:100,
        listeners:{
            focus:function(){Ext.getCmp('dest_source_radio').setValue(true);}
        }
    });

    this.netmask = new Ext.form.TextField({
        fieldLabel: '',
        hideLabel:true,
        name: 'netmask',
        maxLength: 15,
        allowBlank:false,
        width: 100,
        emptyText:'Specify netmask'

    });

    this.netmask_source_radio_default = new Ext.form.Radio({
        style:'margin-top:5px', name:'netmask_source',width:60,fieldLabel:'Netmask for destination',inputValue: '',boxLabel:'Default',
        listeners:{
            check:function(chkbox,checked){
                if(checked)
                    this.netmask.setDisabled(true);
                else this.netmask.setDisabled(false);
            },scope:this
        }
    });

    this.route_source_radio_default = new Ext.form.Radio({
        style:'margin-top:5px', name:'route_source',width:115,fieldLabel:'Route via',checked:true,inputValue: 'iface',boxLabel:'Network interface',
        listeners:{
            check:function(chkbox,checked){
                if(checked){
                    this.intfCombo.setDisabled(false);
                    this.gateway.setDisabled(true);
                }else{
                    this.intfCombo.setDisabled(true);
                    this.gateway.setDisabled(false);

                }
            },scope:this
        }
    });

    this.gateway = new Ext.form.TextField({
        fieldLabel: '',
        allowBlank:false,
        hideLabel:true,
        disabled:true,
        name: 'gateway',
        maxLength: 15,
        width: 100

    });

    var intf_store = new Ext.data.JsonStore({
        url:<?php echo json_encode(url_for('etfw/json'))?>,
        baseParams:{id:this.service_id,method:'boot_interfaces',mode:'boot_real_interfaces'},
        id: 'fullname',
        remoteSort: false,
        totalProperty: 'total',
        root: 'data',
        fields: [{name:'name'},{name:'fullname'}] // initialized from json metadata
    });
    intf_store.setDefaultSort('fullname', 'ASC');


    this.intfCombo = new Ext.form.ComboBox({
        mode: 'remote',
        reload:true,
        triggerAction: 'all',
        //   name:'up',
        fieldLabel: '',
        hideLabel:true,
        emptyText:'Select...',
        allowBlank: false,
        readOnly:true,
        store:intf_store,
        valueField: 'fullname',
        hiddenName:'iface',
        displayField: 'fullname',
        width:70
    });

    var allFields =
        new Ext.form.FieldSet({
            border:false,
            defaults:{labelWidth:130},
          //  height:90,
            //    autoHeight:true,
            items:
                [
                // destination
                {
                    layout:'table',
                    layoutConfig: {columns:3},
                    items:[
                           {
                            labelAlign:'left',
                            layout:'form',
                            items:[{xtype:'radio',style:'margin-top:5px', name:'dest_source',width:90,fieldLabel:'Route destination',inputValue: '',checked:true,boxLabel:'Default route'}]
                           },
                           {
                            labelAlign:'left',
                            layout:'form',
                            items:[{xtype:'radio', id:'dest_source_radio',name:'dest_source',width:20,fieldLabel:'',hideLabel:true,boxLabel:'',inputValue: 'dest'}]
                           },
                           {
                            labelAlign:'left',
                            labelWidth:30,
                            layout:'form'
                            ,items:this.dest
                           }]
                } // end destination
                ,
                // netmask
                {
                    layout:'table',
                    layoutConfig: {columns:3},
                    items:[
                           {
                            labelAlign:'left',
                            layout:'form',
                            items:this.netmask_source_radio_default
                           },
                           {
                            labelAlign:'left',
                            layout:'form',
                            items:[{xtype:'radio', name:'netmask_source',width:20,fieldLabel:'',hideLabel:true,boxLabel:'',checked:true,inputValue: 'netmask'}]
                           },
                           {
                            labelAlign:'left',
                            layout:'form'
                            ,items:this.netmask
                           }]
                } // end netmask
                ,
                // route via
                {
                    layout:'table',
                    layoutConfig: {columns:2},
                    items:[
                           {
                            labelAlign:'left',
                            layout:'form',
                            items:this.route_source_radio_default
                           },
                           {
                            labelAlign:'left',
                            width:82,
                            layout:'form'
                            ,items:this.intfCombo
                           }

                           ]
                },
                {
                    layout:'table',
                    layoutConfig: {columns:2},
                    items:[
                           {
                            labelAlign:'left',
                            layout:'form',
                            items:[{xtype:'radio', style:'margin-top:5px', name:'route_source',width:68,fieldLabel:'',boxLabel:'Gateway',inputValue: 'gateway'}]
                           },
                           {
                            labelAlign:'left',
                            layout:'form'
                            ,items:this.gateway
                           }
                    ]
                }
            ]
    });


    this.savebtn = this.buildUIForm();



    ETFW.Network.Routing.CreateActiveForm.superclass.constructor.call(this, {
        // baseCls: 'x-plain',
       // labelWidth: 90,
      //  defaultType: 'textfield',
        url:<?php echo json_encode(url_for('etfw/json'))?>,
        buttonAlign:'center',
        frame:true,
        items: allFields
        ,buttons: [this.savebtn]
    });

};

Ext.extend(ETFW.Network.Routing.CreateActiveForm, Ext.form.FormPanel, {
    /*
     * build save button
     */
    buildUIForm:function(){

        return new Ext.Button({text: 'Save',
            handler: function() {
                var alldata = this.form.getValues();

                if (this.form.isValid()) {

                    var alldata = this.form.getValues();

                    var dest_source = alldata['dest_source'];
                    var dest = '';
                    switch(dest_source){
                        case 'dest': dest = alldata['dest'];
                            break;
                        default: break;
                    }

                    var netmask_source = alldata['netmask_source'];
                    var netmask = '';
                    switch(netmask_source){
                        case 'netmask': netmask = alldata['netmask'];
                            break;
                        default: break;
                    }

                    var route_source = alldata['route_source'];
                    var gateway = '';
                    var iface = '';
                    switch(route_source){
                        case 'iface': iface = alldata['iface'];
                            break;
                        case 'gateway': gateway = alldata['gateway'];
                            break;
                        default: break;
                    }

                    var send_data = {"dest":dest,
                        "netmask":netmask,
                        "gateway":gateway,
                        "iface":iface
                    };

                    var conn = new Ext.data.Connection({
                        listeners:{
                            // wait message.....
                            beforerequest:function(){
                                Ext.MessageBox.show({
                                    title: 'Please wait',
                                    msg: 'Processing route...',
                                    width:300,
                                    wait:true,
                                    modal: false
                                });
                            },// on request complete hide message
                            requestexception:function(){Ext.MessageBox.hide();}
                            ,requestcomplete:function(){Ext.MessageBox.hide();}
                        }
                    });// end conn


                    conn.request({
                        url: this.url,
                        params:{id:this.service_id,method:'create_route',params:Ext.encode(send_data)},
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

                            var msg = 'Added route '+dest;
                            Ext.ux.Logger.info(msg);
                            View.notify({html:msg});
                            Ext.getCmp(this.parent_id).reload();
                        },scope:this
                    });// END Ajax request


                } else{
                    Ext.MessageBox.alert('error', 'Please fix the errors noted.');
                }
            },scope:this
        }

    );

    },
    clean : function() {

        this.dest.setValue(null);
        this.gateway.setValue(null);
        this.netmask.setValue(null);
        this.intfCombo.setValue(null);


    }

});

/*
 * END ACTIVE CONFIGURATION
 *
 */





Ext.ns('ETFW.Network.Routing.BootRoutes');

ETFW.Network.Routing.BootRoutes.Grids = function(){
    return{

        init:function(parentContainerId,service_id){

            this.parentContainerId = parentContainerId;
            this.url = <?php echo json_encode(url_for('etfw/json'))?>;

            this.storeIsRouter = new Ext.data.Store({
                //  store:dataSore,
                reader: new Ext.data.JsonReader({
                    root:'IsRouter',
                    fields:[{name:'isRouter', mapping:'value'}]
                })
            });


            // static routes
            var storeStatic = new Ext.data.Store({
                reader: new Ext.data.JsonReader({
                    record: 'plant',
                    root:'dataStaticRoutes',
                    totalProperty: 'totalStaticRoutes',
                    fields:[{name:'device'},{name:'address'},{name:'netmask'},{name:'gateway'}]
                })
            });

            var staticRoutesCM = new Ext.grid.ColumnModel([
                // selectBoxModel,
                // {id:'name',header: "Name", width: 120, dataIndex: 'name', sortable:true},
                {header: "Interface", dataIndex: 'device', width:120,
                    sortable: true,
                    editor: new Ext.form.TextField()
                },
                {header: "Network", dataIndex: 'address', width:120,
                    sortable: true,
                    editor: new Ext.form.TextField()
                },
                {header: "Netmask", dataIndex: 'netmask', width:120,
                    sortable: true,
                    editor: new Ext.form.TextField()
                },
                {header: "Gateway", dataIndex: 'gateway', width:120,
                    sortable: true,
                    editor: new Ext.form.TextField()
                }

            ]);

            var staticEditor = new Ext.ux.grid.RowEditor({
                saveText: 'Update'
            });

            this.staticRoutes = new Ext.grid.GridPanel({
                store:storeStatic,
                cm:staticRoutesCM,
                width:350,
                autoHeight: true,
                viewConfig:{
                    forceFit:true,
                    emptyText: 'Empty!',  //  emptyText Message
                    deferEmptyText:false
                },
                // height:200,
              //  layout:'fit',
               // loadMask:true
                plugins: [staticEditor],
                tbar: [
                      {
                        iconCls: 'add',
                        text: 'Add static route',
                        handler: function(){
                            var Record = this.staticRoutes.getStore().recordType;
                            var rec = new Record({device:'',
                                                  address:'',
                                                  netmask:'',
                                                  gateway:''});

                            staticEditor.stopEditing();
                            storeStatic.insert(0, rec);
                            this.staticRoutes.getView().refresh();
                            this.staticRoutes.getSelectionModel().selectRow(0);
                            staticEditor.startEditing(0);
                        },scope:this
                     },{
                        ref: '../removeBtn',
                        iconCls:'remove',
                        text: 'Remove static route',
                        disabled: true,
                        handler: function(){
                            staticEditor.stopEditing();
                            var s = this.staticRoutes.getSelectionModel().getSelections();
                            for(var i = 0, r; r = s[i]; i++){
                                storeStatic.remove(r);
                            }

                        },scope:this
                    }

                    ,'->',
                    {text: 'Refresh',
                        xtype: 'button',
                        tooltip: 'refresh',
                        iconCls: 'x-tbar-loading',
                        scope:this,
                        handler: function(button,event){this.loadData(service_id);}
                    }
                ]

            });

            this.staticRoutes.getSelectionModel().on('selectionchange', function(sm){
                this.staticRoutes.removeBtn.setDisabled(sm.getCount() < 1);
            },this);


            // local routes
            var localRoutesCM = new Ext.grid.ColumnModel([
                {header: "Interface", dataIndex: 'device', width:120,
                    sortable: true,
                    editor: new Ext.form.TextField()
                },
                {header: "Network", dataIndex: 'address', width:120,
                    sortable: true,
                    editor: new Ext.form.TextField()
                },
                {header: "Netmask", dataIndex: 'netmask', width:120,
                    sortable: true,
                    editor: new Ext.form.TextField()
                }
            ]);

            var storeLocal = new Ext.data.Store({
                reader: new Ext.data.JsonReader({
                    root:'dataLocalRoutes',
                    fields:[{name:'device'},{name:'address'},{name:'netmask'}]
                })
            });
            storeLocal.setDefaultSort('address', 'ASC');

            var localEditor = new Ext.ux.grid.RowEditor({
                saveText: 'Update'
            });


            this.localRoutes = new Ext.grid.GridPanel({
                store:storeLocal,
                cm:localRoutesCM,
                autoHeight: true,
                width:350,
                viewConfig:{
                    forceFit:true,
                    emptyText: 'Empty!',  //  emptyText Message
                    deferEmptyText:false
                },
                //loadMask:true,
                plugins: [localEditor],
                tbar: [
                      {
                        iconCls: 'add',
                        text: 'Add local route',
                        handler: function(){
                            var Record = this.localRoutes.getStore().recordType;
                            var rec = new Record({device:'',
                                                  address:'',
                                                  netmask:''});

                            localEditor.stopEditing();
                            storeLocal.insert(0, rec);
                            this.localRoutes.getView().refresh();
                            this.localRoutes.getSelectionModel().selectRow(0);
                            localEditor.startEditing(0);
                        },scope:this
                     },{
                        ref: '../removeBtn',
                        iconCls:'remove',
                        text: 'Remove local route',
                        disabled: true,
                        handler: function(){
                            localEditor.stopEditing();
                            var s = this.localRoutes.getSelectionModel().getSelections();
                            for(var i = 0, r; r = s[i]; i++){
                                storeStatic.remove(r);
                            }
                        },scope:this
                    }

                    ,'->',
                    {text: 'Refresh',
                        xtype: 'button',
                        tooltip: 'refresh',
                        iconCls: 'x-tbar-loading',
                        scope:this,
                        handler: function(button,event){this.loadData(service_id);}
                    }
                ]

            });

            this.localRoutes.getSelectionModel().on('selectionchange', function(sm){
                this.localRoutes.removeBtn.setDisabled(sm.getCount() < 1);
            },this);

            // default routes
            var defaultRoutesCM = new Ext.grid.ColumnModel([
                {header: "Interface", dataIndex: 'dev', width:120,
                    sortable: true,
                    editor: new Ext.form.TextField()
                },
                {header: "Gateway", dataIndex: 'gateway', width:120,
                    sortable: true,
                    editor: new Ext.form.TextField()
                }
            ]);

            var storeDefault = new Ext.data.Store({
                //  store:dataSore,
                reader: new Ext.data.JsonReader({
                    root:'dataDefaultRoutes',
                    totalProperty: 'totalDefaultRoutes',
                    fields:[{name:'dev'},{name:'gateway'}]
                })
            });

            var defaultEditor = new Ext.ux.grid.RowEditor({
                saveText: 'Update'
            });

            this.defaultRoutes = new Ext.grid.GridPanel({
                store:storeDefault,
                cm:defaultRoutesCM,
               // height:200,
                autoHeight: true,
                border:true,
                width:350,
             //   layout:'fit',
                viewConfig:{
                    forceFit:true,
                    emptyText: 'Empty!',  //  emptyText Message
                    deferEmptyText:false
                },
                plugins: [defaultEditor],
                tbar: [
                      {
                        iconCls: 'add',
                        text: 'Add default route',
                        handler: function(){
                            var Record = this.defaultRoutes.getStore().recordType;
                            var rec = new Record({dev:'',gateway:''});

                            defaultEditor.stopEditing();
                            storeDefault.insert(0, rec);
                            this.defaultRoutes.getView().refresh();
                            this.defaultRoutes.getSelectionModel().selectRow(0);
                            defaultEditor.startEditing(0);
                        },scope:this
                     },{
                        ref: '../removeBtn',
                        iconCls:'remove',
                        text: 'Remove default route',
                        disabled: true,
                        handler: function(){
                            defaultEditor.stopEditing();
                            var s = this.defaultRoutes.getSelectionModel().getSelections();
                            for(var i = 0, r; r = s[i]; i++){
                                storeDefault.remove(r);
                            }
                        },scope:this
                    }
                    ,'->',
                    {text: 'Refresh',
                        xtype: 'button',
                        tooltip: 'refresh',
                        iconCls: 'x-tbar-loading',
                        scope:this,
                        handler: function(button,event){
                            this.loadData(service_id);}
                    }
                ]
            });

            this.defaultRoutes.getSelectionModel().on('selectionchange', function(sm){
                this.defaultRoutes.removeBtn.setDisabled(sm.getCount() < 1);
            },this);




        },
        getDefaultGrid:function(){
            return this.defaultRoutes;
        },
        getStaticGrid:function(){
            return this.staticRoutes;
        },
        getLocalGrid:function(){
            return this.localRoutes;
        },
        getRecordsArray:function(ds){

            var totalRec = ds.getCount();
            var recs = [];

            Ext.each(ds.getRange(0,totalRec),function(e){
                recs.push(e.data);
            });

            return recs;

        }
        ,getAllData:function(){

            var staticDs = this.staticRoutes.getStore();
            var staticRecords = this.getRecordsArray(staticDs);

            var defaultDs = this.defaultRoutes.getStore();
            var defaultRecords = this.getRecordsArray(defaultDs);

            var localDs = this.localRoutes.getStore();
            var localRecords = this.getRecordsArray(localDs);

            var data = {"StaticRoutes":staticRecords,
                        "DefaultRoutes":defaultRecords,
                        "LocalRoutes":localRecords
                       };

            return data;

        },
        loadData:function(service_id){            

            var conn = new Ext.data.Connection({
                    listeners:{
                        // wait message.....
                        beforerequest:function(){
                            Ext.MessageBox.show({
                                title: 'Please wait',
                                msg: 'Processing routes...',
                                width:300,
                                wait:true,
                                modal: false
                            });
                        },// on request complete hide message
                        requestexception:function(){Ext.MessageBox.hide();}
                        ,requestcomplete:function(){Ext.MessageBox.hide();}
                    }
                });// end conn

            conn.request({
                    url: this.url,
                    success: function(response){
                        var decoded_data = Ext.decode(response.responseText);
                        this.defaultRoutes.getStore().loadData(decoded_data);
                        this.staticRoutes.getStore().loadData(decoded_data);
                        this.localRoutes.getStore().loadData(decoded_data);

                        this.storeIsRouter.loadData(decoded_data);
                        var rec = this.storeIsRouter.getAt(0);
                        Ext.getCmp(this.parentContainerId).loadRecord(rec);

                    },
                    params: {id:service_id,method:'get_boot_routing'}
                    ,scope:this
            });
        }
    }
}();


ETFW.Network.Routing.BootRoutes.Form = function(config) {    

    Ext.apply(this,config);

    this.routes = ETFW.Network.Routing.BootRoutes.Grids;
    this.routes.init(this.id,this.service_id);

    var defaultgrid = this.routes.getDefaultGrid();
    var staticgrid = this.routes.getStaticGrid();
    var localgrid = this.routes.getLocalGrid();

    var allFields = new Ext.form.FieldSet({
                                border:false,
                                defaults:{labelWidth:130},
                                items:[
                                        // default routes
                                        {
                                            layout:'table',
                                            layoutConfig: {columns:2},
                                            items:[
                                                   {
                                                    labelAlign:'left',
                                                    layout:'form',
                                                    items:[{fieldLabel:'Default routes'}]
                                                   },
                                                   {
                                                    labelAlign:'left',
                                                    layout:'form',
                                                    bodyStyle: 'padding-bottom:10px;',
                                                    items:defaultgrid
                                                   }
                                            ]
                                        } // end default routes
                                        ,
                                        // isRouter
                                        {
                                            layout:'table',
                                            layoutConfig: {columns:2},
                                            items:[
                                                   {
                                                    labelAlign:'left',
                                                    layout:'form',
                                                    items:[{xtype:'radio', name:'isRouter', width:60,fieldLabel:'Act as router',boxLabel:'Yes',inputValue: '1'}]
                                                   },
                                                   {
                                                    labelAlign:'left',
                                                    layout:'form',
                                                    items:[{xtype:'radio',width:60,fieldLabel:'', name:'isRouter', hideLabel:true,boxLabel:'No',inputValue: '0'}]
                                                   }
                                            ]
                                        } // end isRouter
                                        ,
                                        // static routes
                                        {
                                            layout:'table',
                                            layoutConfig: {columns:2},
                                            items:[
                                                   {
                                                    labelAlign:'left',
                                                    layout:'form',
                                                    items:[{fieldLabel:'Static routes'}]
                                                   },
                                                   {
                                                    labelAlign:'left',
                                                    layout:'form',
                                                    bodyStyle: 'padding-bottom:10px;',
                                                    items:staticgrid
                                                   }
                                            ]
                                        },
                                        // local routes
                                        {
                                            layout:'table',
                                            layoutConfig: {columns:2},
                                            items:[
                                                   {
                                                    labelAlign:'left',
                                                    layout:'form',
                                                    items:[{fieldLabel:'Local routes'}]
                                                   },
                                                   {
                                                    labelAlign:'left',
                                                    layout:'form',
                                                    bodyStyle: 'padding-bottom:10px;',
                                                    items:localgrid
                                                   }
                                            ]
                                        }
                                ]
    });


    this.savebtn = new Ext.Button({text: 'Save',
        handler: function() {

            if (this.form.isValid()) {
                var submitData = this.routes.getAllData();
                var allFormdata = this.form.getValues();

                submitData.IsRouter = allFormdata['isRouter'];


                var conn = new Ext.data.Connection({
                    listeners:{
                        // wait message.....
                        beforerequest:function(){
                            Ext.MessageBox.show({
                                title: 'Please wait',
                                msg: 'Processing route...',
                                width:300,
                                wait:true,
                                modal: false
                            });
                        },// on request complete hide message
                        requestexception:function(){Ext.MessageBox.hide();}
                        ,requestcomplete:function(){Ext.MessageBox.hide();}
                    }
                });// end conn

                conn.request({
                    url: this.url,
                    params:{id:this.service_id,method:'set_boot_routing',params:Ext.encode(submitData)},
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

                        var msg = 'Updated routes';
                        Ext.ux.Logger.info(msg);
                        View.notify({html:msg});
                        this.routes.loadData(this.service_id);
                        //Ext.getCmp('grid-routingactive-panel').reload();
                    },scope:this
                });// END Ajax request


            } else{
                Ext.MessageBox.alert('error', 'Please fix the errors noted.');
            }
        },scope:this
        }// end handler

    );


    // define window and pop-up - render formPanel
    ETFW.Network.Routing.BootRoutes.Form.superclass.constructor.call(this, {
        // baseCls: 'x-plain',
        labelWidth: 90,
        url:<?php echo json_encode(url_for('etfw/json'))?>,
        defaultType: 'textfield',
        autoScroll:true,
        width:600,
        buttonAlign:'center',
        // frame:true,
        items: allFields

        ,buttons: [this.savebtn]
    });

};

Ext.extend(ETFW.Network.Routing.BootRoutes.Form, Ext.form.FormPanel, {

    loadRecord : function(rec) {
        this.getForm().loadRecord(rec);
    },
    loadData:function(){
        this.routes.loadData(this.service_id);
    }

});



ETFW.Network.Routing.Main = function(service_id) {

    var routingCreate = new ETFW.Network.Routing.CreateActiveForm({parent_id:'etfw_network_routing-activegrid-'+service_id,service_id:service_id});

    var activePanel = new Ext.Panel({
        title:'Active configuration',
        layout:'border',
        frame:true,
        defaults: {
                collapsible: true,
                split: true,
                useSplitTips:true

        },

        items:[
            {
                id: 'etfw_network_routing-activegrid-'+service_id,
                url:<?php echo json_encode(url_for('etfw/json'))?>,
                region:'center',
                collapsible: false,
                service_id:service_id,
                formCmp: routingCreate,
                //   margins: '5 0 0 0',
                layout:'fit'
                //  ,items:[dd]
                ,xtype:'etfw_network_routing_activegrid'
                // ,items:[listGrid]
            },
            {region:'east',
                margins: '0 0 0 5',
                autoScroll:true,
                title:'Create route',width:400,
                items:routingCreate
            }
        ]


    });

    activePanel.on('beforerender',function(){
        Ext.getBody().mask('Loading ETFW network routing panel...');}
        ,this
    );

    activePanel.on('render',function(){
        Ext.getBody().unmask();}
        ,this
    ,{delay:10}
    );



    var bootroutes = new ETFW.Network.Routing.BootRoutes.Form({id:'etfw_network_routing-bootform-'+service_id,service_id:service_id});
    //
    var bootPanel = new Ext.Panel({
        title:'Boot time configuration',
        layout:'border',
        frame:true,
        autoScroll:true,
        items:[{
                region:'center',
                autoScroll:true
                ,items:bootroutes
               }]

    });

    bootPanel.on({
                afterlayout:{scope:this, single:true, fn:function(){bootroutes.loadData();}}
            });

    bootPanel.on('beforerender',function(){
        Ext.getBody().mask('Loading ETFW network routing panel...');}
        ,this
    );

    bootPanel.on('render',function(){
        Ext.getBody().unmask();}
        ,this
    ,{delay:10}
    );
    //
    //
    //
    /************************************************************
     * Constructor for the Ext.grid.EditorGridPanel
     ************************************************************/
    ETFW.Network.Routing.Main.superclass.constructor.call(this, {

        border:false,
       // frame: true,
        layout:'fit',
        title: 'Routing and gateways',
        items: [{
                xtype:'tabpanel',
                //  layoutOnTabChange:true,
                //  deferredRender:false,
                activeTab:0,
                //           bodyStyle:'padding:5px',
                items:[activePanel
                    ,bootPanel
                ]
            }]
    });
}

// define public methods
Ext.extend(ETFW.Network.Routing.Main, Ext.Panel, {
    reload:function(){


        var tabPanel = this.get(0);

        var activeNowPanel = tabPanel.get(0);
        var activeBootPanel = tabPanel.get(1);

        if(activeNowPanel.rendered)
        {
            var grid_now = activeNowPanel.get(0);
            grid_now.reload();
        }

        if(activeBootPanel.rendered)
        {
            var boot_layout = activeBootPanel.get(0);
            var boot_form = boot_layout.get(0);            
            boot_form.loadData();
        }

    }
});


</script>