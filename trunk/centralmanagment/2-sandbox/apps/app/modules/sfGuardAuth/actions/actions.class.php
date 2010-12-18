<?php
require_once(sfConfig::get('sf_plugins_dir').'/sfGuardPlugin/modules/sfGuardAuth/lib/BasesfGuardAuthActions.class.php');

/**
 *
 * @package    symfony
 * @subpackage plugin
 * @author     Fabien Potencier <fabien.potencier@symfony-project.com>
 * @version    SVN: $Id: actions.class.php 2278 2006-10-01 13:30:31Z fabien $
 */
class sfGuardAuthActions extends BasesfGuardAuthActions
{
    public function executeSignin($request){
        $user = $this->getUser();


        if ($user->isAuthenticated()) return $this->redirect('@homepage');
        
        if($this->getRequest()->isXmlHttpRequest() == true){


            $class = sfConfig::get('app_sf_guard_plugin_signin_form', 'sfGuardFormSignin');
            $this->form = new $class();

            if ($request->isMethod('post')){
                $this->form->bind($request->getParameter('signin'));

                if ($this->form->isValid()){
                    
                    $values = $this->form->getValues();
                    
                    $this->getUser()->signin($values['user'], array_key_exists('remember', $values) ? $values['remember'] : false);
                // always redirect to a URL set in app.yml
                // or to the referer
                // or to the homepage
                    $signinUrl = sfConfig::get('app_sf_guard_plugin_success_signin_url', $user->getReferer('@homepage'));

                    $this->getUser()->initVncToken();

                // return $this->redirect($signinUrl);
                    $response = array("success" => true, "redirect" => $signinUrl);
                    $result=json_encode($response);
                    $this->getResponse()->setHttpHeader("X-JSON", '()'); // set a header, although it is empty...
                    return $this->renderText($result);

                }
                else{ // NOT valid
                    foreach ($this->form->getErrorSchema() as $field => $error) {
                        $errors[$field] = $error->getMessage();

                    }                 
                    $this->errors = $errors;
                    //  return sfView::ERROR;
                    $response=array('success' => false, 'error' => $this->errors);

                    $result=json_encode($response);
                    $this->getResponse()->setHttpHeader("X-JSON", '()'); // set a header, although it is empty...
                    $this->getResponse()->setStatusCode(401);
                    return $this->renderText($result);
                }


            } //END POST           
        } // END XmlHttpRequest
        
        parent::executeSignin($request);

  }



}