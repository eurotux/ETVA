<?php

class EtvaServerPeer extends BaseEtvaServerPeer
{
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
     $c->add(self::UID, $uuid);

     $result = self::doSelectOne($c);
     if($result) return false;
     else return true;

  }

}
