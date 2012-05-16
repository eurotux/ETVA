<?php

$js_grid = js_grid_info($tableMap);


?>
<script>
/*
 * Partial vlanGrid
 */
// shorthand alias
Ext.namespace('Vlan');    
Vlan.Grid = Ext.extend(Ext.grid.GridPanel,{
    border: false,    
    loadMask: {msg: <?php echo json_encode(__('Retrieving data...')) ?>},
    viewConfig:{
        emptyText: __('Empty!'),  //  emptyText Message
        forceFit:true
    },
    autoScroll:true,
    stripeRows:true,    
    clicksToEdit:1,
    initComponent:function(){        

        <?php
            $url = json_encode(url_for('vlan/jsonList'));
            $store_id = json_encode($js_grid['pk']);
        ?>

        var gridUrl = <?php echo $url ?>;
        var store_id = <?php echo $store_id ?>;
        var sort_field = store_id;
        var httpProxy = new Ext.data.HttpProxy({url: gridUrl});

        // the check column is created using a custom plugin
        var checkColumn = new Ext.grid.CheckColumn({
            header: 'Network tagged',
            dataIndex: 'tagged',align:'center',
            width: 60
        });
        
        this.cm = new Ext.grid.ColumnModel([
                {header: "Id", width: 15, sortable: true, dataIndex: 'id'},
                {header: "Network ID", width: 20, sortable: true, dataIndex: 'vlanid'},
                {header: "Network Name", width: 135, sortable: true, dataIndex: 'name'},
                {header: "Interface", width: 125, sortable: true, dataIndex: 'intf'},
                checkColumn
                ]);
        this.autoExpandColumn = 'name';

        // create the Data Store
        this.store = new Ext.data.JsonStore({
            proxy: httpProxy,
            //id: store_id,
            baseParams:{id:this.clusterId},
            totalProperty: 'total',
            root: 'data',
            fields: [<?php echo $js_grid['ds'] ?>],
            sortInfo: { field: 'vlanid',
                direction: 'ASC' },
            remoteSort: false
        });

        this.tbar = [];
        <?php if($sf_user->getAttribute('etvamodel')!='standard'): ?>
        this.tbar.push(
            {
                scope:this,
                text: <?php echo json_encode(__('Add network')) ?>,
                iconCls: 'icon-add',
                url: <?php echo json_encode(url_for('vlan/Vlan_CreateForm')); ?>,
                call:'Vlan.Create',                
                callback:function(item){
                    var grid = (item.ownerCt).ownerCt;

                    var win = new Vlan.Create({
                        cluster_id:grid.clusterId,
                        title:item.text
                        ,listeners:{
                            onVlanCancel:function(){
                                win.close();
                            }
                            ,onVlanSuccess:function(){
                                win.close();
                                grid.getStore().reload();
                            },
                            onVlanFailure:function(){
                                win.close();
                                grid.getStore().reload();
                            }
                        }
                    });
                    win.show();
                },
                handler:View.loadComponent                
            }// END Add button
            ,
            {
                text: <?php echo json_encode(__('Remove network')) ?>,
                iconCls: 'icon-remove',
                disabled:true,
                ref:'../remove_btn',
                scope:this,
                handler:function(){

                    var sm = this.getSelectionModel();
                    var sel = sm.getSelected();
                    var grid = this;
                    if (sm.hasSelection()){
                        Ext.Msg.show({
                            title: <?php echo json_encode(__('Remove network')) ?>,
                            buttons: Ext.MessageBox.YESNOCANCEL,
                            msg: String.format(<?php echo json_encode(__('Remove network {0} ?')) ?>,sel.data['name']),
                            scope:this,
                            fn: function(btn){

                                if (btn == 'yes'){

                                    var conn = new Ext.data.Connection({
                                            listeners:{
                                            // wait message.....
                                            beforerequest:function(){
                                                Ext.MessageBox.show({
                                                    title: <?php echo json_encode(__('Please wait...')) ?>,
                                                    msg: <?php echo json_encode(__('Removing network...')) ?>,
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
                                        url: <?php echo json_encode(url_for('vlan/jsonRemove'))?>,
                                        params: {'name': sel.data['name'], cluster_id:this.clusterId},
                                        scope:this,
                                        success: function(resp,opt) {

                                            var response = Ext.util.JSON.decode(resp.responseText);
                                            var txt = response['response'];
                                            var agent = response['agent'];

                                            var length = txt.length;

                                            for(var i=0;i<length;i++){
                                                Ext.ux.Logger.info(txt[i]['agent'],txt[i]['info']);
                                            }
                                            this.store.reload();
                                            grid.fireEvent('reloadInterfaces');

                                        }
                                        ,failure: function(resp,opt) {

                                            var response = Ext.decode(resp.responseText);

                                            if(response && resp.status!=401){
                                                var errors = response['error'];
                                                var oks = response['ok'];
                                                var errors_length = errors.length;
                                                var oks_length = oks.length;
                                                var agents = '<br>';

                                                var logger_errormsg = [String.format(<?php echo json_encode(__('Network {0} could not be uninitialized: {1}')) ?>,sel.data['name'] ,'')];
                                                var logger_okmsg = [String.format(<?php echo json_encode(__('Network {0} uninitialized: ')) ?>,sel.data['name'])];
                                                
                                                var logger_error = [];
                                                var logger_ok = [];
                                                for(var i=0;i<errors_length;i++){
                                                    agents += '<b>'+errors[i]['agent']+'</b> - '+errors[i]['error']+'<br>';
                                                    logger_error[i] = '<b>'+errors[i]['agent']+'</b>('+errors[i]['error']+')';
                                                }

                                                for(var i=0;i<oks_length;i++){
                                                    logger_ok[i] = '<b>'+oks[i]['agent']+'</b>';
                                                }

                                                logger_errormsg += logger_error.join(', ');
                                                logger_okmsg += logger_ok.join(', ');

                                                Ext.ux.Logger.error(response['agent'],logger_errormsg);
                                                if(logger_ok.length>0) Ext.ux.Logger.info(response['agent'],logger_okmsg);

                                                var msg = String.format(<?php echo json_encode(__('Network {0} could not be uninitialized: {1}')) ?>,sel.data['name'],'<br>'+agents);

                                                this.store.reload();
                                                grid.fireEvent('reloadInterfaces');

                                                Ext.Msg.show({
                                                    title: String.format(<?php echo json_encode(__('Error {0}')) ?>,response['agent']),
                                                    width:300,
                                                    buttons: Ext.MessageBox.OK,
                                                    msg: msg,
                                                    icon: Ext.MessageBox.ERROR});
                                            }



                                        }
                                    });// END Ajax request

                                }//END button==yes
                            }// END fn
                        }); //END Msg.show
                    }//END if    
                }
            });
                                        
        <?php endif; ?>
        this.tbar.push('->',
                    {text: <?php echo json_encode(__('MAC Pool Management')) ?>,
                        url:'mac/createwin?cid='+this.clusterId,
                        handler: View.clickHandler
                    });
    

        this.bbar = new Ext.ux.grid.TotalCountBar({
            store:this.store
            ,displayInfo:true
        });

        this.sm = new Ext.grid.RowSelectionModel({
                singleSelect: true,
                moveEditorOnEnter:false               
        });

        this.plugins = checkColumn;

        Vlan.Grid.superclass.initComponent.call(this);

        // load the store at the latest possible moment
        this.on({
            afterlayout:{scope:this, single:true, fn:function() {
                this.store.load();
            }
        }


        });

        this.getSelectionModel().on('selectionchange',function(sm){

                if(this.remove_btn)
                {
                    this.remove_btn.setDisabled(sm.getCount() < 1);

                    var selected = sm.getSelected();
                    if(selected && selected.data['name'] == <?php
                                                $etvamodel = $sf_user->getAttribute('etvamodel');
                                                $devices = sfConfig::get('app_device_interfaces');
                                                echo json_encode($devices[$etvamodel]['va_management']);?>)
                    {
                        this.remove_btn.setDisabled(true);
                        this.remove_btn.setTooltip(<?php echo json_encode(__('Cannot delete default network')) ?>);

                    }
                    else{
                        this.remove_btn.setTooltip('');
                        if(selected) this.remove_btn.setDisabled(false);
                    }
                        

                    

                }
                
                

        },this);

    }//Fim init
    ,
    reload:function(){
        this.store.reload();        
    }
});
// END Vlan.Grid

</script>
