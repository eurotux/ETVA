<?php
include_partial('user/listGridForm',array('modules'=>$modules,'modulesConf'=>$modulesConf));
include_partial('sfGuardGroup/SfGuardGroup_GridForm');

?>

<script>
Ext.ns("User.List");

User.List.Main = function(config) {

    Ext.apply(this,config);
    
    // main panel
    var win = Ext.getCmp('User-List-Main-Window');
    var viewerSize = Ext.getBody().getViewSize();
    var windowHeight = viewerSize.height * 0.97;
    var windowWidth = viewerSize.width * 0.97;
    windowHeight = Ext.util.Format.round(windowHeight,0);
    windowHeight = (windowHeight > 500) ? 500 : windowHeight;

    windowWidth = Ext.util.Format.round(windowWidth,0);
    windowWidth = (windowWidth > 900) ? 900 : windowWidth;

    if(!win){

        //remove cookie if exists
        if(Ext.state.Manager.get('User-List-Main-Window')) Ext.state.Manager.clear('User-List-Main-Window');

        win = new Ext.Window({
            id: 'User-List-Main-Window',
	        title : this.title,
            modal:true,
            iconCls: 'icon-grid',
            width:windowWidth,
            height:windowHeight,
            closeAction:'hide',
            border:true,
            layout: 'border',
            items    : [
                new Ext.TabPanel({
                    region    : 'center',border:false,
                    margins   : '3 3 3 3',
                    activeTab : 0,
                    defaults  : {autoScroll : true},
                    items     : [
                        new User.List.GridForm({
                            region    : 'center',border:false,
                            margins   : '3 3 3 3',
                            title: <?php echo json_encode(__('Manage Users')) ?>
                        })
                        ,new SfGuardGroup.GridForm({title: <?php echo json_encode(__('Manage Groups')) ?>,grid:{defaultId:<?php echo $defaultGroupID; ?>}})
                    ]
                })
            ]
        });
        
        win.show();

    }else{
        win.setSize(windowWidth,windowHeight);
        win.center();
        win.show();
    }  
};

</script>
