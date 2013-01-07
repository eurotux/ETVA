<?php
/*
 * class to perform physical volume operations with VA
 */

class EtvaPhysicalvolume_VA
{
    private $etva_pv; // EtvaPhysicalvolume object
    private $missing_pvs; // array pvs uuids that should be on VA response (shared pvs only)

    const PVCREATE = 'pvcreate';
    const PVREMOVE = 'pvremove';

    const PVINIT = 'pvinit';
    const GET_SYNC_DISKDEVICES = 'hash_phydisks';
    const LOOKUP_DISKDEVICES = 'lookup_fc_devices';

    

    public function EtvaPhysicalvolume_VA(EtvaPhysicalvolume $etva_pv = null)
    {
        $this->missing_pvs = array();
        if($etva_pv) $this->etva_pv = $etva_pv;
        else $this->etva_pv = new EtvaPhysicalvolume();

    }

    /*
     * send pvcreate
     */
    public function send_create(EtvaNode $etva_node)
    {        
        $method = self::PVCREATE;
        
        $etva_pv_uuid = $this->etva_pv->getUuid();
        $etva_pv_type = $this->etva_pv->getStorageType();
        $etva_pv_device = $this->etva_pv->getDevice();
        $params = array('device' => $etva_pv_device, 'uuid' => $etva_pv_uuid);

        $response = $etva_node->soapSend($method,$params);
        $result = $this->processResponse($etva_node,$response,$method);
        return $result;
    }

    /*
     * send pvremove
     */
    public function send_remove(EtvaNode $etva_node)
    {
        
        $method = self::PVREMOVE;
        $etva_pv_uuid = $this->etva_pv->getUuid();
        $etva_pv_type = $this->etva_pv->getStorageType();
        $etva_pv_device = $this->etva_pv->getDevice();
        $params = array('device' => $etva_pv_device, 'uuid' => $etva_pv_uuid);

        $response = $etva_node->soapSend($method,$params);
        $result = $this->processResponse($etva_node,$response,$method);
        return $result;
    }


    /*
     * bulk cluster update info
     *
     */
    protected function sync_update($bulk_responses){

        $errors = array();

        foreach($bulk_responses as $node_id =>$node_response)
        {
            if($node_response['success'])
            {

                $node = EtvaNodePeer::retrieveByPK($node_id);
                $node_init = $this->initialize($node,(array) $node_response['response']);

                if(!$node_init['success']){
                    $errors[$node_id] = $node_init;
                } else {
                    //notify system log
                    $message = Etva::getLogMessage(array(), EtvaPhysicalvolumePeer::_OK_SOAPREFRESH_);
                    sfContext::getInstance()->getEventDispatcher()->notify(new sfEvent($node_response['agent'], 'event.log',array('message' => $message)));
                }
                
            } else {
                $message = Etva::getLogMessage(array('info'=>$node_response['info']), EtvaPhysicalvolumePeer::_ERR_SOAPREFRESH_);
                sfContext::getInstance()->getEventDispatcher()->notify(new sfEvent($node_response['agent'], 'event.log',array('message' => $message,'priority'=>EtvaEventLogger::ERR)));
            }
        }
        return $errors;
    }
    public function send_update($etva_node)
    {

        /*
         * update other nodes..... storages
         *
         */
        $etva_cluster = $etva_node->getEtvaCluster();
        $bulk_responses = $etva_cluster->soapSend(self::GET_SYNC_DISKDEVICES,array('force'=>1),$etva_node);

        return $this->sync_update($bulk_responses);
    }

    /*
     * process PVCREATE and PVREMOVE response
     */
    public function processResponse($etva_node,$response,$method)
    {
        switch($method)
        {
            case self::PVREMOVE:
                                $msg_ok_type = EtvaPhysicalvolumePeer::_OK_UNINIT_;
                                $msg_err_type = EtvaPhysicalvolumePeer::_ERR_UNINIT_;
                                break;
            case self::PVCREATE:
                                $msg_ok_type = EtvaPhysicalvolumePeer::_OK_INIT_;
                                $msg_err_type = EtvaPhysicalvolumePeer::_ERR_INIT_;
                                break;
        }

        $etva_pv = $this->etva_pv;
        $ok = 1;
        if($response['success'])
        {
            $response_decoded = (array) $response['response'];
            $returned_status = $response_decoded['_okmsg_'];
            $returned_object = (array) $response_decoded['_obj_'];

            switch($method)
            {
                case self::PVREMOVE:
                                    // unset physical volume information
                                    $etva_pv->setPvinit('');
                                    $etva_pv->setPv('');
                                    $etva_pv->setPvsize('');
                                    $etva_pv->setPvfreesize('');
                                    $etva_pv->setAllocatable(0);
                                    $etva_pv->save();
                                    break;
                case self::PVCREATE:
                                    // update DB info
                                    $etva_pv->initData((array) $returned_object);
                                    // set allocatable flag
                                    $etva_pv->setAllocatable(1);
                                    $etva_pv->save();
                                    $dev_device = $returned_object[EtvaPhysicalvolume::DEVICE_MAP];
                                    $etva_node_physicalvol = EtvaNodePhysicalvolumePeer::retrieveByPK($etva_node->getId(), $etva_pv->getId());
                                    $etva_node_physicalvol->setDevice($dev_device);
                                    $etva_node_physicalvol->save();

                                    break;
            }
            
            if($etva_pv->getStorageType()!=EtvaPhysicalvolume::STORAGE_TYPE_LOCAL_MAP)
            {                
                /*
                 * if storage type not local send update to nodes...
                 */
                $bulk_update = $this->send_update($etva_node);
                if(!empty($bulk_update))
                {
                    //$response = $bulk_update;
                    $ok = 0;
                }
            }            

            if($ok)
            {
                //notify system log
                $message = Etva::getLogMessage(array('name'=>$etva_pv->getName()), $msg_ok_type);
                $msg_i18n = sfContext::getInstance()->getI18N()->__($msg_ok_type,array('%name%'=>$etva_pv->getName()));
                
                sfContext::getInstance()->getEventDispatcher()->notify(new sfEvent($etva_node->getName(), 'event.log',array('message' => $message)));
                
                $result = array('success'=>true, 'agent'=>$response['agent'], 'response'=>$msg_i18n);

                return  $result;
            }

        }else
        {
            $ok = 0;
        }

        // soap response error....
        // DB information not updated
        if($ok==0)
        {


            $result = $response;

            if($this->missing_pvs)
            {
                $errors = $this->missing_pvs;
                $names = array();
                foreach($errors as $node_id=>$error_data)
                {
                    $names[] = $error_data['name'];

                }

                $message = Etva::getLogMessage(array('info'=>implode(', ',$names)), EtvaPhysicalvolumePeer::_ERR_INCONSISTENT_);
                sfContext::getInstance()->getEventDispatcher()->notify(new sfEvent($etva_node->getName(),'event.log',array('message' =>$message,'priority'=>EtvaEventLogger::ERR)));

                return array('success'=>false,'agent'=>sfConfig::get('config_acronym'),'error'=>$message,'action'=>'reload','info'=>$message);


            }else
            {

                $msg_i18n = Etva::makeNotifyLogMessage($response['agent'],
                                                        $msg_err_type,array('name'=>$etva_pv->getName(),'info'=>$response['info']));
                $result['error'] = $msg_i18n;
                return  $result;

            }

        }

    }
    
    /*
     * initialize info based on passed data. check if exists on db and if matches info on DB
     */

    public function initialize(EtvaNode $etva_node,$devs,$force_regist=false)
    {                
        $etva_cluster = $etva_node->getEtvaCluster();
        $volumegroup_names = array();        

        $errors = array();

        /*
         * check shared vgs consistency (applies only for enterprise)
         */
        $etva_data = Etva::getEtvaModelFile();
        /*$etvamodel = $etva_data['model'];
        $consist = 1;
        if($etvamodel != 'standard') $consist = $this->check_shared_consistency($etva_node,$devs);*/
        $check_res = $this->check_consistency($etva_node,$devs);

        if( !$check_res['success'] ){
            $errors = $check_res['errors'];

            $inconsistent_message = Etva::getLogMessage(array('info'=>''), EtvaPhysicalvolumePeer::_ERR_INCONSISTENT_);

            $etva_node->setErrorMessage(self::PVINIT);

            $message = Etva::getLogMessage(array('info'=>$inconsistent_message), EtvaPhysicalvolumePeer::_ERR_SOAPUPDATE_);
            sfContext::getInstance()->getEventDispatcher()->notify(new sfEvent($etva_node->getName(),'event.log',array('message' =>$message,'priority'=>EtvaEventLogger::ERR)));

            //return array('success'=>false,'error'=>$errors);
        }        
        /*if(!$consist){
            $errors = $this->missing_pvs[$etva_node->getId()];

            $inconsistent_message = Etva::getLogMessage(array('info'=>''), EtvaPhysicalvolumePeer::_ERR_INCONSISTENT_);

            $etva_node->setErrorMessage(self::PVINIT);

            $message = Etva::getLogMessage(array('info'=>$inconsistent_message), EtvaPhysicalvolumePeer::_ERR_SOAPUPDATE_);
            sfContext::getInstance()->getEventDispatcher()->notify(new sfEvent($etva_node->getName(),'event.log',array('message' =>$message,'priority'=>EtvaEventLogger::ERR)));

            return array('success'=>false,'error'=>$errors);
        }*/

        $physical_names = array();

        foreach($devs as $dev=>$devInfo)
        {
            $dev_info = (array) $devInfo;
            $dev_type = $dev_info[EtvaPhysicalvolume::STORAGE_TYPE_MAP];            
            $dev_device = $dev_info[EtvaPhysicalvolume::DEVICE_MAP];            
            //error_log(sprintf("node name=%s id=%d device=%s uuid=%s type=%s",$etva_node->getName(),$etva_node->getId(),$dev_device,$dev_info[EtvaPhysicalvolume::UUID_MAP],$dev_type));

            if($dev_type == EtvaPhysicalvolume::STORAGE_TYPE_LOCAL_MAP){
                $etva_physicalvol = EtvaPhysicalvolumePeer::retrieveByNodeTypeDevice($etva_node->getId(), $dev_type, $dev_device);
            }else{
                if( isset($dev_info[EtvaPhysicalvolume::UUID_MAP]) ){
                    $dev_uuid = $dev_info[EtvaPhysicalvolume::UUID_MAP];
                    $etva_physicalvol = EtvaPhysicalvolumePeer::retrieveByUUID($dev_uuid);
                } else { 
                    $dev_info[EtvaPhysicalvolume::UUID_MAP] = '';   // clean uuid
                    $etva_physicalvol = EtvaPhysicalvolumePeer::retrieveByNodeTypeDevice($etva_node->getId(), $dev_type, $dev_device);
                }

                if( !$etva_physicalvol )
                    $etva_physicalvol = EtvaPhysicalvolumePeer::retrieveByNodeTypeDevice($etva_node->getId(), $dev_type, $dev_device);

                if( !$etva_physicalvol )
                    $etva_physicalvol = EtvaPhysicalvolumePeer::retrieveByClusterTypeUUIDDevice($etva_node->getClusterId(), $dev_type, $dev_info[EtvaPhysicalvolume::UUID_MAP], $dev_device);
            }
                        

            if($force_regist && !$etva_physicalvol){ // no pv in db... and force registration ... so create new one
                $etva_node_physicalvol = new EtvaNodePhysicalvolume();
                $etva_physicalvol = new EtvaPhysicalvolume();
            } else if( $etva_physicalvol ){
                //if pv  already in DB we need to make sure if already exists association with node. if not create new one
                $etva_node_physicalvol = EtvaNodePhysicalvolumePeer::retrieveByPK($etva_node->getId(), $etva_physicalvol->getId());
                if(!$etva_node_physicalvol) $etva_node_physicalvol = new EtvaNodePhysicalvolume();
            }

            if( $etva_physicalvol ){

                $etva_physicalvol->initData($dev_info);            
                $etva_physicalvol->setEtvaCluster($etva_cluster);
                
                $etva_node_physicalvol->setEtvaPhysicalvolume($etva_physicalvol);
                $etva_node_physicalvol->setEtvaNode($etva_node);
                $etva_node_physicalvol->setDevice($dev_device);
                $etva_node_physicalvol->save();

                $physical_names[] = $etva_physicalvol->getName();
            }
            // TODO treat ignoring cases
             

        }

        if( !empty($errors) ){
            // if have some errors, return it
            return array('success'=>false,'error'=>$errors);
        } else {
            /*
             * check if is an appliance restore operation...
             */
            $apli = new Appliance();
            $action = $apli->getStage(Appliance::RESTORE_STAGE);
            if($action) $apli->setStage(Appliance::RESTORE_STAGE,Appliance::VA_UPDATE_PVS);

            $etva_node->clearErrorMessage(self::PVINIT);        

            $message = Etva::getLogMessage(array('info'=>implode(', ',$physical_names)), EtvaPhysicalvolumePeer::_OK_SOAPUPDATE_);
            sfContext::getInstance()->getEventDispatcher()->notify(new sfEvent($etva_node->getName(),'event.log',array('message' =>$message)));

            return array('success'=>true,'response'=>$physical_names);
        }
    }

    /*
     * register - save physical volume in database
     */
    public function register(EtvaNode $etva_node, $dev_info=null){

        // set device info if needed
        if( $dev_info )
            $this->etva_pv->initData($dev_info);

        // check if physical volume is shared
        $shared = ($this->etva_pv->getStorageType() != EtvaPhysicalvolume::STORAGE_TYPE_LOCAL_MAP);

        $etva_cluster = $etva_node->getEtvaCluster();

        $bulk_responses = array();
        if( $shared ){
            // lookup new disk devices
            $etva_cluster->soapSend(self::LOOKUP_DISKDEVICES);
            // force to load disk devices
            $bulk_responses = $etva_cluster->soapSend(self::GET_SYNC_DISKDEVICES,array('force'=>1));
        } else {
            $node_id = $etva_node->getId();
            // lookup new disk devices
            $etva_node->soapSend(self::LOOKUP_DISKDEVICES);
            // force to load disk devices
            $bulk_responses[$node_id] = $etva_node->soapSend(self::GET_SYNC_DISKDEVICES,array('force'=>1));
        }

        // for none local storage devices
        if($shared){
            // check if all nodes see that
            $check_res = $this->check_all_see_it($etva_node,$bulk_responses);
            if( !$check_res['success'] ){
                return array('success'=>false, 'errors'=>$check_res['errors'], 'debug'=>'check_all_see_it');
            }
        }

        $etva_physicalvol = $this->etva_pv;

        // set cluster
        $etva_cluster = $etva_node->getEtvaCluster();
        $etva_physicalvol->setEtvaCluster($etva_cluster);

        // save it
        $etva_physicalvol->save();

        $dev_device = $etva_physicalvol->getDevice();

        // create relation node_physicalvolume
        $etva_node_physicalvol = new EtvaNodePhysicalvolume();
        $etva_node_physicalvol->setEtvaPhysicalvolume($etva_physicalvol);
        $etva_node_physicalvol->setEtvaNode($etva_node);
        $etva_node_physicalvol->setDevice($dev_device);
        $etva_node_physicalvol->save();

        $return = array('success'=>true);
        $errors = $this->sync_update($bulk_responses);
        if( !empty($errors) )
            $return = array('success'=>false, 'errors'=>$errors);

        return $return;
    }
    public function check_all_see_it(EtvaNode $etva_node, $bulk_responses){

        $errors = array();
        foreach($bulk_responses as $node_id =>$node_response)
        {
            if($node_response['success']){

                $node = EtvaNodePeer::retrieveByPK($node_id);
                $devs = $node_response['response'];

                $found = false;
                foreach($devs as $dev=>$devInfo){
                    $dev_info = (array) $devInfo;
                    $dev_type = $dev_info[EtvaPhysicalvolume::STORAGE_TYPE_MAP];
                    $dev_device = $dev_info[EtvaPhysicalvolume::DEVICE_MAP];

                    if( $dev_type == $this->etva_pv->getStorageType() ){
                        if($dev_type == EtvaPhysicalvolume::STORAGE_TYPE_LOCAL_MAP){
                            if( $dev_device == $this->etva_pv->getDevice() )
                                $found = true;
                        }else{
                            if( isset($dev_info[EtvaPhysicalvolume::UUID_MAP]) ){
                                $dev_uuid = $dev_info[EtvaPhysicalvolume::UUID_MAP];
                                if( $dev_uuid == $this->etva_pv->getUuid() )
                                    $found = true;
                            } else {
                                if( $dev_device == $this->etva_pv->getDevice() )
                                    $found = true;
                            }
                        }
                    }
                    if( $found ) break;
                }
                if( !$found )
                    $errors[$node_id] = array( 'node_id'=>$node_id, 'uuid'=>$this->etva_pv->getUuid(), 'device'=>$this->etva_pv->getDevice(), 'found'=>true );


                /*$node_init = $this->initialize($node,(array) $node_response['response']);

                if(!$node_init['success']){
                    $errors[$node_id] = $node_init;
                } else {
                    //notify system log
                    $message = Etva::getLogMessage(array(), EtvaPhysicalvolumePeer::_OK_SOAPREFRESH_);
                    sfContext::getInstance()->getEventDispatcher()->notify(new sfEvent($node_response['agent'], 'event.log',array('message' => $message)));
                }*/
            } else {
                /*$message = Etva::getLogMessage(array('info'=>$node_response['info']), EtvaPhysicalvolumePeer::_ERR_SOAPREFRESH_);
                sfContext::getInstance()->getEventDispatcher()->notify(new sfEvent($node_response['agent'], 'event.log',array('message' => $message,'priority'=>EtvaEventLogger::ERR)));*/
            }
        }

        if( !empty($errors) ) 
            return array('success'=>false, 'errors'=>$errors);
        else return array('success'=>'true');
    }

    public function unregister(EtvaNode $etva_node){

        $etva_physicalvol = $this->etva_pv;

        // delete it
        $etva_physicalvol->delete();

        return array( 'success'=>true );
    }

    /*
     * checks for shared pvs consistency between DB info and soap response
     * the DB info should be in soap response otherwise there is consistency problems
     */
    private function check_shared_consistency($etva_node,$response)
    {
        $node_id = $etva_node->getId();
        $etva_cluster = $etva_node->getEtvaCluster();
        
        $db_ = $etva_cluster->getSharedPvs();
        if(!$db_) $db_ = array();

        $in_db_uuids = array();
        $db_uuids = array();

        //build uuid array from DB where vgs are type 'storage'
        foreach($db_ as $data){
            $data_uuid = $data->getUuid();
            if( !$data_uuid )
                $data_uuid = $data->getDevice();
            $db_uuids[$data_uuid] = $data;
        }

        $in_db_uuids = array_keys($db_uuids);


        $tam_db = count($in_db_uuids);
        $in_resp = (array) $response;
        $in_resp_arr = array();

        // build uuid array from soap response with type 'shared'
        foreach($in_resp as $data_info){
            $info = (array) $data_info;            
            if($info[EtvaPhysicalvolume::STORAGE_TYPE_MAP]!=EtvaPhysicalvolume::STORAGE_TYPE_LOCAL_MAP){
                if( isset($info[EtvaPhysicalvolume::UUID_MAP]) )
                    $in_resp_arr[] = $info[EtvaPhysicalvolume::UUID_MAP];
                else
                    $in_resp_arr[] = $info[EtvaPhysicalvolume::DEVICE_MAP];
            }
        }

        $consistent = 1;
        $tam_resp = count($in_resp);
        $i=0;
        while($i<$tam_db){
            if(!in_array($in_db_uuids[$i],$in_resp_arr)){
                $consistent = 0;
                $this->missing_pvs[$node_id]['uuid'][] = $in_db_uuids[$i];
                $this->missing_pvs[$node_id]['name'] = $etva_node->getName();
            }
            $i++;
        }

        return $consistent;

    }
    
    public function check_consistency(EtvaNode $etva_node,$sync_node_pvs){

        $errors = array();

        $etva_node->getId();
        $etva_cluster = $etva_node->getEtvaCluster();

        // get node database PVs
        //$node_pvs = $etva_node->getEtvaPhysicalvolumes();
        $node_pvs = $etva_node->getEtvaNodePhysicalvolumes();
        
        // get shared database PVs
        $shared_pvs = $etva_cluster->getSharedPvs();

        $node_inconsistent = 0;

        foreach( $node_pvs as $rpv ){
            $pv = $rpv->getEtvaPhysicalvolume();

            $error = array();

            $uuid = $pv->getUuid();
            $device = $pv->getDevice();

            // init
            $inconsistent = 0;

            // look at physical volumes list
            $found_lvm = 0;
            foreach( $sync_node_pvs as $hpv ){
                $arr_hpv = (array)$hpv;
                if( $arr_hpv[EtvaPhysicalvolume::STORAGE_TYPE_MAP] == $pv->getStorageType() ){
                    if( isset($arr_hpv[EtvaPhysicalvolume::UUID_MAP]) ){
                        if( $arr_hpv[EtvaPhysicalvolume::UUID_MAP] == $uuid )
                            $found_lvm = 1;
                    } else {
                        if( $arr_hpv[EtvaPhysicalvolume::DEVICE_MAP] == $device )
                            $found_lvm = 1;
                    }
                }
            }

            $inconsistent = $inconsistent || !$found_lvm;
            if( !$found_lvm ) $error['not_found_lvm'] = 1;

            /* TODO
             *   check when have multipath
             */

            $found_shared_lvm = 0;
            $inconsistent_shared_pvs = 0;
            foreach( $shared_pvs as $s_pv ){
                $s_uuid = $s_pv->getUuid();
                $s_device = $s_pv->getDevice();

                if( $s_uuid == $uuid ){
                    if( $s_pv->getStorageType() != $pv->getStorageType() ){
                        $inconsistent_shared_pvs = 1;
                    } else {
                        $found_shared_lvm = 1;
                    }
                } else {
                    if( $s_pv->getStorageType() == $pv->getStorageType() )
                        if( $arr_hpv[EtvaPhysicalvolume::DEVICE_MAP] == $device )
                            $found_shared_lvm = 1;
                }
            }

            if( $pv->getStorageType() != EtvaPhysicalvolume::STORAGE_TYPE_LOCAL_MAP ){
                $inconsistent = $inconsistent || !$found_shared_lvm;
                if( !$found_shared_lvm ) $error['not_found_shared_lvm'] = 1;
            }
            $inconsistent = $inconsistent || $inconsistent_shared_pvs;
            if( $inconsistent_shared_pvs ) $error['diff_storage_type'] = 1;

            // update data-base
            $etva_physicalvol = EtvaPhysicalvolumePeer::retrieveByNodeTypeUUIDDevice($etva_node->getId(), $pv->getStorageType(), $pv->getUuid(), $pv->getDevice() );
            if( $etva_physicalvol ){
                $etva_physicalvol->setInconsistent($inconsistent);
                $etva_physicalvol->save();
            }
            $etva_node_physicalvol = EtvaNodePhysicalvolumePeer::retrieveByPK($etva_node->getId(), $pv->getId());
            if( $etva_node_physicalvol ){
                $etva_node_physicalvol->setInconsistent($inconsistent);
                $etva_node_physicalvol->save();
            }

            if( $inconsistent ){
                $message = sfContext::getInstance()->getI18N()->__(EtvaPhysicalvolumePeer::_ERR_INCONSISTENT_,array('%info%'=>sprintf('device "%s" with uuid "%s"',$pv->getDevice(),$pv->getUuid())));
                sfContext::getInstance()->getEventDispatcher()->notify(new sfEvent($etva_node->getName(),'event.log',array('message' =>$message,'priority'=>EtvaEventLogger::ERR)));
                $error['node'] = array( 'name'=>$etva_node->getName(), 'id'=>$etva_node->getId(), 'uuid'=>$etva_node->getUuid() );
                $error['device'] = $pv->getDevice();
                $error['device_id'] = $pv->getId();
                $error['uuid'] = $pv->getUuid();
                $error['message'] = $message;
            }

            if( !empty($error) )
                $errors[] = $error;

            $node_inconsistent = $node_inconsistent || $inconsistent;
        }

        // check consistency for shared PVs
        foreach( $shared_pvs as $pv ){
            $error = array();

            $uuid = $pv->getUuid();
            $device = $pv->getDevice();

            // init
            $inconsistent = 0;

            // look at physical volumes list
            $found_lvm = 0;
            foreach( $sync_node_pvs as $hpv ){
                $arr_hpv = (array)$hpv;
                if( $arr_hpv[EtvaPhysicalvolume::STORAGE_TYPE_MAP] == $pv->getStorageType() ){
                    if( isset($arr_hpv[EtvaPhysicalvolume::UUID_MAP]) ){
                        if( $arr_hpv[EtvaPhysicalvolume::UUID_MAP] == $uuid )
                            $found_lvm = 1;
                    } else {
                        if( $arr_hpv[EtvaPhysicalvolume::DEVICE_MAP] == $device )
                            $found_lvm = 1;
                    }
                }
            }

            $inconsistent = $inconsistent || !$found_lvm;
            if( !$found_lvm ) $error['not_found_lvm'] = 1;

            /* TODO
             *   check when have multipath
             */

            // update data-base
            $etva_physicalvol = EtvaPhysicalvolumePeer::retrieveByNodeTypeUUIDDevice($etva_node->getId(), $pv->getStorageType(), $pv->getUuid(), $pv->getDevice() );
            if( $etva_physicalvol ){
                $etva_physicalvol->setInconsistent($inconsistent);
                $etva_physicalvol->save();
            }
            $etva_node_physicalvol = EtvaNodePhysicalvolumePeer::retrieveByPK($etva_node->getId(), $pv->getId());
            if( $etva_node_physicalvol ){
                $etva_node_physicalvol->setInconsistent($inconsistent);
                $etva_node_physicalvol->save();
            }

            if( $inconsistent ){
                $message = sfContext::getInstance()->getI18N()->__(EtvaPhysicalvolumePeer::_ERR_INCONSISTENT_,array('%info%'=>sprintf('device "%s" with uuid "%s"',$pv->getDevice(),$pv->getUuid())));
                sfContext::getInstance()->getEventDispatcher()->notify(new sfEvent($etva_node->getName(),'event.log',array('message' =>$message,'priority'=>EtvaEventLogger::ERR)));
                $error['node'] = array( 'name'=>$etva_node->getName(), 'id'=>$etva_node->getId(), 'uuid'=>$etva_node->getUuid() );
                $error['device'] = $pv->getDevice();
                $error['device_id'] = $pv->getId();
                $error['uuid'] = $pv->getUuid();
                $error['message'] = $message;
            }

            if( !empty($error) )
                $errors[] = $error;

            $node_inconsistent = $node_inconsistent || $inconsistent;
        }

        $return = array();

        if( $node_inconsistent ){
            $etva_node->setErrorMessage(self::PVINIT);
            $return = array( 'success'=>false, 'errors'=>$errors );
        } else {
            $etva_node->clearErrorMessage(self::PVINIT);
            $return = array( 'success'=>true );
        }

        return $return; //($node_inconsistent) ? false : true;
    }
}
