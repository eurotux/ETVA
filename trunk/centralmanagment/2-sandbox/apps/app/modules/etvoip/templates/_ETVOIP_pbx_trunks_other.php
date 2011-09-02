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

        var configuration = 
            
            [
            {
                layout: 'vbox',
                layoutConfig:{align:'stretch'},
                items:[
                    {flex:1,
                        items:[
                                {
                                    xtype:'displayfield'
                                    ,value:'dasd dasdas dsda sdasd'
                                }
                        ]
                    }
                    ,
                    {flex:2,
                        layout:'fit',
                        items:[                            


                            {

                                bodyStyle:'padding:5px;'
                                ,layout: {
                                    type: 'hbox',
                                    align: 'stretch'  // Child items are stretched to full width
                                }
                                ,defaults:{layout:'form',defaultType:'textfield',autoScroll:true,border:false}
                                ,items:[{



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

                                                ]
                                            }

                                        ]//end items flex
                                    }
                                    ,{
                                        flex:1,
                                        items:[
                                            /*
                                             * Outgoing settings
                                             */
                                            {
                                                xtype:'fieldset',title: <?php echo json_encode(__('Outgoing Settings')) ?>,anchor:'95%',
                                                defaultType:'textfield',
                                                items:[],
                                                ref:'../../outgoing_settings_panel'
                                            }
                                        ]
                                    }
                                ]
                            }




                        ]
                    }
                ]

            }
            
                
                

            ];

        return configuration;

    }
    ,loadRecord:function(record){

    }
    //populate form fiels without ajax call
    ,losadRecord:function(record){
        
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