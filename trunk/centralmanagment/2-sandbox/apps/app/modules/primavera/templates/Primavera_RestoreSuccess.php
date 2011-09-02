<script>

Ext.ns('Primavera.Restore');

Primavera.Restore.Form = new Ext.extend( Ext.form.FormPanel, {

    border: false,
    labelWidth: 140,
    defaults: { border:false },
    initComponent:function(){
        this.items = [
                {xtype:'hidden',name:'id'},
                {
                    anchor: '100% 100%',
                    layout: {
                        type: 'hbox',
                        align: 'stretch'  // Child items are stretched to full width
                    }
                    ,defaults: { flex: 1, layout:'form', autoScroll:true, bodyStyle:'padding:10px;', border:false}
                    ,items:[
                        {
                            items: [
                                    new Ext.form.ComboBox({
                                        id: 'combo-empresa',
                                        triggerAction: 'all',
                                        mode: 'local',
                                        editable: false,
                                        valueField: 'db',
                                        displayField: 'name',
                                        name: 'empresa',
                                        fieldLabel: __('Empresa'),
                                        store: new Ext.data.ArrayStore({
                                            fields: [
                                                'db',
                                                'name'
                                            ],
                                            sortInfo:{field:'name',direction:'ASC'}
                                        })
                                        ,listeners:{select:{fn:function(combo, value) {
                                                var comboBkp = Ext.getCmp('combo-backup');
                                                comboBkp.clearValue();
                                                comboBkp.store.filter([{ property: 'db', value: combo.getValue(), exactMatch: true }]);
                                            }}
                                        }
                                    }),
                                    new Ext.form.ComboBox({
                                        id: 'combo-backup',
                                        triggerAction:'all',
                                        mode: 'local',
                                        editable: false,
                                        valueField: 'bkp',
                                        displayField: 'file',
                                        name: 'backup',
                                        fieldLabel: __('Backup'),
                                        minListWidth:250,
                                        lastQuery:'',
                                        store: new Ext.data.ArrayStore({
                                            fields: [
                                                'db',
                                                'bkp',
                                                'file'
                                            ],
                                            sortInfo:{field:'file',direction:'DESC'}
                                        })
                                    })
                                    ,{ fieldLabel: __('Full restore'),
                                      name: 'fullrestore',
                                      xtype:'checkbox',listeners: { 
                                                                'check':{scope:this,fn:function(cbox,ck){
                                                                                                        if(ck){
                                                                                                            this.form.findField('empresa').disable();
                                                                                                            this.form.findField('backup').disable();
                                                                                                        } else {
                                                                                                            this.form.findField('empresa').enable();
                                                                                                            this.form.findField('backup').enable();
                                                                                                        }
                                                                                                }}} }

                            ]
                        }
                    ]
                    ,buttons: [{
                           text: __('Save'),
                           formBind:true,
                           handler: this.onSave,
                           scope: this
                       },
                       {
                           text:__('Cancel'),
                           scope:this,
                           handler:function(){(this.ownerCt).close()}
                       }]

                }
            ];

        Primavera.Restore.Form.superclass.initComponent.call(this);
    }
    ,onSave: function(){
        var form_values = this.getForm().getValues();

        var send_data = {};
        var method = 'primavera_restore';

        if( form_values['fullrestore'] ){
            method = 'primavera_fullrestore';
        } else {
            method = 'primavera_restore';
            send_data['database'] = Ext.getCmp('combo-empresa').getValue();
            send_data['file'] = Ext.getCmp('combo-backup').getValue();
        }

        // process
        var conn = new Ext.data.Connection({
            listeners:{
                // wait message.....
                beforerequest:function(){
                    Ext.MessageBox.show({
                        title: <?php echo json_encode(__('Please wait...')) ?>,
                        msg: <?php echo json_encode(__('Restore...')) ?>,
                        width:300,
                        wait: true,
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
            url: <?php echo json_encode(url_for('primavera/json')) ?>,
            params: {
                id: this.service_id,
                method: method,
                params: Ext.encode(send_data)                
            },            
            scope:this,
            success: function(resp,opt) {

                var response = Ext.util.JSON.decode(resp.responseText);                

                Ext.ux.Logger.info(response['agent'],response['response']);
                this.ownerCt.fireEvent('onSave');                

            },
            failure: function(resp,opt) {
                
                var response = Ext.util.JSON.decode(resp.responseText);
                Ext.ux.Logger.error(response['agent'],response['error']);

                Ext.Msg.show({
                    title: String.format(<?php echo json_encode(__('Error {0}')) ?>,response['agent']),
                    buttons: Ext.MessageBox.OK,
                    msg: String.format(<?php echo json_encode(__('Unable to restore!')) ?>)+'<br>'+response['info'],
                    icon: Ext.MessageBox.ERROR});

            }
        });// END Ajax request
    }
    ,loadRecord: function(){
        this.form.findField('id').setValue(this.service_id);
        this.load({url:<?php echo json_encode(url_for('primavera/json'))?>,params:{id:this.service_id,method:'primavera_backupinfo'} ,waitMsg:'Loading...'
                        ,success:function(f,a){
                            if( a.result['data']['empresas'].length > 0 ){
                                var empresas = a.result['data']['empresas'];
                                var dataEmpresa = new Array();
                                var dataBackup = new Array();
                                for(var i=0; i<empresas.length; i++){
                                    var e = new Array(empresas[i]['db'],empresas[i]['name']);
                                    dataEmpresa.push(e);
                                    if( empresas[i]['bkps'] ){
                                        var bkps = empresas[i]['bkps'];
                                        for(var k=0; k<bkps.length; k++){
                                            var f = new Array(empresas[i]['db'],bkps[k]['name'],bkps[k]['name']);
                                            dataBackup.push(f);
                                        }
                                    }
                                }
                                
                                Ext.getCmp('combo-backup').store.loadData(dataBackup);
                                Ext.getCmp('combo-empresa').store.loadData(dataEmpresa);

                                Ext.getCmp('combo-empresa').setValue(dataEmpresa[0][0]);
                                Ext.getCmp('combo-backup').clearValue();
                                Ext.getCmp('combo-backup').store.filter([{ property: 'db', value: dataEmpresa[0][0], exactMatch: true }]);
                            }
                        }
                        ,scope: this
                    });
    }
});

Primavera.Restore.Window = function(config) {

    Ext.apply(this,config);

    Primavera.Restore.Window.superclass.constructor.call(this, {
        width:360
        ,height:240
        ,border:false
        ,modal:true
        ,layout:'fit'
        ,items:[ new Primavera.Restore.Form({service_id:this.service_id})]
    });
};


Ext.extend(Primavera.Restore.Window, Ext.Window,{
    loadData:function(data){
        this.items.get(0).loadRecord(data);
    }
});

</script>

