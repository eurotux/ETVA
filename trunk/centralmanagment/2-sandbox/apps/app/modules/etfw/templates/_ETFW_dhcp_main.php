<?php
$cliOptionsSchema = $clientOptionsForm->getWidgetSchema();
$cliOptionsLbls = $cliOptionsSchema->getLabels();

$subnetSchema = $subnetForm->getWidgetSchema();
$subnetLbls = $subnetSchema->getLabels();

$sharedSchema = $sharednetworkForm->getWidgetSchema();
$sharedLbls = $sharedSchema->getLabels();

$poolSchema = $poolForm->getWidgetSchema();
$poolLbls = $poolSchema->getLabels();

$hostSchema = $hostForm->getWidgetSchema();
$hostLbls = $hostSchema->getLabels();

$groupSchema = $groupForm->getWidgetSchema();
$groupLbls = $groupSchema->getLabels();
?>
<script>
Ext.ns('ETFW.DHCP');


// dhcp server interfaces listener
ETFW.DHCP.NetworkInterface_Form = Ext.extend(Ext.form.FormPanel, {

    border:false
    ,frame:true
    ,url:<?php echo json_encode(url_for('etfw/json'))?>

    ,initComponent:function() {

        this.saveBtn = new Ext.Button({text:'Save'
            ,scope:this
            ,handler:this.onSave
        });

        // uses network_dispatcher_id from component method ETFW_dhcp_networkinterface
        this.ifacesStore = new Ext.data.JsonStore({
            url: this.url,
            baseParams:{id:this.network_dispatcher,method:'boot_interfaces',mode:'boot_real_interfaces'},
            id: 'fullname',
            remoteSort: false,
            totalProperty: 'total',
            root: 'data',
            fields: ['fullname']
        });
        this.ifacesStore.setDefaultSort('fullname', 'ASC');

        this.ifacesStore.on('load',function(){
            this.onLoad();
        },this);

        //bind mask to store on load...
        new Ext.LoadMask(Ext.getBody(), {msg:"Please wait...",store:this.ifacesStore});


        this.ifaces = new Ext.ux.Multiselect({
            name:'ifaces',
            style:'padding-top:5px',
            fieldLabel:"Listen on interfaces",
            valueField:"fullname",
            displayField:"fullname",
            height:80,
            allowBlank:true,
            store:this.ifacesStore
        });


        var config = {
            layout:'form'
            ,autoScroll:false
            ,items:[

                {
                    xtype:'fieldset',
                   // layout:'fit',
                    collapsible:true,
                    title: 'Network Interface',
                    items :[{html:'The DHCP server can only assign IP addresses on networks connected to one of the interfaces selected below.\n\
                        The network interface for all defined subnets must be included. If none are selected, the DHCP server will attempt to find one automatically.'},
                        this.ifaces]
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
        ETFW.DHCP.NetworkInterface_Form.superclass.initComponent.apply(this, arguments);



    } // eo function initComponent
    ,onRender:function(){
          // call parent
             ETFW.DHCP.NetworkInterface_Form.superclass.onRender.apply(this, arguments);

         // set wait message target
            this.getForm().waitMsgTarget = this.getEl();

        // loads store after initial layout
           this.on('afterlayout', this.loadStore, this, {single:true});
    }
    //loads interfaces store
    ,loadStore:function(){
        this.ifacesStore.load();
    }
    // for setting current selected values (interfaces)
    ,onLoad:function(){
        this.load({
            url:this.url
            ,waitMsg:'Loading...'
            ,params:{id:this.service_id,method:'get_interface'}
            ,scope:this
        });
    }
    ,onSave:function() {
        var send_data = new Object();
        var form_values = this.getForm().getValues();

        var ifaces_values = form_values['ifaces'];
        var ifaces_values_array = ifaces_values.split(',');
        var ifaces = [];

        for(var i=0,len=ifaces_values_array.length;i<len;i++){
            if(ifaces_values_array[i])
                ifaces.push(ifaces_values_array[i]);
        }

        send_data['ifaces'] = ifaces;


        var conn = new Ext.data.Connection({
            listeners:{
                // wait message.....
                beforerequest:function(){
                    Ext.MessageBox.show({
                        title: 'Please wait',
                        msg: 'Updating network interfaces...',
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
            params:{id:this.service_id,method:'set_interface',params:Ext.encode(send_data)},
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
                var msg = 'DHCP server network interfaces listener edited successfully';
                Ext.ux.Logger.info(msg);
                View.notify({html:msg});

            },scope:this
        });// END Ajax request


    }


}); // eo extend


/*
 *
 * CLIENT OPTIONS FORM TEMPLATE
 *
 */
ETFW.DHCP.ClientOptions_Form = Ext.extend(Ext.form.FormPanel, {

    // defaults - can be changed from outside
    border:false
    ,frame:true
    ,bodyStyle:'padding-top:10px'
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
                            items: [{width:410,items:[{
                                            layout:'table',
                                            bodyStyle:'padding-top:3px',
                                            layoutConfig: {columns: 3},
                                            defaults:{layout:'form',bodyStyle:'padding-left:5px;',height:40},
                                            items: [
                                                {bodyStyle:'padding:0px;',items:[{xtype:'radio', fieldLabel:'<?php echo $cliOptionsLbls['use-host-decl-names']?>',labelSeparator:'?',name:'use-host-decl-names',boxLabel:'Yes',inputValue: 'on'}]},
                                                {items:[{xtype:'radio', fieldLabel:'',hideLabel:true,name:'use-host-decl-names',boxLabel:'No',inputValue: 'off'}]},
                                                {items:[{xtype:'radio', fieldLabel:'',hideLabel:true,name:'use-host-decl-names',checked:true,boxLabel:'Default',inputValue: ''}]}
                                            ]
                                        }]},
                                // 2nd col
                                {bodyStyle:'padding-left:30px;',
                                    items:[
                                        {
                                            layout:'table',
                                            bodyStyle:'padding-top:3px',
                                            layoutConfig: {columns: 4},
                                            defaults:{layout:'form',bodyStyle:'padding-left:5px;'},
                                            items: [
                                                {bodyStyle:'padding:0px;',items:[{xtype:'radio', fieldLabel:'<?php echo $cliOptionsLbls['default-lease-time']?>',name:'default-lease-time-src',boxLabel:'Default',checked:true,inputValue: 0}]},
                                                {items:[{xtype:'radio', hideLabel:true,name:'default-lease-time-src',boxLabel:'',inputValue: 1}]},
                                                {items:[{xtype:'numberfield',name:'default-lease-time',hideLabel:true}]},
                                                {items:[{xtype:'displayfield',value:'secs',fieldLabel:'',hideLabel:true}]}
                                            ]
                                        }
                                    ]}// end 2nd col
                            ]
                        }
                        ,
                         ETFW.DHCP.Data_Form_DefaultItems,
                        {
                            layout:'table',
                            layoutConfig: {columns: 4},
                            defaults:{layout:'form',height:40,bodyStyle:'padding-left:5px;'},
                            items: [
                                {bodyStyle:'padding:0px;',items:[{xtype:'radio', fieldLabel:'<?php echo $cliOptionsLbls['ddns-update-style']?>',name:'ddns-update-style',boxLabel:'Ad-hoc',inputValue: 'ad-hoc'}]},
                                {items:[{xtype:'radio', fieldLabel:'',hideLabel:true,name:'ddns-update-style',boxLabel:'Interim',inputValue: 'interim'}]},
                                {items:[{xtype:'radio', fieldLabel:'',hideLabel:true,name:'ddns-update-style',boxLabel:'None',inputValue: 'none'}]},
                                {items:[{xtype:'radio', fieldLabel:'',hideLabel:true,name:'ddns-update-style',boxLabel:'Default',checked:true,inputValue: ''}]}
                            ]
                        },
                        //Allow unknown clients?
                        {
                            layout:'table',
                            layoutConfig: {columns: 4},
                            defaults:{layout:'form',bodyStyle:'padding-left:5px;'},
                            items: [
                                {bodyStyle:'padding:0px;',items:[{xtype:'radio', labelSeparator:'?',fieldLabel:'<?php echo $cliOptionsLbls['unknown-clients']?>',name:'unknown-clients',boxLabel:'Allow',inputValue: 'allow'}]},
                                {items:[{xtype:'radio', fieldLabel:'',hideLabel:true,name:'unknown-clients',boxLabel:'Deny',inputValue: 'deny'}]},
                                {items:[{xtype:'radio', fieldLabel:'',hideLabel:true,name:'unknown-clients',boxLabel:'Ignore',inputValue: 'ignore'}]},
                                {items:[{xtype:'radio', fieldLabel:'',hideLabel:true,name:'unknown-clients',boxLabel:'Default',checked:true,inputValue: ''}]}
                            ]
                        },
                        //Can clients update their own records?
                        {
                            layout:'table',
                            layoutConfig: {columns: 4},
                            defaults:{layout:'form',height:40,bodyStyle:'padding-left:5px;'},
                            items: [
                                {bodyStyle:'padding:0px;',items:[{xtype:'radio', labelSeparator:'?',fieldLabel:'<?php echo $cliOptionsLbls['client-updates']?>',name:'client-updates',boxLabel:'Allow',inputValue: 'allow'}]},
                                {items:[{xtype:'radio', fieldLabel:'',name:'client-updates',hideLabel:true,boxLabel:'Deny',inputValue: 'deny'}]},
                                {items:[{xtype:'radio', fieldLabel:'',name:'client-updates',hideLabel:true,boxLabel:'Ignore',inputValue: 'ignore'}]},
                                {items:[{xtype:'radio', fieldLabel:'',name:'client-updates',hideLabel:true,boxLabel:'Default',checked:true,inputValue: ''}]}
                            ]
                        },
                        //Server is authoritative for this subnet?
                        {
                            layout:'table',
                            bodyStyle:'padding-top:3px;',
                            layoutConfig: {columns: 2},
                            defaults:{layout:'form',height:40,bodyStyle:'padding-left:5px;'},
                            items: [
                                {bodyStyle:'padding:0px;',items:[{xtype:'radio', labelSeparator:'?',fieldLabel:'<?php echo $cliOptionsLbls['authoritative']?>',name:'authoritative',boxLabel:'Yes',inputValue: 1}]},
                                {items:[{xtype:'radio', fieldLabel:'',hideLabel:true,name:'authoritative',boxLabel:'Default(No)',checked:true,inputValue: 0}]}
                            ]
                        }
                 ];
        }



        var config = {
            monitorValid:true
            ,autoScroll:true
            ,border:true
            // ,buttonAlign:'right'
            ,items:[
                {
                    xtype:'fieldset',
                    labelWidth:130,
                    border:false,
                    title: 'Client options',
                    items :[
                        {
                            layout:'table',
                            layoutConfig: {columns: 2},
                            defaults:{layout:'form'},
                            items: [{width:410,items:[
                                        {xtype:'hidden',name:'uuid'},
                                        this.buildDefaultItem('<?php echo $cliOptionsLbls['option_host-name']?>','option_host-name'),
                                        this.buildDefaultItem('<?php echo $cliOptionsLbls['option_subnet-mask']?>','option_subnet-mask',{'vtype':'ip_addr'}),
                                        this.buildDefaultItem('<?php echo $cliOptionsLbls['option_domain-name']?>','option_domain-name'),
                                        this.buildDefaultItem('<?php echo $cliOptionsLbls['option_time-servers']?>','option_time-servers'),
                                        this.buildDefaultItem('<?php echo $cliOptionsLbls['option_swap-server']?>','option_swap-server',{'vtype':'ip_addr'}),
                                        this.buildDefaultItem('<?php echo $cliOptionsLbls['option_nis-domain']?>','option_nis-domain'),
                                        this.buildDefaultItem('<?php echo $cliOptionsLbls['option_font-servers']?>','option_font-servers')
                                    ]},
                                // 2ns col
                                {bodyStyle:'padding-left:30px;',
                                    items:[
                                        this.buildDefaultItem('<?php echo $cliOptionsLbls['option_routers']?>','option_routers'),
                                        this.buildDefaultItem('<?php echo $cliOptionsLbls['option_broadcast-address']?>','option_broadcast-address',{'vtype':'ip_addr'}),
                                        this.buildDefaultItem('<?php echo $cliOptionsLbls['option_domain-name-servers']?>','option_domain-name-servers'),
                                        this.buildDefaultItem('<?php echo $cliOptionsLbls['option_log-servers']?>','option_log-servers'),
                                        this.buildDefaultItem('<?php echo $cliOptionsLbls['option_root-path']?>','option_root-path'),
                                        this.buildDefaultItem('<?php echo $cliOptionsLbls['option_nis-servers']?>','option_nis-servers'),
                                        this.buildDefaultItem('<?php echo $cliOptionsLbls['option_x-display-manager']?>','option_x-display-manager')
                                    ]}// end 2nd col
                            ]
                        },

        {
            bodyStyle:'padding-top:3px;',
            layout:'table',
            layoutConfig: {columns: 4},
            defaults:{layout:'form',bodyStyle:'padding-left:5px;'},
            items: [
                {bodyStyle:'padding:0px;',items:[{xtype:'radio',fieldLabel:'<?php echo $cliOptionsLbls['option_static-routes']?>',name:'option_static-routes-src',boxLabel:'Default',checked:true,inputValue: 0}]},
                {items:[{xtype:'radio', fieldLabel:'',hideLabel:true,name:'option_static-routes-src',boxLabel:'',inputValue: 1}]},
                {items:[{xtype:'textfield',name:'option_static-routes',width:220,hideLabel:true}]},
                {bodyStyle:'padding:0px;',items:[{xtype:'displayfield',helpText:'Insert a valid IP address pair (like 1.2.3.4,5.6.7.8)',hideLabel:true}]}
            ]
        },
                        {
                            layout:'table',
                            layoutConfig: {columns: 2},
                            defaults:{layout:'form'},
                            items: [{width:410,items:[
                                        this.buildDefaultItem('<?php echo $cliOptionsLbls['option_ntp-servers']?>','option_ntp-servers'),
                                        this.buildDefaultItem('<?php echo $cliOptionsLbls['option_netbios-scope']?>','option_netbios-scope'),
                                        this.buildDefaultItem('<?php echo $cliOptionsLbls['option_time-offset']?>','option_time-offset')
                                    ]},
                                // 2ns col
                                {bodyStyle:'padding-left:30px;',
                                    items:[
                                        this.buildDefaultItem('<?php echo $cliOptionsLbls['option_netbios-name-servers']?>','option_netbios-name-servers'),
                                        this.buildDefaultItem('<?php echo $cliOptionsLbls['option_netbios-node-type']?>','option_netbios-node-type'),
                                        this.buildDefaultItem('<?php echo $cliOptionsLbls['option_dhcp-server-identifier']?>','option_dhcp-server-identifier')
                                    ]}// end 2nd col
                            ]
                        },
                        {
                            layout:'table',
                            bodyStyle:'padding-top:3px;',
                            layoutConfig: {columns: 4},
                            defaults:{layout:'form',bodyStyle:'padding-left:5px;'},
                            items: [
                                {bodyStyle:'padding:0px;',items:[{xtype:'radio', fieldLabel:'<?php echo $cliOptionsLbls['option_slp-directory-agent']?>',name:'option_slp-directory-agent-src',boxLabel:'Default',checked:true,inputValue: 0}]},
                                {items:[{xtype:'radio', hideLabel:true,name:'option_slp-directory-agent-src',boxLabel:'',inputValue: 1}]},
                                {items:[{xtype:'textfield',name:'option_slp-directory-agent',fieldLabel:'',hideLabel:true}]},
                                {items:[{xtype:'checkbox', name:'option_slp-directory-agent-ips', fieldLabel:'',hideLabel:true,boxLabel:'These IPs only?',inputValue: '1'}]}

                            ]
                        },
                        {
                            layout:'table',
                            bodyStyle:'padding-top:3px;',
                            layoutConfig: {columns: 4},
                            defaults:{layout:'form',bodyStyle:'padding-left:5px;'},
                            items: [
                                {bodyStyle:'padding:0px;',items:[{xtype:'radio', fieldLabel:'<?php echo $cliOptionsLbls['option_slp-service-scope']?>',name:'option_slp-service-scope-src',boxLabel:'Default',checked:true,inputValue: 0}]},
                                {items:[{xtype:'radio', hideLabel:true,name:'option_slp-service-scope-src',boxLabel:'',inputValue: 1}]},
                                {items:[{xtype:'textfield',name:'option_slp-service-scope',fieldLabel:'',hideLabel:true}]},
                                {items:[{xtype:'checkbox', name:'option_slp-service-scope-scope', fieldLabel:'',hideLabel:true,boxLabel:'This scope only?',inputValue: '1'}]}

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
        ETFW.DHCP.ClientOptions_Form.superclass.initComponent.apply(this, arguments);



    } // eo function initComponent
    ,buildDefaultItem:function(fieldlabel,name,config){
        var txt_field = {xtype:'textfield',name:name,fieldLabel:'',hideLabel:true};
        if(config) Ext.apply(txt_field,config);
            //txt_field = {xtype:'textfield',name:name,fieldLabel:'',width:width,hideLabel:true};

        var config = {
            bodyStyle:'padding-top:3px;',
            layout:'table',
            layoutConfig: {columns: 3},
            defaults:{layout:'form',bodyStyle:'padding-left:5px;'},
            items: [
                {bodyStyle:'padding:0px;',items:[{xtype:'radio', fieldLabel:fieldlabel,name:name+'-src',boxLabel:'Default',checked:true,inputValue: 0}]},
                {items:[{xtype:'radio', fieldLabel:'',hideLabel:true,name:name+'-src',boxLabel:'',inputValue: 1}]},
                {items:[txt_field]}
            ]
        };
        return config;

    }
    ,onRender:function(){
          // call parent
             ETFW.DHCP.ClientOptions_Form.superclass.onRender.apply(this, arguments);

         // set wait message target
            this.getForm().waitMsgTarget = this.getEl();

        // loads form after initial layout
        //   if(this.all) this.on('afterlayout', this.onLoad, this, {single:true,delay:100});
    }
    ,onLoadd:function(){

        this.load({
            url:this.url,
            waitMsg:'Loading...',
            params:{id:this.service_id,method:'list_clientoptions'},
            success: function ( form, action ) {
                var rec = action.result;
                this.loadDefaultData(rec);
                this.getForm().loadRecord(rec);
            },scope:this
        });

    }
    ,loadRecord:function(rec){
        this.loadDefaultData(rec);
        this.getForm().loadRecord(rec);

        var uuidField = this.getForm().findField('uuid');
        uuidField.setValue(rec.data['uuid']);

    }
    ,loadDefaultData:function(rec){
        var records = Ext.ux.util.clone(rec.data);

        for(field in records)
            rec.data[field+'-src'] = 1;


        var routers = rec.data['option_routers'];
        if(routers)
            rec.data['option_routers'] = routers.replace(/[\s,]+/g, ' ');

        var domain_name_servers = rec.data['option_domain-name-servers'];
        if(domain_name_servers)
            rec.data['option_domain-name-servers'] = domain_name_servers.replace(/[\s,]+/g, ' ');

        var time_servers = rec.data['option_time-servers'];
        if(time_servers)
            rec.data['option_time-servers'] = time_servers.replace(/[\s,]+/g, ' ');

        var log_servers = rec.data['option_log-servers'];
        if(log_servers)
            rec.data['option_log-servers'] = log_servers.replace(/[\s,]+/g, ' ');

        var nis_servers = rec.data['option_nis-servers'];
        if(nis_servers)
            rec.data['option_nis-servers'] = nis_servers.replace(/[\s,]+/g, ' ');

        var font_servers = rec.data['option_font-servers'];
        if(font_servers)
            rec.data['option_font-servers'] = font_servers.replace(/[\s,]+/g, ' ');

        var xdm_servers = rec.data['option_x-display-manager'];
        if(xdm_servers)
            rec.data['option_x-display-manager'] = xdm_servers.replace(/[\s,]+/g, ' ');

        var static_routes = rec.data['option_static-routes'];
        if(static_routes){

            var static_routes_splited = static_routes.split(',');
            var pairs_array = [];
            for(var i=0,len = static_routes_splited.length;i<len;i++){
                var ips_pair = static_routes_splited[i].replace(/^\s+/g, '');
                ips_pair = ips_pair.replace(/\s+/g,',');
                pairs_array.push(ips_pair);
            }
            rec.data['option_static-routes'] = pairs_array.join(' ');

        }

        var ntp_servers = rec.data['option_ntp-servers'];
        if(ntp_servers)
            rec.data['option_ntp-servers'] = ntp_servers.replace(/[\s,]+/g, ' ');

        var netbios_ns = rec.data['option_netbios-name-servers'];
        if(netbios_ns)
            rec.data['option_netbios-name-servers'] = netbios_ns.replace(/[\s,]+/g, ' ');

        var slp_directory_agent = rec.data['option_slp-directory-agent'];
        if(slp_directory_agent){
            var found_true = slp_directory_agent.indexOf('true');
            if(found_true!=-1){
                slp_directory_agent = slp_directory_agent.replace('true ','');
                rec.data['option_slp-directory-agent'] = slp_directory_agent.replace(/[\s,]+/g, ' ');
                rec.data['option_slp-directory-agent-ips'] = 1;
            }else{
                slp_directory_agent = slp_directory_agent.replace('false ','');
                rec.data['option_slp-directory-agent'] = slp_directory_agent.replace(/[\s,]+/g, ' ');
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

        if(Ext.isEmpty(rec.data['authoritative'],true)) rec.data['authoritative'] = 0;
        else rec.data['authoritative'] = 1;


    }
    ,getDataSubmit:function(){
        var form_values = this.getForm().getValues();

        var send_data = new Object();
        send_data['uuid'] = form_values['uuid'];
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

        if(send_data['option']['routers'])
            send_data['option']['routers'] = send_data['option']['routers'].replace(/[\s,]+/g, ', ');

        if(send_data['option']['domain-name-servers'])
            send_data['option']['domain-name-servers'] = send_data['option']['domain-name-servers'].replace(/[\s,]+/g, ', ');

        if(send_data['option']['time-servers'])
            send_data['option']['time-servers'] = send_data['option']['time-servers'].replace(/[\s,]+/g, ', ');

        if(send_data['option']['log-servers'])
            send_data['option']['log-servers'] = send_data['option']['log-servers'].replace(/[\s,]+/g, ', ');

        if(send_data['option']['nis-servers'])
            send_data['option']['nis-servers'] = send_data['option']['nis-servers'].replace(/[\s,]+/g, ', ');

        if(send_data['option']['font-servers'])
            send_data['option']['font-servers'] = send_data['option']['font-servers'].replace(/[\s,]+/g, ', ');

        if(send_data['option']['x-display-manager'])
            send_data['option']['x-display-manager'] = send_data['option']['x-display-manager'].replace(/[\s,]+/g, ', ');


        if(send_data['option']['static-routes']){
            var static_routes = send_data['option']['static-routes'].replace(/\s+/g, ' ');
            static_routes = static_routes.replace(/(\s+)?,(\s+)?/g, ',');

            var static_routes_splited = static_routes.split(' ');
            var pairs_array = [];
            for(var i=0,len = static_routes_splited.length;i<len;i++){
                var ips_pair = static_routes_splited[i];
                ips_pair = ips_pair.replace(/,/g,' ');
                pairs_array.push(ips_pair);
            }

            send_data['option']['static-routes'] = pairs_array.join(', ');
        }

        if(send_data['option']['ntp-servers'])
            send_data['option']['ntp-servers'] = send_data['option']['ntp-servers'].replace(/[\s,]+/g, ', ');

        if(send_data['option']['netbios-name-servers'])
            send_data['option']['netbios-name-servers'] = send_data['option']['netbios-name-servers'].replace(/[\s,]+/g, ', ');

        if(send_data['option']['slp-service-scope'])
            if(form_values['option_slp-service-scope-scope'] == 1)
                send_data['option']['slp-service-scope'] = 'true '+form_values['option_slp-service-scope'];
        else
            send_data['option']['slp-service-scope'] = 'false '+form_values['option_slp-service-scope'];

        if(send_data['option']['slp-directory-agent']){

            if(form_values['option_slp-directory-agent-ips'] == 1)
                send_data['option']['slp-directory-agent'] = 'true '+form_values['option_slp-directory-agent'].replace(/[\s,]+/g, ', ');
            else
                send_data['option']['slp-directory-agent'] = 'false '+form_values['option_slp-directory-agent'].replace(/[\s,]+/g, ', ');
        }

        return send_data;

    }
    ,onSave:function() {

         if(this.form.isValid()){
             var send_data = this.getDataSubmit();

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
                params:{id:this.service_id,method:'set_options',params:Ext.encode(send_data)},
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
                    var msg = 'Client options edited successfully';
                    Ext.ux.Logger.info(msg);
                    View.notify({html:msg});
                    this.fireEvent('updatedClientOptions',this);


                },scope:this
            });// END Ajax request

         } else{
            Ext.MessageBox.show({
                title: 'Error',
                msg: 'Please fix the errors noted.',
                buttons: Ext.MessageBox.OK,
                icon: Ext.MessageBox.WARNING
            });
         }

    }
    ,onSaveAll:function() {
        if(this.form.isValid()){
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
                    requestcomplete:function(){Ext.MessageBox.hide();},
                    requestexception:function(c,r,o){Ext.Ajax.fireEvent('requestexception',c,r,o);}
                }
            });// end conn


            conn.request({
                url: this.url,
                params:{id:this.service_id,method:'set_clientoptions',params:Ext.encode(send_data)},
                // everything ok...
                success: function(resp,opt){
                    (this.ownerCt).close();
                    var msg = 'General Client options edited successfully';
                    Ext.ux.Logger.info(msg);
                    View.notify({html:msg});
                },scope:this
            });// END Ajax request

        } else{
            Ext.MessageBox.show({
                title: 'Error',
                msg: 'Please fix the errors noted.',
                buttons: Ext.MessageBox.OK,
                icon: Ext.MessageBox.WARNING
            });
        }

    }


}); // eo extend

ETFW.DHCP.Data_Form_DefaultItems = function(){

    var default_fields = [
            {
                layout:'table',border:false,
                layoutConfig: {columns: 2},
                defaults:{layout:'form',border:false},
                items: [{width:410,defaults:{bodyStyle:'padding-top:3px;'},items:[
                        {
                            layout:'table',
                            bodyStyle:'padding-top:0px;',
                            layoutConfig: {columns: 3},border:false,
                            defaults:{layout:'form',bodyStyle:'padding-left:5px;'},
                            items: [
                                {bodyStyle:'padding:0px;',items:[{xtype:'radio', fieldLabel:'<?php echo $cliOptionsLbls['filename']?>',name:'filename-src',boxLabel:'None',checked:true,inputValue: 0}]},
                                {items:[{xtype:'radio', hideLabel:true,name:'filename-src',boxLabel:'',inputValue: 1}]},
                                {items:[{xtype:'textfield',name:'filename',hideLabel:true}]}
                            ]
                        },
                        //boot file server
                        {
                            layout:'table',
                            layoutConfig: {columns: 3},
                            defaults:{layout:'form',bodyStyle:'padding-left:5px;'},
                            items: [
                                {bodyStyle:'padding:0px;',items:[{xtype:'radio', fieldLabel:'<?php echo $cliOptionsLbls['next-server']?>',name:'next-server-src',boxLabel:'This server',checked:true,inputValue: 0}]},
                                {items:[{xtype:'radio', hideLabel:true,name:'next-server-src',boxLabel:'',inputValue: 1}]},
                                {items:[{xtype:'textfield',name:'next-server',hideLabel:true}]}
                            ]
                        },
                        //Lease length for BOOTP clients
                        {
                            layout:'table',
                            layoutConfig: {columns: 4},
                            defaults:{layout:'form',bodyStyle:'padding-left:5px;',height:40},
                            items: [
                                {bodyStyle:'padding:0px;',items:[{xtype:'radio', fieldLabel:'<?php echo $cliOptionsLbls['dynamic-bootp-lease-length']?>',name:'dynamic-bootp-lease-length-src',boxLabel:'Forever',checked:true,inputValue: 0}]},
                                {items:[{xtype:'radio', hideLabel:true,name:'dynamic-bootp-lease-length-src',boxLabel:'',inputValue: 1}]},
                                {items:[{xtype:'numberfield',name:'dynamic-bootp-lease-length',hideLabel:true}]},
                                {items:[{xtype:'displayfield',value:'secs',hideLabel:true}]}
                            ]
                        },
                        //Dynamic DNS enabled?
                        {
                            layout:'table',
                            layoutConfig: {columns: 3},
                            defaults:{layout:'form',bodyStyle:'padding-left:5px;'},
                            items: [
                                {bodyStyle:'padding:0px;',items:[{xtype:'radio', fieldLabel:'<?php echo $cliOptionsLbls['ddns-updates'] ?>',labelSeparator:'?',name:'ddns-updates',boxLabel:'Yes',inputValue: 'on'}]},
                                {items:[{xtype:'radio', hideLabel:true,name:'ddns-updates',boxLabel:'No',inputValue: 'off'}]},
                                {items:[{xtype:'radio', hideLabel:true,name:'ddns-updates',checked:true,boxLabel:'Default',inputValue: ''}]}
                            ]
                        },
                        //Dynamic DNS reverse domain
                        {
                            layout:'table',
                            layoutConfig: {columns: 3},
                            defaults:{layout:'form',bodyStyle:'padding-left:5px;',height:40},
                            items: [
                                {bodyStyle:'padding:0px;',items:[{xtype:'radio', fieldLabel:'<?php echo $cliOptionsLbls['ddns-rev-domainname'] ?>',name:'ddns-rev-domainname-src',boxLabel:'Default',checked:true,inputValue: 0}]},
                                {items:[{xtype:'radio', fieldLabel:'',hideLabel:true,name:'ddns-rev-domainname-src',boxLabel:'',inputValue: 1}]},
                                {items:[{xtype:'textfield',name:'ddns-rev-domainname',fieldLabel:'',hideLabel:true}]}
                            ]
                        }
                    ]},
                // 2ns col
                {bodyStyle:'padding-left:30px;',width:440,defaults:{bodyStyle:'padding-top:3px;'},
                    items:[

                        //maximum lease time
                        {
                            layout:'table',
                            layoutConfig: {columns: 4},
                            defaults:{layout:'form',bodyStyle:'padding-left:5px;'},
                            items: [
                                {bodyStyle:'padding:0px;',items:[{xtype:'radio', fieldLabel:'<?php echo $cliOptionsLbls['max-lease-time'] ?>',name:'max-lease-time-src',boxLabel:'Default',checked:true,inputValue: 0}]},
                                {items:[{xtype:'radio', hideLabel:true,name:'max-lease-time-src',boxLabel:'',inputValue: 1}]},
                                {items:[{xtype:'numberfield',name:'max-lease-time',hideLabel:true}]},
                                {items:[{xtype:'displayfield',value:'secs',hideLabel:true}]}
                            ]
                        },
                        //server name
                        {
                            layout:'table',
                            layoutConfig: {columns: 3},
                            defaults:{layout:'form',bodyStyle:'padding-left:5px;'},
                            items: [
                                {bodyStyle:'padding:0px;',items:[{xtype:'radio', fieldLabel:'<?php echo $cliOptionsLbls['server-name'] ?>',name:'server-name-src',boxLabel:'Default',checked:true,inputValue: 0}]},
                                {items:[{xtype:'radio',hideLabel:true,name:'server-name-src',boxLabel:'',inputValue: 1}]},
                                {items:[{xtype:'textfield',name:'server-name',hideLabel:true}]}
                            ]
                        },
                        //Lease end for BOOTP clients
                        {
                            layout:'table',
                            layoutConfig: {columns: 4},
                            defaults:{layout:'form',bodyStyle:'padding-left:5px;',height:40},
                            items: [
                                {bodyStyle:'padding:0px;',items:[{xtype:'radio', fieldLabel:'<?php echo $cliOptionsLbls['dynamic-bootp-lease-cutoff'] ?>',name:'dynamic-bootp-lease-cutoff-src',boxLabel:'Never',checked:true,inputValue: 0}]},
                                {items:[{xtype:'radio', hideLabel:true,name:'dynamic-bootp-lease-cutoff-src',boxLabel:'',inputValue: 1}]},
                                {items:[{xtype:'textfield',hideLabel:true,name:'dynamic-bootp-lease-cutoff'}]},
                                {bodyStyle:'padding:0px;',items:[{xtype:'displayfield',helpText:'W YYYY/MM/DD HH:MM:SS',hideLabel:true}]}
                            ]
                        },
                        //Dynamic DNS domain name
                        {
                            layout:'table',
                            layoutConfig: {columns: 3},
                            defaults:{layout:'form',bodyStyle:'padding-left:5px;',height:40},
                            items: [
                                {bodyStyle:'padding:0px;',items:[{xtype:'radio', fieldLabel:'<?php echo $cliOptionsLbls['ddns-domainname'] ?>',name:'ddns-domainname-src',boxLabel:'Default',checked:true,inputValue: 0}]},
                                {items:[{xtype:'radio', hideLabel:true,name:'ddns-domainname-src',boxLabel:'',inputValue: 1}]},
                                {items:[{xtype:'textfield',name:'ddns-domainname',hideLabel:true}]}
                            ]
                        },
                        //Dynamic DNS hostname
                        {
                            layout:'table',
                            layoutConfig: {columns: 3},
                            defaults:{layout:'form',bodyStyle:'padding-left:5px;',height:40},
                            items: [
                                {bodyStyle:'padding:0px;',items:[{xtype:'radio', fieldLabel:'<?php echo $cliOptionsLbls['ddns-hostname'] ?>',name:'ddns-hostname-src',boxLabel:'From client',checked:true,inputValue: 0}]},
                                {items:[{xtype:'radio', hideLabel:true,name:'ddns-hostname-src',boxLabel:'',inputValue: 1}]},
                                {items:[{xtype:'textfield',name:'ddns-hostname',hideLabel:true}]}
                            ]
                        }

                    ]}// end 2nd col





            ]
            },
            //Allow unknown clients?
            {
                layout:'table',
                layoutConfig: {columns: 4},
                defaults:{layout:'form',bodyStyle:'padding-left:5px;'},
                items: [
                    {bodyStyle:'padding:0px;',items:[{xtype:'radio', fieldLabel:'<?php echo $cliOptionsLbls['unknown-clients']?>',name:'unknown-clients',labelSeparator:'?',boxLabel:'Allow',inputValue: 'allow'}]},
                    {items:[{xtype:'radio', hideLabel:true,name:'unknown-clients',boxLabel:'Deny',inputValue: 'deny'}]},
                    {items:[{xtype:'radio', hideLabel:true,name:'unknown-clients',boxLabel:'Ignore',inputValue: 'ignore'}]},
                    {items:[{xtype:'radio', hideLabel:true,name:'unknown-clients',boxLabel:'Default',checked:true,inputValue: ''}]}
                ]
            },
            //Can clients update their own records?
            {
                layout:'table',
                layoutConfig: {columns: 4},
                defaults:{layout:'form',height:40,bodyStyle:'padding-left:5px;'},
                items: [
                    {bodyStyle:'padding:0px;',items:[{xtype:'radio', fieldLabel:'<?php echo $cliOptionsLbls['client-updates']?>',labelSeparator:'?',name:'client-updates',boxLabel:'Allow',inputValue: 'allow'}]},
                    {items:[{xtype:'radio', name:'client-updates',hideLabel:true,boxLabel:'Deny',inputValue: 'deny'}]},
                    {items:[{xtype:'radio', name:'client-updates',hideLabel:true,boxLabel:'Ignore',inputValue: 'ignore'}]},
                    {items:[{xtype:'radio', name:'client-updates',hideLabel:true,boxLabel:'Default',checked:true,inputValue: ''}]}
                ]
            }

        ];// end layout 2 col
        return default_fields;
}();


/*
*  ADDRESS RANGES GRID TEMPLATE
*
*/

ETFW.DHCP.AddressRangesGrid = Ext.extend(Ext.grid.GridPanel, {
    initComponent:function() {

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
                    editor: new Ext.form.TextField({
                        allowBlank: false,
                        selectOnFocus:true,
                        vtype:'ip_addr'
                    })
                }, {
                    header: 'To address',
                    dataIndex: 'to_range',
                    width: 100,
                    align: 'right',
                    editor: new Ext.form.TextField({
                        allowBlank: false,
                        selectOnFocus:true,
                        vtype:'ip_addr'
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
        Ext.apply(this,{
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
                        var Range = this.store.recordType;
                        var r = new Range({
                            from_range: '0.0.0.0',
                            to_range: '0.0.0.0',
                            bootp: false
                        });

                        defaultEditor.stopEditing();

                        address_ranges_store.insert(0, r);
                        this.getView().refresh();
                        this.getSelectionModel().selectRow(0);


                        defaultEditor.startEditing(0);
                    }
                    ,scope:this}
                ,{
                    ref: '../removeBtn',
                    text: 'Remove address range',
                    disabled: true,
                    handler: function(){
                        defaultEditor.stopEditing();
                        var s = this.getSelectionModel().getSelections();
                        for(var i = 0, r; r = s[i]; i++){
                            address_ranges_store.remove(r);
                        }
                    },scope:this
                }]

        });

        this.getSelectionModel().on('selectionchange', function(sm){
            this.removeBtn.setDisabled(sm.getCount() < 1);
        },this);

        /*
         * END Address ranges grid
         */

        ETFW.DHCP.AddressRangesGrid.superclass.initComponent.call(this);
    }
    ,loadRangeData:function(range_data){

        var total_range = range_data.length;

        //dynamic-bootp
        var range_store = new  Object();
        var range_array = [];
        for(var i=0,len = total_range;i<len;i++){

            var range_record = range_data[i];
            var bootp = false;

            if(range_record){

                if(!(range_record.indexOf("dynamic-bootp ")==-1)){
                    range_record = range_record.replace("dynamic-bootp ","");
                    bootp = true;
                }

                var from_to = range_record.split(' ');
                range_array.push({"from_range":from_to[0],"to_range":from_to[1],"bootp":bootp});
            }

        }
        range_store["range"] = range_array;
        range_store["success"] =true;

        this.getStore().loadData(range_store);

    }
    ,getRangeData:function(){
        var ds = this.getStore();
        var totalRec = ds.getCount();
        var recs = [];

        Ext.each(ds.getRange(0,totalRec),function(e){
            if(e.data.bootp===true) recs.push('dynamic-bootp '+e.data.from_range+' '+e.data.to_range);
            else recs.push(e.data.from_range+' '+e.data.to_range);
        });

        return recs;
    }
});



/*
*  POOL FORM TEMPLATE
*
*/

ETFW.DHCP.Pool_Form = Ext.extend(Ext.form.FormPanel, {

    border:true
    ,frame:true
    ,autoScroll:true

    ,bodyStyle:'padding-top:10px'
    ,url:<?php echo json_encode(url_for('etfw/json'))?>
    ,initComponent:function() {

        this.address_ranges = new ETFW.DHCP.AddressRangesGrid();

        this.items = this.buildPoolForm();

        // build form-buttons
        this.buttons = this.buildPoolUI();

        this.on('beforerender',function(){
            Ext.getBody().mask('Loading form data...');},this);

        this.on('render',function(){
            Ext.getBody().unmask();},this,{delay:100});

        ETFW.DHCP.Pool_Form.superclass.initComponent.call(this);

    }
    ,buildPoolUI:function(){
        return [{
                text: 'Create',
                ref: '../saveBtn',
                handler: this.onCreatePool,
                scope: this
            },
            {
                text: 'Delete',
                ref: '../delBtn',
                hidden:true,
                handler:function(){
                    Ext.MessageBox.show({
                        title:'Delete pool',
                        msg: 'You are about to delete this pool. <br />Are you sure you want to delete?',
                        buttons: Ext.MessageBox.YESNOCANCEL,
                        fn: function(btn){

                            if(btn=='yes'){
                                var form_values = this.getForm().getValues();
                                var uuid = form_values['uuid'];
                                if(uuid) this.fireEvent('deletePool',uuid);
                            }

                        },
                        scope:this,
                        icon: Ext.MessageBox.QUESTION
                    });
                },
                scope: this
            }];

    }
    /*
    *
    * BUILD POOL FORM TEMPLATE
    *
    */
    ,buildPoolForm:function(){



        return [
                {
                    xtype:'fieldset',
                    labelWidth:130,
                    border:false,
                    title: 'Address pool options',
                    defaults:{bodyStyle:'padding-top:3px;'},
                    items :[

                        {
                            layout:'table',
                            layoutConfig: {columns:2},
                            items:[
                                {
                                    labelAlign:'left',
                                    layout:'form',
                                    items:[
                                        {xtype:'hidden',name:'uuid'},
                                        {fieldLabel:'<?php echo $poolLbls['range']?>'}]
                                },
                                {
                                    labelAlign:'left',
                                    // layout:'form',
                                    bodyStyle: 'padding-bottom:10px;',
                                    items:this.address_ranges
                                }
                            ]
                        },
                        {
                            layout:'table',
                            layoutConfig: {columns: 3},
                            defaults:{layout:'form',bodyStyle:'padding-left:5px;'},
                            items: [
                                {bodyStyle:'padding:0px;',items:[{xtype:'radio', fieldLabel:'<?php echo $poolLbls['failover']?>',name:'failover-src',boxLabel:'None',checked:true,inputValue: 0}]},
                                {items:[{xtype:'radio', hideLabel:true,name:'failover-src',boxLabel:'',inputValue: 1}]},
                                {items:[{xtype:'textfield',name:'failover',hideLabel:true}]}
                            ]
                        },
                        {
                            layout:'table',
                            layoutConfig: {columns: 2},
                            defaults:{layout:'form'},
                            items: [{width:410,items:[{xtype:'textarea',width:200,name:'allow',fieldLabel:'<?php echo $poolLbls['allow']?>'}]},
                                // 2nd col
                                {bodyStyle:'padding-left:30px;',
                                    items:[
                                        {xtype:'textarea',width:200,name:'deny',fieldLabel:'<?php echo $poolLbls['deny']?>'}]}
                            ]
                        },
                        {
                            layout:'table',
                            layoutConfig: {columns: 4},
                            defaults:{layout:'form',bodyStyle:'padding-left:5px;'},
                            items: [
                                {bodyStyle:'padding:0px;',items:[{xtype:'radio', fieldLabel:'<?php echo $poolLbls['default-lease-time']?>',name:'default-lease-time-src',boxLabel:'Default',checked:true,inputValue: 0}]},
                                {items:[{xtype:'radio', hideLabel:true,name:'default-lease-time-src',boxLabel:'',inputValue: 1}]},
                                {items:[{xtype:'numberfield',name:'default-lease-time',hideLabel:true}]},
                                {items:[{xtype:'displayfield',value:'secs',hideLabel:true}]}
                            ]
                        },
                        ETFW.DHCP.Data_Form_DefaultItems


                    ]
                }
            ];

    }
    ,loadPoolRecord:function(rec){


        this.saveBtn.setText('Save');
        this.saveBtn.setHandler(this.onUpdatePool,this);
        this.delBtn.show();

        this.address_ranges.loadRangeData(rec.data['range']);

        var form_values = this.getForm().getValues();

        if(rec.data['failover']){
            rec.data['failover'] = rec.data['failover'].replace("peer ","");
        }

        //check if '-src' fields are to be selected
        for(var data in form_values){
            if(data.indexOf('-src')!=-1){
                var field = data.replace('-src','');
                if(rec.data[field]) rec.data[data] = 1;
            }
        }

        this.getForm().loadRecord(rec);

    }
    ,getDataSubmit:function(){

        var send_data = new Object();
        var form_values = this.getForm().getValues();

        if(form_values['failover'] && form_values['failover-src']==1){
            send_data['failover'] = 'peer '+form_values['failover'];
            delete form_values['failover-src'];
            delete form_values['failover'];
        }

        if(!Ext.isEmpty(this.address_ranges.getRangeData()))
            send_data['range'] = this.address_ranges.getRangeData();



        //gather allow/deny data.....
        var allow_array = [];
        var deny_array = [];
        var ignore_array = [];

        if(form_values['allow']){
            allow_array = form_values['allow'].split('\n');
            delete form_values['allow'];
        }

        if(form_values['deny']){
            deny_array = form_values['deny'].split('\n');
            delete form_values['deny'];
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
        delete form_values['client-updates'];

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
        delete form_values['unknown-clients'];

        if(allow_array.length>0) send_data['allow'] = allow_array;
        if(deny_array.length>0) send_data['deny'] = deny_array;
        if(ignore_array.length>0) send_data['ignore'] = ignore_array;

        //end gather allow/deny data.....


        for(var field in form_values){

            if(field.indexOf('-src')==-1 && !Ext.isEmpty(form_values[field])){

                if(form_values[field+'-src']){
                    var field_src = form_values[field+'-src'];
                    delete form_values[field+'-src'];

                    if(field_src==1) send_data[field] = form_values[field];

                }else{
                    //check if item is auto-generated..only chose defined!
                    if(field.indexOf('ext-comp-')==-1) send_data[field] = form_values[field];
                }
            }
        }

        return send_data;

    }
    ,setPoolParent:function(uuid){
        this.poolParent = uuid;
    }
    ,onCreatePool:function(){

        var send_data = this.getDataSubmit();

        if(this.poolParent){

            send_data['parent'] = new Object();
            send_data['parent']['uuid'] = this.poolParent;

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
                ,requestexception:function(c,r,o){Ext.Ajax.fireEvent('requestexception',c,r,o);}
            }
        });// end conn

        conn.request({
            url: this.url,
            params:{id:this.service_id,method:'add_pool',params:Ext.encode(send_data)},
            // everything ok...
            success: function(resp,opt){
                this.fireEvent('updatedPool',this);
                var msg = 'Pool successfully added';
                Ext.ux.Logger.info(msg);
                View.notify({html:msg});

            },scope:this
        });// END Ajax request

    }
    ,onUpdatePool:function(){

        var send_data = this.getDataSubmit();

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
                }// on request complete hide message
                ,requestcomplete:function(){Ext.MessageBox.hide();}
                ,requestexception:function(c,r,o){Ext.Ajax.fireEvent('requestexception',c,r,o);}
            }
        });// end conn


        conn.request({
            url: this.url,
            params:{id:this.service_id,method:'set_pool',params:Ext.encode(send_data)},
            // everything ok...
            success: function(resp,opt){
                this.fireEvent('updatedPool',this);
                var msg = 'Pool edited successfully';
                Ext.ux.Logger.info(msg);
                View.notify({html:msg});
            },scope:this
        });// END Ajax request

    }
});


ETFW.DHCP.Subnet_Form = Ext.extend(Ext.form.FormPanel, {

    border:false
    ,frame:true
    ,autoScroll:true
    ,bodyStyle:'padding-top:10px'
    ,url:<?php echo json_encode(url_for('etfw/json'))?>
    ,initComponent:function() {

        this.address_ranges = new ETFW.DHCP.AddressRangesGrid();

        this.items = this.buildSubnetForm();

        // build form-buttons
        this.buttons = this.buildSubnetUI();

        this.on('beforerender',function(){
            Ext.getBody().mask('Loading form data...');},this);

        this.on('render',function(){
            Ext.getBody().unmask();},this,{delay:100});

        ETFW.DHCP.Subnet_Form.superclass.initComponent.call(this);

    }
    ,buildSubnetUI:function(){
        return [{
                text: 'Create',
                ref: '../saveBtn',
                handler: this.onCreateSubnet,
                scope: this
            },
            {
                text: 'Delete',
                ref: '../delBtn',
                hidden:true,
                handler:function(){
                    Ext.MessageBox.show({
                        title:'Delete pool',
                        msg: 'You are about to delete this subnet. <br />Are you sure you want to delete?',
                        buttons: Ext.MessageBox.YESNOCANCEL,
                        fn: function(btn){

                            if(btn=='yes'){
                                var form_values = this.getForm().getValues();
                                var uuid = form_values['uuid'];
                                if(uuid) this.fireEvent('deleteSubnet',uuid);
                            }

                        },
                        scope:this,
                        icon: Ext.MessageBox.QUESTION
                    });
                },
                scope: this
            }];

    }
    ,buildSubnetForm:function(){

        //shared network combo

        var sharedNet_store = new Ext.data.Store({
            reader: new Ext.data.JsonReader({
                root:'data',
                fields:['uuid','value']
            })
        });
        sharedNet_store.setDefaultSort('value', 'ASC');

        this.sharedNetCombo = new Ext.form.ComboBox({
            name:'shared_network',
            mode: 'local',
            triggerAction: 'all',
            editable:false,
            fieldLabel: '<?php echo $subnetLbls['parent']?>',
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
            fieldLabel:"<?php echo $subnetLbls['hosts']?>",
            valueField:"uuid",
            displayField:"host",
            height:80,
            name:'hosts',
            allowBlank:true,
            store :new Ext.data.Store({
                reader: new Ext.data.JsonReader({
                    root:'data',
                    fields:['uuid','host']
                })
            })
        });

        this.groups_directly = new Ext.ux.Multiselect({
            fieldLabel:"<?php echo $subnetLbls['groups']?>",
            valueField:"uuid",
            displayField:"hosts_count",
            height:80,
            name:'groups',
            allowBlank:true,
            store:new Ext.data.Store({
                reader: new Ext.data.JsonReader({
                    root:'data',
                    fields:['uuid','hosts_count']
                })
            })
        });

        return [
                {
                    xtype:'fieldset',
                    labelWidth:130,
                    border:false,
                    title: 'Subnet details',
                    defaults:{bodyStyle:'padding-top:3px;'},
                    items :[
                        {xtype:'hidden',name:'uuid'},
                        {xtype:'textfield',width:200,name:'lastcomment',fieldLabel:'<?php echo $subnetLbls['lastcomment']?>'},
                        {
                            layout:'table',
                            layoutConfig: {columns: 2},
                            defaults:{layout:'form'},
                            items: [{items:[{xtype:'textfield',name:'address',fieldLabel:'<?php echo $subnetLbls['address']?>'}]},
                                {labelWidth:60,bodyStyle:'padding-left:10px',items:[{xtype:'textfield',name:'netmask',fieldLabel: '<?php echo $subnetLbls['netmask']?>'}]}
                            ]
                        },
                        {
                            layout:'table',
                            layoutConfig: {columns:2},
                            items:[
                                {
                                    labelAlign:'left',
                                    layout:'form',
                                    items:[{fieldLabel:'<?php echo $subnetLbls['range']?>'}]
                                },
                                {
                                    labelAlign:'left',
                                    // layout:'form',
                                    bodyStyle: 'padding-bottom:10px;',
                                    items:this.address_ranges
                                }
                            ]
                        },

                        {
                            layout:'table',
                            layoutConfig: {columns: 2},
                            defaults:{layout:'form'},
                            items: [{width:410,items:[this.sharedNetCombo]},
                                // 2nd col
                                {bodyStyle:'padding-left:30px;',
                                    items:[
                                        {
                                            layout:'table',
                                            layoutConfig: {columns: 4},
                                            defaults:{layout:'form',bodyStyle:'padding-left:5px;'},
                                            items: [
                                                {bodyStyle:'padding:0px;',items:[{xtype:'radio', fieldLabel:'<?php echo $subnetLbls['default-lease-time']?>',name:'default-lease-time-src',boxLabel:'Default',checked:true,inputValue: 0}]},
                                                {items:[{xtype:'radio', hideLabel:true,name:'default-lease-time-src',boxLabel:'',inputValue: 1}]},
                                                {items:[{xtype:'textfield',name:'default-lease-time',hideLabel:true}]},
                                                {items:[{xtype:'displayfield',value:'secs',hideLabel:true}]}
                                            ]
                                        }
                                    ]}// end 2nd col
                            ]
                        },// end layout 2 col
                        ETFW.DHCP.Data_Form_DefaultItems,
                        //Server is authoritative for this subnet?
                        {
                            layout:'table',
                            layoutConfig: {columns: 2},
                            defaults:{layout:'form',height:40,bodyStyle:'padding-left:5px;'},
                            items: [
                                {bodyStyle:'padding:0px;',items:[{xtype:'radio', fieldLabel:'<?php echo $subnetLbls['authoritative']?>',labelSeparator:'?',name:'authoritative',boxLabel:'Yes',inputValue: 1}]},
                                {items:[{xtype:'radio', hideLabel:true,name:'authoritative',boxLabel:'Default(No)',checked:true,inputValue: 0}]}
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
                                {bodyStyle:'padding-left:30px',
                                    items:[
                                        this.groups_directly
                                    ]}
                            ]
                        }

                    ]
                }

            ];
    }
    ,reset:function(){
        this.remoteLoadData();
    }
    ,loadSubnetRecord:function(rec){


        this.saveBtn.setText('Save');
        this.saveBtn.setHandler(this.onUpdateSubnet,this);
        this.delBtn.show();

        this.address_ranges.loadRangeData(rec.data['range']);

        var form_values = this.getForm().getValues();

        //check if '-src' fields are to be selected
        for(var data in form_values){
            if(data.indexOf('-src')!=-1){
                var field = data.replace('-src','');
                if(rec.data[field]) rec.data[data] = 1;
            }
        }

        if(Ext.isEmpty(rec.data['authoritative'],true)) rec.data['authoritative'] = 0;
        else rec.data['authoritative'] = 1;

        this.getForm().loadRecord(rec);

        this.remoteLoadData(rec);

    }
    //load remote data to populate fields on display
    ,remoteLoadData:function(rec){

        this.load({
            url:this.url,
            waitMsg:'Loading...',
            params:{id:this.service_id,method:'list_all'},
            success: function ( form, action ){
                var result = action.result;
                var data = result.data;

                this.sharedNetCombo.getStore().loadData(data['shared_networks']);

                if(!rec){

                    var hosts_info = data['subnet']['hosts_list'];
                    var groups_info = data['subnet']['groups_list'];
                    this.hosts_directly.store.loadData(hosts_info);
                    this.groups_directly.store.loadData(groups_info);

                }
                else{

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

                    if(rec.data['parent-uuid']) this.sharedNetCombo.setValue(rec.data['parent-uuid']);
                    this.hosts_directly.setValue(rec.data.hosts);
                    this.groups_directly.setValue(rec.data.groups);

                }

            }
            ,scope:this

        });

    }
    ,getDataSubmit:function(){

        var send_data = new Object();
        var form_values = this.getForm().getValues();

        if(!Ext.isEmpty(this.address_ranges.getRangeData()))
            send_data['range'] = this.address_ranges.getRangeData();

        if(this.sharedNetCombo){
            var sharedNet = this.sharedNetCombo.getValue();
            if(sharedNet){
                send_data['parent'] = new Object();
                send_data['parent']['uuid'] = sharedNet;
            }else send_data['parent'] = new Object();
            delete form_values['shared_network'];
        }

        //hosts directly....
        var hosts_values = this.hosts_directly.getValue();
        var hosts_values_array = hosts_values.split(',');
        var hosts = [];

        for(var j=0,jlen=hosts_values_array.length;j<jlen;j++){

            if(hosts_values_array[j])
                hosts.push({"uuid":hosts_values_array[j]});


        }
        delete form_values['hosts'];
        send_data['hosts'] = hosts;

        //groups directly....
        var groups_values = this.groups_directly.getValue();
        var groups_values_array = groups_values.split(',');
        var groups = [];

        for(var j=0,jlen=groups_values_array.length;j<jlen;j++){

            if(groups_values_array[j])
                groups.push({"uuid":groups_values_array[j]});


        }
        delete form_values['groups'];
        send_data['groups'] = groups;


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
            default:
                break;
        }
        delete form_values['client-updates'];

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
            default:
                break;

        }
        delete form_values['unknown-clients'];

        if(allow_array.length>0) send_data['allow'] = allow_array;
        if(deny_array.length>0) send_data['deny'] = deny_array;
        if(ignore_array.length>0) send_data['ignore'] = ignore_array;


        if(form_values['authoritative'] && form_values['authoritative']==1) send_data['authoritative'] = '';
        delete form_values['authoritative'];
        //end gather allow/deny data.....


        for(var field in form_values){

            if(field && field.indexOf('-src')==-1 && !Ext.isEmpty(form_values[field])){

                if(form_values[field+'-src']){
                    var field_src = form_values[field+'-src'];
                    delete form_values[field+'-src'];

                    if(field_src==1) send_data[field] = form_values[field];

                }else{
                    //check if item is auto-generated..only chose defined!
                    if(field.indexOf('ext-comp-')==-1) send_data[field] = form_values[field];
                }

            }
        }

        return send_data;

    }
    ,onCreateSubnet:function(){

        var send_data = this.getDataSubmit();

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
                }// on request complete hide message
                ,requestcomplete:function(){Ext.MessageBox.hide();}
                ,requestexception:function(c,r,o){Ext.Ajax.fireEvent('requestexception',c,r,o);}
            }
        });// end conn

        conn.request({
            url: this.url,
            params:{id:this.service_id,method:'add_subnet',params:Ext.encode(send_data)},
            // everything ok...
            success: function(resp,opt){
                this.fireEvent('createdSubnet',this);
                var msg = 'Subnet successfully added';
                Ext.ux.Logger.info(msg);
                View.notify({html:msg});

            },scope:this
        });// END Ajax request

    }
    ,onUpdateSubnet:function(){

        var send_data = this.getDataSubmit();

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
                }// on request complete hide message
                ,requestcomplete:function(){Ext.MessageBox.hide();}
                ,requestexception:function(c,r,o){Ext.Ajax.fireEvent('requestexception',c,r,o);}
            }
        });// end conn


        conn.request({
            url: this.url,
            params:{id:this.service_id,method:'set_subnet',params:Ext.encode(send_data)},
            // everything ok...
            success: function(resp,opt){
                this.fireEvent('updatedSubnet',this);
                var msg = 'Subnet edited successfully';
                Ext.ux.Logger.info(msg);
                View.notify({html:msg});
            },scope:this
        });// END Ajax request

    }

});


ETFW.DHCP.Sharednetwork_Form = Ext.extend(Ext.form.FormPanel, {

    border:false
    ,frame:true
    ,autoScroll:true
    ,bodyStyle:'padding-top:10px'
    ,url:<?php echo json_encode(url_for('etfw/json'))?>
    ,initComponent:function() {

        this.items = this.buildSharedForm();

        // build form-buttons
        this.buttons = this.buildSharedUI();

        this.on('beforerender',function(){
            Ext.getBody().mask('Loading form data...');},this);

        this.on('render',function(){
            Ext.getBody().unmask();},this,{delay:100});

        ETFW.DHCP.Sharednetwork_Form.superclass.initComponent.call(this);

    }
    ,buildSharedUI:function(){
        return [{
                text: 'Create',
                ref: '../saveBtn',
                handler: this.onCreateShared,
                scope: this
            },
            {
                text: 'Delete',
                ref: '../delBtn',
                hidden:true,
                handler:function(){
                    Ext.MessageBox.show({
                        title:'Delete shared network',
                        msg: 'You are about to delete this shared network. <br />Are you sure you want to delete?',
                        buttons: Ext.MessageBox.YESNOCANCEL,
                        fn: function(btn){

                            if(btn=='yes'){
                                var form_values = this.getForm().getValues();
                                var uuid = form_values['uuid'];
                                if(uuid) this.fireEvent('deleteShared',uuid);
                            }

                        },
                        scope:this,
                        icon: Ext.MessageBox.QUESTION
                    });
                },
                scope: this
            }];

    }
    ,buildSharedForm:function(){


        this.hosts_directly = new Ext.ux.Multiselect({
            fieldLabel:"<?php echo $sharedLbls['hosts']?>",
            valueField:"uuid",
            displayField:"host",
            height:80,
            name:'hosts',
            allowBlank:true,
            store :new Ext.data.Store({
                reader: new Ext.data.JsonReader({
                    root:'data',
                    fields:['uuid','host']
                })
            })
        });

        this.groups_directly = new Ext.ux.Multiselect({
            fieldLabel:"<?php echo $sharedLbls['groups']?>",
            valueField:"uuid",
            displayField:"hosts_count",
            height:80,
            name:'groups',
            allowBlank:true,
            store:new Ext.data.Store({
                reader: new Ext.data.JsonReader({
                    root:'data',
                    fields:['uuid','hosts_count']
                })
            })
        });


        this.subnets_directly = new Ext.ux.Multiselect({
            fieldLabel:"<?php echo $sharedLbls['subnets']?>",
            valueField:"uuid",
            displayField:"subnet",
            height:80,
            name:'subnets',
            allowBlank:true,
            store:new Ext.data.Store({
                reader: new Ext.data.JsonReader({
                    root:'data',
                    fields:['uuid','subnet']
                })
            })
        });

        return [{
            defaultType:'textfield'
            ,monitorValid:true
            ,autoScroll:true
            // ,buttonAlign:'right'
            ,items:[
                {
                    xtype:'fieldset',
                    labelWidth:130,
                    border:false,
                    defaults:{bodyStyle:'padding-top:3px;'},
                    title: 'Shared Network details',
                    items :[
                        {xtype:'hidden',name:'uuid'},
                        {xtype:'textfield',width:200,name:'lastcomment',fieldLabel:'<?php echo $sharedLbls['lastcomment']?>'},
                        {
                            layout:'table',
                            layoutConfig: {columns: 2},
                            defaults:{layout:'form'},
                            items: [{width:410,items:[{xtype:'textfield',name:'name',fieldLabel:'<?php echo $sharedLbls['name']?>'}]},
                                // 2nd col
                                {bodyStyle:'padding-left:30px;',
                                    items:[
                                        {
                                            layout:'table',
                                            bodyStyle:'padding-top:3px',
                                            layoutConfig: {columns: 4},
                                            defaults:{layout:'form',bodyStyle:'padding-left:5px;'},
                                            items: [
                                                {bodyStyle:'padding:0px;',items:[{xtype:'radio', fieldLabel:'<?php echo $sharedLbls['default-lease-time']?>',name:'default-lease-time-src',boxLabel:'Default',checked:true,inputValue: 0}]},
                                                {items:[{xtype:'radio', fieldLabel:'',hideLabel:true,name:'default-lease-time-src',boxLabel:'',inputValue: 1}]},
                                                {items:[{xtype:'textfield',fieldLabel:'',name:'default-lease-time',hideLabel:true}]},
                                                {items:[{xtype:'displayfield',value:'secs',fieldLabel:'',hideLabel:true}]}
                                            ]
                                        }
                                    ]}// end 2nd col
                            ]
                        },// end layout 2 col
                        ETFW.DHCP.Data_Form_DefaultItems,
                        //Server is authoritative for this subnet?
                        {
                            layout:'table',
                            layoutConfig: {columns: 2},
                            defaults:{layout:'form',height:40,bodyStyle:'padding-left:5px;'},
                            items: [
                                {bodyStyle:'padding:0px;',items:[{xtype:'radio', fieldLabel:'<?php echo $sharedLbls['authoritative']?>',labelSeparator:'?',name:'authoritative',boxLabel:'Yes',inputValue: 1}]},
                                {items:[{xtype:'radio', hideLabel:true,name:'authoritative',boxLabel:'Default(No)',checked:true,inputValue: 0}]}
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
            ]
        }];

    }
    ,reset:function(){
        this.remoteLoadData();
    }
    ,loadSharedRecord:function(rec){


        this.saveBtn.setText('Save');
        this.saveBtn.setHandler(this.onUpdateShared,this);
        this.delBtn.show();


        var form_values = this.getForm().getValues();

        //check if '-src' fields are to be selected
        for(var data in form_values){
            if(data.indexOf('-src')!=-1){
                var field = data.replace('-src','');
                if(rec.data[field]) rec.data[data] = 1;
            }
        }

        if(Ext.isEmpty(rec.data['authoritative'],true)) rec.data['authoritative'] = 0;
        else rec.data['authoritative'] = 1;

        this.getForm().loadRecord(rec);

        this.remoteLoadData(rec);

    }
    //load remote data to populate fields on display
    ,remoteLoadData:function(rec){


        this.load({
            url:this.url,
            waitMsg:'Loading...',
            params:{id:this.service_id,method:'list_all'},
            success: function ( form, action ) {
                var result = action.result;
                var data = result.data;

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
                }

            }
            ,scope:this
        });


    }
    ,getDataSubmit:function(){

        var send_data = new Object();
        var form_values = this.getForm().getValues();

        //hosts directly....
        var hosts_values = this.hosts_directly.getValue();
        var hosts_values_array = hosts_values.split(',');
        var hosts = [];

        for(var j=0,jlen=hosts_values_array.length;j<jlen;j++){

            if(hosts_values_array[j])
                hosts.push({"uuid":hosts_values_array[j]});


        }
        delete form_values['hosts'];
        send_data['hosts'] = hosts;

        //groups directly....
        var groups_values = this.groups_directly.getValue();
        var groups_values_array = groups_values.split(',');
        var groups = [];

        for(var j=0,jlen=groups_values_array.length;j<jlen;j++){

            if(groups_values_array[j])
                groups.push({"uuid":groups_values_array[j]});


        }
        delete form_values['groups'];
        send_data['groups'] = groups;

        //subnets directly...
        var subnets_values = this.subnets_directly.getValue();
        var subnets_values_array = subnets_values.split(',');
        var subnets = [];

        for(var j=0,jlen=subnets_values_array.length;j<jlen;j++){

            if(subnets_values_array[j])
                subnets.push({"uuid":subnets_values_array[j]});

        }
        delete form_values['subnets'];
        send_data['subnets'] = subnets;


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
            default:
                break;
        }
        delete form_values['client-updates'];

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
            default:
                break;

        }
        delete form_values['unknown-clients'];

        if(allow_array.length>0) send_data['allow'] = allow_array;
        if(deny_array.length>0) send_data['deny'] = deny_array;
        if(ignore_array.length>0) send_data['ignore'] = ignore_array;

        //end gather allow/deny data.....


        for(var field in form_values){

            if(field && field.indexOf('-src')==-1 && !Ext.isEmpty(form_values[field])){

                if(form_values[field+'-src']){
                    var field_src = form_values[field+'-src'];
                    delete form_values[field+'-src'];

                    if(field_src==1) send_data[field] = form_values[field];

                }else{
                    //check if item is auto-generated..only chose defined!
                    if(field.indexOf('ext-comp-')==-1) send_data[field] = form_values[field];
                }

            }
        }

        return send_data;

    }
    ,onCreateShared:function(){

        var send_data = this.getDataSubmit();

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
                }// on request complete hide message
                ,requestcomplete:function(){Ext.MessageBox.hide();}
                ,requestexception:function(c,r,o){Ext.Ajax.fireEvent('requestexception',c,r,o);}
            }
        });// end conn

        conn.request({
            url: this.url,
            params:{id:this.service_id,method:'add_sharednetwork',params:Ext.encode(send_data)},
            // everything ok...
            success: function(resp,opt){
                this.fireEvent('createdShared',this);
                var msg = 'Shared network successfully added';
                Ext.ux.Logger.info(msg);
                View.notify({html:msg});

            },scope:this
        });// END Ajax request

    }
    ,onUpdateShared:function(){

        var send_data = this.getDataSubmit();

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
                }// on request complete hide message
                ,requestcomplete:function(){Ext.MessageBox.hide();}
                ,requestexception:function(c,r,o){Ext.Ajax.fireEvent('requestexception',c,r,o);}
            }
        });// end conn


        conn.request({
            url: this.url,
            params:{id:this.service_id,method:'set_sharednetwork',params:Ext.encode(send_data)},
            // everything ok...
            success: function(resp,opt){
                this.fireEvent('updatedShared',this);
                var msg = 'Shared network edited successfully';
                Ext.ux.Logger.info(msg);
                View.notify({html:msg});
            },scope:this
        });// END Ajax request

    }

});


ETFW.DHCP.Host_Form = Ext.extend(Ext.form.FormPanel, {

    border:false
    ,frame:true
    ,autoScroll:true
    ,bodyStyle:'padding-top:10px'
    ,url:<?php echo json_encode(url_for('etfw/json'))?>
    ,initComponent:function() {

        this.items = this.buildHostForm();

        // build form-buttons
        this.buttons = this.buildHostUI();

        this.on('beforerender',function(){
            Ext.getBody().mask('Loading form data...');},this);

        this.on('render',function(){
            Ext.getBody().unmask();},this,{delay:100});

        ETFW.DHCP.Host_Form.superclass.initComponent.call(this);

    }
    ,buildHostUI:function(){
        return [{
                text: 'Create',
                ref: '../saveBtn',
                handler: this.onCreateHost,
                scope: this
            },
            {
                text: 'Delete',
                ref: '../delBtn',
                hidden:true,
                handler:function(){
                    Ext.MessageBox.show({
                        title:'Delete host',
                        msg: 'You are about to delete this host. <br />Are you sure you want to delete?',
                        buttons: Ext.MessageBox.YESNOCANCEL,
                        fn: function(btn){

                            if(btn=='yes'){
                                var form_values = this.getForm().getValues();
                                var uuid = form_values['uuid'];
                                if(uuid) this.fireEvent('deleteHost',uuid);
                            }

                        },
                        scope:this,
                        icon: Ext.MessageBox.QUESTION
                    });
                },
                scope: this
            }];

    }
    ,buildHostForm:function(){



        this.hardwareCombo = new Ext.form.ComboBox({

            name:'hardware_src'
            ,fieldLabel:'<?php echo $hostLbls['hardware']?>'
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
            fieldLabel:'<?php echo $hostLbls['parent']?>'
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

        });

        this.host_assign = new Ext.ux.Multiselect({
            valueField:"uuid",
            displayField:"value",
            minLength:1,
            maxLength:1,
            width:200,
            name:'host_assign',
            height:80,
            allowBlank:true,
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



        return [{
            defaultType:'textfield'
            ,monitorValid:true
            ,autoScroll:true
            // ,buttonAlign:'right'
            ,items:[
                {
                    xtype:'fieldset',
                    labelWidth:130,
                    border:false,
                    defaults:{bodyStyle:'padding-top:3px;'},
                    title: 'Host details',
                    items :[
                        {xtype:'hidden',name:'uuid'},
                        {xtype:'textfield',width:200,name:'lastcomment',fieldLabel:'<?php echo $hostLbls['lastcomment']?>'},
                        {
                            layout:'table',
                            layoutConfig: {columns: 2},
                            bodyStyle:'padding-top:3px;',
                            defaults:{layout:'form'},
                            items: [{width:410,items:[{xtype:'textfield',name:'host',fieldLabel:'<?php echo $hostLbls['host']?>'},

                                        {
                                            layout:'table',
                                            defaults:{bodyStyle:'padding-top:3px;'},
                                            layoutConfig: {columns:2},
                                            items:[
                                                {
                                                    labelAlign:'left',
                                                    layout:'form',
                                                    items:this.hardwareCombo
                                                },
                                                {
                                                    labelAlign:'left',
                                                    layout:'form',
                                                    items:[{xtype:'textfield',name:'hardware',hideLabel:true}]
                                                }
                                            ]
                                        }

                                    ]},
                                {labelWidth:60,bodyStyle:'padding-left:30px',items:[

                                        {
                                            layout:'table',
                                            bodyStyle:'padding-top:3px',
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
                            items: [{width:410,items:[{xtype:'textfield',name:'fixed-address',fieldLabel:'<?php echo $hostLbls['fixed-address']?>'}]},
                                // 2nd col
                                {bodyStyle:'padding-left:30px;',
                                    items:[
                                        {
                                            layout:'table',
                                            bodyStyle:'padding-top:3px',
                                            layoutConfig: {columns: 4},
                                            defaults:{layout:'form',bodyStyle:'padding-left:5px;'},
                                            items: [
                                                {bodyStyle:'padding:0px;',items:[{xtype:'radio', fieldLabel:'<?php echo $hostLbls['default-lease-time']?>',name:'default-lease-time-src',boxLabel:'Default',checked:true,inputValue: 0}]},
                                                {items:[{xtype:'radio', hideLabel:true,name:'default-lease-time-src',boxLabel:'',inputValue: 1}]},
                                                {items:[{xtype:'numberfield',name:'default-lease-time',hideLabel:true}]},
                                                {items:[{xtype:'displayfield',value:'secs',fieldLabel:'',hideLabel:true}]}
                                            ]
                                        }
                                    ]}// end 2nd col
                            ]
                        },// end layout 2 col
                        ETFW.DHCP.Data_Form_DefaultItems
                    ]
                }
            ]
        }];

    }
    ,reset:function(){
        this.remoteLoadData();
    }
    ,loadHostRecord:function(rec){


        this.saveBtn.setText('Save');
        this.saveBtn.setHandler(this.onUpdateHost,this);
        this.delBtn.show();


        var form_values = this.getForm().getValues();

        //check if '-src' fields are to be selected
        for(var data in form_values){
            if(data.indexOf('-src')!=-1){
                var field = data.replace('-src','');
                if(rec.data[field]) rec.data[data] = 1;
            }
        }

        var hardware = rec.data['hardware'];
        if(hardware){
            var hardware_data = hardware.split(' ');
            rec.data['hardware_src'] = hardware_data[0];
            rec.data['hardware'] = hardware_data[1];
        }

        this.getForm().loadRecord(rec);

        this.remoteLoadData(rec);

    }
    //load remote data to populate fields on display
    ,remoteLoadData:function(rec){


        this.load({
            url:this.url,
            waitMsg:'Loading...',
            params:{id:this.service_id,method:'list_all'},
            success: function ( form, action ) {
                var result = action.result;
                var data = result.data;

                var hosts_info = data['assigned_to'];

                this.host_assign.store.loadData(hosts_info);

                if(rec && rec.data['parent-type']){
                    this.host_assign_combo.setValue(rec.data['parent-type']);
                    this.host_assign_combo.fireEvent("select",this.host_assign_combo);

                    this.host_assign.setValue(rec.data['parent-uuid']);
                }


            }
            ,scope:this
        });


    }
    ,getDataSubmit:function(){

        var send_data = new Object();
        var form_values = this.getForm().getValues();


        var parent_uuid = form_values['host_assign'];
        if(parent_uuid){

            send_data['parent'] = new Object();
            send_data['parent']['uuid'] = parent_uuid;

        }else send_data['parent'] = new Object();
        delete form_values['host_assign'];


        if(form_values['hardware']){
            var hardware = this.hardwareCombo.getValue();
            if(hardware) send_data['hardware'] = hardware+' '+form_values['hardware'];
            delete form_values['hardware'];
        }
        delete form_values['hardware_src'];


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
            default:
                break;
        }
        delete form_values['client-updates'];

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
            default:
                break;

        }
        delete form_values['unknown-clients'];

        if(allow_array.length>0) send_data['allow'] = allow_array;
        if(deny_array.length>0) send_data['deny'] = deny_array;
        if(ignore_array.length>0) send_data['ignore'] = ignore_array;

        //end gather allow/deny data.....


        for(var field in form_values){

            if(field && field.indexOf('-src')==-1 && !Ext.isEmpty(form_values[field])){

                if(form_values[field+'-src']){
                    var field_src = form_values[field+'-src'];
                    delete form_values[field+'-src'];

                    if(field_src==1) send_data[field] = form_values[field];

                }else{
                    //check if item is auto-generated..only chose defined!
                    if(field.indexOf('ext-comp-')==-1) send_data[field] = form_values[field];
                }

            }
        }

        return send_data;

    }
    ,onCreateHost:function(){

        var send_data = this.getDataSubmit();

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
                }// on request complete hide message
                ,requestcomplete:function(){Ext.MessageBox.hide();}
                ,requestexception:function(c,r,o){Ext.Ajax.fireEvent('requestexception',c,r,o);}
            }
        });// end conn

        conn.request({
            url: this.url,
            params:{id:this.service_id,method:'add_host',params:Ext.encode(send_data)},
            // everything ok...
            success: function(resp,opt){
                this.fireEvent('createdHost',this);
                var msg = 'Host successfully added';
                Ext.ux.Logger.info(msg);
                View.notify({html:msg});

            },scope:this
        });// END Ajax request

    }
    ,onUpdateHost:function(){

        var send_data = this.getDataSubmit();

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
                }// on request complete hide message
                ,requestcomplete:function(){Ext.MessageBox.hide();}
                ,requestexception:function(c,r,o){Ext.Ajax.fireEvent('requestexception',c,r,o);}
            }
        });// end conn

        conn.request({
            url: this.url,
            params:{id:this.service_id,method:'set_host',params:Ext.encode(send_data)},
            // everything ok...
            success: function(resp,opt){
                this.fireEvent('updatedHost',this);
                var msg = 'Host edited successfully';
                Ext.ux.Logger.info(msg);
                View.notify({html:msg});
            },scope:this
        });// END Ajax request

    }

});



ETFW.DHCP.Group_Form = Ext.extend(Ext.form.FormPanel, {

    border:false
    ,frame:true
    ,autoScroll:true
    ,bodyStyle:'padding-top:10px'
    ,url:<?php echo json_encode(url_for('etfw/json'))?>
    ,initComponent:function() {

        this.items = this.buildGroupForm();

        // build form-buttons
        this.buttons = this.buildGroupUI();

        this.on('beforerender',function(){
            Ext.getBody().mask('Loading form data...');},this);

        this.on('render',function(){
            Ext.getBody().unmask();},this,{delay:100});

        ETFW.DHCP.Group_Form.superclass.initComponent.call(this);

    }
    ,buildGroupUI:function(){
        return [{
                text: 'Create',
                ref: '../saveBtn',
                handler: this.onCreateGroup,
                scope: this
            },
            {
                text: 'Delete',
                ref: '../delBtn',
                hidden:true,
                handler:function(){
                    Ext.MessageBox.show({
                        title:'Delete host group',
                        msg: 'You are about to delete this host group. <br />Are you sure you want to delete?',
                        buttons: Ext.MessageBox.YESNOCANCEL,
                        fn: function(btn){

                            if(btn=='yes'){
                                var form_values = this.getForm().getValues();
                                var uuid = form_values['uuid'];
                                if(uuid) this.fireEvent('deleteGroup',uuid);
                            }

                        },
                        scope:this,
                        icon: Ext.MessageBox.QUESTION
                    });
                },
                scope: this
            }];

    }
    ,buildGroupForm:function(){


        this.group_assign_combo = new Ext.form.ComboBox({
            fieldLabel:'<?php echo $groupLbls['parent']?>'
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
            name:'group_assign',
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
            fieldLabel:"<?php echo $groupLbls['hosts']?>",
            valueField:"uuid",
            displayField:"host",
            height:80,
            name:'hosts',
            allowBlank:true,
            store :new Ext.data.Store({
                reader: new Ext.data.JsonReader({
                    root:'data',
                    fields:['uuid','host']
                })
            })
        });



        return [{
            defaultType:'textfield'
            ,monitorValid:true
            ,autoScroll:true
            ,items:[
                {
                    xtype:'fieldset',
                    labelWidth:130,
                    border:false,
                    defaults:{bodyStyle:'padding-top:3px;'},
                    title: 'Group details',
                    items :[
                        {xtype:'hidden',name:'uuid'},
                        {xtype:'textfield',width:200,name:'lastcomment',fieldLabel:'<?php echo $groupLbls['lastcomment']?>'},
                        {
                            layout:'table',
                            bodyStyle:'padding-top:3px;',
                            layoutConfig: {columns: 2},
                            defaults:{layout:'form'},
                            items: [{width:410,items:[this.hosts_directly]},
                                {labelWidth:60,bodyStyle:'padding-left:30px',items:[

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
                            items: [
                                {width:410,items:[
                                        {
                                            layout:'table',
                                            bodyStyle:'padding-top:3px',
                                            layoutConfig: {columns: 3},
                                            defaults:{layout:'form',bodyStyle:'padding-left:5px;',height:40},
                                            items: [
                                                {bodyStyle:'padding:0px;',items:[{xtype:'radio', fieldLabel:'<?php echo $groupLbls['use-host-decl-names']?>',labelSeparator:'?',name:'use-host-decl-names',boxLabel:'Yes',inputValue: 'on'}]},
                                                {items:[{xtype:'radio', hideLabel:true,name:'use-host-decl-names',boxLabel:'No',inputValue: 'off'}]},
                                                {items:[{xtype:'radio', hideLabel:true,name:'use-host-decl-names',checked:true,boxLabel:'Default',inputValue: ''}]}
                                            ]
                                        }
                                    ]},
                                // 2nd col
                                {bodyStyle:'padding-left:30px;',
                                    items:[
                                        {
                                            layout:'table',
                                            bodyStyle:'padding-top:3px',
                                            layoutConfig: {columns: 4},
                                            defaults:{layout:'form',bodyStyle:'padding-left:5px;'},
                                            items: [
                                                {bodyStyle:'padding:0px;',items:[{xtype:'radio', fieldLabel:'<?php echo $groupLbls['default-lease-time']?>',name:'default-lease-time-src',boxLabel:'Default',checked:true,inputValue: 0}]},
                                                {items:[{xtype:'radio', hideLabel:true,name:'default-lease-time-src',boxLabel:'',inputValue: 1}]},
                                                {items:[{xtype:'numberfield',name:'default-lease-time',hideLabel:true}]},
                                                {items:[{xtype:'displayfield',value:'secs',fieldLabel:'',hideLabel:true}]}
                                            ]
                                        }
                                    ]}// end 2nd col
                            ]
                        },// end layout 2 col
                        ETFW.DHCP.Data_Form_DefaultItems
                    ]
                }
            ]
        }]; // eo config object

    }
    ,reset:function(){
        this.remoteLoadData();
    }
    ,loadGroupRecord:function(rec){


        this.saveBtn.setText('Save');
        this.saveBtn.setHandler(this.onUpdateGroup,this);
        this.delBtn.show();


        var form_values = this.getForm().getValues();

        //check if '-src' fields are to be selected
        for(var data in form_values){
            if(data.indexOf('-src')!=-1){
                var field = data.replace('-src','');
                if(rec.data[field]) rec.data[data] = 1;
            }
        }

        this.getForm().loadRecord(rec);

        this.remoteLoadData(rec);

    }
    //load remote data to populate fields on display
    ,remoteLoadData:function(rec){


        this.load({
            url:this.url,
            waitMsg:'Loading...',
            params:{id:this.service_id,method:'list_all'},
            success: function ( form, action ) {
                var result = action.result;
                var data = result.data;

                var groups_info = data['assigned_to'];
                this.group_assign.store.loadData(groups_info);

                if(rec){

                    var hosts_info = data['group_hosts'][rec.data['uuid']];

                    this.hosts_directly.store.loadData(hosts_info);

                    if(rec.data['parent-type']){
                        this.group_assign_combo.setValue(rec.data['parent-type']);
                        this.group_assign_combo.fireEvent("select",this.group_assign_combo);

                        this.group_assign.setValue(rec.data['parent-uuid']);
                    }

                    this.hosts_directly.setValue(rec.data['hosts']);

                }else{
                    var hosts_info = data['group_hosts'][''];
                    if(hosts_info) this.hosts_directly.store.loadData(hosts_info);
                }



            }
            ,scope:this
        });


    }
    ,getDataSubmit:function(){

        var send_data = new Object();
        var form_values = this.getForm().getValues();


        var hosts_values = form_values['hosts'];
        var hosts_values_array = hosts_values.split(',');
        var hosts = [];

        for(var i=0,len=hosts_values_array.length;i<len;i++){

            if(hosts_values_array[i])
                hosts.push({"uuid":hosts_values_array[i]});
        }
        delete form_values['hosts'];
        send_data['hosts'] = hosts;



        var parent_uuid = form_values['group_assign'];
        if(parent_uuid){

            send_data['parent'] = new Object();
            send_data['parent']['uuid'] = parent_uuid;

        }else send_data['parent'] = new Object();
        delete form_values['group_assign'];






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
            default:
                break;
        }
        delete form_values['client-updates'];

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
            default:
                break;

        }
        delete form_values['unknown-clients'];

        if(allow_array.length>0) send_data['allow'] = allow_array;
        if(deny_array.length>0) send_data['deny'] = deny_array;
        if(ignore_array.length>0) send_data['ignore'] = ignore_array;

        //end gather allow/deny data.....


        for(var field in form_values){

            if(field && field.indexOf('-src')==-1 && !Ext.isEmpty(form_values[field])){

                if(form_values[field+'-src']){
                    var field_src = form_values[field+'-src'];
                    delete form_values[field+'-src'];

                    if(field_src==1) send_data[field] = form_values[field];

                }else{
                    //check if item is auto-generated..only chose defined!
                    if(field.indexOf('ext-comp-')==-1) send_data[field] = form_values[field];
                }

            }
        }

        return send_data;

    }
    ,onCreateGroup:function(){

        var send_data = this.getDataSubmit();

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
                }// on request complete hide message
                ,requestcomplete:function(){Ext.MessageBox.hide();}
                ,requestexception:function(c,r,o){Ext.Ajax.fireEvent('requestexception',c,r,o);}
            }
        });// end conn

        conn.request({
            url: this.url,
            params:{id:this.service_id,method:'add_group',params:Ext.encode(send_data)},
            // everything ok...
            success: function(resp,opt){
                this.fireEvent('createdHost',this);
                var msg = 'Host group successfully added';
                Ext.ux.Logger.info(msg);
                View.notify({html:msg});

            },scope:this
        });// END Ajax request

    }
    ,onUpdateGroup:function(){

        var send_data = this.getDataSubmit();

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
                }// on request complete hide message
                ,requestcomplete:function(){Ext.MessageBox.hide();}
                ,requestexception:function(c,r,o){Ext.Ajax.fireEvent('requestexception',c,r,o);}
            }
        });// end conn

        conn.request({
            url: this.url,
            params:{id:this.service_id,method:'set_group',params:Ext.encode(send_data)},
            // everything ok...
            success: function(resp,opt){
                this.fireEvent('updatedGroup',this);
                var msg = 'Host group edited successfully';
                Ext.ux.Logger.info(msg);
                View.notify({html:msg});
            },scope:this
        });// END Ajax request

    }

});


/*
 *
 * BBAR
 *
 */

//edit configfile
ETFW.DHCP.ConfigEditor_Form = Ext.extend(Ext.form.FormPanel, {

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
        ETFW.DHCP.ConfigEditor_Form.superclass.initComponent.apply(this, arguments);



    } // eo function initComponent
    ,onRender:function(){
          // call parent
             ETFW.DHCP.ConfigEditor_Form.superclass.onRender.apply(this, arguments);

         // set wait message target
            this.getForm().waitMsgTarget = this.getEl();

        // loads form after initial layout
           this.on('afterlayout', this.onLoad, this, {single:true});
    }
    ,onLoad:function(){
        this.load({
            url:this.url
            ,waitMsg:'Loading...'
            ,params:{id:this.service_id,method:'get_configfile_content'}
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
            params:{id:this.service_id,method:'save_configfile_content',params:Ext.encode(send_data)},
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
ETFW.DHCP.ListLeases_Grid = Ext.extend(Ext.grid.GridPanel, {

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
        ETFW.DHCP.ListLeases_Grid.superclass.initComponent.apply(this, arguments);

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
        ETFW.DHCP.ListLeases_Grid.superclass.onRender.apply(this, arguments);

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
            params:{id:this.service_id,method:'del_leases',params:Ext.encode({'indexes':leases})},
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
Ext.reg('etfw_dhcp_leasesgrid', ETFW.DHCP.ListLeases_Grid);






ETFW.DHCP.Bbar = function(service_id,network_dispatcher) {

    this.service_id = service_id;
    this.network_dispatcher = network_dispatcher;

    this.url = <?php echo json_encode(url_for('etfw/json'))?>;

    ETFW.DHCP.Bbar.superclass.constructor.call(this, {
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

                        var viewerSize = Ext.getBody().getViewSize();
                        var windowHeight = viewerSize.height * 0.95;
                        windowHeight = Ext.util.Format.round(windowHeight,0);
                        windowHeight = (windowHeight > 950) ? 950 : windowHeight;

                        // create and show window
                        var win = new Ext.Window({
                                title:'Client Options'
                                ,layout:'fit'
                                ,width:920
                                ,height:windowHeight
                                ,modal:true
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
                        this.fireEvent('showZones');
                    }
                    ,scope:this
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

                }]}/*,{
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
                }]}*/,{
            xtype: 'buttongroup',
            title:'Apply current configuration',
            columns: 1,
            height:60,
            defaults: {
                scale: 'small', width:170
            },
            items: [{
                    text: 'Apply configuration',
                    tooltip:'Click this button to apply current changes configuration on the DHCP server'
                    ,action:'apply_config'
                    ,handler:this.applyConfiguration
                    ,scope: this
                    /*,handler: function(){
                                alert('Start');
                    }*/
                }]}
                ]
    });


}//eof

// define public methods
Ext.extend(ETFW.DHCP.Bbar, Ext.Toolbar,{
    buildEditor:function(){

        return new ETFW.DHCP.ConfigEditor_Form({service_id:this.service_id});
    },
    buildNetworkInterface:function(){

        return new ETFW.DHCP.NetworkInterface_Form({service_id:this.service_id,network_dispatcher:this.network_dispatcher});
    },
    buildClientOptions:function(){

        return new ETFW.DHCP.ClientOptions_Form({title:'Client Options for all networks, hosts and groups',all:true,service_id:this.service_id});
    },
    buildListLeases:function(){

        var params = {'all':1};
        var item = {
                    url:<?php echo json_encode(url_for('etfw/json'))?>,
                    service_id:this.service_id,
                    baseParams:{id:this.service_id,method:'list_leases',params:Ext.encode(params)},xtype:'etfw_dhcp_leasesgrid'
                    };
        return item;
    }
    ,applyConfiguration:function(b,e){
        var conn = new Ext.data.Connection({
            listeners:{
                // wait message.....
                beforerequest:function(){
                    Ext.MessageBox.show({
                        title: 'Please wait',
                        msg: 'Applying...',
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
            params:{id:this.service_id,method:b.action},
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

                var msg = b.text+' successfully';
                Ext.ux.Logger.info(msg);
                View.notify({html:msg});
                if(b.action=='revert_config' || b.action=='reset_config' || b.action=='activate_onboot' || b.action=='deactivate_onboot'){

                    this.reload();                    
                }

            },scope:this
        });// END Ajax request
    }

});



ETFW.DHCP.Main = function(config){

    Ext.apply(this,config);

    var service_id = this.service_id;
    var network_dispatcher = this.network_dispatcher;

    var etfw_dhcp_networks = new ETFW.DHCP.Networks.Main(service_id);
    var etfw_dhcp_hosts = new ETFW.DHCP.Hosts.Main(service_id);
    var etfw_dhcp_zones = new ETFW.DHCP.Zones.Main(service_id);
    var etfw_dhcp_bbar = new ETFW.DHCP.Bbar(service_id,network_dispatcher);

    etfw_dhcp_bbar.on('showZones',function(){
        var node = Ext.getCmp('etfw-dhcp-modulesTree-'+service_id).getNodeById(etfw_dhcp_zones.id);
        node.fireEvent("click",node);
    });

    var dhcp_modulesData = [
        {
            text:'Modules',
            expanded: true,
            children:[
                {
                    id:etfw_dhcp_networks.id,
                    text:etfw_dhcp_networks.title,
                    item:etfw_dhcp_networks,
                    leaf:true
                },
                {
                    text:etfw_dhcp_hosts.title,
                    item:etfw_dhcp_hosts,
                    leaf:true
                },
                {
                    id:etfw_dhcp_zones.id,
                    text:etfw_dhcp_zones.title,
                    item:etfw_dhcp_zones,
                    leaf:true
                }
           ]
    }];

    var dhcp_modulesTree = new Ext.tree.TreePanel({
        id:'etfw-dhcp-modulesTree-'+service_id,
        region:'west',
        title:'Modules',
        split:true,
        tbar: [
            {text:'DHCP Wizard'
             ,iconCls:'wizard'
             ,url:<?php echo json_encode(url_for('etfw/ETFW_wizard?tpl=dhcp&sid='))?>+this.server_id
             ,handler: View.clickHandler
            }
        ],
        useSplitTips: true,
        width: 200,
        margins: '3 0 3 3',
        cmargins: '3 3 3 3',
        minSize: 155,
        maxSize: 400,
        collapsible: true,
        autoScroll: true,
        rootVisible: false,
        lines: false,
        singleExpand: true,
        useArrows: true
        ,root: new Ext.tree.AsyncTreeNode({
            draggable:false,
            children: dhcp_modulesData
        })
    });

    dhcp_modulesTree.on({
        reload:function(){
            var contentPanel = Ext.getCmp('etfw-dhcp-contentpanel-'+service_id);
            var modules_node = this.getRootNode().childNodes[0]; // get 'Modules' node
            var modules_children = modules_node.childNodes;

            for(var i=0,len = modules_children.length;i<len;i++)
            {

                var child_node = modules_children[i];
                if(contentPanel.get(child_node.attributes.item.id))
                    child_node.attributes.item.reload();
            }

        },
        click:function(n){

            var sn = this.selModel.selNode || {}; // selNode is null on initial selection
            if(n.leaf && n.id != sn.id){  // ignore clicks on folders and currently selected node
                
                var contentPanel = Ext.getCmp('etfw-dhcp-contentpanel-'+service_id);

                if(!contentPanel.get(n.attributes.item.id)){                    
                    Ext.getBody().mask('Loading ETFW dhcp data...');                    
                    (function(){
                        contentPanel.add(n.attributes.item);
                        contentPanel.layout.setActiveItem(n.attributes.item.id);
                        Ext.getBody().unmask();
                    }).defer(100);

                }else contentPanel.layout.setActiveItem(n.attributes.item.id);
            }
        },
        load:{single:true,delay:100,fn:function(){
            var modules_node = this.getRootNode().childNodes[0]; // get 'Modules' node
            var modules_children = modules_node.childNodes;
            (modules_children[0]).fireEvent("click",modules_children[0]);
        }}
    });


    var dhcpContentPanel = {
        id:'etfw-dhcp-contentpanel-'+service_id,
        layout:'card',border:false,
        defaults:{border:false},
        items: []
    };


    ETFW.DHCP.Main.superclass.constructor.call(this, {
        layout:'border',
        border:false,
        defaults: {
            // collapsible: true,
            split: true,
            //border:false,
            bodyStyle: 'padding:0px'
        },
        items: [

                 dhcp_modulesTree,
                 {region:'center',
                    layout:'fit',
                    margins: '3 3 3 3',items:[dhcpContentPanel]}
                ,{region:'south',
                    title:'General options',
                    collapsible:true,
                    height:90,layout:'fit',
                    margins: '0 3 3 3',
                    cmargins: '3 3 3 3',
                    items:[etfw_dhcp_bbar]
                }
         ]
         ,listeners:{
            'reload':function(){
                var modulesPanel = this.items.get(0);
                modulesPanel.fireEvent('reload');
            }
        }
});


};

Ext.extend(ETFW.DHCP.Main, Ext.Panel,{});

    
</script>
