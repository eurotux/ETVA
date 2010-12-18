<script>

Ext.namespace('Agent');
  Agent.View = function(){
    var mainView;
    return{
      init:function(){
         

var bogusMarkup = '<p>Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Sed metus nibh, sodales a, porta at, vulputate eget, dui. Pellentesque ut nisl. Maecenas tortor turpis, interdum non, sodales non, iaculis ac, lacus. Vestibulum auctor, tortor quis iaculis malesuada, libero lectus bibendum purus, sit amet tincidunt quam turpis vel lacus. In pellentesque nisl non sem. Suspendisse nunc sem, pretium eget, cursus a, fringilla vel, urna.<br/><br/>Aliquam commodo ullamcorper erat. Nullam vel justo in neque porttitor laoreet. Aenean lacus dui, consequat eu, adipiscing eget, nonummy non, nisi. Morbi nunc est, dignissim non, ornare sed, luctus eu, massa. Vivamus eget quam. Vivamus tincidunt diam nec urna. Curabitur velit.</p>';
var shortBogusMarkup = '<p>Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Sed metus nibh, sodales a, porta at, vulputate eget, dui. Pellentesque ut nisl. Maecenas tortor turpis, interdum non, sodales non, iaculis ac, lacus. Vestibulum auctor, tortor quis iaculis malesuada, libero lectus bibendum purus, sit amet tincidunt quam turpis vel lacus. In pellentesque nisl non sem. Suspendisse nunc sem, pretium eget, cursus a, fringilla vel, urna.';


var tools = [{
               id:'gear',
               handler: function(e, target, panel){
                   panel.setSrc();
               }
           },{
               id:'close',
               handler: function(e, target, panel){
                   panel.ownerCt.remove(panel, true);
               }
        }];


var sourceWin;

var mifWin = new Ext.ux.ManagedIFrame.Window({

      title         : 'Simple MIF.Window',
      width         : 845,
      height        : 469,
      maximizable   : true,
      collapsible   : true,
      constrain     : true,
      shadow        : Ext.isIE,
      animCollapse  : false,
      autoScroll    : true,
      hideMode      : 'nosize',
      defaultSrc    : 'http://www.extjs.com',

      listeners : {
         domready : function(frameEl){  //raised for "same-origin" frames only

            var MIF = frameEl.ownerCt;
            notify({html:MIF.title+' reports:domready '});
            //Demo.balloon(null, );
         },
         documentloaded : function(frameEl){
            var MIF = frameEl.ownerCt;
            notify({html:MIF.title+' reports:docloaded '});
            // Demo.balloon(null, MIF.title+' reports:','docloaded ');
         },
         beforedestroy : function(){
            if(sourceWin){
                sourceWin.close();
                sourceWin = null;
            }
         }
       },

       tbar : [
           {
             text    :'View Source',
             iconCls :'source-icon',
             tooltip : 'View the Demo Source Code..',
             handler : function(button){

               var sourceType = 'javascript';
               sourceWin || (sourceWin = new Ext.ux.ManagedIFrame.Window(
                    {
                      title       : 'Demo Source',
                      iconCls     : 'source-icon',
                      width       : 600,
                      height      : 600,
                      autoScroll  : true,
                      constrain   : true,
                      closeAction : 'hide',
                      closable    : true,

                      tbar : [
                           {
                             text    :'Print',
                             iconCls :'demo-print',
                             tooltip : 'Print the source file.',
                             handler : function(button){
                                sourceWin.getFrame().print();
                              }
                            },
                             {
                             text    :'Save',
                             id      :'save-but',
                             iconCls :'demo-save',
                             tooltip : 'Save the source file. (IE only)',
                             disabled : true,
                             handler : function(button){
                                sourceWin.getFrame().execCommand('SaveAs',true);
                              }
                            }

                         ],

                      /**

                       * Write the source of this demo module directly to the frame

                       * using a Ext.DomHelper config.

                       */
                      html    : {tag : 'pre',
                                 cls : sourceType + ' codelife',
                                 cn : [
                                    {tag:'code',
                                    html: 'teste'
                                    }
                                   ]
                                },
                      listeners : {

                        //Bring the MIFWindow to the front if the nested document receives focus

                        focus : function(frameEl){
                            this.toFront();
                        },

                        //Using $JIT, sprinkle some syntax-highlighting CSS when the frame's dom is ready

                        domready : function(frameEl){

                       //     var module = String.format('codelife-{0}.css',sourceType);

//                            $JIT.css('ux/' + module, function(ok){
//                                //inject CSS directly into the iframe
//
//                                ok && $JIT.applyStyle(module, null, frameEl.getWindow());
//                            });

                            //See if the Browser supports the SaveAs command

                            Ext.getCmp('save-but').setDisabled(
                               !this.getFrameDocument().queryCommandEnabled('SaveAs')
                            );
                        }
                      }

                  }));

                sourceWin.show(button.btnEl);
            }
          },
          {
             text    :'Reload',
             iconCls :'demo-action',
             tooltip : 'Reload the Frame',
             handler : function(button){ mifWin.setSrc(); }
          }],

       sourceModule : 'mifsimple'

   });//.show();




 mainView = new Ext.Panel({

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
                  tools: tools,
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

 
        return mainView;
     }//Fim init
     


}
  }();
  
 // Ext.onReady(Network.Grid.init, Network.Grid);
</script>