<?php

class serverStartTask extends sfBaseTask
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
      new sfCommandOption('assign', null, sfCommandOption::PARAMETER_OPTIONAL, 'Force to assign to the node'),
    ));

    $this->namespace        = 'server';
    $this->name             = 'start';
    $this->briefDescription = 'Start the server';
    $this->detailedDescription = <<<EOF
The [server:start|INFO] task does things.
Call it with:

  [php symfony server:start|INFO]
EOF;
  }

  public function dependsOnIt(EtvaAsynchronousJob $j1, EtvaAsynchronousJob $j2)
  {
    if( $j2->getTasknamespace() == 'logicalvolume' )   // logicalvolume operations
    {
        $j2_args = (array) json_decode($j1->getArguments());
        $j2_opts = (array) json_decode($j1->getOptions());
        $etva_node = EtvaNodePeer::getOrElectNodeFromArray(array_merge($j2_opts,$j2_args));
        if( $etva_node )
        {
            // original logical volume
            $original_lv = $j2_args['original'];
            $original_vg = $j2_opts['original-volumegroup'];

            // find orignal logical volume
            $etva_original_lv = $etva_node->retrieveLogicalvolumeByAny($original_lv,$original_vg);
            if( $etva_original_lv )
            {
                $j1_args = (array) json_decode($j1->getArguments());
                $lv_servers = $etva_original_lv->getServers();  // get servers where logical volume is attached
                foreach( $lv_servers as $srv )
                {
                    if( ($srv->getId() == $j1_args['server']) ||        // if the server is the same
                            ($srv->getUuid() == $j1_args['server']) ||
                            ($srv->getName() == $j1_args['server']) )
                    {
                        return true;                            // depends on it
                    }
                }
            }
        }
    }
    return false;
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

    $this->log("[INFO] Start server with '$server'");

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
            if(!$etva_node = EtvaNodePeer::retrieveByPK($nid)){

                //notify event log
                $msg_i18n = Etva::makeNotifyLogMessage(sfConfig::get('config_acronym'),
                                                                        EtvaNodePeer::_ERR_NOTFOUND_ID_, array('id'=>$nid),
                                                                        EtvaServerPeer::_ERR_START_, array('name'=>$server));
                $error = array('success'=>false,'agent'=>sfConfig::get('config_acronym'),'error'=>$msg_i18n,'info'=>$msg_i18n);

                $this->log("[ERROR] ".$error['error']);
                return $error;
            }
        } else {

            // get list of nodes that server can assign
            $nodes_toassign = $etva_server->listNodesAssignTo();

            if( count($nodes_toassign) ){
                $etva_node = $nodes_toassign[0];    // get first
            } else {

                //notify event log
                $msg_i18n = Etva::makeNotifyLogMessage(sfConfig::get('config_acronym'),
                                                                        EtvaServerPeer::_ERR_NO_NODE_TO_ASSIGN_, array(),
                                                                        EtvaServerPeer::_ERR_START_, array('name'=>$server));
                $error = array('success'=>false,'agent'=>sfConfig::get('config_acronym'),'error'=>$msg_i18n,'info'=>$msg_i18n);

                $this->log("[ERROR] ".$error['error']);
                return $error;
            }
        }

        $server_va = new EtvaServer_VA($etva_server);

        if( $options['assign'] || $etva_server->getUnassigned() ){

            // Assign server to node
            $response = $server_va->send_assign($etva_node);

            if(!$response['success']){

                $this->log("[ERROR] ".$response['error']);
                return $response;
            }
        }

        // start server
        $response = $server_va->send_start($etva_node);

        if($response['success']){

            // update GA Info
            $response_ga = $server_va->getGAInfo($etva_node);

            $this->log("[INFO] ".$response['response']);
        } else {
            $this->log("[ERROR] ".$response['error']);
        }      
        return $response;
    }
  }
}
