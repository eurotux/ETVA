<?php

/**
 * cluster actions.
 *
 * @package    centralM
 * @subpackage cluster
 * @author     Your name here
 * @version    SVN: $Id: actions.class.php 23810 2009-11-12 11:07:44Z Kris.Wallsmith $
 */
class clusterActions extends sfActions
{
 /**
  * Executes index action
  *
  * @param sfRequest $request A request object
  */
  public function executeIndex(sfWebRequest $request)
  {
    $this->forward('default', 'module');
  }

/*
 *
 * list all cluster
 *
 * return json array response
 *
 */
    public function executeJsonList(sfWebRequest $request)
    {
        $c = new Criteria();
        $clusters = EtvaClusterPeer::doSelect($c);
        $elements = array();
        foreach ($clusters as $cluster){
            $elements[] = $cluster->toArray();
        }
        
        $return = array('data'  => $elements);

        $result=json_encode($return);
        $this->getResponse()->setHttpHeader('Content-type', 'application/json');
        return $this->renderText($result);
    }

    public function executeJsonGetId(sfWebRequest $request)
    {
        $level  = $request->getParameter('level');
        $id     = $request->getParameter('id');

        if($level == 'node'){
            $dc_c = new Criteria(); //convert node id in cluster id
            $dc_c->add(EtvaNodePeer::ID, $id);
            $node = EtvaNodePeer::doSelectOne($dc_c);
            $cluster_id = $node->getClusterId();
        }elseif($level == 'server'){
            $dc_c = new Criteria(); //convert server id in cluster id
            $dc_c->addJoin(EtvaNodePeer::ID, EtvaServerPeer::NODE_ID);
            $dc_c->add(EtvaServerPeer::ID, $id, Criteria::EQUAL);
            $node = EtvaNodePeer::doSelectOne($dc_c);
            $cluster_id = $node->getClusterId();
        }

        $return = array('cluster_id'  => $cluster_id);
//        error_log(print_r($return, true));

        $result=json_encode($return);
        $this->getResponse()->setHttpHeader('Content-type', 'application/json');
        return $this->renderText($result);
    }

    public function executeView_ClusterWizard(sfWebRequest $request){
        $vlan_form = new EtvaVlanForm();
        $vlanid_val = $vlan_form->getValidator('vlanid');
        $this->min_vlanid = $vlanid_val->getOption('min');
        $this->max_vlanid = $vlanid_val->getOption('max');

        $vlanname_val = $vlan_form->getValidator('name');
        $this->min_vlanname = $vlanname_val->getOption('min_length');
        $this->max_vlanname = $vlanname_val->getOption('max_length');

    }

    public function executeJsonExists(sfWebRequest $request){
        $clustername = $request->getParameter('name');
        if($clustername){
            $this->dispatcher->notify(new sfEvent($error['agent'], 'event.log', array('message' => Etva::getLogMessage(array('name'=>$clustername,'info'=>$msg), EtvaClusterPeer::_ERR_CLUSTER_),'priority'=>EtvaEventLogger::ERR)));
            $error = array('success'=>false,'agent'=>sfConfig::get('config_acronym'),'info'=>$msg_i18n,'error'=>array($error_msg));
        }

        $c = new Criteria();
        $c->add(EtvaClusterPeer::NAME, $clustername, Criteria::EQUAL);
        if(EtvaClusterPeer::doCount($c) != 0){
            $msg = 'not avaialable';
        }else{
            $msg = 'avaialable';
        }

        $msg_i18n = $this->getContext()->getI18N()->__($msg);

        $return = array('success' => true,
                              'avaialable' => true,
                              'msg' => $msg_i18n);

        $result=json_encode($return);
        $this->getResponse()->setHttpHeader('Content-type', 'application/json');
        return $this->renderText($result);
    }

    /**
     * Creates cluster
     * Does not create the default cluster
     *
     * @return 
     *
     */
    public function executeJsonCreate(sfWebRequest $request)
    {
        $clustername = $request->getParameter('name');
        $form_data = array();

        if($clustername){
            $form_data['name'] = $clustername;
            $form_data['isDefaultCluster'] = '0';
            error_log("CLUSTER[INFO] Creating cluster with name ".$clustername);
        }else{
            //invalid parameters
            $msg = "Invalid parameters";
            error_log("CLUSTER[ERROR] Invalid parameters");
            $msg_i18n = $this->getContext()->getI18N()->__($msg);
            $error_msg = array('success' => false,
                                  'agent' => sfConfig::get('config_acronym'),
                                  'error' => $msg_i18n);
            //notify system log
            $this->dispatcher->notify(new sfEvent($error['agent'], 'event.log', array('message' => Etva::getLogMessage(array('name'=>$clustername,'info'=>$msg), EtvaClusterPeer::_ERR_CREATE_),'priority'=>EtvaEventLogger::ERR)));            $error = array('success'=>false,'agent'=>sfConfig::get('config_acronym'),'info'=>$msg_i18n,'error'=>array($error_msg));

            // if is a CLI soap request return json encoded data
            if(sfConfig::get('sf_environment') == 'soap') return json_encode($error);

            // if is browser request return text renderer
            $error = $this->setJsonError($error);
            return $this->renderText($error);
        }

        if(!preg_match('/^[a-zA-Z][a-zA-Z0-9\-\_]+$/', $clustername)){
            $msg = "Invalid name format";
            error_log("CLUSTER[ERROR]".$msg);
            $msg_i18n = $this->getContext()->getI18N()->__($msg);
            $error_msg = array('success' => false,
                                  'agent' => sfConfig::get('config_acronym'),
                                  'error' => $msg_i18n);

            //notify system log
            $this->dispatcher->notify(new sfEvent($error['agent'], 'event.log', array('message' => Etva::getLogMessage(array('name'=>$clustername,'info'=>$msg), EtvaClusterPeer::_ERR_CREATE_),'priority'=>EtvaEventLogger::ERR)));
            $error = array('success'=>false,'agent'=>sfConfig::get('config_acronym'),'info'=>$msg_i18n,'error'=>array($error_msg));

            // if is a CLI soap request return json encoded data
            if(sfConfig::get('sf_environment') == 'soap') return json_encode($error);

            // if is browser request return text renderer
            $error = $this->setJsonError($error);
            return $this->renderText($error);
        }

//        $form_data['isDefaultCluster'];
        //cluster name validator

        $cluster_criteria = new Criteria();
        $cluster_criteria->add(EtvaClusterPeer::NAME, $clustername, Criteria::EQUAL);
        $exists = EtvaClusterPeer::doCount($cluster_criteria);
        
        if($exists != 0){            
            //cluster already exists
            $msg = 'Cluster name not available';
            error_log("CLUSTER[INFO]".$msg);
            $msg_i18n = $this->getContext()->getI18N()->__($msg);

            $this->dispatcher->notify(new sfEvent(sfConfig::get('config_acronym'), 'event.log', array('message' => Etva::getLogMessage(array('name'=>$netname,'info'=>$msg_i18n), EtvaClusterPeer::_ERR_CREATE_),'priority'=>EtvaEventLogger::ERR)));

            $result = array('success' => false,
                              'avaialable' => true,
                              'info'    => $msg_i18n,
                              'error' => $msg_i18n);
            $return = json_encode($result);

            // if is browser request return text renderer
            $this->getResponse()->setHttpHeader('Content-type', 'application/json');
            return  $this->renderText($return);
        }

        // create cluster entry
        $form = new EtvaClusterForm();
        $result = $this->processJsonForm($form_data, $form);
        
        if(!$result['success']){
            error_log("CLUSTER[ERROR] Error processing cluster form");
            //$result['ok'] = $oks;
            //notify system log
            $this->dispatcher->notify(new sfEvent(sfConfig::get('config_acronym'), 'event.log', array('message' => Etva::getLogMessage(array('name'=>$netname,'info'=>'Could not insert cluster'), EtvaClusterPeer::_ERR_CREATE_),'priority'=>EtvaEventLogger::ERR)));
            $error = $this->setJsonError($result);
            return $this->renderText($error);
        }else{
            //Cluster creation well succeeded
            $this->dispatcher->notify(new sfEvent(sfConfig::get('config_acronym'), 'event.log', array('message' => Etva::getLogMessage(array('name'=>$netname), EtvaClusterPeer::_OK_CREATE_))));
        }

        //get cluster object
        $etva_cluster =  $result['object'];

        //create default network configuration
        error_log("CLUSTER[INFO] Loading cluster default networks");
        $res = $this->loadNetworkDefaults($etva_cluster);

        if($res == 1){

            $i18n_msg = $msg_i18n = $this->getContext()->getI18N()->__('Cluster added to the system!');

            //notify system log
            $this->dispatcher->notify(new sfEvent(sfConfig::get('config_acronym'), 'event.log', array('message' => Etva::getLogMessage(array('name'=>$netname), EtvaVlanPeer::_OK_CREATE_))));
            $result = array('success'=>true,'agent'=>sfConfig::get('config_acronym'),'response'=> $i18n_msg, 'cluster_id' => $etva_cluster->getId());

        }elseif($res == 0){
            error_log("CLUSTER[ERROR] Error creating datacenter default networks");
            $this->dispatcher->notify(new sfEvent(sfConfig::get('config_acronym'), 'event.log', array('message' => Etva::getLogMessage(array('name'=>$netname,'info'=>'Could not create vlans'), EtvaVlanPeer::_ERR_CREATE_),'priority'=>EtvaEventLogger::CRIT)));
            $error = $this->setJsonError($result);
            return $this->renderText($error);
        }
        
        $return = json_encode($result);

        // if is browser request return text renderer
        $this->getResponse()->setHttpHeader('Content-type', 'application/json');
        return  $this->renderText($return);
    }

    protected function loadNetworkDefaults($clusterObj){

        //call network load task
        chdir(sfConfig::get('sf_root_dir')); // Trick plugin into thinking you are in a project directory
        $task = new etvaLoadConfTask($this->dispatcher, new sfFormatter());
        return $task->run(array(), array('cluster_id' => $clusterObj->getId()));  // array('option_name' => 'option'));
    }

    protected function processJsonForm($request, sfForm $form)
    {

        $form->bind($request);

        if ($form->isValid())
        {
            try{
                $etva_cluster = $form->save();

            }catch(Exception $e){
                $result = array('success'=>false,'error'=>array('cluster'=>$e->getMessage()), 'obj'=>$etva_cluster);
                return $result;
            }

            //$result = array('success'=>true,'insert_id'=>$etva_server->getId());
            $result = array('success'=>true, 'object'=>$etva_cluster);
            return $result;

        }
        else
        {
            error_log("CREATECLUSTER[ERROR] Form is invalid");
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

    public function executeJsonName(sfWebRequest $request)
    {
        $id = $request->getParameter('id');
        $method = $request->getParameter('method');
        $name = $request->getParameter('name');

        if($etva_cluster = EtvaClusterPeer::retrieveByPK($id)) $count = 1;
        else{

            $msg_i18n = $this->getContext()->getI18N()->__(EtvaClusterPeer::_ERR_CLUSTER_,array('%id%'=>$id));
            $error = array('success'=>false,'agent'=>sfConfig::get('config_acronym'),'error'=>$msg_i18n,'info'=>$msg_i18n);

            // if is browser request return text renderer
            $error = $this->setJsonError($error);
            return $this->renderText($error);
        }
        
        switch($method){
            case 'update':
                try{
                    //check if name is available
                    $cname = new Criteria();
                    $cname->add(EtvaClusterPeer::NAME, $name);
                    if(EtvaClusterPeer::doSelectOne($cname)){
                        $msg = 'Cluster with the same name already exists';
                        $msg_i18n = $this->getContext()->getI18N()->__($msg);
                        $result = array(
                               'success'    => false,
//                               'total'      => 1,
//                               'data'       => array('id'=>$cluster_obj->getId(),'name'=>$cluster_obj->getName()),
                               'agent'      => sfConfig::get('config_acronym'),
                               'info'       => $msg_i18n
                        );
                        $return = json_encode($result);
                        $this->getResponse()->setHttpHeader('Content-type', 'application/json');
                        return $this->renderText($return);
                    }

                    $c = new Criteria();
                    $c->add(EtvaClusterPeer::ID, $id, Criteria::EQUAL);
                    $cluster_obj = EtvaClusterPeer::doSelectOne($c);
                    $cluster_obj->setName($name);
                    $cluster_obj->save();                    
                }catch(Exception $e){
                    $result = array(
                        'success' => false,
                        'error'   => 'Could not perform operation',
                        'agent'   => sfConfig::get('config_acronym'),
                        'info'    => 'Could not perform operation'
                    );

                    $return = json_encode($result);
                    $this->getResponse()->setHttpHeader('Content-type', 'application/json');
                    return $this->renderText($return);
                }

                $msg = 'Cluster name changed successfully';
                $msg_i18n = $this->getContext()->getI18N()->__($msg);
                $result = array(
                       'success'    => true,
                       'total'      => 1,
                       'data'       => array('id'=>$cluster_obj->getId(),'name'=>$cluster_obj->getName()),
                       'agent'      => sfConfig::get('config_acronym'),
                       'info'       => $msg_i18n
                );

                $return = json_encode($result);
                $this->getResponse()->setHttpHeader('Content-type', 'application/json');
                return $this->renderText($return);



//                // if the request is made throught soap request...
//                if(sfConfig::get('sf_environment') == 'soap') return $return;
//
//                // if is browser request return text renderer
//                $this->getResponse()->setHttpHeader('Content-type', 'application/json');
//                return  $this->renderText($return);

                break;
        default  :
                $return = array(
                               'success' => true,
                               'total' => $count,
                               'data'  => array('id'=>$etva_cluster->getId(),'name'=>$etva_cluster->getName())
                );

                $result = json_encode($return);
                $this->getResponse()->setHttpHeader('Content-type', 'application/json');
                return $this->renderText($result);
        }
    }


    /**
     * Moves an unaccepted node to the given cluster
     * @param sfWebRequest $request
     * json, cluster id (target) and node id
     * @return json, success
     */
    public function executeJsonMoveNode(sfWebRequest $request)
    {
        $cluster_id = $request->getParameter('to_cluster_id');
        $node_id = $request->getParameter('node_id');

        try{
            $c = new Criteria();
            $c->add(EtvaNodePeer::ID, $node_id, Criteria::EQUAL);
            if(EtvaNodePeer::doCount($c) == 1){
                $etva_node = EtvaNodePeer::doSelectOne($c);
                $etva_node->setClusterId($cluster_id);
                $etva_node->save();
            }else{
                $msg_i18n = $this->getContext()->getI18N()->__(EtvaNodePeer::_ERR_NOTFOUND_ID_,array('%id%'=>$node_id));
                $error = array('success'=>false,'agent'=>sfConfig::get('config_acronym'),'error'=>$msg_i18n,'info'=>$msg_i18n);

                // if is browser request return text renderer
                $error = json_encode($error);
                return $this->renderText($error);
            }
        }catch(Exception $e){
            error_log("CLUSTER[ERROR] Move of node ".$node_id." into cluster ".$cluster_id." failed!");
            $error = array('success'=>false,'error'=>array('cluster'=>$e->getMessage()));
            $error = json_encode($error);
            return $this->renderText($error);
        }

        $msg_i18n = $this->getContext()->getI18N()->__('Node %name% moved successfully',array('%name%'=>$etva_node->getName()));     // EtvaServerPeer::_ERR_NOTFOUND_ID_,array('%id%'=>$sid));

        $return = array(
                'agent'=>sfConfig::get('config_acronym'),
                'success' => true,
                'info'       => $msg_i18n
        );

        $result = json_encode($return);
        $this->getResponse()->setHttpHeader('Content-type', 'application/json');
        return $this->renderText($result);

    }
}
