<script>

Ext.ns('ETFS.EditShare');

ETFS.EditShare.Form = new Ext.extend( Ext.form.FormPanel, {

    monitorValid:true,
    border: false,
    labelWidth: 140,
    defaults: { border:false },
    initComponent:function(){
        var service_id = this.service_id;

        var store_list_users = new Ext.data.JsonStore({
            proxy:new Ext.data.HttpProxy({url:<?php echo json_encode(url_for('etfs/json'))?>}),
            baseParams:{ id:service_id,method:'list_users'},
            root: 'data',
            fields: [            
               {name: 'uid'},
               {name: 'name'}
            ]
            ,autoLoad: true
        });
        var store_list_groups = new Ext.data.JsonStore({
            proxy:new Ext.data.HttpProxy({url:<?php echo json_encode(url_for('etfs/json'))?>}),
            baseParams:{ id:service_id,method:'list_groups'},
            root: 'data',
            fields: [            
               {name: 'gid'},
               {name: 'name'}
            ]
            ,autoLoad: true
        });

        this.editshare_fieldset_folder = {
                                    id: 'etfs-edit-file-share-fieldset',
                                    items: [
                                            { fieldLabel:__('Share name'),
                                              name: 'name',
                                              allowBlank: false,
                                              xtype:'textfield' },
                                            { fieldLabel:__('Directory to share'),
                                              name: 'path',
                                              //allowBlank: false,
                                              xtype:'textfield' },
                                            { fieldLabel:__('Available?'),
                                                ref: 'rbgsharefolderavailable', 
                                                xtype: 'radiogroup',
                                                items: [
                                                    { boxLabel:__('Yes'),
                                                        name: 'available',
                                                        checked: true,
                                                        inputValue: 'yes' },
                                                    { boxLabel:__('No'),
                                                        name: 'available',
                                                        inputValue: 'no' },
                                                ]
                                            },
                                            { fieldLabel:__('Browseable?'),
                                                ref: 'rbgsharefolderbrowseable', 
                                                xtype: 'radiogroup',
                                                items: [
                                                    { boxLabel:__('Yes'),
                                                        name: 'browseable',
                                                        inputValue: 'yes' },
                                                    { boxLabel:__('No'),
                                                        name: 'browseable',
                                                        checked: true,
                                                        inputValue: 'no' }
                                                ]
                                            },
                                            { fieldLabel:__('Share Comment'),
                                              name: 'comment',
                                              //allowBlank: false,
                                              xtype:'textfield' },
                                            { fieldLabel:__('Writable?'),
                                                ref: 'rbgsharefolderwritable', 
                                                xtype: 'radiogroup',
                                                items: [
                                                    { boxLabel:__('Yes'),
                                                        name: 'writable',
                                                        inputValue: 'yes' },
                                                    { boxLabel:__('No'),
                                                        name: 'writable',
                                                        checked: true,
                                                        inputValue: 'no' }
                                                ]
                                            },
                                            { fieldLabel:__('Guest Access?'),
                                                ref: 'rbgsharefolderguest', 
                                                xtype: 'radiogroup',
                                                items: [
                                                    { boxLabel:__('None'),
                                                        name: 'guest',
                                                        checked: true,
                                                        inputValue: 'none' },
                                                    { boxLabel:__('Yes'),
                                                        name: 'guest',
                                                        inputValue: 'yes' },
                                                    { boxLabel:__('Guest only'),
                                                        name: 'guest',
                                                        inputValue: 'guest_only' }
                                                ]
                                            },
                                            { fieldLabel: __('Read only users/groups'),
                                                ref: 'readlistFieldset',
                                                xtype: 'fieldset',
                                                width: '100%',
                                                border: false,
                                                collapsible: true,
                                                collapsed: true,
                                                items: [
                                                    {
                                                        layout:'column',
                                                        border: false,
                                                        defaults:{ layout:'form', border: false, labelAlign: 'top' },
                                                        items: [
                                                            {
                                                                columnWidth: .5,
                                                                items: [
                                                                    new Ext.ux.Multiselect({
                                                                        name: 'read_list_u',
                                                                        store: store_list_users,
                                                                        //fieldLabel: __('Read only users'),
                                                                        valueField: 'name',
                                                                        displayField: 'name',
                                                                        allowBlank: true
                                                                    }),
                                                                ]
                                                            },
                                                            {
                                                                columnWidth: .5,
                                                                items: [
                                                                    new Ext.ux.Multiselect({
                                                                        name: 'read_list_g',
                                                                        store: store_list_groups,
                                                                        //fieldLabel: __('Read only groups'),
                                                                        valueField: 'name',
                                                                        displayField: 'name',
                                                                        allowBlank: true
                                                                    })
                                                                ]
                                                            }
                                                        ]
                                                    }
                                                ]
                                            },
                                            { fieldLabel: __('Read/Write users/groups'),
                                                ref: 'writelistFieldset',
                                                xtype: 'fieldset',
                                                width: '100%',
                                                border: false,
                                                collapsible: true,
                                                collapsed: true,
                                                items: [
                                                    {
                                                        layout:'column',
                                                        border: false,
                                                        defaults:{ layout:'form', border: false, labelAlign: 'top' },
                                                        items: [
                                                            {
                                                                columnWidth: .5,
                                                                items: [
                                                                    new Ext.ux.Multiselect({
                                                                        name: 'write_list_u',
                                                                        store: store_list_users,
                                                                        //fieldLabel: __('Read/Write users'),
                                                                        valueField: 'name',
                                                                        displayField: 'name',
                                                                        allowBlank: true
                                                                    }),
                                                                ]
                                                            },
                                                            {
                                                                columnWidth: .5,
                                                                items: [
                                                                    new Ext.ux.Multiselect({
                                                                        name: 'write_list_g',
                                                                        store: store_list_groups,
                                                                        //fieldLabel: __('Read/Write groups'),
                                                                        valueField: 'name',
                                                                        displayField: 'name',
                                                                        allowBlank: true
                                                                    })
                                                                ]
                                                            }
                                                        ]
                                                    }
                                                ]
                                            },
                                            { fieldLabel: __('Invalid users/groups'),
                                                ref: 'invalidusersFieldset',
                                                xtype: 'fieldset',
                                                width: '100%',
                                                border: false,
                                                collapsible: true,
                                                collapsed: true,
                                                items: [
                                                    {
                                                        layout:'column',
                                                        border: false,
                                                        defaults:{ layout:'form', border: false, labelAlign: 'top' },
                                                        items: [
                                                            {
                                                                columnWidth: .5,
                                                                items: [
                                                                    new Ext.ux.Multiselect({
                                                                        name: 'invalid_users_u',
                                                                        store: store_list_users,
                                                                        //fieldLabel: __('Invalid users'),
                                                                        valueField: 'name',
                                                                        displayField: 'name',
                                                                        allowBlank: true
                                                                    }),
                                                                ]
                                                            },
                                                            {
                                                                columnWidth: .5,
                                                                items: [
                                                                    new Ext.ux.Multiselect({
                                                                        name: 'invalid_users_g',
                                                                        store: store_list_groups,
                                                                        //fieldLabel: __('Invalid groups'),
                                                                        valueField: 'name',
                                                                        displayField: 'name',
                                                                        allowBlank: true
                                                                    })
                                                                ]
                                                            }
                                                        ]
                                                    }
                                                ]
                                            },
                                    ]
        };
        this.editshare_fieldset_global = {
                                    id: 'etfs-edit-global-config-fieldset',
                                    items: [
                                            {xtype:'hidden',name:'name'},
                                            { fieldLabel:__('Workgroup'),
                                              name: 'workgroup',
                                              allowBlank: false,
                                              xtype:'textfield' },
                                            { fieldLabel:__('WINS mode'),
                                                border: false,
                                                layout: 'column',
                                                items: [
                                                    { boxLabel:__('Be WINS server'),
                                                        xtype: 'radio',
                                                        name: 'wins',
                                                        inputValue: '1' },
                                                    { boxLabel:__('Use server'),
                                                        xtype: 'radio',
                                                        name: 'wins',
                                                        inputValue: '2' },
                                                    { name: 'wins server',
                                                        xtype: 'textfield' },
                                                    { boxLabel:__('Neither'),
                                                        xtype: 'radio',
                                                        name: 'wins',
                                                        checked: true,
                                                        inputValue: '0' }
                                                ]
                                            },
                                            { fieldLabel:__('Server description'),
                                              name: 'server_string',
                                              //allowBlank: false,
                                              xtype:'textfield' },
                                            { fieldLabel:__('Server name'),
                                              name: 'netbios_name',
                                              //allowBlank: false,
                                              xtype:'textfield' },
                                            { fieldLabel:__('Server aliases'),
                                              name: 'netbios_aliases',
                                              //allowBlank: false,
                                              xtype:'textfield' },
                                            { fieldLabel:__('Master browser?'),
                                                xtype: 'radiogroup',
                                                items: [
                                                    { boxLabel:__('Yes'),
                                                        name: 'preferred_master',
                                                        inputValue: 'yes' },
                                                    { boxLabel:__('No'),
                                                        name: 'preferred_master',
                                                        inputValue: 'no' },
                                                    { boxLabel:__('Automatic'),
                                                        name: 'preferred_master',
                                                        checked: true,
                                                        inputValue: '' },
                                                ]
                                            },
                                            {
                                                xtype: 'combo',
                                                triggerAction: 'all',
                                                mode: 'local',
                                                editable: false,
                                                name: 'security',
                                                hiddenName:'security',
                                                store: new Ext.data.ArrayStore({
                                                    fields: ['value','name'],
                                                    data: [['', __('Default')], 
                                                            ['share', __('Share level')],
                                                            ['user', __('User level')],
                                                            ['server', __('Password server')],
                                                            ['domain', __('Domain')],
                                                            ['ads', __('Active directory')]]
                                                }),
                                                fieldLabel: __('Security'),
                                                valueField: 'value',
                                                displayField: 'name',
                                                allowBlank: true
                                            },
                                            { fieldLabel:__('Password server'),
                                              name: 'password_server',
                                              //allowBlank: false,
                                              xtype:'textfield' },
                                            { fieldLabel:__('Enable Winbind for local accounts?'),
                                                xtype: 'radiogroup',
                                                items: [
                                                    { boxLabel:__('Yes'),
                                                        name: 'winbind_enable_local_accounts',
                                                        checked: true,
                                                        inputValue: 'yes' },
                                                    { boxLabel:__('No'),
                                                        name: 'winbind_enable_local_accounts',
                                                        inputValue: 'no' },
                                                ]
                                            },
                                            { fieldLabel:__('Enable Winbind for local accounts?'),
                                                xtype: 'radiogroup',
                                                items: [
                                                    { boxLabel:__('Yes'),
                                                        name: 'winbind_trusted_domains_only',
                                                        inputValue: 'yes' },
                                                    { boxLabel:__('No'),
                                                        checked: true,
                                                        name: 'winbind_trusted_domains_only',
                                                        inputValue: 'no' },
                                                ]
                                            },
                                            { fieldLabel:__('Disallow listing of users?'),
                                                xtype: 'radiogroup',
                                                items: [
                                                    { boxLabel:__('Yes'),
                                                        name: 'winbind_enum_users',
                                                        checked: true,
                                                        inputValue: 'yes' },
                                                    { boxLabel:__('No'),
                                                        name: 'winbind_enum_users',
                                                        inputValue: 'no' },
                                                ]
                                            },
                                            { fieldLabel:__('Disallow listing of groups?'),
                                                xtype: 'radiogroup',
                                                items: [
                                                    { boxLabel:__('Yes'),
                                                        name: 'winbind_enum_groups',
                                                        checked: true,
                                                        inputValue: 'yes' },
                                                    { boxLabel:__('No'),
                                                        name: 'winbind_enum_groups',
                                                        inputValue: 'no' },
                                                ]
                                            },
                                            { fieldLabel:__('Always use default domain? '),
                                                xtype: 'radiogroup',
                                                items: [
                                                    { boxLabel:__('Yes'),
                                                        name: 'winbind_use_default_domain',
                                                        checked: true,
                                                        inputValue: 'yes' },
                                                    { boxLabel:__('No'),
                                                        name: 'winbind_use_default_domain',
                                                        inputValue: 'no' },
                                                ]
                                            },
                                            { fieldLabel:__('Kerberos realm on domain server'),
                                              name: 'realm',
                                              //allowBlank: false,
                                              xtype:'textfield' },
                                            { fieldLabel:__('Seconds to cache user details for'),
                                              name: 'winbind_cache_time',
                                              //allowBlank: false,
                                              xtype:'textfield' },
                                            { fieldLabel:__('Range of UIDs for Windows users'),
                                              name: 'idmap_uid',
                                              //allowBlank: false,
                                              xtype:'textfield' },
                                            { fieldLabel:__('Range of GIDs for Windows groups'),
                                              name: 'idmap_gid',
                                              //allowBlank: false,
                                              xtype:'textfield' },
                                    ]
        };
        this.editshare_fieldset_printer = {
                                    id: 'etfs-edit-printer-share-fieldset',
                                    items: [
                                            { xtype:'hidden', name:'printable', value: 'yes' },
                                            { fieldLabel:__('Share name'),
                                              name: 'name',
                                              allowBlank: false,
                                              xtype:'textfield' },
                                            { fieldLabel:__('Unix Printer'),
                                              name: 'printer',
                                              //allowBlank: false,
                                              xtype:'textfield' },
                                            { fieldLabel:__('Spool directory'),
                                              name: 'path',
                                              //allowBlank: false,
                                              xtype:'textfield' },
                                            { fieldLabel:__('Available?'),
                                                ref: 'rbgsharefolderavailable', 
                                                xtype: 'radiogroup',
                                                items: [
                                                    { boxLabel:__('Yes'),
                                                        name: 'available',
                                                        checked: true,
                                                        inputValue: 'yes' },
                                                    { boxLabel:__('No'),
                                                        name: 'available',
                                                        inputValue: 'no' },
                                                ]
                                            },
                                            { fieldLabel:__('Browseable?'),
                                                ref: 'rbgsharefolderbrowseable', 
                                                xtype: 'radiogroup',
                                                items: [
                                                    { boxLabel:__('Yes'),
                                                        name: 'browseable',
                                                        inputValue: 'yes' },
                                                    { boxLabel:__('No'),
                                                        name: 'browseable',
                                                        checked: true,
                                                        inputValue: 'no' }
                                                ]
                                            },
                                            { fieldLabel:__('Share Comment'),
                                              name: 'comment',
                                              //allowBlank: false,
                                              xtype:'textfield' },
                                            { fieldLabel:__('Writable?'),
                                                ref: 'rbgsharefolderwritable', 
                                                xtype: 'radiogroup',
                                                items: [
                                                    { boxLabel:__('Yes'),
                                                        name: 'writable',
                                                        inputValue: 'yes' },
                                                    { boxLabel:__('No'),
                                                        name: 'writable',
                                                        checked: true,
                                                        inputValue: 'no' }
                                                ]
                                            },
                                            { fieldLabel:__('Guest Access?'),
                                                ref: 'rbgsharefolderguest', 
                                                xtype: 'radiogroup',
                                                items: [
                                                    { boxLabel:__('None'),
                                                        name: 'guest',
                                                        checked: true,
                                                        inputValue: 'none' },
                                                    { boxLabel:__('Yes'),
                                                        name: 'guest',
                                                        inputValue: 'yes' },
                                                    { boxLabel:__('Guest only'),
                                                        name: 'guest',
                                                        inputValue: 'guest_only' }
                                                ]
                                            },
                                            { fieldLabel: __('Read only users/groups'),
                                                ref: 'readlistFieldset',
                                                xtype: 'fieldset',
                                                width: '100%',
                                                border: false,
                                                collapsible: true,
                                                collapsed: true,
                                                items: [
                                                    {
                                                        layout:'column',
                                                        border: false,
                                                        defaults:{ layout:'form', border: false, labelAlign: 'top' },
                                                        items: [
                                                            {
                                                                columnWidth: .5,
                                                                items: [
                                                                    new Ext.ux.Multiselect({
                                                                        name: 'read_list_u',
                                                                        store: store_list_users,
                                                                        //fieldLabel: __('Read only users'),
                                                                        valueField: 'name',
                                                                        displayField: 'name',
                                                                        allowBlank: true
                                                                    }),
                                                                ]
                                                            },
                                                            {
                                                                columnWidth: .5,
                                                                items: [
                                                                    new Ext.ux.Multiselect({
                                                                        name: 'read_list_g',
                                                                        store: store_list_groups,
                                                                        //fieldLabel: __('Read only groups'),
                                                                        valueField: 'name',
                                                                        displayField: 'name',
                                                                        allowBlank: true
                                                                    })
                                                                ]
                                                            }
                                                        ]
                                                    }
                                                ]
                                            },
                                            { fieldLabel: __('Read/Write users/groups'),
                                                ref: 'writelistFieldset',
                                                xtype: 'fieldset',
                                                width: '100%',
                                                border: false,
                                                collapsible: true,
                                                collapsed: true,
                                                items: [
                                                    {
                                                        layout:'column',
                                                        border: false,
                                                        defaults:{ layout:'form', border: false, labelAlign: 'top' },
                                                        items: [
                                                            {
                                                                columnWidth: .5,
                                                                items: [
                                                                    new Ext.ux.Multiselect({
                                                                        name: 'write_list_u',
                                                                        store: store_list_users,
                                                                        //fieldLabel: __('Read/Write users'),
                                                                        valueField: 'name',
                                                                        displayField: 'name',
                                                                        allowBlank: true
                                                                    }),
                                                                ]
                                                            },
                                                            {
                                                                columnWidth: .5,
                                                                items: [
                                                                    new Ext.ux.Multiselect({
                                                                        name: 'write_list_g',
                                                                        store: store_list_groups,
                                                                        //fieldLabel: __('Read/Write groups'),
                                                                        valueField: 'name',
                                                                        displayField: 'name',
                                                                        allowBlank: true
                                                                    })
                                                                ]
                                                            }
                                                        ]
                                                    }
                                                ]
                                            },
                                            { fieldLabel: __('Invalid users/groups'),
                                                ref: 'invalidusersFieldset',
                                                xtype: 'fieldset',
                                                width: '100%',
                                                border: false,
                                                collapsible: true,
                                                collapsed: true,
                                                items: [
                                                    {
                                                        layout:'column',
                                                        border: false,
                                                        defaults:{ layout:'form', border: false, labelAlign: 'top' },
                                                        items: [
                                                            {
                                                                columnWidth: .5,
                                                                items: [
                                                                    new Ext.ux.Multiselect({
                                                                        name: 'invalid_users_u',
                                                                        store: store_list_users,
                                                                        //fieldLabel: __('Invalid users'),
                                                                        valueField: 'name',
                                                                        displayField: 'name',
                                                                        allowBlank: true
                                                                    }),
                                                                ]
                                                            },
                                                            {
                                                                columnWidth: .5,
                                                                items: [
                                                                    new Ext.ux.Multiselect({
                                                                        name: 'invalid_groups_g',
                                                                        store: store_list_groups,
                                                                        //fieldLabel: __('Invalid groups'),
                                                                        valueField: 'name',
                                                                        displayField: 'name',
                                                                        allowBlank: true
                                                                    })
                                                                ]
                                                            }
                                                        ]
                                                    }
                                                ]
                                            },
                                    ]
        };

        this.items = [
                        {xtype:'hidden',name:'id'},
                        {xtype:'hidden',name:'operation'},
                        {
                            id: 'etfs-edit-fieldset-box',
                            border: false,
                            anchor: '100% 100%',
                            layout: {
                                type: 'vbox',
                                align: 'stretch'  // Child items are stretched to full width
                            }
                            ,defaults: { flex: 1, layout:'form', autoScroll:true, bodyStyle:'padding:10px;', border:false}
                            ,items:[]
                        }
                ];

            this.buttons = [{
                               text: __('Save'),
                               formBind:true,
                               handler: this.onSave,
                               scope: this
                           },
                           {
                               text:__('Cancel'),
                           scope:this,
                           handler:function(){(this.ownerCt).close()}
                       }];
        ETFS.EditShare.Form.superclass.initComponent.call(this);
    }
    ,onSave: function(){
        var form_values = this.getForm().getValues();
        console.log(form_values);
        var name = this.getForm().findField("name").getValue();
        if( !name ){
            Ext.Msg.show({
                title: String.format(<?php echo json_encode(__('Error invalid share name')) ?>),
                buttons: Ext.MessageBox.OK,
                msg: String.format(<?php echo json_encode(__('Invalid share name!')) ?>),
                icon: Ext.MessageBox.ERROR});
        } else {
            var send_data = form_values;
            delete(send_data['id']);
            delete(send_data['operation']);
            send_data['name'] = name;

            if( send_data['guest'] == 'guest_only' ){
                send_data['guest_only'] = 'yes';
            } if( send_data['guest'] == 'yes' ){
                send_data['guest_ok'] = 'yes';
            }
            delete(send_data['guest']);
            if( send_data['read_list_u'] || send_data['read_list_g'] ){
                var readlist_arr = [];
                if( send_data['read_list_u'] ){
                    readlist_arr.push(send_data['read_list_u']);
                }
                if( send_data['read_list_g'] ){
                    var readlistg_arr = send_data['read_list_g'].split(',');
                    for(var i=0; i<readlistg_arr.length; i++){
                        readlist_arr.push('@'+readlistg_arr[i]);
                    }
                }
                send_data['read_list'] = readlist_arr.join(',');
            }
            delete(send_data['read_list_u']);
            delete(send_data['read_list_g']);
            if( send_data['write_list_u'] || send_data['write_list_g'] ){
                var writelist_arr = [];
                if( send_data['write_list_u'] ){
                    writelist_arr.push(send_data['write_list_u']);
                }
                if( send_data['write_list_g'] ){
                    var writelistg_arr = send_data['write_list_g'].split(',');
                    for(var i=0; i<writelistg_arr.length; i++){
                        writelist_arr.push('@'+writelistg_arr[i]);
                    }
                }
                send_data['write_list'] = writelist_arr.join(',');
            }
            delete(send_data['write_list_u']);
            delete(send_data['write_list_g']);
            if( send_data['invalid_users_u'] || send_data['invalid_users_g'] ){
                var invalidusers_arr = [];
                if( send_data['invalid_users_u'] ){
                    invalidusers_arr.push(send_data['invalid_users_u']);
                }
                if( send_data['invalid_users_g'] ){
                    var invalidusersg_arr = send_data['invalid_users_g'].split(',');
                    for(var i=0; i<invalidusersg_arr.length; i++){
                        invalidusers_arr.push('@'+invalidusersg_arr[i]);
                    }
                }
                send_data['invalid_users'] = invalidusers_arr.join(',');
            }
            delete(send_data['invalid_users_u']);
            delete(send_data['invalid_users_g']);
            console.log(send_data);
 
            // process update share
            var conn = new Ext.data.Connection({
                listeners:{
                    // wait message.....
                    beforerequest:function(){
                        Ext.MessageBox.show({
                            title: <?php echo json_encode(__('Please wait...')) ?>,
                            msg: <?php echo json_encode(__('Saving share info...')) ?>,
                            width:300,
                            wait: true,
                            modal: true
                        });
                    },// on request complete hide message
                    requestcomplete:function(){Ext.MessageBox.hide();}
                    ,requestexception:function(c,r,o){
                                Ext.MessageBox.hide();
                                Ext.Ajax.fireEvent('requestexception',c,r,o);}
                }
            });// end conn

            var method = 'update_share';
            var operation = this.getForm().findField("operation").getValue();
            if( operation == 'new' ){
                method = 'create_share';
            }
            conn.request({
                url: <?php echo json_encode(url_for('etfs/json')) ?>,
                params: {
                    id: this.service_id,
                    method: method,
                    params: Ext.encode(send_data)
                },            
                scope:this,
                success: function(resp,opt) {

                    var response = Ext.util.JSON.decode(resp.responseText);                

                    Ext.ux.Logger.info(response['agent'],response['response']);

                    (this.ownerCt).fireEvent('onSave');
                },
                failure: function(resp,opt) {
                    
                    var response = Ext.util.JSON.decode(resp.responseText);
                    Ext.ux.Logger.error(response['agent'],response['error']);

                    Ext.Msg.show({
                        title: String.format(<?php echo json_encode(__('Error {0}')) ?>,response['agent']),
                        buttons: Ext.MessageBox.OK,
                        msg: String.format(<?php echo json_encode(__('Error saving share!')) ?>)+'<br>'+response['info'],
                        icon: Ext.MessageBox.ERROR});

                }
            });// END Ajax request
        }
    }
    ,loadRecord: function(data){
        this.form.findField('id').setValue(this.service_id);
        console.log(data);
        if( data['new'] ){
            this.form.findField('operation').setValue("new");
            
            if( data['type'] == 'printer' ){
                Ext.getCmp('etfs-edit-fieldset-box').add(this.editshare_fieldset_printer);
            } else {
                Ext.getCmp('etfs-edit-fieldset-box').add(this.editshare_fieldset_folder);
            }
            Ext.getCmp('etfs-edit-fieldset-box').doLayout();

        } else {
            this.form.findField('operation').setValue("update");

            if( data['share'] ){
                var share = data['share'];

                if( share['printable'] ){
                    Ext.getCmp('etfs-edit-fieldset-box').add(this.editshare_fieldset_printer);
                } else {
                    Ext.getCmp('etfs-edit-fieldset-box').add(this.editshare_fieldset_folder);
                }
                Ext.getCmp('etfs-edit-fieldset-box').doLayout();

                this.load({url:<?php echo json_encode(url_for('etfs/json'))?>
                                ,params:{id:this.service_id,method:'list_shares_filterBy', params: Ext.encode({name:share['name']}) }
                                ,waitMsg: <?php echo json_encode(__('Loading...')) ?>
                                ,success:function(f,a){
                                    var data = a.result['data']
                                    data['guest'] = 'none';
                                    if( data['guest_ok'] == 'yes' ){
                                        data['guest'] = 'yes';
                                        Ext.getCmp('etfs-edit-fieldset-box').items.get(0).rbgsharefolderguest.setValue('yes');
                                    } else if( data['guest_only'] == 'yes' ){
                                        data['guest'] = 'guest_only';
                                        Ext.getCmp('etfs-edit-fieldset-box').items.get(0).rbgsharefolderguest.setValue('guest_only');
                                    }

                                    if( data['available'] ){
                                        Ext.getCmp('etfs-edit-fieldset-box').items.get(0).rbgsharefolderavailable.setValue(data['available']);
                                    }

                                    if( data['browseable'] ){
                                        Ext.getCmp('etfs-edit-fieldset-box').items.get(0).rbgsharefolderbrowseable.setValue(data['browseable']);
                                    }

                                    if( data['writable'] ){
                                        Ext.getCmp('etfs-edit-fieldset-box').items.get(0).rbgsharefolderwritable.setValue(data['writable']);
                                    }

                                    if( data['read_list'] ){
                                        var readlist_u = [];
                                        var readlist_g = [];
                                        var readlist_arr = data['read_list'].split(',');
                                        for(var i=0; i<readlist_arr.length; i++){
                                            var user = readlist_arr[i].replace(/\s+/g,"");
                                            if( user.match(/@/) ){
                                                var group = user.replace("@","");
                                                readlist_g.push(group);
                                            } else {
                                                readlist_u.push(user);
                                            }
                                        }
                                        data['read_list_u'] = readlist_u.join(',');
                                        data['read_list_g'] = readlist_g.join(',');
                                        Ext.getCmp('etfs-edit-fieldset-box').items.get(0).readlistFieldset.expand();
                                    }
                                    if( data['write_list'] ){
                                        var writelist_u = [];
                                        var writelist_g = [];
                                        var writelist_arr = data['write_list'].split(',');
                                        for(var i=0; i<writelist_arr.length; i++){
                                            var user = writelist_arr[i].replace(/\s+/g,"");
                                            if( user.match(/@/) ){
                                                var group = user.replace("@","");
                                                writelist_g.push(group);
                                            } else {
                                                writelist_u.push(user);
                                            }
                                        }
                                        data['write_list_u'] = writelist_u.join(',');
                                        data['write_list_g'] = writelist_g.join(',');
                                        Ext.getCmp('etfs-edit-fieldset-box').items.get(0).writelistFieldset.expand();
                                    }
                                    if( data['invalid_users'] ){
                                        var invalidusers_u = [];
                                        var invalidusers_g = [];
                                        var invalidusers_arr = data['invalid_users'].split(',');
                                        for(var i=0; i<invalidusers_arr.length; i++){
                                            var user = invalidusers_arr[i].replace(/\s+/g,"");
                                            if( user.match(/@/) ){
                                                var group = user.replace("@","");
                                                invalidusers_g.push(group);
                                            } else {
                                                invalidusers_u.push(user);
                                            }
                                        }
                                        data['invalid_users_u'] = invalidusers_u.join(',');
                                        data['invalid_users_g'] = invalidusers_g.join(',');
                                        Ext.getCmp('etfs-edit-fieldset-box').items.get(0).invalidusersFieldset.expand();
                                    }
                                    console.log(data);
                                    this.form.loadRecord(new Ext.data.Record(data));
                                }
                                ,scope: this
                            });
            }
        }
    }
});

ETFS.EditShare.Window = function(config) {

    console.log('ETFS.EditShare.Window');
    console.log(config);
    Ext.apply(this,config);

    ETFS.EditShare.Window.superclass.constructor.call(this, {
        width:560
        ,height:620
        ,border:false
        ,modal:true
        ,layout:'fit'
        ,items:[ new ETFS.EditShare.Form({service_id:this.service_id, share: this.share})]
    });
};


Ext.extend(ETFS.EditShare.Window, Ext.Window,{
    loadData:function(data){
        this.items.get(0).loadRecord(data);
    }
});

</script>

