<?php
include_partial('sfGuardUser/SfGuardUser_GridForm');
include_partial('sfGuardGroup/SfGuardGroup_GridForm');
include_partial('sfGuardPermission/SfGuardPermission_GridForm');
?>
<script>

Ext.ns("SfGuardAuth");

SfGuardAuth.Main = function(config) {

    Ext.apply(this,config);
    
    // main panel
    var win = Ext.getCmp('sfguardAuth-main');
    var viewerSize = Ext.getBody().getViewSize();
    var windowHeight = viewerSize.height * 0.97;
    var windowWidth = viewerSize.width * 0.97;
    windowHeight = Ext.util.Format.round(windowHeight,0);
    windowHeight = (windowHeight > 600) ? 600 : windowHeight;

    windowWidth = Ext.util.Format.round(windowWidth,0);
    windowWidth = (windowWidth > 1000) ? 1000 : windowWidth;

    if(!win){

        //remove cookie if exists
        if(Ext.state.Manager.get('sfguardAuth-main')) Ext.state.Manager.clear('sfguardAuth-main');

        win = new Ext.Window({
            id: 'sfguardAuth-main',
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
                                 new SfGuardUser.GridForm({title: <?php echo json_encode(__('Manage Users')) ?>})
                                 ,new SfGuardGroup.GridForm({title: <?php echo json_encode(__('Manage Groups')) ?>,grid:{defaultId:<?php echo $defaultGroupID; ?>}})
                                 ,new SfGuardPermission.GridForm({title: <?php echo json_encode(__('Manage Permissions')) ?>})
                    ]
                })
            ]
//            ,listeners:{
//                show:function() {
//                    //this.loadMask = new Ext.LoadMask(this.body, {msg:'Loading. Please wait...'});
//                }
//            }
        });
        
        win.show();

    }else{
        
        win.setSize(windowWidth,windowHeight);
        win.center();
        win.show();
    }  


};

</script>