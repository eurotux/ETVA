<script>
Ext.ns('ETFW.DHCP');


ETFW.DHCP = Ext.extend(Ext.Panel,{
    layout:'fit',    
    border:false,    
    //defaults:{border:false},
    title:'DHCP Server',
    initComponent:function(){        
      

        ETFW.DHCP.superclass.initComponent.call(this);

        this.on({
            'activate':function(){
                if(this.items.length>0){
                  for(var i=0,len=this.items.length;i<len;i++){
                      var item = this.items.get(i);
                      item.fireEvent('reload');
                  }
                }
            }
            ,afterlayout:{single:true, fn:function() {
                    
                    this.getEl().mask(<?php echo json_encode(__('Retrieving data...')) ?>);

                    var service_id = this.service['id'];
                    var network_dispatcher = this.network_service['id'];
                    var server_id = this.server['id'];

                    if(typeof ETFW.DHCP !='undefined' && typeof ETFW.DHCP.Main!='undefined'){

                        this.add(new ETFW.DHCP.Main({server_id:server_id,network_dispatcher:network_dispatcher,service_id:service_id}));
                        this.getEl().unmask();                        

                    }else{
                        // no js class loaded....
                        this.load({
                            url:<?php echo json_encode(url_for('etfw/view?dispatcher_id=')); ?>+service_id
                            ,scripts:true,scope:this
                            ,callback:function(){

                                this.add(new ETFW.DHCP.Main({server_id:server_id,network_dispatcher:network_dispatcher,service_id:service_id}));
                                this.doLayout();
                                this.getEl().unmask();
                            }
                        });
                    }
            }}// end afterlayout
            
        });

    }
});


</script>