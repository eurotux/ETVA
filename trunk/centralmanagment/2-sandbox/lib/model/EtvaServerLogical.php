<?php

require 'lib/model/om/BaseEtvaServerLogical.php';


/**
 * Skeleton subclass for representing a row from the 'server_logical' table.
 *
 * 
 *
 * This class was autogenerated by Propel 1.4.1 on:
 *
 * Fri May 28 16:33:12 2010
 *
 * You should add additional methods to this class to meet the
 * application requirements.  This class will only be generated as
 * long as it does not already exist in the output directory.
 *
 * @package    lib.model
 */
class EtvaServerLogical extends BaseEtvaServerLogical {
    
    const TARGET_MAP = 'target';
    const DISKTYPE_MAP = 'bus';

    const _DISK_MAP_VA_   = 'path=%path%,bus=%bus%';

    /*
     * update some object data from VA response
     */
    public function initData($arr)
	{
        
        $lv = $this->getEtvaLogicalvolume();
        $lv->setInUse(1);        
        
        if(array_key_exists(self::TARGET_MAP, $arr)) $lv->initData(array(self::TARGET_MAP=>$arr[self::TARGET_MAP]));
        if(array_key_exists(self::DISKTYPE_MAP, $arr)) $this->setDiskType($arr[self::DISKTYPE_MAP]);                
	}


    

    /*
     * return string network representation for VA
     */
    public function serverdisk_VA()
    {
        $lv = $this->getEtvaLogicalvolume();

        $disk_map_va = self::_DISK_MAP_VA_;
        $arr = array('%path%' => $lv->getLvdevice(),'%bus%' => $this->getDiskType());
        if( $lv->getFormat() && ($lv->getFormat() != 'lvm') && ($lv->getFormat() != 'raw') ){
            $arr['%format%'] = $lv->getFormat();
            $disk_map_va .= ',format=%format%';
        }
        if( $lv->getStorageType()!=EtvaLogicalvolume::STORAGE_TYPE_LOCAL_MAP ){
            /*
             * set driver cache = none for not local storage
             *  improve this doing configuration on interface
             */
            $arr['%drivercache%'] = 'none';
            $disk_map_va .= ',drivercache=%drivercache%';
        }

        return strtr($disk_map_va, $arr);

    }

    /*
     * removes rrd file also
     */
    public function preDelete(PropelPDO $con = null)
    {
        $server = $this->getEtvaServer();
        $node = $server->getEtvaNode();
        $logicalvol = $this->getEtvaLogicalvolume();
        $lv = $logicalvol->getLv();
        //remove disk RRA files        
        $server_disk_rw = new ServerDisk_rwRRA($node->getUuid(),$server->getUuid(),$lv,false);
        $server_disk_rw->delete();        

        return true;
    }

} // EtvaServerLogical
