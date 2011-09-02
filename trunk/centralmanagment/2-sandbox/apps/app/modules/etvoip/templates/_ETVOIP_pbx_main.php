<script>

ETVOIP.PBX.Main = function(config){

    Ext.apply(this,config);

    var service_id = this.service_id;

    var etvoip_extensions = new ETVOIP.PBX.Extensions.Main({service_id:service_id});
    etvoip_extensions.on('onReloadAsterisk', function(){this.reloadAsterisk();},this);
    var etvoip_trunks = new ETVOIP.PBX.Trunks.Main({service_id:service_id});
    etvoip_trunks.on('onReloadAsterisk', function(){this.reloadAsterisk();},this);

    var etvoip_out_routes = new ETVOIP.PBX.Outboundroutes.Main({service_id:service_id});
    etvoip_out_routes.on('onReloadAsterisk', function(){this.reloadAsterisk();},this);

    var etvoip_in_routes = new ETVOIP.PBX.Inboundroutes.Main({service_id:service_id});
    etvoip_in_routes.on('onReloadAsterisk', function(){this.reloadAsterisk();},this);

    var pbx_modulesData = [
        {
            text: <?php echo json_encode(__('Modules')) ?>,
            expanded: true,            
            children:[
                {
                    text:etvoip_extensions.title,
                    item:etvoip_extensions,
                    leaf:true
                }
                ,{
                    text:etvoip_trunks.title,
                    item:etvoip_trunks,
                    leaf:true
                }
                ,{
                    text:etvoip_out_routes.title,
                    item:etvoip_out_routes,
                    leaf:true
                }
                ,{
                    text:etvoip_in_routes.title,
                    item:etvoip_in_routes,
                    leaf:true
                }
           ]
    }];



    var pbx_modulesTree = new Ext.tree.TreePanel({        
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
        ,tbar:[
                {
                    text:'FREEPBX',
                    iconCls: 'icon-world-go',
                    tooltip: <?php echo json_encode(__('Open FREEPBX in new window')) ?>,
                    scope:this,
                    handler:function(){this.loadUnembedded();}
                }
        ]
    });

    pbx_modulesTree.on({
        click:function(n){

            var sn = this.selModel.selNode || {}; // selNode is null on initial selection
            if(n.leaf && n.id != sn.id){  // ignore clicks on folders and currently selected node

                var contentPanel = Ext.getCmp('etvoip-pbx-contentpanel-'+service_id);

                if(!contentPanel.get(n.attributes.item.id)){
                    Ext.getBody().mask('Loading ETVOIP pbx data...');
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
                (modules_children[0]).fireEvent("click",modules_children[0]);
        }}
    });


    var pbxContentPanel = {
        id:'etvoip-pbx-contentpanel-'+service_id,
        layout:'card',border:false,
        defaults:{border:false},
        items: [],
        listeners:{
            notify_reload:function(p,v){

                p.items.each(function(e){
                    e.checkAsteriskReload(v);
                });                         
            }
        }
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
                /*
                 * fired on activate ETVOIP PBX
                 */
                var modulesPanel = this.items.get(0);                
                modulesPanel.fireEvent('reload');
            }
        }
});


};

Ext.extend(ETVOIP.PBX.Main, Ext.Panel,{
    loadIframe:function(url){

        var loadframe = new Ext.ux.ManagedIFrame.Panel(
                {
                    border:false,              
                    defaultSrc :  url,
                    listeners : {
                        documentloaded : function(){
                            View.notify({html:'Document loaded '});
                            // Demo.balloon(null, MIF.title+' reports:','docloaded ');
                        },
                        exception:function(){},
                        domready:function(){},
                        beforedestroy : function(){}
                    }
        });

        var panel = {
            xtype: 'panel',
            layout:'fit',
            frame: true,            
            bodyStyle:'padding:5px',
            border:false,
            items:[loadframe],
            bbar:[
                    '->',
                    {
                        text: 'Reload',
                        tooltip: 'Reload frame',
                        iconCls: 'x-tbar-loading',
                        handler: function(button,event)
                        {
                            //button.addClass('x-item-disabled');
                            loadframe.setSrc('',false,function(){
                                //button.removeClass('x-item-disabled');
                            });
                        }
                    }
                ]
        };


        var win = new Ext.Window({
                title:'FREEPBX',
                layout:'fit'
                ,border:false
                ,maximizable:true
                ,items: panel
                ,listeners:{
                    'close':function(){
                        Ext.EventManager.removeResizeListener(win.resizeFunc,win);
                    }
                }

        });
        
        //on browser resize, resize window
        Ext.EventManager.onWindowResize(win.resizeFunc,win);

        win.resizeFunc();
        win.show();

    }
    ,loadUnembedded:function(){

        var conn = new Ext.data.Connection({
            listeners:{
                scope:this,
                beforerequest:function(){
                    this.getEl().mask(<?php echo json_encode(__('Loading FREEPBX...')) ?>);
                },// on request complete hide message
                requestcomplete:function(){
                    this.getEl().unmask();
                }
                ,requestexception:function(c,r,o){
                    this.getEl().unmask();
                    Ext.Ajax.fireEvent('requestexception',c,r,o);
                }
            }
        });// end conn

        conn.request({
            url:<?php echo json_encode(url_for('etvoip/jsonGetServer'))?>,
            params:{id:this.service_id},
            // everything ok...
            success: function(resp,opt){
                
                var response = Ext.util.JSON.decode(resp.responseText);                
                var server_ip = response['data']['ip'];

                this.loadIframe('http://'+server_ip+'/admin/');
                                                

            },scope:this
        });// END Ajax request
        

    }
    ,reloadAsterisk:function(){

        var conn = new Ext.data.Connection({
            listeners:{
                scope:this,
                beforerequest:function(){
                    this.getEl().mask(<?php echo json_encode(__('Reloading Asterisk configuration...')) ?>);
                },// on request complete hide message
                requestcomplete:function(){
                    this.getEl().unmask();
                }
                ,requestexception:function(c,r,o){
                    this.getEl().unmask();
                    Ext.Ajax.fireEvent('requestexception',c,r,o);
                }
            }
        });// end conn

        conn.request({
            url:<?php echo json_encode(url_for('etvoip/json'))?>,
            params:{id:this.service_id,method:'do_reload'},
            // everything ok...
            success: function(resp,opt){
                var resp = Ext.util.JSON.decode(resp.responseText);                
                var need = resp.response['need_reload'];                
                var pbx_content = Ext.getCmp('etvoip-pbx-contentpanel-'+this.service_id);
                pbx_content.fireEvent('notify_reload',pbx_content,need);

                var msg = <?php echo json_encode(__('Reloaded Asterisk configuration')) ?>;
                View.notify({html:msg});

            },scope:this
        });// END Ajax request
    }

});


</script>