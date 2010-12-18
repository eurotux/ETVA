<?php

require_once(sfConfig::get('sf_lib_dir').'/filter/base/BaseFormFilterPropel.class.php');

/**
 * EtvaService filter form base class.
 *
 * @package    centralM
 * @subpackage filter
 * @author     Your name here
 * @version    SVN: $Id: sfPropelFormFilterGeneratedTemplate.php 13459 2008-11-28 14:48:12Z fabien $
 */
class BaseEtvaServiceFormFilter extends BaseFormFilterPropel
{
  public function setup()
  {
    $this->setWidgets(array(
      'server_id'   => new sfWidgetFormPropelChoice(array('model' => 'EtvaServer', 'add_empty' => true)),
      'name'        => new sfWidgetFormFilterInput(),
      'description' => new sfWidgetFormFilterInput(),
      'params'      => new sfWidgetFormFilterInput(),
    ));

    $this->setValidators(array(
      'server_id'   => new sfValidatorPropelChoice(array('required' => false, 'model' => 'EtvaServer', 'column' => 'id')),
      'name'        => new sfValidatorPass(array('required' => false)),
      'description' => new sfValidatorPass(array('required' => false)),
      'params'      => new sfValidatorPass(array('required' => false)),
    ));

    $this->widgetSchema->setNameFormat('etva_service_filters[%s]');

    $this->errorSchema = new sfValidatorErrorSchema($this->validatorSchema);

    parent::setup();
  }

  public function getModelName()
  {
    return 'EtvaService';
  }

  public function getFields()
  {
    return array(
      'id'          => 'Number',
      'server_id'   => 'ForeignKey',
      'name'        => 'Text',
      'description' => 'Text',
      'params'      => 'Text',
    );
  }
}
