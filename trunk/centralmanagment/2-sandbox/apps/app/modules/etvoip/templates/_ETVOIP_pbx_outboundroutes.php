<script>
Ext.ns('ETVOIP.PBX.Outboundroutes');


ETVOIP.PBX.Outboundroutes.Form = Ext.extend(Ext.form.FormPanel, {
    border:true
    ,defaults:{border:false}
    ,labelWidth:145
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
        ETVOIP.PBX.Outboundroutes.Form.superclass.initComponent.apply(this, arguments);


    } // eo function initComp
    ,buildForm:function(){        

        /*
         * MOH COMBO STORE
         */
        this.st_moh = new Ext.data.Store({
            reader: new Ext.data.JsonReader({
                root:'data',
                fields: ['dir']
            })
        });

        /*
         *
         * TRUNKS PRIORITY
         *
         */
        this.st_priority = new Ext.data.Store({
            reader: new Ext.data.ArrayReader({
                root:'data',
                fields: ['trunkid','name', 'disabled']
            })
        });
   
        var configuration =
            [                
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
                                /*
                                 * General settings
                                 */
                                ,{
                                    xtype:'fieldset',title: <?php echo json_encode(__('General settings')) ?>,anchor:'95%',
                                    defaultType:'textfield',
                                    ref:'../../general_panel',
                                    items:[
                                        {
                                            name: 'routename',
                                            vtype:'no_spaces',
                                            fieldLabel : <?php echo json_encode(__('Route Name')) ?>,
                                            hint: <?php echo json_encode(__('Name of this route. Should be used to describe what type of calls this route matches (for example, \'local\' or \'longdistance\')')) ?>,
                                            allowBlank : false
                                        }
                                        ,{
                                            layout:'table',
                                            xtype:'panel',
                                            ref: 'routecid',
                                            border:false,
                                            layoutConfig: {columns:2},
                                            defaults:{layout:'form',border:false},
                                            items:[
                                                {
                                                    items:[
                                                        {
                                                            name: 'routecid',
                                                            xtype: 'textfield',
                                                            fieldLabel : <?php echo json_encode(__('Route CID')) ?>,
                                                            hint: <?php echo json_encode(__('Optional: Route CID to be used for this route. If set, this will override all CIDs specified except:<br>&nbsp;&nbsp;- extension/device EMERGENCY CIDs if this route is checked as an EMERGENCY Route<br>&nbsp;&nbsp;- trunk CID if trunk is set to force it\'s CID<br>&nbsp;&nbsp;- Forwarded call CIDs (CF, Follow Me, Ring Groups, etc)<br>&nbsp;&nbsp;- Extension/User CIDs if checked')) ?>
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
                                                            boxLabel: <?php echo json_encode(__('Override Extension CID')) ?>,
                                                            name: 'routecid_mode', inputValue:'override_extension'
                                                        }
                                                    ]
                                                }
                                            ]
                                        }
                                        ,{
                                            name: 'routepass',
                                            ref: 'routepass',
                                            fieldLabel : <?php echo json_encode(__('Route Password')) ?>,
                                            hint: <?php echo json_encode(__('Optional: A route can prompt users for a password before allowing calls to progress.  This is useful for restricting calls to international destinations or 1-900 numbers.<br><br>A numerical password, or the path to an Authenticate password file can be used.<br><br>Leave this field blank to not prompt for password')) ?>
                                        }                                        
/*
                                        ,{
                                            xtype:'combo',
                                            fieldLabel: <?php // echo json_encode(__('PIN Set')) ?>,
                                            name: 'pinsets',
                                            hiddenName: 'pinsets',
                                            hint: 'Optional: Select a PIN set to use. If using this option, leave the Route Password field blank',
                                            store: new Ext.data.ArrayStore({
                                                fields: ['value', 'name'],
                                                data: [['','None']]
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
*/
                                        ,{
                                            xtype: 'checkbox',
                                            fieldLabel: <?php echo json_encode(__('Emergency Dialing')) ?>,
                                            ref: 'emergency',
                                            hint: <?php echo json_encode(__('Optional: Selecting this option will enforce the use of a device\'s Emergency CID setting (if set).  Select this option if this set of routes is used for emergency dialing (ie: 911)')) ?>,
                                            name: 'emergency', inputValue:'yes'
                                        }
                                        ,{
                                            xtype: 'checkbox',
                                            ref: 'intracompany',
                                            fieldLabel: <?php echo json_encode(__('Intra Company Route')) ?>,
                                            hint: <?php echo json_encode(__('Optional: Selecting this option will treat this route as a intra-company connection, preserving the internal Caller ID information and not use the outbound CID of either the extension or trunk')) ?>,
                                            name: 'intracompany', inputValue:'yes'
                                        }
                                        ,{
                                            xtype:'combo',
                                            fieldLabel: <?php echo json_encode(__('Music On Hold')) ?>,
                                            labelSeparator:'?',
                                            name: 'mohsilence',
                                            ref: 'mohsilence',
                                            hiddenName: 'mohsilence',
                                            hint: <?php echo json_encode(__('You can choose which music category to use. For example, choose a type appropriate for a destination country which may have announcements in the appropriate language')) ?>,
                                            store: this.st_moh,
                                            displayField:'dir',
                                            valueField:'dir',
                                            typeAhead:true,
                                            value:'default',
                                            mode:'local',
                                            forceSelection: true,
                                            triggerAction: 'all',
                                            selectOnFocus:true,
                                            width: 120
                                        }
                                        ,{
                                            name: 'dialpattern',
                                            xtype:'textarea',
                                            allowBlank : false,
                                            fieldLabel : <?php echo json_encode(__('Dial Patterns')) ?>,
                                            hint: <?php echo json_encode(__('A Dial Pattern is a unique set of digits that will select this trunk. Enter one dial pattern per line.<br><br>
                                                <b>Rules:</b><br><strong>X</strong> matches any digit from 0-9<br>
                                                <strong>Z</strong> matches any digit from 1-9<br>
                                                <strong>N</strong> matches any digit from 2-9<br>
                                                <strong>[1237-9]</strong> matches any digit or letter in the brackets (in this example, 1,2,3,7,8,9)<br>
                                                <strong>.</strong> wildcard, matches one or more characters <br>
                                                <strong>|</strong> separates a dialing prefix from the number (for example, 9|NXXXXXX would match when some dialed "95551234" but would only pass "5551234" to the trunks)<br />
                                                <strong>/</strong> appended to a dial pattern, matches a callerid or callerid pattern (for example, NXXXXXX/104 would match only if dialed by extension "104")')) ?>
                                        }
                                    ]
                                }

                            ]//end items flex
                        }
                        ,{
                            flex:1,
                            items:[
                                /*
                                 * Trunk Sequence
                                 */
                                {
                                    xtype:'fieldset',title: <?php echo json_encode(__('Trunks')) ?>,anchor:'95%',
                                    defaultType:'textfield',
                                    ref:'../../trunks_panel',
                                    items:[
                                        {
                                            xtype:'button'
                                            ,text: <?php echo json_encode(__('Add trunk')) ?>
                                            ,scope:this
                                            ,handler:function(){this.addTrunkUI(1);}
                                        }
                                    ]
                                }
                            ]
                        }
                    ]// end items hbox
                }// end hbox
            ];

        return configuration;

    }
    ,disableRoutename:function(disable){
        this.getForm().findField('routename').setDisabled(disable);
    }
    ,resetTrunkUI:function(){
                
        var cmbs = this.trunks_panel.findByType('combo');        
        for(var i=1; i<cmbs.length; i++)
        {
            if(cmbs[i].rendered) this.trunks_panel.remove(cmbs[i]);
            else cmbs[i].on('afterrender',function(cp){this.trunks_panel.remove(cp);},this);
        }       

    }
    ,addTrunkUI:function(howMany){                

        var cmbs = this.trunks_panel.findByType('combo');             
        var i = 0;


        for(var i = 0;i<howMany;i++)
        {
            this.trunks_panel.add(
                    {
                        layout:'table',
                        xtype:'panel',
                        border:false,
                        layoutConfig: {columns:2},
                        defaults:{layout:'form',border:false},
                        items:[
                            {
                                items:[
                                    {
                                        xtype:'combo',
                                        name: 'trunkpriority',
                                        hiddenName: 'trunkpriority',
                                        hint: <?php echo json_encode(__('The Trunk Sequence controls the order of trunks that will be used when the above Dial Patterns are matched. <br><br>For Dial Patterns that match long distance numbers, for example, you\'d want to pick the cheapest routes for long distance (ie, VoIP trunks first) followed by more expensive routes (POTS lines)')) ?>,
                                        tpl:'<tpl for="."><div class="x-combo-list-item">{name} <tpl if="values.disabled==\'on\'"> <i><font color="red">Disabled</i></font></tpl></div></tpl>',
                                        store: this.st_priority,
                                        displayField:'name',
                                        valueField:'trunkid',
                                        allowBlank:false,
                                        //valueField:'value',
                                        typeAhead:true,
                                        //value:'',
                                        mode:'local',
                                        forceSelection: true,
                                        triggerAction: 'all',
                                        selectOnFocus:true,
                                        width: 120
                                    }
                                ]
                            }
                            // 2nd col
                            ,{
                                bodyStyle:'padding-left:5px;',
                                items:[
                                    {
                                        xtype:'button'
                                        ,text: __('Remove')
                                        ,handler:function(){
                                            var curP = this.ownerCt.ownerCt;
                                            var parentP = curP.ownerCt;
                                            
                                            parentP.remove(curP);
                                        }
                                    }
                                ]
                            }
                        ]
                    }
            );
        }
        this.trunks_panel.doLayout();



    }
    ,getFocusField:function(){
        return this.getForm().findField('routename').disabled ? this.getForm().findField('routecid'): this.getForm().findField('routename') ;
    }
    ,showAdvanced:function(){
        this.general_panel.routepass.showAll(true);
        this.general_panel.routecid.show();
        this.general_panel.emergency.showAll(true);
        this.general_panel.intracompany.showAll(true);
        this.general_panel.mohsilence.showAll(true);
    }
    ,showBasic:function(){        
        this.general_panel.routepass.showAll(false);
        this.general_panel.routecid.hide();
        this.general_panel.emergency.showAll(false);
        this.general_panel.intracompany.showAll(false);
        this.general_panel.mohsilence.showAll(false);


    }
    //populate form fiels without ajax call
    ,buildLoad:function(record){

        this.getForm().clearInvalid();
        
              

        this.showBasic();
        this.resetTrunkUI();
        this.getForm().reset();        
        
        this.getForm().loadRecord(record);

    }
    ,onRender:function() {
        // call parent
        ETVOIP.PBX.Outboundroutes.Form.superclass.onRender.apply(this, arguments);

        // set wait message target
        this.getForm().waitMsgTarget = this.getEl();
        
    } // eo function onRender
    ,remoteLoad:function(op,route){
        var service_id = this.getForm().findField('service_id').getValue();

        
        this.load({
            url:this.url,
            waitMsg: <?php echo json_encode(__('Loading route data...')) ?>,
            params:{id:service_id,method:'<?php echo ETVOIP_PBX::GET_OUTBOUNDROUTE ?>',params:Ext.encode({'routename':route})},
            scope:this,
            success: function ( form, action ){
                var result = action.result;
                var data = result.data;

                var ntrunks = data['trunkpriority'].length;
                if(ntrunks==0) ntrunks++;
                
                this.addTrunkUI(ntrunks);
                
                this.st_priority.loadData(result.data['priorities']);
                this.st_moh.loadData(result.data['moh']);

                var mohsilence = result.data['mohsilence'];
                if(mohsilence == '') mohsilence = 'default';                
                this.general_panel.mohsilence.setValue(mohsilence);

                var cmbs = this.trunks_panel.findByType('combo');

                for(var i=0; i<cmbs.length; i++)
                {
                    if(data["trunkpriority"][i]) cmbs[i].setValue(data["trunkpriority"][i]);
                }

                
                if(op=='edit')
                {
                    this.disableRoutename(true);
                }
                
                this.getFocusField().focus();

            }

        });

    }    
    ,save:function(){


        var alldata = this.form.getValues();
        var send_data = new Object();
        var send_data = alldata;
        send_data['routename'] = this.getForm().findField('routename').getValue();
        var cmbs = this.trunks_panel.findByType('combo');

        if (this.form.isValid()) {

            if(cmbs.length == 0){
                Ext.MessageBox.alert(<?php echo json_encode(__('Error!')) ?>, <?php echo json_encode(__('At least one trunk needed!')) ?>);
                return;
                
            }

            var service_id = alldata['service_id'];
            var method = 'add_outboundroute';
            var wait_msg = <?php echo json_encode(__('Adding route...'))?>;
            var ok_msg = <?php echo json_encode(__('Added route {0}'))?>;

            if(alldata['action'] == 'edit'){
                method = 'edit_outboundroute';
                wait_msg = <?php echo json_encode(__('Updating route...'))?>;
                ok_msg = <?php echo json_encode(__('Updated route {0}'))?>;
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
           //     form: this.form.getEl().dom,method:'POST',
                // everything ok...
                success: function(resp,opt){
                    var response = Ext.util.JSON.decode(resp.responseText);
                    var msg = String.format(ok_msg,alldata['routename']);
                    View.notify({html:msg});
                    this.fireEvent('onSave');

                },scope:this
            });// END Ajax request


        }else{
            Ext.MessageBox.alert(<?php echo json_encode(__('Error!')) ?>, <?php echo json_encode(__('Please fix the errors noted!')) ?>);
        }
    }

});


ETVOIP.PBX.Outboundroutes.Main = Ext.extend(Ext.Panel, {
    layout:'fit',
    title: <?php echo json_encode(__('Outbound Routes')) ?>,
    initComponent:function(){

        var routesStore = new Ext.data.JsonStore({
                proxy:new Ext.data.HttpProxy({url:<?php echo json_encode(url_for('etvoip/json'))?>}),
                totalProperty: 'total',
                baseParams:{id:this.service_id,method:'get_outboundroutes'},
                root: 'data',
                fields: ['context'],
                listeners:{
                    load:{scope:this,fn:function(st,rcs,opts){

                        var need_reload = st.reader.jsonData.need_reload;
                        var pbxPanel = this.ownerCt;

                        pbxPanel.fireEvent('notify_reload',pbxPanel,need_reload);

                    }
                }}
        });

        this.routesGrid = new Ext.grid.GridPanel({
                            border: false,
                            store: routesStore,
                            columns: [
                                        {
                                            header: "Context", sortable: false, dataIndex: 'context'
                                        }
                                        ],
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
                                            store:routesStore,
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
                                        ,text: <?php echo json_encode(__('Add route')) ?>
                                        ,scope:this
                                        ,handler:function(){
                                            (function(){this.loadWindowForm({action:'add'})}).defer(10,this);
                                            return true;
                                        }                                        
                                    }
                                    ,'-'
                                    ,{
                                        text: <?php echo json_encode(__('Edit route')) ?>
                                        ,ref: 'editBtn'
                                        ,iconCls:'icon-edit-record'
                                        ,tooltip: <?php echo json_encode(__('Edit selected route')) ?>
                                        ,disabled:true
                                        ,scope:this
                                        ,handler: function(){
                                            var s = this.routesGrid.getSelectionModel().getSelected();
                                            if(s){
                                                (function(){this.loadWindowForm({                                                                                    
                                                                                    action:'edit',
                                                                                    route:s['data']['context']
                                                                                    
                                                                                })}).defer(10,this);
                                                return true;
                                            }   
                                        }
                                    }
                                    ,'-'
                                    ,{
                                        text: <?php echo json_encode(__('Remove route')) ?>
                                        ,ref: 'removeBtn'
                                        ,tooltip: <?php echo json_encode(__('Remove selected route')) ?>
                                        ,iconCls:'remove'
                                        ,disabled:true
                                        ,scope:this
                                        ,handler: function(){
                                            var s = this.routesGrid.getSelectionModel().getSelected();
                                            if(s) this.removeRoute(s);
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

        this.routesGrid.getSelectionModel().on('selectionchange', function(sm){
            var topBar = this.routesGrid.getTopToolbar();
            topBar.editBtn.setDisabled(sm.getCount() < 1);
            topBar.removeBtn.setDisabled(sm.getCount() < 1);
        },this);


        this.items = [this.routesGrid];

        ETVOIP.PBX.Outboundroutes.Main.superclass.initComponent.apply(this);

    }
    ,onRender:function() {

        // call parent
        ETVOIP.PBX.Outboundroutes.Main.superclass.onRender.apply(this, arguments);

        // loads form after initial layout
        this.on('afterlayout', this.reloadRoutes, this, {single:true});

    } // eo function onRender
    ,applyChanges:function(){
        this.fireEvent('onReloadAsterisk');
    }
    ,checkAsteriskReload:function(need){
        if(need){
            this.routesGrid.getTopToolbar().setStatus({
								text: <?php echo json_encode(__('<i>Configuration changed</i>')) ?>,
								iconCls: 'icon-status-warning'
            });

        }
        else if(need==0){
            this.routesGrid.getTopToolbar().clearStatus();
        }

    }
    ,reloadRoutes:function(){
        this.routesGrid.store.reload();

    }
    ,loadWindowForm:function(op){

        Ext.getBody().mask(<?php echo json_encode(__('Loading route...')) ?>);

        var win = Ext.getCmp('etvoip-pbx-outboundroutes-window');
        if(!win){

            var routes_form = new ETVOIP.PBX.Outboundroutes.Form();

            win = new Ext.Window({
                    id: 'etvoip-pbx-outboundroutes-window'                    
                    ,layout:'fit'
                    ,border:false
                    ,maximizable:true
                    ,maxW:1000
                    ,maxH:400
                    ,defaultButton: routes_form.getFocusField()
                    ,items: routes_form
                    ,listeners:{
                        'close':function(){
                            Ext.EventManager.removeResizeListener(win.resizeFunc,win);
                        }
                    }
            });

            routes_form.on({
                'onSave':{scope:this,fn:function(){this.reloadRoutes();win.close();}}
                ,'onCancel':function(){win.close();}
            });

            //on browser resize, resize window
            Ext.EventManager.onWindowResize(win.resizeFunc,win);

        }
                
        var action = 'add';
        var title = <?php echo json_encode(__('Add route')) ?>;
        
        switch(op.action){
            case 'edit'  :                            
                            title = String.format(<?php echo json_encode(__('Edit route {0}')) ?>,op.route);
                            action = op.action;
                            break;
            default  :
                            break;
        }

                             
        var rec = {'data':{'service_id':this.service_id,'action':action}};
        if(op.action == 'edit') rec['data'].routename = op.route;
        
        win.get(0).buildLoad(rec);
        win.setTitle(title);

        win.resizeFunc();
        (function(){            
            win.show(null,(function(){
                            win.get(0).remoteLoad(op.action,op.route);}
            ));
            Ext.getBody().unmask();
            
        }).defer(10);

    }    
    ,deleteRoute:function(rec){

            var route = rec.data['context'];

            var conn = new Ext.data.Connection({
                listeners:{
                    scope:this,
                    beforerequest:function(){
                        this.getEl().mask(<?php echo json_encode(__('Removing route...')) ?>);
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
                params:{id:this.service_id,method:'del_outboundroute',params:Ext.encode({routename:route})},
                // everything ok...
                success: function(resp,opt){
                    var response = Ext.util.JSON.decode(resp.responseText);
                    var msg = String.format(<?php echo json_encode(__('Removed route {0}')) ?>,route);
                    this.reloadRoutes();
                    View.notify({html:msg});

                },scope:this
            });// END Ajax request

    }
    ,removeRoute:function(rec){


        var route = rec.data['context'];

        Ext.MessageBox.show({
                title: <?php echo json_encode(__('Remove route')) ?>,
                msg: String.format(<?php echo json_encode(__('You are about to remove outbound route {0}. <br />Are you sure ?')) ?>,route),
                buttons: Ext.MessageBox.YESNO,
                fn: function(btn){

                    if(btn=='yes')
                        this.deleteRoute(rec);

                },
                scope:this,
                icon: Ext.MessageBox.WARNING
        });

    }

});

</script>