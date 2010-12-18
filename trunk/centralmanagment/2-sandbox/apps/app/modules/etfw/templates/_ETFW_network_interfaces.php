<script>

    /*
     * function to show form in window
     * params: mode: 'now' or 'boot' panels
     *          interface name to load
     *
     **/
    function etfw_addVirtIntfWin(serviceId,mode,parent_gridID,parent_formID,intfname){
        var intf_form;
        var win_id;
        var win;


        if(intfname){

            intf_form = new ETFW_Network.Interfaces.CreateEditInterfaceForm({serviceId:serviceId,mode:mode,
                        parent_gridID:parent_gridID,parent_formID:parent_formID},1);
                    
            if(mode =='boot'){                
                win_width = 350;
                win_height = 370;
            }

            if(mode =='now'){                
                win_width = 250;
                win_height = 320;
            }

            intf_form.loadFullName(intfname);

         //   win = Ext.getCmp(win_id);

           // if(!win){

                win = new Ext.Window({
                    // applyTo:'hello-win',
                    layout:'fit',
                 //   id:win_id,
                    width:win_width,
                    height:win_height,
                    modal:true,
                    closeAction:'hide',
                    //plain: true,

                    items: [intf_form],

                    buttons: [{
                            text: 'Close',
                            handler: function(){
                                win.close();
                            }
                        }],
                    listeners:{show:function(){ // focus form elem with delay
                            (intf_form.getForm().items.get(2)).focus(false, 800);
                        }}
                });
     //       }

            win.show();
        }// end if intfname
    }


    /*
    * active now grid
    *
    */
    Ext.ns('ETFW_Network.Interfaces.ActiveNow');
    ETFW_Network.Interfaces.ActiveNow.Grid = Ext.extend(Ext.grid.GridPanel, {
        formCmp:null,
        initComponent:function() {

            // show check boxes
            var selectBoxModel= new Ext.grid.CheckboxSelectionModel({keepSelections:true});            

            // column model
            var cm = new Ext.grid.ColumnModel([
                selectBoxModel,
                {header: "Name", dataIndex: 'fullname', width:120,  sortable: true},
                {header: "Type", dataIndex: 'type', width:120, sortable: true},
                {header: "IP Address", dataIndex: 'address', width:120, sortable: true},
                {header: "Netmask", dataIndex: 'netmask', width:120, sortable: true},
                {header: "Status", id:'status', dataIndex: 'up', width:120, sortable: false, renderer:function(v){
                        if(v==1) return 'Up';
                        else return 'Down';
                    }}
            ]);


            var dataStore = new Ext.data.JsonStore({
                url: this.url,
                baseParams:{id:this.serviceId,method:'active_interfaces'},
                id: 'fullname',
                remoteSort: false,
                totalProperty: 'total',
                root: 'data',
                fields: [{name:'fullname'},{name:'occur'},{name:'virtual'},{name:'up'},{name:'type'},
                    {name:'address'},{name:'netmask'},{name:'broadcast'},{name:'macaddress'},{name:'mtu'}] // initialized from json metadata
            });
            dataStore.setDefaultSort('fullname', 'ASC');

            var config = {
                store:dataStore
                ,cm:cm
                ,sm:selectBoxModel
                ,viewConfig:{forceFit:true}
                ,loadMask:true
            }; // eo config object

            // apply config
            Ext.apply(this, Ext.apply(this.initialConfig, config));

            this.formCmp = Ext.getCmp(this.containerId+'-nowform');
            
            this.bbar = new Ext.ux.grid.TotalCountBar({
                store:this.store
                ,displayInfo:true
            });

            this.tbar = [{
                    text:'Add',
                    tooltip:'Add a New Interface',
                    iconCls:'add',
                    handler: function(){
                        this.formCmp.create('now');
                    },scope:this
                },'-',{
                    text:'Edit',
                    ref: '../editBtn',
                    tooltip:'Edit the selected item',
                    disabled:true,
                    handler: function(){
                                var record = this.getSelectionModel().getSelected();
                                this.formCmp.loadNowRecord(record);
                    },scope:this
                },'-',{
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
            ETFW_Network.Interfaces.ActiveNow.Grid.superclass.initComponent.apply(this, arguments);


            this.on('rowdblclick', function(gridPanel, rowIndex, e) {
                var selected = this.store.data.items[rowIndex];
                this.formCmp.loadNowRecord(selected);
            });


            this.getSelectionModel().on('selectionchange', function(sm){
                this.editBtn.setDisabled(sm.getCount() < 1);
                this.removeBtn.setDisabled(sm.getCount() < 1);
            },this);


            /************************************************************
             * handle contextmenu event
             ************************************************************/
            this.addListener("rowcontextmenu", onContextMenu, this);
            function onContextMenu(grid, rowIndex, e) {
              
                this.menu = new Ext.menu.Menu({
                    // id: 'menus',
                    items: [{
                            text:'Edit',
                            tooltip:'Edit the selected item',
                            iconCls:'editItem',
                            handler: function(){                                
                                var record = this.getSelectionModel().getSelected();
                                if(!record) record = this.store.getAt(rowIndex);                              
                                this.formCmp.loadNowRecord(record);
                            },scope:this
                        },{
                            text:'Delete',
                            tooltip:'Delete the selected item(s)',
                            iconCls:'remove',
                            handler: function(){
                                new Grid.util.DeleteItem({panel: grid.id});
                            }
                        }]
                });
              
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
                            msg: 'De-activating interface...',
                            width:300,
                            wait:true,
                            modal: false
                        });
                    },// on request complete hide message
                    requestcomplete:function(){Ext.MessageBox.hide();}
                }
            });// end conn

            var ifaces = [];

            for(var i=0,len = items.length;i<len;i++){
                ifaces[i] = {"name":items[i].data.fullname};
            }

            var send_data = {'interfaces':ifaces};

            conn.request({
                url: this.url,
                params:{id:this.serviceId,method:'deactivate_interfaces',params:Ext.encode(send_data)},
                failure: function(resp,opt){
                    var response = Ext.util.JSON.decode(resp.responseText);
                    Ext.MessageBox.alert('Error Message', response['info']);
                    Ext.ux.Logger.error(response['error']);

                },
                // everything ok...
                success: function(resp,opt){

                    var msg = 'Deactivated interface(s)';
                    Ext.ux.Logger.info(msg);
                    View.notify({html:msg});
                    this.reload();
                    this.formCmp.create('now');
                },scope:this
            });// END Ajax request

            },scope:this

    });
    Ext.reg('etfw_network_interfaces_activenowgrid', ETFW_Network.Interfaces.ActiveNow.Grid);


    /*
    * active boot grid
    *
    */
    Ext.ns('ETFW_Network.Interfaces.ActiveBoot');
    ETFW_Network.Interfaces.ActiveBoot.Grid = Ext.extend(Ext.grid.GridPanel, {
        formCmp:null,
        initComponent:function() {

            // show check boxes
            var selectBoxModel= new Ext.grid.CheckboxSelectionModel({keepSelections:true});            

            function renderAddressTypeConf(v,p,r){

                if(r.data['dhcp']==1) return 'From DHCP';
                if(r.data['bootp']==1) return 'From BOOTP';
                if(v) return v;
            }

            // column model
            var cm = new Ext.grid.ColumnModel([
                selectBoxModel,
                // {id:'name',header: "Name", width: 120, dataIndex: 'name', sortable:true},
                {header: "Name", dataIndex: 'fullname', width:120,  sortable: true},
                {header: "Type", dataIndex: 'type', width:120, sortable: true},
                {header: "IP Address", dataIndex: 'address', width:120, sortable: true,renderer:renderAddressTypeConf},
                {header: "Netmask", dataIndex: 'netmask', width:120, sortable: true,renderer:function(v){
                        if(v) return v;
                        else return 'Automatic';
                    }},
                {header: "Activate at boot?", id:'status', dataIndex: 'up', width:120, sortable: false, renderer:function(v){
                        if(v==1) return 'Yes';
                        else return 'No';
                    }}
            ]);

            var dataStore = new Ext.data.JsonStore({
                url: this.url,
                baseParams:{id:this.serviceId,method:'boot_interfaces'},
                id: 'fullname',
                remoteSort: false,
                totalProperty: 'total',
                root: 'data',
                fields: [{name:'fullname'},{name:'dhcp'},{name:'bootp'},{name:'occur'},{name:'virtual'},{name:'up'},{name:'type'},
                    {name:'address'},{name:'netmask'},{name:'broadcast'},{name:'mtu'}] // initialized from json metadata
            });
            dataStore.setDefaultSort('fullname', 'ASC');

            var config = {
                store:dataStore
                ,cm:cm
                ,sm:selectBoxModel
                ,viewConfig:{forceFit:true}
                ,loadMask:true
            }; // eo config object

            // apply config
            Ext.apply(this, Ext.apply(this.initialConfig, config));

            this.formCmp = Ext.getCmp(this.containerId+'-bootform');

            this.bbar = new Ext.ux.grid.TotalCountBar({
                store:this.store
                ,displayInfo:true
            });
            

            this.tbar = [{
                    text:'Add',
                    tooltip:'Add a New Interface',
                    iconCls:'add',
                    handler: function(){
                        this.formCmp.create('boot');
                    },scope:this
                },'-',{
                    text:'Edit',
                    ref: '../editBtn',
                    tooltip:'Edit the selected item',
                    disabled:true,
                    handler: function(){
                                var record = this.getSelectionModel().getSelected();
                                this.formCmp.loadBootRecord(record);
                    },scope:this
                },'-',{
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
            ETFW_Network.Interfaces.ActiveBoot.Grid.superclass.initComponent.apply(this, arguments);

            this.on('rowdblclick', function(gridPanel, rowIndex, e) {
                var selected = this.store.data.items[rowIndex];
                this.formCmp.loadBootRecord(selected);
            });

            this.getSelectionModel().on('selectionchange', function(sm){
                this.editBtn.setDisabled(sm.getCount() < 1);
                this.removeBtn.setDisabled(sm.getCount() < 1);
            },this);


            /************************************************************
             * handle contextmenu event
             ************************************************************/
            this.addListener("rowcontextmenu", onContextMenu, this);
            function onContextMenu(grid, rowIndex, e) {
                
                this.menu = new Ext.menu.Menu({
                    // id: 'menus',
                    items: [{
                            text:'Edit',
                            tooltip:'Edit the selected item',
                            iconCls:'editItem',
                            handler: function(){
                                var record = this.getSelectionModel().getSelected();
                                if(!record) record = this.store.getAt(rowIndex);
                                this.formCmp.loadBootRecord(record);
                            },scope:this
                        },{
                            text:'Delete',
                            tooltip:'Delete the selected item(s)',
                            iconCls:'remove',
                            handler: function(){
                                new Grid.util.DeleteItem({panel: grid.id});
                            }
                        }]
                });
                
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
                            msg: 'Removing interface...',
                            width:300,
                            wait:true,
                            modal: false
                        });
                    },// on request complete hide message
                    requestcomplete:function(){Ext.MessageBox.hide();}
                }
            });// end conn

            var ifaces = [];

            for(var i=0,len = items.length;i<len;i++){
                ifaces[i] = {"name":items[i].data.fullname};
            }

            var send_data = {'interfaces':ifaces};
                            
            conn.request({
                url: this.url,
                params:{id:this.serviceId,method:'del_boot_interfaces',params:Ext.encode(send_data)},
                failure: function(resp,opt){
                    var response = Ext.util.JSON.decode(resp.responseText);
                    Ext.MessageBox.alert('Error Message', response['info']);
                    Ext.ux.Logger.error(response['error']);

                },
                success: function(resp,opt){

                    var msg = 'Removed boot interface(s) ';
                    Ext.ux.Logger.info(msg);
                    View.notify({html:msg});
                    this.reload();
                    this.formCmp.create('boot');
                },scope:this

            });// END Ajax request

        }

    });

    Ext.reg('etfw_network_interfaces_activebootgrid', ETFW_Network.Interfaces.ActiveBoot.Grid);




    /*
    * right side form
    * params: config array cotaining form ID and mode ('now' or 'boot')
    *          virtIntfWin // show on window 0/1
    *
    */

    ETFW_Network.Interfaces.CreateEditInterfaceForm = function(config,virtIntfWin) {

        Ext.QuickTips.init();
        Ext.form.Field.prototype.msgTarget = 'side';

        var allFields;

        Ext.apply(this,config);

        // default form fields
        this.fullname = new Ext.form.TextField({
            fieldLabel: 'Name',
            name: 'fullname',
            allowBlank: false,
            width:100
            //,anchor: '90%'

        });


        this.address = new Ext.form.TextField({
            fieldLabel: 'IP Address',
            // allowBlank: false,
            name: 'address',
            maxLength: 15,
            width:100

        });

        this.netmask = new Ext.form.TextField({
            fieldLabel: 'Netmask',
            name: 'netmask',
            maxLength: 15,
            //  allowBlank: false,
            width: 100

        });


        this.broadcast = new Ext.form.TextField({
            fieldLabel: 'Broadcast',
            name: 'broadcast',
            maxLength: 15,
            width:100
        });


        this.virtual = new Ext.form.DisplayField({
            fieldLabel: 'Virtual interfaces',
            //  hideMode:'display',

            labelStyle: 'padding: 0 0 0 0',
            value: '0 <a onclick="javascript:etfw_addVirtIntfWin()" href="#">Add virtual</a>',
            listeners:{
                hide: function(){this.getEl().up('.x-form-item').setDisplayed(false);}
                ,show: function(){this.getEl().up('.x-form-item').setDisplayed(true);}
            }
        });


        if(config.mode == 'boot')
        {
            allFields = this.buildBootForm(virtIntfWin);
            this.savebtn = this.buildUIBootForm();
        }

        if(config.mode == 'now')
        {
            allFields = this.buildNowForm(virtIntfWin);
            this.savebtn = this.buildUINowForm();
        }


        // define window and pop-up - render formPanel
        ETFW_Network.Interfaces.CreateEditInterfaceForm.superclass.constructor.call(this, {
            // baseCls: 'x-plain',
            labelWidth: 90,
            url:<?php echo json_encode(url_for('etfw/json'))?>,
            defaultType: 'textfield',       
            buttonAlign:'center',
            frame:true,
            items: [allFields]

            ,buttons: [this.savebtn]
        });

    };

    Ext.extend(ETFW_Network.Interfaces.CreateEditInterfaceForm, Ext.form.FormPanel, {
        /*
        * build save button fow 'now' panel
        */
        buildUINowForm:function(){

            return new Ext.Button({text: 'Save',
                handler: function() {
                    var alldata = this.form.getValues();

                    if (this.form.isValid()) {

                        if(this.virtualpartname)
                        {
                            this.ownerCt.hide();
                            var virtual = this.virtualpartname.getValue();
                            var real = this.realname.getValue();
                            this.fullname.setValue(real+virtual);
                        }

                        var alldata = this.form.getValues();

                        alldata['name'] = this.fullname.getValue();


                        var conn = new Ext.data.Connection({
                            listeners:{
                                // wait message.....
                                beforerequest:function(){
                                    Ext.MessageBox.show({
                                        title: 'Please wait',
                                        msg: 'Updating interface...',
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
                            params:{id:this.serviceId,method:'activate_interface',params:Ext.encode(alldata)},
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
                                
                                var gridCmp = Ext.getCmp(this.parent_gridID);
                                var msg = 'Updated interface '+alldata['name'];
                                Ext.ux.Logger.info(msg);
                                View.notify({html:msg});
                                gridCmp.getStore().reload(
                                {callback:function(){
                                        var selected = gridCmp.getSelectionModel().getSelected();
                                        if(!selected) selected = gridCmp.getStore().getById(alldata['name']);

                                        if(selected) (Ext.getCmp(this.parent_formID)).loadNowRecord(selected);

                                    },scope:this}
                            );
                            },scope:this
                        });// END Ajax request


                    } else{
                        Ext.MessageBox.alert('error', 'Please fix the errors noted.');
                    }
                },scope:this
            }

        );

        },
        /*
        * build save button fow 'boot' panel
        */
        buildUIBootForm:function(){

            return new Ext.Button({text: 'Save',
                handler: function() {
                    var alldata = this.form.getValues();

                    if (this.form.isValid()) {

                        if(this.virtualpartname)
                        {
                            this.ownerCt.hide();
                            var virtual = this.virtualpartname.getValue();
                            var real = this.realname.getValue();
                            this.fullname.setValue(real+virtual);
                        }

                        var alldata = this.form.getValues();
                        alldata['fullname'] = this.fullname.getValue();

                        var addres_source = alldata['address_source'];
                        var dhcp = 0;
                        var bootp = 0;

                        switch(addres_source){
                            case 'dhcp': dhcp = 1;
                                break;
                            case 'bootp': bootp = 1;
                                break;
                            default: break;
                        }

                        var mtu_source = alldata['mtu_source'];
                        var mtu = '';
                        switch(mtu_source){
                            case 'mtu': mtu = alldata['mtu'];
                                break;
                            default: break;
                        }



                        var send_data = {"name":alldata['fullname'],
                            "address":alldata['address'],
                            "netmask":alldata['netmask'],
                            "broadcast":alldata['broadcast'],
                            "mtu":mtu,
                            "dhcp":dhcp,
                            "bootp":bootp,
                            "up":alldata['up']
                        }



                        var conn = new Ext.data.Connection({
                            listeners:{
                                // wait message.....
                                beforerequest:function(){
                                    Ext.MessageBox.show({
                                        title: 'Please wait',
                                        msg: 'Updating interface...',
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
                            params:{id:this.serviceId,method:'save_boot_interface',
                                params:Ext.encode(send_data)
                            },
                            failure: function(resp,opt){
                                var response = Ext.util.JSON.decode(resp.responseText);
                                Ext.MessageBox.alert('Error Message', response['info']);
                                Ext.ux.Logger.error(response['error']);

                            },
                             success: function(resp,opt){
                                
                                var gridCmp = Ext.getCmp(this.parent_gridID);
                                var msg = 'Updated interface '+alldata['fullname'];
                                Ext.ux.Logger.info(msg);
                                View.notify({html:msg});
                                gridCmp.getStore().reload(
                                {callback:function(){
                                        var selected = gridCmp.getSelectionModel().getSelected();
                                        if(!selected) selected = gridCmp.getStore().getById(alldata['name']);
                                        
                                        if(selected) (Ext.getCmp(this.parent_formID)).loadBootRecord(selected);                                        

                                },scope:this}
                            );
                            },scope:this

                        });// END Ajax request


                    } else{
                        Ext.MessageBox.alert('error', 'Please fix the errors noted.');
                    }
                },scope:this
            }

        );

        },
        /*
        * build form for 'now' panel
        */
        buildNowForm:function(virtIntfWin){


            this.mtu = new Ext.form.TextField({
                fieldLabel: 'MTU',
                name: 'mtu',
                maxLength: 50,
                width:60
            });

            this.macaddr = new Ext.form.TextField({
                fieldLabel: 'MAC Address',
                name: 'macaddress',
                maxLength: 50,
                anchor: '90%',
                listeners:{
                    hide: function(){this.getEl().up('.x-form-item').setDisplayed(false);}
                    ,show: function(){this.getEl().up('.x-form-item').setDisplayed(true);}
                }
            });



            this.status_up = new Ext.form.Radio({
                boxLabel: 'Up', name: 'up',fieldLabel:'',hideLabel:true, inputValue: 1
            });

            this.status_down = new Ext.form.Radio({
                boxLabel: 'Down', name: 'up',fieldLabel:'',hideLabel:true, inputValue: 0
            });


            var nameLayout = [this.fullname];
            var title = 'Add active interface';

            if(virtIntfWin){
                title = 'Create active virtual interface';

                this.fullname = new Ext.form.Hidden({name: 'fullname'});

                this.realname = new Ext.form.DisplayField({
                    fieldLabel:'Name',
                    autoWidth:true,
                    name: 'realname'
                });

                this.virtualpartname = new Ext.form.TextField({
                    hideLabel:true,
                    width:44,
                    name: 'virtualpartname'
                });

                Ext.apply(this.macaddr,{hideLabel:true,hidden:true});
                Ext.apply(this.virtual,{hideLabel:true,hidden:true});


                nameLayout = [this.fullname,{
                        layout:'table',
                        layoutConfig: {
                            columns:2
                        },
                        items:[{
                                layout:'form',
                                items:[this.realname]
                            },
                            {layout:'form',
                                items:[this.virtualpartname]
                            }]
                    }];

            }// end virtWin

            this.fieldset = new Ext.form.FieldSet({
                title: title,
                autoHeight:true,
                items:[nameLayout,
                    this.address,
                    this.netmask,
                    this.broadcast,
                    this.macaddr,
                    this.mtu
                    ,
                    {
                        xtype: 'radiogroup'
                        ,fieldLabel: 'Status'
                        ,width:100
                        ,items: [this.status_up,this.status_down]
                    },
                    this.virtual
                ]
            });

            return this.fieldset;


        },
        buildBootForm:function(virtIntfWin){

            this.status_up = new Ext.form.Radio({
                boxLabel: 'Yes', name: 'up',width:45, fieldLabel:'Activate on boot',inputValue: 1
            });

            this.status_down = new Ext.form.Radio({
                boxLabel: 'No', name: 'up',fieldLabel:'',hideLabel:true, inputValue: 0
            });

            this.mtu = new Ext.form.TextField({
                    fieldLabel:'',
                    hideLabel:true,
                    name:'mtu',
                    width:40
                });


            this.dhcp = new Ext.form.Radio({
                boxLabel: 'From DHCP', width:90, name: 'address_source',fieldLabel:'',hideLabel:true, inputValue: 'dhcp'
            });

            this.bootp = new Ext.form.Radio({
                boxLabel: 'From BOOTP', width:90, name: 'address_source',fieldLabel:'',hideLabel:true, inputValue: 'bootp'
            });

            this.static = new Ext.form.Radio({
                boxLabel: 'Static config', width:90, name: 'address_source',fieldLabel:'',hideLabel:true, inputValue: 'static'
            });

            var nameLayout = [this.fullname];
            var title = 'Add bootup interface';

            if(virtIntfWin){
                title = 'Create virtual bootup interface';

                this.fullname = new Ext.form.Hidden({name: 'fullname'});

                this.realname = new Ext.form.DisplayField({
                    fieldLabel:'Name',
                    autoWidth:true,
                    name: 'realname'
                });

                this.virtualpartname = new Ext.form.TextField({
                    hideLabel:true,
                    width:44,
                    name: 'virtualpartname'
                });

                this.dhcp.hide();
                this.bootp.hide();

                Ext.apply(this.virtual,{hideLabel:true,hidden:true});
                Ext.apply(this.static,{checked:true});

                nameLayout = [this.fullname,{
                        layout:'table',
                        layoutConfig: {
                            columns:2
                        },
                        items:[{
                                layout:'form',
                                items:[this.realname]
                            },
                            {layout:'form',
                                items:[this.virtualpartname]
                            }]
                    }];

            }// end if virtwin

            this.fieldset = new Ext.form.FieldSet({
                title: title,
                autoHeight:true,
                items:[nameLayout,
                    //this.status,
                    {xtype:'fieldset',
                        title: 'Address source',
                        items:[this.dhcp,
                            this.bootp,

                            {
                                layout:'table',
                                //   width:300,
                                layoutConfig: {columns:2},
                                items:[
                                    {
                                        labelAlign:'left',
                                        layout:'form',
                                        items:this.static
                                    },
                                    {
                                        // labelAlign:'top',
                                        labelWidth:60,
                                        //   width:150,
                                        layout:'form',
                                        items:[
                                            this.address,
                                            this.netmask,
                                            this.broadcast
                                        ]
                                    }]


                            }]


                    }// end fieldset
                    ,// mtu
                    {
                        layout:'table',
                        layoutConfig: {columns:3},
                        items:[

                            {
                                labelAlign:'left',
                                layout:'form',
                                items:[{xtype:'radio',style:'margin-top:3px', name:'mtu_source',width:60,fieldLabel:'MTU',inputValue: '',boxLabel:'Default'}]
                            },
                            {
                                labelAlign:'left',
                                layout:'form',
                                items:[{xtype:'radio', name:'mtu_source',width:20,fieldLabel:'',hideLabel:true,boxLabel:'',inputValue: 'mtu'}]
                            },
                            {
                                labelAlign:'left',
                                // labelWidth:60,
                                layout:'form',
                                items:this.mtu
                            }]
                    } // end mtu
                    ,
                    {


                        layout:'table',
                        layoutConfig: {columns:2},
                        items:[{layout:'form',items:this.status_up},
                            {layout:'form',items:this.status_down}
                        ]


                    },this.virtual

                ]
            });
            return this.fieldset;

        },
       /*
        * loads fullname only ( for form win)
        */
        loadFullName:function(name){
            this.realname.setValue(name+':');
        },
        loadNowRecord : function(rec) {

            this.fieldset.setTitle('Edit active interface');
            this.fullname.setDisabled(true);

            if(!rec.data.macaddress) this.macaddr.hide();
            else this.macaddr.show();

            if(rec.data.virtual){
                this.fieldset.setTitle('Edit active virtual interface');
                this.virtual.hide();
            }else{
                this.virtual.show();
                var value = rec.data.occur-1+' <a onclick="javascript:etfw_addVirtIntfWin('+this.serviceId+',\'now\',\''+this.parent_gridID+'\',\''+this.id+'\',\''+rec.id+'\')" href="#">Add virtual</a>';
                this.virtual.setValue(value);
            }

            this.savebtn.setText('Update'); // save button
            this.getForm().loadRecord(rec);
        },

        loadBootRecord : function(rec) {

            this.fullname.setDisabled(true);
            this.fieldset.setTitle('Edit bootup interface');

            var address = rec.get('address');
            if(rec.data['dhcp']==1) rec.data['address_source'] = 'dhcp';
            else if(rec.data['bootp']==1) rec.data['address_source'] = 'bootp';
            else if(address) rec.data['address_source'] = 'static';


            var mtu = rec.get('mtu');

            if(mtu) rec.data['mtu_source'] = 'mtu';
            else rec.data['mtu_source'] = '';


            if(rec.data.virtual)
            {
                this.dhcp.hide();
                this.bootp.hide();
                this.fieldset.setTitle('Edit virtual bootup interface');
                this.virtual.hide();
            }else{
                this.dhcp.show();
                this.bootp.show();
                this.virtual.show();
                var value = rec.data.occur-1+' <a onclick="javascript:etfw_addVirtIntfWin('+this.serviceId+',\'boot\',\''+this.parent_gridID+'\',\''+this.id+'\',\''+rec.id+'\')" href="#">Add virtual</a>';
                this.virtual.setValue(value);
            }

            this.savebtn.setText('Update'); // save button
            this.getForm().loadRecord(rec);
        },

        create : function(mode) {
            this.buttons[0].setText('Save'); // save button
            this.fullname.setDisabled(false);

            if(mode=='boot')this.fieldset.setTitle('Add boot interface');
            if(mode=='now')this.fieldset.setTitle('Add active interface');


            this.clean();
        },

        clean : function() {

            this.fullname.setValue(null);
            this.address.setValue(null);
            this.netmask.setValue(null);
            this.broadcast.setValue(null);
            this.status_up.setValue(true);

            if(this.macaddr) this.macaddr.setValue(null);
            this.mtu.setValue(null);
            this.virtual.hide();

        }

    });



    ETFW_Network.Interfaces.Main = function(serviceId,containerId) {

        
        var interfacesNowCreateEdit = new
                ETFW_Network.Interfaces.CreateEditInterfaceForm({id:containerId+'-nowform',
                        serviceId:serviceId,mode:'now',
                        parent_gridID:containerId+'-activenowgrid',
                        parent_formID:containerId+'-nowform'},0);

        var activeNowPanel = new Ext.Panel({
            title:'Active now',
            layout:'border',
            frame:true,            
            items:[
                {
                    id:containerId+'-activenowgrid'
                    ,containerId:containerId
                    ,region:'center'
                    //   margins: '5 0 0 0',
                    ,layout:'fit'
                    ,serviceId:serviceId
                    ,url:<?php echo json_encode(url_for('etfw/json'))?>
                    ,xtype:'etfw_network_interfaces_activenowgrid'

                },
                // dd,
                {region:'east',
                    margins: '0 0 0 5'
                    ,autoScroll:true                  
                    ,url:<?php echo json_encode(url_for('etfw/json'))?>
                    ,title:'Interface details',width:280,collapsible: true
                    ,items:[interfacesNowCreateEdit]}
            ]


        });

        activeNowPanel.on('beforerender',function(){
            Ext.getBody().mask('Loading ETFW network interfaces panel...');}
            ,this
        );

        activeNowPanel.on('render',function(){
            Ext.getBody().unmask();}
            ,this
        ,{delay:10}
        );


     
        var interfacesBootCreateEdit = new 
                ETFW_Network.Interfaces.CreateEditInterfaceForm({id:containerId+'-bootform',
                        serviceId:serviceId,mode:'boot',
                        parent_gridID:containerId+'-activebootgrid',
                        parent_formID:containerId+'-bootform'},0);
                    
        var activeAtBootPanel = new Ext.Panel({
            title:'Active at boot',
            layout:'border',
            frame:true,           
            items:[
                {
                    id:containerId+'-activebootgrid'
                    ,containerId:containerId
                    ,region:'center'
                    ,url:<?php echo json_encode(url_for('etfw/json'))?>
                    ,layout:'fit'
                    ,serviceId:serviceId
                    ,xtype:'etfw_network_interfaces_activebootgrid'
                }
                // dd,
                ,{region:'east'
                    ,margins: '0 0 0 5'
                    ,autoScroll:true
                    ,url:<?php echo json_encode(url_for('etfw/json'))?>
                    ,title:'Interface details',width:330,collapsible: true
                    ,items:[interfacesBootCreateEdit]}
            ]


        });


        activeAtBootPanel.on('beforerender',function(){
            Ext.getBody().mask('Loading ETFW network interfaces panel...');}
            ,this
        );

        activeAtBootPanel.on('render',function(){
            Ext.getBody().unmask();}
            ,this
        ,{delay:10}
        );




        /************************************************************
         * Constructor for the Ext.grid.EditorGridPanel
         ************************************************************/
        ETFW_Network.Interfaces.Main.superclass.constructor.call(this, {
            // id: 'account-grid-form',

            border:false,
           // el:'grid-example',
          //  frame: true,
            //  xtype:'tabpanel',
            // height:300,
            //  labelAlign: 'left',
            // bodyStyle:'padding:5px',
            layout:'fit',
           // id:'Network_Interfaces',
            title: 'Network Interfaces',
            //autoScroll:true,
            items: [{
                    xtype:'tabpanel',
                    //  layoutOnTabChange:true,
                    //  deferredRender:false,
                    activeTab:0,
                    //           bodyStyle:'padding:5px',
                    items:[activeNowPanel,activeAtBootPanel]
                }]


        });






    }
    // define public methods
    Ext.extend(ETFW_Network.Interfaces.Main, Ext.Panel, {
    });


</script>