<?php

/**
 * etfs actions.
 *
 * @package    centralM
 * @subpackage etfs
 * @author     Your name here
 * @version    SVN: $Id: actions.class.php 23810 2009-11-12 11:07:44Z Kris.Wallsmith $
 */
class etfsActions extends sfActions
{
 /**
  * Executes view action
  *
  * @param sfRequest $request A request object
  */
  public function executeView(sfWebRequest $request)
  {
    $this->etva_server = EtvaServerPeer::retrieveByPK($request->getParameter('sid'));
  }

  /*
   * processes json requests and invokes dispatcher
   */
  public function executeJson(sfWebRequest $request)
  {
    $service_id = $request->getParameter('id');
    $etva_service = EtvaServicePeer::retrieveByPK($request->getParameter('id'));
    if(!$etva_service){
        $msg_i18n = $this->getContext()->getI18N()->__('No service with specified id',array());
        $msg = array('success'=>false,'error'=>$msg_i18n,'info'=>$msg_i18n);
        $result = $this->setJsonError($msg);
        $this->getResponse()->setHttpHeader('Content-type', 'application/json');
        return $this->renderText($result);
    }

    $etva_server = $etva_service->getEtvaServer();

    $agent_tmpl =$etva_server->getAgentTmpl();
    $service_tmpl = $etva_service->getNameTmpl();
    $method = $request->getParameter('method');
    $mode = $request->getParameter('mode');
    $params = json_decode($request->getParameter('params'),true);

    if(!$params) $params = array();


    $dispatcher_tmpl = $agent_tmpl.'_'.$service_tmpl;

    if(method_exists($this,$dispatcher_tmpl))
    {
        $ret = call_user_func_array(array($this, $dispatcher_tmpl), array($etva_server,$method,$params,$mode,$service_id));

        if($ret['success'])
            $result = json_encode($ret);
        else
            $result = $this->setJsonError($ret);
    }else{
        $msg_i18n = $this->getContext()->getI18N()->__('No method implemented! %dispatcher%',array('%dispatcher%'=>$dispatcher_tmpl));
        $info = array('success'=>false,'error'=>$msg_i18n);
        $result = $this->setJsonError($info);
    }

    $this->getResponse()->setHttpHeader('Content-type', 'application/json; charset=utf-8');
    $this->getResponse()->setHttpHeader("X-JSON", '()');

    return $this->renderText($result);
  }

  public function ETFS_main(EtvaServer $etva_server, $r_method, $params,$mode, $service_id)
  {

      $method = str_replace(array("_cbx","_tree","_grid","_filterBy","_only"),"",$r_method);

      // prepare soap info....
      $initial_params = array(
                      //'dispatcher'=>'wizard'
      );

      $call_params = array_merge($initial_params,$params);

      // send soap request
      $response = $etva_server->soapSend($method,$call_params);        

      // if soap response is ok
      if($response['success']){
          $response_decoded = (array) $response['response'];

          if($mode) $method = $mode;

          switch($r_method){
                  case 'list_shares_filterBy':
                              $share_name = $params['name'];
                              $error_i18n = $this->getContext()->getI18N()->__('Share \'%name%\' not found.',array('%name%'=>$share_name));
                              $info_i18n = $this->getContext()->getI18N()->__('Share \'%name%\' not found.',array('%name%'=>$share_name));
                              $return = array('success' => false,
                                              'error'=>$error_i18n,
                                              'info'=>$info_i18n);

                              $shares_data = (array)$response_decoded;
                              foreach($shares_data as $sObj){
                                  $share = (array)$sObj;
                                  if( $share['name'] == $share_name ){
                                      $return = array( 'success'=>true, 'data'=>$share );
                                      break;
                                  }
                              }
                              break;
                  case 'list_shares_only':
                              $shares_data = (array)$response_decoded;
                              $return_data = array();
                              foreach($shares_data as $sObj){
                                  $share = (array)$sObj;
                                  if( $share['name'] !== 'global' ){
                                      array_push($return_data,$share);
                                  }
                              }
                              $return = array( 'success'=>true, 'data'=>$return_data );
                              break;
                  case 'list_groups':
                  case 'list_smb_users':
                  case 'list_users':
                  case 'list_shares':
                  case 'get_samba_status':
                  case 'get_samba_status_raw':
                  case 'get_global_configuration':
                  case 'status_service':
                              $return_data = (array)$response_decoded;
                              $return = array( 'success'=>true, 'data'=>$return_data );
                              break;
                  /*case 'get_samba_status_raw':
                              $return_data = (array)$response_decoded;
                              error_log(print_r($return_data,true));
                              $return_data['status'] = str_replace("\\n","<br/>",$return_data['status']);
                              error_log(print_r($return_data,true));
                              $return = array( 'success'=>true, 'data'=>$return_data );
                              break;*/
                  case 'create_share':
                  case 'update_share':
                  case 'delete_share':
                  case 'create_user':
                  case 'update_user':
                  case 'delete_user':
                  case 'start_service':
                  case 'restart_service':
                  case 'stop_service':
                  case 'set_global_configuration':
                  case 'join_to_domain':
                              $okmsg_i18n = $this->getContext()->getI18N()->__($response_decoded['_okmsg_'],array());
                              $return = array( 'success'=>true, 'data'=>$data, 'response'=>$okmsg_i18n );
                              break;
                  default:
                              $error_i18n = $this->getContext()->getI18N()->__('No action \'%method%\' defined yet.',array('%method%'=>$method));
                              $info_i18n = $this->getContext()->getI18N()->__('No action \'%method%\' implemented yet',array('%method%'=>$method));
                              $return = array('success' => false,
                                              'error'=>$error_i18n,
                                              'info'=>$info_i18n);
          }
          return $return;

      }else{

          $error_details = $response['info'];
          $error_details = nl2br($error_details);
          $error_details_i18n = $this->getContext()->getI18N()->__($error_details);
          $error = $response['error'];

          $result = array('success'=>false,'error'=>$error,'info'=>$error_details_i18n,'faultcode'=>$response['faultcode']);
          return $result;
      }
  }

  public function executeETFS_EditShare(sfWebRequest $request)
  {
  }

  public function executeETFS_EditGlobal(sfWebRequest $request)
  {
  }
  public function executeETFS_JoinToAD(sfWebRequest $request)
  {
  }

  public function executeETFS_EditUser(sfWebRequest $request)
  {
  }

  protected function setJsonError($info,$statusCode = 400)
  {
    if(isset($info['faultcode']) && $info['faultcode']=='TCP') $statusCode = 404;
    $this->getContext()->getResponse()->setStatusCode($statusCode);
    $error = json_encode($info);
    $this->getResponse()->setHttpHeader('Content-type', 'application/json');
    return $error;
  }
}
