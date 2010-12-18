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
//include_partial('agent/grid',array('server_id'=>$server_id,'agent_form'=>$agent_form,'agent_tableMap'=>$agent_tableMap));
//include_partial('agent/view');
include_partial('network/grid',array('server_id'=>$server_id,'node_id'=>$node_id,'network_tableMap'=>$network_tableMap));
// include_partial('server/formView',array('server_id'=>$server_id));
include_partial('server/grid',array('server_id'=>$server_id,'node_id'=>$node_id,'sfGuardGroup_tableMap'=>$sfGuardGroup_tableMap,'server_tableMap'=>$server_tableMap));
//include_partial('server/stats',array('server_id'=>$server_id,'rra_stores'=>$rra_stores,'rra_names'=>$rra_names));
include_partial('server/stats',array('containerId'=>$containerId,'node_id'=>$node_id,'server_id'=>$server_id,'networks'=>$networks,'lv'=>$lv));
?>
<script>
    var containerId = <?php echo json_encode($containerId) ?>;

    
    Services = function(){

        return{
            // var networkGrid;
            init:function(){
                Ext.QuickTips.init();

                var servicesPanel = new Ext.Panel({
                        //id:'server-services-panel-'+containerId,
                        title:'Services',
                        layout:'fit',
                        autoLoad:{url:<?php echo json_encode(url_for('service/view?sid='.$server_id.'&containerId='.$containerId)); ?>,scripts:true,callback:function(){
                        // add component ID
                        servicesPanel.add(Ext.getCmp('service-tabs-'+containerId));
                        servicesPanel.doLayout();
                        }},
                        listeners:{
                            afterlayout:{scope:this, single:true, fn:function() {



                                var updater = servicesPanel.getUpdater();
                                updater.on('beforeupdate', function(){
                                    Ext.getBody().mask('Loading...');});

                                updater.on('update', function(){
                                    Ext.getBody().unmask();});


                            }}
                        }
                });
                return servicesPanel;
            }//Fim init


        }
    }();

    // add to main panel
    Ext.getCmp('view-center-panel-'+containerId).add(new Ext.TabPanel({
       activeTab:0,
       items: [Server.Grid.init()
               ,Stats.Server.init()
               ,Network.Grid.init()
               ,Services.init()
             ]
       
       })
    );
    

</script>