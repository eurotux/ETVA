<script>
Ext.ns('Server.View');

Server.View.Info = Ext.extend(Ext.form.FormPanel, {
    border:false,
    labelWidth:140,
    defaults:{border:false},
    initComponent:function(){

        var cdromstore = new Ext.data.JsonStore({
                id:'id'
                ,root:'data'
                ,totalProperty:'total'
                ,fields:[
                    {name:'name', type:'string'},'full_path'
                ]
                ,proxy: new Ext.data.HttpProxy({
                        url:<?php echo json_encode(url_for('view/iso'))?>
                    })
                ,baseParams:{doAction:'jsonList',params:Ext.encode({emptyValue:true})}

            });

        //build cdrom com for menu bar
        var cdromcombo = new Ext.form.ComboBox({
            editable:false
            ,valueField:'full_path'
            ,hiddenName:'cdromcombo'
            ,displayField:'name'
            ,pageSize:10
            ,triggerAction:'all'
            ,forceSelection:true
            ,selectOnFocus:true
            ,valueNotFoundText: __('Invalid')
            ,mode:'remote'
            //,enableKeyEvents:true
            ,resizable:true
            ,minListWidth:250
            ,getListParent: function() {
                return this.el.up('.x-menu');
            }
            ,allowBlank:false
            ,store:cdromstore
            ,listeners:{
                // set tooltip and validate
                render:function() {
                    this.el.set({qtip: <?php echo json_encode(__('Choose iso to load')) ?>});
                    this.validate();
                }
                ,select:function(cb,rec,index){
                    cb.ownerCt.fireEvent('onSelect');
                    //Server.Grid.updateRecords({grid:serverGrid,data:[{field:'Boot',value:'cdrom'},{field:'Location',value:rec.data['full_path']}]});
                }
            }
        });

        /*
         * used by start button
         *
         */
        var menu_boot = new Ext.menu.Menu({
                        style: {
                            overflow: 'visible'     // For the Combo popup
                        },
                        items: [
                            {
                                text: <?php echo json_encode(__('VM Filesystem')) ?>,
                                name:'filesystem',xtype:'menucheckitem',ref:'boot_filesystem'
                                ,group: 'boot_from'
                                ,scope:this
                                ,listeners:{
                                    scope:this,
                                    checkchange: function(chkitem,chk){
                                        if(chk)
                                            this.updateRecords({boot:'filesystem',data:[]});
                                    }
                                }
                            },
                            {
                                text: 'Location URL',
                                name: 'location',xtype:'menucheckitem',ref:'boot_location'
                                ,scope:this
                                ,group: 'boot_from'
                                ,listeners:{
                                    scope:this,
                                    checkchange: function(chkitem,chk){
                                        if(chk)
                                            this.updateRecords({boot:'location',data:[]});
                                    }
                                }
                            }
                           ,{
                                text:'PXE'
                                ,xtype:'menucheckitem'
                                ,group: 'boot_from'
                                ,ref:'boot_pxe'
                                ,name:'pxe'
                                ,scope:this
                                ,listeners:{
                                    scope:this,
                                    checkchange: function(chkitem,chk){
                                        if(chk)
                                            this.updateRecords({boot:'pxe',data:[]});
                                    }
                                }
                           }
                          ,{
                                text:'CD-ROM',
                                xtype:'menucheckitem',
                                group: 'boot_from',
                                ref:'boot_cdrom',
                                scope:this,
                                menu:{items: [cdromcombo],listeners:{'onSelect':function(){
                                            if(!this.ownerCt.checked) this.ownerCt.setChecked(true,false);
                                            else this.ownerCt.fireEvent('checkchange',this.ownerCt,true);
                                        }}}
                                ,listeners:{
                                    scope:this,
                                    checkchange: function(chkitem,chk){
                                        if(chk){
                                            var full_path = cdromcombo.getValue();
                                            if(full_path)
                                                this.updateRecords({boot:'cdrom',data:[{field:'location',value:full_path}]});
                                            else return false;
                                        }
                                    }
                                }
                           }
                        ]
        });

        this.tbar = [
                    {
                        text: <?php echo json_encode(__('Add server wizard')) ?>,
                        ref: '../addwizardBtn',
                        disabled:true,
                        iconCls: 'icon-add',
                        handler: View.clickHandler
                    },
                    {
                        text: <?php echo json_encode(__('Open console')) ?>,
                        ref: '../consoleBtn',
                        disabled:false,scope:this,
                        handler:function(){

                            var server_id = this.form.findField('id').getValue();
                            var server_state = this.form.findField('vm_state').getValue();

                            if(server_state!='running')
                            {
                                Ext.Msg.show({
                                    title: this.consoleBtn.text,
                                    buttons: Ext.MessageBox.OK,
                                    icon: Ext.MessageBox.INFO,
                                    msg: <?php echo json_encode(__('Cannot open console. Maybe server not running!')) ?>});
                                return;
                            }

                            if(!navigator.javaEnabled()){
                                Ext.Msg.show({
                                    title: this.consoleBtn.text,
                                    buttons: Ext.MessageBox.OK,
                                    icon: Ext.MessageBox.INFO,
                                    msg: __('Java required!')});
                                return;
                            }

                            Ext.getBody().mask(<?php echo json_encode(__('Retrieving data...')) ?>);

                            var url = '<?php echo url_for('/view/vncviewer/id/') ?>'+server_id+'/';
                            var viewerSize = Ext.getBody().getViewSize();
                            var windowHeight = viewerSize.height * 0.95;
                            windowHeight = Ext.util.Format.round(windowHeight,0);

                            var config = {
                                    title: <?php echo json_encode(__('Please wait...')) ?>,
                                    //html:'Loadin applet',
                                    maximizable   : true,
                                    collapsible   : true,
                                    constrain     : true,
                                    defaultSrc:url,
                                    shadow        : Ext.isIE,
                                    autoScroll    : true,
                                    useShim:true,
                                    //loadMask:true,
                                    hidden:true,
                                    hideMode      : 'nosize',
                                    listeners : {
                                        domready : function(frameEl){  //raised for "same-origin" frames only
                                                        var MIF = frameEl.ownerCt;
                                        },
                                        documentloaded : function(frameEl){

                                                        var MIF = frameEl.ownerCt;
                                                        var doc = frameEl.getFrameDocument();
                                                        View.notify({html:doc.title+' reports: DATA LOADED'});
                                                        (function(){Ext.getBody().unmask();}).defer(1000);


                                        },
                                        beforedestroy : function(){}
                                    },
                                    sourceModule : 'mifsimple'
                            };

                            var win = new Ext.ux.ManagedIFrame.Window(config);

                            win.show();
                            win.hide();
                        }
                    },
                    '-'
                    ,{
                        xtype:'splitbutton',
                        ref: '../startBtn',
                        disabled: true,
                        scope:this,
                        text: <?php echo json_encode(__('Start server')) ?>,
                        menu: [
                                {
                                    text: <?php echo json_encode(__('Boot From')) ?>
                                    ,menu: menu_boot
                                }
                        ],
                        listeners:{
                            scope:this,
                            menushow:function(bt,mn){

                                var server_vm_type = this.form.findField('vm_type').getValue();
                                var server_boot = this.form.findField('boot').getValue();
                                var server_location = this.form.findField('location').getValue();

                                var boot_location = menu_boot.boot_location;
                                var boot_cdrom = menu_boot.boot_cdrom;
                                var boot_pxe = menu_boot.boot_pxe;
                                var boot_filesystem = menu_boot.boot_filesystem;

                                boot_pxe.setVisible(server_vm_type!='pv');

                                boot_cdrom.setVisible(server_vm_type!='pv');
                                boot_location.setVisible(server_vm_type=='pv');
                                boot_location.setDisabled(server_location=='');

                                boot_location.setChecked(server_boot=='location',true);
                                boot_filesystem.setChecked(server_boot=='filesystem',true);
                                boot_pxe.setChecked(server_boot=='pxe',true);
                                boot_cdrom.setChecked(server_boot=='cdrom',true);

                                if(server_boot=='cdrom' || server_vm_type!='pv'){
                                    if(cdromcombo.getStore().getTotalCount()>0){
                                        var cb_store = cdromcombo.getStore();
                                        var matched = cb_store.findExact('full_path',server_location);

                                        if(matched == -1) cdromcombo.setValue('');
                                        else cdromcombo.setValue(server_location);

                                    }
                                    else cdromcombo.getStore().reload({
                                            callback:function(){
                                                // populate cdrom combo items
                                                var cb_store = cdromcombo.getStore();
                                                var matched = cb_store.findExact('full_path',server_location);

                                                if(matched == -1) cdromcombo.setValue('');
                                                else cdromcombo.setValue(server_location);
                                            }});
                                }

                            }
                        },
                        handler: function(item) {

                            var server_id = this.form.findField('id').getValue();
                            var server_name = this.form.findField('name').getValue();
                            var server_vm_state = this.form.findField('vm_state').getValue();
                            var node_id = this.form.findField('node_id').getValue();                            

                            var send_data = {'nid': node_id,
                                             'server': server_name};

                            Ext.Msg.show({
                                title: item.text,
                                buttons: Ext.MessageBox.YESNOCANCEL,
                                scope:this,
                                msg: String.format(<?php echo json_encode(__('Current state reported: {0}')) ?>,server_vm_state)+'<br>'
                                     +String.format(<?php echo json_encode(__('Start server {0} ?')) ?>,server_name),
                                fn: function(btn){
                                    if (btn == 'yes'){

                                        var conn = new Ext.data.Connection({
                                            listeners:{
                                                // wait message.....
                                                beforerequest:function(){
                                                    Ext.MessageBox.show({
                                                        title: <?php echo json_encode(__('Please wait...')) ?>,
                                                        msg: <?php echo json_encode(__('Starting virtual server...')) ?>,
                                                        width:300,
                                                        wait:true
                                                     //   modal: true
                                                    });
                                                },// on request complete hide message
                                                requestcomplete:function(){Ext.MessageBox.hide();}
                                                ,requestexception:function(c,r,o){Ext.Ajax.fireEvent('requestexception',c,r,o);}
                                            }
                                        });// end conn
                                        conn.request({
                                            url: <?php echo json_encode(url_for('server/jsonStart'))?>,
                                            params: send_data,
                                            scope:this,
                                            success: function(resp,opt) {

                                                var response = Ext.util.JSON.decode(resp.responseText);
                                                Ext.ux.Logger.info(response['agent'], response['response']);
                                                this.loadRecord({id:server_id});                                                

                                            }
                                            ,failure: function(resp,opt) {
                                                var response = Ext.util.JSON.decode(resp.responseText);
                                                if(response && resp.status!=401)
                                                    Ext.Msg.show({
                                                        title: String.format(<?php echo json_encode(__('Error {0}')) ?>,response['agent']),
                                                        buttons: Ext.MessageBox.OK,
                                                        msg: String.format(<?php echo json_encode(__('Unable to start virtual server {0}!')) ?>,server_name)+'<br>'+response['info'],
                                                        icon: Ext.MessageBox.ERROR});
                                            }
                                        });// END Ajax request
                                    }//END button==yes
                                }// END fn
                            }); //END Msg.show

                        }//END handler
                    },
                    {
                        text: <?php echo json_encode(__('Stop server')) ?>,
                        ref: '../stopBtn',
                        disabled:false,
                        scope:this,
                        handler: function(item) {

                            var server_id = this.form.findField('id').getValue();
                            var server_name = this.form.findField('name').getValue();
                            var server_vm_state = this.form.findField('vm_state').getValue();
                            var node_id = this.form.findField('node_id').getValue();

                            Ext.Msg.show({
                                title: item.text,
                                scope:this,
                                buttons: Ext.MessageBox.YESNOCANCEL,
                                msg: String.format(<?php echo json_encode(__('Current state reported: {0}')) ?>,server_vm_state)+'<br>'
                                     +String.format(<?php echo json_encode(__('Stop server {0} ?')) ?>,server_name),
                                icon: Ext.MessageBox.QUESTION,
                                fn: function(btn){

                                    if (btn == 'yes'){
                                        var params = {'name':server_name};
                                        var conn = new Ext.data.Connection({
                                            listeners:{
                                                // wait message.....
                                                beforerequest:function(){
                                                    Ext.MessageBox.show({
                                                        title: <?php echo json_encode(__('Please wait...')) ?>,
                                                        msg: <?php echo json_encode(__('Stoping virtual server...')) ?>,
                                                        width:300,
                                                        wait:true,
                                                        modal: false
                                                    });
                                                },// on request complete hide message
                                                requestcomplete:function(){Ext.MessageBox.hide();}
                                                ,requestexception:function(c,r,o){
                                                    Ext.MessageBox.hide();
                                                    Ext.Ajax.fireEvent('requestexception',c,r,o);}
                                            }
                                        });// end conn
                                        conn.request({
                                            url: <?php echo json_encode(url_for('server/jsonStop'))?>,
                                            params: {'nid':node_id,'server': server_name},
                                            scope:this,
                                            success: function(resp,opt) {
                                                var response = Ext.util.JSON.decode(resp.responseText);
                                                Ext.ux.Logger.info(response['agent'],response['response']);
                                                this.loadRecord({id:server_id});                                                

                                            },
                                            failure: function(resp,opt) {
                                                var response = Ext.util.JSON.decode(resp.responseText);

                                                Ext.ux.Logger.error(response['agent'], response['error']);

                                                Ext.Msg.show({
                                                    title: String.format(<?php echo json_encode(__('Error {0}')) ?>,response['agent']),
                                                    width:300,
                                                    buttons: Ext.MessageBox.OK,
                                                    msg: String.format(<?php echo json_encode(__('Unable to stop virtual server {0}!')) ?>,server_name)+'<br>'+response['info'],
                                                    icon: Ext.MessageBox.ERROR});
                                            }
                                        });// END Ajax request
                                    }//END button==yes
                                }// END fn
                            }); //END Msg.show

                        }//END handler Remove
                    }
                    ,'-',
                    {
                        text: <?php echo json_encode(__('Edit server')) ?>,
                        ref: '../editBtn',
                        disabled:false,
                        iconCls:'icon-edit-record',
                        url:<?php echo(json_encode(url_for('server/Server_Edit')))?>,
                        call:'Server.Edit',
                        scope:this,
                        callback:function(item){

                            var server_id = (item.scope).form.findField('id').getValue();
                            var server_name = (item.scope).form.findField('name').getValue();
                            var node_id = (item.scope).form.findField('node_id').getValue();
                            
                            var window = new Server.Edit.Window({
                                                title: String.format(<?php echo json_encode(__('Edit server {0}')) ?>,server_name),
                                                server_id:server_id,node_id:node_id});

                            window.on({
                                show:{fn:function(){window.loadData({id:server_id});}}
                                ,onSave:{fn:function(){
                                        this.close();
                                        var parentCmp = Ext.getCmp((item.scope).id);
                                        parentCmp.fireEvent('refresh',parentCmp);
                                }}
                            });
                            
                            window.show();

                        },
                        handler: function(btn){View.loadComponent(btn);}
                    },
                    <?php if($sf_user->getAttribute('etvamodel')!='standard'): ?>
                    {
                        ref: '../migrateBtn',
                        disabled:false,
                        url:<?php echo(json_encode(url_for('server/Server_Migrate')))?>,
                        call:'Server.Migrate',
                        scope:this,
                        callback:function(item){

                            var server_id = (item.scope).form.findField('id').getValue();
                            var server_name = (item.scope).form.findField('name').getValue();
                            var node_id = (item.scope).form.findField('node_id').getValue();

                            var record = {data:{'id':server_id,'name':server_name}};

                            var window = new Server.Migrate.Window({title:item.text,type:item.type, parent:(item.scope).id}).show();
                            window.loadData(record);
                            //eval("var window = new "+item.call+".Window().show();window.loadData(sel)");
                        },
                        handler: function(btn){View.loadComponent(btn);}
                    },
                    <?php endif; ?>
                    {
                        text: <?php echo json_encode(__('Remove server')) ?>,
                        ref: '../removeBtn',
                        disabled:false,
                        iconCls:'icon-remove',
                        url:<?php echo(json_encode(url_for('server/Server_Remove')))?>,
                        call:'Server.Remove',
                        scope:this,
                        callback:function(item){

                            var server_id = (item.scope).form.findField('id').getValue();
                            var server_name = (item.scope).form.findField('name').getValue();                            

                            var window = new Server.Remove.Window({
                                                title: <?php echo json_encode(__('Remove server')) ?>,parent:(item.scope).id});


                            var rec = new Object();
                            rec.data = {'server':server_name,'server_id':server_id};

                            window.on('show',function(){window.loadData(rec);});
                            window.show();

                        },
                        handler: function(btn){View.loadComponent(btn);}
                    }
                    ,'->'
                    ,{
                        text: __('Refresh'),
                        xtype: 'button',
                        ref:'../btn_refresh',
                        tooltip: __('Refresh'),
                        iconCls: 'x-tbar-loading',
                        scope:this,
                        handler: function(button,event)
                        {                            
                            this.loadRecord({id:this.server_id});
                        }
                    },{
                        xtype: 'panel',
                        baseCls: '',
                        tools:[{id:'help', qtip: __('Help'),handler:function(){View.showHelp({anchorid:'help-vmachine-main',autoLoad:{ params:'mod=server'},title: <?php echo json_encode(__('Server Info Help')) ?>});}}]
                    }
//                    ,{
//                        xtype: 'panel',
//                        cls: 'x-tool x-tool-help',
//                        handler:function(){
//                            View.showHelp({
//                                anchorid:'help-vmachine-main',
//                                autoLoad:{ params:'mod=server'},
//                                title: <?php echo json_encode(__('Remove Server Help')) ?>
//                            });
//                        }
//
//                    }

        ];

        /*
        *  build  network interfaces info
        *
        */

        var nic_cm = new Ext.grid.ColumnModel([
            new Ext.grid.RowNumberer(),
            {
                id:'mac',
                header: "MAC Address",
                dataIndex: 'mac',
                fixed:true,
                allowBlank: false,
                width: 120
            },
            {
                header: "Network",
                dataIndex: 'vlan',
                width: 130
            }

        ]);

        var nic_store = new Ext.data.JsonStore({
                        proxy: new Ext.data.HttpProxy({url:<?php echo json_encode(url_for('network/jsonGridNoPager')); ?>}),
                        baseParams: {'query': Ext.encode({'server_id':this.server_id})},
                        totalProperty: 'total',
                        root: 'data',
                        fields: [
                                {name:'vlan',mapping:'Vlan'},
                                {name:'mac',mapping:'Mac'}],
                        remoteSort: false});


        this.nic_grid = new Ext.grid.GridPanel({
                            border: false,
                            anchor: '-20',
                            autoHeight: true,
                            store: nic_store,
                            cm: nic_cm,
                            loadMask: true,
                            viewConfig: {forceFit:true},
                            stripeRows: true,
                            bbar: new Ext.ux.grid.TotalCountBar({
                                displayMsg: <?php echo json_encode(__('Total of {2} interface(s)')) ?>,
                                store: nic_store
                                ,displayInfo:true
                            }),
                            title: <?php echo json_encode(__('Network interfaces')) ?>
        });        



        /*
        *  build  disks info
        *
        */

        var disk_store = new Ext.data.JsonStore({
                            proxy: new Ext.data.HttpProxy({url:<?php echo json_encode(url_for('logicalvol/jsonList')); ?>}),
                            baseParams: {'sid': this.server_id},
                            fields:[
                                {name:'lv', type:'string'}
                                ,{name:'size', type:'int'}
                                ,{name:'pos', type:'int'}],
                            totalProperty: 'total',
                            root: 'data',
                            listeners:{
                                load:{scope:this,fn:function(){

                                    /*
                                     * on store reload make sort by pos
                                     */
                                    disk_store.setDefaultSort('pos','ASC' );
                                    disk_store.sort([{ field: 'pos', direction: 'ASC' }]);

                                }}
                            }
        });

        var disk_cols = [
                new Ext.grid.RowNumberer(),
                {id:'lv', header: "Name", width: 150, sortable: false, dataIndex: 'lv'},
                {header: "Size", width: 150, sortable: true, dataIndex: 'size',renderer:function(v){return Ext.util.Format.fileSize(v);}}
        ];


        this.disk_grid = new Ext.grid.GridPanel({
                            border: false,                            
                            anchor: '-20',
                            autoHeight: true,
                            store: disk_store,
                            columns: disk_cols,
                            loadMask: true,
                            viewConfig: {forceFit:true},
                            stripeRows: true,
                            autoExpandColumn: 'lv',
                            bbar: new Ext.ux.grid.TotalCountBar({
                                displayMsg: <?php echo json_encode(__('Total of {2} disk(s)')) ?>,
                                store: disk_store
                                ,displayInfo:true
                            }),
                            title: <?php echo json_encode(__('Attached disks')) ?>
        });

        this.items = [
                {xtype:'hidden',name:'id'}
                ,{xtype:'hidden',name:'boot'}
                ,{xtype:'hidden',name:'node_id'}
                ,{xtype:'hidden',name:'location'}              
                ,{
                    anchor: '100% 100%',
                    layout: {
                        type: 'hbox',
                        align: 'stretch'  // Child items are stretched to full width
                    }
                    ,defaults:{layout:'form',autoScroll:true,bodyStyle:'padding:10px;',border:false}
                    ,items:[
                            {
                                flex:1,
                                defaultType:'displayfield',
                                items:[
                                    {                                        
                                        name: 'name',
                                        fieldLabel : <?php echo json_encode(__('Virtual server name')) ?>
                                    },
                                    {                                        
                                        name: 'description',
                                        fieldLabel : <?php echo json_encode(__('Description')) ?>
                                    }
                                    ,{                                        
                                        name: 'mem',
                                        fieldLabel : <?php echo json_encode(__('Memory size (MB)')) ?>
                                    }
                                    ,{                                        
                                        name: 'vcpu',
                                        fieldLabel : <?php echo json_encode(__('Virtual CPUs')) ?>
                                    }
                                    ,this.disk_grid
                                ]//end items flex
                            }
                            ,{
                                flex:1,
                                defaultType:'displayfield',
                                items:[
                                    {                                        
                                        name: 'vm_type',
                                        fieldLabel : <?php echo json_encode(__('Virtual server type')) ?>
                                    },
                                    {                                        
                                        name: 'vm_state',
                                        fieldLabel : <?php echo json_encode(__('Virtual server state')) ?>
                                    },
                                    {                                        
                                        name: 'ip',
                                        fieldLabel : <?php echo json_encode(__('IP')) ?>
                                    }
                                    ,this.nic_grid
                                ]
                            }
                    ]
                }];

        Server.View.Info.superclass.initComponent.call(this);

        this.on({refresh:{scope:this,fn:function(){                    
                    this.loadRecord({id:this.server_id});
                }}
        });



    }
    ,onRender:function(){
        // call parent
        Server.View.Info.superclass.onRender.apply(this, arguments);
        // set wait message target
        //this.getForm().waitMsgTarget = this.getEl();
    }
    ,loadRecord:function(data){
        
        this.btn_refresh.addClass('x-item-disabled');
        this.disk_grid.getStore().load.defer(100,this.disk_grid.getStore());
        this.nic_grid.getStore().load.defer(100,this.nic_grid.getStore());
        
        this.load({url: <?php echo json_encode(url_for('server/jsonLoad')) ?>,params:data
            ,scope:this
            ,waitMsg: <?php echo json_encode(__('Retrieving data...')) ?>
            ,method:'POST'
            ,success:function(f,a){
                this.btn_refresh.removeClass('x-item-disabled');
                var data = a.result['data'];

                this.fireEvent('updateNodeState',{selected:true,parentNode:data['node_id'],node:'s'+data['id']},data);

                /*
                 * check node id and state
                 */

                var node_id = data.node_id;
                var node_state = data.node_state;
                var not_running_msg = <?php echo json_encode(__('VirtAgent should be running to enable this menu')) ?>;

                this.addwizardBtn.setDisabled(!node_state);
                this.addwizardBtn.url = <?php echo(json_encode(url_for('server/wizard?nid=')))?>+node_id;


                if(node_state)
                {
                    this.addwizardBtn.el.child('button:first').dom.qtip = <?php echo json_encode(__('Click to open new server wizard')) ?>;
                }
                else
                {
                    this.addwizardBtn.el.child('button:first').dom.qtip = not_running_msg;
                }                

                /*
                 * check vm state
                 */
                 var vm_state = this.form.findField('vm_state');                 
                 if(data['vm_state']=='running')
                 {
                   vm_state.removeClass('vm-state-notrunning');
                   vm_state.addClass('vm-state-running');

                   if(data['vm_type']!='pv')
                   {
                       this.editBtn.setTooltip(<?php echo json_encode(__('Server need to be stop to edit!')) ?>);
                       this.editBtn.setDisabled(true);                       
                   }


                   <?php if($sf_user->getAttribute('etvamodel')!='standard'): ?>
                   /*
                    *
                    * check migrate/move button
                    */
                     this.migrateBtn.type = 'migrate';
                     this.migrateBtn.setTooltip(<?php echo json_encode(__('To perform a move instead of a migrate the server must be stopped!')); ?>);
                     this.migrateBtn.setText(<?php echo json_encode(__('Migrate server')) ?>);
                     this.migrateBtn.setDisabled(false);                     


                   <?php endif; ?>
                 }
                 else
                 {
                     vm_state.removeClass('vm-state-running');
                     vm_state.addClass('vm-state-notrunning');

                     this.editBtn.setDisabled(false);

                     <?php if($sf_user->getAttribute('etvamodel')!='standard'): ?>

                     /*
                      *
                      * check migrate/move button
                      */
                     this.migrateBtn.type = 'move';
                     this.migrateBtn.setTooltip(<?php echo json_encode(__('To perform a migrate instead of a move the server must be running!')); ?>);
                     this.migrateBtn.setText(<?php echo json_encode(__('Move server')) ?>);
                     this.migrateBtn.setDisabled(false);
                     <?php endif; ?>
                 }

                 if(this.migrateBtn && !data['all_shared_disks'])
                 {
                         this.migrateBtn.setTooltip(<?php echo json_encode(__(EtvaLogicalvolumePeer::_NOTALLSHARED_)); ?>);
                         this.migrateBtn.setDisabled(true);
                 }
                 
                 this.startBtn.setDisabled(data['vm_state']=='running');

            }
        });
    }
    ,updateRecords:function(obj){
            var send_data = Ext.encode(obj.data);
            var conn = new Ext.data.Connection({
                listeners:{
                    // wait message.....
                    beforerequest:function(){
                        Ext.MessageBox.show({
                            title: <?php echo json_encode(__('Please wait...')) ?>,
                            msg: <?php echo json_encode(__('Please wait...')) ?>,
                            width:300,
                            wait:true,
                            modal: false
                        });
                    },// on request complete hide message
                    requestcomplete:function(){Ext.MessageBox.hide();}
                    ,requestexception:function(c,r,o){
                            Ext.MessageBox.hide();
                            Ext.Ajax.fireEvent('requestexception',c,r,o);}
                }
            });// end conn

            conn.request({
                url: <?php echo json_encode(url_for('server/jsonSetBoot')) ?>,scope:this,
                params: {
                    boot:obj.boot,
                    data:send_data,
                    id: this.server_id
                },
                success: function(resp,opt) {
                    this.loadRecord({id:this.server_id});

                },
                failure: function(resp,opt) {
                    Ext.Msg.alert(<?php echo json_encode(__('Error!')) ?>, <?php echo json_encode(__('Could not save changes!')) ?>);

                }
            });//END Ajax request

    }
});

</script>