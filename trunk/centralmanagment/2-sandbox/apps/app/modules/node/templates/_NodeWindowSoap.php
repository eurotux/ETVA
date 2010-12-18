<script>
NodeWindowSoap = function(btn) {

    // create the Data Store
    this.ds = new Ext.data.JsonStore({
        // load using HTTP
        // proxy: new Ext.data.HttpProxy({url: <?php // echo json_encode('soapapi/getNodeServers?method=')?>+btn.param}),

        url: <?php echo json_encode('node/soap?method=')?>+btn.param,

        // the return will be XML, so lets set up a reader
       fields:[
               // set up the fields mapping into the xml doc
               // The first needs mapping, the others are very basic
               {name: 'Name', mapping: '>name'},
          //     {name: 'CPUTime', mapping: "info@cpuTime"},
          //     {name: 'MaxVCPU', mapping: "info@maxvcpus"},
               {name: 'State', mapping: 'state'}
           ]
    });
    

    var cm = new Ext.grid.ColumnModel([
       {header: "Name", width: 120, dataIndex: 'Name'},
    //    {header: "CPU Time", width: 100, dataIndex: 'CPUTime'},
	//	{header: "Max VCPU", width: 80, dataIndex: 'MaxVCPU'},
        {header: "State", width: 100, dataIndex: 'State'}

	//	{header: "Manufacturer", width: 115, dataIndex: 'Manufacturer'},
	//	{header: "Product Group", width: 100, dataIndex: 'ProductGroup'}
	]);
     cm.defaultSortable = true;

    // create the grid
    this.grid = new Ext.grid.GridPanel({
        store: this.ds,
        cm:cm,
        loadMask:true,
    //    renderTo:'example-grid',
        width:540,
        height:200,
        listeners:{
    render: function(grid){   //load the store when the grid is rendered
           grid.loadMask.show();
           var store = grid.getStore();
           store.load.defer(20,store);  //give the mask a chance to render
    },
    delay : 100, //also give the loadMask time to init (afterRender).
    single : true
}

    });


//     var win = new Ext.Window({
// width:400
// ,id:'autoload-win'
// ,height:300
// ,autoScroll:true
// ,title:'ola'
// ,tbar:[{
// text:'Reload'
// ,handler:function() {
// win.load(win.autoLoad.url + '?' + (new Date).getTime());
// }
// }]
// ,listeners:{show:function() {
// this.loadMask = new Ext.LoadMask(this.body, {
// msg:'Loading. Please wait...'
// });
// }}
// });

//win.show();

NodeWindowSoap.superclass.constructor.call(this, {
        title: 'Add new Node',
        iconCls: 'icon-add',
        autoHeight: true,
        width: 560,
        resizable: false,
        plain:true,
        modal: true,
        loadMask:true,
        autoScroll: false,
        closeAction: 'hide',
         tbar:[{
 text:'Reload'
 ,handler:this.reload,
   scope:this
 }
 ],
        buttons:[{
            text: 'Add Node',
            handler: this.onAdd,
            scope: this
        },{
            text: 'Cancel',
            handler: this.hide.createDelegate(this, [])
        }],

        items: this.grid

    });
// this.grid.render();

};

Ext.extend(NodeWindowSoap, Ext.Window, {

    show : function(){

        if(this.rendered){
            this.ds.reload();
      //     this.
            //this.feedUrl.setValue('');
           // this.nodeForm.form.reset();

        }


        NodeWindowSoap.superclass.show.apply(this, arguments);



    },reload:function(){this.ds.reload();

    }});

</script>
