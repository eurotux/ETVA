<script>

Ext.ns('ETFW.Squid.Acl');


ETFW.Squid.Acl.AclTpl = function(config) {

    Ext.apply(this,config);


    Ext.apply(Ext.form.VTypes, {
        spinner_timerange : function(val, field) {
            if(field.form){
                var date = Date.parseDate(val,"h:i").getTime();
                if(!date){
                    return false;
                }

                if (field.startTimeField) {
                    var start = field.form.getForm().findField(field.startTimeField);
                    var start_value = start.getValue();

                    if(start_value){
                        var start_date = Date.parseDate(start_value,"h:i").getTime();
                        if(start_date>date) return false;
                        else return true;
                    }
                }
                else if (field.endTimeField) {
                    var end = field.form.getForm().findField(field.endTimeField);
                    var end_value = end.getValue();

                    if(end_value){
                        var end_date = Date.parseDate(end_value,"h:i").getTime();
                        if(end_date<date) return false;
                        else return true;
                    }
                }
            }
            return true;
        }
    });

    switch(this.type){
        case 'browser':
                        this.loadBrowserForm();
                        break;
        case 'proto':
                        this.loadProtocolForm();
                        break;
        case 'port':
                        this.loadPortForm();
                        break;
        case 'srcdomain':
                        this.loadHostnameForm('Client Hostname ACL');
                        break;
        case 'dstdomain':
                        this.loadHostnameForm('Web Server Hostname ACL');
                        break;
        case 'ident':
                        this.loadRFC931Form();
                        break;
        case 'ident_regex':
                        this.loadRFC931RegexForm();
                        break;
        case 'arp':
                        this.loadEthernetForm();
                        break;
        case 'time':
                        this.loadTimeForm();
                        break;
        case 'max_user_ip':
                        this.loadMaxUserIpForm();
                        break;
        case 'maxconn':
                        this.loadMaxConnForm();
                        break;
        case 'dst':
                        this.loadAddressForm('Web Server Address ACL');
                        break;
        case 'dst_as':
                        this.loadAsForm('Dest AS Number ACL');
                        break;
        case 'src_as':
                        this.loadAsForm('Source AS Number ACL');
                        break;
        case 'src':
                        this.loadAddressForm('Client Address ACL');
                        break;
        case 'myip':
                        this.loadProxyIpForm();
                        break;
        case 'myport':
                        this.loadProxyPortForm();
                        break;
        case 'method':
                        this.loadMethodForm();
                        break;
        case 'external':
                        this.loadExternalForm();
                        break;
        case 'req_mime_type':
                        this.loadRequestmimeForm();
                        break;
        case 'rep_mime_type':
                        this.loadReplymimeForm();
                        break;
        case 'snmp_community':
                        this.loadSnmpcommunityForm();
                        break;
        case 'proxy_auth':
                        this.loadProxyAuthForm();
                        break;
        case 'proxy_auth_regex':
                        this.loadProxyAuthRegexForm();
                        break;
        case 'dstdom_regex':
                        this.loadRegexForm('Web Server Regexp ACL');
                        break;
        case 'srcdom_regex':
                        this.loadRegexForm('Client Regexp ACL');
                        break;
        case 'urlpath_regex':
                        this.loadRegexForm('URL Path Regexp ACL');
                        break;
        case 'url_regex':
                        this.loadRegexForm('URL Regexp ACL');
                        break;

        default:
                        break;
    }


    this.savebtn = new Ext.Button({text: 'Add',handler:this.onSave,scope:this});


    ETFW.Squid.Acl.AclTpl.superclass.constructor.call(this, {
        // baseCls: 'x-plain',
        labelWidth: 140,
        bodyStyle:'padding-top:10px',
        url:<?php echo json_encode(url_for('etfw/json'))?>,
        defaultType: 'textfield',
        buttonAlign:'center',
        border:false,
        defaults:{border:false},
        items: [this.allFields]

        ,buttons: [this.savebtn]
    });

    this.on('show',this.reset,this);

};

Ext.extend(ETFW.Squid.Acl.AclTpl, Ext.form.FormPanel, {
    reset:function(){

        this.savebtn.setText('Add');
        this.getForm().reset();

        switch(this.type){
            case 'dst':
            case 'src':
            case 'myip':
                        this.address_grid.getStore().removeAll();
                        break;
            default:
                        break;
        }

    }
    ,joinAddrRecord:function(rec){
        var from_ip = rec.data['from_ip'];
        var to_ip = rec.data['to_ip'];
        var netmask = Ext.isEmpty(rec.data['netmask']) ? '' : '/'+rec.data['netmask'];
        var data = '';

        if(!Ext.isEmpty(from_ip)){
            //if from_ip
            if(!Ext.isEmpty(to_ip)) data = from_ip+'-'+to_ip+netmask;
            else data = from_ip+netmask;
        }

        return data;
    }
    ,onSave:function(){

        if (this.form.isValid()) {

            var alldata = this.form.getValues();
            var params = [];
            var method = 'set_acl';

            var send_data = new Object();
            if(!Ext.isEmpty(alldata['index'])){
                send_data['index'] = alldata['index'];
            }else{
                method = 'add_acl';
            }

            send_data['type'] = alldata['type'];
            send_data['name'] = alldata['name'];
            if(!Ext.isEmpty(alldata['deny_info_index']) || !Ext.isEmpty(alldata['deny_info_value'])) send_data['deny_info'] = [{'index':alldata['deny_info_index'],'val':alldata['deny_info_value']}];
            if(!Ext.isEmpty(alldata['ignore_case'])) params.push('-i');

            switch(this.type){
                case 'arp':
                case 'srcdomain':
                case 'dstdomain':
                case 'ident':
                case 'ident_regex':
                case 'srcdom_regex':
                case 'proxy_auth_regex':
                case 'urlpath_regex':
                case 'url_regex':
                            send_data['vals'] = alldata['vals'].replace(/[\n\r]+/g, ' ');
                            break;
                case 'proxy_auth':
                            if(alldata['authall']=='1')
                                send_data['vals'] = 'REQUIRED';
                            else send_data['vals'] = alldata['vals'].replace(/[\n\r]+/g, ' ');
                            break;
                case 'proto':
                case 'method':
                            var values = alldata['vals'];
                            var str_array = [];
                            if(typeof alldata['vals']=='string'){
                                send_data['vals'] = alldata['vals'];
                            }else{
                                for(var i=0, len = values.length;i<len;i++)
                                    str_array.push(values[i]);
                                send_data['vals'] = str_array.join(' ');
                            }
                            break;
                case 'time':
                            var dow = this.getForm().findField('dow');
                            var alldow = alldata['dowall'];
                            var alltime = alldata['timeall'];
                            var pass = [];

                            if(alldow==0){
                                var dow_values = dow.getValue();
                                dow_values = dow_values.replace(/,/g,'');
                                pass.push(dow_values);
                            }

                            if(alltime==0)
                                pass.push(alldata['start_time']+'-'+alldata['end_time']);
                            send_data['vals'] = pass.join(' ');


                            break;
                case 'dst':
                case 'src':
                case 'myip':
                            var ds_addrGrid = this.address_grid.getStore();
                            var total_addrGrid = ds_addrGrid.getCount();
                            var recs_addrGrid = [];

                            Ext.each(ds_addrGrid.getRange(0,total_addrGrid),function(e){
                                var data = this.joinAddrRecord(e);
                                recs_addrGrid.push(data);
                            },this);
                            send_data['vals'] = recs_addrGrid.join(' ')+'\n';
                            break;
                case 'external':
                            var vals = [];
                            if(alldata['file-src']==0) vals.push(alldata['vals']);
                            else params.push(alldata['vals']);

                            if(!Ext.isEmpty(alldata['args'])) vals.push(alldata['args']);
                            send_data['vals'] = vals.join(' ');
                            break;
                case 'max_user_ip':
                            var vals = [];
                            if(!Ext.isEmpty(alldata['strict_enforce'])) vals.push('-s');
                            vals.push(alldata['vals']);
                            send_data['vals'] = vals.join(' ');
                            break;
                default:
                            send_data['vals'] = alldata['vals'];
                            break;

            }

            if(alldata['file-src']==1){

                send_data['file'] = alldata['file'];

                switch(this.type){
                    case 'req_mime_type':
                    case 'rep_mime_type':
                    case 'browser':
                    case 'snmp_community':
                            send_data['filecontent'] = send_data['vals']+'\n';
                            break;
                    default:
                            var filecontent = send_data['vals'].replace(/\s+/g, '\n')+'\n';
                            filecontent = filecontent.replace(/\n+/g, '\n');
                            send_data['filecontent'] = filecontent;
                            break;
                }
                params.push('"'+alldata['file']+'"');
                send_data['vals'] = params.join(' ');

            }else{
                params.push(send_data['vals']);
                send_data['vals'] = params.join(' ');
            }


            var conn = new Ext.data.Connection({
                listeners:{
                    // wait message.....
                    beforerequest:function(){
                        Ext.MessageBox.show({
                            title: 'Please wait',
                            msg: 'Updating acl information...',
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
                params:{id:this.service_id,method:method,
                    params:Ext.encode(send_data)
                },
                failure: function(resp,opt){
                    var response = Ext.util.JSON.decode(resp.responseText);
                    Ext.MessageBox.alert('Error Message', response['info']);
                    Ext.ux.Logger.error(response['error']);

                },
                // everything ok...
                success: function(resp,opt){
                    var msg = 'Updated ACL '+send_data['name'];
                    Ext.ux.Logger.info(msg);
                    View.notify({html:msg});
                    this.ownerCt.fireEvent('reloadAcl');


                },scope:this
            });// END Ajax request


        } else{
            Ext.MessageBox.alert('error', 'Please fix the errors noted.');
        }



    }
    /*
    * build record [ip,ip,netmask] from from_ip/netmask to_ip/netmask
    *
    */
    ,buildAddrGridRecord:function(addr_values){
        var from_net = to_net = '';
        var from_ip = to_ip = netmask = '';
        var addr_pieces = addr_values.split('-',2);
        if(addr_pieces.length>1){
            from_net = addr_pieces[0];
            to_net = addr_pieces[1];

        }
        else
            from_net = addr_pieces[0];

        var from_addr = this.buildAddrGridAddress(from_net);
        var to_addr = this.buildAddrGridAddress(to_net);

        from_ip = from_addr[0];
        to_ip = to_addr[0];

        netmask = from_addr[1];
        if(Ext.isEmpty(netmask)) netmask = to_addr[1];

        var record = [from_ip,to_ip,netmask];
        return record;
    }
    /*
     * builds address ip/netmask
     */
    ,buildAddrGridAddress:function(data){
        var address = data.split('/',2);
        var ip = netmask = '';
        if(address.length>1){
               ip = address[0];
               netmask = address[1];
               }
               else{
               ip = address[0];
               }
        return [ip,netmask];
    }
    ,loadRecord:function(rec){

        if(rec.data['vals'].indexOf('-i ')!=-1)
            rec.data['ignore_case'] = '1';


        if(!Ext.isEmpty(rec.data['file'])){
            rec.data['vals'] = Ext.util.Format.htmlDecode(rec.data['filecontent']);
            rec.data['vals'] = rec.data['vals'].replace(/[\n]$/, '');
            rec.data['file-src'] = 1;
        }else rec.data['file-src'] = 0;

        rec.data['vals'] = rec.data['vals'].replace(/[\n\r]+/g, ' ');

        switch(this.type){
            case 'max_user_ip':
                        if(rec.data['vals'].indexOf('-s ')!=-1)
                            rec.data['strict_enforce'] = '1';
                        if(!Ext.isEmpty(rec.data['strict_enforce']))
                            rec.data['vals'] = rec.data['vals'].replace('-s ','');

                        break;
            case 'urlpath_regex':
            case 'url_regex':
            case 'dstdom_regex':
                        if(!Ext.isEmpty(rec.data['ignore_case']))
                            rec.data['vals'] = rec.data['vals'].replace('-i ','');

                        rec.data['vals'] = rec.data['vals'].replace(/\s+/g, '\n');
                        break;
            case 'proxy_auth':
                        if(rec.data['vals'].indexOf('REQUIRED')>-1){
                            rec.data['vals'] = '';
                            rec.data['authall'] = 1;
                        }else{
                            rec.data['vals'] = rec.data['vals'].replace(/\s+/g, '\n');
                            rec.data['authall'] = 0;
                        }
                        break;
            case 'proto':
            case 'method':
                        rec.data['vals'] = rec.data['vals'].replace(/\s+/g, ',');
                        break;
            case 'time':
                        var values = rec.data['vals'].replace(/[\n\r]+/g, ' ');

                        var dow = this.getForm().findField('dow');
                        var dow_arr = [];
                        var exp_reg = '';
                        var day_string = '';

                        for(var i=0,len=dow.store.getTotalCount();i<len;i++){
                            var day = dow.store.getAt(i).data['value'];
                            day_string+=day;
                            var exp_reg = '/'+day+'/';
                            exp_reg = eval(exp_reg);
                            if(exp_reg.test(values)) dow_arr.push(day);
                        }

                        if(Ext.isEmpty(dow_arr)) rec.data['dowall'] = 1;
                        else rec.data['dowall'] = 0;

                        var h_reg = '/[ '+day_string+']+/g';
                        h_reg = eval(h_reg);
                        var hours = values.replace(h_reg, '');

                        if(Ext.isEmpty(hours)){
                            rec.data['timeall'] = 1;
                        }else{
                            var pieces_h = hours.split('-',2);
                            var start_time = pieces_h[0];
                            var end_time = pieces_h[1];

                            if(start_time.length==4) start_time = '0'+start_time;
                            if(end_time.length==4) end_time = '0'+end_time;
                            rec.data['timeall'] = 0;
                            rec.data['start_time'] = start_time;
                            rec.data['end_time'] = end_time;
                        }
                        rec.data['dow'] = dow_arr;
                        break;
            case 'src':
            case 'dst':
                        var addr_values = rec.data['vals'];

                        var addr_data = addr_values.split(' ');
                        var recs = [];

                        for(var i=0,len=addr_data.length;i<len;i++){
                            recs.push(this.buildAddrGridRecord(addr_data[i]));
                        }

                        this.address_grid.getStore().loadData(recs);

                        break;
            case 'myip':

                        var addr_values = rec.data['vals'];
                        var addr_data = addr_values.split(' ');
                        var recs = [];

                        for(var i=0,len=addr_data.length;i<len;i++){
                            recs.push(this.buildAddrGridAddress(addr_data[i]));
                        }

                        this.address_grid.getStore().loadData(recs);

                        break;
            case 'external':
                        var splitted = rec.data['value'].split(' ',4);
                        var values = rec.data['vals'].replace(/[\s\n\r]+/g, ' ');
                        rec.data['vals'] = splitted[2];

                        splitted = values.split(' ');
                        if(rec.data['file-src']==1){
                            rec.data['args'] = values;
                        }else{
                            splitted.splice(0, 1);
                            var pieces_args = splitted.join(' ');
                            if(splitted.length>1)
                                rec.data['args'] = pieces_args;
                            else rec.data['args'] = '';
                        }

                        break;
            case 'srcdomain':
            case 'dstdomain':
            case 'ident_regex':
                        rec.data['vals'] = rec.data['vals'].replace(/\s+/g, '\n');
                        break;
            default:
                        break;
        }
        this.savebtn.setText('Save');

        rec.data['deny_info_index'] = rec.data['deny_info']['index'];
        rec.data['deny_info_value'] = rec.data['deny_info']['val'];

        this.getForm().getEl().mask('Loading data...');
        (function(){
            this.getForm().loadRecord(rec);
            this.getForm().getEl().unmask();
        }).defer(100,this);

    }
    /*
     * Browser Regexp -> browser
     */
    ,loadBrowserForm:function(){
        this.allFields = [{xtype:'fieldset',
                        title:'Browser Regexp ACL',
                        defaultType:'textfield',
                        items:[
                                {xtype:'hidden',name:'index'}
                                ,{xtype:'hidden',name:'type',value:this.type}
                                ,{fieldLabel:'ACL Name',name:'name'}
                                ,{fieldLabel:'Browser Regexp',name:'vals'}
                                ,{xtype:'hidden',name:'deny_info_index'}
                                ,{fieldLabel:'Failure URL', name:'deny_info_value'}
                                ,{xtype:'radio', fieldLabel:'Store ACL values in file',name:'file-src',boxLabel:'Squid configuration',checked:true,inputValue: '0'}
                                ,{xtype:'radio', fieldLabel:'',name:'file-src',boxLabel:'Separate file',inputValue: '1'}
                                ,{fieldLabel:'',name:'file',width:200}

                        ]
                    }];

    }
    ,loadProxyPortForm:function(){
        this.allFields = [{xtype:'fieldset',
                        title:'Proxy Port ACL',
                        defaultType:'textfield',
                        items:[
                                {xtype:'hidden',name:'index'}
                                ,{xtype:'hidden',name:'type',value:this.type}
                                ,{fieldLabel:'ACL Name',name:'name'}
                                ,{fieldLabel:'Proxy Server Port',name:'vals'}
                                ,{xtype:'hidden',name:'deny_info_index'}
                                ,{fieldLabel:'Failure URL', name:'deny_info_value'}
                                ,{xtype:'radio', fieldLabel:'Store ACL values in file',name:'file-src',boxLabel:'Squid configuration',checked:true,inputValue: '0'}
                                ,{xtype:'radio', fieldLabel:'',name:'file-src',boxLabel:'Separate file',inputValue: '1'}
                                ,{fieldLabel:'',name:'file',width:200}

                        ]
                    }];

    }
    ,loadMaxUserIpForm:function(title){
        this.allFields = [{xtype:'fieldset',
                        title:'Max User IP ACL',
                        items:[
                                {xtype:'hidden',name:'index'}
                                ,{xtype:'hidden',name:'type',value:this.type}
                                ,{fieldLabel:'ACL Name', xtype:'textfield', name:'name'}
                                ,{fieldLabel:'Max Logon IPs per user', xtype:'textfield', name:'vals'}
                                ,{
                                    layout:'table',border:false,
                                    layoutConfig: {columns: 2},
                                    defaults:{layout:'form',border:false, bodyStyle:'padding-left:5px;'},
                                    items: [
                                        {bodyStyle:'padding:0px;',items:[{xtype:'checkbox', style: {marginTop: '5px'}, fieldLabel:'Strictly Enforced',name:'strict_enforce'}]},
                                        {bodyStyle:'padding:0px;',items:[{xtype:'displayfield',helpText:'Remember to set Authenticate IP Cache to > 0 <br> in "Authentication Programs Module"',hideLabel:true}]}
                                    ]
                                }
                                ,{xtype:'hidden',name:'deny_info_index'}
                                ,{fieldLabel:'Failure URL', xtype:'textfield', name:'deny_info_value'}
                                ,{xtype:'radio', fieldLabel:'Store ACL values in file',name:'file-src',boxLabel:'Squid configuration',checked:true,inputValue: '0'}
                                ,{xtype:'radio', fieldLabel:'',name:'file-src',boxLabel:'Separate file',inputValue: '1'}
                                ,{fieldLabel:'',name:'file',width:200}

                        ]
                    }];

    }
    ,loadMaxConnForm:function(title){
        this.allFields = [{xtype:'fieldset',
                        title:'Maximum Connections ACL',
                        defaultType:'textfield',
                        items:[
                                {xtype:'hidden',name:'index'}
                                ,{xtype:'hidden',name:'type',value:this.type}
                                ,{fieldLabel:'ACL Name',name:'name'}
                                ,{fieldLabel:'Maximum Concurrent Requests', name:'vals'}
                                ,{xtype:'hidden',name:'deny_info_index'}
                                ,{fieldLabel:'Failure URL', name:'deny_info_value'}
                                ,{xtype:'radio', fieldLabel:'Store ACL values in file',name:'file-src',boxLabel:'Squid configuration',checked:true,inputValue: '0'}
                                ,{xtype:'radio', fieldLabel:'',name:'file-src',boxLabel:'Separate file',inputValue: '1'}
                                ,{fieldLabel:'',name:'file',width:200}

                        ]
                    }];

    }
    ,loadAsForm:function(title){
        this.allFields = [{xtype:'fieldset',
                        title:title,
                        defaultType:'textfield',
                        items:[
                                {xtype:'hidden',name:'index'}
                                ,{xtype:'hidden',name:'type',value:this.type}
                                ,{fieldLabel:'ACL Name',name:'name'}
                                ,{fieldLabel:'AS Numbers',name:'vals'}
                                ,{xtype:'hidden',name:'deny_info_index'}
                                ,{fieldLabel:'Failure URL', name:'deny_info_value'}
                                ,{xtype:'radio', fieldLabel:'Store ACL values in file',name:'file-src',boxLabel:'Squid configuration',checked:true,inputValue: '0'}
                                ,{xtype:'radio', fieldLabel:'',name:'file-src',boxLabel:'Separate file',inputValue: '1'}
                                ,{fieldLabel:'',name:'file',width:200}

                        ]
                    }];

    }
    ,loadRegexForm:function(title){
        this.allFields = [{xtype:'fieldset',
                        title:title,
                        defaultType:'textfield',
                        items:[
                                {xtype:'hidden',name:'index'}
                                ,{xtype:'hidden',name:'type',value:this.type}
                                ,{fieldLabel:'ACL Name',name:'name'}
                                ,{xtype:'checkbox',boxLabel:'Ignore case?',fieldLabel:'Regular expressions',name:'ignore_case'}
                                ,{xtype:'textarea',name:'vals',width:200}
                                ,{xtype:'hidden',name:'deny_info_index'}
                                ,{fieldLabel:'Failure URL', name:'deny_info_value'}
                                ,{xtype:'radio', fieldLabel:'Store ACL values in file',name:'file-src',boxLabel:'Squid configuration',checked:true,inputValue: '0'}
                                ,{xtype:'radio', fieldLabel:'',name:'file-src',boxLabel:'Separate file',inputValue: '1'}
                                ,{fieldLabel:'',name:'file',width:200}

                        ]
                    }];

    }
     /*
      * Address -> src || dst
      */
    ,loadAddressForm:function(title){

        /*
         * address grid
         *
         */
        var addrSelectBoxModel= new Ext.grid.CheckboxSelectionModel({keepSelections:true});


        // the column model has information about grid columns
        // dataIndex maps the column to the specific data field in
        // the data store (created below)
        var addr_cm = new Ext.grid.ColumnModel({
            // specify any defaults for each column
            defaults: {
                sortable: true // columns are not sortable by default
            },
            columns: [
                addrSelectBoxModel,
                {
                    header: 'From IP',
                    dataIndex: 'from_ip',
                    width: 80,
                    editor: new Ext.form.TextField({
                        allowBlank: false,
                        selectOnFocus:true
                    })
                }
                ,{
                    header: 'To IP',
                    dataIndex: 'to_ip',
                    width: 80,
                    align: 'right',
                    editor: new Ext.form.TextField({
                        allowBlank: false
                    })
                }
                ,{
                    header: 'Netmask',
                    dataIndex: 'netmask',
                    id:'netmask',
                    width: 80,
                    align: 'right',
                    editor: new Ext.form.TextField({
                        allowBlank: false
                    })
                }
            ]
        });

        var addr_store = new Ext.data.SimpleStore({
                fields:['from_ip','to_ip','netmask']
            });


        // create the grid
        this.address_grid = new Ext.grid.EditorGridPanel({
            store: addr_store,
            cm: addr_cm,
            sm:addrSelectBoxModel,
            autoHeight: true,
            border:true,
            layout:'fit',
            style:'padding:5px 0px 5px 0px;',
            autoExpandColumn:'netmask',
            viewConfig:{
                forceFit:true
                ,emptyText: 'Empty!',  //  emptyText Message
                deferEmptyText:false
            },
            tbar: [{
                    text: 'Add address',
                    handler : function(){

                        // access the Record constructor through the grid's store
                        var addrRec = this.address_grid.getStore().recordType;
                        var addr = new addrRec({
                            from_ip: '',
                            to_ip: '',
                            netmask: ''
                        });

                        this.address_grid.stopEditing();
                        addr_store.insert(0, addr);
                        this.address_grid.startEditing(0,1);

                    }
                    ,scope:this}
                ,{
                    ref: '../removeBtn',
                    text: 'Remove address',
                    disabled: true,
                    handler: function(){
                        this.address_grid.stopEditing();
                        var s = this.address_grid.getSelectionModel().getSelections();
                        for(var i = 0, r; r = s[i]; i++){
                            addr_store.remove(r);
                        }
                    },scope:this
                }]


        });

        this.address_grid.getSelectionModel().on('selectionchange', function(sm){
            this.address_grid.removeBtn.setDisabled(sm.getCount() < 1);
        },this);

        /*
         * END address grid
         */

        this.allFields = [{xtype:'fieldset',
                        title:title,
                        defaultType:'textfield',
                        items:[
                                {xtype:'hidden',name:'index'}
                                ,{xtype:'hidden',name:'type',value:this.type}
                                ,{fieldLabel:'ACL Name',name:'name'}
                                ,this.address_grid
                                ,{xtype:'hidden',name:'deny_info_index'}
                                ,{fieldLabel:'Failure URL', name:'deny_info_value'}
                                ,{xtype:'radio', fieldLabel:'Store ACL values in file',name:'file-src',boxLabel:'Squid configuration',checked:true,inputValue: '0'}
                                ,{xtype:'radio', fieldLabel:'',name:'file-src',boxLabel:'Separate file',inputValue: '1'}
                                ,{fieldLabel:'',name:'file',width:200}

                        ]
                    }];

    }
    ,loadProxyIpForm:function(){

        /*
         * address grid
         *
         */
        var addrSelectBoxModel= new Ext.grid.CheckboxSelectionModel({keepSelections:true});


        // the column model has information about grid columns
        // dataIndex maps the column to the specific data field in
        // the data store (created below)
        var addr_cm = new Ext.grid.ColumnModel({
            // specify any defaults for each column
            defaults: {
                sortable: true // columns are not sortable by default
            },
            columns: [
                addrSelectBoxModel,
                {
                    header: 'IP Address',
                    dataIndex: 'from_ip',
                    width: 80,
                    editor: new Ext.form.TextField({
                        allowBlank: false,
                        selectOnFocus:true
                    })
                }
                ,{
                    header: 'Netmask',
                    dataIndex: 'netmask',
                    id:'netmask',
                    width: 80,
                    align: 'right',
                    editor: new Ext.form.TextField({
                        allowBlank: false
                    })
                }
            ]
        });

        var addr_store = new Ext.data.SimpleStore({
                fields:['from_ip','netmask']
            });

        // create the grid
        this.address_grid = new Ext.grid.EditorGridPanel({
            store: addr_store,
            cm: addr_cm,
            sm:addrSelectBoxModel,
            autoHeight: true,
            border:true,
            layout:'fit',
            style:'padding:5px 0px 5px 0px;',
            autoExpandColumn:'netmask',
            viewConfig:{
                forceFit:true
                ,emptyText: 'Empty!',  //  emptyText Message
                deferEmptyText:false
            },
            tbar: [{
                    text: 'Add address',
                    handler : function(){

                        // access the Record constructor through the grid's store
                        var addrRec = this.address_grid.getStore().recordType;
                        var addr = new addrRec({
                            ip: '',
                            netmask: ''
                        });

                        this.address_grid.stopEditing();
                        addr_store.insert(0, addr);
                        this.address_grid.startEditing(0,1);

                    }
                    ,scope:this}
                ,{
                    ref: '../removeBtn',
                    text: 'Remove address',
                    disabled: true,
                    handler: function(){
                        this.address_grid.stopEditing();
                        var s = this.address_grid.getSelectionModel().getSelections();
                        for(var i = 0, r; r = s[i]; i++){
                            addr_store.remove(r);
                        }
                    },scope:this
                }]


        });

        this.address_grid.getSelectionModel().on('selectionchange', function(sm){
            this.address_grid.removeBtn.setDisabled(sm.getCount() < 1);
        },this);

        /*
         * END address grid
         */

        this.allFields = [{xtype:'fieldset',
                        title:'Proxy IP Address ACL',
                        defaultType:'textfield',
                        items:[
                                {xtype:'hidden',name:'index'}
                                ,{xtype:'hidden',name:'type',value:this.type}
                                ,{fieldLabel:'ACL Name',name:'name'}
                                ,this.address_grid
                                ,{xtype:'hidden',name:'deny_info_index'}
                                ,{fieldLabel:'Failure URL', name:'deny_info_value'}
                                ,{xtype:'radio', fieldLabel:'Store ACL values in file',name:'file-src',boxLabel:'Squid configuration',checked:true,inputValue: '0'}
                                ,{xtype:'radio', fieldLabel:'',name:'file-src',boxLabel:'Separate file',inputValue: '1'}
                                ,{fieldLabel:'',name:'file',width:200}

                        ]
                    }];

    }
    ,loadPortForm:function(){
        this.allFields = [{xtype:'fieldset',
                        title:'URL Port ACL',
                        defaultType:'textfield',
                        items:[
                                {xtype:'hidden',name:'index'}
                                ,{xtype:'hidden',name:'type',value:this.type}
                                ,{fieldLabel:'ACL Name',name:'name'}
                                ,{fieldLabel:'TCP Ports',name:'vals'}
                                ,{xtype:'hidden',name:'deny_info_index'}
                                ,{fieldLabel:'Failure URL', name:'deny_info_value'}
                                ,{xtype:'radio', fieldLabel:'Store ACL values in file',name:'file-src',boxLabel:'Squid configuration',checked:true,inputValue: '0'}
                                ,{xtype:'radio', fieldLabel:'',name:'file-src',boxLabel:'Separate file',inputValue: '1'}
                                ,{fieldLabel:'',name:'file',width:200}

                        ]
                    }];

    }
    ,loadMethodForm:function(){
        this.allFields = [{xtype:'fieldset',
                        title:'Request Method ACL',
                        defaultType:'textfield',
                        items:[
                                {xtype:'hidden',name:'index'}
                                ,{xtype:'hidden',name:'type',value:this.type}
                                ,{fieldLabel:'ACL Name',name:'name'}
                                ,{
                                    xtype: 'checkboxgroup',
                                    fieldLabel: 'Request Methods',
                                    name:'vals',
                                    columns:2,
                                    items: [
                                        {boxLabel: 'GET', name: 'vals', inputValue: 'GET'},
                                        {boxLabel: 'POST', name: 'vals', inputValue: 'POST'},
                                        {boxLabel: 'HEAD', name: 'vals', inputValue: 'HEAD'},
                                        {boxLabel: 'CONNECT', name: 'vals', inputValue: 'CONNECT'},
                                        {boxLabel: 'PUT', name: 'vals', inputValue: 'PUT'},
                                        {boxLabel: 'DELETE', name: 'vals', inputValue: 'DELETE'}
                                    ]
                                }
                                ,{xtype:'hidden',name:'deny_info_index'}
                                ,{fieldLabel:'Failure URL', name:'deny_info_value'}
                                ,{xtype:'radio', fieldLabel:'Store ACL values in file',name:'file-src',boxLabel:'Squid configuration',checked:true,inputValue: '0'}
                                ,{xtype:'radio', fieldLabel:'',name:'file-src',boxLabel:'Separate file',inputValue: '1'}
                                ,{fieldLabel:'',name:'file',width:200}

                        ]
                    }];

    }
    ,loadProtocolForm:function(){
        this.allFields = [{xtype:'fieldset',
                        title:'URL Protocol ACL',
                        defaultType:'textfield',
                        items:[
                                {xtype:'hidden',name:'index'}
                                ,{xtype:'hidden',name:'type',value:this.type}
                                ,{fieldLabel:'ACL Name',name:'name'}
                                ,{
                                    xtype: 'checkboxgroup',
                                    fieldLabel: 'URL Protocols',
                                    name:'vals',
                                    columns:2,
                                    items: [
                                        {boxLabel: 'http', name: 'vals', inputValue: 'http'},
                                        {boxLabel: 'ftp', name: 'vals', inputValue: 'ftp'},
                                        {boxLabel: 'gopher', name: 'vals', inputValue: 'gopher'},
                                        {boxLabel: 'wais', name: 'vals', inputValue: 'wais'},
                                        {boxLabel: 'cache_object', name: 'vals', inputValue: 'cache_object'}
                                    ]
                                }
                                ,{xtype:'hidden',name:'deny_info_index'}
                                ,{fieldLabel:'Failure URL', name:'deny_info_value'}
                                ,{xtype:'radio', fieldLabel:'Store ACL values in file',name:'file-src',boxLabel:'Squid configuration',checked:true,inputValue: '0'}
                                ,{xtype:'radio', fieldLabel:'',name:'file-src',boxLabel:'Separate file',inputValue: '1'}
                                ,{fieldLabel:'',name:'file',width:200}

                        ]
                    }];

    }
    ,loadProxyAuthForm:function(){
        this.allFields = [{xtype:'fieldset',
                        title:'External Auth ACL',
                        defaultType:'textfield',
                        items:[
                                {xtype:'hidden',name:'index'}
                                ,{xtype:'hidden',name:'type',value:this.type}
                                ,{fieldLabel:'ACL Name',name:'name'}
                                ,{xtype:'radiogroup',
                                    name:'authall',
                                    fieldLabel:'External Auth Users',
                                    height:15,
                                    columns:[70,100],
                                    items:[
                                        {xtype:'radio', name:'authall', checked:true, inputValue:'1', boxLabel:'All users'},
                                        {xtype:'radio', name:'authall',inputValue:'0', boxLabel:'Only listed...'}]
                                }
                                ,{xtype:'textarea',fieldLabel:'',name:'vals',width:200}
                                ,{xtype:'hidden',name:'deny_info_index'}
                                ,{fieldLabel:'Failure URL', name:'deny_info_value'}
                                ,{xtype:'radio', fieldLabel:'Store ACL values in file',name:'file-src',boxLabel:'Squid configuration',checked:true,inputValue: '0'}
                                ,{xtype:'radio', fieldLabel:'',name:'file-src',boxLabel:'Separate file',inputValue: '1'}
                                ,{fieldLabel:'',name:'file',width:200}

                        ]
                    }];
    }
    ,loadProxyAuthRegexForm:function(title){
        this.allFields = [{xtype:'fieldset',
                        title:'External Auth Regexp ACL',
                        defaultType:'textfield',
                        items:[
                                {xtype:'hidden',name:'index'}
                                ,{xtype:'hidden',name:'type',value:this.type}
                                ,{fieldLabel:'ACL Name',name:'name'}
                                ,{xtype:'checkbox',boxLabel:'Ignore case?',fieldLabel:'External Auth Users',name:'ignore_case'}
                                ,{xtype:'textarea',name:'vals',width:200}
                                ,{xtype:'hidden',name:'deny_info_index'}
                                ,{fieldLabel:'Failure URL', name:'deny_info_value'}
                                ,{xtype:'radio', fieldLabel:'Store ACL values in file',name:'file-src',boxLabel:'Squid configuration',checked:true,inputValue: '0'}
                                ,{xtype:'radio', fieldLabel:'',name:'file-src',boxLabel:'Separate file',inputValue: '1'}
                                ,{fieldLabel:'',name:'file',width:200}

                        ]
                    }];

    }
    ,loadRFC931Form:function(title){
        this.allFields = [{xtype:'fieldset',
                        title:'RFC931 User ACL',
                        defaultType:'textfield',
                        items:[
                                {xtype:'hidden',name:'index'}
                                ,{xtype:'hidden',name:'type',value:this.type}
                                ,{fieldLabel:'ACL Name',name:'name'}
                                ,{xtype:'textarea',fieldLabel:'RFC931 Users',name:'vals',width:200}
                                ,{xtype:'hidden',name:'deny_info_index'}
                                ,{fieldLabel:'Failure URL', name:'deny_info_value'}
                                ,{xtype:'radio', fieldLabel:'Store ACL values in file',name:'file-src',boxLabel:'Squid configuration',checked:true,inputValue: '0'}
                                ,{xtype:'radio', fieldLabel:'',name:'file-src',boxLabel:'Separate file',inputValue: '1'}
                                ,{fieldLabel:'',name:'file',width:200}

                        ]
                    }];
    }
    ,loadRFC931RegexForm:function(){
        this.allFields = [{xtype:'fieldset',
                        title:'RFC931 User Regexp ACL',
                        defaultType:'textfield',
                        items:[
                                {xtype:'hidden',name:'index'}
                                ,{xtype:'hidden',name:'type',value:this.type}
                                ,{fieldLabel:'ACL Name',name:'name'}
                                ,{xtype:'checkbox',boxLabel:'Ignore case?',fieldLabel:'RFC931 Users Regexps',name:'ignore_case'}
                                ,{xtype:'textarea',name:'vals',width:200}
                                ,{xtype:'hidden',name:'deny_info_index'}
                                ,{fieldLabel:'Failure URL', name:'deny_info_value'}
                                ,{xtype:'radio', fieldLabel:'Store ACL values in file',name:'file-src',boxLabel:'Squid configuration',checked:true,inputValue: '0'}
                                ,{xtype:'radio', fieldLabel:'',name:'file-src',boxLabel:'Separate file',inputValue: '1'}
                                ,{fieldLabel:'',name:'file',width:200}

                        ]
                    }];

    }
    ,loadHostnameForm:function(title){
        this.allFields = [{xtype:'fieldset',
                        title:title,
                        defaultType:'textfield',
                        items:[
                                {xtype:'hidden',name:'index'}
                                ,{xtype:'hidden',name:'type',value:this.type}
                                ,{fieldLabel:'ACL Name',name:'name'}
                                ,{xtype:'textarea',fieldLabel:'Domains',name:'vals',width:200}
                                ,{xtype:'hidden',name:'deny_info_index'}
                                ,{fieldLabel:'Failure URL', name:'deny_info_value'}
                                ,{xtype:'radio', fieldLabel:'Store ACL values in file',name:'file-src',boxLabel:'Squid configuration',checked:true,inputValue: '0'}
                                ,{xtype:'radio', fieldLabel:'',name:'file-src',boxLabel:'Separate file',inputValue: '1'}
                                ,{fieldLabel:'',name:'file',width:200}

                        ]
                    }];
    }
    ,loadEthernetForm:function(title){
        this.allFields = [{xtype:'fieldset',
                        title:'Ethernet Address ACL',
                        defaultType:'textfield',
                        items:[
                                {xtype:'hidden',name:'index'}
                                ,{xtype:'hidden',name:'type',value:this.type}
                                ,{fieldLabel:'ACL Name',name:'name'}
                                ,{xtype:'textarea',fieldLabel:'Client ethernet addresses',name:'vals',width:200}
                                ,{xtype:'hidden',name:'deny_info_index'}
                                ,{fieldLabel:'Failure URL', name:'deny_info_value'}
                                ,{xtype:'radio', fieldLabel:'Store ACL values in file',name:'file-src',boxLabel:'Squid configuration',checked:true,inputValue: '0'}
                                ,{xtype:'radio', fieldLabel:'',name:'file-src',boxLabel:'Separate file',inputValue: '1'}
                                ,{fieldLabel:'',name:'file',width:200}

                        ]
                    }];
    }
    ,loadTimeForm:function(){
        this.allFields = [{xtype:'fieldset',
                        title:'Date and Time ACL',
                        defaultType:'textfield',
                        items:[
                                {xtype:'hidden',name:'index'}
                                ,{xtype:'hidden',name:'type',value:this.type}
                                ,{fieldLabel:'ACL Name',name:'name'}
                                ,{xtype:'radiogroup',
                                    name:'dowall',
                                    fieldLabel:'Days of the week',
                                    height:15,
                                    columns:[35,100],
                                    items:[
                                        {xtype:'radio', name:'dowall', checked:true, inputValue:'1', boxLabel:'All'},
                                        {xtype:'radio', name:'dowall',inputValue:'0', boxLabel:'Selected...'}]
                                }
                                ,{
                                    xtype: 'multiselect',
                                    fieldLabel: '',
                                    name: 'dow',
                                    valueField: 'value',
                                    displayField:'name',
                                    width:205,
                                    height:130,
                                    allowBlank:true,
                                    store: new Ext.data.ArrayStore({
                                                fields: ['name', 'value'],
                                                data: [
                                                      ['Sunday','S'],
                                                      ['Monday','M'],['Tuesday','T'],
                                                      ['Wednesday','W'],['Thursday','H'],
                                                      ['Friday','F'],['Saturday','A']]
                                    })
                                }
                               ,{
                                    layout:'table',
                                    xtype:'panel',
                                    layoutConfig: {columns: 4},
                                    border: false,
                                    defaults:{layout:'form',border:false},
                                    items: [
                                        {items:[{xtype:'radio', fieldLabel:'Hours of the day',name:'timeall',boxLabel:'All',checked:true,inputValue: 1}]},
                                        {items:[{xtype:'radio', fieldLabel:'',hideLabel:true,name:'timeall',boxLabel:'',inputValue: 0}]},
                                        {bodyStyle:'padding-bottom:5px;padding-left:5px;',items:[{xtype:'uxspinner',
                                                        fieldLabel:'',hideLabel:true,name:'start_time',
                                                        strategy:{xtype:'time'},emptyText: 'hh:mm',
                                                        width:60,form:this,vtype: 'spinner_timerange',
                                                        endTimeField: 'end_time',regex: /^(([01][0-9])|2[0-3]):[0-5][0-9]$/
                                        }]},
                                        {bodyStyle:'padding-bottom:5px;padding-left:5px;',labelWidth:10,items:[{xtype:'uxspinner',
                                                        fieldLabel:'to',name:'end_time',
                                                        strategy:{xtype:'time'},emptyText: 'hh:mm',
                                                        width:60,form:this,vtype: 'spinner_timerange',
                                                        startTimeField: 'start_time',regex: /^(([01][0-9])|2[0-3]):[0-5][0-9]$/
                                        }]}
                                    ]
                                 }
                                ,{xtype:'hidden',name:'deny_info_index'}
                                ,{fieldLabel:'Failure URL', name:'deny_info_value'}
                                ,{xtype:'radio', fieldLabel:'Store ACL values in file',name:'file-src',boxLabel:'Squid configuration',checked:true,inputValue: '0'}
                                ,{xtype:'radio', fieldLabel:'',name:'file-src',boxLabel:'Separate file',inputValue: '1'}
                                ,{fieldLabel:'',name:'file',width:200}
                        ]
                    }];

    }
    ,loadExternalForm:function(){

        var external_store = new Ext.data.JsonStore({
            // id:'Id'
            autoLoad:true
            ,root:'data'
            ,totalProperty:'total'
            ,fields:[
                {name:'name', sortType:'asUCString',type:'string'}
                ,{name:'value', type:'string'}
            ]
            ,url:<?php echo json_encode(url_for('etfw/json'))?>
            ,baseParams:{id:this.service_id,method:'get_external_acl_type',mode:'get_external_acl_combo'}
        });

        var externalCombo = new Ext.form.ComboBox({
            mode: 'local',
            name:'vals',
            bbar:new Ext.ux.grid.TotalCountBar({
                store:external_store
                ,displayInfo:false
            }),
            triggerAction: 'all',
            fieldLabel: 'Program class',
            //readOnly:true,
            editable:false,
            store:external_store,
          //  valueField: 'value',
            selectOnFocus:true,
            forceSelection: true,
            typeAhead:true,
            lazyRender:true,
            maxHeight:200,
            displayField: 'name'
        });

        this.panel.el.mask('Loading external program data');

        external_store.on('load',function(){
            this.panel.el.unmask();
            if(Ext.isEmpty(externalCombo.getValue()))
                externalCombo.setValue(external_store.getAt(0).data['name']);
        },this);


        this.allFields = [{xtype:'fieldset',
                        title:'External Program ACL',
                        defaultType:'textfield',
                        items:[
                                {xtype:'hidden',name:'index'}
                                ,{xtype:'hidden',name:'type',value:this.type}
                                ,{fieldLabel:'ACL Name',name:'name'}
                                ,externalCombo
                                ,{fieldLabel:'Additional arguments',name:'args'}
                                ,{xtype:'hidden',name:'deny_info_index'}
                                ,{fieldLabel:'Failure URL', name:'deny_info_value'}
                                ,{xtype:'radio', fieldLabel:'Store ACL values in file',name:'file-src',boxLabel:'Squid configuration',checked:true,inputValue: '0'}
                                ,{xtype:'radio', fieldLabel:'',name:'file-src',boxLabel:'Separate file',inputValue: '1'}
                                ,{fieldLabel:'',name:'file',width:200}

                        ]
                    }];

    }
    ,loadRequestmimeForm:function(){
        this.allFields = [{xtype:'fieldset',
                        title:'Request MIME Type ACL',
                        defaultType:'textfield',
                        items:[
                                {xtype:'hidden',name:'index'}
                                ,{xtype:'hidden',name:'type',value:this.type}
                                ,{fieldLabel:'ACL Name',name:'name'}
                                ,{fieldLabel:'Request MIME Type',name:'vals'}
                                ,{xtype:'hidden',name:'deny_info_index'}
                                ,{fieldLabel:'Failure URL', name:'deny_info_value'}
                                ,{xtype:'radio', fieldLabel:'Store ACL values in file',name:'file-src',boxLabel:'Squid configuration',checked:true,inputValue: '0'}
                                ,{xtype:'radio', fieldLabel:'',name:'file-src',boxLabel:'Separate file',inputValue: '1'}
                                ,{fieldLabel:'',name:'file',width:200}

                        ]
                    }];

    }
    ,loadReplymimeForm:function(){
        this.allFields = [{xtype:'fieldset',
                        title:'Reply MIME Type ACL',
                        defaultType:'textfield',
                        items:[
                                {xtype:'hidden',name:'index'}
                                ,{xtype:'hidden',name:'type',value:this.type}
                                ,{fieldLabel:'ACL Name',name:'name'}
                                ,{fieldLabel:'Reply MIME Type',name:'vals'}
                                ,{xtype:'hidden',name:'deny_info_index'}
                                ,{fieldLabel:'Failure URL', name:'deny_info_value'}
                                ,{xtype:'radio', fieldLabel:'Store ACL values in file',name:'file-src',boxLabel:'Squid configuration',checked:true,inputValue: '0'}
                                ,{xtype:'radio', fieldLabel:'',name:'file-src',boxLabel:'Separate file',inputValue: '1'}
                                ,{fieldLabel:'',name:'file',width:200}

                        ]
                    }];

    }
    ,loadSnmpcommunityForm:function(){
        this.allFields = [{xtype:'fieldset',
                        title:'SNMP Community ACL',
                        defaultType:'textfield',
                        items:[
                                {xtype:'hidden',name:'index'}
                                ,{xtype:'hidden',name:'type',value:this.type}
                                ,{fieldLabel:'ACL Name',name:'name'}
                                ,{fieldLabel:'SNMP Community String',name:'vals'}
                                ,{xtype:'hidden',name:'deny_info_index'}
                                ,{fieldLabel:'Failure URL', name:'deny_info_value'}
                                ,{xtype:'radio', fieldLabel:'Store ACL values in file',name:'file-src',boxLabel:'Squid configuration',checked:true,inputValue: '0'}
                                ,{xtype:'radio', fieldLabel:'',name:'file-src',boxLabel:'Separate file',inputValue: '1'}
                                ,{fieldLabel:'',name:'file',width:200}

                        ]
                    }];

    }

});
Ext.reg('etfw_squid_acl_tpl', ETFW.Squid.Acl.AclTpl);


/*
 * ACL grid
 *
 */

ETFW.Squid.Acl.Acl_Grid = Ext.extend(Ext.grid.GridPanel, {
    initComponent:function() {

        // show check boxes
        var selectBoxModel= new Ext.grid.CheckboxSelectionModel({keepSelections:true});

        var acl_tpl_store = new Ext.data.ArrayStore({
            fields:['value','name'],
            data:[
                ['browser','Browser Regexp'],['src','Client Address'],
                ['srcdomain','Client Hostname'],['srcdom_regex','Client Regexp'],
                ['time','Date and Time'],['dst_as','Dest AS Number'],
                ['arp','Ethernet Address'],['proxy_auth','External Auth'],
                ['proxy_auth_regex','External Auth Regexp'],['external','External Program'],
                ['max_user_ip','Max User IP'],['maxconn','Maximum Connections'],
                ['myip','Proxy IP Address'],['myport','Proxy Port'],
                ['ident','RFC931 User'],['ident_regex','RFC931 User Regexp'],
                ['rep_mime_type','Reply MIME Type'],['req_mime_type','Request MIME Type'],
                ['method','Request Method'],['snmp_community','SNMP Community'],
                ['src_as','Source AS Number'],['urlpath_regex','URL Path Regexp'],
                ['port','URL Port'],['proto','URL Protocol'],
                ['url_regex','URL Regexp'],['dst','Web Server Address'],
                ['dstdomain','Web Server Hostname'],['dstdom_regex','Web Server Regexp']
            ]
        });

        // column model
        var cm = new Ext.grid.ColumnModel([
            selectBoxModel,
            {header: "Name", width: 120, sortable: true, dataIndex: 'name',renderer: function (value, metadata, record, rowIndex, colIndex, store) {
                            metadata.attr = 'ext:qtip="Double-click to edit"';
                            return value;
                        }},
            {header: "Type", dataIndex: 'type', width:120, sortable: true,renderer:function(v){
                    var store_index = acl_tpl_store.findExact('value',v);
                    var display = '';
                    if(store_index!=-1) display = acl_tpl_store.getAt(store_index).get('name');
                    return display;}},
            {header: "Matching", dataIndex: 'vals', width:120,renderer:function(value,meta,rec){
                    if(!Ext.isEmpty(rec.data['file'])) return 'From file '+rec.data['file'];
                    else return value;
            }, sortable: true}
        ]);

        var dataStore = new Ext.data.GroupingStore({
                reader:new Ext.data.JsonReader({
                    totalProperty: 'total',
                    root: 'data',
                    fields: ['index',{name:'name', sortType:'asUCString',type:'string'},'type','value',{name:'vals', type:'string'},'file','filecontent','deny_info']
                })
                ,proxy:new Ext.data.HttpProxy({url:this.url})
                ,baseParams:{id:this.service_id,method:'get_acl'}
                //,groupField:'type'
                ,sortInfo:{field:'index',direction:'ASC'}
        });

        var config = {
            store:dataStore
            ,view: new Ext.grid.GroupingView({
                forceFit:true
                ,groupTextTpl:'{text} ({[values.rs.length]} {[values.rs.length > 1 ? "Items" : "Item"]})'
            })
            ,cls:'gridWrap'
            ,cm:cm
            ,sm:selectBoxModel
            ,viewConfig:{forceFit:true}
            ,loadMask:true
        }; // eo config object

        // apply config
        Ext.apply(this, Ext.apply(this.initialConfig, config));

        this.bbar = new Ext.ux.grid.TotalCountBar({
            store:this.store
            ,displayInfo:true
        });



        this.acl_tpl_combo = new Ext.form.ComboBox({
            editable:false,
            //readOnly:true,
            mode:'local',
            store: acl_tpl_store,
            //value:'myport',
            value:'time',
            triggerAction: 'all',
            valueField: 'value',
            displayField: 'name',
            forceSelection: true
        });


        this.tbar = [
                {
                    tooltip:'Click here to submit changes',
                    text:'Create new ACL',
                    iconCls:'add',
                    handler:function(){this.setAclTpl('show');},
                    scope:this

                }
                ,this.acl_tpl_combo,'-'
                ,{
                    ref: '../editBtn',
                    text:'Edit',
                    tooltip:'Edit the select item',
                    disabled:true,
                    handler: function(item){
                        var selected = item.ownerCt.ownerCt.getSelectionModel().getSelected();
                        this.ownerCt.fireEvent('loadAclTplRecord',selected);
                    },scope:this
                }
                ,'-'
                ,{
                    ref: '../removeBtn',
                    text:'Delete',
                    tooltip:'Delete the selected item(s)',
                    iconCls:'remove',
                    disabled:true,
                    handler: function(){
                        new Grid.util.DeleteItem({panel: this.id});
                    },scope:this
                }];

        // call parent
        ETFW.Squid.Acl.Acl_Grid.superclass.initComponent.apply(this, arguments);

        this.getSelectionModel().on('selectionchange', function(sm){
            this.editBtn.setDisabled(sm.getCount() < 1);
            this.removeBtn.setDisabled(sm.getCount() < 1);
        },this);

        this.on('rowdblclick', function(gridPanel, rowIndex, e) {
            var selected = this.store.data.items[rowIndex];

            this.ownerCt.fireEvent('loadAclTplRecord',selected);
        });

        // load the store at the latest possible moment
        this.on({
            afterlayout:{scope:this, single:true, fn:function() {
                    this.store.load();

                }}
        });

        /************************************************************
         * handle contextmenu event
         ************************************************************/
        this.addListener("rowcontextmenu", onContextMenu, this);
        function onContextMenu(grid, rowIndex, e) {
            if (!this.menu) {
                this.menu = new Ext.menu.Menu({
                    // id: 'menus',
                    items: [{
                            text:'Delete',
                            tooltip:'Delete the selected item(s)',
                            iconCls:'remove',
                            handler: function(){
                                new Grid.util.DeleteItem({panel: grid.id});
                            }
                        }]
                });
            }
            e.stopEvent();
            this.menu.showAt(e.getXY());
        }

        this.store.on('load',function(){this.setAclTpl()},this,{delay:200});

    } // eo function initComponent
    ,reload : function() {
        this.store.load();
    }
    ,// call delete stuff now
    // Server side will receive delData throught parameter
    deleteData : function (items) {

        var conn = new Ext.data.Connection({
            listeners:{
                // wait message.....
                beforerequest:function(){
                    Ext.MessageBox.show({
                        title: 'Please wait',
                        msg: 'Deleting ACL(s)...',
                        width:300,
                        wait:true,
                        modal: false
                    });
                },// on request complete hide message
                requestcomplete:function(){Ext.MessageBox.hide();}
            }
        });// end conn

        var acls = [];

        for(var i=0,len = items.length;i<len;i++){
            acls[i] = items[i].data.index;
        }

        var send_data = {'indexes':acls};

        conn.request({
            url: this.url,
            params:{id:this.service_id,method:'del_acl',params:Ext.encode(send_data)},
            failure: function(resp,opt){
                var response = Ext.util.JSON.decode(resp.responseText);
                Ext.MessageBox.alert('Error Message', response['info']);
                Ext.ux.Logger.error(response['error']);

            },
            // everything ok...
            success: function(resp,opt){
                var msg = 'Deleted ACL(s)';
                Ext.ux.Logger.info(msg);
                View.notify({html:msg});
                this.reload();

            },scope:this
        });// END Ajax request

    }
    ,setAclTpl:function(mode){

         var acltpl = this.acl_tpl_combo.getValue();
         if(!mode) mode = '';
         this.ownerCt.fireEvent('loadAclTpl',acltpl,mode);

    }

});
Ext.reg('etfw_squid_aclgrid', ETFW.Squid.Acl.Acl_Grid);



/*
 *
 *
 * RESTRICTIONS
 *
 *
 */

/*
 * generic restrictions grid
 *
 */

ETFW.Squid.Acl.Restriction_Grid = Ext.extend(Ext.grid.GridPanel, {
    initComponent:function() {

        // show check boxes
        var selectBoxModel= new Ext.grid.CheckboxSelectionModel({keepSelections:true});

        var move_action = new Ext.ux.grid.RowActions({
            header:'Move'
            ,keepSelection:true
            ,actions:[{
                    iconCls:'icon-down'
                    ,tooltip:'Move rule down'
                },{
                    iconCls:'icon-up'
                    ,tooltip:'Move rule up'
                }]
            ,scope:this
        });


        move_action.on({

            action:function(grid, record, action, row, col) {

                var record = grid.getStore().getAt(row);
                var total_rows = grid.getStore().getCount();

                switch(action){
                        case 'icon-up':
                                        if(row!=0){
                                            var prev_record = grid.getStore().getAt(row-1);
                                            this.moveRestriction(record.data.index,prev_record.data.index);
                                        }
                                        break;
                      case 'icon-down':
                                        if(row!=(total_rows-1)){
                                            var next_record = grid.getStore().getAt(row+1);
                                            this.moveRestriction(record.data.index,next_record.data.index);
                                        }
                                        break;
                               default: break;
                }

            },scope:this
        },this);


        var dataStore = new Ext.data.JsonStore({
            url: this.url,
            //idProperty:'index',
            baseParams:{id:this.service_id,method:this.method},
            remoteSort: false,
            totalProperty: 'total',
            root: 'data',
            fields: ['index','action','match','dontmatch','acl']
        });

        // column model
        var cm = new Ext.grid.ColumnModel([
            selectBoxModel,
            {header: "Action", width: 120, sortable: true, dataIndex: 'action',renderer: function (value, metadata, record, rowIndex, colIndex, store) {
                            metadata.attr = 'ext:qtip="Double-click to edit"';
                            return value;
            }},
            {header: "ACLs", dataIndex: 'acl', width:120, sortable: true, renderer:function(value,meta,rec){
                    return value.join(' ');
            }},
            move_action
        ]);




        var config = {
            store:dataStore
            ,cls:'gridWrap'
            ,cm:cm
            ,sm:selectBoxModel
            ,viewConfig:{forceFit:true}
            ,loadMask:true
            ,plugins:[move_action]
        }; // eo config object

        // apply config
        Ext.apply(this, Ext.apply(this.initialConfig, config));

        this.bbar = new Ext.ux.grid.TotalCountBar({
            store:this.store
            ,displayInfo:true
        });

        this.tbar = [
                {
                    tooltip:'Click here to add '+this.restrictionTitle,
                    text:'Create new '+this.restrictionTitle,
                    iconCls:'add',
                    handler:function(){this.clearRestrictionTpl('show');},
                    scope:this

                }
                ,'-'
                ,{
                    ref: '../editBtn',
                    text:'Edit',
                    tooltip:'Edit the select item',
                    disabled:true,
                    handler: function(item){
                        var selected = item.ownerCt.ownerCt.getSelectionModel().getSelected();
                        this.ownerCt.fireEvent('loadRestrictionTplRecord',selected);
                    },scope:this
                }
                ,'-'
                ,{
                    ref: '../removeBtn',
                    text:'Delete',
                    tooltip:'Delete the selected item(s)',
                    iconCls:'remove',
                    disabled:true,
                    handler: function(){
                        new Grid.util.DeleteItem({panel: this.id});
                    },scope:this
                }];

        // call parent
        ETFW.Squid.Acl.Restriction_Grid.superclass.initComponent.apply(this, arguments);

        this.getSelectionModel().on('selectionchange', function(sm){
            this.editBtn.setDisabled(sm.getCount() < 1);
            this.removeBtn.setDisabled(sm.getCount() < 1);
        },this);

        this.on('rowdblclick', function(gridPanel, rowIndex, e) {
            var selected = this.store.data.items[rowIndex];

            this.ownerCt.fireEvent('loadRestrictionTplRecord',selected);
        });



        /************************************************************
         * handle contextmenu event
         ************************************************************/
        this.addListener("rowcontextmenu", onContextMenu, this);
        function onContextMenu(grid, rowIndex, e) {
            if (!this.menu) {
                this.menu = new Ext.menu.Menu({
                    // id: 'menus',
                    items: [{
                            text:'Delete',
                            tooltip:'Delete the selected item(s)',
                            iconCls:'remove',
                            handler: function(){
                                new Grid.util.DeleteItem({panel: grid.id});
                            }
                        }]
                });
            }
            e.stopEvent();
            this.menu.showAt(e.getXY());
        }

        this.store.on('load',function(){this.clearRestrictionTpl()},this,{delay:200});

        // load the store at the latest possible moment
        this.on({
            afterlayout:{scope:this, single:true, fn:function() {
                    this.store.load();

                }}
        });

    } // eo function initComponent
    ,reload : function() {
        this.store.load();
    }
    ,moveRestriction:function(oldIndex,newIndex){

        var send_data = {"index":oldIndex,"to":newIndex};
        var method = this.method.replace('get_','move_');
        var title = this.restrictionTitle;

        var conn = new Ext.data.Connection({
            listeners:{
                // wait message.....
                beforerequest:function(){
                    Ext.MessageBox.show({
                        title: 'Please wait',
                        msg: 'Updating '+title+' order...',
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
            params:{id:this.service_id,method:method,params:Ext.encode(send_data)},
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

                var msg = title+' order successfully modified';
                Ext.ux.Logger.info(msg);
                View.notify({html:msg});
                this.reload();


            },scope:this
        });// END Ajax request

    }
    ,// call delete stuff now
    // Server side will receive delData throught parameter
    deleteData : function (items) {
        var title = this.restrictionTitle;
        var conn = new Ext.data.Connection({
            listeners:{
                // wait message.....
                beforerequest:function(){
                    Ext.MessageBox.show({
                        title: 'Please wait',
                        msg: 'Deleting '+title+'(s)...',
                        width:300,
                        wait:true,
                        modal: false
                    });
                },// on request complete hide message
                requestcomplete:function(){Ext.MessageBox.hide();}
            }
        });// end conn

        var rests = [];

        for(var i=0,len = items.length;i<len;i++){
            rests[i] = items[i].data.index;
        }

        var send_data = {'indexes':rests};
        var method = this.method.replace('get_','del_');

        conn.request({
            url: this.url,
            params:{id:this.service_id,method:method,params:Ext.encode(send_data)},
            failure: function(resp,opt){
                var response = Ext.util.JSON.decode(resp.responseText);
                Ext.MessageBox.alert('Error Message', response['info']);
                Ext.ux.Logger.error(response['error']);

            },
            // everything ok...
            success: function(resp,opt){
                var msg = 'Deleted '+title+'(s)';
                Ext.ux.Logger.info(msg);
                View.notify({html:msg});
                this.reload();

            },scope:this
        });// END Ajax request

    }
    ,clearRestrictionTpl:function(mode){
        if(!mode) mode = '';
        this.ownerCt.fireEvent('clearRestrictionTpl',mode);
    }

});
Ext.reg('etfw_squid_restriction_grid', ETFW.Squid.Acl.Restriction_Grid);


ETFW.Squid.Acl.RestrictionTpl = function(config) {

    Ext.apply(this,config);

    this.acls_store = new Ext.data.JsonStore({
        url:<?php echo json_encode(url_for('etfw/json'))?>,
        baseParams:{id:this.service_id,method:'get_uniq_acl'},
        remoteSort: false,
        totalProperty: 'total',
        root: 'data',
        msg:"Loading remote data...",
        mask:"Loading remote data...",
        fields: [{name:'name',type:'string',sortType:'asUCString'}]
    });

    var dontmatch_acls = new Ext.ux.Multiselect({
        fieldLabel:"Don't match ACLs",
        valueField:"name",
        displayField:"name",
        name:'dontmatch',
        height:120,
        width:200,
        tbar:new Ext.ux.grid.TotalCountBar({
                store:this.acls_store
                ,displayInfo:false
            }),
        allowBlank:true,
        store:this.acls_store
    });

    var match_acls = new Ext.ux.Multiselect({
        fieldLabel:"Match ACLs",
        valueField:"name",
        displayField:"name",
        name:'match',
        height:120,
        width:200,
        tbar:new Ext.ux.grid.TotalCountBar({
                store:this.acls_store
                ,displayInfo:false
            }),
        allowBlank:true,
        store:this.acls_store
    });


    var allFields = [{xtype:'fieldset',
                        title:this.fieldsetTitle,
                        defaultType:'textfield',
                        items:[
                                {xtype:'hidden',name:'index'}
                                ,{xtype:'radiogroup',
                                    name:'action',
                                    fieldLabel:'Action',
                                    height:15,width:200,
                                    columns:[50,50],
                                    items:[
                                        {xtype:'radio', name:'action', checked:true, inputValue:'allow', boxLabel:'Allow'},
                                        {xtype:'radio', name:'action',inputValue:'deny', boxLabel:'Deny'}]
                                }
                           ,match_acls
                           ,dontmatch_acls
                        ]
                    }];





    this.savebtn = new Ext.Button({text: 'Add',handler:this.onSave,scope:this});


    ETFW.Squid.Acl.RestrictionTpl.superclass.constructor.call(this, {
        // baseCls: 'x-plain',
        labelWidth: 100,
        bodyStyle:'padding-top:10px',
        url:<?php echo json_encode(url_for('etfw/json'))?>,
        defaultType: 'textfield',
        buttonAlign:'center',
        border:false,
        defaults:{border:false},
        items: [allFields]
        ,buttons: [this.savebtn]
    });

    // load the store at the latest possible moment
    this.on({
        afterlayout:{scope:this, single:true, fn:function() {
                //bind mask to store on load...
                new Ext.LoadMask(match_acls.getEl(), {msg:"Loading remote data...",store:this.acls_store});
                new Ext.LoadMask(dontmatch_acls.getEl(), {msg:"Loading remote data...",store:this.acls_store});
                this.acls_store.load();
            }}
    });


};

Ext.extend(ETFW.Squid.Acl.RestrictionTpl, Ext.form.FormPanel, {
    reset:function(){

        this.savebtn.setText('Add');
        this.getForm().reset();
    }
    ,reload:function(){
        this.acls_store.load();
    }
    ,loadRecord:function(rec){

        this.getForm().reset();

        this.savebtn.setText('Save');

        this.getForm().getEl().mask('Loading data...');
        (function(){
            this.getForm().loadRecord(rec);
            this.getForm().getEl().unmask();
        }).defer(100,this);

    }
    ,onSave:function(){

        if (this.form.isValid()) {

            var alldata = this.form.getValues();
            var title = this.fieldsetTitle;
            var send_data = new Object();
            var method = this.method;

            if(!Ext.isEmpty(alldata['index'])){
                send_data['index'] = alldata['index'];
            }else{
                method = this.method.replace('set_','add_');
            }

            send_data[alldata['action']] = 1;

            if(!Ext.isEmpty(alldata['match'])) send_data['match'] = alldata['match'].split(',');
            if(!Ext.isEmpty(alldata['dontmatch'])) send_data['dontmatch'] = alldata['dontmatch'].split(',');

            var conn = new Ext.data.Connection({
                listeners:{
                    // wait message.....
                    beforerequest:function(){
                        Ext.MessageBox.show({
                            title: 'Please wait',
                            msg: 'Updating '+title+' information...',
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
                params:{id:this.service_id,method:method,
                    params:Ext.encode(send_data)
                },
                failure: function(resp,opt){
                    var response = Ext.util.JSON.decode(resp.responseText);
                    Ext.MessageBox.alert('Error Message', response['info']);
                    Ext.ux.Logger.error(response['error']);

                },
                // everything ok...
                success: function(resp,opt){
                    var msg = 'Updated '+title;
                    Ext.ux.Logger.info(msg);
                    View.notify({html:msg});
                    this.ownerCt.fireEvent('reloadRestriction');


                },scope:this
            });// END Ajax request


        } else{
            Ext.MessageBox.alert('error', 'Please fix the errors noted.');
        }

    }
});






/*
 *
 *
 * EXTERNAL ACL PROGRAMS
 *
 *
 */


ETFW.Squid.Acl.ExternalAclTpl = function(config) {

    Ext.apply(this,config);


    this.allFields = [{xtype:'fieldset',
                        title:'External ACL program details',
                        defaultType:'textfield',
                        defaults:{border:false},
                        items:[
                                {xtype:'hidden',name:'index'}
                                ,{xtype:'hidden',name:'type'}
                                ,{fieldLabel:'Program type name',name:'name'}
                                ,{fieldLabel:'Input format string',name:'format',anchor:'90%'}
                                ,{
                                    layout:'table',
                                    xtype:'panel',
                                    layoutConfig: {columns: 4},
                                    defaults:{layout:'form',border:false,height:40},
                                    items: [
                                        {width:165,items:[{xtype:'radio', fieldLabel:'TTL for cached results',name:'ttl-radio',boxLabel:'Default',checked:true,inputValue: 0}]},
                                        {items:[{xtype:'radio', fieldLabel:'',hideLabel:true,name:'ttl-radio',boxLabel:'',inputValue: 1}]},
                                        {bodyStyle:'padding-bottom:5px;padding-left:5px;',items:[{xtype:'textfield',fieldLabel:'',hideLabel:true,width:60,name:'ttl'}]},
                                        {bodyStyle:'padding-bottom:5px;padding-left:5px;',items:[{xtype:'displayfield',labelSeparator:'',fieldLabel:'seconds'}]}
                                    ]
                                  }
                                 ,{
                                    layout:'table',
                                    xtype:'panel',
                                    layoutConfig: {columns: 4},
                                    defaults:{layout:'form',height:40,border:false},
                                    items: [
                                        {width:165,items:[{xtype:'radio', fieldLabel:'TTL for cached negative results',name:'negative_ttl-radio',boxLabel:'Default',checked:true,inputValue: 0}]},
                                        {items:[{xtype:'radio', fieldLabel:'',hideLabel:true,name:'negative_ttl-radio',boxLabel:'',inputValue: 1}]},
                                        {bodyStyle:'padding-left:5px;',items:[{xtype:'textfield',fieldLabel:'',hideLabel:true,width:60,name:'negative_ttl'}]},
                                        {bodyStyle:'padding-left:5px;',items:[{xtype:'displayfield',labelSeparator:'',fieldLabel:'seconds'}]}
                                    ]
                                  }
                                 ,{
                                    layout:'table',
                                    xtype:'panel',
                                    layoutConfig: {columns: 4},
                                    defaults:{layout:'form',height:40,border:false},
                                    items: [
                                        {width:165,items:[{xtype:'radio', fieldLabel:'Number of programs to run',name:'concurrency-radio',boxLabel:'Default',checked:true,inputValue: 0}]},
                                        {items:[{xtype:'radio', fieldLabel:'',hideLabel:true,name:'concurrency-radio',boxLabel:'',inputValue: 1}]},
                                        {bodyStyle:'padding-left:5px;',items:[{xtype:'textfield',fieldLabel:'',hideLabel:true,width:60,name:'concurrency'}]},
                                        {bodyStyle:'padding-left:5px;',items:[{xtype:'displayfield',labelSeparator:'',fieldLabel:'programs'}]}
                                    ]
                                  }
                                ,{
                                    layout:'table',
                                    xtype:'panel',
                                    layoutConfig: {columns: 4},
                                    defaults:{layout:'form',border:false},
                                    items: [
                                        {width:165,items:[{xtype:'radio', fieldLabel:'Cache size',name:'cache-radio',boxLabel:'Default',checked:true,inputValue: 0}]},
                                        {items:[{xtype:'radio', fieldLabel:'',hideLabel:true,name:'cache-radio',boxLabel:'',inputValue: 1}]},
                                        {bodyStyle:'padding-bottom:5px;padding-left:5px;',items:[{xtype:'textfield',fieldLabel:'',hideLabel:true,width:60,name:'cache'}]},
                                        {bodyStyle:'padding-bottom:5px;padding-left:5px;',items:[{xtype:'displayfield',labelSeparator:'',fieldLabel:'bytes'}]}
                                    ]
                                 }
                                ,{fieldLabel:'Program path and arguments',name:'path',anchor:'90%'}
                        ]
                    }];





    this.savebtn = new Ext.Button({text: 'Add',handler:this.onSave,scope:this});


    ETFW.Squid.Acl.ExternalAclTpl.superclass.constructor.call(this, {
        // baseCls: 'x-plain',
        labelWidth: 100,
        bodyStyle:'padding-top:10px;',
        url:<?php echo json_encode(url_for('etfw/json'))?>,
        defaultType: 'textfield',
        buttonAlign:'center',
        border:false,
        defaults:{border:false},
        items: [this.allFields]
        ,buttons: [this.savebtn]
    });

};

Ext.extend(ETFW.Squid.Acl.ExternalAclTpl, Ext.form.FormPanel, {
    reset:function(){
        this.savebtn.setText('Add');
        this.getForm().reset();
    }
    ,loadRecord:function(rec){

        this.getForm().reset();

        var options = rec.data['options'];
        for(var opt in options){
            rec.data[opt+'-radio'] = 1;
            rec.data[opt] = options[opt];

        }

        var path_vals = [rec.data['helper']];
        if(!Ext.isEmpty(rec.data['args'])) path_vals.push(rec.data['args'].join(' '));
        rec.data['path'] = path_vals.join(' ');


        this.savebtn.setText('Save');

        this.getForm().getEl().mask('Loading data...');
        (function(){
            this.getForm().loadRecord(rec);
            this.getForm().getEl().unmask();
        }).defer(100,this);

    }
    ,onSave:function(){

        if (this.form.isValid()) {

            var alldata = this.form.getValues();
            var params = [];
            var options = new Object();
            var send_data = new Object();
            var method = 'set_external_acl_type';

            if(!Ext.isEmpty(alldata['index'])){
                send_data['index'] = alldata['index'];
            }else{
                method = 'add_external_acl_type';
            }

            send_data['name'] = alldata['name'];
            send_data['format'] = alldata['format'];

            var splitted_path = alldata['path'].split(' ');

            send_data['helper'] = splitted_path.splice(0, 1).toString();
            send_data['args'] = splitted_path;


            if(alldata['ttl-radio']=='1') options['ttl'] = alldata['ttl'];
            if(alldata['negative_ttl-radio']=='1') options['negative_ttl'] = alldata['negative_ttl'];
            if(alldata['concurrency-radio']=='1') options['concurrency'] = alldata['concurrency'];
            if(alldata['cache-radio']=='1') options['cache'] = alldata['cache'];
            send_data['options'] = options;



            var conn = new Ext.data.Connection({
                listeners:{
                    // wait message.....
                    beforerequest:function(){
                        Ext.MessageBox.show({
                            title: 'Please wait',
                            msg: 'Updating external acl information...',
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
                params:{id:this.service_id,method:method,
                    params:Ext.encode(send_data)
                },
                failure: function(resp,opt){
                    var response = Ext.util.JSON.decode(resp.responseText);
                    Ext.MessageBox.alert('Error Message', response['info']);
                    Ext.ux.Logger.error(response['error']);

                },
                // everything ok...
                success: function(resp,opt){
                    var msg = 'Updated external ACL '+send_data['name'];
                    Ext.ux.Logger.info(msg);
                    View.notify({html:msg});
                    this.ownerCt.fireEvent('reloadExternalAcl');


                },scope:this
            });// END Ajax request


        } else{
            Ext.MessageBox.alert('error', 'Please fix the errors noted.');
        }



    }
});



/*
 * EXTERNAL ACL grid
 *
 */

ETFW.Squid.Acl.ExternalAcl_Grid = Ext.extend(Ext.grid.GridPanel, {
    initComponent:function() {

        // show check boxes
        var selectBoxModel= new Ext.grid.CheckboxSelectionModel({keepSelections:true});

        var dataStore = new Ext.data.JsonStore({
            url: this.url,
            //idProperty:'index',
            baseParams:{id:this.service_id,method:'get_external_acl_type'},
            remoteSort: false,
            totalProperty: 'total',
            root: 'data',
            fields: ['index',{name:'name', sortType:'asUCString',type:'string'},'format','helper','args','options']
        });

        // column model
        var cm = new Ext.grid.ColumnModel([
            selectBoxModel,
            {header: "Type name", width: 120, sortable: true, dataIndex: 'name',renderer: function (value, metadata, record, rowIndex, colIndex, store) {
                            metadata.attr = 'ext:qtip="Double-click to edit"';
                            return value;
            }},
            {header: "Input format", dataIndex: 'format', width:120, sortable: true},
            {header: "Handler program", dataIndex: 'helper', width:120,renderer:function(value,meta,rec){
                    var vals = [value];
                    if(!Ext.isEmpty(rec.data['args'])) vals.push(rec.data['args'].join(' '));
                    return vals.join(' ');
            }, sortable: true}
        ]);

        var config = {
            store:dataStore
            ,cls:'gridWrap'
            ,cm:cm
            ,sm:selectBoxModel
            ,viewConfig:{forceFit:true}
            ,loadMask:true
        }; // eo config object

        // apply config
        Ext.apply(this, Ext.apply(this.initialConfig, config));

        this.bbar = new Ext.ux.grid.TotalCountBar({
            store:this.store
            ,displayInfo:true
        });

        this.tbar = [
                {
                    tooltip:'Click here to add external ACL program',
                    text:'Create new External ACL',
                    iconCls:'add',
                    handler:function(){this.clearExternalAclTpl('show');},
                    scope:this

                }
                ,'-'
                ,{
                    ref: '../editBtn',
                    text:'Edit',
                    tooltip:'Edit the select item',
                    disabled:true,
                    handler: function(item){
                        var selected = item.ownerCt.ownerCt.getSelectionModel().getSelected();
                        this.ownerCt.fireEvent('loadExternalAclTplRecord',selected);
                    },scope:this
                }
                ,'-'
                ,{
                    ref: '../removeBtn',
                    text:'Delete',
                    tooltip:'Delete the selected item(s)',
                    iconCls:'remove',
                    disabled:true,
                    handler: function(){
                        new Grid.util.DeleteItem({panel: this.id});
                    },scope:this
                }];

        // call parent
        ETFW.Squid.Acl.ExternalAcl_Grid.superclass.initComponent.apply(this, arguments);

        this.getSelectionModel().on('selectionchange', function(sm){
            this.editBtn.setDisabled(sm.getCount() < 1);
            this.removeBtn.setDisabled(sm.getCount() < 1);
        },this);

        this.on('rowdblclick', function(gridPanel, rowIndex, e) {
            var selected = this.store.data.items[rowIndex];

            this.ownerCt.fireEvent('loadExternalAclTplRecord',selected);
        });

        /************************************************************
         * handle contextmenu event
         ************************************************************/
        this.addListener("rowcontextmenu", onContextMenu, this);
        function onContextMenu(grid, rowIndex, e) {
            if (!this.menu) {
                this.menu = new Ext.menu.Menu({
                    // id: 'menus',
                    items: [{
                            text:'Delete',
                            tooltip:'Delete the selected item(s)',
                            iconCls:'remove',
                            handler: function(){
                                new Grid.util.DeleteItem({panel: grid.id});
                            }
                        }]
                });
            }
            e.stopEvent();
            this.menu.showAt(e.getXY());
        }

        this.store.on('load',function(){this.clearExternalAclTpl()},this,{delay:200});

        // load the store at the latest possible moment
        this.on({
            afterlayout:{scope:this, single:true, fn:function() {
                    this.store.load();
                }}
        });

    } // eo function initComponent
    ,reload : function() {
        this.store.load();
    }
    ,// call delete stuff now
    // Server side will receive delData throught parameter
    deleteData : function (items) {

        var conn = new Ext.data.Connection({
            listeners:{
                // wait message.....
                beforerequest:function(){
                    Ext.MessageBox.show({
                        title: 'Please wait',
                        msg: 'Deleting external ACL(s)...',
                        width:300,
                        wait:true,
                        modal: false
                    });
                },// on request complete hide message
                requestcomplete:function(){Ext.MessageBox.hide();}
            }
        });// end conn

        var acls = [];

        for(var i=0,len = items.length;i<len;i++){
            acls[i] = items[i].data.index;
        }

        var send_data = {'indexes':acls};

        conn.request({
            url: this.url,
            params:{id:this.service_id,method:'del_external_acl_type',params:Ext.encode(send_data)},
            failure: function(resp,opt){
                var response = Ext.util.JSON.decode(resp.responseText);
                Ext.MessageBox.alert('Error Message', response['info']);
                Ext.ux.Logger.error(response['error']);

            },
            // everything ok...
            success: function(resp,opt){
                var msg = 'Deleted external ACL(s)';
                Ext.ux.Logger.info(msg);
                View.notify({html:msg});
                this.reload();

            },scope:this
        });// END Ajax request

    }
    ,clearExternalAclTpl:function(mode){
        if(!mode) mode = '';
        this.ownerCt.fireEvent('clearExternalAclTpl',mode);
    }

});
Ext.reg('etfw_squid_external_aclgrid', ETFW.Squid.Acl.ExternalAcl_Grid);




ETFW.Squid.Acl.Main = function(service_id) {


    var acl_panel = new Ext.Panel({
        title:'Access control lists',
        layout:'border',
        defaults: {
                collapsible: true,
                split: true,
                useSplitTips:true,
                border:false
        },
        items:[
            {
                url:<?php echo json_encode(url_for('etfw/json'))?>
                ,region:'center'
                ,collapsible: false
                ,margins: '3 0 3 3'
                ,service_id:service_id
                ,xtype:'etfw_squid_aclgrid'
                ,layout:'fit'
            },
            {region:'east',
                margins: '3 3 3 0',
                cmargins: '3 3 3 3',
                border:true,
                autoScroll:true,
                collapsed:true,
                width:400,
                title:'Create ACL',
                defaults:{border:false},
                items:[],
                listeners:{
                    beforeexpand:function(){
                        Ext.getBody().mask('Expanding panel...');
                    }
                    ,expand:{fn:function(){
                        Ext.getBody().unmask();
                    },delay:100}
                    ,beforecollapse:function(){
                        Ext.getBody().mask('Collapsing panel...');
                    }
                    ,collapse:{fn:function(){
                        Ext.getBody().unmask();
                    },delay:100}
                    ,reloadAcl:function(){
                            acl_panel.get(0).reload();
                    }
                }
            }
        ]
    });

    acl_panel.on({
        beforerender:function(){
            Ext.getBody().mask('Loading ETFW squid acl panel...');
        }
        ,render:{delay:100,fn:function(){
            Ext.getBody().unmask();
        }}
        ,afterlayout:{scope:this, single:true, fn:function() {
            acl_panel.get(0).setAclTpl();
        }}
        ,loadAclTplRecord:function(record){

            var right_region = acl_panel.get(1);

            right_region.setTitle('Edit ACL');
            var tpl = record.data['type'];

            right_region.items.each(function(ct){ // hide all items
                ct.hide();
            });

            var container = right_region.find('type',tpl);

            if(Ext.isEmpty(container)){ //if template not in panel, add it!
                var newElem = new ETFW.Squid.Acl.AclTpl({service_id:service_id,panel:right_region,type:tpl});

                right_region.add(newElem);
                        right_region.doLayout();
                        newElem.show();

                if(!right_region.isVisible()){
                    Ext.getBody().mask('Loading panel...');

                    (function(){
                        right_region.expand(false);
                        newElem.loadRecord(record);
                    }).defer(100);
                }
                else newElem.loadRecord(record);

            }else{
                container[0].show();

                if(!right_region.isVisible()){
                    Ext.getBody().mask('Loading panel...');

                    (function(){
                        right_region.expand(false);
                        container[0].loadRecord(record);
                    }).defer(100);
                }
                else container[0].loadRecord(record);

            }

        }
        ,loadAclTpl:function(tpl,mode){
            var right_region = acl_panel.get(1);

            right_region.setTitle('Create ACL');

            right_region.items.each(function(ct){ // hide all items
                    ct.hide();
            });

            var container = right_region.find('type',tpl);

            if(Ext.isEmpty(container)){ //if template not in panel, add it!
                var newElem = new ETFW.Squid.Acl.AclTpl({service_id:service_id,panel:right_region,type:tpl});
                right_region.add(newElem);
                right_region.doLayout();

                if(mode == 'show' && !right_region.isVisible()){
                    Ext.getBody().mask('Loading panel...');

                    (function(){
                        right_region.expand(false);
                        newElem.show();
                    }).defer(100);
                }
                else newElem.show();

            }else{

                if(mode == 'show' && !right_region.isVisible()){
                    Ext.getBody().mask('Loading panel...');

                    (function(){
                        right_region.expand(false);
                        container[0].show();
                    }).defer(100);
                }
                else container[0].show();

            }


        }
       });


    /*
    *
    * proxy restrictions panel
    *
    */
    var proxy_rest_form = new ETFW.Squid.Acl.RestrictionTpl({fieldsetTitle:'Proxy Restriction',method:'set_http_access',service_id:service_id});

    var proxy_rest_panel = new Ext.Panel({
        title:'Proxy restrictions',
        layout:'border',
        defaults: {
                collapsible: true,
                split: true,
                border:false,
                useSplitTips:true
        },
        items:[
            {
                url:<?php echo json_encode(url_for('etfw/json'))?>
                ,region:'center'
                ,collapsible: false
                ,service_id:service_id
                ,xtype:'etfw_squid_restriction_grid'
                ,restrictionTitle: 'Proxy Restriction'
                ,method:'get_http_access'
                ,margins: '3 0 3 3'
                ,layout:'fit'
            },
            {region:'east',
                margins: '3 3 3 0',
                cmargins: '3 3 3 3',
                autoScroll:true,
                collapsed:true,
                title:'Create Proxy Restriction',
                width:350,
                border:true,
                defaults:{border:false},
                items:[proxy_rest_form],
                listeners:{
                    beforeexpand:function(){
                        Ext.getBody().mask('Expanding panel...');
                    }
                    ,expand:{fn:function(){
                        Ext.getBody().unmask();
                    },delay:100}
                    ,beforecollapse:function(){
                        Ext.getBody().mask('Collapsing panel...');
                    }
                    ,collapse:{fn:function(){
                        Ext.getBody().unmask();
                    },delay:100}
                    ,reloadRestriction:function(){
                            proxy_rest_panel.get(0).reload();
                    }
                }
            }
        ]
    });

    proxy_rest_panel.on({
        beforerender:function(){
            Ext.getBody().mask('Loading ETFW squid proxy restrictions panel...');
        }
        ,render:{delay:100,fn:function(){
            Ext.getBody().unmask();
        }}
        ,afterlayout:{scope:this, single:true, fn:function() {
            acl_panel.get(0).setAclTpl();
        }}
        ,loadRestrictionTplRecord:function(record){
            var right_region = proxy_rest_panel.get(1);
            right_region.setTitle('Edit Proxy Restriction');

            if(!right_region.isVisible()){
                    Ext.getBody().mask('Loading panel...');

                    (function(){
                        right_region.expand(false);
                        proxy_rest_form.loadRecord(record);
                    }).defer(100);
            }
            else proxy_rest_form.loadRecord(record);
        }
        ,clearRestrictionTpl:function(mode){
            var right_region = proxy_rest_panel.get(1);
            right_region.setTitle('Create Proxy Restriction');

            if(mode == 'show' && !right_region.isVisible()){
                    Ext.getBody().mask('Loading panel...');

                    (function(){
                        right_region.expand(false);
                        proxy_rest_form.reset();
                    }).defer(100);
            }
            else proxy_rest_form.reset();
        }
    });


    /*
    *
    * icp restrictions panel
    *
    */
    var icp_rest_form = new ETFW.Squid.Acl.RestrictionTpl({fieldsetTitle:'ICP Restriction',method:'set_icp_access',service_id:service_id});

    var icp_rest_panel = new Ext.Panel({
        title:'ICP restrictions',
        layout:'border',
        defaults: {
                collapsible: true,
                split: true,
                border:false,
                useSplitTips:true
        },
        items:[
            {
                url:<?php echo json_encode(url_for('etfw/json'))?>
                ,region:'center'
                ,collapsible: false
                ,service_id:service_id
                ,xtype:'etfw_squid_restriction_grid'
                ,restrictionTitle: 'ICP Restriction'
                ,method:'get_icp_access'
                ,margins: '3 0 3 3'
                ,layout:'fit'
            },
            {region:'east',
                margins: '3 3 3 0',
                cmargins: '3 3 3 3',
                border:true,
                autoScroll:true,
                title:'Create ICP Restriction',
                collapsed:true,
                width:350,
                items:[icp_rest_form],
                listeners:{
                    beforeexpand:function(){
                        Ext.getBody().mask('Expanding panel...');
                    }
                    ,expand:{fn:function(){
                        Ext.getBody().unmask();
                    },delay:100}
                    ,beforecollapse:function(){
                        Ext.getBody().mask('Collapsing panel...');
                    }
                    ,collapse:{fn:function(){
                        Ext.getBody().unmask();
                    },delay:100}
                    ,reloadRestriction:function(){
                            icp_rest_panel.get(0).reload();
                    }
                }
            }
        ]
    });

    icp_rest_panel.on({
        beforerender:function(){
            Ext.getBody().mask('Loading ETFW squid icp restrictions panel...');
        }
        ,render:{delay:100,fn:function(){
            Ext.getBody().unmask();
        }}
        ,loadRestrictionTplRecord:function(record){
            var right_region = icp_rest_panel.get(1);
            right_region.setTitle('Edit ICP Restriction');

            if(!right_region.isVisible()){
                    Ext.getBody().mask('Loading panel...');

                    (function(){
                        right_region.expand(false);
                        icp_rest_form.loadRecord(record);
                    }).defer(100);
            }
            else icp_rest_form.loadRecord(record);
        }
        ,clearRestrictionTpl:function(mode){
            var right_region = icp_rest_panel.get(1);
            right_region.setTitle('Create ICP Restriction');

            if(mode == 'show' && !right_region.isVisible()){
                    Ext.getBody().mask('Loading panel...');

                    (function(){
                        right_region.expand(false);
                        icp_rest_form.reset();
                    }).defer(100);
            }
            else icp_rest_form.reset();

        }
    });




    /*
    *
    * external acl programs panel
    *
    */
    var external_acl_form = new ETFW.Squid.Acl.ExternalAclTpl({service_id:service_id});

    var external_acl_panel = new Ext.Panel({
        title:'External ACL Programs',
        layout:'border',
        defaults: {
                collapsible: true,
                split: true,
                border:false,
                useSplitTips:true
        },
        items:[
            {
                url:<?php echo json_encode(url_for('etfw/json'))?>
                ,region:'center'
                ,collapsible: false
                ,service_id:service_id
                ,xtype:'etfw_squid_external_aclgrid'
                ,margins: '3 0 3 3'
                ,layout:'fit'
            },
            {region:'east',
                margins: '3 3 3 0',
                cmargins: '3 3 3 3',
                border:true,
                autoScroll:true,
                collapsed:true,
                title:'Create External Program',
                width:350,
                items:[external_acl_form],
                listeners:{
                    beforeexpand:function(){
                        Ext.getBody().mask('Expanding panel...');
                    }
                    ,expand:{fn:function(){
                        Ext.getBody().unmask();
                    },delay:100}
                    ,beforecollapse:function(){
                        Ext.getBody().mask('Collapsing panel...');
                    }
                    ,collapse:{fn:function(){
                        Ext.getBody().unmask();
                    },delay:100}
                    ,reloadExternalAcl:function(){
                            external_acl_panel.get(0).reload();
                    }
                }
            }
        ]
    });



    external_acl_panel.on({
        beforerender:function(){
            Ext.getBody().mask('Loading ETFW squid acl programs panel...');
        }
        ,render:{delay:100,fn:function(){
            Ext.getBody().unmask();
        }}
        ,loadExternalAclTplRecord:function(record){
            var right_region = external_acl_panel.get(1);
            right_region.setTitle('Edit External Program');

            if(!right_region.isVisible()){
                    Ext.getBody().mask('Loading panel...');

                    (function(){
                        right_region.expand(false);
                        external_acl_form.loadRecord(record);
                    }).defer(100);
            }
            else external_acl_form.loadRecord(record);
        }
        ,clearExternalAclTpl:function(mode){
            var right_region = external_acl_panel.get(1);
            right_region.setTitle('Create External Program');

            if(mode == 'show' && !right_region.isVisible()){
                    Ext.getBody().mask('Loading panel...');

                    (function(){
                        right_region.expand(false);
                        external_acl_form.reset();
                    }).defer(100);
            }
            else external_acl_form.reset();

        }
    });


    /*
    *
    * reply proxy restrictions panel
    *
    */
    var reply_rest_form = new ETFW.Squid.Acl.RestrictionTpl({fieldsetTitle:'Proxy Reply Restriction',method:'set_http_reply_access',service_id:service_id});

    var reply_rest_panel = new Ext.Panel({
        title:'Proxy Reply restrictions',
        layout:'border',
        defaults: {
                collapsible: true,
                split: true,
                border:false,
                useSplitTips:true
        },
        items:[
            {
                url:<?php echo json_encode(url_for('etfw/json'))?>
                ,region:'center'
                ,collapsible: false
                ,service_id:service_id
                ,xtype:'etfw_squid_restriction_grid'
                ,restrictionTitle: 'Proxy Reply Restriction'
                ,method:'get_http_reply_access'
                ,layout:'fit'
                ,margins: '3 0 3 3'
            },
            {region:'east',
                margins: '3 3 3 0',
                cmargins: '3 3 3 3',
                border:true,
                autoScroll:true,
                collapsed:true,
                title:'Create Proxy Reply Restriction',
                width:400,
                items:[reply_rest_form],
                listeners:{
                    beforeexpand:function(){
                        Ext.getBody().mask('Expanding panel...');
                    }
                    ,expand:{fn:function(){
                        Ext.getBody().unmask();
                    },delay:100}
                    ,beforecollapse:function(){
                        Ext.getBody().mask('Collapsing panel...');
                    }
                    ,collapse:{fn:function(){
                        Ext.getBody().unmask();
                    },delay:100}
                    ,reloadRestriction:function(){
                            reply_rest_panel.get(0).reload();
                    }
                }
            }
        ]
    });

    reply_rest_panel.on({
        beforerender:function(){
            Ext.getBody().mask('Loading ETFW squid proxy reply panel...');
        }
        ,render:{delay:100,fn:function(){
            Ext.getBody().unmask();
        }}
        ,loadRestrictionTplRecord:function(record){
            var right_region = reply_rest_panel.get(1);
            right_region.setTitle('Edit Proxy Reply Restriction');

            if(!right_region.isVisible()){
                    Ext.getBody().mask('Loading panel...');

                    (function(){
                        right_region.expand(false);
                        reply_rest_form.loadRecord(record);
                    }).defer(100);
            }
            else reply_rest_form.loadRecord(record);
        }
        ,clearRestrictionTpl:function(mode){
            var right_region = reply_rest_panel.get(1);
            right_region.setTitle('Create Proxy Reply Restriction');

            if(mode == 'show' && !right_region.isVisible()){
                    Ext.getBody().mask('Loading panel...');

                    (function(){
                        right_region.expand(false);
                        reply_rest_form.reset();
                    }).defer(100);
            }
            else reply_rest_form.reset();
        }
    });



    ETFW.Squid.Acl.Main.superclass.constructor.call(this, {
        border:false,
        layout:'fit',
        title: 'Access control',
        defaults:{border:false},
        items: [{
                xtype:'tabpanel',layoutOnTabChange:true,
                activeTab:0,
                items:[acl_panel
                    ,proxy_rest_panel
                    ,icp_rest_panel
                    ,external_acl_panel
                    ,reply_rest_panel
                ]
            }]


    });

}

// define public methods
Ext.extend(ETFW.Squid.Acl.Main, Ext.Panel, {
    reload:function(){
        var tabPanel = this.get(0);

        var aclPanel = tabPanel.get(0);
        var proxyPanel = tabPanel.get(1);
        var icpPanel = tabPanel.get(2);
        var extPanel = tabPanel.get(3);
        var proxy_repPanel = tabPanel.get(4);


        if(aclPanel.rendered){
            var grid_acl = aclPanel.get(0);
            grid_acl.reload();
        }

        if(extPanel.rendered){
            var grid_ext = extPanel.get(0);
            grid_ext.reload();
        }

        if(proxyPanel.rendered){
            var grid_proxy = proxyPanel.get(0);
            var formPanel_proxy = proxyPanel.get(1);
                grid_proxy.reload();
                (formPanel_proxy.get(0)).reload();
        }

        if(icpPanel.rendered){
            var grid_icp = icpPanel.get(0);
            var formPanel_icp = icpPanel.get(1);
                grid_icp.reload();
                (formPanel_icp.get(0)).reload();
        }

        if(proxy_repPanel.rendered){
            var grid_proxy_rep = proxy_repPanel.get(0);
            var formPanel_proxy_rep = proxy_repPanel.get(1);
                grid_proxy_rep.reload();
                (formPanel_proxy_rep.get(0)).reload();
        }





    }

});

</script>
