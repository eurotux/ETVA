/*
 * Adds reload button to combo
 * use: reload:true in config
 */
Ext.override(Ext.form.ComboBox, {


    initList : function(){
        if(!this.list){
            var cls = 'x-combo-list';

            this.list = new Ext.Layer({
                parentEl: this.getListParent(),
                shadow: this.shadow,
                cls: [cls, this.listClass].join(' '),
                constrain:false
            });

            var lw = this.listWidth || Math.max(this.wrap.getWidth(), this.minListWidth);
            this.list.setSize(lw, 0);
            this.list.swallowEvent('mousewheel');
            this.assetHeight = 0;
            if(this.syncFont !== false){
                this.list.setStyle('font-size', this.el.getStyle('font-size'));
            }
            if(this.title){
                this.header = this.list.createChild({cls:cls+'-hd', html: this.title});
                this.assetHeight += this.header.getHeight();
            }

            if(this.reload){
                if(!this.header){
                    this.header = this.list.createChild({cls:cls+'-hd',html:' '});
                }
                this.refreshTool = Ext.DomHelper.insertFirst(this.header,{html:'Reload'},true);
                //this.refreshTool.addClass('x-item-disabled');
                this.refreshTool.addClassOnOver('x-tool-refresh-over');
             //   this.refreshTool.dom.qtip = 'Reload list';
                this.refreshTool.on('click',function(){
                    this.refreshTool.removeClass('x-tool-refresh-over');
                    this.store.load();
                },this);
                this.assetHeight += this.header.getHeight();
            }

            this.innerList = this.list.createChild({cls:cls+'-inner'});
            this.mon(this.innerList, 'mouseover', this.onViewOver, this);
            this.mon(this.innerList, 'mousemove', this.onViewMove, this);
            this.innerList.setWidth(lw - this.list.getFrameWidth('lr'));

            if(this.pageSize){
                this.footer = this.list.createChild({cls:cls+'-ft'});
                this.pageTb = new Ext.PagingToolbar({
                    store: this.store,
                    pageSize: this.pageSize,
                    renderTo:this.footer
                });
                this.assetHeight += this.footer.getHeight();
            }

            if(!this.tpl){
                /**
                * @cfg {String/Ext.XTemplate} tpl <p>The template string, or {@link Ext.XTemplate} instance to
                * use to display each item in the dropdown list. The dropdown list is displayed in a
                * DataView. See {@link #view}.</p>
                * <p>The default template string is:</p><pre><code>
                  '&lt;tpl for=".">&lt;div class="x-combo-list-item">{' + this.displayField + '}&lt;/div>&lt;/tpl>'
                * </code></pre>
                * <p>Override the default value to create custom UI layouts for items in the list.
                * For example:</p><pre><code>
                  '&lt;tpl for=".">&lt;div ext:qtip="{state}. {nick}" class="x-combo-list-item">{state}&lt;/div>&lt;/tpl>'
                * </code></pre>
                * <p>The template <b>must</b> contain one or more substitution parameters using field
                * names from the Combo's</b> {@link #store Store}. In the example above an
                * <pre>ext:qtip</pre> attribute is added to display other fields from the Store.</p>
                * <p>To preserve the default visual look of list items, add the CSS class name
                * <pre>x-combo-list-item</pre> to the template's container element.</p>
                * <p>Also see {@link #itemSelector} for additional details.</p>
                */
                this.tpl = '<tpl for="."><div class="'+cls+'-item">{' + this.displayField + '}</div></tpl>';
                /**
                 * @cfg {String} itemSelector
                 * <p>A simple CSS selector (e.g. div.some-class or span:first-child) that will be
                 * used to determine what nodes the {@link #view Ext.DataView} which handles the dropdown
                 * display will be working with.</p>
                 * <p><b>Note</b>: this setting is <b>required</b> if a custom XTemplate has been
                 * specified in {@link #tpl} which assigns a class other than <pre>'x-combo-list-item'</pre>
                 * to dropdown list items</b>
                 */
            }

            /**
            * The {@link Ext.DataView DataView} used to display the ComboBox's options.
            * @type Ext.DataView
            */
            this.view = new Ext.DataView({
                applyTo: this.innerList,
                tpl: this.tpl,
                singleSelect: true,
                selectedClass: this.selectedClass,
                itemSelector: this.itemSelector || '.' + cls + '-item',
                emptyText: this.listEmptyText
            });

            this.mon(this.view, 'click', this.onViewClick, this);

            this.bindStore(this.store, true);

            if(this.resizable){
                this.resizer = new Ext.Resizable(this.list,  {
                   pinned:true, handles:'se'
                });
                this.mon(this.resizer, 'resize', function(r, w, h){
                    this.maxHeight = h-this.handleHeight-this.list.getFrameWidth('tb')-this.assetHeight;
                    this.listWidth = w;
                    this.innerList.setWidth(w - this.list.getFrameWidth('lr'));
                    this.restrictHeight();
                }, this);

                this[this.pageSize?'footer':'innerList'].setStyle('margin-bottom', this.handleHeight+'px');
            }
        }
    }


});
