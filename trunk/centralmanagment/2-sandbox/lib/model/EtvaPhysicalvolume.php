<?php

class EtvaPhysicalvolume extends BaseEtvaPhysicalvolume
{

    const UUID_MAP = 'uuid';
    const NAME_MAP = 'device';
    const DEVICE_MAP = 'device';
    const DEVSIZE_MAP = 'size';
    const PV_MAP = 'pv';
    const VG_MAP = 'vg';
    const PVSIZE_MAP = 'pvsize';
    const PVFREESIZE_MAP = 'pvfreesize';
    const PVINIT_MAP = 'pvinit';    
    const ALLOCATABLE_MAP = 'allocatable';

    const STORAGE_TYPE_MAP = 'type';
    const STORAGE_TYPE_LOCAL_MAP = 'local';

    /*
     * used to process soap comm data
     */
    public function initData($arr)
	{
        if(array_key_exists(self::UUID_MAP, $arr)) $this->setUuid($arr[self::UUID_MAP]);

        if(array_key_exists(self::NAME_MAP, $arr)) $this->setName($arr[self::NAME_MAP]);                
        
        if(array_key_exists(self::DEVICE_MAP, $arr)) $this->setDevice($arr[self::DEVICE_MAP]);
        else $this->setDevice('');

        if(array_key_exists(self::DEVSIZE_MAP, $arr)) $this->setDevsize($arr[self::DEVSIZE_MAP]);
        else $this->setDevsize('');

        if(array_key_exists(self::PV_MAP, $arr)) $this->setPv($arr[self::PV_MAP]);
        else $this->setPv('');

        if(array_key_exists(self::PVSIZE_MAP, $arr)) $this->setPvsize($arr[self::PVSIZE_MAP]);
        else $this->setPvsize('');

        if(array_key_exists(self::PVFREESIZE_MAP, $arr)) $this->setPvfreesize($arr[self::PVFREESIZE_MAP]);
        else $this->setPvfreesize('');

        if(array_key_exists(self::PVINIT_MAP, $arr)) $this->setPvinit($arr[self::PVINIT_MAP]);
        else $this->setPvinit(0);

        if(array_key_exists(self::STORAGE_TYPE_MAP, $arr)) $this->setStorageType($arr[self::STORAGE_TYPE_MAP]);

        if(array_key_exists(self::ALLOCATABLE_MAP, $arr)) $this->setAllocatable($arr[self::ALLOCATABLE_MAP]);
        else $this->setAllocatable('');

        // set allocatable flag
        //if has vg associated it cannot be allocatable to another vg
        if(array_key_exists(self::VG_MAP, $arr))
        {
            if(empty($arr[self::VG_MAP])) $this->setAllocatable(1);
            else $this->setAllocatable(0);
        }


	}

    public function _VA()
    {
        $pv_VA = array(self::UUID_MAP => $this->getUuid(),
                       self::DEVICE_MAP => $this->getDevice(),
                       self::DEVSIZE_MAP => $this->getDevsize(),
                       self::PV_MAP => $this->getPv(),
                       self::PVSIZE_MAP => $this->getPvsize(),
                       self::PVFREESIZE_MAP => $this->getPvfreesize(),
                       self::PVINIT_MAP => $this->getPvinit(),
                       self::STORAGE_TYPE_MAP => $this->getStorageType());

        return $pv_VA;
    }

    
}
