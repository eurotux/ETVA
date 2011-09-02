<?php
/*
 * Use Extjs helper to dynamic create data store and column model javascript
 */
// use_helper('Extjs');
$extraDSfields = array('vnc_port','SfGuardGroupName','all_shared_disks');

$extraCMAttrs = array(
                        'Boot'=> array('editor'=>"bootCmb",
                                'scope'=>'this'
                                ,'renderer'=>"function(value){

                                                    var storage = bootCmb.getStore();
                                                    var index = storage.find('value',value);
                                                    var name = '';
                                                    if(index!=-1) name = storage.getAt(index).data['name'];
                                                    return name;

                                }"
                        ),                        
                        'SfGuardGroupId'=> array('editor'=>"new Ext.form.ComboBox({
                                valueField: 'Id',displayField: 'Name',pageSize:5,
                                forceSelection: true,store: sfGuardGroup_ds,
                                mode: 'remote',lazyRender: true,triggerAction: 'all',
                                listClass: 'x-combo-list-small'})",
                                 'renderer'=>"rendersfGuardGroupName")
);


$js_grid = js_grid_info($server_tableMap,true,$extraDSfields,$extraCMAttrs);

$js_sfGuard = js_grid_info($sfGuardGroup_tableMap);





?>
<script>



Ext.namespace('Server');
Server.Grid = function(){

    return{        
        updateRecords:function(obj){
            var sm = obj.grid.getSelectionModel();
            var sel = sm.getSelected();
            var send_data = Ext.encode(obj.data);

            var serverGrid = obj.grid;
            var conn = new Ext.data.Connection({
                listeners:{
                    // wait message.....
                    beforerequest:function(){
                        Ext.MessageBox.show({
                            title: <?php echo json_encode(__('Please wait...')) ?>,
                            msg: <?php echo json_encode(__('Please wait...')) ?>,
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
                url: <?php echo json_encode(url_for('server/jsonSetBoot')) ?>,
                params: {
                    boot:obj.boot,
                    data:send_data,
                    id: sel.id
                },
                success: function(resp,opt) {
                    serverGrid.store.reload();
                },
                failure: function(resp,opt) {
                    Ext.Msg.alert(<?php echo json_encode(__('Error!')) ?>, <?php echo json_encode(__('Could not save changes!')) ?>);

                }
            });//END Ajax request


        },
        // var networkGrid;
        init:function(config){
            Ext.QuickTips.init();

            Ext.apply(this,config);

            /*
             * Partial networkGrid
             */




            // the column model has information about grid columns
            // dataIndex maps the column to the specific data field in
            // the data store (created below)
            // TODO: get fields dynamicaly


            /*
             * Data Source model. Used when creating new network object
             */
            //var ds_model = Ext.data.Record.create([
            //              'Id','Name']);

<?php
$store_id = json_encode($js_grid['pk']);
?>

            var grid_title = this.title;
            var gridUrl = this.url;
            var store_id = <?php echo $store_id ?>;
            var sort_field = store_id;
            var httpProxy = new Ext.data.HttpProxy({url: gridUrl});
            var selectItem_msg = <?php echo json_encode(__('Server from grid must be selected!')) ?>;
            var is_selectItem_msg = '';

            // create the Data Store
            var store = new Ext.data.JsonStore({
                proxy: httpProxy,
                id: store_id,
                totalProperty: 'total',
                root: 'data',
                fields: [<?php echo $js_grid['ds'] ?>],
                sortInfo: { field: 'name',
                    direction: 'ASC' },
                remoteSort: true,
                listeners:{
                    load:function(){

                        var nodeState = store.reader.jsonData.node_state;
                        var nodeInit = store.reader.jsonData.node_initialize;
                        var nodeHasVgs = store.reader.jsonData.node_has_vgs;
                        var not_running_msg = <?php echo json_encode(__('VirtAgent should be running to enable this menu')) ?>;

                        serverGrid.addwizardBtn.setDisabled(!nodeHasVgs || !nodeState || nodeInit!=<?php echo json_encode(EtvaNode_VA::INITIALIZE_OK); ?>);
                        if(nodeState){

                            if(!nodeInit && serverGrid.addwizardBtn.disabled) serverGrid.addwizardBtn.el.child('button:first').dom.qtip = <?php echo json_encode(__('VirtAgent should be initialized to enable this menu')) ?>;
                            else{
                                if(!nodeHasVgs) serverGrid.addwizardBtn.el.child('button:first').dom.qtip = <?php echo json_encode(__('VirtAgent should have storage information to enable this menu')) ?>;
                                else serverGrid.addwizardBtn.el.child('button:first').dom.qtip = <?php echo json_encode(__('Click to open new server wizard')) ?>;

                            }

                            if(!serverGrid.getSelectionModel().getSelected()){
                                serverGrid.removeBtn.setTooltip(selectItem_msg);
                                serverGrid.consoleBtn.setTooltip(selectItem_msg);
                                serverGrid.startBtn.setTooltip(selectItem_msg);
                                serverGrid.stopBtn.setTooltip(selectItem_msg);
                                if(serverGrid.migrateBtn) serverGrid.migrateBtn.setTooltip(selectItem_msg);
                                serverGrid.editBtn.setTooltip(selectItem_msg);
                            }

                        }
                        else{
                            serverGrid.removeBtn.setTooltip(not_running_msg);
                            serverGrid.consoleBtn.setTooltip(not_running_msg);
                            serverGrid.startBtn.setTooltip(not_running_msg);
                            serverGrid.stopBtn.setTooltip(not_running_msg);
                            if(serverGrid.migrateBtn) serverGrid.migrateBtn.setTooltip(not_running_msg);
                            serverGrid.editBtn.setTooltip(not_running_msg);
                            serverGrid.addwizardBtn.el.child('button:first').dom.qtip = not_running_msg;
                        }


                        //serverGrid.getSelectionModel().fireEvent('selectionchange',serverGrid.getSelectionModel());

                    }
                }
            });


            var expander = new Ext.ux.grid.RowExpander({
                enableCaching : false,
                tpl : new Ext.XTemplate(
                '<p><b>UUID:</b> {uuid}&nbsp&nbsp <b>VNC Port:</b> {vnc_port}&nbsp&nbsp <b>VNC Keymap:</b> {vnc_keymap}<br>',
                '<b>Status:</b>',
                '<span style="color:{[values.vm_state === "running" ? "green" : "red"]}">',
                    ' {values.vm_state}',
                '</span>&nbsp&nbsp <b>Created at:</b> {created_at}<br>',
                '</p>'


            )});
                      

            //var cm = new Ext.grid.ColumnModel([<?php// echo $js_grid['cm'] ?>]);
            var cm = new Ext.grid.ColumnModel([expander,
                            {header:'Name', dataIndex:'name',sortable:true},
                            {header:'Description', dataIndex:'description'},
                            {header:'Memory (MB)', dataIndex:'mem'},
                            {header:'CPUs', dataIndex:'vcpu'},
                            {header:'IP', dataIndex:'ip'},
                            {header:'Type', dataIndex:'vm_type', sortable:true},
                            {header: "State", dataIndex:'vm_state', sortable:true, renderer: function (value, metadata, record, rowIndex, colIndex, store) {
                                if(value!='running') metadata.attr = 'style="background-color: red;color:white;"';
                                else metadata.attr = 'style="background-color: green;color:white;"';
                                return value;
                            }}                            
            ]);



            var bpaging = new Ext.PagingToolbar({
                store: store,
                displayInfo:true,
                pageSize:10,
                plugins:new Ext.ux.Andrie.pPageSize({comboCfg: {width: 50}})
            });

            var cdromstore = new Ext.data.JsonStore({
                id:'id'
                ,root:'data'
                ,totalProperty:'total'
                ,fields:[
                    {name:'name', type:'string'},'full_path'
                ]
                ,proxy: new Ext.data.HttpProxy({
                        url:<?php echo json_encode(url_for('view/iso'))?>
                    })
                ,baseParams:{doAction:'jsonList',params:Ext.encode({emptyValue:true})}

            });

            //build cdrom com for menu bar
            var cdromcombo = new Ext.form.ComboBox({
                editable:false
                ,valueField:'full_path'
                ,hiddenName:'cdromcombo'
                ,displayField:'name'
                ,pageSize:10
                ,triggerAction:'all'
                ,forceSelection:true
                ,selectOnFocus:true
                ,valueNotFoundText: __('Invalid')
                ,mode:'remote'
                //,enableKeyEvents:true
                ,resizable:true
                ,minListWidth:250
                ,getListParent: function() {
                    return this.el.up('.x-menu');
                }
                ,allowBlank:false
                ,store:cdromstore
                ,listeners:{
                    // set tooltip and validate
                    render:function() {
                        this.el.set({qtip: <?php echo json_encode(__('Choose iso to load')) ?>});
                        this.validate();
                    }
                    ,select:function(cb,rec,index){                        
                        cb.ownerCt.fireEvent('onSelect');
                        //Server.Grid.updateRecords({grid:serverGrid,data:[{field:'Boot',value:'cdrom'},{field:'Location',value:rec.data['full_path']}]});
                    }
                }
            });

            var menu_boot = new Ext.menu.Menu({
                        style: {
                            overflow: 'visible'     // For the Combo popup
                        },
                        items: [
                        {
                            text: <?php echo json_encode(__('VM Filesystem')) ?>,
                            name:'filesystem',xtype:'menucheckitem',ref:'boot_filesystem'
                            ,group: 'boot_from'
                            ,listeners:{
                                checkchange: function(chkitem,chk){
                                    if(chk)
                                        Server.Grid.updateRecords({grid:serverGrid,boot:'filesystem',data:[]});
                                }
                            }
                        }, {
                            text: 'Location URL',
                            name: 'location',xtype:'menucheckitem',ref:'boot_location'
                            ,group: 'boot_from'
                            ,listeners:{
                                checkchange: function(chkitem,chk){
                                    if(chk)
                                        Server.Grid.updateRecords({grid:serverGrid,boot:'location',data:[]});

                                }
                            }
                           }
                           ,{
                            text:'PXE'
                            ,xtype:'menucheckitem'
                            ,group: 'boot_from'
                            ,ref:'boot_pxe'
                            ,name:'pxe'
                            ,listeners:{
                                checkchange: function(chkitem,chk){
                                    if(chk)
                                        Server.Grid.updateRecords({grid:serverGrid,boot:'pxe',data:[]});
                                }
                            }
                           }
                          ,{
                            text:'CD-ROM',
                            xtype:'menucheckitem',group: 'boot_from',ref:'boot_cdrom',menu:{items: [cdromcombo],listeners:{'onSelect':function(){
                                        if(!this.ownerCt.checked) this.ownerCt.setChecked(true,false);
                                        else this.ownerCt.fireEvent('checkchange',this.ownerCt,true);
                                }}}
                            ,listeners:{
                                checkchange: function(chkitem,chk){                                    
                                    if(chk){
                                        var full_path = cdromcombo.getValue();
                                        if(full_path)
                                            Server.Grid.updateRecords({grid:serverGrid,boot:'cdrom',data:[{field:'location',value:full_path}]});
                                        else return false;
                                    }

                                }
                            }
                           }                           
                    ]
            });


            // create the editor grid
            var serverGrid = new Ext.grid.EditorGridPanel({
                store: store,
                cm: cm,
                plugins:expander,
                border: false,
                loadMask: {msg: <?php echo json_encode(__('Retrieving data...')) ?>},
                viewConfig:{
                    forceFit:true,
                    emptyText: __('Empty!')  //  emptyText Message
                    //deferEmptyText:false
                },
                autoScroll:true,
                title: grid_title,
                stripeRows:true,
                clicksToEdit:2,
                tbar: [
                    {text: <?php echo json_encode(__('Add server wizard')) ?>,
                        ref: '../addwizardBtn',
                        disabled:true,
                        iconCls: 'icon-add',
                        url:<?php echo(json_encode(url_for('server/wizard?nid=')))?>+this.node_id,
                        handler: View.clickHandler
                    },
                    {text: <?php echo json_encode(__('Open console')) ?>,
                    ref: '../consoleBtn',
                    disabled:true,
                        handler:function(){

                            var sm = serverGrid.getSelectionModel();
                            var sel = sm.getSelected();
                            if (sm.hasSelection()){

                                if(sel.data['vm_state']!='running'){

                                    Ext.Msg.show({
                                        title: this.text,
                                        buttons: Ext.MessageBox.OK,
                                        icon: Ext.MessageBox.INFO,
                                        msg: <?php echo json_encode(__('Cannot open console. Maybe server not running!')) ?>});
                                    return;
                                }

                                if(!navigator.javaEnabled()){
                                    Ext.Msg.show({
                                        title: this.text,
                                        buttons: Ext.MessageBox.OK,
                                        icon: Ext.MessageBox.INFO,
                                        msg: __('Java required!')});
                                    return;
                                }

                                Ext.getBody().mask(<?php echo json_encode(__('Retrieving data...')) ?>);

                                var url = '<?php echo url_for('/view/vncviewer/id/') ?>'+sel.data['id']+'/';
                                var viewerSize = Ext.getBody().getViewSize();
                                var windowHeight = viewerSize.height * 0.95;
                                windowHeight = Ext.util.Format.round(windowHeight,0);

                                var config = {
                                        title: <?php echo json_encode(__('Please wait...')) ?>,
                                        //html:'Loadin applet',
                                        maximizable   : true,
                                        collapsible   : true,
                                        constrain     : true,
                                        defaultSrc:url,
                                        shadow        : Ext.isIE,
                                        autoScroll    : true,
                                        useShim:true,
                                        //loadMask:true,
                                        hidden:true,
                                        hideMode      : 'nosize',
                                        listeners : {
                                            domready : function(frameEl){  //raised for "same-origin" frames only
                                                            var MIF = frameEl.ownerCt;
                                            },
                                            documentloaded : function(frameEl){
                                                
                                                            var MIF = frameEl.ownerCt;                                                                                                                        
                                                            var doc = frameEl.getFrameDocument();
                                                            View.notify({html:doc.title+' reports: DATA LOADED'});
                                                            (function(){Ext.getBody().unmask();}).defer(1000);


                                            },
                                            beforedestroy : function(){}
                                        },
                                        sourceModule : 'mifsimple'
                                };

                                //var ww = window.open(url,'mywidow','height=200,width=150');
                                //if(window.focus) (ww.focus())
                                //return false;

                                var win = new Ext.ux.ManagedIFrame.Window(config);

                                win.show();
                                win.hide();

                            }
                            else{

                                Ext.Msg.show({
                                    title:this.text,
                                    buttons: Ext.MessageBox.OK,
                                    icon: Ext.MessageBox.INFO,
                                    msg: this.el.child('button:first').dom.qtip});
                            }


                        }
                    },                                                            
                    '-'
                    ,{
                        xtype:'splitbutton',
                        ref: '../startBtn',
                        disabled:true,
                        scope:this,
                        text: <?php echo json_encode(__('Start server')) ?>,
                        menu: [{text: <?php echo json_encode(__('Boot From')) ?>

                              ,menu: menu_boot
                            }],
                        listeners:{                            
                            menushow:function(bt,mn){
                                var sm = serverGrid.getSelectionModel();
                                var sel = sm.getSelected();
                                var boot_location = menu_boot.boot_location;
                                var boot_cdrom = menu_boot.boot_cdrom;
                                var boot_pxe = menu_boot.boot_pxe;
                                var boot_filesystem = menu_boot.boot_filesystem;

                                boot_pxe.setVisible(sel.data['vm_type']!='pv');

                                boot_cdrom.setVisible(sel.data['vm_type']!='pv');
                                boot_location.setVisible(sel.data['vm_type']=='pv');
                                boot_location.setDisabled(sel.data['location']=='');

                                boot_location.setChecked(sel.data['boot']=='location',true);
                                boot_filesystem.setChecked(sel.data['boot']=='filesystem',true);
                                boot_pxe.setChecked(sel.data['boot']=='pxe',true);
                                boot_cdrom.setChecked(sel.data['boot']=='cdrom',true);

                                if(sel.data['boot']=='cdrom' || sel.data['vm_type']!='pv'){
                                    if(cdromcombo.getStore().getTotalCount()>0){
                                        var cb_store = cdromcombo.getStore();
                                        var matched = cb_store.findExact('full_path',sel.data['location']);

                                        if(matched == -1) cdromcombo.setValue('');
                                        else cdromcombo.setValue(sel.data['location']);

                                    } 
                                    else cdromcombo.getStore().reload({
                                            callback:function(){
                                                // populate cdrom combo items
                                                var cb_store = cdromcombo.getStore();
                                                var matched = cb_store.findExact('full_path',sel.data['location']);

                                                if(matched == -1) cdromcombo.setValue('');
                                                else cdromcombo.setValue(sel.data['location']);
                                            }});
                                }

                            }
                        },
                        handler: function(item) {
                            var sm = serverGrid.getSelectionModel();
                            var sel = sm.getSelected();

                            if (sm.hasSelection()){

                                var send_data = {'nid':this.node_id,
                                                 'server':sel.data['name']};

                                Ext.Msg.show({
                                    title: item.text,
                                    buttons: Ext.MessageBox.YESNOCANCEL,
                                    msg: String.format(<?php echo json_encode(__('Current state reported: {0}')) ?>,sel.data['vm_state'])+'<br>'
                                         +String.format(<?php echo json_encode(__('Start server {0} ?')) ?>,sel.data['name']),
                                    fn: function(btn){
                                        if (btn == 'yes'){

                                            var conn = new Ext.data.Connection({
                                                listeners:{
                                                    // wait message.....
                                                    beforerequest:function(){
                                                        Ext.MessageBox.show({
                                                            title: <?php echo json_encode(__('Please wait...')) ?>,
                                                            msg: <?php echo json_encode(__('Starting virtual server...')) ?>,
                                                            width:300,
                                                            wait:true
                                                         //   modal: true
                                                        });
                                                    },// on request complete hide message
                                                    requestcomplete:function(){Ext.MessageBox.hide();}
                                                    ,requestexception:function(c,r,o){Ext.Ajax.fireEvent('requestexception',c,r,o);}
                                                }
                                            });// end conn
                                            conn.request({
                                                url: <?php echo json_encode(url_for('server/jsonStart'))?>,
                                                params: send_data,
                                                scope:this,
                                                success: function(resp,opt) {

                                                    var response = Ext.util.JSON.decode(resp.responseText);
                                                    Ext.ux.Logger.info(response['agent'], response['response']);
                                                    store.reload({callback:function(){

                                                            var sm = serverGrid.getSelectionModel();
                                                            var sel = sm.getSelected();                                                            
                                                            serverGrid.fireEvent('updateNodeState',{selected:false,parentNode:sel.data['node_id'],node:'s'+sel.data['id']},sel.data);
                                                    }});

                                                }
                                                ,failure: function(resp,opt) {
                                                    var response = Ext.util.JSON.decode(resp.responseText);
                                                    if(response && resp.status!=401)
                                                        Ext.Msg.show({
                                                            title: String.format(<?php echo json_encode(__('Error {0}')) ?>,response['agent']),
                                                            buttons: Ext.MessageBox.OK,
                                                            msg: String.format(<?php echo json_encode(__('Unable to start virtual server {0}!')) ?>,sel.data['name'])+'<br>'+response['info'],
                                                            icon: Ext.MessageBox.ERROR});
                                                }
                                            });// END Ajax request
                                        }//END button==yes
                                    }// END fn
                                }); //END Msg.show
                            }//END if
                            else{

                                Ext.Msg.show({
                                    title:item.text,
                                    buttons: Ext.MessageBox.OK,
                                    icon: Ext.MessageBox.INFO,
                                    msg: item.el.child('button:first').dom.qtip});
                            }
                        }//END handler
                    },
                    {
                        text: <?php echo json_encode(__('Stop server')) ?>,
                        ref: '../stopBtn',
                        disabled:true,
                        scope:this,
                        handler: function(item) {
                            var sm = serverGrid.getSelectionModel();
                            var sel = sm.getSelected();

                            if (sm.hasSelection()){
                                Ext.Msg.show({
                                    title: item.text,
                                    scope:this,
                                    buttons: Ext.MessageBox.YESNOCANCEL,
                                    msg: String.format(<?php echo json_encode(__('Current state reported: {0}')) ?>,sel.data['vm_state'])+'<br>'
                                         +String.format(<?php echo json_encode(__('Stop server {0} ?')) ?>,sel.data['name']),
                                    icon: Ext.MessageBox.QUESTION,
                                    fn: function(btn){

                                        if (btn == 'yes'){
                                            var params = {'name':sel.data['name']};
                                            var conn = new Ext.data.Connection({
                                                listeners:{
                                                    // wait message.....
                                                    beforerequest:function(){
                                                        Ext.MessageBox.show({
                                                            title: <?php echo json_encode(__('Please wait...')) ?>,
                                                            msg: <?php echo json_encode(__('Stoping virtual server...')) ?>,
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
                                                url: <?php echo json_encode(url_for('server/jsonStop'))?>,
                                                params: {'nid':this.node_id,'server': sel.data['name']},
                                                scope:this,
                                                success: function(resp,opt) {
                                                    var response = Ext.util.JSON.decode(resp.responseText);
                                                    Ext.ux.Logger.info(response['agent'],response['response']);


                                                    store.reload({callback:function(){

                                                            var sm = serverGrid.getSelectionModel();
                                                            var sel = sm.getSelected();
                                                            serverGrid.fireEvent('updateNodeState',{selected:false,parentNode:sel.data['node_id'],node:'s'+sel.data['id']},sel.data);
                                                    }});



                                                },
                                                failure: function(resp,opt) {
                                                    var response = Ext.util.JSON.decode(resp.responseText);

                                                    Ext.ux.Logger.error(response['agent'], response['error']);

                                                    Ext.Msg.show({
                                                        title: String.format(<?php echo json_encode(__('Error {0}')) ?>,response['agent']),
                                                        width:300,
                                                        buttons: Ext.MessageBox.OK,
                                                        msg: String.format(<?php echo json_encode(__('Unable to stop virtual server {0}!')) ?>,sel.data['name'])+'<br>'+response['info'],
                                                        icon: Ext.MessageBox.ERROR});
                                                }
                                            });// END Ajax request
                                        }//END button==yes
                                    }// END fn
                                }); //END Msg.show
                            }//END if
                            else{

                                Ext.Msg.show({
                                    title:item.text,
                                    buttons: Ext.MessageBox.OK,
                                    icon: Ext.MessageBox.INFO,
                                    msg: item.el.child('button:first').dom.qtip});
                            }
                        }//END handler Remove
                    }
                    ,'-',
                    {
                        text: <?php echo json_encode(__('Edit server')) ?>,
                        ref: '../editBtn',
                        disabled:true,
                        iconCls:'icon-edit-record',                        
                        url:<?php echo(json_encode(url_for('server/Server_Edit')))?>,
                        call:'Server.Edit',
                        callback:function(item){
                            var sm = serverGrid.getSelectionModel();
                            var sel = sm.getSelected();

                            var window = new Server.Edit.Window({
                                                title: String.format(<?php echo json_encode(__('Edit server {0}')) ?>,sel.data['name']),
                                                server_id:sel.data['id'],node_id:sel.data['node_id']});

                            window.on({
                                show:{fn:function(){window.loadData({id:sel.data['id']});}}
                                ,onSave:{fn:function(){
                                        this.close();
                                        var parentCmp = Ext.getCmp(serverGrid.id);
                                        parentCmp.fireEvent('refresh',parentCmp);
                                }}
                            });


                            window.show();
                            
                        },
                        handler: function(btn){
                            
                            var sm = serverGrid.getSelectionModel();
                            if (sm.hasSelection())
                                View.loadComponent(btn);
                            
                            else
                                Ext.Msg.show({
                                    title:this.text,
                                    buttons: Ext.MessageBox.OK,
                                    icon: Ext.MessageBox.INFO,
                                    msg: this.el.child('button:first').dom.qtip});
                            
                        }
                    },
                    <?php if($sf_user->getAttribute('etvamodel')!='standard'): ?>
                    {text: <?php echo json_encode(__('Migrate server')) ?>,
                        ref: '../migrateBtn',
                        disabled:true,
                        url:<?php echo(json_encode(url_for('server/Server_Migrate')))?>,
                        call:'Server.Migrate',
                        callback:function(item){
                            var sm = serverGrid.getSelectionModel();
                            var sel = sm.getSelected();

                            var window = new Server.Migrate.Window({title:item.text,type:item.type, parent:serverGrid.id}).show();
                            window.loadData(sel);
                            //eval("var window = new "+item.call+".Window().show();window.loadData(sel)");
                        },
                        handler: function(btn){
                            var sm = serverGrid.getSelectionModel();
                            if (sm.hasSelection()){
                                View.loadComponent(btn);
                            }
                            else{

                                Ext.Msg.show({
                                    title:this.text,
                                    buttons: Ext.MessageBox.OK,
                                    icon: Ext.MessageBox.INFO,
                                    msg: this.el.child('button:first').dom.qtip});
                            }
                        }
                    },
                    <?php endif; ?>
                    {
                        text: <?php echo json_encode(__('Remove server')) ?>,
                        ref: '../removeBtn',
                        disabled:true,
                        iconCls:'icon-remove',
                        url:<?php echo(json_encode(url_for('server/Server_Remove')))?>,
                        call:'Server.Remove',
                        scope:this,
                        callback:function(item){

                            var sm = serverGrid.getSelectionModel();
                            var sel = sm.getSelected();                                                       

                            var window = new Server.Remove.Window({
                                                title: <?php echo json_encode(__('Remove server')) ?>,parent:serverGrid.id});

                            var rec = new Object();
                            rec.data = {'server':sel.data['name'],'server_id':sel.id};

                            window.on('beforeshow',function(){window.loadData(rec);});
                            window.show();

                        },
                        handler: function(btn){

                            var sm = serverGrid.getSelectionModel();
                            if (sm.hasSelection())
                                View.loadComponent(btn);

                            else
                                Ext.Msg.show({
                                    title:this.text,
                                    buttons: Ext.MessageBox.OK,
                                    icon: Ext.MessageBox.INFO,
                                    msg: this.el.child('button:first').dom.qtip});

                        }
                    }                    
//                    ,
//                    '-',
//                    {text: __('Refresh'),
//                        xtype: 'button',
//                        tooltip: __('Refresh'),
//                        iconCls: 'x-tbar-loading',
//                        scope:this,
//                        handler: function(button,event)
//                        {
//                            button.addClass('x-item-disabled');
//
//
//                            store.reload({
//                                callback:function(){button.removeClass('x-item-disabled');}});
//                        }
//                    }
                    ,{
                      xtype: 'tbfill'
                    },{
                        xtype: 'panel',
                        baseCls: '',
                        tools:[{id:'help', qtip: __('Help'),handler:function(){View.showHelp({anchorid:'help-vmachine-main',autoLoad:{ params:'mod=server'},title: <?php echo json_encode(__('Server Info Help')) ?>});}}]
                    }
                ],// END tbar
                bbar : bpaging,
                sm: new Ext.grid.RowSelectionModel({
                    singleSelect: true,
                    moveEditorOnEnter:false

                }),
                listeners: {
                    afteredit: function(e){
                        Server.Grid.updateRecords({grid:serverGrid,data:[{field:e.field,value:e.value}]});
                    }//END afteredit

                }
            });//END serverGrid


            serverGrid.getSelectionModel().on('selectionchange', function(sm){

                var nodeState = serverGrid.store.reader.jsonData.node_state;
                var btnState = sm.getCount() < 1 ? true :false;
                var selected = sm.getSelected();
                
                serverGrid.removeBtn.setTooltip(btnState ? selectItem_msg : is_selectItem_msg);
                serverGrid.consoleBtn.setTooltip(btnState ? selectItem_msg : is_selectItem_msg);
                serverGrid.startBtn.setTooltip(btnState ? selectItem_msg : is_selectItem_msg);
                serverGrid.stopBtn.setTooltip(btnState ? selectItem_msg : is_selectItem_msg);
                if(serverGrid.migrateBtn) serverGrid.migrateBtn.setTooltip(btnState ? selectItem_msg : is_selectItem_msg);
                serverGrid.editBtn.setTooltip(btnState ? selectItem_msg : is_selectItem_msg);

                serverGrid.removeBtn.setDisabled(btnState);
                serverGrid.consoleBtn.setDisabled(btnState);
                
                serverGrid.stopBtn.setDisabled(btnState);
                if(serverGrid.migrateBtn) serverGrid.migrateBtn.setDisabled(btnState);
                
                if(selected){

                      serverGrid.startBtn.setDisabled(selected.data['vm_state']=='running');

                     if(selected.data['vm_state']=='running')
                     {


                       if(selected.data['vm_type']!='pv')
                       {
                           serverGrid.editBtn.setTooltip(<?php echo json_encode(__('Server need to be stop to edit!')) ?>);
                           serverGrid.editBtn.setDisabled(true);
                       }

                       <?php if($sf_user->getAttribute('etvamodel')!='standard'): ?>

                       /*
                        *
                        * check migrate/move button
                        */
                         serverGrid.migrateBtn.type = 'migrate';
                         serverGrid.migrateBtn.setTooltip(<?php echo json_encode(__('To perform a move instead of a migrate the server must be stopped!')); ?>);
                         serverGrid.migrateBtn.setText(<?php echo json_encode(__('Migrate server')) ?>);

                       <?php endif; ?>

                     }
                     else
                     {

                         serverGrid.editBtn.setDisabled(false);

                         <?php if($sf_user->getAttribute('etvamodel')!='standard'): ?>

                         /*
                          *
                          * check migrate/move button
                          */
                         serverGrid.migrateBtn.type = 'move';
                         serverGrid.migrateBtn.setTooltip(<?php echo json_encode(__('To perform a migrate instead of a move the server must be running!')); ?>);
                         serverGrid.migrateBtn.setText(<?php echo json_encode(__('Move server')) ?>);

                         <?php endif; ?>
                     }
                     
                     if(serverGrid.migrateBtn && !selected.data['all_shared_disks'])
                     {
                             serverGrid.migrateBtn.setTooltip(<?php echo json_encode(__(EtvaLogicalvolumePeer::_NOTALLSHARED_)); ?>);
                             serverGrid.migrateBtn.setDisabled(true);
                     }
                }




            });



            serverGrid.on({
                refresh:{scope:this,fn:function(){
                    
                    store.load.defer(100,store,[{params:{start:0, limit:10}}]);
                }}
                ,activate:function(){serverGrid.fireEvent('refresh');}
                ,rowcontextmenu:function(grid,rowIndex,e){

                    grid.getSelectionModel().selectRow(rowIndex);                    

                    if (!this.menu) {
                        this.menu = new Ext.menu.Menu({
                                items: [
                                    {
                                        text: <?php echo json_encode(__('Edit server')) ?>,
                                        ref: 'editBtn',
                                        iconCls:'icon-edit-record',
                                       // disabled:true,
                                        url:<?php echo(json_encode(url_for('server/Server_Edit')))?>,
                                        call:'Server.Edit',
                                        callback:function(item){

                                            var sm = grid.getSelectionModel();
                                            var sel = sm.getSelected();

                                            var window = new Server.Edit.Window({
                                                title: String.format(<?php echo json_encode(__('Edit server {0}')) ?>,sel.data['name']),
                                                server_id:sel.data['id'],node_id:sel.data['node_id'],parent:grid.id});

                                            window.on('show',function(){window.loadData({id:sel.data['id']});});
                                            window.show();

                                        },
                                        handler: function(btn){

                                            var sm = grid.getSelectionModel();
                                            if (sm.hasSelection())
                                                View.loadComponent(btn);
                                            else
                                                Ext.Msg.show({
                                                    title:this.text,
                                                    buttons: Ext.MessageBox.OK,
                                                    icon: Ext.MessageBox.INFO,
                                                    msg: this.el.child('button:first').dom.qtip});

                                        }
                                    }
                                ]});
                            
                    }
                    //this.rowctx = rowIndex;
            
                    this.menu.showAt(e.getXY());
                    
                    
                    var sm = grid.getSelectionModel();
                    var sel = sm.getSelected();
                    
                    if(sel.data['vm_type']!='pv' && sel.data['vm_state']=='running') this.menu.editBtn.setDisabled(true);
                    else this.menu.editBtn.setDisabled(false);

                    e.preventDefault();
                }
            });

            return serverGrid;
        }//Fim init


            //     ,afterlayout:{scope:this, single:true, fn:function() {
            // store.load({params:{start:0, limit:10}});
            // }}
            
        }
    }();

</script>