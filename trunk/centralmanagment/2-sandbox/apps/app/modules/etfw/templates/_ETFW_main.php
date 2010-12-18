<script>
    var containerId = <?php echo json_encode($containerId) ?>;


    // main layout panel
    
    
    var etfw_main_layout = new Ext.Panel({
        layout:'fit',
        title:'Main information'
        ,items:[{
                layout:'vbox',
                layoutConfig:{
                    padding:'5',
                    align:'left'
                },
                defaults:{margins:'0 0 15 0'},
                items:[{xtype:'button',text:'Network setup wizard'
                    ,url:<?php echo json_encode(url_for('etfw/ETFW_network_wizard')) ?>
                    ,handler: View.clickHandler
                    //,{xtype:'spacer',flex:1}
                //    ,{xtype:'button',text:'button2'}
                }
                ]
        }]
       // ,autoLoad:{url:<?php //   echo json_encode(url_for('ETFW/view?sid='.$etva_server->getId().'&dispatcher='.$etva_service->getNameTmpl())); ?>,
       //     scripts:true}
    });


    etfw_main_layout.on({
        afterlayout:{single:true, fn:function() {
                var updater = this.getUpdater();
                updater.disableCaching = true;
                updater.on('beforeupdate', function(){                                        
                });

                updater.on('update', function(){
                    alert('update');
                    Ext.getBody().unmask();
                });



            }}
    });

    // add to etva services tab panel... defined in service/view
    Ext.getCmp('service-tabs-'+containerId).add(etfw_main_layout);

</script>
