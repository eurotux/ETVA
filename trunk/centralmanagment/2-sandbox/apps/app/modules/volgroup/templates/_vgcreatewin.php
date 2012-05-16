<script>


    Ext.ns("vgwin.createForm");


    vgwin.createForm.Main = function(id, level) {
               
        var baseParams;
        if(level == 'cluster'){
            baseParams = {cid:id, level:level};
            this.cluster_id = id;
            this.level = level;
        }else if(level == 'node'){
            baseParams = {nid:id, level:level};
            this.node_id = id;
            this.level = level;
        }else{
            baseParams = {nid:id};            
            this.node_id = id;
        }
 
        this.fromStore = new Ext.data.Store({
            proxy: new Ext.data.HttpProxy({                
                        url: <?php echo json_encode(url_for('physicalvol/jsonListAllocatable'))?>
                   }),
            baseParams:baseParams,
            listeners:{
                'beforeload':function(){                                    
                    this.ownerCt.body.mask(<?php echo json_encode(__('Please wait...')) ?>, 'x-mask-loading');
                },
                'load':function(){
                    this.ownerCt.body.unmask();                
                },
                'loadexception':function(store,options,resp,error){                                        
                    this.ownerCt.body.unmask();                    
                }
                ,
                scope:this
            },
            reader: new Ext.data.JsonReader({
                root: 'response',
                totalProperty: 'total'                
            }, [{name: 'value', mapping:'id'},{name: 'text', mapping:'name'}])
        });

        this.vgname = new Ext.form.TextField({            
            fieldLabel: <?php echo json_encode(__('Volume group name')) ?>,
            allowBlank: false,
            msgTarget: 'side',
            name: 'vgname',
            maxLength: 10
        });               

    
        this.pvs = new Ext.ux.ItemSelector({                            
                            name:"pvs",
                            fieldLabel:"Physical volumes",                           
                            imagePath:"/images/icons/",
                            multiselects: [{            
                                height:200,
                                width:150,
                              //  dataFields:["id", "name"],
                                legend: __('Available'),
                                store: this.fromStore,
                                displayField: 'text',
                                valueField: 'value'
                               },{
                                height:200,
                                width:150,
                                legend: __('Selected'),
                                store: [],
                                tbar:[{
                                    text: __('Clear'),
                                    handler:function(){
                                        this.pvs.reset.call(this.pvs);
                                    },scope:this
                               }]
                            }]
                        });
        var items = [this.vgname];
        <?php if($sf_user->getAttribute('etvamodel')!='standard'): ?>

            this.types = new Ext.form.ComboBox({
                        anchor:'90%',
                        emptyText: __('Select...'),fieldLabel: <?php echo json_encode(__('Physical volumes type')) ?>,triggerAction: 'all',
                        displayField:'name',
                        store:new Ext.data.Store({
                                sortInfo: { field: 'name',direction: 'DESC' },
                                proxy:new Ext.data.HttpProxy({url:<?php echo json_encode(url_for('physicalvol/jsonListTypes')) ?>})
                                ,reader: new Ext.data.JsonReader({
                                            root:'data',
                                            fields:['name']})
                                ,listeners:{
                                            'beforeload':function(){                                    
                                                this.ownerCt.body.mask(<?php echo json_encode(__('Please wait...')) ?>, 'x-mask-loading');                                                
                                            },
                                            'load':function(st){
                                                this.ownerCt.body.unmask();
                                                if(this.types.getValue()==''){
                                                    this.types.setValue(st.getAt(0).data['name']);
                                                    this.types.fireEvent('select',this.types,st.getAt(0),0);
                                                }
                                            
                                            },scope:this}
                        })
                        ,listeners:{

                                select:{scope:this, fn:function(combo, record, index) {

                                    this.fromStore.removeAll();
                                    this.pvs.toMultiselect.store.removeAll();


                                    this.fromStore.load({params:{'filter':Ext.encode({'storage_type':record.data['name']})}});
                                }}
                        }//end listeners
                    });

            items.push(this.types);
            
        <?php endif; ?>
        items.push(this.pvs);
        
        // define window and pop-up - render formPanel
        vgwin.createForm.Main.superclass.constructor.call(this, {            
            //width:500,
            labelWidth:140,
            monitorValid:true,            
            bodyStyle: 'padding:10px;',
            items:items,
            buttons: [{
                    text: __('Save'),
                    formBind:true,
                    handler: this.sendRequest,
                    scope: this
                },
                {text: __('Cancel'),handler:function(){this.ownerCt.close();},scope:this}
            ]// end buttons            

        });// end superclass contructor        

    };// end vgwin.createForm.Main

    Ext.extend(vgwin.createForm.Main, Ext.form.FormPanel, {

        // load data
        load : function() {
            //loads data for the physical volumes available combo
            //this.fromStore.load();
            <?php if($sf_user->getAttribute('etvamodel')!='standard'): ?>
                this.types.getStore().load();
            <?php else:?>
                this.fromStore.load();
            <?php endif;?>       

        },        
        sendRequest:function(){


            var vgname = this.vgname.getValue();            

            if(this.pvs.toStore.getCount() == 0){

                this.pvs.markInvalid(<?php echo json_encode(__('Form fields may not be submitted with invalid values!')) ?>);
                return false;
            }            

            if(this.getForm().isValid()){
                var pvs_string = this.pvs.getValue();
                var pvs = pvs_string.split(',');             
                //params: vgname, physical volume ID

                // create parameters array to pass to soap request....
                var params;
   
                if(this.level == 'cluster'){
                    params = {
                        'cid':this.cluster_id,
                        'level':this.level,
                        'vg':vgname,
                        'pvs':Ext.encode(pvs)
                    };
                }else if(this.level == 'node'){
                    params = {
                        'nid':this.node_id,
                        'level':this.level,
                        'vg':vgname,
                        'pvs':Ext.encode(pvs)
                    };
                }else{
                    params = {
                        'nid':this.node_id,
                        'level':this.level,
                        'vg':vgname,
                        'pvs':Ext.encode(pvs)
                        };
                }


                
                //var params = {'nid':this.node_id,'vg':vgname,'pvs':Ext.encode(pvs)};
                var conn = new Ext.data.Connection({
                    listeners:{
                        // wait message.....
                        beforerequest:function(){

                            Ext.MessageBox.show({
                                title: <?php echo json_encode(__('Please wait...')) ?>,
                                msg: <?php echo json_encode(__('Creating volume group...')) ?>,
                                width:300,
                                wait:true,
                                modal: false
                            });
                        },// on request complete hide message
                        requestcomplete:function(){Ext.MessageBox.hide();}
                        ,requestexception:function(c,r,o){
                                        Ext.MessageBox.hide();
                                        Ext.Ajax.fireEvent('requestexception',c,r,o);}
                    }
                });// end conn

                conn.request({
                    url: <?php echo json_encode(url_for('volgroup/jsonUpdate'))?>,
                    params: params,
                    scope:this,
                    success: function(resp,opt){
                        var response = Ext.util.JSON.decode(resp.responseText);
                        Ext.ux.Logger.info(response['agent'],response['response']);
                        this.fireEvent('onCreate');                        
                        
                        
                    }
                    ,
                    failure: function(resp,opt) {
                        var response = Ext.util.JSON.decode(resp.responseText);

                        Ext.ux.Logger.error(response['error']);

                        Ext.Msg.show({
                            title: String.format(<?php echo json_encode(__('Error {0}')) ?>,response['agent']),
                            buttons: Ext.MessageBox.OK,
                            msg: String.format(<?php echo json_encode(__('Unable to create volume group {0}')) ?>,vgname),
                            icon: Ext.MessageBox.ERROR});

                    }
                });// END Ajax request


            }//end isValid

        }

    });

</script>
