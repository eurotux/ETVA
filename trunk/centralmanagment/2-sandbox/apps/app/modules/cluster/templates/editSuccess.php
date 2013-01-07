<?php
?>
<script>
Ext.ns('Cluster.Edit');

Cluster.Edit.Form = Ext.extend(Ext.form.FormPanel, {    
    id: 'cluster-edit-form',
    border:true
    ,monitorValid:true   
    ,initComponent:function() {

        var config = {
            items: [
                    { xtype:'hidden', name:'id'}
                    //{ xtype: 'hidden', name: 'isdefault' },
                    ,{
                        xtype:'textfield',
                        fieldLabel: __('Name'), 
                        allowBlank:false,
                        name:'name',                
                        //invalidText : <?php echo json_encode(__('No spaces and only alpha-numeric characters allowed! "Default" not availalable.')) ?>,
                        invalidText : <?php echo json_encode(__('No spaces and only alpha-numeric characters allowed!')) ?>,
                        validator  : function(v){
                            var t = /^[a-zA-Z][a-zA-Z0-9\-\_]+$/;
                            //var d = /^Default$/;
                            //return t.test(v) && !d.test(v);
                            return t.test(v);
                        }
                        ,listeners:{
                            specialkey:{
                                scope:this,
                                fn:function(field,e){
                                    if(e.getKey()==e.ENTER) this.onSave();
                                }
                            }
                        }
                    }
                    ,{
                        xtype: 'checkbox',
                        fieldLabel: __('Default'), 
                        xtype:'checkbox',
                        name:'isdefault',
                        inputValue:'1',
                        ref:'isdefault'
                    }
                    <?php if($sf_user->getAttribute('etvamodel')!='standard'): ?>
                    ,{
                        id: 'cluster-nodeha-fieldset',
                        cls:'fieldset-top-sp',
                        xtype:'fieldset',
                        collapsed: true,
                        checkboxToggle: true,
                        title: <?php echo json_encode(__('Node High availability')) ?>,
                        items: [
                                {
                                    border: false,
                                    bodyStyle: 'padding:4px',
                                    layout: { type: 'hbox', align: 'stretchmax' },
                                    defaults: { layout: 'form', border:false },
                                    items: [
                                            {
                                                flex: 1,
                                                xtype: 'radio',
                                                id: 'radio-admissiongate-type-n-host-failures-tolerates',
                                                name: 'admissionGate_type',
                                                inputValue: 1,
                                                boxLabel:  <?php echo json_encode(__('Host failures tolerates')) ?>,
                                                listeners: {
                                                    'check': function(e,check){
                                                        //Ext.getCmp('spinner-admissiongate-value-n-host-failures-tolerates').setVisible(check);
                                                        Ext.getCmp('spinner-admissiongate-value-n-host-failures-tolerates').setDisabled(e.disabled || !check);
                                                    }
                                                }},
                                            new Ext.ux.form.SpinnerField({
                                                flex: 1,
                                                //fieldLabel: __('Num. hosts'),
                                                id: 'spinner-admissiongate-value-n-host-failures-tolerates',
                                                name: 'admissionGate_value_n_hosts',
                                                disabled: true,
                                                //hidden: true,
                                                allowBlank:false,
                                                minValue: 0,
                                                value: 1})
                                        ]
                                }
                                ,{
                                    border: false,
                                    bodyStyle: 'padding:4px',
                                    layout: { type: 'hbox', align: 'stretchmax' },
                                    autoScroll:true,
                                    defaults: { layout: 'form', border:false },
                                    items: [
                                        {
                                            flex: 2,
                                            xtype: 'radio',
                                            id: 'radio-admissiongate-type-per-resources',
                                            name: 'admissionGate_type',
                                            inputValue: 2,
                                            boxLabel:  <?php echo json_encode(__('Percentage of resources reserved to failover')) ?>,
                                            listeners: {
                                                'check': function(e,check){
                                                    //Ext.getCmp('spinner-admissiongate-value-per-resources').setVisible(check);
                                                    Ext.getCmp('spinner-admissiongate-value-per-resources').setDisabled(e.disabled || !check);
                                                }
                                            }},
                                        new Ext.ux.form.SpinnerField({
                                            flex: 1,
                                            //fieldLabel: __('Per. resources'),
                                            id: 'spinner-admissiongate-value-per-resources',
                                            name: 'admissionGate_value_per_resources',
                                            disabled: true,
                                            //hidden: true,
                                            allowBlank:false,
                                            minValue: 0,
                                            maxValue: 100,
                                            value: 25}),
                                    ]
                                }
                                ,{
                                    border: false,
                                    bodyStyle: 'padding:4px',
                                    layout: { type: 'hbox', align: 'stretchmax' },
                                    autoScroll:true,
                                    defaults: { layout: 'form', border:false },
                                    items: [
                                        {
                                            flex: 1,
                                            xtype: 'radio',
                                            id: 'radio-admissiongate-type-sparenode',
                                            name: 'admissionGate_type',
                                            inputValue: 0,
                                            boxLabel:  <?php echo json_encode(__('Spare node')) ?>,
                                            listeners: {
                                                'check': function(e,check){
                                                    //Ext.getCmp('combo-select-spare-node').setVisible(check);
                                                    Ext.getCmp('combo-select-spare-node').setDisabled(e.disabled || !check);
                                                }
                                            }},
                                        {
                                            flex: 1,
                                            xtype:'combo',
                                            id: 'combo-select-spare-node',
                                            emptyText: __('Select...'),
                                            //fieldLabel: __('Spare node'),
                                            disabled: true,
                                            //hidden: true,
                                            triggerAction: 'all',
                                            selectOnFocus:true,
                                            forceSelection:true,
                                            editable:false,
                                            allowBlank:false,
                                            width: 100,
                                            //name:'nodes_cb',
                                            ref:'nodes_cb',
                                            hiddenName:'nodes_cb',
                                            valueField:'Id',
                                            displayField:'name',
                                            store: new Ext.data.Store({
                                                        proxy:new Ext.data.HttpProxy({url:'node/JsonListCluster'}),
                                                        reader: new Ext.data.JsonReader({
                                                                    root:'data',
                                                                    fields:['Id',{name:'name',mapping:'Name'},{name:'isSpareNode',mapping:'Issparenode'}]
                                                        })
                                                        ,listeners:{
                                                            'load': function(store,records,options){
                                                                            for(var i = 0; i < records.length;i++){
                                                                                var rec = records[i];
                                                                                if( rec.data.isSpareNode ){
                                                                                    Ext.getCmp('combo-select-spare-node').setValue(rec.data.Id);
                                                                                }
                                                                            }
                                                            }
                                                        }
                                            })
                                        }
                                    ]
                                }
                                ]
                        ,listeners:{
                            beforecollapse:{scope:this,fn:function(panel,anim){
                                Ext.getCmp('cluster-edit-window').setHeight(Ext.getCmp('cluster-edit-window').minHeight);
                                panel.items.each(function(pitem,index,length){
                                                    pitem.setDisabled(true);
                                                    pitem.items.each(function(item,index,length){
                                                        item.setDisabled(true);
                                                    });
                                                });
                            }},
                            beforeexpand:{scope:this,fn:function(panel,anim){
                                Ext.getCmp('cluster-edit-window').setHeight(Ext.getCmp('cluster-edit-window').maxHeight);
                                panel.items.each(function(pitem,index,length){
                                                    pitem.setDisabled(false);
                                                    pitem.items.each(function(item,index,length){
                                                        //if( !item.xtype || (item.xtype=='fieldset') || (item.xtype == 'radio')){   // radio only
                                                        if((item.xtype == 'radio')){   // radio only
                                                            item.setDisabled(false);
                                                            if( item.getValue() ) item.fireEvent('check',item,true);
                                                        }
                                                    });
                                                });
                            }}
                        }
                    }
                    <?php endif; ?>
            ]
            ,scope:this
            ,bodyStyle:'padding:10px'
            ,buttons:[{
                            text: __('Save'),
                            formBind:true,
                            scope:this,
                            handler:this.onSave
                        },
                        {
                            text: __('Cancel'),
                            scope:this,
                            handler:function(){
                                (this.ownerCt).close();
                            }
                        }
                     ]
        };

        // apply config
        Ext.apply(this, Ext.apply(this.initialConfig, config));

        Cluster.Edit.Form.superclass.initComponent.call(this);
    }
    ,onSave:function(btn,e){

        if (this.form.isValid()) {

                
            var form_fieldvalues = this.getForm().getFieldValues();
            var form_values = this.getForm().getValues();

            var isDefaultCluster = form_values['isdefault'] ? 1 : 0;
            var send_data = {'id':form_values['id']};

            var etva_cluster = { 'name':form_fieldvalues['name'], 'isDefaultCluster':isDefaultCluster };

            etva_cluster['admissionGate_type'] = 0;
            etva_cluster['admissionGate_value'] = 0;
            etva_cluster['hasNodeHA'] =  0;

            if( form_values['cluster-nodeha-fieldset-checkbox'] == 'on' ){
                etva_cluster['admissionGate_type'] = form_values['admissionGate_type'] || 0;
                etva_cluster['admissionGate_value'] = form_values['admissionGate_value'] || form_values['admissionGate_value_n_hosts'] || form_values['admissionGate_value_per_resources'] || 0;
                etva_cluster['hasNodeHA'] =  1;
            }

            send_data['etva_cluster'] = Ext.encode(etva_cluster);
            send_data['sparenode'] = form_values['nodes_cb'];

            var conn = new Ext.data.Connection({
                listeners:{
                    // wait message.....
                    beforerequest:function(){
                        Ext.MessageBox.show({
                            title: <?php echo json_encode(__('Please wait...')) ?>,
                            msg: <?php echo json_encode(__('Updating cluster name...')) ?>,
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
                url:<?php echo json_encode(url_for('cluster/jsonUpdate')) ?>,
                params:send_data,
                // everything ok...
                success: function(resp,opt){
                    var response = Ext.util.JSON.decode(resp.responseText);
                    Ext.ux.Logger.info(response['agent'],response['response']);
                    //Ext.getCmp((this.scope).id).fireEvent('onSave');
                    (this.ownerCt).fireEvent('onSave');
                }
                ,failure: function(resp,opt) {
                    var response = Ext.util.JSON.decode(resp.responseText);
                    Ext.ux.Logger.error(response['agent'],response['error']);
                    //var msg = String.format(<?php echo json_encode(__('Network {0} could not be initialized: {1}')) ?>,name,'<br>'+agents);
                    var msg = response['error'];
                    Ext.Msg.show({
                        title: String.format(<?php echo json_encode(__('Error {0}')) ?>,response['agent']),
                        width:300,
                        buttons: Ext.MessageBox.OK,
                        msg: msg,
                        icon: Ext.MessageBox.ERROR}); 

                }
                ,scope:this
                
            });// END Ajax request


        } else{
            Ext.MessageBox.show({
                title: <?php echo json_encode(__('Error!')) ?>,
                msg: <?php echo json_encode(__('Please fix the errors noted!')) ?>,
                buttons: Ext.MessageBox.OK,
                icon: Ext.MessageBox.WARNING
            });
        }
    }
    ,loadData:function(p){
        this.load({
            url:<?php echo json_encode(url_for('cluster/jsonLoad')) ?>,
            waitMsg: <?php echo json_encode(__('Please wait...')) ?>,
            params: p,
            scope:this
            ,success: function ( form, action ) {
                var data = action.result['data'];

                <?php if($sf_user->getAttribute('etvamodel')!='standard'): ?>

                var cb = this.getForm().findField('nodes_cb');
                //cb.store.baseParams = {cid:p.id};

                if( data['allnodes_have_fencing_cmd_configured'] ){
                    Ext.getCmp('cluster-nodeha-fieldset').setDisabled(false);
                    if( !data.hasNodeHA ){
                        Ext.getCmp('cluster-nodeha-fieldset').collapse();
                    } else  {
                        Ext.getCmp('cluster-nodeha-fieldset').expand();
                        //cb.store.reload();
                    }
                } else {
                    var title = <?php echo json_encode(__('Node High availability')) ?>;
                    title += <?php echo json_encode(__(' - configuration for fencing device needed')) ?>;
                    Ext.getCmp('cluster-nodeha-fieldset').setTitle(title);
                    Ext.getCmp('cluster-nodeha-fieldset').setDisabled(true);
                }

                <?php endif; ?>

                if( data.isDefaultCluster ){
                    //form.findField('name').disable();
                    form.findField('isdefault').setValue(1);
                }
            }
        });
    }
});

Cluster.Edit.Window = function(config) {

    Ext.apply(this,config);

    Cluster.Edit.Window.superclass.constructor.call(this, {
        tools:[{id:'help', qtip: __('Help'),handler:function(){View.showHelp({anchorid:'help-edit',autoLoad:{ params:'mod=cluster'},title: <?php echo json_encode(__('Edit cluster Help')) ?>});}}],
        id: 'cluster-edit-window',
        width:480
        ,height:220
        ,minHeight:220
        ,maxHeight: 360
        ,border:false
        ,modal:true
        ,layout:'fit'
        ,items:[ new Cluster.Edit.Form({})]       
    });
};

Ext.extend(Cluster.Edit.Window, Ext.Window,{
    loadData:function(data){
        this.items.get(0).loadData(data);
    }        
});

</script>
