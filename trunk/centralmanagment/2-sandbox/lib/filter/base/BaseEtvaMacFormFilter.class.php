<?php

require_once(sfConfig::get('sf_lib_dir').'/filter/base/BaseFormFilterPropel.class.php');

/**
 * EtvaMac filter form base class.
 *
 * @package    centralM
 * @subpackage filter
 * @author     Your name here
 * @version    SVN: $Id: sfPropelFormFilterGeneratedTemplate.php 13459 2008-11-28 14:48:12Z fabien $
 */
class BaseEtvaMacFormFilter extends BaseFormFilterPropel
{
  public function setup()
  {
    $this->setWidgets(array(
      'mac'    => new sfWidgetFormFilterInput(),
      'in_use' => new sfWidgetFormFilterInput(),
    ));

    $this->setValidators(array(
      'mac'    => new sfValidatorPass(array('required' => false)),
      'in_use' => new sfValidatorSchemaFilter('text', new sfValidatorInteger(array('required' => false))),
    ));

    $this->widgetSchema->setNameFormat('etva_mac_filters[%s]');

    $this->errorSchema = new sfValidatorErrorSchema($this->validatorSchema);

    parent::setup();
  }

  public function getModelName()
  {
    return 'EtvaMac';
  }

  public function getFields()
  {
    return array(
      'id'     => 'Number',
      'mac'    => 'Text',
      'in_use' => 'Number',
    );
  }
}
