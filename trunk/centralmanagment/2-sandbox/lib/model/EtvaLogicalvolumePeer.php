<?php

class EtvaLogicalvolumePeer extends BaseEtvaLogicalvolumePeer
{
  const _ERR_SYSTEM_LV_   = 'Logical volume %name% cannot be used. SYSTEM LV';

  const _ERR_NOTFOUND_ID_   = 'Logical volume with ID %id% could not be found';
  const _ERR_NOTFOUND_   = 'Logical volume %name% could not be found';
  
  const _ERR_DISK_EXIST_   = 'Disk file %name% already exist';
  const _ERR_LV_EXIST_   = 'Logical volume %name% already exist';

  const _ERR_SOAPUPDATE_   = 'Logical volumes info could not be updated. %info%';
  const _OK_SOAPUPDATE_   = 'Logical volumes %info% updated';

  const _ERR_CREATE_   = 'Logical volume %name% could not be created. %info%';
  const _OK_CREATE_   = 'Logical volume %name% created successfully';

  const _ERR_REMOVE_   = 'Logical volume %name% could not be removed. %info%';
  const _OK_REMOVE_   = 'Logical volume %name% removed successfully';

  const _ERR_RESIZE_   = 'Logical volume %name% could not be resized to %size%MB. %info%';
  const _OK_RESIZE_   = 'Logical volume %name% resized to %size%MB successfully';

  const _OK_KEEP_   = 'Logical volume %name% NOT REMOVED';
  const _OK_NOTKEEP_   = 'Logical volume %name% REMOVED';

  const _OK_SOAPREFRESH_ = 'Logical volumes info reloaded successfully';
  const _ERR_SOAPREFRESH_ = 'Logical volumes info could not be reloaded. %info%';

  const _ERR_INUSE_   = 'Logical volume %name% in use by  virtual server %server%';
  const _NOTINUSE_   = 'Logical volume %name% not in use';

  const _NOTAVAILABLE_ = 'No logical volumes available';

  const _NOTALLSHARED_ = 'All logical volumes needs to be shared.';

  const _HASSNAPSHOTS_ = 'The logical volumes has snapshots.';

  const _ERR_INVALIDSIZE_ = 'Invalid logical volume size';

  const _ERR_INCONSISTENT_ = 'Shared logical volumes info reported inconsistent. %info%';

  const _SNAPSHOT_LV_   = 'Volume %name% is a snapshot.';
    
  const _ERR_CREATESNAPSHOT_   = 'Snapshot %name% could not be created. %info%';
  const _OK_CREATESNAPSHOT_   = 'Snapshot %name% created successfully';

  const _SNAPSHOT_INOTHERNODE_   = 'Volume %name% is a snapshot in other node.';
  const _SNAPSHOT_CLUSTER_CONTEXT_ = 'Volume %name% is a snapshot and cannot be changed in cluster context. Please select the corresponding the node.';
  const _LV_HAVESNAPSHOTS_INOTHERNODE_   = 'Volume %name% have snapshots in other node.';
  const _LV_HAVESNAPSHOTS_INNODE_CONTEXT_   = 'Volume %name% have snapshots in node context.';

  const _ERR_INIT_OTHER_CLUSTER_ = 'Node has some logical volumes that exists on cluster %name%.';

  const _OK_CREATECLONE_    = 'Clone %name% was successfully created.';
  const _ERR_CREATECLONE_   = 'Could not create clone %name%.';

  public static function retrieveByLvDevice($name, Criteria $criteria = null)
  {
    if(is_null($criteria )) $criteria = new Criteria();
    
    $criteria->add(self::LVDEVICE, $name);

      return self::doSelectOne($criteria);
  }


  public static function retrieveByLv($lv, Criteria $criteria = null)
  {
    if(is_null($criteria )) $criteria = new Criteria();

    $criteria->add(self::LV, $lv);

    return self::doSelectOne($criteria);
  }

  public static function retrieveByUUID($uuid, Criteria $criteria = null)
  {
    if(is_null($criteria )) $criteria = new Criteria();

    $criteria->add(self::UUID, $uuid);

    return self::doSelectOne($criteria);
  }

  /**
   * Retrieve logical volume by node, storage type, uuid and lv
   * This method checks if storage type not local. if not local, uuid is used otherwise use lv name to get info if uuid is empty
   *
   *
   */
  public static function retrieveByNodeTypeUUIDLv($node_id, $type, $uuid, $lv)
  {

    $criteria = new Criteria();

    /*
     * check if lv already exists on DB
     * if not, insert it, otherwise update
     */
    switch($type){
        case EtvaLogicalvolume::STORAGE_TYPE_LOCAL_MAP:
            /*
             * need to check if node has already that lv in DB
             * if storage type is local we need to check if has uuid or not( DISK)
             */
            if($uuid) $criteria->add(self::UUID,$uuid);
            else $criteria->add(self::LV,$lv);

            $criteria->add(EtvaNodeLogicalvolumePeer::NODE_ID,$node_id);
            $criteria->addJoin(self::ID,EtvaNodeLogicalvolumePeer::LOGICALVOLUME_ID);
            $criteria->add(self::STORAGE_TYPE,$type);

            break;

        default:
            /*
             * if shared storage check only if lv is already in DB
             */
            $criteria->add(self::STORAGE_TYPE,$type);
            $criteria->add(self::UUID,$uuid);

            break;
    }

    $etva_logicalvolume = self::doSelectOne($criteria);
    return $etva_logicalvolume;


  }



  public static function retrieveByNodeTypeLvDevice($node_id, $type, $lvdev)
  {

    $criteria = new Criteria();
    $criteria->add(self::LVDEVICE,$lvdev);
    $criteria->add(EtvaNodeLogicalvolumePeer::NODE_ID,$node_id);
    $criteria->addJoin(self::ID,EtvaNodeLogicalvolumePeer::LOGICALVOLUME_ID);
    $criteria->add(self::STORAGE_TYPE,$type);

    $etva_logicalvolume = self::doSelectOne($criteria);
    return $etva_logicalvolume;

  }

    
}
