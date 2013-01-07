<script>
Ext.ns('Cluster.View');

Cluster.View.Info = Ext.extend(Ext.form.FormPanel, {
    border:false,    
    labelWidth:140,    
    defaults:{border:false},
    initComponent:function(){        
        
        this.items = [                
                    {
                        anchor: '100% 100%',
                        layout: {
                            type: 'hbox',
                            align: 'stretch'  // Child items are stretched to full width
                        }
                        ,defaults:{layout:'form',autoScroll:true,bodyStyle:'padding:10px;',border:false}
                        ,items:[
                                {
                                    flex:1,
                                    defaultType:'displayfield',
                                    items:[
                                        {
                                            name: 'name',
                                            fieldLabel : <?php echo json_encode(__('Cluster name')) ?>
                                        }
                                        ,{
                                            name: 'mem_text',
                                            fieldLabel : <?php echo json_encode(__('Memory (MB)')) ?>
                                        }
                                        ,{
                                            name: 'mem_available',
                                            fieldLabel : <?php echo json_encode(__('Memory available (MB)')) ?>
                                        }
                                        ,{                                            
                                            name: 'cpus',
                                            fieldLabel : <?php echo json_encode(__('CPUs')) ?>
                                        }
                                        ,{
                                            text: __('Refresh'),
                                            xtype: 'button',                                            
                                            ref:'../../btn_refresh',
                                            tooltip: __('Refresh'),
                                            iconCls: 'x-tbar-loading',
                                            scope:this,
                                            handler: function(button,event)
                                            {                                                
                                                this.loadRecord({id:this.cluster_id});                                                
                                            }
                                        }
                                    ]//end items flex
                                }
                                ,{
                                    flex:1,
                                    defaultType:'displayfield',
                                    items:[
                                        {
                                            name: 'n_nodes',
                                            fieldLabel : <?php echo json_encode(__('Nodes (up/down)')) ?>
                                        },
                                        {
                                            name: 'n_servers',
                                            fieldLabel : <?php echo json_encode(__('Servers (assign/unassign)')) ?>
                                        }
                                    ]
                                }
                        ]
                    }
        ];

        Cluster.View.Info.superclass.initComponent.call(this);

        this.on({activate:{scope:this,fn:function(){
                    this.loadRecord({id:this.cluster_id});
                }}
        });

    }
    ,onRender:function(){
        // call parent
        Cluster.View.Info.superclass.onRender.apply(this, arguments);
        // set wait message target
        //this.getForm().waitMsgTarget = this.getEl();
    }
    ,loadRecord:function(data){        

        this.btn_refresh.addClass('x-item-disabled');                                               

        this.load({url:'cluster/jsonLoad',params:data
            ,scope:this
            ,waitMsg: <?php echo json_encode(__('Retrieving data...')) ?>
            ,method:'POST'
            ,success:function(f,a){
                this.btn_refresh.removeClass('x-item-disabled');
                var data = a.result['data'];                


                this.form.findField('n_nodes').setValue( data['nodes_up'] + ' / ' + data['nodes_down'] );

                this.form.findField('n_servers').setValue( data['servers_assign'] + ' / ' + data['servers_unassign'] );
            }
        });
    }



});

</script>


