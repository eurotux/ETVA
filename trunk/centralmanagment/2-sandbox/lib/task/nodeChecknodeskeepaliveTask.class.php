<?php
/*
 * Task to perform state nodes check
 */
class nodeChecknodeskeepaliveTask extends sfBaseTask
{
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

    $this->namespace        = 'node';
    $this->name             = 'check-nodes-keepalive';
    $this->briefDescription = 'Checks nodes(virtAgents) connectivity';
    $this->detailedDescription = <<<EOF
The [node:check-nodes-keepalive|INFO] task does things.
Call it with:

  [php symfony node:check-nodes-keepalive|INFO]
EOF;
  }

  protected function execute($arguments = array(), $options = array())
  {
    $context = sfContext::createInstance(sfProjectConfiguration::getApplicationConfiguration('app','dev',true));
    // initialize the database connection
    $databaseManager = new sfDatabaseManager($this->configuration);
    $con = $databaseManager->getDatabase($options['connection'])->getConnection();

    // add your code here

    $this->log('Checking VirtAgents state...'."\n");

    $offset = sfConfig::get('app_node_keepalive_update') + sfConfig::get('app_node_keepalive_update_offset');
    $total_nodes = EtvaNodePeer::doCount(new Criteria());

    if($total_nodes > 0)
    {
        $con->beginTransaction();
        $affected = 0;

        try {
            $offset_date = date("c",time() - $offset);

            /*
             * get nodes that have last_keepalive field outdated
             */

            $c1 = new Criteria();
            $c1->add(EtvaNodePeer::LAST_KEEPALIVE, $offset_date, Criteria::LESS_THAN);
            //$c1->add(EtvaNodePeer::STATE, 0, Criteria::NOT_EQUAL);

            //update statement
            $c2 = new Criteria();
            $c2->add(EtvaNodePeer::STATE,0);

            $affected = BasePeer::doUpdate($c1, $c2, $con);

            $con->commit();

            $message = sprintf('%d Node(s) NOT received update in time offset of %d seconds', $affected, $offset);

            if($affected > 0){
                $context->getEventDispatcher()->notify(
                    new sfEvent('ETVA', 'event.log',
                        array('message' => $message,'priority'=>EtvaEventLogger::ERR)));
            }

            $this->log($message);

        } catch (PropelException $e) {
            $con->rollBack();
            throw $e;
        }

    }    

    $logger = new sfFileLogger($context->getEventDispatcher(), array('file' => sfConfig::get("sf_log_dir").'/cron_status.log'));

    // log the message!
    $logger->log("The check virtAgents task ran!", 6);




  }
  
  
  

}
