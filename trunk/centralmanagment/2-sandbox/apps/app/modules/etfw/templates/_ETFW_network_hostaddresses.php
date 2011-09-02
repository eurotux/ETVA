<script>

Ext.ns('ETFW.Network.HostAddresses');

ETFW.Network.HostAddresses.Grid = Ext.extend(Ext.grid.GridPanel, {
    initComponent:function() {

        // show check boxes
        var selectBoxModel= new Ext.grid.CheckboxSelectionModel({keepSelections:true});

        var editor = new Ext.ux.grid.RowEditor({
            saveText: 'Update'
        });

        // column model
        var cm = new Ext.grid.ColumnModel([
            selectBoxModel,
            {
                header: "IP Address", dataIndex: 'address', width:120,
                sortable: true,editor: new Ext.form.TextField()
            },
            {
                header: "Hostnames", dataIndex: 'hosts', width:120,
                sortable: true,editor: new Ext.form.TextField()
            }
        ]);

        var dataStore = new Ext.data.JsonStore({
            url: this.url,
            baseParams:{id:this.service_id,method:'list_hosts'},
            id: 'id',
            remoteSort: false,
            totalProperty: 'total',
            root: 'data',
            fields: [{name:'id'},{name:'address'},{name:'hosts'}]
        });
        dataStore.setDefaultSort('address', 'ASC');

        var config = {
            store:dataStore
            ,cm:cm
            ,sm:selectBoxModel
            ,viewConfig:{forceFit:true}
            ,loadMask:true
        }; // eo config object

        // apply config
        Ext.apply(this, Ext.apply(this.initialConfig, config));

        this.bbar = new Ext.ux.grid.TotalCountBar({
            store:this.store
            ,displayInfo:true
        });

        this.plugins = editor;
        this.layout = 'fit';
        this.tbar = [{
                        iconCls: 'add',
                        text: 'Add host address',
                        handler: function(){
                                    var Record = this.store.recordType;
                                    var rec = new Record({address:'',hosts:''});

                                    editor.stopEditing();
                                    this.store.insert(0, rec);
                                    //this.getView().refresh();
                                    this.getSelectionModel().selectRow(0);
                                    editor.startEditing(0);
                        },scope:this
                    },
                    {
                        ref: '../removeBtn',
                        iconCls:'remove',
                        text: 'Remove host(s) address',
                        disabled: true,
                        handler: function(){
                                    editor.stopEditing();
                                    new Grid.util.DeleteItem({panel: this.id});
                        },scope:this
                    }];



        // call parent
        ETFW.Network.HostAddresses.Grid.superclass.initComponent.apply(this, arguments);

        this.getSelectionModel().on('selectionchange', function(sm){
            this.removeBtn.setDisabled(sm.getCount() < 1);
        },this);


        this.store.on('update',function(store,rec,op){
                    var rec_hosts = rec.data.hosts;
                    var host_array = rec_hosts.split(",");

                    var method = 'modify_host';
                    var send_data = {"address":rec.data.address,"hosts":host_array};

                    if(typeof(rec.data.id)=='undefined') method = 'create_host';
                    else send_data.index = rec.data.id;


                    var conn = new Ext.data.Connection({
                        listeners:{
                            // wait message.....
                            beforerequest:function(){
                                Ext.MessageBox.show({
                                    title: 'Please wait',
                                    msg: 'Processing host address...',
                                    width:300,
                                    wait:true,
                                    modal: false
                                });
                            },// on request complete hide message
                            requestexception:function(){Ext.MessageBox.hide();}
                            ,requestcomplete:function(){Ext.MessageBox.hide();}
                        }
                    });// end conn


                    conn.request({
                        url: this.url,
                        params:{id:this.service_id,method:method,params:Ext.encode(send_data)},
                        failure: function(resp,opt){

                            if(!resp.responseText){
                                Ext.ux.Logger.error(resp.statusText);
                                return;
                            }

                            var response = Ext.util.JSON.decode(resp.responseText);
                            Ext.MessageBox.alert('Error Message', response['info']);
                            Ext.ux.Logger.error(response['error']);

                        },
                        // everything ok...
                        success: function(resp,opt){

                            var msg = 'Added/updated host address ';
                            Ext.ux.Logger.info(msg);
                            View.notify({html:msg});
                            this.store.reload();
                        },scope:this
                    });// END Ajax request

        },this);// end store update




        this.on({
            afterlayout:{scope:this, single:true, fn:function() {
                    this.store.load();
                }}
        });


        this.on('beforerender',function(){
            Ext.getBody().mask('Loading ETFW network host addresses panel...');}
            ,this
        );

        this.on('render',function(){
            Ext.getBody().unmask();}
            ,this
            ,{delay:10}
        );


    } // eo function initComponent
    ,reload : function() {
        this.store.load();
    }

    ,// call delete stuff now
    // Server side will receive delData throught parameter
    deleteData : function (items) {

        var conn = new Ext.data.Connection({
            listeners:{
                // wait message.....
                beforerequest:function(){
                    Ext.MessageBox.show({
                        title: 'Please wait',
                        msg: 'Removing host(s) address...',
                        width:300,
                        wait:true,
                        modal: false
                    });
                },// on request complete hide message
                requestcomplete:function(){Ext.MessageBox.hide();}
            }
        });// end conn

        for(var i=0,len = items.length;i<len;i++){


            var data = {"address":items[i].data.address};


            conn.request({
                url: this.url,
                params:{id:this.service_id,method:'delete_host',params:Ext.encode(data)},
                failure: function(resp,opt){
                    var response = Ext.util.JSON.decode(resp.responseText);
                    Ext.MessageBox.alert('Error Message', response['info']);
                    Ext.ux.Logger.error(response['error']);

                },
                // everything ok...
                success: function(resp,opt){

                    var msg = 'Removed host(s) address ';
                    Ext.ux.Logger.info(msg);
                    View.notify({html:msg});
                    this.reload();
                },scope:this
            });// END Ajax request

        }}

});
Ext.reg('etfw_network_hostaddresses_grid', ETFW.Network.HostAddresses.Grid);



ETFW.Network.HostAddresses.Main = function(service_id) {



    ETFW.Network.HostAddresses.Main.superclass.constructor.call(this, {

        border:false,
      //  frame: true,
        layout:'fit',
        title: 'Host Addresses',
        //autoScroll:true,
        items: [{                
                service_id:service_id,
                url:<?php echo json_encode(url_for('etfw/json'))?>,
                xtype:'etfw_network_hostaddresses_grid'
               }]

    });
}

// define public methods
Ext.extend(ETFW.Network.HostAddresses.Main, Ext.Panel, {
    reload:function(){
        var grid = this.get(0);

        if(grid.rendered)
        {
            grid.reload();
        }

    }
});



</script>