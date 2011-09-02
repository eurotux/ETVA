<?php
include_partial('event/Event_GridFilter');
?>

<script>

Ext.ns("Event");

Event.Main = function(config) {

    Ext.apply(this,config);
    // main panel
    var win = Ext.getCmp('event-main');
    var viewerSize = Ext.getBody().getViewSize();
    var windowHeight = viewerSize.height * 0.97;
    var windowWidth = viewerSize.width * 0.97;
    windowHeight = Ext.util.Format.round(windowHeight,0);
    windowHeight = (windowHeight > 600) ? 600 : windowHeight;

    windowWidth = Ext.util.Format.round(windowWidth,0);
    windowWidth = (windowWidth > 1000) ? 1000 : windowWidth;

    if(!win){
        var logs = new Event.GridFilter();


        //remove cookie if exists
        if(Ext.state.Manager.get('event-main')) Ext.state.Manager.clear('event-main');
       
        win = new Ext.Window({
            id: 'event-main',
	        title    : this.title,
            modal:true,
            iconCls: 'icon-grid',
            width:windowWidth,
            height:windowHeight,
            closeAction:'hide',
            border:true,
            layout:'fit',
            items:logs
            ,tools:[{id:'help', qtip: __('Help'),handler:function(){View.showHelp({anchorid:'help-event-main',autoLoad:{ params:'mod=event'},title: <?php echo json_encode(__('System events log Help')) ?>});}}]
            ,listeners:{
                show:function() {
                    logs.reload();
                    //this.loadMask = new Ext.LoadMask(this.body, {msg:'Loading. Please wait...'});
                    //this.loadMask.show();                    
                }
            }
        });

        win.show();

    }else{

        win.setSize(windowWidth,windowHeight);
        win.center();
        win.show();
    }


};

</script>