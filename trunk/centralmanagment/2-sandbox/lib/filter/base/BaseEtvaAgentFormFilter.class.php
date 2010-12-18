<?php

require_once(sfConfig::get('sf_lib_dir').'/filter/base/BaseFormFilterPropel.class.php');

/**
 * EtvaAgent filter form base class.
 *
 * @package    centralM
 * @subpackage filter
 * @author     Your name here
 * @version    SVN: $Id: sfPropelFormFilterGeneratedTemplate.php 13459 2008-11-28 14:48:12Z fabien $
 */
class BaseEtvaAgentFormFilter extends BaseFormFilterPropel
{
  public function setup()
  {
    $this->setWidgets(array(
      'server_id'   => new sfWidgetFormPropelChoice(array('model' => 'EtvaServer', 'add_empty' => true)),
      'name'        => new sfWidgetFormFilterInput(),
      'description' => new sfWidgetFormFilterInput(),
      'uid'         => new sfWidgetFormFilterInput(),
      'service'     => new sfWidgetFormFilterInput(),
      'ip'          => new sfWidgetFormFilterInput(),
      'state'       => new sfWidgetFormFilterInput(),
      'created_at'  => new sfWidgetFormFilterDate(array('from_date' => new sfWidgetFormDate(), 'to_date' => new sfWidgetFormDate(), 'with_empty' => false)),
      'updated_at'  => new sfWidgetFormFilterDate(array('from_date' => new sfWidgetFormDate(), 'to_date' => new sfWidgetFormDate(), 'with_empty' => false)),
    ));

    $this->setValidators(array(
      'server_id'   => new sfValidatorPropelChoice(array('required' => false, 'model' => 'EtvaServer', 'column' => 'id')),
      'name'        => new sfValidatorPass(array('required' => false)),
      'description' => new sfValidatorPass(array('required' => false)),
      'uid'         => new sfValidatorPass(array('required' => false)),
      'service'     => new sfValidatorPass(array('required' => false)),
      'ip'          => new sfValidatorPass(array('required' => false)),
      'state'       => new sfValidatorSchemaFilter('text', new sfValidatorInteger(array('required' => false))),
      'created_at'  => new sfValidatorDateRange(array('required' => false, 'from_date' => new sfValidatorDate(array('required' => false)), 'to_date' => new sfValidatorDate(array('required' => false)))),
      'updated_at'  => new sfValidatorDateRange(array('required' => false, 'from_date' => new sfValidatorDate(array('required' => false)), 'to_date' => new sfValidatorDate(array('required' => false)))),
    ));

    $this->widgetSchema->setNameFormat('etva_agent_filters[%s]');

    $this->errorSchema = new sfValidatorErrorSchema($this->validatorSchema);

    parent::setup();
  }

  public function getModelName()
  {
    return 'EtvaAgent';
  }

  public function getFields()
  {
    return array(
      'id'          => 'Number',
      'server_id'   => 'ForeignKey',
      'name'        => 'Text',
      'description' => 'Text',
      'uid'         => 'Text',
      'service'     => 'Text',
      'ip'          => 'Text',
      'state'       => 'Number',
      'created_at'  => 'Date',
      'updated_at'  => 'Date',
    );
  }
}
