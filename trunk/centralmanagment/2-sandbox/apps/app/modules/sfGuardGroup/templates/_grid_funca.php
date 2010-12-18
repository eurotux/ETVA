<script>

sfGuardGroupGrid = function(){
    return{

    init:function(){
    Ext.QuickTips.init();

    var cm = new Ext.grid.ColumnModel([
        {header: "Name", width: 120, dataIndex: 'Name'},
        {header: "CPU Time", width: 100, dataIndex: 'CPUTime'},
		{header: "Max VCPU", width: 80, dataIndex: 'MaxVCPU'},
        {header: "State", width: 100, dataIndex: 'State'}

	//	{header: "Manufacturer", width: 115, dataIndex: 'Manufacturer'},
	//	{header: "Product Group", width: 100, dataIndex: 'ProductGroup'}
	]);
    cm.defaultSortable = true;



var ds = new Ext.data.Store({
        // load using HTTP
        proxy: new Ext.data.HttpProxy({url: <?php echo json_encode('soapapi/getNodeServers?method=')?>}),

        // the return will be XML, so lets set up a reader
        reader: new Ext.data.XmlReader({
               // records will have an "Item" tag
               record: 'domain',
               id: 'name',
               totalRecords: '@total'
           }, [
               // set up the fields mapping into the xml doc
               // The first needs mapping, the others are very basic
               {name: 'Name', mapping: 'name'},
               {name: 'CPUTime', mapping: "info@cpuTime"},
               {name: 'MaxVCPU', mapping: "info@maxvcpus"},
               {name: 'State', mapping: 'state'}
           ])
    });




   // create the grid
    var nodeGrid = new Ext.grid.GridPanel({
        store: ds,
        cm:cm,
        title:'teste',
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
    return nodeGrid;




    }}
    }();

nodeGrid = sfGuardGroupGrid.init();



</script>