<?php
/*
 * Use Extjs helper to dynamic create data store and column model javascript
 */
use_helper('Extjs');
$formItems = js_form_fields($node_form);
?>
<script>
NodeWindowForm = function() {

    // turn on validation errors beside the field globally
    //   Ext.form.Field.prototype.msgTarget = 'side';

    /*
     * ================  Simple form  =======================
     */
    this.nodeForm = new Ext.FormPanel({
       labelAlign: 'top',
       baseCls: 'x-plain',
       labelWidth: 120,

       defaultType: 'textfield',
       frame:true,
        id: 'nodeFormWindow',
   

//        items:this.feedUrl,
        border: false,
        bodyStyle:'background:transparent;padding:10px;',

items: [<?php  echo $formItems ?>]
      //  items: [{
//          layout:'column',
//
//            items:[{
//                columnWidth:.5,
//                layout: 'form',
//
//                items: [{
//                    xtype:'textfield',
//                    fieldLabel: 'Name',
//                    id: 'name',
//                    name: 'etva_node[name]',
//                    anchor:'95%'
//                }, {
//                    xtype:'textfield',
//                    fieldLabel: 'Total Memory',
//                    name: 'etva_node[memtotal]',
//                    anchor:'95%'
//                },{
//                    xtype:'textfield',
//                    fieldLabel: 'Total CPU',
//                    name: 'etva_node[cputotal]',
//                    anchor:'95%'
//                }]
//            },{
//                columnWidth:.5,
//                layout: 'form',
//                items: [{
//                    xtype:'textfield',
//                    fieldLabel: 'IP address',
//                    emptyText: '10.10.10.1',
//                    name: 'etva_node[ip]',
//                    anchor:'95%'
//                },{
//                    xtype:'textfield',
//                    fieldLabel: 'Network cards',
//                    name: 'etva_node[network_cards]',
//                    vtype:'email',
//
//                    anchor:'95%'
//                },
//                {
//                    xtype:'textfield',
//                    fieldLabel: 'State',
//                    name: 'etva_node[state]',
//                    value: 1,
//              //      vtype:'email',
//                    anchor:'95%'
//                }]
//            }]




//        },{
//            xtype:'htmleditor',
//            id:'bio',
//            fieldLabel:'Biography',
//            height:200,
//            anchor:'98%'
//        }],





    });
//    this.form.add(
//        new Ext.form.TextField({
//            fieldLabel: 'First Name',
//            name: 'first',
//            width:175,
//            allowBlank:false
//        }),
//
//        new Ext.form.TextField({
//            fieldLabel: 'Last Name',
//            name: 'last',
//            width:175
//        }),
//
//        new Ext.form.TextField({
//            fieldLabel: 'Company',
//            name: 'company',
//            width:175
//        }),
//
//        new Ext.form.TextField({
//            fieldLabel: 'Email',
//            name: 'email',
//            vtype:'email',
//            width:175
//        })
//    );

 //   this.form.addButton('Save');
//    this.form.addButton('Cancel');

   // simple.render('form-ct');




//    this.form = new Ext.FormPanel({
//        labelAlign:'top',
//        items:this.feedUrl,
//        border: false,
//        bodyStyle:'background:transparent;padding:10px;'
//    });

    NodeWindowForm.superclass.constructor.call(this, {
        title: 'Add new Node',
        iconCls: 'icon-add',        
        autoHeight: true,
        width: 200,
        resizable: false,
        plain:true,
        modal: true,      
        autoScroll: true,
        closeAction: 'hide',

        buttons:[{
            text: 'Add Node',
            handler: this.onAdd,
            scope: this
        },{
            text: 'Cancel',
            handler: this.hide.createDelegate(this, [])
        }],

        items: this.nodeForm
        
    });

    this.addEvents({add:true});
}


           
           
        
Ext.extend(NodeWindowForm, Ext.Window, {

    show : function(){
        
        if(this.rendered){
      //     this.
            //this.feedUrl.setValue('');
           this.nodeForm.form.reset();

        }


        NodeWindowForm.superclass.show.apply(this, arguments);
   

        
    },

    onAdd: function() {
        this.el.mask('In Progress...', 'x-mask-loading');








        Ext.Ajax.request({
            url: 'node/jsonCreate',
            // params : form_data,
            form : this.nodeForm.getForm().getEl().dom,
           // params: Ext.Ajax.serializeForm(this.form.form),
          // form: this.form.form,
            success: this.validateNode,
            failure: this.markInvalid,
            scope: this,
            title: this.nodeForm.getForm().findField("name").getValue()
          //  title: this.form.getForm()..getValue()
        });
    },

    markInvalid : function(){
        alert("not possible")
        this.feedUrl.markInvalid('The URL specified is not a valid RSS2 feed.');
        this.el.unmask();
    },


    validateNode : function(response, options){
        var dq = Ext.DomQuery;
        var title = options.title;
       
        var insert_id = Ext.util.JSON.decode(response.responseText).insert_id;
        var str = '';
        for(prop in response){
            str += prop + ' value :'+ response[prop]+'\n';//Concate prop and its value from object
        }
        alert(str); //Show all properties and its value
        
        //
        // alert(response.responseXML);
// alert('aki');
     //   try{
      //      var xml = response.responseXML;
      //      var channel = xml.getElementsByTagName('channel')[0];
      //      if(channel){
        //        var text = dq.selectValue('title', channel, url);
       //         var description = dq.selectValue('description', channel, 'No description available.');
                this.el.unmask();
                this.hide();
                return this.fireEvent('validnode', {
                    
                    id: insert_id,// $node->getID()
                    text: title, //$node->getName()
                    url: 'node/view?id='+insert_id
                    // description: description
                });
        //    }
     //   }catch(e){
     //   }
    //    this.markInvalid();


    // $aux[] = array('text'=>$node->getName(),'title'=>'file','url'=>'http://localhost:8003/toma','id'=>$node->getID(),'children'=>$aux_servers);

//     feeds.addFeed({
//        url:'http://feeds.feedburner.com/extblog',
//        text: 'ExtJS.com Blog'
//    }, false, true);


    }
});




</script>