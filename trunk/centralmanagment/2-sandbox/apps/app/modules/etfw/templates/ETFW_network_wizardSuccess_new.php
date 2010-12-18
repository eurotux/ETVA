<?php include_partial("script/CardLayout.js"); ?>
<?php include_partial("script/Wizard.js"); ?>
<?php include_partial("script/Header.js"); ?>
<?php // include_partial("script/West.js"); ?>
<?php include_partial("script/Card.js"); ?>
<script>
 ETFW_network_wizard = function() {


    <?php $node_id = $etva_node->getId();?>

    var node_id = <?php echo $node_id ?>;
    var node_memtotal = byte_to_MBconvert(<?php echo($etva_node->getMemtotal()); ?>,2);


    var node_cputotal = <?php echo($etva_node->getCputotal()); ?>;
    var cpuset = [];

    for(var i=0;i<node_cputotal;i++){
        cpuset.push([i,i+1]);
    }


    function moveSelectedRow(grid,direction) {
        var record = grid.getSelectionModel().getSelected();

        if (!record) {
            return;
        }
        var index = grid.getStore().indexOf(record);
        if (direction < 0) {
            index--;
            if (index < 0) {
                return;
            }
        } else {
            index++;
            if (index >= grid.getStore().getCount()) {
                return;
            }
        }
        grid.getStore().remove(record);
        grid.getStore().insert(index, record);
        grid.getView().refresh();
        grid.getSelectionModel().selectRow(index, true);
    }


    // Add the additional 'advanced' VTypes
    // checks memory cannot exceed total memory size
    Ext.apply(Ext.form.VTypes, {

        vm_memory_size : function(val, field) {
            if (field.totalmemsize) {
                var tsize = Ext.getCmp(field.totalmemsize);
                return (val <= parseFloat(tsize.getValue()));
            }

            return true;
        },
        vm_memory_sizeText : 'Cannot exceed total allocatable memory size',
        vm_lv_newsize : function(val, field) {
            if (field.totallvsize) {
                var tsize = Ext.getCmp(field.totallvsize);
                return (val <= parseFloat(tsize.getValue()));
            }

            return true;
        },
        vm_lv_newsizeText : 'Cannot exceed total allocatable memory size'
    });

    Ext.override(Ext.Editor, {
        beforeDestroy : function(){
            Ext.destroy(this.field);
            this.field = null;
        }
    });


    Ext.onReady(function(){


        Ext.QuickTips.init();
        Ext.form.Field.prototype.msgTarget = 'qtip';

        var vgcombo = new Ext.form.ComboBox({
            // we need id to focus this field. See window::defaultButton
            readOnly:true
            // we want to submit id, not text
            ,valueField:'name'
            ,hiddenName:'vm_vg'

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
            // ,pageSize:5

            // make the drop down list resizable
            ,resizable:true

            // we need wider list for paging toolbar
            ,minListWidth:250

            // force user to fill something
            ,allowBlank:false

            // store getting items from server
            ,store:new Ext.data.JsonStore({
                id:'name'
                ,root:'data'
                ,totalProperty:'total'
                ,fields:[
                    {name:'name', type:'string'}
                    ,{name:'size', mapping:'value', type:'string'}
                ]
                ,url:<?php echo json_encode(url_for('volgroup/jsonListFree?nid='.$node_id))?>
            })
            // concatenate vgname and size (MB)
            ,tpl:'<tpl for="."><div class="x-combo-list-item">{name} - Size {[byte_to_MBconvert(values.size,2)]}</div></tpl>'

            // listeners
            ,listeners:{
                // sets raw value to concatenated last and first names
                select:function(combo, record, index) {
                    var size = byte_to_MBconvert(record.get('size'),2);
                    this.setRawValue(record.get('name') + ' - Size ' + size);
                    Ext.getCmp('vm_total_vg_size').setValue(size);
                    Ext.getCmp('vm_lv_newsize').setValue(size);

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

        });// end vgcombo

        var lvcombo = new Ext.form.ComboBox({
            // we need id to focus this field. See window::defaultButton
            readOnly:true
            // we want to submit id, not text
            ,valueField:'lv'
            ,hiddenName:'vm_lv'

            // could be undefined as we use custom template
            ,displayField:'lv'

            // query all records on trigger click
            ,triggerAction:'all'

            // minimum characters to start the search
            ,minChars:2

            // do not allow arbitrary values
            ,forceSelection:true

            // otherwise we will not receive key events
            ,enableKeyEvents:true

            // let's use paging combo
            // ,pageSize:5


            // make the drop down list resizable
            ,resizable:true

            // we need wider list for paging toolbar
            ,minListWidth:250
            //,maxHeight:150

            // force user to fill something
            ,allowBlank:false

            // store getting items from server
            ,store:new Ext.data.JsonStore({
                id:'id'
                ,root:'data'
                ,totalProperty:'total'
                ,fields:[
                    {name:'id', type:'string'}
                    ,{name:'lv', type:'string'}
                    ,{name:'size', type:'string'}
                ]
                ,url:<?php echo json_encode(url_for('logicalvol/jsonGetAvailable'))?>
                ,baseParams:{'nid':node_id}

                //,url:<?php // echo json_encode('node/soap?method=getlvs&opt=l&q=writeable')?>+'&id='+node_id

            })
            // concatenate vgname and size (MB)
            ,tpl:'<tpl for="."><div class="x-combo-list-item">{lv} - Size {[byte_to_MBconvert(values.size,2)]} MB</div></tpl>'

            // listeners
            ,listeners:{
                // sets raw value to concatenated last and first names
                select:function(combo, record, index) {
                    var size = byte_to_MBconvert(record.get('size'),2);
                    this.setRawValue(record.get('lv') + ' - Size ' + size + ' MB');
                    Ext.getCmp('vm_lvsize').setValue(size);

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
                    {qtip:'Choose logical volume'}
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
            ,fieldLabel:'Device'

        });// end lvcombo


        var mac_vlan_record = Ext.data.Record.create([
            {name: 'id', type: 'int'},
            {name: 'mac', type: 'string'},
            {name: 'vlan'}
        ]);



        var fromVlans = new Ext.data.JsonStore({
            // id:'Id'
            root:'data'
            ,totalProperty:'total'
            ,fields:[
                {name:'Id', type:'string'}
                ,{name:'Name', type:'string'}
            ]
            ,url:<?php echo json_encode(url_for('vlan/jsonList?nid='.$node_id))?>});


        var network_status = new Ext.form.TextField({
                            name:'network_status',
                            cls: 'nopad-border',
                            readOnly:true,

                            width:200,
                            labelSeparator: '',

                           // labelWidth:50,
                           value : 'Fill grid with data...',
                           invalidText : 'Fill grid with data...',
                            allowBlank : false,
                            validator  : function(v){
                                //var t = /Tou^[a-zA-Z_\-]+$/;
                                return v!='Fill grid with data...';
                               // return t.test(v);
                            }
                        });

        var mac_vlan_cm = new Ext.grid.ColumnModel([
            new Ext.grid.RowNumberer(),
            {
                id:'mac',
                header: "MAC Address",
                dataIndex: 'mac',
                fixed:true,
                allowBlank: false,
                width: 120
                ,renderer: function(val){return '<span ext:qtip="Drag and Drop to reorder">' + val + '</span>';}
            },
            {
                header: "VLAN",
                dataIndex: 'vlan',
                width: 130,
                renderer:function(value,meta,rec){
                    if(!value){
                        return '<b>Select vlan...</b>';}
                    else{ rec.commit(true); return value;}
                },
                editor: new Ext.form.ComboBox({
                    typeAhead: true,
                    editable:false,
                    triggerAction: 'all',
                    store:fromVlans,
                    displayField:'Name',
                    lazyRender:true,
                    listClass: 'x-combo-list-small',
                    listeners: {
                        // focus:function() {
                        //     this.store.load();
                        // },
                        select:function(combo,record,index){

                            var record_ = mac_vlan_grid.getSelectionModel().getSelected();
                            record_.set('vlan', this.getValue());

                        }
                    }// end listeners
                })
            }
        ]);// end mac_vlan columnmodel



        var mac_vlan_grid = new Ext.grid.EditorGridPanel({
            store: new Ext.data.SimpleStore({
                fields: [
                    {name: 'mac'},
                    {name: 'vlan'}
                ]
            }),
            autoScroll: true,
            ddGroup: 'testDDGroup',
            enableDragDrop: true,
            labelSeparator: '',
            isFormField:true,
            cm: mac_vlan_cm,
            width:400,
            height:200,
            autoExpandColumn:'mac',
            viewConfig:{
                forceFit:true,
                emptyText: 'Empty!',  //  emptyText Message
                deferEmptyText:false
            },
            clicksToEdit:2,
            forceValidation:true,
            sm: new Ext.grid.RowSelectionModel({
                singleSelect: true,
                moveEditorOnEnter:false
            }),
            tbar: [{
                    text: 'Add',
                    iconCls:'add',
                    handler : function(){
                        var conn = new Ext.data.Connection();
                        conn.request({
                            url: 'mac/jsonGetUnused',
                            scope: this,
                            success: function(resp,options) {
                                var response = Ext.util.JSON.decode(resp.responseText);
                                var new_mac = response['Mac'];

                                var new_record = new mac_vlan_record({
                                    mac: new_mac,
                                    vlan: ''});

                                network_status.setValue("Fill grid with data...");

                                mac_vlan_grid.getStore().insert(0, new_record);
                                mac_vlan_grid.getView().refresh();
                                mac_vlan_grid.getSelectionModel().selectRow(0, true);
                            },
                            failure: function(resp,opt) {
                                var response = Ext.util.JSON.decode(resp.responseText);

                                Ext.ux.Logger.error(response.toString());

                                Ext.Msg.show({title: 'Error',
                                    buttons: Ext.MessageBox.OK,
                                    msg: response['error'],
                                    icon: Ext.MessageBox.ERROR});
                            }

                        });// end ajax request
                    }// end handler
                },// end button
                {
                    text: 'Remove',
                    iconCls:'remove',
                    handler : function(){
                        var record = mac_vlan_grid.getSelectionModel().getSelected();

                        if (!record) {return;}
                        mac_vlan_grid.getStore().remove(record);
                         var rows = mac_vlan_grid.getStore().getCount();
                         if(rows==0) network_status.setValue("Fill grid with data...");
                         else mac_vlan_grid.fireEvent('afteredit');

                        mac_vlan_grid.getView().refresh();

                    }
                },'-',
                {
                    text: 'Move up',
                    handler : function(){moveSelectedRow(mac_vlan_grid,-1);}

                },
                {
                    text: 'Move down',
                    handler : function(){moveSelectedRow(mac_vlan_grid,1);}

                },'->',
                {text:'Add MAC pool',
                    url:'mac/createwin',
                    handler: View.clickHandler
                }
            ]
            ,listeners: {
                afteredit:function(){

                    var cols = this.colModel.getColumnCount();
                var rows = this.store.getCount();
                if(rows==0){
                    network_status.setValue("Fill grid with data...");
                    return false;
                }

                var r, c;
                var valid = true;
                for(r = 0; r < rows; r++) {
                    for(c = 1; c < cols; c++) {
                        valid = this.isCellValid(c, r);
                        if(!valid) {
                            break;
                        }
                    }
                    if(!valid) {
                        break;
                    }
                }
                if(!valid) network_status.setValue("Fill grid with data...");
                else network_status.setValue("You have "+rows+" networks inserted");
                return valid;

                },

                render: function(g) {
                    // Best to create the drop target after render, so we don't need to worry about whether grid.el is null

                    // constructor parameters:
                    //    grid (required): GridPanel or EditorGridPanel (with enableDragDrop set to true and optionally a value specified for ddGroup, which defaults to 'GridDD')
                    //    config (optional): config object
                    // valid config params:
                    //    anything accepted by DropTarget
                    //    listeners: listeners object. There are 4 valid listeners, all listed in the example below
                    //    copy: boolean. Determines whether to move (false) or copy (true) the row(s) (defaults to false for move)
                    var ddrow = new Ext.ux.dd.GridReorderDropTarget(g, {
                        copy: false
                        ,listeners: {
                            beforerowmove: function(objThis, oldIndex, newIndex, records) {
                                // code goes here
                                // return false to cancel the move
                            }
                            ,afterrowmove: function(objThis, oldIndex, newIndex, records) {
                                g.getView().refresh();
                                // code goes here
                            }
                            ,beforerowcopy: function(objThis, oldIndex, newIndex, records) {
                                // code goes here
                                // return false to cancel the copy
                            }
                            ,afterrowcopy: function(objThis, oldIndex, newIndex, records) {
                                // code goes here
                            }
                        }
                    });

                    // if you need scrolling, register the grid view's scroller with the scroll manager
                    Ext.dd.ScrollManager.register(g.getView().getEditorParent());
                }
                ,beforedestroy: function(g) {
                    // if you previously registered with the scroll manager, unregister it (if you don't it will lead to problems in IE)
                    Ext.dd.ScrollManager.unregister(g.getView().getEditorParent());
                }
            }// end listeners

        });// end mac_vlan_grid

        Ext.apply(mac_vlan_grid, {

            isCellValid:function(col, row) {

                var record = this.store.getAt(row);
                if(!record) {
                    return true;
                }

                var field = this.colModel.getDataIndex(col);
                if(!record.data[field]) return false;
                return true;
            }

        });


        var viewerSize = Ext.getBody().getViewSize();
        var windowHeight = viewerSize.height * 0.95;
        windowHeight = Ext.util.Format.round(windowHeight,0);

        var wizard = new Ext.ux.Wiz({
            border:true,
            title : 'Network Setup Wizard',

            headerConfig : {
                title : 'Create new network configuration'
            },
            width:600,
            height:windowHeight,

            westConfig : {
                width : 150
            },

            cardPanelConfig : {
                defaults : {
                    baseCls    : 'x-small-editor',
                    bodyStyle  : 'border:none;padding:15px 15px 15px 15px;background-color:#F6F6F6;',
                    border     : false
                }
            },

            cards : [
                // card with welcome message
                new Ext.ux.Wiz.Card({
                    title : 'Welcome',
                    defaults     : {
                        labelStyle : 'font-size:11px;width:140px;'
                    },
                    items : [{
                            border    : false,
                            bodyStyle : 'background:none;',
                            html      : 'Welcome to the network setup wizard.<br><br>'+
                                'All the actual configuration will be lost. It should be applyed on first time setup.'+
                                '<br>Follow the steps to setup initial network<br/><br/>'

                        }]
                }),
                // second card ID 2
                // memory related
                new Ext.ux.Wiz.Card({
                    id:'1',
                    title        : 'Memoryyyy',
                    monitorValid : true,
                    
                    defaults     : {
                        labelStyle : 'font-size:11px;width:150px;'
                    },
                    items : [{
                            border    : false,
                            bodyStyle : 'background:none;padding-bottom:30px;',
                            html      : 'Specify the amount of memory to allocate to this machine.'+
                                'It should be a multiple of 4 MB.'
                        },
                        new Ext.form.TextField({
                            name       : 'vm_maxmemory',
                            id         : 'vm_maxmemory',
                            fieldLabel : 'Max allocatable memory (MB)',
                            value : node_memtotal,
                            allowBlank : false,
                            readOnly:true
                        }),
                        new Ext.form.TextField({
                            name       : 'vm_memory',
                            fieldLabel : 'Memory size (MB)',
                            allowBlank : false,
                            vtype: 'vm_memory_size',
                            totalmemsize: 'vm_maxmemory'

                        })



                    ]
                }),

                // first card ID 1
                // virtual server name
                new Ext.ux.Wiz.Card({
                    id: '2',
                    title        : 'Network topology',
                    monitorValid : true,
                    autoScroll:true,
                    defaults     : {
                        labelStyle : 'font-size:11px;width:140px;'
                    },

                    items : [{
                            border    : false,
                            bodyStyle : 'background:none;',
                            html      : 'Please choose network topology.'
                        },
                        new Ext.form.Hidden({
                            name:'node_id',
                            value:node_id})
                        ,                        
                        new Ext.form.FieldSet({
                            cls:'fieldset-top-sp',
                            title: 'Topology',
                            collapsible: false,
                            autoHeight:true,
                            defaultType: 'radio',
                            labelWidth:10,
                            //layout:'fit',
                            //autoScroll:true,
                            items :[
                                {
                                    checked:true,
                                    fieldLabel: '',
                                    labelSeparator: '',
                                    boxLabel: 'ETFW',
                                    name: 'vm_OS',
                                    inputValue: 'linux'


                                }
                                ,{
isFormField:false ,fieldLabel:'',labelWidth:10
                                    ,xtype:'box'
                                    ,autoEl:{
 tag:'div', children:[{
 tag:'img'
 ,qtip:'You can also have a tooltip on the image'
 ,src:'images/network/etfw_only_tp.png'
 //,width:200
 ,height:100
 ,style:'margin:0px 0px 10px 10px'
 //,src:'http://extjs.com/deploy/dev/examples/shared/screens/desktop.gif'
 }]
 }
                                }
                                ,{
                                    fieldLabel: '',
                                    labelSeparator: '',
                                    boxLabel: 'ETFW+DMZ',
                                    name: 'vm_OS',
                                    inputValue: 'windows',
                                    listeners:{
                                        'check':function(){
                                            alert('chk');
                                            (wizard.cards[6]).setSkip(true);
                                        }
                                    }
                                }
                                ,{
isFormField:false ,fieldLabel:'',labelWidth:10
                                    ,xtype:'box'
                                    ,autoEl:{
 tag:'div', children:[{
 tag:'img'
 ,qtip:'You can also have a tooltip on the image'
 ,src:'images/network/etfw_only_tp.png'
 ,width:300
 ,height:100
 ,style:'margin:0px 0px 10px 10px'
 //,src:'http://extjs.com/deploy/dev/examples/shared/screens/desktop.gif'
 }]
 }
                                }
                            ]
                        })

                    ],
                    listeners: {

                    onNextClick:function(){alert('click');},
              beforecardhide: {
                scope: this,
                fn: function(card) {
                  var wizData = wizard.getWizardData()
                  alert(wizard.lastAction);
                  alert('d');
                //  (wizard.cards[6]).setSkip(true);
              //    Ext.getCmp('6').setSkip(true);
                  var cardData = wizData[card.getId()];
                  console.log('query wiz. card #2: ',wizard.getWizardData(), card, cardData );
//                  Ext.Ajax.request({
//                    disableCaching: true,
//                    method: 'POST',
//                    url: 'pageConfig.queryRunUrl',
//                    params: {
//                      ajax: 1,
//                      cli_id: 'pageConfig.cli_id',
//                      query_name: cardData['query_name'],
//                      rm: 'validate_name',
//                      sid: 'pageConfig.ses_id'
//                    },
//                    success: function( result, request ) {
//                      var json = doJSON( result.responseText );
//                      if( json.success ) {
//                        return true;
//                      }
//                      else {
//                        Ext.MessageBox.alert('Error!','Query with identical name already exists. Use different name.');
//                        return false;
//                      }
//                    },
//                    failure: function( result, request ) {
//                      Ext.MessageBox.alert('Error!',"Could not complete AJAX request. " +
//                                                    result.responseText );
//                      return false;
//                    }
//                  });
                }
              },scope:this
            }

                }),
                
                // third card ID 3
                // processor
                new Ext.ux.Wiz.Card({
                    id:'3',
                    title        : 'Processor',
                    monitorValid : true,

                    defaults     : {
                        labelStyle : 'font-size:11px;'
                        ,
                        bodyStyle: 'padding:10px;'
                    },
                    //    width:300,

                    items : [{
                            border    : false,
                            bodyStyle : 'background:none;padding-bottom:30px;',
                            html      : 'Specify the amount of processors to use with this machine.'
                        },
                        new Ext.form.TextField({
                            name       : 'vm_realcpu',
                            id         : 'vm_realcpu',
                            fieldLabel : 'Total usable CPU',
                            value : node_cputotal,
                            width:50,
                            allowBlank : false,
                            readOnly:true
                        }),
                        {
                            xtype:"multiselect",
                            //     cls:'vm-win-multiselect',
                            fieldLabel:"CPU to use",
                            name:"vm_cpu",
                            dataFields:["code", "desc"],
                            valueField:"code",
                            displayField:"desc",
                            width:50,
                            height:100,
                            allowBlank:false,
                            data:cpuset
                        }]
                }),

                // fourth card ID 4
                // storage info
                new Ext.ux.Wiz.Card({
                    id:'4',
                    title        : 'Storage',
                    monitorValid : true,
                    defaults     : {
                        labelStyle : 'font-size:11px;width:130px;'
                    },

                    items : [{
                            border    : false,
                            bodyStyle : 'background:none;padding-bottom:30px;',
                            html      : 'Choose storage.'
                        },
                        new Ext.form.FieldSet({
                            defaults     : {
                                labelStyle : 'font-size:11px;width:130px;'
                            },
                            id:'vm_device_set_exist',
                            checkboxToggle:true,
                            title: 'Existing device',
                            autoHeight:true,
                            // defaults: {width: 210},
                            defaultType: 'textfield',
                            collapsed: false,
                            items :[
                                lvcombo,
                                {
                                    id: 'vm_lvsize',
                                    fieldLabel: 'Device size (MB)',
                                    name: 'vm_lvsize',
                                    maxLength: 50,
                                    allowBlank: false
                                }],
                            listeners:{
                                beforecollapse:function(){
                                    lvcombo.setDisabled(true);
                                    Ext.getCmp('vm_lvsize').setDisabled(true);

                                },
                                collapse:function(){
                                    Ext.getCmp('vm_device_set_new').expand();
                                },
                                beforeexpand:function(){
                                    lvcombo.setDisabled(false);
                                    Ext.getCmp('vm_lvsize').setDisabled(false);
                                },
                                expand:function(){

                                    Ext.getCmp('vm_device_set_new').collapse();

                                }
                            }
                        }),
                        new Ext.form.FieldSet({
                            id:'vm_device_set_new',
                            checkboxToggle:true,
                            title: 'New device',
                            autoHeight:true,
                            defaults     : {
                                labelStyle : 'font-size:11px;width:130px;'
                            },
                            defaultType: 'textfield',
                            collapsed: true,
                            items :[ new Ext.form.Hidden({
                                    id: 'vm_total_vg_size',
                                    name:'vm_total_vg_size'
                                }),
                                {
                                    id:'vm_lv_new',
                                    fieldLabel: 'Logical volume name',
                                    allowBlank: false,
                                    name:'vm_lv_new'
                                },
                                vgcombo,
                                {
                                    id: 'vm_lv_newsize',
                                    fieldLabel: 'Logical volume size',
                                    name: 'vm_lv_newsize',
                                    maxLength: 50,
                                    vtype: 'vm_lv_newsize',
                                    allowBlank: false,
                                    totallvsize: 'vm_total_vg_size'
                                }
                            ],
                            listeners:{

                                beforecollapse:function(){
                                    Ext.getCmp('vm_lv_new').setDisabled(true);
                                    Ext.getCmp('vm_lv_newsize').setDisabled(true);
                                    Ext.getCmp('vm_total_vg_size').setDisabled(true);
                                    vgcombo.setDisabled(true);

                                },
                                collapse:function(){
                                    Ext.getCmp('vm_device_set_exist').expand();
                                },
                                beforeexpand:function(){

                                    Ext.getCmp('vm_lv_new').setDisabled(false);
                                    Ext.getCmp('vm_lv_newsize').setDisabled(false);
                                    Ext.getCmp('vm_total_vg_size').setDisabled(false);
                                    vgcombo.setDisabled(false);

                                },
                                expand:function(){

                                    Ext.getCmp('vm_device_set_exist').collapse();
                                }
                            }
                        })
                    ]// end card items
                }),
                // fifth card ID 5
                // network type
                new Ext.ux.Wiz.Card({
                    id:'5',
                    title        : 'Network Type',
                    monitorValid : true,
                    defaults     : {
                        labelStyle : 'font-size:11px;width:140px;'
                    },

                    items : [{
                            border    : false,
                            bodyStyle : 'background:none;padding-bottom:30px;',
                            html      : 'Specify the network type to use in virtual server.'
                        },
                        new Ext.form.FieldSet({
                            title: 'Network connection',
                            collapsible: false,
                            autoHeight:true,
                            defaultType: 'radio',
                            labelWidth:10,
                            items :[{
                                    checked:true,
                                    fieldLabel: '',
                                    labelSeparator: '',
                                    boxLabel: 'Use bridged networking',
                                    name: 'vm_nettype',
                                    inputValue: 'bridge'
                                },{
                                    fieldLabel: '',
                                    labelSeparator: '',
                                    boxLabel: 'Use network address',
                                    name: 'vm_nettype',
                                    inputValue: 'network'
                                },{
                                    fieldLabel: '',
                                    labelSeparator: '',
                                    boxLabel: 'Use host-only',
                                    name: 'vm_nettype',
                                    inputValue: 'user'
                                }
                            ]
                        })
                    ]
                }),

                // six card ID 6
                // host network
                new Ext.ux.Wiz.Card({
                    id:'6',
                    title        : 'Host network',
                    monitorValid : true,
                    defaults     : {
                        labelStyle : 'font-size:11px;width:90px;'
                    },
                    items : [{
                            border    : false,
                            bodyStyle : 'background:none;padding-bottom:30px;padding-left:0px;',
                            html      : 'Specify the NIC\'s to use.'
                        },
                        new Ext.form.FieldSet({
                            labelWidth: 1,
                          cls:'wizard-network-grid',
                            autoHeight:true,
                            items:[mac_vlan_grid]
                        })
                        ,new Ext.form.FieldSet({
                            labelWidth: 1,
                          cls:'wizard-network-grid',
                            autoHeight:true,
                            items:[network_status]
                        })

                    ]
                }),
                // seven card ID 7
                // installation source
                new Ext.ux.Wiz.Card({
                    id:'7',
                    title        : 'Installation source',
                    monitorValid : true,
                    defaults     : {
                        labelStyle : 'font-size:11px;width:0px;',
                        labelSeparator: ''
                    },
                    labelWidth:5,
                    defaultType: 'radio',

                    items : [{
                            xtype:'panel',
                            border    : false,
                            bodyStyle : 'background:none;padding-bottom:30px;',
                            html      : 'Specify the installation media source.'
                        },{
                            checked: true,
                            boxLabel: 'Local install path image (iso,...)',
                            name: 'vm_inst',
                            inputValue: 'local',
                            handler:function(box,check){
                                Ext.getCmp('vm_inst_local').setDisabled(!check);
                                Ext.getCmp('vm_inst_local').clearInvalid();}
                        },{
                            xtype: 'textfield',
                            name: 'vm_inst_local',
                            allowBlank:false,
                            anchor:'90%',
                            id: 'vm_inst_local',
                            emptyText : '/path/to/image',
                            labelStyle : 'font-size:11px;width:25px;'
                        },
                        {
                            boxLabel: 'Network install (http,ftp,...)',
                            name: 'vm_inst',
                            inputValue: 'remote',
                            handler:function(box,check){
                                Ext.getCmp('vm_inst_remote').setDisabled(!check);
                                Ext.getCmp('vm_inst_remote').clearInvalid();}
                        },{
                            xtype: 'textfield',
                            name: 'vm_inst_remote',
                            id: 'vm_inst_remote',
                            disabled:true,
                            anchor:'90%',
                            allowBlank:false,
                            emptyText : 'url://path/to/image',
                            labelStyle : 'font-size:11px;width:25px;'
                        }

                    ]
                }),

                // finish card with finish-message
                new Ext.ux.Wiz.Card({
                    id:'8',
                    title        : 'Finished!',
                    monitorValid : true,
                    items : [{
                            border    : false,
                            bodyStyle : 'background:none;',
                            html      : 'Thank you!. Your data has been collected.<br>'+
                                'When you click on the "finish" button, the virtual server will be created.<br />'
                        }]
                })

            ],
            listeners: {
                finish: function() { saveConfig( this.getWizardData() ) }
            }
        });



        function vmCreate(server) {

            var name = server[1]['vm_name'];
            var storage = server[4]['vm_lv'];
            var mem = server[2]['vm_memory'];
            var cpuset = server[3]['vm_cpu'];

            var nettype = server[5]['vm_nettype'];

            if(!server[7]['vm_inst_local'])
                var location = server[7]['vm_inst_remote'];
            else
                var location = server[7]['vm_inst_local'];


            var networks=[];

            var nets_store = mac_vlan_grid.getStore();


            var i = 0;
            nets_store.each(function(f){
                //  var field = grid.colModel.getDataIndex(col);
                //           ed.field.setValue(record.data[field]);
                var data = f.data;

                networks.push({
                    'port':i,
                    'vlan':data['vlan'],
                    'mac':data['mac']
                });
                i++;

            });



            var insert_model = {
                'lv':storage,
                'networks': networks,
                'nettype':nettype,
                'name':name,
                'ip':"000.000.000.000",
                'mem':mem,
                'cpuset':cpuset,
                'location':location};
            //   "etva_server[mac_addresses]":macs};

            var conn = new Ext.data.Connection({
                listeners:{
                    // wait message.....
                    beforerequest:function(){
                        Ext.MessageBox.show({
                            title: 'Please wait',
                            msg: 'Storing in db...',
                            width:300,
                            wait:true,
                            modal: false
                        });
                    },// on request complete hide message
                    requestcomplete:function(){Ext.MessageBox.hide();}}
            });// end conn

            conn.request({
                url: <?php echo json_encode(url_for('server/jsonCreate',false)); ?>,
                params: {'nid':node_id,'server': Ext.encode(insert_model)},
                title: name,
                scope: this,
                success: function(resp,opt) {
                    var response = Ext.util.JSON.decode(resp.responseText);
                    var sid = response['response']['insert_id'];
                    var tree_id = 's'+ sid;


                    Ext.ux.Logger.info(response['response']['msg']);

                    nodesPanel.addNode({id: tree_id,leaf:true,text: name,
                        url: <?php echo json_encode(url_for('server/view?id=',false)) ?>+sid});

                },
                failure: function(resp,opt) {
                    var response = Ext.util.JSON.decode(resp.responseText);

                    Ext.ux.Logger.error(response['error']);

                    Ext.Msg.show({title: 'Error',
                        buttons: Ext.MessageBox.OK,
                        msg: 'Unable to create virtual server ',
                        icon: Ext.MessageBox.ERROR});
                }
            }); // END Ajax request

        };


        function lvcreate(obj){
            var info = obj[4];
            var lvname = info['vm_lv_new'];
            var vgname = info['vm_vg'];
            var size = info['vm_lv_newsize'];


            // create parameters array to pass to soap request....
            var params = {
                'nid':node_id,
                'lv':lvname,
                'vg':vgname,
                'size':size};

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
                    vmCreate(obj);

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

        }




        // save form processing
        function saveConfig(obj) {


            // checks if needs to create lv
            // if lv is new then create lv and then create server
            if(obj[4]['vm_device_set_new-checkbox'] == 'on'){

                // setting lv name parameter for post processing vmCreate
                obj[4]['vm_lv'] = obj[4]['vm_lv_new'];;
                lvcreate(obj);
            }
            else // lv already exists just create server
                vmCreate(obj);

    }


    // show the wizard
    wizard.show();
    //.showInit();
});



   
}

new ETFW_network_wizard();
</script>