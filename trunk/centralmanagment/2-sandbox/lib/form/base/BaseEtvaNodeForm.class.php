<?php

/**
 * EtvaNode form base class.
 *
 * @package    centralM
 * @subpackage form
 * @author     Your name here
 * @version    SVN: $Id: sfPropelFormGeneratedTemplate.php 12815 2008-11-09 10:43:58Z fabien $
 */
class BaseEtvaNodeForm extends BaseFormPropel
{
  public function setup()
  {
    $this->setWidgets(array(
      'id'            => new sfWidgetFormInputHidden(),
      'name'          => new sfWidgetFormInput(),
      'memtotal'      => new sfWidgetFormInput(),
      'memfree'       => new sfWidgetFormInput(),
      'cputotal'      => new sfWidgetFormInput(),
      'ip'            => new sfWidgetFormInput(),
      'port'          => new sfWidgetFormInput(),
      'uid'           => new sfWidgetFormInput(),
      'network_cards' => new sfWidgetFormInput(),
      'state'         => new sfWidgetFormInput(),
      'created_at'    => new sfWidgetFormDateTime(),
      'updated_at'    => new sfWidgetFormDateTime(),
    ));

    $this->setValidators(array(
      'id'            => new sfValidatorPropelChoice(array('model' => 'EtvaNode', 'column' => 'id', 'required' => false)),
      'name'          => new sfValidatorString(array('max_length' => 255)),
      'memtotal'      => new sfValidatorInteger(array('required' => false)),
      'memfree'       => new sfValidatorInteger(array('required' => false)),
      'cputotal'      => new sfValidatorInteger(array('required' => false)),
      'ip'            => new sfValidatorString(array('max_length' => 255, 'required' => false)),
      'port'          => new sfValidatorInteger(array('required' => false)),
      'uid'           => new sfValidatorString(array('max_length' => 255, 'required' => false)),
      'network_cards' => new sfValidatorInteger(array('required' => false)),
      'state'         => new sfValidatorInteger(),
      'created_at'    => new sfValidatorDateTime(),
      'updated_at'    => new sfValidatorDateTime(array('required' => false)),
    ));

    $this->widgetSchema->setNameFormat('etva_node[%s]');

    $this->errorSchema = new sfValidatorErrorSchema($this->validatorSchema);

    parent::setup();
  }

  public function getModelName()
  {
    return 'EtvaNode';
  }


}
