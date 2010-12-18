<?php

class EtvaNodePeer extends BaseEtvaNodePeer
{
     static function getWithServers()
    {
        $criteria = new Criteria();
    //    $criteria->addJoin(self::ID, EtvaServerPeer::NODE_ID);
     //   $criteria->addJoin(EtvaServerPeer::SF_GUARD_GROUP_ID, $this->getUser()->getGroupsId(), Criteria::IN);
     //   $criteria->setDistinct();
        return self::doSelect($criteria);

    }


    public static function retrieveByIp($host)
  {
    $c = new Criteria();
    $c->add(self::IP, $host);

      return self::doSelectOne($c);
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
     $c->add(self::UID, $uuid);

     $result = self::doSelectOne($c);
     if($result) return false;
     else return true;
      
  }

}
