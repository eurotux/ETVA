<?php

/**
 * EtvaVlan form.
 *
 * @package    centralM
 * @subpackage form
 * @author     Your name here
 */
class EtvaVlanForm extends BaseEtvaVlanForm
{
  public function configure()
  {
      // get last datacenter id
      $c = new Criteria();
      $c->addDescendingOrderByColumn(EtvaClusterPeer::ID);
      $cluster = EtvaClusterPeer::doSelectOne($c);
      error_log("VLANFORM[INFO] Last cluster id: ".$cluster->getId());

      $this->setValidators(array(
              'cluster_id'  => new sfValidatorInteger(array('min' => 1,'max'=>$cluster->getId(),'required' => true)),
 	      'name'        => new sfValidatorString(array('min_length' => 3,'max_length' => 10)),
              'vlanid'      => new sfValidatorInteger(array('min' => 1,'max'=>4094,'required' => false)),
 	      'tagged'      => new sfValidatorChoice(array('choices' => array(0,1)))
    ));
  }
}
