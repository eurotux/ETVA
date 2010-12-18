<?php

/**
 * EtvaVolumegroup form base class.
 *
 * @package    centralM
 * @subpackage form
 * @author     Your name here
 * @version    SVN: $Id: sfPropelFormGeneratedTemplate.php 12815 2008-11-09 10:43:58Z fabien $
 */
class BaseEtvaVolumegroupForm extends BaseFormPropel
{
  public function setup()
  {
    $this->setWidgets(array(
      'id'       => new sfWidgetFormInputHidden(),
      'node_id'  => new sfWidgetFormPropelChoice(array('model' => 'EtvaNode', 'add_empty' => false)),
      'vg'       => new sfWidgetFormInput(),
      'size'     => new sfWidgetFormInput(),
      'freesize' => new sfWidgetFormInput(),
    ));

    $this->setValidators(array(
      'id'       => new sfValidatorPropelChoice(array('model' => 'EtvaVolumegroup', 'column' => 'id', 'required' => false)),
      'node_id'  => new sfValidatorPropelChoice(array('model' => 'EtvaNode', 'column' => 'id')),
      'vg'       => new sfValidatorString(array('max_length' => 255, 'required' => false)),
      'size'     => new sfValidatorInteger(array('required' => false)),
      'freesize' => new sfValidatorInteger(array('required' => false)),
    ));

    $this->widgetSchema->setNameFormat('etva_volumegroup[%s]');

    $this->errorSchema = new sfValidatorErrorSchema($this->validatorSchema);

    parent::setup();
  }

  public function getModelName()
  {
    return 'EtvaVolumegroup';
  }


}
