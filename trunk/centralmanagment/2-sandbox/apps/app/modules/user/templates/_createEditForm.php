<?php if( isset($modulesConf['Primavera']) && ($modulesConf['Primavera']['main']['state']==1) ){
include_partial('primavera/Primavera_User_CreateEdit_Fieldset',array('modules'=>$modules,'modulesConf'=>$modulesConf));
}?>

<?php if( isset($modulesConf['ETMS']) && ($modulesConf['ETMS']['domain']['state']==1) ){
include_partial('etms/ETMS_User_CreateEdit_Fieldset',array('modules'=>$modules,'modulesConf'=>$modulesConf));
}?>

<?php if( isset($modulesConf['ETVOIP']) && ($modulesConf['ETVOIP']['pbx']['state']==1) ){ 
include_partial('etvoip/ETVOIP_User_CreateEdit_Fieldset',array('modules'=>$modules,'modulesConf'=>$modulesConf));
}?>

<script>
Ext.ns("User.List");

call_save_sfGuardUser_UpdateUserService =  function(userData){

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
        url: 'sfGuardUser/jsonUpdateUserService',
        scope:this,
        params: userData,
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
};

User.List.CreateEditForm = function(config) {

    Ext.apply(this,config);
    
    this.hidden_id = new Ext.form.Hidden({name: 'id'});
    
    this.firstname = new Ext.form.TextField({
        fieldLabel: <?php echo json_encode(__('First name')) ?>,
        allowBlank: false,
        name: 'firstName',        
        maxLength: 10	    
    });
    this.lastname = new Ext.form.TextField({
        fieldLabel: <?php echo json_encode(__('Last name')) ?>,
        allowBlank: false,
        name: 'lastName',
        maxLength: 50	    
    });
    this.username = new Ext.form.TextField({
        fieldLabel: <?php echo json_encode(__('Username')) ?>,
        name: 'username',
        maxLength: 50,
        allowBlank: false	    
    });
    this.email = new Ext.form.TextField({
        fieldLabel: 'Email',
        name: 'email',        
        vtype:'email',
        blankText: <?php echo json_encode(__('Please provide correct email address')) ?>,
        maxLength: 50	    
    });

    this.active = new Ext.form.ComboBox({
        selectOnFocus:true,
        editable: false,        
        mode: 'local',
        value:true,
        triggerAction: 'all',
        name:'isActive',
        hiddenName:'isActive',
    	fieldLabel: __('Active'),
 	    xtype:'combo', 	    
 	    allowBlank: false,
        store: new Ext.data.ArrayStore({
            fields: ['value','name'],
            data: [[true, __('Yes')], [false, __('No')]]
        }),
        valueField: 'value',
        displayField: 'name'
    });

    this.isSuperAdmin = new Ext.form.ComboBox({
    	selectOnFocus:true,
        editable: false,
        forceSelection:true,        
        mode: 'local',
        value:false,
        triggerAction: 'all',
        name:'isSuperAdmin',
        hiddenName:'isSuperAdmin',
    	fieldLabel: <?php echo json_encode(__('Super Admin'))?>,
 	    allowBlank: false,
        store: new Ext.data.ArrayStore({
            fields: ['value','name'],
            data: [[true, __('Yes')], [false, __('No')]]
        }),
        valueField: 'value',
        displayField: 'name'
    });
    
	this.password = new Ext.form.TextField({	    
        fieldLabel: 'Password',        
        inputType: 'password',
        name: 'password',
        ref:'password',
        minLength: 4        
    });
    this.confirmPassword = new Ext.form.TextField({        
        fieldLabel: <?php echo json_encode(__('Confirm New Password')) ?>,
        inputType: 'password',
        name: 'password_again',        
        validator:function(v){                                                
            if(!v) return true;
            
            if(v==this.ownerCt.password.getValue()) return true;
            else return <?php echo json_encode(__('Passwords do not match')) ?>;
        },
        minLength: 4        
    });


    var groups_store
            = new Ext.data.Store({
                    proxy: new Ext.data.HttpProxy({
                        url: <?php echo json_encode(url_for('sfGuardGroup/jsonList')); ?>,
                        method:'POST'}),
                        reader: new Ext.data.JsonReader({
                            root: 'data',
                            fields:['Id','Name']})
    });

    this.groups = new Ext.ux.Multiselect({
            fieldLabel: <?php echo json_encode(__('Groups')) ?>,
            valueField:"Id",
            displayField:"Name",
            labelStyle:'margin-top:15px;',
            style:{marginTop:'15px'},           
            name:'sf_guard_user_group_list',            
            allowBlank:true,
            store:groups_store});

    groups_store.load();


    this.saveBtn = new Ext.Button(
            {
                text: __('Save'),
                formBind:true,
                scope:this,
                handler: function(btn,ev) {

                    if (!this.getForm().isValid()) return false;

                    var allvals = this.getForm().getValues();

                    this.onSave(allvals);
                }
    });

    var tabPerms_clusters_store = new Ext.data.Store({
                                                                    //autoLoad: true,
                                                                    proxy: new Ext.data.HttpProxy({
                                                                        url: <?php echo json_encode(url_for('cluster/jsonList')); ?>,
                                                                        method:'POST'}),
                                                                    reader: new Ext.data.JsonReader({
                                                                        root: 'data',
                                                                        fields:['Id','Name']})
                                                    });
    var tabPerms_clusters = new Ext.ux.Multiselect({
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
    var tabPerms_servers = new Ext.ux.Multiselect({
                                                name:'etva_permission_server_list',
                                                fieldLabel: <?php echo json_encode(__('Servers')) ?>,
                                                valueField:"Id",
                                                displayField:"Name",
                                                allowBlank:true,
                                                store: tabPerms_servers_store
                                                });
    tabPerms_servers_store.load();
    // field set
    var allFields = 
        {
            id:'user_list-createedit-tab-userdetails',
            bodyStyle:'padding: 10px',
            autoScroll:true,
            title: <?php echo json_encode(__('User details')) ?>,
            items:[
                    this.hidden_id,
                    this.username,
                    this.firstname,
                    this.lastname, 
                    this.password, this.confirmPassword, this.email,                   
                    this.active,this.isSuperAdmin,
                    this.groups
                    ,{ fieldLabel: __('Permissions'),
                        id:'user_list-createedit-tab-permissions',
                        xtype: 'fieldset',
                        width: '100%',
                        border: false,
                        items: [
                            {
                                layout:'column',
                                border: false,
                                defaults:{ layout:'form', border: false, labelAlign: 'top' },
                                items: [
                                    { columnWidth: .5, items:[ tabPerms_clusters ] },
                                    { columnWidth: .5, items:[ tabPerms_servers ] }
                                ]
                            }
                        ]
                        ,loadRecord: function(data){
                                /*this.etva_permission_cluster_list.setDisabled(false);
                                this.etva_permission_cluster_list.setValue( data['user_cluster_permissions'] );
                                this.etva_permission_server_list.setDisabled(false);
                                this.etva_permission_server_list.setValue( data['user_server_permissions'] );*/
                                this.find('name','etva_permission_cluster_list')[0].setValue( data['user_cluster_permissions'] );
                                this.find('name','etva_permission_server_list')[0].setValue( data['user_server_permissions'] );
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
                                url: 'sfGuardPermission/jsonUserPermissions',
                                params: { 'user_id' : this.user_id },
                                scope:this,
                                success: function(resp,opt) {
                                    var response = Ext.decode(resp.responseText);

                                    this.loadRecord(response['data']);
                                }
                            });//END Ajax request
                        }
                        ,listeners: {
                            afterrender: function(c){
                                this.load();
                            }
                        }
                        ,onSave: function(data){
                            //var etva_permission_cluster_list_value = this.etva_permission_cluster_list.getValue();
                            var etva_permission_cluster_list_value = data['etva_permission_cluster_list'];
                            if( etva_permission_cluster_list_value ){
                                var userPermData_admin = new Object();
                                var perms_admin_list = [];

                                userPermData_admin['user_id'] = this.user_id;
                                userPermData_admin['level'] = 'cluster';
                                userPermData_admin['permtype'] = 'admin';

                                var perms_cluster_list = etva_permission_cluster_list_value.split(',');
                                for(var i=0,len=perms_cluster_list.length; i<len;i++)
                                    perms_admin_list.push(parseInt(perms_cluster_list[i]));
                                userPermData_admin['etva_permission_list'] = Ext.encode(perms_admin_list);
                                if( perms_admin_list.length > 0 ){
                                    this.call_save_sfGuardPermission(userPermData_admin);
                                }
                            }
                            //var etva_permission_server_list_value = this.etva_permission_server_list.getValue();
                            var etva_permission_server_list_value = data['etva_permission_server_list'];
                            if( etva_permission_server_list_value ){
                                var userPermData_op = new Object();
                                var perms_op_list = [];

                                userPermData_op['user_id'] = this.user_id;
                                userPermData_op['level'] = 'server';
                                userPermData_op['permtype'] = 'op';

                                var perms_server_list = etva_permission_server_list_value.split(',');
                                for(var i=0,len=perms_server_list.length; i<len;i++)
                                    perms_op_list.push(parseInt(perms_server_list[i]));

                                userPermData_op['etva_permission_list'] = Ext.encode(perms_op_list);
                                if( perms_op_list.length > 0 ){
                                    this.call_save_sfGuardPermission(userPermData_op);
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
                                url: ' sfGuardPermission/jsonAddUserPermissions',
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
                    }
                ],
            loadRecord: function(data){
                this.find('name','id')[0].setValue( data['Id'] );
                this.find('name','username')[0].setValue( data['Username'] );
                this.find('name','firstName')[0].setValue( data['FirstName'] );
                this.find('name','lastName')[0].setValue( data['LastName'] );
                this.find('name','email')[0].setValue( data['Email'] );
                this.find('name','isActive')[0].setValue( data['IsActive'] );
                this.find('name','isSuperAdmin')[0].setValue( data['IsSuperAdmin'] );
                this.find('name','sf_guard_user_group_list')[0].setValue( data['sf_guard_user_group_list'] );
            },
            load: function(data){
                this.loadRecord(data);
            }
    };

    Ext.apply(allFields,this.fieldsetConf);
        

    User.List.CreateEditForm.superclass.constructor.call(this, {
        baseCls: 'x-plain',
        labelWidth: 90,
        defaultType: 'textfield',
        monitorValid:true,
        buttonAlign:'center',
        border:false,                
        items: [
                {xtype:'tabpanel',
                    id: 'user-list-createedit-tabpanel',
                    activeItem:0,
                    enableTabScroll: true,
                    anchor: '100% 100%',
                    defaults:{
                        layout:'form'
                        ,labelWidth:140
                    },
                    items: [
                        allFields
                        <?php if( isset($modulesConf['ETVOIP']) && ($modulesConf['ETVOIP']['pbx']['state']==1) ){ ?>
                        ,ETVOIP.User.CreateEdit.Fieldset({ title: <?php echo json_encode(__('ETVoIP')) ?>})
                        <?php }?>
                        <?php if( isset($modulesConf['ETMS']) && ($modulesConf['ETMS']['domain']['state']==1) ){ ?>
                        ,ETMS.User.CreateEdit.Fieldset({ title: <?php echo json_encode(__('ETMailServer')) ?>})
                        <?php }?>
                        <?php if( isset($modulesConf['Primavera']) && ($modulesConf['Primavera']['main']['state']==1) ){ ?>
                        ,Primavera.User.CreateEdit.Fieldset({ title: <?php echo json_encode(__('Primavera')) ?>})
                        <?php }?>
                    ]
                }
        ],
        reader: new Ext.data.JsonReader({
            root:'data',
            id:'Id',
            fields: [{name:'id',mapping:'Id'},
                     {name:'username',mapping:'Username'},
                     {name:'firstName',mapping:'FirstName'},
                     {name:'lastName',mapping:'LastName'},
                     {name:'email',mapping:'Email'},
                     {name:'isSuperAdmin',mapping:'IsSuperAdmin'},
                     {name:'isActive',mapping:'IsActive'},
                     'sf_guard_user_group_list',
                     'sf_guard_user_permission_list']
        }),
        buttons: [this.saveBtn,
                    {
                       text:__('Cancel'),
                       scope:this,
                       handler:function(){(this.ownerCt).close()}
                    }]
    });   
};

Ext.extend(User.List.CreateEditForm, Ext.form.FormPanel, {
    clean : function(){        
        this.getForm().reset();        
        this.password.allowBlank = false;
        this.confirmPassword.allowBlank = false;
        this.saveBtn.setText(__('Save'));
    }
    ,load : function(id) {
        
        //Ext.getCmp('user_list-createedit-tab-userdetails').load({'id': id});

        /*this.form.load(
                {url:'sfGuardUser/jsonGridInfo',
                 params:{id : id},
                 scope:this,
                 waitMsg: <?php echo json_encode(__('Retrieving data...')) ?>
                 ,success:function(f,a){
                        console.log(f);
                        console.log(a);
                     this.password.allowBlank = true;
                     this.confirmPassword.allowBlank = true;
                     this.saveBtn.setText(__('Update'));}
                });*/

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
            url: 'sfGuardUser/jsonGridInfo',
            params: { 'id': id },
            scope:this,
            success: function(resp,opt) {

                var response = Ext.decode(resp.responseText);

                Ext.getCmp('user_list-createedit-tab-userdetails').load(response['data']);

                <?php if( isset($modulesConf['ETVOIP']) && ($modulesConf['ETVOIP']['pbx']['state']==1) ){ ?>
                Ext.getCmp('user_list-createedit-tab-etvoip').load(response['data']);
                <?php }?>
                <?php if( isset($modulesConf['ETMS']) && ($modulesConf['ETMS']['domain']['state']==1) ){ ?>
                Ext.getCmp('user_list-createedit-tab-etms').load(response['data']);
                <?php }?>
                <?php if( isset($modulesConf['Primavera']) && ($modulesConf['Primavera']['main']['state']==1) ){ ?>
                Ext.getCmp('user_list-createedit-tab-primavera').load(response['data']);
                <?php }?>

                this.password.allowBlank = true;
                this.confirmPassword.allowBlank = true;
                this.saveBtn.setText(__('Update'));
            }
        });//END Ajax request

        //Ext.getCmp('user_list-createedit-tab-permissions').load({'user_id': id});
        Ext.getCmp('user_list-createedit-tab-permissions').user_id = id;
    }
    ,focusForm:function(){
        this.username.focus();
    }
    ,reload:function(){        
        this.groups.store.reload();
    }
    ,onSave:function(allvals){
        //console.log(allvals);

        var groups = [];
        var groups_to_numbers = [];

        var record = new Object();
        record.data = new Object();
        record.data['id'] = this.find('name','id')[0].getValue();
        record.data['username'] = this.find('name','username')[0].getValue();
        if(this.find('name','password')[0].getValue())
            record.data['password'] = this.find('name','password')[0].getValue();
        if(this.find('name','password_again')[0].getValue())
            record.data['password_again'] = this.find('name','password_again')[0].getValue();
        record.data['first_name'] = this.find('name','firstName')[0].getValue();
        record.data['last_name'] = this.find('name','lastName')[0].getValue();
        record.data['email'] = this.find('name','email')[0].getValue();
        record.data['is_active'] = this.find('name','isActive')[0].getValue();
        record.data['is_super_admin'] = this.find('name','isSuperAdmin')[0].getValue();

        if(this.find('name','sf_guard_user_group_list')[0].getValue())
            groups = this.find('name','sf_guard_user_group_list')[0].getValue().split(',');

        for(var i=0,len=groups.length; i<len;i++)
            groups_to_numbers.push(parseInt(groups[i]));

        record.data['sf_guard_user_group_list'] = Ext.encode(groups_to_numbers);

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
            url: 'user/jsonUpdate',
            scope:this,
            params: record.data,
            success: function(resp,opt) {
                Ext.getCmp('user_list-createedit-tab-permissions').onSave(allvals);

                <?php if( isset($modulesConf['ETVOIP']) && ($modulesConf['ETVOIP']['pbx']['state']==1) ){ ?>
                Ext.getCmp('user_list-createedit-tab-etvoip').onSave(allvals);
                <?php }?>
                <?php if( isset($modulesConf['ETMS']) && ($modulesConf['ETMS']['domain']['state']==1) ){ ?>
                Ext.getCmp('user_list-createedit-tab-etms').onSave(allvals);
                <?php }?>
                <?php if( isset($modulesConf['Primavera']) && ($modulesConf['Primavera']['main']['state']==1) ){ ?>
                Ext.getCmp('user_list-createedit-tab-primavera').onSave(allvals);
                <?php }?>

                (this.ownerCt).fireEvent('onSave');
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

User.List.CreateEditForm.Window = function(config) {

    Ext.apply(this,config);

    User.List.CreateEditForm.Window.superclass.constructor.call(this, {
        width: 420
        ,height:620
        ,border:false
        ,modal:true
        ,layout:'fit'
        ,items:[ new User.List.CreateEditForm({fieldsetConf:{defaults:{width:145}}})]
    });
};


Ext.extend(User.List.CreateEditForm.Window, Ext.Window,{
    loadData:function(data){
        this.items.get(0).load(data);
    }
});

</script>
