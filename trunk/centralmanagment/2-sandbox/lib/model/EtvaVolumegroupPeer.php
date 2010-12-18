<?php

class EtvaVolumegroupPeer extends BaseEtvaVolumegroupPeer
{
    public static function retrieveByVg($name, Criteria $criteria = null)
  {
    if(is_null($criteria )) $criteria = new Criteria();

    $criteria->add(self::VG, $name);

      return self::doSelectOne($criteria);
  }
}
