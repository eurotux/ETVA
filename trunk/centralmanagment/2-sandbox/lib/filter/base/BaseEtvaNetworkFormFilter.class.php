<?php

require_once(sfConfig::get('sf_lib_dir').'/filter/base/BaseFormFilterPropel.class.php');

/**
 * EtvaNetwork filter form base class.
 *
 * @package    centralM
 * @subpackage filter
 * @author     Your name here
 * @version    SVN: $Id: sfPropelFormFilterGeneratedTemplate.php 13459 2008-11-28 14:48:12Z fabien $
 */
class BaseEtvaNetworkFormFilter extends BaseFormFilterPropel
{
  public function setup()
  {
    $this->setWidgets(array(
      'server_id' => new sfWidgetFormPropelChoice(array('model' => 'EtvaServer', 'add_empty' => true)),
      'port'      => new sfWidgetFormFilterInput(),
      'ip'        => new sfWidgetFormFilterInput(),
      'mask'      => new sfWidgetFormFilterInput(),
      'mac'       => new sfWidgetFormFilterInput(),
      'vlan'      => new sfWidgetFormFilterInput(),
      'target'    => new sfWidgetFormFilterInput(),
    ));

    $this->setValidators(array(
      'server_id' => new sfValidatorPropelChoice(array('required' => false, 'model' => 'EtvaServer', 'column' => 'id')),
      'port'      => new sfValidatorPass(array('required' => false)),
      'ip'        => new sfValidatorPass(array('required' => false)),
      'mask'      => new sfValidatorPass(array('required' => false)),
      'mac'       => new sfValidatorPass(array('required' => false)),
      'vlan'      => new sfValidatorPass(array('required' => false)),
      'target'    => new sfValidatorPass(array('required' => false)),
    ));

    $this->widgetSchema->setNameFormat('etva_network_filters[%s]');

    $this->errorSchema = new sfValidatorErrorSchema($this->validatorSchema);

    parent::setup();
  }

  public function getModelName()
  {
    return 'EtvaNetwork';
  }

  public function getFields()
  {
    return array(
      'id'        => 'Number',
      'server_id' => 'ForeignKey',
      'port'      => 'Text',
      'ip'        => 'Text',
      'mask'      => 'Text',
      'mac'       => 'Text',
      'vlan'      => 'Text',
      'target'    => 'Text',
    );
  }
}
