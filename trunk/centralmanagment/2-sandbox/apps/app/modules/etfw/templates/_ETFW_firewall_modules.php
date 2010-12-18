<?php
// used to build initial layout....get data from agent and build default layout
include_component('etfw','ETFW_firewall_rules',array('containerId'=>$containerId,'etva_server'=>$etva_server,'etva_service'=>$etva_service));
?>
<script>
    var containerId = <?php echo json_encode($containerId) ?>;

    var etfw_firewall = new ETFW_Firewall.Main(<?php echo $etva_service->getId(); ?>);
    // add to etfw firewall panel... defined in etfw/firewall
    Ext.getCmp('etfw-firewall-panel-'+containerId).add(etfw_firewall);

    Ext.getCmp('etfw-firewall-panel-'+containerId).doLayout();
        
</script>