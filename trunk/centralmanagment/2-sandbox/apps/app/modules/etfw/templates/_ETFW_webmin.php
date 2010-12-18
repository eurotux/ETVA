<?php
include_component('etfw','ETFW_webmin_iframe',array('etva_server'=>$etva_server,'etva_service'=>$etva_service));
?>
<script>
    var containerId = <?php echo json_encode($containerId) ?>;

    // add to services tab panel... defined in service/view
    Ext.getCmp('service-tabs-'+containerId).add(ETFW_Webmin.init());


</script>