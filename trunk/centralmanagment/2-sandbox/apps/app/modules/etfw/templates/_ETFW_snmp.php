<script>
Ext.ns('ETFW.SNMP');


ETFW.SNMP = Ext.extend(Ext.Panel,{
    layout:'fit',    
    border:false,            
    title: <?php echo json_encode(__('SNMP Server')) ?>,
    initComponent:function(){             

        ETFW.SNMP.superclass.initComponent.call(this);

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

                    if(typeof ETFW.SNMP !='undefined' && typeof ETFW.SNMP.Main!='undefined'){

                        this.add(new ETFW.SNMP.Main({service_id:service_id}));
                        this.getEl().unmask();                        

                    }else{
                        // no js class loaded....
                        this.load({
                            url:<?php echo json_encode(url_for('etfw/view?dispatcher_id=')); ?>+service_id
                            ,scripts:true,scope:this
                            ,callback:function(){

                                this.add(new ETFW.SNMP.Main({service_id:service_id}));
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