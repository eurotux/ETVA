<?php

/**
 * network actions.
 *
 * @package    centralM
 * @subpackage network
 * @author     Ricardo Gomes
 * @version    SVN: $Id: actions.class.php 12474 2008-10-31 10:41:27Z
 */
/**
 * networks actions controller
 * @package    centralM
 * @subpackage network
 *
 */
class networkActions extends sfActions
{

    public function executeNetwork_ManageInterfacesGrid(sfWebRequest $request)
    {      
      // remove session macs for cleanup the wizard
      $this->getUser()->getAttributeHolder()->remove('macs_in_wizard');
    }
   
    // add network interfaces to server
    // params: sid
    //          json array networks ('port':i,'vlan':vlan,'mac':macaddr)
    public function executeJsonReplace(sfWebRequest $request)
    {

        $sid = $request->getParameter('sid');

        //convert server id in cluster id
        $dc_c = new Criteria();         
        $dc_c->addJoin(EtvaNodePeer::ID, EtvaServerPeer::NODE_ID);
        $dc_c->add(EtvaServerPeer::ID, $sid, Criteria::EQUAL);
        $node = EtvaNodePeer::doSelectOne($dc_c);
        $cluster_id = $node->getClusterId();

        $networks = json_decode($request->getParameter('networks'),true);
        

        if(!$etva_server = EtvaServerPeer::retrieveByPK($sid)){

                $msg_i18n = $this->getContext()->getI18N()->__(EtvaServerPeer::_ERR_NOTFOUND_ID_,array('%id%'=>$sid));
                $error = array('success'=>false,'agent'=>sfConfig::get('config_acronym'),'error'=>$msg_i18n,'info'=>$msg_i18n);

                //notify system log
                $server_log = Etva::getLogMessage(array('id'=>$sid), EtvaServerPeer::_ERR_NOTFOUND_ID_);
                $message = Etva::getLogMessage(array('info'=>$server_log), EtvaNetworkPeer::_ERR_REMOVEALL_);

                $this->dispatcher->notify(
                    new sfEvent(sfConfig::get('config_acronym'), 'event.log',
                        array('message' => $message,'priority'=>EtvaEventLogger::ERR)));

                // if is a CLI soap request return json encoded data
                if(sfConfig::get('sf_environment') == 'soap') return json_encode($error);

                // if is browser request return text renderer
                $error = $this->setJsonError($error);
                return $this->renderText($error);
        }
        
        $etva_node = $etva_server->getEtvaNode();
        
        $method = 'detachall_interfaces';

        $params = array(
                        'uuid'=>$etva_server->getUuid()
                       );

        $response = $etva_node->soapSend($method,$params);        

        if(!$response['success']){

            $error_decoded = $response['error'];
            //$error = 'Detaching '.$server.' interfaces: '.$error_decoded;

            $msg_i18n = $this->getContext()->getI18N()->__(EtvaNetworkPeer::_ERR_REMOVEALL_,array('%info%'=>$error_decoded));            

            $response['error'] = $msg_i18n;

            $result = $response;

            //notify system log            
            $message = Etva::getLogMessage(array('info'=>$response['info']), EtvaNetworkPeer::_ERR_REMOVEALL_);
            $this->dispatcher->notify(
                new sfEvent($response['agent'], 'event.log',
                    array('message' => $message,'priority'=>EtvaEventLogger::ERR)));                        

            if(sfConfig::get('sf_environment') == 'soap') return json_encode($result);
            
            $return = $this->setJsonError($result);
            return  $this->renderText($return);

        }


        $response_decoded = (array) $response['response'];
        $returned_status = $response_decoded['_okmsg_'];

        $etva_server->deleteNetworks();

        //notify system log
        $message = Etva::getLogMessage(array('server'=>$etva_server->getName()), EtvaNetworkPeer::_OK_REMOVEALL_);
        $this->dispatcher->notify(
            new sfEvent($response['agent'], 'event.log',
                array('message' => $message)));
        
        $method = 'attach_interface';

        $netSend = array();
        foreach($networks as $network){

            $netSend[] = "name=".$network['vlan'].",macaddr=".$network['mac'];

        }

        $network_string = implode(';',$netSend);

        $params = array(
                    'uuid'=>$etva_server->getUuid(),
                    'network'=>$network_string
                   );

        $response = $etva_node->soapSend($method,$params);

        if($response['success']){

            $response_decoded = (array) $response['response'];
            $returned_status = $response_decoded['_okmsg_'];

            foreach ($networks as $network){
                $c_dc_nm = new Criteria();
                $c_dc_nm->addAnd(EtvaVlanPeer::CLUSTER_ID, $cluster_id);  

                $etva_vlan = EtvaVlanPeer::retrieveByName($network['vlan'], $c_dc_nm);
                $etva_network = new EtvaNetwork();
                $etva_network->fromArray($network,BasePeer::TYPE_FIELDNAME);
                $etva_network->setEtvaServer($etva_server);
                $etva_network->setEtvaVlan($etva_vlan);
                $etva_network->save();

            }

            //$msg = $server.' attached interfaces: '.$returned_status;
            $msg_i18n = $this->getContext()->getI18N()->__(EtvaNetworkPeer::_OK_CREATEALL_,array('%server%'=>$etva_server->getName()));
            $response['response'] = $msg_i18n;

            $message = Etva::getLogMessage(array('server'=>$etva_server->getName()), EtvaNetworkPeer::_OK_CREATEALL_);
            $this->dispatcher->notify(
                new sfEvent($response['agent'], 'event.log',
                    array('message' => $message)));

            $result = $response;

            $return = json_encode($result);

            // if the request is made throught soap request...
            if(sfConfig::get('sf_environment') == 'soap') return $return;
            // if is browser request return text renderer
            $this->getResponse()->setHttpHeader('Content-type', 'application/json');
            return  $this->renderText($return);


        }else
        // soap response error....
        // DB information not updated
        {

            $error_decoded = $response['error'];
            $msg_i18n = $this->getContext()->getI18N()->__(EtvaNetworkPeer::_ERR_CREATEALL_,array('%info%'=>$error_decoded));

            //$error = 'Attaching '.$server.' interfaces: '.$error_decoded;
            $response['error'] = $msg_i18n;

            //notify system log
            $message = Etva::getLogMessage(array('info'=>$response['info']), EtvaNetworkPeer::_ERR_CREATEALL_);
            $this->dispatcher->notify(
                new sfEvent($response['agent'], 'event.log',
                    array('message' => $message,'priority'=>EtvaEventLogger::ERR)));


            if(sfConfig::get('sf_environment') == 'soap') return json_encode($response);

            $return = $this->setJsonError($response);
            return  $this->renderText($return);

        }
           
    }


    public function executeJsonRemove(sfWebRequest $request)
    {


        $sid = $request->getParameter('sid');

        $mac = $request->getParameter('macaddr');
        

        if(!$etva_server = EtvaServerPeer::retrieveByPK($sid)){
                $msg_i18n = $this->getContext()->getI18N()->__(EtvaServerPeer::_ERR_NOTFOUND_ID_,array('%id%'=>$sid));

                $error = array('success'=>false,'agent'=>sfConfig::get('config_acronym'),'error'=>$msg_i18n,'info'=>$msg_i18n);

                //notify system log
                $server_log = Etva::getLogMessage(array('id'=>$sid), EtvaServerPeer::_ERR_NOTFOUND_ID_);
                $message = Etva::getLogMessage(array('name'=>$mac,'info'=>$server_log), EtvaNetworkPeer::_ERR_REMOVE_);

                $this->dispatcher->notify(
                    new sfEvent(sfConfig::get('config_acronym'), 'event.log',
                        array('message' => $message,'priority'=>EtvaEventLogger::ERR)));

                // if is a CLI soap request return json encoded data
                if(sfConfig::get('sf_environment') == 'soap') return json_encode($error);

                // if is browser request return text renderer
                $error = $this->setJsonError($error);
                return $this->renderText($error);
        }
        
        $etva_node = $etva_server->getEtvaNode();
        $method = 'detach_interface';        

        $params = array(
                        'uuid'=>$etva_server->getUuid(),
                        'macaddr'=>$mac
                       );

        $response = $etva_node->soapSend($method,$params);

        if($response['success']){

            $response_decoded = (array) $response['response'];
            $returned_status = $response_decoded['_okmsg_'];            

            $etva_server->deleteNetworkByMac($mac);

            $msg_i18n = $this->getContext()->getI18N()->__(EtvaNetworkPeer::_OK_REMOVE_,array('%name%'=>$mac,'%server%'=>$etva_server->getName()));

            $result = $response;
            $result['response'] = $msg_i18n;

            //notify system log
            $message = Etva::getLogMessage(array('name'=>$mac,'server'=>$etva_server->getName()), EtvaNetworkPeer::_OK_REMOVE_);
            $this->dispatcher->notify(
                new sfEvent($response['agent'], 'event.log',
                    array('message' => $message)));

            $return = json_encode($result);

            // if the request is made throught soap request...
            if(sfConfig::get('sf_environment') == 'soap') return $return;
            // if is browser request return text renderer
            $this->getResponse()->setHttpHeader('Content-type', 'application/json');
            return  $this->renderText($return);


        }else
            // soap response error....
            // DB information not updated
            {

            $error_decoded = $response['error'];
            //$error = 'Interface '.$mac.': '.$error_decoded;
            $msg_i18n = $this->getContext()->getI18N()->__(EtvaNetworkPeer::_ERR_REMOVE_,array('%name%'=>$mac, '%info%'=>$error_decoded));
            $response['error'] = $msg_i18n;
            
            //notify system log
            $message = Etva::getLogMessage(array('name'=>$mac,'info'=>$response['info']), EtvaNetworkPeer::_ERR_REMOVE_);
            $this->dispatcher->notify(
                new sfEvent($response['agent'], 'event.log',
                    array('message' => $message,'priority'=>EtvaEventLogger::ERR)));

            $result = $response;

            if(sfConfig::get('sf_environment') == 'soap') return json_encode($result);

            $return = $this->setJsonError($result);
            return  $this->renderText($return);

        }
    }

    /*
     * export rra data in xml
     */
    public function executeXportRRA(sfWebRequest $request)
    {
        $etva_network = EtvaNetworkPeer::retrieveByPK($request->getParameter('id'));
        if(!$etva_network){
            $error = array('success'=>false,'agent'=>sfConfig::get('config_acronym'),'error'=>'Network not found');
            $error = $this->setJsonError($error);
            return $this->renderText($error);
        }
        $etva_server = $etva_network->getEtvaServer();
        $etva_node = $etva_server->getEtvaNode();

        $graph_start = $request->getParameter('graph_start');
        $graph_end = $request->getParameter('graph_end');
        $step = $request->getParameter('step');

        $mac_strip = str_replace(':','',$etva_network->getMac());

        try{
            $network_rra = new ServerNetworkRRA($etva_node->getUuid(),$etva_server->getUuid(),$mac_strip);
            $this->getResponse()->setContentType('text/xml');
            $this->getResponse()->setHttpHeader('Content-Type', 'text/xml', TRUE);
            $this->getResponse()->sendHttpHeaders();
            $this->getResponse()->setContent($network_rra->xportData($graph_start,$graph_end,$step));
            return sfView::HEADER_ONLY;

        }catch(sfException $e){
            $error = array('success'=>false,'error'=>$e->getMessage());
            $error = $this->setJsonError($error);
            return $this->renderText($error);
        }
        
             
        
    }
    
    /*
     * returns network interface png image from rrd data file
     */
    public function executeGraphPNG(sfWebRequest $request)
    {

        $etva_network = EtvaNetworkPeer::retrieveByPK($request->getParameter('id'));
        $etva_server = $etva_network->getEtvaServer();
        $etva_node = $etva_server->getEtvaNode();

        $graph_start = $request->getParameter('graph_start');
        $graph_end = $request->getParameter('graph_end');        

        $mac_strip = str_replace(':','',$etva_network->getMac());

        try{
            $network_rra = new ServerNetworkRRA($etva_node->getUuid(),$etva_server->getUuid(),$mac_strip);
            $title = sprintf("%s :: %s",$etva_server->getName(),$etva_network->getMac());
            $this->getResponse()->setContentType('image/png');
            $this->getResponse()->setHttpHeader('Content-Type', 'image/png', TRUE);
            $this->getResponse()->sendHttpHeaders();
            $this->getResponse()->setContent(print $network_rra->getGraphImg($title,$graph_start,$graph_end));
            return sfView::HEADER_ONLY;
        }catch(sfFileException $e){
            $error = array('success'=>false,'error'=>$e->getMessage());
            $error = $this->setJsonError($error);
            return $this->renderText($error);
        }       

    }
    





  /**
   * Inserts a server network in DB
   *
   * The request must be an Ajax and POST request
   *   
   * $request may contain the following keys:
   * - etva_network: array hash containing the data to be inserted
   * @return array json array(success=>true,insert_id=>id)
   */
    //uses processJsonForm
    //used in grid template

    

  /**
   * Deletes a network from DB
   *
   * The request must be an Ajax and POST request
   *
   * $request may contain the following keys:
   * - id: network ID
   * @return array json array(success=>true)
   */
  
    /**
     * Returns pre-formated data for Extjs grid with network information
     *
     * Request must be Ajax
     *
     * $request may contain the following keys:
     * - query: json array (field name => value)
     * @return array json array('total'=>num elems, 'data'=>array(network))
     */
    // used in createwin (NIC Management)
    // used in _interfacesGrid template
    public function executeJsonGridNoPager($request)
    {
        $isAjax = $request->isXmlHttpRequest();

        if(!$isAjax) return $this->redirect('@homepage');

        $sid = $this->getRequestParameter('sid');
        if($sid){
            $dc_c = new Criteria();             //convert server id in cluster id
            $dc_c->addJoin(EtvaNodePeer::ID, EtvaServerPeer::NODE_ID);
            $dc_c->add(EtvaServerPeer::ID, $sid, Criteria::EQUAL);
            $node = EtvaNodePeer::doSelectOne($dc_c);
            $cid = $node->getClusterId();
        }else{

            // get cluster id
            $cid = $this->getRequestParameter('cid');
            if(!$cid){
                $etva_cluster = EtvaClusterPeer::retrieveDefaultCluster();
                $cid = $etva_cluster->getId();
            }
        }
        
        //Get networks from cluster
        $c_vlan = new Criteria();
        $c_vlan->add(EtvaVlanPeer::CLUSTER_ID, $cid);
        $etva_vlans = EtvaVlanPeer::doSelect($c_vlan);

        //get networks with server
        $c = new Criteria();        
        $vlan_flag = false;
        $server_flag = false;

        $query = ($this->getRequestParameter('query'))? json_decode($this->getRequestParameter('query'),true) : array();
        foreach($query as $key=>$val){
            $column = EtvaNetworkPeer::translateFieldName(sfInflector::camelize($key), BasePeer::TYPE_PHPNAME, BasePeer::TYPE_COLNAME);
            if($key == 'vlan_id')
                $vlan_flag = true;            

            if($key == 'server_id')
                $server_flag = true;

            $c->add($column, $val);
        }  

        error_log($c->toString());

        //get vlans from cluster
        if(!$vlan_flag && !$server_flag){// && !$server_flag && $cid){
            error_log('!(!$vlan_flag && !$server_flag)');
            foreach($etva_vlans as $etva_vlan){
                if($this->getRequestParameter('query')){
                    $c->addOr(EtvaNetworkPeer::VLAN_ID, $etva_vlan->getId());
                }else{
                    $c->addAnd(EtvaNetworkPeer::VLAN_ID, $etva_vlan->getId());
                }
            }
        }

        // add sort criteria to sort elements
        $this->addSortCriteria($c);
        // add server criteria
        $this->addServerCriteria($c);

        error_log($c->toString());
        $etva_network_list = EtvaNetworkPeer::doSelectJoinEtvaServer($c);


        $elements = array();
        $i=0;
        foreach($etva_network_list as $item){

            $etva_server = $item->getEtvaServer();
            $etva_vlan = $item->getEtvaVlan();
            
            if($etva_server && $etva_vlan){
                $etva_server_name = $etva_server->getName();
                $etva_server_type = $etva_server->getVmType();
                $etva_vm_state = $etva_server->getVmState();
                $etva_vlan_name = $etva_vlan->getName();

                $elements[$i] = $item->toArray();
                $elements[$i]['ServerName'] = $etva_server_name;
                $elements[$i]['VmType'] = $etva_server_type;
                $elements[$i]['Vlan'] = $etva_vlan_name;
                $elements[$i]['Vm_state'] = $etva_vm_state;

                $etva_node = $etva_server->getEtvaNode();                
                if( $etva_node ){
                    $etva_node_name = $etva_node->getName();                
                    $elements[$i]['NodeName'] = $etva_node_name;
                    $elements[$i]['NodeId'] = $etva_node->getId();
                }

                $i++;
            }
                    
        }
        
        $final = array(
                  'total' =>   count($etva_network_list),
                  'data'  => $elements
        );

        $result = json_encode($final);

        $this->getResponse()->setHttpHeader('Content-type', 'application/json');
        return $this->renderText($result);

    }

    /**
     * Returns pre-formated data for Extjs grid with network information
     *
     * Request must be Ajax
     * Returns json info with pager
     *
     * $request may contain the following keys:
     * - limit: number of records to retrieve
     * - start: start at record number
     * - sort: field name to sort by (optional)
     * - dir: direction of sort field: ASC,DESC (optional)
     * - sid: server ID to filter (optional)
     * @return array json array('total'=>num elems, 'data'=>array(network))
     */
    // used in grid template to show networks information
    public function executeJsonGridPager($request)
    {
        $isAjax = $request->isXmlHttpRequest();

        if(!$isAjax) return $this->redirect('@homepage');

        $limit = $this->getRequestParameter('limit', 10);
        $page = floor($this->getRequestParameter('start', 0) / $limit)+1;

        // pager
        $this->pager = new sfPropelPager('EtvaNetwork', $limit);
        $c = new Criteria();

        // add sort criteria to sort elements
        $this->addSortCriteria($c);
        // add server criteria
        $this->addServerCriteria($c);

        $this->pager->setCriteria($c);
        $this->pager->setPage($page);

        $this->pager->setPeerMethod('doSelectJoinAll');
        $this->pager->setPeerCountMethod('doCountJoinAll');

        $this->pager->init();


        $elements = array();

        # Get data from Pager
        $i = 0;
        foreach($this->pager->getResults() as $item){
            $server = $item->getEtvaServer();
            $server_name = $server->getName();
            $elements[$i] = $item->toArray();
            $elements[$i]['ServerName'] = $server_name;
            $i++;
        }
        

        $final = array(
                      'total' =>   $this->pager->getNbResults(),
                      'data'  => $elements
        );

        $result = json_encode($final);

        $this->getResponse()->setHttpHeader('Content-type', 'application/json');
        return $this->renderText($result);

    }

    /**
     * Adds sort field to Criteria $criteria
     *
     * $request may contain the following keys:
     * - sort (field to sort)
     * - dir (ASC, DESC)
     */
    protected function addSortCriteria($criteria)
    {
        if ($this->getRequestParameter('sort')=='') return;

        $column = EtvaNetworkPeer::translateFieldName(sfInflector::camelize($this->getRequestParameter('sort')), BasePeer::TYPE_PHPNAME, BasePeer::TYPE_COLNAME);

        if ('asc' == strtolower($this->getRequestParameter('dir')))
        $criteria->addAscendingOrderByColumn($column);
        else
        $criteria->addDescendingOrderByColumn($column);
    }

    /**
     * Adds server ID to Criteria $criteria
     *
     * $request may contain the following keys:
     * - sid (server ID)
     *
     */
    protected function addServerCriteria($criteria)
    {
        if ($this->getRequestParameter('sid')=='') return;
        $serverID = $this->getRequestParameter("sid");
        $criteria->add(EtvaNetworkPeer::SERVER_ID, $serverID);
    }

    /**
     * Used to bind data from the request with the form values accepted
     * If validates returns array(success=>true,insert_id=>id)
     * else return array(success=>false,errors=>form schema erros)
     */
    // used by jsonCreate
    protected function processJsonForm(sfWebRequest $request, sfForm $form)
    {

        $form->bind($request->getParameter($form->getName()), $request->getFiles($form->getName()));

        if ($form->isValid())
        {
            $etva_network = $form->save();

            $result = array('success'=>true,'insert_id'=>$etva_network->getId());
            return $result;

        }
        else{
            $errors = array();
            foreach ($form->getErrorSchema() as $field => $error)
            $errors[$field] = $error->getMessage();
            $result = array('success'=>false,'error'=>$errors);
            return $result;
        }


    }


   /**
   * Used to return errors messages
   *
   * @param string $info error message
   * @param int $statusCode HTTP STATUS CODE
   * @return array json array
   */
    protected function setJsonError($info,$statusCode = 400){

        if(isset($info['faultcode']) && $info['faultcode']=='TCP') $statusCode = 404;
        $this->getContext()->getResponse()->setStatusCode($statusCode);
        $error = json_encode($info);
        $this->getResponse()->setHttpHeader('Content-type', 'application/json');
        return $error;

    }

}
