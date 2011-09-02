<?php
/*
 * Task that invokes node soap request domain_stats.
 * Updates servers vmState
 */

class serverCheckvmsstateTask extends sfBaseTask
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
      // add your own options here
    ));

    $this->namespace        = 'server';
    $this->name             = 'check-vms-state';
    $this->briefDescription = '';
    $this->detailedDescription = <<<EOF
The [server:check-vms-state|INFO] task does things.
Call it with:

  [php symfony server:check-vms-state|INFO]
EOF;
  }



  protected function execute($arguments = array(), $options = array())
  {
    $context = sfContext::createInstance(sfProjectConfiguration::getApplicationConfiguration('app',$options['env'],true));
    // initialize the database connection
    $databaseManager = new sfDatabaseManager($this->configuration);
    $con = $databaseManager->getDatabase($options['connection'])->getConnection();

    // add your code here

    $this->log('Checking node(s) virtual machines state...'."\n");

    $nodes = EtvaNodePeer::doSelect(new Criteria());

    $affected = 0;
    foreach ($nodes as $node){

        $response = $node->soapSend('domains_stats');

        $success = $response['success'];
        if($success){
            $returned_data = $response['response'];

            foreach ($returned_data as $i => $server){
                $server_data = (array) $server;
                $etva_server = $node->retrieveServerByName($server_data['name']);
                if($etva_server){
                    $etva_server->setVmState($server_data['state']);
                    $etva_server->save();
                }

            }

        }else{
            $affected++;
            $errors[] = $response['error'];
        }
    }

    if($nodes)
    {
        $message = sprintf('%d Node(s) could not be checked for virtual machines state', $affected);

        if($affected > 0)
            $context->getEventDispatcher()->notify(
                new sfEvent('ETVA', 'event.log',
                    array('message' => $message,'priority'=>EtvaEventLogger::ERR)));
    }


    if(!empty($errors))
        $this->log( $message);


    $logger = new sfFileLogger($context->getEventDispatcher(), array('file' => sfConfig::get("sf_log_dir").'/cron_status.log'));

    // log the message!
    $logger->log("The check virtual machines state task ran!", 6);




  }

}
