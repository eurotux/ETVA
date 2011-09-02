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
                name:'sf_guard_group_permission_list',                
                allowBlank:true,
                store:permissions_store});            

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

                    this.fireEvent('onSave',record);                                        
       
                }
    });

    // field set
    var allFields = 
        {
                xtype:'fieldset',
                title: <?php echo json_encode(__('Group details')) ?>,
                defaults:{width:160},
                items:[this.hidden_id, this.name, this.description, this.permissions]};

    Ext.apply(allFields,this.fieldsetConf);

    // define window and pop-up - render formPanel
    SfGuardGroup.CreateEditForm.superclass.constructor.call(this, {
        baseCls: 'x-plain',
        labelWidth: 90,        
        defaultType: 'textfield',
        monitorValid:true,       
        buttonAlign:'center',   
        items: [allFields],        
        buttons: [this.saveBtn]
    });    

};

Ext.extend(SfGuardGroup.CreateEditForm, Ext.form.FormPanel, {

    loadRecord : function(rec) {        
        this.saveBtn.setText(__('Update'));
        this.getForm().loadRecord(rec);
    },
    clean : function(){
        this.getForm().reset();
        this.record = null;        
        this.saveBtn.setText(__('Save'));
    },
    reload:function(){        
        this.permissions.store.reload();
    }
    ,focusForm:function(){
        this.name.focus();
    }

});

</script>