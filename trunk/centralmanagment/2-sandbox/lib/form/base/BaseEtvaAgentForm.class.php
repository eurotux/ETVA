<?php

/**
 * EtvaAgent form base class.
 *
 * @package    centralM
 * @subpackage form
 * @author     Your name here
 * @version    SVN: $Id: sfPropelFormGeneratedTemplate.php 12815 2008-11-09 10:43:58Z fabien $
 */
class BaseEtvaAgentForm extends BaseFormPropel
{
  public function setup()
  {
    $this->setWidgets(array(
      'id'          => new sfWidgetFormInputHidden(),
      'server_id'   => new sfWidgetFormPropelChoice(array('model' => 'EtvaServer', 'add_empty' => false)),
      'name'        => new sfWidgetFormInput(),
      'description' => new sfWidgetFormTextarea(),
      'uid'         => new sfWidgetFormInput(),
      'service'     => new sfWidgetFormTextarea(),
      'ip'          => new sfWidgetFormInput(),
      'state'       => new sfWidgetFormInput(),
      'created_at'  => new sfWidgetFormDateTime(),
      'updated_at'  => new sfWidgetFormDateTime(),
    ));

    $this->setValidators(array(
      'id'          => new sfValidatorPropelChoice(array('model' => 'EtvaAgent', 'column' => 'id', 'required' => false)),
      'server_id'   => new sfValidatorPropelChoice(array('model' => 'EtvaServer', 'column' => 'id')),
      'name'        => new sfValidatorString(array('max_length' => 255)),
      'description' => new sfValidatorString(array('required' => false)),
      'uid'         => new sfValidatorString(array('max_length' => 255, 'required' => false)),
      'service'     => new sfValidatorString(array('required' => false)),
      'ip'          => new sfValidatorString(array('max_length' => 255, 'required' => false)),
      'state'       => new sfValidatorInteger(),
      'created_at'  => new sfValidatorDateTime(),
      'updated_at'  => new sfValidatorDateTime(),
    ));

    $this->widgetSchema->setNameFormat('etva_agent[%s]');

    $this->errorSchema = new sfValidatorErrorSchema($this->validatorSchema);

    parent::setup();
  }

  public function getModelName()
  {
    return 'EtvaAgent';
  }


}
