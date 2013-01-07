<script>

Ext.ns("Setting");

Setting.Form = Ext.extend(Ext.form.FormPanel, {    
    labelWidth:130,
    border:false
    /* build connectivity tab layout */
    ,buildCMConn:function(){

        var static_source_manag = new Ext.Panel(View.StaticIpTpl('cm_management'));
        var tpl = [{
                    xtype: 'fieldset',
                    title: 'Central Management IP',
                    collapsible: false,
                    items: [
                            {xtype:'hidden',name:'network_cm_management_if'},
                            static_source_manag
                    ]
        }];
        return tpl;

    }
    ,initComponent:function() {
        this.get_data = ['vnc_keymap','eventlog_flush'];
        this.fetch = Ext.ux.util.clone(this.get_data);
        this.fetch.push('networks');


        var static_source_lan = new Ext.Panel(View.StaticIpTpl('lan'));
        var dns_static_lan = new Ext.Panel(View.DnsTpl('network'));

        var conn_tab = {title: <?php echo json_encode(__('Connectivity')) ?>,defaults:{layout:'form',border:true,autoScroll:true}};


        this.devices = <?php
                            /*
                             * build interfaces device
                             */
                            $interfaces_devices = sfConfig::get('app_device_interfaces');
                            $devices = $interfaces_devices[$etvamodel];
                            unset($devices['va_management']);

                            $devices_array = array();

                            foreach($devices as $tag =>$if)
                            {                                                                
                                $devices_array[] = json_encode($tag);

                            }

                            echo '['.implode(',',$devices_array).'];'."\n";

                        ?>

        <?php if($etvamodel=='standard'):?>
        

        conn_tab.items = [
            {
                //anchor: '100% 100%',
                border:false,
                height:150,
                defaults:{border:false},
                layout:{type: 'hbox',align: 'stretch'},
                items:[


                {flex:1,items:[this.buildCMConn()]}//end 1col
                ,{width:10}
                ,{flex:1,items:[
                    {
                        xtype: 'fieldset',
                        title: <?php echo json_encode(__('LAN interface IP')) ?>,
                        collapsible: false,
                        items: [{xtype:'hidden',name:'network_lan_if'},
                                static_source_lan
                        ]
                    }

                ]}//end 2col
                ]
            },
            {
                xtype: 'fieldset',
                title: <?php echo json_encode(__('DNS')) ?>,
                collapsible: false,
                items: [dns_static_lan]
            }
        ];

        <?php else: ?>
        
        conn_tab.items = [this.buildCMConn(),{
                                xtype: 'fieldset',
                                title: <?php echo json_encode(__('DNS')) ?>,
                                collapsible: false,
                                items: [dns_static_lan]
                         }];

        <?php endif;?>


        var config = {
            monitorValid:true,
            items:[{xtype:'tabpanel',activeItem:0,deferredRender:false,
             anchor: '100% 100%',
             defaults:{
                 layout:'form'
                 ,bodyStyle: 'padding: 15 5 5 5'
                 ,autoScroll:true
                 ,labelWidth:140
            },items:[
                //1st tab
                {
                    title: <?php echo json_encode(__('General')) ?>,
                    defaults:{layout:'form',border:true,autoScroll:true},
                    items:[
                        {
                            xtype: 'fieldset',
                            title: <?php echo json_encode(__('VNC options')) ?>,
                            collapsible: true,
                            items: [
                                {
                                    xtype : 'compositefield',
                                    fieldLabel: <?php echo json_encode(__('Default keymap')) ?>,
                                    items : [new Setting.VNC.keymapCombo()]
                                },
                                {xtype:'displayfield',value: <?php echo json_encode(__('Default keymap only applies to servers that uses default keymap. Custom servers keymap will NOT be changed.')) ?>}
                            ]
                        },
                        {
                            xtype: 'fieldset',
                            title: <?php echo json_encode(__('Events log options')) ?>,
                            collapsible: true,
                            items: [
                                {
                                    xtype : 'compositefield',
                                    fieldLabel: <?php echo json_encode(__('Keep log data')) ?>,
                                    items : [
                                        {
                                            xtype: 'combo',
                                            mode: 'local',
                                            triggerAction: 'all',
                                            forceSelection: true,
                                            editable: false,
                                            name: 'eventlog_flush',
                                            hiddenName: 'eventlog_flush',
                                            displayField: 'name',
                                            valueField: 'value',
                                            store: new Ext.data.JsonStore({
                                                fields : ['name', 'value'],
                                                data   : [
                                                    {name : __('One day'),   value: '1'},
                                                    {name : __('Two days'),  value: '2'},
                                                    {name : __('Five days'), value: '5'},
                                                    {name : __('One week'), value: '7'},
                                                    {name : __('Two weeks'), value: '14'}
                                                ]
                                            })
                                    }]
                                },
                                {xtype:'displayfield',value: <?php echo json_encode(__('Number of days before flushing system events log data.')) ?>}
                            ]
                        }
                    ]//end 1tab items
                }//end first tab
                ,
                //second tab
                conn_tab
            ]}]
        };

        // apply config
        Ext.apply(this, Ext.apply(this.initialConfig, config));

        // call parent
        Setting.Form.superclass.initComponent.apply(this, arguments);


    } // eo function initComponent
    ,onRender:function() {
        // call parent
        Setting.Form.superclass.onRender.apply(this, arguments);

        // set wait message target
        this.getForm().waitMsgTarget = this.getEl();


    } // eo function onRender
    ,loadData:function(){
        //alert(this.fetch);
        this.load({
            url:this.url,
            waitMsg: <?php echo json_encode(__('Retrieving data...')) ?>,
            params:{params:Ext.encode(this.fetch)},
            failure:function(){
                this.disable();
            },
            success: function ( form, action ) {
                //var rec = action.result;
                //this.getForm().loadRecord(rec);
            },scope:this
        });

    }
    //redirect to ip
    ,redirect:function(ip){

        Ext.MessageBox.show({
                    title: <?php  echo json_encode(__('Please wait...')) ?>,
                    msg: String.format(<?php echo json_encode(__('Browser redirection in {0} secs...')) ?>,7),
                    width:300,
                    wait:true,
                    modal: false
        });

        var redirfunc = function(){
            var host = <?php echo json_encode($host); ?>;
            var split_host = host.split(':');
            if(split_host.length>1) window.location = 'http://'+ip+':'+split_host[1]+'/';
            else window.location = 'http://'+ip+'/';
        }

        var runTask = {run:redirfunc,interval:1000,repeat:1};

        var delay_t = new Ext.util.DelayedTask(function(){Ext.TaskMgr.start(runTask);});
        delay_t.delay(7000);



    }
    ,onSave:function(params){


        if (this.form.isValid()) {

            var alldata = this.form.getValues();

            var send_data = new Object();
            send_data = params;
            send_data['method'] = 'update';
            var networks = new Object();
            var cm_ip = alldata['network_'+this.devices[0]+'_ip'];

            
            for(var i=0,len = this.devices.length;i<len;i++){

                if(alldata['network_'+this.devices[i]+'_bootp']=='dhcp'){
                    networks[this.devices[i]] = {
                        'bootp':'dhcp',
                        /*'primarydns':alldata['network_staticdns_primarydns'],
                        'secondarydns':alldata['network_staticdns_secondarydns'],*/
                        'if':alldata['network_'+this.devices[i]+'_if']};
                }else{
                    networks[this.devices[i]] = {
                        'bootp':'none',
                        'ip':alldata['network_'+this.devices[i]+'_ip'],
                        'netmask':alldata['network_'+this.devices[i]+'_netmask'],
                        'gateway':alldata['network_'+this.devices[i]+'_gateway'],
                        'primarydns':alldata['network_primarydns'],
                        'secondarydns':alldata['network_secondarydns'],
                        'if':alldata['network_'+this.devices[i]+'_if']};
                }
            }

            
            var allRecords = [];
            for(var i=0,len = this.get_data.length;i<len;i++)
                allRecords.push({'param':this.get_data[i],'value':alldata[this.get_data[i]]});

           send_data['settings'] = Ext.encode(allRecords);
           send_data['networks'] = Ext.encode(networks);

            var conn = new Ext.data.Connection({
                listeners:{
                    // wait message.....
                    beforerequest:function(){
                        Ext.MessageBox.show({
                            title: <?php echo json_encode(__('Please wait...')) ?>,
                            msg: <?php echo json_encode(__('Updating system settings...')) ?>,
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
                url: this.url,
                params:send_data,
                timeout:10000,
                failure:function(response) {

                    console.log(response);

                    if((response.status==-1) && cm_ip){
                        this.redirect(cm_ip);
                        return;
                    }

                    var resp = Ext.decode(response.responseText);
                    
                    if(resp['action']=='check_nodes')
                    {

                        Ext.MessageBox.show({
                            title: <?php echo json_encode(__('Node(s) connectivity')) ?>,
                            msg: String.format('{0}<br><br>{1}<br><br>{2}'
                                    ,<?php echo json_encode(__('Some nodes reported down. If you proceed, the node(s) connection settings to CM will be outdated. Remember to verify node(s) connectivity to Central Management')) ?>
                                    ,resp['info']
                                    ,<?php echo json_encode(__('Are you sure you want to do this?')) ?>),
                            buttons: Ext.MessageBox.YESNOCANCEL,
                            fn: function(btn){

                                if(btn=='yes') this.onSave({force:true});

                            },
                            scope:this,
                            icon: Ext.MessageBox.WARNING
                        });

                    }             

                },
                // everything ok...
                success: function(resp,opt){

                    (this.ownerCt).fireEvent('settingSave',resp);



                },scope:this
            });// END Ajax request


        }else{
            //form not valid...
            var f = this.form.findInvalid()[0];
            if(f) f.ensureVisible();        

            Ext.MessageBox.show({
                title: <?php echo json_encode(__('Error!')) ?>,
                msg: <?php echo json_encode(__('Please fix the errors noted!')) ?>,
                buttons: Ext.MessageBox.OK,
                icon: Ext.MessageBox.WARNING
            });
        }

    }


}); // eo extend


Setting.Main = function(config) {

    Ext.apply(this, config);
    // main panel
    var win = Ext.getCmp('setting-main');
    var viewerSize = Ext.getBody().getViewSize();
    var windowHeight = viewerSize.height * 0.97;
    var windowWidth = viewerSize.width * 0.97;
    windowHeight = Ext.util.Format.round(windowHeight,0);
    windowHeight = (windowHeight > 400) ? 400 : windowHeight;

    windowWidth = Ext.util.Format.round(windowWidth,0);
    windowWidth = (windowWidth > 800) ? 800 : windowWidth;

    if(!win){

        //remove cookie if exists
        if(Ext.state.Manager.get('setting-main')) Ext.state.Manager.clear('setting-main');
        var vnckeyMap_combo = new Setting.VNC.keymapCombo();

        win = new Ext.Window({
                id:          'setting-main',
                title:       this.title,
                modal:       true,
                iconCls:     'icon-grid',
                width:       windowWidth,
                height:      windowHeight,                
              //  closeAction: 'hide',
                layout:      'border',
                border:       false,
                items:[
                    new Setting.Form({region:'center',url:<?php echo json_encode(url_for('setting/jsonSetting'))?>})
                ],
                tools:[{id:'help', qtip: __('Help'),handler:function(){View.showHelp({anchorid:'help-settings-main',autoLoad:{ params:'mod=setting'},title: <?php echo json_encode(__('Preferences Help')) ?>});}}]
                ,buttons:[{
                    text: __('Save'),
                    handler:function(){
                        this.ownerCt.ownerCt.get(0).onSave({});}
                    },
                    {text: __('Cancel'),
                     handler:function(){
                        win.close();}
                 }]
             ,listeners:{                 
                 'close':function(){Ext.EventManager.removeResizeListener(resizeFunc);}
             }
        });

        win.on({'show':function(){
                  this.items.get(0).loadData();
                },                
                'settingSave':function(resp){
                    var msg = <?php echo json_encode(__('Updated system settings' )) ?>;
                    win.close();
                    View.notify({html:msg});

                }
        });

        win.show();

    }else{

        win.setSize(windowWidth,windowHeight);
        win.center();
        win.show();
    }



    resizeFunc = function(){

        var viewerSize = Ext.getBody().getViewSize();
        var windowHeight = viewerSize.height * 0.97;
        var windowWidth = viewerSize.width * 0.97;

        windowHeight = Ext.util.Format.round(windowHeight,0);
        windowHeight = (windowHeight > 400) ? 400 : windowHeight;

        windowWidth = Ext.util.Format.round(windowWidth,0);
        windowWidth = (windowWidth > 800) ? 800 : windowWidth;

        win.setSize(windowWidth,windowHeight);
        win.center();
    };

    //on browser resize, resize window
    Ext.EventManager.onWindowResize(resizeFunc);

};

</script>
