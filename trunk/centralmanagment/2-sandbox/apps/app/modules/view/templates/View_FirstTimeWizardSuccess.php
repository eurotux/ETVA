<script>

Ext.ns("View.FirstTimeWizard");
View.FirstTimeWizard.Main = function(init_options) {

    Ext.QuickTips.init();    

    password_cardPanel = Ext.extend(Ext.ux.Wiz.Card, {                
        initComponent: function(){
             var name_tpl = [
                            {
                            xtype:'box'
                            ,height:42
                            ,autoEl:
                                {tag:'div', children:
                                    [{
                                        tag:'div'
                                        ,style:'float:left;width:31px;height:32px;'
                                        ,cls:'icon-warning'
                                    },
                                    {
                                        tag:'div'
                                        ,style:'margin-left:35px;'
                                        ,html: <?php echo json_encode(__('Recommended to change DEFAULT system password!',null,'first_time_wizard')) ?>
                                    }]
                                }
                            },
                            {
                                fieldLabel: <?php echo json_encode(__('Current Password')) ?>,
                                allowBlank: false,
                                inputType: 'password',
                                xtype: 'textfield',
                                name: 'cur_pwd',
                                minLength: 4,
                                tabIndex:1,
                                anchor: '90%'
                            },
                            {
                                fieldLabel: <?php echo json_encode(__('New Password')) ?>,
                                allowBlank: false,
                                inputType: 'password',
                                xtype: 'textfield',
                                tabIndex:2,
                                name: 'pwd',
                                minLength: 4,
                                anchor: '90%'
                            },
                            {
                                fieldLabel: <?php echo json_encode(__('Confirm New Password')) ?>,
                                allowBlank: false,
                                inputType: 'password',
                                xtype: 'textfield',
                                tabIndex:3,
                                name: 'pwd_again',
                                validator:function(v){

                                    if(v==this.ownerCt.form.items.get(1).getValue()) return true;
                                    else return <?php echo json_encode(__('Passwords do not match')) ?>;
                                },
                                minLength: 4,
                                anchor: '90%'
                            },
                            {
                                xtype:'button',
                                text: <?php echo json_encode(__('Set new password!')) ?>,
                                isFormField:true,
                                scope:this,
                                width:30,
                                labelSeparator: '',
                                handler:this.savePassword
                            }
                            ,{
                                xtype:'textfield',
                                fieldLabel: <?php echo json_encode(__('Status')) ?>,
                                anchor:'90%',
                                cls: 'nopad-border',
                                name:'passwordStatus',
                                readOnly:true,
                                width:200,
                                labelSeparator: '',
                                value : <?php echo json_encode(__('New password not set!',null,'first_time_wizard')) ?>,
                                invalidText : '',
                              //  allowBlank : false,
                                validator  : function(v){
                                    return (v!= <?php echo json_encode(__('New password not set!',null,'first_time_wizard')) ?> && v!= <?php echo json_encode(__('Error!')) ?>);
                                }
                            }];
            Ext.apply(this, {
                title        : <?php echo json_encode(__('Default password change',null,'first_time_wizard')) ?>,
                monitorValid : true,
                items : [{
                            border    : false,
                            bodyStyle : 'background:none;padding-bottom:20px;',
                            html      : <?php echo json_encode(__('Default password change',null,'first_time_wizard')) ?>
                        },name_tpl
                    ]

            });

            password_cardPanel.superclass.initComponent.call(this);
            Ext.Component.superclass.constructor.call(this);
        }
        ,savePassword:function(){

            this.form.findField('passwordStatus').setValue(<?php echo json_encode(__('Saving...')) ?>);

            this.form.submit({
                    url: 'sfGuardAuth/jsonChangePwd'
                    ,method:'POST'
                    ,scope:this
                    ,success: function(form, action) {

                        this.form.findField('passwordStatus').setValue(<?php echo json_encode(__('New password saved!')) ?>);
                        View.notify({html:<?php echo json_encode(__('New password set!',null,'first_time_wizard')) ?>});

                        Ext.Msg.show({title: <?php echo json_encode(__('Change password')) ?>,
                            buttons: Ext.MessageBox.OK,
                            msg: action.result.response,
                            icon: Ext.MessageBox.INFO});
                    },
                    failure: function(form, action) {
                        switch (action.failureType) {
                            case Ext.form.Action.CLIENT_INVALID:
                                Ext.Msg.alert(<?php echo json_encode(__('Info')) ?>, <?php echo json_encode(__('Form fields may not be submitted with invalid values!')) ?>);
                                break;
                            case Ext.form.Action.CONNECT_FAILURE:
                                Ext.Msg.alert(<?php echo json_encode(__('Info')) ?>, <?php echo json_encode(__('Communication failed!')) ?>);
                                break;
                            case Ext.form.Action.SERVER_INVALID:
                                Ext.Msg.show({title: String.format(<?php echo json_encode(__('Error {0}')) ?>,action.result.agent),
                                    buttons: Ext.MessageBox.OK,
                                    msg: action.result.info,
                                    icon: Ext.MessageBox.ERROR});
                        }
                        this.form.findField('passwordStatus').setValue(<?php echo json_encode(__('Error!')) ?>);
                    }
                    ,waitMsg:<?php echo json_encode(__('Saving...')) ?>
                });
        }

    });


    /**
     *
     * MAC POOL panel
     *
     */
    macPool_cardPanel = Ext.extend(Ext.ux.Wiz.Card, {

        initComponent: function(){


            var name_tpl = [

                    {
                    xtype:'fieldset',
                    labelWidth: 80,
                    title: <?php echo json_encode(__('MAC pool')) ?>,
                    autoHeight:true,
                    defaults     : {
                        width: 385,
                       // labelStyle : 'width:100px;',
                        border:false,
                        bodyStyle:'background:transparent;'
                    },
                    collapsed: false,
                    items :[
                            {
                            height:30,
                            layout:'column',
                            layoutConfig: {
                                fitHeight: true,
                                split: true
                            },
                            defaults:{
                                border:false,
                                bodyStyle:'background:transparent;'
                            },
                            items:[{
                                    columnWidth:.31,
                                    layout:'form',
                                    items:[{xtype:'textfield',fieldLabel: <?php echo json_encode(__('Initial MAC')) ?>,width:22,
                                            name: 'oct1',
                                            disabled:true,
                                            value:<?php echo json_encode(sprintf('%02x',sfConfig::get('app_mac_default_first_octect'))); ?>,
                                            vtype:'oct_valid'
                                        }]
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
                            maxValue:1000 ,
                            width:30,
                            allowBlank : false,scope:this,
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
                            scope:this,
                            width:30,
                            labelSeparator: '',
                            handler:this.saveMacPool


                    }
                    ,{xtype:'textfield',anchor:'90%',
                      cls: 'nopad-border',name:'poolStatus',
                      readOnly:true,
                      width:200,
                      labelSeparator: '',
                      value : <?php echo json_encode(__('MAC pool not generated!')) ?>,
                      invalidText : '',
                      allowBlank : false,
                      validator  : function(v){
                                return (v!=<?php echo json_encode(__('MAC pool not generated!')) ?> && v!= <?php echo json_encode(__('Error!')) ?>);
                      }
                      ,fieldLabel: <?php echo json_encode(__('Status')) ?>
                    }
                    ]}// end fieldset
                   ];

            Ext.apply(this, {
                title        : <?php echo json_encode(__('MAC pool generation')) ?>,
                monitorValid : true,
                items : [{
                            border    : false,
                            bodyStyle : 'background:none;padding-bottom:20px;',
                            html      : <?php echo json_encode(__('MAC pool generation')) ?>
                        },name_tpl
                    ]
            });

            macPool_cardPanel.superclass.initComponent.call(this);
            Ext.Component.superclass.constructor.call(this);
        }
        ,saveMacPool:function(){

            this.form.findField('poolStatus').setValue(<?php echo json_encode(__('Generating MAC pool...')) ?>);
            if(this.form.isValid()){

                var nicValues = this.form.getValues();
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
                        var msg = String.format(<?php echo json_encode(__('Generated pool of {0} MAC\'s')) ?>,size);
                        this.form.findField('poolStatus').setValue(msg);
                        View.notify({html:msg});

                    }
                    ,failure: function(resp,opt) {
                        this.form.findField('poolStatus').setValue(<?php echo json_encode(__('Error!')) ?>);
                        Ext.Msg.alert(<?php echo json_encode(__('Error!')) ?>,<?php echo json_encode(__('Unable to generate MAC pool')) ?>);
                    }

                }); // END Ajax request


            }else{
                this.form.findField('poolStatus').setValue(<?php echo json_encode(__('MAC pool not generated!')) ?>);
                Ext.MessageBox.alert(<?php echo json_encode(__('Error!')) ?>, <?php echo json_encode(__('Please fix the errors noted!')) ?>);
            }

        }

    });

    /*
     *
     * CONNECTIVITY panel
     *
     */

     connectivity_cardPanel = Ext.extend(Ext.ux.Wiz.Card, {
        title: <?php echo json_encode(__('System preferences')) ?>
        ,url:<?php echo json_encode(url_for('setting/jsonSetting'))?>
        ,initComponent: function(){

            // card components
            var name_tpl = [
                {
                    xtype:'fieldset',
                    labelWidth: 80,
                    title: <?php echo json_encode(__('Central Management IP')) ?>,
                    autoHeight:true,
                    defaults     : {
                        width: 385,
                       // labelStyle : 'width:100px;',
                        border:false,
                        bodyStyle:'background:transparent;'
                    }
                    ,collapsed:false
                            ,items:[
                            {
                                fieldLabel: <?php echo json_encode(__('IP address')) ?>,
                                labelStyle:'width:130px;',
                                width:100,
                                //disabled:true,
                                xtype: 'textfield',
                                readOnly:true
                                ,name: 'network_cm_management_ip'
                                ,ref: 'network_cm_management_ip'
                                //name: 'fs-ip-addr'

                            },{
                                fieldLabel: <?php echo json_encode(__('Subnet mask')) ?>,
                                labelStyle:'width:130px;',
                                width:100,
                                readOnly:true,
                                xtype: 'textfield',
                                name: 'fs_ip_addr',
                                ref: 'fs_ip_addr'
                            },{
                                fieldLabel: <?php echo json_encode(__('Default gateway')) ?>,
                                labelStyle:'width:130px;',
                                width:100,
                                readOnly:true,
                                xtype: 'textfield',
                                name: 'fs_gw_addr'
                                ,ref: 'fs_gw_addr'
                                }]
//                        }
//                    ]
                }
            ];

            var card_msg = "<div><p><b>"+<?php echo json_encode(__('Change default system preferences'))?>;
            card_msg += "</b></p><ul><li>"+<?php echo json_encode(__('VNC options'))?>;
            card_msg += "</li><li>"+<?php echo json_encode(__('Connection settings'))?>;
            card_msg += "</li></ul></div><br/><br/>";

            // apply card items
            Ext.apply(this, {
                monitorValid : true
                ,items : [{
                        border    : false,
                        bodyStyle : 'background:none;padding-bottom:20px;',
                        ctCls     : 'online-help',
                        html      : card_msg
                    },{
                        xtype:'button',
                        ref:'btnOpenPref',
                        url:<?php echo json_encode(url_for('setting/view')); ?>,
                        text: <?php echo json_encode(__('Manage Preferences')) ?>,
                        name:'btnOpenPref',
                        disabled:false,
                        isFormField:true,
                        style:'position:relative; right: 104px; bottom: 38px;',  // z-index:-10;',
                        scope:this,
                        width:30,
                        labelSeparator: ''
                        ,handler: function(){
                            var settings = Ext.getCmp('menuitm-settings');
                            settings.fireEvent('click',settings);
                        }
                    },
                    name_tpl
                    //Setting.Main({title:'item.text'})
                ]
                ,listeners: {
                    reloadData: function(panel) {
                        //alert("reloadData");
                        this.loadData();
                    }
                }
            });

            connectivity_cardPanel.superclass.initComponent.call(this);
            Ext.Component.superclass.constructor.call(this);
            this.loadData();
        }
        ,loadData: function(){
            var ip_fields = this.items.get(2);
           
            var conn = new Ext.data.Connection();
            conn.request({
                scope:this,
                url: this.url,
                waitMsg: <?php echo json_encode(__('Retrieving data...')) ?>,
                params:{params:Ext.encode(["networks"])},
                failure: function(resp,opt){
//                    domainObj = new Object();
//                    domainObj.name = field.getValue();
//                        //this.loadData(domainObj);
//
//                    if(!resp.responseText){
//                        Ext.ux.Logger.error(resp.statusText);
//                        return;
//                    }
//
//                    var response = Ext.util.JSON.decode(resp.responseText);
//                    Ext.MessageBox.alert(<?php echo json_encode(__('Error Message'))?>, response['info']);
//                    Ext.ux.Logger.error(response['error']);
                },
                success: function(response,opt){
                    var decoded_data = Ext.decode(response.responseText);
                    var data = decoded_data['data'];
                    //data['network_cm_management_ip'] = "10.10.0.1";
                    //data['network_cm_management_netmask'] = "255.255.255.0";
                    //data['network_cm_management_gateway'] = "10.10.0.254";

                    ip_fields.network_cm_management_ip.setValue(data['network_cm_management_ip']);
                    ip_fields.fs_ip_addr.setValue(data['network_cm_management_netmask']);
                    ip_fields.fs_gw_addr.setValue(data['network_cm_management_gateway']);
                }
            });
        }
     });


    /*
     *
     * NETWORKS panel
     *
     */

    network_cardPanel = Ext.extend(Ext.ux.Wiz.Card, {

        initComponent: function(){
            // the check column is created using a custom plugin
            var checkColumn = new Ext.grid.CheckColumn({
                header: 'Tagged',
                dataIndex: 'tagged',align:'center',
                width: 60
            });

            var network_grid = new Ext.grid.GridPanel({
                tools:[{id:'refresh',handler:function(e,t,p,tc){p.getStore().reload();}}],
                store: new Ext.data.JsonStore({
                    proxy: new Ext.data.HttpProxy({url:"vlan/jsonList"}),
                    totalProperty: 'total',
                    root: 'data',
                    fields: [{name:"id"},{name:"name"},{name:"tagged"},{name:"vlanid"}],
                    sortInfo: { field: 'vlanid',direction: 'ASC' },
                    remoteSort: false,
                    listeners:{
                        load:{scope:this,fn:function(st,recs,opt){

                            var hasUntagged = st.reader.jsonData.hasUntagged;
                            var vlan_id = this.form.findField('vlan_id');
                            var vlan_tagged = this.form.findField('vlan_tagged');
                        
                            if(hasUntagged){
                                vlan_tagged.setValue(true);
                                vlan_tagged.disable();
                            }
                            else
                            {
                                vlan_tagged.enable();
                                vlan_tagged.setValue(false);
                                vlan_id.clearInvalid();
                                vlan_id.disable();
                            }
                        }}
                    }
                }),
                columns:[
                    {id:'name',header:'Name',dataIndex:'name'},
                    checkColumn
                ],
                plugins:checkColumn,
                loadMask: {msg: <?php echo json_encode(__('Retrieving data...')) ?>},
                stripeRows:true,
                viewConfig:{
//                    forceFit:true,
                    emptyText: __('Empty!'),  //  emptyText Message
                    deferEmptyText:false
                },
                height:140,
                title: <?php echo json_encode(__('Networks in the system')) ?>,
                autoExpandColumn:'name',
                tbar:[{text:<?php echo json_encode(__('Remove')) ?>,ref:'../removeBtn',disabled:true,iconCls:'icon-remove',scope:this,handler:this.removeNetwork}]
            });


            network_grid.getSelectionModel().on('selectionchange', function(sm){
                network_grid.removeBtn.setDisabled(sm.getCount() < 1);

                var selected = sm.getSelected();
                if(selected && selected.data['name'] == <?php
                                            $etvamodel = $sf_user->getAttribute('etvamodel');
                                            $devices = sfConfig::get('app_device_interfaces');
                                            echo json_encode($devices[$etvamodel]['va_management']);?>)
                {
                    network_grid.removeBtn.setDisabled(true);
                    network_grid.removeBtn.setTooltip(<?php echo json_encode(__('Cannot delete default network')) ?>);

                }
                else{
                    network_grid.removeBtn.setTooltip('');
                    if(selected) network_grid.removeBtn.setDisabled(false);
                }


            });            

            var name_tpl = [
                        {
                        xtype:'fieldset',
                        title:<?php echo json_encode(__('Network')) ?>,
                        items:[
                                {layout:'hbox',border:false,
                                 pack:'center',
                                 defaults:{border:false,bodyStyle:'background:transparent;'},
                                 bodyStyle:'background:transparent;padding-bottom:10px;',
                                 align:'middle',layoutConfig:{align:'middle'},
                                 items:[
                                        {layout:'form',labelWidth: 90,width:230,
                                            items:[
                                                {
                                                    name:'vlan_name',
                                                    xtype:'textfield',
                                                    minLength: <?php echo $min_vlanname ?>,
                                                    maxLength: <?php echo $max_vlanname ?>,
                                                    width:130,
                                                    fieldLabel: <?php echo json_encode(__('Network name')) ?>,
                                                    invalidText : <?php echo json_encode(__('No spaces and only alpha-numeric characters allowed!')) ?>,
                                                    allowBlank : true,
                                                    validator  : function(v){
                                                        var t = /^[a-zA-Z0-9_]+$/;
                                                        
                                                        if(v) return t.test(v);
                                                        else return true;
                                                    }
                                                },
                                                {
                                                    xtype:'numberfield',
                                                    fieldLabel: <?php echo json_encode(__('Network ID (1...)')) ?>,
                                                    allowBlank: true,
                                                    allowNegative: false,
                                                    minValue: <?php echo $min_vlanid ?>,
                                                    maxValue: <?php echo $max_vlanid ?>,
                                                    width:50,
                                                    disabled:true,
                                                    name:'vlan_id',
                                                    scope:this,
                                                    listeners:{
                                                        specialkey:{scope:this,fn:function(field,e){

                                                            if(e.getKey()==e.ENTER){
                                                                this.saveNetwork();
                                                            }
                                                        }}
                                                    }
                                                },
                                                {
                                                    name:'vlan_tagged'
                                                    ,xtype:'checkbox'
                                                    ,fieldLabel: <?php echo json_encode(__('Tagged')) ?>
                                                    ,scope:this
                                                    ,listeners:{
                                                        check:{scope:this,fn:function(chkbox,checked){
                                                            if(checked)
                                                                this.form.findField('vlan_id').enable();
                                                            else{
                                                                this.form.findField('vlan_id').clearInvalid();
                                                                this.form.findField('vlan_id').disable();
                                                            }
                                                                
                                                                
                                                        }}
                                                    }
                                                    ,allowBlank:false
                                                }
                                            ]},
                                        {layout:'form',labelWidth: 40,width:160,
                                            items:[
                                                {
                                                    xtype:'button',
                                                    isFormField:true,
                                                    text: <?php echo json_encode(__('Add Network')) ?>,
                                                    scope:this,
                                                    handler:this.saveNetwork
                                                }
                                            ]}
                                ]}
                        ]} //end fielset network
                        ,network_grid
                        ,{
                            xtype:'textfield',
                            fieldLabel: <?php echo json_encode(__('Status')) ?>,
                            anchor:'90%',
                            cls: 'nopad-border',
                            name:'networkStatus',
                            readOnly:true,
                            width:200,
                            labelSeparator: '',
                            value : <?php echo json_encode(__('Networks not set!')) ?>,
                            invalidText : '',
                            allowBlank : false,
                            validator  : function(v){                                
                                return (v!= <?php echo json_encode(__('Networks not set!')) ?>
                                    && v!= <?php echo json_encode(__('Error!')) ?>
                                    && v!= <?php echo json_encode(__('Network could not be created!')) ?>);
                            }
                        }
            ];

            Ext.apply(this, {
                title        : <?php echo json_encode(__('Network setup')) ?>,
                monitorValid : true,
                items : [{
                            border    : false,
                            bodyStyle : 'background:none;padding-bottom:20px;',
                            html      : <?php echo json_encode(__('Network setup')) ?>
                        },name_tpl
                    ]
            });

            network_cardPanel.superclass.initComponent.call(this);
            Ext.Component.superclass.constructor.call(this);


            this.on({
                    reloadVlan:{fn:function(){network_grid.store.reload();}},
                    updateStatus:{fn:function(){                            
                            network_grid.store.reload({scope:this,callback:function(){
                                    var status = this.form.findField('networkStatus');
                                    var total = network_grid.store.getTotalCount();
                                    if(total==0) status.setValue(<?php echo json_encode(__('Networks not set!')) ?>);
                                    else status.setValue(String.format(<?php echo json_encode(__('{0} network(s) on the system')) ?>,total));
                            }});
                    }}
            });

            // load the store at the latest possible moment
            network_grid.on({
                afterlayout:{scope:this, single:true, fn:function() {
                    this.fireEvent('updateStatus');
                }}
            });

        }
        ,saveNetwork:function(){
            this.form.findField('networkStatus').setValue(<?php echo json_encode(__('Saving...')) ?>);
            var alldata = this.form.getValues();
            var can_submit = true;
            var vlan_id = alldata['vlan_id'];

            var name = alldata['vlan_name'];

            var vlan_tagged = this.getForm().findField('vlan_tagged').getValue();

            var send_data = {'name':name};
            if(vlan_tagged){
                send_data['vlan_tagged'] = 1;
                send_data['vlanid'] = vlan_id;

                if(!vlan_id) can_submit = false;
            }
            else send_data['vlan_untagged'] = 1;

            if(!send_data['name']) can_submit = false;

            if(this.form.isValid() && can_submit){

                var conn = new Ext.data.Connection({
                        listeners:{
                            // wait message.....
                            beforerequest:function(){
                                Ext.MessageBox.show({
                                    title: <?php echo json_encode(__('Please wait...')) ?>,
                                    msg: <?php echo json_encode(__('Creating network...')) ?>,
                                    width:300,
                                    wait:true,
                                    modal: true
                                });
                            }// on request complete hide message
                            ,requestcomplete:function(){Ext.MessageBox.hide();}
                            ,requestexception:function(c,r,o){
                                Ext.MessageBox.hide();
                                Ext.Ajax.fireEvent('requestexception',c,r,o);}
                        }
                    });// end conn

                conn.request({
                        url: <?php echo json_encode(url_for('vlan/jsonCreate'))?>,
                        params: send_data,
                        scope:this,
                        success: function(resp,opt) {

                            var response = Ext.util.JSON.decode(resp.responseText);
                            var txt = response['response'];
                            var agent = response['agent'];

                            var length = txt.length;

                            for(var i=0;i<length;i++){
                                Ext.ux.Logger.info(agent,txt[i]);
                            }
                            var msg = <?php echo json_encode(__('Network added to system!')) ?>;

                            this.form.findField('networkStatus').setValue(msg);
                            View.notify({html:msg});

                            this.fireEvent('reloadVlan');

                        },
                        failure: function(resp,opt) {

                            var response = Ext.decode(resp.responseText);

                            if(response && resp.status!=401){
                                var errors = response['error'];


                                // vlan not added to DB
                                if(!response['ok']){
                                    View.notify({html:<?php echo json_encode(__('Network could not be created!')) ?>});
                                    this.form.findField('networkStatus').setValue(<?php echo json_encode(__('Error!')) ?>);
                                    response['ok'] = [];
                                }else{

                                     View.notify({html: <?php echo json_encode(__('Network added to system!')) ?>});
                                     this.form.findField('networkStatus').setValue(<?php echo json_encode(__('Network added to system!')) ?>);
                                }

                                var oks = response['ok'];
                                var errors_length = errors.length;
                                var oks_length = oks.length;
                                var agents = '<br>';
                                var logger_errormsg = [String.format(<?php echo json_encode(__('Network {0} could not be initialized: {1}')) ?>,name ,'')];
                                var logger_okmsg = [String.format(<?php echo json_encode(__('Network {0} initialized: ')) ?>,name)];
                                var logger_error = [];
                                var logger_ok = [];
                                for(var i=0;i<errors_length;i++){
                                    agents += '<b>'+errors[i]['agent']+'</b> - '+errors[i]['error']+'<br>';
                                    logger_error[i] = '<b>'+errors[i]['agent']+'</b>('+errors[i]['error']+')';
                                }

                                for(var i=0;i<oks_length;i++){
                                    logger_ok[i] = '<b>'+oks[i]['agent']+'</b>';
                                }

                                logger_errormsg += logger_error.join(', ');
                                logger_okmsg += logger_ok.join(', ');

                                Ext.ux.Logger.error(response['agent'],logger_errormsg);
                                if(logger_ok.length>0) Ext.ux.Logger.info(response['agent'],logger_okmsg);

                                var msg = String.format(<?php echo json_encode(__('Network {0} could not be initialized: {1}')) ?>,name,'<br>'+agents);



                                Ext.Msg.show({
                                    title: String.format(<?php echo json_encode(__('Error {0}')) ?>,response['agent']),
                                    width:300,
                                    buttons: Ext.MessageBox.OK,
                                    msg: msg,
                                    icon: Ext.MessageBox.ERROR});
                            }

                            this.fireEvent('reloadVlan');


                        }
                    });// END Ajax request


            }else{
                this.form.findField('networkStatus').setValue(<?php echo json_encode(__('Network could not be created!')) ?>);
                Ext.MessageBox.alert(<?php echo json_encode(__('Error!')) ?>, <?php echo json_encode(__('Please fix the errors noted!')) ?>);
            }

        }
        ,removeNetwork:function(g){
            var grid = g.ownerCt.ownerCt;
            var sm = grid.getSelectionModel();
            var sel = sm.getSelected();
            if (sm.hasSelection()){
                Ext.Msg.show({
                    title: <?php echo json_encode(__('Remove network')) ?>,
                    buttons: Ext.MessageBox.YESNO,                   
                    msg: String.format(<?php echo json_encode(__('Remove network {0} ?')) ?>,sel.data['name']),scope:this,
                    fn: function(btn){

                        if (btn == 'yes'){

                            var conn = new Ext.data.Connection({
                                    listeners:{
                                    // wait message.....
                                    beforerequest:function(){
                                        Ext.MessageBox.show({
                                            title: <?php echo json_encode(__('Please wait...')) ?>,
                                            msg: <?php echo json_encode(__('Removing network...')) ?>,
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
                                url: <?php echo json_encode(url_for('vlan/jsonRemove'))?>,
                                params: {'name': sel.data['name']},
                                scope:this,
                                success: function(resp,opt) {

                                    var response = Ext.util.JSON.decode(resp.responseText);
                                    var txt = response['response'];
                                    var agent = response['agent'];

                                    var length = txt.length;

                                    for(var i=0;i<length;i++){
                                        Ext.ux.Logger.info(agent,txt[i]);
                                    }

                                    this.fireEvent('updateStatus');

                                }
                                ,failure: function(resp,opt) {

                                    var response = Ext.decode(resp.responseText);

                                    if(response && resp.status!=401){
                                        var errors = response['error'];
                                        var oks = response['ok'];
                                        var errors_length = errors.length;
                                        var oks_length = oks.length;
                                        var agents = '<br>';

                                        var logger_errormsg = [String.format(<?php echo json_encode(__('Network {0} could not be uninitialized: {1}')) ?>,sel.data['name'] ,'')];
                                        var logger_okmsg = [String.format(<?php echo json_encode(__('Network {0} uninitialized: ')) ?>,sel.data['name'])];
                                        
                                        var logger_error = [];
                                        var logger_ok = [];
                                        for(var i=0;i<errors_length;i++){
                                            agents += '<b>'+errors[i]['agent']+'</b> - '+errors[i]['error']+'<br>';
                                            logger_error[i] = '<b>'+errors[i]['agent']+'</b>('+errors[i]['error']+')';
                                        }

                                        for(var i=0;i<oks_length;i++){
                                            logger_ok[i] = '<b>'+oks[i]['agent']+'</b>';
                                        }

                                        logger_errormsg += logger_error.join(', ');
                                        logger_okmsg += logger_ok.join(', ');

                                        Ext.ux.Logger.error(response['agent'],logger_errormsg);
                                        if(logger_ok.length>0) Ext.ux.Logger.info(response['agent'],logger_okmsg);
                                        
                                        var msg = String.format(<?php echo json_encode(__('Network {0} could not be uninitialized: {1}')) ?>,sel.data['name'],'<br>'+agents);

                                        this.fireEvent('updateStatus');                                       

                                        Ext.Msg.show({
                                            title: String.format(<?php echo json_encode(__('Error {0}')) ?>,response['agent']),
                                            width:300,
                                            buttons: Ext.MessageBox.OK,
                                            msg: msg,
                                            icon: Ext.MessageBox.ERROR});
                                    }



                                }
                            });// END Ajax request

                        }//END button==yes
                    }// END fn
                }); //END Msg.show
            }//END if
        }
    });


    //xen name cardPanel

    var viewerSize = Ext.getBody().getViewSize();
    var windowHeight = viewerSize.height * 0.95;
    windowHeight = Ext.util.Format.round(windowHeight,0);

    var isFullFirstTimeSetupWizard = false;
    <?php if($sf_user->getGuardUser()->getIsSuperAdmin()): ?>
    isFullFirstTimeSetupWizard = <?php echo ($full_first_time_wizard ? 1: 0) ?>;
    if( init_options['full-first-time-setup-wizard'] ){
        isFullFirstTimeSetupWizard = true;
    }
    <?php endif; ?>

    var cards = [
            // card with welcome message
                new Ext.ux.Wiz.Card({
                    title : <?php echo json_encode(__('Welcome')) ?>,
                    defaults     : {
                        labelStyle : 'font-size:11px;width:140px;'
                    },
                    items : [{
                            border    : false,
                            bodyStyle : 'background:none;',
                            html      : <?php echo json_encode(__('Welcome to the one-time configuration utility.<br>Follow the steps to do system initial setup',null,'first_time_wizard')) ?>
                        }]
                })
                ,new password_cardPanel({id:'ft-wiz-password'})
                ];

    if( isFullFirstTimeSetupWizard ){
        cards.push(new macPool_cardPanel({id:'ft-wiz-macpool'})
                    ,new connectivity_cardPanel({id:'ft-wiz-preferences'}));
    }

    //var startupCard = new startup_cardPanel({id:'server-wiz-startup'});
    var config = {etvamodel:<?php echo json_encode($sf_user->getAttribute('etvamodel')); ?>};
    switch(config.etvamodel){
        case 'enterprise' :
                        if( isFullFirstTimeSetupWizard ){
                            cards.push(new network_cardPanel({id:'ft-wiz-network'}));
                        }
                        break;
        default           :
                        break;
    }

    cards.push(// finish card with finish-message
            new Ext.ux.Wiz.Card({
                title        : <?php echo json_encode(__('Finished!')) ?>,
                monitorValid : true,
                items : [{
                        border    : false,
                        bodyStyle : 'background:none;',
                        html      : <?php echo json_encode(__('Thank you! Initial system setup has be done!',null,'first_time_wizard')) ?>
                    }]
            }));

    var wizard = new Ext.ux.Wiz({
        border:true,
        title : <?php echo json_encode(__('One-Time Setup Wizard',null,'first_time_wizard')) ?>,
        tools:[{id:'help', qtip: __('Help'),handler:function(){View.showHelp({anchorid:'help-firttimeW-main',autoLoad:{ params:'mod=view'},title: <?php echo json_encode(__('One-Time Setup Wizard Help')) ?>});}}],
        headerConfig : {
            title : <?php echo json_encode(__('One-Time setup',null,'first_time_wizard')) ?>
        },
        width:610,
        height:520,
        westConfig : {
            width : 185
        },
        cardPanelConfig : {
            defaults : {
                baseCls    : 'x-small-editor',
                bodyStyle  : 'border:none;padding:15px 15px 15px 15px;background-color:#F6F6F6;',                
                border     : false
            }
        },
        cards: cards
    });


    // show the wizard
    wizard.show();
    //.showInit();




}
</script>
