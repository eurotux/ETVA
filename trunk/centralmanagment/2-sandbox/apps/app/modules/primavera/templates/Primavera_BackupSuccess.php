<script>

Ext.ns('Primavera.Backup');

Primavera.Backup.TabPanel = new Ext.extend( Ext.TabPanel, {

    id: 'primavera-backup-tabpanel',
    border:false
    ,monitorValid:true
    ,activeItem:0
    ,initComponent:function() {
        
        var service_id = this.service_id;
        this.items = [
                    new Ext.grid.GridPanel({
                        id: 'grid-backup-plans',
                        title: <?php echo json_encode(__('Backup plans')) ?>,
                        layout: 'fit',
                        viewConfig: { forceFit: true },
                        frame: true,
                        loadMask:true,    
                        store: new Ext.data.Store({
                            url:<?php echo json_encode(url_for('primavera/json'))?>,
                            baseParams: {id:this.service_id,method:'primavera_listbackupplans'},
                            reader: new Ext.data.JsonReader(
                                            {
                                                idProperty: 'id'
                                                ,root: 'data'
                                                ,fields: [
                                                            {name: 'id'},
                                                            {name: 'name'},
                                                            {name: 'date'},
                                                            {name: 'lastExecution'},
                                                            {name: 'nextExecution'},
                                                            {name: 'schedule'},
                                                            {name: 'companies'},
                                                            {name: 'verify'},
                                                            {name: 'overwrite'},
                                                            {name: 'incremental'}
                                                        ]
                                            }
                                        )
                        }),
                        colModel: new Ext.grid.ColumnModel({
                            defaults: {
                                width: 120,
                                sortable: true
                            },
                            columns: [
                                {id: 'id', header: __('id'), sortable: true, dataIndex: 'id', width:80 },
                                {header: __('Name'), dataIndex: 'name', width:200},
                                {header: __('Date'), dataIndex: 'date', width:80},
                                {header: __('Last Execution'), dataIndex: 'lastExecution', width:80},
                                {header: __('Next Execution'), dataIndex: 'nextExecution', width:80},
                                {header: __('Schedule'), dataIndex: 'schedule', width:80, renderer:function(v){return __(v);}},
                                {header: __('Databases'), dataIndex: 'companies', width:200},
                                {header: __('Verify'), dataIndex: 'verify', width:60, renderer:function(v){return __(v);}},
                                {header: __('Overwrite'), dataIndex: 'overwrite', width:60, renderer:function(v){return __(v);}},
                                {header: __('Incremental'), dataIndex: 'incremental', width:60, renderer:function(v){return __(v);}}
                            ]
                        }),
                        sm: new Ext.grid.RowSelectionModel({ singleSelect:true,
                                                                'listeners': {
                                                                        selectionchange: { fn: function(sm){
                                                                                                    var btnState = sm.getCount() < 1 ? true :false;
                                                                                                    var selected = sm.getSelected();
                                                                                                    if( sm.grid.delbkplanBtn ){
                                                                                                        sm.grid.delbkplanBtn.setDisabled(btnState);
                                                                                                    }
                                                                                                    /*if( sm.grid.editbkplanBtn ){
                                                                                                        sm.grid.editbkplanBtn.setDisabled(btnState);
                                                                                                    }*/
                                                                                                }}} }),
                        iconCls: 'icon-grid',
                        tbar: [
                                    /*{
                                        text: <?php echo json_encode(__('Edit backup plan')) ?>,
                                        ref: '../editbkplanBtn',
                                        disabled: true,
                                        url: <?php echo(json_encode(url_for('primavera/Primavera_EditBackPlan')))?>,
                                        call:'Primavera.EditPlan',
                                        scope:this,
                                        callback: function(item) {
                                            var service_id = item.scope.form.findField('id').getValue();
                                            var window = new Primavera.EditBackupPlan.Window({
                                                                title: <?php echo json_encode(__('Edit backup plan')) ?>,
                                                                service_id:service_id });

                                            var bkpplandata;
                                            var selected = item.ownerCt.ownerCt.getSelectionModel().getSelected();
                                            if( selected ){
                                                bkpplandata = selected.data;

                                                window.on({
                                                    show:{fn:function(){window.loadData({service_id:service_id, plan:bpkplandata});}}
                                                    ,onSave:{fn:function(){
                                                            this.close();
                                                            var parentCmp = Ext.getCmp((item.scope).ownerCt.id);
                                                            parentCmp.fireEvent('refresh',parentCmp);
                                                    }}
                                                });
                                                
                                                window.show();

                                            } else {
                                                Ext.Msg.show({
                                                    title: <?php echo json_encode(__('Error no backup plan selected')) ?>,
                                                    buttons: Ext.MessageBox.OK,
                                                    msg: <?php echo json_encode(__('No backup plan selected for update!')) ?>,
                                                    icon: Ext.MessageBox.ERROR});
                                            }
                                        },
                                        handler: function(btn){View.loadComponent(btn);}
                                    },*/
                                    {
                                        text: <?php echo json_encode(__('Remove backup plan')) ?>,
                                        ref: '../delbkplanBtn',
                                        iconCls: 'icon-remove',
                                        disabled: true,
                                        url: <?php echo(json_encode(url_for('primavera/Primavera_RemoveBackupPlan')))?>,
                                        call:'Primavera.RemoveBackupPlan',
                                        scope:this,
                                        handler: function(item) {
                                                            var selected = item.ownerCt.ownerCt.getSelectionModel().getSelected();
                                                            if( selected ){
                                                                Ext.Msg.show({
                                                                    title: item.text,
                                                                    buttons: Ext.MessageBox.YESNOCANCEL,
                                                                    scope:this,
                                                                    msg: String.format(<?php echo json_encode(__('Are you sure you want delete backup plan {0}?')) ?>,selected.data['id']),
                                                                    fn: function(btn){
                                                                                    if (btn == 'yes'){
                                                                                        var conn = new Ext.data.Connection({
                                                                                            listeners:{
                                                                                                // wait message.....
                                                                                                beforerequest:function(){
                                                                                                    Ext.MessageBox.show({
                                                                                                        title: <?php echo json_encode(__('Please wait...')) ?>,
                                                                                                        msg: <?php echo json_encode(__('Remove backup plan...')) ?>,
                                                                                                        width:300,
                                                                                                        wait:true
                                                                                                     //   modal: true
                                                                                                    });
                                                                                                },// on request complete hide message
                                                                                                requestcomplete:function(){Ext.MessageBox.hide();}
                                                                                                ,requestexception:function(c,r,o){Ext.Ajax.fireEvent('requestexception',c,r,o);}
                                                                                            }
                                                                                        });// end conn
                                                                                        conn.request({
                                                                                            url: <?php echo json_encode(url_for('primavera/json'))?>,
                                                                                            params: {id:this.service_id,method:'primavera_removebackupplan', params: Ext.encode({ id: selected.data['id'] }) },
                                                                                            scope:this,
                                                                                            success: function(resp,opt) {

                                                                                                var response = Ext.util.JSON.decode(resp.responseText);
                                                                                                Ext.ux.Logger.info(response['agent'], response['response']);
                                                                                                Ext.getCmp('grid-backup-plans').store.reload();
                                                                                            }
                                                                                            ,failure: function(resp,opt) {
                                                                                                var response = Ext.util.JSON.decode(resp.responseText);
                                                                                                if(response && resp.status!=401)
                                                                                                    Ext.Msg.show({
                                                                                                        title: String.format(<?php echo json_encode(__('Error {0}')) ?>,response['agent']),
                                                                                                        buttons: Ext.MessageBox.OK,
                                                                                                        msg: String.format(<?php echo json_encode(__('Unable to remove backup plan!')) ?>)+'<br>'+response['info'],
                                                                                                        icon: Ext.MessageBox.ERROR});
                                                                                            }
                                                                                        });// END Ajax request
                                                                                    }//END button==yes
                                                                                }
                                                                    });
                                                            } else {
                                                                Ext.Msg.show({
                                                                    title: <?php echo json_encode(__('Error no backup plan selected')) ?>,
                                                                    buttons: Ext.MessageBox.OK,
                                                                    msg: <?php echo json_encode(__('No backup plan for delete!')) ?>,
                                                                    icon: Ext.MessageBox.ERROR});
                                                            } // END if selected
                                                        }
                                    }
                                    ,{
                                        text: __('Refresh'),
                                        xtype: 'button',
                                        tooltip: 'refresh',
                                        iconCls: 'x-tbar-loading',
                                        ref:'refreshBtn',
                                        //scope:this,
                                        handler: function(button,e){
                                            Ext.getCmp('grid-backup-plans').store.reload();
                                        }
                                    }

                            ]
                    })
                    ,{
                        xtype: 'form',
                        id: 'form-new-backup-plan',
                        service_id: service_id,
                        title: <?php echo json_encode(__('New backup plan')) ?>,
                        border: false,
                        labelWidth: 140,
                        monitorValid: true,
                        bodyStyle: 'padding: 10px',
                        defaults: { border:false },                
                        items: [
                                {xtype:'hidden',name:'operation'},
                                {xtype:'hidden',name:'id'},
                                { fieldLabel: __('Name'),
                                  name: 'name', allowBlank: false,
                                  xtype:'textfield' },
                                new Ext.form.ComboBox({
                                    id: 'combo-periodo',
                                    typeAhead: true,
                                    triggerAction: 'all',
                                    lazyRender:true,
                                    mode: 'local',
                                    forceSelection: true,
                                    editable:       false,
                                    valueField: 'id',
                                    displayField: 'name',
                                    name: 'periodo',
                                    fieldLabel: __('Periodo'),
                                    value: 'diario',
                                    store: new Ext.data.ArrayStore({
                                        fields: [
                                            'id',
                                            'name'
                                        ],
                                        data: [ ['diario',__('Daily')],['semanal',__('Weekly')],['mensal',__('Monthly')] ]
                                    })
                                }),
                                {
                                    xtype: 'multiselect',
                                    fieldLabel: __('Databases'),
                                    name: 'companies',
                                    id: 'combo-companies',
                                    allowBlank: false,
                                    valueField: 'company',
                                    displayField: 'db',
                                    loadMask: true,
                                    store: new Ext.data.ArrayStore({
                                        fields: [
                                            'db',
                                            'company'
                                        ],
                                        sortInfo:{field:'db',direction:'ASC'}
                                    })
                                },
                                { fieldLabel: __('Verify'),
                                  name: 'verify',
                                  xtype:'checkbox' },
                                { fieldLabel: __('Overwrite'),
                                  name: 'overwrite',
                                  xtype:'checkbox' },
                                { fieldLabel: __('Incremental'),
                                  name: 'incremental',
                                  xtype:'checkbox' }
                                ]
                        ,buttons: [{
                               text: __('Save'),
                               formBind:true,
                               //handler: this.onSave,
                               handler:function(){Ext.getCmp('form-new-backup-plan').onSave()},
                               scope: this
                           },
                           {
                               text:__('Cancel'),
                               scope:this,
                               handler:function(){(this.ownerCt).close()}
                           }]
                        ,onSave: function(){

                            var form_values = this.getForm().getValues();
                            var method = 'primavera_insertbackupplan';
                            var send_data = {};
                            //send_data['id'] = form_values['id'];
                            send_data['name'] = form_values['name'];
                            send_data['periodo'] = Ext.getCmp('combo-periodo').getValue();

                            var companies_arr = new Array();
                            var companies = Ext.getCmp('combo-companies').getValue();
                            var csplit_arr = companies.split(",")
                            for(var i=0; i<csplit_arr.length; i++){
                                var cf = csplit_arr[i];
                                var cf_arr = cf.split("|");
                                companies_arr.push({'key':cf_arr[0],'name':cf_arr[1]});
                            }
                            
                            send_data['companies'] = companies_arr;
                            send_data['verify'] = form_values['verify'];
                            send_data['overwrite'] = form_values['overwrite'];
                            send_data['incremental'] = form_values['incremental'];

                            // process
                            var conn = new Ext.data.Connection({
                                listeners:{
                                    // wait message.....
                                    beforerequest:function(){
                                        Ext.MessageBox.show({
                                            title: <?php echo json_encode(__('Please wait...')) ?>,
                                            msg: <?php echo json_encode(__('Saving backup plan...')) ?>,
                                            width:300,
                                            wait: true,
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
                                url: <?php echo json_encode(url_for('primavera/json')) ?>,
                                params: {
                                    id: this.service_id,
                                    method: method,
                                    params: Ext.encode(send_data)
                                },            
                                scope:this,
                                success: function(resp,opt) {

                                    var response = Ext.util.JSON.decode(resp.responseText);                

                                    Ext.ux.Logger.info(response['agent'],response['response']);

                                    // set grid tab active
                                    this.ownerCt.setActiveTab(0);
                                    // reload grid
                                    Ext.getCmp('grid-backup-plans').store.reload();
                                    // clear form
                                    Ext.getCmp('form-new-backup-plan').form.reset();
                                },
                                failure: function(resp,opt) {
                                    
                                    var response = Ext.util.JSON.decode(resp.responseText);
                                    Ext.ux.Logger.error(response['agent'],response['error']);

                                    Ext.Msg.show({
                                        title: String.format(<?php echo json_encode(__('Error {0}')) ?>,response['agent']),
                                        buttons: Ext.MessageBox.OK,
                                        msg: String.format(<?php echo json_encode(__('Unable to save backup plan!')) ?>)+'<br>'+response['info'],
                                        icon: Ext.MessageBox.ERROR});

                                }
                            });// END Ajax request
                        }
                    }
                    ,{
                        id: 'form-new-backup-now',
                        xtype: 'form',
                        service_id: service_id,
                        title: <?php echo json_encode(__('Backup now')) ?>,
                        border: false,
                        labelWidth: 140,
                        bodyStyle: 'padding: 10px',
                        defaults: { border:false },
                        items: [
                                new Ext.form.ComboBox({
                                    id: 'combo-empresa',
                                    typeAhead: true,
                                    triggerAction: 'all',
                                    lazyRender:true,
                                    mode: 'local',
                                    forceSelection: true,
                                    editable:       false,
                                    valueField: 'db',
                                    displayField: 'name',
                                    name: 'empresa',
                                    fieldLabel: 'Empresa',
                                    store: new Ext.data.ArrayStore({
                                        fields: [
                                            'db',
                                            'name'
                                        ],
                                        sortInfo:{field:'name',direction:'ASC'}
                                    })
                                })
                                ,{ fieldLabel: __('Full backup'),
                                  name: 'fullbackup',
                                  xtype:'checkbox',listeners: { 
                                                            'check':{scope:this,fn:function(cbox,ck){
                                                                                                    if(ck){
                                                                                                        Ext.getCmp('form-new-backup-now').form.findField('empresa').disable();
                                                                                                    } else {
                                                                                                        Ext.getCmp('form-new-backup-now').form.findField('empresa').enable();
                                                                                                    }
                                                                                            }}}
                                }
                        ]
                        ,buttons: [{
                               text: __('Save'),
                               formBind:true,
                               //handler: this.onSave,
                               handler:function(){Ext.getCmp('form-new-backup-now').onSave()},
                               scope: this
                           },
                           {
                               text:__('Cancel'),
                               scope:this,
                               handler:function(){(this.ownerCt).close()}
                           }]
                        ,onSave: function(){
                            var form_values = this.getForm().getValues();
                            var method = 'primavera_backup';
                            var send_data = {};
                            if( form_values['fullbackup'] ){
                                method = 'primavera_fullbackup';
                            } else {
                                method = 'primavera_backup';
                                send_data['database'] = Ext.getCmp('combo-empresa').getValue();
                                send_data['empresa'] = form_values['empresa'];
                            }

                            // process
                            var conn = new Ext.data.Connection({
                                listeners:{
                                    // wait message.....
                                    beforerequest:function(){
                                        Ext.MessageBox.show({
                                            title: <?php echo json_encode(__('Please wait...')) ?>,
                                            msg: <?php echo json_encode(__('Doing backup...')) ?>,
                                            width:300,
                                            wait: true,
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
                                url: <?php echo json_encode(url_for('primavera/json')) ?>,
                                params: {
                                    id: this.service_id,
                                    method: method,
                                    params: Ext.encode(send_data)                
                                },            
                                scope:this,
                                success: function(resp,opt) {

                                    var response = Ext.util.JSON.decode(resp.responseText);                

                                    Ext.ux.Logger.info(response['agent'],response['response']);
                                    this.ownerCt.fireEvent('onSave');                

                                },
                                failure: function(resp,opt) {
                                    
                                    var response = Ext.util.JSON.decode(resp.responseText);
                                    Ext.ux.Logger.error(response['agent'],response['error']);

                                    Ext.Msg.show({
                                        title: String.format(<?php echo json_encode(__('Error {0}')) ?>,response['agent']),
                                        buttons: Ext.MessageBox.OK,
                                        msg: String.format(<?php echo json_encode(__('Unable to backup!')) ?>)+'<br>'+response['info'],
                                        icon: Ext.MessageBox.ERROR});

                                }
                            });// END Ajax request
                        }
                    }
        ];

        this.on({
                onSave: {fn:function(){
                            this.ownerCt.fireEvent('onSave');
                        }}
            });
        Primavera.Backup.TabPanel.superclass.initComponent.call(this);
    }
    ,loadRecord: function(){
        var conn = new Ext.data.Connection({
                                    listeners:{
                                        // wait message.....
                                        beforerequest:function(){
                                            Ext.MessageBox.show({
                                                title: <?php echo json_encode(__('Please wait...')) ?>,
                                                msg: <?php echo json_encode(__('Loading backup info...')) ?>,
                                                width:300,
                                                wait:true,
                                               modal: true
                                            });
                                        },// on request complete hide message
                                        requestcomplete:function(){Ext.MessageBox.hide();}
                                        ,requestexception:function(c,r,o){Ext.Ajax.fireEvent('requestexception',c,r,o);}
                                    }
                        });
        conn.request({
            url: <?php echo json_encode(url_for('primavera/json'))?>,
            params:{id:this.service_id,method:'primavera_backupinfo'},
            scope:this,
            success: function(resp,opt) {

                var response = Ext.util.JSON.decode(resp.responseText);
                if( response['data']['empresas'].length > 0 ){
                    var empresas = response['data']['empresas'];
                    var data_e = new Array();
                    var data_c = new Array();
                    for(var i=0; i<empresas.length; i++){
                        var e_e = new Array(empresas[i]['db'],empresas[i]['name']);
                        data_e.push(e_e);

                        var e_name = 'E' + empresas[i]['name'] + '|' + empresas[i]['db'];
                        var e_c = new Array(empresas[i]['db'],e_name);
                        data_c.push(e_c);
                    }
                    
                    Ext.getCmp('combo-empresa').store.loadData(data_e);
                    Ext.getCmp('combo-empresa').setValue(data_e[0][0]);

                    // add this by default
                    data_c.push(new Array('BIADM','OBIADM|BIADM'));
                    data_c.push(new Array('PRIEMPRE','OPRIEMPRE|PRIEMPRE'));

                    Ext.getCmp('combo-companies').store.loadData(data_c,false);
                    Ext.getCmp('combo-companies').store.sort('db','ASC');

                    Ext.getCmp('grid-backup-plans').store.reload();
                }
            }
            ,failure: function(resp,opt) {
                var response = Ext.util.JSON.decode(resp.responseText);
                if(response && resp.status!=401)
                    Ext.Msg.show({
                        title: String.format(<?php echo json_encode(__('Error {0}')) ?>,response['agent']),
                        buttons: Ext.MessageBox.OK,
                        msg: String.format(<?php echo json_encode(__('Unable to load backup info!')) ?>)+'<br>'+response['info'],
                        icon: Ext.MessageBox.ERROR});
            }
        });// END Ajax request
    }
});

Primavera.Backup.Window = function(config) {

    Ext.apply(this,config);

    Primavera.Backup.Window.superclass.constructor.call(this, {
        width:800
        ,height:450
        ,border:false
        ,modal:true
        ,layout:'fit'
        ,items:[ new Primavera.Backup.TabPanel({service_id:this.service_id})]
    });
};


Ext.extend(Primavera.Backup.Window, Ext.Window,{
    loadData:function(data){
        this.items.get(0).loadRecord(data);
    }
});

</script>

