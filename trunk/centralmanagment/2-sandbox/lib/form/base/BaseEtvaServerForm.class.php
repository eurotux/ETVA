<?php

/**
 * EtvaServer form base class.
 *
 * @package    centralM
 * @subpackage form
 * @author     Your name here
 * @version    SVN: $Id: sfPropelFormGeneratedTemplate.php 12815 2008-11-09 10:43:58Z fabien $
 */
class BaseEtvaServerForm extends BaseFormPropel
{
  public function setup()
  {
    $this->setWidgets(array(
      'id'                => new sfWidgetFormInputHidden(),
      'logicalvolume_id'  => new sfWidgetFormPropelChoice(array('model' => 'EtvaLogicalvolume', 'add_empty' => false)),
      'node_id'           => new sfWidgetFormPropelChoice(array('model' => 'EtvaNode', 'add_empty' => false)),
      'name'              => new sfWidgetFormInput(),
      'description'       => new sfWidgetFormTextarea(),
      'ip'                => new sfWidgetFormInput(),
      'vnc_port'          => new sfWidgetFormInput(),
      'uid'               => new sfWidgetFormInput(),
      'mem'               => new sfWidgetFormInput(),
      'vcpu'              => new sfWidgetFormInput(),
      'cpuset'            => new sfWidgetFormInput(),
      'location'          => new sfWidgetFormInput(),
      'network_cards'     => new sfWidgetFormInput(),
      'state'             => new sfWidgetFormInput(),
      'mac_addresses'     => new sfWidgetFormTextarea(),
      'sf_guard_group_id' => new sfWidgetFormPropelChoice(array('model' => 'sfGuardGroup', 'add_empty' => false)),
      'created_at'        => new sfWidgetFormDateTime(),
      'updated_at'        => new sfWidgetFormDateTime(),
    ));

    $this->setValidators(array(
      'id'                => new sfValidatorPropelChoice(array('model' => 'EtvaServer', 'column' => 'id', 'required' => false)),
      'logicalvolume_id'  => new sfValidatorPropelChoice(array('model' => 'EtvaLogicalvolume', 'column' => 'id')),
      'node_id'           => new sfValidatorPropelChoice(array('model' => 'EtvaNode', 'column' => 'id')),
      'name'              => new sfValidatorString(array('max_length' => 255)),
      'description'       => new sfValidatorString(array('required' => false)),
      'ip'                => new sfValidatorString(array('max_length' => 255)),
      'vnc_port'          => new sfValidatorInteger(array('required' => false)),
      'uid'               => new sfValidatorString(array('max_length' => 255, 'required' => false)),
      'mem'               => new sfValidatorString(array('max_length' => 255, 'required' => false)),
      'vcpu'              => new sfValidatorInteger(array('required' => false)),
      'cpuset'            => new sfValidatorString(array('max_length' => 255, 'required' => false)),
      'location'          => new sfValidatorString(array('max_length' => 255, 'required' => false)),
      'network_cards'     => new sfValidatorInteger(array('required' => false)),
      'state'             => new sfValidatorString(array('max_length' => 255)),
      'mac_addresses'     => new sfValidatorString(array('required' => false)),
      'sf_guard_group_id' => new sfValidatorPropelChoice(array('model' => 'sfGuardGroup', 'column' => 'id')),
      'created_at'        => new sfValidatorDateTime(),
      'updated_at'        => new sfValidatorDateTime(),
    ));

    $this->widgetSchema->setNameFormat('etva_server[%s]');

    $this->errorSchema = new sfValidatorErrorSchema($this->validatorSchema);

    parent::setup();
  }

  public function getModelName()
  {
    return 'EtvaServer';
  }


}
