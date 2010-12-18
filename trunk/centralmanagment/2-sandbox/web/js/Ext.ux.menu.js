/*!
     * Ext JS Library 3.0+
     * Copyright(c) 2006-2009 Ext JS, LLC
     * licensing@extjs.com
     * http://www.extjs.com/license
     */
    Ext.ns('Ext.ux.menu');

    /**
     * @class Ext.ux.menu.RangeMenu
     * @extends Ext.menu.Menu
     * Custom implementation of Ext.menu.Menu that has preconfigured
     * items for gt, lt, eq.
     * <p><b><u>Example Usage:</u></b></p>
     * <pre><code>

     * </code></pre>
     */
    Ext.ux.menu.RangeMenu = Ext.extend(Ext.menu.Menu, {

        constructor : function (config) {

            Ext.ux.menu.RangeMenu.superclass.constructor.call(this, config);

            this.addEvents(
            /**
             * @event update
             * Fires when a filter configuration has changed
             * @param {Ext.ux.grid.filter.Filter} this The filter object.
             */
            'update'
        );

            this.updateTask = new Ext.util.DelayedTask(this.fireUpdate, this);

            var i, len, item, cfg, Cls;

            for (i = 0, len = this.menuItems.length; i < len; i++) {
                item = this.menuItems[i];
                if (item !== '-') {
                    // defaults
                    cfg = {
                        itemId: 'range-' + item,
                        enableKeyEvents: true,
                        iconCls: this.iconCls[item] || 'no-icon',
                        listeners: {
                            scope: this,
                            keyup: this.onInputKeyUp
                        }
                    };
                    Ext.apply(
                    cfg,
                    // custom configs
                    Ext.applyIf(this.fields[item] || {}, this.fieldCfg[item]),
                    // configurable defaults
                    this.menuItemCfgs
                );
                    Cls = cfg.fieldCls || this.fieldCls;
                    item = this.fields[item] = new Cls(cfg);
                }
                this.add(item);
            }
        },

        /**
         * @private
         * called by this.updateTask
         */
        fireUpdate : function () {
            this.fireEvent('update', this);
        },

        /**
         * Get and return the value of the filter.
         * @return {String} The value of this filter
         */
        getValue : function () {
            var result = {}, key, field;
            for (key in this.fields) {
                field = this.fields[key];
                if (field.isValid() && String(field.getValue()).length > 0) {
                    result[key] = field.getValue();
                }
            }
            return result;
        },

        /**
         * Set the value of this menu and fires the 'update' event.
         * @param {Object} data The data to assign to this menu
         */
        setValue : function (data) {
            var key;
            for (key in this.fields) {
                this.fields[key].setValue(data[key] !== undefined ? data[key] : '');
            }
            this.fireEvent('update', this);
        },

        /**
         * @private
         * Handler method called when there is a keyup event on an input
         * item of this menu.
         */
        onInputKeyUp : function (field, e) {
            var k = e.getKey();
            if (k == e.RETURN && field.isValid()) {
                e.stopEvent();
                this.hide(true);
                return;
            }

            if (field == this.fields.eq) {
                if (this.fields.gt) {
                    this.fields.gt.setValue(null);
                }
                if (this.fields.lt) {
                    this.fields.lt.setValue(null);
                }
            }
            else {
                this.fields.eq.setValue(null);
            }

            // restart the timer
            this.updateTask.delay(this.updateBuffer);
        }
    });

    /*!
     * Ext JS Library 3.0+
     * Copyright(c) 2006-2009 Ext JS, LLC
     * licensing@extjs.com
     * http://www.extjs.com/license
     */
    Ext.namespace('Ext.ux.menu');

    /**
     * @class Ext.ux.menu.ListMenu
     * @extends Ext.menu.Menu
     * This is a supporting class for {@link Ext.ux.grid.filter.ListFilter}.
     * Although not listed as configuration options for this class, this class
     * also accepts all configuration options from {@link Ext.ux.grid.filter.ListFilter}.
     */
    Ext.ux.menu.ListMenu = Ext.extend(Ext.menu.Menu, {
        /**
         * @cfg {String} labelField
         * Defaults to 'text'.
         */
        labelField :  'text',
        /**
         * @cfg {String} paramPrefix
         * Defaults to 'Loading...'.
         */
        loadingText : 'Loading...',
        /**
         * @cfg {Boolean} loadOnShow
         * Defaults to true.
         */
        loadOnShow : true,
        /**
         * @cfg {Boolean} single
         * Specify true to group all items in this list into a single-select
         * radio button group. Defaults to false.
         */
        single : false,

        constructor : function (cfg) {
            this.selected = [];
            this.addEvents(
            /**
             * @event checkchange
             * Fires when there is a change in checked items from this list
             * @param {Object} item Ext.menu.CheckItem
             * @param {Object} checked The checked value that was set
             */
            'checkchange'
        );

            Ext.ux.menu.ListMenu.superclass.constructor.call(this, cfg = cfg || {});

            if(!cfg.store && cfg.options){
                var options = [];
                for(var i=0, len=cfg.options.length; i<len; i++){
                    var value = cfg.options[i];
                    switch(Ext.type(value)){
                        case 'array':  options.push(value); break;
                        case 'object': options.push([value.id, value[this.labelField]]); break;
                        case 'string': options.push([value, value]); break;
                    }
                }

                this.store = new Ext.data.Store({
                    reader: new Ext.data.ArrayReader({id: 0}, ['id', this.labelField]),
                    data:   options,
                    listeners: {
                        'load': this.onLoad,
                        scope:  this
                    }
                });
                this.loaded = true;
            } else {
                this.add({text: this.loadingText, iconCls: 'loading-indicator'});
                this.store.on('load', this.onLoad, this);
            }
        },

        destroy : function () {
            if (this.store) {
                this.store.destroy();
            }
            Ext.ux.menu.ListMenu.superclass.destroy.call(this);
        },

        /**
         * Lists will initially show a 'loading' item while the data is retrieved from the store.
         * In some cases the loaded data will result in a list that goes off the screen to the
         * right (as placement calculations were done with the loading item). This adapter will
         * allow show to be called with no arguments to show with the previous arguments and
         * thus recalculate the width and potentially hang the menu from the left.
         */
        show : function () {
            var lastArgs = null;
            return function(){
                if(arguments.length === 0){
                    Ext.ux.menu.ListMenu.superclass.show.apply(this, lastArgs);
                } else {
                    lastArgs = arguments;
                    if (this.loadOnShow && !this.loaded) {
                        this.store.load();
                    }
                    Ext.ux.menu.ListMenu.superclass.show.apply(this, arguments);
                }
            };
        }(),

        /** @private */
        onLoad : function (store, records) {
            var visible = this.isVisible();
            this.hide(false);

            this.removeAll(true);

            var gid = this.single ? Ext.id() : null;
            for(var i=0, len=records.length; i<len; i++){
                var item = new Ext.menu.CheckItem({
                    text:    records[i].get(this.labelField),
                    group:   gid,
                    checked: this.selected.indexOf(records[i].id) > -1,
                    hideOnClick: false});

                item.itemId = records[i].id;
                item.on('checkchange', this.checkChange, this);

                this.add(item);
            }

            this.loaded = true;

            if (visible) {
                this.show();
            }
            this.fireEvent('load', this, records);
        },

        /**
         * Get the selected items.
         * @return {Array} selected
         */
        getSelected : function () {
            return this.selected;
        },

        /** @private */
        setSelected : function (value) {
            value = this.selected = [].concat(value);

            if (this.loaded) {
                this.items.each(function(item){
                    item.setChecked(false, true);
                    for (var i = 0, len = value.length; i < len; i++) {
                        if (item.itemId == value[i]) {
                            item.setChecked(true, true);
                        }
                    }
                }, this);
            }
        },

        /**
         * Handler for the 'checkchange' event from an check item in this menu
         * @param {Object} item Ext.menu.CheckItem
         * @param {Object} checked The checked value that was set
         */
        checkChange : function (item, checked) {
            var value = [];
            this.items.each(function(item){
                if (item.checked) {
                    value.push(item.itemId);
                }
            },this);
            this.selected = value;

            this.fireEvent('checkchange', item, checked);
        }
    });
