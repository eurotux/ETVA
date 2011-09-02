<script>
    var containerId = <?php echo json_encode($containerId) ?>;

    // main layout panel
    var etfw_squid_layout = new Ext.Panel({
        id:'etfw-squid-panel-'+containerId,     
        layout:'fit',
        title:'SQUID Server',
        defaults:{border:false},
        autoLoad:{url:<?php echo json_encode(url_for('etfw/view?sid='.$etva_server->getId().'&containerId='.$containerId.'&dispatcher='.$etva_service->getNameTmpl())); ?>,
            scripts:true,
            callback:function(){
                 var squid_modules = new ETFW_Squid.Modules.Main({'sid':<?php echo $etva_server->getId(); ?>
                                                      ,'serviceId':<?php echo $etva_service->getId(); ?>
                                                      ,'containerId':containerId});                 

                 //add to etfw squid panel...
                 Ext.getCmp('etfw-squid-panel-'+containerId).add(squid_modules);
                 Ext.getCmp('etfw-squid-panel-'+containerId).doLayout();

                
            }}
        ,listeners:{
            //if event reloa fired for this panel send event to child items
            reload:function(){                
                this.items.each(function(item){            
                    item.fireEvent('reload');
                });
            },
            afterlayout:{single:true, fn:function() {

                var updater = this.getUpdater();
                updater.on('beforeupdate', function(){
                    Ext.getBody().mask('Loading ETFW squid panel...');
                });

                updater.on('update', function(){
                    Ext.getBody().unmask();
                });
           
            }}
        }


    });

    

    // add to services tab panel... defined in service/view
    Ext.getCmp('service-tabs-'+containerId).add(etfw_squid_layout);

</script>