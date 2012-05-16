<script>


Ext.namespace('Vlan');

Vlan.CreateForm = Ext.extend(Ext.form.FormPanel,{        
    url:<?php echo json_encode(url_for('vlan/jsonLoadForm')) ?>,

    initComponent:function(){

        var vlanId_field = new Ext.form.NumberField({
                        fieldLabel: <?php echo json_encode(__('Network ID (1...)')) ?>,
                        allowNegative: false,
                        allowBlank: false,
                        minValue: <?php echo $min_vlanid ?>,
                        maxValue: <?php echo $max_vlanid ?>,
                        name:'vlan_id',                    
                        listeners:{
                            specialkey:function(field,e){
        
                                if(e.getKey()==e.ENTER){
                                    var form = field.ownerCt;
                                    form.submit();
                                }
                            }
                        }
                    });

        // hard coded - cannot be changed from outsid
        var config = {
                defaultType:'textfield'
                ,defaults:{anchor:'-24'}
                ,monitorValid:true
                ,bodyStyle:'padding:10px'
               // ,autoScroll:true
                ,buttonAlign:'right'
                ,items:[
                    {
                        name:'vlan_name',
                        fieldLabel: <?php echo json_encode(__('Network name')) ?>,
                        minLength: <?php echo $min_vlanname ?>,
                        maxLength: <?php echo $max_vlanname ?>,
                        invalidText : <?php echo json_encode(__(EtvaVlanPeer::_ERR_NAME_)) ?>,
                        allowBlank : false,
                        validator  : function(v){
                            var t = <?php echo EtvaVlanPeer::_REGEXP_INVALID_NAME_; ?>;
                            return !t.test(v);
                        }
                    },
                    {
                        name:'vlan_tagged'
                        ,xtype:'checkbox'
                        ,fieldLabel: <?php echo json_encode(__('Tagged')) ?>
                        ,listeners:{
                            check:function(chkbox,checked){

                                if(checked)
                                    vlanId_field.enable();
                                else
                                {
                                    vlanId_field.clearInvalid();
                                    vlanId_field.disable();
                                }

                            }
                        }
                        ,allowBlank:false
                    },vlanId_field]
                ,buttons:[{
                        text:__('Save')
                        ,formBind:true
                        ,scope:this
                        ,handler:this.submit
                    },{
                        text:__('Cancel')
                        ,scope:this
                        ,handler:this.cancel
                    }]
        }; // eo config object

        // apply config
        Ext.apply(this, Ext.apply(this.initialConfig, config));

        // call parent
        Vlan.CreateForm.superclass.initComponent.apply(this, arguments);        

    } // eo function initComponent
      /**
        * Form onRender override
        */
    ,onRender:function() {

        // call parent
        Vlan.CreateForm.superclass.onRender.apply(this, arguments);

        // set wait message target
        this.getForm().waitMsgTarget = this.getEl();

        // loads form after initial layout
        this.on('afterlayout', this.onLoad, this, {single:true});

    } // eo function onRender
    ,onLoad:function(){

        this.load({
            url:this.url
            ,waitMsg: <?php echo json_encode(__('Please wait...')) ?>
            ,success: function ( form, action ) {
                var rec = action.result;
                var vlan_tagged = this.getForm().findField('vlan_tagged');
                var vlan_id = this.getForm().findField('vlan_id');
                var vlan_name = this.getForm().findField('vlan_name');
                if(rec.data['vlan_untagged']){
                    vlan_tagged.setValue(true);
                    vlan_tagged.disable();
                }
                else
                    {
                        vlan_tagged.enable();
                        vlan_id.clearInvalid();
                        vlan_id.disable();

                    }
               vlan_name.focus(true,100);

            },scope:this
            ,failure:function(){}
        });
    }
    ,submit:function(){

//        console.log(this);
        if(this.form.isValid()) {

            var alldata = this.form.getValues();
            var name = alldata['vlan_name'];
            var vlan_id = alldata['vlan_id'];
            var vlan_tagged = this.getForm().findField('vlan_tagged').getValue();


            var send_data = {'name':name, 'cluster_id':this.cluster_id};

            if(vlan_tagged){
                send_data['vlan_tagged'] = 1;
                if(vlan_id) send_data['vlanid'] = vlan_id;
            }
            else send_data['vlan_untagged'] = 1;

            var conn = new Ext.data.Connection({
                    listeners:{
                        // wait message.....
                        beforerequest:function(){
                            Ext.MessageBox.show({
                                title: <?php echo json_encode(__('Please wait...')) ?>,
                                msg: <?php echo json_encode(__('Creating network...')) ?>,
                                width:300,
                                wait:true,
                                modal: true
                            });
                        }// on request complete hide message
                        ,requestcomplete:function(){Ext.MessageBox.hide();}
                        ,requestexception:function(c,r,o){
                            Ext.MessageBox.hide();
                            Ext.Ajax.fireEvent('requestexception',c,r,o);}
                    }
                });// end conn

            conn.request({
                    url: <?php echo json_encode(url_for('vlan/jsonCreate'))?>,
                    params: send_data,
                    scope:this,
                    success: function(resp,opt) {

                        var response = Ext.util.JSON.decode(resp.responseText);
                        var txt = response['response'];
                        var agent = response['agent'];

                        var length = txt.length;                        

                        for(var i=0;i<length;i++){
                            Ext.ux.Logger.info(txt[i]['agent'],txt[i]['info']);
                        }

                        this.ownerCt.fireEvent('onVlanSuccess');

                    },
                    failure: function(resp,opt) {

                        var response = Ext.decode(resp.responseText);

                        if(response && resp.status!=401){
                            var errors = response['error'];

                            if(!response['ok']){
                                View.notify({html:<?php echo json_encode(__('Network could not be created!')) ?>});
                                response['ok'] = [];
                            }else{
                                 View.notify({html: <?php echo json_encode(__('Network added to system!')) ?>});
                            }

                            var oks = response['ok'];
                            var errors_length = errors.length;
                            var oks_length = oks.length;
                            var agents = '<br>';

                            var logger_errormsg = [String.format(<?php echo json_encode(__('Network {0} could not be initialized: {1}')) ?>,name ,'')];
                            var logger_okmsg = [String.format(<?php echo json_encode(__('Network {0} initialized: ')) ?>,name)];

                            var logger_error = [];
                            var logger_ok = [];
                            for(var i=0;i<errors_length;i++){
                                agents += '<b>'+errors[i]['agent']+'</b> - '+errors[i]['error']+'<br>';
                                logger_error[i] = '<b>'+errors[i]['agent']+'</b>('+errors[i]['error']+')';
                            }

                            for(var i=0;i<oks_length;i++){
                                logger_ok[i] = '<b>'+oks[i]['agent']+'</b>';
                            }

                            logger_errormsg += logger_error.join(', ');
                            logger_okmsg += logger_ok.join(', ');

                            Ext.ux.Logger.error(response['agent'],logger_errormsg);
                            if(logger_ok.length>0) Ext.ux.Logger.info(response['agent'],logger_okmsg);

                            var msg = String.format(<?php echo json_encode(__('Network {0} could not be initialized: {1}')) ?>,name,'<br>'+agents);

                            Ext.Msg.show({
                                title: String.format(<?php echo json_encode(__('Error {0}')) ?>,response['agent']),
                                width:300,
                                buttons: Ext.MessageBox.OK,
                                msg: msg,
                                icon: Ext.MessageBox.ERROR});
                        }

                        this.ownerCt.fireEvent('onVlanFailure');


                    }
                });// END Ajax request


        }else{
            Ext.MessageBox.show({
                title: 'Error',
                msg: 'Please fix the errors noted.',
                buttons: Ext.MessageBox.OK,
                icon: Ext.MessageBox.WARNING});
        }

    },
    cancel:function(){
        this.ownerCt.fireEvent('onVlanCancel');
    }
});

Vlan.Create = Ext.extend(Ext.Window,{
    modal:true,
    width:300,
    height:180,
    layout:'fit',
    border:false,
    defaults:{autoScroll: true},
    iconCls:'icon-window'
    ,tools:[{id:'help', qtip: __('Help'),handler:function(){View.showHelp({anchorid:'help-vlan-manage-add',autoLoad:{ params:'mod=vlan'},title: <?php echo json_encode(__('Manage network interfaces Help')) ?>});}}]
    ,initComponent:function(){
        //alert("vlan create : "+this.cluster_id);
        this.items = new Vlan.CreateForm({cluster_id:this.cluster_id});
        this.defaultButton = this.items.getForm().findField('vlan_name');
        Vlan.Create.superclass.initComponent.apply(this, arguments);

    }

});


</script>
