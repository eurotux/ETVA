<script>
Ext.ns("Primavera.User.CreateEdit.Fieldset");

Primavera.User.CreateEdit.onSave = function(alldata){
    var service_id = alldata['service_id'];

    var send_data = new Object();

    send_data['u_cod'] = alldata['cod'];
    send_data['u_name'] = alldata['Nome'];
    send_data['u_email'] = alldata['Email'];
    send_data['u_password'] = alldata['password'];
    send_data['u_suadmin'] = alldata['SuperAdministrador'];
    send_data['u_admin'] = alldata['Administrador'];
    send_data['u_tecnico'] = alldata['Tecnico'];
    send_data['loginWindows'] = alldata['loginWindows'];
    send_data['perfil'] = alldata['perfil'];
    send_data['idioma'] = alldata['idioma'];

    // process change ip
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

    var method = 'primavera_insertuser';
    if( alldata['action'] == 'edit' ){
        method = 'primavera_updateuser';
    }

    conn.request({
        url: <?php echo json_encode(url_for('primavera/json')) ?>,
        params: {
            id: service_id,
            method: method,
            params: Ext.encode(send_data)
        },            
        success: function(resp,opt) {

            var response = Ext.util.JSON.decode(resp.responseText);                

            Ext.ux.Logger.info(response['agent'],response['response']);

            var extra = { 'cod': alldata['cod'] };
            call_save_sfGuardUser_UpdateUserService({ id: alldata['user_id'], service_id: service_id, extra: Ext.encode(extra) });
        },
        failure: function(resp,opt) {
            
            var response = Ext.util.JSON.decode(resp.responseText);
            Ext.ux.Logger.error(response['agent'],response['info']);

            Ext.Msg.show({
                title: String.format(<?php echo json_encode(__('Error {0}')) ?>,response['agent']),
                buttons: Ext.MessageBox.OK,
                msg: String.format(<?php echo json_encode(__('Error saving user!')) ?>)+'<br>'+response['info'],
                icon: Ext.MessageBox.ERROR});

        }
    });// END Ajax request
};
Primavera.User.CreateEdit.Fieldset = function(config){
    
    var Obj = {
                border: false,
                bodyStyle: 'padding: 10px; background:transparent;',
                defaults: { border: false },
                items: [ { xtype: 'fieldset',
                            id:'user_list-createedit-tab-primavera',
                            checkboxToggle: true,
                            collapsed: true,
                            border: false,
                            title: <?php echo json_encode(__('Enable Primavera user information?')) ?>,
                            items: [{
                                        xtype: 'hidden',
                                        name: 'service_id',
                                        ref: 'service_id',
                                        value: parseInt('<?php echo $modulesConf['Primavera']['main']['service_id'] ?>')
                                    },
                                    { xtype:'radio',
                                        name: 'primavera-action',
                                        inputValue: 'add',
                                        fieldLabel:__('New user'),
                                        checked: true,
                                        listeners: {
                                            'check': function(e,check){
                                                e.ownerCt.cod_new.setDisabled(!check);
                                                e.ownerCt.cod_new.setVisible(check);
                                                if( check )
                                                    e.ownerCt.reset({ 'action': 'add' });
                                            }
                                        }
                                    },
                                    { xtype:'radio',
                                        name: 'primavera-action',
                                        inputValue: 'edit',
                                        checked: false,
                                        fieldLabel:__('Edit user'),
                                        listeners: {
                                            'check': function(e,check){
                                                e.ownerCt.cod_edit.setDisabled(!check);
                                                e.ownerCt.cod_edit.setVisible(check);
                                                if( check )
                                                    e.ownerCt.reset({ 'action': 'edit' });
                                            }
                                        }
                                    },
                                    {
                                        fieldLabel: __('User'),
                                        border:false,
                                        bodyStyle:'background:transparent;',
                                        items:[ 
                                    { fieldLabel:__('User'),
                                        name: 'cod',
                                        ref: '../cod_new',
                                        hidden: false,
                                        allowBlank: false,
                                        xtype:'textfield' },
                                    new Ext.form.ComboBox({ fieldLabel:__('User'),
                                        hidden: true,
                                        disabled: true,
                                        ref: '../cod_edit',
                                        selectOnFocus: false,
                                        editable: false,        
                                        //mode: 'local',
                                        triggerAction: 'all',
                                        allowBlank: false,
                                        store: new Ext.data.JsonStore({
                                            proxy:new Ext.data.HttpProxy({url:<?php echo json_encode(url_for('primavera/json'))?>}),
                                            totalProperty: 'total',
                                            baseParams:{ id:parseInt('<?php echo $modulesConf['Primavera']['main']['service_id'] ?>'),method:'primavera_listusers'},
                                            root: 'data',
                                            fields: [            
                                               {name: 'cod'},
                                               {name: 'Nome'},
                                               {name: 'Email'},
                                               {name: 'PerfilSugerido'},
                                               {name: 'LoginWindows'},
                                               {name: 'SuperAdministrador'},
                                               {name: 'Tecnico'},
                                               {name: 'Administrador'}
                                            ],
                                            autoLoad: false
                                        }),
                                        valueField: 'cod',
                                        displayField: 'cod',
                                        name: 'cod',
                                        xtype:'combo',
                                        width: 180,
                                        listeners: {
                                            'select': function(cbx, r, i){
                                                cbx.ownerCt.ownerCt.loadRecord(r.data);
                                            }
                                        } }),
                                        ]
                                    },
                                    { fieldLabel:__('Name'),
                                      name: 'Nome',
                                      allowBlank: false,
                                      xtype:'textfield' },
                                    { fieldLabel:__('Email'),
                                      name: 'Email',
                                      vtype:'email',
                                      allowBlank: false,
                                      xtype:'textfield' },
                                    { fieldLabel:__('Password'),
                                      name: 'password',
                                      ref: 'password',
                                      inputType: 'password',
                                      allowBlank: true,
                                      xtype:'textfield' },
                                    { fieldLabel:__('Verify Password'),
                                      name: 'verpassword',
                                      inputType: 'password',
                                      allowBlank: true,
                                        validator:function(v){
                                            if(!v) return true;
                                            
                                            if(v==this.ownerCt.password.getValue()) return true;
                                            else return <?php echo json_encode(__('Passwords do not match')) ?>;
                                        },
                                      xtype:'textfield' },
                                    {
                                        xtype: 'combo',
                                        typeAhead: true,
                                        triggerAction: 'all',
                                        name: 'perfil',
                                        store: new Ext.data.JsonStore({
                                            proxy:new Ext.data.HttpProxy({url:<?php echo json_encode(url_for('primavera/json'))?>}),
                                            totalProperty: 'total',
                                            baseParams:{ id:parseInt('<?php echo $modulesConf['Primavera']['main']['service_id'] ?>'),method:'primavera_listperfis' },
                                            root: 'data',
                                            fields: [            
                                               {name: 'cod'},
                                               {name: 'Codigo'}
                                            ],
                                            autoLoad: false
                                        }),
                                        fieldLabel: __('Profile'),
                                        editable: false,
                                        valueField: 'cod',
                                        displayField: 'Codigo',
                                        allowBlank: true,
                                        width: 180
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
                                        allowBlank: true,
                                        width: 180
                                    },
                                    { layout: 'column',
                                        bodyStyle:'background:transparent;',
                                        fieldLabel: __('Login Windows'),
                                        border: false,
                                        items:[{
                                        columnWidth: .75,
                                        width: 180,
                                        xtype: 'combo',
                                        typeAhead: true,
                                        triggerAction: 'all',
                                        name: 'loginWindows',
                                        store: new Ext.data.JsonStore({
                                            proxy:new Ext.data.HttpProxy({url:<?php echo json_encode(url_for('primavera/json'))?>}),
                                            totalProperty: 'total',
                                            baseParams:{ id:parseInt('<?php echo $modulesConf['Primavera']['main']['service_id'] ?>'),method:'windows_listusers', params: Ext.encode({'groups':'Users,Administrators'})},
                                            root: 'data',
                                            fields: [            
                                               {name: 'username'},
                                               {name: 'group'}
                                            ],
                                            autoLoad: false
                                        }),
                                        fieldLabel: __('Login Windows'),
                                        editable: false,
                                        valueField: 'username',
                                        displayField: 'username',
                                        allowBlank: true,
                                        bbar:  new Ext.Toolbar({items: [{
                                                xtype: 'button',
                                                tooltip: __('Refresh'),
                                                iconCls: 'x-tbar-loading',
                                                handler: function(b,e){
                                                    Ext.getCmp('user_list-createedit-tab-primavera').find('name','loginWindows')[0].getStore().reload();
                                                }
                                            }]
                                        })
                                    },
                                    {
                                        columnWidth: .25,
                                        border: false,
                                        bodyStyle:'background:transparent;',
                                        items: [{
                                        xtype: 'button',
                                        iconCls:'icon-user-add',
                                        text: '',
                                        url: <?php echo(json_encode(url_for('primavera/Primavera_WindowsNewUser')))?>,
                                        call:'Primavera.Windows.NewUser',
                                        scope:this,
                                        callback: function(item) {
                                            var window = new Primavera.Windows.NewUser.Window({
                                                                title: String.format(<?php echo json_encode(__('New Windows user')) ?>),
                                                                service_id:parseInt('<?php echo $modulesConf['Primavera']['main']['service_id'] ?>') });
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
                                        }]
                                    }]
                                    },
                                    { fieldLabel:__('Super Admin'),
                                      name: 'SuperAdministrador',
                                      ref: 'ckb_superadministrador',
                                      xtype:'checkbox', listeners: { 
                                                                'check':{scope:this,fn:function(cbox,ck){
                                                                                                        if(ck){
                                                                                                            cbox.ownerCt.ckb_administrador.setValue(true);
                                                                                                            cbox.ownerCt.ckb_administrador.disable();
                                                                                                        } else {
                                                                                                            cbox.ownerCt.ckb_administrador.enable();
                                                                                                        }
                                                                                                }}}},
                                    { fieldLabel:__('Admin'),
                                      name: 'Administrador',
                                      ref: 'ckb_administrador',
                                      xtype:'checkbox' },
                                    { fieldLabel:__('Tecnico'),
                                      name: 'Tecnico',
                                      ref: 'ckb_tecnico',
                                      xtype:'checkbox' }
                            ]
                            ,listeners:{
                                beforecollapse:function(panel,anim){
                                    Ext.each(this.items.items,function(ct){ // hide all items
                                        if( ct.xtype )
                                            ct.setDisabled(true);
                                        else Ext.each(ct.items.items, function(sct){ sct.setDisabled(true); });
                                    });
                                },
                                beforeexpand:function(panel,anim){
                                    Ext.each(this.items.items,function(ct){ // hide all items
                                        if( ct.xtype )
                                            ct.setDisabled(false);
                                        else Ext.each(ct.items.items, function(sct){ sct.setDisabled(false); });

                                    });
                                    // clean this user_service_list
                                    if( this.user_service_list ){
                                        this.user_service_list.setValue( '' );
                                    }

                                    Ext.each(this.find('name','primavera-action'),function(ct){ // enable/disable cod field
                                        if( (ct.inputValue=='edit') && ct.checked ){
                                            ct.ownerCt.cod_new.setDisabled(true);
                                            ct.ownerCt.cod_edit.setDisabled(false);
                                        } else {
                                            ct.ownerCt.cod_new.setDisabled(false);
                                            ct.ownerCt.cod_edit.setDisabled(true);
                                        }
                                    });
                                },
                                afterrender: function(c){
                                    if( this.find('name','cod')[0].getValue() ){
                                        this.expand();
                                    } else {
                                        this.collapse();
                                    }
                                }
                            }
                            ,reset: function(data){
                                if( !data ) data = {};
                                this.loadRecord(data);
                                this.find('name','password')[0].setValue('');
                                this.find('name','verpassword')[0].setValue('');
                            }
                            ,loadRecord: function(data){
                                var action = data['action'] ? data['action'] : 'edit';
                                Ext.each(this.find('name','primavera-action'),function(ct){ // set all items
                                    if( ct.inputValue == action ){
                                        ct.setValue(true);
                                    } else {
                                        ct.setValue(false);
                                    }
                                });

                                Ext.each(this.find('name','cod'),function(ct){ // set all items
                                    ct.setValue( data['cod'] );
                                });

                                this.find('name','Nome')[0].setValue( data['Nome'] );
                                this.find('name','Email')[0].setValue( data['Email'] );
                                this.find('name','perfil')[0].setValue( data['PerfilSugerido'] );
                                this.find('name','loginWindows')[0].setValue( data['LoginWindows'] );
                                this.find('name','idioma')[0].setValue( data['Idioma'] );
                                this.find('name','SuperAdministrador')[0].setValue( data['SuperAdministrador'] );
                                this.find('name','Administrador')[0].setValue( data['Administrador'] );
                                this.find('name','Tecnico')[0].setValue( data['Tecnico'] );

                                //this.find('name','password')[0].allowBlank = (action == 'edit')? true : false;
                                //this.find('name','verpassword')[0].allowBlank = (action == 'edit')? true : false;
                            }
                            ,load: function(data){
                                var service_id = this.service_id.getValue();
                                var services_list = data['user_service_list'];
                                var extra_str = null;
                                for(var i=0; i<services_list.length && !extra_str; i++){
                                    if( services_list[i]['service_id'] == service_id){
                                        extra_str = services_list[i]['extra'];
                                    }
                                }
                                if( extra_str ){
                                    // add to user_service_list
                                    this.add( { 'xtype':'hidden', 'name': 'user_service_list', 'ref': 'user_service_list' , 'value': service_id } );

                                    var extra = Ext.decode(extra_str);
                                    if( !extra['cod'] && extra['u_cod'] ) extra['cod'] = extra['u_cod'];
                                    var conn = new Ext.data.Connection({
                                        listeners:{
                                            // wait message.....
                                            beforerequest:function(){
                                                Ext.MessageBox.show({
                                                    title: <?php echo json_encode(__('Please wait...')) ?>,
                                                    msg: <?php echo json_encode(__('Retrieving data...')) ?>,
                                                    width:300,
                                                    wait:true,
                                                    modal: true
                                                });
                                            },// on request complete hide message
                                            requestcomplete:function(){Ext.MessageBox.hide();}
                                            ,requestexception:function(c,r,o){
                                                                    Ext.MessageBox.hide();
                                                                    Ext.Ajax.fireEvent('requestexception',c,r,o);}
                                        }
                                    });// end conn

                                    conn.request({
                                        url: <?php echo json_encode(url_for('primavera/json'))?>,
                                        params: { id:service_id, method:'primavera_viewuser', params:Ext.encode(extra)},
                                        scope:this,
                                        success: function(resp,opt) {
                                            var response = Ext.decode(resp.responseText);

                                            this.loadRecord(response['data']);
                                        }
                                    });//END Ajax request
                                }
                            }
                            ,onSave: function(data){
                                var user_service_list_value;
                                if( this.user_service_list ){
                                    user_service_list_value = this.user_service_list.getValue();
                                }
                                if( data['user_list-createedit-tab-primavera-checkbox'] ){
                                    var pData = new Object();

                                    pData['service_id'] = this.service_id.getValue();
                                    pData['user_id'] = data['id'];

                                    Ext.each(this.find('name','primavera-action'),function(ct){ // set all items
                                        if( ct.checked ){
                                            pData['action'] = ct.getRawValue();
                                        }
                                    });

                                    pData['cod'] = this.find('name','cod')[0].getValue();
                                    pData['Nome'] = this.find('name','Nome')[0].getValue();
                                    pData['Email'] = this.find('name','Email')[0].getValue();
                                    pData['perfil'] = this.find('name','perfil')[0].getValue();
                                    pData['idioma'] = this.find('name','idioma')[0].getValue();
                                    pData['loginWindows'] = this.find('name','loginWindows')[0].getValue();
                                    pData['SuperAdministrador'] = this.find('name','SuperAdministrador')[0].getValue();
                                    pData['Administrador'] = this.find('name','Administrador')[0].getValue();
                                    pData['Tecnico'] = this.find('name','Tecnico')[0].getValue();
                                    pData['Password'] = this.find('name','password')[0].getValue();

                                    Primavera.User.CreateEdit.onSave(pData);
                                } else if( user_service_list_value ){
                                    var extra = { 'cod': this.find('name','cod')[0].getValue() };
                                    call_save_sfGuardUser_UpdateUserService({ 'id': data['id'], 'service_id': user_service_list_value, 'extra': Ext.encode(extra) });
                                }
                            }
                        }]};
    Ext.apply(Obj,config);
    return Obj;
};
</script>
