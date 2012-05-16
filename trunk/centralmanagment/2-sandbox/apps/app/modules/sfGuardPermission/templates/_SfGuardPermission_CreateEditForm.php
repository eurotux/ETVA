<script>

Ext.ns("SfGuardPermission");

function dump(arr,level) {
    var dumped_text = "";
    if(!level) level = 0;

    //The padding given at the beginning of the line.
    var level_padding = "";
    for(var j=0;j<level+1;j++) level_padding += "    ";

    if(typeof(arr) == 'object') { //Array/Hashes/Objects
            for(var item in arr) {
                    var value = arr[item];

                    if(typeof(value) == 'object') { //If it is an array,
                            dumped_text += level_padding + "'" + item + "' ...\n";
                            dumped_text += dump(value,level+1);
                    } else {
                            dumped_text += level_padding + "'" + item + "' => \"" + value + "\"\n";
                    }
            }
    } else { //Stings/Chars/Numbers etc.
            dumped_text = "===>"+arr+"<===("+typeof(arr)+")";
    }
    return dumped_text;
}

SfGuardPermission.CreateEditForm = function(config) {

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

    this.sfGuardPerm_id = new Ext.form.Hidden({
        name: 'permission_id'
        ,allowBlank: false
    });

    this.perm_type = new Ext.form.RadioGroup({
        xtype: 'radiogroup',
        columns: 1,
        fieldLabel: <?php echo json_encode(__('Type')) ?>,
        allowBlank: false,
        name: 'perm_type',
        items: [{
            name: 'perm_type',
            boxLabel: 'Administrator',
            inputValue: 'admin'
        },{
            name: 'perm_type', //nota: o mesmo nome em todos os radio buttons
            boxLabel: 'Operator',
            inputValue: 'op'
        }]
    });

    // Data Center
    var dcenter_store
        = new Ext.data.Store({
                    proxy: new Ext.data.HttpProxy({
                        url: <?php echo json_encode(url_for('cluster/jsonList')); ?>,
                        method:'POST'}),
                        reader: new Ext.data.JsonReader({
                            root: 'data',
                            fields:['Id','Name']})
    });

    this.dcenter = new Ext.ux.Multiselect({
                fieldLabel: <?php echo json_encode(__('Clusters')) ?>,
                valueField:"Id",
                displayField:"Name",
                height:100,
                name:'etva_permission_cluster_list',
                allowBlank:true,
                store:dcenter_store});

    // Groups
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
                height:100,
                name:'etva_permission_group_list',     
                allowBlank:true,
                store:groups_store});

    // Virtual Machines
    var vm_store
            = new Ext.data.Store({
                    proxy: new Ext.data.HttpProxy({
                        url: <?php echo json_encode(url_for('server/jsonListAll')); ?>,
                        method:'POST'}),
                        reader: new Ext.data.JsonReader({
                            root: 'data',
                            fields:['Id','Name']})
                    ,baseParams: { limit: 0, 'sort': 'name', 'dir': 'ASC' }
    });

    this.vms = new Ext.ux.Multiselect({
                fieldLabel: <?php echo json_encode(__('Servers')) ?>,
                valueField:"Id",
                displayField:"Name",
                height:100,
                name:'etva_permission_server_list',
                allowBlank:true,
                store:vm_store});

    // Users
    var user_store
            = new Ext.data.Store({
                    proxy: new Ext.data.HttpProxy({
                        url: <?php echo json_encode(url_for('sfGuardUser/JsonList')); ?>,
                        method:'POST'}),
                        reader: new Ext.data.JsonReader({
                            root: 'data',
                            fields:['Id','Username']})
    });

    this.users = new Ext.ux.Multiselect({
                fieldLabel: <?php echo json_encode(__('Users')) ?>,
                valueField:"Id",
                displayField:"Username",
                height:100,
                name:'etva_permission_user_list',
                allowBlank:true,
                store:user_store});

    this.saveBtn = new Ext.Button(
            {
                text: __('Save'),
                formBind:true,
                scope:this,
                handler: function(btn,ev) {
                    
                    if (!this.getForm().isValid()) return false;
                    
                    var allvals = this.getForm().getValues();
                    var groups = [];
                    var clusters = [];
                    var users = [];
                    var vms = [];
                    var to_numbers = [];
                    var vms_numbers = [];
                    var clusters_numbers = [];
                    var users_numbers = [];
                    
                    var record = new Object();
                    record.data = new Object();                                            
                    record.data['id'] = allvals['id'];
                    record.data['name'] = allvals['name'];
                    record.data['description'] = allvals['description'];
                    record.data['perm_type'] = allvals['perm_type'];
                    record.data['permission_id'] = allvals['permission_id'];


                    if(allvals['etva_permission_group_list'])
                        groups = allvals['etva_permission_group_list'].split(',');

                    if(allvals['etva_permission_server_list'])
                        vms = allvals['etva_permission_server_list'].split(',');

                    if(allvals['etva_permission_cluster_list'])
                        clusters = allvals['etva_permission_cluster_list'].split(',');
                    
                    if(allvals['etva_permission_user_list'])
                        users = allvals['etva_permission_user_list'].split(',');

                    for(var i=0,len=groups.length; i<len;i++)
                        to_numbers.push(parseInt(groups[i]));

                    for(var i=0,len=vms.length; i<len;i++)
                        vms_numbers.push(parseInt(vms[i]));

                    for(var i=0,len=clusters.length; i<len;i++)
                        clusters_numbers.push(parseInt(clusters[i]));

                    for(var i=0,len=users.length; i<len;i++)
                        users_numbers.push(parseInt(users[i]));

                    record.data['etva_permission_group_list'] = Ext.encode(to_numbers);
                    record.data['etva_permission_server_list'] = Ext.encode(vms_numbers);
                    record.data['etva_permission_cluster_list'] = Ext.encode(clusters_numbers);
                    record.data['etva_permission_user_list'] = Ext.encode(users_numbers);

//                    alert(dump(record));

                    this.fireEvent('onSave',record);
                }
    });

    // field set
    var allFields = {
                xtype:'fieldset',
                title: <?php echo json_encode(__('Permission details')) ?>,
                defaults:{width:160},
                items:[this.hidden_id,this.name, this.description, this.perm_type, this.sfGuardPerm_id, this.groups, this.dcenter, this.vms, this.users]};

    Ext.apply(allFields,this.fieldsetConf);

    // define window and pop-up - render formPanel
    SfGuardPermission.CreateEditForm.superclass.constructor.call(this, {
        baseCls: 'x-plain',
        labelWidth: 90,        
        defaultType: 'textfield',
        monitorValid:true,       
        buttonAlign:'center',        
        items: [allFields],        
        buttons: [this.saveBtn]
    });    

};

Ext.extend(SfGuardPermission.CreateEditForm, Ext.form.FormPanel, {

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
        this.groups.store.reload();
        this.dcenter.store.reload();
        this.vms.store.reload();
        this.users.store.reload();
    }
    ,focusForm:function(){
        this.name.focus();
    }

});

</script>
