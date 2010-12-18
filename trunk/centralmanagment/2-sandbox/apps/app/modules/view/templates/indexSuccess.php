<?php
use_stylesheet('main.css');

use_javascript('Ext.ux.ComboBox.js'); //extended combo with reload button

use_javascript('MessageWindow.js'); //plugin for MessageWindows

use_javascript('GridPageSizer.js'); //plugin for Grid Pager


use_javascript('Ext.ux.menu.js'); // contains menu plugin for grid filter plugin
use_javascript('Ext.ux.grid.js'); // contains plugin for remeber selection in grids reload and RowEditor
use_javascript('Ext.ux.grid.filter.js'); // contains plugin for filter plugin. numeric and string filters

use_javascript('ColumnNodeUI.js'); //plugin for Ext.tree.ColumnTree
use_javascript('ColumnLayout.js'); //plugin for panel ColumnLayout


use_javascript('MultiSelect.js'); //plugin for multiselect & itemselector

use_javascript('Ext.ux.dd.GridReorderDropTarget.js'); //plugin for grid reorder DD

use_javascript('miframe-debug.js');

use_javascript('Main.js'); //general javscript functions and patches

$sfExtjs3Plugin = new sfExtjs3Plugin(array('theme'=>'blue')
    //,array('js' => sfConfig::get('sf_extjs2_js_dir').'ext-all.js')
   //'css' => '/css/symfony-extjs.css'
   );
$sfExtjs3Plugin->load();
$sfExtjs3Plugin->begin();
$sfExtjs3Plugin->end();

/*
 * Include form and soap window info
 */
include_partial('node/NodeWindowSoap',array());
include_partial('node/NodeWindowForm',array('node_form'=>$node_form));



?>

<style type="text/css">
        #out {
            padding: 5px;
            overflow:auto;
            border-width:0;
        }
        #out b {
            color:#555;
        }
        #out xmp {
            margin: 5px;
        }
        #out p {
            margin:0;
        }
}
    </style>

<script type='text/javascript'>
 
    // used to display popup info
    function notify(params,updater) {        

        var notificationWindow = new Ext.ux.window.MessageWindow({
            title:'System info'
            // ,items:[menuScreenPanel]
            ,origin:{offY:-5,offX:-5}
            ,autoHeight:true
            ,iconCls: 'icon-info'
            ,help:false
            // ,pinState: 'pin'//render pinned
            ,hideFx:{delay:2000, mode:'standard'}
            ,listeners:{
                render:function(){
                    // Ext.ux.Sound.play('generic.wav');
                }             

            }
        });
        
        Ext.apply(notificationWindow, params);

        if(updater){
                notificationWindow.on('hide',function(){updater.stopAutoRefresh();})
        }
     
        notificationWindow.show(Ext.getDoc());
    }




    // initial check nodes for connectivity
    function checkState(){
        var mgr = new Ext.Updater("notificationDiv");

        <?php foreach($node_list as $node):?>
                mgr.update({url: <?php echo json_encode(url_for('node/jsonCheckState?id='.$node->getId()))?>,scripts:true});
        <?php endforeach; ?>

    }


    function monitorAlive() {         
         var mgr = new Ext.Updater("notificationDiv");
         notify({html:'<span>Keepalive is now ON</span>',pinState: 'pin'},mgr);

        <?php  foreach($node_list as $node):?>
                mgr.update({url: <?php echo json_encode(url_for('node/jsonCheckState?id='.$node->getId()))?>,scripts:true});
                mgr.startAutoRefresh(10, <?php echo json_encode(url_for('node/jsonCheckState?id='.$node->getId()))?>);
        <?php  endforeach; ?>

    }


    //function loadPage(url, proxy, target){
    //			Ext.get(proxy).load(url, "", function (oElement, bSuccess, oConn, target){
    //				if (bSuccess)
    //				{
    //					var html = oElement.dom.innerHTML;
    //					Ext.get('dynPageContainer').update(html, true, function (){
    //						alert("Dynamic loading completed");
    //					});
    //				}
    //				else
    //				{
    //					alert("Failed to load Page. Please check the URL or try again later.");
    //				}
    //			}.createDelegate(null, [target], true));
    //		}

    function loadPage(url, target){
        Ext.lib.Ajax.request('GET',url,
        {success:function(response)
            {
                Ext.get('dynPageContainer').update(response.responseText,true);
            },
            failure:function(response){alert('Please log in');}
        });
    }

    // sfExtjs2Helper: v0.60
    Ext.BLANK_IMAGE_URL = '/sfExtjs2Plugin/extjs/resources/images/default/s.gif';
    Ext.state.Manager.setProvider(new Ext.state.CookieProvider());

    Ext.namespace('View');
    View = function(){

        return{

            init:function(){
                Ext.QuickTips.init();

                /*
                 *
                 * TOP BAR
                 *
                 */

                buttonHi =  {xtype: 'tbtext',text: 'Hi, '+<?php echo json_encode($sf_user->getUsername()) ?>};
               

                buttonUser = {xtype: 'button',text: 'Users',url:'sfGuardUser/viewer',scope:this,
                    handler: this.clickHandler};
                buttonLogout = new Ext.Action({
                    text: 'Logout',
                    handler: function(){
                        window.location.href=<?php  echo json_encode(url_for('@signout',true)); ?>;}});

                userMenu = new Ext.menu.Menu({
                    id: 'userMenu', // the menu's id we use later to assign as submenu
                    items: [{
                            text: 'Groups',
                            url:'sfGuardGroup/view',
                            scope:this,
                            handler: this.clickHandler
                            //handler: loadPage.createCallback("sfGuardGroup/index", "userMenu")
                        },buttonUser,buttonLogout
                    ]});

                toolsMenu = new Ext.menu.Menu({
                    id: 'toolsMenu', // the menu's id we use later to assign as submenu
                    items: [{

                            text: 'Monitor keepalive',
                           // url:'sfGuardGroup/view',
                            scope:this,
                            handler: function(){monitorAlive();}
                        }
                    ]});

                admintoolsMenu = new Ext.Action({text: 'Tools',
                    menu: 'toolsMenu' // assign the colorMenu object by id
                });

                adminMenu = new Ext.Action({text: 'User Administration',
                    menu: 'userMenu' // assign the colorMenu object by id
                });

                var topBar = new Ext.Toolbar({
                    renderTo:'topBar',
                    buttons: [buttonUser,adminMenu,
                        admintoolsMenu,//one to N left buttons
                        new Ext.Toolbar.Fill(),
                        buttonHi,
                        buttonLogout //one to N right buttons
                    ]
                });
                //      var topBar = new Ext.Toolbar('topBar');
                // add your left aligned buttons here
                //    topBar.addButton(buttonUser);
                //topBar.add(menuT);
                //     topBar.add(adminMenu);
                // add greedy spacer e.i. subsequent buttons will be aligned right
                //    Ext.fly(topBar.addSpacer());
                //          Ext.fly(topBar.addSpacer().getEl().parentNode).setStyle('width', '100%');
                // add your right aligned buttons here
                //    topBar.addText("Hi,"+<?php // echo json_encode($sf_user->getUsername()) ?>);
                //     topBar.add(buttonHi);
                //   topBar.add(buttonLogout);



                /*
                 *
                 * Node Panel (left side)
                 *
                 *
                 */
                NodePanel = function() {

                    NodePanel.superclass.constructor.call(this, {                        
                        region:'west',
                        title:'Nodes',
                        split:true,
                        useSplitTips: true,
                        width: 225,
                        margins: '3 0 3 3',
                        cmargins: '3 3 3 3',
                        minSize: 175,
                        maxSize: 400,
                        collapsible: true,
                        rootVisible:true,
                        animate:false,
                        enableDD:false,
                        lines:false,
                        autoScroll:true,
                        loader: new Ext.tree.TreeLoader({
                            clearOnLoad:true,
                            dataUrl: <?php echo json_encode(url_for('server/jsonTree',false)); ?>,
                            listeners:{
                                beforeload:function(){
                             
                                     checkState();
                                }
                            }
                        }),
                        root: new Ext.tree.AsyncTreeNode({
                            text: 'Main',
                            url: <?php echo json_encode(url_for('view/view',true))?>,
                            draggable: false,
                            expanded: true,
                            singleClickExpand:true,
                            selected:true,
                            id: '0'                  // this IS the id of the startnode
                        }),
                        collapseFirst:false,
                        tools:[{
                                id:'refresh',
                                on:{
                                    click: function(){this.root.reload();}
                                    ,scope:this
                                }
                            }],
                        /*
                         * nodes top toolbar
                         */
                        tbar: [{
                                text:'Add Node',
                                iconCls: 'icon-add',
                                handler: this.showNodeWindowForm,
                                scope: this
                            },{
                                id:'delete',
                                text:'Remove Node',
                                iconCls: 'icon-remove',
                                handler: function(){
                                    var s = this.getSelectionModel().getSelectedNode();
                                    if(s){ // has selection
                                        Ext.Msg.show({
                                            title: 'Remove Node',
                                            buttons: Ext.MessageBox.YESNOCANCEL,
                                            msg: 'Remove '+s.text+'?',
                                            icon: Ext.MessageBox.WARNING,
                                            fn: function(btn){
                                                if (btn == 'yes'){
                                                    var conn = new Ext.data.Connection();
                                                    conn.request({
                                                        url: 'node/jsonDelete',
                                                        params: {'sf_method':'delete',id: s.id},
                                                        success: function(resp,opt) {
                                                            nodesPanel.removeNode(s.id);
                                                        },
                                                        failure: function(resp,opt) {
                                                            Ext.Msg.show({
                                                                title: 'Error',
                                                                buttons: Ext.MessageBox.OK,
                                                                msg: 'Unable to delete node',
                                                                icon: Ext.MessageBox.ERROR});
                                                        }
                                                    });// END Ajax request
                                                }//END button==yes
                                            }// END fn
                                        }); //END Msg.show
                                    }// end has selection
                                },// end handler
                                scope: this
                            }]
                        /*
                         * end nodes top toolbar
                         */
                    });// end NodePanel superclass

                    this.nodes = this.root;

                    // add a tree sorter in folder mode
                    new Ext.tree.TreeSorter(this, {folderSort:true});

                    this.getSelectionModel().on({
                        'beforeselect' : function(sm, node){return true;},
                        'selectionchange' : function(sm, node){
                            if(node){this.fireEvent('nodeselect', node.attributes);}
                            this.getTopToolbar().items.get('delete').setDisabled(node==this.root);}, //disable button Remove for root node
                        scope:this
                    });

                    this.addEvents({nodeselect:true});

                    this.on('contextmenu', this.onContextMenu, this);


                    //            this.store.on('beforeload',function(){
                    //                alert('a');
                    //                initNodesState();
                    //                })
                };// end NodePanel function

                /*
                 *
                 * Extend NodePanel
                 *
                 */

                Ext.extend(NodePanel, Ext.tree.TreePanel,{
                    /*
                     * Create context menu
                     */
                    onContextMenu : function(node, e){
                        if(!this.menu){ // create context menu on first right click
                            this.menu = new Ext.menu.Menu({
                                id:'nodes-ctx',
                                items: [{
                                        id:'load-node',
                                        iconCls:'load-icon',
                                        text:'Load Node',
                                        scope: this,
                                        handler:function(){this.ctxNode.select();}
                                    },
                                    {
                                        id:'soap-nodeinfo',
                                        iconCls:'add-node',
                                        text:'Node Soap',
                                        param:'listDomains',
                                        menu:[{text:'listDomains',param:'listDomains&id='+node.id,handler: this.showNodeWindowSoap},
                                            {text:'getphydisk',param:'getphydisk_as_xml&id='+node.id,handler: this.showNodeWindowSoap},
                                            '-',
                                            {text:'Go Virtual Machines',param:'list_vms&opt=update&id='+node.id,handler: this.showNodeWindowSoap},
                                            '-',
                                            {text:'Sync Virtual Machines',param:'list_vms_as_xml&id='+node.id,handler: this.showNodeWindowSoap}],
                                        scope: this,
                                        handler: this.showNodeWindowSoap
                                    },
                                    '-',{
                                        id:'add-node',
                                        iconCls:'add-node',
                                        text:'Add Node',
                                        scope: this,
                                        handler: this.showNodeWindowForm
                                    },{
                                        id:'remove-node',
                                        iconCls:'delete-icon',
                                        text:'Remove Node',
                                        scope: this,
                                        handler:function(){
                                            var ctx = this.ctxNode;
                                            Ext.Msg.show({
                                                title: 'Remove Node',
                                                buttons: Ext.MessageBox.YESNOCANCEL,
                                                msg: 'Remove '+this.ctxNode.text+'?',
                                                icon: Ext.MessageBox.WARNING,
                                                fn: function(btn){
                                                    if (btn == 'yes'){
                                                        var conn = new Ext.data.Connection();
                                                        conn.request({
                                                            url: 'node/jsonDelete',
                                                            params: {'sf_method':'delete',
                                                                id: ctx.id
                                                            },
                                                            success: function(resp,opt) {
                                                                ctx.ui.removeClass('x-node-ctx');
                                                                //nodes.fireEvent('removeNode',ctx.id);
                                                                //nodesPanel.fireEvent('removeNode',ctx.id);
                                                                nodesPanel.removeNode(ctx.id);
                                                                ctx = null;
                                                            },
                                                            failure: function(resp,opt) {
                                                                Ext.Msg.show({
                                                                    title: 'Error',
                                                                    buttons: Ext.MessageBox.OK,
                                                                    msg: 'Unable to delete node',
                                                                    icon: Ext.MessageBox.ERROR});
                                                            }
                                                        });// END Ajax request
                                                    }//END button==yes
                                                }// END fn
                                            }); //END Msg.show
                                        } //end Remove Node handler
                                    }]//end contextmenu items
                            }); //end this.menu

                            this.menu.on('hide', this.onContextHide, this);
                        } //end if create menu

                        if(this.ctxNode){
                            this.ctxNode.ui.removeClass('x-node-ctx');
                            this.ctxNode = null;
                        }

                        if(!node.isLeaf()){ //open context menu only if node is not a leaf
                            this.ctxNode = node;
                            this.ctxNode.ui.addClass('x-node-ctx');
                            this.menu.items.get('load-node').setDisabled(node.isSelected());
                            this.menu.items.get('remove-node').setDisabled(node.id==0);
                            this.menu.showAt(e.getXY());
                        }
                    },
                    /*
                     * end onContextMenu
                     */
                    onContextHide : function(){
                        if(this.ctxNode){
                            this.ctxNode.ui.removeClass('x-node-ctx');
                            this.ctxNode = null;
                        }
                    },
                    /*
                     * Show insert node form
                     */
                    showNodeWindowForm : function(btn){
                        if(!this.win){
                            this.win = new NodeWindowForm();
                            this.win.on('validnode', this.addNode, this);
                        }
                        this.win.show(btn);
                    },
                    showNodeWindowSoap : function(btn){

                        if(!this.winSoap){
                            this.winSoap = new NodeWindowSoap(btn);
                            // this.winSoap.on('validnode', this.addNode, this);
                        }
                        this.winSoap.show(btn);
                    },
                    selectNode: function(id){this.getNodeById(id).select();},
                    removeNode: function(id){
                        var node = this.getNodeById(id);

                        if(node){
                            var gotoNode = (node.isLeaf())? node.parentNode: this.getRootNode();

                            node.unselect();

                            Ext.fly(node.ui.elNode).ghost('l', {
                                callback: node.remove, scope: node, duration: .4});

                            this.getSelectionModel().select(gotoNode);
                        }
                    },
                    updateNode: function(attrs){
                        var exists = this.getNodeById(attrs.id);

                        if(exists){
                            exists.setText(attrs.text);
                            var s = this.getSelectionModel().getSelectedNode();
                            if(s.id==attrs.id) mainPanel.setTitle(attrs.text);
                            return;

                        }
                    },
                    addNode : function(attrs){
                        var exists = this.getNodeById(attrs.id);
                        var s = this.getSelectionModel().getSelectedNode();
                        var appendTo = this.nodes;

                        if(s.isLeaf()) appendTo = s.parentNode;
                        else if(attrs.leaf) appendTo = s;

                        if(!appendTo.expanded) appendTo.expand();

                        if(exists){

                            //   this.fireEvent('nodeselect', attrs);

                            exists.select();
                            exists.ui.highlight();
                            return;
                        }

                        if(!attrs.leaf)
                            Ext.apply(attrs, {
                                //  iconCls: 'node-icon',
                                // leaf:true,
                                //  cls:'node',
                                children: [],
                                expanded: true,
                                cls: 'x-tree-node-collapsed'});

                        var node = new Ext.tree.AsyncTreeNode(attrs);
                        appendTo.appendChild(node);

                        Ext.fly(node.ui.elNode).slideIn('l', {
                            callback: node.select, scope: node, duration: .4});

                        return node;
                    },
                    // prevent the default context menu when you miss the node
                    afterRender : function(){
                        NodePanel.superclass.afterRender.call(this);
                        this.el.on('contextmenu', function(e){e.preventDefault();});
                    }
                });
                /*
                 *
                 * end Extend NodePanel
                 *
                 */


                /*
                 *
                 * end Node Panel (left side)
                 *
                 *
                 */




                Ext.ux.Logger = function() {
                    var tpl = new Ext.Template("<div class='x-log-entry'><div class='x-log-level x-log-{0:lowercase}'>" +
                        "{0:capitalize}</div><span class='x-log-time'>{2:date('H:i:s')}</span>" +
                        "<span class='x-log-message'>{1}</span></div>");

                    return Ext.apply(new Ext.Panel({
                        //  id:'log-panel',

                        region:'south',
                        // region:'center',
                        title: 'Log',
                        closeAction: 'hide',
                        collapsible: true,
                        margins: '0 3 3 3',
                        split: true,
                        useSplitTips: true,
                        height: 90,
                        autoScroll: true,
                        bbar:['->',{
                                text: 'Clear',
                                handler: function() {
                                    Ext.ux.Logger.body.update('');
                                }}
                        ]
                    }), {
                        fn: Ext.Template.prototype.insertFirst,

                        toggleDirection: function() {
                            if (this.fn === Ext.Template.prototype.insertFirst) {
                                this.fn = Ext.Template.prototype.append;
                            } else {
                                this.fn = Ext.Template.prototype.insertFirst;
                            }
                        },

                        debug: function(msg) {
                            this.fn.call(tpl, this.body, ['debug', msg, new Date()], true).scrollIntoView(this.body);
                        },

                        info: function(msg) {
                            this.fn.call(tpl, this.body, ['info', msg, new Date()], true).scrollIntoView(this.body);
                        },

                        warning: function(msg) {
                            this.fn.call(tpl, this.body, ['warning', msg, new Date()], true).scrollIntoView(this.body);
                        },

                        error: function(msg) {
                            this.fn.call(tpl, this.body, ['error', msg, new Date()], true).scrollIntoView(this.body);
                        }
                    });
                }();



                /*
                 *
                 * Main Panel (center side)
                 *
                 *
                 */

                mainPanel = new Ext.Panel({
                    // defaults: {bodyStyle: 'padding:10px'},
                    margins: '3 3 3 3',
                    layout: 'card',
                //   activeItem: 0,
                    id:'main-panel',
                  //  title:'Welcome',
                    region: 'center',
//                    items:[
//                        {title:'fda',html:'fda'},
//                        {title:'ddda',html:'ddd'},
//                        {title:'gggda',html:'ggg'}
//                    ],
                  //  layoutConfig: {layoutOnCardChange: true},
                 //   defaults: {hideMode: 'offsets'},
                  //  width:300,
                  //  hideMode:'offsets',
                   // layoutOnCardChange:true,
                    collapsible: false,
                    border:true});

                    

                /*
                 *
                 * Initializing some stuff
                 *
                 */

                // Ext.ux.Logger.show();
                // Ext.ux.Logger.info('Hello World!!!');


                nodesPanel = new NodePanel();
                nodesPanel.on('dblclick' ,function(node){this.fireEvent('nodeselect', node.attributes);});
                nodesPanel.on('click' ,function(node){if(!node.isLeaf()) node.expand();});
                //    nodesPanel.on('addNode', this.addNode, this);
                //    nodesPanel.on('addNode',function(node){nodes.addNode(node);});
                //    nodesPanel.on('updateNode',function(node){nodes.updateNode(node);});
                //    nodesPanel.on('removeNode',function(id){nodes.removeNode(id);});

                nodesPanel.on('nodeselect', function(node){
                 
                    var centerElem = mainPanel.findById('view-center-panel-'+node.id);
                    if(!centerElem)
                    /*
                     * create component and add to mainPanel
                     */
                    {
                        
                        centerElem = mainPanel.add(
                                    {
                                     id: 'view-center-panel-'+node.id,
                                     title: node.text,
                                     autoLoad:{url: node.url,
                                               params:{containerId:node.id},
                                               scripts:true,scope:this,
                                               text:'Please wait...',
                                               callback:function(el,succ,resp,opt){
                                                        Ext.getCmp('view-center-panel-'+node.id).doLayout();
                         
                                             }},
                                     layout:'fit',
                                     bodyStyle:'padding:0px;',
                                     scripts:true
                                    }
                        );

                        centerElem.on({
                            afterlayout:{scope:this, single:true, fn:function() {

                                var updater = centerElem.getUpdater();
                                updater.disableCaching = true;
                                updater.on('beforeupdate', function(){
                                    Ext.getBody().mask('Loading '+node.text+' panel...');
                                });
                                updater.on('update', function(){
                                    Ext.getBody().unmask();
                                });

                            }}
                        });
                    }//end if

                    mainPanel.layout.setActiveItem(centerElem);                                    

                });


                var viewport = new Ext.Viewport({
                    layout:'border',
                    items:[
                        new Ext.BoxComponent({ // raw element
                            region:'north',                            
                            el: 'header',
                            height:60}),
                        nodesPanel,
                        mainPanel
                        //   ,
                        ,Ext.ux.Logger
                        //                  {
                        //                   id:'log-panel',
                        //                  // xtype: 'panel',
                        //                   region: 'south',
                        //                   layout:'border',
                        //                   title: 'Logs',
                        //                   collapsible: false,
                        //                   split: true,
                        //                   height: 1000,
                        //                   items:[Ext.ux.Logger,
                        //                   {
                        //                   id:'loeg-panel',
                        //                   xtype: 'panel',
                        //                   region: 'east',
                        //                   title: 'east',
                        //                   collapsible: false,
                        //                   split: true
                        //                  }]
                        //                  }
                    ]
                });

                nodesPanel.selectNode(0);
                
                //   setTimeout(initNodesState, 2000);



mainPanel.getUpdater().on('beforeupdate', function(){
     Ext.getBody().mask('Loading...');
});

mainPanel.getUpdater().on('update', function(){
     Ext.getBody().unmask();
});






            }// END init
            ,
            loadFailed:function(proxy, options, response, error) {
                var object = Ext.util.JSON.decode(response.responseText);
                var errorMessage = "Error loading data.";
                Ext.MessageBox.alert('Error Message', errorMessage);
            },
            redirectFromError:function(){
                window.location.href="<?php echo url_for('@homepage',true)?>";
            },
            requestFailed:function(connection, response, options) {
                Ext.MessageBox.alert('Error Message',
                "Please contact support with the following: " +
                    "Status: " + response.status +
                    ", Status Text: " + response.statusText,View.redirectFromError);
            },

            loadPage:function (url, proxy, target){
                Ext.get(proxy).load(url, "", function (oElement, bSuccess, oConn, target){
                    if (bSuccess)
                    {
                        var html = oElement.dom.innerHTML;
                        Ext.get(target).update(html, true, function (){
                            alert("Dynamic loading completed");
                        });
                    }
                    else
                    {
                        alert("Failed to load Page. Please check the URL or try again later.");
                    }
                }.createDelegate(null, [target], true));
            },
             // used to display popup info
    notify:function(params,updater) {

        var notificationWindow = new Ext.ux.window.MessageWindow({
            title:'System info'
            // ,items:[menuScreenPanel]
            ,origin:{offY:-5,offX:-5}
            ,autoHeight:true
            ,iconCls: 'icon-info'
            ,help:false
            // ,pinState: 'pin'//render pinned
            ,hideFx:{delay:2000, mode:'standard'}
            ,listeners:{
                render:function(){
                    // Ext.ux.Sound.play('generic.wav');
                }

            }
        });

        Ext.apply(notificationWindow, params);

        if(updater){
                notificationWindow.on('hide',function(){updater.stopAutoRefresh();})
        }

        notificationWindow.show(Ext.getDoc());
    }


            ,
            clickHandler:function (item, e) {

                Ext.getBody().mask('Retrieving data...');

                //   alert('Clicked on the menu item: ' + item.text);


                //var tt= item;
                //
                Ext.Ajax.request({
                    url: item.url,
                    method: 'GET',
                    success:function(response){                         
                         Ext.get('dynPageContainer').update(response.responseText,true,function(){
                            Ext.getBody().unmask();
                        });
                     //   
                    }
                });


                //Ext.lib.Ajax.request('GET',item.url,
                //{success:function(response)
                //{
                //Ext.get('dynPageContainer').update(response.responseText,true);
                //}});

                // Ext.lib.Ajax.request.on("failure","alert('falhou')");


                // View.loadPage.createCallback("child2.htm", "proxy", "dynPageContainer");
                // button.on('click', function(){

                //Ext.Ajax.request({
                //            url: 'sfGuardGroup/view',
                //            // params : form_data,
                //         //   form : this.nodeForm.getForm().getEl().dom,
                //           // params: Ext.Ajax.serializeForm(this.form.form),
                //          // form: this.form.form,
                //            success: function(response, options){
                //                var html = response.dom.innerHTML;
                //              //  win.show(item);
                //                },
                //     //       failure: this.markInvalid,
                //            scope: this
                //           // title: this.nodeForm.getForm().findField("name").getValue()
                //          //  title: this.form.getForm()..getValue()
                //        });


                // tabs for the center
                //        var tabs = new Ext.TabPanel({
                //            region    : 'center',
                //            margins   : '3 3 3 0',
                //            activeTab : 0,
                //            defaults  : {
                //				autoScroll : true
                //			},
                //            items     : [{
                //                title    : 'Bogus Tab',
                //                html     : 'Ext.example.bogusMarkup'
                //             },{
                //                title    : 'Another Tab',
                //                html     : 'Ext.example.bogusMarkup'
                //             },{
                //                title    : 'Closable Tab',
                //                html     : 'Ext.example.bogusMarkup',
                //                closable : true
                //            }]
                //        });
                //
                //        // Panel for the west
                //        var nav = new Ext.Panel({
                //            title       : 'Navigation',
                //            region      : 'west',
                //            split       : true,
                //            width       : 200,
                //            collapsible : true,
                //            margins     : '3 0 3 3',
                //            cmargins    : '3 3 3 3'
                //        });
                //
                //        var win = new Ext.Window({
                //            title    : 'Layout Window',
                //            closable : true,
                //            width    : 600,
                //            height   : 350,
                //            minimizable : true,
                //            //border : false,
                //            plain    : true,
                //            layout   : 'border'
                //          //    ,autoLoad:{
                // //url:'sfGuardGroup/view',scripts:true,scope:this
                //// }
                // // ,title:Ext.getDom('page-title').innerHTML
                // ,tbar:[{
                // text:'Reload'
                // ,handler:function() {
                // win.load(win.autoLoad.url + '?' + (new Date).getTime());
                // }
                // }]
                // ,listeners:{show:function() {
                // this.loadMask = new Ext.LoadMask(this.body, {
                // msg:'Loading. Please wait...'
                // });}},
                //            items    : [nav, tabs]
                //        });
                // // this.win.show(btn);
                //        win.show(item);
                //   // });


            }

        }// end return
    }();// end View

    Ext.onReady(View.init, View, true);
    // Global Ajax events can be handled on every request!
    //Ext.Ajax.on('beforerequest', function(){Ext.getBody().mask('Loading...');}, this);
    //Ext.Ajax.on('requestcomplete', function(){Ext.getBody().unmask();}, this);
    //Ext.Ajax.on("requestexception",View.requestFailed);
    // Ext.Ajax.on("loadexception",View.requestFailed);


    //Ext.util.Observable.observeClass(Ext.data.Connection);
    //Ext.data.Connection.on('requestcomplete', responseHandler);
    //
    //function responseHandler(conn, response, options) { //debugger;
    //alert('oi');
    //}

// Ext.Ajax.on('beforerequest', function(){
//                                    Ext.MessageBox.show({
//                                    title: 'Please wait',
//                                    msg: 'In process...',
//                                    width:300,
//                                    wait:true,
//                                    modal: false
//                                    });
//
//                       }, this);
//                       Ext.Ajax.on('requestcomplete', function(){Ext.MessageBox.hide();}, this);

    Ext.Ajax.on('requestexception', function(conn,response,options){
        
        if(response.status==500){
            Ext.ux.Logger.error(response.statusText);
            return;

        }

        var responseText = Ext.util.JSON.decode(response.responseText);
        var val = '';

        //if(response.status==503)
        //info(responseText+'<br><br>'+response.statusText);

        // Ext.util.JSON.decode();        
        
        if(responseText){


            var isArray = (responseText['error'].constructor.toString().indexOf("Array") == -1);

            if(!isArray) Ext.ux.Logger.error(responseText);
            if(isArray && responseText['error']){

                var errs_string = '';
                if((typeof responseText['error'])=='string'){
                    errs_string = responseText['error'];
                    Ext.ux.Logger.error(errs_string);
                }
                    
                                                
                //not logged in anymore
                if(response.status==401){

                    Ext.MessageBox.show({
                        title: 'Login Error',
                        msg: 'Need to login',
                        buttons: Ext.MessageBox.OK,
                        fn: function(){
                            window.location = <?php  echo json_encode(url_for('@homepage',true)); ?>;},
                        icon: Ext.MessageBox.ERROR
                    });
                    return;
                }

                //TCP error returned
                if(response.status==404){

                    Ext.MessageBox.show({
                        title: 'Error',
                        msg: '<center><b>The request could not be accomplished!</b></center><br>'+responseText['error'],
                        buttons: Ext.MessageBox.OK,                        
                        icon: Ext.MessageBox.ERROR
                    });
                    return;
                }


            }
        }

    }, this);

    //function updateTab ( tabId, title, url ){
    //				var tab = Ext.getCmp('main-tab').getItem ( tabId );
    //				//alert (tabId);
    //				if (tab){
    //					tab.getUpdater().update(url);
    //					tab.setTitle (title);
    //				} else {
    //					tab=addTab(tabId, title, url);
    //				}
    //
    //				tabPanel.setActiveTab (tab);
    //			};


</script>

<div id="header">
    <h1>Central Management - ETVA</h1>
    <div id="topBar"></div>
</div>
<div id="dynPageContainer"></div>
<div id="notificationDiv" style="display:none"></div>