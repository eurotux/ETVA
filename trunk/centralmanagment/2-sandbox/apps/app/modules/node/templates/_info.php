<script>
Ext.ns('Node.View');

Node.View.Info = Ext.extend(Ext.form.FormPanel, {
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
                                            fieldLabel : <?php echo json_encode(__('Node name')) ?>
                                        }
                                        ,{                                            
                                            name: 'mem_text',
                                            fieldLabel : <?php echo json_encode(__('Memory size (MB)')) ?>
                                        }
                                        ,{
                                            name: 'mem_available',
                                            fieldLabel : <?php echo json_encode(__('Memory available (MB)')) ?>
                                        }
                                        ,{                                            
                                            name: 'cputotal',
                                            fieldLabel : <?php echo json_encode(__('CPUs')) ?>
                                        }
                                        ,{
                                            name: 'network_cards',
                                            fieldLabel : <?php echo json_encode(__('Network cards')) ?>
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
                                                this.loadRecord({id:this.node_id});                                                
                                            }
                                        }
                                    ]//end items flex
                                }
                                ,{
                                    flex:1,
                                    defaultType:'displayfield',
                                    items:[
                                        {                                            
                                            name: 'hypervisor',
                                            fieldLabel : <?php echo json_encode(__('Hypervisor')) ?>
                                        },
                                        {
                                            name: 'state_text',
                                            fieldLabel : <?php echo json_encode(__('VirtAgent state')) ?>
                                        },
                                        {
                                            name: 'ip',
                                            fieldLabel : <?php echo json_encode(__('IP')) ?>
                                        }
                                    ]
                                }
                        ]
                    }
        ];

        Node.View.Info.superclass.initComponent.call(this);

        this.on({activate:{scope:this,fn:function(){
                    this.loadRecord({id:this.node_id});
                }}
        });

    }
    ,onRender:function(){
        // call parent
        Node.View.Info.superclass.onRender.apply(this, arguments);
        // set wait message target
        //this.getForm().waitMsgTarget = this.getEl();
    }
    ,loadRecord:function(data){        

        this.btn_refresh.addClass('x-item-disabled');                                               

        this.load({url:'node/jsonLoad',params:data
            ,scope:this
            ,waitMsg: <?php echo json_encode(__('Retrieving data...')) ?>
            ,method:'POST'
            ,success:function(f,a){
                this.btn_refresh.removeClass('x-item-disabled');
                var data = a.result['data'];                

                /*
                 * check state
                 */
                 var state_text = this.form.findField('state_text');
                 if(data['state']==1)
                 {                     
                     state_text.removeClass('vm-state-notrunning');
                     state_text.addClass('vm-state-running');
                   
                 }
                 else
                 {                     
                     state_text.removeClass('vm-state-running');
                     state_text.addClass('vm-state-notrunning');
                     
                 }

            }
        });
    }



});

</script>

