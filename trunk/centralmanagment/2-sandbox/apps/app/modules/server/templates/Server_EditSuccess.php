<script>

Ext.ns('Server.Edit');

Server.Edit.Form = Ext.extend(Ext.form.FormPanel, {    
    border:false
    ,monitorValid:true   
    ,initComponent:function() {
        
        this.items = [
            {xtype:'hidden',name:'id'},            
            {xtype:'tabpanel', activeItem:0,
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
        

    }
    ,onRender:function(){
        // call parent
        Server.Edit.Form.superclass.onRender.apply(this, arguments);
        // set wait message target
        this.getForm().waitMsgTarget = this.getEl();       
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
       
        var fieldset_items = 
                        [
                            {
                                boxLabel: <?php echo json_encode(__('Auto-start')) ?>,
                                xtype:'checkbox', checked: (data['autostart']) ? true : false,
                                name:'autostart',inputValue:'1',ref:'autostart'
                            },
                            {
                                boxLabel: <?php echo json_encode(__('VM Filesystem')) ?>,
                                xtype:'radio', checked: (data['boot']=='filesystem') ? true : false,
                                name:'boot_from',inputValue:'filesystem',ref:'boot_filesystem'
                            }
                        ];
                  
        /*
        * if virtual machine is PV... load location item
        *
        */
        if(data['vm_type']=='pv'){
            fieldset_items.push(
                        {
                            boxLabel: 'Location URL',
                            xtype:'radio', checked: (data['boot']=='location') ? true : false,
                            inputValue: 'location',ref:'boot_location'
                            ,name: 'boot_from'
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
                            disabled:(data['boot']=='location') ? false : true,
                            validator:function(v){
                                if(!v) return 'needed';
                            },
                            value: (data['cdrom']==1) ? '': data['location']
                        }
            );            
            //end push
        }else{

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
                        }
                        ,{
                            boxLabel:'CD-ROM'
                            ,xtype:'radio'
                            ,inputValue:'cdrom',ref:'../boot_cdrom', checked: (data['boot']=='cdrom') ? true : false
                            ,name: 'boot_from'
                            ,scope:this
                            ,handler:function(box,check){
                                var cdrom_field = this.form.findField('cdrom_cb');
                                if(cdrom_field.getValue()=='') cdrom_field.validate();
                            }
                        }
                        
            );//end push
            

        }

        // populate cdrom combo items
        
        if(cdromcombo.getStore().getTotalCount()>0)
        {
            if(data['cdrom']) cdromcombo.setValue(data['location']);

        }
        else
        {
            cdromcombo.getStore().reload({scope:this,callback:function(){
                        var cb_store = cdromcombo.getStore();
                        if(cb_store)
                        {
                            cdromcombo.setValue('');
                            
                            if(data['cdrom'] || data['boot']=='cdrom')
                            {
                                var matched = cb_store.findExact('full_path',data['location']);

                                if(matched != -1) cdromcombo.setValue(data['location']);

                            }
                            
                        }


            }});
        }

        
        
        
        
        var fieldset = [
                        {
                            xtype:'fieldset',          
                            autoHeight:true,
                            title: <?php echo json_encode(__('Boot options')) ?>,
                            labelWidth:10,                            
                            items:fieldset_items
                        }
                    ];
        if(data['vm_type']!='pv'){
            fieldset.push(
                                {
                                    xtype:'fieldset',
                                    title: <?php echo json_encode(__('Removable media')) ?>,
                                    items:[cdromcombo]
                                }
                            );
        }
                    
        return fieldset;
        
    }
    ,loadConfigurationPanel:function(){


        // keymap combo
        var kcmb = new Setting.VNC.keymapCombo(); 
        
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
                            allowBlank:false,
                            allowNegative:false,
                            validator:function(v){

                                var max_cpu = (this.ownerCt).node_ncpus.getValue();
                                if(max_cpu)
                                    if(v > max_cpu) return <?php echo json_encode(__('Cannot exceed max cpu')) ?>;
                                    else return true;
                            },
                            scope:this,
                            width: 50
                        }
                        ,
                        /*
                         * VNC OPTIONS
                         */
                        {
                            xtype:'hidden',name:'keymap_default'
                        },
                        {
                            xtype:'fieldset',title: <?php echo json_encode(__('VNC options')) ?>,
                            items:[
                                {xtype:'checkbox', name:'vnc_keymap_default',boxLabel: <?php echo json_encode(__('Use default keymap')) ?>,listeners:{
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
                        }
                    ]//end items flex
                }
                ,{
                    flex:1,
                    id:'server-edit-config-right_panel'                    
                }
            ]};

        this.get(1).add(configuration);
        
    }
    ,loadNetworksPanel:function(){

        var networks = {id:'server-edit-networks',title: <?php echo json_encode(__('Network interfaces')) ?>,layout:'fit',autoScroll:true};

        this.get(1).add(networks);

        Ext.getCmp('server-edit-networks').on({
            beforerender:function(){
                    Ext.getBody().mask(<?php echo json_encode(__('Loading network interfaces...')) ?>);
            }
            ,render:{scope:this,
                fn:function(p){

                    if(typeof Network !='undefined' && typeof Network.ManageInterfacesGrid !='undefined'){
//                        alert("constroi "+this.server_id);
                        var grid = new Network.ManageInterfacesGrid({id:'server-edit-networks-grid',vm_type:p.vmType,server_id:this.server_id,level:'server',loadMask:true,border:false});
                        grid.on('render',function(){
                            this.store.load.defer(200,this.store);
                            Ext.getBody().unmask();
                        });
                        
                        Ext.getCmp('server-edit-networks').add(grid);

                    }else{

                        Ext.getCmp('server-edit-networks').load({
                            url:<?php echo json_encode(url_for('network/Network_ManageInterfacesGrid')); ?>
                            ,scripts:true
                            ,scope:this
                            ,callback:function(){
//                                alert("constroi "+this.server_id);
                                var grid = new Network.ManageInterfacesGrid({id:'server-edit-networks-grid',vm_type:p.vmType,server_id:this.server_id,level:'server', loadMask:true,border:false});
                                grid.on('render',function(){
                                    this.store.load.defer(200,this.store);
                                    Ext.getBody().unmask();
                                });
                                
                                Ext.getCmp('server-edit-networks').add(grid);
                                Ext.getCmp('server-edit-networks').doLayout();
                            }
                        });
                    }
                }}
        });// end on...

    }
    ,loadDisksPanel:function(){

        var disks = {id:'server-edit-disks',title: <?php echo json_encode(__('Disks')) ?>,layout:'fit',autoScroll:true};

        this.get(1).add(disks);

        Ext.getCmp('server-edit-disks').on({
            beforerender:function(){
                    Ext.getBody().mask(<?php echo json_encode(__('Loading disks...')) ?>);
                },
            render:{
                scope:this,
                fn:function(p){

                    if(typeof Logicalvol !='undefined' && typeof Logicalvol.ManageDisksGrid !='undefined'){
                        var grid = new Logicalvol.ManageDisksGrid({id:'server-edit-disks-grid',vm_state:p.vmState,vm_type:p.vmType,server_id:this.server_id,node_id:this.node_id});
                        Ext.getCmp('server-edit-disks').add(grid);

                    }else{

                        Ext.getCmp('server-edit-disks').load({
                            url:<?php echo json_encode(url_for('logicalvol/Logicalvol_ManageDisksGrid')); ?>
                            ,scripts:true
                            ,scope:this
                            ,callback:function(){
                                var grid = new Logicalvol.ManageDisksGrid({id:'server-edit-disks-grid',vm_state:p.vmState,vm_type:p.vmType,server_id:this.server_id,node_id:this.node_id});
                                Ext.getCmp('server-edit-disks').add(grid);
                                Ext.getCmp('server-edit-disks').doLayout();
                            }
                        });
                    }
                }}
        });// end on...

    }    
    ,loadRecord:function(data){
        
        this.load({url:'server/jsonLoad',params:data
            ,scope:this
            ,waitMsg: <?php echo json_encode(__('Retrieving data...')) ?>
            ,success:function(f,a){
                var data = a.result['data'];
                var form = this.getForm();
                

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
                 * set virtual machine type in disk panel to use when rendered
                 */
                Ext.getCmp('server-edit-disks').vmType = data['vm_type'];
                Ext.getCmp('server-edit-disks').vmState = data['vm_state'];

                Ext.getCmp('server-edit-networks').vmType = data['vm_type'];

                /*
                 * set boot options based on vm_type
                 */
                Ext.getCmp('server-edit-config-right_panel').add(this.loadBootOptions(data));                
                Ext.getCmp('server-edit-config-right_panel').doLayout();
                               
            }            
        });
    }
    ,onSave:function(){
        
        var form_values = this.getForm().getValues();        

        var send_data = {'id'           : form_values['id'],
                         'name'         : form_values['name'],
                         'mem'          : form_values['mem'],
                         'description'  : form_values['description'],
                         'vcpu'       : form_values['vcpu']};

        /*
         * gather boot options and media
         *
         */

        send_data['autostart'] = form_values['autostart'] ? true : false;
        send_data['boot'] = form_values['boot_from'];
        send_data['cdrom'] = 0;
        send_data['location'] = '';
        if(send_data['boot']=='cdrom') send_data['location'] = form_values['cdrom_cb'];
        else{
            if(form_values['cdrom_cb']){
                send_data['cdrom'] = 1;
                send_data['location'] = form_values['cdrom_cb'];
            }
        }

        if(send_data['boot']=='location') send_data['location'] = form_values['location'];
        
        /*
         * gather vnc options
         *
         */
        var default_keymap = this.getForm().findField('vnc_keymap_default');

        send_data['vnc_keymap'] = form_values['vnc_keymap'];
        send_data['vnc_keymap_default'] = 0;
        if(form_values['vnc_keymap_default']){
            send_data['vnc_keymap_default'] = 1;
            send_data['vnc_keymap'] = form_values['keymap_default'];
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
                    icon: Ext.MessageBox.INFO});
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

        /*
         * gather disk info
         */
        var grid_disks = Ext.getCmp('server-edit-disks-grid');
        if(grid_disks){
            
            if(!grid_disks.getSelected().isValid()){

                Ext.Msg.show({title: <?php echo json_encode(__('Error!')) ?>,
                    buttons: Ext.MessageBox.OK,
                    msg: <?php echo json_encode(__('Missing disk data!')) ?>,
                    icon: Ext.MessageBox.INFO});
                return false;
            }

            var disks=[];
            var disks_store = grid_disks.getSelected().getStore();

            disks_store.each(function(f){
                    var data = f.data;
                    var insert = {'id':data['id'],'disk_type':data['disk_type']};

                    disks.push(insert);
            });

            if(Ext.isEmpty(disks)){
                Ext.Msg.show({
                    title: String.format(<?php echo json_encode(__('Error {0}')) ?>,'<?php echo sfConfig::get('config_acronym'); ?>'),
                    buttons: Ext.MessageBox.OK,
                    msg: String.format(<?php echo json_encode(__('Unable to edit virtual server {0}!')) ?>,form_values['name'])+'<br>'+<?php echo json_encode(__('At least one disk is required!')) ?>,
                    icon: Ext.MessageBox.ERROR});
                return false;
                
            }
            else{
              send_data['disks'] = disks;
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
                    
                    
        conn.request({
            url: <?php echo json_encode(url_for('server/jsonEdit')) ?>,
            params: {
                server:Ext.encode(send_data)                
            },            
            scope:this,
            success: function(resp,opt) {

                var response = Ext.util.JSON.decode(resp.responseText);                

                Ext.ux.Logger.info(response['agent'],response['response']);
                this.ownerCt.fireEvent('onSave');                

            },
            failure: function(resp,opt) {
                
                var response = Ext.util.JSON.decode(resp.responseText);
                Ext.ux.Logger.error(response['agent'],response['error']);

                Ext.Msg.show({
                    title: String.format(<?php echo json_encode(__('Error {0}')) ?>,response['agent']),
                    buttons: Ext.MessageBox.OK,
                    msg: String.format(<?php echo json_encode(__('Unable to edit virtual server {0}!')) ?>,form_values['name'])+'<br>'+response['info'],
                    icon: Ext.MessageBox.ERROR});

            }
        });// END Ajax request
    }
});


Server.Edit.Window = function(config) {

    Ext.apply(this,config);

    Server.Edit.Window.superclass.constructor.call(this, {
        width:800
        ,height:450
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
