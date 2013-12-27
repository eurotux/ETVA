<?php

class logicalvolCloneTask extends sfBaseTask
{
  protected function configure()
  {
    // // add your own arguments here
    $this->addArguments(array(
      new sfCommandArgument('original', sfCommandArgument::REQUIRED, 'Original logical volume'),
      new sfCommandArgument('logicalvolume', sfCommandArgument::REQUIRED, 'New logical volume'),
    ));

    $this->addOptions(array(
      new sfCommandOption('application', null, sfCommandOption::PARAMETER_REQUIRED, 'The application name'),
      new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'prod'),
      new sfCommandOption('connection', null, sfCommandOption::PARAMETER_REQUIRED, 'The connection name', 'propel'),
      // add your own options here
      new sfCommandOption('level', null, sfCommandOption::PARAMETER_OPTIONAL, 'The level context: cluster or node'),
      new sfCommandOption('cluster', null, sfCommandOption::PARAMETER_OPTIONAL, 'The cluster context'),
      new sfCommandOption('node', null, sfCommandOption::PARAMETER_OPTIONAL, 'The node context'),
      new sfCommandOption('volumegroup', null, sfCommandOption::PARAMETER_REQUIRED, 'The volume group of logical volume'),
      new sfCommandOption('original-volumegroup', null, sfCommandOption::PARAMETER_REQUIRED, 'The volume group of original logical volume'),
    ));

    $this->namespace        = 'logicalvol';
    $this->name             = 'clone';
    $this->briefDescription = 'Clone logical volume';
    $this->detailedDescription = <<<EOF
The [logicalvol:clone|INFO] task does things.
Call it with:

  [php symfony logicalvol:clone|INFO]
EOF;
  }

  public function dependsOnIt(EtvaAsynchronousJob $j1, EtvaAsynchronousJob $j2)
  {
    if( $j2->getTasknamespace() == 'server' )   // server operations
    {
        if( ($j2->getTaskname() == 'stop') ||   // stop or start
            ($j2->getTaskname() == 'start') )
        {
            $j1_args = (array) json_decode($j1->getArguments());
            $j1_opts = (array) json_decode($j1->getOptions());
            $etva_node = EtvaNodePeer::getOrElectNodeFromArray(array_merge($j1_opts,$j1_args));
            if( $etva_node )
            {
                // original logical volume
                $original_lv = $j1_args['original'];
                $original_vg = $j1_opts['original-volumegroup'];

                // find orignal logical volume
                $etva_original_lv = $etva_node->retrieveLogicalvolumeByAny($original_lv,$original_vg);
                if( $etva_original_lv )
                {
                    $j2_args = (array) json_decode($j2->getArguments());
                    $lv_servers = $etva_original_lv->getServers();  // get servers where logical volume is attached
                    foreach( $lv_servers as $srv )
                    {
                        if( ($srv->getId() == $j2_args['server']) ||        // if the server is the same
                                ($srv->getUuid() == $j2_args['server']) ||
                                ($srv->getName() == $j2_args['server']) )
                        {
                            return true;                            // depends on it
                        }
                    }
                }
            }
        }
    }
    return false;
  }

  protected function execute($arguments = array(), $options = array())
  {
    $context = sfContext::createInstance(sfProjectConfiguration::getApplicationConfiguration('app',$options['env'],true));

    // initialize the database connection
    $databaseManager = new sfDatabaseManager($this->configuration);
    $connection = $databaseManager->getDatabase($options['connection'])->getConnection();

    // add your code here

    // get node
    $node_id = $options['node'];
    $etva_node = EtvaNodePeer::getOrElectNodeFromArray(array_merge($options,$arguments));
    if(!$etva_node)
    {
        $msg_i18n = $context->getI18N()->__(EtvaNodePeer::_ERR_NOTFOUND_ID_,array('%id%'=>$node_id));

        $error = array('success'=>false,'agent'=>sfConfig::get('config_acronym'),'error'=>$msg_i18n,'info'=>$msg_i18n);
        $this->log("[ERROR] ".$error['error']);
        return $error;
    }

    // logical volume to clone
    $lv = $arguments['logicalvolume'];
    $vg = $options['volumegroup'];

    // check if a logical volume exists
    if(!$etva_lv = $etva_node->retrieveLogicalvolumeByAny($lv,$vg))  //lv is the logical volume name
    {
        $msg = Etva::getLogMessage(array('name'=>$lv), EtvaLogicalvolumePeer::_ERR_NOTFOUND_);
        $msg_i18n = $context->getI18N()->__(EtvaLogicalvolumePeer::_ERR_NOTFOUND_,array('%name%'=>$lv));

        $error = array('success'=>false,'agent'=>sfConfig::get('config_acronym'),'error'=>$msg_i18n,'info'=>$msg_i18n);
        $this->log("[ERROR] ".$error['error']);
        return $error;
    }

    // original logical volume
    $original_lv = $arguments['original'];
    $original_vg = $options['original-volumegroup'];

    // if cannot find logical volume
    if(!$etva_original_lv = $etva_node->retrieveLogicalvolumeByAny($original_lv,$original_vg))
    {
        $msg = Etva::getLogMessage(array('name'=>$original_lv), EtvaLogicalvolumePeer::_ERR_NOTFOUND_);
        $msg_i18n = $context->getI18N()->__(EtvaLogicalvolumePeer::_ERR_NOTFOUND_,array('%name%'=>$original_lv));

        $error = array('success'=>false,'agent'=>sfConfig::get('config_acronym'),'error'=>$msg_i18n,'info'=>$msg_i18n);
        $this->log("[ERROR] ".$error['error']);
        return $error;
    }

    // prepare soap info....
    $lv_va = new EtvaLogicalvolume_VA($etva_lv);

    $response = $lv_va->send_clone($etva_node,$etva_original_lv);

    if( !$response['success'] ){
        $this->log("[ERROR] ".$response['error']);
    } else {
        $this->log("[INFO] ".$response['response']);
    }
    return $response;
  }

}
