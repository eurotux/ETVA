<script>
    
Ext.ns("lvwin.resizeForm");

lvwin.resizeForm.Main = function(node_id, level) {

    Ext.QuickTips.init();

    this.level = level;
    this.node_id = node_id;   
    
    this.lv = '';

    this.vg_free_size = new Ext.form.NumberField({
        fieldLabel: <?php echo json_encode(__('Maximum logical volume size (MB)')) ?>,
        allowBlank: false,
        ref:'vg_free_size',
        name: 'vg-free-size',
        readOnly:true,
        anchor: '90%'
    });

    this.lv_size = new Ext.form.NumberField({
        fieldLabel: <?php echo json_encode(__('Current logical volume size (MB)')) ?>,
        allowBlank: false,
        name:'lv-size',
        ref:'lv_size',
        readOnly:true,
        maxLength: 50,
        anchor: '90%'
    });

    this.lv_new_size = new Ext.form.NumberField({        
        fieldLabel: <?php echo json_encode(__('New logical volume size (MB)')) ?>,
        name:'lv_new_size',
        ref:'lv_new_size',
        maxLength: 50,
        allowBlank:false,
        validator:function(v){

            if (this.ownerCt.vg_free_size) {
                var tsize = this.ownerCt.vg_free_size;
                var csize = this.ownerCt.lv_size;

                //if(v == parseFloat(csize.getValue())) return 'Nothing to do';
                if(v > parseFloat(tsize.getValue())) return <?php echo json_encode(__('Cannot exceed total volume group size')) ?>;

                var size_snapshots = this.ownerCt.lv_snapshots_size;
                if(v < parseFloat(size_snapshots.getValue())) return <?php echo json_encode(__('Cannot reduce to the snashots size')) ?>;
            }
            return true;

        },scope:this,
        listeners:{
            specialkey:{scope:this,fn:function(field,e){

                if(e.getKey()==e.ENTER){
                    this.resizeLv();
                }
            }}
            ,change: { scope: this, fn: function(field,v,o){
                var size_snapshots = field.ownerCt.lv_snapshots_size.getValue();
                if( size_snapshots < v ){
                    var per_usage_snapshots = size_snapshots * 100 / v;
                    var persnapshotusage = Math.ceil(per_usage_snapshots);
                    this.lvsnapshotusage.setMinValue(persnapshotusage);
                    //console.log(field);
                    //console.log(persnapshotusage);

                    if( this.lvsnapshotusage.getValue() < persnapshotusage ){
                        this.lvsnapshotusage.setValue(persnapshotusage);
                    }
                    //console.log(this.lvsnapshotusage.getValue());
                }
            }}
        },
        anchor: '90%'
    });

    this.lv_snapshots_size = new Ext.form.Hidden({
        name:'lv_snapshots_size',
        ref: 'lv_snapshots_size'
    });

    this.lvsnapshotusage = new Ext.form.SliderField({
                                ref: 'lvsnapshotusage',
                                id: 'lvsnapshotusage',
                                name: 'snapshotusage',
                                //disabled: true,
                                anchor: '95%',
                                tipText: function(thumb){
                                            return String(thumb.value) + '%';
                                },
                                value: 20,
                                fieldLabel: <?php echo json_encode(__('Snapshot usage (%)')) ?>,
                            });

    // field set
    var allFields = new Ext.form.FieldSet({
        autoHeight:true,
        border:false,defaults:{msgTarget: 'side'},
        items: [this.lv_snapshots_size,this.vg_free_size, this.lv_size, this.lv_new_size,this.lvsnapshotusage
             ,{
                xtype:'box'
                ,id: 'lv-warning-message'
                ,autoEl:{
                    tag:'div', children:[
                        {
                            tag:'div'
                            ,style:'float:left;width:31px;height:32px;'
                            ,cls:'icon-warning'
                        },
                        {
                            tag:'div'
                            ,style:'margin-left:35px'
                            ,html: <?php echo json_encode(__('When DECREASING size you must BE CAREFULL because you may LOOSE ALL DATA!')) ?>
                    }]
                }
              }
        ]
    });




    lvwin.resizeForm.Main.superclass.constructor.call(this, {        
        baseCls: 'x-plain',
        defaultType: 'textfield',
        monitorValid:true,
        buttonAlign:'center',
        labelWidth:210,
        items: [allFields],
        buttons: [{
                text: __('Save'),
                scope:this,
                formBind:true,
                handler: this.resizeLv
            },// end Save
            {text: __('Cancel'),
                scope:this,
                handler:function(){this.ownerCt.close();}}]                
    });// end superclass constructor


};// end resizeForm


// public methods
Ext.extend(lvwin.resizeForm.Main, Ext.form.FormPanel, {

    // load data
    load : function(node) {
        var total_vg_free_size = parseFloat(node.attributes.vgfreesize) + parseFloat(node.attributes.size);

        var vg_free_size = byte_to_MBconvert(total_vg_free_size,2,'floor');

        this.vg_free_size.setValue(vg_free_size);

        this.lv = node.attributes.text;
        var lv_size = byte_to_MBconvert(node.attributes.size,2,'floor');
        this.lv_size.setValue(lv_size);

        if( node.attributes.format != 'qcow2' ){
            this.lvsnapshotusage.setDisabled(true);
            var lv_warn_msg = Ext.getCmp('lv-warning-message').autoEl.children[1].html;
            Ext.getCmp('lv-warning-message').autoEl.children[1].html = lv_warn_msg + '<br/>' + <?php echo json_encode(__('Can\'t define snapshots usage space for volumes that doesn\'t support snapshots!')) ?>;
        } else {
            var perfreespace = Math.ceil((1 - node.attributes.per_usage) * 100);
            this.lvsnapshotusage.setValue(perfreespace);

            if( node.attributes.size_snapshots > 0 ){
                var size_snapshots = byte_to_MBconvert(node.attributes.size_snapshots,2,'floor');
                this.lv_snapshots_size.setValue(size_snapshots);

                var per_usage_snapshots = size_snapshots * 100 / lv_size;
                var persnapshotusage = Math.ceil(per_usage_snapshots);
                this.lvsnapshotusage.setMinValue(persnapshotusage);

                this.lvsnapshotusage.setDisabled(true);

                //Ext.getCmp('lv-warning-message').
                var lv_warn_msg = Ext.getCmp('lv-warning-message').autoEl.children[1].html;
                Ext.getCmp('lv-warning-message').autoEl.children[1].html = lv_warn_msg + '<br/>' + <?php echo json_encode(__('Can\'t change snapshots usage space if volume have snapshots!')) ?>;
                //console.log(Ext.getCmp('lv-warning-message').autoEl.children[1]);
            }
        }

        //console.log(node);

    }
    ,resizeLv:function(){

        if (this.form.isValid()) {
            var old_size = this.lv_size.getValue();
            var size = this.lv_new_size.getValue();
            var persnapshotusage = this.lvsnapshotusage.getValue();

            if(size<old_size)
                Ext.MessageBox.show({
                    title: <?php echo json_encode(__('Resize logical volume')) ?>,
                    msg: String.format('{0}<br>{1}<br>{2}'
                            ,<?php echo json_encode(__('You are about to decrease size.')) ?>
                            ,<?php echo json_encode(__('When DECREASING size you must BE CAREFULL because you may LOOSE ALL DATA!')) ?>
                            ,<?php echo json_encode(__('Are you sure you want to do this?')) ?>),
                    buttons: Ext.MessageBox.YESNO,
                    fn: function(btn){

                        if(btn=='yes') this.sendRequest(size,persnapshotusage);

                    },
                    scope:this,
                    icon: Ext.MessageBox.WARNING
                });

            else this.sendRequest(size,persnapshotusage);


        }// not valid
        else Ext.MessageBox.alert(<?php echo json_encode(__('Error!')) ?>, <?php echo json_encode(__('Please fix the errors noted!')) ?>);

    }
    /*
     * send soap request
     * on success store returned object in DB (lvStoreDB)
     */
    ,sendRequest:function(size,per){

        // create parameters array to pass to soap request....
        var params;

        if(this.level == 'cluster'){
            params = {
                        'cid':this.node_id,
                        'level':this.level,
                        'lv':this.lv,
                        'size': size + 'M',
                        'persnapshotusage': per
                    }
        }else if(this.level == 'node'){
            params = {
                        'nid':this.node_id,
                        'level':this.level,
                        'lv':this.lv,
                        'size': size + 'M',
                        'persnapshotusage': per
                    }
        }else{
            params = {
                        'nid':this.node_id,
                        'lv':this.lv,
                        'size': size + 'M',
                        'persnapshotusage': per
                    }
        }

        var conn = new Ext.data.Connection({
            listeners:{
                // wait message.....
                beforerequest:function(){
                    Ext.MessageBox.show({
                        title: <?php echo json_encode(__('Please wait...')) ?>,
                        msg: <?php echo json_encode(__('Resizing logical volume...')) ?>,
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
            url: <?php echo json_encode(url_for('logicalvol/jsonResize'))?>,
            params: params, 
            scope:this,
            success: function(resp,opt){
                var response = Ext.util.JSON.decode(resp.responseText);
                Ext.ux.Logger.info(response['agent'], response['response']);
                this.fireEvent('updated');

            },
            failure: function(resp,opt) {
                if(resp.status==401) return;

                var response = Ext.util.JSON.decode(resp.responseText);

                Ext.ux.Logger.error(response['agent'],response['error']);

                Ext.Msg.show({
                    title: String.format(<?php echo json_encode(__('Error {0}')) ?>,response['agent']),
                    buttons: Ext.MessageBox.OK,
                    msg: String.format(<?php echo json_encode(__('Unable to resize logical volume {0}!')) ?>,this.lv)+'<br>'+response['info'],
                    icon: Ext.MessageBox.ERROR});
            }
        });// END Ajax request

    }

});


</script>
