<?php

class clusterChecksharedstorageconsistencyTask extends etvaBaseTask
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
      new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'dev'),
      new sfCommandOption('connection', null, sfCommandOption::PARAMETER_REQUIRED, 'The connection name', 'propel'),
      // add your own options here
    ));

    $this->namespace        = 'cluster';
    $this->name             = 'check-shared-storage-consistency';
    $this->briefDescription = 'Check shared storage consistency';
    $this->detailedDescription = <<<EOF
The [cluster:check-shared-storage-consistency|INFO] task does things.
Call it with:

  [php symfony cluster:check-shared-storage-consistency|INFO]
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

    $this->log('Checking shared storage cluster consistency...'."\n");

    $ok = 1;

    $affected = 0;
    $errors = array();

    $clusters = EtvaClusterPeer::doSelect(new Criteria());
    foreach ($clusters as $cluster){

        $bulk_response_lvs = $cluster->soapSend('getlvs',array('force'=>1));
        $bulk_response_dtable = $cluster->soapSend('device_table');

        $lv_va = new EtvaLogicalvolume_VA();

        foreach($bulk_response_lvs as $node_id =>$node_response){
            if($node_response['success']){ //response received ok

                $lvs = (array) $node_response['response'];
                $node = EtvaNodePeer::retrieveByPK($node_id); 

                $consist = $lv_va->check_shared_consistency($node,$lvs);
                
                $response_dtable = (array)$bulk_response_dtable[$node_id];
                if( $response_dtable['success'] ){
                    $dtable = (array)$response_dtable['response'];
                    $consist_dtable = $lv_va->check_shared_devicetable_consistency($node,$dtable,$bulk_response_dtable);
                }

                if( !$consist || !$consist_dtable ){
                    $errors = $lv_va->get_missing_lv_devices();
                    $msg = $errors ? $errors['message'] :
                                    sfContext::getInstance()->getI18N()->__(EtvaLogicalvolumePeer::_ERR_INCONSISTENT_,array('%info%'=>''));

                    $err_msg = sprintf( " node with id=%s is not consistent: %s \n", $node_id, $msg );
                    $errors[] = array( 'message'=> $err_msg, 'debug'=>array( 'consist_lvs'=>$consist, 'consist_dtable'=>$consist_dtable ) );
                    $affected++;
                    $node->setErrorMessage(EtvaLogicalvolume_VA::LVINIT);
                } else {
                    $node->clearErrorMessage(EtvaLogicalvolume_VA::LVINIT);
                }

            } else {
                $errors[] = $node_response;
                $affected++;
            }
        }

    }

    if($clusters)
    {
        $message = sprintf('%d Clusters(s) could not be checked for shared storage consistency.\nErrors: %s ', $affected, print_r($errors,true));

        if($affected > 0)
            $context->getEventDispatcher()->notify(
                new sfEvent(sfConfig::get('config_acronym'), 'event.log',
                    array('message' => $message,'priority'=>EtvaEventLogger::ERR)));
    }


    if(!empty($errors)){
        $this->log( $message );
    } else {

        // log the message!
        $this->log("The check shared storage cluster consistency task ran!", 6);
    }

    

  }
}
