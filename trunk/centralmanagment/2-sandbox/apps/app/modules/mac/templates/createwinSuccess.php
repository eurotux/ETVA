<script>

    Ext.onReady(function(){

        // NOTE: This is an example showing simple state management. During development,
        // it is generally best to disable state management as dynamically-generated ids
        // can change across page loads, leading to unpredictable results.  The developer
        // should ensure that stable state ids are set for stateful components in real apps.
        Ext.state.Manager.setProvider(new Ext.state.CookieProvider());

        Ext.QuickTips.init();




        // create the data store to retrieve network data
        var store_networks_created = new Ext.data.JsonStore({
            proxy: new Ext.data.HttpProxy({url:'network/jsonGridNoPager'}),
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
                {id:'mac', header: "MAC Address", width: 75, sortable: true, dataIndex: 'mac'},
                {header: "VLAN", width: 75, sortable: true, dataIndex: 'vlan'}
            ],
            stripeRows: true,
            autoExpandColumn: 'mac',
            height:180,
            viewConfig:{forceFit:true},
            border:true,
            title:'Networks',
            loadMask: {msg: 'Retrieving info...'}
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
                {id:'mac', header: "MAC Address", width: 75, sortable: true, dataIndex: 'mac'}

            ],
            stripeRows: true,
            autoExpandColumn: 'mac',
            height:180,
            width:200,
            autoWidth: true,
    autoScroll: true,
    stripeRows: true,
            viewConfig:{forceFit:true},
            title:'Available MAC Addresses',
            loadMask: {msg: 'Retrieving info...'}
        });



        Ext.apply(Ext.form.VTypes, {

            mac_valid : function(val, field) {
                if (val.length > 2) {
                    field.setValue(val.substr(0,2));
                }
                return true;
            }
        });


        var nicForm = new Ext.FormPanel({
            labelWidth: 75, // label settings here cascade unless overridden
            frame:true,
         //   bodyStyle:'padding:5px 5px 0',
            width: 350,
            items: [{
                    xtype:'fieldset',
                    checkboxToggle:true,
                    title: 'MAC pool',
                    autoHeight:true,
                    defaults     : {
                        width: 350,                        
                        labelStyle : 'width:100px;'
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

                                    items:[{xtype:'textfield',vtype:'mac_valid',fieldLabel: 'Initial MAC',width:22,
                                            name: 'mac1',
                                            disabled:true,
                                            value:'00'}]
                                },
                                {
                                    columnWidth:.11,
                                    layout:'form',
                                    labelWidth: 4,
                                    items:[{xtype:'textfield',width:22,
                                            name: 'mac2',
                                            disabled:true,
                                            value:'30',
                                            vtype:'mac_valid'}]
                                },
                                {
                                    columnWidth:.11,
                                    layout:'form',
                                    labelWidth: 4,
                                    items:[{xtype:'textfield',width:22,
                                            name: 'mac3',
                                            disabled:true,
                                            value:'E3',
                                            vtype:'mac_valid'}]
                                },
                                {
                                    columnWidth:.11,
                                    layout:'form',
                                    labelWidth: 4,
                                    items:[{xtype:'textfield',width:22,
                                            name: 'mac4',
                                            value:'00',
                                            vtype:'mac_valid'}]
                                },
                                {
                                    columnWidth:.11,
                                    layout:'form',
                                    labelWidth: 4,
                                    items:[{xtype:'textfield',width:22,
                                            name: 'mac5',
                                            value:'00',
                                            vtype:'mac_valid'}]
                                },
                                {
                                    columnWidth:.11,
                                    layout:'form',
                                    labelWidth: 4,
                                    items:[{xtype:'textfield',width:22,
                                            name: 'mac6',
                                            value:'00',
                                            vtype:'mac_valid'}]
                                }
                            ]// end column layout
                        }
                        ,
                        {xtype:'numberfield',
                            name       : 'pool_size',                            
                            fieldLabel : 'Mac address pool',
                            minValue:1,                            
                            width:20,                            
                            allowBlank : false,
                            vtype:'mac_valid',
                            listeners:{invalid:function(){
                                    Ext.getCmp('btnGenMac').disable();
                                },valid:function(){
                                    Ext.getCmp('btnGenMac').enable();
                                }}
                        }
                        ,
                        {
                            xtype:'button',
                            id:'btnGenMac',
                            text: 'Generate!',
                            name:'btnGenMac',
                            disabled:true,
                            isFormField:true,
                            width:30,
                            labelSeparator: '',
                            handler:function(){

                                var size = nicForm.form.findField('pool_size').getValue();

                                var conn = new Ext.data.Connection({
                                    listeners:{
                                        // wait message.....
                                        beforerequest:function(){
                                            Ext.MessageBox.show({
                                                title: 'Please wait',
                                                msg: 'Generating mac pool...',
                                                width:300,
                                                wait:true,
                                                modal: false
                                            });
                                        },// on request complete hide message
                                        requestcomplete:function(){Ext.MessageBox.hide();}}
                                });// end conn

                                conn.request({
                                    url: 'mac/JsonGeneratePool',
                                    params: {'size': size},
                                    scope: this,
                                    success: function(resp,options) {

                                        store_unused_macs.reload();

                                    }
                                    ,
                                    failure: function(resp,opt) {
                                        Ext.Msg.alert('Error','Unable to generate mac pool');
                                    }
                                }); // END Ajax request

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
                items:[{items:
                grid_networks},
                {items:grid_unused_macs}
                ]
                }

            ],

            buttons: [{text: 'Close',
                    handler:function(){

                        win.close();
                        
                    }}]
        });



        var win = new Ext.Window({
            // id:'mac-pool-win',
            title: 'Network Interface Card Management',
            width:465,
            height:430,
            iconCls: 'icon-window',
            shim:false,
            animCollapse:false,
            //  closeAction:'hide',
            border:false,
            constrainHeader:true,
            defaults:{autoScroll: true},
            layout: 'fit',
            items: [nicForm]
        });
       
        win.show();

    });



</script>