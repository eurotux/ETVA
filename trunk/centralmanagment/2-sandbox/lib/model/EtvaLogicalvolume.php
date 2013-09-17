<?php

class EtvaLogicalvolume extends BaseEtvaLogicalvolume
{
    
    private $volumegroup;
    const UUID_MAP = 'uuid';
    const VOLUMEGROUP_MAP = 'volumegroup';
    const SIZE_MAP = 'size';
    const FREESIZE_MAP = 'freesize';
    const VIRTUALSIZE_MAP = 'virtual_size';
    const SIZESNAPSHOTS_MAP = 'size_of_snapshots';
    const LV_MAP = 'lv_name';
    const LVDEVICE_MAP = 'lvdevice';    
    const WRITEABLE_MAP = 'writeable';
    const TARGET_MAP = 'target';
    const PATH_MAP = 'path';
    const MOUNTED_MAP = 'mounted';
    const SNAPSHOT_MAP = 'snapshot';
    const ORIGIN_MAP = 'origin';
    const FORMAT_MAP = 'format';
    const HAVESNAPSHOTS_MAP = 'havesnapshots';

    const STORAGE_TYPE_MAP = 'type';
    const STORAGE_TYPE_LOCAL_MAP = 'local';

    const PER_USAGESNAPSHOTS_CRITICAL = 0.90;
    const PER_USAGESNAPSHOTS_WARNING = 0.80;
    
    /*
     * used to process soap comm data
     */
    public function initData($arr)
	{
        if(array_key_exists(self::UUID_MAP, $arr)) $this->setUuid($arr[self::UUID_MAP]);
        if(array_key_exists(self::SIZE_MAP, $arr)) $this->setSize($arr[self::SIZE_MAP]);
        if(array_key_exists(self::FREESIZE_MAP, $arr)) $this->setFreesize($arr[self::FREESIZE_MAP]);
        if(array_key_exists(self::VIRTUALSIZE_MAP, $arr)) $this->setVirtualSize($arr[self::VIRTUALSIZE_MAP]);
        if(array_key_exists(self::SIZESNAPSHOTS_MAP, $arr)) $this->setSizeSnapshots($arr[self::SIZESNAPSHOTS_MAP]);

        if(array_key_exists(self::LV_MAP, $arr)) $this->setLv($arr[self::LV_MAP]);
        if(array_key_exists(self::LVDEVICE_MAP, $arr)) $this->setLvdevice($arr[self::LVDEVICE_MAP]);
        if(array_key_exists(self::WRITEABLE_MAP, $arr)) $this->setWriteable($arr[self::WRITEABLE_MAP]);
        if(array_key_exists(self::STORAGE_TYPE_MAP, $arr)) $this->setStorageType($arr[self::STORAGE_TYPE_MAP]);
        if(array_key_exists(self::TARGET_MAP, $arr)) $this->setTarget($arr[self::TARGET_MAP]);

        if(array_key_exists(self::MOUNTED_MAP, $arr)) $this->setMounted($arr[self::MOUNTED_MAP]);
        if(array_key_exists(self::SNAPSHOT_MAP, $arr)) $this->setSnapshot($arr[self::SNAPSHOT_MAP]);
        if(array_key_exists(self::ORIGIN_MAP, $arr)) $this->setOrigin($arr[self::ORIGIN_MAP]);
        if(array_key_exists(self::FORMAT_MAP, $arr)) $this->setFormat($arr[self::FORMAT_MAP]);
        //if(array_key_exists(self::HAVESNAPSHOTS_MAP, $arr)) $this->setHaveSnapshots($arr[self::HAVESNAPSHOTS_MAP]);

        //error_log("EtvaLogicalVolume initData=".print_r($arr,true));
        if( array_key_exists('has_snapshots',$arr) && $arr['has_snapshots'] ){
            $size = $arr['size'];
            $virtual_size = $arr['virtual_size'];
            $size_of_snapshots = $arr['size_of_snapshots'];

            error_log("EtvaLogicalVolume initData device=".$this->getLvdevice()." has snapshots size=$size virtual_size=$virtual_size size_of_snapshots=$size_of_snapshots");
        }

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
     * returns the all servers associated with this lv
     */
    public function getEtvaServers(PropelPDO $con = null)
	{

      $criteria = new Criteria();
      $criteria->add(EtvaServerLogicalPeer::LOGICALVOLUME_ID, $this->getId());
      $criteria->addJoin(EtvaServerPeer::ID, EtvaServerLogicalPeer::SERVER_ID);
      return EtvaServerPeer::doSelect($criteria, $con);
	}

    public function getEtvaLogicalvolumesSnapshots(Criteria $criteria = null)
    {
        if(!$criteria) $criteria = new Criteria();
        $criteria->add(EtvaLogicalvolumePeer::VOLUMEGROUP_ID, $this->getVolumegroupId());
        $criteria->add(EtvaLogicalvolumePeer::ORIGIN, $this->getLv());
        $criteria->add(EtvaLogicalvolumePeer::SNAPSHOT, 1);
        return EtvaLogicalvolumePeer::doSelect($criteria);
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
    
    public function getPerUsageSnapshots()
	{
        $diff_size_for_snapshots = $this->getSize() - $this->getVirtualSize();
        $per_usage_snapshots = $diff_size_for_snapshots > 0 ? ($this->getSizeSnapshots() / $diff_size_for_snapshots) : 0;
        return $per_usage_snapshots;
    }
}
