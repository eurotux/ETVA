/*
 *
 * EXTJS PATCHES
 *
 *
 */

Ext.ux.WindowAlwaysOnTop = function(){
       this.init = function(win){           

            win.on({            
                'deactivate': function(){
                    var i=1;
                    this.manager.each(function(){i++});
                    this.setZIndex(this.manager.zseed + (i*10));
                }})
       }
}


Ext.override(Ext.Window, {
    resizeFunc:function(){

        var viewerSize = Ext.getBody().getViewSize();
        var windowHeight = viewerSize.height * 0.97;
        var windowWidth = viewerSize.width * 0.97;

        windowHeight = Ext.util.Format.round(windowHeight,0);
        windowHeight = (windowHeight > this.maxH) ? this.maxH : windowHeight;

        windowWidth = Ext.util.Format.round(windowWidth,0);
        windowWidth = (windowWidth > this.maxW) ? this.maxW : windowWidth;

        this.setSize(windowWidth,windowHeight);

        if(this.isVisible()) this.center();
    }
});




Ext.DomQuery.pseudos.scrollable = function(c, t) {
    var r = [], ri = -1;
    for(var i = 0, ci; ci = c[i]; i++){
        var o = ci.style.overflow;
        if(o=='auto'||o=='scroll') {
            if (ci.scrollHeight < Ext.fly(ci).getHeight(true)) r[++ri] = ci;
        }
    }
    return r;
};


Ext.override(Ext.Component, {
    ensureVisible: function(stopAt) {
        var p;
        this.ownerCt.bubble(function(c) {
            if (p = c.ownerCt) {
                if (p instanceof Ext.TabPanel) {
                    p.setActiveTab(c);
                } else if (p.layout.setActiveItem) {
                    p.layout.setActiveItem(c);
                }
            }
            return (c !== stopAt);
        });
        this.el.scrollIntoView(this.el.up(':scrollable'));
        return this;
    }
});
/*
 * fix accordion layout:'fit'
 * use layout:'accordionpatch',
 *       layoutConfig: {
 *           autoWidth: false
 *       },
 */

Ext.layout.AccordionPatch = Ext.extend(Ext.layout.Accordion, {

    inactiveItems: [],//ADDED

    // private
    onLayout : function(ct, target){//ADDED
        Ext.layout.AccordionPatch.superclass.onLayout.call(this, ct, target);
        if(this.autoWidth === false) {
            for(var i = 0; i < this.inactiveItems.length; i++) {
                var item = this.inactiveItems[i];
                item.setSize(target.getStyleSize());
            }
        }
    },
    // private
    beforeExpand : function(p, anim){//MODFIED
        var ai = this.activeItem;
        if(ai){
            if(this.sequence){
                delete this.activeItem;
                ai.collapse({callback:function(){
                    p.expand(anim || true);
                }, scope: this});
                return false;
            }else{
                ai.collapse(this.animate);
                if(this.autoWidth === false && this.inactiveItems.indexOf(ai) == -1)//*****
                    this.inactiveItems.push(ai);//*****
            }
        }
        this.activeItem = p;
        if(this.activeOnTop){
            p.el.dom.parentNode.insertBefore(p.el.dom, p.el.dom.parentNode.firstChild);
        }
        if(this.autoWidth === false && this.inactiveItems.indexOf(this.activeItem) != -1)//*****
            this.inactiveItems.remove(this.activeItem);//*****
        this.layout();
    }

});

Ext.Container.LAYOUTS['accordionpatch'] = Ext.layout.AccordionPatch;

Ext.override(Ext.grid.CheckColumn, {
	onMouseDown : function(e, t){
		if(t.className && t.className.indexOf('x-grid3-cc-'+this.id) != -1){
			e.stopEvent();
			var index = this.grid.getView().findRowIndex(t);
			var record = this.grid.store.getAt(index);
			var cm = this.grid.getColumnModel();
			var col = cm.getIndexById(this.id);
			if(cm.isCellEditable(col, index)){
				record.set(this.dataIndex, !record.data[this.dataIndex]);
			}
		}
	}
});

/*
 * Function to translate to i18n. uses values from i18n array defined in locale/[janguage].js
 *
 */
function __(string) {

     if (typeof(i18n)!='undefined' && i18n[string]) {

        return i18n[string];
    }

    return string;
}

function format_number(pnumber,decimals, roundTo){

    if (isNaN(pnumber)) { return 0};
    if (pnumber=='') { return 0};

    var snum = new String(pnumber);
    var sec = snum.split('.');
    var whole = parseFloat(sec[0]);
    var result = '';

    if(sec.length > 1){
        var dec = new String(sec[1]);
        dec = String(parseFloat(sec[1])/Math.pow(10,(dec.length - decimals)));
        if(roundTo=='round')
            dec = String(whole + Math.round(parseFloat(dec))/Math.pow(10,decimals));
        if(roundTo=='floor')
        dec = String(whole + Math.floor(parseFloat(dec))/Math.pow(10,decimals));
        var dot = dec.indexOf('.');
        if(dot == -1){
            dec += '.';
            dot = dec.indexOf('.');
        }
        while(dec.length <= dot + decimals) { dec += '0'; }
        result = dec;
    } else{
        var dot;
        var dec = new String(whole);
        dec += '.';
        dot = dec.indexOf('.');
        while(dec.length <= dot + decimals) { dec += '0'; }
        result = dec;
    }
    return result;
}


function byte_to_MBconvert(bytes, precision, roundTo) {

if(!precision && precision!=0) precision = 2;

if(!roundTo) roundTo = 'round';
//do {
//bytes /= 1024;
//unit++;
//} while (bytes > 1024);

        bytes = bytes / Math.pow(2,20);

return format_number(bytes,precision,roundTo);
}


/*
 * returns array(network_address,broadcast_address,first_host,last_host,ranges)
 */
function network_calculator(ip,netmask)
{
    var ip1, ip2, ip3, ip4;
    var ba1, ba2, ba3, ba4, na1, na2, na3, na4, nm1, nm2, nm3, nm4;
    var fa1, fa2, fa3, fa4, la1, la2, la3, la4;
    
    var data_ip = ip.split('.');
    var data_netmask = netmask.split('.');

    ip1 = parseInt(data_ip[0]);

    ip2 = parseInt(data_ip[1]);

    ip3 = parseInt(data_ip[2]);

    ip4 = parseInt(data_ip[3]);


    nm1 = data_netmask[0];

    nm2 = data_netmask[1];

    nm3 = data_netmask[2];

    nm4 = data_netmask[3];



    na1 = (data_ip[0] & 0xFF) & (nm1 & 0xFF);

    na2 = (data_ip[1] & 0xFF) & (nm2 & 0xFF);

    na3 = (data_ip[2] & 0xFF) & (nm3 & 0xFF);

    na4 = (data_ip[3] & 0xFF) & (nm4 & 0xFF);


    ba1 = (data_ip[0] & 0xFF) | (~nm1 & 0xFF);

    ba2 = (data_ip[1] & 0xFF) | (~nm2 & 0xFF);

    ba3 = (data_ip[2] & 0xFF) | (~nm3 & 0xFF);

    ba4 = (data_ip[3] & 0xFF) | (~nm4 & 0xFF);

    fa1 = na1;

    fa2 = na2;

    fa3 = na3;

    fa4 = na4 + 1;

    la1 = ba1;

    la2 = ba2;

    la3 = ba3;

    la4 = ba4 - 1;




    var net_address = na1+'.'+na2+'.'+na3+'.'+na4;
    var bcast_address = ba1+'.'+ba2+'.'+ba3+'.'+ba4;
    var first_address = fa1+'.'+fa2+'.'+fa3+'.'+fa4;
    var last_address = la1+'.'+la2+'.'+la3+'.'+la4;


    var ranges = [];
    var from_range = first_address;
    var to_range = last_address;

    if(ip!=first_address){
        to_range = ip1+'.'+ip2+'.'+ip3+'.'+(ip4-1);
        ranges.push({'from':from_range,'to':to_range});

        from_range = ip1+'.'+ip2+'.'+ip3+'.'+(ip4+1);
        if(from_range!=bcast_address){
            to_range = last_address;
            if(last_address == ip) to_range = la1+'.'+la2+'.'+la3+'.'+(la4-1);
            ranges.push({'from':from_range,'to':to_range});
        }
        
    }else{
        from_range = fa1+'.'+fa2+'.'+fa3+'.'+(fa4+1);
        if(last_address == ip) to_range = la1+'.'+la2+'.'+la3+'.'+(la4-1);
        ranges.push({'from':from_range,'to':to_range});
    }
        

    var result = [net_address,bcast_address,first_address,last_address,ranges];
    return result;
}


/*
 *
 * FORM vtypes
 *
 *
 */
// Add the additional 'advanced' VTypes
Ext.apply(Ext.form.VTypes, {

    ip_addr : function(val, field) {

        var ip_regexp = /^(([1-9][0-9]{0,2})|0)\.(([1-9][0-9]{0,2})|0)\.(([1-9][0-9]{0,2})|0)\.(([1-9][0-9]{0,2})|0)$/;
        return ip_regexp.test(val);

    },
    ip_addrText : 'IP wrong format',
    no_spaces : function(val, field){

        var no_spaces_regexp = /^[0-9a-zA-Z._\-]+$/;
        return no_spaces_regexp.test(val);

    },
    no_spacesText : 'No spaces and special characters allowed!',
    // checks MAC octects
    oct_valid : function(val, field) {

        if (val.length > 2) field.setValue(val.substr(0,2));
        return true;

    }
    ,pool_valid : function(val, field) {

        if (val.length > 3) return false;
        return true;

    }
    ,pool_validText : 'Max value is 999!'
    
});


Ext.ns("Ext.ux.util");
/**
 * Clone Function
 * @param {Object/Array} o Object or array to clone
 * @return {Object/Array} Deep clone of an object or an array
 * @author Ing. Jozef Sakáloš
 */
Ext.ux.util.clone = function(o) {
    if(!o || 'object' !== typeof o) {
        return o;
    }
    if('function' === typeof o.clone) {
        return o.clone();
    }
    var c = '[object Array]' === Object.prototype.toString.call(o) ? [] : {};
    var p, v;
    for(p in o) {
        if(o.hasOwnProperty(p)) {
            v = o[p];
            if(v && 'object' === typeof v) {
                c[p] = Ext.ux.util.clone(v);
            }
            else {
                c[p] = v;
            }
        }
    }
    return c;
}; // eo function clone
