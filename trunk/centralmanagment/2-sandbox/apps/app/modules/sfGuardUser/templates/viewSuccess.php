<?php
include_partial('sfGuardUser/SfGuardUser_GridForm');
?>
<script>
SfGuardUser.Main = function(app) {

    // main panel

    var win = Ext.getCmp('sfguardUser-main');
    var viewerSize = Ext.getBody().getViewSize();
    var windowHeight = viewerSize.height * 0.96;
    var windowWidth = viewerSize.width * 0.96;
    windowHeight = Ext.util.Format.round(windowHeight,0);
    windowHeight = (windowHeight > 600) ? 600 : windowHeight;

    windowWidth = Ext.util.Format.round(windowWidth,0);
    windowWidth = (windowWidth > 900) ? 900 : windowWidth;


    if(!win){
        var centerPanel = new SfGuardUser.GridForm();

         win = new Ext.Window({
            id: 'sfguardUser-main',
	        title: 'User Management',
            modal:true,
            iconCls: 'icon-grid',
            width:windowWidth,
            height:windowHeight,
            closeAction:'hide',
            border:false,
            layout: 'fit',

            //defaults:{autoScroll:true},
            items: [centerPanel]
        });
        
        win.show();


    }else{

        win.setSize(windowWidth,windowHeight);
        win.center();
        win.show();

    }
};

new SfGuardUser.Main();
</script>