<?php

class DhcpClientoptionsForm extends DhcpNetworkForm
{
    
  protected static $on_off = array('on','off');  
  protected static $ddns_update_style = array('ad-hoc','interim','none');


  public function configure()
  {

      parent::configure();
      
      unset($this['range']);


      $this->setWidget('option_host-name'              , new sfWidgetFormInputText());
      $this->setWidget('option_routers'                , new sfWidgetFormInputText());
      $this->setWidget('option_subnet-mask'            , new sfWidgetFormInputText());
      $this->setWidget('option_broadcast-address'      , new sfWidgetFormInputText());
      $this->setWidget('option_domain-name'            , new sfWidgetFormInputText());
      $this->setWidget('option_domain-name-servers'    , new sfWidgetFormInputText());
      $this->setWidget('option_time-servers'           , new sfWidgetFormInputText());
      $this->setWidget('option_log-servers'            , new sfWidgetFormInputText());
      $this->setWidget('option_swap-server'            , new sfWidgetFormInputText());
      $this->setWidget('option_root-path'              , new sfWidgetFormInputText());
      $this->setWidget('option_nis-domain'             , new sfWidgetFormInputText());
      $this->setWidget('option_nis-servers'            , new sfWidgetFormInputText());
      $this->setWidget('option_font-servers'           , new sfWidgetFormInputText());
      $this->setWidget('option_x-display-manager'      , new sfWidgetFormInputText());
      $this->setWidget('option_static-routes'          , new sfWidgetFormInputText());
      $this->setWidget('option_ntp-servers'            , new sfWidgetFormInputText());
      $this->setWidget('option_netbios-name-servers'   , new sfWidgetFormInputText());
      $this->setWidget('option_netbios-scope'          , new sfWidgetFormInputText());
      $this->setWidget('option_netbios-node-type'      , new sfWidgetFormInputText());
      $this->setWidget('option_time-offset'            , new sfWidgetFormInputText());
      $this->setWidget('option_dhcp-server-identifier' , new sfWidgetFormInputText());
      $this->setWidget('option_slp-directory-agent'    , new sfWidgetFormInputText());
      $this->setWidget('option_slp-service-scope'      , new sfWidgetFormInputText());

      $this->setWidget('use-host-decl-names'           , new sfWidgetFormInputText());
      $this->setWidget('ddns-update-style'             , new sfWidgetFormInputText());                  
      

      $this->widgetSchema->setLabel('option_host-name'              , 'Client hostname');
      $this->widgetSchema->setLabel('option_routers'                , 'Default routers');
      $this->widgetSchema->setLabel('option_subnet-mask'            , 'Subnet mask');
      $this->widgetSchema->setLabel('option_broadcast-address'      , 'Broadcast address');
      $this->widgetSchema->setLabel('option_domain-name'            , 'Domain name');
      $this->widgetSchema->setLabel('option_domain-name-servers'    , 'DNS servers');
      $this->widgetSchema->setLabel('option_time-servers'           , 'Time servers');
      $this->widgetSchema->setLabel('option_log-servers'            , 'Log servers');
      $this->widgetSchema->setLabel('option_swap-server'            , 'Swap server');
      $this->widgetSchema->setLabel('option_root-path'              , 'Root disk path');
      $this->widgetSchema->setLabel('option_nis-domain'             , 'NIS domain');
      $this->widgetSchema->setLabel('option_nis-servers'            , 'NIS servers');
      $this->widgetSchema->setLabel('option_font-servers'           , 'Font servers');
      $this->widgetSchema->setLabel('option_x-display-manager'      , 'XDM servers');
      $this->widgetSchema->setLabel('option_static-routes'          , 'Static routes');
      $this->widgetSchema->setLabel('option_ntp-servers'            , 'NTP servers');
      $this->widgetSchema->setLabel('option_netbios-name-servers'   , 'NetBIOS name servers');
      $this->widgetSchema->setLabel('option_netbios-scope'          , 'NetBIOS scope');
      $this->widgetSchema->setLabel('option_netbios-node-type'      , 'NetBIOS node type');
      $this->widgetSchema->setLabel('option_time-offset'            , 'Time offset');
      $this->widgetSchema->setLabel('option_dhcp-server-identifier' , 'DHCP server identifier');
      $this->widgetSchema->setLabel('option_slp-directory-agent'    , 'SLP directory agent IPs');
      $this->widgetSchema->setLabel('option_slp-service-scope'      , 'SLP service scope');

      $this->widgetSchema->setLabel('use-host-decl-names'           , 'Use name as client hostname');
      $this->widgetSchema->setLabel('ddns-update-style'             , 'Dynamic DNS update style');



      $valSchema = $this->getValidatorSchema(); 
      $valSchema->offsetSet('option'                , new ValidatorDhcpClientOptions(array('required' => false)));
      $valSchema->offsetSet('use-host-decl-names'   , new sfValidatorChoice(array('required' => true,'choices'=>self::$on_off)));
      $valSchema->offsetSet('ddns-update-style'     , new sfValidatorChoice(array('required' => false,'choices'=>self::$ddns_update_style)));
      $valSchema->offsetSet('authoritative'         , new sfValidatorPass());
      
  }

}
