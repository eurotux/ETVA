<?php
/*
 * Use Extjs helper to dynamic create data store and column model javascript
 */
use_helper('Extjs');
/*
 * Include network grid
 * var networkGrid
 *
 */

include_partial('node/grid',array('node_id'=>$node_id,'node_tableMap'=>$node_tableMap));
include_partial('server/grid',array('node_id'=>$node_id,'sfGuardGroup_tableMap'=>$sfGuardGroup_tableMap,'server_form'=>$server_form,'server_tableMap'=>$server_tableMap));
// include_partial('vlan/grid',array('node_id'=>$node_id,'tableMap'=>$vlan_tableMap,'server_form'=>$server_form,'server_tableMap'=>$server_tableMap));

?>
<script>
    var containerId = <?php echo json_encode($containerId) ?>;
    
    var tab_storage = new Ext.Panel({id:'storage-tab',
            title:'Storage',
            layout:'fit',
            autoLoad:{url:<?php echo json_encode(url_for('node/storage?id='.$node_id)); ?>,
                      scripts:true,callback:function(){
                        tab_storage.add(Ext.getCmp('node-storage'));
                        tab_storage.doLayout();
                }} 
            });



    Ext.getCmp('view-center-panel-'+containerId).add(new Ext.TabPanel({
       activeTab:0,
       items: [Node.Grid.init()
               ,Server.Grid.init()
               ,tab_storage
             ]

       })
    );

 
</script>