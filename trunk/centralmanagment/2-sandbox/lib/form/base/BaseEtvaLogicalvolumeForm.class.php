<?php

/**
 * EtvaLogicalvolume form base class.
 *
 * @package    centralM
 * @subpackage form
 * @author     Your name here
 * @version    SVN: $Id: sfPropelFormGeneratedTemplate.php 12815 2008-11-09 10:43:58Z fabien $
 */
class BaseEtvaLogicalvolumeForm extends BaseFormPropel
{
  public function setup()
  {
    $this->setWidgets(array(
      'id'             => new sfWidgetFormInputHidden(),
      'volumegroup_id' => new sfWidgetFormPropelChoice(array('model' => 'EtvaVolumegroup', 'add_empty' => false)),
      'node_id'        => new sfWidgetFormPropelChoice(array('model' => 'EtvaNode', 'add_empty' => false)),
      'lv'             => new sfWidgetFormInput(),
      'lvdevice'       => new sfWidgetFormInput(),
      'size'           => new sfWidgetFormInput(),
      'freesize'       => new sfWidgetFormInput(),
      'storage_type'   => new sfWidgetFormInput(),
      'writeable'      => new sfWidgetFormInput(),
      'in_use'         => new sfWidgetFormInput(),
      'target'         => new sfWidgetFormInput(),
    ));

    $this->setValidators(array(
      'id'             => new sfValidatorPropelChoice(array('model' => 'EtvaLogicalvolume', 'column' => 'id', 'required' => false)),
      'volumegroup_id' => new sfValidatorPropelChoice(array('model' => 'EtvaVolumegroup', 'column' => 'id')),
      'node_id'        => new sfValidatorPropelChoice(array('model' => 'EtvaNode', 'column' => 'id')),
      'lv'             => new sfValidatorString(array('max_length' => 255, 'required' => false)),
      'lvdevice'       => new sfValidatorString(array('max_length' => 255, 'required' => false)),
      'size'           => new sfValidatorInteger(array('required' => false)),
      'freesize'       => new sfValidatorInteger(array('required' => false)),
      'storage_type'   => new sfValidatorString(array('max_length' => 255, 'required' => false)),
      'writeable'      => new sfValidatorInteger(array('required' => false)),
      'in_use'         => new sfValidatorInteger(array('required' => false)),
      'target'         => new sfValidatorString(array('max_length' => 255, 'required' => false)),
    ));

    $this->widgetSchema->setNameFormat('etva_logicalvolume[%s]');

    $this->errorSchema = new sfValidatorErrorSchema($this->validatorSchema);

    parent::setup();
  }

  public function getModelName()
  {
    return 'EtvaLogicalvolume';
  }


}
