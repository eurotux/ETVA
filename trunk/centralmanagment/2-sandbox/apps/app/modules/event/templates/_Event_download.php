<script>
Ext.ns('Event.Diagnostic');

Event.Diagnostic.Form = Ext.extend(Ext.form.FormPanel, {
    labelWidth:80,   
    border:false
    ,bodyStyle:'padding:10px;'
    ,initComponent:function() {
        var securityOptions = new Ext.data.ArrayStore({
            fields: ['type', 'security_name'],
            data : [['noencryption','No encryption'],['sslencription','SSL encryption']]
        });
        

        var config = {
            monitorValid:true,     
            scope:this,
            items:[{
                    xtype:'displayfield',
                    hideLabel:true,
                    value: <?php echo json_encode(__("<p>The diagnostic process aggreates all information required for debug, such as logs and current configuration.</p><p>If some nodes are running, their's information will also be included.</p><p>At last, an email is sent to Eurotux with the data.</p>")) ?>
                 },{
                    xtype:'fieldset',title: <?php echo json_encode(__('SMTP Server')) ?>,
                      checkboxToggle: false,
                      items:[{                     
                          xtype: 'textfield',
                          scope:this,
                          hideLabel: false,
                          fieldLabel: <?php echo json_encode(__('Address')) ?>,
                          ref:'../smtp_addr',
                          width: 200,
                          cansend:false,
//                          vtype: 'email',
                          allowBlank: false
                          ,listeners:{
//                              invalid:function(){
//                                  this.ownerCt.items.get(4).disable();
//                              },valid:function(){
//                                  if(this.cansend == true){
//                                      this.ownerCt.items.get(4).enable();
//                                  }
//                              }
                          }
                     },{                     
                          xtype: 'textfield',
                          scope:this,
                          hideLabel: false,
                          fieldLabel: <?php echo json_encode(__('Port')) ?>,
                          ref:'../smtp_port',
                          width: 30,
                          cansend:false,
//                          vtype: 'email',
                          allowBlank: false
                          ,listeners:{
//                              invalid:function(){
//                                  this.ownerCt.items.get(4).disable();
//                              },valid:function(){
//                                  if(this.cansend == true){
//                                      this.ownerCt.items.get(4).enable();
//                                  }
//                              }
                          }
                     },{
                            xtype:'checkbox', 
                            name:'auth_needed',
                            ref:'../auth_needed',
//                            boxLabel: <?php echo json_encode(__('Use default keymap')) ?>,
                            fieldLabel: <?php echo json_encode(__('Server requires authentication')) ?>,
                            listeners:{
                               'check':{
                                    scope:this,
                                    fn:function(cbox,ck){
                                        this.smtp_user.setDisabled(!ck);
                                        this.smtp_password.setDisabled(!ck);
                                    }
                                }
                            }
                        }]
                    }
                    ,{
                        layout: 'hbox',
                        border: false,
                        scope:this,
                        layoutConfig: {
                            padding: '0 0 15 0'
                        },
                        items: [{
                              xtype:'fieldset',
                              title: <?php echo json_encode(__('Authentication')) ?>,
//                            checkboxToggle: true,
                              collapsed:false,
                              items:[
                              {                     
                                  xtype: 'textfield',
                                  scope:this,
                                  hideLabel: false,
                                  disabled: true,
                                  fieldLabel: <?php echo json_encode(__('Username')) ?>,
                                  ref:'../../smtp_user',
                                  width: 200,
                                  cansend:false,
//                                  vtype: 'email',
                                  allowBlank: false
                                  ,listeners:{
                                  }
                              },{                     
                                  xtype: 'textfield',
                                  scope:this,
                                  hideLabel: false,
                                  disabled: true,
                                  fieldLabel: <?php echo json_encode(__('Password')) ?>,
                                  inputType: 'password',
                                  ref:'../../smtp_password',
                                  width: 200,
                                  cansend:false,
 //                                 vtype: 'email',
                                  allowBlank: false
                                  ,listeners:{
                                  }
                              }
                              ]
                        }
                        ,{
                            xtype: 'spacer',
                            width: 15                        
                        }
                        ,{
                              xtype:'fieldset',
                              title: <?php echo json_encode(__('Security')) ?>,
//                              checkboxToggle: true,
                              items:[{
                                    xtype: 'combo',
                                    scope:this,
                                    hiddenName: 'secHidden',    
                                    fieldLabel: <?php echo json_encode(__('User secure connection')) ?>,        
                                    mode: 'local',
                                    store: securityOptions,     //The store provides the data 
                                    displayField:'security_name',    //Campo que aparece
                                    valueField:'type',
                                    ref:'../../security_type',
                                    width: 138,
                                    listeners: {
                                        select: function(field, rec, selIndex){     //Evento select. Rec -> registo
    //                                    if (selIndex == 0){
    //                                        Ext.Msg.prompt('New Genre', 'Name', Ext.emptyFn);   
    //                                    }
                                        }
                                    }
                              }]
                        }]
                },{
                    xtype:'fieldset',title: <?php echo json_encode(__('')) ?>,
                      checkboxToggle: false,
                      items:[{
                            xtype: 'spacer',
                            width: 6                        
                      },{
                            xtype: 'displayfield',
                            ref:'../status_label',
                            fieldLabel: <?php echo json_encode(__('Status')) ?>,
//                            width: 195
                      }
                    ]
                 }
        ]};

        this.buttons = [{
                            text: <?php echo json_encode(__('Generate  file')) ?>,
                            scope:this,
                            handler:function(){
                                this.save();
                                //this.fireEvent('onCancel');
                            }
//                        },{

//                            xtype:'button',
//                            ref:'../diagnostic_now',
//                            width: 75,
//                            scope:this,
//                            handler:function(){ this.save(); }
                        },{
                            xtype:'button',
                            disabled: true,
                            width: 75,
                            text: <?php echo json_encode(__('Download file')) ?>,
                            handler:function(){
                                self.location = <?php echo json_encode(url_for('event/logDownload'))?>;
                            }
                        },{
                            text: __('Cancel'),
                            scope:this,
                            handler:function(){
                                this.fireEvent('onCancel');
                            }
                        }];

        // apply config
        Ext.apply(this, Ext.apply(this.initialConfig, config));
        this.getSMTPConf();

        // call parent
        Event.Diagnostic.Form.superclass.initComponent.apply(this, arguments);


    } // eo function initComponent
    ,disableDownload:function(disable){
//        this.download_btn.setDisabled(disable);
        this.buttons[1].setDisabled(disable);
    }
//    ,canSendEmail:function(bool){
//        this.email_field.cansend = bool;
//        if(bool == true){
//            this.email_field.fireEvent('valid');
//        }
//    }
    ,setStatusLable:function(str){
        this.status_label.setValue(str);
        this.status_label.setVisible(true);
    }
    ,disableDiagnostic:function(disable){
//      this.diagnostic_now.setDisabled(disable);
        this.buttons[0].setDisabled(disable);
    }
    ,onRender:function() {
        // call parent
        Event.Diagnostic.Form.superclass.onRender.apply(this, arguments);

        // set wait message target
        //this.getForm().waitMsgTarget = this.getEl();


    } // eo function onRender
    ,getFocusField:function(){                
//        return this.diagnostic_now;
        return this.smtp_addr;
    }
    ,getSMTPConf:function(){

        var conn = new Ext.data.Connection({
            listeners:{
                // wait message.....
                beforerequest:function(){
                    Ext.MessageBox.show({
                        title: <?php echo json_encode(__('Please wait...')) ?>,
                        msg: <?php echo json_encode(__('Generating diagnostic file')) ?>,
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
            url: <?php echo json_encode(url_for('event/jsonGetSMTPConf'))?>,
            //params:send_data,
            // everything ok...
            failure:function(response){
            }
            ,success: function(r,opt){
                var resp = Ext.decode(r.responseText);
                this.smtp_addr.setValue(resp.addr);
                this.smtp_port.setValue(resp.port);
                this.smtp_user.setValue(resp.username);
                this.smtp_password.setValue(resp.key);
                this.security_type.setValue(resp.security);
                this.auth_needed.setValue(resp.useauth);
            },scope:this
        });// END Ajax request

    }
//    ,sendLogToMail:function(params){
//        var email_v = this.email_field.getValue();
//        this.send_email.disable();
//        Ext.Ajax.request({
//            url:<?php echo json_encode(url_for('event/jsonSendmailLog'))?>,
//            scope:this,
//            params:{email: email_v},
//            success: function(r) {              
//                var resp = Ext.decode(r.responseText);
//                var msg = '';
//                if(resp.success == true){
//                    msg = <?php echo json_encode(__('Email send successfully')) ?>;
//
//                }else{
//                    msg = resp.error;
//                }
//                this.setStatusLable(msg);
//                View.notify({html:msg});
//                this.send_email.enable();
//            }
//        });
//    }
    ,save:function(params){
        
        this.disableDiagnostic(true);
        this.disableDownload(true);
        this.setStatusLable(<?php echo json_encode(__('Generating diagnostic file')) ?>);

        var progressbar = new Ext.ProgressBar({text:<?php echo json_encode(__('Initializing...')) ?>});
        var progress_msg = <?php echo json_encode(__('% completed...')) ?>;



        /*
         * run task ever 5 seconds...check upload progress
         */
        var task = {run:function(){

                            Ext.Ajax.request({
                                url:<?php echo json_encode(url_for('event/jsonDiagnostic'))?>,
                                scope:this,
                                params:{method:'get_diagnostic_progress'},
                                success: function(r) {
                                            var resp = Ext.decode(r.responseText);
                                            var action = resp.action;
                                            
                                            if(resp.action == <?php echo json_encode(Appliance::UPLOAD_BACKUP) ?>){
                                                var i = resp.percent;                                                
                                                //progressbar.updateProgress(i, Math.round(100*i)+progress_msg);
                                                progressbar.updateProgress(i, Math.round(50)+progress_msg);

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
                                                {bodyStyle:'background:transparent;',html:<?php echo json_encode(__('Generating diagnostic file')) ?>},
                                                progressbar
                                            ],
                                            pinState: 'pin',
                                            task:task
                                        });

                                        Ext.TaskMgr.start(task);
        });
        delay_notification.delay(1000); // start notiy progress task in 1 seconds...


        var send_data = new Object();

        //Gather data
        if(params) send_data = params;
        send_data['method'] = 'diagnostic';                
        send_data['smtpserver'] = this.smtp_addr.getValue();
        send_data['port'] = this.smtp_port.getValue();
        send_data['useauth'] = (this.auth_needed.getValue())?'1':'0';
        send_data['username'] = this.smtp_user.getValue();
        send_data['key'] = this.smtp_password.getValue();
        send_data['security_type'] = this.security_type.getValue();

        var conn = new Ext.data.Connection({
            listeners:{
                // wait message.....
                beforerequest:function(){
                    Ext.MessageBox.show({
                        title: <?php echo json_encode(__('Please wait...')) ?>,
                        msg: <?php echo json_encode(__('Generating diagnostic file')) ?>,
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
                this.disableDownload(true);
//                this.canSendEmail(false);
                this.disableDiagnostic(false);
                this.setStatusLable(<?php echo json_encode(__('Error')) ?>);
                delay_notification.cancel();
                Ext.TaskMgr.stop(task);

                if(resp['action']==<?php echo json_encode(Appliance::MA_BACKUP) ?>)
                {

                    Ext.MessageBox.show({
                        title: <?php echo json_encode(__('Server(s) diagnostic')) ?>,
                        msg: String.format('{0}<br><br>{1}<br><br>{2}'
                                ,<?php echo json_encode(__('Some virtual machines agents reported down. If you proceed agent configuration WILL NOT BE SAVED IN BACKUP.')) ?>
                                ,resp['info']
                                ,<?php echo json_encode(__('Are you sure you want to do this?')) ?>),
                        buttons: Ext.MessageBox.YESNOCANCEL,
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
                        msg: String.format(<?php echo json_encode(__('Could not perform '.sfConfig::get('config_acronym').' BACKUP.<br> {0}')) ?>,resp['info']),
                        buttons: Ext.MessageBox.OK,
                        icon: Ext.MessageBox.ERROR
                    });
                }

            }
            ,success: function(resp,opt){
                this.disableDiagnostic(false);
//                this.canSendEmail(true);
                this.disableDownload(false);
    
                
//                this.setStatusLable(<?php echo json_encode(__('File generated successfully')) ?>);
                progressbar.updateText('');
                progressbar.updateProgress(1, Math.round(100)+progress_msg);
                Ext.TaskMgr.stop(task);
                var response = Ext.util.JSON.decode(resp.responseText);

                if(response['success']){
                    var msg = String.format(<?php echo json_encode(__('File generated successfully')) ?>);
                    View.notify({html:msg});
    //                var msg = <?php echo json_encode(__('File generated successfully')) ?>;
                    if(response['mail_errors']){
                        msg += ". ";
                        msg += <?php echo json_encode(__('However, the email message could not be sent.')) ?>;
                    }
                }else{
                    var msg = <?php echo json_encode(__('Could not generate diagnostic file. Please contact the support for further information.')) ?>
                }
                this.setStatusLable(msg);

                if(response['errors'] || response['mail_errors']){
                    var ms = response['errors'] + "\n" + response['mail_errors'];
                    Ext.MessageBox.show({
                            title: String.format(<?php echo json_encode(__('Error {0}')) ?>,resp['agent']),
                            msg: String.format(<?php echo json_encode(__('Some errors occurred.<br> {0}')) ?>, ms),
                            buttons: Ext.MessageBox.OK,
                            icon: Ext.MessageBox.ERROR
                    });
//                }else{
//                    if(response['info']){
//                        Ext.MessageBox.show({
//                                title: String.format(<?php echo json_encode(__('Warning {0}')) ?>,resp['agent']),
//                                msg: String.format(<?php echo json_encode(__('Some virtualization agents are not running.<br> {0}')) ?>,response['info']),
//                                buttons: Ext.MessageBox.OK,
//                                icon: Ext.MessageBox.WARNING
//                        });
//                    }
                }

                this.fireEvent('onSave');


            },scope:this
        });// END Ajax request

    }

}); // eo extend


Event.Diagnostic.Main = function(config) {

    Ext.apply(this, config);
    
    var diagnostic_form = new Event.Diagnostic.Form({url:<?php echo json_encode(url_for('event/jsonDiagnostic'))?>});
    diagnostic_form.on({
        'onCancel':{scope:this,fn:function(){this.close();}}
    });
    
        
    this.items = diagnostic_form;

    Event.Diagnostic.Main.superclass.constructor.call(this, {
        layout: 'fit',
        iconCls: 'icon-etva',
        maxW:600,
        modal:true,
        //maxH:400,
        maxH:430,
        defaultButton: diagnostic_form.getFocusField(),
        border:false
    });

    this.on({
        'show':function(){            
                this.resizeFunc();
        },
        'close':function(){
                Ext.EventManager.removeResizeListener(this.resizeFunc,this);
        }
    });

    //on browser resize, resize window
    Ext.EventManager.onWindowResize(this.resizeFunc,this);

};
Ext.extend(Event.Diagnostic.Main, Ext.Window,{
    tools:[{id:'help', qtip: __('Help'),handler:function(){View.showHelp({anchorid:'help-event-diagnose',autoLoad:{ params:'mod=event'},title: <?php echo json_encode(__('Event Diagnostic Help')) ?>});}}]
});






//        var securityOptions = new Ext.data.Store({
//            reader: new Ext.data.JsonReader({
//                fields: ['id', 'name'],
//                root: 'rows'
//            }),
//            proxy: new Ext.data.HttpProxy({
//                url: 'php/service.php'
//            }),
//            autoLoad: true
//        });



//                ,{
//                    layout: 'hbox',
//                    border: false,
//                    scope:this,
//                    layoutConfig: {
//                        padding: '10 20 10'
//                    },
//                    items: [
//                        {
//                            xtype:'displayfield',
//                            hideLabel:true,
//                            value: 'Email:'
//                        },{
//                            xtype: 'spacer',
//                            width: 15                        
//                        },{
//                            xtype: 'textfield',
//                            scope:this,
//                            hideLabel: false,
//                            fieldLabel: <?php echo json_encode(__('Email')) ?>,
//                            ref:'../email_field',
//                            width: 200,
//                            cansend:false,
//                            vtype: 'email',
//                            allowBlank: false
//                            ,listeners:{
//                                invalid:function(){
//                                    this.ownerCt.items.get(4).disable();
//                                },valid:function(){
//                                    if(this.cansend == true){
//                                        this.ownerCt.items.get(4).enable();
//                                    }
//                                }
//                            }
//                        },{
//                            xtype: 'spacer',
//                            width: 30
//                        },{
//                            xtype:'button',
//                            ref:'../send_email',
//                            name:'send_mail_btn',
//                            disabled: true,
//                            width: 75,
//                            text: <?php echo json_encode(__('Send')) ?>
//                            ,scope:this
//                            ,handler:function(){
//                                this.sendLogToMail();
//                            }
//                    }]
//                }
</script>


