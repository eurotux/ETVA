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
        this.items = [
                new Ext.grid.GridPanel({
                    id: 'grid-users',
                    layout: 'fit',
                    viewConfig: { forceFit: true },
                    //title: <?php echo json_encode(__('List users')) ?>,
                    /*width: 720,
                    height: 560,*/
                    frame: true,
                    loadMask:true,    
                    iconCls: 'icon-grid',
                    store: new Ext.data.Store({
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
                                                            {name: 'telemovel', mapping: 'Telemovel'},
                                                            {name: 'suadmin', mapping: 'SuperAdministrador'},
                                                            {name: 'admin', mapping: 'Administrador'},
                                                            {name: 'tecnico', mapping: 'Tecnico'}
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
                            {id: 'cod', header: __('User'), sortable: true, dataIndex: 'cod', width:80 },
                            {header: __('Name'), dataIndex: 'name', width:200},
                            {header: __('Email'), dataIndex: 'email', width:120},
                            {header: __('Telemovel'), dataIndex: 'telemovel', width:120},
                            {header: __('Super Administrator'), dataIndex: 'suadmin', width:60, renderer:function(v){return v ? __('Yes') : __('No');}},
                            {header: __('Administrator'), dataIndex: 'admin', width:60, renderer:function(v){return v ? __('Yes') : __('No');}},
                            {header: __('Tecnico'), dataIndex: 'tecnico', width:60, renderer:function(v){return v ? __('Yes') : __('No');}},
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
                                    url: <?php echo(json_encode(url_for('primavera/Primavera_DeleteUser')))?>,
                                    call:'Primavera.DeleteUser',
                                    scope:this,
                                    handler: function(item) {
                                                        var selected = item.ownerCt.ownerCt.getSelectionModel().getSelected();
                                                        if( selected ){
                                                            Ext.Msg.show({
                                                                title: item.text,
                                                                buttons: Ext.MessageBox.YESNOCANCEL,
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
                        ]
                })
            ];

        Primavera.Users.Panel.superclass.initComponent.call(this);
    }
    ,loadRecord: function(){
        /*this.load({url:<?php echo json_encode(url_for('primavera/json'))?>,params:{id:this.service_id,method:'primavera_listusers'} ,waitMsg:'Loading...'
                        ,success:function(f,a){
                            if( a.result['data']['users'].length > 0 ){
                                var users = a.result['data']['users'];
                                var data = new Array();
                                for(var i=0; i<users.length; i++){
                                    var e = new Array(users[i]['cod'],users[i]['Nome'], users[i]['Email'],users[i]['Telemovel']);
                                    var suadmin = users[i]['SuperAdministrador'] ? __('Yes') : __('No');
                                    e.push( suadmin );
                                    var admin = users[i]['Administrador'] ? __('Yes') : __('No');
                                    e.push( admin );
                                    var tecnico = users[i]['Tecnico'] ? __('Yes') : __('No');
                                    e.push( tecnico );
                                    data.push(e);
                                }
                                
                                Ext.getCmp('grid-users').store.loadData(data);
                            }
                        }
                        ,scope: this
                    });*/
        Ext.getCmp('grid-users').store.reload();
    }
});

Primavera.Users.Window = function(config) {

    Ext.apply(this,config);

    Primavera.Users.Window.superclass.constructor.call(this, {
        width:800
        ,height:600
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
