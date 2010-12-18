<?php
/*
 * storage template.
 * Include disk related functions
 */


// physical volume resize window
//
//
// TODO physical volume resize
//
//
//include_partial('physicalvol/pvresizewin');

// volume group create window
include_partial('volgroup/vgcreatewin');
// logical volume create window
include_partial('logicalvol/lvcreatewin');
// logical volume resize window
include_partial('logicalvol/lvresizewin');

?>
<script>


    // some functions to determine whether is not the drop is allowed
    function hasNode(t, n){

        var tree = t.getOwnerTree();
        var exists = tree.getNodeById(n.id);

        if(exists) return true;
        // tree.expandAll();
        // var root = tree.getRootNode();
        // alert(check_exists(root,n));
        //  var root = tree.getRootNode();

        // alert(tree.getRootNode().findChild('id', n.id));

        return (t.attributes.type == 'vg' && t.findChild('id', n.id)) ||
            (t.leaf === true && t.parentNode.findChild('id', n.id));
    };

    // check if source node is initiliazed has pv
    function isInit(n){ return (n.attributes.type == 'dev-pv' );};


    // canMoveTo(target node, source node)
    function canMoveTo(e, n){
        var a = e.target.attributes;

        if(!isInit(n)){
            Ext.Msg.show({
                title: 'Error',
                buttons: Ext.MessageBox.OK,
                msg: n.text+ ' not initialized',
                icon: Ext.MessageBox.ERROR});
        }

        return n.getOwnerTree() == doDevPanel && isInit(n) && !hasNode(e.target, n) &&
            ((e.point == 'append' && a.type == 'vg') );
    };




    /*
     * devices tree
     */
    treeDEV = function() {
        this.node_id = <?php echo $node->getId(); ?>;

        this.tree = new Ext.tree.ColumnTree({
            width: 200,
            height: 300,
            rootVisible:false,
            autoScroll:true,
            enableDrag:true,
            enableDrop:false,
            columnWidth:1/3,
            border:false,
            title: 'Devices',
            columns:[{
                    header:'Device',
                    width:100,
                    dataIndex:'text'
                },{
                    header:'Volume size',
                    width:100,
                    dataIndex:'pretty-pvsize'
                },
                {
                    header:'Device size',
                    width:100,
                    dataIndex:'prettysize'
                }],
            loader: new Ext.tree.TreeLoader({
                dataUrl:<?php echo json_encode(url_for('physicalvol/jsonPhydiskTree'))?>,
                baseParams:{'nid':this.node_id},
                uiProviders:{
                    'col': Ext.tree.ColumnNodeUI
                }
            }),
            id:'dev-tree',
            // panel tools: refresh
            tools:[{
                    id:'refresh',
                    on:{
                        click: function(){this.tree.root.reload();}
                        ,scope:this
                    }                    
                }],
            root: new Ext.tree.AsyncTreeNode({
                text: 'Devices',
                draggable: false,
                expanded: true,
                singleClickExpand:true,
                selected:true,
                id: '0'                  // this IS the id of the startnode
            }),
            rootVisible:false,
            listeners:{
                'beforeload':function(){this.body.mask('Loading', 'x-mask-loading');},
                'load':function(){this.body.unmask();}
            }
        });// end this.tree

        this.tree.loader.on('loadexception', function(loader,node,resp){
            var response = Ext.util.JSON.decode(resp.responseText);
            Ext.ux.Logger.error(response);

            var error_win = Ext.getCmp('storage-error');
            if(!error_win){
                Ext.Msg.show({id:'storage-error',
                    title: 'Error',
                    buttons: Ext.MessageBox.OK,
                    msg: response,
                    icon: Ext.MessageBox.ERROR});
            }else if(!error_win.isVisible()) //not visible box
                error_win.show();
        });// end load exception

        // on context click call onContextMenu
        this.tree.on('contextmenu', this.onContextMenu,this);

        // sort....
        new Ext.tree.TreeSorter(this.tree, {
            folderSort: true,
            dir: "desc",
            sortType: function(node) {
                var size = node.attributes.size;
                // sort by a custom, typed attribute:
                // return parseInt(node.id, 10);
                return parseInt(size);
            }
        }); // end sort

    }// end function treeDEV


    // define public methods tree-dev
    Ext.extend(treeDEV, Ext.tree.TreePanel, {
        render:function(){
            return this.tree;
        },
        // on click menu
        onContextMenu : function(node, e){
            if(!this.menu){ // create context menu on first right click
                this.menu = new Ext.menu.Menu({
                    id:'nodes-ctx',
                    items: [{id:'pv-create',
                            iconCls:'go-action',
                            text:'Initialize volume',
                            scope: this,
                            handler:this.pvcreate
                        },
                        // TODO
                        //   resize pv
                        //   {id:'pv-resize',
                        //     iconCls:'go-action',
                        //     text:'Resize volume',
                        //     scope: this,
                        //     handler:this.pvresize
                        //    },
                        {id:'pv-remove',
                            iconCls:'go-action',
                            text:'Unitialize volume',
                            scope: this,
                            handler:this.pvremove
                        }]
                }); // end menu

                this.menu.on('hide', this.onContextHide, this);

            } //end if create menu

            if(this.ctxNode){
                this.ctxNode.ui.removeClass('x-node-ctx');
                this.ctxNode = null;
            }

            if(node.isLeaf()){ //open context menu only if node is a leaf
                this.ctxNode = node;
                this.ctxNode.ui.addClass('x-node-ctx');
                this.menu.items.get('pv-create').setDisabled(node.attributes.cls=='dev-pv');                
                this.menu.items.get('pv-remove').setDisabled(node.attributes.cls=='dev-pd');
                this.menu.showAt(e.getXY());
            }
        },
        onContextHide : function(){
            if(this.ctxNode){
                this.ctxNode.ui.removeClass('x-node-ctx');
                this.ctxNode = null;
            }
        },        
        // initialize physical volume        
        // args: id: device ID
        pvcreate:function(){
            var ctx = this.ctxNode;            
            var tree = ctx.getOwnerTree();
            var conn = new Ext.data.Connection({
                listeners:{
                    // wait message.....
                    beforerequest:function(){
                        Ext.MessageBox.show({
                            title: 'Please wait',
                            msg: 'Initializing physical volume...',
                            width:300,
                            wait:true,
                            modal: false
                        });
                    },// on request complete hide message
                    requestcomplete:function(){Ext.MessageBox.hide();}}
            });// end conn


            //send pvcreate SOAP request
            conn.request({
                url: <?php echo json_encode(url_for('physicalvol/jsonInit'))?>,
                params: {'nid':this.node_id,'dev':ctx.attributes.device},
                scope:this,
                success: function(resp,opt){
                    var response = Ext.util.JSON.decode(resp.responseText);
                                        
                    Ext.ux.Logger.info(response['response']);

                    tree.root.reload(function(){

                        tree.getNodeById(ctx.parentNode.id).expand(false,false,
                                                function(){tree.getNodeById(ctx.id).select();}
                                                );

                    });

                    
                },
                failure: function(resp,opt) {
                    var response = Ext.util.JSON.decode(resp.responseText);

                    Ext.ux.Logger.error(response['error']);

                    Ext.Msg.show({title: 'Error',
                        buttons: Ext.MessageBox.OK,
                        msg: 'Unable to initialize '+ctx.attributes.device,
                        icon: Ext.MessageBox.ERROR});
                }
            });// END Ajax request
        },
        //
        // TODO not used...yet
        //  resize physical volume
        // call: pvresize
        // args: device = /dev/device
        //       size
        pvresize:function(){
            var ctx = this.ctxNode;            

            //uses pvresizewin
            var centerPanel = new pvwin.resizeForm.Main(ctx.text,this.node_id);
            centerPanel.load(ctx);

            var win = Ext.getCmp('pv-resize-win-'+ctx.text);

            if(!win){
                win = new Ext.Window({
                    id: 'pv-resize-win-'+ctx.text,
                    title: 'Resize physical volume '+ctx.text,
                    width:330,
                    height:200,
                    iconCls: 'icon-window',
                    shim:false,
                    animCollapse:false,
                    closeAction:'hide',
                    border:false,
                    constrainHeader:true,
                    layout: 'fit',
                    items: [centerPanel]
                });
            }

            win.show();
        },
        // uninitialize physical volume
        // unsets physical volume info for the device
        // args device ID        
        pvremove:function(){
            var ctx = this.ctxNode;
            var tree = ctx.getOwnerTree();
            
            var conn = new Ext.data.Connection({
                listeners:{
                    beforerequest:function(){
                        Ext.MessageBox.show({
                            title: 'Please wait',
                            msg: 'Unitializing physical volume...',
                            width:300,
                            wait:true,
                            modal: false
                        });

                    },
                    requestcomplete:function(){Ext.MessageBox.hide();}

                }
            });

            conn.request({
                url: <?php echo json_encode(url_for('physicalvol/jsonUninit'))?>,                
                params: {'nid':this.node_id,'dev':ctx.attributes.device},
                scope:this,
                success: function(resp,opt){
                    var response = Ext.util.JSON.decode(resp.responseText);

                    Ext.ux.Logger.info(response['response']);

                    tree.root.reload(function(){

                        tree.getNodeById(ctx.parentNode.id).expand(false,false,
                                                function(){tree.getNodeById(ctx.id).select();}
                                                );

                    });
                },
                failure: function(resp,opt) {
                    var response = Ext.util.JSON.decode(resp.responseText);

                    Ext.ux.Logger.error(response['error']);

                    Ext.Msg.show({title: 'Error',
                        buttons: Ext.MessageBox.OK,
                        msg: 'Unable to uninitialize '+ctx.attributes.device,
                        icon: Ext.MessageBox.ERROR});
                }
                
            });// END Ajax request

        }// end pvremove
    });// end extend treeDEV


    /*
     *  end devices tree
     */
    var devPanel = new treeDEV();
    var doDevPanel = devPanel.render();






    /*
     *  volume groups tree
     */
    treeVG = function() {

        this.node_id = <?php echo $node->getId(); ?>;        

        this.tree = new Ext.tree.ColumnTree({
            width: 200,
            height: 300,
            rootVisible:false,
            autoScroll:true,
            enableDrag:false,
            enableDrop:true,
            border:false,
            columnWidth:1/3,
            title: 'Volume Groups',
            columns:[{
                    header:'Volume Group',
                    width:100,
                    dataIndex:'text'
                },{
                    header:'Size',
                    width:100,
                    dataIndex:'prettysize'
                }],
            loader: new Ext.tree.TreeLoader({
                dataUrl:<?php echo json_encode(url_for('volgroup/jsonVgsTree'))?>,
                baseParams:{'nid':this.node_id},
                uiProviders:{
                    'col': Ext.tree.ColumnNodeUI
                }
            }),
            id:'vg-tree',
            tools:[{
                    id:'refresh',
                    on:{
                        click: function(){this.tree.root.reload();}
                        ,scope:this
                    }
                }],
            root: new Ext.tree.AsyncTreeNode({
                text: 'Volume Groups',
                draggable: false,
                expanded: true,
                singleClickExpand:true,
                selected:true,
                id: '0'                  // this IS the id of the startnode
            }),
            rootVisible:false,
            listeners:{
                'beforeload':function(){this.body.mask('Loading', 'x-mask-loading');},
                'load':function(){this.body.unmask();}
            }
        });// end this.tree

        this.tree.loader.on('loadexception', function(loader,node,resp){
            var response = Ext.util.JSON.decode(resp.responseText);
            Ext.ux.Logger.error(response);

            var error_win = Ext.getCmp('storage-error');
            if(!error_win){
                Ext.Msg.show({id:'storage-error',
                    title: 'Error',
                    buttons: Ext.MessageBox.OK,
                    msg: response,
                    icon: Ext.MessageBox.ERROR});
            }else if(!error_win.isVisible()) //not visible box
                error_win.show();
        });// end load exception

        this.tree.on('contextmenu', this.onContextMenu,this);

        // sort....
        new Ext.tree.TreeSorter(this.tree, {
            folderSort: true,
            dir: "desc",
            sortType: function(node) {
                var size = node.attributes.size;
                // sort by a custom, typed attribute:
                // return parseInt(node.id, 10);
                return parseInt(size);
            }
        }); // end sort

        // event to perform vgextend
        //
        // vgextend is called on drop node
        //
        // call: vgextend
        // args: vgname = name
        //       pv1 =
        this.tree.on('nodedragover', function(e){
            e.tree.expandAll();});
        this.tree.on('beforenodedrop', function(e){
            var n = e.dropNode;            

            // canMoveTo(destination node,source node)
            if(canMoveTo(e, n)){

                 var copy = new Ext.tree.TreeNode(
                Ext.apply({allowDelete:true,expanded:true},n.attributes));
                e.dropNode = copy;
                
                return true;
            }
            return false;

        },this);// end beforenodedrop event


        this.tree.on('nodedrop', function(e){
            var tree = e.target.getOwnerTree();
            var n = e.dropNode;
            var pvs = {'pv1':n.attributes.device};

             // everthing ok
                // send data to virt agent

                var conn = new Ext.data.Connection({
                    listeners:{
                        beforerequest:function(){
                            Ext.MessageBox.show({
                                title: 'Please wait',
                                msg: 'Adding to volume group...',
                                width:300,
                                wait:true,
                                modal: false
                            });

                        },
                        requestcomplete:function(){Ext.MessageBox.hide();}

                    }
                });// end conn

                conn.request({
                    url: <?php echo json_encode(url_for('volgroup/jsonUpdate'))?>,                    
                    params: {'nid':this.node_id,'pvs':Ext.encode(pvs),'vg':e.target.id},
                    scope:this,
                    success: function(resp,opt){
                        var response = Ext.util.JSON.decode(resp.responseText);
                        Ext.ux.Logger.info(response['response']);

                       
                        tree.root.reload(function()
                                        {tree.getNodeById(e.target.id).expand();}
                                    );


                    },
                    failure: function(resp,opt) {
                        var response = Ext.util.JSON.decode(resp.responseText);
                        n.remove();
                        Ext.ux.Logger.error(response['error']);
                        Ext.Msg.show({
                            title: 'Error',
                            buttons: Ext.MessageBox.OK,
                            msg: 'Unable to extend '+e.target.attributes.id,
                            icon: Ext.MessageBox.ERROR});

                    }
                });// END Ajax request
        
        },this);



    }// end function treeVG



    // define public methods
    Ext.extend(treeVG, Ext.tree.TreePanel, {
        render:function(){
            return this.tree;
        },
        onContextMenu : function(node, e){
            if(!this.menu){ // create context menu on first right click
                this.menu = new Ext.menu.Menu({
                    id:'nodes-ctx',
                    items: [
                        {
                            id:'vg-create',
                            iconCls:'go-action',
                            text:'Create volume group',
                            scope: this,
                            handler:this.vgcreate
                        },{
                            id:'vg-remove',
                            iconCls:'go-action',
                            text:'Remove volume group',
                            scope: this,
                            handler:this.vgremove
                        },
                        '-',{
                            id:'vg-remove-pv',
                            iconCls:'go-action',
                            text:'Remove physical volume',
                            scope: this,
                            handler:this.vgreduce
                        }]
                });// end menu

                this.menu.on('hide', this.onContextHide, this);

            } //end if create menu

            if(this.ctxNode){
                this.ctxNode.ui.removeClass('x-node-ctx');
                this.ctxNode = null;
            }

            if(node.isLeaf()){ //open context menu only if node is a leaf
                this.ctxNode = node;
                this.ctxNode.ui.addClass('x-node-ctx');
                this.menu.items.get('vg-create').setDisabled(true);
                this.menu.items.get('vg-remove-pv').setDisabled(false);
                this.menu.items.get('vg-remove').setDisabled(true);

                if(node.isLast() && node.isFirst()) // last pv in vg. cannot use vgreduce. only vgremove
                {
                    this.menu.items.get('vg-remove-pv').setDisabled(true);

                }


            }else{
                this.ctxNode = node;
                this.ctxNode.ui.addClass('x-node-ctx');
                this.menu.items.get('vg-create').setDisabled(false);
                this.menu.items.get('vg-remove-pv').setDisabled(true);
                this.menu.items.get('vg-remove').setDisabled(false);
            }

            this.menu.showAt(e.getXY());

        },
        onContextHide : function(){
            if(this.ctxNode){
                this.ctxNode.ui.removeClass('x-node-ctx');
                this.ctxNode = null;
            }
        },
        // create volume group
        // call: open template volgroup/_vgcreatewin
        // see _vgcreatewin        
        vgcreate:function(){

            
            // TODO: use this for multiple selection
            //var selModel = this.tree.getSelectionModel();
            //selNodes = selModel.getUniqueSelectedNodes();
            //alert(selNodes);

            //uses vgcreatewin
            var centerPanel = new vgwin.createForm.Main(this.node_id);

            var win = Ext.getCmp('vg-create-win');

            if(!win){
                win = new Ext.Window({
                    id: 'vg-create-win',
                    title: 'Create new volume group',
                    width:510,
                    height:350,
                    iconCls: 'icon-window',
                    bodyStyle: 'padding:10px;',
                    shim:false,
                    border:true,
                    constrainHeader:true,
                    layout: 'fit',
                    items: [centerPanel]
                });

            }

            win.show();

            centerPanel.load();
        },        
        // removes physical volume from volume group
        // call: vgreduce
        // args: vgname = name
        //       pv1 = /dev/device
        vgreduce:function(){
            var ctx = this.ctxNode;
            var tree = ctx.getOwnerTree();            
            var pvs = {'pv1':ctx.attributes.pv};
            var conn = new Ext.data.Connection({
                listeners:{
                    // wait message.....
                    beforerequest:function(){
                        Ext.MessageBox.show({
                            title: 'Please wait',
                            msg: 'Removing physical volume...',
                            width:300,
                            wait:true,
                            modal: false
                        });
                    },// on request complete hide message
                    requestcomplete:function(){Ext.MessageBox.hide();}}
            });// end conn

            //send vgreduce SOAP request
            conn.request({
                url: <?php echo json_encode(url_for('volgroup/jsonReduce'))?>,                
                params: {'nid':this.node_id,'vg':ctx.parentNode.id,'pvs': Ext.encode(pvs)},
                scope:this,
                success: function(resp,opt){
                    var response = Ext.util.JSON.decode(resp.responseText);
                    Ext.ux.Logger.info(response['response']);

                    tree.root.reload(function(){
                        tree.getNodeById(ctx.parentNode.id).expand();}
                    );

                },
                failure: function(resp,opt) {
                    var response = Ext.util.JSON.decode(resp.responseText);

                    Ext.ux.Logger.error(response['error']);

                    Ext.Msg.show({title: 'Error',
                        buttons: Ext.MessageBox.OK,
                        msg: 'Unable to remove '+ctx.attributes.text+' from '+ctx.parentNode.id+' group',
                        icon: Ext.MessageBox.ERROR});
                }
            });// END Ajax request

        },        
        vgremove:function(){
            var ctx = this.ctxNode;
            var tree = ctx.getOwnerTree();
           
            var conn = new Ext.data.Connection({
                listeners:{
                    beforerequest:function(){
                        Ext.MessageBox.show({
                            title: 'Please wait',
                            msg: 'Removing volume group...',
                            width:300,
                            wait:true,
                            modal: false
                        });

                    },
                    requestcomplete:function(){Ext.MessageBox.hide();}

                }
            });

            conn.request({
                url: <?php echo json_encode(url_for('volgroup/jsonRemove'))?>,               
                params: {'nid':this.node_id,'vg':ctx.id },
                scope:this,
                success: function(resp,opt){
                    
                    var response = Ext.util.JSON.decode(resp.responseText);
                    Ext.ux.Logger.info(response['response']);
                    tree.root.reload();
              

                },
                failure: function(resp,opt) {
                    var response = Ext.util.JSON.decode(resp.responseText);
                    Ext.ux.Logger.error(response['error']);
                    Ext.Msg.show({
                        title: 'Error',
                        buttons: Ext.MessageBox.OK,
                        msg: 'Unable to remove '+ctx.id+' group',
                        icon: Ext.MessageBox.ERROR});
                }
            });// END Ajax request
        }

        
    });// end extend treeVG


    /*
     *  end volume groups tree
     */


    var vgPanel = new treeVG();
    var doVgPanel = vgPanel.render();


    /*
     *  logical volumes tree
     */
    treeLV = function() {
        this.node_id = <?php echo $node->getId(); ?>;
        
        this.tree = new Ext.tree.ColumnTree({
            width: 200,
            height: 300,
            rootVisible:false,
            autoScroll:true,
            enableDD:false,
            border:false,
            columnWidth:1/3,
            title: 'Logical Volumes',
            columns:[{
                    header:'Logical Volume',
                    width:150,
                    dataIndex:'text'
                },
                {
                    header:'Volume Group',
                    width:100,
                    dataIndex:'vg'
                },
                {
                    header:'Size',
                    width:100,
                    dataIndex:'prettysize'
                }],
            loader: new Ext.tree.TreeLoader({
                dataUrl:<?php echo json_encode(url_for('logicalvol/jsonLvsTree'))?>,
                baseParams:{'nid':this.node_id},
                uiProviders:{
                    'col': Ext.tree.ColumnNodeUI
                }
            }),
            id:'lv-tree',
            tools:[{
                    id:'refresh',
                    on:{
                        click: function(){this.tree.root.reload();}
                        ,scope:this
                    }
                }],
            root: new Ext.tree.AsyncTreeNode({
                text: 'Volume',
                draggable: false,
                expanded: true,
                singleClickExpand:true,
                selected:true,
                id: '0'                  // this IS the id of the startnode
            }),
            rootVisible:true,
            listeners:{
                'beforeload':function(){this.body.mask('Loading', 'x-mask-loading');},
                'load':function(){this.body.unmask();}
            }

        });// end this.tree



        this.tree.loader.on('loadexception', function(loader,node,resp){
            var response = Ext.util.JSON.decode(resp.responseText);
            Ext.ux.Logger.error(response);

            var error_win = Ext.getCmp('storage-error');
            if(!error_win){
                Ext.Msg.show({id:'storage-error',
                    title: 'Error',
                    buttons: Ext.MessageBox.OK,
                    msg: response,
                    icon: Ext.MessageBox.ERROR});
            }else if(!error_win.isVisible()) //not visible box
                error_win.show();
        });// end load exception


        this.tree.on('contextmenu', this.onContextMenu,this);

        // sort....
        new Ext.tree.TreeSorter(this.tree, {
            folderSort: true,
            dir: "desc",
            sortType: function(node) {
                var size = node.attributes.size;
                // sort by a custom, typed attribute:
                // return parseInt(node.id, 10);
                return parseInt(size);
            }
        }); // end sort

    }// end function treeLV



    // define public methods
    Ext.extend(treeLV, Ext.tree.TreePanel, {
        render : function(){

            return this.tree;
        },
        onContextMenu : function(node,e){
            if(!this.menu){ // create context menu on first right click
                this.menu = new Ext.menu.Menu({
                    id:'nodes-ctx',
                    items: [{
                            id:'lv-create',
                            iconCls:'go-action',
                            text:'Create logical volume',
                            scope: this,
                            handler:this.lvcreate
                        },
                        {id:'lv-resize',
                            iconCls:'go-action',
                            text:'Resize logical volume',
                            scope: this,
                            handler:this.lvresize
                        },
                        {id:'lv-remove',
                            iconCls:'go-action',
                            text:'Remove logical volume',
                            scope: this,
                            handler:this.lvremove
                        }
                        // TODO snapshot?!
                        //                                ,'-',
                        //                                {id:'lv-snapshot',
                        //                                iconCls:'go-action',
                        //                                text:'Take snapshot',
                        //                                scope: this,
                        //                                handler:this.lvsnapshot
                        //                                }
                    ]
                });

                this.menu.on('hide', this.onContextHide, this);

            } //end if create menu

            if(this.ctxNode){
                this.ctxNode.ui.removeClass('x-node-ctx');
                this.ctxNode = null;
            }

            //if(node.isLeaf()){ //open context menu only if node is a leaf
            this.ctxNode = node;
            this.ctxNode.ui.addClass('x-node-ctx');
            this.menu.showAt(e.getXY());
            //}
        },
        onContextHide : function(){
            if(this.ctxNode){
                this.ctxNode.ui.removeClass('x-node-ctx');
                this.ctxNode = null;
            }
        },
        // create logical volume
        // call: open template logicalvol/_lvcreatewin
        // see _lvcreatewin
        lvcreate:function(){            

            //uses lvcreatewin

            var win = Ext.getCmp('lv-create-win');

            if(!win){
                var centerPanel = new lvwin.createForm.Main(this.node_id);
                win = new Ext.Window({
                    id: 'lv-create-win',
                    title: 'Create new logical volume',
                    width:430,
                    height:210,
                    iconCls: 'icon-window',
                    bodyStyle: 'padding:10px;',
                    shim:false,
                    border:true,
                    constrainHeader:true,
                    layout: 'fit',
                    items: [centerPanel]
                });

            }

            win.show();
        },        
        // removes logical volume        
        // args: id: lv ID
        lvremove:function(){
            var ctx = this.ctxNode;
            var tree = ctx.getOwnerTree();

            var conn = new Ext.data.Connection({
                listeners:{
                    beforerequest:function(){
                        Ext.MessageBox.show({
                            title: 'Please wait',
                            msg: 'Removing logical volume...',
                            width:300,
                            wait:true,
                            modal: false
                        });

                    },
                    requestcomplete:function(){Ext.MessageBox.hide();}

                }
            });

            conn.request({
                url: <?php echo json_encode(url_for('logicalvol/jsonRemove'))?>,             
                params: {'nid': this.node_id,'lv':ctx.attributes.text},
                scope:this,

                success: function(resp,opt){
                    var response = Ext.util.JSON.decode(resp.responseText);                
                    Ext.ux.Logger.info(response['response']);
                    tree.root.reload();
                },
                failure: function(resp,opt) {
                    var response = Ext.util.JSON.decode(resp.responseText);
                    Ext.ux.Logger.error(response['error']);
                    Ext.Msg.show({
                        title: 'Error',
                        buttons: Ext.MessageBox.OK,
                        msg: 'Unable to remove '+ctx.attributes.text+' logical volume',
                        icon: Ext.MessageBox.ERROR});
                }
            });// END Ajax request
        },
        // resize logical volume
        // call: lvresize
        // args: lv = lvname
        //       size
        lvresize:function(){
            var ctx = this.ctxNode;            

            //uses lvresizewin

            var win = Ext.getCmp('lv-resize-win');


            if(!win){
                var centerPanel = new lvwin.resizeForm.Main(this.node_id);
                centerPanel.load(ctx);

                win = new Ext.Window({
                    id: 'lv-resize-win',
                    title: 'Resize logical volume '+ctx.text,
                    width:330,
                    height:200,
                    iconCls: 'icon-window',
                    shim:false,
                    animCollapse:false,
                    //     closeAction:'hide',
                    modal:true,
                    border:false,
                    constrainHeader:true,
                    layout: 'fit',
                    items: [centerPanel]
                });
            }

            win.show();
        },
        // TODO not implemented yet
        // logical volume snapshot
        // call: createsnapshot
        // args: olv = vgname/lvname
        //       slv = lvname
        //       size
        lvsnapshot:function(){
            var ctx = this.ctxNode;

            Ext.MessageBox.prompt('Snapshot for '+ctx.id, 'Please enter new logic volume name:', function(btn,text){

                var params = {'olv':ctx.attributes.vg+'/'+ctx.id,'slv':text,'size':ctx.attributes.prettysize};
                var conn = new Ext.data.Connection({
                    listeners:{
                        beforerequest:function(){
                            Ext.MessageBox.show({
                                title: 'Please wait',
                                msg: 'Applying snapshot...',
                                width:300,
                                wait:true,
                                modal: false
                            });

                        },
                        requestcomplete:function(){Ext.MessageBox.hide();}

                    }
                });

                conn.request({
                    url: <?php echo json_encode('node/soap?method=createsnapshot&id='.$node->getID())?>,
                    params: {'params': Ext.encode(params)},
                    scope:this,

                    success: function(resp,opt) {
                        var response = Ext.util.JSON.decode(resp.responseText);
                        var tree = Ext.getCmp('lv-tree');

                        Ext.ux.Logger.info(response);

                        var node = new Ext.tree.TreeNode({text:text,iconCls:'devices-folder'});
                        tree.root.appendChild(node);

                        Ext.fly(node.ui.elNode).slideIn('l', {
                            callback: function(){tree.root.reload();},
                            scope: node,
                            duration: 0.4
                        });


                    },
                    failure: function(resp,opt) {
                        var response = Ext.util.JSON.decode(resp.responseText);
                        Ext.ux.Logger.error(response);
                        Ext.Msg.show({
                            title: 'Error',
                            buttons: Ext.MessageBox.OK,
                            msg: 'Unable to take snapshot of '+ctx.id,
                            icon: Ext.MessageBox.ERROR});
                    }
                });// END Ajax request

            });// end MessageBox

        }// end lvsnapshot
    });

    /*
     *  end logical volume tree
     */


    var lvPanel = new treeLV();
    var doLvPanel = lvPanel.render();




    // main column storage layout
    //panel will be added in viewSuccess
    new Ext.Panel({
        id:'node-storage'
        ,title:'Storages'
        ,border:false        
        ,layout:'column'
        ,layoutConfig: {
            fitHeight: true,
            margin: 5,
            split: true
        }
        ,items:[doDevPanel,doVgPanel,doLvPanel]

    });
    
</script>