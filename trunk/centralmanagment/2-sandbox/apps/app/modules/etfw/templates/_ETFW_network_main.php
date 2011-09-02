<script>

ETFW.Network.Main = function(config){

    Ext.apply(this,config);

    var service_id = this.service_id;

    var etfw_network_interfaces = new ETFW.Network.Interfaces.Main(service_id);
    var etfw_network_routing = new ETFW.Network.Routing.Main(service_id);
    var etfw_network_hostaddresses = new ETFW.Network.HostAddresses.Main(service_id);
    var etfw_network_hostdns = new ETFW.Network.HostDns.Main(service_id);

    var network_modulesData = [
        {
            text:'Modules',
            expanded: true,
            children:[
                {
                    text:etfw_network_interfaces.title,
                    item:etfw_network_interfaces,
                    leaf:true
                },
                {
                    text:etfw_network_routing.title,
                    item:etfw_network_routing,
                    leaf:true
                },
                {
                    text:etfw_network_hostaddresses.title,
                    item:etfw_network_hostaddresses,
                    leaf:true
                },
                {                    
                    text:etfw_network_hostdns.title,
                    item:etfw_network_hostdns,
                    leaf:true
                }
           ]
    }];

    var network_modulesTree = new Ext.tree.TreePanel({
        region:'west',
        title:'Modules',
        split:true,        
        useSplitTips: true,
        width: 200,
        margins: '3 0 3 3',
        cmargins: '3 3 3 3',
        minSize: 155,
        maxSize: 400,
        collapsible: true,
        autoScroll: true,
        rootVisible: false,
        lines: false,
        singleExpand: true,
        useArrows: true
        ,root: new Ext.tree.AsyncTreeNode({
            draggable:false,
            children: network_modulesData
        })
    });

    network_modulesTree.on({
        reload:function(){
            var contentPanel = Ext.getCmp('etfw-network-contentpanel-'+service_id);
            var modules_node = this.getRootNode().childNodes[0]; // get 'Modules' node
            var modules_children = modules_node.childNodes;

            var active_item = contentPanel.layout.activeItem;
            if(active_item) active_item.reload();

        },
        click:function(n){

            var sn = this.selModel.selNode || {}; // selNode is null on initial selection
            if(n.leaf && n.id != sn.id){  // ignore clicks on folders and currently selected node

                var contentPanel = Ext.getCmp('etfw-network-contentpanel-'+service_id);

                if(!contentPanel.get(n.attributes.item.id)){
                    
                    Ext.getBody().mask('Loading ETFW network data...');
                    (function(){
                        contentPanel.add(n.attributes.item);
                        contentPanel.layout.setActiveItem(n.attributes.item.id);
                        Ext.getBody().unmask();
                    }).defer(100);

                }else contentPanel.layout.setActiveItem(n.attributes.item.id);
            }
        },
        load:{single:true,delay:100,fn:function(){
            var modules_node = this.getRootNode().childNodes[0]; // get 'Modules' node
            var modules_children = modules_node.childNodes;
            (modules_children[0]).fireEvent("click",modules_children[0]);
        }}
    });


    var networkContentPanel = {
        id:'etfw-network-contentpanel-'+service_id,
        layout:'card',border:false,
        defaults:{border:false},
        items:[]        
    };


    ETFW.Network.Main.superclass.constructor.call(this, {
        layout:'border',
        border:false,
        defaults: {
            // collapsible: true,
            split: true,
            //border:false,
            bodyStyle: 'padding:0px'
        },
        items: [

                 network_modulesTree,
                 {region:'center',
                    layout:'fit',
                    margins: '3 3 3 3',items:[networkContentPanel]}
                
         ]
         ,listeners:{
            'reload':function(){
                var modulesPanel = this.items.get(0);
                modulesPanel.fireEvent('reload');
            }
        }
});


};

Ext.extend(ETFW.Network.Main, Ext.Panel,{});

    
</script>