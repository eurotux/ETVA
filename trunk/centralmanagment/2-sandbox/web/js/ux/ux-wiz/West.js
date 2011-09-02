Ext.namespace('Ext.ux.Wiz');

/**
 * Licensed under GNU LESSER GENERAL PUBLIC LICENSE Version 3
 *
 * @author Thorsten Suckow-Homberg <ts@siteartwork.de>
 * @url http://www.siteartwork.de/wizardcomponent
 */

/**
 * @class Ext.ux.Wiz.Header
 * @extends Ext.BoxComponent
 *
 * A specific {@link Ext.BoxComponent} that can be used to show the current process in an
 * {@link Ext.ux.Wiz}.
 *
 * An instance of this class is usually being created by {@link Ext.ux.Wiz#initPanels} using the
 * {@link Ext.ux.Wiz#headerConfig}-object.
 *
 * @private
 * @constructor
 * @param {Object} config The config object
 */
Ext.ux.Wiz.West = Ext.extend(Ext.BoxComponent, {

    /**
     * @cfg {Number} height The height of this component. Defaults to "55".
     */
 //   height : 55,

    /**
     * @cfg {String} region The Region of this component. Since a {@link Ext.ux.Wiz}
     * usually uses a {@link Ext.layout.BorderLayout}, this property defaults to
     * "north". If you want to change this property, you should also change the appropriate
     * css-classes that are used for this component.
     */
  //  region : 'north',

    /**
     * @cfg {String} title The title that gets rendered in the head of the component. This
     * should be a text describing the purpose of the wizard.
     */
    title : 'Steps',

    /**
     * @cfg {Number} steps The overall number of steps the user has to go through
     * to finish the wizard.
     */
    steps : 0,
    border:false,

    stepsText : null,

    /**
     * @cfg {String} stepText The text in the header indicating the current process in the wizard.
     * (defaults to "Step {0} of {1}: {2}").
     * {0} is replaced with the index (+1) of the current card, {1} is replaced by the
     * total number of cards in the wizard and {2} is replaced with the title-property of the
     * {@link Ext.ux.Wiz.Card}
     * @type String
     */
    stepText : "{0}. {1}",

    /**
     * @cfg {Object} autoEl The element markup used to render this component.
     */
	autoEl : {
		tag : 'div',
		cls		 : 'ext-ux-wiz-West',
		children : [{
		  	tag		 : 'div',
		  	cls		 : 'ext-ux-wiz-West-title'
		}, {
			tag  : 'div',
			children : [{
				tag : 'div',
                cls : 'ext-ux-wiz-West-step'
			}]
		}]
	},

    /**
     * @param {Ext.Element}
     */
  	titleEl : null,

    /**
     * @param {Ext.Element}
     */
 //   stepEl  : null,

    /**
     * @param {Ext.Element}
     */
  	stepsContainer : null,

    /**
   * @param {Ext.ux.Wiz}
   */
    wizard : null,

    /**
     * @param {Array}
     */
 // 	indicators : null,
    indicatorsText : null,

  	/**
  	 * @param {Ext.Template}
  	 */
  	stepTemplate : null,

  	/**
  	 * @param {Number} lastActiveStep Stores the index of the last active card that
  	 * was shown-
  	 */
  	lastActiveStep : -1,
                  

// -------- helper
    /**
     * Gets called by  {@link Ext.ux.Wiz#onCardShow()} and updates the header
     * with the approppriate information, such as the progress of the wizard
     * (i.e. which card is being shown etc.)
     *
     * @param {pos} if pos not specified uses currentStep The index of the card currently shown in
     * the wizard
     * @param {String} title The title-property of the {@link Ext.ux.Wiz.Card}
     *
     * @private
     */  	        
    updateStep : function(pos) {
        
        var currentStep = this.wizard.currentCard;
        if(pos) currentStep = pos;
      
        var currentCard = this.wizard.cards[currentStep];
        
        var html = this.stepTemplate.apply({
          0 : currentStep + 1,
          1 : currentCard.title
        });

        this.indicatorsText[currentStep].update(html);

        if(pos){
            this.stepsText[pos] = currentCard.title;
        }
        else{
            if (this.lastActiveStep != -1)
                this.indicatorsText[this.lastActiveStep].removeClass('ext-ux-wiz-West-stepText-active');
            this.indicatorsText[currentStep].addClass('ext-ux-wiz-West-stepText-active');            
            this.lastActiveStep = currentStep;
        }
        
    },
    addStep:function(pos,card){

        this.steps++;
    
        this.stepsText[pos] = card.title;

        var txt = txt = document.createElement('div');
        var step = pos+1;
        var html = this.stepTemplate.apply({
          0 : step,
          1 : this.stepsText[pos]
        });
        
        txt.innerHTML = html;
        txt.className = 'ext-ux-wiz-West-stepText';

        this.indicatorsText[pos] = new Ext.Element(txt);
		this.stepsContainer.appendChild(txt);	
        
    },
    removeStep:function(pos,card){
        
        this.steps--;
        
        var txt = this.indicatorsText[pos];
        Ext.removeNode(txt);
        
        this.stepsText.splice(pos,1);
        this.indicatorsText.splice(pos,1);
        
    },
// -------- listener
    /**
     * Overrides parent implementation to render this component properly.
     */
	onRender : function(ct, position)
	{        
		Ext.ux.Wiz.Header.superclass.onRender.call(this, ct, position);

	//	this.indicators   = [];
        this.indicatorsText   = [];
		this.stepTemplate = new Ext.Template(this.stepText),
		this.stepTemplate.compile();

	    var el = this.el.dom.firstChild;
	    var ns = el.nextSibling;
        var html = '';

		this.titleEl        = new Ext.Element(el);
//		this.stepEl         = new Ext.Element(ns.firstChild);
		this.stepsContainer = new Ext.Element(ns.lastChild);

		this.titleEl.update(this.title);

        var txt = null;
		for (var i = 0, len = this.steps; i < len; i++) {
            var step = i+1;
			txt = document.createElement('div');

            html = this.stepTemplate.apply({
                        0 : step,
                        1 : this.stepsText[i]
            });

            txt.innerHTML = html;
        

		//    txt.innerHTML = step+". "+this.stepsText[i];
            txt.className = 'ext-ux-wiz-West-stepText';

            // Button version
            //
            // var btn = new Ext.Button({text:this.stepsText[i],cls:'buttonAsLink',
            //                        handler: function(){
            //                                    Ext.Msg.alert('Click', 'You did something.');
            //                        }});
            // btn.render(txt);


        //  image = document.createElement('div');
		//   image.innerHTML = "&#160;";

		//	image.className = 'ext-ux-wiz-Header-stepIndicator';
		//	this.indicators[i] = new Ext.Element(image);
            this.indicatorsText[i] = new Ext.Element(txt);
		//	this.imageContainer.appendChild(image);
            this.stepsContainer.appendChild(txt);
		}

	}
});