<?php

class etvaUpdateGuestsInfoTask extends etvaBaseTask
{
  protected function configure()
  {
    parent::configure();
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

    $this->namespace        = 'etva';
    $this->name             = 'update-guests-info';
    $this->briefDescription = 'Updates CM info about nodes servers (Guest Agent info).';
    $this->detailedDescription = <<<EOF
The [etva:update-guests-info|INFO] task updates CM info about nodes servers. Contacts all existing guest agents.
Call it with:

  [php symfony etva:update-guests-info|INFO]
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
    $this->log('Refreshing Guest Agents Info...'."\n");

    $nodes = EtvaNodeQuery::create()
                    ->filterByState(EtvaNode::NODE_ACTIVE)
                    ->find();

    foreach($nodes as $node){        
        $node_va = new EtvaNode_VA($node);
        $this->log('Collecting info for node: '.$node->getName()."\n");
        $bulk_response_gas = $node_va->send_get_gas_info();
        //$this->log('The following servers info were updated: '.implode('; ', $bulk_response_gas));
        $message = 'The following servers info were updated: '.implode('; ', $bulk_response_gas);
        $this->log($message);
        $context->getEventDispatcher()->notify(
            new sfEvent($node->getName(), 'event.log',
                array('message' => $message,'priority'=>EtvaEventLogger::INFO)));

    }
  }
}
