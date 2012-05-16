<script>
Ext.ns("Ovf.ImportWizard");
Ovf.ImportWizard.Cards = function(){

    ovfdetails_cardPanel = Ext.extend(Ext.ux.Wiz.Card, {
        initComponent:function(){

            var name_tpl = [
                    {
                        xtype:'displayfield',
                        fieldLabel: <?php echo json_encode(__('Product')) ?>,
                        name:'product'
                    },
                    {
                        xtype:'displayfield',
                        fieldLabel: <?php echo json_encode(__('Version')) ?>,
                        name:'version'
                    }
                    ,{
                        xtype:'displayfield',
                        fieldLabel: <?php echo json_encode(__('Vendor')) ?>,
                        name:'vendor'
                    }
                    ,{
                        xtype:'displayfield',
                        fieldLabel: <?php echo json_encode(__('Description')) ?>,
                        name:'info'
                    },
                    {
                        xtype:'displayfield',
                        fieldLabel: <?php echo json_encode(__('Approx. size')) ?>,
                        name:'ovf_size'
                    }
            ];

            Ext.apply(this, {
                title        : <?php echo json_encode(__('OVF details')) ?>,ref:'ovf_details',
                monitorValid : true,
                items : [{
                            border    : false,
                            bodyStyle : 'background:none;padding-bottom:20px;',
                            html      : <?php echo json_encode(__('OVF details')) ?>
                        },name_tpl
                    ]
            });



            ovfdetails_cardPanel.superclass.initComponent.call(this);

        }
        ,loadRecord:function(data){

            if(data['productUrl']) data['product'] = '<a title="'+data['productUrl']+'" target="_blank" href="'+data['productUrl']+'">'+data['product']+'</a>';
            if(data['vendorUrl']) data['vendor'] = '<a title="'+data['vendorUrl']+'" target="_blank" href="'+data['vendorUrl']+'">'+data['vendor']+'</a>';

            if(data['ovf_size']) data['ovf_size'] = Ext.util.Format.fileSize(data['ovf_size']);

            var form = this.getForm();
            var rec = new Object();
            rec.data = data;
            form.loadRecord(rec);
        }

    });



    ovfeula_cardPanel = Ext.extend(Ext.ux.Wiz.Card, {
        initComponent:function(){

            var name_tpl = [
                    {xtype:'hidden',ref:'license_state'},
                    {
                        xtype:'textarea',
                        preventMark:true,
                        readOnly:true,
                        ref:'license',
                        hideLabel:true,
                        anchor:'100% 75%',
                        validator:function(v){

                            var state = (this.ownerCt).license_state;
                            if(state.getValue()=='ok'){
                              return true;
                            }
                            else return <?php echo json_encode(__('Please agree to continue')) ?>},
                        name:'license'
                    }
                    ,{
                        xtype:'button'
                        ,text: <?php echo json_encode(__('Agree')) ?>
                        ,scope:this
                        ,handler:function(){
                            this.license_state.setValue('ok');                            
                        }
                    }
            ];

            Ext.apply(this, {
                title        : <?php echo json_encode(__('License agreement')) ?>,ref:'ovf_eula',
                monitorValid : true,
                items : [{
                            border : false,
                            hideLabel:true,
                            xtype:'displayfield',
                            style : 'background:none;padding-bottom:20px;',
                            name:'info'
                        },name_tpl
                    ]
            });


            ovfeula_cardPanel.superclass.initComponent.call(this);
            //Ext.Component.superclass.constructor.call(this);

        }
        ,loadRecord:function(data){

            var form = this.getForm();
            var rec = new Object();
            rec.data = data;
            form.loadRecord(rec);

        }

    });


    ovfname_cardPanel = Ext.extend(Ext.ux.Wiz.Card, {
        initComponent:function(){


            var kvm = {
                        cls:'fieldset-top-sp',xtype:'fieldset',hidden:true,ref:'kvm_panel',
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
                                name: 'vm_kvm_OS',
                                inputValue: 'Linux'
                              }
                              ,{
                                height:40,
                                hideLabel:true,
                                boxLabel: 'Windows',
                                name: 'vm_kvm_OS',
                                inputValue: 'Windows'
                               }
                        ]//end fieldset items
                    };
                    
            var xen = {
                        cls:'fieldset-top-sp',xtype:'fieldset',hidden:true,ref:'xen_panel',
                        title: <?php echo json_encode(__('Guest operating system')) ?>,
                        collapsible: false,
                        autoHeight:true,
                        labelWidth:10,
                        items:[

                            {
                                checked:true,
                                xtype:'radio',
                                ref:'linux_pv',
                                hideLabel:true,
                                boxLabel: 'Linux PV',
                                name: 'vm_xen_OS',
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
                                        ref:'../../linux_hvm',
                                        xtype:'radio',
                                        boxLabel: 'Linux HVM',
                                        name: 'vm_xen_OS',
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
                            hideLabel:true,
                            boxLabel: 'Windows',
                            ref:'windows_hvm',
                            name: 'vm_xen_OS',
                            inputValue: 'Windows'
                           }
                        ]//end fieldset items
                    };
                        
            var name_tpl = [
                    {
                        xtype:'hidden',
                        name:'hypervisor',
                        ref:'hypervisor'

                    },
                    {
                        xtype:'hidden',
                        name:'memory',
                        ref:'ovf_memory'

                    },
                    {
                        xtype:'hidden',
                        name:'node_memory',
                        ref:'node_memory'

                    },
                    {
                        xtype:'textfield',
                        ref:'name',
                        fieldLabel: <?php echo json_encode(__('Virtual server name')) ?>,
                        name:'name',
                        allowBlank : false,
                        invalidText : <?php echo json_encode(__('No spaces and only alpha-numeric characters allowed!')) ?>,
                        validator  : function(v){
                            var t = /^[a-zA-Z][a-zA-Z0-9\-\_]+$/;
                            return t.test(v);
                        }

                    },
                    {
                        xtype:'combo',ref:'nodes_cb',anchor:'90%',
                        emptyText: __('Select...'),fieldLabel: <?php echo json_encode(__('Destination node')) ?>,triggerAction: 'all',
                        selectOnFocus:true,forceSelection:true,editable:false,allowBlank:false,
                        name:'nodes_cb',hiddenName:'nodes_cb',valueField:'Id',displayField:'name',
                        store:new Ext.data.Store({
                                proxy:new Ext.data.HttpProxy({
                                    url:'node/JsonListCluster?initialize=1' 
                                }),
                                reader: new Ext.data.JsonReader({
                                            root:'data',
                                            fields:['Id',{name:'memfree',mapping:'Memfree'},{name:'name',mapping:'Name'},{name:'hypervisor',mapping:'Hypervisor'}]})
                                ,listeners:{load:{scope:this,fn:function(st, records){                                        

                                        if(!records.length)
                                            Ext.Msg.show({title: this.wizard.title,
                                                buttons: Ext.MessageBox.OK,
                                                msg: <?php echo json_encode(__('No destination nodes available')); ?>,
                                                icon: Ext.MessageBox.INFO});
                                        
                                }}}
                        })
                        ,listeners:{
                                select:{scope:this, fn:function(combo, record, index) {                                    
                                    var node_id = record.get('Id');
                                    var conf = {'id':node_id, 'level':'node'};
                                    Ext.getCmp('ovfnetwork_cardPanel').confGrid(conf);

                                    this.hypervisor.setValue(record.get('hypervisor'));
                                    this.node_memory.setValue(record.get('maxmem'));

                                    if(record.get('hypervisor')=='xen'){
                                        this.kvm_panel.hide();
                                        this.xen_panel.linux_hvm.disable();
                                        this.xen_panel.windows_hvm.hide();
                                        this.xen_panel.linux_pv.setValue(true);
                                        this.xen_panel.show();
                                    }

                                    if(record.get('hypervisor')=='hvm+xen'){
                                        this.kvm_panel.hide();
                                        this.xen_panel.linux_hvm.enable();
                                        this.xen_panel.windows_hvm.show();
                                        this.xen_panel.show();
                                        
                                    }
                                    
                                    if(record.get('hypervisor')=='kvm'){
                                        this.xen_panel.hide();                                                                                
                                        this.kvm_panel.show();
                                    }
                                }}
                        }//end listeners
                    }
                    ,xen
                    ,kvm
            ];

            Ext.apply(this, {
                title        : <?php echo json_encode(__('Name and location')) ?>,ref:'ovf_name',
                monitorValid : true,
                labelWidth:140,                
                items : [{
                            border : false,
                            hideLabel:true,
                            xtype:'displayfield',
                            style : 'background:none;padding-bottom:20px;',
                            html      : <?php echo json_encode(__('Name and location')) ?>
                        },name_tpl
                    ]
                ,listeners:{
                    beforehide:function(cp){
                        if(cp.hasLoaded) return true;
                        else return false;
                    },                    
                    nextclick:function(){

                        this.hasLoaded = true;

                        var msg = '';
                        var vm_mem = parseInt(this.ovf_memory.getValue());
                        var node_mem = parseInt(this.node_memory.getValue());
                        
                        if(vm_mem > node_mem){
                            this.hasLoaded = false;                            
                            msg = String.format(<?php echo json_encode(__('Destination node does not have enought memory available ({0}) to import VM with {1}')) ?>,Ext.util.Format.fileSize(node_mem), Ext.util.Format.fileSize(vm_mem))+'<br>';
                        }
                                                                                                    
                        var ovf_storage = this.wizard.cardPanel.ovf_storage;
                        var ovf_storage_node = ovf_storage.form.findField('node');
                        var cb = this.form.findField('nodes_cb');
                        ovf_storage.vgcombo.store.baseParams = {'nid':cb.getValue()};

                        if(ovf_storage_node.getValue()!=cb.getValue()) ovf_storage.cleanRecords();

                        ovf_storage_node.setValue(cb.getValue());

                        
                        var form_data = this.getSubmitData();
                        var vm_type = form_data['vm_type'];                        

                        /*
                         * check networks type
                         *
                         */
                        
                        var interfaces =
                        <?php
//                            /*
//                             * build interfaces model dynamic
//                             */
                            $interfaces_drivers = sfConfig::get('app_interfaces');
                            $interfaces_model = array();
                            
                            foreach($interfaces_drivers as $hyper =>$models)
                            {
                                $interfaces_elem = array();
                                foreach($models as $model)
                                {
                                    $interfaces_elem[] = json_encode($model);                                                                        
                                }
                                $elems = '['.implode(',',$interfaces_elem).']';
                                $interfaces_model[] = json_encode($hyper).':'.$elems;

                            }

                            echo '{'.implode(',',$interfaces_model).'}'."\n";

                        ?>;

                        var vm_interfaces = interfaces[vm_type] ? interfaces[vm_type] : '';                        

                        var ovf_networks = this.wizard.cardPanel.ovf_networks;
                        var networks_store = ovf_networks.ovfnetworks_grid.getStore();
                        var network_compat = 1;

                        networks_store.each(function(f){
                            var data = f.data;
                            var indexOf = vm_interfaces.indexOf(data['intf_model']);
                            
                            if(indexOf==-1 && data['intf_model']!='') network_compat = 0;
                            else if(data['intf_model']=='' && vm_type!='pv') data['intf_model'] = 'rtl8139';
                            
                        });
                        
                        if(!network_compat){
                            this.hasLoaded = false;
                            msg += <?php echo json_encode(__('VM type selected does not support network interfaces drivers defined in OVF.')) ?>+'<br>';
                        }
                        

                        /*
                         * check disks type
                         *
                         */

                        var disks =
                        <?php
                            /*
                             * build disks model dynamic
                             */
                            $disks_drivers = sfConfig::get('app_disks');
                            $disks_model = array();

                            foreach($disks_drivers as $hyper =>$models)
                            {
                                $disks_elem = array();
                                foreach($models as $model)
                                {
                                    $disks_elem[] = json_encode($model);
                                }
                                $elems = '['.implode(',',$disks_elem).']';
                                $disks_model[] = json_encode($hyper).':'.$elems;

                            }

                            echo '{'.implode(',',$disks_model).'}'."\n";

                        ?>;

                        var vm_disks = disks[vm_type] ? disks[vm_type] : '';                        

                        var ovf_disks = this.wizard.cardPanel.ovf_storage;
                        var disks_store = ovf_disks.ovfstorage_grid.getStore();
                        var disks_compat = 1;

                        disks_store.each(function(f){
                            var data = f.data;                            
                            var indexOf = vm_disks.indexOf(data['bus']);

                            if(indexOf==-1){
                                disks_compat = 0;
                            }
                        });

                        if(!disks_compat){
                            this.hasLoaded = false;                            
                            msg += <?php echo json_encode(__('VM type selected does not support disk drivers defined in OVF.')) ?> +'<br>';
                        }

                        if(!this.hasLoaded)
                        {
                            Ext.Msg.show({
                                title: String.format(<?php echo json_encode(__('Error {0}')) ?>,'<?php echo sfConfig::get('config_acronym'); ?>'),
                                buttons: Ext.MessageBox.OK,
                                msg: msg+ <?php echo json_encode(__('Could not continue!')) ?>,
                                icon: Ext.MessageBox.ERROR});
                            
                        }                        

                    }
                    ,previousclick:function(){
                        this.hasLoaded = true;
                    }
                }
            });


            ovfname_cardPanel.superclass.initComponent.call(this);

        }
        ,getSubmitData:function(){
            var form_values = this.form.getValues();
            var hypervisor = this.hypervisor.getValue();
            var vm_type = '';
            var vm_os = '';
            var description = '';
            switch(hypervisor){
                case 'xen' :
                                vm_type = 'pv';                                                                
                                description = form_values['vm_xen_OS'];
                                break;
                case 'hvm+xen' :
                                if(this.xen_panel.linux_pv.checked) vm_type = 'pv';
                                else vm_type = 'hvm';
                                description = form_values['vm_xen_OS'];
                                break;
                case 'kvm':
                            vm_type = 'kvm';                            
                            description = form_values['vm_kvm_OS'];
                            break;
                default:
                            break;
                            
            }

            var match_os = (description.toLowerCase()).match(/^linux/);
            if(match_os) vm_os = 'linux';
            else vm_os = 'windows';

            return {'name':this.name.getValue(),'vm_os':vm_os,'description':description,'vm_type':vm_type,'node_id':this.nodes_cb.getValue()};
        }
        ,loadRecord:function(data){

            var form = this.getForm();
            var rec = new Object();
            rec.data = data;
            form.loadRecord(rec);

        }

    });


    ovfstorage_cardPanel = Ext.extend(Ext.ux.Wiz.Card, {
        initComponent:function(){
            this.vgsizefree = [];
            this.vgmaxsize = [];

            var vgstore = new Ext.data.JsonStore({
                root:'data'
                ,totalProperty:'total'
                ,fields:[
                    {name:'name', type:'string'}
                    ,{name:'size', mapping:'value', type:'string'}
                ]
                ,url:<?php echo json_encode(url_for('volgroup/jsonListFree'))?>
                ,baseParams:{'nodisk':true}
                ,listeners:{
                    'loadexception':function(store,options,resp,error){

                        if(resp.status==401) return;

                        var response = Ext.util.JSON.decode(resp.responseText);

                        Ext.Msg.show({
                            title: <?php echo json_encode(__('Error!')) ?>,
                            buttons: Ext.MessageBox.OK,
                            msg: response['error'],
                            icon: Ext.MessageBox.ERROR});
                    }
                }

            });

            this.vgcombo = new Ext.form.ComboBox({
                editable:false,
                valueField:'name'
                //,hiddenName:'vm_vg'
                //,name:'vm_vg'
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
                    select:{scope:this, fn:function(combo, record, index) {

                        this.vgmaxsize[record.get('name')] = record.get('size');
                        if(!this.vgsizefree[record.get('name')]) this.vgsizefree[record.get('name')] = record.get('size');

                    }}                    
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
                ,bbar:new Ext.ux.grid.TotalCountBar({
                    store:vgstore
                    ,displayInfo:true
                })
                ,anchor:'80%'
            });// end vgcombo

            var cm = new Ext.grid.ColumnModel([
                new Ext.grid.RowNumberer(),
                {
                    header: "File name",
                    dataIndex: 'href',
                    width: 130
                },
                {
                    header: "Disk capacity",
                    dataIndex: 'capacity',
                    allowBlank: false,
                    width: 120
                    ,renderer: function(val){return '<span ext:qtip="'+__('Drag and Drop to reorder')+'">' + Ext.util.Format.fileSize(val) + '</span>';}
                },
                {
                    header: "Volume Group",
                    dataIndex: 'vg',
                    editor:this.vgcombo,
                    allowBlank: false,
                    width: 120,
                    renderer:function(value,meta,rec){
                        if(!value){ return '<span ext:qtip="'+__('Drag and Drop to reorder')+'">' + String.format('<b>{0}</b>',<?php echo json_encode(__('Choose volume group')) ?>) + '</span>';}
                        else{ rec.commit(true); return value;}
                    }
                },
                {
                    header: "Logical name",
                    dataIndex: 'lv',
                    allowBlank: false,
                    editor: new Ext.form.TextField({
                        allowBlank: false
                    }),
                    width: 120
                    ,renderer: function(val){return '<span ext:qtip="'+__('Click to edit')+'">' + val + '</span>';}
                }
            ]);

            var grid = new Ext.grid.EditorGridPanel({
                ref:'ovfstorage_grid',forceValidation:true,
                store: new Ext.data.Store({
                    reader: new Ext.data.JsonReader({
                                fields:['disk_id','href','capacity','bus','vg','lv']})
                }),
                autoScroll: true,
                layout:'fit',
                anchor:'100% 75%',
                cls:'gridWrap',
              //  ddGroup: 'testDDGroup',
                enableDragDrop: false,
                cm: cm,
                viewConfig:{
                    forceFit:true,
                    emptyText: __('Empty!'),  //  emptyText Message
                    deferEmptyText:false
                },
                clicksToEdit:1,
                forceValidation:true,
                sm: new Ext.grid.RowSelectionModel({
                    singleSelect: true,
                    moveEditorOnEnter:false
                })
                ,listeners: {
                    afteredit:function(obj){

                        var storage_status = (this.ownerCt).form.findField('storage_status');
                        var storage_vgsmaxsize = (this.ownerCt).vgmaxsize;
                        var storage_vgsizefree = (this.ownerCt).vgsizefree;
                        var valid = true;


                        if(obj.originalValue)
                            storage_vgsizefree[obj.originalValue] = parseInt(obj.record.data['capacity']) + parseInt(storage_vgsizefree[obj.originalValue]);

                        storage_vgsizefree[obj.record.data['vg']] = parseInt(storage_vgsizefree[obj.record.data['vg']]) - parseInt(obj.record.data['capacity']);


                        var rows = this.store.getCount();
//
                        var r;
                        for(r = 0; r < rows; r++)
                        {
                            var rec = this.store.getAt(r);
                            var vg = rec.data['vg'];
                            if(storage_vgsizefree[vg] < 0){
                                storage_status.setValue(<?php echo json_encode(__('Cannot exceed total volume group size')) ?>);
                                return false;
                            }
                        }

                        valid = this.isValid();
                        return valid;

                    },

                    render: function(g) {
                        // Best to create the drop target after render, so we don't need to worry about whether grid.el is null

                        // constructor parameters:
                        //    grid (required): GridPanel or EditorGridPanel (with enableDragDrop set to true and optionally a value specified for ddGroup, which defaults to 'GridDD')
                        //    config (optional): config object
                        // valid config params:
                        //    anything accepted by DropTarget
                        //    listeners: listeners object. There are 4 valid listeners, all listed in the example below
                        //    copy: boolean. Determines whether to move (false) or copy (true) the row(s) (defaults to false for move)
                        var ddrow = new Ext.ux.dd.GridReorderDropTarget(g, {
                            copy: false
                            ,listeners: {
                                beforerowmove: function(objThis, oldIndex, newIndex, records) {
                                    // code goes here
                                    // return false to cancel the move
                                }
                                ,afterrowmove: function(objThis, oldIndex, newIndex, records) {
                                    g.getView().refresh();
                                    // code goes here
                                }
                                ,beforerowcopy: function(objThis, oldIndex, newIndex, records) {
                                    // code goes here
                                    // return false to cancel the copy
                                }
                                ,afterrowcopy: function(objThis, oldIndex, newIndex, records) {
                                    // code goes here
                                }
                            }
                        });

                        // if you need scrolling, register the grid view's scroller with the scroll manager
                        Ext.dd.ScrollManager.register(g.getView().getEditorParent());
                    }
                    ,beforedestroy: function(g) {
                        // if you previously registered with the scroll manager, unregister it (if you don't it will lead to problems in IE)
                        Ext.dd.ScrollManager.unregister(g.getView().getEditorParent());
                    }
                }// end listeners

            });// end mac_vlan_grid


            grid.getStore().on('load',function(){this.cleanRecords();},this);

            Ext.apply(grid, {

                isCellValid:function(col, row) {

                    var record = this.store.getAt(row);
                    if(!record) {
                        return true;
                    }

                    var field = this.colModel.getDataIndex(col);                    
                    if(!record.data[field]) return false;
                    return true;
                },
                isValid:function() {


                    var storage_status = (this.ownerCt).form.findField('storage_status');
                    var cols = this.colModel.getColumnCount();
                    var rows = this.store.getCount();
                    if(rows==0) return false;

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

                    if(!valid) storage_status.setValue(<?php echo json_encode(__('Storage mapping incomplete!')) ?>);
                    else storage_status.setValue(String.format(<?php echo json_encode(__('Storage mapping completed!')) ?>,rows));

                    return valid;
                }

            });

            var tpl = [
                    {xtype:'hidden',name:'node'},
                    grid,
                    {
                        xtype:'textfield',
                        name:'storage_status',
                        cls: 'nopad-border',
                        readOnly:true,
                        anchor:'90%',
                        hideLabel:true,
                        value : <?php echo json_encode(__('Storage mapping incomplete!')) ?>,
                        invalidText : <?php echo json_encode(__('Storage mapping incomplete!')) ?>,
                        allowBlank : false,
                        validator  : function(v){
                            return v== <?php echo json_encode(__('Storage mapping completed!')) ?>;
                        }
                    }
            ];

            Ext.apply(this, {
                title        : <?php echo json_encode(__('Storage')) ?>,ref:'ovf_storage',
                monitorValid : true,
                items : [{
                            border : false,
                            hideLabel :true,
                            xtype :'displayfield',
                            style : 'background:none;padding-bottom:20px;',
                            html: <?php echo json_encode(__('Storage')) ?>
                        },tpl
                    ]
            });

            ovfstorage_cardPanel.superclass.initComponent.call(this);
            //Ext.Component.superclass.constructor.call(this);

        }
        ,cleanRecords:function(){

            this.vgsizefree = [];
            this.vgmaxsize = [];

            var store = this.ovfstorage_grid.getStore();

            store.each(function(rec){
                rec.data['vg'] = '';
                rec.commit();
            });

            this.ovfstorage_grid.isValid();
            this.vgcombo.lastQuery = null;

        }
        ,getSubmitData:function(){

            var disks = new Object();
            var store = this.ovfstorage_grid.getStore();
            store.each(function(f){
                var data = f.data;
                var id = data['disk_id'];
                disks[id] ={
                    'disk_id':id,
                    'vg':data['vg'],
                    'lv':data['lv'],
                    'size':data['capacity']+'B'
                };
            });


            return {'disks':disks};
        }
        ,loadRecord:function(data){

            var form = this.getForm();
            var records = [];

            for(var item in data){                
                if(data[item]['href'])
                {
                    data[item]['lv'] = data[item]['href'].replace(/.([a-z])+$/,'');
                    records.push(data[item]);
                }
            }

            this.ovfstorage_grid.getStore().loadData(records);
        }

    });



    ovfnetwork_cardPanel = Ext.extend(Ext.ux.Wiz.Card, {id:'ovfnetwork_cardPanel',
        initComponent:function(){

           
            Ext.apply(this, {
                title        : <?php echo json_encode(__('Network interfaces')) ?>,ref:'ovf_networks',
                monitorValid : true,
                items : [{
                            border : false,
                            hideLabel:true,
                            xtype:'displayfield',
                            style : 'background:none;padding-bottom:20px;',
                            html      : <?php echo json_encode(__('Network interfaces')) ?>
                        }
                        ,{ref:'ovfnetworks_panel',layout:'fit',border:true,anchor:'100% 75%'}
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
            });


            ovfnetwork_cardPanel.superclass.initComponent.call(this);
            
            this.ovfnetworks_panel.on({                   
                render:{scope:this,
                    fn:function(p){
                        if(typeof Network !='undefined' && typeof Network.ManageInterfacesGrid !='undefined'){
                            var grid = this.addGrid();
                            this.ovfnetworks_panel.add(grid);                            

                        }else{
                            this.ovfnetworks_panel.load({
                                url:<?php echo json_encode(url_for('network/Network_ManageInterfacesGrid')); ?>
                                ,scripts:true
                                ,scope:this
                                ,callback:function(){

                                    //var grid = this.addGrid();

                                    //this.ovfnetworks_panel.add(grid);
                                    //this.ovfnetworks_panel.doLayout();
                                }//end callback
                            });
                        }
                    }}//end fn
            });// end on...

        }
        ,confGrid:function(conf){
            var grid = this.addGrid(conf);
            this.ovfnetworks_panel.add(grid);                                       
            this.ovfnetworks_panel.doLayout();

            (this.ovfnetworks_grid).getStore().loadData(this.nets);


        }
        ,addGrid:function(conf){
            var gridConf = {ref:'../ovfnetworks_grid',vm_type:'pv',border:false};
            if(conf){
                gridConf.node_id = conf.id;
                gridConf.level = conf.level;
            }

            var grid = new Network.ManageInterfacesGrid(gridConf);

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
        ,getSubmitData:function(){


            var networks = [];
            var store = this.ovfnetworks_grid.getStore();
            var i = 0;
            store.each(function(f){
                var data = f.data;
                networks.push({
                    'port':i,
                    'vlan_id':data['vlan_id'],
                    'intf_model':data['intf_model'],
                    'mac':data['mac']
                });
                i++;
            });


            return {'networks':networks};
        }
        ,loadRecord:function(data){

            var records = new Object();
            records.data = [];

            for(var i=0,len=data.length;i<len;i++)
                records.data.push(data[i]);
            this.nets = records;
                   
//            (this.ovfnetworks_grid).getStore().loadData(records);                       
        }

    });


    ovfload_cardPanel = Ext.extend(Ext.ux.Wiz.Card, {
        initComponent: function(){
            this.ovf_eula = '';

            var name_tpl = [

                            {
                            boxLabel: <?php echo json_encode(__('URL (http,ftp,...)')) ?>,
                            checked: true,
                            hideLabel:true,xtype:'radio',
                            name:'ovf_location',
                            inputValue: 'url',
                            scope:this,
                            handler:function(box,check){
                                var remote_field = this.form.findField('vm_location_remote');
                                remote_field.setDisabled(!check);
                                remote_field.clearInvalid();}
                        },{
                            xtype: 'textfield',
                            name: 'ovf_location_url',
                            ref: 'ovf_location_url',
                            hideLabel:true,
                            anchor:'90%',
                            allowBlank:false,
                            emptyText : 'url://path/to/ovf file'
                            ,labelStyle : 'font-size:11px;width:25px;'
                        }
                         ];
            Ext.apply(this, {
                title        : <?php echo json_encode(__('Source OVF file')) ?>,ref:'ovf_source',
                monitorValid : true,
                items : [{
                            border    : false,
                            bodyStyle : 'background:none;padding-bottom:20px;',
                            html      : <?php echo json_encode(__('Source OVF file')) ?>
                        },name_tpl
                    ]
                ,listeners:{
                    beforehide:function(cp){                        
                        if(cp.hasLoaded) return true;
                        else return false;
                    },
                    nextclick:function(card){
                        this.hasLoaded = false;
                        this.loadOvfDescriptor(card.ovf_location_url.getValue());
                    }
                    ,
                    previousclick:function(){
                        this.hasLoaded = true;
                    }
                }

            });

            ovfload_cardPanel.superclass.initComponent.call(this);
            Ext.Component.superclass.constructor.call(this);
        }
        ,getSubmitData:function(){
            return {'url':this.ovf_location_url.getValue()};
        }
        ,loadOvfDescriptor:function(url){

            this.form.load({
                    url: 'ovf/jsonLoadDescriptor'
                    ,params:{'ovf_location_url':url}
                    //,method:'POST'
                    ,scope:this
                    ,success: function(form, action) {
                        
                        this.hasLoaded = true;
                        var result = action.result;
                        var data = result.data;

                        var next_index = this.getNext();

                        /*
                         * load ovf details
                         */
                        var ovf_details = this.wizard.cardPanel.ovf_details;
                        ovf_details.loadRecord(data['ovf_details']);


                        /*
                         * load EULA
                         */
                        if(data['ovf_eula']['license']){

                            if(!this.ovf_eula) this.ovf_eula = new ovfeula_cardPanel();
                            this.ovf_eula.loadRecord(data['ovf_eula']);
                            this.ovf_eula.license_state.setValue('');
                            next_index++;
                            this.wizard.addCard(next_index,this.ovf_eula);

                        }else{
                            if(this.ovf_eula){
                                this.wizard.removeCard(this.ovf_eula);
                                this.ovf_eula = '';
                            }
                        }

                        /*
                         * load name
                         */                        
                        var ovf_name = this.wizard.cardPanel.ovf_name;
                        ovf_name.loadRecord(data['ovf_name']);

                        /*
                         * load storage
                         */                        
                        var ovf_storage = this.wizard.cardPanel.ovf_storage;                        
                        ovf_storage.loadRecord(data['ovf_storage']);


                        /*
                         * load networks
                         */                        
                        var ovf_networks = this.wizard.cardPanel.ovf_networks;
                        ovf_networks.loadRecord(data['ovf_networks']);                        

                        this.wizard.cardPanel.getLayout().setActiveItem(ovf_details.id);


                    },
                    failure: function(form, action) {
                        switch (action.failureType) {
                            case Ext.form.Action.CLIENT_INVALID:
                                Ext.Msg.alert(<?php echo json_encode(__('Info')) ?>, <?php echo json_encode(__('Form fields may not be submitted with invalid values!')) ?>);
                                break;
                            case Ext.form.Action.CONNECT_FAILURE:                                

                                if(action.response.responseText){
                                    var response = Ext.util.JSON.decode(action.response.responseText);

                                    Ext.Msg.show({title: String.format(<?php echo json_encode(__('Error {0}')) ?>,response['agent']),
                                        buttons: Ext.MessageBox.OK,
                                        msg: response['info'],
                                        icon: Ext.MessageBox.ERROR});
                                }
                                else Ext.Msg.alert(<?php echo json_encode(__('Info')) ?>, <?php echo json_encode(__('Communication failed!')) ?>);

                                break;
                            case Ext.form.Action.SERVER_INVALID:
                                Ext.Msg.show({title: String.format(<?php echo json_encode(__('Error {0}')) ?>,action.result.agent),
                                    buttons: Ext.MessageBox.OK,
                                    msg: action.result.info,
                                    icon: Ext.MessageBox.ERROR});
                        }
                       // this.form.findField('passwordStatus').setValue(<?php // echo json_encode(__('Error!')) ?>);
                    }
                    ,waitMsg:<?php echo json_encode(__('Loading OVF...')) ?>
                });
        }

    });





    var cards = [new Ext.ux.Wiz.Card({
                    title : <?php echo json_encode(__('Welcome')) ?>,
                    defaults     : {
                        labelStyle : 'font-size:11px;width:140px;'
                    },
                    items : [{
                            border    : false,
                            bodyStyle : 'background:none;',
                            html      : <?php echo json_encode(__('Welcome to the OVF import wizard.<br>The following steps will assist you on importing a virtual machine.')) ?>
                        }]
                })];
    cards.push(new ovfload_cardPanel());
    cards.push(new ovfdetails_cardPanel());
    cards.push(new ovfname_cardPanel());
    cards.push(new ovfstorage_cardPanel());
    cards.push(new ovfnetwork_cardPanel());
    //cards.push(new ovfeula_cardPanel());

    cards.push(// finish card with finish-message
            new Ext.ux.Wiz.Card({
                title        : <?php echo json_encode(__('Finished!')) ?>,
                monitorValid : true,
                items : [{
                        border    : false,
                        bodyStyle : 'background:none;',
                        html      : <?php echo json_encode(__('Thank you! Your data has been collected.<br>When you click on the "Finish" button, the virtual server will be imported.')) ?>
                    }]
            }));

    return cards;
};

Ovf.ImportWizard.Save = function(wizard) {

    var wizPanel = wizard.cardPanel;
    var ovf_source = wizPanel.ovf_source;
    var ovf_source_data = ovf_source.getSubmitData();
    var ovf_url = ovf_source_data.url;

    var ovf_name = wizPanel.ovf_name;
    var ovf_name_data = ovf_name.getSubmitData();
    var name = ovf_name_data.name;
    var description = ovf_name_data.description;
    var vm_type = ovf_name_data.vm_type;
    var vm_os = ovf_name_data.vm_os;
    var node_id = ovf_name_data.node_id;


    var ovf_storage = wizPanel.ovf_storage;
    var ovf_storage_data = ovf_storage.getSubmitData();
    var disks = ovf_storage_data.disks;

    var ovf_networks = wizPanel.ovf_networks;
    var ovf_networks_data = ovf_networks.getSubmitData();
    var networks = ovf_networks_data.networks;


    var send_data = {
            'url': ovf_url
            ,'name': name
            ,'description': description
            ,'vm_type': vm_type
            ,'vm_os': vm_os
            ,'disks': disks
            ,'networks':networks
        };



    var conn = new Ext.data.Connection({
            listeners:{
                // wait message.....
                beforerequest:function(){
                    Ext.MessageBox.show({
                        title: <?php echo json_encode(__('Please wait...')) ?>,
                        msg: <?php echo json_encode(__('Importing virtual server...')) ?>,
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
        url: <?php echo json_encode(url_for('ovf/jsonImport',false)); ?>,
        params: {'nid':node_id,'import': Ext.encode(send_data)},      
        scope: this,
        success: function(resp,opt) {
            var response = Ext.decode(resp.responseText);
            var sid = response['insert_id'];
            var tree_id = 's'+ sid;
            wizard.close();
            
            Ext.ux.Logger.info(response['agent'],response['response']);

            Ext.getCmp('view-nodes-panel').addNode({parentNode:node_id,id: tree_id,leaf:true,type:'server',text: send_data['name'],
                url: <?php echo json_encode(url_for('server/view?id=',false)) ?>+sid});


        },
        failure: function(resp,opt) {
            var response = Ext.decode(resp.responseText);

            if(response)
            Ext.Msg.show({
                title: String.format(<?php echo json_encode(__('Error {0}')) ?>,response['agent']),
                buttons: Ext.MessageBox.OK,
                width:300,
                msg: String.format(<?php echo json_encode(__('Unable to import virtual server {0}!')) ?>,send_data['name'])+'<br>'+response['info'],
                icon: Ext.MessageBox.ERROR});

        }
    }); // END Ajax request






}

Ovf.ImportWizard.Main = function(config) {

    Ext.apply(this,config);
    // main panel
    var wizard = Ext.getCmp('ovf-import-wiz');
    var viewerSize = Ext.getBody().getViewSize();
    var windowHeight = viewerSize.height * 0.97;
    var windowWidth = viewerSize.width * 0.97;
    windowHeight = Ext.util.Format.round(windowHeight,0);
    windowHeight = (windowHeight > 600) ? 600 : windowHeight;

    windowWidth = Ext.util.Format.round(windowWidth,0);
    windowWidth = (windowWidth > 900) ? 900 : windowWidth;

    if(!wizard){
        var cards = Ovf.ImportWizard.Cards();

        //remove cookie if exists
        if(Ext.state.Manager.get('ovf-import-wiz')) Ext.state.Manager.clear('ovf-import-wiz');

        var helpid = 'online-help';
        
        wizard = new Ext.ux.Wiz({
            id:'ovf-import-wiz',
            title:this.title,            
            tools:[{id:'help', qtip: __('Help'),handler:function(){View.showHelp({id:helpid,anchorid:'ovf_import',autoLoad:{ params:'mod=ovf'},title: <?php echo json_encode(__('OVF Import Help')) ?>});}}],
            border:true,
            headerConfig : {
                title : <?php echo json_encode(__('OVF import wizard')) ?>
            },
            width:800,
            height:400,
            westConfig : {
                width : 160
            },
            cardPanelConfig : {
                defaults : {
                    baseCls    : 'x-small-editor',
                    bodyStyle  : 'border:none;padding:15px 15px 15px 15px;background-color:#F6F6F6;',
                    border     : false
                }
            },
            cards: cards
            ,listeners: {
                activate:function(self){
                    if(Ext.getCmp(helpid))                        
                        Ext.getCmp(helpid).setActive(false);                    
                }
                ,finish: function() { Ovf.ImportWizard.Save(this);return false;}
            }
        });

        //on browser resize, resize window
        Ext.EventManager.onWindowResize(function(){

            var viewerSize = Ext.getBody().getViewSize();

            var windowHeight = viewerSize.height * 0.97;
            var windowWidth = viewerSize.width * 0.97;
            windowHeight = Ext.util.Format.round(windowHeight,0);
            windowHeight = (windowHeight > 600) ? 600 : windowHeight;

            windowWidth = Ext.util.Format.round(windowWidth,0);
            windowWidth = (windowWidth > 900) ? 900 : windowWidth;

            if(Ext.getCmp('ovf-import-wiz')){
                Ext.getCmp('ovf-import-wiz').setSize(windowWidth,windowHeight);
                Ext.getCmp('ovf-import-wiz').center();
            }

        });

        wizard.show();

        

    }else{

        wizard.setSize(windowWidth,windowHeight);
        wizard.center();
        wizard.show();
    }


};


</script>
