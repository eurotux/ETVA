<?php

class EtvaNetworkPeer extends BaseEtvaNetworkPeer
{
  public static function retrieveByMac($mac, Criteria $criteria = null)
  {
    if(is_null($criteria )) $criteria = new Criteria();

    $criteria->add(self::MAC, $mac);

    return self::doSelectOne($criteria);
  }
    
}
