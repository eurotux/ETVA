<?php

class EtvaNode extends BaseEtvaNode
{
    public function getServers()
    {
        $criteria = new Criteria();
        $criteria->add(EtvaServerPeer::NODE_ID, $this->getId());
        return EtvaServerPeer::getServers($criteria);
    }


 


    public function __toString()
    {
        return $this->getName();
    }

    public function soapSend($method,$params=null){
        

        $addr = $this->getIP();
        $port = $this->getPort();
        $proto = "tcp";
        $host = "" . $proto . "://" . $addr . ":" . $port;
        
        if(!$params) $params = array("nil"=>"true");

        $soap = new soapClient($host,$port);

        $response = $soap->processSoap($method, $params);

        return $response;
        
    }

    public function retrievePhysicalvolumeByPv($pv){
        $criteria = new Criteria();
        $criteria->add(EtvaPhysicalvolumePeer::NODE_ID, $this->getId());
        
        return EtvaPhysicalvolumePeer::retrieveByPv($pv,$criteria);
    }
    
    public function retrievePhysicalvolumeByDevice($dev){
        $criteria = new Criteria();
        $criteria->add(EtvaPhysicalvolumePeer::NODE_ID, $this->getId());
        
        return EtvaPhysicalvolumePeer::retrieveByDevice($dev,$criteria);
    }

    public function retrieveVolumegroupByVg($vg){
        $criteria = new Criteria();
        $criteria->add(EtvaVolumegroupPeer::NODE_ID, $this->getId());

        return EtvaVolumegroupPeer::retrieveByVg($vg,$criteria);
    }

    public function retrieveLogicalvolumeByLv($lv){
        $criteria = new Criteria();
        $criteria->add(EtvaLogicalvolumePeer::NODE_ID, $this->getId());

        return EtvaLogicalvolumePeer::retrieveByLv($lv, $criteria);
    }

    public function retrieveServerByName($server){
        $criteria = new Criteria();
        $criteria->add(EtvaServerPeer::NODE_ID, $this->getId());

        return EtvaServerPeer::retrieveByName($server, $criteria);
    }



}
