<script>
/*
 *
 * HOSTS
 *
 */
Ext.ns('ETFW.DHCP.Hosts');

// create pre-configured grid class
ETFW.DHCP.Hosts.Data_Grid = Ext.extend(Ext.grid.GridPanel, {

    initComponent:function() {

        var selectBoxModel= new Ext.grid.CheckboxSelectionModel({keepSelections:true});

        var group_field = '';
        var sort_info = '';
        var grid_columns = [];
        switch(this.type){
            case 'host':
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
                            Ext.getBody().mask('Preparing data...');
                            this.addHost.defer(150,this);
                        },
                        scope:this
                    },
                    '-',
                    {
                        text:'Edit host',
                        ref: '../editBtn',
                        tooltip:'Edit selected host',
                        disabled:true,
                        handler: function(){
                            Ext.getBody().mask('Preparing data...');
                            this.editHost.defer(150,this);
                        },
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
                            Ext.getBody().mask('Preparing data...');
                            this.addGroup.defer(150,this);
                        },
                        scope:this
                    },
                    '-',
                    {
                        text:'Edit host group',
                        ref: '../editBtn',
                        tooltip:'Edit selected host group',
                        disabled:true,
                        handler: function(){
                            Ext.getBody().mask('Preparing data...');
                            this.editGroup.defer(150,this);
                        },
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
                    fields:[]
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
        ETFW.DHCP.Hosts.Data_Grid.superclass.initComponent.apply(this, arguments);



        this.getSelectionModel().on('selectionchange', function(sm){
            this.editBtn.setDisabled(sm.getCount() < 1);
            this.removeBtn.setDisabled(sm.getCount() < 1);
        },this);




    } // eo function initComponent
    // }}}
    // {{{
    ,onRender:function() {

        // call parent
        ETFW.DHCP.Hosts.Data_Grid.superclass.onRender.apply(this, arguments);

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
                        handler: function(){
                            Ext.getBody().mask('Preparing data...');
                            this.editHost.defer(150,this);
                        },
                        scope:this
                    },{
                        text:'Delete host',
                        tooltip:'Delete this item',
                        iconCls:'remove',
                        handler:function(){
                            Ext.MessageBox.show({
                                title:'Delete host',
                                msg: 'You are about to delete this host. <br />Are you sure you want to delete?',
                                buttons: Ext.MessageBox.YESNOCANCEL,
                                fn: function(btn){

                                    if(btn=='yes'){
                                        var selected = this.getSelectionModel().getSelected();
                                        var uuid = selected.data['uuid'];
                                        if(uuid) this.onDeleteHost(uuid);
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
    ,onGroupRowContextMenu:function(grid, rowIndex, e) {
        grid.getSelectionModel().selectRow(rowIndex);

        if (!this.menu) {
            this.menu = new Ext.menu.Menu({
                // id: 'menus',
                items: [{
                        text:'Edit host group',
                        tooltip:'Edit host group information of the selected item',
                        iconCls:'editItem',
                        handler: function(){
                            Ext.getBody().mask('Preparing data...');
                            this.editGroup.defer(150,this);
                        },
                        scope:this
                    },{
                        text:'Delete host group',
                        tooltip:'Delete this item',
                        iconCls:'remove',
                        handler:function(){
                            Ext.MessageBox.show({
                                title:'Delete host group',
                                msg: 'You are about to delete this host group. <br />Are you sure you want to delete?',
                                buttons: Ext.MessageBox.YESNOCANCEL,
                                fn: function(btn){

                                    if(btn=='yes'){
                                        var selected = this.getSelectionModel().getSelected();
                                        var uuid = selected.data['uuid'];
                                        if(uuid) this.onDeleteGroup(uuid);
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
    ,onDeleteGroup:function(uuid){

        var send_data = {"decls":[{"uuid":uuid}]};

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
                ,requestexception:function(c,r,o){Ext.Ajax.fireEvent('requestexception',c,r,o);}
            }
        });// end conn

        conn.request({
            url: this.url,
            params:{id:this.service_id,method:'del_declarations',params:Ext.encode(send_data)},
            // everything ok...
            success: function(resp,opt){
                var msg = 'Host group removed successfully';
                Ext.ux.Logger.info(msg);
                View.notify({html:msg});
                this.reload();
                alert(this.service_id);
                Ext.getCmp(this.service_id+'-hosts_grid').reload();

            },scope:this
        });// END Ajax request


    }
    ,onDeleteHost:function(uuid){


        var send_data = {"decls":[{"uuid":uuid}]};

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
                ,requestexception:function(c,r,o){Ext.Ajax.fireEvent('requestexception',c,r,o);}
            }
        });// end conn

        conn.request({
            url: this.url,
            params:{id:this.service_id,method:'del_declarations',params:Ext.encode(send_data)},
            // everything ok...
            success: function(resp,opt){
                var msg = 'Host removed successfully';
                Ext.ux.Logger.info(msg);
                View.notify({html:msg});
                this.reload();
                Ext.getCmp(this.service_id+'-groups_grid').reload();

            },scope:this
        });// END Ajax request

    }
    ,addHost:function(){

        var host_title = 'Add host ';

        var host_form = new ETFW.DHCP.Host_Form({title:host_title,service_id:this.service_id});

        host_form.on({
                createdHost:function(){
                    alert('createdHost');
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
            title:'Host information'
            ,width:920
            ,layout:'fit'
            ,modal:true
            ,height:windowHeight
            ,closable:true
            ,border:false
            ,items:host_form
            ,buttons:[{text:'Close',handler:function(){win.close()}}]
            ,listeners:{
                show:{fn:function(){
                        host_form.reset();
                      },delay:10}
            }

        });

        win.show();

    }
    ,editHost:function(){


        var selected = this.getSelectionModel().getSelected();
        var host_title = 'Edit Host '+selected.data['host'];

        var host_form = new ETFW.DHCP.Host_Form({title:host_title,service_id:this.service_id});
        host_form.on({
                updatedHost:function(){
                                win.close();
                                this.reload();
                            },
                deleteHost:function(uuid){
                                win.close();
                                this.onDeleteHost(uuid);},
                scope:this
        });

        var client_title = 'Client Options for host '+ selected.data['host'];
        var client_options = new ETFW.DHCP.ClientOptions_Form({title:client_title,service_id:this.service_id});
        client_options.on({
                updatedClientOptions:function(){
                                win.close();
                                this.reload();
                            },
                scope:this
        });

        var record_option = new Object();
        record_option.data = selected.data['option'];
        record_option.data['uuid'] = selected.data['uuid'];

        client_options.on('afterLayout',function(){client_options.loadRecord(record_option);},this,{single:true});



        var tabs = new Ext.TabPanel({
            activeTab:0,
            border:false,
            defaults:{border:false},
            items:[host_form
                ,client_options
            ]
        });

        var viewerSize = Ext.getBody().getViewSize();
        var windowHeight = viewerSize.height * 0.95;
        windowHeight = Ext.util.Format.round(windowHeight,0);
        windowHeight = (windowHeight > 650) ? 650 : windowHeight;

        // create and show window
        var win = new Ext.Window({
            title:'Host information'
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
                        host_form.loadHostRecord(record);
                      },delay:10}
            }
        });

        win.show();


    }
    ,addGroup:function(){

        var host_title = 'Add host group ';

        var host_form = new ETFW.DHCP.Group_Form({title:host_title,service_id:this.service_id});

        host_form.on({
                createdGroup:function(){
                                alert('aki');
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
            title:'Host information'
            ,width:920
            ,layout:'fit'
            ,modal:true
            ,height:windowHeight
            ,closable:true
            ,border:false
            ,items:host_form
            ,buttons:[{text:'Close',handler:function(){win.close()}}]
            ,listeners:{
                show:{fn:function(){
                        host_form.reset();
                      },delay:10}
            }

        });

        win.show();

    }
    ,editGroup:function(){

        var selected = this.getSelectionModel().getSelected();

        var type = '';
        var host_title = 'Edit Host group ';

        switch(selected.data['assigned_type']){
            case 'subnet': type = 'subnet '+selected.data['parent'];
                break;
            case 'shared-network': type = 'shared network '+selected.data['parent'];
                break;
            default:break;
        }

        if(selected.data['assigned']) host_title += 'in '+type;


        var hostgroup_form = new ETFW.DHCP.Group_Form({title:host_title,service_id:this.service_id});
        hostgroup_form.on({
                updatedGroup:function(){
                                win.close();
                                this.reload();
                            },
                deleteGroup:function(uuid){
                                win.close();
                                this.onDeleteGroup(uuid);},
                scope:this
        });

        var client_title = 'Client Options for '+ selected.data['group']+' group';
        var client_options = new ETFW.DHCP.ClientOptions_Form({title:client_title,service_id:this.service_id});
        client_options.on({
                updatedClientOptions:function(){
                                win.close();
                                this.reload();
                            },
                scope:this
        });

        var record_option = new Object();
        record_option.data = selected.data['option'];
        record_option.data['uuid'] = selected.data['uuid'];

        client_options.on('afterLayout',function(){client_options.loadRecord(record_option);},this,{single:true});

        var tabs = new Ext.TabPanel({
            activeTab:0,
            border:false,
            defaults:{border:false},
            items:[hostgroup_form
                ,client_options
            ]
        });

        var viewerSize = Ext.getBody().getViewSize();
        var windowHeight = viewerSize.height * 0.95;
        windowHeight = Ext.util.Format.round(windowHeight,0);
        windowHeight = (windowHeight > 650) ? 650 : windowHeight;

        // create and show window
        var win = new Ext.Window({
            title:'Host group information'
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
                        hostgroup_form.loadGroupRecord(record);
                      },delay:10}
            }
        });

        win.show();


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
            params:{id:this.service_id,method:'del_declarations',params:Ext.encode([groups])},
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
                Ext.getCmp(this.service_id+'-hosts_grid').reload();







            },scope:this

        });// END Ajax request

    }





}); // eo extend

// register component
Ext.reg('etfw_dhcp_hostgrid', ETFW.DHCP.Hosts.Data_Grid);


ETFW.DHCP.Hosts.Main = function(service_id) {

    this.service_id = service_id;

    ETFW.DHCP.Hosts.Main.superclass.constructor.call(this, {
        title: 'Hosts and Hosts Groups',
        layout:'border',
        border:false,
        items:[{
                xtype: 'tabpanel',
                region: 'center',
                margins: '3 3 3 3',
                tabPosition: 'bottom',
                activeTab: 0,
                items: [{

                        title: 'Hosts info',
                        url:<?php echo json_encode(url_for('etfw/json'))?>,
                        type:'host',
                        border:false,
                        service_id:this.service_id,
                        id:this.service_id+'-hosts_grid',
//                            containerId:this.containerId,
                        baseParams:{id:this.service_id,method:'list_all',mode:'list_host'},xtype:'etfw_dhcp_hostgrid'
                    }
                    ,{

                        title: 'Host groups info',
                        url:<?php echo json_encode(url_for('etfw/json'))?>,
                        type:'group',
                        service_id:this.service_id,
                        border:false,
                        id:this.service_id+'-groups_grid',
//                            containerId:this.containerId,
                        baseParams:{id:this.service_id,method:'list_all',mode:'list_group'},xtype:'etfw_dhcp_hostgrid'
                    }
                ]
            }
        ]
        ,listeners:{
            beforerender:function(){
                Ext.getBody().mask('Loading ETFW dhcp hosts panel...');
            }
            ,render:{delay:100,fn:function(){
                Ext.getBody().unmask();
            }}
        }
    });


}//eof

// define public methods
Ext.extend(ETFW.DHCP.Hosts.Main, Ext.Panel,{
    // define public methods
    reload:function(){
        var tabPanel = this.get(0);

        var hostsPanel = tabPanel.get(0);
        var groupsPanel = tabPanel.get(1);

        if(hostsPanel.rendered)
            hostsPanel.reload();

        if(groupsPanel.rendered)
            groupsPanel.reload();

    }
});

</script>