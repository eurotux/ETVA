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
    lvsizeText : 'Cannot exceed total volume group size'
});

Ext.ns("lvwin.createForm");

lvwin.createForm.Main = function(node_id) {

    Ext.QuickTips.init();
   	Ext.form.Field.prototype.msgTarget = 'side';

    this.node_id = node_id;

    this.totalvgsize = new Ext.form.Hidden({
        id: 'total-vg-size',
        name:'total-vg-size'
    });

    this.lvname = new Ext.form.TextField({        
        fieldLabel: 'Logical volume name',
        allowBlank: false,
        name:'lvname',
        maxLength: 50,
	    anchor: '90%'
    });


    this.lvsize = new Ext.form.TextField({
        id: 'form-lvsize',
        fieldLabel: 'Logical volume size',
        name: 'size',
        maxLength: 50,
        vtype: 'lvsize',
        allowBlank: false,
        totallvsize: 'total-vg-size',
	    anchor: '90%'
    });

    this.vg = new Ext.form.ComboBox({
        
        readOnly:true
        ,valueField:'name'

        // could be undefined as we use custom template
        ,displayField:'name'

        // query all records on trigger click
        ,triggerAction:'all'

        // minimum characters to start the search
        ,minChars:2

        // do not allow arbitrary values
        ,forceSelection:true

        // otherwise we will not receive key events
        ,enableKeyEvents:true

        // let's use paging combo
        ,pageSize:5

        // make the drop down list resizable
        ,resizable:true

        // we need wider list for paging toolbar
        ,minListWidth:250

        // force user to fill something
        ,allowBlank:false

        // store getting items from server
        ,store:new Ext.data.JsonStore({
            root:'data'
            ,totalProperty:'total'
            ,fields:[
                ,{name:'id'}
                ,{name:'name', type:'string'}
                ,{name:'size', mapping:'value', type:'string'}
            ]
            ,url:<?php echo json_encode(url_for('volgroup/jsonListFree'))?>
            ,baseParams:{'nid':node_id}
            ,listeners:{
                'loadexception':function(store,options,resp,error){
                    
                    var response = Ext.util.JSON.decode(resp.responseText);

                    Ext.Msg.show({title: 'Error',
                        buttons: Ext.MessageBox.OK,
                        msg: response['error'],
                        icon: Ext.MessageBox.ERROR});
                }

            }
        })
        // concatenate vgname and size (MB)
        ,tpl:'<tpl for="."><div class="x-combo-list-item">{name} - Size {[byte_to_MBconvert(values.size,2)]}</div></tpl>'

        // listeners
        ,listeners:{
            // sets raw value to concatenated last and first names
             select:function(combo, record, index) {
                var size = byte_to_MBconvert(record.get('size'),2);
                this.setRawValue(record.get('name') + ' - Size ' + size);              
                Ext.getCmp('total-vg-size').setValue(size);                
                Ext.getCmp('form-lvsize').setValue(size);
                
            }

            // repair raw value after blur
            ,blur:function() {
                var val = this.getRawValue();
                this.setRawValue.defer(1, this, [val]);
            }

            // set tooltip and validate
            ,render:function() {
                this.el.set(
                    //{qtip:'Type at least ' + this.minChars + ' characters to search in volume group'}
                    {qtip:'Choose volume group'}
                );
                this.validate();
            }
            // requery if field is cleared by typing
            ,keypress:{buffer:100, fn:function() {
                if(!this.getRawValue()) {
                    this.doQuery('', true);
                }
            }}
        
        }

        // label
        ,fieldLabel:'Volume Group'
        ,anchor:'90%'
    });// end this.vg
    

    // field set
    var allFields = new Ext.form.FieldSet({
        autoHeight:true,
        border:false,
        labelWidth:130,
        items: [this.lvname, this.vg, this.lvsize]
    });

    // define window and pop-up - render formPanel
    lvwin.createForm.Main.superclass.constructor.call(this, {        
        bodyStyle: 'padding-top:10px;',
        items: [allFields],
        buttons: [{
            text: 'Save',            
            handler: this.sendRequest,
            scope: this
            },
            {
            text: 'Cancel',
            scope:this,
            handler:function(){this.ownerCt.close();}
            }]// end buttons

    });// end superclass constructor    

};// end lvwin.createForm.Main function

// define public methods
Ext.extend(lvwin.createForm.Main, Ext.form.FormPanel, {
    
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
                          'lv':lvname,
                          'vg':this.vg.getValue(),
                          'size':this.lvsize.getValue()};

            var conn = new Ext.data.Connection({
                            listeners:{
                                // wait message.....
                                beforerequest:function(){
                                    Ext.MessageBox.show({
                                    title: 'Please wait',
                                    msg: 'Creating logical volume...',
                                    width:300,
                                    wait:true,
                                    modal: false
                                    });
                                },// on request complete hide message
                                requestcomplete:function(){Ext.MessageBox.hide();}
                            }
            });// end conn


            conn.request({
                url: <?php echo json_encode(url_for('logicalvol/jsonCreate'))?>,
                params: params,
                scope:this,
                success: function(resp,opt){
                    var response = Ext.util.JSON.decode(resp.responseText);
                    Ext.ux.Logger.info(response['response']);

                    var tree = Ext.getCmp('lv-tree');

                    //close window
                    this.ownerCt.close();

                    var node = new Ext.tree.TreeNode({text:lvname,iconCls:'devices-folder'});
                    tree.root.appendChild(node);

                    Ext.fly(node.ui.elNode).slideIn('l', {
                        callback: function(){tree.root.reload();},
                        scope: node,
                        duration: 0.4
                    });
                            
                    
                },
                failure: function(resp,opt) {
                    var response = Ext.util.JSON.decode(resp.responseText);
                    Ext.ux.Logger.error(response['error']);

                    Ext.Msg.show({title: 'Error',
                                  buttons: Ext.MessageBox.OK,
                                  msg: 'Unable to create logical volume '+lvname,
                                  icon: Ext.MessageBox.ERROR});
                }
            });// END Ajax request


        }//end isValid

    }

});


</script>