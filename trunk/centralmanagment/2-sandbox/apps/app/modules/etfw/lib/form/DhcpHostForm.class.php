<?php

class DhcpHostForm extends DhcpNetworkForm
{  

  public function configure()
  {
      parent::configure();

      unset($this['range'],$this['authoritative']);      
      
      $this->setWidget('host'             , new sfWidgetFormInputText());
      $this->setWidget('lastcomment'      , new sfWidgetFormInputText());
      $this->setWidget('parent'      , new sfWidgetFormInputText());
      $this->setWidget('fixed-address'      , new sfWidgetFormInputText());
      $this->setWidget('hardware'      , new sfWidgetFormInputText());
        

      $this->widgetSchema->setLabel('host'              , 'Host name');
      $this->widgetSchema->setLabel('lastcomment'       , 'Host description');
      $this->widgetSchema->setLabel('parent'       , 'Host assigned to');
      $this->widgetSchema->setLabel('fixed-address'       , 'Fixed IP address');
      $this->widgetSchema->setLabel('hardware'       , 'Hardware Address');


      $valSchema = $this->getValidatorSchema();
      $valSchema->offsetSet('host'          , new sfValidatorString(array('required' => true)));
      $valSchema->offsetSet('lastcomment'   , new sfValidatorString(array('required' => false)));
      $valSchema->offsetSet('parent'        , new sfValidatorString(array('required' => false)));
      $valSchema->offsetSet('fixed-address'       , new ValidatorIP(array('required' => false)));
      $valSchema->offsetSet('hardware'       , new sfValidatorString(array('required' => false)));         
      
  }
}
