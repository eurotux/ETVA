<script>
Ext.ns('Ovf.Export');

Ovf.Export.Form = Ext.extend(Ext.form.FormPanel, {
    border:true
    ,monitorValid:true
    ,autoScroll:true
  //  ,anchor:'100%'
    ,labelWidth:140
    ,bodyStyle:'padding:10px;'
    ,initComponent:function() {

        this.items = [
            {xtype:'hidden',name:'Id'},
            {xtype:'box',height:40
            ,autoEl:{
                tag:'div',
                children:[{
                            tag:'div'
                            ,style:'float:left;width:31px;height:32px;'
                            ,cls:'icon-warning'
                        },
                        {
                            tag:'div'
                            ,style:'margin-left:35px'
                            ,html: <?php echo json_encode(__('Server needs to be stopped!')) ?>
                        }]
                }
            },            
            {
                xtype:'combo',
                anchor:'90%',
                listWidth:300,
                emptyText: __('Select...'),
                fieldLabel: <?php echo json_encode(__('Server to export')) ?>,
                triggerAction: 'all',
                validator:function(v){
                    var record = this.findRecord('name',v);
                    if(record)
                        if(record.get('vm_state')=='running') return <?php echo json_encode(__('Server needs to be stopped!')) ?>;
                    return true;
                },
                tpl:'<tpl for="."><div class="x-combo-list-item">{name} <b><i>{vm_state}</i></b> ({node_name})</div></tpl>',
                selectOnFocus:true,
                forceSelection:true,
                editable:false,
                allowBlank:false,
                name:'servers_cb',
                hiddenName:'servers_cb',
                valueField:'Id',
                displayField:'name',
                pageSize:10,
                store:new Ext.data.Store({
                        proxy:new Ext.data.HttpProxy({url:'/app_dev.php/server/JsonListAll'}),
                        baseParams:{'sort':Ext.encode([{field: 'node_name',direction: 'ASC'},{field: 'name',direction: 'ASC'}])},
                        remoteSort: true,
                        reader: new Ext.data.JsonReader({
                            root:'data',
                            fields:['Id',{name:'name',mapping:'Name'},{name:'vm_state',mapping:'VmState'},{name:'node_name',mapping:'NodeName'}]
                        })
                        ,listeners:{load:{scope:this,fn:function(st, records){

                                if(!records.length)
                                    Ext.Msg.show({title: (this.ownerCt).title,
                                        buttons: Ext.MessageBox.OK,
                                        msg: <?php echo json_encode(__('No virtual servers available')); ?>,
                                        icon: Ext.MessageBox.INFO});

                        }}}
                })
            }
        ];

        // build form-buttons
        this.buttons = [{
                            text: __('Ok'),
                            formBind:true,
                            handler: this.onSave,
                            scope: this
                        },
                        {
                            text:__('Cancel'),
                            scope:this,
                            handler:function(){(this.ownerCt).close()}
                        }];

        Ovf.Export.Form.superclass.initComponent.call(this);

    }
    ,loadRecord:function(rec){

        var cb = this.getForm().findField('nodes_cb');
        cb.store.baseParams = {sid:rec.data['Id']};

        this.getForm().loadRecord(rec);

    }
    ,onSave:function(){

        var form_values = this.getForm().getValues();
        var send_data = new Object();

        send_data['id'] = form_values['Id'];
        send_data['sid'] = form_values['servers_cb'];

        var url = <?php echo json_encode(url_for('ovf/OvfDownload'))?> + '?sid=' + send_data['sid'];

        var export_iframe = Ext.getCmp('ovf-export-frame');

        if(!export_iframe){
            export_iframe = new Ext.ux.ManagedIFrame.Window({id:'ovf-export-frame',defaultSrc:url,
                                listeners:{
                                    'domready':function(frameEl){
                                        var doc = frameEl.getFrameDocument();
                                        var response = doc.body.innerHTML;

                                        if(response)
                                        Ext.Msg.show({
                                            title: String.format(<?php echo json_encode(__('Error!')) ?>),
                                            buttons: Ext.MessageBox.OK,
                                            msg: doc.body.innerHTML,
                                            icon: Ext.MessageBox.ERROR});            
                                    }
                                    ,'hide':function(){Ext.getCmp('ovf-export-window').fireEvent('onSave');}
                                }
            });
        }        
                
        export_iframe.setSrc(url);

        export_iframe.show();
        export_iframe.hide();
        
        
        return;

    }
});


Ovf.Export.Window = function(config) {

    Ext.apply(this,config);

    Ovf.Export.Window.superclass.constructor.call(this, {        
        id:'ovf-export-window',
        width:400,
        height:200,
        modal:true,layout:'fit',
        bodyStyle:'padding:3px;',
        tools:[{id:'help',qtip: __('Help'),handler:function(){View.showHelp({anchorid:'ovf_export',autoLoad:{ params:'mod=ovf'},title: <?php echo json_encode(__('OVF Export Help')) ?>});}}],
        items:[ new Ovf.Export.Form()]
    });

    this.on('onSave',function(){this.close.defer(500,this);});
    

};


Ext.extend(Ovf.Export.Window, Ext.Window,{});

</script>