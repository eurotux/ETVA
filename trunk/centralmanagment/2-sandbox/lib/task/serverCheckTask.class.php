<?php

class serverCheckTask extends sfBaseTask
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
      new sfCommandOption('request_id', null, sfCommandOption::PARAMETER_OPTIONAL, 'The request id'),
      new sfCommandOption('node', null, sfCommandOption::PARAMETER_OPTIONAL, 'The node where server should be started'),
      new sfCommandOption('check', null, sfCommandOption::PARAMETER_OPTIONAL, 'The check status'),
    ));

    $this->namespace        = 'server';
    $this->name             = 'check';
    $this->briefDescription = 'Check server status';
    $this->detailedDescription = <<<EOF
The [server:check|INFO] task does things.
Call it with:

  [php symfony server:check|INFO]
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

    $this->log("[INFO] Check status of server '$server'");

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

        /*if(!$etva_node){

            //notify event log
            $msg_i18n = Etva::makeNotifyLogMessage(sfConfig::get('config_acronym'),
                                                                    EtvaNodePeer::_ERR_NOTFOUND_ID_, array('id'=>$nid));
            $error = array('success'=>false,'agent'=>sfConfig::get('config_acronym'),'error'=>$msg_i18n,'info'=>$msg_i18n);

            $this->log("[ERROR] ".$error['error']);
            return $error;
        }

        $server_va = new EtvaServer_VA($etva_server);
        $response = $server_va->getGAInfo($etva_node);   // update GA Info
        */

        // TODO force check
        $response = array( 'success'=>true, 'response'=>'check ok', 'agent'=>sfConfig::get('config_acronym') );
        if( $etva_node ){
            $response['agent'] = $etva_node->getName();
        }

        if( $options['check'] )
        {
            if( !$options['request_id'] ) $options['request_id'] = UUID::generate(UUID::UUID_RANDOM, UUID::FMT_STRING);
            $response['_request_id'] = $options['request_id'];
            $response['_request_status'] = EtvaAsynchronousJob::PENDING;
            if( $etva_server->getVmState() == $options['check'] ){
                $response['_request_status'] = EtvaAsynchronousJob::FINISHED;

                $msg_ok_type = '';
                if( $options['check'] == EtvaServer::STATE_RUNNING ){
                    $msg_ok_type = EtvaServerPeer::_OK_START_;
                } elseif( $options['check'] == EtvaServer::STATE_STOP ){
                    $msg_ok_type = EtvaServerPeer::_OK_STOP_;
                }

                if( $msg_ok_type ){
                    $response['response'] = Etva::makeNotifyLogMessage($response['agent'],
                                                            $msg_ok_type, array('name'=>$etva_server->getName()),
                                                            null,array(),EtvaEventLogger::INFO);
                }

            }

            //$this->log("[DEBUG] status=".$response['_request_status']." state=".$etva_server->getVmState()." == ".$options['check']);
        }

        if($response['success']){
            $this->log("[INFO] ".$response['response']);
            //$this->log("[DEBUG ".print_r($response,true));
        } else {
            $this->log("[ERROR] ".$response['error']);
        }      
        return $response;
    }
  }
}
