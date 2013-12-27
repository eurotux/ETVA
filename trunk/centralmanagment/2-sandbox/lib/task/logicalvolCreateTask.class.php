<?php

class logicalvolCreateTask extends sfBaseTask
{
  protected function configure()
  {
    // // add your own arguments here
    $this->addArguments(array(
      new sfCommandArgument('name', sfCommandArgument::REQUIRED, 'New logical volume name'),
      new sfCommandArgument('volumegroup', sfCommandArgument::REQUIRED, 'The volume group where logical volume should be created'),
      new sfCommandArgument('size', sfCommandArgument::REQUIRED, 'Size of new logical volume'),
    ));

    $this->addOptions(array(
      new sfCommandOption('application', null, sfCommandOption::PARAMETER_REQUIRED, 'The application name'),
      new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'prod'),
      new sfCommandOption('connection', null, sfCommandOption::PARAMETER_REQUIRED, 'The connection name', 'propel'),
      // add your own options here
      new sfCommandOption('level', null, sfCommandOption::PARAMETER_OPTIONAL, 'The level context: cluster or node'),
      new sfCommandOption('cluster', null, sfCommandOption::PARAMETER_OPTIONAL, 'The cluster context'),
      new sfCommandOption('node', null, sfCommandOption::PARAMETER_OPTIONAL, 'The node where logical volume should be created'),
      new sfCommandOption('format', null, sfCommandOption::PARAMETER_OPTIONAL, 'The format'),
      new sfCommandOption('persnapshotusage', null, sfCommandOption::PARAMETER_OPTIONAL, 'Percentage of snapshot usage'),
    ));

    $this->namespace        = 'logicalvol';
    $this->name             = 'create';
    $this->briefDescription = 'Create logical volume';
    $this->detailedDescription = <<<EOF
The [logicalvol:create|INFO] task does things.
Call it with:

  [php symfony logicalvol:create|INFO]
EOF;
  }

  public function dependsOnIt(EtvaAsynchronousJob $j1, EtvaAsynchronousJob $j2)
  {
    if( $j1->getTasknamespace() == $j2->getTasknamespace() )    // logicalvolume operations
    {
        if( $j2->getTaskname() !== 'clone' )        // unless is clone
        {
            return true;
        }
    }
    else if( $j2->getTasknamespace() == 'volumegroup' )     // volumegroup operations
    {
        return true;
    }
    else if( $j2->getTasknamespace() == 'physicalvolume' )  // physicalvolume operations
    {
        return true;
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
    // get parameters
    $lv = $arguments['name'];
    $size = $arguments['size'];
    $vg = $arguments['volumegroup'];

    $node_id = $options['node'];

    $format = $options['format'];
    $persnapshotusage = $options['persnapshotusage'];

    /*
     * check if lv is a file disk instead
     * if is a file disk check if special volume group exists. if not create
     */
    $is_DiskFile = ($vg == sfConfig::get('app_volgroup_disk_flag')) ? 1:0;

    // get etva_node
    $etva_node = EtvaNodePeer::getOrElectNodeFromArray(array_merge($options,$arguments));
    if(!$etva_node)
    {
        $msg_i18n = $context->getI18N()->__(EtvaNodePeer::_ERR_NOTFOUND_ID_,array('%id%'=>$node_id));

        $error = array('success'=>false,'agent'=>sfConfig::get('config_acronym'),'error'=>$msg_i18n,'info'=>$msg_i18n);
        $this->log("[ERROR] ".$error['error']);
        return $error;
    }

    // get logical volume 
    if($etva_lv = $etva_node->retrieveLogicalvolumeByLv($lv))  //lv is the logical volume name
    {
        $msg_type = $is_DiskFile ? EtvaLogicalvolumePeer::_ERR_DISK_EXIST_ : EtvaLogicalvolumePeer::_ERR_LV_EXIST_;
        $msg = Etva::getLogMessage(array('name'=>$lv), $msg_type);
        $msg_i18n = $context->getI18N()->__($msg_type,array('%name%'=>$lv));

        $error = array('success'=>false,
                   'agent'=>$etva_node->getName(),
                   'error'=>$msg_i18n,
                   'info'=>$msg_i18n);
        $this->log("[ERROR] ".$error['error']);
        return $error;
    }
        
    if(!$etva_vg = $etva_node->retrieveVolumegroupByVg($vg))
    {
        $msg = Etva::getLogMessage(array('name'=>$vg), EtvaVolumegroupPeer::_ERR_NOTFOUND_);
        $msg_i18n = $context->getI18N()->__(EtvaVolumegroupPeer::_ERR_NOTFOUND_,array('%name%'=>$vg));

        $error = array('success'=>false,'agent'=>$etva_node->getName(),'error'=>$msg_i18n, 'info'=>$msg_i18n);
        $this->log("[ERROR] ".$error['error']);
        return $error;
    }

    // prepare soap info....
    $etva_lv = new EtvaLogicalvolume();
    $etva_lv->setEtvaVolumegroup($etva_vg);
    $etva_lv->setLv($lv);
    $lv_va = new EtvaLogicalvolume_VA($etva_lv);

    $response = $lv_va->send_create($etva_node,$size,$format,$persnapshotusage);

    if( !$response['success'] ){
        $this->log("[ERROR] ".$response['error']);
    } else {
        $this->log("[INFO] ".$response['response']);
    }
    return $response;
  }
}
