<?php include_partial("script/CardLayout.js"); ?>
<?php include_partial("script/Wizard.js"); ?>
<?php include_partial("script/Header.js"); ?>
<?php include_partial("script/West.js"); ?>
<?php include_partial("script/Card.js"); ?>
<script>
        
 ETFW_network_wizard = function() {


    Ext.QuickTips.init();
    Ext.form.Field.prototype.msgTarget = 'qtip';


    var ip_regexp = /^(([1-9][0-9]{0,2})|0)\.(([1-9][0-9]{0,2})|0)\.(([1-9][0-9]{0,2})|0)\.(([1-9][0-9]{0,2})|0)$/;
    // Add the additional 'advanced' VTypes
    Ext.apply(Ext.form.VTypes, {

        ip_addr : function(val, field) {

                return ip_regexp.test(val);
        },
        ip_addrText : 'IP wrong format'
    });

    var build_interface_fields = function (interface,value){

        var static_radio = new Ext.form.Radio({
                boxLabel: 'Static config', width:90,
                name: 'address_source',fieldLabel:'',hideLabel:true,
                inputValue: 'static'
        });
            
        var static_source = new Ext.Panel({
               border:false,
               items:[{
                        layout:'table',
                        frame:true,
                        //   width:300,
                        layoutConfig: {columns:2},
                        items:[
                                {
                                labelAlign:'left',
                                layout:'form',
                                items:static_radio
                                },
                                {
                                // labelAlign:'top',
                                labelWidth:70,
                                //   width:150,
                                layout:'form',
                                items:[
                                    new Ext.form.TextField({
                                            fieldLabel: 'IP Address',
                                            name: 'address',
                                            maxLength: 15,
                                            width:100
                                    }),
                                    new Ext.form.TextField({
                                            fieldLabel: 'Netmask',
                                            name: 'netmask',
                                            maxLength: 15,
                                            width:100
                                    }),
                                    new Ext.form.TextField({
                                            fieldLabel: 'Broadcast',
                                            name: 'broadcast',
                                            maxLength: 15,
                                            width:100
                                    })
                                    
                                 ]
                                }
                        ]
                }]
        });
        
        var dhcp_source = new Ext.form.Radio({style:'margin-left:5px',
                boxLabel: 'From DHCP', width:90, name: 'address_source',
                fieldLabel:'',hideLabel:true, inputValue: 'dhcp'
        });

        var intfname = new Ext.form.TextField({
        fieldLabel: 'Interface',
        name: 'interface',
        readOnly:true,
        disabled:true,
        editable:false,
        value: value,
        allowBlank: false,
        width:100});

        var fields = [];
        switch(interface){
              case 'wan':                                                                     
                    (static_source.get(0)).get(1).add(new Ext.form.TextField({
                                            fieldLabel: 'Gateway',
                                            name: 'gateway',
                                            maxLength: 15,
                                            width:100
                                    }));
                    fields = [intfname,
                    new Ext.form.FieldSet({
                    style:'margin-top:10px',
                    title: 'Address source',items:[dhcp_source,static_source]})];
                    dhcp_source.setValue(true);
                    break;
                default:                    
                    fields = [intfname,
                    new Ext.form.FieldSet({
                    style:'margin-top:10px',
                    title: 'Address source',items:[static_source]})];
                    static_radio.setValue(true);
                    (static_source.find('name','address'))[0].allowBlank = false;

                    break;

        }
        return fields;
        
    }; // end build_interface_fields


    var build_address_ranges = function (interface){

        /*
         * address ranges grid
         *
         */

        var address_ranges_cm = new Ext.grid.ColumnModel({
            // specify any defaults for each column
            defaults: {
                sortable: true // columns are not sortable by default
            },
            columns: [
                {
                    header: 'From address',
                    dataIndex: 'from_range',
                    width: 100,
                    // use shorthand alias defined above
                    editor: new Ext.form.TextField({
                        allowBlank: false,
                        selectOnFocus:true,
                        vtype:'ip_addr'
                    })
                }, {
                    header: 'To address',
                    dataIndex: 'to_range',
                    width: 100,
                    align: 'right',
                    editor: new Ext.form.TextField({
                        allowBlank: false,
                        selectOnFocus:true,
                        vtype:'ip_addr'
                    })
                }
            ]
        });

        var address_ranges_store = new Ext.data.Store({
            reader: new Ext.data.JsonReader({
                root:'range',
                fields:['from_range','to_range']
            })
        });

        var range_address_status = new Ext.form.TextField({
                    name:'network_status',
                    cls: 'nopad-border',
                    readOnly:true,
                    width:200,
                    labelSeparator: '',
                   // labelWidth:50,
                    value : 'Fill grid with data...',
                    invalidText : 'Fill grid with data...',
                    allowBlank : false,
                    validator  : function(v){
                        //var t = /Tou^[a-zA-Z_\-]+$/;
                        return v!='Fill all fields...';
                       // return t.test(v);
                    }
        });

        // create the grid
        var address_ranges_grid = new Ext.grid.EditorGridPanel({
            store: address_ranges_store,
            id:interface+'_address_ranges_dhcp',
            cm: address_ranges_cm,
            autoHeight: true,
            border:true,
            //isFormField:true,
            //width:250,
            //   layout:'fit',
            viewConfig:{
                forceFit:true,
                emptyText: 'Empty!',  //  emptyText Message
                deferEmptyText:false
            },
            sm: new Ext.grid.RowSelectionModel({
                singleSelect: true,
                moveEditorOnEnter:false
            }),
           // plugins: [defaultEditor],
            tbar: [{
                    text: 'Add address range',
                    iconCls:'add',
                    handler : function(){
                        // access the Record constructor through the grid's store
                        var Range = address_ranges_grid.getStore().recordType;
                        var r = new Range({
                            from_range: '0.0.0.0',
                            to_range: '0.0.0.0'
                        });

                        address_ranges_grid.stopEditing();
                        address_ranges_store.insert(0, r);
                        address_ranges_grid.startEditing(0,0);

                    }
                    }
                ,{
                    ref: '../removeBtn',
                    text: 'Remove address range',
                    iconCls:'remove',
                    disabled: true,
                    handler: function(){
                        //defaultEditor.stopEditing();
                        var s = address_ranges_grid.getSelectionModel().getSelections();
                        for(var i = 0, r; r = s[i]; i++)
                            address_ranges_store.remove(r);

                        address_ranges_grid.fireEvent('afteredit');
    //                        var rows = address_ranges_store.getCount();
    //                        if(rows==0)
    //                            range_address_status.setValue("Fill grid with data...");
    //                        else
    //                            range_address_status.setValue("You have "+rows+" address range(s) inserted");


                    }
            }],
            listeners:{
                afteredit:function(){
                            var cols = this.colModel.getColumnCount();
                            var rows = this.store.getCount();

                            if(rows==0){
                                range_address_status.setValue("Fill grid with data...");
                                return false;
                            }

                            var r, c;
                            var valid = true;
                            for(r = 0; r < rows; r++) {
                                for(c = 1; c < cols; c++) {
                                    valid = this.isCellValid(c, r);
                                    if(!valid) {
                                        break;
                                    }
                                }
                                if(!valid) {
                                    break;
                                }
                            }
                            if(!valid){
                                range_address_status.setValue("Fill all fields...");
                            }
                            //
                            else{

                               range_address_status.setValue("You have "+rows+" address range(s) inserted");
                            }
                            return valid;
                }
            }
        });

        Ext.apply(address_ranges_grid, {

            isCellValid:function(col, row) {

                var record = this.store.getAt(row);
                if(!record) {
                    return true;
                }

                var field = this.colModel.getDataIndex(col);

                if(!record.data[field]) return false;
                return true;
            }

        });

        address_ranges_grid.getSelectionModel().on('selectionchange', function(sm){
            address_ranges_grid.removeBtn.setDisabled(sm.getCount() < 1);
        });



        return [{
                layout:'table',
                frame:true,
                layoutConfig: {columns:2},
                items:[
                    {
                        labelAlign:'left',
                        layout:'form',
                        items:[{fieldLabel:'Address ranges'}]
                    },
                    {
                        labelAlign:'left',
                        layout:'fit',
                        // layout:'form',
                    //    bodyStyle: 'padding-bottom:10px;',
                        items: address_ranges_grid
                    }
                ]
                }
                ,new Ext.form.FieldSet({
                labelWidth: 1,
                style:'padding-left:0px;border:none;',
                autoHeight:true,
                items:[range_address_status]
        })];
        
    }; // end function


            


    this.wan_card_fields = build_interface_fields('wan',<?php echo json_encode(sfConfig::get("mod_etfw_interface_wan")) ?>);// end fieldset
    this.lan_card_fields = [build_interface_fields('lan',<?php echo json_encode(sfConfig::get("mod_etfw_interface_lan")) ?>)
                            ,                        
                            {xtype:'radio',boxLabel: 'Yes', name: 'etfw_lan_dhcp', labelStyle : 'width:200px;',labelSeparator:'',fieldLabel: 'Configure LAN interface DHCP now?', inputValue:'1'},
                            {xtype:'radio',boxLabel: 'No', name: 'etfw_lan_dhcp', labelStyle : 'width:200px;',inputValue:'0', checked: true},
                            {xtype:'radio',boxLabel: 'Yes', name: 'etfw_lan_squid', labelStyle : 'width:200px;',labelSeparator:'',fieldLabel: 'Configure LAN interface SQUID now?', inputValue:'1'},
                            {xtype:'radio',boxLabel: 'No', name: 'etfw_lan_squid', labelStyle : 'width:200px;',inputValue:'0', checked: true}

                            
                        ];// end fieldset

    this.dmz_card_fields = [build_interface_fields('dmz',<?php echo json_encode(sfConfig::get("mod_etfw_interface_dmz")) ?>)
                            ,                        
                            {xtype:'radio',boxLabel: 'Yes', name: 'etfw_dmz_dhcp', labelStyle : 'width:200px;',labelSeparator:'',fieldLabel: 'Configure DMZ interface DHCP now?', inputValue:'1'},
                            {xtype:'radio',boxLabel: 'No', name: 'etfw_dmz_dhcp', labelStyle : 'width:200px;',inputValue:'0', checked: true},
                            {xtype:'radio',boxLabel: 'Yes', name: 'etfw_dmz_squid', labelStyle : 'width:200px;',labelSeparator:'',fieldLabel: 'Configure DMZ interface SQUID now?', inputValue:'1'},
                            {xtype:'radio',boxLabel: 'No', name: 'etfw_dmz_squid', labelStyle : 'width:200px;',inputValue:'0', checked: true}
                        ];// end fieldset


    var viewerSize = Ext.getBody().getViewSize();
    var windowHeight = viewerSize.height * 0.95;
    windowHeight = Ext.util.Format.round(windowHeight,0);

    var wizard = new Ext.ux.Wiz({
        border:true,
        title : 'Network Setup Wizard',

        headerConfig : {
            title : 'Create new network configuration'
        },
        width:620,
        height:500,

        westConfig : {
            width : 150
        },

        cardPanelConfig : {
            defaults : {
                baseCls    : 'x-small-editor',
                bodyStyle  : 'border:none;padding:15px 15px 15px 15px;background-color:#F6F6F6;',
                border     : false
            }
        },

        cards : [
            // card with welcome message
            new Ext.ux.Wiz.Card({
                title : 'Welcome',
                defaults     : {
                    labelStyle : 'font-size:11px;width:140px;'
                },
                items : [{
                        border    : false,
                        bodyStyle : 'background:none;',
                        html      : 'Welcome to the network setup wizard.<br><br>'+
                            'All the actual configuration will be lost. It should be applyed on first time setup.'+
                            '<br>Follow the steps to setup initial network<br/><br/>'

                    }]
            }),
            // network topology
            new Ext.ux.Wiz.Card({
                title        : 'Network topology',
                monitorValid : true,
                autoScroll:true,
                defaults     : {
                    labelStyle : 'font-size:11px;width:140px;'
                },

                items : [{
                        border    : false,
                        bodyStyle : 'background:none;',
                        html      : 'Please choose network topology.'
                    },
//                        new Ext.form.Hidden({
//                            name:'node_id',
//                            value:node_id})
//                        ,
                    new Ext.form.FieldSet({
                        cls:'fieldset-top-sp',
                        title: 'Topology',
                        collapsible: false,
                        autoHeight:true,
                        defaultType: 'radio',
                        labelWidth:10,
                        //layout:'fit',
                        //autoScroll:true,
                        items :[
                            {
                                checked:true,
                                fieldLabel: '',
                                hideLabel:true,
                                labelSeparator: '',
                                boxLabel: 'ETFW',
                                name: 'etfw_tp',
                                inputValue: 'etfw_only'


                            }
                            ,{
                                isFormField:false ,fieldLabel:'',labelWidth:10
                                ,xtype:'box'
                                ,autoEl:{
                                    tag:'div',
                                    children:[{tag:'img'
                                            ,qtip:'You can also have a tooltip on the image'
                                            ,src:'/images/network/etfw_only_tp.png'
                                            ,style:'margin:0px 0px 10px 10px'}]
                                }
                            }
                            ,{
                                fieldLabel: '',
                                hideLabel:true,
                                labelSeparator: '',
                                boxLabel: 'ETFW+DMZ',
                                name: 'etfw_tp',
                                inputValue: 'etfw_dmz'

                            }
                            ,{
                                isFormField:false ,fieldLabel:'',labelWidth:10
                                ,xtype:'box'
                                ,autoEl:{
                                    tag:'div',
                                    children:[{tag:'img'
                                            ,qtip:'You can also have a tooltip on the image'
                                            ,src:'/images/network/etfw_dmz_tp.png'
                                            ,style:'margin:0px 0px 0px 10px'}]
                                }
                            }
                        ]
                    })

                ],
                listeners: {
                    nextclick:function(card){

                                var wizData = wizard.getWizardData();
                                var cardData = wizData[card.getId()];
                                var cp = wizard.cardPanel;
                                switch(cardData['etfw_tp']){
                                    case 'etfw_only' : (cp.get('etfw-network-wiz_dmz')).setSkip(true);
                                                       (cp.get('etfw-network-wiz_dmz_dhcp')).setSkip(true);
                                                       (cp.get('etfw-network-wiz_dmz_squid')).setSkip(true);
                                                     break;
                                    case 'etfw_dmz' : (cp.get('etfw-network-wiz_dmz')).setSkip(false);
                                                      (cp.get('etfw-network-wiz_dmz_dhcp')).setSkip(false);
                                                      (cp.get('etfw-network-wiz_dmz_squid')).setSkip(false);
                                                     break;
                                           default : break;
                                }
                    }
                }
            }),
            // WAN interface
            new Ext.ux.Wiz.Card({
                title        : 'WAN interface',
                monitorValid : true,
                defaults     : {
                    labelStyle : 'font-size:11px;',
                    bodyStyle : 'padding:10px;background-color:#F6F6F6;'
                },
                items : [{
                        border    : false,frame:false,
                        bodyStyle : 'background:none;padding-bottom:30px;',
                        html      : 'Specify the external (WAN) interface address.'
                    },
                    this.wan_card_fields
                ]
            }),
            /*
             *
             * LAN interface
             *
             */
            new Ext.ux.Wiz.Card({
                title        : 'LAN interface',
                id: 'etfw-network-wiz_lan',
                monitorValid : true,
                
                defaults     : {
                    labelStyle : 'font-size:11px;',
                    bodyStyle: 'padding:10px;'                
                },
                items : [{
                        border    : false,
                        bodyStyle : 'background:none;padding-bottom:30px;',
                        html      : 'Specify the LAN interface address.'
                        },
                        this.lan_card_fields
                        ],
                listeners:{
                    nextclick:function(card){

                                var wizData = wizard.getWizardData();
                                var cardData = wizData[card.getId()];
                                var cp = wizard.cardPanel;
                                switch(cardData['etfw_lan_dhcp']){
                                    case '0' : (cp.get('etfw-network-wiz_lan_dhcp')).setSkip(true);
                                               break;
                                    case '1' : var lan_dhcp_card = cp.get('etfw-network-wiz_lan_dhcp');
                                               lan_dhcp_card.setSkip(false);
                                               var addr_field = (lan_dhcp_card.find('name','lan_address_dhcp'))[0];
                                               addr_field.setValue(cardData['address']);
                                               break;
                                    default : break;
                                }

                                switch(cardData['etfw_lan_squid']){
                                    case '0' : (cp.get('etfw-network-wiz_lan_squid')).setSkip(true);
                                               break;
                                    case '1' : var lan_squid_card = cp.get('etfw-network-wiz_lan_squid');
                                               lan_squid_card.setSkip(false);
                                               break;
                                    default : break;
                                }

                    }
                }
            }),
            /*
             *
             * LAN DHCP
             *
             */
            new Ext.ux.Wiz.Card({
                id: 'etfw-network-wiz_lan_dhcp',
                title        : 'LAN DHCP',
                monitorValid : true,
                autoScroll:true,
                defaults     : {
                    labelStyle : 'font-size:11px;'
                   // ,bodyStyle: 'padding:10px;'
                },
                //    width:300,

                items : [{
                        border    : false,
                        bodyStyle : 'background:none;padding-bottom:30px;',
                        html      : 'Configure DHCP on LAN interface.'
                    },
                    new Ext.form.TextField({
                        fieldLabel: 'Network address',
                        name: 'lan_address_dhcp',
                        readOnly:true,
                        disabled:true,
                        editable:false,
                        allowBlank: false
                        ,width:100
                    })
                    ,{xtype:'spacer',height:20}
                    ,build_address_ranges('lan')
                    ]
            }),
             /*
             *
             * LAN SQUID
             *
             */
            new Ext.ux.Wiz.Card({
                id: 'etfw-network-wiz_lan_squid',
                title        : 'LAN SQUID',
                monitorValid : true,
                autoScroll:true,
                defaults     : {
                    labelStyle : 'font-size:11px;'
                   // ,bodyStyle: 'padding:10px;'
                },
                //    width:300,

                items : [{
                        border    : false,
                        bodyStyle : 'background:none;padding-bottom:30px;',
                        html      : 'Configure SQUID on LAN.'
                    }
                    ]
            }),
            // DMZ interface
            new Ext.ux.Wiz.Card({
                id: 'etfw-network-wiz_dmz',
                title        : 'DMZ interface',
                monitorValid : true,

                defaults     : {
                    labelStyle : 'font-size:11px;',
                    bodyStyle: 'padding:10px;'
                },
                //    width:300,

                items : [{
                        border    : false,
                        bodyStyle : 'background:none;padding-bottom:30px;',
                        html      : 'Specify the DMZ interface address.'
                    },
                    this.dmz_card_fields
                    ],
                listeners:{
                    nextclick:function(card){

                                var wizData = wizard.getWizardData();
                                var cardData = wizData[card.getId()];
                                var cp = wizard.cardPanel;
                                switch(cardData['etfw_dmz_dhcp']){
                                    case '0' : (cp.get('etfw-network-wiz_dmz_dhcp')).setSkip(true);
                                               break;
                                    case '1' : var dmz_dhcp_card = cp.get('etfw-network-wiz_dmz_dhcp');
                                               dmz_dhcp_card.setSkip(false);                                               
                                               var addr_field = (dmz_dhcp_card.find('name','dmz_address_dhcp'))[0];
                                               addr_field.setValue(cardData['address']);
                                               break;
                                    default : break;
                                }

                                switch(cardData['etfw_dmz_squid']){
                                    case '0' : (cp.get('etfw-network-wiz_dmz_squid')).setSkip(true);
                                               break;
                                    case '1' : var dmz_squid_card = cp.get('etfw-network-wiz_dmz_squid');
                                               dmz_squid_card.setSkip(false);
                                               break;
                                    default : break;
                                }

                    }
                }
            }),
            /*
             *
             * DMZ DHCP
             *
             */
            new Ext.ux.Wiz.Card({
                id: 'etfw-network-wiz_dmz_dhcp',
                title        : 'DMZ DHCP',
                monitorValid : true,
                autoScroll:true,
                defaults     : {
                    labelStyle : 'font-size:11px;'
                   // ,bodyStyle: 'padding:10px;'
                },
                //    width:300,

                items : [{
                        border    : false,
                        bodyStyle : 'background:none;padding-bottom:30px;',
                        html      : 'Configure DHCP on DMZ interface.'
                    },
                    new Ext.form.TextField({
                        fieldLabel: 'Network address',
                        name: 'dmz_address_dhcp',
                        readOnly:true,
                        disabled:true,
                        editable:false,
                        allowBlank: false
                        ,width:100
                    })
                    ,{xtype:'spacer',height:20}
                    ,build_address_ranges('dmz')]
            }),
            /*
             *
             * DMZ SQUID
             *
             */
            new Ext.ux.Wiz.Card({
                id: 'etfw-network-wiz_dmz_squid',
                title        : 'DMZ SQUID',
                monitorValid : true,
                autoScroll:true,
                defaults     : {
                    labelStyle : 'font-size:11px;'
                   // ,bodyStyle: 'padding:10px;'
                },
                //    width:300,

                items : [{
                        border    : false,
                        bodyStyle : 'background:none;padding-bottom:30px;',
                        html      : 'Configure SQUID on DMZ.'
                    }
                    ]
            }),
            // finish card with finish-message
            new Ext.ux.Wiz.Card({
          //      id:'8',
                title        : 'Finished!',
                monitorValid : true,
                items : [{
                        border    : false,
                        bodyStyle : 'background:none;',
                        html      : 'Thank you!. Your data has been collected.<br>'+
                            'When you click on the "finish" button, the virtual server will be created.<br />'
                    }]
            })

        ],
        listeners: {
            finish: function() { saveConfig( this.getWizardData() ) }
        }
    });



        function vmCreate(server) {

            var name = server[1]['vm_name'];
            var storage = server[4]['vm_lv'];
            var mem = server[2]['vm_memory'];
            var cpuset = server[3]['vm_cpu'];

            var nettype = server[5]['vm_nettype'];

            if(!server[7]['vm_inst_local'])
                var location = server[7]['vm_inst_remote'];
            else
                var location = server[7]['vm_inst_local'];


            var networks=[];

            var nets_store = mac_vlan_grid.getStore();


            var i = 0;
            nets_store.each(function(f){
                //  var field = grid.colModel.getDataIndex(col);
                //           ed.field.setValue(record.data[field]);
                var data = f.data;

                networks.push({
                    'port':i,
                    'vlan':data['vlan'],
                    'mac':data['mac']
                });
                i++;

            });



            var insert_model = {
                'lv':storage,
                'networks': networks,
                'nettype':nettype,
                'name':name,
                'ip':"000.000.000.000",
                'mem':mem,
                'cpuset':cpuset,
                'location':location};
            //   "etva_server[mac_addresses]":macs};

            var conn = new Ext.data.Connection({
                listeners:{
                    // wait message.....
                    beforerequest:function(){
                        Ext.MessageBox.show({
                            title: 'Please wait',
                            msg: 'Storing in db...',
                            width:300,
                            wait:true,
                            modal: false
                        });
                    },// on request complete hide message
                    requestcomplete:function(){Ext.MessageBox.hide();}}
            });// end conn

            conn.request({
                url: <?php echo json_encode(url_for('server/jsonCreate',false)); ?>,
                params: {'nid':node_id,'server': Ext.encode(insert_model)},
                title: name,
                scope: this,
                success: function(resp,opt) {
                    var response = Ext.util.JSON.decode(resp.responseText);
                    var sid = response['response']['insert_id'];
                    var tree_id = 's'+ sid;


                    Ext.ux.Logger.info(response['response']['msg']);

                    nodesPanel.addNode({id: tree_id,leaf:true,text: name,
                        url: <?php echo json_encode(url_for('server/view?id=',false)) ?>+sid});

                },
                failure: function(resp,opt) {
                    var response = Ext.util.JSON.decode(resp.responseText);

                    Ext.ux.Logger.error(response['error']);

                    Ext.Msg.show({title: 'Error',
                        buttons: Ext.MessageBox.OK,
                        msg: 'Unable to create virtual server ',
                        icon: Ext.MessageBox.ERROR});
                }
            }); // END Ajax request

        };





        // save form processing
        function saveConfig(obj) {

            var addr_ranges = [];

            //var addr_rangees_store = mac_vlan_grid.getStore();


            
            address_ranges_store.each(function(f){
                //  var field = grid.colModel.getDataIndex(col);
                //           ed.field.setValue(record.data[field]);
                var data = f.data;

                addr_ranges.push(data['from_range']+' '+data['to_range']);

            });


                         



//alert(obj['etfw_network_wiz_lan']);
//var didi = obj['etfw_network_wiz_lan'];
for(prop in addr_ranges)
     alert(prop+' dd '+addr_ranges[prop]);
//
//alert(obj['etfw_network_wiz_lan_dhcp']['lan_address_dhcp']);




            var send_data = new Object();
            send_data['range'] = addr_ranges;
            

            var conn = new Ext.data.Connection({
                listeners:{
                    // wait message.....
                    beforerequest:function(){
                        Ext.MessageBox.show({
                            title: 'Please wait',
                            msg: 'Storing in db...',
                            width:300,
                            wait:true,
                            modal: false
                        });
                    },// on request complete hide message
                    requestcomplete:function(){Ext.MessageBox.hide();}}
            });// end conn

            conn.request({
                url: <?php echo json_encode(url_for('server/jsosnCreate',false)); ?>,
                params: {'nid':node_id,'server': Ext.encode(send_data)},                
                scope: this,
                success: function(resp,opt) {
                    var response = Ext.util.JSON.decode(resp.responseText);
                    var sid = response['response']['insert_id'];
                    var tree_id = 's'+ sid;


                    Ext.ux.Logger.info(response['response']['msg']);
               

                },
                failure: function(resp,opt) {
                    var response = Ext.util.JSON.decode(resp.responseText);

                    Ext.ux.Logger.error(response['error']);

                    Ext.Msg.show({title: 'Error',
                        buttons: Ext.MessageBox.OK,
                        msg: 'Unable to create virtual server ',
                        icon: Ext.MessageBox.ERROR});
                }
            }); // END Ajax request


    }


    // show the wizard
    wizard.show();
    //.showInit();




}
new ETFW_network_wizard();
</script>