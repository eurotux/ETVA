<?php

/**
 * EtvaService form base class.
 *
 * @package    centralM
 * @subpackage form
 * @author     Your name here
 * @version    SVN: $Id: sfPropelFormGeneratedTemplate.php 12815 2008-11-09 10:43:58Z fabien $
 */
class BaseEtvaServiceForm extends BaseFormPropel
{
  public function setup()
  {
    $this->setWidgets(array(
      'id'          => new sfWidgetFormInputHidden(),
      'server_id'   => new sfWidgetFormPropelChoice(array('model' => 'EtvaServer', 'add_empty' => false)),
      'name'        => new sfWidgetFormInput(),
      'description' => new sfWidgetFormTextarea(),
      'params'      => new sfWidgetFormTextarea(),
    ));

    $this->setValidators(array(
      'id'          => new sfValidatorPropelChoice(array('model' => 'EtvaService', 'column' => 'id', 'required' => false)),
      'server_id'   => new sfValidatorPropelChoice(array('model' => 'EtvaServer', 'column' => 'id')),
      'name'        => new sfValidatorString(array('max_length' => 255, 'required' => false)),
      'description' => new sfValidatorString(array('required' => false)),
      'params'      => new sfValidatorString(array('required' => false)),
    ));

    $this->widgetSchema->setNameFormat('etva_service[%s]');

    $this->errorSchema = new sfValidatorErrorSchema($this->validatorSchema);

    parent::setup();
  }

  public function getModelName()
  {
    return 'EtvaService';
  }


}
