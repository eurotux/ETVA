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
      $this->setValidators(array( 	      
 	      'name' => new sfValidatorString(array('min_length' => 3,'max_length' => 10)),
          'vlanid'   => new sfValidatorInteger(array('min' => 1,'max'=>4094,'required' => false)),
 	      'tagged' => new sfValidatorChoice(array('choices' => array(0,1)))
    ));
  }
}
