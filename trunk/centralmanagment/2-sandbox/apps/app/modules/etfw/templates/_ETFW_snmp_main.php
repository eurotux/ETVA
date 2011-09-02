<script>

ETFW.SNMP.Main = function(config){

    Ext.apply(this,config);

    var service_id = this.service_id;
    this.etfw_settings = new ETFW.SNMP.Settings({service_id:service_id});          

    ETFW.SNMP.Main.superclass.constructor.call(this, {
        layout:'border',
        border:false,        
        items: [
                {
                    region:'center',
                    layout:'fit',
                    margins: '3 3 3 3',
                    items:[this.etfw_settings]
                }
         ]
         ,listeners:{
            'reload':function(){this.etfw_settings.loadData();}
         }
    });

};

Ext.extend(ETFW.SNMP.Main, Ext.Panel,{});
    
</script>