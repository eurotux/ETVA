<?php
class EtvaServerPeer extends BaseEtvaServerPeer
{    
    const _ERR_VNCKEYMAP_CHANGE_   = 'Server %server% VNC keymap could not be changed to %name%. %info%';
    const _OK_VNCKEYMAP_CHANGE_   = 'Server %server% VNC keymap changed to %name%';

    const _ERR_NOTFOUND_ID_   = 'Server with ID %id% could not be found';
    const _ERR_NOTFOUND_   = 'Server %name% could not be found';
    
    const _ERR_CREATE_   = 'Server with name %name% could not be created. %info%';    
    const _OK_CREATE_   = 'Server with name %name% created successfully';

    const _ERR_EXIST_   = 'Server with name %name% already exists';

    const _ERR_EDIT_   = 'Server with name %name% could not be edited. %info%';
    const _OK_EDIT_   = 'Server with name %name% edited successfully';

    const _ERR_REMOVE_ID_   = 'Server could not be removed. %info%';
    const _ERR_REMOVE_   = 'Server with name %name% could not be removed. %info%';
    const _OK_REMOVE_   = 'Server with name %name% removed successfully. %info%';

    const _ERR_START_   = 'Server with name %name% could not be started. %info%';
    const _OK_START_   = 'Server with name %name% started successfully';

    const _ERR_STOP_   = 'Server with name %name% could not be stopped. %info%';
    const _OK_STOP_   = 'Server with name %name% stopped successfully';

    const _ERR_MIGRATE_UNKNOWN_   = 'Could not migrate. %info%';
    const _ERR_MIGRATE_   = 'Could not migrate %name%. %info%';
    const _ERR_MIGRATE_FROMTO_COND_ = 'Could not migrate %name% between different clusters and/or same node';

    const _ERR_MIGRATE_FROMTO_ = 'Could not migrate %name% from %from% to %to%. %info%';
    const _OK_MIGRATE_FROMTO_ = 'Server %name% migrated successfully from %from% to %to%. %info%';

    const _ERR_MOVE_UNKNOWN_   = 'Could not move. %info%';
    const _ERR_MOVE_   = 'Could not move %name%. %info%';

    const _ERR_MOVE_FROMTO_COND_ = 'Could not move %name% between different clusters and/or same node';
    const _ERR_MOVE_FROMTO_ = 'Could not move %name% from %from% to %to%. %info%';

    const _OK_MOVE_FROMTO_ = 'Server %name% moved successfully from %from% to %to%. %info%';

    const _ERR_SOAPUPDATE_   = 'Could not update Servers. %info%';
    const _OK_SOAPUPDATE_   = 'Servers %info% sent to %name%';

    const _ERR_ADD_NETWORKS_   = 'Server name %name% networks info problem. %info%';

    const _ERR_SOAPSTATE_   = 'Server %name% state could not be checked. %info%';
    const _OK_SOAPSTATE_   = 'Server %name% state has been checked';

    const _ERR_RELOAD_   = 'Could not reload server %name% from %node%. %info%';
    const _OK_RELOAD_   = 'Server %name% from %node% reload successfully';

    const _ERR_UNASSIGNED_ = 'Could not unassign server %name% from %node%. %info%';
    const _OK_UNASSIGNED_ = 'Server %name% unassigned from %node% successfully';

    const _ERR_ASSIGNED_ = 'Could not assign server %name% to %node%. %info%';
    const _OK_ASSIGNED_ = 'Server %name% assigned to %node% successfully';

    const _MSG_   = 'Server %name% : %info%';

    const _CDROM_INUSE_ = 'Server %name% has ISO mounted in CD-ROM';

    static public function getServers(Criteria $criteria = null)
    {
        if (is_null($criteria))
        {
            $criteria = new Criteria();
        }

        return self::doSelect($criteria);
    }

  public static function retrieveByName($server, Criteria $criteria = null)
  {
    if(is_null($criteria )) $criteria = new Criteria();

    $criteria->add(self::NAME, $server);

    return self::doSelectOne($criteria);
  }


  public static function generateUUID(){

      $uuid = UUID::generate(UUID::UUID_RANDOM, UUID::FMT_STRING);
      while(!self::uniqueUUID($uuid)){
          $uuid = UUID::generate(UUID::UUID_RANDOM, UUID::FMT_STRING);
      }
      return $uuid;
  }

  public static function uniqueUUID($uuid)
  {
     $c = new Criteria();
     $c->add(self::UUID, $uuid);

     $result = self::doSelectOne($c);
     if($result) return false;
     else return true;

  }

  /*
   * return total servers memory
   */
  public static function getTotalMem(Criteria $c)
  {
    
    if(!$c) $c = new Criteria();
    $c->addSelectColumn("SUM(".self::MEM.") AS total_mem");
    $stmt = self::doSelectStmt($c);
    if($stmt) $row = $stmt->fetch();
    
    if($row) return $row['total_mem'];
    else return 0;
  }

}
