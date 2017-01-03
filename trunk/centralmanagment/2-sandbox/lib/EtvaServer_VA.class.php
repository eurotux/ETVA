<?php
/*
 * class to perform server operations with VA
 */
class EtvaServer_VA
{
    const SERVER_CREATE = 'create_vm';
    const SERVER_START = 'start_vm';
    const SERVER_STOP = 'vmStop';
    const SERVER_EDIT = 'reload_vm';
    const SERVER_REMOVE = 'vmDestroy';
    const SERVER_MIGRATE = 'migrate_vm';
    const SERVER_MOVE = 'move';
    const SERVER_GET = 'get_vm';
    const SERVER_GA_INFO = 'refreshGAInfo';
    const SERVER_PLONE_PACK = 'plone_pack_may_fork';
    const SERVER_LIST_SNAPSHOTS = 'vm_list_snapshots';
    const SERVER_CREATE_SNAPSHOT = 'vm_create_snapshot_may_fork';
    const SERVER_REVERT_SNAPSHOT = 'vm_revert_snapshot_may_fork';
    const SERVER_REMOVE_SNAPSHOT = 'vm_remove_snapshot_may_fork';
    const SERVER_SUSPEND = 'suspend_vm';
    const SERVER_RESUME = 'resume_vm';

    private $etva_server;
    private $collNetworks;
    private $collDisks;
    private $collDevices;
    private $disks_changed;
    private $networks_changed;

    public function EtvaServer_VA(EtvaServer $etva_server)
    {
        if($etva_server) $this->etva_server = $etva_server;
        $this->collNetworks = array();
        $this->collDisks = array();
    }


    public function send_remove(EtvaNode $etva_node = null, $keep_fs)
    {
        $method = self::SERVER_REMOVE;
        $etva_server = $this->etva_server;

        $params = array('uuid'=>$etva_server->getUuid(),'keep_fs' =>$keep_fs);
        
        if( !$etva_server->getUnassigned() ){
            $response = $etva_node->soapSend($method,$params);
            $result = $this->processRemoveResponse($etva_node,$response,$method,$keep_fs);
        } else {
            $etva_server->deleteServer(1);  // always keep fs

            $msg_i18n = sfContext::getInstance()->getI18N()->__(EtvaServerPeer::_OK_REMOVE_,array('%name%'=>$etva_server->getName(),'%info%'=>''));
            $result = array('success'=>true, 'response'=>$msg_i18n);
        }

        return $result;
    }

    // update remove Logical Volumes on each node
    private function updateRemovedLogicalVolumes(EtvaNode $etva_node, $server_lvs){
        $etva_server = $this->etva_server;

        $etva_cluster = $etva_node->getEtvaCluster();

        foreach( $server_lvs as $logicalvol ){
            // if logical volume is shared
            if( $logicalvol->getStorageType() != EtvaLogicalvolume::STORAGE_TYPE_LOCAL_MAP ){
                $lvdevice = $logicalvol->getLvdevice();
                // remove device in each node
                $etva_cluster->soapSend('device_remove',array('device'=>$lvdevice),$etva_node);
            }
        }

        $lv_va = new EtvaLogicalvolume_VA();
        $lv_va->send_update($etva_node);
    }

    private function removeLogicalVolumes(EtvaNode $etva_node, $server_lvs=null){
        if(!$server_lvs) $server_lvs = $this->etva_server->getEtvaLogicalvolumes();

        foreach( $server_lvs as $logicalvol ){
            $node = $etva_node;
            if( $logicalvol->getStorageType() != EtvaLogicalvolume::STORAGE_TYPE_LOCAL_MAP ){
                $node = EtvaNodePeer::ElectNode($etva_node);
            }

            $lvdevice = $logicalvol->getLvdevice();
            $etva_vg = $logicalvol->getEtvaVolumegroup();
            $vgname = $etva_vg->getVg();

            // send soap request
            $response = $node->soapSend(EtvaLogicalvolume_VA::LVREMOVE, array( 'lv' => $lvdevice, 'vg' => $vgname));
            if( !$response['success'] ){
                Etva::makeNotifyLogMessage($response['agent'],
                                                        EtvaLogicalvolumePeer::_ERR_REMOVE_, array('name'=>$logicalvol->getLv(),'info'=>$response['info']),
                                                        ServerPeer::_ERR_REMOVE_,array('%name%'=>$this->etva_server->getName()));
            }
        }
        
    }

    public function processRemoveResponse($etva_node,$response,$method, $keep_fs)
    {
        $etva_server = $this->etva_server;
        $server_name = $etva_server->getName();

        if(!$response['success']){

            $result = $response;
            $msg_i18n = sfContext::getInstance()->getI18N()->__(EtvaServerPeer::_ERR_REMOVE_,array('%name%'=>$server_name,'%info%'=>$response['info']));
            $result['error'] = $msg_i18n;

            //notify event log
            $message = Etva::getLogMessage(array('name'=>$server_name,'info'=>$response['info']), EtvaServerPeer::_ERR_REMOVE_);
            sfContext::getInstance()->getEventDispatcher()->notify(
                new sfEvent($response['agent'], 'event.log',
                    array('message' => $message,'priority'=>EtvaEventLogger::ERR)));

            return  $result;

        }

        $response_decoded = (array) $response['response'];
        $returned_status = $response_decoded['_okmsg_'];

        $server_lvs = $etva_server->getEtvaLogicalvolumes();
        $has_shared_lvs = $etva_server->hasSharedLogicalvolume();

        /*
         * if server is unassigned and choose not keep fs, need remove lvs by hand
         */
        if( !$keep_fs && $etva_server->getUnassigned() ){
            $this->removeLogicalVolumes($etva_node,$server_lvs);
        }

        switch($method){
            case self::SERVER_REMOVE :
                                        $etva_server->deleteServer($keep_fs);
                                        break;
            case self::SERVER_MOVE :
                                        break;
            default :
                                        break;

        }                    
        
        /*
         * if has an shared lv send bulk update to nodes...
         */
        if($has_shared_lvs && !$keep_fs){
            $this->updateRemovedLogicalVolumes($etva_node,$server_lvs);
        }

        $msg_i18n = sfContext::getInstance()->getI18N()->__(EtvaServerPeer::_OK_REMOVE_,array('%name%'=>$server_name,'%info%'=>$returned_status));

        $result = $response;
        $result['response'] = $msg_i18n;

        //notify event log
        $infos = array();
        foreach($server_lvs as $lv)
            $infos[] = Etva::getLogMessage(array('name'=>$lv->getLv()), $keep_fs ? EtvaLogicalvolumePeer::_OK_KEEP_ : EtvaLogicalvolumePeer::_OK_NOTKEEP_);

        $message = Etva::getLogMessage(array('name'=>$server_name,'info'=> implode($infos)), EtvaServerPeer::_OK_REMOVE_);
        sfContext::getInstance()->getEventDispatcher()->notify(
            new sfEvent($response['agent'], 'event.log',
                array('message' => $message)));


        return  $result;

    }

    public function send_create(EtvaNode $etva_node, $server_data)
    {
        $method = self::SERVER_CREATE;
        $etva_server = $this->etva_server;
        $etva_server->fromArray($server_data,BasePeer::TYPE_FIELDNAME);

        $check_nics_available = $this->check_nics_availability($etva_node, $server_data['networks'],$method);
        //error processing parameters
        if(!$check_nics_available['success']) return $check_nics_available;

        $check_disks_available = $this->check_disks_availability($etva_node, $server_data['disks'], $method);
        //error processing parameters
        if(!$check_disks_available['success']) return $check_disks_available;

        $criteria = new Criteria();
        $criteria->add(EtvaServerPeer::CLUSTER_ID,$etva_node->getClusterId());

        // If name changes
        $etva_server_aux = EtvaServerPeer::retrieveByName($server_data['name'],$criteria);
        if( $etva_server_aux ){

            $msg = Etva::getLogMessage(array('name'=>$server_data['name']), EtvaServerPeer::_ERR_EXIST_);
            $msg_i18n = sfContext::getInstance()->getI18N()->__(EtvaServerPeer::_ERR_EXIST_,array('%name%'=>$server_data['name']));

            $error = array('success'=>false,'agent'=>sfConfig::get('config_acronym'),'error'=>$msg_i18n,'info'=>$msg_i18n);

            //notify event log
            $message = Etva::getLogMessage(array('name'=>$etva_server->getName(),
                            'node'=>$etva_node->getName(),
                            'info'=>$msg), EtvaServerPeer::_ERR_CREATE_);

            sfContext::getInstance()->getEventDispatcher()->notify(
                new sfEvent(sfConfig::get('config_acronym'), 'event.log',
                    array('message' => $message,'priority'=>EtvaEventLogger::ERR)));

            return $error;
        }


        $this->buildServer($method);

        $params = $etva_server->_VA();

        $response = $etva_node->soapSend($method,$params);
        error_log("[INFO] Send_create params sended to server ".print_r($params, true));
        $result = $this->processCreateResponse($etva_node,$response);
        return $result;
    }

    public function processCreateResponse($etva_node,$response)
    {
        $etva_server = $this->etva_server;
        $server_name = $etva_server->getName();

        if(!$response['success']){

            $error_decoded = $response['error'];

            $result = $response;

            $msg_i18n = sfContext::getInstance()->getI18N()->__(EtvaServerPeer::_ERR_CREATE_,array('%name%'=>$server_name,'%info%'=>$error_decoded));
            $result['error'] = $msg_i18n;

            //notify event log
            $message = Etva::getLogMessage(array('name'=>$server_name,'info'=>$response['info']), EtvaServerPeer::_ERR_CREATE_);
            sfContext::getInstance()->getEventDispatcher()->notify(
                new sfEvent($response['agent'], 'event.log',
                    array('message' => $message,'priority'=>EtvaEventLogger::ERR)));

            return  $result;

        }

        $response_decoded = (array) $response['response'];
        $returned_status = $response_decoded['_okmsg_'];
        $returned_object = (array) $response_decoded['_obj_'];

        //update some data from agent response
        $etva_server->initData($returned_object);

        $etva_server->setEtvaCluster($etva_node->getEtvaCluster());
        //$etva_server->setEtvaNode($etva_node);
        $etva_server->save();

        $etva_server->assignTo($etva_node);

        $msg_i18n = sfContext::getInstance()->getI18N()->__(EtvaServerPeer::_OK_CREATE_,array('%name%'=>$server_name));
        $message = Etva::getLogMessage(array('name'=>$server_name), EtvaServerPeer::_OK_CREATE_);
        sfContext::getInstance()->getEventDispatcher()->notify(new sfEvent($etva_node->getName(), 'event.log', array('message' => $message)));

        $msg = $returned_status . ' ('.$server_name.')';

        $result = array('success'=>true,
                        'agent'=>$response['agent'],
                        'insert_id'=>$etva_server->getId(),
                        'response'=>$msg_i18n);

        return  $result;


    }


    public function send_edit(EtvaNode $etva_node, $orig_server, $server_data)
    {
        $method = self::SERVER_EDIT;
        $etva_server = $this->etva_server;

        $etva_server->fromArray($server_data,BasePeer::TYPE_FIELDNAME);        

        if( $orig_server->getName() !== $server_data['name'] ){
            $criteria = new Criteria();
            $criteria->add(EtvaServerPeer::CLUSTER_ID,$etva_node->getClusterId());

            // If name changes
            $etva_server_aux = EtvaServerPeer::retrieveByName($server_data['name'], $criteria);
            if( $etva_server_aux ){
                // check if exist one server with this name
                if( $etva_server->getUuid() !== $etva_server_aux->getUuid() ){

                    $msg = Etva::getLogMessage(array('name'=>$server_data['name']), EtvaServerPeer::_ERR_EXIST_);
                    $msg_i18n = sfContext::getInstance()->getI18N()->__(EtvaServerPeer::_ERR_EXIST_,array('%name%'=>$server_data['name']));

                    $error = array('success'=>false,'agent'=>sfConfig::get('config_acronym'),'error'=>$msg_i18n,'info'=>$msg_i18n);

                    //notify event log
                    $message = Etva::getLogMessage(array('name'=>$orig_server->getName(),
                                    'node'=>$etva_node->getName(),
                                    'info'=>$msg), EtvaServerPeer::_ERR_EDIT_);

                    sfContext::getInstance()->getEventDispatcher()->notify(
                        new sfEvent(sfConfig::get('config_acronym'), 'event.log',
                            array('message' => $message,'priority'=>EtvaEventLogger::ERR)));

                    return $error;
                }
            }
        }

        /*
         * process networks
         */
        $this->networks_changed = isset($server_data['networks']);

        if($this->networks_changed)
        {
            $check_nics_available = $this->check_nics_availability($etva_node, $server_data['networks'],$method);
            if(!$check_nics_available['success']) return $check_nics_available;

        }else
        {
            /*
             * clone networks and add to server copy
             */
            $networks = $orig_server->getEtvaNetworks();
            foreach($networks as $network) $etva_server->addEtvaNetwork($network);

        }

        /*
         * process devices
         */
        $devices_changed = isset($server_data['devices']);

        if($devices_changed)
        {
                // ensure if devices are not attached to other vms
                $devs_in_use = EtvaNodePeer::getDevicesInUse($etva_node, $etva_server);
                $srv_name = $etva_server->getName();
                
                foreach($server_data['devices'] as $k => $dev){
                         
                    // check if device is already attached
                    $testid = $dev['idvendor'].$dev['idproduct'].$dev['type'];
                    foreach($devs_in_use as $du){
                        if($du == $testid){
//                            $msg_i18n = $this->getContext()->getI18N()->__(EtvaNodePeer::_ERR_DEVICE_ATTACHED_,array('%dev%'=>$dev['description']));
                            $error = EtvaNodePeer::_ERR_DEVICE_ATTACHED_;
                            $response = array('success'=>False, 'error'=>$error, 'agent'=>$srv_name, 'dev'=>$dev['description']);
                            return $response;
                        }
                    }
                }

//            $check_nics_available = $this->check_nics_availability($etva_node, $server_data['devices'],$method);
//            if(!$check_nics_available['success']) return $check_nics_available;
//
            $check_devs_available = $this->check_devices_availability($etva_node, $server_data['devices'],$method);
            if(!$check_devs_available['success']) return $check_devs_available;
//            
        }else
        {
            /*
             * clone devices and add to server copy
             */
            $devices = $orig_server->getDevices();
            $etva_server->setDevices($devices);
        }

        /*
         * process disks
         */
        $this->disks_changed = isset($server_data['disks']);
        $disks = $server_data['disks'];

        if($this->disks_changed)
        {

            /*
             * create disks objects and add to server copy
             */
            $check_disks_available = $this->check_disks_availability($etva_node, $server_data['disks'], $method);

            //error processing parameters
            if(!$check_disks_available['success']) return $check_disks_available;
        }
        else
        {
            /*
             * clone disks and add to server copy
             */
            $disks = $orig_server->getEtvaServerLogicals();
            foreach($disks as $disk){
                $etva_server->addEtvaServerLogical($disk);
            }
        }

        $this->buildServer($method);

        $params = $etva_server->_VA();
        if( $etva_server->getVmState() == 'running' )
            $params['live'] = 1;

        # if don't has snapshots and try to change name, send force_to_change_name= flag
        if( !$orig_server->getHassnapshots() && ($orig_server->getName() != $etva_server->getName()) )
            $params['force_to_change_name'] = 1;

        if( !$etva_server->getUnassigned() )
            $response = $etva_node->soapSend($method,$params);
        else
            $response = array('success'=>true);

        $result = $this->processEditResponse($etva_node,$orig_server,$response);
        return $result;

    }

    public function processEditResponse($etva_node,$orig_server,$response)
    {
        $etva_server = $this->etva_server;
        $server_name = $etva_server->getName();

        if(!$response['success']){

            $result = $response;
            $msg_i18n = Etva::makeNotifyLogMessage($response['agent'],
                                                        $response['info'],array(),
                                                        EtvaServerPeer::_ERR_EDIT_,array('name'=>$server_name));
            $result['error'] = $msg_i18n;
            return $result;
        }

        /*
         * if all went ok make changes to DB....
         */

        /* remove server networks if networks have changed */
        if($this->networks_changed){
            $orig_server->deleteNetworks();
            error_log("DELETING NETWORKS");
        }

        // if disk data has been changed removed old disks references and keep lvs,
        if($this->disks_changed){
            $orig_server->deleteDisks(true);               
        }

        // get some info from response...
        $response_decoded = (array) $response['response'];
        $returned_status = $response_decoded['_okmsg_'];
        $returned_object = (array) $response_decoded['_obj_'];

        //update some server data from agent response
        $etva_server->initData($returned_object);

        $etva_server->setNew(false);        
        $etva_server->save();

        //update node mem available
        if ($etva_server->isRunning()) {
            $mult = 1;
            $cur_avail = $etva_node->getMemfree();        
            if($etva_server->getMem()<$orig_server->getMem()) $mult = -1;

            $server_mem_diffMB = abs($etva_server->getMem() - $orig_server->getMem());
            $server_mem_diff = Etva::MB_to_Byteconvert($server_mem_diffMB);
            $cur_free = $cur_avail - ($server_mem_diff*$mult);

            $etva_node->setMemfree($cur_free);
            $etva_node->save();
        }

        $message = Etva::getLogMessage(array('name'=>$server_name), EtvaServerPeer::_OK_EDIT_);
        $msg_i18n = sfContext::getInstance()->getI18N()->__(EtvaServerPeer::_OK_EDIT_,array('%name%'=>$server_name));

        sfContext::getInstance()->getEventDispatcher()->notify(new sfEvent($etva_node->getName(), 'event.log', array('message' => $message)));

        $result = $response;
        $result['response'] = $msg_i18n;
        return  $result;


    }



    public function buildServer($method)
    {
        $etva_server = $this->etva_server;

        if($method==self::SERVER_CREATE)
        {

            $etva_server->setUuid(EtvaServerPeer::generateUUID());

            $vnc_keymap = EtvaSettingPeer::retrieveByPk('vnc_keymap');
            $etva_server->setVncKeymap($vnc_keymap->getValue());

            $user_groups = sfContext::getInstance()->getUser()->getGroups();
            $server_sfgroup = array_shift($user_groups);

            //if user has group then put one of them otherwise put DEFAULT GROUP ID
            if($server_sfgroup) $etva_server->setsfGuardGroup($server_sfgroup);
            else $etva_server->setsfGuardGroup(sfGuardGroupPeer::getDefaultGroup());

        }


        foreach($this->collNetworks as $coll) $etva_server->addEtvaNetwork($coll);
        foreach($this->collDisks as $coll){
            $etva_server->addEtvaServerLogical($coll);        
        }

        if( isset($this->collDevices) ){
            $str = json_encode($this->collDevices);
            $etva_server->setDevices($str);
        }
    }


    public function check_disks_availability($etva_node, $disks, $method)
    {

        $etva_server = $this->etva_server;

        $collDisks = array();
        $i = 0;
        foreach($disks as $disk){

            if(!$etva_lv = EtvaLogicalvolumePeer::retrieveByPK($disk['id'])){

                $msg = Etva::getLogMessage(array('id'=>$disk['id']), EtvaLogicalvolumePeer::_ERR_NOTFOUND_ID_);
                $msg_i18n = sfContext::getInstance()->getI18N()->__(EtvaLogicalvolumePeer::_ERR_NOTFOUND_ID_,array('%id%'=>$disk['id']));

                $error = array('success'=>false,'agent'=>$etva_node->getName(),'error'=>$msg_i18n);

                //notify event log
                $message = Etva::getLogMessage(array('name'=>$etva_server->getName(),'info'=>$msg), EtvaServerPeer::_ERR_CREATE_);
                sfContext::getInstance()->getEventDispatcher()->notify(
                    new sfEvent($error['agent'], 'event.log',
                        array('message' => $message,'priority'=>EtvaEventLogger::ERR)));

                return $error;
            }
            
            $lv = $etva_lv->getLv();

            /*
             * check if is not system lv
             */
            if($etva_lv->getMounted()){

                $msg = Etva::getLogMessage(array('name'=>$lv), EtvaLogicalvolumePeer::_ERR_SYSTEM_LV_);
                $msg_i18n = sfContext::getInstance()->getI18N()->__(EtvaLogicalvolumePeer::_ERR_SYSTEM_LV_,array('%name%'=>$lv));

                $error = array('success'=>false,'agent'=>$etva_node->getName(),'error'=>$msg_i18n,'info'=>$msg_i18n);

                //notify system log
                $message = Etva::getLogMessage(array('name'=>$etva_server->getName(),'info'=>$msg), EtvaServerPeer::_ERR_CREATE_);
                sfContext::getInstance()->getEventDispatcher()->notify(new sfEvent($etva_node->getName(), 'event.log',array('message' => $message,'priority'=>EtvaEventLogger::ERR)));

                return $error;
            }

            //check if lv already marked as 'in use'
            if($method==self::SERVER_CREATE && $etva_lv->getInUse()){
                $lv_server = $etva_lv->getEtvaServer();
                $msg = Etva::getLogMessage(array('name'=>$lv,'server'=>$lv_server->getName()), EtvaLogicalvolumePeer::_ERR_INUSE_);
                $msg_i18n = sfContext::getInstance()->getI18N()->__(EtvaLogicalvolumePeer::_ERR_INUSE_,array('%name%'=>$lv,'%server%'=>$lv_server->getName()));

                $error = array('success'=>false,'agent'=>$etva_node->getName(),'error'=>$msg_i18n,'info'=>$msg_i18n);

                //notify event log
                $message = Etva::getLogMessage(array('name'=>$etva_server->getName(),'info'=>$msg), EtvaServerPeer::_ERR_CREATE_);
                sfContext::getInstance()->getEventDispatcher()->notify(
                    new sfEvent($error['agent'], 'event.log',
                        array('message' => $message,'priority'=>EtvaEventLogger::ERR)));

                return $error;
            }
            
            $etva_sl = new EtvaServerLogical();
            $etva_sl->setLogicalvolumeId($etva_lv->getId());
            $etva_sl->setDiskType($disk['disk_type']);
            $etva_sl->setBootDisk($i);
            $i++;
            $collDisks[] = $etva_sl;


        }// end each disk

        $this->collDisks = $collDisks;

        return array('success'=>true);


    }


    /**
      * Devices already decoded
      */
    public function check_devices_availability($etva_node, $devices, $method)
    {
        $etva_server = $this->etva_server;
        $this->collDevices = $devices;
        return array('success'=>true);
    }
    public function check_nics_availability($etva_node, $networks, $method)
    {
        $etva_server = $this->etva_server;

        $collNetworks = array();

        // check if networks are available
        foreach ($networks as $network){

            $etva_vlan = EtvaVlanPeer::retrieveByPk($network['vlan_id']);
            $etva_mac = EtvaMacPeer::retrieveByMac($network['mac']);


            if(!$etva_mac || !$etva_vlan){

                $msg = Etva::getLogMessage(array(), EtvaNetworkPeer::_ERR_);
                $msg_i18n = sfContext::getInstance()->getI18N()->__(EtvaNetworkPeer::_ERR_,array());

                $error = array('success'=>false,'agent'=>$etva_node->getName(),'error'=>$msg_i18n);

                //notify event log
                $message = Etva::getLogMessage(array('name'=>$etva_server->getName(),'info'=>$msg), EtvaServerPeer::_ERR_CREATE_);
                sfContext::getInstance()->getEventDispatcher()->notify(
                    new sfEvent($error['agent'], 'event.log',
                        array('message' => $message,'priority'=>EtvaEventLogger::ERR)));

                return $error;

            }

            if($method==self::SERVER_CREATE && $etva_mac->getInUse()){

                $msg = Etva::getLogMessage(array('name'=>$etva_mac->getMac()), EtvaMacPeer::_ERR_ASSIGNED_);
                $msg_i18n = sfContext::getInstance()->getI18N()->__(EtvaMacPeer::_ERR_ASSIGNED_,array('%name%'=>$etva_mac->getMac()));

                $error = array('success'=>false,'agent'=>$etva_node->getName(),'info'=>$msg_i18n,'error'=>$msg_i18n);

                //notify event log
                $message = Etva::getLogMessage(array('name'=>$etva_server->getName(),'info'=>$msg), EtvaServerPeer::_ERR_CREATE_);
                sfContext::getInstance()->getEventDispatcher()->notify(
                    new sfEvent($error['agent'], 'event.log',
                        array('message' => $message,'priority'=>EtvaEventLogger::ERR)));

                return $error;

            }

            $etva_network = new EtvaNetwork();
            $etva_network->fromArray($network,BasePeer::TYPE_FIELDNAME);
            $collNetworks[] = $etva_network;
        }
        $this->collNetworks = $collNetworks;

        return array('success'=>true);

    }


    /*
     * send migrate
     */
    public function send_migrate(EtvaNode $from_etva_node, EtvaNode $to_etva_node)
    {
        $method = self::SERVER_MIGRATE;

        $params = array(
                    'daddr'=>$to_etva_node->getIp(),
                    'dagentname'=>$to_etva_node->getName(),
                    'uuid'=>$this->etva_server->getUuid(),
                    'live'=>true);  // do migrate in live mode

        $preCond = $this->preSend($method, $from_etva_node, $to_etva_node);

        if(!$preCond['success']) return $preCond;

        // mark state as migrating...
        $etva_server = $this->etva_server;
        $bkp_server_state = $etva_server->getVmState();
        $etva_server->setVmState(EtvaServer::STATE_MIGRATING);

        $etva_server->save();

        $response = $from_etva_node->soapSend($method,$params);
        $result = $this->processMigrateResponse($from_etva_node, $to_etva_node, $response, $method);

        if($result['success'])
        {
            // update free memory on source
            $cur_avail = $from_etva_node->getMemfree();
            $cur_free = $cur_avail + Etva::MB_to_Byteconvert($this->etva_server->getMem());
            $from_etva_node->setMemfree($cur_free);
            $from_etva_node->save();
        } else { // if not success
            // rollback state 
            // TODO improve this...
            $etva_server->setVmState($bkp_server_state);
            $etva_server->save();
        }
        return $result;
    }

    /*
     * send move
     */
    public function send_move(EtvaNode $from_etva_node, EtvaNode $to_etva_node)
    {
        $method = self::SERVER_MOVE;

        $preCond = $this->preSend($method, $from_etva_node, $to_etva_node);

        if(!$preCond['success']) return $preCond;

        $params = $this->etva_server->_VA();


        $response = $to_etva_node->soapSend(self::SERVER_CREATE,$params);
        $result = $this->processMigrateResponse($from_etva_node, $to_etva_node, $response, $method);

        if($result['success'])
        {
            // remove vm from source

            $params = array('uuid'=>$this->etva_server->getUuid(),'keep_fs' =>1);
            $response = $from_etva_node->soapSend(self::SERVER_REMOVE,$params);
            $response_rm = $this->processRemoveResponse($from_etva_node,$response,$method,$params['keep_fs']);

            if(!$response_rm['success']){

                
                $error_decoded = $response_rm['info'];

                $msg_i18n = sfContext::getInstance()->getI18N()->__(EtvaServerPeer::_ERR_MOVE_,array('%name%'=>$this->etva_server->getName(),
                                    '%from%'=>$from_etva_node->getName(),'%to%'=>$to_etva_node->getName(),'%info%'=>$error_decoded));
                $response_rm['error'] = $msg_i18n;

                $message = Etva::getLogMessage(array('name'=>$this->etva_server->getName(),
                            'from'=>$from_etva_node->getName(),
                            'to'=>$to_etva_node->getName(),
                            'info'=>$response_rm['info']), EtvaServerPeer::_ERR_MOVE_);

                sfContext::getInstance()->getEventDispatcher()->notify(new sfEvent($response['agent'], 'event.log',array('message' => $message,'priority'=>EtvaEventLogger::ERR)));

            }
            else return $result;

        }

        return $result;
    }

    public function haveMemoryAvailable($method,$to_etva_node){

        $etva_server = $this->etva_server;

        /*
         * check server memory
         *     if method is migrate check free memory other else check max allocatable memory
         */
        $to_mem_available = ( $method == self::SERVER_MIGRATE ) ? $to_etva_node->getMemfree() : $to_etva_node->getMaxMem();

        $server_mem = $etva_server->getMem();
        $server_memBytes = Etva::MB_to_Byteconvert($server_mem);
        if($to_mem_available < $server_memBytes){

            $no_mem_msg = Etva::getLogMessage(array('name' => $to_etva_node->getName(), 'info' => $server_mem ), EtvaNodePeer::_ERR_MEM_AVAILABLE_);
            $no_mem_msg_i18n = sfContext::getInstance()->getI18N()->__(EtvaNodePeer::_ERR_MEM_AVAILABLE_,array('%name%'=>$to_etva_node->getName(),
                                    '%info%'=>$server_mem));


            $msg_i18n = sfContext::getInstance()->getI18N()->__($err_op,array('%name%'=>$etva_server->getName(),
                                    '%node%'=>$to_etva_node->getName(),'%info%'=>$no_mem_msg_i18n));

            $error = array('success'=>false,'agent'=>sfConfig::get('config_acronym'),'error'=>$msg_i18n,'info'=>$msg_i18n);

            $message = Etva::getLogMessage(array('name'=>$etva_server->getName(),
                            'node'=>$to_etva_node->getName(),
                            'info'=>$no_mem_msg), $err);

            sfContext::getInstance()->getEventDispatcher()->notify(
                new sfEvent(sfConfig::get('config_acronym'), 'event.log',
                    array('message' => $message,'priority'=>EtvaEventLogger::ERR)));

            return $error;
        }
        
        $result = array('success'=>true);
        return $result;
    }

    public function haveAllSharedLogicalvolumes($method,$etva_node){
        $etva_server = $this->etva_server;

        $disks_shared = $etva_server->isAllSharedLogicalvolumes();
        if(!$disks_shared)
        {

            $msg_i18n = sfContext::getInstance()->getI18N()->__($err_op,array('%name%'=>$etva_server->getName(),
                                    '%node%'=>$etva_node->getName(),'%info%'=>EtvaLogicalvolumePeer::_NOTALLSHARED_));

            $error = array('success'=>false,'agent'=>sfConfig::get('config_acronym'),'error'=>$msg_i18n,'info'=>$msg_i18n);

            $message = Etva::getLogMessage(array('name'=>$etva_server->getName(),
                            'node'=>$etva_node->getName(),
                            'info'=>EtvaLogicalvolumePeer::_NOTALLSHARED_), $err);

            sfContext::getInstance()->getEventDispatcher()->notify(
                new sfEvent(sfConfig::get('config_acronym'), 'event.log',
                    array('message' => $message,'priority'=>EtvaEventLogger::ERR)));

            return $error;

        }
        $result = array('success'=>true);
        return $result;
    }
    public function canAssignToNode($method,$etva_node){
        $etva_server = $this->etva_server;

        if(!$etva_server->canAssignTo($etva_node)){

            $msg_i18n = sfContext::getInstance()->getI18N()->__($err_op,array('%name%'=>$etva_server->getName(),
                                    '%node%'=>$etva_node->getName(),'%info%'=>EtvaServerPeer::_CANTASSIGNTO_));

            $error = array('success'=>false,'agent'=>sfConfig::get('config_acronym'),'error'=>$msg_i18n,'info'=>$msg_i18n);

            $message = Etva::getLogMessage(array('name'=>$etva_server->getName(),
                            'node'=>$etva_node->getName(),
                            'info'=>EtvaServerPeer::_CANTASSIGNTO_), $err);

            sfContext::getInstance()->getEventDispatcher()->notify(
                new sfEvent(sfConfig::get('config_acronym'), 'event.log',
                    array('message' => $message,'priority'=>EtvaEventLogger::ERR)));

            return $error;

        }
        $result = array('success'=>true);
        return $result;
    }

    public function preSend($method,$from_etva_node,$to_etva_node)
    {
        $etva_server = $this->etva_server;

        switch($method){
            case self::SERVER_MIGRATE :
                                    $msg_ok_type = EtvaLogicalvolumePeer::_OK_REMOVE_;
                                    $msg_err_type = EtvaLogicalvolumePeer::_ERR_REMOVE_;
                                    $err_cond = EtvaServerPeer::_ERR_MIGRATE_FROMTO_COND_;
                                    $err_op = EtvaServerPeer::_ERR_MIGRATE_FROMTO_;
                                    $err = EtvaServerPeer::_ERR_MIGRATE_;
                                    break;
            case self::SERVER_MOVE :
                                    $msg_ok_type = EtvaLogicalvolumePeer::_OK_REMOVE_;
                                    $msg_err_type = EtvaLogicalvolumePeer::_ERR_REMOVE_;
                                    $err_cond = EtvaServerPeer::_ERR_MOVE_FROMTO_COND_;
                                    $err_op = EtvaServerPeer::_ERR_MOVE_FROMTO_;
                                    $err = EtvaServerPeer::_ERR_MOVE_;
                                    break;
        }

        if(($from_etva_node->getId() == $to_etva_node->getId()) || ($from_etva_node->getClusterId() != $to_etva_node->getClusterId()) ){

            $msg = Etva::getLogMessage(array('name'=>$etva_server->getName()), $err_cond);
            $msg_i18n = sfContext::getInstance()->getI18N()->__($err_cond,array('%name%'=>$etva_server->getName()));

            $error = array('success'=>false,'agent'=>sfConfig::get('config_acronym'),'error'=>$msg_i18n,'info'=>$msg_i18n);

            //notify event log
            $message = Etva::getLogMessage(array('name'=>$etva_server->getName(),'info'=>$msg), $err );
            sfContext::getInstance()->getEventDispatcher()->notify(
                new sfEvent(sfConfig::get('config_acronym'), 'event.log',
                    array('message' => $message,'priority'=>EtvaEventLogger::ERR)));

            return $error;


        }

        // check server name collision on destination cluster
        $server_name = $etva_server->getName();

        $criteria = new Criteria();
        $criteria->add(EtvaServerPeer::CLUSTER_ID,$to_etva_node->getClusterId());

        $etva_server_aux = EtvaServerPeer::retrieveByName($server_name, $criteria);
        if( $etva_server_aux ){
            // check if exist one server with this name
            if( $etva_server->getUuid() !== $etva_server_aux->getUuid() ){

                $msg = Etva::getLogMessage(array('name'=>$server_name), EtvaServerPeer::_ERR_EXIST_);
                $msg_i18n = sfContext::getInstance()->getI18N()->__(EtvaServerPeer::_ERR_EXIST_,array('%name%'=>$server_name));

                $error = array('success'=>false,'agent'=>sfConfig::get('config_acronym'),'error'=>$msg_i18n,'info'=>$msg_i18n);

                //notify event log
                $message = Etva::getLogMessage(array('name'=>$etva_server->getName(), 'info'=>$msg), $err);

                sfContext::getInstance()->getEventDispatcher()->notify(
                    new sfEvent(sfConfig::get('config_acronym'), 'event.log',
                        array('message' => $message,'priority'=>EtvaEventLogger::ERR)));

                return $error;
            }
        }

        /*
         * check server memory
         *     if method is migrate check free memory other else check max allocatable memory
         */
        $to_mem_available = ( $method == self::SERVER_MIGRATE ) ? $to_etva_node->getMemfree() : $to_etva_node->getMaxMem();

        $server_mem = $etva_server->getMem();
        $server_memBytes = Etva::MB_to_Byteconvert($server_mem);
        if($to_mem_available < $server_memBytes){

            $no_mem_msg = Etva::getLogMessage(array('name' => $to_etva_node->getName(), 'info' => $server_mem ), EtvaNodePeer::_ERR_MEM_AVAILABLE_);
            $no_mem_msg_i18n = sfContext::getInstance()->getI18N()->__(EtvaNodePeer::_ERR_MEM_AVAILABLE_,array('%name%'=>$to_etva_node->getName(),
                                    '%info%'=>$server_mem));


            $msg_i18n = sfContext::getInstance()->getI18N()->__($err_op,array('%name%'=>$etva_server->getName(),
                                    '%from%'=>$from_etva_node->getName(),'%to%'=>$to_etva_node->getName(),'%info%'=>$no_mem_msg_i18n));

            $error = array('success'=>false,'agent'=>sfConfig::get('config_acronym'),'error'=>$msg_i18n,'info'=>$msg_i18n);

            $message = Etva::getLogMessage(array('name'=>$etva_server->getName(),
                            'from'=>$from_etva_node->getName(),
                            'to'=>$to_etva_node->getName(),
                            'info'=>$no_mem_msg), $err);

            sfContext::getInstance()->getEventDispatcher()->notify(
                new sfEvent(sfConfig::get('config_acronym'), 'event.log',
                    array('message' => $message,'priority'=>EtvaEventLogger::ERR)));

            return $error;
        }
        

        $disks_shared = $etva_server->isAllSharedLogicalvolumes();
        if(!$disks_shared)
        {

            $msg_i18n = sfContext::getInstance()->getI18N()->__($err_op,array('%name%'=>$etva_server->getName(),
                                    '%from%'=>$from_etva_node->getName(),'%to%'=>$to_etva_node->getName(),'%info%'=>EtvaLogicalvolumePeer::_NOTALLSHARED_));

            $error = array('success'=>false,'agent'=>sfConfig::get('config_acronym'),'error'=>$msg_i18n,'info'=>$msg_i18n);

            $message = Etva::getLogMessage(array('name'=>$etva_server->getName(),
                            'from'=>$from_etva_node->getName(),
                            'to'=>$to_etva_node->getName(),
                            'info'=>EtvaLogicalvolumePeer::_NOTALLSHARED_), $err);

            sfContext::getInstance()->getEventDispatcher()->notify(
                new sfEvent(sfConfig::get('config_acronym'), 'event.log',
                    array('message' => $message,'priority'=>EtvaEventLogger::ERR)));

            return $error;

        }

        return array('success'=>true);


    }







    /*
     * process response
     */
    public function processMigrateResponse($from_etva_node, $to_etva_node, $response, $method)
    {
        $etva_server = $this->etva_server;


        switch($method){
            case self::SERVER_MIGRATE :
                                    $msg_ok_type = EtvaServerPeer::_OK_MIGRATE_FROMTO_;
                                    $err_op = EtvaServerPeer::_ERR_MIGRATE_FROMTO_;
                                    break;
            case self::SERVER_MOVE :
                                    $msg_ok_type = EtvaServerPeer::_OK_MOVE_FROMTO_;
                                    $err_op = EtvaServerPeer::_ERR_MOVE_FROMTO_;
                                    break;
        }

        if(!$response['success']){

            $error_decoded = $response['error'];

            $result = $response;

            $msg_i18n = sfContext::getInstance()->getI18N()->__($err_op,array('%name%'=>$etva_server->getName(),
                                    '%from%'=>$from_etva_node->getName(),'%to%'=>$to_etva_node->getName(),'%info%'=>$error_decoded));
            $result['error'] = $msg_i18n;

            $message = Etva::getLogMessage(array('name'=>$etva_server->getName(),
                            'from'=>$from_etva_node->getName(),
                            'to'=>$to_etva_node->getName(),
                            'info'=>$response['info']), $err_op);

            sfContext::getInstance()->getEventDispatcher()->notify(new sfEvent($response['agent'], 'event.log',array('message' => $message,'priority'=>EtvaEventLogger::ERR)));


            return  $result;

        }

        $update_node_server = $this->reloadVm($to_etva_node);

        if($update_node_server['success'])
        {

            $msg_i18n = sfContext::getInstance()->getI18N()->__($msg_ok_type,array('%name%'=>$etva_server->getName(),
                                    '%from%'=>$from_etva_node->getName(),'%to%'=>$to_etva_node->getName(),'%info%'=>$returned_status));

            $result = array('success'=>true,'agent'=>$response['agent'],'response'=>$msg_i18n);

            //notify event log
            $message = Etva::getLogMessage(array('name'=>$etva_server->getName(),
                            'from'=>$from_etva_node->getName(),
                            'to'=>$to_etva_node->getName(),
                            'info'=>$response['info']), $msg_ok_type);

            sfContext::getInstance()->getEventDispatcher()->notify(new sfEvent($response['agent'], 'event.log',array('message' => $message)));
        }

        return $update_node_server;

    }

    public function plonePack(EtvaNode $etva_node){
        $etva_server = $this->etva_server;
        $method = self::SERVER_PLONE_PACK;
        $params = array('vmname' => $etva_server->getName());

        $response = $etva_node->soapSend($method, $params);
    
        error_log("PLONE PACK RESPONSE");
        error_log(print_r($response, true));

        if(!$response['success']){
            #TODO: implement

            $error_decoded = $response['error'];
            $result = $response;
            $msg_i18n = sfContext::getInstance()->getI18N()->__(EtvaServerPeer::_ERR_RELOAD_,array('%name%'=>$etva_server->getName(),
                                    '%node%'=>$etva_node->getName(),'%info%'=>$error_decoded));
            $result['error'] = $msg_i18n;
            $message = Etva::getLogMessage(array('name'=>$etva_server->getName(),
                            'node'=>$etva_node->getName(),
                            'info'=>$response['info']), EtvaServerPeer::_ERR_RELOAD_);

            sfContext::getInstance()->getEventDispatcher()->notify(new sfEvent($response['agent'], 'event.log',array('message' => $message,'priority'=>EtvaEventLogger::ERR)));
            return $result;
        }
        
        $returned_object = (array) $response['response'];

        if($returned_object['success'] == 'ok'){
            error_log("GA request well succeeded");
            $str = json_encode($returned_object['msg']);
            $etva_server->setGaState(EtvaServerPeer::_GA_RUNNING_);
            $etva_server->setGaInfo($str);
            $etva_server->save();
        }else{
            #TODO: treat error 
            error_log("GA request had no success");
            $etva_server->setGaState(EtvaServerPeer::_GA_STOPPED_);
        }

        return $response;
    }   

    public function getGAInfo(EtvaNode $etva_node){
        $etva_server = $this->etva_server;
        $method = self::SERVER_GA_INFO;
        $params = array('vmname' => $etva_server->getName());

        $response = $etva_node->soapSend($method, $params);
        if(!$response['success']){
            #TODO: implement

            $error_decoded = $response['error'];
            $result = $response;

            $msg_i18n = Etva::makeNotifyLogMessage($response['agent'],
                                                        EtvaServerPeer::_ERR_GAUPDATE_, array('name'=>$etva_server->getName(),'info'=>$error_decoded));
            $result['error'] = $msg_i18n;
            return $result;
        }
        
        $returned_object = (array) $response['response'];

        if($returned_object['success'] == 'ok'){
            error_log("GA request well succeeded");
            $str = json_encode($returned_object['msg']);
            if( $returned_object['msg'] ){
                $etva_server->setGaState(EtvaServerPeer::_GA_RUNNING_);
            } else {
                $etva_server->setGaState(EtvaServerPeer::_GA_NOSTATE_);
            }
            $etva_server->setGaInfo($str);
        }else{
            #TODO: treat error 
            error_log("GA request had no success");
            $etva_server->setGaState(EtvaServerPeer::_GA_STOPPED_);
        }
        $etva_server->save();

        $msg_i18n = Etva::makeNotifyLogMessage($response['agent'],
                                                    EtvaServerPeer::_OK_GAUPDATE_, array('name'=>$etva_server->getName()),
                                                    null,array(),EtvaEventLogger::INFO);
        $result = array('success'=>true,'agent'=>$response['agent'],'response'=>$msg_i18n, 'ga_state'=>$etva_server->getGaState() );
        return $result;
    }

    public function reloadVm(EtvaNode $etva_node)
    {
        $etva_server = $this->etva_server;
        $method = self::SERVER_GET;
        $params = array('uuid'=>$etva_server->getUuid(),'force'=>1);

        $response = $etva_node->soapSend($method,$params);


        if(!$response['success']){

            $error_decoded = $response['error'];

            $result = $response;

            $msg_i18n = sfContext::getInstance()->getI18N()->__(EtvaServerPeer::_ERR_RELOAD_,array('%name%'=>$etva_server->getName(),
                                    '%node%'=>$etva_node->getName(),'%info%'=>$error_decoded));
            $result['error'] = $msg_i18n;

            $message = Etva::getLogMessage(array('name'=>$etva_server->getName(),
                            'node'=>$etva_node->getName(),
                            'info'=>$response['info']), EtvaServerPeer::_ERR_RELOAD_);

            sfContext::getInstance()->getEventDispatcher()->notify(new sfEvent($response['agent'], 'event.log',array('message' => $message,'priority'=>EtvaEventLogger::ERR)));

            return  $result;

        }


        $returned_object = (array) $response['response'];

        $etva_server->initData($returned_object);
        $etva_server->setEtvaCluster($etva_node->getEtvaCluster());
        //$etva_server->setEtvaNode($etva_node);

        $etva_server->save();

        $etva_server->assignTo($etva_node);

        //update agent free memory only if not stopped
        if ($etva_server->isRunning()) {
            $cur_avail = $etva_node->getMemfree();
            $cur_free = $cur_avail - Etva::MB_to_Byteconvert($etva_server->getMem());
            error_log("reloadVm name=".$etva_server->getName()." state=".$etva_server->getVmState()." mem=".Etva::MB_to_Byteconvert($etva_server->getMem())." cur_free=".$cur_free);
            $etva_node->setMemfree($cur_free);
            $etva_node->save();
        }

        $msg_i18n = sfContext::getInstance()->getI18N()->__(EtvaServerPeer::_OK_RELOAD_,array('%name%'=>$etva_server->getName(),
                                    '%node%'=>$etva_node->getName()));

        $result = array('success'=>true,'agent'=>$response['agent'],'response'=>$msg_i18n);

        //notify event log
        $message = Etva::getLogMessage(array('name'=>$etva_server->getName(),'node'=>$etva_node->getName()), EtvaServerPeer::_OK_RELOAD_);

        sfContext::getInstance()->getEventDispatcher()->notify(new sfEvent($response['agent'], 'event.log',array('message' => $message)));

        return $result;


    }

    public function send_unassign(EtvaNode $etva_node){
        $etva_server = $this->etva_server;

        $etva_server->setUnassigned(1);   // mark server as unassigned

        $params = $etva_server->_VA();

        // remove vm from source
        $params = array('uuid'=>$etva_server->getUuid(),'keep_fs' =>1);
        $response = $etva_node->soapSend(self::SERVER_REMOVE,$params);

        if(!$response['success']){

            $result = $response;
            $msg_i18n = sfContext::getInstance()->getI18N()->__(EtvaServerPeer::_ERR_UNASSIGNED_,array('%name%'=>$server_name,'%info%'=>$response['info'],'%node%'=>$etva_node->getName()));
            $result['error'] = $msg_i18n;

            //notify event log
            $message = Etva::getLogMessage(array('name'=>$server_name,'node'=>$etva_node->getName(),'info'=>$response['info']), EtvaServerPeer::_ERR_UNASSIGNED_);
            sfContext::getInstance()->getEventDispatcher()->notify(
                new sfEvent($response['agent'], 'event.log',
                    array('message' => $message,'priority'=>EtvaEventLogger::ERR)));

            return  $result;

        }

        //update agent free memory only if is running
        if ($etva_server->isRunning()) {
            $cur_avail = $etva_node->getMemfree();
            $cur_free = $cur_avail - Etva::MB_to_Byteconvert($etva_server->getMem());
            $etva_node->setMemfree($cur_free);
            $etva_node->save();
        }


        // force the server to stop
        $etva_server->setVmState(EtvaServer::STATE_STOP);
        $etva_server->setState(0);

        $etva_server->save();

        $msg_i18n = sfContext::getInstance()->getI18N()->__(EtvaServerPeer::_OK_UNASSIGNED_,array('%name%'=>$etva_server->getName(),
                                    '%node%'=>$etva_node->getName()));

        $result = array('success'=>true,'agent'=>$response['agent'],'response'=>$msg_i18n);

        //notify event log
        $message = Etva::getLogMessage(array('name'=>$etva_server->getName(),'node'=>$etva_node->getName()), EtvaServerPeer::_OK_UNASSIGNED_);

        sfContext::getInstance()->getEventDispatcher()->notify(new sfEvent($response['agent'], 'event.log',array('message' => $message)));

        return $result;
    }

    public function send_assign(EtvaNode $to_etva_node){
        $etva_server = $this->etva_server;

        $from_etva_node = $etva_server->getEtvaNode();

        $etva_server->setUnassigned(0);   // mark server as unassigned

        $method = self::SERVER_MOVE;
        $err_op = EtvaServerPeer::_ERR_ASSIGNED_;

        // check server name collision on destination cluster
        $server_name = $etva_server->getName();

        $criteria = new Criteria();
        $criteria->add(EtvaServerPeer::CLUSTER_ID,$to_etva_node->getClusterId());

        $etva_server_aux = EtvaServerPeer::retrieveByName($server_name, $criteria);
        if( $etva_server_aux ){
            // check if exist one server with this name
            if( $etva_server->getUuid() !== $etva_server_aux->getUuid() ){

                $msg = Etva::getLogMessage(array('name'=>$server_name), EtvaServerPeer::_ERR_EXIST_);
                $msg_i18n = sfContext::getInstance()->getI18N()->__(EtvaServerPeer::_ERR_EXIST_,array('%name%'=>$server_name));

                $error = array('success'=>false,'agent'=>sfConfig::get('config_acronym'),'error'=>$msg_i18n,'info'=>$msg_i18n);

                //notify event log
                $message = Etva::getLogMessage(array('name'=>$etva_server->getName(),
                                                        'node'=>$to_etva_node->getName(), 'info'=>$msg), $err_op);

                sfContext::getInstance()->getEventDispatcher()->notify(
                    new sfEvent(sfConfig::get('config_acronym'), 'event.log',
                        array('message' => $message,'priority'=>EtvaEventLogger::ERR)));

                return $error;
            }
        }

        $preHaveMem = $this->haveMemoryAvailable($method,$to_etva_node);
        if( !$preHaveMem['success'] ) return $preHaveMem;

        if( $from_etva_node ){
            if( $from_etva_node->getId() != $to_etva_node->getId() ){       // if different nodes
                $preHaveAllShared = $this->haveAllSharedLogicalvolumes($method,$to_etva_node);
                if( !$preHaveAllShared['success'] ) return $preHaveAllShared;
            }
        } else {
            $preCanAssign = $this->canAssignToNode($method,$to_etva_node);
            if( !$preCanAssign['success'] ) return $preCanAssign;
        }

        $params = $etva_server->_VA();

        $response = $to_etva_node->soapSend(self::SERVER_CREATE,$params);

        if(!$response['success']){

            $error_decoded = $response['error'];

            $result = $response;

            $msg_i18n = sfContext::getInstance()->getI18N()->__(EtvaServerPeer::_ERR_ASSIGNED_,
                            array('%name%'=>$etva_server->getName(),
                                    '%node%'=>$to_etva_node->getName(),'%info%'=>$error_decoded));
            $result['error'] = $msg_i18n;

            $message = Etva::getLogMessage(array('name'=>$etva_server->getName(),
                            'node'=>$to_etva_node->getName(),
                            'info'=>$response['info']), EtvaServerPeer::_ERR_ASSIGNED_);

            sfContext::getInstance()->getEventDispatcher()->notify(new sfEvent($response['agent'], 'event.log',array('message' => $message,'priority'=>EtvaEventLogger::ERR)));


            return  $result;

        }

        $etva_server->save();

        $update_node_server = $this->reloadVm($to_etva_node);

        if($update_node_server['success'])
        {

            $msg_i18n = sfContext::getInstance()->getI18N()->__(EtvaServerPeer::_OK_ASSIGNED_,
                                array('%name%'=>$etva_server->getName(),
                                    '%node%'=>$to_etva_node->getName(),'%info%'=>$returned_status));

            $result = array('success'=>true,'agent'=>$response['agent'],'response'=>$msg_i18n);

            //notify event log
            $message = Etva::getLogMessage(array('name'=>$etva_server->getName(),
                            'node'=>$to_etva_node->getName(),
                            'info'=>$response['info']), EtvaServerPeer::_OK_ASSIGNED_);

            sfContext::getInstance()->getEventDispatcher()->notify(new sfEvent($response['agent'], 'event.log',array('message' => $message)));

            return $result;
        }

        return $update_node_server;
    }

    public function send_stop(EtvaNode $etva_node,$extra=null){
        $etva_server = $this->etva_server;
        $method = self::SERVER_STOP;

        $params = $extra ? $extra : array();
        $params['uuid'] = $etva_server->getUuid();
        
        $response = $etva_node->soapSend($method,$params);
        return $this->processStartStop($etva_node,$response,$method);
    }
    public function send_start(EtvaNode $etva_node, $extra=null, $ignoreAdmissionGate=false){
        $etva_server = $this->etva_server;
        $method = self::SERVER_START;

        $mem_available = $etva_node->getMemfree();
        $server_mem_mb = $etva_server->getMem();
        $server_mem = Etva::MB_to_Byteconvert($server_mem_mb);

        error_log( " start_vm mem_available=".$mem_available." server_mem=".$server_mem);
        if( $server_mem > $mem_available ){

            //notify event log
            $msg_i18n = Etva::makeNotifyLogMessage($error['agent'],
                                            EtvaNodePeer::_ERR_MEM_AVAILABLE_, array('name' => $etva_node->getName(), 'info' => $server_mem_mb ),
                                            EtvaServerPeer::_ERR_START_, array('name'=>$server));

            $error = array('success'=>false,'agent'=>$etva_node->getName(),'info'=>$msg_i18n,'error'=>$msg_i18n);
            return $error;
        }

        $etva_cluster = $etva_node->getEtvaCluster();
        if( !$ignoreAdmissionGate && $etva_cluster->getHasNodeHA() ){
            $admissiongate_response = $etva_cluster->getAdmissionGate( $etva_server );
            if( !$admissiongate_response['success'] ){

                //notify event log
                $msg_i18n = Etva::makeNotifyLogMessage($error['agent'],
                                                EtvaClusterPeer::_ERR_ADMISSION_GATE_FAIL_, array('name' => $etva_server->getName(), 'info' => $admissiongate_response['info'] ),
                                                EtvaServerPeer::_ERR_START_, array('name'=>$server));

                $error = array('success'=>false,'agent'=>$etva_node->getName(),'info'=>$msg_i18n,'error'=>$msg_i18n);
                return $error;
            }
        }

        $params = $extra ? $extra : array();
        $params['uuid'] = $etva_server->getUuid();
        
        $boot = $etva_server->getBoot(); 
        $location = $etva_server->getLocation(); 
        $vm_type = $etva_server->getVmType(); 
        $first_boot = $etva_server->getFirstBoot(); 
        
        if($first_boot){ 
            $params['first_boot'] = $first_boot; 
            if($location && $vm_type=='pv') $boot = 'location'; 
        } 
        
        $params['boot'] = $boot; 
        if($boot=='location' || $boot=='cdrom') $params['location'] = $location; 
        if($boot=='location') $params['extra'] = $etva_server->getExtra();
        $params['vnc_keymap'] = $etva_server->getVncKeymap(); 

        $response = $etva_node->soapSend($method,$params);
        return $this->processStartStop($etva_node,$response,$method);
    }
    protected function processStartStop(EtvaNode $etva_node, $response, $method )
    {

        $etva_server = $this->etva_server;

        switch($method){
            case self::SERVER_START :
                                $msg_ok_type = EtvaServerPeer::_OK_START_;
                                $msg_err_type = EtvaServerPeer::_ERR_START_;
                                break;
            case self::SERVER_STOP :
                                $msg_ok_type = EtvaServerPeer::_OK_STOP_;
                                $msg_err_type = EtvaServerPeer::_ERR_STOP_;                                
                                break;
        }
        if(!$response['success']){
            $result = $response;
            $msg_i18n = Etva::makeNotifyLogMessage($response['agent'],
                                                        $response['info'],array(),
                                                        $msg_err_type, array('name'=>$etva_server->getName()));
            $result['error'] = $msg_i18n;
            return $result;
        }

        $response_decoded = (array) $response['response'];
        $returned_status = $response_decoded['_okmsg_'];
        $returned_object = (array) $response_decoded['_obj_'];

        // get some info from response...

        //update some server data from agent response
        $etva_server->initData($returned_object);
        $etva_server->setFirstBoot(0);
        
        if($first_boot) $etva_server->setBoot('filesystem');
        else{
            $boot_field = $etva_server->getBoot();
            switch($boot_field){
                case 'filesystem' :
                case 'pxe'        :
                                    if(!$etva_server->getCdrom()) $etva_server->setLocation(null);
                                    break;
            }
        }

        switch($method){
            case self::SERVER_START :
                                    $etva_server->setHblaststart('NOW');    // update hb last start
                                    break;
        }
        $etva_server->save();

        // update free memory
        $etva_node->updateMemFree();
        $etva_node->save();

        //notify event log
        $msg_i18n = Etva::makeNotifyLogMessage($response['agent'],
                                                        $msg_ok_type, array('name'=>$etva_server->getName()),
                                                        null,array(),EtvaEventLogger::INFO);

        $result = array('success'=>true,'agent'=>$response['agent'],'response'=>$msg_i18n);

        return $result;
    }
    public function get_list_snapshots(EtvaNode $etva_node){
        $response = $etva_node->soapSend(self::SERVER_LIST_SNAPSHOTS,array('uuid'=>$this->etva_server->getUuid(),'name'=>$this->etva_server->getName()));
        if( $response['success'] ){
            $elems = $response['response'];

            if( count($elems) && !$this->etva_server->getHassnapshots() ){
                $this->etva_server->setHassnapshots(1);
                $this->etva_server->save();
            } else if( !count($elems) && $this->etva_server->getHassnapshots() ) {
                $this->etva_server->setHassnapshots(0);
                $this->etva_server->save();
            }
        }
        return $response;
    }
    public function create_snapshot(EtvaNode $etva_node,$snapshot){
        $response =$etva_node->soapSend(self::SERVER_CREATE_SNAPSHOT,array('uuid'=>$this->etva_server->getUuid(),'name'=>$this->etva_server->getName(),'snapshot'=>$snapshot));
        if( $response['success'] ){
            if( !$this->etva_server->getHassnapshots() ){
                $this->etva_server->setHassnapshots(1);
                $this->etva_server->save();
            }
        }
        return $response;
    }
    public function revert_snapshot(EtvaNode $etva_node,$snapshot){
        return $etva_node->soapSend(self::SERVER_REVERT_SNAPSHOT,array('uuid'=>$this->etva_server->getUuid(),'name'=>$this->etva_server->getName(),'snapshot'=>$snapshot));
    }
    public function remove_snapshot(EtvaNode $etva_node,$snapshot){
        return $etva_node->soapSend(self::SERVER_REMOVE_SNAPSHOT,array('uuid'=>$this->etva_server->getUuid(),'name'=>$this->etva_server->getName(),'snapshot'=>$snapshot));
    }
    public function send_suspend(EtvaNode $etva_node){
        $etva_server = $this->etva_server;
        $method = self::SERVER_SUSPEND;

        $params = array();
        $params['uuid'] = $etva_server->getUuid();
        
        $response = $etva_node->soapSend($method,$params);
        return $this->processSuspendResume($etva_node,$response,$method);
    }
    public function send_resume(EtvaNode $etva_node){
        $etva_server = $this->etva_server;
        $method = self::SERVER_RESUME;

        $params = array();
        $params['uuid'] = $etva_server->getUuid();
        
        $response = $etva_node->soapSend($method,$params);
        return $this->processSuspendResume($etva_node,$response,$method);
    }
    protected function processSuspendResume(EtvaNode $etva_node, $response, $method )
    {

        $etva_server = $this->etva_server;

        switch($method){
            case self::SERVER_SUSPEND :
                                $msg_ok_type = EtvaServerPeer::_OK_SUSPEND_;
                                $msg_err_type = EtvaServerPeer::_ERR_SUSPEND_;
                                break;
            case self::SERVER_RESUME :
                                $msg_ok_type = EtvaServerPeer::_OK_RESUME_;
                                $msg_err_type = EtvaServerPeer::_ERR_RESUME_; 
                                break;
        }
        if(!$response['success']){
            $result = $response;
            $msg_i18n = Etva::makeNotifyLogMessage($response['agent'],
                                                        $response['info'],array(),
                                                        $msg_err_type, array('name'=>$etva_server->getName()));
            $result['error'] = $msg_i18n;
            return $result;
        }

        $response_decoded = (array) $response['response'];
        $returned_status = $response_decoded['_okmsg_'];
        $returned_object = (array) $response_decoded['_obj_'];

        // get some info from response...

        //update some server data from agent response
        $etva_server->initData($returned_object);
        
        $etva_server->save();

        //notify event log
        $msg_i18n = Etva::makeNotifyLogMessage($response['agent'],
                                                        $msg_ok_type, array('name'=>$etva_server->getName()),
                                                        null,array(),EtvaEventLogger::INFO);

        $result = array('success'=>true,'agent'=>$response['agent'],'response'=>$msg_i18n);

        return $result;
    }
}
