<script>
Ext.namespace('Ext.ux.Wiz');

/**
 * Licensed under GNU LESSER GENERAL PUBLIC LICENSE Version 3
 *
 * @author Thorsten Suckow-Homberg <ts@siteartwork.de>
 * @url http://www.siteartwork.de/wizardcomponent
 */

/**
 * @class Ext.ux.Wiz.Card
 * @extends Ext.FormPanel
 *
 * A specific {@link Ext.FormPanel} that can be used as a card in a
 * {@link Ext.ux.Wiz}-component. An instance of this card does only work properly
 * if used in a panel that uses a {@see Ext.layout.CardLayout}-layout.
 *
 * @constructor
 * @param {Object} config The config object
 */
Ext.ux.Wiz.Card = Ext.extend(Ext.FormPanel, {

    /**
     * @cfg {Boolean} header "True" to create the header element. Defaults to
     * "false". See {@link Ext.form.FormPanel#header}
     */
    header : false,

    /**
     * @cfg {Strting} hideMode Hidemode of this component. Defaults to "offsets".
     * See {@link Ext.form.FormPanel#hideMode}
     */
    hideMode : 'display',

    /**
	 * @cfg {String} skip If this card should be skipped during process.Defaults to false.
	 */
	skip : false,

	/**
	 * @cfg {Ext.ux.Wiz} wizard The parent wizard.
	 */
	wizard : null,


    initComponent : function()
    {
        this.addEvents(
            /**
             * @event beforecardhide
             * If you want to add additional checks to your card which cannot be easily done
             * using default validators of input-fields (or using the monitorValid-config option),
             * add your specific listeners to this event.
             * This event gets only fired if the activeItem of the ownerCt-component equals to
             * this instance of {@see Ext.ux.Wiz.Card}. This is needed since a card layout usually
             * hides it's items right after rendering them, involving the beforehide-event.
             * If those checks would be attached to the normal beforehide-event, the card-layout
             * would never be able to hide this component after rendering it, depending on the
             * listeners return value.
             *
             * @param {Ext.ux.Wiz.Card} card The card that triggered the event
             */
            'beforecardhide'
        );


        Ext.ux.Wiz.Card.superclass.initComponent.call(this);

    },

// -------- helper
    isValid : function()
    {
        if (this.monitorValid) {
            return this.bindHandler();
        }

        return true;
    },

// -------- overrides

    /**
     * Overrides parent implementation since we allow to add any element
     * in this component which must not be neccessarily be a form-element.
     * So before a call to "isValid()" is about to be made, this implementation
     * checks first if the specific item sitting in this component has a method "isValid" - if it
     * does not exists, it will be added on the fly.
     */
    bindHandler : function()
    {
        //alert(this.card);
        //alert(this.items);

      //  if(!this.bound){
        //    return false; // stops binding
      //  }
        var valid = true;
        
        this.form.items.each(function(f){
            
            if(f.isXType('editorgrid')) alert('tala');
// alert('aki');
            /** [ itemselector validation begin ] ******************************** **/
            if (f.isXType('itemselector')) {
                var count = f.toStore.getCount();
                alert('a');
                if(count==0) f.isValid = false;

                // if the html editor is empty or has only (non breaking) spaces
                // or breaks (<br>),  then valid = false

                // valid = (count != 0);
            }            

           
            //else
            /** [ itemselector validation end ] ********************************** **/
          //  if(!f.isValid){
              //  alert('invalidi');
          //  }


           // if(f.isValid && !f.isValid(true)){
           //     valid = false;
           //     return false;
           // }

            if(!f.isValid){
                f.isValid = Ext.emptyFn;
            }
        });

       

        Ext.ux.Wiz.Card.superclass.bindHandler.call(this);
    },

    /**
     * Overrides parent implementation. This is needed because in case
     * this method uses "monitorValid=true", the method "startMonitoring" must
     * not be called, until the "show"-event of this card fires.
     */
    initEvents : function()
    {
        var old = this.monitorValid;
        this.monitorValid = false;
        Ext.ux.Wiz.Card.superclass.initEvents.call(this);
        this.monitorValid = old;

        this.on('beforehide',     this.bubbleBeforeHideEvent, this);

        this.on('beforecardhide', this.isValid,    this);
        this.on('show',           this.onCardShow, this);
        this.on('hide',           this.onCardHide, this);
    },

// -------- listener
    /**
     * Checks wether the beforecardhide-event may be triggered.
     */
    bubbleBeforeHideEvent : function()
    {
        var ly         = this.ownerCt.layout;
        var activeItem = ly.activeItem;

        if (activeItem && activeItem.id === this.id) {
            return this.fireEvent('beforecardhide', this);
        }

        return true;
    },

    /**
     * Stops monitoring the form elements in this component when the
     * 'hide'-event gets fired.
     */
    onCardHide : function()
    {
        if (this.monitorValid) {
            this.stopMonitoring();
        }
    },

    /**
     * Starts monitoring the form elements in this component when the
     * 'show'-event gets fired.
     */
    onCardShow : function()
    {
        if (this.monitorValid) {
            this.startMonitoring();
        }
    },

    getPrevious : function() {
                  // No cards before the first one
                  var pos = this.wizard.getCardPosition(this);
                  if (pos <= 0)
                    return -1;
                  while (--pos >= 0)
                    if (this.wizard.cards[pos].skip == false)
                      return pos;
                  return pos;
                },

    getNext : function() {
              // No cards after the last one
              var pos = this.wizard.getCardPosition(this);
              if (pos >= (this.wizard.cards.length-1))
                return -1;
              while (++pos < this.wizard.cards.length)
                if (this.wizard.cards[pos].skip == false)
                  return pos;
              return -1;
            },

    setSkip : function(skip) {
              if (this.wizard.cards[this.wizard.currentCard] == this)
                return false;
              if (this.skip != skip) {
                this.skip = skip;
                this.wizard.headPanel.updateStep();
                this.wizard.westPanel.updateStep();
                return true;
              }
              return false;
            }


});
</script>