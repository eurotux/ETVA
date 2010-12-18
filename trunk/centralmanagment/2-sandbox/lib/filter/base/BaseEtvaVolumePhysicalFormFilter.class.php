<?php

require_once(sfConfig::get('sf_lib_dir').'/filter/base/BaseFormFilterPropel.class.php');

/**
 * EtvaVolumePhysical filter form base class.
 *
 * @package    centralM
 * @subpackage filter
 * @author     Your name here
 * @version    SVN: $Id: sfPropelFormFilterGeneratedTemplate.php 13459 2008-11-28 14:48:12Z fabien $
 */
class BaseEtvaVolumePhysicalFormFilter extends BaseFormFilterPropel
{
  public function setup()
  {
    $this->setWidgets(array(
      'volumegroup_id'    => new sfWidgetFormPropelChoice(array('model' => 'EtvaVolumegroup', 'add_empty' => true)),
      'physicalvolume_id' => new sfWidgetFormPropelChoice(array('model' => 'EtvaPhysicalvolume', 'add_empty' => true)),
    ));

    $this->setValidators(array(
      'volumegroup_id'    => new sfValidatorPropelChoice(array('required' => false, 'model' => 'EtvaVolumegroup', 'column' => 'id')),
      'physicalvolume_id' => new sfValidatorPropelChoice(array('required' => false, 'model' => 'EtvaPhysicalvolume', 'column' => 'id')),
    ));

    $this->widgetSchema->setNameFormat('etva_volume_physical_filters[%s]');

    $this->errorSchema = new sfValidatorErrorSchema($this->validatorSchema);

    parent::setup();
  }

  public function getModelName()
  {
    return 'EtvaVolumePhysical';
  }

  public function getFields()
  {
    return array(
      'id'                => 'Number',
      'volumegroup_id'    => 'ForeignKey',
      'physicalvolume_id' => 'ForeignKey',
    );
  }
}
