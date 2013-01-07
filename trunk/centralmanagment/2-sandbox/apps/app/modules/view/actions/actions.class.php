<?php

/**
 * view actions.
 *
 * @package    centralM
 * @subpackage view
 * @author     Your name here
 * @version    SVN: $Id: actions.class.php 12474 2008-10-31 10:41:27Z fabien $
 */
class viewActions extends sfActions
{

  /**
   *
   * show online help page
   *
   */
  public function executeHelp(sfWebRequest $request)
  {
      $this->module = $request->getParameter('mod'); //module to load template from
      $lang = sfContext::getInstance()->getUser()->getCulture();
      $this->tmpl = 'help'.strtoupper($lang);

      $directory = $this->context->getConfiguration()->getTemplateDir($this->module, $this->tmpl.'Success.php');
      if($directory)
        $this->setTemplate($this->tmpl, $this->module);
      else{
            // no module help page found...show lang 'no help page available'
            $module = 'view';
            $tmpl = 'nohelp'.strtoupper($lang);
            
            $directory = $this->context->getConfiguration()->getTemplateDir($module, $tmpl.'Success.php');

            if($directory)
                $this->setTemplate($tmpl, $module);
            else{
                // no lang 'no help page available' page found...show default EN 'no help page available'
                $tmpl = 'nohelpEN';
                $this->setTemplate($tmpl);
            }
            
      }
  }

  public function executeIndex(sfWebRequest $request)
  {            
//    if(update::checkDbVersion() == 0){
//
//    }else{
//        
//    }


    $this->node_list = EtvaNodePeer::doSelect(new Criteria());
    $this->node_form = new EtvaNodeForm();
    $etva_data = Etva::getEtvaModelFile();
    $this->etvamodel = $etva_data['model'];

    if ($this->getUser()->isFirstRequest())
    {
        $etva_data = Etva::getEtvaModelFile();
        $etvamodel = $etva_data['model'];
        // remove session macs for cleanup the wizard
        $this->getUser()->getAttributeHolder()->remove('etvamodel');
        // store the new mac back into the session
        $this->getUser()->setAttribute('etvamodel', $etvamodel);

        $this->getUser()->isFirstRequest(false);
    }

    
    //$action = $this->getController()->getAction('node','bulkUpdateState');
    //$result = $action->executeBulkUpdateState($this->request);


  }

  /*
   * First time wizard setup
   */
  public function executeView_FirstTimeWizard(sfWebRequest $request)
  {

    $vlan_form = new EtvaVlanForm();
    $vlanid_val = $vlan_form->getValidator('vlanid');
    $this->min_vlanid = $vlanid_val->getOption('min');
    $this->max_vlanid = $vlanid_val->getOption('max');

    $vlanname_val = $vlan_form->getValidator('name');
    $this->min_vlanname = $vlanname_val->getOption('min_length');
    $this->max_vlanname = $vlanname_val->getOption('max_length');


  }

    /**
     * Perform operations on table setting
     *
     * @param      string $param parameter name to perform operation
     * @param      string $value value for the $param
     * @param      string $method Operation to perform (update). Default will list value
     * 
     */
  public function executeJsonSetting(sfWebRequest $request)
  {
      $method = $request->getParameter('method');
      
      switch($method){
          case 'update':              
              $this->forward('view', 'jsonUpdateSetting');
              break;
          default:
              $param = $request->getParameter('param');
      $etva_setting = EtvaSettingPeer::retrieveByPk($param);      

      if(!$etva_setting){
          $msg =  array('success'=>false,'data'=>array());
          return $this->renderText(json_encode($msg));
      }      

      $data = array($etva_setting->getParam() => $etva_setting->getValue());
      
                $msg =  array('success'=>true,'data'=>$data);

                return $this->renderText(json_encode($msg));
              break;
          
              
      }
      


  }


    /**
     * Perform update on table setting
     *
     * @param      string $param parameter name to perform operation
     * @param      string $value value for the $param
     *     
     */
  public function executeJsonUpdateSetting(sfWebRequest $request)
  {
        $isAjax = $request->isXmlHttpRequest();

        if(!$isAjax) return $this->redirect('@homepage');

        if(!$request->isMethod('post') && !$request->isMethod('put')){
            $info = array('success'=>false,'error'=>'Wrong parameters');
            $error = $this->setJsonError($info);
            return $this->renderText($error);
        }

        $param = $request->getParameter('param');

        if(!$etva_setting = EtvaSettingPeer::retrieveByPk($param)){
            $error_msg = sprintf('Object etva_setting does not exist (%s).', $param);
            $info = array('success'=>false,'error'=>$error_msg);
            $error = $this->setJsonError($info);
            return $this->renderText($error);
        }

        $etva_setting->setValue($request->getParameter('value'));
        $param = $request->getParameter('param');

        switch($param){
            case 'vnc_keymap' : if($etva_setting->saveVNCkeymap()){
                                    //notify system log
                                    $this->dispatcher->notify(
                                        new sfEvent(sfConfig::get('config_acronym'),
                                                    'event.log',
                                                     array('message' => Etva::getLogMessage(array('name'=>$etva_setting->getValue()), EtvaSettingPeer::_OK_VNCKEYMAP_CHANGE_))
                                        ));
                                                                
                                }else{
                                    //notify system log
                                    $this->dispatcher->notify(
                                        new sfEvent(sfConfig::get('config_acronym'),
                                                    'event.log',
                                                    array('message' => Etva::getLogMessage(array('name'=>$request->getParameter('value')), EtvaSettingPeer::_ERR_VNCKEYMAP_CHANGE_),'priority'=>EtvaEventLogger::ERR)));
                                }
                                break;
            default:
                                $etva_setting->save();
                                break;
        }
        

        $result = array('success'=>true,'agent'=>sfConfig::get('config_acronym'),'info'=>sfConfig::get('config_acronym'));
        $result = json_encode($result);
        $this->getResponse()->setHttpHeader('Content-type', 'application/json');
        return $this->renderText($result);

  }

  /*
   * displays jupload applet to perform FTP uploads
   */
  public function executeJupload(sfWebRequest $request)
  {
      $response = $this->getResponse();
      $response->setTitle(sfConfig::get('config_acronym').' :: ISO Upload');
      $this->lang = $this->getUser()->getCulture();
      $this->postUrl = 'ftp://'.
                       sfConfig::get("config_isos_user").':'.sfConfig::get("config_isos_password").'@'.
                       $_SERVER['SERVER_ADDR'];
      
  }  

  public function executeVncviewer(sfWebRequest $request)
  {

      if( $request->getParameter('sleep') ){
          $tsleep = $request->getParameter('sleep');
          sleep($tsleep);
      }

      $etva_server = EtvaServerPeer::retrieveByPk($request->getParameter('id'));

      if(!$etva_server) return sfView::NONE;            
      
      $etva_node = $etva_server->getEtvaNode();
            
      $user = $this->getUser();
      $tokens = $user->getGuardUser()->getEtvaVncTokens();

      $this->username = $tokens[0]->getUsername();
      $this->token = $tokens[0]->getToken();

      $proxyhost1 = $request->getHost();
      $proxyhost1_arr = split(':',$proxyhost1);
      $proxyhost1 = $proxyhost1_arr[0];

      $proxyport1 = 80;
      if( $proxyhost1_arr[1] ) $proxyport1 = $proxyhost1_arr[1];

      $this->proxyhost1 = $proxyhost1;
      $this->proxyport1 = $proxyport1;

      $this->host = $etva_node->getIp();

      //if host is localhost address then is the same machine
      if($this->host == '127.0.0.1') $this->host = $proxyhost1;

      $this->port = $etva_server->getVncPort();
     
      $response = $this->getResponse();
      $response->setTitle($etva_server->getName().' :: Console');      

  }

  public function executeView(sfWebRequest $request)
  {
      $this->node_form = new EtvaNodeForm();
      
      $this->node_tableMap = EtvaNodePeer::getTableMap();
      
      // parent extjs container id
      $this->containerId = $request->getParameter('containerId');

      
            

            
      // $this->request->setParameter('id', 1);
      
     // $this->request->setParameter('method', 'list_vms');
    //  $this->dispatcher->notify(new sfEvent($this, 'updatedsoap'));
    // $this->forward('node','soap');


// WORKING!!!
// CAN BE USED TO PERFORM EXTRA STUFF
    // $action = sfContext::getInstance()->getController()->getAction('node','soap');
    // $action = $this->getController()->getAction('node','soap');
    // $result = $action->executeSoap($this->request,1);
// END WORKING

    // return sfView::SUCCESS;
    //sfContext::getInstance()->getController()->dispatch('somemodule', 'someaction');

      
  }

  public function executeNetworks(sfWebRequest $request)
  {
      $this->network_form = new EtvaNetworkForm();
      $this->network_tableMap = EtvaNetworkPeer::getTableMap();
      
      $this->vlan_tableMap = EtvaVlanPeer::getTableMap();
    
  }

  /*
   * isos management
   */
  public function executeIso(sfWebRequest $request)
  {    
     $action = $request->getParameter('doAction');
    
     switch($action){
           case 'jsonList' :                                
                                $this->forward('view', 'isoJsonList',$request);
                                break;
         case 'jsonUpload' :
                                $this->forward('view', 'isoJsonUpload',$request);
                                break;
           
         case 'jsonDelete' :
                                $this->forward('view', 'isoJsonDelete',$request);
                                break;
         case 'jsonRename' :
                                $this->forward('view', 'isoJsonRename',$request);
                                break;
                   default :
                                $msg =  array('success' => false, 'message' => 'Could not find action ' . $action);    
                                $response = json_encode($msg);
                                return $this->renderText($response);
                                break;
     }

     

  }

  public function executeIsoJsonUpload(sfWebRequest $request)
  {
            
     $directory = sfConfig::get("config_isos_dir");

     foreach ($request->getFiles() as $file) {

        if (is_uploaded_file($file['tmp_name'])) {
            // Set the filename for the uploaded file
            $filename = $directory . "/" . $file['name'];

            if (file_exists($filename) == true) {
                // File already exists \\
                $msg =  array('success'=>false,'message'=>$file['name'] . ' already exists');                
            } else if (copy($file['tmp_name'], $filename) == false) {
                // File can not be copied \\
                $msg =  array('success'=>false,'message'=>'Could not upload '.$file['name']);
            } else {
                $msg =  array('success'=>true,'message'=>'Upload complete');
            }
            
            $response = json_encode($msg);
        }
     }
     return $this->renderText($response);
  }

  public function executeIsoJsonRename(sfWebRequest $request)
  {
     $directory = sfConfig::get("config_isos_dir");
     $file = $request->getParameter('file');
     $file_path = $directory . '/' . $file;
     
     $new_name = $request->getParameter('new_name');                    
     $sys_msg = exec('stat -c "%F" '. escapeshellarg ($file_path),$sys_call);
     
     if(empty($sys_call)){
         $info_message = Etva::getLogMessage(array('name'=>$file,'info'=>''), ETVA::_ERR_ISO_PROBLEM_);
         $msg_i18n = $this->getContext()->getI18N()->__(ETVA::_ERR_ISO_RENAME_,array('%info%'=>$info_message));

         $msg = array('success'=>false,'message'=>$msg_i18n);
         $response = json_encode($msg);
         return $this->renderText($response);
     }


     $errors = Etva::verify_iso_usage($file);

     if($errors){

        $info_message = Etva::getLogMessage(array('name'=>$file,'info'=>ETVA::_CDROM_INUSE_), ETVA::_ERR_ISO_INUSE_);
        $message = Etva::getLogMessage(array('info'=>$info_message), ETVA::_ERR_ISO_RENAME_);
        $this->dispatcher->notify(new sfEvent(sfConfig::get('config_acronym'), 'event.log',array('message' => $message,'priority'=>EtvaEventLogger::ERR)));

        $i18n_br_sep = implode('<br>',$errors);
        $i18n_sp_sep = implode(' ',$errors);

        $i18n_iso_br_msg = $this->getContext()->getI18N()->__(ETVA::_ERR_ISO_INUSE_,array('%name%'=>$file,'%info%'=>'<br>'.$i18n_br_sep));
        $i18n_iso_sp_msg = $this->getContext()->getI18N()->__(ETVA::_ERR_ISO_INUSE_,array('%name%'=>$file,'%info%'=>$i18n_sp_sep));

        $message_i18n = $this->getContext()->getI18N()->__(ETVA::_ERR_ISO_PROBLEM_,array('%name%'=>$file,'%info%'=>''));

        $msg = array('success'=>false,'agent'=>sfConfig::get('config_acronym'),'message'=>$message_i18n,'info'=>$i18n_iso_br_msg,'error'=>$i18n_iso_sp_msg);
        $error = $this->setJsonError($msg);
        return $this->renderText($error);
    }

     if ($file && $request->isMethod('post' ) && stristr($sys_msg, 'file'))
     {
        
        if (rename($file_path, $directory . "/" . $new_name))
            $msg = array('success'=>true);
        else
            $msg = array('success'=>false,'message'=>$msg_i18n);
        
    } else {
        $msg = array('success'=>false,'message'=>$msg_i18n);
    }

    $response = json_encode($msg);
    return $this->renderText($response);
      
  }
  /*
   *
   */
  public function executeIsoJsonList(sfWebRequest $request)
  {    

    $directory = sfConfig::get("config_isos_dir");
    $data = array();    

    $dir = opendir($directory);

    if($dir===false){
        $data['success'] = false;        

        $data['agent'] = sfConfig::get('config_acronym');
        $data['error'] = 'Cannot open '.$directory;

        $response = json_encode($data);
        return $this->renderText($response);
    }

    $i = 0;

    /*
     * check for params array. if emptyValue=true return empty record
     */
    if($request->getParameter('params')){
        $params = json_decode($request->getParameter('params'),true);
        if($params['emptyValue']==true){
            $results[$i]['name']        = 'None';
            $results[$i]['size']        = 0;
            $results[$i]['ctime']       = 0;
            $results[$i]['mtime']       = 0;
            $results[$i]['full_path']   = '';
            $i++;
        }
    }

    

    // Get a list of all the files in the directory
    while ($temp = readdir($dir)) {
        
        if (is_dir($directory . "/" . $temp)) continue; // If its a directory skip it

        $file_path = $directory . '/' . $temp;

        /*
         * used system call 'stat' to overcome 32bit integer limitation
         */
        $stats_info = exec ('stat -c "%s %Z %Y" '. escapeshellarg ($file_path));

        $stats = explode(' ',$stats_info);

        $results[$i]['name']        = $temp;
        $results[$i]['size']        = $stats[0];                        
        $results[$i]['ctime']       = $stats[1];
        $results[$i]['mtime']       = $stats[2];
        $results[$i]['full_path']   = $file_path;
        
        $i++;
    }

    if (is_array($results)) {
        $data['count'] = count($results);
        $data['data'] = $results;
    } else {
        $data['count'] = 0;
        $data['data'] = '';
    }

    $response = json_encode($data);
    return $this->renderText($response);

  }


  public function executeIsoJsonDelete(sfWebRequest $request)
  {
    $directory = sfConfig::get("config_isos_dir");
    $file = $request->getParameter('file');
    $file_path = $directory . '/' . $file;

    $sys_msg = exec('stat -c "%F" '. escapeshellarg ($file_path),$sys_call);
    
    if(empty($sys_call)){
        $info_message = Etva::getLogMessage(array('name'=>$file,'info'=>''), ETVA::_ERR_ISO_PROBLEM_);
        $msg_i18n = $this->getContext()->getI18N()->__(ETVA::_ERR_ISO_DELETE_,array('%info%'=>$info_message));

        $msg = array('success'=>false,'message'=>$msg_i18n);
        $response = json_encode($msg);
        return $this->renderText($response);
    }


    $errors = Etva::verify_iso_usage($file);

    if($errors){

        $info_message = Etva::getLogMessage(array('name'=>$file,'info'=>ETVA::_CDROM_INUSE_), ETVA::_ERR_ISO_INUSE_);
        $message = Etva::getLogMessage(array('info'=>$info_message), ETVA::_ERR_ISO_DELETE_);
        $this->dispatcher->notify(new sfEvent(sfConfig::get('config_acronym'), 'event.log',array('message' => $message,'priority'=>EtvaEventLogger::ERR)));

        $i18n_br_sep = implode('<br>',$errors);
        $i18n_sp_sep = implode(' ',$errors);
        
        $i18n_iso_br_msg = $this->getContext()->getI18N()->__(ETVA::_ERR_ISO_INUSE_,array('%name%'=>$file,'%info%'=>'<br>'.$i18n_br_sep));
        $i18n_iso_sp_msg = $this->getContext()->getI18N()->__(ETVA::_ERR_ISO_INUSE_,array('%name%'=>$file,'%info%'=>$i18n_sp_sep));

        $message_i18n = $this->getContext()->getI18N()->__(ETVA::_ERR_ISO_PROBLEM_,array('%name%'=>$file,'%info%'=>''));

        $msg = array('success'=>false,'agent'=>sfConfig::get('config_acronym'),'message'=>$message_i18n,'info'=>$i18n_iso_br_msg,'error'=>$i18n_iso_sp_msg);
        $error = $this->setJsonError($msg);
        return $this->renderText($error);
    }


    if ($file && $request->isMethod('post' ) && stristr($sys_msg, 'file'))
    {
        if (unlink($file_path))
            $msg =  array('success' => true);
        else
            $msg =  array('success' => false, 'message' => $msg_i18n);

    } else {
        $msg =  array('success' => false, 'message' => $msg_i18n);
    }

    $response = json_encode($msg);
    return $this->renderText($response);
    
  }
  
  public function executeIsoDownload(sfWebRequest $request)
  {
    $directory = sfConfig::get("config_isos_dir");
    $file = $request->getParameter('file');

    $filepath = $directory . "/" . $file;


    if(dirname($filepath)==$directory){
        if ($directory && $file && is_file($filepath)){
            $response = $this->getResponse();
            $response->clearHttpHeaders();
            $response->setHttpHeader('Pragma: public', true);
            $response->setHttpHeader('Content-Length', sprintf("%u",filesize($filepath)));
            $response->setContentType('application/x-download');
            $response->setHttpHeader('Content-Disposition',
                            'attachment; filename="'.
                            $file.'"');
            $response->sendHttpHeaders();
            ob_end_clean();

            $this->getResponse()->setContent(IOFile::readfile_chunked($filepath));    
        }        
    }
    return sfView::NONE;
    
  }


  protected function setJsonError($info,$statusCode = 400){

    if(isset($info['faultcode']) && $info['faultcode']=='TCP') $statusCode = 404;
    $this->getContext()->getResponse()->setStatusCode($statusCode);
    $error = json_encode($info);
    $this->getResponse()->setHttpHeader('Content-type', 'application/json');
    return $error;

  }



}
