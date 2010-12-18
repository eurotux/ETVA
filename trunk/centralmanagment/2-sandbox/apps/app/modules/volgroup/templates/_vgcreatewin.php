<script>


    Ext.ns("vgwin.createForm");


    vgwin.createForm.Main = function(node_id) {
        Ext.QuickTips.init();
        Ext.form.Field.prototype.msgTarget = 'side';
        this.node_id = node_id;
        this.fromStore = new Ext.data.Store({
            proxy: new Ext.data.HttpProxy({                
                        url: <?php echo json_encode(url_for('physicalvol/jsonListAllocatable'))?>
                   }),
            baseParams:{'nid':node_id},
            listeners:{
                'beforeload':function(){                                    
                    this.ownerCt.body.mask('Loading', 'x-mask-loading');                
                },
                'load':function(){
                    this.ownerCt.body.unmask();                
                },
                'loadexception':function(store,options,resp,error){
                    this.ownerCt.body.unmask();
                    var response = Ext.util.JSON.decode(resp.responseText);

                    Ext.Msg.show({title: 'Error',
                        buttons: Ext.MessageBox.OK,
                        msg: response['error'],
                        icon: Ext.MessageBox.ERROR});
                }
                ,
                scope:this
            },
            reader: new Ext.data.JsonReader({
                root: 'response',
                totalProperty: 'total'                
            }, [{name: 'id'},{name: 'name'}])
        });

        this.vgname = new Ext.form.TextField({            
            fieldLabel: 'Group Name',
            allowBlank: false,
            name: 'vgname',
            maxLength: 10
        });


        this.pvs = new Ext.ux.ItemSelector({                            
                            name:"pvs",
                            fieldLabel:"Physical volumes",
                            dataFields:["id", "name"],
                            toData:[],
                            msWidth:130,
                            msHeight:150,
                            valueField:"id",
                            displayField:"name",
                            imagePath:"/images/icons/",
                            toLegend:"Selected",
                            fromLegend:"Available",
                            fromStore: this.fromStore,
                            toTBar:[{
                                    text:"Clear",
                                    handler:function(){                                      
                                       this.pvs.reset.call(this.pvs);
                                    }
                                    ,
                                    scope:this
                                }]
                        });

        // define window and pop-up - render formPanel
        vgwin.createForm.Main.superclass.constructor.call(this, {


            /*
             * Ext.ux.ItemSelector Example Code
             */            
            width:410,
            bodyStyle: 'padding-top:10px;',

            items:[
                new Ext.form.FieldSet({
                    autoHeight:true,
                    border:false,
                    labelWidth:140,
                    items: [this.vgname,
                        this.pvs]// end fieldset items
                })// end fieldset
            ],
            buttons: [{
                    text: 'Save',
                    handler: this.sendRequest,
                    scope: this
                },
                {text:'Cancel',handler:function(){this.ownerCt.close();},scope:this}
            ]// end buttons

        });// end superclass contructor

    };// end vgwin.createForm.Main

    Ext.extend(vgwin.createForm.Main, Ext.form.FormPanel, {

        // load data
        load : function() {
            //loads data for the physical volumes available combo
            this.fromStore.load();

        },        
        sendRequest:function(){


            var vgname = this.vgname.getValue();            

            if(this.pvs.toStore.getCount() == 0){

                this.pvs.markInvalid("Invalid");
                return false;
            }            

            if(this.getForm().isValid()){
                var pvs_string = this.pvs.getValue();
                var pvs = pvs_string.split(',');             
                //params: vgname, physical volume ID
                var params = {'nid':this.node_id,'vg':vgname,'pvs':Ext.encode(pvs)};
                var conn = new Ext.data.Connection({
                    listeners:{
                        // wait message.....
                        beforerequest:function(){
                            Ext.MessageBox.show({
                                title: 'Please wait',
                                msg: 'Creating volume group...',
                                width:300,
                                wait:true,
                                modal: false
                            });
                        },// on request complete hide message
                        requestcomplete:function(){Ext.MessageBox.hide();}}
                });// end conn

                conn.request({
                    url: <?php echo json_encode(url_for('volgroup/jsonUpdate'))?>,
                    params: params,
                    scope:this,
                    success: function(resp,opt){
                        var response = Ext.util.JSON.decode(resp.responseText);
                        Ext.ux.Logger.info(response['response']);
                        var tree = Ext.getCmp('vg-tree');

                        //close window
                        this.ownerCt.close();
                        tree.root.reload();
                        
                    }
                    ,
                    failure: function(resp,opt) {
                        var response = Ext.util.JSON.decode(resp.responseText);

                        Ext.ux.Logger.error(response['error']);

                        Ext.Msg.show({title: 'Error',
                            buttons: Ext.MessageBox.OK,
                            msg: 'Unable to create volume group '+vgname,
                            icon: Ext.MessageBox.ERROR});
                    }
                });// END Ajax request




            }//end isValid




        }

    });


</script>