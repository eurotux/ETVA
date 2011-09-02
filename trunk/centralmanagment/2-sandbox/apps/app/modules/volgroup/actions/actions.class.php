<?php

/**
 * volgroup actions.
 *
 * @package    centralM
 * @subpackage volgroup
 * @author     Ricardo Gomes
 * @version    SVN: $Id: actions.class.php 12474 2008-10-31 10:41:27Z
 */
/**
 * volume group actions controller
 * @package    centralM
 * @subpackage volgroup
 *
 */
class volgroupActions extends sfActions
{    


   /**
   * Updates a volume group
   *
   * If volume group is new it will be created
   * else it will be updated
   *
   * $request may contain the following keys:
   * - nid: node ID
   * - vg: volume group name
   * - pvs: json encoded array with physical volumes
   * @return json array('success'=>true,'response'=>$data)
   * @return json array('success'=>false,'error'=>$errordata)
   */

    public function executeJsonUpdate(sfWebRequest $request){

        $nid = $request->getParameter('nid');
        // volume group name to create...
        $vg = $request->getParameter('vg');                
        
        // physical volume id to be inserted into new volume group
        //TODO : Currently only accept one physicalvolume!!!
        
        $pvs = json_decode($request->getParameter('pvs'),true);
        

        $params = array();
        $etva_pvs = array();
        $i = 0;
        
        
        if(!$etva_node = EtvaNodePeer::retrieveByPK($nid)){

            $msg_i18n = $this->getContext()->getI18N()->__(EtvaNodePeer::_ERR_NOTFOUND_ID_,array('%id%'=>$nid));
            $error = array('success'=>false,'error'=>$msg_i18n);

            //notify event log
            $node_log = Etva::getLogMessage(array('id'=>$nid), EtvaNodePeer::_ERR_NOTFOUND_ID_);
            $message = Etva::getLogMessage(array('name'=>$vg,'info'=>$node_log), EtvaVolumegroupPeer::_ERR_CREATE_EXTEND_);
            $this->dispatcher->notify(
                new sfEvent('ETVA', 'event.log',
                    array('message' => $message,'priority'=>EtvaEventLogger::ERR)));

            // if is a CLI soap request return json encoded data
            if(sfConfig::get('sf_environment') == 'soap') return json_encode($error);

            // if is browser request return text renderer
            $error = $this->setJsonError($error);
            return $this->renderText($error);

        }        

        // if volgroup doesnt exist then method is to create
        if(!$etva_volgroup = $etva_node->retrieveVolumegroupByVg($vg)){
            $etva_volgroup = new EtvaVolumegroup();
            $etva_volgroup->setVg($vg);

            $vg_va = new EtvaVolumegroup_VA($etva_volgroup);
            $response = $vg_va->send_create($etva_node,$pvs);

        }else{
            // vgextend
            $vg_va = new EtvaVolumegroup_VA($etva_volgroup);
            $response = $vg_va->send_extend($etva_node,$pvs);
        }
        


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
     * - vg: volume group name
     *
     */
    public function executeJsonRemove(sfWebRequest $request)
    {
        $nid = $request->getParameter('nid');
        $vg = $request->getParameter('vg');

        if(!$etva_node = EtvaNodePeer::retrieveByPK($nid)){

            $msg_i18n = $this->getContext()->getI18N()->__(EtvaNodePeer::_ERR_NOTFOUND_ID_,array('%id%'=>$nid));
            $error = array('success'=>false,'agent'=>'ETVA','error'=>$msg_i18n);

            //notify event log
            $node_log = Etva::getLogMessage(array('id'=>$nid), EtvaNodePeer::_ERR_NOTFOUND_ID_);
            $message = Etva::getLogMessage(array('name'=>$vg,'info'=>$node_log), EtvaVolumegroupPeer::_ERR_REMOVE_);
            $this->dispatcher->notify(
                new sfEvent('ETVA', 'event.log',
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
            $message = Etva::getLogMessage(array('name'=>$vg,'info'=>$msg), EtvaVolumegroupPeer::_ERR_REMOVE_);
            $this->dispatcher->notify(
                new sfEvent($error['agent'], 'event.log',
                    array('message' => $message,'priority'=>EtvaEventLogger::ERR)));

            // if is a CLI soap request return json encoded data
            if(sfConfig::get('sf_environment') == 'soap') return json_encode($error);

            // if is browser request return text renderer
            $error = $this->setJsonError($error);
            return $this->renderText($error);

        }

        /*
         * send volume group to VA
         */
        $vg_va = new EtvaVolumegroup_VA($etva_vg);
        $response = $vg_va->send_remove($etva_node);


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
    * Reduces the volume group by n physical volumes
    *
    * $request may contain the following keys:    
    * - nid: node ID
    * - vg: volume group name
    * - pvs: json array with physical volumes
    *
    */    
    
    public function executeJsonReduce(sfWebRequest $request)
    {
        
        $nid = $request->getParameter('nid');

        $vg = $request->getParameter('vg');

        // physical volume id
        $pvs = json_decode($request->getParameter('pvs'),true);       

        if(!$etva_node = EtvaNodePeer::retrieveByPK($nid)){

            $msg_i18n = $this->getContext()->getI18N()->__(EtvaNodePeer::_ERR_NOTFOUND_ID_,array('%id%'=>$nid));
            $error = array('success'=>false,'agent'=>'ETVA','error'=>$msg_i18n);

            //notify event log
            $node_log = Etva::getLogMessage(array('id'=>$nid), EtvaNodePeer::_ERR_NOTFOUND_ID_);
            $message = Etva::getLogMessage(array('name'=>$vg,'info'=>$node_log), EtvaVolumegroupPeer::_ERR_REDUCE_);
            $this->dispatcher->notify(
                new sfEvent('ETVA', 'event.log',
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
            $message = Etva::getLogMessage(array('name'=>$vg,'info'=>$msg), EtvaVolumegroupPeer::_ERR_REMOVE_);
            $this->dispatcher->notify(
                new sfEvent($error['agent'], 'event.log',
                    array('message' => $message,'priority'=>EtvaEventLogger::ERR)));

            // if is a CLI soap request return json encoded data
            if(sfConfig::get('sf_environment') == 'soap') return json_encode($error);

            // if is browser request return text renderer
            $error = $this->setJsonError($error);
            return $this->renderText($error);

        }

        /*
         * send volume group to VA
         */
        $vg_va = new EtvaVolumegroup_VA($etva_vg);
        $response = $vg_va->send_reduce($etva_node,$pvs);
        
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
   * Returns pre-formated data for Extjs combo box with volume group free size available
   *  
   *
   * $request may contain the following keys:
   * - nid: nid (virtAgent node ID)
   * @return array json array('total'=>num elems, 'data'=>array('value'=>value,'name'=>name))
   */

  /*
   * Used in logical volume create window
   * Used in server creation wizard window
   */
    public function executeJsonListFree(sfWebRequest $request)
    {

        $elements = array();

        $criteria = new Criteria();
        $criteria->add(EtvaNodeVolumegroupPeer::NODE_ID,$request->getParameter('nid'));
        $criteria->addJoin(EtvaNodeVolumegroupPeer::VOLUMEGROUP_ID, EtvaVolumegroupPeer::ID);        
        $criteria->add(EtvaVolumegroupPeer::FREESIZE,0,Criteria::NOT_EQUAL);

        $nodisk = $request->getParameter('nodisk');
        if($nodisk)
            $criteria->add(EtvaVolumegroupPeer::VG, sfConfig::get('app_volgroup_disk_flag'),Criteria::NOT_EQUAL);


        $etva_vgs = EtvaVolumegroupPeer::doSelect($criteria);

        if(!$etva_vgs){
            $msg_i18n = $this->getContext()->getI18N()->__(EtvaVolumegroupPeer::_NOTAVAILABLE_);

            $info = array('success'=>false,'error'=>$msg_i18n);
            $error = $this->setJsonError($info,204); // 204 => no content
            return $this->renderText($error);
        }

        foreach ($etva_vgs as $elem){
            $id = $elem->getId();
            $size = $elem->getFreesize();
            $txt = $elem->getVg();            
            $elements[] = array('id'=>$id,'name'=>$txt,'value'=>$size);

        }

        $result = array('total' =>   count($elements),'data'  => $elements);

        $return = json_encode($result);
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
    public function executeJsonVgsTree(sfWebRequest $request)
    {

        $criteria = new Criteria();

        $criteria->add(EtvaNodeVolumeGroupPeer::NODE_ID,$request->getParameter('nid'));
        $node_vgs = EtvaNodeVolumeGroupPeer::doSelectJoinEtvaVolumegroup($criteria);

        $volumes = array();

        foreach ($node_vgs as $data){
            
            $node_id = $data->getNodeId();
            $vg = $data->getEtvaVolumegroup();
            $pvs_tree = array();
            $etva_vp = $vg->getEtvaVolumePhysicals();

            foreach($etva_vp as $vp)
            {

                $pv = $vp->getEtvaPhysicalvolume();                

                if($pv)
                {
                    $id = $pv->getId();

                    $np = EtvaNodePhysicalvolumePeer::retrieveByPK($node_id, $id);

                    $elem = $np->getDevice();
                    $pvdevice = $pv->getPv();
                    $pretty_size = $size = $pv->getPvsize();
                    $qtip = '';
                    $cls = 'dev-pv';
                    $pvs_tree[] = array('id'=>$id,'cls'=>$cls,'iconCls'=>'task','text'=>$elem,'pv'=>$pvdevice,'size'=>$size,'prettysize'=>$pretty_size,'singleClickExpand'=>true,'type'=>'dev-pv','qtip'=>$qtip,'leaf'=>true);
                }
            }

            $id = $vg->getVg();
            $vgid = $vg->getId();
            $qtip = '';
            $cls = 'vg';
            $pretty_size = $size = $vg->getSize();
            $free_size = $vg->getFreesize();
            $expanded = empty($pvs_tree) ? true: false;
            $type = $vg->getStorageType();
            $is_DiskVG = ($id == sfConfig::get('app_volgroup_disk_flag')) ? 1:0;
            if($is_DiskVG) $type = 'file';

            $volumes[] = array('id'=>$id,'expanded'=>$expanded,'vgid'=>$vgid,'iconCls'=>'devices-folder','cls'=>$cls,'text'=>$id,'type'=>$type,'size'=>$size,'prettysize'=>$pretty_size, 'freesize'=>$free_size, 'singleClickExpand'=>true,'qtip'=>$qtip,'children'=>$pvs_tree);
        }

        if(empty($volumes)){
            $msg_i18n = $this->getContext()->getI18N()->__('No data found');
            $volumes[] = array('expanded'=>true,'text'=>$msg_i18n,'qtip'=>$msg_i18n,'leaf'=>true);
        }
        $return = json_encode($volumes);
        $this->getResponse()->setHttpHeader('Content-type', 'application/json');

        return $this->renderText($return);
      

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
   * Used to process soap requests => updateVirtAgentVgs
   *
   * Updates volume group info sent by virt Agent
   * The request should be made throught soapapi
   *
   * Replies with succcess
   *
   * $request may contain the following keys:
   * - uid: uid (virtAgent sending request uid)
   * - vgs (object containing volumes info)
   * @return array array(success=>true)
   */

   public function executeSoapUpdate(sfWebRequest $request)
   {
        
       if(sfConfig::get('sf_environment') == 'soap'){

            $vgs = $request->getParameter('vgs');

            // check node ID correspondig to the uid given
            $c = new Criteria();
            $c->add(EtvaNodePeer::UUID ,$request->getParameter('uuid'));


            if(!$etva_node = EtvaNodePeer::doSelectOne($c)){
                $error_msg = sprintf('Object etva_node does not exist (%s).', $request->getParameter('uuid'));
                $error = array('success'=>false,'error'=>$error_msg);

                //notify event log
                $node_message = Etva::getLogMessage(array('name'=>$request->getParameter('uuid')), EtvaNodePeer::_ERR_NOTFOUND_UUID_);
                $message = Etva::getLogMessage(array('info'=>$node_message), EtvaVolumegroupPeer::_ERR_SOAPUPDATE_);
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

                //notify event log
                $cluster_message = Etva::getLogMessage(array('info'=>$error_msg), EtvaClusterPeer::_ERR_CLUSTER_);
                $message = Etva::getLogMessage(array('info'=>$cluster_message), EtvaVolumegroupPeer::_ERR_SOAPUPDATE_);
                $this->dispatcher->notify(
                    new sfEvent('ETVA',
                            'event.log',
                            array('message' =>$message,'priority'=>EtvaEventLogger::ERR)
                ));
            
                return $error;
            }

            /*
             * send volume group to VA
             */
            $vg_va = new EtvaVolumegroup_VA();
            $response = $vg_va->initialize($etva_node,$vgs);
            return $response; 
       }

   }

}
