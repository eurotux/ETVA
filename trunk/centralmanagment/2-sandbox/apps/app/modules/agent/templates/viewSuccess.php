<script>

    Ext.namespace('Agent');


    Agent.Services = function(){
        var mainView;
        return{
            init:function(){


                mainView = new Ext.TabPanel({
                    activeTab:0,
                    border:false
                   // title: 'Services',
                    // renderTo:Ext.getdocument.body,
                 //   layout:'fit',

                     ,defaults: {border:false}

                });

                // mainView.add(Service.webmin.init());

              //  Ext.getCmp('<?php // echo $containerId ?>').add(mainView);
              //  Ext.getCmp('<?php // echo $containerId ?>').doLayout();

            }//Fim init
            ,addPanel:function(panel){
                mainView.add(panel);
            },
            show:function(){

                  Ext.getCmp('<?php  echo $containerId ?>').add(mainView);
                  Ext.getCmp('<?php  echo $containerId ?>').doLayout();

            }



        }
    }();


    Service.View.init();

</script>

   <?php
foreach($server_services as $service){
    if($sf_context->getController()->componentExists('service',$service->getName()))
        include_component('service',$service->getName(),array('etva_server'=>$etva_server,'etva_service'=>$service));

}
?>

<script>
    Service.View.show();
</script>