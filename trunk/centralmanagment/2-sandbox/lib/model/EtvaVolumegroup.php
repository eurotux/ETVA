<?php

class EtvaVolumegroup extends BaseEtvaVolumegroup
{


    private $physicalvolumes;
    const UUID_MAP = 'uuid';
    const PHYSICALVOLUMES_MAP = 'physicalvolumes';    
    const SIZE_MAP = 'size';
    const FREESIZE_MAP = 'freesize';
    const VG_MAP = 'vg_name';

    const STORAGE_TYPE_MAP = 'type';
    const STORAGE_TYPE_LOCAL_MAP = 'local';

    /*
     * used to process soap comm data
     */
    public function initData($arr)
	{

        if(array_key_exists(self::UUID_MAP, $arr)) $this->setUuid($arr[self::UUID_MAP]);
        if(array_key_exists(self::SIZE_MAP, $arr)) $this->setSize($arr[self::SIZE_MAP]);
        if(array_key_exists(self::FREESIZE_MAP, $arr)) $this->setFreesize($arr[self::FREESIZE_MAP]);
        
        if(array_key_exists(self::VG_MAP, $arr)) $this->setVg($arr[self::VG_MAP]);

        if(array_key_exists(self::STORAGE_TYPE_MAP, $arr)) $this->setStorageType($arr[self::STORAGE_TYPE_MAP]);

       // TODO: no need to update physical volumes?? DB LOCK ON IMPORT OVF???
       // if (array_key_exists(self::PHYSICALVOLUMES_MAP, $arr)) $this->setPhysicalvolumes($arr[self::PHYSICALVOLUMES_MAP]);

	}

    public function _VA()
    {
        $vgs_pvs = $this->getEtvaVolumePhysicalsJoinEtvaPhysicalvolume();
        $pvs = array();
        foreach($vgs_pvs as $vg_pv){
            $pv = $vg_pv->getEtvaPhysicalvolume();
            $pvs[] = $pv->getPv();
        }



        $vg_VA = array(self::UUID_MAP => $this->getUuid(),                       
                       self::SIZE_MAP => $this->getSize(),
                       self::FREESIZE_MAP => $this->getFreesize(),
                       self::VG_MAP => $this->getVg(),
                       self::STORAGE_TYPE_MAP => $this->getStorageType(),
                       self::PHYSICALVOLUMES_MAP => $pvs);

        return $vg_VA;
    }
       	

    public function setPhysicalvolumes($v)
    {
        if(!$etva_cluster = $this->getEtvaCluster()) return;
        $list = array($v);

        $criteria = new Criteria();
        $criteria->add(EtvaPhysicalvolumePeer::CLUSTER_ID,$etva_cluster->getId());

        $etva_volphys = $this->getEtvaVolumePhysicals();
        $etva_phys_uuid = array();

        if($etva_volphys)
            foreach($etva_volphys as $etva_volphy){
                $etva_phy = $etva_volphy->getEtvaPhysicalvolume();
                $etva_phys_uuid[$etva_phy->getUuid()] = $etva_phy;
            }
                    

        foreach($v as $pv => $device){

                $device_array = (array) $device;
                $dev_uuid = $device_array['uuid'];


                if(in_array($dev_uuid,array_keys($etva_phys_uuid)))
                    $etva_physicalvol = $etva_phys_uuid[$dev_uuid];
                else
                    $etva_physicalvol = EtvaPhysicalvolumePeer::retrieveByUUID($dev_uuid,$criteria);
               
                $etva_physicalvol->initData($device_array);

                if($etva_physicalvol) $this->physicalvolumes[] = $etva_physicalvol;               
                
        }
      
        return $this;
    }

    public function save(PropelPDO $con = null)
    {
        
        if ($this->physicalvolumes !== null)
        {
            
            foreach($this->physicalvolumes as $physicalvol)
        {
            
            $etva_vg_phy = EtvaVolumePhysicalPeer::retrieveByVGPV($this->getId(),$physicalvol->getId());
            if(!$etva_vg_phy) $etva_vg_phy = new EtvaVolumePhysical();

            $etva_vg_phy->setEtvaPhysicalvolume($physicalvol);
            $this->addEtvaVolumePhysical($etva_vg_phy);

        }

        }

    

        
        parent::save($con);

    }

    public function hasLogicalVolumesInUse(){
        $num = EtvaLogicalVolumeQuery::create()
                    ->filterByEtvaVolumegroup($this)
                    ->useEtvaServerLogicalQuery()
                    ->endUse()
                    ->count();
        return ($num > 0) ? true : false;
    }


}
