<?php

class EtvaVlanPeer extends BaseEtvaVlanPeer
{  

  public static function retrieveByName($vlan)
  {
    $c = new Criteria();
    $c->add(self::NAME, $vlan);

      return self::doSelectOne($c);
  }

}
