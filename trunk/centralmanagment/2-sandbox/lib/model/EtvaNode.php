<?php

class EtvaNode extends BaseEtvaNode
{
    const NAME_MAP = 'name';
    const RESERVED_MEM_MB = 640;
    const NODE_ACTIVE = 1;
    const NODE_INACTIVE = 0;
    const INITIALIZE_OK = 'ok';
    const INITIALIZE_PENDING = 'pending';

    public function initData($arr)
	{
        if(array_key_exists(self::NAME_MAP, $arr)) $this->setName($arr[self::NAME_MAP]);        

	}

    /*
     * sets last message received. overwrites if any already exists
     */
    public function setErrorMessage($action, $message = null)
    {
        if(!$message) $message = EtvaNodePeer::_PROBLEM_;
        
        $data = array('message' => $message, 'priority' =>EtvaEventLogger::ERR, 'action' =>$action);
        
        $this->setLastMessage(json_encode($data));
        $this->save();
    }

    /*
     * clears message of action if exists
     */
    public function clearErrorMessage($action)
    {
        $last_message = $this->getLastMessage();
        $last_message_decoded = json_decode($last_message,true);
        
        if(isset($last_message_decoded['action'])){
            $this->setLastMessage('');
            $this->save();
        }
    }


    public function getServers()
    {
        $criteria = new Criteria();
        $criteria->add(EtvaServerPeer::NODE_ID, $this->getId());
        return EtvaServerPeer::getServers($criteria);
    }

    /*
     * returns nodes from the same cluster as this
     */
    public function getNodesCluster(Criteria $criteria)
    {
        if(!$criteria) $criteria = new Criteria();
        $criteria->add(EtvaNodePeer::CLUSTER_ID, $this->getClusterId());
        return EtvaNodePeer::doSelect($criteria);
    }

    public function getEtvaLogicalvolumes(Criteria $criteria = null)
    {

		if ($criteria === null) {
			$criteria = new Criteria();
		}		
        
        $criteria->add(EtvaNodeLogicalvolumePeer::NODE_ID, $this->getId());
        $criteria->add(EtvaLogicalvolumePeer::LV,'etva-isos',Criteria::NOT_EQUAL);
        $criteria->addJoin(EtvaNodeLogicalvolumePeer::LOGICALVOLUME_ID, EtvaLogicalvolumePeer::ID);        
        $criteria->addAscendingOrderByColumn(EtvaLogicalvolumePeer::LV);

        return EtvaLogicalvolumePeer::doSelect($criteria);
    }
 


    public function __toString()
    {
        return $this->getName();
    }

    public function setSoapTimeout($val) //seconds
    {
        $this->rcv_timeout = $val;

    }


    /*
     * Send soap request to VirtAgent
     * It onlys send request id state is 1. Relies on virtAgent to send state=1
     * If virtAgent state is 0 and forceRequest = false does not send request
     * If forceRequest flag is true the request will always be sent.
     * If virtAgent request is sent and returns TCP failure sets state to 0
     */
    public function soapSend($method,$params=null,$forceRequest=false,$rcv_timeout=0){
        $dispatcher = sfContext::getInstance()->getEventDispatcher();
        $addr = $this->getIP();
        $port = $this->getPort();
        $proto = "tcp";
        $host = "" . $proto . "://" . $addr . ":" . $port;
        $state = $this->getState();
        $agent = $this->getName();

        if(!$params) $params = array("nil"=>"true");
        $request_array = array('request'=>array(
                            'agent'=>$agent,
                            'host'=>$host,
                            'port'=>$port,
                            'method'=>$method,
                            'params'=>$params));                

        if(!$state && !$forceRequest){
            /*
             * if current state is 0 DO NOT send soap request
             * this approach avoids generating unnecessary traffic since if
             *  the agent is alive it should update its state
             *
             */
            // if current state is 0 DO NOT send soap request
            $info = sfContext::getInstance()->getI18N()->__('VirtAgent down! Request not sent!');
            $error = sfContext::getInstance()->getI18N()->__('VirtAgent down');
            $response = array('success'=>false,'agent'=>$this->getName(),'error'=>$error,'info'=>$info);
        }else{
            //if state reports 1 send request....
            $soap = new soapClient_($host,$port);
            if($rcv_timeout) $soap->set_rcv_timeout($rcv_timeout);
            else if($this->rcv_timeout) $soap->set_rcv_timeout($this->rcv_timeout);
            $response = $soap->processSoap($method, $params);
            $response['agent'] = $this->getName();


            /*
             * if response is TCP failure then VirtAgent is not reachable
             * set state to 0
             */
            if($response['faultcode'] && $response['faultcode']=='TCP')
            {
                $this->setState(0);
                $this->save();
            }else{

                //keepalive update
                $this->setState(1);
                $this->setLastKeepalive('NOW');
                $this->save();
            }

        }

        $response_array = array('response'=>$response);
        
        $all_params = array_merge($request_array,$response_array);

        $dispatcher->notify(new sfEvent($this, sfConfig::get('app_virtsoap_log'),$all_params));

        return $response;
        
    }

    public function retrievePhysicalvolumeByPv($pv){
        $criteria = new Criteria();
        $criteria->add(EtvaNodePhysicalvolumePeer::NODE_ID, $this->getId());
        $criteria->addJoin(EtvaNodePhysicalvolumePeer::PHYSICALVOLUME_ID, EtvaPhysicalvolumePeer::ID);
        $criteria->add(EtvaPhysicalvolumePeer::PV, $pv);
        
        return EtvaPhysicalvolumePeer::doSelectOne($criteria);
    }

    public function retrievePhysicalvolumeByUuid($uuid){
        $criteria = new Criteria();
        $criteria->add(EtvaPhysicalvolumePeer::UUID, $uuid);

        return EtvaPhysicalvolumePeer::doSelectOne($criteria);
    }


    /*
     * retrieves node physical volume info with matching current node and device
     */
    public function retrievePhysicalvolumeByDevice($dev){
        $criteria = new Criteria();
        $criteria->add(EtvaNodePhysicalvolumePeer::NODE_ID, $this->getId());
        $criteria->add(EtvaNodePhysicalvolumePeer::DEVICE, $dev);
        $criteria->addJoin(EtvaNodePhysicalvolumePeer::PHYSICALVOLUME_ID, EtvaPhysicalvolumePeer::ID);

        return EtvaPhysicalvolumePeer::doSelectOne($criteria);
    }


    public function retrieveVolumegroupByVg($vg){
        $criteria = new Criteria();
        $criteria->add(EtvaNodeVolumegroupPeer::NODE_ID, $this->getId());
        $criteria->addJoin(EtvaNodeVolumegroupPeer::VOLUMEGROUP_ID, EtvaVolumegroupPeer::ID);
        $criteria->add(EtvaVolumegroupPeer::VG, $vg);

        return EtvaVolumegroupPeer::doSelectOne($criteria);
    }

    public function retrieveLogicalvolumeByLv($lv){

        $criteria = new Criteria();
        $criteria->add(EtvaNodeLogicalvolumePeer::NODE_ID, $this->getId());
        $criteria->addJoin(EtvaNodeLogicalvolumePeer::LOGICALVOLUME_ID, EtvaLogicalvolumePeer::ID);
        $criteria->add(EtvaLogicalvolumePeer::LV, $lv);

        return EtvaLogicalvolumePeer::doSelectOne($criteria);
    }
    public function retrieveLogicalvolumeByVgLv($vg,$lv){

        $criteria = new Criteria();
        $criteria->add(EtvaNodeLogicalvolumePeer::NODE_ID, $this->getId());
        $criteria->add(EtvaLogicalvolumePeer::LV, $lv);
        $criteria->add(EtvaVolumegroupPeer::VG, $vg);
        $criteria->addJoin(EtvaNodeLogicalvolumePeer::LOGICALVOLUME_ID, EtvaLogicalvolumePeer::ID);
        $criteria->addJoin(EtvaLogicalvolumePeer::VOLUMEGROUP_ID, EtvaVolumegroupPeer::ID);

        return EtvaLogicalvolumePeer::doSelectOne($criteria);
    }
    public function retrieveLogicalvolumeByUuid($uuid){

        $criteria = new Criteria();
        $criteria->add(EtvaNodeLogicalvolumePeer::NODE_ID, $this->getId());
        $criteria->addJoin(EtvaNodeLogicalvolumePeer::LOGICALVOLUME_ID, EtvaLogicalvolumePeer::ID);
        $criteria->add(EtvaLogicalvolumePeer::UUID, $uuid);

        return EtvaLogicalvolumePeer::doSelectOne($criteria);
    }
    public function retrieveLogicalvolume($uuid = null, $vg = null, $lv = null){
        if( $uuid )
            return $this->retrieveLogicalvolumeByUuid($uuid);
        else if( $vg && $lv )
            return $this->retrieveLogicalvolumeByVgLv($vg,$lv);
        else
            return $this->retrieveLogicalvolumeByLv($lv);
    }

    public function retrieveServerByName($server){
        $criteria = new Criteria();
        $criteria->add(EtvaServerPeer::NODE_ID, $this->getId());

        return EtvaServerPeer::retrieveByName($server, $criteria);
    }

    /*
     * gets array of fields names in DB
     */
    public function toDisplay()
	{

        $array_data = $this->toArray(BasePeer::TYPE_FIELDNAME);
        $array_data['mem_text'] = Etva::Byte_to_MBconvert($array_data['memtotal']);
        $array_data['mem_available'] = Etva::Byte_to_MBconvert($array_data['memfree']);
        $array_data['state_text'] = $array_data['state'] == 0 ? 'Down' : 'Up';
		
		return $array_data;       
	}
    
    /*
     * update memfree based on total of system mem and vms mem
     */
    public function updateMemFree()
    {        
        $criteria = new Criteria();
        $criteria->add(EtvaServerPeer::NODE_ID,$this->getId());
        $criteria->add(EtvaServerPeer::VM_STATE,'stop',Criteria::NOT_EQUAL);    // count vms that not stopped
        $total_vms = EtvaServerPeer::getTotalMem($criteria);
        $total_vms = Etva::MB_to_Byteconvert($total_vms);
        $sys_mem = Etva::MB_to_Byteconvert(self::RESERVED_MEM_MB);
        $mem_free = $this->getMemtotal()-$sys_mem-$total_vms;
        $this->setMemfree($mem_free);
    }

    public function getMaxMem()
    {
        $sys_mem = Etva::MB_to_Byteconvert(self::RESERVED_MEM_MB);
        $max_mem = $this->getMemtotal()-$sys_mem;
        return $max_mem;
    }

    /*
     * before delete node from db delete other info...
     */
    public function preDelete(PropelPDO $con = null)
    {

        /*
         * delete servers info
         *
         */
        $servers = $this->getEtvaServers();
        foreach($servers as $server)
            $server->deleteServer(true); //keep lvs....will be deleted later



        /*
         * delete lvs that are not shared....numVgs=1 only
         *
         */
        $criteria = new Criteria();       
        $criteria->add(EtvaLogicalvolumePeer::CLUSTER_ID, $this->getClusterId());
        $criteria->addGroupByColumn(EtvaNodeLogicalvolumePeer::LOGICALVOLUME_ID);
        $criteria->addAsColumn('numLvs', 'COUNT('.EtvaNodeLogicalvolumePeer::LOGICALVOLUME_ID.')');
        $criteria->addHaving($criteria->getNewCriterion(EtvaNodeLogicalvolumePeer::LOGICALVOLUME_ID, 'numLvs=1',Criteria::CUSTOM));

        $records = EtvaNodeLogicalvolumePeer::doSelectJoinEtvaLogicalvolume($criteria);

        foreach ($records as $record)
        {
            $etva_lv = $record->getEtvaLogicalvolume();
            if($record->getNodeId() == $this->getId()) $etva_lv->delete();
        }
        


        /*
         * delete vgs that are not shared....numVgs=1 only
         *
         */
        $criteria = new Criteria();
        $criteria->add(EtvaVolumegroupPeer::CLUSTER_ID, $this->getClusterId());
        $criteria->addGroupByColumn(EtvaNodeVolumegroupPeer::VOLUMEGROUP_ID);
        $criteria->addAsColumn('numVgs', 'COUNT('.EtvaNodeVolumegroupPeer::VOLUMEGROUP_ID.')');
        $criteria->addHaving($criteria->getNewCriterion(EtvaNodeVolumegroupPeer::VOLUMEGROUP_ID, 'numVgs=1',Criteria::CUSTOM));
        $records = EtvaNodeVolumegroupPeer::doSelectJoinEtvaVolumegroup($criteria);

        foreach ($records as $record)
        {
            $etva_vg = $record->getEtvaVolumegroup();
            if($record->getNodeId() == $this->getId()) $etva_vg->delete();
        }


        /*
         * delete pvs that are not shared....numVgs=1 only
         *
         */
        $criteria = new Criteria();
        $criteria->add(EtvaPhysicalvolumePeer::CLUSTER_ID, $this->getClusterId());
        $criteria->addGroupByColumn(EtvaNodePhysicalvolumePeer::PHYSICALVOLUME_ID);
        $criteria->addAsColumn('numPvs', 'COUNT('.EtvaNodePhysicalvolumePeer::PHYSICALVOLUME_ID.')');
        $criteria->addHaving($criteria->getNewCriterion(EtvaNodePhysicalvolumePeer::PHYSICALVOLUME_ID, 'numPvs=1',Criteria::CUSTOM));
        $records = EtvaNodePhysicalvolumePeer::doSelectJoinEtvaPhysicalvolume($criteria);

        foreach ($records as $record)
        {
            $etva_pv = $record->getEtvaPhysicalvolume();
            if($record->getNodeId() == $this->getId()) $etva_pv->delete();
        }

        // delete rra node dir and cpu load rrd
        $this->deleteRRAFiles();        

        return true;
    }



    public function deleteRRAFiles()
    {
        
        $node_uuid = $this->getUuid();

        $cpu_load = new NodeLoadRRA($node_uuid,false);
        $cpu_load->delete(true); // true == remove dir also
        
    }


    /*
     * removes node pvs, vgs and lvs
     */
    public function clearStorage()
    {
        $c = new Criteria();
        $c->add(EtvaNodeLogicalvolumePeer::NODE_ID, $this->getId());
        EtvaLogicalvolumePeer::doDelete($c);

        $c = new Criteria();
        $c->add(EtvaNodeVolumegroupPeer::NODE_ID, $this->getId());
        EtvaVolumegroupPeer::doDelete($c);

        $c = new Criteria();
        $c->add(EtvaNodePhysicalvolumePeer::NODE_ID, $this->getId());
        EtvaPhysicalvolumePeer::doDelete($c);

    }

    /*
     * returns nodes from the same cluster as this
     */
    public static function getFirstActiveNode(EtvaCluster $cluster)
    {
        $c = new Criteria();
        $c->add(EtvaNodePeer::CLUSTER_ID, $cluster->getId(), Criteria::EQUAL);
        $c->addAnd(EtvaNodePeer::STATE, EtvaNode::NODE_ACTIVE, Criteria::EQUAL);
        $c->addDescendingOrderByColumn(EtvaNodePeer::ID);
        $c->setLimit(1);
        error_log($c->toString());
        $etva_node = EtvaNodePeer::doSelectOne($c);
        return $etva_node;
    }



}
