<script>

Ext.ns('Server.Edit');

Server.Edit.Form = Ext.extend(Ext.form.FormPanel, {
    id: 'server-edit-form',
    border:false
    ,monitorValid:true   
    ,initComponent:function() {
        
        this.items = [
            {xtype:'hidden',name:'id'},
            {xtype:'hidden',name:'vm_state'},
            {xtype:'tabpanel', 
             activeItem:0,
             id: 'server-edit-tabpanel',
             ref: 'serverEditTabPanel',
             anchor: '100% 100%',             
             defaults:{
                 layout:'form'
                 ,labelWidth:140                 
            }
           }
        ];        

        // build form-buttons
        this.buttons = [{
                            text: __('Save'),
                            formBind:true,
                            handler: this.onSave,
                            scope: this
                        },
                        {
                            text:__('Cancel'),
                            scope:this,
                            handler:function(){(this.ownerCt).close()}
                        }];

        Server.Edit.Form.superclass.initComponent.call(this);

        this.loadConfigurationPanel();
        this.loadNetworksPanel();
        this.loadDisksPanel();
        this.loadDevicesPanel(); 
        this.loadOtherOptionsPanel(); 
        this.loadVMHAPanel();

    }
    ,onRender:function(){
        // call parent
        Server.Edit.Form.superclass.onRender.apply(this, arguments);
        // set wait message target
        this.getForm().waitMsgTarget = this.getEl();       
    }
    ,getVmType:function(data){
        var vm_type = data['vm_type'] ? data['vm_type'] : '';
        var vm_os = data['vm_OS'] ? data['vm_OS'] : '';
        if( data['node_hypervisor'] == 'xen' ) vm_type = 'pv';
        else if( data['node_hypervisor'] == 'hvm+xen' ){  // only xen with hvm can change vm_type
            if( vm_os ){
                vm_type = 'hvm';
                if( (vm_os.toLowerCase()).match(/ pv$/) )
                    vm_type = 'pv';
            }
        } else {
            vm_type = data['node_hypervisor'];
        }
        return vm_type;
    }
    /*
     * load boot options
     */
    ,loadBootOptions:function(data){

        var cdromstore = new Ext.data.JsonStore({
                //id:'id'
                root:'data'
                ,totalProperty:'total'
                ,fields:[
                    {name:'name', type:'string'},'full_path'
                ]
                ,proxy: new Ext.data.HttpProxy({
                        url:<?php echo json_encode(url_for('view/iso'))?>
                    })
                ,baseParams:{doAction:'jsonList',params:Ext.encode({emptyValue:true})}
        });        

        //build cdrom com for menu bar

        var cdromcheckbox = new Ext.form.Checkbox({ fieldLabel:'',hideLabel:true,ref:'cdrom_ckb',name:'cdrom_ckb',boxLabel:'',inputValue: '1'
                                                            ,checked: ((data['location']!=null)? true : false)
                                                            ,listeners:{
                                                                    check:{scope:this,fn:function(ckb,ck){
                                                                            this.getForm().findField('cdrom_cb').setDisabled(!ck);
                                                                            if( ck ){
                                                                                Ext.getCmp('server-edit-config-boot-cdrom').setDisabled(false);
                                                                            } else {
                                                                                Ext.getCmp('server-edit-config-boot-cdrom').setDisabled(!this.getForm().findField('cdromextra_ckb').getValue());
                                                                            }
                                                                    }}
                                                            }
                                                        });
        var cdromcombo = new Ext.form.ComboBox({
                fieldLabel:'CD-ROM',
                editable:false
                ,disabled: (data['boot']=='location') ? true : false
                ,valueField:'full_path'
                ,hiddenName:'cdrom_cb'
                ,ref:'cdrom_cb'
                ,displayField:'name'
                ,pageSize:10
                ,triggerAction:'all'
                ,forceSelection:true
                ,selectOnFocus:true
                ,valueNotFoundText: __('Invalid')            
                ,resizable:true
                ,minListWidth:250
                ,anchor:'80%'
                ,store:cdromstore
                ,validator:function(v){                    
                    
                    var boot_cdrom = (this.ownerCt).ownerCt.boot_cdrom;
                    if(boot_cdrom)
                        if(boot_cdrom.getValue() && cdromstore.getAt(0) && cdromstore.getAt(0).data['name']==v) return <?php echo json_encode(__('Choose iso to load')) ?>;

                    return true;
                    
                }
                ,listeners:{
                    // set tooltip and validate
                    render:function() {
                        this.el.set({qtip: <?php echo json_encode(__('Choose iso to load')) ?>});
                        this.validate();
                    }
                    
                }
        });

        var cdromcheckboxExtra = new Ext.form.Checkbox({ fieldLabel:'',hideLabel:true,ref:'cdromextra_ckb',name:'cdromextra_ckb',boxLabel:'',inputValue: '1'
                                                            ,checked: ((data['cdromextra']!=null) ? true : false)
                                                            ,listeners:{
                                                                    check:{scope:this,fn:function(ckb,ck){
                                                                            this.getForm().findField('cdromextra_cb').setDisabled(!ck);

                                                                            if( ck ){
                                                                                Ext.getCmp('server-edit-config-boot-cdrom').setDisabled(false);
                                                                            } else {
                                                                                Ext.getCmp('server-edit-config-boot-cdrom').setDisabled(!this.getForm().findField('cdrom_ckb').getValue());
                                                                            }
                                                                    }}
                                                            }
                                                });
        var cdromcomboExtra = new Ext.form.ComboBox({
                fieldLabel:'Extra CD-ROM',
                editable:false
                ,valueField:'full_path'
                ,hiddenName:'cdromextra_cb'
                ,ref:'cdromextra_cb'
                ,displayField:'name'
                ,pageSize:10
                ,triggerAction:'all'
                ,forceSelection:true
                ,selectOnFocus:true
                ,valueNotFoundText: __('Invalid')            
                ,resizable:true
                ,minListWidth:250
                ,anchor:'80%'
                ,store:cdromstore
                ,listeners:{
                    // set tooltip and validate
                    render:function() {
                        this.el.set({qtip: <?php echo json_encode(__('Choose iso to load')) ?>});
                    }
                    
                }
        });
        var fieldset_items = 
                        [
                            {
                                boxLabel: <?php echo json_encode(__('Auto-start')) ?>,
                                xtype:'checkbox', checked: (data['autostart']) ? true : false,
                                name:'autostart',inputValue:'1',ref:'autostart'
                            },
                            {
                                boxLabel: <?php echo json_encode(__('Disk')) ?>,
                                xtype:'radio', checked: (data['boot']=='filesystem') ? true : false,
                                name:'boot_from',inputValue:'filesystem',ref:'boot_filesystem'
                                ,disabled: ((!data['server_has_disks'] || (this.getVmType(this.getForm().getValues())=='pv' && (data['vm_state'] == 'running'))) ? true : false)
                                ,id: 'server-edit-config-boot-vmfilesystem'
                            }
                        ];
                  
        //if(1 || this.getVmType(this.getForm().getValues())!='pv'){
            
            /*
            * if virtual machine is HVM or KVM... load pxe, cd-rom items
            *
            */
            fieldset_items.push(
                        {
                            boxLabel:'PXE'
                            ,xtype:'radio', checked: (data['boot']=='pxe') ? true : false
                            ,ref:'boot_pxe'
                            ,inputValue:'pxe'
                            ,name: 'boot_from'
                            ,disabled: !data['server_has_netifs']
                            ,id: 'server-edit-config-boot-pxe'
                        }
                        ,{
                            boxLabel:'CD-ROM'
                            ,xtype:'radio'
                            ,inputValue:'cdrom',ref:'../boot_cdrom', checked: (data['boot']=='cdrom') ? true : false
                            ,name: 'boot_from'
                            ,disabled: (((data['cdromextra']!=null) || (data['location']!=null))? false : true)
                            ,id: 'server-edit-config-boot-cdrom'
                            ,scope:this
                            ,handler:function(box,check){
                                var cdrom_field = this.form.findField('cdrom_cb');
                                if(cdrom_field.getValue()=='') cdrom_field.validate();
                            }
                        }
                        
            );//end push
            

        /*
        * if virtual machine is PV... load location item
        *
        */
        //}else{

            fieldset_items.push(
                        {
                            boxLabel: 'Location URL',
                            xtype:'radio', checked: (data['boot']=='location') ? true : false,
                            inputValue: 'location',ref:'boot_location'
                            ,name: 'boot_from'
                            ,disabled: !data['server_has_netifs']
                            ,id: 'server-edit-config-boot-locationurl'
                            ,scope:this
                            ,handler:function(box,check){
                                var boot_location_field = this.form.findField('location');
                                var cdrom_field = this.form.findField('cdrom_cb');
                                boot_location_field.setDisabled(!check);
                                boot_location_field.clearInvalid();
                                //cdrom_field.setDisabled(check);

                                if(check) boot_location_field.focus();

                            }
                        }
                        ,{
                            xtype:'textfield',
                            labelStyle:'width:30px',
                            name:'location', anchor:'80%',
                            id: 'server-edit-config-boot-locationurl-text',
                            disabled:((data['server_has_netifs'])&&(data['boot']=='location')) ? false : true,
                            validator:function(v){
                                if(!v) return 'needed';
                            },
                            value: (data['cdrom']==1) ? '': data['location']
                        }
            );            
            //end push
        //}

        // populate cdrom combo items
        if(cdromcombo.getStore().getTotalCount()>0){
            if(data['cdrom']) cdromcombo.setValue(data['location']);
        } else {
            cdromcombo.getStore().reload({scope:this,callback:function(){
                        var cb_store = cdromcombo.getStore();
                        if(cb_store){
                            cdromcombo.setValue('');
                            if(data['cdrom'] || data['boot']=='cdrom'){
                                var matched = cb_store.findExact('full_path',data['location']);

                                if(matched != -1) cdromcombo.setValue(data['location']);

                            }
                        }
            }});
        }

        // populate cdrom extra combo items
        if(cdromcomboExtra.getStore().getTotalCount()>0){
            if(data['cdromextra']) cdromcomboExtra.setValue(data['cdromextra']);
        } else {
            cdromcomboExtra.getStore().reload({scope:this,callback:function(){
                        var cb_store = cdromcomboExtra.getStore();
                        if(cb_store){
                            cdromcomboExtra.setValue('');
                            if(data['cdromextra']){
                                var matched = cb_store.findExact('full_path',data['cdromextra']);
                                if(matched != -1) cdromcomboExtra.setValue(data['cdromextra']);
                            }
                        }
            }});
        }

        /*cdromcheckbox.setValue((data['location']!=null) ? true : false);
        cdromcheckboxExtra.setValue((data['cdromextra']!=null) ? true : false);*/

        var fieldset = [];
        if(data['node_hypervisor']=='kvm'){
            fieldset.push({xtype:'fieldset',
                                title: <?php echo json_encode(__('Guest operating system')) ?>,
                                collapsible: false,
                                autoHeight:true,
                                defaultType:'radio',
                                labelWidth:10,
                                items:[
                                      {
                                        checked: (data['vm_os']=='linux') ? true : false,
                                        hideLabel:true,
                                        boxLabel: 'Linux',
                                        name: 'vm_OS',
                                        disabled: ((data['vm_state'] == 'running') ? true : false),
                                        inputValue: 'Linux'
                                      }
                                      ,{
                                        checked: (data['vm_os']=='windows') ? true : false,
                                        hideLabel:true,
                                        boxLabel: 'Windows',
                                        name: 'vm_OS',
                                        disabled: ((data['vm_state'] == 'running') ? true : false),
                                        inputValue: 'Windows'
                                       }
                                ]//end fieldset items
                            });
        } else {
            fieldset.push({xtype:'fieldset',
                                title: <?php echo json_encode(__('Guest operating system')) ?>,
                                collapsible: false,
                                autoHeight:true,
                                labelWidth:10,
                                items:[

                                    {
                                        checked: ((data['vm_type']=='pv') && (data['vm_os']=='linux')) ? true : false,
                                        xtype:'radio',
                                        hideLabel:true,
                                        boxLabel: 'Linux PV',
                                        name: 'vm_OS',
                                        disabled: ((data['vm_state'] == 'running') ? true : false),
                                        inputValue: 'Linux PV'
                                        ,listeners : {
                                            check:{
                                                fn:function(form,checked){
                                                    if( checked ){
                                                        Ext.getCmp('server-edit-devices').setDisabled(true);
                                                        Ext.getCmp('server-edit-config-boot-locationurl').show();
                                                        Ext.getCmp('server-edit-config-boot-locationurl-text').show();
                                                        Ext.getCmp('server-edit-config-boot-pxe').hide();
                                                        Ext.getCmp('server-edit-config-boot-cdrom').hide();
                                                        Ext.getCmp('server-edit-fieldset-removable-media').hide();
                                                    } else {
                                                        Ext.getCmp('server-edit-devices').setDisabled(false);
                                                        Ext.getCmp('server-edit-config-boot-locationurl').hide();
                                                        Ext.getCmp('server-edit-config-boot-locationurl-text').hide();
                                                        Ext.getCmp('server-edit-config-boot-pxe').show();
                                                        Ext.getCmp('server-edit-config-boot-cdrom').show();
                                                        Ext.getCmp('server-edit-fieldset-removable-media').show();
                                                    }
                                                }
                                            }
                                        }
                                    },
                                    {
                                        layout:'table',
                                        border:false,
                                        bodyStyle:'background:transparent;',
                                        layoutConfig: {columns: 2},
                                        defaults:{layout:'form',border:false,bodyStyle:'background:transparent;'},
                                        items: [
                                            {items:[{
                                                hideLabel:true,
                                                xtype:'radio',
                                                boxLabel: 'Linux HVM',
                                                disabled: (((data['vm_state'] == 'running') || (data['node_hypervisor']=='xen')) ? true : false),
                                                checked: ((data['vm_type']!='pv') && (data['vm_os']=='linux')) ? true : false,
                                                name: 'vm_OS',
                                                inputValue: 'Linux HVM'
                                            }]},
                                            {items:[
                                                {xtype:'displayfield',width:40,helpText: <?php echo json_encode(__('Enabled only if this machine supports Hardware Virtual Machine')) ?>}
                                            ]}
                                        ]
                                    }
                                  ,{
                                    xtype:'radio',
                                    hidden: ((data['node_hypervisor']=='xen') ? true : false),
                                    disabled: ((data['vm_state'] == 'running') ? true : false),
                                    hideLabel:true,
                                    boxLabel: 'Windows',
                                    name: 'vm_OS',
                                    checked: (data['vm_os']=='windows') ? true : false,
                                    inputValue: 'Windows'
                                   }
                                ]//end fieldset items
                            });
        }
        
        fieldset.push({
                            xtype:'fieldset',          
                            autoHeight:true,
                            title: <?php echo json_encode(__('Boot options')) ?>,
                            labelWidth:10,                            
                            items:fieldset_items
                        });
        //if(1 || this.getVmType(this.getForm().getValues())!='pv'){
            fieldset.push(
                                {
                                    id: 'server-edit-fieldset-removable-media',
                                    xtype:'fieldset',
                                    title: <?php echo json_encode(__('Removable media')) ?>,
                                    //items:[cdromcombo,cdromcomboExtra]
                                    defaults:{border:false},
                                    items: [
                                        {
                                            layout:'table',
                                            layoutConfig: {columns: 2},
                                            defaults:{layout:'form',border:false},
                                            items: [
                                                {items:[cdromcheckbox]},
                                                {items:[cdromcombo]}
                                            ]
                                        }
                                        ,{
                                            layout:'table',
                                            layoutConfig: {columns: 2},
                                            defaults:{layout:'form',border:false},
                                            items: [
                                                {items:[cdromcheckboxExtra]},
                                                {items:[cdromcomboExtra]}
                                            ]
                                        }
                                    ]
                                }
                            );
        //}
                    
        return fieldset;
        
    }
    ,loadConfigurationPanel:function(){


        var configuration = {
            title: <?php echo json_encode(__('General')) ?>
            ,bodyStyle:'padding:5px'
            ,autoScroll:true 
            ,layout: {
                type: 'hbox',
                align: 'stretch'  // Child items are stretched to full width
            }            
            ,defaults:{layout:'form',bodyStyle:'padding:10px;',border:false}
            ,items:
                [
                 {
                     flex:1,
                     items:[                        
                        {xtype:'hidden',name:'node_hypervisor'},
                        {xtype:'hidden',name:'vm_type'},
                        {
                            xtype:'textfield',
                            name       : 'name',
                            fieldLabel : <?php echo json_encode(__('Virtual server name')) ?>,
                            allowBlank : false,
                            invalidText : <?php echo json_encode(__('No spaces and only alpha-numeric characters allowed!')) ?>,
                            validator  : function(v){
                                var t = /^[a-zA-Z][a-zA-Z0-9\-\_]+$/;
                                return t.test(v);
                            }
                        },
                        {
                            xtype:'textfield',
                            name       : 'description',
                            fieldLabel : <?php echo json_encode(__('Description')) ?>
                        },
                        {
                            xtype:'numberfield',
                            name       : 'maxmemory',
                            fieldLabel : <?php echo json_encode(__('Max allocatable memory (MB)')) ?>,
                            allowBlank : false,
                            disabled:true,
                            readOnly:true
                        }
                        ,{
                            xtype:'numberfield',
                            name       : 'freememory',
                            fieldLabel : <?php echo json_encode(__('Free memory (MB)')) ?>,
                            allowBlank : false,
                            disabled:true,
                            readOnly:true
                        }
                        ,{
                            xtype:'numberfield',
                            name       : 'mem',
                            fieldLabel : <?php echo json_encode(__('Memory size (MB)')) ?>,
                            allowBlank : false,                            
                            validator:function(val){                                
                                
                                var max_mem = this.ownerCt.find('name','maxmemory')[0];
                                var max_mem_value = max_mem.getValue();
                                if(max_mem_value)
                                    if(val <= parseFloat(max_mem_value)) return true;
                                else return <?php echo json_encode(__('Cannot exceed total allocatable memory size')) ?>;
                            }
                        },
                        {
                            xtype:'fieldset',title: <?php echo json_encode(__('CPUs')) ?>,
                            /*layout: 'hbox',
                            defaults: { border: false },*/
                            items:[
                                    /*{
                                        xtype:'fieldset',
                                        items: [*/
                                                {
                                                    name       : 'node_ncpus',
                                                    ref         : 'node_ncpus',
                                                    xtype      : 'numberfield',
                                                    fieldLabel : <?php echo json_encode(__('Total usable CPU')) ?>,
                                                    width:50,
                                                    allowBlank : false,
                                                    readOnly:true
                                                }
                                                ,{
                                                    xtype: 'numberfield',
                                                    fieldLabel: <?php echo json_encode(__('CPUs to use')) ?>,
                                                    name:"vcpu",
                                                    ref        : 'vcpu',
                                                    allowBlank:false,
                                                    allowNegative:false,
                                                    validator:function(v){

                                                        var max_cpu = (this.ownerCt).node_ncpus.getValue();
                                                        if(max_cpu)
                                                            if(v > max_cpu) return <?php echo json_encode(__('Cannot exceed max cpu')) ?>;
                                                            else return true;
                                                    },
                                                    listeners    : {
                                                            'change': function(f,n,o){
                                                                    var vcpu = (f.ownerCt).vcpu.getValue();
                                                                    // set sockets and cores by default
                                                                    if( (vcpu>2) && ((vcpu % 2) == 0) ){
                                                                        var half_vcpu = vcpu/2;
                                                                        (f.ownerCt).cpu_sockets.setValue(half_vcpu);
                                                                        (f.ownerCt).cpu_cores.setValue(half_vcpu);
                                                                    } else {
                                                                        (f.ownerCt).cpu_sockets.setValue(vcpu);
                                                                        (f.ownerCt).cpu_cores.setValue(1);
                                                                    }
                                                                    (f.ownerCt).cpu_threads.setValue(1);
                                                            }
                                                    },
                                                    scope:this,
                                                    width: 50
                                                },
                                        /*]
                                    },
                                    {
                                        xtype:'fieldset',
                                        items: [*/
                                                { xtype: 'spacer', height: '10' },
                                                {
                                                    xtype         : 'numberfield',
                                                    name          : 'cpu_sockets',
                                                    ref           : 'cpu_sockets',
                                                    fieldLabel    : <?php echo json_encode(__('Number of CPU sockets')) ?>,
                                                    width         : 50,
                                                    allowBlank    : false,
                                                    allowNegative :false,
                                                    vtype         : 'vm_vcpu_topology'
                                                    ,listeners    : {
                                                            'change': this.onVCPUChange
                                                    }
                                                }
                                                ,{
                                                    xtype         : 'numberfield',
                                                    name          : 'cpu_cores',
                                                    ref           : 'cpu_cores',
                                                    fieldLabel    : <?php echo json_encode(__('Number of cores per socket')) ?>,
                                                    width         : 50,
                                                    allowBlank    : false,
                                                    allowNegative : false,
                                                    vtype         : 'vm_vcpu_topology'
                                                    ,listeners    : {
                                                            'change': this.onVCPUChange
                                                    }
                                                }
                                                ,{
                                                    xtype         : 'numberfield',
                                                    name          : 'cpu_threads',
                                                    ref           : 'cpu_threads',
                                                    fieldLabel    : <?php echo json_encode(__('Number of threads per core')) ?>,
                                                    width         : 50,
                                                    allowBlank    : false,
                                                    allowNegative : false,
                                                    vtype         : 'vm_vcpu_topology'
                                                    ,listeners    : {
                                                            'change': this.onVCPUChange
                                                    }
                                                }
                                        /*]
                                    }*/
                                ]
                        }
                    ]//end items flex
                }
                ,{
                    flex:1,
                    id:'server-edit-config-right_panel'                    
                }
            ]};

        this.serverEditTabPanel.add(configuration);
        
    }
    ,loadNetworksPanel:function(){

        var networks = {id:'server-edit-networks',title: <?php echo json_encode(__('Network interfaces')) ?>,layout:'fit',autoScroll:true};

        this.serverEditTabPanel.add(networks);

        Ext.getCmp('server-edit-networks').on({
            beforerender:function(){
                    Ext.getBody().mask(<?php echo json_encode(__('Loading network interfaces...')) ?>);
            }
            ,activate:{
                scope:this,
                single: true,
                fn:function(p){
                    Ext.getCmp('server-edit-networks').load({
                        url:<?php echo json_encode(url_for('network/Network_ManageInterfacesGrid')); ?>
                        ,scripts:true
                        ,scope:this
                        ,callback:function(){
//                                alert("constroi "+this.server_id);
                            var grid = new Network.ManageInterfacesGrid({id:'server-edit-networks-grid',vm_type:this.getVmType(this.getForm().getValues()),server_id:this.server_id,level:'server', loadMask:true,border:false});
                            grid.on('render',function(){
                                this.store.load.defer(200,this.store);
                                Ext.getBody().unmask();
                            });
                            
                            Ext.getCmp('server-edit-networks').add(grid);
                            Ext.getCmp('server-edit-networks').doLayout();
                        }
                    });
            }}
            ,render:{scope:this,
                fn:function(p){

                    if(typeof Network !='undefined' && typeof Network.ManageInterfacesGrid !='undefined'){
//                        alert("constroi "+this.server_id);
                        var grid = new Network.ManageInterfacesGrid({id:'server-edit-networks-grid',vm_type:this.getVmType(this.getForm().getValues()),server_id:this.server_id,level:'server',loadMask:true,border:false});
                        grid.on('render',function(){
                            this.store.load.defer(200,this.store);
                            Ext.getBody().unmask();
                        });
                        
                        Ext.getCmp('server-edit-networks').add(grid);
                    }
                }}
        });// end on...

    }
    ,loadDevicesPanel: function(){

        var devices = {
            id:'server-edit-devices',
            title: <?php echo json_encode(__('Hosted Devices')) ?>,
            layout:'fit',
            autoScroll:true
        };

        this.serverEditTabPanel.add(devices);

        Ext.getCmp('server-edit-devices').on({
            beforerender:function(){
                Ext.getBody().mask(<?php echo json_encode(__('Loading hosted devices...')) ?>);
            }
            ,render:{
                scope:this
                ,fn:function(p){
                    Ext.getCmp('server-edit-devices').load({
                        url:<?php echo json_encode(url_for('server/Server_ManageDevices')); ?>
                        ,scripts:true
                        ,scope:this
                        ,callback:function(){
                            var panel = new Server.ManageDevices(
                            {
                                id:'server-edit-devices-panel',
                                server_id:this.server_id,level:'server', 
                                loadMask:true,border:false
                            }
                            );

                            panel.on('render', function(){
                                Ext.getBody().unmask();
                            });
                            
                            Ext.getCmp('server-edit-devices').add(panel);
                            Ext.getCmp('server-edit-devices').doLayout();
                        }
                    });
                }
            }
        });// end on...
    }
    ,loadDisksPanel:function(){

        var disks = {id:'server-edit-disks',title: <?php echo json_encode(__('Disks')) ?>,layout:'fit',autoScroll:true};

        this.serverEditTabPanel.add(disks);

        Ext.getCmp('server-edit-disks').on({
            beforerender:function(){
                    Ext.getBody().mask(<?php echo json_encode(__('Loading disks...')) ?>);
                },
            activate:{
                scope:this,
                single: true,
                fn:function(p){
                    Ext.getCmp('server-edit-disks').load({
                        url:<?php echo json_encode(url_for('logicalvol/Logicalvol_ManageDisksGrid')); ?>
                        ,scripts:true
                        ,scope:this
                        ,callback:function(){
                            var grid = new Logicalvol.ManageDisksGrid({id:'server-edit-disks-grid',vm_state:p.vmState,vm_type:this.getVmType(this.getForm().getValues()),server_id:this.server_id,node_id:this.node_id});
                            Ext.getCmp('server-edit-disks').add(grid);
                            Ext.getCmp('server-edit-disks').doLayout();
                        }
                    });
            }},
            render:{
                scope:this,
                fn:function(p){

                    if(typeof Logicalvol !='undefined' && typeof Logicalvol.ManageDisksGrid !='undefined'){
                        var grid = new Logicalvol.ManageDisksGrid({id:'server-edit-disks-grid',vm_state:p.vmState,vm_type:this.getVmType(this.getForm().getValues()),server_id:this.server_id,node_id:this.node_id});
                        Ext.getCmp('server-edit-disks').add(grid);
                    }
                }}
        });// end on...

    }
    /*
     * VNC OPTIONS
     */
    ,loadVNCOptions: function(data){
        // keymap combo
        var kcmb = new Setting.VNC.keymapCombo(); 
        
        if( data['vnc_keymap_default'] ){
            var default_map = data['keymap_default']
            if(default_map) kcmb.setValue(default_map);
            kcmb.disable();
        } else {
            kcmb.setValue(data['vnc_keymap']);
            kcmb.enable();
        }
            
        var fieldset = [];
        fieldset.push({
                            xtype:'hidden',name:'keymap_default', value: data['keymap_default']
                        },
                        {
                            xtype:'fieldset',title: <?php echo json_encode(__('VNC options')) ?>,
                            items:[
                                {xtype:'checkbox', name:'vnc_keymap_default',boxLabel: <?php echo json_encode(__('Use default keymap')) ?>
                                ,checked: data['vnc_keymap_default'] ? true : false
                                ,listeners:{
                                 'check':{scope:this,fn:function(cbox,ck){
                                            if(ck){
                                                var default_map = this.form.findField('keymap_default').getValue();
                                                if(default_map) kcmb.setValue(default_map);
                                                kcmb.disable();
                                            }else{
                                                kcmb.enable();
                                            }

                                    }
                                }}}
                                ,kcmb
                                ,{
                                    xtype:'displayfield', name:'vnc_keymap_tooltip',value: <?php echo json_encode(__('This changes will only take effect after restarting virtual machine.')) ?>
                                }

                            ]
                        });
        return fieldset;
    }
    ,loadOtherOptionsPanel: function(){
        var vncoptions = {
            id:'server-edit-otheroptions',
            title: <?php echo json_encode(__('Other options')) ?>,
            bodyStyle:'padding:5px'
            ,autoScroll:true 
            ,layout: {
                type: 'hbox',
                align: 'stretch'  // Child items are stretched to full width
            }            
            ,defaults:{layout:'form',bodyStyle:'padding:10px;',border:false}
            ,items: [
                {
                    flex:1,
                    id:'server-edit-otheroptions-left_panel'
                }
                ,{
                    flex:1,
                    items: [
                        {
                            xtype:'fieldset',title: <?php echo json_encode(__('Feature options')) ?>,
                            items:[
                                {
                                    xtype:'checkbox',
                                    name:'feature_acpi',
                                    boxLabel: <?php echo json_encode(__('ACPI')) ?>
                                }
                                ,{
                                    xtype:'checkbox',
                                    name:'feature_apic',
                                    boxLabel: <?php echo json_encode(__('APIC')) ?>
                                }
                                ,{
                                    xtype:'checkbox',
                                    name:'feature_pae',
                                    boxLabel: <?php echo json_encode(__('PAE')) ?>
                                }
                            ]
                        }
                    ]
                }
            ]
        };
        this.serverEditTabPanel.add(vncoptions);
    }
    ,loadVMHAPanel: function(){
        var vmhaoptions = {
            id:'server-edit-vmha',
            title: <?php echo json_encode(__('High Availability')) ?>,
            bodyStyle:'padding:5px'
            ,autoScroll:true 
            ,layout: {
                type: 'hbox',
                align: 'stretch'  // Child items are stretched to full width
            }            
            ,defaults:{layout:'form',bodyStyle:'padding:10px;',border:false}
            ,items: [
                    {
                        flex:1,
                        id:'server-edit-ha-right_panel',
                        items: [
                            {
                                id: 'server-priorityrestart-fieldset',
                                cls:'fieldset-top-sp',
                                xtype:'fieldset',
                                title: <?php echo json_encode(__('Priority to start/migrate')) ?>,
                                items: [
                                            {
                                                id: 'server-priority-ha-rg',
                                                xtype: 'radiogroup',
                                                columns: 1,
                                                items: [
                                                    {boxLabel: __('Disabled'), name: 'priority_ha', inputValue: 0},
                                                    {boxLabel: __('Low'), name: 'priority_ha', inputValue: 1},
                                                    {boxLabel: __('Medium'), name: 'priority_ha', inputValue: 2},
                                                    {boxLabel: __('High'), name: 'priority_ha', inputValue: 3}
                                                ]
                                            }
                                        ]
                            }
                        ]
                    }
                    ,{
                        flex:1,
                        id:'server-edit-ha-left_panel',
                        items: [
                            { xtype: 'hidden', name: 'server_hasha' },
                            {
                                id: 'server-hasha-fieldset',
                                cls:'fieldset-top-sp',
                                xtype:'fieldset',
                                collapsed: true,
                                checkboxToggle: true,
                                disabled: true,
                                title: <?php echo json_encode(__('VM High availability')) ?>,
                                items: [
                                            {
                                                xtype: 'numberfield',
                                                name: 'hbtimeout',
                                                fieldLabel: __('Timeout')
                                            }
                                        ]
                                ,listeners:{
                                    beforecollapse:{scope:this,fn:function(panel,anim){
                                        this.form.findField('server_hasha').setValue(0);
                                        panel.items.each(function(item,index,length){
                                                            item.setDisabled(true);
                                                        });
                                    }},
                                    beforeexpand:{scope:this,fn:function(panel,anim){
                                        this.form.findField('server_hasha').setValue(1);
                                        panel.items.each(function(item,index,length){
                                                            item.setDisabled(false);
                                                        });
                                    }},
                                    afterrender:{scope:this,fn:function(c){
                                        if( !c.hasHA ){
                                            Ext.getCmp('server-hasha-fieldset').collapse();
                                        } else {
                                            Ext.getCmp('server-hasha-fieldset').expand();
                                        }
                                    }}

                                }
                            }
                        ]
                    }
            ]
        };
        this.serverEditTabPanel.add(vmhaoptions);
    }
    ,loadRecord:function(data){
        this.load({url:'server/jsonLoad',params:data
            ,scope:this
            ,waitMsg: <?php echo json_encode(__('Retrieving data...')) ?>
            ,success:function(f,a){
                var data = a.result['data'];
                var form = this.getForm();

                var name = form.findField('name');
                form.findField('name').el.set({qtip: '' });
                /*
                 * load memory data
                 */
                var maxmemory = form.findField('maxmemory');
                if(data['node_maxmemory']){                    
                    maxmemory.setValue(parseInt(byte_to_MBconvert(data['node_maxmemory'],0,'floor')));
                }
                var freememory = form.findField('freememory');
                if(data['node_freememory']){                    
                    freememory.setValue(parseInt(byte_to_MBconvert(data['node_freememory'],0,'floor')));
                }
                    

                /*
                 * default values for cpu sockets, cores and threads
                 */
                if( !data['cpu_sockets'] || !data['cpu_cores'] || !data['cpu_threads'] ){
                    var vcpu = data['vcpu'] ? data['vcpu'] : 1;
                    form.findField('cpu_sockets').setValue(vcpu);
                    form.findField('cpu_cores').setValue(1);
                    form.findField('cpu_threads').setValue(1);
                }

                /*
                 * set virtual machine type in disk panel to use when rendered
                 */
                Ext.getCmp('server-edit-disks').vmType = this.getVmType(this.getForm().getValues());
                Ext.getCmp('server-edit-disks').vmState = data['vm_state'];

                //Ext.getCmp('server-edit-networks').vmType = data['vm_type'];
                Ext.getCmp('server-edit-networks').vmType = this.getVmType(this.getForm().getValues());

                /*
                 * set boot options based on vm_type
                 */
                Ext.getCmp('server-edit-config-right_panel').add(this.loadBootOptions(data));
                Ext.getCmp('server-edit-config-right_panel').doLayout();

                Ext.getCmp('server-edit-otheroptions-left_panel').add(this.loadVNCOptions(data));
                Ext.getCmp('server-edit-otheroptions-left_panel').doLayout();

                Ext.getCmp('server-priority-ha-rg').setValue(data.priority_ha);
                Ext.getCmp('server-hasha-fieldset').hasHA = data.hasHA;

                var hbtimeout = (data.hbtimeout) ? data.hbtimeout : <?php echo sfConfig::get('app_server_heartbeat_timeout_default'); ?>;
                form.findField('hbtimeout').setValue(hbtimeout);
                form.findField('server_hasha').setValue(data.hasHA);

                if( data.ga_state && data.heartbeat ){
                    Ext.getCmp('server-hasha-fieldset').setDisabled(false);
                } else {
                    Ext.getCmp('server-hasha-fieldset').setDisabled(true);
                }

                if(data['vm_type']!='pv'){
                    if( form.findField('cdromextra_ckb') && !form.findField('cdrom_ckb').getValue() ){
                        form.findField('cdrom_cb').setDisabled(true);
                    }
                    if( form.findField('cdromextra_ckb') && !form.findField('cdromextra_ckb').getValue() ){
                        form.findField('cdromextra_cb').setDisabled(true);
                    }
                }

                if( data['vm_state'] == 'running' ){
                    form.findField('name').setDisabled(true);
                    form.findField('name').el.set({qtip: <?php echo json_encode(__('Can\'t change the name with server running!')) ?>});
                    if(data['vm_type']!='pv'){
                        form.findField('mem').setDisabled(true);
                        form.findField('node_ncpus').setDisabled(true);
                        form.findField('vcpu').setDisabled(true);
                        form.findField('cpu_sockets').setDisabled(true);
                        form.findField('cpu_cores').setDisabled(true);
                        form.findField('cpu_threads').setDisabled(true);
                        form.findField('cdrom_ckb').setDisabled(true);
                        form.findField('cdromextra_ckb').setDisabled(true);
                    }
                }
                if( data['hasSnapshots'] ){
                    form.findField('name').setDisabled(true);
                    form.findField('name').el.set({qtip: <?php echo json_encode(__('Can\'t change the name when server has snapshots!')) ?>});
;
                }
                if(data['vm_type']=='pv'){
                    Ext.getCmp('server-edit-devices').setDisabled(true);
                    Ext.getCmp('server-edit-config-boot-locationurl').show();
                    Ext.getCmp('server-edit-config-boot-locationurl-text').show();
                    Ext.getCmp('server-edit-config-boot-pxe').hide();
                    Ext.getCmp('server-edit-config-boot-cdrom').hide();
                    Ext.getCmp('server-edit-fieldset-removable-media').hide();
                } else {
                    Ext.getCmp('server-edit-devices').setDisabled(false);
                    Ext.getCmp('server-edit-config-boot-locationurl').hide();
                    Ext.getCmp('server-edit-config-boot-locationurl-text').hide();
                    Ext.getCmp('server-edit-config-boot-pxe').show();
                    Ext.getCmp('server-edit-config-boot-cdrom').show();
                    Ext.getCmp('server-edit-fieldset-removable-media').show();
                }
            } 
        });
    }
    ,onSave:function(){
        
        var name = this.getForm().findField('name').getValue();
        var form_fieldvalues = this.getForm().getFieldValues();
        /*console.log('form_fieldvalues');
        console.log(form_fieldvalues);*/

        var form_values = this.getForm().getValues();
        /*console.log('form_values');
        console.log(form_values);*/

        var form_vm_os = form_values['vm_OS'] ? form_values['vm_OS'] : '';
        var vm_os = '';
        if( form_vm_os ){
            var match_os = (form_vm_os.toLowerCase()).match(/^linux/);
            if(match_os) vm_os = 'linux';
            else vm_os = 'windows';
        }

        var send_data = {'id'           : form_values['id'],
                         'name'         : form_values['name'],
                         'mem'          : form_values['mem'],
                         'description'  : form_values['description'],
                         'vcpu'         : form_values['vcpu'],
                         'cpu_sockets'  : form_values['cpu_sockets'],
                         'cpu_cores'    : form_values['cpu_cores'],
                         'cpu_threads'  : form_values['cpu_threads']
                         };

        if( vm_os ) send_data['vm_os'] = vm_os;

        send_data['priority_ha'] = form_values['priority_ha'];
        send_data['hbtimeout'] = form_fieldvalues['hbtimeout'];
        send_data['hasHA'] = form_fieldvalues['server_hasha'];

        send_data['vm_type'] = this.getVmType(form_values);

        /*
         * gather boot options and media
         *
         */

        send_data['autostart'] = form_values['autostart'] ? true : false;
        send_data['boot'] = form_values['boot_from'];
        send_data['cdrom'] = 0;
        send_data['cdromextra'] = null;
        send_data['location'] = null;

        if(send_data['boot']=='location'){
            send_data['location'] = form_values['location'];
        } else if( send_data['vm_type'] != 'pv' ){
            if( form_fieldvalues['cdrom_ckb'] ){
                send_data['cdrom'] = 1;
                send_data['location'] = '';
                if(send_data['boot']=='cdrom') send_data['location'] = form_values['cdrom_cb'];
                else{
                    if(form_values['cdrom_cb']){
                        send_data['cdrom'] = 1;
                        send_data['location'] = form_values['cdrom_cb'];
                    }
                }
            }
            if( form_fieldvalues['cdromextra_ckb'] ){
                send_data['cdromextra'] = '';
                if(form_values['cdromextra_cb']){
                    send_data['cdromextra'] = form_values['cdromextra_cb'];
                }
            }
        }
        
        /*
         * gather vnc options
         *
         */
        send_data['vnc_keymap'] = form_fieldvalues['vnc_keymap'];
        send_data['vnc_keymap_default'] = 0;
        if(form_fieldvalues['vnc_keymap_default']){
            send_data['vnc_keymap_default'] = 1;
            send_data['vnc_keymap'] = form_fieldvalues['keymap_default'];
        }

        // features
        var cFeatures = 0;
        var features = {};
        for(f in form_fieldvalues){
            if( f.match(/^feature_/) ){
                var fv = f.replace('feature_','');
                //features.push(fv);
                features[fv] = form_fieldvalues[f] ? 1 : 0;
                cFeatures++;
            }
        }
        if( cFeatures > 0 ){
            send_data['features'] = Ext.encode(features);
        } else {
            send_data['features'] = '';
        }

        /*
         * gather network interfaces info
         */
        var grid_networks = Ext.getCmp('server-edit-networks-grid');
        if(grid_networks){

            if(!grid_networks.isValid()){
            
                Ext.Msg.show({title: <?php echo json_encode(__('Error!')) ?>,
                    buttons: Ext.MessageBox.OK,
                    msg: <?php echo json_encode(__('Missing network interface data!')) ?>,
                    icon: Ext.MessageBox.ERROR});
                return false;
            }

            var networks=[];
            var nets_store = grid_networks.getStore();
            var i = 0;

            nets_store.each(function(f){
                    var data = f.data;
                    var insert = {
                        'port':i,
                        'vlan_id':data['vlan_id'],
                        'intf_model': Ext.isEmpty(data['intf_model']) ? '': data['intf_model'],
                        'mac':data['mac']};

                    networks.push(insert);
                    i++;
            });

            send_data['networks'] = networks;

        }

        var controllers_changed = false;
        /*
         * gather device info
         */
        var grid_devices = Ext.getCmp('server-edit-devices-grid');
        if(grid_devices){
            var devices = [];

            dev_store = grid_devices.getStore();
            dev_store.each(function(f){            
                var data = f.data;
                var insert = {
                    'type': data['type']
                    ,'idvendor': data['idvendor']
                    ,'idproduct': data['idproduct']
                    ,'description': data['description']
                    ,'bus': data['bus']
                    ,'slot': data['slot']
                    ,'function': data['function']
                    ,'controller': data['controller']
                };
                devices.push(insert);
                if( data['controller'] ){
                    controllers_changed = true;
                }
            });
            send_data['devices'] = devices;
        }

        /*
         * gather disk info
         */
        var grid_disks = Ext.getCmp('server-edit-disks-grid');
        if(grid_disks){
            
            var disks=[];
            var disks_store = grid_disks.getSelected().getStore();

            disks_store.each(function(f){
                    var data = f.data;
                    var insert = {'id':data['id'],'disk_type':data['disk_type']};

                    disks.push(insert);
            });
            send_data['disks'] = disks;
        }

        // check if need to reboot
        var need_to_reboot = false;
        if( form_values['vm_state'] == 'running' ){
            if( controllers_changed ){
                need_to_reboot = true;
            }
        }

        // process delete
        var conn = new Ext.data.Connection({
            listeners:{
                // wait message.....
                beforerequest:function(){
                    Ext.MessageBox.show({
                        title: <?php echo json_encode(__('Please wait...')) ?>,
                        msg: <?php echo json_encode(__('Saving virtual server...')) ?>,
                        width:300,
                        wait:true,
                        modal: false
                    });
                },// on request complete hide message
                requestcomplete:function(){Ext.MessageBox.hide();}
                ,requestexception:function(c,r,o){
                            Ext.MessageBox.hide();
                            Ext.Ajax.fireEvent('requestexception',c,r,o);}
            }
        });// end conn
                    
        console.log(send_data);
        conn.request({
            url: <?php echo json_encode(url_for('server/jsonEdit')) ?>,
            params: {
                server:Ext.encode(send_data)                
            },            
            scope:this,
            success: function(resp,opt) {

                var response = Ext.util.JSON.decode(resp.responseText);                

                Ext.ux.Logger.info(response['agent'],response['response']);

                if( need_to_reboot ){
                    Ext.Msg.show({
                        title: String.format(<?php echo json_encode(__('Server changes')) ?>),
                        buttons: Ext.MessageBox.OK,
                        msg: String.format(<?php echo json_encode(__('Some changes will take effect after next restart of virtual server {0}.')) ?>,name),
                        icon: Ext.MessageBox.INFO,
                        scope: this,
                        fn: function(btn){
                            this.ownerCt.fireEvent('onSave');
                        }
                    });
                } else {
                    this.ownerCt.fireEvent('onSave');
                }
            },
            failure: function(resp,opt) {
                
                var response = Ext.util.JSON.decode(resp.responseText);
                Ext.ux.Logger.error(response['agent'],response['error']);

                Ext.Msg.show({
                    title: String.format(<?php echo json_encode(__('Error {0}')) ?>,response['agent']),
                    buttons: Ext.MessageBox.OK,
                    msg: String.format(<?php echo json_encode(__('Unable to edit virtual server {0}!')) ?>,name),
                    icon: Ext.MessageBox.ERROR});

            }
        });// END Ajax request
    }
    ,onVCPUChange: function(f,n,o){
                            var nsockets = (f.ownerCt).cpu_sockets.getValue();
                            var ncores = (f.ownerCt).cpu_cores.getValue();
                            var nthreads = (f.ownerCt).cpu_threads.getValue();
                            var vcpu = nsockets * ncores * nthreads;
                            (f.ownerCt).vcpu.setValue(vcpu);
    }
});


Server.Edit.Window = function(config) {

    Ext.apply(this,config);

    Ext.apply(Ext.form.VTypes, {
                vm_vcpu_topology : function(val, field) {
                            var max_cpu = (field.ownerCt).node_ncpus.getValue();
                            var nsockets = (field.ownerCt).cpu_sockets.getValue();
                            var ncores = (field.ownerCt).cpu_cores.getValue();
                            var nthreads = (field.ownerCt).cpu_threads.getValue();
                            var vcpu = nsockets * ncores * nthreads;
                            if(max_cpu)
                                if(vcpu > max_cpu) return false;
                            return true;
                        },
                vm_vcpu_topologyText: <?php echo json_encode(__('Cannot exceed max cpu')) ?>
            });

    Server.Edit.Window.superclass.constructor.call(this, {
        width:800
        ,height:480
        ,border:false
        ,modal:true
        ,layout:'fit'
        ,items:[ new Server.Edit.Form({server_id:this.server_id,node_id:this.node_id})]       
    });

    

};


Ext.extend(Server.Edit.Window, Ext.Window,{
    tools:[{id:'help', qtip: __('Help'),handler:function(){View.showHelp({anchorid:'help_vmachine_edit',autoLoad:{ params:'mod=server'},title: <?php echo json_encode(__('Edit Server Help')) ?>});}}],
    loadData:function(data){
        this.items.get(0).loadRecord(data);
    }        
});

</script>
