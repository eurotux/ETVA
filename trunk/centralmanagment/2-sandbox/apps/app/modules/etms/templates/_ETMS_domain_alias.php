<script>
/*
 *
 * ALIAS
 *
 */
Ext.ns('ETMS.DOMAIN.Alias');

ETMS.DOMAIN.AliasPanel = Ext.extend(Ext.Panel,{
    layout:'fit',
    border:false,
    //defaults:{border:false},
    title:<?php echo json_encode(__('Alias List')) ?>,
     initComponent:function(){


    //======================== DATA STORE ALIAS =========================

    var aliasRecord = Ext.data.Record.create([	//representa um alias
            'alias'
    ]);

    // sample static data for the store
//    var myData = [
//        ['tmn.eu'],
//        ['eurotux.pt']
//    ];

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

    // create the data store
//    var store = new Ext.data.ArrayStore({
//        fields: [
//           {name: 'alias'},
//        ]
//    });


    var storeParams = function() {
        return Ext.encode({'domain':this.selectedDomain});
    }

    var store = new Ext.data.JsonStore({
        proxy:new Ext.data.HttpProxy({url:<?php echo json_encode(url_for('etms/json'))?>}),
        totalProperty: 'total',
        baseParams:{id:this.service_id,method:'select_alias',params:storeParams()},
        root: 'value',
        fields: [
           {name: 'alias'}
        ]
        //,autoLoad: true
    });

    // manually load local data
//    store.loadData(myData);

    //===================== ALIAS EDITOR ===================
    var alias_edit = new Ext.form.TextField();

    // create the Grid
    var gridAlias = new Ext.grid.EditorGridPanel({
       // viewConfig: {forceFit: true},
        service_id:this.service_id,
        selectedDomain:this.selectedDomain,
        store: store,
        colModel: new Ext.grid.ColumnModel([
            {
                id       : 'alias',
                header   : <?php echo json_encode(__('Alias')) ?>,
                width    : 166,
                sortable : true,
                dataIndex: 'alias',
                editor   : alias_edit
                ,vtype: 'domain'
                //,editable : false
            }
            ]),

//        columns: [
//            {
//                id       : 'alias',
//                header   : 
//                width    : 166,
//                sortable : true,
//                dataIndex: 'alias',
//                editor   : alias_edit,
//                editable : false
//            }
//        ],
        stripeRows: true,
        height: 350,
        width: 600,
        bbar: new Ext.ux.grid.TotalCountBar({
            store:store
            ,displayInfo:true
//            ,items:[{
//                text: <?php echo json_encode(__('Save')) ?>,
//                cls: 'x-btn-text-icon',
//                handler: function() {
//                    gridAlias.getStore().insert(0, new aliasRecord({
//                            alias: ''
//                            }, gridAlias.getStore().getCount())
//                    );
//                    gridAlias.startEditing(0,0);
//                }
//        }]
        }),
        //title: 'Array Grid',
        // config options for stateful behavior
        tbar: [{
                //================= EDIÇÃO =================
                text: <?php echo json_encode(__('Add')) ?>,
                scope:this,
                icon: 'images/table_add.png',
                cls: 'x-btn-text-icon',
                handler: function() {
                    gridAlias.getStore().insert(0, new aliasRecord({
                            alias: ''
                            }, gridAlias.getStore().getCount())
                    );
                    gridAlias.startEditing(0,0);
                }},{
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
                        var msg = <?php echo json_encode(__('Are you sure you want to remove {0} ?'))?>;
                        Ext.Msg.show({
                            title: <?php echo json_encode(__('Warning')) ?>,
                            msg: String.format(msg, sel.get('alias')),
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
                                                Ext.Ajax.request({
                                                    url:<?php echo json_encode(url_for('etms/json'))?>,
                                                    params: {
                                                            id: gridAlias.service_id,
                                                            method: 'delete_alias',
                                                            params: Ext.encode({'domain':gridAlias.selectedDomain, 'alias':[sel.get('alias')]})
                                                    },
                                                    success: function(resp,opt) {
                                                            Ext.Msg.alert(<?php echo json_encode(__('Warning')) ?>,
                                                            <?php echo json_encode(__('Done!')) ?>);
                                                                gridAlias.getStore().remove(sel);
                                                            //sel.commit();
                                                    },
                                                    failure: function(resp,opt) {
                                                            //sel.reject();
                                                    }
                                                });
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
                alert(a);
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
            }),

            //Verificar se há alterações -> ajax
            listeners: {
                afteredit:{scope:this, fn:function(e){
               
                    Ext.Ajax.request({
                        url:<?php echo json_encode(url_for('etms/json'))?>,
                        params: {
                                id: this.service_id,
                                method: 'change_alias',
                                field: e.field,
                                params: Ext.encode({'domain':this.selectedDomain, 'org':e.originalValue, 'dst':e.value})
                        },
                        success: function(resp,opt) {
                            Ext.MessageBox.alert(<?php echo json_encode(__('success')) ?>,
                                <?php echo json_encode(__("Alias added with success")) ?>);
                            gridAlias.getStore().reload();
                            e.record.commit();
                        },
                        failure: function(resp,opt) {
                            var response = Ext.util.JSON.decode(resp.responseText);
                            Ext.MessageBox.alert(<?php echo json_encode(__('Error Message'))?>, response['info']);
                            Ext.ux.Logger.error(response['error']);
                            gridAlias.getStore().reload();
                            e.record.reject();
                        }
                    });

                }
            }}

         });

        gridAlias.getSelectionModel().on('selectionchange', function(sm){
            var btnState = sm.getCount() < 1 ? true :false;
            var selected = sm.getSelected();

            //alert(grid.get('tbar'));//.setDisable(btnState);
            gridAlias.removeBtn.setDisabled(!selected);
        });

        this.items = [gridAlias];



        ETMS.DOMAIN.Main.superclass.initComponent.call(this);

    }
    ,loadData:function(){
        this.get(0).getStore().setBaseParam('params', Ext.encode({'domain':this.selectedDomain}) );
        this.get(0).selectedDomain = this.selectedDomain;
        this.get(0).getStore().reload();
    }
});

//// create pre-configured grid class
//ETMS.DOMAIN.Alias = Ext.extend(Ext.Window, {
//        title: 'Domain Aliasing',
//        width: 600,
//        height: 300,
//      //  closeAction: 'hide',
//        layout: 'fit',
//
//        initComponent: function(){
//
//            var aliasForm = new Ext.FormPanel({
//                url: 'php/submitMovie.php',	//URL do submit
//                frame: true,
//                width: 550,
//                //Campos do formulário
//                items: [{
//                        xtype: 'textfield',
//                        fieldLabel: 'Alias Domain',
//                        hidden:this.xpto,
//                        name: 'title',
//                        allowBlank: false
//                },{
//                        xtype: 'textfield',
//                        fieldLabel: 'Real Domain',
//                        name: 'name',
//                        allowBlank: false
//                }],
//                buttons: [{
//                        text: 'Add',
//                        handler: function(){
//
//                        }
//                },{
//                        text: 'Reset',
//                        handler: function(){
//                                aliasForm.getForm().reset();		//apaga o formulário
//                        }
//                }]
//
//                });
//                //================ TOP BAR ====================
//                var options =  [{
//                        text: 'Close',
//                        scope: this,
//                        handler: function(){
//                          this.hide();
//                        }
//                }]
//
//                 this.items = [aliasForm];
//                 this.buttons = options;
//
//                 ETMS.DOMAIN.Main.superclass.initComponent.call(this);
//
//
//        }
//        ,loadData:function(data){
//
//            ((this.get(0)).getForm()).loadRecord(data);
//        }
//
//});

</script>
