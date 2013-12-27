<?php

//require_once(dirname(__FILE__).'/../config/ProjectConfiguration.class.php');

class etvaSetSMTPTask extends sfBaseTask
{
  protected function configure()
  {
    // // add your own arguments here
    // $this->addArguments(array(
    //   new sfCommandArgument('my_arg', sfCommandArgument::REQUIRED, 'My argument'),
    // ));
    $this->addOptions(array(
      new sfCommandOption('application', null, sfCommandOption::PARAMETER_REQUIRED, 'The application name'),
      new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'prod'),
      new sfCommandOption('connection', null, sfCommandOption::PARAMETER_REQUIRED, 'The connection name', 'propel'),
      new sfCommandOption('server', null, sfCommandOption::PARAMETER_OPTIONAL, 'The server address.', 'zeus.eurotux.com'),
      new sfCommandOption('port', null, sfCommandOption::PARAMETER_OPTIONAL, 'The server\'s listening port', '25'),
      new sfCommandOption('security-type', null, sfCommandOption::PARAMETER_OPTIONAL, 'Security mode. Must be "noencryption" or "sslencryption".', 'noencryption'),
      new sfCommandOption('use-auth', null, sfCommandOption::PARAMETER_OPTIONAL, '1 if the server requires authentication, otherwize 0.', '0'),
      new sfCommandOption('username', null, sfCommandOption::PARAMETER_OPTIONAL, 'Username used for authentication. Required if use-auth setted to 1.', null),
      new sfCommandOption('password', null, sfCommandOption::PARAMETER_OPTIONAL, 'User password. Required if use-auth setted to 1.', null),
      // add your own options here
    ));

    $this->namespace        = 'etva';
    $this->name             = 'setSMTP';
    $this->briefDescription = 'Configure the SMTP used for email notifications';
    $this->detailedDescription = <<<EOF
The [etva:setSMTP|INFO] lets you to configure the SMTP server parameters.
Call it with:

  [php symfony etva:setSMTP|INFO]
EOF;
  }

  protected function execute($arguments = array(), $options = array())
  {   
    // initialize the database connection
    $databaseManager = new sfDatabaseManager($this->configuration);
    $con = $databaseManager->getDatabase($options['connection'])->getConnection();

    $context = sfContext::createInstance(sfProjectConfiguration::getApplicationConfiguration('app','dev',true));
    
    if($options['server'] !== null)
        EtvaSettingPeer::updateSetting(EtvaSettingPeer::_SMTP_SERVER_, $options['server']);
    if($options['port'] !== null)
        EtvaSettingPeer::updateSetting(EtvaSettingPeer::_SMTP_PORT_, $options['port']);
    if($options['security-type'] !== null)
        EtvaSettingPeer::updateSetting(EtvaSettingPeer::_SMTP_SECURITY_, $options['security-type']);
    if($options['use-auth'] !== null)
        EtvaSettingPeer::updateSetting(EtvaSettingPeer::_SMTP_USE_AUTH_, $options['use-auth']);
    if($options['username'] !== null)
        EtvaSettingPeer::updateSetting(EtvaSettingPeer::_SMTP_USERNAME_, $options['username']);
    if($options['password'] !== null)
        EtvaSettingPeer::updateSetting(EtvaSettingPeer::_SMTP_KEY_, $options['password']);
        
    return;
  }
}
