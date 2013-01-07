<?php

class EtvaVolumegroupPeer extends BaseEtvaVolumegroupPeer
{
  const _ERR_NOTFOUND_   = 'Volume group %name% could not be found';

  const _ERR_VG_EXIST_   = 'Volume group %name% already exist';

  const _ERR_CREATE_EXTEND_ = 'Volume group %name% could not be created/extended. %info%';

  const _OK_CREATE_   = 'Volume group %name%  created successfully with %pvs%';
  const _ERR_CREATE_   = 'Volume group %name% could not be created. %info%';

  const _OK_EXTEND_   = 'Volume group %name% extended successfully with %pvs%';
  const _ERR_EXTEND_   = 'Volume group %name% could not be extended. %info%';

  const _OK_SOAPREFRESH_ = 'Volumes groups info reloaded successfully';
  const _ERR_SOAPREFRESH_ = 'Volumes groups info could not be reloaded. %info%';

  const _ERR_SOAPUPDATE_   = 'Volumes groups info could not be updated. %info%';
  const _OK_SOAPUPDATE_   = 'Volumes groups %info% updated';

  const _ERR_REMOVE_   = 'Volume group %name% could not be removed. %info%';
  const _OK_REMOVE_   = 'Volume group %name% removed successfully';

  const _ERR_REDUCE_   = 'Volume group %name% could not be reduced. %info%';
  const _OK_REDUCE_   = 'Volume group %name% reduced successfully';

  const _LVASSOC_ = 'Logical volumes associated with volume group %name%';

  const _NOTAVAILABLE_ = 'No volume groups available';

  const _SPECIAL_NODEL_ = 'Special volume group %name% cannot be removed';

  const _ERR_INCONSISTENT_ = 'Shared volume groups info reported inconsistent. %info%';

  const _ERR_INIT_OTHER_CLUSTER_ = 'Node has some volume group that exists on cluster %name%.';

  const _ERR_REGISTER_   = 'Volume group %name% could not be registered. %info%';
  const _OK_REGISTER_   = 'Volume group %name% registered successfully';

  const _ERR_UNREGISTER_   = 'Volume group %name% could not be unregistered. %info%';
  const _OK_UNREGISTER_   = 'Volume group %name% unregistered successfully';


  public static function retrieveByVg($name, Criteria $criteria = null)
  {
    if(is_null($criteria )) $criteria = new Criteria();

    $criteria->add(self::VG, $name);

      return self::doSelectOne($criteria);
  }

  public static function retrieveByUUID($uuid, Criteria $criteria = null)
  {
    if(is_null($criteria )) $criteria = new Criteria();

    $criteria->add(self::UUID, $uuid);

    return self::doSelectOne($criteria);
  }


  /*
   * return total free size
   */
  public static function retrieveTotalFreesize(Criteria $criteria = null)
  {
    if(is_null($criteria )) $criteria = new Criteria();
    
    $criteria->addAsColumn('total', "SUM(". self::FREESIZE . ")");

    $stmt = self::doSelectStmt($criteria);
    $row = $stmt->fetch();
    
    return $row['total'];    
  }

  /*
   * return total size
   */
  public static function retrieveTotalsize(Criteria $criteria = null)
  {
    if(is_null($criteria )) $criteria = new Criteria();

    $criteria->addAsColumn('total', "SUM(". self::SIZE . ")");

    $stmt = self::doSelectStmt($criteria);
    $row = $stmt->fetch();

    return $row['total'];
  }


  public static function retrieveByNodeTypeUUIDVg($node_id, $type, $uuid, $vg)
  {

    $criteria = new Criteria();

    /*
     * check if vg already exists on DB
     * if not, insert it, otherwise update
     */
    switch($type){
        case 'local':
            /*
             * need to check if node has already that vg in DB
             * if storage type is local we need to check if has uuid or not( DISK)
             */
            if($uuid) $criteria->add(self::UUID,$uuid);
            else $criteria->add(self::VG,$vg);

            $criteria->add(EtvaNodeVolumegroupPeer::NODE_ID,$node_id);
            $criteria->addJoin(self::ID,EtvaNodeVolumegroupPeer::VOLUMEGROUP_ID);
            $criteria->add(self::STORAGE_TYPE,$type);

            break;

        default:
            /*
             * if shared storage check only if vg is already in DB
             */
            $criteria->add(self::STORAGE_TYPE,$type);
            $criteria->add(self::UUID,$uuid);

            break;
    }

    $etva_volumegroup = self::doSelectOne($criteria);
    return $etva_volumegroup;

  }

  public static function retrieveByNodeTypeVg($node_id, $type, $vg)
  {

    $criteria = new Criteria();
    $criteria->add(self::VG,$vg);
    $criteria->add(EtvaNodeVolumegroupPeer::NODE_ID,$node_id);
    $criteria->addJoin(self::ID,EtvaNodeVolumegroupPeer::VOLUMEGROUP_ID);    
    $criteria->add(self::STORAGE_TYPE,$type);

    $etva_volumegroup = self::doSelectOne($criteria);
    return $etva_volumegroup;

  }

}
