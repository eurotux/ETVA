<?php
/*
 * class to perform logical volume operations with VA
 */
class EtvaLogicalvolume_VA
{
    private $etva_lv; // EtvaLogicalvolume object    
    private $missing_lvs; // array lvs uuids that should be on VA response (shared lvs only)    
    private $missing_dtable; // array of devices uuids

    const MUTEX_UPDATE_ID = 555;
    const LVCREATE = 'lvcreate';
    const LVREMOVE = 'lvremove';
    const LVRESIZE = 'lvresize';
    //const LVCLONE  = 'clonedisk_may_fork';
    const LVCLONE  = 'lvclone_may_fork';

    const CREATESNAPSHOT = 'createsnapshot_may_fork';
    const REVERTSNAPSHOT = 'revertsnapshot_may_fork';
    const CONVERTFORMAT = 'convertformat_may_fork';

    const LVINIT = 'lvinit';

    const GET_SYNC_LOGICALVOLUMES = 'getlvs_arr';
    const GET_SYNC_DEVICESTABLE = 'device_table';
    const GET_SYNC_DEVICESLOADTABLE = 'device_loadtable_wrap';
    const GET_SYNC_DEVICESREMOVE = 'device_remove';

    public function EtvaLogicalvolume_VA(EtvaLogicalvolume $etva_lv = null)
    {
        $this->missing_lvs = array();
        $this->missing_dtable = array();
        $this->etva_lv = new EtvaLogicalvolume();        
        
        if($etva_lv) $this->etva_lv = $etva_lv;
    }

    /**
      * original_lv - /dev/... ( lv origem )
      * lv - nome do novo lv
      * vg - nome do vg
      * size - tamanho do lv em Mb
      *
      */
    public function send_clone(EtvaNode $etva_node, EtvaLogicalvolume $original_lv)
    {   
        $method = self::LVCLONE;

        $lv = $this->etva_lv;

        // prepare soap info....
        $params = array(
                        'olv'   => $original_lv->getLvdevice(),
                        'clv'   => $lv->getLvdevice());

        // send soap request
        $response = $etva_node->soapSend($method,$params);
        return $this->processCommonResponse($etva_node,$response,$method,EtvaLogicalvolumePeer::_OK_CREATECLONE_,EtvaLogicalvolumePeer::_ERR_CREATECLONE_,array('name'=>$lv->getLv()));
    }

    /*
     * send lvcreate
     */
    public function send_create(EtvaNode $etva_node,$size,$format='',$persnapshotusage=null)
    {        
        $method = self::LVCREATE;

        $lv = $this->etva_lv->getLv();

        $etva_vg = $this->etva_lv->getEtvaVolumegroup();
        $vg = $etva_vg->getVg();        

        $is_DiskFile = ($vg == sfConfig::get('app_volgroup_disk_flag')) ? 1:0;

        $params = array(
                    'lv'    => $is_DiskFile ? $etva_node->getStoragedir().'/'.$lv : $lv,
                    'vg'    => $vg,
                    'size'  => $size,
                    'format'=> $format);
        if( $persnapshotusage ) $params['usagesize'] = Etva::MB_to_Byteconvert($size) * ( 1 - ($persnapshotusage/100) );
        
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
    public function send_resize(EtvaNode $etva_node, $size,$persnapshotusage=null)
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

        if( $persnapshotusage == null ){
            if( ($etva_lv->getVirtualSize() > 0) && ($etva_lv->getVirtualSize() < $etva_lv->getSize()) ){
                // set usage ratio
                $persnapshotusage = 100 * ( 1 - ($etva_lv->getVirtualSize() / $etva_lv->getSize()));
            }
        }

        if( $persnapshotusage )
            $params['usagesize'] = Etva::MB_to_Byteconvert($size) * ( 1 - ($persnapshotusage/100) );

        // send soap request
        $response = $etva_node->soapSend($method,$params);
        $result = $this->processResizeResponse($etva_node,$response,$method,$size);
        return $result;


    }

    /*
     * send createsnapshot
     */
    public function send_createsnapshot(EtvaNode $etva_node, EtvaLogicalvolume $etva_lv, $size)
    {
        $method = self::CREATESNAPSHOT;

        $etva_slv = $this->etva_lv;
        $slv = $etva_slv->getLv();

        /*
         * check if is not system lv
         */
        /*if($etva_lv->getMounted()){

            $msg = Etva::getLogMessage(array('name'=>$lv), EtvaLogicalvolumePeer::_ERR_SYSTEM_LV_);
            $msg_i18n = sfContext::getInstance()->getI18N()->__(EtvaLogicalvolumePeer::_ERR_SYSTEM_LV_,array('%name%'=>$lv));

            $error = array('success'=>false,'agent'=>$etva_node->getName(),'error'=>$msg_i18n,'info'=>$msg_i18n);

            //notify system log
            $message = Etva::getLogMessage(array('name'=>$lv,'size'=>$size,'info'=>$msg), EtvaLogicalvolumePeer::_ERR_RESIZE_);
            sfContext::getInstance()->getEventDispatcher()->notify(new sfEvent($etva_node->getName(), 'event.log',array('message' => $message,'priority'=>EtvaEventLogger::ERR)));

            return $error;
        }*/
        

        /*
         * check if can be resized to the specified size...
         */
        /*if(!$etva_lv->canResizeTo($size)){

            $msg = Etva::getLogMessage(array(), EtvaLogicalvolumePeer::_ERR_INVALIDSIZE_);
            $msg_i18n = sfContext::getInstance()->getI18N()->__(EtvaLogicalvolumePeer::_ERR_INVALIDSIZE_);

            $error = array('success'=>false,'agent'=>$etva_node->getName(),'error'=>$msg_i18n,'info'=>$msg_i18n);

            //notify system log
            $message = Etva::getLogMessage(array('name'=>$lv,'size'=>$size,'info'=>$msg), EtvaLogicalvolumePeer::_ERR_RESIZE_);
            sfContext::getInstance()->getEventDispatcher()->notify(new sfEvent($etva_node->getName(), 'event.log',array('message' => $message,'priority'=>EtvaEventLogger::ERR)));            

            return $error;
        }*/       
        // TODO testing can do snapshot


        // prepare soap info....

        $lvdevice = $etva_lv->getLvdevice();                
        
        $params = array(
                        'olv'   => $lvdevice,
                        'slv'   => $slv,
                        'size'  => $size);

        // send soap request
        $response = $etva_node->soapSend($method,$params);
        $result = $this->processResponse($etva_node,$response,$method,$size);
        return $result;


    }
    /*
     * send revertsnapshot
     */
    public function send_revertsnapshot(EtvaNode $etva_node, EtvaLogicalvolume $etva_lv)
    {
        $method = self::REVERTSNAPSHOT;

        $etva_slv = $this->etva_lv;

        /*
         * check if is not system lv
         */
        /*if($etva_lv->getMounted()){

            $msg = Etva::getLogMessage(array('name'=>$lv), EtvaLogicalvolumePeer::_ERR_SYSTEM_LV_);
            $msg_i18n = sfContext::getInstance()->getI18N()->__(EtvaLogicalvolumePeer::_ERR_SYSTEM_LV_,array('%name%'=>$lv));

            $error = array('success'=>false,'agent'=>$etva_node->getName(),'error'=>$msg_i18n,'info'=>$msg_i18n);

            //notify system log
            $message = Etva::getLogMessage(array('name'=>$lv,'size'=>$size,'info'=>$msg), EtvaLogicalvolumePeer::_ERR_RESIZE_);
            sfContext::getInstance()->getEventDispatcher()->notify(new sfEvent($etva_node->getName(), 'event.log',array('message' => $message,'priority'=>EtvaEventLogger::ERR)));

            return $error;
        }*/
        

        // TODO testing can do snapshot


        // prepare soap info....

        $lvdevice = $etva_lv->getLvdevice();                
        $slvdevice = $etva_slv->getLvdevice();
        
        $params = array(
                        'olv'   => $lvdevice,
                        'slv'   => $slvdevice );

        // send soap request
        $response = $etva_node->soapSend($method,$params);
        $result = $this->processCommonResponse($etva_node,$response,$method,EtvaLogicalvolumePeer::_OK_REVERTSNAPSHOT_,EtvaLogicalvolumePeer::_ERR_REVERTSNAPSHOT_,array('name'=>$etva_slv->getLv()));
        return $result;
    }
    /*
     * send convertformat
     */
    public function send_convertformat(EtvaNode $etva_node, $format)
    {
        $method = self::CONVERTFORMAT;

        $etva_lv = $this->etva_lv;

        // TODO testing can do convert


        // prepare soap info....

        $lvdevice = $etva_lv->getLvdevice();                
        
        $params = array(
                        'olv'   => $lvdevice,
                        'dlv'   => $lvdevice,
                        'format'  => $format);

        // send soap request
        $response = $etva_node->soapSend($method,$params);
        $result = $this->processCommonResponse($etva_node,$response,$method,EtvaLogicalvolumePeer::_OK_CONVERT_,EtvaLogicalvolumePeer::_ERR_CONVERT_,array('name'=>$etva_lv->getLv()));
        if( $result['success'] ){
            //update logical, volume group and physical sizes
            $etva_lv->setFormat($format);
            $etva_lv->save();
        }
        return $result;
    }

    /*
     * process response for LVREMOVE, LVCREATE, CREATESNAPSHOT
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
            case self::CREATESNAPSHOT :
                                $msg_ok_type = EtvaLogicalvolumePeer::_OK_CREATESNAPSHOT_;
                                $msg_err_type = EtvaLogicalvolumePeer::_ERR_CREATESNAPSHOT_;
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
                                    $uuid = $etva_lv->getUuid();
                                    $insert_id = $etva_lv->getId();
                                    $lv_type = $etva_lv->getStorageType();
                                    $etva_lv->delete();

                                    $snapshotsObject = $returned_object['SNAPSHOTS'];
                                    $snapshotsArray = (array) $snapshotsObject;
                                    foreach( $snapshotsArray as $slv_obj ){
                                        $slv = (array) $slv_obj;
                                        if( $etva_slv = $etva_node->retrieveLogicalvolumeByLv( $slv[EtvaLogicalvolume::LV_MAP] ) ){
                                            $etva_slv->delete();
                                        }
                                    }

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
                                    $uuid = $etva_lv->getUuid();
                                    $lv_type = $etva_lv->getStorageType();

                                    break;                
                case self::CREATESNAPSHOT :                                    
                                    $etva_vg->initData($vg_info);
                                    // update logical volume
                                    $etva_lv->setEtvaCluster($etva_node->getEtvaCluster());
                                    $etva_lv->setSnapshotNodeId($etva_node->getId());
                                    $etva_lv->initData($returned_object);

                                    $etva_node_lv = new EtvaNodeLogicalvolume();

                                    $etva_node_lv->setEtvaLogicalvolume($etva_lv);
                                    $etva_node_lv->setEtvaNode($etva_node);
                                    $etva_node_lv->save();

                                    $insert_id = $etva_lv->getId();
                                    $uuid = $etva_lv->getUuid();
                                    $lv_type = $etva_lv->getStorageType();
                                    break;               
            }            

            if($lv_type!=EtvaLogicalvolume::STORAGE_TYPE_LOCAL_MAP)
            {

                /*
                 * In case LVREMOVE need syncronize device table
                 */
                if( $method == self::LVREMOVE )
                    $this->sync_device_table_afterremove($etva_node);

                /*
                 * if storage type not local send update to nodes...
                 */
                $bulk_update = $this->send_update($etva_node);
                if(!empty($bulk_update)){
                    $errors = $this->get_missing_lv_devices();
                    $msg_i18n = $errors ? $errors['message'] :
                                    sfContext::getInstance()->getI18N()->__(EtvaLogicalvolumePeer::_ERR_SHARED_INCONSISTENT_,array('%info%'=>' processResponse'));
                    $response = array( 'success'=>false, 'agent'=>$etva_node->getName(), 'info'=>$msg_i18n, 'msg_i18n'=>$msg_i18n );
                    $ok = 0;
                }

            }

            if($ok)
            {
                //notify system log
                $message = Etva::getLogMessage(array('name'=>$lv), $msg_ok_type);
                $msg_i18n = sfContext::getInstance()->getI18N()->__($msg_ok_type,array('%name%'=>$lv));

                sfContext::getInstance()->getEventDispatcher()->notify(new sfEvent($etva_node->getName(), 'event.log',array('message' => $message)));
                
                $result = array('success'=>true, 'agent'=>$response['agent'], 'response'=>$msg_i18n,'insert_id'=>$insert_id, 'uuid'=>$uuid );

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

            $errors = $this->get_missing_lv_devices();
            if( $errors ){
                $message = $errors['message'];
                sfContext::getInstance()->getEventDispatcher()->notify(new sfEvent($etva_node->getName(),'event.log',array('message' =>$message,'priority'=>EtvaEventLogger::ERR)));

                return array('success'=>false,'agent'=>sfConfig::get('config_acronym'),'error'=>$message,'action'=>'reload','info'=>$message);
 
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
      * process response for REVERTSNAPSHOT, CREATECLONE, CONVERT
      */
    public function processCommonResponse( $etva_node, $response, $method, $msg_ok_type, $msg_err_type, $msg_args=array() ){
        if($response['success']){

            $response_decoded = (array) $response['response'];            
            $returned_object = (array) $response_decoded['_obj_'];

            $msg_i18n = Etva::makeNotifyLogMessage($response['agent'],
                                                            $msg_ok_type, $msg_args,
                                                            null,array(),EtvaEventLogger::INFO);

            $result = array('success'=>true,'agent'=>$response['agent'],'response'=>$msg_i18n);
            return  $result;
        } else {
            $msg_args['info'] = $response['info'];
            $msg_i18n = Etva::makeNotifyLogMessage($response['agent'], $msg_err_type, $msg_args);
            $result = array('success'=>false,'agent'=>$response['agent'],'error'=>$msg_i18n,'info'=>$msg_i18n);
            return  $result;
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

                // syncronization device table procedure after resize
                $this->sync_device_table_afterresize($etva_node);

                /*
                 * if storage type not local send update to nodes...
                 */
                $bulk_update = $this->send_update($etva_node);
                if(!empty($bulk_update)){
                    $errors = $this->get_missing_lv_devices();
                    $msg_i18n = $errors ? $errors['message'] :
                                    sfContext::getInstance()->getI18N()->__(EtvaLogicalvolumePeer::_ERR_SHARED_INCONSISTENT_,array('%info%'=>'processResizeResponse'));
                    $response = array( 'success'=>false, 'agent'=>$etva_node->getName(), 'info'=>$msg_i18n, 'msg_i18n'=>$msg_i18n );
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
    public function initialize(EtvaNode $etva_node,$lvs,$dtable,$bulk_dtable)
    {

        $etva_cluster = $etva_node->getEtvaCluster();
        $logical_names = array();

        $errors = array();

        /*
         * check lv consistency
         */
        $check_res = $this->check_consistency($etva_node,$lvs,$dtable,$bulk_dtable);
        if( !$check_res['success'] ){
            $errors = $check_res['errors'];

            $inconsistent_message = sfContext::getInstance()->getI18N()->__(EtvaLogicalvolumePeer::_ERR_INCONSISTENT_,array('%info%'=>''));

            $etva_node->setErrorMessage(self::LVINIT);

            $message = Etva::getLogMessage(array('info'=>$inconsistent_message), EtvaLogicalvolumePeer::_ERR_SOAPUPDATE_);
            sfContext::getInstance()->getEventDispatcher()->notify(new sfEvent($etva_node->getName(),'event.log',array('message' =>$message,'priority'=>EtvaEventLogger::ERR)));

            // dont stop process
            //return array('success'=>false,'error'=>$errors);
        }

        /*
         * check shared lvs consistency
         */
        /*$consist = $this->check_shared_consistency($etva_node,$lvs);        
        $consist_dtable = $this->check_shared_devicetable_consistency($etva_node,$dtable,$bulk_dtable);

        if(!$consist || !$consist_dtable){
            $errors = $this->get_missing_lv_devices($etva_node);
            $inconsistent_message = $errors ? $errors['message'] :
                            sfContext::getInstance()->getI18N()->__(EtvaLogicalvolumePeer::_ERR_INCONSISTENT_,array('%info%'=>'initialize'));

            $etva_node->setErrorMessage(self::LVINIT);

            $message = Etva::getLogMessage(array('info'=>$inconsistent_message), EtvaLogicalvolumePeer::_ERR_SOAPUPDATE_);
            sfContext::getInstance()->getEventDispatcher()->notify(new sfEvent($etva_node->getName(),'event.log',array('message' =>$message,'priority'=>EtvaEventLogger::ERR)));

            // dont stop process
            //return array('success'=>false,'error'=>$errors);
        }*/

        foreach($lvs as $lvInfo)
        {
            if( !empty($lvInfo) ){
                $lv_info = (array) $lvInfo;

                // set mounted 0 when not mounted
                if( !isset($lv_info[EtvaLogicalvolume::MOUNTED_MAP]) )
                    $lv_info[EtvaLogicalvolume::MOUNTED_MAP] = 0;

                //error_log("device " . $lv_info[EtvaLogicalvolume::LVDEVICE_MAP] . " EtvaLogicalvolume::MOUNTED_MAP " . $lv_info[EtvaLogicalvolume::MOUNTED_MAP]);

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

                if( $etva_volgroup ){

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
                    $etva_logicalvol->save();

                    $etva_node_logicalvol->setEtvaLogicalvolume($etva_logicalvol);
                    $etva_node_logicalvol->setEtvaNode($etva_node);
                    $etva_node_logicalvol->save();

                    
                    $logical_names[] = $etva_logicalvol->getLv();
                }
            }
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
    }

    
    /*
     * syncronization device table procedure after resize
     */
    public function sync_device_table_afterresize($etva_node){

        $etva_cluster = $etva_node->getEtvaCluster();

        $lvdevice = $this->etva_lv->getLvdevice();

        // suspend device on all nodes
        //$etva_cluster->soapSend('device_suspend',array('device'=>$lvdevice));

        // get device table from all nodes
        $dt_response = $etva_node->soapSend(self::GET_SYNC_DEVICESTABLE,array('device'=>$lvdevice));

        if( $dt_response['success'] ){

            $device_table = $dt_response['response'];

            // load table in each node
            $etva_cluster->soapSend(self::GET_SYNC_DEVICESLOADTABLE,array('device'=>$lvdevice,'table'=>$device_table),$etva_node);
        }

        // resume device on all nodes
        //$etva_cluster->soapSend('device_resume',array('device'=>$lvdevice));

    }

    /*
     * syncronization device table procedure after remove
     */
    public function sync_device_table_afterremove($etva_node){

        $etva_cluster = $etva_node->getEtvaCluster();

        $lvdevice = $this->etva_lv->getLvdevice();

        // remove device in each node
        $etva_cluster->soapSend(self::GET_SYNC_DEVICESREMOVE,array('device'=>$lvdevice),$etva_node);
    }

    /*
     * bulk cluster update info
     *
     */
    public function send_update($etva_node, $all=false, $shared=true)
    {        

        $mutex = new Mutex();
        $mutex->init(self::MUTEX_UPDATE_ID);
        $mutex->acquire();

        /*
         * update other nodes..... storages
         *
         */
        $etva_cluster = $etva_node->getEtvaCluster();

        $bulk_responses = array();
        $bulk_response_dtable = array();
        if( $shared ){
            if( $all ){
                $bulk_responses = $etva_cluster->soapSend(self::GET_SYNC_LOGICALVOLUMES,array('force'=>1));
            } else {
                $bulk_responses = $etva_cluster->soapSend(self::GET_SYNC_LOGICALVOLUMES,array('force'=>1),$etva_node);
            }
            $bulk_response_dtable = $etva_cluster->soapSend(self::GET_SYNC_DEVICESTABLE);
        } else {
            $node_id = $etva_node->getId();
            $bulk_responses[$node_id] = $etva_node->soapSend(self::GET_SYNC_LOGICALVOLUMES,array('force'=>1));
            $bulk_response_dtable[$node_id] = $etva_node->soapSend(self::GET_SYNC_DEVICESTABLE);
        }

        $errors = array();
        foreach($bulk_responses as $node_id =>$node_response){

            if($node_response['success']){ //response received ok

                $node = EtvaNodePeer::retrieveByPK($node_id);

                $dtable = array();

                $response_dtable = (array)$bulk_response_dtable[$node_id];
                if( $response_dtable['success'] )
                    $dtable = (array)$response_dtable['response'];

                //try initialize data from response
                $node_init = $this->initialize($node,(array) $node_response['response'],$dtable,$bulk_response_dtable);

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

        $mutex->release();
        return $errors;

    }

    /*
     * check for consistency between soap response shareed items and DB shared info
     *
     */
    public function check_shared_consistency($etva_node,$response)
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

            if($info[EtvaLogicalvolume::STORAGE_TYPE_MAP]!=EtvaLogicalvolume::STORAGE_TYPE_LOCAL_MAP){
                $in_resp_arr[] = $info[EtvaLogicalvolume::UUID_MAP];
            }

        }


        $consistent = 1;
        $tam_resp = count($in_resp);
        $i=0;

        
        while($i<$tam_db){
            if(!in_array($in_db_uuids[$i],$in_resp_arr)){
                $consistent = 0;
                $in_db_uuid = $in_db_uuids[$i];
                if( isset($db_uuids[$in_db_uuid]) ){
                    $lv = $db_uuids[$in_db_uuid];
                    $in_db_device = $lv->getLvdevice();
                    $this->missing_lvs[$node_id]['devices'][] = $in_db_device;
                    $this->missing_lvs[$node_id]['lvs'][] = array( 'uuid'=>$in_db_uuid, 'device'=>$in_db_device );
                }
                $this->missing_lvs[$node_id]['uuid'][] = $in_db_uuid;
                $this->missing_lvs[$node_id]['name'] = $etva_node->getName();
            }
            $i++;
        }

        return $consistent;

    }
    
    public function check_shared_devicetable_consistency($etva_node,$lvs_1stdtable,$bulk_response_dtable){

        $node_id = $etva_node->getId();
        $etva_cluster = $etva_node->getEtvaCluster();

        // get shared LV
        $db_ = $etva_cluster->getSharedLvs();
        if(!$db_) $db_ = array();

        $shared_lvs_1stdtable = array();
        //filter LVs by shared
        foreach($db_ as $data){
            $lvdevice = $data->getLvdevice();
            $clvdev = $lvdevice; 
            $clvdev = str_replace("/dev/","",$clvdev); 
            $clvdev = str_replace("-","--",$clvdev); 
            $clvdev = str_replace("/","-",$clvdev); 
            $re_clvdev = "/" . $clvdev . ":/"; 
            $lv_dtable_aux = preg_grep($re_clvdev,$lvs_1stdtable); 
            // ignore snapshots
            $lv_dtable = preg_grep("/(?:(?! snapshot ).)/",$lv_dtable_aux);
            $shared_lvs_1stdtable = array_merge($shared_lvs_1stdtable,$lv_dtable);
        }

        $consistent = 0;

        $dt_errors = array();

        foreach($bulk_response_dtable as $e_id =>$e_response){
            if($e_response['success']){ //response received ok
                $node = EtvaNodePeer::retrieveByPK($e_id); 
                $dtable = (array) $e_response['response'];

                $count_eq = 0;
                foreach($dtable as $e_line ){
                    if( in_array($e_line,$shared_lvs_1stdtable) )
                        $count_eq ++ ;
                }
                if( $count_eq != count($shared_lvs_1stdtable) ){
                    foreach( $shared_lvs_1stdtable as $d ){
                        if( !in_array($d,$dtable) ){
                            $this->missing_dtable[$e_id]['devices'][] = $d;
                            $this->missing_dtable[$e_id]['name'] = $node->getName();
                        }
                    }
                    $missing_devices = $this->missing_dtable[$e_id]['devices'];
                    $err_msg = sprintf(" node_id=%s missing_devices=%s \n",$e_id,print_r($missing_devices,true));
                    $dt_errors[] = array( 'message'=>$err_msg );
                }
            }
        }
        if( empty($dt_errors) )
            $consistent = 1;

        return $consistent;
    }

    public function get_missing_lv_devices(EtvaNode $etva_node = null){
        $return = array();
        if( $this->missing_lvs ){
            $name_lvs = array();
            if( $etva_node ){
                $return['debug_errors'] = $this->missing_lvs[$etva_node->getId()];
                $name_lvs[] = $return['debug_errors']['name'] . ': ' . implode(', ',$return['debug_errors']['devices']);
            } else {
                $return['debug_errors'] = $this->missing_lvs;
                foreach($return['debug_errors'] as $node_id=>$error_data)
                {
                    $name_lvs[] = $error_data['name'] . ': ' . implode(', ',$error_data['devices']);

                }
            }
            
            $return['info'] = $return['message'] = sfContext::getInstance()->getI18N()->__(EtvaLogicalvolumePeer::_ERR_INCONSISTENT_,array('%info%'=>' missing lvs: ' . implode(', ',$name_lvs)));

        } elseif( $this->missing_dtable ){
            $name_devices = array();
            if( $etva_node ){
                $return['debug_errors'] = $this->missing_dtable[$etva_node->getId()];
                $names_devices[] = implode(', ',$return['debug_errors']['devices']);
            } else {
                $return['debug_errors'] = $this->missing_dtable;
                foreach($return['debug_errors'] as $node_id=>$error_data)
                {
                    $name_devices[] = $error_data['name'] . ': ' . implode(', ',$error_data['devices']);
                }
            }

            $return['info'] = $return['message'] = sfContext::getInstance()->getI18N()->__(EtvaLogicalvolumePeer::_ERR_INCONSISTENT_,array('%info%'=>' missing device: ' . implode(', ',$name_devices)));
        }
        return $return;
    }
    public function check_consistency(EtvaNode $etva_node,$sync_node_lvs,$sync_node_dtable, $sync_bulk_dtable){

        $errors = array();

        $etva_node->getId();
        $etva_cluster = $etva_node->getEtvaCluster();

        // get node database LVs
        $node_lvs = $etva_node->getEtvaLogicalvolumes();
        
        $node_inconsistent = 0;

        foreach( $node_lvs as $lv ){

            $error = array();

            $uuid = $lv->getUuid();
            $device = $lv->getLvdevice();
            $etva_vg = $lv->getEtvaVolumegroup();
            $vgname = $etva_vg->getVg();

            $is_FileDiskVG = ($vgname == sfConfig::get('app_volgroup_disk_flag')) ? true : false;

            // init
            $inconsistent = 0;

            // look at logical volumes list
            $found_lvm = 0;
            foreach( $sync_node_lvs as $hlv ){
                $arr_hlv = (array)$hlv;
                if($arr_hlv[EtvaLogicalvolume::STORAGE_TYPE_MAP] == $lv->getStorageType()){
                    if( $arr_hlv[EtvaLogicalvolume::UUID_MAP] ){
                        if( $arr_hlv[EtvaLogicalvolume::UUID_MAP] == $uuid )
                            $found_lvm = 1;
                    } else {
                        if( $arr_hlv[EtvaLogicalvolume::LVDEVICE_MAP] == $device )
                            $found_lvm = 1;
                    }
                }
            }

            $inconsistent = $inconsistent || !$found_lvm;
            if( !$found_lvm ) $error['not_found_lvm'] = 1;

            if( !$is_FileDiskVG ){  // if not file disk volume

                // look at devices table
                $clvdev = $device;
                $clvdev = str_replace("/dev/","",$clvdev); 
                $clvdev = str_replace("-","--",$clvdev); 
                $clvdev = str_replace("/","-",$clvdev); 
                $re_clvdev = "/" . $clvdev . ":/"; 

                // found device table of node
                $node_lv_dtable_aux = preg_grep($re_clvdev,$sync_node_dtable); 

                // ignore snapshots
                $node_lv_dtable = preg_grep("/(?:(?! snapshot ).)/",$node_lv_dtable_aux);

                // check if found
                $found_node_dt = ( empty($node_lv_dtable) ) ? 0 : 1;
                $inconsistent = $inconsistent || !$found_node_dt;

                if( !$found_node_dt ) $error['not_found_node_device_table'] = 1;
            }

            // update data-base
            $etva_logicalvol = EtvaLogicalvolumePeer::retrieveByNodeTypeUUIDLv($etva_node->getId(), $lv->getStorageType(), $lv->getUuid(), $lv->getLv() );
            if( $etva_logicalvol ){
                $etva_logicalvol->setInconsistent($inconsistent);
                $etva_logicalvol->save();
            }
            $etva_node_logicalvol = EtvaNodeLogicalvolumePeer::retrieveByPK($etva_node->getId(), $lv->getId());
            if( $etva_node_logicalvol ){
                $etva_node_logicalvol->setInconsistent($inconsistent);
                $etva_node_logicalvol->save();
            }

            if( $inconsistent ){
                $message = sfContext::getInstance()->getI18N()->__(EtvaLogicalvolumePeer::_ERR_INCONSISTENT_,array('%info%'=>sprintf('device "%s" with uuid "%s"',$lv->getLvdevice(),$lv->getUuid())));
                sfContext::getInstance()->getEventDispatcher()->notify(new sfEvent($etva_node->getName(),'event.log',array('message' =>$message,'priority'=>EtvaEventLogger::ERR)));
                $error['node'] = array( 'name'=>$etva_node->getName(), 'id'=>$etva_node->getId(), 'uuid'=>$etva_node->getUuid() );
                $error['device'] = $lv->getLvdevice();
                $error['uuid'] = $lv->getUuid();
                $error['message'] = $message;
            }

            if( !empty($error) )
                $errors[] = $error;

            $node_inconsistent = $node_inconsistent || $inconsistent;
        }

        // get shared database LVs
        $shared_lvs = $etva_cluster->getSharedLvs();

        // check consistency for shared LVs
        foreach( $shared_lvs as $lv ){
            $error = array();

            $uuid = $lv->getUuid();
            $device = $lv->getLvdevice();
            $etva_vg = $lv->getEtvaVolumegroup();
            $vgname = $etva_vg->getVg();

            // init
            $inconsistent = 0;

            // look at logical volumes list
            $found_lvm = 0;
            foreach( $sync_node_lvs as $hlv ){
                $arr_hlv = (array)$hlv;
                if($arr_hlv[EtvaLogicalvolume::STORAGE_TYPE_MAP] == $lv->getStorageType()){
                    if( isset($arr_hlv[EtvaLogicalvolume::UUID_MAP]) ){
                        if( $arr_hlv[EtvaLogicalvolume::UUID_MAP] == $uuid )
                            $found_lvm = 1;
                    } else {
                        if( $arr_hlv[EtvaLogicalvolume::LVDEVICE_MAP] == $device )
                            $found_lvm = 1;
                    }
                }
            }

            $inconsistent = $inconsistent || !$found_lvm;
            if( !$found_lvm ) $error['not_found_lvm'] = 1;

            // look at devices table
            $clvdev = $device;
            $clvdev = str_replace("/dev/","",$clvdev); 
            $clvdev = str_replace("-","--",$clvdev); 
            $clvdev = str_replace("/","-",$clvdev); 
            $re_clvdev = "/" . $clvdev . ":/"; 

            // found device table of node
            $node_lv_dtable_aux = preg_grep($re_clvdev,$sync_node_dtable); 

            // ignore snapshots
            $node_lv_dtable = preg_grep("/(?:(?! snapshot ).)/",$node_lv_dtable_aux);

            // check if found
            $found_node_dt = ( empty($node_lv_dtable) ) ? 0 : 1;

            $inconsistent = $inconsistent || !$found_node_dt;

            if( !$found_node_dt ) $error['not_found_node_device_table'] = 1;

            // look at all nodes devices table
            $found_all_nodes_dt = 1;
            foreach($sync_bulk_dtable as $e_id =>$e_response){
                if($e_response['success']){ //response received ok
                    $dtable = (array) $e_response['response'];

                    // found device table of node
                    $lv_dtable = preg_grep($re_clvdev,$dtable); 
                    $found = ( empty($lv_dtable) ) ? 0 : 1;

                    $is_eq = ( count($lv_dtable) == count($node_lv_dtable) ) ? 1 : 0;
                    if( $is_eq ){
                        foreach($lv_dtable as $e_line ){
                            // TODO fix this
                            if( !in_array($e_line,$node_lv_dtable) )
                                $is_eq = 0 ;
                        }
                    }

                    $found_all_nodes_dt = $found_all_nodes_dt && $found && $is_eq;
                }
            }
            $inconsistent = $inconsistent || !$found_all_nodes_dt;
            if( !$found_all_nodes_dt ) $error['not_found_all_nodes_device_table'] = 1;

            // update data-base
            $etva_logicalvol = EtvaLogicalvolumePeer::retrieveByNodeTypeUUIDLv($etva_node->getId(), $lv->getStorageType(), $lv->getUuid(), $lv->getLv() );
            if( $etva_logicalvol ){
                $etva_logicalvol->setInconsistent($inconsistent);
                $etva_logicalvol->save();
            }
            $etva_node_logicalvol = EtvaNodeLogicalvolumePeer::retrieveByPK($etva_node->getId(), $lv->getId());
            if( $etva_node_logicalvol ){
                $etva_node_logicalvol->setInconsistent($inconsistent);
                $etva_node_logicalvol->save();
            }

            if( $inconsistent ){
                $message = sfContext::getInstance()->getI18N()->__(EtvaLogicalvolumePeer::_ERR_SHARED_INCONSISTENT_,array('%info%'=>sprintf('device "%s" with uuid "%s"',$lv->getLvdevice(),$lv->getUuid())));
                sfContext::getInstance()->getEventDispatcher()->notify(new sfEvent($etva_node->getName(),'event.log',array('message' =>$message,'priority'=>EtvaEventLogger::ERR)));
                $error['node'] = array( 'name'=>$etva_node->getName(), 'id'=>$etva_node->getId(), 'uuid'=>$etva_node->getUuid() );
                $error['device'] = $lv->getLvdevice();
                $error['uuid'] = $lv->getUuid();
                $error['message'] = $message;
            }

            if( !empty($error) )
                $errors[] = $error;

            $node_inconsistent = $node_inconsistent || $inconsistent;
        }

        $return = array();

        if( $node_inconsistent ){
            $etva_node->setErrorMessage(self::LVINIT);
            $return = array( 'success'=>false, 'errors'=>$errors );
        } else {
            $etva_node->clearErrorMessage(self::LVINIT);
            $return = array( 'success'=>true );
        }

        return $return;
    }
    public function fix_consistency(EtvaNode $etva_node)
    {
        $ok = 1;
        $etva_lv = $this->etva_lv;
        $lv = $etva_lv->getLv();
        $etva_vg = $etva_lv->getEtvaVolumegroup();

        /*
        $vgInfo = $returned_object[EtvaLogicalvolume::VOLUMEGROUP_MAP];
        $vg_info = (array) $vgInfo;*/

        // removes logical volume
        $insert_id = $etva_lv->getId();
        $uuid = $etva_lv->getUuid();
        $lv_type = $etva_lv->getStorageType();
        $etva_lv->delete();

        // remove snapshots
        $snapshotsObject = $returned_object['SNAPSHOTS'];
        $snapshotsArray = (array) $snapshotsObject;
        foreach( $snapshotsArray as $slv_obj ){
            $slv = (array) $slv_obj;
            if( $etva_slv = $etva_node->retrieveLogicalvolumeByLv( $slv[EtvaLogicalvolume::LV_MAP] ) ){
                $etva_slv->delete();
            }
        }

        /*$etva_vg->initData($vg_info);
        $etva_vg->save();*/

        if( $lv_type!=EtvaLogicalvolume::STORAGE_TYPE_LOCAL_MAP ){
            /*
             * need syncronize device table
             */
            $this->sync_device_table_afterremove($etva_node);

            /*
             * if storage type not local send update to nodes...
             */
            $bulk_update = $this->send_update($etva_node);
            if(!empty($bulk_update)){
                $errors = $this->get_missing_lv_devices();
                $msg_i18n = $errors ? $errors['message'] :
                                sfContext::getInstance()->getI18N()->__(EtvaLogicalvolumePeer::_ERR_SHARED_INCONSISTENT_,array('%info%'=>' processResponse'));
                $response = array( 'success'=>false, 'agent'=>$etva_node->getName(), 'info'=>$msg_i18n, 'msg_i18n'=>$msg_i18n );
                $ok = 0;
            }

        }

        // Update volume groups
        $vg_va = new EtvaVolumegroup_VA($etva_vg);
        $bulk_update = $vg_va->send_update($etva_node);
        // TODO call fix consistency volume group

        if($ok==0){
            $result = $response;            

            $errors = $this->get_missing_lv_devices();
            if( $errors ){
                $message = $errors['message'];
                sfContext::getInstance()->getEventDispatcher()->notify(new sfEvent($etva_node->getName(),'event.log',array('message' =>$message,'priority'=>EtvaEventLogger::ERR)));

                return array('success'=>false,'agent'=>sfConfig::get('config_acronym'),'error'=>$message,'action'=>'reload','info'=>$message);
 
            } else {
                $message = Etva::getLogMessage(array('name'=>$lv,'info'=>$response['info']), $msg_err_type);

                $msg_i18n = sfContext::getInstance()->getI18N()->__($msg_err_type,array('%name%'=>$lv,'%info%'=>$response['info']));
                $result['error'] = $msg_i18n;
            
                sfContext::getInstance()->getEventDispatcher()->notify(new sfEvent($response['agent'], 'event.log',array('message' => $message,'priority'=>EtvaEventLogger::ERR)));
            
                return  $result;
            }
        } else {
            //notify system log
            $message = Etva::getLogMessage(array('name'=>$lv), $msg_ok_type);
            $msg_i18n = sfContext::getInstance()->getI18N()->__($msg_ok_type,array('%name%'=>$lv));

            sfContext::getInstance()->getEventDispatcher()->notify(new sfEvent($etva_node->getName(), 'event.log',array('message' => $message)));
            
            $result = array('success'=>true, 'agent'=>$response['agent'], 'response'=>$msg_i18n,'insert_id'=>$insert_id, 'uuid'=>$uuid);

            return  $result;
        }
    }
    public function unregister(EtvaNode $etva_node){

        $etva_logicalvol = $this->etva_lv;

        // delete it
        $etva_logicalvol->delete();

        return array( 'success'=>true );
    }
}
