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

//include_partial('network/grid',array('network_tableMap'=>$network_tableMap)); //not used...remove it?
// include_partial('server/formView',array('server_id'=>$server_id));
include_partial('server/info');
include_partial('server/guestagent');
//include_partial('server/ploneservice');
//include_partial('server/stats',array('server_id'=>$server_id,'rra_stores'=>$rra_stores,'rra_names'=>$rra_names));
include_partial('server/stats');
include_partial('server/services');

?>
<script>
Ext.ns('Server.View');

Server.View.Main = function(config) {

    Ext.apply(this,config);

    var items = [];

    var server_info = new Server.View.Info({title:<?php echo json_encode(__('Server info')) ?>,server_id:this.server['id']});
   
    server_info.on('afterlayout',function(){
        server_info.loadRecord({id:this.server['id']});
    },this,{single:true});

    server_info.on({
        'updateNodeState':{fn:function(node_attrs,data){this.fireEvent('updateNodeCss',node_attrs,data);},scope:this}
        ,'reloadTree': { fn: function(attrs){ this.fireEvent('reloadTree',attrs); }, scope:this}
    });

    items.push(server_info);

    var ga_info_obj = Ext.decode(this.server['data']['ga_info']);
    if(ga_info_obj && this.server['data']['ga_state'] && (this.server['data']['ga_state'] != <?php echo json_encode(__(EtvaServerPeer::_GA_UNINSTALLED_)) ?>) && (this.server['data']['ga_state'] != <?php echo json_encode(__(EtvaServerPeer::_GA_NOSTATE_)) ?>) ){

        // guest agent tab
        var guestagent = new Server.GuestAgent.Info({title:<?php echo json_encode(__('Guest agent')) ?>,server_id:this.server['id']});
        guestagent.on('afterlayout',function(){
            guestagent.loadRecord({id:this.server['id']});
        },this,{single:true});
        guestagent.on({
            'updateNodeState':{fn:function(node_attrs,data){this.fireEvent('updateNodeCss',node_attrs,data);},scope:this}
            ,'reloadTree': { fn: function(attrs){ this.fireEvent('reloadTree',attrs); }, scope:this}
        });
    
        items.push(guestagent);
    
    }

//    if(this.server['data']['plone']){
//        // plone service tab
//        var ploneservice = new Server.PloneService.Info({title:<?php echo json_encode(__('ETASP')) ?>,server_id:this.server['id']});
//        ploneservice.on('afterlayout',function(){
//            ploneservice.loadRecord({id:this.server['id']});
//        },this,{single:true});
//        ploneservice.on({
//            'updateNodeState':{fn:function(node_attrs,data){this.fireEvent('updateNodeCss',node_attrs,data);},scope:this}
//            ,'reloadTree': { fn: function(attrs){ this.fireEvent('reloadTree',attrs); }, scope:this}
//        });
//    
//        items.push(ploneservice);
//    }

    //not used. remove it?
    //var network_grid = new Network.Grid.init({server_id:this.server['id']});
    //items.push(network_grid);

    if( !this.server.data.unassigned ){ // show stats only if server is assigned to node
        var server_stats = new Server.Stats({node_id:this.node_id,server_id:this.server['id']});
        items.push(server_stats);
    }

    if(!Ext.isEmpty(this.server['agent_tmpl']))
    {
        var services_disabled = this.server['state'] ? false : true;
        var services_tabTip = this.server['state'] ? '' : 'Disabled.... Management agent should be running';
        //var server_services = new Server.View.Services({server_id:this.server['id'],disable_panels:services_disabled,tabTip:services_tabTip});
        var server_services = new Server.View.Services({server:this.server,disable_panels:services_disabled,tabTip:services_tabTip});
        items.push(server_services);
    }

    Server.View.Main.superclass.constructor.call(this, {
        activeTab:0,
        items: [items]
    });


    this.on({
            'reload':function(){
                var active = this.getActiveTab();
                active.fireEvent('refresh');
            }
    });


}


// define public methods
Ext.extend(Server.View.Main, Ext.TabPanel,{});

</script>
