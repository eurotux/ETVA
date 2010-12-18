<?php
/*
 * Use Extjs helper to dynamic create data store and column model javascript
 */
// use_helper('Extjs');
/*
 * Include network grid
 * var networkGrid
 *
 */


// include_partial('sfGuardGroup/grid',array('server_id'=>$server_id,'node_id'=>$node_id,'sfGuardGroup_tableMap'=>$sfGuardGroup_tableMap,'server_form'=>$server_form,'server_tableMap'=>$server_tableMap));

?>
<script>

//
//// Adds tab to center panel
//    function addTab(tabId, tabTitle, targetUrl){
//        mainTabPanel.add({
//	    title: tabTitle,
//        id: tabId,
//        layout:'fit',
//        viewConfig: {layout:'fit'},
//        autoScroll:true,
//        closable:true,
//	    iconCls: 'tabs'
//        //,
//       // items:[myForm]
//	    // autoLoad: {url: targetUrl, scripts:true
//            // callback: this.sucess, scope: this
//
//
//	}).show();
//    }
//
//    // Update the contents of a tab if it exists, otherwise create a new one
//    function updateTab(tabId,title, url) {
//    	var tab = mainTabPanel.getItem(tabId);
//
//    	if(tab){
//    		tab.getUpdater().update(url);
//    		tab.setTitle(title);
//    	}else{
//    		tab = addTab(tabId,title,url);
//    	}
//    	mainTabPanel.setActiveTab(tab);
//    }
//
//
//
//
////var infoTab = new Ext.Panel ({
////        id:'info-tab',
////        bodyStyle:'padding:10px',
////      //  contentEl:'pnl-tab2',
////        title: 'Main information',
////       // cls: 'info-tab',
////        tbar: [{
////            text: 'Edit Server',
////            iconCls: 'icon-add',
////          //  cls: 'x-btn-text-icon',
////            handler: function() {
////
////                var tab = mainTabPanel.getItem('tab-teste');
////                if(!tab){
////                mainTabPanel.add(tabForm);
////                alert("i");
////                }
////                //.show();
////                mainTabPanel.setActiveTab(tabForm);
////                // updateTab('tab-4','Edit Server','<?php // echo url_for('server/edit?id='.$server_id) ?>');
////
////
////            }// END Add handler
////           }// END Add button
////           ],
////        // layout:'form',
////     //   html: tpl.apply({title:'ola'}),
////        autoLoad: '<?php // echo url_for('server/show?id='.$server_id) ?>',
////     //   viewConfig: {layout:'fit'},
////    //    items:[myForm],
////        autoScroll:true
////    });
//
//var mainTabPanelGroups = new Ext.TabPanel({
//          id: 'main-tabGroups',
//         // layout:'border',
//        // region: 'center',
//        margins: '0 0 0 0',
//        border:false,
//		activeTab:0,
//        layoutOnTabChange: true,
//    //    autoDestroy:false,
//      //  anchor:'100% 100%',
//		frame:true,
// //       renderTo:'sample-div',
//	//	tabPosition:'bottom',
//
//    //    tbar: tabsTBar,
//
//  defaults:{layout:'fit'}
//        ,items:[{html:'ola'}
//           // Server.Grid.init()
//          // ,Network.Grid.init()
//          // ,Agent.Grid.init()
//
//        ]
//    });
//
//
//// winGroups.items.add('main-tabGroups',mainTabPanelGroups);
//
////  mainPanel.items.add('main-tab',mainTabPanel);
////  mainPanel.layout.setActiveItem('main-tab');
//
 var nav = new Ext.Panel({
            title       : 'Navigation',
            region      : 'west',
            split       : true,
            width       : 200,
            collapsible : true,
            margins     : '3 0 3 3',
            cmargins    : '3 3 3 3'
        });
//
//
  // tabs for the center
        var tabs = new Ext.TabPanel({
            region    : 'center',
            margins   : '3 3 3 0',
            activeTab : 0,
            defaults  : {
				autoScroll : true
			},
            items     : [{
                title    : 'Bogus Tab',
                html     : 'Ext.example.bogusMarkup'
             },{
                title    : 'Another Tab',
                html     : 'Ext.example.bogusMarkup'
             },{
                title    : 'Closable Tab',
                html     : 'Ext.example.bogusMarkup',
                closable : true
            }]
        });
//
//
 var panelito = new Ext.Panel({
 // renderTo: 'sample-div',
 id:'panel-it',
  width:400,
  height: 400,
  layout: 'border',
  items: [nav,tabs]
});


//win.items.add('panel-it',panelito);
// win.layout.setActiveTab('main-tab');
//
//
//
//









//// tabs for the center
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
//         //   plain    : true,
//            layout   : 'border'
//          //    ,autoLoad:{
// //url:'sfGuardGroup/view',scripts:true,scope:this
// //}
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
// win.show();
//        // winGroups.show(item);



  
</script>
<div id="sample-div">xs</div>