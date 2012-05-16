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
    

    public function EtvaPhysicalvolume_VA(EtvaPhysicalvolume $etva_pv = null)
    {
        $this->missing_pvs = array();
        $this->etva_pv = new EtvaPhysicalvolume();

        if($etva_pv) $this->etva_pv = $etva_pv;
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
        if($etva_pv_type == EtvaPhysicalvolume::STORAGE_TYPE_LOCAL_MAP)
            $params = array('device' => $etva_pv_device);
        else
            $params = array('uuid' => $etva_pv_uuid);

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
        if($etva_pv_type == EtvaPhysicalvolume::STORAGE_TYPE_LOCAL_MAP)
            $params = array('device' => $etva_pv_device);
        else
            $params = array('uuid' => $etva_pv_uuid);

        $response = $etva_node->soapSend($method,$params);
        $result = $this->processResponse($etva_node,$response,$method);
        return $result;
    }


    /*
     * bulk cluster update info
     *
     */
    public function send_update($etva_node)
    {

        /*
         * update other nodes..... storages
         *
         */
        $etva_cluster = $etva_node->getEtvaCluster();
        $bulk_responses = $etva_cluster->soapSend('hash_phydisks',array('force'=>1),$etva_node);
        $errors = array();

        foreach($bulk_responses as $node_id =>$node_response)
        {
            if($node_response['success'])
            {

                $node = EtvaNodePeer::retrieveByPK($node_id);                
                $node_init = $this->initialize($node,(array) $node_response['response']);

                if(!$node_init['success']){
                    $errors[$node_id] = $node_init;
                }
                else
                {
                    //notify system log
                    $message = Etva::getLogMessage(array(), EtvaPhysicalvolumePeer::_OK_SOAPREFRESH_);
                    sfContext::getInstance()->getEventDispatcher()->notify(new sfEvent($node_response['agent'], 'event.log',array('message' => $message)));
                }
                

            }else
            {
                $message = Etva::getLogMessage(array('info'=>$node_response['info']), EtvaPhysicalvolumePeer::_ERR_SOAPREFRESH_);
                sfContext::getInstance()->getEventDispatcher()->notify(new sfEvent($node_response['agent'], 'event.log',array('message' => $message,'priority'=>EtvaEventLogger::ERR)));

            }
        }

        return $errors;

    }

    /*
     * process PVCREATE and PVREMOVE response
     */
    public function processResponse($etva_node,$response,$method)
    {
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
                                    $msg_ok_type = EtvaPhysicalvolumePeer::_OK_UNINIT_;
                                    $msg_err_type = EtvaPhysicalvolumePeer::_ERR_UNINIT_;
                                    // unset physical volume information
                                    $etva_pv->setPvinit('');
                                    $etva_pv->setPv('');
                                    $etva_pv->setPvsize('');
                                    $etva_pv->setPvfreesize('');
                                    $etva_pv->setAllocatable(0);
                                    $etva_pv->save();
                                    break;
                case self::PVCREATE:
                                    $msg_ok_type = EtvaPhysicalvolumePeer::_OK_INIT_;
                                    $msg_err_type = EtvaPhysicalvolumePeer::_ERR_INIT_;
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
                $message = Etva::getLogMessage(array('name'=>$etva_pv->getName(),'info'=>$response['info']), $msg_err_type);

                $msg_i18n = sfContext::getInstance()->getI18N()->__($msg_err_type,array('%name%'=>$etva_pv->getName(),'%info%'=>$response['info']));
                $result['error'] = $msg_i18n;

                sfContext::getInstance()->getEventDispatcher()->notify(new sfEvent($response['agent'], 'event.log',array('message' => $message,'priority'=>EtvaEventLogger::ERR)));

                return  $result;

            }

        }

    }
    
    /*
     * initialize info based on passed data. check if exists on db and if matches info on DB
     */

    public function initialize(EtvaNode $etva_node,$devs)
    {                
        $etva_cluster = $etva_node->getEtvaCluster();
        $volumegroup_names = array();        

        /*
         * check shared vgs consistency (applies only for enterprise)
         */
        $etva_data = Etva::getEtvaModelFile();
        $etvamodel = $etva_data['model'];
        $consist = 1;
        if($etvamodel != 'standard') $consist = $this->check_shared_consistency($etva_node,$devs);

        if(!$consist){
            $errors = $this->missing_pvs[$etva_node->getId()];

            $inconsistent_message = Etva::getLogMessage(array('info'=>''), EtvaPhysicalvolumePeer::_ERR_INCONSISTENT_);

            $etva_node->setErrorMessage(self::PVINIT);

            $message = Etva::getLogMessage(array('info'=>$inconsistent_message), EtvaPhysicalvolumePeer::_ERR_SOAPUPDATE_);
            sfContext::getInstance()->getEventDispatcher()->notify(new sfEvent($etva_node->getName(),'event.log',array('message' =>$message,'priority'=>EtvaEventLogger::ERR)));

            return array('success'=>false,'error'=>$errors);
        }        

        foreach($devs as $dev=>$devInfo)
        {
            $dev_info = (array) $devInfo;
            $dev_type = $dev_info[EtvaPhysicalvolume::STORAGE_TYPE_MAP];            
            $dev_device = $dev_info[EtvaPhysicalvolume::DEVICE_MAP];            

            if($dev_type == EtvaPhysicalvolume::STORAGE_TYPE_LOCAL_MAP){
                $etva_physicalvol = EtvaPhysicalvolumePeer::retrieveByNodeTypeDevice($etva_node->getId(), $dev_type, $dev_device);
            }else{
                if( isset($dev_info[EtvaPhysicalvolume::UUID_MAP]) ){
                    $dev_uuid = $dev_info[EtvaPhysicalvolume::UUID_MAP];
                    $etva_physicalvol = EtvaPhysicalvolumePeer::retrieveByUUID($dev_uuid);
                } else 
                    $etva_physicalvol = EtvaPhysicalvolumePeer::retrieveByNodeTypeDevice($etva_node->getId(), $dev_type, $dev_device);
                if( !$etva_physicalvol )
                    $etva_physicalvol = EtvaPhysicalvolumePeer::retrieveByNodeTypeDevice($etva_node->getId(), $dev_type, $dev_device);
            }
                        

            if(!$etva_physicalvol){ // no pv in db...so create new one
                $etva_node_physicalvol = new EtvaNodePhysicalvolume();
                $etva_physicalvol = new EtvaPhysicalvolume();
            }
            else{
                //if pv  already in DB we need to make sure if already exists association with node. if not create new one
                $etva_node_physicalvol = EtvaNodePhysicalvolumePeer::retrieveByPK($etva_node->getId(), $etva_physicalvol->getId());
                if(!$etva_node_physicalvol) $etva_node_physicalvol = new EtvaNodePhysicalvolume();
            }            

            $etva_physicalvol->initData($dev_info);            
            $etva_physicalvol->setEtvaCluster($etva_cluster);
            
            $etva_node_physicalvol->setEtvaPhysicalvolume($etva_physicalvol);
            $etva_node_physicalvol->setEtvaNode($etva_node);
            $etva_node_physicalvol->setDevice($dev_device);
            $etva_node_physicalvol->save();

            $physical_names[] = $etva_physicalvol->getName();

        }

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
    
}
