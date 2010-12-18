<script>

Ext.namespace('Agent');

Agent.Webmin = function(){
    var mainView;
    return{
      init:function(){


var bogusMarkup = '<p>Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Sed metus nibh, sodales a, porta at, vulputate eget, dui. Pellentesque ut nisl. Maecenas tortor turpis, interdum non, sodales non, iaculis ac, lacus. Vestibulum auctor, tortor quis iaculis malesuada, libero lectus bibendum purus, sit amet tincidunt quam turpis vel lacus. In pellentesque nisl non sem. Suspendisse nunc sem, pretium eget, cursus a, fringilla vel, urna.<br/><br/>Aliquam commodo ullamcorper erat. Nullam vel justo in neque porttitor laoreet. Aenean lacus dui, consequat eu, adipiscing eget, nonummy non, nisi. Morbi nunc est, dignissim non, ornare sed, luctus eu, massa. Vivamus eget quam. Vivamus tincidunt diam nec urna. Curabitur velit.</p>';
var shortBogusMarkup = '<p>Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Sed metus nibh, sodales a, porta at, vulputate eget, dui. Pellentesque ut nisl. Maecenas tortor turpis, interdum non, sodales non, iaculis ac, lacus. Vestibulum auctor, tortor quis iaculis malesuada, libero lectus bibendum purus, sit amet tincidunt quam turpis vel lacus. In pellentesque nisl non sem. Suspendisse nunc sem, pretium eget, cursus a, fringilla vel, urna.';

var loadframe = new Ext.ux.ManagedIFrame.Panel(
    {
                   // xtype     :'iframepanel',
                   border:false,
                  bbar:['->',{text: 'Reload',
                            // xtype: 'button',
                            tooltip: 'Reload frame',
                            iconCls: 'x-tbar-loading',
                            
                            handler: function(button,event)
                            {
                                button.addClass('x-item-disabled');
                                //alert(this.ownerCt.id);
                                loadframe.setSrc('',false,function(){
                                    button.removeClass('x-item-disabled');
                                });
                                
                            }
                        }
                  ],                  
                  loadMask:{msg:'Loading...'},


                  //hideMode  :!Ext.isIE?'nosize':'display',

                  //defaults:{
                        // hideMode:!Ext.isIE?'nosize':'display'
                  //},
                  title:'Webmin',                 


                        // defaultSrc :  'http://www.google.pt'
                        // defaultSrc :  'http://gmail.com'
                        defaultSrc :  'http://10.10.20.79:10000/session_login.cgi?user=root&pass=ola123'
                         






              });




        var tabsNestedLayouts = {
	xtype: 'tabpanel',
    border:false,
	activeTab: 1,
	items:[{
		title: 'Foo',
		layout: 'border',
		items: [{
			region: 'north',
			title: 'North',
			height: 75,
			maxSize: 150,
			margins: '5 5 0 5',
			bodyStyle: 'padding:10px;',
			split: true,
			html: 'Some content'
		},{
			xtype: 'tabpanel',
			plain: true,
			region: 'center',
			margins: '0 5 5 5',
			activeTab: 0,
			items: [{
				title: 'Inner Tab 1',
				bodyStyle: 'padding:10px;',
                html: '<iframe width="640" height="480" frameborder=0 src="http://gmail.com"></iframe>'
			},{
				title: 'Inner Tab 2',
				cls: 'inner-tab-custom', // custom styles in layout-browser.css
				layout: 'border',
                // Make sure IE can still calculate dimensions after a resize when the tab is not active.
                // With display mode, if the tab is rendered but hidden, IE will mess up the layout on show:
                hideMode: Ext.isIE ? 'offsets' : 'display',
				items: [{
					title: 'West',
					region: 'west',
					collapsible: true,
					width: 150,
					minSize: 100,
					maxSize: 350,
					margins: '5 0 5 5',
					cmargins: '5 5 5 5',
					html: 'Hello',
					bodyStyle:'padding:10px;',
					split: true
				},{
					xtype: 'tabpanel',
					region: 'center',
					margins: '5 5 5 0',
					tabPosition: 'bottom',
					activeTab: 0,
					items: [{
						// Panels that are used as tabs do not have title bars since the tab
						// itself is the title container.  If you want to have a full title
						// bar within a tab, you can easily nest another panel within the tab
						// with layout:'fit' to acheive that:
						title: 'Bottom Tab',
						layout: 'fit',
						items: {
							title: 'Interior Content',
							bodyStyle:'padding:10px;',
							border: false,
							html: 'See the next tab for a nested grid. The grid is not rendered until its tab is first accessed.'
						}
					},{
						// A common mistake when adding grids to a layout is creating a panel first,
						// then adding the grid to it.  GridPanel (xtype:'grid') is a Panel subclass,
						// so you can add it directly as an item into a container.  Typically you will
						// want to specify layout:'fit' on GridPanels so that they'll size along with
						// their container and take up the available space.
						title: 'Nested Grid',
						xtype: 'grid',
						layout: 'fit',
				        store: new Ext.data.ArrayStore({
					        fields: [
					           {name: 'company'},
					           {name: 'price', type: 'float'},
					           {name: 'change', type: 'float'},
					           {name: 'pctChange', type: 'float'},
					           {name: 'lastChange', type: 'date', dateFormat: 'n/j h:ia'}
					        ]
					    }),
				        columns: [
				            {id:'company',header: 'Company', width: 160, sortable: true, dataIndex: 'company'},
				            {header: 'Price', width: 75, sortable: true, renderer: 'usMoney', dataIndex: 'price'},
				            {header: 'Change', width: 75, sortable: true, dataIndex: 'change'},
				            {header: '% Change', width: 75, sortable: true, dataIndex: 'pctChange'},
				            {header: 'Last Updated', width: 85, sortable: true, renderer: Ext.util.Format.dateRenderer('m/d/Y'), dataIndex: 'lastChange'}
				        ],
				        stripeRows: true,
				        autoExpandColumn: 'company',

				        // Add a listener to load the data only after the grid is rendered:
				        listeners: {
				        	render: function(){
				        		this.store.loadData(myData);
				        	}
				        }
					}]
				}]
			}]
		}]
	},loadframe
	//	title: 'Webmin',
	//	bodyStyle: 'padding:10px;',
	//	html: 'Nothing to see here.',
        
	]
};


mainView = new Ext.Panel({
title: 'Services',
layout:'fit'
// ,defaults: {autoScroll:true}
,items:tabsNestedLayouts
});
         mainVisew = new Ext.Panel({
       // renderTo:'tabs',

        resizeTabs:true, // turn on tab resizing
        minTabWidth: 115,
        tabWidth:135,
        enableTabScroll:true,
        width:600,
        height:250,
        defaults: {autoScroll:true}
        //plugins: new Ext.ux.TabCloseMenu()
    });

    // tab generation code
//    var index = 0;
//    while(index < 7){
//
//        mainView.add({
//            title: 'New Tab ' + (++index),
//            iconCls: 'tabs',
//            html: 'Tab Body ' + (index) + '<br/><br/>'
//                    + bogusMarkup,
//            closable:true
//        }).show();
//    }

//    new Ext.Button({
//        text: 'Add Tab',
//        handler: addTab,
//        iconCls:'new-tab'
//    }).render(document.body, 'tabs');


mainVierw = new Ext.Panel({
//activeItem:0,
//defaultxType:'tabpanel',
items:[{
        html:'fd',
        title:'fd'
}]
});

 mainViefw = new Ext.Panel({

title:'daa',
layout:'border',
              items:[{
                  region:'west',
                  id:'west-panel',
                  title:'West',
                  split:true,
                  width: 200,
                  minSize: 175,
                  maxSize: 400,
                  collapsible: true,
                  animCollapse  :Ext.isIE,
                  margins       :'5 0 5 5',
                  cmargins      :'5 5 5 5',
                  layout        :'accordion',
                  defaultType   :'iframepanel',
                  layoutConfig  :{
                      //animate:  Ext.isIE
                  },
                  defaults      :{
                        loadMask:false,
                        border:false
                   },
                  items : [{
                      html: shortBogusMarkup,
                      title:'Navigation'
                     },{
                      title:'Settings',
                      html: shortBogusMarkup
                      }]
              },
              {
                  xtype:'tabpanel',
                  region    :'center',
                  margins   :'5 5 5 0',
        deferredRender:false,
        defaults:{autoScroll: true},
        defaultType:"iframepanel",
        activeTab:0,

              items:[
              {
                   xtype     :'iframepanel',

                   border:true,
                //  tools: tools,
                  loadMask:{msg:'Loading Printable Bogus Markup...'},


                  //hideMode  :!Ext.isIE?'nosize':'display',

                  //defaults:{
                        // hideMode:!Ext.isIE?'nosize':'display'
                  //},
                  title:'ManagedIframe Portlets',


                        // defaultSrc :  'http://www.google.pt'
                          defaultSrc :  '/app_dev.php/server/img'






              }
             // ,new Ext.ContentPanel(new Ext.ux.ManagedIFrame( {autoCreate : {src:'mapgen.php'}})
        //,{title: 'Location', fitToFrame:true})

          ]

              }]

          });


        return tabsNestedLayouts;
     }//Fim init



}
  }();




  Agent.View = function(){
    var mainView;
    return{
      init:function(){
         


mainView = new Ext.Panel({
title: 'Services',
layout:'fit'
// ,defaults: {autoScroll:true}

});


// mainView.add(Agent.Webmin.init()).show();
mainView.add(Agent.Webmin.init());

 
        return mainView;
     }//Fim init
     


}
  }();
  
 // Ext.onReady(Network.Grid.init, Network.Grid);
</script>