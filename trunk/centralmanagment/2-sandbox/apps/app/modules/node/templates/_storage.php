<style>
    .x-tree-node .x-tree-node-disabled div{
        color:gray !important;
    }
</style>
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
include_partial('physicalvol/pvscan');
include_partial('volgroup/vgscan');

// volume group create window
include_partial('volgroup/vgcreatewin');
// logical volume create window
include_partial('logicalvol/lvcreatewin');
// logical volume resize window
include_partial('logicalvol/lvresizewin');
include_partial('logicalvol/lvcreatesnapshotwin');
include_partial('logicalvol/lvclonewin');
include_partial('logicalvol/lvconvertwin');
?>
<script>
Ext.ns('Node.Storage');
/*
 * devices tree
 */
treeDEV = Ext.extend(Ext.ux.tree.TreeGrid, {
        id:this.id,
        enableDrag:true,
        enableDrop:false,
        columnWidth:0.3,
        border:false,
        enableSort: false, // disable sorting
        title: <?php echo json_encode(__('Physical Devices')) ?>,
        columns:[{
                header: __('Device'),
                width:150,
                dataIndex:'text'
                ,sortType: function(node) {
                    return node.attributes.text;
                }
            },
            {
                header: __('Device size'),
                width:100,
                dataIndex:'devicesize',
                align: 'center',
                sortType: function(node) {
                    var size = parseInt(node.attributes.devicesize);
                    return size;
                },
                tpl: new Ext.XTemplate('{devicesize:this.formatSize}', {
                    formatSize: function(v) {

                        if(v){
                          return Ext.util.Format.fileSize(v);
                        }
                        else return '&#160;';
                    }
                })
            }],

        listeners:{
            'beforeload':function(){this.body.mask(<?php echo json_encode(__('Please wait...')) ?>, 'x-mask-loading');},
            'load':function(){this.body.unmask();}
        },
        initComponent: function(){
            this.tools = [{
                id: 'gear',
                qtip: <?php echo json_encode(__('Scan physical devices')) ?>,
                scope:this,
                handler:this.pvscan
            },{
                id:'refresh',
                on:{
                    click: this.reload
                    ,scope:this
                }
            },{
                id:'help',
                qtip: __('Help'),
                handler:function(){
                    View.showHelp({
                        anchorid:'help-physical-vol',
                        autoLoad:{ params:'mod=physicalvol'},
                        title: <?php echo json_encode(__('Physical Volume Help')) ?>
                    });}
            }];

            // Check if we want the tree from cluster or datacenter
            var myparams = {};
            var myurl = '';

            if(this.level == 'cluster'){
                myparams = {'cid':this.tree_node_id};
                myurl = <?php echo json_encode(url_for('physicalvol/jsonClusterPhydiskTree'))?>;
            }else if(this.level == 'node'){
                myparams = {'nid':this.tree_node_id};
                myurl = <?php echo json_encode(url_for('physicalvol/jsonPhydiskTree'))?>;                
                this.node_id = this.tree_node_id;
            }
            

            this.loader = new Ext.tree.TreeLoader({
                dataUrl: myurl,
                baseParams: myparams
            });


            treeDEV.superclass.initComponent.call(this);


            // sort....
            new Ext.tree.TreeSorter(this, {
                folderSort: true,
                dir: "DESC",
                sortType: function(node) {
                    var size = parseInt(node.attributes.devicesize);
                    return size;
                }
            }); // end sort


            this.loader.on('loadexception', function(loader,node,resp){
                if(resp.status==401) return;

                var response = Ext.util.JSON.decode(resp.responseText);
                Ext.ux.Logger.error(response);

                var error_win = Ext.getCmp('storage-error');
                if(!error_win){
                    Ext.Msg.show({id:'storage-error',
                        title: <?php echo json_encode(__('Error!')) ?>,
                        buttons: Ext.MessageBox.OK,
                        msg: response,
                        icon: Ext.MessageBox.ERROR});
                }else if(!error_win.isVisible()) //not visible box
                    error_win.show();
            });// end load exception

            // on context click call onContextMenu
            this.on('contextmenu', this.onContextMenu,this);

        },
        reload : function(){
            this.root.reload();
        },
        onContextMenu : function(node, e){
            if(!this.menu){ // create context menu on first right click
                this.menu = new Ext.menu.Menu({
                    items: [{ref:'pv_create',
                            iconCls:'go-action',
                            text: <?php echo json_encode(__('Initialize physical volume')) ?>,
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
                        {ref:'pv_remove',
                            iconCls:'go-action',
                            text: <?php echo json_encode(__('Uninitialize physical volume')) ?>,
                            scope: this,
                            handler:this.pvremove
                        },
                        '-',{
                            ref:'pv_unregister',
                            id:'pv-unregister',
                            iconCls:'go-action',
                            text: <?php echo json_encode(__('Unregister physical device')) ?>,
                            scope: this,
                            handler:this.pvunregister
                        }]
                }); // end menu

                this.menu.on('hide', this.onContextHide, this);

            } //end if create menu

            if(this.ctxNode){
                this.ctxNode.ui.removeClass('x-node-ctx');
                this.ctxNode = null;
            }

            var childItems = this.root.firstChild.childNodes.length;

            if(node.isLeaf()){ //open context menu only if node is a leaf
                this.ctxNode = node;
                this.ctxNode.ui.addClass('x-node-ctx');
                this.menu.pv_create.setDisabled(node.attributes.cls.match(/dev-pv/) || node.attributes.cls.match(/-inc/) || childItems==0);
                this.menu.pv_remove.setDisabled(node.attributes.cls.match(/dev-pd/) || node.attributes.cls.match(/-inc/) || childItems==0);
                this.menu.pv_unregister.setDisabled( !node.attributes.cls.match(/-inc/) && (node.attributes.cls.match(/dev-pv/) || childItems==0) );
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

            if(this.level == 'cluster'){
                myparams = {'cid':this.tree_node_id, 'dev':ctx.attributes.device, 'level':this.level};
            }else if(this.level == 'node'){
                myparams = {'nid':this.tree_node_id, 'dev':ctx.attributes.device, 'level':this.level};
            }else{
                myparams = {'nid':this.node_id, 'dev':ctx.attributes.device}
            }
            if( ctx.attributes['uuid'] )
                myparams['uuid'] = ctx.attributes['uuid'];
            if( ctx.attributes['device'] )
                myparams['dev'] = ctx.attributes['device'];


            var conn = new Ext.data.Connection({
                listeners:{
                    // wait message.....
                    beforerequest:function(){
                        Ext.MessageBox.show({
                            title: <?php echo json_encode(__('Please wait...')) ?>,
                            msg: <?php echo json_encode(__('Initializing physical volume...')) ?>,
                            width:300,
                            wait:true
                        });
                    },// on request complete hide message
                    requestcomplete:function(){Ext.MessageBox.hide();}
                    ,requestexception:function(c,r,o){
                                        Ext.MessageBox.hide();
                                        Ext.Ajax.fireEvent('requestexception',c,r,o);}
                }
            });// end conn


            //send pvcreate SOAP request
            conn.request({
                url: <?php echo json_encode(url_for('physicalvol/jsonInit'))?>,
                params: myparams,
                //params: {'nid':this.node_id,'dev':ctx.attributes.device},
                scope:this,
                success: function(resp,opt){
                    var response = Ext.util.JSON.decode(resp.responseText);

                    Ext.ux.Logger.info(response['agent'],response['response']);

                    tree.root.reload(function(){

                        tree.getNodeById(ctx.parentNode.id).expand(false,false,
                                                function(){tree.getNodeById(ctx.id).select();}
                                                );

                    });


                },
                failure: function(resp,opt) {
                    var response = Ext.util.JSON.decode(resp.responseText);

                    Ext.Msg.show({
                        title: String.format(<?php echo json_encode(__('Error {0}')) ?>,response['agent']),
                        buttons: Ext.MessageBox.OK,
                        msg: String.format(<?php echo json_encode(__('Unable to initialize {0}!')) ?>,ctx.attributes.device),
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
            var centerPanel;
            if(this.level == 'cluster' || this.level == 'node'){
                centerPanel = new pvwin.resizeForm.Main(ctx.text,this.tree_node_id, this.level);
            }else{
                centerPanel = new pvwin.resizeForm.Main(ctx.text,this.node_id);
            }

//            var centerPanel = new pvwin.resizeForm.Main(ctx.text,this.node_id);
            centerPanel.load(ctx);

            var win = Ext.getCmp('pv-resize-win-'+ctx.text);

            if(!win){
                win = new Ext.Window({
                    id: 'pv-resize-win-'+ctx.text,
                    title: String.format(<?php echo json_encode(__('Resize physical volume {0}')) ?>,ctx.text),
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

            var myparams = {};
            var myurl = '';

//                params: {'nid':this.node_id,'dev':ctx.attributes.device},
            if(this.level == 'cluster'){
                myparams = {'cid':this.tree_node_id, 'dev':ctx.attributes.device, 'level':this.level};
            }else if(this.level == 'node'){
                myparams = {'nid':this.tree_node_id, 'dev':ctx.attributes.device, 'level':this.level};
            }else{
                myparams = {'nid':this.node_id, 'dev':ctx.attributes.device}
            }
            if( ctx.attributes['uuid'] )
                myparams['uuid'] = ctx.attributes['uuid'];
            if( ctx.attributes['device'] )
                myparams['dev'] = ctx.attributes['device'];

            var conn = new Ext.data.Connection({
                listeners:{
                    beforerequest:function(){
                        Ext.MessageBox.show({
                            title: <?php echo json_encode(__('Please wait...')) ?>,
                            msg: <?php echo json_encode(__('Uninitializing physical volume...')) ?>,
                            width:300,
                            wait:true
                        });

                    },
                    requestcomplete:function(){Ext.MessageBox.hide();}
                    ,requestexception:function(c,r,o){
                                        Ext.MessageBox.hide();
                                        Ext.Ajax.fireEvent('requestexception',c,r,o);}

                }
            });

            conn.request({
                url: <?php echo json_encode(url_for('physicalvol/jsonUninit'))?>,
                //params: {'nid':this.node_id,'dev':ctx.attributes.device},
                params: myparams,
                scope:this,
                success: function(resp,opt){
                    var response = Ext.util.JSON.decode(resp.responseText);

                    Ext.ux.Logger.info(response['agent'], response['response']);

                    tree.root.reload(function(){

                        tree.getNodeById(ctx.parentNode.id).expand(false,false,
                                                function(){tree.getNodeById(ctx.id).select();}
                                                );

                    });
                },
                failure: function(resp,opt) {
                    var response = Ext.util.JSON.decode(resp.responseText);

                    Ext.Msg.show({
                        title: String.format(<?php echo json_encode(__('Error {0}')) ?>,response['agent']),
                        buttons: Ext.MessageBox.OK,
                        msg: String.format(<?php echo json_encode(__('Unable to uninitialize {0}!')) ?>,ctx.attributes.device)+'<br>'+response['info'],
                        icon: Ext.MessageBox.ERROR});
                }

            });// END Ajax request

        }// end pvremove
        // scan new physical devices
        ,pvscan:function(){

            var win = Ext.getCmp('pv-scan-win');

            if(!win){
                var centerPanel;

                if(this.level == 'cluster' || this.level == 'node'){
                    centerPanel = new pvwin.scanForm.Main(this.tree_node_id, this.level);
                }else{
                    centerPanel = new pvwin.scanForm.Main(this.node_id);
                }
                
                centerPanel.on('updated',function(){
                    win.close();
                    this.reload();},this);

                win = new Ext.Window({
                    id: 'pv-scan-win',
                    title: <?php echo json_encode(__('Scan physical devices')) ?>,
                    width:430,
                    height:320,
                    modal:true,
                    iconCls: 'icon-window',
                    bodyStyle: 'padding:10px;',
                    border:true,
                    layout: 'fit',
                    items: [centerPanel]
                    ,tools: [{
                        id:'help',
                        qtip: __('Help'),
                        handler:function(){
                            View.showHelp({
                                anchorid:'help-pvol-add',
                                autoLoad:{ params:'mod=physicalvol'},
                                title: <?php echo json_encode(__('Physical Volume Help')) ?>
                            });
                        }
                    }]
                });

            }

            win.show();
            //gridDiskDevices.store.reload();
            centerPanel.load();

        }// end pvscan
        ,pvunregister: function(){
            var ctx = this.ctxNode;
            var tree = ctx.getOwnerTree();

            Ext.MessageBox.show({
                    title: <?php echo json_encode(__('Unregister physical volume')) ?>,
                    msg: String.format(<?php echo json_encode(__('Unregister physical volume {0} ?')) ?>,ctx.text),
                    buttons: Ext.MessageBox.YESNOCANCEL,
                    fn: function(btn){

                        if(btn=='yes'){
                            var myparams;
                            if(this.level == 'cluster'){
                                myparams = {'cid': this.tree_node_id, 'level': this.level};
                            } else if(this.level == 'node'){
                                myparams = {'nid': this.tree_node_id, 'level': this.level};
                            }else{
                                myparams = {'nid': this.tree_node_id};
                            }
                            if( ctx.attributes['uuid'] )
                                myparams['uuid'] = ctx.attributes['uuid'];
                            if( ctx.attributes['device'] )
                                myparams['device'] = ctx.attributes['device'];

                            var conn = new Ext.data.Connection({
                                listeners:{
                                    beforerequest:function(){
                                        Ext.MessageBox.show({
                                            title: <?php echo json_encode(__('Please wait...')) ?>,
                                            msg: <?php echo json_encode(__('Unregistering physical volume...')) ?>,
                                            width:300,
                                            wait:true
                                        });

                                    },
                                    requestcomplete:function(){Ext.MessageBox.hide();}
                                    ,requestexception:function(c,r,o){
                                                        Ext.MessageBox.hide();
                                                        Ext.Ajax.fireEvent('requestexception',c,r,o);}

                                }
                            });

                            //var ctxid = (this.level == 'cluster' || this.level == 'node') ? this.tree_node_id : this.node_id;

                            conn.request({
                                url: <?php echo json_encode(url_for('physicalvol/jsonUnregister'))?>,
                                params: myparams,
                                scope:this,
                                success: function(resp,opt){

                                    var response = Ext.util.JSON.decode(resp.responseText);
                                    Ext.ux.Logger.info(response['agent'],response['response']);
                                    tree.root.reload();

                                    /*Ext.getCmp('node-storage-vg-tree-'+ctxid).reload();
                                    Ext.getCmp('node-storage-dev-tree-'+ctxid).reload();
                                    Ext.getCmp('node-storage-lv-tree-'+ctxid).reload();*/
                                },
                                failure: function(resp,opt) {
                                    var response = Ext.util.JSON.decode(resp.responseText);
                                    Ext.Msg.show({
                                        title: String.format(<?php echo json_encode(__('Error {0}')) ?>,response['agent']),
                                        buttons: Ext.MessageBox.OK,
                                        msg: String.format(<?php echo json_encode(__('Unable to unregiste physical volume {0}')) ?>,ctx.text)+'<br>'+response['info'],
                                        icon: Ext.MessageBox.ERROR});
                                }
                            });// END Ajax request

                        }
                    },
                    scope:this,
                    icon: Ext.MessageBox.QUESTION
            });
        }
});





treeVG = Ext.extend(Ext.ux.tree.TreeGrid, {
        id:this.id,
        enableDrag:false,
        enableDrop:true,
        columnWidth:0.35,
        border:false,
        enableSort: false, // disable sorting
        title: <?php echo json_encode(__('Volume Groups')) ?>,
        columns:[{
                header: __('Volume Group'),
                width:150,
                dataIndex:'text'
                ,sortType: function(node) {
                    return node.attributes.text;
                }
            },{
                header: __('Size'),
                align: 'center',
                width:100,
                dataIndex:'prettysize',
                sortType: function(node) {
                    var size = parseInt(node.attributes.size);
                    return size;
                },
                tpl: new Ext.XTemplate('{prettysize:this.formatVGSize}', {
                    formatVGSize: function(v) {

                        if(v) return Ext.util.Format.fileSize(v);
                        else return '&#160;';
                    }
                })
            }
            ,{
                header: __('Free size'),
                align: 'center',
                width:100,
                dataIndex:'freesize',
                sortType: function(node) {
                    var size = parseInt(node.attributes.freesize);
                    return size;
                },
                tpl: new Ext.XTemplate('{freesize:this.formatVGSize}'
                , {
                    formatVGSize: function(v) {

                        if(v) return Ext.util.Format.fileSize(v);
                        else return '&#160;';
                    }
                }
            )
            }
        ],
        listeners:{
            'beforeload':function(){this.body.mask(<?php echo json_encode(__('Please wait...')) ?>, 'x-mask-loading');},
            'load':function(){this.body.unmask();}
        },
        initComponent: function(){


            this.tools = [{
                id: 'gear',
                qtip: <?php echo json_encode(__('Scan volume groups')) ?>,
                scope:this,
                handler:this.vgscan
                },{
                id:'refresh',
                on:{
                    click: this.reload
                    ,scope:this
                }
            },{
                id:'help',
                qtip: __('Help'),
                handler:function(){
                    View.showHelp({
                        anchorid:'help-vol-group',
                        autoLoad:{ params:'mod=volgroup'},
                        title: <?php echo json_encode(__('Volume Groups Help')) ?>
                    });}
            }];

            // Check if we want the tree from cluster or datacenter
            var myparams= {};
            var myurl = '';

            if(this.level == 'cluster'){
                myparams = {'cid':this.tree_node_id};
                myurl = <?php echo json_encode(url_for('volgroup/jsonClusterVgsTree'))?>;
            }else if(this.level == 'node'){
                myparams = {'nid':this.tree_node_id};
                myurl = <?php echo json_encode(url_for('volgroup/jsonVgsTree'))?>;
            }


            this.loader = new Ext.tree.TreeLoader({
                dataUrl: myurl,
                baseParams: myparams
            });

            treeVG.superclass.initComponent.call(this);


            // sort....
            new Ext.tree.TreeSorter(this, {
                folderSort: true,
                dir: "DESC",
                sortType: function(node) {
                    var size = parseInt(node.attributes.size);
                    return size;
                }
            }); // end sort


            this.loader.on('loadexception', function(loader,node,resp){
                if(resp.status==401) return;
                var response = Ext.util.JSON.decode(resp.responseText);
                Ext.ux.Logger.error(response);

                var error_win = Ext.getCmp('storage-error');
                if(!error_win){
                    Ext.Msg.show({id:'storage-error',
                        title: <?php echo json_encode(__('Error!')) ?>,
                        buttons: Ext.MessageBox.OK,
                        msg: response,
                        icon: Ext.MessageBox.ERROR});
                }else if(!error_win.isVisible()) //not visible box
                    error_win.show();
            });// end load exception

            // on context click call onContextMenu
            this.on('contextmenu', this.onContextMenu,this);


            this.on('nodedragover', function(e){
                var n = e.dropNode;
                e.tree.expandAll();
                return this.canMoveTo(e, n, this.dev_id);
            });

            this.on('beforenodedrop', function(e){
                var n = e.dropNode;
                // canMoveTo(destination node,source node)
                if(this.canMoveTo(e, n, this.dev_id)){
                    var copy = new Ext.tree.TreeNode(
                    Ext.apply({allowDelete:true,expanded:true},n.attributes));
                    e.dropNode = copy;

                    return true;
                }
                return false;
            },this);// end beforenodedrop event


            this.on('nodedrop', function(e){
                var tree = e.target.getOwnerTree();
                var n = e.dropNode;
                var pvs = {'pv1':n.attributes.device};

                var nid = this.node_id;
                var cid = this.tree_node_id;
                var n_level = this.level;
                var vg = e.target.id;

                Ext.Msg.show({
                            title: <?php echo json_encode(__('Add physical volume to volume group')) ?>,
                            buttons: Ext.MessageBox.YESNOCANCEL,
                            msg: String.format(<?php echo json_encode(__('Add physical volume {0} to volume group {1} ?')) ?>,n.attributes.text,e.target.attributes.text),
                            icon: Ext.MessageBox.WARNING,
                            fn: function(btn){
                                if (btn == 'yes'){
                                    // everthing ok
                                    // send data to virt agent

                                    var conn = new Ext.data.Connection({
                                        listeners:{
                                            beforerequest:function(){

                                                Ext.MessageBox.show({
                                                    title: <?php echo json_encode(__('Please wait...')) ?>,
                                                    msg: <?php echo json_encode(__('Adding physical volume to volume group...')) ?>,
                                                    width:300,
                                                    wait:true
                                                });

                                            },
                                            requestcomplete:function(){Ext.MessageBox.hide();}
                                            ,requestexception:function(c,r,o){
                                                            Ext.MessageBox.hide();
                                                            Ext.Ajax.fireEvent('requestexception',c,r,o);}

                                        }
                                    });// end conn

                                    var myparams;
                                    if(n_level == 'cluster'){
                                        myparams = {'cid':cid,'pvs':Ext.encode(pvs),'vg':vg, 'level': n_level};
                                    }else if(this.level == 'node'){
                                        myparams = {'nid':nid,'pvs':Ext.encode(pvs),'vg':vg, 'level': n_level};
                                    }else{
                                        myparams = {'nid':nid,'pvs':Ext.encode(pvs),'vg':vg};
                                    }

                                    conn.request({
                                        url: <?php echo json_encode(url_for('volgroup/jsonUpdate'))?>,
                                        params: myparams,
                                        scope:this,
                                        success: function(resp,opt){
                                            var response = Ext.util.JSON.decode(resp.responseText);
                                            Ext.ux.Logger.info(response['agent'], response['response']);


                                            tree.root.reload(function()
                                                            {tree.getNodeById(vg).expand();}
                                                        );


                                        },
                                        failure: function(resp,opt) {
                                            var response = Ext.util.JSON.decode(resp.responseText);

                                            //n.remove();
                                            tree.root.reload(function()
                                                            {tree.getNodeById(vg).expand();}
                                                        );

                                            //Ext.ux.Logger.error(response['error']);
                                            Ext.Msg.show({
                                                title: String.format(<?php echo json_encode(__('Error {0}')) ?>,response['agent']),
                                                buttons: Ext.MessageBox.OK,
                                                msg: String.format(<?php echo json_encode(__('Unable to extend {0}')) ?>,vg),
                                                icon: Ext.MessageBox.ERROR});

                                        }
                                    });// END Ajax request
                                } else {//END button==yes
                                    //Ext.getCmp('view-nodes-panel').reload();
                                    tree.root.reload(function()
                                                    {tree.getNodeById(vg).expand();}
                                                );
                                }
                            }
                            });

            },this);


        },
        reload : function(){
            this.root.reload();
        },
        hasNode:function(t, n){

            var tree = t.getOwnerTree();
            var exists = tree.getNodeById(n.id);

            if(exists) return true;
            // tree.expandAll();
            // var root = tree.getRootNode();
            // alert(check_exists(root,n));
            //  var root = tree.getRootNode();

            // alert(tree.getRootNode().findChild('id', n.id));

            return (t.attributes.type == n.attributes.storage_type && t.findChild('id', n.id)) ||
                (t.leaf === true && t.parentNode.findChild('id', n.id));
        },
        freespace: function(){ //returns an array with the lvms free space
            var res = new Array();
            var childs = this.root.childNodes;
            for(idx in childs){
                var child = childs[idx];
                if(typeof(child) == 'object'){
                    res[idx] = parseInt(child.attributes.freesize);
                }else{
                    res[idx] = 0;
                }
            }
            return res;
        },
        // check if source node is initiliazed has pv
        isInit:function(n){ return (n.attributes.type == 'dev-pv' );},
        // canMoveTo(target node, source node)
        canMoveTo:function(tn, sn, fP){
            var a = tn.target.attributes;
            if(!this.isInit(sn)){
                Ext.Msg.show({
                    title: <?php echo json_encode(__('Error!')) ?>,
                    buttons: Ext.MessageBox.OK,
                    msg: String.format(<?php echo json_encode(__('Physical volume {0} not initialized!')) ?>,sn.text),
                    icon: Ext.MessageBox.ERROR});
            }

            return (sn.getOwnerTree()).id == fP && this.isInit(sn) && !this.hasNode(tn.target, sn) &&
                ((tn.point == 'append' && a.cls == 'vg' && a.type == sn.attributes.storage_type ) );
        },
        onContextMenu : function(node, e){
            if(!this.menu){ // create context menu on first right click
                this.menu = new Ext.menu.Menu({
                    items: [
                        {
                            id:'vg-create',
                            iconCls:'go-action',
                            text: <?php echo json_encode(__('Add volume group')) ?>,
                            scope: this,
                            handler:this.vgcreate
                        },{
                            id:'vg-remove',
                            iconCls:'go-action',
                            text: <?php echo json_encode(__('Remove volume group')) ?>,
                            scope: this,
                            handler:this.vgremove
                        },
                        '-',{
                            id:'vg-remove-pv',
                            iconCls:'go-action',
                            text: <?php echo json_encode(__('Remove physical volume')) ?>,
                            scope: this,
                            handler:this.vgreduce
                        },
                        '-',{
                            id:'vg-unregister',
                            iconCls:'go-action',
                            text: <?php echo json_encode(__('Unregister volume group')) ?>,
                            scope: this,
                            handler:this.vgunregister
                        }
                        ]
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
                //this.menu.items.get('vg-create').setDisabled(true);
                this.menu.items.get('vg-remove-pv').setDisabled(false);
                this.menu.items.get('vg-remove').setDisabled(true);
                this.menu.items.get('vg-unregister').setDisabled(true);

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
                
                //this.menu.pv_unregister.setDisabled( !node.attributes.cls.match(/-inc/) && (node.attributes.cls.match(/dev-pv/) || childItems==0) );
                
                this.menu.items.get('vg-unregister').setDisabled(node.attributes.inuse);
                if( node.attributes.inuse ){
                    this.menu.items.get('vg-unregister').setTooltip({text: <?php echo json_encode(__('Cannot unregister the volume group with logical volumes in use by virtual server')) ?>});
                }
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

            //alert("level"+this.level+this.tree_node_id);

            //uses vgcreatewin
            var centerPanel;

            if(this.level == 'cluster' || this.level == 'node'){
                centerPanel = new vgwin.createForm.Main(this.tree_node_id, this.level);
            }else{
                centerPanel = new vgwin.createForm.Main(this.node_id);
            }
            var win = Ext.getCmp('vg-create-win');

            /*
             * after send create vg.....close window and reload tree data
             */
            centerPanel.on('onCreate',function(){
                win.close();
                this.reload();
            },this);

            if(!win){
                win = new Ext.Window({
                    id: 'vg-create-win',
                    title: <?php echo json_encode(__('Add volume group')) ?>,
                    width:550,
                    height:350,
                    iconCls: 'icon-window',
                    bodyStyle: 'padding:10px;',
                    shim:false,
                    border:true,
                    constrainHeader:true,
                    layout: 'fit',
                    items: [centerPanel]
                    ,tools: [{
                        id:'help',
                        qtip: __('Help'),
                        handler:function(){
                            View.showHelp({
                                anchorid:'help-vol-group-add',
                                autoLoad:{ params:'mod=volgroup'},
                                title: <?php echo json_encode(__('Volume Groups Help')) ?>
                            });
                        }
                    }]
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

            var nid = this.node_id;
            var cid = this.tree_node_id;
            var n_level = this.level;

            Ext.MessageBox.show({
                    title: <?php echo json_encode(__('Remove physical volume')) ?>,
                    msg: String.format(<?php echo json_encode(__('Remove {0} from volume group {1} ?')) ?>,ctx.attributes.pv,ctx.parentNode.id),
                    buttons: Ext.MessageBox.YESNOCANCEL,
                    fn: function(btn){

                        if(btn=='yes'){

                            var conn = new Ext.data.Connection({
                                listeners:{
                                    // wait message.....
                                    beforerequest:function(){

                                        Ext.MessageBox.show({
                                            title: <?php echo json_encode(__('Please wait...')) ?>,
                                            msg: <?php echo json_encode(__('Removing physical volume...')) ?>,
                                            width:300,
                                            wait:true
                                        });
                                    },// on request complete hide message
                                    requestcomplete:function(){Ext.MessageBox.hide();}
                                    ,requestexception:function(c,r,o){
                                                        Ext.MessageBox.hide();
                                                        Ext.Ajax.fireEvent('requestexception',c,r,o);}
                                }
                            });// end conn

                            var myparams;
                            if(n_level == 'cluster'){
                                myparams = {'cid':cid,'vg':ctx.parentNode.id,'pvs': Ext.encode(pvs), 'level': n_level};
                            }else if(n_level == 'node'){
                                myparams = {'nid':nid,'vg':ctx.parentNode.id,'pvs': Ext.encode(pvs), 'level': n_level};
                            }else{
                                myparams = {'nid':nid,'vg':ctx.parentNode.id,'pvs': Ext.encode(pvs)};
                            }

                            //send vgreduce SOAP request
                            conn.request({
                                url: <?php echo json_encode(url_for('volgroup/jsonReduce'))?>,
                                params: myparams,
                                scope:this,
                                success: function(resp,opt){
                                    var response = Ext.util.JSON.decode(resp.responseText);
                                    Ext.ux.Logger.info(response['agent'],response['response']);

                                    tree.root.reload(function(){
                                        tree.getNodeById(ctx.parentNode.id).expand();}
                                    );

                                },
                                failure: function(resp,opt) {
                                    var response = Ext.util.JSON.decode(resp.responseText);

                                    Ext.Msg.show({
                                        title: String.format(<?php echo json_encode(__('Error {0}')) ?>,response['agent']),
                                        buttons: Ext.MessageBox.OK,
                                        msg: String.format(<?php echo json_encode(__('Unable to remove {0} from volume group {1}')) ?>,ctx.attributes.text,ctx.parentNode.id)+'<br>'+response['info'],
                                        icon: Ext.MessageBox.ERROR});
                                }
                            });// END Ajax request




                        }
                    },
                    scope:this,
                    icon: Ext.MessageBox.QUESTION
            });

        },
        vgremove:function(){
            var ctx = this.ctxNode;
            var tree = ctx.getOwnerTree();


            Ext.MessageBox.show({
                    title: <?php echo json_encode(__('Remove volume group')) ?>,
                    msg: String.format(<?php echo json_encode(__('Remove volume group {0} ?')) ?>,ctx.id),
                    buttons: Ext.MessageBox.YESNOCANCEL,
                    fn: function(btn){

                        if(btn=='yes'){
                            var myparams;
                            if(this.level == 'cluster'){
                                myparams = {'cid': this.tree_node_id, 'level': this.level, 'vg':ctx.id};
                            }else if(this.level == 'node'){
                                myparams = {'nid': this.tree_node_id, 'level': this.level, 'vg':ctx.id};
                            }else{
                                myparams = {'nid': this.tree_node_id, 'vg':ctx.id};
                            }

                            var conn = new Ext.data.Connection({
                                listeners:{
                                    beforerequest:function(){
                                        Ext.MessageBox.show({
                                            title: <?php echo json_encode(__('Please wait...')) ?>,
                                            msg: <?php echo json_encode(__('Removing volume group...')) ?>,
                                            width:300,
                                            wait:true
                                        });

                                    },
                                    requestcomplete:function(){Ext.MessageBox.hide();}
                                    ,requestexception:function(c,r,o){
                                                        Ext.MessageBox.hide();
                                                        Ext.Ajax.fireEvent('requestexception',c,r,o);}

                                }
                            });

                            conn.request({
                                url: <?php echo json_encode(url_for('volgroup/jsonRemove'))?>,
                                params: myparams,  //{'nid':this.node_id,'vg':ctx.id },
                                scope:this,
                                success: function(resp,opt){

                                    var response = Ext.util.JSON.decode(resp.responseText);
                                    Ext.ux.Logger.info(response['agent'],response['response']);
                                    tree.root.reload();


                                },
                                failure: function(resp,opt) {
                                    var response = Ext.util.JSON.decode(resp.responseText);
                                    Ext.Msg.show({
                                        title: String.format(<?php echo json_encode(__('Error {0}')) ?>,response['agent']),
                                        buttons: Ext.MessageBox.OK,
                                        msg: String.format(<?php echo json_encode(__('Unable to remove volume group {0}')) ?>,ctx.id)+'<br>'+response['info'],
                                        icon: Ext.MessageBox.ERROR});
                                }
                            });// END Ajax request

                        }
                    },
                    scope:this,
                    icon: Ext.MessageBox.QUESTION
            });
        }
        ,vgscan:function(){

            var win = Ext.getCmp('vg-scan-win');

            if(!win){
                var centerPanel;

                if(this.level == 'cluster' || this.level == 'node'){
                    centerPanel = new Volgroup.Scan.Main(this.tree_node_id, this.level);
                }else{
                    centerPanel = new Volgroup.Scan.Main(this.node_id);
                }
                
                var ctxid = (this.level == 'cluster' || this.level == 'node') ? this.tree_node_id : this.node_id;
                centerPanel.on('updated',function(){
                    win.close();
                    //this.reload();
                    Ext.getCmp('node-storage-vg-tree-'+ctxid).reload();
                    Ext.getCmp('node-storage-dev-tree-'+ctxid).reload();
                    Ext.getCmp('node-storage-lv-tree-'+ctxid).reload();
                    },this);

                win = new Ext.Window({
                    id: 'vg-scan-win',
                    title: <?php echo json_encode(__('Scan volume groups')) ?>,
                    width:430,
                    height:420,
                    modal:true,
                    iconCls: 'icon-window',
                    bodyStyle: 'padding:10px;',
                    border:true,
                    layout: 'fit',
                    items: [centerPanel]
                    ,tools: [{
                        id:'help',
                        qtip: __('Help'),
                        handler:function(){
                            View.showHelp({
                                anchorid:'help-vol-group',
                                autoLoad:{ params:'mod=volgroup'},
                                title: <?php echo json_encode(__('Volume Groups Help')) ?>
                            });
                        }
                    }]
                });
            }

            win.show();
            centerPanel.load();

        }// end vgscan
        ,vgunregister: function(){
            var ctx = this.ctxNode;
            var tree = ctx.getOwnerTree();

            Ext.MessageBox.show({
                    title: <?php echo json_encode(__('Unregister volume group')) ?>,
                    msg: String.format(<?php echo json_encode(__('Unregister volume group {0} ?')) ?>,ctx.id),
                    buttons: Ext.MessageBox.YESNOCANCEL,
                    fn: function(btn){

                        if(btn=='yes'){
                            var myparams;
                            if(this.level == 'cluster'){
                                myparams = {'cid': this.tree_node_id, 'level': this.level, 'vg':ctx.id};
                            }else if(this.level == 'node'){
                                myparams = {'nid': this.tree_node_id, 'level': this.level, 'vg':ctx.id};
                            }else{
                                myparams = {'nid': this.tree_node_id, 'vg':ctx.id};
                            }

                            var conn = new Ext.data.Connection({
                                listeners:{
                                    beforerequest:function(){
                                        Ext.MessageBox.show({
                                            title: <?php echo json_encode(__('Please wait...')) ?>,
                                            msg: <?php echo json_encode(__('Unregistering volume group...')) ?>,
                                            width:300,
                                            wait:true
                                        });

                                    },
                                    requestcomplete:function(){Ext.MessageBox.hide();}
                                    ,requestexception:function(c,r,o){
                                                        Ext.MessageBox.hide();
                                                        Ext.Ajax.fireEvent('requestexception',c,r,o);}

                                }
                            });

                            var ctxid = (this.level == 'cluster' || this.level == 'node') ? this.tree_node_id : this.node_id;

                            conn.request({
                                url: <?php echo json_encode(url_for('volgroup/jsonUnregister'))?>,
                                params: myparams,  //{'nid':this.node_id,'vg':ctx.id },
                                scope:this,
                                success: function(resp,opt){

                                    var response = Ext.util.JSON.decode(resp.responseText);
                                    Ext.ux.Logger.info(response['agent'],response['response']);
                                    tree.root.reload();

                                    Ext.getCmp('node-storage-vg-tree-'+ctxid).reload();
                                    Ext.getCmp('node-storage-dev-tree-'+ctxid).reload();
                                    Ext.getCmp('node-storage-lv-tree-'+ctxid).reload();

                                },
                                failure: function(resp,opt) {
                                    var response = Ext.util.JSON.decode(resp.responseText);
                                    Ext.Msg.show({
                                        title: String.format(<?php echo json_encode(__('Error {0}')) ?>,response['agent']),
                                        buttons: Ext.MessageBox.OK,
                                        msg: String.format(<?php echo json_encode(__('Unable to unregiste volume group {0}')) ?>,ctx.id)+'<br>'+response['info'],
                                        icon: Ext.MessageBox.ERROR});
                                }
                            });// END Ajax request

                        }
                    },
                    scope:this,
                    icon: Ext.MessageBox.QUESTION
            });
        }
});


treeLV = Ext.extend(Ext.ux.tree.TreeGrid, {
        id:this.id,
        enableDD:false,
        //enableSort:false,
        defaultSortable: true,
        border:false,
        columnWidth:0.35,
        title: <?php echo json_encode(__('Logical Volumes')) ?>,
        columns:[{
                header: __('Logical Volume'),
                width:150,
                dataIndex:'text'
                ,sortType: function(node) {
                    return node.attributes.text;
                }
            },
            {
                header: __('Volume Group'),
                width:100,
                align: 'center',
                dataIndex:'vg'
                ,sortType: function(node) {
                    return node.attributes.vg + " " + node.attributes.text;
                }
            },
            {
                header: __('Server'),
                width:100,
                align: 'center',
                dataIndex:'vm_name'
                ,sortType: function(node) {
                    return node.attributes.vm_name + " " + node.attributes.text;
                }
            },
            {
                header: __('Format'),
                width:100,
                align: 'center',
                dataIndex:'format'
                ,sortType: function(node) {
                    return node.attributes.format + " " + node.attributes.text;
                }
            },
            {
                header: __('Size'),
                align: 'center',
                width:100,
                dataIndex:'prettysize',
                sortType: function(node) {
                    var size = parseInt(node.attributes.size);
                    return size;
                },
                tpl: new Ext.XTemplate('{prettysize:this.formatLVSize}', {
                    formatLVSize: function(v) {

                        if(v) return Ext.util.Format.fileSize(v);
                        else return '&#160;';
                    }
                })
            }]
        ,listeners:{
            'beforeload':function(){this.body.mask(<?php echo json_encode(__('Please wait...')) ?>, 'x-mask-loading');},
            'load':function(){this.body.unmask();}
            ,'contextmenu': function(node, evt_obj){
//                var lv_size = parseInt(node.attributes.size);
//
//                // check if there are enouf space on any volume groups to perform a clone
//                var ctxid = (this.level == 'cluster' || this.level == 'node') ? this.tree_node_id : this.node_id;
//                var vg_tree = Ext.getCmp('node-storage-vg-tree-'+ctxid);
//                var res = vg_tree.freespace();
//                var disable_clone = true;
//                for(i in res){
//                    if(res[i] >= lv_size){
//                        disable_clone = false;
//                    }
//                }
//                treeLV.menu.lvclone.setDisabled(disable_clone);

            }
        },
        initComponent: function(){
            this.tools = [{
                id: 'gear',
                qtip: <?php echo json_encode(__('Scan logical volumes')) ?>,
                scope:this,
                handler:this.lvscan
                },{
                id:'refresh',
                on:{
                    click: this.reload
                    ,scope:this
                }
            },{
                id:'help',
                qtip: __('Help'),
                handler:function(){
                    View.showHelp({
                        anchorid:'help-lvol',
                        autoLoad:{ params:'mod=logicalvol'},
                        title: <?php echo json_encode(__('Logical Volume Help')) ?>
                    });
               }
            },{
                id: 'search',
                qtip: <?php echo json_encode(__('Search')) ?>,
                scope:this,
                handler: function(e, toolEl, panel, tc){
                    var tbar = panel.getTopToolbar();

                    if(tbar.isVisible()){
                        tbar.hide();
                    }else{
                        tbar.show();
                    }
                    panel.doLayout();
                }
            }];

            this.tbar = new Ext.Toolbar({
            //id: 'topbar-search-lvs',
            hidden: true
            ,items:       
            [
//            '->',
            {   
                cls: 'version_box',
                xtype: 'tbtext',
                text: <?php echo json_encode(__('Search')) ?>
            },{
                xtype: 'textfield',
                fieldLabel: <?php echo json_encode(__('Search')) ?>,
                //name: id+'_primarydns',
                maxLength: 15,
                //vtype:'ip_addr',
                //allowBlank:false,
                disabled:false,
                width:100,
                scope:this,
                listeners:{
                    specialkey:{
                        scope:this,
                        fn:function(field,e){

                            /* Influence with a large hammer */
                            try {
                                search_on_nodetree(field, e);
                            } catch (e) {
                            }
                        },
                        render: function(c) {
                            Ext.QuickTips.register({
                                target: c.getEl(),
                                text: <?php echo json_encode(__('Press enter to search')) ?>
                            });
                        }
                    }
                }    
            }
        ]});
            // Check if we want the tree from cluster or datacenter
            var myparams = {};
            var myurl = '';

            if(this.level == 'cluster'){
                myparams = {'cid':this.tree_node_id};
                myurl = <?php echo json_encode(url_for('logicalvol/jsonClusterLvsTree'))?>;
            }else if(this.level == 'node'){
                myparams = {'nid':this.tree_node_id};
                myurl = <?php echo json_encode(url_for('logicalvol/jsonLvsTree'))?>;
            }


            this.loader = new Ext.tree.TreeLoader({
                dataUrl: myurl,
                baseParams: myparams
            });


            treeLV.superclass.initComponent.call(this);




            this.loader.on('loadexception', function(loader,node,resp){
                if(resp.status==401) return;

                var response = Ext.util.JSON.decode(resp.responseText);
                Ext.ux.Logger.error(response);

                var error_win = Ext.getCmp('storage-error');
                if(!error_win){
                    Ext.Msg.show({id:'storage-error',
                        title: <?php echo json_encode(__('Error!')) ?>,
                        buttons: Ext.MessageBox.OK,
                        msg: response,
                        icon: Ext.MessageBox.ERROR});
                }else if(!error_win.isVisible()) //not visible box
                    error_win.show();
            });// end load exception

            // on context click call onContextMenu
            this.on('contextmenu', this.onContextMenu,this);

        },
        reload : function(){
            this.root.reload();
        },
        onContextMenu : function(node,e){
            if(!node.disabled) node.select();

            if(!this.menu){ // create context menu on first right click
                this.menu = new Ext.ux.TooltipMenu({
                    items: [{
                                iconCls:'go-action',
                                text: <?php echo json_encode(__('Add logical volume')) ?>,
                                ref:'lvadd',
                                scope: this,
                                handler:this.lvcreate
                            },
                            {
                                iconCls:'go-action',
                                ref:'lvresize',
                                text: <?php echo json_encode(__('Resize logical volume')) ?>,
                                scope: this,
                                handler:this.lvresize
                            },
                            {
                                iconCls:'go-action',
                                ref:'lvremove',
                                text: <?php echo json_encode(__('Remove logical volume')) ?>,
                                scope: this,
                                handler:this.lvremove
                            }
                            ,'-',
                            {
                                iconCls:'go-action',
                                ref: 'lvsnapshot',
                                text: <?php echo json_encode(__('Take snapshot')) ?>,
                                hidden: true,
                                scope: this,
                                handler:this.lvsnapshot
                            },{
                                iconCls:'go-action',
                                ref: 'lvsnapshot_revert',
                                text: <?php echo json_encode(__('Revert snapshot')) ?>,
                                hidden: true,
                                scope: this,
                                handler:this.lvsnapshotrevert
                            },
                            {
                                iconCls:'go-action',
                                ref: 'lvclone',
                                text: <?php echo json_encode(__('Clone')) ?>,
                                scope: this,
//                                tooltip: {text: <?php echo json_encode(__('Enabled if virtual server is not running and if any volume groups have enouf space.')) ?>},
                                handler:this.lvclone
                            },
                            {
                                iconCls:'go-action',
                                ref: 'lvconvert',
                                text: <?php echo json_encode(__('Convert')) ?>,
                                scope: this,
                                handler:this.lvconvert
                            },
                            '-',{
                                ref:'lv_unregister',
                                id:'lv-unregister',
                                iconCls:'go-action',
                                text: <?php echo json_encode(__('Unregister logical volume')) ?>,
                                scope: this,
                                handler:this.lvunregister
                            }
                            ]
                });

                this.menu.on('hide', this.onContextHide, this);

            } else {    // initialize
                this.menu.lvadd.setDisabled(false); 
                this.menu.lvadd.clearTooltip('');
                this.menu.lvresize.setDisabled(false); 
                this.menu.lvresize.clearTooltip('');
                this.menu.lvremove.setDisabled(false); 
                this.menu.lvremove.clearTooltip('');
                this.menu.lvsnapshot.setDisabled(false); 
                this.menu.lvsnapshot.clearTooltip('');
                this.menu.lvsnapshot_revert.setDisabled(false); 
                this.menu.lvsnapshot_revert.clearTooltip('');
                this.menu.lvclone.setDisabled(false); 
                this.menu.lvclone.clearTooltip('');
                this.menu.lvconvert.setDisabled(false); 
                this.menu.lvconvert.clearTooltip('');
                this.menu.lv_unregister.setDisabled(false); 
                this.menu.lv_unregister.clearTooltip('');
            } //end if create menu

            if(this.ctxNode){
                if(this.ctxNode.ui) this.ctxNode.ui.removeClass('x-node-ctx');
                this.ctxNode = null;
            }
            if( !node.attributes.type ){
                this.menu.lvresize.setDisabled(true); 
                this.menu.lvresize.clearTooltip('');
                this.menu.lvremove.setDisabled(true); 
                this.menu.lvremove.clearTooltip('');
            }

            if( node.attributes.havesnapshots &&
                node.attributes.havesnapshots_inuse_inrunningvm ){
                this.menu.lvremove.setTooltip({text: <?php echo json_encode(__('Can\'t remove logical volume with snapshots in use by any virtual server')) ?>});
                this.menu.lvremove.setDisabled(true);
            } else if(!Ext.isEmpty(node.attributes.vm_name)){
                this.menu.lvremove.setDisabled(true);
                this.menu.lvremove.setTooltip({text: <?php echo json_encode(__('Enabled if not in use by any virtual server')) ?>});
            }

            /*if(node.attributes.vm_state=='running'){
                this.menu.lvresize.setTooltip({text: String.format(<?php echo json_encode(__('Enabled if virtual server {0} is not running')) ?>,node.attributes.vm_name)});
                this.menu.lvresize.setDisabled(true);
                this.menu.lvclone.setTooltip({text: <?php echo json_encode(__('Enabled if virtual server is not running and if any volume groups have enouf space.')) ?>});
                this.menu.lvclone.setDisabled(true);
            } else if(node.attributes.havesnapshots){*/
            if(node.attributes.havesnapshots){
                this.menu.lvresize.setTooltip({text: String.format(<?php echo json_encode(__('Can\'t resize volumes if has snapshots')) ?>,node.attributes.vm_name)});
                this.menu.lvresize.setDisabled(true);
            }

            if( node.attributes.format!='lvm' && !node.attributes.snapshot ) {
                this.menu.lvsnapshot.setTooltip({text: String.format(<?php echo json_encode(__('Enabled if volume support snapshot.')) ?>)});
                this.menu.lvsnapshot.setDisabled(true);
                this.menu.lvsnapshot_revert.setDisabled(true);
            } else if( node.attributes.snapshot ){
                this.menu.lvsnapshot.setTooltip({text: String.format(<?php echo json_encode(__('Enabled if volume is not snapshot.')) ?>)});
                this.menu.lvsnapshot.setDisabled(true);
                if( (!Ext.isEmpty(node.attributes.vm_name) && (node.attributes.vm_state=='running')) || 
                        (!Ext.isEmpty(node.parentNode.attributes.vm_name) && (node.parentNode.attributes.vm_state=='running')) ){
                    this.menu.lvsnapshot_revert.setDisabled(true);
                    this.menu.lvsnapshot_revert.setTooltip({text: <?php echo json_encode(__('Enabled if not in use by any running virtual server')) ?>});
                } else {
                    this.menu.lvsnapshot_revert.setDisabled(false);
                }
            } else {
                this.menu.lvsnapshot_revert.setDisabled(true);
            }

            if(node.disabled){
                var qtip = node.attributes['qtip'];
                this.menu.lvremove.setTooltip({text:qtip});
                this.menu.lvremove.setDisabled(true);

                this.menu.lvresize.setTooltip({text:qtip});
                this.menu.lvresize.setDisabled(true);

                this.menu.lvsnapshot.setTooltip({text:qtip});
                this.menu.lvsnapshot.setDisabled(true);
                this.menu.lvsnapshot_revert.setTooltip({text:qtip});
                this.menu.lvsnapshot_revert.setDisabled(true);

                this.menu.lvclone.setTooltip({text: <?php echo json_encode(__('Enabled if virtual server is not running and if any volume groups have enouf space.')) ?>});
                this.menu.lvclone.setDisabled(true);
            }

            if(this.level == 'cluster'){

                //if in cluster context, disable create snapshot option
                this.menu.lvsnapshot.setDisabled(true);
                this.menu.lvsnapshot.setTooltip({text: String.format(<?php echo json_encode(__('Snapshots not supported in cluster context.')) ?>)});

                this.menu.lvsnapshot_revert.setDisabled(true);
            }

            if( node.attributes.storagetype != 'local' ) {
                this.menu.lvsnapshot.setDisabled(true);
                this.menu.lvsnapshot.setTooltip({text: String.format(<?php echo json_encode(__('Snapshots not supported in shared storage.')) ?>)});

                this.menu.lvsnapshot_revert.setDisabled(true);
            }
            if( !node.attributes.inconsistent ){
                this.menu.lv_unregister.setDisabled(true);
            }

            this.fireEvent('checkContextItems',this.menu);

            //if(node.isLeaf()){ //open context menu only if node is a leaf
            this.ctxNode = node;
            this.ctxNode.ui.addClass('x-node-ctx');

            this.menu.showAt(e.getXY());
            //}

            var disable_clone = this.menu.lvclone.disabled;
            if(!disable_clone){
                var lv_size = parseInt(node.attributes.size);
                disable_clone = true;

                // check if there are enouf space on any volume groups to perform a clone
                var ctxid = (this.level == 'cluster' || this.level == 'node') ? this.tree_node_id : this.node_id;
                var vg_tree = Ext.getCmp('node-storage-vg-tree-'+ctxid);
                var res = vg_tree.freespace();
    
                for(i in res){
                    if(res[i] >= lv_size){
                        disable_clone = false;
                    }
                }
                this.menu.lvclone.setDisabled(disable_clone);
                if(disable_clone)
                    this.menu.lvclone.setTooltip({text: <?php echo json_encode(__('Enabled if virtual server is not running and if any volume groups have enough space.')) ?>});
                else
                    this.menu.lvclone.clearTooltip();
            }
        },
        onContextHide : function(){
            if(this.ctxNode){
                if(this.ctxNode.ui) this.ctxNode.ui.removeClass('x-node-ctx');
                this.ctxNode = null;
            }
        },
        // create logical volume
        // call: open template logicalvol/_lvcreatewin
        // see _lvcreatewin
        lvcreate:function(){
            //uses lvcreatewin


            //alert("level"+this.level+this.tree_node_id);

            var win = Ext.getCmp('lv-create-win');

            if(!win){           
                var centerPanel;
                if(this.level == 'cluster' || this.level == 'node'){
                    centerPanel = new lvwin.createForm.Main(this.tree_node_id, this.level);
                }else{
                    centerPanel = new lvwin.createForm.Main(this.node_id, this.level);
                }
                
                centerPanel.on('updated',function(){
                    win.close();
                    this.reload();},this);

                win = new Ext.Window({
                    id: 'lv-create-win',
                    title: <?php echo json_encode(__('Add logical volume')) ?>,
                    width:430,
                    height:240,
                    modal:true,
                    iconCls: 'icon-window',
                    bodyStyle: 'padding:10px;',
                    border:true,
                    layout: 'fit',
                    items: [centerPanel]
                    ,tools: [{
                        id:'help',
                        qtip: __('Help'),
                        handler:function(){
                            View.showHelp({
                                anchorid:'help-lvol-add',
                                autoLoad:{ params:'mod=logicalvol'},
                                title: <?php echo json_encode(__('Logical Volume Help')) ?>
                            });
                        }
                    }]
                });

            }

            win.show();
        },
        // removes logical volume
        // args: id: lv ID
        lvremove:function(){
            var ctx = this.ctxNode;
            var tree = ctx.getOwnerTree();

            Ext.MessageBox.show({
                    title: <?php echo json_encode(__('Remove logical volume')) ?>,
                    msg: String.format(<?php echo json_encode(__('Remove logical volume {0} ?')) ?>,ctx.text),
                    buttons: Ext.MessageBox.YESNOCANCEL,
                    fn: function(btn){

                        if(btn=='yes'){
                            var myparams;
                            if(this.level == 'cluster'){
                                myparams = {'cid': this.tree_node_id, 'level': this.level, 'lv':ctx.attributes.text};
                            }else if(this.level == 'node'){
                                myparams = {'nid': this.tree_node_id, 'level': this.level, 'lv':ctx.attributes.text};
                            }else{
                                myparams = {'nid': this.tree_node_id, 'lv':ctx.attributes.text};
                            }

                            var conn = new Ext.data.Connection({
                                            listeners:{
                                                beforerequest:function(){

                                                    Ext.MessageBox.show({
                                                        title: <?php echo json_encode(__('Please wait...')) ?>,
                                                        msg: <?php echo json_encode(__('Removing logical volume...')) ?>,
                                                        width:300,
                                                        wait:true
                                                    });

                                                },
                                                requestcomplete:function(){Ext.MessageBox.hide();}
                                                ,requestexception:function(c,r,o){
                                                    Ext.MessageBox.hide();
                                                    Ext.Ajax.fireEvent('requestexception',c,r,o);}
                                            }
                                        });

                            conn.request({
                                url: <?php echo json_encode(url_for('logicalvol/jsonRemove'))?>,
                                params: myparams,
                                scope:this,
                                success: function(resp,opt){
                                    var response = Ext.util.JSON.decode(resp.responseText);
                                    Ext.ux.Logger.info(response['agent'],response['response']);
                                    tree.root.reload();
                                },
                                failure: function(resp,opt) {
                                    var response = Ext.decode(resp.responseText);
                                    //Ext.ux.Logger.error(response['agent'],response['error']);

                                    Ext.Msg.show({
                                        title: String.format(<?php echo json_encode(__('Error {0}')) ?>,response['agent']),
                                        buttons: Ext.MessageBox.OK,
                                        msg: String.format(<?php echo json_encode(__('Unable to remove logical volume {0}')) ?>,ctx.attributes.text)+'<br>'+response['info'],
                                        icon: Ext.MessageBox.ERROR});
                                }
                            });// END Ajax request
                        }

                    },
                    scope:this,
                    icon: Ext.MessageBox.QUESTION
            });

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
                var centerPanel;
                if(this.level == 'cluster' || this.level == 'node'){
                    centerPanel = new lvwin.resizeForm.Main(this.tree_node_id, this.level);
                }else{
                    centerPanel = new lvwin.resizeForm.Main(this.node_id, this.level);
                }

                centerPanel.load(ctx);

                centerPanel.on('updated',function(){
                    win.close();
                    this.reload();},this);

                win = new Ext.Window({
                    id: 'lv-resize-win',
                    title: String.format(<?php echo json_encode(__('Resize logical volume {0}')) ?>,ctx.text),
                    width:360,
                    height:200,
                    iconCls: 'icon-window',
                   // shim:false,
                    animCollapse:false,
                    //     closeAction:'hide',
                    modal:true,
                    border:false,
                    defaultButton:centerPanel.items.get(0).lv_new_size,
                   // constrainHeader:true,
                    layout: 'fit',
                    items: [centerPanel]
                    ,tools: [{
                        id:'help',
                        qtip: __('Help'),
                        handler:function(){
                            View.showHelp({
                                anchorid:'help-lvol-rs',
                                autoLoad:{ params:'mod=logicalvol'},
                                title: <?php echo json_encode(__('Logical Volume Help')) ?>
                            });
                        }
                    }]
                });
            }

            win.show();

        },
        lvclone: function(){
            var ctx = this.ctxNode;
            var win = Ext.getCmp('lv-clone-win');

            var srcSize = byte_to_MBconvert(ctx.attributes.size,2,'floor');

            


            var win = Ext.getCmp('lv-resize-win');

            if(!win){
                var centerPanel;
                if(this.level == 'cluster' || this.level == 'node'){
                    centerPanel = new lvwin.cloneForm.Main(this.tree_node_id, this.level, srcSize); 
                }else{
                    centerPanel = new lvwin.cloneForm.Main(this.node_id, this.level, srcSize); 
                }

                centerPanel.load(ctx);
                centerPanel.on('updated',function(){
                    win.close();
                    this.reload();},this);

                win = new Ext.Window({
                    id: 'lv-clone-win',
                    title: String.format(<?php echo json_encode(__('Clone logical volume {0}')) ?>,ctx.text),
                    width:430,
                    height:230,
                    modal:true,
                    iconCls: 'icon-window',
                    bodyStyle: 'padding:10px;',
                    border:true,
                    layout: 'fit',
                    items: [centerPanel]
                    ,tools: [{
                        id:'help',
                        qtip: __('Help'),
                        handler:function(){
                            View.showHelp({
                                anchorid:'help-lvol-clone',
                                autoLoad:{ params:'mod=logicalvol'},
                                title: <?php echo json_encode(__('Logical Volume Help')) ?>
                            });
                        }
                    }]
                });
            }

            win.show();
        }
        ,lvconvert: function(){
            var ctx = this.ctxNode;
            var win = Ext.getCmp('lv-convert-win');

            if(!win){

                var centerPanel = new lvwin.convertForm.Main(this.node_id);

                centerPanel.load(ctx);
                centerPanel.on('updated',function(){
                    win.close();
                    this.reload();},this);

                win = new Ext.Window({
                    id: 'lv-convert-win',
                    title: String.format(<?php echo json_encode(__('Convert logical volume {0}')) ?>,ctx.text),
                    width:430,
                    height:180,
                    modal:true,
                    iconCls: 'icon-window',
                    bodyStyle: 'padding:10px;',
                    border:true,
                    layout: 'fit',
                    items: [centerPanel]
                    ,tools: [{
                        id:'help',
                        qtip: __('Help'),
                        handler:function(){
                            View.showHelp({
                                anchorid:'help-lvol-convert',
                                autoLoad:{ params:'mod=logicalvol'},
                                title: <?php echo json_encode(__('Logical Volume Help')) ?>
                            });
                        }
                    }]
                });

            }

            win.show();
        }
        // logical volume snapshot
        // call: createsnapshot
        // args: olv = vgname/lvname
        //       slv = lvname
        //       size
        ,lvsnapshot:function(){

            //uses lvcreatesnapshotwin
            var ctx = this.ctxNode;

            var win = Ext.getCmp('lv-createsnapshot-win');

            if(!win){

                var centerPanel = new lvwin.createSnapshotForm.Main(this.node_id);

                centerPanel.load(ctx);
                centerPanel.on('updated',function(){
                    win.close();
                    this.reload();},this);

                win = new Ext.Window({
                    id: 'lv-createsnapshot-win',
                    title: String.format(<?php echo json_encode(__('Create snapshot for logical volume {0}')) ?>,ctx.text),
                    width:430,
                    height:210,
                    modal:true,
                    iconCls: 'icon-window',
                    bodyStyle: 'padding:10px;',
                    border:true,
                    layout: 'fit',
                    items: [centerPanel]
                    ,tools: [{
                        id:'help',
                        qtip: __('Help'),
                        handler:function(){
                            View.showHelp({
                                anchorid:'help-lvol-snapshot',
                                autoLoad:{ params:'mod=logicalvol'},
                                title: <?php echo json_encode(__('Logical Volume Help')) ?>
                            });
                        }
                    }]
                });

            }

            win.show();
        }
        ,lvsnapshotrevert:function(){
            var ctx = this.ctxNode;
            console.log(ctx);
            var tree = ctx.getOwnerTree();

            Ext.MessageBox.show({
                    title: <?php echo json_encode(__('Revert snapshot')) ?>,
                    msg: String.format(<?php echo json_encode(__('Revert snapshot {0}?')) ?>,ctx.text),
                    buttons: Ext.MessageBox.YESNOCANCEL,
                    fn: function(btn){

                        if(btn=='yes'){
                            var myparams;
                            if(this.level == 'cluster'){
                                myparams = {'cid': this.tree_node_id, 'level': this.level, 'slv':ctx.attributes.text, 'olv':ctx.parentNode.attributes.text};
                            }else if(this.level == 'node'){
                                myparams = {'nid': this.tree_node_id, 'level': this.level, 'slv':ctx.attributes.text, 'olv':ctx.parentNode.attributes.text};
                            }else{
                                myparams = {'nid': this.tree_node_id, 'slv':ctx.attributes.text, 'olv':ctx.parentNode.attributes.text};
                            }

                            var conn = new Ext.data.Connection({
                                            listeners:{
                                                beforerequest:function(){

                                                    Ext.MessageBox.show({
                                                        title: <?php echo json_encode(__('Please wait...')) ?>,
                                                        msg: <?php echo json_encode(__('Reverting snapshot...')) ?>,
                                                        width:300,
                                                        wait:true
                                                    });

                                                },
                                                requestcomplete:function(){Ext.MessageBox.hide();}
                                                ,requestexception:function(c,r,o){
                                                    Ext.MessageBox.hide();
                                                    Ext.Ajax.fireEvent('requestexception',c,r,o);}
                                            }
                                        });

                            conn.request({
                                url: <?php echo json_encode(url_for('logicalvol/jsonRevertSnapshot'))?>,
                                params: myparams,
                                scope:this,
                                success: function(resp,opt){
                                    var response = Ext.util.JSON.decode(resp.responseText);
                                    Ext.ux.Logger.info(response['agent'],response['response']);
                                    tree.root.reload();
                                },
                                failure: function(resp,opt) {
                                    var response = Ext.decode(resp.responseText);
                                    //Ext.ux.Logger.error(response['agent'],response['error']);

                                    Ext.Msg.show({
                                        title: String.format(<?php echo json_encode(__('Error {0}')) ?>,response['agent']),
                                        buttons: Ext.MessageBox.OK,
                                        msg: String.format(<?php echo json_encode(__('Unable to revert snapshot {0}')) ?>,ctx.attributes.text)+'<br>'+response['info'],
                                        icon: Ext.MessageBox.ERROR});
                                }
                            });// END Ajax request
                        }

                    },
                    scope:this,
                    icon: Ext.MessageBox.QUESTION
            });
        }
        ,lvscan: function(){
            Ext.MessageBox.show({
                    title: <?php echo json_encode(__('Scan logical volumes')) ?>,
                    msg: String.format(<?php echo json_encode(__('Scan logical volumes?')) ?>),
                    buttons: Ext.MessageBox.YESNOCANCEL,
                    fn: function(btn){

                        if(btn=='yes'){
                            var myparams;
                            if(this.level == 'cluster'){
                                myparams = {'cid': this.tree_node_id, 'level': this.level};
                            }else if(this.level == 'node'){
                                myparams = {'nid': this.tree_node_id, 'level': this.level};
                            }else{
                                myparams = {'nid': this.tree_node_id};
                            }

                            var conn = new Ext.data.Connection({
                                listeners:{
                                    beforerequest:function(){
                                        Ext.MessageBox.show({
                                            title: <?php echo json_encode(__('Please wait...')) ?>,
                                            msg: <?php echo json_encode(__('Scan logical volumes...')) ?>,
                                            width:300,
                                            wait:true
                                        });

                                    },
                                    requestcomplete:function(){Ext.MessageBox.hide();}
                                    ,requestexception:function(c,r,o){
                                                        Ext.MessageBox.hide();
                                                        Ext.Ajax.fireEvent('requestexception',c,r,o);}

                                }
                            });

                            conn.request({
                                url: <?php echo json_encode(url_for('logicalvol/jsonReloadLogicalVolumes'))?>,
                                params: myparams,
                                scope:this,
                                success: function(resp,opt){

                                    var response = Ext.util.JSON.decode(resp.responseText);
                                    Ext.ux.Logger.info(response['agent'],response['response']);
                                    this.reload();
                                },
                                failure: function(resp,opt) {
                                    var response = Ext.util.JSON.decode(resp.responseText);
                                    Ext.Msg.show({
                                        title: String.format(<?php echo json_encode(__('Error {0}')) ?>,response['agent']),
                                        buttons: Ext.MessageBox.OK,
                                        msg: String.format(<?php echo json_encode(__('Unable to scan logical volumes')) ?>)+'<br>'+response['info'],
                                        icon: Ext.MessageBox.ERROR});
                                }
                            });// END Ajax request

                        }
                    },
                    scope:this,
                    icon: Ext.MessageBox.QUESTION
            });
        }
        ,lvunregister: function(){
            var ctx = this.ctxNode;
            var tree = ctx.getOwnerTree();

            Ext.MessageBox.show({
                    title: <?php echo json_encode(__('Unregister logical volume')) ?>,
                    msg: String.format(<?php echo json_encode(__('Unregister logical volume {0} ?')) ?>,ctx.text),
                    buttons: Ext.MessageBox.YESNOCANCEL,
                    fn: function(btn){

                        if(btn=='yes'){
                            var myparams;
                            if(this.level == 'cluster'){
                                myparams = {'cid': this.tree_node_id, 'level': this.level, 'lv':ctx.text, 'vg': ctx.attributes['vg'] };
                            }else if(this.level == 'node'){
                                myparams = {'nid': this.tree_node_id, 'level': this.level, 'lv':ctx.text, 'vg': ctx.attributes['vg'] };
                            }else{
                                myparams = {'nid': this.tree_node_id, 'lv':ctx.text, 'vg': ctx.attributes['vg'] };
                            }

                            if( ctx.attributes['uuid'] )
                                myparams['uuid'] = ctx.attributes['uuid'];

                            var conn = new Ext.data.Connection({
                                listeners:{
                                    beforerequest:function(){
                                        Ext.MessageBox.show({
                                            title: <?php echo json_encode(__('Please wait...')) ?>,
                                            msg: <?php echo json_encode(__('Unregistering logical volume...')) ?>,
                                            width:300,
                                            wait:true
                                        });

                                    },
                                    requestcomplete:function(){Ext.MessageBox.hide();}
                                    ,requestexception:function(c,r,o){
                                                        Ext.MessageBox.hide();
                                                        Ext.Ajax.fireEvent('requestexception',c,r,o);}

                                }
                            });

                            conn.request({
                                url: <?php echo json_encode(url_for('logicalvol/jsonUnregister'))?>,
                                params: myparams,
                                scope:this,
                                success: function(resp,opt){

                                    var response = Ext.util.JSON.decode(resp.responseText);
                                    Ext.ux.Logger.info(response['agent'],response['response']);
                                    tree.root.reload();
                                },
                                failure: function(resp,opt) {
                                    var response = Ext.util.JSON.decode(resp.responseText);
                                    Ext.Msg.show({
                                        title: String.format(<?php echo json_encode(__('Error {0}')) ?>,response['agent']),
                                        buttons: Ext.MessageBox.OK,
                                        msg: String.format(<?php echo json_encode(__('Unable to unregiste logical volume {0}')) ?>,ctx.text)+'<br>'+response['info'],
                                        icon: Ext.MessageBox.ERROR});
                                }
                            });// END Ajax request

                        }
                    },
                    scope:this,
                    icon: Ext.MessageBox.QUESTION
            });
        }
});






Node.Storage.Main = function(config){

    Ext.apply(this,config);

    if(this.level == 'cluster'){
        this.tree_node_id = this.cluster_id;
    }else{
        if(this.level == 'node'){
            this.tree_node_id = this.node_id;
        }
    }
    
    var doDevPanel = new treeDEV({id:'node-storage-dev-tree-'+this.tree_node_id
                          ,tree_node_id:this.tree_node_id
                          ,node_id:this.node_id
                          ,level:this.level
                      });

    var doVgPanel = new treeVG({id:'node-storage-vg-tree-'+this.tree_node_id
                          ,dev_id:'node-storage-dev-tree-'+this.tree_node_id
                          ,tree_node_id:this.tree_node_id
                          ,node_id: this.node_id
                          ,level:this.level
                      });


    //alert("level"+this.level+this.tree_node_id);
    var doLvPanel = new treeLV({id:'node-storage-lv-tree-'+this.tree_node_id
                          ,tree_node_id:this.tree_node_id
                          ,node_id: this.node_id
                          ,level:this.level
                      });

    doLvPanel.on('checkContextItems',function(ctxMenu){

        if(!doVgPanel.root.firstChild.attributes.type){
            //means that no data in vg
            ctxMenu.lvadd.setDisabled(true);
            ctxMenu.lvresize.setDisabled(true);
            ctxMenu.lvremove.setDisabled(true);
        }
    });



    Node.Storage.Main.superclass.constructor.call(this, {
                            border:false
                            ,layout:'column'
                            ,layoutConfig: {
                                fitHeight: true,
                                margin: 5,
                                split: true
                            }
                            ,items:[doDevPanel,doVgPanel,doLvPanel]
                            ,listeners:{
                                'reload':function(){
                                    var i = 0;
                                    for(var i=0,len=this.items.length;i<len;i++){
                                        var item = this.items.get(i);
                                        item.reload();
                                    }
                                }
                            }

    });

};

Ext.extend(Node.Storage.Main, Ext.Panel,{});

function search_on_nodetree(field,e){

    var nodesPanel = field.ownerCt.ownerCt;
    var name = field.getValue(); // Treenode name =P    
    if(name == ''){
        var node = nodesPanel.getRootNode();
        node.ui.show();
        var childs = node.childNodes;

        for(idx in childs){
            if(typeof(childs[idx]) == 'function'){
                break;
            }
            childs[idx].ui.show();
        }
        nodesPanel.doLayout();
    }else{
        var root = nodesPanel.getRootNode();
        var search_node = function(node, name){
           
            // Leaf nodes
            if(!node.hasChildNodes()){
                var patt=new RegExp(name,'ig');
                if(node.attributes['text'].match(patt) != null){
                    node.ensureVisible();
                    node.attributes.cls = 'RedText';
                    node.ui.show();
                    return false;
                }else{
                    node.ui.removeClass('RedText');
                    node.ui.hide();
                    return true;
                }
            }else{
                var childs = node.childNodes;
                var collapse = true;
  
                for(idx in childs){
                    if(typeof(childs[idx]) == 'function'){
                        break;
                    }
  
                    var child = childs[idx];
                    child.expand();
                    var res = search_node(childs[idx], name);
                    collapse = (collapse && res);                                                    
                }
                
                if(collapse){
                    node.collapse();
                    node.ui.hide();
                }else{
                    node.ui.show();
                }
                
                var patt=new RegExp(name,'ig');
            
                if(node.attributes['text'].match(patt) != null){
                    node.ensureVisible();
                    //node.ui.addClass('RedText');
                    node.attributes.cls = 'RedText';
                    return false;
                }else{
                    node.ui.removeClass('RedText');
                    return collapse;
                }
            }
            return collapse;
        }
        search_node(root, name);
    }
}

</script>
