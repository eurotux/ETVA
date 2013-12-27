<?php

class EtvaPhysicalvolumePeer extends BaseEtvaPhysicalvolumePeer
{
  const _ERR_NOTFOUND_   = 'Physical volume %name% could not be found';

  const _ERR_SOAPUPDATE_   = 'Physical volumes info could not be updated. %info%';
  const _OK_SOAPUPDATE_   = 'Physical volumes %info% updated';

  const _ERR_INIT_   = 'Device %name% could not be initialized. %info%';
  const _OK_INIT_   = 'Physical volume %name% initialized successfully';

  const _ERR_UNINIT_   = 'Device %name% could not be uninitialized. %info%';
  const _OK_UNINIT_   = 'Device %name% uninitialized successfully';

  const _NOT_AVAILABLE_ = 'Physical volume %name% not available';

  const _ERR_SOAPREFRESH_ = 'Physical volumes info could not be reloaded. %info%';
  const _OK_SOAPREFRESH_ = 'Physical volumes info reloaded successfully';

  const _MISMATCH_VG_ = 'Physical volume %name% does not belong to volume group %vg%';

  const _MISMATCH_STORAGE_ = 'Physical volume(s) storage type mismatch';

  const _VGASSOC_ = 'Physical volume %name% associated to volume group %vg%';

  const _PVINIT_ = 'Physical volume %name% initialized';
  const _PVUNINIT_ = 'Physical volume %name% not initialized';

  const _NONE_AVAILABLE_ = 'No physical volumes available';

  const _ERR_INCONSISTENT_ = 'Information of the physical volumes is reported as inconsistent. %info%';
  const _ERR_SHARED_INCONSISTENT_ = 'Information of the shared physical volumes is reported as inconsistent. %info%';

  const _ERR_INIT_OTHER_CLUSTER_ = 'Node has some physical volumes that exists on cluster %name%.';  
  
  const _ERR_REGISTER_   = 'Physical device %name% could not be registered. %info%';
  const _OK_REGISTER_   = 'Physical device %name% registered successfully';

  const _ERR_UNREGISTER_   = 'Physical device %name% could not be unregistered. %info%';
  const _OK_UNREGISTER_   = 'Physical device %name% unregistered successfully';

  const _ERR_EXPAND_   = 'Device %name% could not be expanded. %info%';
  const _OK_EXPAND_   = 'Device %name% expanded successfully';

  public static function retrieveByPv($pv, Criteria $criteria = null)
  {
    if(is_null($criteria )) $criteria = new Criteria();

    $criteria->add(self::PV, $pv);

    return self::doSelectOne($criteria);
  }

  public static function retrieveByDevice($dev, Criteria $criteria = null)
  {
    if(is_null($criteria )) $criteria = new Criteria();

    $criteria->add(self::DEVICE, $dev);

    return self::doSelectOne($criteria);
  }

  public static function retrieveByUUID($uuid, Criteria $criteria = null)
  {
    if(is_null($criteria )) $criteria = new Criteria();

    $criteria->add(self::UUID, $uuid);

    return self::doSelectOne($criteria);
  }


  public static function retrieveByNodeTypeUUIDDevice($node_id, $type, $uuid, $device)
  {

    $criteria = new Criteria();

    /*
     * check if pv already exists on DB
     * if not, insert it, otherwise update
     */
    switch($type){
        case EtvaPhysicalvolume::STORAGE_TYPE_LOCAL_MAP:
            /*
             * need to check if node has already that device in DB
             * if storage type is local we need to check if has uuid(local disk) or not( in case of partition)
             */
            if($uuid) $criteria->add(self::UUID,$uuid);
            else $criteria->add(self::DEVICE,$device);

            $criteria->add(EtvaNodePhysicalvolumePeer::NODE_ID,$node_id);
            $criteria->addJoin(self::ID,EtvaNodePhysicalvolumePeer::PHYSICALVOLUME_ID);
            $criteria->add(self::STORAGE_TYPE,$type);

            break;

        default:
            /*
             * if shared storage check only if device is already in DB
             */
            $criteria->add(self::STORAGE_TYPE,$type);
            if($uuid) $criteria->add(self::UUID,$uuid);
            else $criteria->add(self::DEVICE,$device);

            break;
    }

    $etva_physicalvol = self::doSelectOne($criteria);
    return $etva_physicalvol;

    
  }
  public static function retrieveByClusterTypeUUIDDevice($cluster_id, $type, $uuid=null, $device) {

    $criteria = new Criteria();

    /*
     * check if pv already exists on DB
     */
    if($uuid) $criteria->add(self::UUID,$uuid);
    else $criteria->add(self::DEVICE,$device);

    $criteria->add(self::CLUSTER_ID,$cluster_id);
    $criteria->add(self::STORAGE_TYPE,$type);

    $etva_physicalvol = self::doSelectOne($criteria);
    return $etva_physicalvol;
  }

  public static function retrieveByNodeTypeDevice($node_id, $type, $device)
  {

    $criteria = new Criteria();
    $criteria->add(self::DEVICE,$device);
    $criteria->add(EtvaNodePhysicalvolumePeer::NODE_ID,$node_id);
    $criteria->addJoin(self::ID,EtvaNodePhysicalvolumePeer::PHYSICALVOLUME_ID);
    $criteria->add(self::STORAGE_TYPE,$type);

    $etva_physicalvol = self::doSelectOne($criteria);
    return $etva_physicalvol;

  }

}
