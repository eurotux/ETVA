<script>
Ext.ns('ETVOIP.PBX.Extensions');

ETVOIP.PBX.Extensions.Form = Ext.extend(Ext.form.FormPanel, {
    border:true    
    ,defaults:{border:false}
    ,labelWidth:120    
    ,url:<?php echo json_encode(url_for('etvoip/json'))?>
    ,layout:'fit'    
    ,monitorValid:true
    ,initComponent:function(){

        var advance_off = String.format(<?php echo json_encode(__('Advanced Mode: {0}')) ?>,'OFF');
        var advance_on = String.format(<?php echo json_encode(__('Advanced Mode: {0}')) ?>,'ON');

        this.items = this.buildForm();
        this.tbar = [{
                        text: advance_off,
                        enableToggle:true,
                        scope:this,
                        toggleHandler:function(item,pressed){
                            
                            if(pressed){
                                item.setText(advance_on);
                                this.showAdvanced();
                            }else{
                                item.setText(advance_off);
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
        ETVOIP.PBX.Extensions.Form.superclass.initComponent.apply(this, arguments);


    } // eo function initComponent
    ,buildForm:function(){

        var timer_interval = [[0,'Default']];
        for(var i = 1; i < 121;i++ )
            timer_interval.push([i,i]);
        

        var configuration = {            
            bodyStyle:'padding:5px;'
            //,autoScroll:true
            ,layout: {
                type: 'hbox',
                align: 'stretch'  // Child items are stretched to full width
            }
            ,defaults:{layout:'form',defaultType:'textfield',autoScroll:true,border:false}
            ,items:
                [
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
                            name:'faxenabled'
                        }
                        ,{
                            xtype:'hidden',
                            name:'faxemail'
                        }
                        ,{
                            xtype:'hidden',
                            name:'tech'
                        }
                        ,{
                            xtype:'fieldset',title: <?php echo json_encode(__('Extension')) ?>,anchor:'95%',
                            ref:'../../extension_panel',
                            defaultType:'textfield',
                            items:[
                                {
                                    xtype:'numberfield',
                                    name       : 'extension',
                                    fieldLabel : <?php echo json_encode(__('User Extension')) ?>,
                                    validateOnBlur:false,
                                    validator:function(v){

                                        if(v==this.originalValue){
                                          return true;
                                        }

                                    },
                                    hint: <?php echo json_encode(__('The extension number to dial to reach this user')) ?>,
                                    allowBlank : false
                                },
                                {
                                    name       : 'name',
                                    fieldLabel : <?php echo json_encode(__('Display Name')) ?>,
                                    hint: <?php echo json_encode(__('The caller id name for calls from this user will be set to this name. Only enter the name, NOT the number')) ?>,
                                    allowBlank : false
                                },
                                {
                                    xtype:'numberfield',
                                    ref:'cid_masquerade',
                                    name       : 'cid_masquerade',
                                    fieldLabel : <?php echo json_encode(__('CID Num Alias')) ?>,
                                    hint: <?php echo json_encode(__('The CID Number to use for internal calls, if different from the extension number. This is used to masquerade as a different user. A common example is a team of support people who would like their internal callerid to display the general support number (a ringgroup or queue). There will be no effect on external calls')) ?>
                                }
                                ,{
                                    name       : 'sipname',
                                    ref:'sipname',
                                    fieldLabel : <?php echo json_encode(__('SIP Alias')) ?>,
                                    hint: <?php echo json_encode(__('If you want to support direct sip dialing of users internally or through anonymous sip calls, you can supply a friendly name that can be used in addition to the users extension to call them')) ?>
                                }
                            ]
                        }
                        ,
                        /*
                         * Extension options
                         */                        
                        {
                            xtype:'fieldset',title: <?php echo json_encode(__('Extension options')) ?>,anchor:'95%',
                            ref:'../../extension_options_panel',
                            defaultType:'textfield',
                            items:[
                                {
                                    name       : 'outboundcid',
                                    fieldLabel : <?php echo json_encode(__('Outbound CID')) ?>,
                                    hint: <?php echo json_encode(__('Overrides the caller id when dialing out a trunk. Any setting here will override the common outbound caller id set in the Trunks admin.<br><br>Format: <b>"caller name" <#######></b><br><br>Leave this field blank to disable the outbound callerid feature for this user')) ?>
                                }
                                ,{
                                    xtype:'combo',
                                    fieldLabel: <?php echo json_encode(__('Ring Time')) ?>,
                                    name: 'ringtimer',
                                    hiddenName: 'ringtimer',
                                    hint: <?php echo json_encode(__('Number of seconds to ring prior to going to voicemail. If no voicemail is configured this will be ignored')) ?>,
                                    store: new Ext.data.ArrayStore({
                                        fields: ['value', 'name'],
                                        data: timer_interval
                                    }),
                                    displayField:'name',
                                    valueField:'value',
                                    typeAhead:true,
                                    value:0,
                                    originalValue: 0,
                                    mode:'local',
                                    forceSelection: true,
                                    triggerAction: 'all',
                                    selectOnFocus:true,
                                    width: 120
                                }
                                ,{
                                    xtype:'combo',
                                    fieldLabel: <?php echo json_encode(__('Call Waiting')) ?>,
                                    name: 'callwaiting',
                                    hint: <?php echo json_encode(__('Set the initial/current Call Waiting state for this user extension')) ?>,
                                    store: new Ext.data.ArrayStore({
                                        fields: ['value', 'name'],
                                        data: [['enabled','Enable'],['disabled','Disable']]
                                    }),
                                    displayField:'name',
                                    valueField:'value',
                                    hiddenName:'callwaiting',
                                    typeAhead:true,
                                    value:'disabled',
                                    originalValue:'disabled',
                                    mode:'local',
                                    forceSelection: true,
                                    triggerAction: 'all',
                                    selectOnFocus:true,
                                    width: 120
                                }
                                ,{
                                    xtype:'combo',
                                    fieldLabel: <?php echo json_encode(__('Call Screening')) ?>,
                                    name: 'call_screen',
                                    hint: <?php echo json_encode(__('Call Screening requires external callers to say their name, which will be played back to the user and allow the user to accept or reject the call.  Screening with memory only verifies a caller for their caller-id once. Screening without memory always requires a caller to say their name. Either mode will always announce the caller based on the last introduction saved with that callerid. If any user on the system uses the memory option, when that user is called, the caller will be required to re-introduce themselves and all users on the system will have that new introduction associated with the caller CallerId')) ?>,
                                    store: new Ext.data.ArrayStore({
                                        fields: ['value', 'name'],
                                        data: [['0','Disable'],['nomemory','Screen Caller: No Memory'],['memory','Screen Caller: Memory']]
                                    }),
                                    displayField:'name',
                                    valueField:'value',
                                    hiddenName:'call_screen',
                                    typeAhead:true,
                                    value:0,
                                    originalValue:0,
                                    mode:'local',
                                    forceSelection: true,
                                    triggerAction: 'all',
                                    selectOnFocus:true,
                                    width: 120
                                }
                                ,{
                                    xtype:'combo',
                                    fieldLabel: <?php echo json_encode(__('Pinless Dialing')) ?>,
                                    name: 'pinless',
                                    hint: <?php echo json_encode(__('Enabling Pinless Dialing will allow this extension to bypass any pin codes normally required on outbound calls')) ?>,
                                    store: new Ext.data.ArrayStore({
                                        fields: ['value', 'name'],
                                        data: [['enabled','Enable'],['disabled','Disable']]
                                    }),
                                    displayField:'name',
                                    valueField:'value',
                                    hiddenName:'pinless',
                                    typeAhead:true,
                                    value:'disabled',
                                    originalValue:'disabled',
                                    mode:'local',
                                    forceSelection: true,
                                    triggerAction: 'all',
                                    selectOnFocus:true,
                                    width: 120
                                }
                                ,{
                                    name       : 'emergency_cid',
                                    fieldLabel : <?php echo json_encode(__('Emergency CID')) ?>,
                                    hint: <?php echo json_encode(__('This caller id will always be set when dialing out an Outbound Route flagged as Emergency.  The Emergency CID overrides all other caller id settings')) ?>
                                }                                                               

                            ]
                        }
                        ,
                        /*
                         * DID/CID
                         */
                        {
                            xtype:'fieldset',title: <?php echo json_encode(__('Assigned DID/CID')) ?>,anchor:'95%',
                            ref:'../../did_cid_panel',
                            defaultType:'textfield',
                            items:[
                                {
                                    name       : 'newdid_name',
                                    fieldLabel : <?php echo json_encode(__('DID Description')) ?>,
                                    hint: <?php echo json_encode(__('A description for this DID, such as "Fax"'))?>
                                }                                
                                ,{
                                    name       : 'newdid',
                                    fieldLabel : <?php echo json_encode(__('Add Inbound DID')) ?>,
                                    hint: <?php echo json_encode(__('A direct DID that is associated with this extension. The DID should be in the same format as provided by the provider (e.g. full number, 4 digits for 10x4, etc).<br><br>Format should be: <b>XXXXXXXXXX</b><br><br>.An optional CID can also be associated with this DID by setting the next box')) ?>
                                }
                                ,{
                                    name       : 'newdidcid',
                                    fieldLabel : <?php echo json_encode(__('Add Inbound CID')) ?>,
                                    hint: <?php echo json_encode(__('Add a CID for more specific DID + CID routing. A DID must be specified in the above Add DID box. In addition to standard dial sequences, you can also put Private, Blocked, Unknown, Restricted, Anonymous and Unavailable in order to catch these special cases if the Telco transmits them')) ?>
                                }

                            ]
                        }
                        /*
                         * Voicemail & directory
                         */
                        ,{
                            xtype:'fieldset',title: <?php echo json_encode(__('Voicemail & Directory')) ?>,anchor:'95%',
                            ref:'../../voicemail_panel',
                            defaultType:'textfield',
                            items:[
                                {
                                    xtype:'combo',
                                    fieldLabel: <?php echo json_encode(__('Status')) ?>,
                                    name: 'vm',
                                    hiddenName: 'vm',
                                    store: new Ext.data.ArrayStore({
                                        fields: ['value', 'name'],
                                        data: [['enabled','Enabled'],['disabled','Disabled']]
                                    }),
                                    displayField:'name',
                                    valueField:'value',
                                    typeAhead:true,
                                    value:'disabled',
                                    originalValue:'disabled',
                                    mode:'local',
                                    forceSelection: true,
                                    triggerAction: 'all',
                                    selectOnFocus:true,
                                    width: 120,
                                    listeners:{
                                        'select':{scope:this,fn:function(cb, rec, index){
                                            this.toogleVMOptions(rec.data['value']);
                                        }}                                        
                                    }
                                }
                                ,{
                                    name       : 'vmpwd',
                                    disabled: true,
                                    fieldLabel : <?php echo json_encode(__('Voicemail Password')) ?>,
                                    hint: <?php echo json_encode(__('This is the password used to access the voicemail system.<br /><br />This password can only contain numbers.<br /><br />A user can change the password you enter here after logging into the voicemail system (*98) with a phone')) ?>
                                }
                                ,{
                                    name       : 'email',
                                    disabled: true,
                                    fieldLabel : <?php echo json_encode(__('Email Address')) ?>,
                                    hint: <?php echo json_encode(__('The email address that voicemails are sent to')) ?>,
                                    vtype:'email',
                                    blankText: <?php echo json_encode(__('Please provide correct email address')) ?>
                                }
                                ,{
                                    name       : 'pager',
                                    disabled: true,
                                    fieldLabel : <?php echo json_encode(__('Pager Email Address')) ?>,
                                    hint: <?php echo json_encode(__('Pager/mobile email address that short voicemail notifications are sent to')) ?>,
                                    vtype:'email',
                                    blankText: <?php echo json_encode(__('Please provide correct email address')) ?>
                                }
                                ,{
                                    // Use the default, automatic layout to distribute the controls evenly
                                    // across a single row
                                    xtype: 'radiogroup',
                                    name: 'attach',
                                    disabled: true,
                                    fieldLabel: <?php echo json_encode(__('Email Attachment')) ?>,
                                    width:100,                                    
                                    items: [
                                        {boxLabel: 'yes', hint: <?php echo json_encode(__('Option to attach voicemails to email')) ?>, name: 'attach', inputValue:'yes'},
                                        {boxLabel: 'no', hint: <?php echo json_encode(__('Option to attach voicemails to email')) ?>, name: 'attach', inputValue:"no",checked: true}
                                    ]
                                }                                
                                ,{
                                    xtype: 'radiogroup',
                                    disabled: true,
                                    name: 'saycid',
                                    fieldLabel: <?php echo json_encode(__('Play CID')) ?>,
                                    width:100,                                    
                                    items: [
                                        {boxLabel: 'yes',  name: 'saycid', inputValue:'yes', hint: <?php echo json_encode(__('Read back caller telephone number prior to playing the incoming message, and just after announcing the date and time the message was left')) ?>},
                                        {boxLabel: 'no', name: 'saycid', inputValue:"no",checked: true, hint: <?php echo json_encode(__('Read back caller telephone number prior to playing the incoming message, and just after announcing the date and time the message was left')) ?>}
                                    ]
                                }
                                ,{
                                    xtype: 'radiogroup',
                                    disabled: true,
                                    name: 'envelope',
                                    fieldLabel: <?php echo json_encode(__('Play Envelope')) ?>,
                                    width:100,                                    
                                    items: [
                                        {boxLabel: 'yes', name: 'envelope', inputValue:'yes', hint: <?php echo json_encode(__('Envelope controls whether or not the voicemail system will play the message envelope (date/time) before playing the voicemail message')) ?>},
                                        {boxLabel: 'no', name: 'envelope', inputValue:"no", checked: true, hint: <?php echo json_encode(__('Envelope controls whether or not the voicemail system will play the message envelope (date/time) before playing the voicemail message')) ?>}
                                    ]
                                }
                                ,{
                                    xtype: 'radiogroup',
                                    disabled: true,
                                    name: 'delete',
                                    fieldLabel: <?php echo json_encode(__('Delete Voicemail')) ?>,
                                    width:100,
                                    items: [
                                        {boxLabel: 'yes', name: 'delete', inputValue:'yes', hint: <?php echo json_encode(__('If set to "yes" the message will be deleted from the voicemailbox (after having been emailed). Provides functionality that allows a user to receive their voicemail via email alone, rather than having the voicemail able to be retrieved from the Webinterface or the Extension handset.  CAUTION: MUST HAVE attach voicemail to email SET TO YES OTHERWISE YOUR MESSAGES WILL BE LOST FOREVER')) ?>},
                                        {boxLabel: 'no', name: 'delete', inputValue:"no", checked: true, hint: <?php echo json_encode(__('If set to "yes" the message will be deleted from the voicemailbox (after having been emailed). Provides functionality that allows a user to receive their voicemail via email alone, rather than having the voicemail able to be retrieved from the Webinterface or the Extension handset.  CAUTION: MUST HAVE attach voicemail to email SET TO YES OTHERWISE YOUR MESSAGES WILL BE LOST FOREVER')) ?>}
                                    ]
                                }
                                ,{
                                    name       : 'imapuser',
                                    disabled: true,
                                    fieldLabel : <?php echo json_encode(__('IMAP Username')) ?>,
                                    hint: <?php echo json_encode(__('This is the IMAP username, if using IMAP storage')) ?>
                                }
                                ,{
                                    name       : 'imappassword',
                                    disabled: true,
                                    fieldLabel : <?php echo json_encode(__('IMAP Password')) ?>,
                                    hint: <?php echo json_encode(__('This is the IMAP password, if using IMAP storage')) ?>
                                }
                                ,{
                                    name       : 'options',
                                    disabled: true,
                                    fieldLabel : <?php echo json_encode(__('VM Options')) ?>,
                                    hint: <?php echo json_encode(__('Separate options with pipe ( | )<br /><br />ie: review=yes|maxmessage=60')) ?>
                                }
                                ,{
                                    name       : 'vmcontext',
                                    disabled: true,
                                    value: 'default',
                                    originalValue: 'default',
                                    fieldLabel : <?php echo json_encode(__('VM Context')) ?>,
                                    hint: <?php echo json_encode(__('This is the Voicemail Context which is normally set to default. Do not change unless you understand the implications')) ?>
                                }
                            ]
                        }
                    ]//end items flex
                }
                ,{
                    flex:1,
                    items:[
                        /*
                         * Device options
                         */                        
                        {
                            xtype:'fieldset',title: <?php echo json_encode(__('Device options')) ?>,anchor:'95%',
                            defaultType:'textfield',
                            items:[],
                            ref:'../../devinfo_panel'
                        }
                        /*
                         * Dictation services
                         */
                        ,{
                            xtype:'fieldset',title: <?php echo json_encode(__('Dictation services')) ?>,anchor:'95%',
                            ref:'../../dictation_panel',
                            defaultType:'textfield',
                            items:[
                                {
                                    xtype:'combo',
                                    fieldLabel: <?php echo json_encode(__('Dictation Service')) ?>,
                                    name: 'dictenabled',                                    
                                    store: new Ext.data.ArrayStore({
                                        fields: ['value', 'name'],
                                        data: [['enabled','Enabled'],['disabled','Disabled']]
                                    }),
                                    displayField:'name',
                                    valueField:'value',
                                    typeAhead:true,
                                    value:'disabled',
                                    originalValue:'disabled',
                                    hiddenName:'dictenabled',
                                    mode:'local',
                                    forceSelection: true,
                                    triggerAction: 'all',
                                    selectOnFocus:true,
                                    width: 120
                                }
                                ,{
                                    xtype:'combo',
                                    fieldLabel: <?php echo json_encode(__('Dictation Format')) ?>,
                                    name: 'dictformat',
                                    store: new Ext.data.ArrayStore({
                                        fields: ['value', 'name'],
                                        data: [['ogg','Ogg Vorbis'],['gsm','GSM'],['wav','WAV']]
                                    }),
                                    displayField:'name',
                                    valueField:'value',
                                    typeAhead:true,
                                    value:'ogg',
                                    originalValue:'ogg',
                                    hiddenName:'dictformat',
                                    mode:'local',
                                    forceSelection: true,
                                    triggerAction: 'all',
                                    selectOnFocus:true,
                                    width: 120
                                }
                                ,{
                                    name       : 'dictemail',
                                    fieldLabel : <?php echo json_encode(__('Email Address')) ?>,
                                    hint: <?php echo json_encode(__('The email address that completed dictations are sent to')) ?>,
                                    vtype:'email',
                                    blankText: <?php echo json_encode(__('Please provide correct email address')) ?>
                                }
                            ]
                        }
                        /*
                         * Language
                         */
                        ,{
                            xtype:'fieldset',title: <?php echo json_encode(__('Language')) ?>,anchor:'95%',
                            ref:'../../language_panel',
                            defaultType:'textfield',
                            items:[                                
                                {
                                    name       : 'langcode',
                                    fieldLabel : <?php echo json_encode(__('Language Code')) ?>,
                                    hint: <?php echo json_encode(__('This will cause all messages and voice prompts to use the selected language if installed')) ?>
                                }
                            ]
                        }
                        /*
                         * Recording options
                         */
                        ,{
                            xtype:'fieldset',title: <?php echo json_encode(__('Recording options')) ?>,anchor:'95%',
                            ref:'../../recording_options_panel',
                            defaultType:'textfield',
                            items:[
                                {
                                    xtype:'combo',
                                    fieldLabel: <?php echo json_encode(__('Record Incoming')) ?>,
                                    name: 'record_in',
                                    hint: <?php echo json_encode(__('Record all inbound calls received at this extension')) ?>,
                                    store: new Ext.data.ArrayStore({
                                        fields: ['value', 'name'],
                                        data: [['Adhoc','On Demand'],['Always','Always'],['Never','Never']]
                                    }),
                                    displayField:'name',
                                    valueField:'value',
                                    hiddenName:'record_in',
                                    typeAhead:true,
                                    value:'Adhoc',
                                    originalValue:'Adhoc',
                                    mode:'local',
                                    forceSelection: true,
                                    triggerAction: 'all',
                                    selectOnFocus:true,
                                    width: 120
                                }
                                ,{
                                    xtype:'combo',
                                    fieldLabel: <?php echo json_encode(__('Record Outgoing')) ?>,
                                    hiddenName:'record_out',
                                    hint: <?php echo json_encode(__('Record all outbound calls received at this extension')) ?>,
                                    store: new Ext.data.ArrayStore({
                                        fields: ['value', 'name'],
                                        data: [['Adhoc','On Demand'],['Always','Always'],['Never','Never']]
                                    }),
                                    displayField:'name',
                                    valueField:'value',
                                    typeAhead:true,
                                    value:'Adhoc',
                                    originalValue:'Adhoc',
                                    mode:'local',
                                    forceSelection: true,
                                    triggerAction: 'all',
                                    selectOnFocus:true,
                                    width: 120
                                }
                            ]
                        }
                        /*
                         * VmX Locater
                         */
                         /*
                        ,{
                            xtype:'fieldset',title: <?php echo json_encode(__('VmX Locater')) ?>,anchor:'95%',
                            ref:'../../vmx_panel',
                            defaultType:'textfield',
                            items:[
                                {
                                    xtype:'combo',
                                    fieldLabel: <?php echo json_encode(__('VmX Locater')) ?>,
                                    name: 'vmx_state',
                                    hint: 'Enable/Disable the VmX Locater feature for this user. When enabled all settings are controlled by the user in the User Portal (ARI). Disabling will not delete any existing user settings but will disable access to the feature',
                                    store: new Ext.data.ArrayStore({
                                        fields: ['value', 'name'],
                                        data: [['','Disabled'],['checked','Enabled']]
                                    }),
                                    displayField:'name',
                                    valueField:'value',
                                    typeAhead:true,
                                    value:'',
                                    originalValue:'',
                                    mode:'local',
                                    forceSelection: true,
                                    triggerAction: 'all',
                                    selectOnFocus:true,
                                    width: 120,
                                    listeners:{
                                        'select':{scope:this,fn:function(cb, rec, index){

                                            var disabled = true;
                                            if(rec.data['value']=='checked') disabled = false;                                            

                                            this.form.findField('vmx_when').setDisabled(disabled);
                                            this.form.findField('vmx_play_instructions').setDisabled(disabled);
                                            this.form.findField('vmx_option_0_number').setDisabled(disabled);
                                            this.form.findField('vmx_option_0_system_default').setDisabled(disabled);
                                            this.form.findField('vmx_option_1_number').setDisabled(disabled);
                                            this.form.findField('vmx_option_2_number').setDisabled(disabled);
                                            

                                        }}
                                    }
                                }
                                ,{
                                    xtype: 'checkboxgroup',
                                    fieldLabel: 'Use When',
                                    name: 'vmx_when',
                                    disabled: true,
                                    width:180,
                                    items: [
                                        {boxLabel: 'unavailable',  name: 'vmx_unavail_enabled', inputValue:'checked', hint:'Menu options below are available during your personal voicemail greeting playback. <br/><br/>Check both to use at all times'},
                                        {boxLabel: 'busy', name: 'vmx_busy_enabled', inputValue:"checked", hint:'Menu options below are available during your personal voicemail greeting playback. <br/><br/>Check both to use at all times'}
                                    ]
                                }
                                ,{
                                    xtype: 'checkbox',
                                    fieldLabel: 'Voicemail Instructions',
                                    boxLabel: 'Standard voicemail prompts',
                                    disabled: true,
                                    name: 'vmx_play_instructions', inputValue:'checked', checked:true, hint:'Uncheck to play a beep after your personal voicemail greeting'

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
                                                    name       : 'vmx_option_0_number',
                                                    disabled: true,
                                                    xtype: 'textfield',
                                                    fieldLabel : <?php echo json_encode(__('Press 0')) ?>,
                                                    hint: 'Pressing 0 during your personal voicemail greeting goes to the Operator. Uncheck to enter another destination here. This feature can be used while still disabling VmX to allow an alternative Operator extension without requiring the VmX feature for the user'
                                                }
                                            ]
                                        }
                                        // 2nd col
                                        ,{
                                            bodyStyle:'padding-left:5px;',
                                            items:[
                                                {                                                    
                                                    xtype: 'checkbox',
                                                    disabled: true,
                                                    hideLabel:true,
                                                    boxLabel: 'Go To Operator',
                                                    name: 'vmx_option_0_system_default', inputValue:'checked', checked:true
                                                }
                                            ]
                                        }
                                    ]
                                }
                                ,{

                                    name       : 'vmx_option_1_number',
                                    disabled: true,
                                    fieldLabel : <?php echo json_encode(__('Press 1')) ?>,
                                    hint: 'The remaining options can have internal extensions, ringgroups, queues and external numbers that may be rung. It is often used to include your cell phone. You should run a test to make sure that the number is functional any time a change is made so you don\'t leave a caller stranded or receiving invalid number messages'
                                }
                                ,{

                                    name       : 'vmx_option_2_number',
                                    disabled: true,
                                    fieldLabel : <?php echo json_encode(__('Press 2')) ?>,
                                    hint: 'Use any extensions, ringgroups, queues or external numbers. <br/><br/>Remember to re-record your personal voicemail greeting and include instructions. Run a test to make sure that the number is functional'
                                }
                            ]
                        }
                        */
                    ]
                }
            ]};

        return configuration;        

    }
    ,toogleVMOptions:function(value){

        var disabled = true;
        if(value=='enabled') disabled = false;
        this.form.findField('vmpwd').setDisabled(disabled);
        this.form.findField('email').setDisabled(disabled);
        this.form.findField('pager').setDisabled(disabled);
        this.form.findField('attach').setDisabled(disabled);
        this.form.findField('saycid').setDisabled(disabled);
        this.form.findField('envelope').setDisabled(disabled);
        this.form.findField('delete').setDisabled(disabled);
        this.form.findField('imapuser').setDisabled(disabled);
        this.form.findField('imappassword').setDisabled(disabled);
        this.form.findField('options').setDisabled(disabled);
        this.form.findField('vmcontext').setDisabled(disabled);

    }
    ,onRender:function() {
        // call parent
        ETVOIP.PBX.Extensions.Form.superclass.onRender.apply(this, arguments);

        // set wait message target
        //this.getForm().waitMsgTarget = this.ownerCt.getEl();
        //this.loadData();



        // set wait message target
        //this.getForm().waitMsgTarget = this.getEl();
        this.getForm().waitMsgTarget = this.getEl();


    } // eo function onRender    
    ,getFocusField:function(){
        return this.getForm().findField('extension').disabled ? this.getForm().findField('name'): this.getForm().findField('extension') ;
    }
    ,showAdvanced:function(){

        this.extension_options_panel.show();
        this.did_cid_panel.show();
        this.voicemail_panel.show();
        this.dictation_panel.show();
        this.language_panel.show();
        this.recording_options_panel.show();
        //this.vmx_panel.show();
        this.extension_panel.sipname.getEl().up('.x-form-item').setDisplayed(true);
        this.extension_panel.cid_masquerade.showAll();

    }
    ,showBasic:function(){
        this.extension_options_panel.hide();
        this.did_cid_panel.hide();
        this.voicemail_panel.hide();
        this.dictation_panel.hide();
        this.language_panel.hide();
        this.recording_options_panel.hide();
        //this.vmx_panel.hide();

        this.extension_panel.cid_masquerade.showAll(false);
        if(this.extension_panel.sipname.rendered) this.extension_panel.sipname.getEl().up('.x-form-item').setDisplayed(false);
        else{
            this.extension_panel.sipname.on('render',function(){
                this.extension_panel.sipname.getEl().up('.x-form-item').setDisplayed(false);
            },this);
        }
        
        
    }
    ,disableExtension:function(disable){
        this.getForm().findField('extension').setDisabled(disable);
    }
    //populate form fiels without ajax call
    ,buildLoad:function(record){        
                                
        this.devinfo_panel.removeAll(true);
                     
        switch(record.data['tech']){
            case 'sip' :
                                this.devinfo_panel.add([
                                        <?php
                                         $sip_obj = new ETVOIP_Pbx_Extension_SIP();
                                         $hiddenF = $sip_obj->getHiddenDevinfo();

                                         foreach($hiddenF as $k=>$v)

                                            echo "{
                                                    xtype: 'hidden',
                                                    name: '".$k."',
                                                    originalValue: '".$v."',
                                                    value: '".$v."'
                                                 },";

                                         
                                        ?>
                                        {
                                            hideLabel:true, xtype:'displayfield', name: 'dev_tech_label'
                                        }
                                        ,{
                                            name       : 'devinfo_secret',
                                            fieldLabel : <?php echo json_encode(__('Secret')) ?>
                                        }
                                        ,{
                                            name       : 'devinfo_dtmfmode',
                                            fieldLabel : <?php echo json_encode(__('Dtmfmode')) ?>,
                                            originalValue: 'rfc2833',
                                            value: 'rfc2833'
                                        }]);
                                this.devinfo_panel.show();                                
                                break;
                                
            case 'iax2' :
                                this.devinfo_panel.add([
                                        <?php
                                         $iax_obj = new ETVOIP_Pbx_Extension_IAX2();
                                         $hiddenF = $iax_obj->getHiddenDevinfo();

                                         foreach($hiddenF as $k=>$v)

                                            echo "{
                                                    xtype: 'hidden',
                                                    name: '".$k."',
                                                    originalValue: '".$v."',
                                                    value: '".$v."'
                                                 },";


                                        ?>
                                        {
                                            hideLabel:true, xtype:'displayfield', name: 'dev_tech_label'
                                        }
                                        ,{
                                            name       : 'devinfo_secret',
                                            fieldLabel : <?php echo json_encode(__('Secret')) ?>
                                        }
                                        ]);
                                this.devinfo_panel.show();
                                break;
                                
            case 'zap' :
                                this.devinfo_panel.add([
                                        <?php
                                         $zap_obj = new ETVOIP_Pbx_Extension_ZAP();
                                         $hiddenF = $zap_obj->getHiddenDevinfo();

                                         foreach($hiddenF as $k=>$v)

                                            echo "{
                                                    xtype: 'hidden',
                                                    name: '".$k."',
                                                    originalValue: '".$v."',
                                                    value: '".$v."'
                                                 },";


                                        ?>
                                        {
                                            hideLabel:true, xtype:'displayfield', name: 'dev_tech_label'
                                        }
                                        ,{
                                            name       : 'devinfo_channel',
                                            fieldLabel : <?php echo json_encode(__('Channel')) ?>
                                        }
                                        ]);
                                this.devinfo_panel.show();
                                break;

            case 'custom' :
                                this.devinfo_panel.add([
                                        {
                                            hideLabel:true, xtype:'displayfield', name: 'dev_tech_label'
                                        }
                                        ,{
                                            name       : 'devinfo_dial',
                                            fieldLabel : <?php echo json_encode(__('Dial')) ?>
                                        }
                                        ]);
                                this.devinfo_panel.show();
                                break;

            case '' :                                
                                this.devinfo_panel.hide();
                                break;
                                
        }

        this.showBasic();
        
//        if(record.data['dev']=='iax2_generic')
//        {
//            show_dtmfmode = false;
//
//
//            this.devinfo_panel.hide();
//
//
//
////            this.getForm().findField('devinfo_dtmfmode').on('render',function(cp){
////                cp.getEl().up('.x-form-item').setDisplayed(false);
////                });
////
////
////            //this.getForm().findField('devinfo_dtmfmode').setVisible(false);
//////            if(this.getForm().findField('devinfo_dtmfmode').getEl())
//////                this.getForm().findField('devinfo_dtmfmode').getEl().up('.x-form-item').setDisplayed(false);
//////            else{
//////              this.getForm().findField('devinfo_dtmfmode').hide();
//////              this.getForm().findField('devinfo_dtmfmode').hideLabel = true;
//////            }
////            //this.doLayout();
////
////            alert(this.getForm().findField('devinfo_dtmfmode'));
//
//        }

        

        //show/hide dtmfmode

////////        var dtmfmode = this.getForm().findField('devinfo_dtmfmode');
////////
////////        if(dtmfmode.rendered) dtmfmode.getEl().up('.x-form-item').setDisplayed(show_dtmfmode);
////////        else dtmfmode.on('render',function(cp){cp.getEl().up('.x-form-item').setDisplayed(show_dtmfmode);});//

        this.getForm().reset();
        this.getForm().clearInvalid();
        this.getForm().loadRecord(record);

      //  this.devinfo_panel.find('name','dev_tech_label').setValue('oioi');
        
     
        //if(params.action=='load') this.remoteLoad(params.extension);

//        if(this.rendered) this.getForm().loadRecord(record);
 //       else this.on('render',function(cp){cp.getForm().loadRecord(record);});
            
    }
    ,remoteLoad:function(record){
        var service_id = record.data['service_id'];
        var extension = record.data['extension'];
        var tech = record.data['tech'];        
        
        this.load({
            url:this.url,
            waitMsg: <?php echo json_encode(__('Loading extension data...')) ?>,
            params:{id:service_id,method:'get_extension',params:Ext.encode({tech:tech,extension:extension})},
            scope:this,
            success: function ( form, action ){
                var result = action.result;
                var data = result.data;                
                
                this.toogleVMOptions(data['vm']);

                if(record.data['action']=='edit')
                {
                    this.disableExtension(true);
                }

                this.getFocusField().focus();
            }

        });

    }
    ,save:function(){

        
        var alldata = this.form.getValues();        
        
        var send_data = new Object();
        var send_data = alldata;
        send_data['extension'] = this.getForm().findField('extension').getValue();        

        if (this.form.isValid()) {

            var service_id = alldata['service_id'];
            var method = 'add_extension';
            var wait_msg = <?php echo json_encode(__('Adding extension...'))?>;
            var ok_msg = <?php echo json_encode(__('Added extension {0}'))?>;

            if(alldata['action'] == 'edit'){
                method = 'edit_extension';
                wait_msg = <?php echo json_encode(__('Updating extension...'))?>;
                ok_msg = <?php echo json_encode(__('Updated extension {0}'))?>;
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
                    var msg = String.format(ok_msg,alldata['extension']);
                    View.notify({html:msg});
                    this.fireEvent('onSave');

                },scope:this
            });// END Ajax request


        }else{
            Ext.MessageBox.alert(<?php echo json_encode(__('Error!')) ?>, <?php echo json_encode(__('Please fix the errors noted!')) ?>);
        }
    }


}); // eo extend

ETVOIP.PBX.Extensions.Main = Ext.extend(Ext.Panel, {
    layout:'fit',    
    title: <?php echo json_encode(__('Extensions')) ?>,
    initComponent:function(){                

       var extensionsStore = new Ext.data.JsonStore({
                proxy:new Ext.data.HttpProxy({url:<?php echo json_encode(url_for('etvoip/json'))?>}),
                totalProperty: 'total',
                baseParams:{id:this.service_id,method:'get_extensions'},                
                root: 'data',
                fields: ['extension', 'name', 'tech','tech_name'],
                listeners:{
                    load:{scope:this,fn:function(st,rcs,opts){

                        var need_reload = st.reader.jsonData.need_reload;
                        var pbxPanel = this.ownerCt;                        
                        
                        pbxPanel.fireEvent('notify_reload',pbxPanel,need_reload);                                                                          
                        
                    }
                }},scope:this
            }
        );



        this.extensionsGrid = new Ext.grid.GridPanel({
                            border: false,                            
                            store: extensionsStore,
                            columns: [
                                        {
                                            header: "Extension", sortable: false, dataIndex: 'extension'
                                        }
                                        ,{
                                            header: "Display Name", sortable: false, dataIndex: 'name'
                                        }
                                        ,{
                                            header: "Device", sortable: false, dataIndex: 'tech_name'
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
                            // add paging toolbar
                            bbar: new Ext.ux.grid.TotalCountBar({
                                            store:extensionsStore,
                                            displayInfo:true
                            }),
                            tbar: new Ext.ux.StatusBar({                
                                defaultText: '',
                                //  defaultIconCls: '',
                                statusAlign: 'right',
                                items: [
                                    {
                                        iconCls: 'icon-add'
                                        ,text: <?php echo json_encode(__('Add extension')) ?>
                                        ,menu:[
                                                {text: String.format(<?php echo json_encode(__('Generic {0} Device')) ?>,'SIP'),scope:this,handler:function(){
                                                        (function(){this.loadWindowForm({type: 'sip'})}).defer(10,this);
                                                        return true;
                                                }}
                                                ,{text: String.format(<?php echo json_encode(__('Generic {0} Device')) ?>,'IAX2'),scope:this,handler:function(){
                                                        (function(){this.loadWindowForm({type: 'iax2'})}).defer(10,this);
                                                        return true;
                                                }}
//                                                ,{text: String.format(<?php //echo json_encode(__('Generic {0} Device')) ?>,'ZAP'),scope:this,handler:function(){
//                                                        (function(){this.loadWindowForm({type: 'zap'})}).defer(10,this);
//                                                        return true;
//                                                }}
//                                                ,{text:'Other (Custom) Device',scope:this,handler:function(){
//                                                        (function(){this.loadWindowForm({type: 'custom'})}).defer(10,this);
//                                                        return true;
//                                                }}
//                                                ,{text:'None (virtual exten)',scope:this,handler:function(){
//                                                        (function(){this.loadWindowForm({type: ''})}).defer(10,this);
//                                                        return true;
//                                                }}
                                        ]
                                    }
                                    ,'-'
                                    ,{
                                        text: <?php echo json_encode(__('Edit extension')) ?>
                                        ,ref: 'editBtn'
                                        ,iconCls:'icon-edit-record'
                                        ,tooltip: <?php echo json_encode(__('Edit selected extension')) ?>
                                        ,disabled:true
                                        ,scope:this
                                        ,handler: function(){
                                            var s = this.extensionsGrid.getSelectionModel().getSelected();
                                            if(s){
                                                (function(){this.loadWindowForm({type: s.data['tech'],action:'edit',extension:s['data']['extension']})}).defer(10,this);
                                                return true;
                                            }
                                            //this.onRemove(s.data['extension']);
                                        }
                                    }
                                    ,'-'
                                    ,{
                                        text: <?php echo json_encode(__('Remove extension')) ?>
                                        ,ref: 'removeBtn'
                                        ,tooltip: <?php echo json_encode(__('Remove selected extension')) ?>
                                        ,iconCls:'remove'
                                        ,disabled:true
                                        ,scope:this
                                        ,handler: function(){
                                            var s = this.extensionsGrid.getSelectionModel().getSelected();
                                            if(s) this.removeExtension(s.data['extension']);
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
                ]
            })




        }); //end grid

        this.extensionsGrid.getSelectionModel().on('selectionchange', function(sm){
            var topBar = this.extensionsGrid.getTopToolbar();
            topBar.editBtn.setDisabled(sm.getCount() < 1);
            topBar.removeBtn.setDisabled(sm.getCount() < 1);
        },this);
        


        this.items = [this.extensionsGrid];

        ETVOIP.PBX.Extensions.Main.superclass.initComponent.apply(this);        

    }
    ,applyChanges:function(){
        this.fireEvent('onReloadAsterisk');
    }
    ,checkAsteriskReload:function(need){        
        if(need){
            this.extensionsGrid.getTopToolbar().setStatus({
								text: <?php echo json_encode(__('<i>Configuration changed</i>')) ?>,
								iconCls: 'icon-status-warning'
            });

        }
        else if(need==0){
            this.extensionsGrid.getTopToolbar().clearStatus();
        }                                
        
    }
    ,onRender:function() {

        // call parent
        ETVOIP.PBX.Extensions.Main.superclass.onRender.apply(this, arguments);

        // loads form after initial layout
        this.on('afterlayout', this.reloadExtensions, this, {single:true});

    } // eo function onRender
    ,loadWindowForm:function(fType){


        var extension = '';
        var action = 'add';
        var title = <?php echo json_encode(__('Add {0} Extension {1}')) ?>;
        switch(fType.action){
            case 'edit'  :
                                title = <?php echo json_encode(__('Edit {0} Extension {1}')) ?>;
                                extension = fType.extension;
                                action = fType.action;
                                break;
            default  :
                                break;
        }

        var dev_label = <?php echo json_encode(__('This device uses {0} technology.')) ?>;
        switch(fType.type){
            case 'sip'  :
                                title = String.format(title,'SIP',extension);
                                dev_label = String.format(dev_label,'SIP');
                                break;
            case 'iax2' :
                                title = String.format(title,'IAX2', extension);
                                dev_label = String.format(dev_label,'IAX2');
                                break;
            case 'zap'  :
                                title = String.format(title,'ZAP',extension);
                                dev_label = String.format(dev_label,'ZAP');
                                break;
            case 'custom':

                                title = String.format(title,'CUSTOM');
                                dev_label = String.format(dev_label,'CUSTOM');
            case ''      :
                                title = String.format(title,'VIRTUAL');
                                dev_label = '';

                                Ext.Msg.show({title: String.format(<?php echo json_encode(__('Error {0}')) ?>,'ETVA'),
                                    buttons: Ext.MessageBox.OK,
                                    msg: <?php echo json_encode(__('DEVICE TYPE OPERATION NOT YET SUPPORTED!')) ?>,
                                    icon: Ext.MessageBox.ERROR});
                                return;
                                break;
        }
        
        Ext.getBody().mask(<?php echo json_encode(__('Loading extension...')) ?>);
        
        var win = Ext.getCmp('etvoip-pbx-extensions-window');
        if(!win){

            var extensions_form = new ETVOIP.PBX.Extensions.Form();            
            win = new Ext.Window({
                    id: 'etvoip-pbx-extensions-window'
                    //,closeAction:'hide'
                    ,layout:'fit'
                    ,border:false
                    ,maximizable:true
                    ,maxW:700
                    ,maxH:400
                    ,defaultButton: extensions_form.getFocusField()
                    ,items: extensions_form                    
                    ,listeners:{                        
                        'close':function(){                        
                            Ext.EventManager.removeResizeListener(win.resizeFunc,win);
                        }
                    }
            });

            extensions_form.on({
                'onSave':{scope:this,fn:function(){this.reloadExtensions();win.close();}}
                ,'onCancel':function(){win.close();}
            });                           

        }

        
        
        var rec = {'data':{'extension':fType.extension,'service_id':this.service_id,'tech':fType.type,'dev_tech_label':dev_label,'action':action}};
        win.setTitle(title);
        win.get(0).buildLoad(rec);
        
        //on browser resize, resize window
        Ext.EventManager.onWindowResize(win.resizeFunc,win);

        win.resizeFunc();
        (function(){
            win.show(null,(function(){                            
                            if(fType.action == 'edit') win.get(0).remoteLoad(rec);                            
                          }
            ));

            Ext.getBody().unmask();
        }).defer(10);
    
    }
    ,reloadExtensions:function(){        
        this.extensionsGrid.store.reload();
        
    }    
    ,deleteExtension:function(exten){

            var conn = new Ext.data.Connection({
                listeners:{
                    scope:this,
                    beforerequest:function(){
                        this.getEl().mask(<?php echo json_encode(__('Removing extension...')) ?>);
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
                params:{id:this.service_id,method:'del_extension',params:Ext.encode({extension:exten})},                
                // everything ok...
                success: function(resp,opt){
                    var response = Ext.util.JSON.decode(resp.responseText);
                    var msg = String.format(<?php echo json_encode(__('Removed extension {0}')) ?>,exten);
                    this.reloadExtensions();
                    View.notify({html:msg});

                },scope:this
            });// END Ajax request
    }
    ,removeExtension:function(exten){

        Ext.MessageBox.show({
                title: <?php echo json_encode(__('Remove extension')) ?>,
                msg: String.format(<?php echo json_encode(__('You are about to remove extension {0}. <br />Are you sure ?')) ?>,exten),
                buttons: Ext.MessageBox.YESNO,
                fn: function(btn){

                    if(btn=='yes')
                        this.deleteExtension(exten);

                },
                scope:this,
                icon: Ext.MessageBox.WARNING
        });

    }

});

</script>