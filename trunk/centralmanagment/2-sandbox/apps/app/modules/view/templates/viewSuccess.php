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
include_partial('node/grid',array('node_tableMap'=>$node_tableMap));
?>
<script>
Ext.ns('View');

View.Networks = Ext.extend(Ext.Panel,{
    title: <?php echo json_encode(__('Networks')) ?>,
    layout:'fit',
    initComponent:function(){


        View.Networks.superclass.initComponent.call(this);

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
    ,loadPanelFromUrl:function(){
        
        this.on('render',function(panel){
            panel.load({
                url:<?php echo json_encode(url_for('view/networks')); ?>
                ,scripts:true,scope:this,
                callback:function(){
                    
                    var viewerSize = this.el.getViewSize();

                    var southHeight = viewerSize.height * 0.40;
                    southHeight = Ext.util.Format.round(southHeight,0);            

                    this.add(new View.Networks.Main({south_height:southHeight, clusterId:this.clusterId}));
                    this.doLayout();
                }
            });
        });// end render
    }
});


View.Main = function(config) {

    Ext.apply(this,config);

    this.cluster_id = this.aaa;

    var node_grid = Node.Grid.init({url:'node/jsonGrid',type:'list',title: <?php echo json_encode(__('Nodes')) ?>, aaa:this.aaa }); //aaa = clusterId
//    console.log(node_grid);

//    node_grid.changeAAA(2);
//    Ext.apply(node_grid, {aaa:2});
    var tab_networks = this.loadNetworks(this.aaa); //aaa = clusterId
    var tab_storage = this.loadStorage(this.aaa);

    var etvamodel = "<?php echo $sf_user->getAttribute('etvamodel'); ?>";

    var p_hidden = (this.aaa == 0) ? true : false;

    if(p_hidden){
        View.Main.superclass.constructor.call(this, {
            activeTab:0,
            items:[{
                    title: <?php echo json_encode(__('Welcome')) ?>,
                    contentEl:'welcome',
                    bodyStyle:'padding:5px 5px 0'
                   }
               ]
        });
    }else{
        var record = new Object();
        record.data = new Object();
        record.data['id'] = this.cluster_id;
        record.data['level'] = 'cluster';

        var conn = new Ext.data.Connection({});

        conn.request({
            url: <?php echo json_encode(url_for('sfGuardPermission/jsonHasPermission')) ?>,
            scope:this,
            params:record.data,
            success: function(resp,opt) {
                var response = Ext.decode(resp.responseText);
                
                if(response['datacenter']){
                    this.add(node_grid);
                    <?php if($sf_user->getAttribute('etvamodel') == 'enterprise'): ?>
                    this.add(tab_storage);
                    <?php endif ?>
                    this.add(tab_networks);
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

        View.Main.superclass.constructor.call(this, {
        activeTab:0,
        items:[{
                title: <?php echo json_encode(__('Welcome')) ?>,
                contentEl:'welcome',
                bodyStyle:'padding:5px 5px 0'
               }
           ]
        });
    }

  
    this.on({
        'reload':function(){
            var active = this.getActiveTab();
            active.fireEvent('activate');
        }
    });

};


Ext.extend(View.Main, Ext.TabPanel,{
    loadNetworks:function(cId){
        var tab_networks = new View.Networks({clusterId:cId});
        tab_networks.loadPanelFromUrl();
        
        return tab_networks;
    },
    loadStorage:function(cId){
        var tab_storage = new View.Tab.Storage();
        tab_storage.loadPanel({level:'cluster', cluster_id:cId});     //change to cluster_id
        return tab_storage;
    }

});




///////////////
//  STORAGE
///////////////


Ext.ns('View.Tab');

View.Tab.Storage = Ext.extend(Ext.Panel,{
    title: <?php echo json_encode(__('Storage')) ?>,
    layout:'fit',
    initComponent:function(){

        View.Tab.Storage.superclass.initComponent.call(this);

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

</script>
