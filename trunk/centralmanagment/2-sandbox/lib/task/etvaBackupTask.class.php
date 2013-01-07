<?php

class etvaBackupTask extends etvaBaseTask
{
  protected function getSigAlarmTimeout(){
    return 300; // less than 5minutes
  }

  protected function configure()
  {
    // // add your own arguments here
    // $this->addArguments(array(
    //   new sfCommandArgument('my_arg', sfCommandArgument::REQUIRED, 'My argument'),
    // ));

    $this->addOptions(array(
      new sfCommandOption('application', null, sfCommandOption::PARAMETER_REQUIRED, 'The application name'),
      new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'dev'),
      new sfCommandOption('connection', null, sfCommandOption::PARAMETER_REQUIRED, 'The connection name', 'propel'),
      // add your own options here
    ));

    $this->namespace        = 'etva';
    $this->name             = 'backup';
    $this->briefDescription = '';
    $this->detailedDescription = <<<EOF
The [etva:backup|INFO] task do appliance backup.
Call it with:

  [php symfony etva:backup|INFO]
EOF;
  }

  protected function execute($arguments = array(), $options = array())
  {
    $context = sfContext::createInstance(sfProjectConfiguration::getApplicationConfiguration('app',$options['env'],true));

    // initialize the database connection
    $databaseManager = new sfDatabaseManager($this->configuration);
    $connection = $databaseManager->getDatabase($options['connection'])->getConnection();

    $apli = new Appliance();
    $serial = $apli->get_serial_number();

    $this->log('[INFO] Appliance backup...'."\n");

    if($serial){
        $result = $apli->backup(true);

        if(!$result['success']){
            if($result['action'] == Appliance::LOGIN_BACKUP) $result['txt'] = 'Could not login!';
            if($result['action'] == Appliance::DB_BACKUP) $result['txt'] = 'DB backup error...';
            if($result['action'] == Appliance::MA_BACKUP) $result['txt'] = 'MA backup error...';
            $message = 'The backup failed, reason: ' . $result['txt'];
            $context->getEventDispatcher()->notify(
                new sfEvent($this->name, 'event.log',
                    array('message' => $message,'priority'=>EtvaEventLogger::ERR)));
            $this->log('[ERR] '.$message."\n");
        } else {
            $message = 'The backup process run with success.';
            $context->getEventDispatcher()->notify(
                new sfEvent($this->name, 'event.log',
                    array('message' => $message,'priority'=>EtvaEventLogger::INFO)));
            $this->log('[INFO] '.$message."\n");
        }                            
    } else {
        $this->log('[INFO] Could not be possible to do the backup because the appliance is not registered!'."\n");
    }

  }
}
