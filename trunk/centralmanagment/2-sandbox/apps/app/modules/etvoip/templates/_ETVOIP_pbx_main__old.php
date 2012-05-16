<script>

ETVOIP.PBX.Main = function(config){

    Ext.apply(this,config);

    var service_id = this.service_id;

   // var etvoip_extensions = new ETVOIP.PBX.Extensions.Main();

    var pbx_modulesData = [
        {
            text:'Modules',
            expanded: true,
            children:[
//                {
//                    text:etvoip_extensions.title,
//                    item:etvoip_extensions,
//                    leaf:true
//                }
           ]
    }];



    var pbx_modulesTree = new Ext.tree.TreePanel({
        //id:'etfw-dhcp-modulesTree-'+service_id,
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
            children: pbx_modulesData
        })
    });

    pbx_modulesTree.on({
//        reload:function(){
//            var contentPanel = Ext.getCmp('etfw-dhcp-contentpanel-'+service_id);
//            var modules_node = this.getRootNode().childNodes[0]; // get 'Modules' node
//            var modules_children = modules_node.childNodes;
//
//            for(var i=0,len = modules_children.length;i<len;i++)
//            {
//
//                var child_node = modules_children[i];
//                if(contentPanel.get(child_node.attributes.item.id))
//                    child_node.attributes.item.reload();
//            }
//
//        },
        click:function(n){

            var sn = this.selModel.selNode || {}; // selNode is null on initial selection
            if(n.leaf && n.id != sn.id){  // ignore clicks on folders and currently selected node

                var contentPanel = Ext.getCmp('etvoip-pbx-contentpanel-'+service_id);

                if(!contentPanel.get(n.attributes.item.id)){
                    Ext.getBody().mask('Loading ETFW dhcp data...');
                    (function(){
                        contentPanel.add(n.attributes.item);
                        contentPanel.layout.setActiveItem(n.attributes.item.id);
                        Ext.getBody().unmask();
                    }).defer(10);

                }else contentPanel.layout.setActiveItem(n.attributes.item.id);
            }
        },
        load:{single:true,delay:10,fn:function(){                
                var modules_node = this.getRootNode().childNodes[0]; // get 'Modules' node
                var modules_children = modules_node.childNodes;
//                (modules_children[0]).fireEvent("click",modules_children[0]);
        }}
    });


    var pbxContentPanel = {
        id:'etvoip-pbx-contentpanel-'+service_id,
        layout:'card',border:false,
        defaults:{border:false},
        items: []
    };


    ETVOIP.PBX.Main.superclass.constructor.call(this, {
        layout:'border',
        border:false,
        defaults: {
            // collapsible: true,
            split: true,
            //border:false,
            bodyStyle: 'padding:0px'
        },
        items: [

                 pbx_modulesTree,
                 {region:'center',
                    layout:'fit',
                    margins: '3 3 3 0',items:[pbxContentPanel]}
         ]
         ,listeners:{
            'reload':function(){
                //alert('ETVOIP.PBX.Main reload');
                var modulesPanel = this.items.get(0);
                modulesPanel.fireEvent('reload');
            }
        }
});


};

Ext.extend(ETVOIP.PBX.Main, Ext.Panel,{});

    
</script>
