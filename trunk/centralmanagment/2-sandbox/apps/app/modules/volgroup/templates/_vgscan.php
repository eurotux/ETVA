<script>
Ext.ns('Volgroup.Scan');

Volgroup.Scan.Main = function(node_id, level) {

    this.level = level;
    this.node_id = node_id;

    var baseParams;
    if(level == 'cluster'){
        baseParams = {'cid':node_id,'level':level};
    }else{
        baseParams = {'nid':node_id,'level':level};
    }

    var vgParams = baseParams;
    vgParams['notregistered'] = true;
    this.gridVGs = new Ext.form.ComboBox({
        id: 'grid-vgs-scan',
        valueField:'name'
        ,name: 'vg_name'
        ,displayField:'name'
        ,triggerAction:'all'
        ,minChars:2
        ,forceSelection:true
        ,enableKeyEvents:true      
        ,resizable:true
        ,minListWidth:250
        ,allowBlank:false
        ,store:new Ext.data.JsonStore({
            root:'data'
            ,totalProperty:'total'
            ,fields:[
                {name:'uuid',mapping:'uuid',type:'string'}
                ,{name:'name', mapping:'vg_name', type:'string'}
                ,{name:'size', mapping:'size', type:'string'}
                ,{name:'uuid', mapping:'uuid', type:'string'}
                ,{name:'registered', mapping:'registered', type:'string'}
                ,{name:'type', mapping:'type', type:'string'}
            ]
            ,url:<?php echo json_encode(url_for('volgroup/jsonListSyncVolumeGroups'))?>
            ,baseParams:vgParams
            ,listeners:{
                'loadexception':function(store,options,resp,error){
                    
                    var response = Ext.util.JSON.decode(resp.responseText);

                    Ext.Msg.show({title: 'Error',
                        buttons: Ext.MessageBox.OK,
                        msg: response['error'],
                        icon: Ext.MessageBox.ERROR});
                }

            }
        })
        // concatenate vgname and size (MB)
        ,tpl:'<tpl for="."><div class="x-combo-list-item">{name} ({[byte_to_MBconvert(values.size,2,"floor")]} MB)</div></tpl>'

        // listeners
        ,listeners:{
            // sets raw value to concatenated last and first names
             select:{scope:this,fn:function(combo, record, index) {
                var size = byte_to_MBconvert(record.get('size'),2,'floor');
                combo.setRawValue(record.get('name') + ' (' + size+' MB)');
                /*this.totalvgsize.setValue(size);
                this.lvsize.setValue(size);*/

                this.form.findField('vg_uuid').setValue(record.get('uuid'));
                this.form.findField('vg_type').setValue(record.get('type'));

                /*if( Ext.getCmp('grid-devices-scan').disabled )
                    Ext.getCmp('grid-devices-scan').disabled = false ;*/

                if( Ext.getCmp('grid-devices-scan').getStore().getCount() == 0 )
                    Ext.getCmp('grid-devices-scan').getStore().load();

                /*if( Ext.getCmp('grid-lvs-scan').disabled )
                    Ext.getCmp('grid-lvs-scan').disabled = false;*/

                if( Ext.getCmp('grid-lvs-scan').getStore().getCount() == 0 )
                    Ext.getCmp('grid-lvs-scan').getStore().reload();

                Ext.getCmp('grid-devices-scan').getStore().filter('vg',record.get('name'));
                Ext.getCmp('grid-lvs-scan').getStore().filter('vg',record.get('name'));
            }}
            // repair raw value after blur
            ,blur:function() {                
                var val = this.getRawValue();
                this.setRawValue.defer(1, this, [val]);
            }

            // set tooltip and validate
            ,render:function() {
                this.el.set(
                    //{qtip:'Type at least ' + this.minChars + ' characters to search in volume group'}
                    {qtip: <?php echo json_encode(__('Choose volume group')) ?>}
                );
                //this.validate();
            }
            // requery if field is cleared by typing

            ,keypress:{buffer:100, fn:function() {
                if(!this.getRawValue()) {
                    this.doQuery('', true);
                }
            }}
        
        }

        // label
        ,fieldLabel: <?php echo json_encode(__('Volume Group')) ?>
        ,anchor:'90%'
    });// end this.vg

    this.gridDevices = new Ext.grid.GridPanel({
                                    id: 'grid-devices-scan',
                                    layout:'fit',
                                    title: <?php echo json_encode(__('Physical devices')) ?>,
                                    border: false,
                                    //disabled: true,
                                    disableSelection: true,
                                    //sm: new Ext.grid.RowSelectionModel({singleSelect:true}),
                                    viewConfig: {
                                        forceFit: true,
                                    },
                                    colModel: new Ext.grid.ColumnModel({
                                                    columns: [
                                                        {id: 'uuid', header: __('Uuid'), dataIndex: 'uuid'},
                                                        {header: __('Device'), dataIndex: 'device'},
                                                        {header: __('Size'), dataIndex: 'size', renderer: Ext.util.Format.fileSize }
                                                    ]
                                                }),
                                    store:new Ext.data.JsonStore({
                                        root:'data'
                                        ,totalProperty:'total'
                                        //,autoLoad: true
                                        ,fields:[
                                            {name:'uuid',mapping:'uuid',type:'string'}
                                            ,{name: 'device', mapping: 'device', type:'string'}
                                            ,{name: 'size', mapping: 'size', type:'int'}
                                            ,{name: 'vg', mapping: 'vg', type:'string'}
                                        ]
                                        ,url:<?php echo json_encode(url_for('physicalvol/jsonListSyncDiskDevices'))?>
                                        ,baseParams:baseParams
                                        ,listeners:{
                                            'beforeload':function(){                                    
                                                Ext.getCmp('grid-devices-scan').body.mask(<?php echo json_encode(__('Please wait...')) ?>, 'x-mask-loading');
                                            },
                                            'load':function(store,records,options){
                                                Ext.getCmp('grid-devices-scan').body.unmask();                
                                                store.filter('vg',Ext.getCmp('grid-vgs-scan').getValue());
                                            },
                                            'loadexception':function(store,options,resp,error){                                        
                                                Ext.getCmp('grid-devices-scan').body.unmask(); 
                                                var response = Ext.util.JSON.decode(resp.responseText);

                                                Ext.Msg.show({title: 'Error',
                                                    buttons: Ext.MessageBox.OK,
                                                    msg: response['error'],
                                                    icon: Ext.MessageBox.ERROR});
                                            }
                                            ,scope:this
                                        }
                                    })
                                    /*,tools: [{
                                                id:'refresh',
                                                handler: function(){
                                                    Ext.getCmp('grid-devices-scan').getStore().reload();
                                                }
                                                ,scope: this
                                            }]*/
                        });
    this.gridLVs = new Ext.grid.GridPanel({
                                    id: 'grid-lvs-scan',
                                    title: <?php echo json_encode(__('Logical volumes')) ?>,
                                    layout:'fit',
                                    border: false,
                                    //disabled: true,
                                    disableSelection: true,
                                    //sm: new Ext.grid.RowSelectionModel({singleSelect:true}),
                                    viewConfig: {
                                        forceFit: true,
                                    },
                                    colModel: new Ext.grid.ColumnModel({
                                                    columns: [
                                                        {id: 'uuid', header: __('Uuid'), dataIndex: 'uuid'},
                                                        {header: __('Name'), dataIndex: 'name'},
                                                        {header: __('Device'), dataIndex: 'device'},
                                                        {header: __('Size'), dataIndex: 'size', renderer: Ext.util.Format.fileSize }
                                                    ]
                                                }),
                                    store:new Ext.data.JsonStore({
                                        root:'data'
                                        ,totalProperty:'total'
                                        //,autoLoad: true
                                        ,fields:[
                                            {name:'uuid',mapping:'uuid',type:'string'}
                                            ,{name:'name', mapping:'lv_name', type:'string'}
                                            ,{name:'device', mapping:'lvdevice', type:'string'}
                                            ,{name:'size', mapping:'size', type:'string'}
                                            ,{name: 'vg', mapping: 'volumegroup', type:'string'}
                                        ]
                                        ,url:<?php echo json_encode(url_for('logicalvol/jsonListSyncLogicalVolumes'))?>
                                        ,baseParams:baseParams
                                        ,listeners:{
                                            'beforeload':function(store,options){
                                                Ext.getCmp('grid-lvs-scan').body.mask(<?php echo json_encode(__('Please wait...')) ?>, 'x-mask-loading');
                                            },
                                            'load':function(store,records,options){
                                                Ext.getCmp('grid-lvs-scan').body.unmask();                
                                                store.filter('vg',Ext.getCmp('grid-vgs-scan').getValue());
                                            },
                                            'loadexception':function(store,options,resp,error){                                        
                                                Ext.getCmp('grid-lvs-scan').body.unmask();                    
                                                var response = Ext.util.JSON.decode(resp.responseText);

                                                Ext.Msg.show({title: 'Error',
                                                    buttons: Ext.MessageBox.OK,
                                                    msg: response['error'],
                                                    icon: Ext.MessageBox.ERROR});
                                            }
                                            ,scope:this
                                        }
                                    })
                                    /*,tools: [{
                                                id:'refresh',
                                                handler: function(){
                                                    Ext.getCmp('grid-lvs-scan').getStore().reload();
                                                }
                                                ,scope: this
                                            }]*/
                        });

    Volgroup.Scan.Main.superclass.constructor.call(this, {        
        layout: { type: 'vbox', align: 'stretch' },
        scope:this,
        monitorValid:true,
        items: [
                {
                    xtype: 'fieldset',
                    autoHeight: true,
                    border: false,
                    items: [
                        { xtype:'hidden', name: 'vg_uuid' },
                        { xtype:'hidden', name: 'vg_type' },
                        this.gridVGs
                    ]
                    ,flex: 0
                }
                ,{
                    layout: 'fit',
                    border: false,
                    items: [this.gridDevices]
                    ,flex: 1
                }
                ,{
                    layout: 'fit',
                    border: false,
                    items: [this.gridLVs]
                    ,flex: 2
                }
                ],
        buttons: [{
                text: __('Save'),
                formBind:true,
                scope: this,
                handler: this.saveRequest
            },
            {
                text: __('Cancel'),
                scope:this,
                //handler:function(){this.ownerCt.close();}
                handler:function(){this.fireEvent('updated');}
            }]// end buttons
    });
};

// define public methods
Ext.extend(Volgroup.Scan.Main, Ext.form.FormPanel, {
    // load data
    load: function(node) {
    }
    ,saveRequest: function(){

        var myparams = {};
        if(this.level == 'cluster'){
            myparams = {'cid':this.node_id, 'level': this.level};
        }else if(this.level == 'node'){
            myparams = {'nid':this.node_id, 'level': this.level};
        }

        var form_values = this.getForm().getValues();        

        var params = myparams;
        params['name'] = form_values['vg_name'];
        params['uuid'] = form_values['vg_uuid'];
        params['type'] = form_values['vg_type'];
        var volumegroup = { 'name': form_values['vg_name'], 'uuid': form_values['vg_uuid'], 'type': form_values['vg_type'] };
        params['volumegroup'] = Ext.encode(volumegroup);

        console.log(params);

        // send request
        var conn = new Ext.data.Connection({
            listeners:{
                // wait message.....
                beforerequest:function(){
                    Ext.MessageBox.show({
                        title: <?php echo json_encode(__('Please wait...')) ?>,
                        msg: <?php echo json_encode(__('Registering volume group...')) ?>,
                        width:300,
                        wait:true
                    });
                },// on request complete hide message
                requestcomplete:function(){Ext.MessageBox.hide();}
                ,requestexception:function(c,r,o){
                            Ext.MessageBox.hide();
                            Ext.Ajax.fireEvent('requestexception',c,r,o);}
            }
        });// end conn
                    
                    
        conn.request({
            url: <?php echo json_encode(url_for('volgroup/jsonRegister')) ?>,
            params: params,
            scope:this,
            success: function(resp,opt) {

                var response = Ext.util.JSON.decode(resp.responseText);                

                Ext.ux.Logger.info(response['agent'],response['response']);
                //this.ownerCt.fireEvent('updated');
                this.fireEvent('updated');
            },
            failure: function(resp,opt) {
                
                var response = Ext.util.JSON.decode(resp.responseText);
                Ext.ux.Logger.error(response['agent'],response['error']);

                Ext.Msg.show({
                    title: String.format(<?php echo json_encode(__('Error {0}')) ?>,response['agent']),
                    buttons: Ext.MessageBox.OK,
                    msg: String.format(<?php echo json_encode(__('Unable to registe volume group {0}!')) ?>,form_values['vg_name'])+'<br>'+response['error'],
                    icon: Ext.MessageBox.ERROR});

            }
        });// END Ajax request
    }
});

</script>



