<script>

    // Add the additional 'advanced' VTypes
    // checks new size cannot exceed total device size
    Ext.apply(Ext.form.VTypes, {

        lvsize : function(val, field) {
            if (field.totallvsize) {
                var tsize = Ext.getCmp(field.totallvsize);
                return (val <= parseFloat(tsize.getValue()));
            }

            return true;
        },
        lvsizeText : 'Cannot exceed volume group available size'
    });




    Ext.ns("lvwin.resizeForm");

    lvwin.resizeForm.Main = function(node_id) {

        Ext.QuickTips.init();
        Ext.form.Field.prototype.msgTarget = 'side';
        this.node_id = node_id;
        this.lv = '';

        this.vg_free_size = new Ext.form.TextField({
            fieldLabel: 'Volume group available size (MB)',
            allowBlank: false,
            name: 'vg-free-size',
            id:'vg-free-size',
            maxLength: 10,
            readOnly:true,
            anchor: '90%'
        });

        this.lv_size = new Ext.form.TextField({
            fieldLabel: 'Actual volume size (MB)',
            allowBlank: false,
            name:'lv-size',
            readOnly:true,
            maxLength: 50,
            anchor: '90%'
        });

        this.lv_new_size = new Ext.form.TextField({
            id: 'lv-new-size',
            fieldLabel: 'New volume size (MB)',
            allowBlank: false,
            name:'lv_new_size',
            maxLength: 50,
            vtype: 'lvsize',
            totallvsize: 'vg-free-size',
            anchor: '90%'
        });

        // field set
        var allFields = new Ext.form.FieldSet({
            autoHeight:true,
            border:false,
            labelWidth:140,
            items: [this.vg_free_size, this.lv_size, this.lv_new_size]
        });




        lvwin.resizeForm.Main.superclass.constructor.call(this, {
            id: 'lvwin-resize-form',
            baseCls: 'x-plain',
            defaultType: 'textfield',
            buttonAlign:'center',
            items: [allFields],

            buttons: [{
                    text: 'Save',
                    scope:this,
                    handler: this.sendRequest
                },// end Save
                {text:'Cancel',
                    scope:this,
                    handler:function(){this.ownerCt.close();}}]
        });// end superclass constructor

    };// end resizeForm


    // public methods
    Ext.extend(lvwin.resizeForm.Main, Ext.form.FormPanel, {

        // load data
        load : function(node) {


            var total_vg_free_size = parseFloat(node.attributes.vgfreesize) + parseFloat(node.attributes.size);

            var vg_free_size = byte_to_MBconvert(total_vg_free_size,2);

            this.vg_free_size.setValue(vg_free_size);

            this.lv = node.attributes.text;

            this.lv_size.setValue(byte_to_MBconvert(node.attributes.size,2));

        },        
        /*
         * send soap request
         * on success store returned object in DB (lvStoreDB)
         */
        sendRequest:function(){

            if (this.form.isValid()) {

                var size = this.lv_new_size.getValue();
                
                //var lv_node = Ext.getCmp('lv-tree').getNodeById(lv_id);
                //var params = {'lv':lv_node.attributes.vg+'/'+lv_node.attributes.text,'size':size};


                var conn = new Ext.data.Connection({
                    listeners:{
                        // wait message.....
                        beforerequest:function(){
                            Ext.MessageBox.show({
                                title: 'Please wait',
                                msg: 'Resizing logical volume...',
                                width:300,
                                wait:true,
                                modal: false
                            });
                        },// on request complete hide message
                        requestcomplete:function(){Ext.MessageBox.hide();}}
                });// end conn


                conn.request({
                    url: <?php echo json_encode(url_for('logicalvol/jsonResize'))?>,                    
                    params: {'nid':this.node_id,'lv':this.lv,'size': size},
                    scope:this,
                    success: function(resp,opt){
                        var response = Ext.util.JSON.decode(resp.responseText);
                        Ext.ux.Logger.info(response['response']);

                        var tree = Ext.getCmp('lv-tree');
                        this.ownerCt.close();

                        tree.root.reload();
                                                
                    },
                    failure: function(resp,opt) {
                        var response = Ext.util.JSON.decode(resp.responseText);

                        Ext.ux.Logger.error(response['error']);

                        Ext.Msg.show({title: 'Error',
                            buttons: Ext.MessageBox.OK,
                            msg: 'Unable to resize '+this.lv,
                            icon: Ext.MessageBox.ERROR});
                    }
                });// END Ajax request



            }// not valid
            else Ext.MessageBox.alert('error', 'Please fix the errors noted.');

        }

    });


</script>