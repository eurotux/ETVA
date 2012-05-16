<?php Etva::loadServicesPartials($etva_server); ?>
<?php include_partial('Utils'); ?>
<script>
Ext.ns('ETMS.SERVER');


/*
* .View
*
* Build all panels. Entry point for module
*
*/
// =================== VTYPES =====================
Ext.apply(Ext.form.VTypes, {
    domainVal: /.+\..+/,
    domainMask: /./,
    domainText: <?php echo json_encode(__('Irregular Domain Name.')) ?>,
    domain: function(v){
            return this.domainVal.test(v);
    }
});


ETMS.View = function(config) {
    Ext.apply(this,config);

    var services_store = new Ext.data.JsonStore({
                autoLoad:true,
                url: <?php echo json_encode(url_for('service/jsonGetServices')) ?>,
                baseParams:{sid:this.server['id']},
                totalProperty: 'total',
                root: 'data',
                fields: ['id','name_tmpl'],
                //scope:this,
                listeners:{
                    'beforeload':function(){Ext.getBody().mask(<?php echo json_encode(__('Retrieving data...')) ?>);}
                    ,'load':{scope:this,fn:function(store){ //passa a store para a main
                        this.buildItem(store);
                        Ext.getBody().unmask();
                    }}
                }
            });


    ETMS.View.superclass.constructor.call(this, {
        activeTab:0,
        defaults: {border:false},
        items: []
    });


    this.on({
            'reload':function(){
                var active = this.getActiveTab();
                //active.fireEvent('refresh');
            }
    });

}

Ext.extend(ETMS.View, Ext.TabPanel,{
    /*
     * build services panels
     */
    buildItem:function(store){

        var records = store.getRange();
        var server_tmpl = this.server['agent_tmpl'];
        var server_id = this.server['id'];

        var services_ids = [];
        for(var i = 0; i < records.length;i++)
        {
            services_ids[records[i].data['name_tmpl']] = records[i].data;
            //alert(records[i].data['name_tmpl']);
        }

        //acrescentar aqui os componentes
        var main = new ETMS.SERVER({server_id:server_id, service:services_ids['server']});
        var mailbox = new ETMS.MAILBOX({server:this.server,service:services_ids['mailbox']});
        var domain = new ETMS.DOMAIN({server:this.server,service:services_ids['domain'],maintab:this,mbtabidx:2});

        var items = [main, domain, mailbox];

        this.add(items);
        this.doLayout();
        this.setActiveTab(0);

        //check if the mail server was already initialized, otherwise present the intialization wizard
        var conn = new Ext.data.Connection({
            scope:this,
            listeners:{
                scope:this,
            // wait message.....
                beforerequest:function(){
                    Ext.MessageBox.show({
                        title: <?php echo json_encode(__('Please wait'))?>,
                        msg: <?php echo json_encode(__('Checking etmailserver configuration'))?>,
                        width:300,
                        wait:true,
                        modal: false
                    });
                },// on request complete hide message
                requestcomplete:function(){
                    Ext.MessageBox.hide();
                }
            }
        });// end conn
        //create
        if(typeof this.mailbox == 'undefined'){
            conn.request({
            scope:this,
            url: <?php echo json_encode(url_for('etms/json'))?>,
            params:{id:services_ids['server']['id'],method:'initialize'},
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
                var response = Ext.util.JSON.decode(resp.responseText);
                // Check if etms was already initialized
                if(response['value'][0] == 'Already initialized'){
                    return;
                }

                var a = new ETMS.SERVER.Initiator({service_id: services_ids['server']['id']});
                a.setMessage(response['value'][0]);
                a.show();
                // show initialization log



//                var msg = 'Client options edited successfully';
//                Ext.ux.Logger.info(msg);
//                this.parent_grid.getStore().reload();
//                this.changeFreeMb(-1);    //actualiza o nr de mailboxs livres
            },scope:this
        });
        }



        //main.setVisible(false);
        //mailbox.setVisible(false);
        //domain.setVisible(false);
    }
});


ETMS.SERVER.space = Ext.extend(Ext.Panel,{
    padding: '30 0 0 0',
    layout:'column',
    border:false,
    //title: <?php echo json_encode(__('Service')) ?>,

    initComponent: function(){
        this.items = [
            {
                xtype: 'displayfield',
                name: 'space_field',
                value : <?php echo json_encode(__('File Space Usage:')) ?>
                ,width: 120
            },
            {
                xtype: 'displayfield',
                name: 'space',
                ref: 'space'
                ,width: 55
            },
            {
                scope:this,
                xtype:'button',
                handler: this.spaceOccupied,
                icon: 'images/silk/icons/zoom.png'
            }
        ];

        ETMS.SERVER.space.superclass.initComponent.call(this);
    }
    ,spaceOccupied: function(){
        var send_data = new Object();

        if(this.domain != undefined){
            send_data['domain'] = this.domain;
        }
        
        if(this.mailbox != undefined){
            send_data['mailbox'] = this.mailbox;
        }
         
        Ext.Ajax.request({
            url:<?php echo json_encode(url_for('etms/json'))?>,
            scope:this,
            params: {
                    id: this.service_id,
                    method: 'occupied_Space'
            },
            success: function(resp,opt) {
                    var decoded_data = Ext.decode(resp.responseText);
                    var spaceObj = decoded_data['value'][0];

                    this.space.setValue(spaceObj.space);
            },
            failure: function(resp,opt) {
                    Ext.Msg.alert(<?php echo json_encode(__('Warning')) ?>,
                    <?php echo json_encode(__('Error reloading data!')) ?>);
                    //sel.reject();
            }

        });
    }
});

// REGISTAR UM XTYPE
Ext.reg('server_space', ETMS.SERVER.space);
</script>
