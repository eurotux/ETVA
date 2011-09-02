<?php

class DhcpNetworkForm extends myBaseForm
{
    
  protected static $ddns_updates = array('on','off');

  public function configure()
  {      
      
      $this->setValidators(array(                    
        'uuid'                          => new sfValidatorString(array('required' => false)),
        'range'                         => new ValidatorDhcpRange(array('required' => false)),
        'filename'                      => new sfValidatorString(array('required' => false)),
        'server-name'                   => new sfValidatorString(array('required' => false)),
        'next-server'                   => new sfValidatorString(array('required' => false)),                
        'default-lease-time'            => new sfValidatorNumber(array('required' => false)),
        'max-lease-time'                => new sfValidatorNumber(array('required' => false)),        
        'dynamic-bootp-lease-cutoff'    => new sfValidatorString(array('required' => false)),
        'dynamic-bootp-lease-length'    => new sfValidatorNumber(array('required' => false)),
        'ddns-rev-domainname'           => new sfValidatorString(array('required' => false)),
        'ddns-domainname'               => new sfValidatorString(array('required' => false)),
        'ddns-hostname'                 => new sfValidatorString(array('required' => false)),
        'ddns-updates'                  => new sfValidatorChoice(array('required' => false,'choices'=>self::$ddns_updates)),        
        'allow'                         => new ValidatorArray(array('required' => false)),
        'deny'                          => new ValidatorArray(array('required' => false)),
        'ignore'                        => new ValidatorArray(array('required' => false)),
        'authoritative'                 => new sfValidatorPass()
      ));


      $this->setWidgets(array(
        'range'                         => new sfWidgetFormInputText(),
        'filename'                      => new sfWidgetFormInputText(),
        'server-name'                   => new sfWidgetFormInputText(),
        'next-server'                   => new sfWidgetFormInputText(),
        'default-lease-time'            => new sfWidgetFormInputText(),
        'max-lease-time'                => new sfWidgetFormInputText(),
        'dynamic-bootp-lease-cutoff'    => new sfWidgetFormInputText(),
        'dynamic-bootp-lease-length'    => new sfWidgetFormInputText(),        
        'ddns-rev-domainname'           => new sfWidgetFormInputText(),
        'ddns-domainname'               => new sfWidgetFormInputText(),
        'ddns-hostname'                 => new sfWidgetFormInputText(),
        'ddns-updates'                  => new sfWidgetFormInputText(),
        'unknown-clients'               => new sfWidgetFormInputText(),
        'client-updates'                => new sfWidgetFormInputText(),
        'authoritative'                 => new sfWidgetFormInputText()
        
      ));


      $this->widgetSchema->setLabels(array(
        'range'                         => 'Address ranges',
        'filename'                      => 'Boot filename',
        'server-name'                   => 'Server name',
        'next-server'                   => 'Boot file server',
        'default-lease-time'            => 'Default lease time',
        'max-lease-time'                => 'Maximum lease time',
        'dynamic-bootp-lease-cutoff'    => 'Lease end for BOOTP clients',
        'dynamic-bootp-lease-length'    => 'Lease length for BOOTP clients',
        'ddns-rev-domainname'           => 'Dynamic DNS reverse domain',
        'ddns-domainname'               => 'Dynamic DNS domain name',
        'ddns-hostname'                 => 'Dynamic DNS hostname',
        'ddns-updates'                  => 'Dynamic DNS enabled',
        'unknown-clients'               => 'Allow unknown clients',
        'client-updates'                => 'Can clients update their own records',
        'authoritative'                 => 'Server is authoritative for all subnets'
     ));
      
  }
}
