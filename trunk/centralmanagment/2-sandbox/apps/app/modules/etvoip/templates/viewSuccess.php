<?php Etva::loadServicesPartials($etva_server); ?>
<script>
Ext.ns('ETVOIP');
/*
 * .View
 *
 * Build all panels. Entry point for module
 *
 */

ETVOIP.View = function(config) {

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
                    ,'load':{scope:this,fn:function(store){                            
                        this.buildItem(store);
                        Ext.getBody().unmask();
                    }}
                }
            });

    ETVOIP.View.superclass.constructor.call(this, {
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

Ext.extend(ETVOIP.View, Ext.TabPanel,{
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

        var items = [];

        if(services_ids['pbx']){
            var pbx = new ETVOIP.PBX({service:services_ids['pbx']});
            items.push(pbx);
        }        

        this.add(items);
        this.doLayout();
        this.setActiveTab(0);

    }
});


</script>