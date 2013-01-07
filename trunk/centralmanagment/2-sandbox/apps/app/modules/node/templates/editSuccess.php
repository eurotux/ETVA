<?php
?>
<script>
Ext.ns('Node.Edit');

Node.Edit.Form = Ext.extend(Ext.form.FormPanel, {    
    border:true
    ,monitorValid:true   
    ,initComponent:function() {

        var config = {
            items: [
                    { xtype:'hidden', name:'id'},
                    {
                        xtype:'textfield',
                        fieldLabel: __('Name'), 
                        allowBlank:false,
                        name:'name',                
                        invalidText : <?php echo json_encode(__('No spaces and only alpha-numeric characters allowed! "Default" not availalable.')) ?>,
                        validator  : function(v){
                            var t = /^[a-zA-Z][a-zA-Z0-9\-\_]+$/;
                            var d = /^Default$/;
                            return t.test(v) && !d.test(v);
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
                    <?php if($sf_user->getAttribute('etvamodel')!='standard'): ?>
                    ,{
                        id: 'node-fencingconf-fieldset',
                        cls:'fieldset-top-sp',
                        xtype:'fieldset',
                        collapsed: true,
                        checkboxToggle: true,
                        title: <?php echo json_encode(__('Enable fencing configuration')) ?>,
                        items: [
                                    {
                                        xtype:'textfield',
                                        fieldLabel: __('Address'), 
                                        allowBlank:false,
                                        disabled: true,
                                        name:'fencingconf_address'
                                    }
                                    ,{
                                        xtype:'textfield',
                                        fieldLabel: __('User name'), 
                                        allowBlank:false,
                                        disabled: true,
                                        name:'fencingconf_username'
                                    }
                                    ,{
                                        xtype:'textfield',
                                        fieldLabel: __('Password'), 
                                        allowBlank:true,
                                        disabled: true,
                                        inputType: 'password',
                                        name:'fencingconf_password'
                                    }
                                    ,{ xtype:'combo',
                                        emptyText: __('Select...'),
                                        fieldLabel: <?php echo json_encode(__('Type')) ?>,
                                        mode: 'local',
                                        triggerAction: 'all',
                                        forceSelection:true,
                                        disabled: true,
                                        editable:false,
                                        allowBlank:false,
                                        width: 100,
                                        ref:'fencingconf_type',
                                        hiddenName:'fencingconf_type',
                                        valueField: 'cmd',
                                        displayField: 'name',
                                        lazyRender:true,
                                        store: new Ext.data.ArrayStore({
                                                fields: ['cmd','name','type'],
                                                data : <?php
                                                            /*
                                                             * build fence types model dynamic
                                                             */
                                                            $fencing_elems = array();

                                                            $fencingtypes_cmds = sfConfig::get('app_fencingcmds');
                                                            foreach($fencingtypes_cmds as $type=>$list_types)
                                                                foreach($list_types as $cmd=>$name)
                                                                    $fencing_elems[] = '['.json_encode($cmd).','.json_encode($name).','.json_encode($type).']';
                                                            echo '['.implode(',',$fencing_elems).']'."\n";
                                                        ?>
                                        })
                                        ,listeners: {
                                            'select': { scope:this, fn:function(cb, rec, ix){
                                                if( rec.data.type == 'datacenter' ){
                                                    this.form.findField('fencingconf_plug').setDisabled(false);
                                                } else {
                                                    this.form.findField('fencingconf_plug').setDisabled(true);
                                                }
                                            }}
                                        }
                                    }
                                    ,{
                                        xtype:'textfield',
                                        fieldLabel: __('Port'), 
                                        allowBlank:true,
                                        disabled: true,
                                        name:'fencingconf_port'
                                    },{
                                        xtype:'textfield',
                                        fieldLabel: __('Slot'), 
                                        allowBlank:false,
                                        disabled: true,
                                        name:'fencingconf_plug'
                                    },{
                                        xtype:'textfield',
                                        fieldLabel: __('Options'), 
                                        allowBlank:true,
                                        disabled: true,
                                        name:'fencingconf_options'
                                    },{
                                        xtype:'checkbox',
                                        fieldLabel: __('Secure'), 
                                        allowBlank:true,
                                        disabled: true,
                                        name:'fencingconf_secure'
                                    },{
                                        xtype:'button',
                                        text: __('Test'),
                                        scope: this,
                                        handler: function(){
                                            if (this.form.isValid()) {

                                                var form_values = this.getForm().getValues();
                                                var name = form_values['name'];
                                                var params = {'id':form_values['id'] };
                                                // fencingconf
                                                var cFencingconf = 0;
                                                var fencingconf = { 'action': 'status' };

                                                for(f in form_values){
                                                    if( f.match(/^fencingconf_/) ){
                                                        var fv = f.replace('fencingconf_','');
                                                        if( form_values[f] ){
                                                            fencingconf[fv] = form_values[f];
                                                            cFencingconf++;
                                                        }
                                                    }
                                                }
                                                params['fencingconf'] = Ext.encode(fencingconf);

                                                var conn = new Ext.data.Connection({
                                                    listeners:{
                                                        // wait message.....
                                                        beforerequest:function(){
                                                            Ext.MessageBox.show({
                                                                title: <?php echo json_encode(__('Please wait...')) ?>,
                                                                msg: <?php echo json_encode(__('Testing fencing configuration...')) ?>,
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
                                                    url:<?php echo json_encode(url_for('node/jsonTestFencing')) ?>,
                                                    params:params,
                                                    // everything ok...
                                                    success: function(resp,opt) {

                                                        var response = Ext.util.JSON.decode(resp.responseText);                

                                                        Ext.ux.Logger.info(response['agent'],response['response']);

                                                        Ext.MessageBox.show({
                                                            title: <?php echo json_encode(__('Test OK!')) ?>,
                                                            msg: response['response'],
                                                            buttons: Ext.MessageBox.OK,
                                                            icon: Ext.MessageBox.INFO
                                                        });

                                                    },
                                                    failure: function(resp,opt) {
                                                        
                                                        var response = Ext.util.JSON.decode(resp.responseText);
                                                        Ext.ux.Logger.error(response['agent'],response['error']);

                                                        Ext.Msg.show({
                                                            title: String.format(<?php echo json_encode(__('Error {0}')) ?>,response['agent']),
                                                            buttons: Ext.MessageBox.OK,
                                                            msg: String.format(<?php echo json_encode(__('Unable to test fencing configuration of node {0}!')) ?>,name)+'<br>'+response['error'],
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
                                    }
                                ]
                        ,listeners:{
                            beforecollapse:{scope:this,fn:function(panel,anim){
                                Ext.getCmp('node-edit-window').setHeight(Ext.getCmp('node-edit-window').minHeight);
                                panel.items.each(function(item,index,length){
                                                    item.setDisabled(true);
                                                });
                            }},
                            beforeexpand:{scope:this,fn:function(panel,anim){
                                Ext.getCmp('node-edit-window').setHeight(Ext.getCmp('node-edit-window').maxHeight);
                                panel.items.each(function(item,index,length){
                                                    item.setDisabled(false);
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

        Node.Edit.Form.superclass.initComponent.call(this);
    }
    ,onSave:function(btn,e){

        if (this.form.isValid()) {

            var form_values = this.getForm().getValues();

            var send_data = {'id':form_values['id'] };
            var etva_node = { 'name': form_values['name'] };
            etva_node['cluster_id'] = form_values['cluster_id'];
            etva_node['memtotal'] = form_values['memtotal'];
            etva_node['memfree'] = form_values['memfree'];
            etva_node['state'] = form_values['state'];

            <?php if($sf_user->getAttribute('etvamodel')!='standard'): ?>
            // fencingconf
            var cFencingconf = 0;
            var fencingconf = {};
            for(f in form_values){
                if( f.match(/^fencingconf_/) ){
                    var fv = f.replace('fencingconf_','');
                    if( form_values[f] ){
                        fencingconf[fv] = form_values[f];
                        cFencingconf++;
                    }
                }
            }
            if( cFencingconf > 0 ){
                etva_node['fencingconf'] = Ext.encode(fencingconf);
            } else {
                etva_node['fencingconf'] = '';
            }
            <?php endif; ?>
            send_data['etva_node'] = Ext.encode(etva_node);

            var conn = new Ext.data.Connection({
                listeners:{
                    // wait message.....
                    beforerequest:function(){
                        Ext.MessageBox.show({
                            title: <?php echo json_encode(__('Please wait...')) ?>,
                            msg: <?php echo json_encode(__('Updating node name...')) ?>,
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
                url:<?php echo json_encode(url_for('node/jsonUpdate')) ?>,
                params:send_data,
                // everything ok...
                success: function(resp,opt) {

                    var response = Ext.util.JSON.decode(resp.responseText);                

                    Ext.ux.Logger.info(response['agent'],response['response']);
                    (this.ownerCt).fireEvent('onSave');

                },
                failure: function(resp,opt) {
                    
                    var response = Ext.util.JSON.decode(resp.responseText);
                    Ext.ux.Logger.error(response['agent'],response['error']);

                    Ext.Msg.show({
                        title: String.format(<?php echo json_encode(__('Error {0}')) ?>,response['agent']),
                        buttons: Ext.MessageBox.OK,
                        msg: String.format(<?php echo json_encode(__('Unable to edit node {0}!')) ?>,form_values['name'])+'<br>'+response['error'],
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
            url:<?php echo json_encode(url_for('node/jsonLoad')) ?>,
            waitMsg: <?php echo json_encode(__('Please wait...')) ?>,
            params: p,
            scope:this
            ,success: function ( form, action ) {
                var data = action.result['data'];
                <?php if($sf_user->getAttribute('etvamodel')!='standard'): ?>
                if( Ext.getCmp('node-fencingconf-fieldset') ){
                    Ext.getCmp('node-fencingconf-fieldset').setDisabled(false);
                    if( data.fencingconf ){
                        Ext.getCmp('node-fencingconf-fieldset').expand();
                    } else  {
                        Ext.getCmp('node-fencingconf-fieldset').collapse();
                    }
                }
                <?php endif; ?>
            }
        });
    }
});

Node.Edit.Window = function(config) {

    Ext.apply(this,config);

    Node.Edit.Window.superclass.constructor.call(this, {
        id: 'node-edit-window',
        tools:[{id:'help', qtip: __('Help'),handler:function(){View.showHelp({anchorid:'help_edit_node',autoLoad:{ params:'mod=node'},title: <?php echo json_encode(__('Edit node Help')) ?>});}}],
        width:480
        ,height:180
        ,minHeight:180
        ,maxHeight:400
        ,border:false
        ,modal:true
        ,layout:'fit'
        ,items:[ new Node.Edit.Form({})]       
    });
};

Ext.extend(Node.Edit.Window, Ext.Window,{
    loadData:function(data){
        this.items.get(0).loadData(data);
    }        
});

</script>
