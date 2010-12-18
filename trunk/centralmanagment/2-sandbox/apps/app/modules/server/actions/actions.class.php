<?php

/**
 * server actions.
 *
 * @package    centralM
 * @subpackage server
 * @author     Ricardo Gomes
 * @version    SVN: $Id: actions.class.php 12474 2008-10-31 10:41:27Z
 */
class serverActions extends sfActions
{

    /*
     * display graphs images in external webpage
     */

    /*
     * display networks graph images for the server
     */
    public function executeGraph_networkImage(sfWebRequest $request)
    {

        $etva_server = EtvaServerPeer::retrieveByPK($request->getParameter('id'));        
        $this->networks = $etva_server->getEtvaNetworks();
        $this->graph_start = $request->getParameter('graph_start');
        $this->graph_end = $request->getParameter('graph_end');

        $response = $this->getResponse();
        $response->setTitle($etva_server->getName().' :: Networks');

        $this->setLayout('graph_image');
        

    }

    /*
     * display cpu load graph images for the server
     */
    public function executeGraph_nodeLoadImage(sfWebRequest $request)
    {

        $etva_server = EtvaServerPeer::retrieveByPK($request->getParameter('id'));
        $this->node = $etva_server->getEtvaNode();
        $this->graph_start = $request->getParameter('graph_start');
        $this->graph_end = $request->getParameter('graph_end');

        $response = $this->getResponse();
        $response->setTitle($this->node->getName().' :: CPU Load');

        $this->setLayout('graph_image');


    }

    /*
     * display disks graph images for the server
     */
    public function executeGraph_diskImage(sfWebRequest $request)
    {

        $etva_server = EtvaServerPeer::retrieveByPK($request->getParameter('id'));
        $this->lv = $etva_server->getEtvaLogicalvolume();

        $this->graph_start = $request->getParameter('graph_start');
        $this->graph_end = $request->getParameter('graph_end');

        $response = $this->getResponse();
        $response->setTitle($etva_server->getName().' :: Disks');

        $this->setLayout('graph_image');


    }

    /*
     * display cpu percentage image webpage
     */
    public function executeGraph_cpu_perImage(sfWebRequest $request)
    {

        $this->server = EtvaServerPeer::retrieveByPK($request->getParameter('id'));
        $this->graph_start = $request->getParameter('graph_start');
        $this->graph_end = $request->getParameter('graph_end');

        $response = $this->getResponse();
        $response->setTitle($this->server->getName().' :: CPU %');

        $this->setLayout('graph_image');


    }

    /*
     * display mem percentage image webpage
     */
    public function executeGraph_memImage(sfWebRequest $request)
    {

        $this->server = EtvaServerPeer::retrieveByPK($request->getParameter('id'));
        $this->graph_start = $request->getParameter('graph_start');
        $this->graph_end = $request->getParameter('graph_end');

        $response = $this->getResponse();
        $response->setTitle($this->server->getName().' :: Memory');

        $this->setLayout('graph_image');


    }


    /*
     * returns cpu utilization png image from rrd data file
     */
    public function executeGraph_cpu_perPNG(sfWebRequest $request)
    {

        $etva_server = EtvaServerPeer::retrieveByPK($request->getParameter('id'));
        $etva_node = $etva_server->getEtvaNode();

        $graph_start = $request->getParameter('graph_start');
        $graph_end = $request->getParameter('graph_end');


        $cpu_per_rra = new ServerCpuUsageRRA($etva_node->getName(),$etva_server->getName());

        $title = $etva_node->getName().'::'.$etva_server->getName();
        $this->getResponse()->setContentType('image/png');
        $this->getResponse()->setHttpHeader('Content-Type', 'image/png', TRUE);
        $this->getResponse()->sendHttpHeaders();
        $this->getResponse()->setContent(print $cpu_per_rra->getGraphImg($title,$graph_start,$graph_end));
        return sfView::HEADER_ONLY;


    }


    /*
     * returns mem utilization png image from rrd data file
     */
    public function executeGraph_mem_perPNG(sfWebRequest $request)
    {

        $etva_server = EtvaServerPeer::retrieveByPK($request->getParameter('id'));
        $etva_node = $etva_server->getEtvaNode();

        $graph_start = $request->getParameter('graph_start');
        $graph_end = $request->getParameter('graph_end');


        $mem_rra = new ServerMemoryUsageRRA($etva_node->getName(),$etva_server->getName());

        $title = $etva_node->getName().'::'.$etva_server->getName();
        $this->getResponse()->setContentType('image/png');
        $this->getResponse()->setHttpHeader('Content-Type', 'image/png', TRUE);
        $this->getResponse()->sendHttpHeaders();
        $this->getResponse()->setContent(print $mem_rra->getGraphImg($title,$graph_start,$graph_end));
        return sfView::HEADER_ONLY;


    }

    public function executeGraph_mem_usagePNG(sfWebRequest $request)
    {

        $etva_server = EtvaServerPeer::retrieveByPK($request->getParameter('id'));
        $etva_node = $etva_server->getEtvaNode();

        $graph_start = $request->getParameter('graph_start');
        $graph_end = $request->getParameter('graph_end');


        $mem_rra = new ServerMemoryRRA($etva_node->getName(),$etva_server->getName());

        $title = $etva_node->getName().'::'.$etva_server->getName();
        $this->getResponse()->setContentType('image/png');
        $this->getResponse()->setHttpHeader('Content-Type', 'image/png', TRUE);
        $this->getResponse()->sendHttpHeaders();
        $this->getResponse()->setContent(print $mem_rra->getGraphImg($title,$graph_start,$graph_end));
        return sfView::HEADER_ONLY;


    }




    /*
     * RRA data xports (XML)
     */
    public function executeXportCpu_perRRA(sfWebRequest $request)
    {

        $etva_server = EtvaServerPeer::retrieveByPK($request->getParameter('id'));
        $etva_node = $etva_server->getEtvaNode();

        $graph_start = $request->getParameter('graph_start');
        $graph_end = $request->getParameter('graph_end');
        $step = $request->getParameter('step');

        $cpu_per_rra = new ServerCpuUsageRRA($etva_node->getName(),$etva_server->getName());
        
        $this->getResponse()->setContentType('text/xml');
        $this->getResponse()->setHttpHeader('Content-Type', 'text/xml', TRUE);
        $this->getResponse()->sendHttpHeaders();
        $this->getResponse()->setContent($cpu_per_rra->xportData($graph_start,$graph_end,$step));
        return sfView::HEADER_ONLY;
        

    }

    public function executeXportMem_perRRA(sfWebRequest $request)
    {

        $etva_server = EtvaServerPeer::retrieveByPK($request->getParameter('id'));
        $etva_node = $etva_server->getEtvaNode();

        $graph_start = $request->getParameter('graph_start');
        $graph_end = $request->getParameter('graph_end');
        $step = $request->getParameter('step');

        $mem_per_rra = new ServerMemoryUsageRRA($etva_node->getName(),$etva_server->getName());

        $this->getResponse()->setContentType('text/xml');
        $this->getResponse()->setHttpHeader('Content-Type', 'text/xml', TRUE);
        $this->getResponse()->sendHttpHeaders();
        $this->getResponse()->setContent($mem_per_rra->xportData($graph_start,$graph_end,$step));
        return sfView::HEADER_ONLY;


    }

    public function executeXportMem_usageRRA(sfWebRequest $request)
    {

        $etva_server = EtvaServerPeer::retrieveByPK($request->getParameter('id'));
        $etva_node = $etva_server->getEtvaNode();

        $graph_start = $request->getParameter('graph_start');
        $graph_end = $request->getParameter('graph_end');
        $step = $request->getParameter('step');

        $mem_rra = new ServerMemoryRRA($etva_node->getName(),$etva_server->getName());

        $this->getResponse()->setContentType('text/xml');
        $this->getResponse()->setHttpHeader('Content-Type', 'text/xml', TRUE);
        $this->getResponse()->sendHttpHeaders();
        $this->getResponse()->setContent($mem_rra->xportData($graph_start,$graph_end,$step));
        return sfView::HEADER_ONLY;


    }

    
   


    

    
    

    // called by 'Add Server Wizard' button
    public function executeCreatewin(sfWebRequest $request)
    {
        $this->etva_node = EtvaNodePeer::retrieveByPk($request->getParameter('id'));
        // remove session macs for cleanup the wizard
        $this->getUser()->getAttributeHolder()->remove('macs_in_wizard');
    }

    // main view    
    public function executeView(sfWebRequest $request)
    {
        $etva_server = EtvaServerPeer::retrieveByPk($request->getParameter('id'));
        $this->forward404Unless($etva_server);

        // used to get parent id component (extjs)
        $this->containerId = $request->getParameter('containerId');
        

        // used
        $this->node_id = $etva_server->getNodeId();
        $this->server_id = $etva_server->getId();
        $this->server_tableMap = EtvaServerPeer::getTableMap();

        $this->sfGuardGroup_tableMap = sfGuardGroupPeer::getTableMap();

        $this->network_tableMap = EtvaNetworkPeer::getTableMap();

        $this->agent_form = new EtvaAgentForm();
        $this->agent_tableMap = EtvaAgentPeer::getTableMap();


        



        // rra statistics
        // networks stuff in _stats                
        $this->networks = $etva_server->getEtvaNetworks();
        // disk stuff
        $this->lv = $etva_server->getEtvaLogicalvolume();

    }


   /*
    * create virtual machine
    * sends soap request and stores info
    */
    public function executeJsonCreate(sfWebRequest $request)
    {
        $nid = $request->getParameter('nid');
        $server = json_decode($request->getParameter('server'),true);


        if(!$etva_node = EtvaNodePeer::retrieveByPK($nid)){

            $error = array('success'=>false,'error'=>'No virtagent node found');

            // if is a CLI soap request return json encoded data
            if(defined('SF_ENVIRONMENT') && SF_ENVIRONMENT  == 'soap') return json_encode($error);

            // if is browser request return text renderer
            $error = $this->setJsonError($error);
            return $this->renderText($error);

        }

        $lv = $server['lv'];

        if(!$etva_lv = $etva_node->retrieveLogicalvolumeByLv($lv)){

            $error = array('success'=>false,'error'=>'No logical volume found');

            // if is a CLI soap request return json encoded data
            if(defined('SF_ENVIRONMENT') && SF_ENVIRONMENT  == 'soap') return json_encode($error);

            // if is browser request return text renderer
            $error = $this->setJsonError($error);
            return $this->renderText($error);

        }

        //check if lv already marked as 'in use'
        if($etva_lv->getInUse()){

            $error = array('success'=>false,'error'=>'Logical volume marked as in use');

            // if is a CLI soap request return json encoded data
            if(defined('SF_ENVIRONMENT') && SF_ENVIRONMENT  == 'soap') return json_encode($error);

            // if is browser request return text renderer
            $error = $this->setJsonError($error);
            return $this->renderText($error);

        }

        $networks = $server['networks'];

        // check if networks are available
        foreach ($networks as $network){

            $etva_vlan = EtvaVlanPeer::retrieveByName($network['vlan']);
            $etva_mac = EtvaMacPeer::retrieveByMac($network['mac']);


            if(!$etva_mac || !$etva_vlan){

                $error = array('success'=>false,'error'=>'Networks problem');

                // if is a CLI soap request return json encoded data
                if(defined('SF_ENVIRONMENT') && SF_ENVIRONMENT  == 'soap') return json_encode($error);

                // if is browser request return text renderer
                $error = $this->setJsonError($error);
                return $this->renderText($error);
            
            }

            if($etva_mac->getInUse()){

                $error = array('success'=>false,'error'=>'Mac address \''.$etva_mac->getMac().'\' already assigned');

                // if is a CLI soap request return json encoded data
                if(defined('SF_ENVIRONMENT') && SF_ENVIRONMENT  == 'soap') return json_encode($error);

                // if is browser request return text renderer
                $error = $this->setJsonError($error);
                return $this->renderText($error);
       
            }
        }




        $method = 'create_vm';        

        $netSend = array();
        foreach($networks as $network){

            $netSend[] = "name=".$network['vlan'].",macaddr=".$network['mac'];

        }

        $network_string = implode(';',$netSend);
        $server['uid'] = EtvaServerPeer::generateUUID();

        $params = array(
                    'uuid'=>$server['uid'],
                    'name'=>$server['name'],
                    'path'=>$etva_lv->getLvdevice(),
                    'ram'=>$server['mem'],
                    'cpuset'=>$server['cpuset'],
                    'network'=>$network_string,
                    'nettype'=>$server['nettype'],
                    'location'=>$server['location'],
                    'vnc_listen'=>'any'
        );

        $response = $etva_node->soapSend($method,$params);


        if(!$response['success']){

            $error_decoded = $response['error'];
            $error = $etva_node->getName().' - '.$server['name'].': '.$error_decoded;

            $result = array('success'=>false,'error'=>$error);

            if(defined('SF_ENVIRONMENT') && SF_ENVIRONMENT  == 'soap') return json_encode($result);

            $return = $this->setJsonError($result);
            return  $this->renderText($return);

        }

        $response_decoded = (array) $response['response'];
        $returned_status = $response_decoded['_okmsg_'];
        $returned_object = (array) $response_decoded['_obj_'];



        // get some info from response...        
        $server['vnc_port'] = $returned_object['vnc_port'];
        $server['state'] = $returned_object['state'];


        // update logical volume target


       $ret_disks = (array) $returned_object['Disks'];
                     
       foreach ($ret_disks as $disktag=>$disk_info){
            $disk = (array) $disk_info;
            if($disk['path']==$etva_lv->getLvdevice()){
                $etva_lv->setTarget($disk['target']);
                break;
            }           

       }
       //foreach ($ret_disks as $disktag=>$disk_info){
         ///   $disk = (array) $disk_info;
           // if($disk['path']==)
       //}


       // get position in response array and update target...
//       $ret_disk_data = (array) $ret_physicalvolumes[$etva_phy->getName()];
//
//                $pvsize_new = $ret_pv_data['size'];
//                $pvfree_new = $ret_pv_data['freesize'];
//
//                $etva_phy->setPvsize($pvsize_new);
//                $etva_phy->setPvfreesize($pvfree_new);
//
//
//            }
//
//
//
//
//        $server['target'] = $returned_object['target'];



        $etva_server = new EtvaServer();

        $etva_server->fromArray($server,BasePeer::TYPE_FIELDNAME);

        $etva_server->setEtvaNode($etva_node);
        $etva_server->setEtvaLogicalvolume($etva_lv);

        $user_groups = $this->getUser()->getGroups();

        $server_sfgroup = array_shift($user_groups);

        $etva_server->setsfGuardGroup($server_sfgroup);

        $etva_lv->setInUse(1);


        foreach ($networks as $network){

            $etva_network = new EtvaNetwork();
            $etva_network->fromArray($network,BasePeer::TYPE_FIELDNAME);
            $etva_network->setEtvaServer($etva_server);
            if(!$etva_network->save()){
                $result = array('success'=>false,'error'=>'Could not add networks');
                return json_encode($result);
            }
            

        }

        $msg = $etva_node->getName().' - '.$server['name'].': '.$returned_status;        

        // if the request is made throught soap request...
        if(defined('SF_ENVIRONMENT') && SF_ENVIRONMENT  == 'soap'){
            $result = array('success'=>true,'response'=>$msg);
            $return = json_encode($result);
            return $return;
        }

        $result = array('success'=>true,'response'=>array('insert_id'=>$etva_server->getId(),'msg'=>$msg));

        $return = json_encode($result);

        // if is browser request return text renderer
        return  $this->renderText($return);
              
    }

    // removes server
    // args: server ID
    // returns json message
    public function executeJsonRemove(sfWebRequest $request)
    {

        $nid = $request->getParameter('nid');
        $server = $request->getParameter('server');


        if(!$etva_node = EtvaNodePeer::retrieveByPK($nid)){

            $error = array('success'=>false,'error'=>'No virtagent node found');

            // if is a CLI soap request return json encoded data
            if(defined('SF_ENVIRONMENT') && SF_ENVIRONMENT  == 'soap') return json_encode($error);

            // if is browser request return text renderer
            $error = $this->setJsonError($error);
            return $this->renderText($error);

        }

        if(!$etva_server = $etva_node->retrieveServerByName($server)){

            $error = array('success'=>false,'error'=>$server.': Server not found');

            // if is a CLI soap request return json encoded data
            if(defined('SF_ENVIRONMENT') && SF_ENVIRONMENT  == 'soap') return json_encode($error);

            // if is browser request return text renderer
            $error = $this->setJsonError($error);
            return $this->renderText($error);

        }

        $method = 'vmDestroy';
        $params = array('name'=>$etva_server->getName());
        $response = $etva_node->soapSend($method,$params);

        if(!$response['success']){

            $error_decoded = $response['error'];
            $error = $etva_node->getName().' - '.$server.': '.$error_decoded;

            $result = array('success'=>false,'error'=>$error);

            if(defined('SF_ENVIRONMENT') && SF_ENVIRONMENT  == 'soap') return json_encode($result);

            $return = $this->setJsonError($result);
            return  $this->renderText($return);

        }

        $response_decoded = (array) $response['response'];
        $returned_status = $response_decoded['_okmsg_'];

        $etva_server->delete();


        $msg = $etva_node->getName().' - '.$server.': '.$returned_status;

        $result = array('success'=>true,'response'=>$msg);

        $return = json_encode($result);

        // if the request is made throught soap request...
        if(defined('SF_ENVIRONMENT') && SF_ENVIRONMENT  == 'soap') return $return;
        // if is browser request return text renderer
        return  $this->renderText($return);


    }


    

    
    /*
     * starts virtual machine
     */
    public function executeJsonStart(sfWebRequest $request)
    {
        $method = 'start_vm';
        $virtAgentID = $request->getParameter('nid');
        $server = $request->getParameter('server');
        return $this->processStartStop($virtAgentID, $server, $method);
    }

    public function executeJsonStop(sfWebRequest $request)
    {
        $method = 'vmStop';
        $virtAgentID = $request->getParameter('nid');
        $server = $request->getParameter('server');
        return $this->processStartStop($virtAgentID, $server, $method);
    }

    protected function processStartStop($nid, $server, $method)
    {

        if(!$etva_node = EtvaNodePeer::retrieveByPK($nid)){

            $error = array('success'=>false,'error'=>'No virtagent node found');

            // if is a CLI soap request return json encoded data
            if(defined('SF_ENVIRONMENT') && SF_ENVIRONMENT  == 'soap') return json_encode($error);

            // if is browser request return text renderer
            $error = $this->setJsonError($error);
            return $this->renderText($error);

        }

        if(!$etva_server = $etva_node->retrieveServerByName($server)){

            $error = array('success'=>false,'error'=>$server.': Server not found');

            // if is a CLI soap request return json encoded data
            if(defined('SF_ENVIRONMENT') && SF_ENVIRONMENT  == 'soap') return json_encode($error);

            // if is browser request return text renderer
            $error = $this->setJsonError($error);
            return $this->renderText($error);

        }
        
        $params = array('name'=>$etva_server->getName());
        $response = $etva_node->soapSend($method,$params);

        if(!$response['success']){

            $error_decoded = $response['error'];
            $error = $etva_node->getName().' - '.$server.': '.$error_decoded;

            $result = array('success'=>false,'error'=>$error);

            if(defined('SF_ENVIRONMENT') && SF_ENVIRONMENT  == 'soap') return json_encode($result);

            $return = $this->setJsonError($result);
            return  $this->renderText($return);

        }

        $response_decoded = (array) $response['response'];
        $returned_status = $response_decoded['_okmsg_'];
        $returned_object = (array) $response_decoded['_obj_'];

        // get some info from response...
        $vnc_port = $returned_object['vnc_port'];
        $state = $returned_object['state'];

        $etva_server->setVncPort($vnc_port);
        $etva_server->setState($state);
        $etva_server->save();

        $msg = $etva_node->getName().' - '.$server.': '.$returned_status;

        $result = array('success'=>true,'response'=>$msg);

        $return = json_encode($result);

        // if the request is made throught soap request...
        if(defined('SF_ENVIRONMENT') && SF_ENVIRONMENT  == 'soap') return $return;
        // if is browser request return text renderer
        return  $this->renderText($return);
        
    }



    public function executeJsonUpdateField(sfWebRequest $request)
    {
        $isAjax = $request->isXmlHttpRequest();

        if(!$isAjax) return $this->redirect('@homepage');

        if(!$request->isMethod('post') && !$request->isMethod('put')){
            $info = array('success'=>false,'error'=>'Wrong parameters');
            $error = $this->setJsonError($info);
            return $this->renderText($error);
        }

        if(!$etva_server = EtvaServerPeer::retrieveByPk($request->getParameter('id'))){
            $error_msg = sprintf('Object etva_server does not exist (%s).', $request->getParameter('id'));
            $info = array('success'=>false,'error'=>$error_msg);
            $error = $this->setJsonError($info);
            return $this->renderText($error);
        }

        $etva_server->setByName($request->getParameter('field'), $request->getParameter('value'));
        $etva_server->save();

        $result = array('success'=>true);
        $result = json_encode($result);
        $this->getResponse()->setHttpHeader("X-JSON", '()'); // set a header, (although it is empty, it is nicer than without a correct header. Filling the header with the result will not be parsed by extjs as far as I have seen).
        return $this->renderText($result);

    }

   

    public function executeJsonUpdate(sfWebRequest $request)
    {
        $isAjax = $request->isXmlHttpRequest();

        if(!$isAjax) return $this->redirect('@homepage');

        $server = ($request->getParameter('server'))? json_decode($request->getParameter('server'),true) : array();

        $etva_server = new EtvaServer();
        $etva_server->fromArray($server);
        $etva_server->setNew(false);

        if($etva_server->validate()){
            $etva_server->save();
            $result = array('success'=>true,'object'=>$etva_server->toArray());

        }else $result = array('success'=>false);        


        $result = json_encode($result);
        $this->getResponse()->setHttpHeader("X-JSON", '()'); // set a header, (although it is empty, it is nicer than without a correct header. Filling the header with the result will not be parsed by extjs as far as I have seen).
        return $this->renderText($result);

    }

   

    /*
     * returns server json for extjs grid store
     */
    public function executeJsonGridInfo(sfWebRequest $request)
    {
        $isAjax = $request->isXmlHttpRequest();

        if(!$isAjax) return $this->redirect('@homepage');
        $elements = array();

        $criteria = new Criteria();
        $criteria->add(EtvaServerPeer::ID, $request->getParameter('id'));

        // $this->etva_server = EtvaServerPeer::doSelectJoinsfGuardGroup($criteria);
        $this->etva_server = EtvaServerPeer::retrieveByPK($request->getParameter('id'));

        $serverGroup = $this->etva_server->getsfGuardGroup();
        $serverGroupName = $serverGroup->getName();

        $lv = $this->etva_server->getEtvaLogicalvolume();
        $lvName = $lv->getLv();

        $elements[0] = $this->etva_server->toArray();
        $elements[0]['SfGuardGroupName'] = $serverGroupName;
        $elements[0]['LogicalvolumeId'] = $lvName;

        $final = array('total' =>   count($elements),'data'  => $elements);
        $result = json_encode($final);

        $this->getResponse()->setHttpHeader("X-JSON", '()'); // set a header, (although it is empty, it is nicer than without a correct header. Filling the header with the result will not be parsed by extjs as far as I have seen).

        return $this->renderText($result);

    }

    /*
     * returns all servers json for extjs grid store with pager
     */
    public function executeJsonGrid(sfWebRequest $request)
    {
        $isAjax = $request->isXmlHttpRequest();

        if(!$isAjax) return $this->redirect('@homepage');

        $limit = $this->getRequestParameter('limit', 10);
        $page = floor($this->getRequestParameter('start', 0) / $limit)+1;

        // pager
        $this->pager = new sfPropelPager('EtvaServer', $limit);
        $c = new Criteria();

        $this->addSortCriteria($c);
        $this->addNodeCriteria($c);

        $this->pager->setCriteria($c);
        $this->pager->setPage($page);

        $this->pager->setPeerMethod('doSelectJoinsfGuardGroup');
        $this->pager->setPeerCountMethod('doCountJoinsfGuardGroup');

        $this->pager->init();


        $elements = array();
        $i = 0;

        # Get data from Pager
        foreach($this->pager->getResults() as $item){
            $serverGroup = $item->getsfGuardGroup();
            $serverGroupName = $serverGroup->getName();

            $lv = $item->getEtvaLogicalvolume();
            $lvName = $lv->getLv();

            $elements[$i] = $item->toArray();
            $elements[$i]['SfGuardGroupName'] = $serverGroupName;
            $elements[$i]['LogicalvolumeId'] = $lvName;
            $i++;
        }


        $final = array(
      'total' =>   $this->pager->getNbResults(),
      'data'  => $elements
        );

        $result = json_encode($final);

        $this->getResponse()->setHttpHeader("X-JSON", '()'); // set a header, (although it is empty, it is nicer than without a correct header. Filling the header with the result will not be parsed by extjs as far as I have seen).
        return $this->renderText($result);

    }

    protected function addSortCriteria($criteria)
    {
        if ($this->getRequestParameter('sort')=='') return;

        $column = EtvaServerPeer::translateFieldName(sfInflector::camelize($this->getRequestParameter('sort')), BasePeer::TYPE_PHPNAME, BasePeer::TYPE_COLNAME);

        if ('asc' == strtolower($this->getRequestParameter('dir')))
        $criteria->addAscendingOrderByColumn($column);
        else
        $criteria->addDescendingOrderByColumn($column);
    }

    protected function addNodeCriteria($criteria)
    {

        $nodeID = $this->getRequestParameter("nid");
        $criteria->add(EtvaServerPeer::NODE_ID, $nodeID);
    }


    protected function processJsonForm(sfWebRequest $request, sfForm $form)
    {
        // get submitted form elements
        $form_elems = $request->getParameter($form->getName());
        // retrieve mac pool elements number
        // $mac_pool = $request->getParameter('mac_pool');

        //generate random macs and add to form elements
        //    if(isset($mac_pool)){
        //        $macs = $this->generateMacPool($mac_pool);
        //        $macs = implode(',',$macs);
        //
        //        $form_elems['mac_addresses'] = $macs;
        //    }

        $form->bind($form_elems, $request->getFiles($form->getName()));

        if ($form->isValid())
        {
            $etva_server = $form->save();

            //$result = array('success'=>true,'insert_id'=>$etva_server->getId());
            $result = array('success'=>true, 'object'=>$etva_server->toArray());
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

    protected function setJsonError($info,$statusCode = 400){

        if(isset($info['faultcode']) && $info['faultcode']=='TCP') $statusCode = 404;
        $this->getContext()->getResponse()->setStatusCode($statusCode);
        $error = json_encode($info);
        $this->getContext()->getResponse()->setHttpHeader("X-JSON", '()');
        return $error;

    }


    public function executeJsonTree(sfWebRequest $request)
    {
        $isAjax = $request->isXmlHttpRequest();

        if(!$isAjax) return $this->redirect('@homepage');

        $tree = $this->generateTree();
        $json = json_encode($tree);
        $this->getResponse()->setHttpHeader('Content-type', 'application/json');
        return $this->renderText ($json);

    }

    protected function generateTree()
    {
        $nodes = EtvaNodePeer::getWithServers();

        $aux = array();
        foreach ($nodes as $node){

            $state = $node->getState();
            if(!$state) $cls = 'no-active';
            else $cls = 'active';

            $aux_servers = array();
            foreach ($node->getServers() as $i => $server){
                $child_id = 's'.$server->getID();
                $aux_servers[] = array('text'=>$server->getName(),'id'=>$child_id,'cls'=>$cls,'url'=> $this->getController()->genUrl('server/view?id='.$server->getID()),
                            'leaf'=>true);
            }



            if(empty($aux_servers)){
                $aux[] = array('text'=>$node->getName(),'id'=>$node->getID(),'url'=>$this->getController()->genUrl('node/view?id='.$node->getID()),
                'children'=>$aux_servers,'expanded'=>true,'cls'=> 'x-tree-node-collapsed '.$cls);
            }else $aux[] = array('text'=>$node->getName(),'id'=>$node->getID(),'cls'=>$cls,'url'=>$this->getController()->genUrl('node/view?id='.$node->getID()),
                        'singleClickExpand'=>true,'children'=>$aux_servers);

        }
        return $aux;

    }


    /*
     * check server state
     * returns json script call
     * uses checkState
     */
    public function executeJsonCheckState(sfWebRequest $request){

        $etva_server = EtvaServerPeer::retrieveByPk($request->getParameter('id'));
        $dispatcher = $request->getParameter('dispatcher');
        $response = $this->checkState($etva_server,$dispatcher);

        $success = $response['success'];

        if(!$success){
            $error = $this->setJsonError($response);
            return $this->renderText($error);            

        }else{
           
            $return = json_encode($response);            
            return  $this->renderText($return);

        }



    }

    /*
     * checks server state
     * send soap request and update DB state
     * returns response from agent
     */
    private function checkState($etva_server,$dispatcher){

        $method = 'getstate';
        $response = $etva_server->soapSend($method,$dispatcher);

//        $success = $response['success'];

//        if(!$success){
//
//            $etva_node->setState(0);
//            $etva_node->save();
//
//        }else{
//
//            $etva_node->setState(1);
//            $etva_node->save();
//
//        }

        return $response;

    }


    

  /*
   * used in soapupdate
   */


    public function executeUpdate(EtvaNode $node, $vms)
    {

        // if(SF_ENVIRONMENT == 'soap'){

        // $vlans = $request->getParameter('vlans');
        // $vms = $request->getParameter('vms');

        $vms_uids = array();



        $vms = !empty($vms) ? $vms : array();

        foreach($vms as $vm){
            $vms_uids[] = $vm->uuid;
        }

        // die(print_r($vms_uids));

        $node_servers = $node->getEtvaServers();



        // return $node_vlans;
        // $result = 'Success';
        $new_servers =array();
        foreach($node_servers as $node_server){


         //   if(!in_array($node_server->getUid(),$vms_uids)){


                $server_uuid = $node_server->getUid();

                $server_name = $node_server->getName();

                $etva_lv = $node_server->getEtvaLogicalvolume();
                $server_path = $etva_lv->getLvdevice();

                $server_ram = $node_server->getMem();
                $server_cpuset = $node_server->getCpuset();
                $server_state = $node_server->getState();
                $server_vnc_port = $node_server->getVncPort();

                $server_networks = $node_server->getEtvaNetworks();



                $server_location = $node_server->getLocation();
                $networks_string = array();

                foreach($server_networks as $server_network){
                    $networks_string[] = 'name='.$server_network->getVlan().','.
                                   'macaddr='.$server_network->getMac();
                }

                $network_string = implode(';',$networks_string);




                $new_servers[$server_name] = array(
                                    'uuid'=>$server_uuid,
                                    'name'=>$server_name,
                                    'path'=>$server_path,
                                    'ram'=>$server_ram,
                                    'cpuset'=>$server_cpuset,
                                    'network'=>$network_string,
                                    'nettype'=>'network',
                                    'state'=>$server_state,
                                    'vnc_port'=>$server_vnc_port,
                                    'location'=>$server_location,
                                     'vnc_listen'=>'any'
                );



          //  }



        }


        return $new_servers;

    }

    public function executeSoapUpdate(sfWebRequest $request)
    {


        if(SF_ENVIRONMENT == 'soap'){


            $vms = $request->getParameter('vms');

            $c = new Criteria();
            $c->add(EtvaNodePeer::UID ,$request->getParameter('uid'));


            if(!$etva_node = EtvaNodePeer::doSelectOne($c)){
                $error_msg = sprintf('Object etva_node does not exist (%s).', $request->getParameter('uid'));
                $error = array('success'=>false,'error'=>$error_msg);

                return $error;
            }


            return $this->executeUpdate($etva_node, $vms);

           
        }
    }    


}
