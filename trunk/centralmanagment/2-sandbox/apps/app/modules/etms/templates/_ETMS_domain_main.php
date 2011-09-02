<script>
Ext.ns('ETMS.DOMAIN');

ETMS.DOMAIN.MainLayout = Ext.extend(Ext.Panel,{
    layout:'border',
    defaults: {
        //collapsible: true,
        split: true,
        bodyStyle: 'padding:0px'
    },
    //grid de domains e alias com layout border
    initComponent:function(){
        var alias = new ETMS.DOMAIN.AliasPanel({
                region:'east',
                collapsible: true,
                margins: '0 0 0 0',
                cmargins: '5 5 5 5',
                width: 175,
                minSize: 100,
                maxSize: 250,
                service_id:this.service_id
        });
        var domains = new ETMS.DOMAIN.Main({
                region:'center',
                collapsible: false,
                margins: '0 0 0 0',
                aliasObj: alias, //objecto que apresenta os alias (para actualizar qd seleccionada uma linha),
                maintab:this.maintab,
                mbtabidx:this.mbtabidx,
                service_id:this.service_id
        });

        this.items = [domains, alias];
        ETMS.DOMAIN.MainLayout.superclass.initComponent.call(this);
    }
});


ETMS.DOMAIN.Main = Ext.extend(Ext.Panel,{
    layout:'fit',
    border:false,
    //defaults:{border:false},
    title:<?php echo json_encode(__('Domains List')) ?>,
    initComponent:function(){


    //======================== DATA STORE DOMAINS =========================

    // sample static data for the store
//    var myData = [
//        ['tmn.pt','tmn.pt', 'password','domain descritption about tmn ', 0, 100, 0, 0],
//        ['eurotux.com', 'olaolaolaola','eurotux.com', 'another description about eurotux', 1024, 0, 25, 1]
//    ];

    //======================== RENDERERS =========================
    /**
     * Custom function used for column renderer
     * @param {Object} val
     */
    function pctChange(val) {
        if (val > 0) {
            return '<span style="color:green;">' + val + '</span>';
        } else{
            return '<span style="color:red;">' + val + '</span>';
        }
        return val;
    }

    function nrMailboxesRenderer(val){
        if(val == 0){
            return '<span style="color:blue;">' + <?php echo json_encode(__('Unlimited'))?> + '</span>';
        }else{
            return '<span style="color:green;">' + val + '</span>';
        }
    }

    function isActiveRenderer(val){
        if (val != 0) {
            return '<span style="color:green;">' + __('Yes') + '</span>';
        } else{
            return '<span style="color:red;">' + __('No') + '</span>';
        }
    }


    //====================== DATA STORE =======================
    // create the data store
//    var store = new Ext.data.ArrayStore({
//        fields: [
//           {name: 'name'},
//           {name: 'password'},
//           {name: 'user'},
//           {name: 'description'},
//           {name: 'server_quota'},
//           {name: 'max_mailboxes'},
//           {name: 'mailbox'},
//           {name: 'isActive'}
//        ]
//    });
//
//    // manually load local data
//    store.loadData(myData);
   
    var store = new Ext.data.JsonStore({
        proxy:new Ext.data.HttpProxy({url:<?php echo json_encode(url_for('etms/json'))?>}),
        totalProperty: 'total',
        baseParams:{id:this.service_id,method:'list_domains'},
        root: 'value',
        fields: [            
           {name: 'name'},
           {name: 'user'},
           {name: 'password'},
           {name: 'description'},
           {name: 'server_quota'},
           {name: 'max_mailboxes'},
           {name: 'mailbox'},
           {name: 'isActive'}
        ],
        id: 'name',
        autoLoad: true
    });


    var reloadStore = function(){
        //var record = store.getSelectionModel().getSelected();
        //alert(record);
        store.reload();
        //store.getSelectionModel().selectRecords(record);
        //store.getSelectionModel().selectFirstRow();
    }
    
    // create the Grid
    var grid = new Ext.grid.GridPanel({
        viewConfig: {forceFit: true},
        loadMask: true,
        service_id:this.service_id,
        url: <?php echo json_encode(url_for('etms/json'))?>,
        aliasObj: this.aliasObj,
        selModel: new Ext.grid.RowSelectionModel({	//deixa seleccionar apenas uma linha
                        scope: this,
			singleSelect: true,
			listeners: {
                             scope:this,
				rowselect: function(sm, index, record) {
//					Ext.Msg.alert('You Selected',record.get('name'));
                                        this.selectedDomain = record.get('name');
                                        this.aliasObj.selectedDomain = this.selectedDomain;
                                        this.aliasObj.loadData();
                                        this.aliasObj.get(0).getSelectionModel().selectFirstRow();
                                        //this.aliasObj.get(0).getStore().reload();
//
//                                        var aliasWindow = new ETMS.DOMAIN.Alias({service_id:this.service_id,xpto:true});
//                                        aliasWindow.on('show',function(){
//                                            alert('show');
//                                            aliasWindow.loadData(sel);
//                                        });
//                                        aliasWindow.show();
				}
			}
		}),
        store: store,
        bbar: new Ext.ux.grid.TotalCountBar({
            store:store
            ,displayInfo:true
        }),
        colModel: new Ext.grid.ColumnModel({
        defaults: {
            sortable: true,
            menuDisabled: true,
            width: 100
        },
        columns: [
            {
                id       :'name',
                header   : <?php echo json_encode(__('Domain Name')) ?>,
                width    : 160,
                sortable : true,
                dataIndex: 'name'
            },            
            {
                header   : <?php echo json_encode(__('User')) ?>,
                width    : 160,
                sortable : true,
                dataIndex: 'user',
                hidden   : true
            },
            {
                header   : <?php echo json_encode(__('Description')) ?>,
                width    : 200,
                sortable : true,
                dataIndex: 'description'
            },{
                header   : <?php echo json_encode(__('Server Quota')) ?>,
                width    : 100,
                dataIndex: 'server_quota',
                renderer : nrMailboxesRenderer
            },{
                header   : <?php echo json_encode(__('Max. Mailboxes')) ?>,
                width    : 100,
                dataIndex: 'max_mailboxes',
                renderer : nrMailboxesRenderer
            },{
                header   : <?php echo json_encode(__('Mailboxes')) ?>,
                width    : 100,
                sortable : true,
                renderer : pctChange,
                dataIndex: 'mailbox'
            },{
                header   : <?php echo json_encode(__('Active')) ?>,
                width    : 45,
                sortable : true,
                renderer : isActiveRenderer,
                dataIndex: 'isActive'
            }
        ]}),
        stripeRows: true,       
      //  height: 350,
      //  width: 600,
        //title: 'Array Grid',
        // config options for stateful behavior
        tbar: [{
                //================= ADICIONAR =================
                text: <?php echo json_encode(__('Add')) ?>,
                icon: 'images/table_add.png',
                cls: 'x-btn-text-icon',
                handler:function(field, eventObj){
                        var addWindow = new ETMS.DOMAIN.Add({edit:false, refreshGrid:reloadStore, service_id:grid.service_id, title: <?php echo json_encode(__('Add Domain')) ?>});
                        addWindow.show();
                }
                },{
                //============= EDITAR ============
                text: <?php echo json_encode(__('Edit')) ?>,
                disabled: true,
                ref: '../editBtn',
                icon: 'images/table_edit.png',
                cls: 'x-btn-text-icon',
                handler: function() {
                    //passa a linha seleccionada
                    var sm = grid.getSelectionModel(),
                        sel = sm.getSelected();

                    var addWindow = new ETMS.DOMAIN.Add({edit:true, refreshGrid:reloadStore,domainObj:sel.data, service_id:grid.service_id});
                    addWindow.show();

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
                        var msg = <?php echo json_encode(__('Are you sure you want to remove {0}, associated alias and mailboxes?'))?>;
                        Ext.Msg.show({
                            title: <?php echo json_encode(__('Warning')) ?>,
                            msg: String.format(msg, sel.get('name')),                        
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
                                            var conn = new Ext.data.Connection({
                                                listeners:{
                                                // wait message.....
                                                    beforerequest:function(){
                                                        Ext.MessageBox.show({
                                                            title: <?php echo json_encode(__('Please wait')) ?>,
                                                            msg: <?php echo json_encode(__('Please wait')) ?>,
                                                            width:300,
                                                            wait:true,
                                                            modal: false
                                                        });
                                                    },// on request complete hide message
                                                    requestcomplete:function(){Ext.MessageBox.hide();}
                                                }
                                            });// end conn

                                            var send_data = new Object();
                                            send_data['domain'] = sel.get('name');
                                            conn.request({
                                                scope:this,
                                                url: grid.url,
                                                params:{id:grid.service_id,method:'delete_domain',params:Ext.encode(send_data)},
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
                                                        grid.getStore().reload();
                                                        grid.getSelectionModel().selectFirstRow();
                                                        var msg = <?php echo json_encode(__('Domain, Alias and Mailboxes successfully removed.')) ?>;
                                                        Ext.ux.Logger.info(msg);
                                                        //this.aliasObj.loadData();

                                                    },scope:this
                                            });
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
                    xtype: 'tbseparator'	//separador
                },{
                    text: <?php echo json_encode(__('Manage Mailboxes')) ?>,
                    scope: this,
                    disabled: true,
                    ref: '../mailboxBtn',
                    icon: 'images/table_edit.png',
                    cls: 'x-btn-text-icon',
                    handler: function() {
                         //=================== LOAD DAS MAILBOXES ==============
                        //mudar de separador e abrir os users do dominio
//                        var mb_cmp = this.maintab.get(this.mbtabidx);
//
//                        this.maintab.on('tabchange', function(tabpanel, tab){   //seleccionado o tab dos mails
//                            if(mb_cmp == tab){
//                                var sm = grid.getSelectionModel(),
//                                sel = sm.getSelected();
//                                mb_cmp.loadData(sel.data.name);
//                            }


                        var mb_cmp = this.maintab.get(this.mbtabidx);                                                

                        this.maintab.setActiveTab(this.mbtabidx);

                        var sm = grid.getSelectionModel(),
                        sel = sm.getSelected();

                        mb_cmp.on('loadedPanel',function(){
                            var sm = grid.getSelectionModel(),
                            sel = sm.getSelected();
                            mb_cmp.loadData(sel.data, reloadStore);
                        });

                        if(mb_cmp.rendered) mb_cmp.loadData(sel.data, reloadStore);

                    }
                },{
			xtype: 'tbfill'		//separa metendo os componentes à direita
		},{
                    text: <?php echo json_encode(__('Details')) ?>,
                    disabled: false,
                    ref: '../detailsBtn',
                    icon: 'images/table_edit.png',
                    cls: 'x-btn-text-icon',                    
                    handler: function(b, e) {                        
                            //grid.addColumn({header:'Seen',dataIndex:'a'});
                            //grid.addColumn({header:'Unseen',dataIndex:'b'});
                            if(grid.getColumnModel().findColumnIndex('space') == -1)
                                grid.addColumn({
                                    header:<?php echo json_encode(__('Space Usage'))?>,dataIndex:'space'});

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
                                            modal: true
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
                                params:{id:grid.service_id,method:'domains_occupied_space'},
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
                                    for(var idx = 0; idx < response['value'].length; idx++){
                                        //alert(dump(response['value'][""+idx]['row']));
                                        var row = grid.getStore().getById(response['value'][""+idx]['row']['domain']);
                                        row.set('space', response['value'][""+idx]['row']['space']);
                                    }

                                    
                                },scope:this
                            });                        

                    }
                }
                ]
         });

        grid.getSelectionModel().on('selectionchange', function(sm){
            var btnState = sm.getCount() < 1 ? true :false;
            var selected = sm.getSelected();

            grid.removeBtn.setDisabled(!selected);
            grid.editBtn.setDisabled(!selected);
            grid.mailboxBtn.setDisabled(!selected);
        });

        store.on('beforeload', function( st, options ){
            grid.removeColumn('space');            
        });


            // double click

//        grid.on('dblclick', function(evtObj){
//            var sm = grid.getSelectionModel(),
//                sel = sm.getSelected();
//
//            if(sm.hasSelection()){
//                //alert(sel.get('name'));
//                new Ext.Window({
//                    title: 'Help',
//                    width: 300,
//                    height: 300,
//                    renderTo: document.body,
//                    closeAction: 'hide',
//                    layout: 'fit',
//                    tbar: [{
//                            text: 'Close',
//                            handler: function(){
//                            winhelp.hide();
//                    }},{
//                            text: 'Disable',
//                            handler: function(t){
//                                    t.disable();
//                            }
//                    }]
//                    ,items: [
//                        new ETMS.SERVER.space({service_id:this.service_id, domain:sel.get('name')})
//                    ]
//                }).show();
//            }
//        });


        this.items = [grid];


        ETMS.DOMAIN.Main.superclass.initComponent.call(this);
    }
    //string domain name, returns a record
//    ,getRecord: function(domainName){
//        var store = this.get(0).getStore();
//        var idx = store.findExact('name', domainName);
//        return getAt(idx);
//    }
});

//==================== DEFINICAO DA JANELA QUE CRIA DOMAINS ==================
ETMS.DOMAIN.Add = Ext.extend(Ext.Window, {
        title: <?php echo json_encode(__('Edit Domain')) ?>,
        width: 600,
        height: 320,
        closeAction: 'hide',
        layout: 'fit',
        initComponent:function(){

            var domainForm = new Ext.FormPanel({
                window:this,
                refreshGrid:this.refreshGrid,
                service_id:this.service_id,
                edit:this.edit,
                url:<?php echo json_encode(url_for('etms/json'))?>,
                frame: false,
                border: false,
                monitorValid:true,
                width: 550,
                padding: '10 20 10 20',
                labelWidth: 200,
                labelPad: 10,
                //Campos do formulário
                items: [{
                        xtype: 'textfield',
                        fieldLabel: <?php echo json_encode(__('Domain Name')) ?>,
                        name: 'domain',
                        width: 210,
                        ref: 'domain',
                        allowBlank: false
                        ,vtype: 'domain'
                }
                ,{
                        xtype: 'textfield',
                        name: 'user',
                        width: 210,
                        ref: 'user',
                        //allowBlank: false,
                        hidden: true
                }
                ,{
                        xtype: 'textarea',
                        fieldLabel: <?php echo json_encode(__('Description')) ?>,
                        name: 'description',
                        width: 210,
                        ref: 'description'
                },{
                        layout:'column',
                        fieldLabel: <?php echo json_encode(__('Change Password')) ?>,
                        items: [{
                            xtype: 'checkbox', 	//pode ser feito como os radio buttons
                            name: 'change_pwd',
                            ref: '../change_pwd',
                            checked: false,
                            handler: function(checkbox, checked){
                                    domainForm.password.setDisabled(!checked);
                            }
                        },{
                            xtype: 'textfield',
                            name: 'password',
                            ref: '../password',
                            inputType: 'password',
                            allowBlank: false,
                            minLength: 6,
                            minLengthText: <?php echo json_encode(__('Password must be at least 6 characters long.')) ?>,
                            disabled: true
                        }]
                },{
                        layout:'column',
                        fieldLabel: <?php echo json_encode(__('Server Quota')) ?>,
                        items: [{
                            xtype:'radio',
                            name: 'radio_maxquota', //nota: o mesmo nome em todos os radio buttons
                            boxLabel: <?php echo json_encode(__('Unlimited')) ?>,
                            inputValue: 'unlimited',
                            //checked: true,
                            ref: '../server_quota_unlimited',
                            columnWidth: .33
                         },{
                            xtype:'radio',
                            name: 'radio_maxquota', //nota: o mesmo nome em todos os radio buttons
                            boxLabel: <?php echo json_encode(__('Limited')) ?>+' (Bytes)',
                            inputValue: 'limited',
                            ref: '../server_quota_limited',
                            columnWidth: .33
//                            ,listeners: {
//                            check : function(checkbox, checked){
//                                var quota = domainForm.getForm().findField('serverQuota');
//                                quota.setDisabled(!checked);
//                                }
//                            }
                        },{
                            xtype: 'textfield',
                            fieldLabel: '',
                            name: 'server_quota',
                            maskRe: /[0-9]/,
                            ref: '../server_quota_limit',
                            columnWidth: .33,
                            disabled: true
                        }]
                },{
                        layout:'column',
                        fieldLabel: <?php echo json_encode(__('Max. Mailboxes')) ?>,
                        items: [
                        {
                                xtype:'radio',
                                name: 'radio_maxmb',
                                //checked: true,
                                boxLabel: <?php echo json_encode(__('Unlimited')) ?>,
                                ref: '../nr_mailboxes_unlimited',
                                inputValue: 'unlimited',
                                columnWidth: .33
                        },{
                                xtype:'radio',
                                name: 'radio_maxmb', //nota: o mesmo nome em todos os radio buttons
                                boxLabel: <?php echo json_encode(__('Limited')) ?>,
                                inputValue: 'limited',
                                ref: '../nr_mailboxes_limited',
                                columnWidth: .33,
                                listeners: {
//                                check : function(checkbox, checked){
//                                    alert(checked);
//                                    var quota = domainForm.getForm().findField('maxMailboxes');
//                                    quota.setDisabled(!checked);
//                                    }
                                }
                        },{
                                xtype: 'textfield',
                                fieldLabel: '',
                                name: 'max_mailboxes',
                                ref: '../nr_mailboxes_limit',
                                maskRe: /[0-9]/,
                                columnWidth: .33,
                                disabled: true
                        }
                        ]
                },{
                        xtype: 'checkbox', 	//pode ser feito como os radio buttons
			columns: 1,
			fieldLabel: <?php echo json_encode(__('Active')) ?>,
			name: 'isActive',
                        ref: 'isactive',
			checked: true
                }],
                buttons: [{
                        text: __('Save'),
                        handler:function(button, eventObj){
                                domainForm.onSave();
//                                domainForm.getForm().submit({		//pedido ajax
//                                        success: function(form, action){
//                                                alert('Success', 'It worked');
//                                                this.refreshGrid();     //refresca a grid de dominios após a edição
//                                        },
//                                        failure: function(form, action){	//trata os vários tipos de falhas
//                                                if(action.result){
//                                                        if (action.failureType == Ext.form.Action.CLIENT_INVALID) {
//                                                                ExtExt.Msg.alert("Cannot submit",
//                                                                "Some fields are still invalid");
//                                                        } else if (action.failureType === Ext.form.Action.CONNECT_FAILURE)
//                                                                {
//                                                                Ext.Msg.alert('Failure', 'Server communication failure: '+
//                                                                action.response.status+' '+action.response.statusText);
//                                                        } else if (action.failureType === Ext.form.Action.SERVER_INVALID)
//                                                                {
//                                                                Ext.Msg.alert('Warning', action.result.errormsg);
//                                                        }
//                                                }
//                                        }
//
//                                });
                        }
                },{
                        text: <?php echo json_encode(__('Reset')) ?>,
                        handler: function(){
                                domainForm.getForm().reset();		//apaga o formulário
                        }
                }]
        });

        //================ BUTTONS ====================
        var options =  [{
                text: 'Close',
                scope: this,
                handler: function(){
                  this.hide();
                }
        }]
            //adiciona os componentes
            this.items = [domainForm];
           // this.buttons = options;


           if(this.domainObj == undefined){
                domainForm.change_pwd.setValue(true);
                domainForm.change_pwd.hidden = true;
                domainForm.server_quota_unlimited.setValue(true);
                domainForm.nr_mailboxes_unlimited.setValue(true);
           }else{
                domainForm.domain.setValue(this.domainObj.name);
                domainForm.domain.setDisabled(true);

                //Domain = user
                domainForm.user.setValue(this.domainObj.user);
                domainForm.description.setValue(this.domainObj.description);

                domainForm.password.setValue(this.domainObj.password);

                var nr_mailboxes = this.domainObj.max_mailboxes;
                var server_quota = this.domainObj.server_quota;

                if(server_quota > 0){   //limited
                    domainForm.server_quota_limited.setValue(true);
                    domainForm.server_quota_limit.setValue(server_quota);
                    domainForm.server_quota_limit.setDisabled(false);
                }else{
                    domainForm.server_quota_unlimited.setValue(true);
                }

                if(nr_mailboxes > 0){   //limited
                    domainForm.nr_mailboxes_limited.setValue(true);
                    domainForm.nr_mailboxes_limit.setValue(nr_mailboxes);
                    domainForm.nr_mailboxes_limit.setDisabled(false);
                }else{
                    domainForm.nr_mailboxes_unlimited.setValue(true);
                }

                if (this.domainObj.isActive != 0) {
                    domainForm.isactive.setValue(true);
                }else{
                    domainForm.isactive.setValue(false);
                }
            }

            domainForm.server_quota_limited.on(
                'check',
                function(checkbox, checked){
                    domainForm.server_quota_limit.setDisabled(!checked);
                }
            );

            domainForm.nr_mailboxes_limited.on(
                'check',
                function(checkbox, checked){
                    domainForm.nr_mailboxes_limit.setDisabled(!checked);
                }
            );
            
            
            //================ DUMPER ===================
            domainForm.dumper = function dump(arr,level) {
                var dumped_text = "";
                if(!level) level = 0;

                //The padding given at the beginning of the line.
                var level_padding = "";
                for(var j=0;j<level+1;j++) level_padding += "    ";

                if(typeof(arr) == 'object') { //Array/Hashes/Objects
                        for(var item in arr) {
                                var value = arr[item];

                                if(typeof(value) == 'object') { //If it is an array,
                                        dumped_text += level_padding + "'" + item + "' ...\n";
                                        dumped_text += dump(value,level+1);
                                } else {
                                        dumped_text += level_padding + "'" + item + "' => \"" + value + "\"\n";
                                }
                        }
                } else { //Stings/Chars/Numbers etc.
                        dumped_text = "===>"+arr+"<===("+typeof(arr)+")";
                }
                return dumped_text;
            }

            // ======================= SAVE SECTION ======================
            domainForm.onSave = function() {
                if(this.form.isValid()){
                    var send_data = new Object();
                    var form_values = this.getForm().getValues();

                    send_data['name'] = domainForm.domain.getValue();
                    send_data['description'] = form_values['description'];

                    if(form_values['max_mailboxes'] == undefined || form_values['max_mailboxes'] == ""){
                        send_data['max_mailboxes'] = '0';
                    }else{
                        send_data['max_mailboxes'] = form_values['max_mailboxes'];
                    }

                    if(form_values['password'] != undefined){
                        send_data['password'] = form_values['password'];
                    }

                    if(form_values['server_quota'] == undefined || form_values['server_quota'] == ""){
                        send_data['server_quota'] = '0';
                    }else{
                        send_data['server_quota'] = form_values['server_quota'];
                    }

                    send_data['user'] = domainForm.domain.getValue();        //USER = DOMAIN

                    if(form_values['isActive'] == 'on'){
                        send_data['isActive'] = 1;
                    }else{
                        send_data['isActive'] = 0;
                    }

                  //============= ENVIAR O PEDIDO ==============
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

                    if(domainForm.edit == true){ //method edit_domain
                        conn.request({
                            scope:this,
                            url: domainForm.url,
                            params:{id:this.service_id,method:'edit_domain',params:Ext.encode(send_data)},
                            failure: function(resp,opt){

                                if(!resp.responseText){
                                    Ext.ux.Logger.error(resp.statusText);
                                    return;
                                }

                                var response = Ext.util.JSON.decode(resp.responseText);
                                Ext.MessageBox.alert(<?php echo json_encode(__('Error Message'))?>, response['info']);
                                Ext.ux.Logger.error(response['error']);
                                domainForm.window.hide();
                            },
                            // everything ok...
                            success: function(resp,opt){
                                var msg = <?php echo json_encode(__('Client options edited successfully')) ?>;
                                Ext.ux.Logger.info(msg);
                                domainForm.refreshGrid();
//                                View.notify({html:msg});
//                                this.fireEvent('updatedClientOptions',this);
                                domainForm.window.hide();
                            },scope:this
                        });// END Ajax request
                    }else{  //method create_domain
                        conn.request({
                            url: domainForm.url,
                            params:{id:this.service_id,method:'create_domain',params:Ext.encode(send_data)},
                            failure: function(resp,opt){
                                if(!resp.responseText){
                                    Ext.ux.Logger.error(resp.statusText);
                                    return;
                                }

                                var response = Ext.util.JSON.decode(resp.responseText);
                                Ext.MessageBox.alert(<?php echo json_encode(__('Error Message'))?>, response['info']);
                                Ext.ux.Logger.error(response['error']);
                                domainForm.window.hide();
                            },
                            // everything ok...
                            success: function(resp,opt){
                                var msg = 'Client options edited successfully';
                                Ext.ux.Logger.info(msg);
                                domainForm.refreshGrid();
//                                View.notify({html:msg});
//                                this.fireEvent('updatedClientOptions',this);
                                domainForm.window.hide();
                            },scope:this
                        });// END Ajax request
                    }

                 } else{
                    Ext.MessageBox.show({
                        title: 'Error',
                        msg: <?php echo json_encode(__('Please fix the errors noted.')) ?>,
                        buttons: Ext.MessageBox.OK,
                        icon: Ext.MessageBox.WARNING
                    });
                 }
            }




            ETMS.DOMAIN.Add.superclass.initComponent.call(this);

        }

});

</script>
