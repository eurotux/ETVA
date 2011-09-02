<script>

// Add the additional 'advanced' VTypes
// checks new size cannot exceed total device size
Ext.apply(Ext.form.VTypes, {
    
    pvsize : function(val, field) {
        if (field.totalpvsize) {
            var tsize = Ext.getCmp(field.totalpvsize);
            return (val <= parseFloat(tsize.getValue()));
        }
        
        return true;
    },
    pvsizeText : 'Cannot exceed total device size'
});




Ext.ns("pvwin.resizeForm");

pvwin.resizeForm.Main = function(id_win,id_node) {    

    this.device = new Ext.form.Hidden({
        id: 'device-id-'+id_win,
        value: null,
        name: 'device'
    });
    
    this.device_size = new Ext.form.TextField({
        fieldLabel: 'Total device size (MB)',
        allowBlank: false,
        name: 'device-size',
        id:'device-size-'+id_win,
        maxLength: 10,
        readOnly:true,
	    anchor: '90%'
    });
    this.unalloc = new Ext.form.TextField({
        fieldLabel: 'Unallocated size (MB)',
        allowBlank: false,
        name: 'unalloc',
        readOnly:true,
        maxLength: 50,
	    anchor: '90%'
    });
    this.pv_size = new Ext.form.TextField({
        fieldLabel: 'Actual volume size (MB)',
        allowBlank: false,
        name:'size',
        readOnly:true,
        maxLength: 50,
	    anchor: '90%'
    });

    this.pv_new_size = new Ext.form.TextField({
        id: 'pv-new-size-'+id_win,
        fieldLabel: 'New volume size (MB)',
        allowBlank: false,
        name:'new_size',
        maxLength: 50,
        vtype: 'pvsize',
        totalpvsize: 'device-size-'+id_win,
	    anchor: '90%'
    });

    // field set
    var allFields = new Ext.form.FieldSet({
        autoHeight:true,
        border:false,
        labelWidth:140,
        items: [this.device, this.device_size, this.unalloc, this.pv_size, this.pv_new_size]
    });


    
    // define window and pop-up - render formPanel
    pvwin.resizeForm.Main.superclass.constructor.call(this, {
        id: 'pvwin-resize-form-'+id_win,
        baseCls: 'x-plain',        
        defaultType: 'textfield',
        buttonAlign:'center',
        items: [allFields],

        buttons: [{            
            text: 'Save',
            handler: function() {
                if (Ext.getCmp('pvwin-resize-form-'+id_win).form.isValid()) {

                    var size = Ext.getCmp('pv-new-size-'+id_win).getValue();
                    var device = Ext.getCmp('device-id-'+id_win).getValue();
                    var device_node = Ext.getCmp('dev-tree').getNodeById(device);
                    var params = {'device':device,'size':size};
                       
		 	
                    var conn = new Ext.data.Connection({
                    listeners:{
                        // wait message.....
                        beforerequest:function(){
                            Ext.MessageBox.show({
                                title: 'Please wait',
                                msg: 'Resizing physical volume...',
                                width:300,
                                wait:true,
                                modal: false
                            });
                        },// on request complete hide message
                        requestcomplete:function(){Ext.MessageBox.hide();}}
                    });// end conn


                    conn.request({
                        url: <?php echo json_encode('node/getSoap?method=pvresize')?>,
                        params: {id:id_node,'params': Ext.encode(params)},
                        scope:this,
                        success: function(resp,opt) {
                                    var response = Ext.util.JSON.decode(resp.responseText);
                                    var tree = Ext.getCmp('dev-tree');
                                    Ext.getCmp('pv-resize-win-'+id_win).close();

                                    // update log panel with response info
                                    Ext.ux.Logger.info(response);


                                    // apply remove effect to dev-tree and reload content
                                    Ext.fly(device_node.ui.elNode).highlight("FFFF00",{
                                        callback: function(){tree.root.reload(function(){
                                                    var node = tree.getNodeById(device_node.parentNode.id);
                                                    node.expand(true);});},
                                        scope: device_node,
                                        duration: .4});
                        },
                        failure: function(resp,opt) {
                                    var response = Ext.util.JSON.decode(resp.responseText);

                                    Ext.ux.Logger.error(response);

                                    Ext.Msg.show({title: 'Error',
                                            buttons: Ext.MessageBox.OK,
                                            msg: 'Unable to resize '+device,
                                            icon: Ext.MessageBox.ERROR});
                        }
                    });// END Ajax request

             
             
                }// not valid
                else
                    Ext.MessageBox.alert('error', 'Please fix the errors noted.');
				}// end handler
            },// end Save
            {text:'Cancel',handler:function(){Ext.getCmp('pv-resize-win-'+id_win).close();}}]
    });// end superclass constructor

};// end resizeForm


// public methods
Ext.extend(pvwin.resizeForm.Main, Ext.form.FormPanel, {

    // load data
    load : function(node) {
     
        var unalloc_size = node.attributes.size - node.attributes.pvsize;
        var unnalloc = byte_to_MBconvert(unalloc_size,2,'floor');
        this.unalloc.setValue(unnalloc);
        
        var dev_size = byte_to_MBconvert(node.attributes.size,2,'floor');
        this.device_size.setValue(dev_size);
     
        this.device.setValue(node.id);
                
        this.pv_size.setValue(byte_to_MBconvert(node.attributes.pvsize,2,'floor'));

    }

});


</script>