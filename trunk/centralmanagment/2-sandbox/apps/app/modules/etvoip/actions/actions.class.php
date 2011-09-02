<?php

/**
 * ETVOIP actions.
 *
 * @package    centralM
 * @subpackage ETVOIP
 * @author     Your name here
 * @version    SVN: $Id: actions.class.php 12479 2008-10-31 10:54:40Z fabien $
 */
class etvoipActions extends sfActions
{
    public function executeView(sfWebRequest $request)
    {
        $dispatcher_id = $request->getParameter('dispatcher_id');        

        // load modules file of dispatcher
        if($dispatcher_id){

            $criteria = new Criteria();
            $criteria->add(EtvaServicePeer::ID,$dispatcher_id);            

            $etva_service = EtvaServicePeer::doSelectOne($criteria);
            $dispatcher = $etva_service->getNameTmpl();
            $etva_server = $etva_service->getEtvaServer();

            $tmpl = $etva_server->getAgentTmpl().'_'.$dispatcher.'_modules';
            $directory = $this->context->getConfiguration()->getTemplateDir('etvoip', '_'.$tmpl.'.php');

            if($directory)
                return $this->renderPartial($tmpl);
            else
                return $this->renderText('Template '.$tmpl.' not found');
        }else{
            $this->etva_server = EtvaServerPeer::retrieveByPK($request->getParameter('sid'));
        }

    }    


    /*
     * processes ETVOIP json requests and invokes dispatcher
     */
    public function executeJson(sfWebRequest $request)
    {
        $etva_service = EtvaServicePeer::retrieveByPK($request->getParameter('id'));

        if(!$etva_service){
            $msg = array('success'=>false,'error'=>'No service with specified id','info'=>'No service with specified id');
            $result = $this->setJsonError($msg);            
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

            if($ret['success'])
                $result = json_encode($ret);
                //$result = json_encode(array(utf8_encode($ret)));
            else
                $result = $this->setJsonError($ret);
        }else{
            $info = array('success'=>false,'error'=>'No method implemented! '.$dispatcher_tmpl);
            $result = $this->setJsonError($info);
        }            

        $this->getResponse()->setHttpHeader('Content-type', 'application/json');

        return $this->renderText($result);

    }


    public function executeJsonGetServer(sfWebRequest $request)
    {
        $etva_service = EtvaServicePeer::retrieveByPK($request->getParameter('id'));

        if(!$etva_service){
            $msg = array('success'=>false,'error'=>'No service with specified id','info'=>'No service with specified id');
            $result = $this->setJsonError($msg);
            return $this->renderText($result);
        }

        $etva_server = $etva_service->getEtvaServer();
        $server_array = $etva_server->toArray(BasePeer::TYPE_FIELDNAME);

        $result = array('success' => true, 'data'=>$server_array);        

        $this->getResponse()->setHttpHeader('Content-type', 'application/json');

        $return = json_encode($result);
        return $this->renderText($return);

    }



    /*
     * ETVOIP pbx dispatcher...
     */
    public function ETVOIP_pbx(EtvaServer $etva_server, $method, $params,$mode)
    {

        $action = $method;
        if($mode) $action = $mode;

        $pbx = new ETVOIP_Pbx($etva_server);        
        
        switch($action){
                case 'do_reload'      :
                                        $response = $pbx->do_reload();
                                        return $response;
                                        break;
                case 'add_extension'  :
                                        $response = $pbx->add_extension($params);
                                        return $response;
                                        break;
                case 'edit_extension' :
                                        $response = $pbx->edit_extension($params);
                                        return $response;
                                        break;
                case 'del_extension'  :
                                        $extension = '';
                                        if(isset($params['extension'])) $extension = $params['extension'];
                                        $response = $pbx->del_extension($extension);
                                        return $response;
                                        break;
                case 'get_extension'  :
                                        $extension = $tech = '';
                                        if(isset($params['extension'])){
                                            $extension = $params['extension'];
                                            $tech = $params['tech'];
                                        }
                                        
                                        $response = $pbx->get_extension($tech, $extension);

                                        if(!$response['success']) return $response;
                                        
                                        $elements = $response['response'];
                                        $items = array(
                                                    'success' =>true,                                                    
                                                    'data'  => $elements);

                                        return $items;
                                        break;
                case 'get_extensions' :
                                        $response = $pbx->get_extensions();

                                        if(!$response['success']) return $response;

                                        $elements = (array) $response['response'];

                                        $items = $elements;
                                        $items['success'] = true;
                                        $items['total'] = count($elements['data']);

                                        if($items['total'] == 0) $items['data'] = array();

                                        return $items;
                                        break;
                case 'add_trunk'      :
                                        $response = $pbx->add_trunk($params);
                                        return $response;
                                        break;
                case 'edit_trunk'     :
                                        $response = $pbx->edit_trunk($params);
                                        return $response;
                                        break;
                case 'del_trunk'      :
                                        $trunknum = '';
                                        if(isset($params['trunknum'])) $trunknum = $params['trunknum'];
                                        $response = $pbx->del_trunk($trunknum);
                                        return $response;
                                        break;                
                case 'get_trunk'      :
                                        $trunknum = '';
                                        if(isset($params['trunknum'])) $trunknum = $params['trunknum'];
                                        $response = $pbx->get_trunk($trunknum);

                                        if(!$response['success']) return $response;

                                        $elements = $response['response'];
                                        $items = array(
                                                    'success' =>true,
                                                    'data'  => $elements);

                                        return $items;
                                        break;
                case 'get_trunks'     :
                                        $response = $pbx->get_trunks();

                                        if(!$response['success']) return $response;                                        

                                        $elements = (array) $response['response'];

                                        $items = $elements;
                                        $items['success'] = true;
                                        $items['total'] = count($elements['data']);

                                        if($items['total'] == 0) $items['data'] = array();

                                        return $items;

                case 'get_outboundroute'      :
                                        $route = '';
                                        if(isset($params['routename'])) $route = $params['routename'];
                                        $response = $pbx->get_outboundroute($route);

                                        if(!$response['success']) return $response;

                                        $elements = (array) $response['response'];                                        
                                                                                
                                        $priorities = $elements['priorities'];
                                        $priorities_data = array(
                                                    'success' =>true,
                                                    'total' => count($priorities),
                                                    'data'  => $priorities);

                                        $moh = $elements['moh'];
                                        $moh_data = array(
                                                    'success' =>true,
                                                    'total' => count($moh),
                                                    'data'  => $moh);

                                        $elements['priorities'] = $priorities_data;
                                        $elements['moh'] = $moh_data;

                                        $items = array(
                                                    'success' =>true,                                                    
                                                    'total' => count($elements),
                                                    'data'  => $elements);

                                        return $items;
                                        break;
                case 'add_outboundroute' :
                                        
                                        $response = $pbx->add_outboundroute($params);
                                        return $response;
                                        break;
                case 'edit_outboundroute' :

                                        $response = $pbx->edit_outboundroute($params);
                                        return $response;
                                        break;
                case 'del_outboundroute' :
                                        $route = '';
                                        if(isset($params['routename'])) $route = $params['routename'];
                                        $response = $pbx->del_outboundroute($route);
                                        return $response;
                                        break;
                case 'get_outboundroutes' :
                                        $response = $pbx->get_outboundroutes();

                                        if(!$response['success']) return $response;

                                        $elements = (array) $response['response'];

                                        $items = $elements;
                                        $items['success'] = true;
                                        $items['total'] = count($elements['data']);

                                        if($items['total'] == 0) $items['data'] = array();

                                        return $items;
                                        
                                        break;
                case 'add_inboundroute' :
                                        $response = $pbx->add_inboundroute($params);
                                        return $response;
                                        break;
               case 'edit_inboundroute' :
                                        $response = $pbx->edit_inboundroute($params);
                                        return $response;
                                        break;
                case 'del_inboundroute' :
                                        $extdisplay = '';
                                        if(isset($params['extdisplay'])) $extdisplay = $params['extdisplay'];
                                        $response = $pbx->del_inboundroute($extdisplay);
                                        return $response;
                                        break;
                case 'get_inboundroute' :
                                        $extdisplay = '';
                                        if(isset($params['extdisplay'])) $extdisplay = $params['extdisplay'];
                                        $response = $pbx->get_inboundroute($extdisplay);

                                        if(!$response['success']) return $response;

                                        $elements = (array) $response['response'];                                        

                                        $dests = $elements['destinations'];
                                        $uniq_dest = array();
                                        foreach($dests as $dest=>$data){
                                            $data_cast = (array) $data;
                                            $data_array = array(
                                                    'success' =>true,
                                                    'total' => count($data),
                                                    'data'  => $data);

                                             $uniq_dest[$data_cast[0]->category] = $data_array;

                                        }

                                        $elements['destinations'] = $uniq_dest;


                                        $items = array(
                                                    'success' =>true,                                                    
                                                    'total' => count($elements),
                                                    'data'  => $elements);

                                        return $items;
                                        break;
                case 'get_inboundroutes' :
                                        $response = $pbx->get_inboundroutes();

                                        if(!$response['success']) return $response;

                                        $elements = (array) $response['response'];

                                        $items = $elements;
                                        $items['success'] = true;
                                        $items['total'] = count($elements['data']);

                                        if($items['total'] == 0) $items['data'] = array();

                                        return $items;

                                        break;
                             default :
                                        $return = array('success' => false,'error'=>'No action \''.$method.'\' defined yet',
                                            'info'=>'No action \''.$method.'\' implemented yet');
                                        return $return;
                                        break;
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
