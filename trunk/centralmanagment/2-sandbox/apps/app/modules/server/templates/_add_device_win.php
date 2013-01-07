<script>
Ext.ns('Server.Devices');

//==================== MAIN FORM ========================
Server.Devices.Form = function(obj){


    Ext.QuickTips.init();   	
    this.server_id = obj.server_id;    
//    this.totalvgsize = new Ext.form.Hidden({
//   //     id: 'total-vg-size',
//        name:'total-vg-size'
//    });

//    this.lvsize = new Ext.form.NumberField({});

    var baseParams = {'sid':this.server_id};

    this.devs = new Ext.form.ComboBox({
        id: 'devs-combo'
//      ,devtype: 'pci'
        ,disabled:true
        ,valueField:'description'
        ,displayField:'description'    // could be undefined as we use custom template
        ,triggerAction:'all'    // query all records on trigger click
        ,minChars:2             // minimum characters to start the search
        ,forceSelection:true    // do not allow arbitrary values
        ,enableKeyEvents:true   // otherwise we will not receive key events 
        ,resizable:true         // make the drop down list resizable        
        ,editable: false
        ,typeAhead: false
//        ,minListWidth:250       // we need wider list for paging toolbar
        ,allowBlank:false       // force user to fill something
        ,autoLoad: true        
        ,resizable:true
        ,minChars:1
        ,minListWidth:500
        ,mode: 'local'

        // store getting items from server
        ,store:new Ext.data.JsonStore({
            root:'data'
            ,totalProperty:'total'
            ,fields:[
                {name:'id'}
                ,{name:'directory', type:'string'}
                ,{name:'idproduct', mapping:'idproduct', type:'string'}
                ,{name:'idvendor', mapping:'idvendor', type:'string'}
                ,{name:'type', mapping:'type', type:'string'}
                ,{name:'description', mapping:'tostring', type:'string'}
                ,{name:'bus', mapping:'bus', type:'string'}
                ,{name:'slot', mapping:'slot', type:'string'}
                ,{name:'function', mapping:'function', type:'string'}
            ]
            ,url: <?php echo json_encode(url_for('node/jsonListFreeDevices'))?>
            ,baseParams:baseParams
            ,listeners:{
                'loadexception':function(store,options,resp,error){
                    var response = Ext.util.JSON.decode(resp.responseText);

                    Ext.Msg.show({title: 'Error',
                        buttons: Ext.MessageBox.OK,
                        msg: response['error'],
                        icon: Ext.MessageBox.ERROR});
                }
                ,'load': function(store, records, options){
                    var a = Ext.getCmp('devs-combo');
                    
                    if(records.length == 0){
                        Ext.Msg.show({
                            title: <?php echo json_encode(__('Information')) ?>,
                            scope:this,
                            buttons: Ext.MessageBox.OK,
                            msg: <?php echo json_encode(__('There are no devices available.')) ?>,
                            icon: Ext.MessageBox.INFO,
                            fn: function(btn){
                                if (btn == 'ok'){
                                }
                            }
                        });
                        
                    }

                    a.reset();
                    a.enable();

                }

            }
        })
        // concatenate vgname and size (MB)
//        ,tpl:'<tpl for="."><div class="x-combo-list-item">{name} ({[byte_to_MBconvert(values.size,2,"floor")]} MB)</div></tpl>'
        ,changeDevType: function(devtype){
            this.disable();
            this.store.setBaseParam('type', devtype);
            this.store.load({'type': devtype});
        }
        // listeners
        ,listeners:{
//            // sets raw value to concatenated last and first names
//             select:{scope:this,fn:function(combo, record, index) {
//                var size = byte_to_MBconvert(record.get('size'),2,'floor');
//                combo.setRawValue(record.get('name') + ' (' + size+' MB)');
//                //Ext.getCmp('total-vg-size').setValue(size);
//                this.totalvgsize.setValue(size);
//                //Ext.getCmp('form-lvsize').setValue(size);
//                this.lvsize.setValue(size);
//                
//            }}
//            // repair raw value after blur
//            ,blur:function() {                
//                var val = this.getRawValue();
//                this.setRawValue.defer(1, this, [val]);
//            }
//
//            // set tooltip and validate
//            ,render:function() {
//                this.el.set(
//                    //{qtip:'Type at least ' + this.minChars + ' characters to search in volume group'}
//                    {qtip: <?php echo json_encode(__('Choose volume group')) ?>}
//                );
//                //this.validate();
//            }
//            // requery if field is cleared by typing
            keypress:{buffer:100, fn:function() {
//                if(!this.getRawValue()) {
//                    this.doQuery('', true);
//                }
            
                //TODO carregar o dev type
//                alert("key pressed");
            }}
//        
        }
        // label
        ,fieldLabel: <?php echo json_encode(__('Devices')) ?>
        ,anchor:'90%'
    });// end this.dv
    
    this.dvtypes = new Ext.form.ComboBox({
        hiddenName: 'devtype',    //This is the name of a hidden HTML input field which is used to
        //store and submit the separate value of the combo if the descriptive text is not
        //what is to be submitted
        fieldLabel: <?php echo json_encode(__('Device Type')) ?>,
        mode: 'local'
        ,lastQuery:''
        ,allowBlank:false
        ,triggerAction: 'all'
        ,editable: false
        ,typeAhead: false
//        ,readOnly: true
        ,store: new Ext.data.ArrayStore({
            fields: ['type', 'type_name']
            ,data : [
                ['pci','PCI']
                ,['usb','USB']
            ]
        })
        ,displayField:'type_name',
        valueField:'type',
        width: 120
    });
    
    this.dvtypes.on({
        'select':function(combo, record, index){
            console.log(combo.getValue());
            devs = Ext.getCmp('devs-combo');
//            devs.devtype = combo.getValue();
//            var store = devs.getStore();
//            store.setBaseParam('devtype', 'pooop');
//            store.setBaseParam('devtype', combo.getValue());
//            store.reload();
            devs.changeDevType(combo.getValue());
        }
    });

    // field set
    var allFields = new Ext.form.FieldSet({
        autoHeight:true,
        border:false,
        labelWidth:60,defaults:{msgTarget: 'side'},
        items: [this.dvtypes, this.devs]
    });

    // define window and pop-up - render formPanel
    Server.Devices.Form.superclass.constructor.call(this, {        
        bodyStyle: 'padding-top:10px;',monitorValid:true,
        items: [allFields],
        buttons: [{
            text: __('Add'),
            formBind:true,
            handler: this.addDevice,
//            handler: this.addDevice_now,            
            scope: this
        }
        ,{
            text: __('Close'),
            scope:this,
            handler:function(){this.ownerCt.close();}
        }]// end buttons
        ,listeners:{
                render:{delay:100,fn:function(){
                        //this.dvalias.focus.defer(500, this.dvalias);                                                                  
                }}
            }

    });// end superclass constructor    

};// end Server.Devices.Form function

// define public methods
Ext.extend(Server.Devices.Form, Ext.form.FormPanel, {

    addDevice:function(){
        var store = this.ownerCt.devs_tab.get(0).getStore();

        //AKII
        var v = this.devs.getValue();
        var record = this.devs.findRecord(this.devs.valueField || this.devs.displayField, v);
        console.log(record);

        console.log(record.get('id'));
        var res = store.getById(record.get('id'));
        if(res){
            // I already have this device
        }else{
            store.add([record]);
        }
        
        
    },
    
    /*
    * send soap request
    * on success store returned object in DB (lvStoreDB)
    */       
    addDevice_now:function(){

        // if necessary fields valid...
        if(this.getForm().isValid()){
//            var dvalias = this.dvalias.getValue();                        
            var v = this.devs.getValue();
            var record = this.devs.findRecord(this.devs.valueField || this.devs.displayField, v);

            // create parameters array to pass to http request....
            var params = {
                'sid'       : this.server_id,
                'type'   : record.get('type'),
                'idproduct' : record.get('idproduct'),
                'idvendor'  : record.get('idvendor'),
                'description': record.get('description'),
                'bus'       : record.get('bus'),
                'slot'      : record.get('slot'),
                'function'  : record.get('function')
            };

            var conn = new Ext.data.Connection({
                listeners:{
                    // wait message.....
                    beforerequest:function(){

                        Ext.MessageBox.show({
                            title: <?php echo json_encode(__('Please wait...')) ?>,
                            msg: <?php echo json_encode(__('Adding device...')) ?>,
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
                url: <?php echo json_encode(url_for('server/jsonAddDevice'))?>,
                params: params,
                scope:this,
                success: function(resp,opt){
                    var response = Ext.util.JSON.decode(resp.responseText);
                    Ext.ux.Logger.info(response['agent'],response['info']);                    
                    this.fireEvent('updated');                                                
                    this.ownerCt.close();
                }
                ,failure: function(resp,opt) {
                    var response = Ext.util.JSON.decode(resp.responseText);
                    if(!resp.responseText){
                        Ext.ux.Logger.error(resp.statusText);
                        return;
                    }

                    var response = Ext.util.JSON.decode(resp.responseText);
                    Ext.MessageBox.alert(<?php echo json_encode(__('Error Message'))?>, response['info']);
                    Ext.ux.Logger.error(response['info']);
                                        
                }
            });// END Ajax request


        }//end isValid

    }
});


//==================== DEFINIÇAO WINDOW =======================
Server.Devices.Editor = Ext.extend(Ext.Window,{
    title: <?php echo json_encode(__('Add device')) ?>,
    width: 400,
    height: 170,
//    closeAction: 'hide',
    layout: 'fit',
    modal:true,

////     id: 'vg-create-win',
//     width:550,
//     height:350,
//     iconCls: 'icon-window',
//     bodyStyle: 'padding:10px;',
//     shim:false,
//     border:true,
//     constrainHeader:true,
//     layout: 'fit'
////     ,items: [centerPanel]
//     ,tools: [{
//         id:'help',
//         qtip: __('Help'),
//         handler:function(){
//             View.showHelp({
//                 anchorid:'help-vol-group-add',
//                 autoLoad:{ params:'mod=volgroup'},
//                 title: <?php echo json_encode(__('Physical Volume Help')) ?>
//             });
//         }
//     }]


    initComponent:function(){
        var form = new Server.Devices.Form({
            server_id: this.server_id 
//                            domain  :this.domain,
//                            maxQuota:this.maxQuota,
//                            mailbox :this.mailbox,
//                            service_id:this.service_id,
//                            parent_grid:this.parent_grid,
//                            changeFreeMb:this.changeFreeMb,
//                            end:this.end
        });

        this.items = [form];
        Server.Devices.Editor.superclass.initComponent.call(this);
    }
    ,end:function(){
        this.hide();
        this.ownerCt.hide();
    }
    
})

//Server.Devices.Editor = Ext.extend(Ext.Window,{
////    title: <?php echo json_encode(__('Manage Mailbox')) ?>,
//    width: 600,
//    height: 400,
//    closeAction: 'hide',
//    layout: 'fit',
//    initComponent:function(){
//        alert("EDITOR");
//        var form = new Server.Devices.Edit.Form({
//                            domain  :this.domain,
//                            maxQuota:this.maxQuota,
//                            mailbox :this.mailbox,
//                            service_id:this.service_id,
//                            parent_grid:this.parent_grid,
//                            changeFreeMb:this.changeFreeMb,
//                            end:this.end
//                        });
//        this.items = [form];
//        Server.Devices.Editor.superclass.initComponent.call(this);
//    }
//    ,end:function(){
//        this.hide();
//        this.ownerCt.hide();
//    }
//})

</script>
