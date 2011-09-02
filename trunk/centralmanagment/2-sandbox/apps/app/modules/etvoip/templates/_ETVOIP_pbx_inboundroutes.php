<script>
Ext.ns('ETVOIP.PBX.Inboundroutes');


ETVOIP.PBX.Inboundroutes.Form = Ext.extend(Ext.form.FormPanel, {
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
//        this.tbarr = [{
//                        text: advance_off,
//                        enableToggle:true,
//                        scope:this,
//                        toggleHandler:function(item,pressed){
//
//                            if(pressed){
//                                item.setText(advance_on);
//                                this.showAdvanced();
//                            }else{
//                                item.setText(advance_off);
//                                this.showBasic();
//                            }
//
//                        }
//                    }];

        
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
        ETVOIP.PBX.Inboundroutes.Form.superclass.initComponent.apply(this, arguments);


    } // eo function initComp
    ,buildForm:function(){

    
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
                                    name:'extdisplay'
                                }
                                ,{
                                    xtype:'hidden',
                                    name:'service_id'
                                }
                                ,{
                                    xtype:'hidden',
                                    name:'mohclass',
                                    ref:'../../mohclass'
                                }
                                ,{
                                    xtype:'hidden',
                                    name:'pricid'
                                }
                                ,{
                                    xtype:'hidden',
                                    name:'privacyman'
                                }
                                ,{
                                    xtype:'hidden',
                                    name:'alertinfo'
                                }
                                ,{
                                    xtype:'hidden',
                                    name:'grppre'
                                }
                                ,{
                                    xtype:'hidden',
                                    name:'ringing'
                                }
                                ,{
                                    xtype:'hidden',
                                    name:'delay_answer'
                                }
                                ,{
                                    xtype:'hidden',
                                    name:'cidlookup_id'
                                }
                                ,{
                                    xtype:'hidden',
                                    name:'pmmaxretries',
                                    ref:'../../pmmaxretries'
                                }
                                ,{
                                    xtype:'hidden',
                                    name:'pmminlength',
                                    ref:'../../pmminlength'
                                }

                                /*
                                 * General settings
                                 */
                                ,{
                                    xtype:'fieldset',title: <?php echo json_encode(__('General settings')) ?>,anchor:'95%',
                                    defaultType:'textfield',                                    
                                    items:[
                                        {
                                            name: 'description',
                                            fieldLabel : <?php echo json_encode(__('Description')) ?>,
                                            hint: <?php echo json_encode(__('Provide a meaningful description of what this incoming route is')) ?>,
                                            allowBlank : false
                                        }                                        
                                        ,{
                                            name: 'extension',
                                            fieldLabel : <?php echo json_encode(__('DID Number')) ?>,
                                            hint: <?php echo json_encode(__('Define the expected DID Number if your trunk passes DID on incoming calls. <br><br>Leave this blank to match calls with any or no DID info.<br><br>You can also use a pattern match (eg _2[345]X) to match a range of numbers')) ?>
                                        }
                                        ,{
                                            name: 'cidnum',
                                            fieldLabel : <?php echo json_encode(__('Caller ID Number')) ?>,
                                            hint: <?php echo json_encode(__('Define the Caller ID Number to be matched on incoming calls.<br><br>Leave this field blank to match any or no CID info. In addition to standard dial sequences, you can also put Private, Blocked, Unknown, Restricted, Anonymous and Unavailable in order to catch these special cases if the Telco transmits them')) ?>
                                        }                                                                                                                        
                                    ]
                                }

                            ]//end items flex
                        }
                        ,{
                            flex:1,
                            items:[
                                /*
                                 * Set Destination
                                 */
                                {
                                    xtype:'fieldset',title: <?php echo json_encode(__('Set Destination')) ?>,anchor:'95%',
                                    defaultType:'textfield',
                                    ref:'../../destinations_panel',
                                    items:[]
                                }
                            ]
                        }
                    ]// end items hbox
                }// end hbox
                            
            ];            

        return configuration;

    }    
    ,getFocusField:function(){
        return this.getForm().findField('description');
    }
    ,showAdvanced:function(){
    }
    ,showBasic:function(){
    }
    //populate form fiels without ajax call
    ,buildLoad:function(record){

        this.getForm().clearInvalid();

        this.showBasic();
        this.resetDestinationsUI();
        this.getForm().reset();

        this.getForm().loadRecord(record);

    }
    ,resetDestinationsUI:function(){

        var cmbs_panel = this.destinations_panel.findByType('panel');

        for(var i=0; i<cmbs_panel.length; i++)
        {
            if(cmbs_panel[i].rendered)
                this.destinations_panel.remove(cmbs_panel[i]);
            else cmbs_panel[i].on('afterrender',function(cp){this.destinations_panel.remove(cp);},this);
        }


    }
    ,addDestinationUI:function(name,data,destination){
        
        var cb_name = name.replace(/ /g,'_');
        
        var store = new Ext.data.Store({
            reader: new Ext.data.JsonReader({
                root:'data',
                totalProperty:'total',
                fields: ['destination','description']
            })
        });
        
        this.destinations_panel.add(
            {
                layout:'table',autoScroll:true,
                xtype:'panel',                
                border:false,
                layoutConfig: {columns:2},
                defaults:{                    
                    layout:'form',
                    border:false},
                items:[
                    {                        
                        items:[
                            {
                                xtype: 'radio',
                                hideLabel:true,                                
                                boxLabel: name,                                
                                name: 'goto0',                                
                                inputValue: cb_name
                            }
                        ]
                    }
                    // 2nd col
                    ,{
                        bodyStyle:'padding-left:5px;',
                        items:[
                            {
                                anchor:'90%',
                                xtype:'combo',                                
                                hideLabel:true,                                
                                name: cb_name,
                                hiddenName: cb_name,                                                                
                                store: store,
                                displayField:'description',
                                valueField:'destination',
                                typeAhead:true,                                
                                mode:'local',
                                allowBlank : false,
                                forceSelection: true,
                                triggerAction: 'all',
                                selectOnFocus:true,                                
                                listeners:{                                    
                                    'focus':function(f){f.ownerCt.ownerCt.get(0).get(0).setValue(true);}
                                }
                                
                            }
                        ]
                    }
                ]
            }
        );

        
        var cmb_added = this.getForm().findField(cb_name);
        
        cmb_added.store.on('load',function(){            
            cmb_added.setValue(this.getAt(0).get('destination'));
        }
        );

        store.loadData(data);
        if(destination && store.findExact('destination',destination)!=-1){
            cmb_added.setValue(destination);                        
            var found_rbs = this.find('inputValue',cb_name);
            found_rbs[0].setValue(true);
            

        }

    }
    ,onRender:function() {
        // call parent
        ETVOIP.PBX.Inboundroutes.Form.superclass.onRender.apply(this, arguments);

        // set wait message target
        this.getForm().waitMsgTarget = this.getEl();
        
    } // eo function onRender
    ,remoteLoad:function(action,extdisplay){
        var service_id = this.getForm().findField('service_id').getValue();

        
        this.load({
            url:this.url,
            waitMsg: <?php echo json_encode(__('Loading incoming route data...')) ?>,
            params:{id:service_id,method:'get_inboundroute',params:Ext.encode({'extdisplay':extdisplay})},
            scope:this,
            success: function ( form, action ){
                var result = action.result;
                var data = result.data;
                
                var dests = result.data['destinations'];
                var destination = result.data['destination'];
                for(var d in dests)
                {
                    
                    this.addDestinationUI(d,dests[d],destination);
                    
                    
                }
                this.destinations_panel.doLayout();


                var mohclass = result.data['mohclass'];                
                if(!mohclass) mohclass = 'default';
                this.mohclass.setValue(mohclass);

                var pmmaxretries = result.data['pmmaxretries'];
                if(!pmmaxretries) pmmaxretries = 3;
                this.pmmaxretries.setValue(pmmaxretries);

                var pmminlength = result.data['pmminlength'];
                if(!pmminlength) pmminlength = 10;
                this.pmminlength.setValue(pmminlength);                                           
            }

        });

    } 
    ,save:function(){

        var alldata = this.form.getValues();
        var send_data = new Object();
        var send_data = alldata;

        if (this.form.isValid()) {

            var service_id = alldata['service_id'];
            var method = 'add_inboundroute';
            var wait_msg = <?php echo json_encode(__('Adding route...'))?>;
            var ok_msg = <?php echo json_encode(__('Added route {0}'))?>;

            if(alldata['action'] == 'edit'){
                method = 'edit_inboundroute';
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
                    var msg = String.format(ok_msg,alldata['description']);
                    View.notify({html:msg});
                    this.fireEvent('onSave');

                },scope:this
            });// END Ajax request


        }else{
            Ext.MessageBox.alert(<?php echo json_encode(__('Error!')) ?>, <?php echo json_encode(__('Please fix the errors noted!')) ?>);
        }
    }
});


ETVOIP.PBX.Inboundroutes.Main = Ext.extend(Ext.Panel, {
    layout:'fit',
    title: <?php echo json_encode(__('Inbound Routes')) ?>,
    initComponent:function(){

        var routesStore = new Ext.data.JsonStore({
                proxy:new Ext.data.HttpProxy({url:<?php echo json_encode(url_for('etvoip/json'))?>}),
                totalProperty: 'total',
                baseParams:{id:this.service_id,method:'get_inboundroutes'},
                root: 'data',
                fields: ['description','extension','cidnum'],
                listeners:{
                    load:{scope:this,fn:function(st,rcs,opts){

                        var need_reload = st.reader.jsonData.need_reload;
                        var pbxPanel = this.ownerCt;                        
                        
                        pbxPanel.fireEvent('notify_reload',pbxPanel,need_reload);                                                                          
                        
                    }
                }},scope:this
            });

        this.routesGrid = new Ext.grid.GridPanel({
                            border: false,
                            store: routesStore,
                            columns: [
                                        {header: "Description", sortable: false, dataIndex: 'description'}
                                        ,{header: "DID", sortable: false, dataIndex: 'extension'}
                                        ,{header: "CID", sortable: false, dataIndex: 'cidnum'}
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
                                                                                    extdisplay:s['data']['extension']+'/'+s['data']['cidnum']
                                                                                    
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

        ETVOIP.PBX.Inboundroutes.Main.superclass.initComponent.apply(this);

    }
    ,onRender:function() {

        // call parent
        ETVOIP.PBX.Inboundroutes.Main.superclass.onRender.apply(this, arguments);

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

        Ext.getBody().mask(<?php echo json_encode(__('Loading incoming route...')) ?>);

        var win = Ext.getCmp('etvoip-pbx-inboundroutes-window');
        if(!win){

            var routes_form = new ETVOIP.PBX.Inboundroutes.Form();            

            win = new Ext.Window({
                    id: 'etvoip-pbx-inboundroutes-window'
                    ,closeAction:'hide'
                    ,layout:'fit'
                    ,border:false
                    ,maxW:700
                    ,maxH:400
                    ,maximizable:true
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
        var title = <?php echo json_encode(__('Add Incoming Route')) ?>;
        
        switch(op.action){
            case 'edit'  :                            
                            title = String.format(<?php echo json_encode(__('Edit Incoming Route {0}')) ?>,op.extdisplay);
                            action = op.action;
                            break;
            default  :
                            break;
        }

        
                
     //   win.on('show',function(){win.get(0).loadRecord(rec);alert('s');});
        var rec = {'data':{'service_id':this.service_id,'action':action}};
        if(op.action == 'edit') rec['data'].extdisplay = op.extdisplay;
        
        win.get(0).buildLoad(rec);
        win.setTitle(title);

        win.resizeFunc();
        (function(){

            win.show(null,(function(){
                           // if(op.action == 'edit')
                                win.get(0).remoteLoad(op.action,op.extdisplay);
                            }
            ));

            Ext.getBody().unmask();
        }).defer(10);



    }
    ,deleteRoute:function(rec){
        var extdisplay = rec.data['extension']+'/'+rec.data['cidnum'];
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
            params:{id:this.service_id,method:'del_inboundroute',params:Ext.encode({extdisplay:extdisplay})},
            // everything ok...
            success: function(resp,opt){
                var response = Ext.util.JSON.decode(resp.responseText);
                var msg = String.format(<?php echo json_encode(__('Removed route {0}')) ?>,extdisplay);
                this.reloadRoutes();
                View.notify({html:msg});

            },scope:this
        });// END Ajax request
        
    }
    ,removeRoute:function(rec){

            
        var extdisplay = rec.data['extension']+'/'+rec.data['cidnum'];

        Ext.MessageBox.show({
                title: <?php echo json_encode(__('Remove route')) ?>,
                msg: String.format(<?php echo json_encode(__('You are about to remove inbound route {0}. <br />Are you sure ?')) ?>,extdisplay),
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