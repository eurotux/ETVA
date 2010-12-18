/*
 *
 * EXTJS PATCHES
 *
 *
 */
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



function format_number(pnumber,decimals){

    if (isNaN(pnumber)) { return 0};
    if (pnumber=='') { return 0};

    var snum = new String(pnumber);
    var sec = snum.split('.');
    var whole = parseFloat(sec[0]);
    var result = '';

    if(sec.length > 1){
        var dec = new String(sec[1]);
        dec = String(parseFloat(sec[1])/Math.pow(10,(dec.length - decimals)));
        dec = String(whole + Math.round(parseFloat(dec))/Math.pow(10,decimals));
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


function byte_to_MBconvert(bytes, precision) {
units = new Array('', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');
unit = 0;
if(!precision) precision = 2;
//do {
//bytes /= 1024;
//unit++;
//} while (bytes > 1024);

        bytes = bytes / Math.pow(2,20);

return format_number(bytes,precision);
}