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

include_partial('node/info');
include_partial('server/grid',array('sfGuardGroup_tableMap'=>$sfGuardGroup_tableMap,'server_tableMap'=>$server_tableMap));
// include_partial('vlan/grid',array('node_id'=>$node_id,'tableMap'=>$vlan_tableMap,'server_form'=>$server_form,'server_tableMap'=>$server_tableMap));
include_partial('node/stats');
// include_partial('node/stats');

?>
<script>
Ext.ns('Node.View');

Node.View.Storage = Ext.extend(Ext.Panel,{
    title: <?php echo json_encode(__('Storage')) ?>,
    layout:'fit',    
    initComponent:function(){

        Node.View.Storage.superclass.initComponent.call(this);

        this.on({
            'activate':function(){
                if(this.items.length>0){
                  for(var i=0,len=this.items.length;i<len;i++){
                      var item = this.items.get(i);
                      item.fireEvent('reload');
                  }
                }
            }
        });




    }
    // panel will be loaded on first request
    ,loadPanel:function(conf){
        
        this.on('render',function(panel){
            // class already exist
            // load new object instance to panel
            if(typeof Node.Storage !='undefined' && typeof Node.Storage.Main !='undefined'){
                                            
                      panel.add(new Node.Storage.Main(conf));                      
            }else{
                // no js class loaded....
                panel.load({
                    url:<?php echo json_encode(url_for('node/storage')); ?>
                    ,scripts:true,scope:this
                    ,callback:function(){
                        this.add(new Node.Storage.Main(conf));
                        this.doLayout();                        
                    }
                });
            }
        });// end render
    }
});


Node.View.Main = function(config) {

    Ext.apply(this,config);    
    
    var node_info = new Node.View.Info({title:<?php echo json_encode(__('Node info')) ?>,node_id:this.node_id});    
    var server_grid = new Server.Grid.init({url: <?php echo json_encode(url_for('server/jsonGrid?nid='))?>+this.node_id,node_id:this.node_id,title: <?php echo json_encode(__('Servers')) ?>});

    /*
     * on event updateNode state fire update node css (update server state on start stop)
     */
    server_grid.on({
        'updateNodeState':{fn:function(node_attrs,data_to_check){this.fireEvent('updateNodeCss',node_attrs,data_to_check);},scope:this}
        ,'reloadTree': { fn: function(attrs){ this.fireEvent('reloadTree',attrs); }, scope:this}
    });


    var tab_storage = this.loadStorage();

    var nodeLoad = new Node.Stats_Load({title: <?php echo json_encode(__('Node Load')) ?>,type:'nodeLoad',node_id:this.node_id});
    
    var node_items = [node_info];

    var record = new Object();
    record.data = new Object();
    record.data['id'] = this.node_id;
    record.data['level'] = 'node';

    var conn = new Ext.data.Connection({});

    conn.request({
        url: <?php echo json_encode(url_for('sfGuardPermission/jsonHasPermission')) ?>,
        scope:this,
        params:record.data,
        success: function(resp,opt) {
            var response = Ext.decode(resp.responseText);
            
            if(response['datacenter']){
                this.add(server_grid);
                this.add(tab_storage)
                this.add(nodeLoad);
            }
        },
        failure: function(resp,opt) {
            var response = Ext.util.JSON.decode(resp.responseText);
            Ext.ux.Logger.error(response['agent'],response['error']);

            Ext.Msg.show({
                buttons: Ext.MessageBox.OK,
                msg: response['info'],
                icon: Ext.MessageBox.ERROR});
        }
    });

    Node.View.Main.superclass.constructor.call(this, {
        activeTab:0,
        items: node_items
    });

    this.on({
            'reload':function(){
                var active = this.getActiveTab();
                active.fireEvent('activate');
            }
    });

};

// define public methods
Ext.extend(Node.View.Main, Ext.TabPanel,{
    loadStorage:function(){
        var tab_storage = new Node.View.Storage();
        tab_storage.loadPanel({node_id:this.node_id, level:'node'});

        return tab_storage;                  
    }
});

</script>
