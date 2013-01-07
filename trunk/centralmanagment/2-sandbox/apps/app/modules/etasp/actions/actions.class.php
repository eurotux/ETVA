<?php

/**
 * etasp actions.
 *
 * @package    centralM
 * @subpackage etasp
 * @author     Your name here
 * @version    SVN: $Id: actions.class.php 23810 2009-11-12 11:07:44Z Kris.Wallsmith $
 */
class etaspActions extends sfActions
{
//   /**
//    * Executes index action
//    *
//    * @param sfRequest $request A request object
//    */
//    public function executeIndex(sfWebRequest $request)
//    {
//      $this->forward('default', 'module');
//    }

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
            erro_log(print_r($etva_service, true));
            $dispatcher = $etva_service->getNameTmpl();
            $etva_server = $etva_service->getEtvaServer();

            $tmpl = $etva_server->getAgentTmpl().'_'.$dispatcher.'_modules';
            $directory = $this->context->getConfiguration()->getTemplateDir('etasp', '_'.$tmpl.'.php');


            //echo 'dispatcher_id ---> '.$tmpl;
            if($directory)
                return $this->renderPartial($tmpl);
            else
                return $this->renderText('Template '.$tmpl.' not found');
        }else{
            $this->etva_server = EtvaServerPeer::retrieveByPK($request->getParameter('sid'));
        }
  }
       
   /////////////// ETASP AGENT STUFF ////////////////
   /*
     * processes ETASP json requests and invoke dispatcher
     */
    public function executeJson(sfWebRequest $request)
    {
        error_log("EXECUTE JSON CALLED");

        // we only have on service... so...
        if(!$request->getParameter('service')){
            $etva_service = EtvaServiceQuery::create()
                ->useEtvaServerQuery()
                    ->filterById($request->getParameter('id'))
                ->endUse()
                ->findOne();
        }else{
            $etva_service = EtvaServicePeer::retrieveByPK($request->getParameter('service'));
        }
 
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
     * ETASP api dispatcher...
     */
    public function ETASP_etasp_api(EtvaServer $etva_server, $method, $params,$mode)
    {
   
        error_log("ETASP_etasp_api called");
        if($method == 'allinfo'){
            $elements = new stdClass;
            $methods = array('getResourceUsage', 'getDatabaseInfo', 'getInstanceMetadata');
            error_log('allinfo'); 
            // make the calls
            foreach($methods as $method){
                error_log($method);    
                $response = $etva_server->soapSend($method,$params);
                if($response['success']){
                    $obj = $response['response'];
                    
                    $elements = (object) array_merge((array)$obj->msg, (array)$elements);
                }else{
                    #todo implement error handling
                    error_log("error");
                }
            }                    
                error_log(print_r($elements, true));

            $method = 'allinfo';
            $return = array('success'=>true,'data'=>$elements, 'total'=> 1);
            return $return;
        }else{
            // send soap request
            $response = $etva_server->soapSend($method,$params);
        }

        // if soap response is ok
        if($response['success'] && $method != 'allinfo'){
            $response_decoded = (array) $response['response'];

            if($mode) $method = $mode;
            switch($method){
                case 'pack':
                    $elements = array();

                    foreach($response_decoded as $dataType=>$data){
                        $elements[] = (array) $data;
                    }

                    $obj = new stdClass;
                    $obj->success = 'ok';
                    $obj->msg->pack = $elements[0];

                    $return = array('success'=>true,'data'=>$obj);

                    break;

                case 'getInstanceMetadata':
                    $elements = array();

                    foreach($response_decoded as $dataType=>$data){
                        $elements[] = array('alias'=>$data);
                    }

                    $return = array('success'=>true,'value'=>$elements);

                    break;

                case 'getDatabaseInfo':
                    $elements = array();

                    foreach($response_decoded as $dataType=>$data){
                        $elements[] = array('alias'=>$data);
                    }

                    $return = array('success'=>true,'value'=>$elements);

                    break;

                case 'getResourceUsage':
                    $elements = array();

                    foreach($response_decoded as $dataType=>$data){
                        $elements[] = array('alias'=>$data);
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

    protected function setJsonError($info,$statusCode = 400){

        if(isset($info['faultcode']) && $info['faultcode']=='TCP') $statusCode = 404;
        $this->getContext()->getResponse()->setStatusCode($statusCode);
        $error = json_encode($info);
        $this->getResponse()->setHttpHeader('Content-type', 'application/json');
        return $error;

    }
}
