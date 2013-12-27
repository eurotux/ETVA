<?php

class serverDownloadbackupsnapshotTask extends etvaBaseTask
{
  protected function getSigAlarmTimeout(){
    return -1; // never
  }

  protected function configure()
  {
    parent::configure();

    // // add your own arguments here
    $this->addArguments(array(
      new sfCommandArgument('serverid', sfCommandArgument::REQUIRED, 'Provide server id'),
      new sfCommandArgument('filepath', sfCommandArgument::REQUIRED, 'path to file to save'),
      new sfCommandArgument('snapshot', sfCommandArgument::OPTIONAL, 'snapshot'),
      new sfCommandArgument('delete', sfCommandArgument::OPTIONAL, 'delete after download'),
      new sfCommandArgument('newsnapshot', sfCommandArgument::OPTIONAL, 'snapshot to create'),
      new sfCommandArgument('location', sfCommandArgument::OPTIONAL, 'location to put backup file'),
      new sfCommandArgument('do_not_generate_tar', sfCommandArgument::OPTIONAL, 'to do not generate tar')
    ));

    $this->addOptions(array(
      new sfCommandOption('application', null, sfCommandOption::PARAMETER_REQUIRED, 'The application name'),
      new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'prod'),
      new sfCommandOption('connection', null, sfCommandOption::PARAMETER_REQUIRED, 'The connection name', 'propel'),
      // add your own options here
    ));

    $this->namespace        = 'server';
    $this->name             = 'download-backup-snapshot';
    $this->briefDescription = 'Download server backup from snapshot';
    $this->detailedDescription = <<<EOF
The [server:download-backup-snapshot|INFO] task does things.
Call it with:

  [php symfony server:download-backup-snapshot|INFO]
EOF;
  }

  protected function execute($arguments = array(), $options = array())
  {
    $context = sfContext::createInstance(sfProjectConfiguration::getApplicationConfiguration('app',$options['env'],true));
    parent::execute($arguments, $options);

    // initialize the database connection
    $databaseManager = new sfDatabaseManager($this->configuration);
    $connection = $databaseManager->getDatabase($options['connection'])->getConnection();

    // add your code here

    $options_task_server_backup = array( // options
                                        'location'=>$arguments['location'],
                                        'filepath'=>$arguments['filepath']
                                    );

    if( $arguments['snapshot'] ){
        $options_task_server_backup['snapshot'] = $arguments['snapshot'];
    }
    if( $arguments['newsnapshot'] ){
        $options_task_server_backup['newsnapshot'] = $arguments['newsnapshot'];
    }
    if( $arguments['delete'] ){
        $options_task_server_backup['deletesnapshot'] = $arguments['delete'];
    }

    if( $arguments['location'] ){
        if( $arguments['do_not_generate_tar'] && ($arguments['do_not_generate_tar']!='false') ){
            $options_task_server_backup['do_not_generate_tar'] = true;
        }
    }

    $task_server_backup = new serverBackupTask($this->dispatcher, new sfFormatter());
    return $task_server_backup->run(
                                array( // arguments
                                    'serverid'=>$arguments['serverid']
                                ),
                                $options_task_server_backup
                            );
  }
}
