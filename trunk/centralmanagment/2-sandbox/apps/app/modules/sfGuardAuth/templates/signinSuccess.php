<?php
$sfExtjs3Plugin = new sfExtjs3Plugin(array('theme'=>'blue'));
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

    $loginFormItems[] = "{fieldLabel: '".$label."',name: '".
               $name."',inputType:'".$type."',allowBlank:".
               $allowBlank.$extraItem."}\n\t\t";
}

$loginFormItems = implode(",", $loginFormItems);

?>
<script type='text/javascript'>
// sfExtjs2Helper: v0.60
Ext.BLANK_IMAGE_URL = '/sfExtjs2Plugin/extjs/resources/images/default/s.gif';

Ext.namespace('Login');
  Login = function(){
    var loginWindow, loginForm;
    return{
      init:function(){
        Ext.QuickTips.init();
        Ext.form.Field.prototype.msgTarget = 'side';


        /*
         * Extjs Login Form
         * TODO: Get fields dynamicaly from symfony schema
         */
        loginForm = new Ext.FormPanel({
            baseCls: 'x-plain',
            labelWidth: 100,
            url:<?php echo json_encode(url_for('@login')) ?>,
            defaultType: 'textfield',
            title: 'Login Information',
            bodyStyle:'padding:10px 0 0 15px',
            items: [
                <?php echo $loginFormItems ?>
            //    {fieldLabel: 'Name',name: 'signin[username]',anchor:'90%',allowBlank:false},
             //   {fieldLabel: 'Password',name: 'signin[password]',anchor:'90%',allowBlank: false,inputType: 'password'},
             //   {fieldLabel: 'Remember me?',name: 'signin[remember]',labelSeparator: "",inputType: 'checkbox'}
            ]
        });

        /*
         * Extj Login Window
         * Place where the login Form will be placed
         */
        loginWindow = new Ext.Window({
            title: 'ETVA - Central Management',
            width: 350,
            height:190,
            layout: 'fit',
            plain:true,
            closable: false,
            bodyStyle:'padding:5px;',
            buttonAlign:'center',
            items: loginForm
        });

        loginWindow.addButton('Login', Login.submitForm, Login);

        loginWindow.on('show', function(){
            var f = loginForm.items.item(0);
            f.focus.defer(300, f);
          
        });

        loginWindow.show(Ext.get('toolbar'));

        var usernameMap = new Ext.KeyMap(loginForm.items.item(0).getEl(), {
            key: Ext.EventObject.ENTER,
            fn: function(){loginForm.items.item(1).focus();}
        });

        var passwordMap = new Ext.KeyMap(loginForm.items.item(1).getEl(), {
            key: Ext.EventObject.ENTER,
            fn: Login.submitForm,
            scope: Login
        });
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
      processFailure: function(response, options){
        var resp = Ext.util.JSON.decode(response.responseText);
        var succ_resp = resp.success;
        var msg = '';

        loginWindow.el.unmask();
        switch(succ_resp){
            case false:
                var str = resp.errors;
                for(prop in str)
                    msg += prop + ' '+ str[prop]+'<br />';//Concat prop and its value from object
                break;
            default:
                msg = 'Unknown error';
                break;
        }
        
        Ext.MessageBox.show({
            title: 'Login Error',
            msg: msg,
            buttons: Ext.MessageBox.OK,
            fn: Login.focusForm,
            icon: Ext.MessageBox.ERROR
        });
       
      },
      submitForm: function(){
        loginWindow.el.mask('Please wait...', 'x-mask-loading');
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
        f.focus.defer(100, f);
      }
    }
  }();
Ext.onReady(Login.init, Login, true);
</script>