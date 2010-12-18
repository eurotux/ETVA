<script>

    Ext.ns('ETFW_DHCP');

    // dhcp server interfaces listener
    ETFW_DHCP.NetworkInterface_Form = Ext.extend(Ext.form.FormPanel, {

        border:false
        ,frame:true
        ,url:<?php echo json_encode(url_for('etfw/json'))?>

        ,initComponent:function() {

            this.saveBtn = new Ext.Button({text:'Save'
                ,scope:this
                ,handler:this.onSave
            });


            // uses network_dispatcher_id from component method ETFW_dhcp_networkinterface
            this.ifacesStore = new Ext.data.JsonStore({
                url: this.url,
                baseParams:{id:<?php echo $network_dispatcher_id ?>,method:'boot_interfaces',mode:'boot_real_interfaces'},
                id: 'fullname',
                remoteSort: false,
                totalProperty: 'total',
                root: 'data',
                fields: ['fullname']
            });
            this.ifacesStore.setDefaultSort('fullname', 'ASC');

            this.ifacesStore.on('load',function(){
                this.onLoad();
            },this);

            //bind mask to store on load...
            new Ext.LoadMask(Ext.getBody(), {msg:"Please wait...",store:this.ifacesStore});


            this.ifaces = new Ext.ux.Multiselect({
                name:'ifaces',
                style:'padding-top:5px',
                fieldLabel:"Listen on interfaces",
                valueField:"fullname",
                displayField:"fullname",
                height:80,
                allowBlank:true,
                store:this.ifacesStore
            });


            var config = {
                layout:'form'
                ,autoScroll:false
                ,items:[

                    {
                        xtype:'fieldset',
                       // layout:'fit',
                        collapsible:true,
                        title: 'Network Interface',
                        items :[{html:'The DHCP server can only assign IP addresses on networks connected to one of the interfaces selected below.\n\
                            The network interface for all defined subnets must be included. If none are selected, the DHCP server will attempt to find one automatically.'},
                            this.ifaces]
                    }],
                buttons:[this.saveBtn]
            };

            // apply config
            Ext.apply(this, Ext.apply(this.initialConfig, config));


            this.on('beforerender',function(){
                Ext.getBody().mask('Loading form data...');

            },this);

            this.on('render',function(){
                Ext.getBody().unmask();}
            ,this
            ,{delay:10}
        );

            // call parent
            ETFW_DHCP.NetworkInterface_Form.superclass.initComponent.apply(this, arguments);



        } // eo function initComponent
        ,onRender:function(){
              // call parent
                 ETFW_DHCP.NetworkInterface_Form.superclass.onRender.apply(this, arguments);

             // set wait message target
                this.getForm().waitMsgTarget = this.getEl();

            // loads store after initial layout
               this.on('afterlayout', this.loadStore, this, {single:true});
        }
        //loads interfaces store
        ,loadStore:function(){
            this.ifacesStore.load();
        }
        // for setting current selected values (interfaces)
        ,onLoad:function(){
            this.load({
                url:this.url
                ,waitMsg:'Loading...'
                ,params:{id:this.serviceId,method:'get_interface'}
                ,scope:this
            });
        }
        ,onSave:function() {
            var send_data = new Object();
            var form_values = this.getForm().getValues();

            var ifaces_values = form_values['ifaces'];
            var ifaces_values_array = ifaces_values.split(',');
            var ifaces = [];

            for(var i=0,len=ifaces_values_array.length;i<len;i++){
                if(ifaces_values_array[i])
                    ifaces.push(ifaces_values_array[i]);
            }

            send_data['ifaces'] = ifaces;


            var conn = new Ext.data.Connection({
                listeners:{
                    // wait message.....
                    beforerequest:function(){
                        Ext.MessageBox.show({
                            title: 'Please wait',
                            msg: 'Updating network interfaces...',
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
                params:{id:this.serviceId,method:'set_interface',params:Ext.encode(send_data)},
                failure: function(resp,opt){

                    if(!resp.responseText){
                        Ext.ux.Logger.error(resp.statusText);
                        return;
                    }

                    var response = Ext.util.JSON.decode(resp.responseText);
                    Ext.MessageBox.alert('Error Message', response['info']);
                    Ext.ux.Logger.error(response['error']);

                },
                // everything ok...
                success: function(resp,opt){
                    (this.ownerCt).close();
                    var msg = 'DHCP server network interfaces listener edited successfully';
                    Ext.ux.Logger.info(msg);
                    View.notify({html:msg});

                },scope:this
            });// END Ajax request


        }


    }); // eo extend


</script>