<script>
Ext.ns('ETMS.MAILBOX');

ETMS.MAILBOX.Main = Ext.extend(Ext.Panel,{
    layout:'fit',
    border:false,
    //defaults:{border:false},
    title:<?php echo json_encode(__('Mailboxes List')) ?>,
    initComponent:function(){

        // sample static data for the store
//    var myData = [
//        ['mfd@eurotux.com','Manuel Dias','password', true,false, 1024, 2, 5, 2, 'resposta automatica']
//    ];
    function isActiveRenderer(val){
        if (val == 'active') {
            return '<span style="color:green;">' + __('Yes') + '</span>';
        } else{
            return '<span style="color:red;">' + __('No') + '</span>';
        }
    }

    function externalSendRenderer(val){
        if(val == 0){
            return '<span style="color:red;">' + __('No') + '</span>';
        } else{
            return '<span style="color:green;">' + __('Yes') + '</span>';
        }

    }
    /**
     * Custom function used for column renderer
     * @param {Object} val
     */
    function mailQuotaRenderer(val) {
        if(val == 0){
            return '<span style="color:blue;">' + <?php echo json_encode(__('Unlimited'))?> + '</span>';
        }else{
            return '<span style="color:green;">' + val + '</span>';
        }
    }

    // create the data store
//    var store = new Ext.data.ArrayStore({
//        fields: [
//           {name: 'user_name'},
//           {name: 'real_name'},
//           {name: 'password'},
//           {name: 'isActive'},
//           {name: 'allowExternalSend'},
//           {name: 'mailbox_quota'},
//           {name: 'delivery_type'},
//           {name: 'nr_mail_alias'},
//           {name: 'nr_redirect_emails'},
//           {name: 'automatic_answer'}
//        ]
//    });

    // manually load local data
//    store.loadData(myData);
    var store = new Ext.data.JsonStore({
        proxy:new Ext.data.HttpProxy({url:<?php echo json_encode(url_for('etms/json'))?>}),
        totalProperty: 'total',
        baseParams:{id:this.service_id,method:'get_users'},
        root: 'value',
        fields: [
           {name: 'user_name'},
           {name: 'real_name'},
           {name: 'password'},
           {name: 'isActive'},
           {name: 'allowExternalSend'},
           {name: 'mailbox_quota'},
           {name: 'delivery_type'},
           {name: 'nr_mail_alias'},
           {name: 'nr_redirect_emails'},
           {name: 'automatic_answer'},
           {name: 'mail_alias'},
           {name: 'redirect_emails'}
       ],
        id:'user_name'
        //,autoLoad: true
    });


    // create the Grid
    var grid = new Ext.grid.GridPanel({
        viewConfig: {forceFit: true},
        service_id: this.service_id,
        store: store,
        url: <?php echo json_encode(url_for('etms/json'))?>,
        scope:this,
        selModel: new Ext.grid.RowSelectionModel({	//deixa seleccionar apenas uma linha
                    singleSelect: true
                }),
        columns: [
            {
                id       :'user_name',
                header   : <?php echo json_encode(__('Mail Address')) ?>,
                width    : 160,
                sortable : true,
                dataIndex: 'user_name'
            },
            {
                header   : <?php echo json_encode(__('Real Name')) ?>,
                width    : 160,
                sortable : true,
                dataIndex: 'real_name'
            },
            {
                header   : <?php echo json_encode(__('Active')) ?>,
                width    : 160,
                sortable : true,
                renderer : isActiveRenderer,
                dataIndex: 'isActive'
            },{
                header   : <?php echo json_encode(__('External Send')) ?>,
                width    : 160,
                sortable : true,
                renderer : externalSendRenderer,
                dataIndex: 'allowExternalSend'
            },{
                header   : <?php echo json_encode(__('Mail Quota')) ?>,
                width    : 160,
                sortable : true,
                dataIndex: 'mailbox_quota',
                renderer : mailQuotaRenderer
            },{
                header   : <?php echo json_encode(__('Delivery Type')) ?>,
                width    : 160,
                sortable : true,
                dataIndex: 'delivery_type'
            },{
                header   : <?php echo json_encode(__('Mailbox Alias')) ?>,
                width    : 160,
                sortable : true,
                dataIndex: 'nr_mail_alias'
            },{
                header   : <?php echo json_encode(__('Mailbox Destines')) ?>,
                width    : 160,
                sortable : true,
                dataIndex: 'nr_redirect_emails'
            },{
                header   : <?php echo json_encode(__('Automatic Answer')) ?>,
                width    : 160,
                sortable : true,
                dataIndex: 'automatic_answer'
            }
        ],
        stripeRows: true,
        height: 350,
        width: 600,
        bbar: new Ext.ux.grid.TotalCountBar({
            store:store
            ,displayInfo:true
        }),
        //title: 'Array Grid',
        // config options for stateful behavior

        tbar: [{
                    html: "&nbsp"
            },{
                    html:<?php echo json_encode(__('Domain Name:'))?>
            },{
                    html: "&nbsp&nbsp"
            },{
                    xtype: 'textfield',
                    ref: '../domain',
                    fieldLabel: '',
                    enableKeyEvents: true,
                    scope   :this,
                    listeners: {
                        specialkey:{scope:this, fn:function(field, eventObj){
                                if (eventObj.getKey() == Ext.EventObject.ENTER) {
                                    //carregar a informação do dominio
                                    var send_data = new Object();
                                    send_data['name'] = field.getValue();
                                    var domain;
                                    var conn = new Ext.data.Connection();// end conn
                                    conn.request({
                                        scope:this,
                                        url: grid.url,
                                        params:{id:this.service_id,method:'select_domain',params:Ext.encode(send_data)},
                                        failure: function(resp,opt){
                                            domainObj = new Object();
                                            domainObj.name = field.getValue();
                                            this.loadData(domainObj);

                                            if(!resp.responseText){
                                                Ext.ux.Logger.error(resp.statusText);
                                                return;
                                            }

                                            var response = Ext.util.JSON.decode(resp.responseText);
                                            Ext.MessageBox.alert(<?php echo json_encode(__('Error Message'))?>, response['info']);
                                            Ext.ux.Logger.error(response['error']);
                                        },
                                        success: function(response,opt){
                                            var decoded_data = Ext.decode(response.responseText);
                                            domainObj = decoded_data['value'];
                                            grid.domainObj = domainObj;
                                            this.loadData(domainObj);
                                        },scope:this
                                    });
                                }
                            }
                        },
                        keyup : function( field, evt ){
                                if(field.getValue() == ''){
                                    grid.addBtn.setDisabled(true);
                                }
//                                else{
//                                    grid.addBtn.setDisabled(false);
//                                }
                        }
                }
                },{
                    xtype: 'tbseparator'	//separador
                },{
                    //================= EDIÇÃO =================
                    text: <?php echo json_encode(__('Add'))?>,
                    icon: 'images/table_add.png',
                    cls: 'x-btn-text-icon',
                    ref: '../addBtn',
                    disabled: true,
                    handler: function() {
                        var editor = new ETMS.MAILBOX.Editor({
                            title   : <?php echo json_encode(__('Create Mailbox')) ?>,
                            domain  : grid.domain.getValue(),
                            maxQuota: grid.domainObj.server_quota,//this.maxQuota,    //TODO: alterar isto para dar info do server
                            service_id: grid.service_id,
                            parent_grid: grid,
                            changeFreeMb: grid.changeFreeMb
                        });

                        editor.show();
                }},{
                    text: <?php echo json_encode(__('Edit'))?>,
                    icon: 'images/table_edit.png',
                    cls: 'x-btn-text-icon',
                    disabled: true,
                    ref: '../editBtn',
                    handler: function() {
                        var sm = grid.getSelectionModel(),
                                        sel = sm.getSelected();
                                        
                        //alert("sel: "+sel);

                        var editor = new ETMS.MAILBOX.Editor({
                            title   : <?php echo json_encode(__('Edit Mailbox')) ?>,
                            mailbox : sel,
                            maxQuota: grid.domainObj.server_quota,
                            service_id: grid.service_id,
                            parent_grid: grid
                        });
                        editor.show();
                }
                },{
                    //================= APAGAR =================
                text: <?php echo json_encode(__('Delete')) ?>,
                disabled: true,
                ref: '../removeBtn',
                icon: 'images/table_delete.png',
                cls: 'x-btn-text-icon',
                handler: function() {
                    var sm = grid.getSelectionModel(),
                                        sel = sm.getSelected();

                    if(sm.hasSelection()){
                        //============ DIÁLOGO QUE PERGUNTA SE PRETENDE REMOVER ===========
                        Ext.Msg.show({
                            title: <?php echo json_encode(__('Warning')) ?>,
                            msg: <?php echo json_encode(__('Do you want to remove Mailbox and associated Emails?')) ?>, //+sel.get('user_name')
                            buttons: {
                                    yes: true,
                                    no: true,
                                    cancel: false,
                                    ok: false
                            },
                            icon: 'removeicon',
                            fn: function(btn) {
                                    switch(btn){
                                        case 'yes':
                                            //PEDIDO AJAX =====> REMOVE DOMINIO E os ALIASes
                                            var conn = new Ext.data.Connection({
                                                listeners:{
                                                // wait message.....
                                                    beforerequest:function(){
                                                        Ext.MessageBox.show({
                                                            title: <?php echo json_encode(__('Please wait')) ?>,
                                                            msg: <?php echo json_encode(__('Updating Client options info...')) ?>,
                                                            width:300,
                                                            wait:true,
                                                            modal: false
                                                        });
                                                    },// on request complete hide message
                                                    requestcomplete:function(){Ext.MessageBox.hide();}
                                                }
                                            });// end conn

                                            var send_data = new Object();
                                            send_data['user_name'] = sel.get('user_name');

                                            conn.request({
                                            scope:this,
                                            url: grid.url,
                                            params:{id:grid.service_id,method:'delete_user',params:Ext.encode(send_data)},
                                            failure: function(resp,opt){
                                                if(!resp.responseText){
                                                    Ext.ux.Logger.error(resp.statusText);
                                                    return;
                                                }

                                                var response = Ext.util.JSON.decode(resp.responseText);
                                                Ext.MessageBox.alert(<?php echo json_encode(__('Error Message'))?>, response['info']);
                                                Ext.ux.Logger.error(response['error']);

                                            },
                                            success: function(resp,opt){
                                                var msg = <?php echo json_encode(__('Mailbox successfully removed.')) ?>;
                                                Ext.ux.Logger.info(msg);
                                                grid.getStore().reload();

                                                grid.changeFreeMb(1);
                                                //this.parent_grid.getStore().reload();
                                            },scope:this
                                        });// END Ajax request
                                            Ext.Msg.alert(<?php echo json_encode(__('Warning')) ?>,<?php echo json_encode(__('Done!')) ?>);
                                            break;
                                        case 'no':
                                            Ext.Msg.alert(<?php echo json_encode(__('Warning')) ?>,<?php echo json_encode(__('Operation canceled!')) ?>);
                                            break;
                                    }
                            }
                        });
                    }
                }
                },{
			xtype: 'tbfill'		//separa metendo os componentes à direita
		},{
                    text: <?php echo json_encode(__('Details')) ?>,
                    disabled: false,
                    ref: '../detailsBtn',
                    icon: 'images/table_edit.png',
                    cls: 'x-btn-text-icon',
                    showDetails: true,
                    handler: function(b, e) {
                        
                        if(b.showDetails){
                            var send_data = new Object();
                            send_data['domain'] = grid.domain.getValue();
                            //grid.addColumn({header:'Seen',dataIndex:'a'});
                            //grid.addColumn({header:'Unseen',dataIndex:'b'});
                            grid.addColumn({
                                header:<?php echo json_encode(__('Unread'))?>,dataIndex:'unread', sortable: true});
                            grid.addColumn({
                                header:<?php echo json_encode(__('Read'))?>,dataIndex:'read', sortable: true});

                            b.showDetails = false;

                            //Actualizar os valores
                            var conn = new Ext.data.Connection({
                                scope:this,
                                listeners:{
                                    scope:this,
                                // wait message.....
                                    beforerequest:function(){
                                        Ext.MessageBox.show({
                                            title: <?php echo json_encode(__('Please wait')) ?>,
                                            msg: <?php echo json_encode(__('Updating Client options info...')) ?>,
                                            width:300,
                                            wait:true,
                                            modal: false
                                        });
                                    },// on request complete hide message
                                    requestcomplete:function(){
                                        Ext.MessageBox.hide();
                                        //this.end();
                                    }
                                }
                            });// end conn

                            conn.request({
                                scope:this,
                                url: grid.url,
                                params:{id:grid.service_id,method:'users_occupied_space',params:Ext.encode(send_data)},
                                failure: function(resp,opt){
                                    if(!resp.responseText){
                                        Ext.ux.Logger.error(resp.statusText);
                                        return;
                                    }

                                    var response = Ext.util.JSON.decode(resp.responseText);
                                    Ext.MessageBox.alert(<?php echo json_encode(__('Error Message'))?>, response['info']);
                                    Ext.ux.Logger.error(response['error']);

                                },
                                success: function(resp,opt){
                                    var msg = <?php echo json_encode(__('Client options edited successfully')) ?>;
                                    Ext.ux.Logger.info(msg);

                                    var response = Ext.util.JSON.decode(resp.responseText);
                                    //alert(dump(response));
                                    for(var idx = 0; idx < response['value'].length; idx++){
                                        //alert(dump(response['value'][""+idx]['row']));
                                        var row = grid.getStore().getById(response['value'][""+idx]['mail']);
                                        row.set('read', response['value'][""+idx]['cur']);
                                        row.set('unread', response['value'][""+idx]['new']);
                                    }


                                },scope:this
                            });
                        }else{
                            grid.removeColumn('read');
                            grid.removeColumn('unread');
                            b.showDetails = true;
                        }
                    }
                },{
                        xtype: 'tbseparator'	//separador
                },{
                    html: "&nbsp"
                },{
                    html:<?php echo json_encode(__('Avaialable Mailboxes:'))?>,
                    ref: '../lbl_free_mb'
                },{
                    html: "&nbsp&nbsp"
                },{
                    xtype: 'textfield',
                    ref: '../free_mb',
                    fieldLabel: '',
                    grow: true,
                    width: 20,
                    disabled: true
                }]
        });



        grid.getStore().on('beforeload', function(store, options){
            this.reloadDomain(grid.domainObj.name);
            grid.removeColumn('read');
            grid.removeColumn('unread');

        },this);

        grid.getSelectionModel().on('selectionchange', function(sm){
            var btnState = sm.getCount() < 1 ? true :false;
            var selected = sm.getSelected();

            grid.removeBtn.setDisabled(!selected);
            grid.editBtn.setDisabled(!selected);
        });

        grid.changeFreeMb = function(nr){
            var free = parseInt(grid.free_mb.getValue()) + nr;
            grid.free_mb.setValue(free);
            //:alert(free);
            if(free > 0){
                grid.addBtn.setDisabled(false);
            }else{
                grid.addBtn.setDisabled(true);
            }

            if(typeof grid.domainGridReload != 'undefined'){
                grid.domainGridReload();
            }
        }
        

        this.items = [grid];

        ETMS.MAILBOX.Main.superclass.initComponent.call(this);


    }
    ,reloadDomain: function(domainname){    //NOTA: nao faz reload à store. Entra em ciclo
        var conn = new Ext.data.Connection();// end conn
        var grid = this.get(0);
        var send_data = new Object();
        send_data['name'] = domainname;

        conn.request({
            scope:this,
            url: grid.url,
            params:{id:this.service_id,method:'select_domain',params:Ext.encode(send_data)},
            failure: function(resp,opt){
                domainObj = new Object();
                domainObj.name = field.getValue();
                    //this.loadData(domainObj);

                if(!resp.responseText){
                    Ext.ux.Logger.error(resp.statusText);
                    return;
                }

                var response = Ext.util.JSON.decode(resp.responseText);
                Ext.MessageBox.alert(<?php echo json_encode(__('Error Message'))?>, response['info']);
                Ext.ux.Logger.error(response['error']);
            },
            success: function(response,opt){
                var decoded_data = Ext.decode(response.responseText);
                domainObj = decoded_data['value'];
                grid.domainObj = domainObj;

                this.nr_mailboxes = domainObj.mailbox;
                this.max_mailboxes = domainObj.max_mailboxes;
                this.changeLimit(this.max_mailboxes, this.nr_mailboxes);
                //this.loadData(domainObj);
            }
        });
    }
    ,changeLimit: function(max_mailboxes, nr_mailboxes){
        var grid = this.get(0);
        //verificar se existe limite de mailboxes
        if(max_mailboxes == 0){
            grid.free_mb.setValue(100000);
            grid.free_mb.setVisible(false);
            grid.lbl_free_mb.setVisible(false);
            grid.addBtn.setDisabled(false);
        }else{
            grid.free_mb.setValue(max_mailboxes - nr_mailboxes);
            grid.free_mb.setVisible(true);
            grid.lbl_free_mb.setVisible(true);
            if(max_mailboxes - nr_mailboxes > 0){
                grid.addBtn.setDisabled(false);
            }else{
                grid.addBtn.setDisabled(true);
            }
        }
    }
    ,loadData: function(domainObj, reloadStore){    //domain record, domain grid reload (function)
        var grid = this.get(0);
        grid.domainObj = domainObj;
        grid.domain.setValue(domainObj.name);    //a grid é o único item
        this.serverQuota = domainObj.server_quota;
        grid.addBtn.setDisabled(false);
        var myStore = grid.getStore();
        myStore.setBaseParam('params', Ext.encode({'domain':domainObj.name}));
        myStore.reload();
        this.nr_mailboxes = domainObj.mailbox;
        this.max_mailboxes = domainObj.max_mailboxes;

        //alert(this.max_mailboxes+" : "+this.nr_mailboxes);

        //verificar se existe limite de mailboxes
        this.changeLimit(this.max_mailboxes, this.nr_mailboxes);
//        if(this.max_mailboxes == 0){
//            grid.free_mb.setValue(100000);
//            grid.free_mb.setVisible(false);
//            grid.lbl_free_mb.setVisible(false);
//            grid.addBtn.setDisabled(false);
//        }else{
//            grid.free_mb.setValue(this.max_mailboxes - this.nr_mailboxes);
//            grid.free_mb.setVisible(true);
//            grid.lbl_free_mb.setVisible(true);
//            if(this.max_mailboxes - this.nr_mailboxes > 0){
//                grid.addBtn.setDisabled(false);
//            }else{
//                grid.addBtn.setDisabled(true);
//            }
//        }
        

        //alert(reloadStore);
        grid.domainGridReload = reloadStore;
    }
});

</script>