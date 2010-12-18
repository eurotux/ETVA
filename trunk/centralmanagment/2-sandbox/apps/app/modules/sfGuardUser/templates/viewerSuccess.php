<script>



/**
 * @author Steven Roussey
 *
 * @ usage:
 *     {
 *	       xtype:'combo',
 *	       store:['Reader','Participant','Moderator','SuperUser']
 *      }
 * Or
 *     {
 *	       xtype:'combo',
 *	       store:[['r','Reader'],['p','Participant'],['m','Moderator'],['s','SuperUser']]
 *      }
 */

Ext.ux.ComboBox = function(config){
	if (config.store && typeof config.store !='string' && config.store.length>1)
	{
		if (typeof config.store[0] !='string' && config.store[0].length>1)
		{
			config.store = new Ext.data.SimpleStore({
			    fields: ['value','text'],
			    data : config.store
			});
	        config.valueField = 'value';
            config.displayField = 'text';
		}
		else
		{
			var store=[];
			for (var i=0,len=config.store.length;i<len;i++)
				store[i]=[config.store[i]];
			config.store = new Ext.data.SimpleStore({
			    fields: ['text'],
			    data : store
			});
	        config.valueField = 'text';
            config.displayField = 'text';
		}
		config.mode = 'local';
	}
    Ext.ux.ComboBox.superclass.constructor.call(this, config);
}
Ext.extend(Ext.ux.ComboBox,Ext.form.ComboBox,{
	});
Ext.reg('combo',Ext.ux.ComboBox);





Ext.namespace('Ext.ux');

Ext.ux.PageSizePlugin = function() {

    Ext.ux.PageSizePlugin.superclass.constructor.call(this, {
        store: new Ext.data.SimpleStore({
            fields: ['text', 'value'],
            data: [['10', 10], ['20', 20], ['30', 30], ['50', 50], ['100', 100]]
        }),
        mode: 'local',
        displayField: 'text',
        valueField: 'value',
        editable: false,
        allowBlank: false,
        triggerAction: 'all',
        width: 60
    });
};

Ext.extend(Ext.ux.PageSizePlugin, Ext.form.ComboBox, {
    init: function(paging) {
        paging.on('render', this.onInitView, this);
    },

    onInitView: function(paging) {
        paging.add('-',
            this,
            '&nbsp;Items per page'
        );
        this.setValue(paging.pageSize);
        this.on('select', this.onPageSizeChanged, paging);
    },

    onPageSizeChanged: function(combo) {
        this.pageSize = parseInt(combo.getValue());
        this.doLoad(0);
    }
});




/*
 * Ext JS Library 2.1
 * Copyright(c) 2006-2008, Ext JS, LLC.
 * licensing@extjs.com
 *
 * http://extjs.com/license
 */

Ext.menu.EditableItem = Ext.extend(Ext.menu.BaseItem, {
    itemCls : "x-menu-item",
    hideOnClick: false,

    initComponent: function(){
      Ext.menu.EditableItem.superclass.initComponent.call(this);
    	this.addEvents('keyup');

			this.editor = this.editor || new Ext.form.TextField();
			if(this.text) {
				this.editor.setValue(this.text);
      }
    },

    onRender: function(container){
        var s = container.createChild({
        	cls: this.itemCls,
        	html: '<img src="' + this.icon + '" class="x-menu-item-icon" style="margin: 3px 3px 2px 2px;" />'
        });

        Ext.apply(this.config, {width: 125});
        this.editor.render(s);

        this.el = s;
        this.relayEvents(this.editor.el, ["keyup"]);

        if(Ext.isGecko) {
    			s.setStyle('overflow', 'auto');
        }

        Ext.menu.EditableItem.superclass.onRender.call(this, container);
    },

    getValue: function(){
    	return this.editor.getValue();
    },

    setValue: function(value){
    	this.editor.setValue(value);
    },

    isValid: function(preventMark){
    	return this.editor.isValid(preventMark);
    }
});





   /*
 * Ext JS Library 2.1
 * Copyright(c) 2006-2008, Ext JS, LLC.
 * licensing@extjs.com
 *
 * http://extjs.com/license
 */

Ext.ns("Ext.grid.filter");
Ext.grid.filter.Filter = function(config){
	Ext.apply(this, config);

	this.events = {
		/**
		 * @event activate
		 * Fires when a inactive filter becomes active
		 * @param {Ext.ux.grid.filter.Filter} this
		 */
		'activate': true,
		/**
		 * @event deactivate
		 * Fires when a active filter becomes inactive
		 * @param {Ext.ux.grid.filter.Filter} this
		 */
		'deactivate': true,
		/**
		 * @event update
		 * Fires when a filter configuration has changed
		 * @param {Ext.ux.grid.filter.Filter} this
		 */
		'update': true,
		/**
		 * @event serialize
		 * Fires after the serialization process. Use this to apply additional parameters to the serialized data.
		 * @param {Array/Object} data A map or collection of maps representing the current filter configuration.
		 * @param {Ext.ux.grid.filter.Filter} filter The filter being serialized.
		 **/
		'serialize': true
	};
	Ext.grid.filter.Filter.superclass.constructor.call(this);

	this.menu = new Ext.menu.Menu();
	this.init();

	if(config && config.value) {
		this.setValue(config.value);
		this.setActive(config.active !== false, true);
		delete config.value;
	}
};
Ext.extend(Ext.grid.filter.Filter, Ext.util.Observable, {
	/**
	 * @cfg {Boolean} active
	 * Indicates the default status of the filter (defaults to false).
	 */
    /**
     * True if this filter is active. Read-only.
     * @type Boolean
     * @property
     */
	active: false,
	/**
	 * @cfg {String} dataIndex
	 * The {@link Ext.data.Store} data index of the field this filter represents. The dataIndex does not actually
	 * have to exist in the store.
	 */
	dataIndex: null,
	/**
	 * The filter configuration menu that will be installed into the filter submenu of a column menu.
	 * @type Ext.menu.Menu
	 * @property
	 */
	menu: null,

	/**
	 * Initialize the filter and install required menu items.
	 */
	init: Ext.emptyFn,

	fireUpdate: function() {
		this.value = this.item.getValue();

		if(this.active) {
			this.fireEvent("update", this);
    }
		this.setActive(this.value.length > 0);
	},

	/**
	 * Returns true if the filter has enough configuration information to be activated.
	 * @return {Boolean}
	 */
	isActivatable: function() {
		return true;
	},

	/**
	 * Sets the status of the filter and fires that appropriate events.
	 * @param {Boolean} active        The new filter state.
	 * @param {Boolean} suppressEvent True to prevent events from being fired.
	 */
	setActive: function(active, suppressEvent) {
		if(this.active != active) {
			this.active = active;
			if(suppressEvent !== true) {
				this.fireEvent(active ? 'activate' : 'deactivate', this);
      }
		}
	},

	/**
	 * Get the value of the filter
	 * @return {Object} The 'serialized' form of this filter
	 */
	getValue: Ext.emptyFn,

	/**
	 * Set the value of the filter.
	 * @param {Object} data The value of the filter
	 */
	setValue: Ext.emptyFn,

	/**
	 * Serialize the filter data for transmission to the server.
	 * @return {Object/Array} An object or collection of objects containing key value pairs representing
	 * 	the current configuration of the filter.
	 */
	serialize: Ext.emptyFn,

	/**
	 * Validates the provided Ext.data.Record against the filters configuration.
	 * @param {Ext.data.Record} record The record to validate
	 * @return {Boolean} True if the record is valid with in the bounds of the filter, false otherwise.
	 */
	 validateRecord: function(){return true;}
});














    /*
 * Ext JS Library 2.1
 * Copyright(c) 2006-2008, Ext JS, LLC.
 * licensing@extjs.com
 *
 * http://extjs.com/license
 */

Ext.grid.filter.StringFilter = Ext.extend(Ext.grid.filter.Filter, {
	updateBuffer: 500,
	icon: 'images/icons/find.png',

	init: function() {
		var value = this.value = new Ext.menu.EditableItem({icon: this.icon});
		value.on('keyup', this.onKeyUp, this);
		this.menu.add(value);

		this.updateTask = new Ext.util.DelayedTask(this.fireUpdate, this);
	},

	onKeyUp: function(event) {
		if(event.getKey() == event.ENTER){
			this.menu.hide(true);
			return;
		}
		this.updateTask.delay(this.updateBuffer);
	},

	isActivatable: function() {
		return this.value.getValue().length > 0;
	},

	fireUpdate: function() {
		if(this.active) {
			this.fireEvent("update", this);
    }
		this.setActive(this.isActivatable());
	},

	setValue: function(value) {
		this.value.setValue(value);
		this.fireEvent("update", this);
	},

	getValue: function() {
		return this.value.getValue();
	},

	serialize: function() {
		var args = {type: 'string', value: this.getValue()};
		this.fireEvent('serialize', args, this);
		return args;
	},

	validateRecord: function(record) {
		var val = record.get(this.dataIndex);
		if(typeof val != "string") {
			return this.getValue().length == 0;
    }
		return val.toLowerCase().indexOf(this.getValue().toLowerCase()) > -1;
	}
});



/*
 * Ext JS Library 2.1
 * Copyright(c) 2006-2008, Ext JS, LLC.
 * licensing@extjs.com
 *
 * http://extjs.com/license
 */

Ext.grid.filter.DateFilter = Ext.extend(Ext.grid.filter.Filter, {
	dateFormat: 'm/d/Y',
	pickerOpts: {},

	init: function() {
		var opts = Ext.apply(this.pickerOpts, {
			minDate: this.minDate,
			maxDate: this.maxDate,
			format:  this.dateFormat
		});
		var dates = this.dates = {
			'before': new Ext.menu.CheckItem({text: "Before", menu: new Ext.menu.DateMenu(opts)}),
			'after':  new Ext.menu.CheckItem({text: "After", menu: new Ext.menu.DateMenu(opts)}),
			'on':     new Ext.menu.CheckItem({text: "On", menu: new Ext.menu.DateMenu(opts)})
    };

		this.menu.add(dates.before, dates.after, "-", dates.on);

		for(var key in dates) {
			var date = dates[key];
			date.menu.on('select', this.onSelect.createDelegate(this, [date]), this);

      date.on('checkchange', function(){
        this.setActive(this.isActivatable());
			}, this);
		};
	},

	onSelect: function(date, menuItem, value, picker) {
    date.setChecked(true);
    var dates = this.dates;

    if(date == dates.on) {
      dates.before.setChecked(false, true);
      dates.after.setChecked(false, true);
    } else {
      dates.on.setChecked(false, true);

      if(date == dates.after && dates.before.menu.picker.value < value) {
        dates.before.setChecked(false, true);
      } else if (date == dates.before && dates.after.menu.picker.value > value) {
        dates.after.setChecked(false, true);
      }
    }

    this.fireEvent("update", this);
  },

	getFieldValue: function(field) {
		return this.dates[field].menu.picker.getValue();
	},

	getPicker: function(field) {
		return this.dates[field].menu.picker;
	},

	isActivatable: function() {
		return this.dates.on.checked || this.dates.after.checked || this.dates.before.checked;
	},

	setValue: function(value) {
		for(var key in this.dates) {
			if(value[key]) {
				this.dates[key].menu.picker.setValue(value[key]);
				this.dates[key].setChecked(true);
			} else {
				this.dates[key].setChecked(false);
			}
    }
	},

	getValue: function() {
		var result = {};
		for(var key in this.dates) {
			if(this.dates[key].checked) {
				result[key] = this.dates[key].menu.picker.getValue();
      }
    }
		return result;
	},

	serialize: function() {
		var args = [];
		if(this.dates.before.checked) {
			args = [{type: 'date', comparison: 'lt', value: this.getFieldValue('before').format(this.dateFormat)}];
    }
		if(this.dates.after.checked) {
			args.push({type: 'date', comparison: 'gt', value: this.getFieldValue('after').format(this.dateFormat)});
    }
		if(this.dates.on.checked) {
			args = {type: 'date', comparison: 'eq', value: this.getFieldValue('on').format(this.dateFormat)};
    }

    this.fireEvent('serialize', args, this);
		return args;
	},

	validateRecord: function(record) {
		var val = record.get(this.dataIndex).clearTime(true).getTime();

		if(this.dates.on.checked && val != this.getFieldValue('on').clearTime(true).getTime()) {
			return false;
    }
		if(this.dates.before.checked && val >= this.getFieldValue('before').clearTime(true).getTime()) {
			return false;
    }
		if(this.dates.after.checked && val <= this.getFieldValue('after').clearTime(true).getTime()) {
			return false;
    }
		return true;
	}
});




/*
 * Ext JS Library 2.1
 * Copyright(c) 2006-2008, Ext JS, LLC.
 * licensing@extjs.com
 *
 * http://extjs.com/license
 */

Ext.grid.filter.ListFilter = Ext.extend(Ext.grid.filter.Filter, {
	labelField:  'text',
	loadingText: 'Loading...',
	loadOnShow:  true,
	value:       [],
	loaded:      false,
	phpMode:     false,

	init: function(){
		this.menu.add('<span class="loading-indicator">' + this.loadingText + '</span>');

		if(this.store && this.loadOnShow) {
		  this.menu.on('show', this.onMenuLoad, this);
		} else if(this.options) {
			var options = [];
			for(var i=0, len=this.options.length; i<len; i++) {
				var value = this.options[i];
				switch(Ext.type(value)) {
					case 'array':
            options.push(value);
            break;
					case 'object':
            options.push([value.id, value[this.labelField]]);
            break;
					case 'string':
            options.push([value, value]);
            break;
				}
			}

			this.store = new Ext.data.Store({
				reader: new Ext.data.ArrayReader({id: 0}, ['id', this.labelField])
			});
			this.options = options;
			this.menu.on('show', this.onMenuLoad, this);
		}

		this.store.on('load', this.onLoad, this);
		this.bindShowAdapter();
	},

	/**
	 * Lists will initially show a 'loading' item while the data is retrieved from the store. In some cases the
	 * loaded data will result in a list that goes off the screen to the right (as placement calculations were done
	 * with the loading item). This adaptor will allow show to be called with no arguments to show with the previous
	 * arguments and thusly recalculate the width and potentially hang the menu from the left.
	 *
	 */
	bindShowAdapter: function() {
		var oShow = this.menu.show;
		var lastArgs = null;
		this.menu.show = function() {
			if(arguments.length == 0) {
				oShow.apply(this, lastArgs);
			} else {
				lastArgs = arguments;
				oShow.apply(this, arguments);
			}
		};
	},

	onMenuLoad: function() {
		if(!this.loaded) {
			if(this.options) {
				this.store.loadData(this.options);
      } else {
				this.store.load();
      }
		}
	},

	onLoad: function(store, records) {
		var visible = this.menu.isVisible();
		this.menu.hide(false);

		this.menu.removeAll();

		var gid = this.single ? Ext.id() : null;
		for(var i=0, len=records.length; i<len; i++) {
			var item = new Ext.menu.CheckItem({
				text: records[i].get(this.labelField),
				group: gid,
				checked: this.value.indexOf(records[i].id) > -1,
				hideOnClick: false
      });

			item.itemId = records[i].id;
			item.on('checkchange', this.checkChange, this);

			this.menu.add(item);
		}

		this.setActive(this.isActivatable());
		this.loaded = true;

		if(visible) {
			this.menu.show(); //Adaptor will re-invoke with previous arguments
    }
	},

	checkChange: function(item, checked) {
		var value = [];
		this.menu.items.each(function(item) {
			if(item.checked) {
				value.push(item.itemId);
      }
		},this);
		this.value = value;

		this.setActive(this.isActivatable());
		this.fireEvent("update", this);
	},

	isActivatable: function() {
		return this.value.length > 0;
	},

	setValue: function(value) {
		var value = this.value = [].concat(value);

		if(this.loaded) {
			this.menu.items.each(function(item) {
				item.setChecked(false, true);
				for(var i=0, len=value.length; i<len; i++) {
					if(item.itemId == value[i]) {
						item.setChecked(true, true);
          }
        }
			}, this);
    }

		this.fireEvent("update", this);
	},

	getValue: function() {
		return this.value;
	},

	serialize: function() {
    var args = {type: 'list', value: this.phpMode ? this.value.join(',') : this.value};
    this.fireEvent('serialize', args, this);
		return args;
	},

	validateRecord: function(record) {
		return this.getValue().indexOf(record.get(this.dataIndex)) > -1;
	}
});







 


    /*
 * Ext JS Library 2.1
 * Copyright(c) 2006-2008, Ext JS, LLC.
 * licensing@extjs.com
 *
 * http://extjs.com/license
 */

Ext.grid.GridFilters = function(config){
	this.filters = new Ext.util.MixedCollection();
	this.filters.getKey = function(o) {return o ? o.dataIndex : null};

	for(var i=0, len=config.filters.length; i<len; i++) {
		this.addFilter(config.filters[i]);
  }

	this.deferredUpdate = new Ext.util.DelayedTask(this.reload, this);

	delete config.filters;
	Ext.apply(this, config);
};
Ext.extend(Ext.grid.GridFilters, Ext.util.Observable, {
	/**
	 * @cfg {Integer} updateBuffer
	 * Number of milisecond to defer store updates since the last filter change.
	 */
	updateBuffer: 500,
	/**
	 * @cfg {String} paramPrefix
	 * The url parameter prefix for the filters.
	 */
	paramPrefix: 'filter',
	/**
	 * @cfg {String} fitlerCls
	 * The css class to be applied to column headers that active filters. Defaults to 'ux-filterd-column'
	 */
	filterCls: 'ux-filtered-column',
	/**
	 * @cfg {Boolean} local
	 * True to use Ext.data.Store filter functions instead of server side filtering.
	 */
	local: false,
	/**
	 * @cfg {Boolean} autoReload
	 * True to automagicly reload the datasource when a filter change happens.
	 */
	autoReload: true,
	/**
	 * @cfg {String} stateId
	 * Name of the Ext.data.Store value to be used to store state information.
	 */
	stateId: undefined,
	/**
	 * @cfg {Boolean} showMenu
	 * True to show the filter menus
	 */
	showMenu: true,

	init: function(grid){
    if(grid instanceof Ext.grid.GridPanel){
      this.grid  = grid;

      this.store = this.grid.getStore();
      if(this.local){
        this.store.on('load', function(store) {
          store.filterBy(this.getRecordFilter());
        }, this);
      } else {
        this.store.on('beforeload', this.onBeforeLoad, this);
      }

      this.grid.filters = this;

      this.grid.addEvents('filterupdate');

      grid.on("render", this.onRender, this);
      grid.on("beforestaterestore", this.applyState, this);
      grid.on("beforestatesave", this.saveState, this);

    } else if(grid instanceof Ext.PagingToolbar) {
      this.toolbar = grid;
    }
	},

	/** private **/
	applyState: function(grid, state) {
		this.suspendStateStore = true;
		this.clearFilters();
		if(state.filters) {
			for(var key in state.filters) {
				var filter = this.filters.get(key);
				if(filter) {
					filter.setValue(state.filters[key]);
					filter.setActive(true);
				}
			}
    }

		this.deferredUpdate.cancel();
		if(this.local) {
			this.reload();
    }

		this.suspendStateStore = false;
	},

	/** private **/
	saveState: function(grid, state){
		var filters = {};
		this.filters.each(function(filter) {
			if(filter.active) {
				filters[filter.dataIndex] = filter.getValue();
      }
		});
		return state.filters = filters;
	},

	/** private **/
	onRender: function(){
		var hmenu;

		if(this.showMenu) {
			hmenu = this.grid.getView().hmenu;

			this.sep  = hmenu.addSeparator();
			this.menu = hmenu.add(new Ext.menu.CheckItem({
					text: 'Filters',
					menu: new Ext.menu.Menu()
				}));
			this.menu.on('checkchange', this.onCheckChange, this);
			this.menu.on('beforecheckchange', this.onBeforeCheck, this);

			hmenu.on('beforeshow', this.onMenu, this);
		}

		this.grid.getView().on("refresh", this.onRefresh, this);
		this.updateColumnHeadings(this.grid.getView());
	},

	/** private **/
	onMenu: function(filterMenu) {
		var filter = this.getMenuFilter();
		if(filter) {
			this.menu.menu = filter.menu;
			this.menu.setChecked(filter.active, false);
		}

		this.menu.setVisible(filter !== undefined);
		this.sep.setVisible(filter !== undefined);
	},

	/** private **/
	onCheckChange: function(item, value) {
		this.getMenuFilter().setActive(value);
	},

	/** private **/
	onBeforeCheck: function(check, value) {
		return !value || this.getMenuFilter().isActivatable();
	},

	/** private **/
	onStateChange: function(event, filter) {
    if(event == "serialize") {
      return;
    }

		if(filter == this.getMenuFilter()) {
			this.menu.setChecked(filter.active, false);
    }

		if(this.autoReload || this.local) {
			this.deferredUpdate.delay(this.updateBuffer);
    }

		var view = this.grid.getView();
		this.updateColumnHeadings(view);

		this.grid.saveState();

		this.grid.fireEvent('filterupdate', this, filter);
	},

	/** private **/
	onBeforeLoad: function(store, options) {
    options.params = options.params || {};
		this.cleanParams(options.params);
		var params = this.buildQuery(this.getFilterData());
		Ext.apply(options.params, params);
	},

	/** private **/
	onRefresh: function(view) {
		this.updateColumnHeadings(view);
	},

	/** private **/
	getMenuFilter: function() {
		var view = this.grid.getView();
		if(!view || view.hdCtxIndex === undefined) {
			return null;
    }

		return this.filters.get(view.cm.config[view.hdCtxIndex].dataIndex);
	},

	/** private **/
	updateColumnHeadings: function(view) {
		if(!view || !view.mainHd) {
      return;
    }

		var hds = view.mainHd.select('td').removeClass(this.filterCls);
		for(var i=0, len=view.cm.config.length; i<len; i++) {
			var filter = this.getFilter(view.cm.config[i].dataIndex);
			if(filter && filter.active) {
				hds.item(i).addClass(this.filterCls);
      }
		}
	},

	/** private **/
	reload: function() {
		if(this.local){
			this.grid.store.clearFilter(true);
			this.grid.store.filterBy(this.getRecordFilter());
		} else {
			this.deferredUpdate.cancel();
			var store = this.grid.store;
			if(this.toolbar) {
				var start = this.toolbar.paramNames.start;
				if(store.lastOptions && store.lastOptions.params && store.lastOptions.params[start]) {
					store.lastOptions.params[start] = 0;
        }
			}
			store.reload();
		}
	},

	/**
	 * Method factory that generates a record validator for the filters active at the time
	 * of invokation.
	 *
	 * @private
	 */
	getRecordFilter: function() {
		var f = [];
		this.filters.each(function(filter) {
			if(filter.active) {
        f.push(filter);
      }
		});

		var len = f.length;
		return function(record) {
			for(var i=0; i<len; i++) {
				if(!f[i].validateRecord(record)) {
					return false;
        }
      }
			return true;
		};
	},

	/**
	 * Adds a filter to the collection.
	 *
	 * @param {Object/Ext.grid.filter.Filter} config A filter configuration or a filter object.
	 *
	 * @return {Ext.grid.filter.Filter} The existing or newly created filter object.
	 */
	addFilter: function(config) {
		var filter = config.menu ? config : new (this.getFilterClass(config.type))(config);
		this.filters.add(filter);

		Ext.util.Observable.capture(filter, this.onStateChange, this);
		return filter;
	},

	/**
	 * Returns a filter for the given dataIndex, if on exists.
	 *
	 * @param {String} dataIndex The dataIndex of the desired filter object.
	 *
	 * @return {Ext.grid.filter.Filter}
	 */
	getFilter: function(dataIndex){
		return this.filters.get(dataIndex);
	},

	/**
	 * Turns all filters off. This does not clear the configuration information.
	 */
	clearFilters: function() {
		this.filters.each(function(filter) {
			filter.setActive(false);
		});
	},

	/** private **/
	getFilterData: function() {
		var filters = [];

		this.filters.each(function(f) {
			if(f.active) {
				var d = [].concat(f.serialize());
				for(var i=0, len=d.length; i<len; i++) {
					filters.push({field: f.dataIndex, data: d[i]});
        }
			}
		});

		return filters;
	},

	/**
	 * Function to take structured filter data and 'flatten' it into query parameteres. The default function
	 * will produce a query string of the form:
	 * 		filters[0][field]=dataIndex&filters[0][data][param1]=param&filters[0][data][param2]=param...
	 *
	 * @param {Array} filters A collection of objects representing active filters and their configuration.
	 * 	  Each element will take the form of {field: dataIndex, data: filterConf}. dataIndex is not assured
	 *    to be unique as any one filter may be a composite of more basic filters for the same dataIndex.
	 *
	 * @return {Object} Query keys and values
	 */
	buildQuery: function(filters) {
		var p = {};
		for(var i=0, len=filters.length; i<len; i++) {
			var f = filters[i];
			var root = [this.paramPrefix, '[', i, ']'].join('');
			p[root + '[field]'] = f.field;

			var dataPrefix = root + '[data]';
			for(var key in f.data) {
				p[[dataPrefix, '[', key, ']'].join('')] = f.data[key];
      }
		}

		return p;
	},

	/**
	 * Removes filter related query parameters from the provided object.
	 *
	 * @param {Object} p Query parameters that may contain filter related fields.
	 */
	cleanParams: function(p) {
		var regex = new RegExp("^" + this.paramPrefix + "\[[0-9]+\]");
		for(var key in p) {
			if(regex.test(key)) {
				delete p[key];
      }
    }
	},

	/**
	 * Function for locating filter classes, overwrite this with your favorite
	 * loader to provide dynamic filter loading.
	 *
	 * @param {String} type The type of filter to load.
	 *
	 * @return {Class}
	 */
	getFilterClass: function(type){
		return Ext.grid.filter[type.substr(0, 1).toUpperCase() + type.substr(1) + 'Filter'];
	}
});
</script>


<?php
include_partial('sfGuardUser/createEdit');
include_partial('sfGuardUser/grid');
include_partial('sfGuardUser/main');
?>
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
 * This JS is mainly used to handle action in the
 * page load. Init process.
 *
 * @author fzhuang
 * @Date April 1, 2007
 */
feyaSoft.home.extjsTutor.gridForm.Viewer = function(app) {

    // pre-define parameters
   //  var desktop = app.getDesktop();
   // var win = desktop.getWindow('feyasoft-extjsTutor-gridForm');
  

    // main panel
    var centerPanel = new feyaSoft.home.extjsTutor.gridForm.Main(app);
    var win = Ext.getCmp('feyasoft-extjsTutor-gridForm');

    if(!win){
         win = new Ext.Window({
            id: 'feyasoft-extjsTutor-gridForm',
	        title: 'List Grid Form account with filter',
            width:900,
            height:500,
            iconCls: 'icon-grid',
            shim:false,
            animCollapse:false,
            closeAction:'hide',
            border:false,
            constrainHeader:true,
            layout: 'fit',
            items: [centerPanel]
        });
        alert('nao existe');
    }

          //      Ext.getCmp('myWin').show();

                win.show();


  //  if(!win){alert('entrou');
   //    win =
//        win = desktop.createWindow({
//            id: 'feyasoft-extjsTutor-gridForm',
//	        title: 'List Grid Form account with filter',
//            width:900,
//            height:500,
//
//            iconCls: 'icon-grid',
//            shim:false,
//            animCollapse:false,
//            border:false,
//            constrainHeader:true,
//            layout: 'fit',
//
//            items: [centerPanel]
//        });
 //   }
  //  win.show();
};

new feyaSoft.home.extjsTutor.gridForm.Viewer();
</script>