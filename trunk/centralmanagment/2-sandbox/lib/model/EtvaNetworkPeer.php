<?php

class EtvaNetworkPeer extends BaseEtvaNetworkPeer
{
  const _ERR_   = 'Network interfaces problem.';
  const _ERR_SAVEALL_WITHMAC_   = 'Could not save all network interfaces. Interface with mac address %name% problem.';

  const _ERR_REMOVEALL_   = 'Network interfaces could not be removed. %info%';
  const _OK_REMOVEALL_   = 'Network interfaces of server %server% removed successfully';

  const _ERR_REMOVE_   = 'Network interface %name% could not be removed. %info%';
  const _OK_REMOVE_   = 'Network interface %name% of server %server% removed successfully';

  const _ERR_CREATEALL_   = 'Network interfaces could not be created. %info%';
  const _OK_CREATEALL_   = 'Network interfaces of server %server% created successfully';

  public static function retrieveByMac($mac, Criteria $criteria = null)
  {
    if(is_null($criteria )) $criteria = new Criteria();

    $criteria->add(self::MAC, $mac);

    return self::doSelectOne($criteria);
  }
    
}
