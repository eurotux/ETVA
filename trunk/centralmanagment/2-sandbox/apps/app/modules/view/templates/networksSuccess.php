<?php
/*
 * Use Extjs helper to dynamic create data store and column model javascript
 */
use_helper('Extjs');

include_partial('network/interfacesGrid',array('tableMap'=>$network_tableMap));
include_partial('vlan/grid',array('tableMap'=>$vlan_tableMap));
?>
<script>


View.Networks.Main = function(config) {

    Ext.apply(this,config);

    var interfaces_grid = new Network.InterfacesGrid();
    var vlan_grid = new Vlan.Grid();

    vlan_grid.on({'rowmousedown':function(grid,rowIndex,e){
                            var rec = grid.store.getAt(rowIndex);
                            if(grid.getSelectionModel().isSelected(rowIndex)){
                                grid.getSelectionModel().clearSelections();
                                interfaces_grid.load(null);
                                return false;
                            }else{

                                interfaces_grid.load(rec);
                                return true;
                            }},
                 'reloadInterfaces':function(){                     
                     vlan_grid.getSelectionModel().clearSelections();                     
                     interfaces_grid.load(null);                     
                 }
    });

        
     
    View.Networks.Main.superclass.constructor.call(this, {
        // passed arguments:
        //     this.south_height
        layout:'border',        
             defaults: {                 
                split: true},
        border:false,
        items: [
                {
                    border:false,                    
                    title: <?php echo json_encode(__('List all network interfaces')) ?>,
                    region: 'south',                    
                    //height:100,
                    height:this.south_height,                 
                    collapsible: true,                                                          
                    //minSize: 105,
                   // maxSize: 350,
                    layout:'fit',
                    items:interfaces_grid,
                    cmargins: '5 0 0 0'
                    ,listeners:{
                        'reload': function(){this.items.get(0).reload();}
                    }
                }
                ,{
                    border:false,
                    title: <?php echo json_encode(__('Manage networks')) ?>,
                    collapsible: false,
                    region:'center',
                    layout:'fit',                    
                    items:vlan_grid,
                    margins: '5 0 0 0'
                    ,tools:[{id:'help', qtip: __('Help'),handler:function(){View.showHelp({anchorid:'help-vlan',autoLoad:{ params:'mod=vlan'},title: <?php echo json_encode(__('Manage networks Help')) ?>});}}]
                    ,listeners:{
                        'reload': function(){this.items.get(0).reload();}
                    }
                }]
        ,listeners:{
                'reload':function(){
                    
                    for(var i=0,len=this.items.length;i<len;i++){
                        var item = this.items.get(i);
                        item.fireEvent('reload');
                    }
                }
        }
    });
};

// define public methods
Ext.extend(View.Networks.Main, Ext.Panel, {});


</script>