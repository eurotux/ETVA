<?php

class DhcpPoolForm extends DhcpNetworkForm
{      

  public function configure()
  {
      parent::configure();


      $this->setWidget('failover' , new sfWidgetFormInputText());
      $this->setWidget('allow'    , new sfWidgetFormInputText());
      $this->setWidget('deny'     , new sfWidgetFormInputText());
            

      $this->widgetSchema->setLabel('failover' , 'Failover Peer');
      $this->widgetSchema->setLabel('allow'    , 'Clients to allow');
      $this->widgetSchema->setLabel('deny'     , 'Clients to deny');
                              

      $valSchema = $this->getValidatorSchema();
      $valSchema->offsetSet('failover' , new sfValidatorString(array('required' => false)));
      $valSchema->offsetSet('parent'   , new sfValidatorString(array('required' => false)));
      
  }
}
