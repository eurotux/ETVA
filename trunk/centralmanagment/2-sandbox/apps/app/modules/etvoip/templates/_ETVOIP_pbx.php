<script>
Ext.ns('ETVOIP.PBX');


ETVOIP.PBX = Ext.extend(Ext.Panel,{
    layout:'fit',    
    border:false,    
    title: <?php echo json_encode(__('PBX')) ?>,
    initComponent:function(){       

        ETVOIP.PBX.superclass.initComponent.call(this);

        this.on({
            'activate':function(){                
                if(this.items.length>0){
                  for(var i=0,len=this.items.length;i<len;i++){                      
                      var item = this.items.get(i);
                      //on activate refresh content
                      item.fireEvent('reload');
                  }
                }
            }
            ,afterlayout:{single:true, fn:function() {

                    this.getEl().mask(<?php echo json_encode(__('Retrieving data...')) ?>);

                    var service_id = this.service['id'];

                    if(typeof ETVOIP.PBX !='undefined' && typeof ETVOIP.PBX.Main!='undefined'){

                        this.add(new ETVOIP.PBX.Main({service_id:service_id}));
                        this.doLayout();
                        this.getEl().unmask();

                    }else{
//                        // no js class loaded....
                        this.load({
                            url:<?php echo json_encode(url_for('etvoip/view?dispatcher_id=')); ?>+service_id
                            ,scripts:true,scope:this
                            ,callback:function(el,succ,resp,opt){

                                if(typeof ETVOIP.PBX !='undefined' && typeof ETVOIP.PBX.Main!='undefined')
                                    this.add(new ETVOIP.PBX.Main({service_id:service_id}));
                                else this.add({bodyStyle:'padding:10px',html: resp.responseText});
                                                                
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