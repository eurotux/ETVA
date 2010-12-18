Ext.ns('Ext.ux.grid');

/**
     * @class Ext.ux.grid.GridFilters
     * @extends Ext.util.Observable
     * <p>GridFilter is a plugin (<code>ptype='gridfilters'</code>) for grids that
     * allow for a slightly more robust representation of filtering than what is
     * provided by the default store.</p>
     * <p>Filtering is adjusted by the user using the grid's column header menu
     * (this menu can be disabled through configuration). Through this menu users
     * can configure, enable, and disable filters for each column.</p>
     * <p><b><u>Features:</u></b></p>
     * <div class="mdetail-params"><ul>
     * <li><b>Filtering implementations</b> :
     * <div class="sub-desc">
     * Default filtering for Strings, Numeric Ranges, Date Ranges, Lists (which can
     * be backed by a Ext.data.Store), and Boolean. Additional custom filter types
     * and menus are easily created by extending Ext.ux.grid.filter.Filter.
     * </div></li>
     * <li><b>Graphical indicators</b> :
     * <div class="sub-desc">
     * Columns that are filtered have {@link #filterCls a configurable css class}
     * applied to the column headers.
     * </div></li>
     * <li><b>Paging</b> :
     * <div class="sub-desc">
     * If specified as a plugin to the grid's configured PagingToolbar, the current page
     * will be reset to page 1 whenever you update the filters.
     * </div></li>
     * <li><b>Automatic Reconfiguration</b> :
     * <div class="sub-desc">
     * Filters automatically reconfigure when the grid 'reconfigure' event fires.
     * </div></li>
     * <li><b>Stateful</b> :
     * Filter information will be persisted across page loads by specifying a
     * <code>stateId</code> in the Grid configuration.
     * <div class="sub-desc">
     * The filter collection binds to the
     * <code>{@link Ext.grid.GridPanel#beforestaterestore beforestaterestore}</code>
     * and <code>{@link Ext.grid.GridPanel#beforestatesave beforestatesave}</code>
     * events in order to be stateful.
     * </div></li>
     * <li><b>Grid Changes</b> :
     * <div class="sub-desc"><ul>
     * <li>A <code>filters</code> <i>property</i> is added to the grid pointing to
     * this plugin.</li>
     * <li>A <code>filterupdate</code> <i>event</i> is added to the grid and is
     * fired upon onStateChange completion.</li>
     * </ul></div></li>
     * <li><b>Server side code examples</b> :
     * <div class="sub-desc"><ul>
     * <li><a href="http://www.vinylfox.com/extjs/grid-filter-php-backend-code.php">PHP</a> - (Thanks VinylFox)</li>
     * <li><a href="http://extjs.com/forum/showthread.php?p=77326#post77326">Ruby on Rails</a> - (Thanks Zyclops)</li>
     * <li><a href="http://extjs.com/forum/showthread.php?p=176596#post176596">Ruby on Rails</a> - (Thanks Rotomaul)</li>
     * <li><a href="http://www.debatablybeta.com/posts/using-extjss-grid-filtering-with-django/">Python</a> - (Thanks Matt)</li>
     * <li><a href="http://mcantrell.wordpress.com/2008/08/22/extjs-grids-and-grails/">Grails</a> - (Thanks Mike)</li>
     * </ul></div></li>
     * </ul></div>
     * <p><b><u>Example usage:</u></b></p>
     * <pre><code>
var store = new Ext.data.GroupingStore({
...
});

var filters = new Ext.ux.grid.GridFilters({
autoReload: false, //don&#39;t reload automatically
local: true, //only filter locally
// filters may be configured through the plugin,
// or in the column definition within the column model configuration
filters: [{
type: 'numeric',
dataIndex: 'id'
}, {
type: 'string',
dataIndex: 'name'
}, {
type: 'numeric',
dataIndex: 'price'
}, {
type: 'date',
dataIndex: 'dateAdded'
}, {
type: 'list',
dataIndex: 'size',
options: ['extra small', 'small', 'medium', 'large', 'extra large'],
phpMode: true
}, {
type: 'boolean',
dataIndex: 'visible'
}]
});
var cm = new Ext.grid.ColumnModel([{
...
}]);

var grid = new Ext.grid.GridPanel({
ds: store,
cm: cm,
view: new Ext.grid.GroupingView(),
plugins: [filters],
height: 400,
width: 700,
bbar: new Ext.PagingToolbar({
store: store,
pageSize: 15,
plugins: [filters] //reset page to page 1 if filters change
})
});

store.load({params: {start: 0, limit: 15}});

// a filters property is added to the grid
grid.filters
     * </code></pre>
     */
    Ext.ux.grid.GridFilters = Ext.extend(Ext.util.Observable, {
        /**
         * @cfg {Boolean} autoReload
         * Defaults to true, reloading the datasource when a filter change happens.
         * Set this to false to prevent the datastore from being reloaded if there
         * are changes to the filters.  See <code>{@link updateBuffer}</code>.
         */
        autoReload : true,
        /**
         * @cfg {Boolean} encode
         * Specify true for {@link #buildQuery} to use Ext.util.JSON.encode to
         * encode the filter query parameter sent with a remote request.
         * Defaults to false.
         */
        /**
         * @cfg {Array} filters
         * An Array of filters config objects. Refer to each filter type class for
         * configuration details specific to each filter type. Filters for Strings,
         * Numeric Ranges, Date Ranges, Lists, and Boolean are the standard filters
         * available.
         */
        /**
         * @cfg {String} filterCls
         * The css class to be applied to column headers with active filters.
         * Defaults to <tt>'ux-filterd-column'</tt>.
         */
        filterCls : 'ux-filtered-column',
        /**
         * @cfg {Boolean} local
         * <tt>true</tt> to use Ext.data.Store filter functions (local filtering)
         * instead of the default (<tt>false</tt>) server side filtering.
         */
        local : false,
        /**
         * @cfg {String} menuFilterText
         * defaults to <tt>'Filters'</tt>.
         */
        menuFilterText : 'Filters',
        /**
         * @cfg {String} paramPrefix
         * The url parameter prefix for the filters.
         * Defaults to <tt>'filter'</tt>.
         */
        paramPrefix : 'filter',
        /**
         * @cfg {Boolean} showMenu
         * Defaults to true, including a filter submenu in the default header menu.
         */
        showMenu : true,
        /**
         * @cfg {String} stateId
         * Name of the value to be used to store state information.
         */
        stateId : undefined,
        /**
         * @cfg {Integer} updateBuffer
         * Number of milliseconds to defer store updates since the last filter change.
         */
        updateBuffer : 500,

        /** @private */
        constructor : function (config) {
            this.deferredUpdate = new Ext.util.DelayedTask(this.reload, this);
            this.filters = new Ext.util.MixedCollection();
            this.filters.getKey = function (o) {
                return o ? o.dataIndex : null;
            };
            this.addFilters(config.filters);
            delete config.filters;
            Ext.apply(this, config);
        },

        /** @private */
        init : function (grid) {
            if (grid instanceof Ext.grid.GridPanel) {
                this.grid = grid;

                this.bindStore(this.grid.getStore(), true);

                this.grid.filters = this;

                this.grid.addEvents({'filterupdate': true});

                grid.on({
                    scope: this,
                    beforestaterestore: this.applyState,
                    beforestatesave: this.saveState,
                    beforedestroy: this.destroy,
                    reconfigure: this.onReconfigure
                });

                if (grid.rendered){
                    this.onRender();
                } else {
                    grid.on({
                        scope: this,
                        single: true,
                        render: this.onRender
                    });
                }

            } else if (grid instanceof Ext.PagingToolbar) {
                this.toolbar = grid;
            }
        },

        /**
         * @private
         * Handler for the grid's beforestaterestore event (fires before the state of the
         * grid is restored).
         * @param {Object} grid The grid object
         * @param {Object} state The hash of state values returned from the StateProvider.
         */
        applyState : function (grid, state) {
            var key, filter;
            this.applyingState = true;
            this.clearFilters();
            if (state.filters) {
                for (key in state.filters) {
                    filter = this.filters.get(key);
                    if (filter) {
                        filter.setValue(state.filters[key]);
                        filter.setActive(true);
                    }
                }
            }
            this.deferredUpdate.cancel();
            if (this.local) {
                this.reload();
            }
            delete this.applyingState;
        },

        /**
         * Saves the state of all active filters
         * @param {Object} grid
         * @param {Object} state
         * @return {Boolean}
         */
        saveState : function (grid, state) {
            var filters = {};
            this.filters.each(function (filter) {
                if (filter.active) {
                    filters[filter.dataIndex] = filter.getValue();
                }
            });
            return (state.filters = filters);
        },

        /**
         * @private
         * Handler called when the grid is rendered
         */
        onRender : function () {
            this.grid.getView().on('refresh', this.onRefresh, this);
            this.createMenu();
        },

        /**
         * @private
         * Handler called by the grid 'beforedestroy' event
         */
        destroy : function () {
            this.removeAll();
            this.purgeListeners();

            if(this.filterMenu){
                Ext.menu.MenuMgr.unregister(this.filterMenu);
                this.filterMenu.destroy();
                this.filterMenu = this.menu.menu = null;
            }
        },

        /**
         * Remove all filters, permanently destroying them.
         */
        removeAll : function () {
            if(this.filters){
                Ext.destroy.apply(Ext, this.filters.items);
                // remove all items from the collection
                this.filters.clear();
            }
        },


        /**
         * Changes the data store bound to this view and refreshes it.
         * @param {Store} store The store to bind to this view
         */
        bindStore : function(store, initial){
            if(!initial && this.store){
                if (this.local) {
                    store.un('load', this.onLoad, this);
                } else {
                    store.un('beforeload', this.onBeforeLoad, this);
                }
            }
            if(store){
                if (this.local) {
                    store.on('load', this.onLoad, this);
                } else {
                    store.on('beforeload', this.onBeforeLoad, this);
                }
            }
            this.store = store;
        },

        /**
         * @private
         * Handler called when the grid reconfigure event fires
         */
        onReconfigure : function () {
            this.bindStore(this.grid.getStore());
            this.store.clearFilter();
            this.removeAll();
            this.addFilters(this.grid.getColumnModel());
            this.updateColumnHeadings();
        },

        createMenu : function () {
            var view = this.grid.getView(),
            hmenu = view.hmenu;

            if (this.showMenu && hmenu) {

                this.sep  = hmenu.addSeparator();
                this.filterMenu = new Ext.menu.Menu({
                    id: this.grid.id + '-filters-menu'
                });
                this.menu = hmenu.add({
                    checked: false,
                    itemId: 'filters',
                    text: this.menuFilterText,
                    menu: this.filterMenu
                });

                this.menu.on({
                    scope: this,
                    checkchange: this.onCheckChange,
                    beforecheckchange: this.onBeforeCheck
                });
                hmenu.on('beforeshow', this.onMenu, this);
            }
            this.updateColumnHeadings();
        },

        /**
         * @private
         * Get the filter menu from the filters MixedCollection based on the clicked header
         */
        getMenuFilter : function () {
            var view = this.grid.getView();
            if (!view || view.hdCtxIndex === undefined) {
                return null;
            }
            return this.filters.get(
            view.cm.config[view.hdCtxIndex].dataIndex
        );
        },

        /**
         * @private
         * Handler called by the grid's hmenu beforeshow event
         */
        onMenu : function (filterMenu) {
            var filter = this.getMenuFilter();

            if (filter) {
                /*
TODO: lazy rendering
if (!filter.menu) {
filter.menu = filter.createMenu();
}
                 */
                this.menu.menu = filter.menu;
                this.menu.setChecked(filter.active, false);
                // disable the menu if filter.disabled explicitly set to true
                this.menu.setDisabled(filter.disabled === true);
            }

            this.menu.setVisible(filter !== undefined);
            this.sep.setVisible(filter !== undefined);
        },

        /** @private */
        onCheckChange : function (item, value) {
            this.getMenuFilter().setActive(value);
        },

        /** @private */
        onBeforeCheck : function (check, value) {
            return !value || this.getMenuFilter().isActivatable();
        },

        /**
         * @private
         * Handler for all events on filters.
         * @param {String} event Event name
         * @param {Object} filter Standard signature of the event before the event is fired
         */
        onStateChange : function (event, filter) {
            if (event === 'serialize') {
                return;
            }

            if (filter == this.getMenuFilter()) {
                this.menu.setChecked(filter.active, false);
            }

            if ((this.autoReload || this.local) && !this.applyingState) {
                this.deferredUpdate.delay(this.updateBuffer);
            }
            this.updateColumnHeadings();

            if (!this.applyingState) {
                this.grid.saveState();
            }
            this.grid.fireEvent('filterupdate', this, filter);
        },

        /**
         * @private
         * Handler for store's beforeload event when configured for remote filtering
         * @param {Object} store
         * @param {Object} options
         */
        onBeforeLoad : function (store, options) {
            options.params = options.params || {};
            this.cleanParams(options.params);
            var params = this.buildQuery(this.getFilterData());
            Ext.apply(options.params, params);
        },

        /**
         * @private
         * Handler for store's load event when configured for local filtering
         * @param {Object} store
         * @param {Object} options
         */
        onLoad : function (store, options) {
            store.filterBy(this.getRecordFilter());
        },

        /**
         * @private
         * Handler called when the grid's view is refreshed
         */
        onRefresh : function () {
            this.updateColumnHeadings();
        },

        /**
         * Update the styles for the header row based on the active filters
         */
        updateColumnHeadings : function () {
            var view = this.grid.getView(),
            hds, i, len, filter;
            if (view.mainHd) {
                hds = view.mainHd.select('td').removeClass(this.filterCls);
                for (i = 0, len = view.cm.config.length; i < len; i++) {
                    filter = this.getFilter(view.cm.config[i].dataIndex);
                    if (filter && filter.active) {
                        hds.item(i).addClass(this.filterCls);
                    }
                }
            }
        },

        /** @private */
        reload : function () {
            if (this.local) {
                this.grid.store.clearFilter(true);
                this.grid.store.filterBy(this.getRecordFilter());
            } else {
                var start,
                store = this.grid.store;
                this.deferredUpdate.cancel();
                if (this.toolbar) {
                    start = store.paramNames.start;
                    if (store.lastOptions && store.lastOptions.params && store.lastOptions.params[start]) {
                        store.lastOptions.params[start] = 0;
                    }
                }
                store.reload();
            }
        },

        /**
         * Method factory that generates a record validator for the filters active at the time
         * of invokation.
         * @private
         */
        getRecordFilter : function () {
            var f = [], len, i;
            this.filters.each(function (filter) {
                if (filter.active) {
                    f.push(filter);
                }
            });

            len = f.length;
            return function (record) {
                for (i = 0; i < len; i++) {
                    if (!f[i].validateRecord(record)) {
                        return false;
                    }
                }
                return true;
            };
        },

        /**
         * Adds a filter to the collection and observes it for state change.
         * @param {Object/Ext.ux.grid.filter.Filter} config A filter configuration or a filter object.
         * @return {Ext.ux.grid.filter.Filter} The existing or newly created filter object.
         */
        addFilter : function (config) {
            var Cls = this.getFilterClass(config.type),
            filter = config.menu ? config : (new Cls(config));
            this.filters.add(filter);

            Ext.util.Observable.capture(filter, this.onStateChange, this);
            return filter;
        },

        /**
         * Adds filters to the collection.
         * @param {Array/Ext.grid.ColumnModel} filters Either an Array of
         * filter configuration objects or an Ext.grid.ColumnModel.  The columns
         * of a passed Ext.grid.ColumnModel will be examined for a <code>filter</code>
         * property and, if present, will be used as the filter configuration object.
         */
        addFilters : function (filters) {
            if (filters) {
                var i, len, filter, cm = false, dI;
                if (filters instanceof Ext.grid.ColumnModel) {
                    filters = filters.config;
                    cm = true;
                }
                for (i = 0, len = filters.length; i < len; i++) {
                    filter = false;
                    if (cm) {
                        dI = filters[i].dataIndex;
                        filter = filters[i].filter || filters[i].filterable;
                        if (filter){
                            filter = (filter === true) ? {} : filter;
                            Ext.apply(filter, {dataIndex:dI});
                            // filter type is specified in order of preference:
                            //     filter type specified in config
                            //     type specified in store's field's type config
                            filter.type = filter.type || this.store.fields.get(dI).type;
                        }
                    } else {
                        filter = filters[i];
                    }
                    // if filter config found add filter for the column
                    if (filter) {
                        this.addFilter(filter);
                    }
                }
            }
        },

        /**
         * Returns a filter for the given dataIndex, if one exists.
         * @param {String} dataIndex The dataIndex of the desired filter object.
         * @return {Ext.ux.grid.filter.Filter}
         */
        getFilter : function (dataIndex) {
            return this.filters.get(dataIndex);
        },

        /**
         * Turns all filters off. This does not clear the configuration information
         * (see {@link #removeAll}).
         */
        clearFilters : function () {
            this.filters.each(function (filter) {
                filter.setActive(false);
            });
        },

        /**
         * Returns an Array of the currently active filters.
         * @return {Array} filters Array of the currently active filters.
         */
        getFilterData : function () {
            var filters = [], i, len;

            this.filters.each(function (f) {
                if (f.active) {
                    var d = [].concat(f.serialize());
                    for (i = 0, len = d.length; i < len; i++) {
                        filters.push({
                            field: f.dataIndex,
                            data: d[i]
                        });
                    }
                }
            });
            return filters;
        },

        /**
         * Function to take the active filters data and build it into a query.
         * The format of the query depends on the <code>{@link #encode}</code>
         * configuration:
         * <div class="mdetail-params"><ul>
         *
         * <li><b><tt>false</tt></b> : <i>Default</i>
         * <div class="sub-desc">
         * Flatten into query string of the form (assuming <code>{@link #paramPrefix}='filters'</code>:
         * <pre><code>
filters[0][field]="someDataIndex"&
filters[0][data][comparison]="someValue1"&
filters[0][data][type]="someValue2"&
filters[0][data][value]="someValue3"&
         * </code></pre>
         * </div></li>
         * <li><b><tt>true</tt></b> :
         * <div class="sub-desc">
         * JSON encode the filter data
         * <pre><code>
filters[0][field]="someDataIndex"&
filters[0][data][comparison]="someValue1"&
filters[0][data][type]="someValue2"&
filters[0][data][value]="someValue3"&
         * </code></pre>
         * </div></li>
         * </ul></div>
         * Override this method to customize the format of the filter query for remote requests.
         * @param {Array} filters A collection of objects representing active filters and their configuration.
         * 	  Each element will take the form of {field: dataIndex, data: filterConf}. dataIndex is not assured
         *    to be unique as any one filter may be a composite of more basic filters for the same dataIndex.
         * @return {Object} Query keys and values
         */
        buildQuery : function (filters) {
            var p = {}, i, f, root, dataPrefix, key, tmp,
            len = filters.length;

            if (!this.encode){
                for (i = 0; i < len; i++) {
                    f = filters[i];
                    root = [this.paramPrefix, '[', i, ']'].join('');
                    p[root + '[field]'] = f.field;

                    dataPrefix = root + '[data]';
                    for (key in f.data) {
                        p[[dataPrefix, '[', key, ']'].join('')] = f.data[key];
                    }
                }
            } else {
                tmp = [];
                for (i = 0; i < len; i++) {
                    f = filters[i];
                    tmp.push(Ext.apply(
                    {},
                    {field: f.field},
                    f.data
                ));
                }
                // only build if there is active filter
                if (tmp.length > 0){
                    p[this.paramPrefix] = Ext.util.JSON.encode(tmp);
                }
            }
            return p;
        },

        /**
         * Removes filter related query parameters from the provided object.
         * @param {Object} p Query parameters that may contain filter related fields.
         */
        cleanParams : function (p) {
            // if encoding just delete the property
            if (this.encode) {
                delete p[this.paramPrefix];
                // otherwise scrub the object of filter data
            } else {
                var regex, key;
                regex = new RegExp('^' + this.paramPrefix + '\[[0-9]+\]');
                for (key in p) {
                    if (regex.test(key)) {
                        delete p[key];
                    }
                }
            }
        },

        /**
         * Function for locating filter classes, overwrite this with your favorite
         * loader to provide dynamic filter loading.
         * @param {String} type The type of filter to load ('Filter' is automatically
         * appended to the passed type; eg, 'string' becomes 'StringFilter').
         * @return {Class} The Ext.ux.grid.filter.Class
         */
        getFilterClass : function (type) {
            // map the supported Ext.data.Field type values into a supported filter
            switch(type) {
                case 'auto':
                    type = 'string';
                    break;
                case 'int':
                case 'float':
                    type = 'numeric';
                    break;
            }
            return Ext.ux.grid.filter[type.substr(0, 1).toUpperCase() + type.substr(1) + 'Filter'];
        }
    });

    // register ptype
    Ext.preg('gridfilters', Ext.ux.grid.GridFilters);




/**
     * @class Ext.ux.grid.RowExpander
     * @extends Ext.util.Observable
     * Plugin (ptype = 'rowexpander') that adds the ability to have a Column in a grid which enables
     * a second row body which expands/contracts.  The expand/contract behavior is configurable to react
     * on clicking of the column, double click of the row, and/or hitting enter while a row is selected.
     *
     * @ptype rowexpander
     */
    Ext.ux.grid.RowExpander = Ext.extend(Ext.util.Observable, {
        /**
         * @cfg {Boolean} expandOnEnter
         * <tt>true</tt> to toggle selected row(s) between expanded/collapsed when the enter
         * key is pressed (defaults to <tt>true</tt>).
         */
        expandOnEnter : true,
        /**
         * @cfg {Boolean} expandOnDblClick
         * <tt>true</tt> to toggle a row between expanded/collapsed when double clicked
         * (defaults to <tt>true</tt>).
         */
        expandOnDblClick : true,

        header : '',
        width : 20,
        sortable : false,
        fixed : true,
        menuDisabled : true,
        dataIndex : '',
        id : 'expander',
        lazyRender : true,
        enableCaching : true,

        constructor: function(config){
            Ext.apply(this, config);

            this.addEvents({
                /**
                 * @event beforeexpand
                 * Fires before the row expands. Have the listener return false to prevent the row from expanding.
                 * @param {Object} this RowExpander object.
                 * @param {Object} Ext.data.Record Record for the selected row.
                 * @param {Object} body body element for the secondary row.
                 * @param {Number} rowIndex The current row index.
                 */
                beforeexpand: true,
                /**
                 * @event expand
                 * Fires after the row expands.
                 * @param {Object} this RowExpander object.
                 * @param {Object} Ext.data.Record Record for the selected row.
                 * @param {Object} body body element for the secondary row.
                 * @param {Number} rowIndex The current row index.
                 */
                expand: true,
                /**
                 * @event beforecollapse
                 * Fires before the row collapses. Have the listener return false to prevent the row from collapsing.
                 * @param {Object} this RowExpander object.
                 * @param {Object} Ext.data.Record Record for the selected row.
                 * @param {Object} body body element for the secondary row.
                 * @param {Number} rowIndex The current row index.
                 */
                beforecollapse: true,
                /**
                 * @event collapse
                 * Fires after the row collapses.
                 * @param {Object} this RowExpander object.
                 * @param {Object} Ext.data.Record Record for the selected row.
                 * @param {Object} body body element for the secondary row.
                 * @param {Number} rowIndex The current row index.
                 */
                collapse: true
            });

            Ext.ux.grid.RowExpander.superclass.constructor.call(this);

            if(this.tpl){
                if(typeof this.tpl == 'string'){
                    this.tpl = new Ext.Template(this.tpl);
                }
                this.tpl.compile();
            }

            this.state = {};
            this.bodyContent = {};
        },

        getRowClass : function(record, rowIndex, p, ds){
            p.cols = p.cols-1;
            var content = this.bodyContent[record.id];
            if(!content && !this.lazyRender){
                content = this.getBodyContent(record, rowIndex);
            }
            if(content){
                p.body = content;
            }
            return this.state[record.id] ? 'x-grid3-row-expanded' : 'x-grid3-row-collapsed';
        },

        init : function(grid){
            this.grid = grid;

            var view = grid.getView();
            view.getRowClass = this.getRowClass.createDelegate(this);

            view.enableRowBody = true;


            grid.on('render', this.onRender, this);
            grid.on('destroy', this.onDestroy, this);
        },

        // @private
        onRender: function() {
            var grid = this.grid;
            var mainBody = grid.getView().mainBody;
            mainBody.on('mousedown', this.onMouseDown, this, {delegate: '.x-grid3-row-expander'});
            if (this.expandOnEnter) {
                this.keyNav = new Ext.KeyNav(this.grid.getGridEl(), {
                    'enter' : this.onEnter,
                    scope: this
                });
            }
            if (this.expandOnDblClick) {
                grid.on('rowdblclick', this.onRowDblClick, this);
            }
        },

        // @private
        onDestroy: function() {
            if(this.keyNav){
                this.keyNav.disable();
                delete this.keyNav;
            }
            /*
             * A majority of the time, the plugin will be destroyed along with the grid,
             * which means the mainBody won't be available. On the off chance that the plugin
             * isn't destroyed with the grid, take care of removing the listener.
             */
            var mainBody = this.grid.getView().mainBody;
            if(mainBody){
                mainBody.un('mousedown', this.onMouseDown, this);
            }
        },
        // @private
        onRowDblClick: function(grid, rowIdx, e) {
            this.toggleRow(rowIdx);
        },

        onEnter: function(e) {
            var g = this.grid;
            var sm = g.getSelectionModel();
            var sels = sm.getSelections();
            for (var i = 0, len = sels.length; i < len; i++) {
                var rowIdx = g.getStore().indexOf(sels[i]);
                this.toggleRow(rowIdx);
            }
        },

        getBodyContent : function(record, index){
            if(!this.enableCaching){
                return this.tpl.apply(record.data);
            }
            var content = this.bodyContent[record.id];
            if(!content){
                content = this.tpl.apply(record.data);
                this.bodyContent[record.id] = content;
            }
            return content;
        },

        onMouseDown : function(e, t){
            e.stopEvent();
            var row = e.getTarget('.x-grid3-row');
            this.toggleRow(row);
        },

        renderer : function(v, p, record){
            p.cellAttr = 'rowspan="2"';
            return '<div class="x-grid3-row-expander">&#160;</div>';
        },

        beforeExpand : function(record, body, rowIndex){
            if(this.fireEvent('beforeexpand', this, record, body, rowIndex) !== false){
                if(this.tpl && this.lazyRender){
                    body.innerHTML = this.getBodyContent(record, rowIndex);
                }
                return true;
            }else{
                return false;
            }
        },

        toggleRow : function(row){
            if(typeof row == 'number'){
                row = this.grid.view.getRow(row);
            }
            this[Ext.fly(row).hasClass('x-grid3-row-collapsed') ? 'expandRow' : 'collapseRow'](row);
        },

        expandRow : function(row){
            if(typeof row == 'number'){
                row = this.grid.view.getRow(row);
            }
            var record = this.grid.store.getAt(row.rowIndex);
            var body = Ext.DomQuery.selectNode('tr:nth(2) div.x-grid3-row-body', row);
            if(this.beforeExpand(record, body, row.rowIndex)){
                this.state[record.id] = true;
                Ext.fly(row).replaceClass('x-grid3-row-collapsed', 'x-grid3-row-expanded');
                this.fireEvent('expand', this, record, body, row.rowIndex);
            }
        },

        collapseRow : function(row){
            if(typeof row == 'number'){
                row = this.grid.view.getRow(row);
            }
            var record = this.grid.store.getAt(row.rowIndex);
            var body = Ext.fly(row).child('tr:nth(1) div.x-grid3-row-body', true);
            if(this.fireEvent('beforecollapse', this, record, body, row.rowIndex) !== false){
                this.state[record.id] = false;
                Ext.fly(row).replaceClass('x-grid3-row-expanded', 'x-grid3-row-collapsed');
                this.fireEvent('collapse', this, record, body, row.rowIndex);
            }
        }
    });

    Ext.preg('rowexpander', Ext.ux.grid.RowExpander);

    //backwards compat
    Ext.grid.RowExpander = Ext.ux.grid.RowExpander;

/**
 * @class Ext.ux.grid.RowEditor
 * @extends Ext.Panel
 * Plugin (ptype = 'roweditor') that adds the ability to rapidly edit full rows in a grid.
 * A validation mode may be enabled which uses AnchorTips to notify the user of all
 * validation errors at once.
 *
 * @ptype roweditor
 */
Ext.ux.grid.RowEditor = Ext.extend(Ext.Panel, {
    floating: true,
    shadow: false,
    layout: 'hbox',
    cls: 'x-small-editor',
    buttonAlign: 'center',
    baseCls: 'x-row-editor',
    elements: 'header,footer,body',
    frameWidth: 5,
    buttonPad: 3,
    clicksToEdit: 'auto',
    monitorValid: true,
    focusDelay: 250,
    errorSummary: true,

    defaults: {
        normalWidth: true
    },

    initComponent: function(){
        Ext.ux.grid.RowEditor.superclass.initComponent.call(this);
        this.addEvents(
            /**
             * @event beforeedit
             * Fired before the row editor is activated.
             * If the listener returns <tt>false</tt> the editor will not be activated.
             * @param {Ext.ux.grid.RowEditor} roweditor This object
             * @param {Number} rowIndex The rowIndex of the row just edited
             */
            'beforeedit',
            /**
             * @event validateedit
             * Fired after a row is edited and passes validation.
             * If the listener returns <tt>false</tt> changes to the record will not be set.
             * @param {Ext.ux.grid.RowEditor} roweditor This object
             * @param {Object} changes Object with changes made to the record.
             * @param {Ext.data.Record} r The Record that was edited.
             * @param {Number} rowIndex The rowIndex of the row just edited
             */
            'validateedit',
            /**
             * @event afteredit
             * Fired after a row is edited and passes validation.  This event is fired
             * after the store's update event is fired with this edit.
             * @param {Ext.ux.grid.RowEditor} roweditor This object
             * @param {Object} changes Object with changes made to the record.
             * @param {Ext.data.Record} r The Record that was edited.
             * @param {Number} rowIndex The rowIndex of the row just edited
             */
            'afteredit'
        );
    },

    init: function(grid){
        this.grid = grid;
        this.ownerCt = grid;
        if(this.clicksToEdit === 2){
            grid.on('rowdblclick', this.onRowDblClick, this);
        }else{
            grid.on('rowclick', this.onRowClick, this);
            if(Ext.isIE){
                grid.on('rowdblclick', this.onRowDblClick, this);
            }
        }

        // stopEditing without saving when a record is removed from Store.
        grid.getStore().on('remove', function() {
            this.stopEditing(false);
        },this);

        grid.on({
            scope: this,
            keydown: this.onGridKey,
            columnresize: this.verifyLayout,
            columnmove: this.refreshFields,
            reconfigure: this.refreshFields,
	    destroy : this.destroy,
            bodyscroll: {
                buffer: 250,
                fn: this.positionButtons
            }
        });
        grid.getColumnModel().on('hiddenchange', this.verifyLayout, this, {delay:1});
        grid.getView().on('refresh', this.stopEditing.createDelegate(this, []));
    },

    refreshFields: function(){
        this.initFields();
        this.verifyLayout();
    },

    listeners: {
            move: function(p){ this.resize(); },
            hide: function(p){
                var mainBody = this.grid.getView().mainBody;
                var lastRow = Ext.fly(this.grid.getView().getRow(this.grid.getStore().getCount()-1));
                mainBody.setHeight(lastRow.getBottom() - mainBody.getTop(),{
                    callback: function(){ mainBody.setHeight('auto'); }
                });
            },
            afterlayout: function(container, layout) { this.resize(); }
        },


    isDirty: function(){
        var dirty;
        this.items.each(function(f){
            if(String(this.values[f.id]) !== String(f.getValue())){
                dirty = true;
                return false;
            }
        }, this);
        return dirty;
    },

    startEditing: function(rowIndex, doFocus){
        if(this.editing && this.isDirty()){
            this.showTooltip('You need to commit or cancel your changes');
            return;
        }
        if(Ext.isObject(rowIndex)){
            rowIndex = this.grid.getStore().indexOf(rowIndex);
        }
        if(this.fireEvent('beforeedit', this, rowIndex) !== false){
            this.editing = true;
            var g = this.grid, view = g.getView();
            var row = view.getRow(rowIndex);
            var record = g.store.getAt(rowIndex);
            this.record = record;
            this.rowIndex = rowIndex;
            this.values = {};
            if(!this.rendered){
                this.render(view.getEditorParent());
            }
            var w = Ext.fly(row).getWidth();
            this.setSize(w);
            if(!this.initialized){
                this.initFields();
            }
            var cm = g.getColumnModel(), fields = this.items.items, f, val;
            for(var i = 0, len = cm.getColumnCount(); i < len; i++){
                val = this.preEditValue(record, cm.getDataIndex(i));
                f = fields[i];
                f.setValue(val);
                this.values[f.id] = Ext.isEmpty(val) ? '' : val;
            }
            this.verifyLayout(true);
            if(!this.isVisible()){
                this.setPagePosition(Ext.fly(row).getXY());
            } else{
                this.el.setXY(Ext.fly(row).getXY(), {duration:0.15});
            }
            if(!this.isVisible()){
                this.show().doLayout();
            }
            if(doFocus !== false){
                this.doFocus.defer(this.focusDelay, this);
            }
        }
    },

    stopEditing : function(saveChanges){
        this.editing = false;
        if(!this.isVisible()){
            return;
        }
        if(saveChanges === false || !this.isValid()){
            this.hide();
            return;
        }
        var changes = {}, r = this.record, hasChange = false;
        var cm = this.grid.colModel, fields = this.items.items;
        for(var i = 0, len = cm.getColumnCount(); i < len; i++){
            if(!cm.isHidden(i)){
                var dindex = cm.getDataIndex(i);
                if(!Ext.isEmpty(dindex)){
                    var oldValue = r.data[dindex];
                    var value = this.postEditValue(fields[i].getValue(), oldValue, r, dindex);
                    if(String(oldValue) !== String(value)){
                        changes[dindex] = value;
                        hasChange = true;
                    }
                }
            }
        }
        if(hasChange && this.fireEvent('validateedit', this, changes, r, this.rowIndex) !== false){
            r.beginEdit();
            for(var k in changes){
                if(changes.hasOwnProperty(k)){
                    r.set(k, changes[k]);
                }
            }
            r.endEdit();
            this.fireEvent('afteredit', this, changes, r, this.rowIndex);
        }
        this.hide();
    },

    verifyLayout: function(force){
        if(this.el && (this.isVisible() || force === true)){
            var row = this.grid.getView().getRow(this.rowIndex);
            this.setSize(Ext.fly(row).getWidth(), Ext.isIE ? Ext.fly(row).getHeight() + 9 : undefined);
            var cm = this.grid.colModel, fields = this.items.items;
            for(var i = 0, len = cm.getColumnCount(); i < len; i++){
                if(!cm.isHidden(i)){
                    var adjust = 0;
                    if(i === (len - 1)){
                        adjust += 3; // outer padding
                    } else{
                        adjust += 1;
                    }
                    fields[i].show();
                    fields[i].setWidth(cm.getColumnWidth(i) - adjust);
                } else{
                    fields[i].hide();
                }
            }
            this.doLayout();
            this.positionButtons();
        }
    },

    slideHide : function(){
        this.hide();
    },

    initFields: function(){
        var cm = this.grid.getColumnModel(), pm = Ext.layout.ContainerLayout.prototype.parseMargins;
        this.removeAll(false);
        for(var i = 0, len = cm.getColumnCount(); i < len; i++){
            var c = cm.getColumnAt(i);
            var ed = c.getEditor();
            if(!ed){
                ed = c.displayEditor || new Ext.form.DisplayField();
            }
            if(i == 0){
                ed.margins = pm('0 1 2 1');
            } else if(i == len - 1){
                ed.margins = pm('0 0 2 1');
            } else{
                ed.margins = pm('0 1 2');
            }
            ed.setWidth(cm.getColumnWidth(i));
            ed.column = c;
            if(ed.ownerCt !== this){
                ed.on('focus', this.ensureVisible, this);
                ed.on('specialkey', this.onKey, this);
            }
            this.insert(i, ed);
        }
        this.initialized = true;
    },

    onKey: function(f, e){
        if(e.getKey() === e.ENTER){
            this.stopEditing(true);
            e.stopPropagation();
        }
    },

    onGridKey: function(e){
        if(e.getKey() === e.ENTER && !this.isVisible()){
            var r = this.grid.getSelectionModel().getSelected();
            if(r){
                var index = this.grid.store.indexOf(r);
                this.startEditing(index);
                e.stopPropagation();
            }
        }
    },

    ensureVisible: function(editor){
        if(this.isVisible()){
             this.grid.getView().ensureVisible(this.rowIndex, this.grid.colModel.getIndexById(editor.column.id), true);
        }
    },

    onRowClick: function(g, rowIndex, e){
        if(this.clicksToEdit == 'auto'){
            var li = this.lastClickIndex;
            this.lastClickIndex = rowIndex;
            if(li != rowIndex && !this.isVisible()){
                return;
            }
        }
        this.startEditing(rowIndex, false);
        this.doFocus.defer(this.focusDelay, this, [e.getPoint()]);
    },

    onRowDblClick: function(g, rowIndex, e){
        this.startEditing(rowIndex, false);
        this.doFocus.defer(this.focusDelay, this, [e.getPoint()]);
    },

    onRender: function(){
        Ext.ux.grid.RowEditor.superclass.onRender.apply(this, arguments);
        this.el.swallowEvent(['keydown', 'keyup', 'keypress']);
        this.btns = new Ext.Panel({
            baseCls: 'x-plain',
            cls: 'x-btns',
            elements:'body',
            layout: 'table',
            width: (this.minButtonWidth * 2) + (this.frameWidth * 2) + (this.buttonPad * 4), // width must be specified for IE
            items: [{
                ref: 'saveBtn',
                itemId: 'saveBtn',
                xtype: 'button',
                text: this.saveText || 'Save',
                width: this.minButtonWidth,
                handler: this.stopEditing.createDelegate(this, [true])
            }, {
                xtype: 'button',                
                text: this.cancelText || 'Cancel',
                width: this.minButtonWidth,
                handler: this.stopEditing.createDelegate(this, [false])
            }]
        });
        this.btns.render(this.bwrap);
    },

    afterRender: function(){
        Ext.ux.grid.RowEditor.superclass.afterRender.apply(this, arguments);
        this.positionButtons();
        if(this.monitorValid){
            this.startMonitoring();
        }
    },

    onShow: function(){
        if(this.monitorValid){
            this.startMonitoring();
        }
        Ext.ux.grid.RowEditor.superclass.onShow.apply(this, arguments);
    },

    onHide: function(){
        Ext.ux.grid.RowEditor.superclass.onHide.apply(this, arguments);
        this.stopMonitoring();
        this.grid.getView().focusRow(this.rowIndex);
    },

    positionButtons: function(){
        if(this.btns){
            var h = this.el.dom.clientHeight;
            var view = this.grid.getView();
            var scroll = view.scroller.dom.scrollLeft;
            var width =  view.mainBody.getWidth();
            var bw = this.btns.getWidth();
            this.btns.el.shift({left: (width/2)-(bw/2)+scroll, top: h - 2, stopFx: true, duration:0.2});
        }
    },

    // private
    preEditValue : function(r, field){
        var value = r.data[field];
        return this.autoEncode && typeof value === 'string' ? Ext.util.Format.htmlDecode(value) : value;
    },

    // private
    postEditValue : function(value, originalValue, r, field){
        return this.autoEncode && typeof value == 'string' ? Ext.util.Format.htmlEncode(value) : value;
    },

    doFocus: function(pt){
        if(this.isVisible()){
            var index = 0;
            if(pt){
                index = this.getTargetColumnIndex(pt);
            }
            var cm = this.grid.getColumnModel();
            for(var i = index||0, len = cm.getColumnCount(); i < len; i++){
                var c = cm.getColumnAt(i);
                if(!c.hidden && c.getEditor()){
                    c.getEditor().focus();
                    break;
                }
            }
        }
    },

    getTargetColumnIndex: function(pt){
        var grid = this.grid, v = grid.view;
        var x = pt.left;
        var cms = grid.colModel.config;
        var i = 0, match = false;
        for(var len = cms.length, c; c = cms[i]; i++){
            if(!c.hidden){
                if(Ext.fly(v.getHeaderCell(i)).getRegion().right >= x){
                    match = i;
                    break;
                }
            }
        }
        return match;
    },

    startMonitoring : function(){
        if(!this.bound && this.monitorValid){
            this.bound = true;
            Ext.TaskMgr.start({
                run : this.bindHandler,
                interval : this.monitorPoll || 200,
                scope: this
            });
        }
    },

    stopMonitoring : function(){
        this.bound = false;
        if(this.tooltip){
            this.tooltip.hide();
        }
    },

    isValid: function(){
        var valid = true;
        this.items.each(function(f){
            if(!f.isValid(true)){
                valid = false;
                return false;
            }
        });
        return valid;
    },

    // private
    bindHandler : function(){
        if(!this.bound){
            return false; // stops binding
        }
        var valid = this.isValid();
        if(!valid && this.errorSummary){
            this.showTooltip(this.getErrorText().join(''));
        }
        this.btns.saveBtn.setDisabled(!valid);
        this.fireEvent('validation', this, valid);
    },

    showTooltip: function(msg){
        var t = this.tooltip;
        if(!t){
            t = this.tooltip = new Ext.ToolTip({
                maxWidth: 600,
                cls: 'errorTip',
                width: 300,
                title: 'Errors',
                autoHide: false,
                anchor: 'left',
                anchorToTarget: true,
                mouseOffset: [40,0]
            });
        }
        var v = this.grid.getView(),
            top = parseInt(this.el.dom.style.top, 10),
            scroll = v.scroller.dom.scrollTop,
            h = this.el.getHeight();

        if(top + h >= scroll){
            t.initTarget(this.items.last().getEl());
            if(!t.rendered){
                t.show();
                t.hide();
            }
            t.body.update(msg);
            t.doAutoWidth();
            t.show();
        }else if(t.rendered){
            t.hide();
        }
    },

    getErrorText: function(){
        var data = ['<ul>'];
        this.items.each(function(f){
            if(!f.isValid(true)){
                data.push('<li>', f.activeError, '</li>');
            }
        });
        data.push('</ul>');
        return data;
    },
     resize: function() {
            var row = Ext.fly(this.grid.getView().getRow(this.rowIndex)).getBottom();
            var lastRow = Ext.fly(this.grid.getView().getRow(this.grid.getStore().getCount()-1)).getBottom();
            var mainBody = this.grid.getView().mainBody;
            var h = Ext.max([row + this.btns.getHeight() + 10, lastRow]) - mainBody.getTop();
            mainBody.setHeight(h,true);
        }

});
Ext.preg('roweditor', Ext.ux.grid.RowEditor);

Ext.override(Ext.form.Field, {
    markInvalid : function(msg){
        if(!this.rendered || this.preventMark){ // not rendered
            return;
        }
        msg = msg || this.invalidText;

        var mt = this.getMessageHandler();
        if(mt){
            mt.mark(this, msg);
        }else if(this.msgTarget){
            this.el.addClass(this.invalidClass);
            var t = Ext.getDom(this.msgTarget);
            if(t){
                t.innerHTML = msg;
                t.style.display = this.msgDisplay;
            }
        }
        this.activeError = msg;
        this.fireEvent('invalid', this, msg);
    }
});

Ext.override(Ext.ToolTip, {
    doAutoWidth : function(){
        var bw = this.body.getTextWidth();
        if(this.title){
            bw = Math.max(bw, this.header.child('span').getTextWidth(this.title));
        }
        bw += this.getFrameWidth() + (this.closable ? 20 : 0) + this.body.getPadding("lr") + 20;
        this.setWidth(bw.constrain(this.minWidth, this.maxWidth));

        // IE7 repaint bug on initial show
        if(Ext.isIE7 && !this.repainted){
            this.el.repaint();
            this.repainted = true;
        }
    }
});





/**
 * Ext.ux.grid.RowSelectionPaging plugin for Ext.grid.GridPanel
 * A grid plugin that preserves row selections across paging / filtering of the store.
 *
 * @author  Joeri Sebrechts
 * @date    February 26, 2009
 *
 * @class Ext.ux.grid.RowSelectionPaging
 * @extends Ext.util.Observable
 */

Ext.ux.grid.RowSelectionPaging = function(config) {
    Ext.apply(this, config);
};
Ext.extend(Ext.ux.grid.RowSelectionPaging, Ext.util.Observable, {
    init: function(grid) {
       this.grid = grid;
       this.selections = []; // array of selected records
       this.selected = {}; // hash mapping record id to selected state
       grid.on('render', function() {
         // attach an interceptor for the selModel's onRefresh handler
         this.grid.view.un('refresh', this.grid.selModel.onRefresh, this.grid.selModel);
         this.grid.view.on('refresh', this.onViewRefresh, this );
         // add a handler to detect when the user changes the selection
         this.grid.selModel.on('rowselect', this.onRowSelect, this );
         this.grid.selModel.on('rowdeselect', this.onRowDeselect, this);
         // and patch selModel to detect selection cleared events
         var scope = this;
         this.selModelClearSelections = this.grid.selModel.clearSelections;
         this.grid.selModel.clearSelections = function(fast) {
            scope.selModelClearSelections.call(this, fast);
            scope.onSelectionClear();
         };
       }, this);
    }, // end init

    // private
    onViewRefresh: function() {
       this.ignoreSelectionChanges = true;
       // explicitly refresh the selection model
       this.grid.selModel.onRefresh();
       // selection changed from view updates, restore full selection
       var ds = this.grid.getStore();
       var newSel = [];
       for (var i = ds.getCount() - 1; i >= 0; i--) {
          if (this.selected[ds.getAt(i).id]) {
             newSel.push(i);
          }
       }
       this.grid.selModel.selectRows(newSel, false);
       this.ignoreSelectionChanges = false;
    }, // end onViewRefresh

    // private
    onSelectionClear: function() {
       if (! this.ignoreSelectionChanges) {
          // selection cleared by user
          // also called internally when the selection replaces the old selection
          this.selections = [];
          this.selected = {};
       }
    }, // end onSelectionClear

    // private
    onRowSelect: function(sm, i, rec) {
       if (! this.ignoreSelectionChanges) {
          if (!this.selected[rec.id])
          {
             this.selections.push(rec);
             this.selected[rec.id] = true;
          }
       }
    }, // end onRowSelect

    // private
    onRowDeselect: function(sm, i, rec) {
       if (!this.ignoreSelectionChanges) {
          if (this.selected[rec.id]) {
             for (var i = this.selections.length - 1; i >= 0; i--) {
                if (this.selections[i].id == rec.id) {
                   this.selections.splice(i, 1);
                   this.selected[rec.id] = false;
                   break;
                }
             }
          }
       }
    }, // end onRowDeselect

    /**
     * Clears selections across all pages
     */
    clearSelections: function() {
       this.selections = [];
       this.selected = {};
       this.onViewRefresh();
    }, // end clearSelections

    /**
     * Returns the selected records for all pages
     * @return {Array} Array of selected records
     */
    getSelections: function() {
       return [].concat(this.selections);
    } // end getSelections
});









// vim: ts=4:sw=4:nu:fdc=4:nospell
/*global Ext */
/**
 * @class Ext.ux.grid.RowActions
 * @extends Ext.util.Observable
 *
 * RowActions plugin for Ext grid. Contains renderer for icons and fires events when an icon is clicked.
 * CSS rules from Ext.ux.RowActions.css are mandatory
 *
 * Important general information: Actions are identified by iconCls. Wherever an <i>action</i>
 * is referenced (event argument, callback argument), the iconCls of clicked icon is used.
 * In other words, action identifier === iconCls.
 *
 * @author    Ing. Jozef SakÃ¡loÅ¡
 * @copyright (c) 2008, by Ing. Jozef SakÃ¡loÅ¡
 * @date      22. March 2008
 * @version   1.0
 * @revision  $Id: Ext.ux.grid.RowActions.js 713 2009-05-18 23:40:07Z jozo $
 *
 * @license Ext.ux.grid.RowActions is licensed under the terms of
 * the Open Source LGPL 3.0 license.  Commercial use is permitted to the extent
 * that the code/component(s) do NOT become part of another Open Source or Commercially
 * licensed development library or toolkit without explicit permission.
 *
 * <p>License details: <a href="http://www.gnu.org/licenses/lgpl.html"
 * target="_blank">http://www.gnu.org/licenses/lgpl.html</a></p>
 *
 * @forum     29961
 * @demo      http://rowactions.extjs.eu
 * @download
 * <ul>
 * <li><a href="http://rowactions.extjs.eu/rowactions.tar.bz2">rowactions.tar.bz2</a></li>
 * <li><a href="http://rowactions.extjs.eu/rowactions.tar.gz">rowactions.tar.gz</a></li>
 * <li><a href="http://rowactions.extjs.eu/rowactions.zip">rowactions.zip</a></li>
 * </ul>
 *
 * @donate
 * <form action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_blank">
 * <input type="hidden" name="cmd" value="_s-xclick">
 * <input type="hidden" name="hosted_button_id" value="3430419">
 * <input type="image" src="https://www.paypal.com/en_US/i/btn/x-click-butcc-donate.gif"
 * border="0" name="submit" alt="PayPal - The safer, easier way to pay online.">
 * <img alt="" border="0" src="https://www.paypal.com/en_US/i/scr/pixel.gif" width="1" height="1">
 * </form>
 */

Ext.ns('Ext.ux.grid');

// add RegExp.escape if it has not been already added
if('function' !== typeof RegExp.escape) {
	RegExp.escape = function(s) {
		if('string' !== typeof s) {
			return s;
		}
		// Note: if pasting from forum, precede ]/\ with backslash manually
		return s.replace(/([.*+?\^=!:${}()|\[\]\/\\])/g, '\\$1');
	}; // eo function escape
}

/**
 * Creates new RowActions plugin
 * @constructor
 * @param {Object} config A config object
 */
Ext.ux.grid.RowActions = function(config) {
	Ext.apply(this, config);

	// {{{
	this.addEvents(
		/**
		 * @event beforeaction
		 * Fires before action event. Return false to cancel the subsequent action event.
		 * @param {Ext.grid.GridPanel} grid
		 * @param {Ext.data.Record} record Record corresponding to row clicked
		 * @param {String} action Identifies the action icon clicked. Equals to icon css class name.
		 * @param {Integer} rowIndex Index of clicked grid row
		 * @param {Integer} colIndex Index of clicked grid column that contains all action icons
		 */
		 'beforeaction'
		/**
		 * @event action
		 * Fires when icon is clicked
		 * @param {Ext.grid.GridPanel} grid
		 * @param {Ext.data.Record} record Record corresponding to row clicked
		 * @param {String} action Identifies the action icon clicked. Equals to icon css class name.
		 * @param {Integer} rowIndex Index of clicked grid row
		 * @param {Integer} colIndex Index of clicked grid column that contains all action icons
		 */
		,'action'
		/**
		 * @event beforegroupaction
		 * Fires before group action event. Return false to cancel the subsequent groupaction event.
		 * @param {Ext.grid.GridPanel} grid
		 * @param {Array} records Array of records in this group
		 * @param {String} action Identifies the action icon clicked. Equals to icon css class name.
		 * @param {String} groupId Identifies the group clicked
		 */
		,'beforegroupaction'
		/**
		 * @event groupaction
		 * Fires when icon in a group header is clicked
		 * @param {Ext.grid.GridPanel} grid
		 * @param {Array} records Array of records in this group
		 * @param {String} action Identifies the action icon clicked. Equals to icon css class name.
		 * @param {String} groupId Identifies the group clicked
		 */
		,'groupaction'
	);
	// }}}

	// call parent
	Ext.ux.grid.RowActions.superclass.constructor.call(this);
};

Ext.extend(Ext.ux.grid.RowActions, Ext.util.Observable, {

	// configuration options
	// {{{
	/**
	 * @cfg {Array} actions Mandatory. Array of action configuration objects. The action
	 * configuration object recognizes the following options:
	 * <ul class="list">
	 * <li style="list-style-position:outside">
	 *   {Function} <b>callback</b> (optional). Function to call if the action icon is clicked.
	 *   This function is called with same signature as action event and in its original scope.
	 *   If you need to call it in different scope or with another signature use
	 *   createCallback or createDelegate functions. Works for statically defined actions. Use
	 *   callbacks configuration options for store bound actions.
	 * </li>
	 * <li style="list-style-position:outside">
	 *   {Function} <b>cb</b> Shortcut for callback.
	 * </li>
	 * <li style="list-style-position:outside">
	 *   {String} <b>iconIndex</b> Optional, however either iconIndex or iconCls must be
	 *   configured. Field name of the field of the grid store record that contains
	 *   css class of the icon to show. If configured, shown icons can vary depending
	 *   of the value of this field.
	 * </li>
	 * <li style="list-style-position:outside">
	 *   {String} <b>iconCls</b> CSS class of the icon to show. It is ignored if iconIndex is
	 *   configured. Use this if you want static icons that are not base on the values in the record.
	 * </li>
	 * <li style="list-style-position:outside">
	 *   {Boolean} <b>hide</b> Optional. True to hide this action while still have a space in
	 *   the grid column allocated to it. IMO, it doesn't make too much sense, use hideIndex instead.
	 * </li>
	 * <li style="list-style-position:outside">
	 *   {String} <b>hideIndex</b> Optional. Field name of the field of the grid store record that
	 *   contains hide flag (falsie [null, '', 0, false, undefined] to show, anything else to hide).
	 * </li>
	 * <li style="list-style-position:outside">
	 *   {String} <b>qtipIndex</b> Optional. Field name of the field of the grid store record that
	 *   contains tooltip text. If configured, the tooltip texts are taken from the store.
	 * </li>
	 * <li style="list-style-position:outside">
	 *   {String} <b>tooltip</b> Optional. Tooltip text to use as icon tooltip. It is ignored if
	 *   qtipIndex is configured. Use this if you want static tooltips that are not taken from the store.
	 * </li>
	 * <li style="list-style-position:outside">
	 *   {String} <b>qtip</b> Synonym for tooltip
	 * </li>
	 * <li style="list-style-position:outside">
	 *   {String} <b>textIndex</b> Optional. Field name of the field of the grids store record
	 *   that contains text to display on the right side of the icon. If configured, the text
	 *   shown is taken from record.
	 * </li>
	 * <li style="list-style-position:outside">
	 *   {String} <b>text</b> Optional. Text to display on the right side of the icon. Use this
	 *   if you want static text that are not taken from record. Ignored if textIndex is set.
	 * </li>
	 * <li style="list-style-position:outside">
	 *   {String} <b>style</b> Optional. Style to apply to action icon container.
	 * </li>
	 * </ul>
	 */

	/**
	 * @cfg {String} actionEvent Event to trigger actions, e.g. click, dblclick, mouseover (defaults to 'click')
	 */
	 actionEvent:'click'
	/**
	 * @cfg {Boolean} autoWidth true to calculate field width for iconic actions only (defaults to true).
	 * If true, the width is calculated as {@link #widthSlope} * number of actions + {@link #widthIntercept}.
	 */
	,autoWidth:true

	/**
	 * @cfg {String} dataIndex - Do not touch!
	 * @private
	 */
	,dataIndex:''

	/**
	 * @cfg {Array} groupActions Array of action to use for group headers of grouping grids.
	 * These actions support static icons, texts and tooltips same way as {@link #actions}. There is one
	 * more action config option recognized:
	 * <ul class="list">
	 * <li style="list-style-position:outside">
	 *   {String} <b>align</b> Set it to 'left' to place action icon next to the group header text.
	 *   (defaults to undefined = icons are placed at the right side of the group header.
	 * </li>
	 * </ul>
	 */

	/**
	 * @cfg {Object} callbacks iconCls keyed object that contains callback functions. For example:
	 * <pre>
	 * callbacks:{
	 * &nbsp;    'icon-open':function(...) {...}
	 * &nbsp;   ,'icon-save':function(...) {...}
	 * }
	 * </pre>
	 */

	/**
	 * @cfg {String} header Actions column header
	 */
	,header:''

	/**
	 * @cfg {Boolean} isColumn
	 * Tell ColumnModel that we are column. Do not touch!
	 * @private
	 */
	,isColumn:true

	/**
	 * @cfg {Boolean} keepSelection
	 * Set it to true if you do not want action clicks to affect selected row(s) (defaults to false).
	 * By default, when user clicks an action icon the clicked row is selected and the action events are fired.
	 * If this option is true then the current selection is not affected, only the action events are fired.
	 */
	,keepSelection:false

	/**
	 * @cfg {Boolean} menuDisabled No sense to display header menu for this column
	 * @private
	 */
	,menuDisabled:true

	/**
	 * @cfg {Boolean} sortable Usually it has no sense to sort by this column
	 * @private
	 */
	,sortable:false

	/**
	 * @cfg {String} tplGroup Template for group actions
	 * @private
	 */
	,tplGroup:
		 '<tpl for="actions">'
		+'<div class="ux-grow-action-item<tpl if="\'right\'===align"> ux-action-right</tpl> '
		+'{cls}" style="{style}" qtip="{qtip}">{text}</div>'
		+'</tpl>'

	/**
	 * @cfg {String} tplRow Template for row actions
	 * @private
	 */
	,tplRow:
		 '<div class="ux-row-action">'
		+'<tpl for="actions">'
		+'<div class="ux-row-action-item {cls} <tpl if="text">'
		+'ux-row-action-text</tpl>" style="{hide}{style}" qtip="{qtip}">'
		+'<tpl if="text"><span qtip="{qtip}">{text}</span></tpl></div>'
		+'</tpl>'
		+'</div>'

	/**
	 * @cfg {String} hideMode How to hide hidden icons. Valid values are: 'visibility' and 'display'
	 * (defaluts to 'visibility'). If the mode is visibility the hidden icon is not visible but there
	 * is still blank space occupied by the icon. In display mode, the visible icons are shifted taking
	 * the space of the hidden icon.
	 */
	,hideMode:'visiblity'

	/**
	 * @cfg {Number} widthIntercept Constant used for auto-width calculation (defaults to 4).
	 * See {@link #autoWidth} for explanation.
	 */
	,widthIntercept:4

	/**
	 * @cfg {Number} widthSlope Constant used for auto-width calculation (defaults to 21).
	 * See {@link #autoWidth} for explanation.
	 */
	,widthSlope:21
	// }}}

	// methods
	// {{{
	/**
	 * Init function
	 * @param {Ext.grid.GridPanel} grid Grid this plugin is in
	 */
	,init:function(grid) {
		this.grid = grid;

		// the actions column must have an id for Ext 3.x
		this.id = this.id || Ext.id();

		// for Ext 3.x compatibility
		var lookup = grid.getColumnModel().lookup;
		delete(lookup[undefined]);
		lookup[this.id] = this;

		// {{{
		// setup template
		if(!this.tpl) {
			this.tpl = this.processActions(this.actions);

		} // eo template setup
		// }}}

		// calculate width
		if(this.autoWidth) {
			this.width =  this.widthSlope * this.actions.length + this.widthIntercept;
			this.fixed = true;
		}

		// body click handler
		var view = grid.getView();
		var cfg = {scope:this};
		cfg[this.actionEvent] = this.onClick;
		grid.afterRender = grid.afterRender.createSequence(function() {
			view.mainBody.on(cfg);
			grid.on('destroy', this.purgeListeners, this);
		}, this);

		// setup renderer
		if(!this.renderer) {
			this.renderer = function(value, cell, record, row, col, store) {
				cell.css += (cell.css ? ' ' : '') + 'ux-row-action-cell';
				return this.tpl.apply(this.getData(value, cell, record, row, col, store));
			}.createDelegate(this);
		}

		// actions in grouping grids support
		if(view.groupTextTpl && this.groupActions) {
			view.interceptMouse = view.interceptMouse.createInterceptor(function(e) {
				if(e.getTarget('.ux-grow-action-item')) {
					return false;
				}
			});
			view.groupTextTpl =
				 '<div class="ux-grow-action-text">' + view.groupTextTpl +'</div>'
				+this.processActions(this.groupActions, this.tplGroup).apply()
			;
		}

		// cancel click
		if(true === this.keepSelection) {
			grid.processEvent = grid.processEvent.createInterceptor(function(name, e) {
				if('mousedown' === name) {
					return !this.getAction(e);
				}
			}, this);
		}

	} // eo function init
	// }}}
	// {{{
	/**
	 * Returns data to apply to template. Override this if needed.
	 * @param {Mixed} value
	 * @param {Object} cell object to set some attributes of the grid cell
	 * @param {Ext.data.Record} record from which the data is extracted
	 * @param {Number} row row index
	 * @param {Number} col col index
	 * @param {Ext.data.Store} store object from which the record is extracted
	 * @return {Object} data to apply to template
	 */
	,getData:function(value, cell, record, row, col, store) {
		return record.data || {};
	} // eo function getData
	// }}}
	// {{{
	/**
	 * Processes actions configs and returns template.
	 * @param {Array} actions
	 * @param {String} template Optional. Template to use for one action item.
	 * @return {String}
	 * @private
	 */
	,processActions:function(actions, template) {
		var acts = [];

		// actions loop
		Ext.each(actions, function(a, i) {
			// save callback
			if(a.iconCls && 'function' === typeof (a.callback || a.cb)) {
				this.callbacks = this.callbacks || {};
				this.callbacks[a.iconCls] = a.callback || a.cb;
			}

			// data for intermediate template
			var o = {
				 cls:a.iconIndex ? '{' + a.iconIndex + '}' : (a.iconCls ? a.iconCls : '')
				,qtip:a.qtipIndex ? '{' + a.qtipIndex + '}' : (a.tooltip || a.qtip ? a.tooltip || a.qtip : '')
				,text:a.textIndex ? '{' + a.textIndex + '}' : (a.text ? a.text : '')
				,hide:a.hideIndex
					? '<tpl if="' + a.hideIndex + '">'
						+ ('display' === this.hideMode ? 'display:none' :'visibility:hidden') + ';</tpl>'
					: (a.hide ? ('display' === this.hideMode ? 'display:none' :'visibility:hidden;') : '')
				,align:a.align || 'right'
				,style:a.style ? a.style : ''
			};
			acts.push(o);

		}, this); // eo actions loop

		var xt = new Ext.XTemplate(template || this.tplRow);
		return new Ext.XTemplate(xt.apply({actions:acts}));

	} // eo function processActions
	// }}}
	,getAction:function(e) {
		var action = false;
		var t = e.getTarget('.ux-row-action-item');
		if(t) {
			action = t.className.replace(/ux-row-action-item /, '');
			if(action) {
				action = action.replace(/ ux-row-action-text/, '');
				action = action.trim();
			}
		}
		return action;
	} // eo function getAction
	// {{{
	/**
	 * Grid body actionEvent event handler
	 * @private
	 */
	,onClick:function(e, target) {

		var view = this.grid.getView();

		// handle row action click
		var row = e.getTarget('.x-grid3-row');
		var col = view.findCellIndex(target.parentNode.parentNode);
		var action = this.getAction(e);

//		var t = e.getTarget('.ux-row-action-item');
//		if(t) {
//			action = this.getAction(t);
//			action = t.className.replace(/ux-row-action-item /, '');
//			if(action) {
//				action = action.replace(/ ux-row-action-text/, '');
//				action = action.trim();
//			}
//		}
		if(false !== row && false !== col && false !== action) {
			var record = this.grid.store.getAt(row.rowIndex);

			// call callback if any
			if(this.callbacks && 'function' === typeof this.callbacks[action]) {
				this.callbacks[action](this.grid, record, action, row.rowIndex, col);
			}

			// fire events
			if(true !== this.eventsSuspended && false === this.fireEvent('beforeaction', this.grid, record, action, row.rowIndex, col)) {
				return;
			}
			else if(true !== this.eventsSuspended) {
				this.fireEvent('action', this.grid, record, action, row.rowIndex, col);
			}

		}

		// handle group action click
		t = e.getTarget('.ux-grow-action-item');
		if(t) {
			// get groupId
			var group = view.findGroup(target);
			var groupId = group ? group.id.replace(/ext-gen[0-9]+-gp-/, '') : null;

			// get matching records
			var records;
			if(groupId) {
				var re = new RegExp(RegExp.escape(groupId));
				records = this.grid.store.queryBy(function(r) {
					return r._groupId.match(re);
				});
				records = records ? records.items : [];
			}
			action = t.className.replace(/ux-grow-action-item (ux-action-right )*/, '');

			// call callback if any
			if('function' === typeof this.callbacks[action]) {
				this.callbacks[action](this.grid, records, action, groupId);
			}

			// fire events
			if(true !== this.eventsSuspended && false === this.fireEvent('beforegroupaction', this.grid, records, action, groupId)) {
				return false;
			}
			this.fireEvent('groupaction', this.grid, records, action, groupId);
		}
	} // eo function onClick
	// }}}

});

// registre xtype
Ext.reg('rowactions', Ext.ux.grid.RowActions);

// eof




 /*!
 * Ext JS Library 3.0+
 * Copyright(c) 2006-2009 Ext JS, LLC
 * licensing@extjs.com
 * http://www.extjs.com/license
 */
//Ext.ns('Ext.ux.grid');

/**
 * @class Ext.ux.grid.CheckColumn
 * @extends Object
 * GridPanel plugin to add a column with check boxes to a grid.
 * <p>Example usage:</p>
 * <pre><code>
// create the column
var checkColumn = new Ext.grid.CheckColumn({
   header: 'Indoor?',
   dataIndex: 'indoor',
   id: 'check',
   width: 55
});

// add the column to the column model
var cm = new Ext.grid.ColumnModel([{
       header: 'Foo',
       ...
    },
    checkColumn
]);

// create the grid
var grid = new Ext.grid.EditorGridPanel({
    ...
    cm: cm,
    plugins: [checkColumn], // include plugin
    ...
});
 * </code></pre>
 * In addition to storing a Boolean value within the record data, this
 * class toggles a css class between <tt>'x-grid3-check-col'</tt> and
 * <tt>'x-grid3-check-col-on'</tt> to alter the background image used for
 * a column.
 */
Ext.ux.grid.CheckColumn = function(config){
    Ext.apply(this, config);
    if(!this.id){
        this.id = Ext.id();
    }
    this.renderer = this.renderer.createDelegate(this);
};

Ext.ux.grid.CheckColumn.prototype ={
    init : function(grid){
        this.grid = grid;
        this.grid.on('render', function(){
            var view = this.grid.getView();
            view.mainBody.on('mousedown', this.onMouseDown, this);
        }, this);
    },

    onMouseDown : function(e, t){
        if(this.editable && t.className && t.className.indexOf('x-grid3-cc-'+this.id) != -1){
            e.stopEvent();
            var index = this.grid.getView().findRowIndex(t);
            var record = this.grid.store.getAt(index);
            record.set(this.dataIndex, !record.data[this.dataIndex]);
        }
    },

    renderer : function(v, p, record){
        p.css += ' x-grid3-check-col-td';
        return '<div class="x-grid3-check-col'+(v?'-on':'')+' x-grid3-cc-'+this.id+'"></div>';
    }
};

// register ptype
Ext.preg('checkcolumn', Ext.ux.grid.CheckColumn);

// backwards compat
Ext.grid.CheckColumn = Ext.ux.grid.CheckColumn;


/*
     * function to delete multiple rows selected
     */
    Ext.ns("Grid.util");

    Grid.util.DeleteItem = function(config) {

        var panel = Ext.getCmp(config.panel);
        var m = panel.getSelectionModel().getSelections();
        if(m.length > 0)
        {
            // ask user confirm to delete
            Ext.Msg.confirm('Message',
            'Do you really want to delete them?',
            function(btn) {
                if(btn == 'yes')
                {
                    var items = [];

                    for(var i = 0, len = m.length; i < len; i++){
                        items.push(m[i]);

                    }
                    panel.deleteData(items);
                }
            }
        );
        }
        else
        {
            Ext.MessageBox.alert('Error',
            'To process delete action, please select at least one item to continue'
        );
        }

    };




  /*
   *
   * Toolbar with refresh button and total store size
   */
  Ext.ux.grid.TotalCountBar = Ext.extend(Ext.Toolbar, {

    /**
     * @cfg {String} displayMsg
     * The paging status message to display (defaults to <tt>'Displaying {0} - {1} of {2}'</tt>).
     * Note that this string is formatted using the braced numbers <tt>{0}-{2}</tt> as tokens
     * that are replaced by the values for start, end and total respectively. These tokens should
     * be preserved when overriding this string if showing those values is desired.
     */
    displayMsg : 'Displaying {2} items',
    /**
     * @cfg {String} emptyMsg
     * The message to display when no records are found (defaults to 'No data to display')
     */
    emptyMsg : 'No data to display',


    /**
     * @cfg {String} refreshText
     * The quicktip text displayed for the Refresh button (defaults to <tt>'Refresh'</tt>).
     * <b>Note</b>: quick tips must be initialized for the quicktip to show.
     */
    refreshText : 'Refresh',



    initComponent : function(){
        
        
        this.items = [];
        

        if(this.displayInfo){            
            this.items.push(this.displayItem = new Ext.Toolbar.TextItem({}));
        }
        
        var pagingItems = ['->',this.refresh = new Ext.Toolbar.Button({
            tooltip: this.refreshText,
            overflowText: this.refreshText,
            iconCls: 'x-tbar-loading',
            text:this.refreshText,
            handler: this.refresh,
            scope: this
        })];

        this.items.push(pagingItems);

        Ext.ux.grid.TotalCountBar.superclass.initComponent.call(this);

        this.on('afterlayout', this.onLoad, this, {single: true});

        this.bindStore(this.store);
    },

    // private
    updateInfo : function(){
        if(this.displayItem){
            var count = this.store.getCount();
            var msg = count == 0 ?
                this.emptyMsg :
                String.format(
                    this.displayMsg,
                    1, count, this.store.getTotalCount()
                );
            this.displayItem.setText(msg);
        }
    },

    // private
    onLoad : function(){

        this.refresh.enable();
        this.updateInfo();
    },


    // private
    onLoadError : function(){
        if(!this.rendered){
            return;
        }
        this.refresh.enable();
    },

    // private
    beforeLoad : function(){
        if(this.rendered && this.refresh){
            this.refresh.disable();
        }
    },

    // private
    doLoad : function(){
        this.store.load();
    },


    /**
     * Refresh the current page, has the same effect as clicking the 'refresh' button.
     */
    refresh : function(){
        this.doLoad();
    },

    /**
     * Binds the paging toolbar to the specified {@link Ext.data.Store}
     * @param {Store} store The store to bind to this toolbar
     * @param {Boolean} initial (Optional) true to not remove listeners
     */
    bindStore : function(store, initial){
        var doLoad;
        if(!initial && this.store){
            this.store.un('beforeload', this.beforeLoad, this);
            this.store.un('load', this.onLoad, this);
            this.store.un('exception', this.onLoadError, this);
            if(store !== this.store && this.store.autoDestroy){
                this.store.destroy();
            }
        }
        if(store){
            store = Ext.StoreMgr.lookup(store);
            store.on({
                scope: this,
                beforeload: this.beforeLoad,
                load: this.onLoad,
                exception: this.onLoadError
            });
            doLoad = store.getCount() > 0;
        }
        this.store = store;
        if(doLoad){
            this.onLoad();
        }
    },

    /**
     * Unbinds the paging toolbar from the specified {@link Ext.data.Store} <b>(deprecated)</b>
     * @param {Ext.data.Store} store The data store to unbind
     */
    unbind : function(store){
        this.bindStore(null);
    },

    /**
     * Binds the paging toolbar to the specified {@link Ext.data.Store} <b>(deprecated)</b>
     * @param {Ext.data.Store} store The data store to bind
     */
    bind : function(store){
        this.bindStore(store);
    },

    // private
    onDestroy : function(){
        this.bindStore(null);
        Ext.ux.grid.TotalCountBar.superclass.onDestroy.call(this);
    }
});

