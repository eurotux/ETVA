<script>
Ext.ns("SfGuardUser");

SfGuardUser.CreateEditForm = function(config) {

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


    var permissions_store
            = new Ext.data.Store({
                    proxy: new Ext.data.HttpProxy({
                        url: <?php echo json_encode(url_for('sfGuardPermission/jsonList')); ?>,
                        method:'POST'}),
                        reader: new Ext.data.JsonReader({
                            root: 'data',
                            fields:['Id','Name']})
    });

    this.permissions = new Ext.ux.Multiselect({
            fieldLabel: <?php echo json_encode(__('Permissions')) ?>,
            valueField:"Id",
            displayField:"Name",
            height:100,           
            name:'sf_guard_user_permission_list',            
            allowBlank:true,
            store:permissions_store});

    //permissions_store.load();


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
            height:100,
            name:'sf_guard_user_group_list',            
            allowBlank:true,
            store:groups_store});

    //groups_store.load();


    this.saveBtn = new Ext.Button(
            {
                text: __('Save'),
                formBind:true,
                scope:this,
                handler: function(btn,ev) {

                    if (!this.getForm().isValid()) return false;

                    var allvals = this.getForm().getValues();
                    var permissions = [];
                    var permissions_to_numbers = [];
                    var groups = [];
                    var groups_to_numbers = [];

                    var record = new Object();
                    record.data = new Object();
                    record.data['id'] = allvals['id'];
                    record.data['username'] = allvals['username'];
                    if(allvals['password'])
                        record.data['password'] = allvals['password'];
                    if(allvals['password_again'])
                    record.data['password_again'] = allvals['password_again'];
                    record.data['first_name'] = allvals['firstName'];
                    record.data['last_name'] = allvals['lastName'];
                    record.data['email'] = allvals['email'];
                    record.data['is_active'] = allvals['isActive'];
                    record.data['is_super_admin'] = allvals['isSuperAdmin'];

                    

                    if(allvals['sf_guard_user_permission_list'])
                        permissions = allvals['sf_guard_user_permission_list'].split(',');

                    if(allvals['sf_guard_user_group_list'])
                        groups = allvals['sf_guard_user_group_list'].split(',');

                    for(var i=0,len=permissions.length; i<len;i++)
                        permissions_to_numbers.push(parseInt(permissions[i]));

                    for(var i=0,len=groups.length; i<len;i++)
                        groups_to_numbers.push(parseInt(groups[i]));

                    record.data['sf_guard_user_permission_list'] = Ext.encode(permissions_to_numbers);
                    record.data['sf_guard_user_group_list'] = Ext.encode(groups_to_numbers);

                    this.fireEvent('onSave',record);
                }
    });

    // field set
    var allFields = 
        {
            xtype:'fieldset',
            title: <?php echo json_encode(__('User details')) ?>,
            items:[
                    this.hidden_id,
                    this.username,
                    this.firstname,
                    this.lastname, 
                    this.password, this.confirmPassword, this.email,                   
                    this.active,this.isSuperAdmin,
                    this.groups,
                    this.permissions
                ]};
    Ext.apply(allFields,this.fieldsetConf);
        

    SfGuardUser.CreateEditForm.superclass.constructor.call(this, {
        baseCls: 'x-plain',
        labelWidth: 90,
        defaultType: 'textfield',
        monitorValid:true,
        buttonAlign:'center',
        border:false,                
        items: [allFields],
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
        buttons: [this.saveBtn]
    });   
};

Ext.extend(SfGuardUser.CreateEditForm, Ext.form.FormPanel, {
    clean : function(){        
        this.getForm().reset();        
        this.password.allowBlank = false;
        this.confirmPassword.allowBlank = false;
        this.saveBtn.setText(__('Save'));
    }
    ,load : function(id) {
        
        this.form.load(
                {url:'sfGuardUser/jsonGridInfo',
                 params:{id : id},
                 scope:this,
                 waitMsg: <?php echo json_encode(__('Retrieving data...')) ?>
                 ,success:function(f,a){
                     this.password.allowBlank = true;
                     this.confirmPassword.allowBlank = true;
                     this.saveBtn.setText(__('Update'));}
                });


    }
    ,focusForm:function(){
        this.username.focus();
    }
    ,reload:function(){        
        this.permissions.store.reload();
        this.groups.store.reload();
    }
});

</script>