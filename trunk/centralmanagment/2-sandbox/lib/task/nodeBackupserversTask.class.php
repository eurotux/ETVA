<?php

class nodeBackupserversTask extends rcParallelTask
{
  // queue of response
  protected $queueResourceResponse;

  /**
   * The class destructor.
   */
  public function  __destruct() {
    parent::__destruct();

    if ($this->iAmParent()) {

        $desiredType = 1;
        $option_receive = MSG_IPC_NOWAIT;

        $stats = msg_stat_queue($this->queueResourceResponse);
        $queueMessageSize = $stats['msg_qbytes'];

        // the Report
        $messageReport = '';

        // receive the report
        $stats = msg_stat_queue($this->queueResourceResponse);
        while($stats['msg_qnum']){
        //for($i=0;count($all_servers);$i++){
            $status = msg_receive($this->queueResourceResponse, $desiredType, $type, $queueMessageSize, $mixed, true, $option_receive);
            if( $status == true ){
                $messageReport .= $mixed['message'];
                if( $mixed['return'] < 0 ){
                    // check if server counldn't do the backups because shutdown doesn't work
                    if( $mixed['error'] && ($mixed['error']['error']['error']=='_ERR_VM_BACKUP_STILL_RUNNING_') ){
                        $etva_server = $mixed['server'];
                        $this->log("[WARN] Receive error that VM '".$etva_server->getName()."' couldn't make backup beacuse is still running, so i will try start again...");
                        $etva_node = $etva_server->getEtvaNode();
                        if( $etva_node ){
                            $res_start = $etva_node->soapSend(EtvaServer_VA::SERVER_START,array('uuid'=>$etva_server->getUuid(),'name'=>$etva_server->getName()));
                            $this->log("[WARN] Start '".$etva_server->getName()."' VM and receive the following message: ".print_r($res_start,true));
                        }
                    }
                }
            } else {
                $err_m = "[ERROR] Receive error when wait for response... ".print_r($err,true);
                $messageReport .= $err_m . "\r\n";
                $this->log($err_m);
            }
            $stats = msg_stat_queue($this->queueResourceResponse);
        }
        msg_remove_queue($this->queueResourceResponse);

        //$this->log($messageReport);
        $this->sendReport($messageReport);
    }
  }
  protected function getLogName($extension){
      switch($extension){
          case '.alert':
              $file = sfConfig::get('app_cron_alert_dir');
              break;
          case '.log':
              $file = sfConfig::get('app_cron_log_dir');
              break;
          default:
              $file = sfConfig::get('app_cron_log_dir');
              break;
      }
      $file .= '/';
      $file .= get_class($this);
      $file .= $extension;
      return $file;
  }
  protected function redefineStdOut(){
      $file = $this->getLogName('.log');
      $this->log($file);
      $file_logger = new sfFileLogger($this->dispatcher, array(
          'file' => $file
      ));

      $this->dispatcher->connect('command.log', array($file_logger, 'listenToLogEvent'));
  }

  protected function configure()
  {
    // // add your own arguments here
    $this->addArguments(array(
      new sfCommandArgument('location', sfCommandArgument::REQUIRED, 'location where backup files will be saved'),
    ));

    $this->addOptions(array(
      new sfCommandOption('application', null, sfCommandOption::PARAMETER_REQUIRED, 'The application name'),
      new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'prod'),
      new sfCommandOption('connection', null, sfCommandOption::PARAMETER_REQUIRED, 'The connection name', 'propel'),
      // add your own options here
      new sfCommandOption('processes', null, sfCommandOption::PARAMETER_REQUIRED, 'Number of processes to handle backups', 1),
      new sfCommandOption('exclude', null, sfCommandOption::PARAMETER_OPTIONAL, 'Exclude servers from backup by comma separated'),
      new sfCommandOption('filter', null, sfCommandOption::PARAMETER_OPTIONAL, 'Filter of servers to backup by comma separated'),
      new sfCommandOption('delete_backups_n_days', null, sfCommandOption::PARAMETER_OPTIONAL, 'Delete backups of servers with n days',2),
    ));

    $this->namespace        = 'node';
    $this->name             = 'backup-servers';
    $this->briefDescription = 'Backup all servers of all nodes';
    $this->detailedDescription = <<<EOF
The [node:backup-servers|INFO] task does things.
Call it with:

  [php symfony node:backup-servers|INFO]
EOF;
  }

  protected function sendReport($message){
    $from = EtvaSettingPeer::retrieveByPK(EtvaSettingPeer::_ALERT_EMAIL_FROM_)->getValue();
    if( !$from ) $from = 'nuxis@eurotux.com';
    $to = EtvaSettingPeer::retrieveByPK(EtvaSettingPeer::_ALERT_EMAIL_)->getValue();
    if( !$to ) $to = 'tec@eurotux.com';
    $subject_prefix = EtvaSettingPeer::retrieveByPK(EtvaSettingPeer::_ALERT_SUBJECT_PREFIX_)->getValue();
    if( !$subject_prefix ) $subject_prefix = 'Nuxis -';

    $subject = $subject_prefix . " " . "Backup Servers report";
    $headers = "From: $from\r\n";
    mail($to, $subject, $message, $headers);

    $this->log("[INFO] The report was sent to '$to' with following subject '$subject'.\n");
  }
  protected function execute($arguments = array(), $options = array())
  {
    // number of workers
    $n_childs = $options['processes']-1;
    // Start n child
    $this->startChildren($n_childs);

    $context = sfContext::createInstance(sfProjectConfiguration::getApplicationConfiguration('app',$options['env'],true));

    $this->redefineStdOut();

    // initialize the database connection
    $databaseManager = new sfDatabaseManager($this->configuration);
    $connection = $databaseManager->getDatabase($options['connection'])->getConnection();

    // add your code here

    // queue of response
    $this->queueResourceResponse = msg_get_queue(ftok(__FILE__, 'X'));

    $backup_location = $arguments['location'];
    
    if( $this->iAmParent() ){

        $this->log('[INFO] Get all nodes with VirtAgent activated...'."\n");

        $query = EtvaServerQuery::create();

        // filter servers
        if( $options['filter'] ){
            $fil_arr = explode(',',$options['filter']);
            foreach($fil_arr as $sname){
                $query->addOr(EtvaServerPeer::NAME,$sname,Criteria::EQUAL);
            }
        }
        // exclude servers
        if( $options['exclude'] ){
            $exc_arr = explode(',',$options['exclude']);
            foreach($exc_arr as $sname){
                $query->addAnd(EtvaServerPeer::NAME,$sname,Criteria::NOT_EQUAL);
            }
        }

        $all_servers = $query->find();

        foreach($all_servers as $server){
            // add server to queue to process backup
            $this->addToQueue($server);
        }

        // Wait until queue is consume
        $this->waitForEmptyQueue();

    } else {
        $msgtype_send = 1;

        // Child process
        while (($server = $this->getFromQueue())) {
            $task_server_backup = new serverBackupTask($this->dispatcher, new sfFormatter());
            $res = $task_server_backup->run(
                                        array( // arguments
                                            'serverid'=>$server->getId()
                                        ),
                                        array( // options
                                            'location'=>$backup_location,
                                            'shutdown'=>'true',
                                            'do_not_generate_tar'=>'true',
                                            'delete_backups_n_days'=>$options['delete_backups_n_days']
                                        )
                                    );
            $error = $task_server_backup->getLastError();
            $message = $task_server_backup->getReport();
            /*$res = 0;
            $message = "[INFO] Backup ".$server->getName()." VM successfully.\n";
            $error = array('message'=>$message);
            $this->log($message);*/
            
            // send report to the parent
            $msgObj = array('return'=>$res,'message'=>$message,'error'=>$error,'server'=>$server);
            msg_send($this->queueResourceResponse, $msgtype_send, $msgObj);
        }
    }
  }
}
