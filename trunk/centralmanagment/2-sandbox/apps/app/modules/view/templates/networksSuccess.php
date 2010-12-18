<?php
/*
 * Use Extjs helper to dynamic create data store and column model javascript
 */
use_helper('Extjs');

include_partial('network/interfacesGrid',array('tableMap'=>$network_tableMap));
include_partial('vlan/grid',array('tableMap'=>$vlan_tableMap));
?>
<script>

// main column storage layout
new Ext.Panel({
    id:'view-networks',
                 layout:'border',
                 defaults: {
    collapsible: true,
    split: true
    //,
    //bodyStyle: 'padding:15px'
},
border:false,
items: [
    {
    border:false,
    id:'interfaces-grid',
    title: 'Interfaces - All',
    region: 'south',
    height: 250,
    minSize: 105,
    maxSize: 350,
    layout:'fit',
    items:[
        Network.InterfacesGrid.init()
    ],
    cmargins: '5 0 0 0'

},
{
    border:false,
    title: 'Manage Vlans',
    collapsible: false,
    region:'center',
    layout:'fit',
 //   items:[gridList],
    items:[Vlan.Grid.init()],

    // items:[doVlanPanel],
    margins: '5 0 0 0'
}]

//                 ,layoutConfig: {
//                                    fitHeight: true,
//                                    margin: 5,
//                                    split: true
//                                }
//                 ,items:[Vlan.Grid.init()]
//                 ,tbar:[{text:'Manage NIC pool',
//                url:'mac/createwin',
//                handler: View.clickHandler
//               }]


 });

</script>