<script>

 Server_wizard = function(config) {

    Ext.QuickTips.init();    

    // Add the additional 'advanced' VTypes
    // checks memory cannot exceed total memory size
    Ext.apply(Ext.form.VTypes, {

        vm_memory_size : function(val, field) {
            if (field.totalmemsize) {
                var tsize = Ext.getCmp(field.totalmemsize);                                
                return (val > 0 && val <= parseFloat(tsize.getValue()));
            }

            return true;
        },
        vm_memory_sizeText : <?php echo json_encode(__('Cannot exceed total allocatable memory size')) ?>,
        vm_lv_newsize : function(val, field) {
            if (field.totallvsize) {
                var tsize = Ext.getCmp(field.totallvsize);
                return (val <= parseFloat(tsize.getValue()));
            }

            return true;
        },
        vm_lv_newsizeText : <?php echo json_encode(__('Cannot exceed total volume group size')) ?>,
        vm_name : function(val, field){
            var t = /^[a-zA-Z][a-zA-Z0-9\-\_]+$/;
            return t.test(val);
        },
        vm_nameText : <?php echo json_encode(__('No spaces and only alpha-numeric characters allowed!')) ?>,
    });


    /**
     *
     * xen name panel class
     *
     */

    xen_name_cardPanel = Ext.extend(Ext.ux.Wiz.Card, {

        initComponent: function(){

            var name_tpl = [
                        {xtype:'hidden',name:'node_id',value:config.nodeId},
                        {xtype:'hidden',name:'vm_type',ref:'vm_type'},
                        {xtype:'textfield',
                            name       : 'vm_name',
                            fieldLabel : <?php echo json_encode(__('Virtual server name')) ?>,
                            emptyText : __('Name...'),
                            vtype : 'vm_name',
                            allowBlank : false,
                            listeners:{
                                specialkey:{scope:this,fn:function(field,e){

                                    if(e.getKey()==e.ENTER)
                                        if(!this.wizard.nextButton.disabled) this.wizard.onNextClick();
                                    
                                }}
                            }
                        },
                        {cls:'fieldset-top-sp',xtype:'fieldset',
                            title: <?php echo json_encode(__('Guest operating system')) ?>,
                            collapsible: false,
                            autoHeight:true,
                            labelWidth:10,
                            items:[

                                {
                                    checked:true,
                                    xtype:'radio',
                                    hideLabel:true,
                                    boxLabel: 'Linux PV',
                                    name: 'vm_OS',
                                    inputValue: 'Linux PV'
                                },
                                {
                                    layout:'table',
                                    border:false,
                                    bodyStyle:'background:transparent;',
                                    layoutConfig: {columns: 2},
                                    defaults:{layout:'form',border:false,bodyStyle:'background:transparent;'},
                                    items: [
                                        {items:[{
                                            //height:40,
                                            hideLabel:true,
                                            xtype:'radio',
                                            boxLabel: 'Linux HVM',
                                            disabled:this.hvm ? false : true,
                                            name: 'vm_OS',
                                            inputValue: 'Linux HVM'
                                        }]},
                                        {items:[
                                            {xtype:'displayfield',width:40,helpText: <?php echo json_encode(__('Enabled only if this machine supports Hardware Virtual Machine')) ?>}
                                        ]}
                                    ]
                                }
                              ,{
                                height:40,
                                xtype:'radio',
                                hidden:this.hvm ? false : true,
                                hideLabel:true,
                                boxLabel: 'Windows',
                                name: 'vm_OS',
                                inputValue: 'Windows'
                               }
                            ]//end fieldset items
                        }];

            Ext.apply(this, {
                title        : <?php echo json_encode(__('Virtual server name')) ?>,
                monitorValid : true,
                defaults     : {
                    labelStyle : 'font-size:11px;width:140px;'
                },
                items : [{
                            border    : false,
                            bodyStyle : 'background:none;padding-bottom:30px;',
                            html      : <?php echo json_encode(__('Please enter a name and choose operating system.')) ?>
                        },name_tpl
                    ]
               ,listeners:{
                    show:function(){                        
                        this.form.findField('vm_name').focus();
                    }
                    ,nextclick:function(card){

                                var wizData = wizard.getWizardData();
                                var cardData = wizData[card.getId()];
                                var cp = wizard.cardPanel;
                                var startupPanel = cp.get('server-wiz-startup');
                                                                
                                switch(cardData['vm_OS']){
                                    case 'Linux PV' :
                                                        this.vm_type.setValue('pv');
                                                        startupPanel.setLocation();
                                                        break;
                                            default :
                                                        this.vm_type.setValue('hvm');
                                                        startupPanel.setBoot();
                                                        break;
                                }

                                //set network cards grid based on vm type
                                var serverwiz_hostnetwork = this.wizard.cardPanel.serverwiz_hostnetwork;
                                serverwiz_hostnetwork.loadRecord({'vm_type':this.vm_type.getValue()});

                                //set disk type options based on vm type
                                var serverwiz_storage = this.wizard.cardPanel.serverwiz_storage;
                                serverwiz_storage.loadRecord({'vm_type':this.vm_type.getValue()});

                    }
                }
            });

            xen_name_cardPanel.superclass.initComponent.call(this);
            Ext.Component.superclass.constructor.call(this);
        }        

    });

    //xen name cardPanel


    /**
     *
     * kvm name panel class
     *
     */

    kvm_name_cardPanel = Ext.extend(Ext.ux.Wiz.Card, {

        initComponent: function(){

            var name_tpl = [
                        {xtype:'hidden',name:'node_id',value:config.nodeId},
                        {xtype:'hidden',name:'vm_type',ref:'vm_type'},
                        {xtype:'textfield',
                            name       : 'vm_name',
                            fieldLabel : <?php echo json_encode(__('Virtual server name')) ?>,
                            emptyText : __('Name...'),
                            vtype : 'vm_name',
                            allowBlank : false,
                            listeners:{
                                specialkey:{scope:this,fn:function(field,e){

                                    if(e.getKey()==e.ENTER)
                                        if(!this.wizard.nextButton.disabled) this.wizard.onNextClick();

                                }}
                            }
                        },
                        {cls:'fieldset-top-sp',xtype:'fieldset',
                            title: <?php echo json_encode(__('Guest operating system')) ?>,
                            collapsible: false,
                            autoHeight:true,
                            defaultType:'radio',
                            labelWidth:10,
                            items:[
                                  {
                                    checked:true,
                                    hideLabel:true,
                                    boxLabel: 'Linux',
                                    name: 'vm_OS',
                                    inputValue: 'Linux'
                                  }
                                  ,{
                                    height:40,
                                    hideLabel:true,
                                    boxLabel: 'Windows',
                                    name: 'vm_OS',
                                    inputValue: 'Windows'
                                   }
                            ]//end fieldset items
                        }];

            Ext.apply(this, {
                title        : <?php echo json_encode(__('Virtual server name')) ?>,
                monitorValid : true,
                defaults     : {
                    labelStyle : 'font-size:11px;width:140px;'
                },
                items : [{
                            border    : false,
                            bodyStyle : 'background:none;padding-bottom:30px;',
                            html      : <?php echo json_encode(__('Please enter a name and choose operating system.')) ?>
                        },name_tpl
                    ]
                ,listeners:{
                    show:function(){
                        this.form.findField('vm_name').focus();
                    }
                    ,nextclick:function(card){

                                var wizData = wizard.getWizardData();
                                var cardData = wizData[card.getId()];
                                var cp = wizard.cardPanel;
                                var startupPanel = cp.get('server-wiz-startup');
                                startupPanel.setBoot();

                                this.vm_type.setValue('kvm');

                                //set network cards grid based on vm type
                                var serverwiz_hostnetwork = this.wizard.cardPanel.serverwiz_hostnetwork;
                                serverwiz_hostnetwork.loadRecord({'vm_type':this.vm_type.getValue()});

                                //set disk type options based on vm type
                                var serverwiz_storage = this.wizard.cardPanel.serverwiz_storage;
                                serverwiz_storage.loadRecord({'vm_type':this.vm_type.getValue()});
                    }
                }
            });

            kvm_name_cardPanel.superclass.initComponent.call(this);
        }

    });
    //kvm name cardPanel


    mem_cardPanel = Ext.extend(Ext.ux.Wiz.Card, {

        initComponent: function(){

            Ext.apply(this, {

                title        : <?php echo json_encode(__('Memory')) ?>,
                monitorValid : true,
                defaults     : {
                    labelStyle : 'font-size:11px;width:150px;'
                },
                items : [{
                        border    : false,
                        bodyStyle : 'background:none;padding-bottom:30px;',
                        html      : <?php echo json_encode(__('Specify the amount of memory to allocate to this machine. It should be a multiple of 4 MB.')) ?>
                        },
                        {
                            xtype:'numberfield',
                            name       : 'vm_maxmemory',
                            id         : 'vm_maxmemory',
                            fieldLabel : <?php echo json_encode(__('Max allocatable memory (MB)')) ?>,
                            allowBlank : false,
                            readOnly:true
                        },
                        {
                            xtype:'numberfield',
                            name       : 'vm_freememory',
                            id         : 'vm_freememory',
                            fieldLabel : <?php echo json_encode(__('Free memory (MB)')) ?>,
                            allowBlank : false,
                            readOnly:true
                        },
                        {
                            xtype:'numberfield',
                            name       : 'vm_memory',
                            fieldLabel : <?php echo json_encode(__('Memory size (MB)')) ?>,
                            allowBlank : false,
                            vtype: 'vm_memory_size',
                            totalmemsize: 'vm_maxmemory'
                            ,listeners:{
                                specialkey:{scope:this,fn:function(field,e){

                                    if(e.getKey()==e.ENTER)
                                        if(!this.wizard.nextButton.disabled) this.wizard.onNextClick();

                                }}
                            }

                        }
                ]
                ,listeners:{
                    show:function(){
                        this.form.findField('vm_memory').focus();
                    }

                }
            });

            mem_cardPanel.superclass.initComponent.call(this);
        },
        loadRecord:function(data){

            if(data['vm_maxmemory'])
                data['vm_maxmemory'] = byte_to_MBconvert(data['vm_maxmemory'],0,'floor');

            if(data['vm_freememory'])
                data['vm_freememory'] = byte_to_MBconvert(data['vm_freememory'],0,'floor');

            var form = this.getForm();
            var rec = new Object();
            rec.data = data;
            form.loadRecord(rec);
        }

    });


    cpu_cardPanel = Ext.extend(Ext.ux.Wiz.Card, {

        initComponent: function(){

            Ext.apply(this, {

                title        : <?php echo json_encode(__('Processor')) ?>,
                monitorValid : true,
                defaults     : {
                    labelStyle : 'font-size:11px;',
                    bodyStyle: 'padding:10px;'
                },
                items : [{
                        border    : false,
                        bodyStyle : 'background:none;padding-bottom:30px;',
                        html      : <?php echo json_encode(__('Specify the amount of processors to use with this machine.')) ?>
                        },
                        {
                            name       : 'vm_realcpu',
                            ref         : 'vm_realcpu',
                            xtype      : 'numberfield',
                            fieldLabel : <?php echo json_encode(__('Total usable CPU')) ?>,
                            width:50,
                            allowBlank : false,
                            readOnly:true
                        }
                        ,{
                            xtype: 'numberfield',
                            allowBlank : false,
                            fieldLabel: <?php echo json_encode(__('CPUs to use')) ?>,
                            name:"vm_cpu",
                            allowNegative:false,
                            validator:function(v){
                                
                                var max_cpu = (this.ownerCt).vm_realcpu.getValue();
                                if(v > max_cpu) return <?php echo json_encode(__('Cannot exceed max cpu')) ?>;
                                else return true;
                            },
                            scope:this,
                            width: 50
                            ,listeners:{
                                specialkey:{scope:this,fn:function(field,e){

                                    if(e.getKey()==e.ENTER)
                                        if(!this.wizard.nextButton.disabled) this.wizard.onNextClick();

                                }}
                            }
                        }
                ]
                ,listeners:{
                    show:function(){
                        this.form.findField('vm_cpu').focus();
                    }

                }
            });

            cpu_cardPanel.superclass.initComponent.call(this);
        },
        loadRecord:function(data){

            var form = this.getForm();
            var rec = new Object();
            rec.data = data;
            form.loadRecord(rec);
        }

    });



    storage_cardPanel = Ext.extend(Ext.ux.Wiz.Card, {

        initComponent: function(){

            var lvstore = new Ext.data.JsonStore({
                id:'id'
                ,root:'data'
                ,totalProperty:'total'
                ,fields:[
                    {name:'id', type:'string'}
                    ,{name:'lv', type:'string'}
                    ,{name:'size', type:'string'}
                ]
                ,url:<?php echo json_encode(url_for('logicalvol/jsonGetAvailable'))?>
                ,baseParams:{'nid':config.nodeId}

            });

            var lvcombo = new Ext.form.ComboBox({
                //editable:false
                typeAhead: true
                ,selectOnFocus: true
                ,valueField:'id'
                ,hiddenName:'vm_lv'
                ,displayField:'lv'
                ,triggerAction:'all'
                ,forceSelection:true
                ,enableKeyEvents:true
                ,resizable:true
                ,minChars:1
                ,minListWidth:250
                ,width:200
                //,maxHeight:150
                ,allowBlank:false
                ,store:lvstore
                // concatenate vgname and size (MB)
                ,tpl:'<tpl for="."><div class="x-combo-list-item">{lv} ({[byte_to_MBconvert(values.size,2,"floor")]} MB)</div></tpl>'
                // listeners
                ,listeners:{
                    // sets raw value to concatenated last and first names
                    select:{scope:this, fn:function(combo, record, index) {
                        var size = byte_to_MBconvert(record.get('size'),2,'floor');
                        var lvsize_field = this.getForm().findField('vm_lvsize');
                        
                        combo.setRawValue(record.get('lv') + ' (' + size + ' MB)');
                        lvsize_field.setValue(size);

                    }}
                    // repair raw value after blur
//                    ,blur:function() {
//                        var val = this.getRawValue();
//                        this.setRawValue.defer(1, this, [val]);
//                    }
                    // set tooltip and validate
                    ,render:function() {
                        this.el.set({qtip: <?php echo json_encode(__('Choose logical volume')) ?>});
                        this.validate();
                    }
                    // requery if field is cleared by typing
//                    ,keypress:{buffer:100, fn:function() {
//                        if(!this.getRawValue()) {
//                            this.doQuery('', true);
//                        }
//                    }}
                }
                ,fieldLabel:'Logical volume'             
                ,bbar:new Ext.ux.grid.TotalCountBar({
                    store:lvstore
                    ,displayInfo:true
                })
            });// end lvcombo

            var vgstore = new Ext.data.JsonStore({
                id:'name'
                ,root:'data'
                ,totalProperty:'total'
                ,fields:[
                    {name:'name', type:'string'}
                    ,{name:'size', mapping:'value', type:'string'}
                ]
                ,url:<?php echo json_encode(url_for('volgroup/jsonListFree'))?>
                ,baseParams:{'nid':config.nodeId,'nodisk':true}
                ,listeners:{
                    'loadexception':function(store,options,resp,error){

                        if(resp.status==401) return;

                        var response = Ext.util.JSON.decode(resp.responseText);

                        Ext.getCmp('vm_device_set_exist').expand();
                        Ext.getCmp('vm_device_set_new').hide();

                        Ext.Msg.show({
                            title: <?php echo json_encode(__('Error!')) ?>,
                            buttons: Ext.MessageBox.OK,
                            msg: response['error'],
                            icon: Ext.MessageBox.ERROR});
                    }
                }

            });

            var vgcombo = new Ext.form.ComboBox({
                editable:false,
                valueField:'name'
                ,hiddenName:'vm_vg'
                ,displayField:'name'
                ,triggerAction:'all'
                ,forceSelection:true
                ,enableKeyEvents:true
                ,resizable:true
                // we need wider list for paging toolbar
                ,minListWidth:250
                ,allowBlank:false
                ,store:vgstore
                // concatenate vgname and size (MB)
                ,tpl:'<tpl for="."><div class="x-combo-list-item">{name} ({[byte_to_MBconvert(values.size,2,"floor")]} MB)</div></tpl>'
                ,listeners:{
                    // sets raw value to concatenated last and first names
                    select:{scope:this, fn:function(combo, record, index) {
                        var size = byte_to_MBconvert(record.get('size'),2,'floor');
                        var lvsize_field = this.getForm().findField('vm_lv_newsize');
                        var totalvg_field = this.getForm().findField('vm_total_vg_size');
                        totalvg_field.setValue(size);

                        combo.setRawValue(record.get('name') + ' (' + size + ' MB)');
                        lvsize_field.setValue(size);

                    }}
                    // repair raw value after blur
                    ,blur:function() {
                        var val = this.getRawValue();
                        this.setRawValue.defer(1, this, [val]);
                    }
                    // set tooltip and validate
                    ,render:function() {
                        this.el.set({qtip: <?php echo json_encode(__('Choose volume group')) ?>});
                        this.validate();
                    }
                    // requery if field is cleared by typing
                    ,keypress:{buffer:100, fn:function() {
                        if(!this.getRawValue()) {
                            this.doQuery('', true);
                        }
                    }}
                }
                ,fieldLabel:'Volume Group'
                ,bbar:new Ext.ux.grid.TotalCountBar({
                    store:vgstore
                    ,displayInfo:true
                })
                ,anchor:'80%'
            });// end vgcombo

            var lv_exists = {
                            id:'vm_device_set_exist',
                            xtype:'fieldset',
                            labelWidth:170,
                            collapsed:config.diskfile ? true : false,
                            // hidden: config.diskfile ? true : false,
                            // disabled: config.diskfile ? true : false,
                            checkboxToggle:true,
                            title: <?php echo json_encode(__('Existing logical volume')) ?>,
                            autoHeight:true,
                            items:[
                                  lvcombo
                                  ,{
                                    xtype:'numberfield',
                                    fieldLabel: <?php echo json_encode(__('Logical volume size (MB)')) ?>,
                                    name: 'vm_lvsize',
                                    maxLength: 50,
                                    allowBlank: false,
                                    disabled:true
                                  }
                            ]//end fieldset items
                            ,listeners:{
                                beforecollapse:{scope:this,fn:function(panel,anim){
                                    var new_dev = Ext.getCmp('vm_device_set_new');

                                    if((!new_dev || new_dev.collapsed)
                                       && Ext.getCmp('vm_device_set_file').collapsed )
                                    {
                                       panel.checkbox.dom.checked = true;
                                       return false;
                                    }

                                    lvcombo.setDisabled(true);
                                    this.getForm().findField('vm_lvsize').setDisabled(true);

                                }},
                                beforeexpand:{scope:this,fn:function(panel,anim){
                                    lvcombo.setDisabled(false);
                                    this.getForm().findField('vm_lvsize').setDisabled(false);
                                }},
                                expand:function(){
                                    var new_dev = Ext.getCmp('vm_device_set_new');
                                    if(new_dev ) new_dev.collapse();
                                    Ext.getCmp('vm_device_set_file').collapse();

                                }
                            }
                        };


            var lv_new = {
                            id:'vm_device_set_new',
                            xtype:'fieldset',
                            checkboxToggle:true,
                            title: <?php echo json_encode(__('New logical volume')) ?>,
                            hidden: config.diskfile ? true : false,
                            disabled: config.diskfile ? true : false,
                            autoHeight:true,
                            labelWidth:170,
                            defaultType: 'textfield',
                            collapsed: true,
                            items :[
                                {
                                    xtype:'hidden',
                                    id:'vm_total_vg_size',
                                    name:'vm_total_vg_size'
                                },
                                {
                                    fieldLabel: <?php echo json_encode(__('Logical volume name')) ?>,
                                    allowBlank: false,
                                    name:'vm_lv_new'
                                },
                                vgcombo,
                                {
                                    fieldLabel: <?php echo json_encode(__('Logical volume size (MB)')) ?>,
                                    xtype:'numberfield',
                                    name: 'vm_lv_newsize',
                                    maxLength: 50,
                                    vtype: 'vm_lv_newsize',
                                    allowBlank: false,
                                    totallvsize: 'vm_total_vg_size'
                                }
                            ]
                            ,listeners:{

                                beforecollapse:{scope:this,fn:function(panel,anim){

                                    if(Ext.getCmp('vm_device_set_exist').collapsed
                                       && Ext.getCmp('vm_device_set_file').collapsed )
                                    {
                                       panel.checkbox.dom.checked = true;
                                       return false;
                                    }

                                    this.getForm().findField('vm_lv_new').setDisabled(true);
                                    this.getForm().findField('vm_lv_newsize').setDisabled(true);
                                    this.getForm().findField('vm_total_vg_size').setDisabled(true);
                                    vgcombo.setDisabled(true);

                                }},
                                beforeexpand:{scope:this,fn:function(panel,anim){

                                    this.getForm().findField('vm_lv_new').setDisabled(false);
                                    this.getForm().findField('vm_lv_newsize').setDisabled(false);
                                    this.getForm().findField('vm_total_vg_size').setDisabled(false);
                                    vgcombo.setDisabled(false);

                                }},
                                expand:function(){

                                    Ext.getCmp('vm_device_set_exist').collapse();
                                    Ext.getCmp('vm_device_set_file').collapse();
                                }
                            }
                        };

            var lv_disk = {
                            id:'vm_device_set_file',
                            xtype:'fieldset',
                            checkboxToggle: true,
                            title: <?php echo json_encode(__('New disk file')) ?>,
                            autoHeight:true,
                            labelWidth:170,
                            collapsed:config.diskfile ? false : true,
                            items :[
                                {
                                    xtype:'numberfield',                                    
                                    id:'vm_total_vgdisk_size',
                                    name:'vm_total_vgdisk_size',
                                    fieldLabel : <?php echo json_encode(__('Max available file size (MB)')) ?>,
                                    allowBlank : false,
                                    value: byte_to_MBconvert(<?php echo $max_size_diskfile ?>,2,'floor'),
                                    readOnly:true
                                },                                
                                {
                                    fieldLabel: <?php echo json_encode(__('File name')) ?>,
                                    xtype:'textfield',
                                    allowBlank: false,
                                    name:'vm_diskfile'
                                },
                                {
                                    fieldLabel: <?php echo json_encode(__('Max file size (MB)')) ?>,
                                    xtype:'numberfield',
                                    name: 'vm_disksize',
                                    vtype: 'vm_lv_newsize',
                                    allowBlank: false,
                                    totallvsize: 'vm_total_vgdisk_size',
                                    maxLength: 50,
                                    allowBlank: false

                                }
                            ]
                            ,listeners:{

                                beforecollapse:{scope:this,fn:function(panel,anim){
                                    var dev_new = Ext.getCmp('vm_device_set_new');
//                                    if(config.diskfile){
//                                       panel.checkbox.dom.checked = true;
//                                       return false;
//                                    }

                                    if(Ext.getCmp('vm_device_set_exist').collapsed
                                       && (!dev_new || dev_new.collapsed) )
                                    {
                                       panel.checkbox.dom.checked = true;
                                       return false;
                                    }

                                    this.getForm().findField('vm_diskfile').setDisabled(true);
                                    this.getForm().findField('vm_disksize').setDisabled(true);

                                }},
                                beforeexpand:{scope:this,fn:function(panel,anim){

                                    this.getForm().findField('vm_diskfile').setDisabled(false);
                                    this.getForm().findField('vm_disksize').setDisabled(false);

                                }},
                                expand:function(){
                                    var dev_new = Ext.getCmp('vm_device_set_new');
                                    if(dev_new) dev_new.collapse();
                                    Ext.getCmp('vm_device_set_exist').collapse();
                                }
                            }
                        };

            var storage_tpls = [];

            if(config.diskfile){
                storage_tpls.push(lv_exists,lv_disk);
            }else{
                storage_tpls.push(lv_exists,lv_new,lv_disk);
            }

            storage_tpls.push({
                    xtype:'combo',
                    ref:'disk_type',
                    name:'disk_type',
                    id:'vm_disk_type'
                    ,editable: false
                    ,typeAhead: false
                    ,fieldLabel: <?php echo json_encode(__('Disk type')) ?>,
                    width:150,hiddenName:'disk_type'
                    ,valueField: 'value',displayField: 'name',forceSelection: true,emptyText: <?php echo json_encode(__('Select type...')) ?>
                    ,store: new Ext.data.ArrayStore({
                            fields: ['type','value', 'name'],
                            data : <?php
                                        /*
                                         * build interfaces model dynamic
                                         */
                                        $disks_drivers = sfConfig::get('app_disks');
                                        $disks_elem = array();

                                        foreach($disks_drivers as $hyper =>$drivers)
                                            foreach($drivers as $driver)
                                                $disks_elem[] = '['.json_encode($hyper).','.json_encode($driver).','.json_encode($driver).']';
                                                echo '['.implode(',',$disks_elem).']'."\n";
                                    ?>
                            })
                    ,mode: 'local'
                    ,lastQuery:''
                    ,allowBlank:false
                    ,triggerAction: 'all'});

            Ext.apply(this, {

                title        : <?php echo json_encode(__('Storage')) ?>,ref:'serverwiz_storage',
                monitorValid : true,                
                items : [{
                        border    : false,
                        bodyStyle : 'background:none;padding-bottom:30px;',
                        html      : <?php echo json_encode(__('Choose storage for the machine.')) ?>
                        },storage_tpls
                ]
                ,listeners:{
                    show:function(){                        
                        this.form.findField('vm_lv').focus();
                    }

                }
            });

            storage_cardPanel.superclass.initComponent.call(this);
        }
        ,loadRecord:function(data){
            this.disk_type.store.filter('type',data['vm_type']);            
            this.disk_type.setValue(this.disk_type.store.getAt(0).data['name']);
        }

    });



    network_cardPanel = Ext.extend(Ext.ux.Wiz.Card, {

        initComponent: function(){

            Ext.apply(this, {

                title        : <?php echo json_encode(__('Network Type')) ?>,
                monitorValid : true,
                defaults     : {labelStyle : 'font-size:11px;width:140px;'},
                items : [{
                        border    : false,
                        bodyStyle : 'background:none;padding-bottom:30px;',
                        html      : <?php echo json_encode(__('Specify the network type connection to use in virtual server.')) ?>
                        },
                        {cls:'fieldset-top-sp',xtype:'fieldset',
                            title: <?php echo json_encode(__('Network Type')) ?>,
                            collapsible: false,
                            autoHeight:true,
                            defaultType:'radio',
                            labelWidth:10,
                            items :[{
                                    checked:true,
                                    hideLabel:true,
                                    boxLabel: <?php echo json_encode(__('Bridged networking')) ?>,
                                    name: 'vm_nettype',
                                    inputValue: 'bridge'
                                },{
                                    hideLabel:true,
                                    boxLabel: <?php echo json_encode(__('Network Address Translation')) ?>,
                                    name: 'vm_nettype',
                                    inputValue: 'network'
                                },{
                                    hideLabel:true,
                                    boxLabel: <?php echo json_encode(__('Host-only')) ?>,
                                    name: 'vm_nettype',
                                    inputValue: 'user'
                                }
                            ]
                        }
                ]                
            });

            network_cardPanel.superclass.initComponent.call(this);
        }

    });
    

    hostnetwork_cardPanel = Ext.extend(Ext.ux.Wiz.Card, {
        initComponent:function(){


            Ext.apply(this, {
                title        : <?php echo json_encode(__('Host network')) ?>,ref:'serverwiz_hostnetwork',
                monitorValid : true,
                items : [{
                            border    : false,
                            bodyStyle : 'background:none;padding-bottom:30px;',
                            html      : <?php echo json_encode(__('Specify the NIC\'s to use.')) ?>
                        }
                        ,{ref:'hostnetworks_panel',layout:'fit',border:true,anchor:'100% 75%'}
                        ,{
                            xtype:'textfield',
                            name:'network_status',
                            cls: 'nopad-border',
                            readOnly:true,
                            anchor:'90%',
                            hideLabel:true,
                            value : <?php echo json_encode(__('Network interfaces mapping incomplete!')) ?>,
                            invalidText : <?php echo json_encode(__('Network interfaces mapping incomplete!')) ?>,
                            allowBlank : false,
                            validator  : function(v){
                                return v!=<?php echo json_encode(__('Network interfaces mapping incomplete!')) ?>;
                            }
                        }

                    ]
                ,listeners:{
                    show:function(){                        
                        this.hostnetworks_panel.get(0).getTopToolbar().get(0).focus();
                    }
                }
            });


            hostnetwork_cardPanel.superclass.initComponent.call(this);


            this.hostnetworks_panel.on({
                render:{scope:this,
                    fn:function(p){

                        if(typeof Network =='undefined' || typeof Network.ManageInterfacesGrid =='undefined'){
                            
                            this.hostnetworks_panel.load({
                                url:<?php echo json_encode(url_for('network/Network_ManageInterfacesGrid')); ?>
                                ,scripts:true
                                ,scope:this
                                ,callback:function(){

                                    //var grid = this.addGrid();

                                    //this.hostnetworks_panel.add(grid);
                                    //this.hostnetworks_panel.doLayout();
                                }//end callback
                            });
                        }
                    }}//end fn
            });// end on...

        }
        ,addGrid:function(type, server_id){
            var grid = new Network.ManageInterfacesGrid({node_id:config.nodeId, level:'node' , ref:'../hostnetworks_grid',vm_type:type,border:false});

            grid.addBtn.on('click',function(){grid.fireEvent('afteredit');},grid);

            grid.getStore().on({
                'load':function(store){
                    grid.fireEvent('afteredit');
                },
                'add':function(store){
                    grid.fireEvent('afteredit');
                },
                'remove':function(store){
                    grid.fireEvent('afteredit');
                }
            });

            grid.on('afteredit',function(){

                var network_status = (this.ownerCt).ownerCt.form.findField('network_status');
                var cols = this.colModel.getColumnCount();
                var rows = this.store.getCount();

                if(rows==0){
                    network_status.setValue(<?php echo json_encode(__('Network interfaces mapping incomplete!')) ?>);
                    return false;
                }

                var r, c;
                var valid = true;
                for(r = 0; r < rows; r++) {
                    for(c = 1; c < cols; c++) {
                        valid = this.isCellValid(c, r);
                        if(!valid) {
                            break;
                        }
                    }
                    if(!valid) {
                        break;
                    }
                }

                if(!valid) network_status.setValue(<?php echo json_encode(__('Network interfaces mapping incomplete!')) ?>);
                else network_status.setValue(String.format(<?php echo json_encode(__('You have {0} network interface(s) defined!')) ?>,rows));

                return valid;
            });

            return grid;


        }
        ,loadRecord:function(data){
            this.hostnetworks_panel.removeAll();
            var grid = this.addGrid(data['vm_type'],data['server_id']);
            this.hostnetworks_panel.add(grid);
            this.hostnetworks_panel.doLayout();

        }

    });


    startup_cardPanel = Ext.extend(Ext.ux.Wiz.Card, {

        initComponent: function(){

            Ext.apply(this, {
                title        : <?php echo json_encode(__('Startup')) ?>,
                monitorValid : true,
                items:[{
                    border    : false,
                    bodyStyle : 'background:none;padding-bottom:30px;',
                    html      : <?php echo json_encode(__('Specify the boot/location parameters used by the virtual server.')) ?>
                    }]
                ,listeners:{
                    show:function(){

                        var item = this.items.get(1);
                        item.items.get(1).focus();
                    }

                }
            });


            var cdromstore = new Ext.data.JsonStore({
                id:'id'
                ,root:'data'
                ,totalProperty:'total'
                ,fields:[
                    {name:'name', type:'string'},'full_path'
                ]
                ,url:<?php echo json_encode(url_for('view/iso'))?>
                ,baseParams:{doAction:'jsonList'}

            });

            this.cdromcombo = new Ext.form.ComboBox({
                editable:false
                ,disabled:true
                ,valueField:'full_path'
                ,hiddenName:'vm_boot_cdrom'
                ,displayField:'name'
                ,triggerAction:'all'
                ,forceSelection:true
                ,enableKeyEvents:true
                ,resizable:true
                ,minListWidth:250
               // ,width:200
                ,anchor:'90%'
                //,maxHeight:150
                ,allowBlank:false
                ,store:cdromstore

                ,listeners:{
                    // set tooltip and validate
                    render:function() {
                        this.el.set({qtip: <?php echo json_encode(__('Choose iso to load')) ?>});
                        this.validate();
                    }
                }
                ,bbar:new Ext.ux.grid.TotalCountBar({
                    store:cdromstore
                    ,displayInfo:true
                })
            });

            startup_cardPanel.superclass.initComponent.call(this);
        }
        ,setLocation:function(){

            var item_Boot = Ext.getCmp('server-wiz-startup-boot');
            var item_Location = Ext.getCmp('server-wiz-startup-location');

            if(item_Boot){
                var boot_cdrom_field = this.form.findField('vm_boot_cdrom');
                boot_cdrom_field.setDisabled(true);

                this.items.get(item_Boot.id).hide();

            }


            if(!item_Location){

                item_Location = this.add({id:'server-wiz-startup-location',
                        cls:'fieldset-top-sp',xtype:'fieldset',
                        title: <?php echo json_encode(__('Location options')) ?>,
                        collapsible: false,
                        autoHeight:true,
                        defaultType:'radio',
                        labelWidth:10,
                        items : [
                            {
                            xtype:'panel',
                            border    : false,
                            bodyStyle : 'background:none;padding-bottom:20px;',
                            html      : <?php echo json_encode(__('Specify the installation source.')) ?>
                        },
                        {
                            boxLabel: <?php echo json_encode(__('None')) ?>,
                            name: 'vm_location',checked: true,
                            hideLabel:true,
                            inputValue: 'none',
                            scope:this
                        },{
                            boxLabel: <?php echo json_encode(__('Network install (http,ftp,...)')) ?>,
                            name: 'vm_location',
                            hideLabel:true,
                            inputValue: 'remote',
                            scope:this,
                            handler:function(box,check){
                                var remote_field = this.form.findField('vm_location_remote');
                                remote_field.setDisabled(!check);
                                remote_field.clearInvalid();}
                        },{
                            xtype: 'textfield',
                            name: 'vm_location_remote',
                            disabled:true,
                            anchor:'90%',
                            allowBlank:false,
                            emptyText : 'url://path/to/image kernel',
                            labelStyle : 'font-size:11px;width:25px;',
                            enableKeyEvents: false,
                            locationValid: false
                            ,validator: function(v){
                                return this.locationValid;
                            }
                            ,listeners:{
                                specialkey:{scope:this,fn:function(field,e){

                                    if(e.getKey()==e.ENTER){
                                        if(!this.wizard.nextButton.disabled) this.wizard.nextButton.focus();
                                        checkLocation(field.getValue(), field);
                                    }
                                    
                                    if(e.getKey() == e.BACKSPACE){
                                        field.locationValid = false;
                                    }

                                }}
                                ,focus:{scope:this, fn:function(field,e){
                                    field.locationValid = false;
                                }}
                                ,blur:{scope:this, fn:function(field,e){
                                    checkLocation(field.getValue(), field);           
                                }}
                            }
                        }
                        ]
                    });
            }
            item_Location.show();
        }
        ,setBoot:function(){
            var item_Boot = Ext.getCmp('server-wiz-startup-boot');
            var item_Location = Ext.getCmp('server-wiz-startup-location');

            if(item_Location){
                //var local_field = this.form.findField('vm_location_local');
                var remote_field = this.form.findField('vm_location_remote');                
                //local_field.setDisabled(true);
                remote_field.setDisabled(true);

                this.items.get(item_Location.id).hide();

            }

            if(!item_Boot){

                item_Boot = this.add({id:'server-wiz-startup-boot',
                            cls:'fieldset-top-sp',xtype:'fieldset',
                            title: <?php echo json_encode(__('Boot options')) ?>,
                            collapsible: false,
                            autoHeight:true,
                            defaultType:'radio',
                            labelWidth:10,
                            items : [
                                {
                                    xtype:'panel',
                                    border    : false,
                                    bodyStyle : 'background:none;padding-bottom:20px;',
                                    html      : <?php echo json_encode(__('Specify boot device.')) ?>
                                },
                                {
                                    boxLabel: <?php echo json_encode(__('Disk')) ?>,
                                    name: 'vm_boot',
                                    checked: true,
                                    hideLabel:true,
                                    inputValue: 'filesystem',
                                    scope:this
                                },
                                {
                                    boxLabel: <?php echo json_encode(__('Network boot (PXE)')) ?>,
                                    name: 'vm_boot',
                                    hideLabel:true,
                                    inputValue: 'network'
                                },
                                {
                                    hideLabel:true,
                                    boxLabel: 'CD-ROM',
                                    name: 'vm_boot',
                                    inputValue: 'cdrom'
                                    ,scope:this
                                    ,handler:function(box,check){
                                        var boot_cdrom_field = this.form.findField('vm_boot_cdrom');
                                        boot_cdrom_field.setDisabled(!check);
                                        boot_cdrom_field.clearInvalid();}
                                },this.cdromcombo
                            ]
                    });
            }
            item_Boot.show();

            var values = this.form.getValues();

            if(values['vm_boot']){
                var boot = values['vm_boot'];

                if(boot == 'cdrom' ){
                    var cdrom_field = this.form.findField('vm_boot_cdrom');
                    cdrom_field.setDisabled(false);
                }

            }

        }

    });

    
    var viewerSize = Ext.getBody().getViewSize();
    var windowHeight = viewerSize.height * 0.95;
    windowHeight = Ext.util.Format.round(windowHeight,0);

    var cards = [
            // card with welcome message
                new Ext.ux.Wiz.Card({
                    title : <?php echo json_encode(__('Welcome')) ?>,
                    defaults     : {
                        labelStyle : 'font-size:11px;width:140px;'
                    },
                    items : [{
                            border    : false,
                            bodyStyle : 'background:none;',
                            html      : <?php echo json_encode(__('Welcome to the virtual server creation wizard.<br>Follow the steps to configure a full capable server')) ?>
                        }]                    
                })
                ];
    
    var memCard = new mem_cardPanel({id:'server-wiz-memory'});
    var cpuCard = new cpu_cardPanel({id:'server-wiz-cpuset'});
    var storageCard = new storage_cardPanel({id:'server-wiz-storage'});
    //var networkCard = new network_cardPanel({id:'server-wiz-nettype'});
    var hostnetworkCard = new hostnetwork_cardPanel();

    var startupCard = new startup_cardPanel({id:'server-wiz-startup'});
   
    switch(config.hypervisor){
        case 'xen' :                        
                        cards.push(new xen_name_cardPanel({id:'server-wiz-name'}));                       
                        cards.push(memCard);
                        cards.push(cpuCard);
                        cards.push(storageCard);
                        //cards.push(networkCard);
                        cards.push(hostnetworkCard);
                        cards.push(startupCard);
                        break;
        case 'hvm+xen'  :
                        cards.push(new xen_name_cardPanel({id:'server-wiz-name',hvm:true}));
                        cards.push(memCard);
                        cards.push(cpuCard);
                        cards.push(storageCard);
                        //cards.push(networkCard);
                        cards.push(hostnetworkCard);
                        cards.push(startupCard);
                        break;
        case 'kvm' :
                        cards.push(new kvm_name_cardPanel({id:'server-wiz-name'}));
                        cards.push(memCard);
                        cards.push(cpuCard);
                        cards.push(storageCard);
                        //cards.push(networkCard);
                        cards.push(hostnetworkCard);
                        cards.push(startupCard);
                        break;
            default:break;
    }

    memCard.on('afterlayout',function(){
                                memCard.loadRecord({'vm_freememory':<?php echo($etva_node->getMemfree()); ?>,'vm_maxmemory':<?php echo($etva_node->getMaxMem()); ?>});
                            },this,{single:true});

    cpuCard.on('afterlayout',function(){
                                cpuCard.loadRecord({'vm_realcpu':<?php echo($etva_node->getCputotal()); ?>});
                            },this,{single:true});


    cards.push(// finish card with finish-message
            new Ext.ux.Wiz.Card({
                title        : <?php echo json_encode(__('Finished!')) ?>,
                //monitorValid : true,
                items : [{
                        border    : false,
                        bodyStyle : 'background:none;',
                        html      : <?php echo json_encode(__('Thank you! Your data has been collected.<br>When you click on the "Finish" button, the virtual server will be created.')) ?>
                    }]
                ,listeners:{
                    show:function(){                        
                        this.wizard.nextButton.focus();
                    }
                }
            }));

    var wizard = new Ext.ux.Wiz({
        border:true,
        title : <?php echo json_encode(__('Add server wizard')) ?>,
        tools:[{id:'help', qtip: __('Help'),handler:function(){View.showHelp({anchorid:'help_virtual_machine_add',autoLoad:{ params:'mod=server'},title: <?php echo json_encode(__('Add Server Help')) ?>});}}],


        headerConfig : {
            title : <?php echo json_encode(__('Create new virtual server')) ?>
        },
        width:800,
        height:450,

        westConfig : {
            width : 170
        },
        defaultButton: 1,
        cardPanelConfig : {
            defaults : {
                baseCls    : 'x-small-editor',
                bodyStyle  : 'border:none;padding:15px 15px 15px 15px;background-color:#F6F6F6;',
                border     : false
            }
        },
        cards: cards,
        listeners: {
            finish: function() { onSave(this.getWizardData()); }
        }
    });
    
    

    function lvcreate(storage,data){

        var lvname = storage['vm_lv_new'];
        var vgname = storage['vm_vg'];
        var size = storage['vm_lv_newsize']+'M';

        // create parameters array to pass to soap request....
        var params = {
            'nid':config.nodeId,
            'lv':lvname,
            'vg':vgname,
            'size':size};

        var conn = new Ext.data.Connection({
            listeners:{
                // wait message.....
                beforerequest:function(){
                    Ext.MessageBox.show({
                        title: <?php echo json_encode(__('Please wait...')) ?>,
                        msg: <?php echo json_encode(__('Creating logical volume...')) ?>,
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
            url: <?php echo json_encode(url_for('logicalvol/jsonCreate'))?>,
            params: params,
            scope:this,
            success: function(resp,opt){
                var response = Ext.util.JSON.decode(resp.responseText);
                Ext.ux.Logger.info(response['agent'],response['response']);                                
                
                var disks=[{'id':response['insert_id'],'disk_type':storage['disk_type']}];
                data['disks'] = disks;

                vmcreate(data);

            },
            failure: function(resp,opt) {
                var response = Ext.util.JSON.decode(resp.responseText);
                Ext.ux.Logger.error(response['agent'],response['error']);

                Ext.Msg.show({
                    title: String.format(<?php echo json_encode(__('Error {0}')) ?>,response['agent']),
                    buttons: Ext.MessageBox.OK,
                    msg: String.format(<?php echo json_encode(__('Unable to create logical volume {0}!')) ?>,lvname)+'<br>'+response['info'],
                    icon: Ext.MessageBox.ERROR});
            }
        });// END Ajax request

    }
    
    function checkLocation(url, field){
        var conn = new Ext.data.Connection({
        });// end conn

        conn.request({
            url: <?php echo json_encode(url_for('server/jsonCheckUrl',false)); ?>,
            params: {'url':url},
            scope: this,
            success: function(resp,opt) {
                var response = Ext.decode(resp.responseText);
                if(response['success']){
                    field.locationValid = true;
                }else{
                    field.locationValid = false;
                }

            }
        }); // END Ajax request
    }

    function vmcreate(send_data)
    {
        var conn = new Ext.data.Connection({
                listeners:{
                    // wait message.....
                    beforerequest:function(){
                        Ext.MessageBox.show({
                            title: <?php echo json_encode(__('Please wait...')) ?>,
                            msg: <?php echo json_encode(__('Creating virtual server...')) ?>,
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
            url: <?php echo json_encode(url_for('server/jsonCreate',false)); ?>,
            params: {'nid':config.nodeId,'server': Ext.encode(send_data)},
          //  title: name,
            scope: this,
            success: function(resp,opt) {
                var response = Ext.decode(resp.responseText);
                var sid = response['insert_id'];
                var tree_id = 's'+ sid;

                Ext.ux.Logger.info(response['agent'],response['response']);
                
                Ext.getCmp('view-nodes-panel').addNode({parentNode:config.nodeId,id: tree_id,leaf:true,type:'server',text: send_data['name'],
                    url: <?php echo json_encode(url_for('server/view?id=',false)) ?>+sid});


            },
            failure: function(resp,opt) {
                var response = Ext.decode(resp.responseText);

                if(response)
                Ext.Msg.show({
                    title: String.format(<?php echo json_encode(__('Error {0}')) ?>,response['agent']),
                    buttons: Ext.MessageBox.OK,
                    width:300,                    
                    msg: String.format(<?php echo json_encode(__('Unable to create virtual server {0}!')) ?>,send_data['name'])+'<br>'+response['info'],
                    icon: Ext.MessageBox.ERROR});

            }
        }); // END Ajax request


    }





    // save form processing
    function onSave(wizData) {

        var wizPanel = wizard.cardPanel;
        var data = new Object();
        

        var hostnetworks = wizPanel.serverwiz_hostnetwork;        
        var networks_store = hostnetworks.hostnetworks_grid.getStore();

       // var nets_store = Ext.getCmp('server-wiz-macvlan').getStore();
        var i = 0;
        var networks=[];
        networks_store.each(function(f){
            var data = f.data;
            networks.push({
                'port':i,
                'vlan_id':data['vlan_id'],
                'intf_model':data['intf_model'],
                'mac':data['mac']
            });
            i++;
        });


        var name_raw = wizData['server-wiz-name'];
        
        var name = name_raw['vm_name'];
        var description = name_raw['vm_OS'];
        var vm_type = name_raw['vm_type'];

        var vm_os = '';
        var match_os = (description.toLowerCase()).match(/^linux/);
        if(match_os) vm_os = 'linux';
        else vm_os = 'windows';        

        //var nettype_raw = wizData['server-wiz-nettype'];
        //var nettype = nettype_raw['vm_nettype'];

        var mem_raw = wizData['server-wiz-memory'];
        var mem = mem_raw['vm_memory'];

        var cpuset_raw = wizData['server-wiz-cpuset'];
        var cpuset = cpuset_raw['vm_cpu'];

        var send_data = {
            'name':name
            ,'vm_type':vm_type
            ,'vm_os': vm_os
            ,'description':description
            ,'ip':"000.000.000.000"
            ,'networks':networks
            //,'nettype':nettype
            ,'mem':mem
            ,'vcpu':cpuset
        }

        var startup_raw = wizData['server-wiz-startup'];
        var startupPanel = wizPanel.get('server-wiz-startup');
        switch(send_data['description']){
            case 'Linux PV' :
                                var location_data = startup_raw['vm_location'];
                                var location = '';                                
                                var remote_field = startupPanel.form.findField('vm_location_remote');                                

                                if(location_data =='remote'){

                                    if(startup_raw['vm_location_remote'] != remote_field.emptyText)
                                        location = startup_raw['vm_location_remote'];
                                }

                                send_data['location'] = location;
                                send_data['boot'] = location ? 'location' : 'filesystem';
                                //send_data['vm_type'] = 'pv';
                                break;                                            
            default:
                                //if(send_data['description'] == 'Linux HVM') send_data['vm_type'] = 'hvm';
                                //else send_data['vm_type'] = 'kvm';

                                var boot_data = startup_raw['vm_boot'];
                                var cdrom_field = startupPanel.form.findField('vm_boot_cdrom');

                                if(boot_data == 'cdrom'){

                                    if(startup_raw['vm_boot_cdrom'] != cdrom_field.emptyText){
                                        send_data['location'] = startup_raw['vm_boot_cdrom'];
                                        send_data['boot'] = boot_data;
                                    }
                                }

                                 if(boot_data == 'network'){
                                    send_data['boot'] = 'pxe';
                                }

                                if(boot_data == 'filesystem'){
                                    send_data['boot'] = 'filesystem';
                                }

                                break;

        }


        // checks if needs to create lv
        // if lv is new then create lv and then create server

        var storage_raw = wizData['server-wiz-storage'];
        if(storage_raw['vm_device_set_new-checkbox'] == 'on'){            
            lvcreate(storage_raw,send_data);
            return;
        }

        if(storage_raw['vm_device_set_file-checkbox'] == 'on'){            
            storage_raw['vm_lv_new'] = storage_raw['vm_diskfile'];
            storage_raw['vm_lv_newsize'] = storage_raw['vm_disksize'];
            storage_raw['vm_vg'] = <?php echo json_encode(sfConfig::get("app_volgroup_disk_flag")) ?>;
            // setting lv name parameter for post processing vmCreate
            lvcreate(storage_raw,send_data);
            return;
        }

        // lv already exists just create server
        var disks=[{'id':storage_raw['vm_lv'],'disk_type':storage_raw['disk_type']}];
        send_data['disks'] = disks;
        //send_data['lv'] = storage_raw['vm_lv'];
        vmcreate(send_data);


    }

    // show the wizard
    wizard.show();    
    //.showInit();




}

/*
 * call server wizard(config)
 * args: config to use
 *       nodeId -- node ID
 *       hypervisor -- type of virtualization supported
 *       diskfile -- only allows disk file creation
 *
 */
new Server_wizard({nodeId:<?php echo $etva_node->getId() ?>,
                   hypervisor:<?php echo json_encode($etva_node->getHypervisor()); ?>,
                   diskfile:<?php echo json_encode($diskfile)?>}
                 );
</script>
