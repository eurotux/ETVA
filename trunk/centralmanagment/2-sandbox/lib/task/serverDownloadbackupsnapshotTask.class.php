<?php

class serverDownloadbackupsnapshotTask extends etvaBaseTask
{
  protected function getSigAlarmTimeout(){
    return -1; // never
  }

  protected function configure()
  {
    parent::configure();

    // // add your own arguments here
    $this->addArguments(array(
      new sfCommandArgument('serverid', sfCommandArgument::REQUIRED, 'Provide server id'),
      new sfCommandArgument('filepath', sfCommandArgument::REQUIRED, 'path to file to save'),
      new sfCommandArgument('snapshot', sfCommandArgument::OPTIONAL, 'snapshot'),
      new sfCommandArgument('delete', sfCommandArgument::OPTIONAL, 'delete after download'),
      new sfCommandArgument('newsnapshot', sfCommandArgument::OPTIONAL, 'snapshot to create'),
      new sfCommandArgument('location', sfCommandArgument::OPTIONAL, 'location to put backup file'),
      new sfCommandArgument('do_not_generate_tar', sfCommandArgument::OPTIONAL, 'to do not generate tar')
    ));

    $this->addOptions(array(
      new sfCommandOption('application', null, sfCommandOption::PARAMETER_REQUIRED, 'The application name'),
      new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'prod'),
      new sfCommandOption('connection', null, sfCommandOption::PARAMETER_REQUIRED, 'The connection name', 'propel'),
      // add your own options here
    ));

    $this->namespace        = 'server';
    $this->name             = 'download-backup-snapshot';
    $this->briefDescription = 'Download server backup from snapshot';
    $this->detailedDescription = <<<EOF
The [server:download-backup-snapshot|INFO] task does things.
Call it with:

  [php symfony server:download-backup-snapshot|INFO]
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

    $sid = $arguments['serverid']; 

    $etva_server = EtvaServerPeer::retrieveByPK($sid);          // try by id
    if( !$etva_server ) $etva_server = EtvaServerPeer::retrieveByUuid($sid);    // try by uuid
    if( !$etva_server ) $etva_server = EtvaServerPeer::retrieveByName($sid);    // try by name

    if(!$etva_server){
        $msg_i18n = sfContext::getInstance()->getI18N()->__(EtvaServerPeer::_ERR_NOTFOUND_ID_,array('%id%'=>$sid));
        // Error
        $this->log("[Error] $msg_i18n");
        return -1;
    } else {
        
        $newsnapshot = $arguments['newsnapshot'];
        $snapshot = $arguments['snapshot'];

        if(!$etva_server->getHasSnapshots() && !$snapshot && !$newsnapshot && ($etva_server->getVmState() != 'stop') && ($etva_server->getVmState() != 'notrunning') ){
            // Error is running
            $this->log("[Error] Can't create backup file of running server without snapshots");
            return -101;
        }
        
        $etva_node = $etva_server->getEtvaNode();

        if( !$etva_node ){
            // Error is running
            $this->log("[Error] The server is not assigned to any node");
            return -1010;
        }
        
        $server_va = new EtvaServer_VA($etva_server);

        if( !$etva_server->getHasSnapshots() || $newsnapshot ){
            $response = $server_va->create_snapshot($etva_node,$newsnapshot);
            if( !$response['success'] ){
                $msg_i18n = $response['info'];
                $this->log("[Error] Can't create snapshot: $msg_i18n");
                // Error
                return -110;
            }
        }

        $url = "http://".$etva_node->getIp();
        $request_body = "uuid=".$etva_server->getUuid();

        if( $snapshot ){
            $request_body .= "&snapshot=$snapshot";
        }

        if( $arguments['location'] ){
            if( $arguments['do_not_generate_tar'] && ($arguments['do_not_generate_tar']!='false') ){ // do not generate tar 
                $request_body .= "&_do_not_generate_tar_=1";
            }
            $request_body .= "&location=".$arguments['location'];
        }

        $filename = $etva_server->getName().".tar";
        
        $port = $etva_node->getPort();
        if($port) $url.=":".$port;        
        $url.="/vm_backup_snapshot_may_fork";
        
        /*
         * get response stream data
         */
        $path = $arguments['filepath'];
        if( $path != 'STDOUT' ) $fp = fopen($path, 'w');

        $ovf_curl = curl_init($url);

        if( $path != 'STDOUT' ) curl_setopt($ovf_curl, CURLOPT_FILE, $fp);

        curl_setopt($ovf_curl, CURLOPT_POST, true);
        curl_setopt($ovf_curl, CURLOPT_POSTFIELDS, $request_body);

        $data = curl_exec($ovf_curl);
        if( curl_getinfo($ovf_curl,CURLINFO_HTTP_CODE)==500){
            // Error;
            $this->log("[Error] Can't download backup, we get STATUS 500: $data");
            return -111;
        }

        curl_close($ovf_curl);

        if( $path != 'STDOUT' ) fclose($fp);

        if( $arguments['delete'] && ($arguments['delete']!='false') ){ // delete after
            if( $newsnapshot ){
                $server_va->remove_snapshot($etva_node,$newsnapshot);
            } else if( $snapshot ){
                $server_va->remove_snapshot($etva_node,$snapshot);
            }
        }

        return 0;

    }

  }
}
