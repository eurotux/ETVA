<?php

class EtvaPhysicalvolumePeer extends BaseEtvaPhysicalvolumePeer
{
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
}
