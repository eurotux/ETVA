<script>

Ext.ns('ETFS.Main');

/*
 * ETFS Main Panel
 */
ETFS.Main = function(config){
	Ext.apply(this,config);

    this.url = <?php echo json_encode(url_for('etfs/json'))?>;

    this.title = <?php echo json_encode(__('File Server')) ?>;
	this.service_id = this.service['id'];
    var service_id = this.service_id;

    this.defaults = { border:false, service_id: service_id };

    this.items  = [
                {
                    anchor: '100% 100%',
                    layout: {
                        type: 'vbox',
                        align: 'stretch'  // Child items are stretched to full width
                    }
                    ,defaults: { flex: 1, autoScroll: true, border: false }
                    ,items: [
                                {
                                    // Shares
                                    tbar: [
                                                {
                                                    text: <?php echo json_encode(__('Add shared folder')) ?>,
                                                    ref: '../newSharedFolderBtn',
                                                    iconCls:'icon-share-add',
                                                    disabled: false,
                                                    url: <?php echo(json_encode(url_for('etfs/ETFS_EditShare')))?>,
                                                    call:'ETFS.EditShare',
                                                    scope:this,
                                                    callback: function(item) {
                                                        var window = new ETFS.EditShare.Window({
                                                                            title: <?php echo json_encode(__('Add shared folder')) ?>,
                                                                            service_id:service_id });

                                                        window.on({
                                                            show:{fn:function(){window.loadData({service_id:service_id, new:true});}}
                                                            ,onSave:{fn:function(){
                                                                    this.close();
                                                                    Ext.getCmp('etfs-shares-dataview').getStore().reload();
                                                            }}
                                                        });
                                                        
                                                        window.show();
                                                    },
                                                    handler: function(btn){View.loadComponent(btn);}
                                                },
                                                {
                                                    text: <?php echo json_encode(__('Add shared printer')) ?>,
                                                    ref: '../newSharedPrinterBtn',
                                                    iconCls:'icon-share-add',
                                                    disabled: false,
                                                    url: <?php echo(json_encode(url_for('etfs/ETFS_EditShare')))?>,
                                                    call:'ETFS.EditShare',
                                                    scope:this,
                                                    callback: function(item) {
                                                        var window = new ETFS.EditShare.Window({
                                                                            title: <?php echo json_encode(__('Add shared printer')) ?>,
                                                                            service_id:service_id });

                                                        window.on({
                                                            show:{fn:function(){window.loadData({service_id:service_id, 'new':true, type:'printer' });}}
                                                            ,onSave:{fn:function(){
                                                                    this.close();
                                                                    Ext.getCmp('etfs-shares-dataview').getStore().reload();
                                                            }}
                                                        });
                                                        
                                                        window.show();
                                                    },
                                                    handler: function(btn){View.loadComponent(btn);}
                                                }
                                                ,{
                                                    text: <?php echo json_encode(__('Edit share')) ?>,
                                                    ref: '../editshareBtn',
                                                    iconCls:'icon-share-edit',
                                                    disabled: true,
                                                    url: <?php echo(json_encode(url_for('etfs/ETFS_EditShare')))?>,
                                                    call:'ETFS.EditShare',
                                                    scope:this,
                                                    callback: function(item) {

                                                        var selected = item.ownerCt.ownerCt.dataViewETFSShares.getSelectedRecords();
                                                        if( selected.length>0 ){
                                                            var sharedata = selected[0].data;

                                                            var window = new ETFS.EditShare.Window({
                                                                                title: <?php echo json_encode(__('Edit share')) ?>,
                                                                                service_id:service_id, share:sharedata });

                                                            window.on({
                                                                show:{fn:function(){window.loadData({service_id:service_id, share:sharedata});}}
                                                                ,onSave:{fn:function(){
                                                                        this.close();
                                                                        Ext.getCmp('etfs-shares-dataview').getStore().reload();
                                                                }}
                                                            });
                                                            
                                                            window.show();

                                                        } else {
                                                            Ext.Msg.show({
                                                                title: <?php echo json_encode(__('Error no share selected')) ?>,
                                                                buttons: Ext.MessageBox.OK,
                                                                msg: <?php echo json_encode(__('No share selected for update!')) ?>,
                                                                icon: Ext.MessageBox.ERROR});
                                                        }
                                                    },
                                                    handler: function(btn){View.loadComponent(btn);}
                                                }
                                                ,{
                                                    text: <?php echo json_encode(__('Remove share')) ?>,
                                                    ref: '../delshareBtn',
                                                    iconCls:'icon-share-delete',
                                                    disabled: true,
                                                    scope:this,
                                                    handler: function(item) {
                                                                        var selected = item.ownerCt.ownerCt.dataViewETFSShares.getSelectedRecords();
                                                                        if( selected.length>0 ){
                                                                            Ext.Msg.show({
                                                                                title: item.text,
                                                                                buttons: Ext.MessageBox.YESNO,
                                                                                scope:this,
                                                                                msg: String.format(<?php echo json_encode(__('Are you sure you want delete share {0}?')) ?>,selected[0].data['name']),
                                                                                fn: function(btn){
                                                                                                if (btn == 'yes'){
                                                                                                    var conn = new Ext.data.Connection({
                                                                                                        listeners:{
                                                                                                            // wait message.....
                                                                                                            beforerequest:function(){
                                                                                                                Ext.MessageBox.show({
                                                                                                                    title: <?php echo json_encode(__('Please wait...')) ?>,
                                                                                                                    msg: <?php echo json_encode(__('Remove share...')) ?>,
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
                                                                                                        url: <?php echo json_encode(url_for('etfs/json'))?>,
                                                                                                        params: {id:service_id,method:'delete_share', params: Ext.encode({ name: selected[0].data['name'] }) },
                                                                                                        scope:this,
                                                                                                        success: function(resp,opt) {

                                                                                                            var response = Ext.util.JSON.decode(resp.responseText);
                                                                                                            Ext.ux.Logger.info(response['agent'], response['response']);
                                                                                                            Ext.getCmp('etfs-shares-dataview').getStore().reload();
                                                                                                        }
                                                                                                        ,failure: function(resp,opt) {
                                                                                                            var response = Ext.util.JSON.decode(resp.responseText);
                                                                                                            if(response && resp.status!=401)
                                                                                                                Ext.Msg.show({
                                                                                                                    title: String.format(<?php echo json_encode(__('Error {0}')) ?>,response['agent']),
                                                                                                                    buttons: Ext.MessageBox.OK,
                                                                                                                    msg: String.format(<?php echo json_encode(__('Unable to delete share!')) ?>)+'<br>'+response['info'],
                                                                                                                    icon: Ext.MessageBox.ERROR});
                                                                                                        }
                                                                                                    });// END Ajax request
                                                                                                }//END button==yes
                                                                                            }
                                                                                });
                                                                        } else {
                                                                            Ext.Msg.show({
                                                                                title: <?php echo json_encode(__('Error no share selected')) ?>,
                                                                                buttons: Ext.MessageBox.OK,
                                                                                msg: <?php echo json_encode(__('No share selected for delete!')) ?>,
                                                                                icon: Ext.MessageBox.ERROR});
                                                                        } // END if selected
                                                                    }
                                                },
                                                '->',
                                                {
                                                    text: __('Refresh'),
                                                    tooltip: __('Refresh'),
                                                    ref: '../refreshshareBtn',
                                                    iconCls:'x-tbar-loading',
                                                    scope:this,
                                                    handler: function(item) {
                                                        Ext.getCmp('etfs-shares-dataview').getStore().reload();
                                                    }
                                                }
                                    ],
                                    defaults: { flex: 1, autoScroll: true, border: false }
                                    ,items: [
                                        new Ext.DataView({
                                            store: new Ext.data.JsonStore({
                                                url: <?php echo json_encode(url_for('etfs/json')) ?>,
                                                baseParams:{id:service_id,method:'list_shares_only'} ,
                                                root: 'data',
                                                fields: [
                                                    'name','comment','printable'
                                                ]
                                                ,autoLoad: true
                                            }),
                                            tpl: new Ext.XTemplate(
                                                '<ul>',
                                                    '<tpl for=".">',
                                                        '<li class="etfs-share" id="{name}">',
                                                            '<img width="64" height="64" src="../images/silk/icons/folder.png" title="{name}" />',
                                                            '<strong>{name} {comment}</strong>',
                                                        '</li>',
                                                    '</tpl>',
                                                '</ul>'
                                            ),
                                            /*plugins : [
                                                new Ext.ux.DataViewTransition({
                                                    duration  : 550,
                                                    idProperty: 'name'
                                                })
                                            ],*/
                                            ref: 'dataViewETFSShares',
                                            id: 'etfs-shares-dataview',
                                            autoHeight:true,
                                            singleSelect: true,
                                            multiSelect: false,
                                            autoScroll  : true,
                                            overClass:'etfs-share-over',
                                            itemSelector:'li.etfs-share',
                                            emptyText: 'No shares to display'
                                            ,'listeners': {
                                                    selectionchange: { fn: function(dv,selected){
                                                                                console.log(selected);
                                                                                var btnState = selected.length < 1 ? true :false;
                                                                                if( dv.ownerCt.delshareBtn ){
                                                                                    var btnDelState = btnState;
                                                                                    for(var i=0; i<selected.length; i++){
                                                                                        if( selected[i].id == 'global' )
                                                                                            btnDelState = true;
                                                                                    }
                                                                                    dv.ownerCt.delshareBtn.setDisabled(btnDelState);
                                                                                }
                                                                                if( dv.ownerCt.editshareBtn ){
                                                                                    dv.ownerCt.editshareBtn.setDisabled(btnState);
                                                                                }
                                                                    }}
                                            }
                                        }),
                                    ]
                                },
                                {
                                    // Users
                                    tbar: [
                                                {
                                                    text: <?php echo json_encode(__('Add user')) ?>,
                                                    ref: '../newuserBtn',
                                                    iconCls:'icon-user-add',
                                                    disabled: false,
                                                    url: <?php echo(json_encode(url_for('etfs/ETFS_EditUser')))?>,
                                                    call:'ETFS.EditUser',
                                                    scope:this,
                                                    callback: function(item) {
                                                        var window = new ETFS.EditUser.Window({
                                                                            title: <?php echo json_encode(__('Add user')) ?>,
                                                                            service_id:service_id });

                                                        window.on({
                                                            show:{fn:function(){window.loadData({service_id:service_id, new:true});}}
                                                            ,onSave:{fn:function(){
                                                                    this.close();
                                                                    Ext.getCmp('etfs-users-dataview').getStore().reload();
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
                                                    url: <?php echo(json_encode(url_for('etfs/ETFS_EditUser')))?>,
                                                    call:'ETFS.EditUser',
                                                    scope:this,
                                                    callback: function(item) {
                                                        var window = new ETFS.EditUser.Window({
                                                                            title: <?php echo json_encode(__('Edit user')) ?>,
                                                                            service_id:service_id });

                                                        var userdata;
                                                        var selected = item.ownerCt.ownerCt.dataViewETFSUsers.getSelectedRecords();
                                                        if( selected.length>0 ){
                                                            userdata = selected[0].data;

                                                            window.on({
                                                                show:{fn:function(){window.loadData({service_id:service_id, user:userdata});}}
                                                                ,onSave:{fn:function(){
                                                                        this.close();
                                                                        Ext.getCmp('etfs-users-dataview').getStore().reload();
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
                                                    scope:this,
                                                    handler: function(item) {
                                                                        var selected = item.ownerCt.ownerCt.dataViewETFSUsers.getSelectedRecords();
                                                                        if( selected.length>0 ){
                                                                            console.log(selected);
                                                                            var userdata = selected[0].data;
                                                                            Ext.Msg.show({
                                                                                title: item.text,
                                                                                buttons: Ext.MessageBox.YESNO,
                                                                                scope:this,
                                                                                msg: String.format(<?php echo json_encode(__('Are you sure you want delete user {0}?')) ?>,userdata['name']),
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
                                                                                                        url: <?php echo json_encode(url_for('etfs/json'))?>,
                                                                                                        params: {id:service_id,method:'delete_user', params: Ext.encode({ name: userdata['name'], 'sync_to_samba': true }) },
                                                                                                        scope:this,
                                                                                                        success: function(resp,opt) {

                                                                                                            var response = Ext.util.JSON.decode(resp.responseText);
                                                                                                            Ext.ux.Logger.info(response['agent'], response['response']);
                                                                                                            Ext.getCmp('etfs-users-dataview').getStore().reload();
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
                                                },
                                                '->',
                                                {
                                                    text: __('Refresh'),
                                                    tooltip: __('Refresh'),
                                                    ref: '../refreshuserBtn',
                                                    iconCls:'x-tbar-loading',
                                                    scope:this,
                                                    handler: function(item) {
                                                        Ext.getCmp('etfs-users-dataview').getStore().reload();
                                                    }
                                                }
                                    ],
                                    defaults: { flex: 1, autoScroll: true, border: false }
                                    ,items: [
                                        new Ext.DataView({
                                            store: new Ext.data.JsonStore({
                                                url: <?php echo json_encode(url_for('etfs/json')) ?>,
                                                baseParams:{id:service_id,method:'list_smb_users'} ,
                                                root: 'data',
                                                fields: [
                                                    'uid','name','opts'
                                                ]
                                                ,autoLoad: true
                                            }),
                                            tpl: new Ext.XTemplate(
                                                '<ul>',
                                                    '<tpl for=".">',
                                                        '<li class="etfs-user" id="{name}">',
                                                            '<img width="64" height="64" src="../images/silk/icons/user.png" title="{name}" />',
                                                            '<strong>{name}</strong>',
                                                        '</li>',
                                                    '</tpl>',
                                                '</ul>'
                                            ),
                                            /*plugins : [
                                                new Ext.ux.DataViewTransition({
                                                    duration  : 550,
                                                    idProperty: 'name'
                                                })
                                            ],*/
                                            ref: 'dataViewETFSUsers',
                                            id: 'etfs-users-dataview',
                                            autoHeight:true,
                                            singleSelect: true,
                                            multiSelect: false,
                                            autoScroll  : true,
                                            overClass:'etfs-user-over',
                                            itemSelector:'li.etfs-user',
                                            emptyText: 'No users to display'
                                            ,'listeners': {
                                                    selectionchange: { fn: function(dv,selected){
                                                                                var btnState = selected.length < 1 ? true :false;
                                                                                if( dv.ownerCt.deluserBtn ){
                                                                                    dv.ownerCt.deluserBtn.setDisabled(btnState);
                                                                                }
                                                                                if( dv.ownerCt.edituserBtn ){
                                                                                    dv.ownerCt.edituserBtn.setDisabled(btnState);
                                                                                }
                                                                    }}
                                            }
                                        })
                                    ]
                                }
                                ,{ title:<?php echo json_encode(__('General options')) ?>,
                                    id: 'etfs-general-options',
                                    layout: 'hbox',
                                    height:130,
                                    margins: '0 3 3 3',
                                    cmargins: '3 3 3 3',
                                    split: true,
                                    //border:false,
                                    bodyStyle: 'padding:0px',
                                    tbar: [
                                                '->',
                                                {
                                                    text: __('Refresh'),
                                                    tooltip: __('Refresh'),
                                                    ref: '../refreshGlobalBtn',
                                                    iconCls:'x-tbar-loading',
                                                    scope:this,
                                                    handler: function(item) {
                                                        this.reload();
                                                    }
                                                }
                                    ],
                                    items:[
                                        {
                                            xtype: 'buttongroup',
                                            title: <?php echo json_encode(__('Edit Global configuration')) ?>,
                                            columns: 1,
                                            height:60,
                                            defaults: {
                                                scale: 'small', width:140
                                            },
                                            items: [{
                                                    //xtype:'splitbutton',
                                                    text: <?php echo json_encode(__('Edit Global configuration')) ?>,
                                                    tooltip: <?php echo json_encode(__('Edit global configuration')) ?>,
                                                    url: <?php echo(json_encode(url_for('etfs/ETFS_EditGlobal')))?>,
                                                    call:'ETFS.EditGlobal',
                                                    scope:this,
                                                    callback: function(item) {
                                                        var window = new ETFS.EditGlobal.Window({
                                                                            title: <?php echo json_encode(__('Global configuration')) ?>,
                                                                            service_id:service_id, share:{'name':'global'} });

                                                        window.on({
                                                            show:{fn:function(){window.loadData({service_id:service_id, share:{'name':'global'}});}}
                                                            ,onSave:{fn:function(){
                                                                    this.close();
                                                                    var parentCmp = Ext.getCmp((item.scope).id);
                                                                    //parentCmp.fireEvent('refresh',parentCmp);
                                                            }}
                                                        });
                                                        
                                                        window.show();

                                                    },
                                                    handler: function(btn){View.loadComponent(btn);}
                                                }]},
                                        {
                                        xtype: 'buttongroup',
                                        title: <?php echo json_encode(__('Client status')) ?>,
                                        columns: 1,
                                        height:60,
                                        defaults: {
                                            scale: 'small', width:140
                                        },
                                        items: [{
                                                //xtype:'splitbutton',
                                                text: <?php echo json_encode(__('Show client status')) ?>,
                                                tooltip: <?php echo json_encode(__('Show all client connections and status')) ?>
                                                ,handler:function(){
                                                    //Ext.getBody().mask('Preparing data...');

                                                    // create and show window
                                                    var win = new Ext.Window({
                                                            title: <?php echo json_encode(__('Client status')) ?>
                                                            ,layout:'fit'
                                                            ,width:420
                                                            ,height:360
                                                            ,modal:true
                                                            ,closable:true
                                                            ,border:false
                                                            ,items:[
                                                                    new Ext.form.FormPanel({
                                                                        layout:'fit',
                                                                        autoScroll:false,
                                                                        //title: 'Navigation',
                                                                        region: 'west',
                                                                        split: true,
                                                                        width: 200,
                                                                        margins:'3 0 3 3',
                                                                        cmargins:'3 3 3 3'
                                                                        ,tbar: [
                                                                                    '->',
                                                                                    {
                                                                                        text: __('Refresh'),
                                                                                        tooltip: __('Refresh'),
                                                                                        ref: '../refreshshareBtn',
                                                                                        iconCls:'x-tbar-loading',
                                                                                        scope:this,
                                                                                        handler: function(item) {
                                                                                            item.ownerCt.ownerCt.loadData();
                                                                                        }
                                                                                    }
                                                                        ]
                                                                        /*,onRender: function(){
                                                                            console.log('render client status');
                                                                             // set wait message target
                                                                            //this.getForm().waitMsgTarget = this.getEl();

                                                                            // loads form after initial layout
                                                                           this.on('afterlayout', this.onLoad, this, {single:true});
                                                                        }*/
                                                                        ,loadData:function(){
                                                                           this.load({
                                                                               url: <?php echo(json_encode(url_for('etfs/json')))?>
                                                                               ,waitMsg: <?php echo json_encode(__('Loading...')) ?>
                                                                               ,params:{id:service_id,method:'get_samba_status_raw'}
                                                                               ,success:function ( form, action ) {
                                                                                   var result = action.result;
                                                                                   var data = result.data;
                                                                                   var s_status = data['status'].replace(/\n/g,"<br/>");
                                                                                   this.update(s_status);
                                                                               },scope:this
                                                                           });
                                                                        }
                                                                    })
                                                                ]
                                                        });

                                                    (function(){
                                                            win.show();
                                                            win.items.get(0).loadData();
                                                    }).defer(100);

                                                },scope:this
                                            }]},
                                    /*{
                                        xtype: 'buttongroup',
                                        title: <?php echo json_encode(__('Edit configfile<br> in texteditor')) ?>,
                                        columns: 1,
                                        height:60,
                                        defaults: {
                                            scale: 'small', width:110
                                        },
                                        items: [{
                                                //xtype:'splitbutton',
                                                text: <?php echo json_encode(__('Configfile')) ?>,
                                                tooltip: <?php echo json_encode(__('Edit configfile in texteditor (caution!)')) ?>
                                                ,handler:function(){
                                                    var win = new Ext.Window({
                                                            title:'Editor samba.conf'
                                                            ,layout:'fit'
                                                            ,width:800
                                                            ,modal:true
                                                            ,height:450
                                                            ,closable:true
                                                            ,border:false
                                                            //,items:[this.buildEditor()]
                                                        });
                                                        win.show();
                                                },scope:this
                                            }]},*/
                                    { xtype: 'spacer', flex: 1 },
                                    {
                                        xtype: 'buttongroup',
                                        title: <?php echo json_encode(__('Start service')) ?>,
                                        tooltip:<?php echo json_encode(__('Start file server service')) ?>,
                                        columns: 1,
                                        height:60,
                                        defaults: {
                                            scale: 'small', width:160
                                        },
                                        items: [{
                                                //xtype:'splitbutton',
                                                text: <?php echo json_encode(__('Start service')) ?>,
                                                tooltip:<?php echo json_encode(__('Start file server service')) ?>
                                                ,ref: '../btnStartService'
                                                ,action:'start_service'
                                                ,handler:this.applyConfiguration
                                                ,scope:this

                                            }]},
                                    {
                                        xtype: 'buttongroup',
                                        title: <?php echo json_encode(__('Stop service')) ?>,
                                        tooltip: <?php echo json_encode(__('Stop file server service')) ?>,
                                        columns: 1,
                                        height:60,
                                        defaults: {
                                            scale: 'small', width:160
                                        },
                                        items: [{
                                                //xtype:'splitbutton',
                                                text: <?php echo json_encode(__('Stop service')) ?>,
                                                tooltip: <?php echo json_encode(__('Stop file server service')) ?>
                                                ,ref: '../btnStopService'
                                                ,action:'stop_service'
                                                ,handler:this.applyConfiguration
                                                ,scope:this

                                            }]},
                                    {
                                        xtype: 'buttongroup',
                                        title: <?php echo json_encode(__('Apply current configuration')) ?>,
                                        columns: 1,
                                        height:60,
                                        defaults: {
                                            scale: 'small', width:170
                                        },
                                        items: [{
                                                text: <?php echo json_encode(__('Apply configuration')) ?>,
                                                tooltip: <?php echo json_encode(__('Click this button to apply current changes configuration on the file server service')) ?>
                                                ,ref: '../btnRestartService'
                                                ,action:'restart_service'
                                                ,handler:this.applyConfiguration
                                                ,scope: this
                                            }]}
                                    ]
                            }
                        ]
                    }
        ];

    ETFS.Main.superclass.constructor.call(this, { 
                                                    reload: function(){
                                                        this.loadRecord();
                                                    },
                                                    loadRecord: function(){
                                                        var conn = new Ext.data.Connection({
                                                            listeners:{
                                                                // wait message.....
                                                                beforerequest:function(){
                                                                    Ext.MessageBox.show({
                                                                        title: <?php echo json_encode(__('Loading...')) ?>, 
                                                                        msg: <?php echo json_encode(__('Loading...')) ?>,
                                                                        width:300,
                                                                        wait:true,
                                                                        modal: true
                                                                    });
                                                                },// on request complete hide message
                                                                requestcomplete:function(){Ext.MessageBox.hide();}
                                                            }
                                                        });// end conn
                                                        conn.request({
                                                            url: this.url,
                                                            params:{id:this.service_id,method:'status_service'},
                                                            failure: function(resp,opt){

                                                                if(!resp.responseText){
                                                                    Ext.ux.Logger.error(resp.statusText);
                                                                    return;
                                                                }

                                                                var response = Ext.util.JSON.decode(resp.responseText);
                                                                Ext.MessageBox.alert('Error Message', response['info']);
                                                                Ext.ux.Logger.error(response['error']);

                                                            },
                                                            // everything ok...
                                                            success: function(resp,opt){
                                                                var response = Ext.util.JSON.decode(resp.responseText);
                                                                if( response.data.running==1 ){
                                                                    Ext.getCmp('etfs-general-options').btnStartService.setDisabled(true);
                                                                    Ext.getCmp('etfs-general-options').btnStopService.setDisabled(false);
                                                                    Ext.getCmp('etfs-general-options').btnRestartService.setDisabled(false);
                                                                } else {
                                                                    Ext.getCmp('etfs-general-options').btnStopService.setDisabled(true);
                                                                    Ext.getCmp('etfs-general-options').btnRestartService.setDisabled(true);
                                                                    Ext.getCmp('etfs-general-options').btnStartService.setDisabled(false);
                                                                }
                                                            },scope:this
                                                        });// END Ajax request
                                                    }
                                            });
    this.on({
        'afterRender': function() {
            this.loadRecord();
        }
        ,refresh:{ scope:this, fn:function(){                                    
                    this.loadRecord();
                }
        }
    });
}

// define public methods
Ext.extend(ETFS.Main, Ext.Panel,{ 
    applyConfiguration:function(b,e){
        var conn = new Ext.data.Connection({
            listeners:{
                // wait message.....
                beforerequest:function(){
                    Ext.MessageBox.show({
                        title: <?php echo json_encode(__('Please wait')) ?>,
                        msg: <?php echo json_encode(__('Waiting...')) ?>,
                        width:300,
                        wait:true,
                        modal: true
                    });
                },// on request complete hide message
                requestcomplete:function(){Ext.MessageBox.hide();}
            }
        });// end conn
        conn.request({
            url: this.url,
            params:{id:this.service_id,method:b.action},
            failure: function(resp,opt){

                if(!resp.responseText){
                    Ext.ux.Logger.error(resp.statusText);
                    return;
                }

                var response = Ext.util.JSON.decode(resp.responseText);
                Ext.MessageBox.alert('Error Message', response['info']);
                Ext.ux.Logger.error(response['error']);

            },
            // everything ok...
            success: function(resp,opt){

                var msg = b.text+' Ok.';
                Ext.ux.Logger.info(msg);
                View.notify({html:msg});

                this.reload();                    

            },scope:this
        });// END Ajax request
    }
});

</script>
