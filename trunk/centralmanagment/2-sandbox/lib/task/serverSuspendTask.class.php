<?php

class serverSuspendTask extends sfBaseTask
{
  protected function configure()
  {
    // // add your own arguments here
    $this->addArguments(array(
      new sfCommandArgument('server', sfCommandArgument::REQUIRED, 'Server id'),
    ));

    $this->addOptions(array(
      new sfCommandOption('application', null, sfCommandOption::PARAMETER_REQUIRED, 'The application name'),
      new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'prod'),
      new sfCommandOption('connection', null, sfCommandOption::PARAMETER_REQUIRED, 'The connection name', 'propel'),
      // add your own options here
      new sfCommandOption('node', null, sfCommandOption::PARAMETER_OPTIONAL, 'The node where server should be started'),
    ));

    $this->namespace        = 'server';
    $this->name             = 'suspend';
    $this->briefDescription = 'suspend the server';
    $this->detailedDescription = <<<EOF
The [server:suspend|INFO] task does things.
Call it with:

  [php symfony server:suspend|INFO]
EOF;
  }

  protected function execute($arguments = array(), $options = array())
  {
    // Context
    $context = sfContext::createInstance(sfProjectConfiguration::getApplicationConfiguration('app',$options['env'],true));

    // initialize the database connection
    $databaseManager = new sfDatabaseManager($this->configuration);
    $connection = $databaseManager->getDatabase($options['connection'])->getConnection();

    // add your code here

    // server id
    $server = $arguments['server'];

    $this->log("[INFO] Suspend server with '$server'");

    $etva_server = EtvaServerPeer::retrieveByPK($server);
    if( !$etva_server ) $etva_server = EtvaServerPeer::retrieveByUuid($server);
    if( !$etva_server ) $etva_server = EtvaServerPeer::retrieveByName($server);

    if(!$etva_server){

        $msg_i18n = $context->getI18N()->__(EtvaServerPeer::_ERR_NOTFOUND_,array('name'=>$server));

        $error = array('success'=>false,'agent'=>sfConfig::get('config_acronym'),'error'=>$msg_i18n,'info'=>$msg_i18n);
        $this->log("[ERROR] ".$error['error']);
        return $error;
    } else {

        if( $nid = $options['node'] ){
            $etva_node = EtvaNodePeer::retrieveByPK($nid);
        } else {
            $etva_node = $etva_server->getEtvaNode();
        }

        if(!$etva_node){

            //notify event log
            $msg_i18n = Etva::makeNotifyLogMessage(sfConfig::get('config_acronym'),
                                                                    EtvaNodePeer::_ERR_NOTFOUND_ID_, array('id'=>$nid),
                                                                    EtvaServerPeer::_ERR_SUSPEND_, array('name'=>$server));
            $error = array('success'=>false,'agent'=>sfConfig::get('config_acronym'),'error'=>$msg_i18n,'info'=>$msg_i18n);

            $this->log("[ERROR] ".$error['error']);
            return $error;
        }

        $server_va = new EtvaServer_VA($etva_server);
        $response = $server_va->send_suspend($etva_node);

        if($response['success']){
            $this->log("[INFO] ".$response['response']);
        } else {
            $this->log("[ERROR] ".$response['error']);
        }      
        return $response;
    }
  }
}
