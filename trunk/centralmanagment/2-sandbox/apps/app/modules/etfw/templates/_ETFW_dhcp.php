<script>
    var containerId = <?php echo json_encode($containerId) ?>;

    // main layout panel
    var etfw_dhcp_layout = new Ext.Panel({
        id:'etfw-dhcp-panel-'+containerId,
        layout:'fit',
        title:'DHCP Server'
        ,autoLoad:{url:<?php   echo json_encode(url_for('etfw/view?sid='.$etva_server->getId().'&containerId='.$containerId.'&dispatcher='.$etva_service->getNameTmpl())); ?>,
            scripts:true,callback:function(){

            }}
        ,listeners:{
            afterlayout:{single:true, fn:function() {

                var updater = this.getUpdater();
                updater.disableCaching = true;
                updater.on('beforeupdate', function(){
                    Ext.getBody().mask('Loading ETFW dhcp panel...');
                });

                updater.on('update', function(){
                    Ext.getBody().unmask();
                });



            }}

        }
    });

   
    // add to services tab panel... defined in service/view
    Ext.getCmp('service-tabs-'+containerId).add(etfw_dhcp_layout);

</script>