<?php
/*
 * class to perform physical volume operations with VA
 */

class EtvaVolumegroup_VA
{
    private $etva_vg;
    private $params; // array to set request parameters
    private $missing_vgs; // array vgs uuids that should be on VA response (shared vgs only)

    const VGCREATE = 'vgcreate';
    const VGEXTEND = 'vgextend';
    const VGREDUCE = 'vgreduce';
    const VGREMOVE = 'vgremove';

    const VGINIT = 'vginit';
        
    public function EtvaVolumegroup_VA(EtvaVolumegroup $etva_vg = null)
    {
        $this->missing_vgs = array();
        $this->params = array();

        $this->etva_vg = new EtvaVolumegroup();

        if($etva_vg) $this->etva_vg = $etva_vg;
    }

    /*
     * send vgcreate
     */
    public function send_create(EtvaNode $etva_node, $pvs)
    {      
        $method = self::VGCREATE;

        $return = $this->processParams($etva_node,$pvs,$method);
        //error processing parameters
        if(!$return['success']) return $return;
        
        $params = $this->params;

        $response = $etva_node->soapSend($method,$params);
        $result = $this->processResponse($etva_node,$response,$method);
        return $result;
    }

    /*
     * send vgextend
     */
    public function send_extend(EtvaNode $etva_node, $pvs)
    {        
        $method = self::VGEXTEND;

        $return = $this->processParams($etva_node,$pvs,$method);
        //error processing parameters
        if(!$return['success']) return $return;

        $params = $this->params;

        $response = $etva_node->soapSend($method,$params);
        $result = $this->processResponse($etva_node,$response,$method);
        return $result;
    }

    /*
     * send vgreduce
     */
    public function send_reduce(EtvaNode $etva_node, $pvs)
    {
        $method = self::VGREDUCE;

        $return = $this->processParams($etva_node,$pvs,$method);
        //error processing parameters
        if(!$return['success']) return $return;

        $params = $this->params;

        $response = $etva_node->soapSend($method,$params);
        $result = $this->processResponse($etva_node,$response,$method);
        return $result;
    }

    /*
     * send vgremove
     */
    public function send_remove($etva_node)
    {
        $method = self::VGREMOVE;
        $etva_vg = $this->etva_vg;
        $vg = $etva_vg->getVg();

        /*
         * check if as already lvs...
         */
        $vg_lvs = $etva_vg->getEtvaLogicalvolumes();
        if(count($vg_lvs)>0)
        {
            $msg = Etva::getLogMessage(array('name'=>$vg), EtvaVolumegroupPeer::_LVASSOC_);
            $msg_i18n = sfContext::getInstance()->getI18N()->__(EtvaVolumegroupPeer::_LVASSOC_,array('%name%'=>$vg));

            $error = array('success' => false,
                           'agent'   => $etva_node->getName(),
                           'error'   => $msg_i18n,
                           'info'    => $msg_i18n);

            //notify system log
            $message = Etva::getLogMessage(array('name'=>$vg,'info'=>$msg), EtvaVolumegroupPeer::_ERR_REMOVE_);
            sfContext::getInstance()->getEventDispatcher()->notify(new sfEvent($error['agent'], 'event.log',array('message' => $message,'priority'=>EtvaEventLogger::ERR)));

            return $error;
        }

        /*
         * checks is vg is the special volume group __DISK__
         */

        $is_DiskVG = ($vg == sfConfig::get('app_volgroup_disk_flag')) ? 1:0;
        if($is_DiskVG)
        {
            
            $msg = Etva::getLogMessage(array('name'=>$vg), EtvaVolumegroupPeer::_SPECIAL_NODEL_);
            $msg_i18n = sfContext::getInstance()->getI18N()->__(EtvaVolumegroupPeer::_SPECIAL_NODEL_,array('%name%'=>$vg));
            $error = array('success'=>false,'agent'=>$etva_node->getName(),'info'=>$msg_i18n);

            //notify system log
            $message = Etva::getLogMessage(array('name'=>$vg,'info'=>$msg), EtvaVolumegroupPeer::_ERR_REMOVE_);
            sfContext::getInstance()->getEventDispatcher()->notify(new sfEvent($error['agent'], 'event.log',array('message' => $message,'priority'=>EtvaEventLogger::ERR)));

            return  $error;
        }
        
        $params = array('vgname'=>$vg);

        $response = $etva_node->soapSend($method,$params);
        $result = $this->processRemoveResponse($etva_node,$response);
        return $result;

    }


    /*
     * build paramaters to send to VA. used in vgcreate, vgextend and vgreduce
     */
    private function processParams($etva_node,$pvs,$method)
    {
        $etva_pvs = array();
        $params = array();

        $i = 0;

        switch($method)
        {
            case 'vgextend':                            
                            $msg_err_type = EtvaVolumegroupPeer::_ERR_EXTEND_;
                            break;
            case 'vgcreate':                            
                            $msg_err_type = EtvaVolumegroupPeer::_ERR_CREATE_;
                            break;
            case 'vgreduce':
                            $msg_err_type = EtvaVolumegroupPeer::_ERR_REDUCE_;
                            break;
        }

        // check if physical volumes exist in DB and are allocatable
        foreach($pvs as $pv)
        {

            // get DB info by primary key
            if(!$etva_pvs[$i] = $etva_node->retrievePhysicalvolumeByPv($pv))
            {
                $msg = Etva::getLogMessage(array('name'=>$etva_node->getName(),'pv'=>$pv), EtvaNodePeer::_ERR_NOPV_);
                $msg_i18n = sfContext::getInstance()->getI18N()->__(EtvaNodePeer::_ERR_NOPV_,array('%name%'=>$etva_node->getName(),'%pv%'=>$pv));

                $error = array('success'=>false,'error'=>$msg_i18n);

                //notify event log
                $message = Etva::getLogMessage(array('name'=>$vg,'info'=>$msg), $msg_err_type );
                sfContext::getInstance()->getEventDispatcher()->notify(new sfEvent($etva_node->getName(), 'event.log',array('message' => $message,'priority'=>EtvaEventLogger::ERR)));                   
                
                return $error;
            }

            if($method!=self::VGREDUCE && !$etva_pvs[$i]->getAllocatable()){

                $msg = Etva::getLogMessage(array('name'=>$etva_pvs[$i]->getPv()), EtvaPhysicalvolumePeer::_NOT_AVAILABLE_);
                $msg_i18n = sfContext::getInstance()->getI18N()->__(EtvaPhysicalvolumePeer::_NOT_AVAILABLE_,array('%name%'=>$etva_pvs[$i]->getPv()));

                $error = array('success'=>false,'error'=> $msg_i18n);

                //notify event log
                $message = Etva::getLogMessage(array('name'=>$vg,'info'=>$msg), $msg_err_type);
                sfContext::getInstance()->getEventDispatcher()->notify(new sfEvent($etva_node->getName(), 'event.log',array('message' => $message,'priority'=>EtvaEventLogger::ERR)));
                
                return $error;

            }

            $pvIndex = 'pv'.$etva_pvs[$i]->getId();
            $pv_type = $etva_pvs[$i]->getStorageType();
            $uuid = $etva_pvs[$i]->getUuid();

            if($pv_type==EtvaVolumegroup::STORAGE_TYPE_LOCAL_MAP)
                $params[$pvIndex] = $etva_pvs[$i]->getPv();
            else
                $params[$pvIndex] = $uuid;
                
            $i++;

        }

        /*
         * check if pvs are all of the same storage type
         */
        $count_pvs = count($etva_pvs);
        $i = 0;
        for($i;$i<$count_pvs;$i++)
        {
            $storage_type = $etva_pvs[$i]->getStorageType();
            $next = $i+1;
            if($next<$count_pvs)
            {
                $next_storage_type = $etva_pvs[$next]->getStorageType();
                if($storage_type!=$next_storage_type)
                {
                
                    $msg = Etva::getLogMessage(EtvaPhysicalvolumePeer::_MISMATCH_STORAGE_);
                    $msg_i18n = sfContext::getInstance()->getI18N()->__(EtvaPhysicalvolumePeer::_MISMATCH_STORAGE_);

                    $error = array('success'=>false,'error'=> $msg_i18n);

                    //notify event log
                    $message = Etva::getLogMessage(array('name'=>$vg,'info'=>$msg), $msg_err_type);
                    sfContext::getInstance()->getEventDispatcher()->notify(new sfEvent($etva_node->getName(), 'event.log',array('message' => $message,'priority'=>EtvaEventLogger::ERR)));

                    return $error;                    

                }
            }

        }       
        
        $params['vgname'] = $this->etva_vg->getVg();
        $this->params = $params;

        return array('success'=>true);

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
        $bulk_responses = $etva_cluster->soapSend('getvgpvs',array('force'=>1),$etva_node);
        $errors = array();

        foreach($bulk_responses as $node_id =>$node_response)
        {
            
            if($node_response['success']){ //response received ok

                $node = EtvaNodePeer::retrieveByPK($node_id);

                //try initialize data from response
                $node_init = $this->initialize($node,(array) $node_response['response']);

                if(!$node_init['success'])
                {
                    $errors[$node_id] = $node_init;
                }
                else
                {
                    //notify system log
                    $message = Etva::getLogMessage(array(), EtvaVolumegroupPeer::_OK_SOAPREFRESH_);
                    sfContext::getInstance()->getEventDispatcher()->notify(new sfEvent($node_response['agent'], 'event.log',array('message' => $message)));
                }
                

            }else
            {
                //response not received

                $message = Etva::getLogMessage(array('info'=>$node_response['info']), EtvaVolumegroupPeer::_ERR_SOAPREFRESH_);
                sfContext::getInstance()->getEventDispatcher()->notify(new sfEvent($node_response['agent'], 'event.log',array('message' => $message,'priority'=>EtvaEventLogger::ERR)));
            }
        }

        return $errors;       

    }


    private function assoc_pvs($etva_node, $pvs,$method)
    {
        $etva_volgroup = $this->etva_vg;

        /*
         * associate pvs with vg and update info
         */
        $pvs = (array) $pvs;
        foreach($pvs as $pvInfo)
        {

            $pv_info = (array) $pvInfo;

            $pv_type = $pv_info[EtvaPhysicalvolume::STORAGE_TYPE_MAP];
            $pv_uuid = isset($pv_info[EtvaPhysicalvolume::UUID_MAP]) ? $pv_info[EtvaPhysicalvolume::UUID_MAP] : '';
            $pv_device = $pv_info[EtvaPhysicalvolume::DEVICE_MAP];

            //get physical volume based on node, type, uuid and device
            $etva_physical = EtvaPhysicalvolumePeer::retrieveByNodeTypeUUIDDevice($etva_node->getId(), $pv_type, $pv_uuid, $pv_device);
            $etva_physical->initData($pv_info);
            $etva_physical->save();

            $etva_node_physical = EtvaNodePhysicalvolumePeer::retrieveByPK($etva_node->getId(), $etva_physical->getId());
            $etva_node_physical->setDevice($pv_device);
            $etva_node_physical->save();

            if($method!=self::VGREMOVE)
            {
                $etva_volgroup_physical = EtvaVolumePhysicalPeer::retrieveByPK($etva_volgroup->getId(), $etva_physical->getId());

                if($method==self::VGREDUCE)
                {
                    $etva_volgroup_physical->delete();

                }else
                {
                    if(!$etva_volgroup_physical) $etva_volgroup_physical = new EtvaVolumePhysical();
                    $etva_volgroup_physical->setEtvaPhysicalvolume($etva_physical);
                    $etva_volgroup_physical->setEtvaVolumegroup($etva_volgroup);
                    $etva_volgroup_physical->save();
                }
            }

        }

    }

    

    /*
     * process vgcreate, vgextend and vgreduce response
     */
    private function processResponse($etva_node,$response,$method)
    {        
        
        switch($method){
            case 'vgextend':
                            $msg_ok_type = EtvaVolumegroupPeer::_OK_EXTEND_;
                            $msg_err_type = EtvaVolumegroupPeer::_ERR_EXTEND_;
                            break;
            case 'vgcreate':
                            $msg_ok_type = EtvaVolumegroupPeer::_OK_CREATE_;
                            $msg_err_type = EtvaVolumegroupPeer::_ERR_CREATE_;
                            break;
            case 'vgreduce':
                            $msg_ok_type = EtvaVolumegroupPeer::_OK_REDUCE_;
                            $msg_err_type = EtvaVolumegroupPeer::_ERR_REDUCE_;
                            break;
        }

        $etva_volgroup = $this->etva_vg;
        $vg = $etva_volgroup->getVg();
        $ok = 1;
        
        if($response['success']){

            $response_decoded = (array) $response['response'];
            $returned_status = $response_decoded['_okmsg_'];
            $returned_object = (array) $response_decoded['_obj_'];

            // set volume group info
            $etva_cluster = $etva_node->getEtvaCluster();
            $etva_volgroup->initData((array) $returned_object);

            $etva_volgroup->setEtvaCluster($etva_cluster);

            $etva_node_volgroup = EtvaNodeVolumegroupPeer::retrieveByPK($etva_node->getId(), $etva_volgroup->getId());
            if(!$etva_node_volgroup) $etva_node_volgroup = new EtvaNodeVolumegroup();

            $etva_node_volgroup->setEtvaVolumegroup($etva_volgroup);
            $etva_node_volgroup->setEtvaNode($etva_node);
            $etva_node_volgroup->save();


            /*
             * associate pvs with vg
             */
            $pvs = (array) $returned_object[EtvaVolumegroup::PHYSICALVOLUMES_MAP];
            $this->assoc_pvs($etva_node, $pvs, $method);
                        

            if($etva_volgroup->getStorageType()!=EtvaVolumegroup::STORAGE_TYPE_LOCAL_MAP)
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

                $pvs_info = $this->params;
                unset($pvs_info['vgname']);
                $pvs_info = implode(', ',$pvs_info);
                //notify event log
                $message = Etva::getLogMessage(array('name'=>$vg,'pvs'=>$pvs_info), $msg_ok_type);
                sfContext::getInstance()->getEventDispatcher()->notify(
                    new sfEvent($response['agent'], 'event.log',
                        array('message' => $message)));


                $msg_i18n = sfContext::getInstance()->getI18N()->__($msg_ok_type,array('%name%'=>$vg,'%pvs%'=>$pvs_info));

                $result = array('success'=>true, 'agent'=>$response['agent'], 'response'=>$msg_i18n);


                return  $result;
            }

        }else
        {
            $ok = 0;
        }

        // soap response error....
        // DB information not updated
        if($ok==0){



            $result = $response;

            if($this->missing_vgs)
            {
                $errors = $this->missing_vgs;
                $names = array();
                foreach($errors as $node_id=>$error_data)
                {
                    $names[] = $error_data['name'];

                }

                $message = Etva::getLogMessage(array('info'=>implode(', ',$names)), EtvaVolumegroupPeer::_ERR_INCONSISTENT_);
                sfContext::getInstance()->getEventDispatcher()->notify(new sfEvent($etva_node->getName(),'event.log',array('message' =>$message,'priority'=>EtvaEventLogger::ERR)));

                return array('success'=>false,'agent'=>'ETVA','error'=>$message,'action'=>'reload','info'=>$message);


            }else
            {
                $message = Etva::getLogMessage(array('name'=>$vg,'info'=>$response['info']), $msg_err_type);

                $msg_i18n = sfContext::getInstance()->getI18N()->__($msg_err_type,array('%name%'=>$vg,'%info%'=>$response['info']));
                $result['error'] = $msg_i18n;

                sfContext::getInstance()->getEventDispatcher()->notify(new sfEvent($response['agent'], 'event.log',array('message' => $message,'priority'=>EtvaEventLogger::ERR)));

                return  $result;

            }

        }

    }


    /*
     * process volume group remove response
     */
    public function processRemoveResponse($etva_node,$response)
    {

        $etva_volgroup = $this->etva_vg;
        $vg = $etva_volgroup->getVg();
        $ok = 1;
        if($response['success']){

            $response_decoded = (array) $response['response'];
            $returned_status = $response_decoded['_okmsg_'];
            $returned_object = (array) $response_decoded['_obj_'];

            // removes volgroup and updates physical volume info
            $vg_type = $etva_volgroup->getStorageType();

            $pvs = (array) $returned_object[EtvaVolumegroup::PHYSICALVOLUMES_MAP];
            $this->assoc_pvs($etva_node, $pvs,'vgremove');

            $etva_volgroup->delete();

            if($vg_type!=EtvaVolumegroup::STORAGE_TYPE_LOCAL_MAP)
            {

                //if vg remove send physical volumes update to other nodes to update physical volume device tag
                $pv = new EtvaPhysicalvolume_VA();
                $bulk_update = $pv->send_update($etva_node);
                
                /*
                 * if storage type not local send update to nodes...
                 */
                //$bulk_update = $this->send_update($etva_node);
                if(!empty($bulk_update))
                {
                    //$response = $bulk_update;
                    $ok = 0;
                }
            }
            

            if($ok)
            {
                $message = Etva::getLogMessage(array('name'=>$vg), EtvaVolumegroupPeer::_OK_REMOVE_);
                sfContext::getInstance()->getEventDispatcher()->notify(new sfEvent($response['agent'], 'event.log',array('message' => $message)));

                $msg_i18n = sfContext::getInstance()->getI18N()->__(EtvaVolumegroupPeer::_OK_REMOVE_,array('%name%'=>$vg));

                $result = array('success'=>true, 'agent'=>$response['agent'], 'response'=>$msg_i18n);

                return  $result;
            }

        }else
        {
            $ok = 0;
        }

        // soap response error....
        // DB information not updated
        if($ok==0){

            $result = $response;


            if($this->missing_vgs)
            {
                $errors = $this->missing_vgs;
                $names = array();
                foreach($errors as $node_id=>$error_data)
                {
                    $names[] = $error_data['name'];

                }

                $message = Etva::getLogMessage(array('info'=>implode(', ',$names)), EtvaVolumegroupPeer::_ERR_INCONSISTENT_);
                sfContext::getInstance()->getEventDispatcher()->notify(new sfEvent($etva_node->getName(),'event.log',array('message' =>$message,'priority'=>EtvaEventLogger::ERR)));

                return array('success'=>false,'agent'=>'ETVA','error'=>$message,'action'=>'reload','info'=>$message);


            }else
            {
                $message = Etva::getLogMessage(array('name'=>$vg,'info'=>$response['info']), EtvaVolumegroupPeer::_ERR_REMOVE_);

                $msg_i18n = sfContext::getInstance()->getI18N()->__(EtvaVolumegroupPeer::_ERR_REMOVE_,array('%name%'=>$vg,'%info%'=>$response['info']));
                $result['error'] = $msg_i18n;

                sfContext::getInstance()->getEventDispatcher()->notify(new sfEvent($response['agent'], 'event.log',array('message' => $message,'priority'=>EtvaEventLogger::ERR)));

                return  $result;

            }

        }

    }

    /*
     * initialize info based on passed data. check if exists on db and if matches info on DB
     */
    public function initialize(EtvaNode $etva_node,$vgs)
    {
        $etva_cluster = $etva_node->getEtvaCluster();
        $volumegroup_names = array();

        /*
         * check shared vgs consistency
         */
        $consist = $this->check_shared_consistency($etva_node,$vgs);        

        if(!$consist){
            $errors = $this->missing_vgs[$etva_node->getId()];

            $inconsistent_message = Etva::getLogMessage(array('info'=>''), EtvaVolumegroupPeer::_ERR_INCONSISTENT_);

            $etva_node->setErrorMessage(self::VGINIT);

            $message = Etva::getLogMessage(array('info'=>$inconsistent_message), EtvaVolumegroupPeer::_ERR_SOAPUPDATE_);
            sfContext::getInstance()->getEventDispatcher()->notify(new sfEvent($etva_node->getName(),'event.log',array('message' =>$message,'priority'=>EtvaEventLogger::ERR)));

            return array('success'=>false,'error'=>$errors);
        }

        foreach($vgs as $vgInfo)
        {
            $vg_info = (array) $vgInfo;            
            $vg_name = $vg_info[EtvaVolumegroup::VG_MAP];
            $vg_type = $vg_info[EtvaVolumegroup::STORAGE_TYPE_MAP];            

            if($vg_type == EtvaVolumegroup::STORAGE_TYPE_LOCAL_MAP){
                $etva_volgroup = EtvaVolumegroupPeer::retrieveByNodeTypeVg($etva_node->getId(), $vg_type, $vg_name);                
            }else{
                $vg_uuid = $vg_info[EtvaVolumegroup::UUID_MAP];
                $etva_volgroup = EtvaVolumegroupPeer::retrieveByUUID($vg_uuid);
            }            
            

            if(!$etva_volgroup){ // no vg in db...so create new one
                $etva_node_volgroup = new EtvaNodeVolumegroup();
                $etva_volgroup = new EtvaVolumegroup();
            }
            else{
                //if vg  already in DB we need to make sure if already exists association with node. if not create new one
                $etva_node_volgroup = EtvaNodeVolumegroupPeer::retrieveByPK($etva_node->getId(), $etva_volgroup->getId());
                if(!$etva_node_volgroup) $etva_node_volgroup = new EtvaNodeVolumegroup();
            }


            $etva_volgroup->initData($vg_info);

            $etva_volgroup->setEtvaCluster($etva_cluster);

            $etva_node_volgroup->setEtvaVolumegroup($etva_volgroup);
            $etva_node_volgroup->setEtvaNode($etva_node);
            $etva_node_volgroup->save();


            /*
             * associate pvs with vg
             */
            $pvs = isset($vg_info[EtvaVolumegroup::PHYSICALVOLUMES_MAP]) ? (array) $vg_info[EtvaVolumegroup::PHYSICALVOLUMES_MAP] : array();
            foreach($pvs as $pvInfo){

                $pv_info = (array) $pvInfo;

                $pv_type = $pv_info[EtvaPhysicalvolume::STORAGE_TYPE_MAP];
                $pv_uuid = isset($pv_info[EtvaPhysicalvolume::UUID_MAP]) ? $pv_info[EtvaPhysicalvolume::UUID_MAP] : '';
                $pv_device = $pv_info[EtvaPhysicalvolume::DEVICE_MAP];

                //get physical volume based on node, type, uuid and device
                $etva_physical = EtvaPhysicalvolumePeer::retrieveByNodeTypeUUIDDevice($etva_node->getId(), $pv_type, $pv_uuid, $pv_device);

                $etva_node_physical = EtvaNodePhysicalvolumePeer::retrieveByPK($etva_node->getId(), $etva_physical->getId());
                $etva_node_physical->setDevice($pv_device);
                $etva_node_physical->save();

                $etva_volgroup_physical = EtvaVolumePhysicalPeer::retrieveByPK($etva_volgroup->getId(), $etva_physical->getId());
                if(!$etva_volgroup_physical) $etva_volgroup_physical = new EtvaVolumePhysical();
                $etva_volgroup_physical->setEtvaPhysicalvolume($etva_physical);
                $etva_volgroup_physical->setEtvaVolumegroup($etva_volgroup);
                $etva_volgroup_physical->save();

            }

            $volumegroup_names[] = $etva_volgroup->getVg();                        
            
        }


        /*
         * check if is an appliance restore operation...
         */
        $apli = new Appliance();
        $action = $apli->getStage(Appliance::RESTORE_STAGE);
        if($action) $apli->setStage(Appliance::RESTORE_STAGE,Appliance::VA_UPDATE_VGS);

        $etva_node->clearErrorMessage(self::VGINIT);
        
        $message = Etva::getLogMessage(array('info'=>implode(', ',$volumegroup_names)), EtvaVolumegroupPeer::_OK_SOAPUPDATE_);
        sfContext::getInstance()->getEventDispatcher()->notify(new sfEvent($etva_node->getName(),'event.log',array('message' =>$message)));
        
        return array('success'=>true,'response'=>$volumegroup_names);            

    }

    /*
     * check consistency response....
     */
    private function check_shared_consistency($etva_node,$response)
    {
        $node_id = $etva_node->getId();
        $etva_cluster = $etva_node->getEtvaCluster();
        $db_ = $etva_cluster->getSharedVgs();
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
        foreach($in_resp as $data_info)
        {
            $info = (array) $data_info;            
            if($info[EtvaVolumegroup::STORAGE_TYPE_MAP]!=EtvaVolumegroup::STORAGE_TYPE_LOCAL_MAP) $in_resp_arr[] = $info[EtvaVolumegroup::UUID_MAP];
        }

        $consistent = 1;
        $tam_resp = count($in_resp);
        $i=0;
        while($i<$tam_db){
            if(!in_array($in_db_uuids[$i],$in_resp_arr)){
                $consistent = 0;
                $this->missing_vgs[$node_id]['uuid'][] = $in_db_uuids[$i];
                $this->missing_vgs[$node_id]['name'] = $etva_node->getName();

            }
            $i++;
        }

        return $consistent;

    }
    
}