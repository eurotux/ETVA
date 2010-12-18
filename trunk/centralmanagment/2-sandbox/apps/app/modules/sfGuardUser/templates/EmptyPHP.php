<script>








/**
 * Copyright(c) 2006-2009, FeyaSoft Inc. All right reserved.
 * ====================================================================
 * Licence
 * ====================================================================
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY
 * KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE
 * WARRANTIES OF MERCHANTABILITY,FITNESS FOR A PARTICULAR PURPOSE
 * AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR
 * COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR
 * OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE
 * SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */
Ext.ns("feyaSoft.home.extjsTutor.gridForm");

/**
 * This JS is mainly used to handle list account.
 *
 * @author Fenqiang Zhuang
 * @Date July 11, 2008
 */
feyaSoft.home.extjsTutor.gridForm.List = function() {


    function renderDate(value){

        if(value){
            return Date.parseDate(value,"Y-m-d h:i:s").format('dS M Y, H:i:s');
        }
    }


    this.myPageSize = 20;

    // show check boxes
    var selectBoxModel= new Ext.grid.CheckboxSelectionModel();

    //listener to activate/deactivate buttons depending on how many rows are selected
    selectBoxModel.on('selectionchange', function(sm){
    	if(sm.getSelections().length > 0){
    		Ext.getCmp('account-delete-button').enable();
    		if(sm.getSelections().length == 1){
    			Ext.getCmp('account-edit-button').enable();
    		}else{
   				Ext.getCmp('account-edit-button').disable();
   			}
    	}else{
    		Ext.getCmp('account-delete-button').disable();
    		Ext.getCmp('account-edit-button').disable();
    	}
    }, this);

    // for filter
    var filters = new Ext.grid.GridFilters({
	  filters:[
	    {type: 'string',  dataIndex: 'firstname'}, // this should map dataIndex in gridCM
	    {type: 'string',  dataIndex: 'lastname'},
	    {type: 'string',  dataIndex: 'username'},
	    {type: 'string',  dataIndex: 'email'}
	]});

    // column model
    var userCM = new Ext.grid.ColumnModel([
        selectBoxModel
      //  {id: 'id', header: "Identify", dataIndex: 'id', width: 100, hidden: true},
 //       {header: "User Name", dataIndex: 'Username', width: 100, sortable: true, tooltip: 'First name shows ... tooltip'},
    //    {header: "Last Name", dataIndex: 'lastname',width: 100, sortable: true, tooltip: 'Last name shows ... tooltip'},
    //    {header: "Username", dataIndex: 'username',width: 100, sortable: true, tooltip: 'Username should be unique'},
    //    {header: "Email", dataIndex: 'email',width: 120, sortable: true},
 //       {header: "Created At", dataIndex: 'CreatedAt', width: 70, sortable: true,renderer: renderDate},
//        {header: "Last Login", dataIndex: 'LastLogin', width: 70, sortable: true,renderer: renderDate},
  //      {header: "Active", dataIndex: 'IsActive', width: 30, sortable: true}
    ]);
    // by default columns are sortable
    userCM.defaultSortable = true;

   /************************************************************
    * connect to backend - grid - core part
    * create the Data Store
    *   connect with backend and list the result in page
    *   through JSON format
    ************************************************************/
    this.dataStore = new Ext.data.JsonStore({
        proxy: new Ext.data.HttpProxy({url: 'sfGuardUser/jsonGrid'}),
        id: 'Id',
        totalProperty: 'total',
        root: 'data',
        fields: [{name:'Id',type:'int'},{name:'Username'},{name:'CreatedAt'},{name:'LastLogin'},{name:'IsActive'}],
        remoteSort: true,
        sortInfo: { field: 'Username',
        direction: 'ASC' },

    });
 //   this.dataStore.setDefaultSort('Username', 'ASC');
    this.dataStore.load({params:{start:0, limit:this.myPageSize}});

   /************************************************************
    * Define menubar now in here
    *   add and delete functions
    ************************************************************/
    var menubar = [{
        text:'Add',
        tooltip:'Add a New Account',
        iconCls:'addItem',
        handler: function(){
            Ext.getCmp('create-edit-account-form').create();
        }
    },'-',{
    	id: 'account-edit-button',
        text:'Edit',
        tooltip:'Edit the selected item',
        iconCls:'editItem',
        disabled:true,
        handler: function(){
            var record = Ext.getCmp('list-account-form-panel').getSelectionModel().getSelected();
	    	Ext.getCmp('create-edit-account-form').load(record.id);
        }
    },'-',{
    	id: 'account-delete-button',
        text:'Delete',
        tooltip:'Delete the selected item(s)',
        iconCls:'remove',
        disabled:true,
        handler: function(){
            new feyaSoft.util.DeleteItem({panel: 'list-account-form-panel'});
        }
    },'-',{
        text:'View Source Code',
        tooltip:'Click this to view source code',
        iconCls:'sourceCode',
        handler: function(){
            alert('oi');
           // new feyaSoft.home.extjsTutor.ListSource(app);
        }
    }];

    var pagingbar = new Ext.PagingToolbar({
        pageSize: this.myPageSize,
        store: this.dataStore,
        displayInfo: true,
        displayMsg: 'Displaying items {0} - {1} of {2}',
        emptyMsg: "not result to display",
        plugins: [new Ext.ux.PageSizePlugin(), filters] // add filter in here
    });

   /************************************************************
    * Constructor for the Ext.grid.EditorGridPanel
    ************************************************************/
    feyaSoft.home.extjsTutor.gridForm.List.superclass.constructor.call(this, {
    	id: 'list-account-form-panel',
        ds: this.dataStore,
        cm: userCM,
        sm: selectBoxModel,
        tbar: menubar,
        bbar: pagingbar,
        viewConfig: {forceFit:true},
        loadMask: {msg: 'loading data ...'},
        layout: 'fit',
        height: 450,
        enableColumnHide: false,
      //  plugins: filters,  // add filters
        autoScroll:true,
        listeners: {
		    render: function(g) {
		        g.getSelectionModel().selectRow(0);
		        var record = g.getSelectionModel().getSelected();
                alert(record.id);
			//	Ext.getCmp('create-edit-account-form').load(record.id);
        	},
		    delay: 750 // Allow rows to be rendered.
		}
    });

   /************************************************************
    * handle contextmenu event
    ************************************************************/
    this.addListener("rowcontextmenu", onContextMenu, this);
    function onContextMenu(grid, rowIndex, e) {
	    if (!this.menu) {
	        this.menu = new Ext.menu.Menu({
		        id: 'menus',
		        items: [{
			        text:'Edit',
			        tooltip:'Edit the selected item',
			        iconCls:'editItem',
			        handler: function(){
			            var record = Ext.getCmp('list-account-form-panel').getSelectionModel().getSelected();
						Ext.getCmp('create-edit-account-form').load(record.id);
			        }
		        },{
			        text:'Delete',
			        tooltip:'Delete the selected item(s)',
			        iconCls:'remove',
			        handler: function(){
			            new feyaSoft.util.DeleteItem({panel: 'list-account-form-panel'});
			        }
			    }]
		    });
		}
		e.stopEvent();
        this.menu.showAt(e.getXY());
    }

   /************************************************************
    * Action - edit
    *   handle user high-light one of column and double click
    *   user want to update this item
    ************************************************************/
    this.on('rowdblclick', function(gridPanel, rowIndex, e) {
	    var selectedId = this.dataStore.data.items[rowIndex].id;
	    Ext.getCmp('create-edit-account-form').load(selectedId);
	});
}

// define public methods
Ext.extend(feyaSoft.home.extjsTutor.gridForm.List, Ext.grid.GridPanel, {

    reload : function() {
		this.dataStore.load({params:{start:0, limit:this.myPageSize}, delay: 750});
	},

    // call delete stuff now
    // Server side will receive delData throught parameter
    deleteData : function (jsonData) {
        Ext.Ajax.request({
            url : 'tutorAccount/delete',
            params:{delData: jsonData},
            success: function ( result, request ) {
            	Ext.getCmp('list-account-form-panel').reload();
            },
			failure: function ( result, request) {
			    Ext.MessageBox.alert('Failed', 'Internal Error, please try again');
			}
        });
    }

});

</script>