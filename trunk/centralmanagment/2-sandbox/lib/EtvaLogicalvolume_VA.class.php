<?php
/*
 * class to perform logical volume operations with VA
 */
class EtvaLogicalvolume_VA
{
    private $etva_lv; // EtvaLogicalvolume object    
    private $missing_lvs; // array lvs uuids that should be on VA response (shared lvs only)    

    const LVCREATE = 'lvcreate';
    const LVREMOVE = 'lvremove';
    const LVRESIZE = 'lvresize';

    const LVINIT = 'lvinit';

    public function EtvaLogicalvolume_VA(EtvaLogicalvolume $etva_lv = null)
    {
        $this->missing_lvs = array();
        $this->etva_lv = new EtvaLogicalvolume();        
        
        if($etva_lv) $this->etva_lv = $etva_lv;
    }


    /*
     * send lvcreate
     */
    public function send_create(EtvaNode $etva_node,$size)
    {        
        $method = self::LVCREATE;

        $lv = $this->etva_lv->getLv();

        $etva_vg = $this->etva_lv->getEtvaVolumegroup();
        $vg = $etva_vg->getVg();        

        $is_DiskFile = ($vg == sfConfig::get('app_volgroup_disk_flag')) ? 1:0;

        $params = array(
                    'lv'    => $is_DiskFile ? $etva_node->getStoragedir().'/'.$lv : $lv,
                    'vg'    => $vg,
                    'size'  => $size);
        
        $response = $etva_node->soapSend($method,$params);
        $result = $this->processResponse($etva_node, $response, $method);
        return $result;
    }

    /*
     * send lvremove
     */
    public function send_remove(EtvaNode $etva_node)
    {
        $method = self::LVREMOVE;

        $etva_lv = $this->etva_lv;
        $lv = $etva_lv->getLv();

        /*
         * check if is not system lv
         */
        if($etva_lv->getMounted()){

            $msg = Etva::getLogMessage(array('name'=>$lv), EtvaLogicalvolumePeer::_ERR_SYSTEM_LV_);
            $msg_i18n = sfContext::getInstance()->getI18N()->__(EtvaLogicalvolumePeer::_ERR_SYSTEM_LV_,array('%name%'=>$lv));

            $error = array('success'=>false,'agent'=>$etva_node->getName(),'error'=>$msg_i18n,'info'=>$msg_i18n);
            
            //notify system log
            $message = Etva::getLogMessage(array('name'=>$lv,'info'=>$msg), EtvaLogicalvolumePeer::_ERR_REMOVE_);
            sfContext::getInstance()->getEventDispatcher()->notify(new sfEvent($etva_node->getName(), 'event.log',array('message' => $message,'priority'=>EtvaEventLogger::ERR)));

            return $error;
        }

        /*
         * if logical volume in use do not delete
         */
        if($etva_lv->getInUse()){


            $etva_server = $etva_lv->getEtvaServer();

            $msg = Etva::getLogMessage(array('name'=>$lv,'server'=>$etva_server->getName()), EtvaLogicalvolumePeer::_ERR_INUSE_);
            $msg_i18n = sfContext::getInstance()->getI18N()->__(EtvaLogicalvolumePeer::_ERR_INUSE_,array('%name%'=>$lv,'%server%'=>$etva_server->getName()));


            $error = array('success'=>false,
                           'agent'=>$etva_node->getName(),
                           'error'=>$msg_i18n,
                           'info'=>$msg_i18n);

            //notify system log
            $message = Etva::getLogMessage(array('name'=>$lv,'info'=>$msg), EtvaLogicalvolumePeer::_ERR_REMOVE_);
            sfContext::getInstance()->getEventDispatcher()->notify(new sfEvent($etva_node->getName(), 'event.log',array('message' => $message,'priority'=>EtvaEventLogger::ERR)));
            
            return $error;

        }


        // prepare soap info....

        $lvdevice = $etva_lv->getLvdevice();
        $etva_vg = $etva_lv->getEtvaVolumegroup();
        $vgname = $etva_vg->getVg();
        

        $params = array(
                        'lv' => $lvdevice,
                        'vg' => $vgname);


        // send soap request
        $response = $etva_node->soapSend($method,$params);
        $result = $this->processResponse($etva_node,$response,$method);
        return $result;


    }

    /*
     * send lvresize
     */
    public function send_resize(EtvaNode $etva_node, $size)
    {
        $method = self::LVRESIZE;

        $etva_lv = $this->etva_lv;
        $lv = $etva_lv->getLv();

        /*
         * check if is not system lv
         */
        if($etva_lv->getMounted()){

            $msg = Etva::getLogMessage(array('name'=>$lv), EtvaLogicalvolumePeer::_ERR_SYSTEM_LV_);
            $msg_i18n = sfContext::getInstance()->getI18N()->__(EtvaLogicalvolumePeer::_ERR_SYSTEM_LV_,array('%name%'=>$lv));

            $error = array('success'=>false,'agent'=>$etva_node->getName(),'error'=>$msg_i18n,'info'=>$msg_i18n);

            //notify system log
            $message = Etva::getLogMessage(array('name'=>$lv,'size'=>$size,'info'=>$msg), EtvaLogicalvolumePeer::_ERR_RESIZE_);
            sfContext::getInstance()->getEventDispatcher()->notify(new sfEvent($etva_node->getName(), 'event.log',array('message' => $message,'priority'=>EtvaEventLogger::ERR)));

            return $error;
        }
        

        /*
         * check if can be resized to the specified size...
         */
        if(!$etva_lv->canResizeTo($size)){

            $msg = Etva::getLogMessage(array(), EtvaLogicalvolumePeer::_ERR_INVALIDSIZE_);
            $msg_i18n = sfContext::getInstance()->getI18N()->__(EtvaLogicalvolumePeer::_ERR_INVALIDSIZE_);

            $error = array('success'=>false,'agent'=>$etva_node->getName(),'error'=>$msg_i18n,'info'=>$msg_i18n);

            //notify system log
            $message = Etva::getLogMessage(array('name'=>$lv,'size'=>$size,'info'=>$msg), EtvaLogicalvolumePeer::_ERR_RESIZE_);
            sfContext::getInstance()->getEventDispatcher()->notify(new sfEvent($etva_node->getName(), 'event.log',array('message' => $message,'priority'=>EtvaEventLogger::ERR)));            

            return $error;
        }        


        // prepare soap info....

        $lvdevice = $etva_lv->getLvdevice();                
        
        $params = array(
                        'lv'    => $lvdevice,
                        'size'  => $size);

        // send soap request
        $response = $etva_node->soapSend($method,$params);
        $result = $this->processResizeResponse($etva_node,$response,$method,$size);
        return $result;


    }

    /*
     * process response for LVREMOVE, LVCREATE
     */
    public function processResponse($etva_node, $response, $method)
    {
        $ok = 1;
        $etva_lv = $this->etva_lv;
        $lv = $etva_lv->getLv();
        $etva_vg = $etva_lv->getEtvaVolumegroup();


        switch($method){
            case self::LVREMOVE :
                                $msg_ok_type = EtvaLogicalvolumePeer::_OK_REMOVE_;
                                $msg_err_type = EtvaLogicalvolumePeer::_ERR_REMOVE_;
                                break;
            case self::LVCREATE :
                                $msg_ok_type = EtvaLogicalvolumePeer::_OK_CREATE_;
                                $msg_err_type = EtvaLogicalvolumePeer::_ERR_CREATE_;                                
                                break;
        }

        if($response['success']){

            $response_decoded = (array) $response['response'];            
            $returned_object = (array) $response_decoded['_obj_'];

            /*
             * update vg info
             */
            $vgInfo = $returned_object[EtvaLogicalvolume::VOLUMEGROUP_MAP];
            $vg_info = (array) $vgInfo;            

            switch($method){
                case self::LVREMOVE :
                                    
                                    // removes logical volume
                                    $insert_id = $etva_lv->getId();
                                    $lv_type = $etva_lv->getStorageType();
                                    $etva_lv->delete();
                                    $etva_vg->initData($vg_info);
                                    $etva_vg->save();
                                    break;
                case self::LVCREATE :                                    

                                    $etva_vg->initData($vg_info);
                                    // update logical volume
                                    $etva_lv->setEtvaCluster($etva_node->getEtvaCluster());                                    
                                    $etva_lv->initData($returned_object);

                                    $etva_node_lv = new EtvaNodeLogicalvolume();

                                    $etva_node_lv->setEtvaLogicalvolume($etva_lv);
                                    $etva_node_lv->setEtvaNode($etva_node);
                                    $etva_node_lv->save();

                                    $insert_id = $etva_lv->getId();
                                    $lv_type = $etva_lv->getStorageType();

                                    break;                
            }            

            if($lv_type!=EtvaLogicalvolume::STORAGE_TYPE_LOCAL_MAP)
            {

                /*
                 * if storage type not local send update to nodes...
                 */
                $bulk_update = $this->send_update($etva_node);
                if(!empty($bulk_update))
                {
//                    $response = $bulk_update;
                    $ok = 0;
                }

            }

            if($ok)
            {
                //notify system log
                $message = Etva::getLogMessage(array('name'=>$lv), $msg_ok_type);
                $msg_i18n = sfContext::getInstance()->getI18N()->__($msg_ok_type,array('%name%'=>$lv));

                sfContext::getInstance()->getEventDispatcher()->notify(new sfEvent($etva_node->getName(), 'event.log',array('message' => $message)));
                
                $result = array('success'=>true, 'agent'=>$response['agent'], 'response'=>$msg_i18n,'insert_id'=>$insert_id);

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

            if($this->missing_lvs)
            {
                $errors = $this->missing_lvs;
                $names = array();
                foreach($errors as $node_id=>$error_data)
                {
                    $names[] = $error_data['name'];

                }
                
                $message = Etva::getLogMessage(array('info'=>implode(', ',$names)), EtvaLogicalvolumePeer::_ERR_INCONSISTENT_);
                sfContext::getInstance()->getEventDispatcher()->notify(new sfEvent($etva_node->getName(),'event.log',array('message' =>$message,'priority'=>EtvaEventLogger::ERR)));

                return array('success'=>false,'agent'=>'ETVA','error'=>$message,'action'=>'reload','info'=>$message);


            }else
            {
                $message = Etva::getLogMessage(array('name'=>$lv,'info'=>$response['info']), $msg_err_type);

                $msg_i18n = sfContext::getInstance()->getI18N()->__($msg_err_type,array('%name%'=>$lv,'%info%'=>$response['info']));
                $result['error'] = $msg_i18n;
            
                sfContext::getInstance()->getEventDispatcher()->notify(new sfEvent($response['agent'], 'event.log',array('message' => $message,'priority'=>EtvaEventLogger::ERR)));
            
                return  $result;
            
            }
        }

    }


    /*
     * process soap response for LVRESIZE
     */
    private function processResizeResponse($etva_node,$response,$method,$size)
    {
        $ok = 1;
        $etva_lv = $this->etva_lv;
        $lv = $etva_lv->getLv();
        $etva_vg = $this->etva_lv->getEtvaVolumegroup();

        if($response['success']){

            $response_decoded = (array) $response['response'];
            $returned_status = $response_decoded['_okmsg_'];
            $returned_object = (array) $response_decoded['_obj_'];

            /*
             * update vg info
             */
            $vgInfo = $returned_object[EtvaLogicalvolume::VOLUMEGROUP_MAP];
            $vg_info = (array) $vgInfo;
            $etva_vg->initData($vg_info);

            
                
            $msg_ok_type = EtvaLogicalvolumePeer::_OK_RESIZE_;
            $msg_err_type = EtvaLogicalvolumePeer::_ERR_RESIZE_;
            //update logical, volume group and physical sizes
            $etva_lv->initData($returned_object);
            $etva_lv->save();
            $lv_type = $etva_lv->getStorageType();        

            if($lv_type!=EtvaLogicalvolume::STORAGE_TYPE_LOCAL_MAP)
            {

                /*
                 * if storage type not local send update to nodes...
                 */
                $bulk_update = $this->send_update($etva_node);
                if(!empty($bulk_update)){
                    $response = $bulk_update;
                    $ok = 0;
                }
            }

            if($ok)
            {

                //notify system log
                $message = Etva::getLogMessage(array('name'=>$lv,'size'=>$size), $msg_ok_type);
                $msg_i18n = sfContext::getInstance()->getI18N()->__($msg_ok_type,array('%name%'=>$lv,'%size%'=>$size));
                
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

            $msg_i18n = sfContext::getInstance()->getI18N()->__($msg_err_type,array('%name%'=>$lv,'%size%'=>$size,'%info%'=>$response['info']));
            $result['error'] = $msg_i18n;

            $message = Etva::getLogMessage(array('name'=>$lv,'size'=>$size,'info'=>$response['info']), $msg_err_type);
            sfContext::getInstance()->getEventDispatcher()->notify(new sfEvent($response['agent'], 'event.log',array('message' => $message,'priority'=>EtvaEventLogger::ERR)));

            return  $result;

        }

    }


    /*
     * initialize info based on passed data. check if exists on db and if matches info on DB
     */
    public function initialize(EtvaNode $etva_node,$lvs)
    {

        $etva_cluster = $etva_node->getEtvaCluster();
        $logical_names = array();

        /*
         * check shared lvs consistency
         */
        $consist = $this->check_shared_consistency($etva_node,$lvs);        

        if(!$consist){
            $errors = $this->missing_lvs[$etva_node->getId()];

            $inconsistent_message = Etva::getLogMessage(array('info'=>''), EtvaLogicalvolumePeer::_ERR_INCONSISTENT_);

            $etva_node->setErrorMessage(self::LVINIT);

            $message = Etva::getLogMessage(array('info'=>$inconsistent_message), EtvaLogicalvolumePeer::_ERR_SOAPUPDATE_);
            sfContext::getInstance()->getEventDispatcher()->notify(new sfEvent($etva_node->getName(),'event.log',array('message' =>$message,'priority'=>EtvaEventLogger::ERR)));

            return array('success'=>false,'error'=>$errors);
        }

        foreach($lvs as $lvInfo)
        {
            $lv_info = (array) $lvInfo;            
            $lv_dev = $lv_info[EtvaLogicalvolume::LVDEVICE_MAP];
            $lv_type = $lv_info[EtvaLogicalvolume::STORAGE_TYPE_MAP];
            $lv_uuid = isset($lv_info[EtvaLogicalvolume::UUID_MAP]) ? $lv_info[EtvaLogicalvolume::UUID_MAP] : '';

            // vg info
            $vg_info = (array) $lv_info[EtvaLogicalvolume::VOLUMEGROUP_MAP];
            $vg_name = $vg_info[EtvaVolumegroup::VG_MAP];
            $vg_type = $vg_info[EtvaVolumegroup::STORAGE_TYPE_MAP];
            $vg_uuid = isset($vg_info[EtvaVolumegroup::UUID_MAP]) ? $vg_info[EtvaVolumegroup::UUID_MAP] : '';

            //get volume group based on node, type, uuid and vg
            $etva_volgroup = EtvaVolumegroupPeer::retrieveByNodeTypeUUIDVg($etva_node->getId(), $vg_type, $vg_uuid, $vg_name);

            if($lv_type == EtvaLogicalvolume::STORAGE_TYPE_LOCAL_MAP){
                $etva_logicalvol = EtvaLogicalvolumePeer::retrieveByNodeTypeLvDevice($etva_node->getId(), $lv_type, $lv_dev);
            }else{
                $etva_logicalvol = EtvaLogicalvolumePeer::retrieveByUUID($lv_uuid);
            }

            if(!$etva_logicalvol){ // no lv in db...so create new one
                $etva_node_logicalvol = new EtvaNodeLogicalvolume();
                $etva_logicalvol = new EtvaLogicalvolume();

            }
            else{
                //if lv already in DB we need to make sure if already exists association with node. if not create new one
                $etva_node_logicalvol = EtvaNodeLogicalvolumePeer::retrieveByPK($etva_node->getId(), $etva_logicalvol->getId());
                if(!$etva_node_logicalvol) $etva_node_logicalvol = new EtvaNodeLogicalvolume();
            }


            $etva_logicalvol->initData($lv_info);
            $etva_logicalvol->setEtvaVolumegroup($etva_volgroup);
            $etva_logicalvol->setEtvaCluster($etva_cluster);

            $etva_node_logicalvol->setEtvaLogicalvolume($etva_logicalvol);
            $etva_node_logicalvol->setEtvaNode($etva_node);
            $etva_node_logicalvol->save();

            
            $logical_names[] = $etva_logicalvol->getLv();


        }

        /*
         * check if is an appliance restore operation...
         */
        $apli = new Appliance();
        $action = $apli->getStage(Appliance::RESTORE_STAGE);
        if($action) $apli->setStage(Appliance::RESTORE_STAGE,Appliance::VA_UPDATE_LVS);

        $etva_node->clearErrorMessage(self::LVINIT);


        $message = Etva::getLogMessage(array('info'=>implode(', ',$logical_names)), EtvaLogicalvolumePeer::_OK_SOAPUPDATE_);
        sfContext::getInstance()->getEventDispatcher()->notify(
            new sfEvent($etva_node->getName(),
                        'event.log',
                        array('message' =>$message)
            ));
        return array('success'=>true,'response'=>$logical_names);
        


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
        $bulk_responses = $etva_cluster->soapSend('getlvs',array('force'=>1),$etva_node);

        $errors = array();

        foreach($bulk_responses as $node_id =>$node_response){

            if($node_response['success']){ //response received ok

                $node = EtvaNodePeer::retrieveByPK($node_id);

                //try initialize data from response
                $node_init = $this->initialize($node,(array) $node_response['response']);

                if(!$node_init['success'])
                {
                    $errors[$node_id] = $node_init;
                }
                else{
                    //notify system log
                    $message = Etva::getLogMessage(array(), EtvaLogicalvolumePeer::_OK_SOAPREFRESH_);
                    sfContext::getInstance()->getEventDispatcher()->notify(new sfEvent($node_response['agent'], 'event.log',array('message' => $message)));
                }


            }else
            {

                //response not received

                $message = Etva::getLogMessage(array('info'=>$node_response['info']), EtvaLogicalvolumePeer::_ERR_SOAPREFRESH_);
                sfContext::getInstance()->getEventDispatcher()->notify(new sfEvent($node_response['agent'], 'event.log',array('message' => $message,'priority'=>EtvaEventLogger::ERR)));

            }
        }

        return $errors;

    }

    /*
     * check for consistency between soap response shareed items and DB shared info
     *
     */
    private function check_shared_consistency($etva_node,$response)
    {
        $node_id = $etva_node->getId();
        $etva_cluster = $etva_node->getEtvaCluster();
        $db_ = $etva_cluster->getSharedLvs();        
        if(!$db_) $db_ = array();

        $in_db_uuids = array();
        $db_uuids = array();

        //build uuid array from DB where vgs are type 'storage'
        foreach($db_ as $data){
            $data_uuid = $data->getUuid();
            $db_uuids[$data_uuid] = $data;
        }

        $in_db_uuids = array_keys($db_uuids);


        $tam_db = count($in_db_uuids);
        $in_resp = (array) $response;
        $in_resp_arr = array();

        // build uuid array from soap response with type 'shared'
        foreach($in_resp as $data_info){
            $info = (array) $data_info;            
            if($info[EtvaLogicalvolume::STORAGE_TYPE_MAP]!=EtvaLogicalvolume::STORAGE_TYPE_LOCAL_MAP) $in_resp_arr[] = $info[EtvaLogicalvolume::UUID_MAP];
        }


        $consistent = 1;
        $tam_resp = count($in_resp);
        $i=0;
        while($i<$tam_db){
            if(!in_array($in_db_uuids[$i],$in_resp_arr)){
                $consistent = 0;
                $this->missing_lvs[$node_id]['uuid'][] = $in_db_uuids[$i];
                $this->missing_lvs[$node_id]['name'] = $etva_node->getName();
            }
            $i++;
        }

        return $consistent;

    }
    
}
