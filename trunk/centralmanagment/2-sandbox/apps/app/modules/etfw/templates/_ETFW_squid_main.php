<script>

ETFW.Squid.Main = function(config){

    Ext.apply(this,config);

    var service_id = this.service_id;        

    var etfw_squid_network = new ETFW.Squid.Network.Main(service_id);
    var etfw_squid_authentication = new ETFW.Squid.Authentication.Main(service_id);
    var etfw_squid_acl = new ETFW.Squid.Acl.Main(service_id);
    var etfw_squid_other = new ETFW.Squid.Othercaches.Main(service_id);


    var squid_modulesData = [
        {
            text:'Modules',
            expanded: true,
            children:[
                {
                    text:etfw_squid_network.title,
                    item:etfw_squid_network,
                    leaf:true
                },
                {
                    text:etfw_squid_acl.title,
                    item:etfw_squid_acl,
                    leaf:true
                },
                {
                    text:etfw_squid_authentication.title,
                    item:etfw_squid_authentication,
                    leaf:true
                },
                {
                    text:etfw_squid_other.title,
                    item:etfw_squid_other,
                    leaf:true
                }]
    }];

    

    var squid_modulesTree = new Ext.tree.TreePanel({        
        region:'west',
        title:'Modules',
        split:true,
        tbar: [
            {text:'SQUID Wizard'
             ,iconCls:'wizard'
             ,url:<?php echo json_encode(url_for('etfw/ETFW_wizard?tpl=squid&sid='))?>+this.server_id
             ,handler: View.clickHandler
            }
        ],
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
            children: squid_modulesData
        })
    });

    squid_modulesTree.on({
        reload:function(){
            var contentPanel = Ext.getCmp('etfw-squid-contentpanel-'+service_id);
            var modules_node = this.getRootNode().childNodes[0]; // get 'Modules' node
            var modules_children = modules_node.childNodes;

            var active_item = contentPanel.layout.activeItem;
            if(active_item) active_item.reload();

        },
        click:function(n){

            var sn = this.selModel.selNode || {}; // selNode is null on initial selection
            if(n.leaf && n.id != sn.id){  // ignore clicks on folders and currently selected node
                
                var contentPanel = Ext.getCmp('etfw-squid-contentpanel-'+service_id);

                if(!contentPanel.get(n.attributes.item.id)){                    
                    Ext.getBody().mask('Loading ETFW squid data...');
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


    var squidContentPanel = {
        id:'etfw-squid-contentpanel-'+service_id,
        layout:'card',border:false,
        defaults:{border:false},
        items: []
    };


    ETFW.Squid.Main.superclass.constructor.call(this, {
        layout:'border',
        border:false,
        defaults: {
            // collapsible: true,
            split: true,
            //border:false,
            bodyStyle: 'padding:0px'
        },
        items: [

                 squid_modulesTree,
                 {region:'center',
                    layout:'fit',
                    margins: '3 3 3 3',items:[squidContentPanel]}                
         ]
         ,listeners:{
            'reload':function(){
                var modulesPanel = this.items.get(0);
                modulesPanel.fireEvent('reload');
            }
        }
});


};

Ext.extend(ETFW.Squid.Main, Ext.Panel,{});

    
</script>