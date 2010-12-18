<?php

/**
 * EtvaMac form base class.
 *
 * @package    centralM
 * @subpackage form
 * @author     Your name here
 * @version    SVN: $Id: sfPropelFormGeneratedTemplate.php 12815 2008-11-09 10:43:58Z fabien $
 */
class BaseEtvaMacForm extends BaseFormPropel
{
  public function setup()
  {
    $this->setWidgets(array(
      'id'     => new sfWidgetFormInputHidden(),
      'mac'    => new sfWidgetFormInput(),
      'in_use' => new sfWidgetFormInput(),
    ));

    $this->setValidators(array(
      'id'     => new sfValidatorPropelChoice(array('model' => 'EtvaMac', 'column' => 'id', 'required' => false)),
      'mac'    => new sfValidatorString(array('max_length' => 255, 'required' => false)),
      'in_use' => new sfValidatorInteger(array('required' => false)),
    ));

    $this->widgetSchema->setNameFormat('etva_mac[%s]');

    $this->errorSchema = new sfValidatorErrorSchema($this->validatorSchema);

    parent::setup();
  }

  public function getModelName()
  {
    return 'EtvaMac';
  }


}
