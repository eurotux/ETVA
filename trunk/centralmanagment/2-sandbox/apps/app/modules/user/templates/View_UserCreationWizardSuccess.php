<?php if( isset($modulesConf['Primavera']) && ($modulesConf['Primavera']['main']['state']==1) ){
include_partial('primavera/Primavera_User_CreateEdit_Fieldset',array('modules'=>$modules,'modulesConf'=>$modulesConf));
}?>

<?php if( isset($modulesConf['ETMS']) && ($modulesConf['ETMS']['domain']['state']==1) ){
include_partial('etms/ETMS_User_CreateEdit_Fieldset',array('modules'=>$modules,'modulesConf'=>$modulesConf));
}?>

<?php if( isset($modulesConf['ETVOIP']) && ($modulesConf['ETVOIP']['pbx']['state']==1) ){ 
include_partial('etvoip/ETVOIP_User_CreateEdit_Fieldset',array('modules'=>$modules,'modulesConf'=>$modulesConf));
}?>

<script>

call_save_sfGuardUser_UpdateUserService =  function(userData){

    var conn = new Ext.data.Connection({
        listeners:{
            // wait message.....
            beforerequest:function(){
                Ext.MessageBox.show({
                    title: <?php echo json_encode(__('Please wait...')) ?>,
                    msg: <?php echo json_encode(__('Saving...')) ?>,
                    width:300,
                    wait:true,
                    modal: true
                });
            },// on request complete hide message
            requestcomplete:function(){Ext.MessageBox.hide();}
            ,requestexception:function(c,r,o){
                                    Ext.MessageBox.hide();
                                    Ext.Ajax.fireEvent('requestexception',c,r,o);}
        }
    });// end conn

    conn.request({
        url: 'sfGuardUser/jsonUpdateUserService',
        scope:this,
        params: userData,
        success: function(resp,opt) {
            var response = Ext.util.JSON.decode(resp.responseText);                

            Ext.ux.Logger.info(response['agent'],response['response']);
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
};

//Ext.ns("View.FirstTimeWizard");
Ext.ns("User.Create.Main");
User.Create.Main = function(config) {

    Ext.QuickTips.init();

    // Wizard window
    var viewerSize = Ext.getBody().getViewSize();
    var windowHeight = viewerSize.height * 0.95;
    windowHeight = Ext.util.Format.round(windowHeight,0);

    user_cardPanel = Ext.extend(Ext.ux.Wiz.Card, {

        initComponent: function(){
            Ext.apply(this, {

                title        : <?php echo json_encode(__('User details')) ?>,
                ref : 'user_wiz_cardpanel',
                monitorValid : true,
                defaults     : {
                    labelStyle : 'font-size:11px;width:150px;',
                    width: 180
                },
                items : [/*{
                            border    : false,
                            bodyStyle : 'background:none;padding-bottom:30px;',
                            html      : <?php echo json_encode(__('Bla bla bla')) ?>
                        },*/
                        new Ext.form.TextField({
                            fieldLabel: <?php echo json_encode(__('Username')) ?>,
                            name: 'username',
                            maxLength: 50,
                            allowBlank: false	    
                        }),
                        new Ext.form.TextField({
                            fieldLabel: <?php echo json_encode(__('First name')) ?>,
                            allowBlank: false,
                            name: 'firstName',        
                            maxLength: 10	    
                        }),
                        new Ext.form.TextField({
                            fieldLabel: <?php echo json_encode(__('Last name')) ?>,
                            allowBlank: false,
                            name: 'lastName',
                            maxLength: 50	    
                        }),
                        new Ext.form.TextField({	    
                            fieldLabel: 'Password',        
                            inputType: 'password',
                            name: 'password',
                            ref:'password',
                            allowBlank: false,
                            minLength: 4        
                        }),
                        new Ext.form.TextField({        
                            fieldLabel: <?php echo json_encode(__('Confirm New Password')) ?>,
                            inputType: 'password',
                            name: 'password_again',        
                            allowBlank: false,
                            validator:function(v){
                                if(!v) return true;
                                
                                if(v==this.ownerCt.password.getValue()) return true;
                                else return <?php echo json_encode(__('Passwords do not match')) ?>;
                            },
                            minLength: 4        
                        }),
                        new Ext.form.TextField({
                            fieldLabel: 'Email',
                            name: 'email',        
                            vtype:'email',
                            allowBlank: false,
                            blankText: <?php echo json_encode(__('Please provide correct email address')) ?>,
                            maxLength: 50	    
                        }),
                        new Ext.form.ComboBox({	           
                            selectOnFocus:true,
                            editable: false,        
                            mode: 'local',
                            value:true,
                            triggerAction: 'all',
                            name:'isActive',
                            hiddenName:'isActive',
                            fieldLabel: __('Active'),
                            xtype:'combo', 	    
                            allowBlank: false,
                            store: new Ext.data.ArrayStore({
                                fields: ['value','name'],
                                data: [[true, __('Yes')], [false, __('No')]]
                            }),
                            valueField: 'value',
                            displayField: 'name'
                        }),
                        new Ext.form.ComboBox({
                            selectOnFocus:true,
                            editable: false,
                            forceSelection:true,        
                            mode: 'local',
                            value:false,
                            triggerAction: 'all',
                            name:'isSuperAdmin',
                            hiddenName:'isSuperAdmin',
                            fieldLabel: <?php echo json_encode(__('Super Admin'))?>,
                            allowBlank: false,
                            store: new Ext.data.ArrayStore({
                                fields: ['value','name'],
                                data: [[true, __('Yes')], [false, __('No')]]
                            }),
                            valueField: 'value',
                            displayField: 'name'
                        }),
                        new Ext.ux.Multiselect({
                                fieldLabel: <?php echo json_encode(__('Groups')) ?>,
                                valueField:"Id",
                                displayField:"Name",
                                labelStyle:'margin-top:15px;',
                                style:{marginTop:'15px'},           
                                height:100,
                                name:'sf_guard_user_group_list',            
                                allowBlank:true,
                                store: new Ext.data.Store({
                                                    autoLoad: true,
                                                    proxy: new Ext.data.HttpProxy({
                                                        url: <?php echo json_encode(url_for('sfGuardGroup/jsonList')); ?>,
                                                        method:'POST'}),
                                                    reader: new Ext.data.JsonReader({
                                                        root: 'data',
                                                        fields:['Id','Name']})
                                        })
                        })
                ]
                ,listeners:{
                    show:function(){
                        this.form.findField('username').focus();
                    }
                    ,nextclick:function(card){
                        var wizData = wizard.getWizardData();
                        var cardData = wizData[card.getId()];

                        //... load record for other wiz panels 
                        if( this.wizard.cardPanel.etvoip_wiz_cardpanel )
                            this.wizard.cardPanel.etvoip_wiz_cardpanel.loadRecord(cardData);
                        if( this.wizard.cardPanel.etms_wiz_cardpanel )
                            this.wizard.cardPanel.etms_wiz_cardpanel.loadRecord(cardData);
                        if( this.wizard.cardPanel.primavera_wiz_cardpanel )
                            this.wizard.cardPanel.primavera_wiz_cardpanel.loadRecord(cardData);

                    }
                }
            });

            user_cardPanel.superclass.initComponent.call(this);
        }
    });

    permissions_cardPanel = Ext.extend(Ext.ux.Wiz.Card, {

        initComponent: function(){

            Ext.apply(this, {

                title        : <?php echo json_encode(__('User permissions')) ?>,
                monitorValid : true,
                defaults     : {
                    labelStyle : 'font-size:11px;width:150px;',
                },
                items : [/*{
                            border    : false,
                            bodyStyle : 'background:none;padding-bottom:30px;',
                            html      : <?php echo json_encode(__('Bla bla bla')) ?>
                        },*/
                        new Ext.ux.Multiselect({
                                    fieldLabel: <?php echo json_encode(__('Clusters')) ?>,
                                    valueField:"Id",
                                    displayField:"Name",
                                    height:100,
                                    name:'etva_permission_cluster_list',
                                    allowBlank:true,
                                    store: new Ext.data.Store({
                                                        autoLoad: true,
                                                        proxy: new Ext.data.HttpProxy({
                                                            url: <?php echo json_encode(url_for('cluster/jsonList')); ?>,
                                                            method:'POST'}),
                                                        reader: new Ext.data.JsonReader({
                                                            root: 'data',
                                                            fields:['Id','Name']})
                                        })
                                    })
                        ,new Ext.ux.Multiselect({
                                    fieldLabel: <?php echo json_encode(__('Servers')) ?>,
                                    valueField:"Id",
                                    displayField:"Name",
                                    height:100,
                                    name:'etva_permission_server_list',
                                    allowBlank:true,
                                    store:new Ext.data.Store({
                                                    autoLoad: true,
                                                    proxy: new Ext.data.HttpProxy({
                                                        url: <?php echo json_encode(url_for('server/jsonListAll')); ?>,
                                                        method:'POST'}),
                                                    reader: new Ext.data.JsonReader({
                                                        root: 'data',
                                                        fields:['Id','Name']})
                                                ,baseParams: { limit: 0, 'sort': 'name', 'dir': 'ASC' }
                                        })
                                    })

                ]
            });

            permissions_cardPanel.superclass.initComponent.call(this);
        }
    });

    <?php if( isset($modulesConf['ETVOIP']) && ($modulesConf['ETVOIP']['pbx']['state']==1) ){ ?>
    etvoip_cardPanel = Ext.extend(Ext.ux.Wiz.Card, {

        initComponent: function(){

            var service_id = parseInt('<?php echo $modulesConf['ETVOIP']['pbx']['service_id'] ?>');

            Ext.apply(this, {

                title        : <?php echo json_encode(__('User ETVOIP')) ?>,
                ref : 'etvoip_wiz_cardpanel',
                monitorValid : true,
                defaults     : {
                    labelStyle : 'font-size:11px;width:150px;',
                },
                items : [ ETVOIP.User.CreateEdit.Fieldset() ]
            });

            etvoip_cardPanel.superclass.initComponent.call(this);
        }
        ,loadRecord: function(data){
            var newdata = data;
            newdata['name'] = data['firstName'] + ' ' + data['lastName'];
            newdata['devinfo_secret'] = data['password'];
            (this.getForm()).loadRecord( { 'data': newdata } );
        }
    });
    <?php }?>
    <?php if( isset($modulesConf['ETMS']) && ($modulesConf['ETMS']['domain']['state']==1) ){ ?>
    etms_cardPanel = Ext.extend(Ext.ux.Wiz.Card, {

        initComponent: function(){

            var domain_service_id = parseInt('<?php echo $modulesConf['ETMS']['domain']['service_id'] ?>');
            var mailbox_service_id = parseInt('<?php echo $modulesConf['ETMS']['mailbox']['service_id'] ?>');

            Ext.apply(this, {

                title        : <?php echo json_encode(__('User ETMailServer')) ?>,
                monitorValid : true,
                ref : 'etms_wiz_cardpanel',
                defaults     : {
                    labelStyle : 'font-size:11px;width:150px;',
                },
                items : [ ETMS.User.CreateEdit.Fieldset() ]
            });

            etms_cardPanel.superclass.initComponent.call(this);
        }
        ,loadRecord: function(data){
            var newdata = data;
            newdata['user_name'] = data['username'];
            newdata['real_name'] = data['firstName'] + ' ' + data['lastName'];
            (this.getForm()).loadRecord( { 'data': newdata } );
        }
    });
    <?php } ?>
    <?php if( isset($modulesConf['Primavera']) && ($modulesConf['Primavera']['main']['state']==1) ){ ?>
    primavera_cardPanel = Ext.extend(Ext.ux.Wiz.Card, {

        initComponent: function(){

            var service_id = parseInt('<?php echo $modulesConf['Primavera']['main']['service_id'] ?>');

            Ext.apply(this, {

                title        : <?php echo json_encode(__('User Primavera')) ?>,
                monitorValid : true,
                ref : 'primavera_wiz_cardpanel',
                defaults     : {
                    labelStyle : 'font-size:11px;width:150px;',
                },
                items : [ Primavera.User.CreateEdit.Fieldset() ]
            });

            primavera_cardPanel.superclass.initComponent.call(this);
        }
        ,loadRecord: function(data){
            var newdata = data;
            newdata['cod'] = data['username'];
            newdata['Nome'] = data['firstName'] + ' ' + data['lastName'];
            newdata['Email'] = data['email'];
            //newdata['verpassword'] = data['password'];
            newdata['verpassword'] = '';
            newdata['password'] = '';
            (this.getForm()).loadRecord( { 'data': newdata } );
        }
    });
    <?php } ?>

    var cards = [
        // card with welcome message
        new Ext.ux.Wiz.Card({
            title : <?php echo json_encode(__('Welcome')) ?>,
            defaults     : {
                labelStyle : 'font-size:11px;width:140px;'
            },
            items : [{
                    border    : false,
                    bodyStyle : 'background:none;',
                    html      : <?php echo json_encode(__('Welcome to the user creation utility.<br>Follow the steps to create a new user.')) ?>
            }]
        })
        ,new user_cardPanel({id:'ft_wiz_user'})
        ,new permissions_cardPanel({id:'ft_wiz_permissions'})
        <?php if( isset($modulesConf['ETVOIP']) && ($modulesConf['ETVOIP']['pbx']['state']==1) ){ ?>
        ,new etvoip_cardPanel({id:'ft_wiz_etvoip'})
        <?php }?>
        <?php if( isset($modulesConf['ETMS']) && ($modulesConf['ETMS']['domain']['state']==1) ){ ?>
        ,new etms_cardPanel({id:'ft_wiz_etms'})
        <?php }?>
        <?php if( isset($modulesConf['Primavera']) && ($modulesConf['Primavera']['main']['state']==1) ){ ?>
        ,new primavera_cardPanel({id:'ft_wiz_primavera'})
        <?php }?>

        /*
         * add each card for service
         */
        ,new Ext.ux.Wiz.Card({
            title        : <?php echo json_encode(__('Finished!')) ?>,
            monitorValid : true,
            items : [{
                border    : false,
                bodyStyle : 'background:none;',
                html      : <?php echo json_encode(__('Thank you! Your data has been collected.<br>When you click on the "Finish" button, new user will be created.')) ?>
             }]
        })
    ];

    var wizard = new Ext.ux.Wiz({
        border:true,
        title : <?php echo json_encode(__('User creation wizard')) ?>,
        tools:[{id:'help', qtip: __('Help'),handler:function(){View.showHelp({anchorid:'help-userCreationWz-main',autoLoad:{ params:'mod=usercreate'},title: <?php echo json_encode(__('User creation wizard Help')) ?>});}}],
        headerConfig : {
            title : <?php echo json_encode(__('User create')) ?>
        },
        width:610,
        height:520,
        westConfig : {
            width : 185
        },
        cardPanelConfig : {
            defaults : {
                baseCls    : 'x-small-editor',
                bodyStyle  : 'border:none;padding:15px 15px 15px 15px;background-color:#F6F6F6;',
                border     : false
            }
        },
        cards: cards
        ,listeners: {
            finish: function() {
                this.onSave(this.getWizardData());
            }
        }
        ,onSave: function(data){
            // save user
            this.onSave_sfGuardUser(data);
            this.fireEvent('onSave');
        }
        ,onSave_Next: function(user,data){

            // save permissions
            this.onSave_sfGuardPermission( user,data);

            <?php if( isset($modulesConf['ETVOIP']) && ($modulesConf['ETVOIP']['pbx']['state']==1) ){ ?>
            if( data['ft_wiz_etvoip']['user_list-createedit-tab-etvoip-checkbox'] ){
                var alldata_etvoip = data['ft_wiz_etvoip'];
                alldata_etvoip['action'] = alldata_etvoip['etvoip-action'];
                alldata_etvoip['user_id'] = user['user_id'];
                ETVOIP.User.CreateEdit.onSave(alldata_etvoip);
            }
            <?php }?>
            <?php if( isset($modulesConf['ETMS']) && ($modulesConf['ETMS']['domain']['state']==1) ){ ?>
            if( data['ft_wiz_etms']['user_list-createedit-tab-etms-checkbox'] ){
                var alldata_etms = data['ft_wiz_etms'];
                alldata_etms['action'] = alldata_etms['etms-action'];
                alldata_etms['user_id'] = user['user_id'];
                ETMS.User.CreateEdit.onSave(alldata_etms);
            }
            <?php }?>
            <?php if( isset($modulesConf['Primavera']) && ($modulesConf['Primavera']['main']['state']==1) ){ ?>
            if( data['ft_wiz_primavera']['user_list-createedit-tab-primavera-checkbox'] ){
                var alldata_primavera = data['ft_wiz_primavera'];
                alldata_primavera['action'] = alldata_primavera['primavera-action'];
                alldata_primavera['user_id'] = user['user_id'];
                Primavera.User.CreateEdit.onSave(alldata_primavera);
            }
            <?php }?>

            this.fireEvent('onSave');
        }
        ,call_save_sfGuardPermission: function(permsData){
            var conn = new Ext.data.Connection({
                listeners:{
                    // wait message.....
                    beforerequest:function(){
                        Ext.MessageBox.show({
                            title: <?php echo json_encode(__('Please wait...')) ?>,
                            msg: <?php echo json_encode(__('Saving...')) ?>,
                            width:300,
                            wait:true,
                            modal: true
                        });
                    },// on request complete hide message
                    requestcomplete:function(){Ext.MessageBox.hide();}
                    ,requestexception:function(c,r,o){
                                            Ext.MessageBox.hide();
                                            Ext.Ajax.fireEvent('requestexception',c,r,o);}
                }
            });// end conn

            conn.request({
                url: ' sfGuardPermission/jsonAddUserPermissions',
                scope:this,
                params: permsData,
                success: function(resp,opt) {
                    var response = Ext.util.JSON.decode(resp.responseText);                

                    Ext.ux.Logger.info(response['agent'],response['response']);
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
        }
        ,onSave_sfGuardPermission: function(user,data){
            if( data['ft_wiz_permissions']['etva_permission_cluster_list'] ){
                var userPermData_admin = new Object();
                var perms_admin_list = [];

                userPermData_admin['user_id'] = user['user_id'];
                userPermData_admin['level'] = 'cluster';
                userPermData_admin['permtype'] = 'admin';

                if( data['ft_wiz_permissions']['etva_permission_cluster_list'] ){
                    var perms_cluster_list = data['ft_wiz_permissions']['etva_permission_cluster_list'].split(',');
                    for(var i=0,len=perms_cluster_list.length; i<len;i++)
                        perms_admin_list.push(parseInt(perms_cluster_list[i]));
                }
                userPermData_admin['etva_permission_list'] = Ext.encode(perms_admin_list);
                if( perms_admin_list.length > 0 ){
                    this.call_save_sfGuardPermission(userPermData_admin);
                }
            }
            if( data['ft_wiz_permissions']['etva_permission_server_list'] ){
                var userPermData_op = new Object();
                var perms_op_list = [];

                userPermData_op['user_id'] = user['user_id'];
                userPermData_op['level'] = 'server';
                userPermData_op['permtype'] = 'op';

                if( data['ft_wiz_permissions']['etva_permission_server_list'] ){
                    var perms_server_list = data['ft_wiz_permissions']['etva_permission_server_list'].split(',');
                    for(var i=0,len=perms_server_list.length; i<len;i++)
                        perms_op_list.push(parseInt(perms_server_list[i]));
                }
                userPermData_op['etva_permission_list'] = Ext.encode(perms_op_list);
                if( perms_op_list.length > 0 ){
                    this.call_save_sfGuardPermission(userPermData_op);
                }
            }
        }
        ,onSave_sfGuardUser: function(data){
            //console.log(data);

            var userData = new Object();
            var groups_to_numbers = [];

            userData['username'] = data['ft_wiz_user']['username'];
            if(data['ft_wiz_user']['password'])
                userData['password'] = data['ft_wiz_user']['password'];
            if(data['ft_wiz_user']['password_again'])
                userData['password_again'] = data['ft_wiz_user']['password_again'];
            userData['first_name'] = data['ft_wiz_user']['firstName'];
            userData['last_name'] = data['ft_wiz_user']['lastName'];
            userData['email'] = data['ft_wiz_user']['email'];
            userData['is_active'] = data['ft_wiz_user']['isActive'];
            userData['is_super_admin'] = data['ft_wiz_user']['isSuperAdmin'];

            if( data['ft_wiz_user']['sf_guard_user_group_list'] ){
                var groups = data['ft_wiz_user']['sf_guard_user_group_list'].split(',');
                for(var i=0,len=groups.length; i<len;i++)
                    groups_to_numbers.push(parseInt(groups[i]));
            }
            userData['sf_guard_user_group_list'] = Ext.encode(groups_to_numbers);

            var conn = new Ext.data.Connection({
                listeners:{
                    // wait message.....
                    beforerequest:function(){
                        Ext.MessageBox.show({
                            title: <?php echo json_encode(__('Please wait...')) ?>,
                            msg: <?php echo json_encode(__('Saving...')) ?>,
                            width:300,
                            wait:true,
                            modal: true
                        });
                    },// on request complete hide message
                    requestcomplete:function(){Ext.MessageBox.hide();}
                    ,requestexception:function(c,r,o){
                                            Ext.MessageBox.hide();
                                            Ext.Ajax.fireEvent('requestexception',c,r,o);}
                }
            });// end conn

            conn.request({
                url: 'sfGuardUser/jsonUpdate',
                scope:this,
                params: userData,
                success: function(resp,opt) {
                    var response = Ext.util.JSON.decode(resp.responseText);                

                    Ext.ux.Logger.info(response['agent'],response['response']);

                    var new_user = new Object();
                    new_user['user_id'] = response['user_id'];

                    this.onSave_Next(new_user,data);
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
        }
    });

    this.on = function(data) {
        wizard.on( data );
    };
    
    Ext.apply(wizard,config);

    // show the wizard
    wizard.show();
}

</script>
