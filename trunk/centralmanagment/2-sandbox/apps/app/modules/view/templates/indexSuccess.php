<?php
use_stylesheet('main.css');

use_javascript('ux/MessageWindow.js'); //plugin for MessageWindows

use_javascript('ux/ux-form/Ext.ux.ComboBox.js'); //extended combo with reload button
use_stylesheet('../js/ux/ux-form/css/Spinner.css');
use_javascript('ux/ux-form/Spinner.js'); //spinner field creation
use_javascript('ux/ux-form/form.js'); // tooltip icon on form items and more
//
//use_javascript('ux/Ext.ux.menu.js'); // contains menu plugin for grid filter plugin
use_javascript('ux/Ext.ux.menu.js');
use_javascript('ux/MultiSelect.js'); //plugin for multiselect & itemselector
//
use_javascript('ux/ux-grid/Ext.ux.grid.filter.js'); // contains plugin for filter plugin. numeric and string filters
//// contains plugin for remeber selection in grids reload, grid reorder DD, RowEditor
use_javascript('ux/ux-grid/Ext.ux.grid.js');
use_javascript('ux/ux-grid/GridPageSizer.js'); //plugin for Grid Pager Ext.ux.Andrie.pPageSize
//
//use_javascript('ux/miframe-debug.js');
use_javascript('ux/multidom.js');
use_javascript('ux/mif.js');
//use_javascript('ux/ux-tree/ColumnNodeUI.js'); //plugin for Ext.tree.ColumnTree
use_javascript('ux/ColumnLayout.js'); //plugin for panel ColumnLayout
use_javascript('ux/CardLayout.js');

//
use_javascript('ux/ux-wiz/Wizard.js');
use_javascript('ux/ux-wiz/Header.js');
use_javascript('ux/ux-wiz/West.js');
use_javascript('ux/ux-wiz/Card.js');


use_javascript('Main.js'); //general javscript functions and patches

use_stylesheet('../js/ux/ux-filemanager/css/styles.css');
use_stylesheet('../js/ux/statusbar/css/statusbar.css');
use_javascript('ux/statusbar/StatusBar.js');

//use_stylesheet('../js/ux/ux-filemanager/ux-uploadDialog/css/Ext.ux.UploadDialog.css');
//use_javascript('ux/ux-filemanager/ux-uploadDialog/Ext.ux.UploadDialog.js');

use_javascript('ux/uxvismode.js'); //visibility pack fo applet window hide


use_stylesheet('../js/ux/treegrid/treegrid.css');
use_javascript("ux/treegrid/TreeGridSorter.js");
use_javascript("ux/treegrid/TreeGridColumnResizer.js");
use_javascript("ux/treegrid/TreeGridNodeUI.js");
use_javascript("ux/treegrid/TreeGridLoader.js");
use_javascript("ux/treegrid/TreeGridColumns.js");
use_javascript("ux/treegrid/TreeGrid.js");


/*
 * check if file locale exist and load
 */
$lang_js = sfFinder::type('file')->name($sf_user->getCulture().'.js')->in(sfConfig::get('sf_web_dir').'/js');
if($lang_js) use_javascript("locale/".$sf_user->getCulture().".js");

$sfExtjs3Plugin = new sfExtjs3Plugin(array('theme'=>'blue'),array
                              (
                                'js' => 'locale/ext-lang-'.$sf_user->getCulture().'.js'
                              )
    //,array('js' => sfConfig::get('sf_extjs2_js_dir').'ext-all.js')
   //'css' => '/css/symfony-extjs.css'
   );

$sfExtjs3Plugin->load();
$sfExtjs3Plugin->begin();

echo "Ext.chart.Chart.CHART_URL = '".sfConfig::get('sf_extjs3_js_dir')."resources/charts.swf';";
echo "Ext.FlashComponent.EXPRESS_INSTALL_URL = '".sfConfig::get('sf_extjs3_js_dir')."resources/expressinstall.swf';";

$sfExtjs3Plugin->end();



/*
 * Include form and soap window info
 */
include_partial('node/NodeWindowSoap',array());
/*
 * include vnc combo
 */
include_partial('setting/Setting_VNC_keymapCombo');

?>
<script type='text/javascript'>

    //Ext.state.Manager.setProvider(new Ext.state.CookieProvider());

    Ext.namespace('View');    

    View = function(){

        return{
            getNodeCssState:function(data){

                var new_css_state = '';
                var remove_css_state = [];

                if((data['vm_state']=='running' && (data['agent_port'] && data['state'])) ||
                   (data['vm_state']=='running' && !data['agent_port']) ){
                   new_css_state = 'active';
                   remove_css_state = ['some-active','no-active'];
                }

                if((data['vm_state']=='running' && (data['agent_port'] && !data['state'])) ||
                   (data['vm_state']!='running' && (data['agent_port'] && data['state'])) ){
                   new_css_state = 'some-active';
                   remove_css_state = ['active','no-active'];
                }
                

                if((data['vm_state']!='running' && (data['agent_port'] && !data['state'])) ||
                   (data['vm_state']!='running' && !data['agent_port']) ){
                   new_css_state = 'no-active';
                   remove_css_state = ['active','some-active'];
                }                
                
                return {old_css:remove_css_state,new_css:new_css_state};


            }
            ,init:function(){
                Ext.QuickTips.init();

                /*
                 *
                 * TOP BAR
                 *
                 */

                buttonHi =  {xtype: 'tbtext',text: <?php echo json_encode(__('Welcome %1%!',array('%1%'=>$sf_user->getUsername()))) ?>};



                buttonLogout = new Ext.Action({
                    text: <?php echo json_encode(__('Logout')) ?>,
                    handler: function(){
                        window.location.href=<?php  echo json_encode(url_for('@signout',true)); ?>;}});

                var adminMenu = new Ext.menu.Menu({
                    id: 'adminMenu', // the menu's id we use later to assign as submenu
                    items: [
                            {
                                text:<?php echo json_encode(__('One-Time Setup Wizard',null,'first_time_wizard')) ?>
                                ,id:'ftwizardBtn'
                                ,url:<?php echo json_encode(url_for('view/View_FirstTimeWizard')); ?>
                                ,call:'View.FirstTimeWizard'
                                ,callback:function(item){
                                    new View.FirstTimeWizard.Main();
                                }
                                ,handler:this.loadComponent
                                ,scope:this
                            },                            
                            {
                                text:<?php echo json_encode(__('System preferences')) ?>
                                ,url:<?php echo json_encode(url_for('setting/view')); ?>
                                ,call:'Setting.Main'
                                ,callback:function(item){
                                    new Setting.Main({title:item.text});
                                }
                                ,handler:this.loadComponent
                                ,scope:this
                            },
                            {
                                text: <?php echo json_encode(__('Users and permissions administration')) ?>
                                ,url:<?php echo json_encode(url_for('sfGuardAuth/view')); ?>
                                ,call:'SfGuardAuth'
                                ,callback:function(item){
                                    new SfGuardAuth.Main({title:item.text});
                                }
                                ,handler:this.loadComponent
                                ,scope:this
                            }
                           ,buttonLogout
                    ]});
                    
                var systemAdminMenu = new Ext.Action({text: <?php echo json_encode(__('System Administration')) ?>,
                    menu: 'adminMenu' // assign the object by id
                });

                var toolsMenu = new Ext.menu.Menu({
                    id: 'toolsMenu', // the menu's id we use later to assign as submenu
                    items: [
                            {
                                text: <?php echo json_encode(__('OVF Import')) ?>
                                ,call:'Ovf.ImportWizard'
                                ,url:<?php echo json_encode(url_for('ovf/OvfImport')); ?>
                                ,callback:function(item){

                                    new Ovf.ImportWizard.Main({title:item.text});
                                }
                                ,handler:this.loadComponent
                                ,scope:this
                            }
                            ,{
                                text: <?php echo json_encode(__('OVF Export')) ?>,
                                url:<?php echo(json_encode(url_for('ovf/OvfExport')))?>,
                                call:'Ovf.Export',
                                callback:function(item){
                                    
                                    var window = new Ovf.Export.Window({title:item.text}).show();                                    
                                }
                                ,handler:this.loadComponent
                                ,scope:this
                            }
                            ,'-',
                            {
                                text: <?php echo json_encode(__('ISO Manager')) ?>,
                                ref:'isoManager',
                                handler:function(){
                                    mainPanel.layout.setActiveItem(0);
                                    mainPanel.doLayout();
                                }
                            }                            
                            ,
                            {
                                text: <?php echo json_encode(__('Nodes Agent Monitor keepalive')) ?>,
                                // url:'sfGuardGroup/view',
                                scope:this,
                                tooltip: <?php echo json_encode(__('Increases the frequency of the nodes status verification')) ?>,
                                handler: function(){View.monitorAlive();}
//                                ,onRender: function(container){
//                                    this.el = Ext.get(this.el);
//                                    container.dom.appendChild(this.el.dom);
//                                    if (this.tooltip) {
//                                            this.el.dom.qtip = this.tooltip;
//                                    }
//                                }
                            }
                            ,{
                                text: <?php echo json_encode(__('System events log')) ?>
                                ,url:<?php echo json_encode(url_for('event/view')); ?>
                                ,call:'Event.Main'
                                ,callback:function(item){
                                    new Event.Main({
                                        title:item.text
                                    });
                                }
                                ,handler:this.loadComponent
                                ,scope:this
                            }
                            <?php if($etvamodel=='standard'): ?>
                            ,'-'
                            ,{
                                text: <?php echo json_encode(__('Appliance')) ?>
                                ,menu:{
                                    items:[
                                        {
                                            text:<?php echo json_encode(__('Register Appliance')) ?>
                                            ,id:'view-index-register-appliance'
                                            ,url:<?php echo json_encode(url_for('appliance/register')); ?>
                                            ,call:'Appliance.Register.Main'
                                            ,callback:function(item){
                                                new Appliance.Register.Main({title:item.text});
                                            }
                                            ,handler:this.loadComponent
                                            ,scope:this
                                        }
                                        ,'-'
                                        ,{
                                            text:<?php echo json_encode(__('Appliance Backup')) ?>
                                            ,url:<?php echo json_encode(url_for('appliance/backup')); ?>
                                            ,call:'Appliance.Backup.Main'
                                            ,callback:function(item){
                                                var appliance = new Appliance.Backup.Main({title:item.text});

                                                // open register window
                                                appliance.on('showRegister',function(){
                                                    var regist_ = Ext.getCmp('view-index-register-appliance');
                                                    regist_.fireEvent('click',regist_);
                                                });
                                                
                                                appliance.show();
                                            }
                                            ,handler:this.loadComponent
                                            ,scope:this
                                        }
                                        ,{
                                            text:<?php echo json_encode(__('Appliance Restore')) ?>
                                            ,url:<?php echo json_encode(url_for('appliance/restore')); ?>
                                            ,call:'Appliance.Restore.Main'
                                            ,callback:function(item){
                                                var appliance = new Appliance.Restore.Main({title:item.text});

                                                // open register window
                                                appliance.on('showRegister',function(){
                                                    var regist_ = Ext.getCmp('view-index-register-appliance');
                                                    regist_.fireEvent('click',regist_);
                                                });

                                                appliance.show();
                                            }
                                            ,handler:this.loadComponent
                                            ,scope:this
                                        }
                                    ]
                                }
                            }                                                        
                            <?php endif; ?>
                    ]});

                var admintoolsMenu = new Ext.Action({text: <?php echo json_encode(__('Tools')) ?>, menu: 'toolsMenu'});

                var topBar = new Ext.Toolbar({
                    renderTo:'topBar',
                    buttons: [systemAdminMenu,
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
                NodePanel = function(id) {

                    NodePanel.superclass.constructor.call(this, {
                        id:id,
                        region:'west',
                        title:'Nodes',
                        //stateful:true,
                        //stateEvents : ['collapsenode', 'expandnode'],
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
                                     //View.checkState();
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
                            },{
                                id:'help',
                                qtip: __('Help'),
                                handler:function(){View.showHelp({anchorid:'help-left-panel-main',autoLoad:{ params:'mod=view'},
                                title: <?php echo json_encode(__('Nodes Help')) ?>});}
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
                            if(node) this.fireEvent('nodeselect', node.attributes);
                        },scope:this
                    });

                    this.addEvents({nodeselect:true});

                    this.on('contextmenu', this.onContextMenu, this);

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

                        var items = [
                                    '<b class="menu-title">'+<?php echo json_encode(__('Initialization')) ?>+'</b>'
                                    ,{
                                        text: <?php echo json_encode(__('Authorize')) ?>,
                                        ref:'btn_authorize',
                                        scope: this,
                                        disabled:true,
                                        cmd: <?php echo json_encode(EtvaNode_VA::INITIALIZE_CMD_AUTHORIZE) ?>,
                                        handler:function(btn,e){
                                            this.setInitialize(btn,this.ctxNode);
                                        }
                                    }
                                    ,{
                                        text: <?php echo json_encode(__('Re-initialize')) ?>,
                                        ref:'btn_reinitialize',
                                        scope: this,
                                        disabled:true,
                                        cmd: <?php echo json_encode(EtvaNode_VA::INITIALIZE) ?>,
                                        handler:function(btn,e){
                                            this.setInitialize(btn,this.ctxNode);
                                        }
                                    }
                                    ,'-'
                                    ,'<b class="menu-title">Node</b>'
                                    ,{
                                        id:'load-node',
                                        iconCls:'load-icon',
                                        text: <?php echo json_encode(__('Load node')) ?>,
                                        scope: this,
                                        handler:function(){
                                            var centerElem = Ext.getCmp('view-main-panel').findById('view-center-panel-'+this.ctxNode.id);
                                            
                                            if(centerElem){
                                                Ext.getCmp('view-main-panel').remove('view-center-panel-'+this.ctxNode.id);
                                                this.ctxNode.unselect();
                                            }
                                            this.ctxNode.select();}
                                    },
                                    {
                                        text: <?php echo json_encode(__('Change hostname')) ?>,
                                        scope: this,
                                        ref:'btn_hostname',
                                        disabled:true,
                                        handler:function(btn,e){
                                            this.setHostname(btn,this.ctxNode);
                                        }
                                    },
                                    <?php if($etvamodel=='enterprise'): ?>
                                    {
                                        text: <?php echo json_encode(__('Connectivity settings')) ?>,
                                        scope: this,
                                        disabled:true,
                                        ref:'btn_connectivity',
                                        handler:function(btn,e){
                                            this.setConn(btn,this.ctxNode);
                                        }
                                    },
                                    <?php endif; ?>
                                    {
                                        iconCls:'icon-keyboard',
                                        ref:'btn_keymap',
                                        text: <?php echo json_encode(__('Set keymap')) ?>,
                                        disabled:true,
                                        scope: this,
                                        handler:function(btn,e){                                            
                                            this.setKeymap(btn,this.ctxNode);
                                        }
                                    },
                                    {
                                        text :<?php echo json_encode(__('Node status')) ?>,
                                        param:'listDomains',
                                        menu:[
                                            //{text:'listDomains',param:'listDomains&id='+node.id,handler: this.showNodeWindowSoap},
                                            //{text:'getphydisk',param:'getphydisk_as_xml&id='+node.id,handler: this.showNodeWindowSoap},
                                            //'-',
                                            {text:<?php echo json_encode(__('Check status')) ?>,
                                                scope:this,
                                             handler:function(){View.checkNodeState(this.ctxNode);}}
                                            //,{text:'Go Virtual Machines',param:'list_vms&opt=update&id='+node.id,handler: this.showNodeWindowSoap},
                                            //'-',
                                            //{text:'Sync Virtual Machines',param:'list_vms_as_xml&id='+node.id,handler: this.showNodeWindowSoap}
                                        ],
                                        scope: this
                                        //,handler: this.showNodeWindowSoap
                                    }
                                    ,{
                                        text: <?php echo json_encode(__('Remove node')) ?>,                                        
                                        tooltip: {text: <?php echo json_encode(__('This will only remove data from Central Management')) ?>},
                                        ref:'btn_remove',
                                        disabled:true,
                                        scope:this,
                                        iconCls: 'icon-remove',
                                        handler:function(btn,e){
                                            this.deleteNode(btn,this.ctxNode);
                                        }
                                    }
                                    ,'-'
                                    
                                ];//end contextmenu items

                        if(!this.menu){ // create context menu on first right click                                                        

                            this.menu = new Ext.ux.TooltipMenu({
                                items: items
                            }); //end this.menu

                            this.menu.on('hide', this.onContextHide, this);
                        } //end if create menu                                               

                        if(this.ctxNode){
                            this.ctxNode.ui.removeClass('x-node-ctx');
                            this.ctxNode = null;
                        }

                        this.ctxNode = node;
                        this.ctxNode.ui.addClass('x-node-ctx');

//                        if(!node.isLeaf()){ //open context menu only if node is not a leaf
//                            this.menu.items.get('load-node').setDisabled(node.isSelected());
//                            //this.menu.items.get('remove-node').setDisabled(node.id==0);
//                        }

                        var node_state_msg = <?php echo json_encode(__('VirtAgent should be running to enable this menu')) ?>;

                        if(node.attributes.type=='node')
                        {
                            this.menu.btn_remove.setDisabled(false);
                            this.menu.btn_keymap.setDisabled(false);
                            this.menu.btn_keymap.clearTooltip();

                            if(node.attributes.state==0)
                            {                                

                                this.menu.btn_authorize.setDisabled(true);
                                this.menu.btn_authorize.setTooltip({text: node_state_msg});

                                this.menu.btn_reinitialize.setDisabled(true);
                                this.menu.btn_reinitialize.setTooltip({text: node_state_msg});

                                this.menu.btn_hostname.setDisabled(true);
                                this.menu.btn_hostname.setTooltip({text: node_state_msg});


                                if(this.menu.btn_connectivity)
                                {
                                    this.menu.btn_connectivity.setDisabled(true);
                                    this.menu.btn_connectivity.setTooltip({text: node_state_msg});
                                }

                            }else
                            {

                                this.menu.btn_authorize.setDisabled(node.attributes.initialize==<?php echo json_encode(EtvaNode_VA::INITIALIZE_OK) ?>);
                                this.menu.btn_authorize.clearTooltip();

                                this.menu.btn_reinitialize.setDisabled(false);
                                this.menu.btn_reinitialize.setTooltip({text:<?php echo json_encode(__('Re-initialize')) ?>});

                                this.menu.btn_hostname.setDisabled(false);
                                this.menu.btn_hostname.clearTooltip();


                                if(this.menu.btn_connectivity)
                                    if(node.attributes.initialize!=<?php echo json_encode(EtvaNode_VA::INITIALIZE_OK) ?>)
                                    {
                                        this.menu.btn_connectivity.setDisabled(true);
                                        this.menu.btn_connectivity.setTooltip({text: <?php echo json_encode(__('Needs to be initialized')) ?>});
                                    }
                                    else
                                    {
                                        this.menu.btn_connectivity.setDisabled(false);
                                        this.menu.btn_connectivity.clearTooltip();
                                    }
                            }
                        
                            
                            
                        }else
                        {
                            this.menu.btn_remove.setDisabled(true);
                            this.menu.btn_hostname.setDisabled(true);
                            this.menu.btn_hostname.clearTooltip();

                            if(node.attributes.node_state==0)
                            {
                                this.menu.btn_keymap.setDisabled(true);
                                this.menu.btn_keymap.setTooltip({text: node_state_msg});

                            }else
                            {
                                this.menu.btn_keymap.setDisabled(false);
                                this.menu.btn_keymap.clearTooltip();
                            }
                            

                            if(this.menu.btn_connectivity)
                            {
                                this.menu.btn_connectivity.setDisabled(true);
                                this.menu.btn_connectivity.clearTooltip();
                            }
                            

                        }
                        

                        if(node.attributes.type) this.menu.showAt(e.getXY());

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
                    }
                    ,deleteNode: function(btn,node){                                                

                        Ext.Msg.show({
                            title: <?php echo json_encode(__('Remove node')) ?>,
                            buttons: Ext.MessageBox.YESNOCANCEL,
                            msg: String.format(<?php echo json_encode(__('Remove node {0} ?')) ?>,node.attributes.text),
                            icon: Ext.MessageBox.WARNING,
                            fn: function(btn){
                                if (btn == 'yes'){

                                    var conn = new Ext.data.Connection({
                                        listeners:{
                                            // wait message.....
                                            beforerequest:function(){
                                                Ext.MessageBox.show({
                                                    title: <?php echo json_encode(__('Please wait...')) ?>,
                                                    msg: <?php echo json_encode(__('Removing node...')) ?>,
                                                    width:300,
                                                    wait:true,
                                                    modal: false
                                                });
                                            },// on request complete hide message
                                            requestcomplete:function(){Ext.MessageBox.hide();}
                                            ,requestexception:function(c,r,o){
                                                Ext.MessageBox.hide();
                                                Ext.Ajax.fireEvent('requestexception',c,r,o);}
                                        }
                                    });// end conn

                                    conn.request({
                                        url: <?php echo json_encode(url_for('node/jsonDelete')) ?>,
                                        params: {id: node.id},
                                        success: function(resp,opt) {
                                            Ext.getCmp('view-nodes-panel').removeNode(node.id);
                                            Ext.getCmp('view-main-panel').remove('view-center-panel-'+node.id);                 

                                        },
                                        failure: function(resp,opt) {
                                            Ext.Msg.alert(<?php echo json_encode(__('Error!')) ?>, <?php echo json_encode(__('Unable to delete node!')) ?>);
                                        }
                                    });// END Ajax request
                                }//END button==yes
                            }// END fn
                        }); //END Msg.show


                    }
                    ,setInitialize: function(btn,node){

                        var cmd = btn.cmd;
                        var msg = String.format(<?php echo json_encode(__('Sending initialization command {0} to {1}...')) ?>,cmd,node.attributes.text);
                        var conn = new Ext.data.Connection({
                            listeners:{
                                // wait message.....
                                beforerequest:function(){
                                    Ext.MessageBox.show({
                                        title: <?php echo json_encode(__('Please wait...')) ?>,
                                        msg: msg,
                                        width:300,
                                        wait:true,
                                        modal: false
                                    });
                                },// on request complete hide message
                                requestcomplete:function(){Ext.MessageBox.hide();}
                                ,requestexception:function(c,r,o){
                                    Ext.MessageBox.hide();
                                    Ext.Ajax.fireEvent('requestexception',c,r,o);}
                            }
                        });// end conn

                        var send_data = {'id':node.id,'cmd':cmd};
                        conn.request({
                            url: <?php echo json_encode(url_for('node/jsonInit')) ?>,
                            scope:this,
                            params:send_data,
                            // everything ok...
                            success: function(resp,opt){

                                this.reload();
                                var response = Ext.decode(resp.responseText);
                                Ext.ux.Logger.info(response['agent'],response['response']);
                                View.notify({html:response['response']});

                             //(this.ownerCt).fireEvent('keymapSave',resp);

                            },scope:this
                        });// END Ajax request
                        
                    }
                    ,setConn:function(btn,node){
                        <?php if($etvamodel=='enterprise'): ?>
                        var ipForm = new View.Connectivity();

                        if(!node.isLeaf()){
                            var node_id = node.id;

                            var window = new Ext.Window({
                                title: String.format(<?php echo json_encode(__('{0} connection settings')) ?>,node.attributes.text),
                                autoHeight: true,
                                width: 400,
                                //resizable: false,
                                border:false,
                                plain:true,
                                modal: false,
                                loadMask:true,
                                items:ipForm
                                ,tools:[{
                                    id:'help',
                                    qtip: __('Help'),
                                    handler:function(){View.showHelp({anchorid:'help-left-panel-connectivity',autoLoad:{ params:'mod=view'},
                                    title: <?php echo json_encode(__('Connectivity settings Help')) ?>});}
                                }]
                            });

                            window.on('show',function(){ipForm.loadData(node_id);});

                            window.show(btn.id);

                            window.on({'ipSave':function(resp){
                                var response = Ext.decode(resp.responseText);
                                var msg = String.format(<?php echo json_encode(__('Updated {0} IP settings')) ?>,node.attributes.text);
                                window.close();
                                Ext.ux.Logger.info(response['agent'],msg);
                                View.notify({html:msg});
                        }});
                        }

                        <?php endif; ?>
                        
                    },
                    setHostname:function(btn,node){
                        var title = String.format(<?php echo json_encode(__('Change {0} hostname')) ?>,node.attributes.text);
                        var hostnameForm = new View.HostName();                        

                        var window = new Ext.Window({
                            title: title,
                            autoHeight: true,                            
                            width: 350,
                            resizable: false,
                            border:false,
                            plain:true,
                            modal: true,
                            loadMask:true,
                            defaultButton:hostnameForm.getForm().findField('name'),
                            items:hostnameForm
                            ,tools:[{
                                id:'help',
                                qtip: __('Help'),
                                handler:function(){View.showHelp({anchorid:'help-left-panel-hostname',autoLoad:{ params:'mod=view'},
                                title: <?php echo json_encode(__('Hostname Help')) ?>});}
                            }]

                        });


                        hostnameForm.on({
                            'onCancel':function(){window.close();}
                            ,'onSave':function(){window.close();

                                this.getRootNode().reload(function(){
                                    var centerElem = Ext.getCmp('view-main-panel').findById('view-center-panel-'+node.id);
                                    if(centerElem && centerElem.isVisible())
                                    {
                                        this.selectNode(node.id);                                        
                                        centerElem.fireEvent('beforeshow');                                        
                                    }
                                    
                                    
                                },this);
                               
                            }
                            ,scope:this
                        });
                        
                        window.on('show',function(){
                            hostnameForm.loadData(node.id);
                        });

                        window.show(btn.id);                      

                    },
                    setKeymap:function(btn,node){
                        var title = <?php echo json_encode(__('Default keymap')) ?>;
                        
                        var url = <?php echo json_encode(url_for('setting/jsonSetting'))?>;
                        var isLeaf = false;
                        if(node.isLeaf()){
                            isLeaf = true;
                            title = node.attributes.text+' keymap';
                            var sId = node.id;
                            sId = sId.replace(/^s/,'');
                            url = <?php echo json_encode(url_for('server/jsonKeymap?id='))?>+sId;
                        }

                        var keyMapForm = new View.KeyMap({url:url,isLeaf:isLeaf});

                        var windowKeyMap = new Ext.Window({
                            title: title,
                            autoHeight: true,
                            width: 300,                            
                            resizable: false,
                            border:false,
                            plain:true,
                            modal: true,
                            loadMask:true,
                            iconCls:'icon-keyboard',
                            buttons:[{
                                text: __('Save'),
                                handler:function(){
                                    keyMapForm.onSave();}
                                },
                                {text: __('Cancel'),
                                 handler:function(){
                                    windowKeyMap.close();}
                             }],
                            items:keyMapForm
                            ,tools:[{
                                id:'help',
                                qtip: __('Help'),
                                handler:function(){View.showHelp({anchorid:'help-left-panel-keymap',autoLoad:{ params:'mod=view'},
                                title: <?php echo json_encode(__('Default keymap Help')) ?>});}
                            }]
                        });

                        windowKeyMap.on('show',function(){
                            keyMapForm.loadData();
                        });
                        
                        windowKeyMap.show(btn.id);

                        windowKeyMap.on({'keymapSave':function(resp){
                            var response = Ext.decode(resp.responseText);
                            var msg = String.format(<?php echo json_encode(__('Updated VNC keymap ({0})')) ?>,node.attributes.text);
                            windowKeyMap.close();
                            Ext.ux.Logger.info(response['agent'],msg);
                            View.notify({html:msg});
                        }});

                    },
                    showNodeWindowSoap : function(btn){

                        if(!this.winSoap){
                            this.winSoap = new NodeWindowSoap(btn);
                            // this.winSoap.on('validnode', this.addNode, this);
                        }
                        this.winSoap.show(btn);
                    },
                    selectNode: function(id){this.getNodeById(id).select();},
                    reload:function(){
                        this.getRootNode().reload();
                    },
                    removeNode: function(id){                                                
                        var node = this.getNodeById(id);                        
                        var gotoNode = this.getRootNode();
                        if(node){                            
                            gotoNode = (node.isLeaf())? node.parentNode: this.getRootNode();                            
                            node.unselect();
                            node.remove();
                            //this.getSelectionModel().select(gotoNode);

                        }

                        this.getRootNode().reload(function(){
                            //gotoNode.select();                            
                            this.selectNode(gotoNode.id);
                            var expand_node = this.getNodeById(gotoNode.id);
                            if(!expand_node.expanded) expand_node.expand();

                        },this);

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
                    //updates node css
                    updateNodeCss: function(attrs,css_old, css_new){
                        
                        var parent = this.getNodeById(attrs.parentNode);
                        if(!parent.expanded) parent.expand();

                        var exists = this.getNodeById(attrs.node);
                                                
                        if(exists)
                        {                                                                                                        
                            (exists.getUI()).removeClass(css_old);
                            (exists.getUI()).addClass(css_new);
                            if(attrs.selected) exists.select();
                        }
                    },                    
                    addNode : function(attrs){
                        
                        this.getRootNode().reload(function(){
                            
                            //var s = this.getSelectionModel().getSelectedNode();
                            var appendTo = this.getNodeById(attrs.parentNode);
                            if(!appendTo.expanded) appendTo.expand();

                            var exists = this.getNodeById(attrs.id);

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
                            node.select();
                            return node;

                        },this);
                        
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
                        "{0:capitalize}</div><span class='x-log-time'>{3:date('H:i:s')}</span>" +
                        "<span class='x-log-message'><b>{1}: </b>{2}</span></div>");

                    return Ext.apply(new Ext.Panel({
                        region:'south',
                        // region:'center',
                        title: <?php echo json_encode(__('Info panel')) ?>,
                        closeAction: 'hide',
                        collapsible: true,
                        margins: '0 3 3 3',
                        split: true,
                        useSplitTips: true,
                        height: 90,
                        autoScroll: true
                        ,tools:[{id:'help', qtip: __('Help'),handler:function(){View.showHelp({anchorid:'help-bottom-panel-main',autoLoad:{ params:'mod=view'},title: <?php echo json_encode(__('Info panel Help')) ?>});}}]
                        ,bbar:['->',{
                                text: __('Clear'),
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

                        debug: function(subject, msg) {
                            if(!msg){
                                msg = subject;
                                subject = '';
                            }
                            this.fn.call(tpl, this.body, ['debug', subject, msg, new Date()], true).scrollIntoView(this.body);
                        },

                        info: function(subject, msg) {
                            if(!msg){
                                msg = subject;
                                subject = '';
                            }
                            this.fn.call(tpl, this.body, ['info', subject, msg, new Date()], true).scrollIntoView(this.body);
                        },

                        warning: function(subject, msg) {
                            if(!msg){
                                msg = subject;
                                subject = '';
                            }
                            this.fn.call(tpl, this.body, ['warning', subject, msg, new Date()], true).scrollIntoView(this.body);
                        },

                        error: function(subject, msg) {
                            if(!msg){
                                msg = subject;
                                subject = '';
                            }
                            this.fn.call(tpl, this.body, ['error', subject, msg, new Date()], true).scrollIntoView(this.body);
                        }
                    });
                }();



                /*
                 *
                 * Main Panel (center side)
                 *
                 *
                 */                                  
                 var isoPanel = new View.ISOManagement({
                                    title:toolsMenu.isoManager.text,
                                    bbar:['->',{text: __('Close'),
                                        handler:function(){
                                            
                                            var currentNode = nodesPanel.getSelectionModel().getSelectedNode();
                                            if(currentNode){
                                                nodesPanel.fireEvent('nodeselect', currentNode.attributes);
                                            }else nodesPanel.selectNode(0);

                                        }}]
                                });





                mainPanel = new Ext.Panel({
                    // defaults: {bodyStyle: 'padding:10px'},
                    margins: '3 3 3 3',
                    layout: 'card',
                   //activeItem: 0,
                    id:'view-main-panel',
                    region: 'center',
                    defaults:{border:false},
                    collapsible: false,
                    border:true
                    ,items:[isoPanel]
                    });
                /*
                 *
                 * Initializing some stuff
                 *
                 */


                nodesPanel = new NodePanel('view-nodes-panel');
                nodesPanel.on('dblclick' ,function(node){this.fireEvent('nodeselect', node.attributes);});
                nodesPanel.on('click' ,function(node){if(!node.isLeaf()) node.expand();});

                nodesPanel.on('nodeselect', function(node){
                             
                    var centerElem = mainPanel.findById('view-center-panel-'+node.id);
                    var node_class = 'View.Main';
                    var component = '';
                    if(node.type=='server') node_class = 'Server.View';
                    if(node.type=='node') node_class = 'Node.View';
                    
                    if(!centerElem)
                    /*
                     * create item component and add to mainPanel
                     */
                    {
                        var item = {
                                    url:node.url,
                                    call: node_class,
                                    callback:function(){

                                        if(node.type=='server'){

                                            var treenode_ = nodesPanel.getNodeById(node.id);
                                            var nid = (treenode_.isLeaf())? treenode_.parentNode: nodesPanel.getRootNode();
                                            var sid = node.id.replace('s','');
                                            var agent_tmpl = node.agent_tmpl;
                                            var state = node.state;
                                            component = new Server.View.Main({node_id:nid.id,server:{id:sid,agent_tmpl:agent_tmpl,state:state}});
                                            
                                        }else{

                                            if(node.type=='node') component = new Node.View.Main({node_id:node.id});
                                            else component = new View.Main();
                                            
                                        }

                                        component.on('updateNodeCss',function(node_attrs,data){
                                                                   
                                                var css_ = View.getNodeCssState(data)                                            
                                                Ext.getCmp('view-nodes-panel').updateNodeCss(node_attrs,css_.old_css,css_.new_css);
                                            
                                        });

                                        centerElem = mainPanel.add(
                                                {
                                                    id: 'view-center-panel-'+node.id,
                                                    title: node.text,                                                    
                                                    items:component,
                                                    layout:'fit',
                                                    bodyStyle:'padding:0px;',
                                                    defaults:{border:false},
                                                    listeners:{
                                                        'beforehide':function(){
                                                            // Ext.TaskMgr.stopAll();
                                                        }
                                                        ,'beforeshow':function(){
//                                           
                                                            if(centerElem.rendered){
                                                            for(var i = 0,limit = centerElem.items.length; i < limit; i++)
                                                                centerElem.get(i).fireEvent('reload');
                                                            }
                                                        }
                                                    },
                                                    scripts:true
                                        });

                                        mainPanel.layout.setActiveItem(centerElem);                                        

                                    }// end callback
                        };// end item to process
                        
                        View.loadComponent(item);

                    }//end if
                    else{
                        centerElem.setTitle(node.text);
                        mainPanel.layout.setActiveItem(centerElem);
                    }
                    
                    

                });


                var viewport = new Ext.Viewport({
                    layout:'border',
                    items:[
                        new Ext.BoxComponent({ // raw element
                            region:'north',
                            el: 'header'
                            ,height:85
                            })
                        ,nodesPanel
                        ,mainPanel
                        ,Ext.ux.Logger
                    ]
                    ,listeners:{
                        render:function(){
                            //prevent browser right-click
                            Ext.getBody().on("contextmenu", Ext.emptyFn, null, {preventDefault: true});
                        }
                    }
                });

                nodesPanel.selectNode(0);

                //calls chagePwd if first time log in
                <?php if($sf_user->getAttribute('user_firstlogin')):?>
                //View.changePwd();
                setTimeout(function(){
                    Ext.getCmp('ftwizardBtn').fireEvent('click',Ext.getCmp('ftwizardBtn'));
                }, 200);
                <?php endif;
                // remove session user_firstlogin
                $sf_user->getAttributeHolder()->remove('user_firstlogin');
                ?>
             
                //   setTimeout(initNodesState, 2000);


            }// END init
            ,
            /*
             * shows a popup window with form change password
             *
             */
            changePwd:function(){
                var form =
                    new Ext.form.FormPanel({
                            monitorValid:true,
                            buttonAlign: 'center',
                            labelWidth: 120,
                            bodyStyle:'padding:10px',
                            url: 'sfGuardAuth/jsonChangePwd',
                            baseCls: 'x-plain',
                            defaults: {xtype: 'textfield',msgTarget:'side'},
                            items: 
                                [
                                {
                                xtype:'box'
                                ,height:42
                                ,autoEl:
                                    {tag:'div', children:
                                        [{
                                            tag:'div'
                                            ,style:'float:left;width:31px;height:32px;'
                                            ,cls:'icon-warning'
                                        },
                                        {
                                            tag:'div'
                                            ,style:'margin-left:35px;'
                                            ,html:'It\'s recommended to change DEFAULT system password!'
                                        }]
                                    }
                                },                                
                                {
                                    fieldLabel: 'Current Password',
                                    allowBlank: false,
                                    inputType: 'password',
                                    name: 'cur_pwd',
                                    minLength: 4,
                                    tabIndex:1,
                                    anchor: '90%'
                                },
                                {
                                    fieldLabel: 'New Password',
                                    allowBlank: false,
                                    inputType: 'password',
                                    tabIndex:2,
                                    name: 'pwd',
                                    minLength: 4,
                                    anchor: '90%'
                                },
                                {
                                    fieldLabel: 'Confirm New Password',
                                    allowBlank: false,
                                    inputType: 'password',
                                    tabIndex:3,
                                    name: 'pwd_again',
                                    validator:function(v){
                                        if(v==form.items.get(2).getValue()) return true;
                                        else return 'Passwords do not match';
                                    },
                                    minLength: 4,
                                    anchor: '90%'
                                }],
                            buttons:
                                [
                                {
                                    text: 'Send',
                                    tabIndex:4,
                                    formBind:true,
                                    handler:function(){
                                        form.getForm().submit({
                                            url:form.url
                                            ,method:'POST'
                                            ,success: function(form, action) {
                                                Ext.Msg.show({title: 'Change password',
                                                    buttons: Ext.MessageBox.OK,
                                                    msg: action.result.response,
                                                    fn:function(){window.close();},
                                                    icon: Ext.MessageBox.INFO});
                                            },
                                            failure: function(form, action) {
                                                switch (action.failureType) {
                                                    case Ext.form.Action.CLIENT_INVALID:
                                                        Ext.Msg.alert('Failure', 'Form fields may not be submitted with invalid values');
                                                        break;
                                                    case Ext.form.Action.CONNECT_FAILURE:
                                                        Ext.Msg.alert('Failure', 'Ajax communication failed');
                                                        break;
                                                    case Ext.form.Action.SERVER_INVALID:
                                                        Ext.Msg.show({title: 'Error '+action.result.agent,
                                                            buttons: Ext.MessageBox.OK,
                                                            msg: action.result.info,
                                                            fn:function(){window.focus();},
                                                            icon: Ext.MessageBox.ERROR});
                                                }
                                            }
                                            ,waitMsg:'Saving...'
                                        });
                                    }
                                },
                                {
                                    text:'Cancel',
                                    tabIndex:5,
                                    handler:function(){window.close();}
                                }]
                            });
                var window = new Ext.Window({
                    title: 'Change password',                    
                    modal:true,
                    width: 330,
                    height: 250,
                    layout: 'fit',
                    defaultButton:form.items.get(1),
                    plain: true,
                    bodyStyle: 'padding:10px;',
                    items: form});

                window.show();              

            },
            loadFailed:function(proxy, options, response, error) {
                var object = Ext.util.JSON.decode(response.responseText);
                var errorMessage = "Error loading data.";
                Ext.MessageBox.alert('Error Message', errorMessage);
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
            /*
            * shows online help window.
            * loads view/help action
            */
            showHelp:function(config){

                var helpWindow = null;

                if(config.id) helpWindow = Ext.getCmp(config.id);
                else helpWindow = Ext.getCmp('online-help');

                if(helpWindow)
                    helpWindow.close();
                
                
                var initConf = {
                    id:'online-help',
                    width: 300,
                    bodyCssClass:'online-help',
                    x:0,height: 300,
                    autoScroll:true,
                    bodyStyle: {
                        padding: '5px'
                    },                    
                    plugins: new Ext.ux.WindowAlwaysOnTop
                };

                Ext.apply(initConf, config);

                if(!initConf.autoLoad.url) initConf.autoLoad.url = <?php echo json_encode(url_for('view/help')) ?>;
                if(!initConf.autoLoad.scripts) initConf.autoLoad.scripts = true;

                if(!initConf.autoLoad.callback){
                    if(config.anchorid) initConf.autoLoad.callback = (function(ctEl){

                        var el = Ext.get(config.anchorid);
                        var yoffset = el.getOffsetsTo(ctEl)[1];
                        ctEl.scrollTo('top',yoffset,true);
                        //    Ext.get(config.anchorid).scrollIntoView(ctEl);
                    });
                }

                helpWindow = new Ext.Window(initConf);
                
                helpWindow.show();
            },
             // used to display popup info
            notify:function(params,updater) {

                var initConf = {
                    title: <?php echo json_encode(__('System notification')) ?>
                    ,origin:{offY:-5,offX:-5}
                    ,autoHeight:true
                    ,defaults:{border:false}
                    ,iconCls: 'icon-info'
                    ,help:false
                    // ,pinState: 'pin'//render pinned
                    ,hideFx:{delay:2000, mode:'standard'}
                    ,listeners:{
                        render:function(){
                            // Ext.ux.Sound.play('generic.wav');
                        }
                    }
                    };

                Ext.apply(initConf, params);

                var notificationWindow = new Ext.ux.window.MessageWindow(initConf);


                if(params.task){
                    notificationWindow.on('beforehide',function(){
                        Ext.TaskMgr.stop(params.task);
                    })
                }

                notificationWindow.show(Ext.getDoc());

                return notificationWindow;
            },
            /*
             * check state of specific node
             */
            checkNodeState:function(node){

                //if node is server query parent node instead
                if(node.attributes.type=='server') node = node.parentNode;
                
                var mgr = new Ext.Updater("notificationDiv");
                // if node agent dead display notice
                mgr.on('failure',function(el,resp){
                    var response = '';

                    if(resp.responseText)
                        response = (Ext.util.JSON.decode(resp.responseText))['error'];
                    else response = 'Erro';
                    View.notify({html:response});

                });

                mgr.on('update',function(el,resp){

                    var agent = (Ext.util.JSON.decode(resp.responseText))['agent'];
                    Ext.ux.Logger.info(agent, 'System check');

                });

                /*
                 * checks if node agent is alive
                 */
                mgr.update({
                    url: <?php echo json_encode(url_for('node/jsonCheckState?id='))?>+node.id,
                    scripts:true
                });



            },
            // initial check nodes for connectivity
            //deprecated see nodes tree panel
            checkState:function(){


                // initial check server for connectivity
                var mgr = new Ext.Updater("notificationDiv");
                // if node agent dead display notice
                mgr.on('failure',function(el,resp){
                    var response = '';

                    if(resp.responseText)
                        response = (Ext.util.JSON.decode(resp.responseText))['error'];
                    else response = 'Erro';
                    View.notify({html:response});

                });

                mgr.on('update',function(el,resp){

                    var agent = (Ext.util.JSON.decode(resp.responseText))['agent'];
                    Ext.ux.Logger.info(agent, 'System check');

                });

                <?php foreach($node_list as $node):?>
                /*
                 * checks if node agent is alive
                 */
                mgr.update({
                    url: <?php echo json_encode(url_for('node/jsonCheckState?id='.$node->getId()))?>,
                    scripts:true
                });
                <?php endforeach; ?>

            },
            /*
             * task to run...
             */
            updateState:function(nid){
                var mgr = new Ext.Updater("notificationDiv");
                mgr.setDefaultUrl(<?php echo json_encode(url_for('node/jsonCheckState?id='))?>+nid);
                mgr.refresh(function(oE,bS){
                    if(!bS) Ext.MessageBox.hide();});

            }
            ,monitorAlive:function() {
                var mgr = new Ext.Updater("notificationDiv");
                View.notify({html:'Keepalive ON',pinState: 'pin'},mgr);
                var gap = 0;

                <?php  foreach($node_list as $node):?>

                    var update_task = {
                            run:function(){
                                this.updateState(<?php echo $node->getId(); ?>);
                            }
                            ,interval: <?php echo sfConfig::get('app_node_monitor_keepalive'); ?>+gap
                            ,scope:this
                        };
                    Ext.TaskMgr.start(update_task);
                    
                    gap += <?php echo sfConfig::get('app_node_monitor_keepalive_gap'); ?>;
                <?php  endforeach; ?>

            },
            //
            registerAppliance:function(){

            },
            //load webpage component once...
            loadComponent:function(item,e){                
                
                var class_load = item.call.split('.');
                var evaluated = true;
                var func_eval = [];
                var eval_exp ='';
                for(var i=0;i<class_load.length;i++)
                {

                    func_eval.push(class_load[i]);
                    eval_exp = func_eval.join('.');
                    
                    if(eval("typeof "+eval_exp+"=='undefined'"))
                    {
                        evaluated = false;
                        break;
                    }
                }

                //if(eval("typeof "+class_load[0]+"!='undefined'")
                //    && eval("typeof "+item.call+"!='undefined'")
                //    && eval("typeof "+item.callback+"!='undefined'"))
                if(evaluated && eval("typeof "+item.callback+"!='undefined'"))
                {                    
                    item.callback(item);
                }
                else{                    
                    View.clickHandler(item,e);
                }

            },
            clickHandler:function (item, e) {            
                Ext.getBody().mask(<?php echo json_encode(__('Retrieving data...')) ?>);                
                Ext.Ajax.request({
                    url: item.url,
                    method: 'POST',
                    success:function(response){

                        Ext.get('dynPageContainer').update(response.responseText,true,function(){

                            if(eval("typeof "+item.callback+"!='undefined'")) item.callback(item);
                            Ext.getBody().unmask();
                            
                        });
                    },
                    failure:function(){
                        Ext.getBody().unmask();
                    }
                });
            }
        }// end return
    }();// end View


    


     View.ISOManagement = function(config) {

        var config_initial = {title:'ISO Management'};
        Ext.apply(this,Ext.apply(config_initial,config));


        /* ---- Begin grid --- */
		var ds = new Ext.data.GroupingStore({
            proxy: new Ext.data.HttpProxy({url:<?php echo json_encode(url_for('view/iso'))?>,method: 'POST'}),
            baseParams:{doAction:'jsonList'},
			sortInfo: {field: 'name', direction: 'ASC'},
			reader: new Ext.data.JsonReader({
				root: 'data',
				totalProperty: 'count'
			},[
				{name: 'name', sortType: 'asUCString'},
				{name: 'size', type: 'int'},
				{name: 'ctime', type: 'date', dateFormat: 'timestamp'},
				{name: 'mtime', type: 'date', dateFormat: 'timestamp'},
				{name: 'full_path'}
			]),
            listeners:{
                exception:function(proxy, type,act,options,resp, args){
                    if(resp.status==401) return;
                    if(type=='response'){
                        var response = Ext.util.JSON.decode(resp.responseText);
                        Ext.MessageBox.show({
                            title: response['agent'],
                            msg: response['error'],
                            buttons: Ext.MessageBox.OK,
                            icon: Ext.MessageBox.ERROR
                        });
                    }
                }
            }
		});

        ds.on('load',function(){this.doButtons();},this);

		var cm = new Ext.grid.ColumnModel({
			defaults: {
				sortable: true
			},
			columns: [
				{header: 'Name', dataIndex: 'name'},
				{header: 'Size', dataIndex: 'size',renderer: Ext.util.Format.fileSize},
				{header: 'Created', dataIndex: 'ctime', renderer: Ext.util.Format.dateRenderer('Y-m-d H:i:s')},
				{header: 'Modified', dataIndex: 'mtime', renderer: Ext.util.Format.dateRenderer('Y-m-d H:i:s')},
				{header: 'Full Path', dataIndex: 'full_path', hidden: true}
			]
		});

		var grid = new Ext.grid.GridPanel({
			anchor: '0 100%',
            id:'view-iso-grid',
            loadMask:true,
			border: false,
            keys:[{
                    key: Ext.EventObject.DELETE,
                    fn: this.doDelete,
                    scope:this

            }],
			view: new Ext.grid.GroupingView({
				emptyText: <?php echo json_encode(__('No files found.')) ?>,
				forceFit: true,
				showGroupName: false,
				enableNoGroups: true
			}),
			ds: ds,
			cm: cm,
			listeners: {
				'rowClick':{fn:this.doButtons,scope:this}

			}
            ,bbar:new Ext.ux.grid.TotalCountBar({
                store:ds
                ,displayInfo:true
            })
		});


		/* ---- End grid --- */


        View.ISOManagement.superclass.constructor.call(this,{			
            layout: 'anchor',
            border: false,
            listeners: {
                'rowClick': function () {
                    alert('cli');
                    //do_buttons();
                }
                ,show:function(){ds.load();
                }
			},
            tbar: new Ext.ux.StatusBar({
                id: 'view-iso-status-bar',
                defaultText: '',
              //  defaultIconCls: '',
              //  statusAlign: 'right',
                items: [{
                    text: <?php echo json_encode(__('Upload Applet (FTP mode)')) ?>,
                    tooltip: <?php echo json_encode(__('Upload through FTP')) ?>,
                    iconCls: 'upload_button',
				    handler: this.doUploadApplet
                }
                ,{
                    ref: 'download_button',
                    text: <?php echo json_encode(__('Download')) ?>,
                    tooltip: <?php echo json_encode(__('Download selected file')) ?>,
                    iconCls: 'download_button',
                    disabled: true,
                    scope:this,
				    handler: this.doDownload
                }
                ,{
                    ref: 'rename_button',
                    text: <?php echo json_encode(__('Rename')) ?>,
                    tooltip: <?php echo json_encode(__('Rename selected file')) ?>,
                    iconCls: 'rename_button',
                    disabled: true,
                    scope:this,
				    handler: this.doRename
                },{
                    ref: 'delete_button',
                    text: <?php echo json_encode(__('Delete')) ?>,
                    tooltip: <?php echo json_encode(__('Delete selected file')) ?>,
                    iconCls: 'delete_button',
                    disabled: true,
                    scope: this,
					handler: this.doDelete
                }
                ]
            })
            ,items:[grid]
            });




    }//eof

    // define public methods
    Ext.extend(View.ISOManagement, Ext.Panel,{
        tools:[{id:'help', qtip: __('Help'),handler:function(){View.showHelp({anchorid:'help-iso-main',autoLoad:{ params:'mod=view'},title: <?php echo json_encode(__('ISO Manager Help')) ?>});}}]
        ,doButtons:function() {

			var row = this.get(0).getSelectionModel().getSelected();

			if (row != null) {
              	Ext.getCmp('view-iso-status-bar').download_button.enable();
				Ext.getCmp('view-iso-status-bar').rename_button.enable();
				Ext.getCmp('view-iso-status-bar').delete_button.enable();

			} else {

                Ext.getCmp('view-iso-status-bar').download_button.disable();
				Ext.getCmp('view-iso-status-bar').rename_button.disable();
                Ext.getCmp('view-iso-status-bar').delete_button.disable();

			}
		}
        ,doUploadApplet:function(){
            var url = '<?php echo url_for('view/jupload');?>';
            var vPack = new Ext.ux.plugin.VisibilityMode({ bubble : false }) ;
            var viewerSize = Ext.getBody().getViewSize();
            var windowHeight = viewerSize.height * 0.95;
            windowHeight = Ext.util.Format.round(windowHeight,0);

            var config = {
                    title: <?php echo json_encode(__('Please wait...')) ?>,
                    height        : 370,
                    width         : 675,
                    maximizable   : true,
                    collapsible   : true,
                    plugins: vPack,
                    //constrain     : true,
                    defaultSrc:url,
                    shadow:false,
                    autoScroll    : true,
                    tools:[{id:'help', qtip: __('Help'),handler:function(){View.showHelp({anchorid:'help-iso-upload',autoLoad:{ params:'mod=view'},title: <?php echo json_encode(__('ISO upload Help')) ?>});}}]
                    ,hideModei :!Ext.isIE?'nosize':'display',
                    //hideMode      : 'display',
                    //hideMode      : 'visibility',
                  //  hideMode      : 'nosize',
                   // hideMode      : 'offsets',
                    listeners : {
                        documentloaded : function(frameEl){
                                        var MIF = frameEl.ownerCt;
                                        var doc = frameEl.getFrameDocument();
                                        var applet = doc.getElementsByTagName('applet');
                                        var docHeight = parseInt(applet[0].height)+70;
                                        var docWidth = parseInt(applet[0].width)+35;

                                        MIF.setTitle(doc.title);
                                        MIF.setWidth(docWidth);
                                        MIF.setHeight(windowHeight > docHeight  ? docHeight:windowHeight );
                                        MIF.center();

                                        View.notify({html:MIF.title+' reports: DATA LOADED'});

                        },
                        resize:function(frameEl,event,docSize,viewPortSize,viewSize){
                                        var MIF = frameEl.ownerCt;
                                        var doc = frameEl.getFrameDocument();

                                        if(doc && typeof(docSize)=='object'){

                                            var applet = doc.getElementsByTagName('applet');
                                            var new_x = parseInt(docSize['width']) - 20;
                                            var new_y = parseInt(docSize['height']) - 35;

                                            applet[0].width = new_x;
                                            applet[0].height = new_y;
                                        }

                        },
                        beforedestroy : function(){}
                    }
                    //,sourceModule : 'mifsimple'
            };

            var win = new Ext.ux.ManagedIFrame.Window(config);
            win.show();
        }
        ,doDownload:function(){
            var grid = this.get(0);
			var row = grid.getSelectionModel().getSelected();
			self.location = <?php echo json_encode(url_for('view/isoDownload'))?> + '?file=' + row.data.name;

        }
        ,doDelete:function(){
            var grid = this.get(0);
			var row = grid.getSelectionModel().getSelected();
//                        var w = new Ext.Window({title: 'asdas', width: 100, heigth: 100, tools:[{id:'help', qtip: __('Help'),handler:function(){View.showHelp({anchorid:'help-iso-upload',autoLoad:{ params:'mod=view'},title: <?php echo json_encode(__('ISO upload Help')) ?>});}}]});;
//                        w.show();
//            Ext.Msg.show({title: 'ghfgdh',
//                        buttons: Ext.MessageBox.OK,
//                        msg: 'bla bla',
//                        tools:[{id:'help', qtip: __('Help'),handler:function(){View.showHelp({anchorid:'help-iso-upload',autoLoad:{ params:'mod=view'},title: <?php echo json_encode(__('ISO upload Help')) ?>});}}]
//                        ,fn:function(){rename_window.focus();},
//                        icon: Ext.MessageBox.ERROR});
            Ext.MessageBox.confirm(<?php echo json_encode(__('Confirm delete')) ?>, String.format(<?php echo json_encode(__('Are you sure you wanto to delete {0} ?')) ?>,row.data.name),
                function(reponse) {

                  if (reponse == "yes") {

					var connection = new Ext.data.Connection({
                                                    listeners:{
                                                        // wait message.....
                                                        beforerequest:function(){
                                                            Ext.getCmp('view-iso-status-bar').showBusy(<?php echo json_encode(__('Deleting...')) ?>);
                                                        }// on request complete hide message
                                                        ,requestcomplete:function(){
                                                            Ext.getCmp('view-iso-status-bar').clearStatus();
                                                        }
                                                        ,requestexception:function(c,r,o){
                                                            Ext.Ajax.fireEvent('requestexception',c,r,o);}
                                                    }
                                                }).request({
						url: <?php echo json_encode(url_for('view/iso'))?>,
						method: "POST",
						params: {doAction: "jsonDelete", file: row.data.name},
						success: function(o) {
							var response = Ext.util.JSON.decode(o.responseText);

							if (response.success == true) {
								grid.getStore().reload();
							} else {

								// Set a status bar message
								Ext.getCmp('view-iso-status-bar').setStatus({
									text: response.message,
									iconCls: 'save_warning_icon',
									clear: true
								});

							}
						},
						failure: function(o) {
							var response = Ext.util.JSON.decode(o.responseText);

							// Set a status bar message
							Ext.getCmp('view-iso-status-bar').setStatus({
								text: response.message,
								iconCls: 'save_warning_icon',
								clear: true
							});
						}
					});
				}
			});
        }
        ,doRename:function(){
            
            var grid = this.get(0);
			var row = grid.getSelectionModel().getSelected();

			var rename_form = new Ext.FormPanel({
				url: <?php echo json_encode(url_for('view/iso'))?>,
				method: 'POST',
				bodyStyle: 'padding:10px',
				border: false,
				items: [
					new Ext.form.TextField({
						fieldLabel: __('Name'),
						name: 'new_name',
						value: row.data.name,
						width: 'auto'
					})
				]
			});

			var rename_window = new Ext.Window({
				title: <?php echo json_encode(__('Rename file')) ?>,
				width: 340,
				closable: true,
				resizable: false,
                defaultButton:0,
                                tools:[{id:'help', qtip: __('Help'),handler:function(){View.showHelp({anchorid:'help-iso-rename',autoLoad:{ params:'mod=view'},title: <?php echo json_encode(__('Rename file Help')) ?>});}}],
				buttons: [{
					text: __('Save'),
					handler: function() {
						rename_form.getForm().submit({
							waitMsg: <?php echo json_encode(__('Please wait...')) ?>,
                            params: {doAction: "jsonRename", file: row.data.name},
							success: function() {
								grid.getStore().reload({callback:this.doButtons});
								rename_window.hide();
							},
                            failure: function(form, action) {                                

                                if(action.response.responseText){

                                    var response = Ext.util.JSON.decode(action.response.responseText);

                                    // Set a status bar message
                                    Ext.getCmp('view-iso-status-bar').setStatus({
                                        text: response.message,
                                        iconCls: 'save_warning_icon',
                                        clear: true
                                    });

                                    Ext.Msg.show({title: String.format(<?php echo json_encode(__('Error {0}')) ?>,response['agent']),
                                        buttons: Ext.MessageBox.OK,
                                        msg: response['info'],
                                        fn:function(){rename_window.focus();},
                                        icon: Ext.MessageBox.ERROR});
                                }
                                
                            }
							
						});
					}
				},{
					text: __('Cancel'),
					handler: function() {
						rename_window.hide();
					}
				}],
				items: rename_form
			});

			rename_window.show(Ext.getCmp('view-iso-status-bar').rename_button.id);

        }

    });

    View.StaticIpTpl = function(id){
        var static_tpl = {
               border:false,
               items:[{
                        layout:'table',
                        frame:true,
                        layoutConfig: {columns:2},
                        items:[
                                {
                                labelAlign:'left',
                                layout:'form',
                                items:[
                                    new Ext.form.Radio({
                                        boxLabel: <?php echo json_encode(__('Static')) ?>, width:90,
                                        name: 'network_'+id+'_static',fieldLabel:'',hideLabel:true,
                                        inputValue: '1'
                                        ,listeners:{
                                            check:function(chkbox,checked){
                                                    var addrCmp = (this.ownerCt).ownerCt;
                                                    var addrFields = addrCmp.get(1);                                                    

                                                    addrFields.items.each(function(e){
                                                        if(!checked){
                                                            if(!e.isValid())
                                                                e.clearInvalid();
                                                            e.disable();
                                                        }else
                                                            e.enable();
                                                    });
                                            }
                                        }
                                    })
                                ]
                                },
                                {
                                labelWidth:120,
                                layout:'form',
                                items:[
                                    new Ext.form.TextField({
                                            fieldLabel: <?php echo json_encode(__('IP address')) ?>,
                                            name: 'network_'+id+'_ip',
                                            maxLength: 15,
                                            vtype:'ip_addr',
                                            allowBlank:false,
                                            disabled:true,
                                            width:100,
                                            listeners:{
                                                blur:function(field){
                                                    var ip_val = field.getValue();
                                                    var item_netmask = this.ownerCt.get(1);
                                                    var netmask_val = item_netmask.getValue();
                                                    var item_bcast = this.ownerCt.get(2);

                                                    //calculate network and broadcast address giving ip and subnet mask
                                                    if(field.isValid() && item_netmask.isValid())
                                                    {
                                                        var netbcast = network_calculator(ip_val,netmask_val);
                                                        item_bcast.setValue(netbcast[3]);
                                                    }
                                                }
                                            }
                                    }),
                                    new Ext.form.TextField({
                                            fieldLabel: <?php echo json_encode(__('Subnet mask')) ?>,
                                            name: 'network_'+id+'_netmask',
                                            maxLength: 15,
                                            width:100,
                                            vtype:'ip_addr',
                                            allowBlank:false,
                                            disabled:true,
                                            listeners:{
                                                blur:function(field){
                                                    var netmask_val = field.getValue();
                                                    var item_ip = this.ownerCt.get(0);
                                                    var ip_val = item_ip.getValue();
                                                    var item_bcast = this.ownerCt.get(2);

                                                    if(field.isValid() && item_ip.isValid())
                                                    {
                                                        var netbcast = network_calculator(ip_val,netmask_val);
                                                        item_bcast.setValue(netbcast[3]);                                                        
                                                    }

                                                }
                                            }
                                    }),
                                    new Ext.form.TextField({
                                            fieldLabel: <?php echo json_encode(__('Default gateway')) ?>,
                                            name: 'network_'+id+'_gateway',
                                            vtype:'ip_addr',
                                            allowBlank:false,
                                            disabled:true,
                                            maxLength: 15,
                                            width:100
                                    })

                                ]
                               }
                        ]// end layout table items
                }]};
        return static_tpl;
    };

    View.DnsTpl = function(id){

        var dns_tpl = {
               border:false,
               items:[{
                        layout:'table',
                        frame:true,
                        layoutConfig: {columns:2},
                        items:[
                                {
                                labelAlign:'left',
                                layout:'form',
                                items:[
                                    new Ext.form.Radio({
                                        boxLabel: <?php echo json_encode(__('Static')) ?>, width:90,
                                        name: id+'_staticdns',fieldLabel:'',hideLabel:true,
                                        inputValue: '1'
                                        ,listeners:{
                                            check:function(chkbox,checked){
                                                var addrCmp = (this.ownerCt).ownerCt;
                                                var addrFields = addrCmp.get(1);
                                                 
                                                addrFields.items.each(function(e){
                                                    if(!checked){
                                                        if(!e.isValid())
                                                            e.clearInvalid();
                                                        e.disable();
                                                    }else
                                                        e.enable();
                                                });
                                            }
                                        }
                                    })
                                ]
                                },
                                {
                                labelWidth:70,
                                layout:'form',
                                items:[
                                    new Ext.form.TextField({
                                            fieldLabel: <?php echo json_encode(__('Primary')) ?>,
                                            name: id+'_primarydns',
                                            maxLength: 15,
                                            vtype:'ip_addr',
                                            allowBlank:false,
                                            disabled:true,
                                            width:100
                                    }),
                                    new Ext.form.TextField({
                                            fieldLabel: <?php echo json_encode(__('Secondary')) ?>,
                                            name: id+'_secondarydns',
                                            maxLength: 15,
                                            vtype:'ip_addr',
                                            allowBlank:true,
                                            disabled:true,
                                            width:100
                                    })

                                 ]
                                }
                        ]//end layout table items
                }]};

        return dns_tpl;

    };


<?php if($etvamodel=='enterprise'):?>

    View.Connectivity = Ext.extend(Ext.form.FormPanel, {

        // defaults - can be changed from outside
        border:false
        ,labelWidth:100
        ,monitorValid:true
        ,labelAlign:'right'        
        ,initComponent:function() {

            var dns_source = new Ext.form.Radio({style:'margin-left:5px',
                    boxLabel: <?php echo json_encode(__('DHCP')) ?>, width:90, name: 'network_staticdns',disabled:true,
                    fieldLabel:'',hideLabel:true, inputValue: '0'});

            var dhcp_source = new Ext.form.Radio({style:'margin-left:5px',
                    boxLabel: <?php echo json_encode(__('DHCP')) ?>, width:90, name: 'network_va_management_static',
                    fieldLabel:'',hideLabel:true, inputValue: '0'
                    ,listeners:{
                            check:{scope:this,fn:function(chkbox,checked){

                                    var els = dns_source.el.up('form').select('input[name='+dns_source.el.dom.name+']');

                                    els.each(function(el){
                                        
                                        if(el.dom.id == dns_source.id){
                                            if(!checked) dns_source.disable();
                                            else dns_source.enable();
                                            dns_source.setValue(checked);

                                        }else{
                                            if(checked) Ext.getCmp(el.dom.id).disable();
                                            else Ext.getCmp(el.dom.id).enable();
                                            Ext.getCmp(el.dom.id).setValue(!checked);
                                        }
                                    }, this);
                            }}
                    }
            });            

            var config = {
                monitorValid:true
                ,buttons:[
                    {
                        text: __('Save'),
                        ref:'../saveBtn',
                        formBind:true,
                        scope:this,
                        handler:this.onSave
                    }
                    ,
                    {
                        text: __('Cancel'),
                        scope:this,
                        handler:function(){this.ownerCt.close();}
                    }]
                ,items:[
                    {xtype:'hidden',name:'node_id'},
                    {xtype:'hidden',name:'network_va_management_if'},
                    {
                        xtype: 'fieldset',
                        title: 'IP',
                        collapsible: false,
                        items:[dhcp_source,View.StaticIpTpl('va_management')]
                    },{
                        xtype: 'fieldset',
                        title: <?php echo json_encode(__('DNS')) ?>,
                        collapsible: false,
                        items:[dns_source,View.DnsTpl('network')]
                    }
                    ]
                //,frame:true
                ,scope:this
                ,bodyStyle:'padding:10px'
            };
        
            // apply config
            Ext.apply(this, Ext.apply(this.initialConfig, config));

            // call parent
            View.Connectivity.superclass.initComponent.apply(this, arguments);


        } // eo function initComponent
        ,onRender:function() {
            // call parent
            View.Connectivity.superclass.onRender.apply(this, arguments);

            // set wait message target
            this.getForm().waitMsgTarget = this.getEl();


        } // eo function onRender
        ,loadData:function(id){


            this.load({
                params:{id:id},                
                scope:this,
                url:<?php echo json_encode(url_for('node/jsongetIP'))?>,
                waitMsg: <?php echo json_encode(__('Please wait...')) ?>,
                failure:function(){                                        
                    this.disable();                                        
                }
                ,scope:this
            });

        }
        ,onSave:function(){
            
            if (this.form.isValid()) {

                var alldata = this.form.getValues();
                var network = new Object();
                var send_data = new Object();
                if(alldata['network_va_management_static']=='0'){
                    network = {
                        'bootp':'dhcp',
                        'if':alldata['network_va_management_if']};
                }else{
                    network = {
                        'ip':alldata['network_va_management_ip'],
                        'netmask':alldata['network_va_management_netmask'],
                        'gateway':alldata['network_va_management_gateway'],
                        'primarydns':alldata['network_primarydns'],
                        'secondarydns':alldata['network_secondarydns'],
                        'if':alldata['network_va_management_if']};
                }

                send_data['id'] = alldata['node_id'];
                send_data['network'] = Ext.encode(network);              

                var conn = new Ext.data.Connection({
                    listeners:{
                        // wait message.....
                        beforerequest:function(){
                            Ext.MessageBox.show({
                                title: <?php echo json_encode(__('Please wait...')) ?>,
                                msg: <?php echo json_encode(__('Changing IP...')) ?>,
                                width:300,
                                wait:true,
                                modal: false
                            });
                        },// on request complete hide message
                        requestcomplete:function(){Ext.MessageBox.hide();}
                        ,requestexception:function(c,r,o){
                            Ext.MessageBox.hide();
                            Ext.Ajax.fireEvent('requestexception',c,r,o);}
                    }
                });// end conn

                conn.request({
                    url:<?php echo json_encode(url_for('node/jsonsetIP'))?>,                    
                    params:send_data,                    
                    // everything ok...
                    success: function(resp,opt){

                        (this.ownerCt).fireEvent('ipSave',resp);

                    },scope:this
                });// END Ajax request


            } else{
                Ext.MessageBox.show({
                    title: <?php echo json_encode(__('Error!')) ?>,
                    msg: <?php echo json_encode(__('Please fix the errors noted!')) ?>,
                    buttons: Ext.MessageBox.OK,
                    icon: Ext.MessageBox.WARNING
                });
            }
        }



    }); // eo extend

<?php endif; ?>


    View.KeyMap = Ext.extend(Ext.form.FormPanel, {

        // defaults - can be changed from outside
        border:false
        ,labelWidth:100
        ,labelAlign:'right'
        ,initComponent:function() {


            var kcmb = new Setting.VNC.keymapCombo();

            var keymap_default = new Ext.form.Hidden({name:'keymap_default'});

            var config = {
                monitorValid:true
                ,items:[keymap_default,{xtype:'checkbox',hidden: !this.isLeaf, name:'vnc_keymap_default',boxLabel: <?php echo json_encode(__('Use default keymap')) ?>,listeners:{
                            'check':function(cbox,ck){
                                if(ck){

                                    var def_val = keymap_default.getValue();
                                    kcmb.setValue(def_val);
                                    kcmb.disable();
                                }else{
                                    kcmb.enable();
                                }

                            }
                }},kcmb,{xtype:'displayfield',hidden: !this.isLeaf, name:'vnc_keymap_tooltip',value: <?php echo json_encode(__('This changes will only take effect after restarting virtual machine.')) ?>}]
                ,frame:true
                ,scope:this
                ,bodyStyle:'padding-top:10px'
            };


            // apply config
            Ext.apply(this, Ext.apply(this.initialConfig, config));

            // call parent
            View.KeyMap.superclass.initComponent.apply(this, arguments);


        } // eo function initComponent
        ,onRender:function() {
            // call parent
            View.KeyMap.superclass.onRender.apply(this, arguments);

            // set wait message target
            this.getForm().waitMsgTarget = this.getEl();


        } // eo function onRender
        ,loadData:function(){


            this.load({
                url:this.url,
                waitMsg: <?php echo json_encode(__('Please wait...')) ?>,
                params:{params:Ext.encode(['vnc_keymap'])},
                success: function ( form, action ) {
                    var rec = action.result;

                    if(!Ext.isEmpty(rec.data['vnc_keymap_default'])){                        

                        if(rec.data['vnc_keymap_default'])
                            (this.getForm().findField('vnc_keymap')).disable();
                    }                                        
                    this.getForm().loadRecord(rec);
                },scope:this
            });

        }
        ,onSave:function(){

            if (this.form.isValid()) {

                var alldata = this.form.getValues();
                var allRecords = [];
                var send_data = new Object();
                var msg = <?php echo json_encode(__('Updating VNC keymap...')) ?>;
                send_data['method'] = 'update';

               var checkbox = this.getForm().findField('vnc_keymap_default');
               if(checkbox.hidden){                                      
                   allRecords.push({'param':'vnc_keymap','value':alldata['vnc_keymap']});
                   send_data['settings'] = Ext.encode(allRecords);                   

               }else{                   
                   send_data['vnc_keymap'] = alldata['vnc_keymap'];
                   send_data['vnc_keymap_default'] = 0;
                   if(alldata['vnc_keymap_default']){
                       send_data['vnc_keymap_default'] = 1;
                       send_data['vnc_keymap'] = alldata['keymap_default'];
                   }

               }

                var conn = new Ext.data.Connection({
                    listeners:{
                        // wait message.....
                        beforerequest:function(){
                            Ext.MessageBox.show({
                                title: <?php echo json_encode(__('Please wait...')) ?>,
                                msg: msg,
                                width:300,
                                wait:true,
                                modal: false
                            });
                        },// on request complete hide message
                        requestcomplete:function(){Ext.MessageBox.hide();}
                        ,requestexception:function(c,r,o){
                            Ext.MessageBox.hide();
                            Ext.Ajax.fireEvent('requestexception',c,r,o);}
                    }
                });// end conn

                conn.request({
                    url: this.url,
                    params:send_data,
                    // everything ok...
                    success: function(resp,opt){

                     (this.ownerCt).fireEvent('keymapSave',resp);

                    },scope:this
                });// END Ajax request


            } else{                
                Ext.MessageBox.show({
                    title: <?php echo json_encode(__('Error!')) ?>,
                    msg: <?php echo json_encode(__('Please fix the errors noted!')) ?>,
                    buttons: Ext.MessageBox.OK,
                    icon: Ext.MessageBox.WARNING
                });
            }
        }



    }); // eo extend



    View.HostName = Ext.extend(Ext.form.FormPanel, {

        // defaults - can be changed from outside
        border:false
        ,height:100
        ,labelWidth:100
       // ,labelAlign:'right'
        ,initComponent:function() {            
            

            var config = {
                monitorValid:true
                ,items:[
                    {xtype:'hidden', name:'id'}
                    ,{xtype:'textfield',fieldLabel:'Hostname', allowBlank:false,
                      name:'name',
                      listeners:{
                            specialkey:{scope:this,fn:function(field,e){

                                            if(e.getKey()==e.ENTER) this.onSave();
                
                            }}
                    }}
                ]
                ,frame:true
                ,scope:this
                ,bodyStyle:'padding:10px'
                ,buttons:[{
                                text: __('Save'),
                                formBind:true,
                                scope:this,
                                handler:function(){
                                    this.onSave();}
                                },
                                {
                                    text: __('Cancel'),
                                    scope:this,
                                    handler:function(){
                                        this.fireEvent('onCancel');
                                    }
                                }
                         ]
                //,buttons:[{text:'ok'}]
            };


            // apply config
            Ext.apply(this, Ext.apply(this.initialConfig, config));

            // call parent
            View.HostName.superclass.initComponent.apply(this, arguments);


        } // eo function initComponent
        ,onRender:function() {
            // call parent
            View.HostName.superclass.onRender.apply(this, arguments);

            // set wait message target
            this.getForm().waitMsgTarget = this.getEl();


        } // eo function onRender
        ,loadData:function(id){


            this.load({
                url:<?php echo json_encode(url_for('node/jsonHostname')) ?>,
                waitMsg: <?php echo json_encode(__('Please wait...')) ?>,
                params:{id:id},
                success: function ( form, action ) {
                    var name = form.findField('name');
                    name.focus(true,20);
                    
                },scope:this
            });

        }
        ,onSave:function(){

            if (this.form.isValid()) {

                var alldata = this.form.getValues();
                
                var send_data = {'method':'update','id':alldata['id'],'name':alldata['name']};

                var conn = new Ext.data.Connection({
                    listeners:{
                        // wait message.....
                        beforerequest:function(){
                            Ext.MessageBox.show({
                                title: <?php echo json_encode(__('Please wait...')) ?>,
                                msg: <?php echo json_encode(__('Updating hostname...')) ?>,
                                width:300,
                                wait:true,
                                modal: false
                            });
                        },// on request complete hide message
                        requestcomplete:function(){Ext.MessageBox.hide();}
                        ,requestexception:function(c,r,o){
                            Ext.MessageBox.hide();
                            Ext.Ajax.fireEvent('requestexception',c,r,o);}
                    }
                });// end conn

                conn.request({
                    url:<?php echo json_encode(url_for('node/jsonHostname')) ?>,
                    params:send_data,
                    // everything ok...
                    success: function(resp,opt){

                        var response = Ext.util.JSON.decode(resp.responseText);
                        Ext.ux.Logger.info(response['agent'],response['response']);
                        
                        this.fireEvent('onSave');

                    },scope:this
                });// END Ajax request


            } else{
                Ext.MessageBox.show({
                    title: <?php echo json_encode(__('Error!')) ?>,
                    msg: <?php echo json_encode(__('Please fix the errors noted!')) ?>,
                    buttons: Ext.MessageBox.OK,
                    icon: Ext.MessageBox.WARNING
                });
            }
        }



    }); // eo extend



    /*
    *
    * Global AJAX request exception
    *
    */
    Ext.Ajax.timeout = 2700000; //45 minutes
 //   Ext.Ajax.timeout = 30000; //1:2 minute
    Ext.data.Connection.prototype.timeout = Ext.Ajax.timeout;
    Ext.Ajax.on('requestexception', function(conn,response,options){

        if(response.status==500){
            Ext.ux.Logger.error(response.statusText);
            return;
        }

        var responseText = new Object();        

        if(response.responseText) responseText = Ext.util.JSON.decode(response.responseText);
        if(!responseText['agent']) responseText['agent'] = 'ETVA';
        if(!responseText['error']) responseText['error'] = 'Error!';
        if(!responseText['info']) responseText['info'] = 'Error!';

        var agent = responseText['agent'];

        var isArray = ((responseText['error']).constructor.toString().indexOf("Array") != -1);



        if(!isArray && responseText['error']){

            if((typeof responseText['error'])=='string'){

                if(response.status==0)                    
                    Ext.ux.Logger.error(agent, response.statusText);                
                else{
                    Ext.MessageBox.hide();                    
                    Ext.ux.Logger.error(agent, responseText['error']);
                }

            }

            //not logged in anymore
            if(response.status==401){                                
                
                (function(){
                    Ext.MessageBox.show({
                        title: 'Login Error',
                        msg: 'Need to login',
                        buttons: Ext.MessageBox.OK,
                        fn: function(){                            
                            window.location = <?php  echo json_encode(url_for('@homepage',true)); ?>;},
                        icon: Ext.MessageBox.ERROR
                    });
                }).defer(200);
                
                return;
            }

            //TCP error returned
            if(response.status==404){                
                Ext.MessageBox.show({
                    title: 'Error '+agent,
                    msg: '<center><b>The request could not be accomplished!</b></center><br>'+responseText['error'],
                    buttons: Ext.MessageBox.OK,
                    icon: Ext.MessageBox.ERROR
                });
                return;
            }

            if(response.status==400){
                
                if(!responseText['info']) responseText['info'] = responseText['error'];

                Ext.MessageBox.show({
                    title: String.format(<?php echo json_encode(__('Error {0}')) ?>,agent),
                    msg: responseText['info'],
                    buttons: Ext.MessageBox.OK,
                    icon: Ext.MessageBox.ERROR
                });
                return;
            }

        }


    }, this);

    Ext.onReady(View.init, View, true);


</script>

<div id="header">
    <h1>Central Management - ETVA</h1>
    <div id="topBar"></div>
</div>
<div id="dynPageContainer"></div>
<div id="notificationDiv" style="display:none"></div>
