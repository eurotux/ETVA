<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

?>

<script>
Ext.ns('ETMS.SERVER');

function dump(arr,level) {
    var dumped_text = "";
    if(!level) level = 0;

    //The padding given at the beginning of the line.
    var level_padding = "";
    for(var j=0;j<level+1;j++) level_padding += "    ";

    if(typeof(arr) == 'object') { //Array/Hashes/Objects
            for(var item in arr) {
                    var value = arr[item];

                    if(typeof(value) == 'object') { //If it is an array,
                            dumped_text += level_padding + "'" + item + "' ...\n";
                            dumped_text += dump(value,level+1);
                    } else {
                            dumped_text += level_padding + "'" + item + "' => \"" + value + "\"\n";
                    }
            }
    } else { //Stings/Chars/Numbers etc.
            dumped_text = "===>"+arr+"<===("+typeof(arr)+")";
    }
    return dumped_text;
}


ETMS.SERVER = Ext.extend(Ext.Panel,{
    title: <?php echo json_encode(__('Service')) ?>,
    layout:'fit',
    defaults:{border:false},
    initComponent:function(){
        /*
        *  Server Info
        *
        */
        this.items = [
        {
            scope:this,
            anchor: '100% 100%',
            layout: {
                type: 'hbox',
                align: 'stretch'  // Child items are stretched to full width
            }
            ,defaults:{layout:'form',autoScroll:true, bodyStyle:'padding:10px;',border:false}
            ,items:[
                    {
                        scope:this,
                        flex:1,
                        defaultType:'displayfield',
                        items:[
                            {
                                name: 'state',
                                fieldLabel : <?php echo json_encode(__('State')) ?>,
                                ref: '../state'
                            },
                            {
                                name: 'time',
                                fieldLabel : <?php echo json_encode(__('Time (seconds)')) ?>,
                                ref: '../time'
                            },
                            {
                                name: 'nr_domains',
                                fieldLabel : <?php echo json_encode(__('Domains')) ?>,
                                ref: '../nr_domains'
                            }
                            ,{
                                name: 'nr_mailboxes',
                                fieldLabel : <?php echo json_encode(__('Mailboxes')) ?>,
                                ref: '../nr_mailboxes'
                            },
                            {
                                xtype:'button',
                                iconCls: 'x-tbar-loading',
                                name:'bt_reload',
                                fieldLabel : <?php echo json_encode(__('Refresh')) ?>,
                                scope:this,
                                handler: this.serverInfo
                                //,width:50
                            }
                            , new ETMS.SERVER.space({service_id:this.service['id']})
////                            ,{
//                                xtype: 'server_space'
//                            }
                        ]//end items flex
                    }
                    ,{
                        flex:1,
                        defaultType:'button',
                        scope:this,
                        items:[
                            {
                                name: 'bt_start',
                                icon: 'images/silk/icons/arrow_up.png',
                                scope:this,
                                fieldLabel : <?php echo json_encode(__('Start')) ?>,
                                handler:this.startServer
                                //,width:50
                            },
                            {
                                name: 'bt_stop',
                                icon: 'images/silk/icons/arrow_down.png',
                                fieldLabel : <?php echo json_encode(__('Stop')) ?>,
                                scope:this,
                                handler:this.stopServer
                                //,width:50
                            },
                            {
                                name: 'bt_restart',
                                icon: 'images/silk/icons/arrow_rotate_anticlockwise.png',
                                fieldLabel : <?php echo json_encode(__('Restart')) ?>,
                                scope:this,
                                handler:this.restartServer
                                //,width:50
                            },
                            {
                                name: 'bt_kill',
                                icon: 'images/silk/icons/stop.png',
                                fieldLabel : <?php echo json_encode(__('Force stop')) ?>,
                                scope:this,
                                handler:this.killServer
                                //,width:50
                            }
                        ]
                    }
                    ,{
                        flex:1,
                        scope:this,
                        defaultType:'button',
                        items:[
                            {
                                name: 'bt_restore',
                                //icon: 'images/icons/up2.gif',
                                icon: 'images/silk/icons/server_uncompressed.png',
                                fieldLabel : <?php echo json_encode(__('Restore')) ?>,
                                scope:this,
                                handler:this.restoreServer
                                //,width:50
                            }
                            ,{
                                name: 'bt_backup',
                                //icon: 'images/icons/down2.gif',
                                icon: 'images/silk/icons/server_compressed.png',
                                fieldLabel : <?php echo json_encode(__('Backup')) ?>,
                                scope:this,
                                handler:this.backupServer
                                //,width:50
                            }
                        ]
                    }
            ]
        }];

        this.serverInfo();


        ETMS.SERVER.superclass.initComponent.call(this);

        //faz reload ao componente
        this.on({
            'activate':function(){
                if(this.items.length>0){
                  for(var i=0,len=this.items.length;i<len;i++){
                      var item = this.items.get(i);
                      item.fireEvent('reload');
                  }
                }
            }
        });

        
    }
    ,doCall: function(method){
        Ext.Ajax.request({
            url:<?php echo json_encode(url_for('etms/json'))?>,
            scope:this,
            params: {
                    id: this.service['id'],
                    method: method
                    //,params: Ext.encode({'server':gridAlias.selectedDomain, 'alias':[sel.get('alias')]})
            },
            success: function(resp,opt) {
                    var decoded_data = Ext.decode(resp.responseText);
                    var statusObj = decoded_data['value'][0];

                    //this.reloadState(statusObj);
                    var vars = this.get(0);
                    if(statusObj.UPTIME == undefined){
                        if (method == 'server_backup' || method == 'server_restore'){
                            Ext.Msg.alert(<?php echo json_encode(__('Warning')) ?>,
                                <?php echo json_encode(__('Done!')) ?>);
                        }
                        return;
                    }
                    vars.time.setValue(statusObj.UPTIME);
                    vars.state.setValue(statusObj.STATE);
                    vars.nr_domains.setValue(statusObj.NRDOMAINS);
                    vars.nr_mailboxes.setValue(statusObj.NRMAILBOXES)

                    if(statusObj.INITLOG != ""){
                        var a = new ETMS.SERVER.Initiator({service_id:this.service['id']});
                        a.setMessage(statusObj.INITLOG);
                        a.show();
                    }
                    //vars.state.setValue()
                    //    gridAlias.getStore().remove(sel);
                    //sel.commit();
            },
            failure: function(resp,opt) {
                    Ext.Msg.alert(<?php echo json_encode(__('Warning')) ?>,
                    <?php echo json_encode(__('Error reloading data!')) ?>);
                    //sel.reject();
            }

        });

    }
    ,serverInfo: function(){
        this.doCall('server_info');
    }
    ,killServer: function(){
        this.doCall('server_kill');
    }
    ,restartServer: function(){
        this.doCall('server_restart');
    }
    ,stopServer: function(){
        this.doCall('server_stop');
    }
    ,startServer: function(){
        this.doCall('server_start');
    }
    ,backupServer: function(){
        this.doCall('server_backup');
    }
    ,restoreServer: function(){
        this.doCall('server_restore');
    }
});


ETMS.SERVER.Initiator = Ext.extend(Ext.Window,{           //mudar para window -> new
    //border:false,
    //defaults:{border:false},
    title: <?php echo json_encode(__('First Time Initiation')) ?>
    ,width: 800
    ,height: 580
    ,closeAction: 'hide'
    ,layout: 'fit'
    ,resizable: false
    ,initComponent: function(){
        
        this.items = [
        {
            scope:this,
            anchor: '100% 100%',
            layout: {
                type: 'vbox',
                align: 'stretch'  // Child items are stretched to full width
            }
            
            ,defaults:{autoScroll:true, bodyStyle:'padding:10px;',border:false, pack:'center'}
            ,items:[
                    {
                        scope:this,
                        height:50,
                        defaultType:'displayfield',
                        items:[{
                            html: <?php echo json_encode(__('<h2>We detect that this is the first time that ETMS is initialized.
                            Some initial configurations has been made. Check the log for more information.</h2>')) ?>//
                        }]//end items flex
                    }
                    ,{
                        flex:1,
                        scope:this,
                        border:true
                        ,title: 'Log'
                        ,items:[{
                            xtype: 'textarea'
                            ,name: 'message'
                            ,ref: '../../message'
                            ,disabled: true
                            ,width: 750
                            ,height: 410
                            ,style: {
                                color: 'blue'
                            }
                        }]
                    }
            ]
            ,buttons: [{
                text: <?php echo json_encode(__('Erase and Close'))?>,
                handler: function(){
                  this.ignoreMessage();
                },
                scope: this
            },{
                text: __('Close'),
                handler: function(){
                  this.hide();
                },
                scope: this
            }]
        }];

        ETMS.SERVER.Initiator.superclass.initComponent.call(this);
    }
    ,setMessage: function(text){
        this.message.setValue(text);
    }
    ,ignoreMessage: function(){
//        alert(this.service_id);

        Ext.Ajax.request({
            url:<?php echo json_encode(url_for('etms/json'))?>,
            scope:this,
            params: {
                    id: this.service_id,
                    method: 'remove_initLog'
            },
            success: function(resp,opt) {
                    var decoded_data = Ext.decode(resp.responseText);
                    var statusObj = decoded_data['value'][0];
                    Ext.ux.Logger.info(statusObj[0]);
                    this.hide();
            },
            failure: function(resp,opt) {
                    var decoded_data = Ext.decode(resp.responseText);
                    var statusObj = decoded_data['value'][0];
                    Ext.Msg.alert(<?php echo json_encode(__('Warning')) ?>,
                    statusObj[0]);
            }

        });
    }
});
</script>