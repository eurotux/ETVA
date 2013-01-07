<script>

/*
 * validation type
 * validates lvsize form field
 * Cannot exceed volume group size
 */

Ext.ns("lvwin.convertForm");

lvwin.convertForm.Main = function(node_id, level) {

    Ext.QuickTips.init();   	

    this.level = level;
    this.node_id = node_id;    

    this.lvformat = new Ext.form.TextField({        
        fieldLabel: __('Original format'),
        allowBlank: false,
        name:'lvformat',
        maxLength: 50,
        selectOnFocus:true,
        readOnly:true,
	    anchor: '90%'
    });


    var baseParams;
    if(level == 'cluster'){
        baseParams = {'cid':node_id,'level':level};
    }else if(level == 'node'){
        baseParams = {'nid':node_id,'level':level};
    }else{
        baseParams = {'nid':node_id};
    }


    this.lvnewformat = new Ext.form.ComboBox({
                    ref:'lvformat',
                    name:'newformat',
                    id:'lvformat'
                    ,editable: false
                    ,typeAhead: false
                    ,fieldLabel: __('New format'),
                    width:150,hiddenName:'newformat'
                    ,valueField: 'format',displayField: 'format',forceSelection: true,emptyText: __('Select format...')
                    ,store: new Ext.data.ArrayStore({
                            fields: ['format'],
                            data : <?php
                                        /*
                                         * build interfaces model dynamic
                                         */
                                        $disk_formats = sfConfig::get('app_disk_formats');
                                        $disk_format_elem = array();

                                        foreach($disk_formats as $eformat)
                                            $disk_format_elem[] = '['.json_encode($eformat).']';
                                        echo '['.implode(',',$disk_format_elem).']'."\n";
                                    ?>
                            })
                    ,mode: 'local'
                    ,lastQuery:''
                    ,allowBlank:false
                    ,triggerAction: 'all'
    });

    // field set
    var allFields = new Ext.form.FieldSet({
        autoHeight:true,
        border:false,
        labelWidth:160,defaults:{msgTarget: 'side'},
        items: [this.lvformat, this.lvnewformat]
    });

    // define window and pop-up - render formPanel
    lvwin.convertForm.Main.superclass.constructor.call(this, {        
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
                        this.lvnewformat.focus.defer(500, this.lvnewformat);
                }}
            }

    });// end superclass constructor    

};// end lvwin.convertForm.Main function

// define public methods
Ext.extend(lvwin.convertForm.Main, Ext.form.FormPanel, {
    
    // load data
    load : function(node) {
        console.log(node);
        this.lv = node.attributes.text;
        this.vg = node.attributes.vg;
        this.lvuuid = node.attributes.uuid;

        this.lvformat.setValue(node.attributes.format);
    },
    /*
    * send soap request
    * on success store returned object in DB (lvStoreDB)
    */       
    sendRequest:function(){
        // if necessary fields valid...
        if(this.getForm().isValid()){            

            // create parameters array to pass to soap request....
            var params = { 'lv':this.lv, 'vg':this.vg, 'lvuuid': this.lvuuid, 'newformat':this.lvnewformat.getValue()};
    
            if(this.level == 'cluster'){
                params['cid'] = this.node_id;
                params['level'] = this.level;
            }else if(this.level == 'node'){
                params['nid'] = this.node_id;
                params['level'] = this.level;
            }else{
                params['nid'] = this.node_id;
            }

            var conn = new Ext.data.Connection({
                            listeners:{
                                // wait message.....
                                beforerequest:function(){

                                    Ext.MessageBox.show({
                                        title: <?php echo json_encode(__('Please wait...')) ?>,
                                        msg: <?php echo json_encode(__('Converting logical volume...')) ?>,
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
                url: <?php echo json_encode(url_for('logicalvol/jsonConvert'))?>,
                params: params,
                scope:this,
                success: function(resp,opt){
                    var response = Ext.util.JSON.decode(resp.responseText);
                    Ext.ux.Logger.info(response['agent'],response['response']);                    
                    this.fireEvent('updated');                                                
                    
                },
                failure: function(resp,opt) {
                    var response = Ext.util.JSON.decode(resp.responseText);
                    
                    if(response){
                        Ext.Msg.show({
                            title: String.format(<?php echo json_encode(__('Error {0}')) ?>,response['agent']),
                            buttons: Ext.MessageBox.OK,
                            msg: String.format(<?php echo json_encode(__('Unable to convert logical volume {0}!')) ?>,this.lv)+'<br>'+response['info'],
                            icon: Ext.MessageBox.ERROR});
                    }
                                        
                }
            });// END Ajax request


        }//end isValid

    }

});


</script>

