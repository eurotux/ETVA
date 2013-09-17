<script>

Ext.ns('Primavera.EditUser');

Primavera.EditUser.Form = new Ext.extend( Ext.form.FormPanel, {

    monitorValid:true,
    border: false,
    labelWidth: 140,
    defaults: { border:false },
    initComponent:function(){
        var service_id = this.service_id;
        this.items = [
            { xtype: 'tabpanel',
                id: 'primavera-edituser-tabpanel',
                activeItem:0,
                enableTabScroll: true,
                anchor: '100% 100%',
                defaults:{ layout:'form', labelWidth:140 },
                items: [
                    {
                    title: __('Identification'),
                    id:'primavera-edituser-tab-identification',
                    bodyStyle:'padding: 10px',
                    autoScroll:true,
                    items: [
                        {xtype:'hidden',name:'id'},
                        {
                            border: false,
                            anchor: '100% 100%',
                            layout: {
                                type: 'hbox',
                                align: 'stretch'  // Child items are stretched to full width
                            }
                            ,defaults: { flex: 1, layout:'form', autoScroll:true, bodyStyle:'padding:10px;', border:false}
                            ,items:[
                                {
                                    items: [
                                            {xtype:'hidden',name:'operation'},
                                            { fieldLabel:__('Name'),
                                              name: 'Nome',
                                              allowBlank: false,
                                              xtype:'textfield' },
                                            { fieldLabel:__('Email'),
                                              name: 'Email',
                                              allowBlank: false,
                                              vtype:'email',
                                              xtype:'textfield' },
                                            { fieldLabel:__('Password'),
                                              name: 'password',
                                              ref: 'password',
                                              inputType: 'password',
                                              xtype:'textfield' },
                                            { fieldLabel:__('Verify Password'),
                                              name: 'verpassword',
                                              inputType: 'password',
                                                validator:function(v){
                                                    if(!v) return true;
                                                    
                                                    if(v==this.ownerCt.password.getValue()) return true;
                                                    else return <?php echo json_encode(__('Passwords doesn\'t match')) ?>;
                                                },
                                              xtype:'textfield' },
                                            {
                                                xtype: 'combo',
                                                typeAhead: true,
                                                triggerAction: 'all',
                                                name: 'perfil',
                                                loadMask: true,
                                                store: new Ext.data.JsonStore({
                                                    proxy:new Ext.data.HttpProxy({url:<?php echo json_encode(url_for('primavera/json'))?>}),
                                                    baseParams:{ id:service_id,method:'primavera_listperfis'},
                                                    root: 'data',
                                                    fields: [            
                                                       {name: 'cod'},
                                                       {name: 'Codigo'}
                                                    ],
                                                    //autoLoad: false
                                                }),
                                                fieldLabel: __('Profile'),
                                                editable: false,
                                                valueField: 'cod',
                                                displayField: 'Codigo',
                                                allowBlank: true
                                            },
                                            {
                                                xtype: 'combo',
                                                triggerAction: 'all',
                                                mode: 'local',
                                                editable: false,
                                                name: 'idioma',
                                                hiddenName:'idioma',
                                                store: new Ext.data.ArrayStore({
                                                    fields: ['value','name'],
                                                    data: [['', __('None')], 
                                                            ['pt-PT', __('Portuguese')],
                                                            ['es-ES', __('Spanish')],
                                                            ['fr-FR', __('French')],
                                                            ['en-UK', __('English')],
                                                            ['en-US', __('English (EUA)')]]
                                                }),
                                                fieldLabel: __('Language'),
                                                valueField: 'value',
                                                displayField: 'name',
                                                allowBlank: true
                                            },
                                            { layout: 'column',
                                                fieldLabel: __('Login Windows'),
                                                border: false,
                                                items:[{
                                                xtype: 'combo',
                                                typeAhead: true,
                                                triggerAction: 'all',
                                                name: 'loginWindows',
                                                loadMask: true,
                                                store: new Ext.data.JsonStore({
                                                    proxy:new Ext.data.HttpProxy({url:<?php echo json_encode(url_for('primavera/json'))?>}),
                                                    totalProperty: 'total',
                                                    baseParams:{ id:service_id,method:'windows_listusers', params: Ext.encode({'groups':'Users,Administrators'})},
                                                    root: 'data',
                                                    fields: [            
                                                       {name: 'username'},
                                                       {name: 'group'}
                                                    ],
                                                    //autoLoad: false
                                                }),
                                                fieldLabel: __('Login Windows'),
                                                editable: false,
                                                valueField: 'username',
                                                displayField: 'username',
                                                allowBlank: true
                                                ,bbar:  new Ext.Toolbar({items: [{
                                                        xtype: 'button',
                                                        tooltip: __('Refresh'),
                                                        iconCls: 'x-tbar-loading',
                                                        scope: this,
                                                        handler: function(){
                                                            this.form.findField('loginWindows').getStore().reload();
                                                        }
                                                    }]
                                                })
                                            },
                                            {
                                                xtype: 'button',
                                                iconCls:'icon-user-add',
                                                text: '',
                                                url: <?php echo(json_encode(url_for('primavera/Primavera_WindowsNewUser')))?>,
                                                call:'Primavera.Windows.NewUser',
                                                scope:this,
                                                callback: function(item) {
                                                    var window = new Primavera.Windows.NewUser.Window({
                                                                        title: String.format(<?php echo json_encode(__('New Windows user')) ?>),
                                                                        service_id:service_id });
                                                    window.on({
                                                        onSave:{fn:function(){
                                                                this.close();
                                                                var parentCmp = Ext.getCmp((item.scope).ownerCt.id);
                                                                parentCmp.items.get(0).form.findField('loginWindows').getStore().reload();
                                                        }}
                                                    });
                                                    window.show();
                                                },
                                                handler: function(btn){View.loadComponent(btn);}
                                            }]},
                                            { fieldLabel:__('Super Admin'),
                                              name: 'SuperAdministrador',
                                              xtype:'checkbox', listeners: { 
                                                                        'check':{scope:this,fn:function(cbox,ck){
                                                                                                                if(ck){
                                                                                                                    this.form.findField('Administrador').setValue(true);
                                                                                                                    this.form.findField('Administrador').disable();
                                                                                                                } else {
                                                                                                                    this.form.findField('Administrador').enable();
                                                                                                                }
                                                                                                        }}}},
                                            { fieldLabel:__('Admin'),
                                              name: 'Administrador',
                                              xtype:'checkbox' },
                                            { fieldLabel:__('Tecnico'),
                                              name: 'Tecnico',
                                              xtype:'checkbox' },
                                    ]
                                }
                            ]
                        }
                    ]},{
                    title: __('Permissions'),
                    id:'primavera-edituser-tab-permissions',
                    bodyStyle:'padding: 10px',
                    autoScroll:true,
                    items : [
                        {
                            border: false,
                            anchor: '100% 100%',
                            layout: {
                                type: 'vbox',
                                align: 'stretch'  // Child items are stretched to full width
                            }
                            ,defaults: { flex: 1, layout:'fit', autoScroll:true, border:false}
                            ,items:[
                                {
                                    items: [
                                        {
                                            border: false,
                                            anchor: '100% 100%',
                                            layout: {
                                                type: 'hbox',
                                                align: 'stretch'  // Child items are stretched to full width
                                            }
                                            ,defaults: { flex: 1, layout:'fit', autoScroll:true, border:false}
                                            ,items:[
                                                {
                                                    items: [
                                                            new Ext.grid.GridPanel({
                                                                id: 'primavera-edituser-permissions-profiles',
                                                                loadMask: true,
                                                                draggable: true,
                                                                ddGroup: 'primavera-selectedProfiles-ddGroup',
                                                                //enableDragDrop: true,
                                                                viewConfig       : {forceFit:true},
                                                                store            : new Ext.data.JsonStore({
                                                                    proxy:new Ext.data.HttpProxy({url:<?php echo json_encode(url_for('primavera/json'))?>}),
                                                                    baseParams:{ id:service_id,method:'primavera_listperfis'},
                                                                    root: 'data',
                                                                    fields: [            
                                                                       {name: 'cod'},
                                                                       {name: 'Nome'}
                                                                    ],
                                                                    //autoLoad: true
                                                                    listeners: {
                                                                        load: function(store,records,options){
                                                                            Ext.getCmp('primavera-edituser-permissions-profiles').body.unmask();
                                                                        }
                                                                    }
                                                                }),
                                                                columns          : [
                                                                    {header: "Code", width: 40, sortable: true, dataIndex: 'cod'},
                                                                    {header: "Name", width: 150, sortable: true, dataIndex: 'Nome'}
                                                                ],
                                                                loadMask         : true,
                                                                border           : false,
                                                                stripeRows       : true,
                                                                title            : <?php echo json_encode(__('Profiles available')) ?>
                                                                ,listeners : {
                                                                   render : function(grid){      
                                                                       grid.body.mask('Loading...');
                                                                       var store = grid.getStore();
                                                                       store.load.defer(100, store);
                                                                   },
                                                                   rowcontextmenu: function(grid,rowIndex,e){
                                                                        var selModel = grid.getSelectionModel();
                                                                        var selRows = selModel.getSelections();
                                                                        if( !selRows.length ){ // force selection
                                                                            selModel.selectRow(rowIndex);
                                                                            selRows = selModel.getSelections();
                                                                        }
                                                                        if(!grid.menu){ // create context menu on first right click
                                                                            grid.menu = new Ext.ux.TooltipMenu({
                                                                                items: [{
                                                                                            iconCls:'go-action',
                                                                                            text: <?php echo json_encode(__('Add permission')) ?>,
                                                                                            menu: [{
                                                                                                xtype:'combo',
                                                                                                typeAhead: true,
                                                                                                triggerAction: 'all',
                                                                                                name: 'selempresa',
                                                                                                loadMask: true,
                                                                                                store: new Ext.data.JsonStore({
                                                                                                    proxy:new Ext.data.HttpProxy({url:<?php echo json_encode(url_for('primavera/json'))?>}),
                                                                                                    baseParams:{ id:service_id,method:'primavera_listempresas_cbx'},
                                                                                                    root: 'data',
                                                                                                    fields: [            
                                                                                                       {name: 'cod'},
                                                                                                       {name: 'name'}
                                                                                                    ],
                                                                                                    //autoLoad: false
                                                                                                }),
                                                                                                fieldLabel: __('Empresa'),
                                                                                                editable: false,
                                                                                                valueField: 'name',
                                                                                                displayField: 'name',
                                                                                                allowBlank: false,
                                                                                                listeners : {
                                                                                                    'select': function(combo,record,index){

                                                                                                        var srcGrid =  Ext.getCmp('primavera-edituser-permissions-profiles');

                                                                                                        var selModel = srcGrid.getSelectionModel();
                                                                                                        var selRows = selModel.getSelections();
                                                                                                        if( selRows.length > 0 ){ // force selection
                                                                                                            var destGrid =  Ext.getCmp('primavera-edituser-permissions-permissions');
                                                                                                            var destStore = destGrid.getStore();
                                                                                                            var empresa = record.data.name;
                                                                                                            var empresa_cod = record.data.cod;
                                                                                                            
                                                                                                            Ext.each(selRows,function(r){
                                                                                                                var perfil = r.data.cod;
                                                                                                                var newrecord = new Ext.data.Record({ 'Name': empresa, 'Empresa': empresa_cod, 'Perfil': perfil });
                                                                                                                var alreadyThere = false;
                                                                                                                var res = destStore.queryBy(function(erecord){
                                                                                                                                                if( (erecord.data.Empresa == empresa) && (erecord.data.Perfil == perfil )){
                                                                                                                                                    alreadyThere =true;
                                                                                                                                                    return true;
                                                                                                                                                }
                                                                                                                                                return false;
                                                                                                                                            })
                                                                                                                if( !alreadyThere ) destStore.add(newrecord);
                                                                                                            })
                                                                                                            destStore.sort('Name');
                                                                                                        }
                                                                                                    }
                                                                                                }
                                                                                            }
                                                                                        ]
                                                                                }]
                                                                            });
                                                                        }
                                                                        grid.menu.showAt(e.getXY());
                                                                   },
                                                                   delay: 200
                                                                }
                                                                })
                                                        ]
                                                    },{
                                                        bodyStyle: 'padding-left: 10px',
                                                        items: [
                                                                new Ext.grid.GridPanel({
                                                                    id: 'primavera-edituser-permissions-permissions',
                                                                    loadMask: true,
                                                                    viewConfig       : {forceFit:true},
                                                                    store : new Ext.data.GroupingStore({
                                                                        url:<?php echo json_encode(url_for('primavera/json'))?>,
                                                                        method:'POST',
                                                                        baseParams:{ id:service_id,method:'primavera_list_user_permissoes_join'},
                                                                        reader: new Ext.data.JsonReader({
                                                                            totalProperty: 'total',
                                                                            root: 'data',
                                                                            fields: [
                                                                               {name: 'Name'},
                                                                               {name: 'Empresa'},
                                                                               {name: 'Perfil'}
                                                                            ]
                                                                        }),
                                                                        sortInfo:{field: 'Name', direction: "DESC"},
                                                                        remoteSort: false,
                                                                        groupField:'Name'
                                                                        ,listeners: {
                                                                            load: function(store,records,options){
                                                                                Ext.getCmp('primavera-edituser-permissions-permissions').body.unmask();
                                                                            }
                                                                        }
                                                                    }),
                                                                    columns          : [
                                                                        {header: __("Empresa"), width: 150, sortable: true, dataIndex: 'Name', hidden: true},
                                                                        {header: __("Perfil"), width: 150, sortable: true, dataIndex: 'Perfil'}
                                                                    ],
                                                                    view: new Ext.grid.GroupingView({
                                                                        autoFill:true,
                                                                        emptyText: __('Empty!'),  //  emptyText Message
                                                                        forceFit:true,
                                                                        groupTextTpl: '{text} ({[values.rs.length]} {[values.rs.length > 1 ? __("Items") : "Item"]})'
                                                                    }),
                                                                    loadMask         : true,
                                                                    border           : false,
                                                                    stripeRows       : true,
                                                                    title            : <?php echo json_encode(__('Companies/Profiles that user has access')) ?>
                                                                    ,listeners : {
                                                                       render : function(grid){      
                                                                           grid.body.mask('Loading...');
                                                                           var store = grid.getStore();
                                                                           store.load.defer(100, store);

                                                                            // This will make sure we only drop to the view scroller element
                                                                            var selectedGridDropTargetEl = grid.getView().scroller.dom;
                                                                            var selectedGridDropTarget = new Ext.dd.DropTarget(selectedGridDropTargetEl, {
                                                                                    ddGroup    : 'primavera-selectedProfiles-ddGroup',
                                                                                    notifyDrop : function(ddSource, e, data){
                                                                                            var sm = grid.getSelectionModel();
                                                                                            console.log(sm);
                                                                                            var records =  ddSource.dragData.selections;
                                                                                            Ext.each(records,function(f){
                                                                                                console.log(f);
                                                                                                //grid.store.add(f);
                                                                                            });
                                                                                            return true;
                                                                                    }
                                                                            });
                                                                       },
                                                                       rowcontextmenu: function(grid,rowIndex,e){
                                                                            var selModel = grid.getSelectionModel();
                                                                            var selRows = selModel.getSelections();
                                                                            if( !selRows.length ){ // force selection
                                                                                selModel.selectRow(rowIndex);
                                                                                selRows = selModel.getSelections();
                                                                            }
                                                                            if(!grid.menu){ // create context menu on first right click
                                                                                grid.menu = new Ext.ux.TooltipMenu({
                                                                                    items: [{
                                                                                            iconCls:'go-action',
                                                                                            text: <?php echo json_encode(__('Remove permission')) ?>,
                                                                                            handler: function(b){
                                                                                                // do it
                                                                                                var pGrid =  Ext.getCmp('primavera-edituser-permissions-permissions');
                                                                                                var selModel = pGrid.getSelectionModel();
                                                                                                var selRows = selModel.getSelections();
                                                                                                if( selRows.length > 0 ){ // force selection
                                                                                                    pGrid.getStore().remove(selRows);
                                                                                                }
                                                                                            }
                                                                                    }]
                                                                                });
                                                                            }
                                                                            grid.menu.showAt(e.getXY());
                                                                       },
                                                                       delay: 200
                                                                    }
                                                                })
                                                        ]
                                                    }
                                                ]
                                            }
                                        ]
                                    },{
                                        items: [
                                                new Ext.grid.GridPanel({
                                                    id: 'primavera-edituser-permissions-applications',
                                                    loadMask: true,
                                                    viewConfig       : {forceFit:true},
                                                    store            : new Ext.data.JsonStore({
                                                        proxy:new Ext.data.HttpProxy({url:<?php echo json_encode(url_for('primavera/json'))?>}),
                                                        baseParams:{ id:service_id,method:'primavera_list_user_aplicacoes_join'},
                                                        batch: false,
                                                        root: 'data',
                                                        fields: [            
                                                           {name: 'checked'},
                                                           {name: 'apl'},
                                                           {name: 'Nome'}
                                                        ],
                                                        //autoLoad: true
                                                        listeners: {
                                                            load: function(store,records,options){
                                                                var recs = [];
                                                                Ext.each(records, function(item, index){
                                                                    if (item.data.checked) {
                                                                        recs.push(index);
                                                                    }
                                                                });
                                                                Ext.getCmp('primavera-edituser-permissions-applications').getSelectionModel().selectRows(recs);
                                                                Ext.getCmp('primavera-edituser-permissions-applications').body.unmask();
                                                            }
                                                        }
                                                    }),
                                                    columns          : [
                                                        new Ext.grid.CheckboxSelectionModel(),
                                                        {header: "Apl", width: 40, sortable: true, dataIndex: 'apl'},
                                                        {header: "Name", width: 150, sortable: true, dataIndex: 'Nome'}
                                                    ],
                                                    sm : new Ext.grid.CheckboxSelectionModel(),
                                                    loadMask         : true,
                                                    border           : false,
                                                    stripeRows       : true,
                                                    title            : <?php echo json_encode(__('Applications/Modules that user has access')) ?>
                                                    ,listeners : {
                                                        render : function(grid){      
                                                            grid.body.mask('Loading...');
                                                            var store = grid.getStore();
                                                            store.load.defer(100, store);
                                                        },
                                                        delay: 200
                                                    }
                                                })
                                        ]
                                    }
                                ]
                            }
                        ],
                        listeners: {
                            'activate': function(p){
                                /*p.items.get(0).items.get(0).items.get(0).items.get(0).items.get(0).getStore().load();
                                p.items.get(0).items.get(0).items.get(0).items.get(1).items.get(0).getStore().load();
                                p.items.get(0).items.get(1).items.get(0).getStore().load();*/
                            }
                        }}
                    ]
                }];

            this.buttons = [{
                               text: __('Save'),
                               formBind:true,
                               handler: this.onSave,
                               scope: this
                           },
                           {
                               text:__('Cancel'),
                           scope:this,
                           handler:function(){(this.ownerCt).close()}
                       }];
        Primavera.EditUser.Form.superclass.initComponent.call(this);
    }
    ,onSave: function(){
        var form_values = this.getForm().getValues();
        var cod = this.getForm().findField("cod").getValue();
        //alert("alert " + " cod:" + cod + " name:" + form_values['Nome'] );
        if( !cod ){
            Ext.Msg.show({
                title: String.format(<?php echo json_encode(__('Error invalid username')) ?>),
                buttons: Ext.MessageBox.OK,
                msg: String.format(<?php echo json_encode(__('Invalid username!')) ?>),
                icon: Ext.MessageBox.ERROR});
        } else if( form_values['password'] != form_values['verpassword'] ){
            Ext.Msg.show({
                title: String.format(<?php echo json_encode(__('Error invalid password')) ?>),
                buttons: Ext.MessageBox.OK,
                msg: String.format(<?php echo json_encode(__('Passwords mismatch!')) ?>),
                icon: Ext.MessageBox.ERROR});
        } else {
            var send_data = {};
            send_data['u_cod'] = cod;
            send_data['u_name'] = form_values['Nome'];
            send_data['u_email'] = form_values['Email'];
            send_data['u_password'] = form_values['password'];
            send_data['u_suadmin'] = form_values['SuperAdministrador'];
            send_data['u_admin'] = this.getForm().findField("Administrador").getValue();
            send_data['u_tecnico'] = form_values['Tecnico'];
            send_data['loginWindows'] = form_values['loginWindows'];
            send_data['perfil'] = form_values['perfil'];
            send_data['idioma'] = form_values['idioma'];
 
            // process update user
            var conn = new Ext.data.Connection({
                listeners:{
                    // wait message.....
                    beforerequest:function(){
                        Ext.MessageBox.show({
                            title: <?php echo json_encode(__('Please wait...')) ?>,
                            msg: <?php echo json_encode(__('Saving user info...')) ?>,
                            width:300,
                            wait: true,
                            modal: true
                        });
                    },// on request complete hide message
                    requestcomplete:function(){Ext.MessageBox.hide();}
                    ,requestexception:function(c,r,o){
                                Ext.MessageBox.hide();
                                Ext.Ajax.fireEvent('requestexception',c,r,o);}
                }
            });// end conn

            var method = 'primavera_updateuser';
            var operation = this.getForm().findField("operation").getValue();
            if( operation == 'new' ){
                method = 'primavera_insertuser';
            }
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
                    this.onSavePermissions(send_data);

                    (this.ownerCt).fireEvent('onSave');
                },
                failure: function(resp,opt) {
                    
                    var response = Ext.util.JSON.decode(resp.responseText);
                    Ext.ux.Logger.error(response['agent'],response['error']);

                    Ext.Msg.show({
                        title: String.format(<?php echo json_encode(__('Error {0}')) ?>,response['agent']),
                        buttons: Ext.MessageBox.OK,
                        msg: String.format(<?php echo json_encode(__('Error saving user!')) ?>)+'<br>'+response['info'],
                        icon: Ext.MessageBox.ERROR});

                }
            });// END Ajax request
        }
    }
    ,onSavePermissions : function(data){
        this.onSavePermissionsCompaniesProfiles(data);
        this.onSavePermissionsApplications(data);
    }
    ,onSavePermissionsCompaniesProfiles : function(data){
        if( Ext.getCmp('primavera-edituser-permissions-permissions').rendered ){
            var permissions_companiesprofiles_store = Ext.getCmp('primavera-edituser-permissions-permissions').getStore();

            var permissions = [];
            permissions_companiesprofiles_store.each(function(item){
                    var data = item.data;
                    if( data.Perfil && data.Empresa ){
                        var a_permission = [data.Perfil,data.Empresa];
                        var s_permission = a_permission.join(',');
                        permissions.push(s_permission);
                    }
                });

            var s_permissions = permissions.join(';');

            var send_data = {};
            send_data['u_user'] = data['u_cod'];
            send_data['u_permissoes'] = s_permissions;

            // process update permissions
            var conn = new Ext.data.Connection({
                listeners:{
                    // wait message.....
                    beforerequest:function(){
                        Ext.MessageBox.show({
                            title: <?php echo json_encode(__('Please wait...')) ?>,
                            msg: <?php echo json_encode(__('Saving user permissions...')) ?>,
                            width:300,
                            wait: true,
                            modal: true
                        });
                    },// on request complete hide message
                    requestcomplete:function(){Ext.MessageBox.hide();}
                    ,requestexception:function(c,r,o){
                                Ext.MessageBox.hide();
                                Ext.Ajax.fireEvent('requestexception',c,r,o);}
                }
            });// end conn

            var method = 'primavera_updateuser_permissoes';
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
                    //this.ownerCt.fireEvent('onSave');
                },
                failure: function(resp,opt) {
                    
                    var response = Ext.util.JSON.decode(resp.responseText);
                    Ext.ux.Logger.error(response['agent'],response['error']);

                    Ext.Msg.show({
                        title: String.format(<?php echo json_encode(__('Error {0}')) ?>,response['agent']),
                        buttons: Ext.MessageBox.OK,
                        msg: String.format(<?php echo json_encode(__('Error saving user permissions!')) ?>)+'<br>'+response['info'],
                        icon: Ext.MessageBox.ERROR});

                }
            });// END Ajax request
        }
    }
    ,onSavePermissionsApplications : function(data){
        if( Ext.getCmp('primavera-edituser-permissions-applications').rendered ){
            var permissions_applications_sm = Ext.getCmp('primavera-edituser-permissions-applications').getSelectionModel();
            var permissions_applications_selected = permissions_applications_sm.getSelections();

            var applications = [];
            Ext.each(permissions_applications_selected, function(item, index){
                    var data = item.data;
                    if( data['apl'] ){
                        applications.push(data['apl']);
                    }
                });

            var s_applications = applications.join(',');

            var send_data = {};
            send_data['u_user'] = data['u_cod'];
            send_data['u_aplicacoes'] = s_applications;

            // process update permissions
            var conn = new Ext.data.Connection({
                listeners:{
                    // wait message.....
                    beforerequest:function(){
                        Ext.MessageBox.show({
                            title: <?php echo json_encode(__('Please wait...')) ?>,
                            msg: <?php echo json_encode(__('Saving user applications...')) ?>,
                            width:300,
                            wait: true,
                            modal: true
                        });
                    },// on request complete hide message
                    requestcomplete:function(){Ext.MessageBox.hide();}
                    ,requestexception:function(c,r,o){
                                Ext.MessageBox.hide();
                                Ext.Ajax.fireEvent('requestexception',c,r,o);}
                }
            });// end conn

            var method = 'primavera_updateuser_aplicacoes';
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
                    //this.ownerCt.fireEvent('onSave');
                },
                failure: function(resp,opt) {
                    
                    var response = Ext.util.JSON.decode(resp.responseText);
                    Ext.ux.Logger.error(response['agent'],response['error']);

                    Ext.Msg.show({
                        title: String.format(<?php echo json_encode(__('Error {0}')) ?>,response['agent']),
                        buttons: Ext.MessageBox.OK,
                        msg: String.format(<?php echo json_encode(__('Error saving user applications!')) ?>)+'<br>'+response['info'],
                        icon: Ext.MessageBox.ERROR});

                }
            });// END Ajax request
        }
    }
    ,loadRecord: function(data){
        this.form.findField('id').setValue(this.service_id);
        if( data['new'] ){
            this.form.findField('operation').setValue("new");
            Ext.getCmp('primavera-edituser-tab-identification').items.get(1).items.get(0).insert(0, { fieldLabel:__('User'), name: 'cod', xtype:'textfield', allowBlank: false });
        } else {
            this.form.findField('operation').setValue("update");
            Ext.getCmp('primavera-edituser-tab-identification').items.get(1).items.get(0).insert(0, { fieldLabel:__('User'), name: 'cod', xtype:'displayfield' });

            if( data['user'] ){

                Ext.getCmp('primavera-edituser-permissions-applications').getStore().baseParams.params = Ext.encode({'u_user': data['user']['cod']});
                Ext.getCmp('primavera-edituser-permissions-permissions').getStore().baseParams.params = Ext.encode({'u_user': data['user']['cod']});

                this.form.findField("cod").setValue(data['user']['cod']);

                this.load({url:<?php echo json_encode(url_for('primavera/json'))?>
                                ,params:{id:this.service_id,method:'primavera_viewuser', params: Ext.encode({cod:data['user']['cod']}) }
                                ,waitMsg: <?php echo json_encode(__('Loading...')) ?>
                                ,success:function(f,a){
                                    //this.form.findField('perfil').getStore().load();
                                    this.form.findField('perfil').setValue(a.result['data']['PerfilSugerido']);
                                    this.form.findField('idioma').setValue(a.result['data']['Idioma']);
                                    this.form.findField('loginWindows').getStore().load();
                                    this.form.findField('loginWindows').setValue(a.result['data']['LoginWindows']);
                                }
                                ,scope: this
                            });
            }
        }
    }
});

Primavera.EditUser.Window = function(config) {

    Ext.apply(this,config);

    Primavera.EditUser.Window.superclass.constructor.call(this, {
        width:560
        ,height:620
        ,border:false
        ,modal:true
        ,layout:'fit'
        ,items:[ new Primavera.EditUser.Form({service_id:this.service_id})]
    });
};


Ext.extend(Primavera.EditUser.Window, Ext.Window,{
    loadData:function(data){
        this.items.get(0).loadRecord(data);
    }
});

</script>

