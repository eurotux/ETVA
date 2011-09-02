<?php
include_partial('sfGuardGroup/grid');
include_partial('sfGuardGroup/SfGuardGroup_CreateEdit');
include_partial('sfGuardPermission/grid');
include_partial('sfGuardPermission/SfGuardPermission_CreateEdit');
// include_partial('sfGuardGroup/grid',array('server_id'=>$server_id,'node_id'=>$node_id,'sfGuardGroup_tableMap'=>$sfGuardGroup_tableMap,'server_form'=>$server_form,'server_tableMap'=>$server_tableMap));

?>
<script>

Ext.ns("SfGuardGroup");

SfGuardGroup.gridForm = function(config) {

    var createEdit = new SfGuardGroup.CreateEdit();    
    var listGrid = new SfGuardGroup.Grid();

    //on save fire grid store update event to store data in db
    createEdit.on('onSave',function(rec){
        var store = listGrid.getStore();

        var conn = new Ext.data.Connection({
            listeners:{
                // wait message.....
                beforerequest:function(){
                    Ext.MessageBox.show({
                        title: 'Please wait',
                        msg: 'Setting up configuration...',
                        width:300,
                        wait:true,
                        modal: false
                    });
                },// on request complete hide message
                requestcomplete:function(){Ext.MessageBox.hide();}}
        });// end conn

        conn.request({
            url: 'sfGuardGroup/jsonUpdate',
            scope:this,
            params:rec.data,
            success: function(resp,opt) {
                store.reload();
                if(rec.data['id']==null) this.fireEvent('onAdd');
            },
            failure: function(resp,opt) {

                var response = Ext.util.JSON.decode(resp.responseText);
                Ext.ux.Logger.error(response['agent'],response['error']);

                Ext.Msg.show({title: 'Error '+response['agent'],
                    buttons: Ext.MessageBox.OK,
                    msg: response['info'],
                    icon: Ext.MessageBox.ERROR});
            }
        });//END Ajax request

        
    });

    listGrid.on({
        rowclick:function(g, index, ev){
            var rec = g.store.getAt(index);
            createEdit.loadRecord(rec);
        }
        // on grid click Add button
        ,onAdd:function(){
            createEdit.clean();
            createEdit.focusForm();
        }
        // on grid remove item (after remove)
        ,onRemove:function(){
            listGrid.getStore().reload();
            createEdit.clean();
        }
    });

    Ext.apply(this,config);

   
    SfGuardGroup.gridForm.superclass.constructor.call(this, {    	
        frame: true,
        labelAlign: 'left',
        //bodyStyle:'padding:5px',
        autoScroll:true,
        layout: 'border',defaults:{layout:'fit'},
        items: [{
                    region:'center'
                    ,items: [listGrid]
                }
                ,{
                    region:'east',defaults:{autoScroll:true},
                    bodyStyle: 'padding-left:10px;',
                    width:300
                    ,items:[createEdit]
                }
        ]
    });



    this.on('activate',function(){
        listGrid.store.reload();
        createEdit.clean();
        createEdit.reload();});
}

// define public methods
Ext.extend(SfGuardGroup.gridForm, Ext.Panel, {});

SfGuardPermission.gridForm = function(config) {

    var createEdit = new SfGuardPermission.CreateEdit();
    var listGrid = new SfGuardPermission.Grid();

    //on save fire grid store update event to store data in db
    createEdit.on('onSave',function(rec){
        var store = listGrid.getStore();        
        
        var conn = new Ext.data.Connection({
            listeners:{
                // wait message.....
                beforerequest:function(){
                    Ext.MessageBox.show({
                        title: 'Please wait',
                        msg: 'Setting up configuration...',
                        width:300,
                        wait:true,
                        modal: false
                    });
                },// on request complete hide message
                requestcomplete:function(){Ext.MessageBox.hide();}}
        });// end conn
        
        conn.request({
            url: 'sfGuardPermission/jsonUpdate',
            scope:this,
            params:rec.data,
            success: function(resp,opt) {
                store.reload();
                if(rec.data['id']==null) this.fireEvent('onAdd');
            },
            failure: function(resp,opt) {

                var response = Ext.util.JSON.decode(resp.responseText);
                Ext.ux.Logger.error(response['agent'],response['error']);

                Ext.Msg.show({title: 'Error '+response['agent'],
                    buttons: Ext.MessageBox.OK,
                    msg: response['info'],
                    icon: Ext.MessageBox.ERROR});
            }
        });//END Ajax request
                





    });

    listGrid.on({
        rowclick:function(g, index, ev){
            var rec = g.store.getAt(index);
            createEdit.loadRecord(rec);
            
            //createEdit.load(rec.id);
        }
        // on grid click Add button
        ,onAdd:function(){
            createEdit.clean();
            createEdit.focusForm();
        }
        // on grid remove item (after remove)
        ,onRemove:function(){
            listGrid.getStore().reload();
            createEdit.clean();
        }
    });

    Ext.apply(this,config);


    SfGuardPermission.gridForm.superclass.constructor.call(this, {
        frame: true,
        labelAlign: 'left',
        //bodyStyle:'padding:5px',
        autoScroll:true,
        layout: 'border',defaults:{layout:'fit'},
        items: [{
                    region:'center'
                    ,items: [listGrid]
                }
                ,{
                    region:'east',defaults:{autoScroll:true},
                    bodyStyle: 'padding-left:10px;',
                    width:300
                    ,items:[createEdit]
                }
        ]
    });

    this.on('activate',function(){
        listGrid.store.reload();
        createEdit.clean();
        createEdit.reload();});
    
}

// define public methods
Ext.extend(SfGuardPermission.gridForm, Ext.Panel, {});


SfGuardGroup.Main = function(app) {

    // main panel

    var win = Ext.getCmp('sfguardGroup-main');

    if(!win){        

        win = new Ext.Window({
            id: 'sfguardGroup-main',
            title    : 'Groups/Permissions Administration',
            closable : true,
            closeAction:'hide',
            width    : 900,
            height   : 350,
            modal:true,
            items    : [
                new Ext.TabPanel({
                    region    : 'center',
                    margins   : '3 3 3 0',
                    activeTab : 0,
                    defaults  : {autoScroll : true},
                    items     : [new SfGuardGroup.gridForm({title:'Manage Groups',id:'gg'}),
                                 new SfGuardPermission.gridForm({title:'Manage Permissions',id:'pp'})                                 
                    ]                    
                })
            ],
            plain    : true,
            layout   : 'border'
            ,listeners:{
                show:function() {
                    this.loadMask = new Ext.LoadMask(this.body, {msg:'Loading. Please wait...'});
                }
        }});
                
        alert('nao existe');
    }else alert('ja existe');

    win.show();


};

new SfGuardGroup.Main();

</script>