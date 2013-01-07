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
use_javascript('ux/Spinner.js'); //plugin for Spinner
use_javascript('ux/SpinnerField.js'); //plugin for Spinner
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
include_partial('node/storage');
include_partial('cluster/changename');

/*
 * check if file locale exist and load
 */
$lang_js = sfFinder::type('file')->name($sf_user->getCulture().'.js')->in(sfConfig::get('sf_web_dir').'/js');
if($lang_js) use_javascript("locale/".$sf_user->getCulture().".js");

$locale_js = array();
if( sfFinder::type('file')->name('ext-lang-'.$sf_user->getCulture().'.js')->in(sfConfig::get('sf_web_dir').'/js') ){
    $locale_js['js'] = 'locale/ext-lang-'.$sf_user->getCulture().'.js';
}
$sfExtjs3Plugin = new sfExtjs3Plugin(array('theme'=>'blue'),$locale_js/*array
                              (
                                'js' => 'locale/ext-lang-'.$sf_user->getCulture().'.js'
                              )*/
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

//include_partial('node/storage');

/*
 * include vnc combo
 */
include_partial('setting/Setting_VNC_keymapCombo');

if($sf_user->getAttribute('etvamodel')!='standard')
    include_partial('server/migrate');

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
                   new_css_state = ['active','icon-vm-stat-ok'];
                   remove_css_state = ['some-active','no-active','icon-vm-stat-nok'];
                }

                if((data['vm_state']=='running' && (data['agent_port'] && !data['state'])) ||
                   (data['vm_state']!='running' && (data['agent_port'] && data['state'])) ){
                   new_css_state = ['some-active','icon-vm-stat-ok'];
                   remove_css_state = ['active','no-active','icon-vm-stat-nok'];
                }
                

                if((data['vm_state']!='running' && (data['agent_port'] && !data['state'])) ||
                   (data['vm_state']!='running' && !data['agent_port']) ){
                   new_css_state = ['no-active','icon-vm-stat-nok'];
                   remove_css_state = ['active','some-active','icon-vm-stat-ok'];
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

                // Cluster name vtype
                //Ext.apply(Ext.form.VTypes, {
                //    clusternameVal: /^(1|2)\d{3}/,
                //    clusternameMask: /\d/,
                //    clusternameText: 'Incorrect year format',
                //    clustername: function(v){
                //        return this.yearVal.test(v);
                //    }
                //});


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
                            }
                            <?php if($etvamodel=='enterprise'):?>
                            ,{
                                text:<?php echo json_encode(__('Cluster Setup Wizard')) ?>
                                ,id:'clusterwizardBtn'
                                ,url:<?php echo json_encode(url_for('cluster/View_ClusterWizard')); ?>
//                                ,call:'View.FirstTimeWizard'
                                ,call:'Cluster.Create'
                                ,callback:function(item){
//                                    alert("click");
                                    new Cluster.Create.Main();
                                    //new View.FirstTimeWizard.Main();
                                }
                                ,handler:this.loadComponent
                                ,scope:this
                            }
                            <?php endif ?>
                            ,{
                                text:<?php echo json_encode(__('System preferences')) ?>
                                ,url:<?php echo json_encode(url_for('setting/view')); ?>
                                ,call:'Setting.Main'
                                ,callback:function(item){
                                    var main = new Setting.Main({title:item.text});
                                    var win = Ext.getCmp('setting-main');
                                    win.on('beforeclose', function(){
                                        var ftw_pref = Ext.getCmp('ft-wiz-preferences');     //notify first time wizard preferences card
                                        if(ftw_pref){
                                            ftw_pref.fireEvent('reloadData', ftw_pref);
                                        }
                                    });
                                }
                                ,handler:this.loadComponent
                                ,scope:this
                                ,id: 'menuitm-settings'
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
                            },{
                                text:<?php echo json_encode(__('Shutdown Central Management')) ?>
                                ,url:<?php echo json_encode(url_for('setting/jsonShutdown')); ?>
//                                ,call:'Setting.Shutdown'
//                                ,callback:function(item){
//                                    var main = new Setting.Main({title:item.text});
//                                    var win = Ext.getCmp('setting-main');
//                                    win.on('beforeclose', function(){
//                                        var ftw_pref = Ext.getCmp('ft-wiz-preferences');     //notify first time wizard preferences card
//                                        if(ftw_pref){
//                                            ftw_pref.fireEvent('reloadData', ftw_pref);
//                                        }
//                                    });
//                                }
                                ,handler: function(){

                                    //this.loadComponent
                                    Ext.Msg.show({
                                        title: <?php echo json_encode(__('Shutdown')) ?>,
                                        buttons: Ext.MessageBox.YESNOCANCEL,
                                        msg: <?php echo json_encode(__('Shutdown Central Management?')) ?>,
                                        icon: Ext.MessageBox.WARNING,
                                        fn: function(btn){
                                            if (btn == 'yes'){
            
                                                var conn = new Ext.data.Connection({
                                                    listeners:{
                                                        // wait message.....
                                                        beforerequest:function(){
                                                            Ext.MessageBox.show({
                                                                title: <?php echo json_encode(__('Please wait...')) ?>,
                                                                msg: <?php echo json_encode(__('Shutting down...')) ?>,
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
            
                                                conn.request({
                                                    url: <?php echo json_encode(url_for('setting/jsonShutdown')) ?>,
//                                                    params: {id: node.id},
                                                    success: function(resp,opt) {
            //                                            Ext.getCmp('view-nodes-panel').removeNode(node.id);
            //                                            Ext.getCmp('view-main-panel').remove('view-center-panel-'+node.id);                 
                                                        Ext.Msg.alert(<?php echo json_encode(__('Information')) ?>, <?php echo json_encode(__('Central Management server is shutting down!')) ?>);
                                                    },
                                                    failure: function(resp,opt) {
                                                        Ext.Msg.alert(<?php echo json_encode(__('Error!')) ?>, <?php echo json_encode(__('Unable to shutdown!')) ?>);
                                                    }
                                                });// END Ajax request
                                            }//END button==yes
                                        }// END fn
                                    }); //END Msg.show
                                }
                                ,scope:this
                                ,id: 'menuitm-shutdown'
                            }
                           ,buttonLogout
                    ]});
                    
                var systemAdminMenu = new Ext.Action({text: <?php echo json_encode(__('System Administration')) ?>,
                    menu: 'adminMenu' // assign the object by id
                });

                //var toolsMenu = new Ext.menu.Menu({
                var toolsMenu = new Ext.ux.TooltipMenu({
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
                                tooltip: {text: <?php echo json_encode(__('Increases the frequency of the nodes status verification')) ?>}
                                //tooltip: {text: <?php echo json_encode(__('Increases the frequency of the nodes status verification')) ?>}
                                ,handler: function(){View.monitorAlive();}
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
                    buttons: [
                        <?php if($sf_user->getGuardUser()->getIsSuperAdmin()): ?>
                        systemAdminMenu,
                        admintoolsMenu,//one to N left buttons
                        <?php endif; ?>
                            
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
                        rootVisible:false,

                        animate:true,
                        lines:false,
                        autoScroll:true,
                        enableDD:true,
                        listeners: {
                            nodedragover: function(e){
                                                //console.log(e);
                                            <?php if($sf_user->getAttribute('etvamodel')!='standard'): ?>

                                                //unaccepted node move between clusters
                                                if( e.point=='append' && e.dropNode.attributes.initialize == 'pending' && e.target.attributes.type == 'cluster'
                                                        && e.dropNode.parentNode.parentNode.attributes.id != e.target.attributes.id)
                                                    return true;

                                               //live migrate
                                                if( e.point=='append' && e.target.attributes.type == 'node' && e.target.attributes.state == 1
                                                        && e.target.attributes.can_create_vms
                                                        && e.target.attributes.contextmenu
                                                        && e.dropNode.attributes.all_shared && !e.dropNode.attributes.has_snapshots
                                                        && e.dropNode.parentNode.attributes.id != e.target.attributes.id
                                                        && e.dropNode.parentNode.attributes.cluster_id == e.target.attributes.cluster_id )
                                                    return true;

                                            <?php endif; ?>

                                                // unassign
                                                if( e.point=='append' && e.dropNode.parentNode.attributes.type == 'node' && e.target.attributes.type == 'unassignednode'
                                                        && e.target.attributes.state == 1 && e.target.attributes.contextmenu
                                                        && e.dropNode.parentNode.attributes.id != e.target.attributes.id
                                                        && e.dropNode.parentNode.attributes.cluster_id == e.target.attributes.cluster_id )
                                                    return true;

                                                // assign
                                                if( e.point=='append' && e.dropNode.parentNode.attributes.type == 'unassignednode' && e.target.attributes.type == 'node'
                                                        && e.target.attributes.state == 1 && e.target.attributes.contextmenu
                                                        && e.target.attributes.can_create_vms
                                                        && ( e.dropNode.attributes.nodes_toassign.indexOf(e.target.attributes.id)!=-1 ||
                                                                !e.dropNode.attributes.has_disks ||
                                                                (e.dropNode.attributes.all_shared && !e.dropNode.attributes.has_snapshots) ||
                                                                (e.dropNode.attributes.node_id == e.target.attributes.id) )
                                                        && e.dropNode.parentNode.attributes.id != e.target.attributes.id
                                                        && e.dropNode.parentNode.attributes.cluster_id == e.target.attributes.cluster_id )
                                                    return true;

                                                return false;
                                            }
                            ,nodedrop: function(e){
                                            var type = e.dropNode.attributes.type;

                                            if(type == 'node'){
                                                //unaccepted node move between clusters
                                                this.moveUnacceptedNode(e);
                                            }else if(type == 'server'){
                                                
                                                if( e.target.attributes.type == 'unassignednode' ){
                                                    var sId = e.dropNode.id;
                                                    var server_id = sId.replace('s','');
                                                    var server_name = e.dropNode.text;
                                                    Ext.Msg.show({
                                                        title: <?php echo json_encode(__('Unassign server')) ?>,
                                                        buttons: Ext.MessageBox.YESNOCANCEL,
                                                        msg: String.format(<?php echo json_encode(__('Unassign server {0} ?')) ?>,server_name),
                                                        icon: Ext.MessageBox.WARNING,
                                                        fn: function(btn){
                                                            if (btn == 'yes'){
                                                                var conn = new Ext.data.Connection({
                                                                    listeners:{
                                                                        // wait message.....
                                                                        beforerequest:function(){
                                                                            Ext.MessageBox.show({
                                                                                title: <?php echo json_encode(__('Please wait...')) ?>,
                                                                                msg: <?php echo json_encode(__('Unassigning server...')) ?>,
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

                                                                conn.request({
                                                                    url: <?php echo json_encode(url_for('server/jsonUnassign')) ?>,
                                                                    params: {id: server_id},
                                                                    success: function(resp,opt) {
                                                                        Ext.getCmp('view-nodes-panel').removeNode(server_id);
                                                                        Ext.getCmp('view-main-panel').remove('view-center-panel-'+server_id);                 

                                                                    },
                                                                    failure: function(resp,opt) {
                                                                        Ext.Msg.alert(<?php echo json_encode(__('Error!')) ?>, <?php echo json_encode(__('Unable to unassign server!')) ?>);
                                                                        Ext.getCmp('view-nodes-panel').reload({'server_id':server_id});
                                                                    }
                                                                });// END Ajax request
                                                            } else {//END button==yes
                                                                Ext.getCmp('view-nodes-panel').reload({'server_id':server_id});
                                                            }
                                                        }// END fn
                                                    }); //END Msg.show
                                                } else if( e.dropNode.attributes.unassigned ){
                                                    var sId = e.dropNode.id;
                                                    var server_id = sId.replace('s','');
                                                    var server_name = e.dropNode.text;

                                                    Ext.Msg.show({
                                                        title: <?php echo json_encode(__('Assign server')) ?>,
                                                        buttons: Ext.MessageBox.YESNOCANCEL,
                                                        msg: String.format(<?php echo json_encode(__('Assign server {0} ?')) ?>,server_name),
                                                        icon: Ext.MessageBox.WARNING,
                                                        fn: function(btn){
                                                            if (btn == 'yes'){

                                                                var conn = new Ext.data.Connection({
                                                                    listeners:{
                                                                        // wait message.....
                                                                        beforerequest:function(){
                                                                            Ext.MessageBox.show({
                                                                                title: <?php echo json_encode(__('Please wait...')) ?>,
                                                                                msg: <?php echo json_encode(__('Assigning server...')) ?>,
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

                                                                conn.request({
                                                                    url: <?php echo json_encode(url_for('server/jsonAssign')) ?>,
                                                                    params: {id: server_id, 'nid': e.target.attributes.id},
                                                                    success: function(resp,opt) {
                                                                        Ext.getCmp('view-nodes-panel').removeNode(server_id);
                                                                        Ext.getCmp('view-main-panel').remove('view-center-panel-'+server_id);                 

                                                                    },
                                                                    failure: function(resp,opt) {
                                                                        Ext.Msg.alert(<?php echo json_encode(__('Error!')) ?>, <?php echo json_encode(__('Unable to assign server!')) ?>);
                                                                        Ext.getCmp('view-nodes-panel').reload({'server_id':server_id});
                                                                    }
                                                                });// END Ajax request
                                                            } else {//END button==yes
                                                                Ext.getCmp('view-nodes-panel').reload({'server_id':server_id});
                                                            }
                                                        }// END fn
                                                    }); //END Msg.show
                                                } else {
                                                    //live migrate
                                                    this.migrateServer(e);
                                                }
                                            }
                                            

                                            
                                        }
                            ,refresh: function(){
                                            this.root.reload();
                                        }
                        },
                        migrateServer: function(e){
                            var sId = e.dropNode.id;
                            var server_id = sId.replace('s','');
                            var server_name = e.dropNode.text;
                            var record = {data:{'id':server_id,'name':server_name, 'nodes_cb': e.target.text , 'target_name': e.target.text , 'target_id': e.target.attributes.id}};

                            var type = (e.dropNode.attributes.vm_state == 'running') ? 'migrate' : 'move';
                            var text = (e.dropNode.attributes.vm_state == 'running') ?
                                            <?php echo json_encode(__('Migrate server')) ?>
                                            : <?php echo json_encode(__('Move server')) ?>;

                            var window = new Server.Migrate.Window({title:text,type:type, parent:NodePanel.id});
                            window.on('close',function(){
                                                            Ext.getCmp('view-nodes-panel').reload({'server_id':server_id});
                                                    });
                            window.show();
                            window.loadData(record);
                        },
                        moveUnacceptedNode: function(e){
                            var node_id = e.dropNode.id;
                            var cluster_id = e.target.id;
                            cluster_id = cluster_id.replace('d','');

                            var send_data = {'to_cluster_id': cluster_id, 'node_id': node_id};
                            
                            var conn = new Ext.data.Connection({
                                listeners:{
                                    // wait message.....
                                    beforerequest:function(){
                                        Ext.MessageBox.show({
                                            title: <?php echo json_encode(__('Please wait...')) ?>,
                                            msg: <?php echo json_encode(__('Moving node...')) ?>,
                                            width:300,
                                            wait:true
                                        });
                                    },// on request complete hide message
                                    requestcomplete:function(){
                                        Ext.MessageBox.hide();

                                    }
                                    ,requestexception:function(c,r,o){
                                        Ext.MessageBox.hide();
                                        Ext.Ajax.fireEvent('requestexception',c,r,o);}
                                }
                            });// end conn

                            conn.request({
                                url:<?php echo json_encode(url_for('cluster/jsonMoveNode')) ?>,
                                params:send_data,
                                // everything ok...
                                success: function(resp,opt){
                                    var response = Ext.util.JSON.decode(resp.responseText);
                                    Ext.ux.Logger.info(response['agent'],response['info']);
                                    var pan = Ext.getCmp('view-nodes-panel')
//                                    pan.on('beforeload', function(node){
//                                        console.log(node);
//                                        console.log("EVENT: " + e.target.id);
//                                        node.expand();
    
                                        // Search for node and expand
//                                        var pan = Ext.getCmp('view-nodes-panel');
//                                        console.log(e.target.id);
//                                        console.log(pan);
//
//                                        var root  = Ext.getCmp('view-nodes-panel').root;
//                                        var f = root.findChild('id',e.target.id, true );
//                                        f.expand(true);
////                                        alert('bla');
//                                        return false;
//                                    });
//                                    pan.reload();

                                },scope:this
                                ,failure: function(o) {
                                    var response = Ext.util.JSON.decode(o.responseText);
                                    Ext.getCmp('view-nodes-panel').reload();
                               }
                            });// END Ajax request
                        },

                        loader: new Ext.tree.TreeLoader({
                            clearOnLoad:true,
                            dataUrl: <?php echo json_encode(url_for('server/jsonTree',false)); ?>,
                            listeners:{
                                beforeload:function(){
                                     //View.checkState();
                                }
                                ,load:function(obj, node, resp ){
                                    var response = Ext.decode(resp.responseText);

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
                                    click: function(){
                                                var attrs;
                                                var currentNode = nodesPanel.getSelectionModel().getSelectedNode();
                                                if( currentNode ){
                                                    attrs = new Object();
                                                    if( currentNode.attributes.type == 'server' )
                                                        attrs.server_id = currentNode.attributes.id;
                                                    else if( currentNode.attributes.type == 'node' )
                                                        attrs.node_id = currentNode.attributes.id;
                                                    else if( currentNode.attributes.type == 'cluster' )
                                                        attrs.cluster_id = currentNode.attributes.id;
                                                    else if( currentNode.attributes.type == 'unassignednode' )
                                                        attrs.cluster_id = currentNode.parentNode.attributes.id;

                                                    attrs.select = true;
                                                }
                                                this.reload(attrs);
                                                //this.root.reload(attrs);
                                            }
                                    ,scope:this
                                }
                            },{
                                id: 'plus',
                                qtip: <?php echo json_encode(__('Expand/collapse all nodes')) ?>,
                                on:{
                                    click: function(){
                                        var nodes = Ext.getCmp('view-nodes-panel').root.childNodes;
                                        for(node in nodes){
                                            if(!isNaN(node)){
                                                if(this.clapse){
                                                    nodes[node].collapse(true);
                                                }else{
                                                    nodes[node].expand(true);
                                                }
                                            }
                                        }
                                        
                                        if(this.clapse != undefined){
                                            this.clapse = !this.clapse;
                                        }else{
                                            this.clapse = true;
                                        }
                                    }
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
//                            alert(sm.getSelectedNode());
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
                    //scope:this,
                    bbar: [
                    '->',
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
                            specialkey:{scope:this,fn:function(field,e){
                                if(e.getKey()==e.ENTER){
                                    var nodesPanel = Ext.getCmp('view-nodes-panel');
                                    var no_name = field.getValue(); // Treenode name =P

                                    if(no_name == ''){
                                        nodesPanel.fireEvent('refresh');                                     
                                    }else{
                                        var root = nodesPanel.getRootNode();
                    
                                        var search_node = function(node, name){
                                           
                                            // Leaf nodes
                                            if(!node.hasChildNodes()){
                                                var patt=new RegExp(name,'ig');
                                                if(node.attributes['text'].match(patt) != null){
                                                    node.ensureVisible();
                                                    node.ui.addClass('RedText');
                                                    node.attributes.cls = 'RedText';
                                                    return false;
                                                }else{
                                                    node.ui.removeClass('RedText');
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
                                                
                                                if(collapse)//{
                                                    node.collapse();
                                                
                                                var patt=new RegExp(name,'ig');
                                            
                                                if(node.attributes['text'].match(patt) != null){
                                                    node.ensureVisible();
                                                    node.ui.addClass('RedText');
                                                    node.attributes.cls = 'RedText';
                                                    return false;
                                                }else{
                                                    node.ui.removeClass('RedText');
                                                    return collapse;
                                                }
                                            }
                                            
                                            return collapse;
                                            
                                        }
                                        search_node(root, no_name);
                                    }
                                }

                            }}                     
                            ,
                            render: function(c) {
                                Ext.QuickTips.register({
                                target: c.getEl(),
                                    text: <?php echo json_encode(__('Press enter to search')) ?>
                                });
                            }
                        }
//                            keypress: {buffer:100, fn:function(textfield, evtobj) {
//                                   alert(textfield.getValue());
//                                }
//                            }
                    }],
                    /*
                     * Create context menu
                     */
                    onContextMenu : function(node, e){
                        if( node.attributes.type == 'unassignednode' ) // no context menu for unassigned node
                            return false;

                        var items = [
                                    {
                                        html: '<b class="menu-title">'+<?php echo json_encode(__('Cluster')) ?>+'</b>',
                                        ref:'ttl_cluster',
                                        border: false,
                                        xtype: "panel"
                                    },{
                                        text: <?php echo json_encode(__('Rename cluster')) ?>,
                                        tooltip: {text: <?php echo json_encode(__('This will change the cluster name. "Default" cluster name cannot be changed.')) ?>},
                                        ref:'btn_rename_cluster',
                                        disabled:false,
                                        hidden: true,
                                        scope:this,
                                        handler:function(btn,e){
                                            this.setClusterName(btn,this.ctxNode);
                                        }
                                    },{
                                        text: <?php echo json_encode(__('Edit cluster')) ?>,
                                        tooltip: {text: <?php echo json_encode(__('This will change the cluster settings. "Default" cluster name cannot be changed.')) ?>},
                                        ref:'btn_edit_cluster',
                                        disabled:false,
                                        hidden: true,
                                        scope:this,
                                        url:<?php echo(json_encode(url_for('cluster/edit')))?>,
                                        call:'Cluster.Edit',
                                        callback:function(btn,e,cluster){
                                            var cId = cluster.id;
                                            var cluster_id = cId.replace('d','');

                                            var name = cluster.text;
                                            var window = new Cluster.Edit.Window({
                                                                                    title: String.format(<?php echo json_encode(__('Edit cluster {0}')) ?>,name), 'cluster_id': cluster_id});
                                            window.on({'show':function(){window.loadData({id:cluster_id});}
                                                        ,'onCancel':function(){window.close();}
                                                        ,'onSave':function(){window.close();

                                                            Ext.getCmp('view-nodes-panel').reload({ 'cluster_id': cluster_id });
                                                            /*Ext.getCmp('view-nodes-panel').getRootNode().reload(function(){
                                                                var centerElem = Ext.getCmp('view-main-panel').findById('view-center-panel-'+cluster_id);
                                                                if(centerElem && centerElem.isVisible())
                                                                {

                                                                    this.selectNode(cluster_id);
                                                                    centerElem.fireEvent('beforeshow');
                                                                }


                                                            },this);*/

                                                        }
                                                        ,scope:this
                                            });
                                            window.show();

                                        }
                                        ,handler: function(btn,e){View.loadComponent(btn,e,this.ctxNode);}
                                    },{
                                        text: <?php echo json_encode(__('Remove cluster')) ?>,
                                        tooltip: {text: <?php echo json_encode(__('This will only remove data from Central Management')) ?>},
                                        ref:'btn_remove_cluster',
                                        disabled:true,
                                        scope:this,
                                        iconCls: 'icon-remove',
                                        handler:function(btn,e){
                                            this.deleteCluster(btn,this.ctxNode);
                                        }
                                    },
//                                    '<b class="menu-title">'+<?php echo json_encode(__('Initialization')) ?>+'</b>'
                                    {
                                        html: '<b class="menu-title">'+<?php echo json_encode(__('Initialization')) ?>+'</b>',
                                        border: false,
                                        ref:'ttl_initialization',
                                        xtype: "panel"
                                    },{
                                        text: <?php echo json_encode(__('Authorize')) ?>,
                                        ref:'btn_authorize',
                                        scope: this,
                                        disabled:false,
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
//                                    ,'-'
//                                    ,{
//                                        xtype: 'tbseparator'    //separador
//                                    }
//                                    ,'<b class="menu-title">Node</b>'
                                    ,{
                                        html: '<b class="menu-title">Node</b>',
                                        border: false,
                                        ref:'ttl_node',
                                        xtype: "panel"
                                    }
                                    ,{
                                        id:'load-node',
                                        ref:'btn_loadnode',
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
                                    },{
                                        /*text: <?php echo json_encode(__('Change hostname')) ?>,
                                        scope: this,
                                        ref:'btn_hostname',
                                        disabled:true,
                                        handler:function(btn,e){
                                            this.setHostname(btn,this.ctxNode);
                                        }
                                    },{*/
                                        text: <?php echo json_encode(__('Edit node')) ?>,
                                        tooltip: {text: <?php echo json_encode(__('This will change the node settings.')) ?>},
                                        ref:'btn_edit_node',
                                        disabled:false,
                                        hidden: true,
                                        scope:this,
                                        url:<?php echo(json_encode(url_for('node/edit')))?>,
                                        call:'Node.Edit',
                                        callback:function(btn,e,node){
                                            var nId = node.id;
                                            //var node_id = nId.replace('d','');
                                            var node_id = nId;

                                            var name = node.text;
                                            var window = new Node.Edit.Window({
                                                                                    title: String.format(<?php echo json_encode(__('Edit node {0}')) ?>,name), 'node_id': node_id});
                                            window.on({'show':function(){window.loadData({id:node_id});}
                                                        ,'onCancel':function(){window.close();}
                                                        ,'onSave':function(){window.close();
                                                            Ext.getCmp('view-nodes-panel').reload({ 'node_id': node_id });
                                                        }
                                                        ,scope:this
                                            });
                                            window.show();

                                        }
                                        ,handler: function(btn,e){View.loadComponent(btn,e,this.ctxNode);}
                                    },
                                    {
                                        text: <?php echo json_encode(__('Remove node')) ?>,                                        
                                        tooltip: {text: <?php echo json_encode(__('This will only remove data from Central Management')) ?>},
                                        ref:'btn_remove',
                                        disabled:true,
                                        scope:this,
                                        iconCls: 'icon-remove',
                                        handler:function(btn,e){
                                            this.deleteNode(btn,this.ctxNode);
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
                                    }
                                    <?php if($sf_user->getGuardUser()->getIsSuperAdmin()): ?>
                                    ,{
                                        text: <?php echo json_encode(__('Set permissions')) ?>,
                                        tooltip: {text: <?php echo json_encode(__("This will change permissions for the node's parent (cluster)")) ?>},
                                        ref:'btn_permission',
                                        disabled:false,
                                        scope:this,
                                        iconCls: 'icon-lockedit',
                                        handler:function(btn,e){
                                            this.changePerms(btn,this.ctxNode);
                                        }
                                    }
                                    <?php endif; ?>
                                    ,{
                                        text :<?php echo json_encode(__('Node status')) ?>,
                                        ref:'btn_node_status',
                                        menu:[
                                            {text:<?php echo json_encode(__('Check status')) ?>,
                                                scope:this,
                                             handler:function(){
                                                View.checkNodeState(this.ctxNode);
                                             }
                                            }
                                            ,{
                                                text: <?php echo json_encode(__('Maintenance')) ?>,
                                                tooltip: {text: <?php echo json_encode(__('This will put in maintenance the selected node')) ?>},
                                                ref:'btn_maintenance_node',
                                                disabled:false,
                                                hidden: true,
                                                scope:this,
                                                iconCls: 'icon-maintenance',
                                                handler: this.maintenanceNode
                                            },{
                                                text: <?php echo json_encode(__('Recover')) ?>,
                                                tooltip: {text: <?php echo json_encode(__('This will execute system check on selected node and recover it.')) ?>},
                                                ref:'btn_systemcheck_node',
                                                disabled:false,
                                                hidden: true,
                                                scope:this,
                                                handler: this.systemCheck
                                            },{
                                                text: <?php echo json_encode(__('Shutdown')) ?>,
                                                tooltip: {text: <?php echo json_encode(__('This will shutdown the selected node')) ?>},
                                                ref:'btn_shutdown_node',
                                                disabled:false,
                                                hidden: true,
                                                scope:this,
                                                iconCls: 'icon-shutdown',
                                                handler:function(btn,e){
                                                    this.shutdownNode(btn,this.ctxNode);
                                                }
                                            }
                                        ],
                                        scope: this
                                    }
//                                    ,'-'
                                    ,{
                                        html: '<b class="menu-title">'+<?php echo json_encode(__('Server')) ?>+'</b>',
                                        ref:'ttl_server',
                                        border: false,
                                        xtype: "panel"
                                    },{
                                        iconCls:'icon-keyboard',
                                        ref:'btn_keymap_server',
                                        text: <?php echo json_encode(__('Set keymap')) ?>,
                                        disabled:true,
//                                        hidden: true,
                                        scope: this,
                                        handler:function(btn,e){
                                            this.setKeymap(btn,this.ctxNode);
                                        }
                                    },{
                                        text: <?php echo json_encode(__('Set permissions')) ?>,
                                        tooltip: {text: <?php echo json_encode(__('This will change permissions for this server')) ?>},
                                        ref:'btn_permission_server',
                                        disabled:false,
                                        hidden: true,
                                        scope:this,
                                        iconCls: 'icon-lockedit',
                                        handler:function(btn,e){
                                            this.changePerms(btn,this.ctxNode);
                                        }
                                    },{
                                        text: <?php echo json_encode(__('Start')) ?>,
                                        tooltip: {text: <?php echo json_encode(__('Starts the selected server')) ?>},
                                        ref:'btn_start_server',
                                        disabled:false,
                                        hidden: true,
                                        scope:this,
                                        iconCls: 'icon-play',
                                        handler:function(btn,e){
                                            this.startServer(btn, this.ctxNode);
                                        }
                                    },{
                                        text: <?php echo json_encode(__('Stop')) ?>,
                                        tooltip: {text: <?php echo json_encode(__('Stops the selected server')) ?>},
                                        ref:'btn_stop_server',
                                        disabled:false,
                                        hidden: true,
                                        scope:this,
                                        iconCls: 'icon-mystop',
                                        menu:[{
                                            text    : <?php echo json_encode(__('Normal')) ?>
                                            ,scope  : this
                                            ,handler: function(btn,e){
                                                this.stopServer(btn, this.ctxNode, false);
                                            }
                                        },{
                                            text    : <?php echo json_encode(__('Forced')) ?>
                                            ,scope  : this
                                            ,handler: function(btn,e){
                                                this.stopServer(btn, this.ctxNode, true);
                                            }
                                        }],
                                        handler:function(btn,e){
                                            this.stopServer(btn, this.ctxNode, false);
                                        }
                                    }
                                ];//end contextmenu items

                        
                        if(node.attributes.contextmenu){
                            if(!this.menu){ // create context menu on first right click
                                this.menu = new Ext.ux.TooltipMenu({
                                    items: items
                                }); //end this.menu
                                this.menu.on('hide', this.onContextHide, this);
                            }
                            //end if create menu

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


                            if(node.attributes.type == 'cluster'){

                                //enable/disable cluster rename button (in the case of default cluster)
                                if(node.text == 'Default' && node.id.toString().substring(0,1) == 'd'){
                                    this.menu.btn_rename_cluster.setDisabled(true);
                                    this.menu.btn_remove_cluster.setDisabled(true);
                                }else{
                                    this.menu.btn_rename_cluster.setDisabled(false);
                                    this.menu.btn_remove_cluster.setDisabled(false);
                                }
                                this.menu.btn_edit_cluster.setDisabled(false);

                                //enable cluster items
                                this.menu.ttl_cluster.show();
                                //this.menu.btn_rename_cluster.show();
                                this.menu.btn_edit_cluster.show();
                                this.menu.btn_remove_cluster.show();

                                //disable node items
                                this.menu.ttl_node.hide();
                                this.menu.btn_remove.hide();
                                if(this.menu.btn_permission)
                                    this.menu.btn_permission.show();
                                //this.menu.btn_hostname.hide();
                                this.menu.btn_edit_node.hide();
                                this.menu.btn_keymap.hide();
                                this.menu.ttl_initialization.hide();
                                this.menu.btn_authorize.hide();
                                this.menu.btn_reinitialize.hide();
                                this.menu.btn_node_status.menu.btn_maintenance_node.hide();
                                this.menu.btn_node_status.menu.btn_systemcheck_node.hide();
                                this.menu.ttl_node.hide();
                                this.menu.btn_loadnode.hide();
                                this.menu.btn_node_status.hide();
                                this.menu.btn_node_status.menu.btn_shutdown_node.hide();
                                if(this.menu.btn_connectivity)
                                    this.menu.btn_connectivity.hide();

                                //hide server specific items
                                this.menu.btn_keymap_server.hide();
                                this.menu.ttl_server.hide();
                                this.menu.btn_permission_server.hide();
                                this.menu.btn_stop_server.hide();
                                this.menu.btn_start_server.hide();

                                this.menu.btn_remove.setDisabled(false);
                                this.menu.btn_keymap.setDisabled(false);
                                this.menu.btn_keymap.clearTooltip();

                            }else if(node.attributes.type=='node')
                            {
                                //hide cluster items
                                this.menu.ttl_cluster.hide();
                                this.menu.btn_rename_cluster.hide();
                                this.menu.btn_remove_cluster.hide();
                                this.menu.btn_edit_cluster.hide();
                                
                                //enable node items
                                this.menu.ttl_node.show();
                                this.menu.btn_remove.show();
                                if(this.menu.btn_permission)
                                    this.menu.btn_permission.hide();
                                //this.menu.btn_hostname.show();
                                this.menu.btn_edit_node.show(); // TODO change me 
                                this.menu.btn_keymap.show();
                                this.menu.ttl_initialization.show();
                                this.menu.btn_authorize.show();
                                this.menu.btn_reinitialize.show();
                                this.menu.ttl_node.show();
                                this.menu.btn_loadnode.show();
                                this.menu.btn_node_status.show();
                                this.menu.btn_node_status.menu.btn_shutdown_node.show();

                                if( (node.attributes.state==<?php echo json_encode(EtvaNode::NODE_MAINTENANCE); ?>) ||
                                        (node.attributes.state==<?php echo json_encode(EtvaNode::NODE_MAINTENANCE_UP); ?>) ){
                                    this.menu.btn_node_status.menu.btn_maintenance_node.hide();
                                    this.menu.btn_node_status.menu.btn_systemcheck_node.show();
                                /*} else if( node.attributes.sparenodeid && (node.attributes.sparenodeid==node.attributes.id) ){
                                    this.menu.btn_node_status.menu.btn_maintenance_node.hide();
                                    this.menu.btn_node_status.menu.btn_systemcheck_node.hide();*/
                                } else {
                                    this.menu.btn_node_status.menu.btn_systemcheck_node.hide();
                                    this.menu.btn_node_status.menu.btn_maintenance_node.show();

                                    /*if( (node.attributes.sparenodeid && node.attributes.sparenodeIsFree) || !node.attributes.has_servers_running ){
                                        this.menu.btn_node_status.menu.btn_maintenance_node.setDisabled(false);
                                        this.menu.btn_node_status.menu.btn_maintenance_node.clearTooltip();
                                    } else {
                                        this.menu.btn_node_status.menu.btn_maintenance_node.setDisabled(true);
                                        this.menu.btn_node_status.menu.btn_maintenance_node.setTooltip({text: <?php echo json_encode(__('This cluster doesn\'t have free spare node configured.')) ?>});
                                    }*/
                                }

                                //hide server specific items
                                this.menu.btn_keymap_server.hide();
                                this.menu.ttl_server.hide();
                                this.menu.btn_permission_server.hide();
                                this.menu.btn_stop_server.hide();
                                this.menu.btn_start_server.hide();

                                this.menu.btn_remove.setDisabled(false);
                                this.menu.btn_keymap.setDisabled(false);
                                this.menu.btn_keymap.clearTooltip();

                                if(node.attributes.state==0)
                                {

                                    this.menu.btn_authorize.setDisabled(true);
                                    this.menu.btn_authorize.setTooltip({text: node_state_msg});

                                    this.menu.btn_reinitialize.setDisabled(true);
                                    this.menu.btn_reinitialize.setTooltip({text: node_state_msg});

                                    //this.menu.btn_hostname.setDisabled(true);
                                    //this.menu.btn_hostname.setTooltip({text: node_state_msg});

                                    this.menu.btn_edit_node.setDisabled(true);
                                    this.menu.btn_edit_node.setTooltip({text: node_state_msg});


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

                                    //this.menu.btn_hostname.setDisabled(false);
                                    //this.menu.btn_hostname.clearTooltip();

                                    this.menu.btn_edit_node.setDisabled(false);
                                    this.menu.btn_edit_node.clearTooltip();


                                    if(this.menu.btn_connectivity){
                                        if(node.attributes.initialize!=<?php echo json_encode(EtvaNode_VA::INITIALIZE_OK) ?>){
                                            this.menu.btn_connectivity.setDisabled(true);
                                            this.menu.btn_connectivity.setTooltip({text: <?php echo json_encode(__('Needs to be initialized')) ?>});
                                        }
                                        else{
                                            this.menu.btn_connectivity.setDisabled(false);
                                            this.menu.btn_connectivity.clearTooltip();
                                        }
                                        this.menu.btn_connectivity.show();
                                    }
                                }



                            }else
                            {
                                //show server items
                                this.menu.btn_keymap_server.show();
                                this.menu.ttl_server.show();
                                this.menu.btn_permission_server.show();
                                   
                                // start stop features 
                                if(node.parentNode.attributes.type == 'unassignednode'){
                                    this.menu.btn_start_server.hide();
                                    this.menu.btn_start_server.clearTooltip();
                                    this.menu.btn_stop_server.hide();
                                    this.menu.btn_stop_server.clearTooltip();
                                }else{
                                    if(node.attributes.vm_state == 'running'){
                                        this.menu.btn_start_server.hide();
                                        this.menu.btn_stop_server.show();
                                    }else if(node.attributes.vm_state == 'stop'){
                                        this.menu.btn_stop_server.hide();
                                        this.menu.btn_start_server.show();
                                    }
    
                                    if(node.parentNode.attributes.state != 1){
                                        this.menu.btn_start_server.setDisabled(true);
                                        this.menu.btn_start_server.setTooltip({text: <?php echo json_encode(__('Node needs to be running')) ?>});
                                        this.menu.btn_stop_server.setDisabled(true);
                                        this.menu.btn_stop_server.setTooltip({text: <?php echo json_encode(__('Node needs to be running')) ?>});
                                    }else{
                                        this.menu.btn_start_server.setDisabled(false);
                                        this.menu.btn_start_server.clearTooltip();
                                        this.menu.btn_stop_server.setDisabled(false);
                                        this.menu.btn_stop_server.clearTooltip();
                                    }
                                }
                                
                                //enable cluster items
                                this.menu.ttl_cluster.hide();
                                this.menu.btn_rename_cluster.hide();
                                this.menu.btn_remove_cluster.hide();
                                this.menu.btn_edit_cluster.hide();

                                //hide node items
                                this.menu.ttl_node.hide();
                                this.menu.btn_remove.hide();
                                if(this.menu.btn_permission)
                                    this.menu.btn_permission.hide();
                                //this.menu.btn_hostname.hide();
                                this.menu.btn_edit_node.hide();
                                this.menu.btn_keymap.hide();
                                this.menu.ttl_initialization.hide();
                                this.menu.btn_authorize.hide();
                                this.menu.btn_reinitialize.hide();
                                this.menu.ttl_node.hide();
                                this.menu.btn_loadnode.hide();
                                this.menu.btn_node_status.hide();
                                this.menu.btn_node_status.menu.btn_shutdown_node.hide();

                                this.menu.btn_node_status.menu.btn_maintenance_node.hide();
                                this.menu.btn_node_status.menu.btn_systemcheck_node.hide();

//                                this.menu.btn_connectivity.hide();

                                this.menu.btn_remove.setDisabled(true);
                                //this.menu.btn_hostname.setDisabled(true);
                                //this.menu.btn_hostname.clearTooltip();
                                this.menu.btn_edit_node.setDisabled(true);
                                this.menu.btn_edit_node.clearTooltip();

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
                                    this.menu.btn_connectivity.hide();
                                }


                            }

                            if(node.attributes.type) this.menu.showAt(e.getXY());
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
                    setClusterName: function(btn,node){
                        var cluster_id = node.id;
                        cluster_id = cluster_id.replace(/^d/,'');

                        var title = String.format(<?php echo json_encode(__('Change {0} name')) ?>,node.attributes.text);
//                        var hostnameForm = new View.HostName();

                        var cluster_name_form = new Cluster.ChangeName();
                        
                        var window = new Ext.Window({
                            title: title,
                            autoHeight: true,
                            width: 350,
                            resizable: false,
                            border:false,
                            plain:true,
                            modal: true,
                            loadMask:true,
                            defaultButton:cluster_name_form.getForm().findField('name'),
                            items:cluster_name_form
                            ,tools:[{
                                id:'help',
                                qtip: __('Help'),
                                handler:function(){View.showHelp({anchorid:'help-left-panel-hostname',autoLoad:{ params:'mod=view'},
                                title: <?php echo json_encode(__('Hostname Help')) ?>});}
                            }]

                        });


                        cluster_name_form.on({
                            'onCancel':function(){window.close();}
                            ,'onSave':function(){window.close();

                                Ext.getCmp('view-nodes-panel').reload({ 'cluster_id': cluster_id });
                                /*this.getRootNode().reload(function(){
                                    var centerElem = Ext.getCmp('view-main-panel').findById('view-center-panel-'+cluster_id);
                                    if(centerElem && centerElem.isVisible())
                                    {

                                        this.selectNode(cluster_id);
                                        centerElem.fireEvent('beforeshow');
                                    }


                                },this);*/

                            }
                            ,scope:this
                        });

                        window.on('show',function(){
                            cluster_name_form.loadData(cluster_id);
                        });

                        window.show(btn.id);

                    }
                    ,startServer: function(btn, node){
                        
                        var server_name = node.attributes.text;
                        var nid = node.parentNode.attributes.id; 
                        var server_vm_state = node.attributes.vm_state;

                        var send_data = {'nid': nid,
                                         'server': server_name};

                        Ext.Msg.show({
                            title: <?php echo json_encode(__('Start server')) ?>,
                            buttons: Ext.MessageBox.YESNO,
                            scope:this,
                            msg: String.format(<?php echo json_encode(__('Current state reported: {0}')) ?>,server_vm_state)+'<br>'
                                 +String.format(<?php echo json_encode(__('Start server {0} ?')) ?>,server_name),
                            fn: function(btn){
                                if (btn == 'yes'){

                                    var conn = new Ext.data.Connection({
                                        listeners:{
                                            // wait message.....
                                            beforerequest:function(){
                                                Ext.MessageBox.show({
                                                    title: <?php echo json_encode(__('Please wait...')) ?>,
                                                    msg: <?php echo json_encode(__('Starting virtual server...')) ?>,
                                                    width:300,
                                                    wait:true
                                                });
                                            },// on request complete hide message
                                            requestcomplete:function(){Ext.MessageBox.hide();}
                                            ,requestexception:function(c,r,o){Ext.Ajax.fireEvent('requestexception',c,r,o);}
                                        }
                                    });// end conn
                                    conn.request({
                                        url: <?php echo json_encode(url_for('server/jsonStart'))?>,
                                        params: send_data,
                                        scope:this,
                                        success: function(resp,opt) {
                                            var response = Ext.util.JSON.decode(resp.responseText);
                                            Ext.ux.Logger.info(response['agent'], response['response']);
//                                            var parentCmp = Ext.getCmp((item.scope).id);
//                                            parentCmp.fireEvent('refresh',parentCmp);

                                        }
                                        ,failure: function(resp,opt) {
                                            var response = Ext.util.JSON.decode(resp.responseText);
                                            if(response && resp.status!=401)
                                                Ext.Msg.show({
                                                    title: String.format(<?php echo json_encode(__('Error {0}')) ?>,response['agent']),
                                                    buttons: Ext.MessageBox.OK,
                                                    msg: String.format(<?php echo json_encode(__('Unable to start virtual server {0}!')) ?>,server_name)+'<br>'+response['info'],
                                                    icon: Ext.MessageBox.ERROR
                                                });
                                        }
                                    });// END Ajax request
                                }//END button==yes
                            }// END fn
                        }); //END Msg.show


                    }
                    ,stopServer: function(btn, node, forced){
                        var server_name = node.attributes.text;
                        var node_id = node.parentNode.attributes.id; 
                        var server_vm_state = node.attributes.vm_state;

                        var forcestop = 0;
                        var title = <?php echo json_encode(__('Stop server')) ?> + ' (';

                        if( forced ){
                            forcestop = 1;                            
                            title += <?php echo json_encode(__('Forced')) ?>;                            
                        }else{
                            title += <?php echo json_encode(__('Normal')) ?>
                        }
                        title += ')';

                        Ext.Msg.show({
                            title: title,
                            scope:this,
                            buttons: Ext.MessageBox.YESNO,
                            msg: String.format(<?php echo json_encode(__('Current state reported: {0}')) ?>,server_vm_state)+'<br>'
                                 +String.format(<?php echo json_encode(__('Stop server {0} ?')) ?>,server_name),
                            icon: Ext.MessageBox.QUESTION,
                            fn: function(btn){

                                if (btn == 'yes'){
                                    var params = {'name':server_name};
                                    var conn = new Ext.data.Connection({
                                        listeners:{
                                            // wait message.....
                                            beforerequest:function(){
                                                Ext.MessageBox.show({
                                                    title: <?php echo json_encode(__('Please wait...')) ?>,
                                                    msg: <?php echo json_encode(__('Stoping virtual server...')) ?>,
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
                                    conn.request({
                                        url: <?php echo json_encode(url_for('server/jsonStop'))?>,
                                        params: {'nid':node_id,'server': server_name, 'force': forcestop, 'destroy': forcestop },
                                        scope:this,
                                        success: function(resp,opt) {
                                            var response = Ext.util.JSON.decode(resp.responseText);
                                            Ext.ux.Logger.info(response['agent'],response['response']);
//                                            var parentCmp = Ext.getCmp((item.scope).id);
//                                            parentCmp.fireEvent('refresh',parentCmp);
                                        },
                                        failure: function(resp,opt) {
                                            var response = Ext.util.JSON.decode(resp.responseText);

                                            Ext.ux.Logger.error(response['agent'], response['error']);

                                            Ext.Msg.show({
                                                title: String.format(<?php echo json_encode(__('Error {0}')) ?>,response['agent']),
                                                width:300,
                                                buttons: Ext.MessageBox.OK,
                                                msg: String.format(<?php echo json_encode(__('Unable to stop virtual server {0}!')) ?>,server_name)+'<br>'+response['info'],
                                                icon: Ext.MessageBox.ERROR});
                                        }
                                    });// END Ajax request
                                }//END button==yes
                            }// END fn
                        }); //END Msg.show



                    }
                    ,changePerms: function(btn,node){
                        //console.log(node);

                        var title = <?php echo json_encode(__('Set permissions')) ?>;

//                        var url = <#?php echo json_encode(url_for('setting/jsonSetting'))?>;
                        var isLeaf = false;
                        var p_id;
                        var p_level;
                        var p_permtype;
                        var msg = <?php echo json_encode(__('Set {0} permissions')) ?>;

                        if(node.isLeaf()){
                            isLeaf = true;
                            title = String.format(msg, node.attributes.text);
                            var sId = node.id;
                            sId = sId.replace(/^s/,'');
                            p_id = sId;
                            p_level = 'server';
                            p_permtype = 'op'
                        } else if(node.attributes.type='cluster'){
                            var cId = node.id;
                            cId = cId.replace(/^d/,'');
                            p_id = cId;
                            p_level = 'cluster';
                            p_permtype = 'admin';
                        }else{
                            p_id = node.id;
                            p_level = 'node';
                            p_permtype = 'admin';
                        }

                        var permsForm = new View.PermForm({id: p_id, level: p_level, permtype: p_permtype});
                        var windowPerms = new Ext.Window({
                            title: title,
                            autoHeight: true,
//                            height: 400,
                            width: 500,
                            resizable: false,
                            border:false,
                            plain:true,
                            modal: true,
                            loadMask:true,
                            iconCls:'icon-lockedit',
                            buttons:[
                                {
                                    text: __('Change'),
                                    handler:function(rec){
                                        permsForm.onSave(rec);
                                        windowPerms.close();
                                    }
                                },
                                {
                                    text: __('Cancel'),
                                    handler:function(){
                                        windowPerms.close();
                                    }
                             }]
                            , items:permsForm
                            ,tools:[{
                                id:'help',
                                qtip: __('Help'),
                                handler:function(){View.showHelp({anchorid:'help-left-panel-addperm',autoLoad:{ params:'mod=view'},
                                title: <?php echo json_encode(__('Default keymap Help')) ?>});}
                            }]
                        });

                        windowPerms.on('show',function(){
                            permsForm.loadData();
                        });

                        windowPerms.show(btn.id);



//                        windowPerms.on({'keymapSave':function(resp){
//                            var response = Ext.decode(resp.responseText);
//                            var msg = String.format(<#?php echo json_encode(__('Updated VNC keymap ({0})')) ?#>,node.attributes.text);
//                            windowPerms.close();
//                            Ext.ux.Logger.info(response['agent'],msg);
//                            View.notify({html:msg});
//                        }});

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
                                                    wait:true
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
                    ,shutdownNode: function(btn, node){
                        
                        Ext.Msg.show({
                            title: String.format(<?php echo json_encode(__('Shutdown {0}')) ?>, node.attributes.text ),
                            buttons: Ext.MessageBox.YESNOCANCEL,
                            msg: String.format(<?php echo json_encode(__('Shutdown node {0} ? This will also shut down all virtual servers from this node.')) ?>,node.attributes.text),
                            icon: Ext.MessageBox.WARNING,
                            fn: function(btn){
                                if (btn == 'yes'){

                                    var conn = new Ext.data.Connection({
                                        listeners:{
                                            // wait message.....
                                            beforerequest:function(){
                                                Ext.MessageBox.show({
                                                    title: <?php echo json_encode(__('Please wait...')) ?>,
                                                    msg: <?php echo json_encode(__('Node is shutting down...')) ?>,
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

                                    var alrtmsg = <?php echo json_encode(__('Command sent successfully')) ?>;
                                    var successMsg = <?php echo json_encode(__('Shutting down')) ?> + ' '+ node.attributes.text;
                                    conn.request({
                                        url: <?php echo json_encode(url_for('node/jsonShutdown')) ?>,
                                        params: {id: node.id},
                                        success: function(resp,opt) {
                                            var response = Ext.decode(resp.responseText);
                                            Ext.ux.Logger.info(response['agent'], successMsg);
                                            View.notify({html:successMsg});
                                            Ext.Msg.alert(<?php echo json_encode(__('Information')) ?>, alrtmsg);
                                        },
                                        failure: function(resp,opt) {
                                            var response = Ext.decode(resp.responseText);
                                            Ext.ux.Logger.info(response['agent'],response['info']);
                                            View.notify({html:response['info']});
                                            Ext.Msg.alert(<?php echo json_encode(__('Error!')) ?>, response['info']);
                                        }
                                    });// END Ajax request
                                }//END button==yes
                            }// END fn
                        }); //END Msg.show
                    }
                    ,maintenanceNode: function(btn, e){
                        node = this.ctxNode;

                        Ext.Msg.show({
                            title: String.format(<?php echo json_encode(__('Put node {0} in maintenance')) ?>, node.attributes.text ),
                            buttons: Ext.MessageBox.YESNOCANCEL,
                            msg: String.format(<?php echo json_encode(__('Do you want put node {0} in maintenance? This will also migrate all virtual servers.')) ?>,node.attributes.text),
                            icon: Ext.MessageBox.WARNING,
                            scope: this,
                            fn: function(btn){
                                if (btn == 'yes'){

                                    var spare_node_id = node.attributes.sparenodeid;
                                    var conn = new Ext.data.Connection({
                                        listeners:{
                                            // wait message.....
                                            beforerequest:function(){
                                                Ext.MessageBox.show({
                                                    title: <?php echo json_encode(__('Please wait...')) ?>,
                                                    msg: <?php echo json_encode(__('Node is moving to maintenance mode...')) ?>,
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

                                    conn.request({
                                        url: <?php echo json_encode(url_for('node/jsonPutMaintenance')) ?>,
                                        params: {'id': node.id, 'spare_id':spare_node_id},
                                        success: function(resp,opt) {
                                            //Ext.TaskMgr.stop(task);
                                            var response = Ext.decode(resp.responseText);
                                            Ext.ux.Logger.info(response['agent'], response['response']);
                                            View.notify({html:response['response']});
                                            Ext.getCmp('view-nodes-panel').reload({ 'node_id': node.id });
                                        },
                                        failure: function(resp,opt) {
                                            //Ext.TaskMgr.stop(task);
                                            var response = Ext.decode(resp.responseText);
                                            Ext.ux.Logger.info(response['agent'],response['error']);
                                            View.notify({html:response['info']});
                                            Ext.Msg.show({
                                                title: String.format(<?php echo json_encode(__('Error {0}')) ?>,response['agent']),
                                                width:300,
                                                buttons: Ext.MessageBox.OK,
                                                msg: response['error'],
                                                icon: Ext.MessageBox.ERROR});
                                            Ext.getCmp('view-nodes-panel').reload({ 'node_id': node.id });
                                        }
                                    });// END Ajax request

                                }//END button==yes
                            }// END fn
                        }); //END Msg.show
                    }
                    ,systemCheck: function(btn, e){
                        node = this.ctxNode;

                        Ext.Msg.show({
                            title: String.format(<?php echo json_encode(__('System check on node {0}')) ?>, node.attributes.text ),
                            buttons: Ext.MessageBox.YESNOCANCEL,
                            msg: String.format(<?php echo json_encode(__('Do you like execute system check on node {0}?')) ?>,node.attributes.text),
                            icon: Ext.MessageBox.WARNING,
                            scope: this,
                            fn: function(btn){
                                if (btn == 'yes'){
                                        var conn = new Ext.data.Connection({
                                            listeners:{
                                                // wait message.....
                                                beforerequest:function(){
                                                    Ext.MessageBox.show({
                                                        title: <?php echo json_encode(__('Please wait...')) ?>,
                                                        msg: <?php echo json_encode(__('Executing system check...')) ?>,
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

                                        conn.request({
                                            url: <?php echo json_encode(url_for('node/jsonSystemCheck')) ?>,
                                            params: {'id': node.id},
                                            success: function(resp,opt) {
                                                var response = Ext.decode(resp.responseText);
                                                Ext.ux.Logger.info(response['agent'], response['response']);
                                                View.notify({html:response['response']});
                                                Ext.getCmp('view-nodes-panel').reload({ 'node_id': node.id });
                                            },
                                            failure: function(resp,opt) {
                                                var response = Ext.decode(resp.responseText);
                                                //Ext.ux.Logger.info(response['agent'],response['info']);
                                                //View.notify({html:response['info']});
                                                Ext.Msg.show({
                                                    title: String.format(<?php echo json_encode(__('Error {0}')) ?>,response['agent']),
                                                    width:300,
                                                    buttons: Ext.MessageBox.OK,
                                                    msg: response['error'],
                                                    icon: Ext.MessageBox.ERROR});
                                                Ext.getCmp('view-nodes-panel').reload({ 'node_id': node.id });
                                            }
                                        });// END Ajax request
                                }//END button==yes
                            }// END fn
                        }); //END Msg.show
                    }
                    ,callMigrateServer: function(srv,tnode){
                        /*send_data['id'] = form_values['id'];
                            send_data['nid'] = form_values['nodes_cb'];*/

                        var sId = srv.id;
                        var server_id = sId.replace('s','');
                        var server_name = srv.text;

                        var node_id = tnode.id;

                        var params = { 'id':server_id, 'nodes_cb':node_id };

                        var type = (srv.attributes.vm_state=='running')?  'migrate' : 'move';
                        Server.Migrate.Call(this,params,type,
                                                    function(){
                                                        console.log(server_name + ' migrate ok');
                                                    }
                                                    ,function(){
                                                        console.log(server_name + ' migrate nok');
                                                    }
                        );
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

                                this.reload({ 'node_id': node.id });
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

                                Ext.getCmp('view-nodes-panel').reload({ 'node_id': node.id });
                                /*this.getRootNode().reload(function(){
                                    var centerElem = Ext.getCmp('view-main-panel').findById('view-center-panel-'+node.id);
                                    if(centerElem && centerElem.isVisible())
                                    {
                                        this.selectNode(node.id);                                        
                                        centerElem.fireEvent('beforeshow');                                        
                                    }
                                    
                                    
                                },this);*/
                               
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
                    selectTreeNode: function(ctx){
                        var expand_dcNode = this.getNodeById(ctx.dc_id);
                        if(expand_dcNode && !expand_dcNode.expanded) expand_dcNode.expand();

                        if( ctx.node_id ){
                            var expand_node = this.getNodeById(ctx.node_id);
                            if(expand_node && !expand_node.expanded) expand_node.expand();

                            if( ctx.srv_id ){
                                var expand_srv = this.getNodeById(ctx.srv_id);
                                if(expand_srv && !expand_srv.expanded) expand_srv.select();
                            } else {
                                expand_node.select();
                            }
                        } else {
                            expand_dcNode.select();
                        }
                    },
                    expandNode: function(ctx){
                        var expand_dcNode = this.getNodeById(ctx.dc_id);
                        if(expand_dcNode && !expand_dcNode.expanded) expand_dcNode.expand();

                        if( ctx.node_id ){
                            var expand_node = this.getNodeById(ctx.node_id);
                            if(expand_node && !expand_node.expanded) expand_node.expand();

                            if( ctx.srv_id ){
                                var expand_srv = this.getNodeById(ctx.srv_id);
                                if(expand_srv && !expand_srv.expanded) expand_srv.expand();
                            }
                        }
                    },
                    getCtxNode: function(id){
                        var ctx;

                        var treeNode = this.getNodeById(id);
                        if( treeNode ){
                            ctx = new Object();
                            if( treeNode.attributes.type == 'cluster' ){
                                ctx.dc_id = treeNode.id;
                            } else if( treeNode.attributes.type == 'node' ){
                                ctx.node_id = treeNode.id;
                                ctx.dc_id = treeNode.parentNode.id;
                            } else if( treeNode.attributes.type == 'server' ){
                                ctx.node_id = treeNode.parentNode.id;
                                ctx.dc_id = treeNode.parentNode.parentNode.id;
                                ctx.srv_id = treeNode.id;
                            }
                        }
                        return ctx;
                    },
                    reload:function(attrs){
                        var ctx;
                        if( attrs )
                            if( attrs.cluster_id )
                                ctx = this.getCtxNode(attrs.cluster_id);
                            else if( attrs.node_id )
                                ctx = this.getCtxNode(attrs.node_id);
                            else if( attrs.server_id )
                                ctx = this.getCtxNode(attrs.server_id);
                        if( ctx )
                            this.getRootNode().reload(function(){
                                                                if( attrs && attrs.select )
                                                                    this.selectTreeNode(ctx);
                                                                else 
                                                                    this.expandNode(ctx);
                                                        },this);
                        else
                            this.getRootNode().reload();
                    },
                    reloadExpandNode: function(id){
                        var node = this.getNodeById(id);
                        var gotoNode = this.getRootNode();
                        if(node){                            
                            gotoNode = (node.isLeaf())? node.parentNode: this.getRootNode();                            
                            node.unselect();
                        }
                        this.getRootNode().reload(function(){
                            //gotoNode.select();                            
                            this.selectNode(gotoNode.id);
                            var expand_node = this.getNodeById(gotoNode.id);
                            if(!expand_node.expanded) expand_node.expand();

                        },this);
                    },
                    removeNode: function(id){
                        var node_id;
                        var node = this.getNodeById(id);                        

                        if( node && node.attributes.type == 'server' )
                            node_id = node.parentNode.id;

                        if(node){
                            node.unselect();
                            node.remove();
                        }
                        if( node_id ){
                            var ctx = this.getCtxNode( node_id );
                            this.getRootNode().reload(function(){
                                                                this.selectTreeNode(ctx);
                                                        },this);
                        } else {
                            this.reload( );
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
                    //updates node css
                    updateNodeCss: function(attrs,css_old, css_new){
                        
                        /*var ctx = this.getCtxNode(attrs.parentNode);
                        if( ctx )
                            this.expandNode(ctx);*/

                        var exists = this.getNodeById(attrs.node);
                                                
                        if(exists)
                        {                                                                                                        
                            (exists.getUI()).removeClass(css_old);
                            (exists.getUI()).addClass(css_new);
                            if(attrs.selected) exists.select();
                        }
                    },                    
                    addNode : function(attrs){
                        
                        var ctx = this.getCtxNode( attrs.parentNode );
                        ctx.srv_id = attrs.id;

                        this.getRootNode().reload(function(){
                                                            this.selectTreeNode(ctx);
                                                    },this);
                        
                    },
                    // prevent the default context menu when you miss the node
                    afterRender : function(){
                        NodePanel.superclass.afterRender.call(this);
                        this.el.on('contextmenu', function(e){e.preventDefault();});
                    }
                    ,deleteCluster: function(btn,cluster){

                        Ext.Msg.show({
                            title: <?php echo json_encode(__('Remove cluster')) ?>,
                            buttons: Ext.MessageBox.YESNOCANCEL,
                            msg: String.format(<?php echo json_encode(__('Remove cluster {0} ?')) ?>,cluster.attributes.text),
                            icon: Ext.MessageBox.WARNING,
                            fn: function(btn){
                                if (btn == 'yes'){

                                    var conn = new Ext.data.Connection({
                                        listeners:{
                                            // wait message.....
                                            beforerequest:function(){
                                                Ext.MessageBox.show({
                                                    title: <?php echo json_encode(__('Please wait...')) ?>,
                                                    msg: <?php echo json_encode(__('Removing cluster...')) ?>,
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

                                    var cId = cluster.id;
                                    var cluster_id = cId.replace('d','');

                                    conn.request({
                                        url: <?php echo json_encode(url_for('cluster/jsonDelete')) ?>,
                                        params: {id: cluster_id},
                                        success: function(resp,opt) {
                                            Ext.getCmp('view-nodes-panel').removeNode(cluster.id);
                                            Ext.getCmp('view-main-panel').remove('view-center-panel-'+cluster.id);
                                        },
                                        failure: function(resp,opt) {
                                            Ext.Msg.alert(<?php echo json_encode(__('Error!')) ?>, <?php echo json_encode(__('Unable to delete cluster!')) ?>);
                                        }
                                    });// END Ajax request
                                }//END button==yes
                            }// END fn
                        }); //END Msg.show


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

                    var version_text = ''; //'<span style="color:#15428B; padding-left:3px; font-size:10px">';
                    <?php if (sfConfig::get('config_version')): ?>;
                        version_text += 'Ver. '+'<?php echo sfConfig::get('config_version'); ?>';
                    <?php endif ?>
                    
                    <?php if (sfConfig::get('config_release')): ?>;
                        version_text += ' Rel. '+'<?php echo sfConfig::get('config_release'); ?>';
                    <?php endif ?>
                    // version_text += '</span>';

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
                        ,bbar:[{   
                                    cls: 'version_box',
                                    xtype: 'tbtext',
                                    text: version_text
//                                },
//                                {
//                                    html: version_text
                                    
                                },'->',{
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
                    if( node.type=='unassignednode'){
                            var item = {
                                            url: node.url,
                                            call: 'Node.ViewUnassigned',
                                            callback: function(){
                                                var centerElem = mainPanel.add(
                                                        {
                                                            id: 'view-center-panel-unassigned'+node.cluster_id,
                                                            title: <?php echo json_encode(__('Unassigned')) ?>,
                                                            items: new Node.ViewUnassigned.Main({}),
                                                            layout:'fit',
                                                            bodyStyle:'padding:0px;',
                                                            defaults:{border:false},
                                                            scripts:true
                                                });

                                                mainPanel.layout.setActiveItem(centerElem);
                                            }
                                        };
                            View.loadComponent(item);
                    } else {
                        var centerElem = mainPanel.findById('view-center-panel-'+node.id);
                        var node_class = 'View.Main';
                        var component = '';
                        if(node.type=='server') node_class = 'Server.View';
                        if(node.type=='node') node_class = 'Node.View';
                        
    //                    if(!centerElem)
    //                    /*
    //                     * create item component and add to mainPanel
    //                     */
    //                    {
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
                                                component = new Server.View.Main({node_id:nid.id,server:{id:sid,agent_tmpl:agent_tmpl,state:state, data:node}});
                                                
                                            }else{

                                                if(node.type=='node') component = new Node.View.Main({node_id:node.id});
                                                else component = new View.Main({aaa:node.id.replace('d','')});
                                                
                                            }

                                            component.on( { 'updateNodeCss': function(node_attrs,data){
                                                                    var css_ = View.getNodeCssState(data);
                                                                    Ext.getCmp('view-nodes-panel').updateNodeCss(node_attrs,css_.old_css,css_.new_css);
                                                            },
                                                            'reloadTree': function(attrs){
                                                                    Ext.getCmp('view-nodes-panel').reload(attrs);
                                                                }
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

    //                    }//end if
    //                    else{
    //                        centerElem.setTitle(node.text);
    //                        mainPanel.layout.setActiveItem(centerElem);
    //                    }
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
                           // alert("Dynamic loading completed");
                        });
                    }
                    else
                    {
                        //alert("Failed to load Page. Please check the URL or try again later.");
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
                        if( el ){
                            var yoffset = el.getOffsetsTo(ctEl)[1];
                            ctEl.scrollTo('top',yoffset,true);
                            //    Ext.get(config.anchorid).scrollIntoView(ctEl);
                        }
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
                    else response = <?php echo json_encode(__('Error')) ?>;
                    View.notify({html:response});
                    if(node.attributes.type=='server'){
                        Ext.getCmp('view-nodes-panel').reload({ 'server_id': node.id });
                    } else {
                        Ext.getCmp('view-nodes-panel').reload({ 'node_id': node.id });
                    }

                });

                mgr.on('update',function(el,resp){

                    var agent = (Ext.util.JSON.decode(resp.responseText))['agent'];
                    var message = (Ext.util.JSON.decode(resp.responseText))['response'];
                    if( !message )
                        message = <?php echo json_encode(__('System check')) ?>;
                    Ext.ux.Logger.info(agent, message);
                    if(node.attributes.type=='server'){
                        Ext.getCmp('view-nodes-panel').reload({ 'server_id': node.id });
                    } else {
                        Ext.getCmp('view-nodes-panel').reload({ 'node_id': node.id });
                    }

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
            loadComponent:function(item,e,obj){
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
                    item.callback(item,e,obj);
                }
                else{                    
                    View.clickHandler(item,e,obj);
                }

            },
            clickHandler:function (item, e, obj) {            
                Ext.getBody().mask(<?php echo json_encode(__('Retrieving data...')) ?>);                
                Ext.Ajax.request({
                    url: item.url,
                    method: 'POST',
                    success:function(response){

                        Ext.get('dynPageContainer').update(response.responseText,true,function(){

                            if(eval("typeof "+item.callback+"!='undefined'")) item.callback(item,e,obj);
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
                    //alert('cli');
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
                    title: <?php echo json_encode(__(sfConfig::get('config_acronym').' :: ISO Upload')) ?>,
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
                        /*documentloaded : function(frameEl){
                                        var MIF = frameEl.ownerCt;
                                        var doc = frameEl.getFrameDocument();
                                        var applet = doc.getElementsByTagName('applet');
                                        if( applet && applet[0]){
                                            var docHeight = parseInt(applet[0].height)+70;
                                            var docWidth = parseInt(applet[0].width)+35;

                                            MIF.setTitle(doc.title);
                                            MIF.setWidth(docWidth);
                                            MIF.setHeight(windowHeight > docHeight  ? docHeight:windowHeight );
                                            MIF.center();

                                            View.notify({html:MIF.title+' reports: DATA LOADED'});
                                        }

                        },*/
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
                                        boxLabel: <?php echo json_encode(__('DHCP')) ?>, width:90,
                                        name: 'network_'+id+'_bootp',fieldLabel:'',hideLabel:true,
                                        inputValue: 'dhcp'
                                    }),
                                    new Ext.form.Radio({
                                        boxLabel: <?php echo json_encode(__('Static')) ?>, width:90,
                                        name: 'network_'+id+'_bootp',fieldLabel:'',hideLabel:true,
                                        inputValue: 'static'
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
                                        boxLabel: <?php echo json_encode(__('DHCP')) ?>, width:90,
                                        name: id+'_bootpdns',fieldLabel:'',hideLabel:true,
                                        inputValue: 'dhcp'
                                    }),
                                    new Ext.form.Radio({
                                        boxLabel: <?php echo json_encode(__('Static')) ?>, width:90,
                                        name: id+'_bootpdns',fieldLabel:'',hideLabel:true,
                                        inputValue: 'static'
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
                        items:[View.StaticIpTpl('va_management')]
                    },{
                        xtype: 'fieldset',
                        title: <?php echo json_encode(__('DNS')) ?>,
                        collapsible: false,
                        items:[View.DnsTpl('network')]
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
                if(alldata['network_va_management_bootp']=='dhcp'){
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
                                wait:true
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

    View.PermForm = Ext.extend(Ext.form.FormPanel, {
        border:false
//        ,height: 350
        ,labelWidth:100
        ,scope:this
        ,labelAlign:'right'
        ,initComponent:function() {

            // MULTISELECT FIELDS
            // users
            var user_store
                = new Ext.data.Store({
                    proxy: new Ext.data.HttpProxy({
                        url: <?php echo json_encode(url_for('sfGuardUser/JsonList')); ?>,
                        method:'POST'}),
                        reader: new Ext.data.JsonReader({
                            root: 'data',
                            fields:['Id','Username']})
            });

            this.users = new Ext.ux.Multiselect({
                fieldLabel: <?php echo json_encode(__('Users')) ?>,
                valueField:"Id",
                displayField:"Username",
                height:250,
                name:'etva_permission_user_list',
                allowBlank:true,
                store:user_store
            });

            // Groups
            var groups_store
                    = new Ext.data.Store({
                            proxy: new Ext.data.HttpProxy({
                                url: <?php echo json_encode(url_for('sfGuardGroup/jsonList')); ?>,
                                method:'POST'}),
                                reader: new Ext.data.JsonReader({
                                    root: 'data',
                                    fields:['Id','Name']})
            });

            this.groups = new Ext.ux.Multiselect({
                fieldLabel: <?php echo json_encode(__('Groups')) ?>,
                valueField:"Id",
                displayField:"Name",
                height:250,
                name:'etva_permission_group_list',
                allowBlank:true,
                store:groups_store
            });

            this.usrGrpStore = new Ext.data.JsonStore({
                proxy: new Ext.data.HttpProxy({url: 'sfGuardPermission/JsonPermsWithGroupsUsers/action' }), //<php echo json_encode(url_for('sfGuardPermission/jsonGridWithGroups')) //'sfGuardPermission/JsonGridGroupsClustersVms ?>'
                id: 'id',
                totalProperty: 'total',
                root: 'data',
                    baseParams: {id: this.id, permtype: this.permtype, level: this.level},
                fields: [
                    {name:'id',type:'int'}
                    ,'etva_permission_group_list'
                    ,'etva_permission_user_list'
                ]
//                ,sortInfo: { field: 'name',
//                ,direction: 'DESC' }
                ,remoteSort: false
            });

//            usrGrpStore.on('beforeload', function(store){
//                store.baseParams = {id: 1, type: 'op', level: 'server'};
//            });

            this.usrGrpStore.on({
                'load':{
                    fn: function(store, records, options){
                        //store is loaded, now you can work with it's records, etc.
//                        console.info('store load, arguments:', arguments);
//                        console.info('Store count = ', store.getCount());

                        var rec = this.usrGrpStore.getAt(0);

                        this.getForm().loadRecord(rec);
//                        this.fireEvent('rowdblclick',this,0);
                        //createEdit.loadRecord(rec);
                    },
                    scope:this
                }
//                ,'loadexception':{
//                    //consult the API for the proxy used for the actual arguments
//                    fn: function(obj, options, response, e){
//                        console.info('store loadexception, arguments:', arguments);
//                        console.info('error = ', e);
//                    },
//                    scope:this
//                }
            });

            //{id: p_id, level: p_level, permtype: p_permtype}
            var perm_title = <?php echo json_encode(__('Select users and/or groups that must have permissions in this {0}.')) ?>;
            if(this.level == 'server'){
                perm_title = String.format(perm_title, <?php echo json_encode(__('server')) ?>);
            }else{
                perm_title = String.format(perm_title, this.level);
            }
            
            var config = {
                defaults:{
                    border: false,
                    frame : true
                }
                
                ,items:[
                    {
                        xtype:'panel',
//                        border: false,
                        layout:'form',
                        padding:10,
                        items:[{
                            html: perm_title
                        }]
                        
                    },
                    {
                    layout:'column',
                    style:'padding:10px;',
                    height:270,
                    layoutConfig: {fitHeight: true},
                    items:[{
                        columnWidth:.50,
                        name:'etva_permission_user_list',
                        layout: 'form',
                        labelWidth:60,
                        items: [this.users]
                    },{
                        columnWidth:.50,
                        layout: 'form',
                        name:'etva_permission_group_list',
                        labelWidth:60,
                        items: [this.groups]
                    }]
                }]
            }

            // apply config
            Ext.apply(this, Ext.apply(this.initialConfig, config));

            // call parent
            View.PermForm.superclass.initComponent.apply(this, arguments);
        }
//        ,selectUserRow:function(var idx){
//
//
//        }
        ,loadData:function(){
            this.groups.store.reload();
            this.groups.store.on('load',function(){
                this.users.store.reload();
                this.users.store.on('load',function(){
                    this.usrGrpStore.reload();
                }, this);
            }, this);   
        }
        ,onSave:function(rec){
//            alert('onsave'+rec);
            var allvals = this.getForm().getValues();
            var groups = [];
            var users = [];
            var to_numbers = [];
            var users_numbers = [];

            var record = new Object();
            record.data = new Object();
            
            if(allvals['etva_permission_group_list'])
                groups = allvals['etva_permission_group_list'].split(',');

            if(allvals['etva_permission_user_list'])
                users = allvals['etva_permission_user_list'].split(',');

            for(var i=0,len=groups.length; i<len;i++)
                to_numbers.push(parseInt(groups[i]));

            for(var i=0,len=users.length; i<len;i++)
                users_numbers.push(parseInt(users[i]));

//id: p_id, level: p_level, permtype: p_permtype

            record.data['etva_permission_group_list'] = Ext.encode(to_numbers);
            record.data['etva_permission_user_list'] = Ext.encode(users_numbers);
            
            record.data['id'] = this.id;
            record.data['level'] = this.level;
            record.data['permtype'] = this.permtype;
           
//            this.fireEvent('onSave',record);

            //update record
//            var store = permsGrid.getStore();

            var conn = new Ext.data.Connection({
                listeners:{
                    // wait message.....
                    beforerequest:function(){
                        Ext.MessageBox.show({
                            title: <?php echo json_encode(__('Please wait...')) ?>,
                            msg: <?php echo json_encode(__('Saving...')) ?>,
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

                url: <?php echo json_encode(url_for('sfGuardPermission/jsonUpdateSpecific')) ?>,
//                url: 'sfGuardPermission/jsonUpdateSpecific',
                scope:this,
                params:record.data,
                success: function(resp,opt) {
                    this.loadData();
                    var response = Ext.util.JSON.decode(resp.responseText);
                    var response = Ext.decode(resp.responseText);
                    Ext.ux.Logger.info(response['agent'],response['response']);
                    View.notify({html:response['response']});
//                    if(rec.data['id']==null) this.fireEvent('onAdd');
                },
                failure: function(resp,opt) {

                    var response = Ext.util.JSON.decode(resp.responseText);
                    Ext.ux.Logger.error(response['agent'],response['error']);

                    Ext.Msg.show({
//                        title: String.format(<?php echo json_encode(__('Error {0}')) ?>,response['agent']),
                        buttons: Ext.MessageBox.OK,
                        msg: response['info'],
                        icon: Ext.MessageBox.ERROR});

                }
            });//END Ajax request

        }


    });


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
                params:{
                    id:id
                },
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
        if(!responseText['agent']) responseText['agent'] = '<?php echo sfConfig::get('config_acronym'); ?>';

        var errorResponse = responseText['error'];
        if(!errorResponse) errorResponse = responseText['info'];
        if(!errorResponse) errorResponse = 'Error!';

        var agent = responseText['agent'];

        var isArray = ((errorResponse).constructor.toString().indexOf("Array") != -1);


        if(!isArray && errorResponse ){

            if((typeof errorResponse)=='string'){

                if(response.status==0)                    
                    Ext.ux.Logger.error(agent, response.statusText);                
                else{
                    Ext.MessageBox.hide();                    
                    Ext.ux.Logger.error(agent, errorResponse);
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
                    msg: '<center><b>The request could not be accomplished!</b></center><br>'+errorResponse,
                    buttons: Ext.MessageBox.OK,
                    icon: Ext.MessageBox.ERROR
                });
                return;
            }

            if(response.status==400){
                
                Ext.MessageBox.show({
                    title: String.format(<?php echo json_encode(__('Error {0}')) ?>,agent),
                    msg: errorResponse,
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
    <h1 class="<?php echo sfConfig::get('config_acronym'); ?>">Central Management - <?php echo sfConfig::get('config_acronym'); ?></h1>
    <div id="topBar"></div>
</div>
<div id="dynPageContainer"></div>
<div id="notificationDiv" style="display:none"></div>
