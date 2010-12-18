<?php

require_once(sfConfig::get('sf_lib_dir').'/filter/base/BaseFormFilterPropel.class.php');

/**
 * EtvaLogicalvolume filter form base class.
 *
 * @package    centralM
 * @subpackage filter
 * @author     Your name here
 * @version    SVN: $Id: sfPropelFormFilterGeneratedTemplate.php 13459 2008-11-28 14:48:12Z fabien $
 */
class BaseEtvaLogicalvolumeFormFilter extends BaseFormFilterPropel
{
  public function setup()
  {
    $this->setWidgets(array(
      'volumegroup_id' => new sfWidgetFormPropelChoice(array('model' => 'EtvaVolumegroup', 'add_empty' => true)),
      'node_id'        => new sfWidgetFormPropelChoice(array('model' => 'EtvaNode', 'add_empty' => true)),
      'lv'             => new sfWidgetFormFilterInput(),
      'lvdevice'       => new sfWidgetFormFilterInput(),
      'size'           => new sfWidgetFormFilterInput(),
      'freesize'       => new sfWidgetFormFilterInput(),
      'storage_type'   => new sfWidgetFormFilterInput(),
      'writeable'      => new sfWidgetFormFilterInput(),
      'in_use'         => new sfWidgetFormFilterInput(),
      'target'         => new sfWidgetFormFilterInput(),
    ));

    $this->setValidators(array(
      'volumegroup_id' => new sfValidatorPropelChoice(array('required' => false, 'model' => 'EtvaVolumegroup', 'column' => 'id')),
      'node_id'        => new sfValidatorPropelChoice(array('required' => false, 'model' => 'EtvaNode', 'column' => 'id')),
      'lv'             => new sfValidatorPass(array('required' => false)),
      'lvdevice'       => new sfValidatorPass(array('required' => false)),
      'size'           => new sfValidatorSchemaFilter('text', new sfValidatorInteger(array('required' => false))),
      'freesize'       => new sfValidatorSchemaFilter('text', new sfValidatorInteger(array('required' => false))),
      'storage_type'   => new sfValidatorPass(array('required' => false)),
      'writeable'      => new sfValidatorSchemaFilter('text', new sfValidatorInteger(array('required' => false))),
      'in_use'         => new sfValidatorSchemaFilter('text', new sfValidatorInteger(array('required' => false))),
      'target'         => new sfValidatorPass(array('required' => false)),
    ));

    $this->widgetSchema->setNameFormat('etva_logicalvolume_filters[%s]');

    $this->errorSchema = new sfValidatorErrorSchema($this->validatorSchema);

    parent::setup();
  }

  public function getModelName()
  {
    return 'EtvaLogicalvolume';
  }

  public function getFields()
  {
    return array(
      'id'             => 'Number',
      'volumegroup_id' => 'ForeignKey',
      'node_id'        => 'ForeignKey',
      'lv'             => 'Text',
      'lvdevice'       => 'Text',
      'size'           => 'Number',
      'freesize'       => 'Number',
      'storage_type'   => 'Text',
      'writeable'      => 'Number',
      'in_use'         => 'Number',
      'target'         => 'Text',
    );
  }
}
