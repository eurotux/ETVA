<?php
include_component('etfw','ETFW_dhcp_networkinterface',array('etva_server'=>$etva_server,'etva_service'=>$etva_service));
include_partial('ETFW_dhcp_networks');
include_partial('ETFW_dhcp_hosts');
include_partial('ETFW_dhcp_zones');
?>
<script>

    Ext.ns('ETFW_DHCP');

    ETFW_DHCP.ClientOptions_Form = Ext.extend(Ext.form.FormPanel, {

        // defaults - can be changed from outside
        border:false
        ,frame:true
        ,labelWidth:170
        ,url:<?php echo json_encode(url_for('etfw/json'))?>

        ,initComponent:function() {

            this.saveBtn = new Ext.Button({text:'Save'
                ,scope:this
                ,handler:this.onSave
            });


            var all_fields = {};
            // if flagged to view general client options....show extra fields also
            if(this.all){
                this.saveBtn.setHandler(this.onSaveAll,this);
                all_fields = [
                              {
                                layout:'table',
                                layoutConfig: {columns: 2},
                                defaults:{layout:'form'},
                                items: [{width:350,items:[{
                                                layout:'table',
                                                layoutConfig: {columns: 3},
                                                defaults:{layout:'form'},
                                                items: [
                                                    {items:[{xtype:'radio', fieldLabel:'Use name as client hostname?',name:'use-host-decl-names',boxLabel:'Yes',inputValue: 'on'}]},
                                                    {items:[{xtype:'radio', fieldLabel:'',hideLabel:true,name:'use-host-decl-names',boxLabel:'No',inputValue: 'off'}]},
                                                    {items:[{xtype:'radio', fieldLabel:'',hideLabel:true,name:'use-host-decl-names',checked:true,boxLabel:'Default',inputValue: ''}]}
                                                ]
                                            }]},
                                    // 2nd col
                                    {bodyStyle:'padding-left:10px;',
                                        items:[
                                            {
                                                layout:'table',
                                                layoutConfig: {columns: 4},
                                                defaults:{layout:'form'},
                                                items: [
                                                    {items:[{xtype:'radio', fieldLabel:'Default lease time',name:'default-lease-time-src',boxLabel:'Default',checked:true,inputValue: 0}]},
                                                    {items:[{xtype:'radio', fieldLabel:'',hideLabel:true,name:'default-lease-time-src',boxLabel:'',inputValue: 1}]},
                                                    {items:[{xtype:'textfield',fieldLabel:'',name:'default-lease-time',hideLabel:true}]},
                                                    {items:[{xtype:'displayfield',value:'secs',fieldLabel:'',hideLabel:true}]}
                                                ]
                                            }
                                        ]}// end 2nd col
                                ]
                            }
                            ,
                             ETFW_DHCP.Data_Form_DefaultItems,
                            {
                                layout:'table',
                                layoutConfig: {columns: 4},
                                defaults:{layout:'form'},
                                items: [
                                    {items:[{xtype:'radio', fieldLabel:'Dynamic DNS update style',name:'ddns-update-style',boxLabel:'Ad-hoc',inputValue: 'ad-hoc'}]},
                                    {items:[{xtype:'radio', fieldLabel:'',hideLabel:true,name:'ddns-update-style',boxLabel:'Interim',inputValue: 'interim'}]},
                                    {items:[{xtype:'radio', fieldLabel:'',hideLabel:true,name:'ddns-update-style',boxLabel:'None',inputValue: 'none'}]},
                                    {items:[{xtype:'radio', fieldLabel:'',hideLabel:true,name:'ddns-update-style',boxLabel:'Default',checked:true,inputValue: ''}]}
                                ]
                            },
                            //Allow unknown clients?
                            {
                                layout:'table',
                                layoutConfig: {columns: 4},
                                defaults:{layout:'form'},
                                items: [
                                    {items:[{xtype:'radio', fieldLabel:'Allow unknown clients?',name:'unknown-clients',boxLabel:'Allow',inputValue: 'allow'}]},
                                    {items:[{xtype:'radio', fieldLabel:'',hideLabel:true,name:'unknown-clients',boxLabel:'Deny',inputValue: 'deny'}]},
                                    {items:[{xtype:'radio', fieldLabel:'',hideLabel:true,name:'unknown-clients',boxLabel:'Ignore',inputValue: 'ignore'}]},
                                    {items:[{xtype:'radio', fieldLabel:'',hideLabel:true,name:'unknown-clients',boxLabel:'Default',checked:true,inputValue: ''}]}
                                ]
                            },
                            //Can clients update their own records?
                            {
                                layout:'table',
                                layoutConfig: {columns: 4},
                                defaults:{layout:'form'},
                                items: [
                                    {items:[{xtype:'radio', fieldLabel:'Can clients update their own records?',name:'client-updates',boxLabel:'Allow',inputValue: 'allow'}]},
                                    {items:[{xtype:'radio', fieldLabel:'',name:'client-updates',hideLabel:true,boxLabel:'Deny',inputValue: 'deny'}]},
                                    {items:[{xtype:'radio', fieldLabel:'',name:'client-updates',hideLabel:true,boxLabel:'Ignore',inputValue: 'ignore'}]},
                                    {items:[{xtype:'radio', fieldLabel:'',name:'client-updates',hideLabel:true,boxLabel:'Default',checked:true,inputValue: ''}]}
                                ]
                            },
                            //Server is authoritative for this subnet?
                            {
                                layout:'table',
                                layoutConfig: {columns: 2},
                                defaults:{layout:'form'},
                                items: [
                                    {items:[{xtype:'radio', fieldLabel:'Server is authoritative for all subnets?',name:'authoritative',boxLabel:'Yes',inputValue: 1}]},
                                    {items:[{xtype:'radio', fieldLabel:'',hideLabel:true,name:'authoritative',boxLabel:'Default(No)',checked:true,inputValue: 0}]}
                                ]
                            }
                     ];
            }



            var config = {
                defaultType:'textfield'
                // ,defaults:{anchor:'-24'}
                ,monitorValid:true
                ,autoScroll:true
                // ,buttonAlign:'right'
                ,items:[
                    {
                        xtype:'fieldset',
                        labelWidth:120,
                        collapsible:true,
                        title: 'Client options',
                        items :[
                            {
                                layout:'table',
                                layoutConfig: {columns: 2},
                                defaults:{layout:'form'},
                                items: [{width:350,items:[
                                            this.buildDefaultItem('Client hostname','option_host-name'),
                                            this.buildDefaultItem('Subnet mask','option_subnet-mask'),
                                            this.buildDefaultItem('Domain name','option_domain-name'),
                                            this.buildDefaultItem('Time servers','option_time-servers'),
                                            this.buildDefaultItem('Swap server','option_swap-server'),
                                            this.buildDefaultItem('NIS domain','option_nis-domain'),
                                            this.buildDefaultItem('Font servers','option_font-servers')
                                        ]},
                                    // 2ns col
                                    {bodyStyle:'padding-left:10px;',
                                        items:[
                                            this.buildDefaultItem('Default routers','option_routers'),
                                            this.buildDefaultItem('Broadcast address','option_broadcast-address'),
                                            this.buildDefaultItem('DNS servers','option_domain-name-servers'),
                                            this.buildDefaultItem('Log servers','option_log-servers'),
                                            this.buildDefaultItem('Root disk path','option_root-path'),
                                            this.buildDefaultItem('NIS servers','option_nis-servers'),
                                            this.buildDefaultItem('XDM servers','option_x-display-manager')
                                        ]}// end 2nd col
                                ]
                            },
                            this.buildDefaultItem('Static routes','option_static-routes',220),
                            {
                                layout:'table',
                                layoutConfig: {columns: 2},
                                defaults:{layout:'form'},
                                items: [{width:350,items:[
                                            this.buildDefaultItem('NTP servers','option_ntp-servers'),
                                            this.buildDefaultItem('NetBIOS scope','option_netbios-scope'),
                                            this.buildDefaultItem('Time offset','option_time-offset')
                                        ]},
                                    // 2ns col
                                    {bodyStyle:'padding-left:10px;',
                                        items:[
                                            this.buildDefaultItem('NetBIOS name servers','option_netbios-name-servers'),
                                            this.buildDefaultItem('NetBIOS node type','option_netbios-node-type'),
                                            this.buildDefaultItem('DHCP server identifier','option_dhcp-server-identifier')
                                        ]}// end 2nd col
                                ]
                            },
                            {
                                layout:'table',
                                layoutConfig: {columns: 4},
                                defaults:{layout:'form'},
                                items: [
                                    {items:[{xtype:'radio', fieldLabel:'SLP directory agent IPs',name:'option_slp-directory-agent-src',boxLabel:'Default',checked:true,inputValue: 0}]},
                                    {items:[{xtype:'radio', fieldLabel:'',hideLabel:true,name:'option_slp-directory-agent-src',boxLabel:'',inputValue: 1}]},
                                    {bodyStyle:'padding-bottom:5px;',items:[{xtype:'textfield',name:'option_slp-directory-agent',fieldLabel:'',hideLabel:true}]},
                                    {bodyStyle:'padding-bottom:5px;padding-left:5px;',items:[{xtype:'checkbox', name:'option_slp-directory-agent-ips', fieldLabel:'',hideLabel:true,boxLabel:'These IPs only?',inputValue: '1'}]}

                                ]
                            },
                            {
                                layout:'table',
                                layoutConfig: {columns: 4},
                                defaults:{layout:'form'},
                                items: [
                                    {items:[{xtype:'radio', fieldLabel:'SLP service scope',name:'option_slp-service-scope-src',boxLabel:'Default',checked:true,inputValue: 0}]},
                                    {items:[{xtype:'radio', fieldLabel:'',hideLabel:true,name:'option_slp-service-scope-src',boxLabel:'',inputValue: 1}]},
                                    {bodyStyle:'padding-bottom:5px;',items:[{xtype:'textfield',name:'option_slp-service-scope',fieldLabel:'',hideLabel:true}]},
                                    {bodyStyle:'padding-bottom:5px;padding-left:5px;',items:[{xtype:'checkbox', name:'option_slp-service-scope-scope', fieldLabel:'',hideLabel:true,boxLabel:'This scope only?',inputValue: '1'}]}

                                ]
                            },
                            all_fields

                        ]
                    }],
                buttons:[this.saveBtn]
            };


            // apply config
            Ext.apply(this, Ext.apply(this.initialConfig, config));


            this.on('beforerender',function(){
                Ext.getBody().mask('Loading form data...');

            },this);

            this.on('render',function(){
                Ext.getBody().unmask();}
            ,this
            ,{delay:10}
        );

            // call parent
            ETFW_DHCP.ClientOptions_Form.superclass.initComponent.apply(this, arguments);



        } // eo function initComponent

        ,buildDefaultItem:function(fieldlabel,name,width){
            var txt_field = {xtype:'textfield',name:name,fieldLabel:'',hideLabel:true};
            if(width) txt_field = {xtype:'textfield',name:name,fieldLabel:'',width:width,hideLabel:true};
            var config = {
                layout:'table',
                layoutConfig: {columns: 3},
                defaults:{layout:'form'},
                items: [
                    {items:[{xtype:'radio', fieldLabel:fieldlabel,name:name+'-src',boxLabel:'Default',checked:true,inputValue: 0}]},
                    {items:[{xtype:'radio', fieldLabel:'',hideLabel:true,name:name+'-src',boxLabel:'',inputValue: 1}]},
                    {bodyStyle:'padding-bottom:5px;',items:[txt_field]}
                ]
            };
            return config;

        }
        ,onRender:function(){
              // call parent
                 ETFW_DHCP.ClientOptions_Form.superclass.onRender.apply(this, arguments);

             // set wait message target
                this.getForm().waitMsgTarget = this.getEl();

            // loads form after initial layout
               if(this.all) this.on('afterlayout', this.onLoad, this, {single:true,delay:100});
        }
        ,onLoad:function(){
            this.load({
                url:this.url,
                waitMsg:'Loading...',
                params:{id:this.serviceId,method:'list_clientoptions'},
                success: function ( form, action ) {
                    var rec = action.result;
                    this.loadDefaultData(rec);
                    this.getForm().loadRecord(rec);
                },scope:this
            });

        }
        ,loadRecord:function(uuid,rec){

            this.uuid = uuid;
            if(rec){
                this.loadDefaultData(rec);
                this.on('afterlayout', function(){this.getForm().loadRecord(rec)}, this, {single:true});
            }

        }
        ,loadDefaultData:function(rec){
            var records = Ext.ux.util.clone(rec.data);

            for(field in records)
                rec.data[field+'-src'] = 1;            
                            
            var static_routes = rec.data['static-routes'];
            if(static_routes)
                rec.data['option_static-routes'] = static_routes.replace(' ',',');

            var slp_directory_agent = rec.data['option_slp-directory-agent'];
            if(slp_directory_agent){
                var found_true = slp_directory_agent.indexOf('true');
                if(found_true!=-1){
                    rec.data['option_slp-directory-agent'] = slp_directory_agent.replace('true ','');
                    rec.data['option_slp-directory-agent-ips'] = 1;
                }else{
                    rec.data['option_slp-directory-agent'] = slp_directory_agent.replace('false ','');
                    rec.data['option_slp-directory-agent-ips'] = 0;
                }
            }


            var slp_service_scope = rec.data['option_slp-service-scope'];
            if(slp_service_scope){
                var found_true = slp_service_scope.indexOf('true');
                if(found_true!=-1){
                    rec.data['option_slp-service-scope'] = slp_service_scope.replace('true ','');
                    rec.data['option_slp-service-scope-scope'] = 1
                }else{
                    rec.data['option_slp-service-scope'] = slp_service_scope.replace('false ','');
                    rec.data['option_slp-service-scope-scope'] = 0;
                }
            }





        }
        ,getDataSubmit:function(){
            var form_values = this.getForm().getValues();
            var send_data = new Object();

            send_data['option'] = new Object();

            for(prop in form_values){
                if(prop.indexOf('-src')!=-1 && form_values[prop]==1){

                    var index = prop.replace('-src','');

                    if(index.indexOf('option_') !=-1){
                        var option_index = index.replace('option_','');
                        send_data['option'][option_index] = form_values[index];
                    }else send_data[index] = form_values[index];

                }
            }

            if(form_values['use-host-decl-names'])
                send_data['use-host-decl-names'] = form_values['use-host-decl-names'];

            if(form_values["ddns-updates"])
                send_data["ddns-updates"] = form_values["ddns-updates"];

            if(form_values["ddns-update-style"])
                send_data["ddns-update-style"] = form_values["ddns-update-style"];

            if(form_values['authoritative'] && form_values['authoritative']==1) send_data['authoritative'] = '';

            //gather allow/deny data.....
            var allow_array = [];
            var deny_array = [];
            var ignore_array = [];

            switch(form_values['client-updates']){
                case 'allow':
                    allow_array.push('client-updates');
                    break;
                case 'deny':
                    deny_array.push('client-updates');
                    break;
                case 'ignore':
                    ignore_array.push('client-updates');
                    break;
                default: break;
            }

            switch(form_values['unknown-clients']){
                case 'allow':
                    allow_array.push('unknown-clients');
                    break;
                case 'deny':
                    deny_array.push('unknown-clients');
                    break;
                case 'ignore':
                    ignore_array.push('unknown-clients');
                    break;
                default:break;
            }

            if(allow_array.length>0) send_data['allow'] = allow_array;
            if(deny_array.length>0) send_data['deny'] = deny_array;
            if(ignore_array.length>0) send_data['ignore'] = ignore_array;

            if(send_data['option']['slp-service-scope'])
                if(form_values['option_slp-service-scope-scope'] == 1)
                    send_data['option']['slp-service-scope'] = 'true '+form_values['option_slp-service-scope'];
            else
                send_data['option']['slp-service-scope'] = 'false '+form_values['option_slp-service-scope'];

            if(send_data['option']['slp-directory-agent'])
                if(form_values['option_slp-directory-agent-ips'] == 1)
                    send_data['option']['slp-directory-agent'] = 'true '+form_values['option_slp-directory-agent'];
            else
                send_data['option']['slp-directory-agent'] = 'false '+form_values['option_slp-directory-agent'];


            return send_data;

        }
        ,onSave:function() {

            var send_data = this.getDataSubmit();
            send_data['uuid'] = this.uuid;

            var conn = new Ext.data.Connection({
                listeners:{
                    // wait message.....
                    beforerequest:function(){
                        Ext.MessageBox.show({
                            title: 'Please wait',
                            msg: 'Updating Client options info...',
                            width:300,
                            wait:true,
                            modal: false
                        });
                    },// on request complete hide message
                    requestcomplete:function(){Ext.MessageBox.hide();}
                }
            });// end conn


            conn.request({
                url: this.url,
                params:{id:this.serviceId,method:'set_options',params:Ext.encode(send_data)},
                failure: function(resp,opt){

                    if(!resp.responseText){
                        Ext.ux.Logger.error(resp.statusText);
                        return;
                    }

                    var response = Ext.util.JSON.decode(resp.responseText);
                    Ext.MessageBox.alert('Error Message', response['info']);
                    Ext.ux.Logger.error(response['error']);

                },
                // everything ok...
                success: function(resp,opt){
                    (this.ownerCt).ownerCt.close();
                    var msg = 'Client options edited successfully';
                    Ext.ux.Logger.info(msg);
                    View.notify({html:msg});
                    this.parent_grid.reload();
                },scope:this
            });// END Ajax request



        }
        ,onSaveAll:function() {

            var send_data = this.getDataSubmit();

            var conn = new Ext.data.Connection({
                listeners:{
                    // wait message.....
                    beforerequest:function(){
                        Ext.MessageBox.show({
                            title: 'Please wait',
                            msg: 'Updating General Client options info...',
                            width:300,
                            wait:true,
                            modal: false
                        });
                    },// on request complete hide message
                    requestcomplete:function(){Ext.MessageBox.hide();}
                }
            });// end conn


            conn.request({
                url: this.url,
                params:{id:this.serviceId,method:'set_clientoptions',params:Ext.encode(send_data)},
                failure: function(resp,opt){

                    if(!resp.responseText){
                        Ext.ux.Logger.error(resp.statusText);
                        return;
                    }

                    var response = Ext.util.JSON.decode(resp.responseText);
                    Ext.MessageBox.alert('Error Message', response['info']);
                    Ext.ux.Logger.error(response['error']);

                },
                // everything ok...
                success: function(resp,opt){
                    (this.ownerCt).close();
                    var msg = 'General Client options edited successfully';
                    Ext.ux.Logger.info(msg);
                    View.notify({html:msg});

                },scope:this
            });// END Ajax request



        }


    }); // eo extend

    ETFW_DHCP.Data_Form_DefaultItems = function(){
        var default_fields = {
                layout:'table',
                layoutConfig: {columns: 2},
                defaults:{layout:'form'},
                items: [{width:350,items:[
                            {
                                layout:'table',
                                layoutConfig: {columns: 3},
                                defaults:{layout:'form'},
                                items: [
                                    {items:[{xtype:'radio', fieldLabel:'Boot filename',name:'filename-src',boxLabel:'None',checked:true,inputValue: 0}]},
                                    {items:[{xtype:'radio', fieldLabel:'',hideLabel:true,name:'filename-src',boxLabel:'',inputValue: 1}]},
                                    {bodyStyle:'padding-bottom:5px;',items:[{xtype:'textfield',fieldLabel:'',name:'filename',hideLabel:true}]}
                                ]
                            },
                            //boot file server
                            {
                                layout:'table',
                                layoutConfig: {columns: 3},
                                defaults:{layout:'form'},
                                items: [
                                    {items:[{xtype:'radio', fieldLabel:'Boot file server',name:'next-server-src',boxLabel:'This server',checked:true,inputValue: 0}]},
                                    {items:[{xtype:'radio', fieldLabel:'',hideLabel:true,name:'next-server-src',boxLabel:'',inputValue: 1}]},
                                    {bodyStyle:'padding-bottom:5px;',items:[{xtype:'textfield',fieldLabel:'',name:'next-server',hideLabel:true}]}
                                ]
                            },
                            //Lease length for BOOTP clients
                            {
                                layout:'table',
                                layoutConfig: {columns: 4},
                                defaults:{layout:'form'},
                                items: [
                                    {items:[{xtype:'radio', fieldLabel:'Lease length for BOOTP clients',name:'dynamic-bootp-lease-length-src',boxLabel:'Forever',checked:true,inputValue: 0}]},
                                    {items:[{xtype:'radio', fieldLabel:'',hideLabel:true,name:'dynamic-bootp-lease-length-src',boxLabel:'',inputValue: 1}]},
                                    {bodyStyle:'padding-bottom:5px;',items:[{xtype:'textfield',name:'dynamic-bootp-lease-length',fieldLabel:'',hideLabel:true}]},
                                    {items:[{xtype:'displayfield',value:'secs',fieldLabel:'',hideLabel:true}]}
                                ]
                            },
                            //Dynamic DNS enabled?
                            {
                                layout:'table',
                                layoutConfig: {columns: 3},
                                defaults:{layout:'form'},
                                items: [
                                    {items:[{xtype:'radio', fieldLabel:'Dynamic DNS enabled?',name:'ddns-updates',boxLabel:'Yes',inputValue: 'on'}]},
                                    {items:[{xtype:'radio', fieldLabel:'',hideLabel:true,name:'ddns-updates',boxLabel:'No',inputValue: 'off'}]},
                                    {items:[{xtype:'radio', fieldLabel:'',hideLabel:true,name:'ddns-updates',checked:true,boxLabel:'Default',inputValue: ''}]}
                                ]
                            },
                            //Dynamic DNS reverse domain
                            {
                                layout:'table',
                                layoutConfig: {columns: 3},
                                defaults:{layout:'form'},
                                items: [
                                    {items:[{xtype:'radio', fieldLabel:'Dynamic DNS reverse domain',name:'ddns-rev-domainname-src',boxLabel:'Default',checked:true,inputValue: 0}]},
                                    {items:[{xtype:'radio', fieldLabel:'',hideLabel:true,name:'ddns-rev-domainname-src',boxLabel:'',inputValue: 1}]},
                                    {bodyStyle:'padding-bottom:5px;',items:[{xtype:'textfield',name:'ddns-rev-domainname',fieldLabel:'',hideLabel:true}]}
                                ]
                            }
                        ]},
                    // 2ns col
                    {bodyStyle:'padding-left:10px;',
                        items:[

                            //maximum lease time
                            {
                                layout:'table',
                                layoutConfig: {columns: 4},
                                defaults:{layout:'form'},
                                items: [
                                    {items:[{xtype:'radio', fieldLabel:'Maximum lease time',name:'max-lease-time-src',boxLabel:'Default',checked:true,inputValue: 0}]},
                                    {items:[{xtype:'radio', fieldLabel:'',hideLabel:true,name:'max-lease-time-src',boxLabel:'',inputValue: 1}]},
                                    {items:[{xtype:'textfield',fieldLabel:'',name:'max-lease-time',hideLabel:true}]},
                                    {items:[{xtype:'displayfield',value:'secs',fieldLabel:'',hideLabel:true}]}
                                ]
                            },
                            //server name
                            {
                                layout:'table',
                                layoutConfig: {columns: 3},
                                defaults:{layout:'form'},
                                items: [
                                    {items:[{xtype:'radio', fieldLabel:'Server name',name:'server-name-src',boxLabel:'Default',checked:true,inputValue: 0}]},
                                    {items:[{xtype:'radio', fieldLabel:'',hideLabel:true,name:'server-name-src',boxLabel:'',inputValue: 1}]},
                                    {bodyStyle:'padding-bottom:5px;',items:[{xtype:'textfield',name:'server-name',fieldLabel:'',hideLabel:true}]}
                                ]
                            },
                            //Lease end for BOOTP clients
                            {
                                layout:'table',
                                layoutConfig: {columns: 3},
                                defaults:{layout:'form'},
                                items: [
                                    {items:[{xtype:'radio', fieldLabel:'Lease end for BOOTP clients',name:'dynamic-bootp-lease-cutoff-src',boxLabel:'Never',checked:true,inputValue: 0}]},
                                    {items:[{xtype:'radio', fieldLabel:'',hideLabel:true,name:'dynamic-bootp-lease-cutoff-src',boxLabel:'',inputValue: 1}]},
                                    {bodyStyle:'padding-bottom:5px;',items:[{xtype:'textfield',fieldLabel:'',name:'dynamic-bootp-lease-cutoff',hideLabel:true}]}

                                    //{bodyStyle:'padding-bottom:5px;',items:[this.lease_end_dow]},
                                    // simple array store


                                    //{bodyStyle:'padding-bottom:5px;',items:[this.lease_end_date]},
                                    // {bodyStyle:'padding-bottom:5px;',items:[{xtype:'timefield',width:50,fieldLabel:'',hideLabel:true,name: 'endfrdt'}]}
                                ]
                            },
                            //Dynamic DNS domain name
                            {
                                layout:'table',
                                layoutConfig: {columns: 3},
                                defaults:{layout:'form'},
                                items: [
                                    {items:[{xtype:'radio', fieldLabel:'Dynamic DNS domain name',name:'ddns-domainname-src',boxLabel:'Default',checked:true,inputValue: 0}]},
                                    {items:[{xtype:'radio', fieldLabel:'',hideLabel:true,name:'ddns-domainname-src',boxLabel:'',inputValue: 1}]},
                                    {bodyStyle:'padding-bottom:5px;',items:[{xtype:'textfield',fieldLabel:'',name:'ddns-domainname',hideLabel:true}]}
                                ]
                            },
                            //Dynamic DNS hostname
                            {
                                layout:'table',
                                layoutConfig: {columns: 3},
                                defaults:{layout:'form'},
                                items: [
                                    {items:[{xtype:'radio', fieldLabel:'Dynamic DNS hostname',name:'ddns-hostname-src',boxLabel:'From client',checked:true,inputValue: 0}]},
                                    {items:[{xtype:'radio', fieldLabel:'',hideLabel:true,name:'ddns-hostname-src',boxLabel:'',inputValue: 1}]},
                                    {bodyStyle:'padding-bottom:5px;',items:[{xtype:'textfield',fieldLabel:'',name:'ddns-hostname',hideLabel:true}]}
                                ]
                            }
                        ]}// end 2nd col
                ]
            };// end layout 2 col
            return default_fields;
    }();

    ETFW_DHCP.Data_Form = Ext.extend(Ext.form.FormPanel, {

        // defaults - can be changed from outside
        border:false
        ,frame:true
        ,labelWidth:170
        ,url:<?php echo json_encode(url_for('etfw/json'))?>
        ,initComponent:function() {





            /*
             * Address ranges grid
             */

            // the check column is created using a custom plugin
            var address_ranges_checkColumn = new Ext.grid.CheckColumn({
                header: 'Dynamic BOOTP?',
                dataIndex: 'bootp',
                width: 110,
                editor:new Ext.form.Checkbox({validationEvent:false})
            });

            // the column model has information about grid columns
            // dataIndex maps the column to the specific data field in
            // the data store (created below)
            var address_ranges_cm = new Ext.grid.ColumnModel({
                // specify any defaults for each column
                defaults: {
                    sortable: true // columns are not sortable by default
                },
                columns: [
                    {
                        //  id: 'common',
                        header: 'From address',
                        dataIndex: 'from_range',
                        width: 100,
                        // use shorthand alias defined above
                        editor: new Ext.form.TextField({
                            allowBlank: false
                        })
                    }, {
                        header: 'To address',
                        dataIndex: 'to_range',
                        width: 100,
                        align: 'right',
                        editor: new Ext.form.TextField({
                            allowBlank: false
                        })
                    }
                    ,address_ranges_checkColumn // the plugin instance
                ]
            });

            var defaultEditor = new Ext.ux.grid.RowEditor({
                saveText: 'Update'
            });

            var address_ranges_store = new Ext.data.Store({
                reader: new Ext.data.JsonReader({
                    root:'range',
                    fields:['from_range','to_range','bootp']
                })
            });

            // create the grid
            this.address_ranges_grid = new Ext.grid.GridPanel({
                store: address_ranges_store,
                cm: address_ranges_cm,
                autoHeight: true,
                border:true,
                width:350,
                //   layout:'fit',
                viewConfig:{
                    forceFit:true,
                    emptyText: 'Empty!',  //  emptyText Message
                    deferEmptyText:false
                },
                plugins: [defaultEditor,address_ranges_checkColumn],
                tbar: [{
                        text: 'Add address range',
                        handler : function(){
                            // access the Record constructor through the grid's store
                            var Range = this.address_ranges_grid.getStore().recordType;
                            var r = new Range({
                                from_range: '0.0.0.0',
                                to_range: '0.0.0.0',
                                bootp: false
                            });


                            defaultEditor.stopEditing();

                            address_ranges_store.insert(0, r);
                            this.address_ranges_grid.getView().refresh();
                            this.address_ranges_grid.getSelectionModel().selectRow(0);


                            defaultEditor.startEditing(0);
                        }
                        ,scope:this}
                    ,{
                        ref: '../removeBtn',
                        text: 'Remove address range',
                        disabled: true,
                        handler: function(){
                            defaultEditor.stopEditing();
                            var s = this.address_ranges_grid.getSelectionModel().getSelections();
                            for(var i = 0, r; r = s[i]; i++){
                                address_ranges_store.remove(r);
                            }
                        },scope:this
                    }]

            });

            this.address_ranges_grid.getSelectionModel().on('selectionchange', function(sm){
                this.address_ranges_grid.removeBtn.setDisabled(sm.getCount() < 1);
            },this);

            /*
             * END Address ranges grid
             */


            var parent_type = this.parent_grid.getXType();

            var config = {};
            switch(parent_type){
                case 'etfw_dhcp_poolgrid':
                                    config = this.buildPoolForm();
                                    this.parent_uuid = (this.parent_grid.filters.getFilter('parent-uuid')).getValue();
                                    break;
                case 'etfw_dhcp_networkgrid':
                                    switch(this.parent_grid.type){
                                        case 'subnet':
                                                        config = this.buildSubnetForm();
                                                        break;
                                        case 'shared-network':
                                                        config = this.buildSharedForm();
                                                        break;
                                        default:break;
                                    }
                                    break;
                case 'etfw_dhcp_hostgrid':
                                    switch(this.parent_grid.type){
                                        case 'host':
                                                        config = this.buildHostForm();
                                                        break;
                                        case 'group':
                                                        config = this.buildGroupForm();
                                                        break;
                                        default:break;
                                    }
                                    break;
                default:break;
            }




            // apply config
            Ext.apply(this, Ext.apply(this.initialConfig, config));


            this.on('beforerender',function(){
                Ext.getBody().mask('Loading form data...');

            },this);

            this.on('render',function(){
                Ext.getBody().unmask();}
            ,this
            ,{delay:10}
        );

            // call parent
            ETFW_DHCP.Data_Form.superclass.initComponent.apply(this, arguments);



        } // eo function initComponent
        ,buildDefaultFields:function(){
            return ETFW_DHCP.Data_Form_DefaultItems;
        }
        ,buildSubnetForm:function(){
            var default_fields = this.buildDefaultFields();


            //shared network combo

            var sharedNet_store = new Ext.data.Store({
                reader: new Ext.data.JsonReader({
                    root:'data',
                    fields:['uuid','value']
                })
            });
            sharedNet_store.setDefaultSort('value', 'ASC');

            this.delBtn = new Ext.Button({text:'Delete'
                ,scope:this
                ,hidden:true
                ,handler:function(){

                    Ext.MessageBox.show({
                        title:'Delete subnet',
                        msg: 'You are about to delete this subnet. <br />Are you sure you want to delete?',
                        buttons: Ext.MessageBox.YESNOCANCEL,
                        fn: function(btn){
                            (this.ownerCt).ownerCt.close();
                            (this.parent_grid).onDeleteSubnet(btn);
                        },
                        scope:this,
                        icon: Ext.MessageBox.QUESTION
                    });

                }
            });

            this.saveBtn = new Ext.Button({text:'Create'
                ,scope:this
                ,handler:this.onCreateSubnet
            });

            this.sharedNetCombo = new Ext.form.ComboBox({
                mode: 'local',
                triggerAction: 'all',
                editable:false,
                fieldLabel: 'Shared network',
                forceSelection:true,
                allowBlank: false,
                readOnly:true,
                store:sharedNet_store,
                valueField: 'uuid',
                displayField: 'value',
                width:90


            });

            // set selected value on load
            sharedNet_store.on('load',function(){
                sharedNet_store.addSorted(new sharedNet_store.recordType({value:'--None--',uuid:''}));
                this.sharedNetCombo.setValue('');

            },this);



            this.hosts_directly = new Ext.ux.Multiselect({
                fieldLabel:"Hosts directly in this subnet",
                valueField:"uuid",
                displayField:"host",
                height:80,
                allowBlank:true,
                store :new Ext.data.Store({
                    reader: new Ext.data.JsonReader({
                        root:'data',
                        fields:['uuid','host']
                    })
                })
            });

            this.groups_directly = new Ext.ux.Multiselect({
                fieldLabel:"Groups directly in this subnet",
                valueField:"uuid",
                displayField:"hosts_count",
                height:80,
                allowBlank:true,
                store:new Ext.data.Store({
                    reader: new Ext.data.JsonReader({
                        root:'data',
                        fields:['uuid','hosts_count']
                    })
                })
            });

            var config = {
                defaultType:'textfield'
                // ,defaults:{anchor:'-24'}
                ,monitorValid:true
                ,autoScroll:true
                // ,buttonAlign:'right'
                ,items:[
                    {
                        xtype:'fieldset',
                        labelWidth:120,
                        collapsible:true,
                        title: 'Subnet details',
                        items :[
                            {xtype:'textfield',width:200,name:'lastcomment',fieldLabel:'Subnet description'},
                            {
                                layout:'table',
                                layoutConfig: {columns: 2},
                                defaults:{layout:'form'},
                                items: [{items:[{xtype:'textfield',name:'address',fieldLabel:'Network address'}]},
                                    {labelWidth:60,bodyStyle:'padding-left:10px',items:[{xtype:'textfield',name:'netmask',fieldLabel: 'Netmask'}]}
                                ]
                            },
                            {
                                layout:'table',
                                layoutConfig: {columns:2},
                                items:[
                                    {
                                        labelAlign:'left',
                                        layout:'form',
                                        items:[{fieldLabel:'Address ranges'}]
                                    },
                                    {
                                        labelAlign:'left',
                                        // layout:'form',
                                        bodyStyle: 'padding-bottom:10px;',
                                        items:this.address_ranges_grid
                                    }
                                ]
                            },

                            {
                                layout:'table',
                                layoutConfig: {columns: 2},
                                defaults:{layout:'form'},
                                items: [{width:350,items:[this.sharedNetCombo]},
                                    // 2nd col
                                    {bodyStyle:'padding-left:10px;',
                                        items:[
                                            {
                                                layout:'table',
                                                layoutConfig: {columns: 4},
                                                defaults:{layout:'form'},
                                                items: [
                                                    {items:[{xtype:'radio', fieldLabel:'Default lease time',name:'default-lease-time-src',boxLabel:'Default',checked:true,inputValue: 0}]},
                                                    {items:[{xtype:'radio', fieldLabel:'',hideLabel:true,name:'default-lease-time-src',boxLabel:'',inputValue: 1}]},
                                                    {items:[{xtype:'textfield',fieldLabel:'',name:'default-lease-time',hideLabel:true}]},
                                                    {items:[{xtype:'displayfield',value:'secs',fieldLabel:'',hideLabel:true}]}
                                                ]
                                            }
                                        ]}// end 2nd col
                                ]
                            },// end layout 2 col
                            default_fields,
                            //Allow unknown clients?
                            {
                                layout:'table',
                                layoutConfig: {columns: 4},
                                defaults:{layout:'form'},
                                items: [
                                    {items:[{xtype:'radio', fieldLabel:'Allow unknown clients?',name:'unknown-clients',boxLabel:'Allow',inputValue: 'allow'}]},
                                    {items:[{xtype:'radio', fieldLabel:'',hideLabel:true,name:'unknown-clients',boxLabel:'Deny',inputValue: 'deny'}]},
                                    {items:[{xtype:'radio', fieldLabel:'',hideLabel:true,name:'unknown-clients',boxLabel:'Ignore',inputValue: 'ignore'}]},
                                    {items:[{xtype:'radio', fieldLabel:'',hideLabel:true,name:'unknown-clients',boxLabel:'Default',checked:true,inputValue: ''}]}
                                ]
                            },
                            //Can clients update their own records?
                            {
                                layout:'table',
                                layoutConfig: {columns: 4},
                                defaults:{layout:'form'},
                                items: [
                                    {items:[{xtype:'radio', fieldLabel:'Can clients update their own records?',name:'client-updates',boxLabel:'Allow',inputValue: 'allow'}]},
                                    {items:[{xtype:'radio', fieldLabel:'',name:'client-updates',hideLabel:true,boxLabel:'Deny',inputValue: 'deny'}]},
                                    {items:[{xtype:'radio', fieldLabel:'',name:'client-updates',hideLabel:true,boxLabel:'Ignore',inputValue: 'ignore'}]},
                                    {items:[{xtype:'radio', fieldLabel:'',name:'client-updates',hideLabel:true,boxLabel:'Default',checked:true,inputValue: ''}]}
                                ]
                            },
                            //Server is authoritative for this subnet?
                            {
                                layout:'table',
                                layoutConfig: {columns: 2},
                                defaults:{layout:'form'},
                                items: [
                                    {items:[{xtype:'radio', fieldLabel:'Server is authoritative for this subnet?',name:'authoritative',boxLabel:'Yes',inputValue: 1}]},
                                    {items:[{xtype:'radio', fieldLabel:'',hideLabel:true,name:'authoritative',boxLabel:'Default(No)',checked:true,inputValue: 0}]}
                                ]
                            },
                            {
                                layout:'table',
                                layoutConfig: {columns: 2},
                                defaults:{layout:'form'},
                                items: [
                                    {items:[
                                            this.hosts_directly
                                        ]},
                                    {bodyStyle:'padding-left:10px',
                                        items:[
                                            this.groups_directly
                                        ]}
                                ]
                            }



                        ]
                    }

                ],
                buttons:[this.saveBtn,this.delBtn]

            }; // eo config object
            return config;
        }
        ,buildSharedForm:function(){
            var default_fields = this.buildDefaultFields();

            this.delBtn = new Ext.Button({text:'Delete'
                ,scope:this
                ,hidden:true
                ,handler:function(){

                    Ext.MessageBox.show({
                        title:'Delete subnet',
                        msg: 'You are about to delete this shared network. <br />Are you sure you want to delete?',
                        buttons: Ext.MessageBox.YESNOCANCEL,
                        fn: function(btn){
                            (this.ownerCt).ownerCt.close();
                            (this.parent_grid).onDeleteShared(btn);
                        },
                        scope:this,
                        icon: Ext.MessageBox.QUESTION
                    });

                }
            });

            this.saveBtn = new Ext.Button({text:'Create'
                ,scope:this
                ,handler:this.onCreateShared
            });


            this.hosts_directly = new Ext.ux.Multiselect({
                fieldLabel:"Hosts directly in this shared network",
                valueField:"uuid",
                displayField:"host",
                height:80,
                allowBlank:true,
                store :new Ext.data.Store({
                    reader: new Ext.data.JsonReader({
                        root:'data',
                        fields:['uuid','host']
                    })
                })
            });

            this.groups_directly = new Ext.ux.Multiselect({
                fieldLabel:"Groups directly in this shared network",
                valueField:"uuid",
                displayField:"hosts_count",
                height:80,
                allowBlank:true,
                store:new Ext.data.Store({
                    reader: new Ext.data.JsonReader({
                        root:'data',
                        fields:['uuid','hosts_count']
                    })
                })
            });


            this.subnets_directly = new Ext.ux.Multiselect({
                fieldLabel:"Subnets in this shared network",
                valueField:"uuid",
                displayField:"subnet",
                height:80,
                allowBlank:true,
                store:new Ext.data.Store({
                    reader: new Ext.data.JsonReader({
                        root:'data',
                        fields:['uuid','subnet']
                    })
                })
            });

            var config = {
                defaultType:'textfield'
                // ,defaults:{anchor:'-24'}
                ,monitorValid:true
                ,autoScroll:true
                // ,buttonAlign:'right'
                ,items:[
                    {
                        xtype:'fieldset',
                        labelWidth:120,
                        collapsible:true,
                        title: 'Shared Network details',
                        items :[
                            {xtype:'textfield',width:200,name:'lastcomment',fieldLabel:'Shared network description'},
                            {
                                layout:'table',
                                layoutConfig: {columns: 2},
                                defaults:{layout:'form'},
                                items: [{width:350,items:[{xtype:'textfield',name:'name',fieldLabel:'Network name'}]},
                                    // 2nd col
                                    {bodyStyle:'padding-left:10px;',
                                        items:[
                                            {
                                                layout:'table',
                                                layoutConfig: {columns: 4},
                                                defaults:{layout:'form'},
                                                items: [
                                                    {items:[{xtype:'radio', fieldLabel:'Default lease time',name:'default-lease-time-src',boxLabel:'Default',checked:true,inputValue: 0}]},
                                                    {items:[{xtype:'radio', fieldLabel:'',hideLabel:true,name:'default-lease-time-src',boxLabel:'',inputValue: 1}]},
                                                    {items:[{xtype:'textfield',fieldLabel:'',name:'default-lease-time',hideLabel:true}]},
                                                    {items:[{xtype:'displayfield',value:'secs',fieldLabel:'',hideLabel:true}]}
                                                ]
                                            }
                                        ]}// end 2nd col
                                ]
                            },// end layout 2 col
                            default_fields,
                            //Allow unknown clients?
                            {
                                layout:'table',
                                layoutConfig: {columns: 4},
                                defaults:{layout:'form'},
                                items: [
                                    {items:[{xtype:'radio', fieldLabel:'Allow unknown clients?',name:'unknown-clients',boxLabel:'Allow',inputValue: 'allow'}]},
                                    {items:[{xtype:'radio', fieldLabel:'',hideLabel:true,name:'unknown-clients',boxLabel:'Deny',inputValue: 'deny'}]},
                                    {items:[{xtype:'radio', fieldLabel:'',hideLabel:true,name:'unknown-clients',boxLabel:'Ignore',inputValue: 'ignore'}]},
                                    {items:[{xtype:'radio', fieldLabel:'',hideLabel:true,name:'unknown-clients',boxLabel:'Default',checked:true,inputValue: ''}]}
                                ]
                            },
                            //Can clients update their own records?
                            {
                                layout:'table',
                                layoutConfig: {columns: 4},
                                defaults:{layout:'form'},
                                items: [
                                    {items:[{xtype:'radio', fieldLabel:'Can clients update their own records?',name:'client-updates',boxLabel:'Allow',inputValue: 'allow'}]},
                                    {items:[{xtype:'radio', fieldLabel:'',name:'client-updates',hideLabel:true,boxLabel:'Deny',inputValue: 'deny'}]},
                                    {items:[{xtype:'radio', fieldLabel:'',name:'client-updates',hideLabel:true,boxLabel:'Ignore',inputValue: 'ignore'}]},
                                    {items:[{xtype:'radio', fieldLabel:'',name:'client-updates',hideLabel:true, checked:true, boxLabel:'Default',inputValue: ''}]}
                                ]
                            },
                            //Server is authoritative for this subnet?
                            {
                                layout:'table',
                                layoutConfig: {columns: 2},
                                defaults:{layout:'form'},
                                items: [
                                    {items:[{xtype:'radio', fieldLabel:'Server is authoritative for this subnet?',name:'authoritative',boxLabel:'Yes',inputValue: 1}]},
                                    {items:[{xtype:'radio', fieldLabel:'',hideLabel:true,name:'authoritative',boxLabel:'Default(No)',checked:true,inputValue: 0}]}
                                ]
                            },
                            {
                                layout:'table',
                                layoutConfig: {columns: 3},
                                defaults:{layout:'form'},
                                items: [
                                    {items:[
                                            this.hosts_directly
                                        ]},
                                    {bodyStyle:'padding-left:10px',
                                        items:[
                                            this.groups_directly
                                        ]},
                                    {bodyStyle:'padding-left:10px',
                                        items:[
                                            this.subnets_directly
                                        ]}
                                ]
                            }



                        ]
                    }

                ],
                buttons:[this.saveBtn,this.delBtn]

            }; // eo config object
            return config;
        }
        ,buildPoolForm:function(){
            var default_fields = this.buildDefaultFields();

            this.delBtn = new Ext.Button({text:'Delete'
                ,scope:this
                ,hidden:true
                ,handler:function(){

                    Ext.MessageBox.show({
                        title:'Delete pool',
                        msg: 'You are about to delete this pool. <br />Are you sure you want to delete?',
                        buttons: Ext.MessageBox.YESNOCANCEL,
                        fn: function(btn){
                            (this.ownerCt).close();
                            (this.parent_grid).onDeletePool(btn);
                        },
                        scope:this,
                        icon: Ext.MessageBox.QUESTION
                    });

                }
            });

            this.saveBtn = new Ext.Button({text:'Create'
                ,scope:this
                ,handler:this.onCreatePool
            });

            var config = {
                defaultType:'textfield'
                // ,defaults:{anchor:'-24'}
                ,monitorValid:true
                ,autoScroll:true
                // ,buttonAlign:'right'
                ,items:[
                    {
                        xtype:'fieldset',
                        labelWidth:120,
                        collapsible:true,
                        title: 'Address pool options',
                        items :[

                            {
                                layout:'table',
                                layoutConfig: {columns:2},
                                items:[
                                    {
                                        labelAlign:'left',
                                        layout:'form',
                                        items:[{fieldLabel:'Address ranges'}]
                                    },
                                    {
                                        labelAlign:'left',
                                        // layout:'form',
                                        bodyStyle: 'padding-bottom:10px;',
                                        items:this.address_ranges_grid
                                    }
                                ]
                            },
                            {
                                layout:'table',
                                layoutConfig: {columns: 3},
                                defaults:{layout:'form'},
                                items: [
                                    {items:[{xtype:'radio', fieldLabel:'Failover Peer',name:'failover-src',boxLabel:'None',checked:true,inputValue: 0}]},
                                    {items:[{xtype:'radio', fieldLabel:'',hideLabel:true,name:'failover-src',boxLabel:'',inputValue: 1}]},
                                    {items:[{xtype:'textfield',fieldLabel:'',name:'failover',hideLabel:true}]}
                                ]
                            },
                            {
                                layout:'table',
                                layoutConfig: {columns: 2},
                                defaults:{layout:'form'},
                                items: [{width:350,items:[{xtype:'textarea',width:200,name:'allow',fieldLabel:'Clients to allow'}]},
                                    // 2nd col
                                    {bodyStyle:'padding-left:10px;',
                                        items:[
                                            {xtype:'textarea',width:200,name:'deny',fieldLabel:'Clients to deny'}]}
                                ]
                            },
                            {
                                layout:'table',
                                layoutConfig: {columns: 4},
                                defaults:{layout:'form'},
                                items: [
                                    {items:[{xtype:'radio', fieldLabel:'Default lease time',name:'default-lease-time-src',boxLabel:'Default',checked:true,inputValue: 0}]},
                                    {items:[{xtype:'radio', fieldLabel:'',hideLabel:true,name:'default-lease-time-src',boxLabel:'',inputValue: 1}]},
                                    {items:[{xtype:'textfield',fieldLabel:'',name:'default-lease-time',hideLabel:true}]},
                                    {items:[{xtype:'displayfield',value:'secs',fieldLabel:'',hideLabel:true}]}
                                ]
                            },
                            default_fields,
                            //Allow unknown clients?
                            {
                                layout:'table',
                                layoutConfig: {columns: 4},
                                defaults:{layout:'form'},
                                items: [
                                    {items:[{xtype:'radio', fieldLabel:'Allow unknown clients?',name:'unknown-clients',boxLabel:'Allow',inputValue: 'allow'}]},
                                    {items:[{xtype:'radio', fieldLabel:'',hideLabel:true,name:'unknown-clients',boxLabel:'Deny',inputValue: 'deny'}]},
                                    {items:[{xtype:'radio', fieldLabel:'',hideLabel:true,name:'unknown-clients',boxLabel:'Ignore',inputValue: 'ignore'}]},
                                    {items:[{xtype:'radio', fieldLabel:'',hideLabel:true,name:'unknown-clients',boxLabel:'Default',checked:true,inputValue: ''}]}
                                ]
                            },
                            //Can clients update their own records?
                            {
                                layout:'table',
                                layoutConfig: {columns: 4},
                                defaults:{layout:'form'},
                                items: [
                                    {items:[{xtype:'radio', fieldLabel:'Can clients update their own records?',name:'client-updates',boxLabel:'Allow',inputValue: 'allow'}]},
                                    {items:[{xtype:'radio', fieldLabel:'',name:'client-updates',hideLabel:true,boxLabel:'Deny',inputValue: 'deny'}]},
                                    {items:[{xtype:'radio', fieldLabel:'',name:'client-updates',hideLabel:true,boxLabel:'Ignore',inputValue: 'ignore'}]},
                                    {items:[{xtype:'radio', fieldLabel:'',name:'client-updates',hideLabel:true,boxLabel:'Default',checked:true,inputValue: ''}]}
                                ]
                            }




                        ]
                    }
                ],
                buttons:[this.saveBtn,this.delBtn]

            }; // eo config object

            return config;
        }
        ,buildHostForm:function(){
            var default_fields = this.buildDefaultFields();


            this.delBtn = new Ext.Button({text:'Delete'
                ,scope:this
                ,hidden:true
                ,handler:function(){

                    Ext.MessageBox.show({
                        title:'Delete subnet',
                        msg: 'You are about to delete this subnet. <br />Are you sure you want to delete?',
                        buttons: Ext.MessageBox.YESNOCANCEL,
                        fn: this.onDelete,
                        scope:this,
                        icon: Ext.MessageBox.QUESTION
                    });

                }
            });

            this.saveBtn = new Ext.Button({text:'Create'
                ,scope:this
                ,handler:this.onCreateHost
            });





            this.hardwareCombo = new Ext.form.ComboBox({

                name:'hardware_src'
                ,fieldLabel:'Hardware address'
                ,xtype:'combo'
                ,triggerAction:'all'
                ,mode:'local'
                ,width:100
                ,editable:false
                ,store:["ethernet",
                    "token-ring",
                    "fddi"]
                ,value:'ethernet'
                // ,validator:function(value){
                //           if(value=='') this.setValue(this.originalValue);
                //         return true;
                // }
                ,scope:this


            });








            this.host_assign_combo = new Ext.form.ComboBox({
                fieldLabel:'Host assigned to'
                ,triggerAction:'all'
                ,mode:'local'
                ,width:100
                ,displayField:'name'
                ,valueField:'value'
                ,editable:false
                ,value:'toplevel'
                ,store: new Ext.data.SimpleStore({
                    fields:['value', 'name']
                    ,data:[["toplevel","Toplevel"],
                        ["shared-network","Shared network"],
                        ["subnet","Subnet"],
                        ["group","Group"]]
                })
                ,listeners:{select:{fn:function(combo, value) {

                            this.host_assign.enable();
                            this.host_assign.store.filter('type', combo.getValue());

                            var data_aux = this.host_assign.store.getAt(0);
                            if(data_aux) this.host_assign.setValue(data_aux.data.uuid);
                            //this.hosts_directly.setValue();
                            if(combo.getValue()=='toplevel') this.host_assign.disable();
                            //    else this.hosts_directly.setValue(this.hosts_directly.store.getAt(0).data.uuid);

                        },scope:this}
                }
                //,scope:this



                // ,validator:function(value){
                //           if(value=='') this.setValue(this.originalValue);
                //         return true;
                // }



                //                fieldLabel:"Hosts directly in this subnet",
                //                valueField:"uuid",
                //                displayField:"host",
                //                minLength:1,
                //                maxLength:1,
                //                height:80,
                //                allowBlank:true,
                //                store :new Ext.data.Store({
                //                    reader: new Ext.data.JsonReader({
                //                        root:'data',
                //                        fields:['uuid','host']
                //                    })
                //                })
            });

            this.host_assign = new Ext.ux.Multiselect({
                valueField:"uuid",
                displayField:"value",
                minLength:1,
                maxLength:1,
                width:200,
                height:80,
                allowBlank:true,
                //   store: new Ext.data.SimpleStore({
                //                                             fields:['uuid', 'value']
                //                                           ,data:[['1','dsdsd']]
                //                             })
                store :new Ext.data.Store({
                    reader: new Ext.data.JsonReader({
                        root:'data',
                        fields:['uuid','type','value']
                    })
                    ,listeners:{load:{fn:function() {
                                //this.host_assign_combo.setValue("toplevel");
                                //this.host_assign_combo.disable();
                                this.host_assign_combo.fireEvent("select",this.host_assign_combo);

                            },scope:this}
                    }

                    //,scope:this

                })
                ,listeners:{

                    change:function(multiselect,value,hvalue){

                        if(value=='') multiselect.setValue(hvalue);

                    },
                    click: function(multiselect,e) {

                        var values = multiselect.getValue();
                        var array_values = [];
                        if(values.indexOf(',')!=-1){
                            array_values = values.split(',');
                            multiselect.setValue(array_values[0]);
                        }


                    }

                }


            });



            var config = {
                defaultType:'textfield'
                // ,defaults:{anchor:'-24'}
                ,monitorValid:true
                ,autoScroll:true
                // ,buttonAlign:'right'
                ,items:[
                    {
                        xtype:'fieldset',
                        labelWidth:120,
                        collapsible:true,
                        title: 'Host details',
                        items :[
                            {xtype:'textfield',width:200,name:'lastcomment',fieldLabel:'Host description'},
                            {
                                layout:'table',
                                layoutConfig: {columns: 2},
                                defaults:{layout:'form'},
                                items: [{items:[{xtype:'textfield',name:'host',fieldLabel:'Host name'},

                                            {
                                                layout:'table',
                                                layoutConfig: {columns:2},
                                                items:[
                                                    {
                                                        labelAlign:'left',
                                                        layout:'form',
                                                        items:this.hardwareCombo
                                                    },
                                                    {
                                                        labelAlign:'left',
                                                        // layout:'form',
                                                        bodyStyle: 'padding-bottom:10px;',
                                                        items:[{xtype:'textfield',name:'hardware',fieldLabel:'',hideLabel:true}]
                                                    }
                                                ]
                                            }

                                        ]},
                                    {labelWidth:60,bodyStyle:'padding-left:10px',items:[

                                            {
                                                layout:'table',
                                                layoutConfig: {columns:2},
                                                items:[
                                                    {
                                                        labelAlign:'left',
                                                        layout:'form',labelAlign:'top',
                                                        items:[this.host_assign_combo]
                                                    },
                                                    {
                                                        labelAlign:'left',
                                                        // layout:'form',
                                                        bodyStyle: 'padding-bottom:10px;',width:200,
                                                        items:[this.host_assign]
                                                    }
                                                ]
                                            }

                                        ]}
                                ]
                            },

                            {
                                layout:'table',
                                layoutConfig: {columns: 2},
                                defaults:{layout:'form'},
                                items: [{width:350,items:[{xtype:'textfield',name:'fixed-address',fieldLabel:'Fixed IP address'}]},
                                    // 2nd col
                                    {bodyStyle:'padding-left:10px;',
                                        items:[
                                            {
                                                layout:'table',
                                                layoutConfig: {columns: 4},
                                                defaults:{layout:'form'},
                                                items: [
                                                    {items:[{xtype:'radio', fieldLabel:'Default lease time',name:'default-lease-time-src',boxLabel:'Default',checked:true,inputValue: 0}]},
                                                    {items:[{xtype:'radio', fieldLabel:'',hideLabel:true,name:'default-lease-time-src',boxLabel:'',inputValue: 1}]},
                                                    {items:[{xtype:'textfield',fieldLabel:'',name:'default-lease-time',hideLabel:true}]},
                                                    {items:[{xtype:'displayfield',value:'secs',fieldLabel:'',hideLabel:true}]}
                                                ]
                                            }
                                        ]}// end 2nd col
                                ]
                            },// end layout 2 col
                            default_fields,
                            //Allow unknown clients?
                            {
                                layout:'table',
                                layoutConfig: {columns: 4},
                                defaults:{layout:'form'},
                                items: [
                                    {items:[{xtype:'radio', fieldLabel:'Allow unknown clients?',name:'unknown-clients',boxLabel:'Allow',inputValue: 'allow'}]},
                                    {items:[{xtype:'radio', fieldLabel:'',hideLabel:true,name:'unknown-clients',boxLabel:'Deny',inputValue: 'deny'}]},
                                    {items:[{xtype:'radio', fieldLabel:'',hideLabel:true,name:'unknown-clients',boxLabel:'Ignore',inputValue: 'ignore'}]},
                                    {items:[{xtype:'radio', fieldLabel:'',hideLabel:true,name:'unknown-clients',boxLabel:'Default',checked:true,inputValue: ''}]}
                                ]
                            },
                            //Can clients update their own records?
                            {
                                layout:'table',
                                layoutConfig: {columns: 4},
                                defaults:{layout:'form'},
                                items: [
                                    {items:[{xtype:'radio', fieldLabel:'Can clients update their own records?',name:'client-updates',boxLabel:'Allow',inputValue: 'allow'}]},
                                    {items:[{xtype:'radio', fieldLabel:'',name:'client-updates',hideLabel:true,boxLabel:'Deny',inputValue: 'deny'}]},
                                    {items:[{xtype:'radio', fieldLabel:'',name:'client-updates',hideLabel:true,boxLabel:'Ignore',inputValue: 'ignore'}]},
                                    {items:[{xtype:'radio', fieldLabel:'',name:'client-updates',hideLabel:true,boxLabel:'Default',checked:true,inputValue: ''}]}
                                ]
                            }




                        ]
                    }

                ],
                buttons:[this.saveBtn,this.delBtn]

            }; // eo config object
            return config;
        }
        ,buildGroupForm:function(){
            var default_fields = this.buildDefaultFields();


            this.delBtn = new Ext.Button({text:'Delete'
                ,scope:this
                ,hidden:true
                ,handler:function(){

                    Ext.MessageBox.show({
                        title:'Delete host group',
                        msg: 'You are about to delete this host group. <br />Are you sure you want to delete?',
                        buttons: Ext.MessageBox.YESNOCANCEL,
                        fn: function(btn){
                            (this.ownerCt).ownerCt.close();
                            (this.parent_grid).onDeleteGroup(btn);
                        },
                        scope:this,
                        icon: Ext.MessageBox.QUESTION
                    });

                }
            });

            this.saveBtn = new Ext.Button({text:'Create'
                ,scope:this
                ,handler:this.onCreateGroup
            });









            this.group_assign_combo = new Ext.form.ComboBox({
                fieldLabel:'Group assigned to'
                ,triggerAction:'all'
                ,mode:'local'
                ,width:100
                ,displayField:'name'
                ,valueField:'value'
                ,editable:false
                ,value:'toplevel'
                ,store: new Ext.data.SimpleStore({
                    fields:['value', 'name']
                    ,data:[["toplevel","Toplevel"],
                        ["shared-network","Shared network"],
                        ["subnet","Subnet"]]
                })
                ,listeners:{select:{fn:function(combo, value) {

                            this.group_assign.enable();
                            this.group_assign.store.filter('type', combo.getValue());

                            var data_aux = this.group_assign.store.getAt(0);
                            if(data_aux) this.group_assign.setValue(data_aux.data.uuid);
                            //this.hosts_directly.setValue();
                            if(combo.getValue()=='toplevel') this.group_assign.disable();
                            //    else this.hosts_directly.setValue(this.hosts_directly.store.getAt(0).data.uuid);

                        },scope:this},
                    render:function(){
                        this.fireEvent("select",this);
                    }
                }

            });

            this.group_assign = new Ext.ux.Multiselect({
                valueField:"uuid",
                displayField:"value",
                minLength:1,
                maxLength:1,
                width:200,
                height:80,
                allowBlank:true,
                store :new Ext.data.Store({
                    reader: new Ext.data.JsonReader({
                        root:'data',
                        fields:['uuid','type','value']
                    })
                    ,listeners:{load:{fn:function() {
                                this.group_assign_combo.fireEvent("select",this.group_assign_combo);
                            },scope:this}
                    }

                    //,scope:this

                })
                ,listeners:{

                    change:function(multiselect,value,hvalue){

                        if(value=='') multiselect.setValue(hvalue);

                    },
                    click: function(multiselect,e) {

                        var values = multiselect.getValue();
                        var array_values = [];
                        if(values.indexOf(',')!=-1){
                            array_values = values.split(',');
                            multiselect.setValue(array_values[0]);
                        }


                    }

                }


            });




            this.hosts_directly = new Ext.ux.Multiselect({
                fieldLabel:"Hosts in this group",
                valueField:"uuid",
                displayField:"host",
                height:80,
                allowBlank:true,
                store :new Ext.data.Store({
                    reader: new Ext.data.JsonReader({
                        root:'data',
                        fields:['uuid','host']
                    })
                })
            });



            var config = {
                defaultType:'textfield'
                // ,defaults:{anchor:'-24'}
                ,monitorValid:true
                ,autoScroll:true
                // ,buttonAlign:'right'
                ,items:[
                    {
                        xtype:'fieldset',
                        labelWidth:120,
                        collapsible:true,
                        title: 'Group details',
                        items :[
                            {xtype:'textfield',width:200,name:'lastcomment',fieldLabel:'Group description'},
                            {
                                layout:'table',
                                layoutConfig: {columns: 2},
                                defaults:{layout:'form'},
                                items: [{width:350,items:[this.hosts_directly]},
                                    {labelWidth:60,bodyStyle:'padding-left:10px',items:[

                                            {
                                                layout:'table',
                                                layoutConfig: {columns:2},
                                                items:[
                                                    {
                                                        labelAlign:'left',
                                                        layout:'form',labelAlign:'top',
                                                        items:[this.group_assign_combo]
                                                    },
                                                    {
                                                        labelAlign:'left',
                                                        // layout:'form',
                                                        bodyStyle: 'padding-bottom:10px;',width:200,
                                                        items:[this.group_assign]
                                                    }
                                                ]
                                            }

                                        ]}
                                ]
                            },

                            {
                                layout:'table',
                                layoutConfig: {columns: 2},
                                defaults:{layout:'form'},
                                items: [{width:350,items:[

                                            {
                                                layout:'table',
                                                layoutConfig: {columns: 3},
                                                defaults:{layout:'form'},
                                                items: [
                                                    {items:[{xtype:'radio', fieldLabel:'Use name as client hostname?',name:'use-host-decl-names',boxLabel:'Yes',inputValue: 'on'}]},
                                                    {items:[{xtype:'radio', fieldLabel:'',hideLabel:true,name:'use-host-decl-names',boxLabel:'No',inputValue: 'off'}]},
                                                    {items:[{xtype:'radio', fieldLabel:'',hideLabel:true,name:'use-host-decl-names',checked:true,boxLabel:'Default',inputValue: ''}]}
                                                ]
                                            }

                                        ]},
                                    // 2nd col
                                    {bodyStyle:'padding-left:10px;',
                                        items:[
                                            {
                                                layout:'table',
                                                layoutConfig: {columns: 4},
                                                defaults:{layout:'form'},
                                                items: [
                                                    {items:[{xtype:'radio', fieldLabel:'Default lease time',name:'default-lease-time-src',boxLabel:'Default',checked:true,inputValue: 0}]},
                                                    {items:[{xtype:'radio', fieldLabel:'',hideLabel:true,name:'default-lease-time-src',boxLabel:'',inputValue: 1}]},
                                                    {items:[{xtype:'textfield',fieldLabel:'',name:'default-lease-time',hideLabel:true}]},
                                                    {items:[{xtype:'displayfield',value:'secs',fieldLabel:'',hideLabel:true}]}
                                                ]
                                            }
                                        ]}// end 2nd col
                                ]
                            },// end layout 2 col
                            default_fields,
                            //Allow unknown clients?
                            {
                                layout:'table',
                                layoutConfig: {columns: 4},
                                defaults:{layout:'form'},
                                items: [
                                    {items:[{xtype:'radio', fieldLabel:'Allow unknown clients?',name:'unknown-clients',boxLabel:'Allow',inputValue: 'allow'}]},
                                    {items:[{xtype:'radio', fieldLabel:'',hideLabel:true,name:'unknown-clients',boxLabel:'Deny',inputValue: 'deny'}]},
                                    {items:[{xtype:'radio', fieldLabel:'',hideLabel:true,name:'unknown-clients',boxLabel:'Ignore',inputValue: 'ignore'}]},
                                    {items:[{xtype:'radio', fieldLabel:'',hideLabel:true,name:'unknown-clients',boxLabel:'Default',checked:true,inputValue: ''}]}
                                ]
                            },
                            //Can clients update their own records?
                            {
                                layout:'table',
                                layoutConfig: {columns: 4},
                                defaults:{layout:'form'},
                                items: [
                                    {items:[{xtype:'radio', fieldLabel:'Can clients update their own records?',name:'client-updates',boxLabel:'Allow',inputValue: 'allow'}]},
                                    {items:[{xtype:'radio', fieldLabel:'',name:'client-updates',hideLabel:true,boxLabel:'Deny',inputValue: 'deny'}]},
                                    {items:[{xtype:'radio', fieldLabel:'',name:'client-updates',hideLabel:true,boxLabel:'Ignore',inputValue: 'ignore'}]},
                                    {items:[{xtype:'radio', fieldLabel:'',name:'client-updates',hideLabel:true,boxLabel:'Default',checked:true,inputValue: ''}]}
                                ]
                            }




                        ]
                    }

                ],
                buttons:[this.saveBtn,this.delBtn]

            }; // eo config object
            return config;
        }
        ,onRender:function(){

            // call parent
            ETFW_DHCP.Data_Form.superclass.onRender.apply(this, arguments);

            // set wait message target
            this.getForm().waitMsgTarget = this.getEl();

            // loads form after initial layout


        }
        //load remote data to populate fields on display
        ,remoteLoad:function(rec){

            this.load({
                url:this.url,
                waitMsg:'Loading...',
                params:{id:this.serviceId,method:'list_all'},
                success: function ( form, action ) {
                    var result = action.result;
                    var data = result.data;

                    switch(this.parent_grid.type){
                        case 'subnet':
                            this.sharedNetCombo.getStore().loadData(data['shared_networks']);


                            if(!rec){

                                var hosts_info = data['subnet']['hosts_list'];
                                var groups_info = data['subnet']['groups_list'];
                                this.hosts_directly.store.loadData(hosts_info);
                                this.groups_directly.store.loadData(groups_info);

                            }
                            else{

                                //      if(rec){
                                if(rec.data['parent-type']=='shared-network'){

                                    var hosts_info = data['subnet_shared']['hosts_list'][rec.data['parent-uuid']];
                                    var groups_info = data['subnet_shared']['groups_list'][rec.data['parent-uuid']];

                                    this.hosts_directly.store.loadData(hosts_info);
                                    this.groups_directly.store.loadData(groups_info);
                                }
                                else{

                                    var hosts_info = data['subnet']['hosts_list'];
                                    var groups_info = data['subnet']['groups_list'];

                                    this.hosts_directly.store.loadData(hosts_info);
                                    this.groups_directly.store.loadData(groups_info);
                                }

                            }
                            if(rec){
                                this.hosts_directly.setValue(rec.data.hosts);
                                this.groups_directly.setValue(rec.data.groups);
                                if(Ext.isEmpty(rec.data['authoritative'],true)) rec.data['authoritative'] = 0;
                                else rec.data['authoritative'] = 1;

                                this.getForm().loadRecord(rec);

                            }
                            break;
                        case 'shared-network':



                            var hosts_info = data['sharednetwork']['hosts_list'];
                            var groups_info = data['sharednetwork']['groups_list'];
                            var subnets_info = data['sharednetwork']['subnets_list'];
                            this.hosts_directly.store.loadData(hosts_info);
                            this.groups_directly.store.loadData(groups_info);
                            this.subnets_directly.store.loadData(subnets_info);



                            if(rec){
                                this.hosts_directly.setValue(rec.data.hosts);
                                this.groups_directly.setValue(rec.data.groups);
                                this.subnets_directly.setValue(rec.data.subnets);

                                this.getForm().loadRecord(rec);

                            }

                            break;
                        case 'host':

                            var hosts_info = data['assigned_to'];

                            this.host_assign.store.loadData(hosts_info);
                            if(rec){
                                if(rec.data['parent-type']){
                                    this.host_assign_combo.setValue(rec.data['parent-type']);
                                    this.host_assign_combo.fireEvent("select",this.host_assign_combo);

                                    this.host_assign.setValue(rec.data['assigned_data']);
                                }




                                this.getForm().loadRecord(rec);

                            }

                            break;
                        case 'group':


                            var groups_info = data['assigned_to'];
                            this.group_assign.store.loadData(groups_info);

                            if((typeof rec)=='object'){

                                var hosts_info = data['group_hosts'][this.uuid];



                                this.hosts_directly.store.loadData(hosts_info);

                                if(rec.data['parent-type']){
                                    this.group_assign_combo.setValue(rec.data['parent-type']);
                                    this.group_assign_combo.fireEvent("select",this.group_assign_combo);

                                    this.group_assign.setValue(rec.data['assigned_data']);
                                }

                                this.hosts_directly.setValue(rec.data['hosts']);




                                this.getForm().loadRecord(rec);

                            }else{
                                var hosts_info = data['group_hosts'][''];
                                if(hosts_info) this.hosts_directly.store.loadData(hosts_info);
                            }


                            break;

                        default:break;
                    }

                    //      }
                    //this.hosts_directly.store.loadData(data['hosts_shared']hosts_direct[rec.data['parent-uuid']]);







                    // this.hosts_directly.store.loadData(hosts_direct[rec.data['parent-uuid']]);
                    // this.groups_directly.store.loadData(groups_direct[rec.data['parent-uuid']]);


                    //

                }
                ,scope:this
                //,success:function(result,request){


                //}
            });

        }

        ,reset:function(){
            this.on('afterlayout', function(){this.remoteLoad()}, this, {single:true});
        }
        ,loadSubnetRecord:function(uuid,rec){
            this.uuid= uuid;
            this.saveBtn.setText('Save');
            this.saveBtn.setHandler(this.onSaveSubnet,this);
            this.delBtn.show();

            this.loadDefaultData(rec);
            this.loadRangeData(rec.data.range);

            this.sharedNetCombo.getStore().on('load',function(){

                this.sharedNetCombo.setValue(rec.data['parent-uuid']);

            },this);

            this.on('afterlayout', function(){this.remoteLoad(rec)}, this, {single:true});


        }
        ,loadSharedRecord:function(uuid,rec){
            this.uuid= uuid;
            this.saveBtn.setText('Save');
            this.saveBtn.setHandler(this.onSaveShared,this);
            this.delBtn.show();

            this.loadDefaultData(rec);

            this.on('afterlayout', function(){this.remoteLoad(rec)}, this, {single:true});


        }
        ,loadHostRecord:function(uuid,rec){
            this.uuid = uuid;

            this.saveBtn.setText('Save');
            this.saveBtn.setHandler(this.onSaveHost,this);
            this.delBtn.show();


            this.loadDefaultData(rec);

            var hardware = rec.data['hardware'];
            if(hardware){
                var hardware_data = hardware.split(' ');
                rec.data['hardware_src'] = hardware_data[0];
                rec.data['hardware'] = hardware_data[1];
            }


            this.on('afterlayout', function(){this.remoteLoad(rec)}, this, {single:true});


        }
        ,loadGroupRecord:function(uuid,rec){
            this.uuid = uuid;

            this.saveBtn.setText('Save');
            this.saveBtn.setHandler(this.onSaveGroup,this);
            this.delBtn.show();

            this.loadDefaultData(rec);
            this.on('afterlayout', function(){this.remoteLoad(rec)}, this, {single:true});


        }
        ,loadPoolRecord:function(uuid,rec){
            this.uuid = uuid;

            this.saveBtn.setText('Save');
            this.saveBtn.setHandler(this.onSavePool,this);
            this.delBtn.show();

            this.loadDefaultData(rec);
            this.loadRangeData(rec.data.range);

            if(rec.data['failover']){
                rec.data['failover'] = rec.data['failover'].replace("peer ","");
                rec.data['failover-src'] = 1;
            }

            this.on('afterlayout', function(){this.getForm().loadRecord(rec)}, this, {single:true});

        }
        ,loadDefaultData:function(rec){


            if(rec.data['filename']) rec.data['filename-src'] = 1;
            if(rec.data['next-server']) rec.data['next-server-src'] = 1;
            if(rec.data['dynamic-bootp-lease-length']) rec.data['dynamic-bootp-lease-length-src'] = 1;
            if(rec.data['ddns-rev-domainname']) rec.data['ddns-rev-domainname-src'] = 1;
            if(rec.data['default-lease-time']) rec.data['default-lease-time-src'] = 1;
            if(rec.data['max-lease-time']) rec.data['max-lease-time-src'] = 1;
            if(rec.data['server-name']) rec.data['server-name-src'] = 1;
            if(rec.data['dynamic-bootp-lease-cutoff']) rec.data['dynamic-bootp-lease-cutoff-src'] = 1;
            if(rec.data['ddns-domainname']) rec.data['ddns-domainname-src'] = 1;
            if(rec.data['ddns-hostname']) rec.data['ddns-hostname-src'] = 1;

        }
        ,loadRangeData:function(range_data){

            var total_range = range_data.length;

            //dynamic-bootp
            var range_store = new  Object();
            var range_array = [];
            for(var i=0,len = total_range;i<len;i++){

                var range_record = range_data[i];
                var bootp = false;
                if(!(range_record.indexOf("dynamic-bootp ")==-1)){
                    range_record = range_record.replace("dynamic-bootp ","");
                    bootp = true;
                }

                var from_to = range_record.split(' ');
                //rules[i] = {index:items[i].data.index};
                //rec.data['range'+i+'_from'] = 'da';
                range_array.push({"from_range":from_to[0],"to_range":from_to[1],"bootp":bootp});
            }
            range_store["range"] = range_array;
            range_store["success"] =true;

            this.address_ranges_grid.getStore().loadData(range_store);

        }
        ,getDataSubmit:function(){
            var form_values = this.getForm().getValues();
            var send_data = new Object();


            if(form_values['lastcomment'])
                send_data['lastcomment'] = form_values['lastcomment'];

            if(form_values['name'])
                send_data['name'] = form_values['name'];

            if(form_values['use-host-decl-names'])
                send_data['use-host-decl-names'] = form_values['use-host-decl-names'];

            if(form_values['fixed-address'])
                send_data['fixed-address'] = form_values['fixed-address'];


            if(form_values['host'])
                send_data['host'] = form_values['host'];

            if(this.hardwareCombo && form_values['hardware']){
                var hardware = this.hardwareCombo.getValue();
                if(hardware) send_data['hardware'] = hardware+' '+form_values['hardware'];
            }

            if(form_values['address'])
                send_data['address'] = form_values['address'];

            if(form_values['netmask'])
                send_data['netmask'] = form_values['netmask'];

            if(form_values['failover'] && form_values['failover-src']==1)
                send_data['failover'] = 'peer '+form_values['failover'];


            //gather allow/deny data.....
            var allow_array = [];
            var deny_array = [];
            var ignore_array = [];
            if(form_values['allow'] && form_values['deny']){
                allow_array = form_values['allow'].split('\n');
                deny_array = form_values['deny'].split('\n');
            }

            var pos_allow_client_updates = allow_array.indexOf('client-updates');
            var pos_allow_unknown_clients = allow_array.indexOf('unknown-clients');

            var pos_deny_client_updates = deny_array.indexOf('client-updates');
            var pos_deny_unknown_clients = deny_array.indexOf('unknown-clients');

            switch(form_values['client-updates']){
                case 'allow':
                    if(pos_allow_client_updates==-1) allow_array.push('client-updates');
                    if(pos_deny_client_updates!=-1) deny_array.splice(pos_deny_client_updates,1);
                    break;
                case 'deny':
                    if(pos_allow_client_updates!=-1) allow_array.splice(pos_allow_client_updates,1);
                    if(pos_deny_client_updates==-1) deny_array.push('client-updates');
                    break;
                case 'ignore': ignore_array.push('client-updates');
                default:

                    if(pos_allow_client_updates!=-1) allow_array.splice(pos_allow_client_updates,1);
                    if(pos_deny_client_updates!=-1) deny_array.splice(pos_deny_client_updates,1);
            }

            switch(form_values['unknown-clients']){
                case 'allow':
                    if(pos_allow_unknown_clients==-1) allow_array.push('unknown-clients');
                    if(pos_deny_unknown_clients!=-1) deny_array.splice(pos_deny_unknown_clients,1);
                    break;
                case 'deny':
                    if(pos_allow_unknown_clients!=-1) allow_array.splice(pos_allow_unknown_clients,1);
                    if(pos_deny_unknown_clients==-1) deny_array.push('unknown-clients');
                    break;
                case 'ignore': ignore_array.push('unknown-clients');
                default:
                    if(pos_allow_unknown_clients!=-1) allow_array.splice(pos_allow_unknown_clients,1);
                    if(pos_deny_unknown_clients!=-1) deny_array.splice(pos_deny_unknown_clients,1);

            }

            if(allow_array.length>0) send_data['allow'] = allow_array;
            if(deny_array.length>0) send_data['deny'] = deny_array;
            if(ignore_array.length>0) send_data['ignore'] = ignore_array;


            if(form_values['filename-src']==1) send_data['filename'] = form_values['filename'];
            if(form_values['next-server-src']==1) send_data['next-server'] = form_values['next-server'];
            if(form_values['dynamic-bootp-lease-length-src']==1) send_data['dynamic-bootp-lease-length'] = form_values['dynamic-bootp-lease-length'];
            if(form_values['ddns-rev-domainname-src']==1) send_data['ddns-rev-domainname'] = form_values['ddns-rev-domainname'];
            if(form_values['default-lease-time-src']==1) send_data['default-lease-time'] = form_values['default-lease-time'];
            if(form_values['max-lease-time-src']==1) send_data['max-lease-time'] = form_values['max-lease-time'];
            if(form_values['server-name-src']==1) send_data['server-name'] = form_values['server-name'];
            if(form_values['dynamic-bootp-lease-cutoff-src']==1) send_data['dynamic-bootp-lease-cutoff'] = form_values['dynamic-bootp-lease-cutoff'];
            if(form_values['ddns-domainname-src']==1) send_data['ddns-domainname'] = form_values['ddns-domainname'];
            if(form_values['ddns-hostname-src']==1) send_data['ddns-hostname'] = form_values['ddns-hostname'];

            if(form_values['authoritative'] && form_values['authoritative']==1) send_data['authoritative'] = '';

            if(form_values["ddns-updates"])
                send_data["ddns-updates"] = form_values["ddns-updates"];








            return send_data;

        }
        ,onSavePool:function() {
            var send_data = this.getDataSubmit();

            var ds = this.address_ranges_grid.getStore();
            var totalRec = ds.getCount();
            var recs = [];

            Ext.each(ds.getRange(0,totalRec),function(e){
                if(e.data.bootp===true) recs.push('dynamic-bootp '+e.data.from_range+' '+e.data.to_range);
                else recs.push(e.data.from_range+' '+e.data.to_range);
            });
            send_data['range'] = recs;

            send_data['uuid'] = this.uuid;

            var conn = new Ext.data.Connection({
                listeners:{
                    // wait message.....
                    beforerequest:function(){
                        Ext.MessageBox.show({
                            title: 'Please wait',
                            msg: 'Updating pool info...',
                            width:300,
                            wait:true,
                            modal: false
                        });
                    },// on request complete hide message
                    requestcomplete:function(){Ext.MessageBox.hide();}
                }
            });// end conn


            conn.request({
                url: this.url,
                params:{id:this.serviceId,method:'set_pool',params:Ext.encode(send_data)},
                failure: function(resp,opt){

                    if(!resp.responseText){
                        Ext.ux.Logger.error(resp.statusText);
                        return;
                    }

                    var response = Ext.util.JSON.decode(resp.responseText);
                    Ext.MessageBox.alert('Error Message', response['info']);
                    Ext.ux.Logger.error(response['error']);

                },
                // everything ok...
                success: function(resp,opt){
                    this.ownerCt.close();
                    var msg = 'Pool edited successfully';
                    Ext.ux.Logger.info(msg);
                    View.notify({html:msg});
                    this.parent_grid.reload();
                },scope:this
            });// END Ajax request



        } // eo function
        ,onSaveSubnet:function() {
            var send_data = this.getDataSubmit();


            var ds = this.address_ranges_grid.getStore();
            var totalRec = ds.getCount();
            var recs = [];

            Ext.each(ds.getRange(0,totalRec),function(e){
                if(e.data.bootp===true) recs.push('dynamic-bootp '+e.data.from_range+' '+e.data.to_range);
                else recs.push(e.data.from_range+' '+e.data.to_range);
            });
            send_data['range'] = recs;

            var hosts_values = this.hosts_directly.getValue();
            var hosts_values_array = hosts_values.split(',');
            var hosts = [];

            for(var j=0,jlen=hosts_values_array.length;j<jlen;j++){

                if(hosts_values_array[j])
                    hosts.push({"uuid":hosts_values_array[j]});


            }

            var groups_values = this.groups_directly.getValue();
            var groups_values_array = groups_values.split(',');
            var groups = [];
            //
            //
            for(var j=0,jlen=groups_values_array.length;j<jlen;j++){

                if(groups_values_array[j])
                    groups.push({"uuid":groups_values_array[j]});


            }

            if(this.sharedNetCombo){
                var sharedNet = this.sharedNetCombo.getValue();
                if(sharedNet){

                    send_data['parent'] = new Object();

                    send_data['parent']['uuid'] = sharedNet;
                }else send_data['parent'] = new Object();
            }

            send_data['uuid'] = this.uuid;
            send_data['hosts'] = hosts;
            send_data['groups'] = groups;
            //



            var conn = new Ext.data.Connection({
                listeners:{
                    // wait message.....
                    beforerequest:function(){
                        Ext.MessageBox.show({
                            title: 'Please wait',
                            msg: 'Updating subnet info...',
                            width:300,
                            wait:true,
                            modal: false
                        });
                    },// on request complete hide message
                    requestcomplete:function(){Ext.MessageBox.hide();}
                }
            });// end conn


            conn.request({
                url: this.url,
                params:{id:this.serviceId,method:'set_subnet',params:Ext.encode(send_data)},
                failure: function(resp,opt){

                    if(!resp.responseText){
                        Ext.ux.Logger.error(resp.statusText);
                        return;
                    }

                    var response = Ext.util.JSON.decode(resp.responseText);
                    Ext.MessageBox.alert('Error Message', response['info']);
                    Ext.ux.Logger.error(response['error']);

                },
                // everything ok...
                success: function(resp,opt){
                    // this.close();
                    (this.ownerCt).ownerCt.close();
                    var msg = 'Subnet edited successfully';
                    Ext.ux.Logger.info(msg);
                    View.notify({html:msg});
                    this.parent_grid.reload();

                    var poolgrid = Ext.getCmp(this.containerId+'-pool_grid');
                    var pool_title = send_data['address']+'/'+send_data['netmask'];
                    var filter = poolgrid.filters.getFilter('parent-uuid');
                    var filter_value = filter.getValue();
                    if(this.uuid==filter_value && filter.active){
                        poolgrid.setTitle('Subnet '+pool_title);
                    }

                },scope:this
            });// END Ajax request



        } // eo function
        ,onSaveShared:function() {
            var send_data = this.getDataSubmit();

            var hosts_values = this.hosts_directly.getValue();
            var hosts_values_array = hosts_values.split(',');
            var hosts = [];

            for(var j=0,jlen=hosts_values_array.length;j<jlen;j++){

                if(hosts_values_array[j])
                    hosts.push({"uuid":hosts_values_array[j]});


            }

            var groups_values = this.groups_directly.getValue();
            var groups_values_array = groups_values.split(',');
            var groups = [];
            //
            //
            for(var j=0,jlen=groups_values_array.length;j<jlen;j++){

                if(groups_values_array[j])
                    groups.push({"uuid":groups_values_array[j]});


            }


            var subnets_values = this.subnets_directly.getValue();
            var subnets_values_array = subnets_values.split(',');
            var subnets = [];

            for(var j=0,jlen=subnets_values_array.length;j<jlen;j++){
                if(subnets_values_array[j])
                    subnets.push({"uuid":subnets_values_array[j]});
            }



            send_data['uuid'] = this.uuid;
            send_data['hosts'] = hosts;
            send_data['groups'] = groups;
            send_data['subnets'] = subnets;




            var conn = new Ext.data.Connection({
                listeners:{
                    // wait message.....
                    beforerequest:function(){
                        Ext.MessageBox.show({
                            title: 'Please wait',
                            msg: 'Updating shared network info...',
                            width:300,
                            wait:true,
                            modal: false
                        });
                    },// on request complete hide message
                    requestcomplete:function(){Ext.MessageBox.hide();}
                }
            });// end conn


            conn.request({
                url: this.url,
                params:{id:this.serviceId,method:'set_sharednetwork',params:Ext.encode(send_data)},
                failure: function(resp,opt){

                    if(!resp.responseText){
                        Ext.ux.Logger.error(resp.statusText);
                        return;
                    }

                    var response = Ext.util.JSON.decode(resp.responseText);
                    Ext.MessageBox.alert('Error Message', response['info']);
                    Ext.ux.Logger.error(response['error']);

                },
                // everything ok...
                success: function(resp,opt){
                    // this.close();
                    (this.ownerCt).ownerCt.close();
                    var msg = 'Shared network edited successfully';
                    Ext.ux.Logger.info(msg);
                    View.notify({html:msg});
                    this.parent_grid.reload();

                    var poolgrid = Ext.getCmp(this.containerId+'-pool_grid');
                    var pool_title = send_data['name'];
                    var filter = poolgrid.filters.getFilter('parent-uuid');
                    var filter_value = filter.getValue();
                    if(this.uuid==filter_value && filter.active){
                        poolgrid.setTitle('Shared network '+pool_title);
                    }

                },scope:this
            });// END Ajax request



        } // eo function
        ,onSaveHost:function(){
            var send_data = this.getDataSubmit();

            send_data['uuid'] = this.uuid;


            if(this.hosts_directly){
                var parent_uuid = this.hosts_directly.getValue();

                if(parent_uuid){

                    send_data['parent'] = new Object();
                    send_data['parent']['uuid'] = parent_uuid;

                }else send_data['parent'] = new Object();
            }




            var conn = new Ext.data.Connection({
                listeners:{
                    // wait message.....
                    beforerequest:function(){
                        Ext.MessageBox.show({
                            title: 'Please wait',
                            msg: 'Updating host info...',
                            width:300,
                            wait:true,
                            modal: false
                        });
                    },// on request complete hide message
                    requestcomplete:function(){Ext.MessageBox.hide();}
                }
            });// end conn


            conn.request({
                url: this.url,
                params:{id:this.serviceId,method:'set_host',params:Ext.encode(send_data)},
                failure: function(resp,opt){

                    if(!resp.responseText){
                        Ext.ux.Logger.error(resp.statusText);
                        return;
                    }

                    var response = Ext.util.JSON.decode(resp.responseText);
                    Ext.MessageBox.alert('Error Message', response['info']);
                    Ext.ux.Logger.error(response['error']);

                },
                // everything ok...
                success: function(resp,opt){
                    (this.ownerCt).ownerCt.close();
                    var msg = 'Host edited successfully';
                    Ext.ux.Logger.info(msg);
                    View.notify({html:msg});
                    this.parent_grid.reload();
                },scope:this
            });// END Ajax request
        }
        ,onCreateSubnet:function(){

            var send_data = this.getDataSubmit();


            var ds = this.address_ranges_grid.getStore();
            var totalRec = ds.getCount();
            var recs = [];

            Ext.each(ds.getRange(0,totalRec),function(e){
                if(e.data.bootp===true) recs.push('dynamic-bootp '+e.data.from_range+' '+e.data.to_range);
                else recs.push(e.data.from_range+' '+e.data.to_range);
            });
            send_data['range'] = recs;

            var hosts_values = this.hosts_directly.getValue();
            var hosts_values_array = hosts_values.split(',');
            var hosts = [];

            for(var j=0,jlen=hosts_values_array.length;j<jlen;j++){

                if(hosts_values_array[j])
                    hosts.push({"uuid":hosts_values_array[j]});


            }

            var groups_values = this.groups_directly.getValue();
            var groups_values_array = groups_values.split(',');
            var groups = [];
            //
            //
            for(var j=0,jlen=groups_values_array.length;j<jlen;j++){

                if(groups_values_array[j])
                    groups.push({"uuid":groups_values_array[j]});


            }

            if(this.sharedNetCombo){
                var sharedNet = this.sharedNetCombo.getValue();
                if(sharedNet){

                    send_data['parent'] = new Object();

                    send_data['parent']['uuid'] = sharedNet;
                }else send_data['parent'] = new Object();
            }

            send_data['hosts'] = hosts;
            send_data['groups'] = groups;


            var conn = new Ext.data.Connection({
                listeners:{
                    // wait message.....
                    beforerequest:function(){
                        Ext.MessageBox.show({
                            title: 'Please wait',
                            msg: 'Creating subnet...',
                            width:300,
                            wait:true,
                            modal: false
                        });
                    },// on request complete hide message
                    requestcomplete:function(){Ext.MessageBox.hide();}
                }
            });// end conn

            conn.request({
                url: this.url,
                params:{id:this.serviceId,method:'add_subnet',params:Ext.encode(send_data)},
                failure: function(resp,opt){

                    if(!resp.responseText){
                        Ext.ux.Logger.error(resp.statusText);
                        return;
                    }

                    var response = Ext.util.JSON.decode(resp.responseText);
                    Ext.MessageBox.alert('Error Message', response['info']);
                    Ext.ux.Logger.error(response['error']);

                },
                // everything ok...
                success: function(resp,opt){
                    this.ownerCt.close();
                    var msg = 'Subnet successfully added';
                    Ext.ux.Logger.info(msg);
                    View.notify({html:msg});
                    this.parent_grid.reload();


                },scope:this
            });// END Ajax request

        }
        ,onCreateShared:function(){

            var send_data = this.getDataSubmit();

            var hosts_values = this.hosts_directly.getValue();
            var hosts_values_array = hosts_values.split(',');
            var hosts = [];

            for(var j=0,jlen=hosts_values_array.length;j<jlen;j++){

                if(hosts_values_array[j])
                    hosts.push({"uuid":hosts_values_array[j]});


            }

            var groups_values = this.groups_directly.getValue();
            var groups_values_array = groups_values.split(',');
            var groups = [];
            //
            //
            for(var j=0,jlen=groups_values_array.length;j<jlen;j++){

                if(groups_values_array[j])
                    groups.push({"uuid":groups_values_array[j]});


            }


            var subnets_values = this.subnets_directly.getValue();
            var subnets_values_array = subnets_values.split(',');
            var subnets = [];

            for(var j=0,jlen=subnets_values_array.length;j<jlen;j++){
                if(subnets_values_array[j])
                    subnets.push({"uuid":subnets_values_array[j]});
            }


            send_data['hosts'] = hosts;
            send_data['groups'] = groups;
            send_data['subnets'] = subnets;

            var conn = new Ext.data.Connection({
                listeners:{
                    // wait message.....
                    beforerequest:function(){
                        Ext.MessageBox.show({
                            title: 'Please wait',
                            msg: 'Creating shared network...',
                            width:300,
                            wait:true,
                            modal: false
                        });
                    },// on request complete hide message
                    requestcomplete:function(){Ext.MessageBox.hide();}
                }
            });// end conn

            conn.request({
                url: this.url,
                params:{id:this.serviceId,method:'add_sharednetwork',params:Ext.encode(send_data)},
                failure: function(resp,opt){

                    if(!resp.responseText){
                        Ext.ux.Logger.error(resp.statusText);
                        return;
                    }

                    var response = Ext.util.JSON.decode(resp.responseText);
                    Ext.MessageBox.alert('Error Message', response['info']);
                    Ext.ux.Logger.error(response['error']);

                },
                // everything ok...
                success: function(resp,opt){
                    this.ownerCt.close();
                    var msg = 'Shared network successfully added';
                    Ext.ux.Logger.info(msg);
                    View.notify({html:msg});
                    this.parent_grid.reload();

                },scope:this
            });// END Ajax request

        }
        ,onCreatePool:function(){

            var send_data = this.getDataSubmit();

            var ds = this.address_ranges_grid.getStore();
            var totalRec = ds.getCount();
            var recs = [];

            Ext.each(ds.getRange(0,totalRec),function(e){
                if(e.data.bootp===true) recs.push('dynamic-bootp '+e.data.from_range+' '+e.data.to_range);
                else recs.push(e.data.from_range+' '+e.data.to_range);
            });
            send_data['range'] = recs;

            if(this.parent_uuid){

                send_data['parent'] = new Object();
                send_data['parent']['uuid'] = this.parent_uuid;

            }else send_data['parent'] = new Object();


            var conn = new Ext.data.Connection({
                listeners:{
                    // wait message.....
                    beforerequest:function(){
                        Ext.MessageBox.show({
                            title: 'Please wait',
                            msg: 'Creating pool...',
                            width:300,
                            wait:true,
                            modal: false
                        });
                    },// on request complete hide message
                    requestcomplete:function(){Ext.MessageBox.hide();}
                }
            });// end conn

            conn.request({
                url: this.url,
                params:{id:this.serviceId,method:'add_pool',params:Ext.encode(send_data)},
                failure: function(resp,opt){

                    if(!resp.responseText){
                        Ext.ux.Logger.error(resp.statusText);
                        return;
                    }

                    var response = Ext.util.JSON.decode(resp.responseText);
                    Ext.MessageBox.alert('Error Message', response['info']);
                    Ext.ux.Logger.error(response['error']);

                },
                // everything ok...
                success: function(resp,opt){
                    this.ownerCt.close();
                    var msg = 'Pool successfully added';
                    Ext.ux.Logger.info(msg);
                    View.notify({html:msg});
                    this.parent_grid.reload();


                },scope:this
            });// END Ajax request

        }
        ,onCreateHost:function(){

            var send_data = this.getDataSubmit();

            var parent_uuid = this.host_assign.getValue();
            if(parent_uuid){

                send_data['parent'] = new Object();
                send_data['parent']['uuid'] = parent_uuid;

            }else send_data['parent'] = new Object();

            var conn = new Ext.data.Connection({
                listeners:{
                    // wait message.....
                    beforerequest:function(){
                        Ext.MessageBox.show({
                            title: 'Please wait',
                            msg: 'Creating host...',
                            width:300,
                            wait:true,
                            modal: false
                        });
                    },// on request complete hide message
                    requestcomplete:function(){Ext.MessageBox.hide();}
                }
            });// end conn

            conn.request({
                url: this.url,
                params:{id:this.serviceId,method:'add_host',params:Ext.encode(send_data)},
                failure: function(resp,opt){

                    if(!resp.responseText){
                        Ext.ux.Logger.error(resp.statusText);
                        return;
                    }

                    var response = Ext.util.JSON.decode(resp.responseText);
                    Ext.MessageBox.alert('Error Message', response['info']);
                    Ext.ux.Logger.error(response['error']);

                },
                // everything ok...
                success: function(resp,opt){
                    this.ownerCt.close();
                    var msg = 'Host successfully added';
                    Ext.ux.Logger.info(msg);
                    View.notify({html:msg});
                    this.parent_grid.reload();
                },scope:this
            });// END Ajax request

        }
        ,onCreateGroup:function(){

            var send_data = this.getDataSubmit();


            var hosts_values = this.hosts_directly.getValue();
            var hosts_values_array = hosts_values.split(',');
            var hosts = [];
            //
            //
            for(var i=0,len=hosts_values_array.length;i<len;i++){

                if(hosts_values_array[i])
                    hosts.push({"uuid":hosts_values_array[i]});
            }
            send_data['hosts'] = hosts;



            var parent_uuid = this.group_assign.getValue();
            if(parent_uuid){

                send_data['parent'] = new Object();
                send_data['parent']['uuid'] = parent_uuid;

            }else send_data['parent'] = new Object();



            var conn = new Ext.data.Connection({
                listeners:{
                    // wait message.....
                    beforerequest:function(){
                        Ext.MessageBox.show({
                            title: 'Please wait',
                            msg: 'Creating host group...',
                            width:300,
                            wait:true,
                            modal: false
                        });
                    },// on request complete hide message
                    requestcomplete:function(){Ext.MessageBox.hide();}
                }
            });// end conn

            conn.request({
                url: this.url,
                params:{id:this.serviceId,method:'add_group',params:Ext.encode(send_data)},
                failure: function(resp,opt){

                    if(!resp.responseText){
                        Ext.ux.Logger.error(resp.statusText);
                        return;
                    }

                    var response = Ext.util.JSON.decode(resp.responseText);
                    Ext.MessageBox.alert('Error Message', response['info']);
                    Ext.ux.Logger.error(response['error']);

                },
                // everything ok...
                success: function(resp,opt){
                    this.ownerCt.close();
                    var msg = 'Host group successfully added';
                    Ext.ux.Logger.info(msg);
                    View.notify({html:msg});
                    this.parent_grid.reload();


                },scope:this
            });// END Ajax request

        }
        ,onSaveGroup:function(){
            var send_data = this.getDataSubmit();

            send_data['uuid'] = this.uuid;


            var hosts_values = this.hosts_directly.getValue();
            var hosts_values_array = hosts_values.split(',');
            var hosts = [];
            //
            //
            for(var i=0,len=hosts_values_array.length;i<len;i++){

                if(hosts_values_array[i])
                    hosts.push({"uuid":hosts_values_array[i]});
            }
            send_data['hosts'] = hosts;



            var parent_uuid = this.group_assign.getValue();
            if(parent_uuid){

                send_data['parent'] = new Object();
                send_data['parent']['uuid'] = parent_uuid;

            }else send_data['parent'] = new Object();





            var conn = new Ext.data.Connection({
                listeners:{
                    // wait message.....
                    beforerequest:function(){
                        Ext.MessageBox.show({
                            title: 'Please wait',
                            msg: 'Updating host group info...',
                            width:300,
                            wait:true,
                            modal: false
                        });
                    },// on request complete hide message
                    requestcomplete:function(){Ext.MessageBox.hide();}
                }
            });// end conn


            conn.request({
                url: this.url,
                params:{id:this.serviceId,method:'set_group',params:Ext.encode(send_data)},
                failure: function(resp,opt){

                    if(!resp.responseText){
                        Ext.ux.Logger.error(resp.statusText);
                        return;
                    }

                    var response = Ext.util.JSON.decode(resp.responseText);
                    Ext.MessageBox.alert('Error Message', response['info']);
                    Ext.ux.Logger.error(response['error']);

                },
                // everything ok...
                success: function(resp,opt){
                    (this.ownerCt).ownerCt.close();
                    var msg = 'Host group edited successfully';
                    Ext.ux.Logger.info(msg);
                    View.notify({html:msg});
                    this.parent_grid.reload();

                },scope:this
            });// END Ajax request
        }

    }); // eo extend





    /*
     *
     * BBAR
     *
     */

    //edit configfile
    ETFW_DHCP.ConfigEditor_Form = Ext.extend(Ext.form.FormPanel, {

        border:false
        ,frame:true
        ,url:<?php echo json_encode(url_for('etfw/json'))?>

        ,initComponent:function() {

            this.saveBtn = new Ext.Button({text:'Save'
                ,scope:this
                ,handler:this.onSave
            });

            this.reset = new Ext.Button({text:'Undo'
                ,scope:this
                ,handler:this.onReset
            });

            this.txtarea = new Ext.form.TextArea({name:'content'});

            var config = {
                layout:'fit',
                autoScroll:false
                ,items:[
                    {
                        xtype:'fieldset',
                        labelWidth:5,
                        layout:'fit',
                        collapsible:true,
                        title: 'Text Editor',
                        items :[this.txtarea]
                    }],
                buttons:[this.saveBtn,this.reset]
            };

            // apply config
            Ext.apply(this, Ext.apply(this.initialConfig, config));


            this.on('beforerender',function(){
                Ext.getBody().mask('Loading form data...');

            },this);

            this.on('render',function(){
                Ext.getBody().unmask();}
            ,this
            ,{delay:10}
        );

            // call parent
            ETFW_DHCP.ConfigEditor_Form.superclass.initComponent.apply(this, arguments);



        } // eo function initComponent
        ,onRender:function(){
              // call parent
                 ETFW_DHCP.ConfigEditor_Form.superclass.onRender.apply(this, arguments);

             // set wait message target
                this.getForm().waitMsgTarget = this.getEl();

            // loads form after initial layout
               this.on('afterlayout', this.onLoad, this, {single:true});
        }
        ,onLoad:function(){
            this.load({
                url:this.url
                ,waitMsg:'Loading...'
                ,params:{id:this.serviceId,method:'get_configfile_content'}
                ,success:function ( form, action ) {
                    var result = action.result;
                    var data = result.data;
                    this.original_txt = data['content'];

                },scope:this
            });
        }
        ,onReset:function(){
            this.txtarea.setValue(this.original_txt);
        }
        ,onSave:function() {
            var send_data = new Object();
            var form_values = this.getForm().getValues();

            send_data['content'] = form_values['content'];


            var conn = new Ext.data.Connection({
                listeners:{
                    // wait message.....
                    beforerequest:function(){
                        Ext.MessageBox.show({
                            title: 'Please wait',
                            msg: 'Updating dhcpd.conf file...',
                            width:300,
                            wait:true,
                            modal: false
                        });
                    },// on request complete hide message
                    requestcomplete:function(){Ext.MessageBox.hide();}
                }
            });// end conn


            conn.request({
                url: this.url,
                params:{id:this.serviceId,method:'save_configfile_content',params:Ext.encode(send_data)},
                failure: function(resp,opt){

                    if(!resp.responseText){
                        Ext.ux.Logger.error(resp.statusText);
                        return;
                    }

                    var response = Ext.util.JSON.decode(resp.responseText);
                    Ext.MessageBox.alert('Error Message', response['info']);
                    Ext.ux.Logger.error(response['error']);

                },
                // everything ok...
                success: function(resp,opt){
                    (this.ownerCt).close();
                    var msg = 'dhcpd.conf file edited successfully';
                    Ext.ux.Logger.info(msg);
                    View.notify({html:msg});

                },scope:this
            });// END Ajax request


        }


    }); // eo extend


    // create pre-configured grid class
    ETFW_DHCP.ListLeases_Grid = Ext.extend(Ext.grid.GridPanel, {

        initComponent:function() {

            var selectBoxModel= new Ext.grid.CheckboxSelectionModel({keepSelections:true});
            var filters = new Ext.ux.grid.GridFilters({
                // encode and local configuration options defined previously for easier reuse
                //encode: false, // json encode the filter query
                local: true,   // defaults to false (remote filtering)
                filters: [
                    {
                        type: 'boolean',
                        dataIndex: 'expired',
                        active: true
                    },
                    {   type:'string',
                        dataIndex:'parent-type'
                    }
                ]
            });

            this.tbar = [
                {
                    text:'Delete ip address(s)',
                    ref: '../removeBtn',
                    tooltip:'Delete the selected ip address(s) from list',
                    iconCls:'remove',
                    disabled:true,
                    handler: function(){
                        new Grid.util.DeleteItem({panel: this.id});
                    },scope:this
                }];




            // configure the grid
            Ext.apply(this, {
                store:new Ext.data.GroupingStore({
                    reader:new Ext.data.JsonReader({

                        totalProperty:'total'
                        ,root:'data'
                        ,fields:[
                            'index','ipaddr','ethernet','client-hostname','sdate','edate','expired'

                        ]
                    })
                    ,proxy:new Ext.data.HttpProxy({url:this.url})
                    ,baseParams:this.baseParams
                    ,groupField:'expired'
                    ,sortInfo:{field:'expired', direction:'ASC'}
                    ,listeners:{
                        load:{scope:this, fn:function() {

                                //   this.getSelectionModel().selectFirstRow();
                            }}
                    }
                })
                ,columns:[

                    selectBoxModel,
                    {header: "IP Address", width: 40, sortable: true, dataIndex: 'ipaddr'}
                    ,{header: "Ethernet", width: 40, sortable: true, dataIndex: 'ethernet'}
                    ,{header: "Hostname", width: 20, sortable: true, dataIndex: 'client-hostname'}
                    ,{header: "Start date", width: 20, sortable: true, dataIndex: 'sdate'}
                    ,{header: "End date", width: 20, sortable: true, dataIndex: 'edate'}
                    ,{header: "Expired", width: 20, sortable: true, dataIndex: 'expired',renderer:function(v){if(v) return 'Yes';
                                                                                                              else return 'No';
                                                                                            }}
                    //,this.action
                ]
                ,stateful:false
                ,plugins:[filters
                    //	,plugins:[this.action
                   // ,this.expander
                ]
                ,sm:selectBoxModel
                ,view: new Ext.grid.GroupingView({
                    forceFit:true
                    ,emptyText: 'Empty!'  //  emptyText Message
                    ,deferEmptyText:false
                    ,groupTextTpl:'{text} ({[values.rs.length]} {[values.rs.length > 1 ? "Items" : "Item"]})'
                })
                ,loadMask:true

                ,height:200

                ,autoScroll:true

            }); // eo apply
            var active_leases = true;
            // add paging toolbar
            this.bbar = [
                         {
                             text: 'Clear Filter Data',
                             handler: function () {
                                 filters.clearFilters();
                             }

                         }
                         ,'-',
                         {
                        text: 'Display only active leases: ' + (active_leases ? 'On' : 'Off'),
                        tooltip: 'Toggle Filter encoding on/off',
                        enableToggle: true,
                        pressed:true,
                        handler: function (button, state) {
                            var filter = filters.getFilter('expired');
                            filter.setValue (false);

                            var active_leases = (filter.active===true) ? false : true;
                            var text = 'Display only active leases: ' + (active_leases ? 'On' : 'Off');

                            filter.setActive(active_leases);

                            button.setText(text);

                        }
                        },
                        '->',
                        {text: 'Refresh',
                            xtype: 'button',
                            tooltip: 'refresh',
                            iconCls: 'x-tbar-loading',
                            scope:this,
                            handler: function(button,event)
                            {
                                button.addClass('x-item-disabled');
                                this.store.reload({
                                    callback:function(){button.removeClass('x-item-disabled');}});
                            }
                        }

                ];



            // call parent
            ETFW_DHCP.ListLeases_Grid.superclass.initComponent.apply(this, arguments);

            this.getSelectionModel().on('selectionchange', function(sm){
                this.removeBtn.setDisabled(sm.getCount() < 1);
            },this);



            /************************************************************
             * handle contextmenu event
             ************************************************************/
            this.addListener("rowcontextmenu", onContextMenu, this);
            function onContextMenu(grid, rowIndex, e) {

                grid.getSelectionModel().selectRow(rowIndex);

                if (!this.menu) {
                    this.menu = new Ext.menu.Menu({
                        // id: 'menus',
                        items: [{
                                text:'Delete ip address from list',
                                tooltip:'Delete this item',
                                iconCls:'remove',
                                handler: function(){
                                            new Grid.util.DeleteItem({panel: this.id});
                                },scope:this
                            }]
                    });
                }
                this.rowctx = rowIndex;
                e.stopEvent();
                this.menu.showAt(e.getXY());
            }


        } // eo function initComponent
        // }}}
        // {{{
        ,onRender:function() {

            // call parent
            ETFW_DHCP.ListLeases_Grid.superclass.onRender.apply(this, arguments);

            // start w/o grouping
            //		this.store.clearGrouping();
            //var store = grid.getStore();
            // store.load.defer(20,store);
            // load the store
            //this.store.load({params:{start:0, limit:10}});
            this.store.load.defer(20,this.store);

        } // eo function onRender
        ,reload:function(){this.store.reload();}
        ,deleteData:function(items){


            var conn = new Ext.data.Connection({
                listeners:{
                    // wait message.....
                    beforerequest:function(){
                        Ext.MessageBox.show({
                            title: 'Please wait',
                            msg: 'Removing ip address(s)...',
                            width:300,
                            wait:true,
                            modal: false
                        });
                    },// on request complete hide message
                    requestcomplete:function(){Ext.MessageBox.hide();}
                }
            });// end conn
            var leases = [];

            for(var i=0,len = items.length;i<len;i++){
                leases[i] = items[i].data.index;

            }

            conn.request({
                url: this.url,
                params:{id:this.serviceId,method:'del_leases',params:Ext.encode({'indexes':leases})},
                failure: function(resp,opt){
                    var response = Ext.util.JSON.decode(resp.responseText);
                    Ext.MessageBox.alert('Error Message', response['info']);
                    Ext.ux.Logger.error(response['error']);

                },
                // everything ok...
                success: function(resp,opt){

                    var msg = 'Ip address(s) removed from list';
                    Ext.ux.Logger.info(msg);
                    View.notify({html:msg});

                    this.reload();

                },scope:this

            });// END Ajax request

        }


    }); // eo extend

    // register component
   Ext.reg('etfw_dhcp_leasesgrid', ETFW_DHCP.ListLeases_Grid);
//






    Ext.ns('ETFW_DHCP.Bbar');
    ETFW_DHCP.Bbar.Main = function(serviceId,containerId) {

        this.serviceId = serviceId;
        this.containerId = containerId;


        ETFW_DHCP.Bbar.Main.superclass.constructor.call(this, {
            items:[
                {
                xtype: 'buttongroup',
                title:'Edit DHCP client options<br> that apply to all',
                columns: 1,
                height:60,
                defaults: {
                    scale: 'small', width:140
                },
                items: [{
                        //xtype:'splitbutton',
                        text: 'Edit Client Options',
                        tooltip:'Edit DHCP client options that apply to all<br>subnets, shared networks, hosts and groups'
                        ,handler:function(){
                            Ext.getBody().mask('Preparing data...');
                            var win = new Ext.Window({
                                    title:'Client Options'
                                    ,layout:'fit'
                                    ,width:800
                                    ,modal:true
                                    ,height:450
                                    ,closable:true
                                    ,border:false
                                    ,items:[this.buildClientOptions()]
                                });

                            (function(){
                                    win.show();
                            }).defer(100);

                        },scope:this
                    }]},
            {
                xtype: 'buttongroup',
                title:'Edit TSIG-keys <br>(used for DNS servers updates)',
                columns: 1,
                height:60,
                defaults: {
                    scale: 'small', width:190
                },
                items: [{
                        //xtype:'splitbutton',
                        text: 'Edit TSIG-keys',
                        tooltip:'Edit TSIG-keys (used for authenticating updates to DNS servers)',
                        handler:function(){
                            Ext.getCmp('etfw-dhcp-zones-'+this.containerId+'-panel').show();
                        },scope:this
                    }]},
            {
                xtype: 'buttongroup',
                title:'Edit configfile<br> in texteditor',
                columns: 1,
                height:60,
                defaults: {
                    scale: 'small', width:110
                },
                items: [{
                        //xtype:'splitbutton',
                        text: 'Configfile',
                        tooltip:'Edit configfile in texteditor (caution!)'
                        ,handler:function(){
                            var win = new Ext.Window({
                                    title:'Editor dhcpd.conf'
                                    ,layout:'fit'
                                    ,width:800
                                    ,modal:true
                                    ,height:450
                                    ,closable:true
                                    ,border:false
                                    ,items:[this.buildEditor()]
                                });
                                win.show();
                        },scope:this
                    }]},
            {
                xtype: 'buttongroup',
                title:'Set the network interfaces<br> to listen on startup',
                columns: 1,
                height:60,
                defaults: {
                    scale: 'small', width:180
                },
                items: [{
                        //xtype:'splitbutton',
                        text: 'Edit Network Interface',
                        tooltip:'Set the network interfaces that the DHCP server listens on when started'
                        ,handler:function(){
                            var win = new Ext.Window({
                                    title:'Network interface listener'
                                    ,layout:'fit'
                                    ,width:600
                                    ,modal:true
                                    ,height:250
                                    ,closable:true
                                    ,border:false
                                    ,items:[this.buildNetworkInterface()]
                                });
                                win.show();
                        },scope:this
                    }]},
            {
                xtype: 'buttongroup',
                title:'List leases currently <br>issued by this DHCP server',
                tooltpi:'daa',
                columns: 1,
                height:60,
                defaults: {
                    scale: 'small', width:160
                },
                items: [{
                        //xtype:'splitbutton',
                        text: 'List active leases',
                        tooltip:'List leases currently issued by this DHCP server for dynamically assigned IP addresses'
                        ,handler:function(){
                            var win = new Ext.Window({
                                    title:'List leases'
                                    ,layout:'fit'
                                    ,width:600
                                    ,modal:true
                                    ,height:250
                                    ,closable:true
                                    ,border:false
                                    ,items:[this.buildListLeases()]
                                });
                                win.show();
                        },scope:this

                    }]},{
                xtype: 'buttongroup',
                title:'Start the DHCP server,<br> using the current configuration',
                columns: 1,
                height:60,
                defaults: {
                    scale: 'small', width:170
                },
                items: [{
                        text: 'Start server',
                        tooltip:'Click this button to start the DHCP server on your system, using the current configuration'
                    }]}]
        });


    }//eof

    // define public methods
    Ext.extend(ETFW_DHCP.Bbar.Main, Ext.Toolbar,{
        buildEditor:function(){

            return new ETFW_DHCP.ConfigEditor_Form({serviceId:this.serviceId});
        },
        buildNetworkInterface:function(){

            return new ETFW_DHCP.NetworkInterface_Form({serviceId:this.serviceId});
        },
        buildClientOptions:function(){

            return new ETFW_DHCP.ClientOptions_Form({title:'Client Options for all networks, hosts and groups',all:true,serviceId:this.serviceId});
        },
        buildListLeases:function(){

            var params = {'all':1};
            var item = {
                        url:<?php echo json_encode(url_for('etfw/json'))?>,
                        serviceId:this.serviceId,
                        baseParams:{id:this.serviceId,method:'list_leases',params:Ext.encode(params)},xtype:'etfw_dhcp_leasesgrid'
                        };
            return item;
        }

    });

    var containerId = <?php echo json_encode($containerId) ?>;
    var etfw_dhcp_networks = new ETFW_DHCP.Networks.Main(<?php echo $etva_service->getId(); ?>,'etfw-dhcp-networks-'+containerId);
    var etfw_dhcp_hosts = new ETFW_DHCP.Hosts.Main(<?php echo $etva_service->getId(); ?>,'etfw-dhcp-hosts-'+containerId);
    var etfw_dhcp_zones = new ETFW_DHCP.Zones.Main(<?php echo $etva_service->getId(); ?>,'etfw-dhcp-zones-'+containerId);
    var etfw_dhcp_bbar = new ETFW_DHCP.Bbar.Main(<?php echo $etva_service->getId(); ?>,containerId);


    Ext.getCmp('etfw-dhcp-panel-'+containerId).add({xtype:'panel',
                         layout:'border',
                         items:[{region:'south',
                                 title:'General options',
                                 collapsible:true,
                                 items:[etfw_dhcp_bbar]},
                                {region:'center',
                                 xtype:'tabpanel',
                                 //deferredRender:true,
                                 activeTab:0,
                                 items:[etfw_dhcp_networks,etfw_dhcp_hosts,etfw_dhcp_zones]}
                         ]});
    Ext.getCmp('etfw-dhcp-panel-'+containerId).doLayout();


</script>
