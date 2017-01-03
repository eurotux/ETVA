<script>

Ext.ns('Primavera.Main');

Primavera.Main.Form = new Ext.extend( Ext.form.FormPanel, {

    id: 'primavera-form-main',
    border: false,
    labelWidth: 140,
    defaults: { border:false },
    initComponent:function(){
        this.items = [
                {xtype:'hidden',id:'service-id',name:'id'},
                {
                    anchor: '100% 100%',
                    layout: {
                        type: 'vbox',
                        align: 'stretch'  // Child items are stretched to full width
                    }
                    ,defaults: { flex: 1, autoScroll:true, bodyStyle:'padding:10px;', border:false }
                    ,items: [
                        {
                            anchor: '100% 100%',
                            layout: {
                                type: 'hbox',
                                align: 'stretch'  // Child items are stretched to full width
                            }
                            ,defaults: { flex: 1, layout:'form', autoScroll:false, bodyStyle:'padding:10px;', border:false }
                            ,items:[
                                {
                                    defaults: { flex: 1, autoScroll:true, border:false },
                                    items: [
                                            { html: __('Disk usage'), height: '10px' },
                                            new Ext.chart.PieChart({ 
                                                            id: 'space-piechart',
                                                            store: new Ext.data.ArrayStore({  
                                                                            fields:[{name:'type'},{name:'space', type:'float'}]
                                                                        }),
                                                            dataField: 'space',
                                                            categoryField: 'type'
                                                    })
                                    ]
                                },
                                /*{
                                    items: [
                                            { fieldLabel:__('Last backup date'),
                                              name: 'lastbackupdate',
                                              xtype:'displayfield' }
                                    ]
                                },*/
                                {
                                    items: [
                                            { fieldLabel: __('N. Empresas'),
                                              name: 'nempresas',
                                              xtype:'displayfield' },
                                            { fieldLabel: __('Segurança Activa'),
                                              name: 'segurancaactiva',
                                              xtype:'displayfield' },
                                            { fieldLabel: __('Language'),
                                              name: 'language',
                                              xtype:'displayfield' },
                                            { fieldLabel: __('License'),
                                              name: 'license',
                                              xtype:'displayfield' },
                                            { fieldLabel: __('N. Utilizadores'),
                                              name: 'nutilizadores',
                                              xtype:'displayfield' },
                                            { fieldLabel: __('Segurança Pro. Emp. Activa'),
                                              name: 'segurancaproempactiva',
                                              xtype:'displayfield' },
                                            { fieldLabel: __('N. Postos'),
                                              name: 'npostos',
                                              xtype:'displayfield' },
                                            { fieldLabel: __('Modo Segurança'),
                                              name: 'modoseguranca',
                                              xtype:'displayfield' }
                                    ]
                                }
                                ,{
                                    items: [
                                            { fieldLabel:__('Primavera is running'),
                                              name: 'primaverarunservice',
                                              xtype:'displayfield' },
                                            { fieldLabel:__('SQL Server is running'),
                                              name: 'sqlserverrunservice',
                                              xtype:'displayfield' },
                                            { fieldLabel: __('IP address'),
                                              name: 'ipaddr',
                                              xtype:'displayfield' },
                                            { fieldLabel: __('Netmask'),
                                              name: 'netmask',
                                              xtype:'displayfield' },
                                            { fieldLabel: __('Gateway'),
                                              name: 'gateway',
                                              xtype:'displayfield' }
                                    ]
                                }
                                /*,{
                                    items: [
                                            { fieldLabel:__('Primavera is running'),
                                              name: 'primaverarunservice',
                                              xtype:'displayfield' },
                                            { fieldLabel:__('SQL Server is running'),
                                              name: 'sqlserverrunservice',
                                              xtype:'displayfield' }
                                    ]
                                }*/
                            ]
                        }
                    ]
                }
            ];

        Primavera.Main.Form.superclass.initComponent.call(this);

        this.on({
            'afterRender': function() {
                this.loadRecord();
            }
        });
    }
    ,loadRecord: function(){
        Ext.getCmp('service-id').setValue(this.service_id);
        this.load({
                    url:<?php echo json_encode(url_for('primavera/json'))?>,
                    params:{id:this.service_id,method:'primavera_info'} ,
                    waitMsg: <?php echo json_encode(__('Loading...')) ?>,
                    scope:this,
                    success:function(f,a){
                        var tfree = a.result['data']['totalfreemb'];
                        var tused = a.result['data']['totalmb'] - a.result['data']['totalfreemb'];
                        var data = [
                                    [__('Free'), tfree],
                                    [__('Used'), tused]
                                    ];
                        var chart = Ext.getCmp('space-piechart').store.loadData(data);
                        
                    }
                });
    }
});

var frmPrimaveraMain = new Primavera.Main.Form({});

/*
 * Primavera Main Panel
 */
Primavera.Main = function(config){
	Ext.apply(this,config);

    this.title = <?php echo json_encode(__('Primavera')) ?>;
	this.service_id = this.service['id'];

    this.defaults = { border:false, service_id: this.service_id };
    this.items = [ frmPrimaveraMain ];

    this.tbar = [
                    {
                        text: <?php echo json_encode(__('Backup')) ?>,
                        ref: '../backupBtn',
                        iconCls: 'icon-backup',
                        disabled: false,
                        url: <?php echo(json_encode(url_for('primavera/Primavera_Backup')))?>,
                        call:'Primavera.Backup',
                        scope:this,
                        callback: function(item) {
                            var service_id = (item.scope).get(0).form.findField('id').getValue();
                            var window = new Primavera.Backup.Window({
                                                title: String.format(<?php echo json_encode(__('Backup')) ?>),
                                                service_id:service_id });

                            window.on({
                                show:{fn:function(){window.loadData({service_id:service_id});}}
                                ,onSave:{fn:function(){
                                        this.close();
                                        var parentCmp = Ext.getCmp((item.scope).id);
                                        parentCmp.fireEvent('refresh',parentCmp);
                                }}
                            });
                            
                            window.show();
                        },
                        handler: function(btn){View.loadComponent(btn);}
                    },
                    {
                        text: <?php echo json_encode(__('Restore')) ?>,
                        ref: '../restoreBtn',
                        iconCls: 'icon-restore',
                        disabled: false,
                        url: <?php echo(json_encode(url_for('primavera/Primavera_Restore')))?>,
                        call:'Primavera.Restore',
                        scope:this,
                        callback: function(item) {
                            var service_id = (item.scope).get(0).form.findField('id').getValue();
                            var window = new Primavera.Restore.Window({
                                                title: String.format(<?php echo json_encode(__('Restore')) ?>),
                                                service_id:service_id });

                            window.on({
                                show:{fn:function(){window.loadData({service_id:service_id});}}
                                ,onSave:{fn:function(){
                                        this.close();
                                        var parentCmp = Ext.getCmp((item.scope).id);
                                        parentCmp.fireEvent('refresh',parentCmp);
                                }}
                            });
                            
                            window.show();
                        },
                        handler: function(btn){View.loadComponent(btn);}
                    },
                    '-',
                    {
                        text: <?php echo json_encode(__('Stop Primavera')) ?>,
                        ref: '../stopBtn',
                        iconCls: 'icon-stop',
                        disabled: false,
                        scope:this,
                        handler: function(item) {
                                            Ext.Msg.show({
                                                title: item.text,
                                                buttons: Ext.MessageBox.YESNO,
                                                scope:this,
                                                msg: String.format(<?php echo json_encode(__('Do you wan\'t stop Primavera?')) ?>),
                                                fn: function(btn){
                                                                if (btn == 'yes'){
                                                                    var conn = new Ext.data.Connection({
                                                                        listeners:{
                                                                            // wait message.....
                                                                            beforerequest:function(){
                                                                                Ext.MessageBox.show({
                                                                                    title: <?php echo json_encode(__('Please wait...')) ?>,
                                                                                    msg: <?php echo json_encode(__('Stop Primavera...')) ?>,
                                                                                    width:300,
                                                                                    wait:true
                                                                                 //   modal: true
                                                                                });
                                                                            },// on request complete hide message
                                                                            requestcomplete:function(){Ext.MessageBox.hide();}
                                                                            ,requestexception:function(c,r,o){Ext.Ajax.fireEvent('requestexception',c,r,o);}
                                                                        }
                                                                    });// end conn
                                                                    conn.request({
                                                                        url: <?php echo json_encode(url_for('primavera/json'))?>,
                                                                        params: {id:this.service_id,method:'primavera_stop'},
                                                                        scope:this,
                                                                        success: function(resp,opt) {

                                                                            var response = Ext.util.JSON.decode(resp.responseText);
                                                                            Ext.ux.Logger.info(response['agent'], response['response']);
                                                                            this.loadRecord({id:this.server_id});
                                                                        }
                                                                        ,failure: function(resp,opt) {
                                                                            var response = Ext.util.JSON.decode(resp.responseText);
                                                                            if(response && resp.status!=401)
                                                                                Ext.Msg.show({
                                                                                    title: String.format(<?php echo json_encode(__('Error {0}')) ?>,response['agent']),
                                                                                    buttons: Ext.MessageBox.OK,
                                                                                    msg: String.format(<?php echo json_encode(__('Unable to stop!')) ?>)+'<br>'+response['info'],
                                                                                    icon: Ext.MessageBox.ERROR});
                                                                        }
                                                                    });// END Ajax request
                                                                }//END button==yes
                                                            }
                                                });
                                        }
                    },
                    {
                        text: <?php echo json_encode(__('Start Primavera')) ?>,
                        ref: '../startBtn',
                        iconCls: 'icon-start',
                        disabled: false,
                        scope:this,
                        handler: function(item) {
                                            Ext.Msg.show({
                                                title: item.text,
                                                buttons: Ext.MessageBox.YESNO,
                                                scope:this,
                                                msg: String.format(<?php echo json_encode(__('Do you wan\'t start Primavera?')) ?>),
                                                fn: function(btn){
                                                                if (btn == 'yes'){
                                                                    var conn = new Ext.data.Connection({
                                                                        listeners:{
                                                                            // wait message.....
                                                                            beforerequest:function(){
                                                                                Ext.MessageBox.show({
                                                                                    title: <?php echo json_encode(__('Please wait...')) ?>,
                                                                                    msg: <?php echo json_encode(__('Start Primavera...')) ?>,
                                                                                    width:300,
                                                                                    wait:true
                                                                                 //   modal: true
                                                                                });
                                                                            },// on request complete hide message
                                                                            requestcomplete:function(){Ext.MessageBox.hide();}
                                                                            ,requestexception:function(c,r,o){Ext.Ajax.fireEvent('requestexception',c,r,o);}
                                                                        }
                                                                    });// end conn
                                                                    conn.request({
                                                                        url: <?php echo json_encode(url_for('primavera/json'))?>,
                                                                        params: {id:this.service_id,method:'primavera_start'},
                                                                        scope:this,
                                                                        success: function(resp,opt) {

                                                                            var response = Ext.util.JSON.decode(resp.responseText);
                                                                            Ext.ux.Logger.info(response['agent'], response['response']);
                                                                            this.loadRecord({id:this.server_id});
                                                                        }
                                                                        ,failure: function(resp,opt) {
                                                                            var response = Ext.util.JSON.decode(resp.responseText);
                                                                            if(response && resp.status!=401)
                                                                                Ext.Msg.show({
                                                                                    title: String.format(<?php echo json_encode(__('Error {0}')) ?>,response['agent']),
                                                                                    buttons: Ext.MessageBox.OK,
                                                                                    msg: String.format(<?php echo json_encode(__('Unable to start!')) ?>)+'<br>'+response['info'],
                                                                                    icon: Ext.MessageBox.ERROR});
                                                                        }
                                                                    });// END Ajax request
                                                                }//END button==yes
                                                            }
                                                });
                                        }
                    },
                    '-',
                    {
                        text: <?php echo json_encode(__('Change IP')) ?>,
                        ref: '../chgipBtn',
                        iconCls: 'icon-config',
                        disabled: false,
                        url: <?php echo(json_encode(url_for('primavera/Primavera_ChangeIP')))?>,
                        call:'Primavera.ChangeIP',
                        scope:this,
                        callback: function(item) {
                            var service_id = (item.scope).get(0).form.findField('id').getValue();
                            var window = new Primavera.ChangeIP.Window({
                                                title: String.format(<?php echo json_encode(__('Change IP')) ?>),
                                                service_id:service_id });

                            window.on({
                                show:{fn:function(){window.loadData({service_id:service_id});}}
                                ,onSave:{fn:function(){
                                        this.close();
                                        var parentCmp = Ext.getCmp((item.scope).id);
                                        parentCmp.fireEvent('refresh',parentCmp);
                                }}
                            });
                            
                            window.show();
                        },
                        handler: function(btn){View.loadComponent(btn);}
                    },
                    '-',
                    {
                        text: <?php echo json_encode(__('Users')) ?>,
                        ref: '../usersBtn',
                        iconCls: 'icon-users',
                        disabled: false,
                        url: <?php echo(json_encode(url_for('primavera/Primavera_Users')))?>,
                        call:'Primavera.Users',
                        scope:this,
                        callback: function(item) {
                            var service_id = (item.scope).get(0).form.findField('id').getValue();
                            var window = new Primavera.Users.Window({
                                                title: String.format(<?php echo json_encode(__('Users')) ?>),
                                                service_id:service_id });
                            window.on({
                                show:{fn:function(){window.loadData({service_id:service_id});}}
                                ,onSave:{fn:function(){
                                        this.close();
                                        var parentCmp = Ext.getCmp((item.scope).id);
                                        parentCmp.fireEvent('refresh',parentCmp);
                                }}
                            });
                            window.show();
                        },
                        handler: function(btn){View.loadComponent(btn);}
                    }
                    ,'->',
                    {
                        text: __('Refresh'),
                        xtype: 'button',
                        ref:'../btn_refresh',
                        tooltip: __('Refresh'),
                        iconCls: 'x-tbar-loading',
                        scope:this,
                        handler: function(button,event)
                        {                            
                            var parentCmp = Ext.getCmp((button.scope).id);
                            parentCmp.fireEvent('refresh',parentCmp);
                        }
                    },
                ];

    Primavera.Main.superclass.constructor.call(this, { 
                                                        loadRecord: function(){
                                                             Ext.getCmp('primavera-form-main').loadRecord();
                                                        }
                                                    });

    this.on({
        'activate': function(){
            if(this.items.length>0){
                for(var i=0,len=this.items.length;i<len;i++){
                    var item = this.items.get(i);
                    item.fireEvent('reload');
                }
            }
		}
        ,refresh:{ scope:this, fn:function(){                                    
                    this.loadRecord();
                }
        }

    });
}

// define public methods
Ext.extend(Primavera.Main, Ext.Panel,{ });

</script>
