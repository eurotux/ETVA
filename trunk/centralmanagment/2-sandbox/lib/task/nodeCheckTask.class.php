<?php

class nodeCheckTask extends sfBaseTask
{
  protected function configure()
  {
    // // add your own arguments here
    $this->addArguments(array(
      new sfCommandArgument('id', sfCommandArgument::REQUIRED, 'Node id'),
    ));

    $this->addOptions(array(
      new sfCommandOption('application', null, sfCommandOption::PARAMETER_REQUIRED, 'The application name'),
      new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'prod'),
      new sfCommandOption('connection', null, sfCommandOption::PARAMETER_REQUIRED, 'The connection name', 'propel'),
      // add your own options here
    ));

    $this->namespace        = 'node';
    $this->name             = 'check';
    $this->briefDescription = 'Check node state';
    $this->detailedDescription = <<<EOF
The [node:check|INFO] task does things.
Call it with:

  [php symfony node:check|INFO]
EOF;
  }

  protected function execute($arguments = array(), $options = array())
  {
    $context = sfContext::createInstance(sfProjectConfiguration::getApplicationConfiguration('app',$options['env'],true));

    // initialize the database connection
    $databaseManager = new sfDatabaseManager($this->configuration);
    $connection = $databaseManager->getDatabase($options['connection'])->getConnection();

    // add your code here

    $nid = $arguments['id'];

    $this->log("[INFO] node check id=$nid");

    if(!$etva_node = EtvaNodePeer::retrieveByPK($nid)){

        $msg_i18n = $context->getI18N()->__(EtvaNodePeer::_ERR_NOTFOUND_ID_,array('%id%'=>$nid));

        $error = array('success'=>false,'agent'=>sfConfig::get('config_acronym'),'error'=>$msg_i18n,'info'=>$msg_i18n);
        $this->log("[ERROR] ".$error['error']);
        return $error;
    } else {
        $etva_node_va = new EtvaNode_VA($etva_node);
        $response = $etva_node_va->checkState();
        if( !$response['success'] ){
            $this->log("[ERROR] ".$response['error']);
        } else {
            $this->log("[INFO] ".$response['response']);
        }
        return $response;
    }
  }
}
