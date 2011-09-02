<style type="text/css">
.removeicon {
	background: url(images/icons/icon-warning.gif) no-repeat;
}
</style>

<script>
Ext.ns('ETMS.DOMAIN');

function dump(arr,level) {
    var dumped_text = "";
    if(!level) level = 0;

    //The padding given at the beginning of the line.
    var level_padding = "";
    for(var j=0;j<level+1;j++) level_padding += "    ";

    if(typeof(arr) == 'object') { //Array/Hashes/Objects
            for(var item in arr) {
                    var value = arr[item];

                    if(typeof(value) == 'object') { //If it is an array,
                            dumped_text += level_padding + "'" + item + "' ...\n";
                            dumped_text += dump(value,level+1);
                    } else {
                            dumped_text += level_padding + "'" + item + "' => \"" + value + "\"\n";
                    }
            }
    } else { //Stings/Chars/Numbers etc.
            dumped_text = "===>"+arr+"<===("+typeof(arr)+")";
    }
    return dumped_text;
}


ETMS.DOMAIN = Ext.extend(Ext.Panel,{
    layout:'fit',
    border:false,
    //defaults:{border:false},
    title:<?php echo json_encode(__('Manage Domains')) ?>,
    initComponent:function(){
        ETMS.DOMAIN.superclass.initComponent.call(this);

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

                    if(typeof ETMS.DOMAIN !='undefined' && typeof ETMS.DOMAIN.Main!='undefined'){

                        this.add(new ETMS.DOMAIN.MainLayout({server_id:server_id,service_id:service_id}));
                        this.getEl().unmask();

                    }else{
                        // no js class loaded....
                        this.load({
                            url:<?php echo json_encode(url_for('etms/view?dispatcher_id=')); ?>+service_id
                            ,scripts:true,scope:this
                            ,callback:function(){
                                this.add(new ETMS.DOMAIN.MainLayout({server_id:server_id,service_id:service_id,maintab:this.maintab,mbtabidx:this.mbtabidx}));
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