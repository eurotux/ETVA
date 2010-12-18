<?php

class EtvaVolumePhysicalPeer extends BaseEtvaVolumePhysicalPeer
{
  public static function retrieveByVGPV($vg,$pv)
  {
    $criteria = new Criteria();

    $criteria->add(self::PHYSICALVOLUME_ID, $pv);
    $criteria->add(self::VOLUMEGROUP_ID, $vg);

      return self::doSelectOne($criteria);
  }
  
  public static function retrieveByEtvaPhysicalvolumeId($pvid)
  {
    $criteria = new Criteria();

    $criteria->add(self::PHYSICALVOLUME_ID, $pvid);    

    return self::doSelectOne($criteria);
  }


}
