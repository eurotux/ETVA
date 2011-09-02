<script>
Ext.ns('ETMS.MAILBOX');


ETMS.MAILBOX = Ext.extend(Ext.Panel,{
    layout:'fit',
    border:false,
    ref: '../mbpanel',
    //defaults:{border:false},
    title:<?php echo json_encode(__('Manage Mailboxes')) ?>,
    
    initComponent:function(){
        ETMS.MAILBOX.superclass.initComponent.call(this);

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
                    var server_id = this.server['id'];

                    if(typeof ETMS.MAILBOX !='undefined' && typeof ETMS.MAILBOX.Main!='undefined'){
                        this.mailboxpanel = new ETMS.MAILBOX.Main({ref:'mb_list',server_id:server_id,service_id:service_id});
                        this.add(this.mailboxpanel);
                        this.fireEvent('loadedPanel');
                        this.getEl().unmask();

                    }else{
                        // no js class loaded....
                        this.load({
                            url:<?php echo json_encode(url_for('etms/view?dispatcher_id=')); ?>+service_id
                            ,scripts:true,scope:this
                            ,callback:function(){
                                this.add(new ETMS.MAILBOX.Main({ref:'mb_list',server_id:server_id,service_id:service_id}));
                                this.doLayout();
                                this.fireEvent('loadedPanel');
                                this.getEl().unmask();
                            }
                        });
                    }
            }}// end afterlayout
        });
    }
    ,loadData:function(domainObj, reloadStore){
        if(typeof this.mb_list != 'undefined'){
            this.mb_list.loadData(domainObj, reloadStore);        //painel MAILBOX.Main
        }
    }
});


</script>