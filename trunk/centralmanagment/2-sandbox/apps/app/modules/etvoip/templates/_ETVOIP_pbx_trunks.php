<script>
Ext.ns('ETVOIP.PBX.Trunks');


ETVOIP.PBX.Trunks.Form = Ext.extend(Ext.form.FormPanel, {
    border:true
    ,defaults:{border:false}
    ,labelWidth:145
    ,url:<?php echo json_encode(url_for('etvoip/json'))?>
    ,layout:'fit'
    ,monitorValid:true
    ,initComponent:function(){

        this.advance_off = String.format(<?php echo json_encode(__('Advanced Mode: {0}')) ?>,'OFF');
        this.advance_on = String.format(<?php echo json_encode(__('Advanced Mode: {0}')) ?>,'ON');

        this.items = this.buildForm();
        this.tbar = [{
                        text: this.advance_off,
                        itemId:'advance',
                        enableToggle:true,
                        scope:this,
                        toggleHandler:function(item,pressed){
                            
                            if(pressed){                                
                                this.showAdvanced();
                            }else{                                
                                this.showBasic();
                            }

                        }
                    }];
        this.buttons = [{
                            text: __('Save'),
                            handler:this.save,
                            scope:this,
                            formBind:true
                        }
                        ,{
                            text: __('Cancel'),
                            scope:this,
                            handler:function(){
                                this.fireEvent('onCancel');
                            }
                        }];

        // call parent
        ETVOIP.PBX.Trunks.Form.superclass.initComponent.apply(this, arguments);


    } // eo function initComp
    ,buildForm:function(){

        var configuration =
            [
                {
                    layout: 'vbox',
                    defaults:{border:false},
                    layoutConfig:{align:'stretch'},
                    items:[
                        //top
                        {
                            flex:0,
                            items:[
                                    {
                                        xtype:'displayfield',                                        
                                        style:'padding:5px;',
                                        ref: '../../info_panel'
                                    }
                            ]
                        }
                        //middle
                        ,
                        {
                            flex:1,
                            layout:'fit',
                            items:[
                                {
                                    border:false,
                                    bodyStyle:'padding:5px;'
                                    ,layout: {
                                        type: 'hbox',
                                        align: 'stretch'  // Child items are stretched to full width
                                    }
                                    ,defaults:{layout:'form',border:false,defaultType:'textfield',autoScroll:true,border:false}
                                    ,items:[
                                        {
                                            flex:1,
                                            items:[
                                                {
                                                    xtype:'hidden',
                                                    name:'action'
                                                }
                                                ,{
                                                    xtype:'hidden',
                                                    name:'service_id'
                                                }
                                                ,{
                                                    xtype:'hidden',
                                                    name:'tech'
                                                }
                                                ,{
                                                    xtype:'hidden',
                                                    name:'provider'
                                                }
                                                ,{
                                                    xtype:'hidden',
                                                    name:'trunknum'
                                                }
                                                /*
                                                 * General settings
                                                 */
                                                ,{
                                                    xtype:'fieldset',title: <?php echo json_encode(__('General settings')) ?>,anchor:'95%',
                                                    defaultType:'textfield',
                                                    ref:'../../../../general_panel',
                                                    items:[
                                                        {
                                                            name: 'trunk_name',
                                                            fieldLabel : <?php echo json_encode(__('Trunk Description')) ?>,
                                                            hint: <?php echo  json_encode(__('Descriptive Name for this Trunk')) ?>,
                                                            allowBlank : false
                                                        }
                                                        ,{
                                                            name: 'outcid',
                                                            fieldLabel : <?php echo json_encode(__('Outbound CID')) ?>,
                                                            hint: <?php echo json_encode(__('Caller ID for calls placed out on this trunk<br><br>Format: <b><#######></b>. You can also use the format: "hidden" <b><#######></b> to hide the CallerID sent out over Digital lines if supported (E1/T1/J1/BRI/SIP/IAX)')) ?>
                                                        }
                                                        ,{
                                                            xtype:'combo',
                                                            fieldLabel: <?php echo json_encode(__('CID Options')) ?>,
                                                            name: 'keepcid',
                                                            hiddenName: 'keepcid',
                                                            ref: 'keepcid',
                                                            hint: <?php echo json_encode(__('Determines what CIDs will be allowed out this trunk. IMPORTANT: EMERGENCY CIDs defined on an extension/device will ALWAYS be used if this trunk is part of an EMERGENCY Route regardless of these settings.<br />Allow Any CID: all CIDs including foreign CIDS from forwarded external calls will be transmitted.<br />Block Foreign CIDs: blocks any CID that is the result of a forwarded call from off the system. CIDs defined for extensions/users are transmitted.<br />Remove CNAM: this will remove CNAM from any CID sent out this trunk<br />Force Trunk CID: Always use the CID defined for this trunk except if part of any EMERGENCY Route with an EMERGENCY CID defined for the extension/device')) ?>,
                                                            store: new Ext.data.ArrayStore({
                                                                fields: ['value', 'name'],
                                                                data: [['off','Allow Any CID'],
                                                                       ['on','Block Foreign CIDs'],
                                                                       ['cnum','Remove CNAM'],
                                                                       ['all','Force Trunk CID']]
                                                            }),
                                                            displayField:'name',
                                                            valueField:'value',
                                                            typeAhead:true,
                                                            value:'off',
                                                            mode:'local',
                                                            forceSelection: true,
                                                            triggerAction: 'all',
                                                            selectOnFocus:true,
                                                            width: 120
                                                        }
                                                        ,{
                                                            xtype:'numberfield',
                                                            name:'maxchans',
                                                            ref:'maxchans',
                                                            fieldLabel : <?php echo json_encode(__('Maximum Channels')) ?>,
                                                            hint: <?php echo json_encode(__('Controls the maximum number of outbound channels (simultaneous calls) that can be used on this trunk. Inbound calls are not counted against the maximum. Leave blank to specify no maximum')) ?>
                                                        }
                                                        ,{
                                                            xtype: 'checkbox',
                                                            fieldLabel: <?php echo json_encode(__('Disable Trunk')) ?>,
                                                            boxLabel: __('Disable'),
                                                            name: 'disabletrunk', inputValue:'on',
                                                            ref:'disabletrunk',
                                                            hint: <?php echo json_encode(__('Check this to disable this trunk in all routes where it is used')) ?>

                                                        }
                                                        ,{
                                                            layout:'table',
                                                            xtype:'panel',
                                                            ref: 'failtrunk',
                                                            border:false,
                                                            layoutConfig: {columns:2},
                                                            defaults:{layout:'form',border:false},
                                                            items:[
                                                                {
                                                                    items:[
                                                                        {
                                                                            name       : 'failtrunk',
                                                                            //disabled: true,
                                                                            xtype: 'textfield',
                                                                            fieldLabel : <?php echo json_encode(__('Monitor Trunk Failures')) ?>,
                                                                            hint: <?php echo json_encode(__('If checked, supply the name of a custom AGI Script that will be called to report, log, email or otherwise take some action on trunk failures that are not caused by either NOANSWER or CANCEL')) ?>
                                                                        }
                                                                    ]
                                                                }
                                                                // 2nd col
                                                                ,{
                                                                    bodyStyle:'padding-left:5px;',
                                                                    items:[
                                                                        {
                                                                            xtype: 'checkbox',
                                                                            hideLabel:true,
                                                                            boxLabel: __('Enable'),
                                                                            name: 'failtrunk_enable', inputValue:'1'
                                                                        }
                                                                    ]
                                                                }
                                                            ]
                                                        }
                                                    ]
                                                }
                                                /*
                                                 * Incoming settings
                                                 */
                                                ,{
                                                    xtype:'fieldset',title: <?php echo json_encode(__('Incoming Settings')) ?>,anchor:'95%',
                                                    defaultType:'textfield',
                                                    items:[],
                                                    ref:'../../../../incoming_settings_panel'
                                                }
                                            ]//end items flex
                                        }
                                        ,{
                                            flex:1,
                                            items:[
                                                /*
                                                 * Outgoing dial rules
                                                 */
                                                {
                                                    xtype:'fieldset',title: <?php echo json_encode(__('Outgoing dial rules')) ?>,anchor:'95%',
                                                    defaultType:'textfield',
                                                    ref:'../../../../outgoing_dial_panel',
                                                    items:[
                                                        {
                                                            name: 'dialrules',
                                                            xtype:'textarea',                                                            
                                                            fieldLabel : <?php echo json_encode(__('Dial Rules')) ?>,
                                                            hint: <?php echo json_encode(__('A Dial Rule controls how calls will be dialed on this trunk. It can be used to add or remove prefixes. Numbers that don\'t match any patterns defined here will be dialed as-is. Note that a pattern without a + or | (to add or remove a prefix) will not make any changes but will create a match. Only the first matched rule will be executed and the remaining rules will not be acted on.<br />
                                                        <br /><b>Rules:</b><br /><strong>X</strong> matches any digit from 0-9<br />
                                                        <strong>Z</strong> matches any digit from 1-9<br />
                                                        <strong>N</strong> matches any digit from 2-9<br />
                                                        <strong>[1237-9]</strong> matches any digit or letter in the brackets (in this example, 1,2,3,7,8,9)<br />
                                                        <strong>.</strong> wildcard, matches one or more characters (not allowed before a | or +)<br />
                                                        <strong>|</strong> removes a dialing prefix from the number (for example, 613|NXXXXXX would match when some dialed "6135551234" but would only pass "5551234" to the trunk)	<strong>+</strong> adds a dialing prefix from the number (for example, 1613+NXXXXXX would match when some dialed "5551234" and would pass "16135551234" to the trunk)<br />
                                                        <br />You can also use both + and |, for example: 01+0|1ZXXXXXXXXX would match "016065551234" and dial it as "0116065551234" Note that the order does not matter, eg. 0|01+1ZXXXXXXXXX does the same thing')) ?>
                                                        }                                                        
                                                        ,{
                                                            xtype: 'numberfield',
                                                            name: 'dialoutprefix',
                                                            fieldLabel : <?php echo json_encode(__('Outbound Dial Prefix')) ?>,
                                                            hint: <?php echo json_encode(__('The outbound dialing prefix is used to prefix a dialing string to all outbound calls placed on this trunk. For example, if this trunk is behind another PBX or is a Centrex line, then you would put 9 here to access an outbound line. Another common use is to prefix calls with \'w\' on a POTS line that need time to obtain dial tone to avoid eating digits.<br><br>Most users should leave this option blank')) ?>
                                                        }
                                                    ]
                                                }
                                                /*
                                                 * Outgoing settings
                                                 */
                                                ,{
                                                    xtype:'fieldset',title: <?php echo json_encode(__('Outgoing Settings')) ?>,anchor:'95%',
                                                    defaultType:'textfield',
                                                    items:[],
                                                    ref:'../../../../outgoing_settings_panel'
                                                }
                                                /*
                                                 * Registration
                                                 */
                                                ,{
                                                    xtype:'fieldset',title: <?php echo json_encode(__('Registration')) ?>,anchor:'95%',
                                                    defaultType:'textfield',
                                                    items:[],
                                                    ref:'../../../../registration_panel'
                                                }
                                            ]
                                        }
                                    ]// end items hbox
                                }// end hbox
                            ]
                        }// end middle
                    ]
                }// end vbox
            ];

        return configuration;

    }
    ,getFocusField:function(){
        return this.getForm().findField('trunk_name');
    }
    ,showAdvanced:function(){
        var bt = this.getTopToolbar().getComponent('advance');
        bt.setText(this.advance_on);
        bt.toggle(true);

        this.outgoing_dial_panel.show();
        
        var keepcid = this.general_panel.keepcid;
        keepcid.showAll(true);

        var maxchans = this.general_panel.maxchans;
        maxchans.showAll(true);

        var disabletrunk = this.general_panel.disabletrunk;
        disabletrunk.showAll(true);

        var failtrunk = this.general_panel.failtrunk;
        failtrunk.show();

    }
    ,showBasic:function(){
        var bt = this.getTopToolbar().getComponent('advance');
        bt.setText(this.advance_off);
        bt.toggle(false);
        
        this.outgoing_dial_panel.hide();
        
        var maxchans = this.general_panel.maxchans;
        maxchans.showAll(false);        

        var keepcid = this.general_panel.keepcid;
        keepcid.showAll(false);

        var disabletrunk = this.general_panel.disabletrunk;
        disabletrunk.showAll(false);
        
        var failtrunk = this.general_panel.failtrunk;
        failtrunk.hide();


    }
    //populate form fiels without ajax call
    ,buildLoad:function(record){

        this.getForm().clearInvalid();
        this.incoming_settings_panel.removeAll(true);
        this.outgoing_settings_panel.removeAll(true);
        this.registration_panel.removeAll(true);

        switch(record.data['tech']){
            case 'enum' :
                        this.incoming_settings_panel.hide();
                        this.registration_panel.hide();
                        this.outgoing_settings_panel.hide();
                        this.info_panel.hide();
                        break;
            case 'dundi':


                        this.info_panel.setValue('FreePBX offers limited support for DUNDi trunks and additional manual configuration is required. The trunk name should correspond to the [mappings] section of the remote dundi.conf systems. For example, you may have a mapping on the remote system, and corresponding configurations in dundi.conf locally, that looks as follows:\n\
                                    [mappings]\n\
                                    priv => dundi-extens,0,IAX2,priv:${SECRET}@218.23.42.26/${NUMBER},noparital\n\
                                    In this example, you would create this trunk and name it priv. You would then create the corresponding IAX2 trunk with proper settings to work with DUNDi. This can be done by making an IAX2 trunk in FreePBX or by using the iax_custom.conf file.The dundi-extens context in this example must be created in extensions_custom.conf. This can simply include contexts such as ext-local, ext-intercom-users, ext-paging and so forth to provide access to the corresponding extensions and features provided by these various contexts and generated by FreePBX. ');
                        this.info_panel.show();
                        break;
            case 'zap'  :
                        this.outgoing_settings_panel.add([
                                {
                                    name: 'channelid',
                                    value: 'g0',
                                    fieldLabel : <?php echo json_encode(__('Zap Identifier (trunk name)')) ?>,
                                    hint: 'ZAP channels are referenced either by a group number or channel number (which is defined in zapata.conf).  <br><br>The default setting is <b>g0</b> (group zero)',
                                    allowBlank : false
                                }]);
                        this.incoming_settings_panel.hide();
                        this.registration_panel.hide();
                        this.info_panel.hide();
                        this.outgoing_settings_panel.show();
                        break;
            case 'sip'  :
            case 'iax2' :
                        this.incoming_settings_panel.add([
                                {
                                    name: 'usercontext',
                                    fieldLabel : <?php echo json_encode(__('USER Context')) ?>,
                                    hint: <?php echo json_encode(__('This is most often the account name or number your provider expects.<br><br>This USER Context will be used to define the below user details')) ?>
                                }
                                ,{
                                    xtype:'textarea',
                                    anchor:'90% 90%',
                                    name: 'userconfig',
                                    value: 'secret=***password***\r\ntype=user\r\ncontext=from-trunk',
                                    fieldLabel : <?php echo json_encode(__('USER Details')) ?>,
                                    hint: <?php echo json_encode(__('Modify the default USER connection parameters for your VoIP provider.<br><br>You may need to add to the default lines listed below, depending on your provider.<br /><br />WARNING: Order is important as it will be retained. For example, if you use the "allow/deny" directives make sure deny comes first')) ?>
                                }
                                ]);

                        this.outgoing_settings_panel.add([
                                {
                                    name: 'channelid',
                                    fieldLabel : <?php echo json_encode(__('Trunk Name')) ?>,
                                    hint: <?php echo json_encode(__('Give this trunk a unique name.  Example: myiaxtel')) ?>,
                                    allowBlank : false
                                }
                                ,{
                                    xtype:'textarea',
                                    anchor:'90% 90%',
                                    name: 'peerdetails',
                                    value: 'host=***provider ip address***\r\nusername=***userid***\r\nsecret=***password***\r\ntype=peer',
                                    fieldLabel : <?php echo json_encode(__('PEER Details')) ?>,
                                    hint: <?php echo json_encode(__('Modify the default PEER connection parameters for your VoIP provider.<br><br>You may need to add to the default lines listed below, depending on your provider.<br /><br />WARNING: Order is important as it will be retained. For example, if you use the "allow/deny" directives make sure deny comes first')) ?>
                                }
                                ]);

                        this.registration_panel.add([
                                {
                                    name: 'register',
                                    fieldLabel : <?php echo json_encode(__('Register String')) ?>,
                                    hint: <?php echo json_encode(__('Most VoIP providers require your system to REGISTER with theirs. Enter the registration line here.<br><br>example:<br><br>username:password@switch.voipprovider.com.<br><br>Many providers will require you to provide a DID number, ex: username:password@switch.voipprovider.com/didnumber in order for any DID matching to work')) ?>
                                }
                                ]);

                        this.incoming_settings_panel.show();
                        this.outgoing_settings_panel.show();
                        this.registration_panel.show();
                        this.info_panel.hide();
                        break;

        }

        this.showBasic();
        this.getForm().loadRecord(record);

        
        //if(this.rendered) this.getForm().loadRecord(record);
        //else this.on('render',function(cp){cp.getForm().loadRecord(record);});

    }
    ,onRender:function() {
        // call parent
        ETVOIP.PBX.Trunks.Form.superclass.onRender.apply(this, arguments);

        // set wait message target
        this.getForm().waitMsgTarget = this.getEl();
        
    } // eo function onRender
    ,remoteLoad:function(trunknum){
        var service_id = this.getForm().findField('service_id').getValue();
        
        this.load({
            url:this.url,
            waitMsg: <?php echo json_encode(__('Loading trunk data...')) ?>,
            params:{id:service_id,method:'get_trunk',params:Ext.encode({'trunknum':trunknum})},
            scope:this

        });

    }
    ,save:function(){


        var alldata = this.form.getValues();
        var send_data = new Object();
        var send_data = alldata;

        var outcid = this.getForm().findField('outcid');
        var keepcid_cmb = this.getForm().findField('keepcid');
        var keepcid_val = keepcid_cmb.getValue();
        
        if( keepcid_val == 'on' || keepcid_val == 'all'){
            var msg = String.format(<?php echo json_encode(__('You must define an Outbound Caller ID when Choosing this {0} value')) ?>,<?php echo json_encode(__('CID Options'))?>);
            outcid.markInvalid(msg);
            this.showAdvanced();
            Ext.MessageBox.alert(<?php echo json_encode(__('Error!')) ?>, msg);
            return false;
        }
                

        if (this.form.isValid()) {

            var service_id = alldata['service_id'];
            var method = 'add_trunk';
            var wait_msg = <?php echo json_encode(__('Adding trunk...'))?>;
            var ok_msg = <?php echo json_encode(__('Added trunk {0}'))?>;

            if(alldata['action'] == 'edit'){
                method = 'edit_trunk';
                wait_msg = <?php echo json_encode(__('Updating trunk...'))?>;
                ok_msg = <?php echo json_encode(__('Updated trunk {0}'))?>;
            }

            var conn = new Ext.data.Connection({
                listeners:{
                    scope:this,
                    beforerequest:function(){
                        this.getEl().mask(wait_msg);
                    },// on request complete hide message
                    requestcomplete:function(){
                        this.getEl().unmask();
                    }
                    ,requestexception:function(c,r,o){
                        this.getEl().unmask();
                        Ext.Ajax.fireEvent('requestexception',c,r,o);
                    }
                }
            });// end conn

            conn.request({
                url: this.url,
                params:{id:service_id,method:method,params:Ext.encode(send_data)},
                // everything ok...
                success: function(resp,opt){
                    var response = Ext.util.JSON.decode(resp.responseText);
                    var msg = String.format(ok_msg,alldata['trunk_name']);
                    View.notify({html:msg});
                    this.fireEvent('onSave');

                },scope:this
            });// END Ajax request


        }else{
            Ext.MessageBox.alert(<?php echo json_encode(__('Error!')) ?>, <?php echo json_encode(__('Please fix the errors noted!')) ?>);
        }
    }





});


ETVOIP.PBX.Trunks.Main = Ext.extend(Ext.Panel, {
    layout:'fit',
    title: <?php echo json_encode(__('Trunks')) ?>,
    initComponent:function(){

        var trunksStore = new Ext.data.JsonStore({
                proxy:new Ext.data.HttpProxy({url:<?php echo json_encode(url_for('etvoip/json'))?>}),
                totalProperty: 'total',
                baseParams:{id:this.service_id,method:'get_trunks'},
                root: 'data',
                fields: ['trunkid','name', 'tech'],
                listeners:{
                    load:{scope:this,fn:function(st,rcs,opts){

                        var need_reload = st.reader.jsonData.need_reload;
                        var pbxPanel = this.ownerCt;

                        pbxPanel.fireEvent('notify_reload',pbxPanel,need_reload);

                    }
                }},scope:this
        });

        this.trunksGrid = new Ext.grid.GridPanel({
                            border: false,
                            store: trunksStore,
                            columns: [
                                        {
                                            header: "Name", sortable: false, dataIndex: 'name'
                                        }
                                        ,{
                                            header: "Technology", sortable: false, dataIndex: 'tech'
                                        }],
                            loadMask: true,
                            sm: new Ext.grid.RowSelectionModel({
                                    singleSelect: true
                            }),
                            viewConfig:{
                                forceFit:true,
                                emptyText: __('Empty!'),  //  emptyText Message
                                deferEmptyText:false
                            },
                            stripeRows: true,
                            // add paging toolbar
                            bbar: new Ext.ux.grid.TotalCountBar({
                                            store:trunksStore,
                                            displayInfo:true
                            }),
                            height:115,
                            tbar: new Ext.ux.StatusBar({
                                defaultText: '',
                                //  defaultIconCls: '',
                                statusAlign: 'right',
                                items: [
                                    {
                                    
                                        iconCls: 'icon-add'
                                        ,text: <?php echo json_encode(__('Add trunk')) ?>
                                        ,menu:[                                                
                                                {text: <?php echo json_encode(__('SIP Trunk')) ?>,scope:this,handler:function(){
                                                        (function(){this.loadWindowForm({type: 'sip'})}).defer(10,this);
                                                        return true;
                                                }}
                                                ,{text: <?php echo json_encode(__('IAX2 Trunk')) ?>,scope:this,handler:function(){
                                                        (function(){this.loadWindowForm({type: 'iax2'})}).defer(10,this);
                                                        return true;
                                                }}
//                                                ,{text: <?php //echo json_encode(__('ZAP Trunk')) ?>,scope:this,handler:function(){
//                                                        (function(){this.loadWindowForm({type: 'zap'})}).defer(10,this);
//                                                        return true;
//                                                }}
//                                                ,{text:'ENUM Trunk',scope:this,handler:function(){
//                                                        (function(){this.loadWindowForm({type: 'enum'})}).defer(10,this);
//                                                        return true;
//                                                }}
//                                                ,{text:'DUNDi Trunk',scope:this,handler:function(){
//                                                        (function(){this.loadWindowForm({type: 'dundi'})}).defer(10,this);
//                                                        return true;
//                                                }}
//                                                ,{text:'CUSTOM Trunk',scope:this,handler:function(){
//                                                        (function(){this.loadWindowForm({type: 'custom'})}).defer(10,this);
//                                                        return true;
//                                                }}
                                        ]
                                    }
                                    ,'-'
                                    ,{
                                        text: <?php echo json_encode(__('Edit trunk')) ?>
                                        ,ref: 'editBtn'
                                        ,iconCls:'icon-edit-record'
                                        ,tooltip: <?php echo json_encode(__('Edit selected trunk')) ?>
                                        ,disabled:true
                                        ,scope:this
                                        ,handler: function(){
                                            var s = this.trunksGrid.getSelectionModel().getSelected();
                                            if(s){
                                                (function(){this.loadWindowForm({
                                                                                    type: s.data['tech'],
                                                                                    action:'edit',
                                                                                    trunknum:s['data']['trunkid'],
                                                                                    trunk:s['data']['name']
                                                                                })}).defer(10,this);
                                                return true;
                                            }   
                                        }
                                    }
                                    ,'-'
                                    ,{
                                        text: <?php echo json_encode(__('Remove trunk')) ?>
                                        ,ref: 'removeBtn'
                                        ,tooltip: <?php echo json_encode(__('Remove selected trunk')) ?>
                                        ,iconCls:'remove'
                                        ,disabled:true
                                        ,scope:this
                                        ,handler: function(){
                                            var s = this.trunksGrid.getSelectionModel().getSelected();
                                            if(s) this.removeTrunk(s);
                                        }
                                    },'->','-',
                                    {
                                        text: <?php echo json_encode(__('Apply changes')) ?>
                                        ,iconCls:'page-save'
                                        ,tooltip: String.format(<?php echo json_encode(__("You have made changes to the configuration that have not yet been applied. When you are finished making all changes, click on <b>{0}</b> to put them into effect.")) ?>, <?php echo json_encode(__('Apply changes')) ?>)
                                        ,scope:this
                                        ,handler: function(){
                                            this.applyChanges();
                                        }

                                    }
                                    ]})
        }); //end grid

        this.trunksGrid.getSelectionModel().on('selectionchange', function(sm){
            var topBar = this.trunksGrid.getTopToolbar();
            topBar.editBtn.setDisabled(sm.getCount() < 1);
            topBar.removeBtn.setDisabled(sm.getCount() < 1);                        
        },this);


        this.items = [this.trunksGrid];

        ETVOIP.PBX.Trunks.Main.superclass.initComponent.apply(this);

    }
    ,onRender:function() {

        // call parent
        ETVOIP.PBX.Trunks.Main.superclass.onRender.apply(this, arguments);

        // loads form after initial layout
        this.on('afterlayout', this.reloadTrunks, this, {single:true});

    } // eo function onRender
    ,applyChanges:function(){
        this.fireEvent('onReloadAsterisk');
    }
    ,checkAsteriskReload:function(need){
        if(need){
            this.trunksGrid.getTopToolbar().setStatus({
								text: <?php echo json_encode(__('<i>Configuration changed</i>')) ?>,
								iconCls: 'icon-status-warning'
            });

        }
        else if(need==0){
            this.trunksGrid.getTopToolbar().clearStatus();
        }

    }
    ,reloadTrunks:function(){
        this.trunksGrid.store.reload();

    }
    ,loadWindowForm:function(fType){

        var trunk = '';
        var action = 'add';
        var title = <?php echo json_encode(__('Add {0} Trunk {1}')) ?>;
        switch(fType.action){
            case 'edit'  :
                                title = <?php echo json_encode(__('Edit {0} Trunk {1}')) ?>;
                                trunk = fType.trunk;
                                action = fType.action;
                                break;
            default  :
                                break;
        }

        switch(fType.type){
            case 'sip'  :
                                title = String.format(title,'SIP',trunk);
                                break;
            case 'iax2' :
            case 'iax'  :
                                fType.type = 'iax2';
                                title = String.format(title,'IAX2',trunk);
                                break;
            case 'zap'  :
                                title = String.format(title,'ZAP','(DAHDI compatibility mode)');
            case 'enum' :
                                title = String.format(title,'ENUM',trunk);
            case 'dundi' :
                                title = String.format(title,'DUNDI',trunk);
            case 'custom' :
                                title = String.format(title,'CUSTOM',trunk);
            case ''      :
                                title = String.format(title,'VIRTUAL');

                                Ext.Msg.show({title: String.format(<?php echo json_encode(__('Error {0}')) ?>,'ETVA'),
                                    buttons: Ext.MessageBox.OK,
                                    msg: <?php echo json_encode(__('DEVICE TECH OPERATION NOT YET SUPPORTED!')) ?>,
                                    icon: Ext.MessageBox.ERROR});
                                return;
                                break;
        }

        Ext.getBody().mask(<?php echo json_encode(__('Loading trunk...')) ?>);

        var win = Ext.getCmp('etvoip-pbx-trunks-window');
        if(!win){

            var trunks_form = new ETVOIP.PBX.Trunks.Form();

            win = new Ext.Window({
                    id: 'etvoip-pbx-trunks-window'                    
                    ,layout:'fit'
                    ,border:false
                    ,maximizable:true
                    ,maxW:900
                    ,maxH:700
                    ,defaultButton: trunks_form.getFocusField()
                    ,items: trunks_form
                    ,listeners:{
                        'close':function(){
                            Ext.EventManager.removeResizeListener(win.resizeFunc,win);
                        }
                    }
            });

            trunks_form.on({
                'onSave':{scope:this,fn:function(){this.reloadTrunks();win.close();}}
                ,'onCancel':function(){win.close();}
            });            

            //on browser resize, resize window
            Ext.EventManager.onWindowResize(win.resizeFunc,win);

        }                       
        
                
     //   win.on('show',function(){win.get(0).loadRecord(rec);alert('s');});
        var rec = {'data':{'service_id':this.service_id,'tech':fType.type,'action':action}};
        if(fType.action == 'edit') rec['data'].trunknum = fType.trunknum;
        
        win.get(0).buildLoad(rec);
        win.setTitle(title);

        
        win.resizeFunc();
        (function(){
            win.show(null,(function(){
                            if(fType.action == 'edit') win.get(0).remoteLoad(fType.trunknum);}
            ));

            Ext.getBody().unmask();
        }).defer(10);



    }
    ,deleteTrunk:function(rec){

            var trunknum = rec.data['trunkid'];
            var trunk = rec.data['name'];

            var conn = new Ext.data.Connection({
                listeners:{
                    scope:this,
                    beforerequest:function(){
                        this.getEl().mask(<?php echo json_encode(__('Removing trunk...')) ?>);
                    },// on request complete hide message
                    requestcomplete:function(){
                        this.getEl().unmask();
                    }
                    ,requestexception:function(c,r,o){
                        this.getEl().unmask();
                        Ext.Ajax.fireEvent('requestexception',c,r,o);
                    }
                }
            });// end conn

            conn.request({
                url:<?php echo json_encode(url_for('etvoip/json'))?>,
                params:{id:this.service_id,method:'del_trunk',params:Ext.encode({trunknum:trunknum})},
                // everything ok...
                success: function(resp,opt){
                    var response = Ext.util.JSON.decode(resp.responseText);
                    var msg = String.format(<?php echo json_encode(__('Removed trunk {0}')) ?>,trunk);
                    this.reloadTrunks();
                    View.notify({html:msg});

                },scope:this
            });// END Ajax request



    }
    ,removeTrunk:function(rec){

        var trunk = rec.data['name'];

        Ext.MessageBox.show({
                title: <?php echo json_encode(__('Remove trunk')) ?>,
                msg: String.format(<?php echo json_encode(__('You are about to remove trunk {0}. <br />Are you sure ?')) ?>,trunk),
                buttons: Ext.MessageBox.YESNO,
                fn: function(btn){

                    if(btn=='yes')
                        this.deleteTrunk(rec);

                },
                scope:this,
                icon: Ext.MessageBox.WARNING
        });

    }

});

</script>