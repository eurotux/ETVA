<?php

class serverChecksnapshotsusageTask extends etvaBaseTask
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
    $this->name             = 'check-snapshots-usage';
    $this->briefDescription = '';
    $this->detailedDescription = <<<EOF
The [server:check-snapshots-usage|INFO] task does things.
Call it with:

  [php symfony server:check-snapshots-usage|INFO]
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

    // Update nodes logical volumes 
    $this->log('[INFO] Update nodes logical volumes info...'."\n");
    $list_nodes = EtvaNodeQuery::create()
                                ->find();
    foreach($list_nodes as $node){
        $lv_va = new EtvaLogicalvolume_VA();
        $lv_errors = $lv_va->send_update($node,false,false);
    }

    // Check servers with snapshots
    $this->log('[INFO] Checking Servers with snapshots...'."\n");

    $errors_arr = array();
    $list_servers = EtvaServerQuery::create()
                                ->find();
    foreach($list_servers as $server){
        $server_lvs = $server->getEtvaLogicalvolumes();
        foreach( $server_lvs as $lv ){
            if( $lv->getPerUsageSnapshots() >= EtvaLogicalvolume::PER_USAGESNAPSHOTS_CRITICAL ){
                $this->log('[ERROR] Logical volume \''.$lv->getLvdevice().'\' of server \''. $server->getName() . '\' is in CRITICAL with '.sprintf('%g%%',round($lv->getPerUsageSnapshots()*100)).' of usage by snapshots ');
                array_push($errors_arr, array('server_name'=>$server->getName(),'lvdevice'=>$lv->getLvdevice(),'per_usage_snapshots'=>$lv->getPerUsageSnapshots(),'status_id'=>EtvaServer::USAGESNAPSHOTS_STATUS_CRITICAL,'status_str'=>EtvaServer::USAGESNAPSHOTS_STATUS_CRITICAL_STR));
            } else if(  $lv->getPerUsageSnapshots() >= EtvaLogicalvolume::PER_USAGESNAPSHOTS_WARNING ){
                $this->log('[ERROR] Logical volume \''.$lv->getLvdevice().'\' of server \''. $server->getName() . '\' is in WARNING with '.sprintf('%g%%',round($lv->getPerUsageSnapshots()*100)).' of usage by snapshots ');
                array_push($errors_arr, array('server_name'=>$server->getName(),'lvdevice'=>$lv->getLvdevice(),'per_usage_snapshots'=>$lv->getPerUsageSnapshots(),'status_id'=>EtvaServer::USAGESNAPSHOTS_STATUS_WARNING,'status_str'=>EtvaServer::USAGESNAPSHOTS_STATUS_WARNING_STR));
            } else {
                $this->log('[INFO] Logical volume \''.$lv->getLvdevice().'\' of server \''. $server->getName() . '\' is in NORMAL with '.sprintf('%g%%',round($lv->getPerUsageSnapshots()*100)).' of usage by snapshots ');
                #array_push($errors_arr, array('server_name'=>$server->getName(),'lvdevice'=>$lv->getLvdevice(),'per_usage_snapshots'=>$lv->getPerUsageSnapshots(),'status_id'=>EtvaServer::USAGESNAPSHOTS_STATUS_CRITICAL,'status_str'=>EtvaServer::USAGESNAPSHOTS_STATUS_CRITICAL_STR));
            }
        }
    }

    if( !empty($errors_arr) ){
        $message = "";
        foreach($errors_arr as $err){
            if( $server_name != $err['server_name'] ){
                $server_name = $err['server_name'];
                $message = $message . "Server '$server_name':\r\n";
            }
            $message = $message . " Logical volume '".$err['lvdevice']."' in state '".$err['status_str']."' with ".sprintf('%g%%',round($err['per_usage_snapshots']*100))." usage of snapshots.\r\n";
        }
        $from = EtvaSettingPeer::retrieveByPK(EtvaSettingPeer::_ALERT_EMAIL_FROM_)->getValue();
        if( !$from ) $from = 'nuxis@eurotux.com';
        $to = EtvaSettingPeer::retrieveByPK(EtvaSettingPeer::_ALERT_EMAIL_)->getValue();
        if( !$to ) $to = 'tec@eurotux.com';
        $subject_prefix = EtvaSettingPeer::retrieveByPK(EtvaSettingPeer::_ALERT_SUBJECT_PREFIX_)->getValue();
        if( !$subject_prefix ) $subject_prefix = 'Nuxis -';

        #$to = "cmar@eurotux.com";
        $subject = $subject_prefix . " " . "Servers snapshots usage report";
        $headers = "From: $from\r\n";
        mail($to, $subject, $message, $headers);

        $this->log("[INFO] Email with report sent to $to");
    } else {
        $this->log("[INFO] No errors to report");
    }
  }
}
