<?php
//load partial files code... based on DB service module name.
//in this case it should be in service name=>main and will load js code from _ETFS_main.php
Etva::loadServicesPartials($etva_server);
?>
<script>

Ext.ns('ETFS');

/*
* .View
*
* Build all panels. Entry point for module
*
*/

ETFS.View = function(config) {
    Ext.apply(this,config);
    console.log(this);

    //load services ID from BD
    var conn = new Ext.data.Connection({
        listeners:{
            // wait message.....
            beforerequest:function(){
                Ext.MessageBox.show({
                    title: <?php echo json_encode(__('Please wait...')) ?>,
                    msg: <?php echo json_encode(__('Retrieving data...')) ?>,
                    width:300,
                    wait:true
                });
            },// on request complete hide message
            requestcomplete:function(){Ext.MessageBox.hide();}
            ,requestexception:function(c,r,o){Ext.Ajax.fireEvent('requestexception',c,r,o);}
        }
    });// end conn
    conn.request({
        url: <?php echo json_encode(url_for('service/jsonGetServices')) ?>,
        params:{sid:this.server['id']},
        scope:this,
        success: function(resp,opt) {
            Ext.getBody().unmask();
            var response = Ext.util.JSON.decode(resp.responseText);
            this.buildItem(response.data);
        }
        ,failure: function(resp,opt) {
            Ext.getBody().unmask();
            var response = Ext.util.JSON.decode(resp.responseText);
            if(response && resp.status!=401)
                Ext.Msg.show({
                    title: String.format(<?php echo json_encode(__('Error {0}')) ?>,response['agent']),
                    buttons: Ext.MessageBox.OK,
                    msg: String.format(<?php echo json_encode(__('Unable to retrieve data!')) ?>)+'<br>'+response['info'],
                    icon: Ext.MessageBox.ERROR});
        }
    });// END Ajax request

    ETFS.View.superclass.constructor.call(this, { });
}

Ext.extend(ETFS.View, Ext.TabPanel,{
    /*
     * build services panels
     */
    buildItem: function(recdata){

        var server_tmpl = this.server['agent_tmpl'];
        var server_id = this.server['id'];

        var services_ids = [];
        for(var i = 0; i < recdata.length;i++)
        {
            services_ids[recdata[i]['name_tmpl']] = recdata[i];
        }

        //load object from _ETFS_main.php
        var main = new ETFS.Main({layout:'fit',server_id:server_id,service:services_ids['main']});        

        var items = [main];

        this.add(items);
        this.doLayout();
        this.setActiveTab(0);
    }
});

</script>
