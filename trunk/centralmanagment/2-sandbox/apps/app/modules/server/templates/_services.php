<script>

Ext.ns('Server.View');

Server.View.Services = Ext.extend(Ext.Panel,{   
    layout:'fit',
    defaults:{border:false},
    initComponent:function(){

        Server.View.Services.superclass.initComponent.call(this);

        this.title = this.server['agent_tmpl'];

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
            ,'render':function(panel){
                Ext.getBody().mask(<?php echo json_encode(__('Retrieving data...')) ?>);

                var server_tmpl = this.server['agent_tmpl'];

                if(eval("typeof "+server_tmpl+"!='undefined'") && eval("typeof Service."+server_tmpl+"!='undefined'")){

                    //panel.add(eval("new "+server_tmpl+".View({server:this.server})"));
                    panel.add(eval("new Service."+server_tmpl+"({server:this.server})"));

                }else{


                    // no js class loaded....
                    panel.load({
                        url:<?php echo json_encode(url_for('service/view?sid=')); ?>+this.server['id']
                        ,scripts:true,scope:this
                        ,callback:function(){

                            this.add(eval("new Service."+server_tmpl+"({server:this.server})"));
                            this.doLayout();
                        }
                    });
                }// end else
            }// end render

        });
    }//end initComponent

});




</script>
