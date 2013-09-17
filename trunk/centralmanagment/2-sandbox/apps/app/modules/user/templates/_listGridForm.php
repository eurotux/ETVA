<?php
include_partial('user/createEditForm',array('modules'=>$modules,'modulesConf'=>$modulesConf));
include_partial('user/listGrid',array('modules'=>$modules,'modulesConf'=>$modulesConf));
?>
<script>
Ext.ns("User.List");


User.List.GridForm = function(config) {

    var usersGrid = new User.List.Grid({
                        'createEdit': function(recid){
                            var createEditWindow = Ext.getCmp('User-List-CreateEditForm');
                            if( !createEditWindow ){
                                createEditWindow = new User.List.CreateEditForm.Window({'id': 'User-List-CreateEditForm'});

                                createEditWindow.on({
                                    'onSave':function(){
                                        this.close();
                                        usersGrid.fireEvent('onUpdate');
                                    }
                                });
                            }
                            if( recid )
                                createEditWindow.loadData(recid);
                            createEditWindow.show();
                        }
                    });
    usersGrid.on({
        rowdblclick:function(g, index, ev){            
            var rec = g.store.getAt(index);
            this.createEdit(rec.id);
        }
        ,onUpdate:function(){
            console.log('usersGrid fire onUpdate');
            usersGrid.getStore().reload();
        }
        ,onAdd: function(){
            this.createEdit();
        }
        // on grid remove item (after remove)
        ,onRemove:function(){
            usersGrid.getStore().reload();
        }
    });

    Ext.apply(this,config);
    
    User.List.GridForm.superclass.constructor.call(this, {                        
        autoScroll:true,
        layout: 'border',        
        items: [{
                    region:'center'
                    ,layout:'fit'
                    ,margins: '3 3 3 3'
                    ,items: [usersGrid]
                    
                }
        ]
    });

    
    this.on('activate',function(){
            (function(){usersGrid.store.reload();}).defer(100);
    });

    

}
// define public methods
Ext.extend(User.List.GridForm, Ext.Panel, {});

</script>
