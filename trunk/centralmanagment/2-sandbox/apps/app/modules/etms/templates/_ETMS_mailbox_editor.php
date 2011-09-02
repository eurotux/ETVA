<script>
Ext.ns('ETMS.MAILBOX.Edit');

//==================== DEFINICAO DO PAINEL USADO PARA CRIAR ALIAS E RELAY PARA UM UTILIZADOR ==================
ETMS.MAILBOX.Edit.Alias = Ext.extend(Ext.Panel,{
    //border:false,
    //defaults:{border:false},
    
    initComponent: function(){


//================================================================
//==                            Alias                           ==
//================================================================
        // sample static data for the store
//        var myData = [
//            ['manueldias@eurotux.com'],
//            ['m@eurotux.com']
//        ];

        this.aliasRecord = Ext.data.Record.create([	//representa um alias
		'mail_alias'
	]);

        // create the data store
        var aliasStore = new Ext.data.ArrayStore({
            fields: [
               {name: 'mail_alias'},
            ]
        });

        // manually load local data
//        aliasStore.loadData(myData);

        //===================== ALIAS EDITOR ===================
        var alias_edit = new Ext.form.TextField();

        //========================= GRID ALIAS ====================
        var gridAlias = new Ext.grid.EditorGridPanel({
            clickstoEdit: 1,
            viewConfig: {forceFit: true},
           // columnWidth: .50,
            store: aliasStore,
            columns: [
                {
                    id       : 'mail_alias',
                    header   : <?php echo json_encode(__('Alias')) ?>,
                    width    : 166,
                    sortable : true,
                    dataIndex: 'mail_alias',
                    editor: alias_edit
                    ,vtype: 'domain'
                }
            ],
            stripeRows: true,
            //title: 'Array Grid',
            // config options for stateful behavior
            tbar: [{
                    //================= EDIÇÃO =================
                    text: <?php echo json_encode(__('Add')) ?>,
                    icon: 'images/table_add.png',
                    cls: 'x-btn-text-icon',
                    handler: function() {

//                        Ext.Ajax.request({
//                                url: 'localhost:8009/php/movie-update.php',
//                                params: {
//                                        action: 'create',
//                                        title: 'New Movie'
//                                },
//                                success: function(resp,opt) {
                                        //var insert_id = Ext.util.JSON.decode(resp.responseText).insert_id;
                                        var rec = Ext.data.Record.create([	//representa um alias
                                            'mail_alias'
                                        ]);

                                        gridAlias.getStore().insert(0, new rec({
                                                mail_alias: ''
                                                }, gridAlias.getStore().getCount())
                                        );
                                        gridAlias.startEditing(0,0);
//                                        },
//                                failure: function(resp,opt) {
//                                        Ext.Msg.alert('Error','Unable to add movie');
//                                }
//                        });
                    }
                    },{
                    //================= EDIÇÃO =================
                    disabled: true,
                    ref: '../removeBtn',
                    text: <?php echo json_encode(__('Delete')) ?>,
                    icon: 'images/table_delete.png',
                    cls: 'x-btn-text-icon',
                    handler: function() {

                        var sm = gridAlias.getSelectionModel(),
                                            sel = sm.getSelected();
                        if(sm.hasSelection()){
                            //============ DIÁLOGO QUE PERGUNTA SE PRETENDE REMOVER ===========
                            var msg = <?php echo json_encode(__('Are you sure you want to remove {0} ?')) ?>;
                            Ext.Msg.show({
                                title: <?php echo json_encode(__('Warning')) ?>,
                                msg: String.format(msg, sel.get('mail_alias')),
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
                                                    Ext.Msg.alert(<?php echo json_encode(__('Warning')) ?>,
                                                        <?php echo json_encode(__('Done!')) ?>);
                                                    gridAlias.getStore().remove(sel);
                                                    break;
                                                case 'no':
                                                    Ext.Msg.alert(<?php echo json_encode(__('Warning')) ?>,
                                                        <?php echo json_encode(__('Operation canceled!')) ?>);
                                                    break;
                                        }
                                }
                            });
                        }else{
                            Ext.Msg.alert(<?php echo json_encode(__('Warning')) ?>,
                                <?php echo json_encode(__('You must select an alias.')) ?>);
                        }
                    }}
                    ]
                ,hideRemove:function(a){
                   // alert(a);
                    //this.tbar.get(1).hidden = true;
                },
                selModel: new Ext.grid.RowSelectionModel({	//deixa seleccionar apenas uma linha
                    singleSelect: true,
                    scope: this,
                    listeners: {
                            rowselect: function(sm, index, record) {
                                   // Ext.Msg.alert('You Selected',record.get('name'));
                            },
                            rowdeselect: function(sm, index, record){
                                   // alert(gridAlias.hideRemove("ola"));
                            }
                    }
                })
                ,listeners: {
			afteredit: function(e){
//				Ext.Ajax.request({
//					url: 'php/movie-update.php',
//					params: {
//						action: 'update',
//						id: e.record.id,
//						field: e.field,
//						value: e.value
//					},
//					success: function(resp,opt) {
//						e.record.commit();
//					},
//					failure: function(resp,opt) {
//						e.record.reject();
//					}
//				});
			}
		}
             });

        gridAlias.getSelectionModel().on('selectionchange', function(sm){
            var btnState = sm.getCount() < 1 ? true :false;
            var selected = sm.getSelected();

            //alert(gridAlias.get('tbar'));//.setDisable(btnState);
            gridAlias.removeBtn.setDisabled(!selected);

        });

        this.items = [gridAlias];

        ETMS.MAILBOX.Edit.Alias.superclass.initComponent.call(this);

    }
    ,loadData: function(){
        var myAlias = this.mailbox.get('mail_alias');

        var store = this.get(0).getStore();
        for(var i = 0; i < myAlias.length; i++){
            store.insert(0,
                new this.aliasRecord({
                    mail_alias: myAlias[i]
                    }, store.getCount())
            );
        }
    }
});

ETMS.MAILBOX.Edit.Relay = Ext.extend(Ext.Panel,{
    //border:false,
    //defaults:{border:false},

    initComponent: function(){

//================================================================
//==                            RELAY                           ==
//================================================================

//        var relayData = [
//            ['manueldias@gmail.com'],
//            ['m@alunos.uminho.pt']
//        ];

        this.relayRecord = Ext.data.Record.create([	//representa um alias
		'redirect_emails'
	]);

        // create the data store
        var relayStore = new Ext.data.ArrayStore({
            fields: [
               {name: 'redirect_emails'},
            ]
        });

        // manually load local data
//        relayStore.loadData(relayData);

        //===================== RELAY EDITOR ===================
        var relay_edit = new Ext.form.TextField();

        //=============== GRID RELAY ===============
        var gridRelay = new Ext.grid.EditorGridPanel({
            viewConfig: {forceFit: true},
            //columnWidth: .50,
            store: relayStore,
            columns: [
                {
                    id       : 'redirect_emails',
                    header   : <?php echo json_encode(__('Emails')) ?>,
                    width    : 166,
                    sortable : true,
                    dataIndex: 'redirect_emails',
                    editor   : relay_edit
                    ,vtype: 'domain'
                }
            ],
            stripeRows: true,
            //title: 'Array Grid',
            // config options for stateful behavior
            tbar: [{
                    //================= EDIÇÃO =================
                    text: <?php echo json_encode(__('Add')) ?>,
                    icon: 'images/table_add.png',
                    cls: 'x-btn-text-icon',
                    handler: function() {

//                        Ext.Ajax.request({
//                                url: 'localhost:8009/php/movie-update.php',
//                                params: {
//                                        action: 'create',
//                                        title: 'New Movie'
//                                },
//                                success: function(resp,opt) {
                                        //var insert_id = Ext.util.JSON.decode(resp.responseText).insert_id;
                                        rec = Ext.data.Record.create([	//representa um alias
                                                'redirect_emails'
                                        ]);
                                        
                                        gridRelay.getStore().insert(0, new rec({
                                                redirect_emails: ''
                                                }, gridRelay.getStore().getCount())
                                        );
                                        gridRelay.startEditing(0,0);
//                                        },
//                                failure: function(resp,opt) {
//                                        Ext.Msg.alert('Error','Unable to add movie');
//                                }
//                        });
                    }},{
                    //================= EDIÇÃO =================
                    disabled: true,
                    ref: '../removeBtn',
                    text: <?php echo json_encode(__('Delete')) ?>,
                    icon: 'images/table_delete.png',
                    cls: 'x-btn-text-icon',
                    handler: function() {

                        var sm = gridRelay.getSelectionModel(),
                                            sel = sm.getSelected();
                        if(sm.hasSelection()){
                            //============ DIÁLOGO QUE PERGUNTA SE PRETENDE REMOVER ===========
                            var msg = <?php echo json_encode(__('Are you sure you want to remove {0} ?'))?>;
                            Ext.Msg.show({
                                title: <?php echo json_encode(__('Warning')) ?>,
                                //msg: 'Are you sure you want to remove "'+sel.get('redirect_emails')+'" ?',
                                msg: String.format(msg, sel.get('redirect_emails')),
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
                                                    Ext.Msg.alert(<?php echo json_encode(__('Warning')) ?>,
                                                        <?php echo json_encode(__('Done!')) ?>);
                                                    gridRelay.getStore().remove(sel);
                                                    break;
                                                case 'no':
                                                    Ext.Msg.alert(<?php echo json_encode(__('Warning')) ?>,
                                                        <?php echo json_encode(__('Operation canceled!')) ?>);
                                                    break;
                                        }
                                }
                            });
                        }else{
                            Ext.Msg.alert(<?php echo json_encode(__('Warning')) ?>,
                                <?php echo json_encode(__('You must select an alias.')) ?>);
                        }
                    }}
                    ]
                ,hideRemove:function(a){
                    //alert(a);
                    //this.tbar.get(1).hidden = true;
                },
                selModel: new Ext.grid.RowSelectionModel({	//deixa seleccionar apenas uma linha
                    singleSelect: true,
                    scope: this,
                    listeners: {
                            rowselect: function(sm, index, record) {
                                   // Ext.Msg.alert('You Selected',record.get('name'));
                            },
                            rowdeselect: function(sm, index, record){
                                   // alert(grid.hideRemove("ola"));
                            }
                    }
                })
                ,listeners: {
			afteredit: function(e){
//				Ext.Ajax.request({
//					url: 'php/movie-update.php',
//					params: {
//						action: 'update',
//						id: e.record.id,
//						field: e.field,
//						value: e.value
//					},
//					success: function(resp,opt) {
//						e.record.commit();
//					},
//					failure: function(resp,opt) {
//						e.record.reject();
//					}
//				});
			}
		}
             });



        gridRelay.getSelectionModel().on('selectionchange', function(sm){
            var btnState = sm.getCount() < 1 ? true :false;
            var selected = sm.getSelected();

            //alert(grid.get('tbar'));//.setDisable(btnState);
            gridRelay.removeBtn.setDisabled(!selected);

        });


        this.items = [gridRelay];

        ETMS.MAILBOX.Edit.Relay.superclass.initComponent.call(this);

    }
    ,loadData: function(){
        var myRedirect = this.mailbox.get('redirect_emails');
        var red = new Array();

        var store = this.get(0).getStore();
        for(var i = 0; i < myRedirect.length; i++){
            store.insert(0,
                new this.relayRecord({
                    redirect_emails: myRedirect[i]
                    }, store.getCount())
            );
        }
 

//
//        var relayStore = new Ext.data.ArrayStore({
//            fields: [
//               {name: 'alias'},
//            ]
//        });
//        aliasStore.relayStore.loadData(relayData);
//
//        var grid = this.get(0); //.getStore().loadData(red);
//        grid.reconfigure(
//            aliasStore
//        );
    }

});

//==================== DEFINICAO DO PAINEL USADO PARA CRIAR AS DEFINICOES GERAIS DE UM UTILIZADOR ==================
ETMS.MAILBOX.Edit.Main = Ext.extend(Ext.Panel,{
    layout:'fit',
    border:false,
    ref: '../mbpanel',
    //defaults:{border:false},
    title:<?php echo json_encode(__('Main Options')) ?>
    ,initComponent: function(){
        //====================== DATA STORE =======================
        var delivery_store =  new Ext.data.ArrayStore({
            fields: ['id','name'],
            data: [['noforward',<?php echo json_encode(__('local only')) ?>],
                ['forwardonly',<?php echo json_encode(__('forward only')) ?>],
                ['noprogram',<?php echo json_encode(__('local and forward')) ?>],
                ['reply',<?php echo json_encode(__('local with automatic answer')) ?>]]
        });

//        var delivery_store = new Ext.data.ArrayStore({
//            fields: [
//               {name: 'delivery_type'},
//            ]
//        });

//        delivery_store.loadData(delivery_arr);

        //============DELIVERY TYPE MAPPER=============
//        var delivery_type = function(val){
//            switch(val){
//                case 'noforward':
//                    return 0;
//                case 'forwardonly':
//                    return 1;
//                case 'noprogram':
//                    return 2;
//                case 'reply':
//                    return 3;
//            }
//
//        }
        cota = this.maxQuota;

        //Validators -> maxQuota
        Ext.apply(Ext.form.VTypes, {
		//titleVal: /^The/i,
		quotaMask: /[0-9]/,
		quotaText: <?php echo json_encode(__('Check mail quota size exceeded')) ?>,
		quota: function(v){
                        //alert(v+" : "+this.maxQuota);
                        if(v < this.maxQuota){
                            return true;
                        }else{
                            return false;
                        }

			//return this.titleVal.test(v);
		}
	});


        var config = {
            cota: this.maxQuota,
            items: [{
                        layout:'column',
                        fieldLabel: <?php echo json_encode(__('Account')) ?>,
                        items: [{
                            xtype: 'textfield',
                            name: 'user_name',
                            required: true,
                            ref:'../user_name',
                            style:'text-align:right;',
                            allowBlank: false
                        },{
                            xtype: 'displayfield',
                            ref:'../domain',
                            value: '@'+this.domain,
                            style:'padding-top:5px;'
                        }]
                },{
                        xtype: 'textfield',
                        fieldLabel: <?php echo json_encode(__('Real Name')) ?>,
                        name: 'real_name',
                        ref:'real_name',
                        allowBlank: false
                },{
                        layout:'column',
                        fieldLabel: <?php echo json_encode(__('Change Password')) ?>,
                        items: [{
                            xtype: 'checkbox', 	//pode ser feito como os radio buttons
                            name: 'change_pwd',
                            ref: '../change_pwd',
                            checked: false,
                            hidden: true,
                            handler: function(checkbox, checked){
                                this.ownerCt.find('name','password')[0].setDisabled(!checked);
                            }
                        },{
                            xtype: 'textfield',
                            name: 'password',
                            ref: '../password',
                            inputType: 'password',
                            minLength: 6,
                            minLengthText: <?php echo json_encode(__('Password must be at least 6 characters long.')) ?>,
                            //allowBlank: false,
                            disabled: false,
                            allowBlank: false
                        }]
                },{
                        xtype: 'checkbox',
			columns: 1,
			fieldLabel: <?php echo json_encode(__('Active')) ?>,
			name: 'isActive',
                        ref: 'isActive',
			checked: true
                },{
                        xtype: 'checkbox',
			columns: 1,
			fieldLabel: <?php echo json_encode(__('Allow External Send')) ?>,
			name: 'allowExternalSend',
			checked: true,
                        ref: 'allowExternalSend'
                },{
                        scope:this,
                        layout:'column',
                        fieldLabel: <?php echo json_encode(__('Mail Quota')) ?>,
                        items: [{
                            xtype:'radio',
                            name: 'radio_maxquota', //nota: o mesmo nome em todos os radio buttons
                            boxLabel: <?php echo json_encode(__('Unlimited')) ?>,
                            inputValue: 'unlimited',
                            checked: true,
                            ref: '../mail_quota_unlimited',
                            columnWidth: .25
                         },{
                            xtype:'radio',
                            name: 'radio_maxquota', //nota: o mesmo nome em todos os radio buttons
                            boxLabel: <?php echo json_encode(__('Limited')) ?>,
                            inputValue: 'limited',
                            ref: '../mail_quota_limited',
                            columnWidth: .25,
                            listeners: {
                            check : function(checkbox, checked){
                                this.ownerCt.find('name','mailbox_quota')[0].setDisabled(!checked);
                                }
                            }
                        },{
                            xtype: 'textfield',
                            fieldLabel: '',
                            name: 'mailbox_quota',
                            scope:this,
                            //maskRe: /[0-9]/,
                            ref: '../mailbox_quota',
                            columnWidth: .25,
                            //vtype: 'quota',
                            validateValue: function(value){
                                if(parseInt(value) < parseInt(cota) || parseInt(cota) == 0){
                                    return true;
                                }else{
                                    return false;
                                }
//                                if(value.length < 1 || value === this.emptyText){ // if it's blank
//                                     if(this.allowBlank){
//                                         this.clearInvalid();
//                                         return true;
//                                     }else{
//                                         this.markInvalid(this.blankText);
//                                         return false;
//                                     }
//                                }
                                 return false;
                            },
                            disabled: true
                        },{
                            xtype: 'displayfield',
                            value: this.maxQuota+' (Bytes)',
                            ref: '../server_quota_limit',
                            columnWidth: .25,
                            style: 'color:green;padding-top:5px;'
                        }]
                    },{
                        xtype: 'combo',
                        typeAhead: true,
                        triggerAction: 'all',
                        mode: 'local',
                        store: delivery_store,
                        fieldLabel: <?php echo json_encode(__('Delivery Type')) ?>,
                        editable: false,
                        ref: 'delivery_type',
                        valueField: 'id',
                        displayField: 'name'
                },{
                        xtype: 'textarea',
                        fieldLabel: <?php echo json_encode(__('Automatic Answer')) ?>,
                        name: 'automatic_answer',
                        width: 250,
                        ref: 'automatic_answer'
                }]
        }
        Ext.apply(this, config);

        ETMS.MAILBOX.Edit.Main.superclass.initComponent.call(this);
    }
//    ,selectDeliveryType: function(idx){
//        alert(this.delivery_type.getValue());
//        this.delivery_type.select(idx, true);
//    }
    ,loadData: function(){
        if(this.mailbox != undefined){
            this.password.setDisabled(true);
            this.change_pwd.setVisible(true);

            this.user_name.setValue(this.mailbox.get('user_name'));
            this.user_name.setDisabled(true);
            this.user_name.setVisible(false);
            
            this.domain.setValue(this.mailbox.get('user_name'));

            this.real_name.setValue(this.mailbox.get('real_name'));

            if(this.mailbox.get('isActive') == 'active'){
                this.isActive.setValue(1);
            }else{
                this.isActive.setValue(0);
            }

            // alert("delivery mode: " + this.mailbox.get('delivery_type'));
            this.delivery_type.setValue( this.mailbox.get('delivery_type'));

            this.allowExternalSend.setValue(this.mailbox.get('allowExternalSend'));

            var mail_quota = this.mailbox.get('mailbox_quota');
            this.mailbox_quota.setValue(mail_quota);
            if(this.mailbox.get('mailbox_quota') > 0){
                this.mail_quota_limited.setValue(true);
            }
            
            //delivery_type
            this.automatic_answer.setValue(this.mailbox.get('automatic_answer'));

          
//            this.get(0).domain.setValue(domainObj.name);    //a grid é o único item
//            this.serverQuota = domainObj.server_quota;
//            this.get(0).addBtn.setDisabled(false);
//            var myStore = this.get(0).getStore();
//            myStore.setBaseParam('params', Ext.encode({'domain':domainObj.name}));
//            myStore.reload();
        }
    }
    ,onSave: function(){

    }

});


//==================== DEFINICAO DOS TABS ========================
ETMS.MAILBOX.Edit.Form = Ext.extend(Ext.form.FormPanel, {
    border:false
    ,url:<?php echo json_encode(url_for('etms/json'))?>
    ,monitorValid:true
    ,initComponent:function() {
        this.items = [
            {xtype:'hidden',name:'id'},
            {xtype:'tabpanel',activeItem:0,
             anchor: '100% 100%',
             defaults:{
                 //layout:'form',
                 labelWidth:140
            }
           }
        ];

        // build form-buttons
        this.buttons = [{
                            text: __('Save'),
                            formBind:true,
                            handler: this.onSave,
                            scope: this
                        }];

        ETMS.MAILBOX.Edit.Form.superclass.initComponent.call(this);

        this.loadMainPanel();
        this.loadAliasRelayPanel();
    }
    ,loadMainPanel:function(){
        var mainPanel = new ETMS.MAILBOX.Edit.Main({
            layout:'form',
            padding:10,
            domain  :this.domain,
            maxQuota:this.maxQuota,    //TODO: alterar isto para dar info do server
            mailbox: this.mailbox
        })
        if(typeof this.mailbox != 'undefined'){
            mainPanel.loadData();
        }
//        else{
//            mainPanel.selectDeliveryType(0);
//        }

        this.get(1).add(mainPanel);
    }
    ,loadAliasRelayPanel:function(){
        var aliasPanel = new ETMS.MAILBOX.Edit.Alias({
            title   :<?php echo json_encode(__('Alias')) ?>,
            layout  :'fit',
            mailbox: this.mailbox
        })
            
        var relayPanel = new ETMS.MAILBOX.Edit.Relay({
            title   :<?php echo json_encode(__('Relay')) ?>,
            layout  :'fit',
            mailbox: this.mailbox
        })

        if(typeof this.mailbox != 'undefined'){
            aliasPanel.loadData();
            relayPanel.loadData();
        }

        this.get(1).add(aliasPanel);
        this.get(1).add(relayPanel);
        
//        this.get(1).add({
//            title   :<?php echo json_encode(__('Alias and Relay')) ?>,
//            layout  :'fit',
//            items   :new ETMS.MAILBOX.Edit.AliasRelay({
//                mailbox: this.mailbox
//            })
//        });
    }
    ,onSave:function(){

        // MAIN TAB
        var mainTab = this.get(1).get(0);
        var send_data = new Object();
        send_data['user_name']      = mainTab.user_name.getValue();
        send_data['domain']         = this.domain;
        send_data['real_name']      = mainTab.real_name.getValue();
       // alert('is active:'+mainTab.isActive.getValue());
        send_data['isActive']       = mainTab.isActive.getValue();
        if(send_data['isActive']){
            send_data['isActive'] = 'active';
        }else{
            send_data['isActive'] = 'noaccess';
        }

        var dlv_type = mainTab.delivery_type.getValue();
        if(dlv_type == ""){
            send_data['delivery_type']  = "noforward";
        }else{
            send_data['delivery_type']  = dlv_type;
        }

        
        
       // alert('external send:'+mainTab.allowExternalSend.getValue());
        if(mainTab.allowExternalSend.getValue()){
            send_data['allowExternalSend'] = 1;
        }else{
            send_data['allowExternalSend'] = 0;
        }

//        if(mainTab.)
        send_data['password'] = mainTab.password.getValue();

      //  alert("mailbox size: "+mainTab.mailbox_quota.getValue());
        var quota = parseInt(mainTab.mailbox_quota.getValue());

        send_data['mailbox_quota'] = 0;
        if(quota > 0){
            send_data['mailbox_quota'] = quota;
        }

        send_data['automatic_answer']   = mainTab.automatic_answer.getValue();

        // ALIAS
        var aliasTab = this.get(1).get(1);
        var alias_store = aliasTab.get(0).getStore();
        var records = alias_store.getRange();
        
        if(records.length > 0){
            send_data['alias'] = new Array();
            for(var i=0; i<records.length; i++){
                send_data['alias'].push(records[i].get('mail_alias'));
            }
        }
        
        // FORWARD
        var forwardTab = this.get(1).get(2);
        var forward_store = forwardTab.get(0).getStore();
        var records = forward_store.getRange();

        if(records.length > 0){
            send_data['forward'] = new Array();
            for(var i=0; i<records.length; i++){
                send_data['forward'].push(records[i].get('redirect_emails'));
            }
        }
 
       // alert(dump(send_data));
      //============= ENVIAR O PEDIDO ==============
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
                requestcomplete:function(){Ext.MessageBox.hide();
                    this.end();
                }
            }
        });// end conn

        //create
        if(typeof this.mailbox == 'undefined'){
            conn.request({
            scope:this,
            url: this.url,
            params:{id:this.service_id,method:'create_user',params:Ext.encode(send_data)},
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
                this.parent_grid.getStore().reload();
                this.changeFreeMb(-1);    //actualiza o nr de mailboxs livres
//                domainForm.refreshGrid();
//                                View.notify({html:msg});
//                                this.fireEvent('updatedClientOptions',this);
            },scope:this
        });
        }else{  //edit
            conn.request({
            scope:this,
            url: this.url,
            params:{id:this.service_id,method:'edit_user',params:Ext.encode(send_data)},
            failure: function(resp,opt){

                if(!resp.responseText){
                    Ext.ux.Logger.error(resp.statusText);
                    return;
                }

                var response = Ext.util.JSON.decode(resp.responseText);
                Ext.MessageBox.alert(<?php echo json_encode(__('Error Message'))?>, response['info']);
                Ext.ux.Logger.error(response['error']);

            },
            // everything ok...
            success: function(resp,opt){
                var msg = <?php echo json_encode(__('Client options edited successfully')) ?>;
                Ext.ux.Logger.info(msg);
                this.parent_grid.getStore().reload();
//                domainForm.refreshGrid();
//                                View.notify({html:msg});
//                                this.fireEvent('updatedClientOptions',this);


            },scope:this
        });// END Ajax request
        }
    }
});

//==================== DEFINIÇAO DA WINDOW =======================

//==================== DEFINICAO DA JANELA QUE CRIA CAIXAS DE CORREIO ==================
ETMS.MAILBOX.Editor = Ext.extend(Ext.Window,{
//    title: <?php echo json_encode(__('Manage Mailbox')) ?>,
    width: 600,
    height: 400,
    closeAction: 'hide',
    layout: 'fit',
    initComponent:function(){
        var form = new ETMS.MAILBOX.Edit.Form({
                            domain  :this.domain,
                            maxQuota:this.maxQuota,
                            mailbox :this.mailbox,
                            service_id:this.service_id,
                            parent_grid:this.parent_grid,
                            changeFreeMb:this.changeFreeMb,
                            end:this.end
                        });
        this.items = [form];
        ETMS.MAILBOX.Editor.superclass.initComponent.call(this);
    }
    ,end:function(){
        this.hide();
        this.ownerCt.hide();
    }
})

</script>