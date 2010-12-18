<?php
/*
 * Use Extjs helper to dynamic create data store and column model javascript
 */
use_helper('Extjs');
/*
 * Include network grid
 * var networkGrid
 *
 */

include_partial('sfGuardGroup/grid',array('sfGuardGroup_tableMap'=>$sfGuardGroup_tableMap,'sfGuardGroup_form'=>$sfGuardGroup_form));
// include_partial('sfGuardGroup/grid',array('server_id'=>$server_id,'node_id'=>$node_id,'sfGuardGroup_tableMap'=>$sfGuardGroup_tableMap,'server_form'=>$server_form,'server_tableMap'=>$server_tableMap));

?>
<script>


//
//
//var accordion = {
//                id: 'acc-win',
//                title: 'Accordion Window',
//                region      : 'west',
//           // split       : true,
//           // width       : 200,
//                width:250,
//                height:400,
//             //   layout:'accordion',layoutConfig:{animate:true},
//                iconCls: 'accordion',
//                shim:false,
//                animCollapse:false,
//                constrainHeader:true,
//
//                tbar:[{
//                    tooltip:{title:'Rich Tooltips', text:'Let your users know what they can do!'},
//                    iconCls:'connect'
//                },'-',{
//                    tooltip:'Add a new user',
//                    iconCls:'user-add'
//                },' ',{
//                    tooltip:'Remove the selected user',
//                    iconCls:'user-delete'
//                }],
//
//                layout:'accordion',
//                border:false,
//                layoutConfig: {
//                    animate:false
//                },
//
//                items: [
//                    new Ext.tree.TreePanel({
//                        id:'im-tree',
//                        title: 'Online Users',
//                        loader: new Ext.tree.TreeLoader(),
//                        rootVisible:false,
//                        lines:false,
//                        autoScroll:true,
//                        tools:[{
//                            id:'refresh',
//                            on:{
//                                click: function(){
//                                    var tree = Ext.getCmp('im-tree');
//                                    tree.body.mask('Loading', 'x-mask-loading');
//                                    tree.root.reload();
//                                    tree.root.collapse(true, false);
//                                    setTimeout(function(){ // mimic a server call
//                                        tree.body.unmask();
//                                        tree.root.expand(true, true);
//                                    }, 1000);
//                                }
//                            }
//                        }],
//                        root: new Ext.tree.AsyncTreeNode({
//                            text:'Online',
//                            children:[{
//                                text:'Friends',
//                                expanded:true,
//                                children:[{
//                                    text:'Jack',
//                                    iconCls:'user',
//                                    leaf:true
//                                },{
//                                    text:'Brian',
//                                    iconCls:'user',
//                                    leaf:true
//                                },{
//                                    text:'Jon',
//                                    iconCls:'user',
//                                    leaf:true
//                                },{
//                                    text:'Tim',
//                                    iconCls:'user',
//                                    leaf:true
//                                },{
//                                    text:'Nige',
//                                    iconCls:'user',
//                                    leaf:true
//                                },{
//                                    text:'Fred',
//                                    iconCls:'user',
//                                    leaf:true
//                                },{
//                                    text:'Bob',
//                                    iconCls:'user',
//                                    leaf:true
//                                }]
//                            },{
//                                text:'Family',
//                                expanded:true,
//                                children:[{
//                                    text:'Kelly',
//                                    iconCls:'user-girl',
//                                    leaf:true
//                                },{
//                                    text:'Sara',
//                                    iconCls:'user-girl',
//                                    leaf:true
//                                },{
//                                    text:'Zack',
//                                    iconCls:'user-kid',
//                                    leaf:true
//                                },{
//                                    text:'John',
//                                    iconCls:'user-kid',
//                                    leaf:true
//                                }]
//                            }]
//                        })
//                    }), {
//                        title: 'Settings',
//                        html:'<p>Something useful would be in here.</p>',
//                        autoScroll:true
//                    },{
//                        title: 'Even More Stuff',
//                        html : '<p>Something useful would be in here.</p>'
//                    },{
//                        title: 'My Stuff',
//                        html : '<p>Something useful would be in here.</p>'
//                    }
//                ]
//            };


/*
 * ================  AccordionLayout config  =======================
 */
var accordion = {
    title       : 'Navigation',
            region      : 'west',
            split       : true,
            width       : 200,
            collapsible : true,
            margins     : '3 0 3 3',
            cmargins    : '3 3 3 3',
    
    layout:'accordion',
    bodyBorder: false,  // useful for accordion containers since the inner panels have borders already
    bodyStyle: 'background-color:#DFE8F6',  // if all accordion panels are collapsed, this looks better in this layout
	defaults: {bodyStyle: 'padding:15px'},
    items: [{
        title: 'Introduction',
		tools: [{id:'gear'},{id:'refresh',
                on:{
                                click: function(){
                                    var tree = Ext.getCmp('im-tree');
                                    tree.body.mask('Loading', 'x-mask-loading');
                                    tree.root.reload();
                                    tree.root.collapse(true, false);
                                    setTimeout(function(){ // mimic a server call
                                        tree.body.unmask();
                                        tree.root.expand(true, true);
                                    }, 1000);
                                }
                            }
            }],
		html: '<p>Here is some accordion content.  Click on one of the other bars below for more.</p>'
    },
    new Ext.tree.TreePanel({
                        id:'im-tree',
                        title: 'Online Users',
                        loader: new Ext.tree.TreeLoader(),
                        rootVisible:false,
                        lines:false,
                        autoScroll:true,
                        tbar:[{
                    tooltip:'Add a new user',
                    iconCls:'icon-user-add'
                },' ',{
                    tooltip:'Remove the selected user',
                    iconCls:'icon-user-delete'
                }],
                        tools:[{
                            id:'refresh',
                            on:{
                                click: function(){
                                    var tree = Ext.getCmp('im-tree');
                                    tree.body.mask('Loading', 'x-mask-loading');
                                    tree.root.reload();
                                    tree.root.collapse(true, false);
                                    setTimeout(function(){ // mimic a server call
                                        tree.body.unmask();
                                        tree.root.expand(true, true);
                                    }, 1000);
                                }
                            }
                        }],
                        root: new Ext.tree.AsyncTreeNode({
                            text:'Online',
                            children:[{
                                text:'Friends',
                             //   cls: 'x-tree-node-leaf',
                                iconCls:'tree-node-leaf',
                                expanded:true,
                                children:[{
                                    text:'Jack',
                                    iconCls:'icon-user',
                                    leaf:true
                                },{
                                    text:'Brian',
                                    iconCls:'icon-user',
                                    leaf:true
                                },{
                                    text:'Jon',
                                    iconCls:'user',
                                    leaf:true
                                },{
                                    text:'Tim',
                                    iconCls:'user',
                                    leaf:true
                                },{
                                    text:'Nige',
                                    iconCls:'user',
                                    leaf:true
                                },{
                                    text:'Fred',
                                    iconCls:'user',
                                    leaf:true
                                },{
                                    text:'Bob',
                                    iconCls:'user',
                                    leaf:true
                                }]
                            },{
                                text:'Family',
                                expanded:true,
                                children:[{
                                    text:'Kelly',
                                    iconCls:'user-girl',
                                    leaf:true
                                },{
                                    text:'Sara',
                                    iconCls:'user-girl',
                                    leaf:true
                                },{
                                    text:'Zack',
                                    iconCls:'user-kid',
                                    leaf:true
                                },{
                                    text:'John',
                                    iconCls:'user-kid',
                                    leaf:true
                                }]
                            }]
                        })
                    }), {
                        title: 'Settings',
                        html:'<p>Something useful would be in here.</p>',
                        autoScroll:true
                    },{
                        title: 'Even More Stuff',
                        html : '<p>Something useful would be in here.</p>'
                    },{
                        title: 'My Stuff',
                        html : '<p>Something useful would be in here.</p>'
                    },




    {
        title: 'Basic Content',
		html: '<br /><p>More content.  Open the third panel for a customized look and feel example.</p>',
		items: {
			xtype: 'button',
			text: 'Show Next Panel',
			handler: function(){
				Ext.getCmp('acc-custom').expand(true);
			}
		}
    },{
		id: 'acc-custom',
        title: 'Custom Panel Look and Feel',
		cls: 'custom-accordion', // look in layout-browser.css to see the CSS rules for this class
		html: '<p>Here is an example of how easy it is to completely customize the look and feel of an individual panel simply by adding a CSS class in the config.</p>'
    }]
};




 var menuScreenPanel = new Ext.Panel({
    autoLoad: {url: 'sfGuardGroup/refresh', scope: this,scripts:true},
    title: 'Screen Monitoring',
    closable:false,
    autoScroll:true
});
menuScreenPanel.on('render', function() {
	menuScreenPanel.getUpdater().startAutoRefresh(5, 'sfGuardGroup/refresh');
});


// var mmm= sfGuardGroup.Grid.init();

// tabs for the center
        var tabs = new Ext.TabPanel({
            region    : 'center',
            margins   : '3 3 3 0',
            activeTab : 0,
            defaults  : {
				autoScroll : true
			},
            items     : [
               // sfGuardGroup.Grid.init(),
               sfGuardGroupGrid,
                menuScreenPanel,{

                title    : 'Closable Tab',
                html     : 'Ext.example.bogusMarkup',
                closable : true
            }]
        });

//        tabs.getUpdater().startAutoRefresh(3);





        // Panel for the west
        var nav = new Ext.Panel({
            title       : 'Navigation',
            region      : 'west',
            split       : true,
            width       : 200,
            collapsible : true,
            margins     : '3 0 3 3',
            cmargins    : '3 3 3 3'
        });

        var win = new Ext.Window({
            title    : 'User Administration',
            closable : true,
            width    : 600,
            height   : 350,
            items    : [accordion, tabs],
            //border : false,
            plain    : true,
            layout   : 'border'
       //       ,autoLoad:{
 //url:'sfGuardGroup/view',scripts:true,scope:this
 //}
 // ,title:Ext.getDom('page-title').innerHTML
 
 ,listeners:{show:function() {
 this.loadMask = new Ext.LoadMask(this.body, {
 msg:'Loading. Please wait...'
 });},
hide:function(){
 if(menuScreenPanel.rendered) menuScreenPanel.getUpdater().stopAutoRefresh();}
}
            
        });

win.show();
 // win.on('afterlayout',function(){alert('aki');});
        // winGroups.show(item);



</script>