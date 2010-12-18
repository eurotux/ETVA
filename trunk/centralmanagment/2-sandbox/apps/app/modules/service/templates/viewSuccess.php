<script>
    var containerId = <?php echo json_encode($containerId) ?>;
    //panel is added in server/view
    new Ext.TabPanel({
                    id:'service-tabs-'+containerId,
                    activeTab:0,                    
                    border:false,
                    defaults: {border:false}                                        
                });

    // initial check server for connectivity
    var mgr = new Ext.Updater("notificationDiv");
    
    mgr.update({
        url: <?php echo json_encode(url_for('server/jsonCheckState?id='.$etva_server->getId()))?>,
        scripts:true
    });
    
    mgr.on('failure',function(el,resp){
        var response = Ext.util.JSON.decode(resp.responseText);
        notify({html:response['error']});

    });

    mgr.on('update',function(el,resp){
        Ext.ux.Logger.info('System check');

    });



                 
</script>
  
<?php

$main_tmpl = $etva_server->getAgentTmpl().'_main';
$module_agent = strtolower($etva_server->getAgentTmpl());

$main_tmpl_path = $sf_context->getConfiguration()->getTemplateDir($module_agent, '_'.$main_tmpl.'.php');
if($main_tmpl_path)
        include_partial($module_agent.'/'.$main_tmpl,array('containerId'=>$containerId,'etva_server'=>$etva_server,'etva_services'=>$server_services));

foreach($server_services as $service){
    $tmpl = $etva_server->getAgentTmpl().'_'.$service->getNameTmpl();
    $service_path = $sf_context->getConfiguration()->getTemplateDir($module_agent, '_'.$tmpl.'.php');
	if($service_path)
        include_partial($module_agent.'/'.$tmpl,array('containerId'=>$containerId,'etva_server'=>$etva_server,'etva_service'=>$service));
}

?>