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

                    this.add(new View.Networks.Main({south_height:southHeight}));
                    this.doLayout();
                }
            });
        });// end render
    }
});


View.Main = function(config) {

    Ext.apply(this,config);

    var node_grid = Node.Grid.init({url:'node/jsonGrid',type:'list',title: <?php echo json_encode(__('Nodes')) ?>});
    var tab_networks = this.loadNetworks();
    
    View.Main.superclass.constructor.call(this, {        
        activeTab:0,        
        items:[{
                title: <?php echo json_encode(__('Welcome')) ?>,
                contentEl:'welcome',
                bodyStyle:'padding:5px 5px 0'
               }
               ,node_grid
               ,tab_networks]
    });

    this.on({
        'reload':function(){
            var active = this.getActiveTab();
            active.fireEvent('activate');
        }
    });

};


Ext.extend(View.Main, Ext.TabPanel,{
    loadNetworks:function(){
    
        var tab_networks = new View.Networks();
        tab_networks.loadPanelFromUrl();
        
        return tab_networks;
    }

});

</script>