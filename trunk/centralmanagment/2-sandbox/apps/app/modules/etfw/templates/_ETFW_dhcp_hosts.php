<script>
    /*
     *
     * HOSTS
     *
     */
    Ext.ns('ETFW_DHCP.Hosts');

    // create pre-configured grid class
    ETFW_DHCP.Hosts.Data_Grid = Ext.extend(Ext.grid.GridPanel, {

        initComponent:function() {

            var selectBoxModel= new Ext.grid.CheckboxSelectionModel({keepSelections:true});

            var store_fields = [];
            var group_field = '';
            var sort_info = '';
            var grid_columns = [];
            switch(this.type){
                case 'host':
                    store_fields = ['uuid','host','assigned','assigned_net','assigned_type','params','option'];

                    group_field = 'assigned';
                    sort_info = {field:'host', direction:'ASC'};
                    grid_columns = [
                        selectBoxModel,
                        {header: "Host", width: 40, sortable: true, dataIndex: 'host',renderer: function (value, metadata, record, rowIndex, colIndex, store) {
                                metadata.attr = 'ext:qtip="Right-click to open sub-menu"';
                                return value;
                            }}
                        ,{header: "Assigned to", width: 20, sortable: true, dataIndex: 'assigned'}
                        ,{header: "Assigned type", width: 20, sortable: true, dataIndex: 'assigned_type'}
                    ];

                    this.tbar = [{
                            text:'Add host',
                            tooltip:'Add a New Host',
                            ref:'../addBtn',
                            iconCls:'add',
                            handler: function(){


                                var form = new ETFW_DHCP.Data_Form({title:'Add Host',parent_grid:this,serviceId:this.serviceId});
                                form.reset();
                                // create and show window
                                var win = new Ext.Window({
                                    title:'Host information'
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
                            text:'Edit host',
                            ref: '../editBtn',
                            tooltip:'Edit selected host',
                            disabled:true,
                            handler:this.editHost,
                            scope:this
                        },
                        '-',
                        {
                            text:'Delete host',
                            ref: '../removeBtn',
                            tooltip:'Delete the selected host(s)',
                            iconCls:'remove',
                            disabled:true,
                            handler: function(){
                                new Grid.util.DeleteItem({panel: this.id});
                            },scope:this
                        }];




                    this.addListener("rowcontextmenu", this.onHostRowContextMenu, this);




                    break;
                case 'group':
                    store_fields = ['uuid','group','assigned','assigned_type','parent','params','option'];

                    group_field = 'assigned';
                    sort_info = {field:'group', direction:'ASC'};
                    grid_columns = [
                        selectBoxModel,
                        {header: "Group", width: 40, sortable: true, dataIndex: 'group',renderer: function (value, metadata, record, rowIndex, colIndex, store) {
                                metadata.attr = 'ext:qtip="Right-click to open sub-menu"';
                                return value;
                            }}
                        ,{header: "Assigned to", width: 20, sortable: true, dataIndex: 'assigned'}
                        ,{header: "Assigned type", width: 20, sortable: true, dataIndex: 'assigned_type'}
                    ];

                    this.tbar = [{
                            text:'Add a new host group',
                            tooltip:'Add a New Host Group',
                            ref:'../addBtn',
                            iconCls:'add',
                            handler: function(){


                                var form = new ETFW_DHCP.Data_Form({title:'Add Host Group',parent_grid:this,serviceId:this.serviceId});
                                form.reset();
                                // create and show window
                                var win = new Ext.Window({
                                    title:'Group information'
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
                            text:'Edit host group',
                            ref: '../editBtn',
                            tooltip:'Edit selected host group',
                            disabled:true,
                            handler:this.editGroup,
                            scope:this
                        },
                        '-',
                        {
                            text:'Delete host group',
                            ref: '../removeBtn',
                            tooltip:'Delete the selected hosts group(s)',
                            iconCls:'remove',
                            disabled:true,
                            handler: function(){
                                new Grid.util.DeleteItem({panel: this.id});
                            },scope:this
                        }];

                    this.addListener("rowcontextmenu", this.onGroupRowContextMenu, this);

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
                    ,listeners:{
                        load:{scope:this, fn:function() {
                                //   this.getSelectionModel().selectFirstRow();
                            }}
                    }
                })
                ,columns:grid_columns
                //	,plugins:[this.action
                //, this.expander
                //  ]
                ,sm:selectBoxModel
                ,view: new Ext.grid.GroupingView({
                    forceFit:true
                    ,groupTextTpl:'{text} ({[values.rs.length]} {[values.rs.length > 1 ? "Items" : "Item"]})'
                })
                ,loadMask:true
                ,viewConfig:{forceFit:true}
                ,height:200
                //    ,layout:'fit'
                //  ,loadMask:true

                ,autoScroll:true
                //			,viewConfig:{forceFit:true}
            }); // eo apply

            // add paging toolbar
            this.bbar = new Ext.ux.grid.TotalCountBar({
                store:this.store
                ,displayInfo:true
            });






            // call parent
            ETFW_DHCP.Hosts.Data_Grid.superclass.initComponent.apply(this, arguments);



            this.getSelectionModel().on('selectionchange', function(sm){
                this.editBtn.setDisabled(sm.getCount() < 1);
                this.removeBtn.setDisabled(sm.getCount() < 1);
            },this);




        } // eo function initComponent
        // }}}
        // {{{
        ,onRender:function() {

            // call parent
            ETFW_DHCP.Hosts.Data_Grid.superclass.onRender.apply(this, arguments);

            // start w/o grouping
            //		this.store.clearGrouping();
            //var store = grid.getStore();
            // store.load.defer(20,store);
            // load the store
            //this.store.load({params:{start:0, limit:10}});
            this.store.load.defer(20,this.store);

        } // eo function onRender
        ,reload:function(){this.store.reload();}
        ,onHostRowContextMenu:function(grid, rowIndex, e) {
            grid.getSelectionModel().selectRow(rowIndex);

            if (!this.menu) {
                this.menu = new Ext.menu.Menu({
                    // id: 'menus',
                    items: [{
                            text:'Edit host',
                            tooltip:'Edit host information of the selected item',
                            iconCls:'editItem',
                            handler: this.editHost,
                            scope:this
                        },{
                            text:'Delete host',
                            tooltip:'Delete this item',
                            iconCls:'remove',
                            handler: function(){
                                Ext.MessageBox.show({
                                    title:'Delete host',
                                    msg: 'You are about to delete this host. <br />Are you sure you want to delete?',
                                    buttons: Ext.MessageBox.YESNOCANCEL,
                                    fn: this.onDeleteHost,
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
        ,onGroupRowContextMenu:function(grid, rowIndex, e) {
            grid.getSelectionModel().selectRow(rowIndex);

            if (!this.menu) {
                this.menu = new Ext.menu.Menu({
                    // id: 'menus',
                    items: [{
                            text:'Edit host group',
                            tooltip:'Edit host group information of the selected item',
                            iconCls:'editItem',
                            handler: this.editGroup,
                            scope:this
                        },{
                            text:'Delete host group',
                            tooltip:'Delete this item',
                            iconCls:'remove',
                            handler: function(){
                                Ext.MessageBox.show({
                                    title:'Delete host group',
                                    msg: 'You are about to delete this host group. <br />Are you sure you want to delete?',
                                    buttons: Ext.MessageBox.YESNOCANCEL,
                                    fn: this.onDeleteGroup,
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
        ,onDeleteGroup:function(btn){

            var selected = this.getSelectionModel().getSelected();
            if(btn=='yes' && selected){


                var conn = new Ext.data.Connection({
                    listeners:{
                        // wait message.....
                        beforerequest:function(){
                            Ext.MessageBox.show({
                                title: 'Please wait',
                                msg: 'Removing host group info...',
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
                        var msg = 'Host group removed successfully';
                        Ext.ux.Logger.info(msg);
                        View.notify({html:msg});
                        this.reload();
                        Ext.getCmp(this.containerId+'-hosts_grid').reload();




                    },scope:this
                });// END Ajax request
            }

        }
        ,onDeleteHost:function(btn){

            var selected = this.getSelectionModel().getSelected();
            if(btn=='yes' && selected){


                var conn = new Ext.data.Connection({
                    listeners:{
                        // wait message.....
                        beforerequest:function(){
                            Ext.MessageBox.show({
                                title: 'Please wait',
                                msg: 'Removing host info...',
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
                        var msg = 'Host removed successfully';
                        Ext.ux.Logger.info(msg);
                        View.notify({html:msg});
                        this.reload();                        
                        Ext.getCmp(this.containerId+'-groups_grid').reload();




                    },scope:this
                });// END Ajax request
            }

        }
        ,editHost:function(){

            var selected = this.getSelectionModel().getSelected();



            var extra_txt = '<br>';

            if(selected.data['assigned_net']) extra_txt += selected.data['assigned_net'];
            //   if(type) extra_txt += ' in '+selected.data['assigned'];
            else extra_txt += '&nbsp';
            //   if(selected.data['parent-args']) extra_txt = '<br> in '+type+selected.data['parent-args'];


            var form = new ETFW_DHCP.Data_Form({title:'Edit Host'+extra_txt,parent_grid:this,serviceId:this.serviceId});
            //   form.loadSubnetRecord(selected);
            var extra_txt_options = '<br> for host '+selected.data['host'];
            var client_options = new ETFW_DHCP.ClientOptions_Form({title:'Client Options'+extra_txt_options,parent_grid:this,serviceId:this.serviceId});
            var record_option= new Object();
            record_option.data = selected.data['option'];

            var tabs = new Ext.TabPanel({
                activeTab:0,
                items:[form,client_options]
            });
            // create and show window
            var win = new Ext.Window({
                title:'Host information'
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
            form.loadHostRecord(selected.data['uuid'],record);





        }
        ,editGroup:function(){


            var selected = this.getSelectionModel().getSelected();



            var extra_txt = '<br>';


            var type = '';
            switch(selected.data['assigned_type']){
                case 'subnet': type = 'subnet ';
                    break;
                case 'shared-network': type = 'shared network ';
                    break;
                default:break;
            }

            if(selected.data['assigned']) extra_txt += 'in '+type+selected.data['parent'];
            else extra_txt += '&nbsp';



            var form = new ETFW_DHCP.Data_Form({title:'Edit Host Group'+extra_txt,parent_grid:this,serviceId:this.serviceId});

            var extra_txt_options = '<br> for '+selected.data['group']+' group';
            var client_options = new ETFW_DHCP.ClientOptions_Form({title:'Client Options'+extra_txt_options,parent_grid:this,serviceId:this.serviceId});
            var record_option= new Object();
            record_option.data = selected.data['option'];

            var tabs = new Ext.TabPanel({
                activeTab:0,
                items:[form,client_options]
            });
            // create and show window
            var win = new Ext.Window({
                title:'Group information'
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
            form.loadGroupRecord(selected.data['uuid'],record);



        }
        ,deleteData:function(items){


            var conn = new Ext.data.Connection({
                listeners:{
                    // wait message.....
                    beforerequest:function(){
                        Ext.MessageBox.show({
                            title: 'Please wait',
                            msg: 'Removing host(s)/host group(s)...',
                            width:300,
                            wait:true,
                            modal: false
                        });
                    },// on request complete hide message
                    requestcomplete:function(){Ext.MessageBox.hide();}
                }
            });// end conn
            var groups = [];

            for(var i=0,len = items.length;i<len;i++){
                groups[i] = {"uuid":items[i].data.uuid};

            }




            conn.request({
                url: this.url,
                params:{id:this.serviceId,method:'del_declarations',params:Ext.encode([groups])},
                failure: function(resp,opt){
                    var response = Ext.util.JSON.decode(resp.responseText);
                    Ext.MessageBox.alert('Error Message', response['info']);
                    Ext.ux.Logger.error(response['error']);

                },
                // everything ok...
                success: function(resp,opt){

                    var msg = 'Host(s)/host group(s) deleted';
                    Ext.ux.Logger.info(msg);
                    View.notify({html:msg});

                    this.reload();                    
                    Ext.getCmp(this.containerId+'-hosts_grid').reload();







                },scope:this

            });// END Ajax request

        }





    }); // eo extend

    // register component
    Ext.reg('etfw_dhcp_hostgrid', ETFW_DHCP.Hosts.Data_Grid);


    ETFW_DHCP.Hosts.Main = function(serviceId,containerId) {

        this.serviceId = serviceId;
        this.containerId = containerId;


        ETFW_DHCP.Hosts.Main.superclass.constructor.call(this, {
            title: 'Hosts and Hosts Groups',
            layout:'border',
            items:[{
                    xtype: 'tabpanel',
                    region: 'center',
                    margins: '5 5 5 0',
                    tabPosition: 'bottom',
                    activeTab: 0,
                    items: [{

                            title: 'Hosts info',
                            url:<?php echo json_encode(url_for('etfw/json'))?>,
                            type:'host',
                            serviceId:this.serviceId,
                            id:this.containerId+'-hosts_grid',
                            containerId:this.containerId,
                            baseParams:{id:this.serviceId,method:'list_all',mode:'list_host'},xtype:'etfw_dhcp_hostgrid'
                        }
                        ,{

                            title: 'Host groups info',
                            url:<?php echo json_encode(url_for('etfw/json'))?>,
                            type:'group',
                            serviceId:this.serviceId,
                            id:this.containerId+'-groups_grid',
                            containerId:this.containerId,
                            baseParams:{id:this.serviceId,method:'list_all',mode:'list_group'},xtype:'etfw_dhcp_hostgrid'
                        }
                    ]
                }
            ]
        });


    }//eof

    // define public methods
    Ext.extend(ETFW_DHCP.Hosts.Main, Ext.Panel,{});

</script>