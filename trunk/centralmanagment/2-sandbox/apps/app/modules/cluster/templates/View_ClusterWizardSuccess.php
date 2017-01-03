<?php

?>


<script>

//Ext.ns("View.FirstTimeWizard");
Ext.ns("Cluster.Create.Main");
Cluster.Create.Main = function(config) {
//View.FirstTimeWizard.Main = function() {

    var CLUSTER_ID;

    Ext.QuickTips.init();

    /*
     * CLUSTER NAME panel
     *
     */
    name_cardPanel = Ext.extend(Ext.ux.Wiz.Card, {
        initComponent: function(){
            var name_tpl = [
                {
                xtype:'fieldset',
                title:<?php echo json_encode(__('Cluster Attributes')) ?>,
                items:[
                        {layout:'hbox',border:false,
                         pack:'center',
                         defaults:{border:false,bodyStyle:'background:transparent;'},
                         bodyStyle:'background:transparent;padding-bottom:10px;',
                         align:'middle',layoutConfig:{align:'middle'},
                         items:[
                                {layout:'form',labelWidth: 90,width:230,
                                    items:[
                                        {
                                            name:'cluster_name',
                                            xtype:'textfield',
                                            minLength: <?php echo $min_vlanname ?>,
                                            maxLength: <?php echo $max_vlanname ?>,
                                            width:130,
                                            fieldLabel: __('Name'),
                                            invalidText : <?php echo json_encode(__('No spaces and only alpha-numeric characters allowed! "Default" not availalable.')) ?>,
                                            allowBlank : false,
                                            validator  : function(v){
                                                var t = /^[a-zA-Z][a-zA-Z0-9\-\_]+$/;
                                                var d = /^Default$/;
                                                return t.test(v) && !d.test(v);
                                            }
                                        }
                                    ]},
                                {layout:'form',labelWidth: 20,width:160,
                                    items:[
                                        {
                                            xtype:'button',
                                            isFormField:true,
                                            text: __('Create'),
                                            scope:this,
                                            handler:this.createCluster
                                        }
                                    ]}
                        ]}
                ]} //end fielset network
                ,{
                    xtype:'textfield',
                    fieldLabel: <?php echo json_encode(__('Status')) ?>,
                    anchor:'90%',
                    cls: 'nopad-border',
                    name:'nameStatus',
                    readOnly:true,
                    width:200,
                    labelSeparator: '',
                    value : <?php echo json_encode(__('Cluster not created.')) ?>,
                    invalidText : '',
                    validator  : function(v){
                        return (v!= <?php echo json_encode(__('Cluster not created.')) ?> && v!= <?php echo json_encode(__('Erro! Não foi possível criar o datacenter.')) ?>);
                    }

                }
            ];

            Ext.apply(this, {
                title        : <?php echo json_encode(__('Cluster name setup')) ?>,
                monitorValid : true,
                items : [{
                            border    : false,
                            bodyStyle : 'background:none;padding-bottom:20px;',
                            html      : <?php echo json_encode(__('Please, answer the following form.')) ?>
                        },name_tpl
                    ]
            });

            name_cardPanel.superclass.initComponent.call(this);
            Ext.Component.superclass.constructor.call(this);
        }
        ,checkAvailability:function(){
            
            var alldata = this.form.getValues();
            var cluster_name = alldata['cluster_name'];
            
            var send_data = {'name':cluster_name};
            if(!send_data['name']){
                return;
            }

            if(this.form.isValid()){
                this.form.findField('nameStatus').setValue(<?php echo json_encode(__('Checking...')) ?>);
                var conn = new Ext.data.Connection({
                    listeners:{
                        // wait message.....
                        beforerequest:function(){
                            Ext.MessageBox.show({
                                title: <?php echo json_encode(__('Please wait...')) ?>,
                                msg: <?php echo json_encode(__('Checking availability...')) ?>,
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
                    url: <?php echo json_encode(url_for('cluster/jsonExists'))?>,
                    params: send_data,
                    scope:this,
                    success: function(resp,opt) {
                        var response = Ext.util.JSON.decode(resp.responseText);
                        var msg = response['msg'];
                        this.form.findField('nameStatus').setValue(msg);
                    },
                    failure: function(resp,opt) {
                    }
            });// END Ajax request
           }

        }
        ,createCluster:function(){

            var alldata = this.form.getValues();
            var cluster_name = alldata['cluster_name'];

            var can_submit = this.form.findField('cluster_name').isValid();

            var send_data = {'name':cluster_name};

            if(!send_data['name']) can_submit = false;

            if(can_submit){
                //alert("submit");
                this.form.findField('nameStatus').setValue(<?php echo json_encode(__('Creating...')) ?>);
                var conn = new Ext.data.Connection({
                    listeners:{
                        // wait message.....
                        beforerequest:function(){
                            Ext.MessageBox.show({
                                title: <?php echo json_encode(__('Please wait...')) ?>,
                                msg: <?php echo json_encode(__('Creating cluster...')) ?>,
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
                    url: <?php echo json_encode(url_for('cluster/jsonCreate'))?>,
                    params: send_data,
                    scope:this,
                    success: function(resp,opt) {

                        var response = Ext.util.JSON.decode(resp.responseText);
                        var txt = response['response'];
                        var agent = response['agent'];
                        var cluster_id = CLUSTER_ID = response['cluster_id'];
                        var netcard = Ext.getCmp('ft-wiz-network');
                        netcard.loadNets(cluster_id);

                        if(response['success'] == true){
                            this.form.findField('nameStatus').setValue(<?php echo json_encode(__('Created successfully.')) ?>);
                            Ext.ux.Logger.info(agent,txt);
                            var msg = <?php echo json_encode(__('Cluster added to the system!')) ?>;
                            View.notify({html:msg});
                            this.fireEvent('reloadVlan');
                        }else{
                            this.form.findField('nameStatus').setValue(<?php echo json_encode(__('Error! Cluster not created.')) ?>);
                        }
                    },
                    failure: function(resp,opt) {

                        var response = Ext.decode(resp.responseText);

                        if(response && resp.status!=401){
                            var errors = response['error'];
this.form.findField('nameStatus').setValue(<?php echo json_encode(__('Creating...')) ?>);
                            // vlan not added to DB
                            if(!response['ok']){
                                View.notify({html:<?php echo json_encode(__('Cluster could not be created!')) ?>});
                                this.form.findField('nameStatus').setValue(<?php echo json_encode(__('Error!')) ?>);
                                response['ok'] = [];
                            }else{

                                 View.notify({html: <?php echo json_encode(__('Cluster added to the system!')) ?>});
                                 this.form.findField('nameStatus').setValue(<?php echo json_encode(__('Cluster added to the system!')) ?>);
                            }

//                            var oks = response['ok'];
//                            var errors_length = errors.length;
//                            var oks_length = oks.length;
//                            var agents = '<br>';
//                            var logger_errormsg = [String.format(<?php echo json_encode(__('Network {0} could not be initialized: {1}')) ?>,name ,'')];
//                            var logger_okmsg = [String.format(<?php echo json_encode(__('Network {0} initialized: ')) ?>,name)];
//                            var logger_error = [];
//                            var logger_ok = [];
//                            for(var i=0;i<errors_length;i++){
//                                agents += '<b>'+errors[i]['agent']+'</b> - '+errors[i]['error']+'<br>';
//                                logger_error[i] = '<b>'+errors[i]['agent']+'</b>('+errors[i]['error']+')';
//                            }
//
//                            for(var i=0;i<oks_length;i++){
//                                logger_ok[i] = '<b>'+oks[i]['agent']+'</b>';
//                            }
//
//                            logger_errormsg += logger_error.join(', ');
//                            logger_okmsg += logger_ok.join(', ');
//
//                            Ext.ux.Logger.error(response['agent'],logger_errormsg);
//                            if(logger_ok.length>0) Ext.ux.Logger.info(response['agent'],logger_okmsg);
//
//                            var msg = String.format(<?php echo json_encode(__('Network {0} could not be initialized: {1}')) ?>,name,'<br>'+agents);
//
//
//
//                            Ext.Msg.show({
//                                title: String.format(<?php echo json_encode(__('Error {0}')) ?>,response['agent']),
//                                width:300,
//                                buttons: Ext.MessageBox.OK,
//                                msg: msg,
//                                icon: Ext.MessageBox.ERROR});
                        }

                        this.fireEvent('reloadVlan');
                    }
                });// END Ajax request
        }}

    });

    /*
     *
     * NETWORKS panel
     *
     */

    network_cardPanel = Ext.extend(Ext.ux.Wiz.Card, {

        initComponent: function(){
            // the check column is created using a custom plugin
            var checkColumn = new Ext.grid.CheckColumn({
                header: 'Tagged',
                dataIndex: 'tagged',align:'center',
                width: 60
            });

            var network_grid = new Ext.grid.GridPanel({
                tools:[{id:'refresh',handler:function(e,t,p,tc){p.getStore().reload();}}],
                store: new Ext.data.JsonStore({
                    proxy: new Ext.data.HttpProxy({url:"vlan/jsonList"}),
                    totalProperty: 'total',
                    root: 'data',
                    fields: [{name:"id"},{name:"name"},{name:"tagged"},{name:"vlanid"}],
                    sortInfo: { field: 'vlanid',direction: 'ASC' },
                    remoteSort: false,
                    listeners:{
                        load:{scope:this,fn:function(st,recs,opt){

                            var hasUntagged = st.reader.jsonData.hasUntagged;
                            var vlan_id = this.form.findField('vlan_id');
                            var vlan_tagged = this.form.findField('vlan_tagged');

                            if(hasUntagged){
                                vlan_tagged.setValue(true);
                                vlan_tagged.disable();
                            }
                            else
                            {
                                vlan_tagged.enable();
                                vlan_tagged.setValue(false);
                                vlan_id.clearInvalid();
                                vlan_id.disable();
                            }
                        }}
                    }
                }),
                columns:[
                    {id:'name',header:'Name',dataIndex:'name'},
                    checkColumn
                ],
                plugins:checkColumn,
                loadMask: {msg: <?php echo json_encode(__('Retrieving data...')) ?>},
                stripeRows:true,
                viewConfig:{
//                    forceFit:true,
                    emptyText: __('Empty!'),  //  emptyText Message
                    deferEmptyText:false
                },
                height:140,
                title: <?php echo json_encode(__('Networks in the system')) ?>,
                autoExpandColumn:'name',
                tbar:[{text:<?php echo json_encode(__('Remove')) ?>,ref:'../removeBtn',disabled:true,iconCls:'icon-remove',scope:this,handler:this.removeNetwork}]
            });


            network_grid.getSelectionModel().on('selectionchange', function(sm){
                network_grid.removeBtn.setDisabled(sm.getCount() < 1);

                var selected = sm.getSelected();
                if(selected && selected.data['name'] == <?php
                                            $etvamodel = $sf_user->getAttribute('etvamodel');
                                            $devices = sfConfig::get('app_device_interfaces');
                                            echo json_encode($devices[$etvamodel]['va_management']);?>)
                {
                    network_grid.removeBtn.setDisabled(true);
                    network_grid.removeBtn.setTooltip(<?php echo json_encode(__('Cannot delete default network')) ?>);

                }
                else{
                    network_grid.removeBtn.setTooltip('');
                    if(selected) network_grid.removeBtn.setDisabled(false);
                }


            });

            var name_tpl = [
                        {
                        xtype:'fieldset',
                        title:<?php echo json_encode(__('Network')) ?>,
                        items:[
                                {layout:'hbox',border:false,
                                 pack:'center',
                                 defaults:{border:false,bodyStyle:'background:transparent;'},
                                 bodyStyle:'background:transparent;padding-bottom:10px;',
                                 align:'middle',layoutConfig:{align:'middle'},
                                 items:[
                                        {layout:'form',labelWidth: 90,width:230,
                                            items:[
                                                {
                                                    name:'vlan_name',
                                                    xtype:'textfield',
                                                    minLength: <?php echo $min_vlanname ?>,
                                                    maxLength: <?php echo $max_vlanname ?>,
                                                    width:130,
                                                    fieldLabel: <?php echo json_encode(__('Network name')) ?>,
                                                    invalidText : <?php echo json_encode(__('No spaces and only alpha-numeric characters allowed!')) ?>,
                                                    allowBlank : true,
                                                    validator  : function(v){
                                                        var t = /^[a-zA-Z0-9_]+$/;

                                                        if(v) return t.test(v);
                                                        else return true;
                                                    }
                                                },
                                                {
                                                    xtype:'numberfield',
                                                    fieldLabel: <?php echo json_encode(__('Network ID (1...)')) ?>,
                                                    allowBlank: true,
                                                    allowNegative: false,
                                                    minValue: <?php echo $min_vlanid ?>,
                                                    maxValue: <?php echo $max_vlanid ?>,
                                                    width:50,
                                                    disabled:true,
                                                    name:'vlan_id',
                                                    scope:this,
                                                    listeners:{
                                                        specialkey:{scope:this,fn:function(field,e){

                                                            if(e.getKey()==e.ENTER){
                                                                this.saveNetwork();
                                                            }
                                                        }}
                                                    }
                                                },
                                                {
                                                    name:'vlan_tagged'
                                                    ,xtype:'checkbox'
                                                    ,fieldLabel: <?php echo json_encode(__('Tagged')) ?>
                                                    ,scope:this
                                                    ,listeners:{
                                                        check:{scope:this,fn:function(chkbox,checked){
                                                            if(checked)
                                                                this.form.findField('vlan_id').enable();
                                                            else{
                                                                this.form.findField('vlan_id').clearInvalid();
                                                                this.form.findField('vlan_id').disable();
                                                            }


                                                        }}
                                                    }
                                                    ,allowBlank:false
                                                }
                                            ]},
                                        {layout:'form',labelWidth: 40,width:160,
                                            items:[
                                                {
                                                    xtype:'button',
                                                    isFormField:true,
                                                    text: <?php echo json_encode(__('Add Network')) ?>,
                                                    scope:this,
                                                    handler:this.saveNetwork
                                                }
                                            ]}
                                ]}
                        ]} //end fielset network
                        ,network_grid
                        ,{
                            xtype:'textfield',
                            fieldLabel: <?php echo json_encode(__('Status')) ?>,
                            anchor:'90%',
                            cls: 'nopad-border',
                            name:'networkStatus',
                            readOnly:true,
                            width:200,
                            labelSeparator: '',
                            value : <?php echo json_encode(__('Networks not set!')) ?>,
                            invalidText : '',
                            allowBlank : false,
                            validator  : function(v){
                                return (v!= <?php echo json_encode(__('Networks not set!')) ?>
                                    && v!= <?php echo json_encode(__('Error!')) ?>
                                    && v!= <?php echo json_encode(__('Network could not be created!')) ?>);
                            }
                        }
            ];

            Ext.apply(this, {
                title        : <?php echo json_encode(__('Network setup')) ?>,
                monitorValid : true,
                items : [{
                            border    : false,
                            bodyStyle : 'background:none;padding-bottom:20px;',
                            html      : <?php echo json_encode(__('Network setup')) ?>
                        },name_tpl
                    ]
            });

            this.loadNets = function(cluster_id){
                this.cluster_id = cluster_id;
                network_grid.getStore().setBaseParam('id',cluster_id);
                network_grid.getStore().setBaseParam('level','cluster');
//                network_grid.fireEvent('reloadVlan', this);
                network_grid.store.reload();
            }

            network_cardPanel.superclass.initComponent.call(this);
            Ext.Component.superclass.constructor.call(this);

            this.on({
                    reloadVlan:{fn:function(){network_grid.store.reload();}},
                    updateStatus:{fn:function(){
                            network_grid.store.reload({scope:this,callback:function(){
                                    //alert("reload vlan grid");
                                    var status = this.form.findField('networkStatus');
                                    var total = network_grid.store.getTotalCount();
                                    if(total==0) status.setValue(<?php echo json_encode(__('Networks not set!')) ?>);
                                    else status.setValue(String.format(<?php echo json_encode(__('{0} network(s) on the system')) ?>,total));
                            }});
                    }}
            });

            // load the store at the latest possible moment
            network_grid.on({
                afterlayout:{scope:this, single:true, fn:function() {
                    this.fireEvent('updateStatus');
                }}
            });

        }
        ,saveNetwork:function(){
            this.form.findField('networkStatus').setValue(<?php echo json_encode(__('Saving...')) ?>);
            var alldata = this.form.getValues();
            var can_submit = true;
            var vlan_id = alldata['vlan_id'];

            var name = alldata['vlan_name'];

            var vlan_tagged = this.getForm().findField('vlan_tagged').getValue();

            var send_data = {'name':name};
            if(vlan_tagged){
                send_data['vlan_tagged'] = 1;
                send_data['vlanid'] = vlan_id;

                if(!vlan_id) can_submit = false;
            }
            else send_data['vlan_untagged'] = 1;

            send_data['cluster_id'] = this.cluster_id;

            if(!send_data['name']) can_submit = false;

            if(this.form.isValid() && can_submit){

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
                                Ext.ux.Logger.info(agent,txt[i]);
                            }
                            var msg = <?php echo json_encode(__('Network added to system!')) ?>;

                            this.form.findField('networkStatus').setValue(msg);
                            View.notify({html:msg});

                            this.fireEvent('reloadVlan');

                        },
                        failure: function(resp,opt) {

                            var response = Ext.decode(resp.responseText);

                            if(response && resp.status!=401){
                                var errors = response['error'];


                                // vlan not added to DB
                                if(!response['ok']){
                                    View.notify({html:<?php echo json_encode(__('Network could not be created!')) ?>});
                                    this.form.findField('networkStatus').setValue(<?php echo json_encode(__('Error!')) ?>);
                                    response['ok'] = [];
                                }else{

                                     View.notify({html: <?php echo json_encode(__('Network added to system!')) ?>});
                                     this.form.findField('networkStatus').setValue(<?php echo json_encode(__('Network added to system!')) ?>);
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

                            this.fireEvent('reloadVlan');


                        }
                    });// END Ajax request


            }else{
                this.form.findField('networkStatus').setValue(<?php echo json_encode(__('Network could not be created!')) ?>);
                Ext.MessageBox.alert(<?php echo json_encode(__('Error!')) ?>, <?php echo json_encode(__('Please fix the errors noted!')) ?>);
            }

        }
        ,removeNetwork:function(g){
            var grid = g.ownerCt.ownerCt;
            var sm = grid.getSelectionModel();
            var sel = sm.getSelected();
            if (sm.hasSelection()){
                Ext.Msg.show({
                    title: <?php echo json_encode(__('Remove network')) ?>,
                    buttons: Ext.MessageBox.YESNO,
                    msg: String.format(<?php echo json_encode(__('Remove network {0} ?')) ?>,sel.data['name']),scope:this,
                    fn: function(btn){

                        if (btn == 'yes'){

                            var conn = new Ext.data.Connection({
                                    listeners:{
                                    // wait message.....
                                    beforerequest:function(){
                                        Ext.MessageBox.show({
                                            title: <?php echo json_encode(__('Please wait...')) ?>,
                                            msg: <?php echo json_encode(__('Removing network...')) ?>,
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
                                url: <?php echo json_encode(url_for('vlan/jsonRemove'))?>,
                                params: {
                                    'name': sel.data['name']
                                    ,'cluster_id': this.cluster_id

                                },
                                scope:this,
                                success: function(resp,opt) {

                                    var response = Ext.util.JSON.decode(resp.responseText);
                                    var txt = response['response'];
                                    var agent = response['agent'];

                                    var length = txt.length;

                                    for(var i=0;i<length;i++){
                                        Ext.ux.Logger.info(agent,txt[i]);
                                    }

                                    this.fireEvent('updateStatus');

                                }
                                ,failure: function(resp,opt) {

                                    var response = Ext.decode(resp.responseText);

                                    if(response && resp.status!=401){
                                        var errors = response['error'];
                                        var oks = response['ok'];
                                        var errors_length = errors.length;
                                        var oks_length = oks.length;
                                        var agents = '<br>';

                                        var logger_errormsg = [String.format(<?php echo json_encode(__('Network {0} could not be uninitialized: {1}')) ?>,sel.data['name'] ,'')];
                                        var logger_okmsg = [String.format(<?php echo json_encode(__('Network {0} uninitialized: ')) ?>,sel.data['name'])];

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

                                        var msg = String.format(<?php echo json_encode(__('Network {0} could not be uninitialized: {1}')) ?>,sel.data['name'],'<br>'+agents);

                                        this.fireEvent('updateStatus');

                                        Ext.Msg.show({
                                            title: String.format(<?php echo json_encode(__('Error {0}')) ?>,response['agent']),
                                            width:300,
                                            buttons: Ext.MessageBox.OK,
                                            msg: msg,
                                            icon: Ext.MessageBox.ERROR});
                                    }



                                }
                            });// END Ajax request

                        }//END button==yes
                    }// END fn
                }); //END Msg.show
            }//END if
        } // end of remove network ??
    }); //end of network card


    // Wizard window
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
                        html      : <?php echo json_encode(__('Welcome to the cluster configuration utility.<br>Follow the steps to create a new cluster.')) ?> //,null,'first_time_wizard')) ?>
                }]
            })
            ,new name_cardPanel({id:'ft_wiz_name'})
            ,new network_cardPanel({id:'ft-wiz-network'})
            ,new Ext.ux.Wiz.Card({
                title        : <?php echo json_encode(__('Finished!')) ?>,
                monitorValid : true,
                items : [{
                    border    : false,
                    bodyStyle : 'background:none;',
                    html      : <?php echo json_encode(__('Thank you! New cluster setup has be done!')) ?> //,null,'first_time_wizard')) ?>
                 }]
            })
        ];
//    }

    var wizard = new Ext.ux.Wiz({
        border:true,
        title : <?php echo json_encode(__('Cluster Setup Wizard')) ?>,
        tools:[{id:'help', qtip: __('Help'),handler:function(){View.showHelp({anchorid:'help-clusterWz-main',autoLoad:{ params:'mod=cluster'},title: <?php echo json_encode(__('Cluster Setup Wizard Help')) ?>});}}],
        headerConfig : {
            title : <?php echo json_encode(__('Cluster setup')) ?>
        },
        width:610,
        height:520,
        westConfig : {
            width : 185
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
            finish: function() {
                //console.log(this); console.log(this.getWizardData());
                Ext.getCmp('view-nodes-panel').getRootNode().reload(function(){
                    var centerElem = Ext.getCmp('view-main-panel').findById('view-center-panel-'+CLUSTER_ID);
                    if(centerElem && centerElem.isVisible())
                    {

                        this.selectNode(CLUSTER_ID);
                        centerElem.fireEvent('beforeshow');
                    }


                },this);
            }
        }

    });

    // show the wizard
    wizard.show();
    //.showInit();
    
}
</script>
