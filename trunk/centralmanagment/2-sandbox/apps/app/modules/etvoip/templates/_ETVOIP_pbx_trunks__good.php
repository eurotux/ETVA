<script>
Ext.ns('ETVOIP.PBX.Trunks');


ETVOIP.PBX.Trunks.Form = Ext.extend(Ext.form.FormPanel, {
    border:true
    ,defaults:{border:false}
    ,labelWidth:145
    ,url:<?php echo json_encode(url_for('etfw/json'))?>
    ,layout:'fit'
    ,monitorValid:true
    ,initComponent:function(){

        this.items = this.buildForm();
        this.buttons = [{
                            text: __('Save'),
                            formBind:true
                        }
                        ,{text: __('Cancel')}];        

        // call parent
        ETVOIP.PBX.Trunks.Form.superclass.initComponent.apply(this, arguments);


    } // eo function initComp
    ,buildForm:function(){        

        var configuration = {
            bodyStyle:'padding:5px;'
            ,layout: {
                type: 'hbox',
                align: 'stretch'  // Child items are stretched to full width
            }
            ,defaults:{layout:'form',defaultType:'textfield',autoScroll:true,border:false}
            ,items:[
                {
                    flex:1,
                    items:[
                        /*
                         * General settings
                         */
                        {
                            xtype:'fieldset',title: <?php echo json_encode(__('General settings')) ?>,anchor:'95%',
                            defaultType:'textfield',
                            items:[
                                {
                                    name: 'trunk_name',
                                    fieldLabel : <?php echo json_encode(__('Trunk Description')) ?>,
                                    hint: 'Descriptive Name for this Trunk',
                                    allowBlank : false
                                }
                                ,{
                                    name: 'outcid',
                                    fieldLabel : <?php echo json_encode(__('Outbound Caller ID')) ?>,
                                    hint: 'Caller ID for calls placed out on this trunk<br><br>Format: <b>&lt;#######&gt;</b>. You can also use the format: "hidden" <b>&lt;#######&gt;</b> to hide the CallerID sent out over Digital lines if supported (E1/T1/J1/BRI/SIP/IAX)',
                                    allowBlank : false
                                }
                                ,{
                                    xtype:'combo',
                                    fieldLabel: <?php echo json_encode(__('CID Options')) ?>,
                                    name: 'keepcid',
                                    hint: 'Determines what CIDs will be allowed out this trunk. IMPORTANT: EMERGENCY CIDs defined on an extension/device will ALWAYS be used if this trunk is part of an EMERGENCY Route regardless of these settings.<br />Allow Any CID: all CIDs including foreign CIDS from forwarded external calls will be transmitted.<br />Block Foreign CIDs: blocks any CID that is the result of a forwarded call from off the system. CIDs defined for extensions/users are transmitted.<br />Remove CNAM: this will remove CNAM from any CID sent out this trunk<br />Force Trunk CID: Always use the CID defined for this trunk except if part of any EMERGENCY Route with an EMERGENCY CID defined for the extension/device',
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
                                    name       : 'maxchans',
                                    fieldLabel : <?php echo json_encode(__('Maximum Channels')) ?>,
                                    hint: 'Controls the maximum number of outbound channels (simultaneous calls) that can be used on this trunk. Inbound calls are not counted against the maximum. Leave blank to specify no maximum',
                                    allowBlank : false
                                }
                                ,{
                                    xtype: 'checkbox',
                                    fieldLabel: 'Disable Trunk',
                                    boxLabel: 'Disable',
                                    name: 'disabletrunk', inputValue:'checked',
                                    hint:'Check this to disable this trunk in all routes where it is used'

                                }
                                ,{
                                    layout:'table',
                                    xtype:'panel',
                                    border:false,
                                    layoutConfig: {columns:2},
                                    defaults:{layout:'form',border:false},
                                    items:[
                                        {
                                            items:[
                                                {
                                                    name       : 'failtrunk',
                                                    disabled: true,
                                                    xtype: 'textfield',
                                                    fieldLabel : <?php echo json_encode(__('Monitor Trunk Failures')) ?>,
                                                    hint: 'If checked, supply the name of a custom AGI Script that will be called to report, log, email or otherwise take some action on trunk failures that are not caused by either NOANSWER or CANCEL'
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
                                                    boxLabel: 'Enable',
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
                            ref:'../../incoming_settings_panel'
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
                            items:[
                                {
                                    name: 'dialrules',
                                    xtype:'textarea',
                                    fieldLabel : <?php echo json_encode(__('Dial Rules')) ?>,
                                    hint: 'A Dial Rule controls how calls will be dialed on this trunk. It can be used to add or remove prefixes. Numbers that don\'t match any patterns defined here will be dialed as-is. Note that a pattern without a + or | (to add or remove a prefix) will not make any changes but will create a match. Only the first matched rule will be executed and the remaining rules will not be acted on.<br />\n\
                                <br /><b>Rules:</b><br /><strong>X</strong>&nbsp;&nbsp;&nbsp; matches any digit from 0-9<br />\n\
                                <strong>Z</strong>&nbsp;&nbsp;&nbsp; matches any digit from 1-9<br />\n\
                                <strong>N</strong>&nbsp;&nbsp;&nbsp; matches any digit from 2-9<br />\n\
                                <strong>[1237-9]</strong>&nbsp;   matches any digit or letter in the brackets (in this example, 1,2,3,7,8,9)<br />\n\
                                <strong>.</strong>&nbsp;&nbsp;&nbsp; wildcard, matches one or more characters (not allowed before a | or +)<br />\n\
                                <strong>|</strong>&nbsp;&nbsp;&nbsp; removes a dialing prefix from the number (for example, 613|NXXXXXX would match when some dialed "6135551234" but would only pass "5551234" to the trunk)	<strong>+</strong>&nbsp;&nbsp;&nbsp; adds a dialing prefix from the number (for example, 1613+NXXXXXX would match when some dialed "5551234" and would pass "16135551234" to the trunk)<br />\n\
                                <br />You can also use both + and |, for example: 01+0|1ZXXXXXXXXX would match "016065551234" and dial it as "0116065551234" Note that the order does not matter, eg. 0|01+1ZXXXXXXXXX does the same thing',
                                    allowBlank : false
                                }
                                ,{
                                    xtype:'combo',
                                    fieldLabel: <?php echo json_encode(__('Dial Rules Wizards')) ?>,
                                    name: 'autopop',
                                    hint: '<strong>Always dial with prefix</strong> is useful for VoIP trunks, where if a number is dialed as "5551234", it can be converted to "16135551234".<br>\n\
                                           <strong>Remove prefix from local numbers</strong> is useful for ZAP trunks, where if a local number is dialed as "6135551234", it can be converted to "555-1234".<br>\n\
                                           <strong>Lookup numbers for local trunk</strong> This looks up your local number on www.localcallingguide.com (NA-only), and sets up so you can dial either 7 or 10 digits (regardless of what your PSTN is) on a local trunk (where you have to dial 1+area code for long distance, but only 5551234 (7-digit dialing) or 6135551234 (10-digit dialing) for local calls<br>',
                                    store: new Ext.data.ArrayStore({
                                        fields: ['value', 'name'],
                                        data: [['','(pick one)'],
                                               ['always','Always dial with prefix'],
                                               ['remove','Remove prefix from local numbers'],
                                               ['lookup7','Lookup numbers for local trunk (7-digit dialing)'],
                                               ['lookup10','Lookup numbers for local trunk (10-digit dialing)']]
                                    }),
                                    displayField:'name',
                                    valueField:'value',
                                    typeAhead:true,
                                    value:'',
                                    mode:'local',
                                    forceSelection: true,
                                    triggerAction: 'all',
                                    selectOnFocus:true,
                                    width: 120
                                }
                                ,{
                                    xtype: 'numberfield',
                                    name: 'dialoutprefix',
                                    fieldLabel : <?php echo json_encode(__('Outbound Dial Prefix')) ?>,
                                    hint: 'The outbound dialing prefix is used to prefix a dialing string to all outbound calls placed on this trunk. For example, if this trunk is behind another PBX or is a Centrex line, then you would put 9 here to access an outbound line. Another common use is to prefix calls with \'w\' on a POTS line that need time to obtain dial tone to avoid eating digits.<br><br>Most users should leave this option blank'
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
                            ref:'../../outgoing_settings_panel'
                        }
                        /*
                         * Registration
                         */
                        ,{
                            xtype:'fieldset',title: <?php echo json_encode(__('Registration')) ?>,anchor:'95%',
                            defaultType:'textfield',
                            items:[],
                            ref:'../../registration_panel'
                        }


                    ]
                }
            ]};

        return configuration;

    }
    //populate form fiels without ajax call
    ,loadRecord:function(record){
        
        this.getForm().clearInvalid();
        this.incoming_settings_panel.removeAll(true);
        this.outgoing_settings_panel.removeAll(true);
        this.registration_panel.removeAll(true);
                     
        switch(record.data['type']){            
            case 'enum' :
                        this.incoming_settings_panel.hide();
                        this.registration_panel.hide();
                        this.outgoing_settings_panel.hide();
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
                        this.outgoing_settings_panel.show();
                        break;
            case 'sip'  :
            case 'iax2' :
                        this.incoming_settings_panel.add([
                                {
                                    name: 'usercontext',
                                    fieldLabel : <?php echo json_encode(__('USER Context')) ?>,
                                    hint: 'This is most often the account name or number your provider expects.<br><br>This USER Context will be used to define the below user details'
                                }
                                ,{
                                    xtype:'textarea',
                                    anchor:'80% 80%',
                                    name: 'userconfig',
                                    value: 'secret=***password***\r\ntype=user\r\ncontext=from-trunk',
                                    fieldLabel : <?php echo json_encode(__('USER Details')) ?>,
                                    hint: 'Modify the default USER connection parameters for your VoIP provider.<br><br>You may need to add to the default lines listed below, depending on your provider..<br /><br />WARNING: Order is important as it will be retained. For example, if you use the "allow/deny" directives make sure deny comes first'
                                }                                
                                ]);

                        this.outgoing_settings_panel.add([
                                {
                                    name: 'channelid',                                    
                                    fieldLabel : <?php echo json_encode(__('Trunk Name')) ?>,
                                    hint: 'Give this trunk a unique name.  Example: myiaxtel',
                                    allowBlank : false
                                }
                                ,{
                                    xtype:'textarea',
                                    anchor:'80% 80%',
                                    name: 'peerdetails',
                                    value: 'host=***provider ip address***\r\nusername=***userid***\r\nsecret=***password***\r\ntype=peer',
                                    fieldLabel : <?php echo json_encode(__('PEER Details')) ?>,
                                    hint: 'Modify the default PEER connection parameters for your VoIP provider.<br><br>You may need to add to the default lines listed below, depending on your provider.<br /><br />WARNING: Order is important as it will be retained. For example, if you use the "allow/deny" directives make sure deny comes first'                                    
                                }                                
                                ]);
                                
                        this.registration_panel.add([
                                {
                                    name: 'register',
                                    fieldLabel : <?php echo json_encode(__('Register String')) ?>,
                                    hint: 'Most VoIP providers require your system to REGISTER with theirs. Enter the registration line here.<br><br>example:<br><br>username:password@switch.voipprovider.com.<br><br>Many providers will require you to provide a DID number, ex: username:password@switch.voipprovider.com/didnumber in order for any DID matching to work'
                                }
                                ]);
                                
                        this.incoming_settings_panel.show();
                        this.outgoing_settings_panel.show();
                        this.registration_panel.show();
                        break;
                                
        }

        if(this.rendered) this.getForm().loadRecord(record);
        else this.on('render',function(cp){cp.getForm().loadRecord(record);});        
            
    }



    

});


ETVOIP.PBX.Trunks.Main = Ext.extend(Ext.Panel, {
    layout:'fit',
    title: 'Trunks',
    initComponent:function(){


        this.trunksGrid = new Ext.grid.GridPanel({
                            border: false,
                            store: new Ext.data.JsonStore({
                                        root: 'data',
                                        fields: ['description', 'type']
                            }),
                            columns: [
                                        {
                                            header: "Description", sortable: false, dataIndex: 'description'
                                        }                                        
                                        ,{
                                            header: "Technology", sortable: false, dataIndex: 'type'
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
                            height:115,
                            tbar:[
                                    {
                                        iconCls: 'icon-add'
                                        ,text: <?php echo json_encode(__('Add trunk')) ?>
                                        ,menu:[
                                                {text:'ZAP Trunk (DAHDI compatibility mode)',scope:this,handler:function(){
                                                        (function(){this.loadWindowForm({type: 'zap'})}).defer(10,this);
                                                        return true;
                                                }}
                                                ,{text:'SIP Trunk',scope:this,handler:function(){
                                                        (function(){this.loadWindowForm({type: 'sip'})}).defer(10,this);
                                                        return true;
                                                }}
                                                ,{text:'IAX2 Trunk',scope:this,handler:function(){
                                                        (function(){this.loadWindowForm({type: 'iax2'})}).defer(10,this);
                                                        return true;
                                                }}
                                                ,{text:'ENUM Trunk',scope:this,handler:function(){
                                                        (function(){this.loadWindowForm({type: 'enum'})}).defer(10,this);
                                                        return true;
                                                }}
                                                ,{text:'DUNDi Trunk',scope:this,handler:function(){
                                                        (function(){this.loadWindowForm({type: 'dundi'})}).defer(10,this);
                                                        return true;
                                                }}
                                                ,{text:'CUSTOM Trunk',scope:this,handler:function(){
                                                        (function(){this.loadWindowForm({type: 'custom'})}).defer(10,this);
                                                        return true;
                                                }}
                                        ]
                                    }
                                    ,'-'
                                    ,{
                                        text: <?php echo json_encode(__('Edit extension')) ?>
                                        ,ref: '../editBtn'
                                        ,iconCls:'icon-edit-record'
                                        ,tooltip: <?php echo json_encode(__('Edit selected extension')) ?>
                                        ,disabled:true
                                        ,scope:this
                                        ,handler: function(){
                                            var selected = this.nmsGrid.getSelectionModel().getSelected();
                                            if(selected)
                                            {
                                                this.nmsGrid.stopEditing();
                                                this.nmsGrid.startEditing(this.nmsGrid.store.indexOf(selected),0);

                                            }
                                        }
                                    }
                                    ,'-'
                                    ,{
                                        text: <?php echo json_encode(__('Remove extension')) ?>
                                        ,ref: '../removeBtn'
                                        ,tooltip: <?php echo json_encode(__('Remove selected extension')) ?>
                                        ,iconCls:'remove'
                                        ,disabled:true
                                        ,scope:this
                                        ,handler: function(){

                                            var s = this.nmsGrid.getSelectionModel().getSelections();
                                            for(var i = 0, r; r = s[i]; i++)
                                                this.nmsGrid.store.remove(r);
                                            this.nmsGrid.getView().refresh();
                                        }
                                    }]
        }); //end grid


        this.items = [this.trunksGrid];

        ETVOIP.PBX.Trunks.Main.superclass.initComponent.apply(this);


        

    }
    ,loadWindowForm:function(fType){

        Ext.getBody().mask(<?php echo json_encode(__('Loading panel...')) ?>);
        
        var title = 'Add {0} Trunk {1}';

        switch(fType.type){
            case 'sip'  :
                                title = String.format(title,'SIP','');
                                break;
            case 'iax2' :
                                title = String.format(title,'IAX2','');
                                break;
            case 'zap'  :                                
                                title = String.format(title,'ZAP','(DAHDI compatibility mode)');
                                break;
            case 'enum' :                                
                                title = String.format(title,'ENUM','');
                                break;
            case 'dundi' :
                                title = String.format(title,'DUNDI','');
                                break;
            case 'custom' :
                                title = String.format(title,'CUSTOM','');
                                break;     
        }

        var rec = {'data':{'type':fType.type}};
                                    
        
        var win = Ext.getCmp('etvoip-pbx-trunks-window');
        //if(!win){

            var trunks_form = new ETVOIP.PBX.Trunks.Form();

            win = new Ext.Window({
                  //  id: 'etvoip-pbx-extensions-window'
                   // ,closeAction:'hide'
                    layout:'fit'
                    ,border:false
                    ,maximizable:true
//                    ,defaultButton: trunks_form.getFocusField()
                    ,items: trunks_form
                    ,listeners:{
                        'close':function(){
                            Ext.EventManager.removeResizeListener(resizeFunc);
                        }
                    }
            });
               
            resizeFunc = function(){

                var viewerSize = Ext.getBody().getViewSize();
                var windowHeight = viewerSize.height * 0.97;
                var windowWidth = viewerSize.width * 0.97;

                windowHeight = Ext.util.Format.round(windowHeight,0);
                //windowHeight = (windowHeight > 400) ? 400 : windowHeight;

                windowWidth = Ext.util.Format.round(windowWidth,0);
                //windowWidth = (windowWidth > 900) ? 900 : windowWidth;

                win.setSize(windowWidth,windowHeight);

                if(win.isVisible()) win.center();
            };

            //on browser resize, resize window
            Ext.EventManager.onWindowResize(resizeFunc);

        //}

        win.setTitle(title);
     //   win.on('show',function(){win.get(0).loadRecord(rec);alert('s');});
        win.get(0).loadRecord(rec);
        

        resizeFunc();
        (function(){win.show();Ext.getBody().unmask()}).defer(10);
    
    }

});

</script>