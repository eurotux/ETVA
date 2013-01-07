<script>
Ext.ns('Server.PloneService');

Server.PloneService.Info = Ext.extend(Ext.form.FormPanel, {
    labelWidth:140,
    defaults:{border:false},
    initComponent:function(){

        this.tbar = [
            {
                text: 'DB Pack',
                iconCls:'icon-edit-record',
                scope: this,
                handler: function(button, event){
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
//                        url: <?php echo json_encode(url_for('server/jsonPlonePack')) ?>,scope:this,
                        url: <?php echo json_encode(url_for('etasp/json')) ?>,scope:this,
                        params: {
                            id: this.server_id,
                            service: this.service
                            ,method: 'pack'
                        },
                        success: function(resp,opt) {
                            var response = Ext.util.JSON.decode(resp.responseText);

                            console.log(response);
                            if( response['data']['success'] == 'ok' ){
                                var msg;
                                if( response['data']['msg']['pack']['status'] == 1 ){
                                    msg = <?php echo json_encode(__('Process started with success')) ?>; 
                                }else{
                                    msg = <?php echo json_encode(__('Could not start the process')) ?>; 
                                    msg += '<br/>Return: 0';
                                }

                                Ext.Msg.show({
                                    title: <?php echo json_encode(__('DB pack')) ?>,
                                    buttons: Ext.MessageBox.OK,
                                    msg: msg,
                                    icon: Ext.MessageBox.INFO
                                });
                            }else{
                                Ext.Msg.show({
                                    title: String.format(<?php echo json_encode(__('Error {0}')) ?>,response['agent']),
                                    buttons: Ext.MessageBox.OK,
                                    msg: String.format(err_msg,form_values['name'])+'<br>'+response['info'],
                                    icon: Ext.MessageBox.ERROR
                                });
                            }
                        },
                        failure: function(resp,opt) {
                            var response = Ext.util.JSON.decode(resp.responseText);
                            Ext.Msg.show({
                                title: String.format(<?php echo json_encode(__('Error {0}')) ?>,response['agent']),
                                buttons: Ext.MessageBox.OK,
                                msg: String.format(err_msg,form_values['name'])+'<br>'+response['info'],
                                icon: Ext.MessageBox.ERROR
                            });
                        }
                    });//END Ajax request
                }
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
                                        xtype:'fieldset'
                                        ,title: <?php echo json_encode(__('Metadata')) ?>
                                        ,defaultType:'displayfield'
                                        /*layout: 'hbox',
                                        defaults: { border: false },*/
                                        ,items:[
                                                //getInstanceMetadata
                                                {                                        
                                                    name: 'python_version',
                                                    fieldLabel : <?php echo json_encode(__('Python')) ?>
                                                },{                                        
                                                    name: 'zope_version',
                                                    fieldLabel : <?php echo json_encode(__('Zope')) ?>
                                                }
                                                ,{                                        
                                                    name: 'plone_version',
                                                    fieldLabel : <?php echo json_encode(__('Plone')) ?>
                                                }
                                                ,{                                        
                                                    name: 'etasp_version',
                                                    fieldLabel : <?php echo json_encode(__('ETASP')) ?>
                                                }
                                                ,{                                        
                                                    name: 'instance_home',
                                                    fieldLabel : <?php echo json_encode(__('Instance Home')) ?>
                                                }
                                                ,{                                        
                                                    name: 'http_port',
                                                    fieldLabel : <?php echo json_encode(__('Http Port')) ?>
                                                }
                                                ,{                                        
                                                    name: 'uptime',
                                                    fieldLabel : <?php echo json_encode(__('Uptime')) ?>
                                                }
                                                ,{                                        
                                                    name: 'debug_mode',
                                                    fieldLabel : <?php echo json_encode(__('Debug Mode')) ?>
                                                }
                                    ]}
                                    ,{
                                        xtype:'fieldset'
                                        ,title: <?php echo json_encode(__('Resource Usage')) ?>
                                        ,defaultType:'displayfield'
                                        /*layout: 'hbox',
                                        defaults: { border: false },*/
                                        ,items:[
                                            {                                        
                                                name: 'memory',
                                                fieldLabel : <?php echo json_encode(__('Memory')) ?>,
                                                tpl: new Ext.XTemplate('{memory:this.formatMemSize}', {
                                                    formatMemSize: function(v) {
                                                        if(v){ 
                                                            alert(v);
                                                            return Ext.util.Format.fileSize(v);
                                                        }else{
                                                            return '&#160;';
                                                        }
                                                    }
                                                })
                                                                                                                        
                                            }
                                            ,{                                        
                                                name: 'threads',
                                                fieldLabel : <?php echo json_encode(__('Threads')) ?>
                                            }
                                            ,{                                        
                                                name: 'busythreads',
                                                fieldLabel : <?php echo json_encode(__('Busy Threads')) ?>
                                            }
                                        ]
                                    }
//                                    ,this.disk_grid
                                ]//end items flex
                            }
                            ,{
                                flex:1,
                                defaultType:'displayfield',
                                items:[
                                    {
                                        xtype:'fieldset',
                                        title: <?php echo json_encode(__('Database Info')) ?>,
                                        /*layout: 'hbox',
                                        defaults: { border: false },*/
                                        items:[
                                                {                                        
                                                    name: 'tcp_port',
                                                    fieldLabel : <?php echo json_encode(__('TCP Port')) ?>
                                                    ,xtype: 'displayfield'
                                                },{     
                                                    xtype: 'spacer', height: '10' 
                                                },
                                                            {
                                                                name       : 'blobstorage_type',
//                                                                ref        : '../blobstorage_type',
                                                                xtype      : 'displayfield',
                                                                fieldLabel : <?php echo json_encode(__('Blob Storage')) ?>,
                                                                width:50,
                                                                readOnly:true
                                                            }
                                                            ,{
                                                                xtype      : 'displayfield',
                                                                fieldLabel: <?php echo json_encode(__('Path')) ?>,
                                                                name       : "blobstorage_path",
                                                                ref        : 'vcpu',
                                                                allowBlank:false,
                                                                allowNegative:false,
                                                                validator:function(v){
            
//                                                                    var max_cpu = (this.ownerCt).node_ncpus.getValue();
//                                                                    if(max_cpu)
//                                                                        if(v > max_cpu) return <?php echo json_encode(__('Cannot exceed max cpu')) ?>;
//                                                                        else return true;
                                                                },
                                                                listeners    : {
                                                                        'change': function(f,n,o){
                                                                                var vcpu = (f.ownerCt).vcpu.getValue();
                                                                                // set sockets and cores by default
                                                                                if( (vcpu % 2) == 0 ){
                                                                                    var half_vcpu = vcpu/2;
                                                                                    (f.ownerCt).cpu_sockets.setValue(half_vcpu);
                                                                                    (f.ownerCt).cpu_cores.setValue(half_vcpu);
                                                                                } else {
                                                                                    (f.ownerCt).cpu_sockets.setValue(vcpu);
                                                                                    (f.ownerCt).cpu_cores.setValue(1);
                                                                                }
                                                                                (f.ownerCt).cpu_threads.setValue(1);
                                                                        }
                                                                },
                                                                scope:this,
                                                                width: 50
                                                            },{
                                                                name       : 'blobstorage_size',
                                                                xtype      : 'displayfield',
                                                                fieldLabel : <?php echo json_encode(__('Size')) ?>,
                                                            },
                                                            { xtype: 'spacer', height: '10' },
                                                            {
                                                                xtype      : 'displayfield',
                                                                name          : 'filestorage_type',
                                                                fieldLabel    : <?php echo json_encode(__('Storage Type')) ?>,
                                                            }
                                                            ,{
                                                                xtype      : 'displayfield',
                                                                name          : 'filestorage_path',
                                                                fieldLabel    : <?php echo json_encode(__('Path')) ?>,
//                                                                vtype         : 'vm_vcpu_topology'
//                                                                ,listeners    : {
//                                                                        'change': this.onVCPUChange
//                                                                }
                                                            }
                                                            ,{
                                                                xtype      : 'displayfield',
                                                                name          : 'filestorage_size',
                                                                fieldLabel    : <?php echo json_encode(__('Size')) ?>,
                                                            }
                                                    /*]
                                                }*/
                                            ]
                                    },
                                ]
                            }
                    ]
                }];

        Server.PloneService.Info.superclass.initComponent.call(this);

        this.on({refresh:{scope:this,fn:function(e){                    
                    this.loadRecord({id:this.server_id});
                    this.fireEvent('reloadTree',{ 'server_id': 's' + this.server_id });
                }}
        });

//        this.refreshTask({id:this.server_id});


    }
    ,onRender:function(){
        // call parent
        Server.PloneService.Info.superclass.onRender.apply(this, arguments);
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
        console.log("load record called");
        console.log(data);
        data['method'] = 'allinfo';

        this.hasPerm(data);
//        this.btn_refresh.addClass('x-item-disabled');
//        this.disk_grid.getStore().load.defer(500,this.disk_grid.getStore());
//        this.app_grid.getStore().load.defer(500,this.app_grid.getStore());
       
        console.log("loading guest agent info");
        var conf = {
            url: <?php echo json_encode(url_for('etasp/json')) ?>
            ,params:data
            ,scope:this
//            ,waitMsg: <?php echo json_encode(__('Retrieving data...')) ?>
            ,method:'POST'
            ,success:function(f,a){
                this.btn_refresh.removeClass('x-item-disabled');
                var data = a.result['data'];

                console.log(data);
                data['blobstorage_type'] = 'blobstorage';
                data['blobstorage_path'] = data['storage']['blobstorage']['path'];
                data['blobstorage_size'] = data['storage']['blobstorage']['size'];
                this.form.findField('blobstorage_type').setValue(data['blobstorage_type']);
                this.form.findField('blobstorage_path').setValue(data['blobstorage_path']);
                this.form.findField('blobstorage_size').setValue(data['blobstorage_size']);

                data['filestorage_type'] = 'filestorage';
                data['filestorage_path'] = data['storage']['filestorage']['path'];
                data['filestorage_size'] = data['storage']['filestorage']['size'];
                this.form.findField('filestorage_type').setValue(data['filestorage_type']);
                this.form.findField('filestorage_path').setValue(data['filestorage_path']);
                this.form.findField('filestorage_size').setValue(data['filestorage_size']);

                /*
                 * check node id and state
                 */

                var node_id = data.node_id;
                var node_state = data.node_state;

//                /*
//                 * check ga state
//                 */
//                 var state = this.form.findField('state');                 
//                 if(data['state']=='running')
//                 {
//                    state.removeClass('vm-state-notrunning');
//                    state.addClass('vm-state-running');
//                 }
//                 else
//                 {
//                     state.removeClass('vm-state-running');
//                     state.addClass('vm-state-notrunning');
//                 }
            }
        };
        
        if(!isTask){
            conf['waitMsg'] = <?php echo json_encode(__('Retrieving data...')) ?>;
        }
        
        this.load(conf);
//        if(!isTask){
//        }
    }
//    ,updateRecords:function(obj){
//        console.log("updateRecords called");
//        concole.log(obj);
//            var send_data = Ext.encode(obj.data);
//            var conn = new Ext.data.Connection({
//                listeners:{
//                    // wait message.....
//                    beforerequest:function(){
//                        Ext.MessageBox.show({
//                            title: <?php echo json_encode(__('Please wait...')) ?>,
//                            msg: <?php echo json_encode(__('Please wait...')) ?>,
//                            width:300,
//                            wait:true,
//                            modal: false
//                        });
//                    },// on request complete hide message
//                    requestcomplete:function(){Ext.MessageBox.hide();}
//                    ,requestexception:function(c,r,o){
//                            Ext.MessageBox.hide();
//                            Ext.Ajax.fireEvent('requestexception',c,r,o);}
//                }
//            });// end conn
//
//            conn.request({
//                url: <?php echo json_encode(url_for('server/jsonSetBoot')) ?>,scope:this,
//                params: {
//                    boot:obj.boot,
//                    data:send_data,
//                    id: this.server_id
//                },
//                success: function(resp,opt) {
//                    this.loadRecord({id:this.server_id});
//
//                },
//                failure: function(resp,opt) {
//                    Ext.Msg.alert(<?php echo json_encode(__('Error!')) ?>, <?php echo json_encode(__('Could not save changes!')) ?>);
//
//                }
//            });//END Ajax request
//
//    }
});

</script>
