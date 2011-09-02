<?php

class EtvaLogicalvolume extends BaseEtvaLogicalvolume
{
    
    private $volumegroup;
    const UUID_MAP = 'uuid';
    const VOLUMEGROUP_MAP = 'volumegroup';
    const SIZE_MAP = 'size';
    const FREESIZE_MAP = 'freesize';
    const LV_MAP = 'lv_name';
    const LVDEVICE_MAP = 'lvdevice';    
    const WRITEABLE_MAP = 'writeable';
    const TARGET_MAP = 'target';
    const PATH_MAP = 'path';
    const MOUNTED_MAP = 'mounted';

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

        if(array_key_exists(self::LV_MAP, $arr)) $this->setLv($arr[self::LV_MAP]);
        if(array_key_exists(self::LVDEVICE_MAP, $arr)) $this->setLvdevice($arr[self::LVDEVICE_MAP]);
        if(array_key_exists(self::WRITEABLE_MAP, $arr)) $this->setWriteable($arr[self::WRITEABLE_MAP]);
        if(array_key_exists(self::STORAGE_TYPE_MAP, $arr)) $this->setStorageType($arr[self::STORAGE_TYPE_MAP]);
        if(array_key_exists(self::TARGET_MAP, $arr)) $this->setTarget($arr[self::TARGET_MAP]);

        if(array_key_exists(self::MOUNTED_MAP, $arr)) $this->setMounted($arr[self::MOUNTED_MAP]);

//        if (array_key_exists(self::VOLUMEGROUP_MAP, $arr)) $this->setVolumegroup($arr[self::VOLUMEGROUP_MAP]);

	}

    public function _VA()
    {
        $vg = $this->getEtvaVolumegroup();
        $vg_name = $vg->getVg();

        $lv_VA = array(self::UUID_MAP => $this->getUuid(),
                       self::SIZE_MAP => $this->getSize(),
                       self::FREESIZE_MAP => $this->getFreesize(),
                       self::LV_MAP => $this->getLv(),
                       self::LVDEVICE_MAP => $this->getLvdevice(),
                       self::WRITEABLE_MAP => $this->getWriteable(),
                       self::STORAGE_TYPE_MAP => $this->getStorageType(),
                       self::TARGET_MAP => $this->getTarget(),
                       self::VOLUMEGROUP_MAP => $vg_name);

        return $lv_VA;
    }

    public function setVolumegroup($v)
    {

        if(!$etva_cluster = $this->getEtvaCluster()) return;
       
        $criteria = new Criteria();
        $criteria->add(EtvaVolumegroupPeer::CLUSTER_ID,$etva_cluster->getId());

        $volgroup_array = (array) $v;
        $vg_uuid = $volgroup_array['uuid'];
        
        $etva_volgroup = $this->getEtvaVolumegroup();
        
        if(!$etva_volgroup){
            $etva_volgroup = EtvaVolumegroupPeer::retrieveByUUID($vg_uuid,$criteria);
            
        }
        $etva_volgroup->initData($volgroup_array);
        

        if($etva_volgroup) $this->volumegroup = $etva_volgroup;

        
                   
        return $this;
    }

    public function save(PropelPDO $con = null)
    {

        if ($this->volumegroup !== null)
        {
            $this->setEtvaVolumegroup($this->volumegroup);            
        }

        parent::save($con);

    }


    /*
     * returns server associated with this lv
     */
    public function getEtvaServer(PropelPDO $con = null)
	{

      $criteria = new Criteria();
      $criteria->add(EtvaServerLogicalPeer::LOGICALVOLUME_ID, $this->getId());
      $criteria->addJoin(EtvaServerPeer::ID, EtvaServerLogicalPeer::SERVER_ID);
      return EtvaServerPeer::doSelectOne($criteria, $con);

	}

    /*
     * checks if new size is valid
     */
    public function canResizeTo($sizeMB)
    {
        $vg = $this->getEtvaVolumegroup();
        $size_bytes = Etva::MB_to_Byteconvert($sizeMB);
                
        $total_available_size = $vg->getFreesize()+$this->size;
                
        if($size_bytes > 0 && $size_bytes <= $total_available_size) return true;
        else return false;

    }

    /*
     * before delete update volume group freesize
     */
    public function preDelete(PropelPDO $con = null)
    {


        $vg = $this->getEtvaVolumegroup();
        $vg_freesize = $vg->getFreesize();
        $lv_size = $this->getSize();
        $vg_new_freesize = $vg_freesize + $lv_size;
        $vg->setFreesize($vg_new_freesize);
        $vg->save();
        return true;
        
    }
    
}
