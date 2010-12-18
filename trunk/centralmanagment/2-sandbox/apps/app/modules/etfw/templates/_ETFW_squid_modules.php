<script>
    var containerId = <?php echo json_encode($containerId) ?>;
    
        
    Ext.getCmp('etfw-squid-panel-'+containerId).doLayout();

</script>