<?php
include_partial('sfGuardGroup/SfGuardGroup_CreateEditForm');
include_partial('sfGuardGroup/SfGuardGroup_Grid');
?>
<script>
Ext.ns("SfGuardGroup");


SfGuardGroup.GridForm = function(config) {

    var groupsGrid = new SfGuardGroup.Grid(config.grid); //load config for grid....
    var createEdit = new SfGuardGroup.CreateEditForm({width:280,fieldsetConf:{defaults:{width:240}}});

    //on save fire grid store update event to store data in db
    createEdit.on('onSave',function(rec,data){
        var store = groupsGrid.getStore();

        var conn = new Ext.data.Connection({
            listeners:{
                // wait message.....
                beforerequest:function(){
                    Ext.MessageBox.show({
                        title: <?php echo json_encode(__('Please wait...')) ?>,
                        msg: <?php echo json_encode(__('Saving...')) ?>,
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
            url: 'sfGuardGroup/jsonUpdate',
            scope:this,
            params:rec.data,
            success: function(resp,opt) {
                var response = Ext.decode(resp.responseText);
                this.onSaveNext(data,response);
                store.reload();
                if(rec.data['id']==null) this.fireEvent('onAdd');
            },
            failure: function(resp,opt) {

                var response = Ext.util.JSON.decode(resp.responseText);
                Ext.ux.Logger.error(response['agent'],response['error']);

                Ext.Msg.show({
                    title: String.format(<?php echo json_encode(__('Error {0}')) ?>,response['agent']),
                    buttons: Ext.MessageBox.OK,
                    msg: response['info'],
                    icon: Ext.MessageBox.ERROR});
            }
        });//END Ajax request


    });

    groupsGrid.on({
        rowdblclick:function(g, index, ev){            
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
            groupsGrid.getStore().reload();
            createEdit.clean();
        }
    });

    Ext.apply(this,config);
    
    SfGuardGroup.GridForm.superclass.constructor.call(this, {        
        autoScroll:true,
        layout: 'border',        
        items: [{
                    region:'center'
                    ,layout:'fit'
                    ,margins: '3 3 3 3'
                    ,items: [groupsGrid]
                    
                }
                ,{
                    region:'east',
                    defaults:{autoScroll:true},
                    autoScroll:true
                    ,width:360
                    ,bodyStyle:'background:transparent;'
                    ,bodyBorder:false
                    ,margins: '3 3 3 3'
                    ,items:[createEdit]
                }
        ]
    });

    this.on('activate',function(){
        (function(){groupsGrid.store.reload();}).defer(100);
        (function(){createEdit.clean();}).defer(100);
        (function(){createEdit.reload();}).defer(100);
    });
}

// define public methods
Ext.extend(SfGuardGroup.GridForm, Ext.Panel, {});

</script>
