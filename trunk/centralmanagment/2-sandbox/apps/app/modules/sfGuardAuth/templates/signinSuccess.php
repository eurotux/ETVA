<?php
use_stylesheet('main.css');

$sfExtjs3Plugin = new sfExtjs3Plugin(array('theme'=>'blue'),array('js' => sfConfig::get('sf_extjs3_js_dir').'src/locale/ext-lang-'.$sf_user->getCulture().'.js'));
$sfExtjs3Plugin->load();

/*
 * Create dynamic form fields from the form schema
 * TODO: create a helper??!!
 */
$fieldSc = $form->getFormFieldSchema();
$loginFormItems = '';
$widget = $fieldSc->getWidget();
$validatorSchema = $form->getValidatorSchema();

foreach($widget->getFields() as $key => $object){
    $label = $fieldSc->offsetGet($key)->renderLabelName();
    $type = $object->getOption('type');

    if($type=='text') $type = 'textfield';
  
    $name = $widget->generateName($key);
    $allowBlank = 'true';
    $extraItem = '';
    
    if($validatorSchema[$key] instanceOf sfValidatorCSRFToken){
        $csrfToken = $form->getDefault($key);
        $extraItem =  ",value:'".$csrfToken."'";
    }

    if(isset($validatorSchema[$key]) and $validatorSchema[$key]->getOption('required') == true) {
        $allowBlank = 'false';
    }

    $loginFormItems[] = "{fieldLabel: '".__($label)."',name: '".
               $name."',inputType:'".$type."',allowBlank:".
               $allowBlank.$extraItem."}\n\t\t";
}

$loginFormItems = implode(",", $loginFormItems);
$sfExtjs3Plugin->begin();
$sfExtjs3Plugin->end();
?>
<script type='text/javascript'>

Ext.namespace('Login');
  Login = function(){
    var loginWindow, loginForm;    
    return{
      init:function(){
        Ext.QuickTips.init();
        Ext.form.Field.prototype.msgTarget = 'side';
          
        <?php
        $culture = new sfCultureInfo(sfContext::getInstance()->getUser()->getCulture());
        $languages = $culture->getLanguages(array('pt','en'));
        $js_languages = array();

        foreach($languages as $language_code=>$language_name){
            $js_languages[] = "[".json_encode($language_code).', '.json_encode(ucfirst($language_name))."]";
        }
        $language_js = implode(',',$js_languages);

        echo "var language_data = [".$language_js."];";
        ?>

        //language_data
        /* Language chooser combobox  */
        var store = new Ext.data.ArrayStore({
            fields: ['code', 'language'],
            data : language_data});

        var combo = new Ext.form.ComboBox({
            //renderTo: 'languages',
            fieldLabel: <?php echo json_encode(__('Language')) ?>,
            store: store,
            displayField:'language',
            valueField:'code',
            typeAhead: true,
            mode: 'local',
            triggerAction: 'all',           
            value: <?php echo json_encode($sf_user->getCulture()); ?>,
            forceSelection:true,
            emptyText: 'Select a language...',
            selectOnFocus: true,
            onSelect: function(record) {
                window.location.search = Ext.urlEncode({"lang":record.get("code")});
            }
        });

        /*
         * Extjs Login Form
         * TODO: Get fields dynamicaly from symfony schema
         */
        loginForm = new Ext.FormPanel({
            baseCls: 'x-plain',
            labelWidth: 100,
            url:<?php echo json_encode(url_for('@signin')) ?>,
            defaultType: 'textfield',
            title : <?php echo json_encode(__('Login Information')) ?>,
            bodyStyle:'padding:10px 0 0 15px',
            items: [
                <?php echo $loginFormItems ?>
                ,combo

            //    {fieldLabel: 'Name',name: 'signin[username]',anchor:'90%',allowBlank:false},
             //   {fieldLabel: 'Password',name: 'signin[password]',anchor:'90%',allowBlank: false,inputType: 'password'},
             //   {fieldLabel: 'Remember me?',name: 'signin[remember]',labelSeparator: "",inputType: 'checkbox'}
            ]
            ,defaults:{
                enableKeyEvents:true,
                listeners:{
                    specialKey: function(field, el)
                        {
                            if(el.getKey() == Ext.EventObject.ENTER)
                            {
                                Login.submitForm();
                            }
                        }
                }
            }
        });        
        var defaultBtn = loginForm.form.findField('signin[username]');
        /*
         * Extj Login Window
         * Place where the login Form will be placed
         */
        loginWindow = new Ext.Window({
            title: 'ETVA - Central Management',
            width: 350,
            height:200,
            layout: 'fit',
            plain:true,
            closable: false,
            bodyStyle:'padding:5px;',
            buttonAlign:'center',
            items: loginForm,
            defaultButton: defaultBtn
        });

        loginWindow.addButton('Login', Login.submitForm, Login);

        loginWindow.on('show', function(){
            var f = loginForm.items.item(0);
            f.focus.defer(600, f);

        });


        //on browser resize, resize window
        Ext.EventManager.onWindowResize(loginWindow.center,loginWindow);

        loginWindow.show();

      },
        
      /*
       * processSuccess
       * It's called upon the success of submitting a form
       * It performs check in the return values: expected:
       *         success:true
       *         success:false
       */
      processSuccess: function(response, options){
        var resp = Ext.util.JSON.decode(response.responseText);        
        var succ_resp = resp.success;
        switch(succ_resp){
            case true:
                loginWindow.el.unmask();
                loginWindow.hide();                
                if(resp.redirect){
                    window.location = resp.redirect;
                }
                break;
            case false:
                Login.processFailure(response, options);
                break;
            default:
                Login.processFailure(response, options);
                break;
        }
      },
      /*
       * processFailure
       * It's called upon the failure of submitting a form or after a negative
       *  response from the form submission
       * It performs check in the return values: expected:
       *         success:false
       *         othervalue
       */
      processFailure: function(response, options)
      {        
        loginWindow.el.unmask();
        if(!response.responseText){
            Ext.MessageBox.show({
                title: <?php echo json_encode(__('Login Error')) ?>,
                msg: response.statusText,
                buttons: Ext.MessageBox.OK,
                fn: Login.focusForm,
                icon: Ext.MessageBox.ERROR
            });            
            return;
        }

        var resp = Ext.util.JSON.decode(response.responseText);
        var succ_resp = resp.success;
        var msg = '';
        
        switch(succ_resp){
            case false:
                var str = resp.error;
                for(prop in str)
                    msg += prop + ' '+ str[prop]+'<br />';//Concat prop and its value from object
                break;
            default:
                msg = <?php echo json_encode(__('Unknown error')) ?>;
                break;
        }
        
        Ext.MessageBox.show({
            title: <?php echo json_encode(__('Login Error')) ?>,
            msg: msg,
            buttons: Ext.MessageBox.OK,
            fn: Login.focusForm,
            icon: Ext.MessageBox.ERROR
        });
       
      },
      submitForm: function(){
        loginWindow.el.mask(<?php echo json_encode(__('Please wait...')) ?>, 'x-mask-loading');
        Ext.Ajax.request({
            url: loginForm.url,
            form : loginForm.getForm().getEl().dom,            
            method: 'POST',
            clientValidation: true,
            success: Login.processSuccess,
            failure: Login.processFailure,
            scope: Login
        });
      },
      focusForm: function(){
        var f = loginForm.items.item(0);
        f.focus.defer(500, f);
      }
    }
  }();
Ext.onReady(Login.init, Login, true);
</script>
<div class="header-login"></div>
<div class="footer-login"></div>