<?php

/**
 * EtvaPhysicalvolume form base class.
 *
 * @package    centralM
 * @subpackage form
 * @author     Your name here
 * @version    SVN: $Id: sfPropelFormGeneratedTemplate.php 12815 2008-11-09 10:43:58Z fabien $
 */
class BaseEtvaPhysicalvolumeForm extends BaseFormPropel
{
  public function setup()
  {
    $this->setWidgets(array(
      'id'           => new sfWidgetFormInputHidden(),
      'node_id'      => new sfWidgetFormPropelChoice(array('model' => 'EtvaNode', 'add_empty' => false)),
      'name'         => new sfWidgetFormInput(),
      'device'       => new sfWidgetFormInput(),
      'devsize'      => new sfWidgetFormInput(),
      'pv'           => new sfWidgetFormInput(),
      'pvsize'       => new sfWidgetFormInput(),
      'pvfreesize'   => new sfWidgetFormInput(),
      'pvinit'       => new sfWidgetFormInput(),
      'storage_type' => new sfWidgetFormInput(),
      'allocatable'  => new sfWidgetFormInput(),
    ));

    $this->setValidators(array(
      'id'           => new sfValidatorPropelChoice(array('model' => 'EtvaPhysicalvolume', 'column' => 'id', 'required' => false)),
      'node_id'      => new sfValidatorPropelChoice(array('model' => 'EtvaNode', 'column' => 'id')),
      'name'         => new sfValidatorString(array('max_length' => 255, 'required' => false)),
      'device'       => new sfValidatorString(array('max_length' => 255, 'required' => false)),
      'devsize'      => new sfValidatorInteger(array('required' => false)),
      'pv'           => new sfValidatorString(array('max_length' => 255, 'required' => false)),
      'pvsize'       => new sfValidatorInteger(array('required' => false)),
      'pvfreesize'   => new sfValidatorInteger(array('required' => false)),
      'pvinit'       => new sfValidatorInteger(array('required' => false)),
      'storage_type' => new sfValidatorString(array('max_length' => 255, 'required' => false)),
      'allocatable'  => new sfValidatorInteger(array('required' => false)),
    ));

    $this->widgetSchema->setNameFormat('etva_physicalvolume[%s]');

    $this->errorSchema = new sfValidatorErrorSchema($this->validatorSchema);

    parent::setup();
  }

  public function getModelName()
  {
    return 'EtvaPhysicalvolume';
  }


}
