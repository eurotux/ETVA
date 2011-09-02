<script>    

ETFW.SNMP.Settings = Ext.extend(Ext.form.FormPanel, {    
    border:false    
    ,labelWidth:150
    ,url:<?php echo json_encode(url_for('etfw/json'))?>
    ,defaults:{anchor:'90%'}
    ,initComponent:function() {

        this.nmsGrid = new Ext.grid.EditorGridPanel({
                            border: false,
                            store: new Ext.data.JsonStore({
                                        root: 'data',
                                        fields: ['community', 'source']
                            }),
                            columns: [
                                        {
                                            header: "Source", sortable: false, dataIndex: 'source',
                                            editor: {xtype: 'textfield',selectOnFocus:true,allowBlank: false}
                                        },
                                        {
                                            header: "Community", sortable: false, dataIndex: 'community',
                                            editor: {xtype: 'textfield',selectOnFocus:true,allowBlank: false}
                                        }],
                            loadMask: true,                                    
                            sm: new Ext.grid.RowSelectionModel({
                                    singleSelect: true,
                                    moveEditorOnEnter:false
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
                                        ,text: <?php echo json_encode(__('Add station')) ?>
                                        ,scope:this
                                        ,handler: function(){

                                                    var nms_Record = this.nmsGrid.store.recordType;
                                                    var nms_r = new nms_Record({
                                                                        source: __('Add...'),
                                                                        community: __('Add...')});

                                                    this.nmsGrid.stopEditing();
                                                    this.nmsGrid.store.insert(0, nms_r);
                                                    this.nmsGrid.startEditing(0,0);
                                        }
                                    }
                                    ,'-'
                                    ,{
                                        text: <?php echo json_encode(__('Edit station')) ?>
                                        ,ref: '../editBtn'
                                        ,tooltip: <?php echo json_encode(__('Edit selected station')) ?>
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
                                        text: <?php echo json_encode(__('Remove station')) ?>
                                        ,ref: '../removeBtn'
                                        ,tooltip: <?php echo json_encode(__('Remove selected station')) ?>
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


        this.nmsGrid.getSelectionModel().on({
            selectionchange:{scope:this,fn:function(sm){
                                var btnState = sm.getCount() < 1 ? true :false;
                                
                                this.nmsGrid.editBtn.setDisabled(btnState);
                                this.nmsGrid.removeBtn.setDisabled(btnState);
            }}
        });


        var refreshBtn = new Ext.Button({
                                text: __('Refresh'),
                                tooltip: __('Refresh'),
                                iconCls: 'x-tbar-loading',
                                ref:'refreshBtn',
                                scope:this,
                                handler: function(button,event){this.loadData();}
        });

        var savebtn = new Ext.Button({text: __('Save'),iconCls:'page-save',handler:this.onSave,scope:this});


        var allFields = [
                            {xtype:'hidden',name:'service_id', value:this.service_id}
                            ,{
                                xtype:'fieldset',
                                title: <?php echo json_encode(__('System information')) ?>,border:false,
                                defaultType:'textfield',
                                defaults:{border:false,anchor:'90%'},
                                items:[
                                    {fieldLabel: <?php echo json_encode(__('System location')) ?>, name:'syslocation'},
                                    {fieldLabel: <?php echo json_encode(__('System contact')) ?>, name:'syscontact'}]
                            }
                            ,{
                                xtype:'fieldset',
                                title: <?php echo json_encode(__('Trap information')) ?>,border:false,
                                defaultType:'textfield',
                                defaults:{border:false,anchor:'90%'},
                                items:[
                                    {fieldLabel: <?php echo json_encode(__('Trap community')) ?>, name:'trapcommunity'},
                                    {fieldLabel:<?php echo json_encode(__('Trap server')) ?>, name:'trapsink'}
                                ]
                            }
                            ,{
                                xtype:'fieldset',
                                title: <?php echo json_encode(__('Management stations')) ?>,border:false,
                                defaults:{border:false},
                                items:[this.nmsGrid]}
        ];


        var config = {
            bodyStyle:'padding-top:10px',
            defaultType: 'textfield',
            buttonAlign:'left',
            autoScroll:true,            
            items: [allFields],
            tbar: [refreshBtn],
            bbar: [savebtn]

        };



        // apply config
        Ext.apply(this, Ext.apply(this.initialConfig, config));

        // call parent
        ETFW.SNMP.Settings.superclass.initComponent.apply(this, arguments);


    } // eo function initComponent
    ,onRender:function() {
        // call parent
        ETFW.SNMP.Settings.superclass.onRender.apply(this, arguments);

        // set wait message target
        this.getForm().waitMsgTarget = this.ownerCt.getEl();
        this.loadData();

    } // eo function onRender
    ,loadData:function(){
        var tBar = this.getTopToolbar();
        tBar.refreshBtn.addClass('x-item-disabled');
        this.load({
            url:<?php echo json_encode(url_for('etfw/json'))?>
            ,waitMsg: <?php echo json_encode(__('Retrieving data...')) ?>
            ,params:{id:this.service_id,method:'get_config'}
            ,scope:this
            ,success:function(form, action){
                                
                tBar.refreshBtn.removeClass('x-item-disabled');
                var rec = action.result;

                //populate grid
                var gridRecs = rec.data['security'];
                this.nmsGrid.getStore().loadData(gridRecs);

            }
            ,failure:function(){
                tBar.refreshBtn.removeClass('x-item-disabled');
            }
            
        });

    }
    //populate form fiels without ajax call
    ,loadRecord:function(records){

        var gridRecs = records.data['security'];
        
        this.getForm().loadRecord(records);
        this.nmsGrid.getStore().loadData(gridRecs);
            
    }    
    ,onSave:function(){


        var alldata = this.form.getValues();
        var send_data = new Object();

        if (this.form.isValid()) {

            var service_id = alldata['service_id'];


            var nms=[];
            var nms_store = this.nmsGrid.getStore();

            nms_store.each(function(f){
                    var data = f.data;
                    var insert = {'source':data['source'],'community':data['community']};
                    nms.push(insert);
            });
            send_data['security'] = nms;

            send_data['directives'] = {'syslocation':alldata['syslocation'],
                                        'syscontact':alldata['syscontact'],
                                        'trapcommunity':alldata['trapcommunity'],
                                        'trapsink':alldata['trapsink']};
            

            var conn = new Ext.data.Connection({
                listeners:{
                    scope:this,                    
                    beforerequest:function(){                        
                        this.getEl().mask(<?php echo json_encode(__('Updating SNMP configuration...')) ?>);
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
                params:{id:service_id,method:'set_config',params:Ext.encode(send_data)},
                failure: function(resp,opt){
                    
                    var response = Ext.util.JSON.decode(resp.responseText);
                    Ext.ux.Logger.error(response['agent'], response['error']);

                },
                // everything ok...
                success: function(resp,opt){
                    var response = Ext.util.JSON.decode(resp.responseText);
                    var msg = <?php echo json_encode(__('Updated SNMP configuration')) ?>;
                    View.notify({html:msg});                    
                    this.loadRecord(response);

                },scope:this
            });// END Ajax request


        }else{
            Ext.MessageBox.alert(<?php echo json_encode(__('Error!')) ?>, <?php echo json_encode(__('Please fix the errors noted!')) ?>);
        }
    }


}); // eo extend


</script>