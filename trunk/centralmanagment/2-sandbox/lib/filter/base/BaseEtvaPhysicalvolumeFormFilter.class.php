<?php

require_once(sfConfig::get('sf_lib_dir').'/filter/base/BaseFormFilterPropel.class.php');

/**
 * EtvaPhysicalvolume filter form base class.
 *
 * @package    centralM
 * @subpackage filter
 * @author     Your name here
 * @version    SVN: $Id: sfPropelFormFilterGeneratedTemplate.php 13459 2008-11-28 14:48:12Z fabien $
 */
class BaseEtvaPhysicalvolumeFormFilter extends BaseFormFilterPropel
{
  public function setup()
  {
    $this->setWidgets(array(
      'node_id'      => new sfWidgetFormPropelChoice(array('model' => 'EtvaNode', 'add_empty' => true)),
      'name'         => new sfWidgetFormFilterInput(),
      'device'       => new sfWidgetFormFilterInput(),
      'devsize'      => new sfWidgetFormFilterInput(),
      'pv'           => new sfWidgetFormFilterInput(),
      'pvsize'       => new sfWidgetFormFilterInput(),
      'pvfreesize'   => new sfWidgetFormFilterInput(),
      'pvinit'       => new sfWidgetFormFilterInput(),
      'storage_type' => new sfWidgetFormFilterInput(),
      'allocatable'  => new sfWidgetFormFilterInput(),
    ));

    $this->setValidators(array(
      'node_id'      => new sfValidatorPropelChoice(array('required' => false, 'model' => 'EtvaNode', 'column' => 'id')),
      'name'         => new sfValidatorPass(array('required' => false)),
      'device'       => new sfValidatorPass(array('required' => false)),
      'devsize'      => new sfValidatorSchemaFilter('text', new sfValidatorInteger(array('required' => false))),
      'pv'           => new sfValidatorPass(array('required' => false)),
      'pvsize'       => new sfValidatorSchemaFilter('text', new sfValidatorInteger(array('required' => false))),
      'pvfreesize'   => new sfValidatorSchemaFilter('text', new sfValidatorInteger(array('required' => false))),
      'pvinit'       => new sfValidatorSchemaFilter('text', new sfValidatorInteger(array('required' => false))),
      'storage_type' => new sfValidatorPass(array('required' => false)),
      'allocatable'  => new sfValidatorSchemaFilter('text', new sfValidatorInteger(array('required' => false))),
    ));

    $this->widgetSchema->setNameFormat('etva_physicalvolume_filters[%s]');

    $this->errorSchema = new sfValidatorErrorSchema($this->validatorSchema);

    parent::setup();
  }

  public function getModelName()
  {
    return 'EtvaPhysicalvolume';
  }

  public function getFields()
  {
    return array(
      'id'           => 'Number',
      'node_id'      => 'ForeignKey',
      'name'         => 'Text',
      'device'       => 'Text',
      'devsize'      => 'Number',
      'pv'           => 'Text',
      'pvsize'       => 'Number',
      'pvfreesize'   => 'Number',
      'pvinit'       => 'Number',
      'storage_type' => 'Text',
      'allocatable'  => 'Number',
    );
  }
}
