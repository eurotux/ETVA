<script>

ETFW_Webmin = function(){
        return{
            init:function(){



                var loadframe = new Ext.ux.ManagedIFrame.Panel(
                {
                    border:false,
               //     loadMask:{msg:'Loading...'},
                    // defaultSrc :  '<?php // echo $url.':'.$port ?>',
                    defaultSrc :  '<?php echo $url ?>/session_login.cgi?user=root&pass=ola123&page=/',

                    listeners : {

                documentloaded : function(){


                    notify({html:'Document loaded '});
                    // Demo.balloon(null, MIF.title+' reports:','docloaded ');
                },
                beforedestroy : function(){

                }
            }
                });


                var panel = {
                    xtype: 'panel',
                    title:'Webmin',
                    layout:'fit',
                    frame: true,
                    // height:300,
                  //  labelAlign: 'left',
                    bodyStyle:'padding:5px',
                    border:false,
                    items:[loadframe],
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
                    ]
                };

                return panel;
            }//Fim init


        }
    }();
</script>