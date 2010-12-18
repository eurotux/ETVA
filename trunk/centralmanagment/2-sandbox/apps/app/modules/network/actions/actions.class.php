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

    public function executeInterfacesWin(sfWebRequest $request)
    {
      $this->etva_server = EtvaServerPeer::retrieveByPk($request->getParameter('sid'));
      // remove session macs for cleanup the wizard
      $this->getUser()->getAttributeHolder()->remove('macs_in_wizard');
    }
   
    // add network interfaces to server
    // params: nid
    //         server
    //          json array networks ('port':i,'vlan':vlan,'mac':macaddr)
    public function executeJsonReplace(sfWebRequest $request)
    {

        $nid = $request->getParameter('nid');

        $server = $request->getParameter('server');

        $networks = json_decode($request->getParameter('networks'),true);


        if(!$etva_node = EtvaNodePeer::retrieveByPK($nid)){

                $error = array('success'=>false,'error'=>'No node exist');

                // if is a CLI soap request return json encoded data
                if(defined('SF_ENVIRONMENT') && SF_ENVIRONMENT  == 'soap') return json_encode($error);

                // if is browser request return text renderer
                $error = $this->setJsonError($error);
                return $this->renderText($error);

        }

        if(!$etva_server = $etva_node->retrieveServerByName($server)){

                $error = array('success'=>false,'error'=>$server.': Server doesnt exist');

                // if is a CLI soap request return json encoded data
                if(defined('SF_ENVIRONMENT') && SF_ENVIRONMENT  == 'soap') return json_encode($error);

                // if is browser request return text renderer
                $error = $this->setJsonError($error);
                return $this->renderText($error);

        }


        
        $method = 'detachall_interfaces';

        $params = array(
                        'name'=>$etva_server->getName()
                       );

        $response = $etva_node->soapSend($method,$params);

        if(!$response['success']){

            $error_decoded = $response['error'];
            $error = 'Deatching '.$server.' interfaces: '.$error_decoded;

            $result = array('success'=>false,'error'=>$error);

            if(defined('SF_ENVIRONMENT') && SF_ENVIRONMENT  == 'soap') return json_encode($result);

            $return = $this->setJsonError($result);
            return  $this->renderText($return);

        }


        $response_decoded = (array) $response['response'];
        $returned_status = $response_decoded['_okmsg_'];

        $etva_server->deleteNetworks();


        $method = 'attach_interface';

        $netSend = array();
        foreach($networks as $network){

            $netSend[] = "name=".$network['vlan'].",macaddr=".$network['mac'];

        }

        $network_string = implode(';',$netSend);

        $params = array(
                    'name'=>$etva_server->getName(),
                    'network'=>$network_string
                   );

        $response = $etva_node->soapSend($method,$params);

        if($response['success']){

            $response_decoded = (array) $response['response'];
            $returned_status = $response_decoded['_okmsg_'];

            foreach ($networks as $network){

                $etva_network = new EtvaNetwork();
                $etva_network->fromArray($network,BasePeer::TYPE_FIELDNAME);
                $etva_network->setEtvaServer($etva_server);
                $etva_network->save();

            }

            $msg = $server.' attached interfaces: '.$returned_status;

            $result = array('success'=>true,'response'=>$msg);

            $return = json_encode($result);

            // if the request is made throught soap request...
            if(defined('SF_ENVIRONMENT') && SF_ENVIRONMENT  == 'soap') return $return;
            // if is browser request return text renderer
            return  $this->renderText($return);


        }else
        // soap response error....
        // DB information not updated
        {

            $error_decoded = $response['error'];
            $error = 'Deatching '.$server.' interfaces: '.$error_decoded;

            $result = array('success'=>false,'error'=>$error);

            if(defined('SF_ENVIRONMENT') && SF_ENVIRONMENT  == 'soap') return json_encode($result);

            $return = $this->setJsonError($result);
            return  $this->renderText($return);

        }
           
    }


    public function executeJsonRemove(sfWebRequest $request)
    {


        $nid = $request->getParameter('nid');

        $server = $request->getParameter('server');

        $mac = $request->getParameter('macaddr');


        if(!$etva_node = EtvaNodePeer::retrieveByPK($nid)){

                $error = array('success'=>false,'error'=>'No node exist');

                // if is a CLI soap request return json encoded data
                if(defined('SF_ENVIRONMENT') && SF_ENVIRONMENT  == 'soap') return json_encode($error);

                // if is browser request return text renderer
                $error = $this->setJsonError($error);
                return $this->renderText($error);

        }

        if(!$etva_server = $etva_node->retrieveServerByName($server)){

                $error = array('success'=>false,'error'=>$server.': Server doesnt exist');

                // if is a CLI soap request return json encoded data
                if(defined('SF_ENVIRONMENT') && SF_ENVIRONMENT  == 'soap') return json_encode($error);

                // if is browser request return text renderer
                $error = $this->setJsonError($error);
                return $this->renderText($error);

        }        


        $method = 'detach_interface';        

        $params = array(
                        'name'=>$etva_server->getName(),
                        'macaddr'=>$mac
                       );

        $response = $etva_node->soapSend($method,$params);

        if($response['success']){

            $response_decoded = (array) $response['response'];
            $returned_status = $response_decoded['_okmsg_'];            

            $etva_server->deleteNetworkByMac($mac);

            $msg = $mac.': '.$returned_status;

            $result = array('success'=>true,'response'=>$msg);

            $return = json_encode($result);

            // if the request is made throught soap request...
            if(defined('SF_ENVIRONMENT') && SF_ENVIRONMENT  == 'soap') return $return;
            // if is browser request return text renderer
            return  $this->renderText($return);


        }else
            // soap response error....
            // DB information not updated
            {

            $error_decoded = $response['error'];
            $error = 'Interface '.$mac.': '.$error_decoded;

            $result = array('success'=>false,'error'=>$error);

            if(defined('SF_ENVIRONMENT') && SF_ENVIRONMENT  == 'soap') return json_encode($result);

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
        $etva_server = $etva_network->getEtvaServer();
        $etva_node = $etva_server->getEtvaNode();

        $graph_start = $request->getParameter('graph_start');
        $graph_end = $request->getParameter('graph_end');
        $step = $request->getParameter('step');

        $mac_strip = str_replace(':','',$etva_network->getMac());
        $network_rra = new ServerNetworkRRA($etva_node->getName(),$etva_server->getName(),$mac_strip);

        $this->getResponse()->setContentType('text/xml');
        $this->getResponse()->setHttpHeader('Content-Type', 'text/xml', TRUE);
        $this->getResponse()->sendHttpHeaders();
        $this->getResponse()->setContent($network_rra->xportData($graph_start,$graph_end,$step));
        return sfView::HEADER_ONLY;     
        
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
        $network_rra = new ServerNetworkRRA($etva_node->getName(),$etva_server->getName(),$mac_strip);
        $title = $etva_node->getName().'::'.$etva_server->getName().'-'.$etva_network->getTarget();
        $this->getResponse()->setContentType('image/png');
        $this->getResponse()->setHttpHeader('Content-Type', 'image/png', TRUE);
        $this->getResponse()->sendHttpHeaders();
        $this->getResponse()->setContent(print $network_rra->getGraphImg($title,$graph_start,$graph_end));
        return sfView::HEADER_ONLY;



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


        $query = ($this->getRequestParameter('query'))? json_decode($this->getRequestParameter('query'),true) : array();


        
        $c = new Criteria();        

        foreach($query as $key=>$val){

            $column = EtvaNetworkPeer::translateFieldName(sfInflector::camelize($key), BasePeer::TYPE_PHPNAME, BasePeer::TYPE_COLNAME);
            $c->add($column, $val);
        }
       

        $etva_network_list = EtvaNetworkPeer::doSelectJoinEtvaServer($c);


        $elements = array();
        $i=0;
        foreach($etva_network_list as $item){

            $etva_server = $item->getEtvaServer();            
            
            if($etva_server){
                $etva_server_name = $etva_server->getName();
                $etva_node = $etva_server->getEtvaNode();
                $etva_node_name = '';
                $etva_node_name = $etva_node->getName();

                $elements[$i] = $item->toArray();
                $elements[$i]['ServerName'] = $etva_server_name;
                $elements[$i]['NodeId'] = $etva_node->getId();
                $elements[$i]['NodeName'] = $etva_node_name;

                $i++;
            }

            
           
        }
        
        $final = array(
                  'total' =>   count($etva_network_list),
                  'data'  => $elements
        );

        $result = json_encode($final);

        $this->getResponse()->setHttpHeader("X-JSON", '()'); // set a header, (although it is empty, it is nicer than without a correct header. Filling the header with the result will not be parsed by extjs as far as I have seen).
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
        foreach($this->pager->getResults() as $item)
        $elements[] = $item->toArray();

        $final = array(
                      'total' =>   $this->pager->getNbResults(),
                      'data'  => $elements
        );

        $result = json_encode($final);

        $this->getResponse()->setHttpHeader("X-JSON", '()'); // set a header, (although it is empty, it is nicer than without a correct header. Filling the header with the result will not be parsed by extjs as far as I have seen).
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

        $this->getContext()->getResponse()->setStatusCode($statusCode);
        $error = json_encode($info);
        $this->getContext()->getResponse()->setHttpHeader("X-JSON", '()');
        return $error;

    }

}
