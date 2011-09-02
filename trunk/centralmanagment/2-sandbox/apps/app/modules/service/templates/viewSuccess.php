<script>
Ext.ns('Service');

Service.<?php echo $etva_server->getAgentTmpl() ?> = Ext.extend(Ext.Panel,{    
    //title: <?php // echo json_encode($etva_server->getAgentTmpl()) ?>,
    layout:'fit',
    defaults:{border:false},
    initComponent:function(){

        Service.<?php echo $etva_server->getAgentTmpl() ?>.superclass.initComponent.call(this);

        this.on({
            'activate':function(){
                if(this.items.length>0){
                  for(var i=0,len=this.items.length;i<len;i++){
                      var item = this.items.get(i);
                      item.fireEvent('reload');
                  }
                }
            }
            ,'refresh':{scope:this,fn:function(){
                this.fireEvent('activate');
            }}
            <?php                
                $directory = sfContext::getInstance()->getConfiguration()->getTemplateDir(strtolower($etva_server->getAgentTmpl()), 'viewSuccess.php');
                
                if($directory):
            ?>
            ,'render':function(panel){
                Ext.getBody().mask(<?php echo json_encode(__('Retrieving data...')) ?>);

                var server_tmpl = this.server['agent_tmpl'];

                if(eval("typeof "+server_tmpl+"!='undefined'") && eval("typeof "+server_tmpl+".View!='undefined'")){
                    
                    panel.add(eval("new "+server_tmpl+".View({server:this.server})"));

                }else{


                    // no js class loaded....
                    panel.load({
                        url:<?php echo json_encode(url_for(strtolower($etva_server->getAgentTmpl()).'/view')); ?>
                        ,params:{sid:this.server['id']}
                        ,scripts:true,scope:this
                        ,callback:function(){
                            
                            this.add(eval("new "+server_tmpl+".View({server:this.server})"));
                            
                            this.doLayout();
                        }
                    });
                }// end else
            }// end render
            <?php else: ?>
            ,'render':function(panel){
                panel.add({bodyStyle:'padding:10px',html: <?php echo json_encode(__('No module view found!')) ?>});
                Ext.getBody().unmask();
            }
            <?php endif; ?>

        });
    }//end initComponent

});

</script>