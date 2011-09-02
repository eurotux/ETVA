<script>
Ext.ns('DUMMY.Main');

/*
 * DUMMY Main Panel
 */
DUMMY.Main = Ext.extend(Ext.Panel,{
    title: <?php echo json_encode(__('Main panel')) ?>,
    layout:'fit',
    defaults:{border:false},
    initComponent:function(){

        this.items = [{
                layout:'vbox',
                layoutConfig:{
                    padding:'5',
                    align:'left'
                },
                defaults:{margins:'0 0 15 0'},
                items:[{html:'TEXTO'}]
        }];

        DUMMY.Main.superclass.initComponent.call(this);

        this.on({
            'activate':function(){
                if(this.items.length>0){
                  for(var i=0,len=this.items.length;i<len;i++){
                      var item = this.items.get(i);
                      item.fireEvent('reload');
                  }
                }
            }
        });

    }

});

    
</script>