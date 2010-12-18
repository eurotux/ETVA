<?php

require_once(sfConfig::get('sf_lib_dir').'/filter/base/BaseFormFilterPropel.class.php');

/**
 * EtvaVolumegroup filter form base class.
 *
 * @package    centralM
 * @subpackage filter
 * @author     Your name here
 * @version    SVN: $Id: sfPropelFormFilterGeneratedTemplate.php 13459 2008-11-28 14:48:12Z fabien $
 */
class BaseEtvaVolumegroupFormFilter extends BaseFormFilterPropel
{
  public function setup()
  {
    $this->setWidgets(array(
      'node_id'  => new sfWidgetFormPropelChoice(array('model' => 'EtvaNode', 'add_empty' => true)),
      'vg'       => new sfWidgetFormFilterInput(),
      'size'     => new sfWidgetFormFilterInput(),
      'freesize' => new sfWidgetFormFilterInput(),
    ));

    $this->setValidators(array(
      'node_id'  => new sfValidatorPropelChoice(array('required' => false, 'model' => 'EtvaNode', 'column' => 'id')),
      'vg'       => new sfValidatorPass(array('required' => false)),
      'size'     => new sfValidatorSchemaFilter('text', new sfValidatorInteger(array('required' => false))),
      'freesize' => new sfValidatorSchemaFilter('text', new sfValidatorInteger(array('required' => false))),
    ));

    $this->widgetSchema->setNameFormat('etva_volumegroup_filters[%s]');

    $this->errorSchema = new sfValidatorErrorSchema($this->validatorSchema);

    parent::setup();
  }

  public function getModelName()
  {
    return 'EtvaVolumegroup';
  }

  public function getFields()
  {
    return array(
      'id'       => 'Number',
      'node_id'  => 'ForeignKey',
      'vg'       => 'Text',
      'size'     => 'Number',
      'freesize' => 'Number',
    );
  }
}
