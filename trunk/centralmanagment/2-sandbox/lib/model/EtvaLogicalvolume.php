<?php

class EtvaLogicalvolume extends BaseEtvaLogicalvolume
{

    public function getEtvaServer(PropelPDO $con = null)
	{
		
		$c = new Criteria(EtvaServerPeer::DATABASE_NAME);
		$c->add(EtvaServerPeer::LOGICALVOLUME_ID, $this->id);
	    $this->aEtvaServer = EtvaServerPeer::doSelectOne($c, $con);
		
		return $this->aEtvaServer;
	}
    
    
}
