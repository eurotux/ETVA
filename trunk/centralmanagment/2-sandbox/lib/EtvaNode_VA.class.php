<?php

class EtvaNode_VA
{
    private $etva_node;
    
    const INITIALIZE_OK = 'ok';
    const INITIALIZE = 'reinitialize';    
    const INITIALIZE_PENDING = 'pending';    
    const INITIALIZE_CMD_AUTHORIZE = 'authorize';    

    const SHUTDOWN = 'shutdown';
    const CHANGE_NAME = 'change_va_name_may_fork';
    const CHANGE_UUID = 'change_uuid';
    const CHANGE_IP = 'change_ip';
    const UMOUNT_ISOSDIR = 'umount_isosdir';

    const GET_DEVS = 'list_free_dev';
    const GET_PCI_DEVS = 'get_pci_devices';
    const GET_USB_DEVS = 'get_usb_devices';

    const GAS_INFO = 'refreshAllGAInfo_may_fork';

    const GET_STATE = 'getstate';

    const SYSTEMCHECK = 'systemCheck';

    const GET_SYS_INFO = 'getsysinfo';

    const CHECK_MDSTAT = 'check_mdstat';

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

    public function send_get_devs($dev_type)
    {
        $params = array();
        if(preg_match('/pci/i', $dev_type)){
            $method = self::GET_PCI_DEVS;
            $response = $this->etva_node->soapSend($method, $params);
        }elseif(preg_match('/usb/i', $dev_type)){
            $method = self::GET_USB_DEVS;
            $response = $this->etva_node->soapSend($method, $params);
        }else{
            $response = array('success' => false);
        }

        $result = $this->processGetDevsResponse($response, $method);
        return $result;
    }

    # refresh all servers guest agent info (if installed)
    # returns the name of the servers updated info
    public function send_get_gas_info(){
        $etva_node = $this->etva_node;
        
        $servers = $etva_node->getServersWithGA();

        $method = self::GAS_INFO;
        $params = array();

        foreach($servers as $s){
            $params[] = $s->getName();
        }

        $response = $etva_node->soapSend($method, array('vmnames' => $params));

        # updated list
        $res = array();

        # process the response
        # update Guest Agent State
        if($response['success']){
            $res_arr = (array) $response['response'];
            foreach($servers as $s){
                $obj = $res_arr[$s->getName()];
                if($obj->success == 'ok'){
                    $str = json_encode($obj->msg);
                    $s->setGaInfo($str);
                    if( $obj->msg ){
                        $s->setGaState(EtvaServerPeer::_GA_RUNNING_);
                    } else {
                        $s->setGaState(EtvaServerPeer::_GA_NOSTATE_);
                    }
                    $res[] = $s->getName();
                }else{
                    $s->setGaState(EtvaServerPeer::_GA_STOPPED_);
                }
                $s->save();
            }
        } else {    // if fail mark ga state as stop
            $msg = sprintf('Couldn\'t get guest agent info.');
            // add log message
            Etva::makeNotifyLogMessage($this->etva_node->getName(),$msg);

            foreach($servers as $s){
                $s->setGaState(EtvaServerPeer::_GA_STOPPED_);
                $s->save();
            }
        }

        return $res;
    }
    public function reset_gas_info(EtvaNode $node = null){
        //if( !$node ) $node = $this->etva_node;
        $servers = $node->getServersWithGA();

        foreach($servers as $s){
            $s->setGaState(EtvaServerPeer::_GA_STOPPED_);
            $s->save();
        }
        $msg = sprintf('Reset guest agent state for all servers.');
        // add log message
        Etva::makeNotifyLogMessage($node->getName(),$msg,array(),null,array(),EtvaEventLogger::INFO);
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

    public function send_shutdown(){
        $params = array();
        $method = self::SHUTDOWN;
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

    public function processGetDevsResponse($response, $method)
    {
        $etva_node = $this->etva_node;
        
        if($response['success'])
        {
            $response_decoded = (array) $response['response'];
            $returned_object = (array) $response_decoded['_obj_'];

            $etva_node->initData($returned_object);
            $node_name = $etva_node->getName();

#            $result = array('success'=>true, 'agent'=>$node_name, 'data'=>$response['response']);
            $result = array('success'=>true, 'agent'=>$node_name, 'data'=>$response['response']);
            return $result;

        }else
        {
            $result = $response;

            $message = Etva::getLogMessage(array('name'=>$etva_node->getName(),'info'=>$response['info']), EtvaNodePeer::_ERR_LIST_DEVICES_);

            $msg_i18n = sfContext::getInstance()->getI18N()->__(EtvaNodePeer::_ERR_LIST_DEVICES_,array('%name%'=>$etva_node->getName(),'%info%'=>$response['info']));
            $result['error'] = $msg_i18n;

            sfContext::getInstance()->getEventDispatcher()->notify(new sfEvent($response['agent'], 'event.log',array('message' => $message,'priority'=>EtvaEventLogger::ERR)));

            return  $result;
        }
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

        }else{
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

        $c = new Criteria();
        $c->add(EtvaNodePeer::UUID,$uuid);        
        $etva_node = EtvaNodePeer::doSelectOne($c);

        if($etva_node)
        {
            if( $etva_node->getState() < EtvaNode::NODE_INACTIVE ){
                // dont touch
                //$data['state'] = $etva_node->getState();
                $data['state'] = $this->calcState($etva_node, EtvaNode::NODE_ACTIVE);
            }
            
            if( !isset($data['isSpareNode']) ){
                $data['isSpareNode'] = $etva_node->getIssparenode() ? 1 : 0;
            }

            if( !isset($data['fencingconf']) ){
                $data['fencingconf'] = $etva_node->getFencingconf();
            }

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

        $this->clearLastMessage();

        $result = $this->processNodeForm($data, $form);

        if(!$result['success']) return $result;

        // reset guest agent info
        $this->reset_gas_info($etva_node);

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

            $result = array('success'=>true,'uuid'=>$uuid,'keepalive_update' => sfConfig::get('app_node_keepalive_update'),'state'=>$etva_node->getState());
            
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
    public function lookup_diskdevices($sharedonly){
        $this->etva_node->soapSend(EtvaPhysicalvolume_VA::LOOKUP_DISKDEVICES);
        return $this->get_sync_diskdevices(true,$sharedonly);
    }
    public function get_sync_diskdevices($force_sync=false, $sharedonly=false){
    
        $elements = array();

        $db_node_devs = EtvaPhysicalvolumeQuery::create()
                                ->useEtvaNodePhysicalVolumeQuery()
                                    ->filterByNodeId($this->etva_node->getId())
                                ->endUse()
                                ->useEtvaVolumePhysicalQuery('volphy','LEFT JOIN')
                                    ->useEtvaVolumegroupQuery('volgroup','LEFT JOIN')
                                    ->endUse()
                                ->endUse()
                                ->withColumn('volgroup.Vg','Vg')
                                ->find();

        $force_flag = ($force_sync) ? 1 : 0;
        $response_devs = $this->etva_node->soapSend(EtvaPhysicalvolume_VA::GET_SYNC_DISKDEVICES,array('force'=>$force_flag));
        if( $response_devs['success'] ){
            $devs = $response_devs['response'];

            foreach ($devs as $k=>$e){
                $dev = (array)$e;
                if( !$sharedonly || ($dev[EtvaPhysicalvolume::STORAGE_TYPE_MAP] != EtvaPhysicalvolume::STORAGE_TYPE_LOCAL_MAP) ){ 
                    $found = false;
                    foreach ($db_node_devs as $pv){
                        if( $dev[EtvaPhysicalvolume::STORAGE_TYPE_MAP] == $pv->getStorageType() ){
                            if( $pv->getUuid() && $dev[EtvaPhysicalvolume::UUID_MAP] ){
                                if( $pv->getUuid() == $dev[EtvaPhysicalvolume::UUID_MAP] ){
                                    $found = true;
                                }
                            } else {
                                if( $pv->getDevice() == $dev[EtvaPhysicalvolume::DEVICE_MAP] ) {
                                    $found = true;
                                }
                            }
                        }
                    }

                    $etva_pv = new EtvaPhysicalvolume();
                    $etva_pv->initData($dev);
                    $arr_e = $etva_pv->_VA();
                    $arr_e[EtvaPhysicalvolume::VG_MAP] = $dev[EtvaPhysicalvolume::VG_MAP];
                    $arr_e['registered'] = $found;
                    $elements[] = $arr_e;
                }
            }
        }

        foreach ($db_node_devs as $pv){
            if( !$sharedonly || ($pv->getStorageType() != EtvaPhysicalvolume::STORAGE_TYPE_LOCAL_MAP) ){ 
                $found = false;
                foreach ($devs as $k=>$e){
                    $dev = (array)$e;
                    if( $dev[EtvaPhysicalvolume::STORAGE_TYPE_MAP] == $pv->getStorageType() ){
                        if( $pv->getUuid() && $dev[EtvaPhysicalvolume::UUID_MAP] ){
                            if( $pv->getUuid() == $dev[EtvaPhysicalvolume::UUID_MAP] ){
                                $found = true;
                            }
                        } else {
                            if( $pv->getDevice() == $dev[EtvaPhysicalvolume::DEVICE_MAP] ){
                                $found = true;
                            }
                        }
                    }
                }

                if( !$found ){
                    $arr_e = $pv->_VA();
                    $arr_e['inconsistent'] = true;
                    $arr_e['registered'] = true;
                    $arr_e[EtvaPhysicalvolume::VG_MAP] = $pv->getVg();
                    $elements[] = $arr_e;
                }
            }
        }

        return $elements;
    }
    public function get_sync_volumegroups($force_sync=false, $sharedonly=false){
    
        $elements = array();

        $criteria = new Criteria();
        $criteria->add(EtvaNodeVolumegroupPeer::NODE_ID,$this->etva_node->getId());
        $db_node_vgs = EtvaNodeVolumegroupPeer::doSelectJoinEtvaVolumegroup($criteria);

        $force_flag = ($force_sync) ? 1 : 0;
        $response_vgpvs = $this->etva_node->soapSend(EtvaVolumegroup_VA::GET_SYNC_VOLUMEGROUPS,array('force'=>$force_flag));
        if( $response_vgpvs['success'] ){
            $vgpvs = $response_vgpvs['response'];

            foreach ($vgpvs as $k=>$e){
                $vgpv = (array)$e;
                if( !$sharedonly || ($vgpv[EtvaVolumegroup::STORAGE_TYPE_MAP] != EtvaVolumegroup::STORAGE_TYPE_LOCAL_MAP) ){ 
                    $found = false;
                    foreach ($db_node_vgs as $data){
                        $vg = $data->getEtvaVolumegroup();
                        if( $vgpv[EtvaVolumegroup::STORAGE_TYPE_MAP] == $vg->getStorageType() ){
                            if( $vg->getUuid() && $vgpv[EtvaVolumegroup::UUID_MAP] ){
                                if( $vg->getUuid() == $vgpv[EtvaVolumegroup::UUID_MAP] ){
                                    $found = true;
                                }
                            } else {
                                if( $vg->getVg() == $vgpv[EtvaVolumegroup::VG_MAP] ) {
                                    $found = true;
                                }
                            }
                        }
                    }

                    $etva_vg = new EtvaVolumegroup();
                    $etva_vg->initData($vgpv);
                    $arr_e = $etva_vg->_VA();
                    $arr_e['registered'] = $found;
                    $elements[] = $arr_e;
                }
            }
        }

        foreach ($db_node_vgpvs as $data){
            $vg = $data->getEtvaVolumegroup();
            if( !$sharedonly || ($vg->getStorageType() != EtvaVolumegroup::STORAGE_TYPE_LOCAL_MAP) ){ 
                $found = false;
                foreach ($vgpvs as $k=>$e){
                    $vgpv = (array)$e;
                    if( $vgpv[EtvaVolumegroup::STORAGE_TYPE_MAP] == $vg->getStorageType() ){
                        if( $vg->getUuid() && $vgpv[EtvaVolumegroup::UUID_MAP] ){
                            if( $vg->getUuid() == $vgpv[EtvaVolumegroup::UUID_MAP] ){
                                $found = true;
                            }
                        } else {
                            if( $vg->getVg() == $vgpv[EtvaVolumegroup::VG_MAP] ){
                                $found = true;
                            }
                        }
                    }
                }

                if( !$found ){
                    $arr_e = $vg->_VA();
                    $arr_e['inconsistent'] = true;
                    $arr_e['registered'] = true;
                    $elements[] = $arr_e;
                }
            }
        }

        return $elements;
    }
    public function get_sync_logicalvolumes($force_sync=false, $sharedonly=false){
    
        $elements = array();

        $criteria = new Criteria();
        $criteria->add(EtvaNodeLogicalvolumePeer::NODE_ID,$this->etva_node->getId());
        $db_node_lvs = EtvaNodeLogicalvolumePeer::doSelectJoinEtvaLogicalvolume($criteria);

        $force_flag = ($force_sync) ? 1 : 0;
        $response_lvs = $this->etva_node->soapSend(EtvaLogicalvolume_VA::GET_SYNC_LOGICALVOLUMES,array('force'=>$force_flag));
        if( $response_lvs['success'] ){
            $lvs = $response_lvs['response'];

            foreach ($lvs as $k=>$e){
                $lv_e = (array)$e;
                if( !$sharedonly || ($lv_e[EtvaLogicalvolume::STORAGE_TYPE_MAP] != EtvaLogicalvolume::STORAGE_TYPE_LOCAL_MAP) ){ 
                    $found = false;
                    foreach ($db_node_lvs as $data){
                        $lv = $data->getEtvaLogicalvolume();
                        if( $lv_e[EtvaLogicalvolume::STORAGE_TYPE_MAP] == $lv->getStorageType() ){
                            if( $lv->getUuid() && $lv_e[EtvaLogicalvolume::UUID_MAP] ){
                                if( $lv->getUuid() == $lv_e[EtvaLogicalvolume::UUID_MAP] ){
                                    $found = true;
                                }
                            } else {
                                if( $lv->getLvdevice() == $lv_e[EtvaLogicalvolume::LVDEVICE_MAP] ) {
                                    $found = true;
                                }
                            }
                        }
                    }

                    $etva_lv = new EtvaLogicalvolume();

                    $etva_volgroup = new EtvaVolumegroup();
                    $etva_volgroup->initData((array)$lv_e[EtvaLogicalvolume::VOLUMEGROUP_MAP]);
                    /*$vg_ar = $lv_e[EtvaLogicalvolume::VOLUMEGROUP_MAP];
                    $vg_type = $vg_ar[EtvaVolumegroup::STORAGE_TYPE_MAP];
                    $vg_uuid = $vg_ar[EtvaVolumegroup::UUID_MAP];
                    $vg_name = $vg_ar[EtvaVolumegroup::VG_MAP];
                    $etva_volgroup = EtvaVolumegroupPeer::retrieveByNodeTypeUUIDVg($this->etva_node->getId(), $vg_type, $vg_uuid, $vg_name);*/

                    $etva_lv->initData($lv_e);
                    $etva_lv->setEtvaVolumegroup($etva_volgroup);
                    $arr_e = $etva_lv->_VA();
                    $arr_e['registered'] = $found;
                    $elements[] = $arr_e;
                }
            }
        }

        foreach ($db_node_lvs as $data){
            $lv = $data->getEtvaLogicalvolume();
            if( !$sharedonly || ($lv->getStorageType() != EtvaLogicalvolume::STORAGE_TYPE_LOCAL_MAP) ){ 
                $found = false;
                foreach ($lvs as $k=>$e){
                    $lv_e = (array)$e;
                    if( $lv_e[EtvaLogicalvolume::STORAGE_TYPE_MAP] == $lv->getStorageType() ){
                        if( $lv->getUuid() && $lv_e[EtvaLogicalvolume::UUID_MAP] ){
                            if( $lv->getUuid() == $lv_e[EtvaLogicalvolume::UUID_MAP] ){
                                $found = true;
                            }
                        } else {
                            if( $lv->getLvdevice() == $lv_e[EtvaLogicalvolume::LVDEVICE_MAP] ){
                                $found = true;
                            }
                        }
                    }
                }

                if( !$found ){
                    $arr_e = $lv->_VA();
                    $arr_e['inconsistent'] = true;
                    $arr_e['registered'] = true;
                    $elements[] = $arr_e;
                }
            }
        }

        return $elements;
    }
    /*
     * checks node state
     * send soap request and update DB state
     * returns response from agent
     */
    public function checkState($failstate = EtvaNode::NODE_INACTIVE){

        $etva_node = $this->etva_node;

        $method = self::GET_STATE;
        //soapsend('method','params',forceRequest)
        $response = $etva_node->soapSend($method,null,true);
                        
        $success = $response['success'];
        
        $return = array();
        if(!$success){

            if( $etva_node->getState() < $failstate ){ // if in fail state and still fail
                // state doens't change
                $failstate = $etva_node->getState();
            }

            if( ($etva_node->getState() == EtvaNode::NODE_FAIL_UP) ){
                $failstate = EtvaNode::NODE_FAIL;
            } elseif( ($etva_node->getState() == EtvaNode::NODE_MAINTENANCE_UP) ){
                $failstate = EtvaNode::NODE_MAINTENANCE;
            }

            $etva_node->setState($failstate);
            $etva_node->save();

            //notify system log
            $message = sfContext::getInstance()->getI18N()->__(EtvaNodePeer::_ERR_SOAPSTATE_,array('%name%'=>$etva_node->getName(),'%info%'=>$response['info']));
            sfContext::getInstance()->getEventDispatcher()->notify(new sfEvent(sfConfig::get('config_acronym'), 'event.log', array('message' => $message,'priority'=>EtvaEventLogger::ERR)));

            $return = array('success'=>false,'agent'=>$etva_node->getName(),'error'=>$message,'info'=>$message);
        }else{            

            $ok_state = EtvaNode::NODE_ACTIVE;

            // if previous state is FAIL put node in Maintenance state
            if( ($etva_node->getState() == EtvaNode::NODE_FAIL) ||
                    ($etva_node->getState() == EtvaNode::NODE_FAIL_UP) ){
                $ok_state = EtvaNode::NODE_MAINTENANCE;
            } elseif( ($etva_node->getState() == EtvaNode::NODE_MAINTENANCE) ||
                        ($etva_node->getState() == EtvaNode::NODE_MAINTENANCE_UP) ){
                $ok_state = EtvaNode::NODE_MAINTENANCE_UP;
            }

            $etva_node->setState($ok_state);
            $etva_node->save();

            //notify system log
            $message = sfContext::getInstance()->getI18N()->__(EtvaNodePeer::_OK_SOAPSTATE_,array('%name%'=>$etva_node->getName()));
            sfContext::getInstance()->getEventDispatcher()->notify(new sfEvent(sfConfig::get('config_acronym'), 'event.log', array('message' => $message)));
            $return = array('success'=>true,'agent'=>$etva_node->getName(),'response'=>$message);
        }

        return $return;       

    }

    public function calcState( EtvaNode $etva_node, $newstate=EtvaNode::NODE_ACTIVE ){
        
        $res_state = $newstate;
        if( $newstate == EtvaNode::NODE_ACTIVE ){
            if( ($etva_node->getState() == EtvaNode::NODE_FAIL) ||
                    ($etva_node->getState() == EtvaNode::NODE_FAIL_UP) ){
                $res_state = EtvaNode::NODE_FAIL_UP;
            } elseif( ($etva_node->getState() == EtvaNode::NODE_MAINTENANCE) ||
                        ($etva_node->getState() == EtvaNode::NODE_MAINTENANCE_UP) ){
                $res_state = EtvaNode::NODE_MAINTENANCE_UP;
            } elseif( ($etva_node->getState() < EtvaNode::NODE_INACTIVE) ){
                $res_state = $etva_node->getState();
            }
        } else {
            if( ($etva_node->getState() == EtvaNode::NODE_FAIL) ||
                    ($etva_node->getState() == EtvaNode::NODE_FAIL_UP) ){
                $res_state = EtvaNode::NODE_FAIL;
            } elseif( ($etva_node->getState() == EtvaNode::NODE_MAINTENANCE) ||
                        ($etva_node->getState() == EtvaNode::NODE_MAINTENANCE_UP) ){
                $res_state = EtvaNode::NODE_MAINTENANCE;
            } elseif( ($etva_node->getState() < EtvaNode::NODE_INACTIVE) ){
                $res_state = $etva_node->getState();
            }
        }
        error_log(sprintf('calcState node=%s oldstate=%s newstate=%s',$etva_node->getName(),$etva_node->getState(),$res_state));
        return $res_state;
    }

    public function migrateAllServers(EtvaNode $etva_sparenode = null, $off = false, $ignoreAdmissionGate = false){
        // migrate all servers

        // order by priority HA
        $querysrvs = EtvaServerQuery::create();
        $querysrvs->orderByPriorityHa('desc');
        $etva_servers = $this->etva_node->getEtvaServers($querysrvs);
        foreach($etva_servers as $etva_server){
            $server_va = new EtvaServer_VA($etva_server);
            $response = array();
            $etva_tonode = $etva_sparenode;
            if( !$etva_tonode ){
                $list_nodes_toassign = $etva_server->listNodesAssignTo(true);
                if( count($list_nodes_toassign) )
                    $etva_tonode = $list_nodes_toassign[0];    // get first
            }
            if( $etva_tonode ){
                if( !$off && ($etva_server->getVmState() == 'running') ){
                    error_log("migrate server=".$etva_server->getName()." to node=".$etva_tonode->getName());
                    $response = $server_va->send_migrate($this->etva_node, $etva_tonode);
                } else {
                    error_log("move server=".$etva_server->getName()." to node=".$etva_tonode->getName());
                    $response = $server_va->send_move($this->etva_node, $etva_tonode);

                    // start it server is running or has autostart  or has HA or has priority HA
                    if( $off && (($etva_server->getVmState() == 'running') || $etva_server->getAutostart()) ){
                        // send start server
                        $start_res = $server_va->send_start($etva_tonode,null,$ignoreAdmissionGate);
                    }
                }
                if( $response['success'] ){
                    Etva::makeNotifyLogMessage($this->etva_node->getName(),sprintf('Server %s migrate ok',$etva_server->getName()),array(),null,array(),EtvaEventLogger::INFO);
                    error_log(sprintf('Server %s migrate ok',$etva_server->getName()));
                } else {
                    Etva::makeNotifyLogMessage($this->etva_node->getName(),sprintf('Server %s migrate nok',$etva_server->getName()));
                    error_log(sprintf('Server %s migrate nok',$etva_server->getName()));
                }
            } else {
                    Etva::makeNotifyLogMessage($this->etva_node->getName(),sprintf('Can\'t migrate server %s. No node free available.',$etva_server->getName()));
                    error_log(sprintf('Can\'t migrate server %s. No node free available.',$etva_server->getName()));
            }
        }
    }

    public function putMaintenance(EtvaNode $etva_sparenode=null, $off=false){

        if( !$etva_sparenode ||
                ($etva_sparenode->isNodeFree() && ($etva_sparenode->getState()==EtvaNode::NODE_ACTIVE)) ){
                //  if has sparenode, migrate all servers only if node is free and is active
                //      else do right distribution of servers for each node
            $this->migrateAllServers($etva_sparenode,$off);
        }

        // put node in maintenance
        $this->etva_node->setState(EtvaNode::NODE_MAINTENANCE);
        $this->etva_node->save();
    }
    public function systemCheck(){
        $etva_node = $this->etva_node;

        $method = self::SYSTEMCHECK;
        $response = $etva_node->soapSend($method,null,true);
                        
        $success = $response['success'];
        
        $return = array();
        if(!$success){
            //notify system log
            $message = Etva::makeNotifyLogMessage($etva_node->getName(),
                                                    EtvaNodePeer::_ERR_SYSTEMCHECK_,array('name'=>$etva_node->getName(),'info'=>$response['info']));
            $return = array('success'=>false,'agent'=>$etva_node->getName(),'error'=>$message,'info'=>$message);
            return $return;
        }

        $node_response = $etva_node->soapSend('getlvs_arr',array('force'=>1));
        if(!$node_response['success']){

            $errors = $node_response['error'];
            $etva_node->setErrorMessage(EtvaLogicalvolume_VA::LVINIT);
 
            $msg_i18n = Etva::makeNotifyLogMessage($etva_node->getName(),
                                                    EtvaLogicalvolumePeer::_ERR_INCONSISTENT_,array('info'=>$node_response['info']),
                                                    EtvaNodePeer::_ERR_SYSTEMCHECK_,array('name'=>$etva_node->getName()));
            $return = array('success'=>false, 'error'=>$msg_i18n, 'agent'=>$etva_node->getName());
            return  $return;
         }

        $dtable = array();
        $response_dtable = $etva_node->soapSend('device_table');
        if( !$response_dtable['success'] ){

            $errors = $response_dtable['error'];
            $etva_node->setErrorMessage(EtvaLogicalvolume_VA::LVINIT);
 
            $msg_i18n = Etva::makeNotifyLogMessage($etva_node->getName(),
                                                    EtvaLogicalvolumePeer::_ERR_INCONSISTENT_,array('info'=>$response_dtable['info']),
                                                    EtvaNodePeer::_ERR_SYSTEMCHECK_,array('name'=>$etva_node->getName()));
            $return = array('success'=>false, 'error'=>$msg_i18n, 'agent'=>$etva_node->getName());
            return  $return;
        }

        $lvs = (array)$node_response['response'];
        $dtable = (array)$response_dtable['response'];

        $etva_cluster = $etva_node->getEtvaCluster();
        $bulk_response_dtable = $etva_cluster->soapSend('device_table');


        $lv_va = new EtvaLogicalvolume_VA();
        $check_res = $lv_va->check_consistency($etva_node,$lvs,$dtable,$bulk_response_dtable);

        if( !$check_res['success'] ){
            $errors = $check_res['errors'];

            $etva_node->setErrorMessage(EtvaLogicalvolume_VA::LVINIT);

            $msg_i18n = Etva::makeNotifyLogMessage($etva_node->getName(),
                                                    EtvaLogicalvolumePeer::_ERR_INCONSISTENT_,array('info'=>print_r($errors, true)),
                                                    EtvaNodePeer::_ERR_SYSTEMCHECK_,array('name'=>$etva_node->getName()));
            $return = array('success'=>false, 'error'=>$msg_i18n, 'agent'=>$etva_node->getName(),'info'=>$msg_i18n);
            return  $return;
        }

        $etva_node->setState(EtvaNode::NODE_ACTIVE);
        $etva_node->save();

        //notify system log
        $message = Etva::makeNotifyLogMessage($etva_node->getName(),
                                                EtvaNodePeer::_OK_SYSTEMCHECK_,array('name'=>$etva_node->getName()),
                                                null,array(),EtvaEventLogger::INFO);
        $return = array('success'=>true,'agent'=>$etva_node->getName(),'response'=>$message);

        return $return;       
    }

    public function send_get_sys_info(){
        $method = self::GET_SYS_INFO;
        $response = $this->etva_node->soapSend($method);
        return $response;
    }

    private function clearLastMessage($response=null)
    {
        // if node is mark with error of check mdstat
        if( $this->etva_node->isLastErrorMessage(self::CHECK_MDSTAT) ){

            $message='';    // empty message

            if( $response ){
                $message = Etva::getLogMessage(array('name'=>$node_name,'info'=>$response['message']), EtvaNodePeer::_OK_CHECK_MDSTAT_ );
                sfContext::getInstance()->getEventDispatcher()->notify(new sfEvent($node_name, 'event.log',array('message' => $message,'priority'=>EtvaEventLogger::INFO)));

                error_log($message);
            }

            $apli = new Appliance();
            $apli->updateStatusMessage($message);

            $this->etva_node->clearErrorMessage(self::CHECK_MDSTAT);
        }
    }
    public function updateLastMessage($response)
    {
        $node_name = $this->etva_node->getName();

        if( !$response['success'] ){
            $message = Etva::getLogMessage(array('name'=>$node_name,'info'=>$response['message']), EtvaNodePeer::_ERR_CHECK_MDSTAT_ );
            sfContext::getInstance()->getEventDispatcher()->notify(new sfEvent($node_name, 'event.log',array('message' => $message,'priority'=>EtvaEventLogger::ERR)));
            $msg_i18n = sfContext::getInstance()->getI18N()->__(EtvaNodePeer::_ERR_CHECK_MDSTAT_,array('%name%'=>$node_name,'%info%'=>$response['message']));

            error_log($message);

            // send status to mastersite
            $apli = new Appliance();
            $apli->updateStatusMessage($message);

            // mark node with fail
            $this->etva_node->setErrorMessage(self::CHECK_MDSTAT,$msg_i18n);
        } else {
            $this->clearLastMessage($response);
        }
    }
}
