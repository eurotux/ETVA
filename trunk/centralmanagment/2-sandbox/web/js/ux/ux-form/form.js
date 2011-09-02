
Ext.override(Ext.form.BasicForm, {
    findInvalid: function() {
        var result = [], it = this.items.items, l = it.length, i, f;

        for (i = 0; i < l; i++) {
            if(!(f = it[i]).disabled && f.el.hasClass(f.invalidClass)){
                result.push(f);
            }
        }
        return result;
    }
});


Ext.override(Ext.form.Field, {
  // Hack to show/hide all elements label and field
  showAll: function(show){

    if(show==undefined) show = true;         

    if(this.rendered) this.el.up('.x-form-item').setDisplayed(show);
    else{
        this.on('render',function(){            
            this.el.up('.x-form-item').setDisplayed(show);
        },this);
    }
  }
  ,setFieldLabel : function(text) {
    if (this.rendered) {
      this.el.up('.x-form-item', 10, true).child('.x-form-item-label').update(text);
    }
    this.fieldLabel = text;
  }
  ,getTipTarget:function(){
      return this.getEl();
  }
  ,afterRender: function() {

        if(this.hint){                       
            var target = this.getTipTarget();
            Ext.QuickTips.register({
                target:target,                
                text: this.hint
            });
        }
        if(this.helpText){
            var label = findLabel(this);
            if(label){
             	var helpImage = label.createChild({
             			tag: 'img',
             			src:'/images/icons/information.png',
             			style: 'margin-bottom: 0px; margin-left: 5px; padding: 0px;'                        
             		});
                Ext.QuickTips.register({
                    target:  helpImage,                    
                    title: '',
                    text: this.helpText,
                    enabled: true
                });
            }
          }
          Ext.form.Field.superclass.afterRender.call(this);
          this.initEvents();
          this.initValue();
  }
  ,afterRender_: function() {
        if(this.helpText){
            var label = findLabel_(this);
                       
            if(label){            	
             	var helpImage = label.createChild({
                        tag: 'img',
                        src:'/images/icons/information.png',
             			style: 'position:relative; margin-bottom: 5px; margin-left: 5px;padding-top: 1px;'                      
             		});	                	
                Ext.QuickTips.register({
                    target:  helpImage,
                    title: '',
                    text: this.helpText,
                    enabled: true
                });
            }
          }
          Ext.form.Field.superclass.afterRender.call(this);
          this.initEvents();
          this.initValue();
  }
});


var findLabel = function(field) {
    var wrapDiv = null;
    var label = null;
    var type = field.getXType();    
    
    switch(type){
        case 'checkbox':
        case 'radio':
                    wrapDiv = field.getEl().up('div.x-form-check-wrap');
                    break;
        default:
                    wrapDiv = field.getEl().up('div.x-form-item');
                    break;


    }
    

    if(wrapDiv) {                
        label = wrapDiv.child('label');
    }
    if(label) {
        return label;
    }
}


var findLabel_ = function(field) {
    var wrapDiv = null;
    var label = null;
    alert(field.getXType());
    wrapDiv = field.getEl().up('div.x-form-check-wrap');    
    if(!wrapDiv) wrapDiv = field.getEl().up('div.x-form-item');
    
    //if(!wrapDiv) wrapDiv = field.getEl().up('div.x-form-radio-wrap');

    if(wrapDiv) {
        label = wrapDiv.child('div');
        if(!label) label = wrapDiv.child('label');    
    }
    if(label) {
        return label;
    }
    return '';
}
