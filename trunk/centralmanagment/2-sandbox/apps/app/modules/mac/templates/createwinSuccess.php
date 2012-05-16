<script>

    Ext.onReady(function(){

        // NOTE: This is an example showing simple state management. During development,
        // it is generally best to disable state management as dynamically-generated ids
        // can change across page loads, leading to unpredictable results.  The developer
        // should ensure that stable state ids are set for stateful components in real apps.
        //Ext.state.Manager.setProvider(new Ext.state.CookieProvider());

        Ext.QuickTips.init();
        this.cid = <?php echo $cid ?>;
        // create the data store to retrieve network data
        var store_networks_created = new Ext.data.JsonStore({
            proxy: new Ext.data.HttpProxy({url:'network/jsonGridNoPager?cid='+this.cid+'&query=""'}),
            totalProperty: 'total',
            root: 'data',
            fields: [{name:'vlan',mapping:'Vlan'},{name:'mac',mapping:'Mac'}],            
            remoteSort: false
        });

        store_networks_created.load();

        // create the Grid
        var grid_networks = new Ext.grid.GridPanel({
            store: store_networks_created,            
            columns: [
                {id:'mac', header: "MAC Address", width: 100, sortable: true, dataIndex: 'mac'},
                {header: "Network", width: 75, sortable: true, dataIndex: 'vlan'}
            ],
            viewConfig:{
                emptyText: __('Empty!'),  //  emptyText Message
                forceFit:true
            },
            tools:[{id:'refresh',
                    on:{
                        click: function(){store_networks_created.reload();}
                    }
                   }],
            stripeRows: true,
            autoExpandColumn: 'mac',
            height:180,                     
            border:true,
            title: <?php echo json_encode(__('Networks')) ?>,
            loadMask: {msg: <?php echo json_encode(__('Retrieving data...')) ?>}
        });




        var query = {'in_use':0};
        // create the data store showing free available macs
        var store_unused_macs = new Ext.data.JsonStore({
            url:'mac/jsonGridAll',
            baseParams: {'query': Ext.encode(query)},            
            totalProperty: 'total',
            root: 'data',
            fields: [{name:'mac',mapping:'Mac'}],
            remoteSort: false
        });

        store_unused_macs.load();

        // create the Grid
        var grid_unused_macs = new Ext.grid.GridPanel({
            store: store_unused_macs,
            columns: [
                {id:'mac', header: "MAC Address", width: 100, sortable: true, dataIndex: 'mac'}
            ],
            viewConfig:{
                emptyText: __('Empty!'),  //  emptyText Message
                forceFit:true
            },
            stripeRows: true,
            autoExpandColumn: 'mac',
            height:180,
            tools:[{id:'refresh',
                    on:{
                        click: function(){store_unused_macs.reload();}
                    }
                   }],
            autoScroll: true,
            stripeRows: true,            
            title: <?php echo json_encode(__('Available MAC Addresses')) ?>,
            loadMask: {msg: <?php echo json_encode(__('Retrieving data...')) ?>}
        });        

        var nicForm = new Ext.FormPanel({
            labelWidth: 80, // label settings here cascade unless overridden
            frame:true,
         //   bodyStyle:'padding:5px 5px 0',
            width: 385,
            items: [{
                    xtype:'fieldset',
                    checkboxToggle:true,
                    title: <?php echo json_encode(__('MAC pool')) ?>,
                    autoHeight:true,
                    defaults     : {
                        width: 385
                        //labelStyle : 'width:100px;'
                    },
                    collapsed: false,
                    items :[{
                            height:30,
                            layout:'column',
                            layoutConfig: {                                
                                fitHeight: true,                                
                                split: true
                            },
                            items:[{
                                    columnWidth:.31,
                                    layout:'form',

                                    items:[{xtype:'textfield',fieldLabel: <?php echo json_encode(__('Initial MAC')) ?>,width:22,
                                            name: 'oct1',
                                            disabled:true,
                                            value:<?php echo json_encode(sprintf('%02x',sfConfig::get('app_mac_default_first_octect'))); ?>,
                                            vtype:'oct_valid'}]
                                },
                                {
                                    columnWidth:.11,
                                    layout:'form',
                                    labelWidth: 4,
                                    items:[{xtype:'textfield',width:22,
                                            name: 'oct2',
                                            disabled:true,
                                            value:<?php echo json_encode(sprintf('%02x',sfConfig::get('app_mac_default_second_octect'))); ?>,
                                            vtype:'oct_valid'}]
                                },
                                {
                                    columnWidth:.11,
                                    layout:'form',
                                    labelWidth: 4,
                                    items:[{xtype:'textfield',width:22,
                                            name: 'oct3',
                                            disabled:true,
                                            value:<?php echo json_encode(sprintf('%02x',sfConfig::get('app_mac_default_third_octect'))); ?>,
                                            vtype:'oct_valid'}]
                                },
                                {
                                    columnWidth:.11,
                                    layout:'form',
                                    labelWidth: 4,
                                    items:[{xtype:'textfield',width:22,
                                            name: 'oct4',
                                            value:'00',
                                            vtype:'oct_valid'}]
                                },
                                {
                                    columnWidth:.11,
                                    layout:'form',
                                    labelWidth: 4,
                                    items:[{xtype:'textfield',width:22,
                                            name: 'oct5',
                                            value:'00',
                                            vtype:'oct_valid'}]
                                },
                                {
                                    columnWidth:.11,
                                    layout:'form',
                                    labelWidth: 4,
                                    items:[{xtype:'textfield',width:22,
                                            name: 'oct6',
                                            value:'00',
                                            vtype:'oct_valid'}]
                                }
                            ]// end column layout
                        }
                        ,
                        {xtype:'numberfield',
                            name       : 'pool_size',                            
                            fieldLabel : <?php echo json_encode(__('MAC pool size')) ?>,
                            minValue:1,                          
                            maxValue: 1000,
                            width:30,
                            allowBlank : false,
                            vtype:'pool_valid',
                            listeners:{invalid:function(){
                                    this.ownerCt.btnGenMac.disable();
                                },valid:function(){
                                    this.ownerCt.btnGenMac.enable();
                                }}
                        }
                        ,
                        {
                            xtype:'button',
                            ref:'btnGenMac',
                            text: <?php echo json_encode(__('Generate!')) ?>,
                            name:'btnGenMac',
                            disabled:true,
                            isFormField:true,
                            width:30,
                            labelSeparator: '',
                            handler:function(){                                
                                
                                if(nicForm.form.isValid()){

                                    var nicValues = nicForm.form.getValues();
                                    var size = nicValues['pool_size'];
                                    var octects = {'oct4':nicValues['oct4'],
                                            'oct5':nicValues['oct5'],
                                            'oct6':nicValues['oct6']}

                                    var conn = new Ext.data.Connection({
                                        listeners:{
                                            // wait message.....
                                            beforerequest:function(){
                                                Ext.MessageBox.show({
                                                    title: <?php echo json_encode(__('Please wait...')) ?>,
                                                    msg: <?php echo json_encode(__('Generating MAC pool...')) ?>,
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
                                        url: 'mac/JsonGeneratePool',
                                        params: {'size': size,'octects':Ext.encode(octects)},
                                        scope: this,
                                        success: function(resp,options) {

                                            store_unused_macs.reload();

                                        }
                                        ,failure: function(resp,opt) {                                            
                                            Ext.Msg.alert(<?php echo json_encode(__('Error!')) ?>,<?php echo json_encode(__('Unable to generate MAC pool')) ?>);
                                        }
                                    
                                    }); // END Ajax request

                                }
                                else{
                                    Ext.MessageBox.alert(<?php echo json_encode(__('Error!')) ?>, <?php echo json_encode(__('Please fix the errors noted!')) ?>);
                                }

                            }


                    }
                      //  btnGenMac
                        
                    ]}// end fieldset
                ,
               
                {
                layout:'table',                
                defaults: {
                    // applied to each contained panel                    
                    bodyStyle:'padding:10px'
                },
                layoutConfig:{columns:2},
                items:[{width:240,items:grid_networks},
                       {width:230,items:grid_unused_macs}]
                }
            ],

            buttons: [{text: __('Close'),
                    handler:function(){

                        win.close();
                        
                    }}]
        });



        var win = new Ext.Window({
            // id:'mac-pool-win',
            title: <?php echo json_encode(__('MAC Pool Management')) ?>,
            width:500,
            height:430,
            iconCls: 'icon-window',
            shim:false,
            animCollapse:false,
            //  closeAction:'hide',
            border:false,
            constrainHeader:true,
            modal:true,
            defaults:{autoScroll: true},
            layout: 'fit',
            items: [nicForm]
            ,tools: [{
                id:'help',
                qtip: __('Help'),
                handler:function(){
                    View.showHelp({
                        anchorid:'help-vlan-mac',
                        autoLoad:{ params:'mod=vlan'},
                        title: <?php echo json_encode(__('MAC Pool Management Help')) ?>
                    });
                }
            }]
        });
       
        win.show();

    });



</script>
