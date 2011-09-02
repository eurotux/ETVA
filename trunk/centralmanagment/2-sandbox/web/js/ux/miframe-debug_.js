/*
 * ux.ManagedIFrame.* 2.0.1
 * Copyright(c) 2007-2009, Active Group, Inc.
 * licensing@theactivegroup.com
 *
 * http://licensing.theactivegroup.com
 *
 *
 */


 Ext.namespace('Ext.ux.plugin');
 Ext.onReady(function(){


    var CSS = Ext.util.CSS;
    if(CSS){
        CSS.getRule('.x-hide-nosize') || //already defined?
            CSS.createStyleSheet('.x-hide-nosize{height:0px!important;width:0px!important;border:none!important;zoom:1;}.x-hide-nosize * {height:0px!important;width:0px!important;border:none!important;zoom:1;}');
        CSS.refreshCache();
    }

});

(function(){

      var El = Ext.Element, A = Ext.lib.Anim, supr = El.prototype;
      var VISIBILITY = "visibility",
        DISPLAY = "display",
        HIDDEN = "hidden",
        NONE = "none";

      var fx = {};

      fx.El = {


	       setDisplayed : function(value) {
                var me=this;
                me.visibilityCls ? (me[value !== false ?'removeClass':'addClass'](me.visibilityCls)) :
	                supr.setDisplayed.call(me, value);
                return me;
	        },


	        isDisplayed : function() {
	            return !(this.hasClass(this.visibilityCls) || this.dom.style[DISPLAY] == NONE);
	        },
	        // private
	        fixDisplay : function(){
	            var me = this;
	            supr.fixDisplay.call(me);
                me.visibilityCls && me.removeClass(me.visibilityCls);
	        },


	        isVisible : function(deep) {
	            return this.visible ||
                   (!this.isStyle(VISIBILITY, HIDDEN) &&
                       this.visibilityCls ? !this.hasClass(this.visibilityCls) :!this.isStyle(DISPLAY, NONE));
	        }
	    };

        //Add basic capabilities to the Ext.Element.Flyweight class
        Ext.override(El.Flyweight, fx.El);


 Ext.ux.plugin.VisibilityMode = function(opt) {

    Ext.apply(this, opt||{});

    var CSS = Ext.util.CSS;

    if(CSS && !Ext.isIE && this.fixMaximizedWindow !== false && !Ext.ux.plugin.VisibilityMode.MaxWinFixed){
        //Prevent overflow:hidden (reflow) transitions when an Ext.Window is maximize.
        CSS.updateRule ( '.x-window-maximized-ct', 'overflow', '');
        Ext.ux.plugin.VisibilityMode.MaxWinFixed = true;  //only updates the CSS Rule once.
    }

   };


  Ext.extend(Ext.ux.plugin.VisibilityMode , Object, {


      bubble              :  true,


      fixMaximizedWindow  :  true,



      elements       :  null,



      visibilityCls   : 'x-hide-nosize',


      hideMode  :   'nosize' ,

      ptype     :  'uxvismode',

      init : function(c) {

        var hideMode = this.hideMode || c.hideMode,
            plugin = this,
            bubble = Ext.Container.prototype.bubble,
            changeVis = function(){

	            var els = [this.collapseEl, this.actionMode].concat(plugin.elements||[]);

	            Ext.each(els, function(el){
		            plugin.extend( this[el] || el );
	            },this);

	            var cfg = {
                    visFixed  : true,
                    animCollapse : false,
                    animFloat   : false,
		            hideMode  : hideMode,
		            defaults  : this.defaults || {}
	            };

	            cfg.defaults.hideMode = hideMode;

	            Ext.apply(this, cfg);
	            Ext.apply(this.initialConfig || {}, cfg);

            };

         c.on('render', function(){

            // Bubble up the layout and set the new
            // visibility mode on parent containers
            // which might also cause DOM reflow when
            // hidden or collapsed.
            if(plugin.bubble !== false && this.ownerCt){

               bubble.call(this.ownerCt, function(){
                  this.visFixed || this.on('afterlayout', changeVis, this, {single:true} );
               });
             }

             changeVis.call(this);

          }, c, {single:true});

     },

     extend : function(el, visibilityCls){
        el && Ext.each([].concat(el), function(e){

	        if(e && e.dom){
                 if('visibilityCls' in e)return;  //already applied or defined?
	             Ext.apply(e, fx.El);
	             e.visibilityCls = visibilityCls || this.visibilityCls;
	        }
        },this);
        return this;
     }

  });

  Ext.preg && Ext.preg('uxvismode', Ext.ux.plugin.VisibilityMode );

  Ext.provide && Ext.provide('uxvismode');
})();





 (function(){


   var El = Ext.Element, ElFrame, ELD = Ext.lib.Dom, A = Ext.lib.Anim;
   var emptyFn = function(){},
       OP = Object.prototype,
       OPString = OP.toString,
       HTMLDoc = '[object HTMLDocument]';


   var _documents= {
        $_top : {_elCache : El.cache,
                 _dataCache : El.dataCache
                 }
    };

    var resolveCache = ELD.resolveCache = function(doc, cacheId){
        doc = GETDOC(doc);

        //Use Ext.Element.cache for top-level document
        var c = (doc == document? '$_top' : cacheId);

        var cache = _documents[c] || null, d, win;
         //see if the document instance is managed by FRAME
        if(!cache && doc && (win = doc.parentWindow || doc.defaultView)){  //Is it a frame document
              if(d = win.frameElement){
                    c = d.id || d.name;  //the id of the frame is the cacheKey
                }
         }
         return cache ||
            _documents[c] ||
            (c ? _documents[c] = {_elCache : {} ,_dataCache : {} }: null);
     };

     var clearCache = ELD.clearCache = function(cacheId){
       delete  _documents[cacheId];
     };

   El.addMethods || ( El.addMethods = function(ov){ Ext.apply(El.prototype, ov||{}); });

   Ext.removeNode =  function(n){
         var dom = n ? n.dom || n : null;
         if(dom && dom.parentNode && dom.tagName != 'BODY'){
            var el, docCache = resolveCache(ELD.getDocument(dom));
            if(el = docCache._elCache[dom.id]){

                //clear out any references from the El.cache(s)
                el.dom && el.removeAllListeners();
                delete docCache._elCache[dom.id];
                delete docCache._dataCache[dom.id];
                el.dom && (el.dom = null);
                el = null;
            }

            if(Ext.isIE && !Ext.isIE8){
                var d = ELD.getDocument(dom).createElement('div');
                d.appendChild(dom);
                //d.innerHTML = '';  //either works equally well
                d.removeChild(dom);
                d = null;  //just dump the scratch DIV reference here.
            } else {
                var p = dom.parentNode;
                p.removeChild(dom);
                p = null;
            }
	      }
	      dom = null;
    };

     var overload = function(pfn, fn ){
           var f = typeof pfn === 'function' ? pfn : function t(){};
           var ov = f._ovl; //call signature hash
           if(!ov){
               ov = { base: f};
               ov[f.length|| 0] = f;
               f= function t(){  //the proxy stub
                  var o = arguments.callee._ovl;
                  var fn = o[arguments.length] || o.base;
                  //recursion safety
                  return fn && fn != arguments.callee ? fn.apply(this,arguments): undefined;
               };
           }
           var fnA = [].concat(fn);
           for(var i=0,l=fnA.length; i<l; i++){
             //ensures no duplicate call signatures, but last in rules!
             ov[fnA[i].length] = fnA[i];
           }
           f._ovl= ov;
           var t = null;
           return f;
       };

    Ext.applyIf( Ext, {
        overload : overload( overload,
           [
             function(fn){ return overload(null, fn);},
             function(obj, mname, fn){
                 return obj[mname] = overload(obj[mname],fn);}
          ]),

        isArray : function(v){
           return OPString.apply(v) == '[object Array]';
        },

        isObject:function(obj){
            return (obj !== null) && typeof obj == 'object';
        },


        isDocument : function(el, testOrigin){

            var test = OPString.call(el) == HTMLDoc || (el && el.nodeType == 9);
            if(test && testOrigin){
                try{
                    test = !!el.location;
                }
                catch(e){return false;}
            }
            return test;
        },

        isIterable : function(obj){
            //check for array or arguments
            if( obj === null || obj === undefined )return false;
            if(Ext.isArray(obj) || !!obj.callee || Ext.isNumber(obj.length) ) return true;

            return !!((/NodeList|HTMLCollection/i).test(OPString.call(obj)) || //check for node list type
              //NodeList has an item and length property
              //IXMLDOMNodeList has nextNode method, needs to be checked first.
             obj.nextNode || obj.item || false);
        },
        isElement : function(obj){
            return obj && Ext.type(obj)== 'element';
        },

        isEvent : function(obj){
            return OPString.apply(obj) == '[object Event]' || (Ext.isObject(obj) && !Ext.type(o.constructor) && (window.event && obj.clientX && obj.clientX == window.event.clientX));
        },

        isFunction: function(obj){
            return !!obj && typeof obj == 'function';
        },


        isEventSupported : function(evName, testEl){
            var TAGNAMES = {
              'select':'input',
              'change':'input',
              'submit':'form',
              'reset':'form',
              'load':'img',
              'error':'img',
              'abort':'img'
            }
            //Cached results
            var cache = {};
            //Get a tokenized string of the form nodeName:type
            var getKey = function(type, el){

                var tEl = Ext.getDom(el);

                return (tEl ?
                           (Ext.isElement(tEl) || Ext.isDocument(tEl) ?
                                tEl.nodeName.toLowerCase() :
                                    el.self ? '#window' : el || '#object')
                       : el || 'div') + ':' + type;
            };

            return function (evName, testEl) {
              var el, isSupported = false;
              var eventName = 'on' + evName;
              var tag = (testEl ? testEl : TAGNAMES[evName]) || 'div';
              var key = getKey(evName, tag);

              if(key in cache){
                //Use a previously cached result if available
                return cache[key];
              }

              el = Ext.isString(tag) ? document.createElement(tag): testEl;
              isSupported = (!!el && (eventName in el));

              isSupported || (isSupported = window.Event && !!(String(evName).toUpperCase() in window.Event));

              if (!isSupported && el) {
                el.setAttribute && el.setAttribute(eventName, 'return;');
                isSupported = Ext.isFunction(el[eventName]);
              }
              //save the cached result for future tests
              cache[key] = isSupported;
              el = null;
              return isSupported;
            };

        }()
    });



    var assertClass = function(el){

        return El[(el.tagName || '-').toUpperCase()] || El;

      };

    var libFlyweight;
    function fly(el, doc) {
        if (!libFlyweight) {
            libFlyweight = new Ext.Element.Flyweight();
        }
        libFlyweight.dom = Ext.getDom(el, doc);
        return libFlyweight;
    }


    Ext.apply(Ext, {


      get : El.get = function(el, doc){         //document targeted
            if(!el ){ return null; }
            doc || (doc = document);
            if(!Ext.isDocument(doc)) {
                return this.get(el); //a bad get signature
             }
            var ex, elm, id, cache = resolveCache(doc);
            if(Ext.isDocument(el)){

                if(!Ext.isDocument(el, true)){ return false; }  //is it accessible

                // create a bogus element object representing the document object
                if(cache._elCache['$_doc']){
                    return cache._elCache['$_doc'];
                }
                var f = function(){};
                f.prototype = El.prototype;
                var docEl = new f();
                docEl.dom = el;
                docEl._isDoc = true;
                return cache._elCache['$_doc'] = docEl;
             }

             cache = cache._elCache;

             if(typeof el == "string"){ // element id

                elm = Ext.getDom(el,doc);

                if(!elm) return null;

                if(ex = cache[el]){
                    ex.dom = elm;
                }else{
                    ex = cache[el] = new (assertClass(elm))(elm, null, doc);
                }

                return ex;
             }else if(el.tagName){ // dom element

                doc = GETDOC(el);
                cache = resolveCache(doc);
                if(ex = cache[el.id || (el.id = Ext.id(el))]){
                    ex.dom = el;
                }else{
                    ex = cache[el.id] = new (assertClass(el))(el, null, doc);
                }

                return ex;
            }else if(el instanceof El || el instanceof El['IFRAME']){

                el.dom = doc.getElementById(el.id) || el.dom; // refresh dom element in case no longer valid,
                                                              // catch case where it hasn't been appended
                el.dom && (cache[el.id] = el); // in case it was created directly with Element(), let's cache it

                return el.dom ? el : null;
            }else if(el.isComposite){
                return el;

            }else if(Ext.isArray(el)){
                return Ext.get(doc,doc).select(el);
            }
           return null;

    },


     getDom : function(el, doc){
            if(!el){ return null;}
            return el.dom ? el.dom : (typeof el === 'string' ? (doc ||document).getElementById(el) : el);
        },

     getBody : function(doc){
            var D = ELD.getDocument(doc) || document;
            return Ext.get(D.body || D.documentElement, D);
       },

     getDoc :Ext.overload([
       Ext.getDoc,
       function(doc){ return Ext.get(doc,doc); }
       ])
   });

    var propCache = {},
        camelRe = /(-[a-z])/gi,
        camelFn = function(m, a){ return a.charAt(1).toUpperCase(); },
        opacityRe = /alpha\(opacity=(.*)\)/i,
        trimRe = /^\s+|\s+$/g,
        propFloat = Ext.isIE ? 'styleFloat' : 'cssFloat',
        view = document.defaultView,
        VISMODE = 'visibilityMode',
        ELDISPLAY = El.DISPLAY,
        PADDING = "padding",
        MARGIN = "margin",
        BORDER = "border",
        LEFT = "-left",
        RIGHT = "-right",
        TOP = "-top",
        BOTTOM = "-bottom",
        WIDTH = "-width",
        MATH = Math,
        HIDDEN = 'hidden',
        ISCLIPPED = 'isClipped',
        OVERFLOW = 'overflow',
        OVERFLOWX = 'overflow-x',
        OVERFLOWY = 'overflow-y',
        ORIGINALCLIP = 'originalClip',
        // special markup used throughout Ext when box wrapping elements
        borders = {l: BORDER + LEFT + WIDTH, r: BORDER + RIGHT + WIDTH, t: BORDER + TOP + WIDTH, b: BORDER + BOTTOM + WIDTH},
        paddings = {l: PADDING + LEFT, r: PADDING + RIGHT, t: PADDING + TOP, b: PADDING + BOTTOM},
        margins = {l: MARGIN + LEFT, r: MARGIN + RIGHT, t: MARGIN + TOP, b: MARGIN + BOTTOM},
        data = El.data,
        GETDOM = Ext.getDom,
        GET = Ext.get,
        DH = Ext.DomHelper,
        CSS = Ext.util.CSS;  //Not available in Ext Core.

    function chkCache(prop) {
        return propCache[prop] || (propCache[prop] = prop == 'float' ? propFloat : prop.replace(camelRe, camelFn));
    };


    El.addMethods({

        getDocument : function(){
           return GETDOC(this);
        },




	    remove : function(cleanse, deep){
	      if(this.dom){
	        this._mask && this.unmask(true);
	        this._mask = null;
	        cleanse && this.cleanse(true, deep);
	        Ext.removeNode(this);
	        this.dom = null;  //clear ANY DOM references
	        delete this.dom;
	      }
	    },


	    cleanse : function(forceReclean, deep){
	        if(this.isCleansed && forceReclean !== true){
	            return this;
	        }
	        var d = this.dom, n = d.firstChild, nx;
	        while(d && n){
	             nx = n.nextSibling;
	             deep && Ext.fly(n, '_cleanser').cleanse(forceReclean, deep);
	             Ext.removeNode(n);
	             n = nx;
	         }
             delete El._flyweights['_cleanser']; //orphan reference cleanup
	         this.isCleansed = true;
	         return this;
	     },


        appendChild: function(el, doc){
            return GET(el, doc || this.getDocument()).appendTo(this);
        },


        appendTo: function(el, doc){
            GETDOM(el, doc || this.getDocument()).appendChild(this.dom);
            return this;
        },


        insertBefore: function(el, doc){
            (el = GETDOM(el, doc || this.getDocument())).parentNode.insertBefore(this.dom, el);
            return this;
        },


        insertAfter: function(el, doc){
            (el = GETDOM(el, doc || this.getDocument())).parentNode.insertBefore(this.dom, el.nextSibling);
            return this;
        },


        insertFirst: function(el, returnDom){
            el = el || {};
            if(el.nodeType || el.dom || typeof el == 'string'){ // element
                el = GETDOM(el);
                this.dom.insertBefore(el, this.dom.firstChild);
                return !returnDom ? GET(el) : el;
            }else{ // dh config
                return this.createChild(el, this.dom.firstChild, returnDom);
            }
        },


        replace: function(el, doc){
            el = GET(el, doc || this.getDocument());
            this.insertBefore(el);
            el.remove();
            return this;
        },


        replaceWith: function(el, doc){
            var me = this,
                Element = Ext.Element;
            if(el.nodeType || el.dom || typeof el == 'string'){
                el = GETDOM(el, doc || me.getDocument());
                me.dom.parentNode.insertBefore(el, me.dom);
            }else{
                el = DH.insertBefore(me.dom, el);
            }

            delete Element.cache[me.id];
            Ext.removeNode(me.dom);
            me.id = Ext.id(me.dom = el);
            return Element.cache[me.id] = me;
        },



        insertHtml : function(where, html, returnEl){
            var el = DH.insertHtml(where, this.dom, html);
            return returnEl ? Ext.get(el, GETDOC(el)) : el;
        },

	    scrollIntoView : function(container, hscroll){
                var d = this.getDocument();
	            var c = Ext.getDom(container, d) || Ext.getBody(d).dom;
	            var el = this.dom;
	            var o = this.getOffsetsTo(c),
	                s = this.getScroll(),
	                l = o[0] + s.left,
	                t = o[1] + s.top,
	                b = t + el.offsetHeight,
	                r = l + el.offsetWidth;
	            var ch = c.clientHeight;
	            var ct = parseInt(c.scrollTop, 10);
	            var cl = parseInt(c.scrollLeft, 10);
	            var cb = ct + ch;
	            var cr = cl + c.clientWidth;
	            if(el.offsetHeight > ch || t < ct){
	                c.scrollTop = t;
	            }else if(b > cb){
	                c.scrollTop = b-ch;
	            }
	            c.scrollTop = c.scrollTop; // corrects IE, other browsers will ignore
	            if(hscroll !== false){
	                if(el.offsetWidth > c.clientWidth || l < cl){
	                    c.scrollLeft = l;
	                }else if(r > cr){
	                    c.scrollLeft = r-c.clientWidth;
	                }
	                c.scrollLeft = c.scrollLeft;
	            }
	            return this;
        },

        contains : function(el){
	        try {
	            return !el ? false : ELD.isAncestor(this.dom, el.dom ? el.dom : el);
	        } catch(e) {
	            return false;
	        }
	    },


        getScroll : function(){
            var d = this.dom,
            doc = this.getDocument(),
            body = doc.body,
            docElement = doc.documentElement,
            l,
            t,
            ret;

            if(Ext.isDocument(d) || d == body){
                if(Ext.isIE && ELD.docIsStrict(doc)){
                    l = docElement.scrollLeft;
                    t = docElement.scrollTop;
                }else{
                    l = window.pageXOffset;
                    t = window.pageYOffset;
                }
                ret = {left: l || (body ? body.scrollLeft : 0), top: t || (body ? body.scrollTop : 0)};
            }else{
                ret = {left: d.scrollLeft, top: d.scrollTop};
            }
            return ret;
        },

        getStyle : function(){
            var getStyle =
             view && view.getComputedStyle ?
                function GS(prop){
                    var el = !this._isDoc ? this.dom : null,
                        v,
                        cs,
                        out;

                    if(!el || el == document || Ext.isDocument(el)) return null;
                    prop = chkCache(prop);
                    out =  (v = el.style[prop]) ? v :
                           (cs = view.getComputedStyle(el, "")) ? cs[prop] : null;
                     // Webkit returns rgb values for transparent.
                    if(Ext.isWebKit && out == 'rgba(0, 0, 0, 0)'){
                        out = 'transparent';
                    }
                    return out;
                } :
                function GS(prop){
                   var el = !this._isDoc ? this.dom : null,
                        m,
                        cs;
                    if(!el || el == document || Ext.isDocument(el)) return null;
                    if (prop == 'opacity') {
                        if (el.style.filter.match) {
                            if(m = el.style.filter.match(opacityRe)){
                                var fv = parseFloat(m[1]);
                                if(!isNaN(fv)){
                                    return fv ? fv / 100 : 0;
                                }
                            }
                        }
                        return 1;
                    }
                    prop = chkCache(prop);
                    return el.style[prop] || ((cs = el.currentStyle) ? cs[prop] : null);
                };
                var GS = null;
                return getStyle;
        }(),

        setStyle : function(prop, value){
            if(this._isDoc || Ext.isDocument(this.dom)) return this;
            var tmp,
                style,
                camel;
            if (!Ext.isObject(prop)) {
                tmp = {};
                tmp[prop] = value;
                prop = tmp;
            }
            for (style in prop) {
                value = prop[style];
                style == 'opacity' ?
                    this.setOpacity(value) :
                    this.dom.style[chkCache(style)] = value;
            }
            return this;
        },

	    center : function(centerIn){
	        return this.alignTo(centerIn || this.getDocument(), 'c-c');
	    },


	    getCenterXY : function(){
	        return this.getAlignToXY(this.getDocument(), 'c-c');
	    },

	    getAnchorXY : function(anchor, local, s){
	        //Passing a different size is useful for pre-calculating anchors,
	        //especially for anchored animations that change the el size.
	        anchor = (anchor || "tl").toLowerCase();
	        s = s || {};

	        var me = this,  doc = this.getDocument(),
	            vp = me.dom == doc.body || me.dom == doc,
	            w = s.width || vp ? Ext.lib.Dom.getViewWidth(false,doc) : me.getWidth(),
	            h = s.height || vp ? Ext.lib.Dom.getViewHeight(false,doc) : me.getHeight(),
	            xy,
	            r = Math.round,
	            o = me.getXY(),
	            scroll = me.getScroll(),
	            extraX = vp ? scroll.left : !local ? o[0] : 0,
	            extraY = vp ? scroll.top : !local ? o[1] : 0,
	            hash = {
	                c  : [r(w * .5), r(h * .5)],
	                t  : [r(w * .5), 0],
	                l  : [0, r(h * .5)],
	                r  : [w, r(h * .5)],
	                b  : [r(w * .5), h],
	                tl : [0, 0],
	                bl : [0, h],
	                br : [w, h],
	                tr : [w, 0]
	            };

	        xy = hash[anchor];
	        return [xy[0] + extraX, xy[1] + extraY];
	    },


	    anchorTo : function(el, alignment, offsets, animate, monitorScroll, callback){
	        var me = this,
	            dom = me.dom;

	        function action(){
	            fly(dom).alignTo(el, alignment, offsets, animate);
	            Ext.callback(callback, fly(dom));
	        };

	        Ext.EventManager.onWindowResize(action, me);

	        if(!Ext.isEmpty(monitorScroll)){
	            Ext.EventManager.on(window, 'scroll', action, me,
	                {buffer: !isNaN(monitorScroll) ? monitorScroll : 50});
	        }
	        action.call(me); // align immediately
	        return me;
	    },


	    getScroll : function(){
	        var d = this.dom,
	            doc = this.getDocument(),
	            body = doc.body,
	            docElement = doc.documentElement,
	            l,
	            t,
	            ret;

	        if(d == doc || d == body){
	            if(Ext.isIE && ELD.docIsStrict(doc)){
	                l = docElement.scrollLeft;
	                t = docElement.scrollTop;
	            }else{
	                l = window.pageXOffset;
	                t = window.pageYOffset;
	            }
	            ret = {left: l || (body ? body.scrollLeft : 0), top: t || (body ? body.scrollTop : 0)};
	        }else{
	            ret = {left: d.scrollLeft, top: d.scrollTop};
	        }
	        return ret;
	    },


	    getAlignToXY : function(el, p, o){
            var doc;
	        el = Ext.get(el, doc = this.getDocument());

	        if(!el || !el.dom){
	            throw "Element.getAlignToXY with an element that doesn't exist";
	        }

	        o = o || [0,0];
	        p = (p == "?" ? "tl-bl?" : (!/-/.test(p) && p != "" ? "tl-" + p : p || "tl-bl")).toLowerCase();

	        var me = this,
	            d = me.dom,
	            a1,
	            a2,
	            x,
	            y,
	            //constrain the aligned el to viewport if necessary
	            w,
	            h,
	            r,
	            dw = Ext.lib.Dom.getViewWidth(false,doc) -10, // 10px of margin for ie
	            dh = Ext.lib.Dom.getViewHeight(false,doc)-10, // 10px of margin for ie
	            p1y,
	            p1x,
	            p2y,
	            p2x,
	            swapY,
	            swapX,
	            docElement = doc.documentElement,
	            docBody = doc.body,
	            scrollX = (docElement.scrollLeft || docBody.scrollLeft || 0)+5,
	            scrollY = (docElement.scrollTop || docBody.scrollTop || 0)+5,
	            c = false, //constrain to viewport
	            p1 = "",
	            p2 = "",
	            m = p.match(/^([a-z]+)-([a-z]+)(\?)?$/);

	        if(!m){
	           throw "Element.getAlignToXY with an invalid alignment " + p;
	        }

	        p1 = m[1];
	        p2 = m[2];
	        c = !!m[3];

	        //Subtract the aligned el's internal xy from the target's offset xy
	        //plus custom offset to get the aligned el's new offset xy
	        a1 = me.getAnchorXY(p1, true);
	        a2 = el.getAnchorXY(p2, false);

	        x = a2[0] - a1[0] + o[0];
	        y = a2[1] - a1[1] + o[1];

	        if(c){
	           w = me.getWidth();
	           h = me.getHeight();
	           r = el.getRegion();
	           //If we are at a viewport boundary and the aligned el is anchored on a target border that is
	           //perpendicular to the vp border, allow the aligned el to slide on that border,
	           //otherwise swap the aligned el to the opposite border of the target.
	           p1y = p1.charAt(0);
	           p1x = p1.charAt(p1.length-1);
	           p2y = p2.charAt(0);
	           p2x = p2.charAt(p2.length-1);
	           swapY = ((p1y=="t" && p2y=="b") || (p1y=="b" && p2y=="t"));
	           swapX = ((p1x=="r" && p2x=="l") || (p1x=="l" && p2x=="r"));


	           if (x + w > dw + scrollX) {
	                x = swapX ? r.left-w : dw+scrollX-w;
	           }
	           if (x < scrollX) {
	               x = swapX ? r.right : scrollX;
	           }
	           if (y + h > dh + scrollY) {
	                y = swapY ? r.top-h : dh+scrollY-h;
	            }
	           if (y < scrollY){
	               y = swapY ? r.bottom : scrollY;
	           }
	        }

	        return [x,y];
	    },
            // private ==>  used outside of core
	    adjustForConstraints : function(xy, parent, offsets){
	        return this.getConstrainToXY(parent || this.getDocument(), false, offsets, xy) ||  xy;
	    },

	    // private ==>  used outside of core
	    getConstrainToXY : function(el, local, offsets, proposedXY){
	        var os = {top:0, left:0, bottom:0, right: 0};

	        return function(el, local, offsets, proposedXY){
	            var doc = this.getDocument();
                el = Ext.get(el, doc);
	            offsets = offsets ? Ext.applyIf(offsets, os) : os;

	            var vw, vh, vx = 0, vy = 0;
	            if(el.dom == doc.body || el.dom == doc){
	                vw = Ext.lib.Dom.getViewWidth(false,doc);
	                vh = Ext.lib.Dom.getViewHeight(false,doc);
	            }else{
	                vw = el.dom.clientWidth;
	                vh = el.dom.clientHeight;
	                if(!local){
	                    var vxy = el.getXY();
	                    vx = vxy[0];
	                    vy = vxy[1];
	                }
	            }

	            var s = el.getScroll();

	            vx += offsets.left + s.left;
	            vy += offsets.top + s.top;

	            vw -= offsets.right;
	            vh -= offsets.bottom;

	            var vr = vx+vw;
	            var vb = vy+vh;

	            var xy = proposedXY || (!local ? this.getXY() : [this.getLeft(true), this.getTop(true)]);
	            var x = xy[0], y = xy[1];
	            var w = this.dom.offsetWidth, h = this.dom.offsetHeight;

	            // only move it if it needs it
	            var moved = false;

	            // first validate right/bottom
	            if((x + w) > vr){
	                x = vr - w;
	                moved = true;
	            }
	            if((y + h) > vb){
	                y = vb - h;
	                moved = true;
	            }
	            // then make sure top/left isn't negative
	            if(x < vx){
	                x = vx;
	                moved = true;
	            }
	            if(y < vy){
	                y = vy;
	                moved = true;
	            }
	            return moved ? [x, y] : false;
	        };
	    }(),

	    getCenterXY : function(){
	        return this.getAlignToXY(Ext.getBody(this.getDocument()), 'c-c');
	    },


        getHeight : function(contentHeight){
            var h = Math.max(this.dom.offsetHeight, this.dom.clientHeight) || 0;
            h = !contentHeight ? h : h - this.getBorderWidth("tb") - this.getPadding("tb");
            return h < 0 ? 0 : h;
        },


        getWidth : function(contentWidth){
            var w = Math.max(this.dom.offsetWidth, this.dom.clientWidth) || 0;
            w = !contentWidth ? w : w - this.getBorderWidth("lr") - this.getPadding("lr");
            return w < 0 ? 0 : w;
        },


	    center : function(centerIn){
	        return this.alignTo(centerIn || Ext.getBody(this.getDocument()), 'c-c');
	    } ,


        findParent : function(simpleSelector, maxDepth, returnEl){
            var p = this.dom,
                D = this.getDocument(),
                b = D.body,
                depth = 0,
                stopEl;
            if(Ext.isGecko && OPString.call(p) == '[object XULElement]') {
                return null;
            }
            maxDepth = maxDepth || 50;
            if (isNaN(maxDepth)) {
                stopEl = Ext.getDom(maxDepth, D);
                maxDepth = Number.MAX_VALUE;
            }
            while(p && p.nodeType == 1 && depth < maxDepth && p != b && p != stopEl){
                if(Ext.DomQuery.is(p, simpleSelector)){
                    return returnEl ? Ext.get(p, D) : p;
                }
                depth++;
                p = p.parentNode;
            }
            return null;
        }
    });



    Ext.apply(ELD , {

        getDocument : function(el, accessTest){
          var dom= null;
          try{
            dom = Ext.getDom(el, null); //will fail if El.dom is non "same-origin" document
          }catch(ex){}

          var isDoc = Ext.isDocument(dom);
          if(isDoc){
            if(accessTest){
                return Ext.isDocument(dom, accessTest) ? dom : null;
            }
            return dom;
          }
          return dom ?
                dom.ownerDocument ||  //Element
                dom.document //Window
                : null;
        },


        docIsStrict : function(doc){
            return (Ext.isDocument(doc) ? doc : this.getDocument(doc)).compatMode == "CSS1Compat";
        },

        getViewWidth : Ext.overload ([
           ELD.getViewWidth || function(full){},
            function() { return this.getViewWidth(false);},
            function(full, doc) {
                return full ? this.getDocumentWidth(doc) : this.getViewportWidth(doc);
            }]
         ),

        getViewHeight : Ext.overload ([
            ELD.getViewHeight || function(full){},
            function() { return this.getViewHeight(false);},
	        function(full, doc) {
	            return full ? this.getDocumentHeight(doc) : this.getViewportHeight(doc);
	        }]),

        getDocumentHeight: Ext.overload([
           ELD.getDocumentHeight || emptyFn,
           function(doc) {
            if(doc=this.getDocument(doc)){
              return Math.max(
                 !this.docIsStrict(doc) ? doc.body.scrollHeight : doc.documentElement.scrollHeight
                 , this.getViewportHeight(doc)
                 );
            }
            return undefined;
           }
         ]),

        getDocumentWidth: Ext.overload([
           ELD.getDocumentWidth || emptyFn,
           function(doc) {
              if(doc=this.getDocument(doc)){
                return Math.max(
                 !this.docIsStrict(doc) ? doc.body.scrollWidth : doc.documentElement.scrollWidth
                 , this.getViewportWidth(doc)
                 );
              }
              return undefined;
            }
        ]),

        getViewportHeight: Ext.overload([
           ELD.getViewportHeight || emptyFn,
           function(doc){
             if(doc=this.getDocument(doc)){
                if(Ext.isIE){
                    return this.docIsStrict(doc) ? doc.documentElement.clientHeight : doc.body.clientHeight;
                }else{
                    return doc.defaultView.innerHeight;
                }
             }
             return undefined;
           }
        ]),

        getViewportWidth: Ext.overload([
           ELD.getViewportWidth || emptyFn,
           function(doc) {
              if(doc=this.getDocument(doc)){
                return !this.docIsStrict(doc) && !Ext.isOpera ? doc.body.clientWidth :
                   Ext.isIE ? doc.documentElement.clientWidth : doc.defaultView.innerWidth;
              }
              return undefined;
            }
        ]),

        getXY : Ext.overload([
	        ELD.getXY || function(el){},
	        function(el, doc) {

                el = Ext.getDom(el, doc);
	            var D= this.getDocument(el);
                var p, pe, b, scroll;
	            var bd = D ? (D.body || D.documentElement): null;

	            if(!el || !bd || el == bd){ return [0, 0]; }

	            if (el.getBoundingClientRect) {
	                b = el.getBoundingClientRect();
	                scroll = fly(D).getScroll();
	                return [b.left + scroll.left, b.top + scroll.top];
	            }
	            var x = 0, y = 0;

	            p = el;

	            var hasAbsolute = fly(el).getStyle("position") == "absolute";

	            while (p) {

	                x += p.offsetLeft;
	                y += p.offsetTop;

	                if (!hasAbsolute && fly(p).getStyle("position") == "absolute") {
	                    hasAbsolute = true;
	                }

	                if (Ext.isGecko) {
	                    pe = fly(p);

	                    var bt = parseInt(pe.getStyle("borderTopWidth"), 10) || 0;
	                    var bl = parseInt(pe.getStyle("borderLeftWidth"), 10) || 0;


	                    x += bl;
	                    y += bt;


	                    if (p != el && pe.getStyle('overflow') != 'visible') {
	                        x += bl;
	                        y += bt;
	                    }
	                }
	                p = p.offsetParent;
	            }

	            if (Ext.isSafari && hasAbsolute) {
	                x -= bd.offsetLeft;
	                y -= bd.offsetTop;
	            }

	            if (Ext.isGecko && !hasAbsolute) {
	                var dbd = fly(bd);
	                x += parseInt(dbd.getStyle("borderLeftWidth"), 10) || 0;
	                y += parseInt(dbd.getStyle("borderTopWidth"), 10) || 0;
	            }

	            p = el.parentNode;
	            while (p && p != bd) {
	                if (!Ext.isOpera || (p.tagName != 'TR' && fly(p).getStyle("display") != "inline")) {
	                    x -= p.scrollLeft;
	                    y -= p.scrollTop;
	                }
	                p = p.parentNode;
	            }
	            return [x, y];
	        }])
    });

    var GETDOC = ELD.getDocument,
        flies = El._flyweights;



    Ext.fly = El.fly = function(el, named, doc){
	    var ret = null;
	    named = named || '_global';

        if (el = Ext.getDom(el, doc)) {
	        (ret = flies[named] = (flies[named] || new El.Flyweight())).dom = el;
            ret._isDoc = Ext.isDocument(el);
	    }
	    return ret;
	};

    var flyFn = function(){};
	flyFn.prototype = El.prototype;

	// dom is optional
	El.Flyweight = function(dom){
	    this.dom = dom;
	};

	El.Flyweight.prototype = new flyFn();
	El.Flyweight.prototype.isFlyweight = true;


    Ext.provide && Ext.provide('multidom');
 })();



(function(){

    var El = Ext.Element,
        ElFrame,
        ELD = Ext.lib.Dom,
        EMPTYFN = function(){},
        OP = Object.prototype,
        addListener = function () {
            var handler;
            if (window.addEventListener) {
                handler = function F(el, eventName, fn, capture) {
                    el.addEventListener(eventName, fn, !!capture);
                };
            } else if (window.attachEvent) {
                handler = function F(el, eventName, fn, capture) {
                    el.attachEvent("on" + eventName, fn);
                };
            } else {
                handler = function F(){};
            }
            var F = null; //Gbg collect
            return handler;
        }(),
       removeListener = function() {
            var handler;
            if (window.removeEventListener) {
                handler = function F(el, eventName, fn, capture) {
                    el.removeEventListener(eventName, fn, (capture));
                };
            } else if (window.detachEvent) {
                handler = function F(el, eventName, fn) {
                    el.detachEvent("on" + eventName, fn);
                };
            } else {
                handler = function F(){};
            }
            var F = null; //Gbg collect
            return handler;
        }();

  //assert multidom support: REQUIRED for Ext 3 or higher!
  if(typeof ELD.getDocument != 'function'){
     throw "MIF 2.0 requires multidom support" ;
  }
  //assert Ext 3 SVN/RC2.1 or higher!
  if(Ext.version < 3 || typeof Ext.Element.data != 'function'){
     throw "MIF 2.0 requires Ext 3.0 SVN/RC2.1 or higher." ;
  }

  Ext.isDocument = function(obj , testOrigin){
            var test = OP.toString.apply(obj) == '[object HTMLDocument]' || (obj && obj.nodeType == 9);
            if(test && !!testOrigin){
                try{
                    test = test && !!obj.location;
                }
                catch(e){return false;}
            }
            return test;
        };

  Ext.ns('Ext.ux.ManagedIFrame', 'Ext.ux.plugin');

  var MIM, MIF = Ext.ux.ManagedIFrame, MIFC;
  var frameEvents = ['documentloaded',
                     'domready',
                     'focus',
                     'blur',
                     'resize',
                     'scroll',
                     'unload',
                     'scroll',
                     'exception',
                     'message',
                     'reset'];

    var reSynthEvents = new RegExp('^('+frameEvents.join('|')+ ')', 'i');



    Ext.ux.ManagedIFrame.Element = Ext.extend(Ext.Element, {

            constructor : function(element, forceNew, doc ){
                var d = doc || document;
                var elCache  = ELD.resolveCache ? ELD.resolveCache(d)._elCache : El.cache ;
                var dom = typeof element == "string" ?
                            d.getElementById(element) : element.dom || element;
                if(!dom || !(/^(iframe|frame)/i).test(dom.tagName)) { // invalid id/element
                    return null;
                }
                var id = dom.id;
                if(forceNew !== true && id && elCache[id]){ // element object already exists
                    return elCache[id];
                } else {
                    if(id){ elCache[id] = this;}
                }

                this.dom = dom;


                 this.id = id || Ext.id(dom);
                 this.dom.name || (this.dom.name = this.id);

                 if(Ext.isIE){
                     document.frames[this.dom.name] || (document.frames[this.dom.name].name = this.dom);
                 }

                 this.dom.ownerCt = this;
                 MIM.register(this);

                 (this._observable = new Ext.util.Observable()).addEvents(


                    'documentloaded',



                    'domready',


                     'exception',


                     'resize',


                    'message',


                     'blur',


                    'focus',


                     'unload',


                     'scroll',


                    'reset'
                 );
                    //  Private internal document state events.
                 this._observable.addEvents('_docready','_docload');

                 // Hook the Iframes loaded and error state handlers
                 this.dom[Ext.isIE?'onreadystatechange':'onload'] =
                    this.dom['onerror'] = this.loadHandler.createDelegate(this);

            },


            destructor   :  function () {
                this.dom[Ext.isIE?'onreadystatechange':'onload'] = this.dom['onerror'] = EMPTYFN;
                MIM.deRegister(this);
                this.removeAllListeners();
                Ext.destroy(this.shim, this.DDM);
                this.hideMask(true);
                delete this.loadMask;
                this.reset();
                this.manager = null;
                this.dom.ownerCt = null;
            },


            cleanse : function(forceReclean, deep){
                if(this.isCleansed && forceReclean !== true){
                    return this;
                }
                var d = this.dom, n = d.firstChild, nx;
                while(d && n){
                     nx = n.nextSibling;
                     deep && Ext.fly(n).cleanse(forceReclean, deep);
                     Ext.removeNode(n);
                     n = nx;
                }
                this.isCleansed = true;
                return this;
            },


            src     : null,


            CSS     : null,


            manager : null,


            disableMessaging  :  true,


            domReadyRetries   :  7500,


            focusOnLoad   : Ext.isIE,


            eventsFollowFrameLinks   : true,


            _domCache      : null,


            remove  : function(){
                this.destructor.apply(this, arguments);
                ElFrame.superclass.remove.apply(this,arguments);
            },


            getDocument :
                function(){ return this.dom ? this.dom.ownerDocument : document;},


            submitAsTarget : function(submitCfg){
                var opt = submitCfg || {},
                D = this.getDocument(),
  	            form = Ext.getDom(
                       opt.form ? opt.form.form || opt.form: null,
                    D) || Ext.DomHelper.append(D.body, {
                    tag: 'form',
                    cls : 'x-hidden x-mif-form',
                    encoding : 'multipart/form-data'
                  }),
                formState = {
                    target: form.target || '',
                    method: form.method || '',
                    encoding: form.encoding || '',
                    enctype: form.enctype || '',
                    action: form.action || ''
                 },
                encoding = opt.encoding || form.encoding,
                method = opt.method || form.method || 'POST';

                Ext.fly(form, D).set({
                   target  : this.dom.name,
                   method  : method,
                   encoding: encoding,
                   action  : opt.url || opt.action || form.action
                });

                if(method == 'POST' || !!opt.enctype){
                    Ext.fly(form, D).set({enctype : opt.enctype || form.enctype || encoding});
                }

		        var hiddens, hd, ps;
                // add any additional dynamic params
		        if(opt.params && (ps = Ext.isFunction(opt.params) ? opt.params() : opt.params)){
		            hiddens = [];

		            Ext.iterate(ps = typeof ps == 'string'? Ext.urlDecode(ps, false): ps,
                        function(n, v){
		                    Ext.fly(hd = D.createElement('input')).set({
		                     type : 'hidden',
		                     name : n,
		                     value: v
                            });
		                    form.appendChild(hd);
		                    hiddens.push(hd);
		                });
		        }

		        opt.callback &&
                    this._observable.addListener('_docready',opt.callback, opt.scope,{single:true});

                this._frameAction = true;
                this._targetURI = location.href;
		        this.showMask();

		        //slight delay for masking
		        (function(){

		            form.submit();
                    // remove dynamic inputs
		            hiddens && Ext.each(hiddens, Ext.removeNode, Ext);

                    //Remove if dynamically generated, restore state otherwise
                    var ff = Ext.fly(form, '_dynaForm');
		            if(ff.hasClass('x-mif-form')){
                        ff.remove();
                    }else{
                        ff.set(formState);
                    }
		            this.hideMask(true);
		        }).defer(100, this);

                return this;
		    },


            resetUrl : (function(){
                return Ext.isIE && Ext.isSecure ? Ext.SSL_SECURE_URL : 'about:blank';
            })(),


            setSrc : function(url, discardUrl, callback, scope) {
                var src = url || this.src || this.resetUrl;

                var O = this._observable;
                this._unHook();
                Ext.isFunction(callback) && O.addListener('_docload', callback, scope||this, {single:true});
                this.showMask();
                (discardUrl !== true) && (this.src = src);
                var s = this._targetURI = (Ext.isFunction(src) ? src() || '' : src);
                try {
                    this._frameAction = true; // signal listening now
                    this.dom.src = s;
                    this.checkDOM();
                } catch (ex) {
                    O.fireEvent.call(O, 'exception', this, ex);
                }
                return this;
            },


            setLocation : function(url, discardUrl, callback, scope) {

                var src = url || this.src || this.resetUrl;
                var O = this._observable;
                this._unHook();
                Ext.isFunction(callback) && O.addListener('_docload', callback, scope||this, {single:true});
                this.showMask();
                var s = this._targetURI = (Ext.isFunction(src) ? src() || '' : src);
                if (discardUrl !== true) {
                    this.src = src;
                }
                try {
                    this._frameAction = true; // signal listening now
                    this.getWindow().location.replace(s);
                    this.checkDOM();
                } catch (ex) {
                    O.fireEvent.call(O,'exception', this, ex);
                }
                return this;
            },


            reset : function(src, callback, scope) {

                this._unHook();
                var loadMaskOff = false;
                var s = src, win = this.getWindow();
                if(this.loadMask){
                    loadMaskOff = this.loadMask.disabled;
                    this.loadMask.disabled = false;
                 }
                this.hideMask(true);

                if(win){
                    this.isReset= true;
                    var cb = callback;
	                this._observable.addListener('_docload',
	                  function(frame) {
	                    if(this.loadMask){
	                        this.loadMask.disabled = loadMaskOff;
	                    };
	                    Ext.isFunction(cb) &&  (cb = cb.apply(scope || this, arguments));
                        this._observable.fireEvent("reset", this);
	                }, this, {single:true});

                    Ext.isFunction(s) && ( s = src());
                    s = this._targetURI = Ext.isEmpty(s, true)? this.resetUrl: s;
                    win.location.href = s;
                }

                return this;
            },


            scriptRE : /(?:<script.*?>)((\n|\r|.)*?)(?:<\/script>)/gi,


            update : function(content, loadScripts, callback, scope) {
                loadScripts = loadScripts || this.getUpdater().loadScripts || false;
                content = Ext.DomHelper.markup(content || '');
                content = loadScripts === true ? content : content.replace(this.scriptRE, "");
                var doc;
                if ((doc = this.getFrameDocument()) && !!content.length) {
                    this._unHook();
                    this.src = null;
                    this.showMask();
                    Ext.isFunction(callback) &&
                        this._observable.addListener('_docload', callback, scope||this, {single:true});
                    this._targetURI = location.href;
                    doc.open();
                    this._frameAction = true;
                    doc.write(content);
                    doc.close();
                    this.checkDOM();

                } else {
                    this.hideMask(true);
                    Ext.isFunction(callback) && callback.call(scope, this);
                }

                return this;
            },


            execCommand : function(command, userInterface, value, validate){
               var doc, assert;
               if ((doc = this.getFrameDocument()) && !!command) {
                  try{
                      Ext.isIE && this.getWindow().focus();
	                  assert = validate && Ext.isFunction(doc.queryCommandEnabled) ?
	                    doc.queryCommandEnabled(command) : true;

                      return assert && doc.execCommand(command, !!userInterface, value);
                  }catch(eex){return false;}
               }
               return false;

            },


            setDesignMode : function(active){
               var doc;
               (doc = this.getFrameDocument()) &&
                 (doc.designMode = (/on|true/i).test(String(active))?'on':'off');
            },


            getUpdater : function(){
               return this.updateManager ||
                    (this.updateManager = new MIF.Updater(this));

            },


            getHistory  : function(){
                var h=null;
                try{ h=this.getWindow().history; }catch(eh){}
                return h;
            },


            get : function(el) {
                var doc = this.getFrameDocument();
                return doc? Ext.get(el, doc) : doc=null;
            },


            fly : function(el, named) {
                var doc = this.getFrameDocument();
                return doc ? Ext.fly(el,named, doc) : null;
            },


            getDom : function(el) {
                var d;
                if (!el || !(d = this.getFrameDocument())) {
                    return (d=null);
                }
                return Ext.getDom(el, d);
            },


            select : function(selector, unique) {
                var d; return (d = this.getFrameDocument()) ? Ext.Element.select(selector,unique, d) : d=null;
            },


            query : function(selector) {
                var d; return (d = this.getFrameDocument()) ? Ext.DomQuery.select(selector, d): null;
            },


            removeNode : function( node) {

                var dom = n ? n.dom || n : null;
		         if(dom && dom.parentNode && dom.tagName != 'BODY'){

                    if(!dom.ownerDocument || dom.ownerDocument != this.getFrameDocument()){
                        throw new MIF.Error('documentcontext-remove' , dom.ownerDocument);
                    }
		            var el, docCache = this._domCache;
		            if(docCache && (el = docCache._elCache[dom.id])){
		                //clear out any references from the El.cache(s)
		                el.dom && el.removeAllListeners();
		                delete docCache._elCache[dom.id];
		                delete docCache._dataCache[dom.id];
		                el.dom && (el.dom = null);
		                el = null;
		            }
		            var D;
                    if(this.domWritable()){
			            if(Ext.isIE && !Ext.isIE8){
			                var d = D.createElement('div');
			                d.appendChild(dom);
			                d.removeChild(dom);
			                d = null;  //just dump the scratch DIV reference here.
			            } else {
			                var p = dom.parentNode;
			                p.removeChild(dom);
			                p = null;
			            }
                    }
		          }
		          dom = null;
            },


            _renderHook : function() {
                this._windowContext = null;
                this.CSS = this.CSS ? this.CSS.destroy() : null;
                this._hooked = false;
                try {
                    if (this.writeScript('(function(){(window.hostMIF = parent.document.getElementById("'
                                    + this.id
                                    + '").ownerCt)._windowContext='
                                    + (Ext.isIE
                                            ? 'window'
                                            : '{eval:function(s){return new Function("return ("+s+")")();}}')
                                    + ';})()')) {
                        var w, p = this._frameProxy, D = this.getFrameDocument();
                        if(w = this.getWindow()){
                            p || (p = this._frameProxy = this._eventProxy.createDelegate(this));
                            addListener(w, 'focus', p);
                            addListener(w, 'blur', p);
                            addListener(w, 'resize', p);
                            addListener(w, 'unload', p);
                            D && addListener(Ext.isIE ? w : D, 'scroll', p);
                        }

                        D && (this.CSS = new Ext.ux.ManagedIFrame.CSS(D));

                    }
                } catch (ex) {}
                return this.domWritable();
            },


            _unHook : function() {
                if (this._hooked) {
                    var id, el, c = this._domCache;
                    for ( id in c ) {
                        el = c[id];
                        el && el.removeAllListeners && el.removeAllListeners();
                        el && (c[id] = el = null);
                        delete c[id];
                    }

                    this._windowContext && (this._windowContext.hostMIF = null);
                    this._windowContext = null;

                    var w, p = this._frameProxy;
                    if(p && this.domWritable() && (w = this.getWindow())){
                        removeListener(w, 'focus', p);
                        removeListener(w, 'blur', p);
                        removeListener(w, 'resize', p);
                        removeListener(w, 'unload', p);
                        removeListener(Ext.isIE ? w : this.getFrameDocument(), 'scroll', p);
                    }
                }
                MIM._flyweights = {};
                this._domCache = null;
                ELD.clearCache && ELD.clearCache(this.id);
                this.CSS = this.CSS ? this.CSS.destroy() : null;
                this.domFired = this._frameAction = this.domReady = this._hooked = false;
            },


            _windowContext : null,


            getFrameDocument : function() {
                var win = this.getWindow(), doc = null;
                try {
                    doc = (Ext.isIE && win ? win.document : null)
                            || this.dom.contentDocument
                            || window.frames[this.dom.name].document || null;
                } catch (gdEx) {
                    this._domCache = null;

                    ELD.clearCache && ELD.clearCache(this.id);
                    return false; // signifies probable access restriction
                }
                doc = (doc && Ext.isFunction(ELD.getDocument)) ? ELD.getDocument(doc,true) : doc;

                if(doc){
                  this._domCache || (this._domCache = ELD.resolveCache ?

                     ELD.resolveCache(doc, this.id) :
                        {_elCache : {},
                       _dataCache : {},
                           '$_doc': Ext.get(doc,doc)
                    });
                }

                return doc;
            },


            getDoc : function() {
                var D = this.getFrameDocument();
                return Ext.get(D,D);
            },


            getBody : function() {
                var d;
                return (d = this.getFrameDocument()) ? this.get(d.body || d.documentElement) : null;
            },


            getDocumentURI : function() {
                var URI, d;
                try {
                    URI = this.src && (d = this.getFrameDocument()) ? d.location.href: null;
                } catch (ex) { // will fail on NON-same-origin domains
                }
                return URI || (Ext.isFunction(this.src) ? this.src() : this.src);
                // fallback to last known
            },


            getWindowURI : function() {
                var URI, w;
                try {
                    URI = (w = this.getWindow()) ? w.location.href : null;
                } catch (ex) {
                } // will fail on NON-same-origin domains
                return URI || (Ext.isFunction(this.src) ? this.src() : this.src);
                // fallback to last known
            },


            getWindow : function() {
                var dom = this.dom, win = null;
                try {
                    win = dom.contentWindow || window.frames[dom.name] || null;
                } catch (gwEx) {}
                return win;
            },


            scrollChildIntoView : function(child, container, hscroll){
                this.fly(child, '_scrollChildIntoView').scrollIntoView(this.getDom(container) || this.getBody().dom, hscroll);
                return this;
            },


            print : function() {
                try {
                    var win;
                    if( win = this.getWindow()){
                        Ext.isIE && win.focus();
                        win.print();
                    }
                } catch (ex) {
                    throw new MIF.Error('printexception' , ex.description || ex.message || ex);
                }
                return this;
            },


            domWritable : function() {
                return !!Ext.isDocument(this.getFrameDocument(),true) //test access
                    && !!this._windowContext;
            },


            execScript : function(block, useDOM) {
                try {
                    if (this.domWritable()) {
                        if (useDOM) {
                            this.writeScript(block);
                        } else {
                            return this._windowContext.eval(block);
                        }
                    } else {
                        throw new MIF.Error('execscript-secure-context');
                    }
                } catch (ex) {
                    this._observable.fireEvent.call(this._observable,'exception', this, ex);
                    return false;
                }
                return true;
            },


            writeScript : function(block, attributes) {
                attributes = Ext.apply({}, attributes || {}, {
                            type : "text/javascript",
                            text : block
                        });
                try {
                    var head, script, doc = this.getFrameDocument();
                    if (doc && typeof doc.getElementsByTagName != 'undefined') {
                        if (!(head = doc.getElementsByTagName("head")[0])) {
                            // some browsers (Webkit, Safari) do not auto-create
                            // head elements during document.write
                            head = doc.createElement("head");
                            doc.getElementsByTagName("html")[0].appendChild(head);
                        }
                        if (head && (script = doc.createElement("script"))) {
                            for (var attrib in attributes) {
                                if (attributes.hasOwnProperty(attrib)
                                        && attrib in script) {
                                    script[attrib] = attributes[attrib];
                                }
                            }
                            return !!head.appendChild(script);
                        }
                    }
                } catch (ex) {
                    this._observable.fireEvent.call(this._observable, 'exception', this, ex);

                }finally{
                    script = head = null;
                }
                return false;
            },


            loadFunction : function(fn, useDOM, invokeIt) {
                var name = fn.name || fn;
                var fnSrc = fn.fn || window[fn];
                name && fnSrc && this.execScript(name + '=' + fnSrc, useDOM); // fn.toString coercion
                invokeIt && this.execScript(name + '()'); // no args only
            },


            loadHandler : function(e, target) {
                var rstatus = (e && typeof e.type !== 'undefined' ? e.type: this.dom.readyState);
                //console.log('lh', rstatus, this.isReset, this._frameAction, this.domReady, this.domFired, this.eventsFollowFrameLinks);
                if (this.eventsFollowFrameLinks || this._frameAction || this.isReset ) {

	                switch (rstatus) {
	                    case 'domready' : // MIF
                        case 'DOMFrameContentLoaded' :
	                    case 'domfail' : // MIF
	                        this._onDocReady (rstatus);
	                        break;
	                    case 'load' : // Gecko, Opera, IE
	                    case 'complete' :
	                        this._onDocLoaded(rstatus);
	                        break;
	                    case 'error':
	                        this._observable.fireEvent.apply(this._observable,['exception', this].concat(arguments));
	                        break;
	                    default :
	                }
                }
                this.frameState = rstatus;
            },


            _onDocReady  : function(eventName ){
                var w, obv = this._observable, D;
                if(!this.isReset && this.focusOnLoad && (w = this.getWindow())){
                    w.focus();
                }
                //raise internal event regardless of state.
                obv.fireEvent("_docready", this);

                (D = this.getDoc()) && (D.isReady = true);

                if ( !this.domFired &&
                     (this._hooked = this._renderHook())) {
                        // Only raise if sandBox injection succeeded (same origin)
                        this.domFired = true;
                        this.isReset || obv.fireEvent.call(obv, 'domready', this);
                }

                this.domReady = true;
                this.hideMask();
            },


            _onDocLoaded  : function(eventName ){
                var obv = this._observable, w;
                this.domReady || this._onDocReady('domready');

                obv.fireEvent("_docload", this);  //invoke any callbacks
                this.isReset || obv.fireEvent("documentloaded", this);
                this.hideMask(true);
                this._frameAction = this.isReset = false;
            },


            checkDOM : function( win) {
                if (Ext.isOpera || Ext.isGecko ) { return; }
                // initialise the counter
                var n = 0, frame = this, domReady = false,
                    b, l, d,
                    max = this.domReadyRetries || 2500, //default max 5 seconds
                    polling = false,
                    startLocation = (this.getFrameDocument() || {location : {}}).location.href;
                (function() { // DOM polling for IE and others
                    d = frame.getFrameDocument() || {location : {}};
                    // wait for location.href transition
                    polling = (d.location.href !== startLocation || d.location.href === frame._targetURI);
                    if ( frame.domReady) { return;}
                    domReady = polling && ((b = frame.getBody()) && !!(b.dom.innerHTML || '').length) || false;
                    // null href is a 'same-origin' document access violation,
                    // so we assume the DOM is built when the browser updates it
                    if (d.location.href && !domReady && (++n < max)) {
                        setTimeout(arguments.callee, 2); // try again
                        return;
                    }
                    frame.loadHandler({ type : domReady ? 'domready' : 'domfail'});
                })();
            },


            filterEventOptionsRe: /^(?:scope|delay|buffer|single|stopEvent|preventDefault|stopPropagation|normalized|args|delegate)$/,


            addListener : function(eventName, fn, scope, options){

                if(typeof eventName == "object"){
                    var o = eventName;
                    for(var e in o){
                        if(this.filterEventOptionsRe.test(e)){
                            continue;
                        }
                        if(typeof o[e] == "function"){
                            // shared options
                            this.addListener(e, o[e], o.scope,  o);
                        }else{
                            // individual options
                            this.addListener(e, o[e].fn, o[e].scope, o[e]);
                        }
                    }
                    return;
                }

                if(reSynthEvents.test(eventName)){
                    var O = this._observable;
                    if(O){
                        O.events[eventName] || (O.addEvents(eventName));
                        O.addListener.call(O, eventName, fn, scope || this, options) ;}
                }else {
                    ElFrame.superclass.addListener.call(this, eventName,
                            fn, scope || this, options);
                }
                return this;
            },


            removeListener : function(eventName, fn, scope){
                var O = this._observable;
                if(reSynthEvents.test(eventName)){
                    O && O.removeListener.call(O, eventName, fn, scope || this, options);
                }else {
                  ElFrame.superclass.removeListener.call(this, eventName, fn, scope || this);
              }
              return this;
            },


            removeAllListeners : function(){
                Ext.EventManager.removeAll(this.dom);
                var O = this._observable;
                O && O.purgeListeners.call(this._observable);
                return this;
            },


            showMask : function(msg, msgCls, maskCls) {
                var lmask = this.loadMask;
                if (lmask && !lmask.disabled && !this._mask){
                    this.mask(msg || lmask.msg, msgCls || lmask.msgCls, maskCls || lmask.maskCls);
                }
            },


            hideMask : function(forced) {
                var tlm = this.loadMask;
                if (tlm && !!this._mask){
                    if (forced || (tlm.hideOnReady && this.domReady)) {
                        this.unmask();
                    }
                }
            },

            mask : function(msg, msgCls, maskCls){
                this._mask && this.unmask();
                var p = this.parent('.'+this.cls+'-mask-target') || this.parent();
                if(p.getStyle("position") == "static" &&
                    !p.select('iframe,frame,object,embed').elements.length){
                        p.addClass("x-masked-relative");
                }

                p.addClass("x-masked");

                this._mask = Ext.DomHelper.append(p, {cls: maskCls || this.cls+"-el-mask"} , true);
                this._mask.setDisplayed(true);
                this._mask._agent = p;

                if(typeof msg == 'string'){
                     var delay = (this.loadMask ? this.loadMask.delay : 0) || 10;

                     this._maskMsg = Ext.DomHelper.append(p, {cls: msgCls || this.cls+"-el-mask-msg" , style: {visibility:'hidden'}, cn:{tag:'div', html:msg}}, true);
                     this._maskMsg.setVisibilityMode(Ext.Element.VISIBILITY);
                     (function(){
                       this._mask &&
                        this._maskMsg &&
                          this._maskMsg.center(p).setVisible(true);
                      }).defer(delay,this);
                }
                if(Ext.isIE && !(Ext.isIE7 && Ext.isStrict) && this.getStyle('height') == 'auto'){ // ie will not expand full height automatically
                    this._mask.setSize(undefined, this._mask.getHeight());
                }
                return this._mask;
            },


            unmask : function(){

                var a;
                if(this._mask){
                    (a = this._mask._agent) && a.removeClass(["x-masked-relative","x-masked"]);
                    if(this._maskMsg){
                        this._maskMsg.remove();
                        delete this._maskMsg;
                    }
                    this._mask.remove();
                    delete this._mask;
                }
             },


             createShim : function(imgUrl, shimCls ){
                 this.shimCls = shimCls || this.shimCls || 'ux-mif-shim';
                 this.shim || (this.shim = this.next('.'+this.shimCls) ||  //already there ?
                  Ext.DomHelper.append(
                     this.dom.parentNode,{
                         tag : 'img',
                         src : imgUrl|| Ext.BLANK_IMAGE_URL,
                         cls : this.shimCls ,
                         galleryimg : "no"
                    }, true)) ;
                 this.shim && (this.shim.autoBoxAdjust = false);
                 return this.shim;
             },


             toggleShim : function(show){
                var shim = this.shim || this.createShim();
                var cls = this.shimCls + '-on';
                !show && shim.removeClass(cls);
                show && !shim.hasClass(cls) && shim.addClass(cls);
             },


            load : function(loadCfg) {
                var um;
                if (um = this.getUpdater()) {
                    if (loadCfg && loadCfg.renderer) {
                        um.setRenderer(loadCfg.renderer);
                        delete loadCfg.renderer;
                    }
                    um.update.apply(um, arguments);
                }
                return this;
            },


             _eventProxy : function(e) {
                 if (!e) return;
                 e = Ext.EventObject.setEvent(e);
                 var be = e.browserEvent || e, er, args = [e.type, this];

                 if (!be['eventPhase']
                         || (be['eventPhase'] == (be['AT_TARGET'] || 2))) {

                     if(e.type == 'resize'){
	                    var doc = this.getFrameDocument();
	                    doc && (args.push(
	                        { height: ELD.getDocumentHeight(doc), width : ELD.getDocumentWidth(doc) },
	                        { height: ELD.getViewportHeight(doc), width : ELD.getViewportWidth(doc) },
	                        { height: ELD.getViewHeight(false, doc), width : ELD.getViewWidth(false, doc) }
	                      ));
	                 }

                     er =  this._observable ?
                           this._observable.fireEvent.apply(this._observable, args.concat(
                              Array.prototype.slice.call(arguments,0)))
                           : null;

	                 // same-domain unloads should clear ElCache for use with the
	                 // next document rendering
	                 (e.type == 'unload') && this._unHook();

                 }
                 return er;
            },


	        sendMessage : function(message, tag, origin) {
	          //(implemented by mifmsg.js )
	        },


	        postMessage : function(message ,ports ,origin ){
	            //(implemented by mifmsg.js )
	        }

    });

    ElFrame = Ext.Element.IFRAME = Ext.Element.FRAME = Ext.ux.ManagedIFrame.Element;


    var fp = ElFrame.prototype;

    Ext.override ( ElFrame , {


        on :  fp.addListener,


        un : fp.removeListener,

        getUpdateManager : fp.getUpdater
    });



   Ext.ux.ManagedIFrame.ComponentAdapter = function(){};
   Ext.ux.ManagedIFrame.ComponentAdapter.prototype = {


        version : 2.01,


        defaultSrc : null,

        title      : '&#160;',


        unsupportedText : 'Inline frames are NOT enabled\/supported by your browser.',

        hideMode   : !Ext.isIE && !!Ext.ux.plugin.VisibilityMode ? 'nosize' : 'display',

        animCollapse  : Ext.isIE ,

        animFloat  : Ext.isIE ,


        frameConfig  : null,


        focusOnLoad   : false,


        frameEl : null,


        useShim   : false,


        autoScroll: true,


        autoLoad: null,


        getId : function(){
             return this.id   || (this.id = "mif-comp-" + (++Ext.Component.AUTO_ID));
        },

        stateEvents : ['documentloaded'],

        stateful    : false,


        setAutoScroll : function(auto){
            var scroll = Ext.value(auto, this.autoScroll === true);
            this.rendered && this.getFrame() &&
                this.frameEl.setOverflow( (this.autoScroll = scroll) ? 'auto':'hidden');
            return this;
        },

        getContentTarget : function(){
            return this.getFrame();
        },


        getFrame : function(){
             if(this.rendered){
                if(this.frameEl){ return this.frameEl;}
                var f = this.items && this.items.first ? this.items.first() : null;
                f && (this.frameEl = f.frameEl);
                return this.frameEl;
             }
             return null;
            },


        getFrameWindow : function() {
            return this.getFrame() ? this.frameEl.getWindow() : null;
        },


        getFrameDocument : function() {
            return this.getFrame() ? this.frameEl.getFrameDocument() : null;
        },


        getFrameDoc : function() {
            return this.getFrame() ? this.frameEl.getDoc() : null;
        },


        getFrameBody : function() {
            return this.getFrame() ? this.frameEl.getBody() : null;
        },


        resetFrame : function() {
            this.getFrame() && this.frameEl.reset.apply(this.frameEl, arguments);
            return this;
        },


        submitAsTarget  : function(submitCfg){
            this.getFrame() && this.frameEl.submitAsTarget.apply(this.frameEl, arguments);
            return this;
        },


        load : function(loadCfg) {
            if(loadCfg && this.getFrame()){
                var args = arguments;
                this.resetFrame(null, function(){
                    loadCfg.submitAsTarget ?
                    this.submitAsTarget.apply(this,args):
                    this.frameEl.load.apply(this.frameEl,args);
                },this);
            }
            this.autoLoad = loadCfg;
            return this;
        },


        doAutoLoad : function() {
            this.autoLoad && this.load(typeof this.autoLoad == 'object' ?
                this.autoLoad : { url : this.autoLoad });
        },


        getUpdater : function() {
            return this.getFrame() ? this.frameEl.getUpdater() : null;
        },


        setSrc : function(url, discardUrl, callback, scope) {
            this.getFrame() && this.frameEl.setSrc.apply(this.frameEl, arguments);
            return this;
        },


        setLocation : function(url, discardUrl, callback, scope) {
           this.getFrame() && this.frameEl.setLocation.apply(this.frameEl, arguments);
           return this;
        },


        getState : function() {
            var URI = this.getFrame() ? this.frameEl.getDocumentURI() || null : null;
            var state = this.supr().getState.call(this);
            state = Ext.apply(state || {},
                {defaultSrc : Ext.isFunction(URI) ? URI() : URI,
                 autoLoad   : this.autoLoad
                });
            return state;
        },


        setMIFEvents : function(){

            this.addEvents(


                    'documentloaded',


                    'domready',

                    'exception',


                    'message',


                    'blur',


                    'focus',


                    'scroll',


                    'resize',


                    'unload',


                    'reset'
                );
        },


        sendMessage : function(message, tag, origin) {

          //(implemented by mifmsg.js )
        },
        //Suspend (and queue) host container events until the child MIF.Component is rendered.
        onAdd : function(C){
             C.relayTarget && this.suspendEvents(true);
        },

        initRef: function() {

	        if(this.ref){
	            var t = this,
	                levels = this.ref.split('/'),
	                l = levels.length,
	                i;
	            for (i = 0; i < l; i++) {
	                if(t.ownerCt){
	                    t = t.ownerCt;
	                }
	            }
	            this.refName = levels[--i];
	            t[this.refName] || (t[this.refName] = this);

	            this.refOwner = t;
	        }
	    }

   };




  Ext.ux.ManagedIFrame.Component = Ext.extend(Ext.BoxComponent , {

            ctype     : "Ext.ux.ManagedIFrame.Component",


            initComponent : function() {

                var C = {
	                monitorResize : this.monitorResize || (this.monitorResize = !!this.fitToParent),
	                plugins : (this.plugins ||[]).concat(
	                    this.hideMode === 'nosize' && Ext.ux.plugin.VisibilityMode ?
		                    [new Ext.ux.plugin.VisibilityMode(
		                        {hideMode :'nosize',
		                         elements : ['bwrap']
		                        })] : [] )
                  };

                MIF.Component.superclass.initComponent.call(
                  Ext.apply(this,
                    Ext.apply(this.initialConfig, C)
                    ));

                this.setMIFEvents();
            },


            onRender : function(){
                //create a wrapper DIV if the component is not targeted
                this.el || (this.autoEl = {});

                MIF.Component.superclass.onRender.apply(this, arguments);

                //default child frame's name to that of MIF-parent id (if not specified on frameCfg).
                var frCfg = this.frameCfg || this.frameConfig || (this.relayTarget ? {name : this.relayTarget.id}: {}) || {};

                //backward compatability with MIF 1.x
                var frDOM = frCfg.autoCreate || frCfg;
                frDOM = Ext.apply({tag  : 'iframe', id: Ext.id()}, frDOM);

                var frame = this.el.child('iframe',true) || this.el.child('frame',true) ||
                            this.el.createChild([
	                        Ext.apply({
	                                name : frDOM.id,
	                                frameborder : 0
	                               }, frDOM ),
	                         {tag: 'noframes', html : this.unsupportedText || null}
	                        ]);
                var F;
                if( F = this.frameEl = (!!frame ? new MIF.Element(frame, true): null)){
                    (F.ownerCt = (this.relayTarget || this)).frameEl = F;
                    F.addClass(['ux-mif', 'ux-mif-fill']);
                    if (this.loadMask) {
                        //resolve possible maskEl by Element name eg. 'body', 'bwrap', 'actionEl'
                        var mEl = this.loadMask.maskEl || 'x-panel-bwrap';
                        F.loadMask = Ext.apply({
                                    disabled    : false,
                                    hideOnReady : false,
                                    msgCls      : 'ext-el-mask-msg x-mask-loading',
                                    maskCls     : 'ext-el-mask'
                                },
                                {
                                  maskEl : Ext.get( this[mEl] || F.parent('.' + mEl) || mEl || this.el)
                                },
                                this.loadMask);
                        F.cls && F.loadMask.maskEl && F.loadMask.maskEl.addClass(F.cls + '-mask-target');
                    }
                    F.disableMessaging = Ext.value(frCfg.disableMessaging, true);
                    F._observable &&
                        (this.relayTarget || this).relayEvents(F._observable, frameEvents.concat(this._msgTagHandlers || []));
                    delete this.contentEl;
                 }

            },


            afterRender  : function(container) {
                MIF.Component.superclass.afterRender.apply(this,arguments);

                // only resize (to Parent) if the panel is NOT in a layout.
                // parentNode should have {style:overflow:hidden;} applied.
                if (this.fitToParent && !this.ownerCt) {
                    var pos = this.getPosition(), size = (Ext.get(this.fitToParent)
                            || this.getEl().parent()).getViewSize();
                    this.setSize(size.width - pos[0], size.height - pos[1]);
                }

                this.getEl().setOverflow('hidden'); //disable competing scrollers
                this.setAutoScroll();
                var F;

                if(F = this.frameEl){
                    var ownerCt = this.ownerCt;
                    while (ownerCt) {
                        ownerCt.on('afterlayout', function(container, layout) {
                            Ext.each(['north', 'south', 'east', 'west'],
                                    function(region) {
                                        var reg;
                                        if ((reg = layout[region]) &&
                                             reg.split && reg.split.dd &&
                                             !reg._splitTrapped) {
                                               reg.split.dd.endDrag = reg.split.dd.endDrag.createSequence(MIM.hideShims, MIM );
                                               reg.split.on('beforeresize',MIM.showShims,MIM);
                                               reg._splitTrapped = MIM._splitTrapped = true;
                                        }
                            }, this);
                        }, this, { single : true}); // and discard
                        ownerCt = ownerCt.ownerCt; // nested layouts?
                    }

                    if(!!this.ownerCt || this.useShim ){ this.shim = F.createShim(); }
                    this.getUpdater().showLoadIndicator = this.showLoadIndicator || false;

                    //Resume Parent containers' events
                    var resumeEvents = this.relayTarget && this.ownerCt ?
                       this.ownerCt.resumeEvents.createDelegate(this.ownerCt) : null;

                    if(this.autoload){
                       this.doAutoLoad();
                    } else if(this.frameMarkup || this.html) {
                       F.update(this.frameMarkup || this.html, true, resumeEvents);
                       delete this.html;
                       delete this.frameMarkup;
                       return;
                    }else{

                        if(this.defaultSrc){
                            F.setSrc(this.defaultSrc, false);
                        }else{

                            F.reset(null, resumeEvents);
                            return;
                        }
                    }
                    resumeEvents && resumeEvents();
                }
            },


            beforeDestroy : function() {
                this.setVisible(false);
                var F;
                if(F = this.getFrame()){
                    F.hide();
                    F.remove();
                    El.uncache(F);
                    this.frameEl = this.shim = null;
                }
                this.relayTarget && (this.relayTarget.frameEl = null);
                MIF.Component.superclass.beforeDestroy.call(this);
            }
    });

    Ext.override(MIF.Component, MIF.ComponentAdapter.prototype);
    Ext.reg('mif', MIF.Component);




  function embed_MIF(config){

    config || (config={});
    config.layout = 'fit';
    config.items = {
             xtype    : 'mif',
               ref    : 'mifChild',
            useShim   : true,
           autoScroll : Ext.value(config.autoScroll , this.autoScroll),
          defaultSrc  : Ext.value(config.defaultSrc , this.defaultSrc),
         frameMarkup  : Ext.value(config.html , this.html),
            loadMask  : Ext.value(config.loadMask , this.loadMask),
         focusOnLoad  : Ext.value(config.focusOnLoad, this.focusOnLoad),
          frameConfig : Ext.value(config.frameConfig || config.frameCfg , this.frameConfig),
          relayTarget : this  //direct relay of events to the parent component
        };
    delete config.html;
    this.setMIFEvents();
    return config;

  };



  Ext.ux.ManagedIFrame.Panel = Ext.extend( Ext.Panel , {
        ctype       : "Ext.ux.ManagedIFrame.Panel",

        constructor : function(config){
            MIF.Panel.superclass.constructor.call(this, embed_MIF.call(this, config));
         }
  });

  Ext.override(MIF.Panel, MIF.ComponentAdapter.prototype);
  Ext.reg('iframepanel', MIF.Panel);




    Ext.ux.ManagedIFrame.Portlet = Ext.extend(Ext.ux.ManagedIFrame.Panel, {
                ctype      : "Ext.ux.ManagedIFrame.Portlet",
                anchor     : '100%',
                frame      : true,
                collapseEl : 'bwrap',
                collapsible: true,
                draggable  : true,
                cls        : 'x-portlet'

            });

    Ext.reg('iframeportlet', MIF.Portlet);




  Ext.ux.ManagedIFrame.Window = Ext.extend( Ext.Window ,
       {
            ctype       : "Ext.ux.ManagedIFrame.Window",
            constructor : function(config){
			    MIF.Window.superclass.constructor.call(this, embed_MIF.call(this, config));
            }
    });
    Ext.override(MIF.Window, MIF.ComponentAdapter.prototype);
    Ext.reg('iframewindow', MIF.Window);




    Ext.ux.ManagedIFrame.Updater = Ext.extend(Ext.Updater, {


        showLoading : function(){
            this.showLoadIndicator && this.el && this.el.mask(this.indicatorText);

        },


        hideLoading : function(){
            this.showLoadIndicator && this.el && this.el.unmask();
        },

        // private
        updateComplete : function(response){
            MIF.Updater.superclass.updateComplete.apply(this,arguments);
            this.hideLoading();
        },

        // private
        processFailure : function(response){
            MIF.Updater.superclass.processFailure.apply(this,arguments);
            this.hideLoading();
        }

    });


    var styleCamelRe = /(-[a-z])/gi;
    var styleCamelFn = function(m, a) {
        return a.charAt(1).toUpperCase();
    };


    Ext.ux.ManagedIFrame.CSS = function(hostDocument) {
        var doc;
        if (hostDocument) {
            doc = hostDocument;
            return {
                rules : null,

                destroy  :  function(){  return doc = null; },


                createStyleSheet : function(cssText, id) {
                    var ss;
                    if (!doc)return;
                    var head = doc.getElementsByTagName("head")[0];
                    var rules = doc.createElement("style");
                    rules.setAttribute("type", "text/css");
                    Ext.isString(id) && rules.setAttribute("id", id);

                    if (Ext.isIE) {
                        head.appendChild(rules);
                        ss = rules.styleSheet;
                        ss.cssText = cssText;
                    } else {
                        try {
                            rules.appendChild(doc.createTextNode(cssText));
                        } catch (e) {
                            rules.cssText = cssText;
                        }
                        head.appendChild(rules);
                        ss = rules.styleSheet
                                ? rules.styleSheet
                                : (rules.sheet || doc.styleSheets[doc.styleSheets.length - 1]);
                    }
                    this.cacheStyleSheet(ss);
                    return ss;
                },


                removeStyleSheet : function(id) {

                    if (!doc || !id)return;
                    var existing = doc.getElementById(id);
                    if (existing) {
                        existing.parentNode.removeChild(existing);
                    }
                },


                swapStyleSheet : function(id, url) {
                    if (!doc)return;
                    this.removeStyleSheet(id);
                    var ss = doc.createElement("link");
                    ss.setAttribute("rel", "stylesheet");
                    ss.setAttribute("type", "text/css");
                    Ext.isString(id) && ss.setAttribute("id", id);
                    ss.setAttribute("href", url);
                    doc.getElementsByTagName("head")[0].appendChild(ss);
                },


                refreshCache : function() {
                    return this.getRules(true);
                },

                // private
                cacheStyleSheet : function(ss, media) {
                    this.rules || (this.rules = {});

                     try{// try catch for cross domain access issue

				          Ext.each(ss.cssRules || ss.rules || [],
				            function(rule){
				              this.hashRule(rule, ss, media);
				          }, this);

				          //IE @imports
				          Ext.each(ss.imports || [],
				           function(sheet){
				              sheet && this.cacheStyleSheet(sheet,this.resolveMedia([sheet, sheet.parentStyleSheet]));
				           }
				          ,this);

			        }catch(e){}
                },
                 // @private
			   hashRule  :  function(rule, sheet, mediaOverride){

			      var mediaSelector = mediaOverride || this.resolveMedia(rule);

			      //W3C @media
			      if( rule.cssRules || rule.rules){
			          this.cacheStyleSheet(rule, this.resolveMedia([rule, rule.parentRule ]));
			      }

			       //W3C @imports
			      if(rule.styleSheet){
			         this.cacheStyleSheet(rule.styleSheet, this.resolveMedia([rule, rule.ownerRule, rule.parentStyleSheet]));
			      }

			      rule.selectorText &&
			        Ext.each((mediaSelector || '').split(','),
			           function(media){
			            this.rules[((media ? media.trim() + ':' : '') + rule.selectorText).toLowerCase()] = rule;
			        }, this);

			   },


			   resolveMedia  : function(rule){
			        var media;
			        Ext.each([].concat(rule),function(r){
			            if(r && r.media && r.media.length){
			                media = r.media;
			                return false;
			            }
			        });
			        return media ? (Ext.isIE ? String(media) : media.mediaText ) : '';
			     },


                getRules : function(refreshCache) {
                    if (!this.rules || refreshCache) {
                        this.rules = {};
                        if (doc) {
                            var ds = doc.styleSheets;
                            for (var i = 0, len = ds.length; i < len; i++) {
                                try {
                                    this.cacheStyleSheet(ds[i]);
                                } catch (e) {}
                            }
                        }
                    }
                    return this.rules;
                },


                getRule : function(selector, refreshCache, mediaSelector) {
                    var rs = this.getRules(refreshCache);

			        if(Ext.type(mediaSelector) == 'string'){
			            mediaSelector = mediaSelector.trim() + ':';
			        }else{
			            mediaSelector = '';
			        }

			        if(!Ext.isArray(selector)){
			            return rs[(mediaSelector + selector).toLowerCase()];
			        }
			        var select;
			        for(var i = 0; i < selector.length; i++){
			            select = (mediaSelector + selector[i]).toLowerCase();
			            if(rs[select]){
			                return rs[select];
			            }
			        }
			        return null;
                },


                updateRule : function(selector, property, value, mediaSelector){

			         Ext.each((mediaSelector || '').split(','), function(mediaSelect){
			            if(!Ext.isArray(selector)){
			                var rule = this.getRule(selector, false, mediaSelect);
			                if(rule){
			                    rule.style[property.replace(camelRe, camelFn)] = value;
			                    return true;
			                }
			            }else{
			                for(var i = 0; i < selector.length; i++){
			                    if(this.updateRule(selector[i], property, value, mediaSelect)){
			                        return true;
			                    }
			                }
			            }
			            return false;
			         }, this);
                }
            };
        }
    };


    Ext.ux.ManagedIFrame.Manager = function() {
        var frames = {};
        var implementation = {
            // private DOMFrameContentLoaded handler for browsers (Gecko, Webkit, Opera) that support it.
            _DOMFrameReadyHandler : function(e) {
                try {
                    var $frame ;
                    if ($frame = e.target.ownerCt){
                        $frame.loadHandler.call($frame,e);
                    }
                } catch (rhEx) {} //nested iframes will throw when accessing target.id
            },

            shimCls : 'ux-mif-shim',


            register : function(frame) {
                frame.manager = this;
                frames[frame.id] = frames[frame.name] = {ref : frame };
                return frame;
            },

            deRegister : function(frame) {
                delete frames[frame.id];
                delete frames[frame.name];

            },

            hideShims : function() {
                var mm = MIF.Manager;
                mm.shimsApplied && Ext.select('.' + mm.shimCls, true).removeClass(mm.shimCls+ '-on');
                mm.shimsApplied = false;
            },


            showShims : function() {
                var mm = MIF.Manager;
                !mm.shimsApplied && Ext.select('.' + mm.shimCls, true).addClass(mm.shimCls+ '-on');
                mm.shimsApplied = true;
            },


            getFrameById : function(id) {
                return typeof id == 'string' ? (frames[id] ? frames[id].ref
                        || null : null) : null;
            },


            getFrameByName : function(name) {
                return this.getFrameById(name);
            },


            // retrieve the internal frameCache object
            getFrameHash : function(frame) {
                return frames[frame.id] || frames[frame.id] || null;
            },


            _flyweights : {},


            destroy : function() {
                if (document.addEventListener) {
                      window.removeEventListener("DOMFrameContentLoaded", this._DOMFrameReadyHandler , false);
                }
                delete this._flyweights;
            }
        };
        // for Gecko and Opera and any who might support it later
        document.addEventListener &&
            window.addEventListener("DOMFrameContentLoaded", implementation._DOMFrameReadyHandler , false);

        Ext.EventManager.on(window, 'beforeunload', implementation.destroy, implementation);
        return implementation;
    }();

    MIM = MIF.Manager;
    MIM.showDragMask = MIM.showShims;
    MIM.hideDragMask = MIM.hideShims;


    var winDD = Ext.Window.DD;
    Ext.override(winDD, {
       startDrag : winDD.prototype.startDrag.createInterceptor(MIM.showShims),
       endDrag   : winDD.prototype.endDrag.createInterceptor(MIM.hideShims)
    });

    //Previous release compatibility
    Ext.ux.ManagedIFramePanel = MIF.Panel;
    Ext.ux.ManagedIFramePortlet = MIF.Portlet;
    Ext.ux.ManagedIframe = function(el,opt){

        var args = Array.prototype.slice.call(arguments, 0),
            el = Ext.get(args[0]),
            config = args[0];

        if (el && el.dom && el.dom.tagName == 'IFRAME') {
            config = args[1] || {};
        } else {
            config = args[0] || args[1] || {};

            el = config.autoCreate ? Ext.get(Ext.DomHelper.append(
                    config.autoCreate.parent || Ext.getBody(), Ext.apply({
                        tag : 'iframe',
                        frameborder : 0,
                        cls : 'x-mif',
                        src : (Ext.isIE && Ext.isSecure)? Ext.SSL_SECURE_URL: 'about:blank'
                    }, config.autoCreate)))
                    : null;

            if(el && config.unsupportedText){
                Ext.DomHelper.append(el.dom.parentNode, {tag:'noframes',html: config.unsupportedText } );
            }
        }

        var mif = new MIF.Element(el,true);
        if(mif){
            Ext.apply(mif, {
                disableMessaging : Ext.value(config.disableMessaging , true),
                loadMask : !!config.loadMask ? Ext.apply({
                            msg : 'Loading..',
                            msgCls : 'x-mask-loading',
                            maskEl : null,
                            hideOnReady : false,
                            disabled : false
                        }, config.loadMask) : false,
                _windowContext : null,
                eventsFollowFrameLinks : Ext.value(config.eventsFollowFrameLinks ,true)
            });

            config.listeners && mif.on(config.listeners);

            if(!!config.html){
                mif.update(config.html);
            } else {
                !!config.src && mif.setSrc(config.src);
            }
        }

        return mif;
    };


	Ext.ux.ManagedIFrame.Error = Ext.extend(Ext.Error, {
	    constructor : function(message, arg) {
	        this.arg = arg;
	        Ext.Error.call(this, message);
	    },
	    name : 'Ext.ux.ManagedIFrame'
	});

	Ext.apply(Ext.ux.ManagedIFrame.Error.prototype, {
	    lang: {
	        'documentcontext-remove': 'An attempt was made to remove an Element from the wrong document context.',
	        'execscript-secure-context': 'An attempt was made at script execution within a document context with limited access permissions.',
	        'printexception': 'An Error was encountered attempting the print the frame contents (document access is likely restricted).'
	    }
	});


    Ext.onReady(function() {
            // Generate CSS Rules but allow for overrides.
            var CSS = new Ext.ux.ManagedIFrame.CSS(document), rules = [];

            CSS.getRule('.ux-mif-fill')|| (rules.push('.ux-mif-fill{height:100%;width:100%;}'));
            CSS.getRule('.ux-mif-mask-target')|| (rules.push('.ux-mif-mask-target{position:relative;zoom:1;}'));
            CSS.getRule('.ux-mif-el-mask')|| (rules.push(
              '.ux-mif-el-mask {z-index: 100;position: absolute;top:0;left:0;-moz-opacity: 0.5;opacity: .50;*filter: alpha(opacity=50);width: 100%;height: 100%;zoom: 1;} ',
              '.ux-mif-el-mask-msg {z-index: 1;position: absolute;top: 0;left: 0;border:1px solid;background:repeat-x 0 -16px;padding:2px;} ',
              '.ux-mif-el-mask-msg div {padding:5px 10px 5px 10px;border:1px solid;cursor:wait;} '
              ));

            if (!CSS.getRule('.ux-mif-shim')) {
                rules.push('.ux-mif-shim {z-index:8500;position:absolute;top:0px;left:0px;background:transparent!important;overflow:hidden;display:none;}');
                rules.push('.ux-mif-shim-on{width:100%;height:100%;display:block;zoom:1;}');
                rules.push('.ext-ie6 .ux-mif-shim{margin-left:5px;margin-top:3px;}');
            }

            !!rules.length && CSS.createStyleSheet(rules.join(' '), 'mifCSS');

        });


    Ext.provide && Ext.provide('mif');
})()
