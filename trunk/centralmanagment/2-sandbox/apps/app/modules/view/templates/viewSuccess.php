<?php
/*
* Use Extjs helper to dynamic create data store and column model javascript
*/
use_helper('Extjs');
/*
* Include nodes grid
* var nodeGrid
*
*/
include_partial('view/welcome');
include_partial('node/grid',array('node_form'=>$node_form,'node_tableMap'=>$node_tableMap));
?>
<script>
    var containerId = <?php echo json_encode($containerId) ?>;    

    var tab_networks_url = <?php echo json_encode(url_for('view/networks')); ?>;
    var tab_networks = new Ext.Panel({
                title:'Networks',
                layout:'fit',
                border:false,
                autoLoad:{url:tab_networks_url,scripts:true,callback:function(){
                        // add component ID
                        tab_networks.add(Ext.getCmp('view-networks'));
                        tab_networks.doLayout();
                        
                }}                
            });

    //add to main panel ID all the stuff (node grid tab and network tab )
    Ext.getCmp('view-center-panel-'+containerId).add(new Ext.TabPanel({
       activeTab:0,     
       items: [{
                title:'Welcome',
                contentEl:'welcome',
                bodyStyle:'padding:5px 5px 0'
               }
               ,Node.Grid.init()
               ,tab_networks
             ]
       })       
    );

</script>