<script>

/*
 * validation type
 * validates lvsize form field
 * Cannot exceed volume group size
 */
Ext.apply(Ext.form.VTypes, {

    lvsize : function(val, field) {
        if (field.totallvsize) {            
            var tsize = Ext.getCmp(field.totallvsize);
            return (val > 0 && val <= parseFloat(tsize.getValue()));
        }

        return true;
    },
    lvsizeText : <?php echo json_encode(__('Cannot exceed total volume group size')) ?>
});

Ext.ns("lvwin.createSnapshotForm");

lvwin.createSnapshotForm.Main = function(node_id) {

    Ext.QuickTips.init();   	

    this.node_id = node_id;

    /*this.totalvgsize = new Ext.form.Hidden({
   //     id: 'total-vg-size',
        name:'total-vg-size'
    });*/
    this.vg_free_size = new Ext.form.DisplayField({
        fieldLabel: <?php echo json_encode(__('Maximum snapshot size (MB)')) ?>,
        allowBlank: false,
        ref:'vg_free_size',
        name: 'vg-free-size',
        readOnly:true,
        anchor: '90%'
    });

    this.lvname = new Ext.form.TextField({        
        fieldLabel: <?php echo json_encode(__('Snapshot name')) ?>,
        allowBlank: false,
        name:'lvname',        
        maxLength: 50,
        selectOnFocus:true,
	    anchor: '90%'
    });


    this.lv_size = new Ext.form.NumberField({
       // id: 'form-lvsize',
        fieldLabel: <?php echo json_encode(__('Snapshot size (MB)')) ?>,
        name: 'size',
        maxLength: 50,
        vtype: 'lvsize',
        allowBlank: false,
        totallvsize: this.vg_free_size.id,
        //'total-vg-size',
	    anchor: '90%'
        ,validator:function(v){

            if (this.ownerCt.vg_free_size) {
                var tsize = this.ownerCt.vg_free_size;
                var csize = this.ownerCt.lv_size;

                //if(v == parseFloat(csize.getValue())) return 'Nothing to do';
                if(v > parseFloat(tsize.getValue())) return <?php echo json_encode(__('Cannot exceed total volume group size')) ?>;
            }
            return true;

        }
    });

    

    // field set
    var allFields = new Ext.form.FieldSet({
        autoHeight:true,
        border:false,
        labelWidth:160,defaults:{msgTarget: 'side'},
        items: [this.lvname, this.vg_free_size, this.lv_size]
    });

    // define window and pop-up - render formPanel
    lvwin.createSnapshotForm.Main.superclass.constructor.call(this, {        
        bodyStyle: 'padding-top:10px;',monitorValid:true,
        items: [allFields],
        buttons: [{
            text: __('Save'),
            formBind:true,
            handler: this.sendRequest,
            scope: this
            },
            {
            text: __('Cancel'),
            scope:this,
            handler:function(){this.ownerCt.close();}
            }]// end buttons
        ,listeners:{
                render:{delay:100,fn:function(){
                        this.lvname.focus.defer(500, this.lvname);                                                                  
                }}
            }

    });// end superclass constructor    

};// end lvwin.createSnapshotForm.Main function

// define public methods
Ext.extend(lvwin.createSnapshotForm.Main, Ext.form.FormPanel, {
    
    // load data
    load : function(node) {
        var node_vgfreesize = parseFloat(node.attributes.vgfreesize);
        var node_size = parseFloat(node.attributes.size);
        var total_vg_free_size = ( node_vgfreesize > node_size ) ? (node_vgfreesize + node_size) : (node_vgfreesize);

        var vg_free_size = byte_to_MBconvert(total_vg_free_size,2,'floor');

        this.vg_free_size.setValue(vg_free_size);

        this.lv = node.attributes.text;

        this.lv_size.setValue(byte_to_MBconvert(node.attributes.size,2,'floor'));

    },
    /*
    * send soap request
    * on success store returned object in DB (lvStoreDB)
    */       
    sendRequest:function(){
        // if necessary fields valid...
        if(this.getForm().isValid()){            
            var lvname = this.lvname.getValue();                        

            // create parameters array to pass to soap request....
            var params = {
                          'nid':this.node_id,
                          'slv':lvname,
                          'olv':this.lv,
                          'size':this.lv_size.getValue()+'M'};

            var conn = new Ext.data.Connection({
                            listeners:{
                                // wait message.....
                                beforerequest:function(){

                                    Ext.MessageBox.show({
                                        title: <?php echo json_encode(__('Please wait...')) ?>,
                                        msg: <?php echo json_encode(__('Creating logical volume snapshot...')) ?>,
                                        width:300,
                                        wait:true
                                    });

                                },// on request complete hide message
                                requestcomplete:function(){Ext.MessageBox.hide();}
                                ,requestexception:function(c,r,o){
                                        Ext.MessageBox.hide();
                                        Ext.Ajax.fireEvent('requestexception',c,r,o);}
                            }
            });// end conn


            conn.request({
                url: <?php echo json_encode(url_for('logicalvol/jsonCreateSnapshot'))?>,
                params: params,
                scope:this,
                success: function(resp,opt){
                    var response = Ext.util.JSON.decode(resp.responseText);
                    Ext.ux.Logger.info(response['agent'],response['response']);                    
                    this.fireEvent('updated');                                                
                    
                },
                failure: function(resp,opt) {
                    var response = Ext.util.JSON.decode(resp.responseText);
                    
                    if(response)
                    {
                        if(response['action']=='reload'){
                            
                            Ext.Msg.show({
                            title: String.format(<?php echo json_encode(__('Error {0}')) ?>,response['agent']),
                            buttons: Ext.MessageBox.OK,
                            msg: String.format(<?php echo json_encode(__('Error reloading logical volume {0}!')) ?>,lvname)+'<br>'+response['info'],
                            icon: Ext.MessageBox.ERROR});
                        }
                        else
                        {
                            Ext.Msg.show({
                                title: String.format(<?php echo json_encode(__('Error {0}')) ?>,response['agent']),
                                buttons: Ext.MessageBox.OK,
                                msg: String.format(<?php echo json_encode(__('Unable to create logical volume {0} snapshot!')) ?>,lvname)+'<br>'+response['info'],
                                icon: Ext.MessageBox.ERROR});
                        }
                    }
                                        
                }
            });// END Ajax request


        }//end isValid

    }

});


</script>
