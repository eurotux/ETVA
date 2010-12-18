<script>
    var containerId = <?php echo json_encode($containerId) ?>;    

    // main layout panel
    var etfw_network_layout = new Ext.Panel({
        id:'etfw-network-panel-'+containerId,
        layout:'accordionpatch',
        layoutConfig: {
            autoWidth: false
        },
        title:'Network',
        autoLoad:{url:<?php echo json_encode(url_for('etfw/view?sid='.$etva_server->getId().'&containerId='.$containerId.'&dispatcher='.$etva_service->getNameTmpl())); ?>,
            scripts:true,callback:function(){
                        // add component ID                        
                }}
        ,listeners:{
            afterlayout:{single:true, fn:function() {

                var updater = this.getUpdater();
                updater.on('beforeupdate', function(){
                    Ext.getBody().mask('Loading ETFW network panel...');
                });

                updater.on('update', function(){
                    Ext.getBody().unmask();
                });

            }}
        }
       

    });



    
    // add to services tab panel... defined in service/view
    Ext.getCmp('service-tabs-'+containerId).add(etfw_network_layout);

</script>