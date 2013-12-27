<?php

class serverBackupTask extends sfBaseTask
{
  private $report = '';
  private $errors = array();

  protected function configure()
  {
    // // add your own arguments here
    $this->addArguments(array(
      new sfCommandArgument('serverid', sfCommandArgument::REQUIRED, 'Provide server id'),
    ));

    $this->addOptions(array(
      new sfCommandOption('application', null, sfCommandOption::PARAMETER_REQUIRED, 'The application name'),
      new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'prod'),
      new sfCommandOption('connection', null, sfCommandOption::PARAMETER_REQUIRED, 'The connection name', 'propel'),
      // add your own options here
      new sfCommandOption('location', null, sfCommandOption::PARAMETER_OPTIONAL, 'location to put backup file'),
      new sfCommandOption('filepath', null, sfCommandOption::PARAMETER_OPTIONAL, 'path of file to save'),
      new sfCommandOption('snapshot', null, sfCommandOption::PARAMETER_OPTIONAL, 'specify the snapshot for backup'),
      new sfCommandOption('newsnapshot', null, sfCommandOption::PARAMETER_OPTIONAL, 'create snapshot for backup'),
      new sfCommandOption('deletesnapshot', null, sfCommandOption::PARAMETER_OPTIONAL, 'delete snapshot after backup'),
      new sfCommandOption('shutdown', null, sfCommandOption::PARAMETER_OPTIONAL, 'shutdown server if don\'t have snapshots'),
      new sfCommandOption('do_not_generate_tar', null, sfCommandOption::PARAMETER_OPTIONAL, 'do not generate tar'),
      new sfCommandOption('delete_backups_n_days', null, sfCommandOption::PARAMETER_OPTIONAL, 'delete backups with n days'),
      new sfCommandOption('sendreport', null, sfCommandOption::PARAMETER_OPTIONAL, 'Send report at end of execution')
    ));

    $this->namespace        = 'server';
    $this->name             = 'backup';
    $this->briefDescription = 'Backup the server';
    $this->detailedDescription = <<<EOF
The [server:backup|INFO] task does things.
Call it with:

  [php symfony server:backup|INFO]
EOF;
  }

  protected function sendReport(){
    $message = $this->report;
    $from = EtvaSettingPeer::retrieveByPK(EtvaSettingPeer::_ALERT_EMAIL_FROM_)->getValue();
    if( !$from ) $from = 'nuxis@eurotux.com';
    $to = EtvaSettingPeer::retrieveByPK(EtvaSettingPeer::_ALERT_EMAIL_)->getValue();
    if( !$to ) $to = 'tec@eurotux.com';
    $subject_prefix = EtvaSettingPeer::retrieveByPK(EtvaSettingPeer::_ALERT_SUBJECT_PREFIX_)->getValue();
    if( !$subject_prefix ) $subject_prefix = 'Nuxis -';

    $subject = $subject_prefix . " " . "Backup Server report";
    $headers = "From: $from\r\n";
    mail($to, $subject, $message, $headers);

    $this->log("[INFO] The report was sent to '$to' with following subject '$subject'.\n");

  }

  public function getReport(){
    return $this->report;
  }
  public function getErrors(){
    return $this->errors;
  }
  public function getLastError(){
    $len = count($this->errors) ? count($this->errors)-1 : 0;
    return $this->errors[$len];
  }

  protected function call_backup($etva_node, $params = array(),$filepath = null)
  {
    
    if( $filepath )
    {

        $url = "http://".$etva_node->getIp();

        $port = $etva_node->getPort();
        if($port) $url.=":".$port;        
        $url.="/vm_backup";

        $request_body = "uuid=".$params['uuid'];

        if( $params['snapshot'] ){
            $request_body .= "&snapshot=".$params['snapshot'];
        }

        if( $params['location'] ){
            if( $params['do_not_generate_tar'] && ($params['do_not_generate_tar']!='false') ){ // do not generate tar 
                $request_body .= "&do_not_generate_tar=1";
            }
            $request_body .= "&location=".$params['location'];
        }

        if( $params['shutdown'] ){
            $request_body .= "&shutdown=1";
        }

        $filename = $params['name'].".tar";
        
        /*
         * get response stream data
         */
        $ovf_curl = new ovfcURL($url);
        $ovf_curl->post($request_body);
        $ovf_curl->setFilename($filename);

        // set file to write output
        if( $filepath != 'STDOUT' ){
            $ovf_curl->setOutputFile($filepath);
        }

        $ovf_curl->exec();

        if($ovf_curl->getStatus()==500){
            // Error;
            $err_m = "[ERROR] Server '".$etva_server->getName()."' can't download backup, we get STATUS 500: $data";
            $this->report .= $err_m . "\r\n";
            $this->log($err_m);
            array_push($this->errors,array('message'=>$err_m));
            return -111;
        }

    } else {
        $res_backup = $etva_node->soapSend("vm_backup",$params);

        if( !$res_backup['success'] ){
            $err_m = '[ERROR] Backup of '.$params['name'].' VM: '.$res_backup['info'];
            $this->log($err_m."\n");
            $this->report .= $err_m . "\r\n";
            array_push($this->errors,array('message'=>$err_m,'error'=>$res_backup));
            return -111;
        }
    }
    // backup with success
    $info_m = '[INFO] Backup '.$params['name'].' VM successfully.';
    $this->report .= $info_m . "\r\n";
    $this->log($info_m."\n");

    return 0;
  }

  protected function _execute($arguments = array(), $options = array())
  {
    // get server id
    $sid = $arguments['serverid']; 

    $etva_server = EtvaServerPeer::retrieveByPK($sid);          // try by id
    if( !$etva_server ) $etva_server = EtvaServerPeer::retrieveByUuid($sid);    // try by uuid
    if( !$etva_server ) $etva_server = EtvaServerPeer::retrieveByName($sid);    // try by name

    if(!$etva_server){
        $msg_i18n = sfContext::getInstance()->getI18N()->__(EtvaServerPeer::_ERR_NOTFOUND_ID_,array('%id%'=>$sid));
        // Error
        $err_m = "[ERROR] $msg_i18n";
        $this->report .= $err_m . "\r\n";
        $this->log($err_m);
        array_push($this->errors,array('message'=>$err_m));
        return -1;
    } else {
        $etva_node = $etva_server->getEtvaNode();
        if( $etva_node ){
            $info_m = '[INFO] Backup of the server '.$etva_server->getName().' assign to node '.$etva_node->getName().'...';
            $this->log($info_m."\n");

            $params = array('name'=>$etva_server->getName(),'uuid'=>$etva_server->getUuid());

            if( $options['location'] ){     // location set
                $params['location'] = $options['location'];
                if( $options['do_not_generate_tar'] && ($options['do_not_generate_tar']!='false') ){ // do not generate tar 
                    $params['do_not_generate_tar'] = true;
                }
            }

            if( $options['shutdown'] ){     // set shutdown
                $params['shutdown'] = true;
            }

            if( $options['delete_backups_n_days'] ){
                $params['clean_old_backups'] = true;
                $n_days = intval($options['delete_backups_n_days']);
                if( $n_days ){
                    $params['n_days'] = $n_days;
                }
            }

            if(!$etva_server->getHasSnapshots() && !$options['shutdown'] && !$options['snapshot'] && !$options['newsnapshot'] && ($etva_server->getVmState() != 'stop') && ($etva_server->getVmState() != 'notrunning') ){
                // Error is running
                $err_m = "[ERROR] Server '".$etva_server->getName()."' can't create backup file of running server without snapshots";
                $this->report .= $err_m . "\r\n";
                $this->log($err_m);
                array_push($this->errors,array('message'=>$err_m));
                return -101;
            }
            
            $server_va = new EtvaServer_VA($etva_server);

            // use snapshot for backup
            if( $options['snapshot'] ){     // set snapshot
                $params['snapshot'] = $options['snapshot'];
            }

            if( $options['newsnapshot'] || !$options['shutdown'] ){
                // create new snapshot
                $newsnapshot = $options['newsnapshot'];
                if( !$etva_server->getHasSnapshots() || $newsnapshot ){
                    $response = $server_va->create_snapshot($etva_node,$newsnapshot);
                    if( !$response['success'] ){
                        $msg_i18n = $response['info'];
                        $err_m = "[ERROR] Server '".$etva_server->getName()."' can't create snapshot: $msg_i18n";
                        $this->report .= $err_m . "\r\n";
                        $this->log($err_m);
                        array_push($this->errors,array('message'=>$err_m,'error'=>$response));
                        // Error
                        return -110;
                    }
                    $params['snapshot'] = $newsnapshot;
                }
            }

            // call backup
            $res = $this->call_backup($etva_node,$params,$options['filepath']);

            if( $res < 0 ){
                return $res;
            }

            if( $options['deletesnapshot'] && ($options['deletesnapshot']!='false') ){ // delete snapshot after
                if( $newsnapshot ){
                    $server_va->remove_snapshot($etva_node,$newsnapshot);
                } else if( $snapshot ){
                    $server_va->remove_snapshot($etva_node,$snapshot);
                }
            }

            return 0;
        } else {
            $warn_m = '[WARN] '.$etva_server->getName().' VM is not assigned and will be ignored.';
            $this->report .= $warn_m . "\r\n";
            $this->log($warn_m."\n");
            return -1010;
        }
    }
  }
  protected function execute($arguments = array(), $options = array())
  {
    $context = sfContext::createInstance(sfProjectConfiguration::getApplicationConfiguration('app',$options['env'],true));

    // initialize the database connection
    $databaseManager = new sfDatabaseManager($this->configuration);
    $connection = $databaseManager->getDatabase($options['connection'])->getConnection();

    // add your code here

    $res = $this->_execute($arguments,$options);

    // send report (by default is disabled)
    if( $options['sendreport'] ){
        $this->sendReport();
    }
    return $res;
  }
}
