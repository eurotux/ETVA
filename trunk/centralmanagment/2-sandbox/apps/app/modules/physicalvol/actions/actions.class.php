<?php
/**
 * physicalvol actions.
 *
 * @package    centralM
 * @subpackage physicalvol
 * @author     Ricardo Gomes
 * @version    SVN: $Id: actions.class.php 12474 2008-10-31 10:41:27Z
 */
/**
 * physical volume actions controller
 * @package    centralM
 * @subpackage physicalvol
 *
 */
class physicalvolActions extends sfActions
{
   /**
   * Initializes a physical device
   *
   * $request may contain the following keys:
   * - nid: virt agent node ID
   * - dev: device name Ex: /dev/sdb1   
   *
   * Returns json array('success'=>true,'response'=>$resp) on success
   * <br>or<br>
   * json array('success'=>false,'error'=>$error) on error
   *
   */
    // sends soap request and store info in DB after receiving SOAP response
    public function executeJsonInit(sfWebRequest $request)
    {

        $nid = $request->getParameter('nid');
        $dev = $request->getParameter('dev');        
        $uuid = $request->getParameter('uuid');

        $etva_node = EtvaNodePeer::getOrElectNode($request);

        if(!$etva_node){

            $msg_i18n = $this->getContext()->getI18N()->__(EtvaNodePeer::_ERR_NOTFOUND_ID_,array('%id%'=>$nid));

            $error = array('success'=>false,'agent'=>sfConfig::get('config_acronym'),'error'=>$msg_i18n,'info'=>$msg_i18n);

            //notify system log
            $node_log = Etva::getLogMessage(array('id'=>$nid), EtvaNodePeer::_ERR_NOTFOUND_ID_);
            $message = Etva::getLogMessage(array('name'=>$dev,'info'=>$node_log), EtvaPhysicalvolumePeer::_ERR_INIT_);
            $this->dispatcher->notify(
                new sfEvent(sfConfig::get('config_acronym'), 'event.log',
                    array('message' => $message,'priority'=>EtvaEventLogger::ERR)));

            // if is a CLI soap request return json encoded data
            if(sfConfig::get('sf_environment') == 'soap') return json_encode($error);

            // if is browser request return text renderer
            $error = $this->setJsonError($error);
            return $this->renderText($error);

        }
      
        // get DB info       
        if($uuid){
            $etva_pv = $etva_node->retrievePhysicalvolumeByUuid($uuid);
        }else{
            $etva_pv = $etva_node->retrievePhysicalvolumeByDevice($dev);
        }

        if(!$etva_pv){
            $msg = Etva::getLogMessage(array('name'=>$etva_node->getName(),'dev'=>$dev), EtvaNodePeer::_ERR_NODEV_);
            $msg_i18n = $this->getContext()->getI18N()->__(EtvaNodePeer::_ERR_NODEV_,array('%name%'=>$etva_node->getName(),'%dev%'=>$dev));
            $error = array('success'=>false,'agent'=>sfConfig::get('config_acronym'),'error'=>$msg_i18n);

            //notify system log            
            $message = Etva::getLogMessage(array('name'=>$dev,'info'=>$msg), EtvaPhysicalvolumePeer::_ERR_INIT_);
            $this->dispatcher->notify(
                new sfEvent(sfConfig::get('config_acronym'), 'event.log',
                    array('message' => $message,'priority'=>EtvaEventLogger::ERR)));

            // if is a CLI soap request return json encoded data
            if(sfConfig::get('sf_environment') == 'soap') return json_encode($error);

            $error = $this->setJsonError($error);
            return $this->renderText($error);

        }

        /*
         * send physical volume to VA
         */
        $pv_va = new EtvaPhysicalvolume_VA($etva_pv);
        $response = $pv_va->send_create($etva_node);


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
   * Uninitializes the physical volume
   *
   * Unsets the physical volume info.
   *
   * $request may contain the following keys:
   * - nid: virt agent node ID
   * - dev: device name Ex: /dev/sdb1
   *
   * Returns json array('success'=>true,'response'=>$resp) on success
   *  <br>or<br>
   * json array('success'=>false,'error'=>$error) on error
   *
   */
    public function executeJsonUninit(sfWebRequest $request)
    {
        $nid = $request->getParameter('nid');
        $dev = $request->getParameter('dev');        
        $etva_node = EtvaNodePeer::getOrElectNode($request);

        if(!$etva_node){

            $msg_i18n = $this->getContext()->getI18N()->__(EtvaNodePeer::_ERR_NOTFOUND_ID_,array('%id%'=>$nid));

            $error = array('success'=>false,'agent'=>sfConfig::get('config_acronym'),'error'=>$msg_i18n,'info'=>$msg_i18n);

            //notify system log
            $node_log = Etva::getLogMessage(array('id'=>$nid), EtvaNodePeer::_ERR_NOTFOUND_ID_);
            $message = Etva::getLogMessage(array('name'=>$dev,'info'=>$node_log), EtvaPhysicalvolumePeer::_ERR_UNINIT_);
            $this->dispatcher->notify(
                new sfEvent(sfConfig::get('config_acronym'), 'event.log',
                    array('message' => $message,'priority'=>EtvaEventLogger::ERR)));

            // if is a CLI soap request return json encoded data
            if(sfConfig::get('sf_environment') == 'soap') return json_encode($error);

            // if is browser request return text renderer
            $error = $this->setJsonError($error);
            return $this->renderText($error);

        }        

        // get DB info        
        if(!$etva_pv = $etva_node->retrievePhysicalvolumeByDevice($dev)){

            $msg = Etva::getLogMessage(array('name'=>$etva_node->getName(),'dev'=>$dev), EtvaNodePeer::_ERR_NODEV_);
            $msg_i18n = $this->getContext()->getI18N()->__(EtvaNodePeer::_ERR_NODEV_,array('%name%'=>$etva_node->getName(),'%dev%'=>$dev));
            $error = array('success'=>false,'agent'=>sfConfig::get('config_acronym'),'error'=>$msg_i18n,'info'=>$msg_i18n);

            //notify system log
            $message = Etva::getLogMessage(array('name'=>$dev,'info'=>$msg), EtvaPhysicalvolumePeer::_ERR_UNINIT_);
            $this->dispatcher->notify(
                new sfEvent(sfConfig::get('config_acronym'), 'event.log',
                    array('message' => $message,'priority'=>EtvaEventLogger::ERR)));

            // if is a CLI soap request return json encoded data
            if(sfConfig::get('sf_environment') == 'soap') return json_encode($error);

            $error = $this->setJsonError($error);
            return $this->renderText($error);

        }

        //check if as volume group associated
        $pv_vg = $etva_pv->getEtvaVolumePhysicals();
        if(count($pv_vg)>0){            

            $vg = $pv_vg[0]->getEtvaVolumegroup();
            $msg = Etva::getLogMessage(array('name'=>$etva_pv->getPv(),'vg'=>$vg->getVg()), EtvaPhysicalvolumePeer::_VGASSOC_);
            $msg_i18n = $this->getContext()->getI18N()->__(EtvaPhysicalvolumePeer::_VGASSOC_,array('%name%'=>$etva_pv->getPv(),'%vg%'=>$vg->getVg()));
            
            $error = array('success'=>false,
                           'agent'=>$etva_node->getName(),
                           'error'=>$msg_i18n,
                           'info'=>$msg_i18n);

            //notify system log
            $message = Etva::getLogMessage(array('name'=>$dev,'info'=>$msg), EtvaPhysicalvolumePeer::_ERR_UNINIT_);
            $this->dispatcher->notify(
                new sfEvent($etva_node->getName(), 'event.log',
                    array('message' => $message,'priority'=>EtvaEventLogger::ERR)));

            // if is a CLI soap request return json encoded data
            if(sfConfig::get('sf_environment') == 'soap') return json_encode($error);

            $error = $this->setJsonError($error);
            return $this->renderText($error);

        }
        

        /*
         * send physical volume to VA
         */
        $pv_va = new EtvaPhysicalvolume_VA($etva_pv);
        $response = $pv_va->send_remove($etva_node);


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


    /*
     * lists pv types
     */
    public function executeJsonListTypes()
    {        
        $criteria = new Criteria();
        $criteria->addGroupByColumn(EtvaPhysicalvolumePeer::STORAGE_TYPE);
        $criteria->setDistinct();
        $pv_types = EtvaPhysicalvolumePeer::doSelect($criteria);

        $elements = array();
        foreach ($pv_types as $type){            
            $elements[] = array('name'=>$type->getStorageType());
        }

        $result = array('success'=>true,
                    'total'=> count($pv_types)
                     ,'data'=> $elements
        );


        $return = json_encode($result);
        $this->getResponse()->setHttpHeader('Content-type', 'application/json');

        return $this->renderText($return);
        

    }

    /**
     * Return pre-formatted data for tree-column extjs
     *
     * $request may contain the following keys:
     * - cid: cid (Cluster ID)
     * @return array json array
     */
    public function executeJsonClusterPhydiskTree(sfWebRequest $request)
    {
        /*
         * criteria to select only cluster ID column matching with incoming parameter
         *
         */
        $cluster_id = $request->getParameter('cid');

        $criteria = new Criteria();
        $criteria->add(EtvaPhysicalvolumePeer::CLUSTER_ID, $cluster_id, CRITERIA::EQUAL);
        $criteria->addAnd(EtvaPhysicalvolumePeer::STORAGE_TYPE, EtvaPhysicalvolume::STORAGE_TYPE_LOCAL_MAP, Criteria::ALT_NOT_EQUAL);
//        $criteria->addAscendingOrderByColumn(EtvaPhysicalvolumePeer::STORAGE_TYPE);
        $cluster_devs = EtvaPhysicalvolumePeer::doSelect($criteria);
//return;

        
//        $node_devs = EtvaNodePhysicalVolumePeer::doSelectJoinEtvaPhysicalvolume($criteria);
        $storages = array();
        $storage_types = array();

        foreach($cluster_devs as $dev){
//            $dev = $data->getEtvaPhysicalvolume();
            $storage_type = $dev->getStorageType();

//            $dev = new EtvaPhysicalvolume();


            $id = $dev->getId();
            $device = $dev->getDevice();
//            $device = $dev->get $data->getDevice();
            $tag = $device;
            $uuid = $dev->getUuid();
            $qtip_i18n = $this->getContext()->getI18N()->__(EtvaPhysicalvolumePeer::_PVUNINIT_,array('%name%'=>$tag));
            $qtip = $qtip_i18n;
            $type = $cls = 'dev-pd';


            $pvsize = $dev->getPvsize();
            $pretty_pvsize = $dev->getPvsize();

            $size = $dev->getDevsize();
            $pretty_size = $dev->getDevsize();

            $devicesize = $size;
            $pretty_devicesize = $pretty_size;

            if($dev->getPvinit())
            {
                $qtip_i18n = $this->getContext()->getI18N()->__(EtvaPhysicalvolumePeer::_PVINIT_,array('%name%'=>$tag));
                $qtip = $qtip_i18n;
                $type = 'dev-pv';
                $cls = 'dev-pv';

                $devicesize = $pvsize;
                $pretty_devicesize = $pretty_pvsize;
            }

            $qtip .= '<br>'.$uuid;


            if(sfConfig::get('sf_environment') == 'soap'){
                $children = array('device'=>$device,'iconCls'=>'task','cls'=>$cls,'text'=>$tag,'size'=>$size,'storage_type'=>$storage_type,'prettysize'=>$pretty_size,'pvsize'=>$pvsize,'pretty-pvsize'=>$pretty_pvsize,'devicesize'=>$devicesize,'pretty-devicesize'=>$pretty_devicesize, 'singleClickExpand'=>true,'type'=>$type,'qtip'=>$qtip,'leaf'=>true);
            }else
            {
                $children = array('id'=>$id,'device'=>$device,'iconCls'=>'task','cls'=>$cls,'text'=>$tag,'size'=>$size,'storage_type'=>$storage_type,'prettysize'=>$pretty_size,'pvsize'=>$pvsize,'pretty-pvsize'=>$pretty_pvsize,'devicesize'=>$devicesize,'pretty-devicesize'=>$pretty_devicesize, 'singleClickExpand'=>true,'type'=>$type,'qtip'=>$qtip,'uuid'=>$uuid,'leaf'=>true);

            }

            $storage_types[$storage_type][] = $children;

        }


        foreach($storage_types as $type=>$data)
        {
            $storages[] = array('id'=>$type,'iconCls'=>'devices-folder','text'=>$type,'expanded'=>true, 'singleClickExpand'=>true,'children'=>$data);

        }


        if(empty($storages)){
            $msg_i18n = $this->getContext()->getI18N()->__('No data found');
            $storages[] = array('expanded'=>true,'text'=>$msg_i18n,'qtip'=>$msg_i18n,'leaf'=>true);
        }
        $return = json_encode($storages);

        if(sfConfig::get('sf_environment') == 'soap') return $return;
        else{

            $this->getResponse()->setHttpHeader('Content-type', 'application/json');
            return $this->renderText($return);

        }

    }


    /**
     * Return pre-formatted data for tree-column extjs
     *
     * $request may contain the following keys:
     * - nid: nid (virtAgent node ID)
     * @return array json array
     */
    public function executeJsonPhydiskTree(sfWebRequest $request)
    {
        /*
         * criteria to select only node ID column matching with nid 
         * 
         */
        $criteria = new Criteria();
        
        $criteria->add(EtvaNodePhysicalVolumePeer::NODE_ID,$request->getParameter('nid'));
        $criteria->addAscendingOrderByColumn(EtvaPhysicalvolumePeer::STORAGE_TYPE);

        $node_devs = EtvaNodePhysicalVolumePeer::doSelectJoinEtvaPhysicalvolume($criteria);
        $storages = array();
        $storage_types = array();
        
        foreach($node_devs as $data){
            $dev = $data->getEtvaPhysicalvolume();
            $storage_type = $dev->getStorageType();

            $id = $dev->getId();            
            $device = $data->getDevice();
            $tag = $device;
            $uuid = $dev->getUuid();
            $qtip_i18n = $this->getContext()->getI18N()->__(EtvaPhysicalvolumePeer::_PVUNINIT_,array('%name%'=>$tag));
            $qtip = $qtip_i18n;
            $type = $cls = 'dev-pd';
            

            $pvsize = $dev->getPvsize();
            $pretty_pvsize = $dev->getPvsize();

            $size = $dev->getDevsize();
            $pretty_size = $dev->getDevsize();            

            $devicesize = $size;
            $pretty_devicesize = $pretty_size;

            if($dev->getPvinit())
            {
                $qtip_i18n = $this->getContext()->getI18N()->__(EtvaPhysicalvolumePeer::_PVINIT_,array('%name%'=>$tag));                
                $qtip = $qtip_i18n;
                $type = 'dev-pv';
                $cls = 'dev-pv';

                $devicesize = $pvsize;
                $pretty_devicesize = $pretty_pvsize;
            }

            $qtip .= '<br>'.$uuid;


            if(sfConfig::get('sf_environment') == 'soap'){
                $children = array('device'=>$device,'iconCls'=>'task','cls'=>$cls,'text'=>$tag,'size'=>$size,'storage_type'=>$storage_type,'prettysize'=>$pretty_size,'pvsize'=>$pvsize,'pretty-pvsize'=>$pretty_pvsize,'devicesize'=>$devicesize,'pretty-devicesize'=>$pretty_devicesize, 'singleClickExpand'=>true,'type'=>$type,'qtip'=>$qtip,'leaf'=>true);
            }else
            {
                $children = array('id'=>$id,'device'=>$device,'iconCls'=>'task','cls'=>$cls,'text'=>$tag,'size'=>$size,'storage_type'=>$storage_type,'prettysize'=>$pretty_size,'pvsize'=>$pvsize,'pretty-pvsize'=>$pretty_pvsize,'devicesize'=>$devicesize,'pretty-devicesize'=>$pretty_devicesize, 'singleClickExpand'=>true,'type'=>$type,'qtip'=>$qtip,'uuid'=>$uuid,'leaf'=>true);

            }

            $storage_types[$storage_type][] = $children;
            
        }

        
        foreach($storage_types as $type=>$data)
        {
            $storages[] = array('id'=>$type,'iconCls'=>'devices-folder','text'=>$type,'expanded'=>true, 'singleClickExpand'=>true,'children'=>$data);

        }
        

        if(empty($storages)){
            $msg_i18n = $this->getContext()->getI18N()->__('No data found');
            $storages[] = array('expanded'=>true,'text'=>$msg_i18n,'qtip'=>$msg_i18n,'leaf'=>true);
        }
        $return = json_encode($storages);

        if(sfConfig::get('sf_environment') == 'soap') return $return;
        else{

            $this->getResponse()->setHttpHeader('Content-type', 'application/json');
            return $this->renderText($return);

        }

    }

  /**
   * Returns pre-formated data with physical volumes allocatable
   *   
   *
   * $request may contain the following keys:
   * - nid: nid (virtAgent node ID)
   * - filter pair (field => value)
   * @return array json array('total'=>num elems, 'data'=>array('id'=>pvdevice,'name'=>name))
   */

  /*
   * Used in volume group create window and to list allocatable pv
   */

    public function executeJsonListAllocatable(sfWebRequest $request)
    {

        $elements = array();

        //adding cluster id filter
        $cid = $request->getParameter('cid');
        $nid = $request->getParameter('nid');
        $level = $request->getParameter('level');

        if(!$level)
            $level = 'node';

        //get the
        $filter = json_decode($request->getParameter('filter'),true);
        $criteria = new Criteria();
        $criteria->add(EtvaPhysicalvolumePeer::ALLOCATABLE, 1);
        $criteria->add(EtvaPhysicalvolumePeer::PVINIT, 1);

        foreach($filter as $field => $value)
        {
            $column = EtvaPhysicalvolumePeer::translateFieldName(sfInflector::camelize($field), BasePeer::TYPE_PHPNAME, BasePeer::TYPE_COLNAME);
            $criteria->add($column, $value);
        }

        if($level == 'cluster'){
            error_log("treated as a cluster");
            $etva_cluster = EtvaClusterPeer::retrieveByPK($cid);
            $etva_pvs = $etva_cluster->getEtvaPhysicalvolumes($criteria);
        }elseif($level == 'node'){
            error_log("treated as a node");
            $etva_node = EtvaNodePeer::retrieveByPK($nid);
            $etva_pvs = $etva_node->getEtvaNodePhysicalVolumesJoinEtvaPhysicalvolume($criteria);
        }else{
            return;
        }

        if(!$etva_pvs){
            $msg_i18n = $this->getContext()->getI18N()->__(EtvaPhysicalvolumePeer::_NONE_AVAILABLE_);
            $info = array('success'=>false,'agent'=>sfConfig::get('config_acronym'),'info'=>$msg_i18n,'error'=>$msg_i18n);

            if(sfConfig::get('sf_environment') == 'soap') return json_encode($info);

            $error = $this->setJsonError($info);
            return $this->renderText($error);
        }

        if(sfConfig::get('sf_environment') == 'soap'){
            foreach ($etva_pvs as $elem){
                $etva_pv = $elem->getEtvaPhysicalVolume();
                $id = $etva_pv->getId();
                $pv = $etva_pv->getPv();                
                $elements[$id] = array('id'=>$id,'pv'=>$pv);
            }

        }else{

            foreach ($etva_pvs as $elem){
                if($level == 'node'){
                    $etva_pv = $elem->getEtvaPhysicalVolume();
                }elseif($level == 'cluster'){
                    $etva_pv = $elem;
                }
                // $id = $elem->getId();
                //error_log(print_r($etva_pv, true));
                $name = $etva_pv->getName();
                $pv = $etva_pv->getPv();                
                $elements[] = array('id'=>$pv,'name'=>$name);
            }

        }

        $result = array('success'=>true,
                    'total'=> count($elements),
                    'response'=> $elements
        );


        $return = json_encode($result);

        if(sfConfig::get('sf_environment') == 'soap') return $return;

        $this->getResponse()->setHttpHeader('Content-type', 'application/json');
        return $this->renderText($return);
    }


    public function executeJsonList(sfWebRequest $request)
    {

        $elements = array();

        $nid = $request->getParameter('nid');

        $etva_node = EtvaNodePeer::retrieveByPK($nid);

        $criteria = new Criteria();        

        $etva_pvs = $etva_node->getEtvaNodePhysicalvolumesJoinEtvaPhysicalvolume();

        
        foreach ($etva_pvs as $elem){
            $etva_pv = $elem->getEtvaPhysicalVolume();
            $id = $etva_pv->getId();
            $pv = $etva_pv->getPv();            
            $elements[$id] = array('id'=>$id,'pv'=>$pv);
        }

       

        $result = array('success'=>true,
                    'total'=> count($elements),
                    'response'=> $elements
        );


        $return = json_encode($result);

        if(sfConfig::get('sf_environment') == 'soap') return $return;

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
   * Used to process soap requests => updateVirtAgentDevices
   *
   * Updates physical volume/device info sent by virt Agent
   * The request should be made throught soapapi
   *
   * Replies with succcess
   *
   * $request may contain the following keys:
   * - uuid: uuid (virtAgent sending request uuid)
   * - devs (object containing devices info)
   * @return array array(success=>true)
   */

   public function executeSoapUpdate(sfWebRequest $request)
   {

       if(sfConfig::get('sf_environment') == 'soap'){

            $devs = $request->getParameter('devs');

            // check node ID correspondig to the uuid given
            $c = new Criteria();
            $c->add(EtvaNodePeer::UUID ,$request->getParameter('uuid'));            

            if(!$etva_node = EtvaNodePeer::doSelectOne($c)){
                $error_msg = sprintf('Object etva_node does not exist (%s).', $request->getParameter('uuid'));
                $error = array('success'=>false,'error'=>$error_msg);
                
                //notify system log
                $node_message = Etva::getLogMessage(array('name'=>$request->getParameter('uuid')), EtvaNodePeer::_ERR_NOTFOUND_UUID_);
                $message = Etva::getLogMessage(array('info'=>$node_message), EtvaPhysicalvolumePeer::_ERR_SOAPUPDATE_);
                $this->dispatcher->notify(
                    new sfEvent(sfConfig::get('config_acronym'),
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
                $message = Etva::getLogMessage(array('info'=>$cluster_message), EtvaPhysicalvolumePeer::_ERR_SOAPUPDATE_);
                $this->dispatcher->notify(
                    new sfEvent(sfConfig::get('config_acronym'),
                            'event.log',
                            array('message' =>$message,'priority'=>EtvaEventLogger::ERR)
                ));
            
                return $error;
            }      

            /*
             * send physical volume to VA
             */
            $pv_va = new EtvaPhysicalvolume_VA();
            $response = $pv_va->initialize($etva_node,$devs);
            return $response;            

          
       }
       
   }


}
