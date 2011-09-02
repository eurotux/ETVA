<?php

class DhcpGroupForm extends DhcpNetworkForm
{
  protected static $on_off = array('on','off');

  public function configure()
  {
      parent::configure();

      unset($this['range'],$this['authoritative']);      
            
      $this->setWidget('lastcomment'         , new sfWidgetFormInputText());
      $this->setWidget('hosts'               , new sfWidgetFormInputText());
      $this->setWidget('parent'              , new sfWidgetFormInputText());
      $this->setWidget('use-host-decl-names' , new sfWidgetFormInputText());
        
      
      $this->widgetSchema->setLabel('lastcomment'         , 'Group description');
      $this->widgetSchema->setLabel('hosts'               , 'Hosts in this group');
      $this->widgetSchema->setLabel('parent'              , 'Group assigned to');
      $this->widgetSchema->setLabel('use-host-decl-names' , 'Use name as client hostname');


      $valSchema = $this->getValidatorSchema();      
      $valSchema->offsetSet('lastcomment'         , new sfValidatorString(array('required' => false)));
      $valSchema->offsetSet('hosts'               , new sfValidatorString(array('required' => false)));
      $valSchema->offsetSet('parent'              , new sfValidatorString(array('required' => false)));
      $valSchema->offsetSet('use-host-decl-names' , new sfValidatorChoice(array('required' => false,'choices'=>self::$on_off)));
      
  }
}
