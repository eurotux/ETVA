<script>

Ext.ns('ETFW.Network.HostDns');


ETFW.Network.HostDns.Form = function(service_id) {    

    var allFields;

    this.service_id = service_id;

    this.hostname = new Ext.form.TextField({
        fieldLabel: 'Hostname',
        name: 'hostname',
        allowBlank: false,
        width:100
        //,anchor: '90%'

    });

    this.dns0 = new Ext.form.TextField({
        fieldLabel: 'DNS servers',
        name: 'nameserver0',
        maxLength: 15,
        width: 100

    });

    this.dns1 = new Ext.form.TextField({
        fieldLabel: '',
        name: 'nameserver1',
        maxLength: 15,
        width: 100

    });

    this.dns2 = new Ext.form.TextField({
        fieldLabel: '',
        name: 'nameserver2',
        maxLength: 15,
        //  allowBlank: false,
        width: 100

    });


    allFields = this.buildForm();


    ETFW.Network.HostDns.Form.superclass.constructor.call(this, {
        labelWidth: 90,
        autoScroll:true,        
        bodyStyle:'padding:10px',
        url:<?php echo json_encode(url_for('etfw/json'))?>,
        frame:true,
        defaults:{
            anchor:'90%'
        },
        items: [allFields]
    });

     this.on({
             afterlayout:{scope:this, single:true, fn:function() {this.loadData();}}
            });

};

Ext.extend(ETFW.Network.HostDns.Form, Ext.form.FormPanel, {

    buildUIForm:function(){

        return new Ext.Button({text: 'Save',
            width:60,
            handler: function() {
                var alldata = this.form.getValues();

                if (this.form.isValid()) {

                    var order0 = alldata['order0'];
                    var order1 = alldata['order1'];
                    var order2 = alldata['order2'];
                    var order3 = alldata['order3'];
                    var order = [];
                    order.push(order0);
                    order.push(order1);
                    order.push(order2);
                    order.push(order3);

                    var nameserver0 = alldata['nameserver0'];
                    var nameserver1 = alldata['nameserver1'];
                    var nameserver2 = alldata['nameserver2'];
                    var nameserver = [];
                    nameserver.push(nameserver0);
                    nameserver.push(nameserver1);
                    nameserver.push(nameserver2);

                    var domain = alldata['domain'];
                    var domain_array = [];

                    if(alldata['search']==1) domain_array = domain.split("\n");


                    var send_data = {"hostname":alldata['hostname'],
                        "order":order,
                        "nameserver":nameserver,
                        "domain":domain_array};

                    var conn = new Ext.data.Connection({
                        listeners:{
                            // wait message.....
                            beforerequest:function(){
                                Ext.MessageBox.show({
                                    title: 'Please wait',
                                    msg: 'Updating DNS client...',
                                    width:300,
                                    wait:true,
                                    modal: false
                                });
                            },// on request complete hide message
                            requestcomplete:function(){Ext.MessageBox.hide();}
                        }
                    });// end conn


                    conn.request({
                        url: this.url,
                        params:{id:this.service_id,method:'set_hostname_dns',
                            params:Ext.encode(send_data)
                        },
                        failure: function(resp,opt){
                            var response = Ext.util.JSON.decode(resp.responseText);
                            Ext.MessageBox.alert('Error Message', response['info']);
                            Ext.ux.Logger.error(response['error']);

                        },
                        // everything ok...
                        success: function(resp,opt){
                            var msg = 'Updated dns information';
                            Ext.ux.Logger.info(msg);
                            View.notify({html:msg});
                            this.loadData();


                        },scope:this
                    });// END Ajax request


                } else{
                    Ext.MessageBox.alert('error', 'Please fix the errors noted.');
                }
            },scope:this
        }

    );

    },
    /*
    * build form for 'now' panel
    */
    buildForm:function(){


        var resOrder = [
                        ['', ''],
                        ['files', 'Hosts'],
                        ['dns', 'DNS'],
                        ['nis', 'NIS'],
                        ['nisplus', 'NIS+'],
                        ['ldap', 'LDAP'],
                        ['db', 'Hosts'],
                        ['mdns4', 'Multicast DNS']
                       ];


        var orderStore = new Ext.data.SimpleStore({
                        fields: ['value', 'name'],
                        data : resOrder});

        orderCombo = function(config) {
            var defaultConfig = {
                emptyText: '[None]',
                tpl: '<tpl for="."><div class="x-combo-list-item">{name:defaultValue("&nbsp;")}</div></tpl>',//HACK: Render empty value in valid height. See: http://extjs.com/forum/showthread.php?t=65803&highlight=combobox+empty+text
                displayField: 'name',
                valueField: 'value',
                forceSelection: true,
                editable: false,
                fieldLabel: 'Resolution order',
                store: orderStore,
                triggerAction: 'all',
                mode: 'local'
            };

            Ext.applyIf(config, defaultConfig);
            return new Ext.form.ComboBox(config);
        };

        this.order0 = orderCombo({width:95,name:'order0',hiddenName:'order0'});
        this.order1 = orderCombo({width:95,name:'order1',hiddenName:'order1',hideLabel:true,fieldLabel:''});
        this.order2 = orderCombo({width:95,name:'order2',hiddenName:'order2',hideLabel:true,fieldLabel:''});
        this.order3 = orderCombo({width:95,name:'order3',hiddenName:'order3',hideLabel:true,fieldLabel:''});

        this.saveBtn = this.buildUIForm();
        this.refreshBtn = new Ext.Button({
                        text: 'Refresh',
                        tooltip: 'refresh',
                        iconCls: 'x-tbar-loading',
                        scope:this,
                        handler: function(button,event){this.loadData();}
                    });

        this.fieldset =
                [
                this.hostname
                ,{
                    layout:'table',
                    layoutConfig: {columns:4},
                    items:[{
                            layout:'form',
                            items:this.order0
                           }
                           ,{
                             layout:'form',
                             items:this.order1
                           },
                            {
                             layout:'form',
                             items:this.order2
                           },
                           {
                            layout:'form',
                            items:this.order3
                           }
                         ]

                }
                ,{
                    layout:'column',
                    border:true,
                    //defaults:{anchor:'90% 60%'},
                    //defaults:{height:130},
                    //layoutConfig: {columns:2},
                    items:[{
                            width:250,
                            layout:'form',
                            items:[this.dns0,
                                   this.dns1,
                                   this.dns2
                               ]
                           }
                           ,{

                             layout:'form',
                             columnWidth:.5,
                             
                             items:[{xtype:'radio', name:'search',
                                    fieldLabel:'Search domains',boxLabel:'None',inputValue: '0'},
                                    {xtype:'radio', name:'search',
                                    fieldLabel:'',boxLabel:'Listed',inputValue: '1'},
                                    {fieldLabel: '',anchor:'90% 70%',xtype:'textarea',name:'domain'}]
                            }
                          ]

                }
                ,{
                    layout:'table',
                    border:true,
                    layoutConfig: {columns:2},
                    items:[this.refreshBtn,this.saveBtn]
                }

            ];

        return this.fieldset;


    },
    loadData:function(){
            this.refreshBtn.addClass('x-item-disabled');
            this.load({
                url: this.url
                ,waitMsg:'Loading...'
                ,params:{id:this.service_id,method:'get_hostname_dns'}
                ,success:function(){this.refreshBtn.removeClass('x-item-disabled');}
                ,scope:this
            });


    }

});


ETFW.Network.HostDns.Main = function(service_id) {


    var form = new ETFW.Network.HostDns.Form(service_id);

    form.on('beforerender',function(){
            Ext.getBody().mask('Loading ETFW network dns client panel...');}
            ,this
    );

    form.on('render',function(){
            Ext.getBody().unmask();}
            ,this
            ,{delay:10}
    );


    ETFW.Network.HostDns.Main.superclass.constructor.call(this, {
        border:false,
        layout:'fit',
        title: 'Hostname and DNS Client',
        items:form

    });
}

// define public methods
Ext.extend(ETFW.Network.HostDns.Main, Ext.Panel, {
    reload:function(){
        var form = this.get(0);        

        if(form.rendered)
        {            
            form.loadData();
        }        

    }
});

</script>