<?php

/**
 * logicalvol actions.
 *
 * @package    centralM
 * @subpackage logicalvol
 * @author     Ricardo Gomes
 * @version    SVN: $Id: actions.class.php 12474 2008-10-31 10:41:27Z
 */
/**
 * logical volume actions controller
 * @package    centralM
 * @subpackage logicalvol
 *
 */
class logicalvolActions extends sfActions
{

    /*
     * use to call disks attach/detach template
     */
    public function executeLogicalvol_ManageDisksGrid(sfWebRequest $request)
    {            
    }


   /**
   * Inserts a logical volume
   *
   *
   * $request may contain the following keys:
   * - nid: node ID
   * - lv: logical volume name
   * - vg: volume group name
   * - size: logical volume size
   *
   */
    public function executeJsonCreate(sfWebRequest $request)
    {
        $nid = $request->getParameter('nid');
        $lv = $request->getParameter('lv');
        $size = $request->getParameter('size');
        $vg = $request->getParameter('vg');


        /*
         * check if lv is a file disk instead
         * if is a file disk check if special volume group exists. if not create
         */
        $is_DiskFile = ($vg == sfConfig::get('app_volgroup_disk_flag')) ? 1:0;
        
        if(!$etva_node = EtvaNodePeer::retrieveByPK($nid)){

            $msg_i18n = $this->getContext()->getI18N()->__(EtvaNodePeer::_ERR_NOTFOUND_ID_,array('%id%'=>$nid));

            $error = array('success'=>false,'agent'=>'ETVA','error'=>$msg_i18n,'info'=>$msg_i18n);
            
            $node_log = Etva::getLogMessage(array('id'=>$nid), EtvaNodePeer::_ERR_NOTFOUND_ID_);
            $message = Etva::getLogMessage(array('name'=>$lv,'info'=>$node_log), EtvaLogicalvolumePeer::_ERR_CREATE_);
            //notify system log
            $this->dispatcher->notify(
                new sfEvent('ETVA', 'event.log',
                    array('message' => $message,'priority'=>EtvaEventLogger::ERR)));

            // if is a CLI soap request return json encoded data
            if(sfConfig::get('sf_environment') == 'soap') return json_encode($error);

            // if is browser request return text renderer
            $error = $this->setJsonError($error);
            return $this->renderText($error);

        }


        if($etva_lv = $etva_node->retrieveLogicalvolumeByLv($lv)){
             
                $msg_type = $is_DiskFile ? EtvaLogicalvolumePeer::_ERR_DISK_EXIST_ : EtvaLogicalvolumePeer::_ERR_LV_EXIST_;
                $msg = Etva::getLogMessage(array('name'=>$lv), $msg_type);
                $msg_i18n = $this->getContext()->getI18N()->__($msg_type,array('%name%'=>$lv));


                $error = array('success'=>false,
                           'agent'=>$etva_node->getName(),
                           'error'=>$msg_i18n,
                           'info'=>$msg_i18n);

                //notify system log
                $message = Etva::getLogMessage(array('name'=>$lv,'info'=>$msg), EtvaLogicalvolumePeer::_ERR_CREATE_);                
                $this->dispatcher->notify(
                    new sfEvent($error['agent'], 'event.log',
                        array('message' => $message,'priority'=>EtvaEventLogger::ERR)));
                

                // if is a CLI soap request return json encoded data
                if(sfConfig::get('sf_environment') == 'soap') return json_encode($error);

                // if is browser request return text renderer
                $error = $this->setJsonError($error);
                return $this->renderText($error);            

        }
                
        if(!$etva_vg = $etva_node->retrieveVolumegroupByVg($vg)){
            
            $msg = Etva::getLogMessage(array('name'=>$vg), EtvaVolumegroupPeer::_ERR_NOTFOUND_);
            $msg_i18n = $this->getContext()->getI18N()->__(EtvaVolumegroupPeer::_ERR_NOTFOUND_,array('%name%'=>$vg));

            $error = array('success'=>false,'agent'=>$etva_node->getName(),'error'=>$msg_i18n, 'info'=>$msg_i18n);

            //notify system log
            $message = Etva::getLogMessage(array('name'=>$lv,'info'=>$msg), EtvaLogicalvolumePeer::_ERR_CREATE_);
            $this->dispatcher->notify(
                new sfEvent($error['agent'], 'event.log',
                    array('message' => $message,'priority'=>EtvaEventLogger::ERR)));

            // if is a CLI soap request return json encoded data
            if(sfConfig::get('sf_environment') == 'soap') return json_encode($error);

            // if is browser request return text renderer
            $error = $this->setJsonError($error);
            return $this->renderText($error);
            

        }


        // prepare soap info....
        $etva_lv = new EtvaLogicalvolume();
        $etva_lv->setEtvaVolumegroup($etva_vg);
        $etva_lv->setLv($lv);
        $lv_va = new EtvaLogicalvolume_VA($etva_lv);
        $response = $lv_va->send_create($etva_node,$size);


        if($response['success'])
        {
            $return = json_encode($response);

            // if the request is made throught soap request...
            if(sfConfig::get('sf_environment') == 'soap') return $return;
            // if is browser request return text renderer
            $this->getResponse()->setHttpHeader('Content-type', 'application/json');
            return  $this->renderText($return);


        }else{

            if(sfConfig::get('sf_environment') == 'soap') return json_encode($response);

            $return = $this->setJsonError($response);
            return  $this->renderText($return);
        }

    }   


    /**
     * Removes volume group
     *
     * $request may contain the following keys:
     * - nid: node ID
     * - lv: logical volume name
     *
     */
    public function executeJsonRemove(sfWebRequest $request){

        // logical volume id
        $nid = $request->getParameter('nid');
        $lv = $request->getParameter('lv');


        if(!$etva_node = EtvaNodePeer::retrieveByPK($nid)){

            $msg_i18n = $this->getContext()->getI18N()->__(EtvaNodePeer::_ERR_NOTFOUND_ID_,array('%id%'=>$nid));

            $error = array('success'=>false,'agent'=>'ETVA','error'=>$msg_i18n,'info'=>$msg_i18n);

            //notify system log
            $node_log = Etva::getLogMessage(array('id'=>$nid), EtvaNodePeer::_ERR_NOTFOUND_ID_);
            $message = Etva::getLogMessage(array('name'=>$lv,'info'=>$node_log), EtvaLogicalvolumePeer::_ERR_REMOVE_);
            $this->dispatcher->notify(
                new sfEvent('ETVA', 'event.log',
                    array('message' => $message,'priority'=>EtvaEventLogger::ERR)));

            // if is a CLI soap request return json encoded data
            if(sfConfig::get('sf_environment') == 'soap') return json_encode($error);

            // if is browser request return text renderer
            $error = $this->setJsonError($error);
            return $this->renderText($error);

        }

        if(!$etva_lv = $etva_node->retrieveLogicalvolumeByLv($lv)){

            $msg = Etva::getLogMessage(array('name'=>$lv), EtvaLogicalvolumePeer::_ERR_NOTFOUND_);
            $msg_i18n = $this->getContext()->getI18N()->__(EtvaLogicalvolumePeer::_ERR_NOTFOUND_,array('%name%'=>$lv));

            $error = array('success'=>false,'agent'=>'ETVA','error'=>$msg_i18n,'info'=>$msg_i18n);

            //notify system log            
            $message = Etva::getLogMessage(array('name'=>$lv,'info'=>$msg), EtvaLogicalvolumePeer::_ERR_REMOVE_);
            $this->dispatcher->notify(
                new sfEvent($etva_node->getName(), 'event.log',
                    array('message' => $message,'priority'=>EtvaEventLogger::ERR)));


            // if is a CLI soap request return json encoded data
            if(sfConfig::get('sf_environment') == 'soap') return json_encode($error);

            // if is browser request return text renderer
            $error = $this->setJsonError($error);
            return $this->renderText($error);

        }


        /*
         * send logical volume to VA
         */
        $lv_va = new EtvaLogicalvolume_VA($etva_lv);
        $response = $lv_va->send_remove($etva_node);

        if($response['success'])
        {
            $return = json_encode($response);

            // if the request is made throught soap request...
            if(sfConfig::get('sf_environment') == 'soap') return $return;
            // if is browser request return text renderer
            $this->getResponse()->setHttpHeader('Content-type', 'application/json');
            return  $this->renderText($return);


        }else{

            if(sfConfig::get('sf_environment') == 'soap') return json_encode($response);

            $return = $this->setJsonError($response);
            return  $this->renderText($return);
        }

    }

    /**
     * Resizes volume group
     *
     * $request may contain the following keys:
     * - nid: node ID
     * - lv: logical volume name
     * - size: size
     *
     */
    public function executeJsonResize(sfWebRequest $request)
    {

        // logical volume id
        $nid = $request->getParameter('nid');
        $lv = $request->getParameter('lv');
        $size = $request->getParameter('size');


        if(!$etva_node = EtvaNodePeer::retrieveByPK($nid)){

            $msg_i18n = $this->getContext()->getI18N()->__(EtvaNodePeer::_ERR_NOTFOUND_ID_,array('%id%'=>$nid));
            $error = array('success'=>false,'agent'=>'ETVA','error'=>$msg_i18n,'info'=>$msg_i18n);
            

            //notify system log
            $node_log = Etva::getLogMessage(array('id'=>$nid), EtvaNodePeer::_ERR_NOTFOUND_ID_);
            $message = Etva::getLogMessage(array('name'=>$lv,'size'=>$size,'info'=>$node_log), EtvaLogicalvolumePeer::_ERR_RESIZE_);
            $this->dispatcher->notify(
                new sfEvent('ETVA', 'event.log',
                    array('message' => $message,'priority'=>EtvaEventLogger::ERR)));

            // if is a CLI soap request return json encoded data
            if(sfConfig::get('sf_environment') == 'soap') return json_encode($error);

            // if is browser request return text renderer
            $error = $this->setJsonError($error);
            return $this->renderText($error);

        }

        if(!$etva_lv = $etva_node->retrieveLogicalvolumeByLv($lv)){

            $msg = Etva::getLogMessage(array('name'=>$lv), EtvaLogicalvolumePeer::_ERR_NOTFOUND_);
            $msg_i18n = $this->getContext()->getI18N()->__(EtvaLogicalvolumePeer::_ERR_NOTFOUND_,array('%name%'=>$lv));

            $error = array('success'=>false,'agent'=>'ETVA','error'=>$msg_i18n,'info'=>$msg_i18n);

            //notify system log
            $message = Etva::getLogMessage(array('name'=>$lv,'size'=>$size,'info'=>$msg), EtvaLogicalvolumePeer::_ERR_RESIZE_);
            $this->dispatcher->notify(
                new sfEvent($etva_node->getName(), 'event.log',
                    array('message' => $message,'priority'=>EtvaEventLogger::ERR)));

            // if is a CLI soap request return json encoded data
            if(sfConfig::get('sf_environment') == 'soap') return json_encode($error);

            // if is browser request return text renderer
            $error = $this->setJsonError($error);
            return $this->renderText($error);

        }


        /*
         * send logical volume to VA
         */
        $lv_va = new EtvaLogicalvolume_VA($etva_lv);
        $response = $lv_va->send_resize($etva_node,$size);

        if($response['success'])
        {
            $return = json_encode($response);

            // if the request is made throught soap request...
            if(sfConfig::get('sf_environment') == 'soap') return $return;
            // if is browser request return text renderer
            $this->getResponse()->setHttpHeader('Content-type', 'application/json');
            return  $this->renderText($return);


        }else{

            if(sfConfig::get('sf_environment') == 'soap') return json_encode($response);

            $return = $this->setJsonError($response);
            return  $this->renderText($return);
        }

    }

    /**
     * List logical volumes by server ID or node ID
     *
     *
     * Returns json array('id'=>id,'lv'=> lv name)
     *
     *
     */
    public function executeJsonList(sfWebRequest $request)
    {

        $nid = $request->getParameter('nid');
        $sid = $request->getParameter('sid');        
        $listBy = $sid ? 'server' : 'node';

        
        if($nid && !$etva_ = EtvaNodePeer::retrieveByPK($nid)){

            $msg_i18n = $this->getContext()->getI18N()->__(EtvaNodePeer::_ERR_NOTFOUND_ID_,array('%id%'=>$nid));
            $error = array('success'=>false,'agent'=>'ETVA','error'=>$msg_i18n,'info'=>$msg_i18n);

            if(sfConfig::get('sf_environment') == 'soap') return json_encode($error);

            // if is browser request return text renderer
            $error = $this->setJsonError($error);
            return $this->renderText($error);

        }

        
        if($sid && !$etva_ = EtvaServerPeer::retrieveByPK($sid)){

            $msg_i18n = $this->getContext()->getI18N()->__(EtvaServerPeer::_ERR_NOTFOUND_ID_,array('%id%'=>$sid));
            $error = array('success'=>false,'agent'=>'ETVA','error'=>$msg_i18n,'info'=>$msg_i18n);

            if(sfConfig::get('sf_environment') == 'soap') return json_encode($error);

            // if is browser request return text renderer
            $error = $this->setJsonError($error);
            return $this->renderText($error);

        }

        $criteria = new Criteria();
        $criteria->addAscendingOrderByColumn(EtvaServerLogicalPeer::BOOT_DISK);
        $list = $etva_->getEtvaLogicalvolumes($criteria);

        $lvs = array();        

        switch($listBy){
            case 'server':
                            $pos = 0;
                            foreach ($list as $elem){
                                
                                $sls = $elem->getEtvaServerLogicals();
                                $sl = $sls[0];
                                $disk_type = $sl->getDiskType();                                
                                $lv_array = $elem->toArray(BasePeer::TYPE_FIELDNAME);
                                $lv_array['disk_type'] = $disk_type;
                                $lv_array['pos'] = $pos;
                                $lvs[] = $lv_array;
                                $pos++;
                            }                            
                            break;
            default      :
                            foreach ($list as $elem){
                                $id = $elem->getId();
                                $lv = $elem->getLv();                                
                                $lvs[$id] = array('id'=>$id,'lv'=>$lv,'target');                                
                            }                            
        }

        
        $result = array('success'=>true,
                'total'=> count($lvs),
                'data'=> $lvs
               );

        $return = json_encode($result);

        if(sfConfig::get('sf_environment') == 'soap') return $return;

        $this->getResponse()->setHttpHeader('Content-type', 'application/json');
        return $this->renderText($return);

    }



  /**
   * Return pre-formatted data for tree-column extjs
   *
   * $request may contain the following keys:
   * - nid: nid (virtAgent node ID)
   * @return array json array
   */
    public function executeJsonLvsTree(sfWebRequest $request)
    {
        $lvs = array();
        $etva_node = EtvaNodePeer::retrieveByPK($request->getParameter('nid'));
        
        if(!$etva_node){
            $msg_i18n = $this->getContext()->getI18N()->__('No data found');
            $lvs[] = array('expanded'=>true,'text'=>$msg_i18n,'qtip'=>$msg_i18n,'leaf'=>true);
            $return = json_encode($lvs);

            $this->getResponse()->setHttpHeader('Content-type', 'application/json');
            return $this->renderText($return);
        }

        $criteria = new Criteria();
        $node_lvs = $etva_node->getEtvaLogicalvolumes($criteria);               

        foreach ($node_lvs as $etva_lv){
            $vm_name = '';
            $vm_state = '';
            
            $etva_vg = $etva_lv->getEtvaVolumegroup();
            $etva_server = $etva_lv->getEtvaServer();
            if($etva_server){
                $vm_name = $etva_server->getName();
                $vm_state = $etva_server->getVmState();
            }
            
            //check data consistency....should be fine
            if($etva_vg){
                $id = $etva_lv->getId();
                $text = $etva_lv->getLv();
                $disabled = false;
                if($etva_lv->getInUse()){                    
                    $qtip = $this->getContext()->getI18N()->__(EtvaLogicalvolumePeer::_ERR_INUSE_,array('%name%'=>$text,'%server%'=>$vm_name));
                }
                else if($etva_lv->getMounted()){
                    $qtip = $this->getContext()->getI18N()->__(EtvaLogicalvolumePeer::_ERR_SYSTEM_LV_,array('%name%'=>$text));
                    $disabled = true;
                }else{
                    $qtip = $this->getContext()->getI18N()->__(EtvaLogicalvolumePeer::_NOTINUSE_,array('%name%'=>$text));
                }
                
                $pretty_size = $etva_lv->getSize();
                $size = $etva_lv->getSize();

                $vg = $etva_vg->getVg();
                $vg_size = $etva_vg->getSize();
                $vg_freesize = $etva_vg->getFreesize();
                $cls = 'lv';
                $lvs[] = array('id'=>$id,'cls'=>$cls,
                               'iconCls'=>'devices-folder',
                               'text'=>$text,'size'=>$size,
                               'prettysize'=>$pretty_size,'vgsize'=>$vg_size,
                               'singleClickExpand'=>true,'type'=>'lv',
                               'vg'=>$vg,'vgfreesize'=>$vg_freesize,
                               'vm_state'=>$vm_state,'disabled'=>$disabled,
                               'vm_name'=>$vm_name,'qtip'=>$qtip,'leaf'=>true);
            }

        }

        //$return = json_encode(array(array('text'=>'Lvs','expanded'=>true,'children'=>$lvs)));
        if(empty($lvs)){
            $msg_i18n = $this->getContext()->getI18N()->__('No data found');
            $lvs[] = array('expanded'=>true,'text'=>$msg_i18n,'qtip'=>$msg_i18n,'leaf'=>true);
        }

        $return = json_encode($lvs);
        
        $this->getResponse()->setHttpHeader('Content-type', 'application/json');
        return $this->renderText($return);

    }

  /**
   * Returns pre-formated data for Extjs combo with lvs available
   *
   * $request may contain the following keys:
   * - nid: nid (virtAgent node ID)
   * @return array json array
   */
    public function executeJsonGetAvailable(sfWebRequest $request)
    {

        $nid = $request->getParameter('nid');

        if(!$etva_node = EtvaNodePeer::retrieveByPK($nid)){

            $msg_i18n = $this->getContext()->getI18N()->__(EtvaNodePeer::_ERR_NOTFOUND_ID_,array('%id%'=>$nid));
            $error = array('success'=>false,'error'=>$msg_i18n);

            // if is browser request return text renderer
            $error = $this->setJsonError($error);
            return $this->renderText($error);

        }

        $criteria = new Criteria();
        $criteria->add(EtvaLogicalvolumePeer::WRITEABLE, 1);
        $criteria->add(EtvaLogicalvolumePeer::IN_USE, 0);
        $criteria->add(EtvaLogicalvolumePeer::MOUNTED, 0);
        
               
        $etva_lvs = $etva_node->getEtvaLogicalvolumes($criteria);

        if(!$etva_lvs){
            $msg_i18n = $this->getContext()->getI18N()->__(EtvaLogicalvolumePeer::_NOTAVAILABLE_);
            $info = array('success'=>false,'agent'=>'ETVA','info'=>$msg_i18n,'error'=>$msg_i18n);
            $error = $this->setJsonError($info);
            return $this->renderText($error);
        }


        // build result to feed to combo...
        $elements = array();
        foreach ($etva_lvs as $lv){

            $size = $lv->getSize();            
            $elements[] = array('id'=>$lv->getId(),'lv'=>$lv->getLv(),'size'=>$size);

        }


        $result = array('total' =>   count($elements),'data'  => $elements);

        $return = json_encode($result);
        $this->getResponse()->setHttpHeader('Content-type', 'application/json');
        return $this->renderText($return);

    }


    /*
     * NOT USED
     * export rra data in xml
     */
    public function executeXportDiskSpaceRRA(sfWebRequest $request)
    {

        $etva_lv = EtvaLogicalvolumePeer::retrieveByPK($request->getParameter('id'));
        $etva_server = $etva_lv->getEtvaServer();
        $etva_node = $etva_server->getEtvaNode();

        $graph_start = $request->getParameter('graph_start');
        $graph_end = $request->getParameter('graph_end');
        $step = $request->getParameter('step');

        $disk_rra = new ServerDiskSpaceRRA($etva_node->getName(),$etva_server->getUuid(),$etva_lv->getLv());

        $this->getResponse()->setContentType('text/xml');
        $this->getResponse()->setHttpHeader('Content-Type', 'text/xml', TRUE);
        $this->getResponse()->sendHttpHeaders();
        $this->getResponse()->setContent($disk_rra->xportData($graph_start,$graph_end,$step));
        return sfView::HEADER_ONLY;

    }




    public function executeXportDiskRWRRA(sfWebRequest $request)
    {

        $etva_lv = EtvaLogicalvolumePeer::retrieveByPK($request->getParameter('id'));
        $etva_server = $etva_lv->getEtvaServer();
        $etva_node = $etva_server->getEtvaNode();

        $graph_start = $request->getParameter('graph_start');
        $graph_end = $request->getParameter('graph_end');
        $step = $request->getParameter('step');

        $disk_rra = new ServerDisk_rwRRA($etva_node->getUuid(),$etva_server->getUuid(),$etva_lv->getLv());

        $this->getResponse()->setContentType('text/xml');
        $this->getResponse()->setHttpHeader('Content-Type', 'text/xml', TRUE);
        $this->getResponse()->sendHttpHeaders();
        $this->getResponse()->setContent($disk_rra->xportData($graph_start,$graph_end,$step));
        return sfView::HEADER_ONLY;

    }



    /*
     * UNUSED
     * returns logical volume disk space png image from rrd data file
     */
    public function executeGraphDiskSpacePNG(sfWebRequest $request)
    {

        $etva_lv = EtvaLogicalvolumePeer::retrieveByPK($request->getParameter('id'));
        $etva_server = $etva_lv->getEtvaServer();
        $etva_node = $etva_server->getEtvaNode();

        $graph_start = $request->getParameter('graph_start');
        $graph_end = $request->getParameter('graph_end');

        $disk_rra = new ServerDiskSpaceRRA($etva_node->getName(),$etva_server->getUuid(),$etva_lv->getLv());
        $title = sprintf("%s :: %s",$etva_server->getName(),$etva_lv->getLv());


        $this->getResponse()->setContentType('image/png');
        $this->getResponse()->setHttpHeader('Content-Type', 'image/png', TRUE);
        $this->getResponse()->sendHttpHeaders();
        $this->getResponse()->setContent(print $disk_rra->getGraphImg($title,$graph_start,$graph_end));
        return sfView::HEADER_ONLY;

    }


    /*
     * returns logical volume disk r/w png image from rrd data file
     */
    public function executeGraphDiskRWPNG(sfWebRequest $request)
    {

        $etva_lv = EtvaLogicalvolumePeer::retrieveByPK($request->getParameter('id'));
        $etva_server = $etva_lv->getEtvaServer();
        $etva_node = $etva_server->getEtvaNode();

        $graph_start = $request->getParameter('graph_start');
        $graph_end = $request->getParameter('graph_end');

        $disk_rra = new ServerDisk_rwRRA($etva_node->getUuid(),$etva_server->getUuid(),$etva_lv->getLv());
        $title = sprintf("%s :: %s",$etva_server->getName(),$etva_lv->getLv());
        $this->getResponse()->setContentType('image/png');
        $this->getResponse()->setHttpHeader('Content-Type', 'image/png', TRUE);
        $this->getResponse()->sendHttpHeaders();
        $this->getResponse()->setContent(print $disk_rra->getGraphImg($title,$graph_start,$graph_end));
        return sfView::HEADER_ONLY;
    }






  /**
   * Used to return errors messages
   *
   * @param string $info error message
   * @param int $statusCode HTTP STATUS CODE
   * @return array json array
   */
    protected function setJsonError($info,$statusCode = 400){

        if(isset($info['faultcode']) && $info['faultcode']=='TCP') $statusCode = 404;
        $this->getContext()->getResponse()->setStatusCode($statusCode);
        $error = json_encode($info);
        $this->getResponse()->setHttpHeader('Content-type', 'application/json');
        return $error;

    }

  /**
   * Used to process soap requests => updateVirtAgentLvs
   *
   * Updates logical volume info sent by virt Agent
   *
   * Replies with succcess
   *
   * $request may contain the following keys:
   * - uid: uid (virtAgent sending request uid)
   * - lvs (object containing logical volumes info)
   * @return array array(success=>true)
   */
    public function executeSoapUpdate(sfWebRequest $request)
    {

        /*
        * Check if the request is made via soapapi.php interface
        */
        if(sfConfig::get('sf_environment') == 'soap'){

            $lvs = $request->getParameter('lvs');

            // check node ID correspondig to the uid given
            $c = new Criteria();
            $c->add(EtvaNodePeer::UUID ,$request->getParameter('uuid'));

            if(!$etva_node = EtvaNodePeer::doSelectOne($c)){
                $error_msg = sprintf('Object etva_node does not exist (%s).', $request->getParameter('uuid'));
                $error = array('success'=>false,'error'=>$error_msg);

                //notify system log
                $node_message = Etva::getLogMessage(array('name'=>$request->getParameter('uuid')), EtvaNodePeer::_ERR_NOTFOUND_UUID_);
                $message = Etva::getLogMessage(array('info'=>$node_message), EtvaLogicalvolumePeer::_ERR_SOAPUPDATE_);
                $this->dispatcher->notify(
                    new sfEvent('ETVA',
                            'event.log',
                            array('message' =>$message,'priority'=>EtvaEventLogger::ERR)
                ));

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
             * check node cluster ID
             */
            if(!$etva_cluster = $etva_node->getEtvaCluster())
            {
                $error_msg = sprintf('Object etva_cluster does not exist for node %s',$etva_node->getName());
                $error = array('success'=>false,'error'=>$error_msg);

                //notify system log
                $cluster_message = Etva::getLogMessage(array('info'=>$error_msg), EtvaClusterPeer::_ERR_CLUSTER_);
                $message = Etva::getLogMessage(array('info'=>$cluster_message), EtvaLogicalvolumePeer::_ERR_SOAPUPDATE_);
                $this->dispatcher->notify(
                    new sfEvent('ETVA',
                            'event.log',
                            array('message' =>$message,'priority'=>EtvaEventLogger::ERR)
                ));

                return $error;
            }

            /*
             * send logical volume to VA
             */
            $lv_va = new EtvaLogicalvolume_VA();
            $response = $lv_va->initialize($etva_node,$lvs);
            return $response;

        }// end soap request


    }

}
