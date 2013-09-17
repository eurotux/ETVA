<?php

/**
 * etms actions.
 *
 * @package    centralM
 * @subpackage etms
 * @author     Your name here
 * @version    SVN: $Id: actions.class.php 23810 2009-11-12 11:07:44Z Kris.Wallsmith $
 */
class etmsActions extends sfActions
{
 /**
  * Executes index action
  *
  * @param sfRequest $request A request object
  */
  public function executeView(sfWebRequest $request)
  {
    $dispatcher_id = $request->getParameter('dispatcher_id');
        // used to get parent id component (extjs)
        //$this->containerId = $request->getParameter('containerId');

        // load modules file of dispatcher
        if($dispatcher_id){

            $criteria = new Criteria();
            $criteria->add(EtvaServicePeer::ID,$dispatcher_id);
            //$criteria->add(EtvaServicePeer::NAME_TMPL,$dispatcher);

            $etva_service = EtvaServicePeer::doSelectOne($criteria);
            $dispatcher = $etva_service->getNameTmpl();
            $etva_server = $etva_service->getEtvaServer();

            $tmpl = $etva_server->getAgentTmpl().'_'.$dispatcher.'_modules';
            $directory = $this->context->getConfiguration()->getTemplateDir('etms', '_'.$tmpl.'.php');

            //echo 'dispatcher_id ---> '.$tmpl;
            if($directory)
                return $this->renderPartial($tmpl);
            else
                return $this->renderText('Template '.$tmpl.' not found');
        }else{
            $this->etva_server = EtvaServerPeer::retrieveByPK($request->getParameter('sid'));
        }
  }

   /*
     * processes ETMS json requests and invokes dispatcher
     */
    public function executeJson(sfWebRequest $request)
    {
        $etva_service = EtvaServicePeer::retrieveByPK($request->getParameter('id'));
 
        if(!$etva_service){
            $msg = array('success'=>false,'error'=>'No service with specified id','info'=>'No service with specified id');
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
            $ret = call_user_func_array(array($this, $dispatcher_tmpl), array($etva_server,$method,$params,$mode));
//            return $this->renderText($ret);

            if($ret['success'])
                $result = json_encode($ret);
                //$result = json_encode(array(utf8_encode($ret)));
            else
                $result = $this->setJsonError($ret);
        }else{
            $info = array('success'=>false,'error'=>'No method implemented! '.$dispatcher_tmpl);
            $result = $this->setJsonError($info);
        }

            // $result = json_encode($ret);

        $this->getResponse()->setHttpHeader('Content-type', 'application/json; charset=utf-8');
        $this->getResponse()->setHttpHeader("X-JSON", '()');

        return $this->renderText($result);

    }


    /*
     * ETMS server dispatcher...
     */
    public function ETMS_server(EtvaServer $etva_server, $method, $params,$mode)
    {
        // prepare soap info....
//        $initial_params = array(
//                        'dispatcher'=>'hello'
//        );
//
//        $call_params = array_merge($initial_params,$params);

        // send soap request
        $response = $etva_server->soapSend($method,$params);

        // if soap response is ok
        if($response['success']){
            $response_decoded = (array) $response['response'];

            if($mode) $method = $mode;
            switch($method){
                case 'initialize':
                    $elements = array();

                    foreach($response_decoded as $dataType=>$data){
                        $elements[] = (array) $data;
                    }

                    $return = array('success'=>true,'value'=>$elements);

                    break;
                case 'remove_initLog':
                    $elements = array();

                    foreach($response_decoded as $dataType=>$data){
                        $elements[] = (array) $data;
                    }

                    $return = array('success'=>true,'value'=>$elements);

                    break;
                case 'server_info':
                    $elements = array();

                    foreach($response_decoded as $dataType=>$data){
                        $elements[] = (array) $data;
                    }

                    $return = array('success'=>true,'value'=>$elements);

                    break;
                case 'server_restart':
                    $elements = array();

                    foreach($response_decoded as $dataType=>$data){
                        $elements[] = (array) $data;
                    }

                    $return = array('success'=>true,'value'=>$elements);

                    break;
                case 'server_kill':
                    $elements = array();

                    foreach($response_decoded as $dataType=>$data){
                        $elements[] = (array) $data;
                    }

                    $return = array('success'=>true,'value'=>$elements);

                    break;

                case 'server_start':
                    $elements = array();

                    foreach($response_decoded as $dataType=>$data){
                        $elements[] = (array) $data;
                    }

                    $return = array('success'=>true,'value'=>$elements);

                    break;
                    
                case 'server_stop':
                    $elements = array();

                    foreach($response_decoded as $dataType=>$data){
                        $elements[] = (array) $data;
                    }

                    $return = array('success'=>true,'value'=>$elements);

                    break;
                    
                case 'server_backup':
                    $elements = array();

                    foreach($response_decoded as $dataType=>$data){
                        $elements[] = (array) $data;
                    }

                    $return = array('success'=>true,'value'=>$elements);

                    break;

                case 'server_restore':
                    $elements = array();

                    foreach($response_decoded as $dataType=>$data){
                        $elements[] = (array) $data;
                    }

                    $return = array('success'=>true,'value'=>$elements);

                    break;

                 case 'occupied_Space':
                    $elements = array();

                    foreach($response_decoded as $dataType=>$data){
                        $elements[] = (array) $data;
                    }

                    $return = array('success'=>true,'value'=>$elements);

                    break;
            }

            return $return;

        }else{
            $error_details = $response['info'];
            $error_details = nl2br($error_details);
            $error = $response['error'];

            $result = array('success'=>false,'error'=>$error,'info'=>$error_details,'faultcode'=>$response['faultcode']);
            return $result;
        }

    }


    /*
     * ETMS domain dispatcher...
     */
    public function ETMS_domain(EtvaServer $etva_server, $method, $params,$mode)
    {
        // prepare soap info....
//        $initial_params = array(
//                        'dispatcher'=>'hello'
//        );
//
//        $call_params = array_merge($initial_params,$params);

        // send soap request
        $response = $etva_server->soapSend($method,$params);

        // if soap response is ok
        if($response['success']){
            $response_decoded = (array) $response['response'];

            if($mode) $method = $mode;
            switch($method){
                case 'list_domains':
                    $elements = array();

                    foreach($response_decoded as $dataType=>$data){
                        $elements[] = (array) $data;
                    }

                    $return = array('success'=>true,'value'=>$elements);

                    break;
                case 'select_alias':
                    $elements = array();

                    foreach($response_decoded as $dataType=>$data){
                        $elements[] = array('alias'=>$data);
                    }

                    $return = array('success'=>true,'value'=>$elements);

                    break;
                case 'create_domain':
                    $elements = array();

                    foreach($response_decoded as $dataType=>$data){
                        $elements[] = array('alias'=>$data);
                    }

                    $return = array('success'=>true,'value'=>$elements);

                    break;

                case 'edit_domain':
                    $elements = array();

                    foreach($response_decoded as $dataType=>$data){
                        $elements[] = array('alias'=>$data);
                    }

                    $return = array('success'=>true,'value'=>$elements);

                    break;

                case 'change_alias':
                    $elements = array();

                    foreach($response_decoded as $dataType=>$data){
                        $elements[] = array('alias'=>$data);
                    }

                    $return = array('success'=>true,'value'=>$elements);

                    break;
                case 'delete_alias':
                    $elements = array();

                    foreach($response_decoded as $dataType=>$data){
                        $elements[] = array('alias'=>$data);
                    }

                    $return = array('success'=>true,'value'=>$elements);

                    break;
                case 'delete_domain':
                    $elements = array();

                    foreach($response_decoded as $dataType=>$data){
                        $elements[] = array('alias'=>$data);
                    }

                    $return = array('success'=>true,'value'=>$elements);

                    break;
                case 'domains_occupied_space':
                    $elements = array();

                    foreach($response_decoded as $dataType=>$data){
                        $elements[] = array('row'=>$data);
                    }

                    $return = array('success'=>true,'value'=>$elements);

                    break;
            }

            return $return;

        }else{
            $error_details = $response['info'];
            $error_details = nl2br($error_details);
            $error = $response['error'];

            $result = array('success'=>false,'error'=>$error,'info'=>$error_details,'faultcode'=>$response['faultcode']);
            return $result;
        }

    }

    /*
     * ETMS mailbox dispatcher...
     */
    public function ETMS_mailbox(EtvaServer $etva_server, $method, $params,$mode)
    {

        $response = $etva_server->soapSend($method,$params);

        // if soap response is ok
        if($response['success']){
            $response_decoded = (array) $response['response'];

            if($mode) $method = $mode;
            switch($method){
                case 'get_users':
                    $elements = array();

                    foreach($response_decoded as $dataType=>$data){
                        $elements[] = (array) $data;
                    }

                    $return = array('success'=>true,'value'=>$elements, 'agent'=>$response['agent']);

                    break;
                case 'select_alias':

                    $elements = array();

                    foreach($response_decoded as $dataType=>$data){
                        $elements[] = array('alias'=>$data);
                    }

                    $return = array('success'=>true,'value'=>$elements, 'agent'=>$response['agent']);

                    break;

                case 'edit_user':
                    $elements = array();

                    foreach($response_decoded as $dataType=>$data){
                        $elements[] = array('alias'=>$data);
                    }

                    $return = array('success'=>true,'value'=>$elements, 'agent'=>$response['agent']);
                    break;
                case 'create_user':
                    $elements = array();

                    foreach($response_decoded as $dataType=>$data){
                        $elements[] = array('alias'=>$data);
                    }

                    $return = array('success'=>true,'value'=>$elements, 'agent'=>$response['agent']);
                    break;
                case 'delete_user':
                    $elements = array();

                    foreach($response_decoded as $dataType=>$data){
                        $elements[] = array('alias'=>$data);
                    }

                    $return = array('success'=>true,'value'=>$elements, 'agent'=>$response['agent']);
                    break;
                case 'select_domain':
                    $elements = array();

                    foreach($response_decoded as $dataType=>$data){
                        $elements[$dataType] = $data;
                    }

                    $return = array('success'=>true,'value'=>$elements, 'agent'=>$response['agent']);
                    break;
                case 'users_occupied_space':
                    $elements = array();

                    foreach($response_decoded as $dataType=>$data){
                        $elements[$dataType] = $data;
                    }

                    $return = array('success'=>true,'value'=>$elements, 'agent'=>$response['agent']);
                    break;
                default:

                    $return = array('success'=>true, 'value'=>$response_decoded, 'agent'=>$response['agent']);

                    break;
            }

            return $return;

        }else{
            $error_details = $response['info'];
            $error_details = nl2br($error_details);
            $error = $response['error'];

            $result = array('success'=>false,'agent'=>$response['agent'],'error'=>$error,'info'=>$error_details,'faultcode'=>$response['faultcode']);
            return $result;
        }

    }

    protected function setJsonError($info,$statusCode = 400){

        if(isset($info['faultcode']) && $info['faultcode']=='TCP') $statusCode = 404;
        $this->getContext()->getResponse()->setStatusCode($statusCode);
        $error = json_encode($info);
        $this->getResponse()->setHttpHeader('Content-type', 'application/json');
        return $error;

    }
    
}
