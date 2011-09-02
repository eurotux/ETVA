<?php

/**
 * EtvaNode form.
 *
 * @package    centralM
 * @subpackage form
 * @author     Your name here
 */
class EtvaNodeForm extends BaseEtvaNodeForm
{
  public function configure()
  {
      unset($this['created_at']);
 	  unset($this['updated_at']);


      //prevent form save from deleting node current physical volumes
      unset($this->widgetSchema['etva_node_physicalvolume_list']);
      unset($this->validatorSchema['etva_node_physicalvolume_list']);

      //prevent form save from deleting node current volume groups
      unset($this->widgetSchema['etva_node_volumegroup_list']);
      unset($this->validatorSchema['etva_node_volumegroup_list']);

      //prevent form save from deleting node current logical volumes
      unset($this->widgetSchema['etva_node_logicalvolume_list']);
      unset($this->validatorSchema['etva_node_logicalvolume_list']);
            

 	  $this->validatorSchema['memtotal'] = new sfValidatorNumber();
 	  $this->validatorSchema['memfree'] = new sfValidatorNumber();


 }
}
