<?php

class EtvaVlanPeer extends BaseEtvaVlanPeer
{
  
  const _ERR_CREATE_   = 'VLAN %name% could not be created. %info%';
  const _OK_CREATE_   = 'VLAN %name% created successfully';

  const _ERR_REMOVE_   = 'VLAN %name% could not be removed. %info%';
  const _OK_REMOVE_   = 'VLAN %name% removed successfully';
  
  const _ERR_SOAPUPDATE_   = 'Could not update VLANS. %info%';
  const _OK_SOAPUPDATE_   = 'Vlans %info% sent to %name%';

  const _ERR_NAME_   = 'No spaces and no reserved words \'vlan\' or \'eth\' or \'bond\' or \'dev\'. Only alpha-numeric characters allowed!';
  const _REGEXP_INVALID_NAME_ = '/^(((vlan|eth|bond)[a-zA-Z0-9_]*)|(dev[0-9_]*))$/';

  public static function retrieveByClusterAndName($vlan_name, $cluster_id){
      $c = new Criteria();
      $c->add(self::NAME, $vlan_name);
      $c->addAnd(self::CLUSTER_ID, $cluster_id);
      return self::doSelectOne($c);
  }
  
  public static function retrieveByName($vlan, Criteria $c = null)
  {
    if(is_null($c)) $c = new Criteria();
    $c->add(self::NAME, $vlan);

    return self::doSelectOne($c);
  }


  public static function retrieveUntagged($cluster_id)
  {
    $c = new Criteria();
    $c->add(self::CLUSTER_ID, $cluster_id, Criteria::EQUAL);
    $c->add(self::TAGGED, 0);
    
    return self::doSelectOne($c);
  }

  /*
   * checks if vlan name and id are unique
   * @return record if exists
   */
  public static function isUnique($id,$name, $cluster_id = 1)
  {
    $c = new Criteria();
    $c->add(self::CLUSTER_ID, $cluster_id);
    $crit1 = $c->getNewCriterion(self::NAME, $name);
    $crit2 = $c->getNewCriterion(self::VLANID, $id);
    $crit1->addOr($crit2);

    $c->add($crit1);
    
    return self::doSelectOne($c);
  }

}
