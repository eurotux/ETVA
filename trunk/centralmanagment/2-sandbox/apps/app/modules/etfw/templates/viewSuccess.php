<?php Etva::loadServicesPartials($etva_server); ?>
<script>
Ext.ns('ETFW');

ETFW.Main = Ext.extend(Ext.Panel,{
    title: <?php echo json_encode(__('Main panel')) ?>,
    layout:'fit',
    defaults:{border:false},
    initComponent:function(){

        var webmin_url = this.webmin_service['params']['url'];

        this.items = [{
                layout:'vbox',
                layoutConfig:{
                    padding:'5',
                    align:'left'
                },
                defaults:{margins:'0 0 15 0'},
                items:[{ xtype:'button',text:'Network setup wizard'
                            ,url:<?php echo json_encode(url_for('etfw/ETFW_wizard?tpl=default&sid='))?>+this.server_id
                            ,handler: View.clickHandler
                        }
                        ,{
                            xtype:'button',
                            text:'Webmin',
                            handler: function(b,e){
                                window.open(webmin_url,'_blank');
                            }
                        }
                ]
        }];

        ETFW.Main.superclass.initComponent.call(this);

        this.on({
            'activate':function(){
                if(this.items.length>0){
                  for(var i=0,len=this.items.length;i<len;i++){
                      var item = this.items.get(i);
                      item.fireEvent('reload');
                  }
                }
            }
        });

    }

});


/*
* .View
*
* Build all panels. Entry point for module
*
*/

ETFW.View = function(config) {

    Ext.apply(this,config);

    var services_store = new Ext.data.JsonStore({
                autoLoad:true,
                url: <?php echo json_encode(url_for('service/jsonGetServices')) ?>,
                baseParams:{sid:this.server['id']},
                totalProperty: 'total',
                root: 'data',
                fields: ['id','name_tmpl','params'],
                //scope:this,
                listeners:{
                    'beforeload':function(){Ext.getBody().mask(<?php echo json_encode(__('Retrieving data...')) ?>);}
                    ,'load':{scope:this,fn:function(store){
                        this.buildItem(store);
                        Ext.getBody().unmask();
                    }}
                }
            });

    ETFW.View.superclass.constructor.call(this, {
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

Ext.extend(ETFW.View, Ext.TabPanel,{
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
        }

        var mainParms = {server_id:server_id};
        if( services_ids['webmin'] ){
            mainParms['webmin_service'] = services_ids['webmin'];
        }
        
        var main = new ETFW.Main(mainParms);
        var items = [main];

        if(services_ids['network'] && services_ids['dhcp']){
            var dhcp = new ETFW.DHCP({server:this.server,network_service:services_ids['network'],service:services_ids['dhcp']});
            items.push(dhcp);
        }

        if(services_ids['network'] && services_ids['firewall']){
            var firewall = new ETFW.Firewall({server:this.server,network_service:services_ids['network'],service:services_ids['firewall']});
            items.push(firewall);
        }

        if(services_ids['network']){
            var network = new ETFW.Network({server:this.server,service:services_ids['network']});
            items.push(network);
        }

        if(services_ids['squid']){
            var squid = new ETFW.Squid({server:this.server,service:services_ids['squid']});
            items.push(squid);
        }
        
        if(services_ids['snmp']){
            var snmp = new ETFW.SNMP({service:services_ids['snmp']});
            items.push(snmp);
        }        

        this.add(items);
        this.doLayout();
        this.setActiveTab(0);

    }
});

    
</script>
