<script>
Ext.ns('Server.GuestAgent');

Server.GuestAgent.Info = Ext.extend(Ext.form.FormPanel, {
    border:false,
    labelWidth:140,
    defaults:{border:false},
    initComponent:function(){

        this.tbar = [
            '->'
            ,{
                text: __('Refresh'),
                xtype: 'button',
                ref:'../btn_refresh',
                tooltip: __('Refresh'),
                iconCls: 'x-tbar-loading',
                scope:this,
                handler: function(button,event)
                {                            
                    var parentCmp = Ext.getCmp((button.scope).id);
                    console.log(parentCmp);
                    parentCmp.fireEvent('refresh',parentCmp);
                }
            },{
                xtype: 'panel',
                baseCls: '',
                tools:[{id:'help', 
                qtip: __('Help'),
                handler:function(){
                        View.showHelp({
                            anchorid:'help-vmachine-main',autoLoad:{ params:'mod=server'},title: <?php echo json_encode(__('Server Info Help')) ?>
                        });
                    }
                }]
            }
        ];

        /*
        *  build  network interfaces info
        *
        */

        var nic_cm = new Ext.grid.ColumnModel([
            new Ext.grid.RowNumberer(),
            {
                id:'name',
                header: <?php echo json_encode(__('Name')) ?>,
                dataIndex: 'name',
                allowBlank: false,
                width: 75
            },
            {
                id:'inet4',
                header: <?php echo json_encode(__('IPv4')) ?>,
                dataIndex: 'inet',
                allowBlank: false,
                width: 75
            },
            {
                id:'inet6',
                header: <?php echo json_encode(__('IPv6')) ?>,
                dataIndex: 'inet6',
                allowBlank: false,
                width: 75
            },
            {
                id:'hw',
                header: <?php echo json_encode(__('MAC Address')) ?>,
                dataIndex: 'hw',
                allowBlank: false,
                width: 75
            }
        ]);

        var nic_store = new Ext.data.JsonStore({
                        proxy: new Ext.data.HttpProxy({url:<?php echo json_encode(url_for('server/jsonGAInterfaces')); ?>}),
//                        baseParams: {'query': Ext.encode({'server_id':this.server_id}), 'sort':'port', 'dir':'asc'},
                        baseParams: {'sid':this.server_id},
                        totalProperty: 'total',
                        root: 'data',
                        fields: [
                                {name:'name',   mapping:'name'},
                                {name:'inet',   mapping:'inet'},
                                {name:'inet6',  mapping:'inet6'},
                                {name:'hw',     mapping:'hw'}
                        ],
                        remoteSort: false
        });


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
                            proxy: new Ext.data.HttpProxy({url:<?php echo json_encode(url_for('server/jsonGADisks')); ?>}),
                            baseParams: {'sid': this.server_id},
                            fields:[
                                {name:'fs', type:'string'}
                                ,{name:'path', type:'string'}
                                ,{name:'used', type:'int'}
                                ,{name:'total', type:'int'}
                            ],
                            totalProperty: 'total',
                            root: 'data',
                            listeners:{
                                metachange: {scope:this,fn:function(store, meta){
                                    }
                                }    
                                ,load:{scope:this,fn:function(){
                                    /*
                                     * on store reload make sort by pos
                                     */
                                    disk_store.setDefaultSort('path','ASC' );
                                    disk_store.sort([{ field: 'path', direction: 'ASC' }]);

                                }}
                            }
        });

        var disk_cols = [
            new Ext.grid.RowNumberer(),
            {
                header: <?php echo json_encode(__('Path')) ?>, 
                sortable: true, 
                dataIndex: 'path'
//                ,renderer:function(v){
//                    return Ext.util.Format.fileSize(v);
//                }
            },{
                id:'fs', 
                header: <?php echo json_encode(__('File System')) ?>, 
                sortable: false, 
                dataIndex: 'fs'
            },{
                header: <?php echo json_encode(__('Used')) ?>,
                dataIndex: 'used'
            },{
                header: <?php echo json_encode(__('Total')) ?>,
                dataIndex: 'total'
            }
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
                            autoExpandColumn: 'path',
                            bbar: new Ext.ux.grid.TotalCountBar({
                                displayMsg: <?php echo json_encode(__('Total of {2} disk(s)')) ?>,
                                store: disk_store
                                ,displayInfo:true
                            }),
                            title: <?php echo json_encode(__('Mount point')) ?>
        });

        /*
        *  build applications info
        *
        */

        var app_store = new Ext.data.JsonStore({
                            proxy: new Ext.data.HttpProxy({url:<?php echo json_encode(url_for('server/jsonGAApps')); ?>}),
                            baseParams: {'sid': this.server_id},
                            fields:[
                                {name:'name', type:'string'}
                            ],
                            totalProperty: 'total',
                            root: 'data',
                            listeners:{
                                metachange: {scope:this,fn:function(store, meta){
                                    }
                                }    
                                ,load:{scope:this,fn:function(){
                                    /*
                                     * on store reload make sort by pos
                                     */
                                    app_store.setDefaultSort('name','ASC' );
                                    app_store.sort([{ field: 'name', direction: 'ASC' }]);

                                }}
                            }
        });

        var app_cols = [
            new Ext.grid.RowNumberer(),
            {
                header: <?php echo json_encode(__('Name')) ?>, 
                sortable: true, 
                dataIndex: 'name'
//                ,renderer:function(v){
//                    return Ext.util.Format.fileSize(v);
//                }
            }
        ];

        this.app_grid = new Ext.grid.GridPanel({
                            border: false,                            
                            anchor: '-20',
                            autoHeight: true,
                            store: app_store,
                            columns: app_cols,
                            loadMask: true,
                            viewConfig: {forceFit:true},
                            stripeRows: true,
                            autoExpandColumn: 'path',
                            bbar: new Ext.ux.grid.TotalCountBar({
                                displayMsg: <?php echo json_encode(__('Total of {2} application(s)')) ?>,
                                store: app_store
                                ,displayInfo:true
                            }),
                            title: <?php echo json_encode(__('Applications')) ?>
        });
        this.items = [
                {xtype:'hidden',name:'id'}
                ,{xtype:'hidden',name:'name'}
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
                                        name: 'state',
                                        fieldLabel : <?php echo json_encode(__('State')) ?>
                                    },{                                        
                                        name: 'hostname',
                                        fieldLabel : <?php echo json_encode(__('Hostname')) ?>
                                    }
                                    ,{                                        
                                        name: 'os-version',
                                        fieldLabel : <?php echo json_encode(__('OS Version')) ?>
                                    }
                                    ,{                                        
                                        name: 'active-user',
                                        fieldLabel : <?php echo json_encode(__('Active User')) ?>
                                    }
                                    ,{                                        
                                        name: 'free-ram',
                                        fieldLabel : <?php echo json_encode(__('Free RAM')) ?>
                                    }
                                    ,{                                        
                                        name: 'heartbeat',
                                        fieldLabel : <?php echo json_encode(__('Last hearbeat')) ?>
                                    }
                                    ,this.disk_grid
                                ]//end items flex
                            }
                            ,{
                                flex:1,
                                defaultType:'displayfield',
                                items:[
//                                    {                                        
//                                        name: 'vm_type',
//                                        fieldLabel : <?php echo json_encode(__('Virtual server type')) ?>
//                                    },
                                    this.nic_grid
                                    ,{xtype:'spacer',height:20}
                                    ,this.app_grid
                                ]
                            }
                    ]
                }];

        Server.GuestAgent.Info.superclass.initComponent.call(this);

        this.on({refresh:{scope:this,fn:function(e){                    
                    this.loadRecord({id:this.server_id});
                    this.fireEvent('reloadTree',{ 'server_id': 's' + this.server_id });
                }}
        });

//        this.refreshTask({id:this.server_id});


    }
    ,onRender:function(){
        // call parent
        Server.GuestAgent.Info.superclass.onRender.apply(this, arguments);
        // set wait message target
        //this.getForm().waitMsgTarget = this.getEl();
    }
//    ,readGuestAgent: function(data){
//        var send_data = Ext.encode(obj.data);
//        var conn = new Ext.data.Connection({
//            listeners:{
//                // wait message.....
//                beforerequest:function(){
//                    Ext.MessageBox.show({
//                        title: <?php echo json_encode(__('Please wait...')) ?>,
//                        msg: <?php echo json_encode(__('Please wait...')) ?>,
//                        width:300,
//                        wait:true,
//                        modal: false
//                    });
//                },// on request complete hide message
//                requestcomplete:function(){Ext.MessageBox.hide();}
//                ,requestexception:function(c,r,o){
//                        Ext.MessageBox.hide();
//                        Ext.Ajax.fireEvent('requestexception',c,r,o);}
//            }
//        });// end conn
//
//        conn.request({
//            url: <?php echo json_encode(url_for('server/jsonReadAgent')) ?>,scope:this,
//            params: {
//                boot:obj.boot,
//                data:send_data,
//                id: this.server_id
//            },
//            success: function(resp,opt) {
//                this.loadRecord({id:this.server_id});
//
//            },
//            failure: function(resp,opt) {
//                Ext.Msg.alert(<?php echo json_encode(__('Error!')) ?>, <?php echo json_encode(__('Could not save changes!')) ?>);
//
//            }
//        });//END Ajax request
//    }
    ,hasPerm:function(data){

        var record = new Object();
        record.data = new Object();
        record.data['id'] = data['id'];
        record.data['level'] = 'server';

        var conn = new Ext.data.Connection({
//            listeners:{
//                // wait message.....
//                beforerequest:function(){
//                    Ext.MessageBox.show({
//                        title: <#?php echo json_encode(__('Please wait...')) ?>,
//                        msg: <#?php echo json_encode(__('Please wait...')) ?>,
//                        width:300,
//                        wait:true,
//                        modal: false
//                    });
//                },// on request complete hide message
//                requestcomplete:function(){Ext.MessageBox.hide();}
//                ,requestexception:function(c,r,o){
//                        Ext.MessageBox.hide();
//                        Ext.Ajax.fireEvent('requestexception',c,r,o);}
//            }
        });// end conn

        conn.request({
            url: <?php echo json_encode(url_for('sfGuardPermission/jsonHasPermission')) ?>,
            scope:this,
            params:record.data,
            success: function(resp,opt) {
//                this.loadData();
//                var response = Ext.util.JSON.decode(resp.responseText);
                var response = Ext.decode(resp.responseText);
                
                if(response['datacenter']){
                }else{
                    if(response['server']){

                    }else{

                    }
                }

            },
            failure: function(resp,opt) {

                var response = Ext.util.JSON.decode(resp.responseText);
                Ext.ux.Logger.error(response['agent'],response['error']);

                Ext.Msg.show({
//                        title: String.format(<?php echo json_encode(__('Error {0}')) ?>,response['agent']),
                    buttons: Ext.MessageBox.OK,
                    msg: response['info'],
                    icon: Ext.MessageBox.ERROR});

            }
        });
    }
    ,refreshTask: function(data){

        console.log('refresh task called');
        /*
         * run task ever 5 seconds...check upload progress
         */
        var task = {
            scope:this,
            run:function(){
                this.loadRecord(data, true);

            },interval: 20000
        };

        Ext.TaskMgr.start(task);
    }
    ,loadRecord:function(data, isTask){
//        this.hasPerm(data);
//        this.btn_refresh.addClass('x-item-disabled');
        this.disk_grid.getStore().load.defer(500,this.disk_grid.getStore());
        this.nic_grid.getStore().load.defer(500,this.nic_grid.getStore());
        this.app_grid.getStore().load.defer(500,this.app_grid.getStore());
       
        var conf = {
            url: <?php echo json_encode(url_for('server/jsonLoadGA')) ?>
            ,params:data
            ,scope:this
//            ,waitMsg: <?php echo json_encode(__('Retrieving data...')) ?>
            ,method:'POST'
            ,success:function(f,a){
                this.btn_refresh.removeClass('x-item-disabled');
                var data = a.result['data'];

                /*
                 * check node id and state
                 */

                var node_id = data.node_id;
                var node_state = data.node_state;

                /*
                 * check ga state
                 */
                 var state = this.form.findField('state');                 
                 if(data['state']=='running')
                 {
                    state.removeClass('vm-state-notrunning');
                    state.addClass('vm-state-running');
                 }
                 else
                 {
                     state.removeClass('vm-state-running');
                     state.addClass('vm-state-notrunning');
                 }
            }
        };
        
        if(!isTask){
            conf['waitMsg'] = <?php echo json_encode(__('Retrieving data...')) ?>;
        }
        
        this.load(conf);
    }
});

</script>
