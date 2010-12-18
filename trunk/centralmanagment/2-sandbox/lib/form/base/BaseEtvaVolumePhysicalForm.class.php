<?php

/**
 * EtvaVolumePhysical form base class.
 *
 * @package    centralM
 * @subpackage form
 * @author     Your name here
 * @version    SVN: $Id: sfPropelFormGeneratedTemplate.php 12815 2008-11-09 10:43:58Z fabien $
 */
class BaseEtvaVolumePhysicalForm extends BaseFormPropel
{
  public function setup()
  {
    $this->setWidgets(array(
      'id'                => new sfWidgetFormInputHidden(),
      'volumegroup_id'    => new sfWidgetFormPropelChoice(array('model' => 'EtvaVolumegroup', 'add_empty' => false)),
      'physicalvolume_id' => new sfWidgetFormPropelChoice(array('model' => 'EtvaPhysicalvolume', 'add_empty' => false)),
    ));

    $this->setValidators(array(
      'id'                => new sfValidatorPropelChoice(array('model' => 'EtvaVolumePhysical', 'column' => 'id', 'required' => false)),
      'volumegroup_id'    => new sfValidatorPropelChoice(array('model' => 'EtvaVolumegroup', 'column' => 'id')),
      'physicalvolume_id' => new sfValidatorPropelChoice(array('model' => 'EtvaPhysicalvolume', 'column' => 'id')),
    ));

    $this->widgetSchema->setNameFormat('etva_volume_physical[%s]');

    $this->errorSchema = new sfValidatorErrorSchema($this->validatorSchema);

    parent::setup();
  }

  public function getModelName()
  {
    return 'EtvaVolumePhysical';
  }


}
