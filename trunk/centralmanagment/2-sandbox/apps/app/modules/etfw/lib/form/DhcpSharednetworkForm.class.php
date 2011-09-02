<?php

class DhcpSharednetworkForm extends DhcpNetworkForm
{    

  public function configure()
  {
      parent::configure();

      unset($this['range']);
      
      $this->setWidget('name'             , new sfWidgetFormInputText());      
      $this->setWidget('lastcomment'      , new sfWidgetFormInputText());
      $this->setWidget('hosts'            , new sfWidgetFormInputText());
      $this->setWidget('groups'           , new sfWidgetFormInputText());
      $this->setWidget('subnets'          , new sfWidgetFormInputText());            

      $this->widgetSchema->setLabel('name'              , 'Network name');      
      $this->widgetSchema->setLabel('lastcomment'       , 'Shared network description');
      $this->widgetSchema->setLabel('hosts'             , 'Hosts directly in this shared network');
      $this->widgetSchema->setLabel('groups'            , 'Groups directly in this shared network');
      $this->widgetSchema->setLabel('subnets'           , 'Subnets in this shared network');            



      $valSchema = $this->getValidatorSchema();
      $valSchema->offsetSet('name'          , new sfValidatorString(array('required' => true)));
      $valSchema->offsetSet('lastcomment'   , new sfValidatorString(array('required' => false)));
      
      $valSchema->offsetSet('hosts'         , new ValidatorArray(array('required' => false)));
      $valSchema->offsetSet('groups'        , new ValidatorArray(array('required' => false)));
      $valSchema->offsetSet('subnets'       , new ValidatorArray(array('required' => false)));      
      
  }
}
