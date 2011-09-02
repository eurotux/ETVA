<?php

class DhcpSubnetForm extends DhcpNetworkForm
{    

  public function configure()
  {
      parent::configure();
      
      $this->setWidget('address'           , new sfWidgetFormInputText());
      $this->setWidget('netmask'           , new sfWidgetFormInputText());
      $this->setWidget('lastcomment'       , new sfWidgetFormInputText());
      $this->setWidget('parent'            , new sfWidgetFormInputText());      
      $this->setWidget('hosts'             , new sfWidgetFormInputText());
      $this->setWidget('groups'            , new sfWidgetFormInputText());                

      $this->widgetSchema->setLabel('address'          , 'Network address');
      $this->widgetSchema->setLabel('netmask'          , 'Netmask');
      $this->widgetSchema->setLabel('lastcomment'      , 'Subnet description');
      $this->widgetSchema->setLabel('parent'           , 'Shared network');      
      $this->widgetSchema->setLabel('hosts'            , 'Hosts directly in this subnet');
      $this->widgetSchema->setLabel('groups'           , 'Groups directly in this subnet');      



      $valSchema = $this->getValidatorSchema();
      $valSchema->offsetSet('address'       , new ValidatorIP(array('required' => true)));
      $valSchema->offsetSet('netmask'       , new ValidatorIP(array('required' => true)));
      $valSchema->offsetSet('lastcomment'   , new sfValidatorString(array('required' => false)));
      $valSchema->offsetSet('parent'        , new sfValidatorString(array('required' => false)));
      $valSchema->offsetSet('hosts'         , new ValidatorArray(array('required' => false)));
      $valSchema->offsetSet('groups'        , new ValidatorArray(array('required' => false)));      
      
  }
}
