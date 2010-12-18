<?php

/**
 * EtvaNetwork form base class.
 *
 * @package    centralM
 * @subpackage form
 * @author     Your name here
 * @version    SVN: $Id: sfPropelFormGeneratedTemplate.php 12815 2008-11-09 10:43:58Z fabien $
 */
class BaseEtvaNetworkForm extends BaseFormPropel
{
  public function setup()
  {
    $this->setWidgets(array(
      'id'        => new sfWidgetFormInputHidden(),
      'server_id' => new sfWidgetFormPropelChoice(array('model' => 'EtvaServer', 'add_empty' => false)),
      'port'      => new sfWidgetFormInput(),
      'ip'        => new sfWidgetFormInput(),
      'mask'      => new sfWidgetFormInput(),
      'mac'       => new sfWidgetFormInput(),
      'vlan'      => new sfWidgetFormInput(),
      'target'    => new sfWidgetFormInput(),
    ));

    $this->setValidators(array(
      'id'        => new sfValidatorPropelChoice(array('model' => 'EtvaNetwork', 'column' => 'id', 'required' => false)),
      'server_id' => new sfValidatorPropelChoice(array('model' => 'EtvaServer', 'column' => 'id')),
      'port'      => new sfValidatorString(array('max_length' => 255, 'required' => false)),
      'ip'        => new sfValidatorString(array('max_length' => 255, 'required' => false)),
      'mask'      => new sfValidatorString(array('max_length' => 255, 'required' => false)),
      'mac'       => new sfValidatorString(array('max_length' => 255, 'required' => false)),
      'vlan'      => new sfValidatorString(array('max_length' => 255, 'required' => false)),
      'target'    => new sfValidatorString(array('max_length' => 255, 'required' => false)),
    ));

    $this->widgetSchema->setNameFormat('etva_network[%s]');

    $this->errorSchema = new sfValidatorErrorSchema($this->validatorSchema);

    parent::setup();
  }

  public function getModelName()
  {
    return 'EtvaNetwork';
  }


}
