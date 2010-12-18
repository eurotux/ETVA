<?php

class EtvaNetwork extends BaseEtvaNetwork
{
    public function save(PropelPDO $con = null)
	{
        
        $etva_vlan = EtvaVlanPeer::retrieveByName($this->getVlan());
        $etva_mac = EtvaMacPeer::retrieveByMac($this->getMac());
        
        if(!$etva_mac || !$etva_vlan) return ;
            
        if($etva_mac->getInUse()) return;
        
        $etva_mac->setInUse(1);
        $etva_mac->save();
                       
		$ret = parent::save($con);

		return $ret;
	}

    public function updateTarget($target){
        $this->setTarget($target);
        parent::save();
    }

    public function delete(PropelPDO $con = null){

        $mac = EtvaMacPeer::retrieveByMac($this->getMac());
        $mac->setInUse(0);
        $mac->save();
        
        parent::delete($con);

    }


    public function deleteremoveMacFlag()
	{
        
        $mac = EtvaMacPeer::retrieveByMac($this->mac);
        $mac->setInUse(0);
        $mac->save();


  }
}
