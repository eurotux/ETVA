<?php

class eventFlushlogTask extends etvaBaseTask
{
  /**
    * Overrides default timeout
    **/
    protected function getSigAlarmTimeout(){
      return 600; // 10 minutes 
    }

  protected function configure()
  {
    // // add your own arguments here
    // $this->addArguments(array(
    //   new sfCommandArgument('my_arg', sfCommandArgument::REQUIRED, 'My argument'),
    // ));
    parent::configure();

    $this->addOptions(array(
      new sfCommandOption('application', null, sfCommandOption::PARAMETER_REQUIRED, 'The application name'),
      new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'prod'),
      new sfCommandOption('connection', null, sfCommandOption::PARAMETER_REQUIRED, 'The connection name', 'propel'),
      // add your own options here
    ));

    $this->namespace        = 'event';
    $this->name             = 'flushlog';
    $this->briefDescription = 'Clean-up database records of system events';
    $this->detailedDescription = <<<EOF
The [event:flushlog|INFO] task does things.
Call it with:

  [php symfony event:flushlog|INFO]
EOF;
  }  

  protected function execute($arguments = array(), $options = array())
  {

    $context = sfContext::createInstance(sfProjectConfiguration::getApplicationConfiguration('app','dev',true));
    parent::execute($arguments, $options);

    // initialize the database connection
    $databaseManager = new sfDatabaseManager($this->configuration);
    $con = $databaseManager->getDatabase($options['connection'])->getConnection();

    // add your code here

    $this->log('[INFO] Flushing old data...'."\n");
    
    $eventlog_flush = EtvaSettingPeer::retrieveByPK('eventlog_flush');
    $flush_range = $eventlog_flush->getValue();    
    
    $con->beginTransaction();
    $affected = 0;

    try {
        $offset_date = date("c",time() - ($flush_range*24*60*60));        

        /*
         * get event records that have creation date outdated
         */

        $c = new Criteria();
        $c->add(EtvaEventPeer::CREATED_AT, $offset_date, Criteria::LESS_THAN);
        $affected = EtvaEventPeer::doDelete($c, $con);
                
        $con->commit();

        $message = sprintf('Events Log - %d Record(s) deleted after %d day offset', $affected, $flush_range);

        $context->getEventDispatcher()->notify(
            new sfEvent(sfConfig::get('config_acronym'), 'event.log',
                array('message' => $message)));

        $this->log('[INFO] '.$message);

    } catch (PropelException $e) {
        $con->rollBack();
        throw $e;
    }

    $logger = new sfFileLogger($context->getEventDispatcher(), array('file' => sfConfig::get("sf_log_dir").'/cron_status.log'));

    // log the message!
    $logger->log("[INFO] The events flush task ran!", 6);

  }
    

}
