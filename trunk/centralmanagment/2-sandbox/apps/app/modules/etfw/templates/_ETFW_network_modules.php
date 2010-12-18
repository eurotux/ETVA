<?php include_partial('ETFW_network_interfaces'); ?>
<?php include_partial('ETFW_network_routing'); ?>
<?php include_partial('ETFW_network_hostaddresses'); ?>
<?php include_partial('ETFW_network_hostdns'); ?>
<script>
    var containerId = <?php echo json_encode($containerId) ?>;
    

    var etfw_network_interfaces = new ETFW_Network.Interfaces.Main(<?php echo $etva_service->getId(); ?>,'etfw-network-interfaces-'+containerId);
    var etfw_network_routing = new ETFW_Network.Routing.Main(<?php echo $etva_service->getId(); ?>,'etfw-network-routing-'+containerId);
    var etfw_network_hostaddresses = new ETFW_Network.HostAddresses.Main(<?php echo $etva_service->getId(); ?>,'etfw-network-hostaddresses-'+containerId);
    var etfw_network_hostdns = new ETFW_Network.HostDns.Main(<?php echo $etva_service->getId(); ?>);

    // add to etfw network panel... defined in etfw/network
    Ext.getCmp('etfw-network-panel-'+containerId).add(etfw_network_interfaces);
    Ext.getCmp('etfw-network-panel-'+containerId).add(etfw_network_routing);
    Ext.getCmp('etfw-network-panel-'+containerId).add(etfw_network_hostaddresses);
    Ext.getCmp('etfw-network-panel-'+containerId).add(etfw_network_hostdns);
        
    Ext.getCmp('etfw-network-panel-'+containerId).doLayout();

</script>