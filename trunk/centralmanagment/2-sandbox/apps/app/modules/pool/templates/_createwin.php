<script>

Ext.form.VTypes["hostnameVal1"] = /^[a-zA-Z][-.a-zA-Z0-9]{0,254}$/;
Ext.form.VTypes["hostnameVal2"] = /^[a-zA-Z]([-a-zA-Z0-9]{0,61}[a-zA-Z0-9]){0,1}([.][a-zA-Z]([-a-zA-Z0-9]{0,61}[a-zA-Z0-9]){0,1}){0,}$/;
Ext.form.VTypes["ipVal"] = /^([1-9][0-9]{0,1}|1[013-9][0-9]|12[0-689]|2[01][0-9]|22[0-3])([.]([1-9]{0,1}[0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])){2}[.]([1-9][0-9]{0,1}|1[0-9]{2}|2[0-4][0-9]|25[0-4])$/;

Ext.form.VTypes["ip"]=function(v){
    return Ext.form.VTypes["ipVal"].test(v);
};

Ext.form.VTypes["hostname"] = function(v){
        if(!Ext.form.VTypes["hostnameVal1"].test(v)){
            Ext.form.VTypes["hostnameText"]="Must begin with a letter and not exceed 255 characters"
            return false;
        }
        Ext.form.VTypes["hostnameText"]="L[.L][.L][.L][...] where L begins with a letter, ends with a letter or number, and does not exceed 63 characters";
        return Ext.form.VTypes["hostnameVal2"].test(v);
};
Ext.form.VTypes["hostnameText"] = 'Invalid hostname';

Ext.form.VTypes["hostdevice"] = function(v){
        if(Ext.form.VTypes["ipVal"].test(v)){
            return true;
        }
        if(Ext.form.VTypes["hostnameVal1"].test(v)){
            return true;
        }
        if(Ext.form.VTypes["hostnameVal2"].test(v)){
            return true;
        }
        return false;
};
Ext.form.VTypes["hostdeviceText"] = 'Invalid host';

Ext.ns("poolwin.createForm");

poolwin.createForm.Main = function(node_id, level) {

    this.level = level;
    this.node_id = node_id;    

    var myparams = {};
    var myurl = <?php echo json_encode(url_for('pool/jsonList'))?>;

    var shared = 0;
    if(this.level == 'cluster'){
        shared = 1;
        myparams = {'cid':this.node_id, 'level': this.level};
    }else if(this.level == 'node'){
        shared = 0;
        myparams = {'nid':this.node_id, 'level': this.level};
    }


    // field set

    // define window and pop-up - render formPanel
    //var centerPanel = new Ext.form.FormPanel({
    poolwin.createForm.Main.superclass.constructor.call(this, {        
        bodyStyle: 'padding-top:10px;',monitorValid:true,
        scope:this,
        monitorValid:true,
        items: [
            new Ext.form.FieldSet({
                autoHeight:true,
                border:false,
                labelWidth:160,defaults:{msgTarget: 'side'},
                items: [
                    { xtype: 'hidden', name: 'shared', ref: 'shared', value: shared },
                    new Ext.form.TextField({
                        fieldLabel: __('Name'),
                        allowBlank: false,
                        name:'name',
                        maxLength: 50,
                        selectOnFocus:true,
                        anchor: '90%'
                    })
                    ,{ xtype: 'hidden', name: 'pool_type', ref: 'pool_type', value: 'iscsi' }
                    /*,new Ext.form.ComboBox({
                                    ref:'pool_type',
                                    name:'pool_type',
                                    id:'pool_type'
                                    ,editable: false
                                    ,typeAhead: false
                                    ,fieldLabel: __('Type'),
                                    width:150,hiddenName:'pool_type'
                                    ,valueField: 'type',displayField: 'type',
                                    forceSelection: true,emptyText: <?php echo json_encode(__('Select type...')) ?>
                                    ,store: new Ext.data.ArrayStore({
                                            fields: ['type'],
                                            data : <?php
                                                        $pool_types = sfConfig::get('app_pool_types');
                                                        $pool_type_elem = array();

                                                        foreach($pool_types as $eformat)
                                                            $pool_type_elem[] = '['.json_encode($eformat).']';
                                                        echo '['.implode(',',$pool_type_elem).']'."\n";
                                                        error_log(print_r($pool_type_elem,true));
                                                    ?>
                                            })
                                    ,mode: 'local'
                                    ,lastQuery:''
                                    //,allowBlank:false
                                    ,triggerAction: 'all'
                                    ,value: 'iscsi'
                                    ,readOnly: true
                    })*/
                    ,{ layout: 'column',
                        fieldLabel: __('Host'),
                        border: false,
                        items:[
                            new Ext.form.TextField({
                                ref: '../source_host',
                                fieldLabel: __('Host'),
                                allowBlank: false,
                                name:'source_host',
                                maxLength: 50,
                                selectOnFocus:true,
                                vtype: 'hostdevice',
                                anchor: '90%'
                                ,listeners : {
                                    'invalid': function(f){
                                        f.ownerCt.ownerCt.btn_discovery.setDisabled(true);
                                    },
                                    'valid': function(f){
                                        f.ownerCt.ownerCt.btn_discovery.setDisabled(false);
                                    }
                                    ,scope: this
                                }
                            }),
                            {
                                xtype: 'button',
                                iconCls:'go-action',
                                text: __('Discovery'),
                                scope:this,
                                ref: '../btn_discovery',
                                disabled: true,
                                handler: function(btn){
                                    console.log(this);
                                    console.log(btn);
                                    var conn = new Ext.data.Connection({
                                                    listeners:{
                                                        // wait message.....
                                                        beforerequest:function(){

                                                            Ext.MessageBox.show({
                                                                title: <?php echo json_encode(__('Please wait...')) ?>,
                                                                msg: <?php echo json_encode(__('Searching for sources of') . ' ' . __(sfConfig::get('app_storage_pool_title')) . '...') ?>,
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

                                    var params = myparams;
                                    params['type'] = this.items.get(0).pool_type.getValue();
                                    params['source_host'] = this.items.get(0).source_host.getValue();
                                    
                                    conn.request({
                                        url: <?php echo(json_encode(url_for('pool/jsonFindSource')))?>,
                                        params: params,
                                        scope:this,
                                        success: function(resp,opt){
                                            var response = Ext.util.JSON.decode(resp.responseText);
                                            console.log(response);
                                            if( response['response'] ){
                                                if( response['response']['sources'] ){
                                                    var sources = response['response']['sources'];
                                                    if( sources.length > 0 ){
                                                        var source = sources[0];
                                                        if( source['source_device'] ){
                                                            var sourcedevices = source['source_device'];
                                                            /*var field_source_devices = this.form.find('name','source_device');
                                                            for(var i=0; i<sourcedevices.length; i++){
                                                                field_source_devices[0].setValue(sourcedevices[0]);
                                                            }*/
                                                            this.form.findField('source_device').setValue(sourcedevices[0]);

                                                        }
                                                    }
                                                }
                                            }
                                        },
                                        failure: function(resp,opt) {
                                            var response = Ext.util.JSON.decode(resp.responseText);
                                            
                                            if(response)
                                            {
                                                Ext.Msg.show({
                                                    title: String.format(<?php echo json_encode(__('Error {0}')) ?>,response['agent']),
                                                    buttons: Ext.MessageBox.OK,
                                                    msg: String.format(<?php echo json_encode(__('Unable to find sources for ') . ' ' . __(sfConfig::get('app_storage_pool_title')) . ' {0}!') ?>,params['name'])+'<br>'+response['info'],
                                                    icon: Ext.MessageBox.ERROR});
                                            }
                                        }
                                    });// END Ajax request
                                }
                            }
                        ]
                    }
                    ,new Ext.form.TextField({
                        ref : 'source_device',
                        fieldLabel: __('Target IQN'),
                        allowBlank: false,
                        name:'source_device',
                        maxLength: 50,
                        selectOnFocus:true,
                        anchor: '90%'
                    })
                    ,{ xtype: 'hidden', name: 'target_path', value: '/dev/disk/by-path' }
                    /*,new Ext.form.TextField({
                        fieldLabel: __('Target path'),
                        allowBlank: false,
                        name:'target_path',
                        maxLength: 50,
                        selectOnFocus:true,
                        anchor: '90%'
                        //,emptyText: '/dev/disk/by-path'
                        ,value: '/dev/disk/by-path'
                    })*/
                ]
            })
        ],
        buttons: [{
                text: __('Save'),
                formBind:true,
                scope: this,
                handler: this.sendRequest
            },
            {
                text: __('Cancel'),
                scope:this,
                //handler:function(){this.ownerCt.close();}
                handler:function(){this.fireEvent('updated');}
            }]// end buttons
    });
    
};// end poolwin.createForm.Main function

// define public methods
Ext.extend(poolwin.createForm.Main, Ext.form.FormPanel, {
    /*
    * send soap request
    * on success store returned object in DB
    */       
    sendRequest:function(){
        // if necessary fields valid...
        if(this.getForm().isValid()){            

            var params;
    
            if(this.level == 'cluster'){
                params = { 'cid':this.node_id, 'level':this.level };
            }else if(this.level == 'node'){
                params = { 'nid':this.node_id, 'level':this.level };
            }else{
                params = { 'nid':this.node_id };
            }

            var data = this.getForm().getValues();

            var pool_name = data['name'];

            // create parameters array to pass to soap request....
            params['pool'] = Ext.encode(data);

            //console.log(params);

            var conn = new Ext.data.Connection({
                            listeners:{
                                // wait message.....
                                beforerequest:function(){

                                    Ext.MessageBox.show({
                                        title: <?php echo json_encode(__('Please wait...')) ?>,
                                        msg: <?php echo json_encode(__('Creating storage pool...')) ?>,
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
                url: <?php echo json_encode(url_for('pool/jsonCreate'))?>,
                params: params,
                scope:this,
                success: function(resp,opt){
                    var response = Ext.util.JSON.decode(resp.responseText);
                    Ext.ux.Logger.info(response['agent'],response['response']);                    
                    this.fireEvent('updated');                                                

                    if( response['errors'] ){
                        var errors = response['errors'];
                        if( errors.length > 0 ){
                            for(var i=0; i<errors.length; i++){
                                var err = errors[i];
                                Ext.ux.Logger.error(err['agent'],err['response']);
                            }
                            Ext.Msg.show({
                            title: <?php echo json_encode(__('Create storage pool')) ?>,
                            buttons: Ext.MessageBox.OK,
                            msg: String.format(<?php echo json_encode(__(sfConfig::get('app_storage_pool_title')) . ' {0} ' . __('created with some errors. See info panel.')) ?>,pool_name),
                            icon: Ext.MessageBox.WARNING});
                        }
                    }
                    
                },
                failure: function(resp,opt) {
                    var response = Ext.util.JSON.decode(resp.responseText);
                    
                    if(response)
                    {
                        if(response['action']=='reload'){
                            
                            Ext.Msg.show({
                            title: String.format(<?php echo json_encode(__('Error {0}')) ?>,response['agent']),
                            buttons: Ext.MessageBox.OK,
                            msg: String.format(<?php echo json_encode(__('Error reloading storage pool {0}!')) ?>,pool_name)+'<br>'+response['info'],
                            icon: Ext.MessageBox.ERROR});
                        }
                        else
                        {
                            Ext.Msg.show({
                                title: String.format(<?php echo json_encode(__('Error {0}')) ?>,response['agent']),
                                buttons: Ext.MessageBox.OK,
                                msg: String.format(<?php echo json_encode(__('Unable to create storage pool {0}!')) ?>,pool_name)+'<br>'+response['info'],
                                icon: Ext.MessageBox.ERROR});
                        }
                    }
                                        
                }
            });// END Ajax request


        }//end isValid
    },
    // load data
    load: function(node) {
    }
});

</script>
