<script>    
        
 ETFW_network_wizard = function(service_id,wizardTpl,containerId) {
    

    /**
     *
     * squid panel class
     *
     */
    squid_cardPanel = Ext.extend(Ext.ux.Wiz.Card, {

        initComponent: function(){

            var squid_tpl =
             {
                style:'margin-top:10px',
                defaults:{style:'padding-top:10px;'},
                xtype:'fieldset',
                title: 'SQUID proxy',
                items:[
                    {xtype:'hidden',name:'iface',value:<?php echo json_encode(sfConfig::get("mod_etfw_interface_lan")) ?>},
                    {xtype:'radio',
                        boxLabel: 'Transparent Proxy',  name: 'squid_proxy',
                        style:'margin-left:5px;',
                        fieldLabel:'',hideLabel:true, checked:true, inputValue: 'transparent'
                        ,helpText:'Does NOT support autrhentication'                        
                    }
                    ,{
                        layout:'table',
                        frame:true,                       
                        layoutConfig: {columns:2},
                        items:[
                            {
                             labelAlign:'left',
                             layout:'form',
                             items:[
                                {xtype:'radio',height:40,
                                    boxLabel: 'Proxy with AD (Active Directory)',
                                    width:120, name: 'squid_proxy',
                                    fieldLabel:'',hideLabel:true, inputValue: 'proxy_ad'
                                    ,listeners:{
                                        check:function(chkbox,checked){

                                            var adCmp = (this.ownerCt).ownerCt;
                                            var adFields = adCmp.get(1);

                                            adFields.items.each(function(e){
                                                if(!checked){
                                                    if(!e.isValid())
                                                        e.clearInvalid();
                                                    e.disable();
                                                }else
                                                    e.enable();
                                            });
                                        }

                                    }
                                }
                             ]
                            },
                            {
                             labelWidth:100,
                             layout:'form',
                             items:[
                                new Ext.form.TextField({
                                        fieldLabel: 'Server IP',
                                        name: 'address',
                                        maxLength: 15,
                                        vtype:'ip_addr',
                                        allowBlank:false,
                                        disabled:true,
                                        width:100
                                }),
                                new Ext.form.TextField({
                                        fieldLabel: 'Realm name',
                                        name: 'realm',
                                        maxLength: 15,
                                        allowBlank:false,
                                        disabled:true,
                                        vtype:'no_spaces',
                                        width:100
                                }),
                                new Ext.form.TextField({
                                        fieldLabel: 'Workgroup name',
                                        name: 'workgroup',
                                        maxLength: 15,
                                        disabled:true,
                                        allowBlank:false,
                                        vtype:'no_spaces',
                                        width:100
                                }),
                                new Ext.form.TextField({
                                        fieldLabel: 'Host name',
                                        name: 'hostname',
                                        maxLength: 15,
                                        disabled:true,
                                        vtype:'no_spaces',
                                        width:100
                                }),
                                new Ext.form.TextField({
                                        fieldLabel: 'Username',
                                        name: 'username',
                                        allowBlank:false,
                                        maxLength: 15,
                                        disabled:true,
                                        vtype:'no_spaces',
                                        width:100
                                }),
                                new Ext.form.TextField({
                                        fieldLabel: 'Password',
                                        name: 'passwd',
                                        inputType:'password',
                                        allowBlank:false,
                                        maxLength: 15,
                                        vtype:'no_spaces',
                                        disabled:true,
                                        width:100
                                })
                             ]
                            }
                        ]
                    }//end table
                    ,{
                        layout:'table',
                        frame:true,
                        layoutConfig: {columns:2,border:false},                        
                        items:[
                            {
                             labelAlign:'left',
                             layout:'form',                             
                             items:[
                                {xtype:'radio',
                                boxLabel: 'Proxy with LDAP', width:120,
                                name: 'squid_proxy',fieldLabel:'',
                                hideLabel:true, inputValue: 'proxy_ldap'
                                ,listeners:{
                                        check:function(chkbox,checked){
                                            
                                            var ldapCmp = (this.ownerCt).ownerCt;
                                            var ldapFields = ldapCmp.get(1);

                                            ldapFields.items.each(function(e){
                                                if(!checked){
                                                    if(!e.isValid())
                                                        e.clearInvalid();
                                                    e.disable();
                                                }else
                                                    e.enable();
                                            });
                                        }

                                    }
                                }
                             ]
                            },
                            {
                             labelWidth:70,
                             layout:'form',                             
                             items:[
                                new Ext.form.TextField({                                        
                                        fieldLabel: 'IP Address',
                                        name: 'address',
                                        maxLength: 15,
                                        vtype:'ip_addr',
                                        allowBlank:false,
                                        disabled:true,
                                        width:100
                                }),
                                new Ext.form.TextField({
                                        fieldLabel: 'Base DN',
                                        name: 'base_dn',
                                        maxLength: 15,
                                        disabled:true,
                                        allowBlank:false,
                                        width:100,
                                        vtype:'no_spaces'
                                })
                             ]
                            }
                        ]
                    }//end table
                ]//end fieldset items
            };

            Ext.apply(this, {
                id: 'etfw-wiz_lan_squid',
                monitorValid : true,
                defaults     : {
                    labelStyle : 'font-size:11px;'
                   // ,bodyStyle: 'padding:10px;'
                },
                title: 'LAN SQUID',
                items : [{
                        border    : false,
                        bodyStyle : 'background:none;',
                        html      : 'Configure SQUID on LAN.'
                    },squid_tpl
                    ]
            });

            squid_cardPanel.superclass.initComponent.call(this);
        }        

    });

    //squid_cardPanel

    /**
     *
     * network topology panel class
     *
     */
    network_topology_cardPanel = Ext.extend(Ext.ux.Wiz.Card, {

        initComponent: function(){
            

            Ext.apply(this, {
                title        : 'Network topology',
                monitorValid : true,
                defaults     : {
                    labelStyle : 'font-size:11px;width:140px;'
                },

                items : [{
                        border    : false,
                        bodyStyle : 'background:none;',
                        html      : 'Please choose network topology.'
                    },
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
                                            ,qtip:'ETFW + LAN'
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
                                            ,qtip:'ETFW + LAN + DMZ'
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
                                    case 'etfw_only' :                                                                                                                 
                                                       (cp.get('etfw-wiz_dmz')).setSkip(true);
                                                       (cp.get('etfw-wiz_dmz_dhcp')).setSkip(true);
                                                     break;
                                    case 'etfw_dmz' : (cp.get('etfw-wiz_dmz')).setSkip(false);
                                                      (cp.get('etfw-wiz_dmz_dhcp')).setSkip(false);                                                      
                                                     break;
                                           default : break;
                                }
                    }
                }
            });   

            network_topology_cardPanel.superclass.initComponent.call(this);
            Ext.Component.superclass.constructor.call(this);
        }

    });


    /**
     *
     * WAN interface panel class
     *
     */
    wan_cardPanel = Ext.extend(Ext.ux.Wiz.Card, {

        initComponent: function(){

            // WAN interface
            Ext.apply(this, {

                title        : 'WAN interface',
                id: 'etfw-wiz_wan',
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
                    build_interface_fields('wan',<?php echo json_encode(sfConfig::get("mod_etfw_interface_wan")) ?>)
                ]
            });

            wan_cardPanel.superclass.initComponent.call(this);
        }

    });
    
    
    /**
     *
     * LAN interface panel class
     *
     */
    lan_cardPanel = Ext.extend(Ext.ux.Wiz.Card, {

        initComponent: function(){

            var lan_fields = [build_interface_fields('lan',<?php echo json_encode(sfConfig::get("mod_etfw_interface_lan")) ?>),
                            {xtype:'radio',boxLabel: 'Yes', name: 'etfw_lan_dhcp', labelStyle : 'width:200px;',labelSeparator:'',fieldLabel: 'Configure LAN interface DHCP now?', inputValue:'1'},
                            {xtype:'radio',boxLabel: 'No', name: 'etfw_lan_dhcp', labelStyle : 'width:200px;',inputValue:'0', checked: true},
                            {xtype:'radio',boxLabel: 'Yes', name: 'etfw_lan_squid', labelStyle : 'width:200px;',labelSeparator:'',fieldLabel: 'Configure LAN interface SQUID now?', inputValue:'1'},
                            {xtype:'radio',boxLabel: 'No', name: 'etfw_lan_squid', labelStyle : 'width:200px;',inputValue:'0', checked: true}
                        ];// end fieldset

            // LAN interface
            Ext.apply(this, {

                title        : 'LAN interface',
                id: 'etfw-wiz_lan',
                monitorValid : true,
                defaults     : {
                    labelStyle : 'font-size:11px;',
                    bodyStyle : 'padding:10px;background-color:#F6F6F6;'
                },
                items : [{
                        border    : false,frame:false,
                        bodyStyle : 'background:none;padding-bottom:30px;',
                        html      : 'Specify the LAN interface address.'
                    },
                    lan_fields
                ]
                ,listeners:{                    
                    nextclick:function(card){

                                var wizData = wizard.getWizardData();
                                var cardData = wizData[card.getId()];
                                var cp = wizard.cardPanel;
                                var lan_dhcp_card = cp.get('etfw-wiz_lan_dhcp');
                                switch(cardData['etfw_lan_dhcp']){
                                    case '0' : lan_dhcp_card.setSkip(true);
                                               break;
                                    case '1' : 
                                               var lan_dhcp_form = lan_dhcp_card.getForm();
                                               lan_dhcp_card.loadRecord({'lan_interface':cardData['interface'],
                                                                         'lan_address_dhcp':cardData['address'],
                                                                         'lan_netmask_dhcp':cardData['netmask']});
                                               lan_dhcp_card.setSkip(false);                                                                                              
                                               break;
                                    default : break;
                                }

                                switch(cardData['etfw_lan_squid']){
                                    case '0' : (cp.get('etfw-wiz_lan_squid')).setSkip(true);
                                               break;
                                    case '1' : var lan_squid_card = cp.get('etfw-wiz_lan_squid');
                                               lan_squid_card.setSkip(false);
                                               break;
                                    default : break;
                                }

                    }
                }
            });

            lan_cardPanel.superclass.initComponent.call(this);            
        }

    });



    /**
     *
     * LAN DHCP panel class
     *
     */
    lan_dhcp_cardPanel = Ext.extend(Ext.ux.Wiz.Card, {

        initComponent: function(){

            // LAN DHCP
            Ext.apply(this, {

                id: 'etfw-wiz_lan_dhcp',
                title        : 'LAN DHCP',
                monitorValid : true,
                defaults     : {
                    labelStyle : 'font-size:11px;'                   
                },                
                items : [{
                        border    : false,
                        bodyStyle : 'background:none;padding-bottom:30px;',                        
                        html      : 'Configure DHCP on LAN interface.'
                    },
                    {
                        layout: 'column',
                        bodyStyle: 'padding:5px',                        
                        defaults: {layout:'form',border:false},
                        items: [{
                            labelWidth:80,
                            columnWidth: 0.5,
                            items: [
                                    new Ext.form.Hidden({name: 'lan_interface'})
                                    ,new Ext.form.DisplayField({
                                        fieldLabel: 'LAN IP address',
                                        name: 'lan_address_dhcp',
                                        readOnly:true,
                                        editable:false,
                                        allowBlank: false,
                                        width:100})
                                    ,new Ext.form.DisplayField({
                                        fieldLabel: 'LAN netmask',
                                        name: 'lan_netmask_dhcp',
                                        readOnly:true,
                                        editable:false,
                                        allowBlank: false,
                                        width:100})
                                    ,new Ext.form.DisplayField({
                                        fieldLabel: 'LAN broadcast',
                                        name: 'lan_bcastaddress_dhcp',
                                        readOnly:true,
                                        editable:false,
                                        allowBlank: false,
                                        width:100})
                            ]
                            },
                            {
                            columnWidth: 0.5,
                            labelWidth:115,
                            items: [new Ext.form.DisplayField({
                                        fieldLabel: 'LAN network',
                                        name: 'lan_netaddress_dhcp',
                                        readOnly:true,
                                        editable:false,
                                        allowBlank: false,
                                        width:100})
                                    ,new Ext.form.DisplayField({
                                        fieldLabel: 'LAN first host address',
                                        name: 'lan_firstaddress_dhcp',
                                        readOnly:true,
                                        editable:false,
                                        allowBlank: false,
                                        width:100})
                                    ,new Ext.form.DisplayField({
                                        fieldLabel: 'LAN last host address',
                                        name: 'lan_lastaddress_dhcp',
                                        readOnly:true,
                                        editable:false,
                                        allowBlank: false,
                                        width:100})
                            ]
                        }]
                    },
                    {xtype:'spacer',height:20}
                    ,build_address_ranges(<?php echo json_encode(sfConfig::get("mod_etfw_interface_lan")) ?>)
                ]
            });

            lan_dhcp_cardPanel.superclass.initComponent.call(this);
        },
        loadRecord:function(data){
            
            var form = this.getForm();            
                      
            if(data['lan_address_dhcp'] && data['lan_netmask_dhcp']){
                var net_data = network_calculator(data['lan_address_dhcp'],data['lan_netmask_dhcp']);
                data['lan_netaddress_dhcp'] = net_data[0];
                data['lan_bcastaddress_dhcp'] = net_data[1];
                data['lan_firstaddress_dhcp'] = net_data[2];
                data['lan_lastaddress_dhcp'] = net_data[3];

                var rec = new Object();
                rec.data = data;
                form.loadRecord(rec);

                var ranges_grid = Ext.getCmp('etfw-wiz-'+data['lan_interface']+'_address_ranges_dhcp');
                var ranges_ds = ranges_grid.getStore();

                if(ranges_ds.getCount()==0){
                    var ranges_data = net_data[4];
                    var records = [];
                    for(var i=0,len=ranges_data.length;i<len;i++){

                        var record = {
                                from_range: ranges_data[i]['from'],
                                to_range: ranges_data[i]['to'],
                                bootp: 0};

                        records.push(record);                        
                        
                    }
                    ranges_ds.loadData(records,true);

                    ranges_grid.fireEvent('afteredit', ranges_grid);
                }                
            }
        }
    });
    

    dmz_cardPanel = Ext.extend(Ext.ux.Wiz.Card, {

        initComponent: function(){

            var dmz_fields = [build_interface_fields('dmz',<?php echo json_encode(sfConfig::get("mod_etfw_interface_dmz")) ?>),
                            {xtype:'radio',boxLabel: 'Yes', name: 'etfw_dmz_dhcp', labelStyle : 'width:200px;',labelSeparator:'',fieldLabel: 'Configure DMZ interface DHCP now?', inputValue:'1'},
                            {xtype:'radio',boxLabel: 'No', name: 'etfw_dmz_dhcp', labelStyle : 'width:200px;',inputValue:'0', checked: true}
                        ];// end fieldset

            // DMZ interface
            Ext.apply(this, {

                id: 'etfw-wiz_dmz',
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
                    dmz_fields
                    ],
                listeners:{
                    nextclick:function(card){

                                var wizData = wizard.getWizardData();
                                var cardData = wizData[card.getId()];
                                var cp = wizard.cardPanel;
                                var dmz_dhcp_card = cp.get('etfw-wiz_dmz_dhcp');
                                switch(cardData['etfw_dmz_dhcp']){
                                    case '0' : dmz_dhcp_card.setSkip(true);
                                               break;
                                    case '1' :
                                               var dmz_dhcp_form = dmz_dhcp_card.getForm();
                                               dmz_dhcp_card.loadRecord({'dmz_interface':cardData['interface'],
                                                                         'dmz_address_dhcp':cardData['address'],
                                                                         'dmz_netmask_dhcp':cardData['netmask']});
                                               dmz_dhcp_card.setSkip(false);
                                               break;
                                    default : break;
                                }

                    }
                }                
            });

            dmz_cardPanel.superclass.initComponent.call(this);            
        }

    });
       
    
    /**
     *
     * DMZ DHCP panel class
     *
     */
    dmz_dhcp_cardPanel = Ext.extend(Ext.ux.Wiz.Card, {

        initComponent: function(){

            // DMZ DHCP
            Ext.apply(this, {

                id: 'etfw-wiz_dmz_dhcp',
                title        : 'DMZ DHCP',
                monitorValid : true,                
                defaults     : {
                    labelStyle : 'font-size:11px;'                   
                },                
                items : [{
                        border    : false,
                        bodyStyle : 'background:none;padding-bottom:30px;',
                        html      : 'Configure DHCP on DMZ interface.'
                    },
                    {
                        layout: 'column',
                        bodyStyle: 'padding:5px',
                        defaults: {layout:'form',border:false},
                        items: [{
                            labelWidth:80,
                            columnWidth: 0.5,
                            items: [
                                    new Ext.form.Hidden({name: 'dmz_interface'})
                                    ,new Ext.form.DisplayField({
                                        fieldLabel: 'DMZ IP address',
                                        name: 'dmz_address_dhcp',
                                        readOnly:true,
                                        editable:false,
                                        allowBlank: false,
                                        width:100})
                                    ,new Ext.form.DisplayField({
                                        fieldLabel: 'DMZ netmask',
                                        name: 'dmz_netmask_dhcp',
                                        readOnly:true,
                                        editable:false,
                                        allowBlank: false,
                                        width:100})
                                    ,new Ext.form.DisplayField({
                                        fieldLabel: 'DMZ broadcast',
                                        name: 'dmz_bcastaddress_dhcp',
                                        readOnly:true,
                                        editable:false,
                                        allowBlank: false,
                                        width:100})
                            ]
                            },
                            {
                            columnWidth: 0.5,
                            labelWidth:115,
                            items: [new Ext.form.DisplayField({
                                        fieldLabel: 'DMZ network',
                                        name: 'dmz_netaddress_dhcp',
                                        readOnly:true,
                                        editable:false,
                                        allowBlank: false,
                                        width:100})
                                    ,new Ext.form.DisplayField({
                                        fieldLabel: 'DMZ first host address',
                                        name: 'dmz_firstaddress_dhcp',
                                        readOnly:true,
                                        editable:false,
                                        allowBlank: false,
                                        width:100})
                                    ,new Ext.form.DisplayField({
                                        fieldLabel: 'DMZ last host address',
                                        name: 'dmz_lastaddress_dhcp',
                                        readOnly:true,
                                        editable:false,
                                        allowBlank: false,
                                        width:100})
                            ]
                        }]
                    }
                    ,{xtype:'spacer',height:20}
                    ,build_address_ranges(<?php echo json_encode(sfConfig::get("mod_etfw_interface_dmz")) ?>)
                ]
            });

            dmz_dhcp_cardPanel.superclass.initComponent.call(this);
        },
        loadRecord:function(data){
        
            var form = this.getForm();
           
            if(data['dmz_address_dhcp'] && data['dmz_netmask_dhcp']){
                var net_data = network_calculator(data['dmz_address_dhcp'],data['dmz_netmask_dhcp']);
                data['dmz_netaddress_dhcp'] = net_data[0];
                data['dmz_bcastaddress_dhcp'] = net_data[1];
                data['dmz_firstaddress_dhcp'] = net_data[2];
                data['dmz_lastaddress_dhcp'] = net_data[3];

                (form.findField('dmz_interface')).setValue(data['dmz_interface']);
                (form.findField('dmz_address_dhcp')).setValue(data['dmz_address_dhcp']);
                (form.findField('dmz_netmask_dhcp')).setValue(data['dmz_netmask_dhcp']);
                (form.findField('dmz_netaddress_dhcp')).setValue(data['dmz_netaddress_dhcp']);
                (form.findField('dmz_bcastaddress_dhcp')).setValue(data['dmz_bcastaddress_dhcp']);
                (form.findField('dmz_firstaddress_dhcp')).setValue(data['dmz_firstaddress_dhcp']);
                (form.findField('dmz_lastaddress_dhcp')).setValue(data['dmz_lastaddress_dhcp']);

                var ranges_grid = Ext.getCmp('etfw-wiz-'+data['dmz_interface']+'_address_ranges_dhcp');
                var ranges_ds = ranges_grid.getStore();

                if(ranges_ds.getCount()==0){
                    var ranges_data = net_data[4];
                    var records = [];
                    for(var i=0,len=ranges_data.length;i<len;i++){

                        var record = {
                                from_range: ranges_data[i]['from'],
                                to_range: ranges_data[i]['to'],
                                bootp: 0};

                        records.push(record);                        

                    }
                    ranges_ds.loadData(records,true);

                    ranges_grid.fireEvent('afteredit', ranges_grid);
                }
            }
        }

    });


    


    



    /*
     * function used to build form fields to configure interface address (ip,netmask....)
     *
     */
    var build_interface_fields = function (intf,value){

        var static_radio = new Ext.form.Radio({
                boxLabel: 'Static config', width:90,
                name: 'address_source',fieldLabel:'',hideLabel:true,
                inputValue: 'static'
                ,listeners:{
                    check:function(chkbox,checked){

                        var addrCmp = (this.ownerCt).ownerCt;
                        var addrFields = addrCmp.get(1);

                        addrFields.items.each(function(e){
                            if(!checked){
                                if(!e.isValid())
                                    e.clearInvalid();
                                e.disable();
                            }else
                                e.enable();
                        });
                    }
                }
        });
            
        var static_source = new Ext.Panel({
               border:false,
               items:[{
                        layout:'table',
                        frame:true,
                        layoutConfig: {columns:2},
                        items:[
                                {
                                labelAlign:'left',
                                layout:'form',
                                items:static_radio
                                },
                                {                                
                                labelWidth:70,                                
                                layout:'form',                                
                                items:[
                                    new Ext.form.TextField({
                                            fieldLabel: 'IP Address',
                                            name: 'address',
                                            maxLength: 15,
                                            vtype:'ip_addr',
                                            allowBlank:false,
                                            disabled:true,
                                            width:100,
                                            listeners:{
                                                blur:function(field){
                                                    var ip_val = field.getValue();
                                                    var item_netmask = this.ownerCt.get(1);
                                                    var netmask_val = item_netmask.getValue();
                                                    var item_bcast = this.ownerCt.get(2);

                                                    //calculate network and broadcast address giving ip and subnet mask
                                                    if(field.isValid() && item_netmask.isValid())
                                                    {
                                                        var netbcast = network_calculator(ip_val,netmask_val);
                                                        item_bcast.setValue(netbcast[1]);
                                                    }
                                                }
                                            }
                                    }),
                                    new Ext.form.TextField({
                                            fieldLabel: 'Netmask',
                                            name: 'netmask',
                                            maxLength: 15,
                                            width:100,
                                            vtype:'ip_addr',
                                            allowBlank:false,
                                            disabled:true,
                                            listeners:{
                                                blur:function(field){
                                                    var netmask_val = field.getValue();
                                                    var item_ip = this.ownerCt.get(0);
                                                    var ip_val = item_ip.getValue();
                                                    var item_bcast = this.ownerCt.get(2);
                                                    
                                                    if(field.isValid() && item_ip.isValid())
                                                    {
                                                        var netbcast = network_calculator(ip_val,netmask_val);
                                                        item_bcast.setValue(netbcast[1]);
                                                    }
                                                                                                        
                                                }
                                            }
                                    }),
                                    new Ext.form.TextField({
                                            fieldLabel: 'Broadcast',
                                            name: 'broadcast',
                                            vtype:'ip_addr',
                                            allowBlank:false,
                                            disabled:true,
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
            readOnly:true,
            disabled:true,
            editable:false,
            value: value,
            allowBlank: false,
            width:100});

        var intf_hidden = new Ext.form.Hidden({
            name: 'interface',
            value:value
            });

        var fields = [];
        switch(intf){
              case 'wan':                                                                     
                    (static_source.get(0)).get(1).add(new Ext.form.TextField({
                                            fieldLabel: 'Gateway',
                                            name: 'gateway',
                                            maxLength: 15,
                                            vtype:'ip_addr',
                                            allowBlank:false,
                                            disabled:true,
                                            width:100
                                    }));
                    fields = [intfname,intf_hidden,
                    new Ext.form.FieldSet({
                    style:'margin-top:10px',
                    title: 'Address source',items:[dhcp_source,static_source]})];
                    dhcp_source.setValue(true);
                    break;
              default:                    
                    fields = [intfname,intf_hidden,
                    new Ext.form.FieldSet({
                    style:'margin-top:10px',
                    title: 'Address source',items:[static_source]})];
                    static_radio.setValue(true);              
                    break;

        }
        return fields;
        
    }; // end build_interface_fields


    /*
     * function used to build address ranges grid
     *
     */
    var build_address_ranges = function (intf){

        /*
         * address ranges grid
         *
         */
        var bootpColumn = new Ext.grid.CheckColumn({
            header: 'Dynamic BOOTP?',
            dataIndex: 'bootp',
            align: 'center',                
            width: 110,
            editor:new Ext.form.Checkbox({validationEvent:false})
        });
            
        var address_ranges_cm = new Ext.grid.ColumnModel({
            // specify any defaults for each column
            defaults: {
                sortable:true
            },
            columns:[
                new Ext.grid.RowNumberer(),
                {
                    header: 'From address',
                    dataIndex: 'from_range',
                    width: 100,                                        
                    editor: new Ext.form.TextField({
                        allowBlank: false,
                        selectOnFocus:true,
                        vtype:'ip_addr'
                    })
                },
                {
                    header: 'To address',
                    dataIndex: 'to_range',
                    width: 100,                    
                    editor: new Ext.form.TextField({
                        allowBlank: false,
                        selectOnFocus:true,
                        vtype:'ip_addr'
                    })
                },bootpColumn
            ]
        });       


    

        var address_ranges_store = new Ext.data.Store({
            reader: new Ext.data.JsonReader({               
                fields:['from_range','to_range','bootp']
            })
        });

        var address_ranges_status = new Ext.form.TextField({
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
                return v!='Fill grid with data...';
               // return t.test(v);
            }
        });

        // custom template for the grid header
 	    var headerTpl = new Ext.Template(
 	        '<table border="0" cellspacing="0" cellpadding="0" style="{tstyle}">',
 	        '<thead><tr class="x-grid3-hd-row">{cells}</tr></thead>',
 	        '<tbody><tr>',
 	            '<td><div id="new-address_range-icon"></div></td>',
 	            '<td><div class="x-small-editor" id="new-address_range-from-'+intf+'"></div></td>',
 	            '<td><div class="x-small-editor" id="new-address_range-to-'+intf+'"></div></td>',
                '<td><div align="center" id="new-address_range-bootp-'+intf+'"></div></td>',
 	        '</tr></tbody>',
 	        "</table>"
 	    );

        // create the grid
        var address_ranges_grid = new Ext.grid.EditorGridPanel({
            store: address_ranges_store,
            id:'etfw-wiz-'+intf+'_address_ranges_dhcp',
            cm: address_ranges_cm,
            autoHeight: true,
            border:true,
            plugins:[bootpColumn],            
            viewConfig:{               
                emptyText: 'Empty!',  //  emptyText Message
                deferEmptyText:false,
                templates: {
 	                header: headerTpl
 	            }
            },
            sm: new Ext.grid.RowSelectionModel({
                singleSelect: true,
                moveEditorOnEnter:false
            }),        
            tbar:[{
                    text: 'Add address range',
                    iconCls:'add'
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
                        address_ranges_grid.getView().refresh();
                        address_ranges_grid.fireEvent('afteredit');   
                    }
            }]
            ,listeners:{
                afteredit:function(){
                    var cols = this.colModel.getColumnCount();
                    var rows = this.store.getCount();
                    var valid = true;
                    var r, c;

                    if(rows==0){
                        address_ranges_status.setValue("Fill grid with data...");
                        return false;
                    }

                    var valid = true;
                    for(r = 0; r < rows; r++) {
                        for(c = 1; c < cols-1; c++) {
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
                        address_ranges_status.setValue("Fill grid with data...");
                    }
                    else{
                        address_ranges_status.setValue("You have "+rows+" address range(s) inserted");
                    }
                    return valid;
                }
            }
        });
        
        address_ranges_grid.getSelectionModel().on('selectionchange', function(sm){
            address_ranges_grid.removeBtn.setDisabled(sm.getCount() < 1);
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

        
        // The fields in the grid's header
 	    var newFromTpl = new Ext.form.TextField({
 	        emptyText: 'Add new range...',
            vtype:'ip_addr',allowBlank:false
 	    });

        var newToTpl = new Ext.form.TextField({
 	        emptyText: '',disabled:true,
            vtype:'ip_addr',allowBlank:false
 	    });

        var newBootTpl = new Ext.form.Checkbox({disabled:true});


        // syncs the header fields' widths with the grid column widths
 	    function syncFields(){       

            var cm = address_ranges_grid.getColumnModel();
            newFromTpl.setSize(cm.getColumnWidth(1));
 	        newToTpl.setSize(cm.getColumnWidth(2));
            newBootTpl.setSize(cm.getColumnWidth(3));
 	    }

        //render header tpl and call syncFields on render
        address_ranges_grid.on('render',function(){

            newFromTpl.render('new-address_range-from-'+intf);
            newToTpl.render('new-address_range-to-'+intf);
            newBootTpl.render('new-address_range-bootp-'+intf);
            syncFields();
        },this,{single:true});
 	    

 	    var editing = false, focused = false, userTriggered = false;
 	    var handlers = {
 	        focus: function(){
 	            focused = true;
 	        },
 	        blur: function(){
 	            focused = false;
 	            doBlur.defer(250);
 	        },
 	        specialkey: function(f, e){
 	            if(e.getKey()==e.ENTER){
 	                userTriggered = true;
 	                e.stopEvent();
 	                f.el.blur();
 	                if(f.triggerBlur){
 	                    f.triggerBlur();
 	                }
 	            }
 	        }
 	    };
        
 	    newFromTpl.on(handlers);
 	    newToTpl.on(handlers);
        newBootTpl.on(handlers);
 	    
        newFromTpl.on('focus', function(){
 	        focused = true;
 	        if(!editing){
 	            newToTpl.enable();
                newBootTpl.enable();
 	            syncFields();
 	            editing = true;
 	        }
 	    });

 	    // when a field in the add bar is blurred, this determines
 	    // whether a new task should be created
 	    function doBlur(){
          
 	        if(editing && !focused){
 	            var newFrom = newFromTpl.getValue();
                var newTo = newToTpl.getValue();
                var newBootp = newBootTpl.getValue();
                if((newFromTpl.isValid() && !Ext.isEmpty(newFrom))
                    && (newToTpl.isValid() && !Ext.isEmpty(newTo)))
                {
                    var record = {
                            from_range: newFrom,
                            to_range: newTo,
                            bootp: newBootp};
                        
                    address_ranges_grid.stopEditing();
                    address_ranges_store.loadData([record],true);
                    address_ranges_grid.fireEvent('afteredit', address_ranges_grid);

 	                newFromTpl.reset();
                    newToTpl.reset();
                    newBootTpl.reset();
                    
 	                if(userTriggered){ // if the entered to add the range, then go to a new add automatically
 	                    userTriggered = false;
 	                    newFromTpl.focus.defer(100, newFromTpl);
 	                }
 	            }
                
 	            newToTpl.disable();
                newBootTpl.disable();
 	            editing = false;
 	        }
 	    };

        return [{
                layout:'table',id:'fsd-'+intf,
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
                        items: address_ranges_grid
                    }
                ]
                }
                ,new Ext.form.FieldSet({                    
                    labelWidth: 1,
                    style:'padding-left:0px;border:none;',
                    autoHeight:true,
                    items:[address_ranges_status]
        })];
        
    }; // end function build_address_ranges

    var viewerSize = Ext.getBody().getViewSize();
    var windowHeight = viewerSize.height * 0.95;
    windowHeight = Ext.util.Format.round(windowHeight,0);
    
    var cards = [// card with welcome message
            new Ext.ux.Wiz.Card({
                title : 'Welcome',
                defaults     : {
                    labelStyle : 'font-size:11px;width:140px;'
                },
                items : [{
                        border    : false,
                        bodyStyle : 'background:none;',
                        html      : 'Welcome to the '+wizardTpl+' setup wizard.<br><br>'+
                            'All the actual configuration will be lost. It should be applyed on first time setup.'+
                            '<br>Follow the steps to setup '+wizardTpl+' configuration<br/><br/>'

                    }]
            })];
        
    var wizardTitle = 'Setup Wizard';
    switch(wizardTpl){
        case 'squid' :
                        wizardTitle = 'SQUID '+wizardTitle;
                        cards.push(new squid_cardPanel());
                        break;
        case 'dhcp'  :
                        wizardTitle = 'DHCP '+wizardTitle;                      

                        

                        <?php
                        if(isset($interfaces[sfConfig::get("mod_etfw_interface_lan")]))
                        {
                            $lan_intf = $interfaces[sfConfig::get("mod_etfw_interface_lan")];
                        ?>
                            var lan_dhcp = new lan_dhcp_cardPanel();
                            lan_dhcp.on('afterlayout',function(){//alert('after');
                                lan_dhcp.loadRecord({
                                    'lan_interface':<?php echo json_encode(sfConfig::get("mod_etfw_interface_lan")) ?>,
                                    'lan_address_dhcp':<?php echo json_encode($lan_intf['address']) ?>,
                                    'lan_netmask_dhcp':<?php echo json_encode($lan_intf['netmask']) ?>
                                });
                            },this,{single:true});
                            cards.push(lan_dhcp);
                        <?php
                        }
                        ?>

                        <?php
                        if(isset($interfaces[sfConfig::get("mod_etfw_interface_dmz")]))
                        {
                            $dmz_intf = $interfaces[sfConfig::get("mod_etfw_interface_dmz")];
                        ?>
                            var dmz_dhcp = new dmz_dhcp_cardPanel();
                            dmz_dhcp.on('afterlayout',function(){//alert('after');
                                dmz_dhcp.loadRecord({
                                    'dmz_interface':<?php echo json_encode(sfConfig::get("mod_etfw_interface_dmz")) ?>,
                                    'dmz_address_dhcp':<?php echo json_encode($dmz_intf['address']) ?>,
                                    'dmz_netmask_dhcp':<?php echo json_encode($dmz_intf['netmask']) ?>
                                });
                            },this,{single:true});
                            cards.push(dmz_dhcp);
                        <?php
                        }
                        ?>                                                
                        break;
        default:
                        wizardTitle = 'Network '+wizardTitle;
                        cards.push(new network_topology_cardPanel());
                        cards.push(new wan_cardPanel());
                        cards.push(new lan_cardPanel());
                        cards.push(new lan_dhcp_cardPanel());
                        cards.push(new squid_cardPanel());
                        cards.push(new dmz_cardPanel());
                        cards.push(new dmz_dhcp_cardPanel());
                        break;
    }
    
    cards.push(// finish card with finish-message
            new Ext.ux.Wiz.Card({          
                title        : 'Finished!',
                monitorValid : true,
                items : [{
                        border    : false,
                        bodyStyle : 'background:none;',
                        html      : 'Thank you!. Your data has been collected.<br>'+
                            'When you click on the "finish" button, the virtual server will be created.<br />'
                    }]
            }));



    var wizard = new Ext.ux.Wiz({
        border:true,
        title : wizardTitle,
        iconCls:'wizard',
        
        headerConfig : {
            title : 'Create new configuration'
        },
        width:700,
        height:500,

        westConfig : {
            width : 150
        },

        cardPanelConfig : {
            defaults : {
                baseCls    : 'x-small-editor',
                autoScroll:true,
                bodyStyle  : 'border:none;padding:15px 15px 15px 15px;background-color:#F6F6F6;',
                border     : false
            }
        },
        cards: cards,
        listeners: {
            finish: function() { onSave( this.getWizardData() ) }
        }
    });



      
    function get_ranges_address(iface)
    {
        //dhcp ranges
        var ranges = Ext.getCmp('etfw-wiz-'+iface+'_address_ranges_dhcp');
        var ranges_ds = ranges.getStore();
        var dhcp_ranges = [];

        ranges_ds.each(function(e){
            var dhcp_obj = new Object();
            dhcp_obj['low'] = e.data.from_range;
            dhcp_obj['hi'] = e.data.to_range;

            if(e.data.bootp===true) dhcp_obj['dyn'] = 1;
            else dhcp_obj['dyn'] = 0;

            dhcp_ranges.push(dhcp_obj);
        });
        return dhcp_ranges;
    }


    // save form processing
    function onSave(wizData) {
        var wizPanel = wizard.cardPanel;

        /*
         * build interfaces data
         */
        var interfaces = [];

        // wan data ??
        if(!Ext.isEmpty(wizData['etfw-wiz_wan'])){
            var wan_raw = wizData['etfw-wiz_wan'];
            var wan_obj = new Object();
            wan_obj['type'] = 'wan';
            wan_obj['name'] = wan_raw['interface'];
            if(wan_raw['address_source']=='static'){
                wan_obj['address'] = wan_raw['address'];
                wan_obj['netmask'] = wan_raw['netmask'];
                wan_obj['gateway'] = wan_raw['gateway'];
                wan_obj['broadcast'] = wan_raw['broadcast'];
            }
            else{
                //dhcp
                wan_obj['dhcp'] = 1;
            }
            interfaces.push(wan_obj);
        }

        // lan data ??
        if(!Ext.isEmpty(wizData['etfw-wiz_lan'])){
            var lan_raw = wizData['etfw-wiz_lan'];
            var lan_obj = new Object();
            lan_obj['type'] = 'lan';
            lan_obj['name'] = lan_raw['interface'];
            lan_obj['address'] = lan_raw['address'];
            lan_obj['netmask'] = lan_raw['netmask'];
            lan_obj['broadcast'] = lan_raw['broadcast'];
            interfaces.push(lan_obj);
        }

        // dmz data ??
        if(!Ext.isEmpty(wizData['etfw-wiz_dmz'])){
            var skip_dmz = (wizPanel.get('etfw-wiz_dmz')).skip;
            if(!skip_dmz){
                var dmz_raw = wizData['etfw-wiz_dmz'];
                var dmz_obj = new Object();
                dmz_obj['type'] = 'dmz';
                dmz_obj['name'] = dmz_raw['interface'];
                dmz_obj['address'] = dmz_raw['address'];
                dmz_obj['netmask'] = dmz_raw['netmask'];
                dmz_obj['broadcast'] = dmz_raw['broadcast'];
                interfaces.push(dmz_obj);
            }
        }        

        /*
         * build dhcp data
         */
        var dhcp = [];

        //lan dhcp ranges ??
        if(!Ext.isEmpty(wizPanel.get('etfw-wiz_lan_dhcp'))){
            var skip_lan_ranges = (wizPanel.get('etfw-wiz_lan_dhcp')).skip;
            if(!skip_lan_ranges){
                var lan_dhcp_raw = wizData['etfw-wiz_lan_dhcp'];
                var lan_dhcp_ranges = get_ranges_address(lan_dhcp_raw['lan_interface']);
                dhcp.push({'if':lan_dhcp_raw['lan_interface'],'ranges':lan_dhcp_ranges});
            }
        }

        //dmz dhcp ranges ??
        if(!Ext.isEmpty(wizPanel.get('etfw-wiz_dmz_dhcp'))){
            var skip_dmz_ranges = (wizPanel.get('etfw-wiz_dmz_dhcp')).skip;
            if(!skip_dmz_ranges){
                var dmz_dhcp_raw = wizData['etfw-wiz_dmz_dhcp'];
                var dmz_dhcp_ranges = get_ranges_address(dmz_dhcp_raw['dmz_interface']);
                dhcp.push({'if':dmz_dhcp_raw['dmz_interface'],'ranges':dmz_dhcp_ranges});
            }
        }


        /*
        * build squid data
        *
        */

        var squid = '';

        if(!Ext.isEmpty(wizPanel.get('etfw-wiz_lan_squid'))){
            var skip_lan_squid = (wizPanel.get('etfw-wiz_lan_squid')).skip;
            if(!skip_lan_squid){
                //lan squid
                var lan_squid_raw = wizData['etfw-wiz_lan_squid'];
                var lan_squid_proxy = lan_squid_raw['squid_proxy'];
                var lan_squid_obj = new Object();
                lan_squid_obj['if'] = lan_squid_raw['iface'];
                switch(lan_squid_proxy){
                    case 'transparent':
                                    lan_squid_obj['ini_template'] = lan_squid_proxy;
                                    break;
                    case 'proxy_ad':
                                    lan_squid_obj['ini_template'] = lan_squid_proxy;
                                    lan_squid_obj['workgroup'] = lan_squid_raw['workgroup'];
                                    lan_squid_obj['dcipaddr'] = lan_squid_raw['address'];
                                    lan_squid_obj['dchostname'] = lan_squid_raw['hostname'];
                                    lan_squid_obj['domainadmin'] = lan_squid_raw['username'];
                                    lan_squid_obj['domainpasswd'] = lan_squid_raw['passwd'];
                                    lan_squid_obj['realm'] = lan_squid_raw['realm'];
                                    break;
                    case 'proxy_ldap':
                                    lan_squid_obj['ini_template'] = lan_squid_proxy;
                                    lan_squid_obj['ip_admin'] = lan_squid_raw['address'];
                                    lan_squid_obj['base_dn'] = lan_squid_raw['base_dn'];
                                    break;
                }
                squid = lan_squid_obj;
            }
        }

        var send_data = new Object();
        if(!Ext.isEmpty(interfaces)) send_data['interfaces'] = interfaces;
        if(!Ext.isEmpty(dhcp)) send_data['dhcp'] = dhcp;
        if(!Ext.isEmpty(squid)) send_data['squid'] = squid;

        var conn = new Ext.data.Connection({
            listeners:{
                // wait message.....
                beforerequest:function(){
                    Ext.MessageBox.show({
                        title: 'Please wait',
                        msg: 'Setting up configuration...',
                        width:300,
                        wait:true,
                        modal: false
                    });
                },// on request complete hide message
                requestcomplete:function(){Ext.MessageBox.hide();}}
        });// end conn

        conn.request({
            url: <?php echo json_encode(url_for('etfw/json',true)); ?>,
            params:{id:service_id,method:'submit',
                    params:Ext.encode(send_data)
            },
            scope: this,
            success: function(resp,opt) {
                var agent = (Ext.util.JSON.decode(resp.responseText))['agent'];                
                var msg = 'Configuration applied ('+wizardTpl+')';
                Ext.ux.Logger.info(agent,msg);
                View.notify({html:msg});                

                var reloadPanel = Ext.getCmp('etfw-'+wizardTpl+'-panel-'+containerId);
                
                if(reloadPanel) reloadPanel.fireEvent('reload');
                //Ext.getCmp('etfw-squid-panel-s1').getUpdater().update();
                        //updater.refresh();                  

            },
            failure: function(resp,opt) {

                var response = [];
                response['info'] = response['error'] = resp.statusText;
                                
                if(resp.responseText)
                    response = Ext.util.JSON.decode(resp.responseText);

                Ext.Msg.show({title: 'Error',
                    buttons: Ext.MessageBox.OK,
                    msg: response['info'],
                    icon: Ext.MessageBox.ERROR});

                Ext.ux.Logger.error(response['error']);

            },timeout:40000
        }); // END Ajax request


    }


    // show the wizard
    wizard.show();    
    //.showInit();




}

/*
 * call etfw wizard(dispatcher,template,parentPanel)
 * args: dispatcher -- wizard dispatcher number db
 *       template -- template name (squid,....)
 *       parentPanel -- parent extjs component to update on success
 *
 */
new ETFW_network_wizard(<?php echo $wizard_dispatcher_id ?>,
                        <?php echo json_encode(isset($wizard_name) ? $wizard_name : ''); ?>,
                        <?php echo json_encode(isset($containerId) ? $containerId : ''); ?>
                        );
</script>
