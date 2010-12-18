<?php

require_once(sfConfig::get('sf_lib_dir').'/filter/base/BaseFormFilterPropel.class.php');

/**
 * EtvaNode filter form base class.
 *
 * @package    centralM
 * @subpackage filter
 * @author     Your name here
 * @version    SVN: $Id: sfPropelFormFilterGeneratedTemplate.php 13459 2008-11-28 14:48:12Z fabien $
 */
class BaseEtvaNodeFormFilter extends BaseFormFilterPropel
{
  public function setup()
  {
    $this->setWidgets(array(
      'name'          => new sfWidgetFormFilterInput(),
      'memtotal'      => new sfWidgetFormFilterInput(),
      'memfree'       => new sfWidgetFormFilterInput(),
      'cputotal'      => new sfWidgetFormFilterInput(),
      'ip'            => new sfWidgetFormFilterInput(),
      'port'          => new sfWidgetFormFilterInput(),
      'uid'           => new sfWidgetFormFilterInput(),
      'network_cards' => new sfWidgetFormFilterInput(),
      'state'         => new sfWidgetFormFilterInput(),
      'created_at'    => new sfWidgetFormFilterDate(array('from_date' => new sfWidgetFormDate(), 'to_date' => new sfWidgetFormDate(), 'with_empty' => false)),
      'updated_at'    => new sfWidgetFormFilterDate(array('from_date' => new sfWidgetFormDate(), 'to_date' => new sfWidgetFormDate(), 'with_empty' => true)),
    ));

    $this->setValidators(array(
      'name'          => new sfValidatorPass(array('required' => false)),
      'memtotal'      => new sfValidatorSchemaFilter('text', new sfValidatorInteger(array('required' => false))),
      'memfree'       => new sfValidatorSchemaFilter('text', new sfValidatorInteger(array('required' => false))),
      'cputotal'      => new sfValidatorSchemaFilter('text', new sfValidatorInteger(array('required' => false))),
      'ip'            => new sfValidatorPass(array('required' => false)),
      'port'          => new sfValidatorSchemaFilter('text', new sfValidatorInteger(array('required' => false))),
      'uid'           => new sfValidatorPass(array('required' => false)),
      'network_cards' => new sfValidatorSchemaFilter('text', new sfValidatorInteger(array('required' => false))),
      'state'         => new sfValidatorSchemaFilter('text', new sfValidatorInteger(array('required' => false))),
      'created_at'    => new sfValidatorDateRange(array('required' => false, 'from_date' => new sfValidatorDate(array('required' => false)), 'to_date' => new sfValidatorDate(array('required' => false)))),
      'updated_at'    => new sfValidatorDateRange(array('required' => false, 'from_date' => new sfValidatorDate(array('required' => false)), 'to_date' => new sfValidatorDate(array('required' => false)))),
    ));

    $this->widgetSchema->setNameFormat('etva_node_filters[%s]');

    $this->errorSchema = new sfValidatorErrorSchema($this->validatorSchema);

    parent::setup();
  }

  public function getModelName()
  {
    return 'EtvaNode';
  }

  public function getFields()
  {
    return array(
      'id'            => 'Number',
      'name'          => 'Text',
      'memtotal'      => 'Number',
      'memfree'       => 'Number',
      'cputotal'      => 'Number',
      'ip'            => 'Text',
      'port'          => 'Number',
      'uid'           => 'Text',
      'network_cards' => 'Number',
      'state'         => 'Number',
      'created_at'    => 'Date',
      'updated_at'    => 'Date',
    );
  }
}
