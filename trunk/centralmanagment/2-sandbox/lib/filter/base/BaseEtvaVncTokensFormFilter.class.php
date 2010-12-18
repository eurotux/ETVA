<?php

require_once(sfConfig::get('sf_lib_dir').'/filter/base/BaseFormFilterPropel.class.php');

/**
 * EtvaVncTokens filter form base class.
 *
 * @package    centralM
 * @subpackage filter
 * @author     Your name here
 * @version    SVN: $Id: sfPropelFormFilterGeneratedTemplate.php 13459 2008-11-28 14:48:12Z fabien $
 */
class BaseEtvaVncTokensFormFilter extends BaseFormFilterPropel
{
  public function setup()
  {
    $this->setWidgets(array(
      'token'    => new sfWidgetFormFilterInput(),
      'enctoken' => new sfWidgetFormFilterInput(),
      'date'     => new sfWidgetFormFilterDate(array('from_date' => new sfWidgetFormDate(), 'to_date' => new sfWidgetFormDate(), 'with_empty' => true)),
      'user_id'  => new sfWidgetFormPropelChoice(array('model' => 'sfGuardUser', 'add_empty' => true)),
    ));

    $this->setValidators(array(
      'token'    => new sfValidatorSchemaFilter('text', new sfValidatorInteger(array('required' => false))),
      'enctoken' => new sfValidatorSchemaFilter('text', new sfValidatorInteger(array('required' => false))),
      'date'     => new sfValidatorDateRange(array('required' => false, 'from_date' => new sfValidatorDate(array('required' => false)), 'to_date' => new sfValidatorDate(array('required' => false)))),
      'user_id'  => new sfValidatorPropelChoice(array('required' => false, 'model' => 'sfGuardUser', 'column' => 'id')),
    ));

    $this->widgetSchema->setNameFormat('etva_vnc_tokens_filters[%s]');

    $this->errorSchema = new sfValidatorErrorSchema($this->validatorSchema);

    parent::setup();
  }

  public function getModelName()
  {
    return 'EtvaVncTokens';
  }

  public function getFields()
  {
    return array(
      'username' => 'Text',
      'token'    => 'Number',
      'enctoken' => 'Number',
      'date'     => 'Date',
      'user_id'  => 'ForeignKey',
    );
  }
}
