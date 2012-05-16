<?php

/**
 * node actions.
 *
 * @package    centralM
 * @subpackage node
 * @author     Ricardo Gomes
 * @version    SVN: $Id: actions.class.php 12474 2008-10-31 10:41:27Z
 */
/**
 * node actions controller
 * @package    centralM
 * @subpackage node
 *
 */
class nodeActions extends sfActions
{
    /*
     * set node agent interface data
     */
    public function executeJsonsetIP(sfWebRequest $request)
    {
        $id = $request->getParameter('id');

        if(!$etva_node = EtvaNodePeer::retrieveByPK($id))
        {

            $msg_i18n = $this->getContext()->getI18N()->__(EtvaNodePeer::_ERR_NOTFOUND_ID_,array('%id%'=>$id));

            $error = array('success'=>false,'agent'=>sfConfig::get('config_acronym'),'error'=>$msg_i18n,'info'=>$msg_i18n);

            //notify event log
            $node_log = Etva::getLogMessage(array('id'=>$id), EtvaNodePeer::_ERR_NOTFOUND_ID_);
            $message = Etva::getLogMessage(array('name'=>sfConfig::get('config_acronym'),'info'=>$node_log), EtvaNodePeer::_ERR_CHANGEIP_ );
            $this->dispatcher->notify(
                new sfEvent(sfConfig::get('config_acronym'), 'event.log',
                    array('message' => $message,'priority'=>EtvaEventLogger::ERR)));

            // if is browser request return text renderer
            $error = $this->setJsonError($error);
            return $this->renderText($error);

        }

        /*
         *
         * check ISO DIR in use
         *
         */
        $remote_errors = array();

        $isosdir = sfConfig::get("config_isos_dir");
        $criteria = new Criteria();
        $criteria->add(EtvaServerPeer::LOCATION, "%${isosdir}%",Criteria::LIKE);

        $criteria->add(EtvaServerPeer::VM_STATE,'running');
        $criteria->add(EtvaServerPeer::NODE_ID,$etva_node->getId());

        $servers_running_iso = EtvaServerPeer::doSelect($criteria);


        foreach($servers_running_iso as $server)
        {

            $remote_errors[] = $this->getContext()->getI18N()->__(EtvaServerPeer::_CDROM_INUSE_,array('%name%'=>$server->getName()));
        }

        if($remote_errors){

            $message = Etva::getLogMessage(array('info'=>ETVA::_CDROM_INUSE_), ETVA::_ERR_ISODIR_INUSE_);
            $this->dispatcher->notify(new sfEvent(sfConfig::get('config_acronym'), 'event.log',array('message' => $message,'priority'=>EtvaEventLogger::ERR)));

            $i18n_br_sep = implode('<br>',$remote_errors);
            $i18n_sp_sep = implode('. ',$remote_errors);
            
            $i18n_iso_br_msg = $this->getContext()->getI18N()->__(ETVA::_ERR_ISODIR_INUSE_,array('%info%'=>'<br>'.$i18n_br_sep));

            $i18n_iso_sp_msg = $this->getContext()->getI18N()->__(ETVA::_ERR_ISODIR_INUSE_,array('%info%'=>$i18n_sp_sep));

            $msg = array('success'=>false,'agent'=>sfConfig::get('config_acronym'),'info'=>$i18n_iso_br_msg,'error'=>$i18n_iso_sp_msg);
            $error = $this->setJsonError($msg);
            return $this->renderText($error);
        }

        $network = $request->getParameter('network');
        $network_decoded = json_decode($network,true);

        $node_va = new EtvaNode_VA($etva_node);
        $response = $node_va->send_change_ip($network_decoded);

        if($response['success'])
        {
            $return = json_encode($response);

            // if the request is made throught soap request...
            if(sfConfig::get('sf_environment') == 'soap') return $return;
            // if is browser request return text renderer
            $this->getResponse()->setHttpHeader('Content-type', 'application/json');
            return  $this->renderText($return);


        }else{

            if(sfConfig::get('sf_environment') == 'soap') return json_encode($response);

            $return = $this->setJsonError($response);
            return  $this->renderText($return);
        }

    }

    /*
     * get VA 'Management' interface data from node agent
     */
    public function executeJsongetIP(sfWebRequest $request)
    {
        $id = $request->getParameter('id');
        $dev_flag = 'va_management';        

        $interfaces_devices = sfConfig::get('app_device_interfaces');

        $etvamodel = $this->getUser()->getAttribute('etvamodel');
        $devices = $interfaces_devices[$etvamodel];        
        $device = $devices[$dev_flag];                  


        $method = 'get_va_ipconf';        
        $params = array('network'=>$device);        

        $etva_node = EtvaNodePeer::retrieveByPK($id);
        
        $response = $etva_node->soapSend($method,$params);
        
        $success = $response['success'];

        if(!$success){
            $msg_i18n = $this->getContext()->getI18N()->__(SystemNetwork::_ERR_NOTFOUND_INTF_,array('%info%'=>$response['info'],'%name%'=>$params['network']));

            $error = array('success'=>false,'agent'=>sfConfig::get('config_acronym'),'error'=>$msg_i18n,'info'=>$msg_i18n);
            $info = array('success'=>false,'agent'=>$response['agent'],'error'=>$msg_i18n,'info'=>$msg_i18n);
            $error = $this->setJsonError($info);
            return $this->renderText($error);                                

        }

        $response_decoded = (array) $response['response'];        

        $data = array();        
        
        $sys_network = new SystemNetwork();
        $sys_network->set(SystemNetwork::INTF, $params['network']);

        if($response_decoded[SystemNetwork::INTF]) $sys_network->set(SystemNetwork::INTF, $response_decoded[SystemNetwork::INTF]);
        if($response_decoded[SystemNetwork::IP]) $sys_network->set(SystemNetwork::IP, $response_decoded[SystemNetwork::IP]);
        if($response_decoded[SystemNetwork::NETMASK]) $sys_network->set(SystemNetwork::NETMASK, $response_decoded[SystemNetwork::NETMASK]);
        if($response_decoded[SystemNetwork::GW]) $sys_network->set(SystemNetwork::GW, $response_decoded[SystemNetwork::GW]);
        if($response_decoded[SystemNetwork::BOOTP]) $sys_network->set(SystemNetwork::BOOTP, $response_decoded[SystemNetwork::BOOTP]);
        if($response_decoded[SystemNetwork::DNS]) $sys_network->set(SystemNetwork::DNS, $response_decoded[SystemNetwork::DNS]);
        if($response_decoded[SystemNetwork::DNSSec]) $sys_network->set(SystemNetwork::DNSSec, $response_decoded[SystemNetwork::DNSSec]);
        
        $static = 0;        

        $ip = $sys_network->get(SystemNetwork::IP);
        $subnet = $sys_network->get(SystemNetwork::NETMASK);
        $gw = $sys_network->get(SystemNetwork::GW);
        $if = $sys_network->get(SystemNetwork::INTF);
        $dns = $sys_network->get(SystemNetwork::DNS);
        $seconddns = $sys_network->get(SystemNetwork::DNSSec);
        
        $bootp = $sys_network->get(SystemNetwork::BOOTP);        
        if($bootp=='none') $static = 1;

        $data['network_'.$dev_flag.'_static'] = $static;
        $data['network_'.$dev_flag.'_ip'] = $ip;
        $data['network_'.$dev_flag.'_netmask'] = $subnet;
        $data['network_'.$dev_flag.'_gateway'] = $gw;
        $data['network_'.$dev_flag.'_if'] = $if;
                      
        $data['network_primarydns'] = $dns;
        $data['network_secondarydns'] = $seconddns;
        $data['network_staticdns'] = $static;
        $data['node_id'] = $etva_node->getId();

        $msg =  array('success'=>true,'data'=>$data);

        return $this->renderText(json_encode($msg));
    }
    

    /*
     * export rra data in xml
     */
    public function executeXportLoadRRA(sfWebRequest $request)
    {
        $etva_node = EtvaNodePeer::retrieveByPK($request->getParameter('id'));

        $graph_start = $request->getParameter('graph_start');
        $graph_end = $request->getParameter('graph_end');
        $step = $request->getParameter('step');

        $nodeload_rra = new NodeLoadRRA($etva_node->getUuid());

        $this->getResponse()->setContentType('text/xml');
        $this->getResponse()->setHttpHeader('Content-Type', 'text/xml', TRUE);
        $this->getResponse()->sendHttpHeaders();
        $this->getResponse()->setContent($nodeload_rra->xportData($graph_start,$graph_end,$step));
        return sfView::HEADER_ONLY;

    }

    /*
     * returns cpu load png image from rrd data file
     */
    public function executeGraphPNG(sfWebRequest $request)
    {

        $etva_node = EtvaNodePeer::retrieveByPK($request->getParameter('id'));

        $graph_start = $request->getParameter('graph_start');
        $graph_end = $request->getParameter('graph_end');

        $nodeload_rra = new NodeLoadRRA($etva_node->getUuid());
        $title_i18n = sprintf("%s :: %s",$etva_node->getName(),$this->getContext()->getI18N()->__(NodeLoadRRA::getName()));
        
        $this->getResponse()->setContentType('image/png');
        $this->getResponse()->setHttpHeader('Content-Type', 'image/png', TRUE);
        $this->getResponse()->sendHttpHeaders();
        $this->getResponse()->setContent(print $nodeload_rra->getGraphImg($title_i18n,$graph_start,$graph_end));
        return sfView::HEADER_ONLY;

    }

    /**
     *
     * display cpu load graph images template for the server
     *
     * request object is like this;
     * <code>
     * $request['id'] = $id; //server ID
     * $request['graph_start'] = $time;
     * $request['graph_end'] = $time;
     * </code>
     *
     * @param sfWebRequest $request A request object
     *
     */
    public function executeGraph_nodeLoadImage(sfWebRequest $request)
    {

        $this->node = EtvaServerPeer::retrieveByPK($request->getParameter('id'));
        $this->graph_start = $request->getParameter('graph_start');
        $this->graph_end = $request->getParameter('graph_end');

        $response = $this->getResponse();
        $response->setTitle(sprintf("%s :: %s",$this->node->getName(),$this->getContext()->getI18N()->__(NodeLoadRRA::getName())));

        $this->setLayout('graph_image');
    }



  /*
   * Used when loading from left panel
   * Displays node panel related stuff (node info | servers | network | storage)
   * Uses view template
   * Params: id ( node id)
   */
    public function executeView(sfWebRequest $request)
    {
        //used to make soap requests to node VirtAgent
        $this->node_id = $request->getParameter('id');

        // used to get parent id component (extjs)
        $this->containerId = $request->getParameter('containerId');

        // used to build node grid dynamic
        $this->node_tableMap = EtvaNodePeer::getTableMap();

        //maybe deprecated
        //used to build form to create new server with default values
        $this->server_form = new EtvaServerForm();

        // used to build server grid dynamic
        $this->server_tableMap = EtvaServerPeer::getTableMap();

        $this->sfGuardGroup_tableMap = sfGuardGroupPeer::getTableMap();
        


    }

    public function executeViewUnassigned(sfWebRequest $request)
    {
    }
    

  /*
   * Triggered when clicking in 'storage tab'
   * Shows device info
   * Uses storage template
   */
    public function executeStorage(sfWebRequest $request)
    {
        $this->node = EtvaNodePeer::retrieveByPk($request->getParameter('id'));
        // used to get parent id component (extjs)
        $this->containerId = $request->getParameter('containerId');
    }

    /*
     * Internal method that verifies if node has any logical volume that belongs to another cluster...
     */
    protected function check_lvs(EtvaNode $etva_node){
        //error_log("Check if node has the same view of storage devices as the other nodes of the cluster");
        $node_response = $etva_node->soapSend('getlvs',array('force'=>1));

        //error_log("Node Response: ".print_r($node_response, true));

        if(!$node_response['success'])
        {
            $errors = $node_response['error'];
            Etva::getLogMessage(array('info'=>''), EtvaLogicalvolumePeer::_ERR_INCONSISTENT_);
            $etva_node->setErrorMessage(EtvaLogicalvolume_VA::LVINIT);

            $message = Etva::getLogMessage(array('info'=>$node_response['info']), EtvaLogicalvolumePeer::_ERR_SOAPUPDATE_);
            sfContext::getInstance()->getEventDispatcher()->notify(new sfEvent($etva_node->getName(),'event.log',array('message' =>$message,'priority'=>EtvaEventLogger::ERR)));

            $response = array('success'=>false, 'error'=>$errors);

            $return = $this->setJsonError($response);
            return  $this->renderText($return);
        }

        //verify if cluster has any logical volume that belongs to another cluster...
        $cluster_id = $etva_node->getClusterId();

        $my_lvs = $node_response['response'];
        $my_lvs = (array) $my_lvs;
        foreach($my_lvs as $my_lv){
            $lv_info = (array) $my_lv;
            if($lv_info['type'] == EtvaLogicalvolume::STORAGE_TYPE_LOCAL_MAP)
                continue;

            $c_uuid = new criteria();
            $c_uuid->add(EtvaLogicalvolumePeer::UUID, $lv_info['uuid']);
            $c_uuid->addAnd(EtvaLogicalvolumePeer::CLUSTER_ID, $cluster_id, Criteria::NOT_EQUAL);
            $e_lv = EtvaLogicalvolumePeer::doSelectOne($c_uuid);
            if($e_lv){
                $c_c = new Criteria();
                $c_c->add(EtvaClusterPeer::ID, $e_lv->getClusterId()); 
                $etva_cluster = EtvaClusterPeer::doSelectOne($c_c);
                error_log("Lv exists on cluster".$e_lv->getClusterId());
                $msg_i18n = $this->getContext()->getI18N()->__(EtvaLogicalvolumePeer::_ERR_INIT_OTHER_CLUSTER_, array('%name%'=>$etva_cluster->getName()));
                $response = array('success'=>false, 'agent'=>sfConfig::get('config_acronym'), 'error'=>$msg_i18n, 'info'=>$msg_i18n);
                $return = $this->setJsonError($response);
                return  $this->renderText($return);

            }
        }

    }

    /*
     * Internal method that verifies if node has any logical volume that belongs to another cluster...
     */
    private function check_vgs(EtvaNode $etva_node){
        //error_log("Check if node has the same view of storage devices as the other nodes of the cluster");
        $node_response = $etva_node->soapSend('getvgs',array('force'=>1));

        //error_log("Node Response: ".print_r($node_response, true));

        if(!$node_response['success'])
        {
            $errors = $node_response['error'];
            Etva::getLogMessage(array('info'=>''), EtvaVolumegroupPeer::_ERR_INCONSISTENT_);
            $etva_node->setErrorMessage(EtvaVolumegroup_VA::LVINIT);

            $message = Etva::getLogMessage(array('info'=>$node_response['info']), EtvaVolimegroupPeer::_ERR_SOAPUPDATE_);
            sfContext::getInstance()->getEventDispatcher()->notify(new sfEvent($etva_node->getName(),'event.log',array('message' =>$message,'priority'=>EtvaEventLogger::ERR)));

            $response = array('success'=>false, 'error'=>$errors);

            $return = $this->setJsonError($response);
            return  $this->renderText($return);
        }

        ////error_log("Communication succeeded");
        ////error_log("NEW REQUEST");

        //verify if cluster has any logical volume that belongs to another cluster...
        $cluster_id = $etva_node->getClusterId();

        $my_vgs = $node_response['response'];
        $my_vgs = (array) $my_vgs;
        error_log(print_r($my_vgs, true));

        foreach($my_vgs as $my_vg){
            $vg_info = (array) $my_vg;
            //ignore in case of a local device
            if($vg_info['type'] == EtvaVolumegroup::STORAGE_TYPE_LOCAL_MAP)
                continue;

            $c_uuid = new criteria();
            $c_uuid->add(EtvaVolumegroupPeer::UUID, $vg_info['uuid']);
            $c_uuid->addAnd(EtvaVolumegroupPeer::CLUSTER_ID, $cluster_id, Criteria::NOT_EQUAL);
            error_log("AKI");
            error_log($c_uuid->toString());
            $e_vg = EtvaVolumegroupPeer::doSelectOne($c_uuid);
            if($e_vg){
                error_log("ALI");
                error_log(print_r($e_vg, true));
                $c_c = new Criteria();
                $c_c->add(EtvaClusterPeer::ID, $e_vg->getClusterId());
                $etva_cluster = EtvaClusterPeer::doSelectOne($c_c);
                error_log("NODE[INFO] Volume group exists on cluster".$e_vg->getClusterId());

                $msg_i18n = $this->getContext()->getI18N()->__(EtvaVolumegroupPeer::_ERR_INIT_OTHER_CLUSTER_, array('%name%'=>$etva_cluster->getName()));
                
               // $res = "Volume group exists on cluster ".$etva_cluster->getName();
                $response = array('success'=>false, 'agent'=>sfConfig::get('config_acronym'), 'error'=>$msg_i18n, 'info'=>$msg_i18n);
                $return = $this->setJsonError($response);
                return  $this->renderText($return);
            }
        }

    }

    /*
     * Internal method that verifies if node has any logical volume that belongs to another cluster...
     */
    private function check_pvs(EtvaNode $etva_node){
        //error_log("Check if node has the same view of storage devices as the other nodes of the cluster");
        $node_response = $etva_node->soapSend('getpvs',array('force'=>1));

        if(!$node_response['success'])
        {
            $errors = $node_response['error'];
            Etva::getLogMessage(array('info'=>''), EtvaPhysicalvolumePeer::_ERR_INCONSISTENT_);
            //$etva_node->setErrorMessage(EtvaPhysicalvolume_VA::LVINIT);

            $message = $this->getContext()->getI18N()->__(EtvaPhysicalvolumePeer::_ERR_SOAPUPDATE_,array('%info%'=>$node_response['info']));
            sfContext::getInstance()->getEventDispatcher()->notify(new sfEvent($etva_node->getName(),'event.log',array('message' =>$message,'priority'=>EtvaEventLogger::ERR)));

            $response = array('success'=>false, 'error'=>$message, 'agent'=>$etva_node->getName());

            $return = $this->setJsonError($response);
            return  $this->renderText($return);
        }

        //verify if cluster has any logical volume that belongs to another cluster...
        $cluster_id = $etva_node->getClusterId();

        $my_pvs = $node_response['response'];
        $my_pvs = (array) $my_pvs;
        foreach($my_pvs as $my_pv){
            $pv_info = (array) $my_pv;
            if($pv_info['type'] == EtvaPhysicalvolume::STORAGE_TYPE_LOCAL_MAP)
                continue;
            $c_uuid = new criteria();
            $c_uuid->add(EtvaPhysicalvolumePeer::UUID, $pv_info['uuid']);
            $c_uuid->addAnd(EtvaPhysicalvolumePeer::CLUSTER_ID, $cluster_id, Criteria::NOT_EQUAL);
    
            $e_pv = EtvaPhysicalvolumePeer::doSelectOne($c_uuid);
            if($e_pv){
                $c_c = new Criteria();
                $c_c->add(EtvaClusterPeer::ID, $e_pv->getClusterId());
                $etva_cluster = EtvaClusterPeer::doSelectOne($c_c);
                error_log("NODE[INFO] Physical volume exists on cluster ".$e_pv->getClusterId());
                $msg_i18n = $this->getContext()->getI18N()->__(EtvaPhysicalvolumePeer::_ERR_INIT_OTHER_CLUSTER_, array('%name%'=>$etva_cluster->getName()));
                $response = array('success'=>false, 'agent'=>sfConfig::get('config_acronym'), 'error'=>$msg_i18n, 'info'=>$msg_i18n);
                $return = $this->setJsonError($response);
                return  $this->renderText($return);

            }
        }

    }

    public function executeJsonInit(sfWebRequest $request)
    {
        $id = $request->getParameter('id');
        $etva_node = EtvaNodePeer::retrieveByPK($id);

        $res = $this->check_pvs($etva_node);
        if($res)
            return $res;

        $res = $this->check_vgs($etva_node);
        if($res)
            return $res;

        $res = $this->check_lvs($etva_node);
        if($res)
            return $res;

        $node_response = $etva_node->soapSend('getlvs',array('force'=>1));

        if(!$node_response['success'])
        {
             $errors = $node_response['error'];
             Etva::getLogMessage(array('info'=>''), EtvaLogicalvolumePeer::_ERR_INCONSISTENT_);
             $etva_node->setErrorMessage(EtvaLogicalvolume_VA::LVINIT);
 
             $message = Etva::getLogMessage(array('info'=>$node_response['info']), EtvaLogicalvolumePeer::_ERR_SOAPUPDATE_);
             sfContext::getInstance()->getEventDispatcher()->notify(new sfEvent($etva_node->getName(),'event.log',array('message' =>$message,'priority'=>EtvaEventLogger::ERR)));
 
             $msg_i18n = $this->getContext()->getI18N()->__(EtvaLogicalvolumePeer::_ERR_SOAPUPDATE_,array('%info%'=>$node_response['info']));
             $response = array('success'=>false, 'error'=>$msg_i18n, 'agent'=>$etva_node->getName());
 
             $return = $this->setJsonError($response);
             return  $this->renderText($return);
         }

        $lv_va = new EtvaLogicalvolume_VA();
        $consistent = $lv_va->check_shared_consistency($etva_node,$node_response['response']);
        
        $etva_cluster = $etva_node->getEtvaCluster();

        $node_id = $etva_node->getId();

        $response_dtable = $etva_node->soapSend('device_table');
        if( $response_dtable['success'] ){
            $dtable = (array)$response_dtable['response'];
            $bulk_response_dtable = $etva_cluster->soapSend('device_table');
            $consistent_dtable = $lv_va->check_shared_devicetable_consistency($etva_node,$dtable,$bulk_response_dtable);
        }

        if(!$consistent || !$consistent_dtable){
            //error_log("not consistent");
            //error_log("etva_node_id : ".$etva_node->getId());
            $errors = $this->missing_lvs[$etva_node->getId()];
            //error_log(print_r($errors, true));
            $inconsistent_message = Etva::getLogMessage(array('info'=>''), EtvaLogicalvolumePeer::_ERR_INCONSISTENT_);

            $etva_node->setErrorMessage(EtvaLogicalvolume_VA::LVINIT);

            $message = Etva::getLogMessage(array('info'=>$inconsistent_message), EtvaLogicalvolumePeer::_ERR_SOAPUPDATE_);
            sfContext::getInstance()->getEventDispatcher()->notify(new sfEvent($etva_node->getName(),'event.log',array('message' =>$message,'priority'=>EtvaEventLogger::ERR)));

            $response = array('success'=>false,'error'=>$errors);
            $return = $this->setJsonError($response);
            return  $this->renderText($return);
        }

        //accept node
        $cmd = $request->getParameter('cmd');
        //error_log("Command: ".$cmd);
        if(!$etva_node)
        {

            $msg_i18n = $this->getContext()->getI18N()->__(EtvaNodePeer::_ERR_NOTFOUND_ID_,array('%id%'=>$id));

            $error = array('success'=>false,'agent'=>sfConfig::get('config_acronym'),'error'=>$msg_i18n,'info'=>$msg_i18n);

            //notify event log
            $node_log = Etva::getLogMessage(array('id'=>$id), EtvaNodePeer::_ERR_NOTFOUND_ID_);
            $message = Etva::getLogMessage(array('name'=>'','cmd'=>$cmd,'info'=>$node_log), EtvaNodePeer::_ERR_INITIALIZE_CMD_ );
            $this->dispatcher->notify(
                new sfEvent(sfConfig::get('config_acronym'), 'event.log',
                    array('message' => $message,'priority'=>EtvaEventLogger::ERR)));

            // if is browser request return text renderer
            $error = $this->setJsonError($error);
            return $this->renderText($error);

        }

        $node_va = new EtvaNode_VA($etva_node);
        $response = $node_va->send_initialize();

        if($response['success'])
        {
            $return = json_encode($response);

            // if the request is made throught soap request...
            if(sfConfig::get('sf_environment') == 'soap') return $return;
            // if is browser request return text renderer
            $this->getResponse()->setHttpHeader('Content-type', 'application/json');
            return  $this->renderText($return);

        }else{

            if(sfConfig::get('sf_environment') == 'soap') return json_encode($response);

            $return = $this->setJsonError($response);
            return  $this->renderText($return);
        }
        
        
    }


  /*
   * Used to create new node object
   * Note: NOT used
   */

    public function executeJsonCreate(sfWebRequest $request)
    {
        $isAjax = $request->isXmlHttpRequest();

        if(!$isAjax) return $this->redirect('@homepage');

        if(!$request->isMethod('post')){
            $info = array('success'=>false,'error'=>'Wrong parameters');
            $error = $this->setJsonError($info);
            return $this->renderText($error);
        }

        $this->form = new EtvaNodeForm();

        $result = $this->processJsonForm($request, $this->form);

        if(!$result['success']){
            $error = $this->setJsonError($result);
            return $this->renderText($error);
        }

        $result = json_encode($result);

        $this->getResponse()->setHttpHeader('Content-type', 'application/json');

        return $this->renderText($result);

    }

    /*
     * performs update state of all nodes
     * for each node calls checkstate
     */
    public function executeBulkUpdateState()
    {
        $node_list = EtvaNodePeer::doSelect(new Criteria());

        $results = array();
        if($node_list)
            foreach($node_list as $etva_node)
                $this->checkState($etva_node);

                

    }

    /*
     * check node state
     * returns json script call
     * uses checkState
     */
    public function executeJsonCheckState(sfWebRequest $request)
    {        

        $nid = $request->getParameter('id');
        if(!$etva_node = EtvaNodePeer::retrieveByPK($nid)){

            $msg_i18n = $this->getContext()->getI18N()->__(EtvaNodePeer::_ERR_NOTFOUND_ID_,array('%id%'=>$nid));

            $error = array('success'=>false,'agent'=>sfConfig::get('config_acronym'),'error'=>$msg_i18n,'info'=>$msg_i18n);

            // if is browser request return text renderer
            $error = $this->setJsonError($error);
            return $this->renderText($error);
        }


        $response = $this->checkState($etva_node);

        $success = $response['success'];

        if(!$success){
            $error = $this->setJsonError($response);
            return $this->renderText($error);

        }else{
            $this->getResponse()->setHttpHeader('Content-type', 'application/json');
            $return = json_encode($response);
            return  $this->renderText($return);

        }        

    }

    /*
     * checks node state
     * send soap request and update DB state
     * returns response from agent
     */
    public function checkState($etva_node){        

        $method = 'getstate';
        //soapsend('method','params',forceRequest)
        $response = $etva_node->soapSend($method,null,true);
                        
        $success = $response['success'];
        
        $return = array();
        if(!$success){

            $etva_node->setState(0);
            $etva_node->save();

            //notify system log
            $message = $this->getContext()->getI18N()->__(EtvaNodePeer::_ERR_SOAPSTATE_,array('%name%'=>$etva_node->getName(),'%info%'=>$response['info']));
            $this->dispatcher->notify(new sfEvent(sfConfig::get('config_acronym'), 'event.log', array('message' => $message,'priority'=>EtvaEventLogger::ERR)));

            $return = array('success'=>false,'agent'=>$etva_node->getName(),'error'=>$message,'info'=>$message);
        }else{            

            $etva_node->setState(1);
            $etva_node->save();

            //notify system log
            $message = $this->getContext()->getI18N()->__(EtvaNodePeer::_OK_SOAPSTATE_,array('%name%'=>$etva_node->getName()));
            $this->dispatcher->notify(new sfEvent(sfConfig::get('config_acronym'), 'event.log', array('message' => $message)));

            $return = array('success'=>true,'agent'=>$etva_node->getName(),'response'=>$message);
        }

        return $return;       

    }

  /*
   * Used to update a field node
   * Note: not in use
   */
    public function executeJsonUpdate(sfWebRequest $request)
    {
        $isAjax = $request->isXmlHttpRequest();

        if(!$isAjax) return $this->redirect('@homepage');

        if(!$request->isMethod('post') && !$request->isMethod('put')){
            $info = array('success'=>false,'error'=>'Wrong parameters');
            $error = $this->setJsonError($info);
            return $this->renderText($error);
        }

        if(!$etva_node = EtvaNodePeer::retrieveByPk($request->getParameter('id'))){
            $error_msg = sprintf('Object etva_node does not exist (%s).', $request->getParameter('id'));
            $info = array('success'=>false,'error'=>$error_msg);
            $error = $this->setJsonError($info);
            return $this->renderText($error);
        }

        $etva_node->setByName($request->getParameter('field'), $request->getParameter('value'));
        $etva_node->save();

        $result = array('success'=>true);
        $result = json_encode($result);
        $this->getResponse()->setHttpHeader('Content-type', 'application/json');
        return $this->renderText($result);

    }

  /*
   * Used to delete a field node   
   */
    public function executeJsonDelete(sfWebRequest $request)
    {
        $isAjax = $request->isXmlHttpRequest();

        if(!$isAjax) return $this->redirect('@homepage');

        if(!$etva_node = EtvaNodePeer::retrieveByPk($request->getParameter('id'))){
            $error_msg = sprintf('Object etva_node does not exist (%s).', $request->getParameter('id'));
            $info = array('success'=>false,'error'=>$error_msg);
            $error = $this->setJsonError($info);
            return $this->renderText($error);
        }

        $etva_node->delete();

        $result = array('success'=>true);
        $result = json_encode($result);
        $this->getResponse()->setHttpHeader('Content-type', 'application/json');
        return $this->renderText($result);
    }

    /**
     * Returns pre-formated data for Extjs grid with node information
     *
     * Request must be Ajax
     *
     * $request may contain the following keys:
     * - query: json array (field name => value)
     * @return array json array('total'=>num elems, 'data'=>array(network))
     */
    /*
     * Used to show node grid info
     * Returns: array(total=>1,data=>node info)
     */
    public function executeJsonGridInfo(sfWebRequest $request)
    {
        $isAjax = $request->isXmlHttpRequest();

        if(!$isAjax) return $this->redirect('@homepage');
        $elements = array();
        $this->etva_node = EtvaNodePeer::retrieveByPk($request->getParameter('id'));
        $elements[] = $this->etva_node->toArray();

        $final = array('total' =>   count($elements),'data'  => $elements);
        $result = json_encode($final);

        $this->getResponse()->setHttpHeader('Content-type', 'application/json');

        return $this->renderText($result);

    }


    public function executeJsonHostname(sfWebRequest $request)
    {
        $id = $request->getParameter('id');
        $method = $request->getParameter('method');
        $name = $request->getParameter('name');

        if($etva_node = EtvaNodePeer::retrieveByPK($id)) $count = 1;
        else{

            $msg_i18n = $this->getContext()->getI18N()->__(EtvaNodePeer::_ERR_NOTFOUND_ID_,array('%id%'=>$id));
            $error = array('success'=>false,'agent'=>sfConfig::get('config_acronym'),'error'=>$msg_i18n,'info'=>$msg_i18n);

            // if is browser request return text renderer
            $error = $this->setJsonError($error);
            return $this->renderText($error);

        }

        switch($method){
            case 'update':
                            
                            $node_va = new EtvaNode_VA($etva_node);
                            $response = $node_va->send_change_name($name);

                            if($response['success'])
                            {
                                $return = json_encode($response);

                                // if the request is made throught soap request...
                                if(sfConfig::get('sf_environment') == 'soap') return $return;
                                // if is browser request return text renderer
                                $this->getResponse()->setHttpHeader('Content-type', 'application/json');
                                return  $this->renderText($return);


                            }else{

                                if(sfConfig::get('sf_environment') == 'soap') return json_encode($response);

                                $return = $this->setJsonError($response);
                                return  $this->renderText($return);
                            }
                            
                            break;
                default  :
                            $return = array(
                                           'success' => true,
                                           'total' => $count,
                                           'data'  => array('id'=>$etva_node->getId(),'name'=>$etva_node->getName())

                            );

                            $result = json_encode($return);

                            $this->getResponse()->setHttpHeader('Content-type', 'application/json');

                            return $this->renderText($result);
        }
              


    }


    public function executeJsonLoad(sfWebRequest $request)
    {
        $count = 0;
        $id = $request->getParameter('id');
        
        if($etva_node = EtvaNodePeer::retrieveByPK($id)) $count = 1;
        else{

            $msg_i18n = $this->getContext()->getI18N()->__(EtvaNodePeer::_ERR_NOTFOUND_ID_,array('%id%'=>$id));
            $error = array('success'=>false,'agent'=>sfConfig::get('config_acronym'),'error'=>$msg_i18n,'info'=>$msg_i18n);

            // if is browser request return text renderer
            $error = $this->setJsonError($error);
            return $this->renderText($error);

        }        

        $node_array = $etva_node->toDisplay();        

        $return = array(
                       'success' => true,
                       'total' => $count,
                       'data'  => $node_array

        );

        $result = json_encode($return);

        $this->getResponse()->setHttpHeader('Content-type', 'application/json');

        return $this->renderText($result);
        

    }

    
      /*
       * Returns lists all nodes for grid with pager
       */
    public function executeJsonGrid($request)
    {
        $isAjax = $request->isXmlHttpRequest();

        if(!$isAjax) return $this->redirect('@homepage');

        $dc_id = $this->getRequestParameter('id');
        
        $limit = $this->getRequestParameter('limit', 10);
        $page = floor($this->getRequestParameter('start', 0) / $limit)+1;

        // pager
        $this->pager = new sfPropelPager('EtvaNode', $limit);
        $c = new Criteria();

        $this->addSortCriteria($c);

        if($dc_id){
            $cluster_criteria = new Criteria();
            $cluster_criteria->add(EtvaNodePeer::CLUSTER_ID, $dc_id, Criteria::EQUAL);
            $this->pager->setCriteria($cluster_criteria);
        }else{
            $this->pager->setCriteria($c);
        }

        $this->pager->setPage($page);

        $this->pager->setPeerMethod('doSelect');
        $this->pager->setPeerCountMethod('doCount');

        $this->pager->init();


        $elements = array();

        # Get data from Pager
        foreach($this->pager->getResults() as $item)
        $elements[] = $item->toDisplay();

        $final = array(
                    'total' =>   $this->pager->getNbResults(),
                    'data'  => $elements
        );

        $result = json_encode($final);

        $this->getResponse()->setHttpHeader('Content-type', 'application/json');
        return $this->renderText($result);

    }

    protected function addSortCriteria($criteria)
    {
        if ($this->getRequestParameter('sort')=='') return;

        $column = EtvaNodePeer::translateFieldName(sfInflector::camelize($this->getRequestParameter('sort')), BasePeer::TYPE_PHPNAME, BasePeer::TYPE_COLNAME);

        if ('asc' == strtolower($this->getRequestParameter('dir')))
        $criteria->addAscendingOrderByColumn($column);
        else
        $criteria->addDescendingOrderByColumn($column);
        $criteria->setIgnoreCase(true);
    }


/*
 *
 * SOAP Requests
 *
 */

    /*
     * invoked when VA restore ok
     */
    public function executeSoapRestore(sfWebRequest $request)
    {

       if(sfConfig::get('sf_environment') == 'soap'){
            $uuid = $request->getParameter('node_uuid');
            $ok = $request->getParameter('ok');

            /*
             * restore ok...
             */
            $node_va = new EtvaNode_VA();
            $response = $node_va->restore_ok($uuid,$ok);
            return $response;            
       }

    }

    /*
     * invoked when VA restore notok
     */
    public function executeSoapClear(sfWebRequest $request)
    {

       if(sfConfig::get('sf_environment') == 'soap'){
            $uuid = $request->getParameter('node_uuid');

            $error = $request->getParameter('error');            

            /*
             * clear...
             */
            $node_va = new EtvaNode_VA();
            
            $response = $node_va->clear($uuid,$error);                       
            return $response;

       }

    }


   public function executeSoapCreate(sfWebRequest $request)
   {

       if(sfConfig::get('sf_environment') == 'soap'){
            $params = $request->getParameter('etva_node');
           

            /*
             * initialize node
             */
            $node_va = new EtvaNode_VA();
            $response = $node_va->initialize($params);

            return $response;

       }

    }

    
    public function executeSoapUpdate(sfWebRequest $request)
    {
        $data = $request->getParameter('data');        
        $success = false;

        if(sfConfig::get('sf_environment') == 'soap'){

            $c = new Criteria();
            $c->add(EtvaNodePeer::UUID ,$request->getParameter('uuid'));


            if(!$etva_node = EtvaNodePeer::doSelectOne($c)){
                $error_msg = sprintf('Object etva_node does not exist (%s).', $request->getParameter('uuid'));
                $error = array('success'=>false,'error'=>$error_msg);

                //notify system log

                $node_message = Etva::getLogMessage(array('name'=>$request->getParameter('uuid')), EtvaNodePeer::_ERR_NOTFOUND_UUID_);
                $message = Etva::getLogMessage(array('name'=>'data','info'=>$node_message), EtvaNodePeer::_ERR_SOAPUPDATE_);
                $this->dispatcher->notify(
                    new sfEvent(sfConfig::get('config_acronym'),
                            'event.log',
                            array('message' =>$message,'priority'=>EtvaEventLogger::ERR)
                ));

                return $error;
            }

            /*
             * on node soapupdate check if field is state and update keepalive date
             */
            if(isset($data['state']))
                $etva_node->setLastKeepalive('NOW');            
            
            $etva_node->fromArray($data, BasePeer::TYPE_FIELDNAME);

            try{
                $etva_node->save();
                $priority = EtvaEventLogger::INFO;
                $success = true;
                $message = '';
                #$message = Etva::getLogMessage(array('name'=>$etva_node->getName()), EtvaNodePeer::_OK_SOAPUPDATE_);
            }
            catch(Exception $e){
                $priority = EtvaEventLogger::ERR;
                $message = Etva::getLogMessage(array('name'=>$etva_node->getName(),'info'=>'Could not save data'), EtvaNodePeer::_ERR_SOAPUPDATE_);

            }                                                               

            if($message != ''){
                //notify system log
                $this->dispatcher->notify(
                    new sfEvent($etva_node->getName(),'event.log',
                            array('message' =>$message, 'priority'=>$priority)
                ));
            }

            $result = array('success'=>$success,'response'=>$message);
            return $result;
        }

    }

    /*
     * gets nodes in same cluster
     * if sid not specified list all nodes
     * param sid: server id to filter cluster nodes
     */
    public function executeJsonListCluster(sfWebRequest $request)
    {
        $filterBy = $request->hasParameter('sid') ? 'sid' : '';

        $cluster_nodes = array();
        $criteria = new Criteria();

        if( $request->hasParameter('initialize') )  // filter by initialized nodes only
                $criteria->add(EtvaNodePeer::INITIALIZE,'ok',Criteria::EQUAL);
        
        switch($filterBy){
            case 'sid' :
                         $value = $request->getParameter($filterBy);
                         $server = EtvaServerPeer::retrieveByPK($value);
                         $node = $server->getEtvaNode();                         
                         $criteria->add(EtvaNodePeer::ID,$node->getId(),Criteria::NOT_EQUAL);
                         $cluster_nodes = $node->getNodesCluster($criteria);
                         break;
               default : 
                         $cluster_nodes = EtvaNodePeer::doSelect($criteria);
                         break;
        }
        

        $elements = array();
        foreach ($cluster_nodes as $node){
            $node_array = $node->toArray();
            // get max allocatable memory
            $node_array['maxmem'] = $node->getMaxmem();
            $elements[] = $node_array;
        }


        $result = array('success'=>true,
                    'total'=> count($elements),
                    'data'=> $elements
        );


        $return = json_encode($result);

        if(sfConfig::get('sf_environment') == 'soap') return $return;
        $this->getResponse()->setHttpHeader('Content-type', 'application/json');
        
        return $this->renderText($return);

    }

/*
 * list all nodes with servers
 */
    public function executeJsonNodeList(sfWebRequest $request)
    {

    

        $nodes = EtvaNodePeer::getWithServers();

        $elements = array();
        foreach ($nodes as $node){
            $node_array = $node->toArray();

         
            foreach ($node->getServers() as $i => $server){

                $server_array = $server->toArray();
                $node_array['servers'][] = $server_array;

            }

            $elements[] = $node_array;


        }

        $result = array('success'=>true,
                    'total'=> count($elements),
                    'response'=> $elements
        );


        $return = json_encode($result);

        if(sfConfig::get('sf_environment') == 'soap') return $return;

        
    }

/*
 *
 * END SOAP Requests
 *
 */

    protected function processJsonForm(sfWebRequest $request, sfForm $form)
    {
        // die(print_r($request));
        $form->bind($request->getParameter($form->getName()), $request->getFiles($form->getName()));
        $params = $request->getParameter($form->getName());
        if ($form->isValid())
        {
            $etva_node = $form->save();
            $uuid = $etva_node->getUuid();

            $result = array('success'=>true,'uuid'=>$uuid,'keepalive_update' => sfConfig::get('app_node_keepalive_update'));

            //notify system log
            $message = Etva::getLogMessage(
                array('name'=>$etva_node->getName(),
                      'uuid'=>$uuid,
                      'keepalive_update'=>$result['keepalive_update']), EtvaNodePeer::_OK_SOAPINIT_);
            $this->dispatcher->notify(
                new sfEvent(sfConfig::get('config_acronym'),
                        'event.log',
                        array('message' =>$message,'priority'=>EtvaEventLogger::INFO)
            ));
            return $result;

        }
        else{

            $errors = array();
            foreach ($form->getErrorSchema() as $field => $error)
            $errors[$field] = $error->getMessage();
            $result = array('success'=>false,'error'=>$errors);

            //notify system log
            $message = Etva::getLogMessage(
                array('name'=>$params['name'],
                      'uuid'=>$params['uuid']), EtvaNodePeer::_ERR_SOAPINIT_);
            $this->dispatcher->notify(
                new sfEvent(sfConfig::get('config_acronym'),
                        'event.log',
                        array('message' =>$message,'priority'=>EtvaEventLogger::ERR)
            ));

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
