<?php

/**
 * EtvaVncTokens form base class.
 *
 * @package    centralM
 * @subpackage form
 * @author     Your name here
 * @version    SVN: $Id: sfPropelFormGeneratedTemplate.php 12815 2008-11-09 10:43:58Z fabien $
 */
class BaseEtvaVncTokensForm extends BaseFormPropel
{
  public function setup()
  {
    $this->setWidgets(array(
      'username' => new sfWidgetFormInputHidden(),
      'token'    => new sfWidgetFormInput(),
      'enctoken' => new sfWidgetFormInput(),
      'date'     => new sfWidgetFormDateTime(),
      'user_id'  => new sfWidgetFormPropelChoice(array('model' => 'sfGuardUser', 'add_empty' => false)),
    ));

    $this->setValidators(array(
      'username' => new sfValidatorPropelChoice(array('model' => 'EtvaVncTokens', 'column' => 'username', 'required' => false)),
      'token'    => new sfValidatorInteger(),
      'enctoken' => new sfValidatorInteger(),
      'date'     => new sfValidatorDateTime(array('required' => false)),
      'user_id'  => new sfValidatorPropelChoice(array('model' => 'sfGuardUser', 'column' => 'id')),
    ));

    $this->widgetSchema->setNameFormat('etva_vnc_tokens[%s]');

    $this->errorSchema = new sfValidatorErrorSchema($this->validatorSchema);

    parent::setup();
  }

  public function getModelName()
  {
    return 'EtvaVncTokens';
  }


}
