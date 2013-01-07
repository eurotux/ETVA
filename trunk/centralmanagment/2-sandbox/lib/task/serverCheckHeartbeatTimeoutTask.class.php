<?php
/*
 * Task that invokes node soap request domain_stats.
 * Updates servers vmState
 */

class serverCheckHeartbeatTimeoutTask extends etvaBaseTask
{

    /**
      * Overrides default timeout
      **/
    protected function getSigAlarmTimeout(){
        return 290; // less than 5 minutes 
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

    $this->namespace        = 'server';
    $this->name             = 'check-heartbeat-timeout';
    $this->briefDescription = '';
    $this->detailedDescription = <<<EOF
The [server:check-heartbeat-timeout|INFO] task does things.
Call it with:

  [php symfony server:check-heartbeat-timeout|INFO]
EOF;
  }



  protected function execute($arguments = array(), $options = array())
  {

    $context = sfContext::createInstance(sfProjectConfiguration::getApplicationConfiguration('app',$options['env'],true));
    parent::execute($arguments, $options);

    // initialize the database connection
    $databaseManager = new sfDatabaseManager($this->configuration);
    $con = $databaseManager->getDatabase($options['connection'])->getConnection();

    // add your code here

    $this->log('[INFO] Checking Servers heartbeat...'."\n");

    $servers_timeout = EtvaServerQuery::create()
                                ->filterByVmState(EtvaServer::RUNNING)
                                ->filterByGaState(EtvaServerPeer::_GA_RUNNING_)
                                ->filterByHasha(1)
                                ->where('hbtimeout>0 AND UNIX_TIMESTAMP(heartbeat) < (UNIX_TIMESTAMP(now()) - hbtimeout)')
                                ->useEtvaServerAssignQuery('ServerAssign','RIGHT JOIN')
                                    ->useEtvaNodeQuery()
                                        ->filterByState(EtvaNode::NODE_ACTIVE)  // only if node is active
                                    ->endUse()
                                ->endUse()
                                ->find();


    if( count($servers_timeout) > 0 ){
        foreach($servers_timeout as $etva_server){
            $message = sprintf(' The server %s with id %s get heartbeat timed out (last heartbeat at %s and heartbeat timeout %s secods)',$etva_server->getName(),$etva_server->getId(),$etva_server->getHeartbeat(),$etva_server->getHbtimeout());
            $this->log($message);

            // add log message
            Etva::makeNotifyLogMessage($this->name,$message);

            $etva_node = $etva_server->getEtvaNode();
            $server_va = new EtvaServer_VA($etva_server);

            $response_ga = $etva_server->getGAInfo($etva_node);
            if( !$response_ga['success'] ){
                $msg = sprintf(' Something wrong with node %s agent, can\'t get GA state of the server %s with id %s run.',$etva_node->getName(),$etva_server->getName(),$etva_server->getId());
                $this->log($msg);
                // add log message
                Etva::makeNotifyLogMessage($this->name,$msg);

                $etva_server->setGaState(EtvaServerPeer::_GA_STOPPED_); // mark GA as stopped
                $etva_server->save();
            } else {
                if( $reponse_ga['ga_state'] == EtvaServerPeer::_GA_RUNNING_ ){
                    $message_ga = sprintf(' But the explicit check GA state of the server %s with id %s run with success.',$etva_server->getName(),$etva_server->getId());
                    $this->log($message_ga);
                    // add log message
                    Etva::makeNotifyLogMessage($this->name,$message_ga,array(),null,array(),EtvaEventLogger::INFO);
                } else {    // go restart

                    $starttime = sfConfig::get('app_server_heartbeat_starttime');

                    $starttime_date = date("c",time() - $starttime);
                    if( $etva_server->getHblaststart() && ($etva_server->getHblaststart() > $starttime_date) ){
                        $msg = sprintf(' the server %s with id %s is in starttime.',$etva_server->getName(),$etva_server->getId());
                        $this->log($msg);
                        // add log message
                        Etva::makeNotifyLogMessage($this->name,$msg,array(),null,array(),EtvaEventLogger::INFO);
                    } else {
                        $last_nrestarts = $etva_server->getHbnrestarts();
                        if( $last_nrestarts >= sfConfig::get('app_server_heartbeat_number_of_restart') ){
                            $msg = sprintf(' the server %s with id %s exceed number of restart.',$etva_server->getName(),$etva_server->getId());
                            $this->log($msg);
                            // add log message
                            Etva::makeNotifyLogMessage($this->name,$msg);
                        } else {

                            $msg = sprintf(' the server %s with id %s is heartbeat out of date and will be restart.',$etva_server->getName(),$etva_server->getId());
                            $this->log($msg);
                            // add log message
                            Etva::makeNotifyLogMessage($this->name,$msg,array(),null,array(),EtvaEventLogger::INFO);

                            // force to stop
                            $response_stop = $server_va->send_stop($etva_node,array('force'=>1, 'destroy'=>1));

                            sleep(5);       // wait a few seconds

                            $response_start = $server_va->send_start($etva_node);

                            if( !$response_start['success'] ){  // start fail...
                                sleep(10);       // wait a few seconds
                                $response_start = $server_va->send_start($etva_node); // start again
                            }

                            $etva_server->resetHeartbeat(EtvaServerPeer::_GA_STOPPED_); // reset heartbeat and mark GA as stopped
                            $etva_server->setHbnrestarts($last_nrestarts+1);    // inc number of restart
                            $etva_server->save();
                        }
                    }
                }
            }
        }
    } else {
        $this->log("[INFO] No servers with heartbeat timed out.");
    }

    // log the message!
    $this->log("[INFO] The check servers heartbeat task ran!");
  }
}
