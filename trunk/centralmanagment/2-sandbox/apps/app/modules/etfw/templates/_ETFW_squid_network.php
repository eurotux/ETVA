<script>

/*
 * new grid plugin for ip address column
 */

Ext.ns('Ext.ux.grid');

Ext.ux.grid.CustomIPColumn = function(config){
    Ext.apply(this, config);
    if(!this.id){
        this.id = Ext.id();
    }
    this.renderer = this.renderer.createDelegate(this);
};

Ext.ux.grid.CustomIPColumn.prototype ={
    init : function(grid){
        this.grid = grid;
        this.grid.on('render', function(){
            var view = this.grid.getView();
            view.mainBody.on('mousedown', this.onMouseDown, this);
        }, this);
    },

    onMouseDown : function(e, t){


        if(t.className && t.className.indexOf('x-grid3-ipc-'+this.id) != -1){

            var index = this.grid.getView().findRowIndex(t);
            var record = this.grid.store.getAt(index);

            if(t.type=='radio'){

                var cur_value = Ext.get('txt-'+this.id+'-'+record.id).getValue();

                if(t.value=='1') // all radio checked
                {
                    record.set(this.dataIndex, '');
                    (Ext.get('txt-'+this.id+'-'+record.id).dom).value = cur_value;
                }
                else  // custom checked
                {
                    record.set(this.dataIndex, Ext.get('txt-'+this.id+'-'+record.id).getValue());
                }
            }

            if(t.type=='text'){
                var ip_regexp = /^(([1-9][0-9]{0,2})|0)\.(([1-9][0-9]{0,2})|0)\.(([1-9][0-9]{0,2})|0)\.(([1-9][0-9]{0,2})|0)$/;

                var notall = Ext.get('radio-notall-'+this.id+'-'+record.id);


                //on ip/hostname change set grid record
                Ext.get(t.id).on('change',function(){

                    if(!ip_regexp.test(t.value)){
                        t.value='';
                        record.set(this.dataIndex, '');
                        return;
                    }

                    //if custom radio checked then assume textfield value for grid
                    if(notall.dom.checked)
                        record.set(this.dataIndex, t.value);
                    },this);

            }
        }
    },

    renderer : function(v, p, record){

        return '<div class="x-grid3-ipc-'+this.id+'">'+
                    '<div class="x-column" style="width:40px" >'+
                        '<div class="x-form-item">'+
                            '<input class="x-grid3-ipc-'+this.id+'" type="radio" '+(v?'':'checked')+' name="ip_all-'+record.id+'" value=1 />'+
                            '<label class="x-form-cb-label">All</label>'+
                        '</div>'+
                    '</div>'+
                    '<div class="x-column" style="width:20px" >'+
                        '<div class="x-form-item">'+
                            '<input id="radio-notall-'+this.id+'-'+record.id+'" class="x-grid3-ipc-'+this.id+'" type="radio" '+(v?'checked':'')+' name="ip_all-'+record.id+'" value=0 />'+
                        '</div>'+
                    '</div>'+
                    '<div class="x-column" style="width:100px" >'+
                        '<div class="x-form-item">'+
                            '<input id="txt-'+this.id+'-'+record.id+'" style="width:90px;"  class="x-form-text x-form-field x-form-focus x-grid3-ipc-'+this.id+'" name="ip_addresfs" value="'+v+'" type="text" />'+
                        '</div>'+
                    '</div>'+
                '</div>';
    }
};

// register ptype
Ext.preg('customIPcolumn', Ext.ux.grid.CustomIPColumn);
Ext.grid.CustomIPColumn = Ext.ux.grid.CustomIPColumn;

/*
*
* end new column type
*/



Ext.ns('ETFW.Squid.Network');


ETFW.Squid.Network.Form = function(service_id) {    

    this.service_id = service_id;

    this.params = ["http_port","https_port",
                    "icp_port","udp_outgoing_address","udp_incoming_address",
                    "tcp_outgoing_address",
                    "tcp_recv_bufsize",
                    "ssl_unclean_shutdown",
                    "allow_underscore",
                    "check_hostnames",
                    "mcast_groups"
                  ];


    this.refreshBtn = new Ext.Button({
                        text: 'Refresh',
                        tooltip: 'refresh',
                        iconCls: 'x-tbar-loading',
                        scope:this,
                        handler: function(button,event){this.loadData();}
                    });
    this.saveBtn = new Ext.Button({
                        text: 'Save',
                        tooltip: 'save',
                        scope:this,
                        handler: this.onSave
                    });

    /*
     * proxy ports grid
     *
     */
    var proxySelectBoxModel= new Ext.grid.CheckboxSelectionModel({keepSelections:true});

    var proxyCustomIPColumn = new Ext.grid.CustomIPColumn({
        header: 'Hostname/IP Address',
        allowBlank:false,
        dataIndex: 'addr',
        width: 130
    });


    // the column model has information about grid columns
    // dataIndex maps the column to the specific data field in
    // the data store (created below)
    var proxyPorts_cm = new Ext.grid.ColumnModel({
        // specify any defaults for each column
        defaults: {
            sortable: true // columns are not sortable by default
        },
        columns: [
            proxySelectBoxModel,
            {
                header: 'Port',
                dataIndex: 'port',
                width: 80,
                editor: new Ext.form.TextField({
                    allowBlank: false,
                    selectOnFocus:true
                })
            }
            ,proxyCustomIPColumn
            ,{
                header: 'Options for port',
                dataIndex: 'opts',
                id:'options',
                width: 150,
                align: 'right',
                editor: new Ext.form.TextField({
                    allowBlank: false
                })
            }
        ]
    });

    var proxyPorts_store = new Ext.data.Store({
        reader: new Ext.data.JsonReader({
            root:'data',
            fields:['port','addr','opts']
        })
        ,proxy:new Ext.data.HttpProxy({url:<?php echo json_encode(url_for('etfw/json')); ?>})
        ,baseParams:{id:this.service_id,method:'get_http_port'}
    });

    // create the grid
    this.proxyPorts_grid = new Ext.grid.EditorGridPanel({
        store: proxyPorts_store,
        cm: proxyPorts_cm,
        sm:proxySelectBoxModel,
        autoHeight: true,
        border:true,
        layout:'fit',
        autoExpandColumn:'options',
        viewConfig:{
            forceFit:true
            ,templates: {
                cell: new Ext.Template(
                    '<td class="x-grid3-col x-grid3-cell x-grid3-td-{id} x-selectable {css}" style="{style}" tabIndex="0" {cellAttr}>',
                    '<div class="x-grid3-cell-inner x-grid3-col-{id}" {attr}>{value}</div>',
                    '</td>'
                )
            }
            ,emptyText: 'Empty!',  //  emptyText Message
            deferEmptyText:false
        },
        plugins: [proxyCustomIPColumn],
        tbar: [{
                text: 'Add proxy port',
                handler : function(){

                    // access the Record constructor through the grid's store
                    var Proxy_port = this.proxyPorts_grid.getStore().recordType;
                    var port = new Proxy_port({
                        port: '',
                        addr: '',
                        opts: ''
                    });

                    this.proxyPorts_grid.stopEditing();
                    proxyPorts_store.insert(0, port);
                    this.proxyPorts_grid.startEditing(0,1);

                }
                ,scope:this}
            ,{
                ref: '../removeBtn',
                text: 'Remove proxy port',
                disabled: true,
                handler: function(){
                    this.proxyPorts_grid.stopEditing();
                    var s = this.proxyPorts_grid.getSelectionModel().getSelections();
                    for(var i = 0, r; r = s[i]; i++){
                        proxyPorts_store.remove(r);
                    }
                },scope:this
            }]
           ,bbar:new Ext.ux.grid.TotalCountBar({
            store:proxyPorts_store
            ,displayInfo:true
        })

    });

    this.proxyPorts_grid.getSelectionModel().on('selectionchange', function(sm){
        this.proxyPorts_grid.removeBtn.setDisabled(sm.getCount() < 1);
    },this);

    /*
     * END proxy ports grid
     */



    /*
     * ssl ports grid
     *
     */
    var sslSelectBoxModel= new Ext.grid.CheckboxSelectionModel({keepSelections:true});

    var sslCustomIPColumn = new Ext.grid.CustomIPColumn({
        header: 'Hostname/IP Address',
        allowBlank:false,
        dataIndex: 'addr',
        width: 130
    });


    // the column model has information about grid columns
    // dataIndex maps the column to the specific data field in
    // the data store (created below)
    var sslPorts_cm = new Ext.grid.ColumnModel({
        // specify any defaults for each column
        defaults: {
            sortable: true // columns are not sortable by default
        },
        columns: [
            sslSelectBoxModel,
            {
                header: 'Port',
                dataIndex: 'port',
                width: 80,
                editor: new Ext.form.TextField({
                    allowBlank: false,
                    selectOnFocus:true
                })
            }
            ,sslCustomIPColumn
            ,{
                header: 'Options for port',
                dataIndex: 'opts',
                id:'options',
                width: 150,
                align: 'right',
                editor: new Ext.form.TextField({
                    allowBlank: false
                })
            }
        ]
    });

    var sslPorts_store = new Ext.data.Store({
        reader: new Ext.data.JsonReader({
            root:'data',
            fields:['port','addr','opts']
        })
        ,proxy:new Ext.data.HttpProxy({url:<?php echo json_encode(url_for('etfw/json')); ?>})
        ,baseParams:{id:this.service_id,method:'get_https_port'}
    });

    // create the grid
    this.sslPorts_grid = new Ext.grid.EditorGridPanel({
        store: sslPorts_store,
        cm: sslPorts_cm,
        sm:sslSelectBoxModel,
        autoHeight: true,
        border:true,
        layout:'fit',
        autoExpandColumn:'options',
        viewConfig:{
            forceFit:true
            ,templates: {
                cell: new Ext.Template(
                    '<td class="x-grid3-col x-grid3-cell x-grid3-td-{id} x-selectable {css}" style="{style}" tabIndex="0" {cellAttr}>',
                    '<div class="x-grid3-cell-inner x-grid3-col-{id}" {attr}>{value}</div>',
                    '</td>'
                )
            }
            ,emptyText: 'Empty!',  //  emptyText Message
            deferEmptyText:false
        },
        plugins: [sslCustomIPColumn],
        tbar: [{
                text: 'Add ssl port',
                handler : function(){

                    // access the Record constructor through the grid's store
                    var Ssl_port = this.sslPorts_grid.getStore().recordType;
                    var port = new Ssl_port({
                        port: '',
                        addr: '',
                        opts: ''
                    });

                    this.sslPorts_grid.stopEditing();
                    sslPorts_store.insert(0, port);
                    this.sslPorts_grid.startEditing(0,1);

                }
                ,scope:this}
            ,{
                ref: '../removeBtn',
                text: 'Remove ssl port',
                disabled: true,
                handler: function(){
                    this.sslPorts_grid.stopEditing();
                    var s = this.sslPorts_grid.getSelectionModel().getSelections();
                    for(var i = 0, r; r = s[i]; i++){
                        sslPorts_store.remove(r);
                    }
                },scope:this
            }]
           ,bbar:new Ext.ux.grid.TotalCountBar({
            store:sslPorts_store
            ,displayInfo:true
        })

    });

    this.sslPorts_grid.getSelectionModel().on('selectionchange', function(sm){
        this.sslPorts_grid.removeBtn.setDisabled(sm.getCount() < 1);
    },this);

    /*
     * END ssl ports grid
     */

    var allFields = [
        {xtype:'fieldset',width:750,
        defaults:{border:false},
        title:'Ports and Networking options',
        items:[
            {xtype:'radiogroup',
                width:280,
                name:'http_port-src',
                fieldLabel:'Proxy addresses and ports',
                items:[
                    {xtype:'radio', name:'http_port-src-radio', checked:true, inputValue:'0', boxLabel:'Default (usually 3128)'},
                    {xtype:'radio', name:'http_port-src-radio',inputValue:'1', boxLabel:'Listed below...'}]
            },
            {layout:'column',
                fitHeight: true,
                defaults:{border:false},
                width:700,
                items:[
                        {
                        labelAlign:'left',
                        layout:'fit',
                        bodyStyle:'padding-left:150px;',
                        width:700,
                        items:this.proxyPorts_grid
                        }
                ]
            },
            {xtype:'radiogroup',
                width:280,
                name:'https_port-src',
                fieldLabel:'SSL addresses and ports',
                items:[
                    {xtype:'radio', name:'https_port-src-radio', checked:true, inputValue:'0', boxLabel:'Default (usually 3128)'},
                    {xtype:'radio', name:'https_port-src-radio',inputValue:'1', boxLabel:'Listed below...'}]
            },
            {layout:'column',
                fitHeight: true,
                defaults:{border:false},
                width:700,
                items:[
                        {
                        labelAlign:'left',
                        layout:'fit',
                        bodyStyle:'padding-left:150px;',
                        width:700,
                        items:this.sslPorts_grid
                        }
                ]
            }
            //split into two columns
           ,{
                layout:'column',
                width:700,
                fitHeight: true,
                defaults:{layout:'form',border:false},
                items:[
                    //1st col
                    {
                     columnWidth:.5,
                     items:[
                            //ICP port
                            this.buildDefaultItem('ICP port','Default','icp_port',100),
                            //Outgoing  UDP address
                            this.buildDefaultItem('Outgoing  UDP address','Any','udp_outgoing_address',100),
                            {
                                xtype:'textarea', name:'mcast_groups', fieldLabel:'Multicast groups'
                            }
                            ,
                            {xtype:'radiogroup',
                             width:80,
                             name:'check_hostnames',
                             fieldLabel:'Validate hostnames in URLs?',
                             items:[
                                    {xtype:'radio',name:'check_hostnames-radio',inputValue:'',boxLabel:'Yes'},
                                    {xtype:'radio',name:'check_hostnames-radio',inputValue:'off',boxLabel:'No'}]
                            }
                    ]}
                    //2nd col
                    ,{columnWidth:.5,bodyStyle:'padding-left:10px;',items:[
                            //Outgoing  TCP address
                            this.buildDefaultItem('Outgoing  TCP address','Any','tcp_outgoing_address',100),
                            //Incoming UDP address
                            this.buildDefaultItem('Incoming UDP address','Any','udp_incoming_address',100),
                            //TCP receive buffer
                            this.buildDefaultItem('TCP receive buffer','OS default','tcp_recv_bufsize',60),
                            {xtype:'radiogroup',
                             width:80,
                             name:'allow_underscore',
                             fieldLabel:'Allow underscore in hostnames?',
                             items:[
                                    {xtype:'radio',name:'allow_underscore-radio',inputValue:'',boxLabel:'Yes'},
                                    {xtype:'radio',name:'allow_underscore-radio',inputValue:'off',boxLabel:'No'}]
                            }
                            ,
                            {xtype:'radiogroup',
                             width:80,
                             fieldLabel:'Do unclean SSL shutdowns?',
                             name:'ssl_unclean_shutdown',
                             items:[
                                    {xtype:'radio',name:'ssl_unclean_shutdown-radio',inputValue:'on',boxLabel:'On'},
                                    {xtype:'radio',name:'ssl_unclean_shutdown-radio',inputValue:'off',boxLabel:'Off'}]
                            }
                    ]}
                ]
            }
        ]}
    ];

    ETFW.Squid.Network.Form.superclass.constructor.call(this, {
        labelWidth: 150,
        bodyStyle:'padding-top:10px',
        url:<?php echo json_encode(url_for('etfw/json'))?>,
        autoScroll:true,
        border:false,
        defaults:{border:false},
        tbar:[this.refreshBtn],
        items: allFields,
        bbar: [this.saveBtn]
    });

    // on loadRecord finished bind data to form correctly....
        this.on('actioncomplete',function(form,action){

            var rec_data = action.result.data; // data
            var http_port_src = this.find('name','http_port-src')[0]; // get component by name...
            if(!Ext.isEmpty(rec_data['http_port'].data)) http_port_src.setValue('1');
            else http_port_src.setValue('0');

            //load proxy ports grid
            proxyPorts_store.loadData(rec_data['http_port']);

            var https_port_src = this.find('name','https_port-src')[0]; // get component by name...
            if(!Ext.isEmpty(rec_data['https_port'].data)) https_port_src.setValue('1');
            else https_port_src.setValue('0');

            //load ssl ports grid
            sslPorts_store.loadData(rec_data['https_port']);

            var icp_port_src = this.find('name','icp_port-src')[0]; // get component by name...
            if(!Ext.isEmpty(rec_data['icp_port'])) icp_port_src.setValue('1');

            var udp_outgoing_src = this.find('name','udp_outgoing_address-src')[0]; // get component by name...
            if(!Ext.isEmpty(rec_data['udp_outgoing_address'])) udp_outgoing_src.setValue('1');

            var udp_incoming_src = this.find('name','udp_incoming_address-src')[0]; // get component by name...
            if(!Ext.isEmpty(rec_data['udp_incoming_address'])) udp_incoming_src.setValue('1');

            var tcp_outgoing_src = this.find('name','tcp_outgoing_address-src')[0]; // get component by name...
            if(!Ext.isEmpty(rec_data['tcp_outgoing_address'])) tcp_outgoing_src.setValue('1');

            var tcp_recv_bufsize_src = this.find('name','tcp_recv_bufsize-src')[0]; // get component by name...
            if(!Ext.isEmpty(rec_data['tcp_recv_bufsize'])) tcp_recv_bufsize_src.setValue('1');

            var ssl_unclean_shutdown = this.find('name','ssl_unclean_shutdown')[0]; // get component by name...
            if(Ext.isEmpty(rec_data['ssl_unclean_shutdown'])) ssl_unclean_shutdown.setValue('off');



        },this);//end actioncomplete

     this.on({
             afterlayout:{scope:this, single:true, delay:1000,fn:function() {this.loadData();}}
            });

};

Ext.extend(ETFW.Squid.Network.Form, Ext.form.FormPanel, {
    onRender:function() {
        // call parent
        ETFW.Squid.Network.Form.superclass.onRender.apply(this, arguments);

        // set wait message target
        this.getForm().waitMsgTarget = this.getEl();

    } //
    ,joinRecord:function(rec){
        var port = rec.data['port'];
        var ip_addr = rec.data['addr'];
        var options = Ext.isEmpty(rec.data['opts']) ? '' : ' '+rec.data['opts'];
        var data = '';

        if(!Ext.isEmpty(port)){
            //if port
            if(!Ext.isEmpty(ip_addr)) data = ip_addr+':'+port+options;
            else data = port+options;
        }else{
            //if not port
            if(!Ext.isEmpty(ip_addr)){
                data = ip_addr+options;
            }
        }

        return data;
    }
    ,onSave:function(){


        var alldata = this.form.getValues();
        var send_data = new Object();

        if (this.form.isValid()) {


            // proxy port grid data....
            var ds_proxyPorts = this.proxyPorts_grid.getStore();
            var total_proxyPortsRec = ds_proxyPorts.getCount();
            var recs_proxyPorts = [];

            Ext.each(ds_proxyPorts.getRange(0,total_proxyPortsRec),function(e){
                var data = this.joinRecord(e);
                recs_proxyPorts.push(data);
            },this);
            send_data['http_port'] = recs_proxyPorts;


            // ssl port grid data....
            var ds_sslPorts = this.sslPorts_grid.getStore();
            var total_sslPortsRec = ds_sslPorts.getCount();
            var recs_sslPorts = [];

            Ext.each(ds_sslPorts.getRange(0,total_sslPortsRec),function(e){
                var data = this.joinRecord(e);
                recs_sslPorts.push(data);
            },this);
            send_data['https_port'] = recs_sslPorts;


            // ICP port
            var icp_port_src = alldata['icp_port-src'];
            if(icp_port_src==1) send_data['icp_port'] = alldata['icp_port'];
            else send_data['icp_port'] = '';

            // UDP outgoing
            var udp_outgoing_address_src = alldata['udp_outgoing_address-src'];
            if(udp_outgoing_address_src==1) send_data['udp_outgoing_address'] = alldata['udp_outgoing_address'];
            else send_data['udp_outgoing_address'] = '';

            // UDP incoming
            var udp_incoming_address_src = alldata['udp_incoming_address-src'];
            if(udp_incoming_address_src==1) send_data['udp_incoming_address'] = alldata['udp_incoming_address'];
            else send_data['udp_incoming_address'] = '';

            // TCP outgoing
            var tcp_outgoing_address_src = alldata['tcp_outgoing_address-src'];
            if(tcp_outgoing_address_src==1) send_data['tcp_outgoing_address'] = alldata['tcp_outgoing_address'];
            else send_data['tcp_outgoing_address'] = '';

            // TCP receive buffer
            var tcp_recv_bufsize_src = alldata['tcp_recv_bufsize-src'];
            if(tcp_recv_bufsize_src==1) send_data['tcp_recv_bufsize'] = alldata['tcp_recv_bufsize'];
            else send_data['tcp_recv_bufsize'] = '';

            var mcast = alldata['mcast_groups'];
            send_data['mcast_groups'] = mcast;

            var check_hostnames = alldata['check_hostnames-radio'];
            send_data['check_hostnames'] = check_hostnames;

            var ssl_unclean_shutdown = alldata['ssl_unclean_shutdown-radio'];
            send_data['ssl_unclean_shutdown'] = ssl_unclean_shutdown;

            var allow_underscore = alldata['allow_underscore-radio'];
            send_data['allow_underscore'] = allow_underscore;

            var conn = new Ext.data.Connection({
                listeners:{
                    // wait message.....
                    beforerequest:function(){
                        Ext.MessageBox.show({
                            title: 'Please wait',
                            msg: 'Updating ports...',
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
                params:{id:this.service_id,method:'set_config',
                    params:Ext.encode(send_data)
                },
                failure: function(resp,opt){
                    var response = Ext.util.JSON.decode(resp.responseText);
                    Ext.MessageBox.alert('Error Message', response['info']);
                    Ext.ux.Logger.error(response['error']);

                },
                // everything ok...
                success: function(resp,opt){
                    var msg = 'Updated ports information';
                    Ext.ux.Logger.info(msg);
                    View.notify({html:msg});
                    this.loadData();


                },scope:this
            });// END Ajax request


        } else{
            Ext.MessageBox.alert('error', 'Please fix the errors noted.');
        }



    }
    ,buildDefaultItem:function(fieldlabel,boxlabel,name,width){
        var txt_field = {xtype:'textfield',name:name,fieldLabel:'',hideLabel:true};
        if(width) txt_field = {xtype:'textfield',name:name,fieldLabel:'',width:width,hideLabel:true};
        var config = {
            layout:'table',border:false,
            layoutConfig: {columns: 3},
            defaults:{layout:'form',border:false},
            items: [
                {items:[{xtype:'radio', fieldLabel:fieldlabel,name:name+'-src',boxLabel:boxlabel,checked:true,inputValue: '0'}]},
                {items:[{xtype:'radio', fieldLabel:'',hideLabel:true,name:name+'-src',boxLabel:'',inputValue: '1'}]},
                {bodyStyle:'padding-bottom:5px;',items:[txt_field]}
            ]
        };
        return config;

    }
    ,loadData:function(){
            this.refreshBtn.addClass('x-item-disabled');
            var params = {"fields":this.params};
            //'get_proxy_ports'
            this.load({
                url: this.url,
                waitMsg:'Loading...',
                params:{id:this.service_id,method:'get_config_fields',mode:'get_network',params:Ext.encode(params)},
                success:function(){this.refreshBtn.removeClass('x-item-disabled');}
                ,scope:this
            });
    }
});


ETFW.Squid.Network.Main = function(service_id) {


    var form = new ETFW.Squid.Network.Form(service_id);

    form.on('beforerender',function(){
            Ext.getBody().mask('Loading ETFW squid network panel...');}
            ,this
    );


    form.on('render',function(){
            Ext.getBody().unmask();}
        ,this
        ,{delay:100});


    ETFW.Squid.Network.Main.superclass.constructor.call(this, {
        border:false,
        layout:'fit',
        defaults:{border:false},
        title: 'Ports and Networking',
        items:form

    });
}

// define public methods
Ext.extend(ETFW.Squid.Network.Main, Ext.Panel, {
    reload:function(){
        this.get(0).loadData();}
});

</script>