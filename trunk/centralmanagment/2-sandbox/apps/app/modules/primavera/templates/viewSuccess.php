<?php
//load partial files code... based on DB service module name.
//in this case it should be in service name=>main and will load js code from _Primavera_main.php
Etva::loadServicesPartials($etva_server);
?>


<script>
Ext.ns('Primavera');



/*
* .View
*
* Build all panels. Entry point for module
*
*/

Primavera.View = function(config) {

    Ext.apply(this,config);

    //load services ID from BD
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

    Primavera.View.superclass.constructor.call(this, {
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

Ext.extend(Primavera.View, Ext.TabPanel,{
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

        //load object from _Primavera_main.php
        var main = new Primavera.Main({layout:'fit',server_id:server_id,service:services_ids['main']});        

        var items = [main];

        this.add(items);
        this.doLayout();
        this.setActiveTab(0);

    }
});

    
</script>
