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
    public function executeView(sfWebRequest $request)
    {
        // get default Group ID to mark as cannot delete
        $this->defaultGroupID = sfGuardGroupPeer::getDefaultGroup()->getId();
    }
    public function executeUpdatesys($request){ 
    } 

    public function executeSignin($request)
    {
        $user = $this->getUser();

        $lang = $request->getParameter('lang');
        if($lang) $user->setCulture($lang);
        
        if ($user->isAuthenticated()) return $this->redirect('@homepage');
        
        if($this->getRequest()->isXmlHttpRequest() == true){
            $this->getResponse()->setHttpHeader('Content-type', 'application/json');


            $class = sfConfig::get('app_sf_guard_plugin_signin_form', 'sfGuardFormSignin');
            $this->form = new $class();

            if ($request->isMethod('post')){
                $this->form->bind($request->getParameter('signin'));

                if ($this->form->isValid()){
                    
                    $values = $this->form->getValues();

                    $lastlogin = $values['user']->getLastLogin();

                    $this->getUser()->signin($values['user'], array_key_exists('remember', $values) ? $values['remember'] : false);

                    //added firstlogin session to store if is first login made
                    //used to popup user change password window on first login
                    $this->getUser()->setAttribute('user_firstlogin',$lastlogin ? false : true);

                // always redirect to a URL set in app.yml
                // or to the referer
                // or to the homepage
                    $u_ref = $user->getReferer('@homepage');
                    if($u_ref == '@homepage') $u_ref = '/';
                    
                    $signinUrl = sfConfig::get('app_sf_guard_plugin_success_signin_url', $u_ref);

                    $this->getUser()->initVncToken();
                    
                // return $this->redirect($signinUrl);
                    $response = array("success" => true, "redirect" => $signinUrl);
                    $result=json_encode($response);                    
                    return $this->renderText($result);

                }
                else{ // NOT valid
                              
                    $errors = array();
                    foreach ($this->form->getErrorSchema() as $field => $error) {                        
                        $errors[$field] = $this->getContext()->getI18N()->__($error->getMessageFormat(), $error->getArguments());                        
                    }                    
                    
                    //  return sfView::ERROR;
                    $response=array('success' => false, 'error' => $errors, 'info' =>$this->errors);

                    $result=json_encode($response);                    
                    $this->getResponse()->setStatusCode(401);
                    return $this->renderText($result);
                }


            } //END POST           
        } // END XmlHttpRequest
        
        parent::executeSignin($request);

  }
    
    /*
     * change user password
     * @params cur_pwd,pwd,pwd_again 
     */
    public function executeJsonChangePwd(sfWebRequest $request)
    {
        $pwd_again = $request->getParameter('pwd_again');
        $pwd = $request->getParameter('pwd');
        $cur_pwd = $request->getParameter('cur_pwd');
        $user = $this->getUser();

        if($pwd!==$pwd_again){
            $response = array('success' => false, 'agent'=>sfConfig::get('config_acronym'),'info'=>$this->getContext()->getI18N()->__('Passwords do not match'));
            $result = json_encode($response);

            return $this->renderText($result);
        }

        if($user->checkPassword($cur_pwd)){
            $user->setPassword($pwd);

            $response = array('success' => true,'agent'=>sfConfig::get('config_acronym'),'response'=>$this->getContext()->getI18N()->__('New password saved!'));
        }
        else $response = array('success' => false, 'agent'=>sfConfig::get('config_acronym'),'info'=>$this->getContext()->getI18N()->__('Wrong user password'));

        $result = json_encode($response);

        return $this->renderText($result);                
  }



}
