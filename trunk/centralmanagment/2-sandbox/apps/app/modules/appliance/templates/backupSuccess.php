<script>
Ext.ns('Appliance.Backup');

Appliance.Backup.Form = Ext.extend(Ext.form.FormPanel, {
    labelWidth:80,
    border:false
    ,bodyStyle:'padding:10px;'
    ,initComponent:function() {

        var config = {
            monitorValid:true,
            items:[
                {xtype:'displayfield',hideLabel:true,
                    value: String.format(<?php echo json_encode(__('An appliance backup will save all {0} settings. <br> If a virtual machine as an agent running, actual configuration/settings of that service will also be saved.<br><br>IT DOES NOT BACKUP DISK DATA!!! ONLY CURRENT CONFIGURATION!')) ?>,'<?php echo sfConfig::get('config_acronym'); ?>')
                }
                ,{
                    xtype:'button',text: <?php echo json_encode(__('BACKUP NOW!')) ?>,
                    ref:'backup_now',
                    scope:this,
                    handler:function(){this.save();}
                }                
            ]
        };

        this.buttons = [
                        {
                            text: __('Cancel'),
                            scope:this,
                            handler:function(){
                                this.fireEvent('onCancel');
                            }
                        }];

        // apply config
        Ext.apply(this, Ext.apply(this.initialConfig, config));

        // call parent
        Appliance.Backup.Form.superclass.initComponent.apply(this, arguments);


    } // eo function initComponent
    ,disableBackup:function(disable){
        
        this.backup_now.setDisabled(disable);
        
    }
    ,onRender:function() {
        // call parent
        Appliance.Backup.Form.superclass.onRender.apply(this, arguments);

        // set wait message target
        this.getForm().waitMsgTarget = this.getEl();


    } // eo function onRender
    ,loadData:function(){

        /*
         * on form load check if user already register appliance.....(has serial number)
         * if not ask for register....
         */

        this.load({
            url:this.url,
            waitMsg: <?php echo json_encode(__('Retrieving data...')) ?>,            
            failure:function(form,action){               

                var resp = Ext.decode(action.response.responseText);                

                if(resp.action && resp.action=='need_register'){
                    Ext.MessageBox.show({
                                title: <?php echo json_encode(__('Appliance not registered')) ?>,
                                width:300,
                                msg: <?php echo json_encode(__('Need to register Appliance first before creating backup.<br><br>Register now?')) ?>,
                                buttons: Ext.MessageBox.YESNO,
                                fn: function(btn){
                                    if(btn=='yes') this.fireEvent('needRegister');
                                },
                                scope:this,
                                icon: Ext.MessageBox.WARNING
                            });
                }
                else this.disable();
                
                
            },
            success: function ( form, action ) {
            },scope:this
        });

    }
    ,getFocusField:function(){                
        return this.backup_now;
    }
    ,save:function(params){
        
        this.disableBackup(true);

        var progressbar = new Ext.ProgressBar({text:<?php echo json_encode(__('Initializing...')) ?>});
        var progress_msg = <?php echo json_encode(__('% completed...')) ?>;

        /*
         * run task ever 5 seconds...check upload progress
         */
        var task = {run:function(){

                            Ext.Ajax.request({
                                url:<?php echo json_encode(url_for('appliance/jsonBackup'))?>,
                                scope:this,
                                params:{method:'get_backup_progress'},
                                success: function(r) {
                                            var resp = Ext.decode(r.responseText);
                                            var action = resp.action;
                                            
                                            if(resp.action == <?php echo json_encode(Appliance::UPLOAD_BACKUP) ?>){
                                                var i = resp.percent;                                                
                                                progressbar.updateProgress(i, Math.round(100*i)+progress_msg);
                                                if(i==1) Ext.TaskMgr.stop(task);
                                            }
                                            else{

                                                var txt = '';
                                                if(resp.action == <?php echo json_encode(Appliance::MA_BACKUP) ?> && resp.down != 'false'){
                                                    txt = resp.ma+' '+String.format(<?php echo json_encode(__('{0} downloaded...')) ?>,Ext.util.Format.fileSize(resp.down));
                                                }
                                                else{
                                                    txt = resp.txt;
                                                    if(txt) progressbar.updateText(txt);
                                                }
                                            }             
                                }
                            });

            },interval: 5000
        };
             

        var delay_notification =
                new Ext.util.DelayedTask(function(){
                                        Ext.MessageBox.hide();
                                        View.notify({
                                            border:false,
                                            items:[
                                                {bodyStyle:'background:transparent;',html:<?php echo json_encode(__('Appliance Backup')) ?>},
                                                progressbar
                                            ],
                                            pinState: 'pin',
                                            task:task
                                        });

                                        Ext.TaskMgr.start(task);
        });
        delay_notification.delay(1000); // start notiy progress task in 1 seconds...


        var send_data = new Object();

        if(params) send_data = params;
        send_data['method'] = 'backup';                

        var conn = new Ext.data.Connection({
            listeners:{
                // wait message.....
                beforerequest:function(){
                    Ext.MessageBox.show({
                        title: <?php echo json_encode(__('Please wait...')) ?>,
                        msg: <?php echo json_encode(__('Preparing backup configuration...')) ?>,
                        width:300,
                        wait:true,
                        modal: false
                    });
                },// on request complete hide message
                requestcomplete:function(){Ext.MessageBox.hide();}
                ,requestexception:function(c,r,o){
                    Ext.Ajax.fireEvent('requestexception',c,r,o);}
            }
        });// end conn

        conn.request({
            url: this.url,
            params:send_data,
            // everything ok...
            failure:function(response){
                var resp = Ext.decode(response.responseText);

                this.disableBackup(false);
                delay_notification.cancel();
                Ext.TaskMgr.stop(task);

                if(resp['action']==<?php echo json_encode(Appliance::MA_BACKUP) ?>)
                {

                    Ext.MessageBox.show({
                        title: <?php echo json_encode(__('Server(s) backup')) ?>,
                        msg: String.format('{0}<br><br>{1}<br><br>{2}'
                                ,<?php echo json_encode(__('Some virtual machines agents reported down. If you proceed agent configuration WILL NOT BE SAVED IN BACKUP.')) ?>
                                ,resp['info']
                                ,<?php echo json_encode(__('Are you sure you want to do this?')) ?>),
                        buttons: Ext.MessageBox.YESNO,
                        fn: function(btn){

                            if(btn=='yes') this.save({force:true});

                        },
                        scope:this,
                        icon: Ext.MessageBox.WARNING
                    });
                    

                }else{


                    if(resp['txt'])
                        progressbar.updateText(resp['txt']);
                  
                    Ext.MessageBox.show({
                        title: String.format(<?php echo json_encode(__('Error {0}')) ?>,resp['agent']),
                        msg: String.format(<?php echo json_encode(__('Could not perform {0} BACKUP.<br> {1}')) ?>,'<?php echo sfConfig::get('config_acronym'); ?>',resp['info']),
                        buttons: Ext.MessageBox.OK,
                        icon: Ext.MessageBox.ERROR
                    });
                }

            }
            ,success: function(resp,opt){
                this.disableBackup(false);
                progressbar.updateProgress(1, Math.round(100)+progress_msg);
                Ext.TaskMgr.stop(task);
                var response = Ext.util.JSON.decode(resp.responseText);
                var msg = String.format(<?php echo json_encode(__('Appliance Backup success!')) ?>);
                View.notify({html:msg});

                if(response['errors'])
                    Ext.MessageBox.show({
                            title: String.format(<?php echo json_encode(__('Error {0}')) ?>,resp['agent']),
                            msg: String.format(<?php echo json_encode(__('Some errors occurred.<br> {0}')) ?>,response['errors']),
                            buttons: Ext.MessageBox.OK,
                            icon: Ext.MessageBox.ERROR
                    });


                this.fireEvent('onSave');


            },scope:this
        });// END Ajax request

    }

}); // eo extend


Appliance.Backup.Main = function(config) {

    Ext.apply(this, config);
    
    var backup_form = new Appliance.Backup.Form({url:<?php echo json_encode(url_for('appliance/jsonBackup'))?>});
    backup_form.on({
        'needRegister':{scope:this,fn:function(){this.fireEvent('showRegister');}}
        ,'onCancel':{scope:this,fn:function(){this.close();}}
    });
    
        
    this.items = backup_form;
        

    Appliance.Backup.Main.superclass.constructor.call(this, {
        layout: 'fit',
        iconCls: 'icon-etva',
        maxW:400,
        modal:true,
        maxH:200,
        defaultButton: backup_form.getFocusField(),
        border:false
    });

    this.on({
        'show':function(){            
                this.resizeFunc();
                this.items.get(0).loadData();
        },
        'close':function(){
                Ext.EventManager.removeResizeListener(this.resizeFunc,this);
        }
    });

    //on browser resize, resize window
    Ext.EventManager.onWindowResize(this.resizeFunc,this);

};
Ext.extend(Appliance.Backup.Main, Ext.Window,{
    tools:[{id:'help', qtip: __('Help'),handler:function(){View.showHelp({anchorid:'help-appliance-backup',autoLoad:{ params:'mod=appliance'},title: <?php echo json_encode(__('Appliance Backup Help')) ?>});}}]
});

</script>
