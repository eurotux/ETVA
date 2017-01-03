<script>

Ext.ns('Primavera.Users');

Primavera.Users.Panel = new Ext.extend( Ext.Panel, {

    id: 'primavera-users-panel',
    border: false,
    labelWidth: 140,
    layout: 'fit',
    viewConfig: { forceFit: true },
    defaults: { border:false },
    initComponent:function(){
        var service_id = this.service_id;

        var usersGridStore = new Ext.data.Store({
            url:<?php echo json_encode(url_for('primavera/json'))?>,
            baseParams: {id:this.service_id,method:'primavera_listusers'},
            reader: new Ext.data.JsonReader(
                                {
                                    idProperty: 'cod'
                                    ,root: 'data'
                                    ,fields: [
                                                {name: 'cod'},
                                                {name: 'name', mapping: 'Nome'},
                                                {name: 'email', mapping: 'Email'},
                                                {name: 'idioma', mapping: 'Idioma'},
                                                {name: 'perfil', mapping: 'PerfilSugerido'},
                                                {name: 'suadmin', mapping: 'SuperAdministrador'},
                                                {name: 'admin', mapping: 'Administrador'},
                                                {name: 'tecnico', mapping: 'Tecnico'}
                                            ]
                                }
                        )
        });
        this.items = [
                new Ext.grid.GridPanel({
                    id: 'primavera-users-gridpanel',
                    layout: 'fit',
                    viewConfig: { forceFit: true },
                    frame: true,
                    loadMask:true,
                    iconCls: 'icon-grid',
                    store: usersGridStore,
                    colModel: new Ext.grid.ColumnModel({
                        defaults: {
                            width: 120,
                            sortable: true
                        },
                        columns: [
                            {id: 'cod', header: __('User'), sortable: true, dataIndex: 'cod', width:80 },
                            {header: __('Name'), dataIndex: 'name', width:200},
                            {header: __('Email'), dataIndex: 'email', width:120},
                            {header: __('Language'), dataIndex: 'idioma', width:120},
                            {header: __('Profile'), dataIndex: 'perfil', width:120},
                            {header: __('Super Administrator'), dataIndex: 'suadmin', width:60, renderer:function(v){return (v=='true')? __('Yes') : __('No');}},
                            {header: __('Administrator'), dataIndex: 'admin', width:60, renderer:function(v){return (v=='true')? __('Yes') : __('No');}},
                            {header: __('Tecnico'), dataIndex: 'tecnico', width:60, renderer:function(v){return (v=='true')? __('Yes') : __('No');}},
                        ]
                    }),
                    sm: new Ext.grid.RowSelectionModel({ singleSelect:true,
                                                            'listeners': {
                                                                    selectionchange: { fn: function(sm){
                                                                                                var btnState = sm.getCount() < 1 ? true :false;
                                                                                                var selected = sm.getSelected();
                                                                                                if( sm.grid.deluserBtn ){
                                                                                                    sm.grid.deluserBtn.setDisabled(btnState);
                                                                                                }
                                                                                                if( sm.grid.edituserBtn ){
                                                                                                    sm.grid.edituserBtn.setDisabled(btnState);
                                                                                                }
                                                                                            }}} }),
                    tbar: [
                                {
                                    text: <?php echo json_encode(__('Add user')) ?>,
                                    ref: '../newuserBtn',
                                    iconCls:'icon-user-add',
                                    disabled: false,
                                    url: <?php echo(json_encode(url_for('primavera/Primavera_EditUser')))?>,
                                    call:'Primavera.EditUser',
                                    scope:this,
                                    callback: function(item) {
                                        //var service_id = this.service_id;
                                        var window = new Primavera.EditUser.Window({
                                                            title: <?php echo json_encode(__('Add user')) ?>,
                                                            service_id:service_id });

                                        window.on({
                                            show:{fn:function(){window.loadData({service_id:service_id, new:true});}}
                                            ,onSave:{fn:function(){
                                                    this.close();
                                                    var parentCmp = Ext.getCmp((item.scope).ownerCt.id);
                                                    parentCmp.fireEvent('refresh',parentCmp);
                                            }}
                                        });
                                        
                                        window.show();
                                    },
                                    handler: function(btn){View.loadComponent(btn);}
                                }
                                ,{
                                    text: <?php echo json_encode(__('Edit user')) ?>,
                                    ref: '../edituserBtn',
                                    iconCls:'icon-user-edit',
                                    disabled: true,
                                    url: <?php echo(json_encode(url_for('primavera/Primavera_EditUser')))?>,
                                    call:'Primavera.EditUser',
                                    scope:this,
                                    callback: function(item) {
                                        //var service_id = this.service_id;
                                        var window = new Primavera.EditUser.Window({
                                                            title: <?php echo json_encode(__('Edit user')) ?>,
                                                            service_id:service_id });

                                        var userdata;
                                        var selected = item.ownerCt.ownerCt.getSelectionModel().getSelected();
                                        if( selected ){
                                            userdata = selected.data;

                                            window.on({
                                                show:{fn:function(){window.loadData({service_id:service_id, user:userdata});}}
                                                ,onSave:{fn:function(){
                                                        this.close();
                                                        var parentCmp = Ext.getCmp((item.scope).ownerCt.id);
                                                        parentCmp.fireEvent('refresh',parentCmp);
                                                }}
                                            });
                                            
                                            window.show();

                                        } else {
                                            Ext.Msg.show({
                                                title: <?php echo json_encode(__('Error no user selected')) ?>,
                                                buttons: Ext.MessageBox.OK,
                                                msg: <?php echo json_encode(__('No user selected for update!')) ?>,
                                                icon: Ext.MessageBox.ERROR});
                                        }
                                    },
                                    handler: function(btn){View.loadComponent(btn);}
                                }
                                ,{
                                    text: <?php echo json_encode(__('Remove user')) ?>,
                                    ref: '../deluserBtn',
                                    iconCls:'icon-user-delete',
                                    disabled: true,
                                    /*url: <?php echo(json_encode(url_for('primavera/Primavera_DeleteUser')))?>,
                                    call:'Primavera.DeleteUser',*/
                                    scope:this,
                                    handler: function(item) {
                                                        var selected = item.ownerCt.ownerCt.getSelectionModel().getSelected();
                                                        if( selected ){
                                                            Ext.Msg.show({
                                                                title: item.text,
                                                                buttons: Ext.MessageBox.YESNO,
                                                                scope:this,
                                                                msg: String.format(<?php echo json_encode(__('Are you sure you want delete user {0}?')) ?>,selected.data['cod']),
                                                                fn: function(btn){
                                                                                if (btn == 'yes'){
                                                                                    var conn = new Ext.data.Connection({
                                                                                        listeners:{
                                                                                            // wait message.....
                                                                                            beforerequest:function(){
                                                                                                Ext.MessageBox.show({
                                                                                                    title: <?php echo json_encode(__('Please wait...')) ?>,
                                                                                                    msg: <?php echo json_encode(__('Remove user...')) ?>,
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
                                                                                        params: {id:this.service_id,method:'primavera_deleteuser', params: Ext.encode({ u_cod: selected.data['cod'] }) },
                                                                                        scope:this,
                                                                                        success: function(resp,opt) {

                                                                                            var response = Ext.util.JSON.decode(resp.responseText);
                                                                                            Ext.ux.Logger.info(response['agent'], response['response']);
                                                                                            this.loadRecord({id:this.server_id});
                                                                                        }
                                                                                        ,failure: function(resp,opt) {
                                                                                            var response = Ext.util.JSON.decode(resp.responseText);
                                                                                            if(response && resp.status!=401)
                                                                                                Ext.Msg.show({
                                                                                                    title: String.format(<?php echo json_encode(__('Error {0}')) ?>,response['agent']),
                                                                                                    buttons: Ext.MessageBox.OK,
                                                                                                    msg: String.format(<?php echo json_encode(__('Unable to delete user!')) ?>)+'<br>'+response['info'],
                                                                                                    icon: Ext.MessageBox.ERROR});
                                                                                        }
                                                                                    });// END Ajax request
                                                                                }//END button==yes
                                                                            }
                                                                });
                                                        } else {
                                                            Ext.Msg.show({
                                                                title: <?php echo json_encode(__('Error no user selected')) ?>,
                                                                buttons: Ext.MessageBox.OK,
                                                                msg: <?php echo json_encode(__('No user selected for delete!')) ?>,
                                                                icon: Ext.MessageBox.ERROR});
                                                        } // END if selected
                                                    }
                                }
                        ],
                        bbar: new Ext.PagingToolbar({
                            store: usersGridStore,
                            displayInfo:true,
                            pageSize:10
                        })

                })
            ];

        Primavera.Users.Panel.superclass.initComponent.call(this);
    }
    ,loadRecord: function(){
        Ext.getCmp('primavera-users-gridpanel').store.reload();
    }
});

Primavera.Users.Window = function(config) {

    Ext.apply(this,config);

    Primavera.Users.Window.superclass.constructor.call(this, {
        width:600
        ,height:480
        ,border:false
        ,modal:true
        ,layout:'fit'
        ,items:[ new Primavera.Users.Panel({service_id:this.service_id})]
        ,loadRecord: function(){
                         Ext.getCmp('primavera-users-panel').loadRecord();
                    }
    });

    this.on({
        refresh:{ scope:this, fn:function(){
                    this.loadRecord();
                }
        }
    });
};


Ext.extend(Primavera.Users.Window, Ext.Window,{
    loadData:function(data){
        this.items.get(0).loadRecord(data);
    }
});

</script>
