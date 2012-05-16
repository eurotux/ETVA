<?php

class EtvaNode_VA
{
    private $etva_node;
    
    const INITIALIZE_OK = 'ok';
    const INITIALIZE = 'reinitialize';    
    const INITIALIZE_PENDING = 'pending';    
    const INITIALIZE_CMD_AUTHORIZE = 'authorize';    
    
    const CHANGE_NAME = 'change_va_name';
    const CHANGE_UUID = 'change_uuid';
    const CHANGE_IP = 'change_ip';
    const UMOUNT_ISOSDIR = 'umount_isosdir';

    public function EtvaNode_VA(EtvaNode $etva_node = null)
    {

        $this->etva_node = new EtvaNode();

        if($etva_node) $this->etva_node = $etva_node;
    }

    public function send_umount_isosdir()
    {
        $method = self::UMOUNT_ISOSDIR;
        $params = array();

        $response = $this->etva_node->soapSend($method,$params);        
        return $response;

    }       
    

    public function send_change_name($name)
    {
        $method = self::CHANGE_NAME;
        $params = array('name'=>$name);

        $response = $this->etva_node->soapSend($method,$params);
        $result = $this->processChangeNameResponse($response, $method);
        return $result;
        
    }

    public function send_change_ip($network)
    {

        $sys_network = new SystemNetwork();

        if(!$sys_network->fromArray($network)){

            $intf = $network['if'];

            $msg_i18n = sfContext::getInstance()->getI18N()->__(SystemNetwork::_ERR_NOTFOUND_INTF_,array('%name%'=>$intf,'%info%'=>''));

            //notify event log
            $node_log = Etva::getLogMessage(array('name'=>$intf,'info'=>''), SystemNetwork::_ERR_NOTFOUND_INTF_);
            $message = Etva::getLogMessage(array('name'=>sfConfig::get('config_acronym'),'info'=>$node_log), EtvaNodePeer::_ERR_CHANGEIP_ );
            sfContext::getInstance()->getEventDispatcher()->notify(
                new sfEvent(sfConfig::get('config_acronym'), 'event.log',
                    array('message' => $message,'priority'=>EtvaEventLogger::ERR)));


            $error = array('success'=>false,'agent'=>sfConfig::get('config_acronym'),'error'=>$msg_i18n);
            return $error;
        }

        $criteria = new Criteria();
        $criteria->add(EtvaNodePeer::ID,$this->etva_node->getId(),Criteria::NOT_EQUAL);
        $cluster_nodes = $this->etva_node->getNodesCluster($criteria);

        foreach ($cluster_nodes as $node)
        {
            $node_name = $node->getName();
            $node_ip = $node->getIp();                        
            if($sys_network->get(SystemNetwork::IP) == $node_ip){
                
                $msg_i18n = sfContext::getInstance()->getI18N()->__(SystemNetwork::_ERR_IP_INUSE_,array('%ip%'=>$node_ip,'%name%'=>$node_name));
                $msg = Etva::getLogMessage(array('ip'=>$node_ip,'name'=>$node_name), SystemNetwork::_ERR_IP_INUSE_);

                $message = Etva::getLogMessage(array('name'=>sfConfig::get('config_acronym'),'info'=>$msg), EtvaNodePeer::_ERR_CHANGEIP_ );
                sfContext::getInstance()->getEventDispatcher()->notify(
                    new sfEvent(sfConfig::get('config_acronym'), 'event.log', array('message' => $message,'priority'=>EtvaEventLogger::ERR)));
                
                $error = array('success'=>false,'agent'=>sfConfig::get('config_acronym'),'error'=>$msg_i18n);
                return $error;                
            }
        }

        $method = self::CHANGE_IP;
        $params = $sys_network->_VA();                

        /*
         * update MA if any...
         *
         */
        $etva_servers = $this->etva_node->getEtvaServers();
        foreach($etva_servers as $etva_server)
        {
            $server_ma = $etva_server->getAgentTmpl();
            $server_state = $etva_server->getState();

            if($server_state && $server_ma){
                $etva_server->setSoapTimeout(5);
                $response = $etva_server->soapSend($method,$params);
                $result = $this->processChangeIpResponse($response, $method);
            }
            
        }

        $this->etva_node->setSoapTimeout(5);
        $response = $this->etva_node->soapSend($method,$params);        
        $result = $this->processChangeIpResponse($response, $method);
        return $result;

    }
    
    
    public function processChangeIpResponse($response, $method)
    {
        $etva_node = $this->etva_node;
        $node_name = $etva_node->getName();

        if(!$response['success'])
        {
            $result = $response;            


            if($response['faultactor']!='socket_read')
            {

                $message = Etva::getLogMessage(array('name'=>$response['agent'],'info'=>$response['info']), EtvaNodePeer::_ERR_CHANGEIP_ );
                $msg_i18n = sfContext::getInstance()->getI18N()->__(EtvaNodePeer::_ERR_CHANGEIP_,array('%name%'=>$response['agent'],'%info%'=>$response['info']));
                $result['error'] = $msg_i18n;

                sfContext::getInstance()->getEventDispatcher()->notify(
                    new sfEvent(sfConfig::get('config_acronym'), 'event.log',
                        array('message' => $message,'priority'=>EtvaEventLogger::ERR)));

                $info = array('success'=>false,'agent'=>$response['agent'],'error'=>$response['info'],'info'=>$response['info']);
                return  $result;

            }

        }       

        $message = Etva::getLogMessage(array('name'=>$node_name), EtvaNodePeer::_OK_CHANGEIP_);
        $msg_i18n = sfContext::getInstance()->getI18N()->__(EtvaNodePeer::_OK_CHANGEIP_,array('%name%'=>$node_name));
        sfContext::getInstance()->getEventDispatcher()->notify(new sfEvent($node_name, 'event.log',array('message' => $message)));
        
        $result = array('success'=>true,'agent'=>$response['agent'],'response'=>$msg_i18n);
        return $result;

    }


    public function send_initialize()
    {

        $params = array();
        $method = self::INITIALIZE;                
        $response = $this->etva_node->soapSend($method,$params);        
        $result = $this->processResponse($response, $method);
        return $result;        
    }

    public function send_change_uuid($uuid)
    {
        $method = self::CHANGE_UUID;
        $params = array('uuid'=>$uuid);

        $response = $this->etva_node->soapSend($method,$params);
        $result = $this->processResponse($response, $method);
        return $result;
    }


    public function processChangeNameResponse($response, $method)
    {
        $etva_node = $this->etva_node;
        $node_name_old = $etva_node->getName();
        
        if($response['success'])
        {

            $response_decoded = (array) $response['response'];
            $returned_object = (array) $response_decoded['_obj_'];

            $etva_node->initData($returned_object);
            $node_name_new = $etva_node->getName();

            $etva_node->save();

            $message = Etva::getLogMessage(array('name_old'=>$node_name_old,'name_new'=>$node_name_new), EtvaNodePeer::_OK_CHANGENAME_);
            $msg_i18n = sfContext::getInstance()->getI18N()->__(EtvaNodePeer::_OK_CHANGENAME_,array('%name_old%'=>$node_name_old,'%name_new%'=>$node_name_new));
            sfContext::getInstance()->getEventDispatcher()->notify(new sfEvent($node_name_new, 'event.log',array('message' => $message)));

            $result = array('success'=>true, 'agent'=>$node_name_new, 'response'=>$msg_i18n);
            return $result;



        }else
        {
            $result = $response;

            $message = Etva::getLogMessage(array('name'=>$node_name_old,'info'=>$response['info']), EtvaNodePeer::_ERR_CHANGENAME_);

            $msg_i18n = sfContext::getInstance()->getI18N()->__(EtvaNodePeer::_ERR_CHANGENAME_,array('%name%'=>$node_name_old,'%info%'=>$response['info']));
            $result['error'] = $msg_i18n;

            sfContext::getInstance()->getEventDispatcher()->notify(new sfEvent($response['agent'], 'event.log',array('message' => $message,'priority'=>EtvaEventLogger::ERR)));

            return  $result;

        }

    }

    public function processResponse($response, $method)
    {
        $etva_node = $this->etva_node;
        $node_name = $etva_node->getName();
       
        $msg_ok_type = EtvaNodePeer::_OK_INITIALIZE_;
        $msg_err_type = EtvaNodePeer::_ERR_INITIALIZE_;
        $initialize = self::INITIALIZE_OK;                                
                        
        if($response['success'])
        {

            $response_decoded = (array) $response['response'];
            $returned_object = (array) $response_decoded['_obj_'];
                       
            $etva_node->setInitialize($initialize);
            $etva_node->save();

            $message = Etva::getLogMessage(array('name'=>$node_name), $msg_ok_type);
            $msg_i18n = sfContext::getInstance()->getI18N()->__($msg_ok_type,array('%name%'=>$node_name));
            sfContext::getInstance()->getEventDispatcher()->notify(new sfEvent($node_name, 'event.log',array('message' => $message)));

            $result = array('success'=>true, 'agent'=>$node_name, 'response'=>$msg_i18n);
            return $result;                      



        }else
        {
            $result = $response;
            
            $message = Etva::getLogMessage(array('name'=>$node_name,'info'=>$response['info']), $msg_err_type);

            $msg_i18n = sfContext::getInstance()->getI18N()->__($msg_err_type,array('%name%'=>$node_name,'%info%'=>$response['info']));
            $result['error'] = $msg_i18n;

            sfContext::getInstance()->getEventDispatcher()->notify(new sfEvent($response['agent'], 'event.log',array('message' => $message,'priority'=>EtvaEventLogger::ERR)));

            return  $result;
            
        }            

    }

    
    /**
     *
     * Clears DB node storage info
     * if invoked by appliance restore set restore stage
     *
     * @param string $uuid      Node uuid
     * @param mixed $error     Error message associated with the clear action
     */
    public function clear($uuid, $error)
    {

        $c = new Criteria();
        $c->add(EtvaNodePeer::UUID,$uuid);
        $etva_node = EtvaNodePeer::doSelectOne($c);

        $error_array = (array) $error;        

        $message = $error_array['errorstring'];
        //notify system log
        sfContext::getInstance()->getEventDispatcher()->notify(
            new sfEvent($etva_node->getName(),'event.log',
                array('message' =>$message, 'priority'=>EtvaEventLogger::ERR)
        ));        

        $etva_node->clearStorage(); // removes pvs, vgs and lvs

        /*
         * check if is an appliance restore operation...
         */
        $apli = new Appliance();
        $action = $apli->getStage(Appliance::RESTORE_STAGE);
        if($action){
            $apli->disable(false);
            $apli->setStage(Appliance::RESTORE_STAGE,Appliance::VA_ERROR_STORAGE);
        }

        // remove backup VA file
        $apli->del_backupconf_file(Appliance::VA_ARCHIVE_FILE,$etva_node->getUuid(),'VA');

        return array('success'=>true);

    }


    public function restore_ok($uuid, $ok)
    {

        $c = new Criteria();
        $c->add(EtvaNodePeer::UUID,$uuid);
        $etva_node = EtvaNodePeer::doSelectOne($c);

        $ok_array = (array) $ok;
        $message = 'Node restore ok';
        
        //notify system log
        sfContext::getInstance()->getEventDispatcher()->notify(
            new sfEvent($etva_node->getName(),'event.log',
                array('message' =>$message, 'priority'=>EtvaEventLogger::INFO)
        ));

        /*
         * check if is an appliance restore operation...it should be...
         */
        $apli = new Appliance();
        $action = $apli->getStage(Appliance::RESTORE_STAGE);
        if($action){
            $apli->setStage(Appliance::RESTORE_STAGE,Appliance::VA_COMPLETED);
            $apli->disable(false);

        }

        // remove backup VA file
        $apli->del_backupconf_file(Appliance::VA_ARCHIVE_FILE,$etva_node->getUuid(),'VA');
        

        return array('success'=>true);

    }
    
    /*
     * initialize info based on passed data.
     */

    public function initialize($data)
    {

        $uuid = $data['uuid'];
        $etva_data = Etva::getEtvaModelFile();

        if(!$etva_data){
            $error_msg = 'Could not process etva-model.conf';
            $error = array('success'=>false,'error'=>$error_msg);

            //notify system log
            $message = Etva::getLogMessage(array('name'=>$data['name'],'info'=>$error_msg), EtvaNodePeer::_ERR_SOAPINIT_);
            sfContext::getInstance()->getEventDispatcher()->notify(
                new sfEvent(sfConfig::get('config_acronym'),
                        'event.log',
                        array('message' =>$message,'priority'=>EtvaEventLogger::ERR)
            ));

            return $error;
        }

        $etvamodel = $etva_data['model'];

//        /*
//         * add default cluster ID to node
//         */
//        if(!$default_cluster = EtvaClusterPeer::retrieveDefaultCluster()){
//            $error_msg = sprintf('Default Object etva_cluster does not exist ');
//            $error = array('success'=>false,'error'=>$error_msg);
//
//            //notify system log
//            $cluster_message = Etva::getLogMessage(array('info'=>$error_msg), EtvaClusterPeer::_ERR_DEFAULT_CLUSTER_);
//            $message = Etva::getLogMessage(array('name'=>$data['name'],'info'=>$cluster_message), EtvaNodePeer::_ERR_SOAPINIT_);
//            sfContext::getInstance()->getEventDispatcher()->notify(
//                new sfEvent(sfConfig::get('config_acronym'),
//                        'event.log',
//                        array('message' =>$message,'priority'=>EtvaEventLogger::ERR)
//            ));
//
//            return $error;
//        }
//
//        $data['cluster_id'] = $default_cluster->getId();        

        $c = new Criteria();
        $c->add(EtvaNodePeer::UUID,$uuid);        
        $etva_node = EtvaNodePeer::doSelectOne($c);

        if($etva_node)
        {
            $data['cluster_id'] = $etva_node->getClusterId();
            $node_initialize = $etva_node->getInitialize();
            $data['initialize'] = $node_initialize;
            if(empty($node_initialize)) $data['initialize'] = self::INITIALIZE_PENDING;

            if($etvamodel == 'standard') $data['initialize'] = self::INITIALIZE_OK;
            else if($node_initialize!=self::INITIALIZE_OK) return array('success'=>true);
            
            $data['id'] = $etva_node->getId();

            /*
             * calculate free mem
             */
            $etva_node->setMemtotal($data['memtotal']);
            $etva_node->updateMemFree();
            $data['memfree'] = $etva_node->getMemfree();

            $uuid = $etva_node->getUuid();
            $form = new EtvaNodeForm($etva_node);
            
        }else
        {
            /*
             * add default cluster ID to node
             */
            if(!$default_cluster = EtvaClusterPeer::retrieveDefaultCluster()){
                $error_msg = sprintf('Default Object etva_cluster does not exist ');
                $error = array('success'=>false,'error'=>$error_msg);
    
                //notify system log
                $cluster_message = Etva::getLogMessage(array('info'=>$error_msg), EtvaClusterPeer::_ERR_DEFAULT_CLUSTER_);
                $message = Etva::getLogMessage(array('name'=>$data['name'],'info'=>$cluster_message), EtvaNodePeer::_ERR_SOAPINIT_);
                sfContext::getInstance()->getEventDispatcher()->notify(
                    new sfEvent(sfConfig::get('config_acronym'),
                            'event.log',
                            array('message' =>$message,'priority'=>EtvaEventLogger::ERR)
                ));
    
                return $error;
            }
    

            $data['cluster_id'] = $default_cluster->getId();
            $form = new EtvaNodeForm();
            $uuid = EtvaNodePeer::generateUUID();
            $data['initialize'] = self::INITIALIZE_PENDING;

            if($etvamodel == 'standard') $data['initialize'] = self::INITIALIZE_OK;

            /*
             * calculate free mem
             */
            $etva_node = new EtvaNode();
            $etva_node->setMemtotal($data['memtotal']);
            $etva_node->updateMemFree();
            $etva_node->setClusterId($default_cluster->getId());
            $data['memfree'] = $etva_node->getMemfree();
            $data['uuid'] = $uuid;

        }

        $result = $this->processNodeForm($data, $form);

        if(!$result['success']) return $result;

        /*
         *
         * check if has restore to perform....
         */

        $apli = new Appliance();
        $action = $apli->getStage(Appliance::RESTORE_STAGE);
        if($action){
            $backup_url = $apli->get_backupconf_url(Appliance::VA_ARCHIVE_FILE,$uuid,'VA');

            if($backup_url)
            {
                $result['reset'] = 1;
                $result['backup_url'] = $backup_url;

                /*
                 * send pvs, vgs, lvs
                 */


                $node_devs = $etva_node->getEtvaNodePhysicalvolumesJoinEtvaPhysicalvolume();
                $devs_va = array();
                foreach($node_devs as $data)
                {
                    $dev = $data->getEtvaPhysicalvolume();
                    $devs_va[] = $dev->_VA();

                }
                $result['pvs'] = $devs_va;


                $node_vgs = $etva_node->getEtvaNodeVolumegroupsJoinEtvaVolumegroup();
                $vgs_va = array();
                foreach ($node_vgs as $data)
                {
                    $vg = $data->getEtvaVolumegroup();
                    $vgs_va[] = $vg->_VA();
                }
                $result['vgs'] = $vgs_va;


                $node_lvs = $etva_node->getEtvaNodeLogicalvolumesJoinEtvaLogicalvolume();
                $lvs_va = array();
                foreach ($node_lvs as $data)
                {
                    $lv = $data->getEtvaLogicalvolume();
                    $lvs_va[] = $lv->_VA();
                }
                $result['lvs'] = $lvs_va;

            }
            $apli->setStage(Appliance::RESTORE_STAGE,Appliance::VA_INIT);
        }
        
        return $result;

    }


    protected function processNodeForm($data, sfForm $form)
    {
        $uuid = $data['uuid'];
        $form->bind($data);
        if ($form->isValid())
        {
            $etva_node = $form->save();
            $uuid = $etva_node->getUuid();

            $result = array('success'=>true,'uuid'=>$uuid,'keepalive_update' => sfConfig::get('app_node_keepalive_update'));
            
            if($data['initialize'] == self::INITIALIZE_PENDING) $msg_type = EtvaNodePeer::_OK_SOAPREGISTER_;
            else $msg_type = EtvaNodePeer::_OK_SOAPINIT_;

            //notify system log
            $message = Etva::getLogMessage(
                array('name'=>$etva_node->getName(),
                      'uuid'=>$uuid,
                      'keepalive_update'=>$result['keepalive_update']), $msg_type);
            sfContext::getInstance()->getEventDispatcher()->notify(
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
                array('name'=>$data['name'],
                      'uuid'=>$uuid), EtvaNodePeer::_ERR_SOAPINIT_);
            sfContext::getInstance()->getEventDispatcher()->notify(
                new sfEvent(sfConfig::get('config_acronym'),
                        'event.log',
                        array('message' =>$message,'priority'=>EtvaEventLogger::ERR)
            ));

            return $result;
        }


    }
    
}
