
<script>

    /*
     * auxiliary function to move record up and down
     */
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
    // end

    var node_id = <?php echo($etva_server->getNodeId()); ?>;
    var server_id = <?php echo($etva_server->getId()); ?>;
    var server_name = <?php echo json_encode($etva_server->getName()); ?>;

    var mac_vlan_record = Ext.data.Record.create([        
        {name: 'mac', type: 'string'},
        {name: 'vlan'}
    ]);




    var storeVlansCombo = new Ext.data.JsonStore({
        // id:'Id'
        root:'data'
        ,totalProperty:'total'
        ,fields:[
            {name:'Id', type:'string'}
            ,{name:'Name', type:'string'}
        ]
        ,url:<?php echo json_encode(url_for('vlan/jsonList'))?>
        });


    var mac_vlan_cm = new Ext.grid.ColumnModel([
        new Ext.grid.RowNumberer(),
        {
            id:'mac',
            header: "MAC Address",
            dataIndex: 'mac',
            fixed:true,
            allowBlank: false,
            width: 120,
            renderer: function(val){return '<span ext:qtip="Drag and Drop to reorder">' + val + '</span>';}
        },
        {
            header: "VLAN",
            dataIndex: 'vlan',
            width: 130,
            renderer:function(value,meta,rec){
                if(!value){ return '<b>Select vlan...</b>';}
                else{ rec.commit(true); return value;}
            },
            editor: new Ext.form.ComboBox({
                typeAhead: true,
                editable:false,
                triggerAction: 'all',
                store:storeVlansCombo,
                displayField:'Name',
                lazyRender:true,
                listClass: 'x-combo-list-small',
                listeners: {                    
                    select:function(combo,record,index){
                    
                        var record_ = mac_vlan_grid.getSelectionModel().getSelected();
                        record_.set('vlan', this.getValue());
                        record_.set('vlan_id', record.data.Id);
                    
                    }
                }// end listeners
            })
        }
    ]);// end mac_vlan columnmodel


    var queryServer = {'server_id':server_id};
    // create the data store to retrieve network data
    var store_networks = new Ext.data.JsonStore({
        proxy: new Ext.data.HttpProxy({url:'network/jsonGridNoPager'}),
        baseParams: {'query': Ext.encode(queryServer)},
        totalProperty: 'total',
        root: 'data',
        fields: [{name:'vlan',mapping:'Vlan'},{name:'mac',mapping:'Mac'},
            {name:'id',mapping:'Id'}],
        remoteSort: false
    });

    store_networks.load();

    var mac_vlan_grid = new Ext.grid.EditorGridPanel({        
        store:store_networks,
        autoScroll: true,        
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
        sm: new Ext.grid.RowSelectionModel({
            singleSelect: true,
            moveEditorOnEnter:false
        }),
        tbar: [{
        //adds new mac from poll
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

                            mac_vlan_grid.getStore().insert(0, new_record);
                            mac_vlan_grid.getView().refresh();
                            mac_vlan_grid.getSelectionModel().selectRow(0, true);

                        },
                        failure: function(resp,opt) {
                            var response = Ext.util.JSON.decode(resp.responseText);

                            Ext.ux.Logger.error(response['error']);

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
        },
        isValid:function(editInvalid) {
            var cols = this.colModel.getColumnCount();
            var rows = this.store.getCount();
            if(rows==0) return false;

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
            return valid;
        }

    });


    Ext.onReady(function(){

        Ext.QuickTips.init();

        win = new Ext.Window({
            title: 'Attach/detach interfaces for '+server_name,
            width:430,
            height:210,
            iconCls: 'icon-window',
            bodyStyle: 'padding:10px;',
            shim:false,
            border:true,
            resizable:false,
            draggable:false,
            constrainHeader:true,
            layout: 'fit',
            modal:true,
            items:mac_vlan_grid,            
            buttons: [{
                    text: 'Save',
                    handler: function(){
                        if(mac_vlan_grid.isValid()){
                            store_nets();
                        }
                        else{
                            Ext.Msg.show({title: 'Error',
                                buttons: Ext.MessageBox.OK,
                                msg: 'Choose vlan!',
                                icon: Ext.MessageBox.INFO});
                        }

                    }
                },
                {
                    text: 'Cancel',
                    handler: function(){win.close();}
                }
            ]// end buttons
        });
        win.show();



        // called on save button
        function store_nets(){
           
            var networks=[];
            var nets_store = mac_vlan_grid.getStore();
            var i = 0;
            
            nets_store.each(function(f){
               
                    var data = f.data;
                    var insert = {                        
                        'port':i,
                        'vlan':data['vlan'],
                        'mac':data['mac']};

                    networks.push(insert);
                    i++;                   
            });            


            var conn = new Ext.data.Connection({
                listeners:{
                    // wait message.....
                    beforerequest:function(){
                        Ext.MessageBox.show({
                            title: 'Please wait',
                            msg: 'Processing interfaces...',
                            width:300,
                            wait:true,
                            modal: false
                        });
                    },// on request complete hide message
                    requestcomplete:function(){Ext.MessageBox.hide();}}
            });// end conn
            conn.request({
                  url: <?php echo json_encode(url_for('network/jsonReplace')) ?>,
                // url: <?php // echo json_encode(url_for('network/jsonCleanInsert',false)) ?>,
                params:{'nid':node_id,'server':server_name,'networks': Ext.encode(networks)},
                // params:{'networks': Ext.encode(network),'sid':server_id},
                scope: this,
                success: function(resp,options) {

                    var response = Ext.util.JSON.decode(resp.responseText);
                    
                    Ext.ux.Logger.info(response['response']);


                    // if this window was called in the main network interfaces grid
                    if(Network.InterfacesGrid)
                        Network.InterfacesGrid.reload();
                    // if this window was called in the server network grid tab
                    if(Network.Grid)
                        Network.Grid.reload();

                    //close interfaces window
                    win.close();
                    

                }
                ,
                failure: function(resp,opt) {

                    var response = Ext.util.JSON.decode(resp.responseText);
                    Ext.ux.Logger.error(response['error']);

                    Ext.Msg.show({title: 'Error',
                            buttons: Ext.MessageBox.OK,
                            msg: 'Unable to attach/detach interfaces ',
                            icon: Ext.MessageBox.ERROR});

                }
            }); // END Ajax request


        }


    });



</script>