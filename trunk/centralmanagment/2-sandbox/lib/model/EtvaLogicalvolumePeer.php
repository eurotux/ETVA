<?php

class EtvaLogicalvolumePeer extends BaseEtvaLogicalvolumePeer
{
    
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
    
}
