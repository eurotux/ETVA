<?php

class EtvaVlanPeer extends BaseEtvaVlanPeer
{
  
  const _ERR_CREATE_   = 'VLAN %name% could not be created. %info%';
  const _OK_CREATE_   = 'VLAN %name% created successfully';

  const _ERR_REMOVE_   = 'VLAN %name% could not be removed. %info%';
  const _OK_REMOVE_   = 'VLAN %name% removed successfully';
  
  const _ERR_SOAPUPDATE_   = 'Could not update VLANS. %info%';
  const _OK_SOAPUPDATE_   = 'Vlans %info% sent to %name%';

  const _ERR_NAME_   = 'No spaces and no reserved words \'vlan\' or \'eth\' or \'bond\'. Only alpha-numeric characters allowed!';
  
  
  public static function retrieveByName($vlan)
  {
    $c = new Criteria();
    $c->add(self::NAME, $vlan);

    return self::doSelectOne($c);
  }


  public static function retrieveUntagged()
  {
    $c = new Criteria();
    $c->add(self::TAGGED, 0);
    
    return self::doSelectOne($c);
  }

  /*
   * checks if vlan name and id are unique
   * @return record if exists
   */
  public static function isUnique($id,$name)
  {
    $c = new Criteria();
    $crit1 = $c->getNewCriterion(self::NAME, $name);
    $crit2 = $c->getNewCriterion(self::VLANID, $id);
    $crit1->addOr($crit2);
    $c->add($crit1);
    
    return self::doSelectOne($c);
  }

}
