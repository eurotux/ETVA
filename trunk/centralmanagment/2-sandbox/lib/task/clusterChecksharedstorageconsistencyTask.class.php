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
      new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'prod'),
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

        $bulk_response_lvs = $cluster->soapSend('getlvs_arr',array('force'=>1));
        $bulk_response_dtable = $cluster->soapSend('device_table');

        $lv_va = new EtvaLogicalvolume_VA();

        foreach($bulk_response_lvs as $node_id =>$node_response){
            if($node_response['success']){ //response received ok

                $lvs = (array) $node_response['response'];
                $node = EtvaNodePeer::retrieveByPK($node_id); 

                //$consist = $lv_va->check_shared_consistency($node,$lvs);
                
                $response_dtable = (array)$bulk_response_dtable[$node_id];
                $dtable = array();
                if( $response_dtable['success'] ){
                    $dtable = (array)$response_dtable['response'];
                    //$consist_dtable = $lv_va->check_shared_devicetable_consistency($node,$dtable,$bulk_response_dtable);
                }

                $check_res = $lv_va->check_consistency($node,$lvs,$dtable,$bulk_response_dtable);


                if( !$check_res['success'] ){
                    $err = $check_res['errors'];
                    $msg = sfContext::getInstance()->getI18N()->__(EtvaLogicalvolumePeer::_ERR_INCONSISTENT_,array('%info%'=>''));

                    $err_msg = sprintf( " node with id=%s is not consistent: %s \n", $node_id, $msg );
                    $errors[] = array( 'message'=> $err_msg, 'debug'=>$err );
                    $affected++;
                    $node->setErrorMessage(EtvaLogicalvolume_VA::LVINIT);
                } else {
                    $node->clearErrorMessage(EtvaLogicalvolume_VA::LVINIT);
                }

                /*if( !$consist || !$consist_dtable ){
                    $errors = $lv_va->get_missing_lv_devices();
                    $msg = $errors ? $errors['message'] :
                                    sfContext::getInstance()->getI18N()->__(EtvaLogicalvolumePeer::_ERR_INCONSISTENT_,array('%info%'=>''));

                    $err_msg = sprintf( " node with id=%s is not consistent: %s \n", $node_id, $msg );
                    $errors[] = array( 'message'=> $err_msg, 'debug'=>array( 'consist_lvs'=>$consist, 'consist_dtable'=>$consist_dtable ) );
                    $affected++;
                    $node->setErrorMessage(EtvaLogicalvolume_VA::LVINIT);
                } else {
                    $node->clearErrorMessage(EtvaLogicalvolume_VA::LVINIT);
                }*/

            } else {
                $errors[] = $node_response;
                $affected++;
            }
        }

        $bulk_response_pvs = $cluster->soapSend('hash_phydisks',array('force'=>1));

        $pv_va = new EtvaPhysicalvolume_VA();

        foreach($bulk_response_pvs as $node_id =>$node_response){
            if($node_response['success']){ //response received ok

                $pvs = (array) $node_response['response'];
                $node = EtvaNodePeer::retrieveByPK($node_id); 

                $check_res = $pv_va->check_consistency($node,$pvs);


                if( !$check_res['success'] ){
                    $err = $check_res['errors'];
                    $msg = sfContext::getInstance()->getI18N()->__(EtvaPhysicalvolumePeer::_ERR_INCONSISTENT_,array('%info%'=>''));

                    $err_msg = sprintf( " node with id=%s is not consistent: %s \n", $node_id, $msg );
                    $errors[] = array( 'message'=> $err_msg, 'debug'=>$err );
                    $affected++;
                    $node->setErrorMessage(EtvaPhysicalvolume_VA::PVINIT);
                } else {
                    $node->clearErrorMessage(EtvaPhysicalvolume_VA::PVINIT);
                }

            } else {
                $errors[] = $node_response;
                $affected++;
            }
        }

        $bulk_responses_vgs = $cluster->soapSend('getvgpvs',array('force'=>1));

        $vg_va = new EtvaVolumegroup_VA();

        foreach($bulk_response_vgs as $node_id =>$node_response){
            if($node_response['success']){ //response received ok

                $vgs = (array) $node_response['response'];
                $node = EtvaNodePeer::retrieveByPK($node_id); 

                $check_res = $vg_va->check_consistency($node,$vgs);

                if( !$check_res['success'] ){
                    $err = $check_res['errors'];
                    $msg = sfContext::getInstance()->getI18N()->__(EtvaVolumegroupPeer::_ERR_INCONSISTENT_,array('%info%'=>''));

                    $err_msg = sprintf( " node with id=%s is not consistent: %s \n", $node_id, $msg );
                    $errors[] = array( 'message'=> $err_msg, 'debug'=>$err );
                    $affected++;
                    $node->setErrorMessage(EtvaVolumegroup_VA::VGINIT);
                } else {
                    $node->clearErrorMessage(EtvaVolumegroup_VA::VGINIT);
                }

            } else {
                $errors[] = $node_response;
                $affected++;
            }
        }
    }

    if(!empty($errors)){
        $this->log( $message );
    } else {

        // log the message!
        $this->log("The check shared storage cluster consistency task ran!", 6);
    }

    

  }
}
