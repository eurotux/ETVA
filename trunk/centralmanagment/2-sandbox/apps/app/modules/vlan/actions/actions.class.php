<?php

/**
 * vlan actions.
 *
 * @package    centralM
 * @subpackage vlan
 * @author     Ricardo Gomes
 * @version    SVN: $Id: actions.class.php 12474 2008-10-31 10:41:27Z
 */
/**
 * vlan actions controller
 * @package    centralM
 * @subpackage vlan
 *
 */
class vlanActions extends sfActions
{
    // in this case we want only to show create vlan form
    // no action to be performed
    public function executeVlan_CreateForm(sfWebRequest $request)
    {
        $vlan_form = new EtvaVlanForm();
        $vlanid_val = $vlan_form->getValidator('vlanid');
        $this->min_vlanid = $vlanid_val->getOption('min');
        $this->max_vlanid = $vlanid_val->getOption('max');

        $vlanname_val = $vlan_form->getValidator('name');
        $this->min_vlanname = $vlanname_val->getOption('min_length');
        $this->max_vlanname = $vlanname_val->getOption('max_length');
    }


    /*
     * load data for create form (loads has_untagged check)
     */
    public function executeJsonLoadForm(sfWebRequest $request)
    {
        $untagged_vlan = EtvaVlanPeer::retrieveUntagged();
        $untagged = 0;
        if($untagged_vlan) $untagged = $untagged_vlan->getId();
        
        $result = array('success'=>true,'data' => array('vlan_untagged'=>$untagged));

        $return = json_encode($result);

        $this->getResponse()->setHttpHeader('Content-type', 'application/json');
        return $this->renderText($return);

    }
    
    /**
     * Creates vlan
     *
     * Issues soap request to all nodes
     *
     * @return json array string
     * 
     */
    public function executeJsonCreate(sfWebRequest $request)
    {
        $form_data = array();

        $netname = $request->getParameter('name');
        if($netname) $form_data['name'] = $netname;

        $re_inv_name = EtvaVlanPeer::_REGEXP_INVALID_NAME_;
        if(preg_match($re_inv_name,$netname)){

            $msg = EtvaVlanPeer::_ERR_NAME_;
            $msg_i18n = $this->getContext()->getI18N()->__($msg);
            $error_msg = array('success' => false,
                                  'agent' => sfConfig::get('config_acronym'),
                                  'error' => $msg_i18n);
            
            //notify system log
            $this->dispatcher->notify(new sfEvent($error['agent'], 'event.log', array('message' => Etva::getLogMessage(array('name'=>$netname,'info'=>$msg), EtvaVlanPeer::_ERR_CREATE_),'priority'=>EtvaEventLogger::ERR)));
            $error = array('success'=>false,'agent'=>sfConfig::get('config_acronym'),'info'=>$msg_i18n,'error'=>array($error_msg));

            // if is a CLI soap request return json encoded data
            if(sfConfig::get('sf_environment') == 'soap') return json_encode($error);

            // if is browser request return text renderer
            $error = $this->setJsonError($error);
            return $this->renderText($error);
            
        }

        $clusterid = $request->getParameter('cluster_id');


        if(!$clusterid){
            $defaultCluster = EtvaClusterPeer::retrieveDefaultCluster();
            $clusterid = $defaultCluster->getId();
        }
        
        $form_data['cluster_id'] = $clusterid;

        $netid = $request->getParameter('vlanid');
        if($netid) $form_data['vlanid'] = $netid;        


        $nodes_criteria = new Criteria();
        $nodes_criteria->add(EtvaNodePeer::CLUSTER_ID, $clusterid);
        $etva_nodes = EtvaNodePeer::doSelect($nodes_criteria);

        $oks = array();
        $errors = array();
        $method = 'create_network';
        $params = array(
                        'vlanid'=>$netid,
                        'name'=>$netname
                       );

        $tagged = $request->getParameter('vlan_tagged');
        if($tagged){
            $params['vlan_tagged'] = 1;
            $form_data['tagged'] = 1;
        }


        $untagged = $request->getParameter('vlan_untagged');
        if($untagged){
            $params['vlan_untagged'] = 1;
            $form_data['tagged'] = 0;
        }

        /*
         * if type of vlan is untagged check if there is already an untagged VLAN
         */
        if($params['vlan_untagged']){

            $untagged_vlan = EtvaVlanPeer::retrieveUntagged($clusterid);
            if($untagged_vlan){
                $msg = 'Untagged network already exist!';
                $msg_i18n = $this->getContext()->getI18N()->__($msg);
                $error = array('success' => false,
                                  'agent' => sfConfig::get('config_acronym'),
                                  'error' => $msg_i18n);

                //notify system log
                $this->dispatcher->notify(new sfEvent($error['agent'], 'event.log', array('message' => Etva::getLogMessage(array('name'=>$netname,'info'=>$msg), EtvaVlanPeer::_ERR_CREATE_),'priority'=>EtvaEventLogger::ERR)));
                $error = array('success'=>false,'agent'=>sfConfig::get('config_acronym'),'info'=>$msg_i18n,'error'=>array($error));

                // if is a CLI soap request return json encoded data
                if(sfConfig::get('sf_environment') == 'soap') return json_encode($error);

                // if is browser request return text renderer
                $error = $this->setJsonError($error);
                return $this->renderText($error);

            }
        } else {    // if not untagged
            $untagged_vlan = EtvaVlanPeer::retrieveUntagged($clusterid);
                // use untagged ifout to create vlan tagged
            if($untagged_vlan)
                $params['ifout'] = $untagged_vlan->getIntf();
        }


        // if network exists stop
        if($etva_vlan = EtvaVlanPeer::isUnique($netid, $netname, $clusteid)){

            if($etva_vlan->getName() == $netname){
                $msg = "Network $netname already exist!";
                $msg_i18n = $this->getContext()->getI18N()->__('Network %1% already exist!',array('%1%'=>$netname));
            }
            else{

                $msg = "Network ID $netid already exist!";
                $msg_i18n = $this->getContext()->getI18N()->__('Network %1% already exist!',array('%1%'=>'ID '.$netid));
            }
                        
            $error = array('success' => false,
                                  'agent' => sfConfig::get('config_acronym'),                                  
                                  'error' => $msg_i18n);            

            //notify system log
            $this->dispatcher->notify(new sfEvent($error['agent'], 'event.log', array('message' => Etva::getLogMessage(array('name'=>$netname,'info'=>$msg), EtvaVlanPeer::_ERR_CREATE_),'priority'=>EtvaEventLogger::ERR)));
            $error = array('success'=>false,'agent'=>sfConfig::get('config_acronym'),'info'=>$msg_i18n,'error'=>array($error));

            // if is a CLI soap request return json encoded data
            if(sfConfig::get('sf_environment') == 'soap') return json_encode($error);

            // if is browser request return text renderer
            $error = $this->setJsonError($error);
            return $this->renderText($error);


        }

        $form = new EtvaVlanForm();

//        error_log(print_r($form_data, true));
        $result = $this->processJsonForm($form_data, $form);
        
        if(!$result['success']){
            //$result['ok'] = $oks;
            //notify system log
            $this->dispatcher->notify(new sfEvent(sfConfig::get('config_acronym'), 'event.log', array('message' => Etva::getLogMessage(array('name'=>$netname,'info'=>'Could not insert network'), EtvaVlanPeer::_ERR_CREATE_),'priority'=>EtvaEventLogger::CRIT)));
            $error = $this->setJsonError($result);
            return $this->renderText($error);
        }
        //notify system log
        $this->dispatcher->notify(new sfEvent(sfConfig::get('config_acronym'), 'event.log', array('message' => Etva::getLogMessage(array('name'=>$netname), EtvaVlanPeer::_OK_CREATE_))));
        $etva_vlan = $result['object'];


        // send soap request to all nodes (agents)
        foreach($etva_nodes as $etva_node){

            // send soap request
            $response = $etva_node->soapSend($method,$params);            

            if($response['success']){
                $msg_i18n = $this->getContext()->getI18N()->__(EtvaVlanPeer::_OK_CREATE_,array('%name%'=>$netname));
                $response['info'] = $msg_i18n;
                $oks[] =  $response;

                //notify system log
                $this->dispatcher->notify(new sfEvent($etva_node->getName(), 'event.log', array('message' => Etva::getLogMessage(array('name'=>$netname), EtvaVlanPeer::_OK_CREATE_))));

                
            }else
                // soap response error....
                {
                $info = $response['info'];
                $info_i18n = $this->getContext()->getI18N()->__($info);
                $response['info'] = $info_i18n;
                $errors[] = $response;
                //notify system log
                $this->dispatcher->notify(new sfEvent($etva_node->getName(), 'event.log', array('message' => Etva::getLogMessage(array('name'=>$netname,'info'=>$info), EtvaVlanPeer::_ERR_CREATE_),'priority'=>EtvaEventLogger::ERR)));
            }

        }              

        if(!empty($errors)){

            $result = array('success'=>false,'agent'=>sfConfig::get('config_acronym'),'ok'=>$oks,'error'=>$errors);
            if(sfConfig::get('sf_environment') == 'soap') return json_encode($result);

            $return = $this->setJsonError($result);
            return  $this->renderText($return);            
        }                 

        $result = array('success'=>true,'agent'=>sfConfig::get('config_acronym'),'response'=>$oks);

        $return = json_encode($result);

        // if the request is made throught soap request...
        if(sfConfig::get('sf_environment') == 'soap') return $return;

        // if is browser request return text renderer
        $this->getResponse()->setHttpHeader('Content-type', 'application/json');        
        return  $this->renderText($return);
                  
    }
    
    /**
     * Removes vlan
     *
     * Issues soap request to all nodes
     *
     * @return json array string
     *
     */
    public function executeJsonRemove(sfWebRequest $request)
    {
        // vlan name and cluster id...
        $netname = $request->getParameter('name');
        $clusterid = $request->getParameter('cluster_id');

        if(!$clusterid){
            $defaultCluster = EtvaClusterPeer::retrieveDefaultCluster();
            $clusterid = $defaultCluster->getId();
        }
        
        $nodes_criteria = new Criteria();
        $nodes_criteria->add(EtvaNodePeer::CLUSTER_ID, $clusterid);
        $etva_nodes = EtvaNodePeer::doSelect($nodes_criteria);
//        $etva_nodes = EtvaNodePeer::doSelect(new Criteria());

        $oks = array();
        $errors = array();
        $method = 'destroy_network';
        $params = array(
                        'name'=>$netname
                       );

        error_log("VLANREMOVE[INFO] Getting vlan ".$netname." of cluster ".$clusterid);
        if(!$etva_vlan = EtvaVlanPeer::retrieveByClusterAndName($netname, $clusterid)){
            
            $msg = "Network $netname not found!";
            $msg_i18n = $this->getContext()->getI18N()->__('Network %1% not found!',array('%1%'=>$netname));

            $error = array('success'=>false,'ok'=>$oks,'error'=>array($msg_i18n));
            //notify system log
            $this->dispatcher->notify(new sfEvent(sfConfig::get('config_acronym'), 'event.log', array('message' => Etva::getLogMessage(array('name'=>$netname,'info'=>$msg), EtvaVlanPeer::_ERR_REMOVE_),'priority'=>EtvaEventLogger::CRIT)));
            // if is a CLI soap request return json encoded data
            if(sfConfig::get('sf_environment') == 'soap') return json_encode($error);

            // if is browser request return text renderer
            $error = $this->setJsonError($error);
            return $this->renderText($error);

        }

        // check if is in use....
        $c = new Criteria();
        $c->add(EtvaNetworkPeer::VLAN_ID,$etva_vlan->getId());

        if($etva_network = EtvaNetworkPeer::doSelectOne($c)){            
            $msg = "Network $netname in use!";
            $msg_i18n = $this->getContext()->getI18N()->__('Network %1% in use!',array('%1%'=>$netname));
            $msg_array = array('success' => false,
                                  'agent' => sfConfig::get('config_acronym'),
                                  'error' => $msg_i18n);

            $error = array('success'=>false,'agent'=>sfConfig::get('config_acronym'),'ok'=>$oks,'error'=>array($msg_array));
            //notify system log
            $this->dispatcher->notify(new sfEvent($error['agent'], 'event.log', array('message' => Etva::getLogMessage(array('name'=>$netname,'info'=>$msg), EtvaVlanPeer::_ERR_REMOVE_),'priority'=>EtvaEventLogger::ERR)));

            // if is a CLI soap request return json encoded data
            if(sfConfig::get('sf_environment') == 'soap') return json_encode($error);

            // if is browser request return text renderer
            $error = $this->setJsonError($error);
            return $this->renderText($error);
        }

//        error_log(print_r($etva_vlan,true));
        $etva_vlan->delete();
        //notify system log
        $this->dispatcher->notify(new sfEvent(sfConfig::get('config_acronym'), 'event.log', array('message' => Etva::getLogMessage(array('name'=>$netname), EtvaVlanPeer::_OK_REMOVE_))));
        
        // send soap request to all nodes (agents)
        foreach($etva_nodes as $etva_node){

            // send soap request
            $response = $etva_node->soapSend($method,$params);            

            if($response['success']){                                                

                $msg_i18n = $this->getContext()->getI18N()->__(EtvaVlanPeer::_OK_REMOVE_,array('%name%'=>$netname));
                $response['info'] = $msg_i18n;
                
                $oks[] =  $response;
                //notify system log
                $this->dispatcher->notify(new sfEvent($etva_node->getName(), 'event.log', array('message' => Etva::getLogMessage(array('name'=>$netname), EtvaVlanPeer::_OK_REMOVE_))));

            }else
                // soap response error....
                {
                    $info = $response['info'];
                    $info_i18n = $this->getContext()->getI18N()->__($info);
                    $response['info'] = $info_i18n;
                    $errors[] = $response;
                                        
                    //notify system log
                    $this->dispatcher->notify(new sfEvent($etva_node->getName(), 'event.log', array('message' => Etva::getLogMessage(array('name'=>$netname,'info'=>$info), EtvaVlanPeer::_ERR_REMOVE_),'priority'=>EtvaEventLogger::ERR)));
            }

        }
       
        if(!empty($errors)){

            $result = array('success'=>false,'agent'=>sfConfig::get('config_acronym'),'ok'=>$oks,'error'=>$errors);
            if(sfConfig::get('sf_environment') == 'soap') return json_encode($result);

            $return = $this->setJsonError($result);
            return  $this->renderText($return);

        }
    
        
        $result = array('success'=>true,'agent'=>sfConfig::get('config_acronym'),'response'=>$oks);

        $return = json_encode($result);        

        // if the request is made throught soap request...
        if(sfConfig::get('sf_environment') == 'soap') return $return;
        // if is browser request return text renderer
        $this->getResponse()->setHttpHeader('Content-type', 'application/json');        
        return  $this->renderText($return);


    }
    

    
    /**
     * returns json array encoded list of vlans
     * 
     */
    public function executeJsonList(sfWebRequest $request)
    {
        $isAjax = $request->isXmlHttpRequest();

        if(!$isAjax) return $this->redirect('@homepage');

        $p_cluster_id = $this->getRequestParameter('id');
        $p_level = $this->getRequestParameter('level');

        if($p_level){

            if(!$p_cluster_id){
                $error_msg = 'No cluster id defined.';
                $error = array('success'=>false,'error'=>$error_msg,'info'=>$error_msg);
                return $error;
            }

            if($p_level == 'node'){
                $dc_c = new Criteria(); //convert node id in cluster id
                $dc_c->add(EtvaNodePeer::ID, $p_cluster_id);
                $node = EtvaNodePeer::doSelectOne($dc_c);
                $p_cluster_id = $node->getClusterId();
            }elseif($p_level == 'server'){
                $dc_c = new Criteria(); //convert server id in cluster id
                $dc_c->add(EtvaServerPeer::ID, $p_cluster_id, Criteria::EQUAL);
                $server = EtvaServerPeer::doSelectOne($dc_c);
                $p_cluster_id = $server->getClusterId();
            }elseif($p_level == 'cluster'){
                //do nothing
            }else{
                error_log('NETWORK:[ERROR] executejsonlist invalid parameters');
            }
        }elseif(!$p_cluster_id){
            $defaultCluster = EtvaClusterPeer::retrieveDefaultCluster();
            $p_cluster_id = $defaultCluster->getId();
        }

        $c = new Criteria();
        $c->add(EtvaVlanPeer::CLUSTER_ID, $p_cluster_id, Criteria::EQUAL);
        $vlan_nodes = EtvaVlanPeer::doSelect($c);

        $list = array();
        foreach($vlan_nodes as $vlan){
            $list[] = $vlan->toArray(BasePeer::TYPE_FIELDNAME);
        }

        $untagged_vlan = EtvaVlanPeer::retrieveUntagged($p_cluser_id);
        $hasUntagged = 0;
        if($untagged_vlan) $hasUntagged = 1;

        $result = array('total' =>   count($list),'data'  => $list,'hasUntagged'=>$hasUntagged);

        $result = json_encode($result);

        if(sfConfig::get('sf_environment') == 'soap'){
            $soap_result = array( success=>true, 'total' =>   count($list), 'response' => $list);
            return json_encode($soap_result);
        }

        $this->getResponse()->setHttpHeader('Content-type', 'application/json');
        return $this->renderText($result);
    }
    

  public function executeJsonTree(sfWebRequest $request)
  {
    $etva_vlan_list = EtvaVlanPeer::doSelect(new Criteria());

    $tree = array();
    foreach($etva_vlan_list as $vlan)
                $tree[] = array('id'=>$vlan->getId(),'uiProvider'=>'col','iconCls'=>'devices-folder','text'=> 'Vlan '.$vlan->getId(),'name'=>$vlan->getName(),'singleClickExpand'=>true,'leaf'=>true);
                                    
                
    
    $result = json_encode($tree);

    $this->getResponse()->setHttpHeader('Content-type', 'application/json');
    return $this->renderText($result);
  }


  protected function processJsonForm($request, sfForm $form)
  {         

    $form->bind($request);

    if ($form->isValid())
    {
        try{
            $etva_vlan = $form->save();

        }catch(Exception $e){
            $result = array('success'=>false,'error'=>array('vlan'=>$e->getMessage()));
            return $result;
        }
        
        //$result = array('success'=>true,'insert_id'=>$etva_server->getId());
        $result = array('success'=>true, 'object'=>$etva_vlan);
        return $result;

    }
    else
    {
        error_log("CREATEVLAN[ERROR] Form is invalid");
        $errors = array();

        foreach ($form->getFormattedErrors() as $error){            
            $errors[] = $error;
        }
        
        $msg_err = implode('<br>',$errors);        
        $err = array('success'=>false,'agent'=>sfConfig::get('config_acronym'),'error'=>$msg_err);
        $result = array('success'=>false,'agent'=>sfConfig::get('config_acronym'),'error'=>array($err));
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
      $this->getResponse()->setHttpHeader('Content-type', 'application/json');
      return $error;

  }

  /**
   * Used to process soap requests => updateVirtAgentVlans
   *
   * Updates vlan info sent by virt Agent
   *
   * Replies with succcess
   *
   * $request may contain the following keys:
   * - uid: uid (virtAgent sending request uid)
   * - vlans (object containing vlans info)
   * @return array array(new vlans)
   */
  public function executeSoapUpdate(sfWebRequest $request)
  {

         
    if(sfConfig::get('sf_environment') == 'soap'){

        //get config data file
        $etva_data = Etva::getEtvaModelFile();
        //check etva model type
        $etvamodel = $etva_data['model'];

        $vlans = $request->getParameter('vlans');


        
        $vlans_names = array();
        foreach($vlans as $vlanInfo){
            $vlan_data = (array) $vlanInfo;
            $vlans_names[] = $vlan_data['name'];
        }



        $c = new Criteria();
        $c->add(EtvaNodePeer::UUID ,$request->getParameter('uuid'));



        if(!$etva_node = EtvaNodePeer::doSelectOne($c)){
            $error_msg = sprintf('Object etva_node does not exist (%s).', $request->getParameter('uuid'));

            $node_log = Etva::getLogMessage(array('name'=>$request->getParameter('uuid')), EtvaNodePeer::_ERR_NOTFOUND_UUID_);
            //notify system log
            $this->dispatcher->notify(new sfEvent(sfConfig::get('config_acronym'), 'event.log', array('message' => Etva::getLogMessage(array('info'=>$node_log), EtvaVlanPeer::_ERR_SOAPUPDATE_),'priority'=>EtvaEventLogger::ERR)));
            $error = array('success'=>false,'error'=>$error_msg);            
            return $error;
        }

        $node_initialize = $etva_node->getInitialize();
        if($node_initialize!=EtvaNode_VA::INITIALIZE_OK)
        {
            $error_msg = sprintf('Etva node initialize status: %s', $node_initialize);
            $error = array('success'=>false,'error'=>$error_msg);

            return $error;

        }

       
        /*
         * checks if CM vlans exists on node info Vlans
         * return vlans to be created on node
         *
         */

        // filter results by cluster
        $vlan_criteria = new Criteria();
        $vlan_criteria->add(EtvaVlanPeer::CLUSTER_ID, $etva_node->getClusterId());
        $etva_vlans = EtvaVlanPeer::doSelect($vlan_criteria);
        
        $new_vlans =array();
        foreach($etva_vlans as $etva_vlan){
    
            if(!in_array($etva_vlan->getName(),$vlans_names)){

                $data = array(
                            'name'   => $etva_vlan->getName(),
                            'ifout'  => $etva_vlan->getIntf()                            
                );


                /*
                 * if model type == enterprise send vlan ID and untagged/tagged option
                 */
                if(strtolower($etvamodel)!='standard'){
                    $data['vlanid'] = $etva_vlan->getVlanId();
                    $tagged = $etva_vlan->getTagged();

                    if($tagged) $data['vlan_tagged'] = 1;
                    else $data['vlan_untagged'] = 1;
                }

                $new_vlans[$etva_vlan->getName()] = $data;          
            }
            
        
            
        }
        
        $vlans_names = array_keys($new_vlans);
        $vlans = implode(', ',$vlans_names);
                
        //notify system log
        $this->dispatcher->notify(new sfEvent(sfConfig::get('config_acronym'), 'event.log', array('message' => Etva::getLogMessage(array('name'=>$etva_node->getName(),'info'=>$vlans), EtvaVlanPeer::_OK_SOAPUPDATE_),'priority'=>EtvaEventLogger::INFO)));

        
        return $new_vlans;

     }// end soap request
       
  }  
    
  
}
