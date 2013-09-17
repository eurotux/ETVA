<script>

Ext.ns("SfGuardGroup");

SfGuardGroup.CreateEditForm = function(config) {

	Ext.QuickTips.init();   	

    Ext.apply(this,config);

    this.hidden_id = new Ext.form.Hidden({name: 'id'});
    
    this.name = new Ext.form.TextField({
        fieldLabel: __('Name'),
        allowBlank: false,
        msgTarget:'side',
        name: 'name',
        maxLength: 50	    
    });
    
    this.description = new Ext.form.TextField({
        fieldLabel: <?php echo json_encode(__('Description')) ?>,
        allowBlank: false,
        msgTarget:'side',
        name: 'description',        
        maxLength: 50
    });
    
    this.permissions = Ext.getCmp('group_list-createedit-tab-permissions');
    if( !this.permissions ){
        var tabPerms_clusters_store = new Ext.data.Store({
                                                                        //autoLoad: true,
                                                                        proxy: new Ext.data.HttpProxy({
                                                                            url: <?php echo json_encode(url_for('cluster/jsonList')); ?>,
                                                                            method:'POST'}),
                                                                        reader: new Ext.data.JsonReader({
                                                                            root: 'data',
                                                                            fields:['Id','Name']})
                                                        });
        this.tabPerms_clusters = new Ext.ux.Multiselect({ xtype: 'multiselect',
                                                    name:'etva_permission_cluster_list',
                                                    fieldLabel: <?php echo json_encode(__('Clusters')) ?>,
                                                    valueField:"Id",
                                                    displayField:"Name",
                                                    allowBlank:true,
                                                    store: tabPerms_clusters_store
                                                    });
        tabPerms_clusters_store.load();
        var tabPerms_servers_store = new Ext.data.Store({
                                                                    //autoLoad: true,
                                                                    proxy: new Ext.data.HttpProxy({
                                                                        url: <?php echo json_encode(url_for('server/jsonListAll')); ?>,
                                                                        method:'POST'}),
                                                                    reader: new Ext.data.JsonReader({
                                                                        root: 'data',
                                                                        fields:['Id','Name']})
                                                                ,baseParams: { limit: 0, 'sort': 'name', 'dir': 'ASC' }
                                                        });
        this.tabPerms_servers = new Ext.ux.Multiselect({ xtype: 'multiselect',
                                                    name:'etva_permission_server_list',
                                                    fieldLabel: <?php echo json_encode(__('Servers')) ?>,
                                                    valueField:"Id",
                                                    displayField:"Name",
                                                    allowBlank:true,
                                                    store: tabPerms_servers_store
                                                    });
        tabPerms_servers_store.load();
        this.permissions = new Ext.form.FieldSet({ fieldLabel: __('Permissions'),
                            id:'group_list-createedit-tab-permissions',
                            xtype: 'fieldset',
                            width: '100%',
                            border: false,
                            bodyStyle: 'background:transparent;',
                            items: [
                                {
                                    layout:'column',
                                    border: false,
                                    bodyStyle: 'background:transparent;',
                                    defaults:{ layout:'form', border: false, labelAlign: 'top',bodyStyle: 'background:transparent;' },
                                    items: [
                                        { columnWidth: .5, items:[ this.tabPerms_clusters ] },
                                        { columnWidth: .5, items:[ this.tabPerms_servers ] }
                                    ]
                                }
                            ]
                            ,loadRecord: function(data){
                                    this.find('name','etva_permission_cluster_list')[0].setValue( data['group_cluster_permissions'] );
                                    this.find('name','etva_permission_server_list')[0].setValue( data['group_server_permissions'] );
                            },
                            load: function(data){
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
                                    url: 'sfGuardPermission/jsonGroupPermissions',
                                    params: { 'group_id' : this.group_id },
                                    scope:this,
                                    success: function(resp,opt) {
                                        var response = Ext.decode(resp.responseText);

                                        this.loadRecord(response['data']);
                                    }
                                });//END Ajax request
                            }
                            ,onSave: function(data,group){
                                var group_id = this.group_id;
                                if( !group_id ) group_id = group['group_id'];

                                //var etva_permission_cluster_list_value = this.etva_permission_cluster_list.getValue();
                                var etva_permission_cluster_list_value = data['etva_permission_cluster_list'];
                                if( etva_permission_cluster_list_value ){
                                    var groupPermData_admin = new Object();
                                    var perms_admin_list = [];

                                    groupPermData_admin['group_id'] = group_id;
                                    groupPermData_admin['level'] = 'cluster';
                                    groupPermData_admin['permtype'] = 'admin';

                                    var perms_cluster_list = etva_permission_cluster_list_value.split(',');
                                    for(var i=0,len=perms_cluster_list.length; i<len;i++)
                                        perms_admin_list.push(parseInt(perms_cluster_list[i]));
                                    groupPermData_admin['etva_permission_list'] = Ext.encode(perms_admin_list);
                                    if( perms_admin_list.length > 0 ){
                                        this.call_save_sfGuardPermission(groupPermData_admin);
                                    }
                                }
                                //var etva_permission_server_list_value = this.etva_permission_server_list.getValue();
                                var etva_permission_server_list_value = data['etva_permission_server_list'];
                                if( etva_permission_server_list_value ){
                                    var groupPermData_op = new Object();
                                    var perms_op_list = [];

                                    groupPermData_op['group_id'] = group_id;
                                    groupPermData_op['level'] = 'server';
                                    groupPermData_op['permtype'] = 'op';

                                    var perms_server_list = etva_permission_server_list_value.split(',');
                                    for(var i=0,len=perms_server_list.length; i<len;i++)
                                        perms_op_list.push(parseInt(perms_server_list[i]));

                                    groupPermData_op['etva_permission_list'] = Ext.encode(perms_op_list);
                                    if( perms_op_list.length > 0 ){
                                        this.call_save_sfGuardPermission(groupPermData_op);
                                    }
                                }
                            }
                            ,call_save_sfGuardPermission: function(permsData){
                                var conn = new Ext.data.Connection({
                                    listeners:{
                                        // wait message.....
                                        beforerequest:function(){
                                            Ext.MessageBox.show({
                                                title: <?php echo json_encode(__('Please wait...')) ?>,
                                                msg: <?php echo json_encode(__('Saving...')) ?>,
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
                                    url: ' sfGuardPermission/jsonAddGroupPermissions',
                                    scope:this,
                                    params: permsData,
                                    success: function(resp,opt) {
                                        var response = Ext.util.JSON.decode(resp.responseText);                

                                        Ext.ux.Logger.info(response['agent'],response['response']);
                                    },
                                    failure: function(resp,opt) {

                                        var response = Ext.util.JSON.decode(resp.responseText);
                                        Ext.ux.Logger.error(response['agent'],response['error']);

                                        Ext.Msg.show({
                                            title: String.format(<?php echo json_encode(__('Error {0}')) ?>,response['agent']),
                                            buttons: Ext.MessageBox.OK,
                                            msg: response['info'],
                                            icon: Ext.MessageBox.ERROR});
                                    }
                                });//END Ajax request
                            }
                        });
    }

    this.saveBtn = new Ext.Button(
            {
                text: __('Save'),
                formBind:true,
                scope:this,
                handler: function(btn,ev) {

                    if (!this.getForm().isValid()) return false;

                    var allvals = this.getForm().getValues();
                    var permissions = [];
                    var to_numbers = [];

                    var record = new Object();
                    record.data = new Object();
                    record.data['id'] = allvals['id'];
                    record.data['name'] = allvals['name'];
                    record.data['description'] = allvals['description'];

                    if(allvals['sf_guard_group_permission_list'])
                        permissions = allvals['sf_guard_group_permission_list'].split(',');

                    for(var i=0,len=permissions.length; i<len;i++)
                        to_numbers.push(parseInt(permissions[i]));

                    record.data['sf_guard_group_permission_list'] = Ext.encode(to_numbers);

                    this.fireEvent('onSave',record,allvals);
       
                }
    });

    // field set
    var allFields = 
        {
                xtype:'fieldset',
                title: <?php echo json_encode(__('Group details')) ?>,
                items:[this.hidden_id, this.name, this.description, this.permissions]};

    Ext.apply(allFields,this.fieldsetConf);

    // define window and pop-up - render formPanel
    SfGuardGroup.CreateEditForm.superclass.constructor.call(this, {
        baseCls: 'x-plain',
        labelWidth: 90,        
        defaultType: 'textfield',
        monitorValid:true,       
        buttonAlign:'center',   
        width: '100%',
        items: [allFields],        
        buttons: [this.saveBtn]
    });    

};

Ext.extend(SfGuardGroup.CreateEditForm, Ext.form.FormPanel, {

    loadRecord : function(rec) {        
        this.saveBtn.setText(__('Update'));
        this.getForm().loadRecord(rec);
        Ext.getCmp('group_list-createedit-tab-permissions').group_id = rec.id;
        Ext.getCmp('group_list-createedit-tab-permissions').load();
    },
    clean : function(){
        this.getForm().reset();
        this.record = null;        
        this.saveBtn.setText(__('Save'));
    },
    reload:function(){        
        //this.permissions.store.reload();
    }
    ,focusForm:function(){
        this.name.focus();
    }
    ,onSaveNext: function(data,group){
        Ext.getCmp('group_list-createedit-tab-permissions').onSave(data,group);
    }

});

</script>
