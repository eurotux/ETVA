<?php

require_once(sfConfig::get('sf_lib_dir').'/filter/base/BaseFormFilterPropel.class.php');

/**
 * EtvaServer filter form base class.
 *
 * @package    centralM
 * @subpackage filter
 * @author     Your name here
 * @version    SVN: $Id: sfPropelFormFilterGeneratedTemplate.php 13459 2008-11-28 14:48:12Z fabien $
 */
class BaseEtvaServerFormFilter extends BaseFormFilterPropel
{
  public function setup()
  {
    $this->setWidgets(array(
      'logicalvolume_id'  => new sfWidgetFormPropelChoice(array('model' => 'EtvaLogicalvolume', 'add_empty' => true)),
      'node_id'           => new sfWidgetFormPropelChoice(array('model' => 'EtvaNode', 'add_empty' => true)),
      'name'              => new sfWidgetFormFilterInput(),
      'description'       => new sfWidgetFormFilterInput(),
      'ip'                => new sfWidgetFormFilterInput(),
      'vnc_port'          => new sfWidgetFormFilterInput(),
      'uid'               => new sfWidgetFormFilterInput(),
      'mem'               => new sfWidgetFormFilterInput(),
      'vcpu'              => new sfWidgetFormFilterInput(),
      'cpuset'            => new sfWidgetFormFilterInput(),
      'location'          => new sfWidgetFormFilterInput(),
      'network_cards'     => new sfWidgetFormFilterInput(),
      'state'             => new sfWidgetFormFilterInput(),
      'mac_addresses'     => new sfWidgetFormFilterInput(),
      'sf_guard_group_id' => new sfWidgetFormPropelChoice(array('model' => 'sfGuardGroup', 'add_empty' => true)),
      'created_at'        => new sfWidgetFormFilterDate(array('from_date' => new sfWidgetFormDate(), 'to_date' => new sfWidgetFormDate(), 'with_empty' => false)),
      'updated_at'        => new sfWidgetFormFilterDate(array('from_date' => new sfWidgetFormDate(), 'to_date' => new sfWidgetFormDate(), 'with_empty' => false)),
    ));

    $this->setValidators(array(
      'logicalvolume_id'  => new sfValidatorPropelChoice(array('required' => false, 'model' => 'EtvaLogicalvolume', 'column' => 'id')),
      'node_id'           => new sfValidatorPropelChoice(array('required' => false, 'model' => 'EtvaNode', 'column' => 'id')),
      'name'              => new sfValidatorPass(array('required' => false)),
      'description'       => new sfValidatorPass(array('required' => false)),
      'ip'                => new sfValidatorPass(array('required' => false)),
      'vnc_port'          => new sfValidatorSchemaFilter('text', new sfValidatorInteger(array('required' => false))),
      'uid'               => new sfValidatorPass(array('required' => false)),
      'mem'               => new sfValidatorPass(array('required' => false)),
      'vcpu'              => new sfValidatorSchemaFilter('text', new sfValidatorInteger(array('required' => false))),
      'cpuset'            => new sfValidatorPass(array('required' => false)),
      'location'          => new sfValidatorPass(array('required' => false)),
      'network_cards'     => new sfValidatorSchemaFilter('text', new sfValidatorInteger(array('required' => false))),
      'state'             => new sfValidatorPass(array('required' => false)),
      'mac_addresses'     => new sfValidatorPass(array('required' => false)),
      'sf_guard_group_id' => new sfValidatorPropelChoice(array('required' => false, 'model' => 'sfGuardGroup', 'column' => 'id')),
      'created_at'        => new sfValidatorDateRange(array('required' => false, 'from_date' => new sfValidatorDate(array('required' => false)), 'to_date' => new sfValidatorDate(array('required' => false)))),
      'updated_at'        => new sfValidatorDateRange(array('required' => false, 'from_date' => new sfValidatorDate(array('required' => false)), 'to_date' => new sfValidatorDate(array('required' => false)))),
    ));

    $this->widgetSchema->setNameFormat('etva_server_filters[%s]');

    $this->errorSchema = new sfValidatorErrorSchema($this->validatorSchema);

    parent::setup();
  }

  public function getModelName()
  {
    return 'EtvaServer';
  }

  public function getFields()
  {
    return array(
      'id'                => 'Number',
      'logicalvolume_id'  => 'ForeignKey',
      'node_id'           => 'ForeignKey',
      'name'              => 'Text',
      'description'       => 'Text',
      'ip'                => 'Text',
      'vnc_port'          => 'Number',
      'uid'               => 'Text',
      'mem'               => 'Text',
      'vcpu'              => 'Number',
      'cpuset'            => 'Text',
      'location'          => 'Text',
      'network_cards'     => 'Number',
      'state'             => 'Text',
      'mac_addresses'     => 'Text',
      'sf_guard_group_id' => 'ForeignKey',
      'created_at'        => 'Date',
      'updated_at'        => 'Date',
    );
  }
}
